<?php
/**
 * Created by PhpStorm.
 * User: andersonda
 * Date: 4/13/17
 * Time: 9:34 AM
 */

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

/**
 * Class ConditionNode
 * @package PhpMyAdmin\SqlParser\Components
 *
 * Node of a tree structure representation of a WHERE, HAVING, or ON clause
 */
class ConditionNode
{
    /**
     * @var SimpleCondition
     */
    public $value;

    /**
     * @var ConditionNode | null
     */
    public $left;

    /**
     * @var ConditionNode | null
     */
    public $right;

    public function __construct($item) {
        $this->value = $item;
        $this->left = null;
        $this->right = null;
    }
}

abstract class SimpleCondition
{
    /**
     * @var TokensList
     */
    public $list;

    public function __construct($tokensList){
        $this->list = $tokensList;
    }

    /**
     * Identifies which child class parse method to call
     *
     * @return SimpleCondition
     */
    public static function identify(TokensList $list){
        $firstExpr = '';
        $foundFirstExpr = false;

        $firstOp = '';
        // loop through leading whitespace to grab the first expression and first operator/keyword
        for(; $list->idx < $list->count; ++$list->idx){
            $token = $list->tokens[$list->idx];

            // Skipping whitespaces.
            if ($token->type === Token::TYPE_WHITESPACE) {
                continue;
            }

            if(($token->type === Token::TYPE_KEYWORD || $token->type === Token::TYPE_OPERATOR)){
                // right now we expect the first non comment/whitespace token to be an expression, return a NotYetImplementedCondition
                // this will be revisited later for other types of conditions
                if($foundFirstExpr === false) {
                    return new NotYetImplementedCondition($list->tokens);
                }
                else{
                    $firstOp = $token->value;
                    if(in_array($token->value, ComparisonCondition::$COMPARISON_OPS, true)){
                        
                    }
                }
            }
            else{
                $firstExpr = $token->value;
                $foundFirstExpr = true;
            }
        }
    }

    public abstract function parse($tokens);
}

/**
 * represents condition of the form expr0 BETWEEN expr1 AND expr2
 *
 * Class BetweenCondition
 * @package PhpMyAdmin\SqlParser\Components
 */
class BetweenCondition extends SimpleCondition
{
    /**
     * is this BetweenCondition negated?
     *
     * @var bool
     */
    public $not;

    /**
     * expression before BETWEEN keyword
     *
     * @var string
     */
    public $expr;

    /**
     * lower bounds of the BETWEEN statement
     *
     * @var string
     */
    public $lowerBounds;

    /**
     * upper bounds of the BETWEEN statement
     *
     * @var string
     */
    public $upperBounds;

    public function __construct($tokens, $expr, $lowerBounds, $upperBounds, $not = false)
    {
        parent::__construct($tokens);
        $this->expr = $expr;
        $this->lowerBounds = $lowerBounds;
        $this->upperBounds = $upperBounds;
        $this->not = $not;
    }

    public function parse($tokens, $firstExpr = "", $firstOp = ""){
        //TODO: implement
        return null;
    }
}

/**
 * represents conditions of the form expr IS [NOT] NULL
 *
 * Class NullCondition
 * @package PhpMyAdmin\SqlParser\Components
 */
class NullCondition extends SimpleCondition
{
    /**
     * is this NullCondition negated?
     *
     * @var bool
     */
    public $not;

    /**
     * The expression checked for null
     *
     * @var string
     */
    public $expr;

    public function __construct($tokens, $expr, $not = false)
    {
        parent::__construct($tokens);
        $this->expr = $expr;
        $this->not = $not;
    }

    public function parse($tokens, $firstExpr = "", $firstOp = ""){
        //TODO: implement
        return null;
    }
}

/**
 * represents simple conditions such as expr0 = expr1, expr0 < expr1 < expr2, etc.
 *
 * Class ComparisonCondition
 * @package PhpMyAdmin\SqlParser\Components
 */
class ComparisonCondition extends SimpleCondition
{
    public static $COMPARISON_OPS = array('=', '!=', '^=', '<>', '>', '<', '>=', '<=');

    /**
     * @var string
     */
    public $lhs;

    /**
     * @var string in COMPARISON_OPS
     */
    public $op;

    /**
     * ComparisonCondition for cases such as a < b < c, string for cases like a != b
     *
     * @var string | ComparisonCondition
     */
    public $rhs;

    public function __construct($tokens, $lhs, $op, $rhs)
    {
        parent::__construct($tokens);
        $this->lhs = $lhs;
        $this->op = $op;
        $this->rhs = $rhs;
    }

    public function parse($tokens, $firstExpr = "", $firstOp = ""){
        //TODO: implement
        return null;
    }
}

class NotYetImplementedCondition extends SimpleCondition
{
    /**
     * @var string
     */
    public $str = "";

    public function __construct($tokensList)
    {
        parent::__construct($tokensList);
        foreach($tokensList->tokens as $token){
            $this->str .= $token->token;
        }
    }

    public function parse($tokens){
        //TODO: implement
        return $this;
    }
}
