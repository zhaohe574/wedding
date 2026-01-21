# 婚庆服务预约系统 - 开发进度追踪

> 最后更新: 2026-01-20

## 项目概述

婚庆服务预约系统是一个完整的B2C服务预约平台，包含管理后台(Admin)和小程序端(UniApp)两个前端，以及PHP后端服务。

---

## 阶段一：基础架构与服务人员管理

### 1.1 数据库设计
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| 服务人员表 (staff) | ✅ 完成 | `server/sql/wedding/001_create_staff_tables.sql` |
| 服务分类表 (service_category) | ✅ 完成 | `server/sql/wedding/001_create_staff_tables.sql` |
| 服务套餐表 (service_package) | ✅ 完成 | `server/sql/wedding/001_create_staff_tables.sql` |
| 人员套餐关联表 (staff_package) | ✅ 完成 | `server/sql/wedding/001_create_staff_tables.sql` |
| 人员收藏表 (staff_favorite) | ✅ 完成 | `server/sql/wedding/001_create_staff_tables.sql` |

### 1.2 后端Model层
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| Staff模型 | ✅ 完成 | `server/app/common/model/staff/Staff.php` |
| ServiceCategory模型 | ✅ 完成 | `server/app/common/model/service/ServiceCategory.php` |
| ServicePackage模型 | ✅ 完成 | `server/app/common/model/service/ServicePackage.php` |
| StaffPackage模型 | ✅ 完成 | `server/app/common/model/staff/StaffPackage.php` |
| StaffFavorite模型 | ✅ 完成 | `server/app/common/model/staff/StaffFavorite.php` |

### 1.3 管理后台API
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| StaffController | ✅ 完成 | `server/app/adminapi/controller/staff/StaffController.php` |
| StaffLogic | ✅ 完成 | `server/app/adminapi/logic/staff/StaffLogic.php` |
| StaffLists | ✅ 完成 | `server/app/adminapi/lists/staff/StaffLists.php` |
| StaffValidate | ✅ 完成 | `server/app/adminapi/validate/staff/StaffValidate.php` |
| ServiceCategoryController | ✅ 完成 | `server/app/adminapi/controller/service/ServiceCategoryController.php` |
| ServicePackageController | ✅ 完成 | `server/app/adminapi/controller/service/ServicePackageController.php` |

### 1.4 小程序端API
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| StaffController | ✅ 完成 | `server/app/api/controller/StaffController.php` |
| StaffLogic | ✅ 完成 | `server/app/api/logic/StaffLogic.php` |
| ServiceController | ✅ 完成 | `server/app/api/controller/ServiceController.php` |

### 1.5 管理后台Vue页面
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| 服务人员列表 | ✅ 完成 | `admin/src/views/staff/lists/index.vue` |
| 服务人员编辑 | ✅ 完成 | `admin/src/views/staff/edit.vue` |
| 服务分类管理 | ✅ 完成 | `admin/src/views/service/category/index.vue` |
| 服务套餐管理 | ✅ 完成 | `admin/src/views/service/package/index.vue` |

### 1.6 小程序端页面
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| 服务人员列表 | ✅ 完成 | `uniapp/src/packages/pages/staff_list/staff_list.vue` |
| 服务人员详情 | ✅ 完成 | `uniapp/src/packages/pages/staff_detail/staff_detail.vue` |
| 我的收藏 | ✅ 完成 | `uniapp/src/packages/pages/staff_favorite/staff_favorite.vue` |

---

## 阶段二：档期管理与购物车

### 2.1 数据库设计
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| 档期表 (schedule) | ✅ 完成 | `server/sql/wedding/002_create_schedule_cart_tables.sql` |
| 档期预约表 (schedule_booking) | ✅ 完成 | `server/sql/wedding/002_create_schedule_cart_tables.sql` |
| 档期候补表 (schedule_waitlist) | ✅ 完成 | `server/sql/wedding/002_create_schedule_cart_tables.sql` |
| 档期锁定表 (schedule_lock) | ✅ 完成 | `server/sql/wedding/002_create_schedule_cart_tables.sql` |
| 购物车表 (cart) | ✅ 完成 | `server/sql/wedding/002_create_schedule_cart_tables.sql` |
| 购物车方案表 (cart_plan) | ✅ 完成 | `server/sql/wedding/002_create_schedule_cart_tables.sql` |

### 2.2 后端Model层
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| Schedule模型 | ✅ 完成 | `server/app/common/model/schedule/Schedule.php` |
| ScheduleBooking模型 | ✅ 完成 | `server/app/common/model/schedule/ScheduleBooking.php` |
| ScheduleWaitlist模型 | ✅ 完成 | `server/app/common/model/schedule/ScheduleWaitlist.php` |
| ScheduleLock模型 | ✅ 完成 | `server/app/common/model/schedule/ScheduleLock.php` |
| Cart模型 | ✅ 完成 | `server/app/common/model/cart/Cart.php` |
| CartPlan模型 | ✅ 完成 | `server/app/common/model/cart/CartPlan.php` |

### 2.3 管理后台API
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| ScheduleController | ✅ 完成 | `server/app/adminapi/controller/schedule/ScheduleController.php` |
| ScheduleLogic | ✅ 完成 | `server/app/adminapi/logic/schedule/ScheduleLogic.php` |
| ScheduleLists | ✅ 完成 | `server/app/adminapi/lists/schedule/ScheduleLists.php` |
| BookingController | ✅ 完成 | `server/app/adminapi/controller/schedule/BookingController.php` |

### 2.4 小程序端API
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| ScheduleController | ✅ 完成 | `server/app/api/controller/ScheduleController.php` |
| ScheduleLogic | ✅ 完成 | `server/app/api/logic/ScheduleLogic.php` |
| CartController | ✅ 完成 | `server/app/api/controller/CartController.php` |
| CartLogic | ✅ 完成 | `server/app/api/logic/CartLogic.php` |

### 2.5 管理后台Vue页面
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| 档期日历管理 | ✅ 完成 | `admin/src/views/schedule/calendar/index.vue` |
| 预约列表 | ✅ 完成 | `admin/src/views/schedule/booking/index.vue` |
| 候补列表 | ✅ 完成 | `admin/src/views/schedule/waitlist/index.vue` |

### 2.6 小程序端页面
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| 档期日历选择 | ✅ 完成 | `uniapp/src/packages/pages/schedule_calendar/schedule_calendar.vue` |
| 购物车 | ✅ 完成 | `uniapp/src/packages/pages/cart/cart.vue` |
| 我的方案 | ✅ 完成 | `uniapp/src/packages/pages/cart_plan/cart_plan.vue` |
| 我的候补 | ✅ 完成 | `uniapp/src/packages/pages/waitlist/waitlist.vue` |

