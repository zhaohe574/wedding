<template>
    <page-shell
        bg="page"
        tabbar
        current="/pages/user/user"
        title="我的"
        title-align="left"
        title-variant="display"
        :show-back="false"
        :nav-capsule-avoid="isLogin"
    >
        <template #nav-right>
            <view
                v-if="isLogin"
                hover-class="is-pressed"
                class="nav-act"
                aria-role="button"
                aria-label="设置"
                @tap="goSettings"
            >
                <tn-icon name="set" :size="40" />
            </view>
        </template>

        <!-- ============ 未登录：整页替换为登录引导（P 态） ============ -->
        <view v-if="!isLogin" class="guest">
            <view class="guest__seal-wrap">
                <brand-seal size="120" variant="light" round :aria-label="BRAND_NAME" />
            </view>
            <text class="guest__title">登录后看你的备婚进度</text>
            <text class="guest__desc">查档期、收藏、订单都在这里</text>
            <view class="guest__actions">
                <base-button text="微信一键登录" size="lg" @click="goLogin" />
                <text hover-class="is-pressed" class="guest__link" @tap="goQuery">先随便看看</text>
            </view>
        </view>

        <template v-else>
            <!-- ============ Profile ============ -->
            <view class="profile">
                <image v-if="avatar" class="profile__avatar" :src="avatar" mode="aspectFill" />
                <view v-else class="profile__avatar profile__avatar--plain">
                    <text class="profile__avatar-text">{{ surname }}</text>
                </view>
                <view hover-class="is-pressed" class="profile__txt" @tap="goProfile">
                    <view class="profile__name-row">
                        <text class="profile__name">{{ nickname }}</text>
                        <tn-icon name="edit" :size="26" />
                    </view>
                    <text class="profile__sub">{{ profileSub }}</text>
                </view>
            </view>

            <!-- ============ 倒计时卡（无婚期数据就换成引导） ============ -->
            <view v-if="loadingMe && !weddingDate" class="cd cd--sk">
                <view class="sk sk--label" />
                <view class="sk sk--num" />
                <view class="sk sk--foot" />
            </view>

            <view v-else-if="weddingDate" class="cd">
                <text class="cd__label">COUNTDOWN TO THE DAY</text>
                <view class="cd__num-row">
                    <text class="cd__num">{{ daysRemaining }}</text>
                    <text class="cd__unit">天后，就是我们的婚礼</text>
                </view>
                <!-- 筹备进度百分比后端无字段，进度条不画（画了就是编数据） -->
                <view class="cd__foot">
                    <text class="cd__foot-text">{{ todoText }}</text>
                    <text v-if="weddingVenue" class="cd__foot-venue">{{ weddingVenue }}</text>
                </view>
            </view>

            <view v-else class="cd cd--empty">
                <text class="cd__label">COUNTDOWN TO THE DAY</text>
                <text class="cd__empty-title">还没填婚期</text>
                <text class="cd__empty-desc">填上婚期，我们帮你倒排筹备节奏</text>
                <view class="cd__empty-action">
                    <base-button
                        text="去填写"
                        size="sm"
                        variant="ghost"
                        :block="false"
                        @click="goProfile"
                    />
                </view>
            </view>

            <!-- 取数失败：行内降级，不整屏报错 -->
            <view v-if="meError" class="inline-note" @tap="load">
                <text class="inline-note__text">筹备进度没取到 · 重试</text>
            </view>

            <!-- ============ 四维资产（未读只给红点，不给数字角标） ============ -->
            <view class="metrics">
                <view
                    v-for="m in metrics"
                    :key="m.key"
                    hover-class="is-pressed"
                    class="metric"
                    @tap="m.path && navigate({ path: m.path })"
                >
                    <view class="metric__value-wrap">
                        <text class="metric__value">{{ m.value }}</text>
                        <view v-if="m.dot" class="metric__dot" />
                    </view>
                    <text class="metric__label">{{ m.label }}</text>
                </view>
            </view>

            <!-- ============ 我的订单 ============ -->
            <view class="panel">
                <view class="panel__head">
                    <text class="panel__title">我的订单</text>
                    <view hover-class="is-pressed" class="panel__more" @tap="goOrders()">
                        <text class="panel__more-text">全部订单</text>
                        <tn-icon name="right" :size="22" />
                    </view>
                </view>

                <view v-if="hasOrders" class="order-row">
                    <view
                        v-for="o in orderCells"
                        :key="o.key"
                        hover-class="is-pressed"
                        class="order-cell"
                        @tap="goOrders(o.status)"
                    >
                        <view class="order-cell__ic">
                            <tn-icon :name="o.icon" :size="40" />
                            <view v-if="o.count > 0" class="order-cell__num">
                                <text class="order-cell__num-text">{{
                                    o.count > 99 ? '99+' : o.count
                                }}</text>
                            </view>
                        </view>
                        <text class="order-cell__label">{{ o.label }}</text>
                    </view>
                </view>

                <view v-else hover-class="is-pressed" class="order-empty" @tap="goQuery">
                    <text class="order-empty__text">还没有订单 · 去查档期</text>
                    <tn-icon name="right" :size="22" />
                </view>
            </view>

            <!-- ============ 我的服务 ============ -->
            <view class="panel">
                <view class="panel__head">
                    <text class="panel__title">我的服务</text>
                </view>
                <view class="rows">
                    <view
                        v-for="(row, i) in serviceRows"
                        :key="row.key"
                        hover-class="is-pressed"
                        class="lrow"
                        :class="{ 'lrow--last': i === serviceRows.length - 1 }"
                        @tap="onRow(row)"
                    >
                        <tn-icon :name="row.icon" :size="34" />
                        <view class="lrow__txt">
                            <text class="lrow__label">{{ row.label }}</text>
                            <text v-if="row.sub" class="lrow__sub">{{ row.sub }}</text>
                        </view>
                        <view class="lrow__val">
                            <text v-if="row.value" class="lrow__val-text">{{ row.value }}</text>
                            <tn-icon name="right" :size="22" />
                        </view>
                    </view>
                </view>
            </view>

            <!-- ============ 更多 ============ -->
            <view class="panel">
                <view class="panel__head">
                    <text class="panel__title">更多</text>
                </view>
                <view class="rows">
                    <view
                        v-for="(row, i) in moreRows"
                        :key="row.key"
                        hover-class="is-pressed"
                        class="lrow"
                        :class="{ 'lrow--last': i === moreRows.length - 1 }"
                        @tap="onRow(row)"
                    >
                        <tn-icon :name="row.icon" :size="34" />
                        <view class="lrow__txt">
                            <text class="lrow__label">{{ row.label }}</text>
                            <!-- 金标只是身份提示，不等于可进：普通用户点击走弹窗说明 -->
                            <text v-if="row.tag" class="lrow__tag">{{ row.tag }}</text>
                        </view>
                        <view class="lrow__val">
                            <tn-icon name="right" :size="22" />
                        </view>
                    </view>
                </view>
            </view>

            <text class="ver">{{ versionText }}</text>
        </template>

        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
