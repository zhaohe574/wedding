<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar 
            title="服务人员详情"
            :front-color="$theme.navColor" 
            :background-color="$theme.navBgColor" 
        />
    </page-meta>
    
    <view class="staff-detail" v-if="staffInfo">
        <!-- 头图轮播 -->
        <staff-banner
            :banner-list="bannerList"
            :config="bannerConfig"
            :default-image="staffInfo.avatar || '/static/images/user/default_avatar.png'"
            @update:expanded="isExpanded = $event"
        />

        <!-- 人员信息卡片 -->
        <view 
            class="info-card"
            :class="{ 'card-overlap': bannerConfig.banner_mode === 1 && !isExpanded }"
        >
            <view class="card-header">
                <image
                    class="staff-avatar"
                    :src="staffInfo.avatar || '/static/images/user/default_avatar.png'"
                    mode="aspectFill"
                />
                <view class="header-info">
                    <view class="name-row">
                        <text class="staff-name">{{ staffInfo.name }}</text>
                        <!-- 认证标识 -->
                        <view v-if="staffInfo.is_verified" class="verified-badge">
                            <tn-icon name="check-circle-fill" size="32" :color="$theme.primaryColor" />
                        </view>
                        <!-- VIP标识 -->
                        <view v-if="staffInfo.is_vip" class="vip-badge">
                            <tn-icon name="vip-fill" size="32" color="#FFD700" />
                        </view>
                        <!-- 推荐标识 -->
                        <view v-if="staffInfo.is_recommend" class="recommend-badge">
                            <text>推荐</text>
                        </view>
                    </view>
                    
                    <view class="category-row">
                        <text class="category-text">{{ staffInfo.category?.name }}</text>
                        <text v-if="staffInfo.experience_years" class="experience-text">
                            {{ staffInfo.experience_years }}年经验
                        </text>
                    </view>
                </view>
            </view>

            <!-- 评分统计 -->
            <view class="stats-row">
                <view class="stat-item">
                    <view class="stat-value">
                        <tn-icon name="star-fill" size="32" :color="$theme.accentColor" />
                        <text :style="{ color: $theme.accentColor }">{{ staffInfo.rating }}</text>
                    </view>
                    <text class="stat-label">评分</text>
                </view>
                <view class="stat-divider"></view>
                <view class="stat-item">
                    <text class="stat-value">{{ staffInfo.order_count }}</text>
                    <text class="stat-label">服务次数</text>
                </view>
                <view class="stat-divider"></view>
                <view class="stat-item">
                    <text class="stat-value">{{ staffInfo.view_count || 0 }}</text>
                    <text class="stat-label">浏览量</text>
                </view>
            </view>

            <!-- 价格和收藏 -->
            <view class="price-row">
                <view class="price-wrapper">
                    <text class="price-label">服务价格</text>
                    <view class="price-amount">
                        <text class="price-symbol">¥</text>
                        <text class="price-value">{{ staffInfo.price }}</text>
                        <text class="price-unit">/次起</text>
                    </view>
                </view>
                <view class="favorite-btn" @click="handleToggleFavorite">
                    <tn-icon
                        :name="staffInfo.is_favorite ? 'star-fill' : 'star'"
                        size="48"
                        :color="staffInfo.is_favorite ? $theme.secondaryColor : '#CCCCCC'"
                    />
                </view>
            </view>
        </view>

        <!-- 标签页切换 -->
        <view class="tabs-section">
            <view class="tabs-wrapper">
                <view 
                    v-for="tab in tabs" 
                    :key="tab.key"
                    class="tab-item"
                    :class="{ active: currentTab === tab.key }"
                    @click="currentTab = tab.key"
                >
                    <text 
                        class="tab-text"
                        :style="currentTab === tab.key ? { color: $theme.primaryColor } : {}"
                    >
                        {{ tab.label }}
                    </text>
                    <view 
                        v-if="currentTab === tab.key" 
                        class="tab-indicator"
                        :style="{ background: $theme.primaryColor }"
                    ></view>
                </view>
            </view>
        </view>

        <!-- 标签页内容 -->
        <view class="tab-content">
            <!-- 简介标签页 -->
            <view v-show="currentTab === 'intro'" class="content-section">
                <!-- 擅长风格 -->
                <view v-if="staffInfo.tags && staffInfo.tags.length" class="content-block">
                    <view class="block-title">擅长风格</view>
                    <view class="tags-wrapper">
                        <view v-for="(tag, index) in staffInfo.tags" :key="index" class="tag-item">
                            <text class="tag-text">{{ tag }}</text>
                        </view>
                    </view>
                </view>

                <!-- 个人简介 -->
                <view class="content-block">
                    <view class="block-title">个人简介</view>
                    <text class="block-content">{{ staffInfo.profile || '暂无简介' }}</text>
                </view>

                <!-- 服务说明 -->
                <view v-if="staffInfo.service_desc" class="content-block">
                    <view class="block-title">服务说明</view>
                    <text class="block-content">{{ staffInfo.service_desc }}</text>
                </view>

                <!-- 服务套餐 -->
                <view v-if="staffInfo.packages && staffInfo.packages.length" class="content-block">
                    <view class="block-title">服务套餐</view>
                    <view class="packages-list">
                        <view 
                            v-for="pkg in staffInfo.packages" 
                            :key="pkg.package_id"
                            class="package-item"
                        >
                            <view class="package-info">
                                <text class="package-name">{{ pkg.package?.name }}</text>
                            </view>
                            <view class="package-price-group">
                                <text v-if="pkg.original_price || pkg.package?.original_price" class="package-original-price">
                                    ¥{{ pkg.original_price || pkg.package?.original_price }}
                                </text>
                                <text class="package-price">¥{{ pkg.custom_price || pkg.price || pkg.package?.price }}</text>
                            </view>
                        </view>
                    </view>
                </view>
            </view>

            <!-- 作品标签页 -->
            <view v-show="currentTab === 'works'" class="content-section">
                <!-- 加载状态 -->
                <view v-if="worksLoading" class="loading-state">
                    <tn-loading mode="circle" />
                </view>
                
                <!-- 作品列表 -->
                <view v-else-if="worksList.length" class="works-grid">
                    <view
                        v-for="work in worksList"
                        :key="work.id"
                        class="work-item"
                        @click="previewImage(work)"
                    >
                        <image 
                            :src="work.cover || work.images?.[0]" 
                            mode="aspectFill"
                            class="work-image"
                            lazy-load
                        />
                        <view class="work-overlay">
                            <text class="work-title">{{ work.title }}</text>
                        </view>
                    </view>
                </view>
                
                <!-- 空状态 -->
                <view v-else class="empty-state">
                    <tn-icon name="image" size="120" color="#CCCCCC" />
                    <text class="empty-text">暂无作品</text>
                </view>
            </view>

            <!-- 评价标签页 -->
            <view v-show="currentTab === 'reviews'" class="content-section">
                <!-- 资质证书 -->
                <view v-if="staffInfo.certificates && staffInfo.certificates.length" class="content-block">
                    <view class="block-title">资质证书</view>
                    <scroll-view scroll-x class="certs-scroll">
                        <view class="certs-wrapper">
                            <view
                                v-for="cert in staffInfo.certificates"
                                :key="cert.id"
                                class="cert-item"
                                @click="previewCert(cert.image)"
                            >
                                <image :src="cert.image" mode="aspectFill" class="cert-image" />
                                <text class="cert-name">{{ cert.name }}</text>
                            </view>
                        </view>
                    </scroll-view>
                </view>

                <!-- 加载状态 -->
                <view v-if="reviewsLoading" class="loading-state">
                    <tn-loading mode="circle" />
                </view>
                
                <!-- 用户评价列表（预留） -->
                <view v-else-if="reviewsList.length" class="reviews-list">
                    <!-- TODO: 评价列表组件 -->
                </view>
                
                <!-- 空状态 -->
                <view v-else class="empty-state">
                    <tn-icon name="chat" size="120" color="#CCCCCC" />
                    <text class="empty-text">暂无评价</text>
                </view>
            </view>
        </view>

        <!-- 底部占位 -->
        <view class="bottom-placeholder"></view>

        <!-- 底部操作栏 -->
        <view class="bottom-bar">
            <view class="action-btns">
                <view class="action-item" @click="handleContact">
                    <tn-icon name="chat" size="48" color="#666666" />
                    <text class="action-text">咨询</text>
                </view>
                <view class="action-item" @click="handleToggleFavorite">
                    <tn-icon
                        :name="staffInfo.is_favorite ? 'star-fill' : 'star'"
                        size="48"
                        :color="staffInfo.is_favorite ? $theme.secondaryColor : '#666666'"
                    />
                    <text class="action-text">{{ staffInfo.is_favorite ? '已收藏' : '收藏' }}</text>
                </view>
            </view>
            <view 
                class="book-btn"
                :style="{ background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)` }"
                @click="handleBook"
            >
                <tn-icon name="calendar" size="32" color="#FFFFFF" />
                <text class="book-text">立即预约</text>
            </view>
        </view>
    </view>

    <!-- 加载状态 -->
    <view v-else class="loading-container">
        <tn-loading mode="circle" />
    </view>
