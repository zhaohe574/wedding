<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 工作人员管理逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\staff;

use app\common\logic\BaseLogic;
use app\common\model\staff\Staff;
use app\common\model\staff\StaffTag;
use app\common\model\staff\StaffPackage;
use app\common\model\staff\StaffWork;
use app\common\model\staff\StaffCertificate;
use think\facade\Db;

/**
 * 工作人员管理逻辑
 * Class StaffLogic
 * @package app\adminapi\logic\staff
 */
class StaffLogic extends BaseLogic
{
    /**
     * @notes 获取工作人员详情
     * @param int $id
     * @return array
     */
    public static function detail(int $id): array
    {
        $staff = Staff::with(['category', 'works', 'certificates'])
            ->find($id);

        if (!$staff) {
            return [];
        }

        $data = $staff->toArray();
        $data['tag_ids'] = StaffTag::getTagIds($id);
        $data['packages'] = StaffPackage::getPackages($id);
        $data['mobile_full'] = $staff->getData('mobile_full');

        return $data;
    }

    /**
     * @notes 添加工作人员
     * @param array $params
     * @return bool
     */
    public static function add(array $params): bool
    {
        Db::startTrans();
        try {
            // 生成工号
            $params['sn'] = Staff::generateSn();
            $params['create_time'] = time();
            $params['update_time'] = time();

            // 处理手机号
            if (!empty($params['mobile'])) {
                $params['mobile_full'] = $params['mobile'];
            }

            // 创建工作人员
            $staff = Staff::create([
                'sn' => $params['sn'],
                'name' => $params['name'],
                'avatar' => $params['avatar'] ?? '',
                'mobile' => $params['mobile'] ?? '',
                'mobile_full' => $params['mobile_full'] ?? '',
                'category_id' => $params['category_id'] ?? 0,
                'price' => $params['price'] ?? 0,
                'experience_years' => $params['experience_years'] ?? 0,
                'profile' => $params['profile'] ?? '',
                'service_desc' => $params['service_desc'] ?? '',
                'sort' => $params['sort'] ?? 0,
                'is_recommend' => $params['is_recommend'] ?? 0,
                'status' => $params['status'] ?? 1,
                'audit_status' => Staff::AUDIT_PASS,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            // 设置标签
            if (!empty($params['tag_ids'])) {
                StaffTag::setTags($staff->id, $params['tag_ids']);
            }

            // 设置套餐
            if (!empty($params['packages'])) {
                StaffPackage::setPackages($staff->id, $params['packages']);
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 编辑工作人员
     * @param array $params
     * @return bool
     */
    public static function edit(array $params): bool
    {
        Db::startTrans();
        try {
            $staff = Staff::find($params['id']);
            if (!$staff) {
                throw new \Exception('工作人员不存在');
            }

            // 处理手机号
            if (!empty($params['mobile'])) {
                $params['mobile_full'] = $params['mobile'];
            }

            // 更新工作人员信息
            $staff->save([
                'name' => $params['name'],
                'avatar' => $params['avatar'] ?? $staff->avatar,
                'mobile' => $params['mobile'] ?? $staff->mobile,
                'mobile_full' => $params['mobile_full'] ?? $staff->mobile_full,
                'category_id' => $params['category_id'] ?? $staff->category_id,
                'price' => $params['price'] ?? $staff->price,
                'experience_years' => $params['experience_years'] ?? $staff->experience_years,
                'profile' => $params['profile'] ?? $staff->profile,
                'service_desc' => $params['service_desc'] ?? $staff->service_desc,
                'sort' => $params['sort'] ?? $staff->sort,
                'is_recommend' => $params['is_recommend'] ?? $staff->is_recommend,
                'status' => $params['status'] ?? $staff->status,
                'update_time' => time(),
            ]);

            // 更新标签
            if (isset($params['tag_ids'])) {
                StaffTag::setTags($params['id'], $params['tag_ids']);
            }

            // 更新套餐
            if (isset($params['packages'])) {
                StaffPackage::setPackages($params['id'], $params['packages']);
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除工作人员
     * @param array $params
     * @return bool
     */
    public static function delete(array $params): bool
    {
        return Staff::destroy($params['id']);
    }

    /**
     * @notes 修改工作人员状态
     * @param array $params
     * @return bool
     */
    public static function changeStatus(array $params): bool
    {
        try {
            Staff::update([
                'id' => $params['id'],
                'status' => $params['status'],
                'update_time' => time(),
            ]);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 获取所有工作人员
     * @param array $params
     * @return array
     */
    public static function getAll(array $params = []): array
    {
        $query = Staff::where('delete_time', null)
            ->where('status', Staff::STATUS_ENABLE);

        if (!empty($params['category_id'])) {
            $query->where('category_id', $params['category_id']);
        }

        return $query->order('sort desc, id desc')
            ->field('id, sn, name, avatar, category_id, price, rating')
            ->select()
            ->toArray();
    }

    /**
     * @notes 统计数据
     * @return array
     */
    public static function statistics(): array
    {
        $total = Staff::where('delete_time', null)->count();
        $enable = Staff::where('delete_time', null)->where('status', Staff::STATUS_ENABLE)->count();
        $disable = Staff::where('delete_time', null)->where('status', Staff::STATUS_DISABLE)->count();
        $recommend = Staff::where('delete_time', null)->where('is_recommend', 1)->count();

        return [
            'total' => $total,
            'enable' => $enable,
            'disable' => $disable,
            'recommend' => $recommend,
        ];
    }
}
