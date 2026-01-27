# 设计文档 - 首页轮播图高度自定义配置

## 概述

本设计文档描述了为首页轮播图组件添加高度自定义配置功能的技术实现方案。该功能允许管理员在装修系统中灵活配置轮播图的显示高度，提升页面设计的灵活性。

## 架构

### 系统架构

```
┌─────────────────────────────────────────────────────────────┐
│                      装修管理系统 (Admin)                      │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  轮播图属性配置面板 (banner/attr.vue)                  │   │
│  │  - 展示样式选择 (常规/大屏)                            │   │
│  │  - 高度配置输入框 (新增)                               │   │
│  │  - 图片上传和链接配置                                  │   │
│  └──────────────────────────────────────────────────────┘   │
│                          ↓ 保存配置                          │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  装修数据模型 (options.ts)                             │   │
│  │  content: {                                           │   │
│  │    style: 1 | 2,                                      │   │
│  │    height: number,  // 新增字段                        │   │
│  │    data: [...],                                       │   │
│  │  }                                                    │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                          ↓ API 传输
┌─────────────────────────────────────────────────────────────┐
│                    移动端应用 (UniApp)                        │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  轮播图组件 (banner/banner.vue)                        │   │
│  │  - 读取配置的高度值                                    │   │
│  │  - 传递给 LSwiper 组件                                 │   │
│  └──────────────────────────────────────────────────────┘   │
│                          ↓                                   │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  轮播组件 (l-swiper/l-swiper.vue)                      │   │
│  │  - 接收动态高度参数                                    │   │
│  │  - 渲染指定高度的轮播图                                │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

### 数据流

1. **配置阶段**：管理员在装修系统中设置轮播图高度 → 数据保存到 content.height 字段
2. **传输阶段**：装修数据通过 API 传输到移动端
3. **渲染阶段**：移动端读取 content.height → 传递给轮播组件 → 按指定高度渲染

## 组件和接口

### 1. 后台管理组件

#### 1.1 轮播图属性配置组件 (banner/attr.vue)

**新增配置项：**

```vue
<el-card shadow="never" class="!border-none flex mt-2">
    <div class="flex items-end mb-4">
        <div class="text-base text-[#101010] dark:text-[#ffffff] font-medium">
            轮播图高度
        </div>
        <div class="text-xs text-tx-secondary ml-2">
            {{ heightTip }}
        </div>
    </div>
    <el-form-item label="高度">
        <el-input-number
            v-model="contentData.height"
            :min="100"
            :max="2000"
            :step="10"
            controls-position="right"
            placeholder="请输入高度"
        >
            <template #append>rpx</template>
        </el-input-number>
    </el-form-item>
    <div v-if="showHeightWarning" class="text-warning text-xs mt-2">
        {{ heightWarningText }}
    </div>
</el-card>
```

**计算属性：**

```typescript
// 高度提示文字
const heightTip = computed(() => {
    return props.content.style === 1 
        ? '建议高度：250-500rpx' 
        : '建议高度：800-1200rpx'
})

// 默认高度
const defaultHeight = computed(() => {
    return props.content.style === 1 ? 321 : 1100
})

// 是否显示警告
const showHeightWarning = computed(() => {
    const height = contentData.value.height || defaultHeight.value
    if (props.content.style === 1) {
        return height < 250 || height > 500
    } else {
        return height < 800 || height > 1200
    }
})

// 警告文字
const heightWarningText = computed(() => {
    return '当前高度超出建议范围，可能影响显示效果'
})
```

**监听样式切换：**

```typescript
watch(() => props.content.style, (newStyle) => {
    // 当样式切换时，如果高度未自定义，则使用新样式的默认高度
    if (!contentData.value.height) {
        contentData.value.height = newStyle === 1 ? 321 : 1100
    }
})
```

#### 1.2 数据模型 (banner/options.ts)

**更新数据结构：**

```typescript
export default () => ({
    title: '首页轮播图',
    name: 'banner',
    content: {
        enabled: 1,
        style: 1, // 展示样式---1=常规，2=大屏
        bg_style: 0, // 背景联动---0=关闭，1=开启
        height: undefined, // 新增：轮播图高度，单位rpx，未设置时使用默认值
        data: [
            {
                is_show: '1',
                image: '',
                bg: '',
                name: '',
                link: {}
            }
        ]
    },
    styles: {}
})
```

#### 1.3 预览组件 (banner/content.vue)

**更新预览高度计算：**

```vue
<template>
    <div class="banner" :style="styles">
        <div class="banner-image w-full h-full">
            <decoration-img
                width="100%"
                :height="previewHeight"
                :src="getImage"
                fit="contain"
            />
        </div>
    </div>
