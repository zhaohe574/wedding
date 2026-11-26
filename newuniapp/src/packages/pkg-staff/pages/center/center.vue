<template>
    <page-shell
        title="服务人员工作台"
        bg="page"
        :show-back="true"
        :pull-refresh="true"
        @refresh="onRefresh"
    >
        <staff-guard
            v-if="!guardAllowed"
            :phase="guardPhase"
            :reason="guardReason"
            :error="guardError"
            @retry="onGuardRetry"
            @action="onGuardAction"
        />

        <view v-else class="page-wrap center">
            <view v-if="phase === 'loading' || phase === 'idle'" class="center__loading">
                <base-skeleton :rows="4" />
            </view>

            <base-empty
                v-else-if="phase === 'error'"
                status="error"
                :error-kind="view?.errorKind || 'unknown'"
                :error-text="view?.title"
                :desc="view?.desc"
                :primary-action="view?.primaryAction"
                fixed
                @primary="onPrimary"
                @retry="() => load()"
            />

            <template v-else>
                <!-- 工作名片：状态、个人资料与最高频的三项工作指标 -->
                <view class="hero">
                    <view class="hero__top">
                        <view class="hero__badges">
                            <view class="hero__role">
                                <tn-icon name="medal" :size="24" class="hero__role-icon" />
                                <text>服务人员</text>
                            </view>
                            <view class="hero__status">
                                <view
                                    class="hero__status-dot"
                                    :class="{ 'is-active': Number(profile.status) === 1 }"
                                />
                                <text>{{ profile.status_desc || '状态待确认' }}</text>
                            </view>
                        </view>
                        <view
                            class="hero__profile-link"
                            hover-class="is-pressed"
                            aria-role="button"
                            aria-label="查看我的资料"
                            @tap="go('/packages/pkg-staff/pages/profile/profile')"
                        >
                            <text>我的资料</text>
                            <tn-icon name="right" :size="22" class="hero__profile-icon" />
                        </view>
                    </view>

                    <view class="hero__person">
                        <image
                            v-if="profile.avatar && !avatarFailed"
                            class="hero__avatar"
                            :src="profile.avatar"
                            mode="aspectFill"
                            aria-label="服务人员头像"
                            @error="avatarFailed = true"
                        />
                        <view v-else class="hero__avatar hero__avatar--text">
                            <text class="hero__initial">{{ initial }}</text>
                        </view>
                        <view class="hero__body">
                            <view class="hero__name-row">
                                <text class="hero__name ellipsis">{{
                                    profile.name || '服务人员'
                                }}</text>
                                <view v-if="ratingText" class="hero__rating">
                                    <text class="hero__rating-star">★</text>
                                    <text>{{ ratingText }}</text>
                                </view>
                            </view>
                            <text class="hero__meta ellipsis">{{ profileMeta }}</text>
                            <text class="hero__price ellipsis">
                                {{ profile.price_text || '展示价格待完善' }}
                            </text>
                        </view>
                    </view>

                    <view class="hero__pulse">
                        <view
                            v-for="item in focusMetrics"
                            :key="item.key"
                            class="pulse"
                            :class="{ 'pulse--active': item.value > 0 }"
                            hover-class="is-pressed"
                            aria-role="button"
                            :aria-label="`${item.label} ${item.value} ${item.unit}`"
                            @tap="onFocusMetric(item.key)"
                        >
                            <text class="pulse__label">{{ item.label }}</text>
                            <view class="pulse__value-row">
                                <text class="pulse__value">{{ item.value }}</text>
                                <text class="pulse__unit">{{ item.unit }}</text>
                            </view>
                        </view>
                    </view>
                </view>

                <view
                    v-if="profile.audit_status_desc"
                    class="audit"
                    :class="`audit--${auditTone}`"
                    hover-class="is-pressed"
                    aria-role="button"
                    :aria-label="`资料审核${profile.audit_status_desc}，查看我的资料`"
                    @tap="go('/packages/pkg-staff/pages/profile/profile')"
                >
                    <tn-icon name="tips" :size="26" class="audit__icon" />
                    <text class="audit__text">资料审核：{{ profile.audit_status_desc }}</text>
                    <text class="audit__link">查看</text>
                </view>

                <!-- 行动条：优先待确认，其次今日服务；均只读跳转，不在本页执行写操作 -->
                <view
                    v-if="primaryTask"
                    class="task"
                    :class="`task--${primaryTask.tone}`"
                    hover-class="is-pressed"
                    aria-role="button"
                    :aria-label="`${primaryTask.title}，${primaryTask.action}`"
                    @tap="onPrimaryTask"
                >
                    <view class="task__icon-wrap">
                        <tn-icon :name="primaryTask.icon" :size="30" class="task__icon" />
                    </view>
                    <view class="task__body">
                        <text class="task__title">{{ primaryTask.title }}</text>
                        <text class="task__desc">{{ primaryTask.desc }}</text>
                    </view>
                    <view class="task__action">
                        <text>{{ primaryTask.action }}</text>
                        <tn-icon name="right" :size="20" class="task__action-icon" />
                    </view>
                </view>

                <section-header title="常用功能" />
                <base-card compact>
                    <view class="quick">
                        <view
                            v-for="item in quickActions"
                            :key="item.key"
                            class="quick__item"
                            hover-class="is-pressed"
                            aria-role="button"
                            :aria-label="item.label"
                            @tap="go(item.path)"
                        >
                            <view class="quick__icon-wrap">
                                <tn-icon :name="item.icon" :size="32" class="quick__icon" />
                                <view v-if="item.badge" class="quick__badge">
                                    <text class="quick__badge-text">{{ item.badge }}</text>
                                </view>
                            </view>
                            <text class="quick__label">{{ item.label }}</text>
                        </view>
                    </view>
                </base-card>

                <section-header title="客户经营与团队" />
                <view class="ops-grid">
                    <view
                        v-for="item in crmEntries"
                        :key="item.key"
                        class="ops-card"
                        hover-class="is-pressed"
                        aria-role="button"
                        :aria-label="item.label"
                        @tap="go(item.path)"
                    >
                        <view class="ops-card__top">
                            <view class="ops-card__icon-wrap">
                                <tn-icon :name="item.icon" :size="28" class="ops-card__icon" />
                            </view>
                            <text v-if="item.badge" class="ops-card__badge">{{ item.badge }}</text>
                        </view>
                        <text class="ops-card__title">{{ item.label }}</text>
                        <text class="ops-card__desc ellipsis">{{ item.desc }}</text>
                    </view>
                </view>

                <section-header
                    title="最近订单"
                    :subtitle="`累计 ${toNumber(overview.order_count)} 笔订单`"
                    more="全部订单"
                    @more="goOrderList()"
                />
                <view v-if="orderCards.length" class="orders">
                    <base-card
                        v-for="order in orderCards"
                        :key="order.id"
                        compact
                        clickable
                        :aria-label="`${order.title}，${order.statusLabel}，查看订单详情`"
                        @tap="goOrderDetail(order)"
                    >
                        <view class="order__top">
                            <text class="order__title ellipsis-2">{{ order.title }}</text>
                            <status-tag
                                :label="order.statusLabel"
                                :tone="order.statusTone"
                                size="sm"
                            />
                        </view>
                        <view class="order__meta">
                            <view class="order__date">
                                <tn-icon name="calendar" :size="24" class="order__meta-icon" />
                                <text>{{ order.dateText }}</text>
                            </view>
                            <status-tag
                                v-if="order.orderStatus === 0 && order.pendingConfirmCount > 0"
                                :label="`待确认 ${order.pendingConfirmCount}`"
                                tone="warning"
                                size="sm"
                                :dot="false"
                            />
                        </view>
                        <view class="order__foot">
                            <text class="order__sn ellipsis">单号 {{ order.orderSn }}</text>
                            <text v-if="order.amountText" class="order__amount">{{
                                order.amountText
                            }}</text>
                            <tn-icon name="right" :size="22" class="order__arrow" />
                        </view>
                    </base-card>
                </view>
                <base-empty
                    v-else
                    status="empty"
                    empty-icon="empty-order"
                    empty-text="暂无订单"
                    desc="新指派给你的订单会显示在这里"
                />

                <section-header title="品牌资产" />
                <base-card compact>
                    <view class="assets">
                        <view
                            v-for="(item, index) in assetEntries"
                            :key="item.key"
                            class="asset"
                            :class="{
                                'asset--wide':
                                    assetEntries.length % 2 === 1 && index === assetEntries.length - 1
                            }"
                            hover-class="is-pressed"
                            aria-role="button"
                            :aria-label="`${item.label}，${item.desc}`"
                            @tap="go(item.path)"
                        >
                            <view class="asset__top">
                                <view class="asset__icon-wrap">
                                    <tn-icon :name="item.icon" :size="30" class="asset__icon" />
                                </view>
                                <text v-if="item.count !== null" class="asset__count">{{
                                    item.count
                                }}</text>
                                <tn-icon v-else name="right" :size="22" class="asset__arrow" />
                            </view>
                            <text class="asset__label">{{ item.label }}</text>
                            <text class="asset__desc">{{ item.desc }}</text>
                        </view>
                    </view>
                </base-card>
            </template>
        </view>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
