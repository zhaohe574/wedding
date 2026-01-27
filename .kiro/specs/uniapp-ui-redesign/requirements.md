# 需求文档：UniApp前端UI重构

## 简介

本项目旨在根据《UI设计规划.md》中定义的设计系统，对婚庆服务预约管理系统的UniApp前端进行全面的UI重构。重构将采用浪漫优雅的设计风格，使用图鸟UI组件库，并确保在小程序、H5和App平台上的一致性体验。

## 术语表

- **System**: 婚庆服务预约管理系统UniApp前端
- **User**: 使用小程序/H5/App的终端用户
- **Theme_System**: 主题配置系统，管理颜色、字体等设计变量
- **TuniaoUI**: 图鸟UI组件库（@tuniao/tnui-vue3-uniapp）
- **Design_Token**: 设计令牌，包括颜色、字号、间距等设计变量
- **Component**: 可复用的UI组件
- **Page**: 应用页面
- **Decoration_System**: 装修系统，支持页面可视化配置

## 需求

### 需求 1：主题色系统实施

**用户故事**：作为开发者，我希望实施统一的主题色系统，以便在整个应用中保持视觉一致性。

#### 验收标准

1. THE System SHALL 定义完整的主题色变量，包括主色（#7C3AED）、辅助色（#EC4899）、CTA色（#F97316）和点缀色（#FFD700）
2. THE System SHALL 为每个主题色提供5个亮度变体（light-3, light-5, light-7, light-9, dark-2）
3. THE System SHALL 定义功能色彩（success、warning、error、info）及其变体
4. THE System SHALL 定义中性色彩系统（white、black、main、content、muted、page、light、disabled）
5. THE System SHALL 通过CSS变量实现主题色的全局可配置性
6. THE System SHALL 在Tailwind配置中映射所有主题色变量
7. THE System SHALL 支持通过装修系统动态修改主题色

### 需求 2：字体系统标准化

**用户故事**：作为设计师，我希望建立标准化的字体系统，以便确保文本层级清晰且易读。

#### 验收标准

1. THE System SHALL 定义7个标准字号（24rpx、26rpx、28rpx、32rpx、34rpx、40rpx、44rpx）
2. THE System SHALL 为每个字号定义对应的行高（1.4-1.6）和字重（400-600）
3. THE System SHALL 使用'Source Han Sans CN'作为主要字体族
4. THE System SHALL 在Tailwind配置中定义fontSize映射（xs、sm、base、lg、xl、2xl、3xl、4xl、5xl）
5. WHEN 显示标题时，THE System SHALL 使用字重600和较大字号
6. WHEN 显示正文时，THE System SHALL 使用字重400和28rpx字号
7. WHEN 显示辅助文本时，THE System SHALL 使用字重400和24-26rpx字号

### 需求 3：间距系统规范化

**用户故事**：作为开发者，我希望使用统一的间距系统，以便保持布局的一致性和节奏感。

#### 验收标准

1. THE System SHALL 基于8rpx基准定义间距系统
2. THE System SHALL 定义6个标准间距值（8rpx、16rpx、24rpx、32rpx、48rpx、64rpx）
3. THE System SHALL 在Tailwind配置中映射间距值（xs、sm、md、lg、xl、2xl）
4. WHEN 设置卡片间距时，THE System SHALL 使用24rpx
5. WHEN 设置卡片内边距时，THE System SHALL 使用24rpx
6. WHEN 设置卡片与屏幕边缘距离时，THE System SHALL 使用24rpx
7. THE System SHALL 在所有页面和组件中一致应用间距系统

### 需求 4：按钮组件重构

**用户故事**：作为用户，我希望按钮具有清晰的视觉层级和良好的交互反馈，以便理解不同操作的重要性。

#### 验收标准

1. THE System SHALL 提供三种按钮类型：主要按钮、次要按钮、CTA按钮
2. WHEN 渲染主要按钮时，THE System SHALL 使用主色背景（#7C3AED）和白色文字
3. WHEN 渲染次要按钮时，THE System SHALL 使用透明背景、主色边框和主色文字
4. WHEN 渲染CTA按钮时，THE System SHALL 使用橙色背景（#F97316）和白色文字
5. THE System SHALL 为按钮设置圆角（48rpx）
6. THE System SHALL 提供三种按钮尺寸：大（88rpx）、中（72rpx）、小（56rpx）
7. WHEN 用户点击按钮时，THE System SHALL 提供视觉反馈（scale 0.98，opacity 0.9）
8. THE System SHALL 为按钮添加过渡动画（transition: all 0.2s ease）

