<?php

namespace diversen\db;

use diversen\db;

/**
 * File contains common methods to use with databases without doing queries,
 * Copy, tables, shift database, adding search index, finding keys etc.  
 */
class admin {
    
    /**
     * Changes the MySQL database we are working on. 
     * If the 1. param $database is empty, we connect
     * to the default database
     * @param string $database the new database to use
     * @return void
     */
    public static function changeDB ($database = null) {
        $db = new db();
        if (!$database) {
            return false;
        }
        $sql = "USE `$database`";
        $db->rawQuery($sql);
    }
    
    /**
     * Get database info from configuration.
     * @return array|false $ary with info or false if no url 
     * does not exists in the configuration
     */
    public static function getDbInfo($url) {
        
        if (empty($url)) {
            return false;
        }
        
        $url = parse_url($url);
        $ary = explode (';', $url['path']);
        foreach ($ary as $val) {
            $a = explode ("=", $val);
            if (isset($a[0], $a[1])) {
                $url[$a[0]] = $a[1];
            }
        }
        return $url;
    }
    
    /**
     * Dublicate a MySQL table.  
     * @param string $source source table name
     * @param string $dest destination table name
     * @param boolean $drop should we drop table if destination exists 
     * @return boolean $res result of the executed query
     */
    public static function dublicateTable ($source, $dest, $drop = true) {
        $db = new db();
        if ($drop) {
            $sql = "DROP TABLE IF EXISTS $dest";
            $res = $db->rawQuery($sql);
            if (!$res) {
                return false;
            }
        }
        $sql = "CREATE TABLE $dest LIKE $source; INSERT $dest SELECT * FROM $source";
        return $db->rawQuery($sql);
    }
    
    /**
     * Alter a table and add a MySQL fulltext index on specified columns.
     * @param string $table
     * @param string $columns columns to make the index on (e.g. 'firstname, lastname')
     * @return boolean $res result of the exectued query
     */
    public static function generateIndex($table, $columns) {
        $db = new db();
        $sql = "ALTER TABLE $table ENGINE = MyISAM";
        $res =  $db->rawQuery($sql);
        if (!$res) {
            return false;
        }
        
        $cols = implode(',', $columns);
        $sql = "ALTER TABLE $table ADD FULLTEXT($cols)";
        return $db->rawQuery($sql);
    }
    
    /**
     * Check if a MySQL table with specified name exists. 
     * May only work on MySQL
     * @param string $table
     * @return array $rows empty array if table does not exist
     */
    public static function tableExists($table) {
        $db = new db();
        $q = "SHOW TABLES LIKE '$table'";
        $rows = $db->selectQueryOne($q);
        return $rows;
    }
    
    /**
     * Get MySQL column *keys* in a table as an array. 
     * May only work on MySQL
     * @param string $table the table name
     * @return array $rows
     */
    public static function getKeys ($table) {
        $q = "SHOW KEYS FROM $table";
        $db = new db();
        $rows = $db->selectQuery($q);
        return $rows;
    }
    
    /**
     * Examine if a MySQL *key* exists in a *table*
     * @return array $rows empty row if the key does not exists
     */
    public static function keyExists ($table, $key) {
        $db = new db();
        $q = "SHOW KEYS FROM $table WHERE Key_name='$key'";
        $rows = $db->selectQueryOne($q);
        return $rows;
    }
    
    /**
     * Clone a complete MySQL database from *database* to *newDatabase*
     * @param string $database
     * @param string $newDatabase
     * @return boolean $res
     */
    public static function cloneDB($database, $newDatabase){
        $db = new db();
        $rows = $db->selectQuery('show tables');
        $tables = array();
        foreach ($rows as $table) {
            $tables[] = array_pop($table);
        }

        $db->rawQuery("CREATE DATABASE `$newDatabase` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
        foreach($tables as $cTable){
            self::changeDB ( $newDatabase );
            $create     =   $db->rawQuery("CREATE TABLE $cTable LIKE ".$database.".".$cTable);
            if(!$create) {
                $error  =   true;
            }
            $db->rawQuery("INSERT INTO $cTable SELECT * FROM ".$database.".".$cTable);
        }
        return !isset($error) ? true : false;
    }
}
