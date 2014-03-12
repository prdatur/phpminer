<?php
/**
 * Main API
 */
class RPCClientApi {
    
    /**
     * Determines if this rpc client run's on a windows machine.
     * 
     * @var boolean
     */
    private $is_windows = false;
    
    /**
     * Holds the current rpc client config data.
     * 
     * @var array
     */
    private $config = array();
        
    /**
     * Creates a new API.
     * 
     * @param array $config
     *   The config for this rpc client
     */
    public function __construct($config) {
        $this->is_windows = (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN');
        $this->config = $config;
    }
    
    /**
     * Process ping pong.
     * 
     * @param array $data
     *   The orig data from phpminer.
     * 
     * @return string static 'pong'
     */
    public function ping($data = array()) {
        return 'pong';
    }
    
    /**
     * Returns all available custom commands.
     * 
     * @return array
     *   All available custom commands for this rig.
     */
    public function get_custom_commands() {
        $resp = array();
        if (!empty($this->config['custom_commands'])) {
            foreach ($this->config['custom_commands'] AS $cmd => $data) {
                if (empty($data['title'])) {
                    continue;
                }
                $resp[$cmd] = array(
                    'title' => $data['title'],
                    'confirmation_required' => $data['confirmation_required'],
                );
            }
        }
        return $resp;
    }
    
    /**
     * Send custom command and returns the result.
     * 
     * @param array $data
     *   The orig data from phpminer. (Optional, default = array())
     * 
     * @return String
     *   The response.
     */
    public function send_custom_command($data = array()) {
        if (empty($data['cmd'])) {
            return "No command provided.";
        }
        
        if (!isset($this->config['custom_commands'][$data['cmd']])) {
            return "Invalid command provided.";
        }
        
        $resp = shell_exec($this->config['custom_commands'][$data['cmd']]['command']);
        if ($this->config['custom_commands'][$data['cmd']]['has_response']) {
            return $resp;
        }
        return "";
    }
    
    /**
     * Returns the miner process id.
     * 
     * @param array $data
     *   The orig data from phpminer. (Optional, default = array())
     * 
     * @return boolean|int
     *   The process id or boolean false if not found.
     */
    public function get_cgminer_pid($data = array()) {
        if ($this->is_windows) {
            $task_list = array();
            exec("tasklist 2>NUL", $task_list);
            foreach ($task_list AS $task) {
                $matches = array();
                if (preg_match("/" . preg_quote($this->config['miner_binary'], '/') . "[^0-9]+([0-9]+)\s/", $task, $matches)) {
                    return $matches[1];
                }
            }
            return false;
        } else {
            $cmd = "ps a | grep \"" . $this->config['miner_binary'] . " -\" | grep -v grep | grep -v SCREEN | grep -v \"php -f \" | awk '{print $1}'";
            #echo $cmd . "\n";
            $res = trim(shell_exec($cmd));
            return intval($res);
        }
    }
    
    /**
     * Kills the miner.
     * 
     * @param array $data
     *   The orig data from phpminer. (Optional, default = array())
     */
    public function kill_cgminer($data = array()) {
        // Get process id.
        $cgminer_pid = $this->get_cgminer_pid();
        
        // Check if we could find it.
        if (!empty($cgminer_pid)) {
            
            // If custom command for stop is found, use this instead of default one.
            if (!empty($this->config['commands']['stop'])) {
                exec(str_replace("%pid%", $cgminer_pid, $this->config['commands']['stop']));
                return true;
            }
            
            // Check if on windows.
            if ($this->is_windows) {
                
                // Kill miner on windows.
                exec("taskkill /F /PID " . intval($cgminer_pid));
            }
            else {
                
                // Kill miner on linux.
                exec("kill -9 " . intval($cgminer_pid));
            }
        }
        return true;
    }
    
    /**
     * Returns the current miner at this rig.
     * 
     * @param array $data
     *   The orig data from phpminer. (Optional, default = array())
     * 
     * @return boolean
     *   the current miner.
     */
    public function get_current_miner($data = array()) {
        return $this->config['rpc_config']->current_miner;
    }
    
    /**
     * Returns all possible miners for this rig.
     * 
     * @return array
     *   The miners.
     */
    public function get_available_miners() {
        return array_keys($this->config['miners']);
    }
    
    /**
     * Switch the rig to the given miner.
     * 
     * @param array $data
     *   The orig data from phpminer. (Optional, default = array())
     * 
     * @return boolean
     *   True on success, else false.
     */
    public function switch_miner($data = array()) {
        
        // Check if miner was provided and if it exists.
        if (empty($data['miner']) || !isset($this->config['miners'][$data['miner']])) {
            return false;
        }
        
        // Get current miner.
        $current_miner = $this->config['rpc_config']->current_miner;
        
        // Check if it is already on that miner.
        if ($data['miner'] === $current_miner) {
            log_console('Already on that miner');
            return true;
        }
        
        $this->kill_cgminer();
        
        $run_tries = 0;
        while ($this->is_cgminer_running()) {
            sleep(1);
            if ($run_tries++ >= 10) {
                log_console('Could not stop current miner');
                return false;
            }
        }
        $current_miner_config = $this->config['miners'][$data['miner']];
        $this->config['miner_api_ip'] = $current_miner_config['ip'];
        $this->config['miner_api_port'] = $current_miner_config['port'];
        $this->config['miner'] = $current_miner_config['miner'];
        $this->config['miner_binary'] = $current_miner_config['miner_binary'];
        $this->config['cgminer_config_path'] = $current_miner_config['cgminer_config_path'];
        $this->config['cgminer_path'] = $current_miner_config['cgminer_path'];
        $this->config['amd_sdk'] = $current_miner_config['amd_sdk'];
        
        $this->config['rpc_config']->current_miner = $data['miner'];

        $this->restart_cgminer();
        
        $client_api = new CGMinerAPI($this->config['miner_api_ip'], $this->config['miner_api_port']);
        $tries = 0;
        while($tries++ <= 10) {
            try {
                $client_api->test_connection();
                return true;
            } catch (Exception $ex) {
                
            }
            sleep(1);
        }
        log_console('Could not start new miner');
        return false;
    }
    
    /**
     * Returns wether the miner is running or not.
     * 
     * @param array $data
     *   The orig data from phpminer. (Optional, default = array())
     * 
     * @return boolean
     *   True if miner is running, else false.
     */
    public function is_cgminer_running($data = array()) {
        
        // Get the process id.
        $pid = $this->get_cgminer_pid();
        
        // If process id is not empty the miner is running, else not.
        return !empty($pid);
    } 
    
    /**
     * Returns wether a defunced process exists or not.
     * 
     * @param array $data
     *   The orig data from phpminer. (Optional, default = array())
     * 
     * @return boolean
     *   True if a defunced process exists, else false.
     */
    public function is_cgminer_defunc($data = array()) {
        
        // On windows there can't be defunced processes.
        if ($this->is_windows) {
            return false;
        } else {
            // Check for defunced process on linux.
            $cmd = "ps a | grep " . $this->config['miner_binary'] . " | grep defunc | grep -v grep | grep -v SCREEN | grep -v \"php -f \" | awk '{print $1}'";
            #echo $cmd."\n";
            $res = trim(shell_exec($cmd));
            return !empty($res);
        }
    }
    
    /**
     * Tries to start the miner.
     * 
     * @param array $data
     *   The orig data from phpminer. (Optional, default = array())
     */
    public function restart_cgminer($data = array()) {
        if ($this->is_cgminer_running()) {
            return;
        }
        $cmd = '';
        // If custom command for start is found, use this instead of default one.
        if (!empty($this->config['commands']['start'])) {
            $cmd = $this->config['commands']['start'];
        }
        // Start on linux.
        if (!$this->is_windows) {
            if (empty($cmd)) {
                $cmd = "#!/bin/bash\n"
                        . "export GPU_MAX_ALLOC_PERCENT=100;\n"
                        . "export GPU_USE_SYNC_OBJECTS=1;\n"
                        . "export DISPLAY=:0;\n";
                if (!empty($this->config['amd_sdk'])) {
                    $cmd .= "export LD_LIBRARY_PATH=" . escapeshellarg($this->config['amd_sdk']) . ":;\n";
                }
                $cmd .= "cd " . escapeshellarg($this->config['cgminer_path']) . ";\n";
                $cmd .= "screen -d -m -S " . $this->config['miner'] . " ./" . $this->config['miner_binary'] . " -c " . escapeshellarg($this->config['cgminer_config_path']) . "\n";
            }
            else {
               // On Linux we need to place the bin bash in front of the custom command.
               $cmd = "#!/bin/bash\n" . $cmd; 
            }
            
            file_put_contents('/tmp/startcg', $cmd);
            @chmod('/tmp/startcg', 0777);
            shell_exec('/tmp/startcg');
            unlink('/tmp/startcg');
            
        // Start on windows.
        } else {
            if (empty($cmd)) {
                $cmd = ""
                        . "setx GPU_MAX_ALLOC_PERCENT 100\n"
                        . "setx GPU_USE_SYNC_OBJECTS 1\n";
                $cmd .= "cd " . escapeshellarg($this->config['cgminer_path']) . "\n";
                $cmd .= $this->config['miner_binary'] . " -c " . escapeshellarg($this->config['cgminer_config_path']) . "\n";
            }
            
            $temp = sys_get_temp_dir();
            if (!preg_match("/(\/|\\)$/", $temp)) {
                $temp .= "\\";
            }
            file_put_contents($temp . '\startcg.bat', $cmd);
            pclose(popen('start ' . $temp . '\startcg.bat', 'r'));
            
            // Let the process run.
            sleep(2);
            
            // Remove temp file.
            unlink($temp . '\startcg.bat');
        }
    }
    
    /**
     * Tries to reboot the rig.
     * 
     * @param array $data
     *   The orig data from phpminer. (Optional, default = array())
     */
    public function reboot($data = array()) {
        
        // If custom command for reboot is found, use this instead of default one.
        if (!empty($this->config['commands']['reboot'])) {
            exec($this->config['commands']['reboot']);
            return true;
        }
            
        if ($this->is_windows) {
            
            // Reboot machine on windows.
            exec('shutdown /r /t 1');
            
        } else {
            
            // Reboot machine on linux.
            
            // Retrieve the user which run's this script-.
            $user = trim(shell_exec("ps uh " . getmypid() . " | awk '{print $1'}"));

            // Any time just try to call "reboot" maybe the user can call it.
            exec('shutdown -r now');

            // If the user of the RPC-Client is root, we can call reboot, so don't try sudo fallback.
            if ($user !== 'root') {

                // Call sudo fallback.
                exec('sudo shutdown -r now');
            }
        }
    }
    
    /**
     * Get miner config.
     * 
     * @param array $data
     *   The orig data from phpminer. (Optional, default = array())
     */
    public function get_config($data = array()) {
        return file_get_contents($this->config['cgminer_config_path']);
    }
    
    /**
     * Check if miner config is writeable.
     * 
     * @param array $data
     *   The orig data from phpminer. (Optional, default = array())
     */
    public function check_cgminer_config_path($data = array()) {
        if (!is_writable($this->config['cgminer_config_path'])) {
            throw new Exception('');
        }
        return '';
    }
    
    /**
     * Set miner config.
     * 
     * @param array $data
     *   The orig data from phpminer. (Optional, default = array())
     */
    public function set_config($data = array()) {
        
        // Get current cgminer config.
        $conf = json_decode(file_get_contents($this->config['cgminer_config_path']), true);
        
        // When the value is empty, we have to remove it from the cgminer config.
        if (empty($data['value'])) {
            if (isset($conf[$data['key']])) {
                unset($conf[$data['key']]);
            }
        } else {
            
            // When we have a gpu key, then we have a multi value field. This means values can be seperated by , (comma).
            if (isset($data['gpu'])) {

                // If config key doesn't exist yet, create it.
                if (!isset($conf[$data['key']])) {
                    $conf[$data['key']] = '';
                }
                
                // Get current config values per gpu.
                $config_values = explode(",", $conf[$data['key']]);
                
                // If there are no config values yet, create an empty array.
                if (empty($config_values)) {
                    $config_values = array();
                }

                // Get the provided values, which should be set per gpu.
                $device_values = array();
                if (isset($data['current_values']) && !empty($data['current_values'])) {
                    $device_values = explode(",", $data['current_values']);
                    if (empty($device_values)) {
                        $device_values = array();
                    }
                }
                
                // Get the device count, so we can create the correct comma seperated value.
                $device_count = $data['devices'];
                
                // Loop through each device.
                for ($i = 0; $i < $device_count; $i++) {
                    // If the config key does not exist, fill it with 0.
                    if (!isset($config_values[$i])) {
                        $config_values[$i] = (!isset($device_values[$i])) ? 0 : $device_values[$i];
                    }
                }
                // Set the given gpu value.
                $config_values[$data['gpu']] = $data['value'];
                
                // Get the end result of the config key with all gpu values.
                $conf[$data['key']] = implode(",", $config_values);
            } else {
                
                // Parse "true" to boolean true
                if ($data['value'] === 'true') {
                    $data['value'] = true;
                }
                
                // Parse "false" to boolean false
                if ($data['value'] === 'false') {
                    $data['value'] = false;
                }
                
                // Set new config key value.
                $conf[$data['key']] = $data['value'];
            }
        }

        // Try to store the new config.
        if (file_put_contents($this->config['cgminer_config_path'], str_replace('\\/', '/', prettyPrint(json_encode($conf)))) === false) {
            throw new Exception('Could not write config file');
        }
    }
}
