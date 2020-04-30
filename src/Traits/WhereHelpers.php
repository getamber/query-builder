<?php

namespace Amber\Components\QueryBuilder\Traits;

use Closure;

trait WhereHelpers
{
    public function whereNot($condition): self
    {
        return $this->where('NOT', ...func_get_args());
    }

    public function andWhere($condition): self
    {
        return $this->addWhere('AND', ...func_get_args());
    }

    public function andWhereNot($condition): self
    {
        return $this->addWhere('AND NOT', ...func_get_args());
    }

    public function orWhere($condition): self
    {
        return $this->addWhere('OR', ...func_get_args());
    }

    public function orWhereNot($condition): self
    {
        return $this->addWhere('OR NOT', ...func_get_args());
    }

    public function whereExists(Closure $subquery): self
    {
        return $this->where('EXISTS', $subquery);
    }

    public function whereNotExists(Closure $subquery): self
    {
        return $this->where('NOT EXISTS', $subquery);
    }

    public function andWhereExists(Closure $subquery): self
    {
        return $this->addWhere('AND EXISTS', $subquery);
    }

    public function andWhereNotExists(Closure $subquery): self
    {
        return $this->addWhere('AND NOT EXISTS', $subquery);
    }

    public function orWhereExists(Closure $subquery): self
    {
        return $this->addWhere('OR EXISTS', $subquery);
    }

    public function orWhereNotExists(Closure $builder)
    {
        return $this->addWhere('OR NOT EXISTS', $builder);
    }
}