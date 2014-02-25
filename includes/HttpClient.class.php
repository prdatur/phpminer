<?php
/**
 * Provide an easy lightwight http client which uses curl.
 * It can do post's and get's with or without ssl and/or arguments.
 *
 * @copyright Christian Ackermann (c) 2013 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category NetTools
 */
class HttpClient {

        /**
         * Holds the current fetched content.
         *
         * @var string
         */
        protected $current_content = "";

        /**
         * Whether to accept cookies or not
         * @var boolean
         */
        protected $usecookie = true;

        /**
         * The file path where we store the cookie.
         *
         * @var string
         */
        protected $cookie_file_path = '';

        /**
         * Current referer.
         *
         * @var string
         */
        protected $current_referer = "";

        /**
         * Header data.
         *
         * @var array
         */
        protected $header_data = array();

        /**
         * Setup defaults.
         */
        public function __construct() {
                $this->cookie_file_path = SITEPATH . '/uploads/httpclient_cookie_' . uniqid() . '.txt';
        }

        /**
         * Resets the hole request.
         * This will clear current referer, current body content and current cookie file content
         * if it is present.
         */
        public function reset() {
                $this->current_content = "";
                if (!empty($this->cookie_file_path) && is_writable($this->cookie_file_path)) {
                        file_put_contents($this->cookie_file_path, "");
                }
                $this->reset_referer();
        }

        /**
         * Resets the current referer.
         */
        public function reset_referer() {
                $this->current_referer = "";
        }

        /**
         * Set/Change the cookie path.
         *
         * If previous cookie path is not empty we first try to delete it.
         * The new path will be only active if the directory for the file exist and is writeable.
         *
         * @param string $file_path
         *   the file path where we should store the cookies
         *
         * @return boolean returns true if file exists and is writeable
         *   else false
         */
        public function set_cookie_file_path($file_path) {
                $dir = dirname($this->cookie_file_path);

                if (file_exists($this->cookie_file_path)) {
                        if (!is_writable($this->cookie_file_path)) {
                                return false;
                        }
                }
                else {
                        if (!file_exists($dir) || !is_writable($dir)) {
                                return false;
                        }
                }

                if (!(empty($this->cookie_file_path)) && file_exists($this->cookie_file_path)) {
                        @unlink($this->cookie_file_path);
                }

                $this->cookie_file_path = $file_path;

                return true;
        }

        /**
         * Returns the full path to the cookie file.
         *
         * @return string the path.
         */
        public function get_cookie_file_path() {
                return $this->cookie_file_path;
        }

        /**
         * Clean up system before destroying the class.
         */
        public function __destruct() {
                if (file_exists($this->cookie_file_path)) {
                        @unlink($this->cookie_file_path);
                }
        }

        /**
         * Add the given header data.
         *
         * @param string $key
         *   The header key.
         * @param string $value
         *   The header value.
         * @param boolean $urlencode
         *   If set to true the value will be url encoded. (optional, default = false)
         */
        public function add_header($key, $value, $urlencode = false) {
                if ($urlencode) {
                        $value = urlencode($value);
                }
                $this->header_data[$key] = $value;
        }
        
