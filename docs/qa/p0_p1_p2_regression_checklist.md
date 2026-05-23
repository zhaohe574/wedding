# P0/P1/P2 预约/支付/档期可靠性回归清单

> 角色：QA 工程师
> 范围：并发抢档、支付回调幂等/异常、取消与锁释放、小程序关键失败态，以及 P1/P2 接口契约/日志/上传/问卷/订阅/PC 导流/CI/迁移。
> 当前日期：2026-05-23（Asia/Shanghai）

## 0. 本轮实际验证结果

| 命令 | 结果 | 备注 |
|---|---|---|
| `node tests/qa/p0_p1_p2_contract_checks.js` | 通过 | 2026-05-23 返工复跑：19/19 passed，覆盖支付回调、档期锁、关键页失败态和迁移/API 契约；PAY-002 已兼容 `trim($transactionId) !== '' ? $transactionId : null` 等实际落库写法。 |
| `php -l` 重点 PHP 文件 | 通过 | `Payment.php`、`Order.php`、`PackageBooking.php`、`Schedule.php`、`PayNotifyLogic.php`、`WeChatPayService.php`、`OrderLogic.php` 均无语法错误；环境有 Imagick 版本 warning。 |
| 全量 `server/app` PHP 语法检查 | 未完整完成 | 120s 超时；已扫描大量文件未见语法错误，因输出量/耗时中断。 |
| `cd uniapp && npm run type-check:active` | 通过 | 小程序活跃类型检查通过。 |
| `cd admin && npm run type-check:active` | 通过 | 后台活跃类型检查通过。 |
| `cd uniapp && npm run validate:all` | 通过 | 配置验证和图鸟迁移验证均通过。 |
| `cd pc && npm run build` | 通过，有告警 | Nuxt 静态生成成功；存在 Node deprecation、Browserslist 过期、payload extraction/SSR false 告警。 |
| `cd admin && npm run build` | 通过，有告警 | Vite 构建成功；重要告警：`src/views/staff_center/couple_questionnaire/index.vue` 中 `taskPager` 为 const 却被分页 `v-model` 更新，可能运行时抛错；另有 Browserslist、chunk size 等告警。 |
| `cd uniapp && npm run build:h5` | 通过，有告警 | H5 构建成功；DCloud 新版本提示，`vconsole` eval 安全/压缩告警。 |

> 未完成项：无数据库/HTTP/真实微信支付环境，无法在本轮直接执行并发抢档、真实支付回调、退款、上传、订阅消息和真机小程序交互；这些已列入下方人工/集成回归清单。

## 1. 自动化/静态契约检查

### 1.1 命令

```bash
node tests/qa/p0_p1_p2_contract_checks.js
```

### 1.2 覆盖点

- `PAY-*`：支付回调金额、币种、`transaction_id`、幂等、关闭/取消后回调补偿、事务/行锁。
- `LOCK-*`：套餐临时锁 900 秒、订单事务、锁归属、条件更新/版本、取消释放锁、已预约档期不可复用。
- `MP-*`：人员详情、订单详情、支付结果、确认函通知入口、订单确认页锁续期/失败恢复。
- `REG-*`：页面/API 路由、安装 SQL 中确认函/问卷/通知订阅迁移入口。

### 1.3 预期解释

- 全部通过：静态契约满足本轮最低防线。
- 失败：按脚本输出的 `FAIL <ID>` 记录为可复现缺陷或待实现项；修复后再次运行同一命令确认。

### 1.4 返工记录（2026-05-23）

