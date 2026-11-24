<template>
    <!-- 头部统一：走 page-shell 的 nav-bar，不再依赖系统导航 -->
    <page-shell title="申请暂停" bg="page" :show-back="true" :bottom-bar="true">
        <view class="page-wrap apply">
            <base-card>
                <text class="block-title">暂停类型</text>
                <view class="chips">
                    <view
                        hover-class="is-pressed"
                        v-for="t in PAUSE_TYPES"
                        :key="t.value"
                        class="chips__item"
                        :class="{ 'is-active': form.pause_type === t.value }"
                        @tap="form.pause_type = t.value"
                    >
                        <text class="chips__text">{{ t.label }}</text>
                    </view>
                </view>
            </base-card>

            <base-card>
                <text class="block-title">暂停区间</text>
                <picker mode="date" :value="form.start_date" :start="today" @change="onStartDate">
                    <view class="date-row">
                        <text class="date-row__label">开始</text>
                        <text :class="form.start_date ? 'date-row__text' : 'date-row__ph'">
                            {{ form.start_date || '请选择' }}
                        </text>
                    </view>
                </picker>
                <picker
                    mode="date"
                    :value="form.end_date"
                    :start="form.start_date || today"
                    @change="onEndDate"
                >
                    <view class="date-row date-row--last">
                        <text class="date-row__label">结束</text>
                        <text :class="form.end_date ? 'date-row__text' : 'date-row__ph'">
                            {{ form.end_date || '请选择' }}
                        </text>
                    </view>
                </picker>
                <!-- 就地校验：原实现在「开始晚于已选结束」时会静默清空结束日期，
                     用户只看到结束变回「请选择」，不知道为什么。这里补一句说明。 -->
                <text v-if="rangeError" class="field-error">{{ rangeError }}</text>
            </base-card>

            <base-card>
                <!-- 暂停原因是必填长字段：必填用 form-field 红星（不再写进标题文案），
                     textarea 走默认插槽（form-field 的 type="textarea" 会渲染成单行 input） -->
                <form-field
                    label="暂停原因"
                    type="textarea"
                    layout="stack"
                    :required="true"
                    :error="reasonError"
                    placeholder="请说明需要暂停的原因，便于商家审核"
                    :maxlength="255"
                    count
                    :model-value="form.reason"
                    @update:model-value="(v: string) => (form.reason = v)"
                    @blur="validateReason"
                />
            </base-card>

            <base-card>
                <!-- 长标签（14 字）→ stack；上传已由 form-field type="upload" 承载（真实上限 10 张） -->
                <form-field
                    label="证明材料（选填，最多 10 张）"
                    type="upload"
                    layout="stack"
                    :max="10"
                    :model-value="form.proof_images"
                    @update:model-value="(v: string[]) => (form.proof_images = v)"
                />
            </base-card>

            <view class="tips">
                <!-- v1 u-icon → v2 tn-icon：info-circle → tips -->
                <tn-icon name="tips" :size="24" class="ic-ink-3" />
                <text class="tips__text"
                    >暂停需商家审核。暂停期间服务人员档期会被释放，恢复后需重新确认日期。</text
                >
            </view>

            <view class="submit">
                <base-button
                    text="提交申请"
                    variant="ink"
                    size="lg"
                    :loading="submitting"
                    @click="handleSubmit"
                />
            </view>
        </view>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
import { toast } from '@/composables/useToast'
/**
 * 申请暂停：pause_type(1-4) + start_date + end_date + reason(必填) + proof_images?
 * 注意 reason 是 append('require') 强制的，前端必须拦空，否则后端返回的是通用校验错误。
 */
import { reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { ORDER_PAUSE_TYPE_NAME_MAP, OrderPauseType } from '@shared/contracts'
import { applyPause } from '@/api/change'
import { remindBeforeOaAction } from '@/utils/oa-reminder'

const PAUSE_TYPE_ORDER = [
    OrderPauseType.EPIDEMIC,
    OrderPauseType.EMERGENCY,
    OrderPauseType.PERSONAL,
    OrderPauseType.OTHER
]
const PAUSE_TYPES = PAUSE_TYPE_ORDER.map((v) => ({ value: v, label: ORDER_PAUSE_TYPE_NAME_MAP[v] }))

const orderId = ref(0)
const submitting = ref(false)
/** 区间就地校验（结束不早于开始） */
const rangeError = ref('')
/** 暂停原因字段级错误 */
const reasonError = ref('')

/**
 * 暂停原因：必填，最少 10 字，上限 255（maxlength 硬限）。
 * 与「变更原因」（apply-date）同一规则 —— 两者同属订单变更申请、同样进商家审核，
 * 一个要求 10 字、一个只要求非空，用户会觉得规则随机。
 * 依据同一条：**校验值必须能支撑下游动作**（商家审核需要信息量）。
 */
const REASON_MIN = 10
function validateReason() {
    const v = form.reason.trim()
    reasonError.value = v.length >= REASON_MIN ? '' : '请说明暂停的原因，至少 10 个字'
    return !reasonError.value
}

const form = reactive({
    pause_type: OrderPauseType.PERSONAL as number,
    start_date: '',
    end_date: '',
    reason: '',
    proof_images: [] as string[]
})

const today = (() => {
    const d = new Date()
    const m = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    return `${d.getFullYear()}-${m}-${day}`
})()

function onStartDate(e: any) {
    form.start_date = String(e?.detail?.value || '')
    // 原来这里静默清空 end_date —— 用户只看到结束变回「请选择」，不知道为什么。
    // 现补一句就地说明：既不丢失数据（清空是对的），也解释了原因。
    if (form.end_date && form.end_date < form.start_date) {
        form.end_date = ''
        rangeError.value = '开始日期晚于已选的结束日期，结束日期已清空，请重新选择'
    } else {
        rangeError.value = ''
    }
}

function onEndDate(e: any) {
    form.end_date = String(e?.detail?.value || '')
    rangeError.value = ''
}

async function handleSubmit() {
    if (submitting.value) return
    if (!form.start_date || !form.end_date) {
        toast.info('请选择暂停区间')
        return
    }
    if (form.end_date < form.start_date) {
        toast.info('结束日期不能早于开始日期')
        return
    }
    // 原因校验走字段下方红字，不再用 toast（01 §6.0：错误反馈落在字段下方）
    if (!validateReason()) return
    submitting.value = true
    try {
        // 服务号提醒：表单校验已通过，只在提交前弹一次；
        // 暂停时直接退出，submitting 由 finally 释放
        if (!(await remindBeforeOaAction())) return
        await applyPause({
            order_id: orderId.value,
            pause_type: form.pause_type,
            reason: form.reason.trim(),
            start_date: form.start_date,
            end_date: form.end_date,
            proof_images: form.proof_images.length ? form.proof_images : undefined
        })
        toast.success('已提交申请')
        setTimeout(() => uni.navigateBack(), 700)
    } catch {
        // 请求层已提示
    } finally {
        submitting.value = false
    }
}

onLoad((options: any) => {
    orderId.value = Number(options?.order_id || 0)
    if (!orderId.value) {
        toast.info('参数缺失')
        setTimeout(() => uni.navigateBack(), 600)
    }
})
</script>

<style lang="scss" scoped>
.apply {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    /* P2：原 padding-bottom: calc(164rpx + var(--safe-bottom)) 已删除，
       预留改由 page-shell 的 :bottom-bar="true" 统一负责 */
    padding: var(--sp-3) var(--sp-4) var(--sp-4);
    background: var(--c-bg);
}

.block-title {
    display: block;
    margin-bottom: var(--sp-3);
    font-size: var(--fs-md);
    font-weight: 500;
    color: var(--c-ink);
}

.chips {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-2);

    &__item {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 64rpx;
        padding: 0 var(--sp-3);
        border-radius: var(--r-pill);
        background: var(--c-surface-2);
        border: 1rpx solid var(--c-line);
        box-sizing: border-box;

        &.is-active {
            background: var(--c-ink);
            border-color: var(--c-ink);
        }
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &__item.is-active &__text {
        color: var(--c-on-dark);
    }
}

.date-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-3);
    min-height: 88rpx;
    padding: var(--sp-2) 0;
    border-bottom: 1rpx solid var(--c-line);
    box-sizing: border-box;

    &--last {
        border-bottom: none;
    }

    &__label {
        flex-shrink: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &__text,
    &__ph {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-md);
        text-align: right;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    &__text {
        color: var(--c-ink);
    }

    &__ph {
        color: var(--c-ink-3);
    }
}

/* 区间就地校验红字（落在区间卡片下方） */
.field-error {
    display: block;
    margin-top: var(--sp-1);
    font-size: var(--fs-md);
    color: var(--c-danger);
    line-height: var(--lh-body);
}
.tips {
    display: flex;
    align-items: flex-start;
    gap: var(--sp-1);
    padding: 0 var(--sp-1);

    &__text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        line-height: var(--lh-body);
        overflow-wrap: anywhere;
    }
}

.submit {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 50;
    padding: var(--sp-3) var(--sp-4) calc(var(--sp-3) + var(--safe-bottom));
    border-top: 1rpx solid var(--c-line);
    background: var(--c-surface);
    box-sizing: border-box;
}

/* tn-icon 颜色工具类（本地 scoped 版）：tn-icon 的 color prop 不认 var()，
   颜色必须靠 CSS 落在图标根节点上再由内部 <text> 继承。 */
.ic-ink-3 {
    color: var(--c-ink-3);
}
</style>
