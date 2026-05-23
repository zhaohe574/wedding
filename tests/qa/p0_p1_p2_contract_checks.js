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
const staffDetailPage = () => read('uniapp', 'src', 'packages', 'pages', 'staff_detail', 'staff_detail.vue')
const orderDetailPage = () => read('uniapp', 'src', 'pages', 'order_detail', 'order_detail.vue')
const paymentResultPage = () => read('uniapp', 'src', 'pages', 'payment_result', 'payment_result.vue')
const notificationPage = () => read('uniapp', 'src', 'packages', 'pages', 'notification', 'index.vue')
const orderConfirmPage = () => read('uniapp', 'src', 'packages', 'pages', 'order_confirm', 'order_confirm.vue')
const pagesJson = () => read('uniapp', 'src', 'pages.json')
const likeSql = () => read('server', 'public', 'install', 'db', 'like.sql')

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

check('LOCK-002', '订单生成必须在事务中校验锁归属并确认预订', () => {
  const src = orderLogic()
  assertRegex(src, /public\s+static\s+function\s+createOrder[\s\S]*Db::startTrans\(\)/, 'createOrder must start a transaction')
  assertIncludes(src, 'ensureTempLockOwned', 'createOrder must verify user owns temporary lock')
  assertIncludes(src, 'Order::createOrder($userId, $selectedItems, $params)', 'API logic must delegate to atomic Order::createOrder')
  const model = orderModel()
  assertIncludes(model, 'PackageBooking::confirmSelection', 'order model must confirm package booking after order item creation')
  assertIncludes(model, 'Db::commit()', 'order model must commit only after order/items/booking are consistent')
})

check('LOCK-003', '档期锁定必须使用条件更新/版本防并发覆盖', () => {
  const src = schedule()
  assertIncludes(src, 'RedisLockService::lockScheduleWithRedis', 'distributed lock wrapper should protect schedule lock')
  assertCountAtLeast(src, "where('version'", 2, 'schedule lock/confirm must update by version')
  assertIncludes(src, "where('status', self::STATUS_LOCKED)", 'owned-lock renewal must condition on locked status')
  assertIncludes(src, '档期已被其他用户抢占', 'concurrent update failure must expose explicit conflict message')
})

check('LOCK-004', '取消订单必须释放套餐锁/档期并清空确认函', () => {
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
  assertIncludes(src, 'router.redirectTo(`/pages/order_detail/order_detail', 'order payment result must return to order detail')
  assertIncludes(src, 'clearPollTimer', 'payment result must clear polling timer')
  assertIncludes(src, 'onUnload', 'payment result must clear resources on unload')
})

check('MP-004', '确认函通知入口必须支持缺失/过期确认函兜底跳转', () => {
  const notify = notificationPage()
  const detail = orderDetailPage()
  assertIncludes(notify, 'confirm_letter', 'notification route map must include confirm_letter')
  assertIncludes(notify, 'entry=confirm_letter_notification', 'notification must mark confirm letter entry')
  assertIncludes(detail, 'handleMissingNotificationConfirmLetter', 'order detail must handle missing notification confirm letter')
  assertIncludes(detail, 'confirmLetterFromNotification', 'order detail must distinguish notification entry')
  assertIncludes(detail, 'allow_fallback', 'confirm letter fetch must allow fallback from notification')
})

check('MP-005', '订单确认页必须明确预约锁续期、失效恢复和失败返回', () => {
  const src = orderConfirmPage()
  assertIncludes(src, 'renewAllBookingLocks', 'order confirm must renew locks on load/show/submit')
  assertIncludes(src, 'releaseAllBookingLocks', 'lock-session errors must release held locks')
  assertIncludes(src, 'isBookingLockSessionMatchingSelection', 'order confirm must verify selection matches lock session')
  assertIncludes(src, '预约锁已失效', 'expired lock must surface clear user message')
  assertIncludes(src, 'uni.navigateBack()', 'lock/preview failure must give back path')
  assertIncludes(src, 'clearBookingLockSession()', 'successful order creation must clear lock session')
})

// P1/P2 regression anchors.
check('REG-001', 'P1/P2 页面和接口路由必须注册关键入口', () => {
  const pages = pagesJson()
  assertIncludes(pages, 'payment_result/payment_result', 'payment result page must be registered')
  assertIncludes(pages, 'order_confirm/order_confirm', 'order confirm page must be registered')
  assertIncludes(pages, 'couple_questionnaire/detail', 'questionnaire detail page must be registered')
  assertIncludes(read('uniapp', 'src', 'api', 'order.ts'), '/order/confirmLetterCurrent', 'confirm letter current API must exist')
  assertIncludes(read('uniapp', 'src', 'api', 'order.ts'), '/order/uploadVoucher', 'offline voucher upload API must exist')
})

check('REG-002', '订阅/通知/确认函/问卷相关迁移必须进入安装 SQL', () => {
  const sql = likeSql()
  assertRegex(sql, /confirm.*letter|order_confirm_letter/i, 'install SQL should include confirm letter schema')
  assertRegex(sql, /couple.*question|questionnaire/i, 'install SQL should include couple questionnaire schema')
  assertRegex(sql, /notification|subscribe|message/i, 'install SQL should include notification/subscribe related schema')
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
