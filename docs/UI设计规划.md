# 婚庆服务预约管理系统 - UI设计规划

> **项目版本:** v2.0  
> **创建日期:** 2026-01-26  
> **设计系统:** 基于装修管理系统风格 + 婚庆行业特性

---

## 一、设计理念

### 1.1 核心定位
- **浪漫优雅**：体现婚庆行业的浪漫氛围
- **专业可信**：展现服务的专业性和可靠性
- **温馨亲和**：营造温暖、贴心的用户体验
- **高端品质**：传递高品质服务形象

### 1.2 设计原则
1. **情感化设计**：通过色彩、字体、图形传递幸福感
2. **清晰易用**：信息层级分明，操作流程简洁
3. **视觉一致性**：全平台统一的设计语言
4. **响应式适配**：完美适配小程序、H5、PC端

---

## 二、色彩系统

### 2.1 主题色变量系统

系统通过装修管理后台动态配置主题色，自动生成完整的色彩变量体系。

#### 核心主题色变量
```css
/* 主色系 - 由装修管理配置 */
--color-primary: 动态配置;           /* 主色（默认：#7C3AED 优雅紫） */
--color-primary-dark-2: 自动生成;    /* 深色变体（shade 20%） */
--color-primary-light-3: 自动生成;   /* 浅色变体（tint 30%） */
--color-primary-light-5: 自动生成;   /* 浅色变体（tint 50%） */
--color-primary-light-7: 自动生成;   /* 浅色变体（tint 70%） */
--color-primary-light-9: 自动生成;   /* 浅色变体（tint 90%） */

/* 辅助色系 - 由装修管理配置 */
--color-secondary: 动态配置;         /* 辅助色（默认：#EC4899 玫瑰粉） */
--color-secondary-dark-2: 自动生成;
--color-secondary-light-3: 自动生成;
--color-secondary-light-5: 自动生成;
--color-secondary-light-7: 自动生成;
--color-secondary-light-9: 自动生成;

/* CTA色系 - 由装修管理配置 */
--color-cta: 动态配置;               /* 行动按钮色（默认：#F97316 活力橙） */
--color-cta-dark-2: 自动生成;
--color-cta-light-3: 自动生成;
--color-cta-light-5: 自动生成;
--color-cta-light-7: 自动生成;
--color-cta-light-9: 自动生成;

/* 点缀色系 - 由装修管理配置 */
--color-accent: 动态配置;            /* 点缀色（默认：#FFD700 金色） */
--color-accent-dark-2: 自动生成;
--color-accent-light-3: 自动生成;
--color-accent-light-5: 自动生成;
--color-accent-light-7: 自动生成;
--color-accent-light-9: 自动生成;

/* 其他配置色 */
--color-minor: 动态配置;             /* 次要色 */
--color-btn-text: 动态配置;          /* 按钮文字色 */
```

**使用场景**：
- `--color-primary`: 主要按钮、导航栏、重要标题、链接
- `--color-primary-light-9`: 浅色背景、hover状态背景
- `--color-primary-light-7`: 边框、分割线、禁用状态
- `--color-secondary`: 次要按钮、标签、装饰元素
- `--color-cta`: 立即预约、支付等关键行动按钮
- `--color-accent`: VIP标识、优惠信息、特殊标记

### 2.2 功能色彩（固定）
```css
--color-success: #19BE6B;        /* 成功：绿色 */
--color-warning: #FF9900;        /* 警告：橙色 */
--color-error: #FF2C3C;          /* 错误：红色 */
--color-info: #909399;           /* 信息：灰色 */
```

### 2.3 中性色彩（固定）
```css
--color-white: #FFFFFF;          /* 纯白 */
--color-black: #000000;          /* 纯黑 */
--color-main: #333333;           /* 主文本 */
--color-content: #666666;        /* 内容文本 */
--color-muted: #999999;          /* 次要文本 */
--color-page: #F6F6F6;           /* 页面背景 */
--color-light: #E5E5E5;          /* 浅灰 */
--color-disabled: #C8C9CC;       /* 禁用状态 */
```

