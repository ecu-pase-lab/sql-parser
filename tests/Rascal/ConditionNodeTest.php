<?php

namespace PhpMyAdmin\SqlParser\Tests\Rascal;

use PhpMyAdmin\SqlParser\Tests\TestCase;

use PhpMyAdmin\SqlParser\Components\ComparisonCondition;
use PhpMyAdmin\SqlParser\Components\SimpleCondition;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

/**
 * Created by PhpStorm.
 * User: danderson
 * Date: 5/17/17
 * Time: 10:02 AM
 */
class ConditionNodeTest extends TestCase
{
    function testComparisonCondition1(){
        $tokens = array(
            new Token(' ', 3, 0),
            new Token('a', 0, 0),
            new Token(' ', 3, 0),
            new Token('=', 2, 2),
            new Token(' ', 3, 0),
            new Token('?0', 11, 0),
            new Token(null, 9, 0)
        );

        $list = new TokensList($tokens, sizeof($tokens));

        $parsed = SimpleCondition::identify($list);

        $this->assertTrue($parsed instanceof ComparisonCondition);
        $this->assertEquals($parsed->lhs, 'a');
        $this->assertEquals($parsed->op, '=');
        $this->asserFalse($parsed->rhs instanceof ComparisonCondition);
        $this->assertEquals($parsed->rhs, '?0');
    }

    function testComparisonCondition2(){
        $tokens = array(
            new Token(' ', 3, 0),
            new Token('a', 0, 0),
            new Token(' ', 3, 0),
            new Token('<', 2, 2),
            new Token(' ', 3, 0),
            new Token('?0', 11, 0),
            new Token(' ', 3, 0),
            new Token('<', 2, 2),
            new Token(' ', 3, 0),
            new Token('?1', 11, 0),
            new Token(null, 9, 0)
        );

        $list = new TokensList($tokens, sizeof($tokens));

        $parsed = SimpleCondition::identify($list);

        $this->assertTrue($parsed instanceof ComparisonCondition);
        $this->assertEquals($parsed->lhs, 'a');
        $this->assertEquals($parsed->op, '<');
        $this->assertTrue($parsed->rhs instanceof ComparisonCondition);
        $this->assertEquals($parsed->rhs->lhs, '?0');
        $this->assertEquals($parsed->rhs->op, '<');
        $this->assertFalse($parsed->rhs->rhs instanceof ComparisonCondition);
        $this->assertEquals($parsed->rhs->rhs, '?1');
    }
}