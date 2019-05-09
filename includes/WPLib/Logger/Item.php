<?php
/**
 *
 * @author
 * @copyright 2014-2018
 */

namespace WPLib\Logger;


/**
 * WPLib\Logger\Item
 *
 * Represents each item in a logging transaction
 *
 */
class Item extends \Phalcon\Logger\Item
{
	/**
	 * Phalcon\Logger\Item constructor
	 *
	 * @param string $message
	 * @param integer $type
	 * @param mixed $time
	 * @param array $context
	 */
	public function __construct($message, $type, $time = 0, $context = null)
	{
		$this->_message = $message;
		$this->_type = $type;
		$this->_time = $time;

		if (is_array($context) == "array") {
			$this->_context = $context;
		}
	}
}
