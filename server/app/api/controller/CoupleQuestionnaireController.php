<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序新人问卷控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\controller;

use app\common\service\CoupleQuestionnaireService;

/**
 * 小程序新人问卷控制器
 */
class CoupleQuestionnaireController extends BaseApiController
{
    public array $notNeedLogin = [];

    /**
     * @notes 我的新人问卷列表
     * @return \think\response\Json
     */
    public function lists()
    {
        $result = CoupleQuestionnaireService::userTaskList($this->userId, $this->request->get());
        return $this->data($result);
    }

    /**
     * @notes 新人问卷详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $id = (int)$this->request->get('id', 0);
        $result = CoupleQuestionnaireService::userTaskDetail($id, $this->userId);
        if (empty($result)) {
            return $this->fail('问卷不存在');
        }

        return $this->data($result);
    }

    /**
     * @notes 提交新人问卷
     * @return \think\response\Json
     */
    public function submit()
    {
        try {
            $id = (int)$this->request->post('id', 0);
            $answers = $this->request->post('answers', []);
            if ($id <= 0) {
                return $this->fail('请选择问卷');
            }
            if (!is_array($answers)) {
                return $this->fail('答案格式错误');
            }

            CoupleQuestionnaireService::submitUserAnswers($id, $this->userId, $answers);
            return $this->success('提交成功', ['id' => $id, 'submitted' => 1], 1, 1);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

}