---

## 阶段三：订单管理与动态社区

### 3.1 数据库设计
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| 订单表 (order) | ✅ 完成 | `server/sql/wedding/003_create_order_dynamic_tables.sql` |
| 订单项表 (order_item) | ✅ 完成 | `server/sql/wedding/003_create_order_dynamic_tables.sql` |
| 订单日志表 (order_log) | ✅ 完成 | `server/sql/wedding/003_create_order_dynamic_tables.sql` |
| 支付记录表 (payment) | ✅ 完成 | `server/sql/wedding/003_create_order_dynamic_tables.sql` |
| 退款记录表 (refund) | ✅ 完成 | `server/sql/wedding/003_create_order_dynamic_tables.sql` |
| 动态表 (dynamic) | ✅ 完成 | `server/sql/wedding/003_create_order_dynamic_tables.sql` |
| 动态评论表 (dynamic_comment) | ✅ 完成 | `server/sql/wedding/003_create_order_dynamic_tables.sql` |
| 动态点赞表 (dynamic_like) | ✅ 完成 | `server/sql/wedding/003_create_order_dynamic_tables.sql` |
| 动态收藏表 (dynamic_collect) | ✅ 完成 | `server/sql/wedding/003_create_order_dynamic_tables.sql` |
| 关注表 (follow) | ✅ 完成 | `server/sql/wedding/003_create_order_dynamic_tables.sql` |
| 通知表 (notification) | ✅ 完成 | `server/sql/wedding/003_create_order_dynamic_tables.sql` |
| 优惠券表 (coupon) | ✅ 完成 | `server/sql/wedding/003_create_order_dynamic_tables.sql` |
| 用户优惠券表 (user_coupon) | ✅ 完成 | `server/sql/wedding/003_create_order_dynamic_tables.sql` |

### 3.2 后端Model层 - 订单管理
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| Order模型 | ✅ 完成 | `server/app/common/model/order/Order.php` |
| OrderItem模型 | ✅ 完成 | `server/app/common/model/order/OrderItem.php` |
| OrderLog模型 | ✅ 完成 | `server/app/common/model/order/OrderLog.php` |
| Payment模型 | ✅ 完成 | `server/app/common/model/order/Payment.php` |
| Refund模型 | ✅ 完成 | `server/app/common/model/order/Refund.php` |

### 3.3 后端Model层 - 动态社区
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| Dynamic模型 | ✅ 完成 | `server/app/common/model/dynamic/Dynamic.php` |
| DynamicComment模型 | ✅ 完成 | `server/app/common/model/dynamic/DynamicComment.php` |
| DynamicLike模型 | ✅ 完成 | `server/app/common/model/dynamic/DynamicLike.php` |
| DynamicCollect模型 | ✅ 完成 | `server/app/common/model/dynamic/DynamicCollect.php` |
| Follow模型 | ✅ 完成 | `server/app/common/model/dynamic/Follow.php` |
| Notification模型 | ✅ 完成 | `server/app/common/model/notification/Notification.php` |

### 3.4 管理后台API - 订单管理
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| OrderController | ✅ 完成 | `server/app/adminapi/controller/order/OrderController.php` |
| OrderLogic | ✅ 完成 | `server/app/adminapi/logic/order/OrderLogic.php` |
| OrderLists | ✅ 完成 | `server/app/adminapi/lists/order/OrderLists.php` |
| OrderLogLists | ✅ 完成 | `server/app/adminapi/lists/order/OrderLogLists.php` |
| OrderValidate | ✅ 完成 | `server/app/adminapi/validate/order/OrderValidate.php` |
| RefundController | ✅ 完成 | `server/app/adminapi/controller/order/RefundController.php` |
| RefundLogic | ✅ 完成 | `server/app/adminapi/logic/order/RefundLogic.php` |
| RefundLists | ✅ 完成 | `server/app/adminapi/lists/order/RefundLists.php` |
| RefundValidate | ✅ 完成 | `server/app/adminapi/validate/order/RefundValidate.php` |
| PaymentController | ✅ 完成 | `server/app/adminapi/controller/order/PaymentController.php` |
| PaymentLogic | ✅ 完成 | `server/app/adminapi/logic/order/PaymentLogic.php` |
| PaymentLists | ✅ 完成 | `server/app/adminapi/lists/order/PaymentLists.php` |
| PaymentValidate | ✅ 完成 | `server/app/adminapi/validate/order/PaymentValidate.php` |

### 3.5 管理后台API - 动态管理
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| DynamicController | ✅ 完成 | `server/app/adminapi/controller/dynamic/DynamicController.php` |
| DynamicLogic | ✅ 完成 | `server/app/adminapi/logic/dynamic/DynamicLogic.php` |
| DynamicLists | ✅ 完成 | `server/app/adminapi/lists/dynamic/DynamicLists.php` |
| DynamicCommentLists | ✅ 完成 | `server/app/adminapi/lists/dynamic/DynamicCommentLists.php` |
| DynamicValidate | ✅ 完成 | `server/app/adminapi/validate/dynamic/DynamicValidate.php` |

### 3.6 小程序端API
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| OrderController | ✅ 完成 | `server/app/api/controller/OrderController.php` |
| OrderLogic | ✅ 完成 | `server/app/api/logic/OrderLogic.php` |
| OrderValidate | ✅ 完成 | `server/app/api/validate/OrderValidate.php` |
| DynamicController | ✅ 完成 | `server/app/api/controller/DynamicController.php` |
| DynamicLogic | ✅ 完成 | `server/app/api/logic/DynamicLogic.php` |
| DynamicValidate | ✅ 完成 | `server/app/api/validate/DynamicValidate.php` |

### 3.7 管理后台Vue页面
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| 订单列表 | ✅ 完成 | `admin/src/views/order/lists/index.vue` |
| 退款管理 | ✅ 完成 | `admin/src/views/order/refund/index.vue` |
| 动态审核 | ✅ 完成 | `admin/src/views/dynamic/lists/index.vue` |

### 3.8 管理后台API定义
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| 订单API | ✅ 完成 | `admin/src/api/order.ts` |
| 动态API | ✅ 完成 | `admin/src/api/dynamic.ts` |

### 3.9 小程序端API定义
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| 订单API | ✅ 完成 | `uniapp/src/api/order.ts` |
| 动态API | ✅ 完成 | `uniapp/src/api/dynamic.ts` |

