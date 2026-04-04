# UniApp 小程序设计系统规范

## 1．文档定位

本规范用于统一婚礼预约服务小程序的用户端、服务人员端、管理员端视觉与交互语言。它是 `docs` 目录下的唯一视觉源，后续页面、组件、装修模块、营销位、表单页、列表页和看板页都必须以本规范为准。

设计依据来自三个来源：

1．`wedding.pen` 中的“风格板”“基础组件库”及 `01` 到 `19` 号设计屏。

2．当前 `uniapp` 代码中的主题注入链路、基础组件与已落地页面。

3．官方技术依据：

- uni-app 官方文档关于 `uni.scss` 全局样式变量与主题能力。
- Vue 3 官方文档关于 SFC 样式中的 CSS 变量与 `v-bind()`。
- Pinia 官方文档关于 `defineStore()` 作为全局主题状态容器的用法。

## 2．设计目标

### 2.1 统一视觉

所有小程序页面必须共享同一套品牌底色、强调色、圆角、阴影、分隔线、按钮、表单和状态样式，禁止每个页面单独定义自己的视觉语言。

### 2.2 快速复用

页面实现必须优先复用页面外壳、卡片、按钮、输入框、筛选标签、底部操作区等基础能力，而不是复制粘贴私有样式。

### 2.3 多场景共存

同一套设计系统同时覆盖三类场景：

- `consumer`：用户端。强调氛围感、图像、暖白质感、轻玻璃卡片、清晰 CTA。
- `staff`：服务人员端。强调任务处理、状态标签、最近订单、快捷入口。
- `admin`：管理员端。共享同一套 token，但改为更高信息密度、更克制的工具化编排。

### 2.4 可实施

本规范的命名、token、组件契约和页面模板必须可以直接映射到 UniApp + Vue 3 + Pinia 的工程体系中，不写“只可意会”的设计描述。

## 3．风格基调

### 3.1 品牌关键词

- 暖白底。
- 珊瑚红强调。
- 香槟金辅助高光。
- 轻毛玻璃卡片。
- 精致细边框。
- 大圆角移动端卡片。
- 图片驱动的情绪首屏。
- 编辑感排版，而不是传统电商货架感。

### 3.2 不同场景的视觉重心

| 场景 | 视觉重心 | 布局密度 | 允许元素 |
| --- | --- | --- | --- |
| `consumer` | 情绪化首屏、预约行动、内容浏览 | 低到中 | 图像大图、玻璃卡片、渐变、浮层 tabbar |
| `staff` | 待办、档期、订单、快捷处理 | 中 | 任务卡片、状态胶囊、分组面板、轻渐变头图 |
| `admin` | 指标、趋势、团队、风险提醒 | 中到高 | 白底面板、统计卡、图表、进度条、提醒条 |

### 3.3 禁止项

- 禁止再把“暗色影院感”作为默认主视觉。
- 禁止新增与婚礼主色无关的紫色主按钮。
- 禁止在用户端主流程使用厚重深色背景作为页面主底。
- 禁止出现没有边界的灰白块堆叠。
- 禁止使用 emoji 充当功能图标。

## 4．Token 命名与取值

### 4.1 命名规则

正式设计 token 统一使用 `--wm-` 前缀，分为九类：

- `--wm-color-*`：颜色。
- `--wm-text-*`：文本语义。
- `--wm-font-*`：字体族与字号。
- `--wm-radius-*`：圆角。
- `--wm-space-*`：间距。
- `--wm-shadow-*`：阴影。
- `--wm-motion-*`：动效时长。
- `--wm-z-*`：层级。
- `--wm-safe-*`：安全区与底部预留。

禁止新增第二套正式前缀。历史 `cinema-*` 与 `dynamic-*` 只能视为兼容遗留，不得继续扩展。

### 4.2 核心颜色 Token

