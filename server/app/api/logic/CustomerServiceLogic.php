<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 用户咨询入口逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\crm\Customer;
use app\common\model\crm\CustomerAssignLog;
use app\common\model\crm\SalesAdvisor;
use app\common\model\decorate\DecoratePage;
use app\common\model\order\Order;
use app\common\model\service\ServiceCategory;
use app\common\model\staff\Staff;
use app\common\model\user\User;
use app\common\service\ConfigService;
use app\common\service\FileService;
use app\common\service\WeComMessageService;
use think\facade\Db;
use think\facade\Log;

/**
 * 用户咨询入口逻辑
 */
class CustomerServiceLogic extends BaseLogic
{
    private const SCENE_TEXT_MAP = [
        'home' => '首页咨询',
        'staff_detail' => '服务人员详情咨询',
        'order_detail' => '订单详情咨询',
        'aftersale' => '售后咨询',
        'package_detail' => '套餐详情咨询',
    ];

    /**
     * @notes 启动咨询
     * @param int $userId
     * @param array $params
     * @return array|false
     */
    public static function startConsult(int $userId, array $params): array|false
    {
        if ($userId <= 0) {
            return [
                'entry_type' => 'fallback',
                'customer_id' => 0,
                'advisor_id' => 0,
                'is_new_assignment' => false,
                'contact' => self::getFallbackContact(),
            ];
        }

        $user = User::find($userId);
        if (!$user) {
            self::setError('用户不存在');
            return false;
        }

        $scene = (string) ($params['scene'] ?? 'home');
        $context = self::buildConsultContext($userId, $params);
        if ($context === false) {
            return false;
        }

        $now = time();
        $customerId = 0;
        $advisorId = 0;
        $finalAdvisor = null;
        $isNewAssignment = false;
        $isReactivated = false;
        $shouldNotifyAdvisor = false;
        $notifyBody = '';

        Db::startTrans();
        try {
            $customer = Customer::findByUserId($userId);
            $sourceDetail = self::buildSourceDetail($scene, $params);

            if ($customer) {
                $customerId = (int) $customer->id;
                $isReactivated = in_array((int) $customer->customer_status, [Customer::STATUS_LOST, Customer::STATUS_COMPLETED], true);
                $currentAdvisor = self::loadAdvisor((int) $customer->advisor_id);

                $updateData = self::buildCustomerUpdateData($customer, $user, $context, $sourceDetail, $now);
                if ($isReactivated) {
                    $updateData['customer_status'] = Customer::STATUS_FOLLOWING;
                }
                if (!empty($updateData)) {
                    $updateData['update_time'] = $now;
                    Customer::where('id', $customerId)->update($updateData);
                    $customer = Customer::find($customerId);
                }

                if ($currentAdvisor && $currentAdvisor->canServeConsultation()) {
                    $finalAdvisor = $currentAdvisor;
                    $advisorId = (int) $currentAdvisor->id;
                } else {
                    $assignedAdvisorId = self::autoAssignAdvisor($context['area'], $context['specialty']);
                    if ($assignedAdvisorId > 0) {
                        $assignType = (int) $customer->advisor_id > 0
                            ? CustomerAssignLog::TYPE_RECYCLE
                            : CustomerAssignLog::TYPE_AUTO;
                        $assignReason = $isReactivated ? '客户重新发起咨询自动重分配' : '用户咨询自动分配';
                        if (!Customer::assignAdvisor($customerId, $assignedAdvisorId, 0, $assignType, $assignReason)) {
                            throw new \RuntimeException('顾问分配失败');
                        }

                        $advisorId = $assignedAdvisorId;
                        $finalAdvisor = self::loadAdvisor($assignedAdvisorId);
                        $isNewAssignment = true;
                    }
                }
            } else {
                $assignedAdvisorId = self::autoAssignAdvisor($context['area'], $context['specialty']);
                $customer = Customer::create([
                    'user_id' => $userId,
                    'customer_name' => self::resolveCustomerName($user, $context),
                    'customer_mobile' => trim((string) ($user->mobile ?? '')),
                    'customer_wechat' => '',
                    'city' => $context['area'],
                    'wedding_date' => $context['wedding_date'] ?: null,
                    'wedding_venue' => $context['wedding_venue'],
                    'service_needs' => $context['service_needs'],
                    'source_channel' => Customer::SOURCE_MINIAPP,
                    'source_detail' => $sourceDetail,
                    'customer_status' => Customer::STATUS_NEW,
                    'advisor_id' => $assignedAdvisorId,
                    'assign_time' => $assignedAdvisorId > 0 ? $now : 0,
                    'first_contact_time' => $now,
                    'create_time' => $now,
                    'update_time' => $now,
                ]);
                $customerId = (int) $customer->id;
                $advisorId = $assignedAdvisorId;
                $isNewAssignment = $assignedAdvisorId > 0;
                if ($assignedAdvisorId > 0) {
                    CustomerAssignLog::record(
                        $customerId,
                        0,
                        $assignedAdvisorId,
                        CustomerAssignLog::TYPE_AUTO,
                        '用户首次咨询自动分配',
                        0
                    );
                    SalesAdvisor::incrementCustomerCount($assignedAdvisorId);
                    $finalAdvisor = self::loadAdvisor($assignedAdvisorId);
                }
            }

            if ($advisorId > 0 && $finalAdvisor && $finalAdvisor->canServeConsultation()) {
                $shouldNotifyAdvisor = $isNewAssignment || $isReactivated;
                if ($shouldNotifyAdvisor) {
                    $notifyTitle = $isNewAssignment ? '新咨询已分配' : '客户重新发起咨询';
                    $notifyBody = self::buildAdvisorNotifyMessage($notifyTitle, $user, $context, $scene);
                }
            }

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            Log::error('启动咨询失败：' . $e->getMessage());
            self::setError($e->getMessage());
            return false;
        }

        if ($shouldNotifyAdvisor && $advisorId > 0 && $notifyBody !== '') {
            try {
                WeComMessageService::sendToAdvisor($advisorId, $notifyBody);
            } catch (\Throwable $e) {
                Log::error('顾问企微提醒发送失败：' . $e->getMessage());
            }
        }

        if ($finalAdvisor && $finalAdvisor->canServeConsultation()) {
            return [
                'entry_type' => 'advisor',
                'customer_id' => $customerId,
                'advisor_id' => (int) $finalAdvisor->id,
                'is_new_assignment' => $isNewAssignment,
                'contact' => self::formatAdvisorContact($finalAdvisor),
            ];
        }

        return [
            'entry_type' => 'fallback',
            'customer_id' => $customerId,
            'advisor_id' => 0,
            'is_new_assignment' => false,
            'contact' => self::getFallbackContact(),
        ];
    }

