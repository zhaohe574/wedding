<?php
namespace app;

use app\common\enum\ErrorCodeEnum;
use app\common\exception\ScheduleOccupancyException;
use app\common\exception\UserBookingConflictException;
use app\common\service\RequestContextService;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\facade\Log;
use think\Request;
use think\Response;
use Throwable;

/**
 * 应用异常处理类
 *
 * 约定：
 * 1. /api 与 /adminapi 属于接口应用，任何异常都必须返回 JSON 契约
 *    {code, show, msg, data, request_id}，不能返回 HTML 错误页；
 * 2. 响应体不包含堆栈、绝对路径、SQL 语句，这些只写日志；
 * 3. 日志统一带 request_id，便于按响应头 X-Request-Id 反查。
 */
class ExceptionHandle extends Handle
{
    /**
     * 不需要记录信息（日志）的异常类列表
     * @var array
     */
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
        ValidateException::class,
    ];

    /**
     * 接口应用前缀，命中后强制走 JSON 渲染
     */
    protected const API_APP_LIST = ['api', 'adminapi'];

    /**
     * 记录异常信息（包括日志或者其它方式记录）
     *
     * @access public
     * @param  Throwable $exception
     * @return void
     */
    public function report(Throwable $exception): void
    {
        if ($this->isIgnoreReport($exception)) {
            return;
        }

        try {
            $request = $this->app->request;
            $requestId = RequestContextService::ensureRequestId($request);
            $context = [
                'request_id' => $requestId,
                'exception' => $exception::class,
                'message' => $this->getMessage($exception),
                'code' => $this->getCode($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'method' => strtoupper((string)$request->method()),
                'path' => (string)$request->url(false),
                'admin_id' => (int)($request->adminId ?? 0),
                'user_id' => (int)($request->userId ?? 0),
            ];
            Log::error('[exception] ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            Log::error($exception->getTraceAsString());
        } catch (Throwable $ignored) {
            // 日志写入失败不能影响异常响应本身
        }
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @access public
     * @param Request $request
     * @param Throwable $e
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        // 框架内部用于中断流程的响应（如 JsonService::throw、验证器失败）原样返回
        if ($e instanceof HttpResponseException) {
            return $e->getResponse();
        }

        if ($this->isApiRequest($request)) {
            return $this->renderApiJson($request, $e);
        }

        // 其余应用（index/pc 等页面型应用）保持框架默认渲染
        return parent::render($request, $e);
    }

    /**
     * @notes 判断当前请求是否属于接口应用
     *
     * 多应用模式下 pathinfo 已经剥掉了应用段（/api/pay/notifyMnp → pay/notifyMnp），
     * 因此优先取 Http 上记录的应用名，取不到再回退到 pathinfo 首段。
     */
    protected function isApiRequest($request): bool
    {
        $appName = '';
        try {
            $appName = (string)$this->app->http->getName();
        } catch (Throwable $ignored) {
            $appName = '';
        }

        if ($appName === '') {
            $path = ltrim((string)$request->pathinfo(), '/');
            $appName = $path === '' ? '' : explode('/', $path)[0];
        }

        return in_array(strtolower($appName), self::API_APP_LIST, true);
    }

    /**
     * @notes 接口应用统一 JSON 异常响应
     */
    protected function renderApiJson($request, Throwable $e): Response
    {
        $requestId = RequestContextService::ensureRequestId($request);
        $statusCode = $e instanceof HttpException ? $e->getStatusCode() : 500;

        $payload = [
            'code' => 0,
            'show' => 1,
            'msg' => $this->resolveMessage($e, $statusCode),
            'data' => [],
            'request_id' => $requestId,
            'error_code' => $this->resolveErrorCode($e, $statusCode),
        ];

        $response = json($payload, $statusCode, [
            'Content-Type' => 'application/json; charset=utf-8',
            RequestContextService::HEADER_REQUEST_ID => $requestId,
        ]);

        if ($e instanceof HttpException) {
            $response->header($e->getHeaders());
        }

        return $response;
    }

    /**
     * @notes 决定返回给调用方的结构化错误码
     */
    protected function resolveErrorCode(Throwable $e, int $statusCode): string
    {
        if ($e instanceof ValidateException) {
            return ErrorCodeEnum::VALIDATE_FAILED;
        }

        if ($e instanceof ScheduleOccupancyException || $e instanceof UserBookingConflictException) {
            return ErrorCodeEnum::SCHEDULE_LOCKED;
        }

        if ($e instanceof HttpException) {
            return match ($statusCode) {
                401 => ErrorCodeEnum::TOKEN_EXPIRED,
                403 => ErrorCodeEnum::RISK_BLOCKED,
                default => ErrorCodeEnum::UNKNOWN,
            };
        }

        return ErrorCodeEnum::UNKNOWN;
    }

    /**
     * @notes 决定返回给调用方的提示文案
     */
    protected function resolveMessage(Throwable $e, int $statusCode): string
    {
        if ($e instanceof HttpException) {
            return match ($statusCode) {
                403 => '无权访问',
                404 => '接口不存在',
                405 => '请求方法不允许',
                default => $statusCode >= 500 ? '服务暂时不可用，请稍后重试' : '请求无法处理',
            };
        }

        if ($e instanceof ValidateException) {
            return $this->getMessage($e);
        }

        // 业务代码主动抛出的 RuntimeException / InvalidArgumentException 属于可读提示，
        // 但数据库与类型错误可能带出表名、SQL 与路径，一律不外泄。
        if ($this->isSafeBusinessException($e)) {
            return $this->getMessage($e);
        }

        return '服务暂时不可用，请稍后重试';
    }

    /**
     * @notes 判断异常是否属于可安全回显的业务异常
     */
    protected function isSafeBusinessException(Throwable $e): bool
    {
        foreach ([
            \PDOException::class,
            \think\db\exception\DbException::class,
            \think\db\exception\PDOException::class,
            \Error::class,
            \TypeError::class,
            \ParseError::class,
            \ErrorException::class,
        ] as $unsafe) {
            if ($e instanceof $unsafe) {
                return false;
            }
        }

        return $e instanceof \RuntimeException
            || $e instanceof \InvalidArgumentException
            || $e instanceof \LogicException;
    }
}
