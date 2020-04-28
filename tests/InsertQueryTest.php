<?php

use Amber\Components\QueryBuilder\QueryBuilder;
use PHPUnit\Framework\TestCase;

class InsertQueryTest extends TestCase
{
    public function testInsert()
    {
        $insert = new QueryBuilder();
        $insert->insert('users')
            ->values([
                'username' => '?',
                'forename' => '?',
                'surname' => '?',
                'email' => '?',
            ]);
        $this->assertEquals('INSERT INTO users (username,forename,surname,email) VALUES (?,?,?,?)', (string) $insert);
    }

    public function testInsertSelect()
    {
        $insert = new QueryBuilder();
        $insert->insert('users')
            ->values(function ($query) {
                $query->select('*')->from('import_users');
            })
        ;
        $this->assertEquals('INSERT INTO users SELECT * FROM import_users', (string) $insert);
    }
}