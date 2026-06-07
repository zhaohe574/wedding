# Vue3 + uni-app 婚礼服务小程序 UI/UX 优化交付说明与回归测试指南

> 适用范围：本指南用于本轮婚礼服务小程序全端 UI/UX 系统级优化的交付说明、手工回归、微信开发者工具检查和最终验收记录。  
> 项目主体：`d:/2025-10-31/wedding-management/uniapp`  
> 日期：2026-05-24  
> 产物性质：文档交付，不修改业务代码、不调整路由、不移动主包/分包页面。

---

## 1. 前置条件

### 1.1 本地环境

1. Node.js：满足 `uniapp/package.json` 中 `engines.node >=18.19.0`。
2. 依赖安装目录：`d:/2025-10-31/wedding-management/uniapp/node_modules`。
3. 执行命令前进入项目主体目录：

```bash
cd d:/2025-10-31/wedding-management/uniapp
```

4. 微信开发者工具本地联调打开开发产物目录；发布包验收打开构建产物目录：

```text
d:/2025-10-31/wedding-management/uniapp/dist/dev/mp-weixin
d:/2025-10-31/wedding-management/uniapp/dist/build/mp-weixin
```

### 1.2 必须保持稳定的项目边界

1. 不重写 `uniapp/src/pages.json` 的主包/分包结构。
2. 不移动主包页面和 `packages` 分包页面。
3. tabBar 页面继续遵守 `switchTab-only` 约束：
   - `uniapp/src/pages/index/index.vue`
   - `uniapp/src/pages/dynamic/dynamic.vue`
   - `uniapp/src/pages/user/user.vue`
4. 主包页面不得直接引用分包专属模块；公共能力放在：
   - `uniapp/src/api`
   - `uniapp/src/utils`
   - `uniapp/src/components/base`
   - `uniapp/src/components/business`
5. 装修数据接口、订单确认、预约、支付链路保持稳定，不因 UI/UX 优化改变业务路径。

### 1.3 本轮 UI/UX 优化原则

1. 不是简单换色；需通过信息层级、留白、图片比例、状态表达、组件统一度提升质感。
2. 继续使用黑、白、香槟金作为主视觉：
   - 黑：权威、主按钮、标题强调。
   - 白：页面背景、内容呼吸感。
   - 香槟金：品牌点缀、状态强调、弱引导。
3. 优先复用现有设计骨架：
   - `uniapp/src/design/theme.ts`
   - `uniapp/src/styles/tokens.scss`
   - `uniapp/src/components/base/PageShell.vue`
   - `uniapp/src/components/base/BaseCard.vue`
   - `uniapp/src/components/base/BaseButton.vue`
   - `uniapp/src/components/base/BaseNavbar.vue`
   - `uniapp/src/components/tabbar/CustomTabbar.vue`
   - `uniapp/src/components/tabbar/tabbar.vue`
   - `uniapp/src/components/business/StaffCard.vue`
   - `uniapp/src/components/business/OrderCard.vue`
   - `uniapp/src/components/business/DynamicCard.vue`

---

## 2. 设计系统变更说明模板

> 前端完成实际优化后，按本节补充“变更前/变更后/验证结果”。若某项未改动，填写“不涉及”。

### 2.1 主题与设计令牌

| 模块 | 文件/入口 | 变更说明 | 影响页面 | 验证结果 |
|---|---|---|---|---|
| 主题预设 | `uniapp/src/design/theme.ts` | 例：保持 `black-white-gold`，统一 primary/secondary/accent、nav、tabbar 语义 | 全局 | 通过/不通过 |
| SCSS 令牌 | `uniapp/src/styles/tokens.scss` | 例：统一 page/card/action/tabbar 安全区变量、圆角、阴影、间距 | 全局 | 通过/不通过 |
| 公共样式 | `uniapp/src/styles/public.scss`、`uniapp/src/styles/utilities.scss` | 例：补充通用状态、列表、固定操作区样式 | 主包+分包 | 通过/不通过 |
| 售后样式 | `uniapp/src/styles/aftersale.scss` | 例：统一售后工单/投诉/回访状态表达 | 售后页面 | 通过/不通过 |
| 动态样式 | `uniapp/src/styles/dynamic.scss` | 例：统一动态卡片、图片比例、互动区 | 动态广场 | 通过/不通过 |

### 2.2 基础组件

| 组件 | 文件 | 变更说明 | 必查点 | 验证结果 |
|---|---|---|---|---|
| 页面壳 | `uniapp/src/components/base/PageShell.vue` | 例：统一页面背景、顶部/底部安全区、scene 标识 | 自定义导航、底部操作栏、安全区 | 通过/不通过 |
| 导航栏 | `uniapp/src/components/base/BaseNavbar.vue` | 例：适配微信胶囊、标题截断、返回区域 | 375/390/414 视口无遮挡 | 通过/不通过 |
| 卡片 | `uniapp/src/components/base/BaseCard.vue` | 例：统一圆角、投影、内边距、分割线 | 首页、订单、人员、售后卡片一致 | 通过/不通过 |
| 按钮 | `uniapp/src/components/base/BaseButton.vue` | 例：主/次/弱/危险/禁用/加载态 | 支付、预约、售后 CTA 优先级 | 通过/不通过 |
| 状态徽标 | `uniapp/src/components/base/StatusBadge.vue` | 例：订单、售后、通知状态统一 | 状态可识别且不只依赖颜色 | 通过/不通过 |
| 空状态 | `uniapp/src/components/base/EmptyState.vue` | 例：补齐文案、图标、引导按钮 | 列表空数据可恢复 | 通过/不通过 |
| 加载态 | `uniapp/src/components/base/LoadingState.vue` | 例：统一骨架/加载文案 | 弱网或首次加载可感知 | 通过/不通过 |
| 底部导航 | `uniapp/src/components/tabbar/CustomTabbar.vue`、`uniapp/src/components/tabbar/tabbar.vue` | 例：统一激活态、安全区、触控面积 | 首页/动态/我的切换 | 通过/不通过 |

### 2.3 业务组件

| 组件 | 文件 | 变更说明 | 关联页面 | 验证结果 |
|---|---|---|---|---|
| 人员卡片 | `uniapp/src/components/business/StaffCard.vue` | 例：头像/封面比例、姓名、价格、标签、档期状态 | `uniapp/src/pages/staff_list/staff_list.vue` | 通过/不通过 |
| 订单卡片 | `uniapp/src/components/business/OrderCard.vue` | 例：订单状态、金额、主次按钮、服务信息 | `uniapp/src/pages/order/order.vue` | 通过/不通过 |
| 动态卡片 | `uniapp/src/components/business/DynamicCard.vue` | 例：图片比例、内容摘要、互动按钮 | `uniapp/src/pages/dynamic/dynamic.vue` | 通过/不通过 |

### 2.4 最终变更摘要模板

```text
本轮 UI/UX 优化复用既有黑白香槟金主题体系，未改动 pages.json 主包/分包边界，未改变订单/支付/装修数据业务链路。

设计系统：
- [填写 theme.ts/tokens.scss/公共样式变化]

基础组件：
- [填写 PageShell/BaseCard/BaseButton/BaseNavbar/Tabbar 等变化]

核心页面：
- 首页：[填写首屏、分类入口、装修组件、CTA 优化]
- 档期/人员：[填写筛选、人员卡片、空/加载状态]
- 详情/预约/支付：[填写服务详情、预约下单、支付结果]
- 订单/售后/通知/评价/问卷：[填写状态表达和关键操作]
- 个人中心/服务人员端：[填写一致性优化]

验证：
- npm run type-check:active：[通过/不通过，时间]
- npm run build:mp-weixin:check：[通过/不通过，主包体积]
- 微信开发者工具：[无页面未注册/无模块加载错误/安全区正常/文字和按钮无溢出]
```

---

## 3. 核心页面手工检查步骤

> 检查原则：每个页面至少覆盖正常态、空状态、加载态、错误态；列表类页面还需覆盖长文本、长价格、无图片或图片加载失败。

### 3.1 首页 → 档期查询/人员列表

#### 前置条件

1. 构建并在微信开发者工具打开小程序。
2. 保证可进入首页 tab：`pages/index/index`。
3. 如依赖装修数据，准备至少一组包含 Banner、分类入口、推荐人员/服务模块的数据。

#### 步骤

