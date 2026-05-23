# 结构化日志上下文规范

## 1. request_id 来源

- 请求进入 `BaseMiddleware` 后调用 `RequestContextService::ensureRequestId()`。
- 若请求头包含 `X-Request-Id`，后端清洗后复用；否则生成 `req_yyyymmddHHMMSS_random`。
- JSON 响应体与响应头都返回同一个 request_id。

## 2. 标准上下文字段

| 字段 | 来源 | 说明 |
| --- | --- | --- |
| `request_id` | `RequestContextService` | 全链路排查主键。 |
| `admin_id` | `request->adminInfo/adminId` | 管理后台操作者。 |
| `user_id` | `request->userInfo/userId` | 用户端操作者。 |
| `order_id` | 请求参数或显式传入 | 订单相关日志必须传。 |
| `method` | request | HTTP 方法。 |
| `path` | request | 请求路径。 |
| `ip` | request | 调用方 IP。 |

## 3. 当前落地点

- `JsonService`：响应体/响应头返回 `request_id`。
- `OperationLog`：后台操作日志 `params.__context` 记录结构化上下文。
- `WeChatPayService`：微信支付/退款回调异常日志记录 `request_id`、`payment_sn`、`transaction_id` 等。

## 4. 新增业务日志写法

```php
use app\common\service\RequestContextService;
use think\facade\Log;

Log::write('订单状态推进失败：' . json_encode(RequestContextService::current(null, [
    'order_id' => $orderId,
    'action' => 'order_status_transition',
    'reason' => $reason,
]), JSON_UNESCAPED_UNICODE), 'error');
```

## 5. 注意事项

- 不在日志写明文密钥、完整支付原始报文、用户身份证等敏感信息。
- 支付报文只保留 `payment_sn/out_trade_no/transaction_id/attach/trade_state/amount.total` 等排障必要字段。
- 对失败态前端页面，展示或复制 `request_id` 给客服即可，不展示内部异常堆栈。
