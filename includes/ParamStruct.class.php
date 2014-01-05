<?php
/**
 * @copyright Christian Ackermann (c) 2013 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */
require_once 'validators/IssetValidator.class.php';
require_once 'validators/RequiredValidator.class.php';
/**
 * Provides a parameter structure, should be always be used if you want to
 * handle get or post variables
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category InputOutput
 */
class ParamStruct {

    /**
     * Define constances
     */
    const CHECK_TYPE_ALL = 0;
    const CHECK_TYPE_REQUIRED = 0;
    const CHECK_TYPE_ISSET = 0;

    /**
     * Define fill types.
     */
    const FILL_FROM_POST = 2;
    const FILL_FROM_GET = 1;
    const FILL_FROM_GP = 0;

    /**
     * The struct container which holds all definitions
     *
     * @var array
     */
    private $struct = array();

    /**
     * Holds validators for specific fields
     * @var array
     */
    private $validators = array();

    /**
     * String if we only want to return one error, else an array with all errors
     * @var mixed
     */
    private $validator_error = "";

    /**
     * A map which can be used as alias fields to for example map as is provided for foo as bar
     * we can access the field foo with bar, be aware within get_values we only get the alias, not the original one
     *
     * @var array
     */
    private $keymap = array();

    /**
     * The submit name, will be used to check if we have submitted (provided values)
     *
     * @var string
     */
    private $submit_name = "";

    /**
     * Determines if we have provided data
     * @var boolean
     */
    private $is_submitted = false;

    /**
     * Holds all fields which had an error
     * @var array
     */
    private $errors = array();

    /**
     * Construct
     *
     * @param string $submit_name
     *   The submit name (optional, default = NS)
     */
    public function __construct($submit_name = NS) {
        $this->submit_name = $submit_name;
    }

    /**
     * Returns if the provided submit key was found. (use construct("submit_name")
     *
     * @return boolean true if form was submitted, else false
     */
    public function is_submitted() {
        return $this->is_submitted;
    }

    /**
     * Get the database struct
     *
     * @return array the struct
     */
    public function get_struct() {
        return $this->struct;
    }

