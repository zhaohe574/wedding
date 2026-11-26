<template>
    <main class="layout-main">
        <div v-if="sidebar.length" class="layout-main__aside">
            <Menu
                :menu="sidebar"
                :default-active="activeMenu"
                mode="vertical"
            />
        </div>
        <div
            class="layout-main__page"
            :class="{ 'layout-main__page--card': hasSidebar }"
        >
            <slot />
        </div>
    </main>
</template>
<script lang="ts" setup>
import Menu from '../menu/index.vue'
const route = useRoute()
const activeMenu = computed<string>(() => route.meta.activeMenu ?? route.path)
const { sidebar, hasSidebar } = useMenu()
</script>

<style lang="scss" scoped>
.layout-main {
    max-width: 1120px;
    width: 100%;
    margin: 0 auto;
    padding: 28px 24px 48px;
    box-sizing: border-box;
    display: flex;
    align-items: flex-start;
    gap: 16px;

    &__aside {
        flex: none;
        overflow: hidden;
        border: 1px solid var(--pc-line);
        border-radius: 14px;
        background: var(--pc-surface);
    }

    &__page {
        flex: 1;
        min-width: 0;
    }

    &__page--card {
        padding: 24px;
        border: 1px solid var(--pc-line);
        border-radius: 14px;
        background: var(--pc-surface);
        box-sizing: border-box;
    }
}
</style>
