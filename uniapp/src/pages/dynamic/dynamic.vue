<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="动态广场"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>
    <view class="dynamic-page">
        <!-- 类型筛选 -->
        <view class="type-tabs bg-white sticky top-0 z-10">
            <scroll-view scroll-x class="whitespace-nowrap">
                <view 
                    v-for="tab in typeTabs" 
                    :key="tab.value"
                    class="inline-block px-4 py-3 text-sm"
                    :class="currentType === tab.value ? 'text-primary border-b-2 border-primary font-medium' : 'text-gray-500'"
                    @click="changeType(tab.value)"
                >
                    {{ tab.label }}
                </view>
            </scroll-view>
        </view>

        <!-- 热门标签 -->
        <view v-if="hotTags.length > 0" class="bg-white px-3 py-2 mb-2">
            <scroll-view scroll-x class="whitespace-nowrap">
                <view 
                    v-for="tag in hotTags" 
                    :key="tag.id"
                    class="inline-block mr-2 px-3 py-1 text-xs rounded-full"
                    :class="currentTag === tag.name ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600'"
                    @click="filterByTag(tag.name)"
                >
                    #{{ tag.name }}
                </view>
            </scroll-view>
        </view>

        <!-- 动态列表 -->
        <view class="p-3">
            <view v-if="loading && dynamics.length === 0" class="py-20 text-center text-gray-400">
                加载中...
            </view>
            <view v-else-if="dynamics.length === 0" class="py-20 text-center text-gray-400">
                <image src="/static/images/empty.png" class="w-32 h-32 mx-auto mb-4" mode="aspectFit" />
                <text>暂无动态</text>
            </view>
            <view v-else>
                <!-- 动态卡片 -->
                <view 
                    v-for="item in dynamics" 
                    :key="item.id"
                    class="bg-white rounded-lg mb-3 overflow-hidden"
                    @click="goDetail(item.id)"
                >
                    <!-- 用户信息 -->
                    <view class="flex items-center px-4 py-3">
                        <image 
                            :src="item.user_avatar || '/static/images/default-avatar.png'" 
                            class="w-10 h-10 rounded-full mr-3"
                            mode="aspectFill"
                        />
                        <view class="flex-1">
                            <view class="flex items-center">
                                <text class="text-sm font-medium">{{ item.user_nickname }}</text>
                                <view v-if="item.user_type === 2" class="ml-2 px-2 py-0.5 bg-primary/10 text-primary text-xs rounded">
                                    服务人员
                                </view>
                                <view v-if="item.user_type === 3" class="ml-2 px-2 py-0.5 bg-blue-100 text-blue-500 text-xs rounded">
                                    官方
                                </view>
                            </view>
                            <text class="text-xs text-gray-400">{{ item.create_time }}</text>
                        </view>
                        <view 
                            v-if="!item.is_followed && item.user_id !== userId"
                            class="px-3 py-1 text-xs border border-primary text-primary rounded-full"
                            @click.stop="handleFollow(item)"
                        >
                            关注
                        </view>
                    </view>

                    <!-- 内容 -->
                    <view class="px-4 pb-3">
                        <!-- 类型标签 -->
                        <view class="mb-2" v-if="item.dynamic_type">
                            <text class="text-xs px-2 py-0.5 rounded" :class="getTypeClass(item.dynamic_type)">
                                {{ getTypeText(item.dynamic_type) }}
                            </text>
                        </view>
                        <!-- 文字内容 -->
                        <view class="text-sm text-gray-700 leading-6 mb-2 line-clamp-3">
                            {{ item.content }}
                        </view>
                        <!-- 图片 -->
                        <view v-if="item.images && item.images.length > 0" class="grid gap-2" :class="getImageGridClass(item.images.length)">
                            <image 
                                v-for="(img, idx) in item.images.slice(0, 9)" 
                                :key="idx"
                                :src="img"
                                class="w-full aspect-square rounded"
                                mode="aspectFill"
                                @click.stop="previewImage(item.images, idx)"
                            />
                        </view>
                        <!-- 视频 -->
                        <view v-if="item.video" class="relative rounded overflow-hidden">
                            <video 
                                :src="item.video" 
                                class="w-full" 
                                :poster="item.video_cover"
                                object-fit="cover"
                                @click.stop
                            />
                        </view>
                    </view>

                    <!-- 互动数据 -->
                    <view class="px-4 py-3 border-t border-gray-100 flex items-center">
                        <view class="flex-1 flex items-center gap-6">
                            <view class="flex items-center text-gray-400 text-sm">
                                <u-icon name="eye" size="32" class="mr-1" />
                                <text>{{ formatCount(item.view_count) }}</text>
                            </view>
                            <view 
                                class="flex items-center text-sm"
                                :class="item.is_liked ? 'text-red-500' : 'text-gray-400'"
                                @click.stop="handleLike(item)"
                            >
                                <u-icon :name="item.is_liked ? 'heart-fill' : 'heart'" size="32" class="mr-1" />
                                <text>{{ formatCount(item.like_count) }}</text>
                            </view>
                            <view class="flex items-center text-gray-400 text-sm">
                                <u-icon name="chat" size="32" class="mr-1" />
                                <text>{{ formatCount(item.comment_count) }}</text>
                            </view>
                            <view 
                                class="flex items-center text-sm"
                                :class="item.is_collected ? 'text-yellow-500' : 'text-gray-400'"
                                @click.stop="handleCollect(item)"
                            >
                                <u-icon :name="item.is_collected ? 'star-fill' : 'star'" size="32" class="mr-1" />
                                <text>{{ formatCount(item.collect_count) }}</text>
                            </view>
                        </view>
                        <view 
                            class="text-gray-400"
                            @click.stop="handleShare(item)"
                        >
                            <u-icon name="share" size="36" />
                        </view>
                    </view>
                </view>

                <!-- 加载更多 -->
                <view v-if="hasMore" class="py-4 text-center text-gray-400 text-sm">
                    <text v-if="loading">加载中...</text>
                    <text v-else @click="loadMore">加载更多</text>
                </view>
                <view v-else-if="dynamics.length > 0" class="py-4 text-center text-gray-400 text-sm">
                    没有更多了
                </view>
            </view>
        </view>

        <!-- 发布按钮 -->
        <view 
            class="fixed right-4 bottom-24 w-14 h-14 bg-primary rounded-full flex items-center justify-center shadow-lg"
            @click="goPublish"
        >
            <u-icon name="plus" color="#fff" size="48" />
        </view>

        <tabbar />
    </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { onLoad, onShow, onReachBottom, onShareAppMessage } from '@dcloudio/uni-app'
