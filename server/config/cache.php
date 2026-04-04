<?php

// +----------------------------------------------------------------------
// | 缓存设置
// +----------------------------------------------------------------------

return [
    // 默认缓存驱动
    'default' => env('cache.driver', 'file'),

    // 缓存连接方式配置
    'stores'  => [
        'file' => [
            // 驱动方式
            'type'       => 'File',
            // 缓存保存目录
            'path'       => '',
            // 缓存前缀
            'prefix'     => 'la',
            // 缓存有效期 0表示永久缓存
            'expire'     => 0,
            // 缓存标签前缀
            'tag_prefix' => 'tag:',
            // 序列化机制 例如 ['serialize', 'unserialize']
            'serialize'  => [],
        ],
        // 配置缓存（跨应用共享，避免多应用模式下配置缓存不一致）
        'config' => [
            // 驱动方式
            'type'       => 'File',
            // 使用根目录 runtime/cache，确保 adminapi/api 共享同一缓存空间
            'path'       => root_path() . 'runtime/cache/',
            // 缓存前缀
            'prefix'     => 'la',
            // 缓存有效期 0表示永久缓存
            'expire'     => 0,
            // 缓存标签前缀
            'tag_prefix' => 'tag:',
            // 序列化机制 例如 ['serialize', 'unserialize']
            'serialize'  => [],
        ],
        // redis缓存
        'redis'  =>  [
            // 驱动方式
            'type'   => 'redis',
            // 服务器地址
            'host'   => env('cache.host','like-redis'),
            // 端口
            'port'   => env('cache.port','6379'),
            // 密码
            'password' => env('cache.password', ''),
            // 缓存前缀
            'prefix' => 'la:',
        ],
    ],
];
