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
        $this->query->select = [];
        $this->query->type = Query::SELECT;
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
        array_push($this->query->select, ...$columns);        
        return $this;
    }

    public function distinct(bool $distinct = true): self
    {
        $this->query->distinct = $distinct;
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

    protected function addJoin($table, $condition, $type)
    {
        if ($table instanceof Closure) {
            $query = new static($this->compiler, $table);
            $table = '('.$query.')'.($query->alias ? ' AS '.$query->alias : '');
        }

        if ($condition instanceof Closure) {
            $query = new static($this->compiler, $condition);
            $condition = '('.$query.')';
        }

        $this->query->addJoin($table, $condition, $type);
    }

    public function join($table, $condition): self
    {
        $this->addJoin($table, $condition, 'JOIN');
        return $this;
    }

    public function leftJoin($table, $condition): self
    {
        $this->addJoin($table, $condition, 'LEFT JOIN');
        return $this;
    }

    public function rightJoin($table, $condition): self
    {
        $this->addJoin($table, $condition, 'RIGHT JOIN');
        return $this;
    }

    public function crossJoin($table): self
    {
        $this->addJoin($table, null, 'CROSS JOIN');
        return $this;
    }

    protected function addWhere($condition, $operator, $append = true)
    {
        if ($condition instanceof Closure) {
            $query = new static($this->compiler, $condition);
            $condition = '('.$query.')';
        }

        $this->query->addWhere($condition, $operator, $append);
    }

    public function where($condition): self
    {
        $this->addWhere($condition, null, false);
        return $this;
    }

    public function whereNot($condition): self
    {
        $this->addWhere($condition, 'NOT', false);
        return $this;
    }

    public function andWhere($condition): self
    {
        $this->addWhere($condition, 'AND');
        return $this;
    }

    public function andWhereNot($condition): self
    {
        $this->addWhere($condition, 'AND NOT');
        return $this;
    }

    public function orWhere($condition): self
    {
        $this->addWhere($condition, 'OR');
        return $this;
    }

    public function orWhereNot($condition): self
    {
        $this->addWhere($condition, 'OR NOT');
        return $this;
    }

    public function whereExists(Closure $builder): self
    {
        $this->addWhere($builder, 'EXISTS', false);
        return $this;
    }

    public function whereNotExists(Closure $builder): self
    {
        $this->addWhere($builder, 'NOT EXISTS', false);
        return $this;
    }

    public function andWhereExists(Closure $builder): self
    {
        $this->addWhere($builder, 'AND EXISTS');
        return $this;
    }

    public function andWhereNotExists(Closure $builder): self
    {
        $this->addWhere($builder, 'AND NOT EXISTS');
        return $this;
    }

    public function orWhereExists(Closure $builder): self
    {
        $this->addWhere($builder, 'OR EXISTS');
        return $this;
    }

    public function orWhereNotExists(Closure $builder)
    {
        $this->addWhere($builder, 'OR NOT EXISTS');
        return $this;
    }

    public function orderBy(string $column, string $sort = self::SORT_ASC): self
    {
        $this->query->orderBy = [];
        return $this->addOrderBy($column, $sort);
    }

    public function addOrderBy(string $column, string $sort = self::SORT_ASC): self
    {
        $this->query->addOrderBy($column, $sort);
        return $this;
    }

    public function groupBy($column): self
    {
        $this->query->addGroupBy($column, false);
        return $this;
    }

    public function addGroupBy($column): self
    {
        $this->query->addGroupBy($column);
        return $this;
    }

    protected function addHaving($condition, $operator, $append = true)
    {
        if ($condition instanceof Closure) {
            $query = new static($this->compiler, $condition);
            $condition = '('.$query.')';
        }

        $this->query->addHaving($condition, $operator);
    }

    public function having($condition): self
    {
        $this->addHaving($condition, null, false);
        return $this;
    }

    public function notHaving($condition): self
    {
        $this->addHaving($condition, 'NOT', false);
        return $this;
    }

    public function andHaving($condition): self
    {
        $this->addHaving($condition, 'AND');
        return $this;
    }

    public function andNotHaving($condition): self
    {
        $this->addHaving($condition, 'AND NOT');
        return $this;
    }

    public function orHaving($condition): self
    {
        $this->addHaving($condition, 'OR');
        return $this;
    }

    public function orNotHaving($condition): self
    {
        $this->addHaving($condition, 'OR NOT');
        return $this;
    }

    public function limit(int $limit): self
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
        $this->query->type = Query::INSERT;
        $this->query->from = $table;
        $this->values($values);
        return $this;
    }

    public function update(string $table, $values = []): self
    {
        $this->query->type = Query::UPDATE;
        $this->query->from = $table;
        $this->values($values);
        return $this;
    }

    public function values(array $values): self
    {
        $this->query->values = array_merge($this->query->values, $values);
        return $this;
    }

    public function delete(string $table)
    {
        $this->query->type = Query::DELETE;
        $this->query->from = $table;
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