<?php

use Dotenv\Dotenv;
use Rxkk\Lib\Env;
use Rxkk\Lib\Exception\AppException;
use Rxkk\Sys\FacadeHandler;
use Rxkk\Lib\Console;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

require_once __DIR__ . '/../vendor/autoload.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 'On');

# set root directory
$root = __DIR__ . '/../';
Env::setRoot($root);

# if .env file exists - load it
$envPath = $root . '.env';
if (file_exists($envPath)) {
    $dotenv = Dotenv::createImmutable($root);
    $dotenv->load();
}

Console::setArguments($argv);

try {
    $command = $argv[1] ?? '';
    $result = commandHandler($command, $argv);

    // dump result to cli
    if (!is_null($result)) {
        $cloner = new VarCloner();
        $cloner->setMaxItems(-1);
        $cloner->setMaxString(-1);

        $dumber = new CliDumper();
        $dumber->dump($cloner->cloneVar($result));
    }
}
// red message to cli for known exceptions
catch (AppException $e) {
    Console::error($e->getMessage());
    return;
}

function commandHandler($command, $argv) {

    $root = Env::getRoot();
    $facadeHandler = new FacadeHandler($root . 'src/Facade/', 'Rxkk\App\Facade');

    switch ($command) {
        case 'hello':
            return "Hello!";
        case 'custom':
            $ticket_command = $argv[2] ?? '';
            $ticket_command_sub = $argv[3] ?? '';
            return "custom command: $ticket_command $ticket_command_sub";
        case 'code':
            $facadeHandler->printFullList();
            return null;
        default:
            if ($facadeHandler->isCodeHandler($command)) {
                // from array get after second element
                $argvMethod = array_slice($argv, 2);
                return $facadeHandler->exec($command, $argvMethod);
            }

            $help = <<<HELP
Use: x [command]

Commands: 
work with code
 - x code - get list of all facade functions
 - x custom - just custom command for example
HELP;
            Console::green($help);

            return null;
    }
}
