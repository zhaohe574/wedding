<template>
    <view class="tabbar" :class="[`tabbar--${variant}`]" :style="tabbarStyle" aria-role="tablist">
        <view class="tabbar__pill">
            <view
                v-for="item in list"
                :key="item.path"
                class="tabbar__tab"
                :class="{ 'is-active': item.path === current }"
                aria-role="tab"
                :aria-selected="item.path === current"
                :aria-label="ariaOf(item)"
                hover-class="tabbar__tab--hover"
                :hover-stay-time="80"
                @tap="go(item)"
            >
                <view class="tabbar__icon" aria-hidden="true">
                    <tn-icon :name="item.tnIcon" />
                    <view v-if="badgeOf(item) > 0" class="tabbar__badge">
                        <text class="tabbar__badge-text">{{
                            badgeOf(item) > 99 ? '99+' : badgeOf(item)
                        }}</text>
                    </view>
                </view>
                <text class="tabbar__label">{{ item.text }}</text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
/**
 * 四段 tabBar（设计稿 1988–1997 行）。
 *
 * 结构：悬浮胶囊 + 选中项整块墨黑圆角块 + 香槟色图标。
 * 硬约束（改了就会白屏或错位，别动）：
 *  - 4 个 tab 的页面都必须在**主包**（微信规定 tabBar 页不能进分包）；
 *  - 切换只用 uni.switchTab；tab 页之间不存在 navigateTo，页面栈不增长；
 *  - 本组件只做导航，不持业务状态；tab 内筛选条件放 pinia（见 docs/redesign/v2/00-工程底座.md）。
 *
 * 角标：「我的」Tab 聚合展示待支付订单 + 未读消息 + 服务人员工作台待办（badgeStore.mineCount），
 * 其余三个 tab 不展示。数字超过 99 显示 99+，与订单格角标口径一致。
 */
import { computed, onMounted, watch } from 'vue'
import { getDeviceInfo } from '@/platform/device'
import { useBadgeStore } from '@/stores/badge'
import { useUserStore } from '@/stores/user'

interface TabItem {
    path: string
    text: string
    /**
     * 图鸟内置图标名（D6：全站只用一个图标族 tn-icon，不用自绘 SVG / 第二套 iconfont）。
     * 字段名刻意带 tn 前缀：scripts/check-pages.mjs 会把名字叫 icon 的对象字面量当成
     * uview 图标映射表逐个校验，而 my / moments 这类名字只存在于图鸟图标集，会被误报。
     */
    tnIcon: string
}

const props = withDefaults(
    defineProps<{
        /** 当前页路径，由 page-shell 透传 */
        current: string
        /** default = 米白渐变背板；on-image = 压在首屏主图上的透明背板 */
        variant?: 'default' | 'on-image'
    }>(),
    { variant: 'default' }
)

/**
 * 真机 JS 侧安全区底部：env(safe-area-inset-bottom) / 嵌套 calc() 在部分真机失效，
 * 取到值时行内补 padding-bottom（基础留白 16rpx 换算 px + 真实安全区）。
 */
const safeBottomPx = Math.max(0, Number(getDeviceInfo().safeAreaBottom) || 0)
const tabbarStyle = computed<Record<string, string> | undefined>(() =>
    safeBottomPx > 0
        ? { paddingBottom: `${Math.round(uni.upx2px(16)) + safeBottomPx}px` }
        : undefined
)

/**
 * 图标统一用 tn-icon（图鸟 iconfont，已 base64 内联进 app.wxss）。
 * 选中态不换图标，只换颜色（--c-ink-3 → --c-brand-soft）+ 外层墨黑胶囊，
 * 这样四个 tab 的图形语言一致，也不需要维护两套字号。
 */
const list: TabItem[] = [
    { path: '/pages/index/index', text: '首页', tnIcon: 'home' },
    { path: '/pages/feed/feed', text: '动态', tnIcon: 'moments' },
    { path: '/pages/query/query', text: '查档期', tnIcon: 'calendar' },
    { path: '/pages/user/user', text: '我的', tnIcon: 'my' }
]

const badgeStore = useBadgeStore()
const userStore = useUserStore()

/** tabbar 在每个 tab 页各挂一个；首帧先取一次，之后靠各页 onShow 的 refresh（带节流）维护 */
onMounted(() => {
    if (userStore.isLogin) badgeStore.refresh()
    else badgeStore.clear()
})

/** 登录 / 登出（含 401 被动登出）时立即同步：登入强制回源，登出清空 */
watch(
    () => userStore.isLogin,
    (loggedIn) => {
        if (loggedIn) badgeStore.refresh(true)
        else badgeStore.clear()
    }
)

