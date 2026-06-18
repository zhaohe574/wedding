<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" tone="workspace" hasSafeBottom>
        <BaseNavbar
            title="发表评价"
            variant="solid"
            bg-color="#191713"
            text-color="#FFFDF8"
        />
        <view class="publish-page wm-page-content">
            <BaseCard
                v-if="orderItem"
                class="order-card"
                variant="list"
                padding="24rpx"
                border-radius="32rpx"
                border="1rpx solid rgba(216, 201, 173, 0.9)"
                box-shadow="0 16rpx 36rpx rgba(74, 43, 24, 0.07)"
            >
                <view class="order-card__top">
                    <view class="order-card__order">
                        <BaseIcon name="order" size="24" color="#B8954A" />
                        <text class="order-card__order-text">订单号 {{ getOrderNo(orderItem) }}</text>
                    </view>
                    <StatusBadge tone="pending" size="sm" dot>待评价</StatusBadge>
                </view>
                <view class="order-card__main">
                    <image
                        :src="getStaffAvatar(orderItem)"
                        class="staff-avatar"
                        mode="aspectFill"
                    />
                    <view class="order-card__copy">
                        <text class="order-card__title">{{ getStaffName(orderItem) }}</text>
                        <text class="order-card__meta">{{ getPackageName(orderItem) }}</text>
                        <view class="order-card__submeta">
                            <BaseIcon name="calendar" size="22" color="#9A9388" />
                            <text>{{ getServiceDate(orderItem) }}</text>
                        </view>
                    </view>
                </view>
            </BaseCard>

            <BaseCard
                class="publish-section score-section"
                variant="panel"
                padding="26rpx"
                border-radius="32rpx"
            >
                <view class="section-header">
                    <text class="section-title">服务评分</text>
                    <view class="score-badge">
                        <BaseIcon name="star-fill" size="22" color="#B8954A" />
                        <text class="score-badge-text">{{ overallScoreText }}分</text>
                    </view>
                </view>

                <view class="main-score">
                    <view class="main-score-stars">
                        <view v-for="i in 5" :key="i" class="star-touch star-touch--readonly">
                            <BaseIcon
                                :name="i <= overallScoreStars ? 'star-fill' : 'star'"
                                size="64rpx"
                                :color="i <= overallScoreStars ? '#9f7a2e' : '#E7E2D6'"
                            />
                        </view>
                    </view>
                </view>

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
                                <BaseIcon
                                    :name="i <= formData[item.key] ? 'star-fill' : 'star'"
                                    size="44rpx"
                                    :color="i <= formData[item.key] ? '#9f7a2e' : '#E7E2D6'"
                                />
                            </view>
                        </view>
                    </view>
                </view>
            </BaseCard>

            <BaseCard
                class="publish-section tag-section"
                variant="panel"
                padding="26rpx"
                border-radius="32rpx"
            >
                <view class="section-header">
                    <text class="section-title">评价标签</text>
                    <view class="tag-header-right">
                        <text class="tag-count-num">{{ selectedTagCount }}</text>
                        <text class="tag-count-sep">/5</text>
                    </view>
                </view>

                <view class="tag-grid" v-if="fixedTags.length > 0">
                    <view
                        v-for="tag in fixedTags"
                        :key="tag.id"
                        class="tag-chip"
                        :class="{ 'tag-chip--active': isFixedTagSelected(tag.id) }"
                        @click="toggleFixedTag(tag.id)"
                    >
                        <BaseIcon
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
                    <BaseButton
                        label="添加"
                        variant="dark"
                        size="sm"
                        height="72rpx"
                        font-size="24rpx"
                        @click="addCustomTag"
                    />
                </view>

                <view class="tag-selected-bar" v-if="customTags.length > 0">
                    <view class="tag-selected-list">
                        <view
                            v-for="tag in customTags"
                            :key="tag"
                            class="tag-mini"
                            @click="removeCustomTag(tag)"
                        >
                            <text>{{ tag }}</text>
                            <BaseIcon name="close" size="20rpx" color="#9A6B35" />
                        </view>
                    </view>
                </view>
            </BaseCard>

            <BaseCard
                class="publish-section content-section"
                variant="panel"
                padding="26rpx"
                border-radius="32rpx"
            >
                <view class="section-header">
                    <text class="section-title">评价内容</text>
                    <text class="content-counter">{{ formData.content.length }}/500</text>
                </view>
                <textarea
                    v-model="formData.content"
                    class="content-input"
                    placeholder="写下您的体验..."
                    maxlength="500"
                    :cursor-spacing="120"
                />
            </BaseCard>

            <BaseCard
                class="publish-section media-section"
                variant="panel"
                padding="26rpx"
                border-radius="32rpx"
            >
                <view class="section-header">
                    <text class="section-title">图片</text>
                    <text class="media-caption">{{ formData.images.length }}/9</text>
                </view>
                <view class="media-uploader">
                    <view v-for="(img, index) in formData.images" :key="index" class="media-item">
                        <image :src="img" class="media-image" mode="aspectFill" />
                        <view class="delete-btn" @click="removeImage(index)">
                            <BaseIcon name="close" size="24rpx" color="#fff"></BaseIcon>
                        </view>
                    </view>
                    <view class="add-media" @click="chooseImage" v-if="formData.images.length < 9">
                        <BaseIcon name="camera" size="56rpx" color="#D8D3C7"></BaseIcon>
                        <text class="add-media-text">添加图片</text>
                    </view>
                </view>
            </BaseCard>

            <BaseCard
                class="publish-section anonymous-section"
                variant="panel"
                padding="24rpx 26rpx"
                border-radius="32rpx"
            >
                <view class="anonymous-row">
                    <view class="anonymous-row__main">
                        <BaseIcon name="my" size="36rpx" color="#9A9388"></BaseIcon>
                        <text class="anonymous-row__text">匿名评价</text>
                    </view>
                    <switch
                        :checked="formData.is_anonymous === 1"
                        @change="handleAnonymousChange"
                        :color="$theme.primaryColor"
                    />
                </view>
            </BaseCard>
            <view class="publish-page__bottom-spacer"></view>

            <ActionArea class="publish-page__action" sticky safeBottom>
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
                    {{ submitting ? '提交中...' : mediaUploading ? '图片上传中...' : '发布评价' }}
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
import BaseCard from '@/components/base/BaseCard.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { uploadImage } from '@/api/app'
import { getPendingOrders, getReviewTags, publishReview } from '@/api/review'
import { useThemeStore } from '@/stores/theme'
import { confirmModal, showError } from '@/utils/feedback'
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
        showError('最多选择5个标签')
        return
    }
    selectedTagIds.value.push(normalizedId)
}

