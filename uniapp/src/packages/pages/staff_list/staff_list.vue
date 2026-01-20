<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>
    <view class="staff-list">
        <!-- 搜索和筛选 -->
        <view class="filter-bar bg-white px-[24rpx] py-[20rpx]">
            <view class="search-box mb-[20rpx]">
                <u-search
                    v-model="keyword"
                    placeholder="搜索人员姓名"
                    :show-action="false"
                    @search="handleSearch"
                    @clear="handleSearch"
                ></u-search>
            </view>
            <scroll-view scroll-x class="category-scroll">
                <view class="category-list flex">
                    <view
                        v-for="item in categories"
                        :key="item.id"
                        class="category-item"
                        :class="{ active: currentCategoryId === item.id }"
                        @click="handleCategoryChange(item.id)"
                    >
                        {{ item.name }}
                    </view>
                </view>
            </scroll-view>
        </view>

        <!-- 排序栏 -->
        <view class="sort-bar bg-white px-[24rpx] py-[16rpx] flex items-center">
            <view
                v-for="item in sortOptions"
                :key="item.value"
                class="sort-item mr-[40rpx]"
                :class="{ active: currentSort === item.value }"
                @click="handleSortChange(item.value)"
            >
                {{ item.label }}
            </view>
        </view>

        <!-- 人员列表 -->
        <z-paging
            ref="pagingRef"
            v-model="staffList"
            @query="queryList"
            :auto="false"
        >
            <view class="staff-cards px-[24rpx] pt-[20rpx]">
                <view
                    v-for="item in staffList"
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
                                    class="favorite"
                                    @click.stop="handleToggleFavorite(item)"
                                >
                                    <u-icon
                                        :name="item.is_favorite ? 'heart-fill' : 'heart'"
                                        :color="item.is_favorite ? '#ff6b6b' : '#999'"
                                        size="40"
                                    />
                                </view>
                            </view>
                            <view class="category text-[24rpx] text-gray-500 mt-[8rpx]">
                                {{ item.category_name }}
                                <text v-if="item.experience_years"> | {{ item.experience_years }}年经验</text>
                            </view>
                            <view class="profile text-[26rpx] text-gray-600 mt-[12rpx] line-clamp-2">
                                {{ item.profile || '暂无简介' }}
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
        </z-paging>
    </view>
</template>

<script lang="ts" setup>
import { ref, reactive } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { getStaffList, toggleStaffFavorite } from '@/api/staff'
import { getServiceCategories } from '@/api/service'

const pagingRef = ref()
const keyword = ref('')
const staffList = ref<any[]>([])
const categories = ref<any[]>([{ id: '', name: '全部' }])
const currentCategoryId = ref<string | number>('')
const currentSort = ref('default')

const sortOptions = [
    { label: '综合', value: 'default' },
    { label: '价格', value: 'price_asc' },
    { label: '评分', value: 'rating' },
    { label: '销量', value: 'order_count' }
]

// 获取分类
const getCategories = async () => {
    try {
        const data = await getServiceCategories()
        categories.value = [{ id: '', name: '全部' }, ...flattenCategories(data)]
    } catch (e) {
        console.error(e)
    }
}

// 扁平化分类树
const flattenCategories = (tree: any[], result: any[] = []): any[] => {
    tree.forEach(item => {
        result.push({ id: item.id, name: item.name })
        if (item.children && item.children.length) {
            flattenCategories(item.children, result)
        }
    })
    return result
}

// 查询列表
const queryList = async (pageNo: number, pageSize: number) => {
    try {
        const params: any = {
            page_no: pageNo,
            page_size: pageSize,
            sort: currentSort.value
        }
        if (keyword.value) {
            params.keyword = keyword.value
        }
        if (currentCategoryId.value) {
            params.category_id = currentCategoryId.value
        }
        const res = await getStaffList(params)
        pagingRef.value.complete(res.lists)
    } catch (e) {
        pagingRef.value.complete(false)
    }
}

// 搜索
const handleSearch = () => {
    pagingRef.value.reload()
}

// 切换分类
const handleCategoryChange = (id: string | number) => {
    currentCategoryId.value = id
    pagingRef.value.reload()
}

// 切换排序
const handleSortChange = (sort: string) => {
    currentSort.value = sort
    pagingRef.value.reload()
}

// 收藏/取消收藏
const handleToggleFavorite = async (item: any) => {
    try {
        await toggleStaffFavorite({ id: item.id })
        item.is_favorite = !item.is_favorite
        uni.showToast({
            title: item.is_favorite ? '收藏成功' : '已取消收藏',
            icon: 'none'
        })
    } catch (e: any) {
        uni.showToast({ title: e.msg || '操作失败', icon: 'none' })
    }
}

// 跳转详情
const goToDetail = (id: number) => {
    uni.navigateTo({
        url: `/packages/pages/staff_detail/staff_detail?id=${id}`
    })
}

onLoad((options) => {
    if (options?.category_id) {
        currentCategoryId.value = options.category_id
    }
    getCategories()
    pagingRef.value.reload()
})
</script>

<style lang="scss" scoped>
.staff-list {
    min-height: 100vh;
    background: #f5f5f5;
}

.category-scroll {
    white-space: nowrap;
}

.category-list {
    .category-item {
        display: inline-block;
        padding: 12rpx 24rpx;
        margin-right: 16rpx;
        background: #f5f5f5;
        border-radius: 30rpx;
        font-size: 26rpx;
        color: #666;
        
        &.active {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
            color: #fff;
        }
    }
}

.sort-bar {
    border-top: 1rpx solid #eee;
    
    .sort-item {
        font-size: 26rpx;
        color: #666;
        
        &.active {
            color: #ff6b6b;
            font-weight: bold;
        }
    }
}

.staff-card {
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.05);
    
    .avatar {
        width: 160rpx;
        height: 160rpx;
        border-radius: 12rpx;
    }
    
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
}
</style>