| 步骤 | 页面/组件 | 操作 | 验证点 |
|---|---|---|---|
| 1 | `uniapp/src/pages/index/index.vue` | 打开首页 | 首屏信息层级清楚；顶部导航不压胶囊；黑白香槟金视觉统一 |
| 2 | 首页装修组件 | 查看 Banner/分类/推荐区 | 图片比例稳定；模块间留白一致；分类入口触控区域不小于常规小程序点击区 |
| 3 | 首页 CTA | 点击“档期查询”或同类入口 | 跳转 `pages/schedule_query/schedule_query` 成功，无路径错误 |
| 4 | `uniapp/src/pages/schedule_query/schedule_query.vue` | 切换日期、筛选条件 | 筛选项不换行错乱；选中/禁用/不可预约状态清晰 |
| 5 | 首页/分类入口 | 点击人员列表入口 | 跳转 `pages/staff_list/staff_list` 成功 |
| 6 | `uniapp/src/pages/staff_list/staff_list.vue` + `StaffCard.vue` | 滚动人员列表 | 人员姓名、价格、标签、档期状态不溢出；卡片间距统一 |
| 7 | 人员列表空数据 | 模拟无结果筛选 | 展示 `EmptyState` 或等价空状态，有恢复筛选/返回入口 |
| 8 | 人员列表加载 | 首次进入或下拉刷新 | 展示加载态，不出现白屏或布局跳动 |

#### 验证

- 首页首屏在 375/390/414 视口均无遮挡。
- 分类入口文字不超过容器，图标与文案垂直居中。
- `pages/schedule_query/schedule_query` 的日期、人员、状态筛选可正常点击。
- `pages/staff_list/staff_list` 长列表滚动流畅，底部内容不被 tabbar 或安全区遮挡。

#### 常见问题

- 如果首页跳转 tabbar 页面使用 `navigateTo` 失败，应检查是否应使用 `switchTab`。
- 如果人员卡片图片变形，应检查封面容器比例和 `mode` 设置。
- 如果筛选栏横向溢出，应检查小屏 375 宽度下的最小宽度和换行策略。

---

### 3.2 服务详情/预约下单 → 支付结果

#### 前置条件

1. 至少存在一名可预约服务人员。
2. 测试账号已登录，或页面可触发登录引导。
3. 支付可使用测试环境或模拟支付结果。

#### 步骤

| 步骤 | 页面/组件 | 操作 | 验证点 |
|---|---|---|---|
| 1 | `uniapp/src/packages/pages/staff_detail/staff_detail.vue` | 从人员列表进入详情 | 封面、头像、姓名、价格、档期、作品/套餐信息层级清晰 |
| 2 | `staff_detail` 底部操作区 | 点击预约/咨询/收藏 | 主按钮最突出；次按钮不抢占；底部安全区正常 |
| 3 | `uniapp/src/packages/pages/staff_booking/staff_booking.vue` | 选择日期、套餐、补充信息 | 表单间距合理；必填项明显；错误提示贴近字段 |
| 4 | `uniapp/src/packages/pages/order_confirm/order_confirm.vue` | 进入订单确认 | 服务、人员、日期、金额、优惠/备注信息完整 |
| 5 | `order_confirm` 支付按钮 | 点击提交/支付 | 加载态明确；按钮防重复点击；失败提示可恢复 |
| 6 | `uniapp/src/pages/payment_result/payment_result.vue` | 查看支付结果 | 成功/失败/处理中状态明确；查看订单和返回首页优先级清楚 |

#### 验证

- 预约链路中底部主按钮始终可见且不遮挡表单末尾。
- 支付相关按钮使用 `BaseButton` 或统一按钮规范，主次层级明确。
- 支付结果页不依赖颜色单独表达状态，应有图标/标题/说明文案。
- 失败结果能返回订单或重新发起支付。

#### 常见问题

- 若分包页面加载失败，检查 `src/pages.json` 中 `subPackages[0].root = packages` 和实际文件路径。
- 若主包页面引用了 `packages` 内部模块导致主包膨胀，应移到 `src/api`、`src/utils` 或 `src/components/base/business` 公共区。

---

### 3.3 我的订单 → 订单详情

#### 前置条件

准备以下订单状态数据：待支付、已支付/待服务、服务中、已完成、已取消、售后中。

#### 步骤

| 步骤 | 页面/组件 | 操作 | 验证点 |
|---|---|---|---|
| 1 | `uniapp/src/pages/order/order.vue` + `OrderCard.vue` | 打开我的订单 | 状态分组/筛选清晰；订单卡片不拥挤 |
| 2 | 订单卡片 | 查看长服务名、长人员名、金额 | 文本不溢出；金额醒目；状态徽标不覆盖内容 |
| 3 | 订单操作区 | 查看待支付/取消/评价/售后等按钮 | 主次按钮优先级明确；危险操作有区分 |
| 4 | `uniapp/src/pages/order_detail/order_detail.vue` | 点击订单进入详情 | 时间、人员、金额、服务项目、支付状态完整 |
| 5 | 订单详情底部操作区 | 执行支付/取消/售后/评价 | 固定操作区不遮挡内容；加载态和禁用态完整 |
| 6 | 空订单 | 清空订单数据或切换无结果状态 | 空状态有说明和引导入口 |

#### 验证

- `pages/order/order` 在 375 宽度下按钮不换行重叠。
- `pages/order_detail/order_detail` 底部操作区考虑 `env(safe-area-inset-bottom)`。
- 订单状态颜色、文案和位置全局一致。

#### 常见问题

- 如果订单卡片按钮过多，需折叠低频操作或降低视觉权重。
- 如果订单状态和支付按钮都很强，需确保“当前最重要动作”只有一个主按钮。

---

### 3.4 售后/通知/评价/问卷

#### 前置条件

准备至少以下数据：

1. 售后工单：待处理、处理中、已完成。
2. 投诉：待反馈、已回复、已关闭。
3. 通知：已读、未读、长标题、长内容。
4. 评价：可评价、已评价、带图评价。
5. 新人问卷：未填写、填写中、已提交。

#### 步骤

| 场景 | 页面路径 | 操作 | 验证点 |
|---|---|---|---|
| 售后首页 | `uniapp/src/packages/pages/aftersale/index.vue` | 打开售后服务 | 工单/投诉/回访入口清晰；状态入口不混乱 |
| 工单列表 | `uniapp/src/packages/pages/aftersale/ticket.vue` | 查看列表/空状态 | 状态徽标统一；卡片信息完整；空状态可返回或新建 |
| 提交工单 | `uniapp/src/packages/pages/aftersale/create_ticket.vue` | 输入问题并提交 | 表单错误提示明确；提交按钮安全区正常 |
| 工单详情 | `uniapp/src/packages/pages/aftersale/ticket_detail.vue` | 查看处理进度 | 时间线/处理结果层级清晰 |
| 投诉列表 | `uniapp/src/packages/pages/aftersale/complaint.vue` | 查看投诉 | 危险/投诉类状态表达克制但明确 |
| 发起投诉 | `uniapp/src/packages/pages/aftersale/create_complaint.vue` | 填写并提交 | 必填、上传、说明文案完整 |
| 回访问卷 | `uniapp/src/packages/pages/aftersale/callback.vue` | 填写回访 | 长表单滚动正常；提交按钮不遮挡最后一题 |
| 通知中心 | `uniapp/src/packages/pages/notification/index.vue` | 查看已读/未读 | 未读状态明显；长标题不撑破卡片 |
| 评价列表 | `uniapp/src/packages/pages/review/list.vue` | 查看评价 | 可评价入口明确；已评价状态不误导 |
| 发布评价 | `uniapp/src/packages/pages/review/publish.vue` | 评分、输入、上传 | 星级控件可点；提交前后加载/禁用态完整 |
| 问卷列表 | `uniapp/src/packages/pages/couple_questionnaire/list.vue` | 查看问卷 | 未填写/已填写状态清楚 |
| 问卷详情 | `uniapp/src/packages/pages/couple_questionnaire/detail.vue` | 填写长问卷 | 小屏下题目、选项、输入框不溢出；提交区安全 |

#### 验证

- 表单类页面提交按钮不被 iPhone 底部安全区遮挡。
- 售后和投诉状态不只依赖红/金色，应有清晰文案。
- 通知列表长标题最多截断到合理行数，不横向滚动。
- 评价图片上传区域在 375 宽度下不换行错乱。

#### 常见问题

- 如果售后页样式与其他页面割裂，优先检查 `src/styles/aftersale.scss` 是否复用令牌。
- 如果长问卷提交按钮遮挡内容，增加内容底部安全间距，而不是降低按钮层级。

---

### 3.5 个人中心

#### 前置条件

准备已登录与未登录两种账号状态。

#### 步骤

| 步骤 | 页面/组件 | 操作 | 验证点 |
|---|---|---|---|
| 1 | `uniapp/src/pages/user/user.vue` | 未登录进入 | 登录引导明确；不出现空白头像/错误昵称 |
| 2 | `pages/user/user` | 已登录进入 | 头像、昵称、权益/订单入口层级清晰 |
| 3 | 个人中心功能入口 | 点击订单、收藏、钱包、售后、设置 | 跳转路径正确；分包页面加载正常 |
| 4 | `uniapp/src/pages/user_data/user_data.vue` | 编辑资料 | 输入框、头像上传、保存按钮可用 |
| 5 | `uniapp/src/pages/user_set/user_set.vue` | 打开设置 | 列表项高度和触控范围合理 |
| 6 | 自定义 tabbar | 首页/动态/我的来回切换 | 激活态正确；底部安全区正常 |