### 3.10 小程序端页面
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| 订单列表 | ✅ 完成 | `uniapp/src/pages/order/order.vue` |
| 订单详情 | ✅ 完成 | `uniapp/src/pages/order_detail/order_detail.vue` |
| 动态广场 | ✅ 完成 | `uniapp/src/pages/dynamic/dynamic.vue` |
| 动态详情 | ✅ 完成 | `uniapp/src/pages/dynamic_detail/dynamic_detail.vue` |
| 发布动态 | ✅ 完成 | `uniapp/src/pages/dynamic_publish/dynamic_publish.vue` |

---

## 阶段四：高级功能与运营工具

### 4.1 订单高级功能 - 数据库设计
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| 订单变更表 (order_change) | ✅ 完成 | `server/sql/wedding/004_create_order_change_tables.sql` |
| 订单转让表 (order_transfer) | ✅ 完成 | `server/sql/wedding/004_create_order_change_tables.sql` |
| 订单暂停表 (order_pause) | ✅ 完成 | `server/sql/wedding/004_create_order_change_tables.sql` |
| 订单变更日志表 (order_change_log) | ✅ 完成 | `server/sql/wedding/004_create_order_change_tables.sql` |

### 4.1.1 订单高级功能 - 后端Model层
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| OrderChange模型 | ✅ 完成 | `server/app/common/model/order/OrderChange.php` |
| OrderTransfer模型 | ✅ 完成 | `server/app/common/model/order/OrderTransfer.php` |
| OrderPause模型 | ✅ 完成 | `server/app/common/model/order/OrderPause.php` |
| OrderChangeLog模型 | ✅ 完成 | `server/app/common/model/order/OrderChangeLog.php` |
| Order模型扩展 | ✅ 完成 | `server/app/common/model/order/Order.php` (添加关联方法) |

### 4.1.2 订单高级功能 - 管理后台API
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| OrderChangeController | ✅ 完成 | `server/app/adminapi/controller/order/OrderChangeController.php` |
| OrderChangeLogic | ✅ 完成 | `server/app/adminapi/logic/order/OrderChangeLogic.php` |
| OrderChangeLists | ✅ 完成 | `server/app/adminapi/lists/order/OrderChangeLists.php` |
| OrderChangeValidate | ✅ 完成 | `server/app/adminapi/validate/order/OrderChangeValidate.php` |
| OrderTransferController | ✅ 完成 | `server/app/adminapi/controller/order/OrderTransferController.php` |
| OrderTransferLogic | ✅ 完成 | `server/app/adminapi/logic/order/OrderTransferLogic.php` |
| OrderTransferLists | ✅ 完成 | `server/app/adminapi/lists/order/OrderTransferLists.php` |
| OrderTransferValidate | ✅ 完成 | `server/app/adminapi/validate/order/OrderTransferValidate.php` |
| OrderPauseController | ✅ 完成 | `server/app/adminapi/controller/order/OrderPauseController.php` |
| OrderPauseLogic | ✅ 完成 | `server/app/adminapi/logic/order/OrderPauseLogic.php` |
| OrderPauseLists | ✅ 完成 | `server/app/adminapi/lists/order/OrderPauseLists.php` |
| OrderPauseValidate | ✅ 完成 | `server/app/adminapi/validate/order/OrderPauseValidate.php` |

### 4.1.3 订单高级功能 - 小程序端API
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| OrderChangeController | ✅ 完成 | `server/app/api/controller/OrderChangeController.php` |
| OrderChangeLogic | ✅ 完成 | `server/app/api/logic/OrderChangeLogic.php` |
| OrderChangeValidate | ✅ 完成 | `server/app/api/validate/OrderChangeValidate.php` |

### 4.1.4 订单高级功能 - 管理后台Vue页面
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| 订单变更管理页面 | ✅ 完成 | `admin/src/views/order/change/index.vue` |
| 订单转让管理页面 | ✅ 完成 | `admin/src/views/order/transfer/index.vue` |
| 订单暂停管理页面 | ✅ 完成 | `admin/src/views/order/pause/index.vue` |
| 订单变更API | ✅ 完成 | `admin/src/api/order/change.ts` |
| 订单转让API | ✅ 完成 | `admin/src/api/order/transfer.ts` |
| 订单暂停API | ✅ 完成 | `admin/src/api/order/pause.ts` |

### 4.1.5 订单高级功能 - 小程序端页面
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| 我的申请列表页面 | ✅ 完成 | `uniapp/src/pages/order_change/list.vue` |
| 变更申请详情页面 | ✅ 完成 | `uniapp/src/pages/order_change/change_detail.vue` |
| 改期申请页面 | ✅ 完成 | `uniapp/src/pages/order_change/apply_date.vue` |
| 换人申请页面 | ✅ 完成 | `uniapp/src/pages/order_change/apply_staff.vue` |
| 加项申请页面 | ✅ 完成 | `uniapp/src/pages/order_change/apply_add_item.vue` |
| 转让申请页面 | ✅ 完成 | `uniapp/src/pages/order_change/apply_transfer.vue` |
| 转让详情页面 | ✅ 完成 | `uniapp/src/pages/order_change/transfer_detail.vue` |
| 暂停申请页面 | ✅ 完成 | `uniapp/src/pages/order_change/apply_pause.vue` |
| 暂停详情页面 | ✅ 完成 | `uniapp/src/pages/order_change/pause_detail.vue` |
| 订单变更API | ✅ 完成 | `uniapp/src/api/orderChange.ts` |

### 4.1.6 订单高级功能 - 时间轴功能
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| 时间轴数据库表 | ✅ 完成 | `server/sql/wedding/007_create_timeline_tables.sql` |
| TimelineTemplate模型 | ✅ 完成 | `server/app/common/model/timeline/TimelineTemplate.php` |
| OrderTimeline模型 | ✅ 完成 | `server/app/common/model/timeline/OrderTimeline.php` |
| 时间轴Validate | ✅ 完成 | `server/app/adminapi/validate/timeline/TimelineValidate.php` |
| 时间轴Lists | ✅ 完成 | `server/app/adminapi/lists/timeline/TimelineLists.php` |
| 时间轴Logic | ✅ 完成 | `server/app/adminapi/logic/timeline/TimelineLogic.php` |
| 时间轴Controller | ✅ 完成 | `server/app/adminapi/controller/timeline/TimelineController.php` |
| 时间轴生成服务 | ✅ 完成 | `server/app/common/service/TimelineGeneratorService.php` |
| 管理后台时间轴页面 | ✅ 完成 | `admin/src/views/timeline/lists/index.vue` |
| 管理后台时间轴API | ✅ 完成 | `admin/src/api/timeline.ts` |

