<template>
    <page-meta :page-style="pageStyle" />
    <PageShell scene="consumer" tone="editorial" has-safe-bottom>
        <BaseNavbar
            title="作品详情"
            variant="solid"
            title-align="center"
            bg-color="#181614"
            text-color="#FFFDF8"
        />

        <!-- 主内容区 -->
        <view v-if="workDetail && !loading" class="work-detail">
            <!-- 顶部多媒体展厅 -->
            <view class="hero-showcase">
                <!-- 媒体类型切换标签 (当同时存在视频与图片时显示) -->
                <view v-if="hasVideo && hasImages" class="media-tabs-capsule">
                    <view
                        class="media-tab-btn"
                        :class="{ 'media-tab-btn--active': mediaTab === 'video' }"
                        @click="mediaTab = 'video'"
                    >
                        <BaseIcon
                            name="play"
                            size="22"
                            :color="mediaTab === 'video' ? '#181614' : '#FFFDF8'"
                        />
                        <text class="media-tab-text">视频</text>
                    </view>
                    <view
                        class="media-tab-btn"
                        :class="{ 'media-tab-btn--active': mediaTab === 'gallery' }"
                        @click="mediaTab = 'gallery'"
                    >
                        <BaseIcon
                            name="empty-data"
                            size="22"
                            :color="mediaTab === 'gallery' ? '#181614' : '#FFFDF8'"
                        />
                        <text class="media-tab-text">图集 ({{ imageCount }})</text>
                    </view>
                </view>

                <!-- 视频展示模式 -->
                <view v-if="mediaTab === 'video' && workDetail.video" class="hero-video-box">
                    <video
                        :src="workDetail.video"
                        :poster="heroCover"
                        class="hero-video-player"
                        object-fit="cover"
                        :controls="true"
                        :show-center-play-btn="true"
                        :enable-play-gesture="true"
                    />
                </view>

                <!-- 图片轮播模式 -->
                <view v-else class="hero-gallery-box">
                    <swiper
                        class="hero-swiper"
                        :circular="imageCount > 1"
                        :current="currentImageIndex"
                        @change="handleSwiperChange"
                    >
                        <swiper-item
                            v-for="(img, index) in (hasImages ? images : [heroCover])"
                            :key="index"
                            class="hero-swiper-item"
                        >
                            <image
                                class="hero-swiper-image"
                                :src="img"
                                mode="aspectFill"
                                @click="previewImage(index)"
                            />
                        </swiper-item>
                    </swiper>

                    <!-- 顶部渐变暗影与悬浮页码指示器 -->
                    <view class="hero-overlay">
                        <view class="hero-overlay__bottom">
                            <view class="hero-pill-indicator" @click="previewImage(currentImageIndex)">
                                <BaseIcon name="tip" size="20" color="#D9BE82" />
                                <text class="hero-pill-text">全屏高清</text>
                            </view>
                            <view v-if="imageCount > 1" class="hero-page-badge">
                                <text class="hero-page-current">
                                    {{ String(currentImageIndex + 1).padStart(2, '0') }}
                                </text>
                                <text class="hero-page-divider">/</text>
                                <text class="hero-page-total">
                                    {{ String(imageCount).padStart(2, '0') }}
                                </text>
                            </view>
                        </view>
                    </view>
                </view>
            </view>

            <!-- 页面内容主体 -->
            <view class="detail-body">
                <!-- 作品叙事与主题信息卡片 -->
                <BaseCard variant="panel" scene="consumer" class="narrative-card" padding="36rpx 32rpx">
                    <!-- 标签行 -->
                    <view class="meta-badge-row">
                        <StatusBadge tone="primary" size="sm">
                            {{ workDetail.type_desc || '精选作品' }}
                        </StatusBadge>
                        <view v-if="workDetail.shoot_date" class="meta-pill">
                            <BaseIcon name="calendar" size="22" color="#C6A15B" />
                            <text class="meta-pill__text">{{ workDetail.shoot_date }}</text>
                        </view>
                        <view v-if="workDetail.location" class="meta-pill">
                            <BaseIcon name="location" size="22" color="#C6A15B" />
                            <text class="meta-pill__text">{{ workDetail.location }}</text>
                        </view>
                        <view class="meta-pill meta-pill--ghost">
                            <BaseIcon name="eye" size="22" color="#8C8273" />
                            <text class="meta-pill__text">{{ formatCount(workDetail.view_count) }} 浏览</text>
                        </view>
                    </view>

                    <!-- 作品大标题 -->
                    <text class="work-title">
                        {{ workDetail.title || '婚礼客照精选' }}
                    </text>

                    <!-- 作品创作手记 / 故事 (如果存在描述) -->
                    <view v-if="workDetail.description" class="work-story-box">
                        <view class="work-story-header">
                            <view class="work-story-quote-mark">“</view>
                            <text class="work-story-title">创作手记</text>
                        </view>
                        <text class="work-story-content">
                            {{ workDetail.description }}
                        </text>
                    </view>
                </BaseCard>

                <!-- 主创人员名片 (核心转化锚点) -->
                <BaseCard
                    v-if="workDetail.staff"
                    variant="panel"
                    scene="consumer"
                    class="artisan-card"
                    interactive
                    @click="goToStaffDetail"
                >
                    <view class="artisan-card__header">
                        <view class="artisan-avatar-wrap">
                            <image
                                class="artisan-avatar"
                                :src="workDetail.staff.avatar || '/static/images/user/default_avatar.png'"
                                mode="aspectFill"
                            />
                            <view class="artisan-badge-tag">
                                <BaseIcon name="trusty" size="18" color="#FFFDF8" />
                            </view>
                        </view>

                        <view class="artisan-profile">
                            <view class="artisan-name-row">
                                <text class="artisan-name">{{ workDetail.staff.name || '特邀主创' }}</text>
                                <StatusBadge tone="warning" size="xs">
                                    {{ workDetail.staff.category_name || '婚礼服务团队' }}
                                </StatusBadge>
                            </view>
                            <text class="artisan-sn">工号 {{ workDetail.staff.sn || '-' }}</text>
                        </view>

                        <view class="artisan-entry-link">
                            <text class="artisan-entry-text">进入主页</text>
                            <BaseIcon name="right" size="24" color="#C6A15B" />
                        </view>
                    </view>

                    <!-- 口碑与服务指标条 -->
                    <view class="artisan-stats-grid">
                        <view class="artisan-stat-item">
                            <view class="artisan-stat-val-wrap">
                                <text class="artisan-stat-val artisan-stat-val--highlight">
                                    {{ formatStaffRating(workDetail.staff.rating) }}
                                </text>
                                <BaseIcon name="star-fill" size="20" color="#C6A15B" />
                            </view>
                            <text class="artisan-stat-label">综合评分</text>
                        </view>
                        <view class="artisan-stat-item">
                            <text class="artisan-stat-val">
                                {{ workDetail.staff.order_count || 0 }}
                            </text>
                            <text class="artisan-stat-label">累计服务</text>
                        </view>
                        <view class="artisan-stat-item">
                            <text class="artisan-stat-val">
                                {{ workDetail.staff.review_count || 0 }}
                            </text>
                            <text class="artisan-stat-label">真实评价</text>
                        </view>
                        <view class="artisan-stat-item">
                            <text class="artisan-stat-val">
                                {{ workDetail.staff.favorite_count || 0 }}
                            </text>
                            <text class="artisan-stat-label">粉丝关注</text>
                        </view>
                    </view>

                    <!-- 服务起价与预约快捷条 -->
                    <view class="artisan-card__footer">
                        <view class="artisan-price-box">
                            <text class="artisan-price-label">服务起价</text>
                            <view class="artisan-price-val-group">
                                <template v-if="staffPriceText !== '面议'">
                                    <text class="artisan-price-currency">¥</text>
                                    <text class="artisan-price-num">{{ staffPriceText }}</text>
                                    <text class="artisan-price-unit">/次起</text>
                                </template>
                                <text v-else class="artisan-price-negotiable">面议</text>
                            </view>
                        </view>
                        <BaseButton
                            label="查看档期"
                            variant="light"
                            size="mini"
                            height="60rpx"
                            @click.stop="goToStaffDetail"
                        />
                    </view>
                </BaseCard>

                <!-- 作品高清大图鉴赏流 -->
                <BaseCard
                    v-if="hasImages"
                    variant="panel"
                    scene="consumer"
                    class="gallery-stream-card"
                    padding="36rpx 28rpx"
                >
                    <view class="section-heading">
                        <view class="section-heading__title-box">
                            <view class="section-accent-dot" />
                            <text class="section-title">作品画廊</text>
                        </view>
                        <text class="section-subtitle">共 {{ imageCount }} 张高清大片</text>
                    </view>

                    <view class="gallery-stream-list">
                        <view
                            v-for="(img, idx) in images"
                            :key="idx"
                            class="gallery-photo-item"
                            @click="previewImage(idx)"
                        >
                            <image
                                class="gallery-photo-img"
                                :src="img"
                                mode="widthFix"
                                :lazy-load="true"
                            />
                            <view class="gallery-photo-meta">
                                <view class="photo-index-tag">
                                    <text>PHOTO {{ String(idx + 1).padStart(2, '0') }}</text>
                                </view>
                                <view class="photo-zoom-hint">
                                    <BaseIcon name="tip" size="18" color="#FFFDF8" />
                                    <text>点击全屏</text>
                                </view>
                            </view>
                        </view>
                    </view>
                </BaseCard>

                <!-- 主创更多作品推荐 (横滑探索卡片) -->
                <BaseCard
                    v-if="relatedWorks.length > 0"
                    variant="panel"
                    scene="consumer"
                    class="related-works-card"
                    padding="36rpx 28rpx"
                >
                    <view class="section-heading">
                        <view class="section-heading__title-box">
                            <view class="section-accent-dot" />
                            <text class="section-title">主创更多作品</text>
                        </view>
                        <text class="section-subtitle">共 {{ relatedWorks.length }} 部代表作</text>
                    </view>

                    <scroll-view scroll-x class="related-works-scroll" :show-scrollbar="false">
                        <view class="related-works-flex">
                            <view
                                v-for="relWork in relatedWorks"
                                :key="relWork.id"
                                class="related-work-card"
                                @click="goToWorkDetail(relWork)"
                            >
                                <view class="related-work-cover-wrap">
                                    <image
                                        class="related-work-cover"
                                        :src="relWork.cover || relWork.images?.[0] || '/static/images/default_cover.png'"
                                        mode="aspectFill"
                                    />
                                    <view class="related-work-type-badge">
                                        <text>{{ relWork.type_desc || '客照' }}</text>
                                    </view>
                                </view>
                                <text class="related-work-title">
                                    {{ relWork.title || '精选作品' }}
                                </text>
                                <view class="related-work-meta">
                                    <text v-if="relWork.shoot_date">{{ relWork.shoot_date }}</text>
                                    <text v-else>{{ formatCount(relWork.view_count) }} 浏览</text>
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                </BaseCard>
            </view>

            <!-- 底部常驻吸底操作栏 (固定安全区) -->
            <view class="bottom-action-bar">
                <view class="bottom-action-bar__inner">
                    <!-- 左侧工具按钮组 -->
                    <view class="bottom-action-tools">
                        <!-- 人员主页入口 -->
                        <view class="action-tool-item" @click="goToStaffDetail">
                            <BaseIcon name="my" size="38" color="#181614" />
                            <text class="action-tool-label">主创主页</text>
                        </view>

                        <!-- 微信分享按钮 (原生 open-type) -->
                        <button class="action-tool-item action-tool-item--share" open-type="share">
                            <BaseIcon name="share" size="38" color="#181614" />
                            <text class="action-tool-label">分享作品</text>
                        </button>

                        <!-- 收藏服务人员 -->
                        <view class="action-tool-item" @click="handleToggleFavorite">
                            <view :class="{ 'heart-pulse-anim': isStaffFavorited }">
                                <BaseIcon
                                    :name="isStaffFavorited ? 'like-fill' : 'like'"
                                    size="38"
                                    :color="isStaffFavorited ? '#C6A15B' : '#181614'"
                                />
                            </view>
                            <text
                                class="action-tool-label"
                                :class="{ 'action-tool-label--favorited': isStaffFavorited }"
                            >
                                {{ isStaffFavorited ? '已收藏' : '收藏主创' }}
                            </text>
                        </view>
                    </view>

                    <!-- 右侧主转化按钮 -->
                    <view class="bottom-action-main">
                        <button class="primary-book-btn" @click="goToBooking">
                            <text class="primary-book-btn__title">立即预约该人员</text>
                            <text class="primary-book-btn__sub">专属档期优先锁定</text>
                        </button>
                    </view>
                </view>
            </view>
        </view>

        <!-- 加载中骨架态 -->
        <view v-else-if="loading" class="loading-state-wrapper">
            <LoadingState text="作品载入中..." tone="workspace" />
        </view>

        <!-- 加载失败态 -->
        <view v-else class="error-state-wrapper">
            <EmptyState
                title="未能加载作品信息"
                :description="errorMessage || '作品可能已被移除或暂时不可见'"
                action-text="返回上一页"
                @action="handleNavigateBack"
            />
        </view>
    </PageShell>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { onLoad, onShareAppMessage, onShareTimeline } from '@dcloudio/uni-app'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { getWorkDetail, getStaffWorks, toggleStaffFavorite } from '@/api/staff'
