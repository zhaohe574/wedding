<?php

declare(strict_types=1);

namespace app\adminapi\lists\notification;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\wechat\OaNotificationTemplate;

/**
 * 公众号通知模板列表。
 */
class OaTemplateLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '%like%' => ['scene', 'template_id'],
            '=' => ['audience', 'status'],
        ];
    }

    public function lists(): array
    {
        return OaNotificationTemplate::where($this->searchWhere)
            ->order('sort desc, id asc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
    }

    public function count(): int
    {
        return OaNotificationTemplate::where($this->searchWhere)->count();
    }
}
