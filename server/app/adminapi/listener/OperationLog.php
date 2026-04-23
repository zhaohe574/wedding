<?php


namespace app\adminapi\listener;


use ReflectionClass;
use think\Exception;
use think\facade\Log;
use Throwable;

class OperationLog
{
    /**
     * 操作日志字段最大字节数（la_operation_log.params/result 为 text）
     */
    private const LOG_TEXT_MAX_BYTES = 60000;

    /**
     * la_operation_log.url 字段最大字节数
     */
    private const LOG_URL_MAX_BYTES = 600;

    /**
     * @notes 管理员操作日志
     * @param $response
     * @return bool|void
     * @throws \ReflectionException
     * @author 段誉
     * @date 2022/4/8 17:09
     */
    public function handle($response)
    {
        try {
            $request = request();

            //需要登录的接口，无效访问时不记录
            if (!$request->controllerObject->isNotNeedLogin() && empty($request->adminInfo)) {
                return;
            }

            //不记录日志操作
            if (strtolower(str_replace('.', '\\', $request->controller())) === 'setting\system\log') {
                return;
            }

            //获取操作注解
            $notes = '';
            try {
                $re = new ReflectionClass($request->controllerObject);
                $doc = $re->getMethod($request->action())->getDocComment();
                if (empty($doc)) {
                    throw new Exception('请给控制器方法注释');
                }
                preg_match('/\s(\w+)/u', $re->getMethod($request->action())->getDocComment(), $values);
                $notes = $values[0];
            } catch (Exception $e) {
                $notes = $notes ?: '无法获取操作名称，请给控制器方法注释';
            }

            $params = $request->param();

            //过滤密码参数
            if (isset($params['password'])) {
                $params['password'] = "******";
            }
            //过滤密钥参数
            if (isset($params['app_secret'])) {
                $params['app_secret'] = "******";
            }

            //导出数据操作进行记录
            if (isset($params['export']) && $params['export'] == 2) {
                $notes .= '-数据导出';
            }

            //记录日志
            $systemLog = new \app\common\model\OperationLog();
            $systemLog->admin_id = $request->adminInfo['admin_id'] ?? 0;
            $systemLog->admin_name = $request->adminInfo['name'] ?? '';
            $systemLog->action = $notes;
            $systemLog->account = $request->adminInfo['account'] ?? '';
            $systemLog->url = $this->truncateText((string)$request->url(true), self::LOG_URL_MAX_BYTES);
            $systemLog->type = $request->isGet() ? 'GET' : 'POST';
            $systemLog->params = $this->truncateText((string)json_encode($params, true));
            $systemLog->ip = $request->ip();
            $systemLog->result = $this->truncateText((string)$response->getContent());
            return $systemLog->save();
        } catch (Throwable $e) {
            // 操作日志属于非核心流程，写入失败不应影响接口主流程
            Log::error('[OperationLog] 写入失败：' . $e->getMessage());
            return false;
        }
    }

    /**
     * 按字节截断文本，避免写入 text 字段超长导致异常
     * @param string $value
     * @param int $maxBytes
     * @return string
     */
    private function truncateText(string $value, int $maxBytes = self::LOG_TEXT_MAX_BYTES): string
    {
        if (strlen($value) <= $maxBytes) {
            return $value;
        }

        $suffix = '...(truncated)';
        $allowBytes = max(0, $maxBytes - strlen($suffix));

        if (function_exists('mb_strcut')) {
            return mb_strcut($value, 0, $allowBytes, 'UTF-8') . $suffix;
        }

        return substr($value, 0, $allowBytes) . $suffix;
    }
}
