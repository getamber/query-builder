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
    const SELECT = 'SELECT';
    const INSERT = 'INSERT';
    const UPDATE = 'UPDATE';
    const DELETE = 'DELETE';

    const SORT_ASC  = 'ASC';
    const SORT_DESC = 'DESC';

    protected $compiler;

    protected $type;
    protected $alias;

    protected $select   = [];
    protected $distinct = false;
    protected $from     = null;
    protected $join     = [];
    protected $where    = [];
    protected $orderBy  = [];
    protected $groupBy  = [];
    protected $having   = [];
    protected $limit    = null;
    protected $offset   = 0;
    protected $values   = [];

    /**
     * Initialises a new QueryBuilder
     * 
     * @param Closure $build
     * @param string  $compiler
     */
    public function __construct(QueryCompiler $compiler = null, Closure $build = null)
    {
        $this->compiler = $compiler ?? new QueryCompiler();

        if ($build) {
            $this->alias = $build($this);
        }
    }

    /**
     * Gets the query type
     * 
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Starts a select query.
     * 
     * @param $columns
     * 
     * @return self
     */
    public function select($columns): self
    {
        $this->select = [];
        return $this->addSelect(func_get_args());
    }

    /**
     * Adds extra columns to a select query.
     * 
     * @param $columns
     * 
     * @return self
     */
    public function addSelect($columns): self
    {
        $this->type = self::SELECT;
        $columns = is_array($columns) ? $columns : func_get_args();
        $columns = array_map(function ($column) {
            if ($column instanceof Closure) {
                $query = new static($this->compiler, $column);
                return '('.$query.')'.($query->alias ? ' AS '.$query->alias : '');
            } else {
                return $column;
            }
        }, $columns);
        
        array_push($this->select, ...$columns);
        return $this;
    }

    public function getSelect(): array
    {
        return $this->select;
    }

    /**
     * Sets whether a select query should return distinct rows.
     * 
     * @param bool $distinct
     * 
     * @return self
     */
    public function distinct(bool $distinct = true): self
    {
        $this->distinct = $distinct;
        return $this;
    }

    public function isDistinct(): bool
    {
        return $this->distinct;
    }

    /**
     * Sets the from clause of a select query.
     * 
     * @param $from
     * 
     * @return self
     */
    public function from($from): self
    {
        if ($from instanceof Closure) {
            $query = new static($this->compiler, $from);
            $from = '('.$query.')'.($query->alias ? ' AS '.$query->alias : '');
        }

        $this->from = $from;
        return $this;
    }

    public function getFrom(): ?string
    {
        return $this->from;
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

        $this->join[] = [$join, $table, $on];
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

    public function getJoin(): array
    {
        return $this->join;
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
        
        $this->where[] = $conditions;
    }

    public function where(...$conditions): self
    {
        $this->where = [];
        $this->addWhere(...$conditions);
        return $this;
    }

    public function whereNot(...$conditions): self
    {
        $this->where = [];
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
        $this->where = [];
        $this->addWhere('EXISTS', $builder);
        return $this;
    }

    public function whereNotExists(Closure $builder): self
    {
        $this->where = [];
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

    public function getWhere(): array
    {
        return $this->where;
    }

    public function orderBy(string $column, string $sort = self::SORT_ASC): self
    {
        $this->orderBy = [];
        return $this->addOrderBy($column, $sort);
    }

    public function addOrderBy(string $column, string $sort = self::SORT_ASC): self
    {
        $this->orderBy[] = [$column, $sort];
        return $this;
    }

    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    public function groupBy($column): self
    {
        $this->groupBy = [];
        $this->addGroupBy($column, false);
        return $this;
    }

    public function addGroupBy($column): self
    {
        $this->groupBy[] = $column;
        return $this;
    }

    public function getGroupBy(): array
    {
        return $this->groupBy;
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

        array_push($this->having, ...$conditions);
    }

    public function having(...$conditions): self
    {
        $this->having = [];
        $this->addHaving(...$conditions);
        return $this;
    }

    public function notHaving(...$conditions): self
    {
        $this->having = [];
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

    public function getHaving(): array
    {
        return $this->having;
    }

    public function limit(?int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function insert(string $table, $values = [])
    {
        $this->type = self::INSERT;
        $this->from($table);
        $this->values = $values;
        return $this;
    }

    /**
     * Starts an update query.
     * 
     * @param string $table
     * @param array  $values Array with column names as keys.
     * 
     * @return self
     */
    public function update(string $table, $values = []): self
    {
        $this->type = self::UPDATE;
        $this->from($table);
        $this->values = $values;
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
        $this->values = array_merge($this->values, $values);
        return $this;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * Starts building a delete query.
     * 
     * @parameter string $table
     * @return QueryBuilder
     */
    public function delete(string $table)
    {
        $this->type = self::DELETE;
        $this->from($table);
        return $this;
    }

    /**
     * Gets the SQL for the query.
     * 
     * @return string
     */
    public function getSQL(): string
    {
        return $this->compiler->getSQL($this);
    }

    public function __toString()
    {
        return $this->getSQL();
    }
}