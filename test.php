<?php

use diversen\db\q;

// Using an array
// q::connect(array('url', 'username', 'password', 'dont_die', 'db_init'));
// E.g sqlite: 
// q::connect(array('sqlite:test.sql'));
// Fetch multiple rows

$db_conn = array(
    'url' => conf::getMainIni('url'),
    'username' => conf::getMainIni('username'),
    'password' => conf::getMainIni('password'),
    'db_init' => conf::getMainIni('db_init')
);

q::connect($db_conn);
// connect::connect($db_conn);

$rows = q::select('account')->
    filter('id > ', '10')->
    condition('AND')->
    filter('email LIKE', '%test%')->
    order('email', 'DESC')->limit(0, 10)->
    fetch();

print_r($rows);

// Fetch one row
$rows = q::select('account')->
        filter('id > ', '10')->
        condition('AND')->
        filter('email LIKE', '%test%')->
        order('email', 'DESC')->
        fetchSingle();

// Insert
$values = array('email' => 'dennisbech@yahoo.dk');
$res = q::insert('account')->
                values($values)->exec();

// Delete
$res = q::delete('account')->
        filter('id =', 21)->
        exec();

// Update
$values['username'] = 'dennis';
$res = q::update('account')->
        setUpdateValues($values)->
        filter('id =', 22)->
        exec();
