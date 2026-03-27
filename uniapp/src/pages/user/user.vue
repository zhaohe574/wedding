<template>
    <page-meta :page-style="$theme.pageStyle" />
    <view class="user-page page-with-tabbar-safe-bottom">
        <template v-for="(item, index) in state.pages" :key="index">
            <template v-if="item.name === 'user-info'">
                <w-user-info
                    :content="mergeUserInfoContent(item.content || {})"
                    :styles="item.styles"
                    :user="userInfo"
                    :isLogin="isLogin"
                    :wedding-info="weddingInfo"
                />
            </template>
            <template v-if="item.name === 'wedding-countdown' && isComponentEnabled(item)">
                <w-wedding-countdown
                    :content="mergeWeddingCountdownContent(item.content || {})"
                    :styles="item.styles"
                    :is-login="isLogin"
                />
            </template>
            <template v-if="item.name === 'quick-entry' && isComponentEnabled(item)">
                <w-quick-entry :content="mergeQuickEntryContent(item.content || {})" :styles="item.styles" />
            </template>
        </template>
        <tabbar :badge-refresh-key="badgeRefreshKey" />
    </view>
</template>

<script setup lang="ts">
import { getDecorate } from '@/api/shop'
import { getOrderStatistics } from '@/api/order'
import { getUserWeddingDate } from '@/api/user'
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

const staffEntryEnabled = computed(() => {
    return featureSwitch.value.staff_center === 1 && !!userInfo.value?.is_staff
})

const adminEntryEnabled = computed(() => {
    if (featureSwitch.value.admin_dashboard !== 1) return false
    const rawUserIds = String(featureSwitch.value.admin_dashboard_user_ids || '')
    if (!rawUserIds.trim()) return false
    const allowedUserIds = rawUserIds
        .split(',')
        .map((item: string) => Number(item.trim()))
        .filter((item: number) => Number.isFinite(item) && item > 0)
    const currentUserId = Number(userInfo.value?.id || userInfo.value?.user_id || 0)
    return currentUserId > 0 && allowedUserIds.includes(currentUserId)
})

const buildQuickEntryData = () => {
    const activeOrderCount =
        Number(orderStats.value.pending_confirm || 0) +
        Number(orderStats.value.pending_pay || 0) +
        Number(orderStats.value.in_service || 0)

    return [
        {
            key: 'order',
            title: '我的订单',
            subtitle: `${activeOrderCount} 个进行中`,
            is_show: '1',
            disabled: false,
            link: { path: '/pages/order/order', type: 'shop' }
        },
        {
            key: 'notification',
            title: '通知中心',
            subtitle: `${Number(unreadMessageCount.value || 0)} 条未读`,
            is_show: '1',
            disabled: false,
            link: { path: '/packages/pages/notification/index', type: 'shop' }
        },
        {
            key: 'aftersale',
            title: '售后服务',
            subtitle: '投诉 / 工单 / 补拍',
            is_show: '1',
            disabled: false,
            link: { path: '/packages/pages/aftersale/index', type: 'shop' }
        },
        {
            key: 'waitlist',
            title: '我的候补',
            subtitle: '查看候补进度',
            is_show: '1',
            disabled: false,
            link: { path: '/packages/pages/waitlist/waitlist', type: 'shop' }
        },
        {
            key: 'staff-center',
            title: '服务人员入口',
            subtitle: staffEntryEnabled.value ? '服务人员管理页面' : '需服务人员身份',
            is_show: isLogin.value ? '1' : '0',
            disabled: !staffEntryEnabled.value,
            link: { path: '/packages/pages/staff_center/staff_center', type: 'shop' }
        },
        {
            key: 'admin-dashboard',
            title: '驾驶舱',
            subtitle: adminEntryEnabled.value ? '管理员驾驶舱入口' : '需管理员权限',
            is_show: isLogin.value ? '1' : '0',
            disabled: !adminEntryEnabled.value,
            link: { path: '/packages/pages/admin_dashboard/admin_dashboard', type: 'shop' }
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
        profile_subtitle: weddingDateText ? `婚礼主档期：${weddingDateText}` : '点击完善资料，更新主档期'
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
        data: buildQuickEntryData()
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
    $theme.setScene('consumer')
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
    min-height: 100vh;
    background:
        radial-gradient(circle at top right, rgba(232, 90, 79, 0.08) 0, transparent 36%),
        linear-gradient(180deg, #fcfbf9 0%, #f7f3f0 58%, #f5f1ee 100%) !important;
}
</style>
