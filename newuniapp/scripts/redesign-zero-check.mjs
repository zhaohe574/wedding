// 目标文件：newuniapp/scripts/redesign-zero-check.mjs
// 来源：docs/redesign/03-组件样式清单与验收.md §5.8「(B) Node 归零核验脚本」
// 用法：cd newuniapp && node scripts/redesign-zero-check.mjs [srcRoot]
// 输出：每条违规 = 相对路径:行号  [规则] 命中内容；末尾汇总，任一 FAIL 则 exit 1
import fs from 'node:fs'
import path from 'node:path'

const ROOT = process.argv[2] || 'src'
const EXCLUDE = [/uni_modules/, /node_modules/, /dist/]
const EXTS = new Set(['.vue', '.scss', '.css', '.ts'])
// 令牌定义处豁免（与 tokens.css 同口径）：
//   tokens.css        —— 设计令牌 SSOT（--c-* / --sp-* / --fs-*）
//   tn-theme.scss     —— 图鸟变量桥（--tn-* 基线 + 覆盖为 var(--c-*)），本身即令牌定义文件，
//                        其 hex 是图鸟基线原值与 sass 混色常量，不是业务散写色值
//   tailwind.css      —— 工具类入口
const IGNORE_FILES = [
  /styles[\\/]tokens\.css$/,
  /styles[\\/]tailwind\.css$/,
  /styles[\\/]tn-theme\.scss$/
]

// 已登记白名单：裸数值但属视觉规范明确允许（逐项登记依据，禁止整页/整目录）
/* ALLOW_RAW 已于 2026-11-08 删除：它只是裸正则数组，结构上不要求写「给哪个文件豁免」，
   于是必然出现「一条正则豁免全站一个值」的情况（R6 的 20/24/28/32rpx 就是这么废掉的）。
   现全部并入 ALLOW_SCOPED（{ file, rule, line, why }，file 必填）——
   结构上让「无限定豁免」写不出来。 */