</template>

<script lang="ts" setup>
import { ref, watch } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { getStaffDetail, toggleStaffFavorite, getStaffWorks } from '@/api/staff'
import { useUserStore } from '@/stores/user'
import StaffBanner from '@/packages/components/staff-banner/staff-banner.vue'

const staffId = ref<number>(0)
const staffInfo = ref<any>(null)
const currentTab = ref('intro')
const isExpanded = ref(false)  // 轮播图展开状态
const presetDate = ref('')  // 预设日期

// 轮播图数据
const bannerList = ref<any[]>([])
const bannerConfig = ref({
    banner_mode: 1,
    banner_small_height: 400,
    banner_large_height: 600,
    banner_indicator_style: 1,
    banner_autoplay: 1,
    banner_interval: 3000
})

// 作品列表
const worksList = ref<any[]>([])
const worksLoading = ref(false)

// 评价列表
const reviewsList = ref<any[]>([])
const reviewsLoading = ref(false)

// 标签页配置
const tabs = [
    { key: 'intro', label: '简介' },
    { key: 'works', label: '作品' },
    { key: 'reviews', label: '评价' }
]

// 监听标签页切换
watch(currentTab, (newTab) => {
    if (newTab === 'works' && worksList.value.length === 0) {
        loadWorks()
    } else if (newTab === 'reviews' && reviewsList.value.length === 0) {
        loadReviews()
    }
})

