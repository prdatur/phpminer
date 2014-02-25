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
     * Holds all sockets.
     * 
     * @var array
     */
    private $sockets = array();
    
    /**
     * Holds connected clients.
     * 
     * @var array
     */
    private $clients = array();
    
    /**
     * Holds the master server socket.
     * 
     * @var resource
     */
    private $master = null;
    
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
        $this->master = @stream_socket_server('tcp://' . $this->config['ip'] . ':' . $this->config['port'], $errno, $err, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, stream_context_create());
        if ($this->master === false) {
            unlock();
            log_console("Could not start rpc client, Network interface not available");
            exit;
        }
        $this->sockets[] = $this->master;
        
        $this->listen();
    }
    
    /**
     * Listen for incoming connections.
     */
    public function listen() {
        while (true) {
            
            // Check for changed sockets.
            $changed_sockets = $this->sockets;
            $except = null;
            @stream_select($changed_sockets, $write = null, $except, 10);
            
            // Loop through each changed socket.
            foreach ($changed_sockets as $socket) {
                
                // Check if a new client is connecting
                if ($socket == $this->master) {
                    
                    // Get the new client.
                    if (($client_socket = stream_socket_accept($this->master)) === false) {
                        log_console('Socket error: ' . socket_strerror(socket_last_error($this->master)));
                        continue;
                    }
                    
                    // Create client data.
                    $client = new RPCClientConnection($client_socket);

                    // Add the client to our current client list.
                    $this->clients[(int) $client_socket] = $client;
                    $this->sockets[$client->getId()] = &$client_socket;
                    
                } else {
                    
                    // Get the client which send the data.
                    $client = $this->get_user_by_socket($socket);
                    
                    // Proccess client.
                    if ($client) {
                        $client->process_connection($this->config);
                    }    
                    // Disconnection client.
                    $this->disconnect($socket);
                    
                }
            }
        }
    }
    
    /**
     * Returns the client given by the $socket.
     * 
     * @param resource $socket
     *   The socket.
     * 
     * @return RPCClientConnection|null
     *   The client or null if client does not exists.
     */
    private function get_user_by_socket($socket) {
        if (isset($this->clients[(int) $socket])) {
            return $this->clients[(int) $socket];
        }
        return null;
    }
    
    /**
     * Disconnects the client.
     * 
     * @param resource $client
     *   The client to close.
     */
    private function disconnect($socket) {
        if (!$socket) {
            return;
        }
        $client_ident = (int) $socket;
        // Check the client socket exists within our clients.
        if (isset($this->clients[$client_ident])) {
            
            // Get the client.
            $client = $this->clients[$client_ident];
            $client_id = $client->getId();
            /* @var $client RPCClientConnection */
            
            // Disconnection client.
            $client->disconnect();
                        
            // Remove from client list.
            unset($this->sockets[$client_id]);
            unset($this->clients[$client_ident]);
        }
    }
}