<?php

namespace PhpMyAdmin\SqlParser\Rascal;

use PhpMyAdmin\SqlParser\Components\BetweenCondition;
use PhpMyAdmin\SqlParser\Components\ComparisonCondition;
use PhpMyAdmin\SqlParser\Components\Condition;
use PhpMyAdmin\SqlParser\Components\InCondition;
use PhpMyAdmin\SqlParser\Components\LikeCondition;
use PhpMyAdmin\SqlParser\Components\NotYetImplementedCondition;
use PhpMyAdmin\SqlParser\Components\NullCondition;
use PhpMyAdmin\SqlParser\Lexer;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\AlterStatement;
use PhpMyAdmin\SqlParser\Statements\DeleteStatement;
use PhpMyAdmin\SqlParser\Statements\DropStatement;
use PhpMyAdmin\SqlParser\Statements\InsertStatement;
use PhpMyAdmin\SqlParser\Statements\PartialStatement;
use PhpMyAdmin\SqlParser\Statements\ReplaceStatement;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\SqlParser\Statements\SetStatement;
use PhpMyAdmin\SqlParser\Statements\TruncateStatement;
use PhpMyAdmin\SqlParser\Statements\UpdateStatement;

require_once("../../vendor/autoload.php");

class RascalPrinter
{
    private $parser;
    private $lexer;

    public static function rascalizeString($str)
    {
        return addcslashes($str, "<>'\n\t\r\\\"");
    }

    public function __construct($query)
    {
        $this->parser = new Parser($query);
        $this->lexer = new Lexer($query);
    }

    public function printQuery()
    {
        // first, run partial checker to see if we have cases where holes cross clause boundaries
        $tokens = $this->lexer->list;
        $partial = new PartialStatement($tokens);
        if($partial->checkIfPartial()){
            return self::printPartialStatement($partial);
        }
        if(empty($this->parser->statements)){
            return "parseError()";
        }
        $parsed = $this->parser->statements[0];
        if (is_null($parsed)) {
            return "parseError()";
        } else if ($parsed instanceof SelectStatement) {
            return self::printSelectQuery($parsed);
        } else if ($parsed instanceof UpdateStatement) {
            return self::printUpdateQuery($parsed);
        } else if ($parsed instanceof InsertStatement) {
            return self::printInsertQuery($parsed);
        } else if ($parsed instanceof DeleteStatement) {
            return self::printDeleteQuery($parsed);
        } else if($parsed instanceof SetStatement){
            return self::printSetQuery($parsed);
        } else if($parsed instanceof DropStatement){
            return self::printDropQuery($parsed);
        } else if($parsed instanceof AlterStatement){
            return self::printAlterQuery($parsed);
        } else if($parsed instanceof ReplaceStatement){
            return self::printReplaceQuery($parsed);
        } else if($parsed instanceof TruncateStatement){
            return self::printTruncateQuery($parsed);
        } else{
            return "unknownQuery()";
        }
    }

    public static function printPartialStatement($partial){
        $res = "partialStatement(";
        //TODO: these are not mutually exclusive. this needs to be reflected somehow
        if($partial->partialType["unknownStatement"]){
            $res .= "unknownStatementType()";
        }
        else if($partial->partialType["connectiveWithoutWhere"]){
            $res .= "connectiveWithoutWhere()";
        }

        return $res . ")";
    }

    public static function printSelectQuery($parsed)
    {
        $res = "selectQuery(";

        // print select expressions
        $res .= self::printExpressionList($parsed->expr);

        if (!is_null($parsed->from)) {
            // print tables to be selected from
            $res .= ", " . self::printExpressionList($parsed->from);
        }

        if (!is_null($parsed->partition)) {
            //TODO: implement partitions.
            $res .= "partitions are not yet implemented";
        }

        if (!is_null($parsed->where)) {
            $res .= ", " . self::printWhere($parsed->where);
        } else {
            $res .= ", noWhere()";
        }

        if (!is_null($parsed->group)) {
            $res .= ", " . self::printGroupBy($parsed->group);
        } else {
            $res .= ", noGroupBy()";
        }

        if (!is_null($parsed->having)) {
            $res .= ", " . self::printHaving($parsed->having);
        } else {
            $res .= ", noHaving()";
        }

        if (!is_null($parsed->order)) {
            $res .= ", " . self::printOrderBy($parsed->order);
        } else {
            $res .= ", noOrderBy()";
        }

        if (!is_null($parsed->limit)) {
            $res .= ", " . self::printLimit($parsed->limit);
        } else {
            $res .= ", noLimit()";
        }

        if (!is_null($parsed->procedure)) {
            //TODO: implement procedure
            $res .= "procedure is not yet implemented";
        }

        if (!is_null($parsed->into)) {
            //TODO: implement INTO
            $res .= "into is not yet implemented";
        }

        if (!is_null($parsed->join)) {
            $res .= ", " . self::printJoins($parsed->join);
        }
        else{
            $res .= ", []";
        }

        if (sizeof($parsed->union) !== 0) {
            //TODO: implement UNION
            $res .= "union is not yet implemented";
        }

        $res .= ")";

        return $res;
    }

