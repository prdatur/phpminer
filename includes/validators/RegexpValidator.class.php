<?php
require_once 'AbstractHtmlValidator.class.php';
/**
 * Provide a validator which checks values against the regexp.
 *
 * Possible parameters:
 * 		value => the value which will be checked
 * 		options => single string which will be used as the pure reqexp, you need to escape it self.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Form.Validators
 */
class RegexpValidator extends AbstractHtmlValidator
{

	/**
	 * Validates the value against the regular expression
	 *
	 * @return boolean if valid true, else false
	 */
	function is_valid() {
		if ($this->is_always_valid()) {
			return true;
		}
		return (preg_match($this->get_options(), $this->get_value()) > 0);
	}

}