/**
 * 服务人员工作台。
 *
 * 数据来自 /staff_center/dashboard：profile / overview / todo / recent_orders。
 * 资料、动态、作品、证书编辑入口受 feature_switch.staff_center_write 灰度控制；
 * 订单履约、档期管理、结算收款/补交、候补通知已按 P1 能力陆续接入。
 */
import { computed, reactive, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import {
    ORDER_STATUS_NAME_MAP,
    ORDER_STATUS_TONE,
    resolveStatusLabel,
    resolveTone
} from '@shared/contracts'
import { formatMoney } from '@shared/utils/format'
import { getStaffDashboard } from '@/api/staff-center'
import StaffGuard from '../../components/staff-guard/staff-guard.vue'
import { useStaffGuard } from '@/composables/useStaffGuard'
import { useStaffWriteGate } from '@/composables/useStaffWriteGate'
import { useBadgeStore } from '@/stores/badge'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { navigate } from '@/utils/link'
import { toast } from '@/composables/useToast'
import type { LoadPhase } from '@/api/types'

const { canWrite } = useStaffWriteGate()
const badgeStore = useBadgeStore()
const {
    guardPhase,
    guardReason,
    guardError,
    guardAllowed,
    verify,
    onGuardRetry,
    onGuardAction
} = useStaffGuard(() => load(true))

const phase = ref<LoadPhase>('idle')
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))

