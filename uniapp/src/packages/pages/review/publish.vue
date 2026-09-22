<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" tone="workspace" hasSafeBottom>
        <BaseNavbar
            title="发表评价"
            variant="solid"
            bg-color="#181614"
            text-color="#FFFDF8"
        />

        <view class="publish-page">
            <view class="publish-page__body wm-page-content">
                <!-- 1. 服务人员与订单卡片 -->
                <view v-if="orderItem" class="order-hero-card">
                    <view class="order-hero-card__top">
                        <view class="order-hero-card__order-box">
                            <BaseIcon name="order" size="20" color="#C6A15B" />
                            <text class="order-hero-card__order-no">订单号 {{ getOrderNo(orderItem) }}</text>
                        </view>
                        <StatusBadge tone="pending" size="sm" dot>待评价</StatusBadge>
                    </view>

                    <view class="order-hero-card__main">
                        <view class="order-hero-card__avatar-ring">
                            <image
                                :src="getStaffAvatar(orderItem)"
                                class="order-hero-card__avatar"
                                mode="aspectFill"
                            />
                        </view>
                        <view class="order-hero-card__info">
                            <text class="order-hero-card__name">{{ getStaffName(orderItem) }}</text>
                            <text class="order-hero-card__pkg">{{ getPackageName(orderItem) }}</text>
                            <view class="order-hero-card__date">
                                <BaseIcon name="calendar" size="18" color="#C6A15B" />
                                <text class="order-hero-card__date-text">{{ getServiceDate(orderItem) }}</text>
                            </view>
                        </view>
                    </view>
                </view>

                <!-- 2. 服务评分卡片 -->
                <view class="form-card">
                    <view class="form-card__head">
                        <text class="form-card__title">服务评分</text>
                        <view class="overall-badge">
                            <BaseIcon name="star-fill" size="22" color="#C6A15B" />
                            <text class="overall-badge__score">{{ overallScoreText }} 分</text>
                        </view>
                    </view>

                    <!-- 主星级展示 -->
                    <view class="main-stars">
                        <view v-for="i in 5" :key="i" class="main-stars__item">
                            <BaseIcon
                                :name="i <= overallScoreStars ? 'star-fill' : 'star'"
                                size="56"
                                :color="i <= overallScoreStars ? '#C6A15B' : '#E7E0D3'"
                            />
                        </view>
                    </view>

                    <!-- 4 维打分行 -->
                    <view class="dimension-scores">
                        <view
                            v-for="item in detailScores"
                            :key="item.key"
                            class="dimension-row"
                        >
                            <text class="dimension-row__label">{{ item.label }}</text>
                            <view class="dimension-row__stars">
                                <view
                                    v-for="i in 5"
                                    :key="i"
                                    class="dimension-star"
                                    @click="formData[item.key] = i"
                                >
                                    <BaseIcon
                                        :name="i <= formData[item.key] ? 'star-fill' : 'star'"
                                        size="38"
                                        :color="i <= formData[item.key] ? '#C6A15B' : '#E7E0D3'"
                                    />
                                </view>
                            </view>
                        </view>
                    </view>
                </view>

                <!-- 3. 评价标签卡片 -->
                <view class="form-card">
                    <view class="form-card__head">
                        <text class="form-card__title">评价标签</text>
                        <text class="tag-counter">{{ selectedTagCount }}/5</text>
                    </view>

                    <view v-if="fixedTags.length > 0" class="tags-grid">
                        <view
                            v-for="tag in fixedTags"
                            :key="tag.id"
                            class="tag-pill"
                            :class="{ 'tag-pill--active': isFixedTagSelected(tag.id) }"
                            @click="toggleFixedTag(tag.id)"
                        >
                            <BaseIcon
                                v-if="isFixedTagSelected(tag.id)"
                                name="check"
                                size="20"
                                color="#FFFDF8"
                            />
                            <text class="tag-pill__text">{{ tag.name }}</text>
                        </view>
                    </view>

                    <view class="custom-tag-row">
                        <input
                            v-model="tagInput"
                            class="custom-tag-input"
                            type="text"
                            maxlength="20"
                            confirm-type="done"
                            placeholder="自定义标签（如：拍摄超敬业）"
                            @confirm="addCustomTag"
                        />
                        <BaseButton
                            label="添加"
                            variant="dark"
                            size="sm"
                            height="68rpx"
                            font-size="23rpx"
                            @click="addCustomTag"
                        />
                    </view>

                    <view v-if="customTags.length > 0" class="custom-tags-box">
                        <view
                            v-for="tag in customTags"
                            :key="tag"
                            class="custom-tag-chip"
                            @click="removeCustomTag(tag)"
                        >
                            <text class="custom-tag-chip__text">{{ tag }}</text>
                            <BaseIcon name="close" size="18" color="#9A6B35" />
                        </view>
                    </view>
                </view>

                <!-- 4. 评价文字与晒图卡片 -->
                <view class="form-card">
                    <view class="form-card__head">
                        <text class="form-card__title">评价体验</text>
                        <text class="char-counter">{{ formData.content.length }}/500</text>
                    </view>

                    <textarea
                        v-model="formData.content"
                        class="comment-textarea"
                        placeholder="记录婚礼当天的难忘体验，分享给更多备婚新人..."
                        maxlength="500"
                        :cursor-spacing="120"
                    />

                    <!-- 图片上传网格 -->
                    <view class="image-uploader-section">
                        <view class="image-uploader-head">
                            <text class="image-uploader-head__title">现场晒图</text>
                            <text class="image-uploader-head__count">{{ formData.images.length }}/9</text>
                        </view>

                        <view class="image-grid">
                            <view
                                v-for="(img, index) in formData.images"
                                :key="index"
                                class="image-cell"
                            >
                                <image :src="img" class="image-cell__img" mode="aspectFill" />
                                <view class="image-cell__remove" @click="removeImage(index)">
                                    <BaseIcon name="close" size="24" color="#FFFDF8" />
                                </view>
                            </view>

                            <view
                                v-if="formData.images.length < 9"
                                class="image-cell-add"
                                @click="chooseImage"
                            >
                                <BaseIcon name="camera" size="48" color="#C6A15B" />
                                <text class="image-cell-add__text">添加照片</text>
                            </view>
                        </view>
                    </view>
                </view>

                <!-- 5. 匿名评价开关卡片 -->
                <view class="form-card form-card--compact">
                    <view class="anonymous-row">
                        <view class="anonymous-row__left">
                            <BaseIcon name="my" size="32" color="#8C8273" />
                            <view class="anonymous-row__copy">
                                <text class="anonymous-row__title">匿名评价</text>
                                <text class="anonymous-row__desc">开启后评价将隐藏您的个人昵称与头像</text>
                            </view>
                        </view>
                        <switch
                            :checked="formData.is_anonymous === 1"
                            color="#181614"
                            @change="handleAnonymousChange"
                        />
                    </view>
                </view>
            </view>

            <!-- 底部悬浮操作栏 -->
            <ActionArea sticky safeBottom class="publish-page__action-area">
                <BaseButton
                    block
                    size="md"
                    height="88rpx"
                    font-size="26rpx"
                    variant="primary"
                    :disabled="submitting || mediaUploading"
                    :loading="submitting"
                    @click="handleSubmit"
                >
                    {{ submitting ? '提交评价中...' : mediaUploading ? '图片上传中...' : '提交真实评价' }}
                </BaseButton>
            </ActionArea>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import ActionArea from '@/components/base/ActionArea.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { uploadImage } from '@/api/app'
