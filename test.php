<?php

include_once "../../autoload.php";

use diversen\db\q;

// Connect example

// MySQL
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
echo "Trying to delete first row. Result: " . PHP_EOL;
print_r($res);
echo PHP_EOL;

// Update row
$values = array ('email' => 'test3@test.dk', 'password' => 'extra secret');
$res = q::update('account')->
        values($values)->
        filter('id =', 2)->
        exec();

// Delete first row inserted
echo "Trying to update row. Result: " . PHP_EOL;
print_r($res);
echo PHP_EOL;

// Use ransactions:
q::begin();
$values = array('email' => 'test4@test.dk', 'password' => 'just a password');
$res = q::insert('account')->values($values)->execLastInsertId();
if (!$res) {
    echo "Could not insert row. We roll back" . PHP_EOL;
    q::rollback();
}

$res = q::commit();
echo "Result of commit" . PHP_EOL;
print_r($res);
echo PHP_EOL;

// Add some custom SQL
$rows = q::select('account')->sql('id > 0 AND id < 12')->fetch();
echo "Results where using custom SQL: " . PHP_EOL;
print_r($rows);
echo PHP_EOL;

// Replace - a bit different syntax
$res = q::replace('account', array('password' => 'hello world'), array ('id =' => 3));

echo "Results where using replace " . PHP_EOL;
print_r($res);
echo PHP_EOL;

// Use multiple filters as array
$filters = array (
    'id >' => 1, 
    'password =' => "hello world");

$row = q::select('account')->filterArray($filters, 'AND')->fetchSingle();

echo "Result when using filterArray " . PHP_EOL;
print_r($row);
echo PHP_EOL;

// Value in
$ids = array (1, 2, 3);
$rows = q::select('account')->filterIn('ID in', $ids)->fetch();

echo "Result when using filterIn " . PHP_EOL;
print_r($rows);
echo PHP_EOL;
