<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="发表评价" />
        <view class="publish-page wm-page-content">
            <!-- 顶部渐变背景 -->
            <view
                class="top-bg"
                :style="{
                    background: `linear-gradient(135deg, ${$theme.primaryColor}, ${$theme.primaryColor}88)`
                }"
            ></view>

            <!-- 顶部标题区域 -->
            <view class="top-header">
                <text class="top-header-title">分享您的体验</text>
                <text class="top-header-desc">欢迎留下真实体验</text>
            </view>

            <!-- 订单信息卡片 -->
            <view class="order-card wm-panel-card" v-if="orderItem">
                <view class="order-card__main">
                    <image
                        :src="orderItem.staff?.avatar || '/static/images/user/default_avatar.png'"
                        class="staff-avatar"
                        mode="aspectFill"
                    />
                    <view class="order-card__copy">
                        <view class="order-card__title">{{ orderItem.staff_name }}</view>
                        <view class="order-card__meta">{{ orderItem.package_name }}</view>
                        <view class="order-card__submeta">
                            服务日期: {{ orderItem.order?.service_date }}
                        </view>
                    </view>
                </view>
            </view>

            <!-- 综合评分 -->
            <view class="section-card wm-form-block">
                <view class="section-header">
                    <view class="section-dot" :style="{ background: $theme.primaryColor }"></view>
                    <text class="section-title">服务评分</text>
                </view>

                <view class="main-score">
                    <text class="main-score-label">综合评分</text>
                    <view class="main-score-stars">
                        <view
                            v-for="i in 5"
                            :key="i"
                            class="star-touch"
                            @click="formData.score = i"
                        >
                            <tn-icon
                                :name="i <= formData.score ? 'star-fill' : 'star'"
                                size="64rpx"
                                :color="i <= formData.score ? '#9f7a2e' : '#E7E2D6'"
                            />
                        </view>
                    </view>
                    <view class="score-badge" :style="{ background: $theme.primaryColor }">
                        <text class="score-badge-text">{{ scoreTexts[formData.score - 1] }}</text>
                    </view>
                </view>

                <view class="detail-divider"></view>

                <view class="detail-scores">
                    <view class="detail-score-row" v-for="item in detailScores" :key="item.key">
                        <text class="detail-label">{{ item.label }}</text>
                        <view class="detail-stars">
                            <view
                                v-for="i in 5"
                                :key="i"
                                class="star-touch-sm"
                                @click="formData[item.key] = i"
                            >
                                <tn-icon
                                    :name="i <= formData[item.key] ? 'star-fill' : 'star'"
                                    size="44rpx"
                                    :color="i <= formData[item.key] ? '#9f7a2e' : '#E7E2D6'"
                                />
                            </view>
                        </view>
                    </view>
                </view>
            </view>

            <!-- 评价标签 -->
            <view class="section-card wm-form-block" v-if="tags.length">
                <view class="section-header">
                    <view class="section-dot" :style="{ background: $theme.primaryColor }"></view>
                    <text class="section-title">选择标签</text>
                    <view class="tag-header-right">
                        <text
                            class="tag-count-num"
                            :style="{
                                color: selectedTags.length > 0 ? $theme.primaryColor : '#9A9388'
                            }"
                            >{{ selectedTags.length }}</text
                        >
                        <text class="tag-count-sep">/5</text>
                    </view>
                </view>
                <!-- 评分感知提示 -->
                <view class="tag-hint">
                    <view class="tag-hint-icon" :style="{ background: scoreHintColor + '18' }">
                        <tn-icon :name="scoreHintIcon" size="28rpx" :color="scoreHintColor" />
                    </view>
                    <text class="tag-hint-text">{{ scoreHintText }}</text>
                </view>
                <!-- 标签网格 -->
                <view class="tag-grid">
                    <view
                        v-for="tag in tags"
                        :key="tag.id"
                        class="tag-chip"
                        :class="{ 'tag-chip--active': selectedTags.includes(tag.id) }"
                        :style="
                            selectedTags.includes(tag.id)
                                ? {
                                      color: '#fff',
                                      borderColor: $theme.primaryColor,
                                      background: $theme.primaryColor
                                  }
                                : {}
                        "
                        @click="toggleTag(tag.id)"
                    >
                        <tn-icon
                            v-if="selectedTags.includes(tag.id)"
                            name="success"
                            size="24rpx"
                            color="#fff"
                            class="tag-chip-icon"
                        />
                        <text>{{ tag.name }}</text>
                    </view>
                </view>
                <!-- 已选标签预览 -->
                <view class="tag-selected-bar" v-if="selectedTags.length > 0">
                    <view class="tag-selected-list">
                        <view
                            v-for="tagId in selectedTags"
                            :key="tagId"
                            class="tag-mini"
                            :style="{
                                background: $theme.primaryColor + '15',
                                color: $theme.primaryColor
                            }"
                            @click="toggleTag(tagId)"
                        >
                            <text>{{ getTagName(tagId) }}</text>
                            <tn-icon name="close" size="20rpx" :color="$theme.primaryColor" />
                        </view>
                    </view>
                </view>
            </view>

            <!-- 评价内容 -->
            <view class="section-card wm-form-block">
                <view class="section-header">
                    <view class="section-dot" :style="{ background: $theme.primaryColor }"></view>
                    <text class="section-title">评价内容</text>
                </view>
                <textarea
                    v-model="formData.content"
                    class="content-input"
                    placeholder="写下您的体验..."
                    maxlength="500"
                    :cursor-spacing="120"
                />
                <view class="content-counter">{{ formData.content.length }}/500</view>
            </view>

            <!-- 上传图片/视频 -->
            <view class="section-card wm-form-block">
                <view class="section-header">
                    <view class="section-dot" :style="{ background: $theme.primaryColor }"></view>
                    <text class="section-title">上传图片/视频（选填）</text>
                </view>
                <view class="media-uploader">
                    <view v-for="(img, index) in formData.images" :key="index" class="media-item">
                        <image :src="img" class="media-image" mode="aspectFill" />
                        <view class="delete-btn" @click="removeImage(index)">
                            <tn-icon name="close" size="24rpx" color="#fff"></tn-icon>
                        </view>
                    </view>
                    <view class="add-media" @click="chooseImage" v-if="formData.images.length < 9">
                        <tn-icon name="camera" size="56rpx" color="#D8D3C7"></tn-icon>
                        <text class="add-media-text">添加图片</text>
                    </view>
                </view>
                <view class="media-caption">最多上传9张图片</view>
            </view>

            <!-- 匿名评价 -->
            <view class="section-card wm-form-block">
                <view class="anonymous-row">
                    <view class="anonymous-row__main">
                        <tn-icon name="my" size="36rpx" color="#9A9388"></tn-icon>
                        <text class="anonymous-row__text">匿名评价</text>
                    </view>
                    <switch
                        :checked="formData.is_anonymous === 1"
                        @change="handleAnonymousChange"
                        :color="$theme.primaryColor"
                    />
                </view>
            </view>

            <!-- 奖励提示 -->
            <view
                class="reward-card"
                v-if="rewardPoints > 0"
                :style="{
                    background: $theme.primaryColor + '10',
                    borderColor: $theme.primaryColor + '30'
                }"
            >
                <view class="reward-icon-wrap" :style="{ background: $theme.primaryColor + '20' }">
                    <tn-icon name="gift" size="40rpx" :color="$theme.primaryColor"></tn-icon>
                </view>
                <view class="reward-info">
                    <text class="reward-text">审核通过后发放积分</text>
                    <text class="reward-points" :style="{ color: $theme.primaryColor }"
                        >预计 +{{ rewardPoints }} 积分</text
                    >
                </view>
            </view>

            <ActionArea class="publish-page__action" sticky safeBottom>
                <BaseButton
                    block
                    size="lg"
                    :disabled="submitting || mediaUploading"
                    :loading="submitting"
                    @click="handleSubmit"
                >
                    {{ submitting ? '提交中...' : mediaUploading ? '图片上传中...' : '发布评价' }}
                </BaseButton>
            </ActionArea>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { uploadImage } from '@/api/app'
