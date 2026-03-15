<template>
    <section class="admin-page-shell" :class="{ 'admin-page-shell--compact': compact }">
        <header v-if="title || description || $slots.header || $slots.extra" class="admin-page-shell__head">
            <div class="admin-page-shell__head-main">
                <h1 v-if="title" class="admin-page-shell__title">{{ title }}</h1>
                <p v-if="description" class="admin-page-shell__description">{{ description }}</p>
                <slot name="header" />
            </div>
            <div v-if="$slots.extra" class="admin-page-shell__head-extra">
                <slot name="extra" />
            </div>
        </header>

        <section v-if="$slots.search" class="admin-page-shell__search">
            <slot name="search" />
        </section>

        <section v-if="$slots.stats" class="admin-page-shell__stats">
            <slot name="stats" />
        </section>

        <section class="admin-page-shell__body">
            <slot />
        </section>

        <footer v-if="$slots.footer" class="admin-page-shell__footer">
            <slot name="footer" />
        </footer>
    </section>
</template>

<script setup lang="ts">
defineProps<{
    title?: string
    description?: string
    compact?: boolean
}>()
</script>

<style lang="scss" scoped>
.admin-page-shell {
    display: flex;
    flex-direction: column;
    gap: 14px;

    &--compact {
        gap: 10px;
    }
}

.admin-page-shell__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 2px 4px;
}

.admin-page-shell__title {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    line-height: 1.35;
    color: var(--admin-color-title);
}

.admin-page-shell__description {
    margin: 6px 0 0;
    font-size: 13px;
    line-height: 1.6;
    color: var(--admin-color-muted);
}

.admin-page-shell__head-extra {
    display: flex;
    align-items: center;
    gap: 8px;
}

.admin-page-shell__search,
.admin-page-shell__stats,
.admin-page-shell__body {
    min-width: 0;
}

.admin-page-shell__footer {
    margin-top: 4px;
}
</style>
