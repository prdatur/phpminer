<?php

/**
 * Handles an incoming connection.
 */
class RPCClientConnection {

    /**
     * A unique string which identifes this client.
     * 
     * @var string
     */
    private $id = null;

    /**
     * The client socket.
     * 
     * @var resource
     */
    private $socket = null;

    /**
     * Creates a new client connection.
     * 
     * @param resource $client_socket
     *   The client socket.
     */
    public function __construct(&$client_socket) {
        $this->id = uniqid();
        $this->socket = $client_socket;
    }

    /**
     * Returns the uniq client id.
     * 
     * @return string
     *   The unique id.
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Process the connection / API Command.
     * 
     * @param array $config
     *   The config of this rpc client.
     */
    public function process_connection($config) {

        // Read the data from phpminer.
        $data = $this->read_buffer();

        // Check if client really sent some data.
        $bytes = strlen($data);
        if ($bytes === 0 || $data === false) {
            return $this->response('No data', true);
        }

        // Parse the json data. PHPMiner always send json.
        $rpc_data = json_decode($data, true);

        // Verify that incoming data was json.
        if (empty($rpc_data)) {
            return $this->response('No data', true);
        }

        // Verify rpc key is included and match with the configurated one.
        if (!isset($rpc_data) || !isset($rpc_data['rpc_key']) || $rpc_data['rpc_key'] !== $config['rpc_key']) {
            log_console('Incoming data request');
            return $this->response('RPC Key not found or invalid.', true);
        }

        // Verify required parameter: command
        if (!isset($rpc_data['command'])) {
            log_console('Incoming data request');
            return $this->response('No command specified', true);
        }

        //Only log incoming command when it is not a ping.
        if ($rpc_data['command'] !== 'ping') {
            log_console('Incoming data request: ' . $rpc_data['command']);
        }

        $client_api = null;
        
        // Check which api is required.
        if (!isset($rpc_data['api_proxy']) || empty($rpc_data['api_proxy'])) {
            // Get the rpc api.
            $client_api = new RPCClientApi($config);
        }
        else {
            // Get miner api
            $client_api = new CGMinerAPI($config['miner_api_ip'], $config['miner_api_port']);
        }

        // Get the method which will be called.
        $method = $rpc_data['command'];

        // Verify api command exists.
        if (method_exists($client_api, $method)) {

            // Try to call the api command.
            try {
                $result = null;
                
                
                if (!empty($rpc_data['data']) || (!is_array($rpc_data['data']) && $rpc_data['data'] !== "0")) {
                    
                    if (!isset($rpc_data['api_proxy']) || empty($rpc_data['api_proxy'])) {
                        $result = call_user_method($method, $client_api, $rpc_data['data']);
                    }
                    else {
                        $call_args = $rpc_data['data'];
                        if (empty($call_args) && !is_array($call_args) && $call_args . "" !== "0") {
                            $call_args = array();
                        }
                        else if (!is_array($call_args)) {
                            $call_args = array($call_args);
                        }
                        $result = call_user_method_array($method, $client_api, $call_args);
                    }
                }
                else {
                    $result = call_user_method($method, $client_api);
                }
                
                // When result is empty but not an empty string, replace it with default 'ok' 
                if (empty($result) && $result !== '') {
                    $result = 'ok';
                }

                // Sent success response.
                return $this->response($result, false);
            } catch (Exception $ex) {

                // Send error response.
                return $this->response($ex->getMessage(), true);
            }
        }
   
            
            
    }

    /**
     * Disconnects the client.
     */
    public function disconnect() {
        // Shutdown socket.
        stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
    }

    /**
     * Read data from phpminer ressource.
     * 
     * @return boolean|string
     *   Returns the read string, boolean false on error.
     */
    private function read_buffer() {
        $buffer = '';
        $buffsize = 8192;
        $metadata['unread_bytes'] = 0;
        do {
            if (feof($this->socket)) {
                return false;
            }
            $result = fread($this->socket, $buffsize);
            if ($result === false) {
                return false;
            }
            $buffer .= $result;
            $metadata = stream_get_meta_data($this->socket);
            $buffsize = ($metadata['unread_bytes'] > $buffsize) ? $buffsize : $metadata['unread_bytes'];
        } while ($metadata['unread_bytes'] > 0);
        return $buffer;
    }

    /**
     * Writes the given data to the phpminer resource.
     * 
     * @param string $string
     *   The data to write.
     * 
     * @return boolean|int
     *   Returns the written bytes as an integer, boolean false on error.
     */
    private function write_buffer($string) {
        $string_length = strlen($string);
        $fwrite = 0;
        for ($written = 0; $written < $string_length; $written += $fwrite) {
            $fwrite = @fwrite($this->socket, substr($string, $written));
            if ($fwrite === false) {
                return false;
            } elseif ($fwrite === 0) {
                return false;
            }
        }
        return $written;
    }

    /**
     * Send a response to a phpminer connection.
     * 
     * @param string $msg
     *   The message which will send to phpminer.
     * @param boolean $error
     *   When set to true, the response had errors. (Optional, default = false)
     */
    private function response($msg = 'ok', $error = false) {
        // Build response.
        $buffer = json_encode(array(
            'msg' => $msg,
            'error' => $error,
        ));
        // Send to client.
        $this->write_buffer($buffer);
    }

}
