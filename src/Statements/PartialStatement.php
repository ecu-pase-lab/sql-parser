<?php
/**
 * Created by PhpStorm.
 * User: danderson
 * Date: 9/6/17
 * Time: 12:04 PM
 */

namespace PhpMyAdmin\SqlParser\Statements;
use PhpMyAdmin\SqlParser\Components\Condition;
use PhpMyAdmin\SqlParser\Components\JoinKeyword;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

/**
 * Representation of queries where a clause is missing (due to being in a hole)
 *
 * Class PartialStatement
 * @package PhpMyAdmin\SqlParser\Statements
 */
class PartialStatement
{
    /**
     * the tokens that make up this statement
     */
    public $tokens;


    /**
     * the clauses that were actually found
     */
    public $foundClauses = array();

    /**
     * flags for different cases of clauses being contained in holes
     * @var array
     */
    public $partialType = array(
        "unknownStatement" => false, // type of query is hidden in a hole
        "connectiveWithoutWhere" => false // case we saw where an AND was encountered but not a WHERE
    );


    /**
     * PartialStatement constructor.
     * @param TokensList $list
     */
    public function __construct(TokensList $list)
    {
        $this->tokens = $list;
    }

    /**
     * given a list of tokens, returns whether some clauses are hidden in query holes
     *  side effects: builds a list of all found clauses
     *                  classifies the PartialStatement based on what clauses are missing inside holes
     *
     *
     * @return boolean
     */
    public function checkIfPartial(){
        $list = $this->tokens;

        $foundWhere = false;
        $foundJoin = false;

        if($list[0]->type === Token::TYPE_HOLE){
            $this->partialType["unknownStatement"] = true;
            return true;
        }

        for (; $list->idx < $list->count; ++$list->idx) {
            $token = $list->tokens[$list->idx];

            if($token->type === Token::TYPE_KEYWORD){
                if($token->value === "WHERE"){
                    $foundWhere = true;
                }
                if(array_key_exists($token->value, JoinKeyword::$JOINS)){
                    $foundJoin = true;
                }
                if(in_array($token->value, Condition::$DELIMITERS, true)){
                    if($foundJoin){
                        return false;
                    }
                    if(!$foundWhere){
                        $this->partialType["connectiveWithoutWhere"] = true;
                        return true;
                    }
                }
            }
        }
        return false;
    }
}