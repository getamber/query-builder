<?php

namespace Amber\Components\QueryBuilder\Traits;

use Closure;

trait UnionHelpers
{
    public function union(Closure $query)
    {
        return $this->addUnion($query);
    }

    public function unionAll(Closure $query)
    {
        return $this->addUnion($query, true);
    }
}