#### 验证

- `pages/user/user` 与首页/订单页视觉一致。
- 入口图标和文案不拥挤，核心入口优先级明确。
- tabbar 不遮挡个人中心底部内容。

#### 常见问题

- 如果“我的”入口过多，应按订单、资产、服务、设置分组，而不是平均铺满。

---

### 3.6 动态广场

#### 前置条件

准备：纯文本动态、单图动态、多图动态、长文动态、无动态空状态。

#### 步骤

| 步骤 | 页面/组件 | 操作 | 验证点 |
|---|---|---|---|
| 1 | `uniapp/src/pages/dynamic/dynamic.vue` + `DynamicCard.vue` | 打开动态广场 | 顶部导航、筛选、列表节奏一致 |
| 2 | 动态卡片 | 查看长文本和图片 | 文本行数合理；图片比例统一；互动区不拥挤 |
| 3 | `uniapp/src/pages/dynamic_detail/dynamic_detail.vue` | 进入详情 | 内容可读性、图片预览、评论/互动清晰 |
| 4 | `uniapp/src/packages/pages/dynamic_publish/dynamic_publish.vue` | 发布动态 | 上传、输入、发布按钮状态完整 |
| 5 | 空动态 | 清空数据 | 有空状态和返回/发布引导 |

#### 验证

- 动态页作为 tabbar 页面不依赖 URL 参数传递场景。
- 长内容不造成横向滚动。
- 图片加载失败有占位或不破坏布局。

---

### 3.7 服务人员端一致性

#### 前置条件

使用服务人员账号或模拟服务人员权限。

#### 步骤

| 场景 | 页面路径 | 操作 | 验证点 |
|---|---|---|---|
| 工作台 | `uniapp/src/packages/pages/staff_center/staff_center.vue` | 打开服务人员中心 | `scene_lock=staff` 风格与消费者端一致但数据工作台更高效 |
| 订单管理 | `uniapp/src/packages/pages/staff_order_list/staff_order_list.vue` | 查看订单 | 状态、筛选、操作按钮清晰 |
| 订单详情 | `uniapp/src/packages/pages/staff_order_detail/staff_order_detail.vue` | 查看详情 | 客户/服务/时间/金额信息层级清楚 |
| 档期管理 | `uniapp/src/packages/pages/staff_schedule/staff_schedule.vue` | 维护档期 | 日期点击区域足够大；占用/可约状态明确 |
| 资料维护 | `uniapp/src/packages/pages/staff_profile/staff_profile.vue` | 编辑资料 | 保存按钮安全区正常；错误提示明确 |
| 作品管理 | `uniapp/src/packages/pages/staff_work_list/staff_work_list.vue` | 查看/编辑作品 | 图片比例统一；编辑入口清晰 |
| 套餐管理 | `uniapp/src/packages/pages/staff_package_list/staff_package_list.vue` | 查看/编辑套餐 | 价格、服务项、上下架状态明确 |

#### 验证

- 服务人员端复用 `PageShell`、`BaseCard`、`BaseButton` 等设计骨架。
- 不出现与消费者端完全割裂的颜色、圆角、按钮样式。
- 工作台数据密度可略高，但触控区域不能过小。

---

## 4. 小程序视口检查要点

### 4.1 推荐视口矩阵

| 视口 | 代表设备 | 必查原因 | 结果 |
|---|---|---|---|
| 375 x 667 | iPhone SE/小屏 | 最容易出现文字溢出、按钮换行、底部遮挡 | 通过/不通过 |
| 390 x 844 | iPhone 12/13/14 标准屏 | 常见全面屏安全区和胶囊适配 | 通过/不通过 |
| 414 x 896 | iPhone Plus/Pro Max | 大屏下留白、卡片节奏、首屏信息密度 | 通过/不通过 |
| Android 常见全面屏 | 微信开发者工具 Android 模拟器 | 字体渲染、底部安全区、状态栏差异 | 通过/不通过 |

### 4.2 每个视口统一检查清单

| 检查项 | 关联入口 | 通过标准 | 结果 |
|---|---|---|---|
| 自定义导航避让胶囊 | `BaseNavbar.vue`、`pages/index/index.vue`、`pages/dynamic/dynamic.vue`、`pages/user/user.vue` | 标题、返回按钮、右侧区域不与微信胶囊重叠 | 通过/不通过 |
| 底部 tabbar 安全区 | `CustomTabbar.vue`、`tabbar.vue` | 首页/动态/我的底部不贴边、不遮挡内容 | 通过/不通过 |
| 固定底部操作区 | `staff_detail`、`staff_booking`、`order_confirm`、`order_detail`、售后表单 | 主按钮完整可见，最后一行内容可滚到按钮上方 | 通过/不通过 |
| 文字溢出 | 首页分类、`StaffCard`、`OrderCard`、通知、问卷 | 无横向滚动；长文案截断或换行策略合理 | 通过/不通过 |
| 按钮重叠 | 订单卡片、订单详情、支付结果、售后提交 | 主次按钮不重叠；危险按钮不误触 | 通过/不通过 |
| 图片比例 | 首页装修图、人员卡片、动态卡片、作品图 | 图片不拉伸；加载失败不破坏布局 | 通过/不通过 |
| 触控面积 | 分类入口、筛选 chip、日期格、tabbar、底部按钮 | 点击区域足够大，左右间距不误触 | 通过/不通过 |
| 空状态 | 人员列表、订单列表、售后列表、通知、动态、问卷 | 有说明文案和恢复/引导动作 | 通过/不通过 |
| 加载状态 | 首页、列表、详情、提交动作 | 无白屏；按钮有 loading/disabled 防重复 | 通过/不通过 |
| 错误状态 | 网络失败、提交失败、支付失败 | 提示可理解，有重试或返回入口 | 通过/不通过 |

### 4.3 重点页面视口记录表

| 页面 | 375 | 390 | 414 | Android | 问题记录 |
|---|---|---|---|---|---|
| `uniapp/src/pages/index/index.vue` |  |  |  |  |  |
| `uniapp/src/pages/schedule_query/schedule_query.vue` |  |  |  |  |  |
| `uniapp/src/pages/staff_list/staff_list.vue` |  |  |  |  |  |
| `uniapp/src/packages/pages/staff_detail/staff_detail.vue` |  |  |  |  |  |
| `uniapp/src/packages/pages/staff_booking/staff_booking.vue` |  |  |  |  |  |
| `uniapp/src/packages/pages/order_confirm/order_confirm.vue` |  |  |  |  |  |
| `uniapp/src/pages/payment_result/payment_result.vue` |  |  |  |  |  |
| `uniapp/src/pages/order/order.vue` |  |  |  |  |  |
| `uniapp/src/pages/order_detail/order_detail.vue` |  |  |  |  |  |
| `uniapp/src/packages/pages/aftersale/index.vue` |  |  |  |  |  |
| `uniapp/src/packages/pages/notification/index.vue` |  |  |  |  |  |
| `uniapp/src/packages/pages/review/publish.vue` |  |  |  |  |  |
| `uniapp/src/packages/pages/couple_questionnaire/detail.vue` |  |  |  |  |  |
| `uniapp/src/pages/user/user.vue` |  |  |  |  |  |
| `uniapp/src/packages/pages/staff_center/staff_center.vue` |  |  |  |  |  |

---

## 5. 命令结果记录模板

### 5.1 `npm run type-check:active`

#### 步骤

```bash
cd d:/2025-10-31/wedding-management/uniapp
npm run type-check:active
```

#### 记录表

| 字段 | 记录 |
|---|---|
| 执行人 |  |
| 执行时间 |  |
| Node 版本 |  |
| 命令 | `npm run type-check:active` |
| tsconfig | `uniapp/tsconfig.active.json` |
| 是否通过 | 通过/不通过 |
| 错误数量 |  |
| 主要错误文件 |  |
| 是否涉及 UI/UX 修改引入 | 是/否 |
| 修复说明 |  |
| 复测结果 | 通过/不通过 |

#### 验证标准

- 命令退出码为 0。
- 无 Vue 模板类型错误。
- 无因组件 props、事件、路径别名引发的类型错误。

### 5.2 `npm run build:mp-weixin:check`

#### 步骤

```bash
cd d:/2025-10-31/wedding-management/uniapp
npm run build:mp-weixin:check
```

该命令包含：

1. `npm run build:mp-weixin`
2. `npm run validate:mp-size`

#### 记录表