import {
    getPendingOrders,
    publishReview,
    getReviewTags,
    getRewardRules
} from '@/packages/common/api/review'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()

const orderItemId = ref(0)
const orderItem = ref<any>(null)
const submitting = ref(false)
const mediaUploading = ref(false)
const tags = ref<any[]>([])
const selectedTags = ref<number[]>([])
const rewardConfig = ref<any[]>([])

const formData = reactive({
    score: 5,
    score_service: 5,
    score_professional: 5,
    score_punctual: 5,
    score_effect: 5,
    content: '',
    images: [] as string[],
    video: '',
    is_anonymous: 0
})

const scoreTexts = ['非常差', '较差', '一般', '满意', '非常满意']
type DetailScoreKey = 'score_service' | 'score_professional' | 'score_punctual' | 'score_effect'

// 评分感知提示
const scoreHintText = computed(() => {
    if (formData.score >= 4) return '选择满意标签'
    if (formData.score === 3) return '选择体验标签'
    return '选择问题标签'
})

const scoreHintIcon = computed(() => {
    if (formData.score >= 4) return 'like-fill'
    if (formData.score === 3) return 'tip'
    return 'clock'
})

const scoreHintColor = computed(() => {
    if (formData.score >= 4) return '#4d4a42'
    if (formData.score === 3) return '#c8a45d'
    return '#5a4433'
})

