<template>
    <page-shell title="经营驾驶舱" bg="page" :show-back="true">
        <view class="page-wrap board">
            <view v-if="phase === 'loading' || phase === 'idle'" class="page-pad">
                <base-skeleton :rows="4" />
            </view>

            <base-empty
                v-else-if="phase === 'denied'"
                status="empty"
                empty-icon="empty-permission"
                :empty-text="FB.EMPTY_PERMISSION"
                desc="经营数据只对已授权的管理员开放，如需查看请联系超级管理员"
                fixed
            />

            <base-empty
                v-else-if="phase === 'error'"
                status="error"
                :error-kind="view?.errorKind || 'unknown'"
                :error-text="view?.title"
                :desc="view?.desc"
                :primary-action="view?.primaryAction"
                fixed
                @primary="onPrimary"
                @retry="load"
            />

            <template v-else>
                <!-- 周期：默认本月至今，服务端按 start_date/end_date 统计 -->
                <view class="period">
                    <text class="period__text">{{ periodText }}</text>
                </view>

                <!-- 收入：一眼看到本期与环比 -->
                <base-card>
                    <view class="hero">
                        <text class="hero__label">本期净收入</text>
                        <price size="xl" :content="overview.net_income ?? 0" color="var(--c-ink)" />
                        <view class="hero__growth" :class="growthClass(overview.income_growth)">
                            <tn-icon
                                :name="growthIcon(overview.income_growth)"
                                :size="20"
                                class="hero__growth-icon"
                            />
                            <text class="hero__growth-text">
                                {{ growthText(overview.income_growth) }} 环比
                            </text>
                        </view>
                    </view>

                    <view class="mini">
                        <view v-for="m in incomeRows" :key="m.key" class="mini__item">
                            <text class="mini__num">{{ m.value }}</text>
                            <text class="mini__label">{{ m.label }}</text>
                        </view>
                    </view>
                </base-card>

                <!-- 团队与产能 -->
                <section-header title="团队与档期" :bar="true" />
                <view class="sum">
                    <view v-for="s in teamRows" :key="s.key" class="sum__item">
                        <text class="sum__num">{{ s.value }}</text>
                        <text class="sum__label">{{ s.label }}</text>
                    </view>
                </view>

                <base-card>
                    <view class="rate">
                        <view class="rate__head">
                            <text class="rate__label"
                                >{{ capacity.month_label || '本月' }} 档期占用</text
                            >
                            <text class="rate__value">{{ capacity.booking_rate ?? 0 }}%</text>
                        </view>
                        <!-- 进度条：宽度用内联 style，刻度来自服务端，不用动画 -->
                        <view class="rate__track">
                            <view
                                class="rate__fill"
                                :style="{ width: barWidth(capacity.booking_rate) }"
                            />
                        </view>
                        <text class="rate__hint">
                            已占 {{ capacity.month_occupied_slots ?? 0 }} / 共
                            {{ capacity.month_total_slots ?? 0 }} 个档期
                        </text>
                    </view>
                </base-card>

                <!-- 待办 -->
                <section-header title="待处理" :bar="true" />
                <base-card>
                    <view
                        v-for="(t, i) in todoRows"
                        :key="t.key"
                        class="todo__row"
                        :class="{ 'todo__row--divided': i > 0 }"
                    >
                        <text class="todo__label">{{ t.label }}</text>
                        <text class="todo__num">{{ t.value }}</text>
                    </view>
                </base-card>

                <!-- 成员负载 -->
                <template v-if="members.length">
                    <section-header title="成员负载" :bar="true" />
                    <view class="members">
                        <base-card v-for="m in members" :key="m.id" compact>
                            <view class="member">
                                <image
                                    v-if="m.avatar"
                                    class="member__avatar"
                                    :src="m.avatar"
                                    mode="aspectFill"
                                    aria-label="头像"
                                />
                                <view v-else class="member__avatar member__avatar--text">
                                    <text class="member__initial">
                                        {{ String(m.name || '员').slice(0, 1) }}
                                    </text>
                                </view>
                                <view class="member__body">
                                    <text class="member__name ellipsis">{{ m.name }}</text>
                                    <text class="member__meta">
                                        近 30 天 {{ m.recent_order_count ?? 0 }} 单 · 待跟进
                                        {{ m.follow_up_count ?? 0 }}
                                    </text>
                                </view>
                                <text class="member__slots">
                                    {{ m.upcoming_booked_slots ?? 0 }} 档
                                </text>
                            </view>
                        </base-card>
                    </view>
                </template>
            </template>
        </view>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
