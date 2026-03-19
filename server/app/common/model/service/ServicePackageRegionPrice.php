<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\service;

use app\common\model\BaseModel;

/**
 * 套餐地区价格
 */
class ServicePackageRegionPrice extends BaseModel
{
    protected $name = 'service_package_region_price';

    public const LEVEL_PROVINCE = 1;
    public const LEVEL_CITY = 2;
    public const LEVEL_DISTRICT = 3;
}
