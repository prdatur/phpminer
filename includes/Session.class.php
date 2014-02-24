<?php

/**
 * Provide a session class which holds all necessary information about the current user
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Security
 */
class Session
{
	/**
	 * Define constances
	 */
	const SESSION_KEY_USER_ID = 'user_id';
	const SESSION_KEY_USER_USERNAME = 'username';
	const SESSION_KEY_USER_PASSWORD = 'password';

	/**
	 * The php session id
	 *
	 * @var string
	 */
	private $session_id = null;

	/**
	 * hold the session keys for an user login
	 * @var array
	 */
	private $session_keys = array(
		"id" => "user_id",
		"user" => "username",
		"pass" => "password",
	);

	/**
	 * Construct
	 *
	 * Creating a session object will always creates a session if not already exist
	 *
	 * @param Core &$core
	 *  the core object to setup (This is needed because globals core maybe does not exists (optional, default = null)
	 */
 	public function __construct() {
            $this->start_session();
	}

	/**
	 * Set session variable keys
	 *
	 * @param string $key
	 *   the array key
	 * @param string $value
	 *   the value to be set
	 *
	 * @return Session Self returning.
	 */
	public function &set($key, $value) {
		$_SESSION[$key] = $value;
		return $this;
	}

	/**
	 * Get session variable keys
	 *
	 * @param string $key
	 *   the array key
	 * @param string $default_value
	 *   return this value if key not found (optional, default = null)
	 *
	 * @return mixed return the value from given key, if key not exists return $default_value
	 */
	public function get($key, $default_value = null) {
		return (isset($_SESSION[$key])) ? $_SESSION[$key] : $default_value;
	}

	/**
	 * Deletes the given key from session
	 *
	 * @param string $key
	 *   The session key
	 */
	public function delete($key) {
		if (isset($_SESSION[$key])) {
			unset($_SESSION[$key]);
		}
	}

	/**
	 * Start a php session, it will start it only if no session_id exists
	 */
	public function start_session() {
		//Get current session id
		$tmp_sessid = session_id();

		//If we have no session id we start the session
		if (empty($tmp_sessid)) {
			session_start();
			$this->session_id = session_id();
		}
		else if (empty($this->session_id)) {
			$this->session_id = $tmp_sessid;
		}
	}

	/**
	 * Returns the current session id
	 *
	 * @return string the current session id
	 */
	public function get_session_id() {
		return $this->session_id;
	}

}