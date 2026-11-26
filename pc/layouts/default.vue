<template>
    <section class="layout-default">
        <LayoutHeader />
        <div class="main-contain">
            <LayoutMain class="flex-1 min-h-0 flex">
                <slot />
            </LayoutMain>
            <LayoutFooter />
        </div>
    </section>
</template>
<script lang="ts" setup>
import LayoutHeader from './components/header/index.vue'
import LayoutMain from './components/main/index.vue'
import LayoutFooter from './components/footer/index.vue'
</script>
<style lang="scss" scoped>
.layout-default {
    --header-height: 72px;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    background: var(--pc-bg);
    color: var(--pc-ink);
}
.main-contain {
    @apply flex flex-col;
    flex: 1;
    min-height: calc(100vh - var(--header-height));
}
</style>
