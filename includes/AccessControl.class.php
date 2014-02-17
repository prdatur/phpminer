<?php

require_once 'AccessConfig.class.php';
class AccessControl {
    
    // POOL
    const PERM_CHANGE_POOL = 'change_pool';
    const PERM_CHANGE_OWN_POOL = 'change_own_pool';
    
    const PERM_VIEW_POOL = 'view_pool';
    const PERM_VIEW_OWN_POOL = 'view_own_pool';
    
    // POOL GROUP
    const PERM_VIEW_POOL_GROUP = 'view_pool_group';
    const PERM_VIEW_OWN_POOL_GROUP = 'view_own_pool_group';
    
    const PERM_CHANGE_POOL_GROUP = 'change_pool_group';
    const PERM_CHANGE_OWN_POOL_GROUP = 'change_own_pool_group';
    
    const PERM_SWITCH_POOL_GROUP = 'switch_pool_group';
    
    // DEVICE
    const PERM_DEVICE_OVERCLOCK = 'device_overclock';
    const PERM_DEVICE_RESET_ALL_STATS = 'device_reset_all_stats';
    const PERM_DEVICE_RESET_STATS = 'device_reset_stats';
    
    // USER
    const PERM_IS_ADMIN = 'is_superadmin';
    const PERM_MANAGE_USERS = 'manage_user';
    
    // USER GROUP
    const PERM_MANAGE_USER_GROUP = 'manage_user_group';
    
    // SETTINGS
    const PERM_CHANGE_MAIN_SETTINGS = 'change_main_settings';
    
    // MINER SETTINGS
    const PERM_CHANGE_MINER_SETTINGS = 'change_miner_settings';
    
    const PERM_VIEW_MINER_SETTINGS = 'view_miner_settings';
    
    // RIGS
    const PERM_VIEW_RIGS = 'view_rigs';
    const PERM_VIEW_OWN_RIGS = 'view_own_rigs';
    
    const PERM_ADD_RIGS = 'add_rigs';
    const PERM_ADD_OWN_RIGS = 'add_own_rigs';
    
    const PERM_CHANGE_RIGS = 'change_rigs';
    const PERM_CHANGE_OWN_RIGS = 'change_own_rigs';
    
    const PERM_STOP_RIGS = 'stop_rigs';
    const PERM_STOP_OWN_RIGS = 'stop_own_rigs';
    
    // NOTIFICATION
    const PERM_VIEW_NOTIFICATION_SETTINGS = 'view_notification_settings';
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
        if (AccessControl::is_enabled()) {
            if (!$this->access_config->is_empty()) {
                if ($user === null) {
                    $user = $this->user;
                }
                if (empty($user)) {
                    return false;
                }
                
                $group = $this->access_config->group_get($user['group']);
                return isset($group['permissions']) && !empty($group['permissions'][$permission]);
            }
        }
        return true;
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
    public function check_permission($permission, $user = null) {
        if (!$this->access_control->has_permission($permission, $user)) {
            throw new Exception('You don\'t have access to this action');
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