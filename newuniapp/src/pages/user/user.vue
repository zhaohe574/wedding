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
        <!-- 顶部暖金晕染：从品牌暖底渐隐到页面底色，Profile 压在上面，无硬切边 -->
        <view class="u-wash">
            <!-- ============ 未登录：整页替换为登录引导（P 态） ============ -->
            <view v-if="!isLogin" class="guest">
            <view class="guest__seal-wrap">
                <!-- 后台配了图标就显示图标，没配自动回退到名称首字印章 -->
                <brand-mark :size="120" variant="light" round />
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
            <view v-if="countdown.enabled === 1 && loadingMe && !weddingDate" class="cd cd--sk">
                <view class="sk sk--label" />
                <view class="sk sk--num" />
                <view class="sk sk--foot" />
            </view>

            <view v-else-if="countdown.enabled === 1 && weddingDate" class="cd">
                <text class="cd__label">{{ countdown.label }}</text>
                <view class="cd__num-row">
                    <view class="cd__num">
                        <rolling-number
                            v-if="daysRemaining !== null"
                            :value="daysRemaining"
                            :min-chars="2"
                        />
                        <text v-else>—</text>
                    </view>
                    <text class="cd__unit">{{ countdown.unit_text }}</text>
                </view>

                <!-- 真实备婚进度：只认 me.wedding.progress；缺失 / 开关关闭 / 无婚期时整块不渲染，绝不补 0 -->
                <view v-if="weddingProgress" class="cd__progress">
                    <view class="cd__progress-head">
                        <text class="cd__progress-title">
                            备婚进度 · {{ weddingProgress.done_count }}/{{ weddingProgress.total_count }} 项
                        </text>
                        <rolling-number
                            class="cd__progress-pct t-num"
                            :value="weddingProgress.percent"
                            suffix="%"
                            :min-chars="3"
                        />
                    </view>
                    <view class="cd__track">
                        <view
                            class="cd__track-fill"
                            :style="{ width: `${weddingProgress.percent}%` }"
                        />
                    </view>
                    <view class="cd__steps">
                        <view
                            v-for="step in weddingProgress.milestones"
                            :key="step.key"
                            class="cd__step"
                            :class="{
                                'is-done': step.done,
                                'is-current': !step.done && step.key === weddingProgress.current_key
                            }"
                        >
                            <view class="cd__step-dot" />
                            <text class="cd__step-label">{{ step.label }}</text>
                        </view>
                    </view>
                </view>

                <view class="cd__foot">
                    <text class="cd__foot-text">{{ todoText }}</text>
                    <text v-if="weddingVenue" class="cd__foot-venue">{{ weddingVenue }}</text>
                </view>
            </view>

            <view v-else-if="countdown.enabled === 1" class="cd cd--empty">
                <text class="cd__label">{{ countdown.label }}</text>
                <text class="cd__empty-title">{{ countdown.empty_title }}</text>
                <text class="cd__empty-desc">{{ countdown.empty_desc }}</text>
                <view class="cd__empty-action">
                    <base-button
                        :text="countdown.empty_action_text"
                        size="sm"
                        variant="gold"
                        :block="false"
                        @click="goQuery"
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
                        <view class="metric__value">
                            <rolling-number v-if="m.num !== null" :value="m.num" :min-chars="1" />
                            <text v-else>—</text>
                        </view>
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
                            <view
                                v-if="o.count > 0"
                                class="order-cell__num"
                                :class="o.urgent ? 'order-cell__num--urgent' : 'order-cell__num--calm'"
                            >
                                <text
                                    class="order-cell__num-text"
                                    :class="{ 'order-cell__num-text--calm': !o.urgent }"
                                    >{{ o.count > 99 ? '99+' : o.count }}</text
                                >
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

            <!-- ============ 我的服务（quick-entry） ============ -->
            <view v-if="serviceRows.length" class="panel">
                <view class="panel__head">
                    <text class="panel__title">{{ quickEntry.title }}</text>
                </view>
                <view class="rows">
                    <template v-for="(row, i) in serviceRows" :key="row.key + '-' + i">
                        <!-- #ifdef MP-WEIXIN -->
                        <button
                            v-if="isWechatContactLink(row.link)"
                            open-type="contact"
                            class="lrow lrow--btn press-spring"
                            :class="{
                                'lrow--last': i === serviceRows.length - 1,
                                'is-pressing': rowPressKey === row.key
                            }"
                            @touchstart="onRowPressStart(row.key)"
                            @touchend="onRowPressEnd"
                            @touchcancel="onRowPressEnd"
                            @tap="onRowHaptic"
                        >
                            <view class="lrow__ic"><tn-icon :name="row.icon" :size="34" /></view>
                            <view class="lrow__txt">
                                <text class="lrow__label">{{ row.label }}</text>
                                <text v-if="row.sub" class="lrow__sub">{{ row.sub }}</text>
                            </view>
                            <view class="lrow__val">
                                <text v-if="row.value" class="lrow__val-text">{{ row.value }}</text>
                                <tn-icon name="right" :size="22" />
                            </view>
                        </button>
                        <view
                            v-else
                            class="lrow press-spring"
                            :class="{
                                'lrow--last': i === serviceRows.length - 1,
                                'is-pressing': rowPressKey === row.key
                            }"
                            @touchstart="onRowPressStart(row.key)"
                            @touchend="onRowPressEnd"
                            @touchcancel="onRowPressEnd"
                            @tap="onRow(row)"
                        >
                            <view class="lrow__ic"><tn-icon :name="row.icon" :size="34" /></view>
                            <view class="lrow__txt">
                                <text class="lrow__label">{{ row.label }}</text>
                                <text v-if="row.sub" class="lrow__sub">{{ row.sub }}</text>
                            </view>
                            <view class="lrow__val">
                                <text v-if="row.value" class="lrow__val-text">{{ row.value }}</text>
                                <tn-icon name="right" :size="22" />
                            </view>
                        </view>
                        <!-- #endif -->
                        <!-- #ifndef MP-WEIXIN -->
                        <view
                            class="lrow press-spring"
                            :class="{
                                'lrow--last': i === serviceRows.length - 1,
                                'is-pressing': rowPressKey === row.key
                            }"
                            @touchstart="onRowPressStart(row.key)"
                            @touchend="onRowPressEnd"
                            @touchcancel="onRowPressEnd"
                            @tap="onRow(row)"
                        >
                            <view class="lrow__ic"><tn-icon :name="row.icon" :size="34" /></view>
                            <view class="lrow__txt">
                                <text class="lrow__label">{{ row.label }}</text>
                                <text v-if="row.sub" class="lrow__sub">{{ row.sub }}</text>
                            </view>
                            <view class="lrow__val">
                                <text v-if="row.value" class="lrow__val-text">{{ row.value }}</text>
                                <tn-icon name="right" :size="22" />
                            </view>
                        </view>
                        <!-- #endif -->
                    </template>
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
                        class="lrow press-spring"
                        :class="{
                            'lrow--last': i === moreRows.length - 1,
                            'is-pressing': rowPressKey === row.key
                        }"
                        @touchstart="onRowPressStart(row.key)"
                        @touchend="onRowPressEnd"
                        @touchcancel="onRowPressEnd"
                        @tap="onRow(row)"
                    >
                        <view class="lrow__ic"><tn-icon :name="row.icon" :size="34" /></view>
                        <view class="lrow__txt">
                            <text class="lrow__label">{{ row.label }}</text>
                            <!-- 金标只是身份提示，不等于可进：普通用户点击走弹窗说明 -->
                            <text v-if="row.tag" class="lrow__tag">{{ row.tag }}</text>
                        </view>
                        <view class="lrow__val">
                            <view v-if="row.badge && row.badge > 0" class="lrow__badge">
                                <text class="lrow__badge-text">{{
                                    row.badge > 99 ? '99+' : row.badge
                                }}</text>
                            </view>
                            <text v-else-if="row.value" class="lrow__val-text">{{ row.value }}</text>
                            <tn-icon name="right" :size="22" />
                        </view>
                    </view>
                </view>
            </view>

            <text class="ver">{{ versionText }}</text>
            </template>
        </view>

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
import {
    normalizeQuickEntryContent,
    normalizeWeddingCountdownContent,
    parseDecoratePage,
    type DecorateLink,
    type MeResponse
} from '@shared/contracts'
import { getMe, getUserCenter } from '@/api/user'
import { getOrderStatistics } from '@/api/order'
import { getDecorate } from '@/api/home'
import { getMyFavorites } from '@/api/catalog'
import { getMyActivityRegistrations } from '@/api/activity'
import { useUserStore } from '@/stores/user'
import { useBadgeStore } from '@/stores/badge'
import { useAppStore } from '@/stores/app'
import { isDecorateLinkConfigured, isWechatContactLink, navigate } from '@/utils/link'
import { haptic } from '@/utils/haptics'
import { useBrand } from '@/composables/useBrand'