import { getPendingOrders, getReviewTags, publishReview } from '@/packages/common/api/review'
import { useThemeStore } from '@/stores/theme'
import { confirmModal, showError, showSuccess } from '@/utils/feedback'
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

const defaultAvatar = '/static/images/user/default_avatar.png'

const getStaffAvatar = (item: any) => {
    return item?.staff?.avatar || item?.staff_avatar || defaultAvatar
}

const getStaffName = (item: any) => {
    return item?.staff_name || item?.staff?.name || '服务人员'
}

const getPackageName = (item: any) => {
    return item?.package_name || item?.order_item?.package_name || item?.orderItem?.package_name || '服务项目'
}

const getOrderNo = (item: any) => {
    return item?.order?.order_sn || item?.order_sn || '--'
}

const getServiceDate = (item: any) => {
    return item?.order?.service_date || item?.service_date || '服务日期待确认'
}

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

const isFixedTagSelected = (id: number) => {
    return selectedTagIds.value.includes(id)
}

const toggleFixedTag = (id: number) => {
    if (isFixedTagSelected(id)) {
        selectedTagIds.value = selectedTagIds.value.filter((tagId) => tagId !== id)
        return
    }

    if (selectedTagCount.value >= 5) {
        showError('最多选择5个标签')
        return
    }

    selectedTagIds.value.push(id)
}

