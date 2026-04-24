<template>
    <page-meta :page-style="pageStyle" />
    <PageShell scene="consumer">
        <BaseNavbar title="作品详情" />

        <view class="work-detail wm-page-content" v-if="workDetail">
            <BaseCard variant="hero" scene="consumer" padding="0">
                <view class="work-hero">
                    <image
                        class="work-hero__image"
                        :src="
                            workDetail.cover ||
                            workDetail.images?.[0] ||
                            '/static/images/default_cover.png'
                        "
                        mode="aspectFill"
                        @click="previewCover"
                    />

                    <view class="work-hero__overlay">
                        <view class="work-hero__badge-row">
                            <StatusBadge tone="neutral" size="sm">
                                <view class="work-hero__badge-content">
                                    <tn-icon name="eye" size="24" color="#C8A45D" />
                                    <text>浏览 {{ workDetail.view_count || 0 }}</text>
                                </view>
                            </StatusBadge>

                            <StatusBadge tone="info" size="sm">
                                {{ workDetail.type_desc || '作品' }}
                            </StatusBadge>
                        </view>

                        <view class="work-hero__copy">
                            <text class="work-hero__eyebrow">婚礼作品集</text>
                            <text class="work-hero__title">
                                {{ workDetail.title || '未命名作品' }}
                            </text>
                            <text v-if="workDetail.description" class="work-hero__desc">
                                {{ workDetail.description }}
                            </text>
                        </view>

                        <view class="work-hero__meta-list">
                            <view v-if="workDetail.shoot_date" class="work-hero__meta-pill">
                                <tn-icon name="calendar" size="24" color="#5F5A50" />
                                <text class="work-hero__meta-text">{{ workDetail.shoot_date }}</text>
                            </view>

                            <view v-if="workDetail.location" class="work-hero__meta-pill">
                                <tn-icon name="map-pin" size="24" color="#5F5A50" />
                                <text class="work-hero__meta-text">{{ workDetail.location }}</text>
                            </view>
                        </view>
                    </view>
                </view>
            </BaseCard>

            <BaseCard
                v-if="workDetail.description"
                variant="surface"
                scene="consumer"
                class="detail-card"
            >
                <view class="detail-card__head">
                    <text class="detail-card__eyebrow">作品说明</text>
                    <text class="detail-card__title">本组作品亮点</text>
                </view>

                <text class="detail-card__body-text">{{ workDetail.description }}</text>
            </BaseCard>

            <BaseCard
                v-if="workDetail.staff"
                variant="glass"
                scene="consumer"
                class="staff-summary-card"
                interactive
                @click="goToStaffDetail"
            >
                <view class="staff-summary-card__head">
                    <view class="staff-summary-card__identity">
                        <image
                            class="staff-summary-card__avatar"
                            :src="
                                workDetail.staff.avatar || '/static/images/user/default_avatar.png'
                            "
                            mode="aspectFill"
                        />

                        <view class="staff-summary-card__copy">
                            <view class="staff-summary-card__title-row">
                                <text class="staff-summary-card__name">
                                    {{ workDetail.staff.name || '-' }}
                                </text>
                                <StatusBadge tone="info" size="sm">
                                    {{ workDetail.staff.category_name || '未分类' }}
                                </StatusBadge>
                            </view>

                            <text class="staff-summary-card__meta">
                                工号：{{ workDetail.staff.sn || '-' }}
                            </text>

                            <view class="staff-summary-card__badge-row">
                                <StatusBadge tone="neutral" size="sm">
                                    评分 {{ workDetail.staff.rating || '5.0' }}
                                </StatusBadge>
                                <StatusBadge tone="success" size="sm">
                                    服务 {{ workDetail.staff.order_count || 0 }} 场
                                </StatusBadge>
                                <StatusBadge tone="warning" size="sm">
                                    评价 {{ workDetail.staff.review_count || 0 }}
                                </StatusBadge>
                                <StatusBadge tone="neutral" size="sm">
                                    收藏 {{ workDetail.staff.favorite_count || 0 }}
                                </StatusBadge>
                            </view>
                        </view>
                    </view>

                    <tn-icon name="arrow-right" size="32" color="#9A9388" />
                </view>

                <view class="staff-summary-card__price-row">
                    <text class="staff-summary-card__price-label">服务价格</text>
                    <view class="staff-summary-card__price">
                        <template
                            v-if="
                                workDetail.staff.has_price !== false &&
                                workDetail.staff.price !== null &&
                                workDetail.staff.price !== undefined
                            "
                        >
                            <text class="staff-summary-card__price-symbol">¥</text>
                            <text class="staff-summary-card__price-value">
                                {{ workDetail.staff.price_text || workDetail.staff.price }}
                            </text>
                            <text class="staff-summary-card__price-unit">/次起</text>
                        </template>
                        <text v-else class="staff-summary-card__price-negotiable">面议</text>
                    </view>
                </view>
            </BaseCard>

            <BaseCard
                v-if="workDetail.images?.length"
                variant="surface"
                scene="consumer"
                class="detail-card"
            >
                <view class="detail-card__head">
                    <text class="detail-card__eyebrow">作品图片</text>
                    <text class="detail-card__title">
                        共 {{ workDetail.images.length }} 张精选画面
                    </text>
                </view>

                <view class="images-grid" :class="getGridClass">
                    <image
                        v-for="(img, index) in displayImages"
                        :key="index"
                        :src="img"
                        class="image-item"
                        mode="aspectFill"
                        @click="previewImages(index)"
                    />
                    <view
                        v-if="workDetail.images.length > 9"
                        class="image-more"
                        @click="previewImages(8)"
                    >
                        <text class="more-text">+{{ workDetail.images.length - 9 }}</text>
                    </view>
                </view>
            </BaseCard>

            <BaseCard
                v-if="workDetail.video"
                variant="surface"
                scene="consumer"
                class="detail-card"
            >
                <view class="detail-card__head">
                    <text class="detail-card__eyebrow">作品视频</text>
                    <text class="detail-card__title">完整动态记录</text>
                </view>
                <video
                    :src="workDetail.video"
                    class="video-player"
                    object-fit="cover"
                    :controls="true"
                    :show-center-play-btn="true"
                />
            </BaseCard>

            <BaseCard variant="surface" scene="consumer" class="detail-card detail-card--meta">
                <view class="detail-card__head">
                    <text class="detail-card__eyebrow">时间信息</text>
                    <text class="detail-card__title">作品更新记录</text>
                </view>

                <view class="detail-card__meta-list">
                    <view class="detail-card__meta-row">
                        <text class="detail-card__meta-label">创建时间</text>
                        <text class="detail-card__meta-value">
                            {{ workDetail.create_time || '-' }}
                        </text>
                    </view>
                    <view class="detail-card__meta-row">
                        <text class="detail-card__meta-label">更新时间</text>
                        <text class="detail-card__meta-value">
                            {{ workDetail.update_time || '-' }}
                        </text>
                    </view>
                </view>
            </BaseCard>
        </view>

        <view v-else class="loading-container">
            <tn-loading mode="circle" />
        </view>
    </PageShell>
