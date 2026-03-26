# UniApp 小程序页面蓝图

## 1．文档定位

本文件把 `wedding.pen` 中已有设计屏与 `uniapp/src/pages.json` 当前全部路由统一映射到一组固定页面模板，保证未来新增页面时不再从零设计。

蓝图只解决三件事：

1．每类页面应该长什么样。

2．每类页面应该由哪些模块组成。

3．每条现有路由应该落在哪个模板与场景下。

## 2．页面模板总览

| 模板编号 | 模板名称 | 适用场景 | 设计稿参考 |
| --- | --- | --- | --- |
| `P1` | 首页与品牌发现 | `consumer` | `01-首页` `IOvaW` |
| `P2` | 列表与筛选 | `consumer` | `02-动态列表` `2GbrE`、`05-档期查询` `YQwzx`、`06-人员列表` `4tPwK`、`10-人员列表-列表态` `nTiDS` |
| `P3` | 详情页 | `consumer` | `04-动态详情` `rdoB4`、`07-人员详情` `17gO0`、`11-订单详情` `jNkec` |
| `P4` | 预约与变更流程 | `consumer` | `12-预约-基础套餐` 到 `17-预约-总价弹窗演示` |
| `P5` | 订单、钱包与售后 | `consumer` | `08-我的订单` `Tn22f`、`11-订单详情` `jNkec` |
| `P6` | 个人中心与资料表单 | `consumer` | `03-个人中心` `IKQnG`、`18-资料编辑` `0VBn3` |
| `P7` | 服务人员工作台 | `staff` | `19-服务人员中心` `eN0Bi`，辅以现有 `staff_center` 页面 |
| `P8` | 管理员经营看板 | `admin` | 共享 token，无完整单独画板；以现有 `admin_dashboard` 页面为工具化变体基线 |
| `P9` | 系统与支持页 | `consumer` `staff` `admin` | 复用通用外壳，无单独专属画板 |

## 3．模板细则

### 3.1 `P1` 首页与品牌发现

适用范围：

- 小程序首页。
- 品牌化入口页。
- 需要情绪化首屏与主 CTA 的营销首页。

结构要求：

1．顶部使用图片型 hero 或装修轮播。

2．hero 下方必须有单一主 CTA，不允许并列两个同级主按钮。

3．正文按“推荐团队”“服务流程”“活动区”“内容区”分段。

4．底部必须兼容浮动胶囊 tabbar。

视觉要求：

- 首屏允许大图、底部圆角、轻阴影。
- 文案居中或半居中，按钮满宽。
- 主色使用珊瑚红，不得变成蓝紫主按钮。

### 3.2 `P2` 列表与筛选

适用范围：

- 动态列表。
- 资讯列表。
- 人员列表。
- 档期查询。
- 收藏与搜索结果。

结构要求：

1．顶部必须有自定义标题区或筛选吸顶区。

2．筛选项统一使用胶囊组件，支持“选中”“可关闭”“下拉排序”。

3．列表卡片必须复用统一卡片节奏，不允许每种列表卡片边距体系不同。

4．页面底部预留加载更多或底部导航空间。

视觉要求：

- `consumer` 场景使用暖白背景与浅玻璃筛选条。
- 卡片优先使用 `surface` 或 `glass` 变体。
- 排序弹层采用底部抽屉而非全屏切页。

### 3.3 `P3` 详情页

适用范围：

- 动态详情。
- 人员详情。
- 资讯详情。
- 工单详情。
- 订单详情。
- 作品详情。

结构要求：

1．顶部为标题区或媒体区。

2．中部按“主体信息”“补充说明”“关联列表”分段。

3．详情页如包含主操作，底部必须使用统一 `ActionArea`。

4．长内容必须保证图文节奏，不允许纯文本直出。

视觉要求：

- 内容卡片可延续用户端玻璃卡风格。
- 底部操作区统一白底或玻璃白底，边框和圆角固定。

### 3.4 `P4` 预约与变更流程

适用范围：

- 套餐预约。
- 候场互动、接亲、管家、督导等多步选择。
- 改期、暂停、加项、附加服务申请。
- 总价弹窗与流程确认面板。

结构要求：

1．顶部保留步骤标签或进度提示。

