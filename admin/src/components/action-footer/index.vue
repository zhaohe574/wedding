<template>
    <div class="action-footer" :class="{ 'action-footer--fixed': fixed }">
        <div class="action-footer__inner">
            <slot />
        </div>
    </div>
</template>

<script setup lang="ts">
withDefaults(
    defineProps<{
        fixed?: boolean
    }>(),
    {
        fixed: false
    }
)
</script>

<style lang="scss" scoped>
.action-footer {
    width: 100%;
}

.action-footer--fixed {
    position: sticky;
    bottom: 0;
    z-index: 15;
}

.action-footer__inner {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    padding: 10px 14px;
    border: 1px solid var(--admin-color-border);
    border-radius: var(--admin-radius-md);
    background: color-mix(in srgb, var(--admin-surface-card) 90%, #ffffff 10%);
    box-shadow: var(--admin-shadow-soft);
}
</style>