import { getStaffBookingPageUrl } from '@/packages/common/utils/staff-booking'
import { useThemeStore } from '@/stores/theme'
import { useUserStore } from '@/stores/user'
import { showError, showSuccess, showToast } from '@/utils/feedback'

const $theme = useThemeStore()
const userStore = useUserStore()

const currentWorkId = ref(0)
const workDetail = ref<any>(null)
const loading = ref(true)
const errorMessage = ref('')
const currentImageIndex = ref(0)
const mediaTab = ref<'video' | 'gallery'>('gallery')
const relatedWorks = ref<any[]>([])
const isStaffFavorited = ref(false)

const pageStyle = computed(() => $theme.pageStyle)

const images = computed<string[]>(() => {
    return Array.isArray(workDetail.value?.images) ? workDetail.value.images : []
})

const imageCount = computed(() => images.value.length)

const hasVideo = computed(() => Boolean(workDetail.value?.video))
const hasImages = computed(() => images.value.length > 0)

const heroCover = computed(() => {
    return workDetail.value?.cover || images.value[0] || '/static/images/default_cover.png'
})

const staffPriceText = computed(() => {
    const staff = workDetail.value?.staff
    if (!staff) return '面议'
    const hasPrice =
        staff.has_price !== false && staff.price !== null && staff.price !== undefined
    if (!hasPrice) return '面议'
    return String(staff.price_text || staff.price)
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

const formatStaffRating = (value: number | string | null | undefined) => {
    const rating = Number(value ?? 0)
    return Number.isFinite(rating) && rating > 0 ? rating.toFixed(1) : '5.0'
}

const formatCount = (value: number | string | null | undefined) => {
    const count = Number(value ?? 0)
    if (!Number.isFinite(count) || count <= 0) return '0'
    if (count > 9999) return `${(count / 10000).toFixed(1)}w`
    if (count > 999) return `${(count / 1000).toFixed(1)}k`
    return String(count)
}

const handleSwiperChange = (e: any) => {
    currentImageIndex.value = e.detail?.current || 0
}

const previewImage = (index: number) => {
    const urls = images.value.length ? images.value : [heroCover.value]
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

const goToBooking = () => {
    const staffId = workDetail.value?.staff?.id
    if (!staffId) {
        showToast('暂无该服务人员预约信息')
        return
    }
    const bookingUrl = getStaffBookingPageUrl({ staff_id: staffId })
    uni.navigateTo({
        url: bookingUrl
    })
}

const handleToggleFavorite = async () => {
    const staffId = workDetail.value?.staff?.id
    if (!staffId) return

    if (!userStore.isLogin) {
        showToast('请先登录后收藏')
        setTimeout(() => {
            uni.navigateTo({ url: '/pages/login/login' })
        }, 1200)
        return
    }

    try {
        await toggleStaffFavorite({ id: staffId })
        isStaffFavorited.value = !isStaffFavorited.value
        if (workDetail.value?.staff) {
            const currentCount = Number(workDetail.value.staff.favorite_count || 0)
            workDetail.value.staff.favorite_count = isStaffFavorited.value
                ? currentCount + 1
                : Math.max(0, currentCount - 1)
        }
        showSuccess(isStaffFavorited.value ? '已收藏主创' : '已取消收藏')
    } catch (e) {
        showError(resolveWorkDetailError(e, '操作失败'))
    }
}

const goToWorkDetail = (work: any) => {
    if (!work?.id || work.id === currentWorkId.value) return
    uni.navigateTo({
        url: `/packages/pages/staff_work_detail/staff_work_detail?id=${work.id}`
    })
}

const handleNavigateBack = () => {
    uni.navigateBack({
        fail: () => {
            uni.reLaunch({ url: '/pages/index/index' })
        }
    })
}

const loadRelatedWorks = async (staffId: number, currentId: number) => {
    if (!staffId) return
    try {
        const works = await getStaffWorks({ staff_id: staffId })
        if (Array.isArray(works)) {
            relatedWorks.value = works.filter((w: any) => Number(w.id) !== currentId).slice(0, 8)
        }
    } catch {
        relatedWorks.value = []
    }
}

const loadDetail = async (id: number) => {
    loading.value = true
    errorMessage.value = ''
    try {
        const data = await getWorkDetail({ id })
        if (!data || !data.id) {
            throw new Error('未找到该作品')
        }
        workDetail.value = data
        currentWorkId.value = data.id

        // 设定默认展示模式
        if (data.type === 2 && data.video) {
            mediaTab.value = 'video'
        } else {
            mediaTab.value = 'gallery'
        }

        isStaffFavorited.value = Boolean(data.staff?.is_favorite)

        if (data.staff?.id) {
            loadRelatedWorks(Number(data.staff.id), data.id)
        }
    } catch (e: any) {
        const msg = resolveWorkDetailError(e, '作品加载失败')
        errorMessage.value = msg
        showError(msg)
    } finally {
        loading.value = false
    }
}

onLoad((options: any) => {
    const id = Number(options?.id || 0)
    if (!id) {
        errorMessage.value = '作品参数错误'
        loading.value = false
        showError('作品参数错误')
        setTimeout(() => {
            handleNavigateBack()
        }, 1500)
        return
    }
    currentWorkId.value = id
    loadDetail(id)
})

// 微信分享配置
onShareAppMessage(() => {
    const title = workDetail.value?.title
        ? `${workDetail.value.title} · ${workDetail.value?.staff?.name || '主创'}作品精选`
        : '精选婚礼作品详情'
    const imageUrl = heroCover.value
    return {
        title,
        path: `/packages/pages/staff_work_detail/staff_work_detail?id=${currentWorkId.value}`,
        imageUrl
    }
})

onShareTimeline(() => {
    const title = workDetail.value?.title
        ? `${workDetail.value.title} · ${workDetail.value?.staff?.name || '主创'}作品精选`
        : '精选婚礼作品详情'
    return {
        title,
        query: `id=${currentWorkId.value}`,
        imageUrl: heroCover.value
    }
})
</script>

<style lang="scss" scoped>
.work-detail {
    min-height: 100vh;
    padding-bottom: calc(140rpx + env(safe-area-inset-bottom));
    box-sizing: border-box;
    background: radial-gradient(
            circle at 50% 0%,
            rgba(217, 190, 130, 0.12) 0%,
            rgba(248, 246, 240, 0) 42%
        ),
        linear-gradient(180deg, #faf8f2 0%, #f4f0e6 100%);
}

.hero-showcase {
    position: relative;
    width: 100%;
    background: #181614;
    overflow: hidden;
}

.media-tabs-capsule {
    position: absolute;
    top: 24rpx;
    left: 32rpx;
    z-index: 20;
    display: inline-flex;
    align-items: center;
    padding: 6rpx 8rpx;
    border-radius: 999rpx;
    background: rgba(24, 22, 20, 0.65);
    border: 1rpx solid rgba(255, 253, 248, 0.22);
    backdrop-filter: blur(20rpx);
    -webkit-backdrop-filter: blur(20rpx);
    box-shadow: 0 8rpx 20rpx rgba(0, 0, 0, 0.25);
}

.media-tab-btn {
    display: flex;
    align-items: center;
    gap: 8rpx;
    height: 48rpx;
    padding: 0 20rpx;
    border-radius: 999rpx;
    transition: all 0.25s ease;
}

.media-tab-btn--active {
    background: #d9be82;
}

.media-tab-text {
    font-size: 22rpx;
    font-weight: 800;
    color: #fffdf8;
}

.media-tab-btn--active .media-tab-text {
    color: #181614;
}

.hero-video-box {
    width: 100%;
    height: 600rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #000000;
}

.hero-video-player {
    width: 100%;
    height: 100%;
    display: block;
}

.hero-gallery-box {
    position: relative;
    width: 100%;
    height: 640rpx;
}

.hero-swiper {
    width: 100%;
    height: 100%;
}

.hero-swiper-item {
    width: 100%;
    height: 100%;
}

.hero-swiper-image {
    width: 100%;
    height: 100%;
    display: block;
}

.hero-overlay {
    position: absolute;
    inset: 0;
    pointer-events: none;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    background: linear-gradient(
        180deg,
        rgba(24, 22, 20, 0.2) 0%,
        rgba(24, 22, 20, 0) 40%,
        rgba(24, 22, 20, 0.65) 100%
    );
    padding: 28rpx 32rpx;
    box-sizing: border-box;
}

.hero-overlay__bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    pointer-events: auto;
}

.hero-pill-indicator {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    height: 48rpx;
    padding: 0 20rpx;
    border-radius: 999rpx;
    background: rgba(24, 22, 20, 0.58);
    border: 1rpx solid rgba(255, 253, 248, 0.22);
    backdrop-filter: blur(16rpx);
    -webkit-backdrop-filter: blur(16rpx);
}

.hero-pill-text {
    font-size: 21rpx;
    font-weight: 700;
    color: #fffdf8;
}

.hero-page-badge {
    display: inline-flex;
    align-items: baseline;
    gap: 6rpx;
    height: 48rpx;
    padding: 0 22rpx;
    border-radius: 999rpx;
    background: rgba(24, 22, 20, 0.68);
    border: 1rpx solid rgba(217, 190, 130, 0.45);
    backdrop-filter: blur(16rpx);
    -webkit-backdrop-filter: blur(16rpx);
}

.hero-page-current {
    font-size: 28rpx;
    font-weight: 900;
    color: #d9be82;
    font-family: Playfair Display, Georgia, serif;
}

.hero-page-divider {
    font-size: 20rpx;
    color: rgba(255, 253, 248, 0.45);
}

.hero-page-total {
    font-size: 22rpx;
    font-weight: 700;
    color: rgba(255, 253, 248, 0.85);
}

.detail-body {
    display: flex;
    flex-direction: column;
    gap: 24rpx;
    padding: 0 28rpx;
    margin-top: -32rpx;
    position: relative;
    z-index: 10;
}

.narrative-card {
    border-radius: 40rpx;
    box-shadow: 0 16rpx 40rpx rgba(74, 43, 24, 0.08);
}

.meta-badge-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12rpx;
    margin-bottom: 20rpx;
}

.meta-pill {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    height: 46rpx;
    padding: 0 18rpx;
    border-radius: 999rpx;
    background: #faf6ee;
    border: 1rpx solid rgba(216, 201, 173, 0.7);
}

.meta-pill--ghost {
    background: transparent;
    border-color: rgba(216, 201, 173, 0.4);
}

.meta-pill__text {
    font-size: 22rpx;
    font-weight: 700;
    color: #5e564b;
}

.work-title {
    display: block;
    font-size: 40rpx;
    font-weight: 900;
    line-height: 1.35;
    color: #181614;
    word-break: break-word;
    letter-spacing: 0.5rpx;
}

.work-story-box {
    margin-top: 26rpx;
    padding: 24rpx 24rpx 24rpx 28rpx;
    border-radius: 24rpx;
    background: linear-gradient(135deg, rgba(217, 190, 130, 0.1) 0%, rgba(250, 246, 238, 0.6) 100%);
    border-left: 6rpx solid #c6a15b;
    border-top: 1rpx solid rgba(216, 201, 173, 0.4);
    border-right: 1rpx solid rgba(216, 201, 173, 0.4);
    border-bottom: 1rpx solid rgba(216, 201, 173, 0.4);
}

.work-story-header {
    display: flex;
    align-items: center;
    gap: 8rpx;
    margin-bottom: 10rpx;
}

.work-story-quote-mark {
    font-family: Playfair Display, Georgia, serif;
    font-size: 38rpx;
    line-height: 1;
    font-weight: 900;
    color: #c6a15b;
}

.work-story-title {
    font-size: 24rpx;
    font-weight: 900;
    color: #7d4c35;
    letter-spacing: 1rpx;
}

.work-story-content {
    font-size: 26rpx;
    font-weight: 500;
    line-height: 1.8;
    color: #5e564b;
    white-space: pre-wrap;
    word-break: break-word;
}

.artisan-card {
    border-radius: 36rpx;
    border: 1rpx solid rgba(217, 190, 130, 0.45);
    background: #ffffff;
    box-shadow: 0 12rpx 36rpx rgba(74, 43, 24, 0.06);
}

.artisan-card__header {
    display: flex;
    align-items: center;
    gap: 20rpx;
}

.artisan-avatar-wrap {
    position: relative;
    width: 116rpx;
    height: 116rpx;
    flex-shrink: 0;
}

.artisan-avatar {
    width: 100%;
    height: 100%;
    border-radius: 32rpx;
    border: 3rpx solid #d9be82;
    background: #f8f6f0;
    box-sizing: border-box;
}

.artisan-badge-tag {
    position: absolute;
    bottom: -6rpx;
    right: -6rpx;
    width: 36rpx;
    height: 36rpx;
    border-radius: 50%;
    background: #c6a15b;
    border: 2rpx solid #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
}

.artisan-profile {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.artisan-name-row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12rpx;
}

.artisan-name {
    font-size: 34rpx;
    font-weight: 900;
    color: #181614;
}

.artisan-sn {
    font-size: 22rpx;
    font-weight: 700;
    color: #8c8273;
}

.artisan-entry-link {
    display: flex;
    align-items: center;
    gap: 4rpx;
    flex-shrink: 0;
    padding: 8rpx 14rpx;
    border-radius: 999rpx;
    background: #faf6ee;
}

.artisan-entry-text {
    font-size: 22rpx;
    font-weight: 800;
    color: #c6a15b;
}

.artisan-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12rpx;
    margin-top: 24rpx;
}

.artisan-stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    min-height: 100rpx;
    padding: 14rpx 6rpx;
    border-radius: 20rpx;
    background: #faf6ee;
    border: 1rpx solid rgba(216, 201, 173, 0.45);
    box-sizing: border-box;
}

