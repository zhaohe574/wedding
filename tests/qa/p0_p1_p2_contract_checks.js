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
const apiStaffLogic = () => read('server', 'app', 'api', 'logic', 'StaffLogic.php')
const consoleConfig = () => read('server', 'config', 'console.php')
const activityRegistrationMigration = () => read('server', 'sql', '1.9.0.20260615', 'update.sql')
const staffDetailPage = () => read('uniapp', 'src', 'packages', 'pages', 'staff_detail', 'staff_detail.vue')
const orderDetailPage = () => read('uniapp', 'src', 'packages', 'pages', 'order_detail', 'order_detail.vue')
const paymentResultPage = () => read('uniapp', 'src', 'packages', 'pages', 'payment_result', 'payment_result.vue')
const notificationPage = () => read('uniapp', 'src', 'packages', 'pages', 'notification', 'index.vue')
const orderConfirmPage = () => read('uniapp', 'src', 'packages', 'pages', 'order_confirm', 'order_confirm.vue')
const pagesJson = () => read('uniapp', 'src', 'pages.json')
const likeSql = () => read('server', 'public', 'install', 'db', 'like.sql')
const exampleEnv = () => read('server', '.example.env')
const featureSwitchPage = () => read('admin', 'src', 'views', 'setting', 'feature_switch', 'index.vue')
const featureSwitchValidate = () => read('server', 'app', 'adminapi', 'validate', 'setting', 'FeatureSwitchValidate.php')
const featureSwitchLogic = () => read('server', 'app', 'adminapi', 'logic', 'setting', 'FeatureSwitchLogic.php')
const staffScheduleDesigner = () => read('admin', 'src', 'components', 'staff', 'schedule-confirm-letter-designer.vue')
const decoratePageLogic = () => read('server', 'app', 'adminapi', 'logic', 'decorate', 'DecoratePageLogic.php')
const homeServiceCategoriesAttr = () => read('admin', 'src', 'views', 'decoration', 'component', 'widgets', 'home-service-categories', 'attr.vue')
const homeFeatureCarouselAttr = () => read('admin', 'src', 'views', 'decoration', 'component', 'widgets', 'home-feature-carousel', 'attr.vue')
const staffSettlementService = () => read('server', 'app', 'common', 'service', 'StaffSettlementService.php')
const adminRefundLogic = () => read('server', 'app', 'adminapi', 'logic', 'order', 'RefundLogic.php')
const staffCompanyFeeUsage = () => read('server', 'app', 'common', 'model', 'financial', 'StaffCompanyFeeUsage.php')
const settlementBatch = () => read('server', 'app', 'common', 'model', 'financial', 'SettlementBatch.php')
const staffSettlementPage = () => read('uniapp', 'src', 'packages', 'pages', 'staff_settlement', 'staff_settlement.vue')

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

