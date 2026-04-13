<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 企微接收人维护逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\setting;

use app\common\logic\BaseLogic;
use app\common\model\crm\SalesAdvisor;

class WecomRecipientLogic extends BaseLogic
{
    public static function lists(array $params = []): array
    {
        $keyword = trim((string) ($params['keyword'] ?? ''));
        $status = isset($params['status']) && $params['status'] !== ''
            ? (int) $params['status']
            : null;

        $query = SalesAdvisor::field([
            'id',
            'advisor_name',
            'avatar',
            'mobile',
            'wechat',
            'contact_link',
            'contact_qr_code',
            'areas',
            'specialties',
            'status',
            'wecom_userid',
            'current_customer_count',
            'max_customer_count',
        ]);

        if ($keyword !== '') {
            $query->where(function ($subQuery) use ($keyword) {
                $subQuery->whereLike('advisor_name', '%' . $keyword . '%')
                    ->whereOrLike('mobile', '%' . $keyword . '%')
                    ->whereOrLike('wechat', '%' . $keyword . '%')
                    ->whereOrLike('wecom_userid', '%' . $keyword . '%');
            });
        }

        if ($status !== null) {
            $query->where('status', $status);
        }

        $lists = $query
            ->order('id', 'desc')
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $areas = is_array($item['areas'] ?? null) ? $item['areas'] : [];
            $specialties = is_array($item['specialties'] ?? null) ? $item['specialties'] : [];
            $item['status'] = (int) ($item['status'] ?? 0);
            $item['status_desc'] = SalesAdvisor::getStatusOptions()[$item['status']] ?? '未知';
            $item['areas_text'] = implode('、', array_filter(array_map('strval', $areas)));
            $item['specialties_text'] = implode('、', array_filter(array_map('strval', $specialties)));
            $item['load_text'] = (int) ($item['current_customer_count'] ?? 0) . '/' . (int) ($item['max_customer_count'] ?? 0);
        }
        unset($item);

        return $lists;
    }

    public static function updateAdvisor(array $params): bool
    {
        $advisor = SalesAdvisor::find((int) $params['id']);
        if (!$advisor) {
            self::setError('顾问不存在');
            return false;
        }

        $advisor->save([
            'wecom_userid' => trim((string) ($params['wecom_userid'] ?? '')),
            'update_time' => time(),
        ]);

        return true;
    }
}
