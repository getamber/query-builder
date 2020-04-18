<?php

namespace Amber\Components\QueryBuilder;

/**
 * Represents an SQL query.
 * 
 * @author  Ken Lynch
 * @license MIT
 */
class Query
{
    const TYPE_SELECT = 'SELECT';
    const TYPE_INSERT = 'INSERT';
    const TYPE_UPDATE = 'UPDATE';
    const TYPE_DELETE = 'DELETE';

    public $type;

    public $select   = [];
    public $distinct = false;
    public $from     = null;
    public $join     = [];
    public $where    = [];
    public $groupBy  = [];
    public $having   = [];
    public $orderBy  = [];
    public $limit    = null;
    public $offset   = null;
    public $values   = [];

    /**
     * Adds a select clause to the query.
     */
    public function addSelect(array $columns, $append = true)
    {
        if (!$append) {
            $this->select = [];
        }

        array_push($this->select, ...$columns);
    }

    /**
     * Sets the from clause of the query.
     */
    public function setFrom($table)
    {
        $this->from = $table;
    }

    public function addJoin($table, $condition, $type)
    {
        $this->join[] = [$table, $condition, $type];
    }

    public function addWhere($condition, $operator, $append = true)
    {
        if (!$append) {
            $this->where = [];
        }

        $this->where[] = [$condition, $operator];
    }

    public function addGroupBy($column, $append = true)
    {
        if (!$append) {
            $this->groupBy = [];
        }

        $this->groupBy[] = $column;
    }

    public function addHaving($condition, $operator, $append = true)
    {
        if (!$append) {
            $this->having = [];
        }

        $this->having[] = [$condition, $operator];
    }

    public function addOrderBy($column, $sort, $append = true)
    {
        if (!$append) {
            $this->orderBy = [];
        }

        $this->orderBy[] = [$column, $sort];
    }

    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    public function setOffset($offset)
    {
        $this->offset = $offset;
    }

    public function addValues(array $values, $append = true)
    {
        if (!$append) {
            $this->values = [];
        }

        array_push($this->values, $values);
    }
}