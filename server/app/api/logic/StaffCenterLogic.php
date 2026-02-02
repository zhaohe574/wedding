<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员中心逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\dynamic\Dynamic;
use app\common\model\schedule\Schedule;
use app\common\model\service\ServicePackage;
use app\common\model\staff\Staff;
use app\common\model\staff\StaffPackage;
use app\common\model\staff\StaffWork;
use app\common\service\StaffService;
use think\facade\Db;

/**
 * 服务人员中心逻辑
 * Class StaffCenterLogic
 * @package app\api\logic
 */
class StaffCenterLogic extends BaseLogic
{
    /**
     * @notes 获取服务人员ID
     */
    public static function getStaffId(int $userId): int
    {
        return StaffService::getStaffIdByUserId($userId);
    }

    /**
     * @notes 获取个人资料
     */
    public static function profile(int $userId): array
    {
        $staff = Staff::where('user_id', $userId)->find();
        if (!$staff) {
            self::setError('未绑定服务人员');
            return [];
        }

        $data = $staff->toArray();
        $fullMobile = $staff->getData('mobile_full') ?: $staff->getData('mobile');
        $data['mobile'] = $fullMobile;
        $data['mobile_full'] = $fullMobile;

        return $data;
    }

    /**
     * @notes 更新个人资料
     */
    public static function updateProfile(int $userId, array $params): bool
    {
        $staff = Staff::where('user_id', $userId)->find();
        if (!$staff) {
            self::setError('未绑定服务人员');
            return false;
        }

        if (!empty($params['mobile'])) {
            $params['mobile_full'] = $params['mobile'];
        }

        $update = [
            'name' => $params['name'],
            'avatar' => $params['avatar'] ?? $staff->avatar,
            'mobile' => $params['mobile'] ?? $staff->getData('mobile_full') ?: $staff->getData('mobile'),
            'mobile_full' => $params['mobile_full'] ?? ($staff->getData('mobile_full') ?: $staff->getData('mobile')),
            'category_id' => $params['category_id'] ?? $staff->category_id,
            'price' => $params['price'] ?? $staff->price,
            'experience_years' => $params['experience_years'] ?? $staff->experience_years,
            'profile' => $params['profile'] ?? $staff->profile,
            'service_desc' => $params['service_desc'] ?? $staff->service_desc,
            'update_time' => time(),
        ];

        $staff->save($update);
        return true;
    }

    /**
     * @notes 作品列表
     */
    public static function workLists(int $userId, array $params): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $query = StaffWork::where('staff_id', $staffId)
            ->order('sort', 'desc')
            ->order('id', 'desc');

