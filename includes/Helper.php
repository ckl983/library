<?php

/**
 * 通用类库
 *
 * @author
 * @copyright 2014-2018
 */
 
 
class Helper
{
    // 小写字母
    const LOWER_ALPHA    = 1;
    // 大写字母
    const UPPER_ALPHA    = 2;
    // 数字
    const NUMBER         = 4;
    // 特殊符号
    const SPECIAL        = 8;

    // 大小写字母
    const ALPHA          = 3;
    // 小写字母+数字
    const LOWER_ALPHANUM = 5;
    // 大写字母+数字
    const UPPER_ALPHANUM = 6;
    // 大小写字母+数字
    const ALPHANUM       = 7;
    // 密码: 大小写字母+数字+特殊字符
    const PASSWORD       = 15;

    /**
     * 错误列表
     *
     * @var array
     */
    private static $_errorMessages = [];

    private static $errors = [];

    /**
     * UTF-8:  "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/"
     * GB2312: "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/"
     * GBK:    "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/"
     * BIG5:   "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/"
     */
    public static function strlen($string, $chinese_len = 2)
    {
        preg_match_all("/./us", $string, $match);
        $strlen = 0;
        foreach ($match[0] as $k => $v) {
            if (ord($v[0]) > 129) {
                $strlen += $chinese_len;
            } else {
                $strlen += 1;
            }
        }

        return $strlen;
    }

    public static function substr($string, $start, $length, $padstr = '...', $chinese_len = 2)
    {
        if (self::strlen($string, $chinese_len) <= $length)
            return $string;

        // $regExp ="/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
        $regExp = "/./us";
        preg_match_all($regExp, $string, $match);
        $i = $j = 0;
        $length -= self::strlen($padstr);
        $retStr = '';
        foreach ($match[0] as $k => $v) {
            if (ord($v[0]) > 129) {
                $j = $chinese_len;
            } else {
                $j = 1;
            }
            $i += $j;
            if ($i > $length) {
                $retStr .= $padstr;
                break;
            }
            $retStr .= $v;
        }

        return $retStr;
    }

    public static function escape($string, $in_encoding = 'UTF-8', $out_encoding = 'UCS-2')
    {
        $return = '';
        if (function_exists('mb_get_info')) {
            for($x = 0; $x < mb_strlen($string, $in_encoding); $x ++) {
                $str = mb_substr($string, $x, 1, $in_encoding);
                if (strlen($str) > 1) { // 多字节字符
                    $return .= '%u' . strtoupper(bin2hex(mb_convert_encoding($str, $out_encoding, $in_encoding)));
                } else {
                    $return .= '%' . strtoupper(bin2hex($str));
                }
            }
        }

        return $return;
    }

    public static function unescape($str)
    {
        $ret = '';
        $len = strlen($str);
        for ($i = 0; $i < $len; $i ++) {
            if ($str[$i] == '%' && $str[$i + 1] == 'u') {
                $val = hexdec(substr($str, $i + 2, 4));
                if ($val < 0x7f) {
                    $ret .= chr($val);
                } else {
                    if ($val < 0x800) {
                        $ret .= chr(0xc0 | ($val >> 6)) . chr(0x80 | ($val & 0x3f));
                    } else {
                        $ret .= chr(0xe0 | ($val >> 12)) . chr(0x80 | (($val >> 6) & 0x3f)) . chr(0x80 | ($val & 0x3f));
                    }
                }
                $i += 5;
            } else {
                if ($str[$i] == '%') {
                    $ret .= urldecode(substr($str, $i, 3));
                    $i += 2;
                } else {
                    $ret .= $str[$i];
                }
            }
        }

        return $ret;
    }

    /**
     * 驼峰
     */
    public static function camelize($str, $delimit = null)
    {
        if (is_string($str)) {
            if ($delimit === null) {
                $arr = preg_split('/[_\W\s\b]+/', $str, -1, PREG_SPLIT_NO_EMPTY);
            } else {
                $arr = explode($delimit, $str);
            }
        } else {
            echo 2;
            $arr = $str;
        }

        $camelize_arr = array_map('ucfirst', $arr);
        return implode('', $camelize_arr);
    }

