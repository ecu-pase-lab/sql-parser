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
     * Identifies which type of SimpleCondition the tokens in $list represent and calls the correct parse method
     *
     * @return SimpleCondition
     */
    public static final function identify(TokensList $list){
        $firstExpr = '';
        $foundFirstExpr = false;
        $firstOp = '';
        $foundNot = false;

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
                    if(in_array($firstOp, ComparisonCondition::$COMPARISON_OPS, true)){
                        $list->idx++;
                        return (new ComparisonCondition($list, $firstExpr, $firstOp))->parse();
                    }
                    else if($token->value === "BETWEEN"){
                        $list->idx++;
                        return (new BetweenCondition($list, $firstExpr, $foundNot))->parse();
                    }
                    else if($token->value === "IS"){
                        $list->idx++;
                        // TODO: other condition types use the IS keyword
                        return (new NullCondition($list, $firstExpr, $foundNot))->parse();
                    }
                    else if($token->value === "NOT"){
                        // this simple condition is negated, loop again to get the next operator or keyword
                        $foundNot = true;
                        continue;
                    }
                }
            }
            else{
                $firstExpr = $token->value;
                $foundFirstExpr = true;
            }
        }
    }

    /**
     * @return SimpleCondition
     */
    public abstract function parse();
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

    public function __construct($tokens, $firstExpr, $not)
    {
        parent::__construct($tokens);
        $this->expr = $firstExpr;
        $this->not = $not;
        $this->lowerBounds = "";
        $this->upperBounds = "";
    }

    /**
     * Modifies the lowerBounds and upperBounds fields of this and returns this
     *
     * @return BetweenCondition
     */
    public function parse(){
        // tokens between the BETWEEN and the AND are the lower bounds
        while($this->list->tokens[$this->list->idx]->value !== "AND"){
            $token = $this->list->tokens[$this->list->idx];
            if($token->type === Token::TYPE_WHITESPACE){
                continue;
            }
            else{
                $this->lowerBounds += $token->value;
            }
            $this->list->idx++;
        }

        // skip the AND token
        $this->list->idx++;

        // tokens after the AND are the upper bounds
        for(; $this->list->idx < $this->list->count; ++$this->list->idx){
            $token = $this->list->tokens[$this->list->idx];

            if($token->type === Token::TYPE_WHITESPACE){
                continue;
            }
            else{
                $this->upperBounds += $token->value;
            }
        }

        return $this;
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

    public function __construct($tokens, $firstExpr, $not)
    {
        parent::__construct($tokens);
        $this->expr = $firstExpr;
        $this->not = $not;
    }

    /**
     * @return NullCondition
     */
    public function parse(){
        // the information provided by SimpleCondition::identify() is enough to parse null conditions
        return $this;
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

    public function __construct($tokens, $lhs, $op)
    {
        parent::__construct($tokens);
        $this->lhs = $lhs;
        $this->op = $op;
        $this->rhs = "";
    }

    /**
     * Modifies the RHS field of this and returns this
     *
     * @return ComparisonCondition
     */
    public function parse(){
        for(; $this->list->idx < $this->list->count; ++$this->list->idx){
            $token = $this->list->tokens[$this->list->idx];

            if($token->type === Token::TYPE_WHITESPACE){
                continue;
            }
            else if(in_array($token->value, ComparisonCondition::$COMPARISON_OPS, true)){
                // found another comparison operator, the RHS we found is the LHS of another comparison
                $this->list->idx++;
                $this->rhs = (new ComparisonCondition($this->list, $this->rhs, $token->value))->parse();
                return $this;
            }
            else{
                $this->rhs .= $token->value;
            }
        }
        return $this;
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
        foreach ($tokensList->tokens as $token) {
            $this->str .= $token->value;
        }
    }

    public function parse()
    {
        return $this;
    }
}