<?php

/**
 * 接口调用
 *
 * @author
 * @copyright 2014-2018
 */

namespace WPLib;

use Phalcon\Di;
use Redis,
    Logger,
    Exception,
    RedisException,
    swoole_client;

class WPApi
{

    const VERSION = 'v2.0.0';

    const DEFAULT_SECRET = 'ZkaYLrDsXEDeO7qHv7imJCWAprCy0u7K';

    public static $_app_id  = null;

    public static $_secret  = null;

    public static $_task_client  = null;

    public static $_modules = null;

    public static $_config  = null;

    protected static $appList = [
        /**
         * 子模块
         */
        10010 => [
            'app_id'    => 10010,
            'app_name'  => '商户服务',
        ],
        10020 => [
            'app_id'    => 10020,
            'app_name'  => '用户服务',
        ],
        10030 => [
            'app_id'    => 10030,
            'app_name'  => '能源服务',
        ],
        10040 => [
            'app_id'    => 10040,
            'app_name'  => '订单服务',
        ],
        10050 => [
            'app_id'    => 10050,
            'app_name'  => '支付服务',
        ],
        10060 => [
            'app_id'    => 10060,
            'app_name'  => '营销服务',
        ],
        10070 => [
            'app_id'    => 10070,
            'app_name'  => '消息服务',
        ],
        10080 => [
            'app_id'    => 10080,
            'app_name'  => '结算服务',
        ],
        10090 => [
            'app_id'    => 10090,
            'app_name'  => '权限服务',
        ],

        /**
         * 业务模块
         */
        // IP
        20010 => [
            'app_id'    => 20010,
            'app_name'  => '微信公众号-小程序-api',
        ],
        20020 => [
            'app_id'    => 20020,
            'app_name'  => '商户营销云平台',
        ],
        20030 => [
            'app_id'    => 20030,
            'app_name'  => '业务管理后台（内部）',
        ]
    ];

    public static function setAppInfo($app_id, $secret)
    {
        self::$_app_id = $app_id;
        self::$_secret = $secret;
    }

    public static function getAppId()
    {
        return self::$_app_id;
    }

    public static function getAppName()
    {
        if (self::$_app_id !== null && isset(self::$appList[self::$_app_id])) {
            $appInfo = self::$appList[self::$_app_id];
            return $appInfo['app_name'];
        }
        return '未知';
    }

    /**
     *
     */
    public static function init()
    {
        if (self::$_modules === null) {
            self::$_modules = WPApi\ApiConfig::getModules();
        }
    }

    public static function getModules()
    {
        self::init();

        return self::$_modules;
    }

