<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端动态控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\controller;

use app\api\logic\DynamicLogic;
use app\api\validate\DynamicValidate;
use app\common\enum\PayEnum;
use app\common\service\ActivityRegistrationService;
use app\common\service\MiniProgramReviewModeService;

/**
 * 小程序端动态控制器
 * Class DynamicController
 * @package app\api\controller
 */
class DynamicController extends BaseApiController
{
    public array $notNeedLogin = ['lists', 'detail', 'commentLists', 'hotTags'];

    /**
     * @notes 动态列表
     * @return \think\response\Json
     */
    public function lists()
    {
        $params = $this->request->get();
        $result = DynamicLogic::getDynamicList($params, $this->userId);
        return $this->data($result);
    }

    /**
     * @notes 动态详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new DynamicValidate())->goCheck('detail');
        $result = DynamicLogic::getDynamicDetail((int)$params['id'], $this->userId);
        if ($result === null) {
            return $this->fail('动态不存在');
        }
        return $this->data($result);
    }

    /**
     * @notes 发布动态
     * @return \think\response\Json
     */
    public function publish()
    {
        // 禁用用户端发布动态功能
        return $this->fail('动态发布功能已关闭，请联系管理员');
        
        // 以下代码已禁用
        // $params = (new DynamicValidate())->post()->goCheck('publish');
        // $params['user_id'] = $this->userId;
        // $result = DynamicLogic::publishDynamic($params);
        // if ($result['success']) {
        //     return $this->success($result['message'], ['dynamic_id' => $result['dynamic_id']]);
        // }
        // return $this->fail($result['message']);
    }

    /**
     * @notes 删除动态
     * @return \think\response\Json
     */
    public function delete()
    {
        $params = (new DynamicValidate())->post()->goCheck('detail');
        $result = DynamicLogic::deleteDynamic($params['id'], $this->userId);
        if ($result['success']) {
            return $this->success($result['message']);
        }
        return $this->fail($result['message']);
    }

    /**
     * @notes 点赞/取消点赞
     * @return \think\response\Json
     */
    public function like()
    {
        $params = (new DynamicValidate())->post()->goCheck('detail');
        $result = DynamicLogic::toggleLike((int)$params['id'], $this->userId);
        return $this->success($result['message'], ['is_liked' => $result['is_liked']]);
    }

    /**
     * @notes 收藏/取消收藏
     * @return \think\response\Json
     */
    public function collect()
    {
        $params = (new DynamicValidate())->post()->goCheck('detail');
        $result = DynamicLogic::toggleCollect((int)$params['id'], $this->userId);
        return $this->success($result['message'], ['is_collected' => $result['is_collected']]);
    }

    /**
     * @notes 评论列表
     * @return \think\response\Json
     */
    public function commentLists()
    {
        $dynamicId = $this->request->get('dynamic_id/d', 0);
        if ($dynamicId <= 0) {
            return $this->fail('请选择动态');
        }
        $result = DynamicLogic::getCommentList($dynamicId, $this->userId, $this->request->get());
        return $this->data($result);
    }

    /**
     * @notes 发表评论
     * @return \think\response\Json
     */
    public function addComment()
    {
        if (MiniProgramReviewModeService::enabled()) {
            return $this->fail(MiniProgramReviewModeService::message('评论'));
        }

        $params = (new DynamicValidate())->post()->goCheck('comment');
        $result = DynamicLogic::addComment((int)$params['dynamic_id'], $this->userId, $params);
        if ($result['success']) {
            return $this->success($result['message'], ['comment_id' => $result['comment_id']]);
        }
        return $this->fail($result['message']);
    }

    /**
     * @notes 删除评论
     * @return \think\response\Json
     */
    public function deleteComment()
    {
        $params = (new DynamicValidate())->post()->goCheck('commentId');
        [$success, $message] = DynamicLogic::deleteComment((int)$params['comment_id'], $this->userId);
        if ($success) {
            return $this->success($message);
        }
        return $this->fail($message);
    }

    /**
     * @notes 评论点赞
     * @return \think\response\Json
     */
    public function likeComment()
    {
        $params = (new DynamicValidate())->post()->goCheck('commentId');
        $result = DynamicLogic::toggleCommentLike((int)$params['comment_id'], $this->userId);
        return $this->success($result['message'], ['is_liked' => $result['is_liked']]);
    }

    /**
     * @notes 我的动态
     * @return \think\response\Json
     */
    public function myDynamics()
    {
        $params = $this->request->get();
        $result = DynamicLogic::getUserDynamics($this->userId, $params);
        return $this->data($result);
    }

    /**
     * @notes 我的收藏
     * @return \think\response\Json
     */
    public function myCollections()
    {
        $params = $this->request->get();
        $result = DynamicLogic::getUserCollections($this->userId, $params);
        return $this->data($result);
    }

    /**
     * @notes 我的点赞
     * @return \think\response\Json
     */
    public function myLikes()
    {
        $params = $this->request->get();
        $result = DynamicLogic::getUserLikes($this->userId, $params);
        return $this->data($result);
    }

