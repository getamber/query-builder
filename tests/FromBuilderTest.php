<?php

use Amber\Components\QueryBuilder\FromBuilder;
use PHPUnit\Framework\TestCase;

class FromBuilderTest extends TestCase
{
    public function testFrom()
    {
        $from = new FromBuilder();
        $from->setTable('table1', 't1');
        $this->assertEquals('FROM table1 t1', (string) $from);
    }
}