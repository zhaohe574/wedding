// 目标文件：newuniapp/scripts/redesign-ac-check.mjs
// 来源：docs/redesign/03-组件样式清单与验收.md §8.2「A 组内嵌 Node 自动检查脚本」
// 用法：cd newuniapp && node scripts/redesign-ac-check.mjs [srcRoot] [reportPath]
// 输出：每条违规 = AC编号 | 相对路径:行号 | 命中内容；末尾按 AC 汇总；任一 FAIL 则 exit 1
import fs from 'node:fs'
import path from 'node:path'

const ROOT = process.argv[2] || 'src'
const REPORT = process.argv[3] || null
const EXCLUDE = [/uni_modules/, /node_modules/, /dist/]
const EXTS = new Set(['.vue', '.scss', '.css'])
// 令牌定义处豁免（与 tokens.css 同口径）：tn-theme.scss 是图鸟变量桥，其 hex 为图鸟基线原值与 sass 混色常量
const IGNORE = [
  /styles[\\/]tokens\.css$/,
  /styles[\\/]tailwind\.css$/,
  /styles[\\/]tn-theme\.scss$/,
  /[\\/]uni\.scss$/
]
const ALLOW_RAW = [
  /font-size:\s*(20|24|28|32)rpx/,   // §7.9 price ¥ 按档位
  /font-size:\s*20rpx/,               // §7.2 数字角标白名单
  /border-radius:\s*999rpx/,          // --r-pill
  /width:\s*(4|6)rpx/                 // §7.8 竖条
]

