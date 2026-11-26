<template>
    <ElMenu class="menu" v-bind="$props" :ellipsis="true">
        <div v-for="item in menu" :key="item.path">
            <slot name="item" :item="item">
                <MenuItem :menu-item="item" :route-path="item.path" />
            </slot>
        </div>
    </ElMenu>
</template>

<script lang="ts" setup>
import { ElMenu, menuProps } from 'element-plus'
import { PropType } from 'vue'
import MenuItem from './menu-item.vue'
defineProps({
    menu: {
        type: Array as PropType<any[]>,
        default: () => []
    },
    ...menuProps
})
</script>

<style lang="scss" scoped>
.menu {
    &.el-menu--horizontal {
        --el-menu-item-height: 40px;
        background: transparent;
        border-bottom: none;

        :deep(.el-menu-item),
        :deep(.el-sub-menu__title) {
            background: transparent;

            span {
                padding-bottom: 4px;
                border-bottom: 2px solid transparent;
                transition: border-color var(--dur-fast) var(--ease-out);
            }

            &.is-active > span {
                border-color: var(--pc-gold);
            }
        }
    }
    &.el-menu--vertical:not(.el-menu--collapse) {
        width: 200px;
    }
}
</style>
