-- 新增移动端开屏广告装修页默认配置（默认关闭，幂等执行）。
SET NAMES utf8mb4;

INSERT INTO `la_decorate_page` (`id`, `type`, `name`, `data`, `meta`, `create_time`, `update_time`)
SELECT 6, 6, '开屏广告页',
       '[{"id":"splash_ad_default","title":"开屏广告页","name":"splash-ad","disabled":1,"content":{"enabled":0,"image":"","auto_enter_enabled":1,"auto_seconds":3,"frequency":"session","button_text":"点击进入"},"styles":{"button_bg_color":"#FFFFFF","button_text_color":"#333333","button_border_color":"#FFFFFF","button_border_radius":24}}]',
       '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_decorate_page` WHERE `id` = 6 OR `type` = 6
);
