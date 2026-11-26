<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 平台统一话术模板列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\staff;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\staff\StaffCopyTemplate;

/**
 * 平台统一话术模板列表（staff_id=0）
 * Class StaffCopyTemplateLists
 * @package app\adminapi\lists\staff
 */
class StaffCopyTemplateLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 设置搜索条件（使用 buildQuery() 自定义查询）
     * @return array
     */
    public function setSearch(): array
    {
        return [];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        StaffCopyTemplate::ensureSystemTemplatesInitialized();

        $items = [];
        foreach ($this->buildQuery()
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select() as $row) {
            $items[] = $this->formatItem($row);
        }
        return $items;
    }

    /**
     * @notes 获取数量
     * @return int
     */
    public function count(): int
    {
        StaffCopyTemplate::ensureSystemTemplatesInitialized();

        return $this->buildQuery()->count();
    }

    /**
     * @notes 构造平台统一话术查询
     */
    private function buildQuery()
    {
        $query = StaffCopyTemplate::where('staff_id', 0);

        $scene = trim((string)($this->params['scene'] ?? ''));
        if ($scene !== '') {
            $query->where('scene', $scene);
        }

        $status = $this->params['status'] ?? '';
        if ($status !== '') {
            $query->where('status', (int)$status);
        }

        $keyword = trim((string)($this->params['keyword'] ?? ''));
        if ($keyword !== '') {
            $query->whereLike('title|content', '%' . $keyword . '%');
        }

        return $query;
    }

    /**
     * @notes 统一格式化输出
     */
    private function formatItem(StaffCopyTemplate $row): array
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
            'create_time' => $this->formatTimeText($row->getData('create_time') ?: $row->create_time),
            'update_time' => $this->formatTimeText($row->getData('update_time') ?: $row->update_time),
        ];
    }

    /**
     * @notes 时间格式化（兼容原始 int 与 auto_timestamp 已格式化的字符串）
     */
    private function formatTimeText(mixed $value): string
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
