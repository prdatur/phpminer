<?php
require_once 'APIException.class.php';
require_once 'APIRequestException.class.php';
class PHPMinerRPC extends HttpClient {
    
    private $ip = null;
    private $port = null;
    private $rpc_key = null;
    private $timeout = null;
    private $has_advanced_api = false;
    public function __construct($ip, $port, $rpc_key, $timeout = 10) {
        $this->ip = $ip;
        $this->port = $port;
        $this->rpc_key = $rpc_key;
        $this->timeout = $timeout;
        parent::__construct();
    }
    
    public function ping() {
        $res = $this->send('ping');
        if (empty($res['error']) && $res['msg'] === 'pong') {
            return true;
        }
        return $res['msg'];
    }
    
    /**
     * Set cgminer config for the remote rig.
     * 
     * @param string $key
     *   The config key.
     * @param mixed $val 
     *   The value to be set.
     * @param int $gpu
     *   The gpu. If provided this is a gpu specific value. (optional, default = null)
     * 
     * @return boolean|string
     *   True on success, else the error string.
     */
    public function set_config($key, $val, $gpu = null) {
        
        $devices = 0;
        $current_values = array();
        if ($gpu !== null) {
            $current_values = array();
            $device_configs = $this->get_devices();
            foreach ($device_configs AS $info) {
                if (!isset($info['GPU'])) {
                    continue;
                }
                switch ($key) {
                    case 'gpu-fan':
                        $current_values[$info['GPU']] = $info['Fan Percent'];
                        break;
                    case 'intensity':
                        $current_values[$info['GPU']] = $info['Intensity'];
                        break;
                    case 'gpu-vddc':
                        $current_values[$info['GPU']] = $info['GPU Voltage'];
                        break;
                    case 'gpu-memclock':
                        $current_values[$info['GPU']] = $info['Memory Clock'];
                        break;
                    case 'gpu-engine':
                        $current_values[$info['GPU']] = $info['GPU Clock'];
                        break;
                }
                $devices++;
            }
            
            if (!empty($current_values)) {
                ksort($current_values);
                $current_values = implode(",", $current_values);
            }
        }
        
        $res =  $this->send('set_config', array(
            'key' => $key,
            'value' => $val,
            'gpu' => $gpu,
            'devices' => $devices,
            'current_values' => $current_values,
        ));
        
        if (empty($res['error'])) {
            return true;
        }
        return $res['msg'];
    }
    
    public function get_config() {
        $res =  $this->send('get_config');
        if (empty($res['error'])) {
            return json_decode($res['msg'], true);
        }
        return $res['msg'];
    }
    
    public function check_cgminer_config_path() {
        $res =  $this->send('check_cgminer_config_path');
        if (empty($res['error'])) {
            return true;
        }
        return $res['msg'];
    }
    
    public function is_cgminer_defunc() {
        $res =  $this->send('is_cgminer_defunc');
        return (empty($res['error']) && $res['msg'] == 1);
    }
    
    public function is_cgminer_running() {
        $res =  $this->send('is_cgminer_running');
        return (empty($res['error']) && $res['msg'] == 1);
    }
    
    public function restart_cgminer() {
        $res =  $this->send('restart_cgminer');
        if (empty($res['error'])) {
            return true;
        }
        return $res['msg'];
    }
    
    public function kill_cgminer() {
        $res =  $this->send('kill_cgminer');
        if (empty($res['error'])) {
            return true;
        }
        return $res['msg'];
    }
    
    public function reboot() {
        $this->send('reboot');
    }
    
    /******* MINER API COMMAND PROXY ************/
    /**
     * Test the connection, tries to connect to the given ip and port.
     * Also sends a command to cgminer to retrieve the current version.
     * If this succeed we are sure we are connected to a cgminer api.
     * 
     * @return array
     *   If connection succeeds it will return an array with the cgminer and cgminer api version.
     */
    public function test_connection() {
        $res = $this->send_api('test_connection', true);
        
        $advanced_api = $this->check('currentpool');
        $this->has_advanced_api = $advanced_api[0]['Exists'] == 'Y';
        
        return $res;
    }
    
    public function has_advanced_api() {
        return $this->has_advanced_api;
    }
    
