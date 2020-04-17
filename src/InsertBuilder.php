<?php

namespace Amber\Components\QueryBuilder;

class InsertBuilder
{
    protected $table;
    protected $values = [];
    
    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    public function values($values)
    {
        $this->values = array_merge($this->values, $values);
        return $this;
    }

    public function __toString()
    {
        $sql = 'INSERT INTO '.$this->table;
        $sql .= ' ('.join(',', array_keys($this->values)).')';
        $sql .= ' VALUES ('.join(',', $this->values).')';

        return $sql;
    }
}