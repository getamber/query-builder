<?php

use Amber\Components\QueryBuilder\ConditionBuilder;
use PHPUnit\Framework\TestCase;

class ConditionBuilderTest extends TestCase
{
    public function testSingleCondition()
    {
        $condition = new ConditionBuilder();
        $condition->where('u.id = ?');
        $this->assertEquals('u.id = ?', (string) $condition);
    }

    public function testMultipleConditions()
    {
        $condition = new ConditionBuilder();
        $condition->where('u.id = ?')
            ->orWhere('u.username = ?')
            ->andWhere('u.email = ?');
        $this->assertEquals('u.id = ? OR u.username = ? AND u.email = ?', (string) $condition);
    }
}