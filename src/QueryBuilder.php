<?php

namespace Amber\Components\QueryBuilder;

use Closure;

/**
 * Fluent query builder for creating SQL queries.
 * 
 * @author  Ken Lynch
 * @license MIT
 */
class QueryBuilder
{
    const SORT_ASC  = 'ASC';
    const SORT_DESC = 'DESC';

    protected $query;
    protected $compiler;
    protected $alias;

    /**
     * Initialises a new QueryBuilder
     * 
     * @param Closure $build
     * @param string  $compiler
     */
    public function __construct(QueryCompiler $compiler = null, Closure $build = null)
    {
        $this->query    = new Query();
        $this->compiler = $compiler ?? new QueryCompiler();

        if ($build) {
            $this->alias = $build($this);
        }
    }

    /**
     * 
     */
    public function select($columns): self
    {
        $this->query->setType(Query::SELECT);
        $this->query->resetPart('select');        
        return $this->addSelect(func_get_args());
    }

    public function addSelect($columns): self
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $columns = array_map(function ($column) {
            if ($column instanceof Closure) {
                $query = new static($this->compiler, $column);
                return '('.$query.')'.($query->alias ? ' AS '.$query->alias : '');
            } else {
                return $column;
            }
        }, $columns);
        $this->query->addSelect($columns);
        return $this;
    }

    public function distinct(bool $distinct = true): self
    {
        $this->query->setDistinct($distinct);
        return $this;
    }

    public function from($from): self
    {
        if ($from instanceof Closure) {
            $query = new static($this->compiler, $from);
            $from = '('.$query.')'.($query->alias ? ' AS '.$query->alias : '');
        }

        $this->query->setFrom($from);
        return $this;
    }

    protected function addJoin($join, $table, $on)
    {
        if ($table instanceof Closure) {
            $query = new static($this->compiler, $table);
            $table = '('.$query.')'.($query->alias ? ' AS '.$query->alias : '');
        }

        if ($on instanceof Closure) {
            $query = new static($this->compiler, $on);
            $on = '('.$query.')';
        }

        $this->query->addJoin($join, $table, $on);
    }

    public function join($table, $on): self
    {
        $this->addJoin('INNER JOIN', $table, $on);
        return $this;
    }

    public function leftJoin($table, $on): self
    {
        $this->addJoin('LEFT JOIN', $table, $on);
        return $this;
    }

    public function rightJoin($table, $on): self
    {
        $this->addJoin('RIGHT JOIN', $table, $on);
        return $this;
    }

    public function crossJoin($table): self
    {
        $this->addJoin('CROSS JOIN', $table, null);
        return $this;
    }

    protected function addWhere(...$conditions)
    {
        $conditions = array_map(function ($part) {
            if ($part instanceof Closure) {
                $query = new static($this->compiler, $part);
                return '('.$query.')';
            } else {
                return $part;
            }
        }, $conditions);
        
        $this->query->addWhere($conditions);
    }

    public function where(...$conditions): self
    {
        $this->query->resetPart('where');
        $this->addWhere(...$conditions);
        return $this;
    }

    public function whereNot(...$conditions): self
    {
        $this->query->resetPart('where');
        $this->addWhere('NOT ', ...$conditions);
        return $this;
    }

    public function andWhere(...$conditions): self
    {
        $this->addWhere('AND', ...$conditions);
        return $this;
    }

    public function andWhereNot($conditions): self
    {
        $this->addWhere('AND NOT', ...$conditions);
        return $this;
    }

    public function orWhere(...$conditions): self
    {
        $this->addWhere('OR', ...$conditions);
        return $this;
    }

    public function orWhereNot(...$conditions): self
    {
        $this->addWhere('OR NOT', ...$conditions);
        return $this;
    }

    public function whereExists(Closure $builder): self
    {
        $this->query->resetPart('where');
        $this->addWhere('EXISTS', $builder);
        return $this;
    }

    public function whereNotExists(Closure $builder): self
    {
        $this->query->resetPart('where');
        $this->addWhere('NOT EXISTS', $builder);
        return $this;
    }

    public function andWhereExists(Closure $builder): self
    {
        $this->addWhere('AND EXISTS', $builder);
        return $this;
    }

    public function andWhereNotExists(Closure $builder): self
    {
        $this->addWhere('AND NOT EXISTS', $builder);
        return $this;
    }

    public function orWhereExists(Closure $builder): self
    {
        $this->addWhere('OR EXISTS', $builder);
        return $this;
    }

    public function orWhereNotExists(Closure $builder)
    {
        $this->addWhere('OR NOT EXISTS', $builder);
        return $this;
    }

    public function orderBy(string $column, string $sort = self::SORT_ASC): self
    {
        $this->query->resetPart('orderBy');
        return $this->addOrderBy($column, $sort);
    }

    public function addOrderBy(string $column, string $sort = self::SORT_ASC): self
    {
        $this->query->addOrderBy($column, $sort);
        return $this;
    }

    public function groupBy($column): self
    {
        $this->query->resetPart('groupBy');
        $this->query->addGroupBy($column, false);
        return $this;
    }

    public function addGroupBy($column): self
    {
        $this->query->addGroupBy($column);
        return $this;
    }

    protected function addHaving(...$conditions)
    {
        $conditions = array_map(function ($part) {
            if ($part instanceof Closure) {
                $query = new static($this->compiler, $part);
                return '('.$query.')';
            } else {
                return $part;
            }
        }, $conditions);

        $this->query->addHaving($conditions);
    }

    public function having(...$conditions): self
    {
        $this->query->resetPart('having');
        $this->addHaving(...$conditions);
        return $this;
    }

    public function notHaving(...$conditions): self
    {
        $this->query->resetPart('having');
        $this->addHaving('NOT', ...$conditions);
        return $this;
    }

    public function andHaving(...$conditions): self
    {
        $this->addHaving('AND', ...$conditions);
        return $this;
    }

    public function andNotHaving(...$conditions): self
    {
        $this->addHaving('AND NOT', ...$conditions);
        return $this;
    }

    public function orHaving(...$conditions): self
    {
        $this->addHaving('OR', ...$conditions);
        return $this;
    }

    public function orNotHaving(...$conditions): self
    {
        $this->addHaving('OR NOT', ...$conditions);
        return $this;
    }

    public function limit(?int $limit): self
    {
        $this->query->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->query->limit = $offset;
        return $this;
    }

    public function insert(string $table, $values = [])
    {
        $this->query->setType(Query::INSERT);
        $this->query->setFrom($table);
        $this->values($values);
        return $this;
    }

    public function update(string $table, $values = []): self
    {
        $this->query->setType(Query::UPDATE);
        $this->query->setFrom($table);
        $this->values($values);
        return $this;
    }

    /**
     * Sets the values for an update or an insert query.
     * 
     * @param array $values Key value pairs of columns and values.
     * @return QueryBuilder
     */
    public function values(array $values): self
    {
        $this->query->addValues($values);
        return $this;
    }

    /**
     * Starts building a delete query.
     * 
     * @parameter string $table
     * @return QueryBuilder
     */
    public function delete(string $table)
    {
        $this->query->setType(Query::DELETE);
        $this->query->setFrom($table);
        return $this;
    }

    public function getSQL(): string
    {
        return $this->compiler->getSQL($this->query);
    }

    public function __toString()
    {
        return $this->getSQL();
    }
}