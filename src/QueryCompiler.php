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
    public function getSQL(Query $query): string
    {
        switch ($query->type) {
            case Query::SELECT:
                return $this->getSQLForSelect($query);

            case Query::INSERT:
                return $this->getSQLForInsert($query);
        
            case Query::UPDATE:
                return $this->getSQLForUpdate($query);

            case Query::DELETE:
                return $this->getSQLForDelete($query);
            
            default:
                return $this->getSQLForConditions($query->where);
        }
    }

    protected function getSQLForSelect($query): string
    {
        $sql = ['SELECT'];

        if ($query->distinct) {
            $sql[] = 'DISTINCT';
        }

        $sql[] = join(',', $query->select);
        
        if ($query->from) {
            $sql[] = 'FROM '.$query->from;
        }

        $sql[] = $this->getSQLForJoins($query->join);
        $sql[] = $this->getSQLForWhereClause($query->where);
        $sql[] = $this->getSQLForGroupByClause($query->groupBy, $query->having);
        $sql[] = $this->getSQLForOrderByClause($query->orderBy);
        $sql[] = $this->getSQLForLimitClause($query->limit, $query->offset);

        return join(' ', array_filter($sql));
    }

    protected function getSQLForInsert(Query $query): string
    {
        return sprintf('INSERT INTO %s (%s) VALUES (%s)',
            $query->from,
            join(',', array_keys($query->values)),
            join(',', $query->values)
        );
    }

    protected function getSQLForUpdate(Query $query): string
    {
        return sprintf('UPDATE %s SET %s %s',
            $query->from,
            join(',', array_map(function ($column, $value) {
                return $column.'='.$value;
            }, array_keys($query->values), $query->values)),
            $this->getSQLForWhereClause($query->where)
        );
    }

    protected function getSQLForDelete(Query $query): string
    {
        return sprintf('DELETE FROM %s %s',
            $query->from,
            $this->getSQLForWhereClause($query->where)
        );
    }

    protected function getSQLForJoins($joins): string
    {
        return join(' ', array_map(function ($join) {
            return $this->getSQLForJoin(...$join);
        }, $joins));
    }

    protected function getSQLForJoin($table, $condition, $type): string
    {
        $join = $type.' '.$table;

        if ($condition) {
            $join .= ' ON '.$condition;
        }

        return $join;
    }

    protected function getSQLForWhereClause($where): string
    {
        if (!$where) {
            return '';
        }

        return 'WHERE '.$this->getSQLForConditions($where);
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

    protected function getSQLForOrderByClause($orderBy): string
    {
        if (!$orderBy) {
            return '';
        }

        return 'ORDER BY '.join(',', array_map(function ($orderBy) {
            return $orderBy[0].' '.$orderBy[1];
        }, $orderBy));
    }

    protected function getSQLForGroupByClause($groupBy, $having): string
    {
        if (!$groupBy) {
            return '';
        }

        $sql = 'GROUP BY '.join(' ', $groupBy);

        if ($having) {
            $sql .= ' HAVING '.$this->getSQLForConditions($having);
        }

        return $sql;
    }

    protected function getSQLForLimitClause($limit, $offset): string
    {
        $sql = [];

        if ($limit) {
            $sql[] = 'LIMIT '.$limit;
        }

        if ($offset > 0) {
            $sql[] = 'OFFSET '.$offset;
        }

        return join(' ', $sql);
    }
}