2．主内容区允许大图和情绪化遮罩，但选择项本身必须高可读。

3．底部必须固定操作区，统一展示上一步、下一步、确认、金额摘要。

4．弹窗与总价摘要必须复用同一模态样式。

视觉要求：

- 预约流程是用户端中最强调沉浸感的模板。
- 遮罩可以较重，但正文卡片仍要保证读写效率。

### 3.5 `P5` 订单、钱包与售后

适用范围：

- 我的订单。
- 订单详情。
- 支付结果。
- 钱包、充值、充值记录。
- 售后首页、投诉、补拍、工单、回访问卷、候补。

结构要求：

1．默认以卡片列表或信息面板为主。

2．状态标签、金额、时间轴、操作按钮必须统一语义。

3．待支付、待处理、失败类状态要在首屏可见。

4．金额模块要使用主文字等级，不允许和说明文字同权重。

视觉要求：

- 交易与售后以功能效率为主，减少大图。
- 主色只用于状态、操作与重点金额，不宜整页铺满。

### 3.6 `P6` 个人中心与资料表单

适用范围：

- 个人中心。
- 资料编辑。
- 登录、注册、忘记密码、绑定手机、修改密码。
- 用户设置。
- 发布评价、发布动态等轻表单页。

结构要求：

1．个人中心页保留“身份信息卡 + 倒计时卡 + 快捷入口卡”组合。

2．表单页统一使用单列表单，不允许字段排列拥挤。

3．按钮顺序固定：主要操作在下方，辅助操作使用次级按钮或文本入口。

4．表单页必须有明确的错误态与提交态。

视觉要求：

- 输入区与卡片区层级清晰。
- 表单背景优先使用暖白与轻灰粉底，不做深色沉浸式。

### 3.7 `P7` 服务人员工作台

适用范围：

- 服务人员中心。
- 订单管理、档期管理、作品管理、套餐管理、附加服务管理、动态管理。
- 服务人员资料与编辑页。

结构要求：

1．首页必须有身份概览、待办看板、最近订单、快捷操作四块。

2．列表页优先用任务密度组织，而非营销化宫格。

3．编辑页沿用 `P6` 表单规范，但场景切为 `staff`。

4．状态标签、审核态、待确认态要统一使用 `StatusBadge`。

视觉要求：

- 头部可使用深色渐变块承接身份信息。
- 正文区域回到高可读白底面板。

### 3.8 `P8` 管理员经营看板

适用范围：

- 管理员驾驶舱。
- 未来的经营总览、趋势图、团队统计类页面。

结构要求：

1．首屏为概览卡与时间范围切换。

2．中段是指标卡、团队卡、状态分布、趋势图、提醒条。

3．底部可以继续叠加诊断模块，但不允许出现用户端大图 hero。

4．图表卡片、进度条、趋势柱图必须共享统一 panel 样式。

视觉要求：

- 保留暖白品牌底色与珊瑚强调，但以白底面板和信息密度优先。
- 不使用用户端浮动 tabbar、玻璃悬浮 CTA、超大情绪文案。

### 3.9 `P9` 系统与支持页

适用范围：

- 空页面。
- 404。
- WebView 容器页。
- 协议页。
- 联系顾问页。

结构要求：

1．继承场景外壳与导航规则。

2．内容以说明、帮助、跳转、重试为主。

3．系统状态页保持图标、标题、说明、操作四段结构。

## 4．路由到模板映射

### 4.1 主包路由

