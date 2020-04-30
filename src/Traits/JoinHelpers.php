<?php

namespace Amber\Components\QueryBuilder\Traits;

trait JoinHelpers
{
    /**
     * Adds an inner join.
     * 
     * @param string|Closure $table The table or subquery to join with. 
     * @param string|Closure $on    The on condition of the join.
     * @return self
     */
    public function join($table, ...$on): self
    {
        return $this->innerJoin($table, ...$on);
    }

    /**
     * Adds an inner join.
     * 
     * @param string|Closure $table The table or subquery to join with. 
     * @param string|Closure $on    The on condition of the join.
     * @return self
     */
    public function innerJoin($table, ...$on): self
    {
        return $this->addJoin('INNER JOIN', $table, ...$on);
    }

    /**
     * Adds a left join.
     * 
     * @param string|Closure $table The table or subquery to join with. 
     * @param string|Closure $on    The on condition of the join.
     * @return self
     */
    public function leftJoin($table, ...$on): self
    {
        return $this->addJoin('LEFT JOIN', $table, ...$on);
    }

    /**
     * Adds a right join.
     * 
     * @param string|Closure $table The table or subquery to join with. 
     * @param string|Closure $on    The on condition of the join.
     * @return self
     */
    public function rightJoin($table, ...$on): self
    {
        return $this->addJoin('RIGHT JOIN', $table, ...$on);
    }

    /**
     * Adds a cross join.
     * 
     * @param string|Closure $table The table or subquery to join with. 
     * @return self
     */
    public function crossJoin($table): self
    {
        $this->addJoin('CROSS JOIN', $table);
        return $this;
    }
}