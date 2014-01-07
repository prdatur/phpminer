<?php
/**
 * @copyright Christian Ackermann (c) 2013 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */
require_once 'PHPMinerException.class.php';
require_once 'Config.class.php';
require_once 'PoolConfig.class.php';
require_once 'ParamStruct.class.php';

class Controller {

    /**
     * This message type will be displayed as an info.
     */
    const MESSAGE_TYPE_INFO = 'info';

    /**
     * This message type will be displayed as an error.
     */
    const MESSAGE_TYPE_ERROR = 'error';

    /**
     * This message type will be displayed as a success.
     */
    const MESSAGE_TYPE_SUCCESS = 'success';

    /**
     * Holds all template variables.
     * 
     * @var array
     */
    private $variables = array(
        'cssfiles' => array(),
        'jsfiles' => array(),
        'jsconfig' => array(),
    );

    /**
     * Holds the controller name.
     * 
     * @var string
     */
    private $controller_name = null;

    /**
     * Holds the action name.
     * 
     * @var string
     */
    private $action_name = null;

    /**
     * Holds the api.
     * 
     * @var CGMinerAPI
     */
    protected $api = null;

    /**
     * Holds the config of php cgminer.
     * 
     * @var Config
     */
    protected $config = array();
    
    /**
     * Holds the config of pools.
     * 
     * @var PoolConfig
     */
    protected $pool_config = array();

    /**
     * The request type, can be html or json.
     * 
     * @var string
     */
    private $request_type = '';
    
    /**
     * This will determine if we use cgminer with advanced api or not.
     * 
     * @var boolean
     */
    protected $has_advanced_api = false;

    /**
     * Load all needed things (configs, api).
     * @throws PHPMinerException
     */
    public function setup_controller() {
        global $system_conf;
        $this->assign('docroot', $system_conf['directory']);
        $this->js_config('docroot', $system_conf['directory']);
        // Get the own config.
        $this->config = new Config(SITEPATH . '/config/config.json');
        
        // Check if we didn't configured phpminer yet.
        if ($this->config->is_empty()) {
            // Default settings matched so save them.
            $this->config->remote_port = 4028;
        }
        
        if (!empty($this->config->latest_version) && $system_conf['version'] !== $this->config->latest_version) {
            $this->add_message('A new version is available, current version <b>' . implode('.', $system_conf['version']) . '</b> - latest version <b>' . implode('.', $this->config->latest_version) . '</b>. <a href="https://phpminer.com" target="_blank">Download</a>', Controller::MESSAGE_TYPE_INFO);
        }
        
        if (empty($this->config->cron_last_run)) {
            $this->add_message('The cronjob never ran! If you configurated it correctly, just wait 1 or 2 minutes, after the cronjob was executed, this message will disappear. If not configurated please have a look at the <a href="' . $system_conf['directory'] . '/README.md" target="_blank">Readme</a>', Controller::MESSAGE_TYPE_INFO);
        }
        else if(round((TIME_NOW - $this->config->cron_last_run) / 60) > 5) {
            $this->add_message('The cronjob has not been executed since 5 minutes. Please check your cronjob config.', Controller::MESSAGE_TYPE_INFO);
        }
        
        // Within first run, get the current cgminer config file data.
        if (empty($this->config->cgminer_conf)) {
            
            // Only get config if some path was configurated
            if (!empty($this->config->cgminer_config_path)) {
                $conf = array();
                try {
                    $cgminer_config = new Config($this->config->cgminer_config_path);
                    foreach(array("intensity", "vectors", "worksize", "kernel", "lookup-gap", "thread-concurrency", "shaders", "gpu-engine", "gpu-fan", "gpu-memclock", "gpu-memdiff", "gpu-powertune", "gpu-vddc", "temp-cutoff", "temp-overheat", "temp-target", "expiry", "gpu-dyninterval", "gpu-platform", "gpu-threads", "log", "no-pool-disable", "queue", "scan-time", "scrypt", "temp-hysteresis", "shares", "kernel-path") AS $key) {
                        if ($cgminer_config->get_value($key) !== null) {
                            $conf[$key] = $cgminer_config->$key;
                        }
                    }
                    $this->config->cgminer_conf = $conf;
                }
                catch(PHPMinerException $e) {
                    $this->add_message('PHPMiner was unable to retrieve the content of the cgminer.conf, the following error occured: ' . $e->getMessage(), Controller::MESSAGE_TYPE_ERROR);
                }
            }
            else {
                $this->add_message('You didn\'t configurated the path to cgminer.conf, please set it up first under <a href="' . $system_config['directory'] . '/main/settings">settings</a>, else PHPMiner will not work properly.', Controller::MESSAGE_TYPE_ERROR);
            }
        }
        $this->assign('unsaved_changes', false);
        if (!empty($this->config->cgminer_config_path)) {
            try {
                $cgminer_config = new Config($this->config->cgminer_config_path);
                $cg_conf = $cgminer_config->get_config();
                foreach ($this->config->cgminer_conf AS $key => $val) {
                    // Pools will be not checked because they will be written directly on pool group change.
                    if ($key === 'pools') {
                        continue;;
                    }

                    if (!isset($cg_conf[$key]) || $cg_conf[$key] !== $val) {
                        $this->assign('unsaved_changes', true);
                    }
                }
            }
            catch(Exception $e) {}
        }
        
        // Provide javascript the current configurated cgminer ip and port.
        $this->js_config('cgminer', array(
            'port' => $this->config->remote_port,
        ));
        
        // We can not process as a normal controller action when we check for connection within the setup or in case of disconnected connection while reconnecting.
        if ($this->controller_name === 'main' && ($this->action_name === 'check_connection' || $this->action_name === 'connection_reconnect')) {
            return;
        }
        
        
        // Try to connect to default connection values.
        try {
            $this->api = new CGMinerAPI($this->config->remote_port);
            $this->api->test_connection();
            $advanced_api = $this->api->check('currentpool');
            $this->has_advanced_api = $advanced_api[0]['Exists'] == 'Y';
            $this->assign('has_advanced_api', $this->has_advanced_api);
            $this->js_config('has_advanced_api', $this->has_advanced_api);
        } catch (APIException $ex) {
            // Not configured and also no config. Switch to setup.
            throw new APIException('No connection to cgminer', APIException::CODE_SOCKET_CONNECT_ERROR);
        }
    }
    
