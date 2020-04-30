<?php

use Amber\Components\QueryBuilder\QueryBuilder;
use PHPUnit\Framework\TestCase;

class UpdateTest extends TestCase
{
    function testUpdate()
    {
        $query = new QueryBuilder();
        $query->update('playlists')
            ->set(['Name' => '?'])
            ->where('PlaylistId = ?')
        ;
        $this->assertEquals(
            'UPDATE playlists SET Name=? WHERE PlaylistId = ?',
            $query->getSQL()
        );
    }
}