### 2.4 背景色使用规范
```css
/* 使用主题色变量 */
background: var(--color-primary-light-9);     /* 主背景 */
background: var(--color-secondary-light-9);   /* 次背景 */
background: #FFFFFF;                          /* 卡片背景 */
background: rgba(0, 0, 0, 0.5);              /* 遮罩层 */
```


---

## 三、字体系统

### 3.1 字体家族（推荐方案）

#### 方案A：优雅衬线组合
```css
/* 标题字体：Playfair Display（优雅衬线） */
font-family-heading: 'Playfair Display', 'Source Han Serif CN', serif;

/* 正文字体：Inter（现代无衬线） */
font-family-body: 'Inter', 'Source Han Sans CN', 'PingFang SC', sans-serif;
```

**特点**：
- 高对比度，传递奢华感
- 适合婚庆、高端服务行业
- 标题优雅，正文清晰易读

#### 方案B：现代无衬线（当前使用）
```css
font-family: 'Source Han Sans CN', 'Helvetica Neue', 'Arial', sans-serif;
```

### 3.2 字号规范（UniApp rpx单位）

| 用途 | 字号 | 行高 | 字重 |
|------|------|------|------|
| 大标题 | 44rpx | 1.4 | 600 |
| 一级标题 | 40rpx | 1.4 | 600 |
| 二级标题 | 34rpx | 1.4 | 500 |
| 三级标题 | 32rpx | 1.4 | 500 |
| 正文 | 28rpx | 1.6 | 400 |
| 辅助文本 | 26rpx | 1.5 | 400 |
| 说明文本 | 24rpx | 1.5 | 400 |

### 3.3 Tailwind字号配置
```javascript
fontSize: {
  xs: '24rpx',
  sm: '26rpx',
  base: '28rpx',
  lg: '30rpx',
  xl: '32rpx',
  '2xl': '34rpx',
  '3xl': '38rpx',
  '4xl': '40rpx',
  '5xl': '44rpx'
}
```


---

## 四、组件设计规范

### 4.1 按钮设计

#### 主要按钮（Primary Button）
```vue
<tn-button type="primary" shape="round" size="lg">
  立即预约
</tn-button>
```

```scss
.btn-primary {
  background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark-2) 100%);
  border-radius: 32rpx;
  padding: 18rpx 32rpx;
  height: 72rpx;
  transition: all 0.2s ease;
  box-shadow: 0 6rpx 16rpx rgba(124, 58, 237, 0.22);
  
  &:active {
    transform: translateY(2rpx);
    box-shadow: 0 3rpx 10rpx rgba(124, 58, 237, 0.22);
  }
}
```

- **样式**：中圆角（border-radius: 32rpx）
- **颜色**：主题色渐变背景 + 白色文字
- **尺寸**：高度 72rpx（大）、64rpx（中）、52rpx（小）
- **状态**：active时轻微下移
- **过渡**：transition: all 0.2s ease

#### 次要按钮（Secondary Button）
```scss
.btn-secondary {
  background: transparent;
  border: 2rpx solid var(--color-primary);
  color: var(--color-primary);
  border-radius: 32rpx;
  
  &:active {
    background: var(--color-primary-light-9);
  }
}
```
- **样式**：圆角 + 边框
- **颜色**：透明背景 + 主题色边框 + 主题色文字
- **用途**：取消、返回等次要操作

#### CTA按钮（行动召唤）
```scss
.btn-cta {
  background: linear-gradient(135deg, var(--color-cta) 0%, var(--color-cta-dark-2) 100%);
  border-radius: 32rpx;
  box-shadow: 0 6rpx 16rpx rgba(249, 115, 22, 0.22);
}
```
- **颜色**：CTA色背景
- **用途**：支付、提交订单等关键操作
- **特效**：轻微阴影 + active时上浮效果

### 4.2 卡片设计

#### 标准卡片
```scss
.card {
  background: #FFFFFF;
  border-radius: 14rpx;
  padding: 20rpx;
  box-shadow: 0 2rpx 10rpx rgba(0, 0, 0, 0.08);
  transition: all 0.2s ease;
  
  &:active {
    box-shadow: 0 6rpx 18rpx rgba(0, 0, 0, 0.12);
    transform: translateY(-3rpx);
  }
}
```