| 路由 | 模板 | 场景 | 设计参考 |
| --- | --- | --- | --- |
| `pages/index/index` | `P1` | `consumer` | `01-首页` |
| `pages/news/news` | `P2` | `consumer` | `02-动态列表` 的内容列表变体 |
| `pages/user/user` | `P6` | `consumer` | `03-个人中心` |
| `pages/staff_list/staff_list` | `P2` | `consumer` | `06-人员列表`、`10-人员列表-列表态` |
| `pages/login/login` | `P6` | `consumer` | `18-资料编辑` 的表单变体 |
| `pages/register/register` | `P6` | `consumer` | `18-资料编辑` 的表单变体 |
| `pages/forget_pwd/forget_pwd` | `P6` | `consumer` | `18-资料编辑` 的表单变体 |
| `pages/user_set/user_set` | `P6` | `consumer` | `03-个人中心` 的设置入口延展 |
| `pages/as_us/as_us` | `P9` | `consumer` | 通用说明页外壳 |
| `pages/change_password/change_password` | `P6` | `consumer` | `18-资料编辑` 的表单变体 |
| `pages/user_data/user_data` | `P6` | `consumer` | `18-资料编辑` |
| `pages/search/search` | `P2` | `consumer` | `06-人员列表` 的搜索结果变体 |
| `pages/webview/webview` | `P9` | `consumer` | 通用外壳，无单独画板 |
| `pages/bind_mobile/bind_mobile` | `P6` | `consumer` | `18-资料编辑` 的表单变体 |
| `pages/empty/empty` | `P9` | `consumer` | 系统状态页 |
| `pages/payment_result/payment_result` | `P5` | `consumer` | 交易结果页，参考订单详情状态区 |
| `pages/order/order` | `P5` | `consumer` | `08-我的订单` |
| `pages/order_detail/order_detail` | `P5` | `consumer` | `11-订单详情` |
| `pages/dynamic/dynamic` | `P2` | `consumer` | `02-动态列表` |
| `pages/dynamic_detail/dynamic_detail` | `P3` | `consumer` | `04-动态详情` |

### 4.2 子包路由：系统、内容与支持

| 路由 | 模板 | 场景 | 设计参考 |
| --- | --- | --- | --- |
| `packages/pages/404/404` | `P9` | `consumer` | 系统状态页 |
| `packages/pages/customer_service/customer_service` | `P9` | `consumer` | 通用支持页 |
| `packages/pages/news_detail/news_detail` | `P3` | `consumer` | `04-动态详情` 的资讯详情变体 |
| `packages/pages/collection/collection` | `P2` | `consumer` | `10-人员列表-列表态` |
| `packages/pages/agreement/agreement` | `P9` | `consumer` | 协议详情页 |
| `packages/pages/dynamic_publish/dynamic_publish` | `P6` | `consumer` | 轻表单 + 内容发布页 |
| `packages/pages/review/list` | `P5` | `consumer` | 订单后服务列表 |
| `packages/pages/review/publish` | `P6` | `consumer` | 评价表单页 |
| `packages/pages/review/detail` | `P3` | `consumer` | 详情页通用模板 |
| `packages/pages/notification/index` | `P9` | `consumer` | `09-通知中心` |

### 4.3 子包路由：订单、售后与预约

| 路由 | 模板 | 场景 | 设计参考 |
| --- | --- | --- | --- |
| `packages/pages/order_change/list` | `P5` | `consumer` | `08-我的订单` 的申请列表变体 |
| `packages/pages/order_change/change_detail` | `P5` | `consumer` | `11-订单详情` 的申请详情变体 |
| `packages/pages/order_change/apply_date` | `P4` | `consumer` | `12-17` 预约流程变体 |
| `packages/pages/order_change/apply_pause` | `P4` | `consumer` | `12-17` 预约流程变体 |
| `packages/pages/order_change/pause_detail` | `P5` | `consumer` | 订单详情信息面板 |
| `packages/pages/order_change/apply_add_item` | `P4` | `consumer` | `12-17` 预约流程变体 |
| `packages/pages/order_change/apply_addon` | `P4` | `consumer` | `12-17` 预约流程变体 |
| `packages/pages/aftersale/index` | `P5` | `consumer` | 订单与售后入口面板 |
| `packages/pages/aftersale/ticket` | `P5` | `consumer` | 工单列表 |
| `packages/pages/aftersale/ticket_detail` | `P3` | `consumer` | 详情页模板 |
| `packages/pages/aftersale/complaint` | `P5` | `consumer` | 售后列表 / 表单切换页 |
| `packages/pages/aftersale/reshoot` | `P5` | `consumer` | 售后列表 |
| `packages/pages/aftersale/apply_reshoot` | `P4` | `consumer` | 预约流程变体 |
| `packages/pages/aftersale/callback` | `P6` | `consumer` | 问卷表单页 |
| `packages/pages/user_wallet/user_wallet` | `P5` | `consumer` | 钱包总览面板 |
| `packages/pages/recharge/recharge` | `P5` | `consumer` | 交易表单页 |
| `packages/pages/recharge_record/recharge_record` | `P5` | `consumer` | 交易记录列表 |
| `packages/pages/order_confirm/order_confirm` | `P4` | `consumer` | `12-预约-基础套餐` 到 `17-预约-总价弹窗演示` |
| `packages/pages/waitlist/waitlist` | `P4` | `consumer` | 预约与候补流程变体 |