.artisan-stat-val-wrap {
    display: flex;
    align-items: center;
    gap: 4rpx;
}

.artisan-stat-val {
    font-size: 30rpx;
    font-weight: 900;
    line-height: 1;
    color: #181614;
    font-family: Playfair Display, Georgia, serif;
}

.artisan-stat-val--highlight {
    color: #c6a15b;
}

.artisan-stat-label {
    font-size: 20rpx;
    font-weight: 700;
    line-height: 1;
    color: #8c8273;
}

.artisan-card__footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 24rpx;
    padding-top: 20rpx;
    border-top: 1rpx solid rgba(216, 201, 173, 0.45);
}

.artisan-price-box {
    display: flex;
    align-items: baseline;
    gap: 12rpx;
}

.artisan-price-label {
    font-size: 22rpx;
    font-weight: 700;
    color: #8c8273;
}

.artisan-price-val-group {
    display: flex;
    align-items: baseline;
    gap: 2rpx;
}

.artisan-price-currency {
    font-size: 24rpx;
    font-weight: 900;
    color: #181614;
}

.artisan-price-num {
    font-size: 36rpx;
    font-weight: 900;
    line-height: 1;
    color: #181614;
    font-family: Playfair Display, Georgia, serif;
}

.artisan-price-unit {
    font-size: 20rpx;
    font-weight: 700;
    color: #8c8273;
    margin-left: 4rpx;
}

