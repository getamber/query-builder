<?php

namespace Amber\Components\QueryBuilder;

use Closure;
use InvalidArgumentException;

/**
 * Fluent SQL query builder.
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

    const JOIN_INNER = 'INNER JOIN';
    const JOIN_LEFT  = 'LEFT JOIN';
    const JOIN_RIGHT = 'RIGHT JOIN';
    const JOIN_CROSS = 'CROSS JOIN';

    const SORT_ASC  = 'ASC';
    const SORT_DESC = 'DESC';

    protected $compiler;

    protected $type;
    protected $select   = [];
    protected $from     = null;
    protected $joins    = [];
    protected $where    = [];
    protected $unions   = [];
    protected $orderBy  = [];
    protected $groupBy  = [];
    protected $having   = [];
    protected $limit    = null;
    protected $offset   = 0;
    protected $distinct = false;
    protected $subquery = false;
    protected $columns  = [];
    protected $values   = [];
    protected $withs    = [];

    /**
     * Initialises a new QueryBuilder
     * 
     * @param QueryCompiler|null $compiler
     */
    public function __construct(QueryCompiler $compiler = null) 
    {
        $this->compiler = $compiler ?? new QueryCompiler();
    }

    /**
     * Create a new QueryBuilder from a closure
     * 
     * @param Closure $closure
     * @param bool    $subquery
     * @return self
     */
    protected function newFromClosure(Closure $closure, bool $subquery): self
    {
        $query = new static($this->compiler);
        $query->subquery = $subquery;
        $query->alias = $closure($query);

        return $query;
    }

    /**
     * Gets the query type.
     * 
     * @return string|null The query type.
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Sets the query as a subquery with optional alias.
     * 
     * @param string $alias
     * @param return self
     */
    public function subquery($alias = null)
    {
        $this->subquery = $alias ?? true;
    }

    /**
     * Checks whether or not this is a subquery.
     * 
     * @return bool
     */
    public function isSubquery(): bool
    {
        return $this->subquery != null;
    }

    /**
     * Gets the subquery alias.
     * 
     * @return string
     */
    public function getAlias(): ?string
    {
        return is_string($this->subquery) ? $this->subquery : null;
    }

    /**
     * Sets the query to return distinct rows.
     * 
     * @return self
     */
    public function distinct(): self
    {
        $this->distinct = true;
        return $this;
    }

    /**
     * Checks whether or not the query returns distinct rows.
     * 
     * @return bool
     */
    public function isDistinct(): bool
    {
        return $this->distinct;
    }

    /**
     * Sets the columns for a select.
     * 
     * @param mixed $columns
     * @return self
     */
    public function select($columns): self
    {
        $this->columns = [];
        $this->addSelect(...func_get_args());
        return $this;
    }

    /**
     * Adds columns to a select.
     * 
     * @param string|Closure $columns
     * @return self
     */
    public function addSelect($columns): self
    {
        $this->type = self::SELECT;
        $columns = is_array($columns) ? $columns : func_get_args();
        $columns = array_map(function ($column) {
            return $column instanceof Closure ? $this->newFromClosure($column, true) : $column;
        }, $columns);
        
        array_push($this->select, ...$columns);
        return $this;
    }

    /**
     * Gets the selected columns.
     * 
     * @return array
     */
    public function getSelect(): array
    {
        return $this->select;
    }

    /**
     * Sets the from clause.
     * 
     * @param string|Closure $from
     * @return self
     */
    public function from($from): self
    {
        $this->from = $from instanceof Closure ? $this->newFromClosure($from, true) : $from;
        return $this;
    }

    /**
     * Gets the from clause.
     * 
     * @return string
     */
    public function getFrom(): ?string
    {
        return $this->from;
    }

    /**
     * Adds a join clause.
     * 
     * @param string         $type  The type of join.
     * @param string|Closure $table The table or subquery to join with.
     * @param string|Closure $on    The on condition of the join.
     * @return self
     */
    public function join(string $type, $table, $on = null): self
    {
        $table = $table instanceof Closure ? $this->newFromClosure($table, true) : $table;
        $on = $on instanceof Closure ? $this->newFromClosure($on, true) : $on;

        $this->joins[] = [$type, $table, $on];
        return $this;
    }

    /**
     * Gets the join clauses.
     * 
     * @return array
     */
    public function getJoins(): array
    {
        return $this->joins;
    }

    /**
     * Sets the where clause.
     * 
     * @param string|Closure $condition
     */
    public function where($condition): self
    {
        $this->where = array_map(function ($condition) {
            return $condition instanceof Closure ? $this->newFromClosure($condition, true) : $condition;
        }, func_get_args());
        
        return $this;
    }

    /**
     * Gets the where clause.
     * 
     * @return array
     */
    public function getWhere(): array
    {
        return $this->where;
    }

    /**
     * Adds a union clause.
     * 
     * @param Closure $query
     * @param bool    $all
     * @return self
     */
    public function union(Closure $query, $all = false): self
    {
        $this->unions[] = [$this->newFromClosure($query, false), $all];
        return $this;
    }

    /**
     * Gets the union clauses.
     * 
     * @return array
     */
    public function getUnions()
    {
        return $this->unions;
    }

    /**
     * Sets the order by clause.
     * 
     * @param string|array $column
     * @param string       $sort
     * @return self
     */
    public function orderBy($column, string $sort = self::SORT_ASC): self
    {
        $this->orderBy = [];
        $this->addOrderBy(...func_get_args());
        return $this;
    }

    /**
     * Adds to the order by clause.
     */
    public function addOrderBy($column, string $sort = self::SORT_ASC): self
    {
        $orderBy = is_array($column) ? $column : [$column => $sort];

        foreach ($orderBy as $column => $sort) {
            $this->orderBy[] = [$column, $sort];
        }

        return $this;
    }

    /**
     * Gets the order by clause.
     * 
     * @return array
     */
    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    /**
     * Sets the group by clause.
     * 
     * @param string|array $column
     * @return self
     */
    public function groupBy($column): self
    {
        $this->groupBy = [];
        $this->addGroupBy(...func_get_args());
        return $this;
    }

    /**
     * Adds to the group by clause.
     * 
     * @param string|array $column
     * @return self
     */
    public function addGroupBy($column): self
    {
        $groupBy = is_array($column) ? $column : [$column];
        array_push($this->groupBy, ...$groupBy);
        return $this;
    }

    /**
     * Gets the group by clause.
     * 
     * @return array
     */
    public function getGroupBy(): array
    {
        return $this->groupBy;
    }

    /**
     * Sets the having clause.
     * 
     * @param string|Closure $condition
     */
    public function having($condition): self
    {
        $this->having = array_map(function ($condition) {
            return $condition instanceof Closure ? $this->newFromClosure($condition, true) : $condition;
        }, func_get_args());

        return $this;
    }

    /**
     * Gets the having clause.
     * 
     * @return array
     */
    public function getHaving(): array
    {
        return $this->having;
    }

    /**
     * Sets the maximum number of records returned for a select.
     * 
     * @param int|null $limit The maximum number of records or null for no limit.
     * @return self
     */
    public function limit(?int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Gets the maximum number of records.
     * 
     * @return int|null
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * Sets the offset for a select.
     * 
     * @param int $offset
     * @return self
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Gets the offset for a select.
     * 
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * Sets the table for an insert.
     * 
     * @param string $table
     * @return self
     */
    public function insert(string $table): self
    {
        $this->type = self::INSERT;
        $this->from($table);
        return $this;
    }

    /**
     * Sets the table for an update.
     * 
     * @param string $table
     * @return self
     */
    public function update(string $table): self
    {
        $this->type = self::UPDATE;
        $this->from($table);
        return $this;
    }

    /**
     * Sets the table for a delete.
     * 
     * @param string $table
     * @return QueryBuilder
     */
    public function delete(string $table)
    {
        $this->type = self::DELETE;
        $this->from($table);
        return $this;
    }

    /**
     * Sets the columns for an insert.
     * 
     * @param array $columns
     * @return self
     */
    public function columns($columns): self
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    /**
     * Gets the columns for an insert query.
     * 
     * @return array
     */
    public function getColumns(): array
    {
        if ($this->columns) {
            return $this->columns;
        } elseif (is_array($this->values)) {
            return array_filter(array_keys($this->values), 'is_string');
        }
        
        return [];
    }

    /**
     * Sets the values for an insert.
     * 
     * @param array $values Key value pairs of columns and values or a closure.
     * @return self
     */
    public function values($values): self
    {
        $this->values = is_array($values) ? $values : func_get_args();
        return $this;
    }

    /**
     * Sets the values for an update.
     * 
     * @param array $values
     * @return self
     */
    public function set(array $values): self
    {
        $this->values = [];

        foreach ($values as $column => $value) {
            $this->values[$column] = $value;
        }

        return $this;
    }

    /**
     * Gets the values for an update or an insert.
     * 
     * @return array|QueryBuilder
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * Adds a with clause.
     * 
     * @param string  $name
     * @param Closure $query
     * @param array   $columns
     * @return self
     */
    public function with(string $name, Closure $query, $columns = []): self
    {
        $this->withs[$name] = [$this->newFromClosure($query, true), $columns];
        return $this;
    }

    /**
     * Gets the with clauses of the query.
     * 
     * @return array
     */
    public function getWiths(): array
    {
        return $this->withs;
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