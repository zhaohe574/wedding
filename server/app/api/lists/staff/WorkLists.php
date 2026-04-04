<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端作品列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\lists\staff;

use app\api\lists\BaseApiDataLists;
use app\common\model\staff\Staff;
use app\common\model\staff\StaffWork;
use app\common\service\FileService;

/**
 * 作品列表（小程序端）
 * Class WorkLists
 * @package app\api\lists\staff
 */
class WorkLists extends BaseApiDataLists
{
    private function baseQuery()
    {
        $query = StaffWork::alias('w')
            ->leftJoin('staff s', 'w.staff_id = s.id')
            ->where('w.delete_time', null)
            ->where('w.is_show', 1)
            ->where('w.audit_status', StaffWork::AUDIT_PASS)
            ->where('s.delete_time', null)
            ->where('s.status', Staff::STATUS_ENABLE)
            ->where('s.audit_status', Staff::AUDIT_PASS);

        if (!empty($this->params['staff_id'])) {
            $query->where('w.staff_id', (int)$this->params['staff_id']);
        }

        if (!empty($this->params['keyword'])) {
            $keyword = trim($this->params['keyword']);
            $query->whereLike('w.title|s.name', '%' . $keyword . '%');
        }

        return $query;
    }

    public function lists(): array
    {
        $field = 'w.id,w.staff_id,w.title,w.cover,w.images,w.view_count,w.like_count,w.create_time,'
            . 's.name as staff_name,s.avatar as staff_avatar';

        $list = $this->baseQuery()
            ->field($field)
            ->order('w.sort desc, w.id desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $item['cover'] = $this->formatUrl($item['cover'] ?? '');
            $item['images'] = $this->normalizeImages($item['images'] ?? '');
            $item['staff_avatar'] = $this->formatUrl($item['staff_avatar'] ?? '');
        }

        return $list;
    }

    public function count(): int
    {
        return $this->baseQuery()->count('w.id');
    }

    private function formatUrl(string $url): string
    {
        return $url ? FileService::getFileUrl($url) : '';
    }

    private function normalizeImages($images): array
    {
        if (is_string($images)) {
            $images = json_decode($images, true) ?: [];
        }
        if (!is_array($images)) {
            return [];
        }
        $result = [];
        foreach ($images as $image) {
            if (is_string($image)) {
                $result[] = $this->formatUrl($image);
                continue;
            }
            if (is_array($image)) {
                $url = $image['url'] ?? $image['path'] ?? '';
                if ($url) {
                    $result[] = $this->formatUrl($url);
                }
            }
        }
        return array_values(array_filter($result));
    }
}