    /**
     * @param $url
     * @param array $params
     * @param string $type
     */
    public static function call($url, $params = [], $type = 'POST', $options = [], $timeout = 5, $connection_timeout = 5, $retry_time = 1)
    {
        self::init();

        $version = isset($options['version']) ? $options['version'] : self::VERSION;

        $name = substr($url, 1, 2);
        $url = substr($url, 3);
        if (isset(self::$_modules[$name])) {
            // 超时时间监控
            if (($timeout > 5) || ($connection_timeout > 5)) {
                if (isset($options['is_ignore_monitor']) && $options['is_ignore_monitor'] == 1) {
                    Logger::debug("接口[{$url}]超时时间大于5秒，并且设置了超时忽略");
                } else {
                    $redis = Di::getDefault()->get('redis');
                    if ($redis->getLock(md5($url), 900)) {
                        self::call('/DX/sms/send/', [
                            'type' => 1,
                            'mobile' => '13715263673',
                            'content' => "接口{$url}超时时间大于5秒，请核实。",
                        ], 'POST', [], 2, 1);
                    }
                }
            }

            $module = self::$_modules[$name];
            if (isset($options['is_package']) && $options['is_package'] == 1) {
                $params = [
                    'data' => json_encode($params, JSON_UNESCAPED_UNICODE),
                ];
            }

            if (!isset($params['timestamp'])) {
                $params['timestamp'] = time();
            }

            $params['app_id']  = self::$_app_id;
            $params['version'] = self::VERSION;
            $params['token'] = self::signature($params, null, null, $version);
            $rand_key = \Helper::getRandByWeight($module['servers'], 'weight');
            $base_url = $module['servers'][$rand_key]['url'];
            $url = $base_url . $url;
            if ($type == 'POST') {
                if (isset($options['is_send_json']) && $options['is_send_json'] == 1) {
                    $options['headers'][] = 'Content-Type: application/json';
                    $send_params = json_encode($params);
                } elseif (isset($options['is_send_xml']) && $options['is_send_xml'] == 1) {
                    $options['headers'][] = 'Content-Type: application/xml';
                    $send_params = $params;
                } else {
                    $send_params = is_array($params) && $options['is_ignore_monitor'] != 1 ? http_build_query($params) : $params;
                }
            } else {
                if ($params) {
                    $send_params = is_array($params) ? http_build_query($params) : $params;
                    if (strpos($url, '?') === false) {
                        $url .= '?' . $send_params;
                    } else {
                        $url .= '&' . $send_params;
                    }
                }
            }

            for ($i = 0; $i < $retry_time; $i ++) {
                $begin_time = microtime(true);
                if ($type == 'POST') {
                    $resp = Http::quickPost($url, $send_params, $timeout, $connection_timeout, $options['headers'] ?: [], $options);
                } else {
                    $resp = Http::quickGet($url, $timeout, $connection_timeout, '', $options);
                }

                if (isset($options['is_recv_gzip']) && $options['is_recv_gzip']) {
                    $resp = gzdecode($resp);
                }

                $is_transaction = !Logger::isTransaction();
                $is_transaction && Logger::begin();
                Logger::debug(sprintf('接口地址: %s', $url));
                Logger::debug(sprintf('发送数据: %s', http_build_query($params)));
                Logger::debug(sprintf("接口返回: %s", $resp));
                Logger::debug(sprintf("USE TIME: %s", microtime(true) - $begin_time));
                $is_transaction && Logger::commit();

                // 请求错误
                if ($resp === false) {
                    $message = \Helper::getLastMessage();
                    Logger::error(sprintf("请求错误: %s[%s]", $message->getMessage(), $message->getCode()));
                    switch ($message->getCode()) {
                        case CURLE_COULDNT_CONNECT:
                            \Helper::appendMessage(new Message('请求超时', 10010));
                            break;
                        case CURLE_OPERATION_TIMEOUTED:
                            \Helper::appendMessage(new Message('操作超时', 10010));
                            break;

                        default:
                            return \Helper::appendMessage(new Message($message->getMessage(), 10086));
                            break;
                    }
                } else {
                    if ($resp !== '') {
                        return json_decode($resp, true);
                    } else {
                        /**
                         * @TODO 错误处理
                         */
                        return false;
                    }
                }
            }
        } else {
            Logger::error(sprintf("WPApi::call - 模块[%s]不存在", $name));
            return \Helper::appendMessage(new Message('模块不存在！', 10086));
        }

        return false;
    }

