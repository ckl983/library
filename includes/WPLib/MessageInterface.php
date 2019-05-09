<?php
/**
 * 消息接口
 *
 * @author
 * @copyright 2014-2018
 */

namespace WPLib;


interface MessageInterface
{

    /**
     * Phalcon\Validation\Message constructor
     *
     * @param string message
     * @param string field
     * @param string type
     */
    public function __construct($message, $field = null, $type = null);

	/**
     * Sets message type
     */
	public function setType($type);

	/**
     * Returns message type
     */
	public function getType();

	/**
     * Sets verbose message
     */
	public function setMessage($message);

	/**
     * Returns verbose message
     *
     * @return string
     */
	public function getMessage();

	/**
     * Sets field name related to message
     */
	public function setField($field);

	/**
     * Returns field name related to message
     *
     * @return string
     */
	public function getField();

	/**
     * Magic __toString method returns verbose message
     */
	public function __toString();

	/**
     * Magic __set_state helps to recover messsages from serialization
     */
	public static function __set_state($message);

}