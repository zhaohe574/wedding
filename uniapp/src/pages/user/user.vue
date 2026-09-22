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

                    <!-- 4 维快速资产数据栏 -->
                    <view class="user-metrics-card">
                        <view class="user-metric-col" @click="handleMetricClick('favorite')">
                            <text class="user-metric-value">{{ isLogin ? favoriteCount : '-' }}</text>
                            <text class="user-metric-label">我的收藏</text>
                        </view>
                        <view class="user-metric-divider"></view>
                        <view class="user-metric-col" @click="handleMetricClick('waitlist')">
                            <text class="user-metric-value">{{ isLogin ? waitlistCount : '-' }}</text>
                            <text class="user-metric-label">我的候补</text>
                        </view>
                        <view class="user-metric-divider"></view>
                        <view class="user-metric-col" @click="handleMetricClick('activity')">
                            <text class="user-metric-value">{{ isLogin ? activityCount : '-' }}</text>
                            <text class="user-metric-label">我的活动</text>
                        </view>
                        <view class="user-metric-divider"></view>
                        <view class="user-metric-col" @click="handleMetricClick('notification')">
                            <text
                                class="user-metric-value"
                                :class="{ 'user-metric-value--unread': isLogin && unreadMessageCount > 0 }"
                            >
                                {{ isLogin ? unreadMessageCount : '-' }}
                            </text>
                            <text class="user-metric-label">消息待办</text>
                        </view>
                    </view>

                    <!-- 我的订单高定业务看板 -->
                    <BaseCard class="user-order-hub" variant="panel" padding="26rpx 24rpx" border-radius="28rpx">
                        <view class="user-order-hub__head">
                            <view class="user-order-hub__title-wrap">
                                <BaseIcon name="order" size="30" color="#191713" />
                                <text class="user-order-hub__title">我的订单</text>
                            </view>
                            <view class="user-order-hub__all" @click="goOrder('')">
                                <text class="user-order-hub__all-text">全部订单</text>
                                <BaseIcon name="right" size="22" color="#8A806F" />
                            </view>
                        </view>
                        <view class="user-order-hub__grid">
                            <view class="user-order-item" @click="goOrder('pending_confirm')">
                                <view class="user-order-icon-box">
                                    <BaseIcon name="file-text" size="40" color="#B8954A" />
                                    <view v-if="Number(orderStats.pending_confirm || 0) > 0" class="user-order-badge">
                                        <text class="user-order-badge-text">{{ Number(orderStats.pending_confirm) > 99 ? '99+' : orderStats.pending_confirm }}</text>
                                    </view>
                                </view>
                                <text class="user-order-label">待确认</text>
                            </view>
                            <view class="user-order-item" @click="goOrder('pending_pay')">
                                <view class="user-order-icon-box">
                                    <BaseIcon name="wallet" size="40" color="#B8954A" />
                                    <view v-if="Number(orderStats.pending_pay || 0) > 0" class="user-order-badge">
                                        <text class="user-order-badge-text">{{ Number(orderStats.pending_pay) > 99 ? '99+' : orderStats.pending_pay }}</text>
                                    </view>
                                </view>
                                <text class="user-order-label">待支付</text>
                            </view>
                            <view class="user-order-item" @click="goOrder('paid')">
                                <view class="user-order-icon-box">
                                    <BaseIcon name="calendar" size="40" color="#B8954A" />
                                    <view v-if="pendingServiceCount > 0" class="user-order-badge">
                                        <text class="user-order-badge-text">{{ pendingServiceCount > 99 ? '99+' : pendingServiceCount }}</text>
                                    </view>
                                </view>
                                <text class="user-order-label">待服务</text>
                            </view>
                            <view class="user-order-item" @click="goOrder('in_service')">
                                <view class="user-order-icon-box">
                                    <BaseIcon name="heart-fill" size="40" color="#B8954A" />
                                    <view v-if="Number(orderStats.in_service || 0) > 0" class="user-order-badge">
                                        <text class="user-order-badge-text">{{ Number(orderStats.in_service) > 99 ? '99+' : orderStats.in_service }}</text>
                                    </view>
                                </view>
                                <text class="user-order-label">服务中</text>
                            </view>
                            <view class="user-order-item" @click="goAftersale">
                                <view class="user-order-icon-box">
                                    <BaseIcon name="shield-check" size="40" color="#B8954A" />
                                </view>
                                <text class="user-order-label">售后/退款</text>
                            </view>
                        </view>
                    </BaseCard>

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
import { getMyFavoriteStaff } from '@/api/staff'
import { getMyWaitlist } from '@/api/schedule'
import { getActivityRegistrations } from '@/api/dynamic'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
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
const favoriteCount = ref(0)
const waitlistCount = ref(0)
const activityCount = ref(0)
const pendingServiceCount = computed(() => Number(orderStats.value.pending_service ?? orderStats.value.paid ?? 0))

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
    { key: 'oa_notice', title: '服务号通知', subtitle: '关注与绑定', is_show: '1', disabled: false, requiresLogin: true,
        link: { path: '/pages/oa_subscribe/oa_subscribe', type: 'shop' } },
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

const goNotification = () => {
    if (!isLogin.value) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    uni.navigateTo({ url: '/packages/pages/notification/index' })
}

const goSettings = () => {
    uni.navigateTo({ url: '/pages/user_set/user_set' })
}

const goOrder = (status = '') => {
    if (!isLogin.value) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    const url = status ? `/pages/order/order?status=${status}` : '/pages/order/order'
    uni.navigateTo({ url })
}

const goAftersale = () => {
    if (!isLogin.value) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    uni.navigateTo({ url: '/packages/pages/aftersale/index' })
}

