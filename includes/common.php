<?php
@date_default_timezone_set(date_default_timezone_get());

class AccessException extends Exception {}

/**
 * @copyright Christian Ackermann (c) 2013 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */
ini_set('display_errors', 'on');
error_reporting(E_ALL);

define("TIME_NOW", time());
define("TIME_NOW_GMT", strtotime(gmdate("Y-m-d H:i:s", TIME_NOW)));
define("DB_DATE", "Y-m-d");
define("DB_DATETIME", "Y-m-d H:i:s");
define("DB_TIME", "H:i:s");

mb_internal_encoding('UTF-8');

/**
 * This is not a primitive datatype but it can be used as a real not set variable, so if we realy want to check if a
 * parameter was provided to a function/method we can default assign NS so if we pass "", null or something similar to empty
 * it is also a allowed "provided" value. The value behind NS is choosen with a string which should never be a value provided by a user
 */
define("NS", "-||notset||-");

/**
 * Define our primitive datatypes, these are used in several ways.
 * Most use is within parameter type checks.
 */
$i = 1;
define("PDT_INT", $i++, true);
define("PDT_FLOAT", $i++, true);
define("PDT_STRING", $i++, true);
define("PDT_DECIMAL", $i++, true);
define("PDT_DATE", $i++, true);
define("PDT_OBJ", $i++, true);
define("PDT_ARR", $i++, true);
define("PDT_BOOL", $i++, true);
define("PDT_INET", $i++, true);
define("PDT_SQLSTRING", $i++, true);
define("PDT_JSON", $i++, true);
define("PDT_PASSWORD", $i++, true);
define("PDT_ENUM", $i++, true);
define("PDT_TEXT", $i++, true);
define("PDT_TINYINT", $i++, true);
define("PDT_MEDIUMINT", $i++, true);
define("PDT_BIGINT", $i++, true);
define("PDT_SMALLINT", $i++, true);
define("PDT_DATETIME", $i++, true);
define("PDT_TIME", $i++, true);
define("PDT_FILE", $i++, true);
define("PDT_LANGUAGE", $i++, true);
define("PDT_LANGUAGE_ENABLED", $i++, true);
define("PDT_SERIALIZED", $i++, true);
define("PDT_BLOB", $i++, true);

require 'Db.class.php';
require 'Updates.class.php';
require 'HttpClient.class.php';
require 'PHPMinerRPC.class.php';
require 'Controller.class.php';


$system_conf['version'] = array(1, 2, 1);

// Prevent error message that directory is not set.
$system_conf['directory'] = '';
if (isset($_SERVER['REQUEST_URI']) && preg_match("/^(.+)?\/(main\/|gpu\/|access\/|notify\/|pools\/|$|index.php)/",$_SERVER['REQUEST_URI'], $matches)) {
    $system_conf['directory'] = $matches[1];
}

function murl($controller, $action = null, $data = null, $is_json = false) {
    global $system_conf;
    
    if (empty($system_conf['directory']) || $system_conf['directory'] === '/') {
        $url = '/' . $controller;
        if ($action != null) {
            $url .= '/' . $action;
            
            if ($data != null) {
                $url .= '/' . $data;
            }
        }
        if ($is_json) {
            $url .= '.json';
        }
        return $url;
    }
    else {
        $url = $system_conf['directory'] . '/index.php?controller=' . $controller;
        if ($action != null) {
            $url .= '&action=' . $action;
            
            if ($data != null) {
                $url .= '&data=' . $data;
            }
        }
        if ($is_json) {
            $url .= '&type=json';
        }
        return $url;
    }
}