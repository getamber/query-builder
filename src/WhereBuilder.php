<?php

namespace Amber\Components\QueryBuilder;

class WhereBuilder extends QueryClause
{
    protected $conditions;

    public function __construct()
    {
        $this->conditions = new ConditionBuilder();
    }

    public function addCondition($condition, $operator = ConditionBuilder::AND)
    {
        $this->conditions->addCondition($condition, $operator);
    }

    public function getSQL(): string
    {
        return $this->conditions->hasConditions() ? 'WHERE '.$this->conditions : '';
    }
}