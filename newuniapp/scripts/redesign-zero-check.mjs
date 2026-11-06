// 目标文件：newuniapp/scripts/redesign-zero-check.mjs
// 来源：docs/redesign/03-组件样式清单与验收.md §5.8「(B) Node 归零核验脚本」
// 用法：cd newuniapp && node scripts/redesign-zero-check.mjs [srcRoot]
// 输出：每条违规 = 相对路径:行号  [规则] 命中内容；末尾汇总，任一 FAIL 则 exit 1
import fs from 'node:fs'
import path from 'node:path'

const ROOT = process.argv[2] || 'src'
const EXCLUDE = [/uni_modules/, /node_modules/, /dist/]
const EXTS = new Set(['.vue', '.scss', '.css', '.ts'])
const IGNORE_FILES = [/styles[\\/]tokens\.css$/, /styles[\\/]tailwind\.css$/]

// 已登记白名单：裸数值但属视觉规范明确允许（逐项登记依据，禁止整页/整目录）
const ALLOW_RAW = [
  /font-size:\s*(20|24|28|32)rpx/,          // price 的 ¥ 按档位（§7.9）
  /font-size:\s*20rpx/,                       // tabBar 数字角标白名单（§7.2）
  /border-radius:\s*999rpx/,                  // --r-pill 定义场景
  /width:\s*(4|6)rpx/,                        // section-header 竖条
  /bottom:\s*160rpx/,                         // app-toast 位置（§7.12，是位置非间距）
  /(width|height):\s*160rpx/,                 // result-page 圆形底直径（§7.11，是尺寸非间距）
]

// ALLOW_SCOPED：按「文件 + 精确行内容」逐项登记依据（禁止整页/整目录加入 ALLOW_RAW）。
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
  { file: 'packages/pkg-service/pages/questionnaire/detail.vue', rule: 'R8-spacing', line: /min-height:\s*200rpx/, why: '03 §5.2 文本域最小高度' },
  { file: 'packages/pkg-staff/pages/schedule/schedule.vue', rule: 'R8-spacing', line: /min-width:\s*220rpx/, why: '03 §7.15 日历格最小宽度（尺寸位，7 列不折行）' },
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

const hits = []
for (const f of files) {
  const lines = fs.readFileSync(f, 'utf8').split(/\r?\n/)
  lines.forEach((line, i) => {
    const trimmed = line.trim()
    if (/^(\/\/|\/\*|\*|<!--)/.test(trimmed)) return       // 注释行豁免
    if (ALLOW_RAW.some(re => re.test(line))) return          // 已登记白名单
    for (const r of RULES) {
      // R11 私有类只在页面/样式文件判定，组件内部合法类名豁免
      if (r.id === 'R11-private' && f.includes(`${path.sep}components${path.sep}`)) continue
      // R3 hex：仅 .vue/.scss/.css 判定，且排除 SCSS 变量定义（uni.scss 白名单由 IGNORE 控制）
      if (r.id === 'R3-hex' && f.endsWith('.ts')) continue
      if (!r.re.test(line)) continue
      if (scopedAllowed(f, r.id, line)) continue         // 逐项白名单（文件+规则+精确行）
      hits.push({ f, n: i + 1, id: r.id, msg: r.msg, text: trimmed.slice(0, 120) })
    }
  })
}

for (const h of hits) console.log(`${h.f}:${h.n}  [${h.id}] ${h.msg}\n    → ${h.text}`)
const byRule = hits.reduce((m, h) => (m[h.id] = (m[h.id] || 0) + 1, m), {})
console.log(`\n扫描 ${files.length} 个文件，违规 ${hits.length} 处`)
console.log('按规则汇总：', Object.keys(byRule).length ? JSON.stringify(byRule, null, 2) : '{}（全绿）')
process.exit(hits.length ? 1 : 0)