| 字段 | 记录 |
|---|---|
| 执行人 |  |
| 执行时间 |  |
| 命令 | `npm run build:mp-weixin:check` |
| 构建产物 | `uniapp/dist/build/mp-weixin` |
| 是否构建成功 | 是/否 |
| 主包体积阈值 | `1.4 MB` |
| 实际主包体积 |  |
| 是否主包超限 | 是/否 |
| 主包目录体积 Top |  |
| 主包大文件 Top |  |
| 是否出现页面未注册 | 是/否 |
| 是否出现模块加载错误 | 是/否 |
| 修复说明 |  |
| 复测结果 | 通过/不通过 |

#### 验证标准

- 命令退出码为 0。
- `dist/build/mp-weixin` 成功生成。
- 主包体积不超过 `scripts/validate-mp-weixin-main-size.js` 当前阈值 `1.4 MB`。
- 构建日志无 `module not found`、页面路径缺失、组件解析失败。

---

## 6. 微信开发者工具检查项

### 6.1 导入与基础编译

#### 前置条件

已执行：

```bash
cd d:/2025-10-31/wedding-management/uniapp
npm run build:mp-weixin:check
```

#### 步骤

1. 打开微信开发者工具。
2. 本地接口联调导入开发目录；发布包验收导入构建目录：

```text
d:/2025-10-31/wedding-management/uniapp/dist/dev/mp-weixin
d:/2025-10-31/wedding-management/uniapp/dist/build/mp-weixin
```

3. 点击“编译”。
4. 打开控制台和网络面板。

#### 验证

| 检查项 | 通过标准 | 结果 |
|---|---|---|
| 主包体积 | 微信工具不提示主包超限；本地校验不超过 1.4 MB | 通过/不通过 |
| 页面注册 | `pages/index/index`、`pages/dynamic/dynamic`、`pages/user/user`、订单/支付/分包页面均可打开 | 通过/不通过 |
| 分包加载 | `packages/pages/staff_detail/staff_detail`、`order_confirm`、售后/通知/评价/问卷页面可打开 | 通过/不通过 |
| 模块加载 | 控制台无 `module not found`、组件未注册、路径大小写错误 | 通过/不通过 |
| 自定义 tabbar | 首页/动态/我的显示正确，激活态正确 | 通过/不通过 |
| 底部安全区 | iPhone 全面屏底部不遮挡 tabbar 和固定按钮 | 通过/不通过 |
| 文字溢出 | 首页分类、人员卡片、订单卡片、通知、问卷无横向溢出 | 通过/不通过 |
| 按钮重叠 | 支付、预约、订单、售后按钮不重叠、不贴边 | 通过/不通过 |
| 空/加载/错误状态 | 各核心列表和提交动作状态完整 | 通过/不通过 |

### 6.2 控制台关键字排查

在微信开发者工具控制台搜索或观察以下关键字：

```text
module not found
Cannot find module
page not found
Component is not found
not registered
subPackage
navigateTo:fail
switchTab:fail
request:fail
setData data size
```

若出现错误，记录：

| 时间 | 页面 | 错误信息 | 复现步骤 | 责任模块 | 处理结果 |
|---|---|---|---|---|---|
|  |  |  |  |  |  |

### 6.3 页面路径抽查清单

| 类型 | 页面路径 | 打开方式 | 结果 |
|---|---|---|---|
| 主包 tab | `pages/index/index` | tabbar/switchTab | 通过/不通过 |
| 主包 tab | `pages/dynamic/dynamic` | tabbar/switchTab | 通过/不通过 |
| 主包 tab | `pages/user/user` | tabbar/switchTab | 通过/不通过 |
| 主包 | `pages/staff_list/staff_list` | 首页入口/navigateTo | 通过/不通过 |
| 主包 | `pages/schedule_query/schedule_query` | 首页入口/navigateTo | 通过/不通过 |
| 主包 | `pages/payment_result/payment_result` | 支付回调/手动打开 | 通过/不通过 |
| 主包 | `pages/order/order` | 个人中心/支付结果 | 通过/不通过 |
| 主包 | `pages/order_detail/order_detail` | 订单列表 | 通过/不通过 |
| 分包 | `packages/pages/staff_detail/staff_detail` | 人员列表 | 通过/不通过 |
| 分包 | `packages/pages/staff_booking/staff_booking` | 人员详情 | 通过/不通过 |
| 分包 | `packages/pages/order_confirm/order_confirm` | 预约页 | 通过/不通过 |
| 分包 | `packages/pages/aftersale/index` | 个人中心/订单详情 | 通过/不通过 |
| 分包 | `packages/pages/notification/index` | 个人中心/消息入口 | 通过/不通过 |
| 分包 | `packages/pages/review/list` | 个人中心/订单详情 | 通过/不通过 |
| 分包 | `packages/pages/review/publish` | 订单详情 | 通过/不通过 |
| 分包 | `packages/pages/couple_questionnaire/list` | 个人中心/服务入口 | 通过/不通过 |
| 分包 | `packages/pages/couple_questionnaire/detail` | 问卷列表 | 通过/不通过 |
| 分包 | `packages/pages/staff_center/staff_center` | 服务人员入口 | 通过/不通过 |

---

## 7. 最终验收记录模板

### 7.1 交付信息

| 字段 | 内容 |
|---|---|
| 版本/分支 |  |
| 提交范围 | UI/UX 系统优化 |
| 是否改动路由结构 | 是/否；说明 |
| 是否改动主包/分包边界 | 是/否；说明 |
| 是否改动订单支付链路 | 是/否；说明 |
| 是否复用现有设计骨架 | 是/否；说明 |
| 文档路径 | `docs/qa/miniapp-ui-ux-regression-guide-20260524.md` |

### 7.2 验收结论

| 验收项 | 结论 | 证据 |
|---|---|---|
| 类型检查 | 通过/不通过 | 粘贴 `npm run type-check:active` 结果摘要 |
| 微信构建 | 通过/不通过 | 粘贴 `npm run build:mp-weixin:check` 结果摘要 |
| 主包体积 | 通过/不通过 | 粘贴主包体积 |
| 页面注册 | 通过/不通过 | 微信开发者工具页面抽查 |
| 模块加载 | 通过/不通过 | 控制台无错误截图/日志 |
| 小程序视口 | 通过/不通过 | 375/390/414/Android 检查记录 |
| 核心路径 | 通过/不通过 | 首页到支付、订单、售后、个人中心检查记录 |
| 服务人员端 | 通过/不通过 | staff_center 等页面检查记录 |

### 7.3 遗留问题记录

| 优先级 | 页面/组件 | 问题 | 影响 | 处理建议 | 负责人 | 状态 |
|---|---|---|---|---|---|---|
| P0 |  |  | 阻塞发布 |  |  |  |
| P1 |  |  | 影响核心体验 |  |  |  |
| P2 |  |  | 可后续优化 |  |  |  |

---

## 8. 前端完成后的最终交付说明定稿

> 定稿依据：前端实现评审结论、当前工作区实际改动清单、`validate:mp-size` 本地复核输出。  
> 重要边界：本节不声明已完成真机视觉走查；真机与微信开发者工具人工视口走查仍列为发布前待执行/风险项。

### 8.1 实际修改范围摘要

| 分类 | 实际结果 | 关键路径 |
|---|---|---|
| 代码范围 | 前端 UI/UX 改造集中在 `uniapp/src`，未以文档口径发现需要移动页面或重写路由的变更。 | `uniapp/src` |
| 设计系统 | 已调整黑白香槟金主题的页面底色、香槟金层级、卡片圆角、阴影、间距、tabbar 和 action 安全区令牌；页面背景从纯白进一步过渡到暖白/香槟灰，增强高端婚礼服务质感。 | `uniapp/src/design/theme.ts`、`uniapp/src/styles/tokens.scss`、`uniapp/src/styles/public.scss` |
| 基础组件 | 已复用并强化现有设计骨架：`PageShell` 统一背景与安全区，`BaseCard` 统一卡片圆角/描边/阴影，`BaseButton` 统一主次按钮高度、阴影和禁用态，`BaseNavbar` 延续自定义导航，`ActionArea` 优化底部固定操作区安全区，`EmptyState`/`LoadingState` 增强空态与加载态表达。 | `uniapp/src/components/base/PageShell.vue`、`BaseCard.vue`、`BaseButton.vue`、`BaseNavbar.vue`、`ActionArea.vue`、`EmptyState.vue`、`LoadingState.vue` |
| tabbar | 已优化自定义 tabbar 的胶囊化视觉、边框、圆角、底部安全区与激活态表达，继续服务 `首页 / 动态 / 我的` 三个 tabbar 页面。 | `uniapp/src/components/tabbar/tabbar.vue`、`uniapp/src/pages.json` tabBar 配置 |
| 主包核心页面 | 已覆盖首页首屏、档期查询、人员列表、订单列表、订单详情、支付结果、动态广场、资讯、个人中心等主包页面的视觉节奏、卡片/按钮/状态表达与安全区。 | `uniapp/src/pages/index/index.vue`、`schedule_query/schedule_query.vue`、`staff_list/staff_list.vue`、`order/order.vue`、`order_detail/order_detail.vue`、`payment_result/payment_result.vue`、`dynamic/dynamic.vue`、`news/news.vue`、`user/user.vue` |
| 分包核心页面 | 已覆盖通知、评价列表、新人问卷列表/详情等分包页面，问卷详情补充加载态、错误态、提交成功态、必填校验提示与底部操作区。 | `uniapp/src/packages/pages/notification/index.vue`、`review/list.vue`、`couple_questionnaire/list.vue`、`couple_questionnaire/detail.vue` |
| 路由/页面注册 | 本轮说明未记录需要重写 `pages.json` 主包/分包结构；仍按主包 `pages` 与分包 `packages` 边界验收。 | `uniapp/src/pages.json` |
| 业务链路 | 交付口径为 UI/UX 改造；不声明改变装修数据接口、预约下单、订单支付、售后提交流程。相关链路仍需按本指南第 3 章做人工回归。 | 首页装修、预约、订单、支付、售后相关页面 |
| 静态资源/体积 | 构建后主包 1.33 MB，低于 1.40 MB 阈值；文件数 410。仍需留意 `common/vendor.js`、`app.wxss` 和页面样式增长。 | `uniapp/dist/build/mp-weixin` |