#### 玻璃态卡片（Glassmorphism）
```scss
.glass-card {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(20rpx);
  border: 2rpx solid rgba(255, 255, 255, 0.3);
  border-radius: 24rpx;
  box-shadow: 0 16rpx 48rpx rgba(124, 58, 237, 0.12),
              0 6rpx 12rpx rgba(0, 0, 0, 0.04);
}
```
- **使用场景**：用户信息卡、动态卡片、特色展示区
- **注意事项**：确保背景对比度达到4.5:1

#### 主题色强调卡片
```scss
.highlight-card {
  background: var(--color-primary-light-9);
  border: 2rpx solid var(--color-primary-light-7);
  border-radius: 14rpx;
  
  .card-title {
    color: var(--color-primary);
  }
}
```

### 4.3 导航栏设计

#### 小程序导航栏
使用 UniApp 原生导航栏组件，通过主题配置动态设置样式：

```vue
<template>
  <page-meta :page-style="$theme.pageStyle">
    <!-- #ifndef H5 -->
    <navigation-bar 
      title="页面标题"
      :front-color="$theme.navColor" 
      :background-color="$theme.navBgColor" 
    />
    <!-- #endif -->
  </page-meta>
  
  <view class="page-container">
    <!-- 页面内容 -->
  </view>
</template>
```

**pages.json 配置**：
```json
{
  "path": "pages/example/example",
  "style": {
    "navigationBarTitleText": "页面标题",
    "enablePullDownRefresh": false
  }
}
```

- **高度**：系统自动适配（含状态栏）
- **背景**：通过 `$theme.navBgColor` 动态配置
- **文字**：通过 `$theme.navColor` 动态配置（`#ffffff` 或 `#000000`）
- **返回按钮**：系统自动显示（有历史记录时）
- **优势**：原生性能、自动适配各平台、无需额外组件

#### 底部标签栏（Tabbar）
- **高度**：120rpx + 安全区域
- **图标尺寸**：48rpx × 48rpx
- **选中状态**：主色图标 + 主色文字
- **未选中**：灰色图标 + 灰色文字

### 4.4 表单组件

#### 输入框
```scss
.input {
  height: 88rpx;
  padding: 0 24rpx;
  background: #F9FAFB;
  border-radius: 16rpx;
  border: 2rpx solid #E5E7EB;
  transition: all 0.2s ease;
  
  &:focus {
    background: #FFFFFF;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 6rpx var(--color-primary-light-9);
  }
}
```

#### 标签/徽章
```scss
.badge {
  padding: 6rpx 12rpx;
  background: var(--color-primary-light-9);
  color: var(--color-primary);
  border: 1rpx solid var(--color-primary-light-7);
  border-radius: 12rpx;
  font-size: 24rpx;
  font-weight: 500;
}
```


---

## 五、页面布局规范

### 5.1 间距系统（8px基准）

```javascript
spacing: {
  xs: '6rpx',    // 极小间距
  sm: '12rpx',   // 小间距
  md: '20rpx',   // 中等间距
  lg: '28rpx',   // 大间距
  xl: '40rpx',   // 超大间距
  '2xl': '56rpx' // 特大间距
}
```

### 5.2 页面结构

#### 标准页面布局
```
┌─────────────────────────┐
│   导航栏 (88rpx)         │
├─────────────────────────┤
│                         │
│   内容区域               │
│   padding: 20rpx        │
│                         │
├─────────────────────────┤
│   底部标签栏 (120rpx)    │
│   + 安全区域             │
└─────────────────────────┘
```

#### 卡片间距
- 卡片之间：16rpx
- 卡片内边距：20rpx
- 卡片与屏幕边缘：20rpx

### 5.3 响应式断点

| 设备 | 宽度 | 布局 |
|------|------|------|
| 手机 | < 768px | 单列 |
| 平板 | 768px - 1024px | 双列 |
| PC | > 1024px | 三列或更多 |



---

## 六、图标系统

### 6.1 图标库选择

**主要图标库**：图鸟UI内置图标（TuniaoUI Icons）
- 风格统一，适配UniApp
- 支持多种尺寸和颜色
- 性能优化良好

**备选图标库**：
- Heroicons（Web端）
- Lucide Icons（通用）

### 6.2 图标使用规范

