<?php
/**
 * @desc 通用配置
 */

!defined('ENVIRON') && define('ENVIRON', 'production');

return array_replace_recursive(
    [
        'application' => [
            'controllersDir' => APP_PATH . 'controllers/',
            'modelsDir'      => APP_PATH . 'models/',
            'migrationsDir'  => APP_PATH . 'migrations/',
            'modelExtends'   => 'WPLib\Base\Model',
            'viewsDir'       => APP_PATH . 'views/',
        ],
        'setting' => [
            'site_name'    => '',
            'site_domain'  => '',
        ],
        'database' => [
            "adapter"     => "Mysql",
            "charset"     => "utf8",
            'prefix'      => '',
        ],
        'mongo' => [
            'dbname'     => '',
        ],
        'session' => [
            'lifetime' => 86400 * 15,
            'prefix'   => 'xxx::session::',
            'auth'     => 'VoJwbJE2OHse8hP2',
            'statsKey' => '',
        ],
        'cache' => [
            'frontend' => [
                'lifetime'    => 3600,
            ],
            'backend' => [
                'client'       => [],
                'prefix' => 'xxx::fw::',
                'lifetime' => 3600,
            ],
            'metadata' => [
                'servers' => [],
                'client' => [],
                'prefix' => 'xxx::fw::',
                'lifetime' => 86400,
                // 'persistent' => false,
            ],
            'redis' => [
                'prefix' => 'xxx::',
                'auth'   => 'VoJwbJE2OHse8hP2',
                'statsKey' => '',
            ],
        ],
        'file' => [
            'avatar' => [
                'Adapter' => '\WPLib\File\Adapter\Aliyun',
                'config' => [
                    'accessKeyId' => 'LTAIIr2hmUniZIFL',
                    'accessKeySecret' => 'tEMEwrCiLbUZYowdFmIZDIM7ZWzsjP',
                    'endpoint' => 'oss-cn-shenzhen.aliyuncs.com',
                    'bucket' => 'jiabeiplus-public',
                    'access' => [
                        'type' => 'public',
                        'domain' => [
                            'http://fs1.jiabeiplus.com/',
                            'http://fs2.jiabeiplus.com/',
                        ],
                    ],
                ],
            ],
            'idcard' => [
                'Adapter' => '\WPLib\File\Adapter\Aliyun',
                'config' => [
                    'accessKeyId' => 'LTAICPNdFaLuwzif',
                    'accessKeySecret' => 'z7Tfi6dr68uogj3wrmd3qJP8SziJpl',
                    'endpoint' => 'oss-cn-shenzhen.aliyuncs.com',
                    'bucket' => 'jiabeiplus-secret',
                    'access' => [
                        'type'    => 'expired',
                        'timeout' => 300,
                        'domain'  => [
                            'http://fs3.jiabeiplus.com/',
                            'http://fs4.jiabeiplus.com/',
                        ],
                    ],
                ],
            ],
        ],
        'logger' => [
            'filename' => DATA_PATH . 'logs/' . APP_NAME . '/application.log',
        ],
    ],
    include __DIR__ . "/environ/" . ENVIRON . ".php"
);
