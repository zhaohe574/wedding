<template>
    <view class="user-page">
        <view class="hero" :style="{ paddingTop: navHeight + 'px' }">
            <view class="hero__bg" />
            <nav-bar title="" transparent :show-back="false" />
            <view class="hero__body">
                <image class="hero__avatar" :src="avatar" mode="aspectFill" />
                <view hover-class="is-pressed" class="hero__info" @tap="handleProfileTap">
                    <text class="hero__name">{{ isLogin ? nickname : '点击登录' }}</text>
                    <text class="hero__sub">{{ isLogin ? maskedMobile : '登录后查看订单与档期' }}</text>
                </view>
                <view hover-class="is-pressed" v-if="isLogin" class="hero__setting" @tap="goSettings">
                    <u-icon name="setting" :size="40" color="var(--c-ink-2)" />
                </view>
            </view>

            <view v-if="isLogin && weddingDate" class="countdown">
                <view class="countdown__left">
                    <text class="countdown__label">距婚礼</text>
                    <text class="countdown__date">{{ weddingDate }}</text>
                </view>
                <view class="countdown__right">
                    <text class="countdown__num">{{ daysRemaining }}</text>
                    <text class="countdown__unit">天</text>
                </view>
            </view>
        </view>

        <view class="page-pad body">
            <base-card>
                <view class="card-head">
                    <text class="card-head__title">我的订单</text>
                    <view hover-class="is-pressed" class="card-head__more" @tap="goOrders()">
                        <text class="card-head__more-text">全部</text>
                        <u-icon name="arrow-right" :size="24" color="var(--c-ink-3)" />
                    </view>
                </view>
                <view class="quick">
                    <view hover-class="is-pressed" v-for="q in quickEntries" :key="q.key" class="quick__item" @tap="goOrders(q.status)">
                        <view class="quick__icon">
                            <u-icon :name="q.icon" :size="40" color="var(--c-brand)" />
                            <view v-if="q.count > 0" class="quick__badge">{{ q.count > 99 ? '99+' : q.count }}</view>
                        </view>
                        <text class="quick__text">{{ q.label }}</text>
                    </view>
                </view>
            </base-card>

            <base-card v-if="isLogin && todoCount > 0">
                <view class="card-head">
                    <text class="card-head__title">待处理</text>
                </view>
                <view class="todo">
                    <view hover-class="is-pressed"
                        v-for="t in todos"
                        :key="t.key"
                        class="todo__item"
                        :class="{ 'is-disabled': t.count === 0 }"
                        @tap="t.count > 0 && navigate({ path: t.path })"
                    >
                        <text class="todo__label">{{ t.label }}</text>
                        <text class="todo__count">{{ t.count }}</text>
                    </view>
                </view>
            </base-card>

            <base-card v-if="staffMenus.length" :flush="true">
                <view class="card-head card-head--pad">
                    <text class="card-head__title">服务人员中心</text>
                </view>
                <view hover-class="is-pressed"
                    v-for="(item, i) in staffMenus"
                    :key="item.key"
                    class="menu"
                    :class="{ 'menu--last': i === staffMenus.length - 1 }"
                    @tap="navigate({ path: item.path })"
                >
                    <u-icon :name="item.icon" :size="32" color="var(--c-ink-2)" />
                    <text class="menu__label">{{ item.label }}</text>
                    <u-icon name="arrow-right" :size="24" color="var(--c-ink-3)" />
                </view>
            </base-card>

            <base-card :flush="true">
                <view hover-class="is-pressed"
                    v-for="(item, i) in menus"
                    :key="item.key"
                    class="menu"
                    :class="{ 'menu--last': i === menus.length - 1 }"
                    @tap="handleMenu(item)"
                >
                    <u-icon :name="item.icon" :size="32" color="var(--c-ink-2)" />
                    <text class="menu__label">{{ item.label }}</text>
                    <u-icon name="arrow-right" :size="24" color="var(--c-ink-3)" />
                </view>
            </base-card>

            <view v-if="isLogin" class="logout">
                <base-button text="退出登录" variant="ghost" size="md" @click="handleLogout" />
            </view>
        </view>

        <view class="user-page__tab-space" />
        <app-tabbar current="/pages/user/user" />
    </view>
</template>

<script setup lang="ts">
/**
 * 我的。
 *
 * 数据来自 /api/v2/me（1 次请求替代老端的 8 次）；接口未上线时降级到 /user/center。
 * 未登录时展示登录引导，不弹窗、不强制跳转。
 */
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import type { MeResponse } from '@shared/contracts'
import { getMe, getUserCenter } from '@/api/user'
import { logout as logoutApi } from '@/api/auth'
import { useUserStore } from '@/stores/user'
import { navigate } from '@/utils/link'
import { getNavBarHeight } from '@/platform/device'
import { report } from '@/platform/report'