### 需求 5：卡片组件标准化

**用户故事**：作为用户，我希望卡片具有统一的外观和交互效果，以便获得一致的浏览体验。

#### 验收标准

1. THE System SHALL 提供两种卡片样式：标准卡片和玻璃态卡片
2. WHEN 渲染标准卡片时，THE System SHALL 使用白色背景、16rpx圆角、24rpx内边距
3. WHEN 渲染标准卡片时，THE System SHALL 添加阴影（0 2rpx 12rpx rgba(0, 0, 0, 0.08)）
4. WHEN 用户悬停卡片时，THE System SHALL 增强阴影并向上移动4rpx
5. WHEN 渲染玻璃态卡片时，THE System SHALL 使用半透明背景（rgba(255, 255, 255, 0.8)）
6. WHEN 渲染玻璃态卡片时，THE System SHALL 应用背景模糊效果（backdrop-filter: blur(20px)）
7. THE System SHALL 确保玻璃态卡片的文字对比度达到4.5:1

### 需求 6：导航栏组件统一

**用户故事**：作为用户，我希望导航栏在不同页面保持一致的外观和行为，以便快速识别和导航。

#### 验收标准

1. THE System SHALL 使用图鸟UI的tn-navbar组件
2. THE System SHALL 设置导航栏高度为88rpx（含状态栏）
3. THE System SHALL 支持自定义导航栏背景色（通过主题配置）
4. THE System SHALL 支持自定义导航栏文字色（白色或黑色，根据背景自适应）
5. WHEN 页面需要返回按钮时，THE System SHALL 在左侧显示返回图标
6. THE System SHALL 支持固定定位（fixed: true）
7. THE System SHALL 在所有使用自定义导航栏的页面应用统一配置

### 需求 7：底部标签栏重构

**用户故事**：作为用户，我希望底部标签栏清晰显示当前位置，以便在主要功能间快速切换。

#### 验收标准

1. THE System SHALL 使用自定义tabbar组件
2. THE System SHALL 设置tabbar高度为120rpx加安全区域
3. THE System SHALL 设置图标尺寸为48rpx × 48rpx
4. WHEN 标签被选中时，THE System SHALL 使用主色显示图标和文字
5. WHEN 标签未被选中时，THE System SHALL 使用灰色显示图标和文字
6. THE System SHALL 使用图鸟UI图标替代图片图标
7. THE System SHALL 确保tabbar在所有平台（小程序、H5、App）正常显示

### 需求 8：表单组件优化

**用户故事**：作为用户，我希望表单输入框具有清晰的焦点状态和良好的可用性，以便轻松完成信息填写。

#### 验收标准

1. THE System SHALL 设置输入框高度为88rpx
2. THE System SHALL 设置输入框内边距为24rpx
3. THE System SHALL 使用浅灰背景（#F8F8F8）和12rpx圆角
4. WHEN 输入框获得焦点时，THE System SHALL 改变背景为白色并显示主色边框
5. THE System SHALL 为输入框添加过渡动画（transition: all 0.2s ease）
6. THE System SHALL 确保输入框在所有平台具有一致的外观
7. THE System SHALL 为必填字段提供清晰的视觉标识

### 需求 9：图标系统标准化

**用户故事**：作为开发者，我希望使用统一的图标系统，以便保持视觉风格的一致性。

#### 验收标准

1. THE System SHALL 使用图鸟UI内置图标作为主要图标库
2. THE System SHALL 禁止使用Emoji作为UI图标
3. THE System SHALL 定义常用图标的标准名称映射（heart、like、chat、share、map-pin、clock、user、setting）
4. THE System SHALL 提供三种标准图标尺寸：小（24）、中（32）、大（48）
5. THE System SHALL 支持自定义图标颜色
6. WHEN 显示功能图标时，THE System SHALL 使用tn-icon组件
7. THE System SHALL 在所有页面和组件中一致使用图标系统

### 需求 10：首页重构

**用户故事**：作为用户，我希望首页清晰展示核心功能和内容，以便快速找到所需服务。

