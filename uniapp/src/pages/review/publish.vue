<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="发表评价"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>
    <view class="publish-page">
        <!-- 顶部渐变背景 -->
        <view class="top-bg" :style="{ background: `linear-gradient(135deg, ${$theme.primaryColor}, ${$theme.primaryColor}88)` }"></view>

        <!-- 顶部标题区域 -->
        <view class="top-header">
            <text class="top-header-title">分享您的体验</text>
            <text class="top-header-desc">您的评价将帮助我们提供更好的服务</text>
        </view>

        <!-- 订单信息卡片 -->
        <view class="order-card" v-if="orderItem">
            <view class="flex items-center">
                <image
                    :src="orderItem.staff?.avatar || '/static/images/user/default_avatar.png'"
                    class="staff-avatar"
                    mode="aspectFill"
                />
                <view class="flex-1 ml-3">
                    <view class="text-base font-bold text-gray-800">{{ orderItem.staff_name }}</view>
                    <view class="text-sm text-gray-400 mt-1">{{ orderItem.package_name }}</view>
                    <view class="text-xs text-gray-400 mt-1">
                        服务日期: {{ orderItem.order?.service_date }}
                    </view>
                </view>
            </view>
        </view>

        <!-- 综合评分 -->
        <view class="section-card">
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
                            :color="i <= formData.score ? '#ff9800' : '#e0e0e0'"
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
                                :color="i <= formData[item.key] ? '#ff9800' : '#e0e0e0'"
                            />
                        </view>
                    </view>
                </view>
            </view>
        </view>

        <!-- 评价标签 -->
        <view class="section-card" v-if="tags.length">
            <view class="section-header">
                <view class="section-dot" :style="{ background: $theme.primaryColor }"></view>
                <text class="section-title">选择标签</text>
                <text class="tag-count">{{ selectedTags.length }}/5</text>
            </view>
            <view class="tag-list">
                <view
                    v-for="tag in tags"
                    :key="tag.id"
                    class="tag-item"
                    :class="{ active: selectedTags.includes(tag.id) }"
                    :style="selectedTags.includes(tag.id) ? {
                        color: $theme.primaryColor,
                        borderColor: $theme.primaryColor,
                        background: $theme.primaryColor + '12'
                    } : {}"
                    @click="toggleTag(tag.id)"
                >
                    {{ tag.name }}
                </view>
            </view>
        </view>

        <!-- 评价内容 -->
        <view class="section-card">
            <view class="section-header">
                <view class="section-dot" :style="{ background: $theme.primaryColor }"></view>
                <text class="section-title">评价内容</text>
            </view>
            <textarea
                v-model="formData.content"
                class="content-input"
                placeholder="分享您的服务体验，帮助更多人选择..."
                maxlength="500"
                :cursor-spacing="120"
            />
            <view class="text-right text-xs text-gray-400 mt-1">{{ formData.content.length }}/500</view>
        </view>

        <!-- 上传图片/视频 -->
        <view class="section-card">
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
                    <tn-icon name="camera" size="56rpx" color="#ccc"></tn-icon>
                    <text class="add-media-text">添加图片</text>
                </view>
            </view>
            <view class="text-xs text-gray-400 mt-2">最多上传9张图片</view>
        </view>

        <!-- 匿名评价 -->
        <view class="section-card">
            <view class="flex justify-between items-center">
                <view class="flex items-center">
                    <tn-icon name="my" size="36rpx" color="#999"></tn-icon>
                    <text class="text-sm text-gray-600 ml-2">匿名评价</text>
                </view>
                <switch
                    :checked="formData.is_anonymous === 1"
                    @change="formData.is_anonymous = $event.detail.value ? 1 : 0"
                    :color="$theme.primaryColor"
                />
            </view>
        </view>

        <!-- 奖励提示 -->
        <view class="reward-card" v-if="rewardPoints > 0" :style="{ background: $theme.primaryColor + '10', borderColor: $theme.primaryColor + '30' }">
            <view class="reward-icon-wrap" :style="{ background: $theme.primaryColor + '20' }">
                <tn-icon name="gift" size="40rpx" :color="$theme.primaryColor"></tn-icon>
            </view>
            <view class="reward-info">
                <text class="reward-text">发布评价可获得积分奖励</text>
                <text class="reward-points" :style="{ color: $theme.primaryColor }">+{{ rewardPoints }} 积分</text>
            </view>
        </view>

        <!-- 提交按钮 -->
        <view class="bottom-actions">
            <button
                class="btn-submit"
                :style="{ background: submitting ? '#ccc' : $theme.primaryColor }"
                :disabled="submitting"
                @click="handleSubmit"
            >
                {{ submitting ? '提交中...' : '发布评价' }}
            </button>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { getPendingOrders, publishReview, getReviewTags, getRewardRules } from '@/api/review'