import { useUserStore } from '@/stores/user'
import { 
    getDynamicList, 
    getHotTags, 
    likeDynamic, 
    collectDynamic, 
    toggleFollow 
} from '@/api/dynamic'

const userStore = useUserStore()
const userId = computed(() => userStore.userInfo?.id)

const typeTabs = [
    { label: '全部', value: '' },
    { label: '图文', value: 1 },
    { label: '视频', value: 2 },
    { label: '案例', value: 3 },
    { label: '活动', value: 4 }
]

const currentType = ref<number | string>('')
const currentTag = ref('')
const dynamics = ref<any[]>([])
const hotTags = ref<any[]>([])
const loading = ref(false)
const page = ref(1)
const hasMore = ref(true)

const getTypeClass = (type: number) => {
    const classes: Record<number, string> = {
        1: 'bg-blue-100 text-blue-500',
        2: 'bg-purple-100 text-purple-500',
        3: 'bg-green-100 text-green-500',
        4: 'bg-orange-100 text-orange-500'
    }
    return classes[type] || 'bg-gray-100 text-gray-500'
}

const getTypeText = (type: number) => {
    const texts: Record<number, string> = {
        1: '图文',
        2: '视频',
        3: '案例',
        4: '活动'
    }
    return texts[type] || ''
}

const getImageGridClass = (count: number) => {
    if (count === 1) return 'grid-cols-1'
    if (count === 2 || count === 4) return 'grid-cols-2'
    return 'grid-cols-3'
}

const formatCount = (count: number) => {
    if (count >= 10000) {
        return (count / 10000).toFixed(1) + 'w'
    }
    if (count >= 1000) {
        return (count / 1000).toFixed(1) + 'k'
    }
    return count || 0
}

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
        if (currentTag.value) {
            params.tag = currentTag.value
        }

        const res = await getDynamicList(params)
        const list = res.data || []
        
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

const fetchHotTags = async () => {
    try {
        const res = await getHotTags()
        hotTags.value = res || []
    } catch (e) {
        console.error(e)
    }
}

const changeType = (type: number | string) => {
    currentType.value = type
    currentTag.value = ''
    fetchDynamics(true)
}

const filterByTag = (tag: string) => {
    if (currentTag.value === tag) {
        currentTag.value = ''
    } else {
        currentTag.value = tag
    }
    fetchDynamics(true)
}

const loadMore = () => {
    if (hasMore.value && !loading.value) {
        page.value++
        fetchDynamics()
    }
}

const goDetail = (id: number) => {
    uni.navigateTo({ url: `/pages/dynamic_detail/dynamic_detail?id=${id}` })
}

const goPublish = () => {
    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    uni.navigateTo({ url: '/pages/dynamic_publish/dynamic_publish' })
}

const handleLike = async (item: any) => {
    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    try {
        await likeDynamic({ id: item.id })
        item.is_liked = !item.is_liked
        item.like_count += item.is_liked ? 1 : -1
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

const handleFollow = async (item: any) => {
    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    try {
        await toggleFollow({ 
            follow_type: item.user_type, 
            follow_id: item.user_id 
        })
        item.is_followed = true
        uni.showToast({ title: '关注成功' })
    } catch (e: any) {
        uni.showToast({ title: e.message || '操作失败', icon: 'none' })
    }
}

const handleShare = (item: any) => {
    // 调用小程序分享
}

const previewImage = (images: string[], current: number) => {
    uni.previewImage({
        urls: images,
        current: current
    })
}

onLoad(() => {
    fetchHotTags()
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
    background-color: #f5f5f5;
    padding-bottom: calc(120rpx + env(safe-area-inset-bottom));
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
