<template>
    <section class="search-panel">
        <div class="search-panel__main">
            <slot />
        </div>
        <aside v-if="$slots.extra" class="search-panel__extra">
            <slot name="extra" />
        </aside>
    </section>
</template>

<script setup lang="ts"></script>

<style lang="scss" scoped>
.search-panel {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 16px;
    border: 1px solid var(--admin-color-border);
    border-radius: var(--admin-radius-md);
    background: var(--admin-surface-soft);
}

.search-panel__main {
    flex: 1;
    min-width: 0;
}

.search-panel__extra {
    flex: none;
}

@media (max-width: 960px) {
    .search-panel {
        flex-direction: column;
    }

    .search-panel__extra {
        width: 100%;
    }
}
</style>
