<template>
    <!-- 头部统一：走 page-shell 的 nav-bar，不再依赖系统导航 -->
    <page-shell title="申请改期" bg="page" :show-back="true" :bottom-bar="true">
        <view class="page-wrap apply">
            <base-card>
                <view class="kv">
                    <text class="kv__k">订单编号</text>
                    <text class="kv__v">{{ orderSn || '-' }}</text>
                </view>
                <view class="kv">
                    <text class="kv__k">当前服务日期</text>
                    <text class="kv__v">{{ currentDate || '-' }}</text>
                </view>
            </base-card>

            <base-card>
                <!-- P3：日期用 form-field 的默认插槽承载原生 picker（标签统一，控件仍是系统 picker）。
                     注：原计划换 tn-calendar 因「无法禁用业务不可选日」未执行，见批次报告。 -->
                <form-field label="新的服务日期" :label-width="180" :required="true">
                    <picker
                        mode="date"
                        :value="form.new_date"
                        :start="today"
                        @change="onDateChange"
                    >
                        <view class="picker-row">
                            <text :class="form.new_date ? 'picker-row__text' : 'picker-row__ph'">
                                {{ form.new_date || '请选择日期' }}
                            </text>
                            <!-- v1 u-icon → v2 tn-icon：arrow-right → right -->
                            <tn-icon name="right" :size="24" color="var(--c-ink-3)" />
                        </view>
                    </picker>
                </form-field>

                <!-- 变更原因：textarea 无法用 form-field 的 type（组件会渲染成单行 input），
                     故用 form-field 的默认插槽承载手写 textarea —— 标签与错误位统一，控件保持多行。 -->
                <form-field
                    label="变更原因"
                    type="textarea"
                    layout="stack"
                    :required="true"
                    :error="reasonError"
                    placeholder="请说明改期原因（选填）"
                    :maxlength="255"
                    count
                    :model-value="form.reason"
                    @update:model-value="(v: string) => (form.reason = v)"
                    @blur="validateReason"
                />
            </base-card>

            <base-card>
                <!-- 上传收归 form-field type="upload"（真实上限 5 张，与原 `.image--add` 的 5 一致） -->
                <form-field
                    label="证明材料（选填，最多 5 张）"
                    type="upload"
                    layout="stack"
                    :max="5"
                    :model-value="form.attach_images"
                    @update:model-value="(v: string[]) => (form.attach_images = v)"
                />
            </base-card>

            <view class="tips">
                <!-- v1 u-icon → v2 tn-icon：info-circle → tips -->
                <tn-icon name="tips" :size="24" color="var(--c-ink-3)" />
                <text class="tips__text"
                    >改期需商家审核，审核通过后档期才会变更。已支付订单的差价处理以审核结果为准。</text
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
/** 申请改期：order_id + new_date + reason? + attach_images? */
import { reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { applyDateChange, checkCanChange } from '@/api/change'
import { getOrderDetail } from '@/api/order'

const orderId = ref(0)
const orderSn = ref('')
const currentDate = ref('')
const submitting = ref(false)
const form = reactive({ new_date: '', reason: '', attach_images: [] as string[] })
/** 变更原因字段级错误（红字落在字段下方，不走 toast） */
const reasonError = ref('')

/**
 * 变更原因：必填，最少 10 字，上限 255（maxlength 硬限）。
 * 为什么是 10 字：下游是「商家审核改期申请」——**校验值必须能支撑下游动作**。
 * 写"忙"两个字，审核人仍然只能打电话问，等于把成本转给客服。
 * 文案按主理人给定：「请说明改期的原因，至少 10 个字」。
 */
const REASON_MIN = 10
function validateReason() {
    const v = form.reason.trim()
    reasonError.value = v.length >= REASON_MIN ? '' : '请说明改期的原因，至少 10 个字'
    return !reasonError.value
}

const today = (() => {
    const d = new Date()
    const m = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    return `${d.getFullYear()}-${m}-${day}`
})()

function onDateChange(e: any) {
    form.new_date = String(e?.detail?.value || '')
}

async function loadContext() {
    try {
        const res = await checkCanChange({ order_id: orderId.value })
        if (res && res.can_change === 0) {
            uni.showModal({
                title: '暂不可改期',
                content: res.message || '当前订单状态不支持改期',
                showCancel: false,
                success: () => uni.navigateBack()
            })
        }
    } catch {
        // 检查失败不阻断，提交时后端仍会校验
    }

    try {
        const detail = await getOrderDetail({ id: orderId.value })
        orderSn.value = detail?.order_sn || ''
        const first = (detail?.items || [])[0]
        currentDate.value = first?.service_date || detail?.service_date || ''
    } catch {
        // 忽略
    }
}

async function handleSubmit() {
    if (submitting.value) return
    // 失焦校验只在 blur 触发；用户填完直接点提交时可能还没校验过
    if (!validateReason()) return
    if (!form.new_date) {
        toast.info('请选择新日期')
        return
    }
    if (form.new_date === currentDate.value) {
        toast.info('新日期与当前日期相同')
        return
    }
    submitting.value = true
    try {
        await applyDateChange({
            order_id: orderId.value,
            new_date: form.new_date,
            reason: form.reason.trim() || undefined,
            attach_images: form.attach_images.length ? form.attach_images : undefined
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
        return
    }
    loadContext()
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

.kv {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--sp-3);
    min-height: 56rpx;
    padding: var(--sp-2) 0;
    box-sizing: border-box;

    & + & {
        border-top: 1rpx solid var(--c-line);
    }

    &__k {
        flex-shrink: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__v {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink);
        text-align: right;
        overflow-wrap: anywhere;
    }
}

.block-title {
    display: block;
    margin-bottom: var(--sp-3);
    font-size: var(--fs-md);
    font-weight: 500;
    color: var(--c-ink);

    &--gap {
        margin-top: var(--sp-4);
    }
}

.picker-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-3);
    min-height: 88rpx;
    padding: 0 var(--sp-3);
    background: var(--c-surface-2);
    border: 1rpx solid var(--c-line);
    border-radius: var(--r-md);
    box-sizing: border-box;

    &__text,
    &__ph {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-md);
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
}.tips {
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
</style>
