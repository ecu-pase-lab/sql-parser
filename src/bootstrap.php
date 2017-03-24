<?php
/**
 * Created by PhpStorm.
 * User: andersonda
 * Date: 3/24/17
 * Time: 1:48 PM
 */

if (!class_exists('PhpMyAdmin\SqlParser\Autoloader')) {
    require __DIR__ . '/' . 'Autoloader.php';
}
PhpMyAdmin\SqlParser\Autoloader::register();