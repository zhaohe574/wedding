<?php

declare(strict_types=1);

namespace app\adminapi\lists\notification;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\wechat\OaFollower;

/**
 * 公众号粉丝绑定状态列表。
 */
class OaFollowerLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '%like%' => ['openid', 'unionid'],
            '=' => ['user_id', 'follow_status'],
        ];
    }

    public function lists(): array
    {
        $rows = OaFollower::where($this->searchWhere)
            ->order('id desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
        foreach ($rows as &$row) {
            $userId = (int)$row['user_id'];
            $user = $userId ? \app\common\model\user\User::find($userId) : null;
            $admin = $userId ? \app\common\model\auth\Admin::where('user_id', $userId)->find() : null;
            $row['nickname'] = (string)($user->nickname ?? '未绑定平台用户');
            $row['admin_account'] = (string)($admin->account ?? '');
            $row['admin_disabled'] = $admin ? (int)$admin->disable : null;
            $row['bound'] = $user !== null;
            $row['can_receive'] = $user && !(int)$user->is_disable && (int)$row['follow_status'] === 1;
        }
        return $rows;
    }

    public function count(): int
    {
        return OaFollower::where($this->searchWhere)->count();
    }
}
