<?php

use Amber\Components\QueryBuilder\QueryBuilder;
use PHPUnit\Framework\TestCase;

class UpdateQueryTest extends TestCase
{
    public function testUpdate()
    {
        $update = new QueryBuilder();
        $update->update('users')
            ->values([
                'forename' => '?',
                'surname' => '?',
                'email' => '?',
            ])
            ->where('username = ?')
        ;
        $this->assertEquals('UPDATE users SET forename=?,surname=?,email=? WHERE username = ?', (string) $update);
    }
}