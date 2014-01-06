<?php
if (!defined('SITEPATH')) {
        define('SITEPATH', dirname(__FILE__));
}
require 'includes/common.php';

// Get the request current path.
$url_decoded_request_array = parse_url(urldecode($_SERVER['REQUEST_URI']));

// Get request type. .json is handled as json, anything else as html.
if (preg_match("/\.json$/", $url_decoded_request_array['path'])) {
    
    // We only need ajax within ajax requests.
    include './includes/AjaxModul.php';
    
    // Remove the .json ending.
    $url_decoded_request_array['path'] = preg_replace("/\.json$/", "", $url_decoded_request_array['path']);
    
    // Set type to json / ajax.
    $set_request_type = 'json';
}
else {
    // Set type to normal html.
    $set_request_type = 'html';
}

// Get all params as an array.
$params = explode("/", $url_decoded_request_array['path']);

// Remove special chars.
foreach ($params AS &$param) {
    $param = preg_replace("/\W/", "", $param);
} 
// Get the controller.
array_shift($params);
if (empty($params[0])) {
    $params = array('main');
}

// Check if controller exist
if (!file_exists('controllers/' . $params[0] . '.php')) {
    $controller = new Controller();
    $controller->no_such_controller();
    unset($controller);
    exit();
}

// Include the controller.
include './controllers/' . $params[0] . '.php';

// Create controller instance.
$controller_name = strtolower($params[0]);
$controller = new $controller_name();

try {
    // Set request type.
    $controller->set_request_type($set_request_type);
        
    // Get the controller action.
    array_shift($params);
    if (!isset($params[0])) {
        $params[0] = 'init';
    }
    $method = strtolower($params[0]);
    array_shift($params);

    // Check if controller action exists.
    if (!method_exists($controller, $method)) {
        $controller->no_such_method();
    }
    else {
    
        // Provide the view the controller and action name.
        $controller->set_controller_name($controller_name);
        $controller->set_action_name($method);

        // Try to init the controller. This will also create the api class and check for needec config files.
        $controller->setup_controller();

        // Call the controller action with optional additional params.
        call_user_func_array(array($controller, $method), $params);
    }

}
catch (Exception $e) {
    if ($set_request_type === 'html') {
        switch ($e->getCode()) {
            // We also want to get back to the setup screen to choose ip and port when we couldn't connect to the api.
            // There the existing connection settings will be pre-filled.
            case APIException::CODE_SOCKET_CONNECT_ERROR:
                $controller->setup();
                break;

            // Bad bad, display errors.
            case PHPMinerException::CODE_CONFIG_NOT_READABLE:
            case PHPMinerException::CODE_CONFIG_NOT_WRITEABLE:
                $controller->add_message($e->getMessage(), Controller::MESSAGE_TYPE_ERROR);
                break;
        }
    }
    else {
        AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $e->getMessage());
    }
}
// Display the view.
unset($controller);