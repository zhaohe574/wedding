<template>
    <page-meta :page-style="pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="评价详情" />

        <view class="review-detail-page wm-page-content" v-if="review">
            <view class="hero-card wm-panel-card">
                <view class="hero-header">
                    <view class="hero-user">
                        <image
                            class="user-avatar"
                            :src="review.user?.avatar || '/static/images/user/default_avatar.png'"
                            mode="aspectFill"
                        />
                        <view class="user-info">
                            <text class="user-name">{{ review.user?.nickname || '匿名用户' }}</text>
                            <text class="review-time">{{ review.create_time_text || '-' }}</text>
                        </view>
                    </view>
                    <view class="status-badge" :class="`status-${review.status}`">
                        {{ review.status_text }}
                    </view>
                </view>

                <view class="score-row">
                    <view class="score-stars">
                        <tn-icon
                            v-for="star in 5"
                            :key="star"
                            :name="star <= Number(review.score || 0) ? 'star-fill' : 'star'"
                            size="28"
                            :color="star <= Number(review.score || 0) ? '#C8A45D' : '#D8D3C7'"
                        />
                    </view>
                    <text class="score-text">{{ review.score_level }} · {{ review.score }}分</text>
                </view>

                <view class="meta-list">
                    <view class="meta-item">
                        <text class="meta-label">服务人员</text>
                        <text class="meta-value">{{ review.staff?.name || '-' }}</text>
                    </view>
                    <view class="meta-item">
                        <text class="meta-label">订单编号</text>
                        <text class="meta-value">{{ review.order?.order_sn || '-' }}</text>
                    </view>
                    <view class="meta-item">
                        <text class="meta-label">服务项目</text>
                        <text class="meta-value">
                            {{
                                review.order_item?.package_name ||
                                review.orderItem?.package_name ||
                                '-'
                            }}
                        </text>
                    </view>
                    <view class="meta-item">
                        <text class="meta-label">服务日期</text>
                        <text class="meta-value">
                            {{ review.service_date || review.order?.service_date || '-' }}
                        </text>
                    </view>
                </view>
            </view>

            <view class="section-card wm-form-block">
                <view class="section-title">审核与奖励说明</view>
                <text class="section-tip">{{ review.status_summary || '请留意审核结果。' }}</text>
                <text class="section-tip">{{ review.reward_summary || rewardPendingText }}</text>
            </view>

            <view class="section-card wm-form-block">
                <view class="section-title">评价内容</view>
                <text v-if="review.content" class="review-content">{{ review.content }}</text>
                <text v-else class="empty-text">未填写评价</text>

                <view v-if="review.tags?.length" class="tag-list">
                    <view v-for="tag in review.tags" :key="tag.id || tag.name" class="tag-item">
                        {{ tag.name }}
                    </view>
                </view>

                <view v-if="review.images?.length" class="image-grid">
                    <image
                        v-for="(image, index) in review.images"
                        :key="`${image}-${index}`"
                        class="review-image"
                        :src="image"
                        mode="aspectFill"
                        @click="previewImages(review.images, index)"
                    />
                </view>
            </view>

            <view class="section-card wm-form-block">
                <view class="section-title">奖励信息</view>
                <view class="reward-panel">
                    <view class="reward-main">
                        <text class="reward-points">+{{ Number(review.reward_points || 0) }}</text>
                        <text class="reward-unit">积分</text>
                    </view>
                    <view class="reward-status" :class="rewardStatus.className">
                        {{ rewardStatus.text }}
                    </view>
                </view>
                <text class="reward-tip">
                    {{
                        review.reward_grant_time
                            ? `积分已于 ${formatDateTime(review.reward_grant_time)} 发放`
                            : rewardPendingText
                    }}
                </text>
            </view>

            <view v-if="review.replies?.length" class="section-card wm-form-block">
                <view class="section-title">回复记录</view>
                <view
                    v-for="reply in review.replies"
                    :key="reply.id"
                    class="reply-card wm-soft-card"
                >
                    <view class="reply-header">
                        <text class="reply-type">
                            {{ Number(reply.reply_type) === 1 ? '用户追评' : '商家回复' }}
                        </text>
                        <text class="reply-time">{{ formatDateTime(reply.create_time) }}</text>
                    </view>
                    <text class="reply-content">{{ reply.content }}</text>
                    <view v-if="reply.images?.length" class="reply-images">
                        <image
                            v-for="(image, index) in reply.images"
                            :key="`${image}-${index}`"
                            class="reply-image"
                            :src="image"
                            mode="aspectFill"
                            @click="previewImages(reply.images, index)"
                        />
                    </view>
                </view>
            </view>

            <view v-if="review.is_owner" class="section-card wm-form-block">
                <view class="section-header">
                    <view class="section-title">晒单奖励</view>
                    <view
                        v-if="review.can_apply_share_reward"
                        class="apply-btn"
                        :style="{ background: primaryGradient }"
                        @click="openSharePopup"
                    >
                        申请晒单奖励
                    </view>
                </view>

                <text class="section-tip">
                    {{
                        review.can_apply_share_reward
                            ? '审核通过后可申请晒单奖励。'
                            : shareRewardDisabledText
                    }}
                </text>

                <view v-if="review.share_reward_records?.length" class="share-reward-list">
                    <view
                        v-for="record in review.share_reward_records"
                        :key="record.id"
                        class="share-reward-card wm-soft-card"
                    >
                        <view class="share-reward-header">
                            <view>
                                <text class="share-platform">{{ record.platform_text }}</text>
                                <text class="share-points"
                                    >预计奖励 {{ record.reward_points }} 积分</text
                                >
                            </view>
                            <view class="share-status" :class="`share-status-${record.status}`">
                                {{ record.status_text }}
                            </view>
                        </view>

                        <view class="share-reward-body">
                            <image
                                v-if="record.verify_image"
                                class="share-proof"
                                :src="record.verify_image"
                                mode="aspectFill"
                                @click="previewImages([record.verify_image], 0)"
                            />
                            <view class="share-meta">
                                <text class="share-meta-text"
                                    >申请平台：{{ record.platform_text }}</text
                                >
                                <text class="share-meta-text">
                                    {{
                                        record.audit_time
                                            ? `审核时间：${formatDateTime(record.audit_time)}`
                                            : '审核时间：待审核'
                                    }}
                                </text>
                            </view>
                        </view>
                    </view>
                </view>
                <view v-else class="empty-block">
                    <text>暂无晒单奖励记录</text>
                </view>
            </view>

            <view class="safe-bottom"></view>

            <BaseOverlayMask :show="showSharePopup" @close="closeSharePopup" />
            <tn-popup
                v-model="showSharePopup"
                open-direction="bottom"
                :radius="32"
                :overlay="false"
                :overlay-closeable="true"
            >
                <view class="share-popup">
                    <view class="popup-header">
                        <text class="popup-title">申请晒单奖励</text>
                        <tn-icon name="close" size="36" color="#9A9388" @click="closeSharePopup" />
                    </view>

                    <view class="popup-section">
                        <text class="popup-label">选择平台</text>
                        <view class="platform-list">
                            <view
                                v-for="option in platformOptions"
                                :key="option.value"
                                class="platform-item"
                                :class="{ active: shareForm.share_platform === option.value }"
                                :style="
                                    shareForm.share_platform === option.value
                                        ? {
                                              background: primaryGradient,
                                              borderColor: $theme.primaryColor,
                                              color: '#FFFFFF'
                                          }
                                        : {}
                                "
                                @click="shareForm.share_platform = option.value"
                            >
                                {{ option.label }}
                            </view>
                        </view>
                    </view>

                    <view class="popup-section">
                        <text class="popup-label">上传核验截图</text>
                        <text class="popup-tip">请上传晒单截图。</text>
                        <view class="upload-area">
                            <view
                                v-if="shareForm.verify_image"
                                class="upload-preview"
                                @click="previewImages([shareForm.verify_image], 0)"
                            >
                                <image :src="shareForm.verify_image" mode="aspectFill" />
                                <view
                                    class="upload-remove"
                                    @click.stop="shareForm.verify_image = ''"
                                >
                                    <tn-icon name="close" size="24" color="#FFFFFF" />
                                </view>
                            </view>
                            <view v-else class="upload-box" @click="chooseVerifyImage">
                                <tn-icon
                                    :name="shareForm.uploading ? 'loading' : 'camera-fill'"
                                    size="40"
                                    :color="$theme.primaryColor"
                                />
                                <text class="upload-text">
                                    {{ shareForm.uploading ? '上传中...' : '选择截图' }}
                                </text>
                            </view>
                        </view>
                    </view>

                    <view class="popup-actions">
                        <view class="popup-btn popup-btn--ghost" @click="closeSharePopup"
                            >取消</view
                        >
                        <view
                            class="popup-btn popup-btn--primary"
                            :style="{ background: primaryGradient }"
                            @click="submitShareReward"
                        >
                            提交申请
                        </view>
                    </view>
                </view>
            </tn-popup>
        </view>

        <view v-else class="loading-wrap">
            <tn-loading mode="circle" />
            <text class="loading-text">评价详情加载中...</text>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { onLoad, onPullDownRefresh } from '@dcloudio/uni-app'
