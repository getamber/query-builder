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
     * Generates SQL for a select.
     * 
     * @param QueryBuilder $query
     * @return string
     */
    public function getSQLForSelect(QueryBuilder $query): string
    {
        $sql = [$this->getSQLForWiths($query->getWiths()), 'SELECT'];

        if ($query->isDistinct()) {
            $sql[] = 'DISTINCT';
        }

        $sql[] = join(',', $query->getSelect());
        
        if ($from = $query->getFrom()) {
            $sql[] = 'FROM '.$from;
        }

        $sql[] = $this->getSQLForJoins($query->getJoins());
        $sql[] = $this->getSQLForWhere($query->getWhere());
        $sql[] = $this->getSQLForUnions($query->getUnions());
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
     * Generates SQL for an array of join clauses.
     * 
     * @param array $joins
     * @return string
     */
    public function getSQLForJoins(array $joins): string
    {
        return join(' ', array_map(function ($join) {
            return $this->getSQLForJoin(...$join);
        }, $joins));
    }

    /**
     * Generates SQL for a single join.
     * 
     * @param string      $join
     * @param string      $table
     * @param array|null $on
     * @return string
     */
    public function getSQLForJoin(string $join, string $table, ?array $on): string
    {
        $on = $this->getSQLForConditions($on);
        return $join.' '.$table.($on ? ' ON '.$on : '');
    }

    /**
     * Generates SQL for a where clause.
     * 
     * @param array $where
     * @return string
     */
    public function getSQLForWhere(array $where): string
    {
        if (!$where) {
            return '';
        }

        return 'WHERE '.$this->getSQLForConditions($where);
    }

    /**
     * Generates SQL for an array of conditions.
     * 
     * @param array $conditions
     * @return string
     */
    public function getSQLForConditions(array $conditions): string
    {
        return join(' ', $conditions);
    }

    /**
     * Generates SQL for an array of union clauses.
     * 
     * @param array $unions
     * @return string
     */
    public function getSQLForUnions(array $unions): string
    {
        return join(' ', array_map(function ($union) {
            return $this->getSQLForUnion(...$union);
        }, $unions));
    }

    /**
     * Generates SQL for a single union clause.
     * 
     * @param QueryBuilder $query
     * @param bool         $all
     * @return string
     */
    public function getSQLForUnion(QueryBuilder $query, bool $all)
    {
        return 'UNION'.($all ? ' ALL ' : ' ').$query;
    }

    /**
     * Generates the SQL for the order by clause.
     * 
     * @param array $orderBy
     * @return string
     */
    public function getSQLForOrderBy(array $orderBy): string
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
    public function getSQLForGroupBy(array $groupBy, array $having): string
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
    public function getSQLForLimit(?int $limit, int $offset): string
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
    public function getSQLForInsert(QueryBuilder $query): string
    {
        $sql[] = 'INSERT INTO '.$query->getFrom();

        if ($values = $query->getValues()) {
            $sql[] = $this->getSQLForColumns($query->getColumns());
            $sql[] = $this->getSQLForValues($values);
        }

        return join(' ', array_filter($sql));
    }

    /**
     * Generates the SQL for the column list of an insert statement.
     * 
     * @param array $columns
     * @return string
     */
    public function getSQLForColumns(array $columns)
    {
        return $columns ? '('.join(',', $columns).')' : '';
    }

    /**
     * Generates the SQL for the values clause of an insert statement.
     * 
     * @param array $values
     * @return string
     */
    public function getSQLForValues(array $values)
    {
        return 'VALUES ('.join(',', $values).')';
    }

    /**
     * Generates the SQL for an update statement.
     * 
     * @param QueryBuilder $query
     * @return string
     */
    public function getSQLForUpdate(QueryBuilder $query): string
    {
        return trim(sprintf('%s UPDATE %s %s %s',
            $this->getSQLForWiths($query->getWiths()),
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
    public function getSQLForSet(array $values): string
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
    public function getSQLForDelete(QueryBuilder $query): string
    {
        return trim(sprintf('%s DELETE FROM %s %s',
            $this->getSQLForWiths($query->getWiths()),
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
    public function getSQLForWiths(array $withs): string
    {
        if (!$withs) {
            return '';
        }

        $sql = [];
        foreach ($withs as $alias => list($query, $columns)) {
            $sql[] = $this->getSQLForWith($alias, $columns, $query);
        }

        return 'WITH '.join(',', $sql);
    }

    /**
     * 
     */
    public function getSQLForWith($alias, $columns, $query)
    {
        if ($columns) {
           return $alias.' ('.join(',', $columns).') AS '.$query;
        } else {
            return $alias.' AS '.$query;
        }
    }
}