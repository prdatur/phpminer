<?php
class Db {
    
    /**
     * Holds the singleton instance.
     * 
     * @var Db
     */
    public static $instance = null;
    
    /**
     * db object.
     * 
     * @var PDO
     */
    private $db = null;
    
    public function __construct() {
        include SITEPATH . '/config/config.php';
        $this->db = new PDO($db['type'] . ':host=' . $db['server'] . ';dbname=' . $db['database'] . ';charset=utf8', $db['username'], $db['password']);
        try {
            if ($db['type'] === 'mysql') {
                $this->db->exec("SET SESSION SQL_MODE=ANSI_QUOTES;");
            }
            if ($db['type'] === 'mssql') {
                $this->db->exec("SET QUOTED_IDENTIFIER ON;");
            }
        } catch (Exception $ex) {

        }
    }
      
    /**
     * Singleton.
     * 
     * @return Db
     *   The instance object.
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Db();
        }
        
        return self::$instance;
    }
    
    /**
     * Alias of exec.
     * 
     * @param string $query
     *   The query.
     * @param array $args
     *   The args. (Optional, default = array())
     * 
     * @return PDOStatement
     */
    public function exec($query, $args = array()) {
        $statement = $this->db->prepare($query);
        $statement->execute($args);
        return $statement;
    }
    
    /**
     * Alias of exec.
     * 
     * @param string $query
     *   The query.
     * @param array $args
     *   The args. (Optional, default = array())
     * 
     * @return PDOStatement
     */
    public function query($query, $args = array()) {
        return $this->exec($query, $args);
    }
    
    /**
     * 
     * @param string $query
     *   The query.
     * @param boolean $entire_row
     *   Set to true if all columns should be returned, else the value of the first found column is returned. (Optional, default = false)
     * @param array $args
     *   The args. (Optional, default = array())
     * 
     * @return mixed
     *   The value of the first column if entire row is set to false, else all columns.
     */
    public function querySingle($query, $entire_row = false, $args = array()) {
        $result = $this->exec($query, $args);
        
        $row = $result->fetch(PDO::FETCH_ASSOC);
        if (!$entire_row) {
            if (!is_array($row)) {
                return $row;
            }
            $row = reset($row);
        }
        return $row;
    }
    
    public function begin() {
        $this->db->exec("BEGIN");
    }
    public function commit() {
        $this->db->exec("COMMIT");
    }
    public function rollback() {
        $this->db->exec("ROLLBACK");
    }
}