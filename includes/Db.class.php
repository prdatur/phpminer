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
        
        
        
        if (file_exists(SITEPATH . '/config/config.php')) {
            include SITEPATH . '/config/config.php';
        }
        
        if (empty($db)) {
            $msg = "<center style='font-size: 18px'>";
            $msg .= "<h1>Hey dude,</h1>";
            $msg .= "It looks like you have just upgraded from an old version or you have just installed PHPMiner.<br>";
            $msg .= "This new version of PHPMiner requires a mysql database. If you don't know how to install it. Please search for it on the net. It's not so complex.<br>";
            $msg .= "When you are on debian based system it's quite simple. Just type <b>apt-get install mysql-server</b>.<br>";
            $msg .= "The problem I have is, you didn't configurated the mysql credentials within config.php.<br>";
            $msg .= "Please copy the provided <b>config.php.dist</b> to <b>config.php</b> and provide your mysql connection details.<br>";
            $msg .= "Don't worry about your data. The old data from the *.json files will be imported.<br>";
            $msg .= "</center>";
            throw new InfoException($msg);
        }
        
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