const isFixedTagSelected = (tagId: number | string) => selectedTagIds.value.includes(Number(tagId))

const normalizeTag = (value: string) => value.trim().replace(/\s+/g, ' ').slice(0, 20)

const addCustomTag = () => {
    const tag = normalizeTag(tagInput.value)
    if (!tag) {
        showError('请输入标签')
        return
    }
    if (customTags.value.includes(tag)) {
        showError('标签已存在')
        return
    }
    if (selectedTagCount.value >= 5) {
        showError('最多填写5个标签')
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
                    showError(
                        uploadedCount > 0
                            ? `已上传${uploadedCount}张，${failedCount}张失败`
                            : '图片上传失败，请重试'
                    )
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
        showError('请选择评分')
        return
    }

    if (mediaUploading.value) {
        showError('请等待图片上传完成')
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

        await confirmModal({
            title: '评价成功',
            content: '已提交，感谢评价',
            showCancel: false
        })
        uni.navigateBack()
    } catch (e: any) {
        showError(e, '提交失败')
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
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 22rpx;
    background: transparent;
    padding-top: 20rpx;
    padding-bottom: calc(144rpx + env(safe-area-inset-bottom));
}

.order-card,
.publish-section {
    display: block;
}

.order-card__top,
.order-card__order,
.order-card__main,
.order-card__submeta,
.section-header,
.score-badge,
.main-score-stars,
.detail-score-row,
.detail-stars,
.tag-header-right,
.tag-chip,
.tag-manual-row,
.tag-selected-list,
.tag-mini,
.media-uploader,
.anonymous-row,
.anonymous-row__main {
    display: flex;
    align-items: center;
}

.order-card__top {
    justify-content: space-between;
    gap: 18rpx;
}

.order-card__order {
    min-width: 0;
    flex: 1;
    gap: 8rpx;
}

.order-card__order-text {
    min-width: 0;
    flex: 1;
    font-size: 22rpx;
    line-height: 1.4;
    color: var(--wm-text-tertiary, #9a9388);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.order-card__main {
    gap: 18rpx;
    margin-top: 22rpx;
}

.order-card__copy {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.order-card__title {
    max-width: 100%;
    font-size: 30rpx;
    font-weight: 900;
    line-height: 1.32;
    color: var(--wm-text-primary, #191713);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.order-card__meta {
    font-size: 24rpx;
    line-height: 1.35;
    color: var(--wm-text-secondary, #5f5a50);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.order-card__submeta {
    gap: 8rpx;
    font-size: 22rpx;
    line-height: 1.4;
    color: var(--wm-text-tertiary, #9a9388);
}

.order-card__submeta text {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.staff-avatar {
    width: 96rpx;
    height: 96rpx;
    flex-shrink: 0;
    border-radius: 999rpx;
    background: var(--wm-color-bg-soft, #faf6ee);
    border: 4rpx solid rgba(255, 253, 248, 0.96);
    box-shadow: 0 10rpx 22rpx rgba(74, 43, 24, 0.1);
}

.section-header {
    justify-content: space-between;
    gap: 18rpx;
    margin-bottom: 22rpx;
}

.section-title {
    min-width: 0;
    flex: 1;
    font-size: 30rpx;
    font-weight: 900;
    line-height: 1.32;
    color: var(--wm-text-primary, #191713);
}

.score-badge,
.tag-header-right {
    flex-shrink: 0;
    justify-content: center;
    gap: 6rpx;
    min-height: 48rpx;
    padding: 0 18rpx;
    border-radius: 999rpx;
    background: rgba(241, 229, 200, 0.72);
    border: 1rpx solid rgba(216, 201, 173, 0.88);
}

.score-badge-text,
.tag-count-num {
    font-size: 24rpx;
    font-weight: 900;
    color: var(--wm-color-gold, #b8954a);
}

.tag-count-sep {
    font-size: 24rpx;
    color: var(--wm-text-tertiary, #9a9388);
}

.tag-grid {
    flex-wrap: wrap;
    margin: 0 -7rpx 8rpx;
}

.tag-chip {
    min-height: 60rpx;
    gap: 8rpx;
    margin: 0 7rpx 14rpx;
    padding: 0 22rpx;
    background: var(--wm-color-bg-soft, #faf6ee);
    border-radius: 999rpx;
    border: 1rpx solid rgba(216, 201, 173, 0.88);
    box-sizing: border-box;
    font-size: 24rpx;
    font-weight: 800;
    color: var(--wm-text-secondary, #5f5a50);
    transition: all 0.2s ease;
}

.tag-chip--active {
    color: var(--wm-text-inverse, #fffdf8);
    border-color: var(--wm-color-primary, #191713);
    background: var(--wm-color-primary, #191713);
    box-shadow: 0 12rpx 28rpx rgba(74, 43, 24, 0.14);
}

.tag-chip-icon {
    margin-right: 2rpx;
}

.tag-manual-row {
    gap: 12rpx;
    margin-top: 6rpx;
}

.tag-input {
    flex: 1;
    min-width: 0;
    height: 72rpx;
    padding: 0 22rpx;
    background: var(--wm-color-bg-soft, #faf6ee);
    border: 1rpx solid rgba(216, 201, 173, 0.88);
    border-radius: 22rpx;
    font-size: 26rpx;
    color: var(--wm-text-primary, #191713);
    box-sizing: border-box;
}

.tag-selected-bar {
    margin-top: 18rpx;
    padding-top: 18rpx;
    border-top: 1rpx solid rgba(216, 201, 173, 0.64);
}

.tag-selected-list {
    flex-wrap: wrap;
    margin: 0 -6rpx -12rpx;
}

.tag-mini {
    gap: 6rpx;
    min-height: 48rpx;
    margin: 0 6rpx 12rpx;
    padding: 0 16rpx;
    border-radius: 999rpx;
    font-size: 22rpx;
    font-weight: 800;
    color: var(--wm-color-clay, #9a6b35);
    background: rgba(241, 229, 200, 0.72);
    border: 1rpx solid rgba(216, 201, 173, 0.88);
}

.main-score {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 6rpx 0 22rpx;
}

.main-score-stars {
    justify-content: center;
    gap: 8rpx;
}

.star-touch {
    padding: 2rpx;
    cursor: pointer;
}

.star-touch--readonly {
    cursor: default;
}

.star-touch-sm {
    padding: 4rpx;
    cursor: pointer;
}

.detail-scores {
    display: flex;
    flex-direction: column;
    padding: 6rpx 4rpx 0;

    .detail-score-row {
        justify-content: space-between;
        gap: 18rpx;
        min-height: 72rpx;
        border-top: 1rpx solid rgba(216, 201, 173, 0.58);

        .detail-label {
            font-size: 26rpx;
            font-weight: 800;
            color: var(--wm-text-secondary, #5f5a50);
            flex-shrink: 0;
        }

        .detail-stars {
            gap: 4rpx;
            justify-content: flex-end;
        }
    }
}

.content-input {
    width: 100%;
    height: 220rpx;
    padding: 22rpx;
    background: var(--wm-color-bg-soft, #faf6ee);
    border: 1rpx solid rgba(216, 201, 173, 0.88);
    border-radius: 24rpx;
    box-sizing: border-box;
    font-size: 28rpx;
    line-height: 1.6;
    color: var(--wm-text-primary, #191713);
}

.content-counter,
.media-caption {
    font-size: 22rpx;
    font-weight: 800;
    color: var(--wm-text-tertiary, #9a9388);
}

.media-uploader {
    flex-wrap: wrap;
    gap: 14rpx;
}

.media-item {
    position: relative;
    width: 154rpx;
    height: 154rpx;
    border-radius: 22rpx;
    overflow: visible;
}

.media-image {
    width: 100%;
    height: 100%;
    border-radius: 22rpx;
    background: var(--wm-color-bg-soft, #faf6ee);
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
    width: 154rpx;
    height: 154rpx;
    border: 2rpx dashed rgba(216, 201, 173, 0.98);
    border-radius: 22rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    background: var(--wm-color-bg-soft, #faf6ee);
}

.add-media-text {
    font-size: 22rpx;
    color: #9A9388;
}

.anonymous-row {
    justify-content: space-between;
    gap: 20rpx;
}

.anonymous-row__main {
    gap: 12rpx;
}

.anonymous-row__text {
    font-size: 26rpx;
    color: var(--wm-text-secondary, #5f5a50);
}

.publish-page__bottom-spacer {
    height: 10rpx;
}

.publish-page__action {
    --wm-space-action-top: 14rpx;
    --wm-space-action-x: 24rpx;
    --wm-space-action-bottom: 18rpx;
}

@media screen and (max-width: 360px) {
    .order-card__top,
    .section-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .order-card__order {
        width: 100%;
        flex: none;
    }

    .main-score-stars {
        gap: 2rpx;
    }

    .media-item,
    .add-media {
        width: 142rpx;
        height: 142rpx;
    }
}
</style>