const profile = reactive<Record<string, any>>({})
const overview = reactive<Record<string, number>>({})
const todo = reactive<Record<string, number>>({})
const recentOrders = ref<any[]>([])
const avatarFailed = ref(false)
const hasLoaded = ref(false)
const loading = ref(false)

function toNumber(value: unknown) {
    const n = Number(value ?? 0)
    return Number.isFinite(n) ? n : 0
}

function formatBadge(value: unknown) {
    const n = toNumber(value)
    if (n <= 0) return ''
    return n > 99 ? '99+' : String(n)
}

const initial = computed(() => String(profile.name || '服').slice(0, 1))
const ratingText = computed(() => {
    const rating = toNumber(profile.rating)
    return rating > 0 ? rating.toFixed(1) : ''
})
const profileMeta = computed(() => {
    const years = toNumber(profile.experience_years)
    return [
        profile.category_name || '未分类',
        years > 0 ? `${years} 年经验` : '经验待完善'
    ].join(' · ')
})
const auditTone = computed(() => {
    const status = Number(profile.audit_status)
    if (status === 1) return 'success'
    if (status === 2) return 'danger'
    return 'warning'
})

const focusMetrics = computed(() => [
    {
        key: 'today',
        label: '今日服务',
        value: toNumber(todo.today_service_count),
        unit: '项'
    },
    {
        key: 'confirm',
        label: '待确认单',
        value: toNumber(todo.pending_confirm_orders),
        unit: '笔'
    },
    {
        key: 'schedule',
        label: '7 日安排',
        value: toNumber(todo.upcoming_7d_schedule_count),
        unit: '场'
    }
])