</template>

<script lang="ts" setup>
import { ref, computed } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import BaseCard from '@/components/base/BaseCard.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { getWorkDetail } from '@/api/staff'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()
const workDetail = ref<any>(null)

const pageStyle = computed(() => $theme.pageStyle)

// 显示的图片（最多9张）
const displayImages = computed(() => {
    if (!workDetail.value?.images?.length) return []
    return workDetail.value.images.slice(0, 9)
})

// 网格类名
const getGridClass = computed(() => {
    const count = displayImages.value.length
    if (count === 1) return 'grid-single'
    if (count === 2) return 'grid-double'
    if (count === 4) return 'grid-four'
    return 'grid-nine'
})

const loadDetail = async (id: number) => {
    try {
        const data = await getWorkDetail({ id })
        workDetail.value = data
    } catch (e: any) {
        const errorMsg = typeof e === 'string' ? e : e?.msg || e?.message || '加载失败'
        uni.showToast({ title: errorMsg, icon: 'none' })
        setTimeout(() => {
            uni.navigateBack()
        }, 1500)
    }
}

const previewCover = () => {
    const cover = workDetail.value?.cover || workDetail.value?.images?.[0]
    if (!cover) return
    uni.previewImage({
        urls: [cover],
        current: cover
    })
}

const previewImages = (index: number) => {
    const urls = workDetail.value?.images || []
    if (!urls.length) return
    uni.previewImage({
        urls,
        current: urls[index] || urls[0]
    })
}

const goToStaffDetail = () => {
    const staffId = workDetail.value?.staff?.id
    if (!staffId) return
    uni.navigateTo({
        url: `/packages/pages/staff_detail/staff_detail?id=${staffId}`
    })
}

onLoad((options: any) => {
    const id = Number(options?.id || 0)
    if (!id) {
        uni.showToast({ title: '作品信息错误', icon: 'none' })
        setTimeout(() => {
            uni.navigateBack()
        }, 1500)
        return
    }
    loadDetail(id)
})
</script>

<style lang="scss" scoped>
.work-detail {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    gap: 24rpx;
    padding-bottom: 24rpx;
}

.loading-container {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
}

.work-hero {
    position: relative;
    width: 100%;
    height: 640rpx;
    min-height: 640rpx;
    overflow: hidden;
    border-radius: inherit;
}

.work-hero__image {
    width: 100%;
    height: 640rpx;
    display: block;
}

.work-hero__overlay {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    gap: 20rpx;
    padding: 36rpx 32rpx;
    background: linear-gradient(180deg, rgba(11, 11, 11, 0.08) 0%, rgba(11, 11, 11, 0.62) 100%);
}