/** 读屏标签带选中态与角标数：选中、未读都不能只靠颜色区分 */
function ariaOf(item: TabItem) {
    const n = badgeOf(item)
    const unread = n > 0 ? `，${n} 条待处理或未读` : ''
    return `${item.text}${item.path === props.current ? '，当前选中' : ''}${unread}`
}

/**
 * 只有「我的」Tab 有角标：待支付订单、未读消息与工作台待办聚合在同一个数字里。
 * 返回 0 时模板不渲染角标，读屏也不播报多余的 0。
 */
function badgeOf(item: TabItem): number {
    return item.path === '/pages/user/user' ? badgeStore.mineCount : 0
}

function go(item: TabItem) {
    if (item.path === props.current) return
    uni.switchTab({ url: item.path })
}
</script>

<style lang="scss" scoped>
.tabbar {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 100; /* 高于内容与吸底栏(90)，低于弹层/toast */
    padding: 20rpx 28rpx 16rpx; /* 设计稿 10px 14px + 底部基础留白 8px */
    padding-bottom: calc(16rpx + var(--safe-bottom));

    &--default {
        /* 渐变与 --c-bg 同源（rgba(var(--c-bg-rgb), α)），避免在组件里写第二遍米白 */
        background: linear-gradient(
            180deg,
            rgba(var(--c-bg-rgb), 0) 0%,
            rgba(var(--c-bg-rgb), 0.86) 34%,
            rgba(var(--c-bg-rgb), 0.98) 100%
        );
    }

    /* 首屏主图版本：主图是深色，胶囊自身仍实底，只把背板变透明 */
    &--on-image {
        background: transparent;
    }

    /* 胶囊：112rpx 高、整圆角（设计稿 32px 半径对 58px 高，本质是 pill）、1rpx 描边 + 唯一允许的浮层阴影 */
    &__pill {
        height: 112rpx;
        border-radius: var(--r-pill);
        background: rgba(255, 255, 255, 0.94);
        border: 1rpx solid var(--c-line-strong);
        box-shadow: var(--shadow-float);
        display: flex;
        align-items: center;
        padding: 8rpx;
        gap: 4rpx;
        box-sizing: border-box;
    }

    &__tab {
        flex: 1;
        height: 96rpx;
        border-radius: var(--r-pill); /* 设计稿 26px 半径对 50px 高，同样是 pill */
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6rpx;
        transition: background-color var(--dur-base) var(--ease-out);

        &--hover {
            opacity: 0.72;
        }
    }

    /* 图标：37rpx（设计稿 19px×1.923）；tn-icon 的 font-size 是 inherit，颜色也随父级 */
    &__icon {
        position: relative;
        font-size: var(--fs-xl); /* 38rpx ≈ 设计稿 19px×1.923 */
        line-height: var(--lh-none);
        color: var(--c-ink-3);
    }

    /* 角标：压在图标右上，左偏移 8rpx 让数字尽量落在图标外沿而不是压住字形。
       2rpx 描边默认为胶囊底（--c-surface），在未选中态的米白背板上切得开；
       选中态整块变墨黑，描边改跟 --c-ink（见下方 is-active）。 */
    &__badge {
        position: absolute;
        top: -10rpx;
        left: 50%;
        margin-left: 8rpx;
        min-width: 30rpx;
        height: 30rpx;
        padding: 0 8rpx;
        box-sizing: border-box;
        border-radius: var(--r-pill);
        background: var(--c-danger);
        border: 2rpx solid var(--c-surface);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__badge-text {
        font-size: var(--fs-20);
        font-weight: var(--fw-semibold);
        color: var(--c-ink-inverse);
        line-height: var(--lh-none);
    }

    &__label {
        /* 设计稿 10px；低于全站最小字号 24rpx，这里取 --fs-xs 保可读 */
        font-size: var(--fs-xs);
        font-weight: var(--fw-medium);
        letter-spacing: 0.04em;
        line-height: var(--lh-none);
        color: var(--c-ink-3);
    }

    /* 选中态：整块墨黑胶囊 + 香槟字（设计 .tab.is-active） */
    &__tab.is-active {
        background: var(--c-ink);

        .tabbar__icon {
            color: var(
                --c-brand-soft
            ); /* 香槟色图标（设计 .tab.is-active .ic{stroke:var(--gold-2)}） */
        }

        .tabbar__label {
            color: var(--c-brand-bg); /* 设计稿取色 F6EDE0，与 --c-brand-bg 同值 */
            font-weight: var(--fw-semibold);
        }

        /* 选中块是墨黑底，角标描边必须跟着变深，否则白边在深底上像缺口 */
        .tabbar__badge {
            border-color: var(--c-ink);
        }
    }
}
</style>
