<?php
class Db extends SQLite3 {
    
    /**
     * Holds the singleton instance.
     * 
     * @var Db
     */
    public static $instance = null;
    
    public function __construct() {
        parent::__construct(SITEPATH . '/config/phpminer.db');
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
    
    public function exec($query) {
        $result = @parent::exec($query);
        $check_counter = 0;
        while(parent::lastErrorCode() === 5) {
            if ($check_counter++ >= 5) {
                throw new Exception('Could not connect to database, database is still locked');
            }
            sleep(1);
            $result = @parent::exec($query); 
        }
        if (parent::lastErrorCode() !== 0) {
            throw new Exception('Database error: ' . parent::lastErrorMsg());
        }
        return $result;
    }
    
    public function query($query) {
        $result = @parent::query($query);   
        $check_counter = 0;
        while(parent::lastErrorCode() === 5) {   
            if ($check_counter++ >= 5) {
                throw new Exception('Could not connect to database, database is still locked');
            }
            sleep(1);
            $result = @parent::query($query);
        }
        if (parent::lastErrorCode() !== 0) {
            throw new Exception('Database error: ' . parent::lastErrorMsg());
        }
        return $result;
    }
    
    public function querySingle($query, $entire_row = false) {
        $result = @parent::querySingle($query, $entire_row);
        $check_counter = 0;
        while(parent::lastErrorCode() === 5) {
            if ($check_counter++ >= 5) {
                throw new Exception('Could not connect to database, database is still locked');
            }
            sleep(1);
            $result = @parent::querySingle($query, $entire_row);
        }
        if (parent::lastErrorCode() !== 0) {
            throw new Exception('Database error: ' . parent::lastErrorMsg());
        }
        return $result;
    }
    
    public function begin() {
        $this->exec("BEGIN");
    }
    public function commit() {
        $this->exec("COMMIT");
    }
    public function rollback() {
        $this->exec("ROLLBACK");
    }
}