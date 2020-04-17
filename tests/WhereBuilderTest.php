<?php

use Amber\Components\QueryBuilder\ConditionBuilder;
use Amber\Components\QueryBuilder\WhereBuilder;
use PHPUnit\Framework\TestCase;

class WhereBuilderTest extends TestCase
{
    public function testSingleCondition()
    {
        $where = new WhereBuilder();
        $where->addCondition('u.id = ?');
        $this->assertEquals('WHERE u.id = ?', (string) $where);
    }

    public function testMultipleConditions()
    {
        $where = new WhereBuilder();
        $where->addCondition('u.id = ?');
        $where->addCondition('u.username = ?', ConditionBuilder::OR);
        $where->addCondition('u.email = ?', ConditionBuilder::AND);
        $this->assertEquals('WHERE u.id = ? OR u.username = ? AND u.email = ?', (string) $where);
    }
}