.artisan-price-negotiable {
    font-size: 30rpx;
    font-weight: 900;
    color: #c6a15b;
}

.section-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24rpx;
}

.section-heading__title-box {
    display: flex;
    align-items: center;
    gap: 12rpx;
}

.section-accent-dot {
    width: 8rpx;
    height: 28rpx;
    border-radius: 999rpx;
    background: #c6a15b;
}

.section-title {
    font-size: 32rpx;
    font-weight: 900;
    color: #181614;
}

.section-subtitle {
    font-size: 22rpx;
    font-weight: 700;
    color: #c6a15b;
}

.gallery-stream-card {
    border-radius: 36rpx;
}

.gallery-stream-list {
    display: flex;
    flex-direction: column;
    gap: 22rpx;
}

.gallery-photo-item {
    position: relative;
    width: 100%;
    border-radius: 24rpx;
    overflow: hidden;
    background: #f8f6f0;
    border: 1rpx solid rgba(216, 201, 173, 0.45);
    box-shadow: 0 8rpx 24rpx rgba(24, 22, 20, 0.04);
}

.gallery-photo-img {
    width: 100%;
    display: block;
    transition: transform 0.25s ease;
}

.gallery-photo-item:active .gallery-photo-img {
    transform: scale(0.985);
}

.gallery-photo-meta {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16rpx 20rpx;
    background: linear-gradient(180deg, transparent 0%, rgba(24, 22, 20, 0.72) 100%);
    pointer-events: none;
}

