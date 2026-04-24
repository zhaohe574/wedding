<template>
    <view class="my-service mx-[24rpx] mt-[24rpx]">
        <!-- 标题 -->
        <view v-if="content.title" class="title-section mb-[20rpx] px-[8rpx]">
            <text class="text-[32rpx] font-semibold text-[#111111]">{{ content.title }}</text>
        </view>

        <!-- 网格布局 -->
        <view v-if="content.style == 1" class="service-grid bg-white rounded-[16rpx] p-[24rpx]">
            <view class="grid grid-cols-4 gap-[32rpx]">
                <view
                    v-for="(item, index) in displayList"
                    :key="index"
                    class="service-item flex flex-col items-center"
                    @click="handleClick(item.link)"
                >
                    <!-- 图标容器 -->
                    <view
                        class="icon-wrapper relative mb-[16rpx] p-[20rpx] rounded-[20rpx]"
                        :style="{ backgroundColor: getIconBg(index) }"
                    >
                        <image
                            class="w-[56rpx] h-[56rpx]"
                            :src="getImageUrl(item.image)"
                            mode="aspectFit"
                        />
                        <view
                            v-if="item.badgeCount > 0"
                            class="count-badge absolute -top-[8rpx] -right-[8rpx] min-w-[36rpx] h-[36rpx] rounded-full flex items-center justify-center px-[8rpx]"
                            :style="{ backgroundColor: $theme.ctaColor }"
                        >
                            <text class="text-white text-[20rpx] font-semibold">
                                {{ item.badgeCount > 99 ? '99+' : item.badgeCount }}
                            </text>
                        </view>
                    </view>
                    <!-- 文字 -->
                    <text class="text-[24rpx] text-[#5F5A50] text-center">{{ item.name }}</text>
                </view>
            </view>
        </view>

        <!-- 列表布局 -->
        <view
            v-if="content.style == 2"
            class="service-list bg-white rounded-[16rpx] overflow-hidden"
        >
            <view
                v-for="(item, index) in displayList"
                :key="index"
                class="service-list-item flex items-center px-[32rpx] py-[28rpx] border-b border-[#F8F7F2] last:border-b-0"
                @click="handleClick(item.link)"
            >
                <!-- 图标 -->
                <view
                    class="icon-wrapper relative p-[16rpx] rounded-[16rpx]"
                    :style="{ backgroundColor: getIconBg(index) }"
                >
                    <image
                        class="w-[48rpx] h-[48rpx]"
                        :src="getImageUrl(item.image)"
                        mode="aspectFit"
                    />
                    <view
                        v-if="item.badgeCount > 0"
                        class="count-badge absolute -top-[8rpx] -right-[8rpx] min-w-[36rpx] h-[36rpx] rounded-full flex items-center justify-center px-[8rpx]"
                        :style="{ backgroundColor: $theme.ctaColor }"
                    >
                        <text class="text-white text-[20rpx] font-semibold">
                            {{ item.badgeCount > 99 ? '99+' : item.badgeCount }}
                        </text>
                    </view>
                </view>
                <!-- 文字 -->
                <text class="flex-1 ml-[24rpx] text-[28rpx] text-[#111111]">{{ item.name }}</text>
                <!-- 箭头 -->
                <tn-icon name="right" size="32" color="#D8D3C7" />
            </view>
        </view>
    </view>
</template>
<script lang="ts" setup>
import { useAppStore } from '@/stores/app'
import { useThemeStore } from '@/stores/theme'
import { useUserStore } from '@/stores/user'
import { loadUserBadgeData } from '@/utils/user-badge'
import { hasConfiguredLink, navigateTo, normalizeAppPath } from '@/utils/util'
import { storeToRefs } from 'pinia'
import { computed, ref, watch } from 'vue'

const props = defineProps({
    content: {
        type: Object,
        default: () => ({})
    },
    styles: {
        type: Object,
        default: () => ({})
    },
    badgeRefreshKey: {
        type: [Number, String],
        default: 0
    }
})

const appStore = useAppStore()
const { getImageUrl } = appStore
const userStore = useUserStore()
const $theme = useThemeStore()
const { userInfo, isLogin } = storeToRefs(userStore)
const badgeState = ref({
    message: 0,
    staffTodo: 0
})
const badgeRequestToken = ref(0)
const hasInitializedBadgeLoad = ref(false)

const NOTIFICATION_PATHS = new Set(['/packages/pages/notification/index'])
const STAFF_CENTER_PATHS = new Set([
    '/packages/pages/staff_center/staff_center',
    '/pages/staff_center/staff_center'
])

const handleClick = (link: any) => {
    navigateTo(link)
}

// 获取图标背景色（循环使用主题色浅色变体）
const getIconBg = (index: number) => {
    const colors = [
        $theme.primaryColor + '15',
        $theme.secondaryColor + '15',
        $theme.ctaColor + '15',
        $theme.accentColor + '15'
    ]
    return colors[index % colors.length]
}

const featureSwitch = computed(() => appStore.config?.feature_switch || {})
const userId = computed(() => Number(userInfo.value?.id || userInfo.value?.user_id || 0))

