<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 动态收藏模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\dynamic;

use app\common\model\BaseModel;

/**
 * 动态收藏模型
 * Class DynamicCollect
 * @package app\common\model\dynamic
 */
class DynamicCollect extends BaseModel
{
    protected $name = 'dynamic_collect';

    /**
     * @notes 关联动态
     * @return \think\model\relation\BelongsTo
     */
    public function dynamic()
    {
        return $this->belongsTo(Dynamic::class, 'dynamic_id', 'id');
    }

    /**
     * @notes 收藏/取消收藏
     * @param int $userId
     * @param int $dynamicId
     * @return array [bool $success, string $message, bool $isCollected]
     */
    public static function toggleCollect(int $userId, int $dynamicId): array
    {
        $dynamic = Dynamic::find($dynamicId);
        if (!$dynamic) {
            return [false, '动态不存在', false];
        }

        $exists = self::where('user_id', $userId)
            ->where('dynamic_id', $dynamicId)
            ->find();

        if ($exists) {
            // 取消收藏
            $exists->delete();
            Dynamic::where('id', $dynamicId)->dec('collect_count')->update();
            return [true, '取消收藏', false];
        } else {
            // 收藏
            self::create([
                'user_id' => $userId,
                'dynamic_id' => $dynamicId,
                'create_time' => time(),
            ]);
            Dynamic::where('id', $dynamicId)->inc('collect_count')->update();
            return [true, '收藏成功', true];
        }
    }

    /**
     * @notes 检查是否已收藏
     * @param int $userId
     * @param int $dynamicId
     * @return bool
     */
    public static function isCollected(int $userId, int $dynamicId): bool
    {
        return self::where('user_id', $userId)
            ->where('dynamic_id', $dynamicId)
            ->count() > 0;
    }

    /**
     * @notes 获取用户收藏列表
     * @param int $userId
     * @param array $params
     * @return array
     */
    public static function getUserCollections(int $userId, array $params = []): array
    {
        return self::where('user_id', $userId)
            ->with(['dynamic' => function ($q) {
                $q->with(['user' => function ($q2) {
                    $q2->field('id, nickname, avatar');
                }]);
            }])
            ->order('create_time', 'desc')
            ->paginate((int)($params['page_size'] ?? 10))
            ->toArray();
    }
}
