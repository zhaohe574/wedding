SET FOREIGN_KEY_CHECKS=0;

-- =============================================
-- 001_create_staff_tables.sql
-- =============================================
-- =============================================
-- 婚庆服务预约系统 - 第一阶段数据库表
-- 工作人员管理 + 服务分类管理
-- 创建日期: 2026-01-19
-- =============================================

-- ----------------------------
-- Table structure for la_service_category (服务分类表)
-- ----------------------------
DROP TABLE IF EXISTS `la_service_category`;
CREATE TABLE `la_service_category` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '分类ID',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '分类名称',
  `pid` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '上级分类ID',
  `icon` varchar(255) NOT NULL DEFAULT '' COMMENT '分类图标',
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '分类图片',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序(数值越大越靠前)',
  `is_show` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否显示:0-否,1-是',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `delete_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_pid` (`pid`) USING BTREE,
  KEY `idx_is_show` (`is_show`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务分类表';

-- ----------------------------
-- Records of la_service_category
-- ----------------------------
BEGIN;
INSERT INTO `la_service_category` VALUES 
(1, '摄影师', 0, '', '/resource/image/wedding/category/photographer.png', 100, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(2, '摄像师', 0, '', '/resource/image/wedding/category/videographer.png', 90, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(3, '化妆师', 0, '', '/resource/image/wedding/category/makeup.png', 80, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(4, '司仪主持', 0, '', '/resource/image/wedding/category/host.png', 70, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(5, '婚礼策划', 0, '', '/resource/image/wedding/category/planner.png', 60, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(6, '花艺师', 0, '', '/resource/image/wedding/category/florist.png', 50, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(7, '跟妆师', 0, '', '/resource/image/wedding/category/stylist.png', 40, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(8, '灯光师', 0, '', '/resource/image/wedding/category/lighting.png', 30, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL);
COMMIT;

-- ----------------------------
-- Table structure for la_style_tag (风格标签表)
-- ----------------------------
DROP TABLE IF EXISTS `la_style_tag`;
CREATE TABLE `la_style_tag` (
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

-- ----------------------------
-- Records of la_style_tag
-- ----------------------------
BEGIN;
INSERT INTO `la_style_tag` VALUES 
(1, '韩式清新', 1, 0, 100, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(2, '中式古典', 1, 0, 90, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(3, '欧式浪漫', 1, 0, 80, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(4, '日系森系', 1, 0, 70, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(5, '简约现代', 1, 0, 60, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(6, '复古怀旧', 1, 0, 50, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(7, '户外自然', 2, 0, 100, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(8, '室内棚拍', 2, 0, 90, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(9, '旅拍跟拍', 2, 0, 80, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(10, '纪实风格', 2, 0, 70, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL);
COMMIT;

-- ----------------------------
-- Table structure for la_service_package (服务套餐表)
-- ----------------------------
DROP TABLE IF EXISTS `la_service_package`;
CREATE TABLE `la_service_package` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '套餐ID',
  `category_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务分类ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '套餐名称',
  `price` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '套餐价格',
  `original_price` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '原价',
  `duration` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务时长(小时)',
  `description` text COMMENT '套餐描述',
  `content` text COMMENT '套餐内容(JSON格式)',
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '套餐图片',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `is_recommend` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否推荐:0-否,1-是',
  `is_show` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否显示:0-否,1-是',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `delete_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_category_id` (`category_id`) USING BTREE,
  KEY `idx_is_recommend` (`is_recommend`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务套餐表';

-- ----------------------------
-- Records of la_service_package
-- ----------------------------
BEGIN;
INSERT INTO `la_service_package` VALUES 
(1, 1, '婚礼跟拍-基础套餐', 2999.00, 3999.00, 8, '8小时婚礼全程跟拍', '["精修照片50张","原片全送","专业设备"]', '', 100, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(2, 1, '婚礼跟拍-标准套餐', 4999.00, 5999.00, 10, '10小时婚礼全程跟拍', '["精修照片80张","原片全送","专业设备","相册一本"]', '', 90, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(3, 1, '婚礼跟拍-豪华套餐', 7999.00, 9999.00, 12, '12小时婚礼全程跟拍+晚宴', '["精修照片120张","原片全送","专业设备","相册两本","视频花絮"]', '', 80, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(4, 2, '婚礼摄像-基础套餐', 3999.00, 4999.00, 8, '8小时婚礼全程摄像', '["成片15分钟","原素材","4K画质"]', '', 100, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(5, 2, '婚礼摄像-标准套餐', 5999.00, 7999.00, 10, '10小时双机位摄像', '["成片20分钟","原素材","4K画质","快剪"]', '', 90, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(6, 3, '新娘跟妆-全天', 1999.00, 2499.00, 10, '全天新娘妆容服务', '["早妆","晚宴补妆","造型2套"]', '', 100, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(7, 3, '新娘跟妆-半天', 999.00, 1299.00, 5, '半天新娘妆容服务', '["早妆或晚宴妆","造型1套"]', '', 90, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(8, 4, '婚礼主持-标准', 2999.00, 3999.00, 4, '婚礼仪式主持', '["仪式主持","互动环节","专业设备"]', '', 100, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL);
COMMIT;

-- ----------------------------
-- Table structure for la_staff (工作人员表)
-- ----------------------------
DROP TABLE IF EXISTS `la_staff`;
CREATE TABLE `la_staff` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '工作人员ID',
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联用户ID(用于登录)',
  `sn` varchar(32) NOT NULL DEFAULT '' COMMENT '工号',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '姓名',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
  `mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号(脱敏显示)',
  `mobile_full` varchar(20) NOT NULL DEFAULT '' COMMENT '完整手机号',
  `category_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务分类ID',
  `price` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '起步价格',
  `experience_years` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '从业年限',
  `profile` text COMMENT '个人简介',
  `service_desc` text COMMENT '服务说明',
  `rating` decimal(3,2) UNSIGNED NOT NULL DEFAULT 5.00 COMMENT '综合评分(1-5)',
  `rating_service` decimal(3,2) UNSIGNED NOT NULL DEFAULT 5.00 COMMENT '服务态度评分',
  `rating_skill` decimal(3,2) UNSIGNED NOT NULL DEFAULT 5.00 COMMENT '专业水平评分',
  `rating_price` decimal(3,2) UNSIGNED NOT NULL DEFAULT 5.00 COMMENT '性价比评分',
  `order_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '接单数量',
  `review_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '评价数量',
  `favorite_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '收藏数量',
  `view_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '浏览数量',
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
  KEY `idx_category_id` (`category_id`) USING BTREE,
  KEY `idx_status` (`status`) USING BTREE,
  KEY `idx_is_recommend` (`is_recommend`) USING BTREE,
  KEY `idx_rating` (`rating`) USING BTREE,
  KEY `idx_price` (`price`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='工作人员表';

-- ----------------------------
-- Table structure for la_staff_work (作品表)
-- ----------------------------
DROP TABLE IF EXISTS `la_staff_work`;
CREATE TABLE `la_staff_work` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '作品ID',
  `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '工作人员ID',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '作品标题',
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
  `audit_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '审核状态:0-待审核,1-已通过,2-已拒绝',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `delete_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_staff_id` (`staff_id`) USING BTREE,
  KEY `idx_audit_status` (`audit_status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='工作人员作品表';

-- ----------------------------
-- Table structure for la_staff_certificate (证书表)
-- ----------------------------
DROP TABLE IF EXISTS `la_staff_certificate`;
CREATE TABLE `la_staff_certificate` (
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

-- ----------------------------
-- Table structure for la_staff_tag (标签关联表)
-- ----------------------------
DROP TABLE IF EXISTS `la_staff_tag`;
CREATE TABLE `la_staff_tag` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '工作人员ID',
  `tag_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '标签ID',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_staff_tag` (`staff_id`, `tag_id`) USING BTREE,
  KEY `idx_tag_id` (`tag_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='工作人员标签关联表';

-- ----------------------------
-- Table structure for la_staff_package (工作人员套餐关联表)
-- ----------------------------
DROP TABLE IF EXISTS `la_staff_package`;
CREATE TABLE `la_staff_package` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '工作人员ID',
  `package_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '套餐ID',
  `price` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '该人员的套餐价格(可覆盖默认价格)',
  `is_default` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否默认套餐:0-否,1-是',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_staff_package` (`staff_id`, `package_id`) USING BTREE,
  KEY `idx_package_id` (`package_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='工作人员套餐关联表';

-- ----------------------------
-- Table structure for la_favorite (收藏表)
-- ----------------------------
DROP TABLE IF EXISTS `la_favorite`;
CREATE TABLE `la_favorite` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '工作人员ID',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_user_staff` (`user_id`, `staff_id`) USING BTREE,
  KEY `idx_staff_id` (`staff_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='收藏表';

-- =============================================
-- 002_create_schedule_cart_tables.sql
-- =============================================
-- ============================================================
-- 婚庆服务预约系统 - 第二阶段数据表
-- 档期管理 + 购物车
-- 创建日期: 2026-01-19
-- ============================================================

-- -----------------------------------------------------------
-- 1. 档期规则表 (la_schedule_rule)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_schedule_rule`;
CREATE TABLE `la_schedule_rule` (
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

-- -----------------------------------------------------------
-- 2. 档期表 (la_schedule)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_schedule`;
CREATE TABLE `la_schedule` (
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

-- -----------------------------------------------------------
-- 3. 档期锁定记录表 (la_schedule_lock)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_schedule_lock`;
CREATE TABLE `la_schedule_lock` (
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

-- -----------------------------------------------------------
-- 4. 档期共享表 (la_schedule_share)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_schedule_share`;
CREATE TABLE `la_schedule_share` (
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

-- -----------------------------------------------------------
-- 5. 购物车表 (la_cart)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_cart`;
CREATE TABLE `la_cart` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `staff_id` INT UNSIGNED NOT NULL COMMENT '工作人员ID',
    `package_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '套餐ID（0=仅预约人员）',
    `schedule_date` DATE NOT NULL COMMENT '预约日期',
    `time_slot` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '时间段：0=全天,1=早礼,2=午宴,3=晚宴',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '价格',
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '数量',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `is_selected` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否选中：0=否,1=是',
    `share_code` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '分享码',
    `share_user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '分享来源用户ID',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_schedule_date` (`schedule_date`),
    KEY `idx_share_code` (`share_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='购物车表';

-- -----------------------------------------------------------
-- 6. 购物车方案表 (la_cart_plan)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_cart_plan`;
CREATE TABLE `la_cart_plan` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `plan_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '方案名称',
    `cart_ids` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '购物车项ID列表（JSON）',
    `items_snapshot` TEXT NULL COMMENT '方案明细快照（JSON）',
    `total_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '方案总价',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '方案备注',
    `share_code` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '分享码',
    `is_default` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否默认方案：0=否,1=是',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_share_code` (`share_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='购物车方案表';

-- -----------------------------------------------------------
-- 7. 候补订单表 (la_waitlist)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_waitlist`;
CREATE TABLE `la_waitlist` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `staff_id` INT UNSIGNED NOT NULL COMMENT '工作人员ID',
    `schedule_date` DATE NOT NULL COMMENT '预约日期',
    `time_slot` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '时间段',
    `package_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '套餐ID',
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

-- -----------------------------------------------------------
-- 8. 黄历/吉日表 (la_calendar_event)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_calendar_event`;
CREATE TABLE `la_calendar_event` (
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

-- -----------------------------------------------------------
-- 初始化数据
-- -----------------------------------------------------------

-- 全局档期规则
INSERT INTO `la_schedule_rule` (`staff_id`, `advance_days`, `max_orders_per_day`, `interval_hours`, `work_start_time`, `work_end_time`, `rest_days`, `is_enabled`, `create_time`, `update_time`) VALUES
(0, 3, 1, 0, '08:00', '20:00', '', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 示例吉日数据（2026年部分吉日）
INSERT INTO `la_calendar_event` (`event_date`, `lunar_date`, `is_lucky_day`, `lucky_events`, `unlucky_events`, `is_holiday`, `holiday_name`, `congestion_level`, `create_time`, `update_time`) VALUES
('2026-01-01', '十一月十二', 0, '', '', 1, '元旦', 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('2026-02-17', '正月初一', 1, '祈福,嫁娶,订盟', '动土,破土', 1, '春节', 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('2026-05-01', '三月十五', 1, '嫁娶,订盟,纳采', '开市,动土', 1, '劳动节', 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('2026-05-20', '四月初四', 1, '嫁娶,订盟,纳采', '安葬,破土', 0, '', 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('2026-10-01', '八月廿一', 1, '嫁娶,祈福,订盟', '安葬,动土', 1, '国庆节', 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- =============================================
-- 003_create_order_dynamic_tables.sql
-- =============================================
-- ============================================================
-- 婚庆服务预约系统 - 第三阶段数据表
-- 订单管理 + 动态社区
-- 创建日期: 2026-01-19
-- ============================================================

-- -----------------------------------------------------------
-- 1. 订单表 (la_order)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_order`;
CREATE TABLE `la_order` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `order_sn` VARCHAR(32) NOT NULL COMMENT '订单编号',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `order_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '订单类型：1=普通订单,2=套餐订单,3=组合订单',
    `order_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单状态：0=待确认,1=待支付,2=已支付,3=服务中,4=已完成,5=已评价,6=已取消,7=已暂停,8=已退款',
    `pay_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付状态：0=未支付,1=已支付,2=部分退款,3=全额退款',
    `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '订单总额',
    `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '优惠金额',
    `coupon_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '优惠券抵扣',
    `pay_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '实付金额',
    `paid_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '已支付金额',
    `deposit_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '定金金额',
    `deposit_paid` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '定金是否支付：0=否,1=是',
    `balance_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '尾款金额',
    `balance_paid` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '尾款是否支付：0=否,1=是',
    `pay_type` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付方式：0=未支付,1=微信,2=支付宝,3=余额,4=线下,5=组合支付',
    `pay_voucher` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '线下支付凭证',
    `pay_voucher_status` TINYINT UNSIGNED DEFAULT NULL COMMENT '凭证审核状态：0=待审核,1=已通过,2=已拒绝',
    `pay_voucher_audit_admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '凭证审核管理员ID',
    `pay_voucher_audit_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '凭证审核时间',
    `pay_voucher_audit_remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '凭证审核备注',
    `pay_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付时间',
    `service_date` DATE DEFAULT NULL COMMENT '服务日期',
    `service_time_slot` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务时间段',
    `service_address` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '服务地址',
    `contact_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '联系人姓名',
    `contact_mobile` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '联系人电话',
    `wedding_date` DATE DEFAULT NULL COMMENT '婚礼日期',
    `wedding_venue` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '婚礼场地',
    `user_remark` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '用户备注',
    `admin_remark` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '管理员备注',
    `coupon_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '使用的优惠券ID',
    `cancel_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '取消原因',
    `cancel_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '取消时间',
    `complete_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '完成时间',
    `is_reviewed` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否已评价：0=否,1=是',
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
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单表';

-- -----------------------------------------------------------
-- 2. 订单项表 (la_order_item)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_order_item`;
CREATE TABLE `la_order_item` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `staff_id` INT UNSIGNED NOT NULL COMMENT '工作人员ID',
    `package_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '套餐ID',
    `schedule_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '档期ID',
    `service_date` DATE NOT NULL COMMENT '服务日期',
    `time_slot` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '时间段',
    `staff_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '人员姓名(冗余)',
    `package_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '套餐名称(冗余)',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '单价',
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '数量',
    `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '小计',
    `item_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '项状态：0=待服务,1=服务中,2=已完成,3=已取消',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_service_date` (`service_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单项表';

-- -----------------------------------------------------------
-- 3. 订单日志表 (la_order_log)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_order_log`;
CREATE TABLE `la_order_log` (
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

-- -----------------------------------------------------------
-- 4. 支付记录表 (la_payment)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_payment`;
CREATE TABLE `la_payment` (
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

-- -----------------------------------------------------------
-- 5. 退款记录表 (la_refund)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_refund`;
CREATE TABLE `la_refund` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `refund_sn` VARCHAR(32) NOT NULL COMMENT '退款编号',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `payment_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付记录ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `refund_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '退款类型：1=用户申请,2=管理员操作,3=系统自动',
    `refund_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '退款金额',
    `refund_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '退款原因',
    `refund_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '退款状态：0=待审核,1=审核通过,2=退款中,3=已退款,4=已拒绝',
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

-- -----------------------------------------------------------
-- 6. 动态表 (la_dynamic)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_dynamic`;
CREATE TABLE `la_dynamic` (
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

-- -----------------------------------------------------------
-- 7. 动态评论表 (la_dynamic_comment)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_dynamic_comment`;
CREATE TABLE `la_dynamic_comment` (
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

-- -----------------------------------------------------------
-- 8. 动态点赞表 (la_dynamic_like)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_dynamic_like`;
CREATE TABLE `la_dynamic_like` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `target_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '目标类型：1=动态,2=评论',
    `target_id` INT UNSIGNED NOT NULL COMMENT '目标ID',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_target` (`user_id`, `target_type`, `target_id`),
    KEY `idx_target` (`target_type`, `target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='动态点赞表';

-- -----------------------------------------------------------
-- 9. 动态收藏表 (la_dynamic_collect)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_dynamic_collect`;
CREATE TABLE `la_dynamic_collect` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `dynamic_id` INT UNSIGNED NOT NULL COMMENT '动态ID',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_dynamic` (`user_id`, `dynamic_id`),
    KEY `idx_dynamic_id` (`dynamic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='动态收藏表';

-- -----------------------------------------------------------
-- 10. 关注表 (la_follow)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_follow`;
CREATE TABLE `la_follow` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '关注者ID',
    `follow_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '关注类型：1=用户,2=工作人员',
    `follow_id` INT UNSIGNED NOT NULL COMMENT '被关注者ID',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_follow` (`user_id`, `follow_type`, `follow_id`),
    KEY `idx_follow` (`follow_type`, `follow_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='关注表';

-- -----------------------------------------------------------
-- 11. 消息通知表 (la_notification)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_notification`;
CREATE TABLE `la_notification` (
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

-- -----------------------------------------------------------
-- 12. 优惠券表 (la_coupon)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_coupon`;
CREATE TABLE `la_coupon` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '优惠券名称',
    `coupon_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '类型：1=满减券,2=折扣券,3=立减券',
    `threshold_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '使用门槛金额(0=无门槛)',
    `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '优惠金额/折扣率',
    `max_discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '最大优惠金额(折扣券用)',
    `total_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '发放总量(0=不限)',
    `receive_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '已领取数量',
    `used_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '已使用数量',
    `per_limit` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '每人限领数量',
    `receive_start_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '领取开始时间',
    `receive_end_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '领取结束时间',
    `valid_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '有效期类型：1=固定日期,2=领取后N天',
    `valid_start_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '有效期开始',
    `valid_end_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '有效期结束',
    `valid_days` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '领取后有效天数',
    `use_scope` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '使用范围：1=全部,2=指定分类,3=指定人员',
    `scope_ids` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '适用范围ID(JSON)',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=禁用,1=启用',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`),
    KEY `idx_receive_time` (`receive_start_time`, `receive_end_time`),
    KEY `idx_valid_time` (`valid_start_time`, `valid_end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='优惠券表';

-- -----------------------------------------------------------
-- 13. 用户优惠券表 (la_user_coupon)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_user_coupon`;
CREATE TABLE `la_user_coupon` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `coupon_id` INT UNSIGNED NOT NULL COMMENT '优惠券ID',
    `coupon_sn` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '优惠券码',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态：0=未使用,1=已使用,2=已过期',
    `order_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '使用订单ID',
    `use_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '使用时间',
    `valid_start_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '有效期开始',
    `valid_end_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '有效期结束',
    `receive_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '领取时间',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_coupon_sn` (`coupon_sn`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_coupon_id` (`coupon_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='用户优惠券表';

-- -----------------------------------------------------------
-- 初始化数据
-- -----------------------------------------------------------

-- 系统优惠券模板
INSERT INTO `la_coupon` (`name`, `coupon_type`, `threshold_amount`, `discount_amount`, `max_discount`, `total_count`, `per_limit`, `valid_type`, `valid_days`, `use_scope`, `status`, `remark`, `create_time`, `update_time`) VALUES
('新人专享券', 1, 1000.00, 100.00, 0.00, 0, 1, 2, 30, 1, 1, '新用户注册赠送', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('满2000减200', 1, 2000.00, 200.00, 0.00, 1000, 1, 1, 0, 1, 1, '限时活动', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('9折优惠券', 2, 500.00, 90.00, 500.00, 500, 2, 2, 15, 1, 1, '会员专享', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- =============================================
-- 004_create_order_change_tables.sql
-- =============================================
-- =============================================
-- 婚庆服务预约系统 - 订单高级功能表结构
-- Phase 4: 订单变更、转让、暂停功能
-- 创建时间: 2026-01-19
-- =============================================

-- =============================================
-- 1. 订单变更申请表
-- 支持改期、换人、加项三种变更类型
-- =============================================
DROP TABLE IF EXISTS `la_order_change`;
CREATE TABLE `la_order_change` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `change_sn` VARCHAR(32) NOT NULL COMMENT '变更单号',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `order_sn` VARCHAR(32) NOT NULL COMMENT '订单编号(冗余)',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `change_type` TINYINT UNSIGNED NOT NULL COMMENT '变更类型：1=改期,2=换人,3=加项',
    `change_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '变更状态：0=待审核,1=审核通过,2=审核拒绝,3=已执行,4=已取消',
    
    -- 改期相关字段
    `old_service_date` DATE DEFAULT NULL COMMENT '原服务日期',
    `new_service_date` DATE DEFAULT NULL COMMENT '新服务日期',
    `old_time_slot` TINYINT UNSIGNED DEFAULT 0 COMMENT '原时间段：0=全天,1=早礼,2=午宴,3=晚宴',
    `new_time_slot` TINYINT UNSIGNED DEFAULT 0 COMMENT '新时间段',
    
    -- 换人相关字段
    `order_item_id` INT UNSIGNED DEFAULT 0 COMMENT '订单项ID（换人/加项用）',
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
    
    -- 加项相关字段
    `add_staff_id` INT UNSIGNED DEFAULT 0 COMMENT '新增工作人员ID',
    `add_package_id` INT UNSIGNED DEFAULT 0 COMMENT '新增套餐ID',
    `add_staff_name` VARCHAR(50) DEFAULT '' COMMENT '新增人员姓名',
    `add_package_name` VARCHAR(100) DEFAULT '' COMMENT '新增套餐名称',
    `add_service_date` DATE DEFAULT NULL COMMENT '新增服务日期',
    `add_time_slot` TINYINT UNSIGNED DEFAULT 0 COMMENT '新增时间段',
    `add_price` DECIMAL(10,2) DEFAULT 0.00 COMMENT '新增价格',
    `add_schedule_id` INT UNSIGNED DEFAULT 0 COMMENT '新增档期ID',
    `add_order_item_id` INT UNSIGNED DEFAULT 0 COMMENT '新增订单项ID',
    
    -- 审核相关
    `apply_reason` VARCHAR(500) DEFAULT '' COMMENT '申请原因',
    `user_remark` VARCHAR(500) DEFAULT '' COMMENT '用户备注',
    `audit_admin_id` INT UNSIGNED DEFAULT 0 COMMENT '审核管理员ID',
    `audit_time` INT UNSIGNED DEFAULT 0 COMMENT '审核时间',
    `audit_remark` VARCHAR(500) DEFAULT '' COMMENT '审核备注',
    `reject_reason` VARCHAR(500) DEFAULT '' COMMENT '拒绝原因',
    
    -- 执行相关
    `execute_time` INT UNSIGNED DEFAULT 0 COMMENT '执行时间',
    `execute_admin_id` INT UNSIGNED DEFAULT 0 COMMENT '执行管理员ID',
    
    -- 附件凭证
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

-- =============================================
-- 2. 订单转让表
-- 支持订单在用户之间转让
-- =============================================
DROP TABLE IF EXISTS `la_order_transfer`;
CREATE TABLE `la_order_transfer` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `transfer_sn` VARCHAR(32) NOT NULL COMMENT '转让单号',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `order_sn` VARCHAR(32) NOT NULL COMMENT '订单编号(冗余)',
    
    -- 转让方
    `from_user_id` INT UNSIGNED NOT NULL COMMENT '转让方用户ID',
    `from_user_name` VARCHAR(50) DEFAULT '' COMMENT '转让方姓名',
    `from_user_mobile` VARCHAR(20) DEFAULT '' COMMENT '转让方电话',
    
    -- 接收方
    `to_user_id` INT UNSIGNED DEFAULT 0 COMMENT '接收方用户ID（注册用户）',
    `to_user_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '接收方姓名',
    `to_user_mobile` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '接收方电话',
    `to_user_verified` TINYINT UNSIGNED DEFAULT 0 COMMENT '接收方是否验证：0=否,1=是',
    
    -- 转让信息
    `transfer_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '转让状态：0=待审核,1=待接收,2=接收确认,3=转让完成,4=已拒绝,5=已取消',
    `transfer_reason` VARCHAR(500) DEFAULT '' COMMENT '转让原因',
    `transfer_fee` DECIMAL(10,2) DEFAULT 0.00 COMMENT '转让手续费',
    `fee_paid` TINYINT UNSIGNED DEFAULT 0 COMMENT '手续费是否支付：0=否,1=是',
    
    -- 审核相关
    `audit_admin_id` INT UNSIGNED DEFAULT 0 COMMENT '审核管理员ID',
    `audit_time` INT UNSIGNED DEFAULT 0 COMMENT '审核时间',
    `audit_remark` VARCHAR(500) DEFAULT '' COMMENT '审核备注',
    `reject_reason` VARCHAR(500) DEFAULT '' COMMENT '拒绝原因',
    
    -- 接收方确认
    `accept_code` VARCHAR(10) DEFAULT '' COMMENT '接收验证码',
    `accept_code_expire` INT UNSIGNED DEFAULT 0 COMMENT '验证码过期时间',
    `accept_code_send_time` INT UNSIGNED DEFAULT 0 COMMENT '验证码发送时间',
    `accept_code_send_count` TINYINT UNSIGNED DEFAULT 0 COMMENT '验证码发送次数',
    `accept_time` INT UNSIGNED DEFAULT 0 COMMENT '接收时间',
    
    -- 完成相关
    `complete_time` INT UNSIGNED DEFAULT 0 COMMENT '完成时间',
    `complete_admin_id` INT UNSIGNED DEFAULT 0 COMMENT '完成操作管理员ID',
    
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_transfer_sn` (`transfer_sn`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_from_user` (`from_user_id`),
    KEY `idx_to_user` (`to_user_id`),
    KEY `idx_to_mobile` (`to_user_mobile`),
    KEY `idx_transfer_status` (`transfer_status`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单转让表';

-- =============================================
-- 3. 订单暂停表
-- 支持特殊情况订单暂停和恢复
-- =============================================
DROP TABLE IF EXISTS `la_order_pause`;
CREATE TABLE `la_order_pause` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `pause_sn` VARCHAR(32) NOT NULL COMMENT '暂停单号',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `order_sn` VARCHAR(32) NOT NULL COMMENT '订单编号(冗余)',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    
    `pause_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '暂停状态：0=待审核,1=暂停中,2=已恢复,3=已拒绝,4=已取消',
    `pause_type` TINYINT UNSIGNED NOT NULL COMMENT '暂停类型：1=疫情,2=突发事件,3=个人原因,4=其他',
    `pause_reason` VARCHAR(500) NOT NULL COMMENT '暂停原因',
    
    -- 暂停期限
    `pause_start_date` DATE DEFAULT NULL COMMENT '暂停开始日期',
    `pause_end_date` DATE DEFAULT NULL COMMENT '暂停结束日期(预计)',
    `pause_days` INT UNSIGNED DEFAULT 0 COMMENT '暂停天数',
    `original_service_date` DATE DEFAULT NULL COMMENT '原服务日期',
    
    -- 审核相关
    `audit_admin_id` INT UNSIGNED DEFAULT 0 COMMENT '审核管理员ID',
    `audit_time` INT UNSIGNED DEFAULT 0 COMMENT '审核时间',
    `audit_remark` VARCHAR(500) DEFAULT '' COMMENT '审核备注',
    `reject_reason` VARCHAR(500) DEFAULT '' COMMENT '拒绝原因',
    
    -- 恢复相关
    `resume_time` INT UNSIGNED DEFAULT 0 COMMENT '恢复时间',
    `resume_admin_id` INT UNSIGNED DEFAULT 0 COMMENT '恢复操作管理员ID',
    `resume_remark` VARCHAR(500) DEFAULT '' COMMENT '恢复备注',
    `actual_pause_days` INT UNSIGNED DEFAULT 0 COMMENT '实际暂停天数',
    `new_service_date` DATE DEFAULT NULL COMMENT '恢复后新服务日期',
    
    -- 提醒设置
    `remind_before_days` INT UNSIGNED DEFAULT 3 COMMENT '提前提醒天数',
    `reminded` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否已提醒：0=否,1=是',
    `remind_time` INT UNSIGNED DEFAULT 0 COMMENT '提醒时间',
    
    -- 附件
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

-- =============================================
-- 4. 订单变更日志表
-- 记录所有变更操作的详细日志
-- =============================================
DROP TABLE IF EXISTS `la_order_change_log`;
CREATE TABLE `la_order_change_log` (
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

-- =============================================
-- 5. 修改订单表，添加新字段
-- =============================================
ALTER TABLE `la_order` 
ADD COLUMN `is_paused` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否暂停：0=否,1=是' AFTER `is_reviewed`,
ADD COLUMN `pause_id` INT UNSIGNED DEFAULT 0 COMMENT '关联暂停记录ID' AFTER `is_paused`,
ADD COLUMN `has_changed` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否有变更记录：0=否,1=是' AFTER `pause_id`,
ADD COLUMN `change_count` INT UNSIGNED DEFAULT 0 COMMENT '变更次数' AFTER `has_changed`,
ADD COLUMN `is_transferred` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否已转让：0=否,1=是' AFTER `change_count`,
ADD COLUMN `transfer_id` INT UNSIGNED DEFAULT 0 COMMENT '关联转让记录ID' AFTER `is_transferred`,
ADD COLUMN `original_user_id` INT UNSIGNED DEFAULT 0 COMMENT '原用户ID(转让前)' AFTER `transfer_id`;

-- 添加索引
ALTER TABLE `la_order`
ADD KEY `idx_is_paused` (`is_paused`),
ADD KEY `idx_is_transferred` (`is_transferred`);

-- =============================================
-- 6. 修改订单项表，添加新字段
-- =============================================
ALTER TABLE `la_order_item`
ADD COLUMN `is_changed` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否变更过：0=否,1=是' AFTER `item_status`,
ADD COLUMN `change_id` INT UNSIGNED DEFAULT 0 COMMENT '关联变更记录ID' AFTER `is_changed`,
ADD COLUMN `original_staff_id` INT UNSIGNED DEFAULT 0 COMMENT '原工作人员ID(换人前)' AFTER `change_id`,
ADD COLUMN `original_price` DECIMAL(10,2) DEFAULT 0.00 COMMENT '原价格(换人前)' AFTER `original_staff_id`;

-- =============================================
-- 7. 初始化数据（暂停类型字典）
-- =============================================
INSERT INTO `la_dict_data`
(`type_id`, `name`, `value`, `type_value`, `sort`, `status`, `remark`, `create_time`, `update_time`)
VALUES
((SELECT id FROM `la_dict_type` WHERE `type` = 'system_config' LIMIT 1),
 '疫情', '1', '1', 1, 1, '订单暂停类型-疫情', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
((SELECT id FROM `la_dict_type` WHERE `type` = 'system_config' LIMIT 1),
 '突发事件', '2', '2', 2, 1, '订单暂停类型-突发事件', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
((SELECT id FROM `la_dict_type` WHERE `type` = 'system_config' LIMIT 1),
 '个人原因', '3', '3', 3, 1, '订单暂停类型-个人原因', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
((SELECT id FROM `la_dict_type` WHERE `type` = 'system_config' LIMIT 1),
 '其他', '4', '4', 4, 1, '订单暂停类型-其他', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
-- =============================================
-- 说明：
-- 1. la_order_change 表支持三种变更类型：改期(1)、换人(2)、加项(3)
-- 2. 差价处理：price_diff 正数表示用户需补付，负数表示退款给用户
-- 3. la_order_transfer 表支持订单在用户间转让，需验证码确认
-- 4. la_order_pause 表支持订单暂停，可设置暂停期限和提醒
-- 5. la_order_change_log 记录所有变更操作的完整日志
-- =============================================

-- =============================================
-- 005_create_review_tables.sql
-- =============================================
-- =============================================
-- 评价与口碑系统数据库表
-- 创建时间: 2026-01-19
-- =============================================

-- ----------------------------
-- 评价主表
-- ----------------------------
DROP TABLE IF EXISTS `la_review`;
CREATE TABLE `la_review` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '评价ID',
    `order_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单ID',
    `order_item_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单项ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID',
    `score` tinyint(1) UNSIGNED NOT NULL DEFAULT 5 COMMENT '综合评分 1-5星',
    `score_service` tinyint(1) UNSIGNED NOT NULL DEFAULT 5 COMMENT '服务态度评分 1-5星',
    `score_professional` tinyint(1) UNSIGNED NOT NULL DEFAULT 5 COMMENT '专业水平评分 1-5星',
    `score_punctual` tinyint(1) UNSIGNED NOT NULL DEFAULT 5 COMMENT '时间守约评分 1-5星',
    `score_effect` tinyint(1) UNSIGNED NOT NULL DEFAULT 5 COMMENT '整体效果评分 1-5星',
    `content` text COMMENT '评价内容',
    `images` text COMMENT '评价图片 JSON数组',
    `video` varchar(500) NOT NULL DEFAULT '' COMMENT '评价视频URL',
    `video_cover` varchar(500) NOT NULL DEFAULT '' COMMENT '视频封面图URL',
    `is_anonymous` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否匿名 0否 1是',
    `is_top` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否置顶 0否 1是',
    `top_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '置顶时间',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态 0待审核 1已通过 2已拒绝',
    `reject_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '拒绝原因',
    `review_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '评价类型 1文字 2图文 3视频',
    `reward_points` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '奖励积分',
    `is_rewarded` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否已发放奖励 0否 1是',
    `reward_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '奖励发放时间',
    `like_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '点赞数',
    `reply_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回复数',
    `is_show` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否显示 0隐藏 1显示',
    `admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核人ID',
    `audit_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核时间',
    `service_date` date DEFAULT NULL COMMENT '服务日期（冗余字段便于查询）',
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

-- ----------------------------
-- 评价标签表
-- ----------------------------
DROP TABLE IF EXISTS `la_review_tag`;
CREATE TABLE `la_review_tag` (
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

-- ----------------------------
-- 评价标签关联表
-- ----------------------------
DROP TABLE IF EXISTS `la_review_tag_relation`;
CREATE TABLE `la_review_tag_relation` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `review_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '评价ID',
    `tag_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '标签ID',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_review_tag` (`review_id`, `tag_id`),
    KEY `idx_tag_id` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='评价标签关联表';

-- ----------------------------
-- 评价回复表
-- ----------------------------
DROP TABLE IF EXISTS `la_review_reply`;
CREATE TABLE `la_review_reply` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '回复ID',
    `review_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '评价ID',
    `parent_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '父回复ID（用于楼中楼）',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID（追评时使用）',
    `admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '管理员ID（商家回复时使用）',
    `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID（人员回复时使用）',
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

-- ----------------------------
-- 评价点赞表
-- ----------------------------
DROP TABLE IF EXISTS `la_review_like`;
CREATE TABLE `la_review_like` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `review_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '评价ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_review_user` (`review_id`, `user_id`),
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='评价点赞表';

-- ----------------------------
-- 评价申诉表
-- ----------------------------
DROP TABLE IF EXISTS `la_review_appeal`;
CREATE TABLE `la_review_appeal` (
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

-- ----------------------------
-- 评价奖励配置表
-- ----------------------------
DROP TABLE IF EXISTS `la_review_reward_config`;
CREATE TABLE `la_review_reward_config` (
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

-- ----------------------------
-- 晒单奖励记录表
-- ----------------------------
DROP TABLE IF EXISTS `la_review_share_reward`;
CREATE TABLE `la_review_share_reward` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `review_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '评价ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `share_platform` varchar(50) NOT NULL DEFAULT '' COMMENT '分享平台 wechat/moments/weibo等',
    `share_url` varchar(500) NOT NULL DEFAULT '' COMMENT '分享链接',
    `reward_points` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '奖励积分',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态 0待审核 1已通过 2已拒绝',
    `verify_image` varchar(500) NOT NULL DEFAULT '' COMMENT '分享截图凭证',
    `admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核人ID',
    `audit_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核时间',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_review_id` (`review_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='晒单奖励记录表';

-- ----------------------------
-- 服务人员评价统计表（冗余表，定时更新）
-- ----------------------------
DROP TABLE IF EXISTS `la_staff_review_stats`;
CREATE TABLE `la_staff_review_stats` (
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

-- ----------------------------
-- 敏感词表
-- ----------------------------
DROP TABLE IF EXISTS `la_sensitive_word`;
CREATE TABLE `la_sensitive_word` (
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

-- ----------------------------
-- 初始化评价标签数据
-- ----------------------------
INSERT INTO `la_review_tag` (`name`, `type`, `icon`, `color`, `sort`, `status`, `create_time`, `update_time`) VALUES
('服务热情', 1, '', '#52c41a', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('专业细致', 1, '', '#52c41a', 2, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('准时守约', 1, '', '#52c41a', 3, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('效果满意', 1, '', '#52c41a', 4, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('沟通顺畅', 1, '', '#52c41a', 5, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('性价比高', 1, '', '#52c41a', 6, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('耐心负责', 1, '', '#52c41a', 7, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('创意独特', 1, '', '#52c41a', 8, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('一般般', 2, '', '#faad14', 10, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('有待改进', 2, '', '#faad14', 11, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('服务冷淡', 3, '', '#ff4d4f', 20, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('不够专业', 3, '', '#ff4d4f', 21, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('迟到早退', 3, '', '#ff4d4f', 22, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('效果不佳', 3, '', '#ff4d4f', 23, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ----------------------------
-- 初始化评价奖励配置
-- ----------------------------
INSERT INTO `la_review_reward_config` (`reward_type`, `reward_points`, `min_content_length`, `min_images`, `min_video_duration`, `extra_points_for_good`, `status`, `create_time`, `update_time`) VALUES
(1, 10, 20, 0, 0, 5, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, 30, 20, 3, 0, 10, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(3, 50, 10, 0, 15, 20, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- =============================================
-- 006_create_financial_tables.sql
-- =============================================
-- ============================================================
-- 婚庆服务预约系统 - 财务与结算管理数据表
-- 创建日期: 2026-01-20
-- ============================================================

-- -----------------------------------------------------------
-- 1. 资金流水表 (la_financial_flow)
-- 记录每一笔资金变动
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_financial_flow`;
CREATE TABLE `la_financial_flow` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `flow_sn` VARCHAR(32) NOT NULL COMMENT '流水编号',
    `flow_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '流水类型：1=收入,2=支出,3=退款,4=分账,5=提现',
    `biz_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '业务类型：1=订单支付,2=订单退款,3=人员结算,4=平台抽成,5=其他',
    `biz_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '业务ID(订单/退款等)',
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

-- -----------------------------------------------------------
-- 2. 成本记录表 (la_cost_record)
-- 记录服务成本、人工成本等
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_cost_record`;
CREATE TABLE `la_cost_record` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `cost_sn` VARCHAR(32) NOT NULL COMMENT '成本编号',
    `order_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `order_item_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单项ID',
    `staff_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联服务人员ID',
    `cost_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '成本类型：1=人工成本,2=物料成本,3=交通成本,4=设备成本,5=其他成本',
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

-- -----------------------------------------------------------
-- 3. 服务人员结算表 (la_staff_settlement)
-- 服务人员的分成结算记录
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_staff_settlement`;
CREATE TABLE `la_staff_settlement` (
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
    `settlement_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '结算类型：1=自动结算,2=手动结算',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态：0=待结算,1=已结算,2=已取消,3=结算失败',
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

-- -----------------------------------------------------------
-- 4. 结算批次表 (la_settlement_batch)
-- 批量结算记录
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_settlement_batch`;
CREATE TABLE `la_settlement_batch` (
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

-- -----------------------------------------------------------
-- 5. 财务日报表 (la_financial_daily)
-- 每日财务数据汇总
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_financial_daily`;
CREATE TABLE `la_financial_daily` (
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
    UNIQUE KEY `uk_report_date` (`report_date`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='财务日报表';

-- -----------------------------------------------------------
-- 6. 财务月报表 (la_financial_monthly)
-- 每月财务数据汇总
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_financial_monthly`;
CREATE TABLE `la_financial_monthly` (
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

-- -----------------------------------------------------------
-- 7. 发票记录表 (la_invoice)
-- 开票记录管理
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_invoice`;
CREATE TABLE `la_invoice` (
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
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态：0=待开票,1=开票中,2=已开票,3=开票失败,4=已作废',
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

-- -----------------------------------------------------------
-- 8. 服务人员结算配置表 (la_staff_settlement_config)
-- 结算比例配置
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_staff_settlement_config`;
CREATE TABLE `la_staff_settlement_config` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `staff_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID(0=默认配置)',
    `category_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务分类ID(0=全部分类)',
    `settlement_rate` DECIMAL(5,2) NOT NULL DEFAULT 70.00 COMMENT '结算比例(%)',
    `min_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '最低结算金额',
    `settle_cycle` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '结算周期：1=月结,2=周结,3=单笔结',
    `settle_delay_days` INT UNSIGNED NOT NULL DEFAULT 7 COMMENT '结算延迟天数(服务完成后)',
    `is_default` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否默认配置：0=否,1=是',
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

-- -----------------------------------------------------------
-- 9. 财务对账记录表 (la_financial_reconciliation)
-- 第三方支付对账
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_financial_reconciliation`;
CREATE TABLE `la_financial_reconciliation` (
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

-- -----------------------------------------------------------
-- 初始化默认结算配置
-- -----------------------------------------------------------
INSERT INTO `la_staff_settlement_config` (`staff_id`, `category_id`, `settlement_rate`, `min_amount`, `settle_cycle`, `settle_delay_days`, `is_default`, `status`, `remark`, `create_time`, `update_time`) VALUES
(0, 0, 70.00, 100.00, 1, 7, 1, 1, '默认结算配置：70%分成，月结，服务完成后7天可结算', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- =============================================
-- 007_create_timeline_tables.sql
-- =============================================
-- ============================================================
-- 婚庆服务预约系统 - 婚礼时间轴数据表
-- 创建日期: 2026-01-20
-- ============================================================

-- -----------------------------------------------------------
-- 1. 时间轴模板表 (la_timeline_template)
-- 预设的婚礼筹备任务模板
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_timeline_template`;
CREATE TABLE `la_timeline_template` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `template_name` VARCHAR(100) NOT NULL COMMENT '模板名称',
    `template_desc` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '模板描述',
    `service_type` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '适用服务类型：0=通用,1=摄影,2=化妆,3=全套服务',
    `tasks` TEXT NOT NULL COMMENT '任务配置JSON [{title, desc, days_before, type}]',
    `is_default` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否默认模板：0=否,1=是',
    `is_enabled` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否启用：0=禁用,1=启用',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序权重',
    `use_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '使用次数',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_service_type` (`service_type`),
    KEY `idx_is_default` (`is_default`),
    KEY `idx_is_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='时间轴模板表';

-- -----------------------------------------------------------
-- 2. 订单时间轴任务表 (la_order_timeline)
-- 每个订单的具体时间轴任务
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_order_timeline`;
CREATE TABLE `la_order_timeline` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `template_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '来源模板ID',
    `wedding_date` DATE NOT NULL COMMENT '婚期日期',
    `task_title` VARCHAR(100) NOT NULL COMMENT '任务标题',
    `task_desc` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '任务描述',
    `task_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '任务类型：1=准备物料,2=确认事项,3=沟通联系,4=现场安排,5=其他',
    `days_before` INT NOT NULL DEFAULT 0 COMMENT '婚期前N天(0=当天,负数=婚期后)',
    `trigger_date` DATE NOT NULL COMMENT '实际触发日期',
    `trigger_time` TIME DEFAULT NULL COMMENT '触发时间(可选)',
    `is_completed` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否完成：0=未完成,1=已完成',
    `complete_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '完成时间',
    `complete_user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '完成人ID',
    `complete_remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '完成备注',
    `is_reminded` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否已提醒：0=未提醒,1=已提醒',
    `remind_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '提醒时间',
    `is_system` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否系统任务：0=手动添加,1=系统生成',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序权重',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_wedding_date` (`wedding_date`),
    KEY `idx_trigger_date` (`trigger_date`),
    KEY `idx_is_completed` (`is_completed`),
    KEY `idx_task_type` (`task_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单时间轴任务表';

-- -----------------------------------------------------------
-- 3. 初始化默认时间轴模板数据
-- -----------------------------------------------------------
INSERT INTO `la_timeline_template` (`template_name`, `template_desc`, `service_type`, `tasks`, `is_default`, `is_enabled`, `sort`, `create_time`, `update_time`) VALUES
('通用婚礼筹备模板', '适用于所有类型婚礼服务的标准筹备流程', 0, '[{"title":"确认服务细节","desc":"与工作人员确认服务内容、时间、地点等细节","days_before":30,"type":2},{"title":"试妆预约","desc":"预约试妆时间，确认妆容造型","days_before":21,"type":2},{"title":"最终方案确认","desc":"确认最终服务方案，签署补充协议","days_before":14,"type":2},{"title":"物料清单核对","desc":"核对婚礼当天所需物料清单","days_before":7,"type":1},{"title":"与工作人员确认集合时间","desc":"确认婚礼当天集合时间、地点和联系方式","days_before":3,"type":3},{"title":"婚礼前一天确认","desc":"最终确认所有细节，确保万无一失","days_before":1,"type":2},{"title":"婚礼当天","desc":"婚礼当天服务开始","days_before":0,"type":4}]', 1, 1, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('摄影服务专属模板', '适用于婚礼摄影、婚纱照等摄影服务', 1, '[{"title":"确认拍摄风格","desc":"与摄影师沟通确认拍摄风格和场景","days_before":30,"type":2},{"title":"场地踩点","desc":"提前踩点拍摄场地，规划拍摄路线","days_before":14,"type":2},{"title":"确认拍摄服装","desc":"确认拍摄当天的服装和配饰","days_before":7,"type":1},{"title":"设备检查","desc":"摄影师检查设备，确保万无一失","days_before":3,"type":1},{"title":"拍摄当天","desc":"拍摄服务开始","days_before":0,"type":4},{"title":"初片交付","desc":"交付初选照片供客户挑选","days_before":-7,"type":5},{"title":"精修交付","desc":"交付精修照片","days_before":-21,"type":5}]', 0, 1, 90, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('化妆服务专属模板', '适用于新娘化妆、跟妆等化妆服务', 2, '[{"title":"确认妆容风格","desc":"与化妆师沟通确认妆容风格","days_before":30,"type":2},{"title":"试妆","desc":"进行试妆，调整妆容细节","days_before":14,"type":2},{"title":"确认化妆品","desc":"确认婚礼当天使用的化妆品","days_before":7,"type":1},{"title":"皮肤护理提醒","desc":"婚前皮肤护理，保持最佳状态","days_before":3,"type":2},{"title":"化妆当天","desc":"婚礼当天化妆服务开始","days_before":0,"type":4}]', 0, 1, 80, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- =============================================
-- 010_create_crm_tables.sql
-- =============================================
-- ============================================================
-- 婚庆服务预约系统 - CRM客户管理数据表
-- 创建日期: 2026-01-20
-- ============================================================

-- -----------------------------------------------------------
-- 1. 销售顾问表 (la_sales_advisor)
-- 销售顾问/客服人员信息
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_sales_advisor`;
CREATE TABLE `la_sales_advisor` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联管理员ID',
    `advisor_name` VARCHAR(50) NOT NULL COMMENT '顾问姓名',
    `avatar` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '头像',
    `mobile` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '手机号',
    `wechat` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '企业微信号',
    `email` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '邮箱',
    `areas` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '负责区域(JSON数组)',
    `specialties` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '擅长服务类型(JSON数组)',
    `max_customer_count` INT UNSIGNED NOT NULL DEFAULT 100 COMMENT '最大客户数',
    `current_customer_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '当前客户数',
    `total_order_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '累计成交订单数',
    `total_order_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '累计成交金额',
    `conversion_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT '转化率(%)',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=离职,1=正常,2=休假',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序权重(自动分配时使用)',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_admin_id` (`admin_id`),
    KEY `idx_status` (`status`),
    KEY `idx_current_customer_count` (`current_customer_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='销售顾问表';

-- -----------------------------------------------------------
-- 2. 客户信息表 (la_customer)
-- 客户/潜在客户信息管理
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_customer`;
CREATE TABLE `la_customer` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联用户ID(0=潜在客户)',
    `customer_name` VARCHAR(50) NOT NULL COMMENT '客户姓名',
    `customer_mobile` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '手机号',
    `customer_wechat` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '微信号',
    `gender` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '性别：0=未知,1=男,2=女',
    `age` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '年龄',
    `city` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '所在城市',
    `district` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '所在区域',
    `intention_level` CHAR(1) NOT NULL DEFAULT 'D' COMMENT '意向等级：A=高意向,B=中意向,C=低意向,D=待跟进',
    `intention_score` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '意向评分(0-100)',
    `wedding_date` DATE DEFAULT NULL COMMENT '计划婚期',
    `wedding_venue` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '婚礼场地',
    `wedding_budget` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '预算金额',
    `budget_range` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '预算范围(如1-2万)',
    `service_needs` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '服务需求(JSON数组)',
    `source_channel` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '来源渠道：1=小程序,2=H5,3=线下,4=转介绍,5=广告,6=其他',
    `source_detail` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '来源详情',
    `tags` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '客户标签(JSON数组)',
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

-- -----------------------------------------------------------
-- 3. 跟进记录表 (la_follow_record)
-- 客户跟进记录
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_follow_record`;
CREATE TABLE `la_follow_record` (
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
    `attachments` TEXT COMMENT '附件(图片、文件JSON)',
    `is_important` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否重要：0=否,1=是',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_customer_id` (`customer_id`),
    KEY `idx_advisor_id` (`advisor_id`),
    KEY `idx_follow_type` (`follow_type`),
    KEY `idx_follow_result` (`follow_result`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='跟进记录表';

-- -----------------------------------------------------------
-- 4. 客户分配日志表 (la_customer_assign_log)
-- 客户分配/转移记录
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_customer_assign_log`;
CREATE TABLE `la_customer_assign_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `customer_id` INT UNSIGNED NOT NULL COMMENT '客户ID',
    `from_advisor_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '原顾问ID(0=首次分配)',
    `to_advisor_id` INT UNSIGNED NOT NULL COMMENT '新顾问ID',
    `assign_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '分配类型：1=自动分配,2=手动分配,3=转交,4=回收重分',
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

-- -----------------------------------------------------------
-- 5. 客户流失预警表 (la_customer_loss_warning)
-- 客户流失风险预警记录
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_customer_loss_warning`;
CREATE TABLE `la_customer_loss_warning` (
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

-- =============================================
-- 011_create_after_sale_tables.sql
-- =============================================
-- =============================================
-- 售后服务系统数据库表
-- 创建时间: 2026-01-20
-- =============================================

-- ----------------------------
-- 售后工单表
-- ----------------------------
DROP TABLE IF EXISTS `la_after_sale_ticket`;
CREATE TABLE `la_after_sale_ticket` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '工单ID',
    `ticket_sn` varchar(32) NOT NULL DEFAULT '' COMMENT '工单编号',
    `order_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '工单类型 1投诉 2咨询 3售后 4建议 5其他',
    `priority` tinyint(1) UNSIGNED NOT NULL DEFAULT 2 COMMENT '优先级 1低 2中 3高 4紧急',
    `title` varchar(200) NOT NULL DEFAULT '' COMMENT '工单标题',
    `content` text COMMENT '工单内容',
    `images` text COMMENT '图片凭证 JSON数组',
    `contact_name` varchar(50) NOT NULL DEFAULT '' COMMENT '联系人姓名',
    `contact_phone` varchar(20) NOT NULL DEFAULT '' COMMENT '联系电话',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态 0待分配 1处理中 2待确认 3已完成 4已关闭 5已取消',
    `assign_admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理人ID',
    `assign_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '分配时间',
    `handle_result` text COMMENT '处理结果',
    `handle_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理时间',
    `close_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '关闭原因',
    `close_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关闭时间',
    `satisfaction` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '满意度评分 0未评价 1-5星',
    `satisfaction_remark` varchar(500) NOT NULL DEFAULT '' COMMENT '满意度评价内容',
    `expect_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '期望处理时间',
    `deadline` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理截止时间',
    `is_overtime` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否超时 0否 1是',
    `escalate_level` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '升级次数',
    `escalate_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后升级时间',
    `source` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '来源 1小程序 2后台 3电话',
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

-- ----------------------------
-- 工单处理记录表
-- ----------------------------
DROP TABLE IF EXISTS `la_after_sale_ticket_log`;
CREATE TABLE `la_after_sale_ticket_log` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `ticket_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '工单ID',
    `operator_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '操作人类型 1用户 2管理员 3系统',
    `operator_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作人ID',
    `action` varchar(50) NOT NULL DEFAULT '' COMMENT '操作动作',
    `old_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作前状态',
    `new_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作后状态',
    `content` text COMMENT '操作内容/备注',
    `images` text COMMENT '附件图片 JSON数组',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_ticket_id` (`ticket_id`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='工单处理记录表';

-- ----------------------------
-- 投诉表
-- ----------------------------
DROP TABLE IF EXISTS `la_complaint`;
CREATE TABLE `la_complaint` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '投诉ID',
    `complaint_sn` varchar(32) NOT NULL DEFAULT '' COMMENT '投诉编号',
    `order_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '被投诉服务人员ID',
    `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '投诉类型 1服务态度 2专业能力 3迟到早退 4违规行为 5其他',
    `level` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '投诉等级 1一般 2严重 3紧急',
    `title` varchar(200) NOT NULL DEFAULT '' COMMENT '投诉标题',
    `content` text COMMENT '投诉内容',
    `images` text COMMENT '图片凭证 JSON数组',
    `videos` text COMMENT '视频凭证 JSON数组',
    `expect_result` varchar(500) NOT NULL DEFAULT '' COMMENT '期望处理结果',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态 0待处理 1处理中 2已处理 3已申诉 4已关闭',
    `handle_admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理人ID',
    `handle_result` text COMMENT '处理结果',
    `handle_action` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理动作 0无 1警告 2扣款 3禁用 4其他',
    `handle_amount` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '处理涉及金额（扣款/赔偿）',
    `handle_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理时间',
    `deadline` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理截止时间',
    `is_overtime` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否超时 0否 1是',
    `appeal_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '申诉状态 0未申诉 1申诉中 2申诉成功 3申诉失败',
    `appeal_reason` text COMMENT '申诉原因',
    `appeal_images` text COMMENT '申诉图片 JSON数组',
    `appeal_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '申诉时间',
    `close_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '关闭原因',
    `close_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关闭时间',
    `source` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '来源 1小程序 2后台 3电话',
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

-- ----------------------------
-- 补拍申请表
-- ----------------------------
DROP TABLE IF EXISTS `la_reshoot`;
CREATE TABLE `la_reshoot` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '补拍ID',
    `reshoot_sn` varchar(32) NOT NULL DEFAULT '' COMMENT '补拍编号',
    `order_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `order_item_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单项ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID',
    `reason` text COMMENT '补拍原因',
    `images` text COMMENT '图片凭证 JSON数组',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态 0待审核 1已通过 2已拒绝 3已完成 4已取消',
    `audit_admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核人ID',
    `audit_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核时间',
    `audit_remark` varchar(255) NOT NULL DEFAULT '' COMMENT '审核备注',
    `reject_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '拒绝原因',
    `schedule_date` date DEFAULT NULL COMMENT '补拍日期',
    `time_slot` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '补拍时间段 0全天 1早礼 2午宴 3晚宴',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` int(11) UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_reshoot_sn` (`reshoot_sn`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='补拍申请表';

-- ----------------------------
-- 服务回访表
-- ----------------------------
DROP TABLE IF EXISTS `la_service_callback`;
CREATE TABLE `la_service_callback` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '回访ID',
    `order_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `order_item_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单项ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID',
    `callback_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '回访类型 1服务前 2服务中 3服务后',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态 0待回访 1已回访 2已关闭',
    `callback_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回访时间',
    `callback_admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回访人ID',
    `callback_result` text COMMENT '回访结果',
    `satisfaction` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '满意度评分 0未评价 1-5星',
    `satisfaction_remark` varchar(500) NOT NULL DEFAULT '' COMMENT '满意度评价内容',
    `next_callback_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '下次回访时间',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` int(11) UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_status` (`status`),
    KEY `idx_callback_time` (`callback_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='服务回访表';

-- ----------------------------
-- 回访问卷配置表
-- ----------------------------
DROP TABLE IF EXISTS `la_callback_questionnaire`;
CREATE TABLE `la_callback_questionnaire` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `title` varchar(200) NOT NULL DEFAULT '' COMMENT '问卷标题',
    `description` varchar(500) NOT NULL DEFAULT '' COMMENT '问卷描述',
    `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '回访类型 1服务前 2服务中 3服务后',
    `questions` text COMMENT '问题列表 JSON数组',
    `sort` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态 0禁用 1启用',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` int(11) UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_type` (`type`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='回访问卷配置表';

-- ----------------------------
-- 回访问卷答案表
-- ----------------------------
DROP TABLE IF EXISTS `la_callback_answer`;
CREATE TABLE `la_callback_answer` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `callback_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回访ID',
    `questionnaire_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '问卷ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `answers` text COMMENT '答案 JSON数组',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_callback_id` (`callback_id`),
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='回访问卷答案表';

-- ----------------------------
-- 问题升级规则配置表
-- ----------------------------
DROP TABLE IF EXISTS `la_escalate_rule`;
CREATE TABLE `la_escalate_rule` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `name` varchar(100) NOT NULL DEFAULT '' COMMENT '规则名称',
    `ticket_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '工单类型 0全部',
    `priority` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '优先级 0全部',
    `timeout_hours` int(11) UNSIGNED NOT NULL DEFAULT 24 COMMENT '超时时间（小时）',
    `escalate_to` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '升级到人员ID',
    `notify_method` varchar(50) NOT NULL DEFAULT 'system' COMMENT '通知方式 system/sms/wechat',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态 0禁用 1启用',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='问题升级规则配置表';

-- ----------------------------
-- 售后工单统计表（每日汇总）
-- ----------------------------
DROP TABLE IF EXISTS `la_after_sale_daily_stats`;
CREATE TABLE `la_after_sale_daily_stats` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `stat_date` date NOT NULL COMMENT '统计日期',
    `ticket_total` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '工单总数',
    `ticket_new` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '新增工单数',
    `ticket_completed` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '完成工单数',
    `ticket_overtime` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '超时工单数',
    `complaint_total` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '投诉总数',
    `complaint_handled` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '已处理投诉数',
    `reshoot_total` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '补拍申请总数',
    `reshoot_approved` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '已通过补拍数',
    `callback_total` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回访总数',
    `callback_completed` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '已完成回访数',
    `avg_handle_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '平均处理时长（秒）',
    `satisfaction_avg` decimal(3,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '平均满意度',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_stat_date` (`stat_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='售后工单每日统计表';

-- ----------------------------
-- 初始化升级规则
-- ----------------------------
INSERT INTO `la_escalate_rule` (`name`, `ticket_type`, `priority`, `timeout_hours`, `notify_method`, `status`, `create_time`, `update_time`) VALUES
('紧急工单2小时升级', 0, 4, 2, 'system,sms', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('高优先级工单4小时升级', 0, 3, 4, 'system', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('普通工单24小时升级', 0, 2, 24, 'system', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('投诉48小时升级', 1, 0, 48, 'system,sms', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ----------------------------
-- 初始化回访问卷
-- ----------------------------
INSERT INTO `la_callback_questionnaire` (`title`, `description`, `type`, `questions`, `sort`, `status`, `create_time`, `update_time`) VALUES
('服务后满意度调查', '感谢您使用我们的服务，请花1分钟完成以下问卷', 3, '[{"id":1,"type":"rating","title":"整体服务满意度","required":true},{"id":2,"type":"rating","title":"服务人员态度"},{"id":3,"type":"rating","title":"专业水平"},{"id":4,"type":"rating","title":"时间守约"},{"id":5,"type":"text","title":"您的建议","placeholder":"请输入您的宝贵意见..."}]', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- =============================================
-- 012_create_subscribe_message_tables.sql
-- =============================================
-- =====================================================
-- 微信小程序订阅消息系统数据库表
-- 创建时间: 2026-01-20
-- 说明: 支持微信小程序服务消息推送功能
-- =====================================================

-- -----------------------------------------------------
-- 订阅消息模板表
-- 存储微信小程序订阅消息模板配置
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `la_subscribe_message_template` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `template_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '微信模板ID',
    `name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '模板名称',
    `title` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '模板标题',
    `scene` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '使用场景: order_create/order_paid/order_confirm/order_complete/schedule_remind/refund_result/callback_remind/ticket_update/change_result/schedule_change/waitlist_release',
    `content` TEXT COMMENT '模板内容(JSON格式，存储字段配置)',
    `example` TEXT COMMENT '示例内容',
    `keywords` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '关键词列表(逗号分隔)',
    `category_id` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '模板类目ID',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态: 0-禁用 1-启用',
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

-- -----------------------------------------------------
-- 用户订阅记录表
-- 记录用户对各模板的订阅授权情况
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `la_user_subscribe` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `template_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '微信模板ID',
    `scene` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '使用场景',
    `accept_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '累计授权次数(一次性订阅消息每次授权+1)',
    `reject_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '累计拒绝次数',
    `last_accept_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后授权时间',
    `last_reject_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后拒绝时间',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '订阅状态: 0-已拒绝 1-已订阅 2-永久订阅',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_template` (`user_id`, `template_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_template_id` (`template_id`),
    KEY `idx_scene` (`scene`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户订阅记录表';

-- -----------------------------------------------------
-- 订阅消息发送日志表
-- 记录每次消息发送的详细信息
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `la_subscribe_message_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `openid` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '用户OpenID',
    `template_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '微信模板ID',
    `scene` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '使用场景',
    `business_type` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '业务类型: order/schedule/refund/callback/ticket等',
    `business_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '业务ID',
    `content` TEXT COMMENT '发送内容(JSON格式)',
    `page` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '跳转页面路径',
    `miniprogram_state` VARCHAR(20) NOT NULL DEFAULT 'formal' COMMENT '小程序状态: developer/trial/formal',
    `send_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '发送状态: 0-待发送 1-发送成功 2-发送失败',
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

-- -----------------------------------------------------
-- 订阅消息场景配置表
-- 配置各业务场景的消息触发规则
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `la_subscribe_message_scene` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `scene` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '场景标识',
    `name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '场景名称',
    `description` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '场景描述',
    `template_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '关联模板ID',
    `trigger_event` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '触发事件标识',
    `data_mapping` TEXT COMMENT '数据字段映射(JSON)',
    `page_path` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '跳转页面路径',
    `is_auto` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否自动触发：0=否,1=是',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态: 0-禁用 1-启用',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_scene` (`scene`),
    KEY `idx_template_id` (`template_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订阅消息场景配置表';

-- -----------------------------------------------------
-- 初始化订阅消息场景配置
-- -----------------------------------------------------
INSERT INTO `la_subscribe_message_scene` (`scene`, `name`, `description`, `trigger_event`, `page_path`, `is_auto`, `status`, `create_time`, `update_time`) VALUES
('order_create', '订单创建通知', '用户提交订单后发送确认通知', 'OrderCreated', 'pages/order_detail/order_detail', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('order_paid', '支付成功通知', '用户完成支付后发送确认通知', 'OrderPaid', 'pages/order_detail/order_detail', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('order_confirm', '订单确认通知', '商家确认订单后通知用户', 'OrderConfirmed', 'pages/order_detail/order_detail', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('order_complete', '服务完成通知', '服务完成后通知用户进行评价', 'OrderCompleted', 'pages/review/publish', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('schedule_remind', '档期提醒通知', '服务日期前提醒用户', 'ScheduleRemind', 'pages/order_detail/order_detail', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('refund_result', '退款结果通知', '退款审核结果通知', 'RefundProcessed', 'pages/order_detail/order_detail', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('callback_remind', '回访提醒通知', '服务完成后的回访提醒', 'CallbackRemind', 'pages/aftersale/callback', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('ticket_update', '工单进度通知', '售后工单状态更新通知', 'TicketUpdated', 'pages/aftersale/ticket_detail', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('change_result', '变更审核通知', '订单变更申请审核结果通知', 'ChangeProcessed', 'pages/order_change/change_detail', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('schedule_change', '档期变更通知', '人员档期发生变更时通知', 'ScheduleChanged', 'pages/order_detail/order_detail', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

INSERT INTO `la_subscribe_message_scene` (`scene`, `name`, `description`, `trigger_event`, `data_mapping`, `page_path`, `is_auto`, `status`, `create_time`, `update_time`) VALUES
('waitlist_release', '候补释放通知', '档期释放后通知候补用户', 'WaitlistReleased', '{"thing1":"staff_name","time2":"schedule_date","thing3":"time_slot_desc","thing4":"package_name","thing5":"remark"}', 'packages/pages/waitlist/waitlist', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- -----------------------------------------------------
-- 初始化示例模板配置
-- 注意: template_id需要在微信后台申请后填入
-- -----------------------------------------------------
INSERT INTO `la_subscribe_message_template` (`template_id`, `name`, `title`, `scene`, `content`, `keywords`, `status`, `sort`, `remark`, `create_time`, `update_time`) VALUES
('TEMPLATE_ID_ORDER_CREATE', '订单提交成功通知', '订单提交成功', 'order_create', '{"thing1":{"key":"订单内容","value":""},"character_string2":{"key":"订单编号","value":""},"amount3":{"key":"订单金额","value":""},"time4":{"key":"下单时间","value":""}}', '订单内容,订单编号,订单金额,下单时间', 1, 100, '订单创建后发送，需在微信后台申请模板后更新template_id', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('TEMPLATE_ID_ORDER_PAID', '支付成功通知', '支付成功', 'order_paid', '{"character_string1":{"key":"订单编号","value":""},"amount2":{"key":"支付金额","value":""},"time3":{"key":"支付时间","value":""},"thing4":{"key":"商品名称","value":""}}', '订单编号,支付金额,支付时间,商品名称', 1, 99, '支付完成后发送，需在微信后台申请模板后更新template_id', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('TEMPLATE_ID_SERVICE_REMIND', '服务提醒通知', '服务提醒', 'schedule_remind', '{"thing1":{"key":"服务内容","value":""},"time2":{"key":"服务时间","value":""},"thing3":{"key":"服务地点","value":""},"thing4":{"key":"服务人员","value":""}}', '服务内容,服务时间,服务地点,服务人员', 1, 98, '服务前1天/3天提醒，需在微信后台申请模板后更新template_id', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('TEMPLATE_ID_REFUND_RESULT', '退款结果通知', '退款通知', 'refund_result', '{"character_string1":{"key":"订单编号","value":""},"amount2":{"key":"退款金额","value":""},"phrase3":{"key":"退款状态","value":""},"thing4":{"key":"退款原因","value":""}}', '订单编号,退款金额,退款状态,退款原因', 1, 97, '退款审核后发送，需在微信后台申请模板后更新template_id', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('TEMPLATE_ID_TICKET_UPDATE', '工单进度通知', '工单状态更新', 'ticket_update', '{"character_string1":{"key":"工单编号","value":""},"phrase2":{"key":"工单状态","value":""},"thing3":{"key":"处理说明","value":""},"time4":{"key":"更新时间","value":""}}', '工单编号,工单状态,处理说明,更新时间', 1, 96, '工单状态变更时发送，需在微信后台申请模板后更新template_id', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('TEMPLATE_ID_WAITLIST_RELEASE', '候补释放通知', '候补释放', 'waitlist_release', '{"thing1":{"key":"服务人员","value":""},"time2":{"key":"档期日期","value":""},"thing3":{"key":"时间段","value":""},"thing4":{"key":"套餐名称","value":""},"thing5":{"key":"备注","value":""}}', '服务人员,档期日期,时间段,套餐名称,备注', 1, 95, '候补释放后发送，需在微信后台申请模板后更新template_id', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- =============================================
-- 013_create_wedding_menus.sql
-- =============================================
-- =============================================
-- 婚庆服务预约系统 - 管理后台菜单配置
-- 创建日期: 2026-01-20
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 婚庆服务管理一级菜单
-- ----------------------------

-- 服务管理（一级菜单）
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (0, 'M', '服务管理', 'el-icon-User', 900, '', 'service', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @service_menu_id = LAST_INSERT_ID();

-- 服务人员列表
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@service_menu_id, 'C', '服务人员', '', 100, 'staff.staff/lists', 'staff', 'staff/lists/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 服务分类管理
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@service_menu_id, 'C', '服务分类', '', 90, 'service.service_category/lists', 'category', 'service/category/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 服务套餐管理
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@service_menu_id, 'C', '服务套餐', '', 80, 'service.service_package/lists', 'package', 'service/package/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


-- 档期管理（一级菜单）
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (0, 'M', '档期管理', 'el-icon-Calendar', 850, '', 'schedule', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @schedule_menu_id = LAST_INSERT_ID();

-- 档期日历
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@schedule_menu_id, 'C', '档期日历', '', 100, 'schedule.schedule/lists', 'calendar', 'schedule/calendar/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 预约列表
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@schedule_menu_id, 'C', '预约列表', '', 90, 'schedule.booking/lists', 'booking', 'schedule/booking/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 候补列表
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@schedule_menu_id, 'C', '候补列表', '', 80, 'schedule.waitlist/lists', 'waitlist', 'schedule/waitlist/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


-- 订单管理（一级菜单）
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (0, 'M', '订单管理', 'el-icon-Document', 800, '', 'order', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @order_menu_id = LAST_INSERT_ID();

-- 订单列表
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@order_menu_id, 'C', '订单列表', '', 100, 'order.order/lists', 'lists', 'order/lists/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 退款管理
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@order_menu_id, 'C', '退款管理', '', 90, 'order.refund/lists', 'refund', 'order/refund/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 订单变更
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@order_menu_id, 'C', '订单变更', '', 80, 'order.order_change/lists', 'change', 'order/change/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 订单转让
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@order_menu_id, 'C', '订单转让', '', 70, 'order.order_transfer/lists', 'transfer', 'order/transfer/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 订单暂停
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@order_menu_id, 'C', '订单暂停', '', 60, 'order.order_pause/lists', 'pause', 'order/pause/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


-- 动态社区（一级菜单）
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (0, 'M', '动态社区', 'el-icon-ChatDotRound', 750, '', 'dynamic', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @dynamic_menu_id = LAST_INSERT_ID();

-- 动态列表
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@dynamic_menu_id, 'C', '动态列表', '', 100, 'dynamic.dynamic/lists', 'lists', 'dynamic/lists/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


-- 评价管理（一级菜单）
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (0, 'M', '评价管理', 'el-icon-Star', 700, '', 'review', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @review_menu_id = LAST_INSERT_ID();

-- 评价列表
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@review_menu_id, 'C', '评价列表', '', 100, 'review.review/lists', 'lists', 'review/lists/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


-- 财务管理（一级菜单）
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (0, 'M', '财务管理', 'el-icon-Coin', 650, '', 'financial', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @financial_menu_id = LAST_INSERT_ID();

-- 资金流水
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@financial_menu_id, 'C', '资金流水', '', 100, 'financial.flow/lists', 'flow', 'financial/flow/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 结算管理
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@financial_menu_id, 'C', '结算管理', '', 80, 'financial.settlement/lists', 'settlement', 'financial/settlement/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 成本管理
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@financial_menu_id, 'C', '成本管理', '', 70, 'financial.cost/lists', 'cost', 'financial/cost/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 发票管理
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@financial_menu_id, 'C', '发票管理', '', 60, 'financial.invoice/lists', 'invoice', 'financial/invoice/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


-- CRM管理（一级菜单）
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (0, 'M', 'CRM管理', 'el-icon-Phone', 600, '', 'crm', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @crm_menu_id = LAST_INSERT_ID();

-- 客户管理
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@crm_menu_id, 'C', '客户管理', '', 100, 'crm.customer/lists', 'customer', 'crm/customer/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 顾问管理
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@crm_menu_id, 'C', '顾问管理', '', 90, 'crm.sales_advisor/lists', 'advisor', 'crm/advisor/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 流失预警
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@crm_menu_id, 'C', '流失预警', '', 80, 'crm.customer_loss_warning/lists', 'warning', 'crm/warning/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


-- 售后服务（一级菜单）
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (0, 'M', '售后服务', 'el-icon-Service', 550, '', 'aftersale', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @aftersale_menu_id = LAST_INSERT_ID();

-- 售后工单
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@aftersale_menu_id, 'C', '售后工单', '', 100, 'aftersale.aftersale/ticketLists', 'ticket', 'aftersale/ticket/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


-- 营销管理（一级菜单）
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (0, 'M', '营销管理', 'el-icon-Present', 500, '', 'marketing', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @marketing_menu_id = LAST_INSERT_ID();

-- 优惠券管理
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@marketing_menu_id, 'C', '优惠券管理', '', 100, 'coupon.coupon/lists', 'coupon', 'coupon/lists/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


-- 消息中心（一级菜单）
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (0, 'M', '消息中心', 'el-icon-Bell', 450, '', 'message', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @message_menu_id = LAST_INSERT_ID();

-- 消息通知
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@message_menu_id, 'C', '消息通知', '', 100, 'notification.notification/lists', 'notification', 'notification/lists/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 订阅消息
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@message_menu_id, 'C', '订阅消息', '', 90, 'subscribe.subscribe/templateList', 'subscribe', 'subscribe/template/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


-- 时间线管理（一级菜单）
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (0, 'M', '时间线管理', 'el-icon-Timer', 400, '', 'timeline', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @timeline_menu_id = LAST_INSERT_ID();

-- 时间线模板
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@timeline_menu_id, 'C', '时间线模板', '', 100, 'timeline.timeline/templateList', 'lists', 'timeline/lists/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- 014_update_package_system.sql
-- =============================================
-- +----------------------------------------------------------------------
-- | 婚庆服务预约系统 - 套餐系统优化迁移脚本
-- | 功能：混合模式套餐绑定、时段差异化定价、单日唯一预订限制
-- +----------------------------------------------------------------------

-- ===================================================================
-- 1. 修改 la_service_package 表 - 添加套餐类型和时段价格支持
-- ===================================================================
ALTER TABLE `la_service_package` 
ADD COLUMN `staff_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属工作人员ID（0=全局套餐）' AFTER `category_id`,
ADD COLUMN `package_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '套餐类型：1=全局套餐,2=人员专属套餐' AFTER `staff_id`,
ADD COLUMN `slot_prices` TEXT COMMENT '时段价格配置JSON：[{"start_time":"08:00","end_time":"12:00","price":1000}]' AFTER `price`,
ADD INDEX `idx_staff_id` (`staff_id`),
ADD INDEX `idx_package_type` (`package_type`);

-- ===================================================================
-- 2. 修改 la_staff_package 表 - 添加个人定制价格和时段价格
-- ===================================================================
ALTER TABLE `la_staff_package` 
ADD COLUMN `custom_price` DECIMAL(10,2) DEFAULT NULL COMMENT '个人定制价格（覆盖套餐默认价格）' AFTER `package_id`,
ADD COLUMN `custom_slot_prices` TEXT COMMENT '个人时段价格配置JSON' AFTER `custom_price`,
ADD COLUMN `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=禁用,1=启用' AFTER `custom_slot_prices`;

-- ===================================================================
-- 3. 新建 la_package_booking 表 - 套餐预订记录（单日唯一限制）
-- ===================================================================
CREATE TABLE IF NOT EXISTS `la_package_booking` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT '主键ID',
  `package_id` INT UNSIGNED NOT NULL COMMENT '套餐ID',
  `staff_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID（0=不限人员）',
  `booking_date` DATE NOT NULL COMMENT '预订日期',
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
  UNIQUE KEY `uk_package_date_active` (`package_id`, `booking_date`, `status`),
  INDEX `idx_staff_id` (`staff_id`),
  INDEX `idx_order_id` (`order_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_lock_expire` (`lock_expire_time`),
  INDEX `idx_booking_date` (`booking_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='套餐预订记录表（用于单日唯一限制）';

-- ===================================================================
-- 4. 更新现有数据 - 将现有套餐标记为全局套餐
-- ===================================================================
UPDATE `la_service_package` SET `package_type` = 1, `staff_id` = 0 WHERE `package_type` IS NULL OR `package_type` = 0;

-- ===================================================================
-- 5. 添加清理过期锁定的事件（可选，需要MySQL开启事件调度器）
-- ===================================================================
-- SET GLOBAL event_scheduler = ON;

-- DROP EVENT IF EXISTS `clear_expired_package_locks`;
-- CREATE EVENT `clear_expired_package_locks`
-- ON SCHEDULE EVERY 5 MINUTE
-- DO
--   UPDATE `la_package_booking` 
--   SET `status` = 0 
--   WHERE `status` = 1 
--   AND `lock_expire_time` IS NOT NULL 
--   AND `lock_expire_time` < UNIX_TIMESTAMP();

-- =============================================
-- 015_add_staff_add_edit_menu.sql
-- =============================================
-- =============================================
-- 服务人员：新增「添加/编辑」菜单，修复「新增人员」按钮无法跳转
-- 原因：getRoutePath('staff.staff/add:edit') 依赖该菜单的 perms，缺省时返回空导致 router-link 无跳转
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 在「服务管理」下新增「服务人员添加/编辑」页面（不在侧栏展示，供新增/编辑人员跳转）
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'C', '服务人员添加/编辑', '', 99, 'staff.staff/add:edit', 'staff/edit', 'staff/lists/edit', '/service/staff', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `paths` = 'service' AND `type` = 'M'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'staff.staff/add:edit')
LIMIT 1;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- 016_add_service_category_level.sql
-- =============================================
-- =============================================
-- 婚庆服务预约系统 - 数据库更新
-- 添加服务分类层级字段
-- 创建日期: 2026-01-23
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 为 la_service_category 表添加 level 字段
-- ----------------------------
ALTER TABLE `la_service_category` 
ADD COLUMN `level` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '分类层级:1-一级,2-二级,3-三级' AFTER `pid`;

-- ----------------------------
-- 更新现有数据的 level 值
-- 所有现有分类都是一级分类（pid=0）
-- ----------------------------
UPDATE `la_service_category` SET `level` = 1 WHERE `pid` = 0;

-- ----------------------------
-- 添加索引
-- ----------------------------
ALTER TABLE `la_service_category` 
ADD KEY `idx_level` (`level`) USING BTREE;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- 017_rollback_simplify_service_category.sql
-- =============================================
-- +----------------------------------------------------------------------
-- | 婚庆服务预约系统 - 服务分类表结构简化回滚脚本
-- +----------------------------------------------------------------------
-- | 说明：恢复 pid 和 level 字段，用于回滚到多级分类结构
-- | 创建时间：2026-01-23
-- +----------------------------------------------------------------------

-- 1. 恢复 pid 字段
ALTER TABLE `la_service_category` 
ADD COLUMN `pid` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '上级分类ID' AFTER `name`;

-- 2. 恢复 level 字段
ALTER TABLE `la_service_category` 
ADD COLUMN `level` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '分类层级:1-一级,2-二级,3-三级' AFTER `pid`;

-- 3. 恢复索引
ALTER TABLE `la_service_category` ADD KEY `idx_pid` (`pid`) USING BTREE;
ALTER TABLE `la_service_category` ADD KEY `idx_level` (`level`) USING BTREE;

-- 4. 恢复数据（所有分类设置为一级分类）
UPDATE `la_service_category` SET `pid` = 0, `level` = 1 WHERE `delete_time` IS NULL;

-- =============================================
-- 017_simplify_service_category.sql
-- =============================================
-- +----------------------------------------------------------------------
-- | 婚庆服务预约系统 - 服务分类表结构简化
-- +----------------------------------------------------------------------
-- | 说明：移除冗余的 pid 和 level 字段，简化为扁平化结构
-- | 创建时间：2026-01-23
-- +----------------------------------------------------------------------

-- 检查并移除 level 字段（如果存在）
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'la_service_category' 
    AND COLUMN_NAME = 'level');

SET @sql = IF(@col_exists > 0, 
    'ALTER TABLE `la_service_category` DROP COLUMN `level`', 
    'SELECT "Column level does not exist, skipping..."');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 注意：不删除 pid 字段，因为它在原始表结构中存在
-- 如果需要删除 pid 字段，请确认业务逻辑不再需要它

-- 移除 level 相关索引（如果存在）
SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'la_service_category' 
    AND INDEX_NAME = 'idx_level');

SET @sql = IF(@idx_exists > 0, 
    'ALTER TABLE `la_service_category` DROP INDEX `idx_level`', 
    'SELECT "Index idx_level does not exist, skipping..."');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =============================================
-- 018_add_dynamic_allow_comment.sql
-- =============================================
-- 添加动态评论开关字段
-- 日期: 2025-01-24
-- 描述: 为 la_dynamic 表添加 allow_comment 字段，用于控制是否允许用户评论

-- 添加 allow_comment 字段
ALTER TABLE `la_dynamic` 
ADD COLUMN `allow_comment` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 
COMMENT '是否允许评论：0=禁止，1=允许' 
AFTER `tags`;

-- 为已存在的记录设置默认值（确保向后兼容）
UPDATE `la_dynamic` SET `allow_comment` = 1 WHERE `allow_comment` = 0 OR `allow_comment` IS NULL;

-- =============================================
-- 018_add_dynamic_allow_comment_simple.sql
-- =============================================
-- 添加动态评论开关字段（简化版）
-- 日期: 2025-01-24
-- 描述: 为 la_dynamic 表添加 allow_comment 字段

-- 检查字段是否已存在，如果不存在则添加
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'la_dynamic' 
    AND COLUMN_NAME = 'allow_comment');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `la_dynamic` ADD COLUMN `allow_comment` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT ''是否允许评论：0=禁止，1=允许'' AFTER `tags`', 
    'SELECT "Column allow_comment already exists, skipping..."');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 确保所有现有记录的 allow_comment 字段为 1
UPDATE `la_dynamic` SET `allow_comment` = 1 WHERE `allow_comment` = 0 OR `allow_comment` IS NULL;

-- =============================================
-- 019_add_comment_review_fields.sql
-- =============================================
-- 添加动态评论审核字段
-- 日期: 2025-01-24
-- 描述: 为 la_dynamic_comment 表添加审核相关字段，支持评论审核功能

-- 添加 review_status 字段（审核状态）
ALTER TABLE `la_dynamic_comment` 
ADD COLUMN `review_status` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 
COMMENT '审核状态：0=待审核，1=已通过，2=已拒绝' 
AFTER `status`;

-- 添加 review_admin_id 字段（审核管理员ID）
ALTER TABLE `la_dynamic_comment` 
ADD COLUMN `review_admin_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 
COMMENT '审核管理员ID' 
AFTER `review_status`;

-- 添加 review_time 字段（审核时间）
ALTER TABLE `la_dynamic_comment` 
ADD COLUMN `review_time` INT(11) UNSIGNED NOT NULL DEFAULT 0 
COMMENT '审核时间' 
AFTER `review_admin_id`;

-- 添加 review_remark 字段（审核备注/拒绝原因）
ALTER TABLE `la_dynamic_comment` 
ADD COLUMN `review_remark` VARCHAR(255) NOT NULL DEFAULT '' 
COMMENT '审核备注（拒绝原因）' 
AFTER `review_time`;

-- 为已存在的评论记录设置 review_status = 1（已通过），保持向后兼容
UPDATE `la_dynamic_comment` SET `review_status` = 1 WHERE `review_status` = 0;

-- =============================================
-- 020_add_comment_review_menus.sql
-- =============================================
-- =============================================
-- 动态评论审核功能 - 菜单配置
-- 创建日期: 2026-01-24
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 获取动态社区菜单ID
-- ----------------------------
SET @dynamic_menu_id = (SELECT id FROM `la_system_menu` WHERE `name` = '动态社区' AND `pid` = 0 LIMIT 1);

-- ----------------------------
-- 添加评论审核菜单
-- ----------------------------
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@dynamic_menu_id, 'C', '评论审核', '', 90, 'dynamic.dynamicComment/reviewList', 'comment/review', 'dynamic/comment/review', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ----------------------------
-- 添加动态配置菜单
-- ----------------------------
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@dynamic_menu_id, 'C', '动态配置', '', 80, 'dynamic.dynamicComment/getReviewConfig', 'config', 'dynamic/config/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- 021_update_booking_logic.sql
-- =============================================
-- +----------------------------------------------------------------------
-- | 婚庆服务预约系统 - 预约逻辑调整（套餐类型/场次/候补批次/套餐预订锁）
-- | 说明：保持与现有 JSON 文本字段一致，允许空值，由应用层补默认值
-- +----------------------------------------------------------------------

-- 1. 服务套餐新增预约类型与允许场次配置
ALTER TABLE `la_service_package`
ADD COLUMN `booking_type` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '预约类型：0=全天套餐,1=分场次套餐' AFTER `slot_prices`,
ADD COLUMN `allowed_time_slots` TEXT NULL COMMENT '允许场次(JSON数组)' AFTER `booking_type`;

-- 2. 工作人员套餐新增预约类型与允许场次覆盖配置
ALTER TABLE `la_staff_package`
ADD COLUMN `booking_type` TINYINT UNSIGNED NULL DEFAULT NULL COMMENT '预约类型覆盖：0=全天套餐,1=分场次套餐' AFTER `custom_slot_prices`,
ADD COLUMN `allowed_time_slots` TEXT NULL COMMENT '允许场次覆盖(JSON数组)' AFTER `booking_type`;

-- 3. 套餐预订锁新增 time_slot 维度并更新唯一索引
ALTER TABLE `la_package_booking`
ADD COLUMN `time_slot` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '时间段：0=全天,1=早礼,2=午宴,3=晚宴' AFTER `booking_date`;

ALTER TABLE `la_package_booking`
DROP INDEX `uk_package_date_active`,
ADD UNIQUE KEY `uk_staff_package_date_slot` (`staff_id`, `package_id`, `booking_date`, `time_slot`);

-- 4. 候补表新增批次号（整单候补）
ALTER TABLE `la_waitlist`
ADD COLUMN `batch_no` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '候补批次号' AFTER `package_id`;

-- 5. 兼容既有数据：默认全量可选场次由应用层补齐
UPDATE `la_service_package` SET `booking_type` = 0 WHERE `booking_type` IS NULL;

-- =============================================
-- 023_add_service_tag_menu.sql
-- =============================================
-- =============================================
-- 服务管理：新增“服务人员标签”菜单与权限
-- 目的：后台可进入标签管理页面并开放新增/编辑/删除/状态权限
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 列表页面菜单（服务管理下）
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'C', '服务人员标签', '', 70, 'service.styleTag/lists', 'tag', 'service/tag/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `paths` = 'service' AND `type` = 'M'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'service.styleTag/lists')
LIMIT 1;

-- 按钮权限（挂在“服务人员标签”菜单下）
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '新增', '', 0, 'service.styleTag/add', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'service.styleTag/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'service.styleTag/add')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '编辑', '', 0, 'service.styleTag/edit', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'service.styleTag/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'service.styleTag/edit')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '删除', '', 0, 'service.styleTag/delete', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'service.styleTag/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'service.styleTag/delete')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '状态切换', '', 0, 'service.styleTag/changeStatus', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'service.styleTag/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'service.styleTag/changeStatus')
LIMIT 1;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- 025_add_staff_banner.sql
-- =============================================
-- 人员轮播图功能
-- 日期：2026-01-29

-- 1. la_staff 表新增轮播图配置字段
ALTER TABLE `la_staff`
ADD COLUMN `banner_mode` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '轮播图展示模式：1=小图模式，2=大图模式' AFTER `service_desc`,
ADD COLUMN `banner_small_height` INT(11) UNSIGNED NOT NULL DEFAULT 400 COMMENT '小图模式初始高度（rpx）' AFTER `banner_mode`,
ADD COLUMN `banner_large_height` INT(11) UNSIGNED NOT NULL DEFAULT 600 COMMENT '大图模式/展开后高度（rpx）' AFTER `banner_small_height`,
ADD COLUMN `banner_indicator_style` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '指示器样式：1=圆点，2=数字，3=进度条，0=无' AFTER `banner_large_height`,
ADD COLUMN `banner_autoplay` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否自动轮播：0=否，1=是' AFTER `banner_indicator_style`,
ADD COLUMN `banner_interval` INT(11) UNSIGNED NOT NULL DEFAULT 3000 COMMENT '轮播间隔时间（毫秒）' AFTER `banner_autoplay`;

-- 2. 创建人员轮播图关联表
CREATE TABLE IF NOT EXISTS `la_staff_banner` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `staff_id` INT(11) UNSIGNED NOT NULL COMMENT '人员ID',
  `type` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '类型：1=图片，2=视频',
  `file_url` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '文件地址',
  `cover_url` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '封面图地址（视频必填）',
  `is_autoplay` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '视频是否自动播放：0=否，1=是',
  `sort` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序（数字越小越靠前）',
  `create_time` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_staff_id` (`staff_id`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='人员轮播图表';

-- =============================================
-- 025_add_staff_package_original_price.sql
-- =============================================
-- +----------------------------------------------------------------------
-- | 婚庆服务预约系统 - 服务人员套餐添加原价字段
-- | 功能：支持配置关联全局套餐的默认价格与原价
-- +----------------------------------------------------------------------

-- ===================================================================
-- 修改 la_staff_package 表 - 添加原价字段
-- ===================================================================
ALTER TABLE `la_staff_package` 
ADD COLUMN `original_price` DECIMAL(10,2) DEFAULT NULL COMMENT '原价（用于显示划线价）' AFTER `price`,
ADD COLUMN `update_time` INT(10) UNSIGNED DEFAULT NULL COMMENT '更新时间' AFTER `create_time`;

-- ===================================================================
-- 说明：
-- price: 默认价格（必填，继承自全局套餐或自定义）
-- original_price: 原价（选填，用于显示划线价）
-- custom_price: 个人定制价格（选填，覆盖 price）
-- custom_slot_prices: 个人时段价格（选填，JSON格式）
-- ===================================================================

SET FOREIGN_KEY_CHECKS=1;

-- =============================================
-- 026_add_staff_admin_feature_switch.sql
-- =============================================

-- =============================================
-- 服务人员后台账号关联 + 功能开关 + 角色菜单
-- 创建日期: 2026-02-01
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. la_staff 增加 admin_id 关联后台账号
ALTER TABLE `la_staff`
    ADD COLUMN `admin_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联后台管理员ID' AFTER `user_id`,
    ADD KEY `idx_admin_id` (`admin_id`);

-- 2. 功能开关配置（feature_switch）
INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'feature_switch', 'staff_center', '1', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'feature_switch' AND `name` = 'staff_center'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'feature_switch', 'staff_admin', '1', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'feature_switch' AND `name` = 'staff_admin'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'feature_switch', 'admin_dashboard', '1', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'feature_switch' AND `name` = 'admin_dashboard'
);

-- 3. 新增角色：服务人员
INSERT INTO `la_system_role` (`name`, `desc`, `sort`, `create_time`, `update_time`)
SELECT '服务人员', '服务人员', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_role` WHERE `name` = '服务人员' AND `delete_time` IS NULL
);

SET @staff_role_id = (
    SELECT `id` FROM `la_system_role`
    WHERE `name` = '服务人员' AND `delete_time` IS NULL
    ORDER BY `id` DESC
    LIMIT 1
);

-- 4. 服务人员中心菜单
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT 0, 'M', '服务人员中心', 'el-icon-User', 720, '', 'staff_center', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `paths` = 'staff_center' AND `type` = 'M'
);

SET @staff_center_menu_id = (
    SELECT `id` FROM `la_system_menu`
    WHERE `paths` = 'staff_center' AND `type` = 'M'
    ORDER BY `id` DESC
    LIMIT 1
);

-- 我的资料
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_center_menu_id, 'C', '我的资料', '', 100, 'staff.staff/lists', 'profile', 'staff/lists/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'profile'
);

-- 档期日历
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_center_menu_id, 'C', '档期日历', '', 90, 'schedule.schedule/lists', 'calendar', 'schedule/calendar/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'calendar'
);

-- 档期规则
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_center_menu_id, 'C', '档期规则', '', 80, 'schedule.scheduleRule/lists', 'rule', 'schedule/rule/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'rule'
);

-- 预约列表
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_center_menu_id, 'C', '预约列表', '', 70, 'schedule.booking/lists', 'booking', 'schedule/booking/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'booking'
);

-- 候补列表
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_center_menu_id, 'C', '候补列表', '', 60, 'schedule.waitlist/lists', 'waitlist', 'schedule/waitlist/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'waitlist'
);

-- 订单列表
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_center_menu_id, 'C', '订单列表', '', 50, 'order.order/lists', 'order', 'order/lists/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'order'
);

-- 动态管理
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_center_menu_id, 'C', '动态管理', '', 40, 'dynamic.dynamic/lists', 'dynamic', 'dynamic/lists/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'dynamic'
);

-- 5. 角色关联菜单（服务人员）
-- 关联服务人员中心菜单及必要权限（若菜单已存在则仅建立关联）
INSERT INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT @staff_role_id, m.id
FROM `la_system_menu` m
WHERE m.pid = @staff_center_menu_id
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_role_menu` rm
      WHERE rm.role_id = @staff_role_id AND rm.menu_id = m.id
  );

-- 关联服务人员编辑页（隐藏菜单）
INSERT INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT @staff_role_id, m.id
FROM `la_system_menu` m
WHERE m.perms = 'staff.staff/add:edit'
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_role_menu` rm
      WHERE rm.role_id = @staff_role_id AND rm.menu_id = m.id
  )
LIMIT 1;

-- 补充服务人员操作权限（若菜单存在则关联）
INSERT INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT @staff_role_id, m.id
FROM `la_system_menu` m
WHERE m.perms IN (
    'staff.staff/detail',
    'staff.staff/edit',
    'schedule.schedule/monthCalendar',
    'schedule.schedule/detail',
    'schedule.schedule/setStatus',
    'schedule.schedule/batchSet',
    'schedule.schedule/unlock',
    'schedule.schedule/statistics',
    'schedule.schedule/lockRecords',
    'schedule.scheduleRule/detail',
    'schedule.scheduleRule/add',
    'schedule.scheduleRule/edit',
    'schedule.scheduleRule/delete',
    'schedule.scheduleRule/changeStatus',
    'schedule.scheduleRule/staffRule',
    'schedule.waitlist/detail',
    'schedule.waitlist/notify',
    'schedule.waitlist/batchNotify',
    'schedule.waitlist/convert',
    'schedule.waitlist/invalidate',
    'schedule.waitlist/statistics',
    'order.order/detail',
    'order.order/startService',
    'order.order/complete',
    'order.order/cancel',
    'order.order/auditVoucher',
    'order.order/logs',
    'order.order/addRemark',
    'order.order/statistics'
)
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_role_menu` rm
      WHERE rm.role_id = @staff_role_id AND rm.menu_id = m.id
  );

-- 6. 功能开关配置菜单（系统设置）
SET @setting_menu_id = (
    SELECT `id` FROM `la_system_menu`
    WHERE `paths` = 'setting' AND `type` = 'M'
    ORDER BY `id` DESC
    LIMIT 1
);

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @setting_menu_id, 'C', '功能开关', 'el-icon-Switch', 55, 'setting.feature_switch/getConfig', 'feature_switch', 'setting/feature_switch/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @setting_menu_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_menu` WHERE `perms` = 'setting.feature_switch/getConfig'
  );

-- 保存权限
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT m.id, 'A', '保存', '', 0, 'setting.feature_switch/setConfig', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu` m
WHERE m.perms = 'setting.feature_switch/getConfig'
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_menu` WHERE `perms` = 'setting.feature_switch/setConfig'
  )
LIMIT 1;

SET FOREIGN_KEY_CHECKS = 1;
