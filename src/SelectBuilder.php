<?php

namespace Amber\Components\QueryBuilder;

class SelectBuilder
{
    protected $distinct = false;
    protected $columns;
    protected $from;
    protected $join;
    protected $where;
    protected $group = [];
    protected $having;
    protected $order;
    protected $limit;

    public function __construct()
    {
        $this->columns = new ColumnsBuilder();
        $this->from = new FromBuilder();
        $this->join = new JoinBuilder();
        $this->where = new WhereBuilder();
        $this->having = new HavingBuilder();
        $this->order = new OrderBuilder();
        $this->limit = new LimitBuilder();
    }

    public function columns($columns): self
    {
        $this->columns->addColumns(...func_get_args());
        return $this;
    }

    public function distinct(bool $distinct = true): self
    {
        $this->distinct = $distinct;
        return $this;
    }

    public function from($table, string $alias = null): self
    {
        $this->from->setTable($table, $alias);
        return $this;
    }

    public function join($table, string $alias, $constraint): self
    {
        $this->join->addJoin($table, $alias, $constraint, JoinBuilder::INNER_JOIN);
        return $this;
    }

    public function leftJoin($table, string $alias, $constraint): self
    {
        $this->join->addJoin($table, $alias, $constraint, JoinBuilder::LEFT_JOIN);
        return $this;
    }

    public function rightJoin($table, string $alias, $constraint): self
    {
        $this->join->addJoin($table, $alias, $constraint, JoinBuilder::RIGHT_JOIN);
        return $this;
    }

    public function crossJoin($table, string $alias): self
    {
        $this->join->addJoin($table, $alias, null, JoinBuilder::CROSS_JOIN);
        return $this;
    }

    public function where($constraint): self
    {
        $this->where->addCondition($constraint, ConditionBuilder::AND);
        return $this;
    }

    public function andWhere($constraint): self
    {
        return $this->where($constraint);
    }

    public function orWhere($constraint): self
    {
        $this->where->addCondition($constraint, ConditionBuilder::OR);
        return $this;
    }

    public function order(string $column, string $sort = OrderBuilder::SORT_ASC): self
    {
        $this->order->addOrder($column, $sort);
        return $this;
    }

    public function group($column): self
    {
        $column = is_array($column) ? $column : func_get_args();
        array_push($this->group, ...$column);
        return $this;
    }

    public function having($constraint): self
    {
        $this->having->addCondition($constraint, ConditionBuilder::AND);
        return $this;
    }

    public function andHaving($constraint): self
    {
        return $this->having($constraint);
    }

    public function orHaving($constraint): self
    {
        $this->having->addCondition($constraint, ConditionBuilder::OR);
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

    public function getSQL(): string
    {
        $sql = ['SELECT'];

        if ($this->distinct) {
            $sql[] = 'DISTINCT';
        }

        $sql[] = (string) $this->columns;
        $sql[] = (string) $this->from;
        $sql[] = (string) $this->join;
        $sql[] = (string) $this->where;

        if ($this->group) {
            $sql[] = 'GROUP BY '.join(',', $this->group);
        }

        $sql[] = (string) $this->having;
        $sql[] = (string) $this->order;
        $sql[] = (string) $this->limit;

        return join(' ', array_filter($sql));
    }

    public function __toString()
    {
        return $this->getSQL();
    }
}