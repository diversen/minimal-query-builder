<?php

namespace diversen\db;

use diversen\db\connect;
use Exception;
use PDO;

/**
 * A simple way of doing DB operations
 */
class q extends connect {
    /**
     * holder for query being built
     * @var string $query holding query 
     */
    public static $query = null;

    /**
     * var holding PDO statement
     * @var object $stmt 
     */
    public static $stmt = null;

    /**
     * holding all statements that will be bound
     * @var array $bind.
     */
    public static $bind = array();

    /**
     * indicate if a WHERE sql sentence has been used
     * @var string|null  $where 
     */
    public static $where = null;
    
    /**
     * var holding method (SELECT, UPDATE, INSERT, DELETE) 
     * @var type $method 
     */
    public static $method = '';
    
    /**
     * flag indicating if a sql method has been set
     * var $isset
     */
    public static $isset = null;

    /**
     * Constructor inits object
     * @param array $options
     * @return void
     */
    function __construct($options = null) {
        self::init($options);       
    }
    
    /**
     * Init. Create connection if no connection exists
     * @param array $options
     * @return void
     */
    public static function init($options = null) {
        if (!self::$dbh) {
            self::connect($options);
        } 
    }
    
    /**
     * begin transaction
     * @return boolean $res
     */
    public static function begin () {
        return self::$dbh->beginTransaction();
    }
    
    /**
     * commit transaction
     * @return boolean $res 
     */
    public static function commit () {
        return self::$dbh->commit();
    }

    /**
     * roolback transaction
     * @return boolean $res 
     */
    public static function rollback () {
        return self::$dbh->rollBack();
    }
    
    /**
     * Get last string from debug array
     * @return string $debug
     */
    public static function getLastDebug () {
        return $debug = array_pop(self::$debug);
    }
    
    /**
     * Set SQL for a SELECT statement
     * @param string $table the table to select from 
     * @param string $fields the fields from the table to select 
     *             e.g. * or 'id, title'
     * @return self
     */
    public static function setSelect ($table, $fields = null){
        self::$method = 'select';
        
        if (empty($fields)) {
            $fields = '*';
        } 
        
        if (is_array($fields)) {
            $fields = implode(', ', $fields);
        }
        
        self::$query = "SELECT $fields FROM `$table` ";
        return new self;
    }
    
    
    /**
     * Set SQL for a SELECT count(*) statement
     * @param type $table
     * @return self
     */
    public static function setSelectNumRows ($table){
        self::$method = 'num_rows';
        self::$query = "SELECT count(*) as num_rows FROM $table ";
        return new self;
    }
    

    
    /**
     * Set SQL for delete
     * @param string $table the table to delete from
     * @return self
     */
    public static function setDelete ($table){
        self::$method = 'delete';
        self::$query = "DELETE FROM $table ";
        return new self;
    }
    
    /**
     * Set SQL for update
     * @param string $table 
     * @return self
     */
    
    public static function setUpdate ($table) {
        self::$method = 'update';
        self::$query = "UPDATE $table SET ";
        return new self;
    }
    
    /**
     * Set SQL For insert
     * @param type $table the table to insert values into
     * @return self
     */
    public static function setInsert ($table) {
        self::$method = 'insert';
        self::$query = "INSERT INTO $table ";
        return new self;
    }
    
    /**
     * Set values for insert or update. 
     * @param array $values the values to insert
     * @param array $bind array with types to bind values to
     * @return self
     */
    public static function setValues ($values, $bind = array()) {
        if (self::$method == 'update') {
            self::setUpdateValues($values, $bind);
        } else {
            self::setInsertValues($values, $bind);
        }
        return new self;
    }
    
    /**
     * Prepare and bind for update
     * @param array $values the values to update with
     * @param array $bind array with types to bind with
     * @return self
     */
    public static function setUpdateValues ($values, $bind = array ()) {
        $ary = array();
        foreach ($values as $field => $value ){
            $ary[] = " `$field` =" . " ? ";
            if (isset($bind[$field])) {
                self::$bind[] = array ('value' => $value, 'bind' => $bind[$field]);
            } else {
                self::$bind[] = array ('value' => $value, 'bind' => null);
            }
        }
        
        self::$query.=  implode (',', $ary);
        return new self;
    } 
    
    /**
     * Prepares and bind insert values
     * @param array $values the values to insert into table
     * @param array $bind the values to bind values with
     * @return self
     */
    public static function setInsertValues ($values, $bind = array ()) {
        $rest = array ();
        $fieldnames = array_keys($values);
        $fields = '( ' . implode(' ,', $fieldnames) . ' )';
        self::$query.= $fields . ' VALUES ';
        foreach ($fieldnames as $val) {
            $rest[] = '?';
        }
        
        self::$query.= '(' . implode(', ', $rest) . ')';
        foreach ($values as $field => $value ){           
            if (isset($bind[$field])) {

                self::$bind[] = array ('value' => $value, 'bind' => $bind[$field]);
            } else {
                self::$bind[] = array ('value' => $value, 'bind' => null);
            }
        }
        return new self;
        
    } 

