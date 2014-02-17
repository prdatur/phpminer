<?php
/**
 * @copyright Christian Ackermann (c) 2013 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */

/**
 * Represents the config of php cgminer.
 */
class AccessConfig extends Config {

    const TYPE_USER = 'users';
    const TYPE_GROUP = 'groups';
        
    /**
     * Creates a new instance of the pool config.
     */
    public function __construct() {
        parent::__construct(SITEPATH . '/config/access.json');
    }
    
    /**
     * Add the user to the given group.
     * 
     * @param string $user
     *   The user.
     * @param string $pass
     *   The password.
     * @param string $group
     *   The group. (optional, default="default")
     * 
     * @return boolean
     *   True on success, else false.
     */
    public function user_add($user, $pass, $group = 'default') {
        
        // Create the group if it doesn't exist yet.
        if (!isset($this->config[self::TYPE_USER])) {
            $this->config[self::TYPE_USER] = array();
        }

        // Add the pool to the group.
        $this->config[self::TYPE_USER][$user] = array(
            'pass' => $pass,
            'group' => $group
        );
        
        return $this->save();
    }
    
    /**
     * Remove the given user.
     * 
     * @param string $user
     *   The user.
     * 
     * @return boolean
     *   True on success, else false.
     */
    public function user_del($user) {
        if (isset($this->config[self::TYPE_USER]) && isset($this->config[self::TYPE_USER][$user])) {
            unset($this->config[self::TYPE_USER][$user]);
            return $this->save();
        }
        return false;        
    }
        
    /**
     * Updates the given user.
     * 
     * @param string $old_user
     *   The old username.
     * @param string $user
     *   The new user.
     * @param string $pass
     *   The new password.
     *   The new user. (Optional, default = null)
     * @param string $group
     *   The new group. (Optional, default = null)
     * 
     * @return boolean
     *   True if success, else false.
     */
    public function user_update($old_user, $user, $pass = null, $group = null) {
        
        if (isset($this->config[self::TYPE_USER]) && isset($this->config[self::TYPE_USER][$old_user])) {
            $old = $this->config[self::TYPE_USER][$user];
            unset($this->config[self::TYPE_USER][$user]);
            
            if ($pass !== null) {
                $old['pass'] = $pass;
            }
            if ($group !== null) {
                $old['group'] = $group;
            }
            $this->config[self::TYPE_USER][$user] = $old;
            return $this->save();
        }
        return false;
    }
    
    /**
     * Returns all configurated users.
     * 
     * @return array
     *   The user names.
     */
    public function user_get() {
        if (!is_array($this->config[self::TYPE_USER])) {
            return array();
        }
        return array_keys($this->config[self::TYPE_USER]);
    }
    
    /**
     * Checks if the given user exists.
     * 
     * @param string $user
     *   The user to checked.
     * 
     * @return boolean
     *   true if exists, else false.
     */
    public function user_exists($user) {
        return isset($this->config[self::TYPE_USER]) && isset($this->config[self::TYPE_USER][$user]);
    }

    /**
     * Add a group.
     * 
     * @param string $group
     *   The group.
     * 
     * @return boolean
     *   True if success, else false.
     */
    public function group_add($group) {
        if (!isset($this->config[self::TYPE_GROUP])) {
            $this->config[self::TYPE_GROUP] = array();
        }
        
        if (!isset($this->config[self::TYPE_GROUP][$group])) {
            $this->config[self::TYPE_GROUP][$group] = array(
                'name' => $group,
                'permissions' => array(),
            );
        }
        return $this->save();
    }
    
    /**
     * Grants the given permission to the given group.
     * 
     * @param string $group
     *   The group.
     * @param string $permission
     *   The permission.
     * 
     * @return boolean
     *   True if success, else false.
     */
    public function group_grant_permission($group, $permission) {
        if (!isset($this->config[self::TYPE_GROUP]) || !isset($this->config[self::TYPE_GROUP][$group])) {
            return false;
        }
        
        $this->config[self::TYPE_GROUP][$group]['permissions'][$permission] = true;
        return $this->save();
    }
    
    /**
     * Revokes the given permission to the given group.
     * 
     * @param string $group
     *   The group.
     * @param string $permission
     *   The permission.
     * 
     * @return boolean
     *   True if success, else false.
     */
    public function group_revoke_permission($group, $permission) {
        if (!isset($this->config[self::TYPE_GROUP]) || !isset($this->config[self::TYPE_GROUP][$group])) {
            return true;
        }
        
        // Remove permission if exists.
        if (isset($this->config[self::TYPE_GROUP][$group]['permissions'][$permission])) {
            unset($this->config[self::TYPE_GROUP][$group]['permissions'][$permission]);
        }
        return $this->save();
    }
    
    /**
     * Update a group.
     * 
     * @param string $group
     *   The group.
     * 
     * @return boolean
     *   True on success, else false.
     */
    public function group_update($group) {
        if (!isset($this->config[self::TYPE_GROUP]) || !isset($this->config[self::TYPE_GROUP][$group])) {
            return false;
        }
        
        $this->config[self::TYPE_GROUP][$group] = array();
        return $this->save();
    }
    
    /**
     * Delete group.
     * 
     * @param string $group
     *   The group.
     * 
     * @return boolean
     *   True on success, else false.
     */
    public function group_delete($group) {
        // No group with this name exist, return an empty array.
        if (!isset($this->config[self::TYPE_GROUP]) || !isset($this->config[self::TYPE_GROUP][$group])) {
            return false;
        }
        unset($this->config[self::TYPE_GROUP][$group]);
        return $this->save();
    }
    
    /**
     * Returns all group names or a single group if $group is provided.
     * 
     * @param string $group
     *   When provided it returns the given group. (Optional, default = null)
     * 
     * @return array|boolean
     *   The group names or the hole group entry if $group is provided.
     *   If $group is provided but doesn't exists it returns false.
     */
    public function group_get($group = null) {
        // No group with this name exist, return an empty array.
        if (!isset($this->config[self::TYPE_GROUP]) || !is_array($this->config[self::TYPE_GROUP])) {
            if ($group !== null) {
                return false;
            }
            return array();
        }
        if ($group !== null) {
            if (!isset($this->config[self::TYPE_GROUP][$group])) {
                return false;
            }
            return $this->config[self::TYPE_GROUP][$group];
        }
        return array_keys($this->config[self::TYPE_GROUP]);
    }
    
    /**
     * Checks if group is empty.
     * 
     * @param string $group
     *   The group.
     * 
     * @return boolean
     *   True if group empty, else false.
     */
    public function group_is_empty($group) {
        // No group with this name exist, return an empty array.
        if (!isset($this->config[self::TYPE_GROUP]) || !isset($this->config[self::TYPE_GROUP][$group]) || !isset($this->config[self::TYPE_USER]) || !is_array($this->config[self::TYPE_USER])) {
            return true;
        }
        
        //Loop through each user and check if a user has this group.
         
        foreach ($this->config[self::TYPE_USER] AS $user) {
            if ($user['group'] === $group) {
                return false;
            }
        }
        
        // No match, group is empty.
        return true;
        
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
        return isset($this->config[self::TYPE_GROUP]) && isset($this->config[self::TYPE_GROUP][$group]);
    }

}