const primaryTask = computed(() => {
    const pending = toNumber(todo.pending_confirm_orders)
    if (pending > 0) {
        return {
            key: 'confirm',
            title: `${pending} 笔订单待确认`,
            desc: '及时查看订单内容与服务安排',
            action: '查看订单',
            icon: 'warning',
            tone: 'warning'
        }
    }
    const today = toNumber(todo.today_service_count)
    if (today > 0) {
        return {
            key: 'today',
            title: `今日有 ${today} 项服务`,
            desc: '进入服务日作战面板，查看场地联系与流程安排',
            action: '进入面板',
            icon: 'calendar',
            tone: 'info'
        }
    }
    return null
})

const quickActions = computed(() => [
    {
        key: 'service-day',
        label: '作战面板',
        icon: 'fire',
        badge: formatBadge(todo.today_service_count),
        path: '/packages/pkg-staff/pages/service-day/service-day'
    },
    {
        key: 'order',
        label: '订单管理',
        icon: 'order',
        badge: formatBadge(todo.pending_confirm_orders),
        path: '/packages/pkg-staff/pages/order-list/order-list'
    },
    {
        key: 'schedule',
        label: '档期日历',
        icon: 'calendar',
        badge: formatBadge(todo.upcoming_7d_schedule_count),
        path: '/packages/pkg-staff/pages/schedule/schedule'
    },
    {
        key: 'activity-checkin',
        label: '活动核销',
        icon: 'scan',
        badge: '',
        path: '/packages/pkg-staff/pages/activity-checkin/activity-checkin'
    },
    {
        key: 'card-poster',
        label: '名片海报',
        icon: 'image',
        badge: '',
        path: '/packages/pkg-staff/pages/card-poster/card-poster'
    },
    {
        key: 'copy-templates',
        label: '文案模板',
        icon: 'edit',
        badge: '',
        path: '/packages/pkg-staff/pages/copy-templates/copy-templates'
    },
    {
        key: 'waitlist',
        label: '我的候补',
        icon: 'clock',
        badge: formatBadge(todo.pending_waitlist_count),
        path: '/packages/pkg-staff/pages/waitlist/waitlist'
    },
    {
        key: 'settlement',
        label: '我的结算',
        icon: 'receipt',
        badge: formatBadge(todo.pending_settlement_count),
        path: '/packages/pkg-staff/pages/settlement-list/settlement-list'
    },
    {
        key: 'message',
        label: '通知消息',
        icon: 'message',
        badge: formatBadge(todo.unread_message_count),
        path: '/pages/message/message'
    }
])

const crmEntries = computed(() => {
    const coupleCount = toNumber(overview.couple_count)
    const teamMemberCount = toNumber(overview.team_member_count)
    const pendingFollowup = toNumber(todo.pending_followup_count)
    const isLeader = Boolean(profile.is_team_leader)
    const teamName = String(profile.team_name || '').trim()

    return [
        {
            key: 'couples',
            label: '新人档案',
            desc: coupleCount > 0 ? `${coupleCount} 份档案清单` : '档案·问卷·手记',
            icon: 'book',
            badge: coupleCount > 0 ? `${coupleCount}` : '',
            path: '/packages/pkg-staff/pages/couple-list/couple-list'
        },
        {
            key: 'followups',
            label: '跟进提醒',
            desc: pendingFollowup > 0 ? `${pendingFollowup} 项待跟进` : '婚前彩排备忘',
            icon: 'notice',
            badge: formatBadge(pendingFollowup),
            path: '/packages/pkg-staff/pages/followups/followups'
        },
        {
            key: 'team',
            label: '团队成员',
            desc: teamName
                ? `${isLeader ? '队长' : '队员'} · ${teamName}`
                : '团队排班协同',
            icon: 'team',
            badge: teamMemberCount > 0 ? `${teamMemberCount}人` : '',
            path: '/packages/pkg-staff/pages/team-members/team-members'
        }
    ]
})

