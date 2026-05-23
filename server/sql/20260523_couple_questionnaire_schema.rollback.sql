-- Rollback: 20260523_couple_questionnaire_schema
-- Owner: architect/backend
-- Preconditions:
--   1. 仅在确认未产生线上新人问卷配置、任务、答案数据时执行。
--   2. 已备份当前库。
-- Notes:
--   - 若已上线产生业务数据，禁止 DROP 表；请回滚应用代码并保留数据。

DROP TABLE IF EXISTS `la_couple_questionnaire_answer`;
DROP TABLE IF EXISTS `la_couple_questionnaire_task`;
DROP TABLE IF EXISTS `la_couple_questionnaire_version`;
DROP TABLE IF EXISTS `la_couple_questionnaire`;
DROP TABLE IF EXISTS `la_couple_question_bank`;
