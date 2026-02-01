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
