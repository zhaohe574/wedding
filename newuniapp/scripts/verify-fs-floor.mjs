// 目标文件：newuniapp/scripts/verify-fs-floor.mjs
// 来源：docs/redesign/03-组件样式清单与验收.md §2.3「375px 基准屏取值不变的自证方式」
// 用法：cd newuniapp && node scripts/verify-fs-floor.mjs
// 输出：各屏宽下 --fs-xs / --fs-md 原值与方案 A 求值；375px 基准屏取值不变即 PASS
const WIDTHS = [320, 340, 375, 390, 414, 430, 600, 768];
const oldXs = w => 24 * w / 750, oldMd = w => 28 * w / 750;
const newXs = w => Math.max(oldXs(w), 12), newMd = w => Math.max(oldMd(w), 14);
let pass = true;
for (const w of WIDTHS) {
  const xsEq = Math.abs(newXs(w) - oldXs(w)) < 1e-9 || w <= 340;
  const mdEq = Math.abs(newMd(w) - oldMd(w)) < 1e-9 || w <= 340;
  const unchangedAt375 = Math.abs(newXs(375) - oldXs(375)) < 1e-9 && Math.abs(newMd(375) - oldMd(375)) < 1e-9;
  if (w >= 375 && !(xsEq && mdEq)) pass = false;
  console.log(`${String(w).padStart(4)}px  xs ${oldXs(w).toFixed(2)}→${newXs(w).toFixed(2)}  md ${oldMd(w).toFixed(2)}→${newMd(w).toFixed(2)}  ${w>=375 ? '等价' : '下限抬升'}`);
}
console.log(`\n375px 基准屏取值不变：${Math.abs(newXs(375)-oldXs(375))<1e-9 && Math.abs(newMd(375)-oldMd(375))<1e-9 ? 'PASS' : 'FAIL'}`);
console.log(`全部 ≥375px 等价：${pass ? 'PASS（截图误差应为 0）' : 'FAIL'}`);
process.exit(pass ? 0 : 1);
