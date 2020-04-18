<?php

namespace Amber\Components\QueryBuilder;

use Closure;

class QueryBuilder
{
    const TYPE_SELECT = 'SELECT';
    const TYPE_INSERT = 'INSERT';
    const TYPE_UPDATE = 'UPDATE';
    const TYPE_DELETE = 'DELETE';

    const SORT_ASC  = 'ASC';
    const SORT_DESC = 'DESC';

    protected $type;

    protected $select   = [];
    protected $distinct = false;
    protected $from     = null;
    protected $join     = [];
    protected $where    = [];
    protected $groupBy  = [];
    protected $having   = [];
    protected $orderBy  = [];
    protected $limit    = null;
    protected $offset   = null;
    protected $values   = [];

    protected $alias;

    public function __construct(Closure $build = null)
    {
        if ($build) {
            $this->alias = $build($this);
        }
    }

    public function select($columns): self
    {
        $this->select = [];
        $this->type = self::TYPE_SELECT;
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
        array_push($this->select, ...$columns);        
        return $this;
    }

    public function distinct(bool $distinct = true): self
    {
        $this->distinct = $distinct;
        return $this;
    }

    public function from($from, string $alias = null): self
    {
        if ($from instanceof Closure) {
            $query = new static($from);
            $from = '('.$query.') '.$query->alias;
        }

        $this->from = trim($from.' '.$alias);
        return $this;
    }

    protected function addJoin($table, string $alias, $condition, $type)
    {
        if ($table instanceof Closure) {
            $query = new static($table);
            $table = '('.$query.')';
        }

        if ($condition instanceof Closure) {
            $query = new static($condition);
            $condition = '('.$query.')';
        }

        $table = trim($table.' '.$alias);
        $join = $type.' '.$table;

        if ($condition) {
            $join .= ' ON '.$condition;
        }

        $this->join[] = $join;
    }

    public function join($table, string $alias, $condition): self
    {
        $this->addJoin($table, $alias, $condition, 'JOIN');
        return $this;
    }

    public function leftJoin($table, string $alias, $condition): self
    {
        $this->addJoin($table, $alias, $condition, 'LEFT JOIN');
        return $this;
    }

    public function rightJoin($table, string $alias, $condition): self
    {
        $this->addJoin($table, $alias, $condition, 'RIGHT JOIN');
        return $this;
    }

    public function crossJoin($table, string $alias): self
    {
        $this->addJoin($table, $alias, null, 'CROSS JOIN');
        return $this;
    }

    protected function addWhere($condition, $operator)
    {
        if ($condition instanceof Closure) {
            $query = new static($condition);
            $condition = '('.$query.')';
        }

        $this->where[] = trim($operator.' '.$condition);
    }

    public function where($condition): self
    {
        $this->where = [];
        $this->addWhere($condition, null);
        return $this;
    }

    public function andWhere($condition): self
    {
        $this->addWhere($condition, 'AND');
        return $this;
    }

    public function orWhere($condition): self
    {
        $this->addWhere($condition, 'OR');
        return $this;
    }

    protected function addExists(Closure $builder, $operator)
    {
        $query = new static($builder);
        $this->addWhere('EXISTS ('.$query.')', $operator);
    }

    public function whereExists(Closure $builder): self
    {
        $this->where = [];
        $this->addExists($builder, null);
        return $this;
    }

    public function andWhereExists(Closure $builder)
    {
        $this->addExists($builder, 'AND');
        return $this;
    }

    public function orWhereExists(Closure $builder)
    {
        $this->addExists($builder, 'OR');
        return $this;
    }

    public function whereNotExists(Closure $builder): self
    {
        $this->where = [];
        $this->addExists($builder, 'NOT');
        return $this;
    }

    public function andWhereNotExists(Closure $builder)
    {
        $this->addExists($builder, 'AND NOT');
        return $this;
    }

    public function orWhereNotExists(Closure $builder)
    {
        $this->addExists($builder, 'OR NOT');
        return $this;
    }

    public function orderBy(string $column, string $sort = self::SORT_ASC): self
    {
        $this->orderBy = [];
        return $this->addOrderBy($column, $sort);
    }

    public function addOrderBy(string $column, string $sort = self::SORT_ASC): self
    {
        $this->orderBy[] = $column.' '.$sort;
        return $this;
    }

