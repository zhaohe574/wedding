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
namespace app\adminapi\logic\decorate;

use app\common\logic\BaseLogic;
use app\common\model\article\Article;
use app\common\model\decorate\DecoratePage;
use app\common\model\coupon\Coupon;
use app\common\model\notification\Notification;
use app\common\model\dynamic\Dynamic;
use app\common\service\DecorateDataService;

/**
 * 装修页-数据
 * Class DecorateDataLogic
 * @package app\adminapi\logic\decorate
 */
class DecorateDataLogic extends BaseLogic
{

    /**
     * @notes 获取文章列表
     * @param $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/9/22 16:49
     */
    public static function getArticleLists($limit): array
    {
        $field = 'id,title,desc,abstract,image,author,content,
        click_virtual,click_actual,create_time';

        return Article::where(['is_show' => 1])
            ->field($field)
            ->order(['id' => 'desc'])
            ->limit($limit)
            ->append(['click'])
            ->hidden(['click_virtual', 'click_actual'])
            ->select()->toArray();
    }

    /**
     * @notes pc设置
     * @return array
     * @author mjf
     * @date 2024/3/14 18:13
     */
    public static function pc(): array
    {
        $pcPage = DecoratePage::findOrEmpty(4)->toArray();
        $updateTime = !empty($pcPage['update_time']) ? $pcPage['update_time'] : date('Y-m-d H:i:s');
        return [
            'update_time' => $updateTime,
            'pc_url' => request()->domain() . '/pc'
        ];
    }

    /**
     * @notes 获取优惠券列表（装修组件选择器）
     * @param array $params 查询参数
     * @return array 优惠券列表
     * @author AI
     * @date 2026/01/22
     */
    public static function getCouponList(array $params): array
    {
        $page = $params['page'] ?? 1;
        $limit = $params['limit'] ?? 20;
        $status = $params['status'] ?? '';
        $name = $params['name'] ?? '';

        $query = Coupon::order('id', 'desc');

        // 状态筛选
        if ($status !== '') {
            $query->where('status', $status);
        }

        // 名称搜索
        if (!empty($name)) {
            $query->where('name', 'like', '%' . $name . '%');
        }

        $result = $query->field([
                'id', 'name', 'coupon_type', 'discount_amount', 
                'threshold_amount', 'valid_start_time', 'valid_end_time',
                'total_count', 'receive_count', 'status'
            ])
            ->paginate([
                'list_rows' => $limit,
                'page' => $page,
            ])
            ->toArray();

        // 格式化数据
        foreach ($result['data'] as &$item) {
            // 计算剩余数量
            $item['remain_count'] = $item['total_count'] - $item['receive_count'];
            
            // 格式化优惠券类型文本
            if ($item['coupon_type'] == 1) {
                $item['type_text'] = '满减券';
                $item['discount_text'] = '满' . $item['threshold_amount'] . '减' . $item['discount_amount'];
            } elseif ($item['coupon_type'] == 2) {
                $item['type_text'] = '折扣券';
                $item['discount_text'] = $item['discount_amount'] . '折';
            } else {
                $item['type_text'] = '无门槛券';
                $item['discount_text'] = '立减' . $item['discount_amount'] . '元';
            }

            // 格式化状态文本
            $item['status_text'] = $item['status'] == 1 ? '启用' : '禁用';

            // 格式化有效期 - 类型转换 - PHP 8.0+ 严格类型检查
            $validStartTime = is_numeric($item['valid_start_time']) ? (int)$item['valid_start_time'] : strtotime($item['valid_start_time']);
            $validEndTime = is_numeric($item['valid_end_time']) ? (int)$item['valid_end_time'] : strtotime($item['valid_end_time']);
            $item['valid_period'] = date('Y-m-d H:i:s', $validStartTime) . ' 至 ' . date('Y-m-d H:i:s', $validEndTime);
        }

        return $result;
    }

