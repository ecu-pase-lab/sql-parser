<?php

namespace PhpMyAdmin\SqlParser;

require_once ("../vendor/autoload.php");
/**
 * Created by PhpStorm.
 * User: andersonda
 * Date: 3/24/17
 * Time: 11:45 AM
 */

$printer = new RascalPrinter($argv[1]);
echo $printer->printQuery();