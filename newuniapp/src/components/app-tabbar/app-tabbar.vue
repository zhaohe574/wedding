<template>
    <view class="tabbar" :class="[`tabbar--${variant}`]" aria-role="tablist">
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
 * 角标：设计稿 4 屏 tabbar 均无角标，故本版移除（badgeStore 保留未动）。
 * 未读消息数应显示在「我的 → 消息」入口，由 user 页触发 badgeStore.refresh()。
 */
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

/** 读屏标签带选中态：选中不只靠颜色区分 */
function ariaOf(item: TabItem) {
    return `${item.text}${item.path === props.current ? '，当前选中' : ''}`
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
    padding: 20rpx 28rpx 0; /* 设计稿 10px 14px 0 */
    padding-bottom: calc(8rpx + var(--safe-bottom));

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
        font-size: var(--fs-xl); /* 38rpx ≈ 设计稿 19px×1.923 */
        line-height: var(--lh-none);
        color: var(--c-ink-3);
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
    }
}
</style>
