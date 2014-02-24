<?php
/**
 * @copyright Christian Ackermann (c) 2013 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */
require_once 'PHPMinerException.class.php';
require_once 'Config.class.php';
require_once 'PoolConfig.class.php';
require_once 'ParamStruct.class.php';
require_once 'AccessControl.class.php';

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
     * @var PHPMinerRPC
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
     * Holds the access control.
     * 
     * @var AccessControl
     */
    protected $access_control = null;
    
    /**
     * Determines if the request had a fatal error.
     * 
     * @var boolean
     */
    private $fatal_error = false;
    
    /**
     * Load all needed things (configs, api).
     * @throws PHPMinerException
     */
    public function setup_controller() {
        global $system_conf;
        
        // Process updates.
        new Update();
        
        if (isset($system_conf['directory'])) {
            $this->assign('docroot', $system_conf['directory']);
            $this->js_config('docroot', $system_conf['directory']);
        }
        // Get the own config.
        $this->config = Config::getInstance();
        
        $this->access_control = AccessControl::getInstance();
        if ($this->config->enable_access_control) {
            $this->access_control->enable();
            if (!$this->access_control->get_config()->is_empty() && !$this->access_control->check_login()) {
                $this->fatal_error('You are not logged in. Access denied!');
            }
            
        }
        if ($this->controller_name === 'access' && !$this->access_control->is_enabled()) {
            $this->fatal_error('Access control is disabled, to view this page you have to enable it first under main settings. If you run this on your local machine and only you have access, this is not required.', Controller::MESSAGE_TYPE_ERROR);
        
        }
        
        if (isset($system_conf['directory']) && !empty($this->config->latest_version) && $system_conf['version'] !== $this->config->latest_version) {
            $this->add_message('A new version is available, current version <b>' . implode('.', $system_conf['version']) . '</b> - latest version <b>' . implode('.', $this->config->latest_version) . '</b>. <a href="https://phpminer.com" target="_blank">Download</a>. After updating to a new version, do not forget to copy the new index.php from the phpminer_rpcclient and restart the service."', Controller::MESSAGE_TYPE_INFO);
        }
        
        if (empty($this->config->cron_last_run)) {
            $this->add_message('The cronjob never ran! If you configurated it correctly, just wait 1 or 2 minutes, after the cronjob was executed, this message will disappear. If not configurated please have a look at the <a href="' . $system_conf['directory'] . '/README.md" target="_blank">Readme</a>', Controller::MESSAGE_TYPE_INFO);
        }
        else if(round((TIME_NOW - $this->config->cron_last_run) / 60) > 5) {
            $this->add_message('The cronjob has not been executed since 5 minutes. Please check your cronjob config.', Controller::MESSAGE_TYPE_INFO);
        }
       
        // We can not process as a normal controller action when we check for connection within the setup or in case of disconnected connection while reconnecting.
        if ($this->controller_name === 'main' && ($this->action_name === 'check_connection' || $this->action_name === 'connection_reconnect')) {
            return;
        }
        
        if (empty($this->config->rigs)) {
            throw new APIException('No rigs configurated', APIException::CODE_SOCKET_CONNECT_ERROR);
        }
    }
       
    /**
     * Returns the rpc handler for the given rig.
     * 
     * @param string $rig
     *   The rig name.
     * 
     * @return PHPMinerRPC
     *   The PHPMiner RPC client
     * 
     * @throws APIException
     */
    public function get_rpc($rig) {
        $rig_cfg = $this->config->get_rig($rig);
        $rpc = new PHPMinerRPC($rig_cfg['http_ip'], $rig_cfg['http_port'], $rig_cfg['rpc_key'], $this->config->socket_timout);
        $res = $rpc->ping();
        if ($res !== true) {
            throw new APIException("No connection to PHPMiner RCP on Rig <b>" . $rig . "</b>.\n\n<b>Error message</b>\n" . $res, APIException::CODE_SOCKET_CONNECT_ERROR);
        }
        return $rpc;
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
            
            if ($this->fatal_error !== true) {
                // When the controller action has directly a js file, load it.
                if (file_exists(SITEPATH . '/views/' . $this->controller_name . '/' . $this->action_name . '.js')) {
                    $this->add_js('/views/' . $this->controller_name . '/' . $this->action_name . '.js');
                }

                // When the controller action has directly a css file, load it.
                if (file_exists(SITEPATH . '/views/' . $this->controller_name . '/' . $this->action_name . '.css')) {
                    $this->add_css('/views/' . $this->controller_name . '/' . $this->action_name . '.css');
                }
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
    
    public function fatal_error($msg, $no_exception = false) {
        $this->fatal_error = true;
        if (!$no_exception) {
            throw new Exception($msg);
        }
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
