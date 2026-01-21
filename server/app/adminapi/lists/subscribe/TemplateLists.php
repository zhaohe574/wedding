<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订阅消息模板列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\subscribe;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\subscribe\SubscribeMessageTemplate;

/**
 * 订阅消息模板列表
 * Class TemplateLists
 * @package app\adminapi\lists\subscribe
 */
class TemplateLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '%like%' => ['name', 'template_id'],
            '=' => ['scene', 'status'],
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $list = SubscribeMessageTemplate::where($this->searchWhere)
            ->field('id, template_id, name, title, scene, status, sort, remark, create_time, update_time')
            ->order('sort', 'desc')
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $item['scene_desc'] = SubscribeMessageTemplate::getSceneDesc($item['scene']);
            $item['status_desc'] = $item['status'] == 1 ? '启用' : '禁用';
            $item['create_time'] = $item['create_time'] ? date('Y-m-d H:i:s', $item['create_time']) : '';
        }

        return $list;
    }

    /**
     * @notes 获取数量
     * @return int
     */
    public function count(): int
    {
        return SubscribeMessageTemplate::where($this->searchWhere)->count();
    }
}
