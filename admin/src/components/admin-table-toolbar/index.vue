<template>
    <div class="admin-table-toolbar">
        <div class="admin-table-toolbar__left">
            <slot name="left">
                <span v-if="hint" class="admin-table-toolbar__hint">{{ hint }}</span>
            </slot>
        </div>
        <div class="admin-table-toolbar__right">
            <slot name="right" />
        </div>
    </div>
</template>

<script lang="ts" setup name="AdminTableToolbar">
/**
 * 表格上方工具条：左侧说明/批量提示，右侧操作按钮。
 *
 * 之前每个列表页都自己写一遍 `mb-4 flex items-center justify-between`，
 * 批量选中数量、导出、新增按钮的位置各不相同。统一到这里。
 */
defineProps<{ hint?: string }>()
</script>

<style lang="scss" scoped>
.admin-table-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;

    &__left {
        min-width: 0;
        flex: 1;
    }

    &__right {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }

    &__hint {
        font-size: 13px;
        line-height: 1.6;
        color: var(--admin-color-muted);
    }
}
</style>
