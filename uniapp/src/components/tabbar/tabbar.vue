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
                <tn-icon
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

const activeColor = computed(() => appStore.getStyleConfig.selected_color || '#0B0B0B')
const inactiveColor = computed(() => appStore.getStyleConfig.default_color || '#8E887D')
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
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    padding-top: var(--wm-tabbar-padding-top, 22rpx);
    padding-left: var(--wm-tabbar-padding-x, 42rpx);
    padding-right: var(--wm-tabbar-padding-x, 42rpx);
    padding-bottom: calc(
        var(--wm-safe-bottom-tabbar, calc(177rpx + env(safe-area-inset-bottom))) -
            var(--wm-tabbar-pill-height, 116rpx) - var(--wm-tabbar-padding-top, 22rpx)
    );
    z-index: 998;
    box-sizing: border-box;
    background: #ffffff;
    border-top: 1rpx solid rgba(11, 11, 11, 0.08);
}

.custom-tabbar__pill {
    display: flex;
    align-items: center;
    gap: var(--wm-tabbar-pill-gap, 8rpx);
    padding: var(--wm-tabbar-pill-padding, 8rpx);
    min-height: var(--wm-tabbar-pill-height, 116rpx);
    border-radius: 0;
    background: #ffffff;
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
    border: none;
    box-shadow: none;
    box-sizing: border-box;
}

.custom-tabbar__item {
    position: relative;
    flex: 1;
    min-height: var(--wm-tabbar-item-height, 108rpx);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6rpx;
    border-radius: var(--wm-tabbar-item-radius, var(--wm-radius-tabbar-item, 56rpx));
    transition: all var(--wm-motion-base, 220ms) ease;
    padding: 0 12rpx;
}

.custom-tabbar__item--active {
    background: transparent;
    box-shadow: none;
}

.custom-tabbar__text {
    font-size: var(--wm-tabbar-text-size, 22rpx);
    line-height: 1.2;
    font-weight: 700;
    color: var(--wm-tabbar-inactive, #8e887d);
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
    color: var(--wm-tabbar-active, #0b0b0b);
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
    background: var(--wm-color-danger, #8a4b45);
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