    /**
     * Returns the version info.
     * 
     * @return array
     *   API Response.
     */
    public function get_version() {
        return $this->send_api('get_version');
    }
    
    /**
     * Returns the config info.
     * 
     * @return array
     *   API Response.
     */
    public function get_api_config() {
        return $this->send_api('get_api_config');
    }
    
    /**
     * Returns the summary data.
     * 
     * @return array
     *   API Response.
     */
    public function get_summary() {
        return $this->send_api('get_summary');
    }
    
    /**
     * Returns the pools data.
     * 
     * @return array
     *   API Response.
     */
    public function get_pools() {
        return $this->send_api('get_pools');
    }
    
    /**
     * Returns the current pool.
     * 
     * @return array
     *   API Response.
     */
    public function get_currentpool() {
        return $this->send_api('get_currentpool');
    }
    
    /**
     * Returns the devices data.
     * 
     * @return array
     *   API Response.
     */
    public function get_devices() {
        return $this->send_api('get_devices');
    }
    
    /**
     * Returns the devices data.
     * 
     * @return array
     *   API Response.
     */
    public function get_device_status() {
        return $this->send_api('get_device_status');
    }
    
    /**
     * Returns the device details data.
     * 
     * @return array
     *   API Response.
     */
    public function get_devices_details() {
        return $this->send_api('get_devices_details');
    }
    
    /**
     * Returns wether the current client (php_cgminer) has priviliged access or not.
     * 
     * @return boolean
     *   True if user has the permissions, else false.
     */
    public function is_privileged() {
        $result = $this->send_api('is_privileged');
        return $result['STATUS'][0]['STATUS'] === 'S';
    }
    
    /**
     * Switch to the given pool.
     * 
     * @param int $pool_id
     *   The pool id.
     * 
     * @return array
     *   API Response.
     */
    public function switchpool($pool_id) {
        return $this->send_api('switchpool', $pool_id);
    }
    
    /**
     * Add the given pool.
     * 
     * @param string $url
     *   The pool url including the stratum and or port
     * @param string $user
     *   The pool worker username.
     * @param string $pass
     *   The pool worker password.
     * 
     * @return array
     *   API Response.
     */
    public function addpool($url, $user, $pass) {
        return $this->send_api('addpool', array(
            $url,
            $user,
            $pass,
        ));
    }
    
    /**
     * Update the given pool.
     * 
     * @param string $pool_id
     *   The pool id.
     * @param string $url
     *   The pool url including the stratum and or port
     * @param string $user
     *   The pool worker username.
     * @param string $pass
     *   The pool worker password.
     * 
     * @return array
     *   API Response.
     */
    public function updatepool($pool_id, $url, $user, $pass) {
        return $this->send_api('updatepool', array(
            $pool_id,
            $url,
            $user,
            $pass,
        ));
    }
    
    /**
     * Enable the given pool.
     * 
     * @param int $pool_id
     *   The pool id.
     * 
     * @return array
     *   API Response.
     */
    public function enablepool($pool_id) {
        return $this->send_api('enablepool', $pool_id);
    }
    
    /**
     * Set the pool priority for each pool ids.
     * 
     * @param int $strategy
     *   The strategy, can be 0:Failover, 1:Round Robin, 2: Rotate,3: Load Balance,4: Balance
     * @param int $rotate_minutes
     *   If strategy is set to Rotate, this param is required and the pools will be rotated configurated minute minutes.
     * 
     * @return array
     *   API Response.
     */
    public function set_poolstrategy($strategy, $rotate_minutes = 0) {
        return $this->send_api('set_poolstrategy', array($strategy, $rotate_minutes));
    }
    
    /**
     * Set the pool priority.
     * 
     * @param array $pool_ids
     *   The pool ids in which the priority should be. First pool id has the highest priority.
     * 
     * @return array
     *   API Response.
     */
    public function set_poolpriority($pool_ids) {
        return $this->send_api('set_poolpriority', $pool_ids);
    }
    
    /**
     * Set the pool quota for the given pool id.
     * 
     * @param int $pool_id
     *   The pool id
     * @param int $quota
     *   The quota (Betweem 1-100%)
     *   if all pools have 100% they all have the same amount of usage.
     * 
     * @return array
     *   API Response.
     */
    public function set_poolquota($pool_id, $quota) {
        return $this->send_api('set_poolquota', array($pool_id, $quota));
    }
    
