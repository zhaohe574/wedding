<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="发表评价"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>
    <view class="publish-page">
        <!-- 订单信息 -->
        <view class="bg-white p-4" v-if="orderItem">
            <view class="flex items-center">
                <image
                    :src="orderItem.staff?.avatar || '/static/images/default-avatar.png'"
                    class="w-16 h-16 rounded-full mr-3"
                    mode="aspectFill"
                />
                <view class="flex-1">
                    <view class="text-base font-medium">{{ orderItem.staff_name }}</view>
                    <view class="text-sm text-gray-400 mt-1">{{ orderItem.package_name }}</view>
                    <view class="text-xs text-gray-400 mt-1">
                        服务日期: {{ orderItem.order?.service_date }}
                    </view>
                </view>
            </view>
        </view>

        <!-- 评分 -->
        <view class="bg-white mt-3 p-4">
            <view class="section-title">服务评分</view>

            <view class="score-item">
                <text class="score-label">综合评分</text>
                <view class="score-stars">
                    <uni-icons
                        v-for="i in 5"
                        :key="i"
                        :type="i <= formData.score ? 'star-filled' : 'star'"
                        size="28"
                        :color="i <= formData.score ? '#ff9800' : '#ddd'"
                        @click="formData.score = i"
                    />
                </view>
                <text class="score-text">{{ scoreTexts[formData.score - 1] }}</text>
            </view>

            <view class="divider"></view>

            <view class="detail-scores">
                <view class="detail-score-item" v-for="item in detailScores" :key="item.key">
                    <text class="label">{{ item.label }}</text>
                    <view class="stars">
                        <uni-icons
                            v-for="i in 5"
                            :key="i"
                            :type="i <= formData[item.key] ? 'star-filled' : 'star'"
                            size="20"
                            :color="i <= formData[item.key] ? '#ff9800' : '#ddd'"
                            @click="formData[item.key] = i"
                        />
                    </view>
                </view>
            </view>
        </view>

        <!-- 评价标签 -->
        <view class="bg-white mt-3 p-4" v-if="tags.length">
            <view class="section-title">选择标签</view>
            <view class="tag-list">
                <view
                    v-for="tag in tags"
                    :key="tag.id"
                    class="tag-item"
                    :class="{ active: selectedTags.includes(tag.id) }"
                    @click="toggleTag(tag.id)"
                >
                    {{ tag.name }}
                </view>
            </view>
        </view>

        <!-- 评价内容 -->
        <view class="bg-white mt-3 p-4">
            <view class="section-title">评价内容</view>
            <textarea
                v-model="formData.content"
                class="content-input"
                placeholder="分享您的服务体验，帮助更多人选择..."
                maxlength="500"
            />
            <view class="text-right text-xs text-gray-400">{{ formData.content.length }}/500</view>
        </view>

        <!-- 上传图片/视频 -->
        <view class="bg-white mt-3 p-4">
            <view class="section-title">上传图片/视频（选填）</view>
            <view class="media-uploader">
                <view v-for="(img, index) in formData.images" :key="index" class="media-item">
                    <image :src="img" class="media-image" mode="aspectFill" />
                    <view class="delete-btn" @click="removeImage(index)">
                        <uni-icons type="close" size="14" color="#fff"></uni-icons>
                    </view>
                </view>
                <view class="add-media" @click="chooseImage" v-if="formData.images.length < 9">
                    <uni-icons type="camera" size="40" color="#ccc"></uni-icons>
                    <text class="text-xs text-gray-400 mt-1">添加图片</text>
                </view>
            </view>
            <view class="text-xs text-gray-400 mt-2">最多上传9张图片</view>
        </view>

        <!-- 匿名评价 -->
        <view class="bg-white mt-3 p-4">
            <view class="flex justify-between items-center">
                <text class="text-sm">匿名评价</text>
                <switch
                    :checked="formData.is_anonymous === 1"
                    @change="formData.is_anonymous = $event.detail.value ? 1 : 0"
                    color="#ff6b35"
                />
            </view>
        </view>

        <!-- 奖励提示 -->
        <view class="reward-tip" v-if="rewardPoints > 0">
            <uni-icons type="gift" size="18" color="#ff6b35"></uni-icons>
            <text
                >发布评价可获得 <text class="highlight">{{ rewardPoints }}</text> 积分奖励</text
            >
        </view>

        <!-- 提交按钮 -->
        <view class="bottom-actions">
            <button class="btn-submit" :disabled="submitting" @click="handleSubmit">
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

    let type = 1 // 文字评价
    if (formData.images.length > 0) type = 2 // 图文评价
    if (formData.video) type = 3 // 视频评价

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
}

.section-title {
    font-size: 28rpx;
    font-weight: bold;
    color: #333;
    margin-bottom: 20rpx;
}

.score-item {
    display: flex;
    align-items: center;
    padding: 20rpx 0;

    .score-label {
        font-size: 28rpx;
        color: #333;
        width: 140rpx;
    }

    .score-stars {
        display: flex;
        gap: 8rpx;
    }

    .score-text {
        font-size: 26rpx;
        color: #ff9800;
        margin-left: 16rpx;
    }
}

.divider {
    height: 1rpx;
    background: #f0f0f0;
    margin: 20rpx 0;
}

.detail-scores {
    .detail-score-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16rpx 0;

        .label {
            font-size: 26rpx;
            color: #666;
        }

        .stars {
            display: flex;
            gap: 4rpx;
        }
    }
}

.tag-list {
    display: flex;
    flex-wrap: wrap;
    gap: 16rpx;
}

.tag-item {
    padding: 12rpx 24rpx;
    background: #f5f5f5;
    border-radius: 30rpx;
    font-size: 26rpx;
    color: #666;
    border: 2rpx solid transparent;

    &.active {
        background: rgba(255, 107, 53, 0.1);
        color: var(--primary-color, #ff6b35);
        border-color: var(--primary-color, #ff6b35);
    }
}

.content-input {
    width: 100%;
    height: 200rpx;
    padding: 20rpx;
    background: #f9f9f9;
    border-radius: 12rpx;
    font-size: 28rpx;
}

.media-uploader {
    display: flex;
    flex-wrap: wrap;
    gap: 16rpx;
}

.media-item {
    position: relative;
    width: 160rpx;
    height: 160rpx;
}

.media-image {
    width: 100%;
    height: 100%;
    border-radius: 8rpx;
}

.delete-btn {
    position: absolute;
    top: -10rpx;
    right: -10rpx;
    width: 36rpx;
    height: 36rpx;
    background: rgba(0, 0, 0, 0.5);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.add-media {
    width: 160rpx;
    height: 160rpx;
    border: 2rpx dashed #ddd;
    border-radius: 8rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.reward-tip {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    padding: 20rpx;
    margin: 24rpx;
    background: rgba(255, 107, 53, 0.1);
    border-radius: 12rpx;
    font-size: 26rpx;
    color: #666;

    .highlight {
        color: var(--primary-color, #ff6b35);
        font-weight: bold;
    }
}

.bottom-actions {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 20rpx 30rpx;
    background: #fff;
    box-shadow: 0 -2rpx 10rpx rgba(0, 0, 0, 0.05);
}

.btn-submit {
    width: 100%;
    height: 88rpx;
    line-height: 88rpx;
    background: var(--primary-color, #ff6b35);
    color: #fff;
    border-radius: 44rpx;
    font-size: 30rpx;
    border: none;

    &[disabled] {
        opacity: 0.6;
    }
}
</style>
