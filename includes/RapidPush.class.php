<?php

/**
 * Provide a class to handle RapidPush notifications.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */
class RapidPush {
	
	/**
	 * Defines the RapidPush API-Service URL.
	 */
	const API_SERVICE_URL = 'https://rapidpush.net/api';
	
	/**
	 * Holds the API-Key.
	 * 
	 * @var string
	 */
	private $api_key = '';
	
	/**
	 * Constructs the RapidPush object with the given api key.
	 * 
	 * @param string $api_key
	 *   The api key.
	 */
	public function __construct($api_key) {
		$this->api_key = $api_key;
	}
	
	/**
	 * Sends a broadcast notification to the channel.
	 * 
	 * @param string $title
	 *   The title.
	 * @param string $message
	 *   The message.
	 * @param string $channel
	 *   The channel
	 * 
	 * @return array The response array.
	 */
	public function broadcast($title, $message, $channel) {
		return $this->execute('broadcast', array(
			'title' => $title,
			'message' => $message,
			'channel' => $channel,
		));
	}
	
	/**
	 * Sends a notification.
	 * 
	 * @param string $title
	 *   The title.
	 * @param string $message
	 *   The message.
	 * @param int $priority
	 *   The priority. (optional, default = 2)
	 * @param string $category
	 *   The category. (optional, default = 'default')
	 * @param string $group
	 *   The device group. (optional, default = '')
	 * 
	 * @return array The response array.
	 */
	public function notify($title, $message, $priority = 2, $category = "default", $group = "") {
		return $this->execute('notify', array(
			'title' => $title,
			'message' => $message,
			'priority' => $priority,
			'category' => $category,
			'group' => $group,
		));
	}
	
	/**
	 * Schedule a notification.
	 * 
	 * @param int when
	 *  The local timestamp.
	 * @param string $title
	 *   The title.
	 * @param string $message
	 *   The message.
	 * @param int $priority
	 *   The priority. (optional, default = 2)
	 * @param string $category
	 *   The category. (optional, default = 'default')
	 * @param string $group
	 *   The device group. (optional, default = '')
	 * 
	 * @return array The response array.
	 */
	public function schedule($when, $title, $message, $priority = 2, $category = "default", $group = "") {
		return $this->execute('notify', array(
			'title' => $title,
			'message' => $message,
			'priority' => $priority,
			'category' => $category,
			'group' => $group,
			'schedule_at' => gmdate("Y-m-d H:i:00", $when),
		));
	}
	
	/**
	 * Get the configurated device groups.
	 * 
	 * @return array The response array, where the groups will be within the data key.
	 */
	public function get_groups() {
		return $this->execute('get_groups', array());
	}
	
	/**
	 * Makes an API call.
	 * 
	 * @param string $command
	 *   The API-Command
	 * @param array $data
	 *   The data to be send.
	 * @param boolean $post
	 *   Whether we want to do a POST (set to true )or GET (set to false). (optional, default = true)
	 * 
	 * @return array The response array.
	 */
	private function execute($command, $data, $post = true) {
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, self::API_SERVICE_URL);
		
		if ($post === true) {
			curl_setopt($ch, CURLOPT_POST, true);		
			curl_setopt($ch, CURLOPT_POSTFIELDS, array(
				'apikey' => $this->api_key,
				'command' => $command,
				'data' => json_encode($data),
			));
		}
		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_USERAGENT, "RapidPush PHP-Library");
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 15);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		$result = curl_exec($ch);

		curl_close($ch);
		return json_decode($result, true);
	}
}