const orderItemId = ref(0)
const orderItem = ref<any>(null)
const submitting = ref(false)
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

const detailScores = [
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
    uni.chooseImage({
        count: 9 - formData.images.length,
        sizeType: ['compressed'],
        sourceType: ['album', 'camera'],
        success: (res) => {
            formData.images.push(...res.tempFilePaths)
        }
    })
}

// 删除图片
const removeImage = (index: number) => {
    formData.images.splice(index, 1)
}

// 提交评价
const handleSubmit = async () => {
    if (formData.score < 1) {
        uni.showToast({ title: '请选择评分', icon: 'none' })
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
            content: res.reward_points > 0 ? `获得${res.reward_points}积分奖励` : '感谢您的评价',
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
    min-height: 100vh;
    background-color: #f5f5f5;
    padding-bottom: 180rpx;
    position: relative;
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
    border: 4rpx solid #f0f0f0;
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
    color: #333;
    flex: 1;
}

.tag-count {
    font-size: 24rpx;
    color: #999;
}

/* 综合评分 */
.main-score {
    display: flex;
    align-items: center;
    padding: 16rpx 0 24rpx;
}

.main-score-label {
    font-size: 28rpx;
    color: #333;
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
    background: linear-gradient(to right, transparent, #e8e8e8, transparent);
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
            color: #666;
            width: 140rpx;
        }

        .detail-stars {
            display: flex;
            gap: 2rpx;
        }
    }
}

/* 标签 */
.tag-list {
    display: flex;
    flex-wrap: wrap;
    gap: 16rpx;
}

.tag-item {
    padding: 14rpx 28rpx;
    background: #f5f5f5;
    border-radius: 32rpx;
    font-size: 26rpx;
    color: #666;
    border: 2rpx solid transparent;
    transition: all 0.2s;
}

/* 评价内容 */
.content-input {
    width: 100%;
    height: 220rpx;
    padding: 20rpx;
    background: #f8f8f8;
    border-radius: 16rpx;
    font-size: 28rpx;
    line-height: 1.6;
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
    border: 2rpx dashed #d9d9d9;
    border-radius: 12rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    background: #fafafa;
}

.add-media-text {
    font-size: 22rpx;
    color: #bbb;
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
    color: #666;
}

.reward-points {
    font-size: 30rpx;
    font-weight: bold;
}

/* 底部按钮 */
.bottom-actions {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 16rpx 24rpx;
    padding-bottom: calc(16rpx + env(safe-area-inset-bottom));
    background: #fff;
    box-shadow: 0 -2rpx 16rpx rgba(0, 0, 0, 0.06);
}

.btn-submit {
    width: 100%;
    height: 88rpx;
    line-height: 88rpx;
    color: #fff;
    border-radius: 44rpx;
    font-size: 32rpx;
    font-weight: 500;
    border: none;
    letter-spacing: 2rpx;

    &[disabled] {
        opacity: 0.6;
    }
}
</style>
