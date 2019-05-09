<?php
/**
 * WPLib\WPApi 接口配置
 *
 * @author
 * @copyright 2014-2018
 */

namespace WPLib\WPApi;

use Redis;


class ApiConfig
{
    public static $_modules = [
        'develop' => [
            'RC' => [
                'name' => 'RBAC登录模块',
                'servers' => [
                    ['url' => 'http://129.xxxx.xxx.92:101', 'weight' => 100],
                ],
            ],

        ],
        // 预发布
        'preview' => [
            'RC' => [
                'name' => 'RBAC登录模块',
                'servers' => [
                    ['url' => 'http://129.xxxx.xxx.92::100', 'weight' => 100],
                ],
            ],

        ],
        'production' => [
            'RC' => [
                'name' => 'RBAC登录模块',
                'servers' => [
                    ['url' => 'http://129.xxxx.xxx.92:3090', 'weight' => 100],
                ],
            ],

        ],
    ];

    public static function getConfig()
    {
        return isset(self::$_config[ENVIRON]) ? self::$_config[ENVIRON] : [];
    }

    public static function getModules()
    {
        return self::$_modules[ENVIRON];
    }

    public static function setModules($modules, $force = false)
    {
        $module_filename = __DIR__ . '/modules.php';
        if (!$force) {
            $last_modify_time = filemtime($module_filename);
            if ($last_modify_time > time() - 3600) {
                return false;
            }
        }
        return self::write($module_filename, $modules);
    }

    public static function write($filename, $data)
    {
        $fp = fopen($filename, 'w+');
        if ($fp) {
            $data = var_export($data, true);
            $content = <<<EOQ
<?php
/**
 *
 * @author 
 */

return $data;


EOQ;
            fwrite($fp, $content);
            fclose($fp);

            return true;
        }

        return false;
    }
}