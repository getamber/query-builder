<?php

namespace Amber\Components\QueryBuilder;

class UpdateBuilder
{
    protected $table;
    protected $values = [];
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

    public function values($values): self
    {
        $this->values = array_merge($this->values, $values);
        return $this;
    }

    public function where($condition): self
    {
        $this->where->addCondition($condition, ConditionBuilder::AND);
        return $this;
    }

    public function andWhere($condition): self
    {
        return $this->where($condition);
    }

    public function orWhere($condition): self
    {
        $this->where->addCondition($condition, ConditionBuilder::OR);
        return $this;
    }

    public function __toString()
    {
        $sql = 'UPDATE '.$this->table.' SET ';
        $sql .= join(',', array_map(function ($column, $value) {
            return $column.'='.$value;
        }, array_keys($this->values), $this->values));
        
        if ($where = (string) $this->where) {
            $sql .= ' '.$this->where;
        }

        return $sql;
    }
}