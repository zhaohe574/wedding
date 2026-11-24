<template>
    <div class="link flex">
        <el-menu
            :default-active="activeMenu"
            class="flex-none w-[180px] min-h-[380px] link-menu"
            :default-openeds="[
                MenuTypeEnum.SHOP_PAGES,
                MenuTypeEnum.BUSINESS_CONTENT,
                MenuTypeEnum.ACTION_TOOL,
                MenuTypeEnum.OTHER_LINK
            ]"
            @select="handleSelect"
        >
            <el-sub-menu v-for="item in visibleMenus" :key="item.type" :index="item.type">
                <template #title>
                    <span>{{ item.name }}</span>
                </template>
                <el-menu-item
                    v-for="sitem in item.children"
                    :key="sitem.type"
                    :index="sitem.type"
                    style="min-width: 160px"
                >
                    <span>{{ sitem.name }}</span>
                </el-menu-item>
            </el-sub-menu>
        </el-menu>
        <div class="flex-1 min-w-0 ml-4 link-content">
            <shop-pages
                v-if="LinkTypeEnum.SHOP_PAGES == activeMenu"
                v-model="activeLink"
                :is-tab="props.isTab"
            />
            <staff-list v-if="LinkTypeEnum.STAFF_DETAIL == activeMenu" v-model="activeLink" />
            <dynamic-list v-if="LinkTypeEnum.DYNAMIC_LIST == activeMenu" v-model="activeLink" />
            <action-link v-if="LinkTypeEnum.ACTION == activeMenu" v-model="activeLink" />
            <custom-link v-if="LinkTypeEnum.CUSTOM_LINK == activeMenu" v-model="activeLink" />
            <mini-program v-if="LinkTypeEnum.MINI_PROGRAM == activeMenu" v-model="activeLink" />
        </div>
    </div>
</template>

<script lang="ts" setup>
import { computed, type PropType, reactive, ref, watch } from 'vue'

import { type Link, LinkTypeEnum, MenuTypeEnum } from '.'
import ActionLink from './action-link.vue'
import CustomLink from './custom-link.vue'
import DynamicList from './dynamic-list.vue'
import MiniProgram from './mini-program.vue'
import ShopPages from './shop-pages.vue'
import StaffList from './staff-list.vue'

const props = defineProps({
    modelValue: {
        type: Object as PropType<Link>,
        required: true
    },
    isTab: {
        type: Boolean,
        default: false
    }
})
const emit = defineEmits<{
    (event: 'update:modelValue', value: Link): void
}>()

const tabLinks = reactive<Record<string, Link>>({
    [LinkTypeEnum.SHOP_PAGES]: { path: '' },
    [LinkTypeEnum.STAFF_DETAIL]: { path: '' },
    [LinkTypeEnum.DYNAMIC_LIST]: { path: '' },
    [LinkTypeEnum.ACTION]: { path: '' },
    [LinkTypeEnum.CUSTOM_LINK]: { path: '' },
    [LinkTypeEnum.MINI_PROGRAM]: { path: '' }
})

const menus = [
    {
        name: '移动端页面',
        type: MenuTypeEnum.SHOP_PAGES,
        children: [{ name: '页面选择', type: LinkTypeEnum.SHOP_PAGES }]
    },
    {
        name: '业务内容',
        type: MenuTypeEnum.BUSINESS_CONTENT,
        children: [
            { name: '服务人员详情', type: LinkTypeEnum.STAFF_DETAIL },
            { name: '动态详情', type: LinkTypeEnum.DYNAMIC_LIST }
        ]
    },
    {
        name: '快捷动作',
        type: MenuTypeEnum.ACTION_TOOL,
        children: [{ name: '动作触发器', type: LinkTypeEnum.ACTION }]
    },
    {
        name: '外部跳转',
        type: MenuTypeEnum.OTHER_LINK,
        children: [
            { name: '外部网页', type: LinkTypeEnum.CUSTOM_LINK },
            { name: '跳转小程序', type: LinkTypeEnum.MINI_PROGRAM }
        ]
    }
]

const visibleMenus = computed(() => {
    if (props.isTab) {
        return menus.filter((item) => item.type === MenuTypeEnum.SHOP_PAGES)
    }
    return menus
})

const VALID_MENUS = new Set<string>(Object.values(LinkTypeEnum))
const activeMenu = ref<string>(LinkTypeEnum.SHOP_PAGES)

const activeLink = computed<Link>({
    get() {
        return tabLinks[activeMenu.value] || { path: '' }
    },
    set(value) {
        tabLinks[activeMenu.value] = value || { path: '' }
        if (value && (value.type || value.path)) {
            emit('update:modelValue', value)
        }
    }
})

const handleSelect = (index: string) => {
    activeMenu.value = index
    const stored = tabLinks[index]
    if (stored && (stored.type || stored.path)) {
        emit('update:modelValue', stored)
    }
}

watch(
    () => props.modelValue,
    (value) => {
        const targetType = props.isTab
            ? LinkTypeEnum.SHOP_PAGES
            : value?.type && VALID_MENUS.has(String(value.type))
              ? String(value.type)
              : LinkTypeEnum.SHOP_PAGES
        activeMenu.value = targetType
        if (value && typeof value === 'object') {
            tabLinks[targetType] = value
        }
    },
    { immediate: true }
)
</script>

<style lang="scss" scoped>
.link {
    .link-menu {
        --el-menu-item-height: 40px;
        border-radius: 8px;
        border: 1px solid var(--el-border-color);

        :deep(.el-menu-item) {
            border-color: transparent;

            &.is-active {
                border-right-width: 2px;
                border-color: var(--el-color-primary);
                background-color: var(--el-color-primary-light-9);
            }
        }
    }

    .link-content {
        padding: 18px 20px;
        box-sizing: border-box;
        border-radius: 8px;
        border: 1px solid var(--el-border-color);
    }
}
</style>