.photo-index-tag {
    font-size: 20rpx;
    font-weight: 800;
    color: #d9be82;
    letter-spacing: 1rpx;
}

.photo-zoom-hint {
    display: flex;
    align-items: center;
    gap: 6rpx;
    font-size: 20rpx;
    font-weight: 700;
    color: #fffdf8;
}

.related-works-card {
    border-radius: 36rpx;
}

.related-works-scroll {
    width: 100%;
    white-space: nowrap;
}

.related-works-flex {
    display: inline-flex;
    gap: 18rpx;
    padding-bottom: 8rpx;
}

.related-work-card {
    width: 290rpx;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    gap: 10rpx;
}

.related-work-cover-wrap {
    position: relative;
    width: 100%;
    height: 200rpx;
    border-radius: 20rpx;
    overflow: hidden;
    background: #181614;
}

.related-work-cover {
    width: 100%;
    height: 100%;
    display: block;
    transition: transform 0.2s ease;
}

.related-work-card:active .related-work-cover {
    transform: scale(0.97);
}

.related-work-type-badge {
    position: absolute;
    top: 12rpx;
    left: 12rpx;
    padding: 4rpx 12rpx;
    border-radius: 999rpx;
    background: rgba(24, 22, 20, 0.65);
    font-size: 19rpx;
    font-weight: 800;
    color: #fffdf8;
    backdrop-filter: blur(12rpx);
    -webkit-backdrop-filter: blur(12rpx);
}