#### 8.1.1 最终交付摘要

本轮 UI/UX 优化基于既有 Vue3 + uni-app 小程序设计骨架完成，不是简单换色。改造重点是继续沿用黑、白、香槟金品牌基调，同时通过暖白页面底、香槟金描边、统一卡片半径、增强阴影层级、统一底部安全区、补齐空/加载/错误状态、强化订单/问卷/支付等关键操作表达，形成更统一、高端、大气、简洁、易用的婚礼服务小程序体验。

核心复用与改造如下：

1. 设计系统：复用 `src/design/theme.ts` 与 `src/styles/tokens.scss`，统一页面背景、surface、secondary/secondary-soft、radius、shadow、space、tabbar、安全区等令牌。
2. 基础组件：复用 `PageShell`、`BaseCard`、`BaseButton`、`BaseNavbar`、`ActionArea`、`EmptyState`、`LoadingState`，避免页面各自散落实现基础 UI 状态。
3. 主包页面：覆盖 `index`、`schedule_query`、`staff_list`、`order`、`order_detail`、`payment_result`、`dynamic`、`news`、`user` 等高频路径。
4. 分包页面：覆盖 `notification`、`review/list`、`couple_questionnaire/list`、`couple_questionnaire/detail`；其中问卷详情增强加载、错误、提交成功、必填校验和底部操作安全区。
5. 稳定性边界：继续按 `pages.json` 中主包/分包结构验收；tabbar 页面仍应使用 `switchTab`，分包页面仍从主包入口或业务入口进入；不以 UI/UX 改造名义重写订单支付链路。

### 8.2 验证命令结果

#### 8.2.1 类型检查

执行目录：`d:/2025-10-31/wedding-management/uniapp`

```bash
npm run type-check:active
```

| 字段 | 结果 |
|---|---|
| 是否通过 | 通过 |
| 结论来源 | 前端评审结论 |
| 失败文件/错误摘要 | 无阻塞错误记录 |
| 发布含义 | TypeScript/Vue 类型检查未阻塞本轮 UI/UX 交付 |

#### 8.2.2 微信小程序构建与主包体积校验

执行目录：`d:/2025-10-31/wedding-management/uniapp`

```bash
npm run build:mp-weixin:check
```

| 字段 | 结果 |
|---|---|
| 是否通过 | 通过 |
| 构建产物目录 | `uniapp/dist/build/mp-weixin` |
| 主包体积阈值 | `1.40 MB` |
| 实际主包体积 | `1.33 MB` |
| 文件数量 | `410` |
| 主包体积结论 | 通过，距离阈值约 `0.07 MB` 余量 |
| 页面注册结论 | 核心页面构建产物注册完整 |
| 模块加载结论 | 构建检查未发现阻塞性模块加载错误 |
| 结论来源 | 前端评审结论 + 本地 `npm run validate:mp-size` 复核 |

本地复核的主包体积 Top 摘要：

| 排名 | 目录/文件 | 体积 |
|---|---|---|
| 1 | `pages` | 396.9 KB |
| 2 | `common` | 351.8 KB |
| 3 | `components` | 139.0 KB |
| 4 | `node-modules` | 125.7 KB |
| 5 | `app.wxss` | 125.1 KB |

本地复核的主包大文件 Top 摘要：

| 排名 | 文件 | 体积 |
|---|---|---|
| 1 | `common/vendor.js` | 351.8 KB |
| 2 | `app.wxss` | 125.1 KB |
| 3 | `uni_modules/vk-uview-ui/components/u-icon/u-icon.wxss` | 47.3 KB |
| 4 | `node-modules/@tuniao/tnui-vue3-uniapp/components/icon/src/icon.wxss` | 35.8 KB |
| 5 | `pages/order_detail/order_detail.js` | 32.6 KB |

### 8.3 微信开发者工具检查结论

> 当前结论仅覆盖构建产物与评审结果；微信开发者工具中的人工真机/模拟器视觉走查不得标记为已完成。

| 检查项 | 当前结论 | 证据/备注 |
|---|---|---|
| 主包体积 | 通过 | `1.33 MB / 1.40 MB`，文件数 410 |
| 页面注册 | 通过 | 前端评审结论：核心页面构建产物注册完整 |
| 模块加载 | 通过 | 前端评审结论：无阻塞性模块加载错误；仍建议在微信开发者工具控制台复查 |
| tabbar | 待人工走查 | 需在微信开发者工具检查首页/动态/我的切换、激活态与底部安全区 |
| 底部安全区 | 待人工走查 | `ActionArea`、`PageShell`、tabbar 已做安全区优化，但需 375/390/414/Android 视口确认 |
| 文字溢出 | 待人工走查 | 人员卡片、订单卡片、通知、问卷长文本仍需模拟数据验证 |
| 按钮重叠 | 待人工走查 | 预约、支付、订单、售后、问卷提交按钮需在小屏确认 |
| 空/加载/错误状态 | 部分已实现，待人工走查 | `EmptyState`、`LoadingState` 和问卷详情状态已增强；列表空态/错误态需逐页验证 |

发布前微信开发者工具必须继续执行：

1. 导入 `d:/2025-10-31/wedding-management/uniapp/dist/build/mp-weixin`。
2. 打开控制台，确认无 `module not found`、`page not found`、组件未注册、分包加载失败。
3. 逐页打开第 8.5 节页面，记录页面注册和模块加载结果。
4. 切换 375/390/414/Android 视口，按第 8.4 节记录视觉结果。

### 8.4 核心页面视口验收记录

> 当前状态：未完成真机视觉走查；以下记录为“待执行”。测试人员必须按表填写，不能直接沿用构建通过结论替代视觉验收。  
> 结果填写：通过 / 不通过 / 不涉及；不通过必须关联问题编号。