```vue
<!-- 标准用法 -->
<tn-icon name="like" size="32" color="#7C3AED" />

<!-- 常用尺寸 -->
<tn-icon name="icon-name" size="24" /> <!-- 小图标 -->
<tn-icon name="icon-name" size="32" /> <!-- 中图标 -->
<tn-icon name="icon-name" size="48" /> <!-- 大图标 -->
```

### 6.3 禁止使用Emoji作为图标

❌ **错误示例**：
```vue
<text>🎨</text>  <!-- 不要用emoji -->
<text>🚀</text>
```

✅ **正确示例**：
```vue
<tn-icon name="palette" />
<tn-icon name="rocket" />
```

### 6.4 常用图标映射

| 功能 | 图标名称 | 使用场景 |
|------|---------|---------|
| 收藏 | star / star-fill | 收藏按钮 |
| 点赞 | like / like-fill | 点赞按钮 |
| 评论 | chat | 评论入口 |
| 分享 | share | 分享功能 |
| 位置 | map-pin | 地理位置 |
| 时间 | clock | 时间显示 |
| 用户 | user | 用户相关 |
| 设置 | setting | 设置入口 |


---

## 七、动效设计

### 7.1 过渡时间

```css
/* 快速过渡 - 颜色、透明度变化 */
transition: all 0.15s ease;

/* 标准过渡 - 按钮、卡片hover */
transition: all 0.2s ease;

/* 慢速过渡 - 页面切换、大型元素 */
transition: all 0.3s ease;
```

### 7.2 常用动画

#### Hover效果
```scss
.card {
  transition: all 0.3s ease;
  cursor: pointer;
  
  &:hover {
    transform: translateY(-4rpx);
    box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.12);
  }
}
```

#### 点击反馈
```scss
.button {
  &:active {
    transform: scale(0.98);
    opacity: 0.9;
  }
}
```

### 7.3 无障碍动画

```css
/* 尊重用户的减少动画偏好 */
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
```

### 7.4 禁止的动画

❌ **不要使用**：
- 无限循环的装饰性动画
- 过度的弹跳效果
- 超过500ms的过渡时间
- 依赖hover的关键功能（移动端不支持）


---

## 八、业务场景设计

### 8.1 首页设计

#### 核心元素
1. **搜索栏**：顶部固定，支持人员/服务搜索
2. **轮播图**：展示活动、优惠、精选案例
3. **导航菜单**：快速入口（人员分类、档期查询、优惠券等）
4. **人员推荐**：横向滚动卡片
5. **服务套餐**：网格布局展示
6. **案例作品**：瀑布流或网格展示
7. **客户评价**：轮播展示

#### 配色方案（使用主题色变量）
```scss
// 页面背景 - 使用主题色浅色渐变
background: linear-gradient(180deg, var(--color-primary-light-9) 0%, #FFFFFF 100%);

// 卡片背景
background: #FFFFFF;
box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.08);

// 强调元素
color: var(--color-primary);
background: var(--color-primary-light-9);
border: 2rpx solid var(--color-primary-light-7);

// CTA按钮
background: linear-gradient(135deg, var(--color-cta) 0%, var(--color-cta-dark-2) 100%);
```

### 8.2 人员详情页

#### 布局结构
```
┌─────────────────────────┐
│   头图（玻璃态导航栏）    │
├─────────────────────────┤
│   人员信息卡片           │
│   - 头像、姓名、评分     │
│   - 服务类型、价格       │
│   - 收藏、分享按钮       │
├─────────────────────────┤
│   标签页切换             │
│   - 简介 - 作品 - 评价   │
├─────────────────────────┤
│   底部操作栏             │
│   [查看档期] [立即预约]  │
└─────────────────────────┘
```

#### 关键设计
- 头图使用玻璃态效果
- 评分使用星级+数字组合
- 作品展示使用九宫格布局
- 底部操作栏固定，带安全区域


### 8.3 动态广场页

#### 设计要点
- **顶部标签**：关注/推荐/话题切换
- **排序筛选**：最新/最热/话题筛选
- **动态卡片**：
  - 用户头像 + 昵称 + 关注按钮
  - 图片/视频内容（九宫格或单图）
  - 文字描述 + 话题标签
  - 互动数据（浏览、点赞、评论）
  - 位置信息（可选）