interface MenuRow {
    key: string
    label: string
    icon: string
    sub?: string
    value?: string
    tag?: string
    path?: string
    /** 右侧角标数字（>0 才渲染，模板里统一截到 99+） */
    badge?: number
    /** quick-entry 的完整链接配置（可带 query） */
    link?: DecorateLink
    action?: 'staff-workbench'
}

const userStore = useUserStore()
const badgeStore = useBadgeStore()
const appStore = useAppStore()
/** 品牌名称取自后台配置：菜单文案、版本串、印章 aria 都跟着后台改 */
const { brand } = useBrand()

const isLogin = computed(() => userStore.isLogin)

const loadingMe = ref(true)
const meError = ref(false)
const me = ref<Partial<MeResponse>>({})
const fallbackUser = ref<any>(null)

/**
 * 个人中心装修：页面 id=2 的 `wedding-countdown` + `quick-entry`。
 * 读不到（接口失败 / 组件缺失）时用契约默认值，页面照常渲染；
 * 婚期/待办/场地仍只认 `/v2/me`。
 */
const countdown = ref(normalizeWeddingCountdownContent(null))
const quickEntry = ref(normalizeQuickEntryContent(null))
let decorateLoaded = false

async function loadUserDecorate() {
    if (decorateLoaded) return
    decorateLoaded = true
    try {
        const page = await getDecorate(2)
        const parsed = parseDecoratePage(page?.data, page?.meta)
        const wedding = parsed.widgets.find((item) => item.name === 'wedding-countdown')
        const quick = parsed.widgets.find((item) => item.name === 'quick-entry')
        countdown.value = normalizeWeddingCountdownContent(wedding?.content)
        quickEntry.value = normalizeQuickEntryContent(quick?.content)
    } catch {
        // 装修失败不影响个人中心主信息，保留默认文案
    }
}

