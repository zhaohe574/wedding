<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="发表评价" />
        <view class="publish-page wm-page-content">

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
                        <view v-for="i in 5" :key="i" class="star-touch star-touch--readonly">
                            <tn-icon
                                :name="i <= overallScoreStars ? 'star-fill' : 'star'"
                                size="64rpx"
                                :color="i <= overallScoreStars ? '#9f7a2e' : '#E7E2D6'"
                            />
                        </view>
                    </view>
                    <view class="score-badge" :style="{ background: $theme.primaryColor }">
                        <text class="score-badge-text">{{ overallScoreText }}分</text>
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
            <view class="section-card wm-form-block">
                <view class="section-header">
                    <view class="section-dot" :style="{ background: $theme.primaryColor }"></view>
                    <text class="section-title">评价标签</text>
                    <view class="tag-header-right">
                        <text
                            class="tag-count-num"
                            :style="{
                                color: selectedTagCount > 0 ? $theme.primaryColor : '#9A9388'
                            }"
                            >{{ selectedTagCount }}</text
                        >
                        <text class="tag-count-sep">/5</text>
                    </view>
                </view>
                <view class="tag-grid" v-if="fixedTags.length > 0">
                    <view
                        v-for="tag in fixedTags"
                        :key="tag.id"
                        class="tag-chip"
                        :class="{ 'tag-chip--active': isFixedTagSelected(tag.id) }"
                        :style="
                            isFixedTagSelected(tag.id)
                                ? {
                                      color: '#fff',
                                      borderColor: $theme.primaryColor,
                                      background: $theme.primaryColor
                                  }
                                : {}
                        "
                        @click="toggleFixedTag(tag.id)"
                    >
                        <tn-icon
                            v-if="isFixedTagSelected(tag.id)"
                            name="success"
                            size="24rpx"
                            color="#fff"
                            class="tag-chip-icon"
                        />
                        <text>{{ tag.name }}</text>
                    </view>
                </view>
                <view class="tag-manual-row">
                    <input
                        v-model="tagInput"
                        class="tag-input"
                        type="text"
                        maxlength="20"
                        confirm-type="done"
                        placeholder="输入标签，如服务细致"
                        @confirm="addCustomTag"
                    />
                    <view
                        class="tag-add-btn"
                        :style="{ background: $theme.primaryColor }"
                        @click="addCustomTag"
                    >
                        添加
                    </view>
                </view>
                <view class="tag-hint">
                    <view class="tag-hint-icon" :style="{ background: $theme.primaryColor + '18' }">
                        <tn-icon name="edit-form" size="28rpx" :color="$theme.primaryColor" />
                    </view>
                    <text class="tag-hint-text">固定标签和手动标签合计最多5个</text>
                </view>
                <view class="tag-selected-bar" v-if="customTags.length > 0">
                    <view class="tag-selected-list">
                        <view
                            v-for="tag in customTags"
                            :key="tag"
                            class="tag-mini"
                            :style="{
                                background: $theme.primaryColor + '15',
                                color: $theme.primaryColor
                            }"
                            @click="removeCustomTag(tag)"
                        >
                            <text>{{ tag }}</text>
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
            <view class="publish-page__bottom-spacer"></view>

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
import { getPendingOrders, getReviewTags, publishReview } from '@/packages/common/api/review'
import { useThemeStore } from '@/stores/theme'
import {
    ensureMiniProgramReviewModeConfig,
    isMiniProgramReviewMode,
    leaveBlockedMiniProgramReviewPage,
    showMiniProgramReviewModeTip
} from '@/utils/miniProgramReviewMode'

const $theme = useThemeStore()

const orderItemId = ref(0)
const orderItem = ref<any>(null)
const submitting = ref(false)
const mediaUploading = ref(false)
const tagInput = ref('')
const fixedTags = ref<any[]>([])
const selectedTagIds = ref<number[]>([])
const customTags = ref<string[]>([])
const miniProgramReviewMode = computed(() => isMiniProgramReviewMode())

const formData = reactive({
    score_service: 5,
    score_professional: 5,
    score_punctual: 5,
    score_effect: 5,
    content: '',
    images: [] as string[],
    video: '',
    is_anonymous: 0
})

type DetailScoreKey = 'score_service' | 'score_professional' | 'score_punctual' | 'score_effect'

const overallScoreValue = computed(() => {
    const total =
        Number(formData.score_service || 0) +
        Number(formData.score_professional || 0) +
        Number(formData.score_punctual || 0) +
        Number(formData.score_effect || 0)
    return Math.round((total / 4) * 10) / 10
})

