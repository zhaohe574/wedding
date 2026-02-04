<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
        <!-- #endif -->
    </page-meta>
    <view class="dynamic-page">
        <!-- 话题筛选提示 -->
        <view v-if="currentTag" class="tag-filter-banner">
            <view class="tag-filter-content">
                <tn-icon name="fire" size="32" color="#FFFFFF" />
                <text class="tag-text">话题：{{ currentTag }}</text>
                <view class="clear-filter" @click="clearTagFilter">
                    <tn-icon name="close" size="32" color="#FFFFFF" />
                </view>
            </view>
        </view>
        
        <!-- 类型筛选标签 -->
        <view class="type-tabs-wrapper">
            <view class="tabs-container">
                <tn-tabs 
                    v-model="currentTypeIndex" 
                    :scroll="false" 
                    height="70rpx" 
                    class="tabs-main"
                    :active-color="$theme.primaryColor"
                    :bar-color="$theme.primaryColor"
                >
                    <tn-tabs-item
                        v-for="(tab, index) in typeTabs"
                        :key="index"
                        :title="tab.label"
                    />
                </tn-tabs>
                <view class="sort-btn" @click="toggleSortMenu">
                    <tn-icon name="sort" size="32" :color="$theme.primaryColor" />
                    <text class="sort-text">{{ currentSortLabel }}</text>
                    <tn-icon :name="showSortMenu ? 'up' : 'down'" size="24" :color="$theme.primaryColor" />
                </view>
            </view>
            
            <!-- 排序菜单 -->
            <view v-if="showSortMenu" class="sort-menu">
                <view
                    v-for="(item, index) in sortOptions"
                    :key="index"
                    class="sort-menu-item"
                    :class="{ 'sort-menu-item-active': currentSort === item.value }"
                    @click="selectSort(item.value)"
                >
                    <text class="sort-menu-text">{{ item.label }}</text>
                    <tn-icon
                        v-if="currentSort === item.value"
                        name="check"
                        size="32"
                        :color="$theme.primaryColor"
                    />
                </view>
            </view>
        </view>

        <!-- 动态列表 -->
        <view class="dynamic-list-wrapper">
            <!-- 加载中 -->
            <view v-if="loading && dynamics.length === 0" class="loading-state">
                <tn-loading size="60" mode="flower" />
                <text class="loading-text">加载中...</text>
            </view>
            
            <!-- 空状态 -->
            <view v-else-if="dynamics.length === 0" class="empty-state">
                <view class="empty-icon-wrapper">
                    <tn-icon name="file-text" size="120" color="#d1d5db" />
                </view>
                <text class="empty-title">暂无动态</text>
                <text class="empty-subtitle">快来发布第一条动态吧~</text>
            </view>
            
            <!-- 动态列表 - 使用DynamicCard组件 -->
            <view v-else class="dynamic-list">
                <dynamic-card
                    v-for="item in dynamics"
                    :key="item.id"
                    :dynamic="item"
                    @click="goDetail"
                    @like="handleLike"
                    @comment="goDetail"
                    @share="handleShare"
                    @follow="handleFollow"
                />

                <!-- 加载更多提示 -->
                <view v-if="hasMore" class="load-more">
                    <text v-if="loading" class="load-more-text">加载中...</text>
                    <text v-else class="load-more-text load-more-clickable" @click="loadMore">加载更多</text>
                </view>
                <view v-else-if="dynamics.length > 0" class="load-more">
                    <text class="load-more-text">没有更多了</text>
                </view>
            </view>
        </view>

        <tabbar />
    </view>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { onLoad, onShow, onReachBottom, onShareAppMessage } from '@dcloudio/uni-app'
import { useUserStore } from '@/stores/user'
import { useThemeStore } from '@/stores/theme'
import {
    getDynamicList,
    likeDynamic,
    collectDynamic,
    toggleFollow
} from '@/api/dynamic'
import DynamicCard from '@/components/business/DynamicCard.vue'
import { mapDynamicItem } from '@/utils/dynamic'

const userStore = useUserStore()
const themeStore = useThemeStore()
const userId = computed(() => userStore.userInfo?.id)

const typeTabs = [
    { label: '全部', value: '' },
    { label: '图文', value: 1 },
    { label: '视频', value: 2 },
    { label: '案例', value: 3 },
    { label: '活动', value: 4 }
]

