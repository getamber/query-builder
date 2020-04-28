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
    /**
     * Generates SQL from a QueryBuilder instance.
     * 
     * @param QueryBuilder $query
     * @return string
     */
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

    /**
     * Generates the SQL for a select query.
     * 
     * @param QueryBuilder $query
     * @return string
     */
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

        $sql[] = $this->getSQLForJoinClauses($query->getJoin());
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

    /**
     * Generates the SQL for the join clauses.
     * 
     * @param array $joins
     * @return string
     */
    protected function getSQLForJoinClauses(array $joins): string
    {
        return join(' ', array_map(function ($join) {
            return $this->getSQLForJoinClause(...$join);
        }, $joins));
    }

    /**
     * Generates the SQL for a single join clause.
     * 
     * @param string $join
     * @param string $table
     * @param string $on
     * @return string
     */
    protected function getSQLForJoinClause(string $join, string $table, string $on): string
    {
        return $join.' '.$table.($on ? ' ON '.$on : '');
    }

    /**
     * Generates the SQL for the where clause.
     * 
     * @param array $where
     * @return string
     */
    protected function getSQLForWhereClause(array $where): string
    {
        if (!$where) {
            return '';
        }

        return 'WHERE '.$this->getSQLForConditions($where);
    }

    /**
     * Generates the SQL for conditions.
     * 
     * @param array $conditions
     * @return string
     */
    protected function getSQLForConditions(array $conditions): string
    {
        return join(' ', array_map(function ($parts) {
            return join(' ', $parts);
        }, $conditions));
    }

    /**
     * Generates the SQL for the order by clause.
     * 
     * @param array $orderBy
     * @return string
     */
    protected function getSQLForOrderByClause(array $orderBy): string
    {
        if (!$orderBy) {
            return '';
        }

        return 'ORDER BY '.join(',', array_map(function ($orderBy) {
            return join(' ', $orderBy);
        }, $orderBy));
    }

    /**
     * Generates the SQL for the group by and having clause.
     * 
     * @param array $groupBy
     * @param array $having
     * @return string
     */
    protected function getSQLForGroupByClause(array $groupBy, array $having): string
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

    /**
     * Generates the SQL for the limit and offset clause.
     * 
     * @param int|null $limit
     * @param int      $offset
     * @return string
     */
    protected function getSQLForLimitClause(?int $limit, int $offset): string
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

    /**
     * Generates the SQL for an insert query.
     * 
     * @param QueryBuilder $query
     * @return string
     */
    protected function getSQLForInsert(QueryBuilder $query): string
    {
        $sql[] = 'INSERT INTO '.$query->getTable();

        $values = $query->getValues();

        if ($values) {
            $sql[] = $this->getSQLForColumns($query->getColumns());
            $sql[] = $values instanceof QueryBuilder ? $values : $this->getSQLForValues($values);
        }

        return join(' ', array_filter($sql));
    }

    /**
     * Generates the SQL for the columns of an insert query.
     * 
     * @param array $columns
     * @return string
     */
    protected function getSQLForColumns(array $columns)
    {
        return $columns ? '('.join(',', $columns).')' : '';
    }

    /**
     * Generates the SQL for the values clause of an insert query.
     * 
     * @param array $values
     * @return string
     */
    protected function getSQLForValues(array $values)
    {
        return 'VALUES ('.join(',', $values).')';
    }

    /**
     * Generates the SQL for an update query.
     * 
     * @param QueryBuilder $query
     * @return string
     */
    protected function getSQLForUpdate(QueryBuilder $query): string
    {
        return trim(sprintf('UPDATE %s SET %s %s',
            $query->getTable(),
            $this->getSQLForSetClause($query->getValues()),
            $this->getSQLForWhereClause($query->getWhere())
        ));
    }

    /**
     * Generates the SQL for a set clause of an update query.
     * 
     * @param array $values
     * @return string
     */
    protected function getSQLForSetClause(array $values): string
    {
        $set = [];
        foreach ($values as $column => $value) {
            $set[] = $column.'='.$value;
        }

        return join(',', $set);
    }

    /**
     * Generates the SQL for a delete query.
     * 
     * @param QueryBuilder $query
     * @return string
     */
    protected function getSQLForDelete(QueryBuilder $query): string
    {
        return sprintf('DELETE FROM %s %s',
            $query->getTable(),
            $this->getSQLForWhereClause($query->getWhere())
        );
    }
}