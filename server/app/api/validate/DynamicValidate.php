<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端动态验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\validate;

use app\common\validate\BaseValidate;

/**
 * 动态验证器
 * Class DynamicValidate
 * @package app\api\validate
 */
class DynamicValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'comment_id' => 'require|integer|gt:0',
        'dynamic_type' => 'require|integer|in:1,2,3,4',
        'content' => 'require|max:2000',
        'images' => 'array|max:9',
        'video_url' => 'url|max:255',
        'video_cover' => 'max:255',
        'location' => 'max:100',
        'tags' => 'max:255',
        'order_id' => 'integer|egt:0',
        'parent_id' => 'integer|egt:0',
        'reply_user_id' => 'integer|egt:0',
        'follow_type' => 'require|integer|in:1,2',
        'follow_id' => 'require|integer|gt:0',
    ];

    protected $message = [
        'id.require' => '请选择动态',
        'id.integer' => '动态ID格式错误',
        'comment_id.require' => '请选择评论',
        'comment_id.integer' => '评论ID格式错误',
        'dynamic_type.require' => '请选择动态类型',
        'dynamic_type.in' => '动态类型参数错误',
        'content.require' => '请填写内容',
        'content.max' => '内容最多2000个字符',
        'images.array' => '图片格式错误',
        'images.max' => '最多上传9张图片',
        'video_url.url' => '视频地址格式错误',
        'video_url.max' => '视频地址最多255个字符',
        'location.max' => '位置信息最多100个字符',
        'tags.max' => '标签最多255个字符',
        'follow_type.require' => '请选择关注类型',
        'follow_type.in' => '关注类型参数错误',
        'follow_id.require' => '请选择关注对象',
    ];

    /**
     * @notes 详情场景
     * @return DynamicValidate
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 发布场景
     * @return DynamicValidate
     */
    public function scenePublish()
    {
        return $this->only(['dynamic_type', 'content', 'images', 'video_url', 'video_cover', 'location', 'tags', 'order_id']);
    }

    /**
     * @notes 评论场景
     * @return DynamicValidate
     */
    public function sceneComment()
    {
        return $this->only(['id', 'content', 'images', 'parent_id', 'reply_user_id'])
            ->remove('content', 'max')
            ->append('content', 'max:500');
    }

    /**
     * @notes 评论ID场景
     * @return DynamicValidate
     */
    public function sceneCommentId()
    {
        return $this->only(['comment_id']);
    }

    /**
     * @notes 关注场景
     * @return DynamicValidate
     */
    public function sceneFollow()
    {
        return $this->only(['follow_type', 'follow_id']);
    }
}
