<?php
/**
 * @copyright Christian Ackermann (c) 2013 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */
require_once 'APIException.class.php';
require_once 'APIRequestException.class.php';

/**
 * API for cgminer.
 */
class CGMinerAPI {
    
    /**
     * The ip address or hostname of the host where cgminer is running.
     * 
     * @var string
     */
    private $remote_ip = null;
    
    /**
     * The remote port of the host where cgminer is running.
     * 
     * @var int
     */
    private $remote_port = 0;
    
    /**
     * Holds the socket.
     * 
     * @var socket
     */
    private $socket = null;
    
    /**
     * Determines if this cgminer has the advanced api commands.
     * 
     * @var boolean
     */
    private $has_advanced_api = false;
    private $timeout = null;
    
    /**
     * Creates a new api instance-
     * 
     * @param string $address
     *   The remote ip or hostname where cgminer is running.
     * @param int $port
     *   The remote port where cgminer is running.
     */
    public function __construct($address, $port, $timeout = 10) {
        $this->remote_ip = $address;
        $this->remote_port = $port;
        $this->timeout = $timeout;
    }
        
    /**
     * Setup the socket.
     * 
     * @throws APIException
     *   If there were any errors while creating the socket or connection to the host
     *   an APIException will be thrown. Error codes are explained at APIException.class.php
     */
    private function setup_socket() {
        if ($this->socket === null) {
            $this->socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if ($this->socket === false || $this->socket === null) {
                $this->socket = null;
                throw new APIException(socket_strerror(socket_last_error()), APIException::CODE_SOCKET_CREATE_ERROR);
            }
            
            @socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>$this->timeout, 'usec'=>0));
            @socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, array('sec'=>$this->timeout, 'usec'=>0));
        
            $res = @socket_connect($this->socket , $this->remote_ip, $this->remote_port);
            if ($res === false) {
                @socket_close($this->socket);
                $this->socket = null;
                throw new APIException(socket_strerror(socket_last_error()), APIException::CODE_SOCKET_CONNECT_ERROR);
            }
        }
    }
    
    /**
     * Test the connection, tries to connect to the given ip and port.
     * Also sends a command to cgminer to retrieve the current version.
     * If this succeed we are sure we are connected to a cgminer api.
     * 
     * @return array
     *   If connection succeeds it will return an array with the cgminer and cgminer api version.
     * 
     * @throws APIException
     * @throws APIRequestException
     */
    public function test_connection() {
        $this->setup_socket();
        $res = $this->get_version();
        
        $advanced_api = $this->check('currentpool');
        $this->has_advanced_api = $advanced_api[0]['Exists'] == 'Y';
        
        return $res;
        
    }
    
    /**
     * Returns wether this cgminer connection has advanced api or not.
     * 
     * @return boolean
     *   True if advanced api is available, else false.
     */
    public function has_advanced_api() {
        return $this->has_advanced_api;
    }
    
    /**
     * Tries to read from the socket.
     * 
     * @return string
     *   The text which was read.
     * @throws APIException
     */
    private function read_from_api() {
        if ($this->socket === null) {
            throw new APIException('Socket not connected', APIException::CODE_SOCKET_NOT_CONNECTED);
        }
        
        $line = '';
        while (true) {
            $data = socket_read($this->socket, 256);
           
            // Nothing was read.
            if (empty($data)) {
                break;
            }
            // Explode by stopbit, if the array has a count of 2 we found a stop bit and just append the text before this bit.
            $stop_bit_check = explode("\0", $data, 2);
            
            // Append data
            $line .= $stop_bit_check[0];
            
            // Check if we found a stop bit, if so break.
            if (count($stop_bit_check) === 2) {
                break;
            }
        }
        return $line;
    }
    
    /**
     * Closes the socket.
     */
    private function close_socket() {
        
        // Only close if the socket is currently opened.
        if ($this->socket !== null) {
            socket_close($this->socket);
            $this->socket = null;
        }
    }
    
    private function request($cmd, $single_response = false, $parameter = null, $has_response = true) {
        
        // If we didn't connected yet, connect
        if ($this->socket === null) {
            $this->setup_socket();
        }
        
        // Create default api request data.
        $cmd_data = array(
            'command' => $cmd,
        );
        
        // If we provided some parameters, add them also within the request.
        if ($parameter !== null) {
            
            // If we have an array, implode the arguments by comma.
            if (is_array($parameter)) {
                $parameter = implode(",", $parameter);
            }
            $cmd_data['parameter'] = $parameter;
        }
                
        // We need to get a variable so we can determine the length of it which is needed by socket_write.
        $json_data = json_encode($cmd_data);    
        
        // Send the command to the api server.
        socket_write($this->socket, $json_data, strlen($json_data));
        
        // Read the api response.
        $line = $this->read_from_api();

        // We don't need the socket connection anymore.
        $this->close_socket();

        // Nothing send, so return an empty string.
        if (strlen($line) == 0) {
            return $line;
        }
        // Couse we always use json, we safely can just parse the response as json.
        $json = json_decode($line, true);

        if (empty($json) && stripos($line, "\"BYE\"") !== false) {
            return true;
        }
        
        // Check for success.
        if ($json['STATUS'][0]['STATUS'] === 'E') {
            throw new APIRequestException($json['STATUS'][0]['Msg'] . "\n");
        }
        
        // Remove status.
        unset($json['STATUS']);
        // Get the command data.
        $response = reset($json);
        
        // Just return an empty array if there is not response.
        if (empty($response)) {
            // If we don't have a response we just return the success.
            return ($has_response) ? array() : true;
        }
        
        // Check if the given api command always respinse only with one element.
        if ($single_response === true) {
            
            // For single respones just return the given data.
            return $response[0];
        }
        return $response;
    }
    
    /**
     * Returns the version info.
     * 
     * @return array
     *   API Response.
     */
    public function get_version() {
        return $this->request('version', true);
    }
    
    /**
     * Returns the config info.
     * 
     * @return array
     *   API Response.
     */
    public function get_config() {
        return $this->request('config', true);
    }
    
    /**
     * Returns the summary data.
     * 
     * @return array
     *   API Response.
     */
    public function get_summary() {
        return $this->request('summary', true);
    }
    
    /**
     * Returns the pools data.
     * 
     * @return array
     *   API Response.
     */
    public function get_pools() {
        return $this->request('pools');
    }
    
    /**
     * Returns the current pool.
     * 
     * @return array
     *   API Response.
     */
    public function get_currentpool() {
        return $this->request('currentpool');
    }
    
    /**
     * Returns the devices data.
     * 
     * @return array
     *   API Response.
     */
    public function get_devices() {
        return $this->request('devs');
    }
    
    /**
     * Returns the devices data.
     * 
     * @return array
     *   API Response.
     */
    public function get_device_status() {
        return $this->request('devstatus');
    }
    
    /**
     * Returns the device details data.
     * 
     * @return array
     *   API Response.
     */
    public function get_devices_details() {
        return $this->request('devdetails');
    }
    
    /**
     * Returns wether the current client (php_cgminer) has priviliged access or not.
     * 
     * @return boolean
     *   True if user has the permissions, else false.
     */
    public function is_privileged() {
        $result = $this->request('privileged');
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
        return $this->request('switchpool', false, $pool_id, false);
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
        return $this->request('addpool', false, array(
            $url,
            $user,
            $pass,
        ), false);
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
        return $this->request('updatepool', false, array(
            $pool_id,
            $url,
            $user,
            $pass,
        ), false);
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
        return $this->request('enablepool', false, $pool_id, false);
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
        return $this->request('strategy', false, array($strategy, $rotate_minutes), false);
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
        return $this->request('poolpriority', false, $pool_ids, false);
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
        return $this->request('poolquota', false, array($pool_id, $quota), false);
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
        $results = array();
        foreach ($data AS $pool_id => $quota) {
            $results[] = $this->request('poolpriority', false, array($pool_id, $quota), false);
        }
        return $results;
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
        return $this->request('disablepool', false, $pool_id, false);
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
        return $this->request('removepool', false, $pool_id, false);
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
        return $this->request('save', false, $filename, false);
    }
    
    /**
     * Returns the gpu count data.
     * 
     * @return array
     *   API Response.
     */
    public function get_gpucount() {
        return $this->request('gpucount');
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
        return $this->request('gpu', false, $gpu_id, false);
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
        return $this->request('gpurestart', false, $gpu_id, false);
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
        return $this->request('gpuintensity', false, array($gpu_id, $value), false);
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
        return $this->request('gpumem', false, array($gpu_id, $value), false);
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
        return $this->request('gpuengine', false, array($gpu_id, $value), false);
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
        return $this->request('gpufan', false, array($gpu_id, $value), false);
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
        return $this->request('gpuvddc', false, array($gpu_id, $value), false);
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
        return $this->request('gpuenable', false, $gpu_id, false);
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
        return $this->request('gpudisable', false, $gpu_id, false);
    }
        
    /**
     * Returns the pga count data.
     * 
     * @return array
     *   API Response.
     */
    public function get_pgacount() {
        return $this->request('pgacount');
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
        return $this->request('pga', false, $pga_id, false);
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
        return $this->request('pgaenable', false, $pga_id, false);
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
        return $this->request('pgadisable', false, $pga_id, false);
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
        return $this->request('pgaidentify', false, $pga_id, false);
    }
    
    /**
     * Returns the asc count data.
     * 
     * @return array
     *   API Response.
     */
    public function get_asccount() {
        return $this->request('asccount');
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
        return $this->request('asc', false, $asc_id, false);
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
        return $this->request('ascenable', false, $asc_id, false);
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
        return $this->request('ascdisable', false, $asc_id, false);
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
        return $this->request('ascidentify', false, $asc_id, false);
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
        return $this->request('failover-only', false, ($failover) ? 't' : 'f', false);
    }
    
    /**
     * Quit cgminer
     * 
     * @return array
     *   API Response.
     */
    public function quit() {
        return $this->request('quit', false, null, false);
    }
    
    /**
     * Restart cgminer
     * 
     * @return array
     *   API Response.
     */
    public function restart() {
        return $this->request('restart', false, null, false);
    }
    
    /**
     * Returns the last status and history count of each devices problem .
     * 
     * @return array
     *   API Response.
     */
    public function notify() {
        return $this->request('notify');
    }
    
    /**
     * Returns stats.
     * Only for debugging purpose.
     * 
     * @return array
     *   API Response.
     */
    public function stats() {
        return $this->request('stats');
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
        return $this->request('zero', false, array(($type !== 'all') ? 'bestshare' : 'all', 'f'), false);
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
            return $this->request('check', false, $cmd, false);
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
        return $this->request('coin');
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
        return $this->set_config('queue', $value);
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
        return $this->set_config('scantime', $value);
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
        return $this->set_config('expiry', $value);
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
    private function set_config($name, $value) {
        if ($value < 0 || $value > 9999) {
            throw new APIRequestException('Invalid value, Allowed values: 0 to 9999');
        }
        return $this->request('setconfig', false, array($name, $value));
    }
    
    /**
     * Returns the usb stats.
     * 
     * @return array
     *   API Response.
     */
    public function get_usbstats() {
        return $this->request('usbstats');
    }
}