const assetEntries = computed(() => {
    const entries = [
        {
            key: 'work',
            label: '作品管理',
            desc: '上传与维护案例',
            icon: 'image',
            count: toNumber(overview.work_count),
            path: '/packages/pkg-staff/pages/work-list/work-list'
        },
        {
            key: 'package',
            label: '套餐管理',
            desc: '服务内容与定价',
            icon: 'gift',
            count: toNumber(overview.package_count),
            path: '/packages/pkg-staff/pages/package-list/package-list'
        },
        {
            key: 'addon',
            label: '附加项',
            desc: '增值服务增删改',
            icon: 'add',
            count: toNumber(overview.addon_count),
            path: '/packages/pkg-staff/pages/addon-list/addon-list'
        },
        {
            key: 'certificate',
            label: '资质证书',
            desc: '认证与荣誉背书',
            icon: 'medal',
            count: null,
            path: '/packages/pkg-staff/pages/certificate-list/certificate-list'
        },
        {
            key: 'dynamic',
            label: '我的动态',
            desc: '管理已发布内容',
            icon: 'comment',
            count: null,
            path: '/packages/pkg-staff/pages/dynamic-list/dynamic-list'
        },
        {
            key: 'dynamicPublish',
            label: '发布动态',
            desc: '图文 / 视频',
            icon: 'edit',
            count: null,
            path: '/packages/pkg-staff/pages/dynamic-publish/dynamic-publish'
        }
    ]
    // 灰度关闭（feature_switch.staff_center_write=0）时不出现写入口；
    // 剩 5 项时最后一项自动独占整行（asset--wide 逻辑不变）
    return canWrite.value ? entries : entries.filter((e) => e.key !== 'dynamicPublish')
})

function packageName(order: any) {
    const names = Array.isArray(order?.package_names)
        ? order.package_names.filter(Boolean)
        : []
    if (names.length > 1) return `${names[0]} 等 ${names.length} 项`
    if (names.length === 1) return String(names[0])

    const items = Array.isArray(order?.items) ? order.items : []
    const direct =
        order?.package_summary ||
        order?.package_name ||
        items[0]?.package_name ||
        items[0]?.item_type_desc
    if (direct) return String(direct)

    const itemCount = toNumber(order?.item_count)
    return itemCount > 0 ? `共 ${itemCount} 个服务项` : '服务订单'
}

function serviceDate(order: any) {
    const items = Array.isArray(order?.items) ? order.items : []
    return String(order?.service_date || items[0]?.service_date || '').trim()
}

function orderLabel(order: any) {
    const mapped = resolveStatusLabel(
        ORDER_STATUS_NAME_MAP as any,
        Number(order?.order_status)
    )
    return String(order?.order_status_desc || mapped.label)
}

function orderTone(order: any) {
    return resolveTone(ORDER_STATUS_TONE as any, Number(order?.order_status))
}

function amountText(order: any) {
    const value = order?.pay_amount ?? order?.total_amount ?? order?.order_amount
    if (value === undefined || value === null || value === '') return ''
    const amount = Number(value)
    if (!Number.isFinite(amount) || amount <= 0) return ''
    return formatMoney(amount, {
        symbol: '¥',
        thousand: true,
        hideZeroDecimals: true
    })
}

const orderCards = computed(() =>
    recentOrders.value.slice(0, 3).map((order) => {
        const date = serviceDate(order)
        return {
            id: Number(order.id || order.order_id),
            title: packageName(order),
            dateText: date ? `服务日期 ${date}` : '服务日期待安排',
            orderSn: String(order.order_sn || '—'),
            orderStatus: Number(order.order_status),
            statusLabel: orderLabel(order),
            statusTone: orderTone(order),
            pendingConfirmCount: toNumber(order.pending_confirm_count),
            amountText: amountText(order)
        }
    })
)

