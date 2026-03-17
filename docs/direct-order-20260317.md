# 2026-03-17 单服务直购改造日志

## 变更目标

本次调整将用户端下单链路从“加入购物车 -> 购物车结算”收敛为“选择人员、日期、套餐后直接下单”，并统一下线购物车、我的方案、分享方案相关能力。

## 变更范围

- `server`：订单预览、可用优惠券、创建订单接口改为基于 `staff_id + package_id + date` 直购。
- `uniapp`：选档期页改为立即下单，确认页改为单服务确认，移除购物车、方案、分享方案页面和路由。
- `admin`：装修链接选择器移除购物车和婚礼方案入口。

## 后端改动

- `server/app/api/validate/OrderValidate.php`
  - 新增直购参数校验：`staff_id`、`package_id`、`date`。
  - 新增 `selection` 场景，供预览和优惠券接口复用。
- `server/app/api/controller/OrderController.php`
  - `preview`、`availableCoupons` 改为走直购参数校验。
  - `create` 语义改为直购创建订单。
- `server/app/api/logic/OrderLogic.php`
  - 新增直购选择构建逻辑。
  - 预览时先释放当前用户未成单的套餐临时锁，再为当前选择创建新的 `PackageBooking` 临时锁。
  - 创建订单时重新校验档期和套餐锁，只创建单条订单项。
- `server/app/common/model/order/Order.php`
  - `createOrder` 改为接收通用 `selectedItems`。
  - 订单头和订单项服务日期直接取直购日期，数量固定为 `1`。
- `server/app/api/controller/CartController.php`
  - 全部接口统一返回“购物车功能已下线，请直接下单”。
- `server/app/api/logic/CartLogic.php`
  - 收口为统一下线响应。
- `server/app/api/validate/CartValidate.php`
  - 保留空验证器，仅兼容旧类引用。
- `server/app/common/service/DecorateDataService.php`
  - 解析装修数据时自动剔除历史购物车、婚礼方案、分享方案入口。
- `server/sql/wedding/042_remove_cart_and_enable_direct_order.sql`
  - 清空 `la_cart`、`la_cart_plan`。
  - 释放未关联订单的套餐临时锁。

## 移动端改动

- `uniapp/src/packages/pages/schedule_calendar/schedule_calendar.vue`
  - 底部主按钮从“加入购物车”改为“立即下单”。
  - 直接跳转订单确认页，并携带 `staff_id`、`package_id`、`date`。
- `uniapp/src/packages/pages/order_confirm/order_confirm.vue`
  - 改为单服务确认页。
  - 预览、优惠券、创建订单全部仅依赖 `staff_id`、`package_id`、`date`。
  - 删除购物车回跳和多项分组结算逻辑。
- `uniapp/src/pages.json`
  - 删除购物车、我的方案、分享方案页面路由。
- 删除文件
  - `uniapp/src/api/cart.ts`
  - `uniapp/src/packages/pages/cart/cart.vue`
  - `uniapp/src/packages/pages/cart_plan/cart_plan.vue`
  - `uniapp/src/packages/pages/share_plan/share_plan.vue`

## 管理端改动

- `admin/src/components/link/shop-pages.vue`
  - 删除“购物车”“婚礼方案”装修跳转入口。

## 校验情况

- 已执行相关 PHP 文件 `php -l` 语法检查，结果通过。
- 已执行 `git diff --check`，未发现新的 diff 格式错误。
- 本次未执行浏览器端人工回归和自动化 UI 回归。