/**
 * 04 我的。
 *
 * 数据：`v2/me` 一次聚合（user / order_stats / todo / wedding / unread / roles）；
 * 失败降级 `/user/center`，此时没有订单统计与婚期 → 对应区块显示「—」或引导，不猜数。
 *
 * 缺口（不编数据，一律隐藏）：
 *  - 筹备进度百分比：无字段 → 不画进度条。
 *  - 收藏 / 活动计数：v2/me 没有，进本屏时各调一次列表接口自己数（G-18 过渡方案）。
 *  - 我的咨询未读数、我的档期「已锁定」摘要：无字段 → 不显示右侧值。
 */
import { computed, ref } from 'vue'
import { onShow, onTabItemTap } from '@dcloudio/uni-app'
import type { MeResponse } from '@shared/contracts'
import { getMe, getUserCenter } from '@/api/user'
import { getMyFavorites } from '@/api/catalog'
import { getMyActivityRegistrations } from '@/api/activity'
import { useUserStore } from '@/stores/user'
import { useAppStore } from '@/stores/app'
import { navigate } from '@/utils/link'
import { BRAND_NAME, BRAND_VERSION_PREFIX } from '@/constants/brand'

interface MenuRow {
    key: string
    label: string
    icon: string
    sub?: string
    value?: string
    tag?: string
    path?: string
    action?: 'staff-workbench'
}

const userStore = useUserStore()
const appStore = useAppStore()

const isLogin = computed(() => userStore.isLogin)

const loadingMe = ref(true)
const meError = ref(false)
const me = ref<Partial<MeResponse>>({})
const fallbackUser = ref<any>(null)

/** 收藏 / 活动计数：v2/me 未提供（G-18），单独取一次；失败显示「—」 */
const favoriteCount = ref<number | null>(null)
const activityCount = ref<number | null>(null)