    /**
     * @notes 热门标签
     * @return \think\response\Json
     */
    public function hotTags()
    {
        $result = DynamicLogic::getHotTags();
        return $this->data($result);
    }

    /**
     * @notes 消息列表
     * @return \think\response\Json
     */
    public function notifications()
    {
        $params = $this->request->get();
        $result = DynamicLogic::getNotifications($this->userId, $params);
        return $this->data($result);
    }

    /**
     * @notes 未读消息数量
     * @return \think\response\Json
     */
    public function unreadCount()
    {
        $result = DynamicLogic::getUnreadCount($this->userId);
        return $this->data($result);
    }

    /**
     * @notes 标记消息已读
     * @return \think\response\Json
     */
    public function markRead()
    {
        $params = $this->request->post();
        if (!empty($params['notification_id'])) {
            $result = DynamicLogic::markNotificationRead($params['notification_id'], $this->userId);
        } else {
            $result = DynamicLogic::markAllNotificationsRead($this->userId, $params['type'] ?? 0);
        }
        return $result ? $this->success('操作成功') : $this->fail('操作失败');
    }

    /**
     * @notes 活动票种
     */
    public function activityTickets()
    {
        $dynamicId = (int)$this->request->get('dynamic_id/d', 0);
        if ($dynamicId <= 0) {
            return $this->fail('请选择活动');
        }
        return $this->data(ActivityRegistrationService::getTicketOptions($dynamicId, true));
    }

    /**
     * @notes 提交活动报名
     */
    public function activityRegister()
    {
        $params = $this->request->post();
        $dynamicId = (int)($params['dynamic_id'] ?? 0);
        $ticketId = (int)($params['ticket_id'] ?? 0);
        if ($dynamicId <= 0 || $ticketId <= 0) {
            return $this->fail('请选择活动和票种');
        }
        if (trim((string)($params['contact_name'] ?? '')) === '') {
            return $this->fail('请填写联系人');
        }
        if (trim((string)($params['contact_mobile'] ?? '')) === '') {
            return $this->fail('请填写手机号');
        }

        [$success, $message, $data] = ActivityRegistrationService::register($dynamicId, $ticketId, $this->userId, $params);
        return $success ? $this->success($message, $data) : $this->fail($message, $data);
    }

    /**
     * @notes 活动报名支付方式
     */
    public function activityPayWay()
    {
        $registrationId = (int)$this->request->get('registration_id/d', 0);
        $result = ActivityRegistrationService::getPayWay($registrationId, $this->userId, (int)$this->userInfo['terminal']);
        if ($result === false) {
            return $this->fail(ActivityRegistrationService::getError() ?: '报名记录不可支付');
        }
        return $this->data($result);
    }

    /**
     * @notes 活动报名预支付
     */
    public function activityPrepay()
    {
        $params = $this->request->post();
        $registrationId = (int)($params['registration_id'] ?? 0);
        $payWay = (int)($params['pay_way'] ?? 0);
        if (!in_array($payWay, [PayEnum::BALANCE_PAY, PayEnum::WECHAT_PAY, PayEnum::ALI_PAY], true)) {
            return $this->fail('支付方式参数错误');
        }
        $result = ActivityRegistrationService::prepay(
            $registrationId,
            $this->userId,
            $payWay,
            (int)$this->userInfo['terminal'],
            (string)($params['redirect'] ?? '')
        );
        if ($result === false) {
            return $this->fail(ActivityRegistrationService::getError() ?: '发起支付失败');
        }
        return $this->success('', $result);
    }

    /**
     * @notes 活动报名支付状态
     */
    public function activityPayStatus()
    {
        $registrationId = (int)$this->request->get('registration_id/d', 0);
        $paymentSn = (string)$this->request->get('payment_sn', '');
        $result = ActivityRegistrationService::getPayStatus($registrationId, $this->userId, $paymentSn);
        if ($result === false) {
            return $this->fail('报名记录不存在');
        }
        return $this->data($result);
    }

    /**
     * @notes 我的活动报名
     */
    public function activityRegistrations()
    {
        return $this->data(ActivityRegistrationService::myRegistrations($this->userId, $this->request->get()));
    }

    /**
     * @notes 活动报名详情
     */
    public function activityRegistrationDetail()
    {
        $registrationId = (int)$this->request->get('id/d', 0);
        $result = ActivityRegistrationService::registrationDetail($registrationId, $this->userId);
        if ($result === null) {
            return $this->fail('报名记录不存在');
        }
        return $this->data($result);
    }

    /**
     * @notes 提交取消报名申请
     */
    public function activityCancelApply()
    {
        $params = $this->request->post();
        $registrationId = (int)($params['registration_id'] ?? 0);
        $reason = trim((string)($params['reason'] ?? ''));
        if ($registrationId <= 0) {
            return $this->fail('请选择报名记录');
        }
        if ($reason === '') {
            return $this->fail('请填写取消原因');
        }
        [$success, $message] = ActivityRegistrationService::applyCancel($registrationId, $this->userId, $reason);
        return $success ? $this->success($message) : $this->fail($message);
    }
}
