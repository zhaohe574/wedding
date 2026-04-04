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
namespace app\common\service\sms\engine;

use think\facade\Log;

/**
 * 短信宝短信
 * Class SmsbaoSms
 * @package app\common\service\sms\engine
 */
class SmsbaoSms
{
    protected $error = null;
    protected $result = null;
    protected $config;
    protected $mobile;
    protected $templateId;
    protected $templateParams = [];

    protected const API_URL = 'http://api.smsbao.com/sms';

    protected const ERROR_MESSAGE = [
        '-1' => '参数不全',
        '-2' => '服务器空间不支持',
        '30' => '错误密码',
        '40' => '账号不存在',
        '41' => '余额不足',
        '42' => '帐户已过期',
        '43' => 'IP地址限制',
        '50' => '内容含有敏感词',
    ];

    public function __construct($config)
    {
        if (empty($config)) {
            $this->error = '请联系管理员配置参数';
            return false;
        }
        $this->config = $config;
    }


    /**
     * @notes 设置手机号
     * @param $mobile
     * @return $this
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;
        return $this;
    }


    /**
     * @notes 设置模板ID
     * @param $templateId
     * @return $this
     */
    public function setTemplateId($templateId)
    {
        $this->templateId = $templateId;
        return $this;
    }


    /**
     * @notes 设置模板参数
     * @param $templateParams
     * @return $this
     */
    public function setTemplateParams($templateParams)
    {
        $this->templateParams = is_array($templateParams) ? $templateParams : [];
        return $this;
    }


    /**
     * @notes 获取错误信息
     * @return string|null
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
     * @return array|false
     */
    public function send()
    {
        try {
            $content = $this->buildContent();
            $url = self::API_URL
                . '?u=' . rawurlencode($this->config['username'])
                . '&p=' . rawurlencode($this->config['api_key'])
                . '&m=' . rawurlencode($this->mobile)
                . '&c=' . urlencode($content);

            [$statusCode, $rawResult] = $this->request($url);
            $parsedResult = $this->extractResponseText($rawResult);
            $normalizedResult = $this->normalizeText($parsedResult);
            if ($statusCode !== 200) {
                $message = 'HTTP请求失败，状态码：' . $statusCode;
                $this->result = [
                    'code' => 'HTTP_ERROR',
                    'message' => $message,
                    'raw' => $rawResult,
                    'parsed' => $normalizedResult,
                    'status_code' => $statusCode,
                ];
                $this->logFailure($statusCode, $rawResult, $normalizedResult, $message);
                throw new \Exception($message);
            }

            if ($normalizedResult === '0') {
                $this->result = [
                    'code' => '0',
                    'message' => '发送成功',
                    'raw' => $rawResult,
                    'parsed' => $normalizedResult,
                    'status_code' => $statusCode,
                ];
                return $this->result;
            }

            [$code, $message] = $this->buildFailurePayload($normalizedResult);
            $this->result = [
                'code' => $code,
                'message' => $message,
                'raw' => $rawResult,
                'parsed' => $normalizedResult,
                'status_code' => $statusCode,
            ];
            $this->logFailure($statusCode, $rawResult, $normalizedResult, $message);
            throw new \Exception('短信宝短信错误：' . $message);
        } catch (\Exception $e) {
            if ($this->result === null) {
                $this->result = [
                    'code' => 'SEND_FAIL',
                    'message' => $e->getMessage(),
                    'raw' => '',
                    'parsed' => '',
                    'status_code' => 0,
                ];
            }
            $this->error = $e->getMessage();
            return false;
        }
    }


    /**
     * @notes 组装短信内容
     * @return string
     */
    protected function buildContent(): string
    {
        $content = trim((string)($this->templateParams['content'] ?? ''));
        if ($content === '') {
            throw new \Exception('短信内容不能为空');
        }

        $sign = trim((string)($this->config['sign'] ?? ''));
        if ($sign === '') {
            throw new \Exception('短信签名不能为空');
        }

        $signPrefix = '【' . $sign . '】';
        if (!$this->hasSignPrefix($content, $signPrefix)) {
            $content = $signPrefix . $content;
        }

        return $content;
    }


    /**
     * @notes 判断内容是否已包含签名前缀
     * @param string $content
     * @param string $signPrefix
     * @return bool
     */
    protected function hasSignPrefix(string $content, string $signPrefix): bool
    {
        return mb_substr($content, 0, mb_strlen($signPrefix, 'UTF-8'), 'UTF-8') === $signPrefix;
    }


