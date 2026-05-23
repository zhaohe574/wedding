-- Migration: 20260523_couple_questionnaire_schema
-- Owner: architect/backend
-- Scope: table/data
-- Precheck:
--   1. SHOW TABLES LIKE 'la_couple_questionnaire%';
--   2. SHOW TABLES LIKE 'la_couple_question_bank';
-- Rollback: server/sql/20260523_couple_questionnaire_schema.rollback.sql（仅限确认无线上问卷数据时执行）
-- Notes:
--   - 用于老库补齐新人问卷表结构；全量新装库已包含这些表。
--   - 若表已存在，本脚本不会覆盖已有业务数据。

CREATE TABLE IF NOT EXISTS `la_couple_question_bank` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '题库ID',
    `category` varchar(50) NOT NULL DEFAULT '' COMMENT '题目分类',
    `category_sort` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '分类排序',
    `type` varchar(20) NOT NULL DEFAULT 'textarea' COMMENT '题型:text/textarea/single/multiple/rating',
    `title` varchar(200) NOT NULL DEFAULT '' COMMENT '题目标题',
    `placeholder` varchar(200) NOT NULL DEFAULT '' COMMENT '输入提示',
    `options` text COMMENT '选项(JSON数组)',
    `required` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否必填',
    `sort` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '题目排序',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态:0-禁用,1-启用',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` int(11) UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_category` (`category`),
    KEY `idx_status` (`status`),
    KEY `idx_sort` (`category_sort`, `sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='新人问卷基础题库表';

INSERT INTO `la_couple_question_bank` (`id`, `category`, `category_sort`, `type`, `title`, `placeholder`, `options`, `required`, `sort`, `status`, `create_time`, `update_time`, `delete_time`) VALUES
(1, '新人基础信息', 90, 'text', '新人的年龄分别是？', '例如：新郎28岁，新娘26岁', '[]', 1, 100, 1, 1777600000, 1777600000, NULL),
(2, '新人基础信息', 90, 'text', '两位的家乡或成长经历有什么特别之处？', '可写城市、成长背景、家乡习俗等', '[]', 0, 90, 1, 1777600000, 1777600000, NULL),
(3, '职业与性格', 80, 'text', '两位目前的职业分别是什么？', '例如：新郎工程师，新娘教师', '[]', 1, 100, 1, 1777600000, 1777600000, NULL),
(4, '职业与性格', 80, 'multiple', '你们希望主持人在仪式中呈现哪些性格关键词？', '', '["温柔","幽默","稳重","浪漫","真诚","活泼"]', 0, 90, 1, 1777600000, 1777600000, NULL),
(5, '恋爱经过', 70, 'textarea', '请描述你们第一次认识的经过。', '包括时间、地点、第一印象', '[]', 1, 100, 1, 1777600000, 1777600000, NULL),
(6, '恋爱经过', 70, 'textarea', '你们确认关系或决定相伴的重要节点是什么？', '可写一次谈话、一次旅行或一个瞬间', '[]', 0, 90, 1, 1777600000, 1777600000, NULL),
(7, '求婚故事', 60, 'textarea', '求婚故事或决定结婚的契机是什么？', '如果没有正式求婚，也可以写决定结婚的故事', '[]', 0, 100, 1, 1777600000, 1777600000, NULL),
(8, '家庭成员', 50, 'textarea', '仪式中特别想感谢或提到的家人有哪些？', '可写称呼、名字和感谢原因', '[]', 0, 100, 1, 1777600000, 1777600000, NULL),
(9, '仪式风格', 40, 'single', '你们偏好的仪式风格是？', '', '["温馨感人","轻松幽默","庄重大气","浪漫梦幻","简洁克制"]', 1, 100, 1, 1777600000, 1777600000, NULL),
(10, '仪式风格', 40, 'rating', '你们希望仪式情绪浓度是多少？', '1星克制，5星浓烈', '[]', 0, 90, 1, 1777600000, 1777600000, NULL),
(11, '特殊纪念', 30, 'textarea', '有没有想放进仪式的纪念日、礼物、歌曲或地点？', '例如纪念日、定情信物、共同喜欢的歌', '[]', 0, 100, 1, 1777600000, 1777600000, NULL),
(12, '避讳内容', 20, 'textarea', '有没有不希望在仪式中提及的话题、称呼或玩笑？', '例如前任、年龄、家庭隐私、职业玩笑等', '[]', 0, 100, 1, 1777600000, 1777600000, NULL),
(13, '补充说明', 10, 'textarea', '还有什么希望服务人员提前了解的内容？', '任何对仪式有帮助的信息都可以写在这里', '[]', 0, 100, 1, 1777600000, 1777600000, NULL)
ON DUPLICATE KEY UPDATE
    `category` = VALUES(`category`),
    `category_sort` = VALUES(`category_sort`),
    `type` = VALUES(`type`),
    `title` = VALUES(`title`),
    `placeholder` = VALUES(`placeholder`),
    `options` = VALUES(`options`),
    `required` = VALUES(`required`),
    `sort` = VALUES(`sort`),
    `status` = VALUES(`status`),
    `update_time` = VALUES(`update_time`),
    `delete_time` = VALUES(`delete_time`);

CREATE TABLE IF NOT EXISTS `la_couple_questionnaire` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '问卷配置ID',
    `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID',
    `title` varchar(120) NOT NULL DEFAULT '' COMMENT '问卷标题',
    `description` varchar(500) NOT NULL DEFAULT '' COMMENT '问卷描述',
    `push_mode` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '推送方式:1自动推送,2手动触发',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态:0-禁用,1-启用',
    `draft_questions` text COMMENT '草稿题目(JSON数组)',
    `current_version_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '当前版本ID',
    `published_version_no` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '已发布版本号',
    `published_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最近发布时间',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` int(11) UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_staff_id` (`staff_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='服务人员新人问卷配置表';

CREATE TABLE IF NOT EXISTS `la_couple_questionnaire_version` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '版本ID',
    `questionnaire_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '问卷配置ID',
    `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID',
    `version_no` int(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT '版本号',
    `title` varchar(120) NOT NULL DEFAULT '' COMMENT '问卷标题',
    `description` varchar(500) NOT NULL DEFAULT '' COMMENT '问卷描述',
    `questions` text COMMENT '题目快照(JSON数组)',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_questionnaire_version` (`questionnaire_id`, `version_no`),
    KEY `idx_staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='服务人员新人问卷版本表';

CREATE TABLE IF NOT EXISTS `la_couple_questionnaire_task` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '任务ID',
    `task_sn` varchar(32) NOT NULL DEFAULT '' COMMENT '任务编号',
    `order_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单ID',
    `order_item_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单主服务项ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '客户ID',
    `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '主服务人员ID',
    `questionnaire_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '问卷配置ID',
    `version_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '当前版本ID',
    `version_no` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '当前版本号',
    `title_snapshot` varchar(120) NOT NULL DEFAULT '' COMMENT '标题快照',
    `description_snapshot` varchar(500) NOT NULL DEFAULT '' COMMENT '描述快照',
    `questions_snapshot` text COMMENT '已提交题目快照(JSON数组)',
    `push_mode` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '推送方式:1自动推送,2手动触发',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '填写状态:0待填写,1已填写,2已取消',
    `send_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '推送状态:0待推送,1已推送',
    `send_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '推送次数',
    `send_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '首次推送时间',
    `last_send_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最近推送时间',
    `submit_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '提交时间',
    `submitted_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '提交时间',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` int(11) UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_task_sn` (`task_sn`),
    UNIQUE KEY `uk_order_id` (`order_id`),
    KEY `idx_user_status` (`user_id`, `status`),
    KEY `idx_staff_status` (`staff_id`, `status`),
    KEY `idx_send_status` (`send_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单新人问卷任务表';

CREATE TABLE IF NOT EXISTS `la_couple_questionnaire_answer` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '答案ID',
    `task_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '任务ID',
    `order_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单ID',
    `questionnaire_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '问卷配置ID',
    `version_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '版本ID',
    `version_no` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '版本号',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '客户ID',
    `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID',
    `questions_snapshot` text COMMENT '题目快照(JSON数组)',
    `answers` text COMMENT '答案(JSON数组)',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_task_id` (`task_id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='新人问卷答案表';