// 获取详情
const getDetail = async () => {
    try {
        const data = await getStaffDetail({ id: staffId.value })
        staffInfo.value = data
        
        // 加载轮播图配置
        if (data.banner_mode !== undefined) {
            bannerConfig.value = {
                banner_mode: data.banner_mode || 1,
                banner_small_height: data.banner_small_height || 400,
                banner_large_height: data.banner_large_height || 600,
                banner_indicator_style: data.banner_indicator_style !== undefined ? data.banner_indicator_style : 1,
                banner_autoplay: data.banner_autoplay !== undefined ? data.banner_autoplay : 1,
                banner_interval: data.banner_interval || 3000
            }
        }
        
        // 加载轮播图列表
        if (data.banners && Array.isArray(data.banners)) {
            bannerList.value = data.banners
        }
    } catch (e: any) {
        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '获取详情失败'
        uni.showToast({ title: errorMsg, icon: 'none' })
    }
}

// 加载作品列表
const loadWorks = async () => {
    if (worksLoading.value) return
    
    worksLoading.value = true
    try {
        const data = await getStaffWorks({ staff_id: staffId.value })
        worksList.value = data || []
    } catch (e: any) {
        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '加载作品失败'
        uni.showToast({ title: errorMsg, icon: 'none' })
    } finally {
        worksLoading.value = false
    }
}

// 加载评价列表（预留）
const loadReviews = async () => {
    if (reviewsLoading.value) return
    
    reviewsLoading.value = true
    try {
        // TODO: 调用评价接口
        // const data = await getStaffReviews({ staff_id: staffId.value })
        // reviewsList.value = data || []
        
        // 暂时模拟空数据
        reviewsList.value = []
    } catch (e: any) {
        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '加载评价失败'
        uni.showToast({ title: errorMsg, icon: 'none' })
    } finally {
        reviewsLoading.value = false
    }
}

// 收藏/取消收藏
const handleToggleFavorite = async () => {
    // 检查登录状态
    const userStore = useUserStore()
    if (!userStore.isLogin) {
        uni.showToast({ title: '请先登录', icon: 'none' })
        setTimeout(() => {
            uni.navigateTo({ url: '/pages/login/login' })
        }, 1500)
        return
    }

    try {
        await toggleStaffFavorite({ id: staffId.value })
        staffInfo.value.is_favorite = !staffInfo.value.is_favorite
        uni.showToast({
            title: staffInfo.value.is_favorite ? '收藏成功' : '已取消收藏',
            icon: 'success'
        })
    } catch (e: any) {
        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '操作失败'
        uni.showToast({ title: errorMsg, icon: 'none' })
    }
}

// 联系咨询
const handleContact = () => {
    uni.navigateTo({
        url: '/pages/customer_service/customer_service'
    })
}

// 立即预约
const handleBook = () => {
    if (!staffId.value || staffId.value === 0) {
        uni.showToast({ title: '服务人员信息错误', icon: 'none' })
        return
    }
    let url = `/packages/pages/schedule_calendar/schedule_calendar?staff_id=${staffId.value}`
    if (presetDate.value) {
        url += `&date=${presetDate.value}`
    }
    uni.navigateTo({ url })
}

