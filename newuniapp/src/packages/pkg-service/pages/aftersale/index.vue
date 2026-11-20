<template>
    <!--
        18 订单售后（设计稿 3784–3858）
        资格全部来自真数据，前端不自己判定：
        · order.getOrderDetailV2 → can_user_refund / refundable_amount / paid_amount / order_status
        · order_change.checkCanChange → can_change / message（message 原样上墙，不改写）
        客服入口恒可用（兜底）。
    -->
    <page-shell bg="page" title="售后与投诉">
        <view v-if="loading" class="pad sk">
            <base-skeleton :rows="3" />
        </view>

        <base-empty
            v-else-if="error"
            status="error"
            :error-kind="view?.errorKind || 'unknown'"
            :error-text="view?.title"
            :desc="view?.desc"
            :primary-action="view?.primaryAction"
            fixed
            @primary="onPrimary"
            @retry="load"
        />

        <view v-else class="as">
            <!-- 先亮订单再给选项：售后最容易出错的是申请错单 -->
            <view class="ord">
                <image
                    v-if="order.staffAvatar"
                    class="ord__av"
                    :src="order.staffAvatar"
                    mode="aspectFill"
                />
                <view v-else class="ord__av ord__av--text">
                    {{ (order.staffName || '订').slice(0, 1) }}
                </view>
                <view class="ord__m">
                    <text class="ord__title">{{ orderTitle }}</text>
                    <text class="ord__sub">{{ orderSub }}</text>
                </view>
                <view v-if="order.paidAmount !== ''" class="ord__r">
                    <text class="ord__r-label">已付</text>
                    <text class="ord__r-amount">¥{{ order.paidAmount }}</text>
                </view>
            </view>

            <!-- 三条诉求：改期 / 退款 / 客服，按发生频率排序，客服最后兜底 -->
            <base-card class="blk" :flush="true">
                <view
                    v-for="(e, i) in entries"
                    :key="e.key"
                    hover-class="is-pressed"
                    class="entry"
                    :class="{ 'entry--last': i === entries.length - 1, 'entry--off': !e.enabled }"
                    :aria-role="e.enabled ? 'button' : 'text'"
                    :aria-label="`${e.title}${e.enabled ? '' : '，不可用'}`"
                    @tap="onEntry(e)"
                >
                    <view class="entry__ic">
                        <tn-icon :name="e.icon" :size="30" />
                    </view>
                    <view class="entry__t">
                        <view class="entry__head">
                            <text class="entry__title">{{ e.title }}</text>
                            <text v-if="e.badge" class="entry__badge">{{ e.badge }}</text>
                        </view>
                        <!-- 不可用原因原样用服务端 message，不改写 -->
                        <text class="entry__desc">{{ e.desc }}</text>
                    </view>
                    <tn-icon v-if="e.enabled" name="right" :size="22" />
                </view>
            </base-card>

            <!-- 处理中：已提交的申请，避免用户重复提交 -->
            <view v-if="pending" class="rec">
                <view class="rec__h">
                    <text class="rec__title">{{ pending.title }}</text>
                    <text class="rec__time">{{ pending.time }}</text>
                </view>
                <text class="rec__desc">{{ pending.desc }}</text>
            </view>

            <!-- FAQ：只给问题，答案就地展开 -->
            <base-card class="blk">
                <section-header title="常见问题" flush />
                <view class="faq">
                    <view v-for="(f, i) in FAQ" :key="f.q" class="faq__row">
                        <view
                            hover-class="is-pressed"
                            class="faq__head"
                            :class="{ 'faq__head--last': i === FAQ.length - 1 && openFaq !== i }"
                            aria-role="button"
                            :aria-label="`${f.q}，${openFaq === i ? '收起' : '展开'}`"
                            @tap="toggleFaq(i)"
                        >
                            <text class="faq__q">{{ f.q }}</text>
                            <tn-icon :name="openFaq === i ? 'up' : 'down'" :size="22" />
                        </view>
                        <text v-if="openFaq === i" class="faq__a">{{ f.a }}</text>
                    </view>
                </view>
            </base-card>

            <text class="priv">改期与退款均由专属顾问人工审核，审核结果通过服务号通知。</text>

            <view
                hover-class="is-pressed"
                class="end"
                aria-role="button"
                aria-label="查看售后服务规则"
                @tap="goRule"
            >
                <text class="end__text"
                    >查看完整<text class="end__name">《售后服务规则》</text></text
                >
                <tn-icon name="right" :size="18" />
            </view>
        </view>
    </page-shell>
    <app-toast />
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { getOrderDetailV2 } from '@/api/order'
import { checkCanChange, getChangeList } from '@/api/change'
import { navigate } from '@/utils/link'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { report } from '@/platform/report'

