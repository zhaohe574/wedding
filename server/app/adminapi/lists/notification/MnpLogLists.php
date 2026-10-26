<?php

declare(strict_types=1);

namespace app\adminapi\lists\notification;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\wechat\MnpNotificationLog;

/**
 * 小程序订阅通知发送日志列表
 */
class MnpLogLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '%like%' => ['scene', 'business_type', 'openid', 'template_id'],
            '=' => ['user_id', 'send_status'],
        ];
    }

    public function lists(): array
    {
        $list = MnpNotificationLog::where($this->searchWhere)
            ->order('id desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
        foreach ($list as &$item) {
            $item['payload'] = is_string($item['payload'] ?? null)
                ? (json_decode($item['payload'], true) ?: [])
                : ($item['payload'] ?? []);
        }
        return $list;
    }

    public function count(): int
    {
        return MnpNotificationLog::where($this->searchWhere)->count();
    }
}
