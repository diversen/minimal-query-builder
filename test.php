<?php

include_once "../../autoload.php";

use diversen\db\q;

// Using an array
// q::connect(array('url', 'username', 'password', 'dont_die', 'db_init'));
// E.g sqlite: 
// q::connect(array('sqlite:test.sql'));
// Fetch multiple rows
// Create table messages

// E.g. for MySQL
/*
$mysql_conn = array(
    'url' => $url, // mysql:dbname=gitbook;host=localhost;charset=utf8
    'username' => conf::getMainIni('username'), // username
    'password' => conf::getMainIni('password'), // password
    'db_init' => conf::getMainIni('db_init') // An init command, e.g. 'SET NAMES utf8'
); */

// But we test with sqlite in memory
$sqlite_url = array('url' => 'sqlite::memory:');

// Connect
q::connect($sqlite_url);

// Create a test table
$table = <<<EOF
CREATE TABLE IF NOT EXISTS account (
    id INTEGER PRIMARY KEY, 
    email TEXT, 
    password TEXT)
EOF;

$res = q::query($table)->exec();
if ($res) {
    echo "Table created" . PHP_EOL;
}

// Insert
$values = array('email' => 'test@test.dk', 'password' => 'secret');
$res = q::insert('account')->values($values)->exec();
if ($res) {
    echo "Record inserted"  . PHP_EOL;
}

// Insert and get last insert ID
$values = array('email' => 'test2@test.dk', 'password' => 'very secret');
$res = q::insert('account')->values($values)->execLastInsertId();
if ($res) {
    echo "Record inserted. Return ID = " . $res . PHP_EOL;
}

// Select multiple rows
$rows = q::select('account')->
    filter('id > ', '0')->
    condition('AND')->
    filter('email LIKE', '%test%')->
    order('email', 'DESC')->
    limit(0, 10)->
    fetch();

echo "Should fetch both rows" . PHP_EOL;
print_r($rows);

// Select single row, but only 'id' and 'password' fields
// Notice: You should not use LIMIT when selecting a single row
// Order is also irrelevant in this case. 
$row = q::select('account', ['id', 'password'])->
    filter('id > ', '0')->
    condition('AND')->
    filter('email LIKE', '%test%')->
    fetchSingle();

echo "Should fetch only a single row" . PHP_EOL;
print_r($row);

// Delete first row
$res = q::delete('account')->
        filter('id =', 1)->
        exec();

// Delete first row inserted
echo "Trying to delete first row" . PHP_EOL;
echo "Result of this should be 'true' or 1 " . PHP_EOL;

// Update row
$values = array ('email' => 'test3@test.dk', 'password' => 'extra secret');
$res = q::update('account')->
        values($values)->
        filter('id =', 2)->
        exec();

// Delete first row inserted
echo "Trying to updat 2. row" . PHP_EOL;
echo "Result of this should be 'true' or 1 " . PHP_EOL;
var_dump($res);

