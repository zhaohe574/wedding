-- =============================================
-- 婚庆服务预约系统 v2.0.0 数据库安装脚本（最终安装态）
-- 版本：2.0.0.20260201
-- 说明：
-- 1. 执行目标：已有 LikeAdmin 基础库上的婚庆业务升级
-- 2. 结构目标：以当前仓库 2.0 代码依赖为准
-- 3. 基础数据：以 2026-03-19 本地/远端数据库比对结果为准
-- 4. 原则：保留业务数据，移除全量删表重建思路
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================================
-- Part 1: 业务表结构（不存在时创建）
-- =============================================================================

-- la_service_category
CREATE TABLE IF NOT EXISTS `la_service_category` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '分类ID',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '分类名称',
  `pid` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '上级分类ID',
  `icon` varchar(255) NOT NULL DEFAULT '' COMMENT '分类图标',
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '分类图片',
  `booking_butler_enabled` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否启用婚礼管家预约：0-否，1-是',
  `booking_butler_category_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '婚礼管家关联服务分类ID',
  `booking_director_enabled` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否启用婚礼督导预约：0-否，1-是',
  `booking_director_category_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '婚礼督导关联服务分类ID',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序(数值越大越靠前)',
  `is_show` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否显示:0-否,1-是',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `delete_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_pid` (`pid`) USING BTREE,
  KEY `idx_is_show` (`is_show`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务分类表';

-- la_style_tag
CREATE TABLE IF NOT EXISTS `la_style_tag` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '标签ID',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '标签名称',
  `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '标签类型:1-风格,2-特长,3-其他',
  `category_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务分类ID',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `is_show` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否显示:0-否,1-是',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `delete_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_type` (`type`) USING BTREE,
  KEY `idx_category_id` (`category_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='风格标签表';

-- la_service_package
CREATE TABLE IF NOT EXISTS `la_service_package` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '套餐ID',
  `category_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务分类ID',
  `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属服务人员ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '套餐名称',
  `price` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '套餐价格',
  `original_price` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '原价',
  `duration` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务时长(小时)',
  `description` text COMMENT '套餐描述',
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '套餐图片',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `is_recommend` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否推荐:0-否,1-是',
  `is_show` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否显示:0-否,1-是',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `delete_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_category_id` (`category_id`) USING BTREE,
  KEY `idx_staff_id` (`staff_id`),
  KEY `idx_is_recommend` (`is_recommend`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务套餐表';

-- la_service_addon
CREATE TABLE IF NOT EXISTS `la_service_addon` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属服务人员ID',
  `category_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属服务分类ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '附加服务名称',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '售价',
  `original_price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '原价',
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '图片',
  `description` varchar(500) NOT NULL DEFAULT '' COMMENT '描述',
  `sort` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
  `is_show` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否上架：0=下架，1=上架',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `delete_time` int(10) UNSIGNED DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_staff_id` (`staff_id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_is_show` (`is_show`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='附加服务配置表';

-- la_service_package_addon
CREATE TABLE IF NOT EXISTS `la_service_package_addon` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `package_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '套餐ID',
  `addon_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '附加服务ID',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_package_addon` (`package_id`, `addon_id`),
  KEY `idx_package_id` (`package_id`),
  KEY `idx_addon_id` (`addon_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='套餐附加服务关联表';

-- la_service_city_pool
CREATE TABLE IF NOT EXISTS `la_service_city_pool` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `province_code` varchar(12) NOT NULL DEFAULT '' COMMENT '省编码',
  `province_name` varchar(50) NOT NULL DEFAULT '' COMMENT '省名称',
  `city_code` varchar(12) NOT NULL DEFAULT '' COMMENT '市编码',
  `city_name` varchar(50) NOT NULL DEFAULT '' COMMENT '市名称',
  `sort` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=停用，1=启用',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_city_code` (`city_code`),
  KEY `idx_status_sort` (`status`, `sort`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='可接单城市池';

-- la_service_package_region_price
CREATE TABLE IF NOT EXISTS `la_service_package_region_price` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `package_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '套餐ID',
  `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '人员ID',
  `region_level` tinyint(1) UNSIGNED NOT NULL DEFAULT 2 COMMENT '地区层级：1=省，2=市，3=区县',
  `province_code` varchar(12) NOT NULL DEFAULT '' COMMENT '省编码',
  `province_name` varchar(50) NOT NULL DEFAULT '' COMMENT '省名称',
  `city_code` varchar(12) NOT NULL DEFAULT '' COMMENT '市编码',
  `city_name` varchar(50) NOT NULL DEFAULT '' COMMENT '市名称',
  `district_code` varchar(12) NOT NULL DEFAULT '' COMMENT '区县编码',
  `district_name` varchar(50) NOT NULL DEFAULT '' COMMENT '区县名称',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '地区售价',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_package_region` (`package_id`, `region_level`, `province_code`, `city_code`, `district_code`),
  KEY `idx_staff_package` (`staff_id`, `package_id`),
  KEY `idx_city_district` (`city_code`, `district_code`),
  KEY `idx_province_city_district` (`province_code`, `city_code`, `district_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='套餐地区价格表';

-- la_staff
CREATE TABLE IF NOT EXISTS `la_staff` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '工作人员ID',
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联用户ID(用于登录)',
  `admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联后台管理员ID',
  `sn` varchar(32) NOT NULL DEFAULT '' COMMENT '工号',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '姓名',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
  `mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号(脱敏显示)',
  `mobile_full` varchar(20) NOT NULL DEFAULT '' COMMENT '完整手机号',
  `wecom_userid` varchar(64) NOT NULL DEFAULT '' COMMENT '企业微信成员ID',
  `category_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务分类ID',
  `experience_years` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '从业年限',
  `profile` text COMMENT '个人简介',
  `service_desc` text COMMENT '服务说明',
  `long_detail` longtext COMMENT '长图详情(JSON)',
  `booking_option_1_name` varchar(100) NOT NULL DEFAULT '' COMMENT '预约附加项1名称',
  `booking_option_1_price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '预约附加项1价格',
  `booking_option_2_name` varchar(100) NOT NULL DEFAULT '' COMMENT '预约附加项2名称',
  `booking_option_2_price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '预约附加项2价格',
  -- 轮播图配置（来自 021）
  `banner_mode` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '轮播图展示模式：1=小图模式，2=大图模式',
  `banner_small_height` int(11) UNSIGNED NOT NULL DEFAULT 400 COMMENT '小图模式初始高度（rpx）',
  `banner_large_height` int(11) UNSIGNED NOT NULL DEFAULT 600 COMMENT '大图模式/展开后高度（rpx）',
  `banner_indicator_style` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '指示器样式：1=圆点，2=数字，3=进度条，0=无',
  `banner_autoplay` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否自动轮播：0=否，1=是',
  `banner_interval` int(11) UNSIGNED NOT NULL DEFAULT 3000 COMMENT '轮播间隔时间（毫秒）',
  -- 评分
  `rating` decimal(3,2) UNSIGNED NOT NULL DEFAULT 5.00 COMMENT '综合评分(1-5)',
  `rating_service` decimal(3,2) UNSIGNED NOT NULL DEFAULT 5.00 COMMENT '服务态度评分',
  `rating_skill` decimal(3,2) UNSIGNED NOT NULL DEFAULT 5.00 COMMENT '专业水平评分',
  `rating_price` decimal(3,2) UNSIGNED NOT NULL DEFAULT 5.00 COMMENT '性价比评分',
  -- 统计
  `order_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '接单数量',
  `review_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '评价数量',
  `favorite_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '收藏数量',
  `view_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '浏览数量',
  -- 状态
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序(数值越大越靠前)',
  `is_recommend` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否推荐:0-否,1-是',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态:0-禁用,1-启用',
  `audit_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '审核状态:0-待审核,1-已通过,2-已拒绝',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `delete_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_sn` (`sn`) USING BTREE,
  KEY `idx_user_id` (`user_id`) USING BTREE,
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_wecom_userid` (`wecom_userid`),
  KEY `idx_category_id` (`category_id`) USING BTREE,
  KEY `idx_status` (`status`) USING BTREE,
  KEY `idx_is_recommend` (`is_recommend`) USING BTREE,
  KEY `idx_rating` (`rating`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='工作人员表';

SET @wm_has_staff_long_detail := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_staff'
      AND COLUMN_NAME = 'long_detail'
);
SET @wm_staff_long_detail_sql := IF(
    @wm_has_staff_long_detail = 0,
    'ALTER TABLE `la_staff` ADD COLUMN `long_detail` LONGTEXT NULL COMMENT ''长图详情(JSON)'' AFTER `service_desc`',
    'SELECT 1'
);
PREPARE wm_staff_long_detail_stmt FROM @wm_staff_long_detail_sql;
EXECUTE wm_staff_long_detail_stmt;
DEALLOCATE PREPARE wm_staff_long_detail_stmt;

-- la_staff_banner
CREATE TABLE IF NOT EXISTS `la_staff_banner` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `staff_id` int(11) UNSIGNED NOT NULL COMMENT '人员ID',
  `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '类型：1=图片，2=视频',
  `file_url` varchar(500) NOT NULL DEFAULT '' COMMENT '文件地址',
  `cover_url` varchar(500) NOT NULL DEFAULT '' COMMENT '封面图地址（视频必填）',
  `is_autoplay` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '视频是否自动播放：0=否，1=是',
  `sort` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序（数字越小越靠前）',
  `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_staff_id` (`staff_id`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='人员轮播图表';

-- la_staff_work
CREATE TABLE IF NOT EXISTS `la_staff_work` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '作品ID',
  `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '工作人员ID',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '作品标题',
  `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '作品类型:1-图片,2-视频',
  `cover` varchar(255) NOT NULL DEFAULT '' COMMENT '封面图片',
  `images` text COMMENT '作品图片(JSON数组)',
  `video` varchar(255) NOT NULL DEFAULT '' COMMENT '作品视频',
  `description` text COMMENT '作品描述',
  `shoot_date` date NULL DEFAULT NULL COMMENT '拍摄日期',
  `location` varchar(100) NOT NULL DEFAULT '' COMMENT '拍摄地点',
  `view_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '浏览数',
  `like_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '点赞数',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `is_show` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否显示:0-否,1-是',
  `is_cover` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否封面:0-否,1-是',
  `audit_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '审核状态:0-待审核,1-已通过,2-已拒绝',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `delete_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_staff_id` (`staff_id`) USING BTREE,
  KEY `idx_audit_status` (`audit_status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='工作人员作品表';

-- la_staff_certificate
CREATE TABLE IF NOT EXISTS `la_staff_certificate` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '证书ID',
  `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '工作人员ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '证书名称',
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '证书图片',
  `issue_org` varchar(100) NOT NULL DEFAULT '' COMMENT '颁发机构',
  `issue_date` date NULL DEFAULT NULL COMMENT '颁发日期',
  `expire_date` date NULL DEFAULT NULL COMMENT '有效期至',
  `certificate_no` varchar(100) NOT NULL DEFAULT '' COMMENT '证书编号',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `audit_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '审核状态:0-待审核,1-已通过,2-已拒绝',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `delete_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_staff_id` (`staff_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='工作人员证书表';

-- la_staff_tag
CREATE TABLE IF NOT EXISTS `la_staff_tag` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '工作人员ID',
  `tag_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '标签ID',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_staff_tag` (`staff_id`, `tag_id`) USING BTREE,
  KEY `idx_tag_id` (`tag_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='工作人员标签关联表';

-- la_favorite
CREATE TABLE IF NOT EXISTS `la_favorite` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '工作人员ID',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_user_staff` (`user_id`, `staff_id`) USING BTREE,
  KEY `idx_staff_id` (`staff_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='收藏表';

-- la_schedule_rule
CREATE TABLE IF NOT EXISTS `la_schedule_rule` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `staff_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '工作人员ID（0=全局规则）',
    `advance_days` INT UNSIGNED NOT NULL DEFAULT 3 COMMENT '提前预约天数',
    `max_orders_per_day` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '单日最大接单数',
    `interval_hours` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单间隔时间（小时）',
    `work_start_time` VARCHAR(10) NOT NULL DEFAULT '09:00' COMMENT '工作开始时间',
    `work_end_time` VARCHAR(10) NOT NULL DEFAULT '18:00' COMMENT '工作结束时间',
    `rest_days` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '休息日（逗号分隔，0=周日,1=周一...）',
    `is_enabled` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否启用：0=否,1=是',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='档期规则表';

-- la_schedule
CREATE TABLE IF NOT EXISTS `la_schedule` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `staff_id` INT UNSIGNED NOT NULL COMMENT '工作人员ID',
    `schedule_date` DATE NOT NULL COMMENT '档期日期',
    `time_slot` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '时间段：0=全天,1=早礼,2=午宴,3=晚宴',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=不可用,1=可预约,2=已预约,3=已锁定,4=内部预留',
    `order_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `lock_type` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '锁定类型：0=正常,1=VIP锁定,2=内部预留',
    `lock_user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '锁定用户ID',
    `lock_expire_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '锁定到期时间',
    `lock_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '锁定/预留原因',
    `queue_lock_until` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '候补锁档截止时间',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '当日价格（0=使用默认价格）',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `version` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '乐观锁版本号',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_staff_date_slot` (`staff_id`, `schedule_date`, `time_slot`),
    KEY `idx_schedule_date` (`schedule_date`),
    KEY `idx_status` (`status`),
    KEY `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='档期表';

-- la_schedule_lock
CREATE TABLE IF NOT EXISTS `la_schedule_lock` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `schedule_id` INT UNSIGNED NOT NULL COMMENT '档期ID',
    `staff_id` INT UNSIGNED NOT NULL COMMENT '工作人员ID',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '锁定用户ID',
    `lock_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '锁定类型：1=VIP锁定,2=内部预留,3=临时锁定',
    `lock_start_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '锁定开始时间',
    `lock_end_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '锁定结束时间',
    `lock_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '锁定原因',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=已释放,1=锁定中,2=已转订单',
    `release_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '释放时间',
    `release_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '释放原因',
    `admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作管理员ID',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_schedule_id` (`schedule_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='档期锁定记录表';

-- la_schedule_share
CREATE TABLE IF NOT EXISTS `la_schedule_share` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `group_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '组合名称',
    `staff_ids` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '工作人员ID列表（逗号分隔）',
    `share_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '共享类型：1=档期同步,2=组合套餐',
    `discount_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT '组合折扣率（如95表示95折）',
    `is_enabled` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否启用：0=否,1=是',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='档期共享表';

-- la_calendar_event
CREATE TABLE IF NOT EXISTS `la_calendar_event` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `event_date` DATE NOT NULL COMMENT '日期',
    `lunar_date` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '农历日期',
    `is_lucky_day` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否吉日：0=否,1=是',
    `lucky_events` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '宜（如：结婚,订婚）',
    `unlucky_events` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '忌',
    `is_holiday` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否节假日：0=否,1=是',
    `holiday_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '节假日名称',
    `congestion_level` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '拥堵等级：0=未知,1=低,2=中,3=高',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_event_date` (`event_date`),
    KEY `idx_is_lucky_day` (`is_lucky_day`),
    KEY `idx_is_holiday` (`is_holiday`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='黄历/吉日表';

-- la_waitlist
CREATE TABLE IF NOT EXISTS `la_waitlist` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `staff_id` INT UNSIGNED NOT NULL COMMENT '工作人员ID',
    `schedule_date` DATE NOT NULL COMMENT '预约日期',
    `time_slot` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '时间段',
    `package_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '套餐ID',
    `batch_no` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '候补批次号',
    `notify_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '通知状态：0=未通知,1=已通知,2=已下单,3=已过期',
    `notify_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '通知时间',
    `expire_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '过期时间',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_schedule_date` (`schedule_date`),
    KEY `idx_notify_status` (`notify_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='候补订单表';

-- la_package_booking
CREATE TABLE IF NOT EXISTS `la_package_booking` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT '主键ID',
  `package_id` INT UNSIGNED NOT NULL COMMENT '套餐ID',
  `staff_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID（0=不限人员）',
  `booking_date` DATE NOT NULL COMMENT '预订日期',
  `time_slot` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '时间段：0=全天,1=早礼,2=午宴,3=晚宴',
  `start_time` VARCHAR(10) DEFAULT NULL COMMENT '开始时间 HH:mm',
  `end_time` VARCHAR(10) DEFAULT NULL COMMENT '结束时间 HH:mm',
  `order_id` INT UNSIGNED DEFAULT NULL COMMENT '关联订单ID',
  `order_item_id` INT UNSIGNED DEFAULT NULL COMMENT '关联订单项ID',
  `user_id` INT UNSIGNED DEFAULT NULL COMMENT '预订用户ID',
  `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=已释放,1=临时锁定,2=已确认',
  `lock_expire_time` INT UNSIGNED DEFAULT NULL COMMENT '临时锁定过期时间戳',
  `version` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '乐观锁版本号',
  `create_time` INT UNSIGNED NOT NULL COMMENT '创建时间',
  `update_time` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
  UNIQUE KEY `uk_staff_package_date_slot` (`staff_id`, `package_id`, `booking_date`, `time_slot`),
  INDEX `idx_order_id` (`order_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_lock_expire` (`lock_expire_time`),
  INDEX `idx_booking_date` (`booking_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='套餐预订记录表（用于单日唯一限制）';

-- la_order
CREATE TABLE IF NOT EXISTS `la_order` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `order_sn` VARCHAR(32) NOT NULL COMMENT '订单编号',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `order_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '订单类型：1=普通订单,2=套餐订单,3=组合订单',
    `order_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单状态：0=待确认,1=待支付,2=待服务,3=服务中,4=已完成,5=已评价,6=已取消,7=已暂停,8=已退款,9=用户已删除',
    `pay_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付状态：0=未支付,1=已支付,2=部分退款,3=全额退款',
    -- 金额
    `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '订单总额',
    `addon_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '附加服务金额',
    `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '优惠金额',
    `pay_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '实付金额',
    `paid_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '已支付金额',
    `deposit_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '定金金额',
    `deposit_paid` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '定金是否支付：0=否,1=是',
    `balance_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '尾款金额',
    `balance_paid` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '尾款是否支付：0=否,1=是',
    `deposit_mode_enabled` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否开启定金模式快照',
    `deposit_type_snapshot` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '定金类型快照',
    `deposit_value_snapshot` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '定金值快照',
    `deposit_remark_snapshot` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '定金说明快照',
    -- 支付
    `pay_type` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付方式：0=未支付,1=微信,2=支付宝,3=余额,4=线下,5=组合支付',
    `pay_voucher` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '线下支付凭证',
    `pay_voucher_status` TINYINT UNSIGNED DEFAULT NULL COMMENT '凭证审核状态：0=待审核,1=已通过,2=已拒绝',
    `pay_voucher_audit_admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '凭证审核管理员ID',
    `pay_voucher_audit_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '凭证审核时间',
    `pay_voucher_audit_remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '凭证审核备注',
    `pay_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付时间',
    `pay_deadline_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付截止时间',
    -- 服务信息
    `service_date` DATE DEFAULT NULL COMMENT '服务日期',
    `service_time_slot` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务时间段',
    `service_address` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '服务地址',
    `service_province_code` VARCHAR(12) NOT NULL DEFAULT '' COMMENT '服务省编码',
    `service_province` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '服务省',
    `service_city_code` VARCHAR(12) NOT NULL DEFAULT '' COMMENT '服务市编码',
    `service_city` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '服务市',
    `service_district_code` VARCHAR(12) NOT NULL DEFAULT '' COMMENT '服务区县编码',
    `service_district` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '服务区县',
    `contact_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '联系人姓名',
    `contact_mobile` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '联系人电话',
    -- 备注
    `user_remark` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '用户备注',
    `admin_remark` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '管理员备注',
    -- 取消/完成
    `cancel_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '取消原因',
    `cancel_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '取消时间',
    `complete_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '完成时间',
    `is_reviewed` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否已评价：0=否,1=是',
    -- 暂停/变更（来自 004）
    `is_paused` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否暂停：0=否,1=是',
    `pause_id` INT UNSIGNED DEFAULT 0 COMMENT '关联暂停记录ID',
    `has_changed` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否有变更记录：0=否,1=是',
    `change_count` INT UNSIGNED DEFAULT 0 COMMENT '变更次数',
    -- 来源
    `source` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '订单来源：1=小程序,2=H5,3=后台',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_order_sn` (`order_sn`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_order_status` (`order_status`),
    KEY `idx_pay_status` (`pay_status`),
    KEY `idx_service_date` (`service_date`),
    KEY `idx_pay_deadline_time` (`order_status`, `pay_deadline_time`),
    KEY `idx_create_time` (`create_time`),
    KEY `idx_is_paused` (`is_paused`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单表';

-- la_order_item
CREATE TABLE IF NOT EXISTS `la_order_item` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `staff_id` INT UNSIGNED NOT NULL COMMENT '工作人员ID',
    `package_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '套餐ID',
    `schedule_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '档期ID',
    `service_date` DATE NOT NULL COMMENT '服务日期',
    `time_slot` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '时间段',
    `staff_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '人员姓名(冗余)',
    `package_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '套餐名称(冗余)',
    `package_description` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '套餐说明快照',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '单价',
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '数量',
    `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '小计',
    `item_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '订单项类型：1=主服务，2=预约附加项，3=关联服务人员',
    `item_meta` TEXT COMMENT '订单项扩展信息(JSON)',
    `item_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '项状态：0=待服务,1=服务中,2=已完成,3=已取消',
    `confirm_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '确认状态：0=待确认,1=已确认',
    -- 变更相关（来自 004）
    `is_changed` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否变更过：0=否,1=是',
    `change_id` INT UNSIGNED DEFAULT 0 COMMENT '关联变更记录ID',
    `original_staff_id` INT UNSIGNED DEFAULT 0 COMMENT '原工作人员ID(换人前)',
    `original_price` DECIMAL(10,2) DEFAULT 0.00 COMMENT '原价格(换人前)',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_service_date` (`service_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单项表';

-- la_order_item_addon
CREATE TABLE IF NOT EXISTS `la_order_item_addon` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `order_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单ID',
    `order_item_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '主订单项ID',
    `addon_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '附加服务ID',
    `addon_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '附加服务快照名称',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '快照单价',
    `quantity` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '数量，固定为1',
    `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '小计',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：1=生效中，2=已移除',
    `create_source` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '创建来源：1=下单，2=变更',
    `create_change_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建变更单ID',
    `remove_change_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '移除变更单ID',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_order_item_id` (`order_item_id`),
    KEY `idx_addon_id` (`addon_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单附加服务快照表';

-- la_order_log
CREATE TABLE IF NOT EXISTS `la_order_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `operator_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '操作者类型：1=用户,2=管理员,3=系统',
    `operator_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作者ID',
    `action` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '操作动作',
    `before_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作前状态',
    `after_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作后状态',
    `content` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '日志内容',
    `ip` VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'IP地址',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单日志表';

-- la_payment
CREATE TABLE IF NOT EXISTS `la_payment` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `payment_sn` VARCHAR(32) NOT NULL COMMENT '支付流水号',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `order_sn` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '订单编号',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `pay_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '支付类型：1=定金,2=尾款,3=全款',
    `pay_way` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '支付方式：1=微信,2=支付宝,3=余额,4=线下',
    `pay_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '支付金额',
    `pay_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付状态：0=待支付,1=已支付,2=已退款,3=支付失败',
    `transaction_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '第三方交易号',
    `pay_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付时间',
    `expire_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '过期时间',
    `callback_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '回调时间',
    `callback_data` TEXT COMMENT '回调数据',
    `refund_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '退款金额',
    `refund_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '退款时间',
    `refund_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '退款原因',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_payment_sn` (`payment_sn`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_pay_status` (`pay_status`),
    KEY `idx_transaction_id` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='支付记录表';

-- la_refund
CREATE TABLE IF NOT EXISTS `la_refund` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `refund_sn` VARCHAR(32) NOT NULL COMMENT '退款编号',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `payment_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付记录ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `refund_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '退款类型：1=用户申请,2=管理员操作,3=系统自动',
    `refund_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '退款金额',
    `actual_refund_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '实际退款金额',
    `refund_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '退款原因',
    `refund_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '退款状态：0=待审核,1=审核通过,2=退款中,3=已退款,4=已拒绝,5=退款失败',
    `source_order_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '发起退款前订单状态',
    `source_pay_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '发起退款前支付状态',
    `audit_admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核管理员ID',
    `audit_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核时间',
    `audit_remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '审核备注',
    `refund_transaction_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '退款交易号',
    `refund_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '实际退款时间',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_refund_sn` (`refund_sn`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_refund_status` (`refund_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='退款记录表';

CREATE TABLE IF NOT EXISTS `la_refund_item` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `refund_id` INT UNSIGNED NOT NULL COMMENT '退款单ID',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `payment_id` INT UNSIGNED NOT NULL COMMENT '支付记录ID',
    `pay_way` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付方式：1=微信,2=支付宝,3=余额,4=线下',
    `pay_terminal` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付终端',
    `refund_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '子项退款金额',
    `refund_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '退款状态：0=待执行,1=处理中,2=已完成,3=失败',
    `out_refund_no` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '商户退款单号',
    `third_refund_no` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '第三方退款单号',
    `refund_msg` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '退款处理说明',
    `refund_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '退款完成时间',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_out_refund_no` (`out_refund_no`),
    KEY `idx_refund_id` (`refund_id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_payment_id` (`payment_id`),
    KEY `idx_refund_status` (`refund_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='退款子项表';

-- la_order_change
CREATE TABLE IF NOT EXISTS `la_order_change` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `change_sn` VARCHAR(32) NOT NULL COMMENT '变更单号',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `order_sn` VARCHAR(32) NOT NULL COMMENT '订单编号(冗余)',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `change_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '变更类型：1=改期,2=换人,3=加项,4=附加服务变更',
    `addon_action` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '附加服务动作：1=新增,2=移除',
    `change_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '变更状态：0=待审核,1=审核通过,2=审核拒绝,3=已执行,4=已取消',
    -- 改期
    `old_service_date` DATE DEFAULT NULL COMMENT '原服务日期',
    `new_service_date` DATE DEFAULT NULL COMMENT '新服务日期',
    `old_time_slot` TINYINT UNSIGNED DEFAULT 0 COMMENT '原时间段',
    `new_time_slot` TINYINT UNSIGNED DEFAULT 0 COMMENT '新时间段',
    -- 换人
    `order_item_id` INT UNSIGNED DEFAULT 0 COMMENT '订单项ID',
    `old_staff_id` INT UNSIGNED DEFAULT 0 COMMENT '原工作人员ID',
    `new_staff_id` INT UNSIGNED DEFAULT 0 COMMENT '新工作人员ID',
    `old_staff_name` VARCHAR(50) DEFAULT '' COMMENT '原人员姓名(冗余)',
    `new_staff_name` VARCHAR(50) DEFAULT '' COMMENT '新人员姓名(冗余)',
    `old_schedule_id` INT UNSIGNED DEFAULT 0 COMMENT '原档期ID',
    `new_schedule_id` INT UNSIGNED DEFAULT 0 COMMENT '新档期ID',
    `old_price` DECIMAL(10,2) DEFAULT 0.00 COMMENT '原价格',
    `new_price` DECIMAL(10,2) DEFAULT 0.00 COMMENT '新价格',
    `price_diff` DECIMAL(10,2) DEFAULT 0.00 COMMENT '差价(正数补付,负数退款)',
    `diff_paid` TINYINT UNSIGNED DEFAULT 0 COMMENT '差价是否已处理：0=否,1=是',
    `diff_payment_id` INT UNSIGNED DEFAULT 0 COMMENT '差价支付记录ID',
    `diff_refund_id` INT UNSIGNED DEFAULT 0 COMMENT '差价退款记录ID',
    -- 加项
    `add_staff_id` INT UNSIGNED DEFAULT 0 COMMENT '新增工作人员ID',
    `add_package_id` INT UNSIGNED DEFAULT 0 COMMENT '新增套餐ID',
    `add_staff_name` VARCHAR(50) DEFAULT '' COMMENT '新增人员姓名',
    `add_package_name` VARCHAR(100) DEFAULT '' COMMENT '新增套餐名称',
    `add_service_date` DATE DEFAULT NULL COMMENT '新增服务日期',
    `add_time_slot` TINYINT UNSIGNED DEFAULT 0 COMMENT '新增时间段',
    `add_price` DECIMAL(10,2) DEFAULT 0.00 COMMENT '新增价格',
    `add_schedule_id` INT UNSIGNED DEFAULT 0 COMMENT '新增档期ID',
    `add_order_item_id` INT UNSIGNED DEFAULT 0 COMMENT '新增订单项ID',
    -- 审核
    `apply_reason` VARCHAR(500) DEFAULT '' COMMENT '申请原因',
    `user_remark` VARCHAR(500) DEFAULT '' COMMENT '用户备注',
    `audit_admin_id` INT UNSIGNED DEFAULT 0 COMMENT '审核管理员ID',
    `audit_time` INT UNSIGNED DEFAULT 0 COMMENT '审核时间',
    `audit_remark` VARCHAR(500) DEFAULT '' COMMENT '审核备注',
    `reject_reason` VARCHAR(500) DEFAULT '' COMMENT '拒绝原因',
    -- 执行
    `execute_time` INT UNSIGNED DEFAULT 0 COMMENT '执行时间',
    `execute_admin_id` INT UNSIGNED DEFAULT 0 COMMENT '执行管理员ID',
    `attach_images` TEXT COMMENT '附件图片(JSON数组)',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_change_sn` (`change_sn`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_change_type` (`change_type`),
    KEY `idx_change_status` (`change_status`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单变更申请表';

-- la_order_change_addon
CREATE TABLE IF NOT EXISTS `la_order_change_addon` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `change_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '变更单ID',
    `order_item_addon_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单附加服务快照ID',
    `addon_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '附加服务ID',
    `addon_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '附加服务名称',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '单价',
    `quantity` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '数量',
    `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '小计',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_change_id` (`change_id`),
    KEY `idx_order_item_addon_id` (`order_item_addon_id`),
    KEY `idx_addon_id` (`addon_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单变更附加服务明细表';

-- la_order_pause
CREATE TABLE IF NOT EXISTS `la_order_pause` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `pause_sn` VARCHAR(32) NOT NULL COMMENT '暂停单号',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `order_sn` VARCHAR(32) NOT NULL COMMENT '订单编号(冗余)',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `pause_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '暂停状态：0=待审核,1=暂停中,2=已恢复,3=已拒绝,4=已取消',
    `pause_type` TINYINT UNSIGNED NOT NULL COMMENT '暂停类型：1=疫情,2=突发事件,3=个人原因,4=其他',
    `pause_reason` VARCHAR(500) NOT NULL COMMENT '暂停原因',
    `pause_start_date` DATE DEFAULT NULL COMMENT '暂停开始日期',
    `pause_end_date` DATE DEFAULT NULL COMMENT '暂停结束日期(预计)',
    `pause_days` INT UNSIGNED DEFAULT 0 COMMENT '暂停天数',
    `original_service_date` DATE DEFAULT NULL COMMENT '原服务日期',
    `audit_admin_id` INT UNSIGNED DEFAULT 0 COMMENT '审核管理员ID',
    `audit_time` INT UNSIGNED DEFAULT 0 COMMENT '审核时间',
    `audit_remark` VARCHAR(500) DEFAULT '' COMMENT '审核备注',
    `reject_reason` VARCHAR(500) DEFAULT '' COMMENT '拒绝原因',
    `resume_time` INT UNSIGNED DEFAULT 0 COMMENT '恢复时间',
    `resume_admin_id` INT UNSIGNED DEFAULT 0 COMMENT '恢复操作管理员ID',
    `resume_remark` VARCHAR(500) DEFAULT '' COMMENT '恢复备注',
    `actual_pause_days` INT UNSIGNED DEFAULT 0 COMMENT '实际暂停天数',
    `new_service_date` DATE DEFAULT NULL COMMENT '恢复后新服务日期',
    `remind_before_days` INT UNSIGNED DEFAULT 3 COMMENT '提前提醒天数',
    `reminded` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否已提醒：0=否,1=是',
    `remind_time` INT UNSIGNED DEFAULT 0 COMMENT '提醒时间',
    `proof_images` TEXT COMMENT '证明材料图片(JSON数组)',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_pause_sn` (`pause_sn`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_pause_status` (`pause_status`),
    KEY `idx_pause_end_date` (`pause_end_date`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单暂停表';

-- la_order_change_log
CREATE TABLE IF NOT EXISTS `la_order_change_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `related_type` TINYINT UNSIGNED NOT NULL COMMENT '关联类型：1=变更,2=转让,3=暂停',
    `related_id` INT UNSIGNED NOT NULL COMMENT '关联记录ID',
    `operator_type` TINYINT UNSIGNED NOT NULL COMMENT '操作者类型：1=用户,2=管理员,3=系统',
    `operator_id` INT UNSIGNED DEFAULT 0 COMMENT '操作者ID',
    `operator_name` VARCHAR(50) DEFAULT '' COMMENT '操作者名称',
    `action` VARCHAR(50) NOT NULL COMMENT '操作动作',
    `before_status` TINYINT UNSIGNED DEFAULT 0 COMMENT '操作前状态',
    `after_status` TINYINT UNSIGNED DEFAULT 0 COMMENT '操作后状态',
    `before_data` TEXT COMMENT '变更前数据(JSON)',
    `after_data` TEXT COMMENT '变更后数据(JSON)',
    `content` VARCHAR(500) DEFAULT '' COMMENT '日志内容',
    `ip` VARCHAR(50) DEFAULT '' COMMENT 'IP地址',
    `user_agent` VARCHAR(500) DEFAULT '' COMMENT '用户代理',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_related` (`related_type`, `related_id`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单变更日志表';

-- la_dynamic
CREATE TABLE IF NOT EXISTS `la_dynamic` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '发布者ID',
    `user_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '发布者类型：1=用户,2=工作人员,3=官方',
    `staff_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '工作人员ID(user_type=2时)',
    `dynamic_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '动态类型：1=图文,2=视频,3=案例分享,4=活动',
    `title` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '标题',
    `content` TEXT COMMENT '内容',
    `images` TEXT COMMENT '图片列表(JSON)',
    `video_url` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '视频地址',
    `video_cover` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '视频封面',
    `location` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '位置信息',
    `latitude` DECIMAL(10,7) NOT NULL DEFAULT 0 COMMENT '纬度',
    `longitude` DECIMAL(10,7) NOT NULL DEFAULT 0 COMMENT '经度',
    `tags` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '标签(逗号分隔)',
    `allow_comment` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否允许评论：0=禁止，1=允许',
    `order_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID(晒单)',
    `view_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '浏览量',
    `like_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '点赞数',
    `comment_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '评论数',
    `share_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '分享数',
    `collect_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '收藏数',
    `is_top` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否置顶：0=否,1=是',
    `is_hot` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否热门：0=否,1=是',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=待审核,1=已发布,2=已下架,3=审核拒绝',
    `audit_admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核管理员ID',
    `audit_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核时间',
    `audit_remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '审核备注',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_dynamic_type` (`dynamic_type`),
    KEY `idx_status` (`status`),
    KEY `idx_create_time` (`create_time`),
    KEY `idx_is_top_hot` (`is_top`, `is_hot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='动态表';

-- la_dynamic_comment
CREATE TABLE IF NOT EXISTS `la_dynamic_comment` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `dynamic_id` INT UNSIGNED NOT NULL COMMENT '动态ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '评论者ID',
    `parent_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '父评论ID(0=一级评论)',
    `reply_user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '回复的用户ID',
    `content` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '评论内容',
    `images` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '评论图片(JSON)',
    `like_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '点赞数',
    `reply_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '回复数',
    `is_top` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否置顶：0=否,1=是',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=待审核,1=正常,2=已删除,3=审核拒绝',
    `review_status` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '审核状态：0=待审核，1=已通过，2=已拒绝',
    `review_admin_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核管理员ID',
    `review_time` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核时间',
    `review_remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '审核备注（拒绝原因）',
    `ip` VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'IP地址',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_dynamic_id` (`dynamic_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_parent_id` (`parent_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='动态评论表';

-- la_dynamic_like
CREATE TABLE IF NOT EXISTS `la_dynamic_like` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `target_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '目标类型：1=动态,2=评论',
    `target_id` INT UNSIGNED NOT NULL COMMENT '目标ID',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_target` (`user_id`, `target_type`, `target_id`),
    KEY `idx_target` (`target_type`, `target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='动态点赞表';

-- la_dynamic_collect
CREATE TABLE IF NOT EXISTS `la_dynamic_collect` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `dynamic_id` INT UNSIGNED NOT NULL COMMENT '动态ID',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_dynamic` (`user_id`, `dynamic_id`),
    KEY `idx_dynamic_id` (`dynamic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='动态收藏表';

-- la_follow
CREATE TABLE IF NOT EXISTS `la_follow` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '关注者ID',
    `follow_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '关注类型：1=用户,2=工作人员',
    `follow_id` INT UNSIGNED NOT NULL COMMENT '被关注者ID',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_follow` (`user_id`, `follow_type`, `follow_id`),
    KEY `idx_follow` (`follow_type`, `follow_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='关注表';

-- la_notification
CREATE TABLE IF NOT EXISTS `la_notification` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '接收者ID',
    `sender_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '发送者ID(0=系统)',
    `notify_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '通知类型：1=系统通知,2=订单通知,3=互动通知,4=活动通知',
    `title` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '标题',
    `content` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '内容',
    `target_type` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '目标类型(order/dynamic/comment等)',
    `target_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '目标ID',
    `is_read` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否已读：0=否,1=是',
    `read_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '阅读时间',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_notify_type` (`notify_type`),
    KEY `idx_is_read` (`is_read`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='消息通知表';

-- la_subscribe_message_template
CREATE TABLE IF NOT EXISTS `la_subscribe_message_template` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `template_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '微信模板ID',
    `name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '模板名称',
    `title` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '模板标题',
    `scene` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '使用场景',
    `content` TEXT COMMENT '模板内容(JSON格式，存储字段配置)',
    `example` TEXT COMMENT '示例内容',
    `keywords` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '关键词列表(逗号分隔)',
    `category_id` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '模板类目ID',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0-禁用，1-启用',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序(越大越靠前)',
    `remark` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '备注说明',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_template_id` (`template_id`),
    KEY `idx_scene` (`scene`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订阅消息模板表';

-- la_user_subscribe
CREATE TABLE IF NOT EXISTS `la_user_subscribe` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `template_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '微信模板ID',
    `scene` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '使用场景',
    `accept_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '累计授权次数',
    `reject_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '累计拒绝次数',
    `last_accept_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后授权时间',
    `last_reject_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后拒绝时间',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '订阅状态：0-已拒绝，1-已订阅，2-永久订阅',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_template` (`user_id`, `template_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_template_id` (`template_id`),
    KEY `idx_scene` (`scene`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户订阅记录表';

-- la_subscribe_message_log
CREATE TABLE IF NOT EXISTS `la_subscribe_message_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `openid` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '用户OpenID',
    `template_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '微信模板ID',
    `scene` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '使用场景',
    `business_type` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '业务类型',
    `business_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '业务ID',
    `content` TEXT COMMENT '发送内容(JSON格式)',
    `page` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '跳转页面路径',
    `miniprogram_state` VARCHAR(20) NOT NULL DEFAULT 'formal' COMMENT '小程序状态',
    `send_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '发送状态：0-待发送，1-发送成功，2-发送失败',
    `error_code` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '错误码',
    `error_msg` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '错误信息',
    `request_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '请求ID',
    `send_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '发送时间',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_template_id` (`template_id`),
    KEY `idx_scene` (`scene`),
    KEY `idx_business` (`business_type`, `business_id`),
    KEY `idx_send_status` (`send_status`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订阅消息发送日志表';

-- la_subscribe_message_scene
CREATE TABLE IF NOT EXISTS `la_subscribe_message_scene` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `scene` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '场景标识',
    `name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '场景名称',
    `description` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '场景描述',
    `template_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '关联模板ID',
    `trigger_event` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '触发事件',
    `data_mapping` TEXT COMMENT '数据映射配置(JSON格式)',
    `page_path` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '默认跳转页面',
    `is_auto` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否自动发送：0-手动，1-自动',
    `delay_seconds` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '延迟发送秒数',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0-禁用，1-启用',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_scene` (`scene`),
    KEY `idx_template_id` (`template_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订阅消息场景配置表';

SET @add_subscribe_scene_template_id_sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_subscribe_message_scene'
          AND COLUMN_NAME = 'template_id'
    ),
    'SELECT 1',
    'ALTER TABLE `la_subscribe_message_scene` ADD COLUMN `template_id` VARCHAR(64) NOT NULL DEFAULT '''' COMMENT ''关联模板ID'' AFTER `description`'
);
PREPARE stmt FROM @add_subscribe_scene_template_id_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_subscribe_scene_data_mapping_sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_subscribe_message_scene'
          AND COLUMN_NAME = 'data_mapping'
    ),
    'SELECT 1',
    'ALTER TABLE `la_subscribe_message_scene` ADD COLUMN `data_mapping` TEXT COMMENT ''数据映射配置(JSON格式)'' AFTER `trigger_event`'
);
PREPARE stmt FROM @add_subscribe_scene_data_mapping_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_subscribe_scene_delay_seconds_sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_subscribe_message_scene'
          AND COLUMN_NAME = 'delay_seconds'
    ),
    'SELECT 1',
    'ALTER TABLE `la_subscribe_message_scene` ADD COLUMN `delay_seconds` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT ''延迟发送秒数'' AFTER `is_auto`'
);
PREPARE stmt FROM @add_subscribe_scene_delay_seconds_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_subscribe_scene_sort_sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_subscribe_message_scene'
          AND COLUMN_NAME = 'sort'
    ),
    'SELECT 1',
    'ALTER TABLE `la_subscribe_message_scene` ADD COLUMN `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT ''排序'' AFTER `status`'
);
PREPARE stmt FROM @add_subscribe_scene_sort_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- la_review
CREATE TABLE IF NOT EXISTS `la_review` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '评价ID',
    `order_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单ID',
    `order_item_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单项ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID',
    `score` tinyint(1) UNSIGNED NOT NULL DEFAULT 5 COMMENT '综合评分 1-5星',
    `score_service` tinyint(1) UNSIGNED NOT NULL DEFAULT 5 COMMENT '服务态度评分',
    `score_professional` tinyint(1) UNSIGNED NOT NULL DEFAULT 5 COMMENT '专业水平评分',
    `score_punctual` tinyint(1) UNSIGNED NOT NULL DEFAULT 5 COMMENT '时间守约评分',
    `score_effect` tinyint(1) UNSIGNED NOT NULL DEFAULT 5 COMMENT '整体效果评分',
    `content` text COMMENT '评价内容',
    `images` text COMMENT '评价图片 JSON数组',
    `video` varchar(500) NOT NULL DEFAULT '' COMMENT '评价视频URL',
    `video_cover` varchar(500) NOT NULL DEFAULT '' COMMENT '视频封面图URL',
    `is_anonymous` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否匿名',
    `is_top` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否置顶',
    `top_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '置顶时间',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态 0待审核 1已通过 2已拒绝',
    `reject_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '拒绝原因',
    `review_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '评价类型 1文字 2图文 3视频',
    `reward_points` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '奖励积分',
    `reward_grant_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '评价奖励发放时间',
    `is_rewarded` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否已发放奖励',
    `like_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '点赞数',
    `reply_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回复数',
    `is_show` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否显示',
    `admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核人ID',
    `audit_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核时间',
    `service_date` date DEFAULT NULL COMMENT '服务日期（冗余）',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` int(11) UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_status` (`status`),
    KEY `idx_score` (`score`),
    KEY `idx_is_top` (`is_top`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='评价主表';

-- la_review_tag
CREATE TABLE IF NOT EXISTS `la_review_tag` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '标签ID',
    `name` varchar(50) NOT NULL DEFAULT '' COMMENT '标签名称',
    `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '标签类型 1好评 2中评 3差评',
    `icon` varchar(255) NOT NULL DEFAULT '' COMMENT '标签图标',
    `color` varchar(20) NOT NULL DEFAULT '' COMMENT '标签颜色',
    `sort` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
    `use_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '使用次数',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态 0禁用 1启用',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` int(11) UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_type` (`type`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='评价标签表';

-- la_review_tag_relation
CREATE TABLE IF NOT EXISTS `la_review_tag_relation` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `review_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '评价ID',
    `tag_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '标签ID',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_review_tag` (`review_id`, `tag_id`),
    KEY `idx_tag_id` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='评价标签关联表';

-- la_review_reply
CREATE TABLE IF NOT EXISTS `la_review_reply` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '回复ID',
    `review_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '评价ID',
    `parent_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '父回复ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '管理员ID',
    `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID',
    `reply_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '回复类型 1用户追评 2商家回复 3人员回复',
    `content` text COMMENT '回复内容',
    `images` text COMMENT '回复图片 JSON数组',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态 0待审核 1已通过 2已拒绝',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` int(11) UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_review_id` (`review_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_reply_type` (`reply_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='评价回复表';

-- la_review_like
CREATE TABLE IF NOT EXISTS `la_review_like` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `review_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '评价ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_review_user` (`review_id`, `user_id`),
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='评价点赞表';

-- la_review_appeal
CREATE TABLE IF NOT EXISTS `la_review_appeal` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '申诉ID',
    `review_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '评价ID',
    `appeal_user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '申诉人ID（用户）',
    `appeal_staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '申诉人ID（服务人员）',
    `appeal_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '申诉类型 1恶意差评 2虚假评价 3侵犯隐私 4其他',
    `appeal_reason` text COMMENT '申诉原因',
    `evidence_images` text COMMENT '证据图片 JSON数组',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态 0待处理 1已通过 2已驳回',
    `handle_admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理人ID',
    `handle_result` text COMMENT '处理结果',
    `handle_action` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理动作 0无 1删除评价 2隐藏评价 3警告用户',
    `handle_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理时间',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_review_id` (`review_id`),
    KEY `idx_status` (`status`),
    KEY `idx_appeal_user_id` (`appeal_user_id`),
    KEY `idx_appeal_staff_id` (`appeal_staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='评价申诉表';

-- la_review_reward_config
CREATE TABLE IF NOT EXISTS `la_review_reward_config` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `reward_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '奖励类型 1文字评价 2图文评价 3视频评价',
    `reward_points` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '奖励积分',
    `min_content_length` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最少字数要求',
    `min_images` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最少图片数量',
    `min_video_duration` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最短视频时长（秒）',
    `extra_points_for_good` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '好评额外奖励积分',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态 0禁用 1启用',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_reward_type` (`reward_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='评价奖励配置表';

-- la_review_share_reward
CREATE TABLE IF NOT EXISTS `la_review_share_reward` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `review_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '评价ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `share_platform` varchar(50) NOT NULL DEFAULT '' COMMENT '分享平台',
    `share_url` varchar(500) NOT NULL DEFAULT '' COMMENT '分享链接',
    `reward_points` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '奖励积分',
    `reward_grant_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '晒单奖励发放时间',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态 0待审核 1已通过 2已拒绝',
    `verify_image` varchar(500) NOT NULL DEFAULT '' COMMENT '分享截图凭证',
    `admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核人ID',
    `audit_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核时间',
    `audit_remark` varchar(255) NOT NULL DEFAULT '' COMMENT '审核备注',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_review_id` (`review_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='晒单奖励记录表';

-- la_staff_review_stats
CREATE TABLE IF NOT EXISTS `la_staff_review_stats` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID',
    `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '总评价数',
    `good_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '好评数（4-5星）',
    `medium_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '中评数（3星）',
    `bad_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '差评数（1-2星）',
    `image_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '有图评价数',
    `video_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '有视频评价数',
    `avg_score` decimal(3,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '平均综合评分',
    `avg_score_service` decimal(3,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '平均服务态度评分',
    `avg_score_professional` decimal(3,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '平均专业水平评分',
    `avg_score_punctual` decimal(3,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '平均时间守约评分',
    `avg_score_effect` decimal(3,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '平均整体效果评分',
    `good_rate` decimal(5,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '好评率',
    `reply_rate` decimal(5,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '回复率',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='服务人员评价统计表';

-- la_sensitive_word
CREATE TABLE IF NOT EXISTS `la_sensitive_word` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `word` varchar(100) NOT NULL DEFAULT '' COMMENT '敏感词',
    `replace_word` varchar(100) NOT NULL DEFAULT '***' COMMENT '替换词',
    `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '类型 1广告 2违法 3政治 4色情 5其他',
    `level` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '级别 1警告 2禁止',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态 0禁用 1启用',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_word` (`word`),
    KEY `idx_type` (`type`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='敏感词表';

-- la_financial_flow
CREATE TABLE IF NOT EXISTS `la_financial_flow` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `flow_sn` VARCHAR(32) NOT NULL COMMENT '流水编号',
    `flow_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '流水类型：1=收入,2=支出,3=退款,4=分账,5=提现',
    `biz_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '业务类型：1=订单支付,2=订单退款,3=人员结算,4=平台抽成,5=其他',
    `biz_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '业务ID',
    `biz_sn` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '业务编号',
    `order_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联用户ID',
    `staff_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联服务人员ID',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '金额(正值)',
    `direction` TINYINT NOT NULL DEFAULT 1 COMMENT '方向：1=收入(+),-1=支出(-)',
    `balance_before` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '变动前余额',
    `balance_after` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '变动后余额',
    `pay_way` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付方式：0=系统,1=微信,2=支付宝,3=余额,4=线下',
    `transaction_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '第三方交易号',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `operator_type` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作者类型：0=系统,1=用户,2=管理员',
    `operator_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作者ID',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_flow_sn` (`flow_sn`),
    KEY `idx_flow_type` (`flow_type`),
    KEY `idx_biz_type` (`biz_type`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='资金流水表';

-- la_cost_record
CREATE TABLE IF NOT EXISTS `la_cost_record` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `cost_sn` VARCHAR(32) NOT NULL COMMENT '成本编号',
    `order_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `order_item_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单项ID',
    `staff_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联服务人员ID',
    `cost_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '成本类型：1=人工,2=物料,3=交通,4=设备,5=其他',
    `cost_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '成本名称',
    `cost_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '成本金额',
    `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '单价',
    `quantity` DECIMAL(10,2) NOT NULL DEFAULT 1.00 COMMENT '数量',
    `service_date` DATE DEFAULT NULL COMMENT '服务日期',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=待确认,1=已确认,2=已取消',
    `confirm_admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '确认管理员ID',
    `confirm_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '确认时间',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_cost_sn` (`cost_sn`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_cost_type` (`cost_type`),
    KEY `idx_service_date` (`service_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='成本记录表';

-- la_staff_settlement
CREATE TABLE IF NOT EXISTS `la_staff_settlement` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `settlement_sn` VARCHAR(32) NOT NULL COMMENT '结算编号',
    `batch_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '结算批次ID',
    `staff_id` INT UNSIGNED NOT NULL COMMENT '服务人员ID',
    `order_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单ID',
    `order_item_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单项ID',
    `service_date` DATE DEFAULT NULL COMMENT '服务日期',
    `order_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '订单金额',
    `settlement_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT '结算比例(%)',
    `settlement_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '结算金额',
    `platform_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '平台抽成',
    `cost_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '扣除成本',
    `actual_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '实际结算金额',
    `settlement_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '结算类型：1=自动,2=手动',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态：0=待结算,1=已结算,2=已取消,3=失败',
    `settle_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '结算时间',
    `settle_way` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '结算方式：1=余额,2=银行卡,3=微信,4=支付宝',
    `transaction_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '交易号',
    `fail_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '失败原因',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_settlement_sn` (`settlement_sn`),
    KEY `idx_batch_id` (`batch_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_status` (`status`),
    KEY `idx_service_date` (`service_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务人员结算表';

-- la_settlement_batch
CREATE TABLE IF NOT EXISTS `la_settlement_batch` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `batch_sn` VARCHAR(32) NOT NULL COMMENT '批次编号',
    `batch_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '批次名称',
    `settle_start_date` DATE NOT NULL COMMENT '结算开始日期',
    `settle_end_date` DATE NOT NULL COMMENT '结算结束日期',
    `total_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '结算总笔数',
    `success_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '成功笔数',
    `fail_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '失败笔数',
    `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '结算总金额',
    `success_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '成功金额',
    `fail_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '失败金额',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态：0=待审核,1=审核通过,2=处理中,3=已完成,4=已取消',
    `audit_admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核管理员ID',
    `audit_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核时间',
    `audit_remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '审核备注',
    `execute_admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '执行管理员ID',
    `execute_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '执行时间',
    `complete_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '完成时间',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_batch_sn` (`batch_sn`),
    KEY `idx_status` (`status`),
    KEY `idx_settle_date` (`settle_start_date`, `settle_end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='结算批次表';

-- la_financial_daily
CREATE TABLE IF NOT EXISTS `la_financial_daily` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `report_date` DATE NOT NULL COMMENT '报表日期',
    `order_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单数量',
    `paid_order_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付订单数',
    `refund_order_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '退款订单数',
    `total_income` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '总收入',
    `deposit_income` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '定金收入',
    `balance_income` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '尾款收入',
    `wechat_income` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '微信支付收入',
    `alipay_income` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '支付宝收入',
    `balance_pay_income` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '余额支付收入',
    `offline_income` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '线下收入',
    `total_refund` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '总退款',
    `total_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '总成本',
    `staff_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '人工成本',
    `material_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '物料成本',
    `other_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '其他成本',
    `total_settlement` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '人员结算总额',
    `platform_income` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '平台收入',
    `gross_profit` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '毛利润',
    `net_profit` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '净利润',
    `profit_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT '利润率(%)',
    `new_user_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '新用户数',
    `active_user_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '活跃用户数',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_report_date` (`report_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='财务日报表';

-- la_financial_monthly
CREATE TABLE IF NOT EXISTS `la_financial_monthly` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `report_year` SMALLINT UNSIGNED NOT NULL COMMENT '报表年份',
    `report_month` TINYINT UNSIGNED NOT NULL COMMENT '报表月份',
    `order_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单数量',
    `paid_order_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付订单数',
    `complete_order_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '完成订单数',
    `refund_order_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '退款订单数',
    `total_income` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '总收入',
    `wechat_income` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '微信支付收入',
    `alipay_income` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '支付宝收入',
    `balance_pay_income` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '余额支付收入',
    `offline_income` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '线下收入',
    `total_refund` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '总退款',
    `total_cost` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '总成本',
    `staff_cost` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '人工成本',
    `material_cost` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '物料成本',
    `other_cost` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '其他成本',
    `total_settlement` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '人员结算总额',
    `platform_income` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '平台收入',
    `gross_profit` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '毛利润',
    `net_profit` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '净利润',
    `profit_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT '利润率(%)',
    `avg_order_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '平均订单金额',
    `conversion_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT '转化率(%)',
    `yoy_growth_rate` DECIMAL(6,2) NOT NULL DEFAULT 0.00 COMMENT '同比增长率(%)',
    `mom_growth_rate` DECIMAL(6,2) NOT NULL DEFAULT 0.00 COMMENT '环比增长率(%)',
    `new_user_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '新用户数',
    `active_user_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '活跃用户数',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_report_year_month` (`report_year`, `report_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='财务月报表';

-- la_invoice
CREATE TABLE IF NOT EXISTS `la_invoice` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `invoice_sn` VARCHAR(32) NOT NULL COMMENT '发票编号',
    `invoice_no` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '发票号码',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `invoice_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '发票类型：1=电子普票,2=电子专票,3=纸质普票,4=纸质专票',
    `title_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '抬头类型：1=个人,2=企业',
    `invoice_title` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '发票抬头',
    `tax_no` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '税号',
    `bank_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '开户行',
    `bank_account` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '银行账号',
    `company_address` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '企业地址',
    `company_phone` VARCHAR(30) NOT NULL DEFAULT '' COMMENT '企业电话',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '发票金额',
    `email` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '接收邮箱',
    `receiver_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '收件人(纸质)',
    `receiver_phone` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '收件电话(纸质)',
    `receiver_address` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '收件地址(纸质)',
    `invoice_url` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '电子发票下载地址',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态：0=待开票,1=开票中,2=已开票,3=失败,4=已作废',
    `issue_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '开票时间',
    `issue_admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '开票管理员ID',
    `fail_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '失败原因',
    `void_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '作废原因',
    `void_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '作废时间',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_invoice_sn` (`invoice_sn`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='发票记录表';

-- la_staff_settlement_config
CREATE TABLE IF NOT EXISTS `la_staff_settlement_config` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `staff_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID(0=默认)',
    `category_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务分类ID(0=全部)',
    `settlement_rate` DECIMAL(5,2) NOT NULL DEFAULT 70.00 COMMENT '结算比例(%)',
    `min_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '最低结算金额',
    `settle_cycle` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '结算周期：1=月结,2=周结,3=单笔结',
    `settle_delay_days` INT UNSIGNED NOT NULL DEFAULT 7 COMMENT '结算延迟天数',
    `is_default` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否默认配置',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=禁用,1=启用',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_category_id` (`category_id`),
    KEY `idx_is_default` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务人员结算配置表';

-- la_financial_reconciliation
CREATE TABLE IF NOT EXISTS `la_financial_reconciliation` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `reconcile_sn` VARCHAR(32) NOT NULL COMMENT '对账编号',
    `reconcile_date` DATE NOT NULL COMMENT '对账日期',
    `pay_channel` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '支付渠道：1=微信,2=支付宝',
    `system_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '系统交易笔数',
    `system_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '系统交易金额',
    `channel_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '渠道交易笔数',
    `channel_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '渠道交易金额',
    `diff_count` INT NOT NULL DEFAULT 0 COMMENT '差异笔数',
    `diff_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '差异金额',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态：0=待对账,1=对账中,2=已平账,3=有差异,4=已处理',
    `bill_file` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '账单文件地址',
    `result_file` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '对账结果文件',
    `handle_admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理管理员ID',
    `handle_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理时间',
    `handle_remark` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '处理备注',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_reconcile_sn` (`reconcile_sn`),
    UNIQUE KEY `uk_date_channel` (`reconcile_date`, `pay_channel`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='财务对账记录表';

-- la_sales_advisor
CREATE TABLE IF NOT EXISTS `la_sales_advisor` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联管理员ID',
    `advisor_name` VARCHAR(50) NOT NULL COMMENT '顾问姓名',
    `avatar` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '头像',
    `mobile` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '手机号',
    `wechat` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '企业微信号',
    `wecom_userid` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '企业微信成员ID',
    `contact_qr_code` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '联系我二维码',
    `contact_link` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '联系我链接',
    `email` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '邮箱',
    `areas` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '负责区域(JSON)',
    `specialties` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '擅长服务类型(JSON)',
    `max_customer_count` INT UNSIGNED NOT NULL DEFAULT 100 COMMENT '最大客户数',
    `current_customer_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '当前客户数',
    `total_order_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '累计成交订单数',
    `total_order_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '累计成交金额',
    `conversion_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT '转化率(%)',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=离职,1=正常,2=休假',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序权重',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_admin_id` (`admin_id`),
    KEY `idx_wecom_userid` (`wecom_userid`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='销售顾问表';

-- la_customer
CREATE TABLE IF NOT EXISTS `la_customer` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联用户ID(0=潜在客户)',
    `customer_name` VARCHAR(50) NOT NULL COMMENT '客户姓名',
    `customer_mobile` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '手机号',
    `customer_wechat` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '微信号',
    `gender` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '性别：0=未知,1=男,2=女',
    `age` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '年龄',
    `city` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '所在城市',
    `district` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '所在区域',
    `intention_level` CHAR(1) NOT NULL DEFAULT 'D' COMMENT '意向等级：A=高,B=中,C=低,D=待跟进',
    `intention_score` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '意向评分(0-100)',
    `wedding_date` DATE DEFAULT NULL COMMENT '计划婚期',
    `wedding_venue` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '婚礼场地',
    `wedding_budget` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '预算金额',
    `budget_range` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '预算范围',
    `service_needs` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '服务需求(JSON)',
    `source_channel` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '来源渠道：1=小程序,2=H5,3=线下,4=转介绍,5=广告,6=其他',
    `source_detail` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '来源详情',
    `tags` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '客户标签(JSON)',
    `customer_status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '客户状态：1=新客户,2=跟进中,3=已签单,4=已流失,5=已完成',
    `loss_reason` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '流失原因',
    `loss_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '流失时间',
    `advisor_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '分配销售顾问ID',
    `assign_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '分配时间',
    `first_contact_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '首次联系时间',
    `last_follow_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后跟进时间',
    `next_follow_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '下次跟进时间',
    `follow_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '跟进次数',
    `order_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '成交订单数',
    `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '累计消费金额',
    `remark` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '备注',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_customer_mobile` (`customer_mobile`),
    KEY `idx_intention_level` (`intention_level`),
    KEY `idx_customer_status` (`customer_status`),
    KEY `idx_advisor_id` (`advisor_id`),
    KEY `idx_wedding_date` (`wedding_date`),
    KEY `idx_last_follow_time` (`last_follow_time`),
    KEY `idx_next_follow_time` (`next_follow_time`),
    KEY `idx_source_channel` (`source_channel`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='客户信息表';

-- la_follow_record
CREATE TABLE IF NOT EXISTS `la_follow_record` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `customer_id` INT UNSIGNED NOT NULL COMMENT '客户ID',
    `advisor_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '销售顾问ID',
    `admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作管理员ID',
    `follow_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '跟进方式：1=电话,2=微信,3=到店,4=试妆,5=看样片,6=上门,7=其他',
    `follow_content` TEXT NOT NULL COMMENT '跟进内容',
    `follow_result` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '跟进结果：1=继续跟进,2=意向提升,3=意向下降,4=已成交,5=已流失',
    `intention_before` CHAR(1) NOT NULL DEFAULT '' COMMENT '跟进前意向等级',
    `intention_after` CHAR(1) NOT NULL DEFAULT '' COMMENT '跟进后意向等级',
    `duration` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '沟通时长(分钟)',
    `next_follow_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '下次跟进时间',
    `next_follow_content` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '下次跟进计划',
    `attachments` TEXT COMMENT '附件(JSON)',
    `is_important` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否重要',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_customer_id` (`customer_id`),
    KEY `idx_advisor_id` (`advisor_id`),
    KEY `idx_follow_type` (`follow_type`),
    KEY `idx_follow_result` (`follow_result`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='跟进记录表';

-- la_customer_assign_log
CREATE TABLE IF NOT EXISTS `la_customer_assign_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `customer_id` INT UNSIGNED NOT NULL COMMENT '客户ID',
    `from_advisor_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '原顾问ID(0=首次分配)',
    `to_advisor_id` INT UNSIGNED NOT NULL COMMENT '新顾问ID',
    `assign_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '分配类型：1=自动,2=手动,3=转交,4=回收重分',
    `assign_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '分配原因',
    `admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作管理员ID(0=系统)',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_customer_id` (`customer_id`),
    KEY `idx_from_advisor_id` (`from_advisor_id`),
    KEY `idx_to_advisor_id` (`to_advisor_id`),
    KEY `idx_assign_type` (`assign_type`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='客户分配日志表';

-- la_customer_loss_warning
CREATE TABLE IF NOT EXISTS `la_customer_loss_warning` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `customer_id` INT UNSIGNED NOT NULL COMMENT '客户ID',
    `advisor_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '销售顾问ID',
    `warning_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '预警类型：1=长期未跟进,2=意向下降,3=竞品流失,4=预算不足,5=其他',
    `warning_level` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '预警等级：1=低,2=中,3=高',
    `warning_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '预警原因',
    `days_no_follow` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '未跟进天数',
    `warning_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理状态：0=待处理,1=已处理,2=已忽略',
    `handle_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理时间',
    `handle_remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '处理备注',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_customer_id` (`customer_id`),
    KEY `idx_advisor_id` (`advisor_id`),
    KEY `idx_warning_type` (`warning_type`),
    KEY `idx_warning_status` (`warning_status`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='客户流失预警表';

-- la_after_sale_ticket
CREATE TABLE IF NOT EXISTS `la_after_sale_ticket` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '工单ID',
    `ticket_sn` varchar(32) NOT NULL DEFAULT '' COMMENT '工单编号',
    `order_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '工单类型:1-投诉,2-咨询,3-售后,4-建议,5-其他',
    `priority` tinyint(1) UNSIGNED NOT NULL DEFAULT 2 COMMENT '优先级:1-低,2-中,3-高,4-紧急',
    `title` varchar(200) NOT NULL DEFAULT '' COMMENT '工单标题',
    `content` text COMMENT '工单内容',
    `images` text COMMENT '图片凭证(JSON数组)',
    `contact_name` varchar(50) NOT NULL DEFAULT '' COMMENT '联系人姓名',
    `contact_phone` varchar(20) NOT NULL DEFAULT '' COMMENT '联系电话',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态:0-待分配,1-处理中,2-待确认,3-已完成,4-已关闭,5-已取消',
    `assign_admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理人ID',
    `assign_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '分配时间',
    `handle_result` text COMMENT '处理结果',
    `handle_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理时间',
    `close_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '关闭原因',
    `close_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关闭时间',
    `satisfaction` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '满意度评分:0-未评价,1-5星',
    `satisfaction_remark` varchar(500) NOT NULL DEFAULT '' COMMENT '满意度评价内容',
    `expect_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '期望处理时间',
    `deadline` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理截止时间',
    `is_overtime` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否超时:0-否,1-是',
    `escalate_level` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '升级次数',
    `escalate_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后升级时间',
    `source` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '来源:1-小程序,2-后台,3-电话',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` int(11) UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_ticket_sn` (`ticket_sn`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_type` (`type`),
    KEY `idx_status` (`status`),
    KEY `idx_priority` (`priority`),
    KEY `idx_assign_admin_id` (`assign_admin_id`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='售后工单表';

-- la_after_sale_ticket_log
CREATE TABLE IF NOT EXISTS `la_after_sale_ticket_log` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `ticket_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '工单ID',
    `operator_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '操作人类型:1-用户,2-管理员,3-系统',
    `operator_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作人ID',
    `action` varchar(50) NOT NULL DEFAULT '' COMMENT '操作动作',
    `old_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作前状态',
    `new_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作后状态',
    `content` text COMMENT '操作内容/备注',
    `images` text COMMENT '附件图片(JSON数组)',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_ticket_id` (`ticket_id`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='工单处理记录表';

-- la_complaint
CREATE TABLE IF NOT EXISTS `la_complaint` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '投诉ID',
    `complaint_sn` varchar(32) NOT NULL DEFAULT '' COMMENT '投诉编号',
    `order_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '被投诉服务人员ID',
    `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '投诉类型:1-服务态度,2-专业能力,3-迟到早退,4-违规行为,5-其他',
    `level` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '投诉等级:1-一般,2-严重,3-紧急',
    `title` varchar(200) NOT NULL DEFAULT '' COMMENT '投诉标题',
    `content` text COMMENT '投诉内容',
    `images` text COMMENT '图片凭证(JSON数组)',
    `videos` text COMMENT '视频凭证(JSON数组)',
    `expect_result` varchar(500) NOT NULL DEFAULT '' COMMENT '期望处理结果',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态:0-待处理,1-处理中,2-已处理,3-已申诉,4-已关闭',
    `handle_admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理人ID',
    `handle_result` text COMMENT '处理结果',
    `handle_action` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理动作:0-无,1-警告,2-扣款,3-禁用,4-其他',
    `handle_amount` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '处理涉及金额（扣款/赔偿）',
    `handle_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理时间',
    `deadline` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理截止时间',
    `is_overtime` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否超时:0-否,1-是',
    `appeal_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '申诉状态:0-未申诉,1-申诉中,2-申诉成功,3-申诉失败',
    `appeal_reason` text COMMENT '申诉原因',
    `appeal_images` text COMMENT '申诉图片(JSON数组)',
    `appeal_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '申诉时间',
    `close_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '关闭原因',
    `close_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关闭时间',
    `source` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '来源:1-小程序,2-后台,3-电话',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` int(11) UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_complaint_sn` (`complaint_sn`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_type` (`type`),
    KEY `idx_status` (`status`),
    KEY `idx_level` (`level`),
    KEY `idx_handle_admin_id` (`handle_admin_id`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='投诉表';

-- la_service_callback
CREATE TABLE IF NOT EXISTS `la_service_callback` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '回访ID',
    `callback_sn` varchar(32) NOT NULL DEFAULT '' COMMENT '回访编号',
    `order_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID',
    `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '回访类型 1服务前 2服务中 3服务后',
    `method` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '回访方式 1电话 2短信 3微信 4小程序问卷',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态 0待回访 1已回访 2无法联系 3已取消',
    `plan_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '计划回访时间',
    `actual_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '实际回访时间',
    `admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回访人ID',
    `duration` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回访时长（秒）',
    `score` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '满意度评分 0未评 1-5星',
    `score_service` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务态度评分',
    `score_professional` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '专业水平评分',
    `score_punctual` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '时间守约评分',
    `score_overall` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '整体满意度评分',
    `content` text COMMENT '回访内容/用户反馈',
    `summary` text COMMENT '回访摘要',
    `has_problem` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否有问题 0否 1是',
    `problem_type` varchar(100) NOT NULL DEFAULT '' COMMENT '问题类型',
    `problem_desc` text COMMENT '问题描述',
    `problem_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '问题状态 0未处理 1已处理 2已升级',
    `problem_handle_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '问题处理时间',
    `ticket_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联工单ID（升级时创建）',
    `retry_count` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '重试次数',
    `next_retry_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '下次重试时间',
    `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` int(11) UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_callback_sn` (`callback_sn`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_status` (`status`),
    KEY `idx_type` (`type`),
    KEY `idx_plan_time` (`plan_time`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='服务回访表';

-- la_callback_questionnaire
CREATE TABLE IF NOT EXISTS `la_callback_questionnaire` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `title` varchar(200) NOT NULL DEFAULT '' COMMENT '问卷标题',
    `description` varchar(500) NOT NULL DEFAULT '' COMMENT '问卷描述',
    `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '回访类型:1-服务前,2-服务中,3-服务后',
    `questions` text COMMENT '问题列表(JSON数组)',
    `sort` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态:0-禁用,1-启用',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` int(11) UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_type` (`type`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='回访问卷配置表';

-- la_callback_answer
CREATE TABLE IF NOT EXISTS `la_callback_answer` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `callback_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回访ID',
    `questionnaire_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '问卷ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `answers` text COMMENT '答案(JSON数组)',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_callback_id` (`callback_id`),
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='回访问卷答案表';

-- la_escalate_rule
CREATE TABLE IF NOT EXISTS `la_escalate_rule` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `name` varchar(100) NOT NULL DEFAULT '' COMMENT '规则名称',
    `ticket_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '工单类型:0-全部',
    `priority` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '优先级:0-全部',
    `timeout_hours` int(11) UNSIGNED NOT NULL DEFAULT 24 COMMENT '超时时间（小时）',
    `escalate_to` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '升级到人员ID',
    `notify_method` varchar(50) NOT NULL DEFAULT 'system' COMMENT '通知方式:system/sms/wechat',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态:0-禁用,1-启用',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='问题升级规则配置表';

-- la_after_sale_daily_stats
CREATE TABLE IF NOT EXISTS `la_after_sale_daily_stats` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `stat_date` date NOT NULL COMMENT '统计日期',
    `ticket_total` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '工单总数',
    `ticket_new` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '新增工单数',
    `ticket_completed` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '完成工单数',
    `ticket_overtime` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '超时工单数',
    `complaint_total` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '投诉总数',
    `complaint_handled` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '已处理投诉数',
    `callback_total` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回访总数',
    `callback_completed` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '已完成回访数',
    `avg_handle_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '平均处理时长（秒）',
    `satisfaction_avg` decimal(3,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '平均满意度',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_stat_date` (`stat_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='售后工单每日统计表';

-- =============================================================================
-- Part 2: 基础结构补充
-- =============================================================================

-- 2.1 服务回访表

CREATE TABLE IF NOT EXISTS `la_service_callback` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '回访ID',
    `callback_sn` varchar(32) NOT NULL DEFAULT '' COMMENT '回访编号',
    `order_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID',
    `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '回访类型 1服务前 2服务中 3服务后',
    `method` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '回访方式 1电话 2短信 3微信 4小程序问卷',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态 0待回访 1已回访 2无法联系 3已取消',
    `plan_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '计划回访时间',
    `actual_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '实际回访时间',
    `admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回访人ID',
    `duration` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回访时长（秒）',
    `score` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '满意度评分 0未评 1-5星',
    `score_service` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务态度评分',
    `score_professional` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '专业水平评分',
    `score_punctual` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '时间守约评分',
    `score_overall` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '整体满意度评分',
    `content` text COMMENT '回访内容/用户反馈',
    `summary` text COMMENT '回访摘要',
    `has_problem` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否有问题 0否 1是',
    `problem_type` varchar(100) NOT NULL DEFAULT '' COMMENT '问题类型',
    `problem_desc` text COMMENT '问题描述',
    `problem_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '问题状态 0未处理 1已处理 2已升级',
    `problem_handle_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '问题处理时间',
    `ticket_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联工单ID（升级时创建）',
    `retry_count` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '重试次数',
    `next_retry_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '下次重试时间',
    `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` int(11) UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_callback_sn` (`callback_sn`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_status` (`status`),
    KEY `idx_type` (`type`),
    KEY `idx_plan_time` (`plan_time`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='服务回访表';

-- 2.2 基础框架关联补充
SET @add_user_points_sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_user'
          AND COLUMN_NAME = 'user_points'
    ),
    'SELECT 1',
    'ALTER TABLE `la_user` ADD COLUMN `user_points` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''用户积分'' AFTER `user_money`'
);
PREPARE stmt FROM @add_user_points_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =============================================================================
-- Part 3: 基础数据同步
-- =============================================================================

-- la_service_category
INSERT INTO `la_service_category` (`id`, `name`, `pid`, `icon`, `image`, `sort`, `is_show`, `create_time`, `update_time`, `delete_time`) VALUES
(1, '摄影师', 0, '', '/resource/image/wedding/category/photographer.png', 100, 0, 1773413103, 1773559892, NULL),
(2, '摄像师', 0, '', '/resource/image/wedding/category/videographer.png', 90, 1, 1773413103, 1773559912, NULL),
(3, '化妆师', 0, '', '/resource/image/wedding/category/makeup.png', 80, 0, 1773413103, 1773559896, NULL),
(4, '司仪主持', 0, '', 'resource/image/wedding/category/host.png', 110, 1, 1773413103, 1773561060, NULL),
(5, '婚礼策划', 0, '', '/resource/image/wedding/category/planner.png', 60, 0, 1773413103, 1773559897, NULL),
(6, '花艺师', 0, '', '/resource/image/wedding/category/florist.png', 50, 0, 1773413103, 1773559898, NULL),
(7, '跟妆师', 0, '', '/resource/image/wedding/category/stylist.png', 40, 0, 1773413103, 1773559899, NULL),
(8, '灯光师', 0, '', '/resource/image/wedding/category/lighting.png', 30, 0, 1773413103, 1773559899, NULL)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `pid`=VALUES(`pid`), `icon`=VALUES(`icon`), `image`=VALUES(`image`), `sort`=VALUES(`sort`), `is_show`=VALUES(`is_show`), `create_time`=VALUES(`create_time`), `update_time`=VALUES(`update_time`), `delete_time`=VALUES(`delete_time`);

-- la_style_tag
INSERT INTO `la_style_tag` (`id`, `name`, `type`, `category_id`, `sort`, `is_show`, `create_time`, `update_time`, `delete_time`) VALUES
(1, '韩式清新', 1, 4, 100, 1, 1773413103, 1773559953, NULL),
(2, '中式古典', 1, 4, 90, 1, 1773413103, 1773559971, NULL),
(3, '欧式浪漫', 1, 4, 80, 1, 1773413103, 1773559975, NULL),
(4, '日系森系', 1, 4, 70, 1, 1773413103, 1773559986, NULL),
(5, '简约现代', 1, 4, 60, 1, 1773413103, 1773559994, NULL),
(6, '复古怀旧', 1, 4, 50, 1, 1773413103, 1773559997, NULL),
(7, '户外自然', 2, 4, 100, 1, 1773413103, 1773559956, NULL),
(8, '室内棚拍', 2, 2, 90, 1, 1773413103, 1773559966, NULL),
(9, '旅拍跟拍', 2, 2, 80, 1, 1773413103, 1773559981, NULL),
(10, '纪实风格', 2, 2, 70, 1, 1773413103, 1773559990, NULL)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `type`=VALUES(`type`), `category_id`=VALUES(`category_id`), `sort`=VALUES(`sort`), `is_show`=VALUES(`is_show`), `create_time`=VALUES(`create_time`), `update_time`=VALUES(`update_time`), `delete_time`=VALUES(`delete_time`);

-- la_service_package
INSERT INTO `la_service_package` (`id`, `category_id`, `staff_id`, `name`, `price`, `original_price`, `duration`, `description`, `image`, `sort`, `is_recommend`, `is_show`, `create_time`, `update_time`, `delete_time`) VALUES
(9, 4, 8, '全程接亲', '1600.00', '1600.00', 0, '', '', 0, 0, 1, 1773563974, 1773563974, NULL),
(10, 4, 10, '全程接亲', '3280.00', '3280.00', 0, '', '', 0, 0, 1, 1773565129, 1773565129, NULL),
(15, 4, 3, '婚礼主持-标准版', '1580.00', '1880.00', 0, '', '', 0, 0, 1, 1773568027, 1773568027, NULL),
(16, 4, 3, '婚礼主持-全程', '2580.00', '2880.00', 0, '', '', 0, 0, 1, 1773568118, 1773568118, NULL),
(17, 4, 9, '婚礼全程主持', '2280.00', '2280.00', 0, '', '', 0, 0, 1, 1773578041, 1773578041, NULL),
(18, 4, 6, '午宴PRO档', '1580.00', '1580.00', 0, '主持人+督导老师+现场音控', '', 0, 0, 1, 1773619584, 1773621115, NULL),
(19, 4, 6, '全程MAX档', '2580.00', '2580.00', 0, '主持人提供陪同新郎全程接亲服务、配合总管组织、协调人员、车辆、把控各环节流程和时间节点、新郎和新娘家出发仪式、双方家中改口仪式、组织合影等相关工作。人员配置：主持人、午宴督导、现场音控', '', 0, 0, 1, 1773619885, 1773621140, NULL)
ON DUPLICATE KEY UPDATE `category_id`=VALUES(`category_id`), `staff_id`=VALUES(`staff_id`), `name`=VALUES(`name`), `price`=VALUES(`price`), `original_price`=VALUES(`original_price`), `duration`=VALUES(`duration`), `description`=VALUES(`description`), `image`=VALUES(`image`), `sort`=VALUES(`sort`), `is_recommend`=VALUES(`is_recommend`), `is_show`=VALUES(`is_show`), `create_time`=VALUES(`create_time`), `update_time`=VALUES(`update_time`), `delete_time`=VALUES(`delete_time`);

-- la_schedule_rule
INSERT INTO `la_schedule_rule` (`id`, `staff_id`, `advance_days`, `max_orders_per_day`, `interval_hours`, `work_start_time`, `work_end_time`, `rest_days`, `is_enabled`, `create_time`, `update_time`) VALUES
(1, 0, 3, 1, 0, '08:00', '20:00', '', 1, 1773413104, 1773413104)
ON DUPLICATE KEY UPDATE `staff_id`=VALUES(`staff_id`), `advance_days`=VALUES(`advance_days`), `max_orders_per_day`=VALUES(`max_orders_per_day`), `interval_hours`=VALUES(`interval_hours`), `work_start_time`=VALUES(`work_start_time`), `work_end_time`=VALUES(`work_end_time`), `rest_days`=VALUES(`rest_days`), `is_enabled`=VALUES(`is_enabled`), `create_time`=VALUES(`create_time`), `update_time`=VALUES(`update_time`);

-- la_calendar_event
INSERT INTO `la_calendar_event` (`id`, `event_date`, `lunar_date`, `is_lucky_day`, `lucky_events`, `unlucky_events`, `is_holiday`, `holiday_name`, `congestion_level`, `remark`, `create_time`, `update_time`) VALUES
(1, '2026-01-01', '十一月十二', 0, '', '', 1, '元旦', 3, '', 1773413104, 1773413104),
(2, '2026-02-17', '正月初一', 1, '祈福,嫁娶,订盟', '动土,破土', 1, '春节', 3, '', 1773413104, 1773413104),
(3, '2026-05-01', '三月十五', 1, '嫁娶,订盟,纳采', '开市,动土', 1, '劳动节', 3, '', 1773413104, 1773413104),
(4, '2026-05-20', '四月初四', 1, '嫁娶,订盟,纳采', '安葬,破土', 0, '', 3, '', 1773413104, 1773413104),
(5, '2026-10-01', '八月廿一', 1, '嫁娶,祈福,订盟', '安葬,动土', 1, '国庆节', 3, '', 1773413104, 1773413104)
ON DUPLICATE KEY UPDATE `event_date`=VALUES(`event_date`), `lunar_date`=VALUES(`lunar_date`), `is_lucky_day`=VALUES(`is_lucky_day`), `lucky_events`=VALUES(`lucky_events`), `unlucky_events`=VALUES(`unlucky_events`), `is_holiday`=VALUES(`is_holiday`), `holiday_name`=VALUES(`holiday_name`), `congestion_level`=VALUES(`congestion_level`), `remark`=VALUES(`remark`), `create_time`=VALUES(`create_time`), `update_time`=VALUES(`update_time`);

-- la_review_tag
INSERT INTO `la_review_tag` (`id`, `name`, `type`, `icon`, `color`, `sort`, `use_count`, `status`, `create_time`, `update_time`, `delete_time`) VALUES
(1, '服务热情', 1, '', '#52c41a', 1, 0, 1, 1773413105, 1773413105, NULL),
(2, '专业细致', 1, '', '#52c41a', 2, 0, 1, 1773413105, 1773413105, NULL),
(3, '准时守约', 1, '', '#52c41a', 3, 0, 1, 1773413105, 1773413105, NULL),
(4, '效果满意', 1, '', '#52c41a', 4, 0, 1, 1773413105, 1773413105, NULL),
(5, '沟通顺畅', 1, '', '#52c41a', 5, 0, 1, 1773413105, 1773413105, NULL),
(6, '性价比高', 1, '', '#52c41a', 6, 0, 1, 1773413105, 1773413105, NULL),
(7, '耐心负责', 1, '', '#52c41a', 7, 0, 1, 1773413105, 1773413105, NULL),
(8, '创意独特', 1, '', '#52c41a', 8, 0, 1, 1773413105, 1773413105, NULL),
(9, '一般般', 2, '', '#faad14', 10, 0, 1, 1773413105, 1773413105, NULL),
(10, '有待改进', 2, '', '#faad14', 11, 0, 1, 1773413105, 1773413105, NULL),
(11, '服务冷淡', 3, '', '#ff4d4f', 20, 0, 1, 1773413105, 1773413105, NULL),
(12, '不够专业', 3, '', '#ff4d4f', 21, 0, 1, 1773413105, 1773413105, NULL),
(13, '迟到早退', 3, '', '#ff4d4f', 22, 0, 1, 1773413105, 1773413105, NULL),
(14, '效果不佳', 3, '', '#ff4d4f', 23, 0, 1, 1773413105, 1773413105, NULL)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `type`=VALUES(`type`), `icon`=VALUES(`icon`), `color`=VALUES(`color`), `sort`=VALUES(`sort`), `use_count`=VALUES(`use_count`), `status`=VALUES(`status`), `create_time`=VALUES(`create_time`), `update_time`=VALUES(`update_time`), `delete_time`=VALUES(`delete_time`);

-- la_review_reward_config
INSERT INTO `la_review_reward_config` (`id`, `reward_type`, `reward_points`, `min_content_length`, `min_images`, `min_video_duration`, `extra_points_for_good`, `status`, `create_time`, `update_time`) VALUES
(1, 1, 10, 20, 0, 0, 5, 1, 1773413105, 1773413105),
(2, 2, 30, 20, 3, 0, 10, 1, 1773413105, 1773413105),
(3, 3, 50, 10, 0, 15, 20, 1, 1773413105, 1773413105)
ON DUPLICATE KEY UPDATE `reward_type`=VALUES(`reward_type`), `reward_points`=VALUES(`reward_points`), `min_content_length`=VALUES(`min_content_length`), `min_images`=VALUES(`min_images`), `min_video_duration`=VALUES(`min_video_duration`), `extra_points_for_good`=VALUES(`extra_points_for_good`), `status`=VALUES(`status`), `create_time`=VALUES(`create_time`), `update_time`=VALUES(`update_time`);

-- la_staff_settlement_config
INSERT INTO `la_staff_settlement_config` (`id`, `staff_id`, `category_id`, `settlement_rate`, `min_amount`, `settle_cycle`, `settle_delay_days`, `is_default`, `status`, `remark`, `create_time`, `update_time`, `delete_time`) VALUES
(1, 0, 0, '70.00', '100.00', 1, 7, 1, 1, '默认结算配置：70%分成，月结，服务完成后7天可结算', 1773413106, 1773413106, NULL)
ON DUPLICATE KEY UPDATE `staff_id`=VALUES(`staff_id`), `category_id`=VALUES(`category_id`), `settlement_rate`=VALUES(`settlement_rate`), `min_amount`=VALUES(`min_amount`), `settle_cycle`=VALUES(`settle_cycle`), `settle_delay_days`=VALUES(`settle_delay_days`), `is_default`=VALUES(`is_default`), `status`=VALUES(`status`), `remark`=VALUES(`remark`), `create_time`=VALUES(`create_time`), `update_time`=VALUES(`update_time`), `delete_time`=VALUES(`delete_time`);

-- la_escalate_rule
INSERT INTO `la_escalate_rule` (`id`, `name`, `ticket_type`, `priority`, `timeout_hours`, `escalate_to`, `notify_method`, `status`, `create_time`, `update_time`) VALUES
(1, '紧急工单2小时升级', 0, 4, 2, 0, 'system,sms', 1, 1773413107, 1773413107),
(2, '高优先级工单4小时升级', 0, 3, 4, 0, 'system', 1, 1773413107, 1773413107),
(3, '普通工单24小时升级', 0, 2, 24, 0, 'system', 1, 1773413107, 1773413107),
(4, '投诉48小时升级', 1, 0, 48, 0, 'system,sms', 1, 1773413107, 1773413107)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `ticket_type`=VALUES(`ticket_type`), `priority`=VALUES(`priority`), `timeout_hours`=VALUES(`timeout_hours`), `escalate_to`=VALUES(`escalate_to`), `notify_method`=VALUES(`notify_method`), `status`=VALUES(`status`), `create_time`=VALUES(`create_time`), `update_time`=VALUES(`update_time`);

-- la_callback_questionnaire
INSERT INTO `la_callback_questionnaire` (`id`, `title`, `description`, `type`, `questions`, `sort`, `status`, `create_time`, `update_time`, `delete_time`) VALUES
(1, '服务后满意度调查', '感谢您使用我们的服务，请花1分钟完成以下问卷', 3, '[{"id":1,"type":"rating","title":"整体服务满意度","required":true},{"id":2,"type":"rating","title":"服务人员态度"},{"id":3,"type":"rating","title":"专业水平"},{"id":4,"type":"rating","title":"时间守约"},{"id":5,"type":"text","title":"您的建议","placeholder":"请输入您的宝贵意见..."}]', 1, 1, 1773413107, 1773413107, NULL)
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`), `description`=VALUES(`description`), `type`=VALUES(`type`), `questions`=VALUES(`questions`), `sort`=VALUES(`sort`), `status`=VALUES(`status`), `create_time`=VALUES(`create_time`), `update_time`=VALUES(`update_time`), `delete_time`=VALUES(`delete_time`);

-- la_dict_data
DELETE FROM `la_dict_data`;
INSERT INTO `la_dict_data` (`id`, `name`, `value`, `type_id`, `type_value`, `sort`, `status`, `remark`, `create_time`, `update_time`, `delete_time`) VALUES
(1, '隐藏', '0', 1, 'show_status', 0, 1, '', 1656381543, 1656381543, NULL),
(2, '显示', '1', 1, 'show_status', 0, 1, '', 1656381550, 1656381550, NULL),
(3, '进行中', '0', 2, 'business_status', 0, 1, '', 1656381410, 1656381410, NULL),
(4, '成功', '1', 2, 'business_status', 0, 1, '', 1656381437, 1656381437, NULL),
(5, '失败', '2', 2, 'business_status', 0, 1, '', 1656381449, 1656381449, NULL),
(6, '待处理', '0', 3, 'event_status', 0, 1, '', 1656381212, 1656381212, NULL),
(7, '已处理', '1', 3, 'event_status', 0, 1, '', 1656381315, 1656381315, NULL),
(8, '拒绝处理', '2', 3, 'event_status', 0, 1, '', 1656381331, 1656381331, NULL),
(9, '禁用', '1', 4, 'system_disable', 0, 1, '', 1656312030, 1656312030, NULL),
(10, '正常', '0', 4, 'system_disable', 0, 1, '', 1656312040, 1656312040, NULL),
(11, '未知', '0', 5, 'sex', 0, 1, '', 1656062988, 1656062988, NULL),
(12, '男', '1', 5, 'sex', 0, 1, '', 1656062999, 1656062999, NULL),
(13, '女', '2', 5, 'sex', 0, 1, '', 1656063009, 1656063009, NULL),
(14, '疫情', '1', 6, '1', 1, 1, '订单暂停类型-疫情', 1773413105, 1773413105, NULL),
(15, '突发事件', '2', 6, '2', 2, 1, '订单暂停类型-突发事件', 1773413105, 1773413105, NULL),
(16, '个人原因', '3', 6, '3', 3, 1, '订单暂停类型-个人原因', 1773413105, 1773413105, NULL),
(17, '其他', '4', 6, '4', 4, 1, '订单暂停类型-其他', 1773413105, 1773413105, NULL);

-- la_subscribe_message_scene
DELETE FROM `la_subscribe_message_scene`;
INSERT INTO `la_subscribe_message_scene` (`id`, `scene`, `name`, `description`, `template_id`, `trigger_event`, `data_mapping`, `page_path`, `is_auto`, `delay_seconds`, `status`, `sort`, `create_time`, `update_time`) VALUES
(1, 'order_create', '订单创建通知', '用户提交订单后发送确认通知', '', 'OrderCreated', NULL, 'pages/order_detail/order_detail', 1, 0, 1, 110, 1773413107, 1773413107),
(2, 'order_paid', '支付成功通知', '用户完成支付后发送确认通知', '', 'OrderPaid', NULL, 'pages/order_detail/order_detail', 1, 0, 1, 109, 1773413107, 1773413107),
(3, 'order_confirm', '订单确认通知', '商家确认订单后通知用户', 'TEMPLATE_ID_ORDER_CONFIRM', 'OrderConfirmed', '{"character_string1":"order_sn","thing2":"status_text","amount3":"pay_amount","time4":"service_date"}', 'pages/order_detail/order_detail', 1, 0, 1, 108, 1773413107, 1773413107),
(4, 'order_complete', '服务完成通知', '服务完成后通知用户进行评价', '', 'OrderCompleted', NULL, 'pages/review/publish', 1, 0, 1, 107, 1773413107, 1773413107),
(5, 'schedule_remind', '档期提醒通知', '服务日期前提醒用户', '', 'ScheduleRemind', NULL, 'pages/order_detail/order_detail', 1, 0, 1, 106, 1773413107, 1773413107),
(6, 'refund_result', '退款结果通知', '退款审核结果通知', '', 'RefundProcessed', NULL, 'pages/order_detail/order_detail', 1, 0, 1, 105, 1773413107, 1773413107),
(7, 'callback_remind', '回访提醒通知', '服务完成后的回访提醒', '', 'CallbackRemind', NULL, 'pages/aftersale/callback', 1, 0, 1, 104, 1773413107, 1773413107),
(8, 'ticket_update', '工单进度通知', '售后工单状态更新通知', '', 'TicketUpdated', NULL, 'pages/aftersale/ticket_detail', 1, 0, 1, 103, 1773413107, 1773413107),
(9, 'change_result', '变更审核通知', '订单变更申请审核结果通知', '', 'ChangeProcessed', NULL, 'pages/order_change/change_detail', 1, 0, 1, 102, 1773413107, 1773413107),
(10, 'schedule_change', '档期变更通知', '人员档期发生变更时通知', '', 'ScheduleChanged', NULL, 'pages/order_detail/order_detail', 1, 0, 1, 101, 1773413107, 1773413107),
(11, 'waitlist_release', '候补释放通知', '档期释放后通知候补用户', 'TEMPLATE_ID_WAITLIST_RELEASE', 'WaitlistReleased', '{"thing1":"staff_name","time2":"schedule_date","thing3":"package_name","thing4":"remark"}', 'packages/pages/waitlist/waitlist', 1, 0, 1, 100, 1773413107, 1773413107),
(12, 'waitlist_expired', '候补失效通知', '候补超过预约日期后通知用户', 'TEMPLATE_ID_WAITLIST_EXPIRED', 'WaitlistExpired', '{"thing1":"staff_name","time2":"schedule_date","thing3":"package_name","thing4":"remark"}', 'packages/pages/waitlist/waitlist', 1, 0, 1, 99, 1773413107, 1773413107);

-- la_subscribe_message_template
DELETE FROM `la_subscribe_message_template`;
INSERT INTO `la_subscribe_message_template` (`id`, `template_id`, `name`, `title`, `scene`, `content`, `example`, `keywords`, `category_id`, `status`, `sort`, `remark`, `create_time`, `update_time`, `delete_time`) VALUES
(1, 'TEMPLATE_ID_ORDER_CREATE', '订单提交成功通知', '订单提交成功', 'order_create', '{"thing1":{"key":"订单内容","value":""},"character_string2":{"key":"订单编号","value":""},"amount3":{"key":"订单金额","value":""},"time4":{"key":"下单时间","value":""}}', NULL, '订单内容,订单编号,订单金额,下单时间', '', 1, 100, '订单创建后发送，需在微信后台申请模板后更新template_id', 1773413107, 1773413107, NULL),
(2, 'TEMPLATE_ID_ORDER_PAID', '支付成功通知', '支付成功', 'order_paid', '{"character_string1":{"key":"订单编号","value":""},"amount2":{"key":"支付金额","value":""},"time3":{"key":"支付时间","value":""},"thing4":{"key":"商品名称","value":""}}', NULL, '订单编号,支付金额,支付时间,商品名称', '', 1, 99, '支付完成后发送，需在微信后台申请模板后更新template_id', 1773413107, 1773413107, NULL),
(3, 'TEMPLATE_ID_ORDER_CONFIRM', '订单确认通知', '订单确认通知', 'order_confirm', '{"character_string1":{"key":"订单编号","value":""},"thing2":{"key":"确认状态","value":""},"amount3":{"key":"待付金额","value":""},"time4":{"key":"服务日期","value":""}}', '{"character_string1":"202603150001","thing2":"服务人员已确认","amount3":"2999.00","time4":"2026-03-20"}', '订单编号,确认状态,待付金额,服务日期', '', 1, 98, '订单全部确认后发送，需在微信后台申请模板后更新 template_id', 1773413107, 1773413107, NULL),
(4, 'TEMPLATE_ID_SERVICE_REMIND', '服务提醒通知', '服务提醒', 'schedule_remind', '{"thing1":{"key":"服务内容","value":""},"time2":{"key":"服务时间","value":""},"thing3":{"key":"服务地点","value":""},"thing4":{"key":"服务人员","value":""}}', NULL, '服务内容,服务时间,服务地点,服务人员', '', 1, 97, '服务前1天/3天提醒，需在微信后台申请模板后更新template_id', 1773413107, 1773413107, NULL),
(5, 'TEMPLATE_ID_REFUND_RESULT', '退款结果通知', '退款通知', 'refund_result', '{"character_string1":{"key":"订单编号","value":""},"amount2":{"key":"退款金额","value":""},"phrase3":{"key":"退款状态","value":""},"thing4":{"key":"退款原因","value":""}}', NULL, '订单编号,退款金额,退款状态,退款原因', '', 1, 96, '退款审核后发送，需在微信后台申请模板后更新template_id', 1773413107, 1773413107, NULL),
(6, 'TEMPLATE_ID_TICKET_UPDATE', '工单进度通知', '工单状态更新', 'ticket_update', '{"character_string1":{"key":"工单编号","value":""},"phrase2":{"key":"工单状态","value":""},"thing3":{"key":"处理说明","value":""},"time4":{"key":"更新时间","value":""}}', NULL, '工单编号,工单状态,处理说明,更新时间', '', 1, 95, '工单状态变更时发送，需在微信后台申请模板后更新template_id', 1773413107, 1773413107, NULL),
(7, 'TEMPLATE_ID_WAITLIST_RELEASE', '候补释放通知', '候补释放', 'waitlist_release', '{"thing1":{"key":"服务人员","value":""},"time2":{"key":"档期日期","value":""},"thing3":{"key":"套餐名称","value":""},"thing4":{"key":"备注","value":""}}', NULL, '服务人员,档期日期,套餐名称,备注', '', 1, 95, '候补释放后发送，需在微信后台申请模板后更新template_id', 1773413107, 1773413107, NULL),
(8, 'TEMPLATE_ID_WAITLIST_EXPIRED', '候补失效通知', '候补失效', 'waitlist_expired', '{"thing1":{"key":"服务人员","value":""},"time2":{"key":"档期日期","value":""},"thing3":{"key":"套餐名称","value":""},"thing4":{"key":"失效说明","value":""}}', NULL, '服务人员,档期日期,套餐名称,失效说明', '', 1, 94, '候补失效后发送，需在微信后台申请模板后更新template_id', 1773413107, 1773413107, NULL);

-- la_system_role
DELETE FROM `la_system_role`;
INSERT INTO `la_system_role` (`id`, `name`, `desc`, `sort`, `create_time`, `update_time`, `delete_time`) VALUES
(1, '服务人员', '服务人员', 0, 1773413108, 1773560454, NULL),
(2, '管理员', '', 0, 1773563523, 1773563576, NULL);

-- la_system_menu
DELETE FROM `la_system_role_menu`;
DELETE FROM `la_system_menu`;
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES
(4, 0, 'M', '权限管理', 'el-icon-Lock', 300, '', 'permission', '', '', '', 0, 1, 0, 1656664556, 1710472802),
(5, 0, 'C', '工作台', 'el-icon-Monitor', 1000, 'workbench/index', 'workbench', 'workbench/index', '', '', 0, 1, 0, 1656664793, 1664354981),
(6, 4, 'C', '菜单', 'el-icon-Operation', 100, 'auth.menu/lists', 'menu', 'permission/menu/index', '', '', 1, 1, 0, 1656664960, 1710472994),
(7, 4, 'C', '管理员', 'local-icon-shouyiren', 80, 'auth.admin/lists', 'admin', 'permission/admin/index', '', '', 0, 1, 0, 1656901567, 1710473013),
(8, 4, 'C', '角色', 'el-icon-Female', 90, 'auth.role/lists', 'role', 'permission/role/index', '', '', 0, 1, 0, 1656901660, 1710473000),
(12, 8, 'A', '新增', '', 1, 'auth.role/add', '', '', '', '', 0, 1, 0, 1657001790, 1663750625),
(14, 8, 'A', '编辑', '', 1, 'auth.role/edit', '', '', '', '', 0, 1, 0, 1657001924, 1663750631),
(15, 8, 'A', '删除', '', 1, 'auth.role/delete', '', '', '', '', 0, 1, 0, 1657001982, 1663750637),
(16, 6, 'A', '新增', '', 1, 'auth.menu/add', '', '', '', '', 0, 1, 0, 1657072523, 1663750565),
(17, 6, 'A', '编辑', '', 1, 'auth.menu/edit', '', '', '', '', 0, 1, 0, 1657073955, 1663750570),
(18, 6, 'A', '删除', '', 1, 'auth.menu/delete', '', '', '', '', 0, 1, 0, 1657073987, 1663750578),
(19, 7, 'A', '新增', '', 1, 'auth.admin/add', '', '', '', '', 0, 1, 0, 1657074035, 1663750596),
(20, 7, 'A', '编辑', '', 1, 'auth.admin/edit', '', '', '', '', 0, 1, 0, 1657074071, 1663750603),
(21, 7, 'A', '删除', '', 1, 'auth.admin/delete', '', '', '', '', 0, 1, 0, 1657074108, 1663750609),
(23, 28, 'M', '开发工具', 'el-icon-EditPen', 40, '', 'dev_tools', '', '', '', 0, 1, 0, 1657097744, 1710473127),
(24, 23, 'C', '代码生成器', 'el-icon-DocumentAdd', 1, 'tools.generator/generateTable', 'code', 'dev_tools/code/index', '', '', 0, 1, 0, 1657098110, 1658989423),
(25, 0, 'M', '组织管理', 'el-icon-OfficeBuilding', 400, '', 'organization', '', '', '', 0, 1, 0, 1657099914, 1710472797),
(26, 25, 'C', '部门管理', 'el-icon-Coordinate', 100, 'dept.dept/lists', 'department', 'organization/department/index', '', '', 1, 1, 0, 1657099989, 1710472962),
(27, 25, 'C', '岗位管理', 'el-icon-PriceTag', 90, 'dept.jobs/lists', 'post', 'organization/post/index', '', '', 0, 1, 0, 1657100044, 1710472967),
(28, 0, 'M', '系统设置', 'el-icon-Setting', 200, '', 'setting', '', '', '', 0, 1, 0, 1657100164, 1710472807),
(29, 28, 'M', '网站设置', 'el-icon-Basketball', 100, '', 'website', '', '', '', 0, 1, 0, 1657100230, 1710473049),
(30, 29, 'C', '网站信息', '', 1, 'setting.web.web_setting/getWebsite', 'information', 'setting/website/information', '', '', 0, 1, 0, 1657100306, 1657164412),
(31, 29, 'C', '网站备案', '', 1, 'setting.web.web_setting/getCopyright', 'filing', 'setting/website/filing', '', '', 0, 1, 0, 1657100434, 1657164723),
(32, 29, 'C', '政策协议', '', 1, 'setting.web.web_setting/getAgreement', 'protocol', 'setting/website/protocol', '', '', 0, 1, 0, 1657100571, 1657164770),
(33, 28, 'C', '存储设置', 'el-icon-FolderOpened', 70, 'setting.storage/lists', 'storage', 'setting/storage/index', '', '', 0, 1, 0, 1657160959, 1710473095),
(34, 23, 'C', '字典管理', 'el-icon-Box', 1, 'setting.dict.dict_type/lists', 'dict', 'setting/dict/type/index', '', '', 0, 1, 0, 1657161211, 1663225935),
(35, 28, 'M', '系统维护', 'el-icon-SetUp', 50, '', 'system', '', '', '', 0, 1, 0, 1657161569, 1710473122),
(36, 35, 'C', '系统日志', '', 90, 'setting.system.log/lists', 'journal', 'setting/system/journal', '', '', 0, 1, 0, 1657161696, 1710473253),
(37, 35, 'C', '系统缓存', '', 80, '', 'cache', 'setting/system/cache', '', '', 0, 1, 0, 1657161896, 1710473258),
(38, 35, 'C', '系统环境', '', 70, 'setting.system.system/info', 'environment', 'setting/system/environment', '', '', 0, 1, 0, 1657162000, 1710473265),
(39, 24, 'A', '导入数据表', '', 1, 'tools.generator/selectTable', '', '', '', '', 0, 1, 0, 1657162736, 1657162736),
(40, 24, 'A', '代码生成', '', 1, 'tools.generator/generate', '', '', '', '', 0, 1, 0, 1657162806, 1657162806),
(41, 23, 'C', '编辑数据表', '', 1, 'tools.generator/edit', 'code/edit', 'dev_tools/code/edit', '/dev_tools/code', '', 1, 0, 0, 1657162866, 1663748668),
(42, 24, 'A', '同步表结构', '', 1, 'tools.generator/syncColumn', '', '', '', '', 0, 1, 0, 1657162934, 1657162934),
(43, 24, 'A', '删除数据表', '', 1, 'tools.generator/delete', '', '', '', '', 0, 1, 0, 1657163015, 1657163015),
(44, 24, 'A', '预览代码', '', 1, 'tools.generator/preview', '', '', '', '', 0, 1, 0, 1657163263, 1657163263),
(45, 26, 'A', '新增', '', 1, 'dept.dept/add', '', '', '', '', 0, 1, 0, 1657163548, 1663750492),
(46, 26, 'A', '编辑', '', 1, 'dept.dept/edit', '', '', '', '', 0, 1, 0, 1657163599, 1663750498),
(47, 26, 'A', '删除', '', 1, 'dept.dept/delete', '', '', '', '', 0, 1, 0, 1657163687, 1663750504),
(48, 27, 'A', '新增', '', 1, 'dept.jobs/add', '', '', '', '', 0, 1, 0, 1657163778, 1663750524),
(49, 27, 'A', '编辑', '', 1, 'dept.jobs/edit', '', '', '', '', 0, 1, 0, 1657163800, 1663750530),
(50, 27, 'A', '删除', '', 1, 'dept.jobs/delete', '', '', '', '', 0, 1, 0, 1657163820, 1663750535),
(51, 30, 'A', '保存', '', 1, 'setting.web.web_setting/setWebsite', '', '', '', '', 0, 1, 0, 1657164469, 1663750649),
(52, 31, 'A', '保存', '', 1, 'setting.web.web_setting/setCopyright', '', '', '', '', 0, 1, 0, 1657164692, 1663750657),
(53, 32, 'A', '保存', '', 1, 'setting.web.web_setting/setAgreement', '', '', '', '', 0, 1, 0, 1657164824, 1663750665),
(54, 33, 'A', '设置', '', 1, 'setting.storage/setup', '', '', '', '', 0, 1, 0, 1657165303, 1663750673),
(55, 34, 'A', '新增', '', 1, 'setting.dict.dict_type/add', '', '', '', '', 0, 1, 0, 1657166966, 1663750783),
(56, 34, 'A', '编辑', '', 1, 'setting.dict.dict_type/edit', '', '', '', '', 0, 1, 0, 1657166997, 1663750789),
(57, 34, 'A', '删除', '', 1, 'setting.dict.dict_type/delete', '', '', '', '', 0, 1, 0, 1657167038, 1663750796),
(58, 62, 'A', '新增', '', 1, 'setting.dict.dict_data/add', '', '', '', '', 0, 1, 0, 1657167317, 1663750758),
(59, 62, 'A', '编辑', '', 1, 'setting.dict.dict_data/edit', '', '', '', '', 0, 1, 0, 1657167371, 1663750751),
(60, 62, 'A', '删除', '', 1, 'setting.dict.dict_data/delete', '', '', '', '', 0, 1, 0, 1657167397, 1663750768),
(61, 37, 'A', '清除系统缓存', '', 1, 'setting.system.cache/clear', '', '', '', '', 0, 1, 0, 1657173837, 1657173939),
(62, 23, 'C', '字典数据管理', '', 1, 'setting.dict.dict_data/lists', 'dict/data', 'setting/dict/data/index', '/dev_tools/dict', '', 1, 0, 0, 1657174351, 1663745617),
(63, 158, 'M', '素材管理', 'el-icon-Picture', 0, '', 'material', '', '', '', 0, 1, 0, 1657507133, 1710472243),
(64, 63, 'C', '素材中心', 'el-icon-PictureRounded', 0, '', 'index', 'material/index', '', '', 0, 1, 0, 1657507296, 1664355653),
(66, 26, 'A', '详情', '', 0, 'dept.dept/detail', '', '', '', '', 0, 1, 0, 1663725459, 1663750516),
(67, 27, 'A', '详情', '', 0, 'dept.jobs/detail', '', '', '', '', 0, 1, 0, 1663725514, 1663750559),
(68, 6, 'A', '详情', '', 0, 'auth.menu/detail', '', '', '', '', 0, 1, 0, 1663725564, 1663750584),
(69, 7, 'A', '详情', '', 0, 'auth.admin/detail', '', '', '', '', 0, 1, 0, 1663725623, 1663750615),
(70, 158, 'M', '文章资讯', 'el-icon-ChatLineSquare', 90, '', 'article', '', '', '', 0, 1, 0, 1663749965, 1710471867),
(71, 70, 'C', '文章管理', 'el-icon-ChatDotSquare', 0, 'article.article/lists', 'lists', 'article/lists/index', '', '', 0, 1, 0, 1663750101, 1664354615),
(72, 70, 'C', '文章添加/编辑', '', 0, 'article.article/add:edit', 'lists/edit', 'article/lists/edit', '/article/lists', '', 0, 0, 0, 1663750153, 1664356275),
(73, 70, 'C', '文章栏目', 'el-icon-CollectionTag', 0, 'article.articleCate/lists', 'column', 'article/column/index', '', '', 1, 1, 0, 1663750287, 1664354678),
(74, 71, 'A', '新增', '', 0, 'article.article/add', '', '', '', '', 0, 1, 0, 1663750335, 1663750335),
(75, 71, 'A', '详情', '', 0, 'article.article/detail', '', '', '', '', 0, 1, 0, 1663750354, 1663750383),
(76, 71, 'A', '删除', '', 0, 'article.article/delete', '', '', '', '', 0, 1, 0, 1663750413, 1663750413),
(77, 71, 'A', '修改状态', '', 0, 'article.article/updateStatus', '', '', '', '', 0, 1, 0, 1663750442, 1663750442),
(78, 73, 'A', '添加', '', 0, 'article.articleCate/add', '', '', '', '', 0, 1, 0, 1663750483, 1663750483),
(79, 73, 'A', '删除', '', 0, 'article.articleCate/delete', '', '', '', '', 0, 1, 0, 1663750895, 1663750895),
(80, 73, 'A', '详情', '', 0, 'article.articleCate/detail', '', '', '', '', 0, 1, 0, 1663750913, 1663750913),
(81, 73, 'A', '修改状态', '', 0, 'article.articleCate/updateStatus', '', '', '', '', 0, 1, 0, 1663750936, 1663750936),
(82, 0, 'M', '渠道设置', 'el-icon-Message', 500, '', 'channel', '', '', '', 0, 1, 0, 1663754084, 1710472649),
(83, 82, 'C', 'h5设置', 'el-icon-Cellphone', 100, 'channel.web_page_setting/getConfig', 'h5', 'channel/h5', '', '', 0, 1, 0, 1663754158, 1710472929),
(84, 83, 'A', '保存', '', 0, 'channel.web_page_setting/setConfig', '', '', '', '', 0, 1, 0, 1663754259, 1663754259),
(85, 82, 'M', '微信公众号', 'local-icon-dingdan', 80, '', 'wx_oa', '', '', '', 0, 1, 0, 1663755470, 1710472946),
(86, 85, 'C', '公众号配置', '', 0, 'channel.official_account_setting/getConfig', 'config', 'channel/wx_oa/config', '', '', 0, 1, 0, 1663755663, 1664355450),
(87, 85, 'C', '菜单管理', '', 0, 'channel.official_account_menu/detail', 'menu', 'channel/wx_oa/menu', '', '', 0, 1, 0, 1663755767, 1664355456),
(88, 86, 'A', '保存', '', 0, 'channel.official_account_setting/setConfig', '', '', '', '', 0, 1, 0, 1663755799, 1663755799),
(89, 86, 'A', '保存并发布', '', 0, 'channel.official_account_menu/save', '', '', '', '', 0, 1, 0, 1663756490, 1663756490),
(90, 85, 'C', '关注回复', '', 0, 'channel.official_account_reply/lists', 'follow', 'channel/wx_oa/reply/follow_reply', '', '', 0, 1, 0, 1663818358, 1663818366),
(91, 85, 'C', '关键字回复', '', 0, '', 'keyword', 'channel/wx_oa/reply/keyword_reply', '', '', 0, 1, 0, 1663818445, 1663818445),
(93, 85, 'C', '默认回复', '', 0, '', 'default', 'channel/wx_oa/reply/default_reply', '', '', 0, 1, 0, 1663818580, 1663818580),
(94, 82, 'C', '微信小程序', 'local-icon-weixin', 90, 'channel.mnp_settings/getConfig', 'weapp', 'channel/weapp', '', '', 0, 1, 0, 1663831396, 1710472941),
(95, 94, 'A', '保存', '', 0, 'channel.mnp_settings/setConfig', '', '', '', '', 0, 1, 0, 1663831436, 1663831436),
(96, 0, 'M', '装修管理', 'el-icon-Brush', 600, '', 'decoration', '', '', '', 0, 1, 0, 1663834825, 1710472099),
(97, 175, 'C', '页面装修', 'el-icon-CopyDocument', 100, 'decorate.page/detail', 'pages', 'decoration/pages/index', '', '', 0, 1, 0, 1663834879, 1710929256),
(98, 97, 'A', '保存', '', 0, 'decorate.page/save', '', '', '', '', 0, 1, 0, 1663834956, 1663834956),
(99, 175, 'C', '底部导航', 'el-icon-Position', 90, 'decorate.tabbar/detail', 'tabbar', 'decoration/tabbar', '', '', 0, 1, 0, 1663835004, 1710929262),
(100, 99, 'A', '保存', '', 0, 'decorate.tabbar/save', '', '', '', '', 0, 1, 0, 1663835018, 1663835018),
(101, 158, 'M', '消息管理', 'el-icon-ChatDotRound', 80, '', 'message', '', '', '', 0, 1, 0, 1663838602, 1710471874),
(102, 101, 'C', '通知设置', '', 0, 'notice.notice/settingLists', 'notice', 'message/notice/index', '', '', 0, 1, 0, 1663839195, 1663839195),
(103, 102, 'A', '详情', '', 0, 'notice.notice/detail', '', '', '', '', 0, 1, 0, 1663839537, 1663839537),
(104, 101, 'C', '通知设置编辑', '', 0, 'notice.notice/set', 'notice/edit', 'message/notice/edit', '/message/notice', '', 0, 0, 0, 1663839873, 1663898477),
(105, 71, 'A', '编辑', '', 0, 'article.article/edit', '', '', '', '', 0, 1, 0, 1663840043, 1663840053),
(107, 101, 'C', '短信设置', '', 0, 'notice.sms_config/getConfig', 'short_letter', 'message/short_letter/index', '', '', 0, 1, 0, 1663898591, 1664355708),
(108, 107, 'A', '设置', '', 0, 'notice.sms_config/setConfig', '', '', '', '', 0, 1, 0, 1663898644, 1663898644),
(109, 107, 'A', '详情', '', 0, 'notice.sms_config/detail', '', '', '', '', 0, 1, 0, 1663898661, 1663898661),
(110, 28, 'C', '热门搜索', 'el-icon-Search', 60, 'setting.hot_search/getConfig', 'search', 'setting/search/index', '', '', 0, 1, 0, 1663901821, 1710473109),
(111, 110, 'A', '保存', '', 0, 'setting.hot_search/setConfig', '', '', '', '', 0, 1, 0, 1663901856, 1663901856),
(112, 28, 'M', '用户设置', 'local-icon-keziyuyue', 90, '', 'user', '', '', '', 0, 1, 0, 1663903302, 1710473056),
(113, 112, 'C', '用户设置', '', 0, 'setting.user.user/getConfig', 'setup', 'setting/user/setup', '', '', 0, 1, 0, 1663903506, 1663903506),
(114, 113, 'A', '保存', '', 0, 'setting.user.user/setConfig', '', '', '', '', 0, 1, 0, 1663903522, 1663903522),
(115, 112, 'C', '登录注册', '', 0, 'setting.user.user/getRegisterConfig', 'login_register', 'setting/user/login_register', '', '', 0, 1, 0, 1663903832, 1663903832),
(116, 115, 'A', '保存', '', 0, 'setting.user.user/setRegisterConfig', '', '', '', '', 0, 1, 0, 1663903852, 1663903852),
(117, 0, 'M', '用户管理', 'el-icon-User', 900, '', 'consumer', '', '', '', 0, 1, 0, 1663904351, 1710472074),
(118, 117, 'C', '用户列表', 'local-icon-user_guanli', 100, 'user.user/lists', 'lists', 'consumer/lists/index', '', '', 0, 1, 0, 1663904392, 1710471845),
(119, 117, 'C', '用户详情', '', 90, 'user.user/detail', 'lists/detail', 'consumer/lists/detail', '/consumer/lists', '', 0, 0, 0, 1663904470, 1710471851),
(120, 119, 'A', '编辑', '', 0, 'user.user/edit', '', '', '', '', 0, 1, 0, 1663904499, 1663904499),
(140, 82, 'C', '微信开发平台', 'local-icon-notice_buyer', 70, 'channel.open_setting/getConfig', 'open_setting', 'channel/open_setting', '', '', 0, 1, 0, 1666085713, 1710472951),
(141, 140, 'A', '保存', '', 0, 'channel.open_setting/setConfig', '', '', '', '', 0, 1, 0, 1666085751, 1666085776),
(142, 176, 'C', 'PC端装修', 'el-icon-Monitor', 8, '', 'pc', 'decoration/pc', '', '', 0, 1, 0, 1668423284, 1710901602),
(143, 35, 'C', '定时任务', '', 100, 'crontab.crontab/lists', 'scheduled_task', 'setting/system/scheduled_task/index', '', '', 0, 1, 0, 1669357509, 1710473246),
(144, 35, 'C', '定时任务添加/编辑', '', 0, 'crontab.crontab/add:edit', 'scheduled_task/edit', 'setting/system/scheduled_task/edit', '/setting/system/scheduled_task', '', 0, 0, 0, 1669357670, 1669357765),
(145, 143, 'A', '添加', '', 0, 'crontab.crontab/add', '', '', '', '', 0, 1, 0, 1669358282, 1669358282),
(146, 143, 'A', '编辑', '', 0, 'crontab.crontab/edit', '', '', '', '', 0, 1, 0, 1669358303, 1669358303),
(147, 143, 'A', '删除', '', 0, 'crontab.crontab/delete', '', '', '', '', 0, 1, 0, 1669358334, 1669358334),
(148, 0, 'M', '模板示例', 'el-icon-SetUp', 100, '', 'template', '', '', '', 0, 1, 0, 1670206819, 1710472811),
(149, 148, 'M', '组件示例', 'el-icon-Coin', 0, '', 'component', '', '', '', 0, 1, 0, 1670207182, 1670207244),
(150, 149, 'C', '富文本', '', 90, '', 'rich_text', 'template/component/rich_text', '', '', 0, 1, 0, 1670207751, 1710473315),
(151, 149, 'C', '上传文件', '', 80, '', 'upload', 'template/component/upload', '', '', 0, 1, 0, 1670208925, 1710473322),
(152, 149, 'C', '图标', '', 100, '', 'icon', 'template/component/icon', '', '', 0, 1, 0, 1670230069, 1710473306),
(153, 149, 'C', '文件选择器', '', 60, '', 'file', 'template/component/file', '', '', 0, 1, 0, 1670232129, 1710473341),
(154, 149, 'C', '链接选择器', '', 50, '', 'link', 'template/component/link', '', '', 0, 1, 0, 1670292636, 1710473346),
(155, 149, 'C', '超出自动打点', '', 40, '', 'overflow', 'template/component/overflow', '', '', 0, 1, 0, 1670292883, 1710473351),
(156, 149, 'C', '悬浮input', '', 70, '', 'popover_input', 'template/component/popover_input', '', '', 0, 1, 0, 1670293336, 1710473329),
(157, 119, 'A', '余额调整', '', 0, 'user.user/adjustMoney', '', '', '', '', 0, 1, 0, 1677143088, 1677143088),
(158, 0, 'M', '应用管理', 'el-icon-Postcard', 800, '', 'app', '', '', '', 0, 1, 0, 1677143430, 1710472079),
(159, 158, 'C', '用户充值', 'local-icon-fukuan', 100, 'recharge.recharge/getConfig', 'recharge', 'app/recharge/index', '', '', 0, 1, 0, 1677144284, 1710471860),
(160, 159, 'A', '保存', '', 0, 'recharge.recharge/setConfig', '', '', '', '', 0, 1, 0, 1677145012, 1677145012),
(161, 28, 'M', '支付设置', 'local-icon-set_pay', 80, '', 'pay', '', '', '', 0, 1, 0, 1677148075, 1710473061),
(162, 161, 'C', '支付方式', '', 0, 'setting.pay.pay_way/getPayWay', 'method', 'setting/pay/method/index', '', '', 0, 1, 0, 1677148207, 1677148207),
(163, 161, 'C', '支付配置', '', 0, 'setting.pay.pay_config/lists', 'config', 'setting/pay/config/index', '', '', 0, 1, 0, 1677148260, 1677148374),
(164, 162, 'A', '设置支付方式', '', 0, 'setting.pay.pay_way/setPayWay', '', '', '', '', 0, 1, 0, 1677219624, 1677219624),
(165, 163, 'A', '配置', '', 0, 'setting.pay.pay_config/setConfig', '', '', '', '', 0, 1, 0, 1677219655, 1677219655),
(166, 0, 'M', '财务管理', 'local-icon-user_gaikuang', 700, '', 'finance', '', '', '', 0, 1, 0, 1677552269, 1710472085),
(167, 166, 'C', '充值记录', 'el-icon-Wallet', 90, 'recharge.recharge/lists', 'recharge_record', 'finance/recharge_record', '', '', 0, 1, 0, 1677552757, 1710472902),
(168, 166, 'C', '余额明细', 'local-icon-qianbao', 100, 'finance.account_log/lists', 'balance_details', 'finance/balance_details', '', '', 0, 1, 0, 1677552976, 1710472894),
(169, 167, 'A', '退款', '', 0, 'recharge.recharge/refund', '', '', '', '', 0, 1, 0, 1677809715, 1677809715),
(170, 166, 'C', '退款记录', 'local-icon-heshoujilu', 0, 'finance.refund/record', 'refund_record', 'finance/refund_record', '', '', 0, 1, 0, 1677811271, 1677811271),
(171, 170, 'A', '重新退款', '', 0, 'recharge.recharge/refundAgain', '', '', '', '', 0, 1, 0, 1677811295, 1677811295),
(172, 170, 'A', '退款日志', '', 0, 'finance.refund/log', '', '', '', '', 0, 1, 0, 1677811361, 1677811361),
(173, 175, 'C', '系统风格', 'el-icon-Brush', 80, '', 'style', 'decoration/style/style', '', '', 0, 1, 0, 1681635044, 1710929278),
(175, 96, 'M', '移动端', '', 100, '', 'mobile', '', '', '', 0, 1, 0, 1710901543, 1710929294),
(176, 96, 'M', 'PC端', '', 90, '', 'pc', '', '', '', 0, 1, 0, 1710901592, 1710929299),
(177, 29, 'C', '站点统计', '', 0, 'setting.web.web_setting/getSiteStatistics', 'statistics', 'setting/website/statistics', '', '', 0, 1, 0, 1726841481, 1726843434),
(178, 177, 'A', '保存', '', 0, 'setting.web.web_setting/saveSiteStatistics', '', '', '', '', 1, 1, 0, 1726841507, 1726841507),
(179, 0, 'M', '服务管理', 'el-icon-User', 900, '', 'service', '', '', '', 0, 1, 0, 1773413107, 1773413107),
(180, 179, 'C', '服务人员', '', 100, 'ops.staff/lists', 'staff', 'staff/lists/index', '', '', 0, 1, 0, 1773413107, 1773413107),
(181, 179, 'C', '服务分类', '', 95, 'ops.category/lists', 'category', 'service/category/index', '', '', 0, 1, 0, 1773413107, 1773413107),
(182, 179, 'C', '服务套餐', '', 90, 'ops.package/lists', 'package', 'service/package/index', '', '', 0, 1, 0, 1773413107, 1773413107),
(183, 0, 'M', '档期管理', 'el-icon-Calendar', 850, '', 'schedule', '', '', '', 0, 1, 0, 1773413107, 1773413107),
(184, 183, 'C', '档期日历', '', 100, 'ops.schedule/lists', 'calendar', 'schedule/calendar/index', '', '', 0, 1, 0, 1773413107, 1773413107),
(186, 183, 'C', '候补列表', '', 80, 'ops.waitlist/lists', 'waitlist', 'schedule/waitlist/index', '', '', 0, 1, 0, 1773413107, 1773413107),
(187, 0, 'M', '订单管理', 'el-icon-Document', 800, '', 'order', '', '', '', 0, 1, 0, 1773413107, 1773413107),
(188, 187, 'C', '订单列表', '', 100, 'ops.order/lists', 'lists', 'order/lists/index', '', '', 0, 1, 0, 1773413107, 1773413107),
(189, 187, 'C', '退款管理', '', 90, 'ops.refund/lists', 'refund', 'order/refund/index', '', '', 0, 1, 0, 1773413107, 1773413107),
(190, 187, 'C', '订单变更', '', 80, 'ops.orderChange/lists', 'change', 'order/change/index', '', '', 0, 1, 0, 1773413107, 1773413107),
(192, 187, 'C', '订单暂停', '', 60, 'ops.orderPause/lists', 'pause', 'order/pause/index', '', '', 0, 1, 0, 1773413107, 1773413107),
(193, 0, 'M', '动态社区', 'el-icon-ChatDotRound', 750, '', 'dynamic', '', '', '', 0, 1, 0, 1773413107, 1773413107),
(194, 193, 'C', '动态列表', '', 100, 'growth.dynamic/lists', 'lists', 'dynamic/lists/index', '', '', 0, 1, 0, 1773413107, 1773413107),
(195, 0, 'M', '评价管理', 'el-icon-Star', 700, '', 'review', '', '', '', 0, 1, 0, 1773413107, 1773413107),
(196, 195, 'C', '评价列表', '', 100, 'growth.review/lists', 'lists', 'review/lists/index', '', '', 0, 1, 0, 1773413107, 1773413107),
(197, 0, 'M', '财务管理', 'el-icon-Coin', 650, '', 'financial', '', '', '', 0, 1, 0, 1773413107, 1773413107),
(198, 197, 'C', '资金流水', '', 100, 'finance.flow/lists', 'flow', 'financial/flow/index', '', '', 0, 1, 0, 1773413107, 1773413107),
(199, 197, 'C', '结算管理', '', 80, 'finance.settlement/lists', 'settlement', 'financial/settlement/index', '', '', 0, 1, 0, 1773413107, 1773413107),
(200, 197, 'C', '成本管理', '', 70, 'finance.cost/lists', 'cost', 'financial/cost/index', '', '', 0, 0, 1, 1773413107, 1773556013),
(201, 197, 'C', '发票管理', '', 60, 'finance.invoice/lists', 'invoice', 'financial/invoice/index', '', '', 0, 0, 1, 1773413107, 1773556013),
(206, 0, 'M', '售后服务', 'el-icon-Service', 550, '', 'aftersale', '', '', '', 0, 0, 1, 1773413107, 1773556013),
(207, 206, 'C', '售后工单', '', 100, 'ops.aftersaleTicket/lists', 'ticket', 'aftersale/ticket/index', '', '', 0, 0, 1, 1773413107, 1773556013),
(210, 0, 'M', '消息中心', 'el-icon-Bell', 450, '', 'message', '', '', '', 0, 1, 0, 1773413107, 1773413107),
(211, 210, 'C', '消息通知', '', 100, 'growth.notification/lists', 'notification', 'notification/lists/index', '', '', 0, 1, 0, 1773413107, 1773413107),
(212, 210, 'C', '订阅消息', '', 90, 'growth.subscribe/templateList', 'subscribe', 'subscribe/template/index', '', '', 0, 1, 0, 1773413107, 1773413107),
(215, 179, 'C', '服务人员添加/编辑', '', 99, 'ops.staff/add:edit', 'staff/edit', 'staff/lists/edit', '/service/staff', '', 0, 0, 0, 1773413108, 1773413108),
(216, 193, 'C', '评论审核', '', 90, 'growth.dynamicComment/reviewList', 'comment/review', 'dynamic/comment/review', '', '', 0, 1, 0, 1773413108, 1773413108),
(217, 193, 'C', '动态配置', '', 80, 'growth.dynamicComment/getReviewConfig', 'config', 'dynamic/config/index', '', '', 0, 1, 0, 1773413108, 1773413108),
(218, 179, 'C', '服务人员标签', '', 70, 'ops.styleTag/lists', 'tag', 'service/tag/index', '', '', 0, 1, 0, 1773413108, 1773413108),
(219, 218, 'A', '新增', '', 0, 'ops.styleTag/add', '', '', '', '', 0, 0, 0, 1773413108, 1773413108),
(220, 218, 'A', '编辑', '', 0, 'ops.styleTag/edit', '', '', '', '', 0, 0, 0, 1773413108, 1773413108),
(221, 218, 'A', '删除', '', 0, 'ops.styleTag/delete', '', '', '', '', 0, 0, 0, 1773413108, 1773413108),
(222, 218, 'A', '状态切换', '', 0, 'ops.styleTag/changeStatus', '', '', '', '', 0, 0, 0, 1773413108, 1773413108),
(223, 0, 'M', '服务人员中心', 'el-icon-User', 720, '', 'staff-center', '', '', '', 0, 1, 0, 1773413108, 1773413108),
(224, 223, 'C', '我的资料', '', 100, 'ops.staff/myProfile', 'profile', 'staff_center/profile/index', '', '', 0, 1, 0, 1773413108, 1773413108),
(225, 223, 'C', '档期日历', '', 90, 'ops.schedule/myCalendar', 'calendar', 'staff_center/calendar/index', '', '', 0, 1, 0, 1773413108, 1773413108),
(226, 223, 'C', '档期规则', '', 80, 'ops.scheduleRule/myRules', 'rule', 'staff_center/rule/index', '', '', 0, 1, 0, 1773413108, 1773413108),
(227, 223, 'C', '预约列表', '', 70, 'ops.booking/myBookings', 'booking', 'staff_center/booking/index', '', '', 0, 1, 0, 1773413108, 1773556013),
(228, 223, 'C', '候补列表', '', 60, 'ops.waitlist/myWaitlist', 'waitlist', 'staff_center/waitlist/index', '', '', 0, 1, 0, 1773413108, 1773413108),
(229, 223, 'C', '订单列表', '', 50, 'ops.order/myOrders', 'order', 'staff_center/order/index', '', '', 0, 1, 0, 1773413108, 1773413108),
(230, 223, 'C', '动态管理', '', 40, 'growth.dynamic/myDynamics', 'dynamic', 'staff_center/dynamic/index', '', '', 0, 1, 0, 1773413108, 1773413108),
(231, 0, 'M', '作品管理', 'el-icon-Picture', 730, '', 'staff_work', '', '', '', 0, 1, 0, 1773413108, 1773413108),
(232, 231, 'C', '作品列表', '', 100, 'ops.work/lists', 'lists', 'staff/work/index', '', '', 0, 1, 0, 1773413108, 1773413108),
(233, 232, 'A', '详情', '', 0, 'ops.work/detail', '', '', '', '', 0, 1, 0, 1773413108, 1773413108),
(234, 232, 'A', '审核', '', 0, 'ops.work/audit', '', '', '', '', 0, 1, 0, 1773413108, 1773413108),
(235, 232, 'A', '修改状态', '', 0, 'ops.work/changeStatus', '', '', '', '', 0, 1, 0, 1773413108, 1773413108),
(236, 232, 'A', '设为封面', '', 0, 'ops.work/setCover', '', '', '', '', 0, 1, 0, 1773413108, 1773413108),
(237, 232, 'A', '删除', '', 0, 'ops.work/delete', '', '', '', '', 0, 1, 0, 1773413108, 1773413108),
(238, 28, 'C', '功能开关', 'el-icon-Switch', 55, 'setting.feature_switch/getConfig', 'feature_switch', 'setting/feature_switch/index', '', '', 0, 1, 0, 1773413108, 1773413108),
(239, 238, 'A', '保存', '', 0, 'setting.feature_switch/setConfig', '', '', '', '', 0, 1, 0, 1773413108, 1773413108),
(240, 179, 'C', '服务地区', '', 85, 'ops.region/lists', 'region', 'service/region/index', '', '', 0, 1, 0, 1773650000, 1773650000),
(241, 240, 'A', '城市选项', '', 0, 'ops.region/cityOptions', '', '', '', '', 0, 0, 0, 1773650000, 1773650000),
(242, 240, 'A', '启用城市选项', '', 0, 'ops.region/enabledCityOptions', '', '', '', '', 0, 0, 0, 1773650000, 1773650000),
(243, 240, 'A', '区县选项', '', 0, 'ops.region/districtOptions', '', '', '', '', 0, 0, 0, 1773650000, 1773650000),
(244, 240, 'A', '新增', '', 0, 'ops.region/add', '', '', '', '', 0, 0, 0, 1773650000, 1773650000),
(245, 240, 'A', '编辑', '', 0, 'ops.region/edit', '', '', '', '', 0, 0, 0, 1773650000, 1773650000),
(246, 240, 'A', '删除', '', 0, 'ops.region/delete', '', '', '', '', 0, 0, 0, 1773650000, 1773650000),
(247, 240, 'A', '状态切换', '', 0, 'ops.region/changeStatus', '', '', '', '', 0, 0, 0, 1773650000, 1773650000),
(255, 179, 'C', '标签审核', '', 195, 'ops.staffTagReview/lists', 'staff-tag-review', 'staff/tag_review/index', '', '', 0, 1, 0, 1775001600, 1775001600),
(256, 255, 'A', '详情', '', 10, 'ops.staffTagReview/detail', '', '', '', '', 0, 0, 0, 1775001600, 1775001600),
(257, 255, 'A', '审核通过', '', 20, 'ops.staffTagReview/approve', '', '', '', '', 0, 0, 0, 1775001600, 1775001600),
(258, 255, 'A', '审核拒绝', '', 30, 'ops.staffTagReview/reject', '', '', '', '', 0, 0, 0, 1775001600, 1775001600),
(248, 179, 'C', '附加服务', '', 75, 'ops.addon/lists', 'addon', 'service/addon/index', '', '', 0, 0, 1, 1773650000, 1773650000),
(249, 248, 'A', '详情', '', 0, 'ops.addon/detail', '', '', '', '', 0, 0, 1, 1773650000, 1773650000),
(250, 248, 'A', '新增', '', 0, 'ops.addon/add', '', '', '', '', 0, 0, 1, 1773650000, 1773650000),
(251, 248, 'A', '编辑', '', 0, 'ops.addon/edit', '', '', '', '', 0, 0, 1, 1773650000, 1773650000),
(252, 248, 'A', '删除', '', 0, 'ops.addon/delete', '', '', '', '', 0, 0, 1, 1773650000, 1773650000),
(253, 248, 'A', '状态切换', '', 0, 'ops.addon/changeStatus', '', '', '', '', 0, 0, 1, 1773650000, 1773650000),
(254, 248, 'A', '全部', '', 0, 'ops.addon/all', '', '', '', '', 0, 0, 1, 1773650000, 1773650000);

-- la_system_role_menu
INSERT INTO `la_system_role_menu` (`role_id`, `menu_id`) VALUES
(1, 223),
(1, 224),
(1, 225),
(1, 226),
(1, 227),
(1, 228),
(1, 229),
(1, 230),
(2, 4),
(2, 5),
(2, 6),
(2, 7),
(2, 8),
(2, 12),
(2, 14),
(2, 15),
(2, 16),
(2, 17),
(2, 18),
(2, 19),
(2, 20),
(2, 21),
(2, 28),
(2, 29),
(2, 30),
(2, 31),
(2, 32),
(2, 33),
(2, 35),
(2, 36),
(2, 37),
(2, 38),
(2, 51),
(2, 52),
(2, 53),
(2, 54),
(2, 61),
(2, 63),
(2, 64),
(2, 68),
(2, 69),
(2, 70),
(2, 71),
(2, 72),
(2, 73),
(2, 74),
(2, 75),
(2, 76),
(2, 77),
(2, 78),
(2, 79),
(2, 80),
(2, 81),
(2, 82),
(2, 83),
(2, 84),
(2, 94),
(2, 95),
(2, 96),
(2, 97),
(2, 98),
(2, 99),
(2, 100),
(2, 101),
(2, 102),
(2, 103),
(2, 104),
(2, 105),
(2, 107),
(2, 108),
(2, 109),
(2, 110),
(2, 111),
(2, 112),
(2, 113),
(2, 114),
(2, 115),
(2, 116),
(2, 117),
(2, 118),
(2, 119),
(2, 120),
(2, 143),
(2, 144),
(2, 145),
(2, 146),
(2, 147),
(2, 157),
(2, 158),
(2, 159),
(2, 160),
(2, 161),
(2, 162),
(2, 163),
(2, 164),
(2, 165),
(2, 166),
(2, 167),
(2, 168),
(2, 169),
(2, 170),
(2, 171),
(2, 172),
(2, 173),
(2, 175),
(2, 177),
(2, 178),
(2, 179),
(2, 180),
(2, 181),
(2, 182),
(2, 183),
(2, 184),
(2, 186),
(2, 187),
(2, 188),
(2, 189),
(2, 190),
(2, 192),
(2, 193),
(2, 194),
(2, 195),
(2, 196),
(2, 197),
(2, 198),
(2, 199),
(2, 210),
(2, 211),
(2, 212),
(2, 215),
(2, 216),
(2, 217),
(2, 218),
(2, 219),
(2, 220),
(2, 221),
(2, 222),
(2, 231),
(2, 232),
(2, 233),
(2, 234),
(2, 235),
(2, 236),
(2, 237),
(2, 240),
(2, 241),
(2, 242),
(2, 243),
(2, 244),
(2, 245),
(2, 246),
(2, 247),
(2, 255),
(2, 256),
(2, 257),
(2, 258),
(2, 238),
(2, 239);

-- la_config feature_switch
DELETE FROM `la_config` WHERE `type` = 'feature_switch';
INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`) VALUES
('feature_switch', 'admin_dashboard', '1', 1773413108, 1773413108),
('feature_switch', 'admin_dashboard_user_ids', '1', 1773556898, 1773556898),
('feature_switch', 'staff_admin', '1', 1773413108, 1773413108),
('feature_switch', 'staff_center', '1', 1773413108, 1773413108),
('feature_switch', 'staff_detail_style', 'classic', 1773556898, 1773556898),
('feature_switch', 'staff_tag_review_enabled', '0', 1775001600, 1775001600);

-- =============================================================================
-- Part 4: 定时任务
-- =============================================================================

DELETE FROM `la_dev_crontab`
WHERE `command` IN ('cancel_unpaid_orders', 'send_station_reminders', 'expire_waitlists');

INSERT INTO `la_dev_crontab` (
    `name`,
    `type`,
    `system`,
    `remark`,
    `command`,
    `params`,
    `status`,
    `expression`,
    `create_time`,
    `update_time`,
    `delete_time`
) VALUES
('超时未支付订单自动取消', 1, 1, '每分钟扫描待支付首笔订单并自动取消超时单', 'cancel_unpaid_orders', '', 1, '* * * * *', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
('站内提醒发送', 1, 1, '每分钟扫描服务前一天提醒与暂停到期提醒的站内消息', 'send_station_reminders', '', 1, '* * * * *', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
('候补超期自动失效', 1, 1, '每天扫描预约日期已过的候补并自动标记为已过期', 'expire_waitlists', '', 1, '10 0 * * *', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL);

-- =============================================================================
-- Part 5: 订单付款渠道
-- =============================================================================

SET @schema_name = DATABASE();
SET @payment_channel_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'la_order'
      AND COLUMN_NAME = 'payment_channel'
);

SET @payment_channel_sql = IF(
    @payment_channel_exists = 0,
    'ALTER TABLE `la_order` ADD COLUMN `payment_channel` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT ''付款渠道：1=线上支付,2=线下支付'' AFTER `pay_type`',
    'SELECT 1'
);

PREPARE payment_channel_stmt FROM @payment_channel_sql;
EXECUTE payment_channel_stmt;
DEALLOCATE PREPARE payment_channel_stmt;

UPDATE `la_order`
SET `payment_channel` = CASE
    WHEN `pay_type` = 4 OR COALESCE(`pay_voucher`, '') <> '' THEN 2
    ELSE 1
END
WHERE `payment_channel` NOT IN (1, 2) OR `payment_channel` IS NULL;

SET FOREIGN_KEY_CHECKS = 1;