import { getReviewDetail, applyShareReward } from '@/packages/common/api/review'
import { uploadImage } from '@/api/app'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()

const reviewId = ref(0)
const review = ref<any>(null)
const showSharePopup = ref(false)
const shareForm = reactive({
    share_platform: '',
    verify_image: '',
    uploading: false,
    submitting: false
})

const platformOptions = [
    { label: '微信好友', value: 'wechat' },
    { label: '朋友圈', value: 'moments' },
    { label: '微博', value: 'weibo' },
    { label: '抖音', value: 'douyin' },
    { label: '小红书', value: 'xiaohongshu' }
]

const primaryGradient = computed(
    () => `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.secondaryColor} 100%)`
)
const pageStyle = computed(() => {
    const base = String($theme.pageStyle || '').trim()
    const separator = !base || base.endsWith(';') ? '' : ';'
    return `${base}${separator}overflow:${showSharePopup.value ? 'hidden' : 'visible'};`
})

const rewardStatus = computed(() => {
    if (review.value?.reward_grant_time) {
        return {
            text: '已发放',
            className: 'reward-status-approved'
        }
    }

    if (Number(review.value?.status) === 1) {
        return {
            text: '待发放',
            className: 'reward-status-pending'
        }
    }

    if (Number(review.value?.status) === 2) {
        return {
            text: '不发放',
            className: 'reward-status-rejected'
        }
    }

    return {
        text: '待审核',
        className: 'reward-status-pending'
    }
})