/** 收藏 / 活动计数：v2/me 未提供（G-18），单独取一次；失败显示「—」 */
const favoriteCount = ref<number | null>(null)
const activityCount = ref<number | null>(null)

const nickname = computed(
    () => me.value.user?.nickname || fallbackUser.value?.nickname || '微信用户'
)
const avatar = computed(() => me.value.user?.avatar || fallbackUser.value?.avatar || '')
const surname = computed(() => (nickname.value ? nickname.value.slice(0, 1) : '新'))

/** 婚期：只认已支付的预约订单（服务端返回），没有就显示「去预约」引导 */
const weddingDate = computed(() => me.value.wedding?.date || '')
const weddingVenue = computed(() => me.value.wedding?.venue || '')
/** null = 后端没给有效天数 → 显示「—」，不补 0 */
const daysRemaining = computed<number | null>(() => {
    const n = Number(me.value.wedding?.days_remaining)
    return Number.isFinite(n) && n >= 0 ? n : null
})

/**
 * 真实备婚进度（M2 新字段 wedding.progress）。
 * 三重门槛：装修开关 show_progress、有婚期、progress 且里程碑非空；
 * 任一不满足整块不渲染 —— 宁可不画，也不用 0% 冒充。
 */
const weddingProgress = computed(() => {
    if (Number(countdown.value.show_progress) === 0) return null
    if (!weddingDate.value) return null
    const progress = me.value.wedding?.progress
    if (!progress) return null
    const milestones = Array.isArray(progress.milestones) ? progress.milestones : []
    if (!milestones.length) return null
    const percent = Math.min(100, Math.max(0, Math.round(Number(progress.percent) || 0)))
    return {
        percent,
        done_count: Number(progress.done_count) || 0,
        total_count: Number(progress.total_count) || milestones.length,
        current_key: String(progress.current_key || ''),
        milestones
    }
})

