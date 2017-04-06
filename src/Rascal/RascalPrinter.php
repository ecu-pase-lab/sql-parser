<?php

namespace PhpMyAdmin\SqlParser\Rascal;

use PhpMyAdmin\SqlParser\Components\Condition;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\DeleteStatement;
use PhpMyAdmin\SqlParser\Statements\InsertStatement;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\SqlParser\Statements\UpdateStatement;

require_once("../../vendor/autoload.php");

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
            $res .= ", " . self::printWhere($parsed->where);
        }
        else{
            $res .= ", noWhere()";
        }

        if(!is_null($parsed->group)){
            $res .= ", " . self::printGroupBy($parsed->group);
        }
        else{
            $res .= ", noGroupBy()";
        }

        if(!is_null($parsed->having)){
            $res .= ", " . self::printHaving($parsed->having);
        }
        else{
            $res .= ", noHaving()";
        }

        if(!is_null($parsed->order)){
            $res .= ", " . self::printOrderBy($parsed->order);
        }
        else{
            $res .= ", noOrderBy()";
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
            $res .= ", " . self::printWhere($parsed->where);
        }
        else{
            $res .= ", noWhere()";
        }

        if(!is_null($parsed->order)){
            $res .= ", " . self::printOrderBy($parsed->order);
        }
        else{
            $res .= ", noGroupBy()";
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

    //TODO: other clauses for delete
    public static function printDeleteQuery($parsed){
        $res = "deleteQuery(";

        // print the tables to be deleted from
        $res .= self::printExpressionList($parsed->from);

        if(!is_null($parsed->where)){
            $res .= ", " . self::printWhere($parsed->where);
        }
        else{
            $res .= ", noWhere()";
        }

        if(!is_null($parsed->order)){
            $res .= ", " . self::printOrderBy($parsed->order);
        }

        else{
            $res .= ", noOrderBy()";
        }
        
        $res .= ")";

        return $res;
    }

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

        if(!is_null($exp->function)){
            //TODO: handle function params
            return "call(\"" . $exp->function . "\")";
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
        } $res .= self::printExpression($expressions[$size - 1]) . "]";

        return $res;
    }


    /*
     * Prints a where clause in rascal format
     */
    public static function printWhere($where){
        return "where(" . self::printConditions(self::conditionsToTree($where)) . ")";
    }

    /*
     * Prints a having clause in rascal format
     */
    public static function printHaving($having){
        return "where(" . self::printConditions(self::conditionsToTree($having)) . ")";
    }

    /*
     * Prints GROUP BY clause in rascal format
     */
    public static function printGroupBy($grouping){
        $size = sizeof($grouping);

        $res = "groupBy({";
        for($i = 0; $i < $size - 1; $i++){
            $res .= "<" . self::printExpression($grouping[$i]->expr) . ", \"" . $grouping[$i]->type . "\">";
            $res .= ", ";
        }
        $res .= "<" . self::printExpression($grouping[$size - 1]->expr) . ", \"" . $grouping[$size - 1]->type . "\">";

        $res .= "})";

        return $res;
    }

    /*
     * Prints ORDER BY clause in rascal format
     */
    public static function printOrderBy($ordering){
        $size = sizeof($ordering);

        $res = "orderBy({";
        for($i = 0; $i < $size - 1; $i++){
            $res .= "<" . self::printExpression($ordering[$i]->expr) . ", \"" . $ordering[$i]->type . "\">";
            $res .= ", ";
        }
        $res .= "<" . self::printExpression($ordering[$size - 1]->expr) . ", \"" . $ordering[$size - 1]->type . "\">";

        $res .= "})";

        return $res;
    }

    /*
     * prints conditions from WHERE or HAVING clause in rascal format
     */
    public static function printConditions($tree){
        if(is_null($tree->left) && is_null($tree->right)){
            return "condition(\"" . $tree->value . "\")";
        }
        else if($tree->value === "NOT"){
            return "not(\"" . self::printConditions($tree->left) . "\")";
        }
        else if($tree->value === "AND" || $tree->value === "&&"){
            return "and(" . self::printConditions($tree->left) . ", " . self::printConditions($tree->right) . ")";
        }
        else if($tree->value === "OR" || $tree->value === "||"){
            return "or(" . self::printConditions($tree->left) . ", " . self::printConditions($tree->right) . ")";
        }
        else if($tree->value === "XOR"){
            return "xor(" . self::printConditions($tree->left) . ", " . self::printConditions($tree->right) . ")";
        }
        else{
            echo "unexpected condition tree node";
            exit(1);
        }
    }

    /*
     * uses shunting yard to get the conditions from WHERE or HAVING in a tree data structure
     * TODO: process expressions rather than just print them as strings
     * TODO: handle parentheses
     */
    public static function conditionsToTree($conditions){
        $output = array();
        $stack = array();
        $precedence = [
          "NOT" => 3,
            "AND" => 2,
            "&&" => 2,
            "OR" => 1,
            "||" => 1,
            "XOR" => 1
        ];

        foreach($conditions as $condition){
            if($condition->isOperator === false){
                // this condition starts with a left paren, push it to the operator stack
                if($condition->expr[0] === "("){
                    array_push($stack, "(");
                    $condition->expr = trim(substr($condition->expr, 1));
                }

                // this condition is negated, push "NOT" onto the operator stack
                if(strtoupper(substr($condition->expr, 0, 3)) === "NOT"){
                    array_push($stack, "NOT");
                    $condition->expr = trim(substr($condition->expr, 4));
                }

                // this condition ends with a right paren, build up the tree until a left paren is on top of the stack
                if(substr($condition->expr, -1) === ")"){
                    array_push($output, new ConditionNode(trim(substr($condition->expr, 0, sizeof($condition->expr) - 2))));
                    while(!(sizeof($stack) === 0)){
                        $op = array_pop($stack);
                        if($op === "("){
                            break;
                        }
                        else{
                            $node = new ConditionNode($op);
                            $node->left = array_pop($output);
                            if($op !== "NOT"){
                                $node->right = array_pop($output);
                            }
                            array_push($output, $node);
                        }
                    }
                }
                // push the simple condition as a new leaf
                else{
                    array_push($output, new ConditionNode($condition->expr));
                }
            }
            else{
                $op1 = strtoupper($condition->expr);
                while(!(sizeof($stack) === 0) && $stack[sizeof($stack) - 1] !== '('){
                    $op2 = $stack[sizeof($stack) - 1];
                    if(in_array($op1, Condition::$DELIMITERS) && $precedence[$op1] <= $precedence[$op2]){
                        $node = new ConditionNode($op2);
                        $node->left = array_pop($output);
                        if($op2 !== "NOT") {
                            $node->right = array_pop($output);
                        }
                        array_push($output, $node);
                        array_pop($stack);
                    }
                }
                array_push($stack, $op1);
            }
        }
        while(!(sizeof($stack) === 0)) {
            $op = array_pop($stack);
            $node = new ConditionNode($op);
            $node->left = array_pop($output);
            if(!($op === "NOT")) {
                $node->right = array_pop($output);
            }
            array_push($output, $node);
        }
        return $output[0];
    }
}

class ConditionNode
{
    public $value;
    public $left;
    public $right;

    public function __construct($item) {
        $this->value = $item;
        $this->left = null;
        $this->right = null;
    }
}