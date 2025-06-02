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