const profileSub = computed(() => {
    const date = weddingDate.value
    return date ? `准新人 · 婚期 ${date}` : '准新人 · 还没预约档期'
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
    if (Number(s.all ?? 0) > 0) return true
    return [
        'pending_confirm',
        'pending_pay',
        'pending_service',
        'in_service',
        'completed',
        'refunding',
        'refunded'
    ].some((k) => Number(s[k] ?? 0) > 0)
})

/**
 * 五格状态与后台状态机一一对应，客服话术可复用。
 * urgent=true 用红色（需立即处理，同时计入「我的」Tab）；其余进行中状态用暖金二级角标，
 * 终态（已退款/已完成/已评价/已取消）不出现在这五格里。
 */
const orderCells = computed(() => {
    const s = orderStats.value
    const val = (k: string) => (s ? Number(s[k] ?? 0) : 0)
    return [
        {
            key: 'confirm',
            label: '待确认',
            icon: 'clock',
            count: val('pending_confirm'),
            status: '0',
            urgent: false
        },
        {
            key: 'pay',
            label: '待支付',
            icon: 'pay',
            count: val('pending_pay'),
            status: '1',
            urgent: true
        },
        {
            key: 'service',
            label: '待服务',
            icon: 'calendar',
            count: val('pending_service'),
            status: '2',
            urgent: false
        },
        {
            key: 'doing',
            label: '服务中',
            icon: 'success',
            count: val('in_service'),
            status: '3',
            urgent: false
        },
        {
            key: 'refund',
            label: '售后',
            icon: 'headset',
            count: val('refunding'),
            status: '8,10',
            urgent: false
        }
    ]
})

/** 四维资产：未读用红点，数字只在有真实计数时出现；null → 「—」，不补 0 */
const metrics = computed(() => {
    const unreadTotal = Number((me.value.unread as any)?.total ?? 0)
    const waitlistCount = me.value.todo
        ? Number(me.value.todo.pending_waitlist ?? 0)
        : null
    return [
        {
            key: 'favorite',
            label: '收藏',
            value: favoriteCount.value === null ? '—' : String(favoriteCount.value),
            num: favoriteCount.value,
            dot: false,
            path: '/packages/pkg-user/pages/favorite/favorite'
        },
        {
            key: 'waitlist',
            label: '候补',
            value: waitlistCount === null ? '—' : String(waitlistCount),
            num: waitlistCount,
            dot: false,
            path: '/packages/pkg-order/pages/waitlist/waitlist'
        },
        {
            key: 'activity',
            label: '活动',
            value: activityCount.value === null ? '—' : String(activityCount.value),
            num: activityCount.value,
            dot: false,
            path: '/packages/pkg-user/pages/activity/activity'
        },
        {
            key: 'message',
            label: '消息',
            value: me.value.unread ? String(unreadTotal) : '—',
            num: me.value.unread ? unreadTotal : null,
            dot: unreadTotal > 0,
            path: '/pages/message/message'
        }
    ]
})

/** 「我的服务」来自 quick-entry；实时数量只给认识的 key，其余不编数 */
const SERVICE_ICONS: Record<string, string> = {
    schedule: 'calendar',
    favorite: 'my-love',
    activity: 'ticket',
    consult: 'chat',
    waitlist: 'my-love',
    aftersale: 'headset',
    settings: 'set',
    wallet: 'funds'
}

