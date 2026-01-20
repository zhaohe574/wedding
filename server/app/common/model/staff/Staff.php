<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

namespace app\common\model\staff;

use app\common\model\BaseModel;
use app\common\model\service\ServiceCategory;
use app\common\model\service\StyleTag;
use app\common\service\FileService;
use think\model\concern\SoftDelete;

/**
 * 工作人员模型
 * Class Staff
 * @package app\common\model\staff
 */
class Staff extends BaseModel
{
    use SoftDelete;

    protected $name = 'staff';
    protected $deleteTime = 'delete_time';

    // 状态
    const STATUS_DISABLE = 0;   // 禁用
    const STATUS_ENABLE = 1;    // 启用

    // 审核状态
    const AUDIT_PENDING = 0;    // 待审核
    const AUDIT_PASS = 1;       // 已通过
    const AUDIT_REJECT = 2;     // 已拒绝

    // 追加字段
    protected $append = [
        'category_name',
        'status_desc',
        'audit_status_desc',
        'tag_names',
    ];

    /**
     * @notes 关联用户
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(\app\common\model\user\User::class, 'user_id', 'id');
    }

    /**
     * @notes 关联服务分类
     * @return \think\model\relation\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id', 'id');
    }

    /**
     * @notes 关联作品
     * @return \think\model\relation\HasMany
     */
    public function works()
    {
        return $this->hasMany(StaffWork::class, 'staff_id', 'id');
    }

    /**
     * @notes 关联证书
     * @return \think\model\relation\HasMany
     */
    public function certificates()
    {
        return $this->hasMany(StaffCertificate::class, 'staff_id', 'id');
    }

    /**
     * @notes 关联标签
     * @return \think\model\relation\BelongsToMany
     */
    public function tags()
    {
        return $this->belongsToMany(
            StyleTag::class,
            StaffTag::class,
            'tag_id',
            'staff_id'
        );
    }

    /**
     * @notes 头像获取器
     * @param $value
     * @return string
     */
    public function getAvatarAttr($value)
    {
        return trim($value) ? FileService::getFileUrl($value) : FileService::getFileUrl(config('project.default_image.user_avatar'));
    }

    /**
     * @notes 头像设置器
     * @param $value
     * @return string
     */
    public function setAvatarAttr($value)
    {
        return trim($value) ? FileService::setFileUrl($value) : '';
    }

    /**
     * @notes 手机号脱敏显示
     * @param $value
     * @return string
     */
    public function getMobileAttr($value)
    {
        if (empty($value)) {
            return '';
        }
        return substr_replace($value, '****', 3, 4);
    }

    /**
     * @notes 分类名称获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getCategoryNameAttr($value, $data)
    {
        $category = ServiceCategory::find($data['category_id'] ?? 0);
        return $category ? $category->name : '';
    }

    /**
     * @notes 状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getStatusDescAttr($value, $data)
    {
        return ($data['status'] ?? 0) ? '启用' : '禁用';
    }

    /**
     * @notes 审核状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getAuditStatusDescAttr($value, $data)
    {
        $statusMap = [
            self::AUDIT_PENDING => '待审核',
            self::AUDIT_PASS => '已通过',
            self::AUDIT_REJECT => '已拒绝',
        ];
        return $statusMap[$data['audit_status'] ?? 0] ?? '未知';
    }

    /**
     * @notes 标签名称获取器
     * @param $value
     * @param $data
     * @return array
     */
    public function getTagNamesAttr($value, $data)
    {
        $tagIds = StaffTag::where('staff_id', $data['id'] ?? 0)->column('tag_id');
        if (empty($tagIds)) {
            return [];
        }
        return StyleTag::whereIn('id', $tagIds)->column('name');
    }

    /**
     * @notes 标签ID获取器
     * @param $value
     * @param $data
     * @return array
     */
    public function getTagIdsAttr($value, $data)
    {
        return StaffTag::where('staff_id', $data['id'] ?? 0)->column('tag_id');
    }

    /**
     * @notes 获取状态选项
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return [
            ['value' => self::STATUS_DISABLE, 'label' => '禁用'],
            ['value' => self::STATUS_ENABLE, 'label' => '启用'],
        ];
    }

    /**
     * @notes 获取审核状态选项
     * @return array
     */
    public static function getAuditStatusOptions(): array
    {
        return [
            ['value' => self::AUDIT_PENDING, 'label' => '待审核'],
            ['value' => self::AUDIT_PASS, 'label' => '已通过'],
            ['value' => self::AUDIT_REJECT, 'label' => '已拒绝'],
        ];
    }

    /**
     * @notes 生成工号
     * @return string
     */
    public static function generateSn(): string
    {
        $prefix = 'S';
        $date = date('Ymd');
        $lastStaff = self::whereDay('create_time')
            ->order('id desc')
            ->find();

        if ($lastStaff && preg_match('/S' . $date . '(\d{4})/', $lastStaff->sn, $matches)) {
            $num = intval($matches[1]) + 1;
        } else {
            $num = 1;
        }

        return $prefix . $date . str_pad($num, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @notes 更新评分
     * @param int $staffId
     * @return void
     */
    public static function updateRating(int $staffId): void
    {
        // 此方法在评价模块实现后调用
        // 根据评价表计算平均分并更新
    }

    /**
     * @notes 增加浏览数
     * @param int $staffId
     * @return void
     */
    public static function incrementViewCount(int $staffId): void
    {
        self::where('id', $staffId)->inc('view_count')->update();
    }
}
