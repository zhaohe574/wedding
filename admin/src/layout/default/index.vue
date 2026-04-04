<template>
    <div class="layout-default h-screen w-full">
        <div class="layout-default__bg"></div>
        <div class="layout-default__aside">
            <layout-sidebar />
        </div>

        <div class="layout-default__content">
            <div class="layout-default__header">
                <layout-header />
            </div>
            <div class="layout-default__main">
                <layout-main />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import LayoutHeader from './components/header/index.vue'
import LayoutMain from './components/main.vue'
import LayoutSidebar from './components/sidebar/index.vue'
</script>

<style lang="scss" scoped>
.layout-default {
    position: relative;
    display: flex;
    overflow: hidden;
}

.layout-default__bg {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(1200px 500px at 20% -20%, rgba(31, 77, 122, 0.08), transparent 60%),
        radial-gradient(800px 500px at 120% 0%, rgba(45, 106, 79, 0.08), transparent 60%);
    pointer-events: none;
}

.layout-default__aside {
    position: relative;
    z-index: 2;
}

.layout-default__content {
    position: relative;
    z-index: 1;
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.layout-default__header {
    padding: 12px 12px 0;
}

.layout-default__main {
    flex: 1;
    min-height: 0;
    padding: 10px 12px 12px;
}

@media (max-width: 768px) {
    .layout-default__header {
        padding: 8px 8px 0;
    }

    .layout-default__main {
        padding: 8px;
    }
}
</style>