// ALLOW_SCOPED：按「文件 + 规则 + 精确行内容 + 理由」逐项登记依据（file 必填）。
// 禁止整页/整目录豁免；也必须写清「为什么这一行可以例外」，否则不可审。
// 每一项必须写明：所属规则、依据章节、为何不改源码。
// 匹配条件：文件相对路径 endsWith(file) 且该行命中 lineRe，且规则 id === rule。
const ALLOW_SCOPED = [
  // ---- R8 尺寸位（24 处）：是尺寸/占位，非 7 档间距（--sp-1..7 仅管间距）----
  { file: 'packages/pkg-catalog/pages/booking/booking.vue', rule: 'R8-spacing', line: /padding-bottom:\s*calc\(180rpx \+ var\(--safe-bottom\)\)/, why: '03 §5.7 吸底占位（与 fixed 底栏等高），是占位尺寸非间距' },
  { file: 'packages/pkg-order/pages/detail/detail.vue', rule: 'R8-spacing', line: /padding-bottom:\s*calc\(180rpx \+ var\(--safe-bottom\)\)/, why: '03 §5.7 吸底占位（detail__space 等高占位块）' },
  { file: 'packages/pkg-service/pages/aftersale/complaint-list.vue', rule: 'R8-spacing', line: /padding-bottom:\s*calc\(160rpx \+ var\(--safe-bottom\)\)/, why: '03 §5.7 吸底占位（列表页底部留白）' },
  { file: 'packages/pkg-service/pages/aftersale/ticket-list.vue', rule: 'R8-spacing', line: /padding-bottom:\s*calc\(160rpx \+ var\(--safe-bottom\)\)/, why: '03 §5.7 吸底占位（列表页底部留白）' },
  { file: 'packages/pkg-catalog/pages/staff-detail/staff-detail.vue', rule: 'R8-spacing', line: /padding-bottom:\s*180rpx/, why: '03 §5.7 吸底占位（action-bar 等高）' },
  { file: 'packages/pkg-catalog/pages/staff-list/staff-list.vue', rule: 'R8-spacing', line: /(max-width|width|height):\s*(220|180)rpx/, why: '03 §7.4 列表缩略图/头像尺寸位（180/220rpx），非间距' },
  { file: 'packages/pkg-order/pages/detail/detail.vue', rule: 'R8-spacing', line: /max-width:\s*180rpx/, why: '03 §7.4 详情缩略图尺寸位' },
  { file: 'packages/pkg-order/pages/review/publish.vue', rule: 'R8-spacing', line: /min-height:\s*220rpx/, why: '03 §5.2 文本域/上传位最小高度（占位语义）' },
  { file: 'packages/pkg-service/pages/aftersale/ticket-create.vue', rule: 'R8-spacing', line: /min-height:\s*220rpx/, why: '03 §5.2 上传位最小高度' },
  { file: 'packages/pkg-service/pages/aftersale/complaint-create.vue', rule: 'R8-spacing', line: /min-height:\s*200rpx/, why: '03 §5.2 上传位最小高度' },
  { file: 'packages/pkg-service/pages/aftersale/callback-detail.vue', rule: 'R8-spacing', line: /min-height:\s*200rpx/, why: '03 §5.2 长文本区最小高度' },
  { file: 'packages/pkg-service/pages/aftersale/complaint-detail.vue', rule: 'R8-spacing', line: /min-height:\s*180rpx/, why: '03 §5.2 内容区最小高度' },
  { file: 'packages/pkg-service/pages/change/apply-addon.vue', rule: 'R8-spacing', line: /min-height:\s*200rpx/, why: '03 §5.2 上传位最小高度' },
  { file: 'packages/pkg-service/pages/change/apply-date.vue', rule: 'R8-spacing', line: /min-height:\s*200rpx/, why: '03 §5.2 上传位最小高度' },
  { file: 'packages/pkg-service/pages/change/apply-pause.vue', rule: 'R8-spacing', line: /min-height:\s*200rpx/, why: '03 §5.2 上传位最小高度' },
  { file: 'components/price/price.vue', rule: 'R6-fontsize', line: /font-size:\s*(20|24|28|32)rpx/, why: '¥ 符号按档位缩放（03 §7.9）；原为全局豁免，2026-11-08 收窄为按文件' },
  { file: 'components/app-tabbar/app-tabbar.vue', rule: 'R6-fontsize', line: /font-size:\s*(20|24|28|32)rpx/, why: 'tabBar 数字角标（03 §7.2）；原为全局豁免，2026-11-08 收窄为按文件' },
  { file: 'components/app-toast/app-toast.vue', rule: 'R8-spacing', line: /(?:bottom):\s*(?:160|180|200|220)rpx/, why: '尺寸位（03 §7.4 缩略图/圆形底/文本框高度），非间距；2026-11-08 由全局正则收窄为按文件' },
  { file: 'components/result-page/result-page.vue', rule: 'R8-spacing', line: /(?:width|height):\s*(?:160|180|200|220)rpx/, why: '尺寸位（03 §7.4 缩略图/圆形底/文本框高度），非间距；2026-11-08 由全局正则收窄为按文件' },
  { file: 'packages/pkg-catalog/pages/staff-list/staff-list.vue', rule: 'R8-spacing', line: /(?:max-width):\s*(?:160|180|200|220)rpx/, why: '尺寸位（03 §7.4 缩略图/圆形底/文本框高度），非间距；2026-11-08 由全局正则收窄为按文件' },
  { file: 'packages/pkg-order/pages/review/publish.vue', rule: 'R8-spacing', line: /(?:width|height):\s*(?:160|180|200|220)rpx/, why: '尺寸位（03 §7.4 缩略图/圆形底/文本框高度），非间距；2026-11-08 由全局正则收窄为按文件' },
  { file: 'packages/pkg-service/pages/aftersale/complaint-create.vue', rule: 'R8-spacing', line: /(?:width|height):\s*(?:160|180|200|220)rpx/, why: '尺寸位（03 §7.4 缩略图/圆形底/文本框高度），非间距；2026-11-08 由全局正则收窄为按文件' },
  { file: 'packages/pkg-service/pages/aftersale/complaint-detail.vue', rule: 'R8-spacing', line: /(?:width|height):\s*(?:160|180|200|220)rpx/, why: '尺寸位（03 §7.4 缩略图/圆形底/文本框高度），非间距；2026-11-08 由全局正则收窄为按文件' },
  { file: 'packages/pkg-service/pages/aftersale/ticket-create.vue', rule: 'R8-spacing', line: /(?:min-height):\s*(?:160|180|200|220)rpx/, why: '尺寸位（03 §7.4 缩略图/圆形底/文本框高度），非间距；2026-11-08 由全局正则收窄为按文件' },
  { file: 'packages/pkg-service/pages/aftersale/ticket-detail.vue', rule: 'R8-spacing', line: /(?:width|height|min-height):\s*(?:160|180|200|220)rpx/, why: '尺寸位（03 §7.4 缩略图/圆形底/文本框高度），非间距；2026-11-08 由全局正则收窄为按文件' },
  { file: 'packages/pkg-service/pages/change/apply-date.vue', rule: 'R8-spacing', line: /(?:width|height):\s*(?:160|180|200|220)rpx/, why: '尺寸位（03 §7.4 缩略图/圆形底/文本框高度），非间距；2026-11-08 由全局正则收窄为按文件' },
  { file: 'packages/pkg-service/pages/change/apply-pause.vue', rule: 'R8-spacing', line: /(?:width|height):\s*(?:160|180|200|220)rpx/, why: '尺寸位（03 §7.4 缩略图/圆形底/文本框高度），非间距；2026-11-08 由全局正则收窄为按文件' },
  { file: 'packages/pkg-service/pages/change/detail.vue', rule: 'R8-spacing', line: /(?:width|height):\s*(?:160|180|200|220)rpx/, why: '尺寸位（03 §7.4 缩略图/圆形底/文本框高度），非间距；2026-11-08 由全局正则收窄为按文件' },
  { file: 'pages/search/search.vue', rule: 'R8-spacing', line: /(?:width|height):\s*(?:160|180|200|220)rpx/, why: '尺寸位（03 §7.4 缩略图/圆形底/文本框高度），非间距；2026-11-08 由全局正则收窄为按文件' },
  { file: 'components/section-header/section-header.vue', rule: 'R8-spacing', line: /width:\s*(?:4|6)rpx/, why: '区块标题竖条（A12 白名单），是线段粗细非间距；2026-11-08 由全局正则收窄为按文件' },
  { file: 'pages/query/query.vue', rule: 'R8-spacing', line: /width:\s*(?:4|6)rpx/, why: '吉日选中点（6rpx 圆点直径），非间距；2026-11-08 由全局正则收窄为按文件' },
  { file: 'packages/pkg-service/pages/questionnaire/detail.vue', rule: 'R8-spacing', line: /min-height:\s*200rpx/, why: '03 §5.2 文本域最小高度' },
  // 2026-11 订正：原注释写「日历格最小宽度（7 列不折行）」是错的 —— 日历格是 .cell { width: 14.2857% }
  // 百分比七等分 + flex-wrap，本来就不横滚。这条豁免命中的是 .month__text（月份标签「2026 年 11 月」），
  // min-width 的作用是**锁住标签宽度**，避免「1 月」↔「11 月」切换时左右 ‹ › 按钮横向跳动。
  { file: 'packages/pkg-staff/pages/schedule/schedule.vue', rule: 'R8-spacing', line: /min-width:\s*220rpx/, why: '月份标签 .month__text 定宽（锁住「1 月」/「11 月」宽度差，防止切月时两侧箭头跳动），非日历格' },
  { file: 'pages/index/index.vue', rule: 'R8-spacing', line: /padding:\s*160rpx var\(--sp-4\)/, why: '03 §5.4.1 brand-head 顶部留白（无轮播时避开自定义导航），占位尺寸' },
  { file: 'pages/index/index.vue', rule: 'R8-spacing', line: /(height|width):\s*200rpx/, why: '03 §7.4 缩略图/分类卡尺寸位（200rpx）' },
  // ---- R12 平台/工具层 toast（4 处）：硬约束禁改，非页面散调用 ----
  { file: 'composables/useNotificationRouter.ts', rule: 'R12-toast', line: /uni\.showToast\(\{ title: '该消息暂无可跳转页面'/, why: 'E12 唯一路由来源 + 04 硬约束零 diff；04 §7.2 白名单登记，不判违规' },
  { file: 'request/index.ts', rule: 'R12-toast', line: /function toast\(message: string\)|uni\.showToast\(\{ title: message, icon: 'none', duration: 2200 \}\)/, why: '请求层统一 toast 底座（非页面散调用）；04 硬约束不改 src/request/*' },
  { file: 'utils/file.ts', rule: 'R12-toast', line: /uni\.showToast\(\{/, why: '文件下载工具层（非页面散调用）；04 硬约束不改工具层' },
  { file: 'utils/link.ts', rule: 'R12-toast', line: /uni\.showToast\(\{ title: '功能开发中'/, why: '路由工具层（非页面散调用）；04 硬约束不改路由工具' },
  // ---- R3 原生属性 / SCSS 变量桥接（4 处）：平台限制或已冻结登记 ----
  { file: 'pages/index/index.vue', rule: 'R3-hex', line: /indicator-active-color="#FFFFFF"/, why: 'swiper 原生属性只接受字面色值，不支持 var()；03 §7.10 白名单' },
  { file: 'packages/pkg-order/pages/review/publish.vue', rule: 'R3-hex', line: /<switch[^>]*color="#C6A15B"/, why: 'switch 原生组件属性只接受字面色值（品牌色），不支持 var()' },
  { file: 'uni.scss', rule: 'R3-hex', line: /\$-color-white: #ffffff;|\$u-color-btn-text: #ffffff;/, why: 'B1 冻结登记（03 §2.5）：uView SCSS 变量桥接白值，非业务代码 hex；零改动' },
  // ---- v2 24 屏：原生 API 属性 / 尺寸位（逐项登记，非整页豁免）----
  { file: 'packages/pkg-user/pages/activity/activity.vue', rule: 'R3-hex', line: /confirmColor: '#B4453C'/, why: 'uni.showModal 的 confirmColor 是原生参数，只接受字面 hex，不支持 var()；与 --c-danger 同值' },
  { file: 'pages/message/message.vue', rule: 'R3-hex', line: /backgroundColor: '#B4453C'/, why: 'tn-swipe-action 的 options.style 走行内样式对象，wxss 变量在行内样式里不生效；与 --c-danger 同值' },
  { file: 'packages/pkg-catalog/pages/booking/booking.vue', rule: 'R8-spacing', line: /max-width: 200rpx/, why: '03 §7.4 缩略图/标签尺寸位，非间距' },
  { file: 'packages/pkg-catalog/pages/staff-detail/staff-detail.vue', rule: 'R8-spacing', line: /max-width: 220rpx/, why: '03 §7.4 标签尺寸位，非间距' },
  { file: 'packages/pkg-service/pages/questionnaire/detail.vue', rule: 'R8-spacing', line: /padding: 30rpx 42rpx 180rpx/, why: '03 §5.7 吸底提交栏等高占位（180rpx），是占位尺寸非间距' },
]

/** 逐项白名单判定：仅当文件 + 规则 + 精确行内容三者同时命中才豁免 */
function scopedAllowed(f, ruleId, line) {
  const norm = f.replace(/\\/g, '/')
  return ALLOW_SCOPED.some(a =>
    norm.endsWith(a.file) && a.rule === ruleId && a.line.test(line))
}

const RULES = [
  { id: 'R1-error-kind', re: /error-kind="(network|business|permission|unknown)"/, msg: 'error-kind 硬编码（应为 :error-kind="view.errorKind"）' },
  { id: 'R2-brand',      re: /variant="(brand|primary)"/,                       msg: 'variant 历史别名（应为 ink/gold/ghost/text/danger + §4.4 重排）' },
  { id: 'R3-hex',        re: /#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})\b/, msg: 'hex 字面色值（应为 var(--c-*)；tokens.css 定义处豁免）' },
  { id: 'R4-lineheight', re: /line-height:\s*1\.(4|5|6)\b/,                    msg: '行高不在 4 档（应为 var(--lh-*)）' },
  { id: 'R5-weight',     re: /font-weight:\s*(700|800|900|bold|bolder|lighter)\b/, msg: '字重违规（只允许 400/500/600）' },
  { id: 'R6-fontsize',   re: /font-size:\s*(?!var\(--fs-)[0-9]+(?:r?px|em|rem)/, msg: '裸字号（应为 var(--fs-*)，或已登记白名单）' },
  { id: 'R7-radius',     re: /border-radius:\s*(?!var\(--r-)[0-9]+(?:r?px|%)/,  msg: '裸圆角（应为 var(--r-*)）' },
  { id: 'R8-spacing',    re: /\b(160|180|200|220)rpx\b/,                        msg: '私建间距值（应只用 --sp-1..7）' },
  { id: 'R9-brandcolor', re: /color="var\(--c-brand\)"/,                        msg: '--c-brand 作图标主色（应改 --c-ink-3 / --c-brand-text / 语义色）' },
  { id: 'R10-linestrong',re: /color="var\(--c-line-strong\)"/,                  msg: '--c-line-strong 作图标/控件色（O6 不豁免）' },
  { id: 'R11-private',   re: /\.(loadmore__text|steps__dot|steps__label|fallback__title|fallback__desc|picker-mask)\b/, msg: '私有样式类（应换 load-more/steps/form-field/base-empty/sheet）' },
  { id: 'R12-toast',     re: /uni\.showToast\s*\(/,                              msg: 'uni.showToast 散调用（应走统一 Toast 层）' },
  { id: 'R13-env',       re: /env\(safe-area-inset-bottom\)|constant\(safe-area/, msg: '业务代码直接写 env()（应只消费 var(--safe-bottom)）' },
  { id: 'R14-import',    re: /@import\s+['"][^'"]*(vant|tdesign|tnui|element|antd|bootstrap)/i, msg: '新增大 UI 库 @import' },
  { id: 'R15-cnmaster',  re: /var\(--color-/,                                   msg: '引用从未定义的 var(--color-*) 历史死链' },
  { id: 'R16-theme',     re: /stores\/theme|utils\/theme|decorate\?id=/,        msg: '运行时主题下发链路（已作废，不得复活）' }
]

const files = []
;(function walk(dir) {
  for (const name of fs.readdirSync(dir)) {
    const p = path.join(dir, name)
    if (EXCLUDE.some(re => re.test(p))) continue
    const st = fs.statSync(p)
    if (st.isDirectory()) walk(p)
    else if (EXTS.has(path.extname(name)) && !IGNORE_FILES.some(re => re.test(p))) files.push(p)
  }
})(ROOT)

/**
 * 去注释：把行内注释剥离，只留代码部分再跑规则。
 *
 * 为什么不能按「行首是否注释」整行跳过：
 *   ① 块注释的**续行**不以 /* 开头 → 续行里的 hex / 裸值被当成真代码 → **假阳性**；
 *   ② `color: red; /* #fff *\/` 这种行既有代码又有注释 → 整行跳过会**漏检**。
 * 支持三种：// 行注释、/* *\/ 块注释（可跨行）、<!-- --> 注释（可跨行，.vue 模板用）。
 *
 * 状态跨行保存在 state 里，每个文件重置一次。
 */
function stripComments(line, state) {
  let out = ''
  let i = 0
  while (i < line.length) {
    if (state.block) {
      const end = line.indexOf('*/', i)
      if (end === -1) return out          // 本行剩余全在块注释内
      state.block = false
      i = end + 2
      continue
    }
    if (state.html) {
      const end = line.indexOf('-->', i)
      if (end === -1) return out
      state.html = false
      i = end + 3
      continue
    }
    if (line.startsWith('//', i)) break    // 行注释：后面全丢
    if (line.startsWith('/*', i)) { state.block = true; i += 2; continue }
    if (line.startsWith('<!--', i)) { state.html = true; i += 4; continue }
    out += line[i]
    i += 1
  }
  return out
}

/* R6 棘轮（2026-11-08 基线）。
   20/24/28/32rpx 的裸字号不报错，但**总量不得超过基线** —— 先止跌，清理另立项。
   为什么不直接报：直接报会带出存量，门禁立刻红，而常驻红 = 没有门禁。
   为什么不能批量换成 --fs-xs：--fs-xs 是 max(24rpx, 12px) **带下限**，
   窄屏行为会变，不是等价换名。那 103 处需逐处判断。
   降基线的条件：每清完一批，把基线改成实测值；清到 0 时把本段换成硬性失败。 */
const R6_NAKED_RE = /font-size:\s*(20|24|28|32)rpx/
const R6_NAKED_BASELINE = 102   // 2026-11-08 下调：ticket-create 的 .ta 收编后清掉 1 处裸字号
let r6NakedCount = 0

const hits = []
for (const f of files) {
  const lines = fs.readFileSync(f, 'utf8').split(/\r?\n/)
  // 注释状态跨行保存：块注释 / HTML 注释都可能跨行，逐个文件重置
  const commentState = { block: false, html: false }
  lines.forEach((line, i) => {
    // 先剥离注释，只对"代码部分"跑规则：
    //   整行注释 → code 为空 → 跳过；
    //   行内尾注释 → 注释部分被剥掉，代码部分照检；
    //   块注释续行 → 被状态机识别并剥掉，不再假阳性。
    const code = stripComments(line, commentState)
    const trimmed = code.trim()
    if (!trimmed) return
    for (const r of RULES) {
      // R11 私有类只在页面/样式文件判定，组件内部合法类名豁免
      if (r.id === 'R11-private' && f.includes(`${path.sep}components${path.sep}`)) continue
      // R3 hex：仅 .vue/.scss/.css 判定，且排除 SCSS 变量定义（uni.scss 白名单由 IGNORE 控制）
      if (r.id === 'R3-hex' && f.endsWith('.ts')) continue
      if (!r.re.test(code)) continue
      if (scopedAllowed(f, r.id, line)) continue         // 逐项白名单（文件+规则+精确行）
      // R6 棘轮：这四个档位的裸字号只计数、不报错（存量受基线把守）
      if (r.id === 'R6-fontsize' && R6_NAKED_RE.test(code)) { r6NakedCount++; continue }
      hits.push({ f, n: i + 1, id: r.id, msg: r.msg, text: trimmed.slice(0, 120) })
    }
  })
}

if (r6NakedCount > R6_NAKED_BASELINE) {
  console.error(`R6 棘轮失败：裸字号(20/24/28/32rpx) 存量 ${r6NakedCount}，超过基线 ${R6_NAKED_BASELINE}。新增了裸字号，请改用 var(--fs-*)。`)
  process.exit(1)
}
console.log(`R6 棘轮：裸字号(20/24/28/32rpx) 存量 ${r6NakedCount} / 基线 ${R6_NAKED_BASELINE}（只拦增量，清理另立项）`)

for (const h of hits) console.log(`${h.f}:${h.n}  [${h.id}] ${h.msg}\n    → ${h.text}`)
const byRule = hits.reduce((m, h) => (m[h.id] = (m[h.id] || 0) + 1, m), {})
console.log(`\n扫描 ${files.length} 个文件，违规 ${hits.length} 处`)
console.log('按规则汇总：', Object.keys(byRule).length ? JSON.stringify(byRule, null, 2) : '{}（全绿）')
process.exit(hits.length ? 1 : 0)
