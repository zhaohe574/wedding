-- 全新安装基线，仅用于空数据库。
SET NAMES utf8mb4;
CREATE TABLE `la_activity_payment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `query_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后支付查询时间',
  `closed_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '微信关单确认时间',
  `payment_sn` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '支付流水号',
  `registration_id` int(10) unsigned NOT NULL COMMENT '报名ID',
  `dynamic_id` int(10) unsigned NOT NULL COMMENT '活动动态ID',
  `ticket_id` int(10) unsigned NOT NULL COMMENT '票种ID',
  `user_id` int(10) unsigned NOT NULL COMMENT '用户ID',
  `pay_way` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '支付方式',
  `pay_terminal` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '支付终端',
  `pay_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '支付金额',
  `pay_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '支付状态：0=待支付,1=已支付,2=已退款,3=支付失败',
  `transaction_id` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '第三方交易号',
  `refund_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '已退金额',
  `refund_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '退款时间',
  `expire_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '支付过期时间',
  `pay_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '支付时间',
  `callback_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '回调时间',
  `callback_data` text COLLATE utf8mb4_general_ci COMMENT '回调数据',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_payment_sn` (`payment_sn`),
  UNIQUE KEY `uk_transaction_id` (`transaction_id`),
  KEY `idx_registration_id` (`registration_id`),
  KEY `idx_dynamic_id` (`dynamic_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_pay_expire` (`pay_status`,`expire_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='活动报名支付表';
