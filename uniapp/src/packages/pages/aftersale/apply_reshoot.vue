<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="申请补拍" />

        <view class="apply-reshoot">
            <view class="apply-reshoot__hero">
                <text class="apply-reshoot__title">提交补拍 / 重拍申请</text>
                <text class="apply-reshoot__desc">
                    填写关联订单、原因和期望日期，平台会尽快完成审核与安排。
                </text>
            </view>

            <view class="apply-reshoot__section">
                <text class="apply-reshoot__section-title">关联订单</text>
                <view class="apply-reshoot__picker" @click="showOrderPicker = true">
                    <text :class="{ 'apply-reshoot__placeholder': !selectedOrder }">
                        {{ selectedOrder?.label || '请选择订单' }}
                    </text>
                    <tn-icon name="right" size="24" color="#B4ACA8" />
                </view>
            </view>

            <view class="apply-reshoot__section">
                <text class="apply-reshoot__section-title">申请类型</text>
                <view class="apply-reshoot__chips">
                    <view
                        v-for="type in typeOptions"
                        :key="type.value"
                        class="apply-reshoot__chip"
                        :class="{ 'is-active': form.type === type.value }"
                        @click="form.type = type.value"
                    >
                        {{ type.label }}
                    </view>
                </view>
            </view>

            <view class="apply-reshoot__section">
                <text class="apply-reshoot__section-title">申请原因</text>
                <view class="apply-reshoot__chips">
                    <view
                        v-for="reason in reasonOptions"
                        :key="reason.value"
                        class="apply-reshoot__chip"
                        :class="{ 'is-active': form.reason_type === reason.value }"
                        @click="form.reason_type = reason.value"
                    >
                        {{ reason.label }}
                    </view>
                </view>
            </view>

            <view class="apply-reshoot__section">
                <text class="apply-reshoot__section-title">详细说明</text>
                <textarea
                    v-model="form.reason"
                    class="apply-reshoot__textarea"
                    placeholder="请补充说明问题背景、期望效果和注意事项"
                    maxlength="300"
                />
            </view>

            <view class="apply-reshoot__section">
                <text class="apply-reshoot__section-title">期望日期</text>
                <view class="apply-reshoot__picker" @click="showDatePicker = true">
                    <text :class="{ 'apply-reshoot__placeholder': !form.expect_date }">
                        {{ form.expect_date || '请选择期望补拍日期' }}
                    </text>
                    <tn-icon name="calendar" size="24" color="#B4ACA8" />
                </view>
            </view>

            <view class="apply-reshoot__tips">
                <text>说明：</text>
                <text>当前页面优先保证申请主流程稳定提交，图片凭证后续统一接入正式上传链路。</text>
            </view>
        </view>

        <view class="apply-reshoot__action-bar">
            <BaseButton block size="lg" :loading="submitting" @click="handleSubmit">
                提交申请
            </BaseButton>
        </view>

        <tn-picker
            v-model="showOrderPicker"
            mode="selector"
            :range="orderOptions"
            range-key="label"
            @confirm="onOrderConfirm"
        />
        <tn-picker v-model="showDatePicker" mode="time" @confirm="onDateConfirm" />
    </PageShell>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import { applyReshoot } from '@/api/aftersale'
import { getOrderList } from '@/api/order'
import { useThemeStore } from '@/stores/theme'
import { onLoad } from '@dcloudio/uni-app'

const $theme = useThemeStore()
const form = reactive({
    order_id: 0,
    type: 1,
    reason_type: 1,
    reason: '',
    expect_date: ''
})

const typeOptions = [
    { value: 1, label: '补拍' },
    { value: 2, label: '重拍' }
]

const reasonOptions = [
    { value: 1, label: '效果不满意' },
    { value: 2, label: '天气原因' },
    { value: 3, label: '设备故障' },
    { value: 4, label: '人员原因' },
    { value: 5, label: '其他' }
]