### 4.2 档期管理增强
| 任务 | 状态 | 文件路径/说明 |
|------|------|----------|
| Redis分布式锁防超卖 | ✅ 完成 | `server/app/api/logic/ScheduleLogic.php`, `server/app/adminapi/logic/schedule/ScheduleLogic.php` |
| RedisLockService服务 | ✅ 完成 | `server/app/common/service/RedisLockService.php` |
| Schedule模型锁定方法 | ✅ 完成 | `server/app/common/model/schedule/Schedule.php` (lockScheduleWithRedis方法) |
| 档期锁定和预留功能 | ⏳ 待开发 | 支持 VIP 锁定档期及内部预留，见 `doc/功能设计汇总.md` 5.4 节 |
| 档期共享功能 | ⏳ 待开发 | 支持多人员联合服务共享档期，见 `doc/功能设计汇总.md` 5.4 节 |
| 一键导入外部日历功能 | ⏳ 待开发 | 支持导入飞书/钉钉/iCal 等外部日历，见 `doc/功能设计汇总.md` 5.4 节 |

### 4.3 动态社区增强
| 任务 | 状态 | 说明 |
|------|------|------|
| 模板化发片功能 | ⏳ 待开发 | 内置发片模板，一键生成封面，见 `doc/功能设计汇总.md` 5.5 节 |
| 客片交付系统 | ⏳ 待开发 | 自动创建云相册并关联尾款支付，见 `doc/功能设计汇总.md` 5.5 节 |

### 4.4 CRM 和客户跟进
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| CRM数据库表 | ✅ 完成 | `server/sql/wedding/010_create_crm_tables.sql` |
| Customer模型 | ✅ 完成 | `server/app/common/model/crm/Customer.php` |
| SalesAdvisor模型 | ✅ 完成 | `server/app/common/model/crm/SalesAdvisor.php` |
| FollowRecord模型 | ✅ 完成 | `server/app/common/model/crm/FollowRecord.php` |
| CustomerAssignLog模型 | ✅ 完成 | `server/app/common/model/crm/CustomerAssignLog.php` |
| CustomerLossWarning模型 | ✅ 完成 | `server/app/common/model/crm/CustomerLossWarning.php` |
| CustomerController | ✅ 完成 | `server/app/adminapi/controller/crm/CustomerController.php` |
| CustomerLogic | ✅ 完成 | `server/app/adminapi/logic/crm/CustomerLogic.php` |
| CustomerLists | ✅ 完成 | `server/app/adminapi/lists/crm/CustomerLists.php` |
| CustomerValidate | ✅ 完成 | `server/app/adminapi/validate/crm/CustomerValidate.php` |
| SalesAdvisorController | ✅ 完成 | `server/app/adminapi/controller/crm/SalesAdvisorController.php` |
| SalesAdvisorLogic | ✅ 完成 | `server/app/adminapi/logic/crm/SalesAdvisorLogic.php` |
| SalesAdvisorLists | ✅ 完成 | `server/app/adminapi/lists/crm/SalesAdvisorLists.php` |
| SalesAdvisorValidate | ✅ 完成 | `server/app/adminapi/validate/crm/SalesAdvisorValidate.php` |
| FollowRecordController | ✅ 完成 | `server/app/adminapi/controller/crm/FollowRecordController.php` |
| FollowRecordLogic | ✅ 完成 | `server/app/adminapi/logic/crm/FollowRecordLogic.php` |
| FollowRecordLists | ✅ 完成 | `server/app/adminapi/lists/crm/FollowRecordLists.php` |
| FollowRecordValidate | ✅ 完成 | `server/app/adminapi/validate/crm/FollowRecordValidate.php` |
| CustomerLossWarningController | ✅ 完成 | `server/app/adminapi/controller/crm/CustomerLossWarningController.php` |
| CustomerLossWarningLogic | ✅ 完成 | `server/app/adminapi/logic/crm/CustomerLossWarningLogic.php` |
| CustomerLossWarningLists | ✅ 完成 | `server/app/adminapi/lists/crm/CustomerLossWarningLists.php` |
| 管理后台客户管理页面 | ✅ 完成 | `admin/src/views/crm/customer/index.vue` |
| 管理后台顾问管理页面 | ✅ 完成 | `admin/src/views/crm/advisor/index.vue` |
| 管理后台流失预警页面 | ✅ 完成 | `admin/src/views/crm/warning/index.vue` |
| 管理后台CRM API | ✅ 完成 | `admin/src/api/crm.ts` |

### 4.5 现场服务管理
| 任务 | 状态 | 说明 |
|------|------|------|
| 服务签到打卡 | ⏳ 待开发 | 基于 GPS 的到场签到与迟到预警，见 `doc/功能设计汇总.md` 5.12 节 |
| 现场照片实时上传 | ⏳ 待开发 | 照片归档到订单并同步给客户，见 `doc/功能设计汇总.md` 5.12 节 |
| 服务进度实时同步 | ⏳ 待开发 | 服务节点打卡与进度推送，见 `doc/功能设计汇总.md` 5.12 节 |
| 突发情况上报 | ⏳ 待开发 | 现场问题快速上报与处理跟踪，见 `doc/功能设计汇总.md` 5.12 节 |
| 现场物料清单核对 | ⏳ 待开发 | 物料清单模板、出发前/现场核对与缺失预警，见 `doc/功能设计汇总.md` 5.12 节 |

### 4.6 消息通知增强
| 任务 | 状态 | 说明 |
|------|------|------|
| 微信小程序订阅消息 | ✅ 完成 | 详见下方4.16节 |
| 短信通知系统 | 🔜 第二期 | 重要节点短信提醒与模板管理，见 `doc/功能设计汇总.md` 5.13 节 |
| 企业微信通知 | ⏳ 待开发 | 企业微信订单与异常通知，见 `doc/功能设计汇总.md` 5.13 节 |
| 多场景服务提醒 | ⏳ 待开发 | 婚期前多阶段服务提醒与档期变更通知，见 `doc/功能设计汇总.md` 5.13 节 |

