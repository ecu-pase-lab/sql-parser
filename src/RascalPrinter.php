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
        $res = "selectQuery(";

        // print select expressions
        $res .=  self::printExpressionList($parsed->expr);

        if(!is_null($parsed->from)) {
            // print tables to be selected from
            $res .= ", " . self::printExpressionList($parsed->from);
        }

        if(!is_null($parsed->where)){
            // print the conditions used for filtering the result
            $res .= ", " . self::printConditionList($parsed->where);
        }

        $res .= ")";

        return $res;
    }

    public static function printUpdateQuery($parsed){
        $res = "updateQuery(";

        // print the tables to be updated
        if(!is_null($parsed->tables)) {
            $res .= self::printExpressionList($parsed->tables);
        }

        //TODO: set operations
        //if(!is_null($parsed->set){...}

        if(!is_null($parsed->where)){
            $res .= self::printConditionList($parsed->where);
        }

        $res .= ")";

        return $res;
    }

    public static function printInsertQuery($parsed){
        $res = "insertQuery(";

        // print the table data will be inserted into
        $res .= self::printExpression($parsed->into);

        //TODO: values statement
        //if(!is_null($parsed->values){...}

        //TODO: set operations
        //if(!is_null($parsed->set){...}

        $res .= ")";

        return $res;
    }

    public static function printDeleteQuery($parsed){
        $res = "deleteQuery(";

        // print the tables to be deleted from
        $res .= self::printExpressionList($parsed->from);

        //TODO: other clauses for delete

        $res .= ")";

        return $res;
    }

    //TODO: handle all cases
    public static function printExpression($exp){
        // first, check for simple cases where this expression is a column, table, database name, or *
        switch($exp->expr){
            case("*"):
                return "star()";
            // this expression is a column name
            case($exp->column):
                return "name(column(\"" . $exp->column . "\"))";
            // this expression is a table name
            case($exp->table):
                return "name(table(\"" . $exp->table . "\"))";
            // this expression is a database name
            case($exp->database):
                return "name(database(\"" . $exp->database . "\"))";
            case($exp->table . "." . $exp->column):
                return "name(tableColumn(\"" . $exp->table . "\", \"" . $exp->column . "\"))";
            case($exp->database . "." . $exp->table):
                return "name(databaseTable(\"" . $exp->database . "\", \"" . $exp->table . "\"))";
            case($exp->database . "." . $exp->table . "." . $exp->column):
                return "name(databaseTableColumn(\"" . $exp->database . "\", \"" . $exp->table . "\", \"" . $exp->column . "\"))";
        }
    }

    /*
     * prints comma separated rascal list of expressions
     */
    public static function printExpressionList($expressions){
        $size = sizeof($expressions);

        $res = "[";
        for($i = 0; $i < $size - 1; $i++){
            $res .= self::printExpression($expressions[$i]) . ", ";
        }
        $res .= self::printExpression($expressions[$size - 1]) . "]";

        return $res;
    }

    /*
     * Prints list of conditions found in a WHERE clause in rascal format
     */
    public static function printConditionList($conditions){
        $size = sizeof($conditions);
        // TODO: implement condition list printing
        return "";
    }
}