        /**
         * Check the given server if it is up and running and the worker credentials are correct.
         * 
         * @param string $host
         *   The server ip.
         * @param int $port
         *   The server port.
         * @param string $type
         *   The server name, can be stratum or http
         * @param string $user
         *   The worker username
         * @param string $password
         *   The worker password.
         * 
         * @return string|boolean
         *   If server is up and username/password correct it will return boolean true, else the error message.
         */
        public function check_pool($host, $port, $type, $user, $password) {
            if ($type === 'stratum') {
                $data = "";
                $data .= json_encode(array('id' => 1, 'method' => 'mining.authorize', 'params' => array($user, $password))) . "\n";

                // Check ip and port.
                $socket = @fsockopen($host, $port, $errno, $errstr, 2);
                if ($socket === false) {
                    return "Can not connect to the given stratum server.";
                }
                
                @socket_set_timeout($socket, 1);
                
                // Check stratum + user / password.
                
                if (!@fwrite($socket, $data)) {
                    return "Can not send data to stratum server.";
                }
                $response = "";
                $i = 0;
                while (($data = @fread($socket, 128)) !== false) {
                    $response .= $data; 
                    // Stop after failover or the first real response.
                    if ($i++ >= 10 || preg_match("/\{[^{}]+\}/", $response)) {
                        break;
                    }
                }
                fclose($socket);
                if (empty($response)) {
                    return 'Could not recieve data from provided stratum server.';
                }
                
                // Responses are each seperated by a new line.
                $json_data_array = explode("\n", $response);
                
                // Only check first response which is the response for our authorize check-
                $connection_result = json_decode($json_data_array[0], true);
                
                if ($connection_result === null) {
                    return "The stratum server response with invalid data.";
                }

                if (isset($connection_result['error'])) {
                    return $connection_result['error'][1];
                }
                
                if (isset($connection_result['result']) && $connection_result['result'] === false) {
                    return 'Username and/or password incorrect.';
                }

                return true;
            }
            else if($type === 'http') {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_USERPWD, $user . ":" . $password); 
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array(
                    'id' => 1,
                    'method' => 'fsdfs',
                    'params' => array(),
                )) . "\n");

                $result = $this->execute($host . ':' . $port, $ch, false);
                
                
                if (empty($result)) {
                    return 'Unknown error occured or could not connect to server.';
                }
                if (stripos($result, '401 Unauthorized') !== false) {
                    return 'Username and/or password incorrect.';
                }
                
                $connection_result = json_decode($result, true);
                if ($connection_result === null) {
                    return "The stratum server response with invalid data.";
                }

                if (isset($connection_result['error'])) {
                    if ($connection_result['error']['message'] === 'Method not found') {
                        return true;
                    }
                    return $connection_result['error']['message'];
                }

                return 'Unknown error occured.';
            }
        }

        /**
         * Send a GET request to the specified url.
         *
         * @param string $url
         *   the url
         * @param array $args
         *   The arguments which will be appended through ? http_build_query()
         * @param boolean $use_ssl
         *   Set to true to force ssl
         *   this will replace http to https if set to true
         *   (optional, default = false)
         *
         * @return string the body content
         */
        public function do_get($url, $args = array(), $use_ssl = false) {
                if (!empty($args)) {
                        if (!preg_match("/\?/", $url)) {
                                $url .= "?";
                        }

                        $url .= http_build_query($args);
                }
                $null = null;
                return $this->execute($url, $null, $use_ssl);
        }

        /**
         * Send a POST request to the specified url.
         *
         * @param string $url
         *   the url
         * @param array $args
         *   The arguments which will be appended through ? http_build_query()
         * @param boolean $use_ssl
         *   Set to true to force ssl
         *   this will replace http to https if set to true
         *   (optional, default = false)
         *
         * @return string the body content
         */
        public function do_post($url, $args = array(), $use_ssl = false) {

                $ch = curl_init();

                if (!empty($args)) {
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
                }

                return $this->execute($url, $ch, $use_ssl);
        }

        /**
         * Sends a HTTP-Request to the specified url.
         *
         * @param string $url
         *   the url.
         * @param resource $ch
         *   the ressource returned by curl_init
         *   if not provided it will create a new one.
         *   (optional, default = null)
         * @param boolean $use_ssl
         *   Set to true to force ssl
         *   this will replace http to https if set to true
         *   (optional, default = false)
         *
         * @return string the body content
         */
        protected function execute($url, &$ch = null, $use_ssl = false) {

                if (!empty($use_ssl)) {
                        $url = preg_replace('/^http:\/\//', 'https://', $url);
                }

                if (empty($ch)) {
                        $ch = curl_init();
                }

                curl_setopt($ch, CURLOPT_URL, $url);

                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

                curl_setopt($ch, CURLOPT_HEADER, 0);

                if ($this->usecookie) {
                        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file_path);
                        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file_path);
                }
                if ($this->current_referer != "") {
                        curl_setopt($ch, CURLOPT_REFERER, $this->current_referer);
                }

                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

                if (!empty($this->header_data)) {

                        $header = array();
                        foreach ($this->header_data AS $k=>$v) {
                                $header[] = $k.": ".$v;
                        }
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

                        $this->header_data = array();
                }
                // Prevent a redirect loop.
                curl_setopt($ch, CURLOPT_MAXREDIRS, 15);

                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $result = curl_exec($ch);
                curl_close($ch);
                $this->current_content = $result;

                $this->current_referer = $url;
                return $result;
        }
}