#!/usr/bin/env node
/**
 * 三端治理静态合同检查。
 *
 * 这些检查不依赖数据库、Composer 或前端构建，用于把页面壳、PC 主题、
 * CRM 验收和质量入口固定成可重复执行的工程约束。
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

const assertFileMissing = (...segments) => {
  const file = rel(...segments)
  if (fs.existsSync(file)) {
    throw new Error(`Unexpected file: ${segments.join('/')}`)
  }
}

const adminPageShellPages = [
  ['admin', 'src', 'views', 'crm', 'customer', 'index.vue'],
  ['admin', 'src', 'views', 'crm', 'follow-record', 'index.vue'],
  ['admin', 'src', 'views', 'crm', 'advisor', 'index.vue'],
  ['admin', 'src', 'views', 'crm', 'loss-warning', 'index.vue']
]

check('GOV-001', '根级 QA 命令必须统一执行核心合同检查', () => {
  const pkg = JSON.parse(read('package.json'))
  assertIncludes(pkg.scripts['qa:contracts'], 'tests/qa/p0_p1_p2_contract_checks.js', 'qa:contracts must include core reliability checks')
  assertIncludes(pkg.scripts['qa:contracts'], 'tests/qa/couple_questionnaire_flow_contract_checks.js', 'qa:contracts must include questionnaire checks')
  assertIncludes(pkg.scripts['qa:contracts'], 'tests/qa/platform_governance_contract_checks.js', 'qa:contracts must include platform governance checks')
  assertIncludes(pkg.scripts['qa:all'], 'scripts/qa-all.mjs', 'qa:all must call the full QA runner')
  assertIncludes(read('scripts', 'qa-all.mjs'), "run('npm', ['run', 'qa:contracts'])", 'full QA runner must call qa:contracts')
})

check('GOV-002', 'GitHub Actions 必须通过根级 QA 命令执行静态合同', () => {
  const workflow = read('.github', 'workflows', 'quality.yml')
  assertIncludes(workflow, 'npm run qa:all', 'quality workflow must call root qa:all')
  assertNotIncludes(workflow, 'node tests/qa/p0_p1_p2_contract_checks.js', 'workflow should not bypass the root QA command')
})

check('GOV-003', 'PC 全局主题不得回退到脚手架蓝色', () => {
  const vars = read('pc', 'assets', 'styles', 'var.css')
  const app = read('pc', 'app.vue')
  for (const blue of ['#4153ff', '#7583ff', '#4a5dff', 'rgb(19, 153, 166)', 'rgb(93, 210, 222)']) {
    assertNotIncludes(vars, blue, `PC theme still contains scaffold color ${blue}`)
    assertNotIncludes(app, blue, `PC app loading indicator still contains scaffold color ${blue}`)
  }
  assertIncludes(vars, '--color-primary: #d8b16a;', 'PC primary color must match enterprise gold')
  assertIncludes(vars, '--el-color-primary: var(--color-primary);', 'Element Plus primary color must follow PC theme token')
})

check('GOV-004', 'PC 首页首屏必须提供展示型联系入口，不新增预约下单链路', () => {
  const page = read('pc', 'pages', 'index.vue')
  assertIncludes(page, 'enterprise-hero__actions', 'PC hero must expose first-screen action area')
  assertIncludes(page, 'href="#contact"', 'PC hero action must lead to contact section')
  assertIncludes(page, '联系顾问', 'PC hero action copy should be contact-oriented')
  assertNotIncludes(page, '/order/', 'PC home must not add order flow route')
  assertNotIncludes(page, '/pay/', 'PC home must not add payment flow route')
})

check('GOV-005', 'CRM 核心页必须接入后台页面壳', () => {
  for (const segments of adminPageShellPages) {
    const src = read(...segments)
    assertRegex(src, /<admin-page-shell[\s>]/, `${segments.join('/')} must use admin-page-shell`)
    assertIncludes(src, '<template #search>', `${segments.join('/')} must put filters into shell search slot`)
    assertIncludes(src, 'admin-page-section', `${segments.join('/')} must wrap table body as admin page section`)
  }
})

check('GOV-006', '三端治理文档必须覆盖 P0/P1/P2 验收', () => {
  const roadmap = read('docs', 'qa', 'platform-governance-roadmap.md')
  assertIncludes(roadmap, 'Admin 页面壳一致性', 'roadmap must cover admin shell consistency')
  assertIncludes(roadmap, 'PC 展示优化', 'roadmap must cover PC display optimization')
  assertIncludes(roadmap, '移动端组件治理', 'roadmap must cover miniapp component governance')
  assertIncludes(roadmap, 'CRM P1 验收', 'roadmap must cover CRM P1 acceptance')
  assertIncludes(roadmap, '状态机与 API 契约', 'roadmap must cover state machine and API contract')
})

check('GOV-007', 'CRM P1 四条链路必须保持前后端契约闭环', () => {
  const customerApi = read('admin', 'src', 'api', 'crm', 'customer.ts')
  const followApi = read('admin', 'src', 'api', 'crm', 'followRecord.ts')
  const advisorApi = read('admin', 'src', 'api', 'crm', 'advisor.ts')
  const warningApi = read('admin', 'src', 'api', 'crm', 'lossWarning.ts')
  const customerLogic = read('server', 'app', 'adminapi', 'logic', 'crm', 'CustomerLogic.php')
  const followLogic = read('server', 'app', 'adminapi', 'logic', 'crm', 'FollowRecordLogic.php')
  const advisorLogic = read('server', 'app', 'adminapi', 'logic', 'crm', 'SalesAdvisorLogic.php')
  const warningLogic = read('server', 'app', 'adminapi', 'logic', 'crm', 'LossWarningLogic.php')
  const customerModel = read('server', 'app', 'common', 'model', 'crm', 'Customer.php')
  const followModel = read('server', 'app', 'common', 'model', 'crm', 'FollowRecord.php')
  const warningModel = read('server', 'app', 'common', 'model', 'crm', 'CustomerLossWarning.php')

  for (const endpoint of [
    '/crm.customer/lists',
    '/crm.customer/detail',
    '/crm.customer/edit',
    '/crm.customer/transferAdvisor',
    '/crm.customer/advisorOptions'
  ]) {
    assertIncludes(customerApi, endpoint, `customer API missing ${endpoint}`)
  }
  for (const endpoint of ['/crm.followRecord/lists', '/crm.followRecord/add', '/crm.followRecord/customerOptions']) {
    assertIncludes(followApi, endpoint, `follow API missing ${endpoint}`)
  }
  for (const endpoint of [
    '/crm.salesAdvisor/lists',
    '/crm.salesAdvisor/add',
    '/crm.salesAdvisor/edit',
    '/crm.salesAdvisor/delete',
    '/crm.salesAdvisor/changeStatus',
    '/crm.salesAdvisor/syncCustomerCount'
  ]) {
    assertIncludes(advisorApi, endpoint, `advisor API missing ${endpoint}`)
  }
  for (const endpoint of [
    '/crm.lossWarning/lists',
    '/crm.lossWarning/handle',
    '/crm.lossWarning/ignore',
    '/crm.lossWarning/generate',
    '/crm.lossWarning/push',
    '/crm.lossWarning/stats'
  ]) {
    assertIncludes(warningApi, endpoint, `loss-warning API missing ${endpoint}`)
  }

  assertIncludes(customerModel, 'STATUS_NEW = 1', 'CRM customer status must include new')
  assertIncludes(customerModel, 'STATUS_FOLLOWING = 2', 'CRM customer status must include following')
  assertIncludes(customerModel, 'STATUS_SIGNED = 3', 'CRM customer status must include signed')
  assertIncludes(customerModel, 'STATUS_LOST = 4', 'CRM customer status must include lost')
  assertIncludes(customerModel, 'assignAdvisor', 'CRM must keep advisor assignment helper')
  assertIncludes(customerLogic, 'canAccessCustomer', 'CRM customer access scope must be centralized')
  assertIncludes(customerLogic, 'transferAdvisor', 'CRM must keep advisor transfer workflow')
  assertIncludes(followLogic, 'next_follow_time', 'follow records must keep next-follow planning')
  assertIncludes(followLogic, 'FollowRecord::createRecord', 'follow logic must delegate record creation to model helper')
  assertIncludes(followModel, 'Customer::updateFollowInfo', 'follow records must update customer follow summary')
  assertIncludes(advisorLogic, 'syncCustomerCount', 'advisor load must support count calibration')
  assertIncludes(advisorLogic, 'activeCustomerCount', 'advisor load must expose active customer count')
  assertIncludes(warningModel, 'STATUS_PENDING = 0', 'loss warning status must include pending')
  assertIncludes(warningModel, 'STATUS_HANDLED = 1', 'loss warning status must include handled')
  assertIncludes(warningLogic, 'WeComMessageService', 'loss warning must keep enterprise WeCom push integration')
  assertIncludes(warningLogic, 'generateAndPushForCrontab', 'loss warning cron flow must stay available')
  assertIncludes(warningLogic, 'self::push([], 0, [])', 'loss warning cron flow must push pending warnings')
})

check('GOV-008', '移动端反馈治理必须有统一工具且清理 BasePicker 孤儿组件', () => {
  const feedback = read('uniapp', 'src', 'utils', 'feedback.ts')
  const reviewMode = read('uniapp', 'src', 'utils', 'miniProgramReviewMode.ts')
  const governedFeedbackFiles = [
    ['uniapp', 'src', 'packages', 'pages', 'dynamic_detail', 'dynamic_detail.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'payment_result', 'payment_result.vue'],
    ['uniapp', 'src', 'components', 'payment', 'payment.vue'],
    ['uniapp', 'src', 'pages', 'schedule_query', 'schedule_query.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'search', 'search.vue'],
    ['uniapp', 'src', 'pages', 'login', 'login.vue'],
    ['uniapp', 'src', 'pages', 'order', 'order.vue'],
    ['uniapp', 'src', 'pages', 'order_detail', 'order_detail.vue'],
    ['uniapp', 'src', 'pages', 'staff_list', 'staff_list.vue'],
    ['uniapp', 'src', 'pages', 'user_data', 'user_data.vue'],
    ['uniapp', 'src', 'pages', 'dynamic', 'dynamic.vue'],
    ['uniapp', 'src', 'pages', 'change_password', 'change_password.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'activity_registration', 'detail.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'admin_dashboard', 'admin_dashboard.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'aftersale', 'create_ticket.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'aftersale', 'create_complaint.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'aftersale', 'ticket_detail.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'aftersale', 'complaint_detail.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'aftersale', 'callback_detail.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'aftersale', 'components', 'AfterSaleMediaUploader.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'couple_questionnaire', 'detail.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'customer_service', 'customer_service.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'dynamic_publish', 'dynamic_publish.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'notification', 'index.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'order_confirm', 'order_confirm.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'order_change', 'apply_date.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'order_change', 'apply_pause.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'order_change', 'apply_add_item.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'order_change', 'list.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'order_change', 'change_detail.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'order_change', 'pause_detail.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'recharge', 'recharge.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'recharge_record', 'recharge_record.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'review', 'publish.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'review', 'detail.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'staff_addon_edit', 'staff_addon_edit.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'staff_addon_list', 'staff_addon_list.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'staff_booking', 'staff_booking.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'staff_center', 'staff_center.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'staff_certificate_edit', 'staff_certificate_edit.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'staff_certificate_list', 'staff_certificate_list.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'staff_detail', 'staff_detail.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'staff_dynamic_edit', 'staff_dynamic_edit.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'staff_dynamic_list', 'staff_dynamic_list.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'staff_favorite', 'staff_favorite.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'staff_order_detail', 'staff_order_detail.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'staff_order_list', 'staff_order_list.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'staff_package_edit', 'staff_package_edit.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'staff_package_list', 'staff_package_list.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'staff_profile', 'staff_profile.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'staff_schedule', 'staff_schedule.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'staff_settlement', 'staff_settlement.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'staff_work_detail', 'staff_work_detail.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'staff_work_edit', 'staff_work_edit.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'staff_work_list', 'staff_work_list.vue'],
    ['uniapp', 'src', 'packages', 'pages', 'waitlist', 'waitlist.vue'],
    ['uniapp', 'src', 'components', 'avatar-upload', 'avatar-upload.vue'],
    ['uniapp', 'src', 'components', 'base', 'BaseMultiTextPicker.vue'],
    ['uniapp', 'src', 'components', 'base', 'BaseServiceRegionPicker.vue'],
    ['uniapp', 'src', 'packages', 'common', 'utils', 'file.ts'],
    ['uniapp', 'src', 'packages', 'common', 'utils', 'staff-center.ts'],
    ['uniapp', 'src', 'packages', 'components', 'staff-long-detail', 'staff-long-detail-editor.vue'],
    ['uniapp', 'src', 'components', 'widgets', 'quick-entry', 'quick-entry.vue'],
    ['uniapp', 'src', 'components', 'widgets', 'store-map', 'store-map.vue'],
    ['uniapp', 'src', 'utils', 'util.ts'],
    ['uniapp', 'src', 'utils', 'subscribe.ts'],
    ['uniapp', 'src', 'packages', 'pages', 'user_wallet', 'user_wallet.vue']
  ]
  assertIncludes(feedback, 'export const showToast', 'mobile feedback utility must expose showToast')
  assertIncludes(feedback, 'export const showSuccess', 'mobile feedback utility must expose showSuccess')
  assertIncludes(feedback, 'export const showError', 'mobile feedback utility must expose showError')
  assertIncludes(feedback, 'export const confirmModal', 'mobile feedback utility must expose confirmModal')
  assertIncludes(reviewMode, "from '@/utils/feedback'", 'review mode tip should use unified feedback utility')
  for (const filePath of governedFeedbackFiles) {
    const source = read(...filePath)
    const displayPath = filePath.join('/')
    assertNotIncludes(source, 'uni.showToast', `${displayPath} must use unified feedback toast helper`)
    assertNotIncludes(source, 'uni.showModal', `${displayPath} must use unified feedback modal helper`)
    assertNotIncludes(source, 'uni.$u.toast', `${displayPath} must use unified feedback toast helper`)
  }
  assertFileMissing('uniapp', 'src', 'components', 'base', 'BasePicker.vue')
})

check('GOV-009', '移动端 active 类型检查必须覆盖订单、通知和核心用户链路', () => {
  const tsconfig = read('uniapp', 'tsconfig.active.json')
  const tsconfigJson = JSON.parse(tsconfig)
  const excludeText = JSON.stringify(tsconfigJson.exclude || [])
  const includedCoreFiles = [
    '"src/pages/order/**/*.vue"',
    '"src/pages/order_detail/**/*.vue"',
    '"src/packages/pages/payment_result/**/*.vue"',
    '"src/pages/schedule_query/**/*.vue"',
    '"src/packages/pages/search/**/*.vue"',
    '"src/packages/pages/dynamic_detail/**/*.vue"',
    '"src/pages/change_password/**/*.vue"',
    '"src/packages/pages/admin_dashboard/**/*.vue"',
    '"src/packages/pages/activity_registration/**/*.vue"',
    '"src/packages/pages/aftersale/**/*.vue"',
    '"src/packages/pages/couple_questionnaire/**/*.vue"',
    '"src/packages/pages/customer_service/**/*.vue"',
    '"src/packages/pages/dynamic_publish/**/*.vue"',
    '"src/packages/pages/notification/**/*.vue"',
    '"src/packages/pages/order_change/**/*.vue"',
    '"src/packages/pages/recharge/**/*.vue"',
    '"src/packages/pages/recharge_record/**/*.vue"',
    '"src/packages/pages/review/**/*.vue"',
    '"src/packages/pages/staff_addon_edit/**/*.vue"',
    '"src/packages/pages/staff_addon_list/**/*.vue"',
    '"src/packages/pages/staff_booking/**/*.vue"',
    '"src/packages/pages/staff_center/**/*.vue"',
    '"src/packages/pages/staff_certificate_edit/**/*.vue"',
    '"src/packages/pages/staff_certificate_list/**/*.vue"',
    '"src/packages/pages/staff_dynamic_edit/**/*.vue"',
    '"src/packages/pages/staff_dynamic_list/**/*.vue"',
    '"src/packages/pages/staff_order_detail/**/*.vue"',
    '"src/packages/pages/staff_order_list/**/*.vue"',
    '"src/packages/pages/staff_package_edit/**/*.vue"',
    '"src/packages/pages/staff_profile/**/*.vue"',
    '"src/packages/pages/staff_settlement/**/*.vue"',
    '"src/packages/pages/staff_work_edit/**/*.vue"',
    '"src/packages/pages/staff_work_list/**/*.vue"',
    '"src/packages/pages/user_wallet/**/*.vue"',
    '"src/packages/pages/waitlist/**/*.vue"',
    '"src/api/aftersale.ts"'
  ]
  const excludedCoreDirs = [
    '"src/pages/order/**/*"',
    '"src/pages/order_detail/**/*"',
    '"src/packages/pages/payment_result/**/*"',
    '"src/pages/schedule_query/**/*"',
    '"src/packages/pages/search/**/*"',
    '"src/packages/pages/dynamic_detail/**/*"',
    '"src/pages/change_password/**/*"',
    '"src/packages/pages/admin_dashboard/**/*"',
    '"src/packages/pages/activity_registration/**/*"',
    '"src/packages/pages/aftersale/**/*"',
    '"src/pages/aftersale/**/*"',
    '"src/packages/pages/couple_questionnaire/**/*"',
    '"src/packages/pages/customer_service/**/*"',
    '"src/packages/pages/dynamic_publish/**/*"',
    '"src/packages/pages/notification/**/*"',
    '"src/packages/pages/order_change/**/*"',
    '"src/packages/pages/recharge/**/*"',
    '"src/packages/pages/recharge_record/**/*"',
    '"src/packages/pages/review/**/*"',
    '"src/packages/pages/staff_addon_edit/**/*"',
    '"src/packages/pages/staff_addon_list/**/*"',
    '"src/packages/pages/staff_booking/**/*"',
    '"src/packages/pages/staff_center/**/*"',
    '"src/packages/pages/staff_certificate_edit/**/*"',
    '"src/packages/pages/staff_certificate_list/**/*"',
    '"src/packages/pages/staff_dynamic_edit/**/*"',
    '"src/packages/pages/staff_dynamic_list/**/*"',
    '"src/packages/pages/staff_order_detail/**/*"',
    '"src/packages/pages/staff_order_list/**/*"',
    '"src/packages/pages/staff_package_edit/**/*"',
    '"src/packages/pages/staff_profile/**/*"',
    '"src/packages/pages/staff_settlement/**/*"',
    '"src/packages/pages/staff_work_edit/**/*"',
    '"src/packages/pages/staff_work_list/**/*"',
    '"src/packages/pages/user_wallet/**/*"',
    '"src/packages/pages/waitlist/**/*"',
    '"src/api/aftersale.ts"'
  ]

  for (const includePattern of includedCoreFiles) {
    assertIncludes(tsconfig, includePattern, `active tsconfig must include ${includePattern}`)
  }
  for (const excludePattern of excludedCoreDirs) {
    assertNotIncludes(excludeText, excludePattern, `active tsconfig must not exclude ${excludePattern}`)
  }
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
console.log(`\nPlatform governance contract checks: ${passed}/${checks.length} passed, ${failed} failed.`)

if (failed > 0) {
  process.exitCode = 1
}
