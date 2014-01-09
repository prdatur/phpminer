<?php

class PHPMinerRPC extends HttpClient {
    
    private $ip = null;
    private $port = null;
    private $rpc_key = null;
    public function __construct($ip, $port, $rpc_key) {
        $this->ip = $ip;
        $this->port = $port;
        $this->rpc_key = $rpc_key;
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
     * @param CGMinerAPI $api
     *   The cgminer api which is needed when gpu is set (optional, default = null)
     * 
     * @return boolean|string
     *   True on success, else the error string.
     */
    public function set_config($key, $val, $gpu = null, $api = null) {
        
        $devices = 0;
        $current_values = array();
        if ($gpu !== null) {
            $current_values = array();
            $device_configs = $api->get_devices();
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
    
    public function reboot() {
        $this->send('reboot');
    }
    
    private function send($command, $data = array()) {
        $args = array(
            'rpc_key' => $this->rpc_key,
            'command' => $command,
            'data' => $data,
        );
        
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            return array(
                'error' => 1,
                'msg' => "Can not create socket.",
            );
        }
        
        $result = @socket_connect($socket, $this->ip, $this->port);
        if ($result === false) {
            return array(
                'error' => 1,
                'msg' => "Could not connect to PHPMiner RPC, please check IP and port settings for PHPMiner RPC",
            );
        }
        $in = json_encode($args);
        @socket_write($socket, $in, strlen($in));
        $resp = $this->read_buffer($socket);
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
    
    function read_buffer($socket) {
        
        $buf = '';
        if (false !== ($bytes = socket_recv($socket, $buf, 2048, MSG_WAITALL))) {
            return $buf;
        }
        return false;
    }
    
}