.related-work-title {
    font-size: 26rpx;
    font-weight: 800;
    color: #181614;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.related-work-meta {
    font-size: 21rpx;
    font-weight: 600;
    color: #8c8273;
}

/* 吸底操作栏 */
.bottom-action-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 100;
    background: rgba(255, 255, 255, 0.94);
    border-top: 1rpx solid rgba(216, 201, 173, 0.6);
    backdrop-filter: blur(28rpx);
    -webkit-backdrop-filter: blur(28rpx);
    padding: 16rpx 28rpx calc(16rpx + env(safe-area-inset-bottom));
    box-shadow: 0 -8rpx 32rpx rgba(24, 22, 20, 0.06);
    box-sizing: border-box;
}

.bottom-action-bar__inner {
    display: flex;
    align-items: center;
    gap: 20rpx;
    height: 96rpx;
}

.bottom-action-tools {
    display: flex;
    align-items: center;
    gap: 24rpx;
}

.action-tool-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4rpx;
    min-width: 72rpx;
    cursor: pointer;
}

.action-tool-item--share {
    border: none;
    background: transparent;
    padding: 0;
    margin: 0;
    line-height: normal;
    outline: none;
}

.action-tool-item--share::after {
    display: none;
}

.action-tool-label {
    font-size: 20rpx;
    font-weight: 700;
    color: #5e564b;
    line-height: 1;
}

