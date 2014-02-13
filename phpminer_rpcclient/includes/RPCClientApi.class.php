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
            $res = trim(shell_exec("ps a | grep \"" . $this->config['miner_binary'] . " -\" | grep -v grep | grep -v SCREEN | grep -v \"php -f \" | awk '{print $1}'"));
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
            $res = trim(shell_exec("ps a | grep " . $this->config['miner_binary'] . " | grep defunc | grep -v grep | grep -v SCREEN | grep -v \"php -f \" | awk '{print $1}'"));
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
        
        // Start on linux.
        if (!$this->is_windows) {
            $cmd = "#!/bin/bash\n"
                    . "export GPU_MAX_ALLOC_PERCENT=100;\n"
                    . "export GPU_USE_SYNC_OBJECTS=1;\n"
                    . "export DISPLAY=:0;\n";
            if (!empty($this->config['amd_sdk'])) {
                $cmd .= "export LD_LIBRARY_PATH=" . escapeshellarg($this->config['amd_sdk']) . ":;\n";
            }
            $cmd .= "cd " . escapeshellarg($this->config['cgminer_path']) . ";\n";
            $cmd .= "screen -d -m -S " . $this->config['miner'] . " ./" . $this->config['miner_binary'] . " -c " . escapeshellarg($this->config['cgminer_config_path']) . "\n";

            file_put_contents('/tmp/startcg', $cmd);
            @chmod('/tmp/startcg', 0777);
            shell_exec('/tmp/startcg');
            unlink('/tmp/startcg');
            
        // Start on windows.
        } else {
            $cmd = ""
                    . "setx GPU_MAX_ALLOC_PERCENT 100\n"
                    . "setx GPU_USE_SYNC_OBJECTS 1\n";
            $cmd .= "cd " . escapeshellarg($this->config['cgminer_path']) . "\n";
            $cmd .= $this->config['miner_binary'] . " -c " . escapeshellarg($this->config['cgminer_config_path']) . "\n";
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
        $conf = json_decode(file_get_contents($this->config['cgminer_config_path']), true);
        if (empty($data['data']['value'])) {
            if (isset($conf[$data['data']['key']])) {
                unset($conf[$data['data']['key']]);
            }
        } else {
            if (isset($data['data']['gpu'])) {

                if (!isset($conf[$data['data']['key']])) {
                    $conf[$data['data']['key']] = '';
                }
                $config_values = explode(",", $conf[$data['data']['key']]);
                if (empty($config_values)) {
                    $config_values = array();
                }

                $device_values = array();
                if (isset($data['data']['current_values']) && !empty($data['data']['current_values'])) {
                    $device_values = explode(",", $data['data']['current_values']);
                    if (empty($device_values)) {
                        $device_values = array();
                    }
                }
                $device_count = $data['data']['devices'];
                for ($i = 0; $i < $device_count; $i++) {
                    if (!isset($config_values[$i])) {
                        $config_values[$i] = (!isset($device_values[$i])) ? 0 : $device_values[$i];
                    }
                }
                $config_values[$data['data']['gpu']] = $data['data']['value'];
                $conf[$data['data']['key']] = implode(",", $config_values);
            } else {
                if ($data['data']['value'] === 'true') {
                    $data['data']['value'] = true;
                }
                if ($data['data']['value'] === 'false') {
                    $data['data']['value'] = false;
                }
                $conf[$data['data']['key']] = $data['data']['value'];
            }
        }

        if (file_put_contents($this->config['cgminer_config_path'], str_replace('\\/', '/', prettyPrint(json_encode($conf)))) === false) {
            throw new Exception('Could not write config file');
        }
    }
}