- 评审复跑曾出现 `PAY-002` 假阴性：旧脚本只按固定字符串统计 `transaction_id = ...`，无法覆盖当前 `Payment.php` 中正常/异常路径使用 `trim($transactionId) !== '' ? $transactionId : null` 的合法落库写法。
- 已修正为函数级正则组合：分别抽取 `paySuccess`、`handleExceptionalPaidCallback`，校验回调传入的 `$transactionId` 被写入 `$payment->transaction_id` 且随后保存，同时保留 `transaction_id` 唯一性行锁校验。
- 复跑证据：`node tests/qa/p0_p1_p2_contract_checks.js` 输出 `QA contract checks: 19/19 passed, 0 failed.`
- 注意：该脚本仍是静态契约检查，只能防止关键守卫/入口缺失；并发抢档、真实微信回调、退款、上传、订阅消息和存量库迁移幂等必须在集成环境按第 2～4 节执行。

---

## 2. P0 后端核心可靠性测试用例

> 需要：独立测试库、可访问 API、可构造用户/人员/套餐/档期数据；支付回调可通过测试桩或微信支付沙箱/测试商户模拟。

### P0-BE-01 并发抢同一档期/套餐

**前置数据**

- 人员 A：启用、审核通过。
- 套餐 P：上架，归属人员 A。
- 日期 D：符合预约规则，无已确认预订。
- 用户 U1/U2：均已登录。

**步骤**

1. U1、U2 同时调用订单预览/锁定入口，选择同一 `staff_id=A`、`package_id=P`、`date=D`。
2. 同时提交创建订单。
3. 查询 `package_booking`、`order`、`order_item`、`schedule`。

**期望**

- 最多 1 个用户创建订单成功。
- 失败用户收到明确冲突文案，如“正在被他人选购中/已被预约/档期已被其他用户抢占”。
- `package_booking` 同一 `package_id+staff_id+booking_date+time_slot(0)` 最多 1 条 `TEMP_LOCK/CONFIRMED` 有效记录。
- 不出现两个有效订单同时占用同一人员同一日期。

### P0-BE-02 同一用户锁续期与切换选择释放

**步骤**

1. U1 选择 A/P/D 进入确认页，记录 `lock_expire_time`。
2. U1 再次进入同一选择确认页。
3. U1 切换到 D2 或返回重新选择。

**期望**

- 同一选择续期成功，`lock_expire_time` 延后，不新增冲突有效锁。
- 切换选择后旧锁被释放或过期后被清理，新选择可锁定。
- 小程序确认页展示清晰失败恢复：锁失效时释放本地 session 并返回上一页重新选择。

### P0-BE-03 重复支付回调幂等

**前置数据**

- 订单 O：`STATUS_PENDING_PAY`。
- 支付流水 PAY：`STATUS_PENDING`，金额 M，微信支付。

**步骤**

1. 发送一次微信支付成功回调：`out_trade_no=PAY.payment_sn`、`transaction_id=T1`、`amount.total=M(分)`、`payer.openid=订单用户绑定 openid`。
2. 使用完全相同参数重复发送 2 次。
3. 查询 `payment`、`order`、`financial_flow`、订单日志、站内通知。

**期望**

- 第 1 次：PAY 变为已支付，订单进入支付后状态，写入 `transaction_id=T1`。
- 第 2/3 次：返回成功/已处理，但不重复增加 `paid_amount`，不重复生成资金流水，不重复生成通知/回访/结算。
- 资金流水按业务 id/sn 唯一。

### P0-BE-04 支付回调金额不一致

**步骤**

1. 对待支付流水 PAY 发送微信成功回调，但 `amount.total=M-1` 或 `M+1`。
2. 查询 PAY、订单、订单日志。

**期望**

- 回调被拒绝，PAY 不变为已支付。
- 订单不进入已支付/待服务。
- PAY 记录保留 `callback_data`、`transaction_id`、拒绝原因备注。
- 订单日志存在 `pay_callback_reject` 或等效拒绝记录。

### P0-BE-05 支付回调 payer 不匹配

**步骤**

1. 使用非订单用户绑定的 `payer.openid` 发送微信成功回调。
2. 查询 PAY、订单、日志。

**期望**

- 回调被拒绝，不标记支付成功。
- 记录拒绝原因和原始回调。

**当前自动检查关注点**

