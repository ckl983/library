<?php
/**
 * 消息队列接口
 *
 * @author
 * @copyright 2014-2018
 */

namespace WPLib;


interface MessageQueueInterface
{
    public function push($keyName, $value);

    public function pop($keyName);

    public function hasEmpty($keyName);

    public function count($keyName);
}