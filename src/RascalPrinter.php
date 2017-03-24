<?php

namespace PhpMyAdmin\SqlParser;

use PhpMyAdmin\SqlParser\Statements\DeleteStatement;
use PhpMyAdmin\SqlParser\Statements\InsertStatement;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\SqlParser\Statements\UpdateStatement;

require_once("bootstrap.php");

class RascalPrinter
{
    private $parser;

    public function __construct($query)
    {
        $this->parser = new Parser($query);
    }

    public function printQuery(){
        $parsed = $this->parser->statements[0];
        if(is_null($parsed)){
            return "parseError()";
        }
        else if($parsed instanceof SelectStatement){
            return "selectQuery()";
        }
        else if($parsed instanceof UpdateStatement){
            return "updateQuery()";
        }
        else if($parsed instanceof InsertStatement){
            return "insertQuery()";
        }
        else if($parsed instanceof DeleteStatement){
            return "deleteQuery()";
        }
        else{
            return "unknownQuery()";
        }
    }
}