# 核心可靠性测试矩阵

## 1. P0 必测

| 场景 | 前置 | 步骤 | 期望 |
| --- | --- | --- | --- |
| 并发抢同一档期 | 同一 staff/date 可预约 | 两个用户同时 `lockSchedule` | 仅一个成功；失败方得到明确原因；数据库无双锁。 |
| 同用户重复锁同一档期 | 用户已锁定未过期 | 再次 `lockSchedule` | 成功续期或幂等返回；`lock_expire_time` 更新；不新增重复订单。 |
| 锁过期后创建订单 | 锁定后等待过期 | 调 `order/create` | 创建失败，提示重新选择档期，不生成有效订单。 |
| 订单创建失败释放锁 | 制造订单创建校验失败 | 调 `order/create` | 本次锁释放，后续其他用户可锁。 |
| 重复支付回调 | 支付流水已处理成功 | 重放同一回调 | 返回成功/已处理；`paid_amount` 不重复累加；资金流水不重复；通知不重复。 |
| 金额不一致回调 | 支付流水待支付 | 回调金额少/多 1 分 | 拒绝或登记异常；订单不推进；记录 `pay_callback_reject`。 |
| 取消后回调 | 订单待支付后取消 | 再发送成功回调 | 登记异常支付；不恢复订单；必要时创建退款申请。 |
| transaction_id 异常 | 支付流水待支付 | 回调缺失或同流水不同交易号 | 拒绝/异常登记，需人工处理。 |
| payer 不一致 | 新单已记录 payer 快照 | 使用其他 payer 回调 | 拒绝/异常登记；订单不推进。 |
| 问卷任务创建幂等 | 同一订单已进入待服务并有可用问卷版本 | 重复触发 `createTaskAfterOrderPendingService(order_id)` | 仅一个 `la_couple_questionnaire_task`；`order_id` 唯一；自动推送不重复创建站内信。 |
| 问卷重复提交 | 任务已提交 | 重放 `/couple_questionnaire/submit` 同一 payload | 可幂等返回成功；答案不覆盖、不新增；任务保持已提交，页面展示已提交态。 |
| 问卷提交并发 | 同一待提交/已查看任务 | 两个请求同时提交不同答案 | 仅首份答案落库；`uk_task_id` 保证单答案；另一方幂等成功或展示已提交态并带 `request_id`。 |
| 问卷权限隔离 | A 用户任务、B 用户登录 | B 访问 detail/submit | 返回不存在/无权限；不得泄露题目、答案、订单、服务人员隐私。 |

## 2. 小程序关键失败态

| 页面 | 失败输入 | 期望 UI |
| --- | --- | --- |
| 人员详情 | 人员不存在/接口 500/网络失败 | 页面级错误；重试；返回首页/上一页。 |
| 订单详情 | 订单不存在/无权限/登录失效 | 错误态；登录失效引导登录；展示 request_id。 |
| 支付结果 | 轮询超时/支付状态未知 | “支付结果确认中”；重新查询；查看订单。 |
| 确认函 | 当前确认函失效/接口失败 | 错误态；刷新；返回订单详情。 |
| 问卷详情 | 任务已取消/已提交/版本缺失 | 明确状态；禁止重复提交；返回订单或任务列表。 |
| 问卷提交 | 必填缺失/网络失败/登录失效 | 必填定位并保留输入；网络失败可重试；登录失效引导登录；展示或可复制 `request_id`。 |
| 预约锁倒计时 | `lock_expire_time` 过期 | 按钮禁用；提示重新选择；释放/重锁失败有恢复路径。 |

## 3. P1/P2 回归

| 领域 | 用例 |
| --- | --- |
| 响应契约 | 成功/失败/登录失效响应均包含 `request_id`，响应头 `X-Request-Id` 一致。 |
| 操作日志 | 后台操作日志 params 包含 `__context.request_id/admin_id/order_id`。 |
| 问卷后台契约 | 题库保存/配置保存/发布/手动发送均返回统一 Envelope；失败时 `data.error_code` 可被前端识别。 |
| 问卷版本快照 | 创建/发送任务后再发布新版本 | 既有任务继续展示创建时 `questions_snapshot/version_id/version_no`；新任务使用新版；已提交答案继续展示提交时快照。 |
| 问卷通知 | 自动/手动发送后任务 `send_status/send_time/last_send_time/send_count` 正确；站内信按 `target_type=questionnaire + task_id` 去重。 |
| 问卷统计查看 | 管理员可按 staff/status/send_status/keyword 过滤任务；服务人员视角（如启用）必须限制本人 `staff_id`。 |
| SQL 迁移 | 新库全量安装、老库先执行问卷 schema 再执行菜单 SQL 均可启动问卷入口。 |
| CI | PHP lint、admin type-check、uniapp validate、pc quality 在本地或 CI 可执行。 |
| 发布回滚 | admin/mobile/pc public 目录备份恢复演练。 |
| Imagick | 图片上传/缩略图在生产同版本镜像冒烟，无版本 warning 或已登记风险。 |

## 4. 断言建议

- 对状态流转用 `CoreStateContract::canTransit()` 做单元断言，防止新增非法跳转。
- 对支付回调用唯一资金流水/唯一 `payment_sn` 断言，防止重复写。
- 对档期锁用同一 `staff_id + schedule_date` 查询，确认同一时刻只有一个有效锁或预约。
- 对问卷提交用 `order_id`、`task_id` 两个唯一键断言：同一订单最多一个任务，同一任务最多一份答案。
- 对问卷后台操作日志断言 `params.__context.request_id/admin_id/path`，发送任务时补充 `task_id/order_id/staff_id`。
- 所有失败截图和日志必须附 `request_id`。
