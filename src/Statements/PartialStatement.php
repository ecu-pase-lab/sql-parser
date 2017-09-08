<?php
/**
 * Created by PhpStorm.
 * User: danderson
 * Date: 9/6/17
 * Time: 12:04 PM
 */

namespace PhpMyAdmin\SqlParser\Statements;
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
     *
     */
    public $tokens;

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
        if($this->checkIfPartial($list) === true){
            return $this;
        }
        else{
            return null;
        }
    }

    /**
     * given a list of tokens, returns whether some clauses are hidden in query holes
     *  side effects: builds a list of all found clauses
     *                  classifies the PartialStatement based on what clauses are missing inside holes
     *
     * @param TokensList $list
     *
     * @return boolean
     */
    public function checkIfPartial(TokensList $list){
        //TODO: implement
        return false;
    }
}