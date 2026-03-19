<template>
    <view
        v-if="showTabbar"
        class="custom-tabbar"
        :style="{ paddingBottom: `${safeAreaBottom}px` }"
    >
        <view
            v-for="(item, index) in tabbarList"
            :key="item.pagePath || index"
            class="custom-tabbar__item"
            :class="{ 'custom-tabbar__item--active': activeIndex === index }"
            @click="handleChange(index)"
        >
            <view v-if="resolveIcon(item, index)" class="custom-tabbar__icon-wrap">
                <image
                    class="custom-tabbar__icon"
                    :src="resolveIcon(item, index)"
                    mode="aspectFit"
                />
                <view
                    v-if="item.badgeCount && item.badgeCount > 0"
                    class="custom-tabbar__badge"
                    :style="{ backgroundColor: $theme.ctaColor }"
                >
                    <text class="custom-tabbar__badge-text">
                        {{ item.badgeCount > 99 ? '99+' : item.badgeCount }}
                    </text>
                </view>
            </view>
            <text
                class="custom-tabbar__text"
                :style="{ color: activeIndex === index ? activeColor : inactiveColor }"
            >
                {{ item.text }}
            </text>
        </view>
    </view>
</template>

<script lang="ts" setup>
import { useAppStore } from '@/stores/app'
import { useThemeStore } from '@/stores/theme'
import { useUserStore } from '@/stores/user'
import { loadUserBadgeData } from '@/utils/user-badge'
import { navigateTo, normalizeAppPath } from '@/utils/util'
import { storeToRefs } from 'pinia'
import { computed, ref, watch } from 'vue'

interface TabbarItem {
    iconPath?: string
    selectedIconPath?: string
    text?: string
    link?: Record<string, any>
    pagePath: string
    badgeCount?: number
}

const props = defineProps({
    badgeRefreshKey: {
        type: [Number, String],
        default: 0
    }
})

const appStore = useAppStore()
const $theme = useThemeStore()
const userStore = useUserStore()
const { userInfo, isLogin } = storeToRefs(userStore)
const myBadgeCount = ref(0)
const badgeRequestToken = ref(0)
const hasInitializedBadgeLoad = ref(false)

const USER_TAB_PATH = '/pages/user/user'

const featureSwitch = computed(() => appStore.config?.feature_switch || {})

const rawTabbarList = computed<TabbarItem[]>(() => {
    return (
        appStore.getTabbarConfig
            ?.filter((item: any) => item.is_show == 1)
            .map((item: any) => ({
                iconPath: appStore.getImageUrl(item.unselected || ''),
                selectedIconPath: appStore.getImageUrl(item.selected || ''),
                text: item.name,
                link: item.link,
                pagePath: normalizeAppPath(item.link?.path || '')
            })) || []
    )
})

const tabbarList = computed<TabbarItem[]>(() => {
    return rawTabbarList.value.map((item) => ({
        ...item,
        badgeCount: item.pagePath === USER_TAB_PATH ? myBadgeCount.value : 0
    }))
})

const currentRoute = computed(() => {
    const currentPages = getCurrentPages()
    const currentPage = currentPages[currentPages.length - 1]
    return normalizeAppPath(currentPage?.route || '')
})

const activeIndex = computed(() => {
    return rawTabbarList.value.findIndex((item) => item.pagePath === currentRoute.value)
})

const showTabbar = computed(() => activeIndex.value >= 0)
const myTabIndex = computed(() => rawTabbarList.value.findIndex((item) => item.pagePath === USER_TAB_PATH))
const shouldLoadBadge = computed(() => showTabbar.value && myTabIndex.value >= 0)
const shouldLoadStaffTodo = computed(() => {
    return featureSwitch.value.staff_center === 1 && !!userInfo.value?.is_staff
})

const activeColor = computed(() => appStore.getStyleConfig.selected_color || '#111827')
const inactiveColor = computed(() => appStore.getStyleConfig.default_color || '#98A2B3')

const safeAreaBottom = computed(() => {
    try {
        const systemInfo = uni.getSystemInfoSync()
        const safeArea = systemInfo.safeArea
        if (!safeArea) return 0
        return Math.max(systemInfo.screenHeight - safeArea.bottom, 0)
    } catch (error) {
        return 0
    }
})

const resolveIcon = (item: TabbarItem, index: number) => {
    return activeIndex.value === index ? item.selectedIconPath || item.iconPath : item.iconPath
}

const loadMyBadgeCount = async () => {
    const requestToken = ++badgeRequestToken.value

    if (!shouldLoadBadge.value || !isLogin.value) {
        myBadgeCount.value = 0
        hasInitializedBadgeLoad.value = true
        return
    }

    const result = await loadUserBadgeData({
        loadMessage: true,
        loadStaffTodo: shouldLoadStaffTodo.value
    })

    if (requestToken !== badgeRequestToken.value) {
        hasInitializedBadgeLoad.value = true
        return
    }

    myBadgeCount.value = result.messageCount + result.staffTodoCount
    hasInitializedBadgeLoad.value = true
}

const isSwitchTabUnavailable = (error: any) => {
    const errMsg = String(error?.errMsg || '')
    return /switchTab:fail/i.test(errMsg)
}

const handleChange = (index: number) => {
    const selectTab = tabbarList.value[index]
    if (!selectTab) return
    if (index === activeIndex.value) return

    if (!selectTab.pagePath) {
        navigateTo(selectTab.link, 'reLaunch')
        return
    }

    uni.switchTab({
        url: selectTab.pagePath,
        fail: (error) => {
            if (isSwitchTabUnavailable(error)) {
                navigateTo(selectTab.link, 'reLaunch')
                return
            }

            console.error('底部导航切换失败:', error)
            uni.showToast({
                title: '页面跳转失败，请稍后重试',
                icon: 'none'
            })
        }
    })
}

watch(
    [
        isLogin,
        shouldLoadBadge,
        shouldLoadStaffTodo
    ],
    () => {
        loadMyBadgeCount()
    },
    { immediate: true }
)

watch(
    () => props.badgeRefreshKey,
    () => {
        if (!hasInitializedBadgeLoad.value) return
        loadMyBadgeCount()
    }
)
</script>

<style scoped lang="scss">
.custom-tabbar {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: flex-end;
    min-height: 104rpx;
    background: rgba(255, 255, 255, 0.96);
    backdrop-filter: blur(18rpx);
    border-top: 1rpx solid rgba(15, 23, 42, 0.08);
    box-shadow: 0 -8rpx 28rpx rgba(15, 23, 42, 0.06);
    z-index: 998;

    &__item {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 104rpx;
        padding: 14rpx 0 10rpx;
        box-sizing: border-box;
    }

    &__item--active {
        .custom-tabbar__text {
            font-weight: 600;
        }
    }

    &__icon-wrap {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__icon {
        width: 44rpx;
        height: 44rpx;
        margin-bottom: 6rpx;
    }

    &__badge {
        position: absolute;
        top: -10rpx;
        right: -20rpx;
        min-width: 36rpx;
        height: 36rpx;
        padding: 0 8rpx;
        border-radius: 999rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4rpx 12rpx rgba(249, 115, 22, 0.4);
    }

    &__badge-text {
        font-size: 20rpx;
        line-height: 1;
        color: #ffffff;
        font-weight: 600;
    }

    &__text {
        font-size: 22rpx;
        line-height: 1.2;
    }
}
</style>
