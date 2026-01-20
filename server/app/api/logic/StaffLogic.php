<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端工作人员逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\staff\Staff;
use app\common\model\staff\StaffWork;
use app\common\model\staff\Favorite;

/**
 * 工作人员逻辑（小程序端）
 * Class StaffLogic
 * @package app\api\logic
 */
class StaffLogic extends BaseLogic
{
    /**
     * @notes 推荐工作人员
     * @param int $limit
     * @return array
     */
    public static function recommend(int $limit = 10): array
    {
        return Staff::where('delete_time', null)
            ->where('status', Staff::STATUS_ENABLE)
            ->where('is_recommend', 1)
            ->where('audit_status', Staff::AUDIT_PASS)
            ->order('sort desc, rating desc, order_count desc')
            ->field('id, sn, name, avatar, category_id, price, rating, order_count, experience_years, profile')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * @notes 工作人员详情
     * @param int $id
     * @param int $userId
     * @return array
     */
    public static function detail(int $id, int $userId = 0): array
    {
        $staff = Staff::where('id', $id)
            ->where('delete_time', null)
            ->where('status', Staff::STATUS_ENABLE)
            ->where('audit_status', Staff::AUDIT_PASS)
            ->with(['category', 'works' => function($query) {
                $query->where('is_show', 1)->order('is_cover desc, sort desc, id desc')->limit(10);
            }, 'certificates' => function($query) {
                $query->where('verify_status', 1)->limit(10);
            }])
            ->find();

        if (!$staff) {
            return [];
        }

        // 增加浏览量
        Staff::incrementViewCount($id);

        $data = $staff->toArray();

        // 获取标签
        $data['tags'] = \app\common\model\staff\StaffTag::with(['tag'])
            ->where('staff_id', $id)
            ->select()
            ->column('tag.name');

        // 获取套餐
        $data['packages'] = \app\common\model\staff\StaffPackage::with(['package'])
            ->where('staff_id', $id)
            ->select()
            ->toArray();

        // 是否已收藏
        $data['is_favorite'] = false;
        if ($userId > 0) {
            $data['is_favorite'] = Favorite::where('user_id', $userId)
                ->where('staff_id', $id)
                ->find() ? true : false;
        }

        // 移除敏感信息
        unset($data['mobile_full']);

        return $data;
    }

    /**
     * @notes 工作人员作品列表
     * @param int $staffId
     * @return array
     */
    public static function works(int $staffId): array
    {
        return StaffWork::where('staff_id', $staffId)
            ->where('delete_time', null)
            ->where('is_show', 1)
            ->order('is_cover desc, sort desc, id desc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 收藏/取消收藏
     * @param int $staffId
     * @param int $userId
     * @return bool true=已收藏, false=已取消收藏
     */
    public static function toggleFavorite(int $staffId, int $userId): bool
    {
        return Favorite::toggleFavorite($userId, $staffId);
    }

    /**
     * @notes 我收藏的工作人员
     * @param int $userId
     * @return array
     */
    public static function myFavorites(int $userId): array
    {
        $favoriteIds = Favorite::where('user_id', $userId)
            ->order('id desc')
            ->column('staff_id');

        if (empty($favoriteIds)) {
            return [];
        }

        return Staff::whereIn('id', $favoriteIds)
            ->where('delete_time', null)
            ->where('status', Staff::STATUS_ENABLE)
            ->field('id, sn, name, avatar, category_id, price, rating, order_count')
            ->select()
            ->toArray();
    }
}