#### 配色（使用主题色变量）
```scss
// 页面背景
background: linear-gradient(180deg, var(--color-primary-light-9) 0%, #F5F5F5 100%);

// 关注按钮
.follow-btn {
  background: var(--color-primary);
  color: #FFFFFF;
  
  &.followed {
    background: var(--color-primary-light-9);
    color: var(--color-primary);
    border: 2rpx solid var(--color-primary-light-7);
  }
}

// 话题标签
.topic-tag {
  background: var(--color-primary-light-9);
  color: var(--color-primary);
  border: 1rpx solid var(--color-primary-light-7);
}

// 点赞图标
.like-icon {
  color: var(--color-primary);
}
```

### 8.4 订单页面

#### 状态色彩（使用主题色和功能色）
```scss
// 订单状态颜色
.order-status {
  &.pending {
    color: var(--color-warning);      /* 待确认 */
    background: rgba(255, 153, 0, 0.1);
  }
  
  &.unpaid {
    color: var(--color-cta);          /* 待支付 */
    background: var(--color-cta-light-9);
  }
  
  &.paid {
    color: var(--color-success);      /* 已支付 */
    background: rgba(25, 190, 107, 0.1);
  }
  
  &.completed {
    color: var(--color-muted);        /* 已完成 */
    background: #F5F5F5;
  }
  
  &.cancelled {
    color: var(--color-error);        /* 已取消 */
    background: rgba(255, 44, 60, 0.1);
  }
}
```

#### 订单卡片设计
- 顶部状态标签（带颜色）
- 服务人员信息（头像 + 姓名 + 服务类型）
- 服务日期 + 地点
- 价格信息（原价 + 优惠 + 实付）
- 底部操作按钮（根据状态显示）

### 8.5 购物车页面

#### 特色功能
- **多选模式**：支持批量操作
- **档期冲突提示**：红色警告标识
- **费用明细**：实时计算总价
- **保存方案**：支持保存多个预约方案

#### 视觉设计（使用主题色变量）
```scss
// 选中状态
.cart-item-selected {
  border: 2rpx solid var(--color-primary);
  background: var(--color-primary-light-9);
}

// 冲突提示
.conflict-warning {
  color: var(--color-error);
  background: rgba(255, 44, 60, 0.1);
  border: 1rpx solid var(--color-error);
}

// 费用明细（固定底部，玻璃态）
.price-summary {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(20rpx);
  border-top: 1rpx solid var(--color-primary-light-7);
  
  .total-price {
    color: var(--color-primary);
    font-size: 48rpx;
    font-weight: 700;
  }
}
```


---

## 九、装修系统集成

### 9.1 装修配置结构

系统通过装修管理实现页面可视化配置，主要配置项：

```typescript
interface DecorateConfig {
  // 主题色配置
  themeColor1: string;      // 主色
  themeColor2: string;      // 辅助色
  buttonColor: string;      // 按钮文字色
  navigationBarColor: string; // 导航栏背景色
  topTextColor: 'white' | 'black'; // 导航栏文字色
  
  // 页面配置
  meta: {
    title: string;          // 页面标题
    bg_type: 1 | 2;        // 背景类型：1纯色 2图片
    bg_color: string;      // 背景颜色
    bg_image: string;      // 背景图片
  };
  
  // 组件配置
  data: Array<{
    name: string;          // 组件名称
    content: any;          // 组件内容配置
    styles: any;           // 组件样式配置
  }>;
}
```

### 9.2 可装修页面

| 页面ID | 页面名称 | 说明 |
|--------|---------|------|
| 1 | 首页 | 支持全部组件 |
| 2 | 个人中心 | 用户信息、快捷入口 |
| 5 | 系统风格 | 全局主题色配置 |

### 9.3 可用组件列表

#### 通用组件
- `search` - 搜索栏
- `banner` - 轮播图
- `nav` - 导航菜单
- `middle-banner` - 中间横幅
- `quick-entry` - 快捷入口
- `notice-bar` - 公告通知