    /**
     * Set the pool quota for multiple pool ids
     * 
     * @param array $data
     *   An array which holds as the key the pool id and as the value the quota.
     * 
     * @return array
     *   API Response's for each pool id.
     * 
     * @see set_poolquota($pool_id, $quota);
     */
    public function set_poolquotas($data) {
        return $this->send_api('set_poolquotas', $data);
    }
    
    /**
     * Disable the given pool.
     * 
     * @param int $pool_id
     *   The pool id.
     * 
     * @return array
     *   API Response.
     */
    public function disablepool($pool_id) {
        return $this->send_api('disablepool', $pool_id);
    }
    
    /**
     * Remove the given pool.
     * 
     * @param int $pool_id
     *   The pool id.
     * 
     * @return array
     *   API Response.
     */
    public function removepool($pool_id) {
        return $this->send_api('removepool', $pool_id);
    }
    
    /**
     * Save the current config to config file.
     * 
     * @param string $filename
     *   The file name to save. If not provided it will use the default one (optional, default = null)
     * 
     * @return array
     *   API Response.
     */
    public function save($filename = null) {
        return $this->send_api('save', $filename);
    }
    
    /**
     * Returns the gpu count data.
     * 
     * @return array
     *   API Response.
     */
    public function get_gpucount() {
        return $this->send_api('get_gpucount');
    }
    
    /**
     * Returns detail information about the given gpu.
     * 
     * @param int $gpu_id
     *   The gpu id.
     * 
     * @return array
     *   API Response.
     */
    public function get_gpu($gpu_id) {
        return $this->send_api('get_gpu', $gpu_id);
    }
    
    /**
     * Restarts the given gpu devices.
     * 
     * @param int $gpu_id
     *   The gpu id.
     * 
     * @return array
     *   API Response.
     */
    public function gpurestart($gpu_id) {
        return $this->send_api('gpurestart', $gpu_id);
    }
    
    /**
     * Set the intensity for the given the given gpu devices.
     * 
     * @param int $gpu_id
     *   The gpu id.
     * @param int $value 
     *   The value.
     * 
     * @return array
     *   API Response.
     */
    public function set_gpuintensity($gpu_id, $value) {
        return $this->send_api('set_gpuintensity', array($gpu_id, $value));
    }
    
    /**
     * Set the memory clock for the given the given gpu devices.
     * 
     * @param int $gpu_id
     *   The gpu id.
     * @param int $value 
     *   The value.
     * 
     * @return array
     *   API Response.
     */
    public function set_gpumem($gpu_id, $value) {
        return $this->send_api('set_gpumem', array($gpu_id, $value));
    }
    
    /**
     * Set the gpu engine clock for the given the given gpu devices.
     * 
     * @param int $gpu_id
     *   The gpu id.
     * @param int $value 
     *   The value.
     * 
     * @return array
     *   API Response.
     */
    public function set_gpuengine($gpu_id, $value) {
        return $this->send_api('set_gpuengine', array($gpu_id, $value));
    }
    
    /**
     * Set the gpu fan speed in percent for the given the given gpu devices.
     * 
     * @param int $gpu_id
     *   The gpu id.
     * @param int $value 
     *   The value in percent.
     * 
     * @return array
     *   API Response.
     */
    public function set_gpufan($gpu_id, $value) {
        if ($value < 0 || $value > 100) {
            throw new APIException('Fan speed can only be 0 to 100 (represents the percentage)', APIException::CODE_INVALID_PARAMETER);
        }
        return $this->send_api('set_gpufan', array($gpu_id, $value));
    }
    
    /**
     * Set the gpu voltage speed in percent for the given the given gpu devices.
     * 
     * @param int $gpu_id
     *   The gpu id.
     * @param int $value 
     *   The value in percent.
     * 
     * @return array
     *   API Response.
     */
    public function set_gpuvddc($gpu_id, $value) {
        return $this->send_api('set_gpuvddc', array($gpu_id, $value));
    }
    
