<?php
/**
 * DbListener
 *
 * @author
 * @copyright 2014-2018
 */

namespace WPLib\Events;

use Phalcon\Db\Profiler,
    Phalcon\Logger,
    Phalcon\Di;

class DbListener
{
    protected $_di;

    protected $_profiler;

    protected $_logger;

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
     * 如果事件触发器是'beforeQuery'，此函数将会被执行
     */
    public function beforeQuery($event, $connection, $bindParams)
    {
        if (!defined('IS_DISABLE_PROFILER') || !IS_DISABLE_PROFILER) {
            $this->_profiler->startProfile($connection->getSQLStatement(), $connection->getSqlVariables());
        }
    }

    /**
     * 如果事件触发器是'afterQuery'，此函数将会被执行
     */
    public function afterQuery($event, $connection, $bindParams)
    {
        if (!defined('IS_DISABLE_PROFILER') || !IS_DISABLE_PROFILER) {
            $this->_profiler->stopProfile();
        }
    }

    public function getProfiler()
    {
        return $this->_profiler;
    }
}