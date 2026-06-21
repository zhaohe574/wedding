#!/usr/bin/env node
/**
 * P0/P1/P2 QA contract checks for booking/payment/schedule reliability.
 *
 * Scope: static source-code assertions that do not require DB/Composer/HTTP runtime.
 * The script intentionally fails when a must-have guard is missing, so QA can attach
 * reproducible evidence to the defect report.
 */

const fs = require('fs')
const path = require('path')

const ROOT = path.resolve(__dirname, '..', '..')

const fileCache = new Map()
const rel = (...segments) => path.join(ROOT, ...segments)
const read = (...segments) => {
  const file = rel(...segments)
  if (!fileCache.has(file)) {
    fileCache.set(file, fs.readFileSync(file, 'utf8'))
  }
  return fileCache.get(file)
}

const normalize = (text) => text.replace(/\s+/g, ' ')

const checks = []
const check = (id, title, fn) => checks.push({ id, title, fn })

const assertIncludes = (text, needle, message) => {
  if (!text.includes(needle)) {
    throw new Error(message || `Missing text: ${needle}`)
  }
}

const assertNotIncludes = (text, needle, message) => {
  if (text.includes(needle)) {
    throw new Error(message || `Unexpected text: ${needle}`)
  }
}

const assertRegex = (text, regex, message) => {
  if (!regex.test(text)) {
    throw new Error(message || `Missing pattern: ${regex}`)
  }
}

const assertCountAtLeast = (text, needle, min, message) => {
  const count = text.split(needle).length - 1
  if (count < min) {
    throw new Error(message || `Expected at least ${min} occurrence(s) of ${needle}, got ${count}`)
  }
}

const extractFunctionBody = (text, functionName) => {
  const match = new RegExp(`function\\s+${functionName}\\s*\\([^)]*\\)\\s*(?::\\s*[^\\{]+)?\\{`, 'm').exec(text)
  if (!match) {
    throw new Error(`Missing function: ${functionName}`)
  }

  let depth = 0
  for (let index = match.index + match[0].length - 1; index < text.length; index += 1) {
    const char = text[index]
    if (char === '{') {
      depth += 1
    } else if (char === '}') {
      depth -= 1
      if (depth === 0) {
        return text.slice(match.index, index + 1)
      }
    }
  }

  throw new Error(`Unclosed function body: ${functionName}`)
}

