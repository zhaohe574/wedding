<template>
    <page-meta :page-style="pageStyle" />
    <PageShell scene="consumer">
        <BaseNavbar title="作品详情" />

        <view class="work-detail wm-page-content" v-if="workDetail">
            <!-- 顶部封面区域 -->
            <view class="cover-section">
                <image
                    class="cover-image"
                    :src="
                        workDetail.cover ||
                        workDetail.images?.[0] ||
                        '/static/images/default_cover.png'
                    "
                    mode="aspectFill"
                    @click="previewCover"
                />

                <!-- 玻璃态信息卡 -->
                <view class="glass-info-card wm-panel-card">
                    <view class="title-row">
                        <text class="work-title">{{ workDetail.title || '未命名作品' }}</text>
                        <view class="badges-group">
                            <view class="view-badge">
                                <tn-icon name="eye" size="24" color="#C99B73" />
                                <text class="view-count">{{ workDetail.view_count || 0 }}</text>
                            </view>
                            <view
                                class="type-badge"
                                :style="{ backgroundColor: $theme.primaryColor }"
                            >
                                <text class="type-text">{{ workDetail.type_desc || '作品' }}</text>
                            </view>
                        </view>
                    </view>

                    <view class="info-tags">
                        <view v-if="workDetail.shoot_date" class="info-tag">
                            <tn-icon name="calendar" size="24" color="#666" />
                            <text class="tag-text">{{ workDetail.shoot_date }}</text>
                        </view>
                        <view v-if="workDetail.location" class="info-tag">
                            <tn-icon name="map-pin" size="24" color="#666" />
                            <text class="tag-text">{{ workDetail.location }}</text>
                        </view>
                    </view>
                </view>
            </view>

            <!-- 作品描述 -->
            <view v-if="workDetail.description" class="desc-card wm-form-block">
                <view class="section-header">
                    <tn-icon name="document" size="32" :color="$theme.primaryColor" />
                    <text class="section-title">作品说明</text>
                </view>
                <text class="desc-text">{{ workDetail.description }}</text>
            </view>

            <!-- 工作人员信息卡片 -->
            <view v-if="workDetail.staff" class="staff-card wm-panel-card" @click="goToStaffDetail">
                <view class="staff-header">
                    <view class="staff-left">
                        <image
                            class="staff-avatar"
                            :src="
                                workDetail.staff.avatar || '/static/images/user/default_avatar.png'
                            "
                            mode="aspectFill"
                        />
                        <view class="staff-info">
                            <view class="staff-name-row">
                                <text class="staff-name">{{ workDetail.staff.name || '-' }}</text>
                                <view
                                    class="staff-badge"
                                    :style="{ backgroundColor: $theme.secondaryColor }"
                                >
                                    <text class="badge-text">{{
                                        workDetail.staff.category_name || '未分类'
                                    }}</text>
                                </view>
                            </view>
                            <view class="staff-meta">
                                <text class="staff-sn">工号：{{ workDetail.staff.sn || '-' }}</text>
                            </view>
                        </view>
                    </view>
                    <tn-icon name="arrow-right" size="32" color="#999" />
                </view>

                <view class="staff-stats">
                    <view class="staff-stat-item">
                        <text class="staff-stat-value" :style="{ color: $theme.primaryColor }">
                            {{ workDetail.staff.rating || '5.0' }}
                        </text>
                        <text class="staff-stat-label">综合评分</text>
                    </view>
                    <view class="staff-stat-item">
                        <text class="staff-stat-value" :style="{ color: $theme.ctaColor }">
                            {{ workDetail.staff.order_count || 0 }}
                        </text>
                        <text class="staff-stat-label">服务次数</text>
                    </view>
                    <view class="staff-stat-item">
                        <text class="staff-stat-value" :style="{ color: $theme.secondaryColor }">
                            {{ workDetail.staff.review_count || 0 }}
                        </text>
                        <text class="staff-stat-label">评价数</text>
                    </view>
                    <view class="staff-stat-item">
                        <text class="staff-stat-value" :style="{ color: '#10B981' }">
                            {{ workDetail.staff.favorite_count || 0 }}
                        </text>
                        <text class="staff-stat-label">收藏数</text>
                    </view>
                </view>

                <view class="staff-price-row">
                    <text class="price-label">服务价格</text>
                    <view class="staff-price">
                        <template
                            v-if="
                                workDetail.staff.has_price !== false &&
                                workDetail.staff.price !== null &&
                                workDetail.staff.price !== undefined
                            "
                        >
                            <text class="price-symbol" :style="{ color: $theme.ctaColor }">¥</text>
                            <text class="price-value" :style="{ color: $theme.ctaColor }">
                                {{ workDetail.staff.price_text || workDetail.staff.price }}
                            </text>
                            <text class="price-unit">/次起</text>
                        </template>
                        <text v-else class="price-negotiable">面议</text>
                    </view>
                </view>
            </view>

            <!-- 作品图片 -->
            <view v-if="workDetail.images?.length" class="images-card wm-form-block">
                <view class="section-header">
                    <tn-icon name="image" size="32" :color="$theme.primaryColor" />
                    <text class="section-title">作品图片（{{ workDetail.images.length }}张）</text>
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
            </view>

            <!-- 作品视频 -->
            <view v-if="workDetail.video" class="video-card wm-form-block">
                <view class="section-header">
                    <tn-icon name="video" size="32" :color="$theme.primaryColor" />
                    <text class="section-title">作品视频</text>
                </view>
                <video
                    :src="workDetail.video"
                    class="video-player"
                    object-fit="cover"
                    :controls="true"
                    :show-center-play-btn="true"
                />
            </view>

            <!-- 时间信息 -->
            <view class="time-info">
                <text class="time-text">创建时间：{{ workDetail.create_time || '-' }}</text>
                <text class="time-text">更新时间：{{ workDetail.update_time || '-' }}</text>
            </view>
        </view>

        <view v-else class="loading-container">
            <tn-loading mode="circle" />
        </view>
    </PageShell>