CREATE TABLE `la_activity_refund` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `refund_sn` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '退款单号',
  `registration_id` int(10) unsigned NOT NULL COMMENT '报名ID',
  `payment_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '支付ID',
  `dynamic_id` int(10) unsigned NOT NULL COMMENT '活动动态ID',
  `ticket_id` int(10) unsigned NOT NULL COMMENT '票种ID',
  `user_id` int(10) unsigned NOT NULL COMMENT '用户ID',
  `refund_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '申请退款金额',
  `actual_refund_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '实际退款金额',
  `refund_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '退款状态：0=待审核,1=审核通过,2=退款中,3=已退款,4=已拒绝,5=退款失败',
  `is_compensation` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '异常实收补偿退款',
  `query_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '退款查询任务领取时间',
  `refund_reason` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '退款原因',
  `audit_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '审核管理员ID',
  `audit_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '审核时间',
  `audit_remark` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '审核备注',
  `third_refund_no` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '第三方退款号',
  `refund_msg` text COLLATE utf8mb4_general_ci COMMENT '退款信息',
  `refund_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '退款完成时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_refund_sn` (`refund_sn`),
  KEY `idx_registration_id` (`registration_id`),
  KEY `idx_payment_id` (`payment_id`),
  KEY `idx_status` (`refund_status`),
  KEY `idx_dynamic_id` (`dynamic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='活动报名退款表';
CREATE TABLE `la_activity_registration` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `dynamic_id` int(10) unsigned NOT NULL COMMENT '活动动态ID',
  `ticket_id` int(10) unsigned NOT NULL COMMENT '票种ID',
  `user_id` int(10) unsigned NOT NULL COMMENT '用户ID',
  `contact_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '联系人',
  `contact_mobile` varchar(30) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '联系电话',
  `remark` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '报名备注',
  `quantity` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '报名人数，固定1',
  `ticket_name` varchar(80) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '票种快照',
  `ticket_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '票价快照',
  `pay_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '应付金额',
  `payment_sn` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '支付流水号',
  `registration_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '报名状态：0=待支付,1=已报名,2=取消审核中,3=已取消,4=退款处理中,5=退款失败',
  `quota_released` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '名额是否已释放',
  `pay_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '支付状态：0=待支付,1=已支付,2=已退款,3=支付失败',
  `pay_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '支付时间',
  `cancel_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '取消状态：0=未申请,1=待审核,2=已通过,3=已拒绝',
  `cancel_reason` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '取消原因',
  `cancel_reject_reason` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '拒绝原因',
  `cancel_apply_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '取消申请时间',
  `cancel_audit_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '取消审核时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_dynamic_id` (`dynamic_id`),
  KEY `idx_ticket_id` (`ticket_id`),
  KEY `idx_user_dynamic` (`user_id`,`dynamic_id`),
  KEY `idx_status` (`registration_status`,`pay_status`,`cancel_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='活动报名表';
CREATE TABLE `la_activity_ticket` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `dynamic_id` int(10) unsigned NOT NULL COMMENT '活动动态ID',
  `name` varchar(80) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '票种名称',
  `price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '票价，0=免费',
  `stock` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '票种库存',
  `sold_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '已占用数量',
  `sale_start_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '票种可购买开始时间，0=不限制',
  `sale_end_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '票种可购买结束时间，0=跟随活动报名截止',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态：0=停用,1=启用',
  `sort` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_dynamic_id` (`dynamic_id`),
  KEY `idx_sale_time` (`sale_start_time`,`sale_end_time`),
  KEY `idx_status_sort` (`status`,`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='活动票种表';
CREATE TABLE `la_admin` (
  `user_id` int(10) unsigned DEFAULT NULL,
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `root` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否超级管理员 0-否 1-是',
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '名称',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户头像',
  `account` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '账号',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '密码',
  `login_time` int(10) DEFAULT NULL COMMENT '最后登录时间',
  `login_ip` varchar(39) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '最后登录ip',
  `multipoint_login` tinyint(1) unsigned DEFAULT '1' COMMENT '是否支持多处登录：1-是；0-否；',
  `force_password_reset` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否必须重置密码：0-否；1-是',
  `disable` tinyint(1) unsigned DEFAULT '0' COMMENT '是否禁用：0-否；1-是；',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) DEFAULT NULL COMMENT '修改时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='管理员表';
CREATE TABLE `la_admin_dept` (
  `admin_id` int(10) NOT NULL DEFAULT '0' COMMENT '管理员id',
  `dept_id` int(10) NOT NULL DEFAULT '0' COMMENT '部门id',
  PRIMARY KEY (`admin_id`,`dept_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='部门关联表';
CREATE TABLE `la_admin_jobs` (
  `admin_id` int(10) NOT NULL COMMENT '管理员id',
  `jobs_id` int(10) NOT NULL COMMENT '岗位id',
  PRIMARY KEY (`admin_id`,`jobs_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='岗位关联表';
CREATE TABLE `la_admin_role` (
  `admin_id` int(10) NOT NULL COMMENT '管理员id',
  `role_id` int(10) NOT NULL COMMENT '角色id',
  PRIMARY KEY (`admin_id`,`role_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='角色关联表';
CREATE TABLE `la_admin_session` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) unsigned NOT NULL COMMENT '用户id',
  `terminal` tinyint(1) NOT NULL DEFAULT '1' COMMENT '客户端类型：1-pc管理后台 2-mobile手机管理后台',
  `token` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '令牌',
  `update_time` int(10) DEFAULT NULL COMMENT '更新时间',
  `expire_time` int(10) NOT NULL COMMENT '到期时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `admin_id_client` (`admin_id`,`terminal`) USING BTREE COMMENT '一个用户在一个终端只有一个token',
  UNIQUE KEY `token` (`token`) USING BTREE COMMENT 'token是唯一的'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='管理员会话表';
CREATE TABLE `la_after_sale_daily_stats` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `stat_date` date NOT NULL COMMENT '统计日期',
  `ticket_total` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '工单总数',
  `ticket_new` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '新增工单数',
  `ticket_completed` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '完成工单数',
  `ticket_overtime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '超时工单数',
  `complaint_total` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '投诉总数',
  `complaint_handled` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '已处理投诉数',
  `reshoot_total` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '补拍申请总数',
  `reshoot_approved` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '已通过补拍数',
  `callback_total` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '回访总数',
  `callback_completed` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '已完成回访数',
  `avg_handle_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '平均处理时长（秒）',
  `satisfaction_avg` decimal(3,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '平均满意度',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_stat_date` (`stat_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='售后工单每日统计表';
CREATE TABLE `la_after_sale_ticket` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '工单ID',
  `ticket_sn` varchar(32) NOT NULL DEFAULT '' COMMENT '工单编号',
  `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关联订单ID',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '工单类型:1-投诉,2-咨询,3-售后,4-建议,5-其他',
  `priority` tinyint(1) unsigned NOT NULL DEFAULT '2' COMMENT '优先级:1-低,2-中,3-高,4-紧急',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '工单标题',
  `content` text COMMENT '工单内容',
  `images` text COMMENT '图片凭证(JSON数组)',
  `contact_name` varchar(50) NOT NULL DEFAULT '' COMMENT '联系人姓名',
  `contact_phone` varchar(20) NOT NULL DEFAULT '' COMMENT '联系电话',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态:0-待分配,1-处理中,2-待确认,3-已完成,4-已关闭,5-已取消',
  `assign_admin_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '处理人ID',
  `assign_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分配时间',
  `handle_result` text COMMENT '处理结果',
  `handle_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '处理时间',
  `close_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '关闭原因',
  `close_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关闭时间',
  `satisfaction` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '满意度评分:0-未评价,1-5星',
  `satisfaction_remark` varchar(500) NOT NULL DEFAULT '' COMMENT '满意度评价内容',
  `expect_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '期望处理时间',
  `deadline` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '处理截止时间',
  `is_overtime` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否超时:0-否,1-是',
  `escalate_level` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '升级次数',
  `escalate_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '最后升级时间',
  `source` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '来源:1-小程序,2-后台,3-电话',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ticket_sn` (`ticket_sn`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_assign_admin_id` (`assign_admin_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='售后工单表';
CREATE TABLE `la_after_sale_ticket_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `ticket_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '工单ID',
  `operator_type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '操作人类型:1-用户,2-管理员,3-系统',
  `operator_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作人ID',
  `action` varchar(50) NOT NULL DEFAULT '' COMMENT '操作动作',
  `old_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '操作前状态',
  `new_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '操作后状态',
  `content` text COMMENT '操作内容/备注',
  `images` text COMMENT '附件图片(JSON数组)',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_ticket_id` (`ticket_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='工单处理记录表';
CREATE TABLE `la_article` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '文章id',
  `cid` int(11) NOT NULL COMMENT '文章分类',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '文章标题',
  `desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '简介',
  `abstract` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '文章摘要',
  `image` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '文章图片',
  `author` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '作者',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '文章内容',
  `click_virtual` int(10) DEFAULT '0' COMMENT '虚拟浏览量',
  `click_actual` int(11) DEFAULT '0' COMMENT '实际浏览量',
  `is_show` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否显示:1-是.0-否',
  `sort` int(5) DEFAULT '0' COMMENT '排序',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='文章表';
CREATE TABLE `la_article_cate` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '文章分类id',
  `name` varchar(90) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '分类名称',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  `is_show` tinyint(1) DEFAULT '1' COMMENT '是否显示:1-是;0-否',
  `create_time` int(10) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='文章分类表';
CREATE TABLE `la_article_collect` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `article_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '文章ID',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '收藏状态 0-未收藏 1-已收藏',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='文章收藏表';
CREATE TABLE `la_calendar_event` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `event_date` date NOT NULL COMMENT '日期',
  `lunar_date` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '农历日期',
  `is_lucky_day` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否吉日：0=否,1=是',
  `lucky_events` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '宜（如：结婚,订婚）',
  `unlucky_events` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '忌',
  `is_holiday` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否节假日：0=否,1=是',
  `holiday_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '节假日名称',
  `congestion_level` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '拥堵等级：0=未知,1=低,2=中,3=高',
  `remark` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_event_date` (`event_date`),
  KEY `idx_is_lucky_day` (`is_lucky_day`),
  KEY `idx_is_holiday` (`is_holiday`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='黄历/吉日表';
CREATE TABLE `la_callback_answer` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `callback_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '回访ID',
  `questionnaire_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '问卷ID',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `answers` text COMMENT '答案(JSON数组)',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_callback_id` (`callback_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='回访问卷答案表';
CREATE TABLE `la_callback_questionnaire` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '问卷标题',
  `description` varchar(500) NOT NULL DEFAULT '' COMMENT '问卷描述',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '回访类型:1-服务前,2-服务中,3-服务后',
  `questions` text COMMENT '问题列表(JSON数组)',
  `sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态:0-禁用,1-启用',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='回访问卷配置表';
INSERT INTO `la_callback_questionnaire` (`id`,`title`,`description`,`type`,`questions`,`sort`,`status`,`create_time`,`update_time`,`delete_time`) VALUES ('1','服务后满意度调查','感谢您使用我们的服务，请花1分钟完成以下问卷','3','[{\"id\":1,\"type\":\"rating\",\"title\":\"整体服务满意度\",\"required\":true},{\"id\":2,\"type\":\"rating\",\"title\":\"服务人员态度\"},{\"id\":3,\"type\":\"rating\",\"title\":\"专业水平\"},{\"id\":4,\"type\":\"rating\",\"title\":\"时间守约\"},{\"id\":5,\"type\":\"text\",\"title\":\"您的建议\",\"placeholder\":\"请输入您的宝贵意见...\"}]','1','1','1773413107','1773413107',NULL);
CREATE TABLE `la_complaint` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '投诉ID',
  `complaint_sn` varchar(32) NOT NULL DEFAULT '' COMMENT '投诉编号',
  `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关联订单ID',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `staff_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '被投诉服务人员ID',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '投诉类型:1-服务态度,2-专业能力,3-迟到早退,4-违规行为,5-其他',
  `level` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '投诉等级:1-一般,2-严重,3-紧急',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '投诉标题',
  `content` text COMMENT '投诉内容',
  `images` text COMMENT '图片凭证(JSON数组)',
  `videos` text COMMENT '视频凭证(JSON数组)',
  `expect_result` varchar(500) NOT NULL DEFAULT '' COMMENT '期望处理结果',
  `contact_name` varchar(50) NOT NULL DEFAULT '' COMMENT '联系人',
  `contact_mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '联系手机号',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态:0-待处理,1-处理中,2-已处理,3-已申诉,4-已关闭',
  `handle_admin_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '处理人ID',
  `handle_result` text COMMENT '处理结果',
  `handle_action` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '处理动作:0-无,1-警告,2-扣款,3-禁用,4-其他',
  `handle_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '处理涉及金额（扣款/赔偿）',
  `handle_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '处理时间',
  `deadline` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '处理截止时间',
  `is_overtime` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否超时:0-否,1-是',
  `appeal_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '申诉状态:0-未申诉,1-申诉中,2-申诉成功,3-申诉失败',
  `appeal_reason` text COMMENT '申诉原因',
  `appeal_images` text COMMENT '申诉图片(JSON数组)',
  `appeal_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '申诉时间',
  `close_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '关闭原因',
  `close_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关闭时间',
  `satisfaction` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '满意度评分:0-未评价,1-5星',
  `source` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '来源:1-小程序,2-后台,3-电话',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) unsigned DEFAULT NULL COMMENT '删除时间',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='投诉表';
CREATE TABLE `la_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '类型',
  `name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '名称',
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '值',
  `create_time` int(10) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='配置表';
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('1','feature_switch','admin_dashboard','1','1773413108','1773413108');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('2','feature_switch','admin_dashboard_user_ids','1','1773556898','1773556898');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('3','feature_switch','comment_review_enabled','0','1773413108','1773413108');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('4','feature_switch','mini_program_review_mode','0','1776200000','1776200000');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('5','feature_switch','staff_admin','1','1773413108','1773413108');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('6','feature_switch','staff_center','1','1773413108','1773413108');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('7','feature_switch','staff_tag_review_enabled','0','1775001600','1775001600');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('8','feature_switch','wechat_text_check_comment_prob','70','1782547200','1782547200');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('9','feature_switch','wechat_text_check_enabled','1','1782547200','1782547200');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('10','feature_switch','wechat_text_check_profile_prob','80','1782547200','1782547200');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('11','feature_switch','wechat_text_check_review_as_hit','1','1782547200','1782547200');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('12','risk_control','rank_permissions','{\"0\":{\"enabled_abilities\":[\"profile\",\"content_publish\",\"comment\",\"interaction\",\"activity\",\"schedule\",\"order\",\"payment\",\"after_sale\",\"customer_service\",\"upload\",\"notification\",\"staff_center\"],\"comment_force_review\":false},\"1\":{\"enabled_abilities\":[\"profile\",\"content_publish\",\"comment\",\"interaction\",\"activity\",\"schedule\",\"order\",\"payment\",\"after_sale\",\"customer_service\",\"upload\",\"notification\",\"staff_center\"],\"comment_force_review\":false},\"2\":{\"enabled_abilities\":[\"profile\",\"content_publish\",\"comment\",\"interaction\",\"activity\",\"schedule\",\"order\",\"payment\",\"after_sale\",\"customer_service\",\"upload\",\"notification\",\"staff_center\"],\"comment_force_review\":true},\"3\":{\"enabled_abilities\":[],\"comment_force_review\":false},\"4\":{\"enabled_abilities\":[],\"comment_force_review\":false}}','1782547200','1782547200');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('13','order_payment','deposit_rounding_enabled','0','1777600000','1777600000');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('14','order_payment','deposit_rounding_unit','1','1777600000','1777600000');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('15','order_payment','offline_collection_enabled','1','1777600000','1777600000');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('16','customer_service','service_time','工作日 09:00 - 18:00','1776200000','1776200000');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('17','customer_service','tips','进入微信客服后可继续沟通','1776200000','1776200000');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('18','after_sale','auto_callback_plan_days','7','1776600000','1776600000');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('19','transaction','staff_confirm_timeout_enabled','0','1776200000','1776200000');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('20','transaction','staff_confirm_timeout_action','cancel','1776200000','1776200000');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('21','transaction','staff_confirm_timeout_minutes','60','1776200000','1776200000');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('22','staff_settlement_transfer','enabled','0','1777000000','1777000000');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('23','staff_settlement_transfer','auto_send','1','1777000000','1777000000');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('24','staff_settlement_transfer','transfer_scene_id','','1777000000','1777000000');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('25','staff_settlement_transfer','transfer_remark','服务人员结算','1777000000','1777000000');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('26','staff_settlement_transfer','user_recv_perception','服务结算','1777000000','1777000000');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('27','staff_settlement_transfer','quota_hint','单笔转账额度以微信商户平台配置为准，金额达到实名校验阈值时必须配置收款实名。','1777000000','1777000000');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('28','staff_settlement_transfer','manual_fallback','1','1777000000','1777000000');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('29','staff_settlement_transfer','amount_name_threshold','2000','1777000000','1777000000');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('30','staff_settlement_transfer','wechatpay_serial','','1777000000','1777000000');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('31','staff_settlement_transfer','wechatpay_public_key','','1777000000','1777000000');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('32','staff_settlement_transfer','transfer_scene_report_infos','[{\"info_type\":\"岗位\",\"info_content\":\"服务人员\"}]','1777000000','1777000000');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('34','oa_notification','enabled','0','1776200000','1776200000');
INSERT INTO `la_config` (`id`,`type`,`name`,`value`,`create_time`,`update_time`) VALUES ('35','oa_notification','channel_mode','oa_only','1776200000','1776200000');
CREATE TABLE `la_couple_question_bank` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '题库ID',
  `category` varchar(50) NOT NULL DEFAULT '' COMMENT '题目分类',
  `category_sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分类排序',
  `type` varchar(20) NOT NULL DEFAULT 'textarea' COMMENT '题型:text/textarea/single/multiple/rating',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '题目标题',
  `placeholder` varchar(200) NOT NULL DEFAULT '' COMMENT '输入提示',
  `options` text COMMENT '选项(JSON数组)',
  `required` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否必填',
  `sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '题目排序',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态:0-禁用,1-启用',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`category_sort`,`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='新人问卷基础题库表';
INSERT INTO `la_couple_question_bank` (`id`,`category`,`category_sort`,`type`,`title`,`placeholder`,`options`,`required`,`sort`,`status`,`create_time`,`update_time`,`delete_time`) VALUES ('1','新人基础信息','90','text','新人的年龄分别是？','例如：新郎28岁，新娘26岁','[]','1','100','1','1777600000','1777600000',NULL);
INSERT INTO `la_couple_question_bank` (`id`,`category`,`category_sort`,`type`,`title`,`placeholder`,`options`,`required`,`sort`,`status`,`create_time`,`update_time`,`delete_time`) VALUES ('2','新人基础信息','90','text','两位的家乡或成长经历有什么特别之处？','可写城市、成长背景、家乡习俗等','[]','0','90','1','1777600000','1777600000',NULL);
INSERT INTO `la_couple_question_bank` (`id`,`category`,`category_sort`,`type`,`title`,`placeholder`,`options`,`required`,`sort`,`status`,`create_time`,`update_time`,`delete_time`) VALUES ('3','职业与性格','80','text','两位目前的职业分别是什么？','例如：新郎工程师，新娘教师','[]','1','100','1','1777600000','1777600000',NULL);
INSERT INTO `la_couple_question_bank` (`id`,`category`,`category_sort`,`type`,`title`,`placeholder`,`options`,`required`,`sort`,`status`,`create_time`,`update_time`,`delete_time`) VALUES ('4','职业与性格','80','multiple','你们希望主持人在仪式中呈现哪些性格关键词？','','[\"温柔\",\"幽默\",\"稳重\",\"浪漫\",\"真诚\",\"活泼\"]','0','90','1','1777600000','1777600000',NULL);
INSERT INTO `la_couple_question_bank` (`id`,`category`,`category_sort`,`type`,`title`,`placeholder`,`options`,`required`,`sort`,`status`,`create_time`,`update_time`,`delete_time`) VALUES ('5','恋爱经过','70','textarea','请描述你们第一次认识的经过。','包括时间、地点、第一印象','[]','1','100','1','1777600000','1777600000',NULL);
INSERT INTO `la_couple_question_bank` (`id`,`category`,`category_sort`,`type`,`title`,`placeholder`,`options`,`required`,`sort`,`status`,`create_time`,`update_time`,`delete_time`) VALUES ('6','恋爱经过','70','textarea','你们确认关系或决定相伴的重要节点是什么？','可写一次谈话、一次旅行或一个瞬间','[]','0','90','1','1777600000','1777600000',NULL);
INSERT INTO `la_couple_question_bank` (`id`,`category`,`category_sort`,`type`,`title`,`placeholder`,`options`,`required`,`sort`,`status`,`create_time`,`update_time`,`delete_time`) VALUES ('7','求婚故事','60','textarea','求婚故事或决定结婚的契机是什么？','如果没有正式求婚，也可以写决定结婚的故事','[]','0','100','1','1777600000','1777600000',NULL);
INSERT INTO `la_couple_question_bank` (`id`,`category`,`category_sort`,`type`,`title`,`placeholder`,`options`,`required`,`sort`,`status`,`create_time`,`update_time`,`delete_time`) VALUES ('8','家庭成员','50','textarea','仪式中特别想感谢或提到的家人有哪些？','可写称呼、名字和感谢原因','[]','0','100','1','1777600000','1777600000',NULL);
INSERT INTO `la_couple_question_bank` (`id`,`category`,`category_sort`,`type`,`title`,`placeholder`,`options`,`required`,`sort`,`status`,`create_time`,`update_time`,`delete_time`) VALUES ('9','仪式风格','40','single','你们偏好的仪式风格是？','','[\"温馨感人\",\"轻松幽默\",\"庄重大气\",\"浪漫梦幻\",\"简洁克制\"]','1','100','1','1777600000','1777600000',NULL);
INSERT INTO `la_couple_question_bank` (`id`,`category`,`category_sort`,`type`,`title`,`placeholder`,`options`,`required`,`sort`,`status`,`create_time`,`update_time`,`delete_time`) VALUES ('10','仪式风格','40','rating','你们希望仪式情绪浓度是多少？','1星克制，5星浓烈','[]','0','90','1','1777600000','1777600000',NULL);
INSERT INTO `la_couple_question_bank` (`id`,`category`,`category_sort`,`type`,`title`,`placeholder`,`options`,`required`,`sort`,`status`,`create_time`,`update_time`,`delete_time`) VALUES ('11','特殊纪念','30','textarea','有没有想放进仪式的纪念日、礼物、歌曲或地点？','例如纪念日、定情信物、共同喜欢的歌','[]','0','100','1','1777600000','1777600000',NULL);
INSERT INTO `la_couple_question_bank` (`id`,`category`,`category_sort`,`type`,`title`,`placeholder`,`options`,`required`,`sort`,`status`,`create_time`,`update_time`,`delete_time`) VALUES ('12','避讳内容','20','textarea','有没有不希望在仪式中提及的话题、称呼或玩笑？','例如前任、年龄、家庭隐私、职业玩笑等','[]','0','100','1','1777600000','1777600000',NULL);
INSERT INTO `la_couple_question_bank` (`id`,`category`,`category_sort`,`type`,`title`,`placeholder`,`options`,`required`,`sort`,`status`,`create_time`,`update_time`,`delete_time`) VALUES ('13','补充说明','10','textarea','还有什么希望服务人员提前了解的内容？','任何对仪式有帮助的信息都可以写在这里','[]','0','100','1','1777600000','1777600000',NULL);
CREATE TABLE `la_couple_questionnaire` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '问卷配置ID',
  `staff_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '服务人员ID',
  `title` varchar(120) NOT NULL DEFAULT '' COMMENT '问卷标题',
  `description` varchar(500) NOT NULL DEFAULT '' COMMENT '问卷描述',
  `push_mode` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '推送方式:1自动推送,2手动触发',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态:0-禁用,1-启用',
  `draft_questions` text COMMENT '草稿题目(JSON数组)',
  `current_version_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '当前版本ID',
  `published_version_no` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '已发布版本号',
  `published_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '最近发布时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_staff_id` (`staff_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='服务人员新人问卷配置表';
CREATE TABLE `la_couple_questionnaire_answer` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '答案ID',
  `task_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '任务ID',
  `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单ID',
  `questionnaire_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '问卷配置ID',
  `version_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '版本ID',
  `version_no` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '版本号',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '客户ID',
  `staff_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '服务人员ID',
  `questions_snapshot` text COMMENT '题目快照(JSON数组)',
  `answers` text COMMENT '答案(JSON数组)',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_task_id` (`task_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='新人问卷答案表';
CREATE TABLE `la_couple_questionnaire_task` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '任务ID',
  `task_sn` varchar(32) NOT NULL DEFAULT '' COMMENT '任务编号',
  `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单ID',
  `order_item_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单主服务项ID',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '客户ID',
  `staff_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '主服务人员ID',
  `questionnaire_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '问卷配置ID',
  `version_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '当前版本ID',
  `version_no` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '当前版本号',
  `title_snapshot` varchar(120) NOT NULL DEFAULT '' COMMENT '标题快照',
  `description_snapshot` varchar(500) NOT NULL DEFAULT '' COMMENT '描述快照',
  `questions_snapshot` text COMMENT '已提交题目快照(JSON数组)',
  `push_mode` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '推送方式:1自动推送,2手动触发',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '填写状态:0待填写,1已填写,2已取消,3已查看,4已过期',
  `send_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '推送状态:0待推送,1已推送,2推送失败,3推送中',
  `send_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '推送尝试次数',
  `send_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '首次推送成功时间',
  `last_send_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '最近推送尝试时间',
  `send_error` varchar(500) NOT NULL DEFAULT '' COMMENT '最近推送失败原因',
  `next_retry_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '下次建议重试时间',
  `viewed_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '首次查看时间',
  `expire_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '过期时间',
  `submit_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '提交时间',
  `submitted_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '提交时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_task_sn` (`task_sn`),
  UNIQUE KEY `uk_order_id` (`order_id`),
  KEY `idx_user_status` (`user_id`,`status`),
  KEY `idx_staff_status` (`staff_id`,`status`),
  KEY `idx_send_status` (`send_status`),
  KEY `idx_retry_time` (`send_status`,`next_retry_time`),
  KEY `idx_expire_time` (`status`,`expire_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='订单新人问卷任务表';
CREATE TABLE `la_couple_questionnaire_version` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '版本ID',
  `questionnaire_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '问卷配置ID',
  `staff_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '服务人员ID',
  `version_no` int(11) unsigned NOT NULL DEFAULT '1' COMMENT '版本号',
  `title` varchar(120) NOT NULL DEFAULT '' COMMENT '问卷标题',
  `description` varchar(500) NOT NULL DEFAULT '' COMMENT '问卷描述',
  `questions` text COMMENT '题目快照(JSON数组)',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_questionnaire_version` (`questionnaire_id`,`version_no`),
  KEY `idx_staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='服务人员新人问卷版本表';
CREATE TABLE `la_customer` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联用户ID(0=潜在客户)',
  `customer_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT '客户姓名',
  `customer_mobile` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '手机号',
  `customer_wechat` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '微信号',
  `gender` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '性别：0=未知,1=男,2=女',
  `age` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '年龄',
  `city` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '所在城市',
  `district` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '所在区域',
  `intention_level` char(1) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'D' COMMENT '意向等级：A=高,B=中,C=低,D=待跟进',
  `intention_score` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '意向评分(0-100)',
  `wedding_date` date DEFAULT NULL COMMENT '计划婚期',
  `wedding_venue` varchar(200) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '婚礼场地',
  `wedding_budget` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '预算金额',
  `budget_range` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '预算范围',
  `service_needs` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '服务需求(JSON)',
  `source_channel` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '来源渠道：1=小程序,3=线下,4=转介绍,5=广告,6=其他',
  `source_detail` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '来源详情',
  `tags` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '客户标签(JSON)',
  `customer_status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '客户状态：1=新客户,2=跟进中,3=已签单,4=已流失,5=已完成',
  `loss_reason` varchar(200) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '流失原因',
  `loss_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '流失时间',
  `advisor_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分配销售顾问ID',
  `assign_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分配时间',
  `first_contact_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '首次联系时间',
  `last_follow_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后跟进时间',
  `next_follow_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '下次跟进时间',
  `follow_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '跟进次数',
  `order_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '成交订单数',
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '累计消费金额',
  `remark` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
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
CREATE TABLE `la_customer_assign_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `customer_id` int(10) unsigned NOT NULL COMMENT '客户ID',
  `from_advisor_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '原顾问ID(0=首次分配)',
  `to_advisor_id` int(10) unsigned NOT NULL COMMENT '新顾问ID',
  `assign_type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '分配类型：1=自动,2=手动,3=转交,4=回收重分',
  `assign_reason` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '分配原因',
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作管理员ID(0=系统)',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_from_advisor_id` (`from_advisor_id`),
  KEY `idx_to_advisor_id` (`to_advisor_id`),
  KEY `idx_assign_type` (`assign_type`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='客户分配日志表';
CREATE TABLE `la_customer_loss_warning` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `customer_id` int(10) unsigned NOT NULL COMMENT '客户ID',
  `advisor_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '销售顾问ID',
  `warning_type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '预警类型：1=长期未跟进,2=意向下降,3=竞品流失,4=预算不足,5=其他',
  `warning_level` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '预警等级：1=低,2=中,3=高',
  `warning_reason` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '预警原因',
  `days_no_follow` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '未跟进天数',
  `warning_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '处理状态：0=待处理,1=已处理,2=已忽略',
  `handle_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '处理时间',
  `handle_remark` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '处理备注',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_advisor_id` (`advisor_id`),
  KEY `idx_warning_type` (`warning_type`),
  KEY `idx_warning_status` (`warning_status`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='客户流失预警表';
CREATE TABLE `la_decorate_page` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `type` tinyint(2) unsigned NOT NULL DEFAULT '10' COMMENT '页面类型 1=商城首页, 2=个人中心, 3=客服设置 4-PC首页',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '页面名称',
  `data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '页面数据',
  `meta` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '页面设置',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='装修页面配置表';
INSERT INTO `la_decorate_page` (`id`,`type`,`name`,`data`,`meta`,`create_time`,`update_time`) VALUES ('1','1','小程序首页','[]','[]','1661757188','1710989700');
INSERT INTO `la_decorate_page` (`id`,`type`,`name`,`data`,`meta`,`create_time`,`update_time`) VALUES ('2','2','个人中心','[]','[]','1661757188','1710933097');
INSERT INTO `la_decorate_page` (`id`,`type`,`name`,`data`,`meta`,`create_time`,`update_time`) VALUES ('3','3','联系客服','[]','[]','1661757188','1710929953');
INSERT INTO `la_decorate_page` (`id`,`type`,`name`,`data`,`meta`,`create_time`,`update_time`) VALUES ('4','4','品牌展示','[]','[]','1661757188','1710990175');
INSERT INTO `la_decorate_page` (`id`,`type`,`name`,`data`,`meta`,`create_time`,`update_time`) VALUES ('5','5','系统风格','{\"themeColorId\":3,\"topTextColor\":\"white\",\"navigationBarColor\":\"#A74BFD\",\"themeColor1\":\"#A74BFD\",\"themeColor2\":\"#CB60FF\",\"buttonColor\":\"white\"}','','1710410915','1710990415');
INSERT INTO `la_decorate_page` (`id`,`type`,`name`,`data`,`meta`,`create_time`,`update_time`) VALUES ('6','6','开屏广告页','[{\"id\":\"splash_ad_default\",\"title\":\"开屏广告页\",\"name\":\"splash-ad\",\"disabled\":1,\"content\":{\"enabled\":0,\"image\":\"\",\"auto_enter_enabled\":1,\"auto_seconds\":3,\"frequency\":\"session\",\"button_text\":\"点击进入\"},\"styles\":{\"button_bg_color\":\"#FFFFFF\",\"button_text_color\":\"#333333\",\"button_border_color\":\"#FFFFFF\",\"button_border_radius\":24}}]','','1776200000','1776200000');
CREATE TABLE `la_decorate_tabbar` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '导航名称',
  `selected` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '未选图标',
  `unselected` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '已选图标',
  `link` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '链接地址',
  `is_show` tinyint(255) unsigned NOT NULL DEFAULT '1' COMMENT '显示状态',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='装修底部导航表';
INSERT INTO `la_decorate_tabbar` (`id`,`name`,`selected`,`unselected`,`link`,`is_show`,`create_time`,`update_time`) VALUES ('1','首页','resource/image/adminapi/default/tabbar_home_sel.png','resource/image/adminapi/default/tabbar_home.png','{\"path\":\"/pages/index/index\",\"name\":\"商城首页\",\"type\":\"shop\"}','1','1662688157','1662688157');
INSERT INTO `la_decorate_tabbar` (`id`,`name`,`selected`,`unselected`,`link`,`is_show`,`create_time`,`update_time`) VALUES ('2','资讯','resource/image/adminapi/default/tabbar_text_sel.png','resource/image/adminapi/default/tabbar_text.png','{\"path\":\"/pages/news/news\",\"name\":\"文章资讯\",\"type\":\"shop\",\"canTab\":\"1\"}','1','1662688157','1662688157');
INSERT INTO `la_decorate_tabbar` (`id`,`name`,`selected`,`unselected`,`link`,`is_show`,`create_time`,`update_time`) VALUES ('3','我的','resource/image/adminapi/default/tabbar_me_sel.png','resource/image/adminapi/default/tabbar_me.png','{\"path\":\"/pages/user/user\",\"name\":\"个人中心\",\"type\":\"shop\",\"canTab\":\"1\"}','1','1662688157','1662688157');
CREATE TABLE `la_dept` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '部门名称',
  `pid` bigint(20) NOT NULL DEFAULT '0' COMMENT '上级部门id',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `leader` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '负责人',
  `mobile` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '联系电话',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '部门状态（0停用 1正常）',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) DEFAULT NULL COMMENT '修改时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='部门表';
INSERT INTO `la_dept` (`id`,`name`,`pid`,`sort`,`leader`,`mobile`,`status`,`create_time`,`update_time`,`delete_time`) VALUES ('1','公司','0','0','','','1','1650592684','1653640368',NULL);
CREATE TABLE `la_dev_crontab` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '定时任务名称',
  `type` tinyint(1) NOT NULL COMMENT '类型 1-定时任务',
  `system` tinyint(4) DEFAULT '0' COMMENT '是否系统任务 0-否 1-是',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '备注',
  `command` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '命令内容',
  `params` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '参数',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态 1-运行 2-停止 3-错误',
  `expression` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '运行规则',
  `error` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '运行失败原因',
  `last_time` int(11) DEFAULT NULL COMMENT '最后执行时间',
  `time` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '0' COMMENT '实时执行时长',
  `max_time` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '0' COMMENT '最大执行时长',
  `create_time` int(10) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='计划任务表';
INSERT INTO `la_dev_crontab` (`id`,`name`,`type`,`system`,`remark`,`command`,`params`,`status`,`expression`,`error`,`last_time`,`time`,`max_time`,`create_time`,`update_time`,`delete_time`) VALUES ('1','超时未支付订单自动取消','1','1','每分钟扫描待支付首笔订单并自动取消超时单','cancel_unpaid_orders','','1','* * * * *',NULL,NULL,'0','0','1776200000','1776200000',NULL);
INSERT INTO `la_dev_crontab` (`id`,`name`,`type`,`system`,`remark`,`command`,`params`,`status`,`expression`,`error`,`last_time`,`time`,`max_time`,`create_time`,`update_time`,`delete_time`) VALUES ('2','活动报名待支付超时释放','1','1','每分钟扫描待支付活动报名并自动释放票种库存','expire_activity_registrations','','1','* * * * *',NULL,NULL,'0','0','1776200000','1776200000',NULL);
INSERT INTO `la_dev_crontab` (`id`,`name`,`type`,`system`,`remark`,`command`,`params`,`status`,`expression`,`error`,`last_time`,`time`,`max_time`,`create_time`,`update_time`,`delete_time`) VALUES ('3','站内提醒发送','1','1','每分钟扫描服务前一天提醒与暂停到期提醒的站内消息','send_station_reminders','','1','* * * * *',NULL,NULL,'0','0','1776200000','1776200000',NULL);
INSERT INTO `la_dev_crontab` (`id`,`name`,`type`,`system`,`remark`,`command`,`params`,`status`,`expression`,`error`,`last_time`,`time`,`max_time`,`create_time`,`update_time`,`delete_time`) VALUES ('4','候补超期自动失效','1','1','每天扫描预约日期已过的候补并自动标记为已过期','expire_waitlists','','1','10 0 * * *',NULL,NULL,'0','0','1776200000','1776200000',NULL);
INSERT INTO `la_dev_crontab` (`id`,`name`,`type`,`system`,`remark`,`command`,`params`,`status`,`expression`,`error`,`last_time`,`time`,`max_time`,`create_time`,`update_time`,`delete_time`) VALUES ('5','服务人员确认超时自动处理','1','1','每分钟扫描待确认订单并按配置自动取消或自动同意','handle_pending_confirm_orders','','1','* * * * *',NULL,NULL,'0','0','1776200000','1776200000',NULL);
INSERT INTO `la_dev_crontab` (`id`,`name`,`type`,`system`,`remark`,`command`,`params`,`status`,`expression`,`error`,`last_time`,`time`,`max_time`,`create_time`,`update_time`,`delete_time`) VALUES ('6','预约订单退款查询','1','1','每分钟查询处理中微信退款并同步订单退款状态','query_refund','','1','* * * * *',NULL,NULL,'0','0','1776200000','1776200000',NULL);
INSERT INTO `la_dev_crontab` (`id`,`name`,`type`,`system`,`remark`,`command`,`params`,`status`,`expression`,`error`,`last_time`,`time`,`max_time`,`create_time`,`update_time`,`delete_time`) VALUES ('7','CRM流失预警生成','1','1','每天扫描长期未跟进客户生成流失预警并发送服务号及站内消息','generate_loss_warnings','','1','0 9 * * *',NULL,NULL,'0','0','1776300000','1776300000',NULL);
INSERT INTO `la_dev_crontab` (`id`,`name`,`type`,`system`,`remark`,`command`,`params`,`status`,`expression`,`error`,`last_time`,`time`,`max_time`,`create_time`,`update_time`,`delete_time`) VALUES ('8','服务人员自动结算','1','1','每分钟生成已完成已付订单的服务人员结算，并处理微信商家转账状态','auto_staff_settlement','','1','* * * * *',NULL,NULL,'0','0','1777000000','1777000000',NULL);
INSERT INTO `la_dev_crontab` (`id`,`name`,`type`,`system`,`remark`,`command`,`params`,`status`,`expression`,`error`,`last_time`,`time`,`max_time`,`create_time`,`update_time`,`delete_time`) VALUES ('9','公众号通知派发','1','1','每分钟扫描并派发到期的公众号服务通知','send_oa_notifications','','1','* * * * *',NULL,NULL,'0','0','1776200000','1776200000',NULL);
INSERT INTO `la_dev_crontab` (`id`,`name`,`type`,`system`,`remark`,`command`,`params`,`status`,`expression`,`error`,`last_time`,`time`,`max_time`,`create_time`,`update_time`,`delete_time`) VALUES ('10','微信支付查单与关单','1','1','每分钟恢复订单、活动与抽成补交付款结果，并关闭超时流水','query_payments','','1','* * * * *',NULL,NULL,'0','0','0','0',NULL);
CREATE TABLE `la_dev_pay_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '模版名称',
  `pay_way` tinyint(4) NOT NULL COMMENT '支付方式：2微信',
  `config` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '对应支付配置(json字符串)',
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '图标',
  `sort` int(5) DEFAULT NULL COMMENT '排序',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '备注',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='支付配置表';
INSERT INTO `la_dev_pay_config` (`id`,`name`,`pay_way`,`config`,`icon`,`sort`,`remark`) VALUES ('2','微信支付','2','{\"interface_version\":\"v3\",\"merchant_type\":\"ordinary_merchant\",\"mch_id\":\"\",\"pay_sign_key\":\"\",\"apiclient_cert\":\"\",\"apiclient_key\":\"\"}','/resource/image/adminapi/default/wechat_pay.png','123','微信小程序支付');
CREATE TABLE `la_dev_pay_way` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `pay_config_id` int(11) NOT NULL COMMENT '支付配置ID',
  `scene` tinyint(4) NOT NULL COMMENT '支付场景：1微信小程序',
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否默认支付:0-否;1-是;',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态:0-关闭;1-开启;',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='支付方式表';
INSERT INTO `la_dev_pay_way` (`id`,`pay_config_id`,`scene`,`is_default`,`status`) VALUES ('2','2','1','1','1');
CREATE TABLE `la_dict_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '数据名称',
  `value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '数据值',
  `type_id` int(11) NOT NULL COMMENT '字典类型id',
  `type_value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '字典类型',
  `sort` int(10) DEFAULT '0' COMMENT '排序值',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态 0-停用 1-正常',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '备注',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) DEFAULT NULL COMMENT '修改时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='字典数据表';
INSERT INTO `la_dict_data` (`id`,`name`,`value`,`type_id`,`type_value`,`sort`,`status`,`remark`,`create_time`,`update_time`,`delete_time`) VALUES ('1','隐藏','0','1','show_status','0','1','','1656381543','1656381543',NULL);
INSERT INTO `la_dict_data` (`id`,`name`,`value`,`type_id`,`type_value`,`sort`,`status`,`remark`,`create_time`,`update_time`,`delete_time`) VALUES ('2','显示','1','1','show_status','0','1','','1656381550','1656381550',NULL);
INSERT INTO `la_dict_data` (`id`,`name`,`value`,`type_id`,`type_value`,`sort`,`status`,`remark`,`create_time`,`update_time`,`delete_time`) VALUES ('3','进行中','0','2','business_status','0','1','','1656381410','1656381410',NULL);
INSERT INTO `la_dict_data` (`id`,`name`,`value`,`type_id`,`type_value`,`sort`,`status`,`remark`,`create_time`,`update_time`,`delete_time`) VALUES ('4','成功','1','2','business_status','0','1','','1656381437','1656381437',NULL);
INSERT INTO `la_dict_data` (`id`,`name`,`value`,`type_id`,`type_value`,`sort`,`status`,`remark`,`create_time`,`update_time`,`delete_time`) VALUES ('5','失败','2','2','business_status','0','1','','1656381449','1656381449',NULL);
INSERT INTO `la_dict_data` (`id`,`name`,`value`,`type_id`,`type_value`,`sort`,`status`,`remark`,`create_time`,`update_time`,`delete_time`) VALUES ('6','待处理','0','3','event_status','0','1','','1656381212','1656381212',NULL);
INSERT INTO `la_dict_data` (`id`,`name`,`value`,`type_id`,`type_value`,`sort`,`status`,`remark`,`create_time`,`update_time`,`delete_time`) VALUES ('7','已处理','1','3','event_status','0','1','','1656381315','1656381315',NULL);
INSERT INTO `la_dict_data` (`id`,`name`,`value`,`type_id`,`type_value`,`sort`,`status`,`remark`,`create_time`,`update_time`,`delete_time`) VALUES ('8','拒绝处理','2','3','event_status','0','1','','1656381331','1656381331',NULL);
INSERT INTO `la_dict_data` (`id`,`name`,`value`,`type_id`,`type_value`,`sort`,`status`,`remark`,`create_time`,`update_time`,`delete_time`) VALUES ('9','禁用','1','4','system_disable','0','1','','1656312030','1656312030',NULL);
INSERT INTO `la_dict_data` (`id`,`name`,`value`,`type_id`,`type_value`,`sort`,`status`,`remark`,`create_time`,`update_time`,`delete_time`) VALUES ('10','正常','0','4','system_disable','0','1','','1656312040','1656312040',NULL);
INSERT INTO `la_dict_data` (`id`,`name`,`value`,`type_id`,`type_value`,`sort`,`status`,`remark`,`create_time`,`update_time`,`delete_time`) VALUES ('11','未知','0','5','sex','0','1','','1656062988','1656062988',NULL);
INSERT INTO `la_dict_data` (`id`,`name`,`value`,`type_id`,`type_value`,`sort`,`status`,`remark`,`create_time`,`update_time`,`delete_time`) VALUES ('12','男','1','5','sex','0','1','','1656062999','1656062999',NULL);
INSERT INTO `la_dict_data` (`id`,`name`,`value`,`type_id`,`type_value`,`sort`,`status`,`remark`,`create_time`,`update_time`,`delete_time`) VALUES ('13','女','2','5','sex','0','1','','1656063009','1656063009',NULL);
INSERT INTO `la_dict_data` (`id`,`name`,`value`,`type_id`,`type_value`,`sort`,`status`,`remark`,`create_time`,`update_time`,`delete_time`) VALUES ('14','疫情','1','6','order_pause_type','1','1','订单暂停类型-疫情','1773413105','1773413105',NULL);
INSERT INTO `la_dict_data` (`id`,`name`,`value`,`type_id`,`type_value`,`sort`,`status`,`remark`,`create_time`,`update_time`,`delete_time`) VALUES ('15','突发事件','2','6','order_pause_type','2','1','订单暂停类型-突发事件','1773413105','1773413105',NULL);
INSERT INTO `la_dict_data` (`id`,`name`,`value`,`type_id`,`type_value`,`sort`,`status`,`remark`,`create_time`,`update_time`,`delete_time`) VALUES ('16','个人原因','3','6','order_pause_type','3','1','订单暂停类型-个人原因','1773413105','1773413105',NULL);
INSERT INTO `la_dict_data` (`id`,`name`,`value`,`type_id`,`type_value`,`sort`,`status`,`remark`,`create_time`,`update_time`,`delete_time`) VALUES ('17','其他','4','6','order_pause_type','4','1','订单暂停类型-其他','1773413105','1773413105',NULL);
CREATE TABLE `la_dict_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '字典名称',
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '字典类型名称',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态 0-停用 1-正常',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '备注',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) DEFAULT NULL COMMENT '修改时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='字典类型表';
INSERT INTO `la_dict_type` (`id`,`name`,`type`,`status`,`remark`,`create_time`,`update_time`,`delete_time`) VALUES ('1','显示状态','show_status','1','','1656381520','1656381520',NULL);
INSERT INTO `la_dict_type` (`id`,`name`,`type`,`status`,`remark`,`create_time`,`update_time`,`delete_time`) VALUES ('2','业务状态','business_status','1','','1656381393','1656381393',NULL);
INSERT INTO `la_dict_type` (`id`,`name`,`type`,`status`,`remark`,`create_time`,`update_time`,`delete_time`) VALUES ('3','事件状态','event_status','1','','1656381075','1656381075',NULL);
INSERT INTO `la_dict_type` (`id`,`name`,`type`,`status`,`remark`,`create_time`,`update_time`,`delete_time`) VALUES ('4','禁用状态','system_disable','1','','1656311838','1656312040',NULL);
INSERT INTO `la_dict_type` (`id`,`name`,`type`,`status`,`remark`,`create_time`,`update_time`,`delete_time`) VALUES ('5','用户性别','sex','1','','1656062946','1656380925',NULL);
INSERT INTO `la_dict_type` (`id`,`name`,`type`,`status`,`remark`,`create_time`,`update_time`,`delete_time`) VALUES ('6','订单暂停类型','order_pause_type','1','','1773413105','1773413105',NULL);
CREATE TABLE `la_dynamic` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` int(10) unsigned NOT NULL COMMENT '发布者ID',
  `user_type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '发布者类型：1=用户,2=工作人员,3=官方',
  `staff_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '工作人员ID(user_type=2时)',
  `dynamic_type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '动态类型：1=图文,2=视频,3=案例分享,4=活动',
  `title` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '标题',
  `content` text COLLATE utf8mb4_general_ci COMMENT '内容',
  `images` text COLLATE utf8mb4_general_ci COMMENT '图片列表(JSON)',
  `video_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '视频地址',
  `video_cover` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '视频封面',
  `location` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '位置信息',
  `latitude` decimal(10,7) NOT NULL DEFAULT '0.0000000' COMMENT '纬度',
  `longitude` decimal(10,7) NOT NULL DEFAULT '0.0000000' COMMENT '经度',
  `tags` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '标签(逗号分隔)',
  `allow_comment` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否允许评论：0=禁止，1=允许',
  `activity_start_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '活动开始时间',
  `activity_signup_deadline` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '报名截止时间',
  `activity_signup_enabled` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否开启报名：0=否,1=是',
  `activity_total_quota` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '活动总名额：0=不限',
  `activity_registered_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '活动已占用名额',
  `order_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联订单ID(晒单)',
  `view_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '浏览量',
  `like_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '点赞数',
  `comment_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '评论数',
  `share_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分享数',
  `collect_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '收藏数',
  `is_top` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否置顶：0=否,1=是',
  `is_hot` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否热门：0=否,1=是',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态：0=待审核,1=已发布,2=已下架,3=审核拒绝',
  `audit_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '审核管理员ID',
  `audit_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '审核时间',
  `audit_remark` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '审核备注',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_staff_id` (`staff_id`),
  KEY `idx_dynamic_type` (`dynamic_type`),
  KEY `idx_status` (`status`),
  KEY `idx_create_time` (`create_time`),
  KEY `idx_is_top_hot` (`is_top`,`is_hot`),
  KEY `idx_activity_time` (`dynamic_type`,`activity_start_time`),
  KEY `idx_activity_signup` (`dynamic_type`,`activity_signup_enabled`,`activity_signup_deadline`),
  KEY `idx_activity_list` (`status`,`dynamic_type`,`is_top`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='动态表';
CREATE TABLE `la_dynamic_collect` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` int(10) unsigned NOT NULL COMMENT '用户ID',
  `dynamic_id` int(10) unsigned NOT NULL COMMENT '动态ID',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_dynamic` (`user_id`,`dynamic_id`),
  KEY `idx_dynamic_id` (`dynamic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='动态收藏表';
CREATE TABLE `la_dynamic_comment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `dynamic_id` int(10) unsigned NOT NULL COMMENT '动态ID',
  `user_id` int(10) unsigned NOT NULL COMMENT '评论者ID',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父评论ID(0=一级评论)',
  `reply_user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '回复的用户ID',
  `content` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '评论内容',
  `images` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '评论图片(JSON)',
  `like_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '点赞数',
  `reply_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '回复数',
  `is_top` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否置顶：0=否,1=是',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态：0=待审核,1=正常,2=已删除,3=审核拒绝',
  `review_status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '审核状态：0=待审核，1=已通过，2=已拒绝',
  `review_admin_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '审核管理员ID',
  `review_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '审核时间',
  `review_remark` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '审核备注（拒绝原因）',
  `ip` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'IP地址',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_dynamic_id` (`dynamic_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='动态评论表';
CREATE TABLE `la_dynamic_like` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` int(10) unsigned NOT NULL COMMENT '用户ID',
  `target_type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '目标类型：1=动态,2=评论',
  `target_id` int(10) unsigned NOT NULL COMMENT '目标ID',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_target` (`user_id`,`target_type`,`target_id`),
  KEY `idx_target` (`target_type`,`target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='动态点赞表';
CREATE TABLE `la_escalate_rule` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '规则名称',
  `ticket_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '工单类型:0-全部',
  `priority` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '优先级:0-全部',
  `timeout_hours` int(11) unsigned NOT NULL DEFAULT '24' COMMENT '超时时间（小时）',
  `escalate_to` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '升级到人员ID',
  `notify_method` varchar(50) NOT NULL DEFAULT 'system' COMMENT '通知方式:system/sms/wechat',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态:0-禁用,1-启用',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='问题升级规则配置表';
INSERT INTO `la_escalate_rule` (`id`,`name`,`ticket_type`,`priority`,`timeout_hours`,`escalate_to`,`notify_method`,`status`,`create_time`,`update_time`) VALUES ('1','紧急工单2小时升级','0','4','2','0','system,sms','1','1773413107','1773413107');
INSERT INTO `la_escalate_rule` (`id`,`name`,`ticket_type`,`priority`,`timeout_hours`,`escalate_to`,`notify_method`,`status`,`create_time`,`update_time`) VALUES ('2','高优先级工单4小时升级','0','3','4','0','system','1','1773413107','1773413107');
INSERT INTO `la_escalate_rule` (`id`,`name`,`ticket_type`,`priority`,`timeout_hours`,`escalate_to`,`notify_method`,`status`,`create_time`,`update_time`) VALUES ('3','普通工单24小时升级','0','2','24','0','system','1','1773413107','1773413107');
INSERT INTO `la_escalate_rule` (`id`,`name`,`ticket_type`,`priority`,`timeout_hours`,`escalate_to`,`notify_method`,`status`,`create_time`,`update_time`) VALUES ('4','投诉48小时升级','1','0','48','0','system,sms','1','1773413107','1773413107');
CREATE TABLE `la_favorite` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `staff_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '工作人员ID',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_user_staff` (`user_id`,`staff_id`) USING BTREE,
  KEY `idx_staff_id` (`staff_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='收藏表';
CREATE TABLE `la_file` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `cid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '类目ID',
  `source_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上传者id',
  `source` tinyint(1) NOT NULL DEFAULT '0' COMMENT '来源类型[0-后台,1-用户]',
  `type` tinyint(2) unsigned NOT NULL DEFAULT '10' COMMENT '类型[10=图片, 20=视频]',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '文件名称',
  `uri` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '文件路径',
  `create_time` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='文件表';
CREATE TABLE `la_file_cate` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父级ID',
  `type` tinyint(2) unsigned NOT NULL DEFAULT '10' COMMENT '类型[10=图片，20=视频，30=文件]',
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `create_time` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='文件分类表';
CREATE TABLE `la_file_cleanup_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `admin_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作管理员ID',
  `delete_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '成功清理数量',
  `registered_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '素材记录清理数量',
  `orphan_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '孤儿文件清理数量',
  `free_size` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '释放字节数',
  `failed_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '失败数量',
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '成功明细JSON',
  `failed_detail` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '失败明细JSON',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_admin_time` (`admin_id`,`create_time`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='素材清理日志表';
CREATE TABLE `la_financial_flow` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `flow_sn` varchar(32) COLLATE utf8mb4_general_ci NOT NULL COMMENT '流水编号',
  `flow_type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '流水类型：1=收入,2=支出,3=退款,4=分账,5=提现',
  `biz_type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '业务类型：1=订单支付,2=订单退款,3=人员结算,4=平台抽成,5=其他,6=抽成补交补偿退款',
  `biz_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '业务ID',
  `unique_biz_id` int unsigned GENERATED ALWAYS AS (NULLIF(`biz_id`, 0)) STORED COMMENT '手工流水不参与业务去重',
  `biz_sn` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '业务编号',
  `order_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联订单ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联用户ID',
  `staff_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联服务人员ID',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '金额(正值)',
  `direction` tinyint(4) NOT NULL DEFAULT '1' COMMENT '方向：1=收入(+),-1=支出(-)',
  `pay_way` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '支付方式：0=系统，1=微信，4=线下',
  `transaction_id` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '第三方交易号',
  `remark` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `operator_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '操作者类型：0=系统,1=用户,2=管理员',
  `operator_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作者ID',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_flow_sn` (`flow_sn`),
  UNIQUE KEY `uk_flow_business` (`biz_type`, `unique_biz_id`, `flow_type`),
  KEY `idx_flow_type` (`flow_type`),
  KEY `idx_biz_type` (`biz_type`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_staff_id` (`staff_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='资金流水表';
CREATE TABLE `la_financial_reconciliation` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `reconcile_sn` varchar(32) COLLATE utf8mb4_general_ci NOT NULL COMMENT '对账编号',
  `reconcile_date` date NOT NULL COMMENT '对账日期',
  `pay_channel` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '支付渠道：1=微信',
  `system_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '系统交易笔数',
  `system_amount` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '系统交易金额',
  `channel_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '渠道交易笔数',
  `channel_amount` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '渠道交易金额',
  `diff_count` int(11) NOT NULL DEFAULT '0' COMMENT '差异笔数',
  `diff_amount` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '差异金额',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '状态：0=待对账,1=对账中,2=已平账,3=有差异,4=已处理',
  `bill_file` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '账单文件地址',
  `result_file` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '对账结果文件',
  `handle_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '处理管理员ID',
  `handle_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '处理时间',
  `handle_remark` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '处理备注',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_reconcile_sn` (`reconcile_sn`),
  UNIQUE KEY `uk_date_channel` (`reconcile_date`,`pay_channel`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='财务对账记录表';
CREATE TABLE `la_follow` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` int(10) unsigned NOT NULL COMMENT '关注者ID',
  `follow_type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '关注类型：1=用户,2=工作人员',
  `follow_id` int(10) unsigned NOT NULL COMMENT '被关注者ID',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_follow` (`user_id`,`follow_type`,`follow_id`),
  KEY `idx_follow` (`follow_type`,`follow_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='关注表';
CREATE TABLE `la_follow_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `customer_id` int(10) unsigned NOT NULL COMMENT '客户ID',
  `advisor_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '销售顾问ID',
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作管理员ID',
  `follow_type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '跟进方式：1=电话,2=微信,3=到店,4=试妆,5=看样片,6=上门,7=其他',
  `follow_content` text COLLATE utf8mb4_general_ci NOT NULL COMMENT '跟进内容',
  `follow_result` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '跟进结果：1=继续跟进,2=意向提升,3=意向下降,4=已成交,5=已流失',
  `intention_before` char(1) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '跟进前意向等级',
  `intention_after` char(1) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '跟进后意向等级',
  `duration` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '沟通时长(分钟)',
  `next_follow_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '下次跟进时间',
  `next_follow_content` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '下次跟进计划',
  `attachments` text COLLATE utf8mb4_general_ci COMMENT '附件(JSON)',
  `is_important` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否重要',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_advisor_id` (`advisor_id`),
  KEY `idx_follow_type` (`follow_type`),
  KEY `idx_follow_result` (`follow_result`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='跟进记录表';
CREATE TABLE `la_generate_column` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `table_id` int(11) NOT NULL DEFAULT '0' COMMENT '表id',
  `column_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '字段名称',
  `column_comment` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '字段描述',
  `column_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '字段类型',
  `is_required` tinyint(1) DEFAULT '0' COMMENT '是否必填 0-非必填 1-必填',
  `is_pk` tinyint(1) DEFAULT '0' COMMENT '是否为主键 0-不是 1-是',
  `is_insert` tinyint(1) DEFAULT '0' COMMENT '是否为插入字段 0-不是 1-是',
  `is_update` tinyint(1) DEFAULT '0' COMMENT '是否为更新字段 0-不是 1-是',
  `is_lists` tinyint(1) DEFAULT '0' COMMENT '是否为列表字段 0-不是 1-是',
  `is_query` tinyint(1) DEFAULT '0' COMMENT '是否为查询字段 0-不是 1-是',
  `query_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '=' COMMENT '查询类型',
  `view_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'input' COMMENT '显示类型',
  `dict_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '字典类型',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) DEFAULT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='代码生成表字段信息表';
CREATE TABLE `la_generate_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `table_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '表名称',
  `table_comment` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '表描述',
  `template_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '模板类型 0-单表(curd) 1-树表(curd)',
  `author` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '作者',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '备注',
  `generate_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '生成方式  0-压缩包下载 1-生成到模块',
  `module_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '模块名',
  `class_dir` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '类目录名',
  `class_comment` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '类描述',
  `admin_id` int(11) DEFAULT '0' COMMENT '管理员id',
  `menu` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '菜单配置',
  `delete` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '删除配置',
  `tree` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '树表配置',
  `relations` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '关联配置',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) DEFAULT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='代码生成表信息表';
CREATE TABLE `la_hot_search` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '关键词',
  `sort` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '排序号',
  `create_time` int(10) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='热门搜索表';
CREATE TABLE `la_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '岗位名称',
  `code` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '岗位编码',
  `sort` int(11) DEFAULT '0' COMMENT '显示顺序',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态（0停用 1正常）',
  `remark` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '备注',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) DEFAULT NULL COMMENT '修改时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='岗位表';
CREATE TABLE `la_monthly_report` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `report_month` char(7) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '月报月份：YYYY-MM',
  `version` int(11) unsigned NOT NULL DEFAULT '1' COMMENT '月份内版本号',
  `is_current` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否当前版本：0=否,1=是',
  `category_ids` json DEFAULT NULL COMMENT '统计服务分类范围',
  `addition_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '最终新增档期数',
  `executed_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '最终共执行场次',
  `top_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '最终单王场次',
  `auto_snapshot` json DEFAULT NULL COMMENT '自动统计快照',
  `final_snapshot` json DEFAULT NULL COMMENT '运营确认快照',
  `template_snapshot` json DEFAULT NULL COMMENT '模板版本快照',
  `rendered_snapshot` json DEFAULT NULL COMMENT '渲染快照',
  `snapshot_hash` char(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '快照哈希',
  `issues` json DEFAULT NULL COMMENT '确认时问题清单',
  `addition_image_url` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '新增档期图片',
  `ranking_image_url` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '执行榜图片',
  `top_image_url` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '单王图片',
  `confirm_admin_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '确认管理员ID',
  `confirm_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '确认时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_month_version` (`report_month`,`version`),
  KEY `idx_month_current` (`report_month`,`is_current`),
  KEY `idx_current_month` (`is_current`,`report_month`),
  KEY `idx_snapshot` (`snapshot_hash`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='单量月报确认记录表';
CREATE TABLE `la_monthly_report_material` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `staff_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '绑定服务人员ID',
  `photo` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '兼容旧月报照片',
  `avatar_photo` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '头像素材：执行榜使用',
  `half_body_photo` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '半身素材：单王使用',
  `english_name` varchar(80) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '英文名或拼音',
  `chinese_name` varchar(80) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '中文名',
  `sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序值',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态：0=停用,1=启用',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_staff` (`staff_id`),
  KEY `idx_status_sort` (`status`,`sort`,`id`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='单量月报素材表';
CREATE TABLE `la_monthly_report_template` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `template_type` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'addition' COMMENT '模板类型：addition=新增档期,ranking=执行榜,top=单王',
  `template_name` varchar(80) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '模板名称',
  `template_version` int(11) unsigned NOT NULL DEFAULT '1' COMMENT '模板版本号',
  `is_default` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否默认模板：0=否,1=是',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '模板状态：0=停用,1=启用',
  `sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序值',
  `design_version` varchar(60) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'monthly-report-designer-v1' COMMENT '设计器版本',
  `design_config` json DEFAULT NULL COMMENT '自由设计配置',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_type_status_default` (`template_type`,`status`,`is_default`),
  KEY `idx_type_version` (`template_type`,`template_version`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='单量月报模板表';
CREATE TABLE `la_notice_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(10) unsigned NOT NULL COMMENT '用户id',
  `title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '标题',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '内容',
  `scene_id` int(10) unsigned DEFAULT '0' COMMENT '场景',
  `read` tinyint(1) DEFAULT '0' COMMENT '已读状态;0-未读,1-已读',
  `recipient` tinyint(1) DEFAULT '0' COMMENT '通知接收对象类型;1-会员;2-商家;3-平台;4-游客(未注册用户)',
  `send_type` tinyint(1) DEFAULT '0' COMMENT '通知发送类型 1-系统通知 2-短信通知 3-微信模板 4-微信小程序',
  `notice_type` tinyint(1) DEFAULT NULL COMMENT '通知类型 1-业务通知 2-验证码',
  `extra` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '其他',
  `create_time` int(10) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='通知记录表';
CREATE TABLE `la_notice_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `scene_id` int(10) NOT NULL COMMENT '场景id',
  `scene_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '场景名称',
  `scene_desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '场景描述',
  `recipient` tinyint(1) NOT NULL DEFAULT '1' COMMENT '接收者 1-用户 2-平台',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '通知类型: 1-业务通知 2-验证码',
  `sms_notice` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '短信通知设置',
  `update_time` int(10) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='通知设置表';
INSERT INTO `la_notice_setting` (`id`,`scene_id`,`scene_name`,`scene_desc`,`recipient`,`type`,`sms_notice`,`update_time`) VALUES (1,101,'登录验证码','用户手机号码登录时发送',1,2,'{"type":"sms","template_id":"","content":"您正在登录，验证码${code}，本条验证码有效期5分钟。","status":0}',0);
INSERT INTO `la_notice_setting` (`id`,`scene_id`,`scene_name`,`scene_desc`,`recipient`,`type`,`sms_notice`,`update_time`) VALUES (2,102,'绑定手机验证码','用户绑定手机号码时发送',1,2,'{"type":"sms","template_id":"","content":"您正在绑定手机号，验证码${code}，本条验证码有效期5分钟。","status":0}',0);
INSERT INTO `la_notice_setting` (`id`,`scene_id`,`scene_name`,`scene_desc`,`recipient`,`type`,`sms_notice`,`update_time`) VALUES (3,103,'变更手机验证码','用户变更手机号码时发送',1,2,'{"type":"sms","template_id":"","content":"您正在变更手机号，验证码${code}，本条验证码有效期5分钟。","status":0}',0);
INSERT INTO `la_notice_setting` (`id`,`scene_id`,`scene_name`,`scene_desc`,`recipient`,`type`,`sms_notice`,`update_time`) VALUES (4,104,'找回登录密码验证码','用户找回登录密码时发送',1,2,'{"type":"sms","template_id":"","content":"您正在找回登录密码，验证码${code}，本条验证码有效期5分钟。","status":0}',0);
CREATE TABLE `la_account_binding_audit` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int unsigned NOT NULL,
  `staff_id` int unsigned NOT NULL DEFAULT 0,
  `old_user_id` int unsigned NOT NULL DEFAULT 0,
  `new_user_id` int unsigned NOT NULL DEFAULT 0,
  `operator_id` int unsigned NOT NULL DEFAULT 0,
  `reason` varchar(500) NOT NULL DEFAULT '',
  `create_time` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_admin` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `la_notification_event` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_key` varchar(64) NOT NULL,
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `audience` varchar(16) NOT NULL DEFAULT 'user',
  `payload` mediumtext NOT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT 0,
  `retry_count` int unsigned NOT NULL DEFAULT 0,
  `next_retry_time` int unsigned NOT NULL DEFAULT 0,
  `error` varchar(500) NOT NULL DEFAULT '',
  `create_time` int unsigned NOT NULL,
  `update_time` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_event_key` (`event_key`),
  KEY `idx_dispatch` (`status`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `la_notification` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` int(10) unsigned NOT NULL COMMENT '接收者ID',
  `sender_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发送者ID(0=系统)',
  `notify_type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '通知类型：1=系统通知,2=订单通知,3=互动通知,4=活动通知',
  `title` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '标题',
  `content` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '内容',
  `target_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '目标类型(order/dynamic/comment等)',
  `target_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '目标ID',
  `event_key` varchar(64) DEFAULT NULL,
  `audience` varchar(16) NOT NULL DEFAULT 'user',
  `identity_revoked` tinyint unsigned NOT NULL DEFAULT 0,
  `access_options` text DEFAULT NULL,
  `business_type` varchar(50) NOT NULL DEFAULT '',
  `business_id` int unsigned NOT NULL DEFAULT 0,
  `is_read` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否已读：0=否,1=是',
  `read_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '阅读时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_notify_type` (`notify_type`),
  UNIQUE KEY `uk_notification_event` (`event_key`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='消息通知表';
CREATE TABLE `la_official_account_reply` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '规则名称',
  `keyword` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '关键词',
  `reply_type` tinyint(1) NOT NULL COMMENT '回复类型 1-关注回复 2-关键字回复 3-默认回复',
  `matching_type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '匹配方式：1-全匹配；2-模糊匹配',
  `content_type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '内容类型：1-文本',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '回复内容',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '启动状态：1-启动；0-关闭',
  `sort` int(11) unsigned NOT NULL DEFAULT '50' COMMENT '排序',
  `create_time` int(10) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='公众号消息回调表';
CREATE TABLE `la_operation_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `admin_id` int(11) NOT NULL COMMENT '管理员ID',
  `admin_name` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '管理员名称',
  `account` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '管理员账号',
  `action` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '操作名称',
  `type` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '请求方式',
  `url` varchar(600) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '访问链接',
  `params` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '请求数据',
  `result` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '请求结果',
  `ip` varchar(39) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'ip地址',
  `create_time` int(10) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='系统日志表';
CREATE TABLE `la_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `order_sn` varchar(32) COLLATE utf8mb4_general_ci NOT NULL COMMENT '订单编号',
  `user_id` int(10) unsigned NOT NULL COMMENT '用户ID',
  `order_type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '订单类型：1=普通订单,2=套餐订单,3=组合订单',
  `order_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '订单状态：0=待确认,1=待支付,2=待服务,3=服务中,4=已完成,5=已评价,6=已取消,7=已暂停,8=已退款,9=用户已删除',
  `pay_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '支付状态：0=未支付,1=已支付,2=部分退款,3=全额退款',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单总额',
  `addon_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '附加服务金额',
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '优惠金额',
  `pay_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实付金额',
  `paid_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '已支付金额',
  `deposit_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '定金金额',
  `deposit_paid` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '定金是否支付：0=否,1=是',
  `balance_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '尾款金额',
  `balance_paid` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '尾款是否支付：0=否,1=是',
  `deposit_mode_enabled` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否开启定金模式快照',
  `deposit_type_snapshot` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '定金类型快照',
  `deposit_value_snapshot` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '定金值快照',
  `deposit_remark_snapshot` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '定金说明快照',
  `pay_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '支付方式：0=未支付，1=微信，4=线下',
  `payment_channel` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '付款渠道：1=线上支付,2=线下支付',
  `current_confirm_letter_id` int(11) unsigned DEFAULT NULL COMMENT '当前有效确认函ID',
  `pay_voucher` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '线下支付凭证',
  `pay_voucher_status` tinyint(3) unsigned DEFAULT NULL COMMENT '凭证审核状态：0=待审核,1=已通过,2=已拒绝',
  `pay_voucher_audit_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '凭证审核管理员ID',
  `pay_voucher_audit_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '凭证审核时间',
  `pay_voucher_audit_remark` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '凭证审核备注',
  `pay_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '支付时间',
  `pay_deadline_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '支付截止时间',
  `confirm_deadline_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '确认截止时间',
  `service_date` date DEFAULT NULL COMMENT '服务日期',
  `service_time_slot` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '服务时间段',
  `service_address` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '服务地址',
  `service_province_code` varchar(12) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '服务省编码',
  `service_province` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '服务省',
  `service_city_code` varchar(12) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '服务市编码',
  `service_city` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '服务市',
  `service_district_code` varchar(12) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '服务区县编码',
  `service_district` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '服务区县',
  `contact_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '联系人姓名',
  `contact_mobile` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '联系人电话',
  `user_remark` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户备注',
  `admin_remark` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '管理员备注',
  `cancel_reason` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '取消原因',
  `cancel_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '取消时间',
  `complete_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '完成时间',
  `is_reviewed` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否已评价：0=否,1=是',
  `is_paused` tinyint(3) unsigned DEFAULT '0' COMMENT '是否暂停：0=否,1=是',
  `pause_id` int(10) unsigned DEFAULT '0' COMMENT '关联暂停记录ID',
  `has_changed` tinyint(3) unsigned DEFAULT '0' COMMENT '是否有变更记录：0=否,1=是',
  `change_count` int(10) unsigned DEFAULT '0' COMMENT '变更次数',
  `source` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '订单来源：1=小程序,3=后台,4=服务人员录入',
  `creator_user_id` int unsigned NOT NULL DEFAULT 0 COMMENT '录单用户',
  `staff_submit_key` char(64) DEFAULT NULL COMMENT '本人录单幂等标识',
  `staff_submit_hash` char(64) NOT NULL DEFAULT '' COMMENT '录单内容摘要',
  UNIQUE KEY `uk_staff_submit` (`staff_submit_key`),
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_sn` (`order_sn`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_order_status` (`order_status`),
  KEY `idx_pay_status` (`pay_status`),
  KEY `idx_service_date` (`service_date`),
  KEY `idx_pay_deadline_time` (`order_status`,`pay_deadline_time`),
  KEY `idx_confirm_deadline_time` (`order_status`,`confirm_deadline_time`),
  KEY `idx_current_confirm_letter_id` (`current_confirm_letter_id`),
  KEY `idx_create_time` (`create_time`),
  KEY `idx_is_paused` (`is_paused`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单表';
CREATE TABLE IF NOT EXISTS `la_order_receipt_request` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL,
  `staff_id` int unsigned NOT NULL,
  `submit_user_id` int unsigned NOT NULL,
  `submit_key` char(64) NOT NULL,
  `request_hash` char(64) NOT NULL,
  `pay_type` tinyint unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `pay_voucher` varchar(512) NOT NULL,
  `collection_owner` tinyint unsigned NOT NULL DEFAULT 2,
  `status` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '0待审核，1通过，2驳回',
  `pending_order_id` int unsigned GENERATED ALWAYS AS (CASE WHEN `status` = 0 THEN `order_id` ELSE NULL END) STORED,
  `payment_id` int unsigned NOT NULL DEFAULT 0,
  `audit_admin_id` int unsigned NOT NULL DEFAULT 0,
  `audit_time` int unsigned NOT NULL DEFAULT 0,
  `reason` varchar(500) NOT NULL DEFAULT '',
  `create_time` int unsigned NOT NULL,
  `update_time` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_submit_key` (`submit_key`),
  UNIQUE KEY `uk_pending_order` (`pending_order_id`),
  KEY `idx_order_history` (`order_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `la_order_change` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `change_sn` varchar(32) COLLATE utf8mb4_general_ci NOT NULL COMMENT '变更单号',
  `order_id` int(10) unsigned NOT NULL COMMENT '订单ID',
  `order_sn` varchar(32) COLLATE utf8mb4_general_ci NOT NULL COMMENT '订单编号(冗余)',
  `user_id` int(10) unsigned NOT NULL COMMENT '用户ID',
  `change_type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '变更类型：1=改期，3=加项，4=附加服务变更',
  `addon_action` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '附加服务动作：1=新增,2=移除',
  `change_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '变更状态：0=待审核,1=审核通过,2=审核拒绝,3=已执行,4=已取消',
  `old_service_date` date DEFAULT NULL COMMENT '原服务日期',
  `new_service_date` date DEFAULT NULL COMMENT '新服务日期',
  `old_time_slot` tinyint(3) unsigned DEFAULT '0' COMMENT '原时间段',
  `new_time_slot` tinyint(3) unsigned DEFAULT '0' COMMENT '新时间段',
  `order_item_id` int(10) unsigned DEFAULT '0' COMMENT '订单项ID',
  `old_schedule_id` int(10) unsigned DEFAULT '0' COMMENT '原档期ID',
  `new_schedule_id` int(10) unsigned DEFAULT '0' COMMENT '新档期ID',
  `old_price` decimal(10,2) DEFAULT '0.00' COMMENT '原价格',
  `new_price` decimal(10,2) DEFAULT '0.00' COMMENT '新价格',
  `price_diff` decimal(10,2) DEFAULT '0.00' COMMENT '差价(正数补付,负数退款)',
  `diff_paid` tinyint(3) unsigned DEFAULT '0' COMMENT '差价是否已处理：0=否,1=是',
  `diff_payment_id` int(10) unsigned DEFAULT '0' COMMENT '差价支付记录ID',
  `diff_refund_id` int(10) unsigned DEFAULT '0' COMMENT '差价退款记录ID',
  `add_staff_id` int(10) unsigned DEFAULT '0' COMMENT '新增工作人员ID',
  `add_package_id` int(10) unsigned DEFAULT '0' COMMENT '新增套餐ID',
  `add_staff_name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '新增人员姓名',
  `add_package_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '新增套餐名称',
  `add_service_date` date DEFAULT NULL COMMENT '新增服务日期',
  `add_time_slot` tinyint(3) unsigned DEFAULT '0' COMMENT '新增时间段',
  `add_price` decimal(10,2) DEFAULT '0.00' COMMENT '新增价格',
  `add_schedule_id` int(10) unsigned DEFAULT '0' COMMENT '新增档期ID',
  `add_order_item_id` int(10) unsigned DEFAULT '0' COMMENT '新增订单项ID',
  `apply_reason` varchar(500) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '申请原因',
  `user_remark` varchar(500) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '用户备注',
  `audit_admin_id` int(10) unsigned DEFAULT '0' COMMENT '审核管理员ID',
  `audit_time` int(10) unsigned DEFAULT '0' COMMENT '审核时间',
  `audit_remark` varchar(500) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '审核备注',
  `reject_reason` varchar(500) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '拒绝原因',
  `execute_time` int(10) unsigned DEFAULT '0' COMMENT '执行时间',
  `execute_admin_id` int(10) unsigned DEFAULT '0' COMMENT '执行管理员ID',
  `attach_images` text COLLATE utf8mb4_general_ci COMMENT '附件图片(JSON数组)',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_change_sn` (`change_sn`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_change_type` (`change_type`),
  KEY `idx_change_status` (`change_status`),
  KEY `idx_order_status` (`order_id`,`change_status`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单变更申请表';
CREATE TABLE `la_order_change_addon` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `change_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '变更单ID',
  `order_item_addon_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '订单附加服务快照ID',
  `addon_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '附加服务ID',
  `addon_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '附加服务名称',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '单价',
  `quantity` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '数量',
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '小计',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_change_id` (`change_id`),
  KEY `idx_order_item_addon_id` (`order_item_addon_id`),
  KEY `idx_addon_id` (`addon_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单变更附加服务明细表';
CREATE TABLE `la_order_change_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `order_id` int(10) unsigned NOT NULL COMMENT '订单ID',
  `related_type` tinyint(3) unsigned NOT NULL COMMENT '关联类型：1=变更,2=转让,3=暂停',
  `related_id` int(10) unsigned NOT NULL COMMENT '关联记录ID',
  `operator_type` tinyint(3) unsigned NOT NULL COMMENT '操作者类型：1=用户,2=管理员,3=系统',
  `operator_id` int(10) unsigned DEFAULT '0' COMMENT '操作者ID',
  `operator_name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '操作者名称',
  `action` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT '操作动作',
  `before_status` tinyint(3) unsigned DEFAULT '0' COMMENT '操作前状态',
  `after_status` tinyint(3) unsigned DEFAULT '0' COMMENT '操作后状态',
  `before_data` text COLLATE utf8mb4_general_ci COMMENT '变更前数据(JSON)',
  `after_data` text COLLATE utf8mb4_general_ci COMMENT '变更后数据(JSON)',
  `content` varchar(500) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '日志内容',
  `ip` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT 'IP地址',
  `user_agent` varchar(500) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '用户代理',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_related` (`related_type`,`related_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单变更日志表';
CREATE TABLE `la_order_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `order_id` int(10) unsigned NOT NULL COMMENT '订单ID',
  `staff_id` int(10) unsigned NOT NULL COMMENT '工作人员ID',
  `package_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '套餐ID',
  `schedule_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '档期ID',
  `service_date` date NOT NULL COMMENT '服务日期',
  `time_slot` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '时间段',
  `staff_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '人员姓名(冗余)',
  `package_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '套餐名称(冗余)',
  `package_description` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '套餐说明快照',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '单价',
  `quantity` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '数量',
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '小计',
  `item_type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '订单项类型：1=主服务，2=预约附加项，3=关联服务人员',
  `item_meta` text COLLATE utf8mb4_general_ci COMMENT '订单项扩展信息(JSON)',
  `item_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '项状态：0=待服务,1=服务中,2=已完成,3=已取消',
  `confirm_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '确认状态：0=待确认,1=已确认',
  `is_changed` tinyint(3) unsigned DEFAULT '0' COMMENT '是否变更过：0=否,1=是',
  `change_id` int(10) unsigned DEFAULT '0' COMMENT '关联变更记录ID',
  `remark` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_staff_id` (`staff_id`),
  KEY `idx_service_date` (`service_date`),
  KEY `idx_order_staff` (`order_id`,`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单项表';
CREATE TABLE `la_order_item_addon` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `order_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '订单ID',
  `order_item_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '主订单项ID',
  `addon_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '附加服务ID',
  `addon_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '附加服务快照名称',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '快照单价',
  `quantity` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '数量，固定为1',
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '小计',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态：1=生效中，2=已移除',
  `create_source` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '创建来源：1=下单，2=变更',
  `create_change_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建变更单ID',
  `remove_change_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '移除变更单ID',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_order_item_id` (`order_item_id`),
  KEY `idx_addon_id` (`addon_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单附加服务快照表';
CREATE TABLE `la_order_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `order_id` int(10) unsigned NOT NULL COMMENT '订单ID',
  `operator_type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '操作者类型：1=用户,2=管理员,3=系统',
  `operator_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作者ID',
  `action` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '操作动作',
  `before_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '操作前状态',
  `after_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '操作后状态',
  `content` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '日志内容',
  `ip` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'IP地址',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单日志表';
CREATE TABLE `la_order_pause` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `pause_sn` varchar(32) COLLATE utf8mb4_general_ci NOT NULL COMMENT '暂停单号',
  `order_id` int(10) unsigned NOT NULL COMMENT '订单ID',
  `order_sn` varchar(32) COLLATE utf8mb4_general_ci NOT NULL COMMENT '订单编号(冗余)',
  `user_id` int(10) unsigned NOT NULL COMMENT '用户ID',
  `pause_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '暂停状态：0=待审核,1=暂停中,2=已恢复,3=已拒绝,4=已取消',
  `pause_type` tinyint(3) unsigned NOT NULL COMMENT '暂停类型：1=疫情,2=突发事件,3=个人原因,4=其他',
  `pause_reason` varchar(500) COLLATE utf8mb4_general_ci NOT NULL COMMENT '暂停原因',
  `pause_start_date` date DEFAULT NULL COMMENT '暂停开始日期',
  `pause_end_date` date DEFAULT NULL COMMENT '暂停结束日期(预计)',
  `pause_days` int(10) unsigned DEFAULT '0' COMMENT '暂停天数',
  `original_service_date` date DEFAULT NULL COMMENT '原服务日期',
  `audit_admin_id` int(10) unsigned DEFAULT '0' COMMENT '审核管理员ID',
  `audit_time` int(10) unsigned DEFAULT '0' COMMENT '审核时间',
  `audit_remark` varchar(500) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '审核备注',
  `reject_reason` varchar(500) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '拒绝原因',
  `resume_time` int(10) unsigned DEFAULT '0' COMMENT '恢复时间',
  `resume_admin_id` int(10) unsigned DEFAULT '0' COMMENT '恢复操作管理员ID',
  `resume_remark` varchar(500) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '恢复备注',
  `actual_pause_days` int(10) unsigned DEFAULT '0' COMMENT '实际暂停天数',
  `new_service_date` date DEFAULT NULL COMMENT '恢复后新服务日期',
  `remind_before_days` int(10) unsigned DEFAULT '3' COMMENT '提前提醒天数',
  `reminded` tinyint(3) unsigned DEFAULT '0' COMMENT '是否已提醒：0=否,1=是',
  `remind_time` int(10) unsigned DEFAULT '0' COMMENT '提醒时间',
  `proof_images` text COLLATE utf8mb4_general_ci COMMENT '证明材料图片(JSON数组)',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pause_sn` (`pause_sn`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_pause_status` (`pause_status`),
  KEY `idx_pause_end_date` (`pause_end_date`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单暂停表';
CREATE TABLE `la_package_booking` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `package_id` int(10) unsigned NOT NULL COMMENT '套餐ID',
  `staff_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '服务人员ID（0=不限人员）',
  `booking_date` date NOT NULL COMMENT '预订日期',
  `time_slot` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '时间段：0=全天,1=早礼,2=午宴,3=晚宴',
  `start_time` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '开始时间 HH:mm',
  `end_time` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '结束时间 HH:mm',
  `order_id` int(10) unsigned DEFAULT NULL COMMENT '关联订单ID',
  `order_item_id` int(10) unsigned DEFAULT NULL COMMENT '关联订单项ID',
  `user_id` int(10) unsigned DEFAULT NULL COMMENT '预订用户ID',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态：0=已释放,1=临时锁定,2=已确认',
  `lock_expire_time` int(10) unsigned DEFAULT NULL COMMENT '临时锁定过期时间戳',
  `version` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '乐观锁版本号',
  `create_time` int(10) unsigned NOT NULL COMMENT '创建时间',
  `update_time` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_staff_package_date_slot` (`staff_id`,`package_id`,`booking_date`,`time_slot`),
  KEY `idx_staff_id` (`staff_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_lock_expire` (`lock_expire_time`),
  KEY `idx_booking_date` (`booking_date`),
  KEY `idx_package_date_status` (`package_id`,`booking_date`,`status`),
  KEY `idx_staff_date_status` (`staff_id`,`booking_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='套餐预订记录表（用于单日唯一限制）';
CREATE TABLE `la_payment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `query_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后支付查询时间',
  `closed_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '微信关单确认时间',
  `payment_sn` varchar(32) COLLATE utf8mb4_general_ci NOT NULL COMMENT '支付流水号',
  `order_id` int(10) unsigned NOT NULL COMMENT '订单ID',
  `order_sn` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '订单编号',
  `user_id` int(10) unsigned NOT NULL COMMENT '用户ID',
  `pay_type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '支付类型：1=定金,2=尾款,3=全款',
  `pay_way` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '支付方式：1=微信，4=线下',
  `collection_owner` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '收款归属：1=平台，2=服务人员代收',
  `pay_voucher` varchar(500) NOT NULL DEFAULT '' COMMENT '本笔收款凭证快照',
  `pay_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '支付金额',
  `pay_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '支付状态：0=待支付,1=已支付,2=已退款,3=支付失败,4=异常支付',
  `transaction_id` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '第三方交易号',
  `pay_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '支付时间',
  `expire_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '过期时间',
  `callback_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '回调时间',
  `callback_data` text COLLATE utf8mb4_general_ci COMMENT '回调数据',
  `refund_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '退款金额',
  `refund_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '退款时间',
  `refund_reason` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '退款原因',
  `remark` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_payment_sn` (`payment_sn`),
  UNIQUE KEY `uk_transaction_id` (`transaction_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_pay_status` (`pay_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='支付记录表';
CREATE TABLE `la_refund` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `is_compensation` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '异常实收补偿退款',
  `refund_sn` varchar(32) COLLATE utf8mb4_general_ci NOT NULL COMMENT '退款编号',
  `order_id` int(10) unsigned NOT NULL COMMENT '订单ID',
  `payment_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '支付记录ID',
  `user_id` int(10) unsigned NOT NULL COMMENT '用户ID',
  `refund_type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '退款类型：1=用户申请,2=管理员操作,3=系统自动',
  `refund_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '退款金额',
  `actual_refund_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实际退款金额',
  `refund_reason` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '退款原因',
  `refund_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '退款状态：0=待审核,1=审核通过,2=退款中,3=已退款,4=已拒绝,5=退款失败',
  `source_order_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '发起退款前订单状态',
  `source_pay_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '发起退款前支付状态',
  `audit_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '审核管理员ID',
  `audit_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '审核时间',
  `audit_remark` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '审核备注',
  `refund_transaction_id` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '退款交易号',
  `refund_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '实际退款时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_refund_sn` (`refund_sn`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_refund_status` (`refund_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='退款记录表';
CREATE TABLE `la_refund_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `refund_voucher` varchar(512) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '线下实际退款凭证',
  `refund_id` int(10) unsigned NOT NULL COMMENT '退款单ID',
  `order_id` int(10) unsigned NOT NULL COMMENT '订单ID',
  `payment_id` int(10) unsigned NOT NULL COMMENT '支付记录ID',
  `pay_way` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '支付方式：1=微信，4=线下',
  `pay_terminal` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '支付终端',
  `refund_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '子项退款金额',
  `refund_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '退款状态：0=待执行,1=处理中,2=已完成,3=失败',
  `out_refund_no` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '商户退款单号',
  `third_refund_no` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '第三方退款单号',
  `refund_msg` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '退款处理说明',
  `refund_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '退款完成时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_out_refund_no` (`out_refund_no`),
  KEY `idx_refund_id` (`refund_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_payment_id` (`payment_id`),
  KEY `idx_refund_status` (`refund_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='退款子项表';
CREATE TABLE `la_reshoot` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '补拍ID',
  `reshoot_sn` varchar(32) NOT NULL DEFAULT '' COMMENT '补拍编号',
  `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关联订单ID',
  `order_item_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关联订单项ID',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `staff_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '服务人员ID',
  `reason` text COMMENT '补拍原因',
  `images` text COMMENT '图片凭证 JSON数组',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态 0待审核 1已通过 2已拒绝 3已完成 4已取消',
  `audit_admin_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '审核人ID',
  `audit_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '审核时间',
  `audit_remark` varchar(255) NOT NULL DEFAULT '' COMMENT '审核备注',
  `reject_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '拒绝原因',
  `schedule_date` date DEFAULT NULL COMMENT '补拍日期',
  `time_slot` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '补拍时间段 0全天 1早礼 2午宴 3晚宴',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_reshoot_sn` (`reshoot_sn`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_staff_id` (`staff_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='补拍申请表';
CREATE TABLE `la_review` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '评价ID',
  `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单ID',
  `order_item_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单项ID',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `staff_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '服务人员ID',
  `score` decimal(2,1) unsigned NOT NULL DEFAULT '5.0' COMMENT '综合评分 1-5星',
  `score_service` tinyint(1) unsigned NOT NULL DEFAULT '5' COMMENT '服务态度评分',
  `score_professional` tinyint(1) unsigned NOT NULL DEFAULT '5' COMMENT '专业水平评分',
  `score_punctual` tinyint(1) unsigned NOT NULL DEFAULT '5' COMMENT '时间守约评分',
  `score_effect` tinyint(1) unsigned NOT NULL DEFAULT '5' COMMENT '整体效果评分',
  `custom_tags` text COMMENT '用户自定义标签 JSON数组',
  `content` text COMMENT '评价内容',
  `images` text COMMENT '评价图片 JSON数组',
  `video` varchar(500) NOT NULL DEFAULT '' COMMENT '评价视频URL',
  `video_cover` varchar(500) NOT NULL DEFAULT '' COMMENT '视频封面图URL',
  `is_anonymous` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否匿名',
  `is_top` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否置顶',
  `top_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '置顶时间',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态 0待审核 1已通过 2已拒绝',
  `reject_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '拒绝原因',
  `review_type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '评价类型 1文字 2图文 3视频',
  `like_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '点赞数',
  `reply_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '回复数',
  `is_show` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示',
  `admin_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '审核人ID',
  `audit_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '审核时间',
  `service_date` date DEFAULT NULL COMMENT '服务日期（冗余）',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_staff_id` (`staff_id`),
  KEY `idx_status` (`status`),
  KEY `idx_score` (`score`),
  KEY `idx_is_top` (`is_top`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='评价主表';
CREATE TABLE `la_review_like` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `review_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '评价ID',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_review_user` (`review_id`,`user_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='评价点赞表';
CREATE TABLE `la_review_reply` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '回复ID',
  `review_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '评价ID',
  `parent_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '父回复ID',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `admin_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `staff_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '服务人员ID',
  `reply_type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '回复类型 1用户追评 2商家回复 3人员回复',
  `content` text COMMENT '回复内容',
  `images` text COMMENT '回复图片 JSON数组',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态 0待审核 1已通过 2已拒绝',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_review_id` (`review_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_reply_type` (`reply_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='评价回复表';
CREATE TABLE `la_review_tag` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '标签ID',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '标签名称',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '标签类型 1好评 2中评 3差评',
  `icon` varchar(255) NOT NULL DEFAULT '' COMMENT '标签图标',
  `color` varchar(20) NOT NULL DEFAULT '' COMMENT '标签颜色',
  `sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `use_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '使用次数',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态 0禁用 1启用',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='评价标签表';
INSERT INTO `la_review_tag` (`id`,`name`,`type`,`icon`,`color`,`sort`,`use_count`,`status`,`create_time`,`update_time`,`delete_time`) VALUES ('1','服务热情','1','','#52c41a','1','0','1','1773413105','1773413105',NULL);
INSERT INTO `la_review_tag` (`id`,`name`,`type`,`icon`,`color`,`sort`,`use_count`,`status`,`create_time`,`update_time`,`delete_time`) VALUES ('2','专业细致','1','','#52c41a','2','0','1','1773413105','1773413105',NULL);
INSERT INTO `la_review_tag` (`id`,`name`,`type`,`icon`,`color`,`sort`,`use_count`,`status`,`create_time`,`update_time`,`delete_time`) VALUES ('3','准时守约','1','','#52c41a','3','0','1','1773413105','1773413105',NULL);
INSERT INTO `la_review_tag` (`id`,`name`,`type`,`icon`,`color`,`sort`,`use_count`,`status`,`create_time`,`update_time`,`delete_time`) VALUES ('4','效果满意','1','','#52c41a','4','0','1','1773413105','1773413105',NULL);
INSERT INTO `la_review_tag` (`id`,`name`,`type`,`icon`,`color`,`sort`,`use_count`,`status`,`create_time`,`update_time`,`delete_time`) VALUES ('5','沟通顺畅','1','','#52c41a','5','0','1','1773413105','1773413105',NULL);
INSERT INTO `la_review_tag` (`id`,`name`,`type`,`icon`,`color`,`sort`,`use_count`,`status`,`create_time`,`update_time`,`delete_time`) VALUES ('6','性价比高','1','','#52c41a','6','0','1','1773413105','1773413105',NULL);
INSERT INTO `la_review_tag` (`id`,`name`,`type`,`icon`,`color`,`sort`,`use_count`,`status`,`create_time`,`update_time`,`delete_time`) VALUES ('7','耐心负责','1','','#52c41a','7','0','1','1773413105','1773413105',NULL);
INSERT INTO `la_review_tag` (`id`,`name`,`type`,`icon`,`color`,`sort`,`use_count`,`status`,`create_time`,`update_time`,`delete_time`) VALUES ('8','创意独特','1','','#52c41a','8','0','1','1773413105','1773413105',NULL);
INSERT INTO `la_review_tag` (`id`,`name`,`type`,`icon`,`color`,`sort`,`use_count`,`status`,`create_time`,`update_time`,`delete_time`) VALUES ('9','一般般','2','','#faad14','10','0','1','1773413105','1773413105',NULL);
INSERT INTO `la_review_tag` (`id`,`name`,`type`,`icon`,`color`,`sort`,`use_count`,`status`,`create_time`,`update_time`,`delete_time`) VALUES ('10','有待改进','2','','#faad14','11','0','1','1773413105','1773413105',NULL);
INSERT INTO `la_review_tag` (`id`,`name`,`type`,`icon`,`color`,`sort`,`use_count`,`status`,`create_time`,`update_time`,`delete_time`) VALUES ('11','服务冷淡','3','','#ff4d4f','20','0','1','1773413105','1773413105',NULL);
INSERT INTO `la_review_tag` (`id`,`name`,`type`,`icon`,`color`,`sort`,`use_count`,`status`,`create_time`,`update_time`,`delete_time`) VALUES ('12','不够专业','3','','#ff4d4f','21','0','1','1773413105','1773413105',NULL);
INSERT INTO `la_review_tag` (`id`,`name`,`type`,`icon`,`color`,`sort`,`use_count`,`status`,`create_time`,`update_time`,`delete_time`) VALUES ('13','迟到早退','3','','#ff4d4f','22','0','1','1773413105','1773413105',NULL);
INSERT INTO `la_review_tag` (`id`,`name`,`type`,`icon`,`color`,`sort`,`use_count`,`status`,`create_time`,`update_time`,`delete_time`) VALUES ('14','效果不佳','3','','#ff4d4f','23','0','1','1773413105','1773413105',NULL);
CREATE TABLE `la_review_tag_relation` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `review_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '评价ID',
  `tag_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '标签ID',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_review_tag` (`review_id`,`tag_id`),
  KEY `idx_tag_id` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='评价标签关联表';
CREATE TABLE `la_sales_advisor` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联管理员ID',
  `advisor_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT '顾问姓名',
  `avatar` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '头像',
  `mobile` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '手机号',
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `areas` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '负责区域(JSON)',
  `specialties` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '擅长服务类型(JSON)',
  `max_customer_count` int(10) unsigned NOT NULL DEFAULT '100' COMMENT '最大客户数',
  `current_customer_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '当前客户数',
  `total_order_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '累计成交订单数',
  `total_order_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '累计成交金额',
  `conversion_rate` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '转化率(%)',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态：0=离职,1=正常,2=休假',
  `sort` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序权重',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_status` (`status`),
  KEY `idx_current_customer_count` (`current_customer_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='销售顾问表';
CREATE TABLE `la_schedule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `staff_id` int(10) unsigned NOT NULL COMMENT '工作人员ID',
  `schedule_date` date NOT NULL COMMENT '档期日期',
  `time_slot` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '时间段：0=全天,1=早礼,2=午宴,3=晚宴',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态：0=不可用,1=可预约,2=已预约,3=已锁定,4=内部预留',
  `order_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联订单ID',
  `lock_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '锁定类型：0=正常,1=VIP锁定,2=内部预留',
  `lock_user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '锁定用户ID',
  `lock_expire_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '锁定到期时间',
  `lock_reason` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '锁定/预留原因',
  `manual_schedule_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '线下档期占用归属',
  `queue_lock_until` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '候补锁档截止时间',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '当日价格（0=使用默认价格）',
  `remark` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `version` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '乐观锁版本号',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_staff_date_slot` (`staff_id`,`schedule_date`,`time_slot`),
  KEY `idx_schedule_date` (`schedule_date`),
  KEY `idx_status` (`status`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_staff_date_status` (`staff_id`,`schedule_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='档期表';
CREATE TABLE `la_manual_schedule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `staff_id` int(10) unsigned NOT NULL COMMENT '服务人员ID',
  `schedule_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '占用档期ID',
  `schedule_date` date NOT NULL COMMENT '服务日期',
  `service_name` varchar(100) NOT NULL DEFAULT '' COMMENT '服务名称',
  `customer_name` varchar(50) NOT NULL DEFAULT '' COMMENT '客户称呼',
  `customer_mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '客户电话',
  `region_name` varchar(100) NOT NULL DEFAULT '' COMMENT '地区',
  `service_address` varchar(255) NOT NULL DEFAULT '' COMMENT '服务地点',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '状态：0待履约,1已完成,2已取消',
  `source_month` char(7) NOT NULL DEFAULT '' COMMENT '首次录入月份',
  `complete_time` int(10) unsigned NOT NULL DEFAULT '0',
  `cancel_time` int(10) unsigned NOT NULL DEFAULT '0',
  `version` int(10) unsigned NOT NULL DEFAULT '1',
  `request_id` varchar(64) NOT NULL DEFAULT '' COMMENT '幂等请求号',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_staff_request` (`staff_id`,`request_id`),
  KEY `idx_staff_date_status` (`staff_id`,`schedule_date`,`status`),
  KEY `idx_source_month` (`source_month`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务人员线下业务档期';
CREATE TABLE `la_schedule_lock` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `schedule_id` int(10) unsigned NOT NULL COMMENT '档期ID',
  `staff_id` int(10) unsigned NOT NULL COMMENT '工作人员ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '锁定用户ID',
  `lock_type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '锁定类型：1=VIP锁定,2=内部预留,3=临时锁定',
  `lock_start_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '锁定开始时间',
  `lock_end_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '锁定结束时间',
  `lock_reason` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '锁定原因',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态：0=已释放,1=锁定中,2=已转订单',
  `release_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '释放时间',
  `release_reason` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '释放原因',
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作管理员ID',
  `active_key` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '有效锁唯一键，已释放为空',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_active_lock` (`active_key`),
  KEY `idx_schedule_id` (`schedule_id`),
  KEY `idx_staff_id` (`staff_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='档期锁定记录表';
CREATE TABLE `la_schedule_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `staff_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '工作人员ID（0=全局规则）',
  `advance_days` int(10) unsigned NOT NULL DEFAULT '3' COMMENT '提前预约天数',
  `max_orders_per_day` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '单日最大接单数',
  `rest_days` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '休息日（逗号分隔，0=周日,1=周一...）',
  `is_enabled` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '是否启用：0=否,1=是',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='档期规则表';
INSERT INTO `la_schedule_rule` (`id`,`staff_id`,`advance_days`,`max_orders_per_day`,`rest_days`,`is_enabled`,`create_time`,`update_time`) VALUES ('1','0','3','1','','1','1773413104','1773413104');
CREATE TABLE `la_schedule_share` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `group_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '组合名称',
  `staff_ids` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '工作人员ID列表（逗号分隔）',
  `share_type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '共享类型：1=档期同步,2=组合套餐',
  `discount_rate` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '组合折扣率（如95表示95折）',
  `is_enabled` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '是否启用：0=否,1=是',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='档期共享表';
CREATE TABLE `la_sensitive_word` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `word` varchar(100) NOT NULL DEFAULT '' COMMENT '敏感词',
  `replace_word` varchar(100) NOT NULL DEFAULT '***' COMMENT '替换词',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '类型 1广告 2违法 3政治 4色情 5其他',
  `level` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '级别 1警告 2禁止',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态 0禁用 1启用',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_word` (`word`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='敏感词表';
CREATE TABLE `la_service_addon` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `staff_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '所属服务人员ID',
  `category_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '所属服务分类ID',
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '附加服务名称',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '售价',
  `original_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '原价',
  `image` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '图片',
  `description` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '描述',
  `sort` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `is_show` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否上架：0=下架，1=上架',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_staff_id` (`staff_id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_is_show` (`is_show`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='附加服务配置表';
CREATE TABLE `la_service_callback` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '回访ID',
  `callback_sn` varchar(32) NOT NULL DEFAULT '' COMMENT '回访编号',
  `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关联订单ID',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `staff_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '服务人员ID',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '回访类型 1服务前 2服务中 3服务后',
  `method` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '回访方式 1电话 2短信 3微信 4小程序问卷',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态 0待回访 1已回访 2无法联系 3已取消',
  `plan_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '计划回访时间',
  `actual_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '实际回访时间',
  `admin_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '回访人ID',
  `duration` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '回访时长（秒）',
  `score` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '满意度评分 0未评 1-5星',
  `score_service` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '服务态度评分',
  `score_professional` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '专业水平评分',
  `score_punctual` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '时间守约评分',
  `score_overall` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '整体满意度评分',
  `content` text COMMENT '回访内容/用户反馈',
  `summary` text COMMENT '回访摘要',
  `has_problem` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否有问题 0否 1是',
  `problem_type` varchar(100) NOT NULL DEFAULT '' COMMENT '问题类型',
  `problem_desc` text COMMENT '问题描述',
  `problem_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '问题状态 0未处理 1已处理 2已升级',
  `problem_handle_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '问题处理时间',
  `ticket_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关联工单ID（升级时创建）',
  `retry_count` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '重试次数',
  `next_retry_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '下次重试时间',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_callback_sn` (`callback_sn`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_staff_id` (`staff_id`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`type`),
  KEY `idx_plan_time` (`plan_time`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='服务回访表';
CREATE TABLE `la_service_category` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '分类ID',
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上级分类ID',
  `icon` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '分类图标',
  `image` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '分类图片',
  `booking_butler_enabled` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否启用婚礼管家预约：0-否，1-是',
  `booking_butler_category_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '婚礼管家关联服务分类ID',
  `booking_director_enabled` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否启用婚礼督导预约：0-否，1-是',
  `booking_director_category_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '婚礼督导关联服务分类ID',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序(数值越大越靠前)',
  `is_show` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示:0-否,1-是',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_pid` (`pid`) USING BTREE,
  KEY `idx_is_show` (`is_show`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务分类表';
CREATE TABLE `la_service_city_pool` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `province_code` varchar(12) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '省编码',
  `province_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '省名称',
  `city_code` varchar(12) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '市编码',
  `city_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '市名称',
  `sort` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态：0=停用，1=启用',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_city_code` (`city_code`),
  KEY `idx_status_sort` (`status`,`sort`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='可接单城市池';
CREATE TABLE `la_service_package` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '套餐ID',
  `category_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '服务分类ID',
  `staff_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '所属服务人员ID',
  `package_type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '套餐类型：1=全局套餐,2=人员专属套餐',
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '套餐名称',
  `price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '套餐价格',
  `original_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '原价',
  `duration` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '服务时长(小时)',
  `description` text COLLATE utf8mb4_general_ci COMMENT '套餐描述',
  `content` text COLLATE utf8mb4_general_ci COMMENT '套餐内容(JSON格式)',
  `image` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '套餐图片',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `is_recommend` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否推荐:0-否,1-是',
  `is_show` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示:0-否,1-是',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_category_id` (`category_id`) USING BTREE,
  KEY `idx_staff_id` (`staff_id`),
  KEY `idx_is_recommend` (`is_recommend`) USING BTREE,
  KEY `idx_package_type` (`package_type`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务套餐表';
CREATE TABLE `la_service_package_addon` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `package_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '套餐ID',
  `addon_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '附加服务ID',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_package_addon` (`package_id`,`addon_id`),
  KEY `idx_package_id` (`package_id`),
  KEY `idx_addon_id` (`addon_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='套餐附加服务关联表';
CREATE TABLE `la_service_package_region_price` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `package_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '套餐ID',
  `staff_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '人员ID',
  `region_level` tinyint(1) unsigned NOT NULL DEFAULT '2' COMMENT '地区层级：1=省，2=市，3=区县',
  `province_code` varchar(12) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '省编码',
  `province_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '省名称',
  `city_code` varchar(12) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '市编码',
  `city_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '市名称',
  `district_code` varchar(12) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '区县编码',
  `district_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '区县名称',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '地区售价',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_package_region` (`package_id`,`region_level`,`province_code`,`city_code`,`district_code`),
  KEY `idx_staff_package` (`staff_id`,`package_id`),
  KEY `idx_city_district` (`city_code`,`district_code`),
  KEY `idx_province_city_district` (`province_code`,`city_code`,`district_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='套餐地区价格表';
CREATE TABLE `la_settlement_batch` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `batch_sn` varchar(32) COLLATE utf8mb4_general_ci NOT NULL COMMENT '批次编号',
  `batch_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '批次名称',
  `settle_start_date` date NOT NULL COMMENT '结算开始日期',
  `settle_end_date` date NOT NULL COMMENT '结算结束日期',
  `total_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '结算总笔数',
  `success_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '成功笔数',
  `fail_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '失败笔数',
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '结算总金额',
  `success_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '成功金额',
  `fail_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '失败金额',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '状态：0=待审核,1=审核通过,2=处理中,3=已完成,4=已取消',
  `audit_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '审核管理员ID',
  `audit_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '审核时间',
  `audit_remark` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '审核备注',
  `execute_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '执行管理员ID',
  `execute_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '执行时间',
  `complete_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '完成时间',
  `remark` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_batch_sn` (`batch_sn`),
  KEY `idx_status` (`status`),
  KEY `idx_settle_date` (`settle_start_date`,`settle_end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='结算批次表';
CREATE TABLE `la_sms_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `scene_id` int(11) NOT NULL COMMENT '场景id',
  `mobile` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '手机号码',
  `content` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '发送内容',
  `code` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '发送关键字（注册、找回密码）',
  `is_verify` tinyint(1) DEFAULT '0' COMMENT '是否已验证；0-否；1-是',
  `check_num` int(5) DEFAULT '0' COMMENT '验证次数',
  `send_status` tinyint(1) NOT NULL COMMENT '发送状态：0-发送中；1-发送成功；2-发送失败',
  `send_time` int(10) NOT NULL COMMENT '发送时间',
  `results` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '短信结果',
  `create_time` int(10) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='短信记录表';
CREATE TABLE `la_staff` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '工作人员ID',
  `user_id` int(11) unsigned DEFAULT NULL COMMENT '关联用户ID(用于登录)',
  `admin_id` int(11) unsigned DEFAULT NULL COMMENT '关联后台管理员ID',
  `active_user_id` int unsigned GENERATED ALWAYS AS (IF(`delete_time` IS NULL, NULLIF(`user_id`, 0), NULL)) STORED,
  `active_admin_id` int unsigned GENERATED ALWAYS AS (IF(`delete_time` IS NULL, NULLIF(`admin_id`, 0), NULL)) STORED,
  `sn` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '工号',
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '姓名',
  `avatar` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '头像',
  `mobile` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '手机号(脱敏显示)',
  `mobile_full` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '完整手机号',
  `category_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '服务分类ID',
  `experience_years` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '从业年限',
  `profile` text COLLATE utf8mb4_general_ci COMMENT '个人简介',
  `service_desc` text COLLATE utf8mb4_general_ci COMMENT '服务说明',
  `long_detail` longtext COLLATE utf8mb4_general_ci COMMENT '长图详情(JSON)',
  `booking_option_1_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '预约附加项1名称',
  `booking_option_1_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '预约附加项1价格',
  `booking_option_2_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '预约附加项2名称',
  `booking_option_2_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '预约附加项2价格',
  `banner_mode` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '轮播图展示模式：1=小图模式，2=大图模式',
  `banner_small_height` int(11) unsigned NOT NULL DEFAULT '400' COMMENT '小图模式初始高度（rpx）',
  `banner_large_height` int(11) unsigned NOT NULL DEFAULT '600' COMMENT '大图模式/展开后高度（rpx）',
  `banner_indicator_style` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '指示器样式：1=圆点，2=数字，3=进度条，0=无',
  `banner_autoplay` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否自动轮播：0=否，1=是',
  `banner_interval` int(11) unsigned NOT NULL DEFAULT '3000' COMMENT '轮播间隔时间（毫秒）',
  `rating` decimal(3,2) unsigned NOT NULL DEFAULT '5.00' COMMENT '综合评分(1-5)',
  `rating_service` decimal(3,2) unsigned NOT NULL DEFAULT '5.00' COMMENT '服务态度评分',
  `rating_skill` decimal(3,2) unsigned NOT NULL DEFAULT '5.00' COMMENT '专业水平评分',
  `rating_price` decimal(3,2) unsigned NOT NULL DEFAULT '5.00' COMMENT '性价比评分',
  `order_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '接单数量',
  `review_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '评价数量',
  `favorite_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '收藏数量',
  `view_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '浏览数量',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序(数值越大越靠前)',
  `is_recommend` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否推荐:0-否,1-是',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态:0-禁用,1-启用',
  `audit_status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '审核状态:0-待审核,1-已通过,2-已拒绝',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_sn` (`sn`) USING BTREE,
  UNIQUE KEY `uk_staff_user` (`active_user_id`),
  UNIQUE KEY `uk_staff_admin` (`active_admin_id`),
  KEY `idx_category_id` (`category_id`) USING BTREE,
  KEY `idx_status` (`status`) USING BTREE,
  KEY `idx_is_recommend` (`is_recommend`) USING BTREE,
  KEY `idx_rating` (`rating`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='工作人员表';
CREATE TABLE `la_staff_banner` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `staff_id` int(11) unsigned NOT NULL COMMENT '人员ID',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '类型：1=图片，2=视频',
  `file_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件地址',
  `cover_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '封面图地址（视频必填）',
  `is_autoplay` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '视频是否自动播放：0=否，1=是',
  `sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序（数字越小越靠前）',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_staff_id` (`staff_id`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='人员轮播图表';
CREATE TABLE `la_staff_certificate` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '证书ID',
  `staff_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '工作人员ID',
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '证书名称',
  `type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '证书类型',
  `sn` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '证书编号',
  `image` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '证书图片',
  `issue_org` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '颁发机构',
  `issue_date` date DEFAULT NULL COMMENT '颁发日期',
  `expire_date` date DEFAULT NULL COMMENT '有效期至',
  `reject_reason` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '拒绝原因',
  `certificate_no` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '证书编号',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `audit_status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '审核状态:0-待审核,1-已通过,2-已拒绝',
  `verify_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '审核状态:0-待审核,1-已通过,2-已拒绝',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_staff_id` (`staff_id`) USING BTREE,
  KEY `idx_verify_status` (`verify_status`) USING BTREE,
  KEY `idx_sn` (`sn`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='工作人员证书表';
CREATE TABLE `la_staff_company_fee_usage` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `staff_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '服务人员ID',
  `rule_config_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '结算配置ID',
  `period_month` char(7) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '自然月：YYYY-MM',
  `monthly_fee_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '包月金额',
  `used_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '已扣金额',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_staff_month` (`staff_id`,`period_month`),
  KEY `idx_rule_config_id` (`rule_config_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务人员包月公司费用累计表';
CREATE TABLE `la_staff_package` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `staff_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '工作人员ID',
  `package_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '套餐ID',
  `custom_price` decimal(10,2) DEFAULT NULL COMMENT '个人定制价格（覆盖套餐默认价格）',
  `custom_area_prices` text COLLATE utf8mb4_general_ci COMMENT '员工自定义区域价格JSON',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态：0=禁用,1=启用',
  `price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '该人员的套餐价格(可覆盖默认价格)',
  `original_price` decimal(10,2) DEFAULT NULL COMMENT '原价（用于显示划线价）',
  `is_default` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否默认套餐:0-否,1-是',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_staff_package` (`staff_id`,`package_id`) USING BTREE,
  KEY `idx_package_id` (`package_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='工作人员套餐关联表';
CREATE TABLE `la_staff_review_stats` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `staff_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '服务人员ID',
  `total_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '总评价数',
  `good_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '好评数（4-5星）',
  `medium_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '中评数（3星）',
  `bad_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '差评数（1-2星）',
  `image_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '有图评价数',
  `video_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '有视频评价数',
  `avg_score` decimal(3,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '平均综合评分',
  `avg_score_service` decimal(3,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '平均服务态度评分',
  `avg_score_professional` decimal(3,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '平均专业水平评分',
  `avg_score_punctual` decimal(3,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '平均时间守约评分',
  `avg_score_effect` decimal(3,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '平均整体效果评分',
  `good_rate` decimal(5,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '好评率',
  `reply_rate` decimal(5,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '回复率',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='服务人员评价统计表';
CREATE TABLE `la_staff_schedule_confirm_letter` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单ID',
  `manual_schedule_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '线下档期ID，与订单二选一',
  `staff_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '服务人员ID',
  `config_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '生成使用的模板配置ID',
  `config_name` varchar(80) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '历史配置' COMMENT '生成使用的模板名称',
  `config_template_version` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '生成使用的模板版本号',
  `version` int(11) unsigned NOT NULL DEFAULT '1' COMMENT '版本号',
  `is_outdated` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否失效：0=否,1=是',
  `rendered_snapshot` json NOT NULL COMMENT '渲染快照JSON',
  `render_spec_version` varchar(60) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'staff-schedule-designer-v2' COMMENT '渲染版本',
  `snapshot_hash` char(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '快照哈希',
  `full_image_url` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '海报全图缓存',
  `thumb_image_url` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '海报缩略图缓存',
  `confirm_date` date NOT NULL COMMENT '确认日期',
  `generate_source` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'staff' COMMENT '生成来源：staff=服务人员,admin=后台',
  `generate_staff_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '生成服务人员ID',
  `generate_admin_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '生成管理员ID',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_staff_version` (`order_id`,`manual_schedule_id`,`staff_id`,`version`),
  KEY `idx_config` (`config_id`),
  KEY `idx_order_staff_current` (`order_id`,`staff_id`,`is_outdated`),
  KEY `idx_manual_current` (`manual_schedule_id`,`staff_id`,`is_outdated`),
  KEY `idx_staff_time` (`staff_id`,`create_time`),
  KEY `idx_snapshot` (`snapshot_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务人员档期确认函版本表';
CREATE TABLE `la_staff_schedule_confirm_letter_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `staff_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '服务人员ID',
  `template_name` varchar(80) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '默认海报' COMMENT '模板名称',
  `template_version` int(11) unsigned NOT NULL DEFAULT '1' COMMENT '模板版本号',
  `is_default` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否默认模板：0=否,1=是',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '模板状态：0=停用,1=启用',
  `sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序值',
  `title` varchar(80) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '档期已定' COMMENT '标题',
  `subtitle` varchar(120) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'SCHEDULE RESERVED' COMMENT '副标题',
  `content_template` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '{service_date_label}，{customer_alias}的{service_name}档期已确认，感谢信任。' COMMENT '正文模板',
  `footer_note` varchar(200) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '档期已预定，感谢信任与选择。' COMMENT '页脚文案',
  `background_type` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'color' COMMENT '背景类型：color=纯色,image=图片',
  `background_image` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '背景图',
  `background_color` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '#191713' COMMENT '背景色',
  `text_theme` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'light' COMMENT '文字主题：light=浅色,dark=深色',
  `show_customer_alias` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否展示脱敏客户称呼',
  `show_service_name` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否展示服务类型',
  `show_city` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否展示城市',
  `show_qrcode` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否展示二维码',
  `qrcode_image` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '二维码图片',
  `design_version` varchar(60) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'staff-schedule-designer-v2' COMMENT '海报设计版本',
  `design_config` json DEFAULT NULL COMMENT '自由设计配置',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_staff_status_default` (`staff_id`,`status`,`is_default`),
  KEY `idx_staff_sort` (`staff_id`,`sort`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务人员档期确认函配置表';
CREATE TABLE `la_staff_settlement` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `settlement_sn` varchar(32) COLLATE utf8mb4_general_ci NOT NULL COMMENT '结算编号',
  `batch_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '结算批次ID',
  `staff_id` int(10) unsigned NOT NULL COMMENT '服务人员ID',
  `team_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '结算时所属队伍ID',
  `leader_staff_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '结算时队长服务人员ID',
  `config_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '结算配置ID',
  `scope_type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '规则范围：1=全员默认,2=队伍,3=人员',
  `settlement_mode` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '结算模式：1=比例抽成,2=包月金额',
  `rule_source` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '规则来源：staff/team/default',
  `order_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '订单ID',
  `order_item_id` int(10) unsigned DEFAULT NULL COMMENT '订单项ID',
  `service_date` date DEFAULT NULL COMMENT '服务日期',
  `order_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额',
  `settlement_rate` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '结算比例(%)',
  `company_rate` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '公司抽成比例(%)',
  `company_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '公司扣款金额',
  `leader_rate` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '队长抽成比例(%)',
  `leader_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '队长抽成金额',
  `monthly_fee_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '包月金额',
  `monthly_fee_deduct_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '本单月费扣款',
  `settlement_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '结算金额',
  `platform_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '平台抽成',
  `platform_paid_share_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '本结算行分摊的平台实收金额',
  `staff_due_platform_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '服务人员应补平台金额',
  `staff_due_collected_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '服务人员已补平台金额',
  `staff_due_collect_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '补收状态：0=无需补收,1=待补收,2=部分补收,3=已补收',
  `staff_due_collect_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最近补收时间',
  `staff_due_collect_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最近补收管理员ID',
  `staff_due_collect_remark` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '最近补收备注',
  `cost_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '扣除成本',
  `actual_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实际结算金额',
  `settlement_type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '结算类型：1=自动,2=手动',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '状态：0=待结算,1=已结算,2=已取消,3=失败,4=转账处理中/待确认,5=无需打款',
  `settle_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '结算时间',
  `settle_way` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '结算方式：3=微信，5=无需打款',
  `transaction_id` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '交易号',
  `fail_reason` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '失败原因',
  `remark` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_settlement_sn` (`settlement_sn`),
  UNIQUE KEY `uk_order_item_id` (`order_item_id`),
  KEY `idx_batch_id` (`batch_id`),
  KEY `idx_staff_id` (`staff_id`),
  KEY `idx_team_id` (`team_id`),
  KEY `idx_leader_staff_id` (`leader_staff_id`),
  KEY `idx_config_id` (`config_id`),
  KEY `idx_settlement_mode` (`settlement_mode`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_status` (`status`),
  KEY `idx_due_collect_status` (`staff_due_collect_status`),
  KEY `idx_service_date` (`service_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务人员结算表';
CREATE TABLE `la_staff_settlement_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `scope_type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '适用范围：1=全员默认,2=队伍,3=人员',
  `staff_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '服务人员ID',
  `team_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '服务队伍ID',
  `category_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '服务分类ID(兼容旧字段)',
  `settlement_mode` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '结算模式：1=比例抽成,2=包月金额',
  `settlement_rate` decimal(5,2) NOT NULL DEFAULT '70.00' COMMENT '队员结算比例(%)',
  `company_rate` decimal(5,2) NOT NULL DEFAULT '30.00' COMMENT '公司抽成比例(%)',
  `leader_rate` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '队长抽成比例(%)',
  `monthly_fee` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '包月金额',
  `min_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '最低结算金额',
  `settle_cycle` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '结算周期：1=月结,2=周结,3=单笔结',
  `settle_delay_days` int(10) unsigned NOT NULL DEFAULT '7' COMMENT '结算延迟天数',
  `is_default` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否默认配置',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态：0=禁用,1=启用',
  `remark` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_scope_type` (`scope_type`),
  KEY `idx_staff_id` (`staff_id`),
  KEY `idx_team_id` (`team_id`),
  KEY `idx_settlement_mode` (`settlement_mode`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_is_default` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务人员结算配置表';
INSERT INTO `la_staff_settlement_config` (`id`,`scope_type`,`staff_id`,`team_id`,`category_id`,`settlement_mode`,`settlement_rate`,`company_rate`,`leader_rate`,`monthly_fee`,`min_amount`,`settle_cycle`,`settle_delay_days`,`is_default`,`status`,`remark`,`create_time`,`update_time`,`delete_time`) VALUES ('1','1','0','0','0','1','70.00','30.00','0.00','0.00','0.00','1','7','1','1','默认结算配置：队员结算70%，公司抽成30%，队长抽成0','1773413106','1773413106',NULL);
CREATE TABLE `la_staff_settlement_repay` (
  `is_compensation` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '异常实收待补偿',
  `refund_sn` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '补偿退款单号',
  `refund_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '0无退款 1待发起 2处理中 3已退 4异常',
  `refund_query_time` int(10) unsigned NOT NULL DEFAULT '0',
  `refund_transaction_id` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `query_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后支付查询时间',
  `closed_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '微信关单确认时间',
  `expire_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '支付过期时间',
  `repay_sn` varchar(32) COLLATE utf8mb4_general_ci NOT NULL COMMENT '补收编号',
  `settlement_id` int(10) unsigned NOT NULL COMMENT '结算记录ID',
  `settlement_sn` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '结算编号',
  `staff_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '服务人员ID',
  `order_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '订单ID',
  `order_item_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '订单项ID',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '补收金额',
  `collect_way` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '补收方式：1=微信支付',
  `pay_way` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '线上支付方式：0=无，2=微信',
  `pay_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '支付状态：0=待支付,1=已支付,2=支付失败,3=已取消',
  `pay_sn` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '第三方支付商户单号',
  `transaction_id` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '第三方交易号或线下补入编号',
  `callback_data` text COLLATE utf8mb4_general_ci COMMENT '支付回调数据',
  `fail_reason` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '失败原因',
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '后台操作管理员ID',
  `remark` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `pay_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '支付或补入时间',
  `callback_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '回调时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_repay_sn` (`repay_sn`),
  UNIQUE KEY `uk_refund_sn` (`refund_sn`),
  UNIQUE KEY `uk_transaction_id` (`transaction_id`),
  KEY `idx_settlement_id` (`settlement_id`),
  KEY `idx_staff_id` (`staff_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_pay_sn` (`pay_sn`),
  KEY `idx_pay_status` (`pay_status`),
  KEY `idx_collect_way` (`collect_way`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务人员补交平台抽成记录表';
CREATE TABLE `la_staff_settlement_transfer` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `settlement_id` int(10) unsigned NOT NULL COMMENT '结算记录ID',
  `settlement_sn` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '结算编号',
  `staff_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '服务人员ID',
  `order_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '订单ID',
  `order_item_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '订单项ID',
  `out_bill_no` varchar(32) COLLATE utf8mb4_general_ci NOT NULL COMMENT '商户转账单号',
  `transfer_bill_no` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '微信转账单号',
  `mch_id` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '微信商户号',
  `appid` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '小程序AppID',
  `openid` varchar(128) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '收款openid',
  `user_name` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '收款实名姓名',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '转账金额',
  `amount_fen` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '转账金额分',
  `transfer_scene_id` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '转账场景ID',
  `transfer_remark` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '转账备注',
  `user_recv_perception` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '到账感知文案',
  `package_info` varchar(512) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '确认收款package_info',
  `wx_state` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '微信转账状态',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '状态：0=待发起,1=转账中,2=待用户确认,3=已到账,4=失败,5=已关闭',
  `request_data` text COLLATE utf8mb4_general_ci COMMENT '请求数据',
  `response_data` text COLLATE utf8mb4_general_ci COMMENT '发起响应',
  `query_response` text COLLATE utf8mb4_general_ci COMMENT '查询响应',
  `notify_data` text COLLATE utf8mb4_general_ci COMMENT '回调数据',
  `fail_reason` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '失败原因',
  `retry_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '重试次数',
  `send_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发起时间',
  `success_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '到账时间',
  `close_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关闭时间',
  `last_query_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后查询时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_out_bill_no` (`out_bill_no`),
  KEY `idx_settlement_id` (`settlement_id`),
  KEY `idx_staff_id` (`staff_id`),
  KEY `idx_transfer_bill_no` (`transfer_bill_no`),
  KEY `idx_status_query` (`status`,`last_query_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务人员结算转账明细表';
CREATE TABLE `la_staff_tag` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `staff_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '工作人员ID',
  `tag_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '标签ID',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_staff_tag` (`staff_id`,`tag_id`) USING BTREE,
  KEY `idx_tag_id` (`tag_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='工作人员标签关联表';
CREATE TABLE `la_staff_tag_apply` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '申请ID',
  `staff_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '服务人员ID',
  `current_tag_ids` text COLLATE utf8mb4_general_ci COMMENT '当前生效标签ID(JSON数组)',
  `apply_tag_ids` text COLLATE utf8mb4_general_ci COMMENT '申请标签ID(JSON数组)',
  `source` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '来源:1-uniapp,2-后台自助',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态:0-待审核,1-已通过,2-已拒绝',
  `reject_reason` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '拒绝原因',
  `submit_user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '提交用户ID',
  `submit_admin_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '提交后台账号ID',
  `audit_admin_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '审核管理员ID',
  `audit_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '审核时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_staff_id` (`staff_id`) USING BTREE,
  KEY `idx_status` (`status`) USING BTREE,
  KEY `idx_source` (`source`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务人员标签变更申请表';
CREATE TABLE `la_staff_team` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '队伍名称',
  `leader_staff_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '队长服务人员ID',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态：0=禁用,1=启用',
  `sort` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `remark` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_leader_staff_id` (`leader_staff_id`),
  KEY `idx_status_sort` (`status`,`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务队伍表';
CREATE TABLE `la_staff_team_member` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `team_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '队伍ID',
  `staff_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '队员服务人员ID',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态：0=禁用,1=启用',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_staff_active` (`staff_id`,`delete_time`),
  KEY `idx_team_id` (`team_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务队伍成员表';
CREATE TABLE `la_staff_work` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '作品ID',
  `staff_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '工作人员ID',
  `title` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '作品标题',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '作品类型:1-图片,2-视频',
  `cover` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '封面图片',
  `images` text COLLATE utf8mb4_general_ci COMMENT '作品图片(JSON数组)',
  `video` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '作品视频',
  `description` text COLLATE utf8mb4_general_ci COMMENT '作品描述',
  `shoot_date` date DEFAULT NULL COMMENT '拍摄日期',
  `location` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '拍摄地点',
  `view_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '浏览数',
  `like_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '点赞数',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `is_show` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示:0-否,1-是',
  `is_cover` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否封面:0-否,1-是',
  `audit_status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '审核状态:0-待审核,1-已通过,2-已拒绝',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_staff_id` (`staff_id`) USING BTREE,
  KEY `idx_audit_status` (`audit_status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='工作人员作品表';
CREATE TABLE `la_style_tag` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '标签ID',
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '标签名称',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '标签类型:1-风格,2-特长,3-其他',
  `category_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '服务分类ID',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `is_show` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示:0-否,1-是',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_type` (`type`) USING BTREE,
  KEY `idx_category_id` (`category_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='风格标签表';
CREATE TABLE `la_system_menu` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '上级菜单',
  `type` char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '权限类型: M=目录，C=菜单，A=按钮',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '菜单名称',
  `icon` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '菜单图标',
  `sort` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '菜单排序',
  `perms` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '权限标识',
  `paths` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '路由地址',
  `component` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '前端组件',
  `selected` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '选中路径',
  `params` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '路由参数',
  `is_cache` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否缓存: 0=否, 1=是',
  `is_show` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示: 0=否, 1=是',
  `is_disable` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否禁用: 0=否, 1=是',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='系统菜单表';
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('4','0','M','权限管理','el-icon-Lock','300','','permission','','','','0','1','0','1656664556','1710472802');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('5','0','C','工作台','el-icon-Monitor','1000','workbench/index','workbench','workbench/index','','','0','1','0','1656664793','1664354981');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('6','4','C','菜单','el-icon-Operation','100','auth.menu/lists','menu','permission/menu/index','','','1','1','0','1656664960','1710472994');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('7','4','C','管理员','local-icon-shouyiren','80','auth.admin/lists','admin','permission/admin/index','','','0','1','0','1656901567','1710473013');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('8','4','C','角色','el-icon-Female','90','auth.role/lists','role','permission/role/index','','','0','1','0','1656901660','1710473000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('12','8','A','新增','','1','auth.role/add','','','','','0','1','0','1657001790','1663750625');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('14','8','A','编辑','','1','auth.role/edit','','','','','0','1','0','1657001924','1663750631');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('15','8','A','删除','','1','auth.role/delete','','','','','0','1','0','1657001982','1663750637');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('16','6','A','新增','','1','auth.menu/add','','','','','0','1','0','1657072523','1663750565');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('17','6','A','编辑','','1','auth.menu/edit','','','','','0','1','0','1657073955','1663750570');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('18','6','A','删除','','1','auth.menu/delete','','','','','0','1','0','1657073987','1663750578');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('19','7','A','新增','','1','auth.admin/add','','','','','0','1','0','1657074035','1663750596');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('20','7','A','编辑','','1','auth.admin/edit','','','','','0','1','0','1657074071','1663750603');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('21','7','A','删除','','1','auth.admin/delete','','','','','0','1','0','1657074108','1663750609');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('23','28','M','开发工具','el-icon-EditPen','40','','dev_tools','','','','0','1','0','1657097744','1710473127');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('24','23','C','代码生成器','el-icon-DocumentAdd','1','tools.generator/generateTable','code','dev_tools/code/index','','','0','1','0','1657098110','1658989423');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('25','0','M','组织管理','el-icon-OfficeBuilding','400','','organization','','','','0','1','0','1657099914','1710472797');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('26','25','C','部门管理','el-icon-Coordinate','100','dept.dept/lists','department','organization/department/index','','','1','1','0','1657099989','1710472962');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('27','25','C','岗位管理','el-icon-PriceTag','90','dept.jobs/lists','post','organization/post/index','','','0','1','0','1657100044','1710472967');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('28','0','M','系统设置','el-icon-Setting','200','','setting','','','','0','1','0','1657100164','1710472807');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('29','28','M','网站设置','el-icon-Basketball','100','','website','','','','0','1','0','1657100230','1710473049');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('30','29','C','网站信息','','1','setting.web.web_setting/getWebsite','information','setting/website/information','','','0','1','0','1657100306','1657164412');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('31','29','C','网站备案','','1','setting.web.web_setting/getCopyright','filing','setting/website/filing','','','0','1','0','1657100434','1657164723');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('32','29','C','政策协议','','1','setting.web.web_setting/getAgreement','protocol','setting/website/protocol','','','0','1','0','1657100571','1657164770');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('33','28','C','存储设置','el-icon-FolderOpened','70','setting.storage/lists','storage','setting/storage/index','','','0','1','0','1657160959','1710473095');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('34','23','C','字典管理','el-icon-Box','1','setting.dict.dict_type/lists','dict','setting/dict/type/index','','','0','1','0','1657161211','1663225935');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('35','28','M','系统维护','el-icon-SetUp','50','','system','','','','0','1','0','1657161569','1710473122');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('36','35','C','系统日志','','90','setting.system.log/lists','journal','setting/system/journal','','','0','1','0','1657161696','1710473253');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('37','35','C','系统缓存','','80','','cache','setting/system/cache','','','0','1','0','1657161896','1710473258');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('38','35','C','系统环境','','70','setting.system.system/info','environment','setting/system/environment','','','0','1','0','1657162000','1710473265');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('39','24','A','导入数据表','','1','tools.generator/selectTable','','','','','0','1','0','1657162736','1657162736');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('40','24','A','代码生成','','1','tools.generator/generate','','','','','0','1','0','1657162806','1657162806');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('41','23','C','编辑数据表','','1','tools.generator/edit','code/edit','dev_tools/code/edit','/dev_tools/code','','1','0','0','1657162866','1663748668');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('42','24','A','同步表结构','','1','tools.generator/syncColumn','','','','','0','1','0','1657162934','1657162934');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('43','24','A','删除数据表','','1','tools.generator/delete','','','','','0','1','0','1657163015','1657163015');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('44','24','A','预览代码','','1','tools.generator/preview','','','','','0','1','0','1657163263','1657163263');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('45','26','A','新增','','1','dept.dept/add','','','','','0','1','0','1657163548','1663750492');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('46','26','A','编辑','','1','dept.dept/edit','','','','','0','1','0','1657163599','1663750498');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('47','26','A','删除','','1','dept.dept/delete','','','','','0','1','0','1657163687','1663750504');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('48','27','A','新增','','1','dept.jobs/add','','','','','0','1','0','1657163778','1663750524');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('49','27','A','编辑','','1','dept.jobs/edit','','','','','0','1','0','1657163800','1663750530');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('50','27','A','删除','','1','dept.jobs/delete','','','','','0','1','0','1657163820','1663750535');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('51','30','A','保存','','1','setting.web.web_setting/setWebsite','','','','','0','1','0','1657164469','1663750649');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('52','31','A','保存','','1','setting.web.web_setting/setCopyright','','','','','0','1','0','1657164692','1663750657');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('53','32','A','保存','','1','setting.web.web_setting/setAgreement','','','','','0','1','0','1657164824','1663750665');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('54','33','A','设置','','1','setting.storage/setup','','','','','0','1','0','1657165303','1663750673');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('55','34','A','新增','','1','setting.dict.dict_type/add','','','','','0','1','0','1657166966','1663750783');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('56','34','A','编辑','','1','setting.dict.dict_type/edit','','','','','0','1','0','1657166997','1663750789');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('57','34','A','删除','','1','setting.dict.dict_type/delete','','','','','0','1','0','1657167038','1663750796');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('58','62','A','新增','','1','setting.dict.dict_data/add','','','','','0','1','0','1657167317','1663750758');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('59','62','A','编辑','','1','setting.dict.dict_data/edit','','','','','0','1','0','1657167371','1663750751');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('60','62','A','删除','','1','setting.dict.dict_data/delete','','','','','0','1','0','1657167397','1663750768');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('61','37','A','清除系统缓存','','1','setting.system.cache/clear','','','','','0','1','0','1657173837','1657173939');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('62','23','C','字典数据管理','','1','setting.dict.dict_data/lists','dict/data','setting/dict/data/index','/dev_tools/dict','','1','0','0','1657174351','1663745617');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('63','158','M','素材管理','el-icon-Picture','0','','material','','','','0','1','0','1657507133','1710472243');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('64','63','C','素材中心','el-icon-PictureRounded','0','','index','material/index','','','0','1','0','1657507296','1664355653');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('66','26','A','详情','','0','dept.dept/detail','','','','','0','1','0','1663725459','1663750516');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('67','27','A','详情','','0','dept.jobs/detail','','','','','0','1','0','1663725514','1663750559');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('68','6','A','详情','','0','auth.menu/detail','','','','','0','1','0','1663725564','1663750584');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('69','7','A','详情','','0','auth.admin/detail','','','','','0','1','0','1663725623','1663750615');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('70','158','M','文章资讯','el-icon-ChatLineSquare','90','','article','','','','0','1','0','1663749965','1710471867');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('71','70','C','文章管理','el-icon-ChatDotSquare','0','article.article/lists','lists','article/lists/index','','','0','1','0','1663750101','1664354615');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('72','70','C','文章添加/编辑','','0','article.article/add:edit','lists/edit','article/lists/edit','/article/lists','','0','0','0','1663750153','1664356275');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('73','70','C','文章栏目','el-icon-CollectionTag','0','article.articleCate/lists','column','article/column/index','','','1','1','0','1663750287','1664354678');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('74','71','A','新增','','0','article.article/add','','','','','0','1','0','1663750335','1663750335');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('75','71','A','详情','','0','article.article/detail','','','','','0','1','0','1663750354','1663750383');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('76','71','A','删除','','0','article.article/delete','','','','','0','1','0','1663750413','1663750413');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('77','71','A','修改状态','','0','article.article/updateStatus','','','','','0','1','0','1663750442','1663750442');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('78','73','A','添加','','0','article.articleCate/add','','','','','0','1','0','1663750483','1663750483');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('79','73','A','删除','','0','article.articleCate/delete','','','','','0','1','0','1663750895','1663750895');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('80','73','A','详情','','0','article.articleCate/detail','','','','','0','1','0','1663750913','1663750913');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('81','73','A','修改状态','','0','article.articleCate/updateStatus','','','','','0','1','0','1663750936','1663750936');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('82','0','M','渠道设置','el-icon-Message','500','','channel','','','','0','1','0','1663754084','1710472649');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('85','82','M','微信公众号','local-icon-dingdan','80','','wx_oa','','','','0','1','0','1663755470','1710472946');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('86','85','C','公众号配置','','0','channel.official_account_setting/getConfig','config','channel/wx_oa/config','','','0','1','0','1663755663','1664355450');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('87','85','C','菜单管理','','0','channel.official_account_menu/detail','menu','channel/wx_oa/menu','','','0','1','0','1663755767','1664355456');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('88','86','A','保存','','0','channel.official_account_setting/setConfig','','','','','0','1','0','1663755799','1663755799');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('89','86','A','保存并发布','','0','channel.official_account_menu/save','','','','','0','1','0','1663756490','1663756490');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('90','85','C','关注回复','','0','channel.official_account_reply/lists','follow','channel/wx_oa/reply/follow_reply','','','0','1','0','1663818358','1663818366');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('91','85','C','关键字回复','','0','','keyword','channel/wx_oa/reply/keyword_reply','','','0','1','0','1663818445','1663818445');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('93','85','C','默认回复','','0','','default','channel/wx_oa/reply/default_reply','','','0','1','0','1663818580','1663818580');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('94','82','C','微信小程序','local-icon-weixin','90','channel.mnp_settings/getConfig','weapp','channel/weapp','','','0','1','0','1663831396','1710472941');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('95','94','A','保存','','0','channel.mnp_settings/setConfig','','','','','0','1','0','1663831436','1663831436');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('96','0','M','装修管理','el-icon-Brush','600','','decoration','','','','0','1','0','1663834825','1710472099');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('97','175','C','页面装修','el-icon-CopyDocument','100','decorate.page/detail','pages','decoration/pages/index','','','0','1','0','1663834879','1710929256');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('98','97','A','保存','','0','decorate.page/save','','','','','0','1','0','1663834956','1663834956');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('99','175','C','底部导航','el-icon-Position','90','decorate.tabbar/detail','tabbar','decoration/tabbar','','','0','1','0','1663835004','1710929262');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('100','99','A','保存','','0','decorate.tabbar/save','','','','','0','1','0','1663835018','1663835018');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('101','158','M','消息管理','el-icon-ChatDotRound','80','','message','','','','0','1','0','1663838602','1710471874');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('102','101','C','通知设置','','0','notice.notice/settingLists','notice','message/notice/index','','','0','1','0','1663839195','1663839195');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('103','102','A','详情','','0','notice.notice/detail','','','','','0','1','0','1663839537','1663839537');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('104','101','C','通知设置编辑','','0','notice.notice/set','notice/edit','message/notice/edit','/message/notice','','0','0','0','1663839873','1663898477');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('105','71','A','编辑','','0','article.article/edit','','','','','0','1','0','1663840043','1663840053');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('107','101','C','短信设置','','0','notice.sms_config/getConfig','short_letter','message/short_letter/index','','','0','1','0','1663898591','1664355708');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('108','107','A','设置','','0','notice.sms_config/setConfig','','','','','0','1','0','1663898644','1663898644');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('109','107','A','详情','','0','notice.sms_config/detail','','','','','0','1','0','1663898661','1663898661');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('110','28','C','热门搜索','el-icon-Search','60','setting.hot_search/getConfig','search','setting/search/index','','','0','1','0','1663901821','1710473109');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('111','110','A','保存','','0','setting.hot_search/setConfig','','','','','0','1','0','1663901856','1663901856');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('112','28','M','用户设置','local-icon-keziyuyue','90','','user','','','','0','1','0','1663903302','1710473056');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('113','112','C','用户设置','','0','setting.user.user/getConfig','setup','setting/user/setup','','','0','1','0','1663903506','1663903506');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('114','113','A','保存','','0','setting.user.user/setConfig','','','','','0','1','0','1663903522','1663903522');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('115','112','C','登录注册','','0','setting.user.user/getRegisterConfig','login_register','setting/user/login_register','','','0','1','0','1663903832','1663903832');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('116','115','A','保存','','0','setting.user.user/setRegisterConfig','','','','','0','1','0','1663903852','1663903852');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('117','0','M','用户管理','el-icon-User','900','','consumer','','','','0','1','0','1663904351','1710472074');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('118','117','C','用户列表','local-icon-user_guanli','100','user.user/lists','lists','consumer/lists/index','','','0','1','0','1663904392','1710471845');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('119','117','C','用户详情','','90','user.user/detail','lists/detail','consumer/lists/detail','/consumer/lists','','0','0','0','1663904470','1710471851');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('120','119','A','编辑','','0','user.user/edit','','','','','0','1','0','1663904499','1663904499');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('142','176','C','PC端装修','el-icon-Monitor','8','','pc','decoration/pc','','','0','1','0','1668423284','1710901602');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('143','35','C','定时任务','','100','crontab.crontab/lists','scheduled_task','setting/system/scheduled_task/index','','','0','1','0','1669357509','1710473246');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('144','35','C','定时任务添加/编辑','','0','crontab.crontab/add:edit','scheduled_task/edit','setting/system/scheduled_task/edit','/setting/system/scheduled_task','','0','0','0','1669357670','1669357765');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('145','143','A','添加','','0','crontab.crontab/add','','','','','0','1','0','1669358282','1669358282');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('146','143','A','编辑','','0','crontab.crontab/edit','','','','','0','1','0','1669358303','1669358303');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('147','143','A','删除','','0','crontab.crontab/delete','','','','','0','1','0','1669358334','1669358334');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('158','0','M','应用管理','el-icon-Postcard','800','','app','','','','0','1','0','1677143430','1710472079');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('161','28','M','支付设置','local-icon-set_pay','80','','pay','','','','0','1','0','1677148075','1710473061');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('162','161','C','支付方式','','0','setting.pay.pay_way/getPayWay','method','setting/pay/method/index','','','0','1','0','1677148207','1677148207');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('163','161','C','支付配置','','0','setting.pay.pay_config/lists','config','setting/pay/config/index','','','0','1','0','1677148260','1677148374');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('164','162','A','设置支付方式','','0','setting.pay.pay_way/setPayWay','','','','','0','1','0','1677219624','1677219624');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('165','163','A','配置','','0','setting.pay.pay_config/setConfig','','','','','0','1','0','1677219655','1677219655');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('173','175','C','系统风格','el-icon-Brush','80','','style','decoration/style/style','','','0','0','1','1681635044','1710929278');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('175','96','M','移动端','','100','','mobile','','','','0','1','0','1710901543','1710929294');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('176','96','M','PC端','','90','','pc','','','','0','1','0','1710901592','1710929299');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('177','29','C','站点统计','','0','setting.web.web_setting/getSiteStatistics','statistics','setting/website/statistics','','','0','1','0','1726841481','1726843434');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('178','177','A','保存','','0','setting.web.web_setting/saveSiteStatistics','','','','','1','1','0','1726841507','1726841507');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('179','0','M','服务管理','el-icon-User','900','','service','','','','0','1','0','1773413107','1773413107');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('180','179','C','服务人员','','100','ops.staff/lists','staff','staff/lists/index','','','0','1','0','1773413107','1773413107');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('181','179','C','服务分类','','95','ops.category/lists','category','service/category/index','','','0','1','0','1773413107','1773413107');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('182','179','C','服务套餐','','90','ops.package/lists','package','service/package/index','','','0','1','0','1773413107','1773413107');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('183','0','M','档期管理','el-icon-Calendar','850','','schedule','','','','0','1','0','1773413107','1773413107');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('184','183','C','档期日历','','100','ops.schedule/lists','calendar','schedule/calendar/index','','','0','1','0','1773413107','1773413107');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('186','183','C','候补列表','','80','ops.waitlist/lists','waitlist','schedule/waitlist/index','','','0','1','0','1773413107','1773413107');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('187','0','M','订单管理','el-icon-Document','800','','order','','','','0','1','0','1773413107','1773413107');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('188','187','C','订单列表','','100','ops.order/lists','lists','order/lists/index','','','0','1','0','1773413107','1773413107');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('189','187','C','退款管理','','90','ops.refund/lists','refund','order/refund/index','','','0','1','0','1773413107','1773413107');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('190','187','C','订单变更','','80','ops.orderChange/lists','change','order/change/index','','','0','1','0','1773413107','1773413107');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('193','0','M','动态社区','el-icon-ChatDotRound','750','','dynamic','','','','0','1','0','1773413107','1773413107');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('194','193','C','动态列表','','100','growth.dynamic/lists','lists','dynamic/lists/index','','','0','1','0','1773413107','1773413107');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('195','0','M','评价管理','el-icon-Star','700','','review','','','','0','1','0','1773413107','1773413107');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('196','195','C','评价列表','','100','growth.review/lists','lists','review/lists/index','','','0','1','0','1773413107','1773413107');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('197','0','M','财务管理','el-icon-Coin','650','','financial','','','','0','1','0','1773413107','1773413107');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('198','197','C','资金流水','','100','finance.flow/lists','flow','financial/flow/index','','','0','1','0','1773413107','1773413107');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('199','197','C','结算管理','','80','finance.settlement/lists','settlement','financial/settlement/index','','','0','1','0','1773413107','1773413107');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('206','0','M','售后服务','el-icon-Service','550','','aftersale','','','','0','1','0','1773413107','1773556013');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('207','206','C','售后管理','','100','ops.aftersaleTicket/ticketLists','ticket','aftersale/ticket/index','','','0','1','0','1773413107','1773556013');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('210','0','M','消息中心','el-icon-Bell','450','','message','','','','0','1','0','1773413107','1773413107');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('211','210','C','消息通知','','100','growth.notification/lists','notification','notification/lists/index','','','0','1','0','1773413107','1773413107');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('215','179','C','服务人员添加/编辑','','99','ops.staff/add:edit','staff/edit','staff/lists/edit','/service/staff','','0','0','0','1773413108','1773413108');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('216','193','C','评论审核','','90','growth.dynamicComment/reviewList','comment/review','dynamic/comment/review','','','0','1','0','1773413108','1773413108');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('218','179','C','服务人员标签','','70','ops.styleTag/lists','tag','service/tag/index','','','0','1','0','1773413108','1773413108');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('219','218','A','新增','','0','ops.styleTag/add','','','','','0','0','0','1773413108','1773413108');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('220','218','A','编辑','','0','ops.styleTag/edit','','','','','0','0','0','1773413108','1773413108');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('221','218','A','删除','','0','ops.styleTag/delete','','','','','0','0','0','1773413108','1773413108');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('222','218','A','状态切换','','0','ops.styleTag/changeStatus','','','','','0','0','0','1773413108','1773413108');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('223','0','M','服务人员中心','el-icon-User','720','','staff-center','','','','0','1','0','1773413108','1773413108');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('224','223','C','基本资料','','100','ops.staff/myProfile','profile','staff_center/profile/index','','','0','1','0','1773413108','1773413108');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('225','223','C','我的档期','','80','ops.schedule/myCalendar','calendar','staff_center/calendar/index','','','0','1','0','1773413108','1773413108');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('226','223','C','接单规则','','70','ops.scheduleRule/myRules','rule','staff_center/rule/index','','','0','1','0','1773413108','1773413108');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('227','223','C','预约确认','','60','ops.booking/myBookings','booking','staff_center/booking/index','','','0','0','0','1773413108','1773556013');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('228','223','C','候补需求','','50','ops.waitlist/myWaitlist','waitlist','staff_center/waitlist/index','','','0','0','0','1773413108','1773413108');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('229','223','C','履约订单','','40','ops.order/myOrders','order','staff_center/order/index','','','0','1','0','1773413108','1773413108');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('230','223','C','内容发布','','30','growth.dynamic/myDynamics','dynamic','staff_center/dynamic/index','','','0','1','0','1773413108','1773413108');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('231','0','M','作品管理','el-icon-Picture','730','','staff_work','','','','0','1','0','1773413108','1773413108');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('232','231','C','作品列表','','100','ops.work/lists','lists','staff/work/index','','','0','1','0','1773413108','1773413108');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('233','232','A','详情','','0','ops.work/detail','','','','','0','1','0','1773413108','1773413108');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('234','232','A','审核','','0','ops.work/audit','','','','','0','1','0','1773413108','1773413108');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('235','232','A','修改状态','','0','ops.work/changeStatus','','','','','0','1','0','1773413108','1773413108');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('236','232','A','设为封面','','0','ops.work/setCover','','','','','0','1','0','1773413108','1773413108');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('237','232','A','删除','','0','ops.work/delete','','','','','0','1','0','1773413108','1773413108');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('238','28','C','功能开关','el-icon-Switch','55','setting.feature_switch/getConfig','feature_switch','setting/feature_switch/index','','','0','1','0','1773413108','1773413108');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('239','238','A','保存','','0','setting.feature_switch/setConfig','','','','','0','1','0','1773413108','1773413108');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('240','179','C','服务地区','','85','ops.region/lists','region','service/region/index','','','0','1','0','1773650000','1773650000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('241','240','A','城市选项','','0','ops.region/cityOptions','','','','','0','0','0','1773650000','1773650000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('242','240','A','启用城市选项','','0','ops.region/enabledCityOptions','','','','','0','0','0','1773650000','1773650000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('243','240','A','区县选项','','0','ops.region/districtOptions','','','','','0','0','0','1773650000','1773650000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('244','240','A','新增','','0','ops.region/add','','','','','0','0','0','1773650000','1773650000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('245','240','A','编辑','','0','ops.region/edit','','','','','0','0','0','1773650000','1773650000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('246','240','A','删除','','0','ops.region/delete','','','','','0','0','0','1773650000','1773650000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('247','240','A','状态切换','','0','ops.region/changeStatus','','','','','0','0','0','1773650000','1773650000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('255','179','C','标签审核','','195','ops.staffTagReview/lists','staff-tag-review','staff/tag_review/index','','','0','1','0','1775001600','1775001600');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('256','255','A','详情','','10','ops.staffTagReview/detail','','','','','0','0','0','1775001600','1775001600');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('257','255','A','审核通过','','20','ops.staffTagReview/approve','','','','','0','0','0','1775001600','1775001600');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('258','255','A','审核拒绝','','30','ops.staffTagReview/reject','','','','','0','0','0','1775001600','1775001600');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('282','179','C','人员证书','','185','ops.staffCertificate/lists','certificate','staff/certificate/index','','','0','1','0','1775923200','1775923200');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('283','282','A','详情','','10','ops.staffCertificate/detail','','','','','0','0','0','1775923200','1775923200');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('284','282','A','新增','','20','ops.staffCertificate/add','','','','','0','0','0','1775923200','1775923200');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('285','282','A','编辑','','30','ops.staffCertificate/edit','','','','','0','0','0','1775923200','1775923200');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('286','282','A','删除','','40','ops.staffCertificate/delete','','','','','0','0','0','1775923200','1775923200');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('287','282','A','审核','','50','ops.staffCertificate/audit','','','','','0','0','0','1775923200','1775923200');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('288','223','C','服务展示','','95','ops.staff/myProfileUpdate','showcase','staff_center/showcase/index','','','0','1','0','1776000000','1776000000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('289','223','C','我的套餐','','90','ops.staff/myProfilePackageConfig','package','staff_center/package/index','','','0','1','0','1776000000','1776000000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('290','238','A','获取订单超时设置','','0','setting.transaction_settings/getConfig','','','','','0','1','0','1776200000','1776200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('291','238','A','保存订单超时设置','','0','setting.transaction_settings/setConfig','','','','','0','1','0','1776200000','1776200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('295','183','C','接单规则','','90','ops.scheduleRule/lists','rule','schedule/rule/index','','','0','1','0','1776000100','1776000100');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('296','295','A','新增','','40','ops.scheduleRule/add','','','','','0','0','0','1776000100','1776000100');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('297','295','A','编辑','','30','ops.scheduleRule/edit','','','','','0','0','0','1776000100','1776000100');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('298','295','A','删除','','20','ops.scheduleRule/delete','','','','','0','0','0','1776000100','1776000100');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('299','295','A','状态切换','','10','ops.scheduleRule/changeStatus','','','','','0','0','0','1776000100','1776000100');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('301','0','M','CRM管理','el-icon-User','620','','crm','','','','0','1','0','1776300000','1776300000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('302','301','C','顾问管理','','90','crm.salesAdvisor/lists','advisor','crm/advisor/index','','','0','1','0','1776300000','1776300000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('303','302','A','新增','','40','crm.salesAdvisor/add','','','','','0','0','0','1776300000','1776300000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('304','302','A','编辑','','30','crm.salesAdvisor/edit','','','','','0','0','0','1776300000','1776300000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('305','302','A','删除','','20','crm.salesAdvisor/delete','','','','','0','0','0','1776300000','1776300000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('306','302','A','状态切换','','10','crm.salesAdvisor/changeStatus','','','','','0','0','0','1776300000','1776300000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('307','302','A','客户数校准','','9','crm.salesAdvisor/syncCustomerCount','','','','','0','0','0','1776300000','1776300000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('308','301','C','客户管理','','100','crm.customer/lists','customer','crm/customer/index','','','0','1','0','1776300000','1776300000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('309','308','A','详情','','40','crm.customer/detail','','','','','0','0','0','1776300000','1776300000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('310','308','A','编辑','','30','crm.customer/edit','','','','','0','0','0','1776300000','1776300000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('311','308','A','转移顾问','','20','crm.customer/transferAdvisor','','','','','0','0','0','1776300000','1776300000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('312','308','A','顾问选项','','10','crm.customer/advisorOptions','','','','','0','0','0','1776300000','1776300000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('313','308','A','客户选项','','9','crm.customer/options','','','','','0','0','0','1776300000','1776300000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('314','301','C','跟进记录','','80','crm.followRecord/lists','follow-record','crm/follow-record/index','','','0','1','0','1776300000','1776300000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('315','314','A','详情','','30','crm.followRecord/detail','','','','','0','0','0','1776300000','1776300000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('316','314','A','新增','','20','crm.followRecord/add','','','','','0','0','0','1776300000','1776300000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('317','314','A','选项','','10','crm.followRecord/options','','','','','0','0','0','1776300000','1776300000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('318','314','A','客户选项','','9','crm.followRecord/customerOptions','','','','','0','0','0','1776300000','1776300000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('319','301','C','流失预警','','70','crm.lossWarning/lists','loss-warning','crm/loss-warning/index','','','0','1','0','1776300000','1776300000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('320','319','A','详情','','60','crm.lossWarning/detail','','','','','0','0','0','1776300000','1776300000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('321','319','A','处理','','50','crm.lossWarning/handle','','','','','0','0','0','1776300000','1776300000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('322','319','A','忽略','','40','crm.lossWarning/ignore','','','','','0','0','0','1776300000','1776300000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('323','319','A','生成预警','','30','crm.lossWarning/generate','','','','','0','0','0','1776300000','1776300000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('324','319','A','推送预警','','20','crm.lossWarning/push','','','','','0','0','0','1776300000','1776300000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('325','319','A','统计','','10','crm.lossWarning/stats','','','','','0','0','0','1776300000','1776300000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('326','319','A','选项','','9','crm.lossWarning/options','','','','','0','0','0','1776300000','1776300000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('327','28','C','海报字体资源','el-icon-DocumentChecked','52','setting.order_confirm_letter/fontLists','order_confirm_letter','setting/order_confirm_letter/index','','','0','1','0','1776400000','1776400000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('328','327','A','保存设置','','0','setting.order_confirm_letter/setConfig','','','','','0','0','0','1776400000','1776400000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('329','327','A','字体列表','','0','setting.order_confirm_letter/fontLists','','','','','0','0','0','1776400000','1776400000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('330','327','A','上传字体','','0','setting.order_confirm_letter/uploadFont','','','','','0','0','0','1776400000','1776400000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('331','327','A','设置字体','','0','setting.order_confirm_letter/setFont','','','','','0','0','0','1776400000','1776400000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('332','327','A','删除字体','','0','setting.order_confirm_letter/deleteFont','','','','','0','0','0','1776400000','1776400000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('333','327','A','检测字体','','0','setting.order_confirm_letter/checkFont','','','','','0','0','0','1776400000','1776400000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('334','207','A','售后统计','','0','ops.aftersaleTicket/statistics','','','','','0','0','0','1776500000','1776500000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('335','207','A','趋势数据','','0','ops.aftersaleTicket/trend','','','','','0','0','0','1776500000','1776500000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('336','207','A','工单详情','','0','ops.aftersaleTicket/ticketDetail','','','','','0','0','0','1776500000','1776500000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('337','207','A','创建工单','','0','ops.aftersaleTicket/createTicket','','','','','0','0','0','1776500000','1776500000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('338','207','A','分配工单','','0','ops.aftersaleTicket/assignTicket','','','','','0','0','0','1776500000','1776500000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('339','207','A','处理工单','','0','ops.aftersaleTicket/handleTicket','','','','','0','0','0','1776500000','1776500000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('340','207','A','关闭工单','','0','ops.aftersaleTicket/closeTicket','','','','','0','0','0','1776500000','1776500000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('341','207','A','升级工单','','0','ops.aftersaleTicket/escalateTicket','','','','','0','0','0','1776500000','1776500000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('342','207','A','工单日志','','0','ops.aftersaleTicket/ticketLogs','','','','','0','0','0','1776500000','1776500000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('343','207','A','投诉列表','','0','ops.aftersaleTicket/complaintLists','','','','','0','0','0','1776500000','1776500000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('344','207','A','投诉详情','','0','ops.aftersaleTicket/complaintDetail','','','','','0','0','0','1776500000','1776500000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('345','207','A','处理投诉','','0','ops.aftersaleTicket/handleComplaint','','','','','0','0','0','1776500000','1776500000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('346','207','A','回访列表','','0','ops.aftersaleTicket/callbackLists','','','','','0','0','0','1776500000','1776500000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('347','207','A','回访详情','','0','ops.aftersaleTicket/callbackDetail','','','','','0','0','0','1776500000','1776500000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('348','207','A','创建回访','','0','ops.aftersaleTicket/createCallback','','','','','0','0','0','1776500000','1776500000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('349','207','A','完成回访','','0','ops.aftersaleTicket/completeCallback','','','','','0','0','0','1776500000','1776500000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('350','207','A','标记无法联系','','0','ops.aftersaleTicket/markUnreachable','','','','','0','0','0','1776500000','1776500000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('351','207','A','问题升级','','0','ops.aftersaleTicket/escalateProblem','','','','','0','0','0','1776500000','1776500000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('352','207','A','获取售后设置','','0','ops.aftersaleTicket/getConfig','','','','','0','0','0','1776600000','1776600000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('353','207','A','保存售后设置','','0','ops.aftersaleTicket/setConfig','','','','','0','0','0','1776600000','1776600000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('354','183','C','吉日设置','','85','ops.calendarEvent/lists','lucky-day','schedule/lucky_day/index','','','0','1','0','1776700000','1776700000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('355','354','A','吉日列表','','0','ops.calendarEvent/lists','','','','','0','0','0','1776700000','1776700000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('356','354','A','吉日详情','','0','ops.calendarEvent/detail','','','','','0','0','0','1776700000','1776700000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('357','354','A','保存吉日','','0','ops.calendarEvent/save','','','','','0','0','0','1776700000','1776700000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('358','354','A','删除吉日','','0','ops.calendarEvent/delete','','','','','0','0','0','1776700000','1776700000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('359','354','A','批量设置吉日','','0','ops.calendarEvent/batchSave','','','','','0','0','0','1776700000','1776700000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('360','354','A','拥堵等级选项','','0','ops.calendarEvent/congestionLevelOptions','','','','','0','0','0','1776700000','1776700000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('361','223','C','我的售后','','35','ops.aftersaleTicket/myTicketLists','aftersale','staff_center/aftersale/index','','','0','1','0','1776700000','1776700000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('362','361','A','我的工单列表','','0','ops.aftersaleTicket/myTicketLists','','','','','0','0','0','1776700000','1776700000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('363','361','A','我的工单详情','','0','ops.aftersaleTicket/myTicketDetail','','','','','0','0','0','1776700000','1776700000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('364','361','A','处理我的工单','','0','ops.aftersaleTicket/myHandleTicket','','','','','0','0','0','1776700000','1776700000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('365','361','A','我的投诉列表','','0','ops.aftersaleTicket/myComplaintLists','','','','','0','0','0','1776700000','1776700000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('366','361','A','我的投诉详情','','0','ops.aftersaleTicket/myComplaintDetail','','','','','0','0','0','1776700000','1776700000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('367','361','A','我的回访列表','','0','ops.aftersaleTicket/myCallbackLists','','','','','0','0','0','1776700000','1776700000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('368','361','A','我的回访详情','','0','ops.aftersaleTicket/myCallbackDetail','','','','','0','0','0','1776700000','1776700000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('369','361','A','完成我的回访','','0','ops.aftersaleTicket/myCompleteCallback','','','','','0','0','0','1776700000','1776700000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('370','361','A','标记无法联系','','0','ops.aftersaleTicket/myMarkUnreachable','','','','','0','0','0','1776700000','1776700000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('390','199','A','手动生成结算','','0','finance.settlement/generate','','','','','0','0','0','1777000000','1777000000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('391','199','A','重试转账','','0','finance.settlement/retryTransfer','','','','','0','0','0','1777000000','1777000000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('392','199','A','同步转账状态','','0','finance.settlement/syncTransfer','','','','','0','0','0','1777000000','1777000000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('393','199','A','转账明细','','0','finance.settlement/transferDetail','','','','','0','0','0','1777000000','1777000000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('394','199','A','转账配置','','0','finance.settlement/transferConfig','','','','','0','0','0','1777000000','1777000000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('395','199','A','保存转账配置','','0','finance.settlement/saveTransferConfig','','','','','0','0','0','1777000000','1777000000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('396','199','A','结算配置列表','','0','finance.settlement/configLists','','','','','0','0','0','1777200000','1777200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('397','199','A','新增结算配置','','0','finance.settlement/addConfig','','','','','0','0','0','1777200000','1777200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('398','199','A','编辑结算配置','','0','finance.settlement/editConfig','','','','','0','0','0','1777200000','1777200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('399','199','A','删除结算配置','','0','finance.settlement/deleteConfig','','','','','0','0','0','1777200000','1777200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('410','179','C','服务团队','','197','ops.staffTeam/lists','staff-team','staff/team/index','','','0','1','0','1777200000','1777200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('411','410','A','详情','','10','ops.staffTeam/detail','','','','','0','0','0','1777200000','1777200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('412','410','A','新增','','20','ops.staffTeam/add','','','','','0','0','0','1777200000','1777200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('413','410','A','编辑','','30','ops.staffTeam/edit','','','','','0','0','0','1777200000','1777200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('414','410','A','删除','','40','ops.staffTeam/delete','','','','','0','0','0','1777200000','1777200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('415','410','A','状态切换','','50','ops.staffTeam/changeStatus','','','','','0','0','0','1777200000','1777200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('416','410','A','队伍选项','','60','ops.staffTeam/options','','','','','0','0','0','1777200000','1777200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('420','223','C','队员管理','','33','ops.staff/myTeamMembers','team','staff_center/team/index','','','0','1','0','1777200000','1777200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('421','223','C','队员审核','','32','ops.staff/myTeamSummary','team-review','staff_center/team_review/index','','','0','1','0','1777200000','1777200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('422','420','A','队伍概要','','0','ops.staff/myTeamSummary','','','','','0','0','0','1777200000','1777200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('423','420','A','队员详情','','0','ops.staff/myTeamMemberDetail','','','','','0','0','0','1777200000','1777200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('424','420','A','编辑队员','','0','ops.staff/myTeamMemberUpdate','','','','','0','0','0','1777200000','1777200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('425','421','A','作品列表','','0','ops.staffWork/lists','','','','','0','0','0','1777200000','1777200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('426','421','A','作品审核','','0','ops.staffWork/audit','','','','','0','0','0','1777200000','1777200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('427','421','A','证书列表','','0','ops.staffCertificate/lists','','','','','0','0','0','1777200000','1777200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('428','421','A','证书审核','','0','ops.staffCertificate/audit','','','','','0','0','0','1777200000','1777200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('429','421','A','标签申请列表','','0','ops.staffTagReview/lists','','','','','0','0','0','1777200000','1777200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('430','421','A','标签审核通过','','0','ops.staffTagReview/approve','','','','','0','0','0','1777200000','1777200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('431','421','A','标签审核拒绝','','0','ops.staffTagReview/reject','','','','','0','0','0','1777200000','1777200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('432','421','A','动态列表','','0','growth.dynamic/lists','','','','','0','0','0','1777200000','1777200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('433','421','A','动态详情','','0','growth.dynamic/detail','','','','','0','0','0','1777200000','1777200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('434','421','A','动态审核','','0','growth.dynamic/audit','','','','','0','0','0','1777200000','1777200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('441','223','C','新人问卷','','37','ops.staff/myCoupleQuestionnaireConfig','couple-questionnaire','staff_center/couple_questionnaire/index','','','0','1','0','1777600000','1777600000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('442','441','A','保存问卷草稿','','0','ops.staff/myCoupleQuestionnaireSave','','','','','0','0','0','1777600000','1777600000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('443','441','A','发布问卷新版','','0','ops.staff/myCoupleQuestionnairePublish','','','','','0','0','0','1777600000','1777600000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('444','441','A','问卷任务列表','','0','ops.staff/myCoupleQuestionnaireTasks','','','','','0','0','0','1777600000','1777600000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('445','441','A','问卷任务详情','','0','ops.staff/myCoupleQuestionnaireTaskDetail','','','','','0','0','0','1777600000','1777600000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('446','441','A','发送问卷','','0','ops.staff/myCoupleQuestionnaireSend','','','','','0','0','0','1777600000','1777600000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('447','0','M','新人问卷','el-icon-Document','560','','couple-questionnaire','','','','0','1','0','1777600000','1777600000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('448','447','C','问卷列表','','100','growth.coupleQuestionnaire/lists','lists','questionnaire/couple_questionnaire/index','','','0','1','0','1777600000','1777600000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('449','447','C','配置问卷','','95','growth.coupleQuestionnaire/detail','config','questionnaire/couple_questionnaire/config','/couple-questionnaire/lists','','0','0','0','1777600000','1777600000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('450','448','A','保存问卷草稿','','0','growth.coupleQuestionnaire/save','','','','','0','0','0','1777600000','1777600000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('451','448','A','发布问卷新版','','0','growth.coupleQuestionnaire/publish','','','','','0','0','0','1777600000','1777600000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('452','447','C','基础题库','','90','growth.coupleQuestionBank/lists','bank','questionnaire/couple_question_bank/index','','','0','1','0','1777600000','1777600000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('453','452','A','题库详情','','0','growth.coupleQuestionBank/detail','','','','','0','0','0','1777600000','1777600000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('454','452','A','保存题库','','0','growth.coupleQuestionBank/save','','','','','0','0','0','1777600000','1777600000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('455','452','A','删除题库','','0','growth.coupleQuestionBank/delete','','','','','0','0','0','1777600000','1777600000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('456','452','A','题库状态','','0','growth.coupleQuestionBank/changeStatus','','','','','0','0','0','1777600000','1777600000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('457','452','A','题库排序','','0','growth.coupleQuestionBank/sort','','','','','0','0','0','1777600000','1777600000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('458','447','C','问卷任务','','80','growth.coupleQuestionnaire/tasks','tasks','questionnaire/couple_questionnaire_task/index','','','0','1','0','1777600000','1777600000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('459','458','A','任务详情','','0','growth.coupleQuestionnaire/taskDetail','','','','','0','0','0','1777600000','1777600000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('460','458','A','发送问卷','','0','growth.coupleQuestionnaire/send','','','','','0','0','0','1777600000','1777600000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('461','194','A','活动报名名单','','0','growth.dynamic/activityRegistrations','','','','','0','0','0','1777700000','1777700000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('462','194','A','活动退款申请','','0','growth.dynamic/activityRefunds','','','','','0','0','0','1777700000','1777700000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('463','194','A','活动退款审核','','0','growth.dynamic/activityRefundAudit','','','','','0','0','0','1777700000','1777700000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('464','194','A','活动报名导出','','0','growth.dynamic/activityRegistrationExport','','','','','0','0','0','1777700000','1777700000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('465','223','C','档期海报','','36','ops.staff/myScheduleConfirmLetterConfig','schedule-confirm-letter','staff_center/schedule_confirm_letter/index','','','0','1','0','1777800000','1777800000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('466','465','A','保存档期海报','','0','ops.staff/myScheduleConfirmLetterSave','','','','','0','0','0','1777800000','1777800000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('467','465','A','预览档期海报','','0','ops.staff/myScheduleConfirmLetterPreview','','','','','0','0','0','1777800000','1777800000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('468','215','A','读取人员档期海报','','0','ops.staff/scheduleConfirmLetterConfig','','','','','0','0','0','1777800000','1777800000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('469','215','A','保存人员档期海报','','0','ops.staff/scheduleConfirmLetterSave','','','','','0','0','0','1777800000','1777800000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('470','215','A','预览人员档期海报','','0','ops.staff/scheduleConfirmLetterPreview','','','','','0','0','0','1777800000','1777800000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('471','465','A','复制档期海报模板','','0','ops.staff/myScheduleConfirmLetterCopy','','','','','0','0','0','1777800000','1777800000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('472','465','A','设置默认档期海报模板','','0','ops.staff/myScheduleConfirmLetterSetDefault','','','','','0','0','0','1777800000','1777800000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('473','465','A','停用档期海报模板','','0','ops.staff/myScheduleConfirmLetterDisable','','','','','0','0','0','1777800000','1777800000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('474','215','A','复制人员档期海报模板','','0','ops.staff/scheduleConfirmLetterCopy','','','','','0','0','0','1777800000','1777800000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('475','215','A','设置人员默认档期海报模板','','0','ops.staff/scheduleConfirmLetterSetDefault','','','','','0','0','0','1777800000','1777800000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('476','215','A','停用人员档期海报模板','','0','ops.staff/scheduleConfirmLetterDisable','','','','','0','0','0','1777800000','1777800000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('477','223','C','我的作品','','94','ops.staffWork/lists','work','staff_center/work/index','','','0','1','0','1777900000','1777900000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('478','223','C','我的证书','','93','ops.staffCertificate/lists','certificate','staff_center/certificate/index','','','0','1','0','1777900000','1777900000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('479','187','C','单量月报','','70','ops.monthlyReport/preview','monthly-report','order/monthly_report/index','','','0','1','0','1778000000','1778000000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('480','479','A','素材列表','','0','ops.monthlyReport/materialLists','','','','','0','0','0','1778000000','1778000000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('481','479','A','保存素材','','0','ops.monthlyReport/materialSave','','','','','0','0','0','1778000000','1778000000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('482','479','A','删除素材','','0','ops.monthlyReport/materialDelete','','','','','0','0','0','1778000000','1778000000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('483','479','A','人员选项','','0','ops.monthlyReport/staffOptions','','','','','0','0','0','1778000000','1778000000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('484','479','A','分类选项','','0','ops.monthlyReport/categoryOptions','','','','','0','0','0','1778000000','1778000000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('485','479','A','模板配置','','0','ops.monthlyReport/templateConfig','','','','','0','0','0','1778000000','1778000000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('486','479','A','保存模板','','0','ops.monthlyReport/templateSave','','','','','0','0','0','1778000000','1778000000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('487','479','A','复制模板','','0','ops.monthlyReport/templateCopy','','','','','0','0','0','1778000000','1778000000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('488','479','A','设置默认模板','','0','ops.monthlyReport/templateSetDefault','','','','','0','0','0','1778000000','1778000000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('489','479','A','停用模板','','0','ops.monthlyReport/templateDisable','','','','','0','0','0','1778000000','1778000000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('490','479','A','模板预览','','0','ops.monthlyReport/templatePreview','','','','','0','0','0','1778000000','1778000000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('491','479','A','生成预览','','0','ops.monthlyReport/preview','','','','','0','0','0','1778000000','1778000000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('492','479','A','确认月报','','0','ops.monthlyReport/confirm','','','','','0','0','0','1778000000','1778000000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('493','479','A','最新版本','','0','ops.monthlyReport/latest','','','','','0','0','0','1778000000','1778000000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('494','479','A','历史版本','','0','ops.monthlyReport/history','','','','','0','0','0','1778000000','1778000000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('495','479','A','版本详情','','0','ops.monthlyReport/detail','','','','','0','0','0','1778000000','1778000000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('496','479','A','重建图片','','0','ops.monthlyReport/regenerateAssets','','','','','0','0','0','1778000000','1778000000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('497','0','M','快捷工具','el-icon-Tools','780','','quick-tools','','','','0','1','0','1778000100','1778000100');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('501','497','C','人像分割','','30','','human-split','tools/quick/human_split','','','0','1','0','1778000100','1778000100');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('502','497','C','图像压缩','','20','','compress','tools/quick/compress','','','0','1','0','1778000100','1778000100');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('503','497','C','图片格式转换','','10','','convert','tools/quick/convert','','','0','1','0','1778000100','1778000100');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('504','497','C','图片编辑','','40','','image-edit','tools/quick/image_edit','','','0','1','0','1778000100','1778000100');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('505','497','C','视频压缩','','50','','video-compress','tools/quick/video_compress','','','0','1','0','1778199189','1778199189');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('520','63','C','素材清理','el-icon-Delete','10','content.materialCleanup/summary','cleanup','material/cleanup','','','0','1','0','1779600000','1779600000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('521','520','A','扫描统计','','0','content.materialCleanup/summary','','','','','0','0','0','1779600000','1779600000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('522','520','A','候选列表','','0','content.materialCleanup/lists','','','','','0','0','0','1779600000','1779600000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('523','520','A','确认删除','','0','content.materialCleanup/delete','','','','','0','0','0','1779600000','1779600000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('524','421','A','作品批量审核','','0','ops.staffWork/batchAudit','','','','','0','0','0','1779700000','1779700000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('525','421','A','证书批量审核','','0','ops.staffCertificate/batchAudit','','','','','0','0','0','1779700000','1779700000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('526','282','A','批量删除','','38','ops.staffCertificate/batchDelete','','','','','0','0','0','1779700000','1779700000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('527','282','A','批量审核','','48','ops.staffCertificate/batchAudit','','','','','0','0','0','1779700000','1779700000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('528','421','A','作品批量删除','','0','ops.staffWork/batchDelete','','','','','0','0','0','1779700000','1779700000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('529','255','A','批量审核通过','','40','ops.staffTagReview/batchApprove','','','','','0','0','0','1779700000','1779700000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('530','255','A','批量审核拒绝','','50','ops.staffTagReview/batchReject','','','','','0','0','0','1779700000','1779700000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('531','421','A','标签批量审核通过','','0','ops.staffTagReview/batchApprove','','','','','0','0','0','1779700000','1779700000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('532','421','A','标签批量审核拒绝','','0','ops.staffTagReview/batchReject','','','','','0','0','0','1779700000','1779700000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('533','85','C','通知推送','el-icon-ChatLineRound','60','notification.oaNotification/templateList','notification','message/oa_notification/index','','','0','1','0','1776200000','1776200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('534','533','A','公众号通知配置','','0','notification.oaNotification/config','','','','','0','0','0','1776200000','1776200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('535','533','A','公众号通知模板编辑','','0','notification.oaNotification/editTemplate','','','','','0','0','0','1776200000','1776200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('536','533','A','公众号通知日志','','0','notification.oaNotification/logList','','','','','0','0','0','1776200000','1776200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('537','533','A','公众号绑定状态','','0','notification.oaNotification/followerList','','','','','0','0','0','1776200000','1776200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('538','533','A','公众号测试通知','','0','notification.oaNotification/testSend','','','','','0','0','0','1776200000','1776200000');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('539','533','A','重试服务号通知','','0','notification.oaNotification/retry','','','','','0','0','0','0','0');
INSERT INTO `la_system_menu` (`id`,`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`) VALUES ('540','189','A','重试退款','','0','ops.refund/retry','','','','','0','0','0','0','0');
CREATE TABLE `la_system_role` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '名称',
  `desc` varchar(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '描述',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  `create_time` int(10) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='角色表';
INSERT INTO `la_system_role` (`id`,`name`,`desc`,`sort`,`create_time`,`update_time`,`delete_time`) VALUES ('1','服务人员','服务人员','0','1773413108','1773560454',NULL);
INSERT INTO `la_system_role` (`id`,`name`,`desc`,`sort`,`create_time`,`update_time`,`delete_time`) VALUES ('2','管理员','','0','1773563523','1773563576',NULL);
INSERT INTO `la_system_role` (`id`,`name`,`desc`,`sort`,`create_time`,`update_time`,`delete_time`) VALUES ('3','顾问','CRM顾问，仅可处理本人客户和跟进记录','10','1776300000','1776300000',NULL);
CREATE TABLE `la_system_role_menu` (
  `role_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '角色ID',
  `menu_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '菜单ID',
  PRIMARY KEY (`role_id`,`menu_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='角色菜单关系表';
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','223');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','224');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','225');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','226');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','227');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','228');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','229');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','230');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','288');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','289');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','361');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','362');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','363');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','364');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','365');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','366');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','367');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','368');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','369');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','370');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','420');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','421');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','422');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','423');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','424');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','441');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','442');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','443');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','444');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','445');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','446');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','465');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','466');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','467');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','471');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','472');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','473');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','497');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','501');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','502');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('1','503');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','4');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','5');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','6');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','7');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','8');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','12');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','14');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','15');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','16');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','17');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','18');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','19');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','20');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','21');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','28');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','29');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','30');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','31');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','32');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','33');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','35');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','36');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','37');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','38');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','51');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','52');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','53');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','54');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','61');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','63');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','64');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','68');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','69');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','70');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','71');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','72');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','73');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','74');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','75');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','76');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','77');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','78');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','79');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','80');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','81');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','82');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','83');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','84');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','94');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','95');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','96');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','97');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','98');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','99');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','100');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','101');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','102');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','103');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','104');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','105');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','107');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','108');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','109');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','110');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','111');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','112');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','113');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','114');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','115');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','116');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','117');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','118');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','119');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','120');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','143');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','144');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','145');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','146');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','147');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','158');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','161');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','162');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','163');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','164');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','165');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','173');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','175');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','177');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','178');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','179');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','180');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','181');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','182');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','183');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','184');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','186');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','187');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','188');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','189');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','190');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','193');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','194');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','195');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','196');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','197');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','198');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','199');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','206');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','207');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','210');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','211');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','215');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','216');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','218');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','219');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','220');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','221');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','222');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','231');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','232');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','233');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','234');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','235');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','236');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','237');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','238');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','239');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','240');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','241');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','242');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','243');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','244');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','245');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','246');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','247');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','255');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','256');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','257');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','258');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','282');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','283');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','284');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','285');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','286');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','287');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','290');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','291');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','295');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','296');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','297');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','298');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','299');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','301');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','302');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','303');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','304');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','305');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','306');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','307');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','308');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','309');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','310');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','311');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','312');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','313');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','314');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','315');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','316');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','317');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','318');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','319');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','320');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','321');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','322');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','323');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','324');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','325');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','326');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','327');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','328');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','329');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','330');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','331');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','332');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','333');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','334');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','335');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','336');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','337');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','338');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','339');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','340');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','341');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','342');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','343');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','344');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','345');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','346');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','347');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','348');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','349');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','350');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','351');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','352');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','353');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','354');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','355');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','356');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','357');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','358');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','359');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','360');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','361');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','362');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','363');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','364');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','365');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','366');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','367');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','368');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','369');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','370');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','396');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','397');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','398');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','399');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','410');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','411');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','412');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','413');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','414');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','415');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','416');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','447');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','448');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','449');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','450');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','451');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','452');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','453');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','454');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','455');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','456');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','457');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','458');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','459');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','460');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','461');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','462');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','463');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','464');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','465');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','466');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','467');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','468');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','469');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','470');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','471');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','472');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','473');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','474');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','475');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','476');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','479');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','480');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','481');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','482');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','483');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','484');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','485');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','486');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','487');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','488');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','489');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','490');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','491');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','492');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','493');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','494');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','495');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','496');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','497');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','501');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','502');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','503');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','504');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','505');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','524');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','525');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','526');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','527');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','528');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','529');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','530');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','531');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','532');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','533');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','534');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','535');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','536');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','537');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','538');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','539');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('2','540');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('3','301');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('3','308');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('3','309');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('3','310');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('3','312');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('3','313');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('3','314');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('3','315');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('3','316');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('3','317');
INSERT INTO `la_system_role_menu` (`role_id`,`menu_id`) VALUES ('3','318');
CREATE TABLE `la_user` (
  `oa_guide_seen_time` int unsigned NOT NULL DEFAULT 0,
  `oa_reminder_skip_until` int unsigned NOT NULL DEFAULT 0,
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `sn` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '编号',
  `avatar` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '头像',
  `real_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '真实姓名',
  `nickname` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户昵称',
  `account` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户账号',
  `password` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户密码',
  `mobile` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户电话',
  `sex` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '用户性别: [1=男, 2=女]',
  `channel` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '注册渠道：1=微信小程序',
  `is_disable` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否禁用: [0=否, 1=是]',
  `login_ip` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '最后登录IP',
  `login_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `is_new_user` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否是新注册用户: [1-是, 0-否]',
  `wechat_risk_rank` tinyint(4) NOT NULL DEFAULT '0' COMMENT '微信检测风险等级：0-4，数字越大风险越高',
  `manual_risk_rank` tinyint(4) NOT NULL DEFAULT '-1' COMMENT '后台人工风险等级：-1=跟随微信检测，0-4=人工覆盖',
  `risk_rank_update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '风险等级更新时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `sn` (`sn`) USING BTREE COMMENT '编号唯一',
  UNIQUE KEY `account` (`account`) USING BTREE COMMENT '账号唯一'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='用户表';
CREATE TABLE `la_user_auth` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `openid` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '微信openid',
  `unionid` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '微信unionid',
  `terminal` tinyint(1) NOT NULL DEFAULT '1' COMMENT '客户端类型：1=微信小程序',
  `create_time` int(10) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `openid` (`openid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='用户授权表';
CREATE TABLE `la_user_session` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `terminal` tinyint(1) NOT NULL DEFAULT '1' COMMENT '客户端类型：1=微信小程序',
  `token` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '令牌',
  `update_time` int(10) DEFAULT NULL COMMENT '更新时间',
  `expire_time` int(10) NOT NULL COMMENT '到期时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `admin_id_client` (`user_id`,`terminal`) USING BTREE COMMENT '一个用户在一个终端只有一个token',
  UNIQUE KEY `token` (`token`) USING BTREE COMMENT 'token是唯一的'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='用户会话表';
CREATE TABLE `la_waitlist` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` int(10) unsigned NOT NULL COMMENT '用户ID',
  `staff_id` int(10) unsigned NOT NULL COMMENT '工作人员ID',
  `schedule_date` date NOT NULL COMMENT '预约日期',
  `time_slot` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '时间段',
  `package_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '套餐ID',
  `batch_no` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '候补批次号',
  `notify_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '通知状态：0=未通知,1=已通知,2=已下单,3=已过期',
  `notify_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '通知时间',
  `expire_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '过期时间',
  `remark` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_staff_id` (`staff_id`),
  KEY `idx_schedule_date` (`schedule_date`),
  KEY `idx_notify_status` (`notify_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='候补订单表';
CREATE TABLE `la_wechat_binding_attempt` (
  `subject` char(64) NOT NULL,
  `attempts` int(10) unsigned NOT NULL DEFAULT '0',
  `window_start` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`subject`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='绑定验证限次';
CREATE TABLE `la_wechat_oa_bind_session` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `source` varchar(20) NOT NULL DEFAULT 'miniapp',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `candidate_user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `candidate_openid` varchar(128) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `binding_code` char(10) COLLATE utf8mb4_general_ci NOT NULL,
  `token` varchar(80) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `expires_time` int(10) unsigned NOT NULL DEFAULT '0',
  `consumed_time` int(10) unsigned NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_token` (`token`),
  UNIQUE KEY `uk_binding_code` (`binding_code`),
  KEY `idx_user_status` (`user_id`,`status`),
  KEY `idx_source_candidate` (`source`,`candidate_openid`,`status`),
  KEY `idx_expire` (`status`,`expires_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='微信公众号绑定会话';
CREATE TABLE `la_wechat_oa_follower` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `openid` varchar(128) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `unionid` varchar(128) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `follow_status` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `source` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `followed_time` int(10) unsigned NOT NULL DEFAULT '0',
  `unfollowed_time` int(10) unsigned NOT NULL DEFAULT '0',
  `last_event_time` int(10) unsigned NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_openid` (`openid`),
  UNIQUE KEY `uk_user_id` (`user_id`),
  KEY `idx_follow_status` (`follow_status`),
  KEY `idx_unionid` (`unionid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='微信公众号粉丝绑定状态';
CREATE TABLE `la_wechat_oa_notification_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lock_token` char(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `openid` varchar(128) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `audience` varchar(16) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'user',
  `scene` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `business_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `business_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `template_id` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `payload` text COLLATE utf8mb4_general_ci,
  `dedupe_key` varchar(128) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `planned_send_time` int(10) unsigned NOT NULL DEFAULT '0',
  `next_retry_time` int(10) unsigned NOT NULL DEFAULT '0',
  `lock_until` int(10) unsigned NOT NULL DEFAULT '0',
  `retry_count` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `send_status` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `last_error_code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `error_msg` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `request_id` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `send_time` int(10) unsigned NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_dedupe_key` (`dedupe_key`),
  KEY `idx_user_scene` (`user_id`,`scene`),
  KEY `idx_business` (`business_type`,`business_id`),
  KEY `idx_send_queue` (`send_status`,`next_retry_time`,`planned_send_time`,`lock_until`,`id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='微信公众号通知发送日志';
CREATE TABLE `la_wechat_oa_notification_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `scene` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `audience` varchar(16) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'user',
  `template_id` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `data_mapping` text COLLATE utf8mb4_general_ci,
  `page_path` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `sort` int(11) NOT NULL DEFAULT '0',
  `remark` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_scene_audience` (`scene`,`audience`),
  KEY `idx_template_status` (`template_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='微信公众号通知模板配置';
INSERT INTO `la_wechat_oa_notification_template` (`id`,`scene`,`audience`,`template_id`,`data_mapping`,`page_path`,`status`,`sort`,`remark`,`create_time`,`update_time`) VALUES ('5','waitlist_release','user','','{\"keyword1\":\"staff_name\",\"keyword2\":\"schedule_date\",\"keyword3\":\"package_name\",\"keyword4\":\"status_text\"}','packages/pages/waitlist/waitlist','0','96','请在公众号后台配置模板ID后开启','1776200000','1776200000');
INSERT INTO `la_wechat_oa_notification_template` (`id`,`scene`,`audience`,`template_id`,`data_mapping`,`page_path`,`status`,`sort`,`remark`,`create_time`,`update_time`) VALUES ('6','waitlist_expired','user','','{\"keyword1\":\"staff_name\",\"keyword2\":\"schedule_date\",\"keyword3\":\"package_name\",\"keyword4\":\"status_text\"}','packages/pages/waitlist/waitlist','0','95','请在公众号后台配置模板ID后开启','1776200000','1776200000');
INSERT INTO `la_wechat_oa_notification_template` (`id`,`scene`,`audience`,`template_id`,`data_mapping`,`page_path`,`status`,`sort`,`remark`,`create_time`,`update_time`) VALUES ('7','staff_order','staff','','{\"keyword1\":\"title\",\"keyword2\":\"content\",\"keyword3\":\"status_text\",\"keyword4\":\"remark_text\"}','packages/pages/staff_order_detail/staff_order_detail','0','90','请在公众号后台配置模板ID后开启','1776200000','1776200000');
INSERT INTO `la_wechat_oa_notification_template` (`id`,`scene`,`audience`,`template_id`,`data_mapping`,`page_path`,`status`,`sort`,`remark`,`create_time`,`update_time`) VALUES ('8','staff_schedule','staff','','{\"keyword1\":\"title\",\"keyword2\":\"content\",\"keyword3\":\"status_text\",\"keyword4\":\"remark_text\"}','packages/pages/staff_order_detail/staff_order_detail','0','89','请在公众号后台配置模板ID后开启','1776200000','1776200000');
INSERT INTO `la_wechat_oa_notification_template` (`id`,`scene`,`audience`,`template_id`,`data_mapping`,`page_path`,`status`,`sort`,`remark`,`create_time`,`update_time`) VALUES ('9','staff_pause','staff','','{\"keyword1\":\"title\",\"keyword2\":\"content\",\"keyword3\":\"status_text\",\"keyword4\":\"remark_text\"}','packages/pages/staff_order_detail/staff_order_detail','0','88','请在公众号后台配置模板ID后开启','1776200000','1776200000');
INSERT INTO `la_wechat_oa_notification_template` (`id`,`scene`,`audience`,`template_id`,`data_mapping`,`page_path`,`status`,`sort`,`remark`,`create_time`,`update_time`) VALUES ('10','staff_refund','staff','','{\"keyword1\":\"title\",\"keyword2\":\"content\",\"keyword3\":\"status_text\",\"keyword4\":\"remark_text\"}','packages/pages/staff_order_detail/staff_order_detail','0','87','请在公众号后台配置模板ID后开启','1776200000','1776200000');
INSERT INTO `la_wechat_oa_notification_template` (`id`,`scene`,`audience`,`template_id`,`data_mapping`,`page_path`,`status`,`sort`,`remark`,`create_time`,`update_time`) VALUES ('11','staff_aftersale','staff','','{\"keyword1\":\"title\",\"keyword2\":\"content\",\"keyword3\":\"status_text\",\"keyword4\":\"remark_text\"}','packages/pages/staff_order_detail/staff_order_detail','0','86','请在公众号后台配置模板ID后开启','1776200000','1776200000');
INSERT INTO `la_wechat_oa_notification_template` (`id`,`scene`,`audience`,`template_id`,`data_mapping`,`page_path`,`status`,`sort`,`remark`,`create_time`,`update_time`) VALUES ('12','staff_change','staff','','{\"keyword1\":\"title\",\"keyword2\":\"content\",\"keyword3\":\"status_text\",\"keyword4\":\"remark_text\"}','packages/pages/staff_order_detail/staff_order_detail','0','85','请在公众号后台配置模板ID后开启','1776200000','1776200000');
INSERT INTO `la_wechat_oa_notification_template` (`id`,`scene`,`audience`,`template_id`,`data_mapping`,`page_path`,`status`,`sort`,`remark`,`create_time`,`update_time`) VALUES ('13','order_update','user','','{\"thing1\":\"title\",\"thing2\":\"content\"}','packages/pages/order_detail/order_detail','0','0','配置服务号已申请的模板ID及字段映射后开启','0','0');
INSERT INTO `la_wechat_oa_notification_template` (`id`,`scene`,`audience`,`template_id`,`data_mapping`,`page_path`,`status`,`sort`,`remark`,`create_time`,`update_time`) VALUES ('14','change_result','user','','{\"thing1\":\"title\",\"thing2\":\"content\"}','packages/pages/order_change/change_detail','0','0','配置服务号已申请的模板ID及字段映射后开启','0','0');
INSERT INTO `la_wechat_oa_notification_template` (`id`,`scene`,`audience`,`template_id`,`data_mapping`,`page_path`,`status`,`sort`,`remark`,`create_time`,`update_time`) VALUES ('15','ticket_update','user','','{\"thing1\":\"title\",\"thing2\":\"content\"}','packages/pages/aftersale/ticket_detail','0','0','配置服务号已申请的模板ID及字段映射后开启','0','0');
INSERT INTO `la_wechat_oa_notification_template` (`id`,`scene`,`audience`,`template_id`,`data_mapping`,`page_path`,`status`,`sort`,`remark`,`create_time`,`update_time`) VALUES ('16','questionnaire_update','user','','{\"thing1\":\"title\",\"thing2\":\"content\"}','packages/pages/couple_questionnaire/detail','0','0','配置服务号已申请的模板ID及字段映射后开启','0','0');
INSERT INTO `la_wechat_oa_notification_template` (`id`,`scene`,`audience`,`template_id`,`data_mapping`,`page_path`,`status`,`sort`,`remark`,`create_time`,`update_time`) VALUES ('17','activity_update','user','','{\"thing1\":\"title\",\"thing2\":\"content\"}','packages/pages/activity_registration/detail','0','0','配置服务号已申请的模板ID及字段映射后开启','0','0');
INSERT INTO `la_wechat_oa_notification_template` (`id`,`scene`,`audience`,`template_id`,`data_mapping`,`page_path`,`status`,`sort`,`remark`,`create_time`,`update_time`) VALUES ('18','settlement_update','staff','','{\"thing1\":\"title\",\"thing2\":\"content\"}','packages/pages/staff_settlement/staff_settlement','0','0','配置服务号已申请的模板ID及字段映射后开启','0','0');
INSERT INTO `la_wechat_oa_notification_template` (`id`,`scene`,`audience`,`template_id`,`data_mapping`,`page_path`,`status`,`sort`,`remark`,`create_time`,`update_time`) VALUES ('19','staff_internal','staff','','{\"thing1\":\"title\",\"thing2\":\"content\"}','packages/pages/notification/index','0','0','配置服务号已申请的模板ID及字段映射后开启','0','0');
-- 后台操作权限补登记：只添加权限项，不自动向现有角色授予写入权限。
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '新增', 'ops.order/add', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.order/add');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '编辑', 'ops.order/edit', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.order/edit');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '取消', 'ops.order/cancel', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.order/cancel');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '确认', 'ops.order/confirm', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.order/confirm');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '开始服务', 'ops.order/startService', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.order/startService');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '完成服务', 'ops.order/complete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.order/complete');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '直接改期', 'ops.order/directReschedule', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.order/directReschedule');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '删除', 'ops.order/delete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.order/delete');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '确认线下收款', 'ops.order/confirmOfflinePay', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.order/confirmOfflinePay');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '审核付款凭证', 'ops.order/auditVoucher', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.order/auditVoucher');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '添加备注', 'ops.order/addRemark', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.order/addRemark');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '线下建单', 'ops.order/addOffline', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.order/addOffline');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '审核', 'ops.orderChange/audit', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.orderChange/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.orderChange/audit');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '执行', 'ops.orderChange/execute', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.orderChange/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.orderChange/execute');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '申请', 'ops.refund/apply', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.refund/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.refund/apply');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '审核', 'ops.refund/audit', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.refund/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.refund/audit');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '确认退款', 'ops.refund/confirmRefund', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.refund/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.refund/confirmRefund');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '新增', 'ops.category/add', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.category/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.category/add');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '编辑', 'ops.category/edit', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.category/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.category/edit');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '删除', 'ops.category/delete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.category/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.category/delete');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '切换状态', 'ops.category/changeStatus', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.category/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.category/changeStatus');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '新增', 'ops.package/add', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.package/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.package/add');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '编辑', 'ops.package/edit', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.package/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.package/edit');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '删除', 'ops.package/delete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.package/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.package/delete');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '切换状态', 'ops.package/changeStatus', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.package/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.package/changeStatus');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '新增', 'ops.staff/add', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/add');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '编辑', 'ops.staff/edit', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/edit');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '删除', 'ops.staff/delete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/delete');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '切换状态', 'ops.staff/changeStatus', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/changeStatus');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '配置套餐', 'ops.staff/configurePackages', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/configurePackages');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '新增人员附加服务', 'ops.staff/createStaffAddon', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/createStaffAddon');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '新增人员套餐', 'ops.staff/createStaffPackage', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/createStaffPackage');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '删除人员附加服务', 'ops.staff/deleteStaffAddon', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/deleteStaffAddon');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '删除人员套餐', 'ops.staff/deleteStaffPackage', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/deleteStaffPackage');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '新增轮播图', 'ops.staff/addBanner', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/addBanner');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '编辑轮播图', 'ops.staff/editBanner', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/editBanner');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '删除轮播图', 'ops.staff/deleteBanner', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/deleteBanner');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '轮播图排序', 'ops.staff/sortBanner', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/sortBanner');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '修改轮播图配置', 'ops.staff/updateBannerConfig', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/updateBannerConfig');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '修改套餐配置', 'ops.staff/updatePackageConfig', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/updatePackageConfig');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '修改人员附加服务', 'ops.staff/updateStaffAddon', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/updateStaffAddon');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '修改人员套餐', 'ops.staff/updateStaffPackage', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/updateStaffPackage');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '重置后台密码', 'ops.staff/resetAdminPassword', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/resetAdminPassword');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '批量设置档期', 'ops.schedule/batchSet', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.schedule/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.schedule/batchSet');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '锁定档期', 'ops.schedule/lock', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.schedule/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.schedule/lock');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '解除锁定', 'ops.schedule/unlock', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.schedule/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.schedule/unlock');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '预留档期', 'ops.schedule/reserve', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.schedule/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.schedule/reserve');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '修改状态', 'ops.schedule/setStatus', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.schedule/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.schedule/setStatus');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '批量通知', 'ops.waitlist/batchNotify', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.waitlist/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.waitlist/batchNotify');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '候补转预约', 'ops.waitlist/convert', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.waitlist/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.waitlist/convert');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '作废候补', 'ops.waitlist/invalidate', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.waitlist/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.waitlist/invalidate');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '发送候补通知', 'ops.waitlist/notify', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.waitlist/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.waitlist/notify');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '新增', 'growth.dynamic/add', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.dynamic/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.dynamic/add');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '编辑', 'growth.dynamic/edit', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.dynamic/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.dynamic/edit');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '删除', 'growth.dynamic/delete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.dynamic/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.dynamic/delete');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '删除评论', 'growth.dynamic/deleteComment', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.dynamic/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.dynamic/deleteComment');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '置顶评论', 'growth.dynamic/setCommentTop', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.dynamic/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.dynamic/setCommentTop');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '下架', 'growth.dynamic/offline', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.dynamic/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.dynamic/offline');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '设置热门', 'growth.dynamic/setHot', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.dynamic/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.dynamic/setHot');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '置顶', 'growth.dynamic/setTop', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.dynamic/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.dynamic/setTop');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '通过', 'growth.dynamicComment/approve', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.dynamicComment/reviewList' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.dynamicComment/approve');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '批量通过', 'growth.dynamicComment/batchApprove', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.dynamicComment/reviewList' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.dynamicComment/batchApprove');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '批量删除', 'growth.dynamicComment/batchDelete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.dynamicComment/reviewList' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.dynamicComment/batchDelete');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '批量拒绝', 'growth.dynamicComment/batchReject', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.dynamicComment/reviewList' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.dynamicComment/batchReject');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '删除', 'growth.dynamicComment/delete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.dynamicComment/reviewList' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.dynamicComment/delete');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '拒绝', 'growth.dynamicComment/reject', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.dynamicComment/reviewList' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.dynamicComment/reject');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '审核', 'growth.review/audit', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.review/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.review/audit');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '批量审核', 'growth.review/batchAudit', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.review/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.review/batchAudit');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '删除', 'growth.review/delete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.review/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.review/delete');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '回复', 'growth.review/reply', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.review/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.review/reply');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '切换展示', 'growth.review/toggleShow', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.review/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.review/toggleShow');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '切换置顶', 'growth.review/toggleTop', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.review/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.review/toggleTop');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '批量删除', 'growth.notification/batchDelete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.notification/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.notification/batchDelete');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '批量发送', 'growth.notification/batchSend', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.notification/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.notification/batchSend');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '删除', 'growth.notification/delete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.notification/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.notification/delete');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '发送通知', 'growth.notification/send', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.notification/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.notification/send');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '全体通知', 'growth.notification/sendToAll', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.notification/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.notification/sendToAll');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '审核结算批次', 'finance.settlement/auditBatch', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'finance.settlement/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'finance.settlement/auditBatch');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '批量结算', 'finance.settlement/batchSettle', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'finance.settlement/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'finance.settlement/batchSettle');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '取消', 'finance.settlement/cancel', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'finance.settlement/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'finance.settlement/cancel');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '取消结算批次', 'finance.settlement/cancelBatch', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'finance.settlement/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'finance.settlement/cancelBatch');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '创建结算批次', 'finance.settlement/createBatch', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'finance.settlement/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'finance.settlement/createBatch');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '执行结算批次', 'finance.settlement/executeBatch', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'finance.settlement/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'finance.settlement/executeBatch');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '发起结算', 'finance.settlement/settle', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'finance.settlement/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'finance.settlement/settle');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '新增', 'channel.official_account_reply/add', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'channel.official_account_reply/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'channel.official_account_reply/add');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '编辑', 'channel.official_account_reply/edit', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'channel.official_account_reply/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'channel.official_account_reply/edit');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '删除', 'channel.official_account_reply/delete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'channel.official_account_reply/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'channel.official_account_reply/delete');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '切换状态', 'channel.official_account_reply/status', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'channel.official_account_reply/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'channel.official_account_reply/status');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '编辑', 'article.articleCate/edit', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'article.articleCate/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'article.articleCate/edit');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '保存通知配置', 'notification.oaNotification/saveConfig', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'notification.oaNotification/config' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'notification.oaNotification/saveConfig');
-- 账号关系属于独立写权限，部署后按岗位显式授权。
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '管理账号绑定', 'auth.admin/bindingSave', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'auth.admin/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'auth.admin/bindingSave');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '查看账号绑定', 'auth.admin/bindingDetail', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'auth.admin/bindingSave' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'auth.admin/bindingDetail');
