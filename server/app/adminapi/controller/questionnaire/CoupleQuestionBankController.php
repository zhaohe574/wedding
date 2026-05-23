<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 新人问卷基础题库控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\questionnaire;

use app\adminapi\controller\BaseAdminController;
use app\common\model\questionnaire\CoupleQuestionBank;
use app\common\service\CoupleQuestionnaireService;

/**
 * 新人问卷基础题库控制器
 */
class CoupleQuestionBankController extends BaseAdminController
{
    /**
     * @notes 题库列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->data(CoupleQuestionnaireService::bankList($this->request->get()));
    }

    /**
     * @notes 题库详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $id = (int)$this->request->get('id', 0);
        $detail = CoupleQuestionBank::whereNull('delete_time')->find($id);
        if (!$detail) {
            return $this->fail('题目不存在');
        }

        return $this->data($detail->toArray());
    }

    /**
     * @notes 保存题库题目
     * @return \think\response\Json
     */
    public function save()
    {
        try {
            $result = CoupleQuestionnaireService::saveBankQuestion($this->request->post());
            return $this->success('保存成功', $result, 1, 1);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 删除题库题目
     * @return \think\response\Json
     */
    public function delete()
    {
        $id = (int)$this->request->post('id', 0);
        if (CoupleQuestionnaireService::deleteBankQuestion($id)) {
            return $this->success('删除成功', [], 1, 1);
        }

        return $this->fail('删除失败');
    }

    /**
     * @notes 启用或禁用题目
     * @return \think\response\Json
     */
    public function changeStatus()
    {
        $id = (int)$this->request->post('id', 0);
        $status = (int)$this->request->post('status', CoupleQuestionBank::STATUS_ENABLED);
        $status = $status === CoupleQuestionBank::STATUS_DISABLED
            ? CoupleQuestionBank::STATUS_DISABLED
            : CoupleQuestionBank::STATUS_ENABLED;

        $result = CoupleQuestionBank::where('id', $id)
            ->whereNull('delete_time')
            ->update([
                'status' => $status,
                'update_time' => time(),
            ]);

        if ($result !== false) {
            return $this->success('操作成功', [], 1, 1);
        }

        return $this->fail('操作失败');
    }

    /**
     * @notes 更新题目排序
     * @return \think\response\Json
     */
    public function sort()
    {
        $items = $this->request->post('items', []);
        if (!is_array($items)) {
            return $this->fail('参数错误');
        }

        foreach ($items as $item) {
            $id = (int)($item['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            CoupleQuestionBank::where('id', $id)
                ->whereNull('delete_time')
                ->update([
                    'sort' => (int)($item['sort'] ?? 0),
                    'category_sort' => (int)($item['category_sort'] ?? 0),
                    'update_time' => time(),
                ]);
        }

        return $this->success('排序成功', [], 1, 1);
    }
}
