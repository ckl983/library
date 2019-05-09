<?php
/**
 * @desc 预发布环境配置
 */

return [
    'mongo' => [
        'servers' => [
            [
                'host' => '172.16.5.190',
                'port' => '27017',
            ],
        ],
        'replicaSet' => '',
    ],
    'session' => [
        'servers' => [
            [
                'host' => '172.16.5.190',
                'port' => '6379',
            ],
        ],
    ],
    'beanstalk' => [
        'servers' => [
            [
                'host'   => '172.16.5.190',
                'port'   => '11300',
                'weight' => 50,
            ],
        ],
    ],
    'cache' => [
        'backend' => [
            'servers'     => [
                [
                    'host'   => '172.16.5.190',
                    'port'   => 11211,
                    'weight' => 50,
                ],
            ],
            'client' => [
                'prefix' => 'xxx::fw::',
            ],
            'lifetime' => 3600,
        ],
        'metadata' => [
            'servers' => [
                [
                    'host'   => '172.16.5.190',
                    'port'   => 11211,
                ],
            ],
            'client'   => [],
            'prefix'   => 'xxx::fw::',
            'lifetime' => 86400,
            // 'persistent' => false,
        ],
        'redis' => [
            'servers' => [
                [
                    'host' => '172.16.5.190',
                    'port' => 6379,
                ],
            ],
        ],
    ],
    'file' => [
        'avatar' => [
            'config' => [
                'endpoint' => 'oss-cn-shenzhen-internal.aliyuncs.com',
            ],
        ],
        'idcard' => [
            'config' => [
                'endpoint' => 'oss-cn-shenzhen-internal.aliyuncs.com',
            ],
        ],
    ],
];