    /**
     * @notes 标准化接口响应
     * @param string $response
     * @return string
     */
    protected function normalizeText(string $response): string
    {
        $response = preg_replace('/^\xEF\xBB\xBF/', '', $response) ?? $response;
        $response = preg_replace('/^\x{FEFF}+/u', '', $response) ?? $response;
        $response = preg_replace('/^[\x00-\x1F\x7F\s]+|[\x00-\x1F\x7F\s]+$/u', '', $response) ?? $response;
        return trim($response);
    }


    /**
     * @notes 构建失败响应
     * @param string $normalizedResult
     * @return array
     */
    protected function buildFailurePayload(string $normalizedResult): array
    {
        if ($normalizedResult === '') {
            return ['EMPTY_RESPONSE', '接口返回为空'];
        }

        if (preg_match('/^-?\d+$/', $normalizedResult)) {
            $detail = self::ERROR_MESSAGE[$normalizedResult] ?? '短信宝未提供对应说明';
            return [$normalizedResult, '错误码 ' . $normalizedResult . '，' . $detail];
        }

        return ['TEXT_RESPONSE', $normalizedResult];
    }


    /**
     * @notes 记录发送失败日志
     * @param int $statusCode
     * @param string $rawResult
     * @param string $parsedResult
     * @param string $message
     * @return void
     */
    protected function logFailure(int $statusCode, string $rawResult, string $parsedResult, string $message): void
    {
        $logData = [
            'mobile' => $this->maskMobile((string)$this->mobile),
            'status_code' => $statusCode,
            'raw' => $rawResult,
            'parsed' => $parsedResult,
            'message' => $message,
        ];
        Log::write('短信宝短信发送失败：' . json_encode($logData, JSON_UNESCAPED_UNICODE));
    }


    /**
     * @notes 发起HTTP请求
     * @param string $url
     * @return array [statusCode, rawBody]
     * @throws \Exception
     */
    protected function request(string $url): array
    {
        if (function_exists('curl_init')) {
            return $this->requestByCurl($url);
        }

        return $this->requestByFileGetContents($url);
    }


    /**
     * @notes 使用cURL请求
     * @param string $url
     * @return array [statusCode, rawBody]
     * @throws \Exception
     */
    protected function requestByCurl(string $url): array
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $body = curl_exec($curl);
        if ($body === false) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new \Exception('cURL请求失败：' . $error);
        }

        $statusCode = intval(curl_getinfo($curl, CURLINFO_HTTP_CODE));
        curl_close($curl);

        return [$statusCode, (string)$body];
    }


    /**
     * @notes 使用file_get_contents请求
     * @param string $url
     * @return array [statusCode, rawBody]
     * @throws \Exception
     */
    protected function requestByFileGetContents(string $url): array
    {
        if (!ini_get('allow_url_fopen')) {
            throw new \Exception('服务器空间不支持curl或者fsocket');
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 30,
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            $error = error_get_last();
            throw new \Exception('file_get_contents请求失败：' . ($error['message'] ?? '未知错误'));
        }

        $statusCode = $this->parseHttpStatusCode($http_response_header ?? []);
        return [$statusCode, (string)$body];
    }


    /**
     * @notes 提取响应正文文本
     * @param string $rawResult
     * @return string
     */
    protected function extractResponseText(string $rawResult): string
    {
        $rawResult = $this->normalizeText($rawResult);
        if ($rawResult === '') {
            return '';
        }

        $decoded = html_entity_decode($rawResult, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plainText = strip_tags($decoded);
        $plainText = html_entity_decode($plainText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plainText = $this->normalizeText($plainText);

        if ($plainText !== '') {
            return $plainText;
        }

        return $this->normalizeText($decoded);
    }


    /**
     * @notes 解析HTTP状态码
     * @param array $headers
     * @return int
     */
    protected function parseHttpStatusCode(array $headers): int
    {
        foreach ($headers as $header) {
            if (preg_match('/HTTP\/\d\.\d\s+(\d{3})/i', $header, $matches)) {
                return intval($matches[1]);
            }
        }

        return 0;
    }


    /**
     * @notes 脱敏手机号
     * @param string $mobile
     * @return string
     */
    protected function maskMobile(string $mobile): string
    {
        if (strlen($mobile) < 7) {
            return $mobile;
        }

        return substr($mobile, 0, 3) . '****' . substr($mobile, -4);
    }
}