const rewardPendingText = computed(() => {
    if (Number(review.value?.status) === 2) {
        return '评价未通过审核。'
    }
    if (Number(review.value?.status) === 1) {
        return '积分发放中。'
    }
    return '审核通过后发放积分。'
})

const shareRewardDisabledText = computed(() => {
    if (Number(review.value?.status) === 2) {
        return '当前不可申请晒单奖励。'
    }
    return '审核通过后可申请晒单奖励。'
})

const formatDateTime = (value: number | string) => {
    if (!value) {
        return '-'
    }

    if (typeof value === 'number') {
        return new Date(value * 1000).toLocaleString()
    }

    const normalized = value.includes('T') ? value : value.replace(' ', 'T')
    const time = new Date(normalized).getTime()
    if (Number.isNaN(time)) {
        return value
    }
    return new Date(time).toLocaleString()
}

const previewImages = (images: Array<string | number>, index: number | string = 0) => {
    const urls = (images || []).map((item) => String(item)).filter(Boolean)
    if (!urls.length) {
        return
    }
    const currentIndex = Number(index || 0)
    uni.previewImage({
        urls,
        current: urls[currentIndex] || urls[0]
    })
}

const resetShareForm = () => {
    shareForm.share_platform = ''
    shareForm.verify_image = ''
    shareForm.uploading = false
    shareForm.submitting = false
}

const fetchDetail = async () => {
    if (!reviewId.value) {
        return
    }
    try {
        review.value = await getReviewDetail({ id: reviewId.value })
    } catch (e: any) {
        uni.showToast({ title: e?.message || e || '加载失败', icon: 'none' })
    } finally {
        uni.stopPullDownRefresh()
    }
}

const openSharePopup = () => {
    resetShareForm()
    showSharePopup.value = true
}

const closeSharePopup = () => {
    showSharePopup.value = false
    resetShareForm()
}

const chooseVerifyImage = () => {
    if (shareForm.uploading) {
        return
    }

    uni.chooseImage({
        count: 1,
        sizeType: ['compressed'],
        sourceType: ['album', 'camera'],
        success: async (res) => {
            const path = res.tempFilePaths?.[0]
            if (!path) {
                return
            }

            try {
                shareForm.uploading = true
                const uploadRes: any = await uploadImage(path)
                if (uploadRes?.uri) {
                    shareForm.verify_image = uploadRes.uri
                    return
                }
                uni.showToast({ title: '上传失败，请重试', icon: 'none' })
            } catch (e: any) {
                uni.showToast({ title: e?.message || e || '上传失败', icon: 'none' })
            } finally {
                shareForm.uploading = false
            }
        }
    })
}

