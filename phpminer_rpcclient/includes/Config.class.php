<?php

/**
 * @copyright Christian Ackermann (c) 2013 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */

/**
 * Represents the config of php cgminer.
 */
class Config {

    /**
     * Holds the config values.
     * 
     * @var array 
     */
    protected $config = array();

    /**
     * The path to the config file.
     * 
     * @var string
     */
    private $config_path = "";
    
    /**
     * Creates a new config instance and load the config.
     * 
     * @param string $config_path
     *   The path to the config file.
     */
    public function __construct($config_path) {
        $this->config_path = $config_path;
        $this->reload();
    }

    /**
     * Reloads/Loads the config.
     * 
     * @throws PHPMinerException
     */
    public function reload() {
        // Check if config file exists, if not try to create it.
        if (!file_exists($this->config_path)) {
            // Config directory is not writeable.
            if (!is_writable(dirname($this->config_path))) {
                throw new Exception('Config file directoy is not writeable: ' . dirname($this->config_path));
            }
            touch($this->config_path);
        }

        // Config file not readable....
        if (!is_readable($this->config_path)) {
            throw new Exception('Config file not found: ' . $this->config_path);
        }

        // Config file not writeable...
        if (!is_writable($this->config_path)) {
            throw new Exception('Config not writeable: ' . $this->config_path);
        }

        // Get the data of the config file.
        $config_data = file_get_contents($this->config_path);

        // If the config file is empty, nothing is bad, just not configured yet.
        if (empty($config_data)) {
            $this->config = array();
            return;
        }

        // Try to decode the existing data from json.
        $this->config = json_decode($config_data, true);

        // Check for invalid json.
        if ($this->config === null) {
            throw new Exception('Config file can not be parsed, no valid json found.');
        }
    }

    /**
     * Returns wether the config file is empty or not.
     * 
     * @return boolean
     *   True if config is empty, else false.
     */
    public function is_empty() {
        return empty($this->config);
    }

    /**
     * Retrieves config the value.
     * 
     * @param string $name
     *   The config key.
     * 
     * @return null|mixed
     *   Null if config key does not exist, else the value.
     */
    public function __get($name) {
        return $this->get_value($name);
    }

    /**
     * Retrieves config the value.
     * 
     * @param string $name
     *   The config key.
     * 
     * @return null|mixed
     *   Null if config key does not exist, else the value.
     */
    public function get_value($name) {
        if (!isset($this->config[$name])) {
            return null;
        }
        return $this->config[$name];
    }
    
    /**
     * Returns the hole config.
     * 
     * @return array
     *   The config array.-
     */
    public function get_config() {
        return $this->config;
    }

    /**
     * Set the config key and write the new config file directly.
     * 
     * @param string $name
     *   The config key to set.
     * @param mixed $value
     *   The value.
     */
    public function __set($name, $value) {
        $this->set_value($name, $value);
    }

    /**
     * Set the config key and write the new config file directly.
     * 
     * @param string $name
     *   The config key to set.
     * @param mixed $value
     *   The value.
     */
    public function set_value($name, $value) {
        $this->config[$name] = $value;
        $this->save();
    }

    /**
     * Returns wether the config key exists or not.
     * 
     * @param string $name
     *   The config key.
     * 
     * @return boolean
     *   True if config key is set, else false.
     */
    public function __isset($name) {
        return isset($this->config[$name]);
    }

    /**
     * Returns wether the config file is writeable or not.
     * 
     * @return boolean
     *   True if path is writeable, else false.
     */
    public function is_writeable() {
        return is_writable($this->config_path);
    }

    /**
     * Write the config to the file.
     * 
     * @return boolean|int
     *   Returns false if the file could not be writte, else the bytes which were written.
     */
    protected function save() {
        return file_put_contents($this->config_path, str_replace('\\/', '/', $this->prettyPrint(json_encode($this->config))));
    }

    /**
     * Format a json string into pretty looking.
     * 
     * @param string $json
     *  The json which needs to be formated.
     * 
     * @return string
     *   The pretty printed json.
     */
    private function prettyPrint($json) {
        $result = '';
        $level = 0;
        $prev_char = '';
        $in_quotes = false;
        $ends_line_level = NULL;
        $json_length = strlen($json);

        for ($i = 0; $i < $json_length; $i++) {
            $char = $json[$i];
            $new_line_level = NULL;
            $post = "";
            if ($ends_line_level !== NULL) {
                $new_line_level = $ends_line_level;
                $ends_line_level = NULL;
            }
            if ($char === '"' && $prev_char != '\\') {
                $in_quotes = !$in_quotes;
            } else if (!$in_quotes) {
                switch ($char) {
                    case '}': case ']':
                        $level--;
                        $ends_line_level = NULL;
                        $new_line_level = $level;
                        break;

                    case '{': case '[':
                        $level++;
                    case ',':
                        $ends_line_level = $level;
                        break;

                    case ':':
                        $post = " ";
                        break;

                    case " ": case "\t": case "\n": case "\r":
                        $char = "";
                        $ends_line_level = $new_line_level;
                        $new_line_level = NULL;
                        break;
                }
            }
            if ($new_line_level !== NULL) {
                $result .= "\n" . str_repeat("\t", $new_line_level);
            }
            $result .= $char . $post;
            $prev_char = $char;
        }

        return $result;
    }

}