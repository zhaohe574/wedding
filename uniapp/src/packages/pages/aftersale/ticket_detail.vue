<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="工单详情" />

        <view class="ticket-detail" v-if="detail">
            <view class="ticket-detail__status-card">
                <view class="ticket-detail__status-icon" :class="getStatusClass(detail.status)">
                    <tn-icon :name="getStatusIcon(detail.status)" size="36" color="#FFFFFF" />
                </view>
                <view class="ticket-detail__status-copy">
                    <text class="ticket-detail__status-text">
                        {{ detail.status_desc || getStatusText(detail.status) }}
                    </text>
                    <text class="ticket-detail__status-time">
                        更新时间：{{ detail.update_time || detail.create_time }}
                    </text>
                </view>
            </view>

            <view class="ticket-detail__section">
                <text class="ticket-detail__section-title">基础信息</text>
                <view class="ticket-detail__kv">
                    <text class="ticket-detail__kv-label">工单编号</text>
                    <text class="ticket-detail__kv-value">{{ detail.ticket_sn }}</text>
                </view>
                <view class="ticket-detail__kv">
                    <text class="ticket-detail__kv-label">工单类型</text>
                    <text class="ticket-detail__kv-value">
                        {{ detail.type_desc || getTypeText(detail.type) }}
                    </text>
                </view>
                <view class="ticket-detail__kv">
                    <text class="ticket-detail__kv-label">创建时间</text>
                    <text class="ticket-detail__kv-value">{{ detail.create_time }}</text>
                </view>
                <view v-if="detail.order?.order_sn" class="ticket-detail__kv">
                    <text class="ticket-detail__kv-label">关联订单</text>
                    <text class="ticket-detail__kv-value">{{ detail.order.order_sn }}</text>
                </view>
            </view>

            <view class="ticket-detail__section">
                <text class="ticket-detail__section-title">问题内容</text>
                <text class="ticket-detail__content-title">{{ detail.title }}</text>
                <text v-if="detail.content" class="ticket-detail__content-text">{{
                    detail.content
                }}</text>
                <view
                    v-if="Array.isArray(detail.images) && detail.images.length"
                    class="ticket-detail__gallery"
                >
                    <image
                        v-for="(img, index) in detail.images"
                        :key="`${img}-${index}`"
                        :src="img"
                        mode="aspectFill"
                        class="ticket-detail__gallery-image"
                        @click="previewImage(detail.images, index)"
                    />
                </view>
            </view>

            <view v-if="detail.logs?.length" class="ticket-detail__section">
                <text class="ticket-detail__section-title">处理进度</text>
                <view class="ticket-detail__timeline">
                    <view
                        v-for="(log, index) in detail.logs"
                        :key="index"
                        class="ticket-detail__timeline-item"
                    >
                        <view
                            class="ticket-detail__timeline-dot"
                            :class="{ 'is-active': index === 0 }"
                        />
                        <view class="ticket-detail__timeline-main">
                            <text class="ticket-detail__timeline-text">{{ log.content }}</text>
                            <text class="ticket-detail__timeline-time">{{ log.create_time }}</text>
                        </view>
                    </view>
                </view>
            </view>

            <view v-if="detail.handle_result" class="ticket-detail__section">
                <text class="ticket-detail__section-title">处理结果</text>
                <text class="ticket-detail__content-text">{{ detail.handle_result }}</text>
            </view>
        </view>

        <view v-if="detail" class="ticket-detail__action-bar">
            <BaseButton
                v-if="detail.status === 0"
                type="ghost"
                block
                size="lg"
                @click="handleCancel"
            >
                取消工单
            </BaseButton>
            <BaseButton v-if="detail.status === 2" block size="lg" @click="showConfirmPopup = true">
                确认完成
            </BaseButton>
        </view>

        <tn-popup
            v-model="showConfirmPopup"
            open-direction="bottom"
            :radius="28"
            safe-area-inset-bottom
        >
            <view class="confirm-panel">
                <view class="confirm-panel__head">
                    <text class="confirm-panel__title">确认完成</text>
                    <tn-icon
                        name="close"
                        size="28"
                        color="#978B83"
                        @click="showConfirmPopup = false"
                    />
                </view>
                <view class="confirm-panel__body">
                    <view class="confirm-panel__field">
                        <text class="confirm-panel__label">满意度</text>
                        <u-rate v-model="confirmForm.satisfaction" :min-count="1" />
                    </view>
                    <view class="confirm-panel__field">
                        <text class="confirm-panel__label">补充备注</text>
                        <textarea
                            v-model="confirmForm.remark"
                            class="confirm-panel__textarea"
                            placeholder="可选，补充本次处理结果体验"
                        />
                    </view>
                </view>
                <view class="confirm-panel__footer">
                    <BaseButton block size="lg" @click="handleConfirm">确认提交</BaseButton>
                </view>
            </view>
        </tn-popup>
    </PageShell>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import { getTicketDetail, cancelTicket, confirmComplete } from '@/api/aftersale'
