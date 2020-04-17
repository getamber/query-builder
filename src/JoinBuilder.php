<?php

namespace Amber\Components\QueryBuilder;

class JoinBuilder extends QueryClause
{
    const INNER_JOIN = 'JOIN';
    const LEFT_JOIN = 'LEFT JOIN';
    const RIGHT_JOIN = 'RIGHT JOIN';
    const CROSS_JOIN = 'CROSS JOIN';

    protected $joins = [];
    protected $hasJoins = false;

    public function addJoin($table, $alias, $condition = null, $type = JoinBuilder::INNER_JOIN)
    {
        $table = trim($table.' '.$alias);
        $join = $type.' '.$table;

        if ($condition) {
            $join .= ' ON '.$condition;
        }

        $this->joins[] = $join;
        $this->hasJoins = true;
    }

    public function hasJoins()
    {
        return $this->hasJoins;
    }

    public function getSQL(): string
    {
        return join(' ', $this->joins);
    }
}