/** ✘ G-17：FAQ 无接口，三条按现有处理口径写，不含未定义的费用比例 */
const FAQ: Array<{ q: string; a: string }> = [
    {
        q: '改期后已付的定金还能用吗',
        a: '改期成功后已付款项会转到新档期，不会重复收款。若新档期价格不同，差额由顾问确认后告知，不会直接从定金里扣。'
    },
    {
        q: '审核期间可以撤销申请吗',
        a: '可以。联系专属客服说明需要撤销即可；在顾问确认新档期之前，原档期一直为你保留，不会二次售出。'
    },
    {
        q: '退款多久能到账',
        a: '可退金额按《售后服务规则》计算后原路退回。具体到账时间以支付渠道与审核结果为准，审核结果会通过服务号通知。'
    }
]

interface EntryItem {
    key: 'change' | 'refund' | 'support'
    icon: string
    title: string
    desc: string
    enabled: boolean
    badge?: string
}

const loading = ref(true)
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))
const error = computed(() => !!rawError.value)

const orderId = ref(0)
const openFaq = ref(-1)

const order = reactive({
    orderSn: '',
    statusDesc: '',
    status: -1,
    serviceDate: '',
    staffName: '',
    staffAvatar: '',
    packageName: '',
    paidAmount: '',
    /** 服务端可退金额；'' 表示字段缺失（不显示，不补 0） */
    refundable: '' as string,
    canRefund: false
})

const change = reactive<{ can_change: boolean | null; message: string }>({
    can_change: null,
    message: ''
})

const pending = ref<{
    title: string
    time: string
    desc: string
} | null>(null)

const orderTitle = computed(() => {
    const name = order.staffName ? `${order.staffName} · ` : ''
    const pkg = order.packageName || ''
    if (name && pkg) return `${order.staffName} · ${pkg}`
    if (name) return order.staffName
    return order.orderSn || '订单'
})

const orderSub = computed(() => {
    const d = order.serviceDate
    const dateText =
        d.length >= 10
            ? `${d.replace(/-/g, '.')} 周${'日一二三四五六'[new Date(`${d}T00:00:00`).getDay()]}`
            : ''
    return [dateText, order.orderSn ? `订单 ${order.orderSn}` : ''].filter(Boolean).join(' · ')
})

/** 改期入口：can_change 明确为假 → 置灰并原样显示 message */
const changeEnabled = computed(() => {
    if (pending.value) return false // 已有在审申请，避免重复提交
    if (change.can_change === null) return true // 字段缺失时不禁用，交由提交页拦
    return Boolean(change.can_change)
})

/** 退款入口：can_user_refund 为真 且 可退金额 > 0 且金额字段存在 */
const refundEnabled = computed(() => {
    if (!order.canRefund) return false
    if (order.refundable === '') return false
    return Number(order.refundable) > 0
})

const entries = computed<EntryItem[]>(() => [
    {
        key: 'change',
        icon: 'calendar',
        title: '改期申请',
        badge: pending.value ? '处理中' : undefined,
        desc: changeEnabled.value
            ? '更换婚礼日期或主持时段，差价多退少补'
            : change.message ||
              (pending.value ? '你已有一笔改期申请在处理中' : '当前订单暂不支持改期，请联系客服'),
        enabled: changeEnabled.value
    },
    {
        key: 'refund',
        icon: 'receipt',
        title: '退款申请',
        desc: refundEnabled.value
            ? order.refundable !== ''
                ? `按《售后服务规则》计算可退金额 ¥${order.refundable}，原路退回`
                : '按《售后服务规则》计算可退金额，原路退回'
            : '当前订单状态不支持自助退款，请联系客服',
        enabled: refundEnabled.value
    },
    {
        key: 'support',
        icon: 'headset',
        title: '人工客服',
        desc: '找到专属客服管家，排期与款项都能问',
        enabled: true
    }
])