.work-hero__badge-row {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
}

.work-hero__badge-content {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
}

.work-hero__copy {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
}

.work-hero__eyebrow {
    font-size: 22rpx;
    font-weight: 600;
    letter-spacing: 0;
    color: rgba(255, 255, 255, 0.82);
}

.work-hero__title {
    font-size: 40rpx;
    font-weight: 700;
    line-height: 1.3;
    color: #fff;
}

.work-hero__desc {
    font-size: 24rpx;
    line-height: 1.7;
    color: rgba(255, 255, 255, 0.9);
}

.work-hero__meta-list {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
}

.work-hero__meta-pill {
    display: flex;
    align-items: center;
    gap: 8rpx;
    padding: 12rpx 18rpx;
    background: rgba(255, 255, 255, 0.14);
    border: 1rpx solid rgba(255, 255, 255, 0.2);
    border-radius: 999rpx;
    backdrop-filter: blur(12rpx);
}

.work-hero__meta-text {
    font-size: 24rpx;
    font-weight: 500;
    color: #fff;
}

.detail-card {
    margin: 0 24rpx;

    &--meta {
        margin-bottom: 8rpx;
    }
}

.detail-card__head {
    display: flex;
    flex-direction: column;
    gap: 10rpx;
    margin-bottom: 24rpx;
}

.detail-card__eyebrow {
    font-size: 22rpx;
    font-weight: 600;
    letter-spacing: 0;
    color: var(--wm-color-primary, #0b0b0b);
}

.detail-card__title {
    font-size: 32rpx;
    font-weight: 700;
    line-height: 1.35;
    color: var(--wm-text-primary, #111111);
}

.detail-card__body-text {
    font-size: 28rpx;
    line-height: 1.8;
    color: var(--wm-text-secondary, #5f5a50);
    white-space: pre-wrap;
}

.staff-summary-card {
    margin: 0 24rpx;
}

.staff-summary-card__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 24rpx;
    gap: 16rpx;
}

.staff-summary-card__identity {
    display: flex;
    align-items: flex-start;
    gap: 22rpx;
    flex: 1;
}

.staff-summary-card__avatar {
    width: 128rpx;
    height: 128rpx;
    border-radius: 28rpx;
    flex-shrink: 0;
}

.staff-summary-card__copy {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 14rpx;
}

.staff-summary-card__title-row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12rpx;
}

.staff-summary-card__name {
    font-size: 34rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #111111);
}

.staff-summary-card__meta {
    font-size: 24rpx;
    color: var(--wm-text-secondary, #5f5a50);
}

.staff-summary-card__badge-row {
    display: flex;
    flex-wrap: wrap;
    gap: 10rpx;
}

.staff-summary-card__price-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    padding-top: 24rpx;
    border-top: 1rpx solid rgba(231, 226, 214, 0.9);
}

.staff-summary-card__price-label {
    font-size: 26rpx;
    color: var(--wm-text-secondary, #5f5a50);
}

.staff-summary-card__price {
    display: flex;
    align-items: baseline;
    gap: 6rpx;
}

.staff-summary-card__price-symbol {
    font-size: 28rpx;
    font-weight: 700;
    color: var(--wm-color-primary, #0b0b0b);
}

.staff-summary-card__price-value {
    font-size: 44rpx;
    font-weight: 700;
    color: var(--wm-color-primary, #0b0b0b);
}

.staff-summary-card__price-unit,
.staff-summary-card__price-negotiable {
    font-size: 24rpx;
    color: var(--wm-text-secondary, #5f5a50);
}

.staff-summary-card__price-negotiable {
    font-size: 30rpx;
    font-weight: 700;
}

.images-grid {
    display: grid;
    gap: 12rpx;
}

.grid-single {
    grid-template-columns: 1fr;
}

.grid-double {
    grid-template-columns: repeat(2, 1fr);
}

.grid-four {
    grid-template-columns: repeat(2, 1fr);
}

.grid-nine {
    grid-template-columns: repeat(3, 1fr);
}

.image-item {
    width: 100%;
    height: 220rpx;
    border-radius: 16rpx;
    transition: all 0.2s ease;
}

.grid-single .image-item {
    height: 480rpx;
}

.grid-double .image-item {
    height: 320rpx;
}

.image-item:active {
    transform: scale(0.95);
}

.image-more {
    width: 100%;
    height: 220rpx;
    border-radius: 16rpx;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
}

.more-text {
    font-size: 40rpx;
    font-weight: 700;
    color: #ffffff;
}

.video-player {
    width: 100%;
    height: 420rpx;
    border-radius: 16rpx;
}

.detail-card__meta-list {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
}

.detail-card__meta-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20rpx;
}

.detail-card__meta-label {
    font-size: 24rpx;
    color: var(--wm-text-secondary, #5f5a50);
}

.detail-card__meta-value {
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-text-primary, #111111);
    text-align: right;
}
</style>