// 根据标签ID获取标签名
const getTagName = (tagId: number) => {
    return tags.value.find((t: any) => t.id === tagId)?.name || ''
}

const detailScores: Array<{ key: DetailScoreKey; label: string }> = [
    { key: 'score_service', label: '服务态度' },
    { key: 'score_professional', label: '专业水平' },
    { key: 'score_punctual', label: '时间守约' },
    { key: 'score_effect', label: '整体效果' }
]

// 计算奖励积分
const rewardPoints = computed(() => {
    if (!rewardConfig.value.length) return 0

    let type = 1
    if (formData.images.length > 0) type = 2
    if (formData.video) type = 3

    const config = rewardConfig.value.find((c) => c.reward_type === type)
    if (!config) return 0

    let points = config.reward_points || 0
    if (formData.score >= 4 && config.extra_points_for_good) {
        points += config.extra_points_for_good
    }
    return points
})

// 加载订单项信息
const loadOrderItem = async () => {
    try {
        const res = await getPendingOrders({ page: 1, limit: 100 })
        const item = res.lists?.find((item: any) => item.id === orderItemId.value)
        if (item) {
            orderItem.value = item
        }
    } catch (e) {
        console.error(e)
    }
}

// 加载标签
const loadTags = async () => {
    try {
        const res = await getReviewTags({ score: formData.score })
        tags.value = res || []
        selectedTags.value = []
    } catch (e) {
        console.error(e)
    }
}

// 加载奖励配置
const loadRewardConfig = async () => {
    try {
        const res = await getRewardRules()
        rewardConfig.value = res || []
    } catch (e) {
        console.error(e)
    }
}

// 监听评分变化，重新加载标签
watch(
    () => formData.score,
    () => {
        loadTags()
    }
)

// 切换标签
const toggleTag = (tagId: number) => {
    const index = selectedTags.value.indexOf(tagId)
    if (index > -1) {
        selectedTags.value.splice(index, 1)
    } else {
        if (selectedTags.value.length < 5) {
            selectedTags.value.push(tagId)
        } else {
            uni.showToast({ title: '最多选择5个标签', icon: 'none' })
        }
    }
}

// 选择图片
const chooseImage = () => {
    if (mediaUploading.value || submitting.value) {
        return
    }

    uni.chooseImage({
        count: 9 - formData.images.length,
        sizeType: ['compressed'],
        sourceType: ['album', 'camera'],
        success: async (res) => {
            const filePaths = Array.isArray(res.tempFilePaths) ? res.tempFilePaths : []
            if (!filePaths.length) {
                return
            }

            mediaUploading.value = true
            let uploadedCount = 0
            let failedCount = 0

            try {
                for (const path of filePaths) {
                    try {
                        const uploadRes: any = await uploadImage(path)
                        const url = String(uploadRes?.uri || uploadRes?.url || '').trim()
                        if (!url) {
                            failedCount++
                            continue
                        }

                        formData.images.push(url)
                        uploadedCount++
                    } catch (error) {
                        failedCount++
                    }
                }

                if (failedCount > 0) {
                    uni.showToast({
                        title:
                            uploadedCount > 0
                                ? `已上传${uploadedCount}张，${failedCount}张失败`
                                : '图片上传失败，请重试',
                        icon: 'none'
                    })
                }
            } finally {
                mediaUploading.value = false
            }
        }
    })
}

// 删除图片
const removeImage = (index: number) => {
    formData.images.splice(index, 1)
}

