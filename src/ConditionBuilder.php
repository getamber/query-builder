<?php

namespace Amber\Components\QueryBuilder;

use Closure;

class ConditionBuilder extends QueryClause
{
    const AND = 'AND';
    const OR = 'OR';

    protected $conditions = [];
    protected $operator;
    protected $hasConditions = false;

    public function addCondition($condition, $operator = ConditionBuilder::AND)
    {
        $operator = $this->hasConditions ? $operator : '';
        
        if ($condition instanceof Closure) {
            $condition = $this->getConditionFromClosure($condition);
        }

        $this->conditions[] = trim($operator.' '.$condition);
        $this->hasConditions = true;
    }

    public function where($condition): self
    {
        $this->addCondition($condition, ConditionBuilder::AND);
        return $this;
    }

    public function andWhere($condition)
    {
        $this->addCondition($condition, ConditionBuilder::AND);
        return $this;
    }

    public function orWhere($condition)
    {
        $this->addCondition($condition, ConditionBuilder::OR);
        return $this;
    }

    public function hasConditions(): bool
    {
        return (bool) $this->conditions;
    }

    protected function getConditionFromClosure(Closure $closure)
    {
        $condition = new ConditionBuilder();
        $closure($condition);
        return '('.$condition.')';
    }

    public function getSQL(): string
    {
        return join(' ', $this->conditions);
    }
}