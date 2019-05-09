<?php
/**
 * Redis消息队列接口
 *
 * @author
 * @copyright 2014-2018
 */

namespace WPLib\Caching;

use WPLib\MessageQueueInterface,
    Phalcon\Cache\Backend\Redis as RedisCache;

class Redis extends RedisCache
{
    /**
     * 重新连接
     */
    public function reconnect($time_out = 0)
    {
        $options = $this->_options;
        $redis = $this->_redis;

        $is_new = false;
        if (!$redis instanceof \Redis) {
            $is_new = true;
            $redis = new \Redis();
        } else {

        }

        $persistent = $options["persistent"];
        $host = $options['host'];
        $port = $options['port'];
        if ($persistent) {
            if ($time_out > 0) {
                $success = $redis->pconnect($host, $port, $time_out);
            }else{
                $success = $redis->pconnect($host, $port);
            }
            
        } else {
            if ($time_out > 0) {
                $success = $redis->connect($host, $port, $time_out);
            }else{
                $success = $redis->connect($host, $port);
            }         
        }

        if (!$success) {
            throw new \Exception("Could not connect to the Redisd server " . $host . ":" . $port);
        }

        if ($options["auth"]) {
            $success = $redis->auth($options["auth"]);

            if (!$success) {
                throw new \Exception("Failed to authenticate with the Redisd server");
            }
        }

        if ($options["index"]) {
            $success = $redis->select($options['index']);

            if (!$success) {
                throw new \Exception("Redisd server selected database failed");
            }
        }

        if ($is_new) {
            $this->_redis = $redis;
        }
    }

    /**
     * 自增
     *
     * @param $keyName
     * @param null $increment
     * @return mixed
     */
    public function incr($keyName, $increment = null)
    {
        $redis = $this->_redis;
        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }

        $prefix = $this->_prefix;
        $lastKey = $prefix . $keyName;
        $this->_lastKey = $lastKey;

        if ($increment === null || $increment == 1) {
            $number = $redis->incr($lastKey);
        } else {
            $number = $redis->incrby($lastKey, $increment);
        }

