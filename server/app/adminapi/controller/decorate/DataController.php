<?php
// +----------------------------------------------------------------------
// | likeadmin快速开发前后端分离管理后台（PHP版）
// +----------------------------------------------------------------------
// | 欢迎阅读学习系统程序代码，建议反馈是我们前进的动力
// | 开源版本可自由商用，可去除界面版权logo
// | gitee下载：https://gitee.com/likeshop_gitee/likeadmin
// | github下载：https://github.com/likeshop-github/likeadmin
// | 访问官网：https://www.likeadmin.cn
// | likeadmin团队 版权所有 拥有最终解释权
// +----------------------------------------------------------------------
// | author: likeadminTeam
// +----------------------------------------------------------------------
namespace app\adminapi\controller\decorate;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\decorate\DecorateDataLogic;
use think\response\Json;

/**
 * 装修-数据
 * Class DataController
 * @package app\adminapi\controller\decorate
 */
class DataController extends BaseAdminController
{
    /**
     * @notes 文章列表
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author mjf
     * @date 2024/3/14 18:13
     */
    public function article(): Json
    {
        $limit = $this->request->get('limit/d', 10);
        $result = DecorateDataLogic::getArticleLists($limit);
        return $this->success('获取成功', $result);
    }

    /**
     * @notes pc设置
     * @return Json
     * @author mjf
     * @date 2024/3/14 18:13
     */
    public function pc(): Json
    {
        $result = DecorateDataLogic::pc();
        return $this->data($result);
    }

    /**
     * @notes 优惠券列表（装修组件选择器）
     * @return Json
     * @author AI
     * @date 2026/01/22
     */
    public function couponList(): Json
    {
        $params = $this->request->get();
        $result = DecorateDataLogic::getCouponList($params);
        return $this->success('获取成功', $result);
    }

    /**
     * @notes 公告列表（装修组件选择器）
     * @return Json
     * @author AI
     * @date 2026/01/22
     */
    public function noticeList(): Json
    {
        $params = $this->request->get();
        $result = DecorateDataLogic::getNoticeList($params);
        return $this->success('获取成功', $result);
    }

    /**
     * @notes 话题列表（装修组件选择器）
     * @return Json
     * @author AI
     * @date 2026/01/22
     */
    public function topicList(): Json
    {
        $params = $this->request->get();
        $result = DecorateDataLogic::getTopicList($params);
        return $this->success('获取成功', $result);
    }

    /**
     * @notes 活动列表（装修组件选择器）
     * @return Json
     * @author AI
     * @date 2026/01/24
     */
    public function activityList(): Json
    {
        $params = $this->request->get();
        $result = DecorateDataLogic::getActivityList($params);
        return $this->success('获取成功', $result);
    }

}