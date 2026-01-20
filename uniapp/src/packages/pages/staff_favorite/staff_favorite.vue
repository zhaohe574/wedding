<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>
    <view class="staff-favorite">
        <view v-if="loading" class="loading flex items-center justify-center py-[100rpx]">
            <u-loading mode="circle" />
        </view>
        
        <view v-else-if="!favoriteList.length" class="empty flex flex-col items-center justify-center py-[200rpx]">
            <u-icon name="heart" size="120" color="#ccc" />
            <text class="text-gray-400 mt-[20rpx]">暂无收藏</text>
            <button class="go-btn mt-[40rpx]" @click="goToList">去看看</button>
        </view>
        
        <view v-else class="favorite-list px-[24rpx] pt-[20rpx]">
            <view
                v-for="item in favoriteList"
                :key="item.id"
                class="staff-card bg-white rounded-[16rpx] mb-[20rpx] overflow-hidden"
                @click="goToDetail(item.id)"
            >
                <view class="flex p-[24rpx]">
                    <image
                        class="avatar"
                        :src="item.avatar || '/static/images/default-avatar.png'"
                        mode="aspectFill"
                    />
                    <view class="info flex-1 ml-[20rpx]">
                        <view class="flex items-center justify-between">
                            <text class="name text-[32rpx] font-bold">{{ item.name }}</text>
                            <view
                                class="cancel-btn"
                                @click.stop="handleCancelFavorite(item)"
                            >
                                <u-icon name="heart-fill" color="#ff6b6b" size="40" />
                            </view>
                        </view>
                        <view class="category text-[24rpx] text-gray-500 mt-[8rpx]">
                            {{ item.category_name }}
                        </view>
                        <view class="bottom flex items-center justify-between mt-[16rpx]">
                            <view class="price">
                                <text class="text-[24rpx] text-red-500">¥</text>
                                <text class="text-[36rpx] font-bold text-red-500">{{ item.price }}</text>
                                <text class="text-[24rpx] text-gray-400">/次</text>
                            </view>
                            <view class="stats text-[24rpx] text-gray-500">
                                <text class="text-orange-500">{{ item.rating }}</text>分
                                <text class="mx-[8rpx]">|</text>
                                {{ item.order_count }}单
                            </view>
                        </view>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { getMyFavoriteStaff, toggleStaffFavorite } from '@/api/staff'

const loading = ref(true)
const favoriteList = ref<any[]>([])

// 获取收藏列表
const getFavorites = async () => {
    loading.value = true
    try {
        const data = await getMyFavoriteStaff()
        favoriteList.value = data || []
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

// 取消收藏
const handleCancelFavorite = async (item: any) => {
    try {
        await toggleStaffFavorite({ id: item.id })
        favoriteList.value = favoriteList.value.filter(i => i.id !== item.id)
        uni.showToast({ title: '已取消收藏', icon: 'none' })
    } catch (e: any) {
        uni.showToast({ title: e.msg || '操作失败', icon: 'none' })
    }
}

// 跳转列表
const goToList = () => {
    uni.navigateTo({
        url: '/packages/pages/staff_list/staff_list'
    })
}

// 跳转详情
const goToDetail = (id: number) => {
    uni.navigateTo({
        url: `/packages/pages/staff_detail/staff_detail?id=${id}`
    })
}

onShow(() => {
    getFavorites()
})
</script>

<style lang="scss" scoped>
.staff-favorite {
    min-height: 100vh;
    background: #f5f5f5;
}

.staff-card {
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.05);
    
    .avatar {
        width: 140rpx;
        height: 140rpx;
        border-radius: 12rpx;
    }
}

.go-btn {
    background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
    color: #fff;
    font-size: 28rpx;
    padding: 16rpx 60rpx;
    border-radius: 40rpx;
    border: none;
}
</style>
