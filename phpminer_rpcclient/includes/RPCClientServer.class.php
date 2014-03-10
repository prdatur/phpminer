<?php
/**
 * Main server, handles the clients.
 */
class RPCClientServer {
    
    /**
     * Holds the current rpc client config data.
     * 
     * @var array
     */
    private $config = array();
    
    /**
     * Holds the master server socket.
     * 
     * @var resource
     */
    private $master = null;
    
    /**
     * Holds the current client.
     * 
     * @var RPCClientConnection 
     */
    private $client = null;
    
    /**
     * If set to true the main listen loop will be stopped.
     * 
     * @var boolean
     */
    private $stop_request = false;
    
    /**
     * Creates a new server.
     * 
     * @param array $config
     *   The config for this rpc client.
     */
    public function __construct($config) {
        $this->config = $config;
    }
    
    /**
     * Start server.
     */
    public function start() {
        log_console('Starting RPC Server at ' . $this->config['ip'] . ' on port ' . $this->config['port']);
        $errno = 0;
        $err = "";
        $this->master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        
        // Non block socket type 
        socket_set_option($this->master, SOL_SOCKET, SO_RCVTIMEO, array("sec" => 10, "usec" => 0));
        socket_set_option($this->master, SOL_SOCKET, SO_LINGER, array('l_onoff' => 1, 'l_linger' => 0));
        socket_set_option($this->master, SOL_SOCKET, SO_REUSEADDR, 1);
        
        @socket_bind($this->master, $this->config['ip'], $this->config['port']);
        while (socket_last_error() === 98) {
            log_console("Socket terminated but address already in use, wait 2 sec's and try again.");
            sleep(2);
            @socket_bind($this->master, $this->config['ip'], $this->config['port']);
        }
        
        $listen = socket_listen($this->master);
        if($listen === FALSE) {
            unlock();
            log_console("Can't listen on socket.");
            exit;
        }
        
        
        $this->listen();
    }
    
    /**
     * Closes main socket
     */
    public function close() {
        if ($this->client !== null) {
            $this->client->disconnect();
            $this->client = null;
        }
        @socket_shutdown($this->master, 2);
        @socket_close($this->master);
        $this->stop_request = true;
    }
    
    /**
     * Listen for incoming connections.
     */
    public function listen() {
        while (!$this->stop_request) {
            
            // Check for changed sockets.
            $socket = @socket_accept($this->master);
            if($socket != null) {
                
                // Get the client which send the data.
                $this->client =new RPCClientConnection($socket);

                // Proccess client.
                if ($this->client) {
                    $this->client->process_connection($this->config);
                }    
                
                // Disconnection client.
                $this->client->disconnect();
                $this->client = null;

            }
            else {
                log_console('Socket error: ' . socket_strerror(socket_last_error($this->master)));
                continue;
            }
            
        }
        @socket_shutdown($this->master, 2);
        @socket_close($this->master);
    }
}