- `PAY-003` 会检查模型层是否有 payer/openid 归属校验；若失败，需后端补实现后再做本用例。

### P0-BE-06 取消后收到支付成功回调

**步骤**

1. 创建订单 O 并生成 PAY，订单处于待支付。
2. 取消订单 O。
3. 发送 PAY 的微信支付成功回调。
4. 查询订单、支付、退款、日志、锁。

**期望**

- 订单保持取消，不恢复为待服务/已完成。
- PAY 记录为已支付或异常已登记，包含 `late_callback_exception` 对应日志。
- 系统自动创建退款申请或明确补偿待人工处理。
- 订单锁/套餐锁/档期仍为已释放，不被支付回调重新占用。

### P0-BE-07 支付成功后档期确认失败补偿

**步骤**

1. 构造支付前另一订单已抢占同一档期。
2. 对当前订单发送成功回调。
3. 查询订单、支付、退款、确认函、日志。

**期望**

- 支付流水登记成功但订单不错误进入可履约状态。
- 自动创建退款申请或异常补偿记录。
- 当前确认函失效。
- 日志描述“首笔支付成功但档期锁定失败”等原因。

---

## 3. 小程序关键失败态与恢复路径

> 需要：微信开发者工具或真机；可通过 Charles/Fiddler/Mock Server 注入接口错误、超时、空数据和 401。

### P0-MP-01 人员详情页失败态

- 页面：`/packages/pages/staff_detail/staff_detail?id=<staff_id>`
- 注入：`/staff/detail` 返回 500/超时/空对象/已下架。
- 期望：页面级 `EmptyState`，不是永久 loading；展示错误文案；“重新加载”可再次请求；“返回首页”可回到首页；登录失效引导登录。

### P0-MP-02 订单详情页失败态

- 页面：`/pages/order_detail/order_detail?id=<order_id>`
- 注入：`/order/detail` 返回 500/404/401/超时。
- 期望：页面级错误态；提供“重新加载”；提供返回订单列表或首页；倒计时定时器停止并不会持续刷接口。

**当前自动检查关注点**

- `MP-002` 会检查是否存在页面级错误态。若失败，则说明目前仅 loading/toast 不足，需要前端补。

### P0-MP-03 支付结果页失败/处理中/成功/关闭

- 页面：`/pages/payment_result/payment_result?id=<order_id>&from=order&payment_sn=<sn>`
- 注入：
  1. 查询成功已支付；
  2. 查询待支付；
  3. 查询支付失败；
  4. 查询订单已取消/关闭；
  5. 查询接口 500/超时。
- 期望：
  - 已支付：显示成功金额和订单入口。
  - 待支付：可“刷新结果”，轮询次数/定时器可控，卸载后停止轮询。
  - 失败/关闭：显示失败原因，提示保留凭证/重新支付/查看订单。
  - 接口异常：页面级错误态，返回首页路径可用。

### P0-MP-04 确认函通知入口

- 从通知列表点击 `confirm_letter` 或 `confirm_letter_order`。
- 构造确认函版本已失效、通知只带 `letter_id` 无 `order_id`、确认函图片缺失。
- 期望：
  - 可 fallback 到当前有效确认函或订单详情。
  - 无可用确认函时弹窗说明并跳转订单列表/订单详情。
  - 不出现白屏或卡 loading。

### P0-MP-05 订单确认页预约锁倒计时/释放/失败恢复

- 页面：`/packages/pages/order_confirm/order_confirm?...`
- 注入：锁 session 缺失、锁续期失败、预览失败、提交失败。
- 期望：
  - 进入确认页会校验本地锁 session 与选择一致。
  - 续期失败时释放本地/服务端锁并返回上一页重新选择。
  - 提交成功清理锁 session。
  - 若返回上一页/切换选择，不应最长占用 15 分钟；需要根据产品要求确认是否补 onHide/onUnload 主动释放。

---

## 4. P1/P2 回归清单

### 4.1 接口契约

