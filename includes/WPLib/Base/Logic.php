<?php
/**
 * WPLib\Base\Logic Logic基类
 *
 * @author
 * @copyright 2014-2018
 */

namespace WPLib\Base;

/**
 *
 */

use Phalcon\Di;

class Logic
{
    protected $_di = null;

    public $db = null;

    protected static $_inst = null;

    protected $_errorMessages = [];

    public function __construct()
    {
        $this->_di = Di::getDefault();
    }

    public function getDI()
    {
        return $this->_di;
    }

    public function setDI(\Phalcon\DiInterface $dependencyInjector)
    {
        $this->_di = $dependencyInjector;
    }

    /**
     * Returns all the validation messages
     *
     * <code>
     * $robot = new Robots();
     * $robot->type = 'mechanical';
     * $robot->name = 'Astro Boy';
     * $robot->year = 1952;
     * if ($robot->save() == false) {
     *	echo "Umh, We can't store robots right now ";
     *	foreach ($robot->getMessages() as message) {
     *		echo message;
     *	}
     *} else {
     *	echo "Great, a new robot was saved successfully!";
     *}
     * </code>
     */
    public function getMessages()
    {
        return $this->_errorMessages;
    }

    /**
     * 返回最后一个错误信息
     *
     * @return mixed
     */
    public function getLastMessage()
    {
        return end($this->_errorMessages);
    }

    /**
     * Appends a customized message on the validation process
     *
     *<code>
     *	use \Phalcon\Mvc\Model\Message as Message;
     *
     *	class Robots extends \Phalcon\Mvc\Model
     *	{
     *
     *		public function beforeSave()
     *		{
     *			if ($this->name == 'Peter') {
     *				message = new Message("Sorry, but a robot cannot be named Peter");
     *				$this->appendMessage(message);
     *			}
     *		}
     *	}
     *</code>
     */
    public function appendMessage($message, $returnValue = false)
    {
        $this->_errorMessages[] = $message;

        return $returnValue;
    }

    static public function inst()
    {
        $className = get_called_class();
        if (!isset(self::$_inst[$className])) {
            self::$_inst[$className] = new $className();
        }

        return self::$_inst[$className];
    }
}