const sortOptions = [
    { label: '最新发布', value: 'latest' },
    { label: '最多点赞', value: 'like' },
    { label: '最多评论', value: 'comment' },
    { label: '最多浏览', value: 'view' }
]

const currentTypeIndex = ref(0)
const currentType = computed(() => typeTabs[currentTypeIndex.value].value)
const currentTag = ref('') // 当前筛选的标签
const currentSort = ref('latest') // 当前排序方式
const showSortMenu = ref(false) // 是否显示排序菜单
const currentSortLabel = computed(() => {
    const option = sortOptions.find(item => item.value === currentSort.value)
    return option?.label || '最新发布'
})
const dynamics = ref<any[]>([])
const loading = ref(false)
const page = ref(1)
const hasMore = ref(true)

const fetchDynamics = async (refresh = false) => {
    if (loading.value) return
    loading.value = true

    try {
        if (refresh) {
            page.value = 1
            dynamics.value = []
        }

        const params: any = { page: page.value, page_size: 10 }
        if (currentType.value !== '') {
            params.dynamic_type = currentType.value
        }
        // 添加标签筛选参数
        if (currentTag.value) {
            params.tag = currentTag.value
        }
        // 添加排序参数
        if (currentSort.value) {
            params.sort = currentSort.value
        }

        const res = await getDynamicList(params)
        const list = (res.data || []).map(mapDynamicItem)

        if (refresh) {
            dynamics.value = list
        } else {
            dynamics.value.push(...list)
        }

        hasMore.value = list.length === 10
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

const loadMore = () => {
    if (hasMore.value && !loading.value) {
        page.value++
        fetchDynamics()
    }
}

const goDetail = (dynamic: any) => {
    // 处理不同的参数类型
    let id: number | undefined
    
    if (typeof dynamic === 'number') {
        id = dynamic
    } else if (dynamic && typeof dynamic === 'object') {
        id = dynamic.id
    }
    
    if (!id) {
        // 静默处理，不输出错误日志
        return
    }
    
    uni.navigateTo({ url: `/pages/dynamic_detail/dynamic_detail?id=${id}` })
}

const goPublish = () => {
    // 禁用用户发布动态功能
    uni.showToast({ 
        title: '动态发布功能已关闭，请联系管理员', 
        icon: 'none',
        duration: 2000
    })
    return
    
    // 以下代码已禁用
    // if (!userStore.isLogin) {
    //     uni.navigateTo({ url: '/pages/login/login' })
    //     return
    // }
    // uni.navigateTo({ url: '/pages/dynamic_publish/dynamic_publish' })
}

const handleLike = async (dynamic: any) => {
    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    try {
        await likeDynamic({ id: dynamic.id })
        dynamic.isLiked = !dynamic.isLiked
        dynamic.likeCount += dynamic.isLiked ? 1 : -1
    } catch (e: any) {
        uni.showToast({ title: e.message || '操作失败', icon: 'none' })
    }
}

const handleCollect = async (item: any) => {
    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    try {
        await collectDynamic({ id: item.id })
        item.is_collected = !item.is_collected
        item.collect_count += item.is_collected ? 1 : -1
        uni.showToast({ title: item.is_collected ? '收藏成功' : '取消收藏', icon: 'none' })
    } catch (e: any) {
        uni.showToast({ title: e.message || '操作失败', icon: 'none' })
    }
}

const handleFollow = async (userId: number) => {
    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    try {
        // 找到对应的动态并更新
        const dynamic = dynamics.value.find(d => d.user.id === userId)
        if (!dynamic) return
        
        await toggleFollow({
            follow_type: 1, // 假设类型为1
            follow_id: userId
        })
        dynamic.user.isFollowed = true
        uni.showToast({ title: '关注成功' })
    } catch (e: any) {
        uni.showToast({ title: e.message || '操作失败', icon: 'none' })
    }
}

const handleShare = (dynamic: any) => {
    // 调用小程序分享
    uni.showToast({ title: '分享功能开发中', icon: 'none' })
}

const previewImage = (images: string[], current: number) => {
    uni.previewImage({
        urls: images,
        current: current
    })
}

// 清除标签筛选
const clearTagFilter = () => {
    currentTag.value = ''
    fetchDynamics(true)
}

// 切换排序菜单
const toggleSortMenu = () => {
    showSortMenu.value = !showSortMenu.value
}

// 选择排序方式
const selectSort = (sort: string) => {
    currentSort.value = sort
    showSortMenu.value = false
    fetchDynamics(true)
}

// 监听tabs切换
watch(currentTypeIndex, () => {
    fetchDynamics(true)
})

onLoad((options: any) => {
    // 接收标签参数
    if (options.tag) {
        currentTag.value = decodeURIComponent(options.tag)
    }
})

onShow(() => {
    fetchDynamics(true)
})

onReachBottom(() => {
    loadMore()
})

onShareAppMessage((res) => {
    return {
        title: '婚庆服务动态广场',
        path: '/pages/dynamic/dynamic'
    }
})
</script>

<style lang="scss" scoped>
.dynamic-page {
    min-height: 100vh;
    background: linear-gradient(180deg, var(--color-primary-light-9, #FAF5FF) 0%, #F5F5F5 100%);
    padding-bottom: calc(120rpx + env(safe-area-inset-bottom));
}

/* 话题筛选提示横幅 */
.tag-filter-banner {
    background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-light-3) 100%);
    padding: 16rpx 24rpx;
    margin-bottom: 16rpx;
    box-shadow: 0 2rpx 12rpx rgba(124, 58, 237, 0.2);
}

.tag-filter-content {
    display: flex;
    align-items: center;
    gap: 16rpx; // 使用sm间距
}

.tag-text {
    flex: 1;
    font-size: 28rpx;
    font-weight: 600;
    color: #FFFFFF;
}

.clear-filter {
    width: 48rpx;
    height: 48rpx;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 24rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    
    &:active {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(0.98);
    }
}

/* 类型筛选标签 */
.type-tabs-wrapper {
    background: #ffffff;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.08);
    position: sticky;
    top: 0;
    z-index: 10;
    margin-bottom: 16rpx;
}

.tabs-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-right: 24rpx; // 使用md间距
}

.tabs-main {
    flex: 1;
}

.sort-btn {
    display: flex;
    align-items: center;
    gap: 8rpx; // 使用xs间距
    padding: 12rpx 16rpx; // 使用sm间距
    background: var(--color-primary-light-9);
    border-radius: 32rpx;
    transition: all 0.2s ease;
    
    &:active {
        transform: scale(0.98);
        background: var(--color-primary-light-7);
    }
}

.sort-text {
    font-size: 24rpx;
    color: var(--color-primary);
    font-weight: 500;
}

/* 排序菜单 */
.sort-menu {
    background: #ffffff;
    border-top: 1rpx solid var(--color-light);
    padding: 16rpx 0; // 使用sm间距
    animation: slideDown 0.2s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10rpx);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.sort-menu-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24rpx 32rpx; // 使用md和lg间距
    transition: all 0.2s ease;
    
    &:active {
        background: var(--color-primary-light-9);
    }
}

