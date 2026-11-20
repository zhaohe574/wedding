<template>
    <admin-page-shell :title="title" :description="description" :compact="compact">
        <template v-if="$slots.extra" #extra>
            <slot name="extra" />
        </template>

        <template v-if="$slots.stats" #stats>
            <slot name="stats" />
        </template>

        <el-card class="!border-none" shadow="never">
            <slot />

            <template v-if="$slots.actions">
                <div class="admin-form-page__footer">
                    <slot name="actions" />
                </div>
            </template>
        </el-card>
    </admin-page-shell>
</template>

<script lang="ts" setup name="AdminFormPage">
/**
 * 表单/配置页模板：标题 + 分组卡片 + 底部操作条。
 *
 * 后台的配置页（网站设置、支付配置、功能开关…）都是「一堆 el-form-item + 一个保存按钮」，
 * 但保存按钮有的在右上、有的在底部、有的在表单最后一行。统一到底部操作条，
 * 并保证滚动很长时也够得着。
 */
defineProps<{
    title?: string
    description?: string
    compact?: boolean
}>()
</script>

<style lang="scss" scoped>
.admin-form-page__footer {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid var(--el-border-color-lighter);
}
</style>