| Token | 值 | 用途 |
| --- | --- | --- |
| `--wm-color-bg-page` | `#FCFBF9` | 页面主背景 |
| `--wm-color-bg-card` | `#FFFFFFE8` | 浮层卡片、主卡片背景 |
| `--wm-color-bg-soft` | `#FFF7F4` | 次级背景、输入底、弱强调面 |
| `--wm-color-bg-mask` | `rgba(30, 36, 50, 0.46)` | 弹窗与底部抽屉遮罩 |
| `--wm-color-primary` | `#E85A4F` | 主操作色、选中态、品牌 CTA |
| `--wm-color-primary-strong` | `#D84D43` | 主操作压下态、强调描边 |
| `--wm-color-primary-soft` | `#FFF1EE` | 轻提示、已选筛选项背景 |
| `--wm-color-secondary` | `#C99B73` | 金色辅助强调、价格亮点 |
| `--wm-color-secondary-soft` | `#F8EFE7` | 金色辅助背景 |
| `--wm-color-border` | `#EFE6E1` | 默认细边框、分隔线 |
| `--wm-color-border-strong` | `#F4C7BF` | 主色相关描边、计时卡边框 |
| `--wm-color-text-primary` | `#1E2432` | 主标题、正文高强调 |
| `--wm-color-text-secondary` | `#7F7B78` | 次级说明、时间、元信息 |
| `--wm-color-text-tertiary` | `#B4ACA8` | 占位、空态弱提示 |
| `--wm-color-success` | `#2F7D58` | 成功、完成、通过 |
| `--wm-color-warning` | `#C98524` | 待处理、提醒、即将超时 |
| `--wm-color-danger` | `#B44A3A` | 失败、驳回、退款、风险 |
| `--wm-color-info` | `#607086` | 中性信息、辅助状态 |

### 4.3 字体 Token

| Token | 默认值 | 用途 |
| --- | --- | --- |
| `--wm-font-family-display` | `SF Pro Display, PingFang SC, Microsoft YaHei, sans-serif` | 首页大标题、章节标题、看板主值 |
| `--wm-font-family-body` | `PingFang SC, Hiragino Sans GB, Microsoft YaHei, sans-serif` | 正文、表单、说明 |
| `--wm-font-size-hero` | `44rpx` | 首屏文案、大促主标题 |
| `--wm-font-size-h1` | `36rpx` | 页面标题 |
| `--wm-font-size-h2` | `32rpx` | 区块标题 |
| `--wm-font-size-h3` | `30rpx` | 卡片主标题、按钮 |
| `--wm-font-size-body` | `28rpx` | 正文 |
| `--wm-font-size-meta` | `24rpx` | 标签、次级说明 |
| `--wm-font-size-caption` | `22rpx` | 极短说明、状态 |
| `--wm-font-size-mini` | `20rpx` | 图表说明、角标 |

字号使用规则：

- 页面首标题使用 `h1` 或 `hero`，禁止混用多种大字号。
- 卡片标题默认 `h3`，除非是计时器、看板主数值。
- 标签与状态胶囊默认 `caption` 或 `mini`。

### 4.4 圆角 Token

| Token | 值 | 用途 |
| --- | --- | --- |
| `--wm-radius-input` | `18rpx` | 输入框、二级按钮 |
| `--wm-radius-chip` | `20rpx` | 筛选项、状态块 |
| `--wm-radius-card` | `24rpx` | 常规卡片 |
| `--wm-radius-card-lg` | `28rpx` | 首页卡片、计时卡、模块卡片 |
| `--wm-radius-shell` | `36rpx` | 页面外壳、整屏画板 |
| `--wm-radius-pill` | `999rpx` | 胶囊按钮、tag、tabbar 激活项 |

### 4.5 间距 Token

| Token | 值 | 用途 |
| --- | --- | --- |
| `--wm-space-2` | `8rpx` | 图标与文字最小间隙 |
| `--wm-space-3` | `12rpx` | 小标签组、按钮内补白 |
| `--wm-space-4` | `16rpx` | 卡片内最小 gap |
| `--wm-space-5` | `20rpx` | 页面左右内边距基线 |
| `--wm-space-6` | `24rpx` | 卡片默认内边距 |
| `--wm-space-7` | `28rpx` | 大卡片内边距 |
| `--wm-space-8` | `32rpx` | 章节间距 |
| `--wm-space-10` | `40rpx` | 首屏下方大留白 |

### 4.6 阴影、动效、层级

| Token | 值 | 用途 |
| --- | --- | --- |
| `--wm-shadow-soft` | `0 14rpx 32rpx rgba(214, 185, 167, 0.16)` | 普通卡片 |
| `--wm-shadow-card` | `0 18rpx 36rpx rgba(214, 185, 167, 0.20)` | 悬浮卡片、编辑感动态卡 |
| `--wm-shadow-hero` | `0 24rpx 56rpx rgba(177, 108, 95, 0.18)` | 首页首屏、主 CTA |
| `--wm-motion-fast` | `150ms` | 标签切换、图标状态 |
| `--wm-motion-base` | `220ms` | 按钮、卡片、筛选项 |
| `--wm-motion-slow` | `260ms` | 底部抽屉、面板出现 |
| `--wm-z-header` | `40` | 顶部导航、吸顶筛选 |
| `--wm-z-tabbar` | `80` | 用户端底部导航 |
| `--wm-z-action` | `90` | 固定底部操作区 |
| `--wm-z-overlay` | `200` | 遮罩、抽屉、弹窗 |