#### 业务组件
- `staff-showcase` - 人员推荐
- `service-packages` - 服务套餐
- `portfolio-gallery` - 案例作品
- `customer-reviews` - 客户评价
- `activity-zone` - 活动专区
- `coupon-receive` - 优惠券领取
- `hot-topics` - 热门话题
- `wedding-countdown` - 婚礼倒计时

#### 用户中心组件
- `user-info` - 用户信息
- `my-service` - 我的服务
- `order-quick-entry` - 订单快捷入口
- `data-stats` - 数据统计


---

## 十、开发实施规范

### 10.1 代码规范

#### Vue组件结构
```vue
<template>
  <!-- 使用page-meta应用主题 -->
  <page-meta :page-style="$theme.pageStyle">
    <navigation-bar 
      :front-color="$theme.navColor" 
      :background-color="$theme.navBgColor" 
    />
  </page-meta>
  
  <view class="page-container">
    <!-- 页面内容 -->
  </view>
</template>

<script setup lang="ts">
import { useThemeStore } from '@/stores/theme'

const themeStore = useThemeStore()
</script>

<style lang="scss" scoped>
.page-container {
  min-height: 100vh;
  background-color: var(--color-page);
}
</style>
```

#### 使用Tailwind类名
```vue
<!-- 推荐：使用Tailwind原子类 -->
<view class="flex items-center justify-between p-[20rpx] bg-white rounded-[14rpx]">
  <text class="text-base text-main font-medium">标题</text>
  <tn-icon name="arrow-right" size="32" color="#999" />
</view>
```

### 10.2 主题色使用

#### 重要提示：小程序兼容性

**CSS变量在小程序中不生效**，必须使用内联样式（`:style`）动态应用主题色。

#### ❌ 错误方式（小程序不支持）
```scss
.button-primary {
  background-color: var(--color-primary);  /* 小程序中不显示 */
  color: var(--color-btn-text);
}
```

```vue
<view class="bg-primary">主色按钮</view>  <!-- 小程序中不显示 -->
```

#### ✅ 正确方式（使用内联样式）

**1. 在 script 中引入主题 store**
```vue
<script setup lang="ts">
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()

// 获取主题色浅色变体（可选）
const getColor = (type: string) => {
  const colors: Record<string, string> = {
    'primary-light-9': '#F3E8FF',
    'primary-light-7': '#D8B4FE',
    'cta-light-9': '#FFF7E6'
  }
  return colors[type] || '#F5F5F5'
}
</script>
```

**2. 使用 `:style` 绑定主题色**
```vue
<!-- 按钮背景色 -->
<view 
  class="btn-primary"
  :style="{ 
    background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
    color: $theme.btnColor
  }"
>
  立即预约
</view>

<!-- 文字颜色 -->
<text :style="{ color: $theme.primaryColor }">主色文字</text>
<text :style="{ color: $theme.ctaColor }">CTA色文字</text>

<!-- 图标颜色 -->
<tn-icon name="heart" size="32" :color="$theme.primaryColor" />

<!-- 条件样式 -->
<view 
  class="checkbox"
  :style="isSelected ? { 
    background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
    borderColor: $theme.primaryColor
  } : {}"
>
  <tn-icon v-if="isSelected" name="check" size="28" color="#FFFFFF" />
</view>

<!-- 浅色背景 -->
<view :style="{ backgroundColor: getColor('primary-light-9') }">
  浅色背景内容
</view>
```

**3. 主题色变量说明**

| 变量名 | 说明 | 使用场景 |
|--------|------|---------|
| `$theme.primaryColor` | 主色 | 主要按钮、导航栏、重要标题 |
| `$theme.secondaryColor` | 辅助色 | 次要按钮、标签、装饰元素 |
| `$theme.ctaColor` | CTA色 | 价格、支付按钮、关键操作 |
| `$theme.accentColor` | 点缀色 | VIP标识、优惠信息 |
| `$theme.btnColor` | 按钮文字色 | 按钮内的文字颜色 |
| `$theme.navColor` | 导航栏文字色 | 导航栏标题和图标 |
| `$theme.navBgColor` | 导航栏背景色 | 导航栏背景 |

**4. 完整示例**

