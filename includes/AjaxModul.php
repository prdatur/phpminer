<?php

/**
 * Provides an ajax return handler<br />
 * This needed for ajax action to return needed json encoded string
 *
 * @copyright Christian Ackermann (c) 2013 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Ajax
 */
abstract class AjaxModul
{
        /**
         * Define constances
         */
        const SUCCESS = 200;
        const SUCCESS_NO_CHANGES = 201;
        const SUCCESS_CANCEL = 205;
        const SUCCESS_RELOAD = 206;

        const SUCCESS_REDIRECT = 301;

        const ERROR_INVALID_PARAMETER = 405;
        const ERROR_MISSING_PARAMETER = 406;
        const ERROR_DATABASE_ERROR = 407;

        const ERROR_DEFAULT = 501;
        const ERROR_MODULE_NOT_FOUND = 502;
        const ERROR_SECURITY_LOCK = 550;
        const ERROR_AJAX_ERROR = 560;

        const ERROR_NOT_LOGGEDIN = 602;
        const ERROR_NO_RIGHTS = 601;
        
        const NEED_CONFIRM = 701;

        /**
         * Will return an array with <br />
         * array("code" => $code, "desc" => $desc, "data" => $data)
         * if $die is set to true it will print out the json encoded string for that array
         * and directly die
         *
         * @param int $code
         *   the return code, use one AjaxModul::*
         * @param mixed $data
         *   the additional data to be returned (optional, default = null)
         * @param boolean $die
         *   whether to output the data and die or to return the data (optional, default = true)
         * @param string $desc
         *   the error description (optional, default = '')
         *
         * @return array the returning array with format array("code" => $code, "desc" => $desc, "data" => $data)
         */
        static function return_code($code, $data = null, $die = true, $desc = "") {
                global $phpminer_error_handler_messages;
                $current_output = ob_get_clean();
                ob_start();
                if (!empty($current_output)) {
                    $code = AjaxModul::ERROR_AJAX_ERROR;
                    $data = sys_get_temp_dir() . '/phpminer_' . uniqid() . '.bugreport';
                    $die = true;
                    file_put_contents($data, $current_output);
                }
                switch ((int)$code) {
                        default: $return = array("code" => $code, "desc" => $desc, "data" => $data);
                }
                if ($die === null) {
                        $die = true;
                }
                if ($die == true) {
                        if (empty($phpminer_error_handler_messages)) {
                            echo json_encode($return);
                        }
                        die();
                }
                return $return;
        }

}