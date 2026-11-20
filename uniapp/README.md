# ⚠️ 旧包（Legacy）· 已冻结

本目录是旧版微信小程序。自 **2026-11-11** 起冻结，仅作迁移对照与线上存量维护。

## 不要在这里开发

- 新功能、页面、组件、样式、接口、测试一律改 `../newuniapp/`。
- 不要在本目录新增页面、改业务逻辑或重构；后续对话默认只开发 `newuniapp`。
- 旧包只保留历史参考价值；迁移进度与差异见 `../rebuild-plan/`、`../docs/redesign/`。

## 例外

只有用户明确要求修旧包线上紧急问题时，才允许修改本目录，并在提交说明标注 `legacy hotfix`，同时说明原因与影响范围。

## 新包入口

```
cd ../newuniapp
npm run dev:mp-weixin
npm run type-check
npm run build:mp-weixin
```
