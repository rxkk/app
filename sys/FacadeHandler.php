<?php

namespace Rxkk\Sys;

use ReflectionMethod;
use Rxkk\Lib\Console;
use Rxkk\Lib\Exception\AppException;

/**
 * Found Facade classes
 *  - provide information about them
 *  - execute their methods
 */
class FacadeHandler {

    private $facadeRoot;
    private $facadeNamespace;

    public function __construct($facadeRoot, $facadeNamespace) {
        $this->facadeRoot = rtrim($facadeRoot, '/') . '/';
        $this->facadeNamespace = $facadeNamespace;
    }

    public function exec($command, $argv) {
        [$class, $method] = explode('::', $command);

        $class = $this->getClassPath($class);

        // check exist class
        if (!class_exists($class)) {
            throw new AppException("Class $class not exist");
        }

        // check exist method
        if (!method_exists($class, $method)) {
            throw new AppException("Method $method not exist in class $class");
        }

        // execute method
        return $class::$method(...$argv);
    }

    public function getClassPath($shortClassName) {
        $files = glob($this->facadeRoot . '*.php');

        $mapShortNameToFullName = [];
        foreach ($files as $file) {
            $file = basename($file, '.php');
            $class = $this->facadeNamespace . '\\' . $file;
            $mapShortNameToFullName[$file] = $class;
        }

        return $mapShortNameToFullName[$shortClassName];
    }

    public function isCodeHandler($command) {
        // string contain ::
        if (strpos($command, '::') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Returns all @MCP annotated methods as MCP tool definitions.
     */
    public function getMcpTools(): array {
        $files = glob($this->facadeRoot . '*.php');
        $tools = [];

        foreach ($files as $file) {
            $file = basename($file, '.php');
            $class = $this->facadeNamespace . '\\' . $file;

            $reflectionClass = new \ReflectionClass($class);
            $publicMethods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($publicMethods as $method) {
                $docComment = $method->getDocComment();
                if (!$docComment) {
                    continue;
                }

                $description = $this->parseMcpDescription($docComment);
                if ($description === null) {
                    continue;
                }

                if (strpos($docComment, '@MCP-CONFIRM') !== false) {
                    $description .= ' [REQUIRES USER CONFIRMATION: Ask the user before executing this tool.]';
                }

                $toolName = $reflectionClass->getShortName() . '::' . $method->getName();

                $tools[] = [
                    'name'        => $toolName,
                    'description' => $description,
                    'inputSchema' => $this->buildInputSchema($method, $docComment),
                ];
            }
        }

        return $tools;
    }

    /**
     * Converts named MCP arguments to positional argv for exec().
     */
    public function buildArgv(string $command, array $namedArgs): array {
        [$shortClass, $methodName] = explode('::', $command);
        $class = $this->getClassPath($shortClass);

        $method = new \ReflectionMethod($class, $methodName);
        $argv = [];

        foreach ($method->getParameters() as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $namedArgs)) {
                $argv[] = $namedArgs[$name];
            } elseif ($param->isOptional()) {
                $argv[] = $param->getDefaultValue();
            } else {
                throw new \RuntimeException("Required parameter '\$$name' not provided");
            }
        }

        return $argv;
    }

    private function buildInputSchema(\ReflectionMethod $method, string $docComment): array {
        $paramDescriptions = $this->parseParamDescriptions($docComment);
        $properties = [];
        $required = [];

        foreach ($method->getParameters() as $param) {
            $name = $param->getName();

            $type = $param->hasType() ? $param->getType() : null;
            $typeName = ($type instanceof \ReflectionNamedType) ? $type->getName() : 'string';
            $jsonType = $this->phpTypeToJsonType($typeName);

            $property = ['type' => $jsonType];
            if (isset($paramDescriptions[$name])) {
                $property['description'] = $paramDescriptions[$name];
            }

            $properties[$name] = $property;

            if (!$param->isOptional()) {
                $required[] = $name;
            }
        }

        $schema = ['type' => 'object', 'properties' => (object) $properties];
        if (!empty($required)) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    private function parseMcpDescription(string $docComment): ?string {
        // Match @MCP (not @MCP-SOMETHING) until the next @tag or end of docblock
        if (!preg_match('/@MCP(?!-)[ \t]+(.*?)(?=\n\s*\*\s*@|\*\/)/s', $docComment, $matches)) {
            return null;
        }

        $lines = explode("\n", $matches[1]);
        $clean = array_map(fn($l) => trim(ltrim(trim($l), '*')), $lines);
        $clean = array_filter($clean, fn($l) => $l !== '');

        return implode(' ', $clean);
    }

    private function parseParamDescriptions(string $docComment): array {
        preg_match_all('/@param\s+\S+\s+\$(\w+)\s+(.+)/m', $docComment, $matches, PREG_SET_ORDER);

        $result = [];
        foreach ($matches as $match) {
            $result[$match[1]] = trim($match[2]);
        }

        return $result;
    }

    private function phpTypeToJsonType(string $phpType): string {
        $phpType = ltrim($phpType, '?');

        return match ($phpType) {
            'int', 'integer' => 'integer',
            'float', 'double' => 'number',
            'bool', 'boolean' => 'boolean',
            'array' => 'array',
            default => 'string',
        };
    }

    /**
     * @return void
     */
    public function printFullList() {
        // Get all files in the facade directory
        $files = glob($this->facadeRoot . '*.php');

        foreach ($files as $file) {
            $file = basename($file, '.php');
            $class = $this->facadeNamespace . '\\' . $file;

            $reflectionClass = new \ReflectionClass($class);

            $className = $reflectionClass->getShortName();

            # get doc comment for class
            $classDoc = $reflectionClass->getDocComment();
            preg_match('/@DOC (.*)/', $classDoc, $matches);
            if (isset($matches[1])) {
                $doc = "\n\n $className - " . $matches[1];
                Console::log($doc, Console::COLOR_BREEZE);
            }

            $publicMethods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);
            foreach ($publicMethods as $publicMethod) {
                $methodName = $publicMethod->getName();

                $method = new ReflectionMethod($reflectionClass->getName(), $methodName);
                $docComment = $method->getDocComment();

                preg_match('/@DOC (.*)/', $docComment, $matches);
                if (isset($matches[1])) {
                    $doc = " - x $className::$methodName - $matches[1]";
                    Console::log($doc, Console::COLOR_GREEN);
                }
            }
        }
    }
}