# Requirements Document

## Introduction

修复服务人员详情页的预约功能。当前点击"立即预约"按钮时显示"预约功能开发中"的提示，但实际上档期日历页面已经实现。需要将预约按钮连接到档期日历页面。

## Glossary

- **Staff_Detail_Page**: 服务人员详情页面，显示服务人员的详细信息
- **Schedule_Calendar_Page**: 档期日历页面，用于选择预约日期和时间段
- **Booking_Button**: 详情页底部的"立即预约"按钮

## Requirements

### Requirement 1: 预约按钮导航

**User Story:** 作为用户，我想在服务人员详情页点击"立即预约"按钮时跳转到档期日历页面，以便我可以选择预约日期和时间。

#### Acceptance Criteria

1. WHEN 用户在服务人员详情页点击"立即预约"按钮 THEN THE System SHALL 导航到档期日历页面
2. WHEN 导航到档期日历页面 THEN THE System SHALL 传递当前服务人员的ID作为参数
3. WHEN 档期日历页面加载 THEN THE System SHALL 显示对应服务人员的档期信息