    /**
     * After calling is_valid you can get all generated errors with this method.
     * Each entry contains a error message.
     * 
     * @return array
     */
    public function get_errors() {
        return $this->validator_error;
    }
    /**
     * Checks all validators if they are valid
     *
     * If $return_more is set to true it will break up after finding the first invalid validator and set this
     * error to our validator_error array
     *
     * @param boolean $return_more
     *   Set to true if you want to have all validator errors not the first invalid one (optional, default = false)
     *
     * @return boolean returns true if all validators are valid, else false
     */
    public function is_valid($return_more = false) {
        //Returns valid because we have no validators
        if (count($this->validators) <= 0) {
            return true;
        }
        //If we want all validator errors we need to initialize the error variable to an array
        if ($return_more == true) {
            $this->validator_error = array();
        }

        //Loop through all validators first foreach just loop all fields which we have setup
        foreach ($this->validators AS $field => $validators) {

            //Loop through all validators within the fields
            foreach ($validators AS $validator) {
                //Check if the validator is valid and if not we add or set the error message
                if (!$validator->is_valid()) {
                    $this->errors[$field] = $field;
                    if ($return_more == false) {
                        $this->validator_error = $validator->get_error();
                        return false;
                    } else {
                        $this->validator_error[] = $validator->get_error();
                    }
                }
            }
        }
        if ($return_more == true) {
            if (count($this->validator_error) > 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * Add a validator to a parameter
     *
     * @param string $field
     *   the parameter field
     * @param mixed $validators
     *   can be an array with AbstractHtmlValidator or a direct AbstractHtmlValidator
     */
    public function add_validator($field, $validators) {
        //Transform a direct validator parameter to an array
        if (!is_array($validators)) {
            $validators = array($validators);
        }

        //If no validators exist for that field, initialize it with an empty array
        if (!isset($this->validators[$field])) {
            $this->validators[$field] = array();
        }

        //Loop through all validator which we want to add and add the parameter value as the validator value
        foreach ($validators AS $validator) {
            $this->validators[$field][$validator->get_type()] = $validator;
        }
    }

    /**
     * Returns if the param is required
     *
     * @param string $param
     *   the param
     *
     * @return boolean true if the param is a required param, else false
     */
    public function is_required_param($param) {
        return (isset($this->validators[$param]) && isset($this->validators[$param]['RequiredValidator']));
    }

    /**
     * Returns if the param must be set (but can be empty)
     *
     * @param string $param
     *   The param
     *
     * @return boolean true if the param is a isset param, else false
     */
    public function is_isset_param($param) {
        return (isset($this->validators[$param]) && isset($this->validators[$param]['IssetValidator']));
    }

    /**
     * Returns true if struct has required parameters
     *
     * @return boolean true if struct has required params, else false
     */
    public function has_required_params() {
        foreach ($this->validators AS &$validators) {
            if (isset($validators['RequiredValidator'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns true if struct has isset parameters
     *
     * @return boolean true if struct has isset params, else false
     */
    public function has_isset_params() {
        foreach ($this->validators AS &$validators) {
            if (isset($validators['IssetValidator'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if struct has param
     *
     * @param string $param
     *   The param
     *
     * @return boolean returns true if exists, else false
     */
    public function has_param($param) {
        return isset($this->struct[$param]);
    }

    /**
     * Returns the struct param
     *
     * @param string $param
     *   The parameter name
     *
     * @return mixed if exist return the field, else false
     */
    public function get_param($param) {
        if (isset($this->struct[$param])) {
            return $this->struct[$param];
        }
        return false;
    }

    /**
     * Returns the type for that param
     *
     * @return int the type based on constants PDT_*, if struct has not the parameter returns -1
     */
    public function get_param_type($key) {
        if (!$this->has_param($key)) {
            return -1;
        }
        return $this->struct[$key]['typ'];
    }

    /**
     * Returns the values
     *
     * @return array the values
     */
    public function get_values() {
        $return_array = array();

        //Loop through all parameters and add the returning value, we do not add the value if the key has an alias (keymap)
        foreach ($this->struct AS $name => $data) {
            if (isset($data['mapped']) && $data['mapped'] == true) {
                continue;
            }
            $return_array[$name] = $this->__get($name);
        }

        //Add all mapped values (aliase)
        foreach ($this->keymap AS $name => $data) {
            $return_array[$name] = $this->__get($data);
        }

        return $return_array;
    }

    /**
     * Add a struct param
     *
     * @param string $name
     *   The param name
     * @param int $typ
     *   The param typ ( use one of PDT_*)
     * @param mixed $default_value
     *   Default value for this param (optional, default = '')
     * @param mixed $map_as
     *   Map the parameter to another name (optional, default = '')
     */
    public function add_param($name, $typ, $default_value = "", $map_as = "") {
        $this->struct[$name]['typ'] = $typ;
        $this->struct[$name]['mapped'] = false;
        if (!empty($default_value)) {
            $this->struct[$name]['default'] = $default_value;
        }

        if (!empty($map_as)) {
            $this->struct[$name]['mapped'] = true;
            $this->keymap[$map_as] = $name;
        }
    }

    /**
     * Add a struct param which is required and cannot be empty
     *
     * @param string $name
     *   The param name
     * @param int $typ
     *   The param typ ( use one of PDT_*)
     * @param mixed $map_as
     *   Map the parameter to another name (optional, default = '')
     */
    public function add_required_param($name, $typ, $map_as = "") {
        $this->add_param($name, $typ, '', $map_as);
        $this->add_validator($name, new RequiredValidator('Missing parameter: ' . $name));
    }

    /**
     * Add a struct param which can be empty but must be set
     *
     * @param string $name
     *   The param name
     * @param int $typ
     *   The param typ ( use one of PDT_*)
     * @param mixed $map_as
     *   Map the parameter to another name (optional, default = '')
     */
    public function add_isset_param($name, $typ, $map_as = "") {
        switch ((int) $typ) {
            case PDT_FILE:
                trigger_error("ParamStruct::addIssetParam() : " . $typ . " is unsuppoerted for this function, use Required or just addParam instead", E_USER_ERROR);
                exit();
        }
        $this->add_param($name, $typ, '', $map_as);
        $this->add_validator($name, new IssetValidator());
    }

    /**
     * Get the default value for the given param
     *
     * @param string $name
     *   The param
     *
     * @return mixed The default value, if value doesnt exist or no default value exist return null
     */
    public function get_default_value($name) {
        if (isset($this->struct[$name]) && isset($this->struct[$name]['default'])) {
            return $this->struct[$name]['default'];
        } else {
            return null;
        }
    }

    /**
     * Checks if value is set
     *
     * @return boolean true if isset, else false
     */
    public function __isset($name) {
        return isset($this->struct[$name]['value']);
    }

    /**
     * Get the value of given name
     *
     * Returns the default value if empty and the struct has the parameter,<br />
     * returns null if the struct has no such element
     *
     * @return mixed the value parsed as the typ for that parameter (PDT_*)
     */
    public function __get($name) {

        //Check if the wanted parameter is not set or it is empty
        if (!isset($this->struct[$name]) || $this->is_empty($this->struct[$name]['value'], $this->get_param_type($name))) {

            //We need to check if we have a mapping for that alias return the value for the alias
            if (isset($this->keymap[$name])) {
                return $this->__get($this->keymap[$name]);
            }
            //If we have a parameter with that name return the value
            else if ($this->has_param($name)) {
                return $this->parse_value($this->get_default_value($name), $this->get_param_type($name));
            } else {
                return null;
            }
        }
        //Return the default value if it is not empty and set
        return $this->struct[$name]['value'];
    }

    /**
     * Set the value of given name
     *
     * @param string $name
     *   The name
     * @param mixed $value
     *   The value
     */
    public function __set($name, $value) {

        //Return if we have no such parameter
        if (!$this->has_param($name)) {
            return;
        }
        $this->struct[$name]['value'] = $this->parse_value($value, $this->get_param_type($name));
    }

    /**
     * Fill the struct with the wanted values
     *
     * $type = ParamStruct::FILL_FROM_POST = Fill the array from $_POST<br />
     * $type = ParamStruct::FILL_FROM_GET = Fill the array from $_GET<br />
     * $type = ParamStruct::FILL_FROM_GP = Fill the array from $_GET and override it with $_POST<br />
     * $type = array = Direct fill the array from the given one
     *
     * @param mixed $type
     *   can be an array to fill direct or a string one of ParamStruct::FILL_FROM_* (optional, default = ParamStruct::FILL_FROM_POST)
     */
    public function fill($type = self::FILL_FROM_POST) {

        $set_values = array();
        if (is_array($type)) {
            $set_values = $type;
        } else {
            if ($type == self::FILL_FROM_GP || $type == self::FILL_FROM_GET) {
                $set_values = array_merge($set_values, $_GET);
            }
            if ($type == self::FILL_FROM_GP || $type == self::FILL_FROM_POST) {
                $set_values = array_merge($set_values, $_POST);
            }
        }
        foreach ($set_values AS $k => $v) {
            //If we want setup a setup name we check if it was filled, if true set is_submitted to true
            if ($this->submit_name != NS && $this->submit_name == $k) {
                $this->is_submitted = true;
                continue;
            }
            $this->__set($k, $v);

            if (isset($this->validators[$k])) {
                foreach ($this->validators[$k] AS &$validator) {
                    $validator->set_value($v);
                }
            }
        }
    }

    /**
     * Setup uploaded files
     */
    public function get_files() {
        foreach ($_FILES AS $k => $v) {
            $this->__set($k, $v);
        }
    }

    /**
     * parse value to the given type
     *
     * @param mixed $value
     *   the value
     * @param int $type
     *   based on PDT_*
     *
     * @return mixed returns the value parsed to the given type
     */
    private function parse_value($value, $type) {
        switch ((int) $type) {
            case PDT_BLOB: return $value;
            case PDT_ENUM:
            case PDT_TEXT:
            case PDT_PASSWORD:
            case PDT_LANGUAGE:
            case PDT_LANGUAGE_ENABLED:
            case PDT_STRING: return '' . trim($value);
            case PDT_JSON: return json_encode($value);
            case PDT_TINYINT:
            case PDT_SMALLINT:
            case PDT_MEDIUMINT:
            case PDT_BIGINT:
            case PDT_INT: return (int) $value;
            case PDT_BOOL: return ($value . "" == "1" || $value . "" == "true") ? true : false;
            case PDT_DECIMAL:
            case PDT_FLOAT: return (float) str_replace(",", ".", $value);
            case PDT_FILE: return $value;
            case PDT_ARR: return (is_array($value)) ? $value : array();
            case PDT_DATE: return ($this->is_empty($value, $type)) ? '' : date(DB_DATE, strtotime($value));
            case PDT_DATETIME: return ($this->is_empty($value, $type)) ? '' : date(DB_DATETIME, strtotime($value));
            case PDT_TIME: return ($this->is_empty($value, $type)) ? '' : date(DB_TIME, strtotime($value));
            default: return trim($value);
        }
    }

    /**
     * Self empty function
     *
     * Check if the given value is empty based up on the given type
     *
     * @param mixed $value
     *   the value
     * @param int $type
     *   based on PDT_*
     *
     * @return boolean returns true on success, else false, if type == PDT_FILE returns false if length <= 0 or tmp_file == empty else true
     */
    public function is_empty(&$value, $type) {

        switch ((int) $type) {
            case T_FILE :
                if (empty($value['size']) || empty($value['tmp_name'])) {
                    return true;
                }
                break;
            case PDT_INT:
            case PDT_FLOAT:
                if (empty($value) && $value . "" != "0") {
                    return true;
                }
                break;
            case PDT_DATETIME:
                if (empty($value) || $value == "1970-01-01 01:00:00" || $value == "1970-01-01 00:00:00") {
                    return true;
                }
                break;
            case PDT_DATE:
                if (empty($value) || $value == "1970-01-01" || $value == "1970-01-01") {
                    return true;
                }
                break;
            case PDT_TIME:
                if (empty($value)) {
                    return true;
                }
                break;
            case PDT_BOOL:
                if ($value . "" == 'false' || $value . "" == "0" || empty($value)) {
                    return true;
                }
                break;
            default :
                if (empty($value) && $value != "0") {
                    return true;
                }
                break;
        }
        return false;
    }

}
