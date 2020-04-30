<?php

namespace Amber\Components\QueryBuilder\Traits;

trait HavingHelpers
{
    public function notHaving($condition)
    {
        return $this->having('NOT', ...func_get_args());
    }

    public function andHaving($condition)
    {
        return $this->addHaving('AND', ...func_get_args());
    }

    public function andNotHaving($condition)
    {
        return $this->addHaving('AND NOT', ...func_get_args());
    }

    public function orHaving($condition)
    {
        return $this->addHaving('OR', ...func_get_args());
    }

    public function orNotHaving($condition)
    {
        return $this->addHaving('OR NOT', ...func_get_args());
    }
}