const showOrderPicker = ref(false)
const showDatePicker = ref(false)
const submitting = ref(false)
const orderOptions = ref<any[]>([])
const selectedOrder = ref<any>(null)

const loadOrders = async () => {
    try {
        const res = await getOrderList()
        const lists = res?.lists || res?.data?.lists || []
        orderOptions.value = lists.map((item: any) => ({
            value: item.id,
            label: item.order_sn,
            ...item
        }))
    } catch (error) {
        console.error('获取订单列表失败', error)
    }
}

const onOrderConfirm = (event: any) => {
    const index = Number(event?.detail?.value ?? event?.[0] ?? 0)
    const order = orderOptions.value[index]
    if (!order) return
    form.order_id = Number(order.value || 0)
    selectedOrder.value = order
}

const onDateConfirm = (event: any) => {
    form.expect_date = event?.detail?.value || event || ''
}

const handleSubmit = async () => {
    if (!form.order_id) {
        return uni.showToast({ title: '请选择关联订单', icon: 'none' })
    }

    submitting.value = true
    try {
        await applyReshoot({
            ...form,
            reason: form.reason.trim()
        })
        uni.showToast({ title: '申请已提交', icon: 'none' })
        setTimeout(() => {
            uni.navigateBack()
        }, 1200)
    } catch (error: any) {
        uni.showToast({ title: error?.message || '提交失败', icon: 'none' })
    } finally {
        submitting.value = false
    }
}

onLoad((options: any) => {
    if (options?.order_id) {
        form.order_id = Number(options.order_id)
    }
    loadOrders()
})
</script>

<style lang="scss" scoped>
@import '../../../styles/aftersale.scss';

.apply-reshoot {
    @include aftersale-page-base;
    padding: 0 20rpx 180rpx;
}

.apply-reshoot__hero {
    padding: 16rpx 0 24rpx;
}

.apply-reshoot__title {
    display: block;
    font-size: 42rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.apply-reshoot__desc {
    display: block;
    margin-top: 12rpx;
    font-size: 24rpx;
    line-height: 1.7;
    color: var(--wm-text-secondary, #7f7b78);
}

.apply-reshoot__section {
    @include aftersale-section-card;
    margin-bottom: 16rpx;
}

.apply-reshoot__section-title {
    display: block;
    margin-bottom: 16rpx;
    font-size: 28rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.apply-reshoot__picker {
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 88rpx;
    padding: 0 20rpx;
    border-radius: 18rpx;
    border: 1rpx solid $aftersale-border;
    background: rgba(255, 255, 255, 0.82);
    font-size: 28rpx;
    color: var(--wm-text-primary, #1e2432);
}

.apply-reshoot__placeholder {
    color: var(--wm-text-tertiary, #b4aca8);
}

.apply-reshoot__chips {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
}

.apply-reshoot__chip {
    padding: 12rpx 18rpx;
    border-radius: 999rpx;
    border: 1rpx solid $aftersale-border;
    background: rgba(255, 255, 255, 0.8);
    font-size: 24rpx;
    color: var(--wm-text-secondary, #7f7b78);
}

.apply-reshoot__chip.is-active {
    color: #ffffff;
    background: var(--wm-color-primary, #e85a4f);
    border-color: var(--wm-color-primary, #e85a4f);
}

.apply-reshoot__textarea {
    min-height: 200rpx;
    padding: 20rpx;
    border-radius: 18rpx;
    border: 1rpx solid $aftersale-border;
    background: rgba(255, 255, 255, 0.82);
    font-size: 26rpx;
    line-height: 1.7;
    color: var(--wm-text-primary, #1e2432);
    box-sizing: border-box;
}

.apply-reshoot__tips {
    padding: 0 4rpx;
    font-size: 22rpx;
    line-height: 1.7;
    color: var(--wm-text-tertiary, #b4aca8);
}

.apply-reshoot__action-bar {
    position: fixed;
    left: 20rpx;
    right: 20rpx;
    bottom: calc(20rpx + env(safe-area-inset-bottom));
}
</style>
