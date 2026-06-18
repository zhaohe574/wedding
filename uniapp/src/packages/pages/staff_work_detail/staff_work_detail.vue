<template>
    <page-meta :page-style="pageStyle" />
    <PageShell scene="consumer" tone="detail">
        <BaseNavbar
            title="作品详情"
            variant="solid"
            title-align="center"
            bg-color="#191713"
            text-color="#FFFDF8"
        />

        <view v-if="workDetail" class="work-detail">
            <BaseCard variant="bare" scene="consumer" padding="0" class="hero-card">
                <view class="work-hero">
                    <image
                        class="work-hero__image"
                        :src="heroImage"
                        mode="aspectFill"
                        @click="previewCover"
                    />

                    <view class="work-hero__overlay">
                        <view class="work-hero__badge-row">
                            <StatusBadge tone="primary" size="sm">
                                {{ workDetail.type_desc || '作品' }}
                            </StatusBadge>
                            <StatusBadge tone="neutral" size="sm">
                                浏览 {{ workDetail.view_count || 0 }}
                            </StatusBadge>
                        </view>

                        <view class="work-hero__copy">
                            <text class="work-hero__title">
                                {{ workDetail.title || '未命名作品' }}
                            </text>
                            <view class="work-hero__meta-list">
                                <view v-if="workDetail.shoot_date" class="work-hero__meta-pill">
                                    <BaseIcon name="calendar" size="22" color="#D9BE82" />
                                    <text class="work-hero__meta-text">
                                        {{ workDetail.shoot_date }}
                                    </text>
                                </view>
                                <view v-if="workDetail.location" class="work-hero__meta-pill">
                                    <BaseIcon name="map-pin" size="22" color="#D9BE82" />
                                    <text class="work-hero__meta-text">
                                        {{ workDetail.location }}
                                    </text>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </BaseCard>

            <BaseCard variant="panel" scene="consumer" class="detail-card">
                <view class="card-head">
                    <text class="card-head__title">作品信息</text>
                    <text class="card-head__meta">{{ mediaSummaryText }}</text>
                </view>

                <text v-if="workDetail.description" class="work-description">
                    {{ workDetail.description }}
                </text>

                <view class="info-list">
                    <BaseInfoRow label="拍摄日期" :value="workDetail.shoot_date || '-'" />
                    <BaseInfoRow label="拍摄地点" :value="workDetail.location || '-'" />
                    <BaseInfoRow label="作品类型" :value="workDetail.type_desc || '作品'" />
                    <BaseInfoRow label="浏览量" :value="String(workDetail.view_count || 0)" />
                </view>
            </BaseCard>

            <BaseCard
                v-if="workDetail.staff"
                variant="panel"
                scene="consumer"
                class="staff-card"
                interactive
                @click="goToStaffDetail"
            >
                <view class="card-head">
                    <text class="card-head__title">服务人员</text>
                    <StatusBadge tone="info" size="sm">
                        {{ workDetail.staff.category_name || '未分类' }}
                    </StatusBadge>
                </view>

                <view class="staff-card__profile">
                    <image
                        class="staff-card__avatar"
                        :src="workDetail.staff.avatar || '/static/images/user/default_avatar.png'"
                        mode="aspectFill"
                    />
                    <view class="staff-card__copy">
                        <text class="staff-card__name">{{ workDetail.staff.name || '-' }}</text>
                        <text class="staff-card__sn">工号 {{ workDetail.staff.sn || '-' }}</text>
                    </view>
                    <BaseIcon name="arrow-right" size="30" color="#9A9388" />
                </view>

                <view class="staff-stats">
                    <view class="staff-stat">
                        <text class="staff-stat__value">
                            {{ formatStaffRating(workDetail.staff.rating) }}
                        </text>
                        <text class="staff-stat__label">评分</text>
                    </view>
                    <view class="staff-stat">
                        <text class="staff-stat__value">{{ workDetail.staff.order_count || 0 }}</text>
                        <text class="staff-stat__label">服务</text>
                    </view>
                    <view class="staff-stat">
                        <text class="staff-stat__value">{{ workDetail.staff.review_count || 0 }}</text>
                        <text class="staff-stat__label">评价</text>
                    </view>
                    <view class="staff-stat">
                        <text class="staff-stat__value">
                            {{ workDetail.staff.favorite_count || 0 }}
                        </text>
                        <text class="staff-stat__label">收藏</text>
                    </view>
                </view>

                <view class="staff-card__footer">
                    <BaseInfoRow label="服务价格" :value="staffPriceText" tone="price" />
                    <BaseButton
                        label="查看主页"
                        variant="light"
                        size="mini"
                        height="56rpx"
                        @click.stop="goToStaffDetail"
                    />
                </view>
            </BaseCard>

            <BaseCard
                v-if="imageCount"
                variant="panel"
                scene="consumer"
                class="detail-card"
            >
                <view class="card-head">
                    <text class="card-head__title">图片</text>
                    <text class="card-head__meta">{{ imageCount }} 张</text>
                </view>

                <view class="images-grid" :class="getGridClass">
                    <view
                        v-for="(img, index) in displayImages"
                        :key="index"
                        class="image-tile"
                        @click="previewImages(index)"
                    >
                        <image :src="img" class="image-item" mode="aspectFill" />
                        <view
                            v-if="index === displayImages.length - 1 && imageCount > 9"
                            class="image-more"
                        >
                            <text class="more-text">+{{ imageCount - 9 }}</text>
                        </view>
                    </view>
                </view>
            </BaseCard>

            <BaseCard
                v-if="workDetail.video"
                variant="panel"
                scene="consumer"
                class="detail-card"
            >
                <view class="card-head">
                    <text class="card-head__title">视频</text>
                </view>
                <video
                    :src="workDetail.video"
                    class="video-player"
                    object-fit="cover"
                    :controls="true"
                    :show-center-play-btn="true"
                />
            </BaseCard>

            <BaseCard variant="panel" scene="consumer" class="detail-card detail-card--last">
                <view class="card-head">
                    <text class="card-head__title">记录</text>
                </view>

                <view class="info-list">
                    <BaseInfoRow label="创建时间" :value="workDetail.create_time || '-'" />
                    <BaseInfoRow label="更新时间" :value="workDetail.update_time || '-'" />
                </view>
            </BaseCard>
        </view>

        <view v-else class="loading-container">
            <LoadingState text="作品加载中" tone="workspace" />
        </view>
    </PageShell>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseInfoRow from '@/components/base/BaseInfoRow.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { getWorkDetail } from '@/api/staff'
