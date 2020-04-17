<?php

use Amber\Components\QueryBuilder\LimitBuilder;
use PHPUnit\Framework\TestCase;

class LimitBuilderTest extends TestCase
{
    public function testEmptyLimit()
    {
        $limit = new LimitBuilder();
        $this->assertEquals('', (string) $limit);
    }
}