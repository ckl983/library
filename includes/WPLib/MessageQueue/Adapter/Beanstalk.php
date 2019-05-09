<?php

/**
 * 消息队列适配器 - bleanstalk
 *
 * @author
 * @copyright 2014-2018
 */

namespace WPLib\MessageQueue\Adapter;

use Phalcon\Di,
    WPLib\MessageQueueInterface,
    WPLib\MessageQueue\Factory;

class Beanstalk extends Factory implements MessageQueueInterface
{

    protected function __construct($config = null)
    {
        parent::__construct($config);

        if (isset($config['service'])) {
            $this->beanstalk = Di::getDefault()->get($config['service']);
        } else {
            /**
             * @todo redis实例化
             */
            $this->beanstalk = Di::getDefault()->get('beanstalk');
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
        $lastKey = str_replace('::', '_', $this->prefix . $keyName . "_p{$priority}");

        $tube = $this->beanstalk->useTube($lastKey);

        return !!$tube->put(json_encode($value));
    }

    /**
     * 出队列
     *
     * @param $keyName
     * @return bool
     */
    public function pop($keyName)
    {
        $lastKey = str_replace('::', '_', $this->prefix . $keyName);

        // $keys = [];
        // foreach ([1, 5, 10] as $k => $v) {
        //     $keys[] = $lastKey . "_p{$v}";
        // }
        $lastKey = $lastKey . "_p5";

        $this->beanstalk->watch($lastKey)->ignore('default');

        if ($job = $this->beanstalk->reserve(0)) {
            $data = json_decode($job->getData(), true);
            $this->beanstalk->delete($job);
        } else {
            $data = null;
        }
        return $data;
    }

    /**
     * 队列是否为空
     *
     * @param $keyName
     * @return bool
     */
    public function hasEmpty($keyName)
    {
        $lastKey = str_replace('::', '_', $this->prefix . $keyName);
        $lastKey = $lastKey . "_p5";

        $this->beanstalk->watch($lastKey)->ignore('default');

        if ($job = $this->beanstalk->reserve(0)) {
            $this->beanstalk->release($job);
            return false;
        }

        return true;
    }

    /**
     * 队列长度
     *
     * @param $keyName
     * @return mixed
     */
    public function count($keyName)
    {
        $lastKey = str_replace('::', '_', $this->prefix . $keyName);

        $count = 0;
        foreach ([5] as $k => $v) {
            $count += $this->beanstalk->llen($lastKey . "_p{$v}");
        }

        return $count;
    }
}