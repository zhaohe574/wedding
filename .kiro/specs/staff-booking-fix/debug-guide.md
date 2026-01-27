# 调试指南

## 问题描述
点击"立即预约"按钮后，跳转到档期日历页面，但显示"请选择工作人员"错误。

## 已实施的修复

### 1. 服务人员详情页 (staff_detail.vue)
- 添加了 staffId 验证
- 添加了调试日志
- 在跳转前检查 staffId 是否有效

### 2. 档期日历页面 (schedule_calendar.vue)
- 添加了参数验证
- 添加了错误处理（无效时自动返回）
- 添加了调试日志

## 调试步骤

### 1. 打开浏览器开发者工具
- 按 F12 或右键 -> 检查
- 切换到 Console 标签页

### 2. 进入服务人员详情页
- 确保URL中有正确的 id 参数
- 例如：`/packages/pages/staff_detail/staff_detail?id=1`

### 3. 点击"立即预约"按钮
- 查看控制台输出的日志

### 4. 分析日志输出

#### 预期的正常日志：
```
handleBook called, staffId: 1
Navigating to: /packages/pages/schedule_calendar/schedule_calendar?staff_id=1
schedule_calendar onLoad, options: {staff_id: "1"}
Parsed staffId: 1
```

#### 可能的异常情况：

**情况 A：staffId 为 0 或 undefined**
```
handleBook called, staffId: 0
// 或
handleBook called, staffId: undefined
```
**原因**：服务人员详情页没有正确加载 staffId
**解决方案**：检查详情页的 URL 参数是否包含 id

**情况 B：参数传递失败**
```
handleBook called, staffId: 1
Navigating to: /packages/pages/schedule_calendar/schedule_calendar?staff_id=1
schedule_calendar onLoad, options: {}
Parsed staffId: 0
```
**原因**：URL 参数没有正确传递到目标页面
**解决方案**：检查 uni-app 的路由配置

**情况 C：参数解析失败**
```
handleBook called, staffId: 1
Navigating to: /packages/pages/schedule_calendar/schedule_calendar?staff_id=1
schedule_calendar onLoad, options: {staff_id: "1"}
Parsed staffId: NaN
```
**原因**：参数解析出错
**解决方案**：检查 parseInt 的使用

## 常见问题排查

### Q1: 如何确认服务人员详情页的 staffId 是否正确？
在服务人员详情页的 onLoad 函数中添加日志：
```javascript
onLoad((options) => {
    console.log('staff_detail onLoad, options:', options)
    if (options?.id) {
        staffId.value = Number(options.id)
        console.log('staffId set to:', staffId.value)
        getDetail()
    }
})
```

### Q2: 如何确认 URL 参数是否正确传递？
检查浏览器地址栏或开发者工具的 Network 标签页，确认跳转的 URL 是否包含 `staff_id` 参数。

### Q3: 如果所有日志都正常，但仍然显示错误？
可能是服务器端返回的错误。检查 Network 标签页中的 API 请求，查看 `/schedule/staffSchedule` 接口的请求参数和响应。

## 下一步行动

请按照上述步骤进行调试，并提供：
1. 控制台的完整日志输出
2. 浏览器地址栏的 URL
3. Network 标签页中相关 API 请求的详情（如果有）

这些信息将帮助我们准确定位问题并提供解决方案。
