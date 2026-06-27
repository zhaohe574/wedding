ALTER TABLE `la_user`
    DROP COLUMN `risk_rank_update_time`,
    DROP COLUMN `manual_risk_rank`,
    DROP COLUMN `wechat_risk_rank`;

DELETE FROM `la_config`
WHERE `type` = 'feature_switch'
  AND `name` IN (
      'wechat_text_check_enabled',
      'wechat_text_check_profile_prob',
      'wechat_text_check_comment_prob',
      'wechat_text_check_review_as_hit'
  );

DELETE FROM `la_config`
WHERE `type` = 'risk_control'
  AND `name` = 'rank_permissions';