const nickname = computed(
    () => me.value.user?.nickname || fallbackUser.value?.nickname || '微信用户'
)
const avatar = computed(() => me.value.user?.avatar || fallbackUser.value?.avatar || '')
const surname = computed(() => (nickname.value ? nickname.value.slice(0, 1) : '新'))

/** 婚期：有日期才算有倒计时，缺失走「去填写」引导 */
const weddingDate = computed(() => me.value.wedding?.date || '')
const weddingVenue = computed(() => me.value.wedding?.venue || '')
const daysRemaining = computed(() => {
    const n = Number(me.value.wedding?.days_remaining)
    return Number.isFinite(n) && n >= 0 ? n : '—'
})

const profileSub = computed(() => {
    const date = weddingDate.value
    return date ? `准新人 · 婚期 ${date}` : '准新人 · 还没填婚期'
})

/** 待确认事项数：todo 四项之和（真实字段，不编） */
const todoText = computed(() => {
    const t: any = me.value.todo || {}
    const n =
        Number(t.pending_review ?? 0) +
        Number(t.pending_questionnaire ?? 0) +
        Number(t.pending_waitlist ?? 0) +
        Number(t.aftersale_unfinished ?? 0)
    if (!me.value.todo) return '筹备节奏待同步'
    return n > 0 ? `还有 ${n} 项待确认` : '暂无待确认事项'
})

const orderStats = computed<any>(() => me.value.order_stats || null)
const hasOrders = computed(() => {
    const s = orderStats.value
    if (!s) return false
    return ['pending_confirm', 'pending_pay', 'pending_service', 'in_service', 'refunding'].some(
        (k) => Number(s[k] ?? 0) > 0
    )
})

/** 五格状态与后台状态机一一对应，客服话术可复用 */
const orderCells = computed(() => {
    const s = orderStats.value
    const val = (k: string) => (s ? Number(s[k] ?? 0) : 0)
    return [
        {
            key: 'confirm',
            label: '待确认',
            icon: 'clock',
            count: val('pending_confirm'),
            status: '0'
        },
        { key: 'pay', label: '待支付', icon: 'pay', count: val('pending_pay'), status: '1' },
        {
            key: 'service',
            label: '待服务',
            icon: 'calendar',
            count: val('pending_service'),
            status: '2'
        },
        { key: 'doing', label: '服务中', icon: 'success', count: val('in_service'), status: '3' },
        { key: 'refund', label: '售后', icon: 'headset', count: val('refunding'), status: '8,10' }
    ]
})

/** 四维资产：未读用红点，数字只在有真实计数时出现 */
const metrics = computed(() => {
    const unreadTotal = Number((me.value.unread as any)?.total ?? 0)
    return [
        {
            key: 'favorite',
            label: '收藏',
            value: favoriteCount.value === null ? '—' : String(favoriteCount.value),
            dot: false,
            path: '/packages/pkg-user/pages/favorite/favorite'
        },
        {
            key: 'waitlist',
            label: '候补',
            value: me.value.todo ? String(Number(me.value.todo.pending_waitlist ?? 0)) : '—',
            dot: false,
            path: '/packages/pkg-order/pages/waitlist/waitlist'
        },
        {
            key: 'activity',
            label: '活动',
            value: activityCount.value === null ? '—' : String(activityCount.value),
            dot: false,
            path: '/packages/pkg-user/pages/activity/activity'
        },
        {
            key: 'message',
            label: '消息',
            value: me.value.unread ? String(unreadTotal) : '—',
            dot: unreadTotal > 0,
            path: '/pages/message/message'
        }
    ]
})

const serviceRows = computed<MenuRow[]>(() => [
    { key: 'schedule', label: '我的档期', icon: 'calendar', path: '/pages/order/order' },
    {
        key: 'favorite',
        label: '我的收藏',
        icon: 'my-love',
        value: favoriteCount.value === null ? '' : String(favoriteCount.value),
        path: '/packages/pkg-user/pages/favorite/favorite'
    },
    {
        key: 'activity',
        label: '我的活动',
        icon: 'ticket',
        value: activityCount.value === null ? '' : `${activityCount.value} 场待参加`,
        path: '/packages/pkg-user/pages/activity/activity'
    },
    {
        key: 'consult',
        label: '我的咨询',
        icon: 'chat',
        path: '/packages/pkg-service/pages/concierge/concierge'
    }
])