### 4.4 子包路由：人员与作品

| 路由 | 模板 | 场景 | 设计参考 |
| --- | --- | --- | --- |
| `packages/pages/staff_detail/staff_detail` | `P3` | `consumer` | `07-人员详情` |
| `packages/pages/staff_work_detail/staff_work_detail` | `P3` | `consumer` | `07-人员详情` 与 `04-动态详情` 混合变体 |
| `packages/pages/staff_favorite/staff_favorite` | `P2` | `consumer` | `10-人员列表-列表态` |

### 4.5 子包路由：服务人员端

| 路由 | 模板 | 场景 | 设计参考 |
| --- | --- | --- | --- |
| `packages/pages/staff_center/staff_center` | `P7` | `staff` | `19-服务人员中心` |
| `packages/pages/staff_order_list/staff_order_list` | `P7` | `staff` | 工作台列表页变体 |
| `packages/pages/staff_order_detail/staff_order_detail` | `P7` | `staff` | 工作台详情页变体 |
| `packages/pages/staff_profile/staff_profile` | `P7` | `staff` | 资料页，继承工作台视觉 |
| `packages/pages/staff_work_list/staff_work_list` | `P7` | `staff` | 资源列表页 |
| `packages/pages/staff_work_edit/staff_work_edit` | `P7` | `staff` | 编辑表单页 |
| `packages/pages/staff_package_list/staff_package_list` | `P7` | `staff` | 资源列表页 |
| `packages/pages/staff_package_edit/staff_package_edit` | `P7` | `staff` | 编辑表单页 |
| `packages/pages/staff_addon_list/staff_addon_list` | `P7` | `staff` | 资源列表页 |
| `packages/pages/staff_addon_edit/staff_addon_edit` | `P7` | `staff` | 编辑表单页 |
| `packages/pages/staff_schedule/staff_schedule` | `P7` | `staff` | 档期工作台页 |
| `packages/pages/staff_dynamic_list/staff_dynamic_list` | `P7` | `staff` | 内容运营列表页 |
| `packages/pages/staff_dynamic_edit/staff_dynamic_edit` | `P7` | `staff` | 内容编辑表单页 |

### 4.6 子包路由：管理端

| 路由 | 模板 | 场景 | 设计参考 |
| --- | --- | --- | --- |
| `packages/pages/admin_dashboard/admin_dashboard` | `P8` | `admin` | 无单独画板，采用共享 token 的工具化看板变体 |

## 5．模板落地规则

### 5.1 页面外壳规则

- `P1` 到 `P6` 默认使用 `consumer` 场景外壳。
- `P7` 使用 `staff` 场景外壳。
- `P8` 使用 `admin` 场景外壳。
- `P9` 继承进入该页的场景规则，若无来源，则默认 `consumer`。

### 5.2 导航规则

- `P1`、`P2`、`P6` 可使用自定义导航。
- `P3` 在有头图时优先透明叠图导航，无头图时使用标准白底导航。
- `P8` 禁止透明导航和图片叠层导航。

### 5.3 底部区域规则

- 用户端 tab 页面必须保留浮动胶囊 tabbar。
- 预约流程与支付流程必须使用固定底部操作区。
- 服务人员端和管理端禁止复用用户端 tabbar。

### 5.4 状态页规则

- 空态与错误态优先复用 `P9`。
- 如果业务页只是在列表为空，不需要切模板，只替换内容块。

## 6．新增页面归类规则

新增页面必须先回答四个问题，再决定模板归属：

1．它是浏览页、流程页、交易页、资料页还是工作台页。

2．它属于 `consumer`、`staff` 还是 `admin`。

3．它是否需要固定底部操作区。

4．它是否需要列表筛选、状态卡或大图首屏。

归类结果一旦确定，就只能在同模板上做内容差异，不允许新开第四套视觉风格。
