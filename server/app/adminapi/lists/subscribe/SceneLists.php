<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订阅消息场景列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\subscribe;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\subscribe\SubscribeMessageScene;
use app\common\model\subscribe\SubscribeMessageTemplate;

/**
 * 订阅消息场景列表
 * Class SceneLists
 * @package app\adminapi\lists\subscribe
 */
class SceneLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '%like%' => ['name', 'scene'],
            '=' => ['status'],
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $list = SubscribeMessageScene::where($this->searchWhere)
            ->field('id, scene, name, description, template_id, trigger_event, page_path, is_auto, delay_seconds, status, sort, create_time')
            ->order('sort', 'desc')
            ->order('id', 'asc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        // 批量获取模板信息
        $templateIds = array_filter(array_column($list, 'template_id'));
        $templates = [];
        if (!empty($templateIds)) {
            $templateList = SubscribeMessageTemplate::whereIn('template_id', $templateIds)
                ->column('name', 'template_id');
            $templates = $templateList;
        }

        foreach ($list as &$item) {
            $item['template_name'] = $templates[$item['template_id']] ?? '未绑定';
            $item['status_desc'] = $item['status'] == 1 ? '启用' : '禁用';
            $item['is_auto_desc'] = $item['is_auto'] == 1 ? '自动发送' : '手动触发';
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
        return SubscribeMessageScene::where($this->searchWhere)->count();
    }
}
