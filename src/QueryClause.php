<?php

namespace Amber\Components\QueryBuilder;

abstract class QueryClause
{
    abstract function getSQL(): string;

    public function __toString()
    {
        return $this->getSQL();
    }
}