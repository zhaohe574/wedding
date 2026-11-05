<template>
    <view class="tabbar">
        <view
            v-for="item in list"
            :key="item.path"
            class="tabbar__item"
            :class="{ 'is-active': item.path === current }"
            :aria-label="ariaOf(item)"
            aria-role="tab"
            :aria-selected="item.path === current"
            hover-class="tabbar__item--hover"
            :hover-stay-time="80"
            @tap="go(item)"
        >
            <view class="tabbar__icon">
                <u-icon
                    :name="item.path === current ? item.activeIcon : item.icon"
                    :size="48"
                    :color="item.path === current ? 'var(--c-brand-text)' : 'var(--c-ink-3)'"
                />
                <view v-if="badgeOf(item.path)" class="tabbar__badge">
                    {{ badgeOf(item.path) > 99 ? '99+' : badgeOf(item.path) }}
                </view>
            </view>
            <text class="tabbar__text">{{ item.text }}</text>
        </view>
    </view>
</template>

<script setup lang="ts">
/**
 * 固定 4 tab 导航。
 *
 * 不用后端装修下发的 tabbar：后端配错就整条导航消失，且 4 个 tab 是产品结构，
 * 不该由运营配置驱动。图标用 vk-uview 内置 iconfont，避免 PNG 资源占主包体积。
 */
import { onMounted } from 'vue'
import { useBadgeStore } from '@/stores/badge'

interface TabItem {
    path: string
    text: string
    icon: string
    activeIcon: string
}

const props = defineProps<{ current: string }>()

const badge = useBadgeStore()

const list: TabItem[] = [
    { path: '/pages/index/index', text: '首页', icon: 'home', activeIcon: 'home-fill' },
    { path: '/pages/order/order', text: '订单', icon: 'file-text', activeIcon: 'file-text-fill' },
    { path: '/pages/message/message', text: '消息', icon: 'chat', activeIcon: 'chat-fill' },
    { path: '/pages/user/user', text: '我的', icon: 'account', activeIcon: 'account-fill' }
]

/** 消息与订单的角标来源不同，其余 tab 不显示 */
function badgeOf(path: string) {
    if (path === '/pages/message/message') return badge.message
    if (path === '/pages/order/order') return badge.order
    return 0
}

/** 读屏标签含角标条数与选中态，不只靠颜色（03 §3.1 C1 / AC-B-08） */
function ariaOf(item: TabItem) {
    const n = badgeOf(item.path)
    const badgeText = n ? `，${n > 99 ? '99+' : n} 条未读` : ''
    const selectedText = item.path === props.current ? '，当前选中' : ''
    return `${item.text}${badgeText}${selectedText}`
}

function go(item: TabItem) {
    if (item.path === props.current) return
    uni.switchTab({ url: item.path })
}

onMounted(() => {
    // 组件在每个 tab 页各挂载一次，配合 store 内的 30 秒节流，切 tab 不会放大请求
    badge.refresh()
})
</script>

<style lang="scss" scoped>
.tabbar {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 100;
    display: flex;
    background: var(--c-surface);
    border-top: 1rpx solid var(--c-line);
    padding-bottom: var(--safe-bottom);

    &__item {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: var(--sp-1);   /* 4rpx → 8rpx，图标↔文字（视觉规范 §6.5 / §7.2） */
        height: 100rpx;

        &--hover {
            background: var(--c-surface-2);
        }
    }

    &__icon {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__badge {
        position: absolute;
        top: -6rpx;
        left: 50%;
        margin-left: 10rpx;
        min-width: 28rpx;
        height: 28rpx;
        padding: 0 6rpx;
        border-radius: var(--r-pill);
        background: var(--c-danger);
        color: var(--c-ink-inverse);
        font-size: 20rpx;        /* 18rpx → 20rpx（数字角标白名单，AC-A-06 已登记） */
        line-height: 28rpx;
        text-align: center;
        box-sizing: border-box;
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        line-height: var(--lh-none);
    }

    &__item.is-active &__text {
        color: var(--c-brand-text);
        font-weight: var(--fw-medium);
    }
}
</style>