#### 验收标准

1. THE System SHALL 在顶部显示搜索栏，支持人员/服务搜索
2. THE System SHALL 显示轮播图，展示活动、优惠、精选案例
3. THE System SHALL 显示导航菜单，提供快捷入口（人员分类、档期查询、优惠券等）
4. THE System SHALL 显示人员推荐区域，使用横向滚动卡片
5. THE System SHALL 显示服务套餐区域，使用网格布局
6. THE System SHALL 显示案例作品区域，使用瀑布流或网格布局
7. THE System SHALL 显示客户评价区域，使用轮播展示
8. THE System SHALL 使用浅紫色渐变背景（#FAF5FF → #FFFFFF）
9. THE System SHALL 确保所有组件使用统一的间距和样式

### 需求 11：人员详情页重构

**用户故事**：作为用户，我希望人员详情页清晰展示人员信息和作品，以便做出预约决策。

#### 验收标准

1. THE System SHALL 在顶部显示头图，使用玻璃态导航栏
2. THE System SHALL 显示人员信息卡片，包含头像、姓名、评分、服务类型、价格
3. THE System SHALL 在人员信息卡片中提供收藏和分享按钮
4. THE System SHALL 提供标签页切换（简介、作品、评价）
5. THE System SHALL 在作品标签页使用九宫格布局展示作品
6. THE System SHALL 在底部显示固定操作栏，包含"查看档期"和"立即预约"按钮
7. THE System SHALL 确保底部操作栏适配安全区域
8. THE System SHALL 使用星级+数字组合显示评分

### 需求 12：动态广场页重构

**用户故事**：作为用户，我希望动态广场页清晰展示动态内容和互动信息，以便浏览和参与社区互动。

#### 验收标准

1. THE System SHALL 在顶部显示标签切换（关注/推荐/话题）
2. THE System SHALL 提供排序筛选功能（最新/最热/话题筛选）
3. THE System SHALL 显示动态卡片，包含用户头像、昵称、关注按钮
4. THE System SHALL 在动态卡片中显示图片/视频内容（九宫格或单图）
5. THE System SHALL 在动态卡片中显示文字描述和话题标签
6. THE System SHALL 在动态卡片中显示互动数据（浏览、点赞、评论）
7. THE System SHALL 可选显示位置信息
8. THE System SHALL 使用紫色（#7C3AED）作为关注按钮和点赞的主色
9. THE System SHALL 使用浅紫色（#F3E8FF）作为标签背景

### 需求 13：订单页面重构

**用户故事**：作为用户，我希望订单页面清晰展示订单状态和信息，以便管理我的预约。

#### 验收标准

1. THE System SHALL 定义订单状态色彩：待确认（#FF9900）、待支付（#F97316）、已支付（#10B981）、已完成（#6B7280）、已取消（#EF4444）
2. THE System SHALL 在订单卡片顶部显示状态标签（带对应颜色）
3. THE System SHALL 显示服务人员信息（头像、姓名、服务类型）
4. THE System SHALL 显示服务日期和地点
5. THE System SHALL 显示价格信息（原价、优惠、实付）
6. THE System SHALL 根据订单状态显示对应的操作按钮
7. THE System SHALL 使用统一的卡片样式和间距

### 需求 14：购物车页面重构

**用户故事**：作为用户，我希望购物车页面清晰展示选中项目和费用明细，以便管理预约方案。

#### 验收标准

1. THE System SHALL 支持多选模式，允许批量操作
2. WHEN 存在档期冲突时，THE System SHALL 显示红色警告标识
3. THE System SHALL 实时计算并显示总价
4. THE System SHALL 支持保存多个预约方案
5. WHEN 项目被选中时，THE System SHALL 显示紫色边框和浅紫背景
6. WHEN 存在冲突时，THE System SHALL 使用红色图标和红色文字提示
7. THE System SHALL 在底部显示费用明细，使用玻璃态背景
8. THE System SHALL 确保费用明细固定在底部并适配安全区域

### 需求 15：装修系统集成

**用户故事**：作为管理员，我希望通过装修系统可视化配置页面，以便灵活调整页面布局和样式。

#### 验收标准