    /**
     * Reload the system config.
     */
    public function reload_config() {
        $this->config = new Config(SITEPATH . '/config/config.json');

    }
    
    /**
     * Returns the cgminer api.
     * 
     * @return CGMinerAPI
     *   The cgminer api.
     */
    public function getCGMinerAPI() {
        return $this->api;
    }
    
    /**
     * Load pool config.
     * 
     * @throws PHPMinerException
     */
    public function load_pool_config() {

        // Get the own config.
        $this->pool_config = new PoolConfig();

        // Check if we didn't configured phpminer yet.
        if ($this->pool_config->is_empty()) {
        }
    }

    /**
     * Provide javascript the $value parameter within the Javascript variable phpminer.settings.{$var}
     *
     * If $as_array is set to true the config key given in $var will not be just set, it will first check
     * if an entry already exists which is not an array if so it will be transformed to array($value)
     * Then it will add all future calls just to the config key array
     * For example:
     * js_config('foo','bar', true);
     * js_config('foo','bar2', true);
     *
     * Javascript will have
     * phpminer.settings.foo[0] = 'bar';
     * phpminer.settings.foo[1] = 'bar2';
     *
     * example of transforming previous added non-array value
     *
     * js_config('foo','bar'); => phpminer.settings.foo = 'bar';
     * js_config('foo','bar2', true);
     *
     * will transform it to:
     * phpminer.settings.foo[0] = 'bar';
     * phpminer.settings.foo[1] = 'bar2';
     *
     * using array_key
     *
     * js_config('foo','bar2', true, 'id1');
     * js_config('foo','bar2', true, 'id2');
     *
     * will produce:
     * phpminer.settings.foo['id1'] = 'bar2';
     * phpminer.settings.foo['id2'] = 'bar2';
     *
     * @param string $var
     *   The variable name for the config key.
     * @param mixed $value
     *   Any variable which should be provided within javascript phpminer.settings.*
     * @param boolean $as_array
     *   If the value should be added as a config array (optional, default = false)
     * @param string $array_key
     *   if $as_array is set this value will be used for the array key.
     *   if left empty or set to null a numeric array push will be used (optional, default = null)
     */
    public function js_config($var, $value, $as_array = false, $array_key = null) {
        //Check if we want to just set the value to the config key or if we want
        //to add it to an config array
        if ($as_array == false) {  //Set
            $this->variables['jsconfig'][$var] = $value;
        } else { //We want to add an value to the specified config array
            //If key not exists create it
            if (!isset($this->variables['jsconfig'][$var])) {
                $this->variables['jsconfig'][$var] = array();
            }
            //If exists but is not an array, transform it to an array
            else if (!is_array($this->variables['jsconfig'][$var])) {
                $this->variables['jsconfig'][$var] = array($this->variables['jsconfig'][$var]);
            }

            //Add the value to the config array
            if (is_null($array_key)) {
                $this->variables['jsconfig'][$var][] = $value;
            } else {
                $this->variables['jsconfig'][$var][$array_key] = $value;
            }
        }
    }

