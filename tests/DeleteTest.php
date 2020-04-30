<?php

use Amber\Components\QueryBuilder\QueryBuilder;
use PHPUnit\Framework\TestCase;

class DeleteTest extends TestCase
{
    public function testDelete()
    {
        $query = new QueryBuilder();
        $query->delete('albums')
            ->where('ArtistId = ?')
        ;
        $this->assertEquals(
            'DELETE FROM albums WHERE ArtistId = ?',
            $query->getSQL()
        );
    }
}