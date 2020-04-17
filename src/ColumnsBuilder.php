<?php

namespace Amber\Components\QueryBuilder;

class ColumnsBuilder extends QueryClause
{
    protected $columns = [];

    public function addColumns($columns)
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        array_push($this->columns, ...$columns);
    }

    public function getSQL(): string
    {
        return join(',', $this->columns);
    }

    public function __toString()
    {
        return $this->getSQL();
    }
}
