<?php

/**
 * `WHERE` keyword parser.
 */

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

/**
 * `WHERE` keyword parser.
 *
 * @category   Keywords
 *
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class Condition extends Component
{
    /**
     * Logical operators that can be used to delimit expressions.
     *
     * @var array
     */
    public static $DELIMITERS = array('&&', '||', 'AND', 'OR', 'XOR');

    /**
     * List of allowed reserved keywords in conditions.
     *
     * @var array
     */
    public static $ALLOWED_KEYWORDS = array(
        'ALL' => 1,
        'AND' => 1,
        'BETWEEN' => 1,
        'EXISTS' => 1,
        'IF' => 1,
        'IN' => 1,
        'INTERVAL' => 1,
        'IS' => 1,
        'LIKE' => 1,
        'MATCH' => 1,
        'NOT IN' => 1,
        'NOT NULL' => 1,
        'NOT' => 1,
        'NULL' => 1,
        'OR' => 1,
        'REGEXP' => 1,
        'RLIKE' => 1,
        'XOR' => 1,
    );

    /**
     * list of operator precedence
     *
     * @var array
     */
    public static $PRECEDENCE = array(
          "NOT" => 3,
            "AND" => 2,
            "&&" => 2,
            "OR" => 1,
            "||" => 1,
            "XOR" => 1
    );

    /**
     * The Condition Tree
     *
     * @var ConditionNode
     */
    public $tree;

    /**
     * Constructor.
     *
     * @param string $expr the tree
     */
    public function __construct($expr = null)
    {
        $this->tree = null;
    }

    /**
     * @param Parser     $parser  the parser that serves as context
     * @param TokensList $list    the list of tokens that are being parsed
     * @param array      $options parameters for parsing
     *
     * @return Condition
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {

        /**
         * keeps track of tokens that make up a simple condition
         * TODO: change this from a string to a token array for further processing
         */
        $condition = "";

        /**
         * Counts brackets.
         *
         * @var int
         */
        $brackets = 0;

        /**
         * Whether there was a `BETWEEN` keyword before or not.
         *
         * It is required to keep track of them because their structure contains
         * the keyword `AND`, which is also an operator that delimits
         * expressions.
         *
         * @var bool
         */
        $betweenBefore = false;

        /**
         * The operator stack
         *
         * @var Token[]
         */
        $opStack = array();

        /**
         * The output stack
         *
         * @var Condition[]
         */
        $output = array();

        for (; $list->idx < $list->count; ++$list->idx) {
            //var_dump($output);
            /**
             * Token parsed at this moment.
             *
             * @var Token
             */
            $token = $list->tokens[$list->idx];

            // End of statement.
            if ($token->type === Token::TYPE_DELIMITER) {
                break;
            }

            // Skipping whitespaces and comments.
            if ($token->type === Token::TYPE_COMMENT) {
                continue;
            }

            // Replacing all whitespaces (new lines, tabs, etc.) with a single
            // space character.
            if ($token->type === Token::TYPE_WHITESPACE) {
                $condition .= ' ';
                continue;
            }

            // Conditions are delimited by logical operators.
            if (in_array($token->value, static::$DELIMITERS, true) || $token->value === "NOT") {
                if (($betweenBefore) && ($token->value === 'AND')) {
                    // The syntax of keyword `BETWEEN` is hard-coded.
                    $condition .= $token->value;
                    $betweenBefore = false;
                }
                else {
                    // The condition ended, add it to the output stack
                    $condition = trim($condition);
                    if(!empty($condition)) {
                        array_push($output, new ConditionNode($condition));
                        $condition = "";
                    }
                    // Adding the operator to the operator stack
                    while(sizeof($opStack) !== 0 && $opStack[sizeof($opStack) - 1]->value !== "(" &&
                        static::$PRECEDENCE[$token->value] <= static::$PRECEDENCE[$opStack[sizeof($opStack) - 1]->value]) {
                        $op2 = $opStack[sizeof($opStack) - 1];
                        $node = new ConditionNode($op2->value);
                        $node->left = array_pop($output);
                        if($op2->value !== "NOT") {
                            $node->right = array_pop($output);
                        }
                        array_push($output, $node);
                        array_pop($opStack);
                    }
                    array_push($opStack, $token);
                }
                continue;
            }

            if (($token->type === Token::TYPE_KEYWORD)
                && ($token->flags & Token::FLAG_KEYWORD_RESERVED)
                && !($token->flags & Token::FLAG_KEYWORD_FUNCTION)
            ) {
                if ($token->value === 'BETWEEN') {
                    $betweenBefore = true;
                }
                if (($brackets === 0) && (empty(static::$ALLOWED_KEYWORDS[$token->value]))) {
                    break;
                }
                $condition .= $token->value;
                continue;
            }

            if($token->flags & Token::FLAG_KEYWORD_FUNCTION){
                // this token is a SQL function keyword, add it do the condition and keep consuming tokens until a right parenthesis is found.
                $condition .= $token->value;
                do{
                    if(in_array($token->value, static::$DELIMITERS, true) || $token->value === "NOT"){
                        break;
                    }
                    $token = $list[++$list->idx];
                    $condition .= $token->value;
                }while($list->idx < $list->count && $token->value !== ")");
            }

            if ($token->type === Token::TYPE_OPERATOR) {
                if ($token->value === "(") {
                    ++$brackets;
                    array_push($opStack, $token);
                    continue;
                }
                else if ($token->value === ")") {
                    if ($brackets == 0) {
                        break;
                    }
                    --$brackets;

                    // push the final condition onto the output stack
                    array_push($output, new ConditionNode($condition));
                    $condition = "";

                    while(sizeof($opStack) !== 0){
                        $op = array_pop($opStack);
                        if($op->value === "("){
                            break;
                        }
                        else{
                            $node = new ConditionNode($op->value);
                            $node->left = array_pop($output);
                            if($op->value !== "NOT"){
                                $node->right = array_pop($output);
                            }
                            array_push($output, $node);
                        }
                    }
                    continue;
                }
                else{
                    $condition .= $token->value;
                    continue;
                }
            }

            $condition .= $token->value;
        }

        // add final condition to the output stack
        if(!empty($condition)){
            array_push($output, new ConditionNode($condition));
        }

        while(sizeof($opStack) !== 0) {
            $op = array_pop($opStack);
            $node = new ConditionNode($op->value);
            $node->left = array_pop($output);
            if($op->value !== "NOT") {
                $node->right = array_pop($output);
            }
            array_push($output, $node);
        }

        --$list->idx;
        return $output[0];
    }

    /**
     * @param Condition[] $component the component to be built
     * @param array       $options   parameters for building
     *
     * @return string
     */
    public static function build($component, array $options = array())
    {
        if (is_array($component)) {
            return implode(' ', $component);
        }

        return $component->expr;
    }
}