    public static function printUpdateQuery($parsed)
    {
        $res = "updateQuery(";

        // print the tables to be updated
        if (!is_null($parsed->tables)) {
            $res .= self::printExpressionList($parsed->tables);
        }

        if (!is_null($parsed->set)) {
            $res .= ", " . self::printSetOperations($parsed->set);
        }
        else{
            $res .= ", []";
        }

        if (!is_null($parsed->where)) {
            $res .= ", " . self::printWhere($parsed->where);
        } else {
            $res .= ", noWhere()";
        }

        if (!is_null($parsed->order)) {
            $res .= ", " . self::printOrderBy($parsed->order);
        } else {
            $res .= ", noOrderBy()";
        }

        if (!is_null($parsed->limit)) {
            $res .= ", " . self::printLimit($parsed->limit);
        } else {
            $res .= ", noLimit()";
        }

        $res .= ")";

        return $res;
    }

    public static function printInsertQuery($parsed)
    {
        $res = "insertQuery(";

        // print the table data will be inserted into
        $res .= self::printInto($parsed->into);

        if (!is_null($parsed->values)) {
            $res .= ", [";
            $size = sizeof($parsed->values);
            // loop in case the query inserts multiple sets of values
            for($i = 0; $i < $size - 1; $i++){
                $res .= self::printArrayObj($parsed->values[$i]) . ", ";
            }
            $res .= self::printArrayObj($parsed->values[$size - 1]);
            $res .= "]";
        }
        else{
            $res .= ", []";
        }

        if (!is_null($parsed->set)) {
            $res .= ", " . self::printSetOperations($parsed->set);
        }
        else{
            $res .= ", []";
        }

        if (!is_null($parsed->select)) {
            $res .= ", " .self::printSelectQuery($parsed->select);
        }
        else{
            $res .= ", noQuery()";
        }

        if (!is_null($parsed->onDuplicateSet)) {
            $res .= ", " . self::printSetOperations($parsed->onDuplicateSet);
        }
        else{
            $res .= ", []";
        }
        $res .= ")";

        return $res;
    }

    public static function printDeleteQuery($parsed)
    {
        $res = "deleteQuery(";

        // print the tables to be deleted from
        if (!is_null($parsed->from)) {
            $res .= self::printExpressionList($parsed->from);
        }

        if (!empty($parsed->using)) {
            $res .= ", " . self::printArrayObj($parsed->using);
        }
        else{
            $res .= ", []";
        }

        if (!is_null($parsed->columns)) {
            //todo columns
            $res .= "columns in DELETE is not yet implemented";
        }

        if (!is_null($parsed->partition)) {
            //TODO: implement partitions.
            $res .= "partitions are not yet implemented";
        }

        if (!is_null($parsed->where)) {
            $res .= ", " . self::printWhere($parsed->where);
        } else {
            $res .= ", noWhere()";
        }

        if (!is_null($parsed->order)) {
            $res .= ", " . self::printOrderBy($parsed->order);
        } else {
            $res .= ", noOrderBy()";
        }

        if (!is_null($parsed->limit)) {
            $res .= ", " . self::printLimit($parsed->limit);
        } else {
            $res .= ", noLimit()";
        }

        $res .= ")";

        return $res;
    }

    public static function printSetQuery($parsed){
        $res = "";
        if(!empty($parsed->set)){
            $res .= "setQuery(";
            $res .= self::printSetOperations($parsed->set);
        }
        else{
            $options = $parsed->options->options[3];
            $res .= "setOptionsQuery(" . "\"" . $options["name"] . "\"" . ", \"" . $options["value"] . "\"";
        }

        $res .= ")";

        return $res;
    }