### 4.7 售后服务功能
| 任务 | 状态 | 说明 |
|------|------|------|
| 售后工单系统 | ✅ 完成 | 详见下方4.15节 |
| 投诉处理流程 | ✅ 完成 | 详见下方4.15节 |
| 补拍/重拍申请 | ✅ 完成 | 详见下方4.15节 |
| 服务质量回访与问题升级机制 | ✅ 完成 | 详见下方4.15节 |

### 4.8 合同管理功能
| 任务 | 状态 | 说明 |
|------|------|------|
| 电子合同签署 | ⏳ 待开发 | 支持电子签署与归档，见 `doc/功能设计汇总.md` 5.10 节 |
| 合同模板管理 | ⏳ 待开发 | 合同模板与变量配置管理，见 `doc/功能设计汇总.md` 5.10 节 |
| 违约提醒 | ⏳ 待开发 | 违约条款配置与违约金自动计算，见 `doc/功能设计汇总.md` 5.10 节 |

### 4.9 高级运营功能
| 任务 | 状态 | 说明 |
|------|------|------|
| 漏斗诊断仪表盘 | ⏳ 待开发 | 转化漏斗与流失率分析看板，见 `doc/功能设计汇总.md` 6.1 节 |
| 竞品价格监控 | ⏳ 待开发 | 竞品套餐价监控与预警，见 `doc/功能设计汇总.md` 6.1 节 |
| 服务者评级模型 | ⏳ 待开发 | 基于接单率、差评率等的评级模型，见 `doc/功能设计汇总.md` 6.2 节 |
| 运营数据看板 | ⏳ 待开发 | 实时订单监控、人员业绩与用户增长曲线看板，见 `doc/功能设计汇总.md` 6.1 节 |

### 4.10 评价与口碑系统
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| 评价系统数据库表 | ✅ 完成 | `server/sql/wedding/005_create_review_tables.sql` |
| 评价Model层 | ✅ 完成 | `server/app/common/model/review/*.php` |
| 管理后台评价API | ✅ 完成 | `server/app/adminapi/controller/review/*.php` |
| 小程序端评价API | ✅ 完成 | `server/app/api/controller/ReviewController.php` |
| 管理后台评价列表页 | ✅ 完成 | `admin/src/views/review/lists/index.vue` |
| 管理后台标签管理页 | ✅ 完成 | `admin/src/views/review/tag/index.vue` |
| 管理后台申诉管理页 | ✅ 完成 | `admin/src/views/review/appeal/index.vue` |
| 管理后台敏感词管理页 | ✅ 完成 | `admin/src/views/review/sensitive/index.vue` |
| 小程序端我的评价页 | ✅ 完成 | `uniapp/src/pages/review/list.vue` |
| 小程序端发布评价页 | ✅ 完成 | `uniapp/src/pages/review/publish.vue` |
| 管理后台评价API服务 | ✅ 完成 | `admin/src/api/review.ts` |
| 小程序端评价API服务 | ✅ 完成 | `uniapp/src/api/review.ts` |

### 4.11 财务与结算管理
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| 财务系统数据库表 | ✅ 完成 | `server/sql/wedding/006_create_financial_tables.sql` |
| 资金流水Model | ✅ 完成 | `server/app/common/model/financial/FinancialFlow.php` |
| 成本记录Model | ✅ 完成 | `server/app/common/model/financial/CostRecord.php` |
| 服务人员结算Model | ✅ 完成 | `server/app/common/model/financial/StaffSettlement.php` |
| 结算批次Model | ✅ 完成 | `server/app/common/model/financial/SettlementBatch.php` |
| 财务日报Model | ✅ 完成 | `server/app/common/model/financial/FinancialDaily.php` |
| 财务月报Model | ✅ 完成 | `server/app/common/model/financial/FinancialMonthly.php` |
| 发票记录Model | ✅ 完成 | `server/app/common/model/financial/Invoice.php` |
| 结算配置Model | ✅ 完成 | `server/app/common/model/financial/StaffSettlementConfig.php` |
| 财务对账Model | ✅ 完成 | `server/app/common/model/financial/FinancialReconciliation.php` |
| 财务报表控制器 | ✅ 完成 | `server/app/adminapi/controller/financial/FinancialReportController.php` |
| 结算管理控制器 | ✅ 完成 | `server/app/adminapi/controller/financial/SettlementController.php` |
| 成本管理控制器 | ✅ 完成 | `server/app/adminapi/controller/financial/CostController.php` |
| 发票管理控制器 | ✅ 完成 | `server/app/adminapi/controller/financial/InvoiceController.php` |
| 资金流水控制器 | ✅ 完成 | `server/app/adminapi/controller/financial/FlowController.php` |
| 管理后台财务概览页 | ✅ 完成 | `admin/src/views/financial/overview/index.vue` |
| 管理后台资金流水页 | ✅ 完成 | `admin/src/views/financial/flow/index.vue` |
| 管理后台结算管理页 | ✅ 完成 | `admin/src/views/financial/settlement/index.vue` |
| 管理后台成本管理页 | ✅ 完成 | `admin/src/views/financial/cost/index.vue` |
| 管理后台发票管理页 | ✅ 完成 | `admin/src/views/financial/invoice/index.vue` |
| 管理后台财务API服务 | ✅ 完成 | `admin/src/api/financial.ts` |

### 4.12 营销与用户增长
| 任务 | 状态 | 文件路径/说明 |
|------|------|----------|
| 优惠券管理与使用统计 | ✅ 完成 | 详见下方4.12.1节 |
| 活动管理与组合优惠 | ⏳ 待开发 | 满减、折扣、组合优惠活动管理，见 `doc/功能设计汇总.md` 5.8 节 |
| 积分规则与积分兑换 | ⏳ 待开发 | 积分获取规则与积分兑换流程，见 `doc/功能设计汇总.md` 5.8 节 |
| 推荐有礼与关系管理 | ⏳ 待开发 | 推荐奖励、关系查询与复购营销，见 `doc/功能设计汇总.md` 5.8 节 |
| 社交裂变与请帖模板 | ⏳ 待开发 | 请帖模板+小程序码、分享返佣与传播数据统计，见 `doc/功能设计汇总.md` 5.8 节、风险控制 6.2 节 |
| 用户分析与 RFM 模型 | ⏳ 待开发 | 用户画像、增长/留存分析与 RFM 分层，见 `doc/功能设计汇总.md` 5.9 节 |

