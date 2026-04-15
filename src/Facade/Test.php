<?php

namespace Rxkk\App\Facade;


use Rxkk\Lib\Env;
use Rxkk\Lib\MySQL;

/**
 * @DOC Class for example. You can delete it.
 */
class Test {
    /**
     * @param string|null $yourParams  Any string value to echo back
     *
     * @DOC [your_param] just example how to run it
     * @EXAMPLE x Test::example myParam
     * @MCP Returns the given parameter back as a string. Use to verify the MCP connection is working.
     */
    public static function example($yourParams = null) {
        var_dump('var_dump from Test::test with params: ', $yourParams);
        return 'text from Test::test with params: ' . $yourParams;
    }

    /**
     * @param string $envName  Name of the environment variable to read
     *
     * @DOC <envName> get environment variable from .env OR system environment
     * @EXAMPLE x Test::env test
     * @MCP Returns the value of an environment variable from .env or system env.
     *      Use this to check configuration values before operations.
     */
    public static function env($envName) {
        $envValue = Env::get($envName);
        return $envValue;
    }

    /**
     * @DOC test MySQL connection
     * @EXAMPLE x Test::testMySQL
     * @MCP Tests the MySQL connection and returns the server version. Use to verify DB connectivity.
     */
    public static function testMYSQL() {
        $version = MySQL::q("SELECT VERSION()");
        return $version;
    }
}