    // TODO: needs work for cases where no table is provided. should also print options array
    public static function printDropQuery($parsed){
        $res = "dropQuery(";

        if(!is_null($parsed->fields)){
            $res .= self::printExpressionList($parsed->fields);
        }
        else{
            $res .= "[]";
        }

        if(!is_null($parsed->table)){
            $res .= ", " . self::printExpression($parsed->table);
        }
        else{
            $res .= ", noExp()";
        }

        $res .= ")";

        return $res;
    }

    public static function printAlterQuery($parsed){
        $res = "alterQuery(";

        if(!is_null($parsed->table)){
            $res .= self::printExpression($parsed->table);
        }

        //TODO: altered fields and options

        return $res . ")";
    }

    public static function printReplaceQuery($parsed){
        $res = "replaceQuery(";

        if(!is_null($parsed->into)){
            $res .= self::printInto($parsed->into);
        }
        else{
            $res .= "noInto()";
        }

        if(!is_null($parsed->values)){
            $res .= ", [";
            $size = sizeof($parsed->values);
            // loop in case the query inserts multiple sets of values
            for($i = 0; $i < $size - 1; $i++){
                $res .= self::printArrayObj($parsed->values[$i]) . ", ";
            }
            $res .= self::printArrayObj($parsed->values[$size - 1]);
            $res .= "]";
        }
        else{
            $res .= ", []";
        }

        if(!is_null($parsed->set)){
            $res .= ", " . self::printSetOperations($parsed->set);
        }
        else{
            $res .= ", []";
        }

        if(!is_null($parsed->select)){
            $res .=  ", " . self::printSelectQuery($parsed->select);
        }
        else{
            $res .= ", noQuery()";
        }
        return $res . ")";
    }

    public static function printTruncateQuery($parsed){
        $res = "truncateQuery(";

        $res .= self::printExpression($parsed->table);

        return $res . ")";
    }

    public static function printExpression($exp)
    {

        $res = "";
        if (!is_null($exp->alias)) {
            $res .= "aliased(";
        }

        /*if (!is_null($exp->queryHole)) {
            $res .= "hole(" . $exp->queryHole . ")";
        }*/

        switch ($exp->expr) {
            case("*"):
                $res .= "star()";
                break;
            // this expression is a column name
            case($exp->column):
                $res .= "name(column(\"" . self::rascalizeString($exp->column) . "\"))";
                break;
            case("`" . $exp->column . "`"):
                $res .= "name(column(\"" . self::rascalizeString($exp->column) . "\"))";
                break;
            // this expression is a table name
            case($exp->table):
                $res .= "name(table(\"" . self::rascalizeString($exp->table) . "\"))";
                break;
            case("`" . $exp->table . "`"):
                $res .= "name(table(\"" . self::rascalizeString($exp->table) . "\"))";
                break;
            // this expression is a database name
            case($exp->database):
                $res .= "name(database(\"" . self::rascalizeString($exp->database) . "\"))";
                break;
            case("`" . $exp->database . "`"):
                $res .= "name(database(\"" . self::rascalizeString($exp->database) . "\"))";
                break;
            case($exp->table . "." . $exp->column):
                $res .= "name(tableColumn(\"" . self::rascalizeString($exp->table) . "\", \"" . self::rascalizeString($exp->column) . "\"))";
                break;
            case($exp->database . "." . $exp->table):
                $res .= "name(databaseTable(\"" . self::rascalizeString($exp->database) . "\", \"" . self::rascalizeString($exp->table) . "\"))";
                break;
            case($exp->database . "." . $exp->table . "." . $exp->column):
                $res .= "name(databaseTableColumn(\"" . self::rascalizeString($exp->database) . "\", \"" . self::rascalizeString($exp->table) . "\", \"" . self::rascalizeString($exp->column) . "\"))";
                break;
        }

        if (!is_null($exp->function)) {
            //TODO: handle function params
            $res .= "call(\"" . self::rascalizeString($exp->function) . "\")";
        }

        if (!is_null($exp->alias)) {
            $res .= ", \"" . self::rascalizeString($exp->alias) . "\")";
        }

        return empty($res) ? "unknownExp(\"" . self::rascalizeString($exp->expr) . "\")" : $res;
    }

