<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 工作人员列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\staff;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\lists\ListsSortInterface;
use app\common\lists\ListsExcelInterface;
use app\common\model\staff\Staff;
use app\common\model\service\ServiceCategory;

/**
 * 工作人员列表
 * Class StaffLists
 * @package app\adminapi\lists\staff
 */
class StaffLists extends BaseAdminDataLists implements ListsSearchInterface, ListsSortInterface, ListsExcelInterface
{
    /**
     * @notes 设置搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '%like%' => ['name', 'sn', 'mobile_full'],
            '=' => ['category_id', 'status', 'is_recommend', 'audit_status'],
        ];
    }

    /**
     * @notes 设置排序字段
     * @return array
     */
    public function setSortFields(): array
    {
        return [
            'id' => 'id',
            'sort' => 'sort',
            'price' => 'price',
            'rating' => 'rating',
            'order_count' => 'order_count',
            'create_time' => 'create_time',
        ];
    }

    /**
     * @notes 设置默认排序
     * @return array
     */
    public function setDefaultOrder(): array
    {
        return ['sort' => 'desc', 'id' => 'desc'];
    }

    /**
     * @notes 获取列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function lists(): array
    {
        $query = Staff::where($this->searchWhere);

        $staffScopeId = $this->getStaffScopeId();
        if ($staffScopeId > 0) {
            $query->where('id', $staffScopeId);
        }

        $lists = $query
            ->field([
                'id', 'sn', 'name', 'avatar', 'mobile', 'category_id',
                'price', 'experience_years', 'rating', 'order_count',
                'review_count', 'favorite_count', 'view_count',
                'sort', 'is_recommend', 'status', 'audit_status',
                'create_time', 'update_time'
            ])
            ->order($this->sortOrder)
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        // 获取分类名称
        $categoryIds = array_column($lists, 'category_id');
        $categories = ServiceCategory::whereIn('id', $categoryIds)
            ->column('name', 'id');

        foreach ($lists as &$item) {
            $item['category_name'] = $categories[$item['category_id']] ?? '';
            $item['status_desc'] = $item['status'] ? '启用' : '禁用';
            $item['is_recommend_desc'] = $item['is_recommend'] ? '是' : '否';
            $item['audit_status_desc'] = ['待审核', '已通过', '已拒绝'][$item['audit_status']] ?? '未知';
        }

        return $lists;
    }

    /**
     * @notes 获取数量
     * @return int
     */
    public function count(): int
    {
        $query = Staff::where($this->searchWhere);
        $staffScopeId = $this->getStaffScopeId();
        if ($staffScopeId > 0) {
            $query->where('id', $staffScopeId);
        }
        return $query->count();
    }

    /**
     * @notes 设置导出字段
     * @return array
     */
    public function setExcelFields(): array
    {
        return [
            'sn' => '工号',
            'name' => '姓名',
            'category_name' => '分类',
            'mobile' => '手机号',
            'price' => '起步价',
            'experience_years' => '从业年限',
            'rating' => '评分',
            'order_count' => '接单数',
            'review_count' => '评价数',
            'status_desc' => '状态',
            'create_time' => '创建时间',
        ];
    }

    /**
     * @notes 设置导出文件名
     * @return string
     */
    public function setFileName(): string
    {
        return '工作人员列表';
    }
}
