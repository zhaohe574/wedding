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
                <view
                    v-if="item.badgeCount && item.badgeCount > 0"
                    class="custom-tabbar__badge"
                >
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

const appStore = useAppStore()
const userStore = useUserStore()
const { userInfo, isLogin } = storeToRefs(userStore)

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
const featureSwitch = computed(() => appStore.config?.feature_switch || {})
const shouldLoadBadge = computed(() => showTabbar.value)
const shouldLoadStaffTodo = computed(() => {
    return featureSwitch.value.staff_center === 1 && !!userInfo.value?.is_staff
})

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

watch([isLogin, shouldLoadBadge, shouldLoadStaffTodo], loadMyBadgeCount, {
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
    padding: 12rpx 21rpx 6rpx;
    padding-bottom: calc(8rpx + constant(safe-area-inset-bottom));
    padding-bottom: calc(8rpx + env(safe-area-inset-bottom));
    z-index: 998;
}

.custom-tabbar__pill {
    display: flex;
    align-items: center;
    padding: 4rpx;
    min-height: 108rpx;
    border-radius: 32rpx;
    background: rgba(255, 255, 255, 0.84);
    backdrop-filter: blur(24rpx);
    -webkit-backdrop-filter: blur(24rpx);
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    box-shadow: var(--wm-shadow-soft, 0 14rpx 32rpx rgba(214, 185, 167, 0.16));
}

.custom-tabbar__item {
    position: relative;
    flex: 1;
    min-height: 96rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 24rpx;
    transition: all var(--wm-motion-base, 220ms) ease;
}

.custom-tabbar__item--active {
    background: var(--wm-color-primary, #e85a4f);
    box-shadow: 0 14rpx 30rpx rgba(232, 90, 79, 0.2);
}

.custom-tabbar__text {
    font-size: 22rpx;
    line-height: 1;
    font-weight: 600;
    color: var(--wm-text-secondary, #7f7b78);
}

.custom-tabbar__item--active .custom-tabbar__text {
    color: #ffffff;
}

.custom-tabbar__badge {
    position: absolute;
    top: 14rpx;
    right: 20rpx;
    min-width: 34rpx;
    height: 34rpx;
    padding: 0 8rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 999rpx;
    background: var(--wm-color-primary, #e85a4f);
    border: 2rpx solid rgba(255, 255, 255, 0.9);
}

.custom-tabbar__badge-text {
    font-size: 18rpx;
    line-height: 1;
    font-weight: 700;
    color: #ffffff;
}
</style>
