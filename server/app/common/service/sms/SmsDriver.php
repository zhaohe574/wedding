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
namespace app\common\service\sms;

use app\common\enum\notice\NoticeEnum;
use app\common\enum\notice\SmsEnum;
use app\common\enum\YesNoEnum;
use app\common\model\Notice;
use app\common\model\notice\SmsLog;
use app\common\service\ConfigService;
use think\facade\Db;

/**
 * 短信驱动
 * Class SmsDriver
 * @package app\common\service\sms
 */
class SmsDriver
{
    /**
     * 单条验证码允许的最大校验次数（含错误与正确尝试）
     * 超过该次数后即使验证码正确也一律拒绝，防止暴力枚举。
     */
    const MAX_CHECK_NUM = 5;

    /**
     * 错误信息
     * @var
     */
    protected $error = null;

    /**
     * 发送结果
     * @var mixed|null
     */
    protected $result = null;

    /**
     * 默认短信引擎
     * @var
     */
    protected $defaultEngine;

    /**
     * 短信引擎
     * @var
     */
    protected $engine;

    /**
     * 架构方法
     * SmsDriver constructor.
     */
    public function __construct()
    {
        // 初始化
        $this->initialize();
    }

    /**
     * @notes 初始化
     * @return bool
     * @author 段誉
     * @date 2022/9/15 16:29
     */
    public function initialize()
    {
        try {
            $defaultEngine = ConfigService::get('sms', 'engine', false);
            if($defaultEngine === false) {
                throw new \Exception('请开启短信配置');
            }
            $this->defaultEngine = $defaultEngine;
            $classSpace = __NAMESPACE__ . '\\engine\\' . ucfirst(strtolower($defaultEngine)) . 'Sms';
            if (!class_exists($classSpace)) {
                throw new \Exception('没有相应的短信驱动类');
            }
            $engineConfig = ConfigService::get('sms', strtolower($defaultEngine), false);
            if($engineConfig === false) {
                throw new \Exception($defaultEngine . '未配置');
            }
            if ($engineConfig['status'] != 1) {
                throw new \Exception('短信服务未开启');
            }
            $this->engine = new $classSpace($engineConfig);
            if(!is_null($this->engine->getError())) {
                throw new \Exception($this->engine->getError());
            }
            return true;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }


    /**
     * @notes 获取错误信息
     * @return null
     * @author 段誉
     * @date 2022/9/15 16:29
     */
    public function getError()
    {
        return $this->error;
    }


    /**
     * @notes 获取发送结果
     * @return mixed|null
     */
    public function getResult()
    {
        return $this->result;
    }


    /**
     * @notes 发送短信
     * @param $mobile
     * @param $data
     * @return false
     * @author 段誉
     * @date 2022/9/15 16:29
     */
    public function send($mobile, $data)
    {
        try {
            // 发送频率限制
            $this->sendLimit($mobile);
            // 开始发送
            $result = $this->engine
                ->setMobile($mobile)
                ->setTemplateId($data['template_id'])
                ->setTemplateParams($data['params'])
                ->send();
            if(false === $result) {
                $this->result = method_exists($this->engine, 'getResult') ? $this->engine->getResult() : $this->engine->getError();
                throw new \Exception($this->engine->getError());
            }
            $this->result = $result;
            return $result;
        } catch(\Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }


    /**
     * @notes 发送频率限制
     * @param $mobile
     * @throws \Exception
     * @author 段誉
     * @date 2022/9/15 16:29
     */
    public function sendLimit($mobile)
    {
        $smsLog = SmsLog::where([
            ['mobile', '=', $mobile],
            ['send_status', '=', SmsEnum::SEND_SUCCESS],
            ['scene_id', 'in', NoticeEnum::SMS_SCENE],
            ])
            ->order('send_time', 'desc')
            ->findOrEmpty()
            ->toArray();
        if(!empty($smsLog) && ($smsLog['send_time'] > time() - 60)) {
            throw new \Exception('同一手机号1分钟只能发送1条短信');
        }
    }


    /**
     * @notes 校验手机验证码
     * @param $mobile
     * @param $code
     * @return bool
     * @author 段誉
     * @date 2022/9/15 16:29
     */
    public function verify($mobile, $code, $sceneId = 0)
    {
        $where = [
            ['mobile', '=', $mobile],
            ['send_status', '=', SmsEnum::SEND_SUCCESS],
            ['scene_id', 'in', NoticeEnum::SMS_SCENE],
            ['is_verify', '=', YesNoEnum::NO],
        ];

        if (!empty($sceneId)) {
            $where[] = ['scene_id', '=', $sceneId];
        }

        $smsLog = SmsLog::where($where)
            ->order('send_time', 'desc')
            ->findOrEmpty();

        // 没有验证码 或 最新验证码已校验 或 已过期(有效期：5分钟)
        if($smsLog->isEmpty() || $smsLog->is_verify || ($smsLog->send_time < time() - 5 * 60) ) {
            return false;
        }

        // 校验次数闸门：达到上限后直接拒绝，不再比对验证码内容。
        // 注意：失败次数达上限后不删除记录，让其自然过期（5分钟），
        // 避免攻击者通过「记录是否被删除」探测该手机号是否存在。
        if ((int)$smsLog->check_num >= self::MAX_CHECK_NUM) {
            return false;
        }

        // 上面这次读只用于「快速拒绝」，它是快照、不是并发保护。
        // 真正的并发保护在下面两条带条件的原子更新里：SQL 带上 check_num < MAX_CHECK_NUM 条件，
        // 数据库行锁保证并发下最多只有 MAX_CHECK_NUM 次能成功占用额度，
        // 避免 6 个并发请求同时通过上面的快照闸门而突破 5 次上限（原「读属性 → +1 → save」写法可被并发绕过）。
        $isCodeMatched = hash_equals((string)$smsLog->code, (string)$code);

        if ($isCodeMatched) {
            // 正确分支：自增校验次数 + 置为已校验。
            // 条件更新保证「码对但并发已超限」时 affected = 0，此时绝不把记录置为已校验。
            $affected = Db::name('sms_log')
                ->where('id', (int)$smsLog->id)
                ->whereNull('delete_time')
                ->where('check_num', '<', self::MAX_CHECK_NUM)
                ->where('is_verify', YesNoEnum::NO)
                ->inc('check_num')
                ->update([
                    'is_verify' => YesNoEnum::YES,
                    'update_time' => time(),
                ]);

            return $affected > 0;
        }

        // 错误分支：只占用一次校验额度，不置位 is_verify
        Db::name('sms_log')
            ->where('id', (int)$smsLog->id)
            ->whereNull('delete_time')
            ->where('check_num', '<', self::MAX_CHECK_NUM)
            ->inc('check_num')
            ->update([
                'update_time' => time(),
            ]);

        return false;
    }
}