// T-37 逐项白名单（与 redesign-zero-check.mjs 同源口径）：按「文件 + AC 规则 + 精确行内容」登记依据，禁止整页/整目录。
// 每条 why 写明：所属规则、依据章节、为何不改源码。与 03 §5.8「白名单登记义务」一致。
const ALLOW_SCOPED = [
  // AC-A-10：160/180/200/220rpx 均为尺寸位/吸底占位（非 7 档间距 --sp-1..7），03 §7.11/§7.12/§5.2/§5.7 已裁决
  { ac:'AC-A-10', file:'components/app-toast/app-toast.vue', line:/bottom:\s*160rpx/, why:'03 §7.12 app-toast bottom:160rpx（位置非间距）' },
  { ac:'AC-A-10', file:'components/result-page/result-page.vue', line:/(width|height):\s*160rpx/, why:'03 §7.11 圆形底直径 160rpx（尺寸非间距）' },
  { ac:'AC-A-10', file:'packages/pkg-catalog/pages/booking/booking.vue', line:/padding-bottom:\s*calc\(180rpx \+ var\(--safe-bottom\)\)/, why:'03 §5.7 吸底占位（与 fixed 底栏等高）' },
  { ac:'AC-A-10', file:'packages/pkg-order/pages/detail/detail.vue', line:/padding-bottom:\s*calc\(180rpx \+ var\(--safe-bottom\)\)/, why:'03 §5.7 吸底占位' },
  { ac:'AC-A-10', file:'packages/pkg-service/pages/aftersale/complaint-list.vue', line:/padding-bottom:\s*calc\(160rpx \+ var\(--safe-bottom\)\)/, why:'03 §5.7 吸底占位' },
  { ac:'AC-A-10', file:'packages/pkg-service/pages/aftersale/ticket-list.vue', line:/padding-bottom:\s*calc\(160rpx \+ var\(--safe-bottom\)\)/, why:'03 §5.7 吸底占位' },
  { ac:'AC-A-10', file:'packages/pkg-catalog/pages/staff-detail/staff-detail.vue', line:/padding-bottom:\s*180rpx/, why:'03 §5.7 吸底占位' },
  { ac:'AC-A-10', file:'packages/pkg-catalog/pages/staff-list/staff-list.vue', line:/(max-width|width|height):\s*(160|180|220)rpx/, why:'03 §7.4 列表缩略图/头像尺寸位' },
  { ac:'AC-A-10', file:'packages/pkg-order/pages/detail/detail.vue', line:/max-width:\s*180rpx/, why:'03 §7.4 详情缩略图尺寸位' },
  { ac:'AC-A-10', file:'packages/pkg-order/pages/review/publish.vue', line:/(min-height|width|height):\s*(160|220)rpx/, why:'03 §5.2 上传位/缩略图尺寸位' },
  { ac:'AC-A-10', file:'packages/pkg-service/pages/aftersale/ticket-create.vue', line:/(min-height|width|height):\s*(160|220)rpx/, why:'03 §5.2 上传位尺寸位' },
  { ac:'AC-A-10', file:'packages/pkg-service/pages/aftersale/complaint-create.vue', line:/(min-height|width|height):\s*(160|200)rpx/, why:'03 §5.2 上传位尺寸位' },
  { ac:'AC-A-10', file:'packages/pkg-service/pages/aftersale/complaint-detail.vue', line:/(min-height|width|height):\s*(160|180)rpx/, why:'03 §5.2 内容区/缩略图尺寸位' },
  { ac:'AC-A-10', file:'packages/pkg-service/pages/aftersale/callback-detail.vue', line:/min-height:\s*200rpx/, why:'03 §5.2 长文本区最小高度' },
  { ac:'AC-A-10', file:'packages/pkg-service/pages/aftersale/ticket-detail.vue', line:/(min-height|width|height):\s*160rpx/, why:'03 §5.2 内容区/缩略图尺寸位' },
  { ac:'AC-A-10', file:'packages/pkg-service/pages/questionnaire/detail.vue', line:/min-height:\s*200rpx/, why:'03 §5.2 文本域最小高度' },
  { ac:'AC-A-10', file:'packages/pkg-service/pages/change/apply-date.vue', line:/(min-height|width|height):\s*(160|200)rpx/, why:'03 §5.2 上传位尺寸位' },
  { ac:'AC-A-10', file:'packages/pkg-service/pages/change/apply-pause.vue', line:/(min-height|width|height):\s*(160|200)rpx/, why:'03 §5.2 上传位尺寸位' },
  { ac:'AC-A-10', file:'packages/pkg-service/pages/change/apply-addon.vue', line:/min-height:\s*200rpx/, why:'03 §5.2 上传位最小高度' },
  { ac:'AC-A-10', file:'packages/pkg-service/pages/change/detail.vue', line:/(width|height):\s*160rpx/, why:'03 §7.4 缩略图尺寸位' },
  { ac:'AC-A-10', file:'packages/pkg-staff/pages/schedule/schedule.vue', line:/min-width:\s*220rpx/, why:'03 §7.15 日历格最小宽度（7 列不折行）' },
  { ac:'AC-A-10', file:'pages/index/index.vue', line:/(padding:\s*160rpx|(height|width):\s*(160|200)rpx)/, why:'03 §5.4.1 品牌头留白 + §7.4 缩略图尺寸位' },
  { ac:'AC-A-10', file:'pages/login/login.vue', line:/(width|height):\s*160rpx/, why:'03 §7.4 Logo/头像尺寸位' },
  { ac:'AC-A-10', file:'pages/search/search.vue', line:/width:\s*160rpx/, why:'03 §7.4 缩略图尺寸位' },
  // AC-A-01：原生组件属性只接受字面色值，不支持 var()；uni.scss 由 IGNORE 排除
  { ac:'AC-A-01', file:'pages/index/index.vue', line:/indicator-active-color="#FFFFFF"/, why:'swiper 原生属性只接受字面色值（03 §7.10）' },
  { ac:'AC-A-01', file:'packages/pkg-order/pages/review/publish.vue', line:/<switch[^>]*color="#C6A15B"/, why:'switch 原生组件属性只接受字面色值（品牌色）' },
  // ---- v2 24 屏新增（逐项登记）----
  { ac:'AC-A-10', file:'packages/pkg-catalog/pages/booking/booking.vue', line:/max-width: 200rpx/, why:'03 §7.4 标签尺寸位，非间距' },
  { ac:'AC-A-10', file:'packages/pkg-catalog/pages/staff-detail/staff-detail.vue', line:/max-width: 220rpx/, why:'03 §7.4 标签尺寸位，非间距' },
  { ac:'AC-A-10', file:'packages/pkg-service/pages/questionnaire/detail.vue', line:/padding: 30rpx 42rpx 180rpx/, why:'03 §5.7 吸底提交栏等高占位（180rpx）' },
  { ac:'AC-A-01', file:'packages/pkg-user/pages/activity/activity.vue', line:/confirmColor: '#B4453C'/, why:'uni.showModal 原生参数只接受字面 hex，与 --c-danger 同值' },
  { ac:'AC-A-01', file:'pages/message/message.vue', line:/backgroundColor: '#B4453C'/, why:'tn-swipe-action options.style 行内样式，wxss 变量不生效' },
  { ac:'AC-A-12', file:'packages/pkg-catalog/pages/booking/booking.vue', line:/transform: scale\(1\)/, why:'选中态勾选标记显隐动画（非按下态）' },
  { ac:'AC-A-12', file:'packages/pkg-catalog/pages/booking/booking.vue', line:/transform: scale\(0\.7\)/, why:'选中态勾选标记显隐动画（非按下态）' },
  { ac:'AC-A-12', file:'packages/pkg-order/pages/payment-result/payment-result.vue', line:/transform: scale\(0\.86\)/, why:'压印印章入场动画 seal-in，非按下态' },
  { ac:'AC-A-12', file:'packages/pkg-order/pages/payment-result/payment-result.vue', line:/transform: scale\(1\)/, why:'压印印章入场动画 seal-in 终态' },
]

