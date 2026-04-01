<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="补拍详情" />

        <view v-if="detail" class="reshoot-detail">
            <view class="reshoot-detail__status-card">
                <view class="reshoot-detail__status-main">
                    <text class="reshoot-detail__status-text">
                        {{ detail.status_desc || getStatusText(detail.status) }}
                    </text>
                    <text class="reshoot-detail__status-time">
                        提交时间：{{ detail.create_time }}
                    </text>
                </view>
                <view class="reshoot-detail__status-tag" :class="getStatusClass(detail.status)">
                    {{ detail.status_desc || getStatusText(detail.status) }}
                </view>
            </view>

            <view class="reshoot-detail__section">
                <text class="reshoot-detail__section-title">申请信息</text>
                <view class="reshoot-detail__kv">
                    <text class="reshoot-detail__kv-label">申请编号</text>
                    <text class="reshoot-detail__kv-value">{{ detail.reshoot_sn }}</text>
                </view>
                <view class="reshoot-detail__kv">
                    <text class="reshoot-detail__kv-label">申请类型</text>
                    <text class="reshoot-detail__kv-value">
                        {{ detail.type_desc || getTypeText(detail.type) }}
                    </text>
                </view>
                <view class="reshoot-detail__kv">
                    <text class="reshoot-detail__kv-label">申请原因</text>
                    <text class="reshoot-detail__kv-value">
                        {{ detail.reason_type_desc || getReasonText(detail.reason_type) }}
                    </text>
                </view>
                <view v-if="detail.order?.order_sn" class="reshoot-detail__kv">
                    <text class="reshoot-detail__kv-label">关联订单</text>
                    <text class="reshoot-detail__kv-value">{{ detail.order.order_sn }}</text>
                </view>
                <view v-if="detail.expect_date" class="reshoot-detail__kv">
                    <text class="reshoot-detail__kv-label">期望日期</text>
                    <text class="reshoot-detail__kv-value">{{ detail.expect_date }}</text>
                </view>
            </view>

            <view class="reshoot-detail__section">
                <text class="reshoot-detail__section-title">详细说明</text>
                <text class="reshoot-detail__content-text">
                    {{ detail.reason || '用户未填写详细说明。' }}
                </text>
                <view
                    v-if="Array.isArray(detail.images) && detail.images.length"
                    class="reshoot-detail__gallery"
                >
                    <image
                        v-for="(img, index) in detail.images"
                        :key="`${img}-${index}`"
                        :src="img"
                        mode="aspectFill"
                        class="reshoot-detail__gallery-image"
                        @click="previewImage(detail.images, index)"
                    />
                </view>
            </view>

            <view
                v-if="detail.audit_remark || detail.complete_remark || detail.new_staff?.name"
                class="reshoot-detail__section"
            >
                <text class="reshoot-detail__section-title">处理进度</text>
                <view v-if="detail.audit_remark" class="reshoot-detail__kv">
                    <text class="reshoot-detail__kv-label">审核备注</text>
                    <text class="reshoot-detail__kv-value">{{ detail.audit_remark }}</text>
                </view>
                <view v-if="detail.new_staff?.name" class="reshoot-detail__kv">
                    <text class="reshoot-detail__kv-label">安排人员</text>
                    <text class="reshoot-detail__kv-value">{{ detail.new_staff.name }}</text>
                </view>
                <view v-if="detail.complete_remark" class="reshoot-detail__kv">
                    <text class="reshoot-detail__kv-label">完成备注</text>
                    <text class="reshoot-detail__kv-value">{{ detail.complete_remark }}</text>
                </view>
            </view>
        </view>

        <view
            v-if="detail && (detail.status === 0 || detail.status === 1)"
            class="reshoot-detail__action-bar"
        >
            <BaseButton type="ghost" block size="lg" @click="handleCancel">取消申请</BaseButton>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import { cancelReshoot, getReshootDetail } from '@/api/aftersale'
import { useThemeStore } from '@/stores/theme'
import { onLoad } from '@dcloudio/uni-app'

const $theme = useThemeStore()
const reshootId = ref(0)
const detail = ref<any>(null)

