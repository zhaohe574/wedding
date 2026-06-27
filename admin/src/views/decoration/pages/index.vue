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
        <footer-btns class="mt-2" :fixed="false" v-perms="['decorate.page/save']">
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
    SERVICE = '3',
    SPLASH = '6'
}

const HOME_WIDGET_NAMES = [
    'banner',
    'home-brand',
    'home-feature-carousel',
    'home-service-categories',
    'home-popup-ad'
]
const USER_WIDGET_NAMES = ['user-info', 'wedding-countdown', 'quick-entry']
const SPLASH_WIDGET_NAMES = ['splash-ad']

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

const parseJsonValue = <T>(value: any, fallback: T): T => {
    if (value === null || value === undefined || value === '') {
        return fallback
    }

    if (typeof value === 'string') {
        try {
            return JSON.parse(value) as T
        } catch (error) {
            console.error('装修数据解析失败', error)
            return fallback
        }
    }

    return value as T
}

const buildWidgetOptions = (widgetName: string, rawWidget: any = null) => {
    const defaultOptions = widgets[widgetName]?.options?.() || {}
    const widget = rawWidget || {}

    return {
        id: widget?.id || getNonDuplicateID(),
        ...defaultOptions,
        ...widget,
        content: {
            ...(defaultOptions.content || {}),
            ...(widget.content || {})
        },
        styles: {
            ...(defaultOptions.styles || {}),
            ...(widget.styles || {})
        }
    }
}

const generatePageData = (widgetNames: string[]) => {
    return widgetNames.map((widgetName) => buildWidgetOptions(widgetName))
}

