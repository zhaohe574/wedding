<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 装修数据解析服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\model\staff\Staff;
use app\common\model\notification\Notification;
use app\common\model\dynamic\Dynamic;
use think\facade\Log;

/**
 * 装修数据解析服务
 * 负责解析装修配置，动态填充引用数据
 * Class DecorateDataService
 * @package app\common\service
 */
class DecorateDataService
{
    /**
     * 已下线的装修跳转路径
     */
    const DISABLED_DECORATE_PATHS = [
        '/packages/pages/cart/cart',
        '/packages/pages/cart_plan/cart_plan',
        '/packages/pages/share_plan/share_plan',
        '/pages/coupon/list',
        '/pages/coupon/center',
        '/pages/coupon/detail',
        '/packages/pages/coupon/list',
        '/packages/pages/coupon/center',
        '/packages/pages/coupon/detail',
        '/packages/pages/coupon_center/coupon_center',
    ];

    /**
     * 组件类型与数据源的映射关系
     */
    const WIDGET_DATA_SOURCE_MAP = [
        'staff-showcase' => [
            'id_field' => 'staff_id',
            'data_type' => 'staff'
        ],
        'notice-bar' => [
            'id_field' => 'notice_id',
            'data_type' => 'notice'
        ],
        'hot-topics' => [
            'id_field' => 'topic_id',
            'data_type' => 'topic'
        ],
    ];

    /**
     * @notes 解析装修页面数据，动态填充引用数据
     * @param array $pageData 装修页面数据
     * @return array 填充后的数据
     */
    public static function parsePageData(array $pageData): array
    {
        if (empty($pageData) || empty($pageData['data'])) {
            return $pageData;
        }

        // 解析 data 字段（可能是 JSON 字符串）
        $data = $pageData['data'];
        $isJsonString = is_string($data);
        
        if ($isJsonString) {
            $data = json_decode($data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('装修数据解析失败: ' . json_last_error_msg());
                return $pageData;
            }
        }

        if (!is_array($data)) {
            return $pageData;
        }

        if (!self::isWidgetListData($data)) {
            if ($isJsonString) {
                $pageData['data'] = json_encode($data, JSON_UNESCAPED_UNICODE);
            } else {
                $pageData['data'] = $data;
            }
            return $pageData;
        }

        $data = self::sanitizeDecorateNode($data);
        if (!is_array($data)) {
            return $pageData;
        }

        $filteredData = [];
        foreach ($data as $widget) {
            if (!is_array($widget)) {
                continue;
            }
            if (isset($widget['id']) && isset($widget['name'])) {
                $widget = self::parseWidget($widget);
            }
            $filteredData[] = $widget;
        }
        $data = $filteredData;

        // 如果原始数据是 JSON 字符串，转换回 JSON 字符串
        if ($isJsonString) {
            $pageData['data'] = json_encode($data, JSON_UNESCAPED_UNICODE);
        } else {
            $pageData['data'] = $data;
        }
        
        return $pageData;
    }