const handleAnonymousChange = (event: Event) => {
    const changeEvent = event as Event & { detail?: { value?: boolean } }
    formData.is_anonymous = changeEvent.detail?.value ? 1 : 0
}

// 提交评价
const handleSubmit = async () => {
    if (formData.score < 1) {
        uni.showToast({ title: '请选择评分', icon: 'none' })
        return
    }

    if (mediaUploading.value) {
        uni.showToast({ title: '请等待图片上传完成', icon: 'none' })
        return
    }

    submitting.value = true
    try {
        const params = {
            order_item_id: orderItemId.value,
            score: formData.score,
            score_service: formData.score_service,
            score_professional: formData.score_professional,
            score_punctual: formData.score_punctual,
            score_effect: formData.score_effect,
            content: formData.content,
            images: formData.images,
            video: formData.video,
            is_anonymous: formData.is_anonymous,
            tag_ids: selectedTags.value
        }

        const res = await publishReview(params)

        uni.showModal({
            title: '评价成功',
            content:
                res.reward_points > 0
                    ? `已提交，预计发放${res.reward_points}积分`
                    : '已提交，感谢评价',
            showCancel: false,
            success: () => {
                uni.navigateBack()
            }
        })
    } catch (e: any) {
        uni.showToast({ title: e.message || '提交失败', icon: 'none' })
    } finally {
        submitting.value = false
    }
}

onLoad((options: any) => {
    if (options.order_item_id) {
        orderItemId.value = Number(options.order_item_id)
        loadOrderItem()
    }
    loadTags()
    loadRewardConfig()
})
</script>

<style lang="scss" scoped>
.publish-page {
    background-color: transparent;
    padding-bottom: calc(var(--wm-safe-bottom-action, 160rpx) + 120rpx);
    position: relative;
}

.order-card__main {
    display: flex;
    align-items: center;
    gap: 24rpx;
}

.order-card__copy {
    flex: 1;
    min-width: 0;
}

.order-card__title {
    font-size: 32rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #111111);
}

.order-card__meta {
    margin-top: 8rpx;
    font-size: 24rpx;
    color: var(--wm-text-secondary, #5f5a50);
}

.order-card__submeta {
    margin-top: 8rpx;
    font-size: 22rpx;
    color: var(--wm-text-tertiary, #9a9388);
}

.top-bg {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 380rpx;
    border-radius: 0 0 60rpx 60rpx;
}

/* 顶部标题 */
.top-header {
    position: relative;
    padding: 32rpx 32rpx 20rpx;
    z-index: 1;
}

.top-header-title {
    display: block;
    font-size: 36rpx;
    font-weight: bold;
    color: #fff;
}

.top-header-desc {
    display: block;
    font-size: 24rpx;
    color: rgba(255, 255, 255, 0.8);
    margin-top: 8rpx;
}

/* 订单信息卡片 */
.order-card {
    position: relative;
    margin: 20rpx 24rpx 0;
    padding: 28rpx;
    background: #fff;
    border-radius: 20rpx;
    box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.06);
}

.staff-avatar {
    width: 100rpx;
    height: 100rpx;
    border-radius: 50%;
    border: 4rpx solid #F8F7F2;
}

/* 通用卡片 */
.section-card {
    position: relative;
    margin: 20rpx 24rpx 0;
    padding: 28rpx;
    background: #fff;
    border-radius: 20rpx;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);
}

.section-header {
    display: flex;
    align-items: center;
    margin-bottom: 24rpx;
}

.section-dot {
    width: 8rpx;
    height: 28rpx;
    border-radius: 4rpx;
    margin-right: 12rpx;
}

.section-title {
    font-size: 28rpx;
    font-weight: bold;
    color: #111111;
    flex: 1;
}

/* 标签头部右侧计数 */
.tag-header-right {
    display: flex;
    align-items: baseline;
}

.tag-count-num {
    font-size: 30rpx;
    font-weight: bold;
    transition: color 0.2s;
}

.tag-count-sep {
    font-size: 24rpx;
    color: #D8D3C7;
}

/* 评分感知提示 */
.tag-hint {
    display: flex;
    align-items: center;
    gap: 12rpx;
    margin-bottom: 24rpx;
    padding: 16rpx 20rpx;
    background: #F8F7F2;
    border-radius: 12rpx;
}

