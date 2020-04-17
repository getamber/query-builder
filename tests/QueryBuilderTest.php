<?php

use Amber\Components\QueryBuilder\DeleteBuilder;
use Amber\Components\QueryBuilder\InsertBuilder;
use Amber\Components\QueryBuilder\QueryBuilder;
use Amber\Components\QueryBuilder\SelectBuilder;
use Amber\Components\QueryBuilder\UpdateBuilder;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase
{
    public function testSelect()
    {
        $object = QueryBuilder::select();
        $this->assertInstanceOf(SelectBuilder::class, $object);
    }

    public function testInsert()
    {
        $object = QueryBuilder::insert();
        $this->assertInstanceOf(InsertBuilder::class, $object);
    }

    public function testUpdate()
    {
        $object = QueryBuilder::update();
        $this->assertInstanceOf(UpdateBuilder::class, $object);
    }

    public function testDelete()
    {
        $object = QueryBuilder::delete();
        $this->assertInstanceOf(DeleteBuilder::class, $object);
    }
}