1. THE System SHALL 支持通过装修系统配置主题色（themeColor1、themeColor2、buttonColor）
2. THE System SHALL 支持通过装修系统配置导航栏样式（navigationBarColor、topTextColor）
3. THE System SHALL 支持通过装修系统配置页面背景（bg_type、bg_color、bg_image）
4. THE System SHALL 支持首页、个人中心、系统风格页面的装修配置
5. THE System SHALL 提供通用组件（search、banner、nav、middle-banner、quick-entry、notice-bar）
6. THE System SHALL 提供业务组件（staff-showcase、service-packages、portfolio-gallery、customer-reviews等）
7. THE System SHALL 提供用户中心组件（user-info、my-service、order-quick-entry、data-stats）
8. WHEN 装修配置更新时，THE System SHALL 实时应用新的样式和布局

### 需求 16：响应式适配

**用户故事**：作为用户，我希望应用在不同设备和平台上都能正常显示，以便获得良好的使用体验。

#### 验收标准

1. THE System SHALL 在手机端（< 768px）使用单列布局
2. THE System SHALL 在平板端（768px - 1024px）使用双列布局
3. THE System SHALL 在PC端（> 1024px）使用三列或更多列布局
4. THE System SHALL 在小程序、H5、App平台保持一致的视觉效果
5. THE System SHALL 适配不同屏幕的安全区域
6. THE System SHALL 确保触摸目标尺寸不小于88rpx
7. THE System SHALL 在所有平台测试并验证响应式布局

### 需求 17：动效系统实施

**用户故事**：作为用户，我希望界面具有流畅的动画效果，以便获得愉悦的交互体验。

#### 验收标准

1. THE System SHALL 为颜色和透明度变化使用快速过渡（0.15s ease）
2. THE System SHALL 为按钮和卡片hover使用标准过渡（0.2s ease）
3. THE System SHALL 为页面切换和大型元素使用慢速过渡（0.3s ease）
4. WHEN 用户悬停卡片时，THE System SHALL 向上移动4rpx并增强阴影
5. WHEN 用户点击按钮时，THE System SHALL 缩放至0.98并降低透明度至0.9
6. THE System SHALL 禁止使用无限循环的装饰性动画
7. THE System SHALL 禁止使用超过500ms的过渡时间
8. THE System SHALL 尊重用户的减少动画偏好（prefers-reduced-motion）

### 需求 18：无障碍设计实施

**用户故事**：作为有特殊需求的用户，我希望应用具有良好的无障碍性，以便我能够正常使用。

#### 验收标准

1. THE System SHALL 确保正文文字与背景的对比度达到4.5:1
2. THE System SHALL 确保大号文字与背景的对比度达到3:1
3. THE System SHALL 确保图标与背景的对比度达到3:1
4. THE System SHALL 确保触摸目标尺寸不小于88rpx × 88rpx
5. THE System SHALL 为所有图片提供alt文本
6. THE System SHALL 为表单输入框提供对应标签
7. THE System SHALL 确保颜色不是唯一的信息指示器
8. THE System SHALL 支持prefers-reduced-motion媒体查询

### 需求 19：性能优化

**用户故事**：作为用户，我希望应用加载快速且运行流畅，以便获得良好的使用体验。

#### 验收标准

1. THE System SHALL 为图片启用懒加载
2. THE System SHALL 在列表页使用缩略图，在详情页使用原图
3. THE System SHALL 为长列表使用虚拟滚动（z-paging）
4. THE System SHALL 避免过度的动画效果
5. THE System SHALL 正确应用CSS变量，避免不必要的重渲染
6. THE System SHALL 优化组件渲染性能
7. THE System SHALL 在所有平台测试并验证性能指标

### 需求 20：代码规范和文档

**用户故事**：作为开发者，我希望代码遵循统一的规范并有完善的文档，以便维护和扩展。

#### 验收标准

1. THE System SHALL 使用Vue 3 Composition API（<script setup lang="ts">）
2. THE System SHALL 使用PascalCase命名组件，kebab-case命名文件
3. THE System SHALL 使用Tailwind原子类优先，必要时使用SCSS
4. THE System SHALL 为所有可复用组件提供Props接口定义
5. THE System SHALL 为所有组件提供中文注释
6. THE System SHALL 在README中提供组件使用示例
7. THE System SHALL 提供设计系统文档，说明颜色、字体、间距等设计令牌的使用