| 页面/路径 | 375 x 667 | 390 x 844 | 414 x 896 | Android 全面屏 | 重点检查 | 问题编号 |
|---|---|---|---|---|---|---|
| `uniapp/src/pages/index/index.vue` | 待执行 | 待执行 | 待执行 | 待执行 | 首页首屏、装修组件、分类入口、tabbar 安全区 | 待填 |
| `uniapp/src/pages/schedule_query/schedule_query.vue` | 待执行 | 待执行 | 待执行 | 待执行 | 日期筛选、档期状态、按钮触控 | 待填 |
| `uniapp/src/pages/staff_list/staff_list.vue` | 待执行 | 待执行 | 待执行 | 待执行 | 人员卡片、价格、标签、空/加载状态 | 待填 |
| `uniapp/src/packages/pages/staff_detail/staff_detail.vue` | 待执行 | 待执行 | 待执行 | 待执行 | 服务详情、底部预约按钮、安全区 | 待填 |
| `uniapp/src/packages/pages/staff_booking/staff_booking.vue` | 待执行 | 待执行 | 待执行 | 待执行 | 表单、日期/套餐选择、提交按钮 | 待填 |
| `uniapp/src/packages/pages/order_confirm/order_confirm.vue` | 待执行 | 待执行 | 待执行 | 待执行 | 金额、服务信息、支付 CTA | 待填 |
| `uniapp/src/pages/payment_result/payment_result.vue` | 待执行 | 待执行 | 待执行 | 待执行 | 成功/失败/处理中状态、返回动作 | 待填 |
| `uniapp/src/pages/order/order.vue` | 待执行 | 待执行 | 待执行 | 待执行 | 订单卡片、状态、操作按钮 | 待填 |
| `uniapp/src/pages/order_detail/order_detail.vue` | 待执行 | 待执行 | 待执行 | 待执行 | 订单详情、底部操作区、安全区 | 待填 |
| `uniapp/src/pages/dynamic/dynamic.vue` | 待执行 | 待执行 | 待执行 | 待执行 | 动态卡片、图片比例、互动区 | 待填 |
| `uniapp/src/packages/pages/aftersale/index.vue` | 待执行 | 待执行 | 待执行 | 待执行 | 售后入口、工单/投诉状态 | 待填 |
| `uniapp/src/packages/pages/notification/index.vue` | 待执行 | 待执行 | 待执行 | 待执行 | 已读/未读、长标题、列表空态 | 待填 |
| `uniapp/src/packages/pages/review/list.vue` | 待执行 | 待执行 | 待执行 | 待执行 | 我的评价、可评价入口 | 待填 |
| `uniapp/src/packages/pages/review/publish.vue` | 待执行 | 待执行 | 待执行 | 待执行 | 星级、文本、图片上传、提交按钮 | 待填 |
| `uniapp/src/packages/pages/couple_questionnaire/list.vue` | 待执行 | 待执行 | 待执行 | 待执行 | 问卷状态、空状态、入口 | 待填 |
| `uniapp/src/packages/pages/couple_questionnaire/detail.vue` | 待执行 | 待执行 | 待执行 | 待执行 | 长问卷滚动、选项、提交安全区 | 待填 |
| `uniapp/src/pages/user/user.vue` | 待执行 | 待执行 | 待执行 | 待执行 | 个人中心入口、未登录态、tabbar | 待填 |
| `uniapp/src/packages/pages/staff_center/staff_center.vue` | 待执行 | 待执行 | 待执行 | 待执行 | 服务人员工作台、数据卡片、入口 | 待填 |
| `uniapp/src/packages/pages/staff_order_list/staff_order_list.vue` | 待执行 | 待执行 | 待执行 | 待执行 | 服务人员订单状态、操作按钮 | 待填 |
| `uniapp/src/packages/pages/staff_schedule/staff_schedule.vue` | 待执行 | 待执行 | 待执行 | 待执行 | 档期管理、日期触控、状态表达 | 待填 |

### 8.5 页面注册与模块加载抽查记录

| 类型 | 页面路径 | 预期打开方式 | 当前结论 | 发布前复查要求 |
|---|---|---|---|---|
| tabbar | `pages/index/index` | `switchTab` | 构建产物注册完整 | 微信开发者工具打开并截图/记录 |
| tabbar | `pages/dynamic/dynamic` | `switchTab` | 构建产物注册完整 | 微信开发者工具打开并截图/记录 |
| tabbar | `pages/user/user` | `switchTab` | 构建产物注册完整 | 微信开发者工具打开并截图/记录 |
| 主包 | `pages/staff_list/staff_list` | `navigateTo` | 构建产物注册完整 | 从首页/分类入口进入 |
| 主包 | `pages/schedule_query/schedule_query` | `navigateTo` | 构建产物注册完整 | 从首页入口进入 |
| 主包 | `pages/payment_result/payment_result` | 支付回调/手动打开 | 构建产物注册完整 | 用支付成功/失败/处理中数据检查 |
| 主包 | `pages/order/order` | 个人中心/支付结果 | 构建产物注册完整 | 从个人中心和支付结果入口进入 |
| 主包 | `pages/order_detail/order_detail` | 订单列表 | 构建产物注册完整 | 从订单卡片进入 |
| 分包 | `packages/pages/staff_detail/staff_detail` | 人员列表 | 构建产物注册完整 | 从人员列表进入 |
| 分包 | `packages/pages/staff_booking/staff_booking` | 人员详情 | 构建产物注册完整 | 从人员详情预约进入 |
| 分包 | `packages/pages/order_confirm/order_confirm` | 预约页 | 构建产物注册完整 | 从预约页进入 |
| 分包 | `packages/pages/aftersale/index` | 个人中心/订单详情 | 构建产物注册完整 | 从售后入口进入 |
| 分包 | `packages/pages/notification/index` | 个人中心/消息入口 | 构建产物注册完整 | 从消息入口进入 |
| 分包 | `packages/pages/review/list` | 个人中心/订单详情 | 构建产物注册完整 | 从评价入口进入 |
| 分包 | `packages/pages/review/publish` | 订单详情 | 构建产物注册完整 | 从可评价订单进入 |
| 分包 | `packages/pages/couple_questionnaire/list` | 个人中心/服务入口 | 构建产物注册完整 | 从问卷入口进入 |
| 分包 | `packages/pages/couple_questionnaire/detail` | 问卷列表 | 构建产物注册完整 | 用未填写/已提交问卷分别进入 |
| 分包 | `packages/pages/staff_center/staff_center` | 服务人员入口 | 构建产物注册完整 | 用服务人员账号进入 |

### 8.6 介绍性栏位移除与首页团队信息栏保留变更回归记录

> 定稿依据：UX 只读审查、前端二审通过摘要、首页团队信息栏恢复实现摘要、产品只读验收结论与本地关键页面源码复核。前端二审结论为：关键失败残留已清零；剩余 hero/banner 多为业务信息卡，不按介绍性栏位缺陷处理。  
> 当前同步时间：2026-05-24。补充结论：用户明确要求“首页的团队信息栏不要去掉”，`home-brand` 驱动的 `home-page__team-card` 属于业务信息/团队展示入口，应保留；但仍不得恢复上一轮删除的纯营销/介绍型头部栏位。

#### 8.6.1 最终移除范围与清理结果

| 优先级 | 页面/路径 | 已清理的介绍性栏位/失败残留 | 必须保留的功能区域 | 最终回归口径 |
|---|---|---|---|---|
| P0 | `uniapp/src/pages/schedule_query/schedule_query.vue` | 已清理导航下方 `schedule-guide`、`BOOKING CHECK`、大段“先确认日期、地区与服务分类”说明卡 | 预约日期、预约地区、服务分类、风格标签、关键词、排序方式、底部“开始查询”按钮 | 首屏直接进入筛选表单；用户仍能完成筛选并跳转人员列表 |
| P0 | `uniapp/src/pages/staff_list/staff_list.vue` | 已清理营销/说明式标题文案，如“精准匹配”“已按档期条件筛选服务团队” | `filter-summary` 作为功能性筛选 chips 容器保留；重筛入口、人员列表、收藏、价格、标签、空/加载态保留 | 允许命中 `filter-summary` 类名；不允许恢复营销标题或说明卡 |
| P0 | `uniapp/src/pages/order/order.vue` | 已清理 `ORDER BOARD`、订单进度介绍文案和说明式头部 copy | `order-page__summary` 作为订单统计指标卡保留；订单状态筛选 tabs、订单卡片、订单状态、金额、支付/取消/评价/售后操作保留 | 允许命中 `order-page__summary` 类名；不允许恢复 `ORDER BOARD` 或说明式订单介绍卡 |
| P0 | `uniapp/src/pages/index/index.vue` | 已清理首屏 `home-page__intro-panel` 中非必要营销介绍、Hello/专业主持等介绍型 copy；本轮恢复/保留 `home-page__team-card`，不视为介绍性栏位残留 | Banner/装修组件、`home-brand` 团队信息卡、分类入口、档期查询入口、推荐服务/人员入口、tabbar 保留 | 首页应可见团队信息栏；团队卡/CTA 可跳转配置的 `cta_link` 或兜底进入档期查询；仍不得恢复旧 intro/hello/英文装饰头部 |
| P1 | `uniapp/src/packages/pages/couple_questionnaire/list.vue` | 已清理顶部 `questionnaire-list__hero` 说明卡 | 问卷状态筛选、问卷卡片、刷新/重试、空/错误/加载态保留 | 无介绍卡时仍能判断未填写/已填写并进入详情 |
| P1 | `uniapp/src/packages/pages/review/publish.vue` | 已清理顶部 `top-header` 中“分享您的体验/欢迎留下真实体验”等介绍文案 | 订单信息卡、星级评分、评价内容、图片上传、提交按钮、必填提示保留 | 首屏聚焦评价表单；提交按钮和校验提示仍可见 |
| P1 | `uniapp/src/packages/pages/aftersale/index.vue` | 已清理顶部营销/说明式售后介绍 copy | 人工入口、工单/投诉/回访统计、主要入口、状态提示、创建/查看操作保留 | 用户仍能快速进入对应售后功能 |
| P2 | 登录、注册、忘记密码、绑定手机号、服务人员工作台、钱包等存在 `hero/banner/intro/eyebrow/description` 的页面 | 前端二审判定剩余多为业务信息卡或功能性 banner，不作为本轮关键失败残留 | 登录/注册表单、验证码、保存/提交、新增/筛选/统计等业务动作保留 | 如后续产品按“所有页面零介绍文案”严格验收，需另开范围二次压缩 |

