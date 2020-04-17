<?php

namespace Amber\Components\QueryBuilder;

class QueryBuilder
{
    public static function select(...$columns)
    {
        return (new SelectBuilder())->columns($columns);
    }

    public static function insert($table = '', $values = [])
    {
        return (new InsertBuilder())->table($table)->values($values);
    }

    public static function update($table = '', $values = [])
    {
        return (new UpdateBuilder())->table($table)->values($values);
    }

    public static function delete($table = '')
    {
        return (new DeleteBuilder())->table($table);
    }
}