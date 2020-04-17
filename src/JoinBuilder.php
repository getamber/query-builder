<?php

namespace Amber\Components\QueryBuilder;

class JoinBuilder
{
    const INNER_JOIN = 'JOIN';
    const LEFT_JOIN  = 'LEFT JOIN';
    const RIGHT_JOIN = 'RIGHT JOIN';
    const CROSS_JOIN = 'CROSS JOIN';

    protected $joins = [];

    public function addJoin($table, $alias, $condition, $type)
    {
        $table = trim($table.' '.$alias);
        $join = $type.' '.$table;

        if ($condition) {
            $join .= ' ON '.$condition;
        }

        $this->joins[] = $join;
    }

    public function __toString()
    {
        return join(' ', $this->joins);
    }
}