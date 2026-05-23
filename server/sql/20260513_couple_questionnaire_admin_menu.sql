-- 新人问卷管理员入口热更新 SQL
-- 作用：将新人问卷调整为后台独立一级菜单，迁移基础题库入口，并补齐管理员角色授权。
-- 可重复执行；按 perms/paths 查找菜单，不依赖固定菜单 ID。

SET @now := UNIX_TIMESTAMP();

INSERT INTO `la_system_menu`
(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT 0, 'M', '新人问卷', 'el-icon-Document', 560, '', 'couple-questionnaire', '', '', '', 0, 1, 0, @now, @now
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu`
    WHERE `pid` = 0 AND `type` = 'M' AND `paths` = 'couple-questionnaire'
);

SET @questionnaire_root_id := (
    SELECT `id` FROM `la_system_menu`
    WHERE `pid` = 0 AND `type` = 'M' AND `paths` = 'couple-questionnaire'
    ORDER BY `id` DESC LIMIT 1
);

UPDATE `la_system_menu`
SET `pid` = 0,
    `type` = 'M',
    `name` = '新人问卷',
    `icon` = 'el-icon-Document',
    `sort` = 560,
    `perms` = '',
    `paths` = 'couple-questionnaire',
    `component` = '',
    `selected` = '',
    `params` = '',
    `is_cache` = 0,
    `is_show` = 1,
    `is_disable` = 0,
    `update_time` = @now
WHERE `id` = @questionnaire_root_id;

INSERT INTO `la_system_menu`
(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @questionnaire_root_id, 'C', '问卷列表', '', 100, 'growth.coupleQuestionnaire/lists', 'lists', 'questionnaire/couple_questionnaire/index', '', '', 0, 1, 0, @now, @now
WHERE NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'growth.coupleQuestionnaire/lists');

SET @questionnaire_list_id := (
    SELECT `id` FROM `la_system_menu`
    WHERE `perms` = 'growth.coupleQuestionnaire/lists'
    ORDER BY `id` DESC LIMIT 1
);

UPDATE `la_system_menu`
SET `pid` = @questionnaire_root_id,
    `type` = 'C',
    `name` = '问卷列表',
    `icon` = '',
    `sort` = 100,
    `paths` = 'lists',
    `component` = 'questionnaire/couple_questionnaire/index',
    `selected` = '',
    `params` = '',
    `is_cache` = 0,
    `is_show` = 1,
    `is_disable` = 0,
    `update_time` = @now
WHERE `id` = @questionnaire_list_id;

INSERT INTO `la_system_menu`
(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @questionnaire_root_id, 'C', '配置问卷', '', 95, 'growth.coupleQuestionnaire/detail', 'config', 'questionnaire/couple_questionnaire/config', '/couple-questionnaire/lists', '', 0, 0, 0, @now, @now
WHERE NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'growth.coupleQuestionnaire/detail');

SET @questionnaire_config_id := (
    SELECT `id` FROM `la_system_menu`
    WHERE `perms` = 'growth.coupleQuestionnaire/detail'
    ORDER BY `id` DESC LIMIT 1
);

UPDATE `la_system_menu`
SET `pid` = @questionnaire_root_id,
    `type` = 'C',
    `name` = '配置问卷',
    `icon` = '',
    `sort` = 95,
    `paths` = 'config',
    `component` = 'questionnaire/couple_questionnaire/config',
    `selected` = '/couple-questionnaire/lists',
    `params` = '',
    `is_cache` = 0,
    `is_show` = 0,
    `is_disable` = 0,
    `update_time` = @now
WHERE `id` = @questionnaire_config_id;

INSERT INTO `la_system_menu`
(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @questionnaire_list_id, 'A', '保存问卷草稿', '', 0, 'growth.coupleQuestionnaire/save', '', '', '', '', 0, 0, 0, @now, @now
WHERE NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'growth.coupleQuestionnaire/save');

UPDATE `la_system_menu`
SET `pid` = @questionnaire_list_id,
    `type` = 'A',
    `name` = '保存问卷草稿',
    `is_show` = 0,
    `is_disable` = 0,
    `update_time` = @now
WHERE `perms` = 'growth.coupleQuestionnaire/save';

INSERT INTO `la_system_menu`
(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @questionnaire_list_id, 'A', '发布问卷新版', '', 0, 'growth.coupleQuestionnaire/publish', '', '', '', '', 0, 0, 0, @now, @now
WHERE NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'growth.coupleQuestionnaire/publish');

UPDATE `la_system_menu`
SET `pid` = @questionnaire_list_id,
    `type` = 'A',
    `name` = '发布问卷新版',
    `is_show` = 0,
    `is_disable` = 0,
    `update_time` = @now
WHERE `perms` = 'growth.coupleQuestionnaire/publish';

INSERT INTO `la_system_menu`
(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @questionnaire_root_id, 'C', '基础题库', '', 90, 'growth.coupleQuestionBank/lists', 'bank', 'questionnaire/couple_question_bank/index', '', '', 0, 1, 0, @now, @now
WHERE NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'growth.coupleQuestionBank/lists');

SET @question_bank_id := (
    SELECT `id` FROM `la_system_menu`
    WHERE `perms` = 'growth.coupleQuestionBank/lists'
    ORDER BY `id` DESC LIMIT 1
);

UPDATE `la_system_menu`
SET `pid` = @questionnaire_root_id,
    `type` = 'C',
    `name` = '基础题库',
    `icon` = '',
    `sort` = 90,
    `paths` = 'bank',
    `component` = 'questionnaire/couple_question_bank/index',
    `selected` = '',
    `params` = '',
    `is_cache` = 0,
    `is_show` = 1,
    `is_disable` = 0,
    `update_time` = @now
WHERE `id` = @question_bank_id;

INSERT INTO `la_system_menu`
(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @question_bank_id, 'A', '题库详情', '', 0, 'growth.coupleQuestionBank/detail', '', '', '', '', 0, 0, 0, @now, @now
WHERE NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'growth.coupleQuestionBank/detail');

UPDATE `la_system_menu`
SET `pid` = @question_bank_id,
    `type` = 'A',
    `name` = '题库详情',
    `is_show` = 0,
    `is_disable` = 0,
    `update_time` = @now
WHERE `perms` = 'growth.coupleQuestionBank/detail';

INSERT INTO `la_system_menu`
(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @question_bank_id, 'A', '保存题库', '', 0, 'growth.coupleQuestionBank/save', '', '', '', '', 0, 0, 0, @now, @now
WHERE NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'growth.coupleQuestionBank/save');

UPDATE `la_system_menu`
SET `pid` = @question_bank_id,
    `type` = 'A',
    `name` = '保存题库',
    `is_show` = 0,
    `is_disable` = 0,
    `update_time` = @now
WHERE `perms` = 'growth.coupleQuestionBank/save';

INSERT INTO `la_system_menu`
(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @question_bank_id, 'A', '删除题库', '', 0, 'growth.coupleQuestionBank/delete', '', '', '', '', 0, 0, 0, @now, @now
WHERE NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'growth.coupleQuestionBank/delete');

UPDATE `la_system_menu`
SET `pid` = @question_bank_id,
    `type` = 'A',
    `name` = '删除题库',
    `is_show` = 0,
    `is_disable` = 0,
    `update_time` = @now
WHERE `perms` = 'growth.coupleQuestionBank/delete';

INSERT INTO `la_system_menu`
(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @question_bank_id, 'A', '题库状态', '', 0, 'growth.coupleQuestionBank/changeStatus', '', '', '', '', 0, 0, 0, @now, @now
WHERE NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'growth.coupleQuestionBank/changeStatus');

UPDATE `la_system_menu`
SET `pid` = @question_bank_id,
    `type` = 'A',
    `name` = '题库状态',
    `is_show` = 0,
    `is_disable` = 0,
    `update_time` = @now
WHERE `perms` = 'growth.coupleQuestionBank/changeStatus';

INSERT INTO `la_system_menu`
(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @question_bank_id, 'A', '题库排序', '', 0, 'growth.coupleQuestionBank/sort', '', '', '', '', 0, 0, 0, @now, @now
WHERE NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'growth.coupleQuestionBank/sort');

UPDATE `la_system_menu`
SET `pid` = @question_bank_id,
    `type` = 'A',
    `name` = '题库排序',
    `is_show` = 0,
    `is_disable` = 0,
    `update_time` = @now
WHERE `perms` = 'growth.coupleQuestionBank/sort';

INSERT INTO `la_system_menu`
(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @questionnaire_root_id, 'C', '问卷任务', '', 80, 'growth.coupleQuestionnaire/tasks', 'tasks', 'questionnaire/couple_questionnaire_task/index', '', '', 0, 1, 0, @now, @now
WHERE NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'growth.coupleQuestionnaire/tasks');

SET @questionnaire_task_id := (
    SELECT `id` FROM `la_system_menu`
    WHERE `perms` = 'growth.coupleQuestionnaire/tasks'
    ORDER BY `id` DESC LIMIT 1
);

UPDATE `la_system_menu`
SET `pid` = @questionnaire_root_id,
    `type` = 'C',
    `name` = '问卷任务',
    `icon` = '',
    `sort` = 80,
    `paths` = 'tasks',
    `component` = 'questionnaire/couple_questionnaire_task/index',
    `selected` = '',
    `params` = '',
    `is_cache` = 0,
    `is_show` = 1,
    `is_disable` = 0,
    `update_time` = @now
WHERE `id` = @questionnaire_task_id;

INSERT INTO `la_system_menu`
(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @questionnaire_task_id, 'A', '任务详情', '', 0, 'growth.coupleQuestionnaire/taskDetail', '', '', '', '', 0, 0, 0, @now, @now
WHERE NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'growth.coupleQuestionnaire/taskDetail');

UPDATE `la_system_menu`
SET `pid` = @questionnaire_task_id,
    `type` = 'A',
    `name` = '任务详情',
    `is_show` = 0,
    `is_disable` = 0,
    `update_time` = @now
WHERE `perms` = 'growth.coupleQuestionnaire/taskDetail';

INSERT INTO `la_system_menu`
(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @questionnaire_task_id, 'A', '发送问卷', '', 0, 'growth.coupleQuestionnaire/send', '', '', '', '', 0, 0, 0, @now, @now
WHERE NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'growth.coupleQuestionnaire/send');

UPDATE `la_system_menu`
SET `pid` = @questionnaire_task_id,
    `type` = 'A',
    `name` = '发送问卷',
    `is_show` = 0,
    `is_disable` = 0,
    `update_time` = @now
WHERE `perms` = 'growth.coupleQuestionnaire/send';

UPDATE `la_system_menu`
SET `is_show` = 0,
    `is_disable` = 1,
    `update_time` = @now
WHERE `name` = '新人问卷题库'
  AND `id` <> @question_bank_id;

SET @admin_role_id := (
    SELECT `id` FROM `la_system_role`
    WHERE `name` = '管理员' AND `delete_time` IS NULL
    ORDER BY `id` ASC LIMIT 1
);
SET @admin_role_id := IFNULL(@admin_role_id, 2);

INSERT IGNORE INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT @admin_role_id, `id`
FROM `la_system_menu`
WHERE `id` IN (
    @questionnaire_root_id,
    @questionnaire_list_id,
    @questionnaire_config_id,
    @question_bank_id,
    @questionnaire_task_id
)
OR `perms` IN (
    'growth.coupleQuestionnaire/save',
    'growth.coupleQuestionnaire/publish',
    'growth.coupleQuestionBank/detail',
    'growth.coupleQuestionBank/save',
    'growth.coupleQuestionBank/delete',
    'growth.coupleQuestionBank/changeStatus',
    'growth.coupleQuestionBank/sort',
    'growth.coupleQuestionnaire/taskDetail',
    'growth.coupleQuestionnaire/send'
);
