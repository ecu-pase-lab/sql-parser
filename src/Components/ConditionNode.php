<?php
/**
 * Created by PhpStorm.
 * User: andersonda
 * Date: 4/13/17
 * Time: 9:34 AM
 */

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\TokensList;

/**
 * Class ConditionNode
 * @package PhpMyAdmin\SqlParser\Components
 *
 * Node of a tree structure representation of a WHERE, HAVING, or ON clause
 */
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

class SimpleCondition
{
    /**
     * @var TokensList
     */
    public $list;

    public function __construct($tokens){
        $this->tokens = $tokens;
    }

    /**
     * @return SimpleCondition
     */
    public function parse(){
        //TODO: implement parsing
    }
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
    public $not = false;

    /**
     * expression before BETWEEN keyword
     *
     * @var string
     */
    public $expr = "";

    /**
     * lower bounds of the BETWEEN statement
     *
     * @var string
     */
    public $lowerBounds = "";

    /**
     * upper bounds of the BETWEEN statement
     *
     * @var string
     */
    public $upperBounds = "";
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
    public $not = false;

    /**
     * The expression checked for null
     *
     * @var string
     */
    public $expr = "";
}

/**
 * represents simple conditions such as expr0 = expr1, expr0 < expr1 < expr2, etc.
 *
 * Class ComparisonCondition
 * @package PhpMyAdmin\SqlParser\Components
 */
class ComparisonCondition extends SimpleCondition
{
    //TODO implement comparison condtions
}