function go(path: string) {
    navigate({ path })
}

function goOrderList(status?: number) {
    navigate({
        path: '/packages/pkg-staff/pages/order-list/order-list',
        query: status === undefined ? {} : { status }
    })
}

function goOrderDetail(order: { id: number }) {
    if (!order.id) {
        toast.warning('订单编号缺失，请刷新后重试')
        return
    }
    navigate({
        path: '/packages/pkg-staff/pages/order-detail/order-detail',
        query: { id: order.id }
    })
}

function onFocusMetric(key: string) {
    if (key === 'today') {
        go('/packages/pkg-staff/pages/service-day/service-day')
        return
    }
    if (key === 'confirm') {
        goOrderList(0)
        return
    }
    go('/packages/pkg-staff/pages/schedule/schedule')
}

function onPrimaryTask() {
    if (!primaryTask.value) return
    onFocusMetric(primaryTask.value.key)
}

function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        load()
        return
    }
    if (kind === 'reselect' || kind === 'back') {
        uni.navigateBack()
        return
    }
    if (kind === 'login') {
        uni.navigateTo({ url: '/pages/login/login' })
    }
}

function assign(target: Record<string, any>, source: any) {
    Object.keys(target).forEach((key) => delete target[key])
    if (source && typeof source === 'object') Object.assign(target, source)
}

async function load(forceGuard = false) {
    if (loading.value) return
    loading.value = true
    try {
        if (!(await verify(forceGuard))) return
        if (!hasLoaded.value) phase.value = 'loading'
        rawError.value = null
        const res = (await getStaffDashboard()) || {}
        // dashboard.profile 是展示裁剪结构，不含 staff id；按实际下发的三段结构校验。
        if (!res.profile || !res.overview || !res.todo) {
            throw new Error('invalid-staff-dashboard')
        }

        assign(profile, res.profile)
        assign(overview, res.overview)
        assign(todo, res.todo)
        // 工作台是「待办」的权威口径：拿它覆盖 badgeStore，回「我的」时数字已经是最新的
        badgeStore.syncFromStaffDashboard(res.todo)
        recentOrders.value = Array.isArray(res.recent_orders) ? res.recent_orders : []
        avatarFailed.value = false
        hasLoaded.value = true
        phase.value = 'ready'
    } catch (e) {
        rawError.value = e
        if (hasLoaded.value) {
            phase.value = 'ready'
            toast.error('刷新失败，请稍后重试')
        } else {
            phase.value = 'error'
        }
    } finally {
        loading.value = false
    }
}

onShow(() => {
    void load()
})

function onRefresh(done: () => void) {
    Promise.resolve(load(true)).finally(done)
}
</script>

<style lang="scss" scoped>
.center {
    box-sizing: border-box;
    padding: var(--sp-2) var(--page-gutter) calc(var(--sp-5) + var(--safe-bottom));

    &__loading {
        padding-top: var(--sp-2);
    }
}