import { useThemeStore } from '@/stores/theme'
import { onLoad } from '@dcloudio/uni-app'

const $theme = useThemeStore()
const ticketId = ref<number>(0)
const detail = ref<any>(null)
const showConfirmPopup = ref(false)
const confirmForm = reactive({
    satisfaction: 5,
    remark: ''
})

const getDetail = async () => {
    try {
        const res = await getTicketDetail(ticketId.value)
        detail.value = res?.data || res
    } catch (error) {
        uni.showToast({ title: '获取详情失败', icon: 'none' })
    }
}

const getStatusClass = (status: number) => {
    const map: Record<number, string> = {
        0: 'status-pending',
        1: 'status-processing',
        2: 'status-confirming',
        3: 'status-completed',
        4: 'status-closed',
        5: 'status-cancelled'
    }
    return map[status] || ''
}

const getStatusIcon = (status: number) => {
    const map: Record<number, string> = {
        0: 'clock',
        1: 'loading',
        2: 'shield-check',
        3: 'check-circle',
        4: 'close-circle',
        5: 'close-circle'
    }
    return map[status] || 'help'
}

const getStatusText = (status: number) => {
    const map: Record<number, string> = {
        0: '待处理',
        1: '处理中',
        2: '待确认',
        3: '已完成',
        4: '已关闭',
        5: '已取消'
    }
    return map[status] || '未知'
}

const getTypeText = (type: number) => {
    const map: Record<number, string> = {
        1: '投诉',
        2: '咨询',
        3: '售后',
        4: '建议',
        5: '其他'
    }
    return map[type] || '其他'
}

const previewImage = (images: string[], index: number) => {
    uni.previewImage({
        urls: images,
        current: index
    })
}

const handleCancel = async () => {
    uni.showModal({
        title: '确认取消',
        content: '确定要取消这条工单吗？',
        success: async (res) => {
            if (!res.confirm) return

            try {
                await cancelTicket(ticketId.value)
                uni.showToast({ title: '取消成功', icon: 'none' })
                getDetail()
            } catch (error: any) {
                uni.showToast({ title: error?.message || '取消失败', icon: 'none' })
            }
        }
    })
}

const handleConfirm = async () => {
    try {
        await confirmComplete({
            id: ticketId.value,
            satisfaction: confirmForm.satisfaction,
            remark: confirmForm.remark
        })
        showConfirmPopup.value = false
        uni.showToast({ title: '确认成功', icon: 'none' })
        getDetail()
    } catch (error: any) {
        uni.showToast({ title: error?.message || '操作失败', icon: 'none' })
    }
}

onLoad((options: any) => {
    ticketId.value = Number(options?.id || 0)
    if (ticketId.value) {
        getDetail()
    }
})
</script>

