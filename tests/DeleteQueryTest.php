<?php

use Amber\Components\QueryBuilder\QueryBuilder;
use PHPUnit\Framework\TestCase;

class DeleteQueryTest extends TestCase
{
    public function testDelete()
    {
        $delete = new QueryBuilder();
        $delete->delete('users')
            ->where('username = ?');
        $this->assertEquals('DELETE FROM users WHERE username = ?', (string) $delete);
    }
}