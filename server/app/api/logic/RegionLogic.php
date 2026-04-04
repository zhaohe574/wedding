<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\service\ServiceCityPool;
use app\common\service\RegionDataService;

/**
 * 小程序地区逻辑
 */
class RegionLogic extends BaseLogic
{
    /**
     * @notes 获取已开通服务地区树
     * @return array
     */
    public static function serviceTree(): array
    {
        $enabledCities = ServiceCityPool::where('status', 1)
            ->order('sort', 'desc')
            ->order('id', 'desc')
            ->select()
            ->toArray();

        return RegionDataService::getServiceTree($enabledCities);
    }
}