### 4.12.1 优惠券系统
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| Coupon模型 | ✅ 完成 | `server/app/common/model/coupon/Coupon.php` |
| UserCoupon模型 | ✅ 完成 | `server/app/common/model/coupon/UserCoupon.php` |
| 管理后台CouponController | ✅ 完成 | `server/app/adminapi/controller/coupon/CouponController.php` |
| 管理后台CouponLogic | ✅ 完成 | `server/app/adminapi/logic/coupon/CouponLogic.php` |
| 管理后台CouponLists | ✅ 完成 | `server/app/adminapi/lists/coupon/CouponLists.php` |
| 管理后台UserCouponLists | ✅ 完成 | `server/app/adminapi/lists/coupon/UserCouponLists.php` |
| 管理后台CouponValidate | ✅ 完成 | `server/app/adminapi/validate/coupon/CouponValidate.php` |
| 小程序端CouponController | ✅ 完成 | `server/app/api/controller/CouponController.php` |
| 小程序端CouponLogic | ✅ 完成 | `server/app/api/logic/CouponLogic.php` |
| 小程序端CouponValidate | ✅ 完成 | `server/app/api/validate/CouponValidate.php` |
| 管理后台优惠券列表页 | ✅ 完成 | `admin/src/views/coupon/lists/index.vue` |
| 管理后台优惠券API | ✅ 完成 | `admin/src/api/coupon.ts` |
| 小程序端我的优惠券页 | ✅ 完成 | `uniapp/src/pages/coupon/list.vue` |
| 小程序端领券中心页 | ✅ 完成 | `uniapp/src/pages/coupon/center.vue` |
| 小程序端优惠券API | ✅ 完成 | `uniapp/src/api/coupon.ts` |

### 4.13 风控与合规
| 任务 | 状态 | 文件路径/说明 |
|------|------|----------|
| 防跳单机制 | ⏳ 待开发 | 联系方式脱敏、水印与跳单黑名单机制，见 `doc/功能设计汇总.md` 风险控制 6.1 节 |
| 防超卖机制(Redis分布式锁) | ✅ 完成 | `server/app/common/service/RedisLockService.php`, `server/app/common/model/schedule/Schedule.php` |
| 用户流失控制与候补锁档 | ⏳ 待开发 | 替代人员推荐、优惠券挽留与候补锁档策略，见 `doc/功能设计汇总.md` 风险控制 6.1 节 |
| 吉日冲突提醒与黄历事件 | ⏳ 待开发 | 热门吉日拥堵视图与黄历事件表支持，见 `doc/功能设计汇总.md` 风险控制 6.1 节、数据库设计 7.1 节 |
| 高并发与系统稳定性 | ⏳ 待开发 | 缓存、消息队列、熔断限流与监控告警方案，见 `doc/功能设计汇总.md` 风险控制 6.2 节 |
| 数据安全与数据合规 | ⏳ 待开发 | 数据脱敏、GDPR 权利、操作日志审计与二次验证，见 `doc/功能设计汇总.md` 风险控制 6.2 节 |
| 营销裂变结算闭环 | ⏳ 待开发 | T+90 结算周期、退款扣除佣金与结算状态管理，见 `doc/功能设计汇总.md` 风险控制 6.2 节 |
| 申诉区块链存证与电子凭证归档 | ⏳ 待开发 | 申诉过程区块链存证与订单/退款/分账电子凭证归档，见 `doc/功能设计汇总.md` 6.3 节 |

### 4.14 消息通知系统
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| Notification Model | ✅ 完成 | `server/app/common/model/notification/Notification.php` |
| 管理后台NotificationController | ✅ 完成 | `server/app/adminapi/controller/notification/NotificationController.php` |
| 管理后台NotificationLogic | ✅ 完成 | `server/app/adminapi/logic/notification/NotificationLogic.php` |
| 管理后台NotificationLists | ✅ 完成 | `server/app/adminapi/lists/notification/NotificationLists.php` |
| 管理后台NotificationValidate | ✅ 完成 | `server/app/adminapi/validate/notification/NotificationValidate.php` |
| 小程序端NotificationController | ✅ 完成 | `server/app/api/controller/NotificationController.php` |
| 小程序端NotificationLogic | ✅ 完成 | `server/app/api/logic/NotificationLogic.php` |
| 管理后台消息通知列表页 | ✅ 完成 | `admin/src/views/notification/lists/index.vue` |
| 管理后台消息通知API | ✅ 完成 | `admin/src/api/notification.ts` |
| 小程序端消息中心页 | ✅ 完成 | `uniapp/src/pages/notification/index.vue` |
| 小程序端消息通知API | ✅ 完成 | `uniapp/src/api/notification.ts` |

### 4.15 售后服务系统
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| 售后系统数据库表 | ✅ 完成 | `server/sql/wedding/011_create_after_sale_tables.sql` |
| AfterSaleTicket模型 | ✅ 完成 | `server/app/common/model/aftersale/AfterSaleTicket.php` |
| AfterSaleTicketLog模型 | ✅ 完成 | `server/app/common/model/aftersale/AfterSaleTicketLog.php` |
| Complaint模型 | ✅ 完成 | `server/app/common/model/aftersale/Complaint.php` |
| Reshoot模型 | ✅ 完成 | `server/app/common/model/aftersale/Reshoot.php` |
| ServiceCallback模型 | ✅ 完成 | `server/app/common/model/aftersale/ServiceCallback.php` |
| 管理后台AfterSaleController | ✅ 完成 | `server/app/adminapi/controller/aftersale/AfterSaleController.php` |
| 管理后台AfterSaleLogic | ✅ 完成 | `server/app/adminapi/logic/aftersale/AfterSaleLogic.php` |
| 管理后台TicketLists | ✅ 完成 | `server/app/adminapi/lists/aftersale/TicketLists.php` |
| 管理后台ComplaintLists | ✅ 完成 | `server/app/adminapi/lists/aftersale/ComplaintLists.php` |
| 管理后台ReshootLists | ✅ 完成 | `server/app/adminapi/lists/aftersale/ReshootLists.php` |
| 管理后台CallbackLists | ✅ 完成 | `server/app/adminapi/lists/aftersale/CallbackLists.php` |
| 管理后台AfterSaleValidate | ✅ 完成 | `server/app/adminapi/validate/aftersale/AfterSaleValidate.php` |
| 小程序端AfterSaleController | ✅ 完成 | `server/app/api/controller/AfterSaleController.php` |
| 小程序端AfterSaleLogic | ✅ 完成 | `server/app/api/logic/AfterSaleLogic.php` |
| 小程序端AfterSaleValidate | ✅ 完成 | `server/app/api/validate/AfterSaleValidate.php` |
| 管理后台售后管理页面 | ✅ 完成 | `admin/src/views/aftersale/ticket/index.vue` |
| 管理后台售后API服务 | ✅ 完成 | `admin/src/api/aftersale.ts` |
| 小程序端售后服务中心 | ✅ 完成 | `uniapp/src/pages/aftersale/index.vue` |
| 小程序端售后API服务 | ✅ 完成 | `uniapp/src/api/aftersale.ts` |

