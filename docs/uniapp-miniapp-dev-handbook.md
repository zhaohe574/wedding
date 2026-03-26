# UniApp 小程序设计系统接入手册

## 1．文档定位

本手册用于把 `uniapp-miniapp-design-system.md` 与 `uniapp-miniapp-page-blueprints.md` 变成可实施的工程规则。目标不是“建议”，而是确定未来改造时应该怎样接管现有代码。

本手册默认当前工程基础如下：

- `uniapp/src/App.vue`：全局页面背景与样式入口。
- `uniapp/src/stores/theme.ts`：当前主题 store。
- `uniapp/src/mixins/theme.ts`：把 `$theme` 注入页面与组件。
- `uniapp/src/styles/index.scss`：全局样式聚合入口。
- `uniapp/src/components/base/*`：基础卡片、按钮、导航、输入等组件。
- `uniapp/src/components/tabbar/tabbar.vue`：用户端自定义 tabbar。

## 2．当前现状与接管原则

### 2.1 当前现状

当前项目已经存在三套并行视觉语义：

1．`cinema-*`：旧主题 store 和基础组件使用的变量前缀。

2．`dynamic-*`：动态页与动态卡片的页面私有变量。

3．直接写死的页面色值：例如首页、服务人员中心、管理员看板中的局部十六进制颜色。

这三套体系共同工作，但会产生四个问题：

- 页面视觉统一靠人工维持，难以扩展。
- 主题变量语义与婚礼视觉不一致。
- 基础组件和业务页面没有同一个命名系统。
- 新页面容易继续复制私有颜色和私有间距。

### 2.2 接管原则

未来实施必须遵守以下顺序：

1．先定义新的 `wm` token 语义。

2．再由主题 store 统一输出 `wm` token。

3．基础组件改为消费 `wm` token。

4．业务组件和页面只消费基础组件与 `wm` token。

5．历史 `cinema-*` 和 `dynamic-*` 只作为过渡兼容层，不能再新增。

## 3．正式主题接口

### 3.1 场景定义

```ts
export type WmScene = 'consumer' | 'staff' | 'admin'
```

### 3.2 Token 结构

```ts
export interface WmThemeTokens {
  colors: Record<string, string>
  text: Record<string, string>
  radius: Record<string, string>
  space: Record<string, string>
  shadow: Record<string, string>
  motion: Record<string, string>
  zIndex: Record<string, string>
}
```

### 3.3 主题 Store 结构

```ts
export interface WmThemeState {
  scene: WmScene
  vars: string
  tokens: WmThemeTokens
  pageStyle: string
}
```

规范要求：

- `scene` 必须是显式字段，不能靠页面自己猜。
- `vars` 负责注入 CSS 变量字符串。
- `pageStyle` 继续给 `page-meta` 使用。
- 所有业务页面从统一 store 读取主题，不允许自己缓存一份主题对象。

## 4．全局注入规则

### 4.1 页面级样式注入

统一保留 `page-meta :page-style="$theme.pageStyle"` 入口，因为当前页面已经大量依赖它。未来只更换它输出的变量内容，不改变页面接入方式。

建议使用方式：

```vue
<template>
  <page-meta :page-style="$theme.pageStyle" />
  <view class="wm-page wm-page--consumer">
    <slot />
  </view>
</template>
```

### 4.2 全局 SCSS 注入

根据 uni-app 官方文档，`uni.scss` 中的 SCSS 变量会自动注入到组件样式中。未来应把设计系统的“SCSS 兼容映射层”放在全局样式入口，而不是在页面里重复导入。

建议结构：

```scss
/* src/styles/tokens.scss */
$wm-color-bg-page: #FCFBF9;
$wm-color-primary: #E85A4F;
$wm-radius-card: 24rpx;
```

```scss
/* src/styles/index.scss */
@import './tokens.scss';
@import './public.scss';
@import './utilities.scss';
```

### 4.3 Vue SFC 动态样式

根据 Vue 3 官方文档，SFC 的 `<style>` 可通过 `v-bind()` 或 CSS 自定义属性响应式接收变量。未来页面和组件应优先使用 token，而不是把颜色字符串塞进模板里。

推荐写法：

```vue
<script setup lang="ts">
const cardTone = 'var(--wm-color-bg-card)'
</script>

<style scoped>
.panel {
  background: v-bind(cardTone);
}
</style>
```

或：