### 4.7 建议的 CSS 变量基线

```css
page,
:root {
  --wm-color-bg-page: #FCFBF9;
  --wm-color-bg-card: #FFFFFFE8;
  --wm-color-bg-soft: #FFF7F4;
  --wm-color-bg-mask: rgba(30, 36, 50, 0.46);
  --wm-color-primary: #E85A4F;
  --wm-color-primary-strong: #D84D43;
  --wm-color-primary-soft: #FFF1EE;
  --wm-color-secondary: #C99B73;
  --wm-color-border: #EFE6E1;
  --wm-color-border-strong: #F4C7BF;
  --wm-color-text-primary: #1E2432;
  --wm-color-text-secondary: #7F7B78;
  --wm-color-text-tertiary: #B4ACA8;
  --wm-radius-input: 18rpx;
  --wm-radius-card: 24rpx;
  --wm-radius-card-lg: 28rpx;
  --wm-radius-shell: 36rpx;
  --wm-radius-pill: 999rpx;
  --wm-space-5: 20rpx;
  --wm-space-6: 24rpx;
  --wm-space-8: 32rpx;
  --wm-shadow-soft: 0 14rpx 32rpx rgba(214, 185, 167, 0.16);
  --wm-shadow-card: 0 18rpx 36rpx rgba(214, 185, 167, 0.20);
  --wm-shadow-hero: 0 24rpx 56rpx rgba(177, 108, 95, 0.18);
  --wm-motion-base: 220ms;
}
```

## 5．场景模式

### 5.1 用户端 `consumer`

- 页面背景必须使用暖白或暖白渐变。
- 首页、详情页、预约流程允许使用大图、柔和遮罩与玻璃卡片。
- 主按钮必须使用珊瑚红，禁止替换为蓝紫色。
- 用户端存在自定义 tabbar 时，页面底部必须预留 tabbar 安全区。

### 5.2 服务人员端 `staff`

- 继续使用暖白与珊瑚品牌 token，但氛围化程度降低。
- 允许在头图或概览区使用深色渐变，以突出任务状态。
- 卡片优先展示待办、订单、档期、入口，不出现用户端大面积情绪文案。
- 主要操作仍使用主色，但次级信息卡应更多使用白底与轻边框。

### 5.3 管理端 `admin`

- 与用户端共用同一色板、字体、圆角、状态色。
- 布局更紧凑，默认白色面板、浅暖灰背景，不使用大面积玻璃质感。
- 图表、统计卡、风险提示必须优先保证可读性，不追求情绪化首屏。
- 激活态和重点值仍用珊瑚红或业务状态色，但面积要克制。

## 6．基础组件契约

### 6.1 页面结构组件

| 组件 | 必须暴露的公开参数 | 规范 |
| --- | --- | --- |
| `PageShell` | `scene`、`hasTabbar`、`hasSafeBottom`、`headerMode` | 所有页面必须从这里开始，负责背景、安全区、底部预留 |
| `PageSection` | `variant`、`padding`、`gap` | 负责内容分区，不允许页面直接散写区块边距 |
| `ActionArea` | `sticky`、`safeBottom`、`layout` | 统一底部操作区、支付栏、预约栏 |

### 6.2 导航与底部导航

| 组件 | 必须暴露的公开参数 | 规范 |
| --- | --- | --- |
| `BaseNavbar` | `scene`、`title`、`back`、`transparent` | 用户端详情页可透明叠图，管理端禁止透明 |
| `MpPageHeader` | `title`、`titleImage`、`scene` | 仅用于小程序自定义头部场景 |
| `CustomTabbar` | `items`、`activeKey`、`badgeMap` | 仅用户端使用；服务人员和管理员页禁止共用用户端 tabbar |

### 6.3 内容与表单组件

