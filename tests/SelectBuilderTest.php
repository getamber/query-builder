<?php

use Amber\Components\QueryBuilder\SelectBuilder;
use PHPUnit\Framework\TestCase;

class SelectBuilderTest extends TestCase
{
    public function testSimplSelectWithoutFrom()
    {
        $select = new SelectBuilder();
        $select->columns('somefunction()');
        $this->assertEquals('SELECT somefunction()', (string) $select);
    }

    public function testSimpleSelect()
    {
        $select = new SelectBuilder();
        $select->columns('column1', 'column2', 'column3')->from('table1', 't1');
        $this->assertEquals('SELECT column1,column2,column3 FROM table1 t1', (string) $select);
    }

    public function testSimpleSelectWithSingleWhere()
    {
        $select = new SelectBuilder();
        $select->columns('column1', 'column2', 'column3')->from('table1', 't1')
            ->where('t1.id = ?');
        $this->assertEquals('SELECT column1,column2,column3 FROM table1 t1 WHERE t1.id = ?', (string) $select);
    }

    public function testSimpleSelectWithMultipleWhere()
    {
        $select = new SelectBuilder();
        $select->columns('column1', 'column2', 'column3')->from('table1', 't1')
            ->where('t1.id = ?')
            ->orWhere('(t1.email = ? AND t1.username = ?)')
            ->andWhere('t1.created_at < ?');
        $this->assertEquals('SELECT column1,column2,column3 FROM table1 t1 WHERE t1.id = ? OR (t1.email = ? AND t1.username = ?) AND t1.created_at < ?', (string) $select);
    }

    public function testSelectWithInnerJoin()
    {
        $select = new SelectBuilder();
        $select->columns('column1', 'column2', 'column3')->from('table1', 't1')
            ->join('table2', 't2', 't1.id = t2.id');
        $this->assertEquals('SELECT column1,column2,column3 FROM table1 t1 JOIN table2 t2 ON t1.id = t2.id', (string) $select);
    }

    public function testSelectWithLeftJoin()
    {
        $select = new SelectBuilder();
        $select->columns('column1', 'column2', 'column3')->from('table1', 't1')
            ->leftJoin('table2', 't2', 't1.id = t2.id');
        $this->assertEquals('SELECT column1,column2,column3 FROM table1 t1 LEFT JOIN table2 t2 ON t1.id = t2.id', (string) $select);
    }

    public function testSelectWithMultipleJoins()
    {
        $select = new SelectBuilder();
        $select->columns('column1', 'column2', 'column3')->from('table1', 't1')
            ->join('table2', 't2', 't1.id = t2.id')
            ->leftJoin('table3', 't3', 't1.id = t3.id');
        $this->assertEquals('SELECT column1,column2,column3 FROM table1 t1 JOIN table2 t2 ON t1.id = t2.id LEFT JOIN table3 t3 ON t1.id = t3.id', (string) $select);
    }

    public function testSelectWithSingleOrderBy()
    {
        $select = new SelectBuilder();
        $select->columns('column1', 'column2', 'column3')->from('table1', 't1')->order('t1.sort', 'DESC');
        $this->assertEquals('SELECT column1,column2,column3 FROM table1 t1 ORDER BY t1.sort DESC', (string) $select);
    }

    public function testSelectWithMultipleOrderBy()
    {
        $select = new SelectBuilder();
        $select->columns('column1', 'column2', 'column3')->from('table1', 't1')->order('t1.sort', 'DESC')->order('t1.surname', 'ASC');
        $this->assertEquals('SELECT column1,column2,column3 FROM table1 t1 ORDER BY t1.sort DESC,t1.surname ASC', (string) $select);
    }
}