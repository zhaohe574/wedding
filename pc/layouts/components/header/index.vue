<template>
    <header class="layout-header">
        <div class="header-contain">
            <Logo class="header-logo" />
            <Navbar class="header-nav" />
            <Search class="header-search" />
        </div>
    </header>
</template>
<script lang="ts" setup>
import Search from './search.vue'
import Logo from './logo.vue'
import Navbar from './navbar.vue'
</script>

<style lang="scss" scoped>
.layout-header {
    position: sticky;
    top: 0;
    z-index: 20;
    width: 100%;
    height: var(--header-height, 72px);
    background: rgba(17, 14, 12, 0.94);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--pc-line-strong);

    .header-contain {
        max-width: 1120px;
        width: 100%;
        height: 100%;
        margin: 0 auto;
        padding: 0 24px;
        box-sizing: border-box;
        display: flex;
        align-items: center;
        gap: 24px;
    }

    .header-logo {
        flex: none;
    }

    .header-nav {
        flex: 1;
        min-width: 0;
    }

    .header-search {
        flex: none;
    }
}

@media (max-width: 560px) {
    .layout-header {
        .header-contain {
            gap: 16px;
            padding: 0 16px;
        }

        .header-logo :deep(.header-brand__name) {
            display: none;
        }
    }
}
</style>
