-- 服务人员结算与退款互斥修复
-- 1. 将历史非订单项结算的 0 归一为空值，避免唯一键误伤手工结算。
-- 2. 清理历史重复结算记录后，给订单项维度增加唯一约束，防止并发重复生成结算。

UPDATE `la_staff_settlement`
SET `order_item_id` = NULL
WHERE `order_item_id` = 0;

DELETE s1
FROM `la_staff_settlement` s1
INNER JOIN `la_staff_settlement` s2
    ON s1.`order_item_id` = s2.`order_item_id`
    AND s1.`id` > s2.`id`
WHERE s1.`order_item_id` > 0
  AND s1.`status` IN (0, 2, 3)
  AND s2.`order_item_id` > 0;

ALTER TABLE `la_staff_settlement`
    MODIFY COLUMN `order_item_id` INT UNSIGNED DEFAULT NULL COMMENT '订单项ID';

ALTER TABLE `la_staff_settlement`
    ADD UNIQUE KEY `uk_order_item_id` (`order_item_id`);
