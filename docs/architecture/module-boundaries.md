# 模块边界、关键流程与低风险拆分路线

## 1. 模块边界

| 模块 | 后端边界 | 前端边界 | 不应承担 |
| --- | --- | --- | --- |
| 档期 `schedule` | 可用性、锁定、释放、候补、规则 | 日历展示、倒计时、重选路径 | 不直接创建订单支付，不直接修改支付状态 |
| 订单 `order` | 订单创建、确认、取消、服务状态、确认函 | 订单列表/详情/确认函 | 不直接处理三方回调验签 |
| 支付 `payment/pay` | 支付流水、预支付、回调、资金流水、异常支付补偿 | 支付结果页、支付按钮 | 不直接改变档期规则；取消后回调不恢复订单 |
| 问卷 `questionnaire` | 题库模板、服务人员草稿、发布版本、订单任务、答案快照、任务统计 | 问卷配置、任务列表/详情、填写提交页 | 不直接改变订单状态，不直接实现消息渠道，仅消费订单/服务人员上下文并调用通知模块 |
| 通知 `notification` | 站内信、订阅消息、短信去重、跳转目标 | 消息列表、订阅授权、通知入口跳转 | 不作为问卷任务状态的唯一事实来源；发送成功不代表已填写 |

## 2. 关键数据流

### 2.1 预约到订单

1. 用户查看人员/档期。
2. 前端调用 `schedule/checkAvailable`。
3. 用户提交确认页，调用 `schedule/lockSchedule`，后端返回 `schedule_id`、`lock_expire_time`。
4. 前端展示倒计时；倒计时过期后禁止继续提交，需重新锁档。
5. 调用 `order/create`，后端在事务内校验锁归属和未过期，创建订单、订单项、支付快照。
6. 创建失败时释放本次锁；创建成功进入待确认或待支付。

### 2.2 支付回调

1. 用户发起支付，生成 `payment_sn`。
2. 支付渠道回调，SDK 完成验签。
3. 后端锁定支付流水和订单行。
4. 校验金额、币种、订单状态、`transaction_id`、`payer`。
5. 正常回调：支付流水置已支付，订单推进，资金流水唯一写入，发送通知。
6. 重复回调：幂等返回成功，不重复累加/通知。
7. 取消后/超时后回调：登记异常支付，必要时创建退款申请。

### 2.3 问卷

1. 平台维护基础题库模板；题库只作为新草稿的素材，不反向修改既有服务人员草稿、发布版本或订单任务。
2. 服务人员或管理员维护服务人员问卷草稿，草稿包含标题、描述、推送方式、启停状态和题目数组。
3. 发布草稿生成不可变版本 `couple_questionnaire_version`；已创建任务保持创建时版本和题目快照，新版本只影响后续新任务。
4. 订单进入可发送状态（待服务/服务中/已完成/已评价）时创建问卷任务，幂等键为 `order_id`（数据库唯一键 `uk_order_id`），任务绑定订单、用户、主服务人员、发布版本、题目快照和默认 30 天过期时间。
5. 自动推送任务只在问卷配置为自动推送且任务未推送/推送失败时触发；手动发送仅允许待提交或已查看且未过期任务，通知模块按 `target_type + target_id + title + content` 做站内信去重。
6. 用户列表仅展示 `send_status = 已推送` 且归属当前用户的任务；详情/提交必须校验任务归属。
7. 用户首次查看待提交任务后可置为已查看；提交时使用任务行锁，校验 `status ∈ {待提交, 已查看}`、`send_status = 已推送`、未过期、必填题和题型，写入答案表唯一键 `task_id`，并将任务置为已提交；重复提交幂等成功但不得覆盖答案。
8. 统计/查看读取任务表与答案表：管理员可查看全部任务；服务人员只能查看自己任务；用户只能查看自己已推送任务。

> 现状说明：当前实现为“创建时冻结快照”，任务创建时写入 `version_id/version_no/questions_snapshot`，发布新版不刷新既有任务；答案提交时再次保存同一题目快照。若历史数据缺少快照，可只读回退到 `version_id` 对应版本并补写。

### 2.4 问卷数据流与接口协作