function serviceValue(key: string): string {
    if (key === 'favorite') {
        return favoriteCount.value === null ? '' : String(favoriteCount.value)
    }
    if (key === 'activity') {
        return activityCount.value === null ? '' : `${activityCount.value} 场待参加`
    }
    if (key === 'waitlist') {
        return me.value.todo ? String(Number(me.value.todo.pending_waitlist ?? 0)) : ''
    }
    return ''
}

const serviceRows = computed<MenuRow[]>(() => {
    if (quickEntry.value.enabled !== 1) return []

    return quickEntry.value.data
        .filter(
            (item) =>
                item.is_show === '1' && item.title.trim() && isDecorateLinkConfigured(item.link)
        )
        .map((item) => ({
            key: item.key || item.title,
            label: item.title,
            icon: SERVICE_ICONS[item.key] || 'notice',
            sub: item.subtitle,
            value: serviceValue(item.key),
            path: item.link?.path || '',
            link: item.link
        }))
})

const canEnterStaffWorkbench = computed(
    () =>
        Number(me.value.roles?.is_staff ?? 0) === 1 &&
        Number(me.value.feature_switch?.staff_center ?? 1) === 1
)

/** 装修里已配了「我的钱包」快捷入口时，不再重复塞一条同样的行 */
const hasWalletInQuickEntry = computed(() =>
    serviceRows.value.some((r) => r.path === '/packages/pkg-user/pages/wallet/wallet')
)

/**
 * 菜单右侧角标：与 tabbar 的 badgeStore 同源（都来自 v2/me），
 * 保证「我的」Tab 上的数字和这里的入口数字永远一致。
 * 入口不可见时（非 staff / 开关关闭）工作台角标必须为 0，否则会出现点不进去却有红点。
 */
const staffTodoTotal = computed(() =>
    canEnterStaffWorkbench.value ? Math.max(0, Number(me.value.staff_todo?.total ?? 0)) : 0
)
const unreadTotal = computed(() => Math.max(0, Number((me.value.unread as any)?.total ?? 0)))

const moreRows = computed<MenuRow[]>(() => {
    const rows: MenuRow[] = []

    // 服务人员工作台只对 staff 且功能开关开启时展示；普通访客连入口都不渲染，不做「点击再拦」
    if (canEnterStaffWorkbench.value) {
        rows.push({
            key: 'workbench',
            label: '服务人员工作台',
            icon: 'server',
            tag: '工作人员',
            badge: staffTodoTotal.value,
            action: 'staff-workbench'
        })
    }

    if (!hasWalletInQuickEntry.value) {
        rows.push({
            key: 'wallet',
            label: '我的钱包',
            icon: 'funds',
            path: '/packages/pkg-user/pages/wallet/wallet'
        })
    }

    rows.push(
        {
            key: 'notice',
            label: '通知与提醒',
            icon: 'notice',
            path: '/pages/message/message',
            badge: unreadTotal.value
        },
        {
            key: 'service',
            label: '客服与帮助',
            icon: 'headset',
            path: '/pages/customer-service/customer-service'
        },
        { key: 'about', label: `关于${brand.value.name}`, icon: 'shop', path: '/pages/about/about' }
    )

    return rows
})

/** 版本号取后台配置（app_update.version）；后台没配就只显示品牌名，不写死版本 */
const versionText = computed(() => {
    const v = (appStore.config as any)?.app_update?.version
    return v ? `${brand.value.name} · V${v}` : brand.value.name
})