    /**
     *
     * SS专用（参数不兼容）
     *
     * @param $url
     * @param array $params
     * @param string $type
     */
    public static function http_request($url, $params = [], $type = 'POST', $options = [], $timeout = 5, $connection_timeout = 5, $retry_time = 1)
    {
        self::init();

        $version = isset($options['version']) ? $options['version'] : self::VERSION;

        $name = substr($url, 1, 2);
        $url = substr($url, 3);
        if (isset(self::$_modules[$name])) {
            // 超时时间监控
            if (($timeout > 5) || ($connection_timeout > 5)) {
                if (isset($options['is_ignore_monitor']) && $options['is_ignore_monitor'] == 1) {
                    Logger::debug("接口[{$url}]超时时间大于5秒，并且设置了超时忽略");
                } else {
                    $redis = Di::getDefault()->get('redis');
                    if ($redis->getLock(md5($url), 900)) {
                        self::call('/DX/sms/send/', [
                            'type' => 1,
                            'mobile' => '13715263673',
                            'content' => "接口{$url}超时时间大于5秒，请核实。",
                        ], 'POST', [], 2, 1);
                    }
                }
            }

            $module = self::$_modules[$name];

            if (!isset($params['timestamp'])) {
                $params['timestamp'] = time();
            }

            $params['version'] = self::VERSION;
            if (isset($options['is_package']) && $options['is_package'] == 1) {
                $params = [
                    'data' => json_encode($params, JSON_UNESCAPED_UNICODE),
                ];
            }

            $params['app_id']  = self::$_app_id;
            $params['token'] = self::signature($params, null, null, $version);
            $rand_key = \Helper::getRandByWeight($module['servers'], 'weight');
            $base_url = $module['servers'][$rand_key]['url'];
            $url = $base_url . $url;
            if ($type == 'POST') {
                if (isset($options['is_send_json']) && $options['is_send_json'] == 1) {
                    $options['headers'][] = 'Content-Type: application/json';
                    $send_params = json_encode($params);
                } elseif (isset($options['is_send_xml']) && $options['is_send_xml'] == 1) {
                    $options['headers'][] = 'Content-Type: application/xml';
                    $send_params = $params;
                } else {
                    $send_params = is_array($params) && $options['is_ignore_monitor'] != 1 ? http_build_query($params) : $params;
                }
            } else {
                if ($params) {
                    $send_params = is_array($params) ? http_build_query($params) : $params;
                    if (strpos($url, '?') === false) {
                        $url .= '?' . $send_params;
                    } else {
                        $url .= '&' . $send_params;
                    }
                }
            }

            for ($i = 0; $i < $retry_time; $i ++) {
                $begin_time = microtime(true);
                if ($type == 'POST') {
                    $resp = Http::quickPost($url, $send_params, $timeout, $connection_timeout, $options['headers'] ?: [], $options);
                } else {
                    $resp = Http::quickGet($url, $timeout, $connection_timeout, '', $options);
                }

                if (isset($options['is_recv_gzip']) && $options['is_recv_gzip']) {
                    $resp = gzdecode($resp);
                }

                $is_transaction = !Logger::isTransaction();
                $is_transaction && Logger::begin();
                Logger::debug(sprintf('接口地址: %s', $url));
                Logger::debug(sprintf('发送数据: %s', http_build_query($params)));
                Logger::debug(sprintf("接口返回: %s", $resp));
                Logger::debug(sprintf("USE TIME: %s", microtime(true) - $begin_time));
                $is_transaction && Logger::commit();

                // 请求错误
                if ($resp === false) {
                    $message = \Helper::getLastMessage();
                    Logger::error(sprintf("请求错误: %s[%s]", $message->getMessage(), $message->getCode()));
                    switch ($message->getCode()) {
                        case CURLE_COULDNT_CONNECT:
                            \Helper::appendMessage(new Message('请求超时', 10010));
                            break;
                        case CURLE_OPERATION_TIMEOUTED:
                            \Helper::appendMessage(new Message('操作超时', 10010));
                            break;

                        default:
                            return \Helper::appendMessage(new Message($message->getMessage(), 10086));
                            break;
                    }
                } else {
                    if ($resp !== '') {
                        return json_decode($resp, true);
                    } else {
                        /**
                         * @TODO 错误处理
                         */
                        return false;
                    }
                }
            }
        } else {
            Logger::error(sprintf("WPApi::call - 模块[%s]不存在", $name));
            return \Helper::appendMessage(new Message('模块不存在！', 10086));
        }

        return false;
    }

    /**
     *
     */
    public static function task($cmd, $data)
    {
        $params = [
            'type' => \Constant::MESSAGE_TYPE_TASK,
            'cmd'  => $cmd,
            'data' => is_string($data) ? $data : serialize($data),
        ];
        $body = json_encode($params);
        // $message = pack('nc*', strlen($body), $body);
        $message = pack('na*', strlen($body), $body);
        $len = self::$_task_client->send($message);

        if ($len) {
            return true;
        }

        return false;
    }

