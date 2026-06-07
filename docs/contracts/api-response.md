# API 响应契约与跨端约定

适用范围：`server` 后端、`admin` 管理端、`uniapp` 小程序/H5、`pc` Nuxt 站点。

## 1. 统一响应 Envelope

后端 JSON 接口统一返回：

```json
{
  "code": 1,
  "show": 0,
  "msg": "success",
  "data": {},
  "request_id": "req_20260523120000_0123456789abcdef"
}
```

字段约定：

| 字段 | 类型 | 说明 |
| --- | --- | --- |
| `code` | number | `1` 成功；`0` 业务失败；`-1` 登录失效；`-2` 未安装；`2` 打开新页面。 |
| `show` | number | `1` 前端可弹提示；`0` 静默或页面内处理。 |
| `msg` | string | 面向用户或运维的错误摘要；严禁包含密钥、完整支付原始报文。 |
| `data` | object/array | 业务数据。失败时可为空对象，也可包含可恢复动作。 |
| `request_id` | string | 请求追踪 ID；同时写入响应头 `X-Request-Id`。前端错误态、客服排查和日志检索必须带上它。 |

兼容性：`request_id` 为新增顶层字段，不改变 `code/data/msg/show`，现有三端请求封装可继续按原逻辑读取 `data`。

业务失败可在 `data.error_code` 携带稳定错误码；不要求旧接口一次性补齐，但新增/修正的核心接口必须优先使用。问卷链路推荐错误码见 `docs/architecture/core-state-machine.md#5-新人问卷任务状态`。

## 2. 请求头约定

| Header | 方向 | 必填 | 说明 |
| --- | --- | --- | --- |
| `token` | 前端 -> 后端 | 登录接口外按接口要求 | 既有登录态。 |
| `X-Request-Id` | 前端/网关 -> 后端 | 否 | 若传入，后端清洗后复用；否则后端生成。 |
| `X-Request-Id` | 后端 -> 前端 | 是 | 与响应体 `request_id` 一致。 |

## 3. 前端错误态要求

关键页（人员详情、订单详情、支付结果、确认函、问卷）遇到以下状态时必须页面内承接：

- 网络失败/超时：展示“加载失败”，提供“重试”和“返回上一页/首页”。
- `code = -1`：展示登录失效路径，引导重新登录，避免无限重试。
- `code = 0` 且 `show = 1`：展示 `msg`，页面内提供返回路径；若有 `data.retryable = true` 可显示重试。
- 支付结果轮询超过上限：展示“支付结果确认中”，提供“查看订单”和“重新查询”。
- 档期锁失效：展示“档期已释放或被占用”，提供“重新选择档期”。

## 4. 关键业务数据命名

| 领域 | 标识字段 | 状态字段 | 备注 |
| --- | --- | --- | --- |
| 订单 | `id`, `order_sn` | `order_status`, `pay_status` | 状态含义见 `docs/architecture/core-state-machine.md`。 |
| 支付流水 | `payment_sn` | `pay_status`, `transaction_id` | 回调必须校验金额、订单状态、第三方交易号、付款人。 |
| 档期 | `schedule_id`, `staff_id`, `schedule_date` | `status`, `lock_expire_time`, `lock_user_id` | 倒计时以前端当前时间与后端过期时间差展示。 |
| 问卷题库 | `id` | `status` | `type` 仅允许 `text/textarea/single/multiple/rating`；禁用不影响历史版本。 |
| 问卷配置 | `id`, `staff_id` | `status`, `push_mode`, `published_version_no` | `staff_id` 唯一；草稿保存不产生版本。 |
| 问卷版本 | `version_id`, `version_no` | 无状态 | 发布后不可变；任务和答案仅引用版本快照。 |
| 问卷任务 | `id`, `task_sn`, `order_id` | `status`, `send_status` | `order_id` 唯一；`status=0/3` 可提交，`1/2/4` 为已提交/已取消/已过期稳定态；`send_status=2` 时展示 `send_error/next_retry_time`。 |
| 问卷答案 | `id`, `task_id` | 无状态 | `task_id` 唯一；提交后不可覆盖。 |

问卷接口失败响应建议：

```json
{
  "code": 0,
  "show": 1,
  "msg": "问卷已过期，请联系服务人员重新发送",
  "data": {
    "error_code": "QUESTIONNAIRE_EXPIRED",
    "retryable": false,
    "action": "contact_admin"
  },
  "request_id": "req_20260523120000_0123456789abcdef"
}
```

前端必须在问卷列表/详情/提交错误态展示 `msg`，并保留或可复制 `request_id`；必填缺失类错误不得清空用户本地输入。已提交任务的重复提交可按成功幂等处理，但不得提示“新答案已覆盖”。

## 5. 契约变更流程

1. 后端新增/变更接口时，先更新 `docs/contracts/openapi-core.yaml` 或补充本文件。
2. 前端按契约更新 API 类型，不直接猜测字段。
3. QA 将契约变化加入接口回归用例；涉及支付/档期必须补并发或幂等测试。
4. 破坏性字段变更必须先双写/双读一个版本，再移除旧字段。
