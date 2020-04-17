<?php

namespace Amber\Components\QueryBuilder;

class OrderBuilder extends QueryClause
{
    const SORT_ASC = 'ASC';
    const SORT_DESC = 'DESC';

    protected $orders = [];

    public function addOrder(string $column, string $sort = OrderBuilder::SORT_ASC)
    {
        $this->orders[] = $column.' '.$sort;
    }

    public function getSQL(): string
    {
        return $this->orders ? 'ORDER BY '.join(',', $this->orders) : '';
    }
}