// 预览作品图片
const previewImage = (work: any) => {
    const urls = work.images || [work.cover]
    uni.previewImage({
        urls,
        current: urls[0]
    })
}

// 预览证书
const previewCert = (url: string) => {
    uni.previewImage({
        urls: [url]
    })
}

onLoad((options) => {
    if (options?.id) {
        staffId.value = Number(options.id)
        getDetail()
    }
    if (options?.date) {
        presetDate.value = options.date
    }
    if (options?.tab && ['intro', 'works', 'reviews'].includes(options.tab)) {
        currentTab.value = options.tab
    }
})
</script>

<style lang="scss" scoped>
.staff-detail {
    min-height: 100vh;
    background: linear-gradient(180deg, var(--color-primary-light-9) 0%, #F5F5F5 100%);
    padding-bottom: env(safe-area-inset-bottom);
}

/* 加载状态 */
.loading-container {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
}

/* 人员信息卡片 */
.info-card {
    margin: 0 24rpx 24rpx;
    background: #FFFFFF;
    border-radius: 24rpx;
    padding: 32rpx;
    box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.08);
    position: relative;
    z-index: 10;
    
    /* 小图模式：卡片压在轮播图上 */
    &.card-overlap {
        margin-top: -80rpx;
    }
}

.card-header {
    display: flex;
    align-items: flex-start;
    margin-bottom: 24rpx;
    
    .staff-avatar {
        width: 120rpx;
        height: 120rpx;
        border-radius: 16rpx;
        border: 4rpx solid #FFFFFF;
        box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.1);
        flex-shrink: 0;
    }
    
    .header-info {
        flex: 1;
        margin-left: 20rpx;
    }
}

.name-row {
    display: flex;
    align-items: center;
    gap: 12rpx;
    margin-bottom: 12rpx;
    
    .staff-name {
        font-size: 36rpx;
        font-weight: 700;
        color: var(--color-main);
    }
    
    .verified-badge,
    .vip-badge {
        display: flex;
        align-items: center;
    }
    
    .recommend-badge {
        padding: 4rpx 12rpx;
        background: linear-gradient(135deg, var(--color-secondary) 0%, var(--color-secondary) 100%);
        border-radius: 12rpx;
        
        text {
            font-size: 20rpx;
            font-weight: 600;
            color: #FFFFFF;
        }
    }
}

.category-row {
    display: flex;
    align-items: center;
    gap: 12rpx;
    
    .category-text {
        font-size: 26rpx;
        color: var(--color-content);
    }
    
    .experience-text {
        font-size: 26rpx;
        color: var(--color-muted);
        
        &::before {
            content: '|';
            margin-right: 12rpx;
            color: var(--color-light);
        }
    }
}

/* 评分统计 */
.stats-row {
    display: flex;
    align-items: center;
    justify-content: space-around;
    padding: 24rpx 0;
    border-top: 1rpx solid #F0F0F0;
    border-bottom: 1rpx solid #F0F0F0;
    margin-bottom: 24rpx;
    
    .stat-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8rpx;
        
        .stat-value {
            display: flex;
            align-items: center;
            gap: 8rpx;
            font-size: 32rpx;
            font-weight: 700;
            color: var(--color-main);
        }
        
        .stat-label {
            font-size: 24rpx;
            color: var(--color-muted);
        }
    }
    
    .stat-divider {
        width: 1rpx;
        height: 48rpx;
        background: #E5E5E5;
    }
}

/* 价格行 */
.price-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.price-wrapper {
    flex: 1;
    
    .price-label {
        font-size: 24rpx;
        color: var(--color-muted);
        margin-bottom: 8rpx;
    }
    
    .price-amount {
        display: flex;
        align-items: baseline;
        
        .price-symbol {
            font-size: 28rpx;
            font-weight: 600;
            color: var(--color-primary);
            margin-right: 4rpx;
        }
        
        .price-value {
            font-size: 48rpx;
            font-weight: 700;
            color: var(--color-primary);
        }
        
        .price-unit {
            font-size: 24rpx;
            color: var(--color-muted);
            margin-left: 8rpx;
        }
    }
}

.favorite-btn {
    width: 80rpx;
    height: 80rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #F9FAFB;
    border-radius: 40rpx;
    transition: all 0.2s ease;
    
    &:active {
        transform: scale(0.9);
        background: #F0F0F0;
    }
}

