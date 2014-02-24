<?php
/**
 * @copyright Christian Ackermann (c) 2013 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */

/**
 * Represents the config of php cgminer.
 */
class AccessConfig {
       
    
    /**
     * Returns wether the config file is empty or not.
     * 
     * @return boolean
     *   True if config is empty, else false.
     */
    public function is_empty() {
        $tmp =  Db::getInstance()->querySingle("SELECT 1 FROM [users] LIMIT 1");
        return empty($tmp);
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
        $pw_hash = new PasswordHash();
        return Db::getInstance()->exec("INSERT INTO [users] ([username],[password],[group]) VALUES ('" . SQLite3::escapeString($user) . "', '" . SQLite3::escapeString($pw_hash->hash_password($pass)) . "', '" . SQLite3::escapeString($group) . "')");
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
        return Db::getInstance()->exec("DELETE FROM [users] WHERE [username] = '" . SQLite3::escapeString($user) . "'");
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
        
        $pw = '';
        if ($pass !== null) {
            $pw_hash = new PasswordHash();
            $pw = ", [password] = '" . SQLite3::escapeString($pw_hash->hash_password($pass)) . "'";
        }
        
        $sql = "UPDATE [users] SET [username] = '" . SQLite3::escapeString($user) . "'" . $pw . ",[group] = '" . SQLite3::escapeString($group) . "' WHERE [username] = '" . SQLite3::escapeString($old_user) . "'";
        return Db::getInstance()->exec($sql);
    }
    
    /**
     * Returns all configurated usernames or if $username is given the user entry if exists.
     * 
     * @param string $username
     *   The username, if provided it will return the user entry instead of all usernames. (Optional, default = null)
     * @return array|boolean
     *   The user names if $username is ommited, else the user entry or false if it doesn't exists.
     */
    public function user_get($username = null) {
        
        if ($username !== null) {
            return Db::getInstance()->querySingle("SELECT * FROM [users] WHERE [username] = '" . SQLite3::escapeString($username) . "' LIMIT 1", true);
        }
        $sql = Db::getInstance()->query("SELECT [username] FROM [users]");
        $result = array();
        if ($sql instanceof SQLite3Result) {
            while ($row = $sql->fetchArray()) {
               $result[] = $row['username'];
            }
        }
        return $result;
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
        $tmp = Db::getInstance()->querySingle("SELECT 1 FROM [users] WHERE [username] = '" . SQLite3::escapeString($user) . "' LIMIT 1");
        return !empty($tmp);
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
        return Db::getInstance()->exec("INSERT INTO [groups] ([name]) VALUES ('" . SQLite3::escapeString($group) . "')");
    }
    
    /**
     * Change a group.
     * 
     * @param string $old
     *   The old group.
     * @param string $group
     *   The group.
     * 
     * @return boolean
     *   True if success, else false.
     */
    public function group_change($old, $group) {
        Db::getInstance()->exec("UPDATE [users] SET [group] = '" . SQLite3::escapeString($group) . "' WHERE  [group] = '" . SQLite3::escapeString($old) . "'");
        return Db::getInstance()->exec("UPDATE [groups] SET [name] = '" . SQLite3::escapeString($group) . "' WHERE  [name] = '" . SQLite3::escapeString($old) . "'");
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
        return Db::getInstance()->exec("INSERT OR REPLACE INTO [group2perm] ([group_name],[permission]) VALUES ('" . SQLite3::escapeString($group) . "', '" . SQLite3::escapeString($permission) . "')");
    }
    
    /**
     * Revokes the given permission for the given group.
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
        return Db::getInstance()->exec("DELETE FROM [group2perm] WHERE [group_name] = '" . SQLite3::escapeString($group) . "' AND [permission] = '" . SQLite3::escapeString($permission) . "'");
    }
    
    /**
     * Revokes all permissions for the given group.
     * 
     * @param string $group
     *   The group.
     * 
     * @return boolean
     *   True if success, else false.
     */
    public function group_revoke_all_permission($group) {
        return Db::getInstance()->exec("DELETE FROM [group2perm] WHERE [group_name] = '" . SQLite3::escapeString($group) . "'");
    }
    
    /**
     * Get all permissions for the given group.
     * 
     * @param string $group
     *   The group.
     * 
     * @return boolean
     *   True if success, else false.
     */
    public function group_get_permission($group) {
        $sql = Db::getInstance()->query("SELECT * FROM [group2perm] WHERE [group_name] = '" . SQLite3::escapeString($group) . "'");
        $result = array();
        if ($sql instanceof SQLite3Result) {
            while ($row = $sql->fetchArray()) {
               $result[$row['permission']] = true;
            }
        }
        return $result;
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
        return Db::getInstance()->exec("DELETE FROM [groups] WHERE [name] = '" . SQLite3::escapeString($group) . "'");
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
        if ($group !== null) {
            return Db::getInstance()->querySingle("SELECT * FROM [groups] WHERE [name] = '" . SQLite3::escapeString($group) . "' LIMIT 1", true);
        }
        $sql = Db::getInstance()->query("SELECT [name] FROM [groups]");
        $result = array();
        if ($sql instanceof SQLite3Result) {
            while ($row = $sql->fetchArray()) {
               $result[] = $row['name'];
            }
        }
        return $result;
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
        $tmp = Db::getInstance()->querySingle("SELECT 1 FROM [users] WHERE [group] = '" . SQLite3::escapeString($group) . "' LIMIT 1");
        return empty($tmp);
        
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
        $tmp = Db::getInstance()->querySingle("SELECT 1 FROM [groups] WHERE [name] = '" . SQLite3::escapeString($group) . "' LIMIT 1");
        return !empty($tmp);
    }

}
