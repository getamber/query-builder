<?php

namespace Amber\Components\QueryBuilder;

class LimitBuilder extends QueryClause
{
    protected $limit;
    protected $offset;

    public function setLimit(int $limit)
    {
        $this->limit = $limit;
    }

    public function setOffset(int $offset)
    {
        $this->offset = $offset;
    }

    public function getSQL(): string
    {
        $query = [];

        if ($this->limit) {
            $query[] = 'LIMIT '.$this->limit;
        }

        if ($this->offset) {
            $query[] = 'OFFSET '.$this->offset;
        }

        return join(' ', $query);
    }
}