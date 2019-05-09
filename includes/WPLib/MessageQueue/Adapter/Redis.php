<?php
/**
 * Redis消息队列适配器
 *
 * @author
 * @copyright 2014-2018
 */

namespace WPLib\MessageQueue\Adapter;

use Phalcon\Di;
use WPLib\MessageQueueInterface,
    WPLib\MessageQueue\Factory,
    Phalcon\Cache\Backend\Redis as RedisCache;

class Redis extends Factory implements MessageQueueInterface
{

    protected function __construct($config = null)
    {
        parent::__construct($config);

        if (isset($config['service'])) {
            $this->redis = Di::getDefault()->get($config['service']);
        } else {
            /**
             * @todo redis实例化
             */
            $this->redis = Di::getDefault()->get('redis');
        }
    }

    /**
     * 入队列
     *
     * @param $keyName
     * @param $value
     * @return bool
     */
    public function push($keyName, $value, $priority = self::PRIORITY_NORMAL)
    {

        $lastKey = $this->prefix . $keyName . "::p{$priority}";

        return !!$this->redis->rpush($lastKey, $value);
    }

    /**
     * 出队列
     *
     * @param $keyName
     * @return bool
     */
    public function pop($keyName)
    {
        $lastKey = $this->prefix . $keyName;

        // $keys = [];
        // foreach ([1, 5, 10] as $k => $v) {
        //     $keys[] = $lastKey . "::p{$v}";
        // }
        $lastKey = $lastKey . "::p5";

        return $this->redis->lpop($lastKey);
    }

    /**
     * 出队列
     *
     * @param $keyName
     * @return bool
     */
    public function multiPop($keyName, $size = 10)
    {
        $lastKey = $this->prefix . $keyName;
        $lastKey = $lastKey . "::p5";

        $data = [];
        for ($i = 0; $i < $size; $i ++) {
            $data[] = $this->redis->lpop($lastKey);
        }

        return $data;
    }

    /**
     * 清空队列
     *
     * @param $keyName
     * @return mixed
     */
    public function clear($keyName)
    {
        $lastKey = $this->prefix . $keyName;
        $lastKey = $lastKey . "::p5";

        return $this->redis->delete($lastKey);
    }

    /**
     * 队列是否为空
     *
     * @param $keyName
     * @return bool
     */
    public function hasEmpty($keyName)
    {
        return $this->count($keyName) === 0;
    }

    /**
     * 队列长度
     *
     * @param $keyName
     * @return mixed
     */
    public function count($keyName)
    {
        $lastKey = $this->prefix . $keyName;

        $count = 0;
        foreach ([5] as $k => $v) {
            $count += $this->redis->llen($lastKey . "::p{$v}");
        }

        return $count;
    }
}