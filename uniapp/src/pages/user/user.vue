<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasTabbar>
        <view class="user-page">
            <MpPageHeader title="我的" surface="glass" />
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
import { appendPageContractQuery, getPageContract, getRoleEntryStates } from '@/utils/page-contract'
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
    key: string
    title: string
    subtitle: string
    is_show: string
    disabled: boolean
    requiresLogin?: boolean
    link: {
        path: string
        type: string
    }
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
const USER_PAGE_CONTRACT = getPageContract('/pages/user/user')

const featureSwitch = computed(() => appStore.config?.feature_switch || {})

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
            subtitle: item.subtitle,
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

const weddingProfileSubtitle = computed(() => {
    const weddingDateText = String(weddingInfo.value?.wedding_date || '').trim()
    if (weddingDateText) {
        return `婚期已同步：${weddingDateText}`
    }
    return '完善婚期、场地与称呼'
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

const buildQuickEntryData = (): QuickEntryItem[] => {
    const pendingServiceCount = Number(
        orderStats.value.pending_service ?? orderStats.value.paid ?? 0
    )
    const activeOrderCount =
        Number(orderStats.value.pending_confirm || 0) +
        Number(orderStats.value.pending_pay || 0) +
        pendingServiceCount +
        Number(orderStats.value.in_service || 0)

    return [
        {
            key: 'order',
            title: '我的订单',
            subtitle: `${activeOrderCount} 个进行中`,
            is_show: '1',
            disabled: false,
            requiresLogin: true,
            link: { path: '/pages/order/order', type: 'shop' }
        },
        {
            key: 'notification',
            title: '通知中心',
            subtitle: Number(unreadMessageCount.value || 0)
                ? `${Number(unreadMessageCount.value || 0)} 条待处理消息`
                : '订单、互动消息会在这里同步',
            is_show: '1',
            disabled: false,
            requiresLogin: true,
            link: { path: '/packages/pages/notification/index', type: 'shop' }
        },
        {
            key: 'profile',
            title: '资料与婚期',
            subtitle: weddingProfileSubtitle.value,
            is_show: '1',
            disabled: false,
            requiresLogin: true,
            link: { path: '/pages/user_data/user_data', type: 'shop' }
        },
        {
            key: 'favorite',
            title: '我的收藏',
            subtitle: '服务人员与内容收藏入口',
            is_show: '1',
            disabled: false,
            requiresLogin: true,
            link: { path: '/packages/pages/staff_favorite/staff_favorite', type: 'shop' }
        },
        {
            key: 'aftersale',
            title: '售后服务',
            subtitle: '投诉、工单、回访统一查看',
            is_show: '1',
            disabled: false,
            requiresLogin: true,
            link: { path: '/packages/pages/aftersale/index', type: 'shop' }
        },
        {
            key: 'waitlist',
            title: '我的候补',
            subtitle: '查看候补进度与平台通知',
            is_show: '1',
            disabled: false,
            requiresLogin: true,
            link: { path: '/packages/pages/waitlist/waitlist', type: 'shop' }
        },
        {
            key: 'settings',
            title: '设置',
            subtitle: '账号与协议管理',
            is_show: '1',
            disabled: false,
            requiresLogin: true,
            link: { path: '/pages/user_set/user_set', type: 'shop' }
        }
    ]
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
            style: 3,
            data: buildQuickEntryData()
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

    return USER_WIDGET_ORDER.map((name) => {
        const defaultItem = defaults[name]
        const sourceItem = sourceMap.get(name)
        return {
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
    })
}

const isComponentEnabled = (item: DecorateWidget) => item.content?.enabled !== 0

const mergeUserInfoContent = (content: Record<string, any>) => {
    const weddingDateText = String(weddingInfo.value?.wedding_date || '').trim()
    return {
        ...content,
        profile_subtitle: weddingDateText
            ? `婚礼主档期：${weddingDateText}`
            : '点击完善资料，更新主档期'
    }
}

const mergeWeddingCountdownContent = (content: Record<string, any>) => {
    return {
        ...content,
        style: 4
    }
}

const mergeQuickEntryContent = (content: Record<string, any>) => {
    return {
        ...content,
        style: 3,
        title: content.title || '快捷功能',
        subtitle: content.subtitle || '常用入口',
        data: buildQuickEntryData()
    }
}

const mergeRoleEntryContent = (content: Record<string, any>) => {
    return {
        ...content,
        enabled: visibleRoleEntryItems.value.length ? 1 : 0,
        style: 3,
        title: '角色入口',
        subtitle: USER_PAGE_CONTRACT.audience.consumer === 'included' ? '固定骨架区' : '兼容入口',
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
    --wm-user-page-content-top: 22rpx;
    --wm-user-page-content-side: 37rpx;
    --wm-user-page-content-bottom: 37rpx;
    --wm-user-page-section-gap: 30rpx;
    --wm-user-profile-radius: 49rpx;
    --wm-user-profile-padding: 30rpx;
    --wm-user-profile-min-height: 149rpx;
    --wm-user-profile-avatar-size: 104rpx;
    --wm-user-profile-avatar-radius: 52rpx;
    --wm-user-profile-gap: 30rpx;
    --wm-user-countdown-radius: 52rpx;
    --wm-user-countdown-padding-top: 34rpx;
    --wm-user-countdown-padding-right: 34rpx;
    --wm-user-countdown-padding-bottom: 37rpx;
    --wm-user-countdown-padding-left: 34rpx;
    --wm-user-countdown-gap: 15rpx;
    --wm-user-quick-radius: 45rpx;
    --wm-user-quick-padding: 30rpx;
    --wm-user-quick-title-gap: 30rpx;
    --wm-user-quick-grid-gap: 22rpx;
    --wm-user-quick-item-radius: 37rpx;
    --wm-user-quick-item-padding: 30rpx;
    --wm-user-quick-item-gap: 11rpx;
    --wm-user-quick-item-height: 116rpx;
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
</style>