const moreRows = computed<MenuRow[]>(() => [
    {
        key: 'workbench',
        label: '服务人员工作台',
        icon: 'server',
        tag: '工作人员',
        action: 'staff-workbench'
    },
    { key: 'notice', label: '通知与提醒', icon: 'notice', path: '/pages/message/message' },
    {
        key: 'service',
        label: '客服与帮助',
        icon: 'headset',
        path: '/pages/customer-service/customer-service'
    },
    { key: 'about', label: `关于${BRAND_NAME}`, icon: 'shop', path: '/pages/about/about' }
])

/** 版本号取后台配置（app_update.version）；后台没配就只显示品牌名，不写死版本 */
const versionText = computed(() => {
    const v = (appStore.config as any)?.app_update?.version
    return v ? `${BRAND_VERSION_PREFIX} · V${v}` : BRAND_VERSION_PREFIX
})

async function load() {
    if (!isLogin.value) {
        me.value = {}
        fallbackUser.value = null
        loadingMe.value = false
        return
    }
    loadingMe.value = true
    meError.value = false
    try {
        me.value = (await getMe()) || {}
    } catch {
        me.value = {}
        meError.value = true
        try {
            fallbackUser.value = await getUserCenter()
        } catch {
            fallbackUser.value = null
        }
    } finally {
        loadingMe.value = false
    }
    loadCounts()
}

/** 计数单独取，任一失败只降级该格为「—」，不影响主信息 */
async function loadCounts() {
    const [fav, act] = await Promise.all([
        getMyFavorites()
            .then((list) => (Array.isArray(list) ? list.length : null))
            .catch(() => null),
        getMyActivityRegistrations({ status_group: 'registered', page: 1, page_size: 1 })
            .then((res: any) => {
                const total = Number(res?.total)
                if (Number.isFinite(total)) return total
                return Array.isArray(res?.list) ? res.list.length : null
            })
            .catch(() => null)
    ])
    favoriteCount.value = fav
    activityCount.value = act
}

// ---------------------------------------------------------------- 跳转

function goLogin() {
    navigate({ path: '/pages/login/login' })
}

function goSettings() {
    navigate({ path: '/packages/pkg-user/pages/settings/settings' })
}

function goProfile() {
    navigate({ path: '/packages/pkg-user/pages/profile/profile' })
}

function goQuery() {
    navigate({ path: '/pages/query/query' })
}

/** 订单五格 → 22 屏带筛选状态（22 用 storage 消费，避免 query 与 tab 页冲突） */
function goOrders(status?: string) {
    if (status) uni.setStorageSync('order_tab_status', status)
    else uni.removeStorageSync('order_tab_status')
    navigate({ path: '/pages/order/order' })
}

function onRow(row: MenuRow) {
    if (row.action === 'staff-workbench') {
        onWorkbench()
        return
    }
    if (row.path) navigate({ path: row.path })
}

/** 工作人员入口：非服务人员点了给说明 + 人工出口，不做死链 */
function onWorkbench() {
    const isStaff = Number(me.value.roles?.is_staff ?? 0) === 1
    if (isStaff) {
        navigate({ path: '/packages/pkg-staff/pages/center/center' })
        return
    }
    uni.showModal({
        title: '仅服务人员可用',
        content: '这个入口只对服务人员开放，需要邀请码激活。',
        confirmText: '联系客服',
        cancelText: '我知道了',
        success: (r) => {
            if (r.confirm) navigate({ path: '/pages/customer-service/customer-service' })
        }
    })
}

onShow(() => load())

/** tab 再点：滚回顶部 + 静默刷新（跨天后倒计时必须变） */
onTabItemTap(() => {
    uni.pageScrollTo({ scrollTop: 0, duration: 0 })
    load()
})
</script>

<style lang="scss" scoped>
/* ---------------- 导航右侧设置按钮（88rpx 热区，避让交给 nav-bar 精算） ---------------- */
.nav-act {
    width: 88rpx;
    height: 88rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    /* 只放图标 */
    color: var(--c-ink-2);
}

/* ---------------- 未登录 ---------------- */
.guest {
    padding: 120rpx 42rpx var(--sp-7);
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;

    &__seal-wrap {
        margin-bottom: var(--sp-5);
    }

    &__title {
        font-size: var(--fs-xl);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        letter-spacing: 0.02em;
    }

    &__desc {
        margin-top: var(--sp-2);
        font-size: var(--fs-md);
        color: var(--c-ink-3);
    }

    &__actions {
        width: 100%;
        margin-top: var(--sp-6);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: var(--sp-4);
    }

    &__link {
        font-size: var(--fs-md);
        color: var(--c-ink-3);
        text-decoration: underline;
    }
}

