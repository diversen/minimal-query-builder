<?php

namespace diversen\db;

use Exception;
use PDO;
use PDOException;

/**
 * Create a connection to some sort of database
 *
 * @package main
 */

class connect
{

    /**
     * database handle
     */
    public static $dbh = null;

    /*
     * Flag indicating if there is a connection
     */
    public static $con = null;

    /**
     * var that holds all sql statements fro debug purpose
     */
    public static $debug = array();

    /**
     * Connect to a database using an array with some of these arguments
     * <code>array('url', 'username', 'password', 'dont_die', 'db_init')</code>
     * @param array $options
     * @return void
     */
    public function __construct($options = null)
    {
        if (!self::$dbh) {
            self::$dbh->connect($options);
        }
    }

    /**
     * Checks params and set missing params to null
     * @param array $options
     * @return array $options
     */
    private static function setOptions($options)
    {
        if (!isset($options['username'])) {
            $options['username'] = null;
        }
        if (!isset($options['password'])) {
            $options['password'] = null;
        }
        if (!isset($options['db_init'])) {
            $options['db_init'] = null;
        }
        return $options;
    }

    /**
     * Connect to a database using an options array
     * <code>array('url', 'username', 'password', 'dont_die', 'db_init')</code>
     * If the array is empty then try to read from a configuration file.
     * @param array $options
     * @return void|string void or 'NO_DB_CON' if fail on connect
     */
    public static function connect($options = [])
    {

        if (!is_array($options) && !is_object($options)) {
            throw new Exception('Connection must to be a PDO Object or a connection array');
        }

        if ($options instanceof PDO) {
            self::$dbh = $options;
            self::$con = true;
            self::$debug[] = 'Connected with instance of PDO';
            return;
        }

        if (!isset($options['url'])) {
            return false;
        }

        $options = self::setOptions($options);
        self::$debug[] = "Trying to connect with " . $options['url'];

        // Dfault is to persist
        if (!isset($options['db_dont_persist'])) {
            $options['PDO::ATTR_PERSISTENT'] = true;
        }

        // Try and connect
        try {
            self::$dbh = new PDO(
                $options['url'],
                $options['username'],
                $options['password'],
                $options
            );

            // Exception mode
            self::$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // set SSL
            self::setSsl($options);

            // init
            if (isset($options['db_init'])) {
                self::$dbh->exec($options['db_init']);
            }

            // Catch Exception
        } catch (PDOException $e) {
            if (!isset($options['dont_die'])) {
                die('Connection failed - check your database and your connection params');
            } else {
                self::$debug[] = $e->getMessage();
                self::$debug[] = 'No connection';
                return "NO_DB_CONN";
            }
        }
        self::$con = true;
        self::$debug[] = 'Connected using array of options';
    }

    /**
     * Set SSL for MySQL if SSL is set in the configuration,
     * experimental
     * @return void
     */
    public static function setSsl($options)
    {
        if (isset($options['mysql_ssl'])) {
            self::$dbh->setAttribute(PDO::MYSQL_ATTR_SSL_KEY, $options['ssl_key']);
            self::$dbh->setAttribute(PDO::MYSQL_ATTR_SSL_CERT, $options['ssl_cert']);
            self::$dbh->setAttribute(PDO::MYSQL_ATTR_SSL_CA, $options['ssl_ca']);
        }
    }

    /**
     * Method for showing fatal database errors
     * @param string $msg the message to show with the backtrace
     * @return void
     */
    protected static function fatalError($msg)
    {
        self::$debug[] = "Fatal error encountered";
        echo "<pre>Error!: $msg\n";
        $bt = debug_backtrace();
        foreach ($bt as $line) {
            $args = var_export($line['args'], true);
            echo "{$line['function']}($args) at {$line['file']}:{$line['line']}\n";
        }
        echo "</pre>";
        die();
    }

    /**
     * Quotes a string safely according to connection type, e.g. MySQL
     * @param string $string
     * @return string $string
     */
    public static function quote($string)
    {
        return self::$dbh->quote($string);
    }

    /**
     * return all sql statements as an array
     * @return array $debug
     */
    public static function getDebug()
    {
        return self::$debug;
    }
}
