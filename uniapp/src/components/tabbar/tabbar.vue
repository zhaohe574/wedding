<template>
    <view v-if="showTabbar" class="custom-tabbar" :style="tabbarStyle">
        <view class="custom-tabbar__pill">
            <view
                v-for="(item, index) in tabbarList"
                :key="item.pagePath"
                class="custom-tabbar__item"
                :class="{ 'custom-tabbar__item--active': activeIndex === index }"
                @click="handleChange(index)"
            >
                <image
                    v-if="getIconSource(item, activeIndex === index)"
                    class="custom-tabbar__icon-image"
                    :src="getIconSource(item, activeIndex === index)"
                    mode="aspectFit"
                />
                <BaseIcon
                    v-else
                    :name="activeIndex === index ? item.fallbackSelectedIcon : item.fallbackIcon"
                    :size="34"
                    :color="activeIndex === index ? activeColor : inactiveColor"
                    class="custom-tabbar__icon"
                />
                <text class="custom-tabbar__text">{{ item.text }}</text>
                <view v-if="item.badgeCount && item.badgeCount > 0" class="custom-tabbar__badge">
                    <text class="custom-tabbar__badge-text">
                        {{ item.badgeCount > 99 ? '99+' : item.badgeCount }}
                    </text>
                </view>
            </view>
        </view>
    </view>
</template>

<script lang="ts" setup>
import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'
import { loadUserBadgeData } from '@/utils/user-badge'
import { navigateTo, normalizeAppPath, resolveAppLink } from '@/utils/util'
import { storeToRefs } from 'pinia'
import { computed, onMounted, ref, watch } from 'vue'

type AppLink = Record<string, any> | string | null | undefined

interface TabbarItem {
    text: string
    pagePath: string
    iconPath: string
    selectedIconPath: string
    fallbackIcon: string
    fallbackSelectedIcon: string
    link?: AppLink
    badgeCount?: number
}

const props = defineProps({
    badgeRefreshKey: {
        type: [Number, String],
        default: 0
    }
})

const appStore = useAppStore()
const userStore = useUserStore()
const { isLogin } = storeToRefs(userStore)

const myBadgeCount = ref(0)
const badgeRequestToken = ref(0)
const hasInitializedBadgeLoad = ref(false)

const USER_TAB_PATH = '/pages/user/user'
const NATIVE_TABBAR_PATHS = new Set(['/pages/index/index', '/pages/dynamic/dynamic', USER_TAB_PATH])
const FALLBACK_TABBAR_CONFIG = [
    {
        name: '首页',
        link: { path: '/pages/index/index', name: '首页', type: 'shop', canTab: true },
        selected: '',
        unselected: '',
        is_show: 1
    },
    {
        name: '动态',
        link: { path: '/pages/dynamic/dynamic', name: '动态', type: 'shop', canTab: true },
        selected: '',
        unselected: '',
        is_show: 1
    },
    {
        name: '我的',
        link: { path: USER_TAB_PATH, name: '我的', type: 'shop', canTab: true },
        selected: '',
        unselected: '',
        is_show: 1
    }
]
const FALLBACK_ICON_BY_PATH: Record<string, { icon: string; selectedIcon: string }> = {
    '/pages/index/index': { icon: 'home', selectedIcon: 'home-fill' },
    '/pages/news/news': { icon: 'news', selectedIcon: 'news-fill' },
    '/pages/dynamic/dynamic': { icon: 'news', selectedIcon: 'news-fill' },
    '/pages/staff_list/staff_list': { icon: 'team', selectedIcon: 'team-fill' },
    '/pages/order/order': { icon: 'order', selectedIcon: 'order-fill' },
    [USER_TAB_PATH]: { icon: 'user', selectedIcon: 'user-fill' }
}
const DEFAULT_FALLBACK_ICON = { icon: 'menu-circle', selectedIcon: 'menu-circle-fill' }

const configuredTabbarList = computed<any[]>(() => {
    const list = appStore.getTabbarConfig
    return Array.isArray(list) && list.length ? list : FALLBACK_TABBAR_CONFIG
})

const activeColor = computed(() => appStore.getStyleConfig.selected_color || '#191713')
const inactiveColor = computed(() => appStore.getStyleConfig.default_color || '#8A806F')
const tabbarStyle = computed(() => ({
    '--wm-tabbar-active': activeColor.value,
    '--wm-tabbar-inactive': inactiveColor.value
}))

const baseTabbarList = computed<TabbarItem[]>(() =>
    configuredTabbarList.value
        .filter((item) => String(item?.is_show ?? 1) !== '0')
        .map((item, index) => {
            const resolvedLink = resolveAppLink(item?.link || {})
            const pagePath = resolvedLink?.path || ''
            if (!pagePath) {
                return null
            }

            const fallbackIcon = FALLBACK_ICON_BY_PATH[pagePath] || DEFAULT_FALLBACK_ICON
            return {
                text: item?.name || item?.link?.name || `导航${index + 1}`,
                pagePath,
                iconPath: appStore.getImageUrl(item?.unselected || ''),
                selectedIconPath: appStore.getImageUrl(item?.selected || ''),
                fallbackIcon: fallbackIcon.icon,
                fallbackSelectedIcon: fallbackIcon.selectedIcon,
                link: item?.link || { path: pagePath, type: 'shop' }
            } as TabbarItem
        })
        .filter((item): item is TabbarItem => !!item)
)

const tabbarList = computed<TabbarItem[]>(() =>
    baseTabbarList.value.map((item) => ({
        ...item,
        badgeCount: item.pagePath === USER_TAB_PATH ? myBadgeCount.value : 0
    }))
)

const currentRoute = computed(() => {
    const currentPages = getCurrentPages()
    const currentPage = currentPages[currentPages.length - 1]
    return normalizeAppPath(currentPage?.route || '')
})