<style lang="scss" scoped>
@import '../../../styles/aftersale.scss';

.ticket-detail {
    @include aftersale-page-base;
    padding: 0 20rpx 160rpx;
}

.ticket-detail__status-card,
.ticket-detail__section {
    @include aftersale-section-card;
    margin-bottom: 16rpx;
}

.ticket-detail__status-card {
    display: flex;
    align-items: center;
    gap: 20rpx;
}

.ticket-detail__status-icon {
    width: 84rpx;
    height: 84rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 999rpx;
    flex-shrink: 0;
}

.ticket-detail__status-copy {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.ticket-detail__status-text {
    font-size: 32rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.ticket-detail__status-time {
    font-size: 22rpx;
    color: var(--wm-text-tertiary, #b4aca8);
}

.ticket-detail__section-title {
    display: block;
    margin-bottom: 18rpx;
    font-size: 30rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.ticket-detail__kv {
    @include aftersale-kv-row;
}

.ticket-detail__kv + .ticket-detail__kv {
    margin-top: 14rpx;
}

.ticket-detail__kv-label {
    @include aftersale-kv-label;
}

.ticket-detail__kv-value {
    @include aftersale-kv-value;
}

.ticket-detail__content-title {
    display: block;
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1.35;
    color: var(--wm-text-primary, #1e2432);
}

.ticket-detail__content-text {
    display: block;
    margin-top: 14rpx;
    font-size: 26rpx;
    line-height: 1.7;
    color: var(--wm-text-secondary, #7f7b78);
}

.ticket-detail__gallery {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10rpx;
    margin-top: 18rpx;
}

.ticket-detail__gallery-image {
    width: 100%;
    height: 180rpx;
    border-radius: 16rpx;
}

.ticket-detail__timeline {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
}

.ticket-detail__timeline-item {
    display: flex;
    gap: 16rpx;
}

.ticket-detail__timeline-dot {
    width: 18rpx;
    height: 18rpx;
    margin-top: 10rpx;
    border-radius: 999rpx;
    background: rgba(180, 172, 168, 0.56);
    flex-shrink: 0;
}

.ticket-detail__timeline-dot.is-active {
    background: var(--wm-color-primary, #e85a4f);
}

.ticket-detail__timeline-main {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.ticket-detail__timeline-text {
    font-size: 26rpx;
    line-height: 1.7;
    color: var(--wm-text-primary, #1e2432);
}

.ticket-detail__timeline-time {
    font-size: 22rpx;
    color: var(--wm-text-tertiary, #b4aca8);
}

.ticket-detail__action-bar {
    position: fixed;
    left: 20rpx;
    right: 20rpx;
    bottom: calc(20rpx + env(safe-area-inset-bottom));
}

.confirm-panel {
    padding: 24rpx 20rpx 28rpx;
    background: var(--wm-color-bg-page, #fcfbf9);
}

.confirm-panel__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.confirm-panel__title {
    font-size: 32rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.confirm-panel__body {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
    margin-top: 20rpx;
}

.confirm-panel__field {
    display: flex;
    flex-direction: column;
    gap: 10rpx;
}

.confirm-panel__label {
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-text-secondary, #7f7b78);
}

.confirm-panel__textarea {
    min-height: 180rpx;
    padding: 20rpx;
    border-radius: 18rpx;
    border: 1rpx solid $aftersale-border;
    background: rgba(255, 255, 255, 0.82);
    font-size: 26rpx;
    line-height: 1.7;
    color: var(--wm-text-primary, #1e2432);
    box-sizing: border-box;
}

.confirm-panel__footer {
    margin-top: 24rpx;
}

.status-pending {
    background: #c98524;
}

.status-processing {
    background: #607086;
}

.status-confirming {
    background: #8f6ab5;
}

.status-completed {
    background: #2f7d58;
}

.status-closed,
.status-cancelled {
    background: #978b83;
}
</style>
