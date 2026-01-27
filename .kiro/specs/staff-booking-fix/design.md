# Design Document

## Overview

修复服务人员详情页的预约功能，将"立即预约"按钮连接到已实现的档期日历页面。这是一个简单的导航修复，不需要新增功能。

## Architecture

使用uni-app的页面导航机制（`uni.navigateTo`），将用户从服务人员详情页导航到档期日历页面，并传递服务人员ID作为URL参数。

## Components and Interfaces

### 修改的组件

**staff_detail.vue**
- 位置: `uniapp/src/packages/pages/staff_detail/staff_detail.vue`
- 修改函数: `handleBook()`
- 当前实现: 显示"预约功能开发中"提示
- 新实现: 导航到档期日历页面

### 目标页面

**schedule_calendar.vue**
- 位置: `uniapp/src/packages/pages/schedule_calendar/schedule_calendar.vue`
- 接收参数: `staff_id` (通过URL query参数)
- 已实现功能: 显示档期日历、选择日期时间、加入购物车

## Data Models

无需修改数据模型。使用现有的服务人员ID（`staffId`）作为导航参数。

## Correctness Properties

*属性是关于系统应该保持为真的特征或行为的形式化陈述，本质上是关于系统应该做什么的形式化声明。*

### Property 1: 导航参数传递

*For any* 有效的服务人员ID，当用户点击"立即预约"按钮时，系统应该导航到档期日历页面并传递该ID作为参数

**Validates: Requirements 1.1, 1.2**

### Property 2: 页面加载验证

*For any* 从详情页导航到档期日历页面的操作，档期日历页面应该能够接收并使用传递的服务人员ID来加载对应的档期数据

**Validates: Requirements 1.3**

## Error Handling

- 如果服务人员ID无效或缺失，档期日历页面应该显示错误提示（已在现有代码中实现）
- 导航失败时，uni-app框架会自动处理错误

## Testing Strategy

### Unit Tests

- 测试 `handleBook()` 函数是否正确调用 `uni.navigateTo`
- 测试导航URL是否包含正确的服务人员ID参数

### Manual Testing

- 在服务人员详情页点击"立即预约"按钮
- 验证是否成功跳转到档期日历页面
- 验证档期日历页面是否显示正确的服务人员信息
- 验证可以正常选择日期和时间段
- 验证可以正常加入购物车