</template>

<script lang="ts" setup>
const previewHeight = computed(() => {
    // 使用配置的高度或默认高度
    const configHeight = props.content.height
    const defaultHeight = props.content.style === 1 ? 321 : 1100
    const height = configHeight || defaultHeight
    
    // 转换为预览区域的像素值 (rpx to px, 按2倍比例)
    const pxHeight = height / 2
    
    return props.content.style === 1 ? `${pxHeight}px` : '550px'
})
</script>
```

### 2. 移动端组件

#### 2.1 轮播图组件 (banner/banner.vue)

**更新高度传递逻辑：**

```vue
<template>
    <view
        class="banner translate-y-0"
        :class="{ 'px-[20rpx]': !isLargeScreen }"
        v-if="content.data.length && content.enabled"
    >
        <LSwiper
            :content="content"
            :height="bannerHeight"
            :circular="true"
            :effect3d="false"
            :border-radius="isLargeScreen ? '0' : '14'"
            interval="7000"
            bgColor="transparent"
            @change="handleChange"
        ></LSwiper>
    </view>
</template>

<script setup lang="ts">
// 计算轮播图高度
const bannerHeight = computed(() => {
    // 优先使用配置的高度
    if (props.content.height) {
        return String(props.content.height)
    }
    
    // 使用默认高度
    return props.isLargeScreen ? '1100' : '321'
})
</script>
```

#### 2.2 轮播组件 (l-swiper/l-swiper.vue)

**组件已支持动态高度，无需修改**

当前组件已通过 `height` prop 接收高度参数，可以直接使用。

## 数据模型

### 装修数据结构

```typescript
interface BannerContent {
    enabled: 0 | 1                    // 是否启用
    style: 1 | 2                      // 展示样式：1=常规，2=大屏
    bg_style: 0 | 1                   // 背景联动：0=关闭，1=开启
    height?: number                   // 轮播图高度（rpx），可选字段
    data: BannerItem[]                // 轮播图数据
}

interface BannerItem {
    is_show: '0' | '1'                // 是否显示
    image: string                     // 轮播图片URL
    bg: string                        // 背景图片URL
    name: string                      // 图片名称
    link: LinkConfig                  // 跳转链接配置
}

