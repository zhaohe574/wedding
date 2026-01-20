<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端评价验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\validate;

use app\common\validate\BaseValidate;

/**
 * 小程序端评价验证器
 * Class ReviewValidate
 * @package app\api\validate
 */
class ReviewValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'order_item_id' => 'require|integer|gt:0',
        'score' => 'require|integer|between:1,5',
        'score_service' => 'integer|between:1,5',
        'score_professional' => 'integer|between:1,5',
        'score_punctual' => 'integer|between:1,5',
        'score_effect' => 'integer|between:1,5',
        'content' => 'max:500',
        'images' => 'array|max:9',
        'video' => 'max:500',
        'is_anonymous' => 'in:0,1',
        'tag_ids' => 'array',
        'review_id' => 'require|integer|gt:0',
        'share_platform' => 'require|in:wechat,moments,weibo,douyin,xiaohongshu',
        'verify_image' => 'max:500',
        'appeal_type' => 'require|in:1,2,3,4',
        'appeal_reason' => 'require|max:500',
        'evidence_images' => 'array|max:5',
    ];

    protected $message = [
        'id.require' => '评价ID不能为空',
        'id.integer' => '评价ID必须是整数',
        'id.gt' => '评价ID必须大于0',
        'order_item_id.require' => '订单项ID不能为空',
        'order_item_id.integer' => '订单项ID必须是整数',
        'order_item_id.gt' => '订单项ID必须大于0',
        'score.require' => '评分不能为空',
        'score.integer' => '评分必须是整数',
        'score.between' => '评分必须在1-5之间',
        'score_service.integer' => '服务态度评分必须是整数',
        'score_service.between' => '服务态度评分必须在1-5之间',
        'score_professional.integer' => '专业水平评分必须是整数',
        'score_professional.between' => '专业水平评分必须在1-5之间',
        'score_punctual.integer' => '时间守约评分必须是整数',
        'score_punctual.between' => '时间守约评分必须在1-5之间',
        'score_effect.integer' => '整体效果评分必须是整数',
        'score_effect.between' => '整体效果评分必须在1-5之间',
        'content.max' => '评价内容最多500个字符',
        'images.array' => '图片格式错误',
        'images.max' => '最多上传9张图片',
        'video.max' => '视频链接过长',
        'is_anonymous.in' => '匿名参数错误',
        'tag_ids.array' => '标签格式错误',
        'review_id.require' => '评价ID不能为空',
        'review_id.integer' => '评价ID必须是整数',
        'review_id.gt' => '评价ID必须大于0',
        'share_platform.require' => '分享平台不能为空',
        'share_platform.in' => '分享平台参数错误',
        'verify_image.max' => '截图链接过长',
        'appeal_type.require' => '申诉类型不能为空',
        'appeal_type.in' => '申诉类型参数错误',
        'appeal_reason.require' => '申诉原因不能为空',
        'appeal_reason.max' => '申诉原因最多500个字符',
        'evidence_images.array' => '证据图片格式错误',
        'evidence_images.max' => '最多上传5张证据图片',
    ];

    protected $scene = [
        'detail' => ['id'],
        'publish' => ['order_item_id', 'score', 'score_service', 'score_professional', 'score_punctual', 'score_effect', 'content', 'images', 'video', 'is_anonymous', 'tag_ids'],
        'append' => ['id', 'content', 'images'],
        'shareReward' => ['review_id', 'share_platform', 'verify_image'],
        'appeal' => ['review_id', 'appeal_type', 'appeal_reason', 'evidence_images'],
    ];
}