| 阶段 | 事实来源 | 关键接口/调用 | 幂等键 | 失败与恢复 |
| --- | --- | --- | --- | --- |
| 模板 | `la_couple_question_bank` | `/adminapi/growth.coupleQuestionBank/*` | `id` | 删除为软删除；题库变更不追溯历史版本。 |
| 配置草稿 | `la_couple_questionnaire.draft_questions` | `/adminapi/growth.coupleQuestionnaire/save` | `staff_id` | 保存失败不生成版本；前端保留草稿输入。 |
| 发布版本 | `la_couple_questionnaire_version` | `/adminapi/growth.coupleQuestionnaire/publish` | `questionnaire_id + version_no` | 发布事务失败整体回滚；无题目返回业务失败。 |
| 创建任务 | `la_couple_questionnaire_task` | `CoupleQuestionnaireService::createTaskAfterOrderPendingService/createTaskForOrder` | `order_id` | 无主服务人员/无可用版本时跳过并记录日志，不阻断支付成功；任务创建时冻结题目并设置过期时间。 |
| 发送通知 | 任务 `send_status/send_count/send_error/next_retry_time` + `notification` | `/adminapi/growth.coupleQuestionnaire/send`、服务人员自助发送、自动推送 | `task_id`；站内信按目标去重 | 发送先置推送中；成功置已推送；失败置推送失败并写 5 分钟后重试时间。 |
| 填写提交 | `la_couple_questionnaire_answer` + 任务状态 | `/api/couple_questionnaire/submit` | `task_id` | 行锁 + `uk_task_id` 防重复；重复提交幂等成功但不得覆盖首份答案。 |
| 统计查看 | 任务/答案只读查询 | `/adminapi/growth.coupleQuestionnaire/tasks`、`taskDetail`、用户 `lists/detail` | 查询条件 | 后台查看不得改变任务状态；用户首次详情查看可将待提交置为已查看；权限过滤必须在后端完成。 |

## 3. 低风险拆分策略

本轮不直接拆超大业务文件，避免覆盖后端/前端核心实现。推荐以下可渐进落地方式：

| 目标 | 第一步 | 第二步 | 回滚 |
| --- | --- | --- | --- |
| `Order` 模型过大 | 新增只读契约 `CoreStateContract`，集中状态图 | 抽 `OrderPaymentStateService`、`OrderTimeoutService`，保留原方法代理 | 删除服务类，原方法恢复内联 |
| 支付回调复杂 | 先补回调测试与校验清单 | 抽 `PaymentCallbackValidator` 仅做纯校验 | 回退到原 `Payment::validatePaidCallback` |
| uniapp 大页面 | 抽纯展示组件、错误态组件、倒计时 composable | API 调用抽到 page service | 保留原页面入口，一次只替换一个区域 |
| 问卷服务过大 | 先明确题库/版本/任务/答案/通知边界 | 拆 `QuestionnaireVersionService`、`QuestionnaireTaskService`、`QuestionnaireNotificationPort` | 服务类内部代理回原方法；必要时保留 `CoupleQuestionnaireService` 外观 |
| 问卷快照冻结策略 | 保持当前“创建时冻结快照” | 若要支持重发新版本，新增取消旧任务/新建任务的显式流程 | 回滚到读取原任务 `questions_snapshot`；禁止发布新版批量改写既有任务 |
| Auth 白名单过长 | 抽配置数组或权限策略类 | 后台菜单/角色对齐后移除硬编码 | 保留原数组作为 fallback |

## 4. 接口契约协作点

- 后端：核心接口变更先更新 `docs/contracts/openapi-core.yaml`，再改控制器/逻辑。
- 前端：关键失败态按 `docs/contracts/api-response.md` 读取 `request_id` 并提供重试/返回路径。
- QA：按 `docs/qa/core-reliability-test-matrix.md` 执行并发、幂等、取消后回调测试。
- 问卷：接口字段以 `docs/contracts/openapi-core.yaml` 和 `shared/contracts/core.ts` 为准；后台配置/发送接口必须继承后台操作日志，用户提交接口必须带 `request_id` 便于排查重复提交。

## 5. 技术权衡

- 短期继续兼容自动路由，降低回归风险；通过 OpenAPI 文档和 CI 门禁先治理契约漂移。
- request_id 先不新增数据库字段，避免老库未迁移失败；以响应体/响应头/操作日志 params 上下文满足排查。
- 状态机先以辅助契约类表达，不强行拦截现有业务流；待测试覆盖稳定后再逐步接入强校验。
- 问卷任务采用“创建时冻结快照”，可最大化保护用户已收到任务的一致性和 QA 可复现性；代价是服务人员修正错题后不能自动影响已创建任务，需要通过取消/过期旧任务并创建新任务的显式流程处理。
