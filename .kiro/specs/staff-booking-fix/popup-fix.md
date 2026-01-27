# 购物车弹窗修复说明

## 问题描述
"保存为方案"弹窗在点击"确定"按钮后无法关闭，一直停留在屏幕上。

## 问题原因
使用了错误的组件名称。项目使用的是 `vk-uview-ui` UI 库，但代码中使用了 `<uni-popup>` 组件，而该组件在项目中不存在。

正确的组件应该是 `<u-popup>`（来自 vk-uview-ui）。

## 解决方案

### 1. 组件名称修改
```vue
<!-- 修改前 -->
<uni-popup ref="planPopup" type="center">
  ...
</uni-popup>

<!-- 修改后 -->
<u-popup v-model="showPlanPopup" mode="center" :border-radius="24">
  ...
</u-popup>
```

### 2. 变量声明修改
```typescript
// 修改前
const planPopup = ref()

// 修改后
const showPlanPopup = ref(false)
```

### 3. 打开弹窗逻辑修改
```typescript
// 修改前
planPopup.value.open()

// 修改后
showPlanPopup.value = true
```

### 4. 关闭弹窗逻辑修改
```typescript
// 修改前
planPopup.value?.close()

// 修改后
showPlanPopup.value = false
```

## 技术要点

### vk-uview-ui 弹窗组件使用方式
- **组件名称**: `<u-popup>`
- **控制方式**: 使用 `v-model` 双向绑定
- **打开**: 设置绑定值为 `true`
- **关闭**: 设置绑定值为 `false`

### 与 uni-popup 的区别
| 特性 | uni-popup | u-popup (vk-uview-ui) |
|------|-----------|----------------------|
| 控制方式 | ref + open()/close() | v-model 双向绑定 |
| 组件来源 | uni-ui | vk-uview-ui |
| 项目中是否可用 | ❌ 否 | ✅ 是 |

## 修改文件
- `uniapp/src/packages/pages/cart/cart.vue`

## 测试验证
1. 打开购物车页面
2. 选择至少一个商品
3. 点击"保存方案"按钮
4. 输入方案名称
5. 点击"确定"按钮
6. ✅ 验证弹窗正常关闭
7. ✅ 验证显示"保存成功"提示

## 相关文档
- vk-uview-ui 文档: https://vkuviewdoc.fsq.pub/
- u-popup 组件文档: https://vkuviewdoc.fsq.pub/components/popup.html