    /**
     * @notes 构建咨询上下文
     * @param int $userId
     * @param array $params
     * @return array|false
     */
    private static function buildConsultContext(int $userId, array $params): array|false
    {
        $scene = (string) ($params['scene'] ?? 'home');
        $staffId = (int) ($params['staff_id'] ?? 0);
        $orderId = (int) ($params['order_id'] ?? 0);
        $categoryId = (int) ($params['category_id'] ?? 0);

        if ($scene === 'staff_detail' && $staffId <= 0) {
            self::setError('缺少服务人员参数');
            return false;
        }
        if ($scene === 'order_detail' && $orderId <= 0) {
            self::setError('缺少订单参数');
            return false;
        }

        $customer = Customer::findByUserId($userId);
        $context = [
            'area' => trim((string) ($customer->city ?? '')),
            'specialty' => '',
            'wedding_date' => trim((string) ($customer->wedding_date ?? '')),
            'wedding_venue' => trim((string) ($customer->wedding_venue ?? '')),
            'service_needs' => is_array($customer?->service_needs ?? null) ? $customer->service_needs : [],
            'order' => null,
            'staff' => null,
        ];

        if ($staffId > 0) {
            $staff = Staff::with(['category'])->find($staffId);
            if (!$staff) {
                self::setError('服务人员不存在');
                return false;
            }
            $context['staff'] = $staff;
            if (empty($context['specialty']) && !empty($staff->category?->name)) {
                $context['specialty'] = trim((string) $staff->category->name);
            }
        }

        if ($orderId > 0) {
            $order = Order::with(['items.package', 'items.staff.category'])
                ->where('user_id', $userId)
                ->find($orderId);
            if (!$order) {
                self::setError('订单不存在');
                return false;
            }
            $context['order'] = $order;

            if ($context['area'] === '') {
                $context['area'] = self::extractCityFromText((string) ($order->service_address ?? ''));
            }
            if ($context['specialty'] === '') {
                $context['specialty'] = self::resolveOrderSpecialty($order);
            }
        }

        if ($categoryId > 0) {
            $category = ServiceCategory::find($categoryId);
            if ($category) {
                $context['specialty'] = trim((string) $category->name);
            }
        }

        $context['area'] = trim((string) $context['area']);
        $context['specialty'] = trim((string) $context['specialty']);
        if (!empty($context['specialty'])) {
            $context['service_needs'] = array_values(array_unique(array_filter(array_merge(
                $context['service_needs'],
                [$context['specialty']]
            ))));
        }

        return $context;
    }

