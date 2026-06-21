<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" tone="workspace" hasTabbar suppress-overlay>
        <view class="user-page">
            <MpPageHeader
                title="个人中心"
                title-align="left"
                title-size="large"
                surface="dark"
                fixed
            />
            <view class="user-page__body">
                <view class="user-page__fixed-skeleton" data-qa="user-fixed-skeleton">
                    <w-user-info
                        v-if="isComponentEnabled(userInfoWidget)"
                        :content="mergeUserInfoContent(userInfoWidget.content || {})"
                        :styles="userInfoWidget.styles"
                        :user="userInfo"
                        :isLogin="isLogin"
                        :show-header="false"
                    />

                    <w-quick-entry
                        v-if="showRoleEntryWidget"
                        :content="mergeRoleEntryContent(quickEntryWidget.content || {})"
                        :styles="quickEntryWidget.styles"
                        :is-login="isLogin"
                    />
                </view>

                <view class="user-page__widget-zone" data-qa="user-widget-zone">
                    <w-wedding-countdown
                        v-if="isComponentEnabled(weddingCountdownWidget)"
                        :content="
                            mergeWeddingCountdownContent(weddingCountdownWidget.content || {})
                        "
                        :styles="weddingCountdownWidget.styles"
                        :is-login="isLogin"
                        :wedding-info="weddingInfo"
                    />

                    <w-quick-entry
                        v-if="isComponentEnabled(quickEntryWidget)"
                        :content="mergeQuickEntryContent(quickEntryWidget.content || {})"
                        :styles="quickEntryWidget.styles"
                        :is-login="isLogin"
                    />
                </view>
            </view>
            <tabbar :badge-refresh-key="badgeRefreshKey" />
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { getDecorate } from '@/api/shop'
import { getOrderStatistics } from '@/api/order'
import { getUserWeddingDate } from '@/api/user'
import MpPageHeader from '@/components/base/MpPageHeader.vue'
import PageShell from '@/components/base/PageShell.vue'
import { appendPageContractQuery, getRoleEntryStates } from '@/utils/page-contract'
import { getLinkPath, hasConfiguredLink } from '@/utils/util'
import { useAppStore } from '@/stores/app'
import { useThemeStore } from '@/stores/theme'
import { useUserStore } from '@/stores/user'
import { loadUserBadgeData } from '@/utils/user-badge'
import { onShow } from '@dcloudio/uni-app'
import { storeToRefs } from 'pinia'
import { computed, reactive, ref } from 'vue'

type DecorateWidget = {
    name: string
    content?: Record<string, any>
    styles?: Record<string, any>
}

type QuickEntryItem = {
    key?: string
    title: string
    subtitle?: string
    is_show: string
    disabled: boolean
    requiresLogin?: boolean
    link: Record<string, any> | string
}

const $theme = useThemeStore()
const appStore = useAppStore()
const userStore = useUserStore()
const { userInfo, isLogin } = storeToRefs(userStore)

const state = reactive<{ pages: DecorateWidget[] }>({
    pages: []
})
const badgeRefreshKey = ref(0)
const orderStats = ref<Record<string, number>>({})
const unreadMessageCount = ref(0)
const weddingInfo = ref<Record<string, any>>({})

