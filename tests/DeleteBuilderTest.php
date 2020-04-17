<?php

use Amber\Components\QueryBuilder\DeleteBuilder;
use PHPUnit\Framework\TestCase;

class DeleteBuilderTest extends TestCase
{
    public function testDelete()
    {
        $delete = new DeleteBuilder();
        $delete->table('users')
            ->where('username = ?');
        $this->assertEquals('DELETE FROM users WHERE username = ?', (string) $delete);
    }
}