/** 防御式读取：退款两项不在 OrderDetailResponse 的公开类型里，按可选读，缺了当没有 */
function pickRefund(res: any) {
    const pay = res?.pay || {}
    const canRaw = pay.can_user_refund ?? res?.can_user_refund ?? res?.order?.can_user_refund
    const amountRaw =
        pay.refundable_amount ?? res?.refundable_amount ?? res?.order?.refundable_amount
    order.canRefund = canRaw !== undefined && canRaw !== null && Number(canRaw) === 1
    order.refundable =
        amountRaw === undefined || amountRaw === null || amountRaw === '' ? '' : String(amountRaw)
}

async function load() {
    loading.value = true
    rawError.value = null
    try {
        const [detailRes, changeRes] = await Promise.all([
            getOrderDetailV2(orderId.value),
            checkCanChange({ order_id: orderId.value }).catch(() => null)
        ])

        const res: any = detailRes || {}
        const o = res.order || {}
        const item = (res.items || [])[0] || {}
        order.orderSn = o.order_sn || ''
        order.status = typeof o.order_status === 'number' ? o.order_status : -1
        order.statusDesc = o.order_status_desc || ''
        order.serviceDate = o.service_date || ''
        order.staffName = item?.staff?.name || ''
        order.staffAvatar = item?.staff?.avatar || ''
        order.packageName = item?.package?.name || ''
        order.paidAmount =
            o.paid_amount === undefined || o.paid_amount === null || o.paid_amount === ''
                ? ''
                : String(o.paid_amount)
        pickRefund(res)

        const c: any = changeRes || {}
        change.message = String(c.message || c.reason || '')
        const can = c.can_change ?? c.canChange ?? c.enabled
        change.can_change =
            can === undefined || can === null ? null : Number(can) === 1 || can === true

        // 处理中卡片：非关键路径，失败只隐藏，不影响两个入口
        loadPending()
    } catch (e) {
        rawError.value = e
    } finally {
        loading.value = false
    }
}

async function loadPending() {
    try {
        const res: any = await getChangeList({ order_id: orderId.value, page: 1, page_size: 3 })
        const arr: any[] = Array.isArray(res) ? res : res?.lists || res?.data || []
        // 只展示待审核单据（契约 OrderChangeStatus.PENDING = 0；后端字段 change_status，历史误读 status/1 双重错误）
        const one = (arr || []).find((i) => Number(i.change_status) === 0)
        if (!one) {
            pending.value = null
            return
        }
        pending.value = {
            title: `${one.change_type === 1 || !one.change_type ? '改期申请' : '变更申请'} · ${
                one.change_status_desc || '待审核'
            }`,
            time: one.create_time
                ? `${String(one.create_time).slice(5, 10).replace('-', '.')} 提交`
                : '',
            desc: one.reason || '专属顾问会在 24 小时内与你确认档期与差价。'
        }
    } catch {
        pending.value = null
        report('api_error', { endpoint: 'order_change.lists', message: '售后页处理中单据加载失败' })
    }
}

function toggleFaq(i: number) {
    openFaq.value = openFaq.value === i ? -1 : i
}

function onEntry(e: EntryItem) {
    if (!e.enabled) return
    if (e.key === 'support') {
        navigate({
            path: '/packages/pkg-service/pages/concierge/concierge',
            query: { orderId: orderId.value }
        })
        return
    }
    navigate({
        path: '/packages/pkg-service/pages/aftersale/ticket-create',
        query: { orderId: orderId.value, type: e.key === 'refund' ? 'refund' : 'change' }
    })
}

function goRule() {
    navigate({ path: '/pages/agreement/agreement', query: { type: 'aftersale' } })
}

function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'login') {
        navigate({ path: '/pages/login/login' })
        return
    }
    load()
}

onLoad((options: any) => {
    orderId.value = Number(options?.orderId || options?.order_id || options?.id || 0) || 0
})

/** 从 23 返回时可能刚提交过申请，重新拉一次（含处理中卡片） */
onShow(() => {
    if (!orderId.value) {
        loading.value = false
        rawError.value = { code: -1, message: '缺少订单信息' }
        return
    }
    load()
})
</script>

<style lang="scss" scoped>
.pad {
    padding: 0 42rpx;
}
.sk {
    padding-top: var(--sp-3);
}
.as {
    padding: 0 42rpx calc(var(--sp-5) + var(--safe-bottom));
}