/* ---------------- Profile ---------------- */
.profile {
    display: flex;
    align-items: center;
    gap: 27rpx; /* 设计稿 14px */
    padding: 15rpx 42rpx 0; /* 设计稿 8px 22px 0 */

    &__avatar {
        width: 112rpx; /* 设计稿 58px */
        height: 112rpx;
        border-radius: var(--r-pill);
        border: 1rpx solid var(--c-line-strong);
        background: var(--c-brand-bg);
        flex: none;

        &--plain {
            display: flex;
            align-items: center;
            justify-content: center;
        }
    }

    &__avatar-text {
        font-family: var(--font-serif);
        font-size: var(--fs-42);
        color: var(--c-brand-text);
    }

    &__txt {
        flex: 1;
        min-width: 0;
    }

    &__name-row {
        display: flex;
        align-items: center;
        gap: 13rpx; /* 设计稿 7px */
        /* 昵称旁的编辑图标；.profile__name 自己声明了 --c-ink */
        color: var(--c-ink-3);
    }

    &__name {
        font-size: var(--fs-37); /* 设计稿 19px */
        font-weight: var(--fw-semibold);
        letter-spacing: 0.04em;
        color: var(--c-ink);
        max-width: 70%;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    &__sub {
        display: block;
        margin-top: 12rpx; /* 设计稿 6px */
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        letter-spacing: 0.03em;
    }
}

/* ---------------- 倒计时卡 ---------------- */
.cd {
    margin: 35rpx 42rpx 0; /* 设计稿 18px 22px */
    border-radius: var(--r-md);
    background: var(--c-ink);
    border: 1rpx solid var(--c-line-strong);
    padding: 35rpx 35rpx 31rpx; /* 设计稿 18px 18px 16px */

    &__label {
        display: block;
        font-family: var(--font-serif);
        font-size: var(--fs-xs);
        letter-spacing: 0.2em;
        color: var(--c-on-dark-2);
    }

    &__num-row {
        display: flex;
        align-items: baseline;
        gap: 12rpx;
        margin-top: 17rpx;
    }

    &__num {
        font-family: var(--font-serif);
        font-size: var(--fs-69); /* 设计稿 36px */
        font-weight: var(--fw-medium);
        color: var(--c-on-dark);
        line-height: var(--lh-none);
    }

    &__unit {
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
        letter-spacing: 0.06em;
    }

    &__foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 23rpx;
        gap: var(--sp-2);
    }

    &__foot-text {
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
        letter-spacing: 0.03em;
    }

    &__foot-venue {
        font-size: var(--fs-xs);
        color: var(--c-brand-soft);
        flex: none;
    }

    /* 无婚期：同一张深色卡换成引导，不换版式 */
    &--empty {
        display: flex;
        flex-direction: column;
        gap: var(--sp-2);
    }

    &__empty-title {
        margin-top: 8rpx;
        font-size: var(--fs-lg);
        font-weight: var(--fw-medium);
        color: var(--c-on-dark);
    }

    &__empty-desc {
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
        line-height: var(--lh-body);
    }

    &__empty-action {
        margin-top: var(--sp-2);
        display: flex;
        justify-content: flex-start;
    }

    &--sk {
        display: flex;
        flex-direction: column;
        gap: var(--sp-3);
    }
}

/* 深色卡内骨架用白色低透明度，不用灰块（灰块在墨黑上会脏） */
.sk {
    border-radius: var(--r-xs);
    background: rgba(255, 255, 255, 0.14);

    &--label {
        width: 45%;
        height: 20rpx;
    }

    &--num {
        width: 38%;
        height: 60rpx;
    }

    &--foot {
        width: 65%;
        height: 20rpx;
    }
}

.inline-note {
    margin: 20rpx 42rpx 0;
    min-height: 80rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--r-sm);
    border: 1rpx dashed var(--c-control-border);

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

/* ---------------- 四维资产 ---------------- */
.metrics {
    margin: 23rpx 42rpx 0; /* 设计稿 12px 22px */
    background: var(--c-surface);
    border: 1rpx solid var(--c-line);
    border-radius: var(--r-lg);
    display: flex;
    padding: 27rpx 0; /* 设计稿 14px */
}