</template>

<script lang="ts" setup>
import { ref, computed } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
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
    padding-bottom: 24rpx;
}

.loading-container {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
}

/* 顶部封面区域 */
.cover-section {
    position: relative;
    width: 100%;
    height: 560rpx;
    margin-bottom: 24rpx;
}

.cover-image {
    width: 100%;
    height: 100%;
    display: block;
}

.glass-info-card {
    position: absolute;
    bottom: 24rpx;
    left: 24rpx;
    right: 24rpx;
    padding: 24rpx;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(20rpx);
    border-radius: 24rpx;
    box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.12);
}

.title-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16rpx;
    margin-bottom: 16rpx;
}

.work-title {
    font-size: 36rpx;
    font-weight: 700;
    color: #1f2937;
    flex: 1;
    line-height: 1.4;
}

.badges-group {
    display: flex;
    align-items: center;
    gap: 8rpx;
    flex-shrink: 0;
}

.view-badge {
    display: flex;
    align-items: center;
    gap: 6rpx;
    padding: 6rpx 12rpx;
    background: rgba(59, 130, 246, 0.1);
    border-radius: 20rpx;
}

.view-count {
    font-size: 22rpx;
    color: #3b82f6;
    font-weight: 500;
}

.type-badge {
    padding: 8rpx 20rpx;
    border-radius: 24rpx;
}

.type-text {
    color: #ffffff;
    font-size: 24rpx;
    font-weight: 500;
}

.info-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 16rpx;
}

.info-tag {
    display: flex;
    align-items: center;
    gap: 8rpx;
}

.tag-text {
    font-size: 24rpx;
    color: #6b7280;
}

/* 通用卡片样式 */
.desc-card,
.staff-card,
.images-card,
.video-card {
    margin: 0 24rpx 24rpx;
    padding: 28rpx;
    background: #ffffff;
    border-radius: 24rpx;
    box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.05);
}

.section-header {
    display: flex;
    align-items: center;
    gap: 12rpx;
    margin-bottom: 20rpx;
}

.section-title {
    font-size: 30rpx;
    font-weight: 700;
    color: #1f2937;
}

/* 作品描述 */
.desc-text {
    font-size: 28rpx;
    line-height: 1.8;
    color: #4b5563;
    white-space: pre-wrap;
}

/* 工作人员卡片 */
.staff-card {
    transition: all 0.2s ease;
}

.staff-card:active {
    transform: scale(0.98);
}

.staff-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24rpx;
    padding-bottom: 24rpx;
    border-bottom: 2rpx solid #f3f4f6;
}

.staff-left {
    display: flex;
    align-items: center;
    gap: 20rpx;
    flex: 1;
}

.staff-avatar {
    width: 112rpx;
    height: 112rpx;
    border-radius: 20rpx;
    flex-shrink: 0;
}

.staff-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.staff-name-row {
    display: flex;
    align-items: center;
    gap: 12rpx;
}

.staff-name {
    font-size: 32rpx;
    font-weight: 700;
    color: #1f2937;
}

.staff-badge {
    padding: 4rpx 12rpx;
    border-radius: 12rpx;
}

.badge-text {
    font-size: 22rpx;
    color: #ffffff;
}

.staff-meta {
    display: flex;
    flex-direction: column;
    gap: 4rpx;
}

.staff-sn {
    font-size: 24rpx;
    color: #9ca3af;
}

.staff-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16rpx;
    margin-bottom: 24rpx;
    padding-bottom: 24rpx;
    border-bottom: 2rpx solid #f3f4f6;
}

.staff-stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4rpx;
}

.staff-stat-value {
    font-size: 32rpx;
    font-weight: 700;
}

.staff-stat-label {
    font-size: 22rpx;
    color: #9ca3af;
}

.staff-price-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.price-label {
    font-size: 26rpx;
    color: #6b7280;
}

.staff-price {
    display: flex;
    align-items: baseline;
    gap: 4rpx;
}

.price-symbol {
    font-size: 28rpx;
    font-weight: 600;
}

.price-value {
    font-size: 44rpx;
    font-weight: 700;
}

.price-unit {
    font-size: 24rpx;
    color: #9ca3af;
}

.price-negotiable {
    font-size: 32rpx;
    font-weight: 700;
    color: #9ca3af;
}

/* 作品图片 */
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

/* 作品视频 */
.video-player {
    width: 100%;
    height: 420rpx;
    border-radius: 16rpx;
}

/* 时间信息 */
.time-info {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
    padding: 0 24rpx 24rpx;
}

.time-text {
    font-size: 22rpx;
    color: #9ca3af;
}
</style>
