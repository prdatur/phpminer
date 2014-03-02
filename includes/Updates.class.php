<?php


class Update {
    
    public function __construct() {
        
        // Main config
        Db::getInstance()->exec('
            CREATE TABLE IF NOT EXISTS "config" (
                "type" varchar(255) CHARACTER SET latin1 NOT NULL,
                "key" varchar(255) CHARACTER SET latin1 NOT NULL,
                "value" text CHARACTER SET latin1 NOT NULL,
                PRIMARY KEY ("type","key")
            );
        ');

        $last_update = Db::getInstance()->querySingle('SELECT "value" FROM "config" WHERE "key" = :key AND "type" = :type', false, array(
            ':key' => 'last_update',
            ':type' => 'config',
        ));
        if (empty($last_update)) {
            $last_update = 0;
        }
        
        $orig_last_update = $last_update;
        $class_reflection = new ReflectionClass($this);
        $required_updates = array();
        
        foreach ($class_reflection->getMethods() AS $method) {
            /* @var $method ReflectionMethod */
            $matches = array();
            if (preg_match("/update_([0-9]+)$/", $method->getName(), $matches)) {
                if (intval($matches[1]) <= $last_update) {
                    continue;
                }
                $required_updates[intval($matches[1])] = $matches[0];
            }
        }
        ksort($required_updates);
        
        foreach ($required_updates as $update_number => $update) {
            $this->{$update}();
            $last_update = $update_number;
        }
        
        if (empty($orig_last_update)) {
            Db::getInstance()->exec('INSERT INTO "config" ("value", "key", "type") VALUES (:value, :key, :type)', array(
                ':value' => $last_update,
                ':key' => 'last_update',
                ':type' => 'config',
            ));
        }
        else {
            Db::getInstance()->exec('UPDATE "config" SET "value" = :value WHERE "key" = :key AND "type" = :type', array(
                ':value' => $last_update,
                ':key' => 'last_update',
                ':type' => 'config',
            ));
        }
    }
    
    /**
     * Implemented sqlite structure, import old config to db.
     */
    public function update_1() {
        
        // Users
        Db::getInstance()->exec('
          CREATE TABLE IF NOT EXISTS "group2perm" (
            "group_name" varchar(255) NOT NULL,
            "permission" varchar(255) NOT NULL,
            PRIMARY KEY ("group_name","permission")
          );

          CREATE TABLE IF NOT EXISTS "groups" (
            "name" varchar(255) NOT NULL,
            PRIMARY KEY ("name")
          );

          CREATE TABLE IF NOT EXISTS "pools" (
            "uuid" varchar(255) NOT NULL,
            "url" varchar(255) NOT NULL,
            "user" varchar(255) NOT NULL,
            "pass" varchar(255) NOT NULL,
            "group" varchar(255) NOT NULL,
            "quota" int(10) unsigned NOT NULL,
            "rig_based" int(10) unsigned NOT NULL,
            PRIMARY KEY ("uuid","group"),
            KEY "group" ("group")
          );

          CREATE TABLE IF NOT EXISTS "pool_groups" (
            "name" varchar(255) NOT NULL,
            "strategy" varchar(255) NOT NULL,
            "period" int(11) NOT NULL,
            PRIMARY KEY ("name")
          );

          

          CREATE TABLE IF NOT EXISTS "users" (
            "username" varchar(255) NOT NULL,
            "password" varchar(255) NOT NULL,
            "group" varchar(255) NOT NULL,
            PRIMARY KEY ("username")
          );
        ');
        
        $config_file = SITEPATH . '/config/config.json';
        if (file_exists($config_file)) {
            $json_config = json_decode(file_get_contents($config_file), true);
            if ($json_config !== false) {

                $rigs = array();
                if (isset($json_config['rigs']) && is_array($json_config['rigs'])) {
                    $rigs = $json_config['rigs'];
                    unset($json_config['rigs']);
                }
                
                foreach ($json_config AS $k=> $v) {

                    Db::getInstance()->exec('INSERT INTO "config" ("value", "key", "type") VALUES (:value, :key, :type)', array(
                        ':value' => json_encode($v), 
                        ':key' => $k, 
                        ':type' => 'config'
                    ));
                }

                foreach ($rigs AS $rig => $rig_data) {
                    Db::getInstance()->exec('INSERT INTO "config" ("value", "key", "type") VALUES (:value, :key, :type)', array(
                        ':value' => json_encode($rig_data),
                        ':key' => $rig,
                        ':type' => 'rigs'
                    ));
                }
               
            }
        }
        
        $config_file = SITEPATH . '/config/pools.json';
        if (file_exists($config_file)) {
            $json_config = json_decode(file_get_contents($config_file), true);
            if ($json_config !== false) {
                foreach ($json_config AS $group => $group_data) {
                    
                    if (!isset($group_data['settings'])) {
                        $group_data['settings'] = array();
                    }
                    
                    $group_settings = $group_data['settings'];
                    unset($group_data['settings']);
                    
                    $group_settings['strategy'] = (isset($group_settings['strategy'])) ? $group_settings['strategy'] : 0;
                    $group_settings['period'] = (isset($group_settings['period'])) ? $group_settings['period'] : 0;
                    Db::getInstance()->exec('INSERT INTO "pool_groups" ("name", "strategy", "period") VALUES (:group, :strategy, :period)', array(
                        ':group' => $group,
                        ':strategy' => $group_settings['strategy'],
                        ':period' => $group_settings['period']
                    ));
 
                    foreach ($group_data AS $pool => $pool_data) {
                        $pool_data['rig_based'] = (isset($pool_data['rig_based'])) ? $pool_data['rig_based'] : false;
                        Db::getInstance()->exec('INSERT INTO "pools" ("uuid", "url", "user", "pass", "group", "quota", "rig_based") VALUES (:uuid, :url, :user, :pass, :group, :quota, :rig_based)', array(
                            ':uuid' => $pool,
                            ':url' => $pool_data['url'],
                            ':user' => $pool_data['user'],
                            ':pass' => $pool_data['pass'],
                            ':group' => $pool_data['group'],
                            ':quota' => $pool_data['quota'],
                            ':rig_based' => $pool_data['rig_based']
                        ));
                    }
                }
            }
        }
        
        $config_file = SITEPATH . '/config/notify.json';
        if (file_exists($config_file)) {
            $json_config = json_decode(file_get_contents($config_file), true);
            if ($json_config !== false) {

                $rigs = $json_config['rigs'];
                unset($json_config['rigs']);
                foreach ($json_config AS $k=> $v) {
                    Db::getInstance()->exec('INSERT INTO "config" ("value", "key", "type") VALUES (:value, :key, :type)', array(
                        ':value' => json_encode($v),
                        ':key' => $k,
                        ':type' => 'notify',
                    ));
                }
            }
        }
    }
    
}