    /**
     * Destructor, will render the page when the object will be destroyed.
     */
    public function __destruct() {
        if ($this->request_type === 'html') {
            // When the controller action has directly a js file, load it.
            if (file_exists(SITEPATH . '/views/' . $this->controller_name . '/' . $this->action_name . '.js')) {
                $this->add_js('/views/' . $this->controller_name . '/' . $this->action_name . '.js');
            }

            // When the controller action has directly a css file, load it.
            if (file_exists(SITEPATH . '/views/' . $this->controller_name . '/' . $this->action_name . '.css')) {
                $this->add_css('/views/' . $this->controller_name . '/' . $this->action_name . '.css');
            }

            // Display page.
            include SITEPATH . '/templates/html.tpl.php';
        }
    }

    /**
     * Add the given file path as a javascript file.
     * 
     * @param string $path
     *   The file path to the javascript file.
     */
    public function add_js($path) {
        $this->variables['jsfiles'][] = $path;
    }

    /**
     * Add the given file path as a css file.
     * 
     * @param string $path
     *   The file path to the css file.
     */
    public function add_css($path) {
        $this->variables['cssfiles'][] = $path;
    }

    /**
     * Add a status message.
     * 
     * @param string $message
     *   The message description.
     * @param string $type
     *   The message type. use one of Controller::MESSAGE_TYPE_* (Optional, default = Controller::MESSAGE_TYPE_SUCCESS)
     */
    public function add_message($message, $type = self::MESSAGE_TYPE_SUCCESS) {

        if (!isset($this->variables['messages'])) {
            $this->variables['messages'] = array();
        }
        if (!isset($this->variables['messages'][$type])) {
            $this->variables['messages'][$type] = array();
        }
        $this->variables['messages'][$type][] = $message;
    }

    /**
     * Set the request type.
     * 
     * @param string $type
     *   The request type, can be html or json-
     */
    public function set_request_type($type) {
        $this->request_type = $type;
    }

    /**
     * Set the controller name.
     * 
     * @param string $value
     *   The controller name-
     */
    public function set_controller_name($value) {
        $this->controller_name = $value;
    }

    /**
     * Set the action name.
     * 
     * @param string $value
     *   The action name-
     */
    public function set_action_name($value) {
        $this->action_name = $value;
    }

    /**
     * Set a template variable.
     * 
     * @param string $name
     *   The template variable name.
     * @param mixed $value
     *   The template value.
     */
    public function assign($name, $value) {
        $this->variables[$name] = $value;
    }

    /**
     * Returns the value for the given variable.
     * 
     * @param string $name
     *   The variable name.
     * 
     * @return null|mixed
     *   If variable does not exist it returns null, else the content.
     */
    public function get_variable($name) {
        if (!isset($this->variables[$name])) {
            return null;
        }
        return $this->variables[$name];
    }

    /**
     * Returns wether the template variable exists or not.
     * 
     * @param string $name
     *   The template variable.
     * 
     * @return boolean
     *   true if variable exist, else false.
     */
    public function has_variable($name) {
        return isset($this->variables[$name]);
    }
    
    /**
     * Returns wether the template variable is empty or not.
     * 
     * @param string $name
     *   The template variable.
     * 
     * @return boolean
     *   true if empty, else false.
     */
    public function variable_is_empty($name) {
        return empty($this->variables[$name]);
    }

    /**
     * Called when the controller was not found.
     */
    public function no_such_controller() {
        $this->add_message('No such controller', self::MESSAGE_TYPE_ERROR);
    }

    /**
     * Called when the action was not found within the current controller.
     */
    public function no_such_method() {
        $this->add_message('No such method', self::MESSAGE_TYPE_ERROR);
    }

    /**
     * Called when no config was found so the user need to setup phpchminer
     */
    public function setup() {
        $this->set_controller_name('system');
        $this->set_action_name('setup');
    }

}
