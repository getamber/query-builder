<?php

use Amber\Components\QueryBuilder\JoinBuilder;
use PHPUnit\Framework\TestCase;

class JoinBuilderTest extends TestCase
{
    public function testInnerJoin()
    {
        $join = new JoinBuilder();
        $join->addJoin('table1', 't1', 't1.id = t2.id', JoinBuilder::INNER_JOIN);
        $this->assertEquals('JOIN table1 t1 ON t1.id = t2.id', (string) $join);
    }

    public function testLeftJoin()
    {
        $join = new JoinBuilder();
        $join->addJoin('table1', 't1', 't1.id = t2.id', JoinBuilder::LEFT_JOIN);
        $this->assertEquals('LEFT JOIN table1 t1 ON t1.id = t2.id', (string) $join);
    }

    public function testRightJoin()
    {
        $join = new JoinBuilder();
        $join->addJoin('table1', 't1', 't1.id = t2.id', JoinBuilder::RIGHT_JOIN);
        $this->assertEquals('RIGHT JOIN table1 t1 ON t1.id = t2.id', (string) $join);
    }

    public function testCrossJoin()
    {
        $join = new JoinBuilder();
        $join->addJoin('table1', 't1', null, JoinBuilder::CROSS_JOIN);
        $this->assertEquals('CROSS JOIN table1 t1', (string) $join);
    }
}