        return $query->paginate($params['page_size'] ?? 10)->toArray();
    }

    /**
     * @notes 作品详情
     */
    public static function workDetail(int $userId, int $id): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $work = StaffWork::where('id', $id)->where('staff_id', $staffId)->find();
        if (!$work) {
            self::setError('作品不存在');
            return [];
        }

        return $work->toArray();
    }

    /**
     * @notes 添加作品
     */
    public static function workAdd(int $userId, array $params): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        StaffWork::create([
            'staff_id' => $staffId,
            'title' => $params['title'],
            'cover' => $params['cover'] ?? '',
            'images' => $params['images'] ?? [],
            'video' => $params['video'] ?? '',
            'description' => $params['description'] ?? '',
            'shoot_date' => $params['shoot_date'] ?? null,
            'location' => $params['location'] ?? '',
            'sort' => $params['sort'] ?? 0,
            'is_show' => $params['is_show'] ?? 1,
            'audit_status' => StaffWork::AUDIT_PENDING,
            'create_time' => time(),
            'update_time' => time(),
        ]);

        return true;
    }

    /**
     * @notes 编辑作品
     */
    public static function workEdit(int $userId, int $id, array $params): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $work = StaffWork::where('id', $id)->where('staff_id', $staffId)->find();
        if (!$work) {
            self::setError('作品不存在');
            return false;
        }

        $work->save([
            'title' => $params['title'] ?? $work->title,
            'cover' => $params['cover'] ?? $work->cover,
            'images' => $params['images'] ?? $work->images,
            'video' => $params['video'] ?? $work->video,
            'description' => $params['description'] ?? $work->description,
            'shoot_date' => $params['shoot_date'] ?? $work->shoot_date,
            'location' => $params['location'] ?? $work->location,
            'sort' => $params['sort'] ?? $work->sort,
            'is_show' => $params['is_show'] ?? $work->is_show,
            'audit_status' => StaffWork::AUDIT_PENDING,
            'update_time' => time(),
        ]);

        return true;
    }

    /**
     * @notes 删除作品
     */
    public static function workDelete(int $userId, int $id): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $work = StaffWork::where('id', $id)->where('staff_id', $staffId)->find();
        if (!$work) {
            self::setError('作品不存在');
            return false;
        }

        $work->delete();
        return true;
    }

    /**
     * @notes 套餐列表
     */
    public static function packageLists(int $userId): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $configured = StaffPackage::where('staff_id', $staffId)
            ->with(['package'])
            ->select()
            ->toArray();
        $configuredIds = array_column($configured, 'package_id');

        $available = ServicePackage::where('package_type', ServicePackage::TYPE_GLOBAL)
            ->where('delete_time', null)
            ->where('is_show', 1)
            ->when(!empty($configuredIds), function ($query) use ($configuredIds) {
                $query->whereNotIn('id', $configuredIds);
            })
            ->select()
            ->toArray();

        return [
            'configured' => $configured,
            'available' => $available,
        ];
    }

    /**
     * @notes 关联套餐
     */
    public static function packageAdd(int $userId, int $packageId): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $package = ServicePackage::find($packageId);
        if (!$package || $package->package_type != ServicePackage::TYPE_GLOBAL) {
            self::setError('套餐不存在或不可关联');
            return false;
        }

        $exists = StaffPackage::where('staff_id', $staffId)
            ->where('package_id', $packageId)
            ->find();
        if ($exists) {
            self::setError('套餐已关联');
            return false;
        }

        StaffPackage::create([
            'staff_id' => $staffId,
            'package_id' => $packageId,
            'price' => $package->price ?? 0,
            'original_price' => $package->original_price ?? null,
            'status' => 1,
            'booking_type' => $package->booking_type ?? null,
            'allowed_time_slots' => $package->allowed_time_slots ?? null,
            'create_time' => time(),
            'update_time' => time(),
        ]);

        return true;
    }

    /**
     * @notes 更新套餐配置
     */
    public static function packageUpdate(int $userId, int $packageId, array $params): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        return StaffPackage::updatePackageConfig($staffId, $packageId, $params);
    }

    /**
     * @notes 移除套餐
     */
    public static function packageRemove(int $userId, int $packageId): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        return StaffPackage::where('staff_id', $staffId)
            ->where('package_id', $packageId)
            ->delete() > 0;
    }

    /**
     * @notes 月度档期
     */
    public static function scheduleMonth(int $userId, int $year, int $month): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        return [
            'year' => $year,
            'month' => $month,
            'schedules' => Schedule::getMonthSchedule($staffId, $year, $month),
        ];
    }

    /**
     * @notes 设置档期状态
     */
    public static function scheduleSetStatus(int $userId, array $params): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $date = $params['date'];
        $timeSlot = (int) ($params['time_slot'] ?? 0);
        $status = (int) $params['status'];

        $schedule = Schedule::where('staff_id', $staffId)
            ->where('schedule_date', $date)
            ->where('time_slot', $timeSlot)
            ->find();

        if ($schedule && in_array((int) $schedule->status, [Schedule::STATUS_BOOKED, Schedule::STATUS_LOCKED, Schedule::STATUS_RESERVED], true)) {
            self::setError('该档期状态不可修改');
            return false;
        }

        if ($schedule) {
            $schedule->status = $status;
            $schedule->remark = $params['remark'] ?? '';
            $schedule->update_time = time();
            $schedule->save();
        } else {
            Schedule::create([
                'staff_id' => $staffId,
                'schedule_date' => $date,
                'time_slot' => $timeSlot,
                'status' => $status,
                'remark' => $params['remark'] ?? '',
                'version' => 1,
                'create_time' => time(),
                'update_time' => time(),
            ]);
        }

        return true;
    }

    /**
     * @notes 动态列表
     */
    public static function dynamicLists(int $userId, array $params): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $query = Dynamic::where('user_type', Dynamic::USER_TYPE_STAFF)
            ->where('staff_id', $staffId)
            ->order('create_time', 'desc');

        return $query->paginate($params['page_size'] ?? 10)->toArray();
    }

    /**
     * @notes 发布动态
     */
    public static function dynamicAdd(int $userId, array $params): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        [$success, $message] = Dynamic::publish($staffId, Dynamic::USER_TYPE_STAFF, [
            'staff_id' => $staffId,
            'dynamic_type' => $params['dynamic_type'] ?? Dynamic::TYPE_IMAGE_TEXT,
            'title' => $params['title'] ?? '',
            'content' => $params['content'],
            'images' => $params['images'] ?? [],
            'video_url' => $params['video_url'] ?? '',
            'video_cover' => $params['video_cover'] ?? '',
            'location' => $params['location'] ?? '',
            'latitude' => $params['latitude'] ?? 0,
            'longitude' => $params['longitude'] ?? 0,
            'tags' => $params['tags'] ?? '',
            'allow_comment' => $params['allow_comment'] ?? 1,
            'order_id' => $params['order_id'] ?? 0,
        ]);

        if (!$success) {
            self::setError($message);
            return false;
        }
        return true;
    }

    /**
     * @notes 编辑动态
     */
    public static function dynamicEdit(int $userId, int $id, array $params): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $dynamic = Dynamic::where('id', $id)
            ->where('staff_id', $staffId)
            ->where('user_type', Dynamic::USER_TYPE_STAFF)
            ->find();
        if (!$dynamic) {
            self::setError('动态不存在');
            return false;
        }

        $dynamic->dynamic_type = $params['dynamic_type'] ?? $dynamic->dynamic_type;
        $dynamic->title = $params['title'] ?? $dynamic->title;
        $dynamic->content = $params['content'] ?? $dynamic->content;
        $dynamic->images = $params['images'] ?? $dynamic->images;
        $dynamic->video_url = $params['video_url'] ?? $dynamic->video_url;
        $dynamic->video_cover = $params['video_cover'] ?? $dynamic->video_cover;
        $dynamic->location = $params['location'] ?? $dynamic->location;
        $dynamic->latitude = $params['latitude'] ?? $dynamic->latitude;
        $dynamic->longitude = $params['longitude'] ?? $dynamic->longitude;
        $dynamic->tags = is_array($params['tags'] ?? '') ? implode(',', $params['tags']) : ($params['tags'] ?? $dynamic->tags);
        $dynamic->allow_comment = $params['allow_comment'] ?? $dynamic->allow_comment;
        $dynamic->status = Dynamic::STATUS_PENDING;
        $dynamic->update_time = time();
        $dynamic->save();

        return true;
    }

    /**
     * @notes 删除动态
     */
    public static function dynamicDelete(int $userId, int $id): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $dynamic = Dynamic::where('id', $id)
            ->where('staff_id', $staffId)
            ->where('user_type', Dynamic::USER_TYPE_STAFF)
            ->find();
        if (!$dynamic) {
            self::setError('动态不存在');
            return false;
        }

        $dynamic->delete();
        return true;
    }
}
