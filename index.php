<?php
ob_start();
$phpminer_error_handler_messages = array();
$phpminer_error_handler_suppressed_messages = array();
if (!defined('SITEPATH')) {
    define('SITEPATH', dirname(__FILE__));
}
$phpminer_request_is_ajax = false;
require 'includes/ErrorHandler.class.php';
set_error_handler(array('ErrorHandler', 'cc_error_handler'), E_ALL);

function fatal_handler() {
    $errfile = "unknown file";
    $errstr = "shutdown";
    $errno = E_CORE_ERROR;
    $errline = 0;


    $error = error_get_last();
    $stack = array();
    if (function_exists('xdebug_get_function_stack')) {
        foreach (array_slice(xdebug_get_function_stack(), 1, -1) as $row) {
            if (isset($row['class'])) {
                $row['type'] = isset($row['type']) && $row['type'] === 'dynamic' ? '->' : '::';
            }
            if (isset($row['params'])) {
                $row['args'] = $row['params'];
            }
            $stack[] = $row;
        }
    }
    
    if ($error !== NULL) {
        $errno = $error["type"];
        $errfile = $error["file"];
        $errline = $error["line"];
        $errstr = $error["message"];
    }
    global $phpminer_request_is_ajax, $phpminer_error_handler_messages;
    
    ErrorHandler::cc_error_handler($errno, $errstr, $errfile, $errline, "", true, $stack);
    $error = $phpminer_error_handler_messages;

    if (empty($error)) {
        return;
    }
    if ($phpminer_request_is_ajax) {
        $code = 560;
        $data = sys_get_temp_dir() . '/phpminer_' . uniqid() . '.bugreport';
        $return = array("code" => $code, "desc" => null, "data" => $data);
        file_put_contents($data, $error);
        echo json_encode($return);
        die();
    }
    echo implode("<br>", $error);
    die();
}
register_shutdown_function("fatal_handler");

require 'includes/common.php';


if (isset($_GET['controller'])) {
    $url = '/' . $_GET['controller'];
    if (isset($_GET['action'])) {
        $url .= '/' . $_GET['action'];
    }
    if (isset($_GET['data'])) {
        $url .= '/' . $_GET['data'];
    }
    if (isset($_GET['type']) && $_GET['type'] === 'json') {
        $url .= '.json';
    }
    $_SERVER['REQUEST_URI'] = urlencode($url);
}

$request_uri = urldecode($_SERVER['REQUEST_URI']);
if ($system_conf['directory'] !== '/') {
    $request_uri = str_replace($system_conf['directory'], '', $request_uri);
}

// Get the request current path.
$url_decoded_request_array = parse_url($request_uri);

// Get request type. .json is handled as json, anything else as html.
if (preg_match("/\.json$/", $url_decoded_request_array['path'])) {
    $phpminer_request_is_ajax = true;

    // We only need ajax within ajax requests.
    include './includes/AjaxModul.php';

    // Remove the .json ending.
    $url_decoded_request_array['path'] = preg_replace("/\.json$/", "", $url_decoded_request_array['path']);

    // Set type to json / ajax.
    $set_request_type = 'json';
} else {
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
    } else {

        // Provide the view the controller and action name.
        $controller->set_controller_name($controller_name);
        $controller->set_action_name($method);

        // Try to init the controller. This will also create the api class and check for needec config files.
        $controller->setup_controller();

        // Call the controller action with optional additional params.
        call_user_func_array(array($controller, $method), $params);
    }
} catch (Exception $e) {
    if ($set_request_type === 'html') {

        if ($e instanceof AccessException) {
            $controller->fatal_error($e->getMessage(), true);
        }
        switch ($e->getCode()) {
            // We also want to get back to the setup screen to choose ip and port when we couldn't connect to the api.
            // There the existing connection settings will be pre-filled.
            case APIException::CODE_SOCKET_CONNECT_ERROR:
                $controller->setup();
                break;
            // Bad bad, display errors.
            default:
                $controller->add_message($e->getMessage(), Controller::MESSAGE_TYPE_ERROR);
                break;
        }
    } else {
        AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $e->getMessage());
    }
}
// Display the view.
unset($controller);