const submitShareReward = async () => {
    if (shareForm.submitting || shareForm.uploading) {
        return
    }
    if (!shareForm.share_platform) {
        uni.showToast({ title: '请选择晒单平台', icon: 'none' })
        return
    }
    if (!shareForm.verify_image) {
        uni.showToast({ title: '请上传核验截图', icon: 'none' })
        return
    }

    try {
        shareForm.submitting = true
        await applyShareReward({
            review_id: review.value.id,
            share_platform: shareForm.share_platform,
            verify_image: shareForm.verify_image
        })
        uni.showToast({ title: '申请已提交', icon: 'success' })
        closeSharePopup()
        fetchDetail()
    } catch (e: any) {
        uni.showToast({ title: e?.message || e || '提交失败', icon: 'none' })
    } finally {
        shareForm.submitting = false
    }
}

onLoad((options: any) => {
    reviewId.value = Number(options?.id || 0)
    fetchDetail()
})

onPullDownRefresh(() => {
    fetchDetail()
})
</script>

<style scoped lang="scss">
.review-detail-page {
    background: transparent;
    padding-bottom: calc(env(safe-area-inset-bottom) + 24rpx);
}

.hero-card,
.section-card {
    padding: 28rpx;
    margin-bottom: 24rpx;
}

.section-tip {
    display: block;
    font-size: 26rpx;
    line-height: 1.75;
    color: #6c665c;

    & + .section-tip {
        margin-top: 12rpx;
    }
}

.hero-header,
.section-header,
.reward-panel,
.share-reward-header,
.reply-header,
.meta-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.hero-user,
.user-info,
.score-row,
.meta-list,
.tag-list,
.image-grid,
.share-reward-list,
.reply-images,
.platform-list,
.popup-actions {
    display: flex;
}

.hero-user {
    align-items: center;
    gap: 16rpx;
}

.user-avatar {
    width: 88rpx;
    height: 88rpx;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.82);
}

.user-info {
    flex: 1;
    min-width: 0;
    flex-direction: column;
    gap: 6rpx;
}

.user-name {
    font-size: 30rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #111111);
}