    /**
     * Prepare and bind a filter in a query, e.g. filter('id > 2', $value);
     * @param string $filter the filter to use e.g. 'id >
     * @param string $value the value to filter from e.g. '2'
     * @param string $bind  if we want to bind the value to a type. 
     * @return self
     */
    public static function filter ($filter, $value, $bind = null) {
        self::setWhere();
        self::$query.= " $filter ? ";
        self::$bind[] = array ('value' => $value, 'bind' => $bind);
        return new self;
    }
    
    /**
     * Prepares and bind a query as string
     * @param string $str the filter to use e.g. 'id > ? OR email = ?'
     * @param array  $values the value to filter from e.g. '2'
     * @param array  $bind  if we want to bind the value to a type. 
     * @return self
     */
    public static function filterString ($str, $values, $bind = null) {
        self::setWhere();
        self::$query.= " $str ";
        foreach ($values as $key => $val) {
            if (isset($bind[$key])) {
                self::$bind[] = array ('value' => $val, 'bind' => $bind[$key]);
            } else {
                self::$bind[] = array ('value' => $val, 'bind' => null);
            }
        }
        return new self;
    }
    
    /**
     * Sets WHERE if a WHERE condition has not been set
     */
    private static function setWhere () {
        if (!self::$where) {
            self::$where = 1;
            self::$query.= "WHERE ";
        }
    }
    
    /**
     * Set clean SQL after WHERE
     * @param string $sql e.g. "id >= 3"
     * @return self
     */
    public static function sql ($sql) {
        self::setWhere();
        self::$query.= " $sql ";
        return new self;
    }
    
    /**
     * Set a complete and clean SQL statement
     * @param string $sql e.g. "SELECT * FROM `table`  id >= 3"
     */
    public static function sqlClean ($sql) {
        self::$method = 'select';
        self::$query = $sql;
        return new self;
    }
    
    /**
     * SQL for preparing IN queries where we use an array of values
     * to create our filter from. 
     * @param string $filter to filter from, e.g. "ID in"
     * @param array $values the values which we will use, e.g. array(1, 2, 3) 
     * @return self
     */
    public static function filterIn ($filter, $values) {
        self::setWhere();

        self::$query.= " $filter ";
        self::$query.= "(";
        $num_val = count($values);

        foreach ($values as $key => $val){
            self::$query.=" ? ";
            self::$bind[] = array ('value' => $val, 'bind' => null);
            $num_val--;
            if ($num_val) { 
                self::$query.=",";
            }
        }
        self::$query.=")";
        return new self;
    }

    /**
     * Sets a condition between filters, e.g. AND
     * @param string $condition (e.g. 'AND', 'OR')
     * @return self
     */
    public static function condition ($condition){
        self::$query.= " $condition ";
        return new self;
    }

    /**
     * Set ordering of the values which we tries to fetch
     * NOTE: Remember to escape the order when using user input
     * @param string $column column to order by, e.g. title (remember to escape this!)
     * @param string $order (e.g. ASC or DESC)
     * @return self
     */
    public static function order ($column, $order = 'ASC', $options = array ()){      
        if (!self::$isset) { 
            self::$query.= " ORDER BY $column $order ";
        } else {
            self::$query.= ", $column $order ";
        }   
        self::$isset = true;
        return new self;
    }
    
    /**
     * Method for setting a limit in the query. Note: Escape user
     * supplied values
     * @param int $from where to start the limit e.g. 200
     * @param int $limit the limit e.g. 10
     * @return self
     */
    public static function limit ($from, $limit){
        $from = (int)$from;
        $limit = (int)$limit;
        self::$query.= " LIMIT $from, $limit";
        return new self;
    }

    /**
     * Method for preparing and setting all bound columns and corresponding values
     */
    private static function prepare (){
        if (self::$bind){
            $i = 1;
            foreach (self::$bind as $key => $val){
                if (isset($val['bind'])) {
                    self::$stmt->bindValue ($i, $val['value'], $val['bind']);
                } else {
                    self::$stmt->bindValue ($i, $val['value']);
                }
                $i++;
            }
        }
        self::$bind = null;
    }

    /**
     * Method for fetching rows
     * @return array $rows assoc array of rows
     */
    public static function fetch (){
        
        try {
            self::$debug[] = self::$query;
            self::init();
            self::$stmt = self::$dbh->prepare(self::$query );
            self::prepare();

            self::$stmt->execute();
            $rows = self::$stmt->fetchAll(PDO::FETCH_ASSOC);
            if (self::$method == 'select_one') {
                if (!empty($rows)) {
                    $rows = $rows[0];
                } 
            }
            self::unsetVars();
        } catch (Exception $e) {
            $error = $e->getMessage() . PHP_EOL;
            $error.= $e->getTraceAsString(). PHP_EOL;
            $error.= self::getLastDebug() . PHP_EOL;
            trigger_error($error, E_USER_ERROR);
            exit();
            
        }
        if (self::$method == 'num_rows') {
            return $rows[0]['num_rows'];
        }       
        return $rows;
    }
    
