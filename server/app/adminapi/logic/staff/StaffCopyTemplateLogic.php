<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 平台统一话术模板逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\staff;

use app\common\logic\BaseLogic;
use app\common\model\staff\StaffCopyTemplate;

/**
 * 平台统一话术模板逻辑（staff_id=0，仅平台管理员可维护）
 */
class StaffCopyTemplateLogic extends BaseLogic
{
    /**
     * @notes 详情
     */
    public static function detail(int $id): array
    {
        $row = StaffCopyTemplate::where('id', $id)
            ->where('staff_id', 0)
            ->find();
        if (!$row) {
            self::setError('模板不存在');
            return [];
        }
        return self::formatItem($row);
    }

    /**
     * @notes 新增
     */
    public static function add(array $params): bool
    {
        $scene = trim((string)($params['scene'] ?? ''));
        $title = trim((string)($params['title'] ?? ''));
        $content = trim((string)($params['content'] ?? ''));

        if (!isset(StaffCopyTemplate::SCENES[$scene])) {
            self::setError('话术场景参数错误');
            return false;
        }
        if ($title === '' || $content === '') {
            self::setError('请填写模板标题与正文');
            return false;
        }

        $now = time();
        StaffCopyTemplate::create([
            'staff_id' => 0,
            'scene' => $scene,
            'title' => $title,
            'content' => $content,
            'sort' => max(0, min(9999, (int)($params['sort'] ?? 0))),
            'use_count' => 0,
            'status' => (int)($params['status'] ?? 1),
            'create_time' => $now,
            'update_time' => $now,
        ]);
        return true;
    }

    /**
     * @notes 编辑
     */
    public static function edit(array $params): bool
    {
        $row = StaffCopyTemplate::where('id', (int)$params['id'])
            ->where('staff_id', 0)
            ->find();
        if (!$row) {
            self::setError('模板不存在');
            return false;
        }

        $scene = trim((string)($params['scene'] ?? ''));
        $title = trim((string)($params['title'] ?? ''));
        $content = trim((string)($params['content'] ?? ''));

        if (!isset(StaffCopyTemplate::SCENES[$scene])) {
            self::setError('话术场景参数错误');
            return false;
        }
        if ($title === '' || $content === '') {
            self::setError('请填写模板标题与正文');
            return false;
        }

        $row->save([
            'scene' => $scene,
            'title' => $title,
            'content' => $content,
            'sort' => max(0, min(9999, (int)($params['sort'] ?? 0))),
            'status' => (int)($params['status'] ?? $row->status),
            'update_time' => time(),
        ]);
        return true;
    }

    /**
     * @notes 删除
     */
    public static function delete(array $params): bool
    {
        $row = StaffCopyTemplate::where('id', (int)$params['id'])
            ->where('staff_id', 0)
            ->find();
        if (!$row) {
            self::setError('模板不存在');
            return false;
        }
        $row->delete();
        return true;
    }

    /**
     * @notes 启停
     */
    public static function changeStatus(array $params): bool
    {
        $row = StaffCopyTemplate::where('id', (int)$params['id'])
            ->where('staff_id', 0)
            ->find();
        if (!$row) {
            self::setError('模板不存在');
            return false;
        }
        $row->save([
            'status' => (int)($params['status'] ?? StaffCopyTemplate::STATUS_DISABLED),
            'update_time' => time(),
        ]);
        return true;
    }

    /**
     * @notes 场景与动态变量选项
     */
    public static function sceneOptions(): array
    {
        return [
            'scenes' => StaffCopyTemplate::getSceneOptions(),
            'variables' => StaffCopyTemplate::getVariableOptions(),
        ];
    }

    /**
     * @notes 导入内置系统默认话术（幂等）
     */
    public static function importDefaults(): array
    {
        $count = StaffCopyTemplate::importSystemDefaultTemplates();
        return ['imported_count' => $count];
    }

    /**
     * @notes 统一格式化输出
     */
    private static function formatItem(StaffCopyTemplate $row): array
    {
        $scene = (string)$row->scene;
        return [
            'id' => (int)$row->id,
            'scene' => $scene,
            'scene_label' => StaffCopyTemplate::SCENES[$scene] ?? $scene,
            'title' => (string)$row->title,
            'content' => (string)$row->content,
            'sort' => (int)$row->sort,
            'use_count' => (int)$row->use_count,
            'status' => (int)$row->status,
            'create_time' => self::formatTimeText($row->getData('create_time') ?: $row->create_time),
            'update_time' => self::formatTimeText($row->getData('update_time') ?: $row->update_time),
        ];
    }

    /**
     * @notes 时间格式化（兼容原始 int 与 auto_timestamp 已格式化的字符串）
     */
    private static function formatTimeText(mixed $value): string
    {
        if (is_numeric($value)) {
            $ts = (int)$value;
            return $ts > 0 ? date('Y-m-d H:i:s', $ts) : '';
        }
        $str = trim((string)$value);
        if ($str === '' || $str === '0') {
            return '';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $str)) {
            return $str;
        }
        return $str;
    }
}