/* 工作名片 */
.hero {
    padding: var(--sp-4);
    border: 1rpx solid var(--c-brand-text);
    border-radius: var(--r-lg);
    background: linear-gradient(145deg, var(--c-dark-1), var(--c-dark-2));
    overflow: hidden;

    &__top,
    &__badges,
    &__role,
    &__status,
    &__profile-link,
    &__person,
    &__name-row,
    &__rating {
        display: flex;
        align-items: center;
    }

    &__top {
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__badges {
        flex: 1;
        min-width: 0;
        flex-wrap: wrap;
        gap: var(--sp-2);
    }

    &__role,
    &__status {
        gap: var(--sp-1);
        min-height: 40rpx;
        font-size: var(--fs-xs);
        line-height: var(--lh-none);
    }

    &__role {
        color: var(--c-brand-soft);
    }

    &__role-icon,
    &__profile-icon {
        color: var(--c-brand-soft);
    }

    &__status {
        color: var(--c-on-dark-2);
    }

    &__status-dot {
        width: 12rpx;
        height: 12rpx;
        border-radius: var(--r-pill);
        background: var(--c-warning-on-dark);

        &.is-active {
            background: var(--c-success-on-dark);
        }
    }

    &__profile-link {
        flex-shrink: 0;
        justify-content: flex-end;
        gap: var(--sp-1);
        min-width: 88rpx;
        min-height: 88rpx;
        margin: calc(-1 * var(--sp-3)) 0;
        font-size: var(--fs-xs);
        color: var(--c-brand-soft);
    }

    &__person {
        gap: var(--sp-3);
        margin-top: var(--sp-2);
    }

    &__avatar {
        flex-shrink: 0;
        width: 120rpx;
        height: 120rpx;
        border: 2rpx solid var(--c-brand-soft);
        border-radius: var(--r-pill);
        background: var(--c-dark-3);

        &--text {
            display: flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
        }
    }

    &__initial {
        font-family: var(--font-serif);
        font-size: var(--fs-2xl);
        color: var(--c-brand-soft);
    }

    &__body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
    }

    &__name-row {
        min-width: 0;
        gap: var(--sp-2);
    }

    &__name {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xl);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
        color: var(--c-on-dark);
    }

    &__rating {
        flex-shrink: 0;
        gap: var(--sp-1);
        padding: var(--sp-1) var(--sp-2);
        border-radius: var(--r-pill);
        background: var(--c-dark-3);
        font-size: var(--fs-xs);
        color: var(--c-on-dark);
    }

    &__rating-star {
        color: var(--c-brand-soft);
    }

    &__meta,
    &__price {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-on-dark-2);
    }

    &__price {
        color: var(--c-brand-soft);
    }

    &__pulse {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: var(--sp-2);
        margin-top: var(--sp-4);
    }
}

.pulse {
    min-width: 0;
    min-height: 128rpx;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: var(--sp-2);
    padding: var(--sp-3) var(--sp-2);
    border: 1rpx solid var(--c-dark-3);
    border-radius: var(--r-md);
    background: var(--c-dark-3);

    &--active {
        border-color: var(--c-brand-soft);
    }

    &__label {
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
        white-space: nowrap;
    }

    &__value-row {
        display: flex;
        align-items: baseline;
        gap: var(--sp-1);
    }

    &__value {
        font-family: var(--font-serif);
        font-size: var(--fs-xl);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-none);
        color: var(--c-on-dark);
        font-variant-numeric: tabular-nums;
    }

    &__unit {
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
    }
}

/* 审核状态与重点待办 */
.audit {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    margin-top: var(--sp-2);
    padding: var(--sp-3);
    border-radius: var(--r-md);

    &--success {
        background: var(--c-success-bg);

        .audit__icon {
            color: var(--c-success);
        }
    }

    &--warning {
        background: var(--c-warning-bg);

        .audit__icon {
            color: var(--c-warning);
        }
    }

    &--danger {
        background: var(--c-danger-bg);

        .audit__icon {
            color: var(--c-danger);
        }
    }

    &__text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
    }

    &__link {
        flex-shrink: 0;
        padding: var(--sp-2) 0 var(--sp-2) var(--sp-2);
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
    }
}

.task {
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    margin-top: var(--sp-2);
    padding: var(--sp-3);
    border: 1rpx solid;
    border-radius: var(--r-md);

    &--warning {
        border-color: var(--c-warning-border);
        background: var(--c-warning-bg);

        .task__icon {
            color: var(--c-warning);
        }
    }

    &--info {
        border-color: var(--c-info-border);
        background: var(--c-info-bg);

        .task__icon {
            color: var(--c-info);
        }
    }

    &__icon-wrap {
        flex-shrink: 0;
        width: 64rpx;
        height: 64rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--r-pill);
        background: var(--c-surface);
    }

    &__body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
        color: var(--c-ink);
    }

    &__desc {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }

    &__action {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        gap: var(--sp-1);
        padding: var(--sp-2) var(--sp-3);
        border-radius: var(--r-pill);
        background: var(--c-action-bg);
        font-size: var(--fs-xs);
        color: var(--c-action-fg);
    }

    &__action-icon {
        color: var(--c-action-fg);
    }
}

