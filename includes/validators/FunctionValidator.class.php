<?php
require_once 'AbstractHtmlValidator.class.php';
/**
 * Provide an validator which validates the given value against a custom defined function
 *
 * Possible parameters:
 * 		value => the value which will be provied as first parameter to the function
 * 		options => the function name
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Form.Validators
 */
class FunctionValidator extends AbstractHtmlValidator
{

	/**
	 * Validates the value against the rules
	 *
	 * @return boolean if valid true, else false
	 */
	function is_valid() {
		if ($this->is_always_valid()) {
			return true;
		}
		$options = $this->get_options();
		if(!is_object($options)) {
			list ($obj, $method) = $options;
			$call = array($obj, $method."");
		}
		else {
			$call = $options;
		}
		return call_user_func_array($call, array($this->get_value(), $this));
	}

}