const adminDashboardUserIds = computed(() => {
    const rawUserIds = String(featureSwitch.value?.admin_dashboard_user_ids || '')
    if (!rawUserIds.trim()) return []

    const idSet = new Set<number>()
    rawUserIds
        .split(/[\s,，]+/)
        .map((item) => Number(item))
        .forEach((id) => {
            if (Number.isInteger(id) && id > 0) {
                idSet.add(id)
            }
        })

    return Array.from(idSet)
})

const canAccessAdminDashboard = computed(() => {
    if (featureSwitch.value.admin_dashboard !== 1) return false
    if (userId.value <= 0) return false
    return adminDashboardUserIds.value.includes(userId.value)
})

const extraItems = computed(() => {
    const items: any[] = []
    if (featureSwitch.value.staff_center === 1 && userInfo.value?.is_staff) {
        items.push({
            name: '服务人员中心',
            image: 'resource/image/adminapi/default/menu_role.png',
            link: {
                path: '/packages/pages/staff_center/staff_center',
                name: '服务人员中心',
                type: 'shop'
            },
            is_show: '1'
        })
    }
    if (canAccessAdminDashboard.value) {
        items.push({
            name: '管理员看板',
            image: 'resource/image/adminapi/default/menu_admin.png',
            link: {
                path: '/packages/pages/admin_dashboard/admin_dashboard',
                name: '管理员看板',
                type: 'shop'
            },
            is_show: '1'
        })
    }
    return items
})

const showList = computed(() => {
    const base =
        props.content.data?.filter(
            (item: any) => item.is_show == '1' && hasConfiguredLink(item?.link)
        ) || []
    return [...base, ...extraItems.value]
})

const resolveLinkPath = (link: any) => {
    const rawPath = typeof link === 'string' ? link : String(link?.path || '')
    if (!rawPath) return ''
    return normalizeAppPath(rawPath.split('?')[0])
}

const isNotificationItem = (item: any) => {
    return NOTIFICATION_PATHS.has(resolveLinkPath(item?.link))
}

const isStaffCenterItem = (item: any) => {
    return STAFF_CENTER_PATHS.has(resolveLinkPath(item?.link))
}

const shouldLoadMessageBadge = computed(() => {
    return showList.value.some((item: any) => isNotificationItem(item))
})

const shouldLoadStaffTodoBadge = computed(() => {
    return (
        featureSwitch.value.staff_center === 1 &&
        !!userInfo.value?.is_staff &&
        showList.value.some((item: any) => isStaffCenterItem(item))
    )
})

const getBadgeCount = (item: any) => {
    if (isNotificationItem(item)) {
        return badgeState.value.message
    }
    if (isStaffCenterItem(item)) {
        return badgeState.value.staffTodo
    }
    return 0
}

const displayList = computed(() => {
    return showList.value.map((item: any) => ({
        ...item,
        badgeCount: getBadgeCount(item)
    }))
})

const loadBadgeData = async () => {
    const requestToken = ++badgeRequestToken.value

    if (!isLogin.value) {
        badgeState.value = {
            message: 0,
            staffTodo: 0
        }
        hasInitializedBadgeLoad.value = true
        return
    }

    const result = await loadUserBadgeData({
        loadMessage: shouldLoadMessageBadge.value,
        loadStaffTodo: shouldLoadStaffTodoBadge.value
    })

    if (requestToken !== badgeRequestToken.value) {
        hasInitializedBadgeLoad.value = true
        return
    }

    badgeState.value = {
        message: result.messageCount,
        staffTodo: result.staffTodoCount
    }
    hasInitializedBadgeLoad.value = true
}

watch(
    [isLogin, showList],
    () => {
        loadBadgeData()
    },
    { immediate: true }
)

watch(
    () => props.badgeRefreshKey,
    () => {
        if (!hasInitializedBadgeLoad.value) return
        loadBadgeData()
    }
)
</script>

<style lang="scss" scoped>
.my-service {
    .service-grid,
    .service-list {
        background: linear-gradient(
            180deg,
            var(--cinema-surface-elevated, #FFFFFF) 0%,
            var(--cinema-surface, #F7F0DF) 100%
        );
        border: 1rpx solid var(--cinema-border, rgba(200, 164, 93, 0.24));
        border-radius: 28rpx;
        box-shadow: var(--cinema-shadow-soft, 0 18rpx 44rpx rgba(11, 11, 11, 0.08));
        transition: all 0.2s ease;
    }

    .service-item {
        transition: all 0.2s ease;
        cursor: pointer;

        &:active {
            transform: translateY(2rpx) scale(0.97);
            opacity: 0.86;
        }

        .icon-wrapper {
            transition: all 0.2s ease;
        }

        .count-badge {
            box-shadow: 0 10rpx 20rpx rgba(159, 122, 46, 0.28);
        }
    }

    .service-list-item {
        transition: all 0.2s ease;
        cursor: pointer;

        &:active {
            background-color: var(--cinema-surface, #F7F0DF);
        }

        .count-badge {
            box-shadow: 0 10rpx 20rpx rgba(159, 122, 46, 0.28);
        }
    }
}
</style>