const normalizeLoadedPageData = (rawData: any) => {
    const pageData = normalizePageWidgets(rawData)

    return pageData
        .filter((item: any) => item?.name !== 'service-packages')
        .map((item: any) => {
            if (!item || typeof item !== 'object') {
                return item
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

            return buildWidgetOptions(item.name, item)
        })
}

const ensureFixedWidgets = (pageData: any[], widgetNames: string[]) => {
    const widgetMap = new Map<string, any>()
    pageData.forEach((item: any) => {
        const widgetName = item?.name
        if (!widgetNames.includes(widgetName) || widgetMap.has(widgetName)) {
            return
        }

        widgetMap.set(widgetName, item)
    })

    return widgetNames.map((widgetName) => buildWidgetOptions(widgetName, widgetMap.get(widgetName)))
}

const ensureHomeFixedWidgets = (pageData: any[]) => ensureFixedWidgets(pageData, HOME_WIDGET_NAMES)

const USER_ENTRY_KEY_BY_PATH: Record<string, string> = {
    '/pages/order/order': 'order',
    '/pages/order/list': 'order',
    '/packages/pages/review/list': 'review',
    '/pages/review/list': 'review',
    '/packages/pages/my_activity/my_activity': 'activity',
    '/pages/my_activity/my_activity': 'activity',
    '/packages/pages/activity_registration/list': 'activity',
    '/pages/activity_registration/list': 'activity',
    '/packages/pages/notification/index': 'notification',
    '/pages/notification/index': 'notification',
    '/packages/pages/staff_favorite/staff_favorite': 'favorite',
    '/packages/pages/collection/collection': 'favorite',
    '/pages/collection/collection': 'favorite',
    '/packages/pages/aftersale/index': 'aftersale',
    '/pages/aftersale/index': 'aftersale',
    '/packages/pages/waitlist/waitlist': 'waitlist',
    '/pages/user_set/user_set': 'settings',
    '/packages/pages/user_wallet/user_wallet': 'wallet',
    '/pages/user_wallet/user_wallet': 'wallet'
}

const USER_ENTRY_KEY_BY_TITLE: Record<string, string> = {
    我的订单: 'order',
    我的活动: 'activity',
    我的活动报名: 'activity',
    活动报名: 'activity',
    我的评价: 'review',
    通知中心: 'notification',
    我的收藏: 'favorite',
    售后服务: 'aftersale',
    我的候补: 'waitlist',
    设置: 'settings',
    个人设置: 'settings',
    我的钱包: 'wallet'
}

const normalizeLinkPath = (link: any) => {
    const rawPath = typeof link === 'string' ? link : String(link?.path || '')
    const path = rawPath.split('?')[0].trim()
    if (!path) {
        return ''
    }

    return path.startsWith('/') ? path : `/${path}`
}

const inferUserEntryKey = (item: any) => {
    const key = String(item?.key || '').trim()
    if (key) {
        return key
    }

    const linkPath = normalizeLinkPath(item?.link)
    if (linkPath && USER_ENTRY_KEY_BY_PATH[linkPath]) {
        return USER_ENTRY_KEY_BY_PATH[linkPath]
    }

    const title = String(item?.title || item?.name || '').trim()
    return USER_ENTRY_KEY_BY_TITLE[title] || ''
}

const normalizeUserEntryItem = (item: any) => {
    const title = String(item?.title || item?.name || '').trim()
    const link = item?.link || {}
    if (!title || !normalizeLinkPath(link)) {
        return null
    }

    return {
        key: inferUserEntryKey(item),
        icon: item?.icon || item?.image || '',
        title,
        subtitle: item?.subtitle || '',
        link,
        is_show: String(item?.is_show ?? '1'),
        requiresLogin: item?.requiresLogin ?? true,
        sort: item?.sort || 0
    }
}

const migrateLegacyMyServiceEntries = (pageData: any[]) => {
    const legacyWidget = pageData.find((item: any) => item?.name === 'my-service')
    const legacyList = normalizeListLikeValue(legacyWidget?.content?.data || [])
    return legacyList.map(normalizeUserEntryItem).filter(Boolean)
}

const ensureUserFixedWidgets = (pageData: any[]) => {
    const fixedWidgets = ensureFixedWidgets(pageData, USER_WIDGET_NAMES)
    const quickEntryWidget = fixedWidgets.find((item: any) => item?.name === 'quick-entry')
    const quickEntryList = normalizeListLikeValue(quickEntryWidget?.content?.data || [])

    if (quickEntryWidget && !quickEntryList.length) {
        const legacyEntries = migrateLegacyMyServiceEntries(pageData)
        if (legacyEntries.length) {
            quickEntryWidget.content = {
                ...(quickEntryWidget.content || {}),
                style: 3,
                data: legacyEntries
            }
        }
    }

    return fixedWidgets
}

const ensureSplashFixedWidget = (pageData: any[]) => ensureFixedWidgets(pageData, SPLASH_WIDGET_NAMES)


const menus: Record<
    string,
    {
        id: number
        name: string
        type?: number
        pageMeta?: any
        pageData: any[]
    }
> = reactive({
    [pagesTypeEnum.HOME]: {
        id: 1,
        type: 1,
        name: '首页装修',
        pageMeta: generatePageData(['page-meta']),
        pageData: generatePageData(HOME_WIDGET_NAMES)
    },
    [pagesTypeEnum.USER]: {
        id: 2,
        type: 2,
        name: '个人中心',
        pageMeta: generatePageData(['page-meta']),
        pageData: generatePageData(USER_WIDGET_NAMES)
    },
    [pagesTypeEnum.SERVICE]: {
        id: 3,
        type: 3,
        name: '客服设置',
        pageMeta: null,
        pageData: generatePageData(['customer-service'])
    },
    [pagesTypeEnum.SPLASH]: {
        id: 6,
        type: 6,
        name: '开屏广告页',
        pageMeta: null,
        pageData: generatePageData(SPLASH_WIDGET_NAMES)
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
    const fallbackMenu = menus[activeMenu.value]
    const data = await getDecoratePages({ id: activeMenu.value })
    const targetMenu = menus[String(data?.id)] || fallbackMenu
    let pageData = normalizeLoadedPageData(data?.data ?? fallbackMenu.pageData)

    if (activeMenu.value === pagesTypeEnum.HOME) {
        pageData = ensureHomeFixedWidgets(pageData)
    }

    if (activeMenu.value === pagesTypeEnum.USER) {
        pageData = ensureUserFixedWidgets(pageData)
    }

    if (activeMenu.value === pagesTypeEnum.SPLASH) {
        pageData = ensureSplashFixedWidget(pageData)
    }

    targetMenu.pageData = pageData
    targetMenu.pageMeta = parseJsonValue<any[] | null>(data?.meta, targetMenu.pageMeta ?? null)
    selectWidgetIndex.value = pageData.findIndex((item: any) => !item?.disabled)
}

const setData = async () => {
    const data = menus[activeMenu.value]
    const pageData =
        activeMenu.value === pagesTypeEnum.HOME
            ? ensureHomeFixedWidgets(data.pageData)
            : activeMenu.value === pagesTypeEnum.USER
              ? ensureUserFixedWidgets(data.pageData)
            : activeMenu.value === pagesTypeEnum.SPLASH
              ? ensureSplashFixedWidget(data.pageData)
              : data.pageData

    await setDecoratePages({
        ...data,
        data: JSON.stringify(pageData),
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
