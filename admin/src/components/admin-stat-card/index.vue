<template>
    <div class="admin-stat-card" :class="{ 'admin-stat-card--clickable': clickable }" @click="emit('click')">
        <div class="admin-stat-card__label">{{ label }}</div>
        <div class="admin-stat-card__value">{{ value }}</div>
        <div v-if="hint" class="admin-stat-card__hint" :class="hintClass">{{ hint }}</div>
    </div>
</template>

<script lang="ts" setup name="AdminStatCard">
/**
 * 指标卡：标签 + 数值 + 可选提示/环比。
 *
 * 工作台与财务概览各写了一份几乎一样的卡片样式（字号、圆角、留白都不同），
 * 收口到这里，数值字号固定 24px、标签 13px，保证一眼扫过去是同一套排版。
 */
withDefaults(
    defineProps<{
        label: string
        value: string | number
        /** 副提示，如「上期退款环比 12%」 */
        hint?: string
        /** 提示文字色调，如 text-danger / text-success */
        hintClass?: string
        clickable?: boolean
    }>(),
    { hint: '', hintClass: '', clickable: false }
)

const emit = defineEmits<{ (e: 'click'): void }>()
</script>

<style lang="scss" scoped>
.admin-stat-card {
    border: 1px solid var(--el-border-color-lighter);
    border-radius: 8px;
    padding: 16px 18px;
    background: var(--el-bg-color);

    &--clickable {
        cursor: pointer;
        transition: border-color 0.2s;

        &:hover {
            border-color: var(--el-color-primary);
        }
    }

    &__label {
        font-size: 13px;
        color: var(--el-text-color-secondary);
    }

    &__value {
        margin-top: 8px;
        font-size: 24px;
        font-weight: 600;
        line-height: 1.2;
        font-variant-numeric: tabular-nums;
    }

    &__hint {
        margin-top: 6px;
        font-size: 12px;
    }
}
</style>