    /*
     * prints comma separated rascal list of expressions
     */
    public static function printExpressionList($expressions)
    {
        $size = sizeof($expressions);

        $res = "[";
        for ($i = 0; $i < $size - 1; $i++) {
            $res .= self::printExpression($expressions[$i]) . ", ";
        }
        $res .= self::printExpression($expressions[$size - 1]) . "]";

        return $res;
    }


    /*
     * Prints a where clause in rascal format
     */
    public static function printWhere($where)
    {
        return "where(" . self::printConditions($where) . ")";
    }

    /*
     * Prints a having clause in rascal format
     */
    public static function printHaving($having)
    {
        return "where(" . self::printConditions($having) . ")";
    }

    /*
     * Prints GROUP BY clause in rascal format
     */
    public static function printGroupBy($grouping)
    {
        $size = sizeof($grouping);

        $res = "groupBy({";
        for ($i = 0; $i < $size - 1; $i++) {
            $res .= "<" . self::printExpression($grouping[$i]->expr) . ", \"" . self::rascalizeString($grouping[$i]->type) . "\">";
            $res .= ", ";
        }
        $res .= "<" . self::printExpression($grouping[$size - 1]->expr) . ", \"" . self::rascalizeString($grouping[$size - 1]->type) . "\">";

        $res .= "})";

        return $res;
    }

    /*
     * Prints ORDER BY clause in rascal format
     */
    public static function printOrderBy($ordering)
    {
        $size = sizeof($ordering);

        $res = "orderBy({";
        for ($i = 0; $i < $size - 1; $i++) {
            $res .= "<" . self::printExpression($ordering[$i]->expr) . ", \"" . self::rascalizeString($ordering[$i]->type) . "\">";
            $res .= ", ";
        }
        $res .= "<" . self::printExpression($ordering[$size - 1]->expr) . ", \"" . self::rascalizeString($ordering[$size - 1]->type) . "\">";

        $res .= "})";

        return $res;
    }

    public static function printLimit($limit)
    {
        if ($limit->offset === 0) {
            $res = "limit(\"" . self::rascalizeString($limit->rowCount) . "\"";
        }
        else{
            $res = "limitWithOffset(\"" .  self::rascalizeString($limit->rowCount) . "\", \"" . self::rascalizeString($limit->offset) . "\"";
        }
        $res .= ")";

        return $res;
    }

    /*
     * prints conditions from WHERE or HAVING clause in rascal format
     */
    public static function printConditions($tree)
    {
        if (is_null($tree->left) && is_null($tree->right)) {
            return "condition(" . self::printSimpleCondition($tree->value) . ")";
        } else if ($tree->value === "NOT") {
            return "not(\"" . self::rascalizeString(self::printConditions($tree->left)) . "\")";
        } else if ($tree->value === "AND" || $tree->value === "&&") {
            return "and(" . self::printConditions($tree->left) . ", " . self::printConditions($tree->right) . ")";
        } else if ($tree->value === "OR" || $tree->value === "||") {
            return "or(" . self::printConditions($tree->left) . ", " . self::printConditions($tree->right) . ")";
        } else if ($tree->value === "XOR") {
            return "xor(" . self::printConditions($tree->left) . ", " . self::printConditions($tree->right) . ")";
        } else {
            exit("parseError()");
        }
    }

    public static function printSimpleCondition($simple){
        if($simple instanceof ComparisonCondition){
            return self::printComparisonCondition($simple);
        }
        else if($simple instanceof BetweenCondition){
            return self::printBetweenCondition($simple);
        }
        else if($simple instanceof NullCondition){
            return self::printNullCondition($simple);
        }
        else if($simple instanceof InCondition){
            return self::printInCondition($simple);
        }
        else if($simple instanceof  LikeCondition){
            return self::printLikeCondition($simple);
        }
        else if($simple instanceof NotYetImplementedCondition){
            return "unknown(\"" . self::rascalizeString($simple->str) . "\")";
        }
        else{
            return "unknown(\"\")";
        }
    }

    public static function printComparisonCondition($comparison){
        $beginning = "";
        $middle = "";
        $end = "";

        // compound condition such as a < b < c
        if($comparison->rhs instanceof ComparisonCondition){
            $beginning .= "compoundComparison(";
            $end .= self::printComparisonCondition($comparison->rhs) . ")";
        }
        // simple condition such as a = b, a < b, etc.
        else{
            $beginning .= "simpleComparison(";
            $end .= "\"" . self::rascalizeString($comparison->rhs) . "\")";
        }

        $middle .= "\"" . self::rascalizeString($comparison->lhs) . "\", \"" . self::rascalizeString($comparison->op) . "\", ";

        if($comparison->not === true) {
            $beginning = "not(" . $beginning;
            $end .= ")";
        }
        return $beginning . $middle . $end;
    }

