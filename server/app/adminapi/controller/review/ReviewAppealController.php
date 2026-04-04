<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 评价申诉管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\review;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\review\ReviewAppealLists;
use app\common\model\review\ReviewAppeal;
use app\common\model\review\Review;

/**
 * 评价申诉管理控制器
 * Class ReviewAppealController
 * @package app\adminapi\controller\review
 */
class ReviewAppealController extends BaseAdminController
{
    /**
     * @notes 申诉列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new ReviewAppealLists());
    }

    /**
     * @notes 申诉详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $id = $this->request->get('id');
        $appeal = ReviewAppeal::with(['review', 'review.user', 'review.staff', 'appealUser', 'appealStaff'])
            ->find($id);
        
        if (!$appeal) {
            return $this->fail('申诉不存在');
        }

        $data = $appeal->toArray();
        $data['appeal_type_text'] = ReviewAppeal::getTypeDesc($data['appeal_type']);
        $data['status_text'] = ReviewAppeal::getStatusDesc($data['status']);
        $data['handle_action_text'] = ReviewAppeal::getActionDesc($data['handle_action']);

        return $this->success('', $data);
    }

    /**
     * @notes 处理申诉
     * @return \think\response\Json
     */
    public function handle()
    {
        $params = $this->request->post();
        
        if (empty($params['id'])) {
            return $this->fail('申诉ID不能为空');
        }
        if (!isset($params['status']) || !in_array($params['status'], [1, 2])) {
            return $this->fail('处理状态错误');
        }

        $appeal = ReviewAppeal::find($params['id']);
        if (!$appeal) {
            return $this->fail('申诉不存在');
        }

        if ($appeal->status != ReviewAppeal::STATUS_PENDING) {
            return $this->fail('该申诉已处理');
        }

        $appeal->handle(
            $this->adminId,
            $params['status'],
            $params['handle_result'] ?? '',
            $params['handle_action'] ?? 0
        );

        return $this->success('处理成功');
    }

    /**
     * @notes 统计数据
     * @return \think\response\Json
     */
    public function statistics()
    {
        $totalCount = ReviewAppeal::count();
        $pendingCount = ReviewAppeal::where('status', ReviewAppeal::STATUS_PENDING)->count();
        $approvedCount = ReviewAppeal::where('status', ReviewAppeal::STATUS_APPROVED)->count();
        $rejectedCount = ReviewAppeal::where('status', ReviewAppeal::STATUS_REJECTED)->count();

        return $this->success('', [
            'total_count' => $totalCount,
            'pending_count' => $pendingCount,
            'approved_count' => $approvedCount,
            'rejected_count' => $rejectedCount,
        ]);
    }

    /**
     * @notes 申诉类型选项
     * @return \think\response\Json
     */
    public function typeOptions()
    {
        $options = [];
        foreach (ReviewAppeal::getTypeDesc() as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }
        return $this->success('', $options);
    }

    /**
     * @notes 处理动作选项
     * @return \think\response\Json
     */
    public function actionOptions()
    {
        $options = [];
        foreach (ReviewAppeal::getActionDesc() as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }
        return $this->success('', $options);
    }
}
