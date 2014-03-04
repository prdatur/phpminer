<?php

require_once 'AccessConfig.class.php';
require_once 'Session.class.php';
require_once 'PasswordHash.class.php';



class AccessControl {
            
    // POOL GROUP
    const PERM_VIEW_POOL_GROUP = 'view_pool_group';
    const PERM_CHANGE_POOL_GROUP = 'change_pool_group';    
    const PERM_SWITCH_POOL_GROUP = 'switch_pool_group';
    
    // DEVICE
    const PERM_DEVICE_OVERCLOCK = 'device_overclock';
    const PERM_DEVICE_RESET_STATS = 'device_reset_stats';
    
    // USER
    const PERM_IS_ADMIN = 'is_admin';
    const PERM_MANAGE_USERS = 'manage_user';
    
    // SETTINGS
    const PERM_CHANGE_MAIN_SETTINGS = 'change_main_settings';
    
    // MINER SETTINGS
    const PERM_CHANGE_MINER_SETTINGS = 'change_miner_settings';
    
    // RIGS 
    const PERM_CHANGE_RIGS = 'change_rigs';    
    const PERM_STOP_RIGS = 'stop_rigs';
    const PERM_REBOOT_RIGS = 'reboot_rigs';
    
    // NOTIFICATION
    const PERM_CHANGE_NOTIFICATION_SETTINGS = 'change_notification_settings';
    
    /**
     * Holds the access config.
     * 
     * @var AccessConfig 
     */
    private $access_config = null;
    
    /**
     * Holds the logged in user.
     * 
     * @var array
     */
    private $user = null;
    
    /**
     * Determines if access control is enabled.
     * 
     * @var boolean
     */
    private static $enabled = false;
    
    /**
     * Singleton instance.
     * 
     * @var AccessControl 
     */
    private static $instance = null;
    
    /**
     * Holds the current session.
     * 
     * @var Session
     */
    private static $session = null;
    
    /**
     * Createa a new access control object.
     */
    public function __construct() {
        $this->access_config = new AccessConfig();
    }
    
    /**
     * The access config.
     * @return AccessConfig
     */
    public function get_config() {
        return $this->access_config;
    }
    
    /**
     * Returns the permission array with all possible permission's and their
     * help text's.
     * 
     * @return array 
     *   The permissions as the key and the help text as the value.
     */
    public static function get_permission_array() {
        return array(
           
            // POOL GROUP
            self::PERM_VIEW_POOL_GROUP => 'Allows to view all pool groups',
            self::PERM_CHANGE_POOL_GROUP => 'Allows to change all pool groups',
            self::PERM_SWITCH_POOL_GROUP => 'Allows to switch pool groups',

            // DEVICE
            self::PERM_DEVICE_OVERCLOCK => 'Allows to change device overclock settings',
            self::PERM_DEVICE_RESET_STATS => 'Allows to reset rig stats',

            // USER
            self::PERM_IS_ADMIN => 'This determines the user has all permissions.',
            self::PERM_MANAGE_USERS => 'Allows to change users',

            // SETTINGS
            self::PERM_CHANGE_MAIN_SETTINGS => 'Allows to view/change main settings',

            // MINER SETTINGS
            self::PERM_CHANGE_MINER_SETTINGS => 'Allows to view/change miner settings',

            // RIGS
            self::PERM_CHANGE_RIGS => 'Allows to change all rigs',
            self::PERM_STOP_RIGS => 'Allows to start/stop any rig',
            self::PERM_REBOOT_RIGS => 'Allows to reboot any rig',
            
            // NOTIFICATION
            self::PERM_CHANGE_NOTIFICATION_SETTINGS => 'Allows to view/change notification settings',
        );
    }
    
    /**
     * Checks if the given user has the given permission.
     * 
     * @param string $permission
     *   The permission string to check-
     * @param array $user
     *   If not provided current logged in user will be used. (Optional, default = null)
     * 
     * @return boolean
     *   True if the user has the permission, else false. If access control is not enabled or was just enabled without any config it returns also true.
     */
    public function has_permission($permission, $user = null) {
        static $perms = array();
        if (AccessControl::is_enabled()) {
            if (!$this->access_config->is_empty()) {
                if ($user === null) {
                    $user = $this->user;
                }
                if (empty($user)) {
                    return false;
                }
                
                if (!isset($perms[$user['username']])) {
                    $res = db::getInstance()->query('SELECT "permission" FROM "group2perm" WHERE "group_name" = :group', array(
                        ':group' => $user['group'],
                    ));
                    $perms[$user['username']] = array();
                    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
                        $perms[$user['username']][$row['permission']] = $row['permission'];
                    }
                }
                return isset($perms[$user['username']]) && (!empty($perms[$user['username']]['*']) || !empty($perms[$user['username']]['is_admin']) || !empty($perms[$user['username']][$permission]));
            }
        }
        return true;
    }
    
    /**
     * Returns the logged in username or throws an exception.
     * 
     * @return string
     *   The username as a string.
     * @throws Exception
     */
    public function get_username() {
        if (empty($this->user)) {
            throw new Exception('User not logged in.');
        }
        return $this->user['username'];
    }
    
    /**
     * Returns wether the user is logged in or not.
     * 
     * @return boolean
     *   True if logged in, else false.
     */
    public function check_login() {
        
        if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
            $pw_hash = new PasswordHash();
            if ($this->access_config->user_exists($_SERVER['PHP_AUTH_USER'])) {
               
                $user = $this->access_config->user_get($_SERVER['PHP_AUTH_USER']);
                if ($pw_hash->check_password($_SERVER['PHP_AUTH_PW'], $user['password'])) {
                    self::$session->set('username', $_SERVER['PHP_AUTH_USER']);
                    $this->user = $this->get_config()->user_get(self::$session->get('username'));
                    return true;
                }
            }
        }
        
        if (self::$session->get('username') != 0) {
            $this->user = $this->get_config()->user_get(self::$session->get('username'));
            return true;
        }
        
        header('WWW-Authenticate: Basic realm="PHPMiner "');
        header('HTTP/1.0 401 Unauthorized');
    }
    
    /**
     * Checks if the given user has the permission and if not it will throw an error.
     * 
     * @param string $permission
     *   The permission string to check-
     * @param array $user
     *   If not provided current logged in user will be used. (Optional, default = null)
     * 
     * @throws Exception
     *   Will be thrown when the user has not the permission-
     */
    public static function check_permission($permission, $user = null) {
        if (!self::getInstance()->has_permission($permission, $user)) {
            throw new AccessException('You don\'t have access to this action');
        }
    }
    
    /**
     * Singleton.
     * 
     * @return AccessControl
     *   The instance object.
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new AccessControl();
        }
        
        return self::$instance;
    }
    
    /**
     * Enables access control.
     */
    public static function enable() {
        self::$session = new Session();
        self::$enabled = true;
    }
    
    /**
     * Returns wether access control is enabled or not.
     * 
     * @return boolean
     *   True if enabled, else false.
     */
    public static function is_enabled() {
        return self::$enabled;
    }
    
}