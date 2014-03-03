<?php
/**
 * @copyright Christian Ackermann (c) 2013 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */

/**
 * Represents the config of php cgminer.
 */
class PoolConfig {
        
    /**
     * Returns wether the config file is empty or not.
     * 
     * @return boolean
     *   True if config is empty, else false.
     */
    public function is_pools_empty() {
        $tmp =  Db::getInstance()->querySingle('SELECT 1 FROM "pools" LIMIT 1');
        return empty($tmp);
    }
    
    /**
     * Returns wether the config file is empty or not.
     * 
     * @return boolean
     *   True if config is empty, else false.
     */
    public function is_empty() {
        $tmp =  Db::getInstance()->querySingle('SELECT 1 FROM "pool_groups" LIMIT 1');
        return empty($tmp);
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
        if (!$this->group_exists($group)) {
            $this->add_group($group);
        }
        
        Db::getInstance()->exec('INSERT INTO "pools" ("uuid", "url", "user", "pass", "group", "quota", "rig_based") VALUES (:uuid, :url, :user, :pass, :group, :quota, :rig_based)', array(
            ':uuid' => $this->get_pool_uuid($url, $user),
            ':url' => $url,
            ':user' => $user,
            ':pass' => $pass,
            ':group' => $group,
            ':quota' => $quota,
            ':rig_based' => ($rig_based) ? 1 : 0
        ));
    }
    
    /**
     * Returns the current active pool group.
     * It will search within the configurated pool groups if the current configurated cgminer pools are match all pools in a group.
     * 
     * @param PHPMinerRPC $rpc
     *   The cgminer api, it's needed to retrieve current configurated pools within cgminer.
     * 
     * @return null|array
     *   the active pool group as an array or null if no active group could be found.
     */
    public function get_current_active_pool_group_from_rpc($rpc) {
        $config = $rpc->get_config();
        $pools = array();
        if (empty($config['pools'])) {
            return null;
        }
        foreach ($config['pools'] AS $pool) {
            $pools[] = array(
                'URL' => $pool['url'],
                'User' => $pool['user'],
            );
        }
        return $this->get_current_active_pool_group_from_pools($pools);
        
    }
    
    /**
     * Returns the current active pool group.
     * It will search within the configurated pool groups if the current configurated cgminer pools are match all pools in a group.
     * 
     * @param PHPMinerRPC $api
     *   The cgminer api, it's needed to retrieve current configurated pools within cgminer.
     * 
     * @return null|array
     *   the active pool group as an array or null if no active group could be found.
     */
    public function get_current_active_pool_group($api) {
        return $this->get_current_active_pool_group_from_pools($api->get_pools());
    }
    
    /**
     * Returns the current active pool group.
     * It will search within the configurated pool groups if the current configurated cgminer pools are match all pools in a group.
     * 
     * @param array $pools
     *   The pools.
     * 
     * @return null|array
     *   the active pool group as an array or null if no active group could be found.
     */
    private function get_current_active_pool_group_from_pools($pools) {
       
        $where_array = array();
        $values = array();
        $i = 0;
        // Loop through each pool wich are active within cgminer.
        foreach ($pools AS $k => $pool) {
            $pools[$k]['uuid'] = $this->get_pool_uuid($pool['URL'], $pool['User']);
            $where_array[] = '"uuid" = :uuid_' . $i; 
            $values[':uuid_' . $i] = $pools[$k]['uuid'];
            $i++;
        }
        
        $result = array();
        $sql = Db::getInstance()->query('SELECT * FROM "pools" WHERE ' . implode(" OR ", $where_array), $values);
        if ($sql instanceof PDOStatement) {
            while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
               $result[$row['group']] = $row['group'];
            }
        }
        
