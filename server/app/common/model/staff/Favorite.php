<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

namespace app\common\model\staff;

use app\common\model\BaseModel;

/**
 * 收藏模型
 * Class Favorite
 * @package app\common\model\staff
 */
class Favorite extends BaseModel
{
    protected $name = 'favorite';

    /**
     * @notes 关联用户
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(\app\common\model\user\User::class, 'user_id', 'id');
    }

    /**
     * @notes 关联工作人员
     * @return \think\model\relation\BelongsTo
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id');
    }

    /**
     * @notes 检查是否已收藏
     * @param int $userId
     * @param int $staffId
     * @return bool
     */
    public static function isFavorited(int $userId, int $staffId): bool
    {
        return self::where('user_id', $userId)
            ->where('staff_id', $staffId)
            ->count() > 0;
    }

    /**
     * @notes 添加收藏
     * @param int $userId
     * @param int $staffId
     * @return bool
     */
    public static function addFavorite(int $userId, int $staffId): bool
    {
        if (self::isFavorited($userId, $staffId)) {
            return true;
        }

        $result = self::create([
            'user_id' => $userId,
            'staff_id' => $staffId,
            'create_time' => time(),
        ]);

        if ($result) {
            // 更新工作人员收藏数
            Staff::where('id', $staffId)->inc('favorite_count')->update();
        }

        return (bool)$result;
    }

    /**
     * @notes 取消收藏
     * @param int $userId
     * @param int $staffId
     * @return bool
     */
    public static function removeFavorite(int $userId, int $staffId): bool
    {
        $result = self::where('user_id', $userId)
            ->where('staff_id', $staffId)
            ->delete();

        if ($result) {
            // 更新工作人员收藏数
            Staff::where('id', $staffId)
                ->where('favorite_count', '>', 0)
                ->dec('favorite_count')
                ->update();
        }

        return (bool)$result;
    }

    /**
     * @notes 切换收藏状态
     * @param int $userId
     * @param int $staffId
     * @return array
     */
    public static function toggleFavorite(int $userId, int $staffId): array
    {
        if (self::isFavorited($userId, $staffId)) {
            self::removeFavorite($userId, $staffId);
            return ['is_favorited' => false, 'message' => '已取消收藏'];
        } else {
            self::addFavorite($userId, $staffId);
            return ['is_favorited' => true, 'message' => '收藏成功'];
        }
    }

    /**
     * @notes 获取用户收藏的工作人员ID列表
     * @param int $userId
     * @return array
     */
    public static function getFavoriteStaffIds(int $userId): array
    {
        return self::where('user_id', $userId)->column('staff_id');
    }
}