const addCustomTag = () => {
    const text = tagInput.value.trim()
    if (!text) return

    if (selectedTagCount.value >= 5) {
        showError('最多添加5个标签')
        return
    }

    if (customTags.value.includes(text)) {
        showError('该标签已存在')
        return
    }

    if (fixedTags.value.some((tag: any) => tag.name === text)) {
        const tag = fixedTags.value.find((tag: any) => tag.name === text)
        if (tag && !selectedTagIds.value.includes(tag.id)) {
            selectedTagIds.value.push(tag.id)
            tagInput.value = ''
            return
        }
    }

    customTags.value.push(text)
    tagInput.value = ''
}

const removeCustomTag = (text: string) => {
    customTags.value = customTags.value.filter((tag) => tag !== text)
}

const chooseImage = () => {
    if (mediaUploading.value) return
    const remainCount = 9 - formData.images.length
    if (remainCount <= 0) return

    uni.chooseImage({
        count: remainCount,
        sizeType: ['compressed'],
        sourceType: ['album', 'camera'],
        success: async (res) => {
            const tempFilePaths = res.tempFilePaths || []
            if (!tempFilePaths.length) return

            mediaUploading.value = true
            try {
                for (const filePath of tempFilePaths) {
                    if (formData.images.length >= 9) break
                    const uploadRes: any = await uploadImage(filePath)
                    if (uploadRes?.uri) {
                        formData.images.push(uploadRes.uri)
                    }
                }
            } catch (e) {
                showError('部分图片上传失败，请重试')
            } finally {
                mediaUploading.value = false
            }
        }
    })
}

const removeImage = (index: number) => {
    formData.images.splice(index, 1)
}

const handleAnonymousChange = (e: any) => {
    formData.is_anonymous = e.detail.value ? 1 : 0
}

const handleSubmit = async () => {
    if (miniProgramReviewMode.value) {
        showMiniProgramReviewModeTip('评价功能维护中，暂时无法发表评价')
        return
    }

    if (!formData.content.trim()) {
        showError('请填写评价内容')
        return
    }

    submitting.value = true
    try {
        const selectedFixedTagNames = fixedTags.value
            .filter((tag: any) => selectedTagIds.value.includes(tag.id))
            .map((tag: any) => tag.name)
        const allTags = [...selectedFixedTagNames, ...customTags.value]

        await publishReview({
            order_item_id: orderItemId.value,
            score_service: formData.score_service,
            score_professional: formData.score_professional,
            score_punctual: formData.score_punctual,
            score_effect: formData.score_effect,
            content: formData.content,
            images: formData.images,
            video: formData.video,
            is_anonymous: formData.is_anonymous,
            tags: allTags
        })

        showSuccess('评价发表成功')
        setTimeout(() => {
            uni.navigateBack()
        }, 1200)
    } catch (e: any) {
        showError(e?.message || '提交评价失败，请重试')
    } finally {
        submitting.value = false
    }
}

watch(tagScoreForQuery, () => {
    loadFixedTags()
})

onLoad((options: any) => {
    orderItemId.value = Number(options?.order_item_id || 0)
    ensureMiniProgramReviewModeConfig()
    if (miniProgramReviewMode.value) {
        leaveBlockedMiniProgramReviewPage()
        return
    }
    loadOrderItem()
    loadFixedTags()
})
</script>

