-- =============================================
-- 婚庆服务预约系统 v2.0.0 数据库更新脚本
-- 版本：2.0.0.20260201
--
-- 当前版本的完整安装基线已合并到：
-- server/public/install/db/like.sql
--
-- 本版本新增评价自定义标签与一位小数综合评分，以下语句可重复执行。
-- =============================================

SET @db_name := DATABASE();

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `la_review` ADD COLUMN `custom_tags` text COMMENT ''用户自定义标签 JSON数组'' AFTER `score_effect`',
        'SELECT 1'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'la_review'
      AND COLUMN_NAME = 'custom_tags'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE `la_review`
    MODIFY COLUMN `score` decimal(2,1) UNSIGNED NOT NULL DEFAULT 5.0 COMMENT '综合评分 1-5星';
