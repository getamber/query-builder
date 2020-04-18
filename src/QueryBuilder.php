<?php

namespace Amber\Components\QueryBuilder;

use Closure;

class QueryBuilder
{
    const SORT_ASC  = 'ASC';
    const SORT_DESC = 'DESC';

    protected $query;
    protected $compiler;
    protected $alias;

    public function __construct(Closure $build = null, QueryCompiler $compiler = null)
    {
        $this->query    = new Query();
        $this->compiler = $compiler ?? new QueryCompiler($this->query);

        if ($build) {
            $this->alias = $build($this);
        }
    }

    public function select($columns): self
    {
        $this->query->select = [];
        $this->query->type = Query::TYPE_SELECT;
        return $this->addSelect(func_get_args());
    }

    public function addSelect($columns): self
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $columns = array_map(function ($column) {
            if ($column instanceof Closure) {
                $query = new static($column);
                return trim('('.$query.') '.$query->alias);
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
            $query = new static($from);
            $from = trim('('.$query.') '.$query->alias);
        }

        $this->query->setFrom($from);
        return $this;
    }

    protected function addJoin($table, $condition, $type)
    {
        if ($table instanceof Closure) {
            $query = new static($table);
            $table = trim('('.$query.') '.$query->alias);
        }

        if ($condition instanceof Closure) {
            $query = new static($condition);
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
            $query = new static($condition);
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

    protected function addExists(Closure $builder, $operator)
    {
        $query = new static($builder);
        $this->addWhere('EXISTS ('.$query.')', $operator);
    }

    public function whereExists(Closure $builder): self
    {
        $this->query->where = [];
        $this->addExists($builder, null);
        return $this;
    }

    public function whereNotExists(Closure $builder): self
    {
        $this->query->where = [];
        $this->addExists($builder, 'NOT');
        return $this;
    }

    public function andWhereExists(Closure $builder)
    {
        $this->addExists($builder, 'AND');
        return $this;
    }

    public function andWhereNotExists(Closure $builder)
    {
        $this->addExists($builder, 'AND NOT');
        return $this;
    }

    public function orWhereExists(Closure $builder)
    {
        $this->addExists($builder, 'OR');
        return $this;
    }

    public function orWhereNotExists(Closure $builder)
    {
        $this->addExists($builder, 'OR NOT');
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
            $query = new static($condition);
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
        $this->query->type = Query::TYPE_INSERT;
        $this->query->from = $table;
        $this->values($values);
        return $this;
    }

    public function update(string $table, $values = []): self
    {
        $this->query->type = Query::TYPE_UPDATE;
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
        $this->query->type = Query::TYPE_DELETE;
        $this->query->from = $table;
        return $this;
    }

    public function getSQL(): string
    {
        return $this->compiler->getSQL();
    }

    public function __toString()
    {
        return $this->getSQL();
    }
}