    /**
     * Perform a raw query
     * @param string $query e.g. "SELECT * FROM mytable";
     * @return self
     */
    public static function query ($query) {
        self::$method = 'select';
        self::$query = $query;
        return new self;
    }
    
    /**
     * Method to execute a query, insert update or delete. 
     * @return boolean $res true on success and false on failure. 
     */
    public static function exec() {
        
        self::$debug[] = self::$query;    
        self::$stmt = self::$dbh->prepare(self::$query);
        try {
            self::prepare(); 
            $res = self::$stmt->execute();
        } catch (Exception $e) {
            $error = $e->getMessage() . PHP_EOL;
            $error.= $e->getTraceAsString(). PHP_EOL;
            $error.= self::getLastDebug() . PHP_EOL;
            trigger_error($error, E_USER_ERROR);
            exit();
            
        }
        self::unsetVars();
        return $res;
    }
    
    /**
     * Exec and return lastInsertId or 0
     * @return int $res
     */
    public static function execLastInsertId() {
        $res = self::exec();
        if (!$res) {
            return 0;
        }
        return self::lastInsertId();
    }
    
    /**
     * Get last insert id
     * @return int $id last insert id
     */
    public static function lastInsertId () {
        return self::$dbh->lastInsertId();
    }

    /**
     * Fetch a single row
     * @return array $row single array
     */
    public static function fetchSingle (){
        self::limit(0, 1);
        $rows = self::fetch();
        if (isset($rows[0])){
            return $rows[0];
        }
        return array();
    }
    
    /**
     * Short hand for setSelect
     * @param string $table
     * @param array $fields
     * @return self
     */
    public static function select ($table, $fields = null){
        self::setSelect($table, $fields);
        return new self;
        
    }
    
    /**
     * Short hand of setSelectNumRows
     * @param string $table
     * @return self
     */
    public static function numRows ($table){
        self::setSelectNumRows($table);
        return new self;
    }
    
    /**
     * Set delete SQL
     * @param string $table the table to delete from
     * @return self
     */
    public static function delete ($table){
        self::$method = 'delete';
        self::$query = "DELETE FROM $table ";
        return new self;
    }
    
    /**
     * Set update SQL
     * @param string $table
     * @return self
     */   
    public static function update ($table) {
        self::setUpdate($table);
        return new self;
    }
    
    /**
     * Set SQL for insert
     * @param type $table the table to insert values into
     * @return self
     */
    public static function insert ($table) {
        self::setInsert($table);
        return new self;
    }
    
    /**
     * Set multiple filters as an array
     * @param array $ary array('user_id =' => 20, 'username =' => 'myname); 
     * @param string $condition the condition to split the statements with, 
     * e.g. 'AND', 'OR' 
     * @return self
     */
    public static function filterArray ($ary, $condition = 'AND') {
        $i = count($ary);
        foreach ($ary as $key => $val) {
            $i--;
            self::filter($key, $val);
            if ($i) { 
                self::condition($condition);
            }
        }
        return new self();
    }
    
    /**
     * Set filter conditions as a array, but without e.g. '=' or '<' in the keys
     * @param string $ary array('user_id' => 20, 'username' => 'myname);
     * @param string $condition e.g. 'AND', 'OR' 
     * @param string $operator e.g. (=)
     * @return self
     */
    public static function filterArrayDirect ($ary, $condition = 'AND', $operator = '=') {
        $i = count($ary);
        foreach ($ary as $key => $val) {
            $i--;
            $key.= $operator; 
            self::filter($key, $val);
            if ($i) { 
                self::condition($condition);
            }
        }
        return new self();
    }
        
    
    /**
     * Replace (insert or update) a row in a table
     * @param string $table
     * @param array $values update|insert values
     * @param array $search e.g. array('user_id =' => 20, 'username =' => 'myname);
     * @return self
     */
    public static function replace ($table, $values, $search) {
        $num_rows = self::numRows($table)->filterArray($search)->fetch();
        if (!$num_rows){
            self::insert($table)->values($values)->exec();
        } else {
            self::update($table)->values($values)->filterArray($search)->exec();
        }
        return new self;
    }
    
    /**
     * Set values for insert or update. 
     * @param array $values the values to insert
     * @param array $bind array with types to bind values to
     * @return self
     */
    public static function values ($values, $bind = array()) {
        self::setValues($values, $bind);
        return new self;
    }
    
    /**
     * Short hand of fetchSingle
     * @return array $row
     */
    public static function one () {
        return self::fetchSingle();
    }
    
    /**
     * Method for unsetting static vars when an operation is complete.
     */
    private static function unsetVars (){
        self::$query = self::$isset = self::$bind = self::$where = self::$stmt = null;
    }
}
