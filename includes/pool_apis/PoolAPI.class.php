<?php
/**
 * 
 * @copyright Christian Ackermann (c) 2013 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */
abstract class PoolAPI {
    
    /**
     * Holds the user's pool api key.
     * 
     * @var string
     */
    protected $api_key = null;
    
    /**
     * Holds the api url for the pool.
     * 
     * @var string
     */
    protected $pool_url = null;
    
    /**
     * Creates a new pool api instance.
     * 
     * @param string $pool_url
     *   The pool url.
     * @param string $api_key
     *   The pool api key from the user.
     */
    public function __construct($pool_url, $api_key) {
        $this->pool_url = $pool_url;
        $this->api_key = $api_key;
    }
    
    /**
     * Makes a api request.
     * 
     * @param string $method
     *   The api command.
     * 
     * @return array 
     *   The api response data.
     */
    protected function call($method) {
        return $this->request($method);
    }
    /**
     * This will be called when a request is made.
     * 
     * @param string $method
     *   The api command.
     * 
     * @return array
     *   The api response data.
     */
    abstract protected function request($method);
}