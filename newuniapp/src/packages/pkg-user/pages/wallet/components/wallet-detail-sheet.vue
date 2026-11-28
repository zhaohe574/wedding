<template>
    <!-- 对账详情：磁吸半屏抽屉（可上推到全屏）。标题 / 关闭 / 脚部安全区由 snap-sheet 统一承担；
         原 .sheet / __head / __body / __foot 包裹层已去掉，样式扁平化 -->
    <snap-sheet v-model="visible" title="对账详情" :enable-full="true" :has-foot="true">
        <view v-if="detail" class="detail-body">
            <!-- 顶部主卡：带符号金额 + 状态 + 标题 -->
            <view class="summary">
                <view class="summary__amount-row">
                    <text class="summary__amount t-num">{{ amountText }}</text>
                    <status-tag :label="detail.status_label" :tone="detail.status_tone" size="sm" />
                </view>
                <text class="summary__title">{{ detail.title }}</text>
            </view>

            <!-- 交易明细 -->
            <view class="block">
                <text class="block__title">交易明细</text>
                <view v-for="row in detailRows" :key="row.key" class="row">
                    <text class="row__k">{{ row.label }}</text>
                    <view
                        v-if="row.copy && row.value"
                        class="row__v row__v--action"
                        hover-class="is-pressed"
                        @tap.stop="copyValue(row.value)"
                    >
                        <text class="row__text t-num">{{ row.value }}</text>
                        <view class="row__copy">
                            <tn-icon name="copy" :size="22" class="row__copy-ic" />
                            <text class="row__copy-text">复制</text>
                        </view>
                    </view>
                    <text v-else class="row__v t-num">{{ row.value }}</text>
                </view>
            </view>

            <!-- 退款子项（按支付方式拆分） -->
            <view v-if="refundItems.length" class="block">
                <text class="block__title">退款明细</text>
                <view v-for="item in refundItems" :key="item.id" class="refund">
                    <view class="refund__head">
                        <text class="refund__amount t-num">¥{{ money(item.refund_amount) }}</text>
                        <text class="refund__way ellipsis">{{ item.pay_way_label }}</text>
                        <text class="refund__status">{{ item.refund_status_label }}</text>
                    </view>
                    <view
                        v-if="item.out_refund_no"
                        class="refund__line"
                        hover-class="is-pressed"
                        @tap.stop="copyValue(item.out_refund_no)"
                    >
                        <text class="refund__line-text ellipsis t-num">{{ item.out_refund_no }}</text>
                        <tn-icon name="copy" :size="22" class="refund__line-ic" />
                    </view>
                    <view
                        v-if="item.third_refund_no"
                        class="refund__line"
                        hover-class="is-pressed"
                        @tap.stop="copyValue(item.third_refund_no)"
                    >
                        <text class="refund__line-text ellipsis t-num">
                            {{ item.third_refund_no }}
                        </text>
                        <tn-icon name="copy" :size="22" class="refund__line-ic" />
                    </view>
                    <text v-if="item.refund_time_text" class="refund__time t-num">
                        {{ item.refund_time_text }}
                    </text>
                </view>
            </view>

            <!-- 线下凭证 -->
            <view v-if="voucherUrl" class="block">
                <text class="block__title">线下凭证</text>
                <image
                    class="voucher-img"
                    :src="voucherUrl"
                    mode="aspectFill"
                    aria-label="查看线下支付凭证"
                    @tap="previewVoucher"
                />
            </view>
        </view>

        <template #foot>
            <view v-if="detail" class="foot">
                <view v-if="canViewOrder" class="foot-btn">
                    <base-button text="查看关联订单" variant="ink" size="md" @click="goOrder" />
                </view>
                <view v-else-if="canViewActivity" class="foot-btn">
                    <base-button text="查看我的活动" variant="ink" size="md" @click="goActivity" />
                </view>
                <view v-else class="foot-btn">
                    <base-button text="关闭" variant="ink" size="md" @click="visible = false" />
                </view>
            </view>
        </template>
    </snap-sheet>
</template>

<script setup lang="ts">
/**
 * 对账详情抽屉。
 *
 * 打开时先用列表项乐观渲染基础字段，再按 flow_key 拉取详情；
 * 详情失败不阻断基础字段展示，只做轻提示。
 * 只读展示 + 复制 + 凭证预览，不提供任何资金变动入口。
 */
import { computed, ref, watch } from 'vue'
import { formatMoney } from '@shared/utils/format'
import { getWalletFlowDetail } from '@/api/wallet'
import type {
    WalletFlowDetailVO,
    WalletFlowItemVO,
    WalletRefundSubItemVO
} from '@/api/types/wallet'
import { toast } from '@/composables/useToast'
import { navigate } from '@/utils/link'