const userStore = useUserStore()

/** 透明导航是 fixed 的，内容必须自己让出状态栏 + 44px，否则头像行会顶到刘海下面 */
const navHeight = getNavBarHeight()
const isLogin = computed(() => userStore.isLogin)

const me = ref<Partial<MeResponse>>({})
const fallbackUser = ref<any>(null)

const nickname = computed(() => me.value.user?.nickname || fallbackUser.value?.nickname || '微信用户')
const avatar = computed(
    () => me.value.user?.avatar || fallbackUser.value?.avatar || '/static/images/user/default_avatar.png'
)
const maskedMobile = computed(() => {
    const m = me.value.user?.mobile || fallbackUser.value?.mobile || ''
    if (!m) return '未绑定手机号'
    return m.replace(/^(\d{3})\d{4}(\d{4})$/, '$1****$2')
})

const weddingDate = computed(() => me.value.wedding?.date || '')
const daysRemaining = computed(() => Number(me.value.wedding?.days_remaining ?? 0))

const quickEntries = computed(() => {
    const s: any = me.value.order_stats || {}
    return [
        {
            key: 'unpaid',
            label: '待付款',
            icon: 'rmb-circle',
            count: Number(s.pending_pay ?? 0) + Number(s.pending_confirm ?? 0),
            status: '0,1'
        },
        { key: 'service', label: '待服务', icon: 'calendar', count: Number(s.pending_service ?? 0), status: '2' },
        { key: 'done', label: '已完成', icon: 'checkmark-circle', count: Number(s.completed ?? 0), status: '4,5' },
        { key: 'refund', label: '退款/售后', icon: 'rmb-circle-fill', count: Number(s.refunding ?? 0), status: '8,10' }
    ]
})

const todos = computed(() => {
    const t: any = me.value.todo || {}
    return [
        {
            key: 'review',
            label: '待评价',
            count: Number(t.pending_review ?? 0),
            path: '/packages/pkg-order/pages/review/list'
        },
        {
            key: 'questionnaire',
            label: '待填问卷',
            count: Number(t.pending_questionnaire ?? 0),
            path: '/packages/pkg-service/pages/questionnaire/detail'
        },
        {
            key: 'waitlist',
            label: '候补中',
            count: Number(t.pending_waitlist ?? 0),
            path: '/packages/pkg-order/pages/waitlist/waitlist'
        },
        {
            key: 'aftersale',
            label: '处理中售后',
            count: Number(t.aftersale_unfinished ?? 0),
            path: '/packages/pkg-service/pages/aftersale/index'
        }
    ]
})

const todoCount = computed(() => todos.value.reduce((n, t) => n + t.count, 0))

const menus = computed(() => [
    { key: 'favorite', label: '我的收藏', icon: 'star', path: '/packages/pkg-user/pages/favorite/favorite' },
    { key: 'aftersale', label: '售后与投诉', icon: 'server-man', path: '/packages/pkg-service/pages/aftersale/index' },
    { key: 'contact', label: '联系客服', icon: 'kefu-ermai', path: '/pages/customer-service/customer-service' },
    { key: 'agreement', label: '服务协议与隐私', icon: 'file-text', path: '/pages/agreement/agreement' },
    { key: 'about', label: '关于我们', icon: 'info-circle', path: '/pages/about/about' }
])

/** 服务人员专属入口：仅 is_staff=1 时出现 */
const staffMenus = computed(() => {
    if (Number(me.value.roles?.is_staff ?? 0) !== 1) return []
    return [
        {
            key: 'staff-orders',
            label: '我的接单',
            icon: 'order',
            path: '/packages/pkg-staff/pages/order-list/order-list'
        },
        {
            key: 'staff-schedule',
            label: '我的档期',
            icon: 'calendar',
            path: '/packages/pkg-staff/pages/schedule/schedule'
        }
    ]
})

async function load() {
    if (!isLogin.value) {
        me.value = {}
        fallbackUser.value = null
        return
    }
    try {
        me.value = await getMe()
    } catch {
        try {
            fallbackUser.value = await getUserCenter()
        } catch {
            report('api_error', { endpoint: 'user.center', message: '个人中心加载失败' })
        }
    }
}

function handleProfileTap() {
    if (!isLogin.value) {
        navigate({ path: '/pages/login/login' })
        return
    }
    navigate({ path: '/packages/pkg-user/pages/profile/profile' })
}