    /**
     * @notes 获取公告列表（装修组件选择器）
     * @param array $params 查询参数
     * @return array 公告列表
     * @author AI
     * @date 2026/01/22
     */
    public static function getNoticeList(array $params): array
    {
        $page = $params['page'] ?? 1;
        $limit = $params['limit'] ?? 20;
        $title = $params['title'] ?? '';

        $query = Notification::where('notify_type', Notification::TYPE_SYSTEM)
            ->order('create_time', 'desc');

        // 标题搜索
        if (!empty($title)) {
            $query->where('title', 'like', '%' . $title . '%');
        }

        $result = $query->field([
                'id', 'title', 'content', 'create_time'
            ])
            ->paginate([
                'list_rows' => $limit,
                'page' => $page,
            ])
            ->toArray();

        // 格式化数据
        foreach ($result['data'] as &$item) {
            // 类型转换 - PHP 8.0+ 严格类型检查
            $createTime = is_numeric($item['create_time']) ? (int)$item['create_time'] : strtotime($item['create_time']);
            $item['create_time_text'] = $createTime > 0 ? date('Y-m-d H:i', $createTime) : '';
            // 截取内容预览
            $item['content_preview'] = mb_substr(strip_tags($item['content']), 0, 50) . '...';
        }

        return $result;
    }

    /**
     * @notes 获取话题列表（装修组件选择器）
     * @param array $params 查询参数
     * @return array 话题列表
     * @author AI
     * @date 2026/01/22
     */
    public static function getTopicList(array $params): array
    {
        $page = $params['page'] ?? 1;
        $limit = $params['limit'] ?? 20;
        $keyword = $params['keyword'] ?? '';

        // 获取热门话题
        $hotTopics = DecorateDataService::getHotTopics(100);

        // 关键词筛选
        if (!empty($keyword)) {
            $hotTopics = array_filter($hotTopics, function($topic) use ($keyword) {
                return stripos($topic['name'], $keyword) !== false;
            });
        }

        // 分页处理
        $total = count($hotTopics);
        $start = ($page - 1) * $limit;
        $topics = array_slice($hotTopics, $start, $limit);

        // 重新索引数组
        $topics = array_values($topics);

        return [
            'total' => $total,
            'per_page' => $limit,
            'current_page' => $page,
            'last_page' => ceil($total / $limit),
            'data' => $topics,
        ];
    }

    /**
     * @notes 获取活动列表（装修组件选择器）
     * @param array $params 查询参数
     * @return array 活动列表
     * @author AI
     * @date 2026/01/24
     */
    public static function getActivityList(array $params): array
    {
        $page = $params['page'] ?? 1;
        $limit = $params['limit'] ?? 20;
        $title = $params['title'] ?? '';

        $query = Dynamic::where('dynamic_type', Dynamic::TYPE_ACTIVITY)
            ->where('status', Dynamic::STATUS_PUBLISHED)
            ->order('is_top', 'desc')
            ->order('create_time', 'desc');

        // 标题搜索
        if (!empty($title)) {
            $query->where('title', 'like', '%' . $title . '%');
        }

        $result = $query->field([
                'id', 'title', 'content', 'images', 'tags',
                'view_count', 'like_count', 'is_top', 'is_hot', 'create_time'
            ])
            ->paginate([
                'list_rows' => $limit,
                'page' => $page,
            ])
            ->toArray();

        // 格式化数据
        foreach ($result['data'] as &$item) {
            // 获取第一张图片作为封面
            $images = is_string($item['images']) ? json_decode($item['images'], true) : $item['images'];
            $item['cover_image'] = !empty($images) && is_array($images) ? $images[0] : '';
            
            // 格式化创建时间
            $createTime = is_numeric($item['create_time']) ? (int)$item['create_time'] : strtotime($item['create_time']);
            $item['create_time_text'] = $createTime > 0 ? date('Y-m-d H:i', $createTime) : '';
            
            // 截取内容预览
            $item['content_preview'] = mb_substr(strip_tags($item['content']), 0, 50);
            if (mb_strlen(strip_tags($item['content'])) > 50) {
                $item['content_preview'] .= '...';
            }
            
            // 标签数组 - 处理字符串和数组两种情况
            if (!empty($item['tags'])) {
                if (is_string($item['tags'])) {
                    // 如果是字符串，按逗号分割
                    $item['tags_arr'] = array_filter(explode(',', $item['tags']));
                } else if (is_array($item['tags'])) {
                    $item['tags_arr'] = $item['tags'];
                } else {
                    $item['tags_arr'] = [];
                }
            } else {
                $item['tags_arr'] = [];
            }
        }

        return $result;
    }




}
