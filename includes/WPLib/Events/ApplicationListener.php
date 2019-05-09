<?php
/**
 * ApplicationListener
 *
 * @author
 * @copyright 2014-2018
 */

namespace WPLib\Events;

use Phalcon\Logger,
    Phalcon\Di;

class ApplicationListener
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
    public function beforeSendResponse($event, $application)
    {
        $di = Di::getDefault();
        $profiler = $di->get('profiler');
        $logger   = $di->get('logger');
        $logger->begin();
        $profilers = $profiler->getProfiles();
        if ($profilers && is_array($profilers)) {

            foreach ($profilers as $k => $v) {
                $logger->log(sprintf("SQL：%s, 参数：%s, 开始时间：%s, 结束时间：%s, 执行时间：%s",
                    $v->getSQLStatement(),
                    json_encode($v->getSqlVariables()),
                    $v->getInitialTime(),
                    $v->getFinalTime(),
                    $v->getTotalElapsedSeconds()
                ), Logger::DEBUG);
            }
            $profiler->reset();
        }

        // 代码返回日志记录
        {
            $content = $di->getShared('response')->getContent();
            if ($content === null) {
                $content = ob_get_contents();
            }
            $logger->log(sprintf("RESPONSE:%s", $content), Logger::INFO);
        }
        $logger->commit();

        return true;
    }

    public function afterHandleTask($event, $application)
    {
        $di = Di::getDefault();
        $profiler = $di->get('profiler');
        $logger   = $di->get('logger');
        $logger->begin();
        $profilers = $profiler->getProfiles();
        if ($profilers && is_array($profilers)) {

            foreach ($profilers as $k => $v) {
                $logger->log(sprintf("SQL：%s, 参数：%s, 开始时间：%s, 结束时间：%s, 执行时间：%s",
                    $v->getSQLStatement(),
                    json_encode($v->getSqlVariables()),
                    $v->getInitialTime(),
                    $v->getFinalTime(),
                    $v->getTotalElapsedSeconds()
                ), Logger::INFO);
            }
            $profiler->reset();
        }
        $logger->commit();
    }

    /**
     * 格式化参数
     *
     * @param array $variables
     * @return string
     */
    private function formatSqlVariables($variables = [])
    {
        if (!$variables || !is_array($variables)) {
            return '';
        }

        $formater = '';
        foreach ($variables as $k => $v) {
            $formater .= ($formater ? ", " : '') . "{$k}: $v";
        }

        return $formater;
    }
}