/* ---------- 订单摘要条 ---------- */
.ord {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    margin-top: var(--sp-2);
    padding: var(--sp-3);
    border-radius: var(--r-md);
    background: var(--c-surface);
    border: 1rpx solid var(--c-line);

    &__av {
        width: 72rpx;
        height: 72rpx;
        border-radius: var(--r-sm);
        flex: none;
        background: var(--c-neutral-bg);

        &--text {
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--font-serif);
            font-size: var(--fs-md);
            color: var(--c-ink-3);
        }
    }

    &__m {
        flex: 1;
        min-width: 0;
    }

    &__title {
        display: block;
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    &__sub {
        display: block;
        margin-top: var(--sp-1);
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
        overflow-wrap: anywhere;
    }

    &__r {
        flex: none;
        text-align: right;
    }

    &__r-label {
        display: block;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__r-amount {
        display: block;
        margin-top: 2rpx;
        font-family: var(--font-serif);
        font-size: 28rpx;
        color: var(--c-ink);
    }
}

/* ---------- 三条入口 ---------- */
.blk {
    margin-top: var(--sp-3);
}

.entry {
    /* 行尾箭头；&-title / &-desc / &-badge 各自声明了颜色 */
    color: var(--c-ink-3);
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    min-height: 128rpx;
    padding: var(--sp-3) var(--sp-4);
    border-bottom: 1rpx solid var(--c-line);

    &--last {
        border-bottom: 0;
    }

    &--off {
        opacity: 0.62;
    }

    &__ic {
        /* 入口图标，此容器只放图标 */
        color: var(--c-ink-2);
        width: 64rpx;
        height: 64rpx;
        border-radius: var(--r-sm);
        flex: none;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--c-surface-2);
        border: 1rpx solid var(--c-line);
    }

    &__t {
        flex: 1;
        min-width: 0;
    }

    &__head {
        /* FAQ 展开箭头；&-q 自带 --c-ink-2 */
        color: var(--c-ink-3);
        display: flex;
        align-items: center;
        gap: var(--sp-2);
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        letter-spacing: 0.02em;
        color: var(--c-ink);
    }

    &__badge {
        flex: none;
        height: 32rpx;
        padding: 0 var(--sp-1);
        border-radius: var(--r-xs);
        font-size: var(--fs-xs);
        line-height: 32rpx;
        color: var(--c-neutral);
        background: var(--c-neutral-bg);
        border: 1rpx solid var(--c-neutral-border);
    }

    &__desc {
        display: block;
        margin-top: var(--sp-1);
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
        overflow-wrap: anywhere;
    }
}

/* ---------- 处理中卡片 ---------- */
.rec {
    margin-top: var(--sp-3);
    padding: var(--sp-3) var(--sp-4);
    border-radius: var(--r-md);
    background: var(--c-warning-bg);
    border: 1rpx solid var(--c-warning-border);

    &__h {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-warning);
    }

    &__time {
        font-family: var(--font-serif);
        font-size: var(--fs-xs);
        color: var(--c-warning);
    }

    &__desc {
        display: block;
        margin-top: var(--sp-2);
        font-size: var(--fs-xs);
        line-height: var(--lh-loose);
        color: var(--c-warning);
    }
}

/* ---------- FAQ ---------- */
.faq {
    margin-top: var(--sp-2);

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-3);
        min-height: 88rpx;
        border-bottom: 1rpx solid var(--c-line);

        &--last {
            border-bottom: 0;
        }
    }

    &__q {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-md);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
        overflow-wrap: anywhere;
    }

    &__a {
        display: block;
        padding: var(--sp-2) 0 var(--sp-3);
        font-size: var(--fs-xs);
        line-height: var(--lh-loose);
        color: var(--c-ink-3);
        border-bottom: 1rpx solid var(--c-line);
        overflow-wrap: anywhere;
    }
}

/* ---------- 页尾 ---------- */
.priv {
    display: block;
    margin-top: var(--sp-4);
    font-size: var(--fs-xs);
    line-height: var(--lh-loose);
    color: var(--c-ink-3);
}

.end {
    /* 箭头；&-text / &-name 自带颜色 */
    color: var(--c-ink-3);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--sp-1);
    margin-top: var(--sp-3);
    padding-top: var(--sp-4);
    border-top: 1rpx solid var(--c-line);

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__name {
        color: var(--c-ink-2);
    }
}
</style>
