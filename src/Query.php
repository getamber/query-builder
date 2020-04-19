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
    const SELECT = 'SELECT';
    const INSERT = 'INSERT';
    const UPDATE = 'UPDATE';
    const DELETE = 'DELETE';

    protected const QUERY_PART_DEFAULTS = [
        'select'   => [],
        'distinct' => false,
        'from'     => null,
        'join'     => [],
        'where'    => [],
        'groupBy'  => [],
        'having'   => [],
        'orderBy'  => [],
        'limit'    => null,
        'offset'   => 0,
        'values'   => [],
    ];

    protected $type;
    protected $parts = self::QUERY_PART_DEFAULTS;

    /**
     * Sets the query type.
     * 
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }

    /**
     * Gets the query type.
     * 
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Gets a query part.
     * 
     * @param string $part The name of the query part.
     * @return mixed
     */
    public function getPart(string $part)
    {
        return $this->parts[$part];
    }

    /**
     * Resets a query part.
     * 
     * @param string $part The name of the part to reset.
     */
    public function resetPart(string $part)
    {
        $this->parts[$part] = self::QUERY_PART_DEFAULTS[$part];
    }

    /**
     * Adds a select clause to the query.
     * 
     * @param array $columns
     */
    public function addSelect(array $columns)
    {
        array_push($this->parts['select'], ...$columns);
    }

    /**
     * Sets whether the select clause is distinct.
     * 
     * @param bool $distinct
     */
    public function setDistinct(bool $distinct)
    {
        $this->parts['distinct'] = $distinct;
    }

    /**
     * Sets the from clause of the query.
     * 
     * @param mixed $table
     */
    public function setFrom($table)
    {
        $this->parts['from'] = $table;
    }

    /**
     * Adds a join clause to the query.
     *
     * @param string $type      The type of join.
     * @param mixed  $table
     * @param mixed  $on
     */
    public function addJoin($type, $table, $on)
    {
        $this->parts['join'][] = [$type, $table, $on];
    }

    public function addWhere(array $conditions)
    {
        $this->parts['where'][] = $conditions;
    }

    public function addGroupBy($column)
    {
        $this->parts['groupBy'][] = $column;
    }

    public function addHaving(array $conditions)
    {
        $this->parts['having'][] = $conditions;
    }

    public function addOrderBy($column, $sort)
    {
        $this->parts['orderBy'][] = [$column, $sort];
    }

    public function setLimit($limit)
    {
        $this->parts['limit'] = $limit;
    }

    public function setOffset($offset)
    {
        $this->parts['offset'] = $offset;
    }

    public function addValues(array $values)
    {
        $this->parts['values'] = array_merge($this->parts['values'], $values);
    }
}