    /**
     * @notes 构建客户更新数据
     * @param Customer $customer
     * @param User $user
     * @param array $context
     * @param string $sourceDetail
     * @param int $now
     * @return array
     */
    private static function buildCustomerUpdateData(Customer $customer, User $user, array $context, string $sourceDetail, int $now): array
    {
        $data = [];
        if (trim((string) $customer->customer_name) === '') {
            $data['customer_name'] = self::resolveCustomerName($user, $context);
        }
        if (trim((string) $customer->customer_mobile) === '' && trim((string) ($user->mobile ?? '')) !== '') {
            $data['customer_mobile'] = trim((string) $user->mobile);
        }
        if (trim((string) $customer->city) === '' && $context['area'] !== '') {
            $data['city'] = $context['area'];
        }
        if (empty($customer->wedding_date) && $context['wedding_date'] !== '') {
            $data['wedding_date'] = $context['wedding_date'];
        }
        if (trim((string) $customer->wedding_venue) === '' && $context['wedding_venue'] !== '') {
            $data['wedding_venue'] = $context['wedding_venue'];
        }
        if (empty($customer->source_channel)) {
            $data['source_channel'] = Customer::SOURCE_MINIAPP;
        }
        if (trim((string) $customer->source_detail) === '') {
            $data['source_detail'] = $sourceDetail;
        }
        if ((int) ($customer->first_contact_time ?? 0) <= 0) {
            $data['first_contact_time'] = $now;
        }

        $mergedNeeds = array_values(array_unique(array_filter(array_merge(
            is_array($customer->service_needs) ? $customer->service_needs : [],
            $context['service_needs']
        ))));
        if (!empty($mergedNeeds)) {
            $data['service_needs'] = $mergedNeeds;
        }

        return $data;
    }

    /**
     * @notes 自动分配顾问
     * @param string $area
     * @param string $specialty
     * @return int
     */
    private static function autoAssignAdvisor(string $area, string $specialty): int
    {
        $advisorId = SalesAdvisor::autoAssign($area, $specialty) ?? 0;
        if ($advisorId <= 0) {
            return 0;
        }

        $advisor = self::loadAdvisor($advisorId);
        if (!$advisor || !$advisor->canServeConsultation()) {
            return 0;
        }

        return $advisorId;
    }

