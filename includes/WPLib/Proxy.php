<?php

/**
 * HTTP代理
 *
 * @author
 * @copyright 2014-2018
 */

namespace WPLib;


class Proxy
{
    public static $proxyList = [];

    public static function getProxyInfo()
    {
        self::initialize();

        $i = array_rand(self::$proxyList);
        return self::$proxyList[$i];
    }

    public static function initialize()
    {
        if (empty(self::$proxyList)) {
            for ($i = 1; $i <= 2; $i ++) {
                $url = "https://www.kuaidaili.com/free/inha/{$i}/";
                $rules = [
                    'host' => ['td:eq(0)', 'text'],
                    'port' => ['td:eq(1)', 'text'],
                ];
                $ql = \QL\QueryList::Query($url, $rules, '#list tbody tr');

                if ($ql->data) {
                    foreach ($ql->data as $k => $v) {
                        self::$proxyList[] = [
                            'ip' => $v['ip'],
                            'port' => $v['port'],
                        ];
                    }
                }
            }
        }
    }
}