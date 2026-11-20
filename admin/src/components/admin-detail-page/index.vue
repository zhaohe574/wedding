<template>
    <admin-page-shell :title="title" :description="description" :compact="compact">
        <template v-if="$slots.extra" #extra>
            <slot name="extra" />
        </template>

        <el-card class="!border-none" shadow="never">
            <slot name="header" />
            <el-descriptions v-if="items?.length" :column="column" border>
                <el-descriptions-item
                    v-for="item in items"
                    :key="item.label"
                    :label="item.label"
                    :span="item.span || 1"
                >
                    {{ item.value ?? '—' }}
                </el-descriptions-item>
            </el-descriptions>
            <slot />
        </el-card>

        <template v-if="$slots.footer" #footer>
            <slot name="footer" />
        </template>
    </admin-page-shell>
</template>

<script lang="ts" setup name="AdminDetailPage">
/**
 * 详情页模板：标题 + 描述列表（el-descriptions）+ 可选扩展区。
 *
 * 用 items 传字段，省掉每个详情页重复写 `<el-descriptions-item>`；
 * 复杂结构（表格、时间线）走默认插槽，不强行塞进 items。
 */
withDefaults(
    defineProps<{
        title?: string
        description?: string
        items?: Array<{ label: string; value?: string | number | null; span?: number }>
        column?: number
        compact?: boolean
    }>(),
    { items: () => [], column: 2, description: '', title: '', compact: false }
)
</script>
