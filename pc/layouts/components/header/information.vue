<template>
    <ClientOnly>
        <el-dropdown
            :max-height="200"
            :disabled="!hasData"
            popper-class="pc-dark-dropdown"
        >
            <span class="information-trigger">
                <MenuItem :menu-item="menuItem" :route-path="menuItem.path" />
                <Icon
                    v-if="hasData"
                    class="information-trigger__arrow"
                    name="el-icon-ArrowDown"
                    :size="12"
                />
            </span>
            <template #dropdown>
                <el-dropdown-menu>
                    <NuxtLink
                        :to="{
                            path: '/information/default',
                            query: {
                                cid: item.id,
                                name: item.name
                            }
                        }"
                        v-for="item in data"
                        :key="item.id"
                    >
                        <el-dropdown-item> {{ item.name }} </el-dropdown-item>
                    </NuxtLink>
                </el-dropdown-menu>
            </template>
        </el-dropdown>
    </ClientOnly>
</template>
<script lang="ts" setup>
import { ElDropdown, ElDropdownItem, ElDropdownMenu } from 'element-plus'
import { getArticleCate } from '~~/api/news'
import MenuItem from '../menu/menu-item.vue'
defineProps({
    menuItem: {
        type: Object,
        default: () => ({})
    }
})

const { data } = await useAsyncData(() => getArticleCate())
const hasData = computed(() => {
    return data.value && data.value.length
})
</script>

<style lang="scss" scoped>
.information-trigger {
    display: inline-flex;
    align-items: center;
    gap: 2px;
    color: var(--pc-ink-2);
    transition: color var(--dur-fast) var(--ease-out);

    &:hover {
        color: var(--pc-gold);
    }

    :deep(.el-menu-item) {
        padding-right: 0;
        background: transparent;
        color: inherit;
    }

    &__arrow {
        color: inherit;
    }
}
</style>