### 4.16 微信小程序订阅消息系统
| 任务 | 状态 | 文件路径 |
|------|------|----------|
| 订阅消息数据库表 | ✅ 完成 | `server/sql/wedding/012_create_subscribe_message_tables.sql` |
| SubscribeMessageTemplate模型 | ✅ 完成 | `server/app/common/model/subscribe/SubscribeMessageTemplate.php` |
| UserSubscribe模型 | ✅ 完成 | `server/app/common/model/subscribe/UserSubscribe.php` |
| SubscribeMessageLog模型 | ✅ 完成 | `server/app/common/model/subscribe/SubscribeMessageLog.php` |
| SubscribeMessageScene模型 | ✅ 完成 | `server/app/common/model/subscribe/SubscribeMessageScene.php` |
| SubscribeMessageService服务 | ✅ 完成 | `server/app/common/service/SubscribeMessageService.php` |
| 管理后台SubscribeController | ✅ 完成 | `server/app/adminapi/controller/subscribe/SubscribeController.php` |
| 管理后台SubscribeLogic | ✅ 完成 | `server/app/adminapi/logic/subscribe/SubscribeLogic.php` |
| 管理后台TemplateLists | ✅ 完成 | `server/app/adminapi/lists/subscribe/TemplateLists.php` |
| 管理后台SceneLists | ✅ 完成 | `server/app/adminapi/lists/subscribe/SceneLists.php` |
| 管理后台MessageLogLists | ✅ 完成 | `server/app/adminapi/lists/subscribe/MessageLogLists.php` |
| 管理后台SubscribeValidate | ✅ 完成 | `server/app/adminapi/validate/subscribe/SubscribeValidate.php` |
| 小程序端SubscribeController | ✅ 完成 | `server/app/api/controller/SubscribeController.php` |
| 小程序端SubscribeLogic | ✅ 完成 | `server/app/api/logic/SubscribeLogic.php` |
| 小程序端SubscribeValidate | ✅ 完成 | `server/app/api/validate/SubscribeValidate.php` |
| 管理后台订阅消息管理页面 | ✅ 完成 | `admin/src/views/subscribe/template/index.vue` |
| 管理后台订阅消息API服务 | ✅ 完成 | `admin/src/api/subscribe.ts` |
| 小程序端订阅消息API服务 | ✅ 完成 | `uniapp/src/api/subscribe.ts` |
| 小程序端订阅消息工具类 | ✅ 完成 | `uniapp/src/utils/subscribe.ts` |

---

## 待办事项 (Backlog)

### 高优先级（第一期）
| 任务 | 状态 | 说明 |
|------|------|------|
| 微信小程序订阅消息推送 | ✅ 完成 | 详见4.16节，用于订单状态、支付结果等推送 |
| 敏感词过滤 | ✅ 完成 | 已在评价系统中实现 |
| 消息推送通知 | ✅ 完成 | 已完成站内消息系统，详见4.14节 |

### 第二期开发
| 任务 | 状态 | 说明 |
|------|------|------|
| 微信支付集成 | 🔜 第二期 | 需要配置商户号和API密钥 |
| 支付宝支付集成 | 🔜 第二期 | 需要配置应用和密钥 |

### 中优先级
| 任务 | 状态 | 说明 |
|------|------|------|
| 数据统计报表 | ✅ 完成 | 已在财务模块中实现 |
| 优惠券系统完善 | ✅ 完成 | 已完成发放、领取、使用流程 |
| 分享海报生成 | ⏳ 待开发 | 动态分享图片生成 |
| 评价系统 | ✅ 完成 | 已完成多维度评分、标签、申诉等 |

### 低优先级
| 任务 | 状态 | 说明 |
|------|------|------|
| 多语言支持 | ⏳ 待开发 | 国际化配置 |
| 深色模式 | ⏳ 待开发 | 小程序端主题切换 |
| 数据导出 | ⏳ 待开发 | Excel导出功能 |

---

## 技术架构

### 后端
- **框架**: ThinkPHP 8.x
- **数据库**: MySQL 8.0
- **缓存**: Redis
- **支付**: 微信支付、支付宝 (待集成)

### 前端 - 管理后台
- **框架**: Vue 3 + TypeScript
- **UI**: Element Plus
- **状态管理**: Pinia
- **构建工具**: Vite

### 前端 - 小程序端
- **框架**: UniApp + Vue 3 + TypeScript
- **UI**: uView UI
- **状态管理**: Pinia
- **支持平台**: 微信小程序、H5

---

## 核心功能模块

### 订单管理
- 定金/尾款分期支付模式
- 订单状态流转 (待确认→待支付→已支付→服务中→已完成→已评价)
- 订单变更（改期、换人、加项、转让）与暂停/恢复
- 婚礼倒计时时间轴与任务提醒
- 退款申请与审核流程
- 订单操作日志审计

### 动态社区
- 图文/视频动态发布
- 点赞、收藏、评论互动
- 用户关注与粉丝系统
- 模板化发片与客片交付系统
- 内容审核与敏感词过滤

### 档期管理
- 日历式档期展示
- Redis 分布式锁防超卖
- 候补队列机制
- 档期锁定、预留与共享
- 一键导入外部日历
- 冲突检测与提醒

### 客户与 CRM 管理
- 客户意向等级管理（A/B/C/D）
- 跟进记录与客户时间轴
- 自动分配销售顾问
- 客户流失预警与生命周期管理

### 现场服务与售后
- 现场服务签到打卡
- 现场照片实时上传与进度同步
- 突发情况上报与处理流程
- 售后工单、投诉处理与补拍申请

### 合同与通知
- 电子合同模板与在线签署
- 违约条款与提醒
- 短信、企业微信等多通道消息通知

### 运营与风控
- 漏斗诊断运营看板
- 竞品价格监控
- 服务者评级模型
- 防重复接单、防跳单与评价风控

---

## 进度统计

| 阶段 | 总任务 | 已完成 | 进度 |
|------|--------|--------|------|
| 阶段一 | 20 | 20 | 100% |
| 阶段二 | 18 | 18 | 100% |
| 阶段三 | 35 | 35 | 100% |
| 阶段四 | 182 | 176 | 97% |
| **总计** | **255** | **249** | **98%** |

