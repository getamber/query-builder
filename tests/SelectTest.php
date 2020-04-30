<?php

use Amber\Components\QueryBuilder\QueryBuilder;
use PHPUnit\Framework\TestCase;

class SelectTest extends TestCase
{
    public function testSelect()
    {
        $query = new QueryBuilder();
        $query->select('*')->from('artists');
        $this->assertEquals(
            'SELECT * FROM artists',
            $query->getSQL()
        );
    }

    public function testSelectWithWhere()
    {
        $query = new QueryBuilder();
        $query->select('Title')
            ->from('albums')
            ->where('ArtistId = ?')
        ;
        $this->assertEquals(
            'SELECT Title FROM albums WHERE ArtistId = ?',
            $query->getSQL()
        );
    }

    public function testSelectWithOrderBy()
    {
        $query = new QueryBuilder();
        $query->select('Title')
            ->from('albums')
            ->orderBy('Title', QueryBuilder::SORT_ASC)
        ;
        $this->assertEquals(
            'SELECT Title FROM albums ORDER BY Title ASC',
            $query->getSQL()
        );
    }

    public function testSelectWithJoin()
    {
        $query = new QueryBuilder();
        $query->select('albums.Title', 'artists.Name')
            ->from('albums')
            ->addJoin(QueryBuilder::JOIN_LEFT, 'artists', 'albums.ArtistId = artists.ArtistId')
        ;
        $this->assertEquals(
            'SELECT albums.Title,artists.Name FROM albums LEFT JOIN artists ON albums.ArtistId = artists.ArtistId',
            $query->getSQL()
        );
    }

    public function testSelectWithJoinAndWhere()
    {
        $query = new QueryBuilder();
        $query->select('albums.Title', 'artists.Name')
            ->from('albums')
            ->addJoin(QueryBuilder::JOIN_LEFT, 'artists', 'albums.ArtistId = artists.ArtistId')
            ->where('artists.ArtistId = ?')
        ;
        $this->assertEquals(
            'SELECT albums.Title,artists.Name FROM albums LEFT JOIN artists ON albums.ArtistId = artists.ArtistId WHERE artists.ArtistId = ?',
            $query->getSQL()
        );
    }

    public function testSelectWithJoinAndOrderBy()
    {
        $query = new QueryBuilder();
        $query->select('albums.Title', 'artists.Name')
            ->from('albums')
            ->addJoin(QueryBuilder::JOIN_LEFT, 'artists', 'albums.ArtistId = artists.ArtistId')
            ->orderBy([
                'artists.Name' => QueryBuilder::SORT_ASC,
                'albums.Title' => QueryBuilder::SORT_ASC,
            ])
        ;
        $this->assertEquals(
            'SELECT albums.Title,artists.Name FROM albums LEFT JOIN artists ON albums.ArtistId = artists.ArtistId ORDER BY artists.Name ASC,albums.Title ASC',
            $query->getSQL()
        );
    }

    public function testSelectWithJoinWhereAndOrderBy()
    {
        $query = new QueryBuilder();
        $query->select('albums.Title', 'artists.Name')
            ->from('albums')
            ->addJoin(QueryBuilder::JOIN_LEFT, 'artists', 'albums.ArtistId = artists.ArtistId')
            ->where('artists.ArtistId = ?')
            ->orderBy([
                'artists.Name' => QueryBuilder::SORT_ASC,
                'albums.Title' => QueryBuilder::SORT_ASC,
            ])
        ;
        $this->assertEquals(
            'SELECT albums.Title,artists.Name FROM albums LEFT JOIN artists ON albums.ArtistId = artists.ArtistId WHERE artists.ArtistId = ? ORDER BY artists.Name ASC,albums.Title ASC',
            $query->getSQL()
        );
    }

    public function testSelectWithJoinAndGroupBy()
    {
        $query = new QueryBuilder();
        $query->select('albums.Title', 'artists.Name', 'COUNT(invoice_items.TrackId) AS Sales')
            ->from('albums')
            ->addJoin(QueryBuilder::JOIN_INNER, 'tracks', 'tracks.AlbumId = albums.AlbumId')
            ->addJoin(QueryBuilder::JOIN_LEFT, 'invoice_items', 'invoice_items.TrackId = tracks.TrackId')
            ->addJoin(QueryBuilder::JOIN_INNER, 'artists', 'artists.ArtistId = albums.ArtistId')
            ->groupBy('albums.Title')
        ;
        $this->assertEquals(
            'SELECT albums.Title,artists.Name,COUNT(invoice_items.TrackId) AS Sales FROM albums INNER JOIN tracks ON tracks.AlbumId = albums.AlbumId LEFT JOIN invoice_items ON invoice_items.TrackId = tracks.TrackId INNER JOIN artists ON artists.ArtistId = albums.ArtistId GROUP BY albums.Title',
            $query->getSQL()
        );
    }

    public function testSelectWithJoinGroupByAndHaving()
    {
        $query = new QueryBuilder();
        $query->select('albums.Title', 'artists.Name', 'COUNT(invoice_items.TrackId) AS Sales')
            ->from('albums')
            ->addJoin(QueryBuilder::JOIN_INNER, 'tracks', 'tracks.AlbumId = albums.AlbumId')
            ->addJoin(QueryBuilder::JOIN_LEFT, 'invoice_items', 'invoice_items.TrackId = tracks.TrackId')
            ->addJoin(QueryBuilder::JOIN_INNER, 'artists', 'artists.ArtistId = albums.ArtistId')
            ->groupBy('albums.Title')
            ->having('Sales = 0')
        ;
        $this->assertEquals(
            'SELECT albums.Title,artists.Name,COUNT(invoice_items.TrackId) AS Sales FROM albums INNER JOIN tracks ON tracks.AlbumId = albums.AlbumId LEFT JOIN invoice_items ON invoice_items.TrackId = tracks.TrackId INNER JOIN artists ON artists.ArtistId = albums.ArtistId GROUP BY albums.Title HAVING Sales = 0',
            $query->getSQL()
        );
    }

    public function testSelectWithRecursiveWith()
    {
        $query = new QueryBuilder();
        $query->addWith('employee_tree', function ($query) {
            $query->select('e0.EmployeeId', 'e0.ReportsTo', '0 AS Level')
                ->from('employees AS e0')
                ->where('e0.ReportsTo IS NULL')
                ->addUnion(function ($query) {
                    $query->select('e1.EmployeeId', 'e1.ReportsTo', 'Level + 1')
                        ->from('employees AS e1')
                        ->addJoin(QueryBuilder::JOIN_INNER, 'employee_tree AS et', 'et.EmployeeId = e1.ReportsTo');
                });
        })->select('*')->from('employee_tree');
        $this->assertEquals(
            'WITH employee_tree AS (SELECT e0.EmployeeId,e0.ReportsTo,0 AS Level FROM employees AS e0 WHERE e0.ReportsTo IS NULL UNION SELECT e1.EmployeeId,e1.ReportsTo,Level + 1 FROM employees AS e1 INNER JOIN employee_tree AS et ON et.EmployeeId = e1.ReportsTo) SELECT * FROM employee_tree',
            $query->getSQL()
        );
    }
}