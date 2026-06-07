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

const assertRegex = (text, regex, message) => {
  if (!regex.test(text)) {
    throw new Error(message || `Missing pattern: ${regex}`)
  }
}

const assertNotRegex = (text, regex, message) => {
  if (regex.test(text)) {
    throw new Error(message || `Unexpected pattern: ${regex}`)
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

const service = () => read('server', 'app', 'common', 'service', 'CoupleQuestionnaireService.php')
const taskModel = () => read('server', 'app', 'common', 'model', 'questionnaire', 'CoupleQuestionnaireTask.php')
const staffController = () => read('server', 'app', 'adminapi', 'controller', 'staff', 'StaffController.php')
const adminController = () => read('server', 'app', 'adminapi', 'controller', 'questionnaire', 'CoupleQuestionnaireController.php')
const apiController = () => read('server', 'app', 'api', 'controller', 'CoupleQuestionnaireController.php')
const adminApi = () => read('admin', 'src', 'api', 'staff-center.ts')
const adminPage = () => read('admin', 'src', 'views', 'staff_center', 'couple_questionnaire', 'index.vue')
const miniApi = () => read('uniapp', 'src', 'api', 'coupleQuestionnaire.ts')
const miniListPage = () => read('uniapp', 'src', 'packages', 'pages', 'couple_questionnaire', 'list.vue')
const miniDetailPage = () => read('uniapp', 'src', 'packages', 'pages', 'couple_questionnaire', 'detail.vue')
const notificationPage = () => read('uniapp', 'src', 'packages', 'pages', 'notification', 'index.vue')
const orderDetailPage = () => read('uniapp', 'src', 'pages', 'order_detail', 'order_detail.vue')
const pagesJson = () => read('uniapp', 'src', 'pages.json')
const openApi = () => read('docs', 'contracts', 'openapi-core.yaml')
const stateDoc = () => read('docs', 'architecture', 'core-state-machine.md')
const installSql = () => read('server', 'public', 'install', 'db', 'like.sql')

// Backend task lifecycle and permissions.
check('CQ-BE-001', '订单进入可发送状态后创建问卷任务必须事务化、按订单去重并绑定服务人员/用户/版本快照', () => {
  const entryBody = extractFunctionBody(service(), 'createTaskAfterOrderPendingService')
  const body = extractFunctionBody(service(), 'createTaskForOrder')
  assertIncludes(entryBody, 'self::createTaskForOrder($orderId, 0, false)', 'auto entry must delegate to the atomic task creation helper')
  assertIncludes(body, 'Db::transaction', 'task creation must be inside a DB transaction')
  assertIncludes(body, "Order::where('id', $orderId)->lock(true)->find()", 'order row must be locked before creating questionnaire task')
  assertIncludes(body, "where('order_id', $orderId)->lock(true)->find()", 'task creation must lock/order-scope dedupe existing task')
  assertRegex(body, /Order::STATUS_PENDING_SERVICE[\s\S]*Order::STATUS_IN_SERVICE[\s\S]*Order::STATUS_COMPLETED[\s\S]*Order::STATUS_REVIEWED/s, 'task creation must restrict order statuses that can receive questionnaire')
  assertIncludes(body, 'resolveOrderStaffItem($orderId, $staffId)', 'task must resolve service staff from order item context')
  assertIncludes(body, "'user_id' => (int)$order->user_id", 'task must bind user_id')
  assertIncludes(body, "'staff_id' => $resolvedStaffId", 'task must bind resolved staff_id')
  assertIncludes(body, "'version_id' => (int)$version->id", 'task must bind published version id')
  assertIncludes(body, "'version_no' => (int)$version->version_no", 'task must bind published version number')
  assertIncludes(body, "'questions_snapshot' => $questions", 'task must snapshot questions at creation/send time')
  assertIncludes(body, "'expire_time' => $now + 30 * 86400", 'task must have an expiry time')
})

check('CQ-BE-002', '服务人员手动发送必须校验人员权限、可发送状态和失败重试', () => {
  const body = extractFunctionBody(service(), 'manualSendTask')
  const guard = extractFunctionBody(service(), 'assertTaskCanBeSent')
  assertIncludes(body, "where('staff_id', $staffId)", 'manual send must be scoped to current staff')
  assertIncludes(body, 'self::assertTaskCanBeSent($task)', 'manual send must use centralized sendability guard')
  assertIncludes(guard, 'SUBMITTABLE_STATUSES', 'sendability guard must only allow submittable statuses')
  assertIncludes(guard, '当前问卷已提交、已取消或已过期，无法发送', 'non-sendable task must get an explicit error')
  assertIncludes(body, '发送失败，已记录失败原因，可稍后重试', 'send failure must be surfaced as retryable')
  assertIncludes(staffController(), 'getRequiredStaffScopeId()', 'staff controller must derive required staff scope')
  assertIncludes(staffController(), 'myCoupleQuestionnaireSend', 'staff controller must expose self-service questionnaire send endpoint')
})

check('CQ-BE-003', '发送动作必须更新发送记录、失败原因/重试时间并创建可跳转站内通知', () => {
  const body = extractFunctionBody(service(), 'sendTaskNotice')
  assertIncludes(body, 'Db::transaction', 'send must protect task status with a transaction')
  assertIncludes(body, '->lock(true)', 'send must lock the task row')
  assertIncludes(body, 'SEND_STATUS_SENDING', 'send must mark in-progress state before writing notification')
  assertIncludes(body, '$task->last_send_time = time()', 'send must record last_send_time before attempting notification')
  assertIncludes(body, '$task->send_count = (int)$task->send_count + 1', 'send/retry must increment send_count')
  assertIncludes(body, 'StationNotificationService::sendUnique', 'send must create a station notification')
  assertIncludes(body, 'StationNotificationService::TARGET_COUPLE_QUESTIONNAIRE', 'notification target must be couple questionnaire detail')
  assertIncludes(body, "'send_status' => CoupleQuestionnaireTask::SEND_STATUS_SENT", 'successful send must mark task as sent')
  assertIncludes(body, "'send_status' => CoupleQuestionnaireTask::SEND_STATUS_FAILED", 'failed send must mark task as failed')
  assertIncludes(body, "'next_retry_time' => $now + 300", 'failed send must set retry time')
})

check('CQ-BE-004', '用户只能查看和提交自己的问卷，提交必须加锁且禁止重复提交', () => {
  const detailBody = extractFunctionBody(service(), 'userTaskDetail')
  const submitBody = extractFunctionBody(service(), 'submitUserAnswers')
  assertIncludes(detailBody, "where('user_id', $userId)", 'user detail must be scoped to current user')
  assertIncludes(submitBody, 'Db::transaction', 'submit must run inside a DB transaction')
  assertIncludes(submitBody, "where('user_id', $userId)", 'submit must be scoped to current user')
  assertIncludes(submitBody, '->lock(true)', 'submit must lock the task row')
  assertIncludes(submitBody, 'SEND_STATUS_SENT', 'submit must require the task to have been sent')
  assertIncludes(submitBody, 'SUBMITTABLE_STATUSES', 'submit must only allow explicitly submittable statuses')
  assertIncludes(service(), 'CoupleQuestionnaireTask::STATUS_PENDING', 'submittable statuses must include pending')
  assertRegex(submitBody, /STATUS_SUBMITTED[\s\S]*return true/s, 'duplicate submit after successful submit should be idempotent success without overwriting answers')
  assertRegex(submitBody, /throw new \\RuntimeException\('问卷已取消或不可填写'\)/, 'cancelled/expired submit must return explicit failure')
  assertIncludes(submitBody, 'CoupleQuestionnaireAnswer::create', 'submit must persist answer row')
  assertIncludes(submitBody, "'questions_snapshot' => $questions", 'answer must persist question snapshot')
  assertIncludes(submitBody, "'answers' => $cleanAnswers", 'answer must persist normalized answers')
  assertIncludes(submitBody, 'STATUS_SUBMITTED', 'task must move to submitted')
})

check('CQ-BE-005', '答案清洗必须覆盖必填、题型、选项和评分边界', () => {
  const body = extractFunctionBody(service(), 'normalizeAnswers')
  assertIncludes(body, "if ($type === 'multiple')", 'multiple answers must be normalized as arrays')
  assertIncludes(body, "elseif ($type === 'rating')", 'rating answers must be normalized')
  assertRegex(body, /max\(1,\s*min\(5,\s*\(int\)\$rawValue\)\)|max\(1,\s*min\(5,\s*\(int\)\$value\)\)/, 'rating must be clamped to 1..5')
  assertIncludes(body, 'limitText', 'text answers must be length-limited')
  assertIncludes(body, '请填写必填项', 'required questions must reject empty answers')
  assertIncludes(body, "'title' => (string)$question['title']", 'each answer item should retain question title for answer readability')
})

check('CQ-BE-006', '状态模型和 OpenAPI 必须声明待提交/已查看/已提交/已取消/已过期以及重复提交规则', () => {
  assertIncludes(taskModel(), 'STATUS_PENDING = 0', 'task model must define pending status')
  assertIncludes(taskModel(), 'STATUS_SUBMITTED = 1', 'task model must define submitted status')
  assertIncludes(taskModel(), 'STATUS_CANCELLED = 2', 'task model must define cancelled status')
  assertIncludes(taskModel(), 'STATUS_EXPIRED = 4', 'task model must define expired status when expiry handling exists')
  assertRegex(stateDoc(), /\|\s*0\s*\|\s*待提交\s*\|[^\n]*1 已提交[^\n]*2 已取消[^\n]*4 已过期/, 'state machine doc must describe questionnaire transitions including submitted/cancelled/expired')
  assertRegex(stateDoc(), /已提交任务重复提交可幂等返回成功但不得覆盖历史答案|重复提交.*不得覆盖/, 'state machine doc must state duplicate submit guard')
  assertIncludes(openApi(), '/couple_questionnaire/submit:', 'OpenAPI must include submit endpoint')
  assertRegex(openApi(), /重复提交幂等返回成功但不得覆盖答案|重复提交.*不得覆盖|idempotent/i, 'OpenAPI must document duplicate submit behavior')
})

check('CQ-BE-007', '平台管理员接口必须覆盖配置、发布、任务、详情和发送', () => {
  const src = adminController()
  for (const method of ['lists', 'detail', 'save', 'publish', 'tasks', 'taskDetail', 'send']) {
    assertIncludes(src, `public function ${method}()`, `admin controller missing ${method}`)
  }
  assertIncludes(src, 'CoupleQuestionnaireService::adminSendTask', 'admin send endpoint must delegate to service')
})

check('CQ-BE-008', '安装 SQL 必须包含问卷配置/版本/任务/答案表、发送状态索引和后台菜单权限', () => {
  const sql = installSql()
  for (const table of ['la_couple_questionnaire', 'la_couple_questionnaire_version', 'la_couple_questionnaire_task', 'la_couple_questionnaire_answer']) {
    assertIncludes(sql, table, `install SQL missing ${table}`)
  }
  assertIncludes(sql, 'idx_send_status', 'task table must include send_status index')
  assertIncludes(sql, '发送问卷', 'install SQL must include send-questionnaire permission/menu action')
  assertIncludes(sql, 'ops.staff/myCoupleQuestionnaireSend', 'install SQL must include staff scoped send permission')
})

// Frontend/admin and mini-program routing.
check('CQ-FE-001', '后台服务人员问卷页必须能保存、发布、筛选任务、查看详情和发送', () => {
  const api = adminApi()
  const page = adminPage()
  for (const fn of ['myCoupleQuestionnaireConfig', 'myCoupleQuestionnaireSave', 'myCoupleQuestionnairePublish', 'myCoupleQuestionnaireTasks', 'myCoupleQuestionnaireTaskDetail', 'myCoupleQuestionnaireSend']) {
    assertIncludes(api, `export function ${fn}`, `admin API missing ${fn}`)
    assertIncludes(page, fn, `admin page missing call to ${fn}`)
  }
  assertIncludes(page, '发送问卷', 'admin page must expose send action')
  assertIncludes(page, 'send_count', 'admin page must show send retry/count evidence')
  assertIncludes(page, 'send_status', 'admin page must filter or display send status')
  assertIncludes(page, '<pagination v-model="taskPager"', 'admin page must expose task pagination')
})

check('CQ-FE-002', '小程序必须注册问卷列表/详情，并按契约调用列表、详情和提交接口', () => {
  const pages = pagesJson()
  const detailPage = miniDetailPage()
  assertIncludes(pages, 'pages/couple_questionnaire/list', 'mini-program questionnaire list page must be registered')
  assertIncludes(pages, 'pages/couple_questionnaire/detail', 'mini-program questionnaire detail page must be registered')
  assertIncludes(miniApi(), "'/couple_questionnaire/lists'", 'mini API must call list endpoint')
  assertIncludes(miniApi(), "'/couple_questionnaire/detail'", 'mini API must call detail endpoint')
  assertIncludes(miniApi(), "'/couple_questionnaire/submit'", 'mini API must call submit endpoint')
  assertIncludes(miniListPage(), 'getCoupleQuestionnaireLists', 'list page must load questionnaire tasks')
  assertIncludes(detailPage, 'getCoupleQuestionnaireDetail', 'detail page must load questionnaire detail')
  assertIncludes(detailPage, 'submitCoupleQuestionnaire', 'detail page must submit answers')
  assertIncludes(detailPage, 'can_submit', 'detail page must honor backend can_submit for viewed-but-submittable tasks')
  assertRegex(detailPage, /submittableStatuses\s*=\s*\[[^\]]*0[^\]]*3[^\]]*\]|status\s*===\s*3|STATUS_VIEWED/, 'detail page must allow STATUS_VIEWED tasks to remain editable')
  assertNotRegex(detailPage, /const\s+canEdit\s*=\s*computed\(\(\)\s*=>\s*Number\(detail\.value\?\.status\s*\|\|\s*0\)\s*===\s*0\)/, 'detail page must not only allow status=0 to submit')
})

