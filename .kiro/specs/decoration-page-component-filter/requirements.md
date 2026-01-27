# 需求文档

## 介绍

本文档定义了装修页面组件按页面类型过滤的功能需求。当前装修系统中，所有组件在添加时都显示在组件选择器中，无论当前编辑的是哪个页面（首页、个人中心等）。本需求旨在实现根据页面类型智能过滤可添加的组件，使每个页面只显示适合该页面的组件，同时支持通用组件在所有页面中显示。

## 术语表

- **Decoration_System**: 装修系统，允许管理员通过可视化界面配置页面布局和组件
- **Page_Type**: 页面类型，包括首页(HOME)、个人中心(USER)、客服设置(SERVICE)等
- **Widget**: 装修组件，可添加到页面中的可配置UI元素
- **Widget_Selector**: 组件选择器，用于选择和添加组件的弹窗界面
- **Page_Scope**: 页面范围，定义组件可以在哪些页面类型中使用
- **Universal_Widget**: 通用组件，可以在所有页面类型中使用的组件
- **Page_Specific_Widget**: 页面专属组件，只能在特定页面类型中使用的组件

## 需求

### 需求 1: 组件页面范围配置

**用户故事:** 作为系统开发者，我希望能够为每个组件配置其适用的页面范围，以便系统能够根据当前编辑的页面类型过滤可添加的组件。

#### 验收标准

1. WHEN 定义组件配置时 THEN THE Decoration_System SHALL 支持在组件options中添加pageScope属性
2. WHEN pageScope属性未定义时 THEN THE Decoration_System SHALL 将该组件视为Universal_Widget
3. WHEN pageScope属性定义为数组时 THEN THE Decoration_System SHALL 仅在指定的Page_Type中显示该组件
4. THE Decoration_System SHALL 支持以下Page_Type值: 'home'(首页), 'user'(个人中心), 'service'(客服设置)

### 需求 2: 组件选择器过滤逻辑

**用户故事:** 作为管理员，我希望在添加组件时只看到适合当前页面的组件，以便更高效地配置页面布局。

#### 验收标准

1. WHEN 管理员在首页装修中点击"添加组件"按钮 THEN THE Widget_Selector SHALL 仅显示pageScope包含'home'的组件和Universal_Widget
2. WHEN 管理员在个人中心装修中点击"添加组件"按钮 THEN THE Widget_Selector SHALL 仅显示pageScope包含'user'的组件和Universal_Widget
3. WHEN 管理员在客服设置中点击"添加组件"按钮 THEN THE Widget_Selector SHALL 仅显示pageScope包含'service'的组件和Universal_Widget
4. WHEN 组件的pageScope包含多个Page_Type THEN THE Widget_Selector SHALL 在所有指定的页面类型中显示该组件

### 需求 3: 现有组件分类配置

**用户故事:** 作为系统开发者，我需要为现有的所有组件配置正确的页面范围，以确保组件在合适的页面中显示。

#### 验收标准

1. THE Decoration_System SHALL 将以下组件配置为首页专属: search(搜索框), banner(轮播图), nav(导航菜单), middle-banner(中部轮播图), staff-showcase(人员推荐), service-packages(服务套餐), portfolio-gallery(案例作品), customer-reviews(客户评价), activity-zone(活动专区), order-quick-entry(订单快捷入口), news(最新资讯)
2. THE Decoration_System SHALL 将以下组件配置为个人中心专属: user-info(用户信息), my-service(我的服务), user-banner(用户轮播图)
3. THE Decoration_System SHALL 将以下组件配置为客服设置专属: customer-service(客服设置)
4. WHEN 未来添加新组件时 THEN THE Decoration_System SHALL 要求开发者明确指定pageScope或将其设为通用组件

### 需求 4: 向后兼容性

**用户故事:** 作为系统维护者，我希望新的过滤功能不会破坏现有的装修配置，以确保系统平滑升级。

#### 验收标准

1. WHEN 系统升级后 THEN THE Decoration_System SHALL 保持现有页面的装修配置不变
2. WHEN 已添加的组件不在当前页面的pageScope中 THEN THE Decoration_System SHALL 仍然正常显示和编辑该组件
3. WHEN 管理员编辑现有页面时 THEN THE Widget_Selector SHALL 根据新的过滤规则显示可添加的组件
4. THE Decoration_System SHALL 不需要数据库迁移或配置更新即可启用新功能

### 需求 5: 用户界面提示

**用户故事:** 作为管理员，我希望在组件选择器中能够清楚地了解哪些组件可用，以便更好地理解系统行为。

#### 验收标准

1. WHEN 组件选择器显示时 THEN THE Widget_Selector SHALL 仅显示适用于当前页面的组件
2. WHEN 组件已添加到当前页面时 THEN THE Widget_Selector SHALL 显示"已添加"状态并禁用点击
3. WHEN 组件选择器为空时 THEN THE Widget_Selector SHALL 显示"暂无可添加的组件"提示
4. THE Widget_Selector SHALL 保持现有的网格布局和交互方式