async function load() {
    if (!isLogin.value) {
        me.value = {}
        fallbackUser.value = null
        loadingMe.value = false
        badgeStore.clear()
        return
    }
    loadUserDecorate()
    loadingMe.value = true
    meError.value = false
    try {
        me.value = (await getMe()) || {}
        // 旧版 /v2/me 不带 order_stats.refunded：退款中可能残留已完成全额退款的脏数据，
        // 此时静默拉一次 /order/statistics 校正售后格，失败则保留原值
        if (
            me.value.order_stats &&
            (me.value.order_stats as any).refunded === undefined &&
            Number((me.value.order_stats as any).refunding ?? 0) > 0
        ) {
            const stats: any = await getOrderStatistics().catch(() => null)
            if (stats && typeof stats === 'object') {
                me.value.order_stats = {
                    ...me.value.order_stats,
                    refunding: Number(stats.refunding ?? 0),
                    refunded: Number(stats.refund ?? 0),
                    all: Number(stats.all ?? 0)
                }
            }
        }
        // 角标与页面共用同一次 /v2/me 结果，避免进「我的」页再单独请求一遍
        badgeStore.syncFromMe(me.value)
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
        getMyActivityRegistrations({ status_group: 'upcoming', page: 1, page_size: 1 })
            .then((res: any) => {
                const upcoming = Number(res?.counts?.upcoming)
                if (Number.isFinite(upcoming)) return upcoming
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

function goProfile() {
    navigate({ path: '/packages/pkg-user/pages/profile/profile' })
}

function goQuery() {
    navigate({ path: '/pages/query/query' })
}

/** 订单五格 → 22 屏带筛选状态（22 用 storage 消费，避免 query 与 tab 页冲突） */
function goOrders(status?: string) {
    haptic('light', 'user-order')
    uni.setStorageSync('order_tab_status', status ?? 'all')
    navigate({ path: '/pages/order/order' })
}

/* ---- 行按压（§3.1）：只压行本身，外层卡片保持静止 ---- */
const rowPressKey = ref('')

function onRowPressStart(key: string) {
    rowPressKey.value = key
}

function onRowPressEnd() {
    rowPressKey.value = ''
}

function onRowHaptic() {
    haptic('light', 'user-row')
}

function onRow(row: MenuRow) {
    haptic('light', 'user-row')
    if (row.action === 'staff-workbench') {
        onWorkbench()
        return
    }
    if (row.link) {
        navigate(row.link)
        return
    }
    if (row.path) navigate({ path: row.path })
}

/** 工作人员入口：非服务人员点了给说明 + 人工出口，不做死链 */
function onWorkbench() {
    // 入口本身只对 staff 渲染；这里再兜一层，避免角色变化后残留点击
    if (!canEnterStaffWorkbench.value) return
    navigate({ path: '/packages/pkg-staff/pages/center/center' })
}

onShow(() => load())

/** tab 再点：滚回顶部 + 静默刷新（跨天后倒计时必须变） */
onTabItemTap(() => {
    uni.pageScrollTo({ scrollTop: 0, duration: 0 })
    load()
})
</script>

<style lang="scss" scoped>
/* 顶部暖金晕染：品牌暖底 → 透明，360rpx 内完成过渡，不留硬边 */
.u-wash {
    background-image: linear-gradient(
        180deg,
        var(--c-brand-bg) 0%,
        rgba(var(--c-bg-rgb), 0) 100%
    );
    background-repeat: no-repeat;
    background-size: 100% 360rpx;
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
    background: linear-gradient(145deg, var(--c-dark-1), var(--c-dark-2));
    border: 1rpx solid var(--c-on-dark-fill-2);
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
        display: inline-flex;
        align-items: baseline;
        min-width: 2ch;
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

    /* ---- 真实备婚进度：金色轨 + 里程碑节点（只渲染后端返回的 milestones） ---- */
    &__progress {
        margin-top: 24rpx;
    }

    &__progress-head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__progress-title {
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
        letter-spacing: 0.04em;
    }

    &__progress-pct {
        font-family: var(--font-serif);
        font-size: var(--fs-lg);
        color: var(--c-brand-soft);
    }

    &__track {
        height: 6rpx;
        margin-top: 12rpx;
        border-radius: var(--r-pill);
        background: var(--c-on-dark-fill-2);
        overflow: hidden;
    }

    &__track-fill {
        height: 100%;
        border-radius: var(--r-pill);
        background: var(--c-brand);
        transition: width 320ms var(--ease-out);
    }

    &__steps {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 4rpx;
        margin-top: 16rpx;
    }

    &__step {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8rpx;
    }

    &__step-dot {
        width: 12rpx;
        height: 12rpx;
        border-radius: var(--r-pill);
        background: var(--c-on-dark-fill-3);
        border: 1rpx solid var(--c-on-dark-fill-3);
    }

    /* 已完成：金色实心；当前节点：金色描边空心 */
    &__step.is-done &__step-dot {
        background: var(--c-brand);
        border-color: var(--c-brand);
    }

    &__step.is-current &__step-dot {
        background: transparent;
        border-color: var(--c-brand);
    }

    &__step-label {
        font-size: var(--fs-18);
        color: var(--c-on-dark-2);
        text-align: center;
        line-height: var(--lh-tight);
    }

    &__step.is-done &__step-label,
    &__step.is-current &__step-label {
        color: var(--c-brand-soft);
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
    background: var(--c-on-dark-fill-3);

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
        display: inline-flex;
        align-items: baseline;
        min-width: 2ch;
        font-family: var(--font-serif);
        font-size: var(--fs-33); /* 设计稿 17px */
        font-weight: var(--fw-medium);
        color: var(--c-ink);
        line-height: var(--lh-tight);
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
    border: 1rpx solid var(--c-card-border);
    border-radius: var(--r-20); /* 同心圆角：面板 20 → 图标底块 --r-16 */
    box-shadow: var(--shadow-card);
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

/* 订单五格：轻量入口条 —— 不做逐格底色，只保留图标 + 红点/金色小数字 */
.order-row {
    display: flex;
    margin: 18rpx 24rpx 0;
    padding: 16rpx 0 20rpx;
    border-top: 1rpx solid var(--c-line);
}

.order-cell {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12rpx;
    min-height: 88rpx;
    padding: 4rpx 0;

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

        &--urgent {
            background: var(--c-danger);
        }

        /* 非紧急状态只留金色小数字，不加底色（减重，避免五个角标堆成噪声） */
        &--calm {
            top: -6rpx;
            right: -12rpx;
            min-width: 0;
            height: auto;
            padding: 0;
            border: 0;
            background: transparent;
        }
    }

    &__num-text {
        font-size: var(--fs-xs);
        /* 深底角标：浅色模式白字，深色模式墨字（--c-on-danger 两套均已核过对比度） */
        color: var(--c-on-danger);
        line-height: var(--lh-none);

        &--calm {
            color: var(--c-brand-text);
        }
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
    transform-origin: center center;

    &--last {
        border-bottom: 0;
    }

    &--btn {
        width: 100%;
        box-sizing: border-box;
        margin: 0;
        padding: 27rpx 0;
        background: transparent;
        border: none;
        border-bottom: 1rpx solid var(--c-line);
        border-radius: 0;
        text-align: left;
        line-height: var(--lh-tight);
        font-size: inherit;
        &::after {
            border: none;
        }
        &.lrow--last {
            border-bottom: 0;
        }
    }

    /* 图标盒：60rpx 圆角方底，同心圆角 20 − 4 = --r-16；颜色固定 --c-brand-text */
    &__ic {
        width: 60rpx;
        height: 60rpx;
        flex: none;
        border-radius: var(--r-16);
        background: var(--c-surface-2);
        color: var(--c-brand-text);
        display: flex;
        align-items: center;
        justify-content: center;
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

    /* 入口角标：比 tabbar 的大 4rpx，行高 96rpx 有空间，不必像图标角标那样裁到最小 */
    &__badge {
        min-width: 34rpx;
        height: 34rpx;
        padding: 0 10rpx;
        box-sizing: border-box;
        border-radius: var(--r-pill);
        background: var(--c-danger);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__badge-text {
        font-size: var(--fs-20);
        font-weight: var(--fw-semibold);
        color: var(--c-on-danger);
        line-height: var(--lh-none);
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
