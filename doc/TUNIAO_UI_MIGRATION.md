# 图鸟 UI 组件迁移指南

## uView UI → 图鸟 UI 组件映射表

### 基础组件

| uView UI | 图鸟 UI | 说明 |
|----------|---------|------|
| `<u-icon>` | `<tn-icon>` | 图标组件 |
| `<u-button>` | `<tn-button>` | 按钮组件 |
| `<u-avatar>` | `<tn-avatar>` | 头像组件 |
| `<u-image>` | `<tn-image>` | 图片组件 |

### 表单组件

| uView UI | 图鸟 UI | 说明 |
|----------|---------|------|
| `<u-input>` | `<tn-input>` | 输入框 |
| `<u-search>` | `<tn-search-box>` | 搜索框 |
| `<u-checkbox>` | `<tn-checkbox>` | 复选框 |
| `<u-radio>` | `<tn-radio>` | 单选框 |
| `<u-switch>` | `<tn-switch>` | 开关 |
| `<u-picker>` | `<tn-picker>` | 选择器 |
| `<u-form-item>` | `<tn-form-item>` | 表单项 |
| `<u-verification-code>` | 自定义实现 | 验证码倒计时 |

### 反馈组件

| uView UI | 图鸟 UI | 说明 |
|----------|---------|------|
| `<u-popup>` | `<tn-popup>` | 弹窗 |
| `<u-modal>` | `<tn-modal>` | 模态框 |
| `<u-action-sheet>` | `<tn-action-sheet>` | 操作菜单 |
| `<u-toast>` | `uni.showToast` | 提示（使用原生） |
| `<u-loading>` | `<tn-loading>` | 加载中 |
| `<u-empty>` | `<tn-empty>` | 空状态 |

### 导航组件

| uView UI | 图鸟 UI | 说明 |
|----------|---------|------|
| `<u-navbar>` | `<tn-navbar>` | 导航栏 |
| `<u-sticky>` | `<tn-sticky>` | 吸顶 |
| `<u-back-top>` | 保留或自定义 | 返回顶部 |

### 其他组件

| uView UI | 图鸟 UI | 说明 |
|----------|---------|------|
| `<u-parse>` | 保留或使用 `rich-text` | HTML解析 |
| `<u-badge>` | `<tn-badge>` | 徽标 |
| `<u-tag>` | `<tn-tag>` | 标签 |

## 属性映射

### 按钮 (Button)

```vue
<!-- uView -->
<u-button 
  type="primary" 
  shape="circle" 
  size="mini"
  :plain="true"
  :customStyle="{}"
>

<!-- 图鸟 UI -->
<tn-button 
  type="primary" 
  shape="round" 
  size="sm"
  :plain="true"
  :custom-style="{}"
>
```

### 图标 (Icon)

```vue
<!-- uView -->
<u-icon name="arrow-right" size="22" color="#666" />

<!-- 图鸟 UI -->
<tn-icon name="right" size="22" color="#666" />
```

### 弹窗 (Popup)

```vue
<!-- uView -->
<u-popup 
  v-model="show" 
  mode="center" 
  :closeable="true"
  border-radius="20"
  :maskCloseAble="false"
>

<!-- 图鸟 UI -->
<tn-popup 
  v-model="show" 
  mode="center" 
  :close-btn="true"
  border-radius="20"
  :mask-close-able="false"
>
```

## 注意事项

1. **图标名称变化**：
   - `arrow-right` → `right`
   - `arrow-left` → `left`
   - `arrow-up` → `up`
   - `arrow-down` → `down`
   - `star-fill` → `star-fill`（保持不变）

2. **按钮形状**：
   - `shape="circle"` → `shape="round"`

3. **尺寸规格**：
   - `size="mini"` → `size="sm"`
   - `size="medium"` → `size="md"`
   - `size="large"` → `size="lg"`

4. **属性命名**：
   - 驼峰命名改为短横线：`customStyle` → `custom-style`
   - `maskCloseAble` → `mask-close-able`

5. **保留组件**：
   - `<u-back-top>` - 图鸟 UI 无此组件，保留使用
   - `<u-parse>` - 富文本解析，可保留或使用原生 `<rich-text>`