    /**
     * @notes 读取顾问
     * @param int $advisorId
     * @return SalesAdvisor|null
     */
    private static function loadAdvisor(int $advisorId): ?SalesAdvisor
    {
        if ($advisorId <= 0) {
            return null;
        }

        return SalesAdvisor::find($advisorId);
    }

    /**
     * @notes 订单里推导咨询分类
     * @param Order $order
     * @return string
     */
    private static function resolveOrderSpecialty(Order $order): string
    {
        $items = $order->items;
        if (is_object($items) && method_exists($items, 'all')) {
            $items = $items->all();
        }
        if (!is_iterable($items)) {
            return '';
        }

        foreach ($items as $item) {
            $packageCategoryId = (int) ($item->package->category_id ?? 0);
            if ($packageCategoryId > 0) {
                $category = ServiceCategory::find($packageCategoryId);
                if ($category) {
                    return trim((string) $category->name);
                }
            }

            $staffCategoryName = trim((string) ($item->staff->category->name ?? ''));
            if ($staffCategoryName !== '') {
                return $staffCategoryName;
            }
        }

        return '';
    }

    /**
     * @notes 提取城市
     * @param string $text
     * @return string
     */
    private static function extractCityFromText(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        if (preg_match('/(北京市|上海市|天津市|重庆市|香港特别行政区|澳门特别行政区|[^\\s,，]+?(?:市|州|地区|盟))/u', $text, $matches)) {
            return trim((string) ($matches[1] ?? ''));
        }

        return '';
    }

    /**
     * @notes 生成来源详情
     * @param string $scene
     * @param array $params
     * @return string
     */
    private static function buildSourceDetail(string $scene, array $params): string
    {
        $id = 0;
        if (!empty($params['order_id'])) {
            $id = (int) $params['order_id'];
        } elseif (!empty($params['staff_id'])) {
            $id = (int) $params['staff_id'];
        } elseif (!empty($params['category_id'])) {
            $id = (int) $params['category_id'];
        }

        return $id > 0 ? sprintf('consult:%s#%d', $scene, $id) : sprintf('consult:%s', $scene);
    }

    /**
     * @notes 组装顾问通知文案
     * @param string $title
     * @param User $user
     * @param array $context
     * @param string $scene
     * @return string
     */
    private static function buildAdvisorNotifyMessage(string $title, User $user, array $context, string $scene): string
    {
        $lines = [$title];
        $customerName = self::resolveCustomerName($user, $context);
        $customerMobile = trim((string) ($user->mobile ?? ''));

        if ($customerName !== '') {
            $lines[] = '客户：' . $customerName;
        }
        if ($customerMobile !== '') {
            $lines[] = '电话：' . $customerMobile;
        }
        $lines[] = '场景：' . (self::SCENE_TEXT_MAP[$scene] ?? '用户咨询');
        if ($context['area'] !== '') {
            $lines[] = '城市：' . $context['area'];
        }
        if ($context['specialty'] !== '') {
            $lines[] = '服务偏好：' . $context['specialty'];
        }
        $lines[] = '请尽快在企业微信中跟进客户。';

        return implode("\n", $lines);
    }

    /**
     * @notes 解析客户名称
     * @param User $user
     * @param array $context
     * @return string
     */
    private static function resolveCustomerName(User $user, array $context): string
    {
        $name = trim((string) ($user->nickname ?? ''));
        if ($name !== '') {
            return $name;
        }

        $order = $context['order'];
        if ($order && trim((string) ($order->contact_name ?? '')) !== '') {
            return trim((string) $order->contact_name);
        }

        return '小程序用户' . (int) $user->id;
    }