const activeIndex = computed(() => {
    return baseTabbarList.value.findIndex((item) => item.pagePath === currentRoute.value)
})

const showTabbar = computed(() => activeIndex.value >= 0)
const shouldLoadBadge = computed(() => showTabbar.value)

const getIconSource = (item: TabbarItem, active: boolean) => {
    return active ? item.selectedIconPath || item.iconPath : item.iconPath || item.selectedIconPath
}

const loadMyBadgeCount = async () => {
    const requestToken = ++badgeRequestToken.value

    if (!shouldLoadBadge.value || !isLogin.value) {
        myBadgeCount.value = 0
        hasInitializedBadgeLoad.value = true
        return
    }

    const result = await loadUserBadgeData({
        loadMessage: true
    })

    if (requestToken !== badgeRequestToken.value) {
        hasInitializedBadgeLoad.value = true
        return
    }

    myBadgeCount.value = result.messageCount
    hasInitializedBadgeLoad.value = true
}

const handleChange = (index: number) => {
    const target = tabbarList.value[index]
    if (!target || index === activeIndex.value) {
        return
    }

    const navigateType = NATIVE_TABBAR_PATHS.has(target.pagePath) ? 'switchTab' : 'reLaunch'
    navigateTo(target.link || { path: target.pagePath, type: 'shop' }, navigateType)
}

onMounted(() => {
    appStore.getConfig()
})

watch([isLogin, shouldLoadBadge], loadMyBadgeCount, {
    immediate: true
})

watch(
    () => props.badgeRefreshKey,
    () => {
        if (!hasInitializedBadgeLoad.value) {
            return
        }

        loadMyBadgeCount()
    }
)
</script>

<style scoped lang="scss">
.custom-tabbar {
    --custom-tabbar-padding-top: 16rpx;
    --custom-tabbar-padding-x: 24rpx;
    --custom-tabbar-pill-height: 144rpx;
    --custom-tabbar-pill-padding: 16rpx;
    --custom-tabbar-pill-gap: 8rpx;
    --custom-tabbar-item-height: 112rpx;
    --custom-tabbar-pill-radius: 72rpx;
    --custom-tabbar-item-radius: 56rpx;
    --custom-tabbar-border-width: 1rpx;
    --custom-tabbar-shell-bg: linear-gradient(180deg, rgba(245, 241, 232, 0) 0%, rgba(245, 241, 232, 0.94) 46%, rgba(245, 241, 232, 0.98) 100%);
    --custom-tabbar-pill-bg: #191713;
    --custom-tabbar-active-bg: #F1E5C8;
    --custom-tabbar-border-color: #D9BE82;
    --custom-tabbar-shadow: 0 20rpx 44rpx rgba(74, 43, 24, 0.18);
    --custom-tabbar-text-size: 22rpx;
    --custom-tabbar-text-color: #8A806F;
    --custom-tabbar-text-active-color: #191713;
    --custom-tabbar-badge-bg: #9A6B35;

    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    padding-top: var(--custom-tabbar-padding-top);
    padding-left: var(--custom-tabbar-padding-x);
    padding-right: var(--custom-tabbar-padding-x);
    padding-bottom: calc(
        164rpx + env(safe-area-inset-bottom) - var(--custom-tabbar-pill-height) -
            var(--custom-tabbar-padding-top)
    );
    z-index: 998;
    box-sizing: border-box;
    background: var(--custom-tabbar-shell-bg);
    border-top: none;
}

.custom-tabbar__pill {
    display: flex;
    align-items: center;
    gap: var(--custom-tabbar-pill-gap);
    padding: var(--custom-tabbar-pill-padding);
    height: var(--custom-tabbar-pill-height);
    border-radius: var(--custom-tabbar-pill-radius);
    background: var(--custom-tabbar-pill-bg);
    backdrop-filter: blur(18rpx);
    -webkit-backdrop-filter: blur(18rpx);
    border: var(--custom-tabbar-border-width) solid var(--custom-tabbar-border-color);
    box-shadow: var(--custom-tabbar-shadow);
    box-sizing: border-box;
}

.custom-tabbar__item {
    position: relative;
    flex: 1 1 0;
    min-width: 0;
    height: var(--custom-tabbar-item-height);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6rpx;
    border-radius: var(--custom-tabbar-item-radius);
    transition: all var(--wm-motion-base, 220ms) ease;
    padding: 0 12rpx;
    box-sizing: border-box;
}

.custom-tabbar__item--active {
    background: var(--custom-tabbar-active-bg);
    box-shadow: none;
}

.custom-tabbar__text {
    font-size: var(--custom-tabbar-text-size);
    line-height: 1.2;
    font-weight: 600;
    color: var(--custom-tabbar-text-color);
    letter-spacing: 0;
}

.custom-tabbar__icon {
    line-height: 1;
}

.custom-tabbar__icon-image {
    width: 38rpx;
    height: 38rpx;
    display: block;
}

.custom-tabbar__item--active .custom-tabbar__text {
    color: var(--custom-tabbar-text-active-color);
    font-weight: 800;
}

.custom-tabbar__badge {
    position: absolute;
    top: 15rpx;
    right: 22rpx;
    min-width: 41rpx;
    height: 41rpx;
    padding: 0 11rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 999rpx;
    background: var(--custom-tabbar-badge-bg);
    border: 2rpx solid rgba(255, 255, 255, 0.9);
}

.custom-tabbar__badge-text {
    font-size: 19rpx;
    line-height: 1;
    font-weight: 700;
    color: #ffffff;
}

/* #ifdef MP-WEIXIN */
.custom-tabbar__pill {
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
}
/* #endif */
</style>
