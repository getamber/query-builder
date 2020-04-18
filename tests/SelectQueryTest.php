<?php

use Amber\Components\QueryBuilder\QueryBuilder;
use PHPUnit\Framework\TestCase;

class SelectQueryTest extends TestCase
{
    public function testSelectWithJustConditions()
    {
        $query = new QueryBuilder();
        $query->where('username = ?')->orWhere('email = ?');
        $this->assertEquals('username = ? OR email = ?', (string) $query);
    }

    public function testSimplSelectWithoutFrom()
    {
        $query = new QueryBuilder();
        $query->select('somefunction()');
        $this->assertEquals('SELECT somefunction()', (string) $query);
    }

    public function testSimpleSelect()
    {
        $query = new QueryBuilder();
        $query->select('column1', 'column2', 'column3')->from('table1', 't1');
        $this->assertEquals('SELECT column1,column2,column3 FROM table1 t1', (string) $query);
    }

    public function testReplaceSelect()
    {
        $query = new QueryBuilder();
        $query->select('column1', 'column2', 'column3')->from('table1', 't1');
        $query->select('column_a', 'column_b', 'column_c');
        $this->assertEquals('SELECT column_a,column_b,column_c FROM table1 t1', (string) $query);
    }

    public function testSimpleSelectWithSingleWhere()
    {
        $query = new QueryBuilder();
        $query->select('column1', 'column2', 'column3')->from('table1', 't1')
            ->where('t1.id = ?');
        $this->assertEquals('SELECT column1,column2,column3 FROM table1 t1 WHERE t1.id = ?', (string) $query);
    }

    public function testSimpleSelectWithMultipleWhere()
    {
        $query = new QueryBuilder();
        $query->select('column1', 'column2', 'column3')->from('table1', 't1')
            ->where('t1.id = ?')
            ->orWhere('(t1.email = ? AND t1.username = ?)')
            ->andWhere('t1.created_at < ?');
        $this->assertEquals('SELECT column1,column2,column3 FROM table1 t1 WHERE t1.id = ? OR (t1.email = ? AND t1.username = ?) AND t1.created_at < ?', (string) $query);
    }

    public function testSelectWithInnerJoin()
    {
        $query = new QueryBuilder();
        $query->select('column1', 'column2', 'column3')->from('table1', 't1')
            ->join('table2', 't2', 't1.id = t2.id');
        $this->assertEquals('SELECT column1,column2,column3 FROM table1 t1 JOIN table2 t2 ON t1.id = t2.id', (string) $query);
    }

    public function testSelectWithLeftJoin()
    {
        $query = new QueryBuilder();
        $query->select('column1', 'column2', 'column3')->from('table1', 't1')
            ->leftJoin('table2', 't2', 't1.id = t2.id');
        $this->assertEquals('SELECT column1,column2,column3 FROM table1 t1 LEFT JOIN table2 t2 ON t1.id = t2.id', (string) $query);
    }

    public function testSelectWithMultipleJoins()
    {
        $query = new QueryBuilder();
        $query->select('column1', 'column2', 'column3')->from('table1', 't1')
            ->join('table2', 't2', 't1.id = t2.id')
            ->leftJoin('table3', 't3', 't1.id = t3.id');
        $this->assertEquals('SELECT column1,column2,column3 FROM table1 t1 JOIN table2 t2 ON t1.id = t2.id LEFT JOIN table3 t3 ON t1.id = t3.id', (string) $query);
    }

    public function testSelectWithSingleOrderBy()
    {
        $query = new QueryBuilder();
        $query->select('column1', 'column2', 'column3')
            ->from('table1', 't1')
            ->orderBy('t1.sort', 'DESC');
        $this->assertEquals('SELECT column1,column2,column3 FROM table1 t1 ORDER BY t1.sort DESC', (string) $query);
    }

    public function testSelectWithMultipleOrderBy()
    {
        $query = new QueryBuilder();
        $query->select('column1', 'column2', 'column3')
            ->from('table1', 't1')
            ->orderBy('t1.sort', 'DESC')
            ->addOrderBy('t1.surname', 'ASC');
        $this->assertEquals('SELECT column1,column2,column3 FROM table1 t1 ORDER BY t1.sort DESC,t1.surname ASC', (string) $query);
    }

    public function testSelectWithSubquery()
    {
        $query = new QueryBuilder();
        $query->select('*')->from(function ($query) {
            $query->select('*')->from('users');
        });
        $this->assertEquals('SELECT * FROM (SELECT * FROM users)', (string) $query);
    }
}