    /**
     * @notes 顾问联系人结构化输出
     * @param SalesAdvisor $advisor
     * @return array
     */
    private static function formatAdvisorContact(SalesAdvisor $advisor): array
    {
        $serviceTime = trim((string) ConfigService::get('customer_service', 'service_time', ''));
        $tips = trim((string) ConfigService::get('customer_service', 'tips', ''));

        return [
            'name' => (string) $advisor->advisor_name,
            'role' => '专属婚礼顾问',
            'avatar' => (string) ($advisor->avatar ?? ''),
            'mobile' => (string) ($advisor->mobile ?? ''),
            'wechat_alias' => (string) ($advisor->wechat ?? ''),
            'contact_qr_code' => (string) ($advisor->contact_qr_code ?? ''),
            'contact_link' => (string) ($advisor->contact_link ?? ''),
            'service_time' => $serviceTime !== '' ? $serviceTime : '工作日 09:00 - 18:00',
            'tips' => $tips !== '' ? $tips : '添加后可继续沟通',
        ];
    }

    /**
     * @notes 获取统一客服兜底信息
     * @return array
     */
    private static function getFallbackContact(): array
    {
        $config = [
            'name' => trim((string) ConfigService::get('customer_service', 'name', '统一客服')),
            'role' => trim((string) ConfigService::get('customer_service', 'role', '统一企微客服')),
            'avatar' => '',
            'mobile' => trim((string) ConfigService::get('customer_service', 'phone', '')),
            'wechat_alias' => trim((string) ConfigService::get('customer_service', 'wechat', '')),
            'contact_qr_code' => self::normalizeImage(ConfigService::get('customer_service', 'qr_code', '')),
            'contact_link' => trim((string) ConfigService::get('customer_service', 'contact_link', '')),
            'service_time' => trim((string) ConfigService::get('customer_service', 'service_time', '')),
            'tips' => trim((string) ConfigService::get('customer_service', 'tips', '')),
        ];

        if ($config['contact_qr_code'] !== '' || $config['wechat_alias'] !== '' || $config['mobile'] !== '') {
            return self::mergeFallbackDefaults($config);
        }

        $page = DecoratePage::field(['data'])->find(3);
        $data = [];
        if ($page && !empty($page->data)) {
            $data = is_string($page->data) ? (json_decode($page->data, true) ?: []) : (array) $page->data;
        }

        foreach ($data as $item) {
            if (($item['name'] ?? '') !== 'customer-service') {
                continue;
            }

            $content = $item['content'] ?? [];
            $config = [
                'name' => trim((string) ($content['title'] ?? '统一客服')),
                'role' => '统一企微客服',
                'avatar' => '',
                'mobile' => trim((string) ($content['mobile'] ?? '')),
                'wechat_alias' => trim((string) ($content['wechat'] ?? '')),
                'contact_qr_code' => self::normalizeImage($content['qrcode'] ?? ''),
                'contact_link' => trim((string) ($content['contactLink'] ?? '')),
                'service_time' => trim((string) ($content['time'] ?? '')),
                'tips' => trim((string) ($content['tips'] ?? '')),
            ];

            return self::mergeFallbackDefaults($config);
        }

        return self::mergeFallbackDefaults($config);
    }

    /**
     * @notes 兜底联系人默认值
     * @param array $config
     * @return array
     */
    private static function mergeFallbackDefaults(array $config): array
    {
        return [
            'name' => $config['name'] !== '' ? $config['name'] : '统一客服',
            'role' => $config['role'] !== '' ? $config['role'] : '统一企微客服',
            'avatar' => $config['avatar'] ?? '',
            'mobile' => $config['mobile'] ?? '',
            'wechat_alias' => $config['wechat_alias'] ?? '',
            'contact_qr_code' => $config['contact_qr_code'] ?? '',
            'contact_link' => $config['contact_link'] ?? '',
            'service_time' => $config['service_time'] !== '' ? $config['service_time'] : '工作日 09:00 - 18:00',
            'tips' => $config['tips'] !== '' ? $config['tips'] : '请联系统一客服',
        ];
    }

    /**
     * @notes 统一图片 URL
     * @param mixed $value
     * @return string
     */
    private static function normalizeImage($value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        return FileService::getFileUrl($value);
    }
}
