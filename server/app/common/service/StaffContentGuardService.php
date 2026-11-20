<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员内容安全闸门
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\logic\BaseLogic;
use app\common\model\review\SensitiveWord;
use think\facade\Log;

/**
 * 服务人员中心写能力内容安全闸门
 *
 * 双层闸门口径（与评价 ReviewLogic 保持一致）：
 *  ① 本地敏感词库（SensitiveWord::filter）：离线快路径，禁止级（LEVEL_FORBID）命中直接拒绝，
 *     不依赖任何外部接口可用性；
 *  ② 微信文本安全（WechatSecurityService::checkText）：fail-open —— 接口超时/异常在其内部
 *     已按通过处理（hit=false），不会阻塞提交；命中风险（hit=true）不直接拒绝，
 *     动态/作品/证书落库本就是待审状态，命中后转人工复核。
 *
 * 注意：本类继承 BaseLogic 仅为复用 setError/getError 静态错误通道，
 * BaseLogic::$error 为静态属性，与 StaffCenterLogic 共享同一错误槽位，
 * 写点 fail 返回后由控制器经 StaffCenterLogic::getError() 读取。
 *
 * Class StaffContentGuardService
 * @package app\common\service
 */
class StaffContentGuardService extends BaseLogic
{
    /**
     * @notes 文本内容安全闸门
     * @param int $userId 提交者用户ID（微信检测取 openid 用）
     * @param array $texts 待检测文本，键为字段名（便于日志定位），值为标量文本；空值自动跳过
     * @param int $scene WechatSecurityService 场景常量（SCENE_DYNAMIC / SCENE_STAFF_WORK / SCENE_STAFF_CERT）
     * @return bool true=放行（含微信 fail-open 与命中转人工两种情况），false=本地禁止级敏感词命中
     */
    public static function textGuard(int $userId, array $texts, int $scene): bool
    {
        // 拼接非空文本，一次送检
        $joined = '';
        foreach ($texts as $field => $text) {
            $text = trim((string)$text);
            if ($text !== '') {
                $joined .= $text . "\n";
            }
        }
        $joined = trim($joined);
        if ($joined === '') {
            return true;
        }

        // ① 本地敏感词：禁止级命中直接拒绝（离线快路径，不依赖外部接口）
        $filterResult = SensitiveWord::filter($joined, false);
        if ($filterResult['has_sensitive'] && $filterResult['level'] >= SensitiveWord::LEVEL_FORBID) {
            $words = implode('、', array_slice(array_map('strval', (array)$filterResult['words']), 0, 3));
            self::setError('提交内容包含违规词汇（' . $words . '），请修改后重试');
            return false;
        }

        // ② 微信文本安全：fail-open —— checkText 自身超时/异常按通过处理（hit=false），不阻塞提交；
        //    命中风险（hit=true）不阻断：动态/作品/证书落库均为待审状态，转人工复核。
        try {
            $checkResult = WechatSecurityService::checkText($userId, $joined, $scene);
            if (!empty($checkResult['hit'])) {
                Log::info(
                    '服务人员内容微信检测命中风险（转人工复核）：user_id=' . $userId
                    . '，scene=' . $scene
                    . '，suggest=' . (string)($checkResult['suggest'] ?? '')
                    . '，trace_id=' . (string)($checkResult['trace_id'] ?? '')
                );
            }
        } catch (\Throwable $e) {
            // 双保险：checkText 内部已 fail-open，这里再兜一层，保证闸门永不抛异常打断业务
            Log::warning(
                '服务人员内容安全检测异常（不阻塞提交）：user_id=' . $userId
                . '，scene=' . $scene
                . '，message=' . $e->getMessage()
            );
        }

        return true;
    }
}
