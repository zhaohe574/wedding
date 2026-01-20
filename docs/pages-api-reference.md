# 婚庆服务预约系统 - 页面与API接口文档

> 更新日期: 2026-01-19
> 版本: v1.0 (Phase 1 & Phase 2)

---

## 目录

1. [数据库表结构](#一数据库表结构)
2. [后端API接口](#二后端api接口)
3. [管理后台页面](#三管理后台页面)
4. [小程序端页面](#四小程序端页面)
5. [核心服务](#五核心服务)

---

## 一、数据库表结构

### Phase 1 - 工作人员与服务分类 (001_create_staff_tables.sql)

| 表名 | 说明 | 主要字段 |
|------|------|----------|
| `la_service_category` | 服务分类表 | id, parent_id, name, icon, sort, is_show |
| `la_style_tag` | 风格标签表 | id, name, type(1风格/2技能/3其他), sort |
| `la_service_package` | 服务套餐表 | id, category_id, name, price, content(JSON) |
| `la_staff` | 工作人员表 | id, category_id, name, mobile, avatar, price, rating, order_count |
| `la_staff_work` | 作品集表 | id, staff_id, title, images(JSON), type, is_cover |
| `la_staff_certificate` | 资质证书表 | id, staff_id, name, image, verify_status |
| `la_staff_tag` | 人员标签关联表 | id, staff_id, tag_id |
| `la_staff_package` | 人员套餐关联表 | id, staff_id, package_id, price |
| `la_favorite` | 收藏表 | id, user_id, staff_id |

### Phase 2 - 档期管理与购物车 (002_create_schedule_cart_tables.sql)

| 表名 | 说明 | 主要字段 |
|------|------|----------|
| `la_schedule_rule` | 档期规则表 | id, staff_id, advance_days, max_orders_per_day, rest_days |
| `la_schedule` | 档期表 | id, staff_id, schedule_date, time_slot, status, version(乐观锁) |
| `la_schedule_lock` | 档期锁定记录表 | id, schedule_id, lock_type, lock_reason, status |
| `la_schedule_share` | 档期共享表 | id, group_name, staff_ids, share_type, discount_rate |
| `la_cart` | 购物车表 | id, user_id, staff_id, schedule_date, time_slot, price |
| `la_cart_plan` | 购物车方案表 | id, user_id, plan_name, cart_ids(JSON), total_price |
| `la_waitlist` | 候补订单表 | id, user_id, staff_id, schedule_date, notify_status |
| `la_calendar_event` | 黄历/吉日表 | id, event_date, lunar_date, is_lucky_day, lucky_events |

---

## 二、后端API接口

### 2.1 管理后台API (adminapi)

#### 工作人员管理 `/staff.staff/*`

| 接口 | 方法 | 说明 | 文件位置 |
|------|------|------|----------|
| `/staff.staff/lists` | GET | 工作人员列表 | `controller/staff/StaffController.php` |
| `/staff.staff/detail` | GET | 工作人员详情 | |
| `/staff.staff/add` | POST | 添加工作人员 | |
| `/staff.staff/edit` | POST | 编辑工作人员 | |
| `/staff.staff/delete` | POST | 删除工作人员 | |
| `/staff.staff/changeStatus` | POST | 修改状态 | |
| `/staff.staff/all` | GET | 获取所有(下拉) | |
| `/staff.staff/statistics` | GET | 统计数据 | |

#### 作品管理 `/staff.staffWork/*`

| 接口 | 方法 | 说明 |
|------|------|------|
| `/staff.staffWork/lists` | GET | 作品列表 |
| `/staff.staffWork/detail` | GET | 作品详情 |
| `/staff.staffWork/add` | POST | 添加作品 |
| `/staff.staffWork/edit` | POST | 编辑作品 |
| `/staff.staffWork/delete` | POST | 删除作品 |
| `/staff.staffWork/setCover` | POST | 设为封面 |

#### 资质证书 `/staff.staffCertificate/*`

| 接口 | 方法 | 说明 |
|------|------|------|
| `/staff.staffCertificate/lists` | GET | 证书列表 |
| `/staff.staffCertificate/add` | POST | 添加证书 |
| `/staff.staffCertificate/edit` | POST | 编辑证书 |
| `/staff.staffCertificate/delete` | POST | 删除证书 |
| `/staff.staffCertificate/audit` | POST | 审核证书 |

#### 服务分类 `/service.category/*`

| 接口 | 方法 | 说明 |
|------|------|------|
| `/service.category/lists` | GET | 分类列表 |
| `/service.category/tree` | GET | 分类树 |
| `/service.category/add` | POST | 添加分类 |
| `/service.category/edit` | POST | 编辑分类 |
| `/service.category/delete` | POST | 删除分类 |

#### 服务套餐 `/service.package/*`

| 接口 | 方法 | 说明 |
|------|------|------|
| `/service.package/lists` | GET | 套餐列表 |
| `/service.package/detail` | GET | 套餐详情 |
| `/service.package/add` | POST | 添加套餐 |
| `/service.package/edit` | POST | 编辑套餐 |
| `/service.package/delete` | POST | 删除套餐 |

#### 风格标签 `/service.styleTag/*`

| 接口 | 方法 | 说明 |
|------|------|------|
| `/service.styleTag/lists` | GET | 标签列表 |
| `/service.styleTag/add` | POST | 添加标签 |
| `/service.styleTag/edit` | POST | 编辑标签 |
| `/service.styleTag/delete` | POST | 删除标签 |
| `/service.styleTag/typeOptions` | GET | 标签类型选项 |

#### 档期管理 `/schedule.schedule/*`

| 接口 | 方法 | 说明 | 文件位置 |
|------|------|------|----------|
| `/schedule.schedule/lists` | GET | 档期列表 | `controller/schedule/ScheduleController.php` |
| `/schedule.schedule/monthCalendar` | GET | 月度日历视图 | |
| `/schedule.schedule/detail` | GET | 档期详情 | |
| `/schedule.schedule/setStatus` | POST | 设置档期状态 | |
| `/schedule.schedule/batchSet` | POST | 批量设置档期 | |
| `/schedule.schedule/lock` | POST | 锁定档期 | |
| `/schedule.schedule/unlock` | POST | 释放锁定 | |
| `/schedule.schedule/reserve` | POST | 内部预留 | |
| `/schedule.schedule/lockRecords` | GET | 锁定记录 | |
| `/schedule.schedule/timeSlotOptions` | GET | 时间段选项 | |
| `/schedule.schedule/statusOptions` | GET | 状态选项 | |
| `/schedule.schedule/statistics` | GET | 档期统计 | |

#### 档期规则 `/schedule.scheduleRule/*`

| 接口 | 方法 | 说明 |
|------|------|------|
| `/schedule.scheduleRule/lists` | GET | 规则列表 |
| `/schedule.scheduleRule/detail` | GET | 规则详情 |
| `/schedule.scheduleRule/add` | POST | 添加规则 |
| `/schedule.scheduleRule/edit` | POST | 编辑规则 |
| `/schedule.scheduleRule/delete` | POST | 删除规则 |
| `/schedule.scheduleRule/changeStatus` | POST | 切换启用状态 |
| `/schedule.scheduleRule/globalRule` | GET | 全局规则 |
| `/schedule.scheduleRule/staffRule` | GET | 工作人员规则 |

#### 日历事件(黄历) `/schedule.calendarEvent/*`

| 接口 | 方法 | 说明 |
|------|------|------|
| `/schedule.calendarEvent/lists` | GET | 事件列表 |
| `/schedule.calendarEvent/monthCalendar` | GET | 月度日历 |
| `/schedule.calendarEvent/detail` | GET | 事件详情 |
| `/schedule.calendarEvent/add` | POST | 添加事件 |
| `/schedule.calendarEvent/edit` | POST | 编辑事件 |
| `/schedule.calendarEvent/delete` | POST | 删除事件 |
| `/schedule.calendarEvent/luckyDays` | GET | 吉日列表 |
| `/schedule.calendarEvent/holidays` | GET | 节假日列表 |
| `/schedule.calendarEvent/batchImport` | POST | 批量导入 |

---

### 2.2 小程序端API (api)

#### 工作人员 `/staff/*`

| 接口 | 方法 | 说明 | 需登录 | 文件位置 |
|------|------|------|--------|----------|
| `/staff/lists` | GET | 工作人员列表 | 否 | `controller/StaffController.php` |
| `/staff/detail` | GET | 工作人员详情 | 否 | |
| `/staff/works` | GET | 作品列表 | 否 | |
| `/staff/recommend` | GET | 推荐人员 | 否 | |
| `/staff/toggleFavorite` | POST | 收藏/取消 | 是 | |
| `/staff/myFavorites` | GET | 我的收藏 | 是 | |

#### 服务 `/service/*`

| 接口 | 方法 | 说明 | 需登录 |
|------|------|------|--------|
| `/service/categories` | GET | 服务分类 | 否 |
| `/service/packages` | GET | 服务套餐 | 否 |
| `/service/tags` | GET | 风格标签 | 否 |

#### 档期 `/schedule/*`

| 接口 | 方法 | 说明 | 需登录 | 文件位置 |
|------|------|------|--------|----------|
| `/schedule/staffSchedule` | GET | 工作人员档期 | 否 | `controller/ScheduleController.php` |
| `/schedule/monthCalendar` | GET | 月度日历 | 否 | |
| `/schedule/luckyDays` | GET | 吉日列表 | 否 | |
| `/schedule/checkAvailable` | GET | 检查可预约 | 否 | |
| `/schedule/lockSchedule` | POST | 锁定档期 | 是 | |
| `/schedule/releaseLock` | POST | 释放锁定 | 是 | |
| `/schedule/joinWaitlist` | POST | 加入候补 | 是 | |
| `/schedule/myWaitlist` | GET | 我的候补 | 是 | |
| `/schedule/cancelWaitlist` | POST | 取消候补 | 是 | |

#### 购物车 `/cart/*`

| 接口 | 方法 | 说明 | 需登录 | 文件位置 |
|------|------|------|--------|----------|
| `/cart/lists` | GET | 购物车列表 | 是 | `controller/CartController.php` |
| `/cart/add` | POST | 添加到购物车 | 是 | |
| `/cart/update` | POST | 更新购物车项 | 是 | |
| `/cart/delete` | POST | 删除购物车项 | 是 | |
| `/cart/batchDelete` | POST | 批量删除 | 是 | |
| `/cart/toggleSelect` | POST | 切换选中 | 是 | |
| `/cart/selectAll` | POST | 全选/取消 | 是 | |
| `/cart/calculate` | GET | 计算总价 | 是 | |
| `/cart/checkConflicts` | GET | 检查冲突 | 是 | |
| `/cart/clear` | POST | 清空购物车 | 是 | |
| `/cart/count` | GET | 购物车数量 | 是 | |
| `/cart/generateShareCode` | POST | 生成分享码 | 是 | |
| `/cart/getByShareCode` | GET | 分享码获取 | 否 | |
| `/cart/savePlan` | POST | 保存方案 | 是 | |
| `/cart/myPlans` | GET | 我的方案 | 是 | |
| `/cart/planDetail` | GET | 方案详情 | 是 | |
| `/cart/deletePlan` | POST | 删除方案 | 是 | |
| `/cart/setDefaultPlan` | POST | 设为默认 | 是 | |
| `/cart/copyPlanByShareCode` | POST | 复制分享方案 | 是 | |
| `/cart/comparePlans` | GET | 比较方案 | 是 | |

---

## 三、管理后台页面

### 3.1 页面路由

| 路由路径 | 页面名称 | 文件位置 |
|----------|----------|----------|
| `/staff/lists` | 工作人员列表 | `views/staff/lists/index.vue` |
| `/staff/lists/edit` | 工作人员编辑 | `views/staff/lists/edit.vue` |
| `/service/category` | 服务分类管理 | `views/service/category/index.vue` |
| `/service/package` | 服务套餐管理 | `views/service/package/index.vue` |
| `/service/tag` | 风格标签管理 | `views/service/tag/index.vue` |
| `/schedule/calendar` | 档期日历管理 | `views/schedule/calendar/index.vue` |
| `/schedule/rule` | 档期规则管理 | `views/schedule/rule/index.vue` |
| `/schedule/event` | 日历事件管理 | `views/schedule/event/index.vue` |

### 3.2 API接口文件

| 文件路径 | 说明 |
|----------|------|
| `src/api/staff.ts` | 工作人员相关API |
| `src/api/service.ts` | 服务分类/套餐/标签API |
| `src/api/schedule.ts` | 档期管理相关API |

### 3.3 页面功能说明

#### 工作人员列表 (`/staff/lists`)
- 统计卡片：总数、启用、禁用、推荐数
- 搜索筛选：姓名、手机号、分类、状态
- 列表展示：头像、姓名、分类、价格、评分、订单数
- 操作：新增、编辑、删除、修改状态

#### 工作人员编辑 (`/staff/lists/edit`)
- Tab1 基本信息：姓名、手机、头像、分类、价格等
- Tab2 标签设置：风格标签多选
- Tab3 套餐关联：关联服务套餐及价格覆盖

#### 档期日历管理 (`/schedule/calendar`)
- 月度日历视图展示
- 工作人员筛选
- 批量设置档期状态
- 锁定/释放/预留操作
- 吉日、节假日标记显示

#### 档期规则管理 (`/schedule/rule`)
- 全局规则配置
- 个人规则配置
- 提前预约天数、单日最大接单、休息日设置

#### 日历事件管理 (`/schedule/event`)
- 黄历数据管理
- 吉日/节假日标记
- 批量导入功能

---

## 四、小程序端页面

### 4.1 页面路由配置 (pages.json)

#### 主包页面 (pages/)
| 路由 | 页面名称 | 需登录 |
|------|----------|--------|
| `pages/index/index` | 首页 | 否 |
| `pages/user/user` | 个人中心 | 否 |

#### 分包页面 (packages/pages/)
| 路由 | 页面名称 | 需登录 | 文件位置 |
|------|----------|--------|----------|
| `packages/pages/staff_list/staff_list` | 服务人员列表 | 否 | `staff_list.vue` |
| `packages/pages/staff_detail/staff_detail` | 人员详情 | 否 | `staff_detail.vue` |
| `packages/pages/staff_favorite/staff_favorite` | 我的收藏 | 是 | `staff_favorite.vue` |
| `packages/pages/schedule_calendar/schedule_calendar` | 选择档期 | 否 | `schedule_calendar.vue` |
| `packages/pages/cart/cart` | 购物车 | 是 | `cart.vue` |
| `packages/pages/cart_plan/cart_plan` | 我的方案 | 是 | `cart_plan.vue` |
| `packages/pages/waitlist/waitlist` | 我的候补 | 是 | `waitlist.vue` |

### 4.2 API接口文件

| 文件路径 | 说明 |
|----------|------|
| `src/api/staff.ts` | 工作人员相关API |
| `src/api/service.ts` | 服务分类/套餐/标签API |
| `src/api/schedule.ts` | 档期相关API |
| `src/api/cart.ts` | 购物车相关API |

### 4.3 页面功能说明

#### 服务人员列表 (`staff_list`)
- 分类筛选Tab
- 排序选项：综合、价格、评分、销量
- z-paging分页加载
- 收藏按钮（需登录）

#### 人员详情 (`staff_detail`)
- 顶部轮播Banner
- 基本信息卡片
- 风格标签展示
- 服务套餐表格
- 作品集Grid
- 资质证书展示
- 底部操作栏：收藏、预约

#### 选择档期 (`schedule_calendar`)
- 工作人员信息展示
- 月份切换
- 日历网格（含吉日、节假日标记）
- 时间段选择（全天/上午/下午/晚上）
- 加入购物车/加入候补

#### 购物车 (`cart`)
- 空状态提示
- 冲突项提醒
- 全选/取消全选
- 购物车项列表（含档期可用状态检测）
- 保存方案/我的方案/清空
- 底部结算栏

#### 我的方案 (`cart_plan`)
- 方案列表展示
- 设为默认
- 分享方案
- 应用方案
- 删除方案

#### 我的候补 (`waitlist`)
- 状态筛选Tab：全部/等待中/已通知/已下单/已过期
- 候补项列表
- 立即预约（已通知时）
- 取消候补

---

## 五、核心服务

### 5.1 Model层文件

```
server/app/common/model/
├── service/
│   ├── ServiceCategory.php    # 服务分类
│   ├── ServicePackage.php     # 服务套餐
│   └── StyleTag.php           # 风格标签
├── staff/
│   ├── Staff.php              # 工作人员
│   ├── StaffWork.php          # 作品集
│   ├── StaffCertificate.php   # 资质证书
│   ├── StaffTag.php           # 人员标签
│   ├── StaffPackage.php       # 人员套餐
│   └── Favorite.php           # 收藏
├── schedule/
│   ├── Schedule.php           # 档期 (含乐观锁+Redis分布式锁)
│   ├── ScheduleRule.php       # 档期规则
│   ├── ScheduleLock.php       # 锁定记录
│   ├── ScheduleShare.php      # 档期共享
│   ├── Waitlist.php           # 候补订单
│   └── CalendarEvent.php      # 黄历/吉日
└── cart/
    ├── Cart.php               # 购物车
    └── CartPlan.php           # 购物车方案
```

### 5.2 Service层文件

| 文件 | 说明 | 核心功能 |
|------|------|----------|
| `RedisLockService.php` | Redis分布式锁 | 档期防超卖 |

#### RedisLockService 核心方法

```php
// 获取锁
RedisLockService::acquire($key, $timeout, $retryCount, $retryDelay)

// 释放锁
RedisLockService::release($key, $token)

// 带锁执行档期锁定
RedisLockService::lockScheduleWithRedis($staffId, $date, $timeSlot, $userId, $callback)

// 批量锁定档期
RedisLockService::batchLockSchedules($schedules, $userId, $callback)
```

### 5.3 防超卖机制

系统采用 **乐观锁 + Redis分布式锁** 双重保障：

1. **乐观锁**: 数据库`la_schedule`表`version`字段
   - 更新时检查版本号，防止并发覆盖

2. **Redis分布式锁**: `RedisLockService`
   - 锁定期间其他请求排队等待
   - 支持自动过期、重试机制
   - Lua脚本保证原子性释放

```php
// 使用示例
Schedule::lockScheduleWithRedis($staffId, $date, $timeSlot, $userId);
```

---

## 附录：文件清单

### Phase 1 新增文件 (共42个)

<details>
<summary>点击展开</summary>

**SQL**
- `server/sql/wedding/001_create_staff_tables.sql`

**Model (10个)**
- `server/app/common/model/service/ServiceCategory.php`
- `server/app/common/model/service/ServicePackage.php`
- `server/app/common/model/service/StyleTag.php`
- `server/app/common/model/staff/Staff.php`
- `server/app/common/model/staff/StaffWork.php`
- `server/app/common/model/staff/StaffCertificate.php`
- `server/app/common/model/staff/StaffTag.php`
- `server/app/common/model/staff/StaffPackage.php`
- `server/app/common/model/staff/Favorite.php`

**Admin API (20个)**
- Controller/Logic/Lists/Validate for Staff, StaffWork, StaffCertificate
- Controller/Logic/Lists/Validate for Category, Package, StyleTag

**小程序API (6个)**
- `server/app/api/controller/StaffController.php`
- `server/app/api/controller/ServiceController.php`
- `server/app/api/logic/StaffLogic.php`
- `server/app/api/logic/ServiceLogic.php`
- `server/app/api/lists/staff/StaffLists.php`

**Admin Vue (5个)**
- `admin/src/api/staff.ts`
- `admin/src/api/service.ts`
- `admin/src/views/staff/lists/index.vue`
- `admin/src/views/staff/lists/edit.vue`
- `admin/src/views/service/category/index.vue`
- `admin/src/views/service/package/index.vue`
- `admin/src/views/service/tag/index.vue`

**UniApp (6个)**
- `uniapp/src/api/staff.ts`
- `uniapp/src/api/service.ts`
- `uniapp/src/packages/pages/staff_list/staff_list.vue`
- `uniapp/src/packages/pages/staff_detail/staff_detail.vue`
- `uniapp/src/packages/pages/staff_favorite/staff_favorite.vue`

</details>

### Phase 2 新增文件 (共35个)

<details>
<summary>点击展开</summary>

**SQL**
- `server/sql/wedding/002_create_schedule_cart_tables.sql`

**Model (8个)**
- `server/app/common/model/schedule/Schedule.php`
- `server/app/common/model/schedule/ScheduleRule.php`
- `server/app/common/model/schedule/ScheduleLock.php`
- `server/app/common/model/schedule/ScheduleShare.php`
- `server/app/common/model/schedule/Waitlist.php`
- `server/app/common/model/schedule/CalendarEvent.php`
- `server/app/common/model/cart/Cart.php`
- `server/app/common/model/cart/CartPlan.php`

**Service (1个)**
- `server/app/common/service/RedisLockService.php`

**Admin API (12个)**
- Controller/Logic/Lists/Validate for Schedule
- Controller/Logic/Lists/Validate for ScheduleRule
- Controller/Logic/Lists/Validate for CalendarEvent

**小程序API (6个)**
- `server/app/api/controller/ScheduleController.php`
- `server/app/api/controller/CartController.php`
- `server/app/api/logic/ScheduleLogic.php`
- `server/app/api/logic/CartLogic.php`
- `server/app/api/validate/ScheduleValidate.php`
- `server/app/api/validate/CartValidate.php`

**Admin Vue (4个)**
- `admin/src/api/schedule.ts`
- `admin/src/views/schedule/calendar/index.vue`
- `admin/src/views/schedule/rule/index.vue`
- `admin/src/views/schedule/event/index.vue`

**UniApp (6个)**
- `uniapp/src/api/schedule.ts`
- `uniapp/src/api/cart.ts`
- `uniapp/src/packages/pages/schedule_calendar/schedule_calendar.vue`
- `uniapp/src/packages/pages/cart/cart.vue`
- `uniapp/src/packages/pages/cart_plan/cart_plan.vue`
- `uniapp/src/packages/pages/waitlist/waitlist.vue`

</details>

---

> 文档结束