```vue
<template>
  <view class="panel" :style="{ '--panel-bg': 'var(--wm-color-bg-card)' }" />
</template>

<style scoped>
.panel {
  background: var(--panel-bg);
}
</style>
```

## 5．组件接入契约

### 5.1 基础组件必须统一的公开参数

| 组件 | 必须保留或新增的参数 | 约束 |
| --- | --- | --- |
| `BaseCard` | `variant`、`scene`、`interactive` | 卡片语义由 `variant` 决定，不允许再靠页面 class 私自改造 |
| `BaseButton` | `variant`、`size`、`block`、`loading`、`disabled` | 主按钮、次按钮、危险按钮全部走统一接口 |
| `BaseInput` | `variant`、`state`、`inputmode` | 输入框高、圆角、描边、焦点 ring 必须统一 |
| `BaseNavbar` | `scene`、`transparent`、`title` | 由场景控制背景与文字，不让页面散写颜色 |
| `StatusBadge` | `tone`、`size` | 统一业务状态胶囊样式 |
| `PageShell` | `scene`、`hasTabbar`、`hasSafeBottom` | 所有页面外壳由它负责 |

### 5.2 页面级组合件

必须新增或收敛为统一组合件的模块：

- `PageSection`
- `ActionArea`
- `FilterBar`
- `MetricCard`
- `EmptyState`
- `LoadingState`
- `BottomSheet`

理由：

- 这些模块在首页、动态、订单、预约、服务人员中心、管理员看板中反复出现。
- 如果不抽象，后续会继续出现页面私有实现。

## 6．三类场景的实施规则

### 6.1 用户端 `consumer`

- 页面背景用 `--wm-color-bg-page` 与轻暖渐变。
- 支持胶囊 tabbar、玻璃卡、图片首屏。
- 内容区仍然优先使用统一卡片，不要直接在页面根节点堆文字。

### 6.2 服务人员端 `staff`

- 头部概览区允许深色渐变。
- 内容区仍回到白底 panel。
- 重点是“任务态”和“状态态”，所以 `MetricCard` 与 `StatusBadge` 使用频率更高。

### 6.3 管理端 `admin`

- 不使用浮动 tabbar、玻璃面板和情绪化 hero。
- 面板密度更高，间距可从 `24rpx` 收紧到 `20rpx` 或 `16rpx`。
- 统计卡、图表卡、提醒卡都走 `panel` 变体，不允许各页各写。

## 7．页面实现范式

### 7.1 用户端首页

```vue
<template>
  <page-meta :page-style="$theme.pageStyle" />
  <PageShell scene="consumer" :has-tabbar="true">
    <HeroMediaCard />
    <PageSection variant="plain">
      <BaseButton variant="primary" size="lg" block>查询档期</BaseButton>
    </PageSection>
    <PageSection variant="list">
      <BaseCard variant="surface" scene="consumer" />
    </PageSection>
  </PageShell>
</template>
```

### 7.2 列表筛选页

```vue
<template>
  <PageShell scene="consumer" :has-tabbar="true">
    <BaseNavbar title="动态" />
    <FilterBar>
      <FilterChip :selected="true">全部</FilterChip>
      <FilterChip>案例</FilterChip>
    </FilterBar>
    <PageSection variant="list">
      <DynamicCard variant="editorial" />
    </PageSection>
  </PageShell>
</template>
```

### 7.3 表单页

```vue
<template>
  <PageShell scene="consumer">
    <BaseNavbar title="个人资料" />
    <PageSection variant="form">
      <BaseInput variant="filled" inputmode="text" />
      <BaseInput variant="filled" inputmode="tel" />
    </PageSection>
    <ActionArea sticky safe-bottom>
      <BaseButton variant="primary" size="lg" block>保存</BaseButton>
    </ActionArea>
  </PageShell>
</template>
```

### 7.4 服务人员工作台

```vue
<template>
  <PageShell scene="staff">
    <BaseNavbar title="服务人员中心" />
    <PageSection variant="hero">
      <BaseCard variant="hero" scene="staff" />
    </PageSection>
    <PageSection variant="dashboard">
      <MetricCard />
      <MetricCard />
    </PageSection>
  </PageShell>
</template>
```

### 7.5 管理员看板

```vue
<template>
  <PageShell scene="admin">
    <BaseNavbar title="经营驾驶舱" />
    <PageSection variant="panel">
      <MetricCard />
      <MetricCard />
      <MetricCard />
      <MetricCard />
    </PageSection>
    <PageSection variant="panel">
      <TrendChart />
    </PageSection>
  </PageShell>
</template>
```

