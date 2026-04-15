<?php

namespace Rxkk\Sys;

/**
 * MCP stdio server (JSON-RPC 2.0).
 * Runs as a persistent process: reads from STDIN, writes to STDOUT.
 * Stderr is used for debug logging so it does not pollute the protocol stream.
 */
class McpServer {

    private FacadeHandler $facadeHandler;

    public function __construct(FacadeHandler $facadeHandler) {
        $this->facadeHandler = $facadeHandler;
    }

    public function run(): void {
        while (true) {
            $line = fgets(STDIN);
            if ($line === false) {
                break;
            }

            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $message = json_decode($line, true);
            if ($message === null) {
                $this->log('Invalid JSON: ' . $line);
                continue;
            }

            $this->handle($message);
        }
    }

    private function handle(array $message): void {
        $method = $message['method'] ?? '';
        $id     = $message['id'] ?? null;

        // Notifications have no id and need no response
        if ($id === null) {
            return;
        }

        switch ($method) {
            case 'initialize':
                $this->respond($id, [
                    'protocolVersion' => '2024-11-05',
                    'capabilities'    => ['tools' => new \stdClass()],
                    'serverInfo'      => ['name' => 'rxkk-app', 'version' => '1.0.0'],
                ]);
                break;

            case 'ping':
                $this->respond($id, new \stdClass());
                break;

            case 'tools/list':
                $tools = $this->facadeHandler->getMcpTools();
                $this->respond($id, ['tools' => $tools]);
                break;

            case 'tools/call':
                $this->handleToolCall($id, $message['params'] ?? []);
                break;

            default:
                $this->respondError($id, -32601, "Method not found: $method");
        }
    }

    private function handleToolCall($id, array $params): void {
        $name      = $params['name'] ?? '';
        $arguments = $params['arguments'] ?? [];

        try {
            $argv = $this->facadeHandler->buildArgv($name, $arguments);

            // Capture any direct output (var_dump, echo) so it does not break the JSON stream
            ob_start();
            $result    = $this->facadeHandler->exec($name, $argv);
            $stdOutput = ob_get_clean();

            $text = $this->serializeResult($result, $stdOutput);

            $this->respond($id, [
                'content' => [['type' => 'text', 'text' => $text]],
            ]);
        } catch (\Throwable $e) {
            $this->respond($id, [
                'content' => [['type' => 'text', 'text' => 'Error: ' . $e->getMessage()]],
                'isError' => true,
            ]);
        }
    }

    private function serializeResult(mixed $result, string $stdOutput): string {
        $parts = [];

        if ($stdOutput !== '') {
            $parts[] = $stdOutput;
        }

        if (!is_null($result)) {
            $parts[] = is_string($result)
                ? $result
                : json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $parts !== [] ? implode("\n", $parts) : 'OK';
    }

    private function respond(mixed $id, mixed $result): void {
        $response = ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
        fwrite(STDOUT, json_encode($response) . "\n");
        fflush(STDOUT);
    }

    private function respondError(mixed $id, int $code, string $message): void {
        $response = ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]];
        fwrite(STDOUT, json_encode($response) . "\n");
        fflush(STDOUT);
    }

    private function log(string $message): void {
        fwrite(STDERR, "[McpServer] $message\n");
    }
}
