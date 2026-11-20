<template>
    <div class="decoration-pages min-w-[1100px]">
        <!-- 未接入小程序的页面必须显式提示，否则运营会以为「配了就上线了」 -->
        <el-alert
            v-if="activePageStatus === 'pending'"
            class="mb-2"
            type="warning"
            show-icon
            :closable="false"
            title="本页尚未在小程序端接入"
            description="可以编辑和保存，但保存后不会产生任何展示效果。小程序当前只渲染「首页装修」「个人中心」与「开屏广告页」。"
        />
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
/**
 * 移动端页面装修编辑器。
 *
 * 唯一事实源：`shared/contracts/decorate.ts` 的 `DECORATE_PAGES`。
 *   - 左侧有哪几页、每页叫什么 → 来自契约；
 *   - 每页允许哪些组件、默认顺序 → 来自契约；
 *   - 本文件不再维护第二份页面/组件清单（原来硬编码的 HOME/USER/SPLASH_WIDGET_NAMES 已删除）。
 *
 * 这样做的目的：后台能配的组件 === 小程序能渲染的组件。
 * 之前后台挂了 30+ 个组件，小程序只认 12 个，配了等于没配。
 */
import {
    DECORATE_PAGES,
    type DecoratePageDef,
    normalizeHomeBrandContent,
    normalizeHomeHeroContent,
    normalizeHomePopupContent,
    normalizeHomeServiceCategoriesContent,
    normalizeQuickEntryContent,
    normalizeWeddingCountdownContent
} from '@shared/contracts/decorate'

import { getDecoratePages, setDecoratePages } from '@/api/decoration'
import { getNonDuplicateID } from '@/utils/util'

import AttrSetting from '../component/pages/attr-setting.vue'
import Menu from '../component/pages/menu.vue'
import Preview from '../component/pages/preview.vue'
import widgets from '../component/widgets'