    /**
     * @param $url
     * @param array $params
     * @param string $type
     */
    public static function send($url, $params = [], $type = 'POST', $options = [], $timeout = 5, $connection_timeout = 5, $retry_time = 1)
    {
        // 超时时间监控
        if (($timeout > 5) || ($connection_timeout > 5)) {
            if (isset($options['is_ignore_monitor']) && $options['is_ignore_monitor'] == 1) {
                Logger::debug("接口[{$url}]超时时间大于5秒，并且设置了超时忽略");
            } else {
                $redis = Di::getDefault()->get('redis');
                if ($redis->getLock(md5($url), 900)) {
                    self::call('/DX/sms/send/', [
                        'type' => 1,
                        'mobile' => '1371***73',
                        'content' => "接口{$url}超时时间大于5秒，请核实。",
                    ], 'POST', [], 2, 1);
                }
            }
        }

        if ($type == 'POST') {
            if (isset($options['is_send_json']) && $options['is_send_json'] == 1) {
                $options['headers'][] = 'Content-Type: application/json';
                if(isset($options['is_unicode']) && $options['is_unicode'] == 0) {
                    $send_params = json_encode($params, JSON_UNESCAPED_UNICODE);
                } else {
                    $send_params = json_encode($params);
                }
            } elseif (isset($options['is_send_xml']) && $options['is_send_xml'] == 1) {
                $options['headers'][] = 'Content-Type: application/xml';
                $send_params = $params;
            } else {
                $send_params = is_array($params) && $options['is_ignore_monitor'] != 1 ? http_build_query($params) : $params;
            }
        } else {
            if ($params) {
                $send_params = is_array($params) ? http_build_query($params) : $params;
                if (strpos($url, '?') === false) {
                    $url .= '?' . $send_params;
                } else {
                    $url .= '&' . $send_params;
                }
            }
        }

        for ($i = 0; $i < $retry_time; $i ++) {
            $begin_time = microtime(true);
            if ($type == 'POST') {
                $resp = Http::quickPost($url, $send_params, $timeout, $connection_timeout, $options['headers'] ?: [], $options);
            } else {
                $cacert_url = isset($options['cacert']) ? $options['cacert'] : '';
                $resp = Http::quickGet($url, $timeout, $connection_timeout, $cacert_url, $options);
            }

            if (isset($options['is_recv_gzip']) && $options['is_recv_gzip']) {
                $resp = gzdecode($resp);
            }

            $is_transaction = !Logger::isTransaction();
            $is_transaction && Logger::begin();
            Logger::debug(sprintf('接口地址: %s', $url));
            Logger::debug(sprintf('发送数据: %s', is_array($params) ? http_build_query($params) : $params));
            Logger::debug(sprintf("接口返回: %s", $resp));
            Logger::debug(sprintf("USE TIME: %s", microtime(true) - $begin_time));
            $is_transaction && Logger::commit();
            if ($resp === false) {
                $message = \Helper::getLastMessage();
                Logger::error(sprintf("请求错误: %s[%s]", $message->getMessage(), $message->getCode()));
                switch ($message->getCode()) {
                    case CURLE_COULDNT_CONNECT:
                        \Helper::appendMessage(new Message('请求超时', 10010));
                        break;
                    case CURLE_OPERATION_TIMEOUTED:
                        \Helper::appendMessage(new Message('操作超时', 10010));
                        break;

                    default:
                        return \Helper::appendMessage(new Message($message->getMessage(), 10086));
                        break;
                }
            } else {
                if ($resp !== '') {
                    if (isset($options['is_recv_json']) && $options['is_recv_json'] == 1) {
                        return json_decode($resp, true);
                    } else {
                        return $resp;
                    }
                } else {
                    /**
                     * @TODO 错误处理
                     */
                    return false;
                }
            }
        }

        return false;
    }

    /**
     * 给参数加签名信息
     *
     * @param $params
     */
    public static function setSignature(&$params)
    {
        $params['app_id'] = self::$_app_id;
        $params['token']  = self::signature($params, null, null, self::VERSION);
    }

    /**
     * 签名
     *
     * @param $params
     * @param null $app_id
     * @param null $secret
     * @return string
     */
    private static function signature($params, $app_id = null, $secret = null, $version = 'v2.0.1')
    {
        if ($app_id === null) {
            $app_id = self::$_app_id;
        }
        if ($secret === null) {
            $secret = self::$_secret;
        }

        ksort($params);

        // 高于2.0版本
        if (\Helper::compareVersion($version, 'v2.0.0') >= 0) {
            $encrypt_str = self::convertToUri($params);
            $token = md5($encrypt_str . $secret);
        } else {
            $encrypt_str = http_build_query($params);
            $token = md5($encrypt_str . $secret);
        }

        return $token;
    }

    /**
     * 校验签名
     *
     * @param $token
     * @param $params
     * @return bool
     */
    public static function verifySignature($token, $params)
    {
        $app_id  = $params['app_id'];
        $version = $params['version'] ?: 'v1.0.1';
        $secret = isset(self::$appList[$app_id]['secret']) ? self::$appList[$app_id]['secret'] : self::DEFAULT_SECRET;
        if ($token == self::signature($params, $app_id, $secret, $version)) {
            return true;
        }

        return false;
    }

    /**
     * 生成
     *
     * @param $params
     * @param bool $is_filter_empty
     * @param bool $is_quotes
     * @param bool $is_special
     * @return string
     */
    public static function convertToUri($params, $unSignList = ['key', 'sign'], $is_filter_empty = false, $is_quotes = false, $is_special = false)
    {
        $str_to_be_signed = '';
        $i = 0;
        foreach ($params as $k => $v) {
            if (in_array($k, $unSignList)) {
                continue;
            }

            if ($is_filter_empty && self::checkEmpty($v)) {
                continue;
            }

            if ($i > 0) {
                $str_to_be_signed .= "&";
            }
            if ($is_quotes) {
                $str_to_be_signed .= "{$k}=\"{$v}\"";
            } else {
                $str_to_be_signed .= "{$k}=$v";
            }
            $i++;
        }
        unset($k, $v);

        return $is_special ? htmlspecialchars($str_to_be_signed, ENT_QUOTES, 'UTF-8') : $str_to_be_signed;
    }

    /**
     * 校验$value是否非空
     *  if not set ,return true;
     *    if is null , return true;
     **/
    protected static function checkEmpty($value) {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;

        return false;
    }
}