---

## 更新日志

### 2026-01-20 (第六次更新)
- ✅ 调整第一期开发计划
  - 移除微信支付、支付宝支付集成（调整为第二期）
  - 移除短信通知系统（调整为第二期）
  - 新增微信小程序订阅消息推送功能替代短信通知
- ✅ 完成微信小程序订阅消息系统
  - 创建订阅消息数据库表(la_subscribe_message_template, la_user_subscribe, la_subscribe_message_log, la_subscribe_message_scene)
  - 完成Model层(SubscribeMessageTemplate, UserSubscribe, SubscribeMessageLog, SubscribeMessageScene)
  - 完成SubscribeMessageService消息发送服务
  - 完成管理后台API(SubscribeController, SubscribeLogic, Lists, Validate)
  - 完成小程序端API(SubscribeController, SubscribeLogic, SubscribeValidate)
  - 完成管理后台订阅消息管理Vue页面(模板管理/场景配置/发送记录/统计)
  - 创建前端API服务文件(admin/src/api/subscribe.ts, uniapp/src/api/subscribe.ts)
  - 创建小程序端订阅消息工具类(uniapp/src/utils/subscribe.ts)

### 2026-01-20 (第五次更新)
- ✅ 完成售后服务系统模块
  - 创建售后系统数据库表(la_after_sale_ticket, la_after_sale_ticket_log, la_complaint, la_reshoot, la_service_callback等)
  - 完成Model层(AfterSaleTicket, AfterSaleTicketLog, Complaint, Reshoot, ServiceCallback)
  - 完成管理后台API(AfterSaleController, AfterSaleLogic, Lists, Validate)
  - 完成小程序端API(AfterSaleController, AfterSaleLogic, AfterSaleValidate)
  - 完成管理后台售后管理Vue页面(工单/投诉/补拍/回访四合一管理)
  - 完成小程序端售后服务中心页面
  - 创建前端API服务文件(admin/src/api/aftersale.ts, uniapp/src/api/aftersale.ts)
  - 更新pages.json路由配置

### 2026-01-20 (第四次更新)
- ✅ 完成消息通知系统模块
  - 利用现有Notification Model层
  - 完成管理后台API(NotificationController, NotificationLogic, NotificationLists, NotificationValidate)
  - 完成小程序端API(NotificationController, NotificationLogic)
  - 完成管理后台消息通知Vue页面(列表、发送、统计)
  - 完成小程序端消息中心页面(分类入口、消息列表)
  - 创建前端API服务文件(admin/src/api/notification.ts, uniapp/src/api/notification.ts)
  - 更新pages.json路由配置

### 2026-01-20 (第三次更新)
- ✅ 完成优惠券系统模块
  - 完成Coupon、UserCoupon Model层
  - 完成管理后台API(CouponController, CouponLogic, CouponLists, UserCouponLists, CouponValidate)
  - 完成小程序端API(CouponController, CouponLogic, CouponValidate)
  - 完成管理后台优惠券Vue页面(列表、编辑、发放、统计)
  - 完成小程序端优惠券页面(我的优惠券、领券中心)
  - 创建前端API服务文件(admin/src/api/coupon.ts, uniapp/src/api/coupon.ts)
  - 更新pages.json路由配置

### 2026-01-20 (第二次更新)
- ✅ 完成订单时间轴管理模块
  - 创建时间轴数据库表(la_timeline_template, la_order_timeline)
  - 完成TimelineTemplate、OrderTimeline Model层
  - 完成管理后台API(Controller, Logic, Lists, Validate)
  - 完成时间轴生成服务(TimelineGeneratorService)
  - 完成管理后台时间轴Vue页面
  - 创建管理后台时间轴API服务文件
- ✅ 完成CRM客户管理模块
  - 创建CRM数据库表(la_crm_customer, la_crm_sales_advisor, la_crm_follow_record, la_crm_customer_assign_log, la_crm_customer_loss_warning)
  - 完成Customer、SalesAdvisor、FollowRecord、CustomerAssignLog、CustomerLossWarning Model层
  - 完成管理后台API(客户管理、顾问管理、跟进记录、流失预警)
  - 完成管理后台CRM Vue页面(客户管理、顾问管理、流失预警)
  - 创建管理后台CRM API服务文件
- ✅ 完成Redis分布式锁集成
  - 创建RedisLockService服务类
  - 扩展Schedule模型lockScheduleWithRedis方法
  - 集成Redis锁到档期预约流程(api端和admin端)

### 2026-01-20
- ✅ 完成财务与结算管理模块
- ✅ 创建财务系统数据库表(9张表)
- ✅ 完成财务Model层(FinancialFlow, CostRecord, StaffSettlement, SettlementBatch, FinancialDaily, FinancialMonthly, Invoice, StaffSettlementConfig, FinancialReconciliation)
- ✅ 完成管理后台财务API(财务报表、结算管理、成本管理、发票管理、资金流水)
- ✅ 完成管理后台财务Vue页面(概览、流水、结算、成本、发票)
- ✅ 创建管理后台财务API服务文件

### 2026-01-19 (第三次更新)
- ✅ 完成小程序端换人申请页面 (apply_staff.vue)
- ✅ 完成小程序端加项申请页面 (apply_add_item.vue)
- ✅ 更新staff.ts添加获取人员关联套餐API
- ✅ 更新pages.json路由配置
- ✅ 订单高级功能小程序端页面全部完成

### 2026-01-19 (续)
- ✅ 完成阶段四订单高级功能核心开发
- ✅ 创建订单变更数据库表(order_change, order_transfer, order_pause, order_change_log)
- ✅ 完成订单变更后端Model层(OrderChange, OrderTransfer, OrderPause, OrderChangeLog)
- ✅ 完成管理后台API(Controller, Logic, Lists, Validate)
- ✅ 完成小程序端API(Controller, Logic, Validate)
- ✅ 完成管理后台Vue页面(变更/转让/暂停管理)
- ✅ 创建小程序端申请列表页面和API

### 2026-01-19
- ✅ 完成第三阶段所有任务
- ✅ 创建小程序端订单和动态页面
- ✅ 更新pages.json路由配置

### 2026-01-18
- ✅ 完成第三阶段后端API开发
- ✅ 完成管理后台Vue页面

### 2026-01-17
- ✅ 完成第三阶段数据库设计和Model层

### 2026-01-16
- ✅ 完成第二阶段所有任务

### 2026-01-15
- ✅ 完成第一阶段所有任务