    public function groupBy($column): self
    {
        $this->groupBy = [];
        return $this->addGroupBy($column);
    }

    public function addGroupBy($column): self
    {
        $this->groupBy[] = $column;
        return $this;
    }

    protected function addHaving($condition, $operator)
    {
        if ($condition instanceof Closure) {
            $query = new static($condition);
            $condition = '('.$query.')';
        }

        $this->having[] = trim($operator.' '.$condition);
    }

    public function having($condition): self
    {
        $this->having = [];
        $this->addHaving($condition, null);
        return $this;
    }

    public function andHaving($condition): self
    {
        $this->addHaving($condition, 'AND');
        return $this;
    }

    public function orHaving($condition): self
    {
        $this->addHaving($condition, 'OR');
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit->setLimit($limit);
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->limit->setOffset($offset);
        return $this;
    }

    public function insert(string $table, $values = [])
    {
        $this->type = self::TYPE_INSERT;
        $this->from = $table;
        $this->values($values);
        return $this;
    }

    public function update(string $table, $values = []): self
    {
        $this->type = self::TYPE_UPDATE;
        $this->from = $table;
        $this->values($values);
        return $this;
    }

    public function values(array $values): self
    {
        $this->values = array_merge($this->values, $values);
        return $this;
    }

    public function delete(string $table)
    {
        $this->type = self::TYPE_DELETE;
        $this->from = $table;
        return $this;
    }

    public function getSQL(): string
    {
        switch ($this->type) {
            case self::TYPE_SELECT:
                return $this->getSQLForSelect();

            case self::TYPE_INSERT:
                return $this->getSQLForInsert();
        
            case self::TYPE_UPDATE:
                return $this->getSQLForUpdate();

            case self::TYPE_DELETE:
                return $this->getSQLForDelete();
            
            default:
                return join(' ', $this->where);
        }
    }

    protected function getSQLForSelect(): string
    {
        $sql = ['SELECT'];

        if ($this->distinct) {
            $sql[] = 'DISTINCT';
        }

        $sql[] = join(',', $this->select);
        
        if ($this->from) {
            $sql[] = 'FROM '.$this->from;
        }

        $sql[] = $this->getSQLForJoins();
        $sql[] = $this->getSQLForWhereClause();
        $sql[] = $this->getSQLForGroupByClause();
        $sql[] = $this->getSQLForOrderByClause();
        $sql[] = $this->getSQLForLimitClause();

        return join(' ', array_filter($sql));
    }

    protected function getSQLForInsert(): string
    {
        return sprintf('INSERT INTO %s (%s) VALUES (%s)',
            $this->from,
            join(',', array_keys($this->values)),
            join(',', $this->values)
        );
    }

    protected function getSQLForUpdate(): string
    {
        return sprintf('UPDATE %s SET %s %s',
            $this->from,
            join(',', array_map(function ($column, $value) {
                return $column.'='.$value;
            }, array_keys($this->values), $this->values)),
            $this->getSQLForWhereClause()
        );
    }

    protected function getSQLForDelete(): string
    {
        return sprintf('DELETE FROM %s %s',
            $this->from,
            $this->getSQLForWhereClause()
        );
    }

    protected function getSQLForJoins()
    {
        return join(' ', $this->join);
    }

    protected function getSQLForWhereClause()
    {
        if (!$this->where) {
            return;
        }

        return 'WHERE '.join(' ', $this->where);
    }

    protected function getSQLForOrderByClause()
    {
        if (!$this->orderBy) {
            return;
        }

        return 'ORDER BY '.join(',', $this->orderBy);
    }

    protected function getSQLForGroupByClause()
    {
        if (!$this->groupBy) {
            return;
        }

        $sql = 'GROUP BY '.join(' ', $this->groupBy);

        if ($this->having) {
            $sql .= ' HAVING '.join(',', $this->having);
        }

        return $sql;
    }

    protected function getSQLForLimitClause()
    {
        $query = [];

        if ($this->limit) {
            $query[] = 'LIMIT '.$this->limit;
        }

        if ($this->offset) {
            $query[] = 'OFFSET '.$this->offset;
        }

        return join(' ', $query);
    }

    public function __toString()
    {
        return $this->getSQL();
    }
}