const overallScoreText = computed(() => overallScoreValue.value.toFixed(1))
const overallScoreStars = computed(() =>
    Math.max(1, Math.min(5, Math.round(overallScoreValue.value)))
)

const selectedTagCount = computed(() => selectedTagIds.value.length + customTags.value.length)
const tagScoreForQuery = computed(() => Math.max(1, Math.min(5, Math.round(overallScoreValue.value))))

const detailScores: Array<{ key: DetailScoreKey; label: string }> = [
    { key: 'score_service', label: '服务态度' },
    { key: 'score_professional', label: '专业水平' },
    { key: 'score_punctual', label: '时间守约' },
    { key: 'score_effect', label: '整体效果' }
]

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

const loadFixedTags = async () => {
    try {
        const res = await getReviewTags({ score: tagScoreForQuery.value })
        fixedTags.value = res || []
        selectedTagIds.value = selectedTagIds.value.filter((tagId) =>
            fixedTags.value.some((tag: any) => Number(tag.id) === Number(tagId))
        )
    } catch (e) {
        console.error(e)
    }
}

watch(tagScoreForQuery, () => {
    loadFixedTags()
})

const toggleFixedTag = (tagId: number) => {
    const normalizedId = Number(tagId)
    const index = selectedTagIds.value.indexOf(normalizedId)
    if (index > -1) {
        selectedTagIds.value.splice(index, 1)
        return
    }
    if (selectedTagCount.value >= 5) {
        uni.showToast({ title: '最多选择5个标签', icon: 'none' })
        return
    }
    selectedTagIds.value.push(normalizedId)
}

const isFixedTagSelected = (tagId: number | string) => selectedTagIds.value.includes(Number(tagId))

const normalizeTag = (value: string) => value.trim().replace(/\s+/g, ' ').slice(0, 20)

const addCustomTag = () => {
    const tag = normalizeTag(tagInput.value)
    if (!tag) {
        uni.showToast({ title: '请输入标签', icon: 'none' })
        return
    }
    if (customTags.value.includes(tag)) {
        uni.showToast({ title: '标签已存在', icon: 'none' })
        return
    }
    if (selectedTagCount.value >= 5) {
        uni.showToast({ title: '最多填写5个标签', icon: 'none' })
        return
    }
    customTags.value.push(tag)
    tagInput.value = ''
}

const removeCustomTag = (tag: string) => {
    customTags.value = customTags.value.filter((item) => item !== tag)
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
    if (miniProgramReviewMode.value) {
        showMiniProgramReviewModeTip('小程序送审模式已开启，暂不支持发表评价')
        return
    }

    if (overallScoreValue.value < 1) {
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
            score: overallScoreValue.value,
            score_service: formData.score_service,
            score_professional: formData.score_professional,
            score_punctual: formData.score_punctual,
            score_effect: formData.score_effect,
            content: formData.content,
            images: formData.images,
            video: formData.video,
            is_anonymous: formData.is_anonymous,
            tag_ids: selectedTagIds.value,
            custom_tags: customTags.value
        }

        await publishReview(params)

        uni.showModal({
            title: '评价成功',
            content: '已提交，感谢评价',
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

onLoad(async (options: any) => {
    const reviewModeEnabled = await ensureMiniProgramReviewModeConfig()
    if (reviewModeEnabled) {
        leaveBlockedMiniProgramReviewPage()
        return
    }

    if (options.order_item_id) {
        orderItemId.value = Number(options.order_item_id)
        loadOrderItem()
    }
    loadFixedTags()
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

.tag-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 16rpx;
    margin-bottom: 18rpx;
}

.tag-chip {
    display: flex;
    align-items: center;
    gap: 6rpx;
    padding: 14rpx 24rpx;
    background: #F8F7F2;
    border-radius: 999rpx;
    border: 2rpx solid #E7E2D6;
    font-size: 24rpx;
    color: #5F5A50;
    transition: all 0.2s ease;
}

.tag-chip--active {
    box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.1);
}

.tag-chip-icon {
    margin-right: 2rpx;
}

.tag-manual-row {
    display: flex;
    align-items: center;
    gap: 14rpx;
    margin-bottom: 16rpx;
}

.tag-input {
    flex: 1;
    min-width: 0;
    height: 76rpx;
    padding: 0 22rpx;
    background: #F8F7F2;
    border-radius: 14rpx;
    font-size: 26rpx;
    color: #111111;
    box-sizing: border-box;
}

.tag-add-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 112rpx;
    height: 76rpx;
    border-radius: 14rpx;
    font-size: 26rpx;
    font-weight: 600;
    color: #ffffff;
    flex-shrink: 0;
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

.star-touch--readonly {
    cursor: default;
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

.publish-page__bottom-spacer {
    height: 44rpx;
}

</style>
