-- =============================================
-- 婚庆服务预约系统 - 第一阶段数据库表
-- 工作人员管理 + 服务分类管理
-- 创建日期: 2026-01-19
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

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
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `is_show` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否显示:0-否,1-是',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `delete_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_type` (`type`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='风格标签表';

-- ----------------------------
-- Records of la_style_tag
-- ----------------------------
BEGIN;
INSERT INTO `la_style_tag` VALUES 
(1, '韩式清新', 1, 100, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(2, '中式古典', 1, 90, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(3, '欧式浪漫', 1, 80, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(4, '日系森系', 1, 70, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(5, '简约现代', 1, 60, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(6, '复古怀旧', 1, 50, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(7, '户外自然', 2, 100, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(8, '室内棚拍', 2, 90, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(9, '旅拍跟拍', 2, 80, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(10, '纪实风格', 2, 70, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL);
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

SET FOREIGN_KEY_CHECKS = 1;
