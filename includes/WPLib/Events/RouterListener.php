<?php
/**
 * RouterListener
 *
 * @author
 * @copyright 2014-2018
 */

namespace WPLib\Events;

use Phalcon\Logger,
    Phalcon\Di;

class RouterListener
{
    protected $_di;

    protected $_profiler;

    /**
     *创建分析器并开始纪录
     */
    public function __construct()
    {
        $this->_di       = Di::getDefault();
        $this->_profiler = $this->_di->get('profiler');
        $this->_logger   = $this->_di->get('logger');
    }

    /**
     * 发送响应内容之前
     *
     * @param $event
     * @param $application
     */
    public function beforeCheckRoutes($event, $router)
    {

    }

    public function matchedRoute($event, $router, $route)
    {

    }

    public function beforeCheckRoute($event, $router, $route)
    {

    }

}