interface DetailRow {
    key: string
    label: string
    value: string
    copy?: boolean
}

const props = defineProps<{
    show: boolean
    item: WalletFlowItemVO | null
}>()

const emit = defineEmits<{
    (e: 'update:show', value: boolean): void
}>()

const visible = computed({
    get: () => props.show,
    set: (value: boolean) => emit('update:show', value)
})

const detail = ref<WalletFlowDetailVO | null>(null)

/** 请求序号：快速切换流水时丢弃旧详情，避免串味 */
let seq = 0

const amountText = computed(() => {
    if (!detail.value) return ''
    const sign = detail.value.direction === 'out' ? '-' : '+'
    return `${sign}¥${money(detail.value.amount)}`
})

const thirdPartyNo = computed(() => {
    const d = detail.value
    if (!d) return ''
    return d.transaction_id || d.third_refund_no || ''
})

const refundItems = computed<WalletRefundSubItemVO[]>(() =>
    Array.isArray(detail.value?.refund_items) ? detail.value!.refund_items! : []
)

const voucherUrl = computed(() => detail.value?.voucher_url || '')

/** 明细行按契约顺序拼装，缺字段的整行不渲染（不占空行） */
const detailRows = computed<DetailRow[]>(() => {
    const d = detail.value
    if (!d) return []

    const rows: DetailRow[] = [
        { key: 'type', label: '交易类型', value: d.biz_type_label || '—' },
        { key: 'way', label: '支付/退回方式', value: d.pay_way_label || '—' }
    ]

    if (d.biz_category === 'order_pay') {
        rows.push({ key: 'owner', label: '收款方', value: d.collection_owner_label || '—' })
    }

    rows.push({
        key: 'time',
        label: '交易时间',
        value: d.event_full_time_text || d.event_time_text || '—'
    })

    if (d.biz_sn) rows.push({ key: 'sn', label: '平台流水号', value: d.biz_sn, copy: true })
    if (thirdPartyNo.value) {
        rows.push({ key: 'third', label: '微信/三方单号', value: thirdPartyNo.value, copy: true })
    }
    if (d.order_sn) rows.push({ key: 'order', label: '关联订单号', value: d.order_sn, copy: true })
    if (d.staff_names) rows.push({ key: 'staff', label: '服务人员', value: d.staff_names })
    if (d.service_date) rows.push({ key: 'service', label: '婚期', value: d.service_date })

    if (d.biz_group === 'activity') {
        if (d.ticket_name) rows.push({ key: 'ticket', label: '活动票种', value: d.ticket_name })
        if (d.activity_start_time_text) {
            rows.push({ key: 'start', label: '活动开始', value: d.activity_start_time_text })
        }
    }

    if (d.direction === 'out' && Number(d.refunded_amount) > 0) {
        rows.push({ key: 'refunded', label: '已退金额', value: `¥${money(d.refunded_amount)}` })
    }

    if (d.direction === 'in') {
        if (Number(d.apply_amount) > 0) {
            rows.push({ key: 'apply', label: '申请金额', value: `¥${money(d.apply_amount)}` })
        }
        if (Number(d.actual_refund_amount) > 0) {
            rows.push({
                key: 'actual',
                label: '实退金额',
                value: `¥${money(d.actual_refund_amount)}`
            })
        }
        if (d.refund_reason) rows.push({ key: 'reason', label: '退款原因', value: d.refund_reason })
        if (d.audit_remark) rows.push({ key: 'remark', label: '审核备注', value: d.audit_remark })
    }

    return rows
})

const canViewOrder = computed(
    () => detail.value?.biz_group === 'order' && Number(detail.value?.order_id) > 0
)
const canViewActivity = computed(() => detail.value?.biz_group === 'activity')

function money(value: number | string | null | undefined): string {
    return formatMoney(value, { decimals: 2, thousand: true, trimZeros: false })
}

async function loadDetail(flowKey: string) {
    const mySeq = ++seq
    try {
        const res = await getWalletFlowDetail(flowKey)
        if (mySeq !== seq) return
        if (res && res.flow_key) detail.value = res
    } catch {
        if (mySeq !== seq) return
        toast.info('详情加载失败，可稍后重试')
    }
}

watch(
    () => [props.show, props.item?.flow_key ?? ''] as const,
    ([show, flowKey]) => {
        if (!show || !flowKey) {
            if (!show) seq += 1
            return
        }
        // 乐观渲染：先展示列表项已有的基础字段
        detail.value = props.item ? { ...props.item } : null
        void loadDetail(flowKey)
    },
    { immediate: true }
)

