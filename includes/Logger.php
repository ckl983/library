<?php

/**
 * 日志
 *
 * @author
 * @copyright 2014-2018
 */

class Logger
{
    public static $_inst = null;

    private function __construct()
    {
        $this->di = \Phalcon\Di::getDefault();

        $this->logger = $this->di->getLogger();
    }

    public static function __callStatic($name, $arguments)
    {
        if (self::$_inst == null) {
            self::$_inst = new self;
        }

        if (in_array($name, ['debug', 'error'])) {
            $trace_list = debug_backtrace(null, 0);
            $arguments[0] = sprintf("[%s][%s] %s", basename($trace_list[0]['file']), $trace_list[0]['line'], $arguments[0]);
        }

        return call_user_func_array(array(self::$_inst->logger, $name), $arguments);
    }
}