<?php

namespace diversen\db;

use diversen\db\connect;
use PDO;

class fulltext extends connect {
    
    /**
     * Search modifier, e.g.: 
     * WITH QUERY EXPANSION
     * IN BOOLEAN MODE
     * @var string 
     */
    public $modifier = 'IN BOOLEAN MODE';
    /**
     * Method for doing a simple full-text mysql search in a database table
     *
     * @param   string  $table the table to search e.g. 'article'
     * @param   string  $match what to match, e.g 'title, content'
     * @param   string  $search what to search for e.g 'some search words'
     * @param   string  $columns what to select e.g. '*'
     * @param   string  $extra extra SQL, e.g. "AND parent_id = 10"
     * @param   int     $from where to start getting the results
     * @param   int     $limit how many results to fetch e.g. 20
     * @return  array   $rows array of rows
     */
    public function simpleSearch($table, $match, $search, $columns, $extra, $from, $limit ){
 
        $search = self::quote($search);
        
        $query = "SELECT ";
        if (empty($columns)){
            $columns = '*';
        }
        $query.= "$columns, ";
        $query.= "MATCH ($match) ";
        $query.= "AGAINST ($search $this->modifier) AS score ";
        $query.= "FROM $table ";
        $query.= "WHERE MATCH ($match) AGAINST ($search $this->modifier) ";
        $query.= " $extra ";
        $query.= "ORDER BY score DESC ";
        $query.= "LIMIT $from, $limit";
        self::$debug[]  = "Trying to prepare simpleSearch sql: $query";
        try {
            $stmt = self::$dbh->prepare($query);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            self::fatalError ($e->getMessage());
        }
        return $rows;
    }

    /**
     * Method for counting rows when searching a mysql table with full-text
     *
     * @param   string  $table the table to search e.g. 'article'
     * @param   string  $match what to match, e.g 'title, content'
     * @param   string  $search what to search for e.g 'some search words'
     * @return  int     $num rows of search results from used full-text search
     */
    public function simpleSearchCount($table, $match, $search, $extra ){
        
        $search = self::quote($search);
        $query = "SELECT COUNT(*) AS num_rows ";
        $query.= "FROM $table ";
        $query.= "WHERE MATCH ($match) AGAINST ($search $this->modifier) ";
        $query.= " $extra ";
        self::$debug[] = "Trying to prepare simpleSearchCount sql: $query in ";
        $stmt = self::$dbh->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['num_rows'];

    }
}

