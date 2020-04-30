<?php

use Amber\Components\QueryBuilder\QueryBuilder;
use PHPUnit\Framework\TestCase;

class InsertTest extends TestCase
{
    public function testInsert()
    {
        $query = new QueryBuilder();
        $query->insert('albums')
            ->values([
                'Title' => '?',
                'ArtistId' => '?',
            ])
        ;
        $this->assertEquals(
            'INSERT INTO albums (Title,ArtistId) VALUES (?,?)',
            $query->getSQL()
        );
    }

    public function testInsertWithColumns()
    {
        $query = new QueryBuilder();
        $query->insert('albums')
            ->columns('Title', 'ArtistId')
            ->values('?', '?')
        ;
        $this->assertEquals(
            'INSERT INTO albums (Title,ArtistId) VALUES (?,?)',
            $query->getSQL()
        );
    }
}