/* 四格快捷入口 */
.quick {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: var(--sp-2);

    &__item {
        min-width: 0;
        min-height: 128rpx;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: var(--sp-2);
    }

    &__icon-wrap {
        position: relative;
        width: 64rpx;
        height: 64rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--r-pill);
        background: var(--c-brand-bg);
    }

    &__icon {
        color: var(--c-brand-text);
    }

    &__badge {
        position: absolute;
        top: -12rpx;
        right: -20rpx;
        min-width: 32rpx;
        height: 32rpx;
        box-sizing: border-box;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 var(--sp-1);
        border-radius: var(--r-pill);
        background: var(--c-danger);
    }

    &__badge-text {
        font-size: var(--fs-18);
        line-height: var(--lh-none);
        color: var(--c-ink-inverse);
        font-variant-numeric: tabular-nums;
    }

    &__label {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink);
        white-space: nowrap;
    }
}

/* 最近订单 */
.orders {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
}

.order {
    &__top {
        display: flex;
        align-items: flex-start;
        gap: var(--sp-3);
    }

    &__title {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-body);
        color: var(--c-ink);
    }

    &__meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: var(--sp-2);
        margin-top: var(--sp-2);
    }

    &__date {
        display: flex;
        align-items: center;
        gap: var(--sp-1);
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }

    &__meta-icon,
    &__arrow {
        color: var(--c-ink-3);
    }

    &__foot {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
        margin-top: var(--sp-3);
        padding-top: var(--sp-2);
        border-top: 1rpx solid var(--c-line);
    }

    &__sn {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__amount {
        flex-shrink: 0;
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-text);
        font-variant-numeric: tabular-nums;
    }
}

/* 品牌资产 */
.assets {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--sp-2);
}

.asset {
    min-width: 0;
    padding: var(--sp-3);
    border-radius: var(--r-20);
    background: var(--c-neutral-bg);

    &--wide {
        grid-column: 1 / -1;
    }

    &__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__icon-wrap {
        width: 56rpx;
        height: 56rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--r-pill);
        background: var(--c-brand-bg);
    }

    &__icon {
        color: var(--c-brand-text);
    }

    &__count {
        font-family: var(--font-serif);
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-text);
        font-variant-numeric: tabular-nums;
    }

    &__arrow {
        color: var(--c-ink-3);
    }

    &__label,
    &__desc {
        display: block;
    }

    &__label {
        margin-top: var(--sp-2);
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
        color: var(--c-ink);
    }

    &__desc {
        margin-top: var(--sp-1);
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }
}

/* 客户经营与团队 */
.ops-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: var(--sp-2);
}

.ops-card {
    min-width: 0;
    padding: var(--sp-3);
    border-radius: var(--r-md);
    background: var(--c-surface);
    border: 1rpx solid var(--c-line);
    display: flex;
    flex-direction: column;
    gap: var(--sp-1);

    &__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-1);
    }

    &__icon-wrap {
        width: 52rpx;
        height: 52rpx;
        border-radius: var(--r-pill);
        background: var(--c-brand-bg);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__icon {
        color: var(--c-brand-text);
    }

    &__badge {
        padding: 2rpx var(--sp-1);
        border-radius: var(--r-pill);
        background: var(--c-brand-bg);
        font-size: var(--fs-18);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-text);
        font-variant-numeric: tabular-nums;
    }

    &__title {
        margin-top: var(--sp-1);
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__desc {
        font-size: var(--fs-20);
        color: var(--c-ink-3);
    }
}
</style>
