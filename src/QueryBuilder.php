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

    const SORT_ASC  = 'ASC';
    const SORT_DESC = 'DESC';

    protected $compiler;

    protected $type;
    protected $subquery = false;
    protected $alias    = null;

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
    protected $columns  = [];
    protected $values   = [];

    /**
     * Creates a new QueryBuilder from a closure.
     * 
     * @param QueryCompiler $compiler
     * @param Closure       $closure  A closure to build the query and return an alias.
     * @param bool          $subquery Whether or not to create a subquery.
     */
    protected static function createFromClosure(QueryCompiler $compiler, Closure $closure, $subquery = false)
    {
        $query = new static($compiler);
        $query->setSubquery($subquery, $closure($query));

        return $query;
    }

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
     * Gets the query type.
     * 
     * @return string The query type.
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets whether or not the query is a subquery.
     * 
     * @param bool   $subquery
     * @param string $alias
     * @return self
     */
    public function setSubquery(bool $subquery, string $alias = null): self
    {
        $this->subquery = $subquery;
        $this->alias = $alias;

        return $this;
    }

    /**
     * Checks whether or not the query is a subquery.
     * 
     * @return bool
     */
    public function isSubquery()
    {
        return $this->subquery;
    }

    /**
     * Gets the subquery alias.
     * 
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Starts building a select query. This method replaces the existing select clause.
     * 
     * @param mixed $columns
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
     * @param mixed $columns
     * @return self
     */
    public function addSelect($columns): self
    {
        $this->type = self::SELECT;
        $columns = is_array($columns) ? $columns : func_get_args();
        $columns = array_map(function ($column) {
            return $column instanceof Closure ? static::createFromClosure($this->compiler, $column, true) : $column;
        }, $columns);
        
        array_push($this->select, ...$columns);
        return $this;
    }

    /**
     * Gets the columns of a select query.
     * 
     * @return array
     */
    public function getSelect(): array
    {
        return $this->select;
    }

    /**
     * Sets whether or not a select query returns distinct rows.
     * 
     * @param bool $distinct
     * @return self
     */
    public function distinct(bool $distinct = true): self
    {
        $this->distinct = $distinct;
        return $this;
    }

    /**
     * Checks whether or not a select query returns distinct rows.
     * 
     * @return bool
     */
    public function isDistinct(): bool
    {
        return $this->distinct;
    }

    /**
     * Sets the from clause of a select query.
     * 
     * @param string|Closure $from
     * @return self
     */
    public function from($from): self
    {
        $this->from = $from instanceof Closure ? static::createFromClosure($this->compiler, $from, true) : $from;

        return $this;
    }

    /**
     * Gets the from clause of a select query.
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
     * @param string         $join  The type of join.
     * @param string|Closure $table The table or subquery to join with.
     * @param string|Closure $on    The on condition of the join.
     */
    protected function addJoin(string $join, $table, $on = null)
    {
        $table = $table instanceof Closure ? static::createFromClosure($this->compiler, $table, true) : $table;
        $on = $on instanceof Closure ? static::createFromClosure($this->compiler, $on, true) : $on;

        $this->join[] = [$join, $table, $on];
    }

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

    /**
     * Gets the join clauses of a select query+.
     * 
     * @return array[]
     */
    public function getJoin(): array
    {
        return $this->join;
    }

    /**
     * Adds a where clause to a query.
     * 
     * @param string|Closure $condition
     */
    protected function addWhere($condition)
    {
        $condition = array_map(function ($part) {
            return $part instanceof Closure ? static::createFromClosure($this->compiler, $part, true) : $part;
        }, func_get_args());
        
        $this->where[] = $condition;
    }

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

    /**
     * Gets the where clause for the query.
     * 
     * @return (string|QueryBuilder)[]
     */
    public function getWhere(): array
    {
        return $this->where;
    }

    /**
     * Begins an order by clause for a select query. This method replaces the existing group by clause.
     * 
     * @param string $column
     * @param string $sort
     * @return self
     */
    public function orderBy(string $column, string $sort = self::SORT_ASC): self
    {
        $this->orderBy = [];
        $this->addOrderBy($column, $sort);
        return $this;
    }

    /**
     * Adds an order by clause to a select query.
     * 
     * @param string $column
     * @param string $sort
     * @return self
     */
    public function addOrderBy(string $column, string $sort = self::SORT_ASC): self
    {
        $this->orderBy[] = [$column, $sort];
        return $this;
    }

    /**
     * Gets the order by clause for a select query.
     * 
     * @return array[]
     */
    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    /**
     * Begins a group by clause for a select query. This method replaces the existing group by clause.
     * 
     * @param string $column
     * @return self
     */
    public function groupBy(string $column): self
    {
        $this->groupBy = [];
        $this->addGroupBy($column, false);
        return $this;
    }
    
    /**
     * Adds a column to the group by clause of a select query.
     * 
     * @param string $column
     * @return self
     */
    public function addGroupBy(string $column): self
    {
        $this->groupBy[] = $column;
        return $this;
    }

    /**
     * Gets the group by clause of a select query.
     * 
     * @return string[]
     */
    public function getGroupBy(): array
    {
        return $this->groupBy;
    }

    /**
     * Adds a having clause to a select query.
     * 
     * @param string|Closure $condition
     */
    protected function addHaving($condition)
    {
        $condition = array_map(function ($part) {
            return $part instanceof Closure ? static::createFromClosure($this->compiler, $part, true) : $part;
        }, func_get_args());

        array_push($this->having, ...func_get_args());
    }

    /**
     * Begins a having clause for a select query. This method replaces the existing having clause.
     */
    public function having($condition): self
    {
        $this->having = [];
        $this->addHaving(...func_get_args());
        return $this;
    }

    public function notHaving($condition): self
    {
        $this->having = [];
        $this->addHaving('NOT', ...func_get_args());
        return $this;
    }

    public function andHaving($condition): self
    {
        $this->addHaving('AND', ...func_get_args());
        return $this;
    }

    public function andNotHaving($condition): self
    {
        $this->addHaving('AND NOT', ...func_get_args());
        return $this;
    }

    public function orHaving($condition): self
    {
        $this->addHaving('OR', ...func_get_args());
        return $this;
    }

    public function orNotHaving($condition): self
    {
        $this->addHaving('OR NOT', ...func_get_args());
        return $this;
    }

    /**
     * Gets the having clause of a select query.
     * 
     * @return mixed[]
     */
    public function getHaving(): array
    {
        return $this->having;
    }

    /**
     * Sets the maximum number of records to be returned by a select query.
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
     * Sets the offset for a select query.
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
     * Gets the offset for a select query.
     * 
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * Starts building an insert query. This method resets or replaces the existing query.
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
     * Starts building an update query. This method resets or replaces the existing values.
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
     * Starts building a delete query.
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
     * Sets the columns for an insert query.
     * 
     * @param array $columns
     * @return self
     */
    public function columns($columns): self
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $this->columns = $columns;
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
     * Sets multiple values for an insert query.
     * 
     * @param array|Closure $values Key value pairs of columns and values.
     * @return self
     */
    public function values($values): self
    {
        if ($values instanceof Closure) {
            $this->values = static::createFromClosure($this->compiler, $values);
        } else {
            $this->values = is_array($values) ? $values : func_get_args();
        }

        return $this;
    }

    /**
     * Sets multiple values for an update query.
     * 
     * @param array $values
     * @return self
     */
    public function set(array $values): self
    {
        $this->values = [];

        foreach ($values as $column => $value) {
            $this->setValue($column, $value);
        }

        return $this;
    }

    /**
     * Sets an individual value for an insert or update query.
     * 
     * @param string         $column
     * @param string|Closure $value
     * @return self
     */
    public function setValue(string $column, $value)
    {
        $this->values[$column] = $value instanceof Closure ? static::createFromClosure($this->compiler, $value, true) : $value;
        return $this;
    }

    /**
     * Gets the values for an update or an insert query.
     * 
     * @return array|QueryBuilder
     */
    public function getValues()
    {
        return $this->values;
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