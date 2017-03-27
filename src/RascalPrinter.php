<?php

namespace PhpMyAdmin\SqlParser;

use PhpMyAdmin\SqlParser\Statements\DeleteStatement;
use PhpMyAdmin\SqlParser\Statements\InsertStatement;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\SqlParser\Statements\SetStatement;
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
            return self::printSelectQuery($parsed);
        }
        else if($parsed instanceof UpdateStatement){
            return self::printUpdateQuery($parsed);
        }
        else if($parsed instanceof InsertStatement){
            return self::printInsertQuery($parsed);
        }
        else if($parsed instanceof DeleteStatement){
            return self::printDeleteQuery($parsed);
        }
        else{
            return "unknownQuery()";
        }
    }

    public static function printSelectQuery($parsed){
        return "selectQuery(" . self::printTables($parsed->from) . ")";
    }

    public static function printUpdateQuery($parsed){
        return "updateQuery(" . self::printTables($parsed->tables) . ")";
    }

    public static function printInsertQuery($parsed){
        return "insertQuery(" . self::printTables($parsed->into) . ")";
    }

    public static function printDeleteQuery($parsed){
        return "deleteQuery(" . self::printTables($parsed->from) . ")";
    }

    public static function printTables($tables){
        $size = sizeof($tables);
        if($size == 1){
            return "table(\"" . $tables[0]->table . "\")";
        }
        else{
            $res = "tables(";
            for($i = 0; $i < $size - 1; $i++){
                $res .= "\"" . $tables[$i]->table . "\", ";
            }
            //print the last table without a comma, plus the closing set notation and closing paren
            $res .= "\"" . $tables[$size - 1]->table . "\")";

            return $res;
        }
    }
}