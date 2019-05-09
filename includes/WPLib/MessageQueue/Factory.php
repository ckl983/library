<?php

/**
 * 消息队列工厂类
 *
 * @author
 * @copyright 2014-2018
 */

namespace WPLib\MessageQueue;


class Factory
{
    const KEY_TRADE_NOTIFY  = ENVIRON . '::trade::notify';

    const KEY_REFUND_NOTIFY = ENVIRON . '::trade::notify';

    const KEY_ANALY_LOGGER  = ENVIRON . '::analy::logger';

    const KEY_ANALY_STAT    = ENVIRON . '::analy::stat';

    /**
     * 优先级
     */
    const PRIORITY_LOWEST   = 10; // 最低

    const PRIORITY_NORMAL   = 5; // 一般

    const PRIORITY_HIGHEST  = 1; // 最高

    protected static $_inst = [];

    protected $prefix = 'queue::';

    protected function __construct($config = null)
    {
        if (isset($config['prefix'])) {
            $this->prefix = $config['prefix'];
        }
    }

    public static function inst($params = [])
    {
        $adapter = "WPLib\\MessageQueue\\Adapter\\" . $params['adapter'];

        if (!isset(self::$_inst[$adapter])) {

            // if (!class_exists($adapter)) {
            //     $adapter = $params['adapter'];
            // }

            unset($params['adapter']);

            $mq = new ${adapter}($params);

            self::$_inst[$adapter] = $mq;
        }

        return self::$_inst[$adapter];
    }

}