<?php
/**
 * Created by PhpStorm.
 * User: andersonda
 * Date: 3/24/17
 * Time: 1:49 PM
 */

namespace PhpMyAdmin\SqlParser;


class Autoloader
{
    /** @var bool Whether the autoloader has been registered. */
    private static $registered = false;

    /**
     * Registers PhpParser\Autoloader as an SPL autoloader.
     *
     * @param bool $prepend Whether to prepend the autoloader instead of appending
     */
    static public function register($prepend = false) {
        if (self::$registered === true) {
            return;
        }

        spl_autoload_register(array(__CLASS__, 'autoload'), true, $prepend);
        self::$registered = true;
    }

    /**
     * Handles autoloading of classes.
     *
     * @param string $class A class name.
     */
    static public function autoload($class) {
        if (0 === strpos($class, 'PhpMyAdmin\\SqlParser\\')) {
            $fileName = __DIR__ . '/' . strtr(substr($class, 21), '\\', '/') . '.php';
            if (file_exists($fileName)) {
                require $fileName;
            }
        }
    }
}