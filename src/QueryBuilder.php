<?php

namespace Amber\Components\QueryBuilder;

use Closure;

class QueryBuilder
{
    const TYPE_SELECT = 'SELECT';
    const TYPE_INSERT = 'INSERT';
    const TYPE_UPDATE = 'UPDATE';
    const TYPE_DELETE = 'DELETE';

    const SORT_ASC = 'ASC';
    const SORT_DESC = 'DESC';

    protected $type;

    protected $select = [];
    protected $distinct = false;
    protected $from;
    protected $join;
    protected $where;
    protected $groupBy = [];
    protected $having;
    protected $orderBy = [];
    protected $limit;

    protected $table;
    protected $values = [];

    public function __construct()
    {
        $this->join = new JoinBuilder();
        $this->where = new ConditionBuilder();
        $this->having = new ConditionBuilder();
        $this->limit = new LimitBuilder();
    }

    public function select($columns): self
    {
        $this->type = self::TYPE_SELECT;
        $columns = is_array($columns) ? $columns : func_get_args();
        array_push($this->select, ...$columns);        
        return $this;
    }

    public function distinct(bool $distinct = true): self
    {
        $this->distinct = $distinct;
        return $this;
    }

    public function from($from, string $alias = null): self
    {
        if ($from instanceof Closure) {
            $query = new static();
            $from($query);
            $from = '('.$query.')';
        }

        $this->from = trim($from.' '.$alias);
        return $this;
    }

    public function join($table, string $alias, $condition): self
    {
        $this->join->addJoin($table, $alias, $condition, JoinBuilder::INNER_JOIN);
        return $this;
    }

    public function leftJoin($table, string $alias, $condition): self
    {
        $this->join->addJoin($table, $alias, $condition, JoinBuilder::LEFT_JOIN);
        return $this;
    }

    public function rightJoin($table, string $alias, $condition): self
    {
        $this->join->addJoin($table, $alias, $condition, JoinBuilder::RIGHT_JOIN);
        return $this;
    }

    public function crossJoin($table, string $alias): self
    {
        $this->join->addJoin($table, $alias, null, JoinBuilder::CROSS_JOIN);
        return $this;
    }

    public function where($condition): self
    {
        if ($condition instanceof Closure) {
            $query = new static();
            $condition($query);
            $condition = '('.$query.')';
        }

        $this->where->addCondition($condition, ConditionBuilder::AND);
        return $this;
    }

    public function andWhere($condition): self
    {
        return $this->where($condition);
    }

    public function orWhere($condition): self
    {
        if ($condition instanceof Closure) {
            $query = new static();
            $condition($query);
            $condition = '('.$query.')';
        }

        $this->where->addCondition($condition, ConditionBuilder::OR);
        return $this;
    }

    public function order(string $column, string $sort = QueryBuilder::SORT_ASC): self
    {
        $this->orderBy[] = $column.' '.$sort;
        return $this;
    }

    public function group($column): self
    {
        $column = is_array($column) ? $column : func_get_args();
        array_push($this->groupBy, ...$column);
        return $this;
    }

    public function having($condition): self
    {
        if ($condition instanceof Closure) {
            $query = new static();
            $condition($query);
            $condition = '('.$query.')';
        }

        $this->having->addCondition($condition, ConditionBuilder::AND);
        return $this;
    }

    public function andHaving($condition): self
    {
        return $this->having($condition);
    }

    public function orHaving($condition): self
    {
        if ($condition instanceof Closure) {
            $query = new static();
            $condition($query);
            $condition = '('.$query.')';
        }

        $this->having->addCondition($condition, ConditionBuilder::OR);
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit->setLimit($limit);
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->limit->setOffset($offset);
        return $this;
    }

    public function insert(string $table, $values = [])
    {
        $this->type = self::TYPE_INSERT;
        $this->table = $table;
        $this->values($values);
        return $this;
    }

    public function update(string $table, $values = []): self
    {
        $this->type = self::TYPE_UPDATE;
        $this->table = $table;
        $this->values($values);
        return $this;
    }

    public function values(array $values): self
    {
        $this->values = array_merge($this->values, $values);
        return $this;
    }

    public function delete(string $table)
    {
        $this->type = self::TYPE_DELETE;
        $this->table = $table;
        return $this;
    }

    public function getSQL(): string
    {
        switch ($this->type) {
            case self::TYPE_SELECT:
                return $this->getSQLForSelect();

            case self::TYPE_INSERT:
                return $this->getSQLForInsert();
        
            case self::TYPE_UPDATE:
                return $this->getSQLForUpdate();

            case self::TYPE_DELETE:
                return $this->getSQLForDelete();
            
            default:
                return $this->where;
        }
    }

    protected function getSQLForSelect(): string
    {
        $sql = ['SELECT'];

        if ($this->distinct) {
            $sql[] = 'DISTINCT';
        }

        $sql[] = join(',', $this->select);
        
        if ($this->from) {
            $sql[] = 'FROM '.$this->from;
        }

        $sql[] = (string) $this->join;

        if ($this->where->hasConditions()) {
            $sql[] = 'WHERE '.$this->where;
        }

        if ($this->groupBy) {
            $sql[] = 'GROUP BY '.join(',', $this->groupBy);

            if ($this->having) {
                $sql[] = 'HAVING '.$this->having;
            }
        }

        if ($this->orderBy) {
            $sql[] = 'ORDER BY '.join(',', $this->orderBy);
        }

        $sql[] = (string) $this->limit;

        return join(' ', array_filter($sql));
    }

    protected function getSQLForInsert(): string
    {
        return sprintf('INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            join(',', array_keys($this->values)),
            join(',', $this->values)
        );
    }

    protected function getSQLForUpdate(): string
    {
        return sprintf('UPDATE %s SET %s WHERE %s',
            $this->table,
            join(',', array_map(function ($column, $value) {
                return $column.'='.$value;
            }, array_keys($this->values), $this->values)),
            $this->where->hasConditions() ? $this->where : 1
        );
    }

    protected function getSQLForDelete(): string
    {
        return sprintf('DELETE FROM %s WHERE %s',
            $this->table,
            $this->where->hasConditions() ? $this->where : 1
        );
    }

    public function __toString()
    {
        return $this->getSQL();
    }
}