    public static function array_search($needle, $haystack, $pos = -1, $strict = false)
    {
        if (!is_array($haystack)) {
            return false;
        }

        $key = false;
        if (is_array(current($haystack))) {
            foreach ($haystack as $k => $v) {
                if (is_array($v)) {
                    if ($pos == -1) {
                        $result = self::array_search($needle, $v, $pos, $strict);
                        if ($result !== false) {
                            $key = $k;
                            break;
                        }
                    } else {
                        if (($strict && $v[$pos] === $needle) || (!$strict && $v[$pos] == $needle)) {
                            $key = $k;
                            break;
                        }
                    }
                } else {
                    if (($strict && $v === $needle) || (!$strict && $v == $needle)) {
                        $key = $k;
                        break;
                    }
                }
            }
        } else {
            $key = array_search($needle, $haystack, $strict);
        }

        return $key;
    }

    /**
     * 时间(距离)格式化，
     *
     * @param $time
     * @return string
     */
    public static function formatTime($time)
    {
        $formated_time = '';
        if ($time > 86400) {
            $day  = $time / 86400;
            $time = $time % 86400;
            $formated_time .= "{$day}天";
        }

        if ($time > 3600) {
            $hour = $time / 3600;
            $time = $time % 3600;
            $formated_time .= "{$hour}小时";
        }

        if ($time > 60) {
            $minute  = $time / 60;
            $time    = $time % 60;
            $formated_time .= "{$minute}分钟";
        }

        if ($time > 0) {
            $formated_time .= "{$time}秒";
        }

        return $formated_time;
    }

    /**
     * 毫秒时间格式化
     *
     * @param string $format
     * @param $timestamp_ms
     * @return string
     */
    public static function date($format = 'Y-m-d H:i:s', $timestamp_ms)
    {
        return sprintf('%s.%03s', date($format, floor($timestamp_ms / 1000)), $timestamp_ms % 1000);
    }

    /**
     * 用户邮箱是否正确，有进行MX验证
     * @param string $email
     * @param boolean $isCheckMX 是否验证MX记录
     * @return boolean
     */
    public static function isEmail($email, $isCheckMX = false)
    {
        $domain = trim(strstr($email, '@'), '@');

        if (!preg_match('/^[a-z\d](([a-z\d_\-\.]*)([a-z\d]))@([a-z\d][a-z\d\-\_]*)?(([a-z\d][a-z\d\-]*)\.)+([a-z]{2,4}(\.[a-z]{2})?)$/i', $email)) {
            return false;
        }

        if ($isCheckMX && !checkdnsrr($domain, 'MX')) {
            return false;
        }

        return true;
    }

    /**
     * 手机号码验证
     * @param string $userMobile 手机号码
     */
    public static function isMobilePhone($userMobile)
    {
        return strlen($userMobile) == 11 &&
        preg_match('/^1([3|4|5|6|7|8|9])\d{9}$/', $userMobile);
    }

    public static function ip2long($ip)
    {
        return bindec(decbin(ip2long($ip)));
    }