.sort-menu-item-active {
    background: linear-gradient(90deg, var(--color-primary-light-9) 0%, #ffffff 100%);
}

.sort-menu-text {
    font-size: 28rpx;
    color: var(--color-content);
    
    .sort-menu-item-active & {
        color: var(--color-primary);
        font-weight: 600;
    }
}

/* 动态列表容器 */
.dynamic-list-wrapper {
    padding: 0 24rpx; // 使用md间距
}

/* 加载状态 */
.loading-state {
    padding: 160rpx 0;
    text-align: center;
}

.loading-text {
    display: block;
    margin-top: 32rpx; // 使用lg间距
    font-size: 28rpx;
    color: var(--color-muted);
}

/* 空状态 */
.empty-state {
    padding: 160rpx 0;
    text-align: center;
}

.empty-icon-wrapper {
    width: 256rpx;
    height: 256rpx;
    margin: 0 auto 32rpx; // 使用lg间距
    display: flex;
    align-items: center;
    justify-content: center;
}

.empty-title {
    display: block;
    font-size: 32rpx;
    color: var(--color-muted);
}

.empty-subtitle {
    display: block;
    margin-top: 16rpx; // 使用sm间距
    font-size: 24rpx;
    color: var(--color-disabled);
}

/* 动态列表 */
.dynamic-list {
    display: flex;
    flex-direction: column;
    gap: 16rpx; // 使用sm间距
}

/* 加载更多 */
.load-more {
    padding: 32rpx 0; // 使用lg间距
    text-align: center;
}

.load-more-text {
    font-size: 28rpx;
    color: var(--color-muted);
}

.load-more-clickable {
    color: var(--color-primary);
    font-weight: 500;
}
</style>