- 订单详情 `/order/detail` 返回：
  - `pay_status_display_key/desc`
  - `need_pay/need_pay_amount/need_pay_label`
  - `pay_remain_seconds/pay_timeout_action_desc`
  - `confirm_remain_seconds/confirm_timeout_action_desc`
  - `status_summary/waiting_for/next_action_text`
  - `confirm_letter` 相关字段可由当前确认函接口补齐。
- 支付结果查询返回：
  - 顶层 `pay_status`
  - `payment.pay_status/pay_status_desc/pay_amount/pay_time/payment_sn`
  - `order.order_status/order_status_desc/pay_status_desc/order_amount`
- 确认函接口：
  - `confirmLetterCurrent`、`confirmLetterById` 支持通知 fallback。
  - 图片 URL 不可用时前端有提示。

### 4.2 日志与可观测性

- 支付金额不一致：记录 `pay_callback_reject` 与原始回调。
- 取消后回调：记录 `pay_exception`、退款创建成功/失败日志。
- 档期锁冲突：接口返回明确冲突文案，服务端日志不出现未捕获异常。
- 小程序资源加载失败：仅 warn，不阻塞主页面。

### 4.3 上传/线下凭证

- 上传成功后订单显示凭证缩略图与审核中状态。
- 上传失败/超限/格式错误时 toast 明确，订单状态不被错误推进。
- 线下凭证审核中时倒计时与超时处理文案正确。

### 4.4 问卷

- 支付完成/订单完成后待填问卷入口出现。
- 问卷列表、详情、提交失败均有错误提示/重试或返回路径。
- 后台问卷分页、筛选、详情不崩溃；特别关注分页 `v-model` 运行时错误。

### 4.5 订阅与通知

- 支付成功、退款、确认函、问卷等订阅消息按场景请求授权。
- 用户拒绝订阅不阻断主流程。
- 站内通知可标记已读、删除、清理，跳转失败有详情弹窗兜底。

### 4.6 PC 导流

- PC 首页导流入口可跳转/展示小程序二维码或相应预约入口。
- PC 构建无新增错误；路由和资源路径正确。

### 4.7 CI/构建/迁移

建议每轮合并前执行：

```bash
# 后端 PHP 语法检查（无 Composer 时至少执行）
Get-ChildItem server/app -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }

# admin
cd admin && npm run type-check && npm run build

# pc
cd pc && npm run build

# uniapp
cd uniapp && npm run type-check:active && npm run validate:all && npm run build:h5

# QA 契约检查
node tests/qa/p0_p1_p2_contract_checks.js
```

迁移核对：

- 新装 SQL：`server/public/install/db/like.sql` 包含确认函、问卷、通知/订阅相关表和菜单。
- 存量库升级 SQL：需要单独提供并在测试库验证幂等执行。
- 回滚/重复执行不会破坏已有数据。

---

## 5. 缺陷报告模板

```text
标题：
优先级：P0/P1/P2
环境：分支/commit、后端地址、小程序基础库、设备、账号
前置数据：用户、订单、支付流水、人员、套餐、日期
复现步骤：
1.
2.
3.
实际结果：
期望结果：
证据：接口请求/响应、DB 截图或 SQL 查询、日志、录屏、自动化命令输出
影响范围：
建议归属：后端/前端/产品/运维
```

## 6. 当前已知需重点复核风险

- `PAY-003`：支付回调金额校验已存在，但 payer/openid 归属校验需自动化确认；若脚本失败，应按 P0 缺陷处理。
- `LOCK-005`：`Schedule::isScheduleRecordAvailableWithReason` 若把 `STATUS_BOOKED` 视为可用，可能导致已预约档期仍可下单，需要后端确认业务口径并修复。
- `MP-001/MP-002`：关键页必须有页面级错误态，不能只 toast 后停留 loading。
- 真实微信支付、退款、订阅消息、上传文件、并发数据库锁，需要在集成环境人工/接口压测验证，静态脚本不能替代。