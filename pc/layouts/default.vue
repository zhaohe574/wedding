<template>
    <section class="layout-default min-w-[1200px]">
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
.main-contain {
    min-height: calc(100vh - var(--header-height));
    @apply flex flex-col;
}
</style>