check('CQ-FE-003', '通知和订单详情必须能把用户带到待填问卷', () => {
  assertIncludes(notificationPage(), 'couple_questionnaire', 'notification route map must include questionnaire target type')
  assertIncludes(notificationPage(), '/packages/pages/couple_questionnaire/detail?id=', 'notification must route to questionnaire detail')
  assertIncludes(orderDetailPage(), 'fetchPendingQuestionnaireTask', 'order detail must fetch pending questionnaire task')
  assertIncludes(orderDetailPage(), 'showQuestionnairePromptCard', 'order detail must expose pending questionnaire prompt card')
  assertIncludes(orderDetailPage(), 'goQuestionnaireTask', 'order detail must navigate to questionnaire detail')
})

check('CQ-FE-004', '小程序问卷列表失败态必须可恢复或至少完成分页失败通知', () => {
  const src = miniListPage()
  assertIncludes(src, 'paging.value?.complete(false)', 'list query failure must notify z-paging failure state')
  assertIncludes(src, '<EmptyState', 'list page must render empty state')
})

// Previously identified gaps; keep them as regression guards after product/engineering fixes land.
check('CQ-GAP-001', '小程序问卷详情加载失败必须有页面级错误态、重试和返回路径', () => {
  const src = miniDetailPage()
  assertIncludes(src, '<EmptyState', 'detail page must render a page-level EmptyState when detail load fails or id is invalid')
  assertRegex(src, /detailError|pageError|status\s*=\s*ref\(/, 'detail page must track a page-level error state')
  assertRegex(src, /重新加载|重试|handle.*Retry|loadDetail/, 'detail page must expose retry action')
  assertRegex(src, /返回首页|返回订单|navigateBack|switchTab|goOrder/, 'detail page must expose a back/recovery path')
})

check('CQ-GAP-002', '问卷模板版本兼容策略必须实现一致且无 PHP 参数错误', () => {
  const src = service()
  const submitBody = extractFunctionBody(src, 'submitUserAnswers')
  const resolveSignature = /function\s+resolveTaskQuestions\s*\(([^)]*)\)/.exec(src)
  if (!resolveSignature) {
    throw new Error('Missing resolveTaskQuestions function')
  }
  const resolveParams = resolveSignature[1]
  if (/resolveTaskQuestions\(\$task,\s*false\)/.test(submitBody) && !/\$[A-Za-z_][A-Za-z0-9_]*\s*=\s*[^,]+/.test(resolveParams)) {
    throw new Error('submitUserAnswers calls resolveTaskQuestions($task, false), but resolveTaskQuestions does not accept a second parameter')
  }
  assertRegex(stateDoc(), /创建时|发送即冻结|提交时冻结答案快照/, 'architecture doc must explicitly document current template compatibility strategy')
  assertIncludes(extractFunctionBody(src, 'createTaskForOrder'), "'questions_snapshot' => $questions", 'task creation must freeze the questions used for later answers')
  assertIncludes(extractFunctionBody(src, 'refreshPendingTasksForStaff'), '已创建任务必须保持创建时的问卷版本快照', 'publishing a new version must not rewrite already-created tasks')
  assertIncludes(submitBody, "'questions_snapshot' => $questions", 'answer must retain the questions used for answers')
  assertIncludes(submitBody, "'version_id' => (int)$task->version_id", 'answer must retain the task version id')
  assertIncludes(submitBody, "'version_no' => (int)$task->version_no", 'answer must retain the task version number')
})

check('CQ-GAP-003', '过期/撤回能力必须在模型、服务、OpenAPI 和验收文档中保持一致', () => {
  const combined = [service(), taskModel(), adminController(), staffController(), stateDoc()].join('\n')
  assertRegex(combined, /expire|expired|过期|withdraw|revoke|撤回|cancelTask|cancelQuestionnaire|取消问卷/s, 'missing explicit expire/withdraw/cancel-task capability or documented non-goal')
  if (taskModel().includes('STATUS_EXPIRED')) {
    assertRegex(openApi(), /已过期|STATUS_EXPIRED|expired|enum:\s*\[[^\]]*4/s, 'OpenAPI QuestionnaireTaskStatus must include expired status when model defines STATUS_EXPIRED')
    assertRegex(stateDoc(), /已过期|STATUS_EXPIRED|expired|过期/s, 'state-machine doc must include expired status when model defines STATUS_EXPIRED')
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
console.log(`\nCouple questionnaire flow contract checks: ${passed}/${checks.length} passed, ${failed} failed.`)

if (failed > 0) {
  process.exitCode = 1
}