interface MenuState {
    id: number
    type: number
    name: string
    /** 页面标识（home / user / service / splash），替代原来的 pagesTypeEnum 数字字面量 */
    key: DecoratePageDef['key']
    /** 该页允许的组件，顺序即默认顺序 */
    widgets: string[]
    hasMeta: boolean
    /** live = 小程序已接入；pending = 配了也不生效 */
    status: 'live' | 'pending'
    pageMeta: any[] | null
    pageData: any[]
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

const parseJsonValue = <T,>(value: any, fallback: T): T => {
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

/**
 * 装修组件内容归一化 —— 与小程序、PHP 共用契约里的同一套规则。
 *
 * 老数据里的死字段（banner.height / home-brand.greeting / 分类.size / 旧倒计时样式 …）
 * 在后台加载时就被清掉，保存时 JSON.stringify 不会再把它们写回库里。
 *
 * ⚠ 不要给 home-flow 套 `normalizeHomeFlowContent()`：它是**渲染用**归一化，
 *   会把标题为空的步骤整行丢掉；编辑态必须保留这一行（运营可能正改到一半）。
 *   home-flow 的保存清洗交给 `HomeDecorateService`，它保留空标题行。
 */
const WIDGET_CONTENT_NORMALIZERS: Record<string, (raw: any) => any> = {
    banner: normalizeHomeHeroContent,
    'home-brand': normalizeHomeBrandContent,
    'home-service-categories': normalizeHomeServiceCategoriesContent,
    'home-popup-ad': normalizeHomePopupContent,
    'wedding-countdown': normalizeWeddingCountdownContent,
    'quick-entry': normalizeQuickEntryContent
}

const buildWidgetOptions = (widgetName: string, rawWidget: any = null) => {
    const defaultOptions = widgets[widgetName]?.options?.() || {}
    const widget = rawWidget || {}
    const mergedContent = {
        ...(defaultOptions.content || {}),
        ...(widget.content || {})
    }
    const normalize = WIDGET_CONTENT_NORMALIZERS[widgetName]
    // 旧组件名/旧 pageScope 不能跟着存量数据回显，统一以当前 options 为准
    const overrides: Record<string, { title?: string; pageScope?: string[] }> = {
        'wedding-countdown': { title: '婚期卡片', pageScope: ['user'] },
        'quick-entry': { pageScope: ['user'] }
    }

    return {
        id: widget?.id || getNonDuplicateID(),
        ...defaultOptions,
        ...widget,
        ...(overrides[widgetName] || {}),
        content: normalize ? normalize(mergedContent) : mergedContent,
        styles: {
            ...(defaultOptions.styles || {}),
            ...(widget.styles || {})
        }
    }
}

const generatePageData = (widgetNames: string[]) => {
    return widgetNames.map((widgetName) => buildWidgetOptions(widgetName))
}

/**
 * 加载回来的数据先按「该页允许的组件」裁剪，再补默认值。
 * 裁掉的是历史遗留组件（如小程序已不渲染的 my-service / service-packages）。
 *
 * ⚠ `keepWidgets` 是例外：个人中心的 my-service 要先留着，
 *   等 ensureUserFixedWidgets() 把它迁移成 quick-entry 之后再丢弃。
 *   在这里提前裁掉，老用户的快捷入口会在保存时被清空。
 */
const normalizeLoadedPageData = (rawData: any, allowedWidgets: string[], keepWidgets: string[] = []) => {
    const pageData = normalizePageWidgets(rawData)

    return pageData
        .filter((item: any) => item?.name !== 'service-packages')
        .filter(
            (item: any) =>
                !item?.name ||
                allowedWidgets.includes(item.name) ||
                keepWidgets.includes(item.name)
        )
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
    '/pages/user_set/user_set': 'settings'
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
    个人设置: 'settings'
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

const ensureUserFixedWidgets = (pageData: any[], widgetNames: string[]) => {
    const fixedWidgets = ensureFixedWidgets(pageData, widgetNames)
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

/** 按契约生成左侧菜单：页面结构完全由 DECORATE_PAGES 决定 */
const menus = reactive<Record<string, MenuState>>(
    DECORATE_PAGES.reduce(
        (acc, page) => {
            acc[String(page.id)] = {
                id: page.id,
                type: page.type,
                name: page.name,
                key: page.key,
                widgets: [...page.widgets],
                hasMeta: page.hasMeta,
                status: page.status,
                pageMeta: page.hasMeta ? generatePageData(['page-meta']) : null,
                pageData: generatePageData(page.widgets)
            }
            return acc
        },
        {} as Record<string, MenuState>
    )
)

const activeMenu = ref<string>(String(DECORATE_PAGES[0]?.id ?? 1))
const activePageStatus = computed<'live' | 'pending'>(
    () => menus[activeMenu.value]?.status ?? 'pending'
)
const selectWidgetIndex = ref<number>(-1)

/** 有 page-meta 的页面（首页 / 个人中心）默认选中第一个业务组件 */
const getActiveWidgetIndex = () => {
    if (selectWidgetIndex.value !== -1) {
        return selectWidgetIndex.value
    }

    if (menus[activeMenu.value]?.hasMeta) {
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
        return menus[activeMenu.value]?.pageMeta?.[0] ?? ''
    }

    return menus[activeMenu.value]?.pageData[activeWidgetIndex] ?? ''
})

/** 按页面 key 做「加载后补齐 / 保存前兜底」，不再用数字字面量判断 */
const applyPageRules = (menu: MenuState, pageData: any[]) => {
    if (menu.key === 'user') {
        return ensureUserFixedWidgets(pageData, menu.widgets)
    }

    const fixed = ensureFixedWidgets(pageData, menu.widgets)

    if (menu.key !== 'home') {
        return fixed
    }

    // 首页组件名以代码为准（首屏主图 / 数据条 / 四宫格入口…）。
    // buildWidgetOptions 是 {...defaultOptions, ...widget}，库里存的旧 title 会覆盖新名，
    // 这里再强制一次；开屏不参与（它的 title 由 SplashAdDecorateService 管）。
    return fixed.map((item: any) => {
        const title = widgets[item?.name]?.options?.()?.title
        return title && item?.title !== title ? { ...item, title } : item
    })
}

const getData = async () => {
    const menuKey = activeMenu.value
    const fallbackMenu = menus[menuKey]
    const data = await getDecoratePages({ id: menuKey })
    const targetMenu = menus[String(data?.id)] || fallbackMenu

    const rawData = data?.data ?? fallbackMenu.pageData
    // 个人中心要先保留 my-service，交给 applyPageRules 迁移成 quick-entry
    const legacyKeeps = targetMenu.key === 'user' ? ['my-service'] : []
    const pageData = applyPageRules(
        targetMenu,
        normalizeLoadedPageData(rawData, targetMenu.widgets, legacyKeeps)
    )

    targetMenu.pageData = pageData
    targetMenu.pageMeta = targetMenu.hasMeta
        ? parseJsonValue<any[] | null>(data?.meta, targetMenu.pageMeta ?? null)
        : null
    selectWidgetIndex.value = pageData.findIndex((item: any) => !item?.disabled)
}

const setData = async () => {
    const data = menus[activeMenu.value]
    const pageData = applyPageRules(data, data.pageData)

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
