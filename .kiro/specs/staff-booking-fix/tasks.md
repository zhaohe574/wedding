# Implementation Plan: 服务人员详情页预约功能修复

## Overview

修复服务人员详情页的"立即预约"按钮，使其导航到档期日历页面。修复了前端 request 工具的参数传递问题和服务器端的类型转换问题。

## Tasks

- [x] 1. 修改 handleBook 函数实现
  - 将 `uni.showToast` 调用替换为 `uni.navigateTo`
  - 传递 `staff_id` 参数到档期日历页面
  - 使用正确的页面路径: `/packages/pages/schedule_calendar/schedule_calendar`
  - 添加参数验证，确保 staffId 有效
  - _Requirements: 1.1, 1.2_

- [x] 2. 改进档期日历页面的错误处理
  - 在 onLoad 中添加 staffId 验证
  - 如果 staffId 无效，显示错误提示并返回上一页
  - _Requirements: 1.3_

- [x] 3. 修复 request 工具的参数传递问题
  - 在 requestInterceptorsHook 中添加 params 到 data 的转换
  - uni.request 使用 data 字段传递参数，而不是 params
  - 这是导致"请选择工作人员"错误的根本原因
  - _Requirements: 1.1, 1.2, 1.3_

- [x] 4. 修复服务器端的类型转换问题
  - 在 ScheduleLogic::getStaffSchedule 中将 staff_id 转换为 int 类型
  - 将 year 和 month 也转换为 int 类型
  - 修复 500 错误：Argument #1 ($staffId) must be of type int, string given
  - _Requirements: 1.1, 1.2, 1.3_

- [ ] 5. 测试和验证修复效果
  - 在服务人员详情页点击"立即预约"按钮
  - 确认成功跳转到档期日历页面
  - 确认档期日历页面显示正确的服务人员信息
  - 确认可以正常选择日期和加入购物车
  - _Requirements: 1.1, 1.2, 1.3_

## Notes

### 问题1：参数未传递到服务器
**原因**：uni-app 的 `uni.request` 使用 `data` 字段传递参数，但前端代码使用 `params` 字段
**修复**：在 request 工具的 `requestInterceptorsHook` 中添加 `params` 到 `data` 的转换

### 问题2：服务器端类型错误
**原因**：GET 请求的参数默认是字符串类型，但服务器端方法期望 int 类型
**错误信息**：`Argument #1 ($staffId) must be of type int, string given`
**修复**：在 `ScheduleLogic::getStaffSchedule` 方法中显式转换参数类型

### 修复内容
1. ✅ 服务人员详情页：添加预约跳转逻辑和参数验证
2. ✅ 档期日历页面：添加错误处理和自动返回
3. ✅ Request 工具：添加 params 到 data 的转换逻辑
4. ✅ 服务器端：添加参数类型转换（string → int）

### 影响范围
- 前端修复会影响所有使用 `request.get({ url, params })` 的 API 调用
- 服务器端修复只影响 `ScheduleLogic::getStaffSchedule` 方法