    public static function printBetweenCondition($between){
        return "between(" . ($between->not ? 'true' : 'false') . ", \"" . self::rascalizeString($between->expr) . "\", \"" . self::rascalizeString($between->lowerBounds) .
            "\", \"" . self::rascalizeString($between->upperBounds) . "\")";
    }

    public static function printNullCondition($null){
        return "isNull(" . ($null->not ? 'true' : 'false') . ", \"" . self::rascalizeString($null->expr) . "\")";
    }

    public static function printInCondition($in){
        $res = "(" . ($in->not ? 'true' : 'false') . ", \"" . $in->expr . "\", ";
        if($in->values instanceof SelectStatement){
            $res = "inSubquery" . $res;
            $res .= self::printSelectQuery($in->values);
        }
        else{
            $res = "inValues" . $res . "[";
            $rascalizedValues = array();
            foreach ($in->values as $value) {
                $rascalizedValues[] = self::rascalizeString($value);
            }
            $values = implode("\", \"", $rascalizedValues);
            $values = "\"" . $values;
            $values .= "\"";
            $res .= $values . "]";
        }
        return $res . ")";
    }

    public static function printLikeCondition($like){
        return "like(" . ($like->not ? 'true' : 'false') . ", \"" . self::rascalizeString($like->expr) . "\", \"" . self::rascalizeString($like->pattern) . "\")";
    }

    /*
     * prints a JOIN clause in rascal format
     */
    public static function printJoin($join){
        $res = "";
        $type = "\"" . self::rascalizeString($join->type) . "\"";
        $joinExp = self::printExpression($join->expr);

        if(!is_null($join->on)){
            $res .= "joinOn(" . $type . ", " . $joinExp . ", ";
            $res .= self::printConditions($join->on);
        }
        else if(!is_null($join->using)){
            $res .= "joinUsing(" . $type . ", " . $joinExp . ", ";
            $res .= self::printArrayObj($join->using);
        }
        else{
            $res .= "simpleJoin(". $type .",  " . $joinExp;
        }

        return $res . ")";
    }

    public static function printJoins($joins){
        $size = sizeof($joins);

        $res = "[";
        for ($i = 0; $i < $size - 1; $i++) {
            $res .= self::printJoin($joins[$i]) . ", ";
        }
        $res .= self::printJoin($joins[$size - 1]) . "]";

        return $res;
    }

    public static function printArrayObj($array){
        $size = sizeof($array->values);
        $res = "[";
        for($i = 0; $i < $size - 1; $i++){
            $res .= "\"" . self::rascalizeString($array->values[$i]) . "\", ";
        }
        $res .= "\"" . self::rascalizeString($array->values[$size - 1]) . "\"]";

        return $res;
    }

    /*
     * prints information from an INTO clause in rascal format
     */
    public static function printInto($into){
        $res = "into(";
        if(!is_null($into->dest)){
            $res .= self::printExpression($into->dest);
        }

        if(!is_null($into->columns)){
            $size = sizeof($into->columns);
            $res .= ", [";
            for($i = 0; $i < $size - 1; $i++){
                $res .= "\"" . self::rascalizeString($into->columns[$i]) . "\", ";
            }
            $res .= "\"" . self::rascalizeString($into->columns[$size - 1]) . "\"]";
        }
        else{
            $res .= ", []";
        }

        if(!is_null($into->values)){
            //TODO values field for into
            $res .= "select...into is not yet implemented";
        }
        return $res . ")";
    }

    /*
     * prints a SQL set operation in rascal format
     */
    public static function printSetOperations($array){
        $res = "[";
        $size = sizeof($array);
        for($i = 0; $i < $size - 1; $i++){
            $res .= "setOp(\"" . self::rascalizeString($array[$i]->column) . "\", \"" . self::rascalizeString($array[$i]->value) . "\"), ";
        }
        $res .= "setOp(\"" . self::rascalizeString($array[$size - 1]->column) . "\", \"" . self::rascalizeString($array[$size - 1]->value) . "\")";
        $res .= "]";

        return $res;
    }
}