.action-tool-label--favorited {
    color: #c6a15b;
}

.heart-pulse-anim {
    animation: heartPulse 0.4s ease-in-out;
}

@keyframes heartPulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.3);
    }
    100% {
        transform: scale(1);
    }
}

.bottom-action-main {
    flex: 1;
    min-width: 0;
}

.primary-book-btn {
    width: 100%;
    height: 88rpx;
    border-radius: 999rpx;
    background: linear-gradient(135deg, #e6ca92 0%, #c6a15b 100%);
    box-shadow: 0 10rpx 24rpx rgba(198, 161, 91, 0.35);
    border: none;
    outline: none;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 0 20rpx;
    line-height: 1.15;
    box-sizing: border-box;
}

.primary-book-btn::after {
    display: none;
}

.primary-book-btn:active {
    opacity: 0.92;
    transform: scale(0.985);
}

.primary-book-btn__title {
    font-size: 30rpx;
    font-weight: 900;
    color: #181614;
    letter-spacing: 1rpx;
}

.primary-book-btn__sub {
    font-size: 19rpx;
    font-weight: 700;
    color: rgba(24, 22, 20, 0.7);
}

.loading-state-wrapper,
.error-state-wrapper {
    min-height: 80vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40rpx;
    box-sizing: border-box;
}
</style>