.metric {
    flex: 1;
    text-align: center;
    position: relative;
    min-height: 88rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;

    & + &::before {
        content: '';
        position: absolute;
        left: 0;
        top: 8rpx;
        bottom: 8rpx;
        width: 1rpx;
        background: var(--c-line);
    }

    &__value-wrap {
        position: relative;
        display: inline-flex;
        align-items: flex-start;
    }

    &__value {
        font-family: var(--font-serif);
        font-size: var(--fs-33); /* 设计稿 17px */
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &__dot {
        width: 10rpx;
        height: 10rpx;
        border-radius: var(--r-pill);
        background: var(--c-danger);
        margin-left: 6rpx;
        margin-top: 2rpx;
    }

    &__label {
        display: block;
        margin-top: 8rpx;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        letter-spacing: 0.05em;
    }
}

/* ---------------- 面板 ---------------- */
.panel {
    margin: 23rpx 42rpx 0;
    background: var(--c-surface);
    border: 1rpx solid var(--c-line);
    border-radius: var(--r-lg);
    overflow: hidden;

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 27rpx 29rpx 0; /* 设计稿 14px 15px 0 */
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        letter-spacing: 0.03em;
        color: var(--c-ink);
    }

    &__more {
        display: flex;
        align-items: center;
        gap: 4rpx;
        min-height: 60rpx;
        /* 箭头与「全部订单」同色；.panel__more-text 自己声明了 --c-ink-3 */
        color: var(--c-ink-3);
    }

    &__more-text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.order-row {
    display: flex;
    padding: 31rpx 15rpx 29rpx; /* 设计稿 16px 8px 15px */
}

.order-cell {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15rpx; /* 设计稿 8px */
    min-height: 88rpx;

    &__ic {
        position: relative;
        height: 40rpx; /* 设计稿 21px */
        display: flex;
        align-items: center;
        justify-content: center;
        /* 订单状态图标；角标文字 .order-cell__num-text 自己声明了 --c-ink-inverse */
        color: var(--c-ink-2);
    }

    &__num {
        position: absolute;
        top: -12rpx;
        right: -18rpx;
        min-width: 27rpx;
        height: 27rpx;
        padding: 0 7rpx;
        border-radius: var(--r-pill);
        background: var(--c-danger);
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2rpx solid var(--c-surface);
    }

    &__num-text {
        font-size: var(--fs-xs);
        color: var(--c-ink-inverse);
        line-height: var(--lh-none);
    }

    &__label {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
        letter-spacing: 0.03em;
    }
}

.order-empty {
    /* 空态文字与箭头同为 --c-ink-3；.order-empty__text 自己声明了颜色 */
    color: var(--c-ink-3);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--sp-1);
    min-height: 108rpx;
    padding: 0 29rpx 23rpx;

    &__text {
        font-size: var(--fs-md);
        color: var(--c-ink-3);
    }
}

/* ---------------- 纵向列表 ---------------- */
.rows {
    padding: 0 29rpx; /* 设计稿 15px */
}

.lrow {
    display: flex;
    align-items: center;
    gap: 21rpx; /* 设计稿 11px */
    /* 左侧行图标；.lrow__label / __sub / __tag 都自带颜色 */
    color: var(--c-ink-2);
    min-height: 96rpx; /* 设计稿 14px padding + 内容 */
    padding: 27rpx 0;
    border-bottom: 1rpx solid var(--c-line);

    &--last {
        border-bottom: 0;
    }

    &__txt {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 13rpx;
    }

    &__label {
        font-size: var(--fs-md);
        color: var(--c-ink);
        letter-spacing: 0.02em;
    }

    &__sub {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    /* 工作人员金标：身份提示，不是状态 */
    &__tag {
        height: 29rpx;
        padding: 0 10rpx;
        border-radius: var(--r-xs);
        background: var(--c-brand-bg);
        border: 1rpx solid var(--c-primary-border);
        color: var(--c-brand-text);
        font-size: var(--fs-xs);
        line-height: 27rpx;
        letter-spacing: 0.05em;
        flex: none;
    }

    &__val {
        display: flex;
        align-items: center;
        gap: 8rpx;
        flex: none;
        /* 右侧箭头单独一色，覆盖 .lrow 继承下来的 --c-ink-2 */
        color: var(--c-ink-3);
    }

    &__val-text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.ver {
    display: block;
    text-align: center;
    padding: 35rpx 0 8rpx; /* 设计稿 18px 0 4px */
    font-family: var(--font-serif);
    font-size: var(--fs-xs);
    letter-spacing: 0.2em;
    color: var(--c-ink-3);
}
</style>
