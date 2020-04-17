<?php

namespace Amber\Components\QueryBuilder;

class DeleteBuilder
{
    protected $table;
    protected $where;

    public function __construct()
    {
        $this->where = new WhereBuilder();
    }

    public function table(string $table): self
    {
        $this->table = $table;
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

    public function __toString()
    {
        $sql = 'DELETE FROM '.$this->table;

        if ($where = (string) $this->where) {
            $sql .= ' '.$where;
        }

        return $sql;
    }
}