/**
 * 经营驾驶舱（管理员）。
 *
 * 小程序用户没有后台 token，服务端用「la_admin.user_id 绑定 + la_system_menu 节点」做鉴权桥接，
 * 所以这里先问 /dashboard/access 再决定是画数据还是画「无权限」—— 不发无谓的 403 请求。
 */
import { computed, reactive, ref } from 'vue'
import { onLoad, onPullDownRefresh } from '@dcloudio/uni-app'
import {
    getDashboardAccess,
    getDashboardOverview,
    getDashboardTeamOverview
} from '@/api/staff-center'
import { FB } from '@/constants/feedback'
import { formatMoney } from '@shared/utils/format'
import { resolveErrorView } from '@/utils/resolveErrorView'
import type { LoadPhase } from '@/api/types'

type Phase = LoadPhase | 'denied'

const phase = ref<Phase>('idle')
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))

const overview = reactive<Record<string, any>>({})
const capacity = reactive<Record<string, any>>({})
const team = reactive<Record<string, any>>({})
const todo = reactive<Record<string, any>>({})
const members = ref<any[]>([])

const periodText = computed(() => {
    const p = overview.period || {}
    return p.start_date && p.end_date ? `${p.start_date} 至 ${p.end_date}` : '本月至今'
})

const incomeRows = computed(() => [
    { key: 'income', label: '收款', value: money(overview.total_income) },
    { key: 'refund', label: '退款', value: money(overview.total_refund) },
    { key: 'orders', label: '成交单数', value: String(overview.receipt_order_count ?? 0) },
    { key: 'avg', label: '客单价', value: money(overview.avg_order_amount) }
])

const teamRows = computed(() => [
    { key: 'total', label: '人员总数', value: team.total_staff ?? 0 },
    { key: 'active', label: '在职可接单', value: team.active_staff ?? 0 },
    { key: 'recommend', label: '推荐位', value: team.recommended_staff ?? 0 },
    { key: 'booked', label: '已订档期', value: capacity.month_booked_slots ?? 0 }
])

const todoRows = computed(() => [
    { key: 'confirm', label: '待确认订单', value: todo.pending_confirm ?? 0 },
    { key: 'pay', label: '待支付订单', value: todo.pending_pay ?? 0 },
    { key: 'service', label: '服务中订单', value: todo.in_service ?? 0 },
    { key: 'waitlist', label: '候补待跟进', value: todo.waitlist_total ?? 0 }
])

/** 金额：过万折成「万」，避免驾驶舱里一排 6 位数挤爆；格式化统一走 shared/utils/format */
function money(v: any) {
    const n = Number(v || 0)
    if (n >= 10000) return `${formatMoney(n / 10000, { symbol: '¥' })} 万`
    return formatMoney(n, { symbol: '¥' })
}

function growthIcon(v: any) {
    const n = Number(v || 0)
    if (n > 0) return 'up'
    if (n < 0) return 'down'
    return 'right'
}

function growthClass(v: any) {
    const n = Number(v || 0)
    if (n > 0) return 'hero__growth--up'
    if (n < 0) return 'hero__growth--down'
    return 'hero__growth--flat'
}

function growthText(v: any) {
    const n = Number(v || 0)
    const sign = n > 0 ? '+' : ''
    return `${sign}${n}%`
}

