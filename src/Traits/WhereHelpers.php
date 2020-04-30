<?php

namespace Amber\Components\QueryBuilder\Traits;

use Closure;

trait WhereHelpers
{
       /**
     * Begins a where clause. This method replaces the existing where clause.
     * 
     * @param string|Closure $condition
     * @return self
     */
    public function where($condition): self
    {
        $this->where = [];
        $this->addWhere(...func_get_args());
        return $this;
    }

    public function whereNot($condition): self
    {
        $this->where = [];
        $this->addWhere('NOT', ...func_get_args());
        return $this;
    }

    public function andWhere($condition): self
    {
        $this->addWhere('AND', ...func_get_args());
        return $this;
    }

    public function andWhereNot($condition): self
    {
        $this->addWhere('AND NOT', ...func_get_args());
        return $this;
    }

    public function orWhere($condition): self
    {
        $this->addWhere('OR', ...func_get_args());
        return $this;
    }

    public function orWhereNot($condition): self
    {
        $this->addWhere('OR NOT', ...func_get_args());
        return $this;
    }

    /**
     * Adds an exists condition to a where clause.
     * 
     * @param Closure $subquery
     * @return self
     */
    public function whereExists(Closure $subquery): self
    {
        $this->where = [];
        $this->addWhere('EXISTS', $subquery);
        return $this;
    }

    public function whereNotExists(Closure $subquery): self
    {
        $this->where = [];
        $this->addWhere('NOT EXISTS', $subquery);
        return $this;
    }

    public function andWhereExists(Closure $subquery): self
    {
        $this->addWhere('AND EXISTS', $subquery);
        return $this;
    }

    public function andWhereNotExists(Closure $subquery): self
    {
        $this->addWhere('AND NOT EXISTS', $subquery);
        return $this;
    }

    public function orWhereExists(Closure $subquery): self
    {
        $this->addWhere('OR EXISTS', $subquery);
        return $this;
    }

    public function orWhereNotExists(Closure $builder)
    {
        $this->addWhere('OR NOT EXISTS', $builder);
        return $this;
    }
}