const USER_WIDGET_ORDER = ['user-info', 'wedding-countdown', 'quick-entry']
const featureSwitch = computed(() => appStore.config?.feature_switch || {})
const DEFAULT_QUICK_ENTRY_ITEMS: QuickEntryItem[] = [
    {
        key: 'order',
        title: '我的订单',
        subtitle: '进行中订单',
        is_show: '1',
        disabled: false,
        requiresLogin: true,
        link: { path: '/pages/order/order', type: 'shop' }
    },
    {
        key: 'activity',
        title: '我的活动',
        subtitle: '报名进度',
        is_show: '1',
        disabled: false,
        requiresLogin: true,
        link: { path: '/packages/pages/my_activity/my_activity', type: 'shop' }
    },
    {
        key: 'review',
        title: '我的评价',
        subtitle: '评价记录',
        is_show: '1',
        disabled: false,
        requiresLogin: true,
        link: { path: '/packages/pages/review/list', type: 'shop' }
    },
    {
        key: 'notification',
        title: '通知中心',
        subtitle: '消息更新',
        is_show: '1',
        disabled: false,
        requiresLogin: true,
        link: { path: '/packages/pages/notification/index', type: 'shop' }
    },
    {
        key: 'favorite',
        title: '我的收藏',
        subtitle: '已收藏',
        is_show: '1',
        disabled: false,
        requiresLogin: true,
        link: { path: '/packages/pages/staff_favorite/staff_favorite', type: 'shop' }
    },
    {
        key: 'aftersale',
        title: '售后服务',
        subtitle: '售后进度',
        is_show: '1',
        disabled: false,
        requiresLogin: true,
        link: { path: '/packages/pages/aftersale/index', type: 'shop' }
    },
    {
        key: 'waitlist',
        title: '我的候补',
        subtitle: '候补进度',
        is_show: '1',
        disabled: false,
        requiresLogin: true,
        link: { path: '/packages/pages/waitlist/waitlist', type: 'shop' }
    },
    {
        key: 'settings',
        title: '设置',
        subtitle: '账号设置',
        is_show: '1',
        disabled: false,
        requiresLogin: true,
        link: { path: '/pages/user_set/user_set', type: 'shop' }
    }
]
const QUICK_ENTRY_KEY_BY_PATH: Record<string, string> = {
    '/pages/order/order': 'order',
    '/packages/pages/my_activity/my_activity': 'activity',
    '/packages/pages/activity_registration/list': 'activity',
    '/packages/pages/activity_registration/detail': 'activity',
    '/packages/pages/review/list': 'review',
    '/packages/pages/notification/index': 'notification',
    '/packages/pages/staff_favorite/staff_favorite': 'favorite',
    '/packages/pages/collection/collection': 'favorite',
    '/packages/pages/aftersale/index': 'aftersale',
    '/packages/pages/waitlist/waitlist': 'waitlist',
    '/pages/user_set/user_set': 'settings',
    '/packages/pages/user_wallet/user_wallet': 'wallet'
}
const QUICK_ENTRY_KEY_BY_TITLE: Record<string, string> = {
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

const roleEntryStates = computed(() =>
    getRoleEntryStates({
        featureSwitch: featureSwitch.value,
        userInfo: userInfo.value,
        isLogin: isLogin.value
    })
)

const visibleRoleEntryItems = computed<QuickEntryItem[]>(() => {
    return roleEntryStates.value
        .filter((item) => item.visible)
        .map((item) => ({
            key: item.key,
            title: item.title,
            subtitle: '',
            is_show: '1',
            disabled: !item.enabled,
            requiresLogin: true,
            link: {
                path: appendPageContractQuery({
                    path: item.routePath,
                    scene: item.scene,
                    source: item.source,
                    back: item.back
                }),
                type: 'shop'
            }
        }))
})

const widgetMap = computed(() => {
    return state.pages.reduce((record, item) => {
        if (item?.name) {
            record[item.name] = item
        }
        return record
    }, {} as Record<string, DecorateWidget>)
})

const userInfoWidget = computed(
    () => widgetMap.value['user-info'] || createDefaultUserWidgets()['user-info']
)

const weddingCountdownWidget = computed(
    () => widgetMap.value['wedding-countdown'] || createDefaultUserWidgets()['wedding-countdown']
)

const quickEntryWidget = computed(
    () => widgetMap.value['quick-entry'] || createDefaultUserWidgets()['quick-entry']
)

const showRoleEntryWidget = computed(() => visibleRoleEntryItems.value.length > 0)

const getQuickEntryRuntimeSubtitle = (key = '') => {
    const pendingServiceCount = Number(
        orderStats.value.pending_service ?? orderStats.value.paid ?? 0
    )
    const activeOrderCount =
        Number(orderStats.value.pending_confirm || 0) +
        Number(orderStats.value.pending_pay || 0) +
        pendingServiceCount +
        Number(orderStats.value.in_service || 0)

    if (key === 'order') {
        return activeOrderCount > 0 ? `${activeOrderCount} 个进行中` : ''
    }

    if (key === 'notification') {
        const messageCount = Number(unreadMessageCount.value || 0)
        return messageCount > 0 ? `${messageCount} 条待处理` : ''
    }

    return ''
}

const inferQuickEntryKey = (item: any) => {
    const configuredKey = String(item?.key || '').trim()
    if (configuredKey) return configuredKey

    const linkPath = getLinkPath(item?.link)
    if (linkPath && QUICK_ENTRY_KEY_BY_PATH[linkPath]) {
        return QUICK_ENTRY_KEY_BY_PATH[linkPath]
    }

    const title = String(item?.title || item?.name || '').trim()
    return QUICK_ENTRY_KEY_BY_TITLE[title] || ''
}

const toBooleanFlag = (value: unknown, fallback = false) => {
    if (value === undefined || value === null || value === '') return fallback
    if (typeof value === 'boolean') return value
    if (typeof value === 'number') return value === 1
    if (typeof value === 'string') return ['1', 'true', 'yes'].includes(value.toLowerCase())
    return Boolean(value)
}

const normalizeQuickEntryItem = (item: any, fallbackItem?: QuickEntryItem): QuickEntryItem | null => {
    if (!item || typeof item !== 'object') return null

    const link = item.link || fallbackItem?.link
    if (!hasConfiguredLink(link)) return null

    const key = inferQuickEntryKey(item) || fallbackItem?.key || ''
    const title = String(item.title || item.name || fallbackItem?.title || '').trim()
    if (!title) return null

    return {
        ...(fallbackItem || {}),
        ...item,
        key,
        title,
        subtitle: String(item.subtitle ?? fallbackItem?.subtitle ?? '').trim(),
        is_show: String(item.is_show ?? fallbackItem?.is_show ?? '1'),
        disabled: toBooleanFlag(item.disabled, fallbackItem?.disabled ?? false),
        requiresLogin: toBooleanFlag(item.requiresLogin, fallbackItem?.requiresLogin ?? true),
        link
    }
}

const getConfiguredQuickEntryItems = (content: Record<string, any>) => {
    const list = Array.isArray(content?.data) ? content.data : []
    const defaultsByKey = new Map(DEFAULT_QUICK_ENTRY_ITEMS.map((item) => [item.key, item]))

    return list
        .map((item: any) => normalizeQuickEntryItem(item, defaultsByKey.get(inferQuickEntryKey(item))))
        .filter((item): item is QuickEntryItem => !!item)
}

const getLegacyMyServiceItems = (widgets: DecorateWidget[]) => {
    const legacyWidget = widgets.find((item) => item?.name === 'my-service')
    const rawList = legacyWidget?.content?.data
    const list = Array.isArray(rawList) ? rawList : []
    if (!list.length) return []

    const defaultsByKey = new Map(DEFAULT_QUICK_ENTRY_ITEMS.map((item) => [item.key, item]))
    return list
        .map((item: any) => {
            const mappedItem = {
                ...item,
                title: item?.title || item?.name,
                subtitle: item?.subtitle || '',
                key: item?.key || inferQuickEntryKey(item)
            }
            return normalizeQuickEntryItem(mappedItem, defaultsByKey.get(inferQuickEntryKey(mappedItem)))
        })
        .filter((item): item is QuickEntryItem => !!item)
}

const mergeQuickEntryItemsWithRuntimeData = (items: QuickEntryItem[]) => {
    return items
        .filter((item) => String(item.is_show ?? '1') !== '0')
        .map((item) => {
            const runtimeSubtitle = getQuickEntryRuntimeSubtitle(item.key || '')
            return {
                ...item,
                subtitle: runtimeSubtitle || item.subtitle || ''
            }
        })
}

const parseDecorateWidgets = (rawData: unknown): DecorateWidget[] => {
    if (!rawData) return []
    try {
        const parsed = typeof rawData === 'string' ? JSON.parse(rawData) : rawData
        if (Array.isArray(parsed)) {
            return parsed
        }
        if (parsed && typeof parsed === 'object') {
            const keys = Object.keys(parsed as Record<string, unknown>)
            if (keys.length && keys.every((key) => /^\d+$/.test(key))) {
                return Object.values(parsed as Record<string, DecorateWidget>)
            }
        }
        return []
    } catch (error) {
        console.error('解析个人中心装修数据失败', error)
        return []
    }
}

const createDefaultUserWidgets = (): Record<string, DecorateWidget> => ({
    'user-info': {
        name: 'user-info',
        content: {
            enabled: 1
        },
        styles: {}
    },
    'wedding-countdown': {
        name: 'wedding-countdown',
        content: {
            enabled: 1,
            style: 4
        },
        styles: {}
    },
    'quick-entry': {
        name: 'quick-entry',
        content: {
            enabled: 1,
            title: '账户入口',
            subtitle: '',
            style: 3,
            data: DEFAULT_QUICK_ENTRY_ITEMS
        },
        styles: {}
    }
})

const normalizeUserWidgets = (sourceWidgets: DecorateWidget[]): DecorateWidget[] => {
    const defaults = createDefaultUserWidgets()
    const sourceMap = new Map<string, DecorateWidget>()
    sourceWidgets.forEach((item) => {
        if (item?.name) sourceMap.set(item.name, item)
    })

    const legacyQuickEntryItems = getLegacyMyServiceItems(sourceWidgets)

    return USER_WIDGET_ORDER.map((name) => {
        const defaultItem = defaults[name]
        const sourceItem = sourceMap.get(name)
        const normalizedItem = {
            ...defaultItem,
            ...sourceItem,
            content: {
                ...(defaultItem.content || {}),
                ...(sourceItem?.content || {})
            },
            styles: {
                ...(defaultItem.styles || {}),
                ...(sourceItem?.styles || {})
            }
        }

        if (name === 'quick-entry') {
            const configuredItems = getConfiguredQuickEntryItems(normalizedItem.content || {})
            normalizedItem.content = {
                ...normalizedItem.content,
                style: 3,
                data: configuredItems.length
                    ? configuredItems
                    : legacyQuickEntryItems.length
                      ? legacyQuickEntryItems
                      : DEFAULT_QUICK_ENTRY_ITEMS
            }
        }

        return normalizedItem
    })
}

const isComponentEnabled = (item: DecorateWidget) => item.content?.enabled !== 0

const mergeUserInfoContent = (content: Record<string, any>) => {
    const weddingDateText = String(weddingInfo.value?.wedding_date || '').trim()
    return {
        ...content,
        profile_subtitle: weddingDateText ? `婚期：${weddingDateText}` : ''
    }
}

const mergeWeddingCountdownContent = (content: Record<string, any>) => {
    return {
        ...content,
        style: 4
    }
}

const mergeQuickEntryContent = (content: Record<string, any>) => {
    const configuredItems = getConfiguredQuickEntryItems(content)
    const sourceItems = configuredItems.length ? configuredItems : DEFAULT_QUICK_ENTRY_ITEMS
    return {
        ...content,
        style: 3,
        title: content.title || '快捷功能',
        subtitle: content.subtitle || '',
        data: mergeQuickEntryItemsWithRuntimeData(sourceItems)
    }
}

const mergeRoleEntryContent = (content: Record<string, any>) => {
    return {
        ...content,
        enabled: visibleRoleEntryItems.value.length ? 1 : 0,
        style: 3,
        title: '角色入口',
        subtitle: '',
        data: visibleRoleEntryItems.value
    }
}

const loadDecorateData = async () => {
    try {
        const data = await getDecorate({ id: 2 })
        const widgets = parseDecorateWidgets(data?.data)
        state.pages = normalizeUserWidgets(widgets)
    } catch (error) {
        console.error('获取个人中心装修数据失败', error)
        state.pages = normalizeUserWidgets([])
    }
}

const loadOrderStats = async () => {
    if (!isLogin.value) {
        orderStats.value = {}
        return
    }
    try {
        orderStats.value = (await getOrderStatistics()) || {}
    } catch (error) {
        console.error('获取订单统计失败', error)
        orderStats.value = {}
    }
}

const loadUnreadMessageCount = async () => {
    if (!isLogin.value) {
        unreadMessageCount.value = 0
        return
    }
    try {
        const result = await loadUserBadgeData({ loadMessage: true })
        unreadMessageCount.value = Number(result.messageCount || 0)
    } catch (error) {
        console.error('获取未读消息数失败', error)
        unreadMessageCount.value = 0
    }
}

const loadWeddingInfo = async () => {
    if (!isLogin.value) {
        weddingInfo.value = {}
        return
    }
    try {
        weddingInfo.value = (await getUserWeddingDate()) || {}
    } catch (error) {
        console.error('获取婚礼信息失败', error)
        weddingInfo.value = {}
    }
}

onShow(async () => {
    if (isLogin.value && !userInfo.value?.id) {
        await userStore.getUser()
    }

    await Promise.all([
        appStore.getConfig().catch(() => ({})),
        loadOrderStats(),
        loadUnreadMessageCount(),
        loadWeddingInfo(),
        loadDecorateData()
    ])
    badgeRefreshKey.value += 1
})
</script>

<style lang="scss" scoped>
.user-page {
    position: relative;
    box-sizing: border-box;
    min-height: 100%;
    background:
        linear-gradient(180deg, rgba(25, 23, 19, 0.08) 0, rgba(25, 23, 19, 0) 120rpx),
        radial-gradient(circle at 82% 0%, rgba(200, 164, 93, 0.14) 0, rgba(200, 164, 93, 0) 320rpx),
        var(--wm-color-bg-page, #fbfaf7);
    --wm-user-page-content-top: 24rpx;
    --wm-user-page-content-side: 40rpx;
    --wm-user-page-content-bottom: var(--wm-safe-bottom-tabbar, calc(164rpx + env(safe-area-inset-bottom)));
    --wm-user-page-section-gap: 24rpx;
    --wm-user-profile-radius: 32rpx;
    --wm-user-profile-padding: 34rpx 40rpx;
    --wm-user-profile-min-height: 172rpx;
    --wm-user-profile-avatar-size: 104rpx;
    --wm-user-profile-avatar-radius: 999rpx;
    --wm-user-profile-gap: 22rpx;
    --wm-user-countdown-radius: var(--wm-radius-card-lg, 32rpx);
    --wm-user-countdown-padding-top: 28rpx;
    --wm-user-countdown-padding-right: 30rpx;
    --wm-user-countdown-padding-bottom: 28rpx;
    --wm-user-countdown-padding-left: 30rpx;
    --wm-user-countdown-gap: 16rpx;
    --wm-user-quick-radius: 30rpx;
    --wm-user-quick-padding: 28rpx 30rpx;
    --wm-user-quick-title-gap: 20rpx;
    --wm-user-quick-grid-gap: 16rpx;
    --wm-user-quick-item-radius: 26rpx;
    --wm-user-quick-item-padding: 24rpx 26rpx;
    --wm-user-quick-item-gap: 10rpx;
    --wm-user-quick-item-height: 112rpx;
}

.user-page__body {
    display: flex;
    flex-direction: column;
    gap: var(--wm-user-page-section-gap);
    padding: var(--wm-user-page-content-top) var(--wm-user-page-content-side)
        var(--wm-user-page-content-bottom) var(--wm-user-page-content-side);
    box-sizing: border-box;
}

.user-page__fixed-skeleton,
.user-page__widget-zone {
    display: flex;
    flex-direction: column;
    gap: var(--wm-user-page-section-gap);
}

.user-page :deep(.mp-page-header) {
    border-bottom-color: rgba(217, 190, 130, 0.78);
}

.user-page :deep(.mp-page-header__title-text--large) {
    font-size: 44rpx;
    line-height: 1.08;
}

.user-page :deep(.user-card) {
    border-radius: 32rpx;
    background: linear-gradient(135deg, rgba(255, 253, 248, 0.98) 0%, #F1E5C8 100%);
    border-color: rgba(216, 201, 173, 0.9);
    box-shadow: 0 16rpx 36rpx rgba(74, 43, 24, 0.08);
}

.user-page :deep(.profile-row) {
    min-width: 0;
}

.user-page :deep(.profile-meta-row) {
    gap: 0;
}

.user-page :deep(.profile-eyebrow) {
    margin-right: 14rpx;
}

.user-page :deep(.profile-action) {
    min-width: 124rpx;
    height: 58rpx;
    justify-content: center;
    margin-left: 20rpx;
    padding: 0 20rpx;
    box-shadow: 0 10rpx 22rpx rgba(25, 23, 19, 0.16);
}

.user-page :deep(.quick-entry-widget) {
    gap: 16rpx;
}

.user-page :deep(.profile-quick-heading) {
    padding: 0 2rpx;
}

.user-page :deep(.profile-role-track) {
    gap: 18rpx;
}

.user-page :deep(.profile-role-pill) {
    min-height: 116rpx;
    border-radius: 30rpx;
}

.user-page :deep(.profile-role-pill__inner) {
    min-height: 116rpx;
    padding: 28rpx;
}

.user-page :deep(.profile-role-copy) {
    gap: 6rpx;
    padding-right: 16rpx;
}

.user-page :deep(.profile-role-title) {
    font-size: 28rpx;
    line-height: 1.35;
}

.user-page :deep(.profile-role-pill--staff-center) {
    background: linear-gradient(135deg, #2B261D 0%, #191713 76%);
    border-color: rgba(217, 190, 130, 0.78);
    box-shadow: 0 14rpx 28rpx rgba(25, 23, 19, 0.16);
}

.user-page :deep(.profile-role-pill--disabled.profile-role-pill--staff-center) {
    opacity: 1;
    background: linear-gradient(135deg, #322C22 0%, #211D18 76%);
    border-color: rgba(217, 190, 130, 0.66);
}

.user-page :deep(.profile-entry-panel) {
    border-radius: 32rpx;
    --wm-space-list-panel-y: 20rpx;
    --wm-space-list-panel-x: 30rpx;
    background: linear-gradient(180deg, rgba(255, 253, 248, 0.98) 0%, #F6EAC9 100%);
    border-color: rgba(216, 201, 173, 0.92);
    box-shadow: 0 16rpx 36rpx rgba(74, 43, 24, 0.08);
}

.user-page :deep(.profile-entry-primary),
.user-page :deep(.profile-entry-row) {
    min-height: 92rpx;
}

.user-page :deep(.base-menu-row__label) {
    font-size: 28rpx;
}

.user-page :deep(.base-menu-row__value) {
    max-width: 250rpx;
    color: var(--wm-text-secondary, #665E52);
}

</style>
