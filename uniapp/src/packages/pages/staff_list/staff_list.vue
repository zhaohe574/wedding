<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar 
            title="服务人员"
            :front-color="$theme.navColor" 
            :background-color="$theme.navBgColor" 
        />
        <!-- #endif -->
    </page-meta>
    
    <view class="staff-list-page">
        <!-- 人员列表 -->
        <z-paging ref="pagingRef" v-model="staffList" @query="queryList" :auto="false">
            <!-- 顶部固定区域 -->
            <template #top>
                <!-- 搜索栏 -->
                <view class="search-section">
                    <tn-search-box
                        v-model="keyword"
                        placeholder="搜索人员姓名"
                        shape="round"
                        :show-action="true"
                        :search-button-bg-color="$theme.primaryColor"
                        :bg-color="'#F9FAFB'"
                        border
                        height="72"
                        @search="handleSearch"
                        @clear="handleSearch"
                    />
                </view>

                <!-- 分类筛选 -->
                <view class="category-section">
                    <scroll-view scroll-x class="category-scroll" show-scrollbar="false">
                        <view class="category-list">
                            <view
                                v-for="item in categories"
                                :key="item.id"
                                class="category-item"
                                :class="{ active: currentCategoryId === item.id }"
                                :style="currentCategoryId === item.id ? {
                                    background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
                                    color: $theme.btnColor
                                } : {}"
                                @click="handleCategoryChange(item.id)"
                            >
                                {{ item.name }}
                            </view>
                        </view>
                    </scroll-view>
                </view>

                <!-- 排序栏 -->
                <view class="sort-section">
                    <view
                        v-for="item in sortOptions"
                        :key="item.value"
                        class="sort-item"
                        :class="{ active: currentSort === item.value }"
                        @click="handleSortChange(item.value)"
                    >
                        <text :style="currentSort === item.value ? { color: $theme.primaryColor } : {}">
                            {{ item.label }}
                        </text>
                        <tn-icon 
                            v-if="item.value !== 'default'" 
                            :name="getSortIcon(item.value)" 
                            size="24" 
                            :color="currentSort === item.value ? $theme.primaryColor : '#999999'" 
                        />
                    </view>
                </view>
            </template>

            <!-- 人员卡片列表 -->
            <view class="staff-cards">
                <view
                    v-for="item in staffList"
                    :key="item.id"
                    class="staff-card"
                    @click="goToDetail(item.id)"
                >
                    <!-- 卡片头部 -->
                    <view class="card-header">
                        <image
                            class="staff-avatar"
                            :src="item.avatar || '/static/images/default-avatar.png'"
                            mode="aspectFill"
                        />
                        <view class="staff-info">
                            <view class="info-top">
                                <text class="staff-name">{{ item.name }}</text>
                                <view 
                                    class="favorite-btn" 
                                    @click.stop="handleToggleFavorite(item)"
                                >
                                    <tn-icon
                                        :name="item.is_favorite ? 'like-fill' : 'like'"
                                        size="40"
                                        :color="item.is_favorite ? '#FF2C3C' : '#CCCCCC'"
                                    />
                                </view>
                            </view>
                            <view class="staff-category">
                                <tn-icon name="bookmark" size="24" color="#999999" />
                                <text>{{ item.category_name }}</text>
                                <text v-if="item.experience_years" class="experience">
                                    | {{ item.experience_years }}年经验
                                </text>
                            </view>
                            <view class="staff-rating">
                                <view class="rating-stars">
                                    <tn-icon name="star-fill" size="28" color="#FFD700" />
                                    <text :style="{ color: $theme.ctaColor }">{{ item.rating }}</text>
                                </view>
                                <view class="order-count">
                                    <tn-icon name="shopping-bag" size="24" color="#999999" />
                                    <text>{{ item.order_count }}单</text>
                                </view>
                            </view>
                        </view>
                    </view>

                    <!-- 卡片内容 -->
                    <view class="card-content">
                        <text class="staff-profile">{{ item.profile || '暂无简介' }}</text>
                        
                        <!-- 人员标签 -->
                        <view v-if="item.tags && item.tags.length" class="staff-tags">
                            <view 
                                v-for="(tag, index) in item.tags" 
                                :key="index" 
                                class="tag-item"
                            >
                                <text class="tag-text">{{ tag }}</text>
                            </view>
                        </view>
                    </view>

                    <!-- 卡片底部 -->
                    <view class="card-footer">
                        <view class="price-section">
                            <text class="price-label">服务价格</text>
                            <view class="price-amount">
                                <text class="price-symbol" :style="{ color: $theme.primaryColor }">¥</text>
                                <text class="price-value" :style="{ color: $theme.primaryColor }">{{ item.price }}</text>
                                <text class="price-unit">/次</text>
                            </view>
                        </view>
                        <view 
                            class="book-btn"
                            :style="{ 
                                background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`
                            }"
                            @click.stop="goToDetail(item.id)"
                        >
                            <tn-icon name="calendar" size="28" color="#FFFFFF" />
                            <text>立即预约</text>
                        </view>
                    </view>
                </view>
            </view>
        </z-paging>
    </view>
</template>

<script lang="ts" setup>
import { ref, computed } from 'vue'
import { onLoad, onReady } from '@dcloudio/uni-app'
import { getStaffList, toggleStaffFavorite } from '@/api/staff'
import { getServiceCategories } from '@/api/service'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()

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

// 获取排序图标
const getSortIcon = (sortValue: string) => {
    if (sortValue === 'price_asc') return 'arrow-up'
    if (sortValue === 'rating' || sortValue === 'order_count') return 'arrow-down'
    return ''
}

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
    tree.forEach((item) => {
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
})

onReady(() => {
    pagingRef.value?.reload()
})
</script>

<style lang="scss" scoped>
.staff-list-page {
    min-height: 100vh;
    background: #F5F5F5;
}

/* 搜索区域 */
.search-section {
    background: #FFFFFF;
    padding: 24rpx;
    box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.04);
}

/* 分类筛选 */
.category-section {
    background: #FFFFFF;
    padding: 20rpx 24rpx;
    border-bottom: 1rpx solid #F0F0F0;

    .category-scroll {
        white-space: nowrap;
    }

    .category-list {
        display: flex;
        gap: 16rpx;

        .category-item {
            display: inline-block;
            padding: 12rpx 32rpx;
            background: #F5F5F5;
            border-radius: 48rpx;
            font-size: 26rpx;
            color: #666666;
            white-space: nowrap;
            transition: all 0.2s ease;

            &:active {
                transform: scale(0.95);
            }
        }
    }
}

/* 排序栏 */
.sort-section {
    display: flex;
    align-items: center;
    background: #FFFFFF;
    padding: 20rpx 24rpx;
    border-bottom: 1rpx solid #F0F0F0;

    .sort-item {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8rpx;
        font-size: 26rpx;
        color: #666666;
        transition: all 0.2s ease;

        &:active {
            transform: scale(0.95);
        }

        &.active {
            font-weight: 600;
        }
    }
}

/* 人员卡片列表 */
.staff-cards {
    padding: 24rpx;
    display: flex;
    flex-direction: column;
    gap: 24rpx;
}

/* 人员卡片 */
.staff-card {
    background: #FFFFFF;
    border-radius: 24rpx;
    overflow: hidden;
    box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);
    transition: all 0.2s ease;

    &:active {
        transform: translateY(-4rpx);
        box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.1);
    }

    /* 卡片头部 */
    .card-header {
        display: flex;
        padding: 24rpx;
        gap: 20rpx;

        .staff-avatar {
            width: 160rpx;
            height: 160rpx;
            border-radius: 16rpx;
            flex-shrink: 0;
            box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.08);
        }

        .staff-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;

            .info-top {
                display: flex;
                align-items: center;
                justify-content: space-between;

                .staff-name {
                    font-size: 32rpx;
                    font-weight: 700;
                    color: #333333;
                }

                .favorite-btn {
                    width: 56rpx;
                    height: 56rpx;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    flex-shrink: 0;
                    transition: all 0.2s ease;

                    &:active {
                        transform: scale(1.2);
                    }
                }
            }

            .staff-category {
                display: flex;
                align-items: center;
                gap: 8rpx;
                font-size: 24rpx;
                color: #999999;
                margin-top: 12rpx;

                .experience {
                    margin-left: 8rpx;
                }
            }

            .staff-rating {
                display: flex;
                align-items: center;
                gap: 24rpx;
                margin-top: 12rpx;

                .rating-stars {
                    display: flex;
                    align-items: center;
                    gap: 8rpx;

                    text {
                        font-size: 28rpx;
                        font-weight: 600;
                    }
                }

                .order-count {
                    display: flex;
                    align-items: center;
                    gap: 8rpx;
                    font-size: 24rpx;
                    color: #999999;
                }
            }
        }
    }

    /* 卡片内容 */
    .card-content {
        padding: 0 24rpx 24rpx;

        .staff-profile {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            font-size: 26rpx;
            color: #666666;
            line-height: 1.6;
            margin-bottom: 16rpx;
        }
        
        /* 人员标签 */
        .staff-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 12rpx;
            
            .tag-item {
                padding: 6rpx 16rpx;
                background: var(--color-primary-light-9);
                border: 1rpx solid var(--color-primary-light-7);
                border-radius: 12rpx;
                
                .tag-text {
                    font-size: 24rpx;
                    font-weight: 500;
                    color: var(--color-primary);
                }
            }
        }
    }

    /* 卡片底部 */
    .card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 24rpx;
        background: #F9FAFB;
        border-top: 1rpx solid #F0F0F0;

        .price-section {
            flex: 1;

            .price-label {
                display: block;
                font-size: 22rpx;
                color: #999999;
                margin-bottom: 8rpx;
            }

            .price-amount {
                display: flex;
                align-items: baseline;

                .price-symbol {
                    font-size: 28rpx;
                    font-weight: 700;
                    margin-right: 4rpx;
                }

                .price-value {
                    font-size: 44rpx;
                    font-weight: 800;
                    line-height: 1;
                }

                .price-unit {
                    font-size: 24rpx;
                    color: #999999;
                    margin-left: 4rpx;
                }
            }
        }

        .book-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8rpx;
            padding: 20rpx 40rpx;
            border-radius: 48rpx;
            color: #FFFFFF;
            font-size: 28rpx;
            font-weight: 600;
            box-shadow: 0 8rpx 24rpx rgba(124, 58, 237, 0.35);
            transition: all 0.2s ease;

            &:active {
                transform: scale(0.95);
                box-shadow: 0 4rpx 12rpx rgba(124, 58, 237, 0.35);
            }
        }
    }
}
</style>