```vue
<template>
  <view class="page">
    <!-- 主要按钮 -->
    <view 
      class="btn-primary"
      :style="{ 
        background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
        color: $theme.btnColor
      }"
      @click="handleClick"
    >
      <tn-icon name="check" size="32" :color="$theme.btnColor" />
      <text>确认</text>
    </view>

    <!-- 价格显示 -->
    <view class="price-section">
      <text class="price-symbol" :style="{ color: $theme.ctaColor }">¥</text>
      <text class="price-value" :style="{ color: $theme.ctaColor }">{{ price }}</text>
    </view>

    <!-- 复选框 -->
    <view 
      class="checkbox"
      :style="isChecked ? { 
        background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
        borderColor: $theme.primaryColor
      } : {}"
      @click="toggleCheck"
    >
      <tn-icon v-if="isChecked" name="check" size="28" color="#FFFFFF" />
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()
const isChecked = ref(false)
const price = ref(999)

const handleClick = () => {
  console.log('按钮点击')
}

const toggleCheck = () => {
  isChecked.value = !isChecked.value
}
</script>

<style lang="scss" scoped>
.btn-primary {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12rpx;
  height: 72rpx;
  padding: 0 40rpx;
  border-radius: 36rpx;
  font-size: 32rpx;
  font-weight: 700;
  box-shadow: 0 8rpx 20rpx rgba(124, 58, 237, 0.32);
  transition: all 0.3s ease;
  
  &:active {
    transform: translateY(2rpx);
    box-shadow: 0 4rpx 12rpx rgba(124, 58, 237, 0.32);
  }
}

.price-section {
  display: flex;
  align-items: baseline;
  
  .price-symbol {
    font-size: 28rpx;
    font-weight: 600;
    margin-right: 4rpx;
  }
  
  .price-value {
    font-size: 44rpx;
    font-weight: 700;
  }
}

.checkbox {
  width: 44rpx;
  height: 44rpx;
  border: 3rpx solid #ddd;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}
</style>
```

#### H5/Web端（可选使用CSS变量）

H5和Web端支持CSS变量，可以使用以下方式：

```scss
.button-primary {
  background-color: var(--color-primary);
  color: var(--color-btn-text);
  
  &:hover {
    background-color: var(--color-primary-dark-2);
  }
}
```

```vue
<view class="bg-primary text-btn-text">主色按钮</view>
<view class="bg-primary-light-9 text-primary">浅色背景</view>
```

**但为了保持代码一致性，建议全平台统一使用内联样式方式。**


### 10.3 组件开发规范

#### 装修组件开发模板
```vue
<template>
  <view 
    class="widget-container" 
    :style="containerStyle"
  >
    <!-- 组件内容 -->
    <view class="widget-title" v-if="content.title">
      {{ content.title }}
    </view>
    
    <!-- 主体内容 -->
    <view class="widget-body">
      <!-- ... -->
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  content: any;  // 内容配置
  styles: any;   // 样式配置
}

const props = defineProps<Props>()

// 容器样式
const containerStyle = computed(() => {
  const { padding, margin, bgColor } = props.styles || {}
  return {
    padding: padding ? `${padding}rpx` : '24rpx',
    margin: margin ? `${margin}rpx` : '0',
    backgroundColor: bgColor || 'transparent'
  }
})
</script>

<style lang="scss" scoped>
.widget-container {
  width: 100%;
}

.widget-title {
  font-size: 32rpx;
  font-weight: 600;
  color: var(--color-main);
  margin-bottom: 24rpx;
}
</style>
```

### 10.4 性能优化

#### 图片优化
```vue
<!-- 使用懒加载 -->
<image 
  :src="imageUrl" 
  mode="aspectFill"
  lazy-load
  :show-menu-by-longpress="true"
/>

<!-- 使用缩略图 -->
<image :src="thumbnail" />  <!-- 列表页 -->
<image :src="original" />   <!-- 详情页 -->
```

#### 列表优化
```vue
<!-- 使用虚拟列表（长列表） -->
<z-paging 
  ref="paging"
  v-model="dataList"
  @query="queryList"
>
  <template #default="{ item }">
    <list-item :data="item" />
  </template>
</z-paging>
```


---

## 十一、无障碍设计

### 11.1 颜色对比度

确保文字与背景的对比度符合WCAG 2.1标准：

