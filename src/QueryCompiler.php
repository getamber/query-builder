<?php

namespace Amber\Components\QueryBuilder;

/**
 * QueryBuilder compiler.
 * 
 * @author  Ken Lynch
 * @license MIT
 */
class QueryCompiler
{
    public function getSQL(QueryBuilder $query): string
    {
        switch ($query->getType()) {
            case QueryBuilder::SELECT:
                return $this->getSQLForSelect($query);

            case QueryBuilder::INSERT:
                return $this->getSQLForInsert($query);
        
            case QueryBuilder::UPDATE:
                return $this->getSQLForUpdate($query);

            case QueryBuilder::DELETE:
                return $this->getSQLForDelete($query);
            
            default:
                return $this->getSQLForConditions($query->getWhere());
        }
    }

    protected function getSQLForSelect(QueryBuilder $query): string
    {
        $sql = ['SELECT'];

        if ($query->isDistinct()) {
            $sql[] = 'DISTINCT';
        }

        $sql[] = join(',', $query->getSelect());
        
        if ($from = $query->getFrom()) {
            $sql[] = 'FROM '.$from;
        }

        $sql[] = $this->getSQLForJoins($query->getJoin());
        $sql[] = $this->getSQLForWhereClause($query->getWhere());
        $sql[] = $this->getSQLForGroupByClause($query->getGroupBy(), $query->getHaving());
        $sql[] = $this->getSQLForOrderByClause($query->getOrderBy());
        $sql[] = $this->getSQLForLimitClause($query->getLimit(), $query->getOffset());

        $sql = join(' ', array_filter($sql));

        if ($query->isSubquery()) {
            $alias = $query->getAlias();
            $sql = '('.$sql.')'. ($alias ? ' AS '.$alias : '');
        }

        return $sql;
    }

    protected function getSQLForInsert(QueryBuilder $query): string
    {
        return sprintf('INSERT INTO %s (%s) VALUES (%s)',
            $query->getFrom(),
            join(',', array_keys($query->getValues())),
            join(',', $query->getValues())
        );
    }

    protected function getSQLForUpdate(QueryBuilder $query): string
    {
        return sprintf('UPDATE %s SET %s %s',
            $query->getFrom(),
            join(',', array_map(function ($column, $value) {
                return $column.'='.$value;
            }, array_keys($query->getValues()), $query->getValues())),
            $this->getSQLForWhereClause($query->getWhere())
        );
    }

    protected function getSQLForDelete(QueryBuilder $query): string
    {
        return sprintf('DELETE FROM %s %s',
            $query->getFrom(),
            $this->getSQLForWhereClause($query->getWhere())
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