const getDetail = async () => {
    try {
        const res = await getReshootDetail(reshootId.value)
        detail.value = res?.data || res
    } catch (error) {
        uni.showToast({ title: '获取详情失败', icon: 'none' })
    }
}

const getStatusClass = (status: number) => {
    const map: Record<number, string> = {
        0: 'status-pending',
        1: 'status-approved',
        2: 'status-rejected',
        3: 'status-scheduled',
        4: 'status-completed',
        5: 'status-cancelled'
    }
    return map[status] || ''
}

const getStatusText = (status: number) => {
    const map: Record<number, string> = {
        0: '待审核',
        1: '审核通过',
        2: '审核拒绝',
        3: '已安排',
        4: '已完成',
        5: '已取消'
    }
    return map[status] || '未知'
}

const getTypeText = (type: number) => {
    return type === 2 ? '重拍' : '补拍'
}

const getReasonText = (reasonType: number) => {
    const map: Record<number, string> = {
        1: '效果不满意',
        2: '天气原因',
        3: '设备故障',
        4: '人员原因',
        5: '其他'
    }
    return map[reasonType] || '其他'
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
        content: '确定要取消这条补拍申请吗？',
        success: async (res) => {
            if (!res.confirm) return

            try {
                await cancelReshoot(reshootId.value)
                uni.showToast({ title: '取消成功', icon: 'none' })
                getDetail()
            } catch (error: any) {
                uni.showToast({ title: error?.message || '取消失败', icon: 'none' })
            }
        }
    })
}

onLoad((options: any) => {
    reshootId.value = Number(options?.id || 0)
    if (reshootId.value) {
        getDetail()
    }
})
</script>

<style lang="scss" scoped>
@import '../../../styles/aftersale.scss';

.reshoot-detail {
    @include aftersale-page-base;
    padding: 0 20rpx 160rpx;
}

.reshoot-detail__status-card,
.reshoot-detail__section {
    @include aftersale-section-card;
    margin-bottom: 16rpx;
}

.reshoot-detail__status-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.reshoot-detail__status-text {
    display: block;
    font-size: 32rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.reshoot-detail__status-time {
    display: block;
    margin-top: 8rpx;
    font-size: 22rpx;
    color: var(--wm-text-tertiary, #b4aca8);
}

.reshoot-detail__status-tag {
    padding: 10rpx 18rpx;
    border-radius: 999rpx;
    font-size: 22rpx;
    font-weight: 700;
}

.reshoot-detail__section-title {
    display: block;
    margin-bottom: 18rpx;
    font-size: 30rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.reshoot-detail__kv {
    @include aftersale-kv-row;
}

.reshoot-detail__kv + .reshoot-detail__kv {
    margin-top: 14rpx;
}

.reshoot-detail__kv-label {
    @include aftersale-kv-label;
}

.reshoot-detail__kv-value {
    @include aftersale-kv-value;
}

.reshoot-detail__content-text {
    display: block;
    font-size: 26rpx;
    line-height: 1.7;
    color: var(--wm-text-secondary, #7f7b78);
}

.reshoot-detail__gallery {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10rpx;
    margin-top: 18rpx;
}

.reshoot-detail__gallery-image {
    width: 100%;
    height: 180rpx;
    border-radius: 16rpx;
}

.reshoot-detail__action-bar {
    position: fixed;
    left: 20rpx;
    right: 20rpx;
    bottom: calc(20rpx + env(safe-area-inset-bottom));
}

.status-pending {
    color: #c98524;
    background: rgba(201, 133, 36, 0.12);
}

.status-approved {
    color: #2f7d58;
    background: rgba(47, 125, 88, 0.12);
}

.status-rejected {
    color: #b44a3a;
    background: rgba(180, 74, 58, 0.12);
}

.status-scheduled {
    color: #8f6ab5;
    background: rgba(143, 106, 181, 0.12);
}

.status-completed {
    color: #607086;
    background: rgba(96, 112, 134, 0.12);
}

.status-cancelled {
    color: #978b83;
    background: rgba(151, 139, 131, 0.12);
}
</style>
