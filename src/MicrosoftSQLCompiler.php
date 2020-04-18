<?php

namespace Amber\Components\QueryBuilder;

class MicrosoftSQLCompiler extends QueryCompiler
{
    protected function getSQLForLimitClause($limit, $offset): string
    {
        $sql = [];

        if ($limit || $offset > 0) {
            $sql[] = 'OFFSET '.$offset.' ROWS';
        }

        if ($limit) {
            $sql[] = 'FETCH NEXT '.$limit.' ROWS ONLY';
        }

        return join(' ', $sql);
    }
}