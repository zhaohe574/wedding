<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 图片内容安全检测任务编排
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\model\ContentMediaCheckTask;
use app\common\model\review\Review;
use think\facade\Log;

/**
 * 微信图片内容安全异步检测的编排层。
 *
 * 链路：业务提交 → WechatSecurityService::checkMediaAsync() 拿 trace_id →
 *       落 ContentMediaCheckTask（待回调）→ 微信消息推送回调 →
 *       按 trace_id 回写任务结论 → 按业务类型做处置。
 *
 * 设计约束：
 * 1. 图片结论一定不是同步的（微信侧以回调为主），所以本服务只负责“提交 + 落账”，
 *    任何异常都不能阻断业务主流程，业务侧仍需用 status=待审 兜底。
 * 2. 处置策略只有一条：命中不自动删除、不自动放行，只标记原因并保持待审，
 *    由人工审核决定（与文本 msg_sec_check 的口径一致）。
 * 3. 超时（回调没来）不等于通过，只标记超时，内容继续待在待审队列里。
 */
class MediaCheckService
{
    /** 回调未到达的判定时长（秒），超过则标记为超时，仍不解锁内容 */
    private const CALLBACK_TIMEOUT_SECONDS = 1800;

    /**
     * 提交图片检测。
     *
     * 微信 media_check_async 一次只接受一个 media_url，因此 N 张图 = N 次请求 = N 条任务，
     * 每条任务各自持有 trace_id，回调可以精确落到某一张图。
     *
     * @param int $userId
     * @param array $mediaUrls
     * @param string $bizType ContentMediaCheckTask::BIZ_*
     * @param int $bizId 业务主键（评价ID / 追评ID / 工单ID / 投诉ID）
     * @param int $scene
     * @return array{task_ids:array,submitted:int,total:int,message:string}
     */
    public static function submit(int $userId, array $mediaUrls, string $bizType, int $bizId, int $scene = WechatSecurityService::SCENE_COMMENT): array
    {
        $empty = ['task_ids' => [], 'submitted' => 0, 'total' => 0, 'message' => ''];

        $mediaUrls = array_values(array_filter(array_map(static function ($url) {
            $url = trim((string)$url);
            if ($url === '') {
                return '';
            }
            // 业务侧存的是相对路径（uploads/...），微信要求公网可访问的绝对地址
            if (!preg_match('#^https?://#i', $url)) {
                try {
                    $url = FileService::getFileUrl($url);
                } catch (\Throwable $e) {
                    return '';
                }
            }
            return (string)$url;
        }, $mediaUrls), static function (string $url) {
            return $url !== '' && preg_match('#^https?://#i', $url) === 1;
        }));

        if (empty($mediaUrls) || $userId <= 0 || $bizId <= 0) {
            $empty['message'] = '无图片或业务参数缺失，跳过检测';
            return $empty;
        }

        $result = WechatSecurityService::checkMediaAsync($userId, $mediaUrls, $scene);

        $taskIds = [];
        $submitted = 0;
        foreach ($result['items'] as $item) {
            try {
                $task = ContentMediaCheckTask::create([
                    'user_id' => $userId,
                    'scene' => $scene,
                    'biz_type' => $bizType,
                    'biz_id' => $bizId,
                    'media_type' => WechatSecurityService::MEDIA_TYPE_IMAGE,
                    'media_urls' => [$item['media_url']],
                    'media_count' => 1,
                    'trace_id' => (string)$item['trace_id'],
                    'status' => $item['submitted'] ? ContentMediaCheckTask::STATUS_WAITING : ContentMediaCheckTask::STATUS_FAILED,
                    'suggest' => '',
                    'label' => 0,
                    'callback_detail' => [],
                    'callback_time' => 0,
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('图片检测任务落库失败：user_id=' . $userId . '，biz=' . $bizType . '#' . $bizId . '，message=' . $e->getMessage());
                continue;
            }

            $taskIds[] = (int)$task->id;
            if ($item['submitted']) {
                $submitted++;
            }

            // 接口在个别场景会随提交响应同步返回 result，能立刻定论的就不要等回调。
            $immediate = self::extractResult((array)$item['response']);
            if ($item['submitted'] && $immediate['has_result']) {
                self::applyResult($task, $immediate['suggest'], $immediate['label'], (array)$item['response'], 'submit');
            }
        }

        Log::info(
            '图片检测任务已创建：biz=' . $bizType . '#' . $bizId
            . '，images=' . count($mediaUrls)
            . '，submitted=' . $submitted
            . '，task_ids=' . implode(',', $taskIds)
        );

        return [
            'task_ids' => $taskIds,
            'submitted' => $submitted,
            'total' => count($mediaUrls),
            'message' => (string)$result['message'],
        ];
    }

    /**
     * 处理微信消息推送回调。
     *
     * @param array $payload 已解析的推送体
     * @return bool 是否命中并处理了任务
     */
    public static function handleCallback(array $payload): bool
    {
        $traceId = trim((string)($payload['trace_id'] ?? ''));
        if ($traceId === '') {
            Log::warning('图片检测回调缺少 trace_id，已忽略：' . json_encode($payload, JSON_UNESCAPED_UNICODE));
            return false;
        }

        $task = ContentMediaCheckTask::where('trace_id', $traceId)->findOrEmpty();
        if ($task->isEmpty()) {
            Log::warning('图片检测回调未匹配到任务：trace_id=' . $traceId);
            return false;
        }

        $result = self::extractResult($payload);
        $suggest = $result['has_result'] ? $result['suggest'] : 'review';
        $label = $result['label'];

        self::applyResult($task, $suggest, $label, $payload, 'callback');
        return true;
    }

    /**
     * 扫描超时未回调的任务，仅做标记，不解锁内容。
     *
     * @param int $timeoutSeconds
     * @return int 处理条数
     */
    public static function sweepTimeout(int $timeoutSeconds = self::CALLBACK_TIMEOUT_SECONDS): int
    {
        $deadline = time() - max(60, $timeoutSeconds);
        $tasks = ContentMediaCheckTask::where('status', ContentMediaCheckTask::STATUS_WAITING)
            ->where('create_time', '<', $deadline)
            ->limit(200)
            ->select();

        $count = 0;
        foreach ($tasks as $task) {
            $task->status = ContentMediaCheckTask::STATUS_TIMEOUT;
            $task->suggest = 'timeout';
            $task->callback_time = time();
            $task->update_time = time();
            $task->save();
            $count++;

            Log::warning(
                '图片检测回调超时，内容继续待人工审核：task_id=' . $task->id
                . '，biz=' . $task->biz_type . '#' . $task->biz_id
                . '，trace_id=' . $task->trace_id
            );
            self::markBiz($task, '图片检测超时未回调，需人工复核');
        }

        return $count;
    }

    /**
     * 回写任务结论并做业务处置。
     *
     * @param ContentMediaCheckTask $task
     * @param string $suggest pass / review / risky
     * @param int $label
     * @param array $detail 微信原始报文
     * @param string $source submit / callback
     * @return void
     */
    public static function applyResult(ContentMediaCheckTask $task, string $suggest, int $label, array $detail, string $source): void
    {
        // 已经定论的任务不再被重复回调改写（微信会重试推送）
        if (in_array((int)$task->status, [ContentMediaCheckTask::STATUS_PASS, ContentMediaCheckTask::STATUS_RISKY], true)) {
            return;
        }

        $risky = $suggest === 'risky' || ($suggest === 'review' && (int)ConfigService::get('feature_switch', 'wechat_text_check_review_as_hit', 1) === 1);

        $task->status = $risky ? ContentMediaCheckTask::STATUS_RISKY : ContentMediaCheckTask::STATUS_PASS;
        $task->suggest = $suggest;
        $task->label = $label;
        $task->callback_detail = $detail;
        $task->callback_time = time();
        $task->update_time = time();
        $task->save();

        Log::info(
            '图片检测结论已落库：task_id=' . $task->id
            . '，biz=' . $task->biz_type . '#' . $task->biz_id
            . '，source=' . $source
            . '，suggest=' . $suggest
            . '，label=' . $label
            . '，risky=' . ($risky ? '1' : '0')
        );

        if ($risky) {
            self::markBiz($task, '图片内容安全命中（suggest=' . $suggest . '，label=' . $label . '），需人工复核');
        }
    }

    /**
     * 把结论写到业务单据上。只写备注，不改审核状态：
     * 评价/追评本来就是“待审”，命中图片的处置由审核员在后台决定。
     *
     * @param ContentMediaCheckTask $task
     * @param string $remark
     * @return void
     */
    private static function markBiz(ContentMediaCheckTask $task, string $remark): void
    {
        try {
            if ($task->biz_type === ContentMediaCheckTask::BIZ_REVIEW) {
                $review = Review::find($task->biz_id);
                if (!$review) {
                    return;
                }
                $review->security_remark = self::mergeRemark((string)($review->security_remark ?? ''), $remark);
                $review->update_time = time();
                $review->save();
                return;
            }

            // 追评/工单/投诉没有独立的安全备注列，结论留在任务表里供排查。
            Log::warning('业务单据图片安全命中：biz=' . $task->biz_type . '#' . $task->biz_id . '，' . $remark);
        } catch (\Throwable $e) {
            Log::warning('写入图片安全备注失败：task_id=' . $task->id . '，message=' . $e->getMessage());
        }
    }

    /**
     * 合并备注文本（上限 255，超出截断）。
     */
    private static function mergeRemark(string $origin, string $addition): string
    {
        $origin = trim($origin);
        $text = $origin === '' ? $addition : $origin . '；' . $addition;
        return mb_substr($text, 0, 255, 'UTF-8');
    }

    /**
     * 从微信响应/推送体里提取检测结论。
     *
     * @param array $payload
     * @return array{has_result:bool,suggest:string,label:int,prob:int}
     */
    private static function extractResult(array $payload): array
    {
        $result = is_array($payload['result'] ?? null) ? $payload['result'] : [];
        $detail = is_array($payload['detail'] ?? null) ? $payload['detail'] : [];

        $suggest = (string)($result['suggest'] ?? '');
        $label = (int)($result['label'] ?? 0);
        $prob = isset($result['prob']) ? (int)round((float)$result['prob']) : -1;

        // 回调体里结论主要落在 detail[0]，只有 result 时用 result
        if ($suggest === '' && !empty($detail)) {
            $first = is_array($detail[0] ?? null) ? $detail[0] : [];
            $suggest = (string)($first['suggest'] ?? '');
            $label = (int)($first['label'] ?? $label);
            $prob = isset($first['prob']) ? (int)round((float)$first['prob']) : $prob;
        }

        if ($suggest === '') {
            // 回调里带 errcode 非 0 说明微信检测失败，按“待人工审核”处理
            $errcode = (int)($payload['errcode'] ?? 0);
            if ($errcode !== 0) {
                return ['has_result' => true, 'suggest' => 'review', 'label' => $label, 'prob' => $prob];
            }
            return ['has_result' => false, 'suggest' => '', 'label' => $label, 'prob' => $prob];
        }

        return ['has_result' => true, 'suggest' => $suggest, 'label' => $label, 'prob' => $prob];
    }
}
