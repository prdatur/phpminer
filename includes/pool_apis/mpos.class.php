<?php
/**
 * 
 * @copyright Christian Ackermann (c) 2013 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */
require_once 'PoolAPI.class.php';

class mpos extends PoolAPI {
    
    /**
     * Magic method to easy handle mpos api call.
     * They all just accept one parameter, the api key.
     * Only the command method change.
     * 
     * @param string $name
     *   The method which was called.
     * @param mixed $arguments
     *   The arguments.
     * 
     * @return array
     *   The api response data.
     */
    public function __call($name, $arguments) {
        switch ($name) {
            case 'getblockcount':
            case 'getblocksfound':
            case 'getcurrentworkers':
            case 'getdashboarddata':
            case 'getdifficulty':
            case 'getestimatedtime':
            case 'gethourlyhashrates':
            case 'getnavbardata':
            case 'getpoolhashrate':
            case 'getpoolsharerate':
            case 'getpoolstatus':
            case 'gettimesincelastblock':
            case 'gettopcontributors':
            case 'getuserbalance':
            case 'getuserhashrate':
            case 'getusersharerate':
            case 'getuserstatus':
            case 'getuserworkers':
            case 'public':
                return $this->call($name);
        }
        throw new Exception('No such method');
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
    protected function request($method) {
        $http_client = new HttpClient();
        $result =  $http_client->do_get($this->pool_url . '/index.php', array(
            'page' => 'api',
            'action' => $method,
            'api_key' => $this->api_key,
            
        ));
        
        return json_decode($result, true);
    }
}