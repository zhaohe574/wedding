<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端 v2 聚合接口
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\controller;

use app\api\logic\V2Logic;
use think\facade\Log;

/**
 * 小程序端 v2 聚合控制器。
 *
 * 目的：把「一个页面 4~8 次请求」压到 1 次。
 *   /api/v2/home            首页      4 → 1
 *   /api/v2/me              我的      8 → 1
 *   /api/v2/staff?id=1      人员详情  6 → 1
 *   /api/v2/order?id=1      订单详情  5 → 1
 *   /api/v2/bookingPreview  下单预检  2 → 1（并新增报价指纹）
 *
 * 兼容策略：**纯新增，不改动任何 v1 接口**。
 *   PC 端、管理后台、老 uniapp 继续走 v1，零影响；新小程序按页面逐块切换到 v2，
 *   任一块不可用时客户端会自动降级回 v1（见各页面 api 调用处的 try/catch）。
 *
 * 路由按 ThinkPHP 约定自动解析，无需在 route/ 中注册：
 *   /api/{snake(控制器)}/{action}
 */
class V2Controller extends BaseApiController
{
    /**
     * 免登录：首页与人员详情。带 token 时仍会带出未读数 / 收藏状态。
     * me / order / bookingPreview 必须登录。
     */
    public array $notNeedLogin = ['home', 'staff'];

    /**
     * @notes 首页聚合
     * @return \think\response\Json
     */
    public function home()
    {
        try {
            return $this->data(V2Logic::home($this->userId));
        } catch (\Throwable $e) {
            Log::error('[v2.home] ' . $e->getMessage());
            return $this->fail('首页加载失败');
        }
    }

    /**
     * @notes 我的聚合
     * @return \think\response\Json
     */
    public function me()
    {
        try {
            return $this->data(V2Logic::me($this->userId, $this->userInfo));
        } catch (\Throwable $e) {
            Log::error('[v2.me] ' . $e->getMessage());
            return $this->fail('个人信息加载失败');
        }
    }

    /**
     * @notes 人员详情聚合
     * @return \think\response\Json
     */
    public function staff()
    {
        $staffId = (int)$this->request->get('id', 0);
        if ($staffId <= 0) {
            return $this->fail('参数错误');
        }

        try {
            $result = V2Logic::staff($staffId, $this->userId, $this->request->get());
        } catch (\Throwable $e) {
            Log::error('[v2.staff] ' . $e->getMessage());
            return $this->fail('人员详情加载失败');
        }

        if ($result === null) {
            return $this->fail('服务人员不存在或已下架');
        }
        return $this->data($result);
    }

    /**
     * @notes 订单详情聚合
     * @return \think\response\Json
     */
    public function order()
    {
        $orderId = (int)$this->request->get('id', 0);
        if ($orderId <= 0) {
            return $this->fail('参数错误');
        }

        try {
            $result = V2Logic::order($orderId, $this->userId);
        } catch (\Throwable $e) {
            Log::error('[v2.order] ' . $e->getMessage());
            return $this->fail('订单加载失败');
        }

        if ($result === null) {
            return $this->fail('订单不存在');
        }
        return $this->data($result);
    }

    /**
     * @notes 下单预检（报价 + 校验 + 报价指纹）
     * @return \think\response\Json
     */
    public function bookingPreview()
    {
        try {
            return $this->data(V2Logic::bookingPreview($this->userId, $this->request->post()));
        } catch (\Throwable $e) {
            Log::error('[v2.bookingPreview] ' . $e->getMessage());
            return $this->fail('价格计算失败');
        }
    }
}