    /**
     * @notes 判断是否为装修组件列表数据
     * @param array $data
     * @return bool
     */
    private static function isWidgetListData(array $data): bool
    {
        if (!self::isNumericListNode($data)) {
            return false;
        }

        foreach ($data as $item) {
            if (!is_array($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @notes 判断当前节点是否为数字键列表
     * @param array $node
     * @return bool
     */
    private static function isNumericListNode(array $node): bool
    {
        if ($node === []) {
            return true;
        }

        foreach (array_keys($node) as $key) {
            if (is_int($key)) {
                continue;
            }

            if (is_string($key) && ctype_digit($key)) {
                continue;
            }

            return false;
        }

        return true;
    }

    /**
     * @notes 递归清理已下线组件与页面入口
     * @param mixed $node
     * @return mixed
     */
    private static function sanitizeDecorateNode($node)
    {
        if (!is_array($node)) {
            return $node;
        }

        $isNumericListNode = self::isNumericListNode($node);

        if (self::shouldRemoveDecorateNode($node)) {
            return null;
        }

        $result = [];
        foreach ($node as $key => $value) {
            $sanitized = self::sanitizeDecorateNode($value);
            if ($sanitized === null) {
                continue;
            }
            $result[$key] = $sanitized;
        }

        if ($isNumericListNode) {
            return array_values($result);
        }

        return $result;
    }

    /**
     * @notes 判断当前节点是否需要剔除
     * @param array $node
     * @return bool
     */
    private static function shouldRemoveDecorateNode(array $node): bool
    {
        $name = (string)($node['name'] ?? '');
        if ($name === 'service-packages' || $name === 'coupon-receive') {
            return true;
        }

        if ((string)($node['value'] ?? '') === 'coupon_count') {
            return true;
        }

        $path = (string)($node['path'] ?? '');
        if ($path !== '' && in_array($path, self::DISABLED_DECORATE_PATHS, true)) {
            return true;
        }

        $linkPath = (string)($node['link']['path'] ?? '');
        return $linkPath !== '' && in_array($linkPath, self::DISABLED_DECORATE_PATHS, true);
    }

    /**
     * @notes 解析单个组件数据
     * @param array $widget 组件数据
     * @return array 填充后的组件数据
     */
    private static function parseWidget(array $widget): array
    {
        $widgetType = $widget['name'] ?? '';

        // 兼容旧数据：未配置enabled时默认启用
        if (isset($widget['content']) && is_array($widget['content']) && !array_key_exists('enabled', $widget['content'])) {
            $widget['content']['enabled'] = 1;
        }
        
        // 检查是否是需要动态填充的组件类型
        if (!isset(self::WIDGET_DATA_SOURCE_MAP[$widgetType])) {
            return $widget;
        }

        // 特殊处理：热门话题组件的自动获取模式
        if ($widgetType === 'hot-topics' && isset($widget['content']['source']) && $widget['content']['source'] == 2) {
            // 自动获取热门话题
            $limit = $widget['content']['show_count'] ?? 10;
            $hotTopics = self::getHotTopics($limit);
            $widget['content']['data'] = $hotTopics;
            return $widget;
        }

        // 检查组件是否有 content.data 数组
        if (empty($widget['content']['data']) || !is_array($widget['content']['data'])) {
            return $widget;
        }

        $config = self::WIDGET_DATA_SOURCE_MAP[$widgetType];
        $idField = $config['id_field'];
        $dataType = $config['data_type'];

        // 提取所有引用ID
        $ids = [];
        foreach ($widget['content']['data'] as $item) {
            $id = $item[$idField] ?? null;
            // 话题ID可能是字符串（话题名称），其他ID是数字
            if ($dataType === 'topic') {
                if (!empty($id) && is_string($id)) {
                    $ids[] = $id;
                }
            } else {
                if (isset($id) && $id > 0) {
                    $ids[] = (int)$id;
                }
            }
        }

        if (empty($ids)) {
            return $widget;
        }

        // 批量获取业务数据
        $businessDataMap = [];
        try {
            if ($dataType === 'staff') {
                $businessDataMap = self::batchGetStaffData($ids);
            } elseif ($dataType === 'notice') {
                $businessDataMap = self::batchGetNoticeData($ids);
            } elseif ($dataType === 'topic') {
                $businessDataMap = self::batchGetTopicData($ids);
            }
        } catch (\Exception $e) {
            Log::error('批量获取业务数据失败: ' . $e->getMessage());
            return $widget;
        }

        // 合并引用数据和业务数据
        $mergedData = [];
        foreach ($widget['content']['data'] as $item) {
            $id = $item[$idField] ?? null;
            // 话题ID是字符串，其他ID是数字
            $idKey = ($dataType === 'topic') ? $id : (int)$id;
            if (!empty($idKey) && isset($businessDataMap[$idKey])) {
                $mergedData[] = self::mergeData($item, $businessDataMap[$idKey], $dataType);
            }
        }

        $widget['content']['data'] = $mergedData;
        return $widget;
    }

    /**
     * @notes 批量获取员工数据
     * @param array $staffIds 员工ID列表
     * @return array 员工数据映射 [id => data]
     */
    private static function batchGetStaffData(array $staffIds): array
    {
        if (empty($staffIds)) {
            return [];
        }

        // 限制单次查询数量，避免性能问题
        $maxBatchSize = 50;
        if (count($staffIds) > $maxBatchSize) {
            $staffIds = array_slice($staffIds, 0, $maxBatchSize);
        }

        try {
            $staffList = Staff::whereIn('id', $staffIds)
                ->where('delete_time', null)
                ->where('status', Staff::STATUS_ENABLE)
                ->field([
                    'id', 'name', 'avatar', 'sn', 'category_id',
                    'rating', 'order_count', 'status'
                ])
                ->with(['category' => function($query) {
                    $query->field('id, name');
                }])
                ->select()
                ->toArray();

            // 转换为以 ID 为键的映射
            $result = [];
            foreach ($staffList as $staff) {
                // 获取分类名称
                $categoryName = $staff['category']['name'] ?? '服务人员';
                
                // 获取标签（如果需要）
                $staffTags = \app\common\model\staff\StaffTag::with(['tag'])
                    ->where('staff_id', $staff['id'])
                    ->select();
                $tags = [];
                foreach ($staffTags as $staffTag) {
                    if (isset($staffTag['tag']['name'])) {
                        $tags[] = $staffTag['tag']['name'];
                    }
                }

                $result[$staff['id']] = [
                    'id' => $staff['id'],
                    'name' => $staff['name'],
                    'avatar' => $staff['avatar'],
                    'sn' => $staff['sn'] ?? '',
                    'category_name' => $categoryName,
                    'rating' => (string)($staff['rating'] ?? '5.0'),
                    'order_count' => $staff['order_count'] ?? 0,
                    'tag_names' => $tags ?? [],
                ];
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('批量查询员工数据失败: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @notes 批量获取公告数据
     * @param array $noticeIds 公告ID列表
     * @return array 公告数据映射 [id => data]
     */
    private static function batchGetNoticeData(array $noticeIds): array
    {
        if (empty($noticeIds)) {
            return [];
        }

        // 限制单次查询数量，避免性能问题
        $maxBatchSize = 50;
        if (count($noticeIds) > $maxBatchSize) {
            $noticeIds = array_slice($noticeIds, 0, $maxBatchSize);
        }

        try {
            $noticeList = Notification::whereIn('id', $noticeIds)
                ->where('notify_type', Notification::TYPE_SYSTEM) // 只查询系统通知
                ->field([
                    'id', 'title', 'content', 'target_type', 'target_id', 'create_time'
                ])
                ->order('create_time', 'desc')
                ->select()
                ->toArray();

            // 转换为以 ID 为键的映射
            $result = [];
            foreach ($noticeList as $notice) {
                // 处理 create_time - 可能是字符串或时间戳
                $createTime = $notice['create_time'];
                if (is_string($createTime)) {
                    // 如果是字符串格式的日期时间，转换为时间戳
                    $createTime = strtotime($createTime);
                }
                $createTime = (int)$createTime;
                
                $result[$notice['id']] = [
                    'id' => $notice['id'],
                    'title' => $notice['title'],
                    'content' => $notice['content'],
                    'target_type' => $notice['target_type'],
                    'target_id' => $notice['target_id'],
                    'create_time' => $createTime,
                    'create_time_text' => $createTime > 0 ? date('Y-m-d H:i', $createTime) : '',
                ];
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('批量查询公告数据失败: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @notes 批量获取话题数据（基于动态标签统计）
     * @param array $topicIds 话题ID列表（这里实际是话题名称的哈希ID）
     * @return array 话题数据映射 [id => data]
     */
    private static function batchGetTopicData(array $topicIds): array
    {
        if (empty($topicIds)) {
            return [];
        }

        // 限制单次查询数量，避免性能问题
        $maxBatchSize = 50;
        if (count($topicIds) > $maxBatchSize) {
            $topicIds = array_slice($topicIds, 0, $maxBatchSize);
        }

        try {
            // 由于话题是基于动态的标签，我们需要从配置中获取话题名称
            // 这里假设 topicIds 实际存储的是话题名称
            $result = [];
            
            foreach ($topicIds as $topicId) {
                // 查询包含该标签的动态数量
                $count = Dynamic::where('status', Dynamic::STATUS_PUBLISHED)
                    ->where('tags', 'like', '%' . $topicId . '%')
                    ->count();

                $result[$topicId] = [
                    'id' => $topicId,
                    'name' => $topicId,
                    'count' => $count,
                    'icon' => '🔥', // 默认图标
                ];
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('批量查询话题数据失败: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @notes 获取热门话题（基于动态标签统计）
     * @param int $limit 返回数量限制
     * @return array 热门话题列表
     */
    public static function getHotTopics(int $limit = 10): array
    {
        try {
            // 查询所有已发布动态的标签
            $dynamics = Dynamic::where('status', Dynamic::STATUS_PUBLISHED)
                ->where('tags', '<>', '')
                ->field('tags')
                ->select()
                ->toArray();

            // 统计标签出现次数
            $tagCounts = [];
            foreach ($dynamics as $dynamic) {
                if (empty($dynamic['tags'])) {
                    continue;
                }
                
                $tags = explode(',', $dynamic['tags']);
                foreach ($tags as $tag) {
                    $tag = trim($tag);
                    if (empty($tag)) {
                        continue;
                    }
                    
                    if (!isset($tagCounts[$tag])) {
                        $tagCounts[$tag] = 0;
                    }
                    $tagCounts[$tag]++;
                }
            }

            // 按出现次数排序
            arsort($tagCounts);

            // 取前 N 个
            $hotTopics = [];
            $index = 0;
            foreach ($tagCounts as $tag => $count) {
                if ($index >= $limit) {
                    break;
                }
                
                $hotTopics[] = [
                    'id' => $tag,
                    'name' => $tag,
                    'count' => $count,
                    'icon' => '🔥',
                ];
                
                $index++;
            }

            return $hotTopics;
        } catch (\Exception $e) {
            Log::error('获取热门话题失败: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @notes 合并引用数据和业务数据
     * @param array $item 引用数据项（包含 ID 和控制信息）
     * @param array $businessData 业务数据
     * @param string $type 数据类型 (staff/notice/topic)
     * @return array 合并后的数据
     */
    private static function mergeData(array $item, array $businessData, string $type): array
    {
        // 保留引用数据中的控制字段
        $result = [
            'is_show' => $item['is_show'] ?? '1',
            'sort' => $item['sort'] ?? 0,
        ];

        // 合并业务数据
        $result = array_merge($result, $businessData);

        // 添加链接信息
        if ($type === 'staff') {
            $result['staff_id'] = $businessData['id'];
            $result['role'] = $businessData['category_name'] ?? '服务人员';
            $result['tags'] = $businessData['tag_names'] ?? [];
            $result['link'] = [
                'path' => '/packages/pages/staff_detail/staff_detail',
                'query' => ['id' => $businessData['id']],
                'type' => 'shop'
            ];
        } elseif ($type === 'notice') {
            $result['notice_id'] = $businessData['id'];
            // 根据目标类型生成跳转链接
            if (!empty($businessData['target_type']) && $businessData['target_id'] > 0) {
                switch ($businessData['target_type']) {
                    case 'order':
                        $result['link'] = '/pages/order_detail/order_detail?id=' . $businessData['target_id'];
                        break;
                    case 'staff_order':
                        $result['link'] = '/packages/pages/staff_order_detail/staff_order_detail?id=' . $businessData['target_id'];
                        break;
                    case 'dynamic':
                        $result['link'] = '/pages/dynamic_detail/dynamic_detail?id=' . $businessData['target_id'];
                        break;
                    case 'news':
                        $result['link'] = '/pages/news_detail/news_detail?id=' . $businessData['target_id'];
                        break;
                    default:
                        $result['link'] = '/pages/notification/index?id=' . $businessData['id'];
                        break;
                }
            } else {
                // 默认跳转到通知详情页
                $result['link'] = '/pages/notification/index?id=' . $businessData['id'];
            }
        } elseif ($type === 'topic') {
            $result['topic_id'] = $businessData['id'];
            $result['link'] = [
                'path' => '/packages/pages/dynamic_list/dynamic_list',
                'query' => ['tag' => $businessData['name']],
                'type' => 'shop'
            ];
        }

        return $result;
    }
}
