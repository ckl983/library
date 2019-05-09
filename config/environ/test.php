<?php
/**
 * @desc 测试环境配置
 */

return [
    'mongo' => [
        'servers' => [
            [
                'host'   => '192.168.1.236',
                'port'   => '27017',
            ],
        ],
    ],
    'session' => [
        'servers' => [
            [
                'host' => '192.168.1.236',
                'port' => '6379',
            ],
        ],
    ],
    'beanstalk' => [
        'servers' => [
            [
                'host'   => '192.168.1.236',
                'port' => '11300',
                'weight' => 50,
            ],
        ],
    ],
    'cache' => [
        'backend' => [
            'servers'     => [
                [
                    'host'   => '192.168.1.236',
                    'port'   => 11211,
                    'weight' => 50,
                ],
            ],
        ],
        'metadata' => [
            'servers'     => [
                [
                    'host'   => '192.168.1.236',
                    'port'   => 11211,
                ],
            ],
        ],
        'redis' => [
            'servers'     => [
                [
                    'host'   => '192.168.1.236',
                    'port'   => 6379,
                ],
            ],
        ],
    ],
];