<style lang="scss" scoped>
.publish-page {
    min-height: 100vh;
    background:
        radial-gradient(ellipse at 50% 0%, rgba(217, 190, 130, 0.1) 0%, rgba(248, 246, 240, 0) 65%),
        var(--wm-color-bg-page, #F8F6F0);
    box-sizing: border-box;
    padding-bottom: calc(130rpx + env(safe-area-inset-bottom));

    &__body {
        padding: 24rpx var(--wm-space-page-x, 28rpx) 40rpx;
        display: flex;
        flex-direction: column;
        gap: 24rpx;
        box-sizing: border-box;
    }
}

/* 订单卡片 */
.order-hero-card {
    padding: 28rpx;
    border-radius: 32rpx;
    background: linear-gradient(180deg, #FFFFFF 0%, #FAF8F5 100%);
    border: 1rpx solid rgba(217, 190, 130, 0.4);
    box-shadow: 0 12rpx 32rpx rgba(24, 22, 20, 0.05);
    display: flex;
    flex-direction: column;
    gap: 18rpx;

    &__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
    }

    &__order-box {
        display: inline-flex;
        align-items: center;
        gap: 8rpx;
        padding: 4rpx 14rpx;
        border-radius: 999rpx;
        background: rgba(242, 236, 225, 0.6);
        border: 1rpx solid rgba(217, 190, 130, 0.3);
    }

    &__order-no {
        font-size: 20rpx;
        font-family: monospace;
        color: var(--wm-color-text-secondary, #5E564B);
    }

    &__main {
        display: flex;
        align-items: center;
        gap: 18rpx;
    }

    &__avatar-ring {
        width: 88rpx;
        height: 88rpx;
        border-radius: 50%;
        padding: 3rpx;
        box-sizing: border-box;
        background: linear-gradient(135deg, #D9BE82 0%, #FAF6EE 100%);
        box-shadow: 0 6rpx 14rpx rgba(24, 22, 20, 0.08);
        flex-shrink: 0;
    }

    &__avatar {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: #FFFFFF;
        display: block;
    }

    &__info {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 6rpx;
    }

    &__name {
        font-size: 28rpx;
        font-weight: 800;
        color: var(--wm-color-primary, #181614);
    }

    &__pkg {
        font-size: 23rpx;
        color: var(--wm-color-text-secondary, #5E564B);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__date {
        display: inline-flex;
        align-items: center;
        gap: 6rpx;
    }

    &__date-text {
        font-size: 20rpx;
        color: var(--wm-color-text-tertiary, #8C8273);
    }
}

/* 表单卡片通用 */
.form-card {
    padding: 30rpx 28rpx;
    border-radius: 32rpx;
    background: linear-gradient(180deg, #FFFFFF 0%, #FAF8F5 100%);
    border: 1rpx solid rgba(217, 190, 130, 0.4);
    box-shadow: 0 12rpx 32rpx rgba(24, 22, 20, 0.05);
    display: flex;
    flex-direction: column;
    gap: 20rpx;
    box-sizing: border-box;

    &--compact {
        padding: 24rpx 28rpx;
    }

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
    }

    &__title {
        font-size: 28rpx;
        font-weight: 800;
        color: var(--wm-color-primary, #181614);
    }
}

/* 总体评分 */
.overall-badge {
    display: inline-flex;
    align-items: center;
    gap: 6rpx;
    padding: 6rpx 16rpx;
    border-radius: 999rpx;
    background: rgba(217, 190, 130, 0.2);
    border: 1rpx solid rgba(217, 190, 130, 0.4);

    &__score {
        font-size: 23rpx;
        font-weight: 800;
        color: var(--wm-color-clay, #9A6B35);
    }
}

.main-stars {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 24rpx;
    padding: 10rpx 0;
}

.dimension-scores {
    display: flex;
    flex-direction: column;
    gap: 14rpx;
    padding-top: 18rpx;
    border-top: 1rpx solid rgba(231, 224, 211, 0.7);
}

.dimension-row {
    display: flex;
    align-items: center;
    justify-content: space-between;

    &__label {
        font-size: 24rpx;
        font-weight: 600;
        color: var(--wm-color-text-secondary, #5E564B);
    }

    &__stars {
        display: flex;
        align-items: center;
        gap: 14rpx;
    }
}

.dimension-star {
    padding: 6rpx;
}

/* 标签区 */
.tag-counter,
.char-counter {
    font-size: 21rpx;
    color: var(--wm-color-text-tertiary, #8C8273);
}

.tags-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
}

.tag-pill {
    display: inline-flex;
    align-items: center;
    gap: 6rpx;
    padding: 10rpx 22rpx;
    border-radius: 999rpx;
    background: rgba(250, 246, 238, 0.9);
    border: 1rpx solid rgba(217, 190, 130, 0.4);
    box-sizing: border-box;
    transition: all 0.2s ease;

    &__text {
        font-size: 23rpx;
        font-weight: 600;
        color: var(--wm-color-text-secondary, #5E564B);
    }

    &--active {
        background: var(--wm-color-primary, #181614);
        border-color: var(--wm-color-primary, #181614);
        box-shadow: 0 4rpx 14rpx rgba(24, 22, 20, 0.15);

        .tag-pill__text {
            color: #FFFDF8;
        }
    }
}

.custom-tag-row {
    display: flex;
    align-items: center;
    gap: 14rpx;
    margin-top: 4rpx;
}

.custom-tag-input {
    flex: 1;
    height: 68rpx;
    padding: 0 20rpx;
    border-radius: 20rpx;
    background: rgba(250, 246, 238, 0.85);
    border: 1rpx solid rgba(217, 190, 130, 0.35);
    font-size: 24rpx;
    color: var(--wm-color-primary, #181614);
}

.custom-tags-box {
    display: flex;
    flex-wrap: wrap;
    gap: 10rpx;
}

.custom-tag-chip {
    display: inline-flex;
    align-items: center;
    gap: 6rpx;
    padding: 6rpx 16rpx;
    border-radius: 999rpx;
    background: rgba(217, 190, 130, 0.2);
    border: 1rpx solid rgba(217, 190, 130, 0.45);

    &__text {
        font-size: 21rpx;
        font-weight: 600;
        color: var(--wm-color-clay, #9A6B35);
    }
}

/* 文本评价 */
.comment-textarea {
    width: 100%;
    min-height: 200rpx;
    padding: 22rpx;
    border-radius: 22rpx;
    background: rgba(250, 246, 238, 0.85);
    border: 1rpx solid rgba(217, 190, 130, 0.35);
    box-sizing: border-box;
    font-size: 26rpx;
    line-height: 1.6;
    color: var(--wm-color-primary, #181614);
}

/* 图片上传网格 */
.image-uploader-section {
    display: flex;
    flex-direction: column;
    gap: 14rpx;
    padding-top: 10rpx;
    border-top: 1rpx solid rgba(231, 224, 211, 0.7);
}

.image-uploader-head {
    display: flex;
    align-items: center;
    justify-content: space-between;

    &__title {
        font-size: 24rpx;
        font-weight: 700;
        color: var(--wm-color-primary, #181614);
    }

    &__count {
        font-size: 21rpx;
        color: var(--wm-color-text-tertiary, #8C8273);
    }
}

.image-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14rpx;
}

.image-cell {
    position: relative;
    width: 100%;
    height: 190rpx;
    border-radius: 20rpx;
    overflow: hidden;
    border: 1rpx solid rgba(217, 190, 130, 0.35);

    &__img {
        width: 100%;
        height: 100%;
        display: block;
    }

    &__remove {
        position: absolute;
        top: 10rpx;
        right: 10rpx;
        width: 44rpx;
        height: 44rpx;
        border-radius: 50%;
        background: rgba(24, 22, 20, 0.65);
        display: flex;
        align-items: center;
        justify-content: center;
    }
}

.image-cell-add {
    width: 100%;
    height: 190rpx;
    border-radius: 20rpx;
    border: 2rpx dashed rgba(217, 190, 130, 0.6);
    background: rgba(250, 246, 238, 0.75);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8rpx;

    &__text {
        font-size: 21rpx;
        font-weight: 600;
        color: var(--wm-color-text-secondary, #5E564B);
    }
}

/* 匿名行 */
.anonymous-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;

    &__left {
        display: flex;
        align-items: center;
        gap: 14rpx;
        flex: 1;
        min-width: 0;
    }

    &__copy {
        display: flex;
        flex-direction: column;
        gap: 2rpx;
    }

    &__title {
        font-size: 26rpx;
        font-weight: 700;
        color: var(--wm-color-primary, #181614);
    }

    &__desc {
        font-size: 20rpx;
        color: var(--wm-color-text-tertiary, #8C8273);
    }
}
</style>