.review-time {
    font-size: 24rpx;
    color: var(--wm-text-tertiary, #9a9388);
}

.status-badge,
.reward-status,
.share-status {
    padding: 10rpx 18rpx;
    border-radius: 999rpx;
    font-size: 22rpx;
    font-weight: 600;
}

.status-0,
.reward-status-pending,
.share-status-0 {
    background: rgba(159, 122, 46, 0.12);
    color: #9f7a2e;
}

.status-1,
.reward-status-approved,
.share-status-1 {
    background: rgba(77, 74, 66, 0.12);
    color: #4D4A42;
}

.status-2,
.reward-status-rejected,
.share-status-2 {
    background: rgba(90, 68, 51, 0.12);
    color: #5a4433;
}

.score-row {
    align-items: center;
    gap: 12rpx;
    margin-top: 28rpx;
}

.score-stars {
    display: inline-flex;
    align-items: center;
    gap: 6rpx;
}

.score-text {
    font-size: 26rpx;
    color: #5F5A50;
}

.meta-list {
    flex-direction: column;
    gap: 18rpx;
    margin-top: 28rpx;
}

.meta-label,
.popup-label {
    font-size: 24rpx;
    color: #9a9388;
}

.meta-value {
    flex: 1;
    text-align: right;
    font-size: 26rpx;
    color: #5F5A50;
}

.section-title,
.popup-title {
    font-size: 30rpx;
    font-weight: 700;
    color: #111111;
}

.review-content,
.reply-content,
.section-tip,
.reward-tip,
.popup-tip,
.share-meta-text {
    display: block;
    font-size: 26rpx;
    line-height: 1.7;
    color: #5F5A50;
}

.empty-text,
.empty-block text {
    font-size: 26rpx;
    color: #9a9388;
}

.tag-list {
    flex-wrap: wrap;
    gap: 12rpx;
    margin-top: 24rpx;
}

.tag-item {
    padding: 10rpx 18rpx;
    border-radius: 999rpx;
    background: rgba(11, 11, 11, 0.08);
    border: 1rpx solid rgba(11, 11, 11, 0.16);
    font-size: 24rpx;
    color: var(--wm-color-primary, #0b0b0b);
}

.image-grid,
.reply-images {
    flex-wrap: wrap;
    gap: 16rpx;
    margin-top: 24rpx;
}

.review-image,
.reply-image {
    width: calc((100% - 32rpx) / 3);
    height: 200rpx;
    border-radius: 18rpx;
    background: #f8f7f2;
}

.reward-panel {
    margin-top: 12rpx;
}

.reward-main {
    display: flex;
    align-items: baseline;
    gap: 8rpx;
}

.reward-points {
    font-size: 54rpx;
    font-weight: 700;
    color: var(--wm-color-primary, #0b0b0b);
}

.reward-unit,
.share-points {
    font-size: 24rpx;
    color: #9a9388;
}

.reward-tip {
    margin-top: 12rpx;
}

.reply-card,
.share-reward-card {
    padding: 22rpx 24rpx;
    border-radius: 20rpx;
    background: #ffffff;
    border: 1rpx solid #E7E2D6;
}

.reply-card + .reply-card,
.share-reward-card + .share-reward-card {
    margin-top: 16rpx;
}

.reply-type,
.share-platform {
    font-size: 26rpx;
    font-weight: 600;
    color: #5F5A50;
}

.reply-time {
    font-size: 22rpx;
    color: #9a9388;
}

.section-tip {
    margin-top: 12rpx;
}

.apply-btn {
    min-width: 180rpx;
    height: 68rpx;
    padding: 0 24rpx;
    border-radius: 999rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 24rpx;
    color: #ffffff;
    font-weight: 600;
}

.share-reward-list {
    margin-top: 24rpx;
    flex-direction: column;
    gap: 16rpx;
}

.share-reward-body {
    display: flex;
    gap: 16rpx;
    margin-top: 18rpx;
}

.share-proof {
    width: 160rpx;
    height: 160rpx;
    border-radius: 16rpx;
    background: #e7e2d6;
    flex-shrink: 0;
}

.share-meta {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 10rpx;
}

.empty-block {
    margin-top: 24rpx;
    padding: 40rpx 0;
    text-align: center;
    background: #ffffff;
    border-radius: 18rpx;
}

.share-popup {
    padding: 32rpx 28rpx calc(env(safe-area-inset-bottom) + 28rpx);
    background: #ffffff;
}

.popup-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.popup-section {
    margin-top: 28rpx;
}

.platform-list {
    flex-wrap: wrap;
    gap: 16rpx;
    margin-top: 18rpx;
}

.platform-item {
    min-width: 180rpx;
    height: 72rpx;
    padding: 0 24rpx;
    border-radius: 18rpx;
    border: 1rpx solid #e7e2d6;
    background: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 24rpx;
    color: #5f5a50;
}

.upload-area {
    margin-top: 20rpx;
}

.upload-box,
.upload-preview {
    width: 240rpx;
    height: 240rpx;
    border-radius: 24rpx;
}

.upload-box {
    border: 2rpx dashed rgba(11, 11, 11, 0.22);
    background: rgba(11, 11, 11, 0.05);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12rpx;
}

.upload-text {
    font-size: 24rpx;
    color: #5F5A50;
}

.upload-preview {
    position: relative;
    overflow: hidden;
    background: #f8f7f2;
}

.upload-preview image {
    width: 100%;
    height: 100%;
}

.upload-remove {
    position: absolute;
    top: 12rpx;
    right: 12rpx;
    width: 44rpx;
    height: 44rpx;
    border-radius: 50%;
    background: rgba(17, 17, 17, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
}

.popup-actions {
    gap: 16rpx;
    margin-top: 36rpx;
}

.popup-btn {
    flex: 1;
    height: 80rpx;
    border-radius: 999rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28rpx;
    font-weight: 600;
}

.popup-btn--ghost {
    border: 2rpx solid #D8D3C7;
    color: #5F5A50;
}

.popup-btn--primary {
    color: #ffffff;
}

.loading-wrap {
    min-height: 100vh;
    background: transparent;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 24rpx;
}

.loading-text {
    font-size: 26rpx;
    color: #9a9388;
}

.safe-bottom {
    height: 1rpx;
}
</style>