        return $number;
    }


    /**
     * 自增(有超时时间)
     *
     * @param $keyName
     * @param null $increment
     * @return mixed
     */
    public function incrWithTimeout($keyName, $increment = null, $time_out=0)
    {
        $redis = $this->_redis;
        if (!is_object($redis)) {
            $this->reconnect($time_out);
            $redis = $this->_redis;
        }

        $prefix = $this->_prefix;
        $lastKey = $prefix . $keyName;
        $this->_lastKey = $lastKey;

        if ($increment === null || $increment == 1) {
            $number = $redis->incr($lastKey);
        } else {
            $number = $redis->incrby($lastKey, $increment);
        }

        return $number;
    }


    /**
     * 自减
     *
     * @param $keyName
     * @param null $decrement
     * @return mixed
     */
    public function decr($keyName, $decrement = null)
    {
        $redis = $this->_redis;
        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }

        $prefix = $this->_prefix;
        $lastKey = $prefix . $keyName;
        $this->_lastKey = $lastKey;

        if ($decrement === null || $decrement == 1) {
            $number = $redis->decr($lastKey);
        } else {
            $number = $redis->incrby($lastKey, $decrement * -1);
        }

        return $number;
    }

    /**
     * 设置有效期
     *
     * @param $keyName
     * @param $seconds
     * @return bool
     */
    public function expire($keyName, $seconds)
    {
        $redis = $this->_redis;
        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }

        $prefix = $this->_prefix;
        $lastKey = $prefix . $keyName;
        $this->_lastKey = $lastKey;

        return !!$redis->expire($lastKey, $seconds);
    }

    /**
     *
     *
     * @param $keyPrefix
     * @return array
     */
    public function keys($keyPrefix)
    {
        $redis = $this->_redis;
        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }

        $prefix = $this->_prefix;
        $lastKey = $prefix . $keyPrefix . '*';

        $prefix_len = strlen($prefix);
        $keys = $redis->keys($lastKey);
        foreach ($keys as $k => &$v) {
            $v = substr($v, $prefix_len);
        }

        return $keys ?: [];
    }

    public function lpush($keyName, $value)
    {
        $redis = $this->_redis;
        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }

        $frontend = $this->_frontend;
        $prefix = $this->_prefix;
        $lastKey = $prefix . $keyName;
        $this->_lastKey = $lastKey;

        if (!is_numeric($value)) {
            $value = $frontend->beforeStore($value);
        }

        return !!$redis->lpush($lastKey, $value);
    }

    public function rpush($keyName, $value)
    {
        $redis = $this->_redis;
        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }

        $frontend = $this->_frontend;
        $prefix = $this->_prefix;
        $lastKey = $prefix . $keyName;
        $this->_lastKey = $lastKey;

        if (!is_numeric($value)) {
            $value = $frontend->beforeStore($value);
        }

        return !!$redis->rpush($lastKey, $value);
    }

    public function lpop($keyName)
    {
        $redis = $this->_redis;
        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }

        $frontend = $this->_frontend;
        $prefix = $this->_prefix;
        $lastKey = $prefix . $keyName;
        $this->_lastKey = $lastKey;

        $cachedContent = $redis->lpop($lastKey);

        if (is_numeric($cachedContent)) {
            return $cachedContent;
        }

        return $frontend->afterRetrieve($cachedContent);
    }

    public function rpop($keyName)
    {
        $redis = $this->_redis;
        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }

        $frontend = $this->_frontend;
        $prefix = $this->_prefix;
        $lastKey = $prefix . $keyName;
        $this->_lastKey = $lastKey;

        $cachedContent = $redis->rpop($lastKey);

        if (is_numeric($cachedContent)) {
            return $cachedContent;
        }

        return $frontend->afterRetrieve($cachedContent);
    }

    public function blpop($keyNames)
    {
        $redis = $this->_redis;
        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }

        if (!is_array($keyNames)) {
            $keyNames = [$keyNames];
        }

        $frontend = $this->_frontend;
        $prefix = $this->_prefix;
        $lastKey = [];
        foreach ($keyNames as $k => $v) {
            $lastKey[] = $prefix . $v;
        }
        $this->_lastKey = $lastKey;
        $lastKey[] = 1;

        $cachedContent = call_user_func_array([$redis, 'blpop'], $lastKey);

        if (is_numeric($cachedContent)) {
            return $cachedContent;
        }

        return $frontend->afterRetrieve($cachedContent);
    }

    public function brpop($keyNames)
    {
        $redis = $this->_redis;
        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }

        if (!is_array($keyNames)) {
            $keyNames = [$keyNames];
        }

        $frontend = $this->_frontend;
        $prefix = $this->_prefix;

        $lastKey = [];
        foreach ($keyNames as $k => $v) {
            $lastKey[] = $prefix . $v;
        }
        $this->_lastKey = $lastKey;
        $lastKey[] = 1;

        list(, $cachedContent) = call_user_func_array([$redis, 'brpop'], $lastKey);

        if (is_numeric($cachedContent)) {
            return $cachedContent;
        }

        return $frontend->afterRetrieve($cachedContent);
    }

    public function llen($keyName)
    {
        $redis = $this->_redis;
        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }

        $prefix = $this->_prefix;
        $lastKey = $prefix . $keyName;
        $this->_lastKey = $lastKey;

        return $redis->llen($lastKey);
    }

    /**
     * Hash操作
     *
     * @param $keyName
     * @param $fieldName
     * @return mixed
     */
    public function hget($keyName, $fieldName)
    {
        $redis = $this->_redis;
        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }

        $frontend = $this->_frontend;
        $prefix = $this->_prefix;
        $lastKey = $prefix . $keyName;
        $this->_lastKey = $lastKey;

        $cachedContent = $redis->hget($lastKey, $fieldName);

        if (is_numeric($cachedContent)) {
            return $cachedContent;
        }

        return $frontend->afterRetrieve($cachedContent);
    }

    public function hset($keyName, $fieldName, $fieldValue)
    {
        $redis = $this->_redis;
        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }

        $frontend = $this->_frontend;
        $prefix = $this->_prefix;
        $lastKey = $prefix . $keyName;
        $this->_lastKey = $lastKey;

        if (!is_numeric($fieldValue)) {
            $fieldValue = $frontend->beforeStore($fieldValue);
        }

        return !!$redis->hset($lastKey, $fieldName, $fieldValue);
    }

    public function hdel($keyName, $fieldName)
    {
        $redis = $this->_redis;
        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }

        $prefix = $this->_prefix;
        $lastKey = $prefix . $keyName;
        $this->_lastKey = $lastKey;

        return $redis->hdel($lastKey, $fieldName);
    }

    public function hkeys($keyName)
    {
        $redis = $this->_redis;
        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }

        $prefix = $this->_prefix;
        $lastKey = $prefix . $keyName;
        $this->_lastKey = $lastKey;

        return $redis->hkeys($lastKey);
    }

    public function hexists($keyName, $fieldName)
    {
        $redis = $this->_redis;
        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }

        $prefix = $this->_prefix;
        $lastKey = $prefix . $keyName;
        $this->_lastKey = $lastKey;

        return $redis->hexists($lastKey, $fieldName);
    }

    public function hlen($keyName)
    {
        $redis = $this->_redis;
        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }

        $prefix = $this->_prefix;
        $lastKey = $prefix . $keyName;
        $this->_lastKey = $lastKey;

        return $redis->hlen($lastKey);
    }

    /**
     * 增加存储在字段中存储由增量键哈希的数量
     *
     * @param $keyName
     * @param $fieldName
     * @param $number
     * @return int 字段的增值操作后的值
     */
    public function hincrby($keyName, $fieldName, $number)
    {
        $redis = $this->_redis;
        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }

        $prefix = $this->_prefix;
        $lastKey = $prefix . $keyName;
        $this->_lastKey = $lastKey;

        return $redis->hincrby($lastKey, $fieldName, $number);
    }

    public function sAdd($keyName, $member)
    {
        $redis = $this->_redis;
        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }

        $prefix = $this->_prefix;
        $lastKey = $prefix . $keyName;
        $this->_lastKey = $lastKey;

        return $redis->sAdd($lastKey, $member);
    }

    public function sRem($keyName, $member)
    {
        $redis = $this->_redis;
        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }

        $prefix = $this->_prefix;
        $lastKey = $prefix . $keyName;
        $this->_lastKey = $lastKey;

        return $redis->sRem($lastKey, $member);
    }

    public function sMembers($keyName)
    {
        $redis = $this->_redis;
        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }

        $prefix = $this->_prefix;
        $lastKey = $prefix . $keyName;
        $this->_lastKey = $lastKey;

        return $redis->sMembers($lastKey);
    }

    /**
     * 删除缓存（get/save的数据请使用delete删除）
     *
     * @param int|string keyName
     * @return boolean
     */
    public function del($keyName)
	{
        $redis = $this->_redis;
        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }

        $prefix = $this->_prefix;
        $lastKey = $prefix . $keyName;
        $this->_lastKey = $lastKey;

		/**
         * Delete the key from redis
         */
		return $redis->delete($lastKey);
	}

    /**
     * 查看Redis服务器信息
     *
     * @return mixed
     */
    public function info()
    {
        $redis = $this->_redis;
        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }

        return $redis->info();
    }

    public function __call($name, $arguments)
    {
        $redis = $this->_redis;
        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }

        $prefix = $this->_prefix;
        $lastKey = $prefix . $arguments[0];
        $this->_lastKey = $lastKey;

        $arguments[0] = $lastKey;

        return call_user_func_array(array($redis, $name), $arguments);
    }

    /**
     * 获取锁
     *
     * @param string $keyName
     * @param int $expire
     * @return bool
     */
    public function getLock($keyName, $expire = 86400)
    {
        $redis = $this->_redis;
        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }

        $prefix = $this->_prefix;
        $lastKey = $prefix . $keyName;
        $this->_lastKey = $lastKey;

        $timestamp = (int)(microtime() * 1000);
        if ($redis->setnx($lastKey, $timestamp)) {
            return true;
        }

        return false;
    }

    /**
     * 释放锁
     *
     * @param string $keyName
     * @return bool
     */
    public function releaseLock($keyName)
    {
        $redis = $this->_redis;
        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }

        $prefix = $this->_prefix;
        $lastKey = $prefix . $keyName;
        $this->_lastKey = $lastKey;

        if ($redis->delete($lastKey)) {
            return true;
        }

        return false;
    }
}