const handleMetricClick = (key: string) => {
    if (!isLogin.value) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    switch (key) {
        case 'favorite':
            uni.navigateTo({ url: '/packages/pages/staff_favorite/staff_favorite' })
            break
        case 'waitlist':
            uni.navigateTo({ url: '/packages/pages/waitlist/waitlist' })
            break
        case 'activity':
            uni.navigateTo({ url: '/packages/pages/my_activity/my_activity' })
            break
        case 'notification':
            uni.navigateTo({ url: '/packages/pages/notification/index' })
            break
    }
}

const loadExtraUserMetrics = async () => {
    if (!isLogin.value) {
        favoriteCount.value = 0
        waitlistCount.value = 0
        activityCount.value = 0
        return
    }
    try {
        const [favRes, waitRes, actRes] = await Promise.allSettled([
            getMyFavoriteStaff(),
            getMyWaitlist(),
            getActivityRegistrations()
        ])
        if (favRes.status === 'fulfilled') {
            const d = favRes.value
            favoriteCount.value = Array.isArray(d) ? d.length : (d?.lists?.length ?? d?.data?.length ?? 0)
        }
        if (waitRes.status === 'fulfilled') {
            const d = waitRes.value
            waitlistCount.value = Array.isArray(d) ? d.length : (d?.lists?.length ?? d?.data?.length ?? 0)
        }
        if (actRes.status === 'fulfilled') {
            const d = actRes.value
            activityCount.value = Array.isArray(d?.lists) ? d.lists.length : (d?.data?.length ?? 0)
        }
    } catch {
        // Non-blocking
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
        loadExtraUserMetrics(),
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
        linear-gradient(180deg, rgba(25, 23, 19, 0.06) 0, rgba(25, 23, 19, 0) 120rpx),
        radial-gradient(circle at 82% 0%, rgba(200, 164, 93, 0.12) 0, rgba(200, 164, 93, 0) 320rpx),
        var(--wm-color-bg-page, #FAF8F3);
    --wm-user-page-content-top: 20rpx;
    --wm-user-page-content-side: 24rpx;
    --wm-user-page-content-bottom: var(--wm-safe-bottom-tabbar, calc(144rpx + env(safe-area-inset-bottom)));
    --wm-user-page-section-gap: 20rpx;
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

/* 4 维快速资产数据栏 */
.user-metrics-card {
    display: flex;
    align-items: center;
    justify-content: space-around;
    padding: 24rpx 12rpx;
    border-radius: 28rpx;
    background: #FFFDF8;
    border: 1.5rpx solid rgba(217, 190, 130, 0.35);
    box-shadow: 0 10rpx 28rpx rgba(74, 43, 24, 0.04);
}

.user-metric-col {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6rpx;
    transition: transform 0.15s ease;

    &:active {
        transform: scale(0.95);
    }
}

.user-metric-value {
    font-size: 34rpx;
    font-weight: 800;
    color: #191713;
    line-height: 1.1;

    &--unread {
        color: #B84A39;
    }
}

.user-metric-label {
    font-size: 22rpx;
    color: #8A806F;
    line-height: 1.2;
    font-weight: 600;
}

.user-metric-divider {
    width: 1rpx;
    height: 36rpx;
    background: rgba(217, 190, 130, 0.3);
}

/* 我的订单高定业务看板 */
.user-order-hub {
    display: block;
    border-radius: 28rpx;
    background: #FFFDF8;
    border: 1.5rpx solid rgba(217, 190, 130, 0.38);
    box-shadow: 0 10rpx 28rpx rgba(74, 43, 24, 0.04);

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 22rpx;
        border-bottom: 1rpx solid rgba(217, 190, 130, 0.22);
    }

    &__title-wrap {
        display: flex;
        align-items: center;
        gap: 12rpx;
    }

    &__title {
        font-size: 28rpx;
        font-weight: 800;
        color: #191713;
    }

    &__all {
        display: flex;
        align-items: center;
        gap: 4rpx;

        &:active {
            opacity: 0.7;
        }
    }

    &__all-text {
        font-size: 23rpx;
        font-weight: 600;
        color: #8A806F;
    }

    &__grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        padding-top: 24rpx;
        gap: 6rpx;
    }
}

.user-order-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10rpx;
    transition: transform 0.15s ease;

    &:active {
        transform: scale(0.94);
    }
}

.user-order-icon-box {
    position: relative;
    width: 76rpx;
    height: 76rpx;
    border-radius: 22rpx;
    background: #FAF6EE;
    border: 1rpx solid rgba(217, 190, 130, 0.35);
    display: flex;
    align-items: center;
    justify-content: center;
}

.user-order-label {
    font-size: 22rpx;
    font-weight: 600;
    color: #4A443B;
    line-height: 1.2;
}

.user-order-badge {
    position: absolute;
    top: -8rpx;
    right: -10rpx;
    min-width: 30rpx;
    height: 30rpx;
    padding: 0 6rpx;
    border-radius: 999rpx;
    background: linear-gradient(135deg, #E65A4B 0%, #B84A39 100%);
    border: 2rpx solid #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
}

.user-order-badge-text {
    font-size: 17rpx;
    font-weight: 800;
    color: #ffffff;
    line-height: 1;
}

.user-page :deep(.mp-page-header) {
    border-bottom-color: rgba(217, 190, 130, 0.78);
}

.user-page :deep(.mp-page-header__title-text--large) {
    font-size: 44rpx;
    line-height: 1.08;
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

.user-page :deep(.base-menu-row__label) {
    font-size: 28rpx;
}

.user-page :deep(.base-menu-row__value) {
    max-width: 250rpx;
    color: var(--wm-text-secondary, #665E52);
}
</style>