#### 8.6.2 保留/移除边界

以下内容不属于本轮要移除的“介绍性栏位”，回归时不能误删：

1. 导航标题与返回区域，例如 `BaseNavbar` 的页面标题。
2. 表单字段标签、必填提示、输入说明、错误提示和禁用原因。
3. 功能性筛选区、状态 tabs、筛选 chips、重筛/刷新/重试入口；`filter-summary` 保留为人员列表筛选摘要容器。
4. 订单、支付、问卷、售后等必要业务状态说明，例如待支付倒计时、支付结果、问卷提交状态、售后处理状态；`order-page__summary` 保留为订单统计指标卡。
5. 首页装修 Banner、业务活动 Banner、首页 `home-brand` 团队信息卡、订单信息卡、人员/服务/作品/套餐事实信息卡。
6. 首页团队信息卡可保留的内容包括：短标签“团队信息”或同义文案、团队名称、短副标题、1-3 项真实统计背书、预约/查看团队 CTA、`home-brand.enabled` 与 `cta_link` 等后台装修配置；整卡或 CTA 必须可跳转，不能只是静态装饰。
7. 空状态、加载态、错误态及其恢复操作。
8. 详情页中与服务、作品、订单、人员身份直接相关的信息卡；可压缩营销文案，但不能删除核心事实信息。

需要持续禁止恢复的内容：独立头部 guide/intro/hero 说明卡、旧 `home-page__intro-panel` / `home-page__hello` / `intro-copy` 语义、英文装饰 eyebrow（如 `BOOKING CHECK`、`ORDER BOARD`、`ABOUT`、`SHOWCASE`）、“欢迎/专业/为您介绍/精准匹配”等非必要营销说明、大段页面使用教程，以及无业务跳转的纯装饰占位模块。

#### 8.6.3 回归验证关键词与步骤

**前置条件**

1. 使用前端二审通过后的源码与重新构建的微信小程序产物。
2. 在 `d:/2025-10-31/wedding-management/uniapp` 执行并记录：
   - `npm run type-check:active`
   - `npm run build:mp-weixin:check`
3. 使用微信开发者工具打开 `d:/2025-10-31/wedding-management/uniapp/dist/build/mp-weixin`。

**源码/产物关键词检查**

- 失败残留关键词不得命中：`schedule-guide`、`BOOKING CHECK`、`home-page__intro-panel`、`home-page__hello`、`intro-copy`、`questionnaire-list__hero`、`ORDER BOARD`、`精准匹配`、`已按档期条件筛选服务团队`、`分享您的体验`、`欢迎留下真实体验`。
- 功能性容器允许命中但需人工确认内容属性：`filter-summary` 只用于筛选 chips/重筛；`order-page__summary` 只用于订单统计指标；首页 `hero/banner` 只用于装修图、业务活动图或可点击入口。
- 首页团队信息栏允许命中并应保留：`home-brand`、`showTeamInfoCard`、`home-page__team-card`、`home-page__team-title`、`home-page__team-stats`、`home-page__booking-btn`、`团队信息`。人工确认其内容是团队身份、统计背书和可点击 CTA，而不是旧欢迎/教程型头部。
- 若构建产物仍命中失败残留关键词，需确认是否重新构建并清理微信开发者工具缓存；若构建产物未命中 `团队信息` 或 `home-page__team-card`，需确认是否使用了最新源码重新构建，不能直接使用旧 `dist` 发布。

**页面回归步骤**

1. 打开 `pages/schedule_query/schedule_query`，确认导航下首屏直接出现日期/地区/分类等筛选表单，不出现 `BOOKING CHECK` 或同类大段介绍卡。
2. 依次填写日期、地区、服务分类、风格标签/关键词，点击“开始查询”，确认跳转人员列表成功。
3. 打开 `pages/staff_list/staff_list`，确认顶部保留筛选条件和“重筛”，但不出现营销式说明标题；切换筛选后列表刷新正常。
4. 打开 `pages/order/order`，确认顶部统计卡仅展示订单指标，不出现 `ORDER BOARD` 或介绍性说明；待支付订单仍可支付/查看详情。
5. 打开首页 `pages/index/index`，确认 Banner 下业务内容区可见团队信息卡：短标签“团队信息”、团队名称、副标题、统计项和预约/查看团队 CTA 清晰可读；`home-brand.enabled` 非 0 时不得被隐藏。
6. 点击首页团队信息卡和 CTA，确认跳转到后台配置的 `cta_link`；未配置时应兜底进入 `pages/schedule_query/schedule_query`，不能无反应或误跳 tabbar。
7. 继续检查首页分类入口、档期查询入口、推荐服务/人员入口和 tabbar，确认团队信息卡未挤掉或遮挡这些核心入口。
8. 打开问卷列表、评价发布、售后首页等 P1 页面，确认无独立头部介绍栏，同时核心入口、表单、状态和提交按钮仍清晰可见。
9. 对剩余 hero/banner/团队卡做人工判定：若承载业务图片、团队身份、统计指标、订单信息或可点击入口，则按保留边界处理；若仅承载欢迎语、营销说明或页面教程，则退回前端继续清理。
10. 在 375 x 667、390 x 844、414 x 896、Android 全面屏视口复查：首屏无旧介绍栏占位；团队卡不遮挡 Banner、分类/推荐入口和 tabbar；CTA 不与团队名称或统计项重叠；长团队名/长副标题不横向溢出。

**通过标准**

- 前端二审通过范围内的关键失败残留为 0，且首页旧 `home-page__intro-panel` / `home-page__hello` 不得恢复。
- 首页团队信息栏必须保留并可用：源码与重新构建产物可见 `home-page__team-card` / “团队信息”，卡片内容为团队身份、统计背书和可点击 CTA，`home-brand.enabled` 默认启用。
- 保留区仍可操作：团队卡可跳转，筛选可选、列表可滚动、详情可进入、表单可提交、订单可操作、空/加载/错误态可恢复。
- 若发现“无介绍栏后不知道下一步做什么”，优先补强字段标签、按钮文案、团队卡 CTA 或空/错误态操作，不恢复大段头部介绍卡。
- 发布前必须重新执行构建并用最新 `dist/build/mp-weixin` 验证；若现有产物搜不到“团队信息”，按旧产物风险处理，不得直接发布。

#### 8.6.4 产品最终验收结论

| 项目 | 当前记录 |
|---|---|
| 前端二审结论 | 通过；关键失败残留已清零，剩余 hero/banner 多为业务信息卡 |
| 首页团队信息栏前端结论 | 已在 `uniapp/src/pages/index/index.vue` 恢复/保留 `home-brand` 团队信息栏：Banner 下新增 `home-page__team-card`，展示“团队信息”、团队名称、副标题、统计项和预约 CTA；整卡/CTA 复用 `cta_link` 跳转，支持 `content.enabled` 隐藏。`npm run type-check:active` 与 `npm run build:mp-weixin:check` 通过，主包约 1.32 MB，低于 1.40 MB 阈值 |
| 首页团队信息栏 UX 结论 | `home-page__team-card` 承载团队身份、服务入口、预约 CTA 与统计背书，属于业务必要信息卡；应保留团队名称、短副标题、1-3 项真实统计和可点击 CTA；仍不得恢复 `home-page__intro-panel`、`home-page__hello`、英文装饰 eyebrow、大段欢迎/介绍/教程文案 |
| QA 文档同步结论 | 已按前端二审与本轮团队信息栏保留范围更新最终口径，明确允许保留的业务信息卡/团队展示入口与禁止恢复的纯介绍性栏位 |
| 产品最终验收 | 已完成只读验收。上一轮介绍性栏位移除范围无 P0/P1 残留；本轮源码层面确认首页团队信息栏已恢复并默认可见，`showTeamInfoCard` 控制的 `home-page__team-card` 含“团队信息”、团队名称、副标题、统计数据和“立即预定”按钮；旧 `home-page__intro-panel`、hello/intro 命名和 Hello 问候未恢复 |
| 保留内容确认 | 保留内容均为业务必要卡片、团队展示入口、状态、表单、筛选、空状态、错误态和加载态，不作为介绍性栏位缺陷处理 |
| 核心路径抽查 | 首页团队卡/按钮可进入档期查询兜底；预约图片区、服务分类推荐卡、首页/动态/我的 tabbar、档期查询和人员列表路由未见阻断 |
| 缺陷等级结论 | P0 = 无；P1 = 无明确阻断；P2 = 发布前必须重新构建发布包并用微信开发者工具/真机预览确认团队信息栏在最新产物中可见，且后台 `home-brand.enabled` 不被运营手动置 0 |
| 发布口径 | 源码层面风险低，可进入发布前常规回归；常规回归、最新构建产物验证和 P2 首屏视觉/路径跳转复查通过后可发布 |

**常见问题**