        $cfg_groups = array();
        foreach ($result AS $possible_group) {
            if (!$this->group_exists($possible_group)) {
                continue;
            }
            $cfg_groups[$possible_group] = $this->get_pools($possible_group);
        } 
        
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
        $user = preg_replace("/.rb[a-zA-Z0-9]+$/", "", $user);
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
     * 
     * @return boolean|string
     *   True if all ok, else the error as a human readable string.
     */
    public function check_pool($url, $user, $pass) {        
        // Get the url parts.
        $url_parts = parse_url($url);
        $connection_check = new HttpClient();
        if (!isset($url_parts['host'])) {
            return 'Invalid pool url.';
        }
        if (Config::getInstance()->allow_offline_pools) {
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
        Db::getInstance()->exec('DELETE FROM "pools"  WHERE "uuid" = :uuid AND "group" = :group', array(
            ':uuid' => $uuid,
            ':group' => $group,
        ));
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
     * 
     */
    public function update_pool($old_pool_id, $url, $user, $pass, $quota = 1, $rig_based = false) {
        Db::getInstance()->exec('UPDATE "pools" SET "uuid" = :uuid,  "url" = :url, "user" = :user, "pass" = :pass, "quota" = :quota, "rig_based" = :rig_based WHERE "uuid" = :old_pool_id', array(
            ':uuid' => $this->get_pool_uuid($url, $user),
            ':url' => $url,
            ':user' => $user,
            ':user' => $user,
            ':pass' => $pass,
            ':quota' => $quota,
            ':rig_based' => $rig_based,
            ':old_pool_id' => $old_pool_id,
        ));
    }
    
    /**
     * Returns the group data..
     * 
     * @param string $group
     *   The group name
     * 
     * @return array
     *   The pool group data.
     */
    public function get_group($group) {
        $row = Db::getInstance()->querySingle('SELECT * FROM "pool_groups" WHERE "name" = :group', true, array(
            ':group' => $group,
        ));
        $row['strategy'] = intval($row['strategy']);
        $row['period'] = intval($row['period']);
        return $row;
    }
    
    /**
     * Returns all configurated pool group names.
     * 
     * @return array
     *   The pool group names.
     */
    public function get_groups() {
        $sql = Db::getInstance()->query('SELECT * FROM "pool_groups"');
        $result = array();
        if ($sql instanceof PDOStatement) {
            while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
                $result[] = $row['name'];
            }
        }
        return $result;
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
        $sql = Db::getInstance()->query('SELECT * FROM "pools" WHERE "group" = :group', array(
            ':group' => $group,
        ));
        $result = array();
        if ($sql instanceof PDOStatement) {
            while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
                $row['rig_based'] = !empty($row['rig_based']);
                $row['quota'] = intval($row['quota']);
               $result[$row['uuid']] = $row;
            }
        }
        return $result;
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
        $tmp = Db::getInstance()->querySingle('SELECT "strategy" FROM "pool_groups" WHERE "name" = :group', false, array(
            ':group' => $group,
        ));
        return (!empty($tmp)) ? $tmp : 0;
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
        $tmp = Db::getInstance()->querySingle('SELECT "period" FROM "pool_groups" WHERE "name" = :group', false, array(
            ':group' => $group,
        ));
        return (!empty($tmp)) ? $tmp : 0;
    }
    
    /**
     * Returns the given pool from given group.
     * 
     * @param string $uuid
     *   The pool uuid.
     * @param string $group
     *   The group name. (optional, default="default")
     * 
     * @return array
     *   The pool data.
     */
    public function get_pool($uuid, $group = 'default') {
        $data = Db::getInstance()->querySingle('SELECT * FROM "pools"  WHERE "uuid" = :uuid AND "group" = :group', true, array(
            ':uuid' => $uuid,
            ':group' => $group,
        ));
        $data['rig_based'] = !empty($data['rig_based']);
        $data['quota'] = intval($data['quota']);
        return $data;
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
        if ($this->group_exists($group)) {
            Db::getInstance()->exec('DELETE FROM "pool_groups"  WHERE "name" = :group', array(
                ':group' => $group,
            ));
            Db::getInstance()->exec('DELETE FROM "pools" WHERE "group" = :group', array(
                ':group' => $group,
            ));
            return true;
        }
        else {
            return "Group does not exist.";
        }
    }
    
    /**
     * Update a group.
     * 
     * @param string $old_group
     *   The old group name.
     * @param string $group
     *   The new group name.
     * @param int $strategy
     *   The group strategy (optional, default = 0)
     * @param int $period
     *   The rotate period in minutes for pool strategy 2:Rotate (optional, default = 0)
     * 
     * @return boolean|string
     *   Boolean true if group could be added, else the error as a string.
     */
    public function update_group($old_group, $group, $strategy = 0, $period = 0) {
        if ($this->group_exists($old_group)) {
            Db::getInstance()->exec('UPDATE "pool_groups" SET "name" = :group, "strategy" = :strategy, "period" = :period WHERE "name" = :old', array(
                ':group' => $group,
                ':strategy' => $strategy,
                ':period' => $period,
                ':old' => $old_group,
            ));
            Db::getInstance()->exec('UPDATE "pools" SET "group" = :group WHERE "group" = :old', array(
                ':group' => $group,
                ':old' => $old_group,
            ));
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
        try {
            Db::getInstance()->exec('INSERT INTO "pool_groups" ("name", "strategy", "period") VALUES (:group, :strategy, :period)', array(
                ':group' => $group,
                ':strategy' => $strategy,
                ':period' => $period,
            ));
            return true;
        }
        catch(Exception $e) {
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
        $tmp = Db::getInstance()->querySingle('SELECT 1 FROM "pool_groups" WHERE "name" = :group LIMIT 1', false, array(
            ':group' => $group
        ));
        return !empty($tmp);
    }

}