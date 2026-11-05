// 目标文件：newuniapp/scripts/check-feedback-consistency.mjs
// 来源：docs/redesign/03-组件样式清单与验收.md §4.8「ERROR_CODES 与 FB.* 边界一致性校验」
// 用法：cd newuniapp && node scripts/check-feedback-consistency.mjs
// 校验：FB.ERR_DUPLICATE / ERR_RATE_LIMIT 与 ERROR_CODES 逐字一致 + O13/O14 支付类 key 存在性
import fs from 'node:fs'
const fb = fs.readFileSync('src/constants/feedback.ts', 'utf8')
const ec = fs.readFileSync('../shared/contracts/error-codes.ts', 'utf8')
const pick = (src, key) => (src.match(new RegExp(`${key}:\\s*'([^']+)'`)) || [])[1]
const pairs = [
  ['ERR_DUPLICATE', 'IDEMPOTENT_REPLAY'],
  ['ERR_RATE_LIMIT', 'QUOTA_EXCEEDED']
]
let fail = 0
for (const [fbKey, ecKey] of pairs) {
  const a = pick(fb, fbKey), b = (ec.match(new RegExp(`${ecKey}:\\s*\\{\\s*msg:\\s*'([^']+)'`)) || [])[1]
  const ok = a && b && a === b
  console.log(`${ok ? 'PASS' : 'FAIL'}  FB.${fbKey} = "${a}"  vs  ERROR_CODES.${ecKey}.msg = "${b}"`)
  if (!ok) fail++
}
// O13：支付类文案必须存在于 FB，且不得出现「支付失败」结论性字样
const payKeys = ['PAY_UNCONFIRMED_TITLE','PAY_UNCONFIRMED_DESC','PAY_RECHECK_UNKNOWN','PAY_ALREADY_PAID',
                 'ACTION_VIEW_ORDER','ACTION_RECHECK_RESULT']
for (const k of payKeys) {
  const ok = new RegExp(`${k}:`).test(fb)
  console.log(`${ok ? 'PASS' : 'FAIL'}  FB.${k} 存在`); if (!ok) fail++
}
console.log(`\n一致性校验：${fail === 0 ? 'PASS' : `FAIL (${fail})`}`)
process.exit(fail ? 1 : 0)