/** 逐项白名单判定：仅当文件 + AC 规则 + 精确行内容三者同时命中才豁免 */
function scopedAllowed(f, ac, line) {
  const norm = f.replace(/\\/g, '/')
  return ALLOW_SCOPED.some(a => norm.endsWith(a.file) && a.ac === ac && a.line.test(line))
}

const RULES = [
  { ac:'AC-A-01', re:/#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})\b/, msg:'hex 字面色值' },
  { ac:'AC-A-02', re:/variant="(brand|primary)"/, msg:'variant 历史别名' },
  { ac:'AC-A-03', re:/error-kind="(network|business|permission|unknown)"/, msg:'error-kind 静态字面量' },
  { ac:'AC-A-04', re:/\.(loadmore__text|steps__dot|steps__label|fallback__title|fallback__desc|picker-mask)\b/, msg:'私有样式类', pageOnly:true },
  { ac:'AC-A-05', re:/'(加载中…|没有更多了|网络开小差了，请检查网络后重试|加载失败，请稍后重试|重新加载|暂无数据|支付结果待确认)'|uni\.showToast\s*\(/, msg:'中文状态字面量 / 原生 Toast' },
  { ac:'AC-A-06', re:/font-size:\s*(?!var\(--fs-)[0-9]+(?:r?px|em|rem)/, msg:'裸 font-size' },
  { ac:'AC-A-07', re:/font-weight:\s*(700|800|900|bold|bolder|lighter)\b/, msg:'违规字重' },
  { ac:'AC-A-08', re:/line-height:\s*1\.(4|5|6)\b/, msg:'违规行高' },
  { ac:'AC-A-09', re:/border-radius:\s*(?!var\(--r-)[0-9]+(?:r?px|%)/, msg:'裸圆角' },
  { ac:'AC-A-10', re:/\b(160|180|200|220)rpx\b/, msg:'私建间距值' },
  { ac:'AC-A-11', re:/color="var\(--c-brand\)"|color="var\(--c-line-strong\)"/, msg:'违规图标/控件色' },
  { ac:'AC-A-12', re:/transform:\s*scale\s*\(/, msg:'transform:scale 按下态（Dialog 0.04 例外须登记）' },
  { ac:'AC-A-13', re:/env\(safe-area-inset-bottom\)|constant\(safe-area/, msg:'业务代码直接写 env()' },
  { ac:'AC-A-14', re:/@import\s+['"][^'"]*(vant|tdesign|tnui|element|antd|bootstrap)/i, msg:'新增大库 @import' },
  { ac:'AC-A-15', re:/var\(--color-|stores\/theme|utils\/theme|decorate\?id=/, msg:'历史死链 / 运行时主题' },
  { ac:'AC-A-16', re:/@media\s*\(?\s*(min|max)-width:\s*(?!340px|600px)\d+px/, msg:'未授权的断点媒体查询' }
]

const files = []
;(function walk(d){ for (const n of fs.readdirSync(d)) {
  const p = path.join(d,n); if (EXCLUDE.some(r=>r.test(p))) continue
  const st = fs.statSync(p)
  if (st.isDirectory()) walk(p)
  else if (EXTS.has(path.extname(n)) && !IGNORE.some(r=>r.test(p))) files.push(p)
}})(ROOT)

const hits = []
for (const f of files) {
  fs.readFileSync(f,'utf8').split(/\r?\n/).forEach((line,i)=>{
    const t = line.trim()
    if (/^(\/\/|\/\*|\*|<!--)/.test(t)) return
    if (ALLOW_RAW.some(r=>r.test(line))) return
    for (const r of RULES) {
      if (r.pageOnly && f.includes(`${path.sep}components${path.sep}`)) continue
      if (!r.re.test(line)) continue
      if (scopedAllowed(f, r.ac, line)) continue        // T-37 逐项白名单（文件+AC+精确行）
      hits.push({ ac:r.ac, f, n:i+1, msg:r.msg, text:t.slice(0,110) })
    }
  })
}
const lines = []
for (const h of hits) { const s = `${h.ac} | ${h.f}:${h.n} | ${h.msg}\n    → ${h.text}`; console.log(s); lines.push(s) }
const byAc = hits.reduce((m,h)=>(m[h.ac]=(m[h.ac]||0)+1,m),{})
const summary = `\n扫描 ${files.length} 个文件；违规 ${hits.length} 处\n按 AC 汇总：${Object.keys(byAc).length?JSON.stringify(byAc,null,2):'{}（全绿）'}`
console.log(summary)
if (REPORT) fs.writeFileSync(REPORT, lines.concat(summary).join('\n'), 'utf8')
process.exit(hits.length ? 1 : 0)
