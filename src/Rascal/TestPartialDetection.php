<?php
/**
 * Created by PhpStorm.
 * User: danderson
 * Date: 9/11/17
 * Time: 9:34 AM
 */
namespace PhpMyAdmin\SqlParser;

use PhpMyAdmin\SqlParser\Statements\PartialStatement;

require_once("../../vendor/autoload.php");

$lexer = new Lexer($argv[1]);
$tokens = $lexer->list;
$partial = new PartialStatement($tokens);
$partial->checkIfPartial();
var_dump($partial);