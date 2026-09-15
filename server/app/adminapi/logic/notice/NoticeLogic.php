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

namespace app\adminapi\logic\notice;

use app\common\enum\notice\NoticeEnum;
use app\common\logic\BaseLogic;
use app\common\model\notice\NoticeSetting;

/** 验证码短信配置；业务通知由服务号模板模块管理。 */
class NoticeLogic extends BaseLogic
{
    public static function detail($params): array
    {
        $setting = NoticeSetting::whereIn('scene_id', NoticeEnum::SMS_SCENE)
            ->field('id,type,scene_id,scene_name,scene_desc,sms_notice')
            ->findOrEmpty($params['id'])->toArray();
        if (!$setting) { return []; }
        $setting['sms_notice'] = array_merge(['type' => 'sms', 'status' => 0, 'template_id' => '', 'content' => ''], $setting['sms_notice'] ?? []);
        $setting['sms_notice']['status'] = (int)$setting['sms_notice']['status'];
        $setting['sms_notice']['tips'] = NoticeEnum::getOperationTips(NoticeEnum::SMS, $setting['scene_id']);
        $setting['type'] = NoticeEnum::getTypeDesc($setting['type']);
        return $setting;
    }

    public static function set($params): bool
    {
        try {
            $setting = NoticeSetting::whereIn('scene_id', NoticeEnum::SMS_SCENE)->find($params['id'] ?? 0);
            if (!$setting) { throw new \RuntimeException('验证码短信配置不存在'); }
            $templates = $params['template'] ?? [];
            if (!is_array($templates) || count($templates) !== 1) {
                throw new \RuntimeException('请提交一项验证码短信模板');
            }
            $item = array_values($templates)[0];
            if (!is_array($item) || ($item['type'] ?? '') !== 'sms'
                || !isset($item['template_id'], $item['content'], $item['status'])
                || !in_array($item['status'], [0, 1, '0', '1'], true)) {
                throw new \RuntimeException('短信模板参数不合法');
            }
            if ((int)$item['status'] === 1 && (trim((string)$item['template_id']) === ''
                || !str_contains((string)$item['content'], '${code}'))) {
                throw new \RuntimeException('启用短信时必须配置模板ID和验证码变量');
            }
            $setting->sms_notice = json_encode(['type' => 'sms', 'template_id' => trim((string)$item['template_id']),
                'content' => (string)$item['content'], 'status' => (int)$item['status']], JSON_UNESCAPED_UNICODE);
            $setting->save();
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