/* 标签页切换 */
.tabs-section {
    margin: 24rpx 24rpx 0;
    background: #FFFFFF;
    border-radius: 16rpx;
    padding: 0 24rpx;
}

.tabs-wrapper {
    display: flex;
    align-items: center;
    gap: 48rpx;
}

.tab-item {
    position: relative;
    padding: 24rpx 0;
    cursor: pointer;
    
    .tab-text {
        font-size: 28rpx;
        font-weight: 500;
        color: var(--color-content);
        transition: all 0.2s ease;
    }
    
    &.active .tab-text {
        font-weight: 700;
    }
    
    .tab-indicator {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 4rpx;
        border-radius: 2rpx;
    }
}

/* 标签页内容 */
.tab-content {
    margin: 16rpx 24rpx 0;
}

.content-section {
    background: #FFFFFF;
    border-radius: 16rpx;
    padding: 32rpx;
}

.content-block {
    margin-bottom: 32rpx;
    
    &:last-child {
        margin-bottom: 0;
    }
}

.block-title {
    font-size: 30rpx;
    font-weight: 700;
    color: var(--color-main);
    margin-bottom: 20rpx;
}

.block-content {
    font-size: 28rpx;
    color: var(--color-content);
    line-height: 1.8;
}

/* 标签 */
.tags-wrapper {
    display: flex;
    flex-wrap: wrap;
    gap: 16rpx;
}

.tag-item {
    padding: 8rpx 20rpx;
    background: var(--color-primary-light-9);
    border: 1rpx solid var(--color-primary-light-7);
    border-radius: 16rpx;
    
    .tag-text {
        font-size: 26rpx;
        font-weight: 500;
        color: var(--color-primary);
    }
}

/* 服务套餐 */
.packages-list {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
}

.package-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24rpx;
    background: #F9FAFB;
    border-radius: 12rpx;
    
    .package-info {
        flex: 1;
        
        .package-name {
            font-size: 28rpx;
            font-weight: 600;
            color: var(--color-main);
            margin-bottom: 8rpx;
        }
        
    }
    
    .package-price-group {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 4rpx;
        
        .package-original-price {
            font-size: 24rpx;
            color: var(--color-muted);
            text-decoration: line-through;
        }
        
        .package-price {
            font-size: 32rpx;
            font-weight: 700;
            color: var(--color-primary);
        }
    }
}

/* 作品网格 */
.works-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16rpx;
}

.work-item {
    position: relative;
    border-radius: 12rpx;
    overflow: hidden;
    
    .work-image {
        width: 100%;
        height: 280rpx;
    }
    
    .work-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 16rpx;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.6), transparent);
        
        .work-title {
            font-size: 24rpx;
            color: #FFFFFF;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    }
}

/* 资质证书 */
.certs-scroll {
    white-space: nowrap;
}

.certs-wrapper {
    display: inline-flex;
    gap: 16rpx;
}

.cert-item {
    display: inline-block;
    width: 240rpx;
    
    .cert-image {
        width: 240rpx;
        height: 160rpx;
        border-radius: 12rpx;
    }
    
    .cert-name {
        font-size: 24rpx;
        color: var(--color-content);
        margin-top: 12rpx;
        text-align: center;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
}

/* 空状态 */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 120rpx 0;
    
    .empty-text {
        font-size: 28rpx;
        color: var(--color-muted);
        margin-top: 24rpx;
    }
}

/* 加载状态 */
.loading-state {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 120rpx 0;
}

/* 底部占位 */
.bottom-placeholder {
    height: 180rpx;
}

/* 底部操作栏 */
.bottom-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    display: flex;
    align-items: center;
    gap: 16rpx;
    padding: 20rpx 24rpx;
    padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
    background: #FFFFFF;
    box-shadow: 0 -4rpx 16rpx rgba(0, 0, 0, 0.08);
    z-index: 100;
}

.action-btns {
    display: flex;
    align-items: center;
    gap: 24rpx;
}

.action-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4rpx;
    
    .action-text {
        font-size: 22rpx;
        color: var(--color-content);
    }
}

.book-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12rpx;
    height: 88rpx;
    border-radius: 48rpx;
    box-shadow: 0 4rpx 16rpx rgba(124, 58, 237, 0.3);
    transition: all 0.2s ease;
    
    &:active {
        transform: scale(0.98);
        box-shadow: 0 2rpx 8rpx rgba(124, 58, 237, 0.3);
    }
    
    .book-text {
        font-size: 32rpx;
        font-weight: 600;
        color: #FFFFFF;
    }
}
</style>
