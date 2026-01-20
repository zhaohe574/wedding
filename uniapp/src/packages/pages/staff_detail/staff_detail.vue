<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>
    <view class="staff-detail" v-if="staffInfo">
        <!-- 头部信息 -->
        <view class="header bg-white">
            <view class="banner">
                <swiper
                    v-if="staffInfo.works && staffInfo.works.length"
                    class="swiper"
                    indicator-dots
                    autoplay
                    circular
                >
                    <swiper-item v-for="work in staffInfo.works" :key="work.id">
                        <image :src="work.cover || work.images?.[0]" mode="aspectFill" class="swiper-image" />
                    </swiper-item>
                </swiper>
                <image v-else :src="staffInfo.avatar" mode="aspectFill" class="banner-image" />
            </view>
            
            <view class="info-card mx-[24rpx] -mt-[60rpx] relative z-10 bg-white rounded-[16rpx] p-[24rpx]">
                <view class="flex items-start">
                    <image
                        class="avatar"
                        :src="staffInfo.avatar || '/static/images/default-avatar.png'"
                        mode="aspectFill"
                    />
                    <view class="flex-1 ml-[20rpx]">
                        <view class="flex items-center">
                            <text class="name text-[36rpx] font-bold">{{ staffInfo.name }}</text>
                            <view
                                v-if="staffInfo.is_recommend"
                                class="recommend-tag ml-[12rpx]"
                            >
                                推荐
                            </view>
                        </view>
                        <view class="category text-[26rpx] text-gray-500 mt-[8rpx]">
                            {{ staffInfo.category?.name }}
                            <text v-if="staffInfo.experience_years"> | {{ staffInfo.experience_years }}年经验</text>
                        </view>
                        <view class="stats flex items-center mt-[12rpx]">
                            <view class="stat-item">
                                <text class="text-orange-500 text-[32rpx] font-bold">{{ staffInfo.rating }}</text>
                                <text class="text-[22rpx] text-gray-500 ml-[4rpx]">评分</text>
                            </view>
                            <view class="stat-item ml-[40rpx]">
                                <text class="text-[32rpx] font-bold">{{ staffInfo.order_count }}</text>
                                <text class="text-[22rpx] text-gray-500 ml-[4rpx]">服务</text>
                            </view>
                            <view class="stat-item ml-[40rpx]">
                                <text class="text-[32rpx] font-bold">{{ staffInfo.view_count }}</text>
                                <text class="text-[22rpx] text-gray-500 ml-[4rpx]">浏览</text>
                            </view>
                        </view>
                    </view>
                </view>
                <view class="price-box flex items-center justify-between mt-[20rpx] pt-[20rpx] border-t border-gray-100">
                    <view class="price">
                        <text class="text-[24rpx] text-red-500">¥</text>
                        <text class="text-[44rpx] font-bold text-red-500">{{ staffInfo.price }}</text>
                        <text class="text-[24rpx] text-gray-400">/次起</text>
                    </view>
                    <view
                        class="favorite-btn flex items-center"
                        @click="handleToggleFavorite"
                    >
                        <u-icon
                            :name="staffInfo.is_favorite ? 'heart-fill' : 'heart'"
                            :color="staffInfo.is_favorite ? '#ff6b6b' : '#999'"
                            size="36"
                        />
                        <text class="text-[26rpx] ml-[8rpx]" :class="staffInfo.is_favorite ? 'text-red-500' : 'text-gray-500'">
                            {{ staffInfo.is_favorite ? '已收藏' : '收藏' }}
                        </text>
                    </view>
                </view>
            </view>
        </view>

        <!-- 标签 -->
        <view v-if="staffInfo.tags && staffInfo.tags.length" class="tags-section bg-white mt-[20rpx] p-[24rpx]">
            <view class="section-title text-[30rpx] font-bold mb-[16rpx]">擅长风格</view>
            <view class="tags flex flex-wrap">
                <view v-for="(tag, index) in staffInfo.tags" :key="index" class="tag">
                    {{ tag }}
                </view>
            </view>
        </view>

        <!-- 个人简介 -->
        <view class="profile-section bg-white mt-[20rpx] p-[24rpx]">
            <view class="section-title text-[30rpx] font-bold mb-[16rpx]">个人简介</view>
            <view class="profile text-[28rpx] text-gray-600 leading-[1.8]">
                {{ staffInfo.profile || '暂无简介' }}
            </view>
        </view>

        <!-- 服务说明 -->
        <view v-if="staffInfo.service_desc" class="service-section bg-white mt-[20rpx] p-[24rpx]">
            <view class="section-title text-[30rpx] font-bold mb-[16rpx]">服务说明</view>
            <view class="service-desc text-[28rpx] text-gray-600 leading-[1.8]">
                {{ staffInfo.service_desc }}
            </view>
        </view>

        <!-- 服务套餐 -->
        <view v-if="staffInfo.packages && staffInfo.packages.length" class="packages-section bg-white mt-[20rpx] p-[24rpx]">
            <view class="section-title text-[30rpx] font-bold mb-[16rpx]">服务套餐</view>
            <view class="packages">
                <view
                    v-for="pkg in staffInfo.packages"
                    :key="pkg.package_id"
                    class="package-item flex items-center justify-between py-[20rpx] border-b border-gray-100 last:border-0"
                >
                    <view class="flex-1">
                        <view class="text-[28rpx]">{{ pkg.package?.name }}</view>
                        <view class="text-[24rpx] text-gray-500 mt-[8rpx]">
                            {{ pkg.package?.duration }}小时
                        </view>
                    </view>
                    <view class="price text-right">
                        <text class="text-red-500 text-[32rpx] font-bold">¥{{ pkg.price || pkg.package?.price }}</text>
                    </view>
                </view>
            </view>
        </view>

        <!-- 作品展示 -->
        <view v-if="staffInfo.works && staffInfo.works.length" class="works-section bg-white mt-[20rpx] p-[24rpx]">
            <view class="section-title text-[30rpx] font-bold mb-[16rpx]">作品展示</view>
            <view class="works-grid">
                <view
                    v-for="work in staffInfo.works"
                    :key="work.id"
                    class="work-item"
                    @click="previewImage(work)"
                >
                    <image :src="work.cover || work.images?.[0]" mode="aspectFill" />
                    <view class="work-title">{{ work.title }}</view>
                </view>
            </view>
        </view>

        <!-- 资质证书 -->
        <view v-if="staffInfo.certificates && staffInfo.certificates.length" class="certs-section bg-white mt-[20rpx] p-[24rpx] mb-[160rpx]">
            <view class="section-title text-[30rpx] font-bold mb-[16rpx]">资质证书</view>
            <scroll-view scroll-x class="certs-scroll">
                <view class="certs flex">
                    <view
                        v-for="cert in staffInfo.certificates"
                        :key="cert.id"
                        class="cert-item mr-[20rpx]"
                    >
                        <image :src="cert.image" mode="aspectFill" @click="previewCert(cert.image)" />
                        <view class="cert-name">{{ cert.name }}</view>
                    </view>
                </view>
            </scroll-view>
        </view>

        <!-- 底部操作栏 -->
        <view class="bottom-bar fixed bottom-0 left-0 right-0 bg-white flex items-center px-[24rpx] py-[20rpx] border-t border-gray-100">
            <view class="action-item" @click="handleContact">
                <u-icon name="chat" size="44" color="#666" />
                <text>咨询</text>
            </view>
            <view class="action-item" @click="handleToggleFavorite">
                <u-icon
                    :name="staffInfo.is_favorite ? 'heart-fill' : 'heart'"
                    size="44"
                    :color="staffInfo.is_favorite ? '#ff6b6b' : '#666'"
                />
                <text>{{ staffInfo.is_favorite ? '已收藏' : '收藏' }}</text>
            </view>
            <view class="flex-1 ml-[24rpx]">
                <button class="book-btn" @click="handleBook">立即预约</button>
            </view>
        </view>
    </view>
    
    <view v-else class="loading flex items-center justify-center h-screen">
        <u-loading mode="circle" />
    </view>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { getStaffDetail, toggleStaffFavorite } from '@/api/staff'

