<?php
/**
 * 文件适配器
 *
 * @author
 * @copyright 2018-2019 深圳市冠林轩实业有限公司 <http://www.jiabeiplus.com/>
 */

namespace WPLib\File;

class Adapter
{

    protected $_errorMessages = [];

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
}