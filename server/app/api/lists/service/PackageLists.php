<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端套餐列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\lists\service;

use app\api\lists\BaseApiDataLists;
use app\common\model\service\ServicePackage;
use app\common\service\FileService;

/**
 * 套餐列表（小程序端）
 * Class PackageLists
 * @package app\api\lists\service
 */
class PackageLists extends BaseApiDataLists
{
    private function baseQuery()
    {
        $query = ServicePackage::where('delete_time', null)
            ->where('is_show', 1);

        if (!empty($this->params['category_id'])) {
            $query->where('category_id', (int)$this->params['category_id']);
        }

        if (!empty($this->params['keyword'])) {
            $keyword = trim($this->params['keyword']);
            $query->whereLike('name|description', '%' . $keyword . '%');
        }

        return $query;
    }

    public function lists(): array
    {
        $field = 'id,category_id,staff_id,package_type,name,image,price,original_price,description,'
            . 'is_recommend,booking_type,allowed_time_slots';

        $list = $this->baseQuery()
            ->field($field)
            ->order('is_recommend desc, sort desc, id desc')
            ->append(['category_name', 'staff_name'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $item['image'] = $this->formatUrl($item['image'] ?? '');
        }

        return $list;
    }

    public function count(): int
    {
        return $this->baseQuery()->count();
    }

    private function formatUrl(string $url): string
    {
        return $url ? FileService::getFileUrl($url) : '';
    }
}
