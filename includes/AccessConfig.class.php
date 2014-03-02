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
        $tmp =  Db::getInstance()->querySingle('SELECT 1 FROM "users" LIMIT 1');
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
        return Db::getInstance()->exec('INSERT INTO "users" ("username", "password", "group") VALUES (:user, :pass, :group)', array(
            ':user' => $user,
            ':pass' => $pw_hash->hash_password($pass),
            ':group' => $group,
        ));
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
        return Db::getInstance()->exec('DELETE FROM "users" WHERE "username" = :user', array(
            ':user' => $user,
        ));
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

        $values = array(
            ':user' => $user,
            ':old_user' => $old_user,
            ':group' => $group,
        );
        $pw = '';
        if ($pass !== null) {
            $pw_hash = new PasswordHash();
            $pw = ', "password" = :password';
            
            $values[':password'] = $pw_hash->hash_password($pass);
        }
        return Db::getInstance()->exec('UPDATE "users" SET "username" = :user' . $pw . ', "group" = :group WHERE "username" = :old_user', $values);
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
            return Db::getInstance()->querySingle('SELECT * FROM "users" WHERE "username" = :user LIMIT 1', true, array(
                ':user' => $username,
            ), true);
        }
        $sql = Db::getInstance()->query('SELECT "username" FROM "users"');
        $result = array();
        if ($sql instanceof PDOStatement) {
            while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
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
        $tmp = Db::getInstance()->querySingle('SELECT 1 FROM "users" WHERE "username" = :user LIMIT 1', false, array(
            ':user' => $user,
        ));
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
        return Db::getInstance()->exec('INSERT INTO "groups" ("name") VALUES (:group)', array(
            ':group' => $group,
        ));
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
        $values = array(
            ':group' => $group,
            ':old' => $old,
        );
        Db::getInstance()->exec('UPDATE "users" SET "group" = :group WHERE "group" = :old', $values);
        return Db::getInstance()->exec('UPDATE "groups" SET "name" = :group WHERE "name" = :old', $values);
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
        Db::getInstance()->exec('INSERT INTO "group2perm" ("group_name","permission") VALUES (:group, :perm)', array(
            ':group' => $group,
            ':perm' => $permission,
        ));
        return true;
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
        return Db::getInstance()->exec('DELETE FROM "group2perm" WHERE "group_name" = :group AND "permission" = :perm', array(
            ':group' => $group,
            ':perm' => $permission,
        ));
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
        return Db::getInstance()->exec('DELETE FROM "group2perm" WHERE "group_name" = :group', array(
            ':group' => $group,
        ));
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
        $sql = Db::getInstance()->query('SELECT * FROM "group2perm" WHERE "group_name" = :group', array(
            ':group' => $group,
        ));
        $result = array();
        if ($sql instanceof PDOStatement) {
            while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
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
        Db::getInstance()->exec('DELETE FROM "group2perm" WHERE "group_name" = :group', array(
            ':group' => $group,
        ));
        return Db::getInstance()->exec('DELETE FROM "groups" WHERE "name" = :group', array(
            ':group' => $group,
        ));
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
            return Db::getInstance()->querySingle('SELECT * FROM "groups" WHERE "name" = :group LIMIT 1', true, array(
                ':group' => $group,
            ));
        }
        $sql = Db::getInstance()->query('SELECT "name" FROM "groups"');
        $result = array();
        if ($sql instanceof PDOStatement) {
            while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
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
        $tmp = Db::getInstance()->querySingle('SELECT 1 FROM "users" WHERE "group" = :group LIMIT 1', false, array(
            ':group' => $group,
        ));
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
        $tmp = Db::getInstance()->querySingle('SELECT 1 FROM "groups" WHERE "name" = :group LIMIT 1', false, array(
            ':group' => $group
        ));
        return !empty($tmp);
    }

}
