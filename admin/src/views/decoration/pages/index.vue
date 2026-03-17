<template>
    <div class="decoration-pages min-w-[1100px]">
        <div class="flex flex-1 h-full justify-between">
            <el-card
                shadow="never"
                class="!border-none flex scroll-view-content"
                :body-style="{ 'padding-right': 0 }"
            >
                <Menu v-model="activeMenu" :menus="menus" />
            </el-card>

            <preview
                class="flex-1 scroll-view-content"
                v-model="selectWidgetIndex"
                :activeMenu="activeMenu"
                @updatePageData="updatePageData"
                :pageData="getPageData"
                :pageMeta="getPageMeta"
            />

            <attr-setting
                class="w-[560px] scroll-view-content"
                :widget="getSelectWidget"
                @update:content="updateContent"
            />
        </div>
        <footer-btns class="mt-2" :fixed="false" v-perms="['decorate:pages:save']">
            <el-button type="primary" @click="setData">保存</el-button>
        </footer-btns>
    </div>
</template>
<script lang="ts" setup name="decorationPages">
import { getDecoratePages, setDecoratePages } from '@/api/decoration'
import { getNonDuplicateID } from '@/utils/util'

import AttrSetting from '../component/pages/attr-setting.vue'
import Menu from '../component/pages/menu.vue'
import Preview from '../component/pages/preview.vue'
import widgets from '../component/widgets'

enum pagesTypeEnum {
    HOME = '1',
    USER = '2',
    SERVICE = '3'
}

const updatePageData = (value: any) => {
    menus[activeMenu.value].pageData = [...value]
}

const isNumericKeyObject = (value: any) => {
    if (!value || Array.isArray(value) || typeof value !== 'object') {
        return false
    }

    const keys = Object.keys(value)
    if (!keys.length) {
        return false
    }

    return keys.every((key) => /^\d+$/.test(key))
}

const normalizeListLikeValue = (value: any) => {
    if (Array.isArray(value)) {
        return value
    }

    if (isNumericKeyObject(value)) {
        return Object.values(value)
    }

    return value
}

const normalizePageWidgets = (rawData: any) => {
    const parsedData = typeof rawData === 'string' ? JSON.parse(rawData) : rawData
    const normalizedData = normalizeListLikeValue(parsedData)
    return Array.isArray(normalizedData) ? normalizedData : []
}

const generatePageData = (widgetNames: string[]) => {
    return widgetNames.map((widgetName) => {
        const options = {
            id: getNonDuplicateID(),
            ...(widgets[widgetName]?.options() || {})
        }
        return options
    })
}

const menus: Record<
    string,
    {
        id: number
        name: string
        pageMeta?: any
        pageData: any[]
    }
> = reactive({
    [pagesTypeEnum.HOME]: {
        id: 1,
        type: 1,
        name: '首页装修',
        pageMeta: generatePageData(['page-meta']),
        pageData: generatePageData([
            'search',
            'banner', 
            'nav',
            'middle-banner',
            'staff-showcase',
            'portfolio-gallery',
            'customer-reviews',
            'activity-zone',
            'order-quick-entry',
            'news'
        ])
    },
    [pagesTypeEnum.USER]: {
        id: 2,
        type: 2,
        name: '个人中心',
        pageMeta: generatePageData(['page-meta']),
        pageData: generatePageData(['user-info', 'my-service', 'user-banner'])
    },
    [pagesTypeEnum.SERVICE]: {
        id: 3,
        type: 3,
        name: '客服设置',
        pageMeta: null,
        pageData: generatePageData(['customer-service'])
    }
})

const activeMenu = ref<string>('1')
const selectWidgetIndex = ref<number>(-1)
const lockPageMetaMenus = new Set<string>([pagesTypeEnum.HOME, pagesTypeEnum.USER])
const getActiveWidgetIndex = () => {
    if (selectWidgetIndex.value !== -1) {
        return selectWidgetIndex.value
    }

    if (lockPageMetaMenus.has(activeMenu.value)) {
        return menus[activeMenu.value]?.pageData?.findIndex((item: any) => !item?.disabled) ?? -1
    }

    return -1
}
const updateContent = (content: any) => {
    const activeWidgetIndex = getActiveWidgetIndex()
    if (activeWidgetIndex < 0) {
        return
    }

    if (menus[activeMenu.value]?.pageData?.[activeWidgetIndex]) {
        menus[activeMenu.value].pageData[activeWidgetIndex].content = content
    }
}
const getPageData = computed(() => {
    return menus[activeMenu.value]?.pageData ?? []
})
const getPageMeta = computed(() => {
    return menus[activeMenu.value]?.pageMeta ?? null
})
const getSelectWidget = computed(() => {
    const activeWidgetIndex = getActiveWidgetIndex()
    if (activeWidgetIndex === -1) {
        return menus[activeMenu.value]?.pageMeta[0] ?? ''
    }

    return menus[activeMenu.value]?.pageData[activeWidgetIndex] ?? ''
})

const getData = async () => {
    const data = await getDecoratePages({ id: activeMenu.value })
    const pageData = normalizePageWidgets(data.data)
    // 兼容旧数据：移除废弃的disabled字段，确保content.enabled存在，补全默认字段
    pageData.forEach((item: any) => {
        if (item?.name === 'service-packages') {
            return
        }
        if (item?.content && 'data' in item.content) {
            item.content.data = normalizeListLikeValue(item.content.data)
        }
        if ('disabled' in item && item.name !== 'user-info') {
            delete item.disabled
        }
        if (item.content && !('enabled' in item.content)) {
            item.content.enabled = 1
        }
        // 将 options 默认值与后端数据合并，确保新增字段不丢失
        const defaultOptions = widgets[item.name]?.options()
        if (defaultOptions?.content) {
            item.content = { ...defaultOptions.content, ...item.content }
        }
        if (defaultOptions?.styles) {
            item.styles = { ...defaultOptions.styles, ...(item.styles || {}) }
        }
    })
    menus[String(data.id)].pageData = pageData.filter((item: any) => item?.name !== 'service-packages')
    menus[String(data.id)].pageMeta = data?.meta ? JSON.parse(data?.meta) : null
}

const setData = async () => {
    const data = menus[activeMenu.value]
    await setDecoratePages({
        ...data,
        data: JSON.stringify(data.pageData),
        meta: data?.pageMeta ? JSON.stringify(data?.pageMeta) : null
    })
    getData()
}
watch(
    activeMenu,
    () => {
        selectWidgetIndex.value = getPageData.value.findIndex((item) => !item.disabled)
        getData()
    },
    {
        immediate: true
    }
)
</script>
<style lang="scss" scoped>
$scroll-height: calc(100vh - var(--navbar-height) - 126px);
.decoration-pages {
    height: $scroll-height;
    @apply flex flex-col;
    .scroll-view-content {
        height: calc($scroll-height - 18px);
    }
}
</style>
