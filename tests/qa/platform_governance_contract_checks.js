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
  assertIncludes(pkg.scripts['qa:all'], 'qa:contracts', 'qa:all must call qa:contracts')
})

check('GOV-002', 'GitHub Actions 必须通过根级 QA 命令执行静态合同', () => {
  const workflow = read('.github', 'workflows', 'quality.yml')
  assertIncludes(workflow, 'npm run qa:contracts', 'quality workflow must call root qa:contracts')
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
  assertIncludes(feedback, 'export const showToast', 'mobile feedback utility must expose showToast')
  assertIncludes(feedback, 'export const showSuccess', 'mobile feedback utility must expose showSuccess')
  assertIncludes(feedback, 'export const showError', 'mobile feedback utility must expose showError')
  assertIncludes(feedback, 'export const confirmModal', 'mobile feedback utility must expose confirmModal')
  assertIncludes(reviewMode, "from '@/utils/feedback'", 'review mode tip should use unified feedback utility')
  assertFileMissing('uniapp', 'src', 'components', 'base', 'BasePicker.vue')
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