import { useThemeStore } from '@/stores/theme'
import { showError } from '@/utils/feedback'

const $theme = useThemeStore()
const workDetail = ref<any>(null)

const pageStyle = computed(() => $theme.pageStyle)

const images = computed<string[]>(() =>
    Array.isArray(workDetail.value?.images) ? workDetail.value.images : []
)

const imageCount = computed(() => images.value.length)

const displayImages = computed(() => images.value.slice(0, 9))

const heroImage = computed(
    () => workDetail.value?.cover || images.value[0] || '/static/images/default_cover.png'
)

const getGridClass = computed(() => {
    const count = displayImages.value.length
    if (count === 1) return 'grid-single'
    if (count === 2) return 'grid-double'
    if (count === 4) return 'grid-four'
    return 'grid-nine'
})

const mediaSummaryText = computed(() => {
    const parts: string[] = []
    if (imageCount.value) parts.push(`${imageCount.value} 张图`)
    if (workDetail.value?.video) parts.push('含视频')
    return parts.length ? parts.join(' / ') : '暂无素材'
})

const staffPriceText = computed(() => {
    const staff = workDetail.value?.staff
    if (!staff) return '-'
    const hasPrice =
        staff.has_price !== false && staff.price !== null && staff.price !== undefined
    if (!hasPrice) return '面议'
    return `¥${staff.price_text || staff.price}/次起`
})

