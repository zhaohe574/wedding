# tn-image 组件修复说明

## 问题描述

在移动端运行时发现图鸟 UI 不存在 `tn-image` 组件，导致应用报错。

## 解决方案

将所有 `tn-image` 组件替换为 UniApp 原生的 `image` 标签，但保留 `tn-image-upload` 组件（图鸟 UI 的图片上传组件）。

## 修复内容

### 1. 批量替换
使用脚本 `scripts/fix-tn-image.js` 批量替换所有文件中的 `tn-image`：
- `<tn-image` → `<image`（不包括 `tn-image-upload`）
- `</tn-image>` → `</image>`（不包括 `tn-image-upload`）

### 2. 保留组件
- `tn-image-upload` - 图鸟 UI 的图片上传组件，保持不变

### 3. 修复统计
- 总文件数：81 个
- 修改文件数：13 个
- 总替换次数：29 处

### 3. 受影响的文件
- `src/pages/aftersale/index.vue` - 2 处
- `src/components/news-card/news-card.vue` - 1 处
- `src/components/widgets/activity-zone/activity-zone.vue` - 4 处
- `src/components/widgets/customer-reviews/customer-reviews.vue` - 5 处
- `src/components/widgets/customer-service/customer-service.vue` - 1 处
- `src/components/widgets/middle-banner/middle-banner.vue` - 1 处
- `src/components/widgets/my-service/my-service.vue` - 2 处
- `src/components/widgets/nav/nav.vue` - 1 处
- `src/components/widgets/portfolio-gallery/portfolio-gallery.vue` - 4 处
- `src/components/widgets/quick-entry/quick-entry.vue` - 2 处
- `src/components/widgets/service-packages/service-packages.vue` - 3 处
- `src/components/widgets/staff-showcase/staff-showcase.vue` - 2 处
- `src/components/widgets/user-banner/user-banner.vue` - 1 处

## 使用方法

如果以后需要再次修复，可以运行：

```bash
npm run fix:tn-image
```

## 验证结果

修复后运行验证脚本：

```bash
npm run validate:all
```

结果：
- 总文件数：94
- 通过：94
- 失败：0
- 通过率：100%

## 更新内容

### 1. 迁移脚本
更新 `scripts/migrate-to-tuniao.js`：
- 将 `u-image` 映射改为原生 `image` 而不是 `tn-image`

### 2. 文档更新
- `doc/UI_MIGRATION_PROGRESS.md` - 更新组件映射表
- `doc/MIGRATION_COMPLETE_SUMMARY.md` - 更新特殊处理说明

## 注意事项

1. 原生 `image` 标签支持的属性与 `tn-image` 基本一致
2. 保持原有的属性（src、mode、width、height 等）
3. 图鸟 UI 官方不提供 `tn-image` 组件，使用原生组件是正确的做法
4. **重要**：`tn-image-upload` 是图鸟 UI 的图片上传组件，必须保留，不能替换

## 修复日期

2026年1月24日