interface LinkConfig {
    path?: string                     // 链接路径
    name?: string                     // 链接名称
    type?: string                     // 链接类型
}
```

### 高度配置规则

| 样式模式 | 默认高度 | 建议范围 | 有效范围 | 单位 |
|---------|---------|---------|---------|------|
| 常规模式 | 321 | 250-500 | 100-2000 | rpx |
| 大屏模式 | 1100 | 800-1200 | 100-2000 | rpx |

## 正确性属性

*属性是一种特征或行为，应该在系统的所有有效执行中保持为真——本质上是关于系统应该做什么的正式声明。属性充当人类可读规范和机器可验证正确性保证之间的桥梁。*

### 属性 1：高度配置范围验证

*对于任何* 轮播图高度配置值，该值必须在100到2000之间（包含边界），或者为undefined（使用默认值）

**验证：需求 1.5, 1.6**

### 属性 2：默认高度一致性

*对于任何* 未配置高度的轮播图，当样式为常规模式时高度应为321rpx，当样式为大屏模式时高度应为1100rpx

**验证：需求 1.2, 1.3, 3.3**

### 属性 3：高度值传递完整性

*对于任何* 在后台配置的轮播图高度值，该值必须完整地传递到前端组件并正确渲染

**验证：需求 2.1, 2.4, 3.1, 3.2**

### 属性 4：样式切换高度保持

*对于任何* 已配置自定义高度的轮播图，当展示样式在常规和大屏之间切换时，自定义高度值应保持不变

**验证：需求 3.4**

### 属性 5：向后兼容性

*对于任何* 不包含height字段的旧装修数据，系统应根据style字段自动使用对应的默认高度值

**验证：需求 5.1, 5.2, 5.3**

## 错误处理

### 输入验证错误

| 错误场景 | 错误处理 | 用户提示 |
|---------|---------|---------|
| 输入非数字字符 | 阻止输入，保持原值 | "请输入有效的数字" |
| 输入值 < 100 | 阻止保存，显示错误提示 | "高度不能小于100rpx" |
| 输入值 > 2000 | 阻止保存，显示错误提示 | "高度不能大于2000rpx" |
| 输入值在有效范围但超出建议范围 | 允许保存，显示警告 | "当前高度超出建议范围，可能影响显示效果" |

### 数据加载错误

| 错误场景 | 错误处理 | 降级方案 |
|---------|---------|---------|
| height字段不存在 | 使用默认值 | 根据style使用321或1100 |
| height字段为null | 使用默认值 | 根据style使用321或1100 |
| height字段为非法值 | 使用默认值并记录日志 | 根据style使用321或1100 |
| 装修数据加载失败 | 显示错误提示 | 使用全局默认配置 |

### 渲染错误

| 错误场景 | 错误处理 | 降级方案 |
|---------|---------|---------|
| 高度值解析失败 | 使用默认高度 | 321rpx (常规) / 1100rpx (大屏) |
| 组件渲染异常 | 捕获错误并记录 | 显示占位图或隐藏组件 |

## 测试策略

### 单元测试

**后台管理组件测试：**

1. 测试高度输入框的数值验证（最小值、最大值、步长）
2. 测试样式切换时的默认高度设置
3. 测试警告提示的显示逻辑
4. 测试数据模型的序列化和反序列化

**前端组件测试：**

1. 测试高度值的正确传递
2. 测试默认高度的回退逻辑
3. 测试不同样式模式下的高度计算

### 属性测试

**测试配置：**
- 最小迭代次数：100次
- 测试框架：根据项目技术栈选择（Vue Test Utils + Vitest）

**属性测试用例：**

1. **属性 1 测试：高度范围验证**
   - 生成随机高度值（包括边界值、负数、超大值）
   - 验证只有100-2000范围内的值被接受
   - **标签：Feature: banner-height-config, Property 1: 高度配置范围验证**

2. **属性 2 测试：默认高度一致性**
   - 生成随机样式配置（常规/大屏）
   - 验证未配置高度时使用正确的默认值
   - **标签：Feature: banner-height-config, Property 2: 默认高度一致性**

3. **属性 3 测试：高度值传递完整性**
   - 生成随机高度配置
   - 验证配置值正确传递到渲染组件
   - **标签：Feature: banner-height-config, Property 3: 高度值传递完整性**

4. **属性 4 测试：样式切换高度保持**
   - 生成随机自定义高度和样式切换序列
   - 验证自定义高度在样式切换后保持不变
   - **标签：Feature: banner-height-config, Property 4: 样式切换高度保持**

5. **属性 5 测试：向后兼容性**
   - 生成不包含height字段的旧数据
   - 验证系统正确使用默认值
   - **标签：Feature: banner-height-config, Property 5: 向后兼容性**

### 集成测试

1. **端到端配置流程测试：**
   - 在装修系统中配置轮播图高度
   - 保存并发布
   - 在移动端验证显示效果

2. **多场景测试：**
   - 常规模式 + 自定义高度
   - 大屏模式 + 自定义高度
   - 样式切换场景
   - 旧数据兼容场景

### 手动测试

1. **视觉回归测试：**
   - 对比不同高度配置下的显示效果
   - 验证图片裁剪和缩放是否正常
   - 检查不同设备上的显示一致性

2. **用户体验测试：**
   - 验证输入提示的清晰度
   - 验证实时预览的准确性
   - 验证错误提示的友好性

## 实现注意事项

### 1. 单位转换

- 后台配置使用 rpx 单位
- 预览区域需要转换为 px（rpx / 2）
- 移动端直接使用 rpx 值

### 2. 数据迁移

- 新增字段为可选字段，不影响现有数据
- 旧数据加载时自动补充默认值
- 不需要数据库迁移脚本

### 3. 性能考虑

- 高度配置变化时使用防抖处理
- 预览更新使用 computed 而非 watch
- 避免不必要的组件重渲染

### 4. 兼容性

- 确保在不同屏幕尺寸下的显示效果
- 考虑不同设备的 rpx 转换比例
- 保持与现有轮播图功能的兼容性

### 5. 代码规范

- 遵循项目现有的代码风格
- 使用 TypeScript 类型定义
- 添加必要的中文注释
- 使用 Composition API 风格
