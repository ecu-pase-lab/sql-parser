<?php
/**
 * Created by PhpStorm.
 * User: andersonda
 * Date: 4/3/17
 * Time: 9:30 AM
 *
 * Allows for easy parser testing via the command line
 */
namespace PhpMyAdmin\SqlParser;

require_once("../../vendor/autoload.php");

$parser = new Parser($argv[1]);
var_dump($parser->statements);