| 文字类型 | 最小对比度 | 推荐对比度 |
|---------|-----------|-----------|
| 正文文字 | 4.5:1 | 7:1 |
| 大号文字 | 3:1 | 4.5:1 |
| 图标 | 3:1 | 4.5:1 |

#### 检查工具
- 使用Chrome DevTools的Lighthouse
- 在线工具：WebAIM Contrast Checker

### 11.2 触摸目标尺寸

```css
/* 最小触摸区域：44px × 44px (约88rpx × 88rpx) */
/* 视觉按钮可更紧凑，但需通过外层padding或热区补足触摸面积 */
.touch-target {
  min-width: 88rpx;
  min-height: 88rpx;
  padding: 12rpx;
}
```

### 11.3 焦点状态

```scss
.interactive-element {
  &:focus {
    outline: 4rpx solid var(--color-primary);
    outline-offset: 4rpx;
  }
  
  &:focus:not(:focus-visible) {
    outline: none;
  }
}
```

### 11.4 语义化标签

```vue
<!-- 使用语义化的角色和标签 -->
<button role="button" aria-label="收藏">
  <tn-icon name="heart" />
</button>

<image :src="avatar" alt="用户头像" />

<view role="navigation" aria-label="主导航">
  <!-- 导航内容 -->
</view>
```

---

## 十二、交付检查清单

### 12.1 视觉质量检查

- [ ] 无Emoji作为图标，全部使用SVG图标
- [ ] 所有图标来自统一图标库（图鸟UI Icons）
- [ ] 品牌Logo正确且清晰
- [ ] Hover状态不会导致布局偏移
- [ ] 直接使用主题色变量，不使用var()包装

### 12.2 交互检查

- [ ] 所有可点击元素添加`cursor-pointer`（Web端）
- [ ] Hover状态提供清晰的视觉反馈
- [ ] 过渡动画流畅（150-300ms）
- [ ] 焦点状态对键盘导航可见
- [ ] 移动端点击反馈明显

### 12.3 主题适配检查

- [ ] 浅色模式文字对比度充足（4.5:1以上）
- [ ] 玻璃态元素在浅色模式下可见
- [ ] 边框在两种模式下都可见
- [ ] 测试主题色切换功能正常


### 12.4 布局检查

- [ ] 浮动元素与屏幕边缘有适当间距
- [ ] 内容不会被固定导航栏遮挡
- [ ] 响应式断点测试：375px、768px、1024px、1440px
- [ ] 无横向滚动条（移动端）
- [ ] 安全区域适配正确

### 12.5 无障碍检查

- [ ] 所有图片有alt文本
- [ ] 表单输入框有对应标签
- [ ] 颜色不是唯一的信息指示器
- [ ] 支持`prefers-reduced-motion`
- [ ] 触摸目标尺寸不小于88rpx

### 12.6 性能检查

- [ ] 图片使用懒加载
- [ ] 长列表使用虚拟滚动
- [ ] 避免过度的动画效果
- [ ] CSS变量正确应用
- [ ] 无不必要的重渲染

---

## 十三、设计资源

### 13.1 设计工具

- **Figma**：UI设计和原型制作
- **Sketch**：备选设计工具
- **Adobe XD**：交互原型

### 13.2 图标资源

- **图鸟UI Icons**：https://vue3.tuniaokj.com/components/icon.html
- **Heroicons**：https://heroicons.com/
- **Lucide Icons**：https://lucide.dev/

### 13.3 字体资源

- **Google Fonts**：https://fonts.google.com/
  - Playfair Display
  - Inter
  - Cormorant
  - Montserrat

### 13.4 配色工具

- **Coolors**：https://coolors.co/
- **Adobe Color**：https://color.adobe.com/
- **Contrast Checker**：https://webaim.org/resources/contrastchecker/

### 13.5 参考案例

- **婚礼纪**：婚庆行业标杆产品
- **大众点评（婚庆频道）**：服务预约流程参考
- **小红书**：动态社区交互参考
- **美团（婚庆）**：订单管理流程参考

---

## 十四、版本更新记录

| 版本 | 日期 | 更新内容 |
|------|------|---------|
| v1.0 | 2026-01-26 | 初始版本，建立完整设计系统 |

---

**文档维护者**：开发团队  
**最后更新**：2026-01-26
