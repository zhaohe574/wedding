<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 跟进记录管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\crm;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\crm\FollowRecordLists;
use app\adminapi\logic\crm\FollowRecordLogic;
use app\adminapi\validate\crm\FollowRecordValidate;

/**
 * 跟进记录管理控制器
 * Class FollowRecordController
 * @package app\adminapi\controller\crm
 */
class FollowRecordController extends BaseAdminController
{
    /**
     * @notes 跟进记录列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new FollowRecordLists());
    }

    /**
     * @notes 跟进记录详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new FollowRecordValidate())->goCheck('detail');
        $result = FollowRecordLogic::detail($params['id']);
        if ($result === null) {
            return $this->fail('跟进记录不存在');
        }
        return $this->data($result);
    }

    /**
     * @notes 添加跟进记录
     * @return \think\response\Json
     */
    public function add()
    {
        $params = (new FollowRecordValidate())->post()->goCheck('add');
        $result = FollowRecordLogic::add($params, $this->adminId);
        if (true === $result) {
            return $this->success('添加成功');
        }
        return $this->fail(FollowRecordLogic::getError());
    }

    /**
     * @notes 编辑跟进记录
     * @return \think\response\Json
     */
    public function edit()
    {
        $params = (new FollowRecordValidate())->post()->goCheck('edit');
        $result = FollowRecordLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功');
        }
        return $this->fail(FollowRecordLogic::getError());
    }

    /**
     * @notes 删除跟进记录
     * @return \think\response\Json
     */
    public function delete()
    {
        $params = (new FollowRecordValidate())->post()->goCheck('delete');
        $result = FollowRecordLogic::delete($params['id']);
        if (true === $result) {
            return $this->success('删除成功');
        }
        return $this->fail(FollowRecordLogic::getError());
    }

    /**
     * @notes 获取客户跟进记录列表
     * @return \think\response\Json
     */
    public function customerRecords()
    {
        $params = (new FollowRecordValidate())->goCheck('customerRecords');
        $limit = $this->request->get('limit', 20);
        $result = FollowRecordLogic::getCustomerRecords($params['customer_id'], (int)$limit);
        return $this->data($result);
    }

    /**
     * @notes 获取顾问今日跟进统计
     * @return \think\response\Json
     */
    public function advisorTodayStats()
    {
        $advisorId = $this->request->get('advisor_id', 0);
        if ($advisorId <= 0) {
            return $this->fail('请指定顾问');
        }
        $result = FollowRecordLogic::getAdvisorTodayStats((int)$advisorId);
        return $this->data($result);
    }

    /**
     * @notes 获取重要跟进记录
     * @return \think\response\Json
     */
    public function importantRecords()
    {
        $params = (new FollowRecordValidate())->goCheck('customerRecords');
        $result = FollowRecordLogic::getImportantRecords($params['customer_id']);
        return $this->data($result);
    }

    /**
     * @notes 获取时间段跟进统计
     * @return \think\response\Json
     */
    public function periodStats()
    {
        $advisorId = $this->request->get('advisor_id', 0);
        $startDate = $this->request->get('start_date', date('Y-m-01'));
        $endDate = $this->request->get('end_date', date('Y-m-d'));
        
        $result = FollowRecordLogic::getPeriodStats((int)$advisorId, $startDate, $endDate);
        return $this->data($result);
    }

    /**
     * @notes 跟进方式选项
     * @return \think\response\Json
     */
    public function typeOptions()
    {
        return $this->data(FollowRecordLogic::getTypeOptions());
    }

    /**
     * @notes 跟进结果选项
     * @return \think\response\Json
     */
    public function resultOptions()
    {
        return $this->data(FollowRecordLogic::getResultOptions());
    }
}
