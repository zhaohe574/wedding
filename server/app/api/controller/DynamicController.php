<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端动态控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\controller;

use app\api\logic\DynamicLogic;
use app\api\validate\DynamicValidate;

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
        $result = DynamicLogic::getDynamicDetail($params['id'], $this->userId);
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
        $params = (new DynamicValidate())->post()->goCheck('publish');
        $params['user_id'] = $this->userId;
        $result = DynamicLogic::publishDynamic($params);
        if ($result['success']) {
            return $this->success($result['message'], ['dynamic_id' => $result['dynamic_id']]);
        }
        return $this->fail($result['message']);
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
        $result = DynamicLogic::toggleLike($params['id'], $this->userId);
        return $this->success($result['message'], ['is_liked' => $result['is_liked']]);
    }

    /**
     * @notes 收藏/取消收藏
     * @return \think\response\Json
     */
    public function collect()
    {
        $params = (new DynamicValidate())->post()->goCheck('detail');
        $result = DynamicLogic::toggleCollect($params['id'], $this->userId);
        return $this->success($result['message'], ['is_collected' => $result['is_collected']]);
    }

    /**
     * @notes 评论列表
     * @return \think\response\Json
     */
    public function commentLists()
    {
        $params = (new DynamicValidate())->goCheck('detail');
        $result = DynamicLogic::getCommentList($params['id'], $this->userId, $this->request->get());
        return $this->data($result);
    }

    /**
     * @notes 发表评论
     * @return \think\response\Json
     */
    public function addComment()
    {
        $params = (new DynamicValidate())->post()->goCheck('comment');
        $result = DynamicLogic::addComment($params['id'], $this->userId, $params);
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
        $result = DynamicLogic::deleteComment($params['comment_id'], $this->userId);
        if ($result['success']) {
            return $this->success($result['message']);
        }
        return $this->fail($result['message']);
    }

    /**
     * @notes 评论点赞
     * @return \think\response\Json
     */
    public function likeComment()
    {
        $params = (new DynamicValidate())->post()->goCheck('commentId');
        $result = DynamicLogic::toggleCommentLike($params['comment_id'], $this->userId);
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
     * @notes 关注/取消关注
     * @return \think\response\Json
     */
    public function follow()
    {
        $params = (new DynamicValidate())->post()->goCheck('follow');
        $result = DynamicLogic::toggleFollow($this->userId, $params['follow_type'], $params['follow_id']);
        return $this->success($result['message'], ['is_followed' => $result['is_followed']]);
    }

    /**
     * @notes 我的关注
     * @return \think\response\Json
     */
    public function myFollowing()
    {
        $params = $this->request->get();
        $result = DynamicLogic::getFollowingList($this->userId, $params);
        return $this->data($result);
    }

    /**
     * @notes 我的粉丝
     * @return \think\response\Json
     */
    public function myFans()
    {
        $params = $this->request->get();
        $result = DynamicLogic::getFansList($this->userId, $params);
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
}