check('PAY-007', '比例定金拆分必须按尾款向上凑整并支持百位单位', () => {
  const model = orderModel()
  const page = featureSwitchPage()
  const validate = featureSwitchValidate()
  const logic = featureSwitchLogic()
  assertIncludes(page, '尾款向上凑整', 'admin setting must name rounding as balance rounding')
  assertIncludes(page, '凑到百位元', 'admin setting must expose hundred-yuan rounding unit')
  assertIncludes(page, '定金允许保留小数', 'admin setting must explain non-integer deposit amount')
  assertIncludes(validate, "'deposit_rounding_unit' => 'in:1,10,100'", 'feature switch validator must accept 1/10/100 rounding units')
  assertIncludes(logic, '[1, 10, 100]', 'feature switch logic must normalize hundred-yuan rounding unit')
  assertIncludes(model, 'roundBalanceAmountUp', 'order split must round balance through a dedicated helper')
  assertIncludes(model, 'roundRatioPaymentSplitByBalance', 'order split must derive ratio payment split from rounded balance')
  assertIncludes(model, '$rawBalanceAmount = round(max($normalizedPayAmount - $depositAmount, 0), 2);', 'order split must compute raw balance before rounding')
  assertIncludes(model, '$depositAmount = round($normalizedPayAmount - $balanceAmount, 2);', 'order split must derive deposit from rounded balance')
  assertNotIncludes(model, 'roundDepositAmountUp', 'order split must no longer round the deposit amount directly')
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

check('REG-005', '移动端装修服务分类允许置空并保持空列表', () => {
  const attr = homeServiceCategoriesAttr()
  const decorate = decoratePageLogic()
  const homePage = read('uniapp', 'src', 'pages', 'index', 'index.vue')
  assertNotIncludes(attr, '最少保留一个分类', 'home service categories widget must allow deleting all categories')
  assertIncludes(decorate, "$widgetName === 'home-service-categories' && $hasRawContentData", 'server must preserve explicit empty home-service-categories data')
  assertIncludes(decorate, "$widget['content']['data'] = $rawContentData", 'server must not replace explicit empty home-service-categories data with defaults')
  assertIncludes(homePage, 'hasConfiguredData', 'mini-program home page must distinguish explicit empty service categories from missing config')
  assertIncludes(homePage, 'if (hasConfiguredData) {\n        return []', 'mini-program home page must not fallback to default categories when service categories are explicitly empty')
})

check('REG-006', '预约图片区高度上限必须允许大图配置并在小程序生效', () => {
  const attr = homeFeatureCarouselAttr()
  const homePage = read('uniapp', 'src', 'pages', 'index', 'index.vue')
  assertIncludes(attr, 'const maxHeight = 900', 'admin feature carousel height max must be raised to 900')
  assertIncludes(attr, ':max="maxHeight"', 'admin feature carousel height input must use raised max height')
  assertIncludes(homePage, 'const MAX_FEATURE_HEIGHT = 900', 'mini-program feature carousel must keep the same max height')
  assertIncludes(homePage, 'MAX_FEATURE_HEIGHT', 'mini-program feature height clamp must use raised max height')
})

check('REG-007', '服务人员详情页必须使用后台配置的主页轮播图高度', () => {
  const logic = apiStaffLogic()
  const detail = staffDetailPage()
  assertIncludes(logic, 'getStaffBannerConfig', 'staff detail API must explicitly return staff banner config')
  assertIncludes(logic, "'banner_small_height' => self::readPositiveStaffNumber", 'staff detail API must return small banner height')
  assertIncludes(logic, "'banner_large_height' => self::readPositiveStaffNumber", 'staff detail API must return large banner height')
  assertIncludes(detail, 'normalizePositiveConfigNumber', 'staff detail page must normalize configured banner heights')
  assertIncludes(detail, 'data?.banner_small_height', 'staff detail page must read small banner height from API')
  assertIncludes(detail, 'data?.banner_large_height', 'staff detail page must read large banner height from API')
  assertNotIncludes(detail, 'if (data?.banner_mode === undefined)', 'staff detail page must not ignore height config when banner_mode is absent')
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
  assertIncludes(service, "CONFIG_KEY_SCHEDULE_QRCODE_IMAGE = 'schedule_qrcode_image'", 'staff poster qrcode must use global system config key')
  assertIncludes(service, 'setGlobalQrcodeConfig', 'system settings must save global schedule poster qrcode')
  assertIncludes(service, 'requireGlobalQrcodeImage', 'poster preview/generation must require global qrcode before rendering')
  assertIncludes(service, 'QRCODE_MIN_SIZE = 160', 'qrcode layer must have a backend minimum size')
  assertIncludes(service, "'opacity' => $type === 'qrcode' ? 1", 'backend must ignore qrcode opacity overrides')
  assertIncludes(service, "'show_qrcode' => 1", 'staff poster config must force qrcode enabled')
  assertIncludes(service, "'fit' => self::normalizeBackgroundFit", 'service must persist background image fit mode')
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
  assertIncludes(renderer, "$backgroundFit = (string)($background['fit']", 'renderer must read background image fit mode')
  assertIncludes(renderer, 'StaffScheduleConfirmLetterService::QRCODE_MIN_SIZE', 'renderer must clamp qrcode layer size')
  assertIncludes(renderer, "$type === 'qrcode' ? 1.0", 'renderer must force qrcode full opacity')
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
  assertIncludes(designer, "layer?.type === 'line'", 'designer must allow zero-height horizontal divider layers')
  assertIncludes(designer, 'draggable="false"', 'designer image layers must disable native browser image dragging')
  assertIncludes(designer, '@dragstart.prevent', 'designer image layers must prevent dragstart opening image tabs')
  assertIncludes(designer, 'imageStyle', 'designer image preview must apply fit and radius settings')
  assertIncludes(designer, 'imageBoxStyle', 'designer image preview must render optional layer background color')
  assertIncludes(designer, 'activeLayer.backgroundFill', 'designer image layer must expose a background color control')
  assertIncludes(designer, 'localDesign.background.fit', 'designer background tab must expose background image fit mode')
  assertIncludes(designer, '铺满裁剪', 'designer must name cover mode for background images')
  assertIncludes(designer, '完整显示', 'designer must name contain mode for background images')
  assertIncludes(designer, 'qrcodeMinSize = 160', 'designer must prevent qrcode being resized below minimum')
  assertIncludes(designer, 'isRequiredQrcodeLayer', 'designer must treat qrcode as required layer')
  assertIncludes(designer, ':disabled="isRequiredQrcodeLayer(layer)"', 'designer must prevent hiding required qrcode layer')
  assertIncludes(designer, ':disabled="!activeLayer || isRequiredQrcodeLayer(activeLayer)"', 'designer must prevent deleting/copying required qrcode layer')
  assertIncludes(designer, 'v-if="!isRequiredQrcodeLayer(activeLayer)" label="透明度"', 'designer must not expose qrcode opacity control')
  assertIncludes(designer, 'opacity: isRequiredQrcodeLayer(layer) ? 1', 'designer must preview qrcode at full opacity')
  assertIncludes(designer, '系统统一二维码', 'designer must not expose personal qrcode image picker')
  if (/function\s+addQrcodeLayer|@click="addQrcodeLayer"/.test(designer)) {
    throw new Error('designer must not allow adding extra qrcode layers')
  }
  if (/activeLayer\.type === 'qrcode'[\s\S]{0,260}<material-picker/.test(designer)) {
    throw new Error('designer qrcode panel must not expose material picker')
  }
  assertIncludes(designer, 'alignShortcutActions', 'designer must expose layer alignment shortcut actions')
  assertIncludes(designer, 'applyLayerAlign', 'designer must implement one-click layer alignment')
  assertIncludes(designer, '一键水平居中', 'designer alignment shortcuts must include horizontal center tooltip')
  assertIncludes(designer, '一键垂直居中', 'designer alignment shortcuts must include vertical center tooltip')
  assertIncludes(designer, 'textAlignActions', 'designer must expose text alignment shortcuts')
  assertIncludes(designer, '<el-tooltip', 'designer alignment shortcuts must show function names on hover')
  assertIncludes(designer, 'addVariableLayer', 'designer must support variable text layer')
  assertIncludes(designer, 'dynamicFields', 'designer must explain available dynamic fields')
  assertIncludes(designer, 'insertDynamicField', 'designer must support one-click inserting dynamic fields')
  assertIncludes(read('admin', 'src', 'views', 'setting', 'order_confirm_letter', 'index.vue'), 'schedule_qrcode_image', 'system settings page must configure global staff poster qrcode')
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

check('REG-009', '服务人员中心订单管理必须支持受限线下建单', () => {
  const adminOrderPage = read('admin', 'src', 'views', 'order', 'lists', 'index.vue')
  const staffCenterOrderPage = read('admin', 'src', 'views', 'staff_center', 'order', 'index.vue')
  const offlineDrawer = read('admin', 'src', 'components', 'order', 'offline-order-drawer.vue')
  const staffCenterApi = read('admin', 'src', 'api', 'staff-center.ts')
  const serviceApi = read('admin', 'src', 'api', 'service.ts')
  const consumerApi = read('admin', 'src', 'api', 'consumer.ts')
  const authMiddleware = read('server', 'app', 'adminapi', 'http', 'middleware', 'AuthMiddleware.php')
  const orderController = read('server', 'app', 'adminapi', 'controller', 'order', 'OrderController.php')

  assertIncludes(offlineDrawer, 'fixedMainStaff', 'offline drawer must support fixed main staff for staff center')
  assertIncludes(offlineDrawer, 'props.addOffline(buildPayload())', 'offline drawer must submit through injected addOffline API')
  assertIncludes(offlineDrawer, 'props.offlineRoleCandidates', 'offline drawer must keep collaborator candidate selection')
  assertIncludes(adminOrderPage, '<offline-order-drawer', 'admin order page must reuse offline order drawer')
  assertIncludes(adminOrderPage, ':load-staff-options="staffAll"', 'admin order page must still allow selecting main staff')
  assertIncludes(staffCenterOrderPage, '线下建单', 'staff center order page must expose offline create entry')
  assertIncludes(staffCenterOrderPage, '<offline-order-drawer', 'staff center order page must reuse offline order drawer')
  assertIncludes(staffCenterOrderPage, ':fixed-main-staff="currentStaffMainOption"', 'staff center order page must fix main staff to current profile')
  assertIncludes(staffCenterOrderPage, 'myProfile', 'staff center order page must load current staff profile')
  assertIncludes(staffCenterApi, '/ops.order/offlineMainPackages', 'staff center API must wrap offline main package endpoint')
  assertIncludes(staffCenterApi, '/ops.order/offlineRoleCandidates', 'staff center API must wrap offline role candidate endpoint')
  assertIncludes(staffCenterApi, '/ops.order/estimateOffline', 'staff center API must wrap offline estimate endpoint')
  assertIncludes(staffCenterApi, '/ops.order/addOffline', 'staff center API must wrap offline add endpoint')
  assertIncludes(staffCenterApi, '/ops.staff/getAddonConfig', 'staff center API must use read-only addon config endpoint')
  assertIncludes(serviceApi, '/ops.region/enabledCityOptions', 'offline drawer must load enabled city options through region API')
  assertIncludes(serviceApi, '/ops.region/districtOptions', 'offline drawer must load district options through region API')
  assertIncludes(consumerApi, '/content.user/lists', 'offline drawer must support customer search through user list API')
  ;[
    'ops.region/enabledCityOptions',
    'ops.region/districtOptions',
    'ops.order/offlineMainPackages',
    'ops.order/offlineRoleCandidates',
    'ops.order/estimateOffline',
    'ops.order/addOffline',
    'ops.staff/getAddonConfig',
    'content.user/lists'
  ].forEach((uri) => {
    assertIncludes(authMiddleware, uri, `staff self-service auth allowlist must include ${uri}`)
  })
  assertNotIncludes(staffCenterOrderPage, 'staffAll', 'staff center order page must not load all staff for main staff selection')
  assertIncludes(orderController, 'applyOfflineMainStaffScope', 'backend must keep staff-scoped offline main staff guard')
})

check('REG-010', '后台登录 IP 变化检测必须支持环境开关', () => {
  const authMiddleware = read('server', 'app', 'adminapi', 'http', 'middleware', 'AuthMiddleware.php')
  const envExample = exampleEnv()

  assertIncludes(authMiddleware, 'isLoginIpCheckEnabled', 'auth middleware must wrap login IP check behind an env switch')
  assertIncludes(authMiddleware, "env('admin.check_login_ip'", 'auth middleware must read [ADMIN] CHECK_LOGIN_IP')
  assertIncludes(authMiddleware, "env('admin_check_login_ip'", 'auth middleware must support top-level ADMIN_CHECK_LOGIN_IP')
  assertIncludes(envExample, '[ADMIN]', 'example env must document admin section')
  assertIncludes(envExample, 'CHECK_LOGIN_IP = "1"', 'example env must document default login IP check setting')
})

check('REG-011', '后台标签审核页必须支持批量通过和批量拒绝', () => {
  const tagReviewPage = read('admin', 'src', 'views', 'staff', 'tag_review', 'index.vue')
  const tagReviewApi = read('admin', 'src', 'api', 'staff-tag-review.ts')
  const staffCenterApi = read('admin', 'src', 'api', 'staff-center.ts')
  const controller = read('server', 'app', 'adminapi', 'controller', 'staff', 'StaffTagReviewController.php')
  const logic = read('server', 'app', 'adminapi', 'logic', 'staff', 'StaffTagReviewLogic.php')
  const validate = read('server', 'app', 'adminapi', 'validate', 'staff', 'StaffTagReviewValidate.php')
  const authMiddleware = read('server', 'app', 'adminapi', 'http', 'middleware', 'AuthMiddleware.php')
  const sql = likeSql()

  assertIncludes(tagReviewPage, 'type="selection"', 'staff tag review page must expose table selection')
  assertIncludes(tagReviewPage, 'selectedIds', 'staff tag review page must track selected ids')
  assertIncludes(tagReviewPage, '批量通过', 'staff tag review page must expose batch approve button')
  assertIncludes(tagReviewPage, '批量拒绝', 'staff tag review page must expose batch reject button')
  assertIncludes(tagReviewPage, "v-perms=\"['ops.staffTagReview/batchApprove']\"", 'batch approve button must use dedicated permission')
  assertIncludes(tagReviewPage, "v-perms=\"['ops.staffTagReview/batchReject']\"", 'batch reject button must use dedicated permission')
  assertIncludes(tagReviewApi, '/ops.staffTagReview/batchApprove', 'admin API must wrap staff tag batch approve endpoint')
  assertIncludes(tagReviewApi, '/ops.staffTagReview/batchReject', 'admin API must wrap staff tag batch reject endpoint')
  assertIncludes(staffCenterApi, '/ops.staffTagReview/batchApprove', 'staff center API must wrap staff tag batch approve endpoint')
  assertIncludes(staffCenterApi, '/ops.staffTagReview/batchReject', 'staff center API must wrap staff tag batch reject endpoint')
  assertIncludes(controller, 'public function batchApprove()', 'controller must expose batchApprove endpoint')
  assertIncludes(controller, 'public function batchReject()', 'controller must expose batchReject endpoint')
  assertIncludes(controller, 'filterAllowedApplyIds', 'batch tag review must keep per-record staff scope filtering')
  assertIncludes(logic, 'public static function batchApprove', 'logic must implement batch approve')
  assertIncludes(logic, 'public static function batchReject', 'logic must implement batch reject')
  assertIncludes(validate, 'sceneBatchApprove', 'validate must define batchApprove scene')
  assertIncludes(validate, 'sceneBatchReject', 'validate must define batchReject scene')
  assertIncludes(authMiddleware, 'ops.staffTagReview/batchApprove', 'staff leader allowlist must include batch approve endpoint')
  assertIncludes(authMiddleware, 'ops.staffTagReview/batchReject', 'staff leader allowlist must include batch reject endpoint')
  assertIncludes(sql, 'ops.staffTagReview/batchApprove', 'install SQL must seed batch approve permission')
  assertIncludes(sql, 'ops.staffTagReview/batchReject', 'install SQL must seed batch reject permission')
})

check('REG-003', 'P1/P2 工程治理契约必须落地 request_id、OpenAPI、状态机和迁移规范', () => {
  assertIncludes(read('server', 'app', 'common', 'service', 'RequestContextService.php'), 'HEADER_REQUEST_ID', 'RequestContextService must define X-Request-Id')
  assertIncludes(read('server', 'app', 'common', 'service', 'JsonService.php'), "result['request_id']", 'JsonService must add request_id to response body')
  assertIncludes(read('server', 'app', 'common', 'contract', 'CoreStateContract.php'), 'ORDER_TRANSITIONS', 'CoreStateContract must define order transitions')
  assertIncludes(read('docs', 'contracts', 'openapi-core.yaml'), '/pay/notifyMnp:', 'OpenAPI core contract must cover payment callback')
  assertIncludes(read('docs', 'ops', 'database-migration.md'), 'server/sql/1.10.1.20260622/security_payment_permission.sql', 'migration doc must mention current security/payment migration')
  assertIncludes(read('shared', 'contracts', 'core.ts'), 'export interface ApiEnvelope', 'shared TS contract must define ApiEnvelope')
})

check('SETTLE-001', '服务人员结算必须按订单项幂等生成并有数据库唯一约束', () => {
  assertIncludes(likeSql(), 'UNIQUE KEY `uk_order_item_id` (`order_item_id`)', 'install SQL must make staff settlement unique by order_item_id')
  assertIncludes(read('server', 'sql', '1.10.5.20260628', 'staff_settlement_refund_guard.sql'), 'ADD UNIQUE KEY `uk_order_item_id` (`order_item_id`)', 'migration must add unique order_item_id key')
  assertIncludes(staffSettlementService(), 'isDuplicateKeyException', 'settlement generation must tolerate duplicate key races')
})

check('SETTLE-002', '退款前必须处理服务人员结算，已进入资金链路需阻断', () => {
  assertIncludes(staffSettlementService(), 'guardRefundForOrder', 'settlement service must expose refund guard')
  assertIncludes(staffSettlementService(), 'cancelForRefund', 'pending or failed settlements must be cancelled inside refund transaction')
  assertIncludes(staffSettlementService(), 'REFUND_BLOCKED_MESSAGE', 'refund guard must expose stable blocked message')
  assertIncludes(staffSettlementService(), 'STATUS_TRANSFER_PROCESSING', 'refund guard must block transfer-processing settlements')
  assertIncludes(staffSettlementService(), 'STATUS_SETTLED', 'refund guard must block settled settlements')
  assertIncludes(adminRefundLogic(), 'StaffSettlementService::guardRefundForOrder', 'admin refund apply must call settlement refund guard')
})

check('SETTLE-003', '包月扣费累计必须处理首次创建并发唯一键冲突', () => {
  const src = staffCompanyFeeUsage()
  assertIncludes(src, 'findMonthlyUsageForUpdate', 'monthly fee usage must lock existing monthly row')
  assertIncludes(src, 'isDuplicateKeyException', 'monthly fee usage must detect duplicate key race')
  assertRegex(src, /return\s+self::consumeMonthlyFee\(\$staffId,\s*\$ruleConfigId,\s*\$serviceDate,\s*\$monthlyFee,\s*\$orderAmount\)/s, 'duplicate insert race must retry monthly fee calculation')
})

check('SETTLE-004', '结算批次必须区分已发起、待确认和已到账', () => {
  const src = settlementBatch()
  assertIncludes(src, 'sent_count', 'batch execute response must include sent_count')
  assertIncludes(src, 'settled_count', 'batch execute response must include settled_count')
  assertIncludes(src, 'wait_confirm_count', 'batch execute response must include wait_confirm_count')
  assertIncludes(src, 'getTransferStatusCounts', 'batch execute must inspect transfer status counts')
})

check('SETTLE-005', '服务人员端确认微信转账后必须立即同步状态', () => {
  const src = staffSettlementPage()
  assertRegex(src, /requestMerchantTransfer\s*=\s*\(\s*item:\s*DisplaySettlementItem/s, 'confirm transfer helper must receive settlement item id')
  assertIncludes(src, 'await staffCenterSettlementSync({ id: item.id })', 'merchant transfer success must sync settlement status immediately')
})

check('SETTLE-006', '订单结算必须按平台实收抵扣平台抽成并支持补收', () => {
  const service = staffSettlementService()
  const model = read('server', 'app', 'common', 'model', 'financial', 'StaffSettlement.php')
  const repayModel = read('server', 'app', 'common', 'model', 'financial', 'StaffSettlementRepay.php')
  const repayService = read('server', 'app', 'common', 'service', 'StaffSettlementRepayService.php')
  const paymentLogic = read('server', 'app', 'common', 'logic', 'PaymentLogic.php')
  const payNotify = read('server', 'app', 'common', 'logic', 'PayNotifyLogic.php')
  const wechat = read('server', 'app', 'common', 'service', 'pay', 'WeChatPayService.php')
  const alipay = read('server', 'app', 'common', 'service', 'pay', 'AliPayService.php')
  const logic = read('server', 'app', 'adminapi', 'logic', 'financial', 'SettlementLogic.php')
  const list = read('server', 'app', 'adminapi', 'lists', 'financial', 'StaffSettlementLists.php')
  const batch = settlementBatch()
  const adminPage = read('admin', 'src', 'views', 'financial', 'settlement', 'index.vue')
  const staffPage = staffSettlementPage()
  const daily = read('server', 'app', 'common', 'model', 'financial', 'FinancialDaily.php')
  const sql = read('server', 'sql', '1.10.6.20260702', 'platform_paid_repay_settlement.sql')

  assertIncludes(model, 'STATUS_NO_PAYOUT', 'staff settlement must define no-payout status')
  assertIncludes(model, 'SETTLE_WAY_NO_PAYOUT', 'staff settlement must define no-payout settle way')
  assertIncludes(model, 'DUE_COLLECT_STATUS_PENDING', 'staff settlement must define due collection status')
  assertIncludes(model, 'getDuePlatformLeftAmount', 'staff settlement must expose due-platform left amount')
  assertIncludes(model, 'recordDueCollectionFlow', 'due collection must write platform fee financial flow')
  assertIncludes(model, "'无需打款'", 'status and settle way text must expose no-payout copy')
  assertIncludes(service, 'getOrderPlatformPaidNetAmount', 'settlement generation must compute platform paid net amount')
  assertIncludes(service, "where('pay_way', '<>', Payment::WAY_OFFLINE)", 'platform paid net amount must exclude offline payment rows')
  assertIncludes(service, 'allocatePlatformPaidShares', 'platform paid net amount must be allocated by order item amount ratio')
  assertIncludes(service, "'platform_paid_share_amount' => $platformPaidShareAmount", 'settlement row must snapshot platform paid share amount')
  assertIncludes(service, "'staff_due_platform_amount' => $staffDuePlatformAmount", 'settlement row must save staff due platform amount')
  assertIncludes(service, '$platformPaidShareAmount > $platformAmount', 'settlement calculation must first compare platform paid share with platform commission')
  assertIncludes(service, '平台实收不足或无可打款金额，无需向服务人员打款', 'transfer service must reject no-payout rows by platform-paid semantics')
  assertIncludes(repayModel, "protected $name = 'staff_settlement_repay'", 'due collection model must use staff settlement repay table')
  assertIncludes(repayService, "PAY_FROM = 'staff_settlement_repay'", 'due collection online pay branch must have stable pay-from key')
  assertIncludes(repayService, 'manualCollect', 'admin must be able to manually collect offline due amount')
  assertIncludes(repayService, 'paySuccess', 'online due payment callback must update settlement due collection')
  assertIncludes(repayService, 'validateCallbackAmount', 'online due payment callback must validate paid amount')
  assertIncludes(paymentLogic, 'StaffSettlementRepayService::PAY_FROM', 'common payment logic must route due collection pay branch')
  assertIncludes(paymentLogic, "['recharge', StaffSettlementRepayService::PAY_FROM]", 'due collection must not expose balance payment')
  assertIncludes(payNotify, 'staff_settlement_repay', 'pay notify logic must expose due collection callback action')
  assertIncludes(wechat, 'StaffSettlementRepayService::PAY_FROM', 'wechat callback must route due collection payments')
  assertIncludes(alipay, 'StaffSettlementRepayService::PAY_FROM', 'alipay callback must route due collection payments')
  assertIncludes(logic, 'platform_commission_amount', 'settlement statistics/detail must return platform commission amount')
  assertIncludes(logic, 'platform_paid_share_amount', 'settlement statistics/detail must return platform paid share amount')
  assertIncludes(logic, 'staff_due_left_amount', 'settlement statistics/detail must return due collection balance')
  assertIncludes(logic, 'collectDue', 'admin settlement logic must support offline due collection')
  assertIncludes(list, 'is_no_payout', 'settlement list must expose no-payout flag')
  assertIncludes(list, 'staff_due_collect_status_text', 'settlement list/export must expose due collection status')
  assertIncludes(batch, 'SETTLE_WAY_NO_PAYOUT', 'batch execution must exclude no-payout rows')
  assertIncludes(adminPage, 'isTransferSelectable', 'admin list must prevent selecting no-payout rows for transfer')
  assertIncludes(adminPage, '平台抽成', 'admin list must display platform commission')
  assertIncludes(adminPage, '补入线下收款', 'admin list/detail must provide offline due collection action')
  assertIncludes(staffPage, 'from="staff_settlement_repay"', 'staff center settlement page must support online due payment')
  assertIncludes(staffPage, 'can_repay_platform', 'staff center settlement page must expose due repay action')
  assertIncludes(daily, "sum('platform_amount')", 'financial daily platform income must use per-order platform commission')
  assertIncludes(sql, 'platform_paid_share_amount', 'migration must add and recalculate platform paid share amount')
  assertIncludes(sql, 'staff_due_platform_amount', 'migration must add and recalculate staff due platform amount')
  assertIncludes(sql, 'la_staff_settlement_repay', 'migration must add due collection record table')
  assertIncludes(sql, '`pay_way` <> 4', 'migration must calculate platform paid net amount from non-offline payments')
  assertIncludes(sql, '人工核对清单', 'migration must leave settled/processing historical rows for manual review')
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