    /**
     * 检查IP是否包含在CIDR中
     *
     * @param mixed $cidrs CIDR列表
     * @param mixed $ip IP
     * @return bool
     */
    public static function checkIpByCIDRs($cidrs, $ip = null)
    {
        if ($ip === null) {
            $ip = self::getClientIp(true);
        } elseif (!is_int($ip)) {
            $ip = ip2long($ip);
        }

        if (!is_array($cidrs)) {
            $cidrs = [$cidrs];
        }

        foreach ($cidrs as $k => $v) {
            if (strpos($v, '/') === false) {
                if (ip2long($v) === $ip) {
                    return true;
                }
            } else {
                list($net, $netmask) = explode('/', $v);
                $netmask = bindec(str_repeat(1, $netmask) . str_repeat('0', 32 - $netmask));
                $net = ip2long($net) & $netmask;
                if (($ip & $netmask) == $net) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 返回客户端的IP地址
     * @param boolean $isInt 所否返回整形IP地址
     * @return mixed $ip 用户IP地址
     */
    public static function getClientIp($isInt = false)
    {
        //$ip = false;
        if(!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER["REMOTE_ADDR"];
        } else if(! empty($_SERVER["HTTP_CLIENT_IP"])){
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }

        if(! empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            $ips = explode(", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
            if($ip){
                array_unshift($ips, $ip);
                $ip = FALSE;
            }
            for($i = 0; $i < count($ips); $i ++){
                if(! preg_match("/^(0|10|100|127|172\.16|192\.168)\./", $ips[$i])){
                    $ip = $ips[$i];
                    break;
                }
            }
        }
        $ip = $ip ? $ip : $_SERVER['REMOTE_ADDR'];
        $ip = $ip ? $ip : '0.0.0.0';

        return $isInt ? self::ip2long($ip) : $ip;
    }

    /**
     * 版本比较
     * @param $oldVersion   老版本
     * @param $newVersion   新版本
     * @return int          0 => 相同， 1 => 老版本更高，-1 => 新版本更高
     */
    public static function compareVersion($oldVersion, $newVersion)
    {
        $oldVersionAry = explode('.', $oldVersion);
        $newVersionAry = explode('.', $newVersion);

        if (count($oldVersionAry) > count($newVersionAry)) {
            for ($i = 0, $j = count($oldVersionAry) - count($newVersionAry); $i < $j; $i ++) {
                $newVersion[] = '0';
            }
        }

        $ret = 0;
        for ($i = 0, $j = count($newVersionAry); $i < $j; $i ++) {
            if ($oldVersionAry[$i] > $newVersionAry[$i]) {
                $ret = 1;
                break;
            } elseif ($oldVersionAry[$i] < $newVersionAry[$i]) {
                $ret = -1;
                break;
            }
        }

        return $ret;
    }

    public static function generateRandString($type = self::ALPHANUM, $len = 6)
    {
        $rand_string = '';
        $string_list = '';

        if (($type & self::LOWER_ALPHA) == self::LOWER_ALPHA) {
            $string_list .= 'abcdefghijkmnpqrstuvwxyz';
        }
        if (($type & self::UPPER_ALPHA) == self::UPPER_ALPHA) {
            $string_list .= 'ABCDEFGHIJKLMNPQRSTUVWXYZ';
        }
        if (($type & self::NUMBER) == self::NUMBER) {
            $string_list .= '0123456789';
        }
        if (($type & self::SPECIAL) == self::SPECIAL) {
            $string_list .= '!@#$%^&*';
        }

        $string_list_len = strlen($string_list);

        for ($i = 0; $i < $len; $i ++) {
            $pos = mt_rand(0, $string_list_len - 1);
            $rand_string .= $string_list[$pos];
        }

        return $rand_string;
    }

    /**
     * 或取带权重的随机数（抽奖）
     */
    public static function getRandByWeight($weightList, $key = null, $ignioreList = [])
    {
        if ($key !== null) {
            $tmp_list = [];
            foreach ($weightList as $k => $v) {
                $tmp_list[$k] = $v[$key];
            }
            $weightList = $tmp_list;
        }
        $total_weight = 0;
        foreach ($weightList as $k => $v) {
            if (array_search($k, $ignioreList) !== false) {
                unset($weightList[$k]);
            } else {
                $total_weight += $v;
                $weightList[$k] = $total_weight;
            }
        }

        $num = mt_rand(1, $total_weight);
        foreach ($weightList as $k => $v) {
            if ($num <= $v) {
                $key = $k;
                break;
            }
        }

        return $key;
    }

    /**
     * 字符串加密以及解密函数
     *
     * @param string $string 原文或者密文
     * @param string $operation 操作(ENCODE | DECODE), 默认为 DECODE
     * @param string $key 密钥
     * @param int $expiry 密文有效期, 加密时候有效， 单位 秒，0 为永久有效
     * @return string 处理后的 原文或者 经过 base64_encode 处理后的密文
     *
     * @example
     *
     *     $a = authcode('abc', 'ENCODE', 'key');
     *     $b = authcode($a, 'DECODE', 'key'); // $b(abc)
     *
     *     $a = authcode('abc', 'ENCODE', 'key', 3600);
     *     $b = authcode('abc', 'DECODE', 'key'); // 在一个小时内，$b(abc)，否则 $b 为空
     */
    public static function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0)
    {
        $ckey_length = 4;    // 随机密钥长度 取值 0-32;
        // 加入随机密钥，可以令密文无任何规律，即便是原文和密钥完全相同，加密结果也会每次不同，增大破解难度。
        // 取值越大，密文变动规律越大，密文变化 = 16 的 $ckey_length 次方
        // 当此值为 0 时，则不产生随机密钥
        $di = Phalcon\Di::getDefault();
        $config = $di->get('config');
        $key = md5($key ? $key : (isset($config->application['encryptKey']) ? $config->application['encryptKey'] : 'NVrMz5usBcIntBAv'));
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
        $cryptkey = $keya.md5($keya.$keyc);
        $key_length = strlen($cryptkey);
        $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
        $string_length = strlen($string);
        $result = '';
        $box = range(0, 255);
        $rndkey = array();
        for($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }
        for($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        for($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        if($operation == 'DECODE') {
            if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $keyc.str_replace('=', '', base64_encode($result));
        }
    }

    public static function imgInfo($imgFile)
    {

        if (is_file($imgFile)) {
            $res = getimagesize($imgFile);
            if ($res !== FALSE) {
                $imgInfo = array(
                    'width'  => $res[0],
                    'height' => $res[1],
                    'type'   => self::$imgTypes[$res[2]],
                    'mime'   => $res['mime'],
                );
            } else {
                $imgInfo = array();
            }
        } else {
            $imgInfo = array();
        }

        return $imgInfo;
    }

    /*
    * 删除文件夹及其文件夹下所有文件
    */
    public static function deldir($dir)
    {
        $dh=opendir($dir); #先删除目录下的文件
        while ($file=readdir($dh)) {
            if($file!="." && $file!="..") {
                $fullpath=$dir."/".$file;
                if(!is_dir($fullpath)) {
                    unlink($fullpath);
                } else {
                    deldir($fullpath);
                }
            }
        }
        closedir($dh);
        if(rmdir($dir)) { #删除当前文件夹：
            return true;
        } else {
            return false;
        }
    }

    /**
     * 转换数组为XML
     *
     * @return string
     * @throws
     */
    public static function toXml($data, $encoding = 'UTF-8', $root = 'root', $xml = null)
    {
        if(!is_array($data)
            || count($data) <= 0)
        {
            throw new \Exception("数组数据异常！");
        }

        if ($xml === null) {
            $xml = simplexml_load_string("<?xml version=\"1.0\" encoding=\"{$encoding}\"?><{$root}/>");
        }
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $child = $xml->addChild($key);
                self::toXml($val, $encoding, '', $child);
            } else {
                $xml->$key = $val;
            }
        }

        return $xml;
    }

    public static function fromXml($data)
    {
        libxml_disable_entity_loader(true);
        $res = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOEMPTYTAG);

        if ($res instanceof \SimpleXMLElement) {
            return json_decode(json_encode($res), true);
        }

        return [];
    }

    public static function addError($errNo, $errMsg = '', $return = false)
    {
        self::$errors[] = array(
            'code'    => $errNo,
            'message' => $errMsg,
        );

        return $return;
    }

    public static function getLastError()
    {
        return end(self::$errors) ?: [];
    }

    /**
     * Returns all the validation messages
     *
     * <code>
     * $robot = new Robots();
     * $robot->type = 'mechanical';
     * $robot->name = 'Astro Boy';
     * $robot->year = 1952;
     * if ($robot->save() == false) {
     *	echo "Umh, We can't store robots right now ";
     *	foreach ($robot->getMessages() as message) {
     *		echo message;
     *	}
     *} else {
     *	echo "Great, a new robot was saved successfully!";
     *}
     * </code>
     */
    public static function getMessages()
    {
        return self::$_errorMessages;
    }

    /**
     * 返回最后一个错误信息
     *
     * @return mixed
     */
    public static function getLastMessage()
    {
        return array_pop(self::$_errorMessages);
    }

    /**
     * Appends a customized message on the validation process
     *
     *<code>
     *	use \Phalcon\Mvc\Model\Message as Message;
     *
     *	class Robots extends \Phalcon\Mvc\Model
     *	{
     *
     *		public function beforeSave()
     *		{
     *			if (self::$name == 'Peter') {
     *				message = new Message("Sorry, but a robot cannot be named Peter");
     *				self::$appendMessage(message);
     *			}
     *		}
     *	}
     *</code>
     */
    public static function appendMessage($message, $returnValue = false)
    {
        array_push(self::$_errorMessages, $message);

        return $returnValue;
    }
}