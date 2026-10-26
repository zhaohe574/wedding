<?php

declare(strict_types=1);

namespace app\adminapi\lists\notification;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\wechat\MnpNotificationTemplate;

/**
 * 小程序订阅通知模板列表
 */
class MnpTemplateLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '%like%' => ['scene', 'title', 'template_id'],
            '=' => ['status'],
        ];
    }

    public function lists(): array
    {
        return MnpNotificationTemplate::where($this->searchWhere)
            ->order('sort desc, id asc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
    }

    public function count(): int
    {
        return MnpNotificationTemplate::where($this->searchWhere)->count();
    }
}
