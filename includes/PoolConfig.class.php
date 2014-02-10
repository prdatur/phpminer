<?php
/**
 * @copyright Christian Ackermann (c) 2013 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */

/**
 * Represents the config of php cgminer.
 */
class PoolConfig extends Config {

    /**
     * Creates a new instance of the pool config.
     */
    public function __construct() {
        parent::__construct(SITEPATH . '/config/pools.json');
    }
    
    /**
     * Add the given pool to the given group.
     * 
     * @param string $url
     *   The pool url.
     * @param string $user
     *   The user.
     * @param string $pass
     *   The password.
     * @param string $group
     *   The group to retrieve. (optional, default="default")
     * @param int $quota
     *   The pool quota. (Optional, default = 1)
     * @param boolean $rig_based
     *   If this pool is rigbased or not. (Optional, default = false)
     */
    public function add_pool($url, $user, $pass, $group = 'default', $quota = 1, $rig_based = false) {
        // Create the group if it doesn't exist yet.
        if (!isset($this->config[$group])) {
            $this->config[$group] = array();
        }
        // Add the pool to the group.
        $this->config[$group][$this->get_pool_uuid($url, $user)] = array(
            'url' => $url,
            'user' => $user,
            'pass' => $pass,
            'group' => $group,
            'quota' => $quota,
            'rig_based' => $rig_based,
        );
        
        // Save the new config instantly.
        $this->save();
    }
    
    /**
     * Returns the current active pool group.
     * It will search within the configurated pool groups if the current configurated cgminer pools are match all pools in a group.
     * 
     * @param CGMinerAPI $api
     *   The cgminer api, it's needed to retrieve current configurated pools within cgminer.
     * 
     * @return null|array
     *   the active pool group as an array or null if no active group could be found.
     */
    public function get_current_active_pool_group($api) {
        $cfg_groups = $this->get_config();
        
        // Get all configured pools within cgminer.
        $pools = $api->get_pools();
        
        // Determine which pool group we are currently using.        
        $current_active_group = null;
        foreach ($cfg_groups AS $check_group => $cfg_groups_pools) {
            // Get a copy so we don't remove anything from $cfg_groups
            $cfg_pools_check = array();

            foreach ($cfg_groups_pools AS $pool) {
                $cfg_pools_check[$this->get_pool_uuid($pool['url'], $pool['user'])] = $pool;
            }
            // We don't want to check empty groups.
            if (empty($cfg_pools_check)) {
                continue;
            }
            
            // Loop through each pool wich are active within cgminer.
            foreach ($pools AS $k => $pool) {
                
                // If we didn't generated a uuid for the current active cgminer pool, generate it.
                if (!isset($pool['uuid'])) {
                    $pool['uuid'] = $this->get_pool_uuid($pool['URL'], $pool['User']);
                    $pools[$k]['uuid'] = $pool['uuid'];
                }
                
                // Now we check if the current active pool uuid is within the current checked group.
                if (isset($cfg_pools_check[$pool['uuid']])) {
                    // If it is, remove it from the check pool array, after all current active pools are processed and the $cfg_pools_check is empty we can be sure that this is the current active group.
                    unset($cfg_pools_check[$pool['uuid']]);
                }
                else {
                    // Here the active pool is not within the group so just check the next group
                    continue 2;
                }
            }

            // After we checked the cgminer active pools which are all within the current configurated group, let's check if the group is now empty. If it is empty we found the current active group.
            if (empty($cfg_pools_check)) {
                $current_active_group = $check_group;
                break;
            }
            
        }
        return $current_active_group;
    }
    
    /**
     * Returns the uuid for the given pool / user.
     * 
     * @param string|array $url
     *   The pool url. or the combination $url . '|' . $user or an array where first value is the url and the second is the user.
     * @param string $user
     *   The pool username. Only optional if url is not just the url. (Optional, default = "")
     * 
     * @return string
     *   The uuid for the pool.
     */
    public function get_pool_uuid($url, $user = "") {
        if (is_array($url)) {
            return md5(implode("|", $url));
        }
        if (empty($user)) {
            return md5($url);
        }
        $user = preg_replace("/_rb_[a-zA-Z0-9]+$/", "", $user);
        return md5($url . '|' . $user);
    }
        
    /**
     * Checks if the given user and password are fully recognized within the given pool.
     * Also checks if the pool it self exists and is a valid mining pool.
     * 
     * @param string $url
     *   The pool url.
     * @param string $user
     *   The worker username.
     * @param string $pass
     *   The worker password.
     * @return boolean|string
     *   True if all ok, else the error as a human readable string.
     */
    public function check_pool($url, $user, $pass) {
        static $config = null;
        if ($config === null) {
            $config = new Config(SITEPATH . '/config/config.json');
        }
        
        // Get the url parts.
        $url_parts = parse_url($url);
        $connection_check = new HttpClient();
        if (!isset($url_parts['host'])) {
            return 'Invalid pool url.';
        }
        if ($config->allow_offline_pools) {
            return true;
        }
        return $connection_check->check_pool($url_parts['host'], $url_parts['port'], (isset($url_parts['scheme']) && $url_parts['scheme'] === 'stratum+tcp') ? 'stratum' : 'http', $user, $pass);
    }

    /**
     * Remove the given pool from the given group.
     * 
     * @param string $user
     *   The user.
     * @param string $pass
     *   The password.
     * @param string $group
     *   The group to retrieve. (optional, default="default")
     */
    public function remove_pool($url, $user, $group = 'default') {
        $this->remove_pool_by_uuid($this->get_pool_uuid($url, $user), $group);
    }
    
