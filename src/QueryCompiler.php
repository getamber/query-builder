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
        switch ($query->getType()) {
            case Query::SELECT:
                return $this->getSQLForSelect($query);

            case Query::INSERT:
                return $this->getSQLForInsert($query);
        
            case Query::UPDATE:
                return $this->getSQLForUpdate($query);

            case Query::DELETE:
                return $this->getSQLForDelete($query);
            
            default:
                return $this->getSQLForConditions($query->getPart('where'));
        }
    }

    protected function getSQLForSelect($query): string
    {
        $sql = ['SELECT'];

        if ($query->getPart('distinct')) {
            $sql[] = 'DISTINCT';
        }

        $sql[] = join(',', $query->getPart('select'));
        
        if ($from = $query->getPart('from')) {
            $sql[] = 'FROM '.$from;
        }

        $sql[] = $this->getSQLForJoins($query->getPart('join'));
        $sql[] = $this->getSQLForWhereClause($query->getPart('where'));
        $sql[] = $this->getSQLForGroupByClause($query->getPart('groupBy'), $query->getPart('having'));
        $sql[] = $this->getSQLForOrderByClause($query->getPart('orderBy'));
        $sql[] = $this->getSQLForLimitClause($query->getPart('limit'), $query->getPart('offset'));

        return join(' ', array_filter($sql));
    }

    protected function getSQLForInsert(Query $query): string
    {
        return sprintf('INSERT INTO %s (%s) VALUES (%s)',
            $query->getPart('from'),
            join(',', array_keys($query->getPart('values'))),
            join(',', $query->getPart('values'))
        );
    }

    protected function getSQLForUpdate(Query $query): string
    {
        return sprintf('UPDATE %s SET %s %s',
            $query->getPart('from'),
            join(',', array_map(function ($column, $value) {
                return $column.'='.$value;
            }, array_keys($query->getPart('values')), $query->getPart('values'))),
            $this->getSQLForWhereClause($query->getPart('where'))
        );
    }

    protected function getSQLForDelete(Query $query): string
    {
        return sprintf('DELETE FROM %s %s',
            $query->getPart('from'),
            $this->getSQLForWhereClause($query->getPart('where'))
        );
    }

    protected function getSQLForJoins($joins): string
    {
        return join(' ', array_map(function ($join) {
            return $this->getSQLForJoin(...$join);
        }, $joins));
    }

    protected function getSQLForJoin($join, $table, $on): string
    {
        $sql = $join.' '.$table;

        if ($on) {
            $sql .= ' ON '.$on;
        }

        return $sql;
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
        return join(' ', array_map(function ($parts) {
            return join(' ', $parts);
        }, $conditions));
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