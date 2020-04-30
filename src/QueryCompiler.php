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
     * Generates the SQL for a select statement.
     * 
     * @param QueryBuilder $query
     * @return string
     */
    protected function getSQLForSelect(QueryBuilder $query): string
    {
        $sql = [$this->getSQLForWith($query->getWith()), 'SELECT'];

        if ($query->isDistinct()) {
            $sql[] = 'DISTINCT';
        }

        $sql[] = join(',', $query->getSelect());
        
        if ($from = $query->getFrom()) {
            $sql[] = 'FROM '.$from;
        }

        $sql[] = $this->getSQLForJoins($query->getJoins());
        $sql[] = $this->getSQLForWhere($query->getWhere());
        $sql[] = $this->getSQLForGroupBy($query->getGroupBy(), $query->getHaving());
        $sql[] = $this->getSQLForOrderBy($query->getOrderBy());
        $sql[] = $this->getSQLForLimit($query->getLimit(), $query->getOffset());

        $sql = join(' ', array_filter($sql));

        if ($query->isSubquery()) {
            $alias = $query->getAlias();
            $sql = '('.$sql.')'. ($alias ? ' AS '.$alias : '');
        }

        return $sql;
    }

    /**
     * Generates the SQL for the join clauses of a select statement.
     * 
     * @param array $joins
     * @return string
     */
    protected function getSQLForJoins(array $joins): string
    {
        return join(' ', array_map(function ($join) {
            return $this->getSQLForJoin(...$join);
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
    protected function getSQLForJoin(string $join, string $table, string $on): string
    {
        return $join.' '.$table.($on ? ' ON '.$on : '');
    }

    /**
     * Generates the SQL for a where clause.
     * 
     * @param array $where
     * @return string
     */
    protected function getSQLForWhere(array $where): string
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
    protected function getSQLForOrderBy(array $orderBy): string
    {
        if (!$orderBy) {
            return '';
        }

        return 'ORDER BY '.join(',', array_map(function ($orderBy) {
            return join(' ', $orderBy);
        }, $orderBy));
    }

    /**
     * Generates the SQL for the group by and having clauses.
     * 
     * @param array $groupBy
     * @param array $having
     * @return string
     */
    protected function getSQLForGroupBy(array $groupBy, array $having): string
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
     * Generates the SQL for the limit and offset clauses.
     * 
     * @param int|null $limit
     * @param int      $offset
     * @return string
     */
    protected function getSQLForLimit(?int $limit, int $offset): string
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
     * Generates the SQL for an insert statement.
     * 
     * @param QueryBuilder $query
     * @return string
     */
    protected function getSQLForInsert(QueryBuilder $query): string
    {
        $sql[] = 'INSERT INTO '.$query->getFrom();

        if ($values = $query->getValues()) {
            $sql[] = $this->getSQLForColumnList($query->getColumns());
            $sql[] = $values instanceof QueryBuilder ? $values : $this->getSQLForValues($values);
        }

        return join(' ', array_filter($sql));
    }

    /**
     * Generates the SQL for the column list of an insert statement.
     * 
     * @param array $columns
     * @return string
     */
    protected function getSQLForColumnList(array $columns)
    {
        return $columns ? '('.join(',', $columns).')' : '';
    }

    /**
     * Generates the SQL for the values clause of an insert statement.
     * 
     * @param array $values
     * @return string
     */
    protected function getSQLForValues(array $values)
    {
        return 'VALUES ('.join(',', $values).')';
    }

    /**
     * Generates the SQL for an update statement.
     * 
     * @param QueryBuilder $query
     * @return string
     */
    protected function getSQLForUpdate(QueryBuilder $query): string
    {
        return trim(sprintf('%s UPDATE %s %s %s',
            $this->getSQLForWith($query->getWith()),
            $query->getFrom(),
            $this->getSQLForSet($query->getValues()),
            $this->getSQLForWhere($query->getWhere())
        ));
    }

    /**
     * Generates the SQL for the set clause of an update statement.
     * 
     * @param array $values
     * @return string
     */
    protected function getSQLForSet(array $values): string
    {
        $set = [];
        foreach ($values as $column => $value) {
            $set[] = $column.'='.$value;
        }

        return 'SET '.join(',', $set);
    }

    /**
     * Generates the SQL for a delete statement.
     * 
     * @param QueryBuilder $query
     * @return string
     */
    protected function getSQLForDelete(QueryBuilder $query): string
    {
        return trim(sprintf('%s DELETE FROM %s %s',
            $this->getSQLForWith($query->getWith()),
            $query->getFrom(),
            $this->getSQLForWhere($query->getWhere())
        ));
    }

    /**
     * Generates the SQL for a with clause.
     * 
     * @param array $withs
     * @return string
     */
    protected function getSQLForWith(array $with): string
    {
        if (!$with) {
            return '';
        }

        $sql = [];
        foreach ($with as $name => list($query, $columns)) {
            if ($columns) {
                $sql[] = $name.' ('.join(',', $columns).') AS '.$query;
            } else {
                $sql[] = $name.' AS '.$query;
            }
        }

        return 'WITH '.join(',', $sql);
    }
}