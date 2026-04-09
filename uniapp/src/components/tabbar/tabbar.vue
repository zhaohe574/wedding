<template>
    <view v-if="showTabbar" class="custom-tabbar">
        <view class="custom-tabbar__pill">
            <view
                v-for="(item, index) in tabbarList"
                :key="item.pagePath"
                class="custom-tabbar__item"
                :class="{ 'custom-tabbar__item--active': activeIndex === index }"
                @click="handleChange(index)"
            >
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
import { useUserStore } from '@/stores/user'
import { loadUserBadgeData } from '@/utils/user-badge'
import { normalizeAppPath } from '@/utils/util'
import { storeToRefs } from 'pinia'
import { computed, ref, watch } from 'vue'

interface TabbarItem {
    text: string
    pagePath: string
    badgeCount?: number
}

const props = defineProps({
    badgeRefreshKey: {
        type: [Number, String],
        default: 0
    }
})

const userStore = useUserStore()
const { isLogin } = storeToRefs(userStore)

const myBadgeCount = ref(0)
const badgeRequestToken = ref(0)
const hasInitializedBadgeLoad = ref(false)

const USER_TAB_PATH = '/pages/user/user'

const baseTabbarList = computed<TabbarItem[]>(() => [
    { text: '首页', pagePath: '/pages/index/index' },
    { text: '动态', pagePath: '/pages/dynamic/dynamic' },
    { text: '我的', pagePath: USER_TAB_PATH }
])

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

    uni.switchTab({
        url: target.pagePath,
        fail: (error) => {
            console.error('底部导航切换失败：', error)
            uni.reLaunch({ url: target.pagePath })
        }
    })
}

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
}

.custom-tabbar__pill {
    display: flex;
    align-items: center;
    gap: var(--wm-tabbar-pill-gap, 8rpx);
    padding: var(--wm-tabbar-pill-padding, 8rpx);
    min-height: var(--wm-tabbar-pill-height, 116rpx);
    border-radius: var(--wm-tabbar-pill-radius, var(--wm-radius-tabbar-shell, 64rpx));
    background: rgba(255, 255, 255, 0.84);
    backdrop-filter: blur(24rpx);
    -webkit-backdrop-filter: blur(24rpx);
    border: var(--wm-tabbar-border-width, 2rpx) solid var(--wm-color-border, #efe6e1);
    box-shadow: var(--wm-shadow-soft, 0 14rpx 32rpx rgba(214, 185, 167, 0.16));
    box-sizing: border-box;
}

.custom-tabbar__item {
    position: relative;
    flex: 1;
    min-height: var(--wm-tabbar-item-height, 108rpx);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--wm-tabbar-item-radius, var(--wm-radius-tabbar-item, 56rpx));
    transition: all var(--wm-motion-base, 220ms) ease;
}

.custom-tabbar__item--active {
    background: var(--wm-color-primary, #e85a4f);
    box-shadow: 0 14rpx 30rpx rgba(232, 90, 79, 0.2);
}

.custom-tabbar__text {
    font-size: var(--wm-tabbar-text-size, 22rpx);
    line-height: 1;
    font-weight: 700;
    color: var(--wm-text-secondary, #7f7b78);
}

.custom-tabbar__item--active .custom-tabbar__text {
    color: #ffffff;
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
    background: var(--wm-color-primary, #e85a4f);
    border: 2rpx solid rgba(255, 255, 255, 0.9);
}

.custom-tabbar__badge-text {
    font-size: 19rpx;
    line-height: 1;
    font-weight: 700;
    color: #ffffff;
}
</style>
