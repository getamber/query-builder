<?php

namespace Amber\Components\QueryBuilder;

use Closure;

class ConditionBuilder
{
    const AND = 'AND';
    const OR  = 'OR';

    protected $conditions = [];
    protected $operator;
    protected $hasConditions = false;

    public function addCondition($condition, $operator)
    {
        $this->conditions[] = trim($operator.' '.$condition);
        $this->hasConditions = true;
    }

    public function hasConditions(): bool
    {
        return (bool) $this->conditions;
    }

    public function __toString()
    {
        return join(' ', $this->conditions);
    }
}