const staffId = ref<number>(0)
const staffInfo = ref<any>(null)

// 获取详情
const getDetail = async () => {
    try {
        const data = await getStaffDetail({ id: staffId.value })
        staffInfo.value = data
    } catch (e: any) {
        uni.showToast({ title: e.msg || '获取详情失败', icon: 'none' })
    }
}

// 收藏/取消收藏
const handleToggleFavorite = async () => {
    try {
        await toggleStaffFavorite({ id: staffId.value })
        staffInfo.value.is_favorite = !staffInfo.value.is_favorite
        uni.showToast({
            title: staffInfo.value.is_favorite ? '收藏成功' : '已取消收藏',
            icon: 'none'
        })
    } catch (e: any) {
        uni.showToast({ title: e.msg || '操作失败', icon: 'none' })
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
    // TODO: 跳转到预约页面
    uni.showToast({ title: '预约功能开发中', icon: 'none' })
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
})
</script>

<style lang="scss" scoped>
.staff-detail {
    min-height: 100vh;
    background: #f5f5f5;
    padding-bottom: env(safe-area-inset-bottom);
}

.banner {
    height: 400rpx;
    
    .swiper, .banner-image {
        width: 100%;
        height: 100%;
    }
    
    .swiper-image {
        width: 100%;
        height: 100%;
    }
}

.info-card {
    box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.1);
    
    .avatar {
        width: 140rpx;
        height: 140rpx;
        border-radius: 12rpx;
    }
    
    .recommend-tag {
        background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
        color: #fff;
        font-size: 22rpx;
        padding: 4rpx 12rpx;
        border-radius: 20rpx;
    }
}

.tags {
    .tag {
        background: #fff5f5;
        color: #ff6b6b;
        font-size: 24rpx;
        padding: 8rpx 20rpx;
        border-radius: 30rpx;
        margin-right: 16rpx;
        margin-bottom: 12rpx;
    }
}

.works-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16rpx;
    
    .work-item {
        image {
            width: 100%;
            height: 200rpx;
            border-radius: 8rpx;
        }
        
        .work-title {
            font-size: 24rpx;
            color: #666;
            margin-top: 8rpx;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    }
}

.certs-scroll {
    white-space: nowrap;
}

.cert-item {
    display: inline-block;
    width: 240rpx;
    
    image {
        width: 240rpx;
        height: 160rpx;
        border-radius: 8rpx;
    }
    
    .cert-name {
        font-size: 24rpx;
        color: #666;
        margin-top: 8rpx;
        text-align: center;
    }
}

.bottom-bar {
    box-shadow: 0 -2rpx 12rpx rgba(0, 0, 0, 0.05);
    padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
    
    .action-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 0 24rpx;
        
        text {
            font-size: 22rpx;
            color: #666;
            margin-top: 4rpx;
        }
    }
    
    .book-btn {
        background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
        color: #fff;
        font-size: 32rpx;
        font-weight: bold;
        border-radius: 44rpx;
        height: 88rpx;
        line-height: 88rpx;
        border: none;
    }
}
</style>
