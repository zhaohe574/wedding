-- 下线发票管理与评价申诉功能入口

DELETE rm
FROM `la_system_role_menu` rm
INNER JOIN `la_system_menu` m ON m.`id` = rm.`menu_id`
WHERE m.`perms` IN (
    'financial.invoice/lists',
    'finance.invoice/lists',
    'review.reviewAppeal/lists',
    'review.review_appeal/lists',
    'growth.reviewAppeal/lists'
);

DELETE FROM `la_system_menu`
WHERE `perms` IN (
    'financial.invoice/lists',
    'finance.invoice/lists',
    'review.reviewAppeal/lists',
    'review.review_appeal/lists',
    'growth.reviewAppeal/lists'
)
   OR `component` IN (
    'financial/invoice/index',
    'review/appeal/index'
);
