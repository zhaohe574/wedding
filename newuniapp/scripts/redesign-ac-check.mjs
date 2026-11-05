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
const IGNORE = [/styles[\\/]tokens\.css$/, /styles[\\/]tailwind\.css$/, /[\\/]uni\.scss$/]
const ALLOW_RAW = [
  /font-size:\s*(20|24|28|32)rpx/,   // §7.9 price ¥ 按档位
  /font-size:\s*20rpx/,               // §7.2 数字角标白名单
  /border-radius:\s*999rpx/,          // --r-pill
  /width:\s*(4|6)rpx/                 // §7.8 竖条
]

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
      if (r.re.test(line)) hits.push({ ac:r.ac, f, n:i+1, msg:r.msg, text:t.slice(0,110) })
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
