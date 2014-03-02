<?php


class Update {
    
    public function __construct() {
        
        // Main config
        Db::getInstance()->exec('CREATE TABLE IF NOT EXISTS [config] ([key] VARCHAR, [value] TEXT, [type] VARCHAR, PRIMARY KEY ([key], [type]));');
        Db::getInstance()->exec('CREATE INDEX IF NOT EXISTS key_typ ON [config] ([key],[type]);');
        Db::getInstance()->exec('CREATE INDEX IF NOT EXISTS cfg_typ ON [config] ([type]);');

        $last_update = Db::getInstance()->querySingle("SELECT [value] FROM [config] WHERE [key] = 'last_update' AND [type] = 'config'");

        if (empty($last_update)) {
            $last_update = 0;
        }
        Db::getInstance()->begin();
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
        Db::getInstance()->exec("INSERT OR REPLACE INTO [config] ([value], [key], [type]) VALUES ('" . SQLite3::escapeString($last_update). "', 'last_update', 'config')");
        Db::getInstance()->commit();
    }
    
    /**
     * Implemented sqlite structure, import old config to db.
     */
    public function update_1() {
        
        // Users
        Db::getInstance()->exec('CREATE TABLE IF NOT EXISTS [users] ([username] VARCHAR PRIMARY KEY, [password] VARCHAR, [group] VARCHAR)');
        
        // Access groups
        Db::getInstance()->exec('CREATE TABLE IF NOT EXISTS [groups] ([name] VARCHAR PRIMARY KEY)');
        
        Db::getInstance()->exec('CREATE TABLE IF NOT EXISTS [group2perm] ([group_name] VARCHAR, [permission] VARCHAR, PRIMARY KEY ([group_name], [permission]));');
        
        // Pools
        Db::getInstance()->exec('CREATE TABLE IF NOT EXISTS [pools] ([uuid] VARCHAR, [url] VARCHAR, [user] VARCHAR, [pass] VARCHAR, [group] VARCHAR, [quota] INTEGER, [rig_based] BOOLEAN, PRIMARY KEY ([uuid], [group]));');
        Db::getInstance()->exec('CREATE INDEX IF NOT EXISTS grp ON [pools] ([group]);');
        
        Db::getInstance()->exec('CREATE TABLE IF NOT EXISTS [pool_groups] ([name] VARCHAR PRIMARY KEY, [strategy] VARCHAR, [period] INTEGER);');
        
        
        $config_file = SITEPATH . '/config/config.json';
        if (file_exists($config_file)) {
            $json_config = json_decode(file_get_contents($config_file), true);
            if ($json_config !== false) {

                if (isset($json_config['rigs']) && is_array($json_config['rigs'])) {
                    $rigs = $json_config['rigs'];
                    unset($json_config['rigs']);
                    foreach ($json_config AS $k=> $v) {
                        Db::getInstance()->exec("INSERT OR REPLACE INTO [config] ([value], [key], [type]) VALUES ('" . SQLite3::escapeString(json_encode($v)). "', '" . SQLite3::escapeString($k) . "', 'config')");
                    }

                    foreach ($rigs AS $rig => $rig_data) {
                        Db::getInstance()->exec("INSERT OR REPLACE INTO [config] ([value], [key], [type]) VALUES ('" . SQLite3::escapeString(json_encode($rig_data)). "', '" . SQLite3::escapeString($rig) . "', 'rigs')");
                    }
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
                    Db::getInstance()->exec("INSERT OR REPLACE INTO [pool_groups] ([name], [strategy], [period]) VALUES ('" . SQLite3::escapeString($group). "', '" . SQLite3::escapeString($group_settings['strategy']). "', '" . SQLite3::escapeString($group_settings['period']) . "')");
 
                    foreach ($group_data AS $pool => $pool_data) {
                        $pool_data['rig_based'] = (isset($pool_data['rig_based'])) ? $pool_data['rig_based'] : false;
                        Db::getInstance()->exec("INSERT OR REPLACE INTO [pools] ([uuid], [url], [user], [pass], [group], [quota], [rig_based]) VALUES ('" . SQLite3::escapeString($pool). "', '" . SQLite3::escapeString($pool_data['url']) . "', '" . SQLite3::escapeString($pool_data['user']) . "', '" . SQLite3::escapeString($pool_data['pass']) . "', '" . SQLite3::escapeString($pool_data['group']) . "', '" . SQLite3::escapeString($pool_data['quota']) . "', '" . SQLite3::escapeString($pool_data['rig_based']) . "')");
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
                    Db::getInstance()->exec("INSERT OR REPLACE INTO [config] ([value], [key], [type]) VALUES ('" . SQLite3::escapeString(json_encode($v)). "', '" . SQLite3::escapeString($k) . "', 'notify')");
                }
            }
        }
    }
    
}