1. 如果移除介绍卡后页面显得空，应把核心筛选/列表上移，而不是新增新的 hero/guide 文案。
2. 如果用户不理解筛选项，应在字段级补充短提示，不在页面头部恢复大段说明。
3. 如果状态说明被误删，应恢复为贴近订单/问卷/售后卡片的业务状态，不做营销式顶部卡片。
4. 如果只命中 `filter-summary`、`order-page__summary`、业务 `hero/banner`，不能直接判定失败，必须结合内容是否为功能性信息卡人工确认。
5. 如果首页命中 `home-page__team-card`、`home-brand` 或“团队信息”，这是本轮要求保留的业务团队信息栏；只要内容是团队身份、统计背书和可点击 CTA，不应按介绍性栏位删除。
6. 如果最新构建产物未出现“团队信息”或团队卡，应先确认是否重新执行 `npm run build:mp-weixin:check`、是否清理微信开发者工具缓存、以及后台 `home-brand.enabled` 是否被置为 `0`。

### 8.7 遗留风险与发布建议

| 风险等级 | 风险项 | 影响范围 | 当前状态 | 发布建议 | 负责人 |
|---|---|---|---|---|---|
| P0 | 介绍性栏位 P0 残留 | 档期查询、人员列表、问卷、售后、客服、充值及核心路径 | 产品最终只读验收：无 P0 残留 | 不构成发布阻断；发布前常规回归继续确认旧头部介绍栏未恢复 | 产品/QA/前端 |
| P1 | 介绍性栏位 P1 残留 | 首页、订单/支付、通知/评价、个人中心、人员详情/预约、钱包、服务人员中心、管理员看板等 | 产品最终只读验收：无 P1 残留，核心路径无发布阻断 | 不构成发布阻断；发布前按第 8.6.3 复查功能性信息卡与介绍栏边界 | 产品/QA/前端 |
| P2 | 实机视觉首屏与路径跳转复查 | 全部核心 UI/UX 页面，尤其首页团队信息卡、首屏、底部安全区、文字溢出、按钮重叠和跨页面跳转 | 建议继续执行 | 发布前建议至少完成微信开发者工具 375/390/414/Android 视口走查；如有真机资源，补 iPhone/Android 真机抽查 | QA/前端 |
| P2 | 首页团队信息栏最新产物验证 | 首页 `home-brand` 团队信息卡、`dist/build/mp-weixin` 发布产物、后台装修配置 | 产品只读验收提示：当前旧 `dist` 可能搜不到“团队信息”，源码层面已恢复但发布前必须重新构建 | 发布前重新执行 `npm run build:mp-weixin:check`，确认最新产物出现 `团队信息` / `home-page__team-card`，并确认后台 `home-brand.enabled` 未被置 0 | 前端/QA/产品 |
| P2 | uni-app 新版本提示 | 构建工具链维护风险 | 已知提示/非阻塞 | 不阻塞本轮 UI/UX 发布；后续单独评估 uni-app 版本升级与回归成本 | 前端/项目负责人 |
| P1 | 非 `uniapp` 改动需隔离核对 | 仓库内同时存在非小程序相关改动，可能影响整体发布判断 | 待核对 | 本轮小程序 UI/UX 发布只采信 `uniapp` 相关验收；其他目录改动需由对应负责人单独确认，避免混入发布风险 | Leader/相关负责人 |
| P2 | 主包体积余量有限 | 后续继续叠加样式或资源可能接近 1.40 MB | 当前通过，余量约 0.07 MB | 后续新增图片/组件库依赖前先评估主包体积，优先放分包或压缩资源 | 前端 |

#### 8.7.1 最终发布建议

当前建议：**产品最终只读验收已完成，介绍性栏位移除结论为 P0 = 无、P1 = 无；首页团队信息栏源码层面已恢复/保留且属于业务信息入口，风险等级低。可进入发布前常规回归；常规回归、最新构建产物中的团队信息栏验证、以及 P2 实机视觉首屏与路径跳转复查通过后可发布。**

判断依据：

1. 当前源码已完成产品最终只读验收；上一轮 `mp-weixin` 构建产物（目标产物时间：2026-05-24 12:00:14）用于介绍性栏位移除结论，本轮发布前需基于最新源码重新构建。
2. `schedule_query`、`staff_list`、`couple_questionnaire/list`、`aftersale/index`、`customer_service`、`recharge` 等重点页无类似旧 `schedule_query` 头部介绍性栏位的 P0/P1 残留。
3. 首页已恢复/保留 `showTeamInfoCard` 控制的 `home-page__team-card`，内容包括“团队信息”、团队名称、副标题、统计数据和预约 CTA；它属于业务必要卡片和团队展示入口，不作为介绍性栏位缺陷处理。
4. 首页团队卡/按钮可进入档期查询兜底；预约图片区、服务分类推荐卡、首页/动态/我的 tabbar、档期查询和人员列表路由未见阻断。
5. 旧 `home-page__intro-panel`、`home-page__hello`、hello/intro 命名和 Hello 问候未恢复；仍禁止独立欢迎语、营销口号、英文装饰 eyebrow 和大段页面说明。
6. 保留内容为业务必要卡片、团队展示入口、状态、表单、筛选、空状态、错误态和加载态，不作为介绍性栏位缺陷处理。
7. 遗留风险集中在 P2 最新构建产物团队信息栏验证、实机视觉首屏与路径跳转复查、uni-app 新版本提示、非 `uniapp` 改动需隔离核对。

发布策略建议：

- 若第 8.6、8.4 和 8.5 的常规回归、最新构建产物、视口/页面抽查均通过且无新增 P0/P1 问题：可以发布。
- 若首页最新产物缺失 `home-page__team-card` / “团队信息”，或后台 `home-brand.enabled` 被置 0 导致用户看不到团队信息栏：暂缓发布，修复配置或重新构建后再执行第 8.6.3。
- 若 `schedule_query`、首页、人员列表、订单页恢复旧头部介绍卡或失败残留关键词（如 `home-page__intro-panel`、`home-page__hello`、`BOOKING CHECK`、`ORDER BOARD`）：暂缓发布，修复后重新执行本指南第 5、6、8 章。
- 若首页团队卡仅为静态装饰、无法跳转 `cta_link` 或兜底档期查询：暂缓发布，修复交互后重新执行首页回归步骤。
- 若发现支付、预约、订单、售后、问卷提交按钮不可见或页面打不开：暂缓发布，修复后重新执行本指南第 5、6、8 章。
- 若仅存在 P2 级实机视觉或跳转细节问题：可按发布策略灰度发布，但需记录问题编号、影响页面和后续修复计划。

---

## 9. 常见问题

### Q1：本轮 UI/UX 优化是否可以只改主题色？

不可以。验收重点是系统级体验优化，包括信息层级、留白节奏、图片比例、状态表达、组件统一、触控体验、安全区和空/加载/错误状态。

### Q2：主包体积超限怎么办？

1. 查看 `npm run build:mp-weixin:check` 输出中的“主包目录体积 Top”和“主包大文件 Top”。
2. 优先排查：
   - `common/vendor.js`
   - 主包页面脚本
   - 全局样式
   - 主包静态资源
3. 检查是否把分包专属页面或组件引入主包。
4. 公共能力应沉淀到 `src/api`、`src/utils`、`src/components/base`，但避免把低频分包能力变成全局依赖。

### Q3：页面在微信开发者工具打不开怎么办？

1. 检查 `uniapp/src/pages.json` 是否注册。
2. 检查实际 `.vue` 文件是否存在。
3. 检查主包路径和分包路径是否混用。
4. tabbar 页面只能使用 `switchTab`，非 tabbar 页面使用 `navigateTo`。
5. 本地联调先确认打开的是 `dist/dev/mp-weixin`；如果打开 `dist/build/mp-weixin`，接口会按生产配置请求。
6. 查看控制台是否有 `page not found`、`module not found` 或路径大小写错误。

### Q4：底部按钮或 tabbar 遮挡内容怎么办？

1. 检查是否复用 `PageShell` 或统一安全区策略。
2. 检查页面内容底部是否预留 `env(safe-area-inset-bottom)` 对应空间。
3. 在 375/390/414 视口分别验证。
4. 不要只通过增大 z-index 解决，应同时处理内容滚动底部 padding。

### Q5：订单/售后状态颜色不统一怎么办？

1. 优先复用 `StatusBadge.vue` 或统一状态样式。
2. 状态表达不能只依赖颜色，还需要明确文案。
3. 同一状态在订单列表、订单详情、售后列表、通知中应保持一致语义。

### Q6：服务人员端是否需要完全不同的视觉？

不需要。服务人员端可以提高数据密度，但应继续复用黑白香槟金主题、`PageShell`、`BaseCard`、`BaseButton` 等设计骨架，保持与消费者端一致的高级、简洁、易用体验。