const resolveWorkDetailError = (error: unknown, fallback = '操作失败') => {
    if (typeof error === 'string' && error.trim()) {
        return error
    }

    if (error && typeof error === 'object') {
        const value =
            (error as { msg?: unknown; message?: unknown }).msg ??
            (error as { message?: unknown }).message

        if (typeof value === 'string' && value.trim()) {
            return value
        }
    }

    return fallback
}

const loadDetail = async (id: number) => {
    try {
        const data = await getWorkDetail({ id })
        workDetail.value = data
    } catch (e: any) {
        showError(resolveWorkDetailError(e, '加载失败'))
        setTimeout(() => {
            uni.navigateBack()
        }, 1500)
    }
}

const previewCover = () => {
    const cover = workDetail.value?.cover || images.value[0]
    if (!cover) return
    uni.previewImage({
        urls: [cover],
        current: cover
    })
}

const previewImages = (index: number) => {
    if (!images.value.length) return
    uni.previewImage({
        urls: images.value,
        current: images.value[index] || images.value[0]
    })
}

const goToStaffDetail = () => {
    const staffId = workDetail.value?.staff?.id
    if (!staffId) return
    uni.navigateTo({
        url: `/packages/pages/staff_detail/staff_detail?id=${staffId}`
    })
}

const formatStaffRating = (value: number | string | null | undefined) => {
    const rating = Number(value ?? 0)
    return Number.isFinite(rating) ? rating.toFixed(1) : '0.0'
}

