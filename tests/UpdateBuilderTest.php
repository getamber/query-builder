<?php

use Amber\Components\QueryBuilder\UpdateBuilder;
use PHPUnit\Framework\TestCase;

class UpdateBuilderTest extends TestCase
{
    public function testUpdate()
    {
        $update = new UpdateBuilder();
        $update->table('users')
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