| 组件 | 必须暴露的公开参数 | 规范 |
| --- | --- | --- |
| `BaseCard` | `variant=surface|glass|hero|panel`、`scene`、`interactive` | `surface` 为默认卡片，`glass` 仅用户端，`panel` 主要给管理端 |
| `BaseButton` | `variant=primary|secondary|ghost|danger`、`size=sm|md|lg`、`block`、`loading` | 主按钮统一珊瑚红；危险操作必须显式使用 `danger` |
| `BaseInput` | `variant=filled|outlined`、`state`、`inputmode`、`disabled` | 输入框高度不低于 `88rpx` |
| `FilterChip` | `selected`、`closable`、`scene` | 列表页筛选项统一使用胶囊组件 |
| `StatusBadge` | `tone=neutral|success|warning|danger|info`、`size` | 所有订单状态、审核状态、候补状态统一使用 |
| `EmptyState` | `scene`、`title`、`description`、`actionText` | 空态结构固定，避免每页一套 |
| `LoadingState` | `scene`、`text` | 加载文案与动画节奏统一 |

### 6.4 图像与媒体组件

| 组件 | 必须暴露的公开参数 | 规范 |
| --- | --- | --- |
| `HeroMediaCard` | `image`、`overlay`、`title`、`subtitle` | 首页和预约流程首屏使用 |
| `MediaGrid` | `count`、`ratio`、`hasVideo` | 动态、作品、案例统一网格逻辑 |
| `MetricCard` | `tone`、`value`、`label`、`hint` | 仅服务人员端和管理员端使用 |

## 7．状态规范

### 7.1 通用交互状态

- `default`：白底或暖白底，细边框，阴影低。
- `active`：主色背景或主色软底，允许抬升阴影。
- `focus`：输入框或可编辑块必须出现描边或 ring。
- `disabled`：降低不透明度，不允许与正常按钮同对比度。
- `loading`：保留布局占位，禁止页面跳动。

### 7.2 业务状态色

| 业务状态 | 颜色策略 |
| --- | --- |
| 成功、通过、已完成 | `success` 色系 |
| 待确认、待处理、提醒 | `warning` 色系 |
| 失败、取消、退款、驳回 | `danger` 色系 |
| 中性说明、草稿、默认 | `info` 或 `secondary` 文本色 |

### 7.3 页面级状态

- 空态页必须保留页面标题与返回路径。
- 错误态必须给出重试动作，不允许只显示“加载失败”。
- 下拉刷新或列表“加载更多”必须有明确的视觉反馈。
- 弹窗、抽屉、底部面板必须统一遮罩色和圆角值。

## 8．无障碍与可用性

1．正文对比度默认满足 `4.5:1` 以上。

2．所有可点击主区域尺寸不得小于 `88rpx`。

3．错误态不能只靠红色描边，必须有文字说明。

4．图片类内容必须提供语义说明，装饰图不承载关键文案。

5．动画遵循轻量原则，优先使用透明度、颜色、阴影，不用大范围位移。

6．当系统偏好减少动画时，页面应允许关闭大幅过渡与自动轮播强化效果。

## 9．设计 Do 与 Don’t

### 9.1 必须做到

- 主操作永远清晰可见。
- 卡片边界始终明确。
- 页面背景、卡片背景、输入底色要有层级差异。
- 列表页先保证信息可扫读，再追求装饰。
- 管理端优先效率，用户端优先情绪与行动。

### 9.2 禁止继续发生

- 在页面里直接写与设计系统无关的随机十六进制颜色。
- 用页面私有类名重新造一套按钮、卡片和胶囊组件。
- 用户端、服务人员端、管理端混用同一套布局密度。
- 同一个页面里同时出现多种圆角体系。
- 为了“更醒目”而把说明文字做成低对比浅灰。

## 10．技术依据

### 10.1 uni-app

- 官方说明 `uni.scss` 用于整体控制应用风格，SCSS 变量可在组件样式中直接使用。
- 规范落地含义：主题 token 应统一从全局样式与主题 store 注入，不要散落在页面局部。

参考：

- https://github.com/dcloudio/unidocs-zh/blob/master/docs/collocation/uni-scss.md

### 10.2 Vue 3

- 官方说明 SFC 的 `<style>` 可通过 `v-bind()` 或 CSS 自定义属性响应式绑定样式值。
- 规范落地含义：页面或组件的场景差异应通过 token 和样式变量切换，而不是复制多份组件。

参考：

- https://github.com/vuejs/docs/blob/main/src/api/sfc-css-features.md
- https://github.com/vuejs/docs/blob/main/src/api/utility-types.md

### 10.3 Pinia

- 官方说明 `defineStore()` 适合承载全局状态、getter 与 action。
- 规范落地含义：主题与场景必须由统一 store 管理，禁止多个页面各自缓存一份主题色。

参考：

- https://github.com/vuejs/pinia/blob/v3/README.md
