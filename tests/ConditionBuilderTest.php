<?php

use Amber\Components\QueryBuilder\ConditionBuilder;
use PHPUnit\Framework\TestCase;

class ConditionBuilderTest extends TestCase
{
    public function testEmptyCondition()
    {
        $condition = new ConditionBuilder();
        $this->assertEquals('', (string) $condition);
    }

    public function testSingleCondition()
    {
        $condition = new ConditionBuilder();
        $condition->addCondition('u.id = ?');
        $this->assertEquals('u.id = ?', (string) $condition);
    }

    public function testMultipleConditions()
    {
        $condition = new ConditionBuilder();
        $condition->addCondition('u.id = ?');
        $condition->addCondition('u.username = ?', ConditionBuilder::OR);
        $condition->addCondition('u.email = ?', ConditionBuilder::AND);
        $this->assertEquals('u.id = ? OR u.username = ? AND u.email = ?', (string) $condition);
    }
}