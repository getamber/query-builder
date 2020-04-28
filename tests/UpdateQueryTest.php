<?php

use Amber\Components\QueryBuilder\QueryBuilder;
use PHPUnit\Framework\TestCase;

class UpdateQueryTest extends TestCase
{
    public function testUpdate()
    {
        $update = new QueryBuilder();
        $update->update('users')
            ->setValues([
                'forename' => '?',
                'surname' => '?',
                'email' => '?',
            ])
            ->where('username = ?')
        ;
        $this->assertEquals('UPDATE users SET forename=?,surname=?,email=? WHERE username = ?', (string) $update);
    }

    public function testUpdateSetFromSubquery()
    {
        $update = new QueryBuilder();
        $update->update('users AS u')
            ->setValues([
                'forename' => function ($query) {
                    $query->select('forename')
                        ->from('customers AS c')
                        ->where('c.email = u.email');
                }
            ])
        ;
        $this->assertEquals('UPDATE users AS u SET forename=(SELECT forename FROM customers AS c WHERE c.email = u.email)', (string) $update);
    }
}