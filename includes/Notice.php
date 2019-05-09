<?php
use Phalcon\Di;
/**
 * Class Notice 异常通知
 * @author isGu
 * @date 2019-04-23 16:06
 */
class Notice
{
    //通知类型
    static public $_type = [
        110, 120, 130, 140, 150
    ];

    //通知方式
    static public $_model = [
        10, 20, 30, 40, 50
    ];

    static public function Set($type, $model, $message, $other = [])
    {
        try {
            $app_id = Di::getDefault()->get('config')->application['app_id'];
            $url = '/NT/notice/create/';
            $params = [
                'app_id'    => $app_id,
                'type'      => $type,
                'model'     => $model,
                'message'   => $message,
                'other'     => $other
            ];
            $res = WPLib\WPApi::call($url, $params);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}