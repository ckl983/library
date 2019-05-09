<?php

/**
 * Url工具类
 *
 * @author
 * @copyright 2014-2018
 */
 
 
class Url
{
    /**
     * 根据parse_url格式的数组生成完整的url
     * @param array $arr 接受parse_url解析出来的所有参数,完整参数实例如下：
     *        Array
     *        (
     *            [scheme] => http            // 协议
     *            [host] => www.baidu.com     // 主机
     *            [port] => 80                // 端口，可选
     *            [path] => /path/file.php    // 路径(文件名)，可选
     *            [query] => a=aaa&b=aaabbb    // 参数(query string)，可选
     *            [fragment] => 123            // 附加部分或者叫做锚点(#后面的)，可选
     *        )
     */
    public static function http_build_url($url_arr) {
        if (function_exists('http_build_url')) {
            $new_url = http_build_url($url_arr);
        } else {
            $new_url = $url_arr['scheme'] . "://" . $url_arr['host'];
            if (!empty($url_arr['port']))
                $new_url = $new_url . ":" . $url_arr['port'];
            $new_url = $new_url . $url_arr['path'];
            if (!empty($url_arr['query']))
                $new_url = $new_url . "?" . $url_arr['query'];
            if (!empty($url_arr['fragment']))
                $new_url = $new_url . "#" . $url_arr['fragment'];
        }
        return $new_url;
    }

    /**
     * 是否HTTPS请求
     *
     * @return bool
     */
    public static function isSSLRequest()
    {
        return ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? true : false;
    }
    /**
     * 转换为绝对链接
     *
     * @param $url
     * @param array $params
     * @return string
     */
    public static function convertAbsoluteUrl($url, $params = [])
    {
        $is_ssl = self::isSSLRequest();
        $protocol = $is_ssl ? 'https' : 'http';

        $url_arr = parse_url($url) + ['scheme' => '', 'host' => '', 'port' => '', 'query' => ''];

        if (!empty($params) && is_array($params)) {
            $query_arr = [];
            if (isset($url_arr['query'])) {
                parse_str($url_arr['query'], $query_arr);
            }
            $query_arr = array_merge($query_arr, $params);
            $url_arr['query'] = http_build_query($query_arr);
        }

        $url_arr['scheme'] = $url_arr['scheme'] ?: $protocol;
        $url_arr['host']   = $url_arr['host'] ?: (strpos($_SERVER['HTTP_HOST'], ':') !== FALSE ? substr($_SERVER['HTTP_HOST'], 0, strpos($_SERVER['HTTP_HOST'], ':')) : $_SERVER['HTTP_HOST']);
        $url_arr['port']   = $url_arr['port'] ?: $_SERVER['SERVER_PORT'];
        if ((!$is_ssl && $url_arr['port'] == 80)
            || ($is_ssl && $url_arr['port'] == 443)) {
            unset($url_arr['port']);
        }

        $absolute_url = Url::http_build_url($url_arr);

        return $absolute_url;
    }
}
