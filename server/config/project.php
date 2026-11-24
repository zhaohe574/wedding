<?php
return [
    // 系统版本号
    'version' => '1.9.4',

    // 官网
    'website' => [
        'name' => env('project.web_name', 'likeadmin'), // 网站名称
        'url' => env('project.web_url', 'www.likeadmin.cn/'), // 网站地址
        'login_image' => 'resource/image/adminapi/default/login_image.png',
        'web_logo' => 'resource/image/adminapi/default/web_logo.jpg', // 网站logo
        'web_favicon' => 'resource/image/adminapi/default/web_favicon.ico', // 网站图标
        'shop_name' => 'likeadmin', // 商城名称
        'shop_logo' => 'resource/image/adminapi/default/shop_logo.png', // 商城图标
        'pc_logo' => 'resource/image/adminapi/default/pc_logo.png', // pc_logo
        'pc_ico' => 'resource/image/adminapi/default/web_favicon.ico', // pc_ico
        'pc_title' => 'likeadmin', // PC网站标题
    ],

    // 后台登录
    'admin_login' => [
        // 管理后台登录限制 0-不限制 1-需要限制
        'login_restrictions' => 1,
        // 限制密码错误次数
        'password_error_times' => 5,
        // 限制禁止多少分钟不能登录
        'limit_login_time' => 30,
    ],

    // 唯一标识，密码盐、路径加密等
    'unique_identification' => env('project.unique_identification', 'likeadmin'),

    // 后台管理员token（登录令牌）配置（有状态 Session Token，临期自动滑动续期）
    'admin_token' => [
        'expire_duration' => (int)env('project.admin_token_expire_duration', 3600 * 8),//管理后台token过期时长(单位秒，默认8小时）
        'be_expire_duration' => (int)env('project.admin_token_be_expire_duration', 3600),//管理后台token过期前剩余时长触发自动续期（默认1小时）
    ],

    // 商城用户token（登录令牌）配置（有状态 Session Token，由 LoginMiddleware 临期自动滑动续期）
    'user_token' => [
        'expire_duration' => (int)env('project.user_token_expire_duration', 3600 * 24 * 7),//用户token过期时长(单位秒，默认7天）
        'be_expire_duration' => (int)env('project.user_token_be_expire_duration', 3600 * 24 * 2),//用户token过期前剩余时长触发自动续期（默认2天）
    ],

    // 写接口幂等保护（消费客户端 Idempotency-Key 请求头）
    'idempotency' => [
        'ttl' => (int)env('project.idempotency_ttl', 600),//成功结果缓存时长(单位秒，默认10分钟）
        'lock_ttl' => (int)env('project.idempotency_lock_ttl', 30),//并发执行中占位锁时长(单位秒，默认30秒）
    ],

    // 列表页
    'lists' => [
        'page_size_max' => 25000,//列表页查询数量限制（列表页每页数量、导出每页数量）
        'page_size' => 25, //默认每页数量
    ],

    // 各种默认图片
    'default_image' => [
        'admin_avatar' => 'resource/image/adminapi/default/avatar.png',
        'user_avatar' => 'resource/image/adminapi/default/default_avatar.png',
        'qq_group' => 'resource/image/adminapi/default/qq_group.png', // qq群
        'customer_service' => 'resource/image/adminapi/default/customer_service.jpg', // 客服
        'menu_admin' => 'resource/image/adminapi/default/menu_admin.png',// 首页快捷菜单-管理员
        'menu_role' => 'resource/image/adminapi/default/menu_role.png', // 首页快捷菜单-角色
        'menu_dept' => 'resource/image/adminapi/default/menu_dept.png',// 首页快捷菜单-部门
        'menu_dict' => 'resource/image/adminapi/default/menu_dict.png',// 首页快捷菜单-字典
        'menu_generator' => 'resource/image/adminapi/default/menu_generator.png',// 首页快捷菜单-代码生成器
        'menu_auth' => 'resource/image/adminapi/default/menu_auth.png',// 首页快捷菜单-菜单权限
        'menu_web' => 'resource/image/adminapi/default/menu_web.png',// 首页快捷菜单-网站信息
        'menu_file' => 'resource/image/adminapi/default/menu_file.png',// 首页快捷菜单-素材中心
    ],

    // 文件上传限制 (图片)
    'file_image' => [
        'jpg', 'png', 'gif', 'jpeg', 'webp', 'ico'
    ],

    // 文件上传限制 (视频)
    'file_video' => [
        'wmv', 'avi', 'mpg', 'mpeg', '3gp', 'mov', 'mp4', 'flv', 'f4v', 'rmvb', 'mkv'
    ],

    //上传文件的格式 (文件)
    'file_file' => [
        'zip','rar','txt','pdf','doc','docx','xls','xlsx','ppt','pptx','csv','txt','ftr','7z','gz'
    ],

    // 登录设置
    'login' => [
        // 登录方式：1-账号密码登录；2-手机短信验证码登录
        'login_way' => ['1', '2'],
        // 注册强制绑定手机 0-关闭 1-开启
        'coerce_mobile' => 1,
        // 第三方授权登录 0-关闭 1-开启
        'third_auth' => 1,
        // 微信授权登录 0-关闭 1-开启
        'wechat_auth' => 1,
        // 登录政策协议 0-关闭 1-开启
        'login_agreement' => 1,
    ],

    // 后台装修
    'decorate' => [
        // 底部导航栏样式设置
        'tabbar_style' => ['default_color' => '#999999', 'selected_color' => '#c455ff'],
    ],


    // 功能开关（默认值）
    'feature_switch' => [
        // 服务人员中心入口
        'staff_center' => 1,
        // 服务人员中心写能力灰度（资料/动态/作品/证书/套餐/附加项写入口，默认开启）
        'staff_center_write' => 1,
        // 服务人员后台账号/菜单
        'staff_admin' => 1,
        // 管理员看板入口
        'admin_dashboard' => 1,
        // 动态评论审核
        'comment_review_enabled' => 0,
        // 微信文本内容安全检测
        'wechat_text_check_enabled' => 1,
        // 资料文本命中最低置信度
        'wechat_text_check_profile_prob' => 80,
        // 评论文本命中最低置信度
        'wechat_text_check_comment_prob' => 70,
        // 微信 suggest=review 是否按命中处理
        'wechat_text_check_review_as_hit' => 1,
    ],

    // 风险等级权限（默认值）
    'risk_control' => [
        'rank_permissions' => [
            0 => [
                'enabled_abilities' => [
                    'profile',
                    'content_publish',
                    'comment',
                    'interaction',
                    'activity',
                    'schedule',
                    'order',
                    'payment',
                    'after_sale',
                    'customer_service',
                    'upload',
                    'notification',
                    'staff_center',
                ],
                'comment_force_review' => false,
            ],
            1 => [
                'enabled_abilities' => [
                    'profile',
                    'content_publish',
                    'comment',
                    'interaction',
                    'activity',
                    'schedule',
                    'order',
                    'payment',
                    'after_sale',
                    'customer_service',
                    'upload',
                    'notification',
                    'staff_center',
                ],
                'comment_force_review' => false,
            ],
            2 => [
                'enabled_abilities' => [
                    'profile',
                    'content_publish',
                    'comment',
                    'interaction',
                    'activity',
                    'schedule',
                    'order',
                    'payment',
                    'after_sale',
                    'customer_service',
                    'upload',
                    'notification',
                    'staff_center',
                ],
                'comment_force_review' => true,
            ],
            3 => [
                'enabled_abilities' => [],
                'comment_force_review' => false,
            ],
            4 => [
                'enabled_abilities' => [],
                'comment_force_review' => false,
            ],
        ],
    ],

];