const assertTransactionIdPersistedFromCallback = (body, message) => {
  const directOrNormalizedAssignment = /\$payment->transaction_id\s*=\s*(?:trim\(\$transactionId\)\s*!==\s*''\s*\?\s*)?\$transactionId(?:\s*:\s*null)?\s*;/s
  assertRegex(body, directOrNormalizedAssignment, message)
  assertRegex(body, /\$payment->save\s*\(/s, `${message}; payment row must be saved after transaction_id assignment`)
}

const payment = () => read('server', 'app', 'common', 'model', 'order', 'Payment.php')
const payNotify = () => read('server', 'app', 'common', 'logic', 'PayNotifyLogic.php')
const wechatPay = () => read('server', 'app', 'common', 'service', 'pay', 'WeChatPayService.php')
const orderModel = () => read('server', 'app', 'common', 'model', 'order', 'Order.php')
const orderLogic = () => read('server', 'app', 'api', 'logic', 'OrderLogic.php')
const packageBooking = () => read('server', 'app', 'common', 'model', 'package', 'PackageBooking.php')
const schedule = () => read('server', 'app', 'common', 'model', 'schedule', 'Schedule.php')
const scheduleLock = () => read('server', 'app', 'common', 'model', 'schedule', 'ScheduleLock.php')
const adminOrderLogic = () => read('server', 'app', 'adminapi', 'logic', 'order', 'OrderLogic.php')
const activityRegistrationService = () => read('server', 'app', 'common', 'service', 'ActivityRegistrationService.php')
const staffScheduleConfirmLetterService = () => read('server', 'app', 'common', 'service', 'StaffScheduleConfirmLetterService.php')
const consoleConfig = () => read('server', 'config', 'console.php')
const activityRegistrationMigration = () => read('server', 'sql', '1.9.0.20260615', 'update.sql')
const staffDetailPage = () => read('uniapp', 'src', 'packages', 'pages', 'staff_detail', 'staff_detail.vue')
const orderDetailPage = () => read('uniapp', 'src', 'packages', 'pages', 'order_detail', 'order_detail.vue')
const paymentResultPage = () => read('uniapp', 'src', 'pages', 'payment_result', 'payment_result.vue')
const notificationPage = () => read('uniapp', 'src', 'packages', 'pages', 'notification', 'index.vue')
const orderConfirmPage = () => read('uniapp', 'src', 'packages', 'pages', 'order_confirm', 'order_confirm.vue')
const pagesJson = () => read('uniapp', 'src', 'pages.json')
const likeSql = () => read('server', 'public', 'install', 'db', 'like.sql')
const staffScheduleDesigner = () => read('admin', 'src', 'components', 'staff', 'schedule-confirm-letter-designer.vue')

// P0: payment callback correctness and idempotency.
check('PAY-001', '微信支付回调必须校验金额和币种', () => {
  const src = payment()
  assertIncludes(src, 'validatePaidCallback', 'Payment::validatePaidCallback must exist')
  assertIncludes(src, "callbackData['amount']", 'callback amount must be read')
  assertIncludes(src, "array_key_exists('total'", 'amount.total must be required')
  assertIncludes(src, 'MoneyService::yuanToFen($payment->pay_amount)', 'local amount must be converted to fen for comparison')
  assertIncludes(src, '$actualFen !== $expectedFen', 'callback amount mismatch must be rejected')
  assertIncludes(src, "currency'] ?? 'CNY'", 'currency must default/check CNY')
})

check('PAY-002', '支付回调需携带并落库 transaction_id', () => {
  const src = payment()
  assertRegex(src, /function\s+paySuccess\s*\([^)]*\$transactionId/s, 'paySuccess must accept transactionId')
  assertTransactionIdPersistedFromCallback(
    extractFunctionBody(src, 'paySuccess'),
    'normal paid callback should persist transaction_id from callback transactionId'
  )
  assertTransactionIdPersistedFromCallback(
    extractFunctionBody(src, 'handleExceptionalPaidCallback'),
    'exceptional paid callback should persist transaction_id from callback transactionId'
  )
  assertRegex(src, /where\(['"]transaction_id['"],\s*\$transactionId\)[\s\S]*->lock\(true\)/s, 'transaction_id uniqueness check must lock matching payment rows')
  assertIncludes(payNotify(), "$extra['transaction_id'] ?? ''", 'PayNotifyLogic must pass transaction_id into Payment::paySuccess')
  assertIncludes(wechatPay(), "'transaction_id' => $message['transaction_id']", 'WeChat notify parser must extract transaction_id')
})

check('PAY-003', '支付回调必须校验 payer/openid 归属', () => {
  const src = payment()
  assertIncludes(wechatPay(), "'payer' => $message['payer'] ?? []", 'WeChat notify parser must pass payer to callback_data')
  assertRegex(src, /callbackData\[['\"]payer['\"]\].*(openid|user_id)|openid.*callbackData\[['\"]payer['\"]|UserAuth/s, 'Payment callback must validate payer.openid/user binding before marking paid')
})

check('PAY-004', '重复支付回调必须幂等且不重复通知/入账', () => {
  const src = payment()
  assertRegex(src, /pay_status\s*===?\s*self::STATUS_PAID[\s\S]*return\s*\[true,\s*['\"]已处理/s, 'paid payment replay must short-circuit as already handled')
  assertIncludes(src, "'should_notify' => false", 'idempotent branch must disable notification')
  assertIncludes(src, "'should_notify_completed' => false", 'idempotent branch must disable completed notification')
  assertIncludes(src, 'FinancialFlow::safeCreateUniqueFlow', 'financial flow must be unique/idempotent by biz id/sn')
})

check('PAY-005', '取消/关闭/超时后的支付回调必须登记异常并走补偿退款', () => {
  const src = payment()
  assertIncludes(src, 'handleExceptionalPaidCallback', 'exceptional paid callback handler must exist')
  assertIncludes(src, 'Order::STATUS_PENDING_PAY', 'normal paid path must require pending-pay order status')
  assertIncludes(src, 'late_callback_exception', 'late callback context must be exposed for notify layer')
  assertIncludes(src, 'Refund::createSystemRefund', 'closed-order callback must create compensation refund when needed')
  assertIncludes(src, 'OrderRefundService::isOrderFinishedStatus', 'finished/closed statuses must be considered for refund')
  assertIncludes(src, 'EXCEPTION_TYPE_SCHEDULE_LOCK_FAILED_AFTER_PAYMENT', 'schedule lock failure after paid callback must expose a stable exception type')
  assertIncludes(src, 'buildPaymentExceptionPayload', 'pay status response must expose schedule-lock-failed payment exception payload')
  assertIncludes(src, '[self::WAY_BALANCE, self::WAY_OFFLINE]', 'balance/offline schedule-lock failure must fail the current payment transaction')
})

check('PAY-006', '支付回调与发起支付需使用事务/行锁保护状态切换', () => {
  assertIncludes(payNotify(), 'Db::startTrans()', 'PayNotifyLogic must wrap callback in transaction')
  assertIncludes(payment(), "where('payment_sn', $paymentSn)->lock(true)->find()", 'Payment row must be locked')
  assertIncludes(payment(), "Order::where('id', $payment->order_id)->lock(true)->find()", 'Order row must be locked')
  assertIncludes(read('server', 'app', 'common', 'logic', 'OrderPayLogic.php'), 'self::getPayableOrder((int)$orderData', 'pay entry must resolve payable order')
})

// P0: schedule/package booking locks.
check('LOCK-001', '套餐临时锁必须使用事务、行锁和 900 秒有效期', () => {
  const src = packageBooking()
  assertIncludes(src, 'const LOCK_DURATION = 900', 'temporary package lock duration must be 900 seconds')
  assertIncludes(src, 'Db::startTrans()', 'package booking must use transactions')
  assertCountAtLeast(src, '->lock(true)', 4, 'package booking conflict queries should use row locks')
  assertIncludes(src, "whereIn('status', [self::STATUS_TEMP_LOCK, self::STATUS_CONFIRMED])", 'TEMP_LOCK/CONFIRMED conflicts must be checked')
  assertIncludes(src, "'lock_expire_time' => time() + self::LOCK_DURATION", 'lock expiry must be refreshed/set')
})

check('LOCK-002', '未支付订单生成只能校验档期，首笔支付成功后才锁档', () => {
  const src = orderLogic()
  assertRegex(src, /public\s+static\s+function\s+createOrder[\s\S]*Db::startTrans\(\)/, 'createOrder must start a transaction')
  assertIncludes(src, 'ensureScheduleAvailable', 'API logic must verify schedule availability before order creation')
  assertIncludes(src, 'Order::createOrder($userId, $selectedItems, $params)', 'API logic must delegate to atomic Order::createOrder')
  const model = orderModel()
  const createOrderBody = extractFunctionBody(model, 'createOrder')
  assertNotIncludes(createOrderBody, 'RedisLockService::batchLockSchedules', 'unpaid order creation must not acquire schedule locks')
  assertNotIncludes(model, 'buildOrderCreationLockSchedules', 'unpaid order creation must not build schedule lock keys')
  assertIncludes(model, 'assertSelectedSchedulesAvailableForOrderCreation', 'order creation must keep read-only schedule availability checks')
  assertIncludes(model, 'lockSchedulesAfterFirstPayment', 'first successful payment must be the schedule booking entrypoint')
  assertIncludes(payment(), 'Order::lockSchedulesAfterFirstPayment($order)', 'payment callback must lock schedules after first paid stage')
  assertIncludes(model, 'Db::commit()', 'order model must commit only after order/items/booking are consistent')

  const adminOrder = adminOrderLogic()
  const confirmOfflineBody = extractFunctionBody(adminOrder, 'confirmOfflinePay')
  const auditVoucherBody = extractFunctionBody(adminOrder, 'auditPayVoucher')
  const confirmLockPos = confirmOfflineBody.indexOf('Order::lockSchedulesAfterFirstPayment($order)')
  const confirmPaymentPos = confirmOfflineBody.indexOf('Payment::create([')
  if (confirmLockPos < 0 || confirmPaymentPos < 0 || confirmLockPos > confirmPaymentPos) {
    throw new Error('admin offline payment must lock schedules before creating paid payment record')
  }
  const auditLockPos = auditVoucherBody.indexOf('Order::lockSchedulesAfterFirstPayment($order)')
  const auditPaymentPos = auditVoucherBody.indexOf('Payment::create([')
  if (auditLockPos < 0 || auditPaymentPos < 0 || auditLockPos > auditPaymentPos) {
    throw new Error('offline voucher approval must lock schedules before creating paid payment record')
  }
})

check('LOCK-003', '档期锁定必须使用条件更新/版本防并发覆盖', () => {
  const src = schedule()
  assertIncludes(src, 'RedisLockService::lockScheduleWithRedis', 'distributed lock wrapper should protect schedule lock')
  assertCountAtLeast(src, "where('version'", 2, 'schedule lock/confirm must update by version')
  assertIncludes(src, "where('status', self::STATUS_LOCKED)", 'owned-lock renewal must condition on locked status')
  assertIncludes(src, '档期已被其他用户抢占', 'concurrent update failure must expose explicit conflict message')
})

check('LOCK-006', 'schedule_lock 有效锁必须有唯一约束且释放后不阻塞历史记录', () => {
  const sql = likeSql()
  const migration = activityRegistrationMigration()
  const model = scheduleLock()
  assertIncludes(sql, '`active_key` VARCHAR(64) DEFAULT NULL', 'install SQL must include nullable active_key for effective locks')
  assertIncludes(sql, 'UNIQUE KEY `uk_active_lock` (`active_key`)', 'install SQL must include active lock unique key')
  assertIncludes(migration, 'ADD COLUMN `active_key`', 'migration must add active_key to existing schedule_lock table')
  assertIncludes(migration, 'ADD UNIQUE KEY `uk_active_lock` (`active_key`)', 'migration must add active lock unique key')
  assertIncludes(model, 'refreshActiveKey', 'ScheduleLock model must maintain active_key automatically')
  assertIncludes(model, 'STATUS_RELEASED', 'released locks must clear active_key and keep history reusable')
})

check('ACT-001', '活动报名待支付过期必须有定时任务释放库存', () => {
  assertIncludes(activityRegistrationService(), 'expirePendingRegistrations', 'ActivityRegistrationService must expose batch expiration method')
  assertIncludes(activityRegistrationService(), 'syncPendingRegistrationExpired($registrationId)', 'batch expiration must reuse existing quota release flow')
  assertIncludes(read('server', 'app', 'common', 'command', 'ExpireActivityRegistrations.php'), 'expire_activity_registrations', 'expiration command must exist')
  assertIncludes(consoleConfig(), "'expire_activity_registrations' => 'app\\common\\command\\ExpireActivityRegistrations'", 'console config must register expiration command')
  assertIncludes(likeSql(), "'expire_activity_registrations'", 'install SQL must create crontab record for activity registration expiration')
  assertIncludes(activityRegistrationMigration(), "command` = 'expire_activity_registrations'", 'migration must idempotently add crontab record')
})

check('LOCK-004', '取消订单必须释放套餐锁/档期并停用旧客户确认函', () => {
  const src = orderModel()
  assertIncludes(src, 'OrderConfirmLetterService::invalidateCurrentLetter', 'cancel must invalidate current confirm letter')
  assertRegex(src, /Schedule::(?:releaseLock|releaseBookingForOrder)\(/, 'cancel must release schedule booking/lock if present')
  assertIncludes(schedule(), 'releaseBookingForOrder', 'schedule model should provide order-scoped safe release to avoid releasing another order after a race')
  assertIncludes(src, 'PackageBooking::releaseByOrderId($orderId)', 'cancel must release package booking locks')
  assertRegex(src, /STATUS_CANCELLED[\s\S]*cancel_time[\s\S]*pay_deadline_time\s*=\s*0/s, 'cancel must move order to cancelled and clear deadlines')
})

check('LOCK-005', '已预约档期不得再次通过可用性校验', () => {
  const src = schedule()
  const bookedAvailablePattern = /status\)\s*===\s*self::STATUS_BOOKED\)\s*\{\s*return\s*\[true/s
  if (bookedAvailablePattern.test(src)) {
    throw new Error('Schedule::isScheduleRecordAvailableWithReason currently returns true for STATUS_BOOKED; this allows booked dates to appear orderable')
  }
})

// P0/P1: mini-program page failure states, retry, back paths.
check('MP-001', '人员详情必须有页面级错误态、重新加载和返回首页路径', () => {
  const src = staffDetailPage()
  assertIncludes(src, '<EmptyState', 'staff detail must render EmptyState on page-level error')
  assertIncludes(src, 'detailError', 'staff detail must hold detailError state')
  assertIncludes(src, 'normalizePageRecoveryError', 'staff detail must normalize recovery errors')
  assertIncludes(src, '@action="handleDetailRecoveryAction"', 'staff detail EmptyState action must bind retry/recovery handler')
  assertRegex(src, /const\s+handleDetailRecoveryAction\s*=|function\s+handleDetailRecoveryAction/, 'staff detail recovery handler must be implemented')
  assertIncludes(src, 'goHome', 'staff detail must provide return-home path')
})

check('MP-002', '订单详情必须有页面级加载失败态、重试和返回路径', () => {
  const src = orderDetailPage()
  assertIncludes(src, '<EmptyState', 'order detail should render EmptyState/page-level error, not only toast/loading')
  assertRegex(src, /detailError|pageError|status\s*=\s*ref\(PageStatusEnum/, 'order detail should track error state')
  assertRegex(src, /重新加载|重试|@action=.*fetchDetail|handle.*Retry/s, 'order detail should expose retry action')
  assertRegex(src, /返回首页|返回订单|goHome|goOrderList|navigateBack|switchTab/s, 'order detail should expose back/recovery path')
})

check('MP-003', '支付结果页必须覆盖错误态、刷新结果、返回首页/订单路径和轮询清理', () => {
  const src = paymentResultPage()
  assertIncludes(src, '<page-status :status="status">', 'payment result must use page-status')
  assertIncludes(src, '<template #error>', 'payment result must have error slot')
  assertIncludes(src, '刷新结果', 'pending payment result must expose refresh action')
  assertIncludes(src, '返回首页', 'payment result error/terminal state must return home')
  assertIncludes(src, 'router.redirectTo(`/packages/pages/order_detail/order_detail', 'order payment result must return to order detail')
  assertIncludes(src, 'clearPollTimer', 'payment result must clear polling timer')
  assertIncludes(src, 'onUnload', 'payment result must clear resources on unload')
})

check('MP-004', '客户侧不得再暴露确认函通知入口或确认函 API', () => {
  const notify = notificationPage()
  const detail = orderDetailPage()
  const orderApi = read('uniapp', 'src', 'api', 'order.ts')
  if (/confirm_letter|confirmLetter|ConfirmLetter/.test(notify)) {
    throw new Error('notification page must not route confirm_letter notifications to customer order detail')
  }
  if (/confirmLetter|ConfirmLetter|订单确认函/.test(detail)) {
    throw new Error('customer order detail must not render or fetch confirm letter')
  }
  if (/confirmLetter(Current|ById|History)|\/order\/confirmLetter/.test(orderApi)) {
    throw new Error('customer order API must not expose confirm letter endpoints')
  }
})

check('MP-005', '订单确认页未支付前不得锁档，失效恢复应回到重新选择', () => {
  const src = orderConfirmPage()
  assertNotIncludes(src, 'ensureBookingLocksForSelection', 'order confirm must not acquire schedule locks before payment')
  assertNotIncludes(src, 'renewAllBookingLocks', 'order confirm must not renew schedule locks before payment')
  assertNotIncludes(src, 'booking-lock-session', 'order confirm must not depend on local booking lock session')
  assertIncludes(src, 'previewOrder(buildSelectionParams())', 'order confirm must use preview API for read-only availability validation')
  assertIncludes(src, 'createOrder(params)', 'order confirm must create unpaid orders without prepayment schedule locks')
  assertIncludes(src, 'isBookingLockError', 'order confirm must still classify schedule unavailable errors')
  assertIncludes(src, 'handleReselect', 'order confirm must let users reselect when schedule becomes unavailable')
  assertIncludes(src, 'getStaffBookingPageUrl(selection)', 'order confirm recovery should return to staff booking selection')
})

// P1/P2 regression anchors.
check('REG-001', 'P1/P2 页面和接口路由必须注册关键入口', () => {
  const pages = pagesJson()
  assertIncludes(pages, 'payment_result/payment_result', 'payment result page must be registered')
  assertIncludes(pages, 'order_confirm/order_confirm', 'order confirm page must be registered')
  assertIncludes(pages, 'couple_questionnaire/detail', 'questionnaire detail page must be registered')
  assertIncludes(pages, 'staff_schedule_confirm_letter/staff_schedule_confirm_letter', 'staff schedule confirm letter page must be registered')
  assertIncludes(read('uniapp', 'src', 'api', 'order.ts'), '/order/uploadVoucher', 'offline voucher upload API must exist')
})

check('REG-002', '订阅/通知/档期确认函/问卷相关迁移必须进入安装 SQL', () => {
  const sql = likeSql()
  assertIncludes(sql, 'la_staff_schedule_confirm_letter_config', 'install SQL should include staff confirm letter config table')
  assertIncludes(sql, 'la_staff_schedule_confirm_letter', 'install SQL should include staff confirm letter record table')
  assertIncludes(sql, '`template_name` varchar(80)', 'staff confirm letter config table must include template_name')
  assertIncludes(sql, '`template_version` int(11)', 'staff confirm letter config table must include template_version')
  assertIncludes(sql, '`is_default` tinyint(1)', 'staff confirm letter config table must include is_default')
  assertIncludes(sql, '`status` tinyint(1)', 'staff confirm letter config table must include template status')
  assertIncludes(sql, '`config_id` int(11)', 'staff confirm letter record table must snapshot selected config_id')
  assertIncludes(sql, '`config_name` varchar(80)', 'staff confirm letter record table must snapshot selected template name')
  assertIncludes(sql, 'idx_staff_status_default', 'staff confirm letter config table must index staff template status/default')
  if (/staff_schedule_confirm_letter_config[\s\S]*UNIQUE KEY `uk_staff_id`/.test(sql)) {
    throw new Error('staff confirm letter config table must not keep unique staff_id constraint')
  }
  assertIncludes(sql, '`design_version` varchar(60)', 'staff confirm letter config table must include design_version')
  assertIncludes(sql, '`design_config` json', 'staff confirm letter config table must include design_config JSON')
  assertIncludes(sql, "DEFAULT 'staff-schedule-designer-v2'", 'install SQL must default new poster renderer version')
  assertIncludes(sql, 'ops.staff/myScheduleConfirmLetterConfig', 'install SQL should include staff-center config permission')
  assertRegex(sql, /couple.*question|questionnaire/i, 'install SQL should include couple questionnaire schema')
  assertRegex(sql, /notification|subscribe|message/i, 'install SQL should include notification/subscribe related schema')
})

check('REG-004', '服务人员档期确认函必须按 staff_id 配置并保护客户隐私', () => {
  const service = staffScheduleConfirmLetterService()
  const adminPage = read('admin', 'src', 'views', 'staff_center', 'schedule_confirm_letter', 'index.vue')
  const adminStaffEditPage = read('admin', 'src', 'views', 'staff', 'lists', 'edit.vue')
  const renderer = read('server', 'app', 'common', 'service', 'StaffScheduleConfirmLetterRenderer.php')
  const designer = staffScheduleDesigner()
  const staffPage = read('uniapp', 'src', 'packages', 'pages', 'staff_schedule_confirm_letter', 'staff_schedule_confirm_letter.vue')
  const staffOrderDetail = read('uniapp', 'src', 'packages', 'pages', 'staff_order_detail', 'staff_order_detail.vue')
  const adminOrderPage = read('admin', 'src', 'views', 'order', 'lists', 'index.vue')
  const adminOrderApi = read('admin', 'src', 'api', 'order.ts')
  const adminOrderController = read('server', 'app', 'adminapi', 'controller', 'order', 'OrderController.php')
  const adminOrderValidate = read('server', 'app', 'adminapi', 'validate', 'order', 'OrderValidate.php')

  assertIncludes(service, 'StaffScheduleConfirmLetterConfig::where(\'staff_id\'', 'config must be loaded by staff_id')
  assertIncludes(service, 'listConfigs', 'service must list multiple staff template versions')
  assertIncludes(service, 'copyConfig', 'service must support copying template versions')
  assertIncludes(service, 'setDefaultConfig', 'service must support setting default template version')
  assertIncludes(service, 'disableConfig', 'service must support soft disabling template versions')
  assertIncludes(service, 'ERROR_CONFIG_LAST_ACTIVE', 'service must prevent disabling the last active template')
  assertIncludes(service, "'config_id' => (int)($config['config_id']", 'generation record must snapshot selected config_id')
  assertIncludes(service, "'config_name' => (string)($config['template_name']", 'generation record must snapshot selected template name')
  assertIncludes(service, 'StaffScheduleConfirmLetter::where(\'order_id\'', 'records must be scoped by order_id')
  assertIncludes(service, "->where('staff_id', $staffId)", 'records/history/detail must be scoped by staff_id')
  assertIncludes(service, "RENDER_SPEC_VERSION = 'staff-schedule-designer-v2'", 'new schedule confirm poster renderer version must be v2')
  assertIncludes(service, 'defaultDesignConfig', 'service must provide default 1080x1920 design config')
  assertIncludes(service, 'normalizeDesignConfig', 'service must normalize arbitrary design config')
  assertIncludes(service, 'mergeLiteDesignConfig', 'service must merge mini-program lite edits into current design')
  assertIncludes(service, 'sanitizeTemplateText', 'service must sanitize template variables before snapshot')
  assertIncludes(service, "'backgroundFill' => self::normalizeOptionalColor", 'service must persist optional image layer background color')
  assertIncludes(service, 'rgba(%d, %d, %d, %s)', 'service must preserve alpha colors from designer color picker')
  assertIncludes(read('server', 'app', 'api', 'logic', 'StaffCenterLogic.php'), "unset($params['design_config'])", 'mini-program preview must ignore full design_config')
  assertIncludes(renderer, 'renderDesigner', 'renderer must render design_config instead of fixed SVG coordinates')
  assertIncludes(renderer, 'renderLayer', 'renderer must render layers dynamically')
  assertIncludes(renderer, '$snapshot[\'variables\']', 'renderer must replace only whitelisted snapshot variables')
  assertIncludes(renderer, 'rgba(%d, %d, %d, %s)', 'renderer must keep alpha colors while rendering designer layers')
  assertIncludes(renderer, 'wrapTextByWidth', 'renderer must wrap text layers by configured layer width')
  assertIncludes(renderer, 'estimateTextWidth', 'renderer must estimate mixed Chinese/number text width before wrapping')
  assertIncludes(renderer, '$backgroundFill = self::normalizeOptionalColor', 'renderer must support image layer background fill for transparent PNGs')
  assertIncludes(renderer, 'clipPathUnits="userSpaceOnUse"', 'renderer must clip image layers with stable rounded-corner clip paths')
  assertIncludes(service, 'maskCustomerAlias', 'snapshot must use masked customer alias')
  assertIncludes(service, 'ERROR_NOT_BOUND', 'generation must require staff bound to order item')
  assertIncludes(service, 'OrderConfirmLetterService::calculateEffectivePaidAmount', 'generation must require paid or locked order state')
  ;['contact_mobile', 'service_address', 'order_total_amount', 'paid_amount', 'remain_amount'].forEach((needle) => {
    if (service.includes(needle)) {
      throw new Error(`staff schedule confirm letter service must not snapshot private field: ${needle}`)
    }
  })
  assertIncludes(adminPage, '<schedule-confirm-letter-designer', 'admin staff center must use reusable designer component')
  assertIncludes(adminPage, 'visibleVersions', 'admin staff center must render template version list')
  assertIncludes(adminPage, 'myScheduleConfirmLetterCopy', 'admin staff center must copy template versions')
  assertIncludes(adminPage, 'myScheduleConfirmLetterSetDefault', 'admin staff center must set default template')
  assertIncludes(adminPage, 'myScheduleConfirmLetterDisable', 'admin staff center must soft disable template')
  assertIncludes(adminPage, 'myScheduleConfirmLetterSave', 'admin staff center must save personal config')
  assertIncludes(adminStaffEditPage, '<schedule-confirm-letter-designer', 'admin staff edit page must use the same designer component')
  assertIncludes(adminStaffEditPage, 'scheduleLetterVisibleVersions', 'admin staff edit page must render template version list')
  assertIncludes(adminStaffEditPage, 'staffScheduleConfirmLetterCopy', 'admin staff edit page must copy template versions')
  assertIncludes(adminStaffEditPage, 'staffScheduleConfirmLetterSetDefault', 'admin staff edit page must set default template')
  assertIncludes(adminStaffEditPage, 'staffScheduleConfirmLetterDisable', 'admin staff edit page must soft disable template')
  assertIncludes(adminStaffEditPage, 'staffScheduleConfirmLetterSave', 'admin staff edit page must save staff-scoped design config')
  assertIncludes(adminOrderPage, '@click="handleConfirmLetter(row)"', 'admin order operation column must expose staff schedule poster action')
  assertIncludes(adminOrderPage, 'const handleConfirmLetter = async (row: any)', 'admin order poster action must open an operation dialog')
  assertIncludes(adminOrderPage, 'confirmLetterVisible.value = true', 'admin order poster action must show a dialog instead of embedding in detail')
  assertIncludes(adminOrderPage, '<el-dialog v-model="confirmLetterVisible" title="档期确认海报"', 'admin order poster generation must be handled in a dialog')
  assertIncludes(adminOrderPage, 'confirmLetterStaffOptions', 'admin order poster dialog must select bound staff before generating poster')
  assertIncludes(adminOrderPage, 'confirmLetterTemplateOptions', 'admin order poster dialog must select a poster template before generating')
  assertIncludes(adminOrderPage, 'submitGenerateConfirmLetter', 'admin order poster dialog must call poster generation handler')
  assertIncludes(adminOrderPage, 'staff_id: Number(confirmLetterForm.staff_id', 'admin order generation must submit selected staff_id')
  assertIncludes(adminOrderPage, 'config_id: Number(confirmLetterForm.config_id', 'admin order generation must submit selected config_id')
  assertIncludes(adminOrderPage, '不会推送给客户', 'admin order generation must not be positioned as customer push')
  assertIncludes(adminOrderApi, '/ops.order/confirmLetterGenerate', 'admin order API must expose backend staff poster generation')
  assertIncludes(adminOrderController, 'appendScheduleConfirmLetterContext', 'admin order detail must return staff poster generation context')
  assertIncludes(adminOrderController, 'checkScheduleConfirmLetterStaffScope', 'admin order poster generation must verify staff is bound to order')
  assertIncludes(adminOrderController, "StaffScheduleConfirmLetterService::generate(\n                $orderId,\n                $staffId,\n                'admin'", 'admin order poster generation must use staff schedule confirm letter service')
  assertIncludes(adminOrderController, '档期确认函不支持推送客户', 'admin order controller must keep customer push disabled')
  assertIncludes(adminOrderValidate, "return $this->only(['id', 'staff_id', 'config_id'])", 'admin order generation validator must require staff/template params')
  assertIncludes(designer, '@mousedown.stop="startDrag', 'designer must support drag move')
  assertIncludes(designer, '@mousedown.stop="startResize', 'designer must support resize')
  assertIncludes(designer, '<svg class="designer-layer__line"', 'designer must preview line layers with the same coordinate model as SVG rendering')
  assertIncludes(designer, "activeLayer.type === 'line' ? 0 : 1", 'designer must allow zero-height horizontal divider layers')
  assertIncludes(designer, 'draggable="false"', 'designer image layers must disable native browser image dragging')
  assertIncludes(designer, '@dragstart.prevent', 'designer image layers must prevent dragstart opening image tabs')
  assertIncludes(designer, 'imageStyle', 'designer image preview must apply fit and radius settings')
  assertIncludes(designer, 'imageBoxStyle', 'designer image preview must render optional layer background color')
  assertIncludes(designer, 'activeLayer.backgroundFill', 'designer image layer must expose a background color control')
  assertIncludes(designer, 'alignShortcutActions', 'designer must expose layer alignment shortcut actions')
  assertIncludes(designer, 'applyLayerAlign', 'designer must implement one-click layer alignment')
  assertIncludes(designer, '一键水平居中', 'designer alignment shortcuts must include horizontal center tooltip')
  assertIncludes(designer, '一键垂直居中', 'designer alignment shortcuts must include vertical center tooltip')
  assertIncludes(designer, 'textAlignActions', 'designer must expose text alignment shortcuts')
  assertIncludes(designer, '<el-tooltip', 'designer alignment shortcuts must show function names on hover')
  assertIncludes(designer, 'addVariableLayer', 'designer must support variable text layer')
  assertIncludes(designer, 'dynamicFields', 'designer must explain available dynamic fields')
  assertIncludes(designer, 'insertDynamicField', 'designer must support one-click inserting dynamic fields')
  assertIncludes(designer, 'addQrcodeLayer', 'designer must support qrcode layer')
  assertIncludes(staffPage, 'editableFields', 'mini-program staff config page must respect editable_fields')
  assertIncludes(staffPage, 'dynamicFields', 'mini-program staff config page must explain available dynamic fields')
  assertIncludes(staffPage, 'insertDynamicField', 'mini-program staff config page must support one-click inserting fields')
  assertIncludes(staffPage, 'versions', 'mini-program staff config page must list enabled template versions')
  assertIncludes(staffPage, 'switchTemplate', 'mini-program staff config page must allow switching template versions')
  assertIncludes(staffPage, 'staffCenterScheduleConfirmLetterSaveConfig', 'mini-program staff config page must save personal config')
  assertIncludes(staffPage, 'staffCenterScheduleConfirmLetterSaveConfig({', 'mini-program lite save must call lite save API')
  assertIncludes(staffPage, 'config_id: activeConfigId.value', 'mini-program lite save must target selected config_id')
  if (/staffCenterScheduleConfirmLetterSaveConfig\(\{[\s\S]*design_config/.test(staffPage)) {
    throw new Error('mini-program lite save must not submit full design_config')
  }
  if (/startDrag|startResize|@mousedown|@touchstart/.test(staffPage)) {
    throw new Error('mini-program staff config page must not expose drag canvas editing')
  }
  assertIncludes(staffOrderDetail, '生成海报', 'staff order detail must generate poster')
  assertIncludes(staffOrderDetail, 'selectConfirmLetterTemplate', 'staff order detail must select template before generating poster')
  assertIncludes(staffOrderDetail, 'config_id: Number(template.config_id', 'staff order detail generate API must include selected config_id')
  assertIncludes(staffOrderDetail, '切换历史海报', 'staff order detail must distinguish generated history from template versions')
  assertIncludes(staffOrderDetail, 'config_name', 'staff order detail history must show template name')
  assertIncludes(staffOrderDetail, '保存图片', 'staff order detail must save poster image')
  if (/推送给客户|orderConfirmLetterPush/.test(staffOrderDetail)) {
    throw new Error('staff order detail must not expose push-to-customer action')
  }
})

check('REG-003', 'P1/P2 工程治理契约必须落地 request_id、OpenAPI、状态机和迁移规范', () => {
  assertIncludes(read('server', 'app', 'common', 'service', 'RequestContextService.php'), 'HEADER_REQUEST_ID', 'RequestContextService must define X-Request-Id')
  assertIncludes(read('server', 'app', 'common', 'service', 'JsonService.php'), "result['request_id']", 'JsonService must add request_id to response body')
  assertIncludes(read('server', 'app', 'common', 'contract', 'CoreStateContract.php'), 'ORDER_TRANSITIONS', 'CoreStateContract must define order transitions')
  assertIncludes(read('docs', 'contracts', 'openapi-core.yaml'), '/pay/notifyMnp:', 'OpenAPI core contract must cover payment callback')
  assertIncludes(read('docs', 'ops', 'database-migration.md'), 'server/sql/20260523_couple_questionnaire_schema.sql', 'migration doc must mention questionnaire old-db schema')
  assertIncludes(read('shared', 'contracts', 'core.ts'), 'export interface ApiEnvelope', 'shared TS contract must define ApiEnvelope')
})

let failed = 0
for (const item of checks) {
  try {
    item.fn()
    console.log(`PASS ${item.id} ${item.title}`)
  } catch (error) {
    failed += 1
    console.error(`FAIL ${item.id} ${item.title}`)
    console.error(`     ${error.message}`)
  }
}

const passed = checks.length - failed
console.log(`\nQA contract checks: ${passed}/${checks.length} passed, ${failed} failed.`)

if (failed > 0) {
  process.exitCode = 1
}