    /**
     * Remove the given pool from the given group.
     * 
     * @param string $uuid
     *   The uuid.
     * @param string $group
     *   The group to retrieve. (optional, default="default")
     * 
     * @return boolean
     *   True if pool was found and removed, else false.
     */
    public function remove_pool_by_uuid($uuid, $group = 'default') {
        
        // Only remove if group exists.
        if (isset($this->config[$group])) {
            
            // Remove the pool from the given group if exists.
            if (isset($this->config[$group][$uuid])) {
                unset($this->config[$group][$uuid]);
                
                // Save the new values.
                $this->save();
                return true;
            }
        }
        return false;
    }
    
    /**
     * Updates the given pool identified by the pool id in all groups where it exists.
     * 
     * @param string $old_pool_id
     *   The old pool uuid to update.
     * @param string $url
     *   The new url.
     * @param string $user
     *   The new user.
     * @param string $pass
     *   The new password.
     * @param int $quota
     *   The quota (Optional, default = 1).
     * @param boolean $rig_based
     *   If this pool is rigbased or not. (Optional, default = false)
     */
    public function update_pool($old_pool_id, $url, $user, $pass, $quota = 1, $rig_based = false) {
        
        // Loop through each pool.
        foreach ($this->config AS $pool_group => $pool_data) {
            
            // Check if pool is within the current group.
            if (isset($pool_data[$old_pool_id])) {
                // Remove the old pool.
                unset($this->config[$pool_group][$old_pool_id]);
                
                // Add the pool with the new data.
                $this->add_pool($url, $user, $pass, $pool_group, $quota, $rig_based);
            }
            
        }
    }
    
    /**
     * Returns all configurated pool group names.
     * 
     * @return array
     *   The pool group names.
     */
    public function get_groups() {
        return array_keys($this->config);
    }

    /**
     * Returns all pools within the given group.
     * 
     * @param string $group
     *   The group to retrieve. (optional, default="default")
     * 
     * @return array
     *   The pools within this group.
     */
    public function get_pools($group = 'default') {
        // No group with this name exist, return an empty array.
        if (!isset($this->config[$group])) {
            return array();
        }
        // Return pools.
        $pools = $this->config[$group];
        unset($pools['settings']);
        return $pools;
    }
    
    /**
     * We need to override this method to remove the settings key for each pool by default.
     * We can get the real complete config when we set the parameter $with_settings to true.
     * 
     * @params boolean $with_settings
     *   If set to true the setting key will be not removed.
     * 
     * @return array
     *   The config.
     */
    public function get_config($with_settings = false) {
        $conf = parent::get_config();
        if (!$with_settings) {
            foreach ($conf AS &$group) {
                unset($group['settings']);
            }
        }
        return $conf;
    }

    /**
     * Returns the pool group strategy.
     * 
     * @param string $group
     *   The group name. (optional, default="default")
     * 
     * @return int
     *   The pool strategy.
     */
    public function get_strategy($group = 'default') {
        if (isset($this->config[$group]) && isset($this->config[$group]['strategy'])) {
            return $this->config[$group]['strategy'];
        }
        // Return failover if no pool or no strategy found.
        return 0;
    }
    
    /**
     * Returns the pool group period.
     * 
     * @param string $group
     *   The group name. (optional, default="default")
     * 
     * @return int
     *   The pool period.
     */
    public function get_period($group = 'default') {
        if (isset($this->config[$group]) && isset($this->config[$group]['period'])) {
            return $this->config[$group]['period'];
        }
        // Return failover if no pool or no strategy found.
        return 0;
    }
    
    /**
     * Returns the given pool from given group.
     * 
     * @param string $uid
     *   The pool uuid.
     * @param string $group
     *   The group name. (optional, default="default")
     * 
     * @return array
     *   The pool data.
     */
    public function get_pool($uid, $group = 'default') {
        // If the group and/or the pool in the group does not exist, return empty array.
        if (!isset($this->config[$group]) || !isset($this->config[$group][$uid])) {
            return array();
        }
        return $this->config[$group][$uid];
    }
    
    /**
     * Deletes a hole group.
     * 
     * @param string $group
     *   The group to be deleted.
     * 
     * @return boolean|string
     *   Boolean true if group could be deleted, else the error as a string.
     */
    public function del_group($group) {
        if (isset($this->config[$group])) {
            unset($this->config[$group]);
            $this->save();
            return true;
        }
        else {
            return "Group does not exist.";
        }
    }
    
    /**
     * Add a group.
     * 
     * @param string $group
     *   The group to be added.
     * @param int $strategy
     *   The group strategy (optional, default = 0)
     * @param int $period
     *   The rotate period in minutes for pool strategy 2:Rotate (optional, default = 0)
     * 
     * @return boolean|string
     *   Boolean true if group could be added, else the error as a string.
     */
    public function add_group($group, $strategy = 0, $period = 0) {
        if (!isset($this->config[$group])) {
            $this->config[$group] = array(
                'settings' => array(
                    'strategy' => $strategy,
                    'period' => $period,
                ),
            );
            $this->save();
            return true;
        }
        else {
            return "Group already exists.";
        }
    }
    
    /**
     * Checks if the given group exists.
     * 
     * @param string $group
     *   The group to checked.
     * 
     * @return boolean
     *   true if exists, else false.
     */
    public function group_exists($group) {
        return isset($this->config[$group]);
    }

}