onLoad((options: any) => {
    const id = Number(options?.id || 0)
    if (!id) {
        showError('作品信息错误')
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
    gap: 22rpx;
    padding: 24rpx var(--wm-space-page-x, 37rpx) calc(40rpx + env(safe-area-inset-bottom));
    box-sizing: border-box;
    background: radial-gradient(
            circle at top left,
            rgba(11, 11, 11, 0.08) 0,
            rgba(248, 247, 242, 0) 38%
        ),
        linear-gradient(180deg, var(--wm-color-bg-page, #ffffff) 0%, #f8f7f2 100%);
}

.loading-container {
    min-height: 100vh;
    padding: 24rpx var(--wm-space-page-x, 37rpx);
    box-sizing: border-box;
    background: linear-gradient(180deg, var(--wm-color-bg-page, #ffffff) 0%, #f8f7f2 100%);
}

.hero-card {
    overflow: hidden;
    border-radius: 40rpx;
    box-shadow: 0 20rpx 48rpx rgba(74, 43, 24, 0.12);
}

.work-hero {
    position: relative;
    width: 100%;
    height: 560rpx;
    min-height: 560rpx;
    overflow: hidden;
    border-radius: inherit;
    background: #191713;
}

.work-hero__image {
    width: 100%;
    height: 100%;
    display: block;
}

.work-hero__overlay {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 24rpx;
    padding: 28rpx;
    background: linear-gradient(
        180deg,
        rgba(11, 11, 11, 0.18) 0%,
        rgba(11, 11, 11, 0.12) 38%,
        rgba(11, 11, 11, 0.72) 100%
    );
    box-sizing: border-box;
}

.work-hero__badge-row,
.work-hero__meta-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10rpx;
}

.work-hero__copy {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
}

.work-hero__title {
    font-size: 42rpx;
    font-weight: 900;
    line-height: 1.28;
    color: #fffdf8;
    word-break: break-word;
}

.work-hero__meta-pill {
    min-height: 48rpx;
    padding: 0 16rpx;
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    border-radius: 999rpx;
    background: rgba(255, 253, 248, 0.12);
    border: 1rpx solid rgba(255, 253, 248, 0.2);
    backdrop-filter: blur(12rpx);
    -webkit-backdrop-filter: blur(12rpx);
}

.work-hero__meta-text {
    max-width: 420rpx;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 23rpx;
    font-weight: 700;
    color: #fffdf8;
}

.detail-card,
.staff-card {
    overflow: hidden;
}

.detail-card--last {
    margin-bottom: 4rpx;
}

.card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18rpx;
    margin-bottom: 18rpx;
}

.card-head__title {
    min-width: 0;
    font-size: 30rpx;
    font-weight: 900;
    line-height: 1.3;
    color: var(--wm-text-primary, #111111);
}

.card-head__meta {
    flex-shrink: 0;
    font-size: 22rpx;
    font-weight: 800;
    line-height: 1.3;
    color: var(--wm-color-gold, #b8954a);
}

.work-description {
    display: block;
    margin-bottom: 18rpx;
    font-size: 26rpx;
    font-weight: 700;
    line-height: 1.7;
    color: var(--wm-text-secondary, #5f5a50);
    white-space: pre-wrap;
    word-break: break-word;
}

.info-list {
    border-radius: 28rpx;
    background: rgba(255, 253, 248, 0.72);
    border: 1rpx solid rgba(216, 201, 173, 0.72);
    overflow: hidden;
}

.info-list :deep(.base-info-row) {
    min-height: 76rpx;
    padding: 0 20rpx;
    box-sizing: border-box;
}

.info-list :deep(.base-info-row + .base-info-row) {
    border-top: 1rpx solid rgba(216, 201, 173, 0.58);
}

.staff-card__profile {
    display: flex;
    align-items: center;
    gap: 18rpx;
}

.staff-card__avatar {
    width: 112rpx;
    height: 112rpx;
    flex-shrink: 0;
    border-radius: 30rpx;
    background: #f8f7f2;
}

.staff-card__copy {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.staff-card__name {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 32rpx;
    font-weight: 900;
    line-height: 1.3;
    color: var(--wm-text-primary, #111111);
}

.staff-card__sn {
    font-size: 23rpx;
    font-weight: 700;
    line-height: 1.3;
    color: var(--wm-text-secondary, #5f5a50);
}

.staff-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10rpx;
    margin-top: 22rpx;
}

.staff-stat {
    min-width: 0;
    min-height: 92rpx;
    padding: 14rpx 8rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    border-radius: 24rpx;
    background: rgba(248, 247, 242, 0.88);
    border: 1rpx solid rgba(216, 201, 173, 0.58);
    box-sizing: border-box;
}

.staff-stat__value {
    font-size: 28rpx;
    font-weight: 900;
    line-height: 1;
    color: var(--wm-text-primary, #111111);
}

.staff-stat__label {
    font-size: 20rpx;
    font-weight: 800;
    line-height: 1;
    color: var(--wm-text-secondary, #5f5a50);
}

.staff-card__footer {
    display: flex;
    align-items: center;
    gap: 18rpx;
    margin-top: 22rpx;
    padding-top: 18rpx;
    border-top: 1rpx solid rgba(216, 201, 173, 0.68);
}

.staff-card__footer :deep(.base-info-row) {
    flex: 1;
    min-width: 0;
    min-height: 56rpx;
}

.images-grid {
    display: grid;
    gap: 12rpx;
}

.grid-single {
    grid-template-columns: 1fr;
}

.grid-double,
.grid-four {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.grid-nine {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.image-tile {
    position: relative;
    width: 100%;
    height: 220rpx;
    overflow: hidden;
    border-radius: 20rpx;
    background: #f8f7f2;
}

.grid-single .image-tile {
    height: 480rpx;
}

.grid-double .image-tile {
    height: 320rpx;
}

.image-item {
    width: 100%;
    height: 100%;
    display: block;
    transition: transform 0.2s ease;
}

.image-tile:active .image-item {
    transform: scale(0.96);
}

.image-more {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(25, 23, 19, 0.58);
}

.more-text {
    font-size: 38rpx;
    font-weight: 900;
    color: #fffdf8;
}

.video-player {
    width: 100%;
    height: 420rpx;
    display: block;
    border-radius: 24rpx;
    overflow: hidden;
    background: #191713;
}
</style>