function copyValue(value?: string) {
    const data = String(value || '').trim()
    if (!data) return
    uni.setClipboardData({
        data,
        success: () => toast.success('已复制')
    })
}

function previewVoucher() {
    if (!voucherUrl.value) return
    uni.previewImage({ urls: [voucherUrl.value], current: voucherUrl.value })
}

function goOrder() {
    const id = Number(detail.value?.order_id || 0)
    if (id <= 0) return
    visible.value = false
    navigate({
        path: '/packages/pkg-order/pages/detail/detail',
        query: { id: String(id) }
    })
}

function goActivity() {
    visible.value = false
    navigate({ path: '/packages/pkg-user/pages/activity/activity' })
}
</script>

<style lang="scss" scoped>
/* 原 .sheet / __head / __body / __foot 已由 snap-sheet 承担 */
.detail-body {
    padding-top: var(--sp-1);
}

.foot {
    display: flex;
    gap: var(--sp-2);
}

.foot-btn {
    flex: 1;
    min-width: 0;
}

/* 顶部主卡 */
.summary {
    padding: var(--sp-4);
    margin-bottom: var(--sp-3);
    border-radius: var(--r-lg);
    background: linear-gradient(145deg, var(--c-dark-1), var(--c-dark-2));
    border: 1rpx solid var(--c-primary-border);

    &__amount-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__amount {
        font-family: var(--font-serif);
        font-size: var(--fs-2xl);
        font-weight: var(--fw-medium);
        line-height: var(--lh-none);
        color: var(--c-on-dark);
    }

    &__title {
        display: block;
        margin-top: var(--sp-2);
        font-size: var(--fs-lg);
        font-weight: var(--fw-medium);
        line-height: var(--lh-tight);
        color: var(--c-on-dark);
    }
}

/* 明细区块：卡片边界走 v10 变量（浅色透明 / 暗色 1rpx 描边） */
.block {
    padding: var(--sp-3);
    margin-bottom: var(--sp-3);
    border-radius: var(--r-md);
    background: var(--c-bg);
    border: 1rpx solid var(--c-card-border);

    &__title {
        display: block;
        margin-bottom: var(--sp-2);
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }
}

.row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--sp-3);
    padding: var(--sp-1) 0;

    &__k {
        flex: none;
        font-size: var(--fs-23);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }

    &__v {
        flex: 1;
        min-width: 0;
        text-align: right;
        font-size: var(--fs-23);
        line-height: var(--lh-body);
        color: var(--c-ink);
        word-break: break-all;

        &--action {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: var(--sp-1);
            /* 复制行直接给足 88rpx，避免伪元素热区压到相邻不可点行 */
            min-height: 88rpx;
        }
    }

    &__text {
        min-width: 0;
        text-align: right;
        word-break: break-all;
    }

    &__copy {
        flex: none;
        display: flex;
        align-items: center;
        gap: var(--sp-1);
        padding: 0 var(--sp-1);
        border-radius: var(--r-pill);
        background: var(--c-brand-bg);
        color: var(--c-brand-text);
    }

    &__copy-ic {
        color: var(--c-brand-text);
    }

    &__copy-text {
        font-size: var(--fs-19);
        color: var(--c-brand-text);
    }
}

/* 退款子项 */
.refund {
    padding: var(--sp-2) var(--sp-3);
    margin-bottom: var(--sp-2);
    border-radius: var(--r-sm);
    background: var(--c-surface);
    border: 1rpx solid var(--c-card-border);

    &:last-child {
        margin-bottom: 0;
    }

    &__head {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
    }

    &__amount {
        flex: none;
        font-family: var(--font-serif);
        font-size: var(--fs-29);
        font-weight: var(--fw-medium);
        line-height: var(--lh-tight);
        color: var(--c-ink);
    }

    &__way {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-20);
        color: var(--c-ink-3);
    }

    &__status {
        flex: none;
        font-size: var(--fs-19);
        color: var(--c-brand-text);
    }

    &__line {
        display: flex;
        align-items: center;
        gap: var(--sp-1);
        min-height: 88rpx;
    }

    &__line-text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-19);
        color: var(--c-ink-2);
    }

    &__line-ic {
        flex: none;
        color: var(--c-brand-text);
    }

    &__time {
        display: block;
        margin-top: var(--sp-1);
        font-size: var(--fs-19);
        color: var(--c-ink-3);
    }
}

/* 线下凭证缩略图（尺寸位：240rpx 不进私建间距白名单） */
.voucher-img {
    display: block;
    width: 240rpx;
    height: 240rpx;
    border-radius: var(--r-sm);
    border: 1rpx solid var(--c-card-border);
    box-shadow: var(--shadow-card);
}
</style>
