<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 晒单奖励管理逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\review;

use app\common\logic\BaseLogic;
use app\common\model\review\ReviewShareReward;
use think\facade\Db;

class ReviewShareRewardLogic extends BaseLogic
{
    public static function detail(int $id): array
    {
        $reward = ReviewShareReward::with(['user', 'review'])->find($id);
        if (!$reward) {
            return [];
        }

        $data = $reward->toArray();
        $data['status_text'] = ReviewShareReward::getStatusDesc((int)$reward->status);
        $data['platform_text'] = ReviewShareReward::getPlatformDesc((string)$reward->share_platform);
        return $data;
    }

    public static function audit(array $params): bool
    {
        $reward = ReviewShareReward::find((int)$params['id']);
        if (!$reward) {
            self::setError('晒单奖励记录不存在');
            return false;
        }

        if ((int)$reward->status !== ReviewShareReward::STATUS_PENDING) {
            self::setError('该记录已审核');
            return false;
        }

        Db::startTrans();
        try {
            $result = (int)$params['status'] === ReviewShareReward::STATUS_APPROVED
                ? $reward->approve((int)$params['admin_id'])
                : $reward->reject((int)$params['admin_id'], (string)($params['audit_remark'] ?? ''));

            if (!$result) {
                throw new \Exception('晒单奖励审核失败');
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }
}
