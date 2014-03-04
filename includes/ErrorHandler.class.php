<?php
/**
 * Provides a class to handle errors
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Tools
 */
class ErrorHandler
{
	/**
	 * Replace the standard php error handler ( this is a callback functions)
	 *
	 * @param int $number
	 *   the error number
	 * @param string $message
	 *   The error message
	 * @param string $file
	 *   the file where there error appeared
	 * @param string $line
	 *   the line where the appeared
	 * @param string $variables
	 *   the variables
	 *
	 * @return string the error string.
	 */
	public static function cc_error_handler($errno = E_NOTICE, $errstr = "", $errfile = "", $errline = "", $variables = "", $from_shutdown = false, $stack = null) {
                global $phpminer_error_handler_messages, $phpminer_error_handler_suppressed_messages;
                $supress_check = md5($errno.$errstr.$errfile.$errline);
                if (error_reporting() == 0 || (($errno & error_reporting()) == 0 ) || isset($phpminer_error_handler_suppressed_messages[$supress_check])) {
                        $phpminer_error_handler_suppressed_messages[$supress_check] = true;
			return false;
		}
                
		// check if function has been called by an exception
		if (func_num_args() == 1) {
			// caught exception
			$exc = func_get_arg(0);
			$errno = $exc->getCode();
			$errstr = $exc->getMessage();
			$errfile = $exc->getFile();
			$errline = $exc->getLine();

			$backtrace = $exc->getTrace();
		}
		else {
			// called by trigger_error()
			$exception = null;
                        if ($stack !== null) {
                            $backtrace = $stack;
                        }
                        else {
                            $backtrace = debug_backtrace();
                            unset($backtrace[0]);
                        }
			$backtrace = array_reverse($backtrace);
		}

		$errorType = array(
			E_ERROR => 'ERROR',
			E_WARNING => 'WARNING',
			E_PARSE => 'PARSING ERROR',
			E_NOTICE => 'NOTICE',
			E_CORE_ERROR => 'CORE ERROR',
			E_CORE_WARNING => 'CORE WARNING',
			E_COMPILE_ERROR => 'COMPILE ERROR',
			E_COMPILE_WARNING => 'COMPILE WARNING',
			E_USER_ERROR => 'USER ERROR',
			E_USER_WARNING => 'USER WARNING',
			E_USER_NOTICE => 'USER NOTICE',
			E_STRICT => 'STRICT NOTICE',
			E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR'
		);
                
		$ignoreErrorType = array(
			
			E_CORE_ERROR => 'CORE ERROR',
			E_CORE_WARNING => 'CORE WARNING',
			E_COMPILE_ERROR => 'COMPILE ERROR',
			E_COMPILE_WARNING => 'COMPILE WARNING',
			E_STRICT => 'STRICT NOTICE',
			E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR'
		);

		// create error message
		if (isset($errorType[$errno])) {
                        if (isset($ignoreErrorType[$errno])) {
                           return false;
                        }
			$err = $errorType[$errno];
		}
		else {
                    if ($exception !== null && $exception instanceof Exception) {
			$err = 'UNCAUGHT EXCEPTION ' . get_class($exception) . '(' . $errno . ')';
                    }
                    else {
                        $err = 'UNCAUGHT EXCEPTION (' . $errno . ')';
                    }
		}

		$errMsgPlain = "";
		$lastfile = '';
		$errMsgPlain.= "Error: " . $err . ": " . $errstr . " in " . $errfile . " on line " . $errline . "<br>\n";

		$errMsgPlain .= "Backtrace:<br>\n";
		foreach ($backtrace as $row) {
			if (empty($row['file']))
				$row['file'] = '(unknown)';
			if (empty($row['line']))
				$row['line'] = '(unknown)';
			if ($lastfile != $row['file']) {
				$errMsgPlain .= "File: " . $row['file'] . "<br>\n";
			}


			$lastfile = $row['file'];
			$errMsgPlain .= "  Line: " . $row['line'] . ": ";
			if (!empty($row['class'])) {
				
				$errMsgPlain .= $row['class'] . $row['type'] . $row['function'];
			}
			elseif (isset($row['function'])) {
				$errMsgPlain .= $row['function'];
			}
                        else {
                            $errMsgPlain .= 'Unknown function or method';
                        }
                        
			if (empty($row['args'])) {
				$errMsgPlain .= ' (no args)';
			}
			else {
				$errMsgPlain .= ' (Args: ';
				$separator = '';
				foreach ($row['args'] as $arg) {

					$value = self::getArgument($arg);
					

					$errMsgPlain .= $separator . $value;
					$separator = ', ';
				}
				$errMsgPlain .= ')';
			}
			$errMsgPlain .= "<br>\n";
		}

                
                if ($from_shutdown) {
                    ob_get_clean();
                    ob_start();
                    $phpminer_error_handler_messages[] = $errMsgPlain;
                    return $phpminer_error_handler_messages;
                }
                
		if (ini_get("display_errors")) {
                    global $phpminer_request_is_ajax;
                    if (!$phpminer_request_is_ajax) {
                        $phpminer_error_handler_messages[] = '<div style="background-color: white;">' .  $errMsgPlain . '</div>';
                    }
                    else {
                        $phpminer_error_handler_messages[] = $errMsgPlain;
                    }
		}

		if (ini_get('log_errors')) {
			error_log($errMsgPlain);
		}
		return $phpminer_error_handler_messages;
	}

	/**
	 * Parses a value to a string which is more preciser than var_export.
	 *
	 * @param mixed $arg
	 *   the value
	 *
	 * @return string the formated argument
	 */
	public static function getArgument($arg) {
		switch (strtolower(gettype($arg))) {
			case 'string':
				return( '"' . str_replace(array("<br>\n"), array(''), $arg) . '"' );

			case 'boolean':
				return (bool) $arg;

			case 'object':
				return 'object(' . get_class($arg) . ')';

			case 'array':
				$ret = 'array(';
				$separtor = '';

				foreach ($arg as $k => $v) {
					#$ret .= $separtor . self::getArgument($k) . ' => ' . self::getArgument($v);
					$separtor = ', ';
				}
				$ret .= ')';

				return $ret;

			case 'resource':
				return 'resource(' . get_resource_type($arg) . ')';

			default:
				return var_export($arg, true);
		}
	}

}