.tag-hint-icon {
    width: 44rpx;
    height: 44rpx;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.tag-hint-text {
    font-size: 24rpx;
    color: #5F5A50;
    line-height: 1.4;
}

/* 标签网格 */
.tag-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 16rpx;
}

.tag-chip {
    display: flex;
    align-items: center;
    gap: 6rpx;
    padding: 16rpx 28rpx;
    background: #F8F7F2;
    border-radius: 40rpx;
    font-size: 26rpx;
    color: #5F5A50;
    border: 2rpx solid #E7E2D6;
    transition: all 0.25s ease;
}

.tag-chip--active {
    box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.1);
}

.tag-chip-icon {
    margin-right: 2rpx;
}

/* 已选标签预览 */
.tag-selected-bar {
    margin-top: 20rpx;
    padding-top: 20rpx;
    border-top: 1rpx solid #F8F7F2;
}

.tag-selected-list {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
}

.tag-mini {
    display: flex;
    align-items: center;
    gap: 6rpx;
    padding: 8rpx 18rpx;
    border-radius: 20rpx;
    font-size: 22rpx;
}
.main-score {
    display: flex;
    align-items: center;
    padding: 16rpx 0 24rpx;
}

.main-score-label {
    font-size: 28rpx;
    color: #111111;
    font-weight: 500;
    width: 140rpx;
    flex-shrink: 0;
}

.main-score-stars {
    display: flex;
    gap: 4rpx;
    flex: 1;
}

.star-touch {
    padding: 4rpx;
    cursor: pointer;
}

.star-touch-sm {
    padding: 2rpx;
    cursor: pointer;
}

.score-badge {
    padding: 6rpx 16rpx;
    border-radius: 20rpx;
    margin-left: 12rpx;
    flex-shrink: 0;
}

.score-badge-text {
    font-size: 22rpx;
    color: #fff;
    white-space: nowrap;
}

.detail-divider {
    height: 1rpx;
    background: linear-gradient(to right, transparent, #E7E2D6, transparent);
    margin: 8rpx 0 16rpx;
}

.detail-scores {
    .detail-score-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14rpx 0;

        .detail-label {
            font-size: 26rpx;
            color: #5F5A50;
            width: 140rpx;
        }

        .detail-stars {
            display: flex;
            gap: 2rpx;
        }
    }
}

/* 评价内容 */
.content-input {
    width: 100%;
    height: 220rpx;
    padding: 20rpx;
    background: #F8F7F2;
    border-radius: 16rpx;
    font-size: 28rpx;
    line-height: 1.6;
}

.content-counter,
.media-caption {
    margin-top: 8rpx;
    text-align: right;
    font-size: 22rpx;
    color: var(--wm-text-tertiary, #9a9388);
}

/* 图片上传 */
.media-uploader {
    display: flex;
    flex-wrap: wrap;
    gap: 16rpx;
}

.media-item {
    position: relative;
    width: 160rpx;
    height: 160rpx;
    border-radius: 12rpx;
    overflow: visible;
}

.media-image {
    width: 100%;
    height: 100%;
    border-radius: 12rpx;
}

.delete-btn {
    position: absolute;
    top: -12rpx;
    right: -12rpx;
    width: 40rpx;
    height: 40rpx;
    background: rgba(0, 0, 0, 0.55);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
}

.add-media {
    width: 160rpx;
    height: 160rpx;
    border: 2rpx dashed #D8D3C7;
    border-radius: 12rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    background: #F8F7F2;
}

.add-media-text {
    font-size: 22rpx;
    color: #9A9388;
}

/* 奖励卡片 */
.reward-card {
    display: flex;
    align-items: center;
    margin: 24rpx 24rpx 0;
    padding: 24rpx 28rpx;
    border-radius: 16rpx;
    border: 2rpx solid transparent;
    gap: 16rpx;
}

.anonymous-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20rpx;
}

.anonymous-row__main {
    display: flex;
    align-items: center;
    gap: 12rpx;
}

.anonymous-row__text {
    font-size: 26rpx;
    color: var(--wm-text-secondary, #5f5a50);
}

.reward-icon-wrap {
    width: 64rpx;
    height: 64rpx;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.reward-info {
    flex: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.reward-text {
    font-size: 26rpx;
    color: #5F5A50;
}

.reward-points {
    font-size: 30rpx;
    font-weight: bold;
}
</style>
