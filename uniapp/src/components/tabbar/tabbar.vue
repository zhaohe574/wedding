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
            <image
                v-if="resolveIcon(item, index)"
                class="custom-tabbar__icon"
                :src="resolveIcon(item, index)"
                mode="aspectFit"
            />
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
import { computed } from 'vue'
import { useAppStore } from '@/stores/app'
import { navigateTo, normalizeAppPath } from '@/utils/util'

interface TabbarItem {
    iconPath?: string
    selectedIconPath?: string
    text?: string
    link?: Record<string, any>
    pagePath: string
}

const appStore = useAppStore()

const nativeTabbar = [
    '/pages/index/index',
    '/pages/news/news',
    '/pages/staff_list/staff_list',
    '/pages/user/user'
]

const tabbarList = computed<TabbarItem[]>(() => {
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

const currentRoute = computed(() => {
    const currentPages = getCurrentPages()
    const currentPage = currentPages[currentPages.length - 1]
    return normalizeAppPath(currentPage?.route || '')
})

const activeIndex = computed(() => {
    return tabbarList.value.findIndex((item) => item.pagePath === currentRoute.value)
})

const showTabbar = computed(() => activeIndex.value >= 0)

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

const handleChange = (index: number) => {
    const selectTab = tabbarList.value[index]
    if (!selectTab) return
    if (index === activeIndex.value) return

    const navigateType = nativeTabbar.includes(selectTab.pagePath) ? 'switchTab' : 'reLaunch'
    navigateTo(selectTab.link, navigateType)
}
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

    &__icon {
        width: 44rpx;
        height: 44rpx;
        margin-bottom: 6rpx;
    }

    &__text {
        font-size: 22rpx;
        line-height: 1.2;
    }
}
</style>