    /**
     * Enables the gpu
     * 
     * @param int $gpu_id
     *   The gpu id.
     * 
     * @return array
     *   API Response.
     */
    public function gpuenable($gpu_id) {
        return $this->send_api('gpuenable', $gpu_id);
    }
    
    /**
     * Disables the gpu
     * 
     * @param int $gpu_id
     *   The gpu id.
     * 
     * @return array
     *   API Response.
     */
    public function gpudisable($gpu_id) {
        return $this->send_api('gpudisable', $gpu_id);
    }
        
    /**
     * Returns the pga count data.
     * 
     * @return array
     *   API Response.
     */
    public function get_pgacount() {
        return $this->send_api('get_pgacount');
    }
    
    /**
     * Returns detail information about the given pga.
     * 
     * @param int $pga_id
     *   The pga id.
     * 
     * @return array
     *   API Response.
     */
    public function get_pga($pga_id) {
        return $this->send_api('get_pga', $pga_id);
    }
    
    /**
     * Enables the pga
     * 
     * @param int $pga_id
     *   The pga id.
     * 
     * @return array
     *   API Response.
     */
    public function pgaenable($pga_id) {
        return $this->send_api('pgaenable', $pga_id);
    }
    
    /**
     * Disables the pga
     * 
     * @param int $pga_id
     *   The pga id.
     * 
     * @return array
     *   API Response.
     */
    public function pgadisable($pga_id) {
        return $this->send_api('pgadisable', $pga_id);
    }
    
    /**
     * Identifies the pga, will let the given pga blink his led for 4s 
     * 
     * @param int $pga_id
     *   The pga id.
     * 
     * @return array
     *   API Response.
     */
    public function pgaidentify($pga_id) {
        return $this->send_api('pgaidentify', $pga_id);
    }
    
    /**
     * Returns the asc count data.
     * 
     * @return array
     *   API Response.
     */
    public function get_asccount() {
        return $this->send_api('get_asccount');
    }
    
    /**
     * Returns detail information about the given asc.
     * 
     * @param int $asc_id
     *   The asc id.
     * 
     * @return array
     *   API Response.
     */
    public function get_asc($asc_id) {
        return $this->send_api('get_asc', $asc_id);
    }
    
    /**
     * Enables the asc
     * 
     * @param int $asc_id
     *   The asc id.
     * 
     * @return array
     *   API Response.
     */
    public function ascenable($asc_id) {
        return $this->send_api('ascenable', $asc_id);
    }
    
    /**
     * Disables the asc
     * 
     * @param int $asc_id
     *   The asc id.
     * 
     * @return array
     *   API Response.
     */
    public function ascdisable($asc_id) {
        return $this->send_api('ascdisable', $asc_id);
    }
    
    /**
     * Identifies the asc, will let the given asc blink his led for 4s 
     * 
     * @param int $asc_id
     *   The asc id.
     * 
     * @return array
     *   API Response.
     */
    public function ascidentify($asc_id) {
        return $this->send_api('ascidentify', $asc_id);
    }
    
    
    
    /**
     * Enable or disable failover-only
     * 
     * @param boolean $failover
     *   The pga id.
     * 
     * @return array
     *   API Response.
     */
    public function set_failover_only($failover) {
        return $this->send_api('set_failover_only', $failover);
    }
    
    /**
     * Quit cgminer
     * 
     * @return array
     *   API Response.
     */
    public function quit() {
        return $this->send_api('quit');
    }
    
    /**
     * Restart cgminer
     * 
     * @return array
     *   API Response.
     */
    public function restart() {
        return $this->send_api('restart');
    }
    
    /**
     * Returns the last status and history count of each devices problem .
     * 
     * @return array
     *   API Response.
     */
    public function notify() {
        return $this->send_api('notify');
    }
    
    /**
     * Returns stats.
     * Only for debugging purpose.
     * 
     * @return array
     *   API Response.
     */
    public function stats() {
        return $this->send_api('stats');
    }
    
    /**
     * Reset stats.
     * 
     * @param string $type
     *   The type to reset, can be 'bestshare' or 'all'. (Optional, default = 'all')
     * 
     * @return array
     *   API Response.
     */
    public function zero($type = 'all') {
        return $this->send_api('zero', $type);
    }
    