function goSettings() {
    navigate({ path: '/packages/pkg-user/pages/settings/settings' })
}

function goOrders(status?: string) {
    if (!isLogin.value) {
        navigate({ path: '/pages/login/login' })
        return
    }
    if (status) uni.setStorageSync('order_tab_status', status)
    uni.switchTab({ url: '/pages/order/order' })
}

function handleMenu(item: any) {
    if (item.path) navigate({ path: item.path })
}

async function handleLogout() {
    const confirmed = await new Promise<boolean>((resolve) => {
        uni.showModal({
            title: '退出登录',
            content: '退出后需要重新登录才能查看订单',
            success: (r) => resolve(!!r.confirm),
            fail: () => resolve(false)
        })
    })
    if (!confirmed) return
    try {
        await logoutApi()
    } catch {
        // 服务端登出失败也要清本地
    }
    userStore.logout()
    me.value = {}
    fallbackUser.value = null
    uni.showToast({ title: '已退出', icon: 'none' })
}

onShow(() => load())
</script>

<style lang="scss" scoped>
.user-page {
    min-height: 100vh;
    background: var(--c-bg);

    &__tab-space {
        height: calc(100rpx + var(--safe-bottom) + var(--sp-4));
    }
}

.hero {
    position: relative;
    padding-bottom: var(--sp-4);

    &__bg {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 380rpx;
        background: linear-gradient(180deg, var(--c-hero-bg) 0%, rgba(248, 246, 240, 0) 100%);
    }

    &__body {
        position: relative;
        display: flex;
        align-items: center;
        gap: var(--sp-3);
        padding: var(--sp-3) var(--sp-4);
    }

    &__avatar {
        width: 120rpx;
        height: 120rpx;
        border-radius: 50%;
        background: var(--c-neutral-bg);
    }

    &__info {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 6rpx;
        min-width: 0;
    }

    &__name {
        font-size: var(--fs-xl);
        font-weight: 500;
        color: var(--c-ink);
    }

    &__sub {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__setting {
        width: 88rpx;
        height: 88rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin: calc(-1 * var(--sp-1));
    }
}

.countdown {
    position: relative;
    margin: 0 var(--sp-4);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--c-ink);
    border-radius: var(--r-lg);
    padding: var(--sp-4);

    &__left {
        display: flex;
        flex-direction: column;
        gap: 6rpx;
    }

    &__label {
        font-size: var(--fs-xs);
        color: rgba(255, 255, 255, 0.6);
    }

    &__date {
        font-size: var(--fs-md);
        color: #ffffff;
    }

    &__right {
        display: flex;
        align-items: baseline;
        gap: 6rpx;
    }

    &__num {
        font-size: var(--fs-number);
        font-weight: 500;
        color: var(--c-brand-soft);
        line-height: 1;
    }

    &__unit {
        font-size: var(--fs-xs);
        color: rgba(255, 255, 255, 0.7);
    }
}

.body {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    padding-top: var(--sp-4);
}

.card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--sp-3);

    &__title {
        font-size: var(--fs-md);
        font-weight: 500;
        color: var(--c-ink);
    }

    &__more {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: var(--sp-1);
        min-width: 88rpx;
        min-height: 88rpx;
        margin: -24rpx 0;
    }

    &__more-text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &--pad {
        padding: var(--sp-3) var(--sp-4) 0;
        margin-bottom: 0;
    }
}

.quick {
    display: flex;
    justify-content: space-between;

    &__item {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10rpx;
    }

    &__icon {
        position: relative;
    }

    &__badge {
        position: absolute;
        top: -8rpx;
        right: -16rpx;
        min-width: 28rpx;
        height: 28rpx;
        padding: 0 6rpx;
        border-radius: var(--r-pill);
        background: var(--c-danger);
        color: #ffffff;
        font-size: var(--fs-xs);
        line-height: 28rpx;
        text-align: center;
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }
}

.todo {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-2);

    &__item {
        flex: 1;
        min-width: 150rpx;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: var(--c-surface-2);
        border-radius: var(--r-sm);
        padding: var(--sp-2) var(--sp-3);

        &.is-disabled {
            opacity: 0.45;
        }
    }

    &__label {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &__count {
        font-size: var(--fs-md);
        font-weight: 500;
        color: var(--c-brand-text);
    }
}

.menu {
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    padding: var(--sp-3) var(--sp-4);
    border-bottom: 1rpx solid var(--c-line);

    &--last {
        border-bottom: none;
    }

    &__label {
        flex: 1;
        font-size: var(--fs-md);
        color: var(--c-ink);
    }

    &__value {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.logout {
    margin-top: var(--sp-4);
}
</style>
