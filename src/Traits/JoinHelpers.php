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
    public function join($table, $on): self
    {
        $this->innerJoin($table, $on);
        return $this;
    }

    /**
     * Adds an inner join.
     * 
     * @param string|Closure $table The table or subquery to join with. 
     * @param string|Closure $on    The on condition of the join.
     * @return self
     */
    public function innerJoin($table, $on): self
    {
        $this->addJoin('INNER JOIN', $table, $on);
        return $this;
    }

    /**
     * Adds a left join.
     * 
     * @param string|Closure $table The table or subquery to join with. 
     * @param string|Closure $on    The on condition of the join.
     * @return self
     */
    public function leftJoin($table, $on): self
    {
        $this->addJoin('LEFT JOIN', $table, $on);
        return $this;
    }

    /**
     * Adds a right join.
     * 
     * @param string|Closure $table The table or subquery to join with. 
     * @param string|Closure $on    The on condition of the join.
     * @return self
     */
    public function rightJoin($table, $on): self
    {
        $this->addJoin('RIGHT JOIN', $table, $on);
        return $this;
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