    /**
     * Returns wether the given api command exists or not.
     * 
     * @param string $cmd 
     *  The cmd to check if it available within the api version.
     * 
     * @return boolean
     *   True if exists, else false.
     */
    public function check($cmd) {
        try {
            return $this->send_api('check', $cmd);
        }
        catch(APIRequestException $e) {
            return false;
        }
    }
    
    /**
     * Returns the coin info data.
     * 
     * @return array
     *   API Response.
     */
    public function get_coin() {
        return $this->send_api('get_coin');
    }
    
    /**
     * Set queue config
     * 
     * @param int $value
     *   The value. (from 0 to 9999)
     * 
     * @return array
     *   API Response.
     */
    public function set_config_queue($value) {
        return $this->set_api_config('queue', $value);
    }
    
    /**
     * Set scantime config
     * 
     * @param int $value
     *   The value. (from 0 to 9999)
     * 
     * @return array
     *   API Response.
     */
    public function set_config_scantime($value) {
        return $this->set_api_config('scantime', $value);
    }
    
    /**
     * Set expiry config
     * 
     * @param int $value
     *   The value. (from 0 to 9999)
     * 
     * @return array
     *   API Response.
     */
    public function set_config_expiry($value) {
        return $this->set_api_config('expiry', $value);
    }
    
    /**
     * Set config
     * 
     * @param string $$name
     *   The config name. (queue, scantime, expiry)
     * @param int $value
     *   The new config value. (0-9999)
     * 
     * @return array
     *   API Response.
     */
    private function set_api_config($name, $value) {
        if ($value < 0 || $value > 9999) {
            throw new APIRequestException('Invalid value, Allowed values: 0 to 9999');
        }
        return $this->send_api('setconfig', array($name, $value));
    }
    
    /**
     * Returns the usb stats.
     * 
     * @return array
     *   API Response.
     */
    public function get_usbstats() {
        return $this->send_api('get_usbstats');
    }
    
    /**
     * Send the api command to the rpc client as a proxy for miner api.
     * 
     * @param string $command
     *   The command.
     * @param array $data
     *   The data. (Optional, default = array())
     * 
     * @return mixed
     *   Returns boolean false on error, else the direct response content from ['msg']
     *   
     */
    private function send_api($command, $data = array()) {
        $result = $this->send($command, $data, true);
        if ($result['error']) {
            throw new APIRequestException($result['msg']);
        }
        
        return $result['msg'];
    }
    
    /**
     * Send the rpc command.
     * 
     * @param string $command
     *   The command.
     * @param array $data
     *   The data. (Optional, default = array())
     * @param boolean $from_api
     *   Enable miner api mode. (Optional, default = false)
     * 
     * @return mixed
     *   Returns an array with 'error' => 0|1 where 1 determines we had an error and 'msg' with eather the error message on error or the response data.
     *   When $from_api is set to true it returns boolean false on error, else the direct response content from ['msg']
     *   
     */
    private function send($command, $data = array(), $from_api = false) {
        $args = array(
            'rpc_key' => $this->rpc_key,
            'command' => $command,
            'data' => $data,
            'api_proxy' => $from_api,
        );
        
        $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            return array(
                'error' => 1,
                'msg' => "Can not create socket.",
            );
        }
        
        @socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>$this->timeout, 'usec'=>0));
        @socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec'=>$this->timeout, 'usec'=>0));

        $result = @socket_connect($socket, $this->ip, $this->port);
        if ($result === false) {
            return array(
                'error' => 1,
                'msg' => "Could not connect to PHPMiner RPC, please check IP and port settings for PHPMiner RPC",
            );
        }
        
        $in = json_encode($args);
        @socket_write($socket, $in, strlen($in));
        
        // Read the response.
        $buf = '';
        $read_buf = '';
        while (0 !== ($bytes = @socket_recv($socket, $buf, 16, MSG_WAITALL))) {
            $read_buf .= $buf;
        }
        
        $resp = $read_buf;
        
        @socket_close($socket);
        $res = json_decode($resp, true);
        if ($res === false || empty($res)) {
            return array(
                'error' => 1,
                'msg' => "Could not connect to PHPMiner RPC, please check IP and port settings for PHPMiner RPC",
            );
        }
        return $res;
    } 
}