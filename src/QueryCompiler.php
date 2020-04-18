<?php

namespace Amber\Components\QueryBuilder;

/**
 * Query object compiler.
 * 
 * @author  Ken Lynch
 * @license MIT
 */
class QueryCompiler
{
    protected $query;

    public static function create(Query $query)
    {
        return new static($query);
    }

    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    public function getSQL(): string
    {
        switch ($this->query->type) {
            case Query::SELECT:
                return $this->getSQLForSelect();

            case Query::INSERT:
                return $this->getSQLForInsert();
        
            case Query::UPDATE:
                return $this->getSQLForUpdate();

            case Query::DELETE:
                return $this->getSQLForDelete();
            
            default:
                return $this->getSQLForConditions($this->query->where);
        }
    }

    protected function getSQLForSelect(): string
    {
        $sql = ['SELECT'];

        if ($this->query->distinct) {
            $sql[] = 'DISTINCT';
        }

        $sql[] = join(',', $this->query->select);
        
        if ($this->query->from) {
            $sql[] = 'FROM '.$this->query->from;
        }

        $sql[] = $this->getSQLForJoins();
        $sql[] = $this->getSQLForWhereClause();
        $sql[] = $this->getSQLForGroupByClause();
        $sql[] = $this->getSQLForOrderByClause();
        $sql[] = $this->getSQLForLimitClause();

        return join(' ', array_filter($sql));
    }

    protected function getSQLForInsert(): string
    {
        return sprintf('INSERT INTO %s (%s) VALUES (%s)',
            $this->query->from,
            join(',', array_keys($this->query->values)),
            join(',', $this->query->values)
        );
    }

    protected function getSQLForUpdate(): string
    {
        return sprintf('UPDATE %s SET %s %s',
            $this->query->from,
            join(',', array_map(function ($column, $value) {
                return $column.'='.$value;
            }, array_keys($this->query->values), $this->query->values)),
            $this->getSQLForWhereClause()
        );
    }

    protected function getSQLForDelete(): string
    {
        return sprintf('DELETE FROM %s %s',
            $this->query->from,
            $this->getSQLForWhereClause()
        );
    }

    protected function getSQLForJoins(): string
    {
        return join(' ', array_map(function ($join) {
            return $this->getSQLForJoin(...$join);
        }, $this->query->join));
    }

    protected function getSQLForJoin($table, $condition, $type): string
    {
        $join = $type.' '.$table;

        if ($condition) {
            $join .= ' ON '.$condition;
        }

        return $join;
    }

    protected function getSQLForWhereClause(): string
    {
        if (!$this->query->where) {
            return '';
        }

        return 'WHERE '.$this->getSQLForConditions($this->query->where);
    }

    protected function getSQLForConditions(array $conditions): string
    {
        return join(' ', array_map(function ($condition) {
            return $this->getSQLForCondition(...$condition);
        }, $conditions));
    }

    protected function getSQLForCondition($condition, $operator)
    {
        return trim($operator.' '.$condition);
    }

    protected function getSQLForOrderByClause(): string
    {
        if (!$this->query->orderBy) {
            return '';
        }

        return 'ORDER BY '.join(',', array_map(function ($orderBy) {
            return $orderBy[0].' '.$orderBy[1];
        }, $this->query->orderBy));
    }

    protected function getSQLForGroupByClause(): string
    {
        if (!$this->query->groupBy) {
            return '';
        }

        $sql = 'GROUP BY '.join(' ', $this->query->groupBy);

        if ($this->query->having) {
            $sql .= ' HAVING '.$this->getSQLForConditions($this->query->having);
        }

        return $sql;
    }

    protected function getSQLForLimitClause(): string
    {
        $query = [];

        if ($this->query->limit) {
            $query[] = 'LIMIT '.$this->query->limit;
        }

        if ($this->query->offset) {
            $query[] = 'OFFSET '.$this->query->offset;
        }

        return join(' ', $query);
    }
}