function barWidth(rate: any) {
    const n = Math.max(0, Math.min(100, Number(rate || 0)))
    return `${n}%`
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

function assign(target: Record<string, any>, src: any) {
    Object.keys(target).forEach((k) => delete target[k])
    if (src && typeof src === 'object') Object.assign(target, src)
}

async function load() {
    if (!overview.total_income && phase.value !== 'ready') phase.value = 'loading'
    rawError.value = null
    try {
        const access = (await getDashboardAccess()) || {}
        if (!access.can_view) {
            phase.value = 'denied'
            return
        }
        // 两个节点分别授权：任一失败只降级对应区块，两个都拿不到才算错误
        let firstError: unknown = null
        const [ov, tm] = await Promise.all([
            getDashboardOverview().catch((e) => {
                firstError = firstError ?? e
                return null
            }),
            getDashboardTeamOverview().catch((e) => {
                firstError = firstError ?? e
                return null
            })
        ])
        if (!ov && !tm) throw firstError || new Error('dashboard unavailable')
        assign(overview, ov)
        if (tm) {
            assign(team, tm.team)
            assign(capacity, tm.capacity)
            assign(todo, tm.todo)
            members.value = Array.isArray(tm.members) ? tm.members : []
        }
        phase.value = 'ready'
    } catch (e) {
        rawError.value = e
        phase.value = 'error'
    }
}

onLoad(() => load())

onPullDownRefresh(async () => {
    try {
        await load()
    } finally {
        uni.stopPullDownRefresh()
    }
})
</script>

<style lang="scss" scoped>
.board {
    padding-top: var(--sp-2);
    padding-bottom: calc(var(--sp-5) + var(--safe-bottom));
}

.period {
    padding: 0 var(--sp-1) var(--sp-2);

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

/* ---------------- 收入主卡 ---------------- */
.hero {
    display: flex;
    flex-direction: column;
    gap: var(--sp-1);

    &__label {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__growth {
        display: flex;
        align-items: center;
        gap: var(--sp-1);
        margin-top: var(--sp-1);

        &--up {
            color: var(--c-danger);
        }

        &--down {
            color: var(--c-success);
        }

        &--flat {
            color: var(--c-ink-3);
        }
    }

    &__growth-icon {
        color: inherit;
    }

    &__growth-text {
        font-size: var(--fs-xs);
        font-variant-numeric: tabular-nums;
    }
}

.mini {
    display: flex;
    margin-top: var(--sp-4);
    padding-top: var(--sp-3);
    border-top: 1rpx solid var(--c-line);

    &__item {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: var(--sp-1);

        & + & {
            border-left: 1rpx solid var(--c-line);
        }
    }

    &__num {
        font-family: var(--font-serif);
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        font-variant-numeric: tabular-nums;
    }

    &__label {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        white-space: nowrap;
    }
}

/* ---------------- 四格 ---------------- */
.sum {
    display: flex;
    margin: 0 var(--sp-1);

    &__item {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: var(--sp-1);
        padding: var(--sp-3) 0;

        & + & {
            border-left: 1rpx solid var(--c-line);
        }
    }

    &__num {
        font-family: var(--font-serif);
        font-size: var(--fs-xl);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
        color: var(--c-brand-text);
        font-variant-numeric: tabular-nums;
    }

    &__label {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
        white-space: nowrap;
    }
}

/* ---------------- 档期占用 ---------------- */
.rate {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);

    &__head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__label {
        font-size: var(--fs-md);
        color: var(--c-ink);
    }

    &__value {
        font-family: var(--font-serif);
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-text);
        font-variant-numeric: tabular-nums;
    }

    &__track {
        height: 12rpx;
        border-radius: var(--r-pill);
        background: var(--c-neutral-bg);
        overflow: hidden;
    }

    &__fill {
        height: 100%;
        border-radius: var(--r-pill);
        background: var(--c-brand-text);
    }

    &__hint {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

/* ---------------- 待办 ---------------- */
.todo {
    &__row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
        padding: var(--sp-3) 0;
        min-height: 88rpx;

        &--divided {
            border-top: 1rpx solid var(--c-line);
        }
    }

    &__label {
        font-size: var(--fs-md);
        color: var(--c-ink);
    }

    &__num {
        font-family: var(--font-serif);
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-danger);
        font-variant-numeric: tabular-nums;
    }
}

/* ---------------- 成员 ---------------- */
.members {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
}

.member {
    display: flex;
    align-items: center;
    gap: var(--sp-3);

    &__avatar {
        width: 80rpx;
        height: 80rpx;
        border-radius: var(--r-pill);
        background: var(--c-neutral-bg);
        flex-shrink: 0;

        &--text {
            display: flex;
            align-items: center;
            justify-content: center;
        }
    }

    &__initial {
        font-family: var(--font-serif);
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-text);
    }

    &__body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
    }

    &__name {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__meta {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }

    &__slots {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        font-variant-numeric: tabular-nums;
    }
}
</style>
