<template>
    <nav class="navbar">
        <Menu
            class="navbar__menu"
            :menu="menu"
            :default-active="activeMenu"
            mode="horizontal"
        >
            <template #item="{ item }">
                <MenuItem
                    v-if="!item.component"
                    :menu-item="item"
                    :route-path="item.path"
                />
                <div v-else>
                    <template v-if="item.component == 'information'">
                        <Information :menu-item="item" />
                    </template>
                </div>
            </template>
        </Menu>
    </nav>
</template>
<script lang="ts" setup>
import Menu from '../menu/index.vue'
import MenuItem from '../menu/menu-item.vue'
import Information from './information.vue'
const route = useRoute()
const activeMenu = computed<string>(() => route.path)
const { menu } = useMenu()
</script>

<style lang="scss" scoped>
.navbar {
    --el-menu-item-font-size: var(--el-font-size-base);
    --el-menu-bg-color: transparent;
    --el-menu-active-color: var(--pc-gold);
    --el-menu-text-color: var(--pc-ink-2);
    --el-menu-hover-text-color: var(--pc-gold);
    --el-menu-hover-bg-color: transparent;
    --el-menu-item-hover-fill: transparent;
    --el-menu-border-color: transparent;

    :deep(.el-menu-item),
    :deep(.el-sub-menu__title) {
        background-color: transparent;
        color: var(--pc-ink-2);
        transition: color var(--dur-fast) var(--ease-out);

        &:hover {
            background-color: transparent;
            color: var(--pc-gold);
        }
    }

    :deep(.el-menu-item.is-active),
    :deep(.el-sub-menu.is-active > .el-sub-menu__title) {
        border-bottom-color: transparent;
        color: var(--pc-gold);
    }
}
</style>
