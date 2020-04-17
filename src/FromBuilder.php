<?php

namespace Amber\Components\QueryBuilder;

class FromBuilder extends QueryClause
{
    protected $table;
    protected $alias;

    public function setTable($table, string $alias = null)
    {
        $this->table = $table;
        $this->alias = $alias;
    }

    public function getSQL(): string
    {
        if (!$this->table) {
            return '';
        }

        $sql = 'FROM ';

        if ($this->table instanceof SelectBuilder) {
            $sql .= '('.$this->table.')';
        } else {
            $sql .= $this->table;
        }

        return trim($sql.' '.$this->alias);
    }
}