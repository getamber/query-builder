<?php

use Amber\Components\QueryBuilder\InsertBuilder;
use PHPUnit\Framework\TestCase;

class InsertBuilderTest extends TestCase
{
    public function testInsert()
    {
        $insert = new InsertBuilder();
        $insert->table('users')
            ->values([
                'username' => '?',
                'forename' => '?',
                'surname' => '?',
                'email' => '?',
            ]);
        $this->assertEquals('INSERT INTO users (username,forename,surname,email) VALUES (?,?,?,?)', (string) $insert);
    }
}