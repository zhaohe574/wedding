<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 附加服务管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\service;

use app\adminapi\controller\BaseAdminController;

/**
 * 附加服务管理控制器
 * Class AddonController
 * @package app\adminapi\controller\service
 */
class AddonController extends BaseAdminController
{
    /**
     * @notes 附加服务列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataEmptyLists();
    }

    /**
     * @notes 附加服务详情
     * @return \think\response\Json
     */
    public function detail()
    {
        return $this->failLegacyAddonOffline();
    }

    /**
     * @notes 添加附加服务
     * @return \think\response\Json
     */
    public function add()
    {
        return $this->failLegacyAddonOffline();
    }

    /**
     * @notes 编辑附加服务
     * @return \think\response\Json
     */
    public function edit()
    {
        return $this->failLegacyAddonOffline();
    }

    /**
     * @notes 删除附加服务
     * @return \think\response\Json
     */
    public function delete()
    {
        return $this->failLegacyAddonOffline();
    }

    /**
     * @notes 修改附加服务状态
     * @return \think\response\Json
     */
    public function changeStatus()
    {
        return $this->failLegacyAddonOffline();
    }

    /**
     * @notes 获取全部附加服务
     * @return \think\response\Json
     */
    public function all()
    {
        return $this->data([]);
    }
}
