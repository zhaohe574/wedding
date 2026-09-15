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

namespace app\common\model\notice;

use app\common\enum\DefaultEnum;
use app\common\enum\notice\NoticeEnum;
use app\common\model\BaseModel;

/** 仅保留验证码短信配置。 */
class NoticeSetting extends BaseModel
{
    public function getSmsStatusDescAttr($value, $data)
    {
        $setting = json_decode((string)($data['sms_notice'] ?? ''), true) ?: [];
        return DefaultEnum::getEnableDesc((int)($setting['status'] ?? 0));
    }

    public function getTypeDescAttr($value, $data)
    {
        return NoticeEnum::getTypeDesc($data['type']);
    }

    public function getRecipientDescAttr($value): string
    {
        return [1 => '用户', 2 => '平台'][$value] ?? '';
    }

    public function getSmsNoticeAttr($value): array
    {
        return json_decode((string)$value, true) ?: [];
    }
}
