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

    protected $select = [];
    protected $distinct = false;
    protected $from;
    protected $join;
    protected $where;
    protected $groupBy = [];
    protected $having;
    protected $orderBy = [];
    protected $limit;
    protected $values = [];

    protected $alias;

    public function __construct(Closure $build = null)
    {
        $this->join   = new JoinBuilder();
        $this->where  = new ConditionBuilder();
        $this->having = new ConditionBuilder();
        $this->limit  = new LimitBuilder();

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

        $this->join->addJoin($table, $alias, $condition, $type);
    }

    public function join($table, string $alias, $condition): self
    {
        $this->addJoin($table, $alias, $condition, JoinBuilder::INNER_JOIN);
        return $this;
    }

    public function leftJoin($table, string $alias, $condition): self
    {
        $this->addJoin($table, $alias, $condition, JoinBuilder::LEFT_JOIN);
        return $this;
    }

    public function rightJoin($table, string $alias, $condition): self
    {
        $this->addJoin($table, $alias, $condition, JoinBuilder::RIGHT_JOIN);
        return $this;
    }

    public function crossJoin($table, string $alias): self
    {
        $this->addJoin($table, $alias, null, JoinBuilder::CROSS_JOIN);
        return $this;
    }

    protected function addWhere($condition, $operator)
    {
        if ($condition instanceof Closure) {
            $query = new static($condition);
            $condition = '('.$query.')';
        }

        $this->where->addCondition($condition, $operator);
    }

    public function where($condition): self
    {
        $this->where = new ConditionBuilder();
        $this->addWhere($condition, null);
        return $this;
    }

    public function andWhere($condition): self
    {
        $this->addWhere($condition, ConditionBuilder::AND);
        return $this;
    }

    public function orWhere($condition): self
    {
        $this->addWhere($condition, ConditionBuilder::OR);
        return $this;
    }

    protected function addExists(Closure $builder, $operator)
    {
        $query = new static($builder);
        $this->addWhere('EXISTS ('.$query.')', $operator);
    }

    public function whereExists(Closure $builder): self
    {
        $this->where = new ConditionBuilder();
        $this->addExists($builder, null);
        return $this;
    }

    public function andWhereExists(Closure $builder)
    {
        $this->addExists($builder, ConditionBuilder::AND);
        return $this;
    }

    public function orWhereExists(Closure $builder)
    {
        $this->addExists($builder, ConditionBuilder::OR);
        return $this;
    }

    public function whereNotExists(Closure $builder): self
    {
        $this->where = new ConditionBuilder();
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

        $this->having->addCondition($condition, $operator);
    }

    public function having($condition): self
    {
        $this->having = new ConditionBuilder();
        $this->addHaving($condition, null);
        return $this;
    }

    public function andHaving($condition): self
    {
        $this->addHaving($condition, ConditionBuilder::AND);
        return $this;
    }

    public function orHaving($condition): self
    {
        $this->addHaving($condition, ConditionBuilder::OR);
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
                return $this->where;
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

        $sql[] = (string) $this->join;

        if ($this->where->hasConditions()) {
            $sql[] = 'WHERE '.$this->where;
        }

        if ($this->groupBy) {
            $sql[] = 'GROUP BY '.join(',', $this->groupBy);

            if ($this->having) {
                $sql[] = 'HAVING '.$this->having;
            }
        }

        if ($this->orderBy) {
            $sql[] = 'ORDER BY '.join(',', $this->orderBy);
        }

        $sql[] = (string) $this->limit;

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
        return sprintf('UPDATE %s SET %s WHERE %s',
            $this->from,
            join(',', array_map(function ($column, $value) {
                return $column.'='.$value;
            }, array_keys($this->values), $this->values)),
            $this->where->hasConditions() ? $this->where : 1
        );
    }

    protected function getSQLForDelete(): string
    {
        return sprintf('DELETE FROM %s WHERE %s',
            $this->from,
            $this->where->hasConditions() ? $this->where : 1
        );
    }

    public function __toString()
    {
        return $this->getSQL();
    }
}