## 8．现有文件的收敛目标

### 8.1 必须优先收敛的文件

| 文件 | 当前问题 | 未来要求 |
| --- | --- | --- |
| `uniapp/src/App.vue` | 仍使用旧深色影院感页面底色 | 改为统一输出 `wm` 页面背景 |
| `uniapp/src/stores/theme.ts` | 语义仍是 `cinema-*` | 改为输出 `wm` token，并兼容旧字段过渡 |
| `uniapp/src/components/base/BaseCard.vue` | 变体命名偏旧体系 | 收敛为 `surface|glass|hero|panel` |
| `uniapp/src/components/base/BaseButton.vue` | 语义能复用，但色值仍由旧 token 驱动 | 统一绑定 `wm` token |
| `uniapp/src/components/base/BaseInput.vue` | 仍直接消费 `cinema-*` 变量 | 按 `wm` 表单语义重写 |
| `uniapp/src/components/base/BaseNavbar.vue` | 只区分背景和文字色 | 新增 `scene` 与透明模式语义 |
| `uniapp/src/components/tabbar/tabbar.vue` | 颜色写死，未正式走设计系统命名 | 改为用户端专属 `consumer` 组件 |
| `uniapp/src/styles/dynamic.scss` | 页面私有视觉体系 | 并入正式 token 或删除 |
| `uniapp/src/pages/index/index.vue` | 已靠近新设计，但仍有硬编码值 | 迁移到 `wm` token |
| `uniapp/src/packages/pages/staff_center/staff_center.vue` | 是 `staff` 模式原型，但未走统一 token | 收敛到 `staff` 场景组件 |
| `uniapp/src/packages/pages/admin_dashboard/admin_dashboard.vue` | 是 `admin` 模式原型，但仍大量页面私有样式 | 收敛到 `panel`、`MetricCard` 等统一组合件 |

### 8.2 严禁继续新增的内容

- 新的 `cinema-*` 变量。
- 新的 `dynamic-*` 变量。
- 页面内随机十六进制颜色。
- 页面私有按钮和页面私有卡片样式。
- 管理端借用用户端浮动 tabbar。

## 9．实施顺序

### 9.1 第一阶段：主题层

1．定义 `wm` token 数据结构。

2．在主题 store 中输出 `wm` 变量字符串。

3．保留旧字段兼容现有页面，但把新规范全部绑定到 `wm`。

### 9.2 第二阶段：基础组件层

1．重写 `BaseCard`、`BaseButton`、`BaseInput`、`BaseNavbar`。

2．补齐 `StatusBadge`、`PageShell`、`PageSection`、`ActionArea`。

3．为 `consumer`、`staff`、`admin` 三场景补齐差异。

### 9.3 第三阶段：页面层

1．用户主链路先收敛：首页、动态、个人中心、订单、预约流程。

2．服务人员中心和其资源页再统一。

3．最后处理管理员看板与系统页。

## 10．验收清单

1．任意新页面创建时，不需要再手写新的主色、圆角、阴影、按钮样式。

2．所有页面都能明确归属到 `consumer`、`staff`、`admin` 之一。

3．路由映射表中的每个页面都有对应模板。

4．登录、编辑、预约、支付、看板页面之间不会再出现四套不同按钮。

5．基础组件演示页能够同时展示三种场景变体。

6．删除任意页面私有颜色后，页面仍能从主题 token 正常取值。

## 11．技术依据

### 11.1 uni-app 官方依据

`uni.scss` 用于整体控制应用风格，SCSS 变量可直接在组件样式中使用。因此主题 token 必须优先放在全局样式和主题 store，而不是散落到页面。

参考：

- https://github.com/dcloudio/unidocs-zh/blob/master/docs/collocation/uni-scss.md

### 11.2 Vue 3 官方依据

SFC 支持在 `<style>` 中通过 `v-bind()` 或自定义 CSS 变量接收响应式样式值。这意味着主题切换与场景切换应通过变量层完成，而不是复制多份组件模板。

参考：

- https://github.com/vuejs/docs/blob/main/src/api/sfc-css-features.md
- https://github.com/vuejs/docs/blob/main/src/api/utility-types.md

### 11.3 Pinia 官方依据

`defineStore()` 是集中管理全局状态、getter 与 action 的正式方式。主题与场景状态应统一由 store 管理，确保页面、基础组件和业务组件从同一源取值。

参考：

- https://github.com/vuejs/pinia/blob/v3/README.md
