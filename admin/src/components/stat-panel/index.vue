<template>
    <section class="stat-panel" :style="{ '--stat-columns': `${columns}` }">
        <article class="stat-panel__item" v-for="item in items" :key="item.label">
            <div class="stat-panel__meta">
                <p class="stat-panel__label">{{ item.label }}</p>
                <p class="stat-panel__value" :class="item.accent ? `is-${item.accent}` : ''">
                    {{ item.value }}<span v-if="item.suffix">{{ item.suffix }}</span>
                </p>
                <p v-if="item.description" class="stat-panel__desc">{{ item.description }}</p>
            </div>
            <div v-if="item.icon" class="stat-panel__icon">{{ item.icon }}</div>
        </article>
    </section>
</template>

<script setup lang="ts">
type Accent = 'primary' | 'success' | 'warning' | 'danger' | 'muted'

interface StatItem {
    label: string
    value: string | number
    suffix?: string
    description?: string
    accent?: Accent
    icon?: string
}

withDefaults(
    defineProps<{
        items: StatItem[]
        columns?: number
    }>(),
    {
        columns: 4
    }
)
</script>

<style lang="scss" scoped>
.stat-panel {
    display: grid;
    gap: 12px;
    grid-template-columns: repeat(var(--stat-columns), minmax(0, 1fr));
}

.stat-panel__item {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 8px;
    padding: 14px 16px;
    border: 1px solid var(--admin-color-border);
    border-radius: var(--admin-radius-md);
    background: var(--admin-surface-card);
    box-shadow: var(--admin-shadow-soft);
}

.stat-panel__label {
    margin: 0;
    font-size: 12px;
    line-height: 1.4;
    color: var(--admin-color-muted);
}

.stat-panel__value {
    margin: 6px 0 0;
    font-size: 24px;
    line-height: 1.2;
    font-weight: 600;
    color: var(--admin-color-title);
}

.stat-panel__value.is-primary {
    color: var(--el-color-primary);
}

.stat-panel__value.is-success {
    color: var(--el-color-success);
}

.stat-panel__value.is-warning {
    color: var(--el-color-warning);
}

.stat-panel__value.is-danger {
    color: var(--el-color-danger);
}

.stat-panel__value.is-muted {
    color: var(--admin-color-muted);
}

.stat-panel__desc {
    margin: 8px 0 0;
    font-size: 12px;
    line-height: 1.5;
    color: var(--admin-color-muted);
}

.stat-panel__icon {
    flex: none;
    font-size: 22px;
    line-height: 1;
    color: var(--admin-color-muted);
}

@media (max-width: 1280px) {
    .stat-panel {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .stat-panel {
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }

    .stat-panel__value {
        font-size: 20px;
    }
}
</style>
