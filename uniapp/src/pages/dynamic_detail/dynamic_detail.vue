<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="动态详情"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>
    <view class="dynamic-detail" v-if="detail">
        <!-- 主体内容 -->
        <scroll-view 
            scroll-y 
            class="content-scroll"
            @scrolltolower="loadMoreComments"
        >
            <!-- 用户信息 -->
            <view class="bg-white px-4 py-4">
                <view class="flex items-start">
                    <image 
                        :src="detail.user_avatar || '/static/images/default-avatar.png'" 
                        class="w-12 h-12 rounded-full mr-3"
                        mode="aspectFill"
                    />
                    <view class="flex-1">
                        <view class="flex items-center justify-between">
                            <view>
                                <view class="flex items-center">
                                    <text class="font-medium">{{ detail.user_nickname }}</text>
                                    <view v-if="detail.user_type === 2" class="ml-2 px-2 py-0.5 bg-primary/10 text-primary text-xs rounded">
                                        服务人员
                                    </view>
                                    <view v-if="detail.user_type === 3" class="ml-2 px-2 py-0.5 bg-blue-100 text-blue-500 text-xs rounded">
                                        官方
                                    </view>
                                </view>
                                <text class="text-xs text-gray-400 mt-1">{{ detail.create_time }}</text>
                            </view>
                            <view 
                                v-if="detail.user_id !== userId"
                                class="px-4 py-1.5 text-sm rounded-full"
                                :class="detail.is_followed ? 'bg-gray-100 text-gray-500' : 'bg-primary text-white'"
                                @click="handleFollow"
                            >
                                {{ detail.is_followed ? '已关注' : '+ 关注' }}
                            </view>
                        </view>
                    </view>
                </view>

                <!-- 内容 -->
                <view class="mt-4">
                    <!-- 类型标签 -->
                    <view class="mb-2" v-if="detail.dynamic_type">
                        <text class="text-xs px-2 py-0.5 rounded" :class="getTypeClass(detail.dynamic_type)">
                            {{ getTypeText(detail.dynamic_type) }}
                        </text>
                    </view>
                    <!-- 文字内容 -->
                    <view class="text-base text-gray-800 leading-7 whitespace-pre-wrap">
                        {{ detail.content }}
                    </view>
                    
                    <!-- 图片 -->
                    <view v-if="detail.images && detail.images.length > 0" class="mt-4 grid gap-2" :class="getImageGridClass(detail.images.length)">
                        <image 
                            v-for="(img, idx) in detail.images" 
                            :key="idx"
                            :src="img"
                            class="w-full aspect-square rounded"
                            mode="aspectFill"
                            @click="previewImage(detail.images, idx)"
                        />
                    </view>

                    <!-- 视频 -->
                    <view v-if="detail.video" class="mt-4 rounded overflow-hidden">
                        <video 
                            :src="detail.video" 
                            class="w-full" 
                            :poster="detail.video_cover"
                            controls
                            object-fit="contain"
                        />
                    </view>
                </view>

                <!-- 互动数据 -->
                <view class="mt-4 pt-4 border-t border-gray-100 flex items-center text-gray-400 text-sm">
                    <view class="flex items-center mr-6">
                        <u-icon name="eye" size="32" class="mr-1" />
                        <text>{{ detail.view_count }} 浏览</text>
                    </view>
                    <view class="flex items-center mr-6">
                        <u-icon name="heart" size="32" class="mr-1" />
                        <text>{{ detail.like_count }} 点赞</text>
                    </view>
                    <view class="flex items-center">
                        <u-icon name="star" size="32" class="mr-1" />
                        <text>{{ detail.collect_count }} 收藏</text>
                    </view>
                </view>
            </view>

            <!-- 评论区 -->
            <view class="mt-2 bg-white">
                <view class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <text class="font-medium">评论 ({{ detail.comment_count }})</text>
                    <view class="flex items-center text-sm text-gray-400">
                        <text 
                            :class="commentSort === 'hot' ? 'text-primary' : ''"
                            @click="commentSort = 'hot'; fetchComments(true)"
                        >最热</text>
                        <text class="mx-2">|</text>
                        <text 
                            :class="commentSort === 'new' ? 'text-primary' : ''"
                            @click="commentSort = 'new'; fetchComments(true)"
                        >最新</text>
                    </view>
                </view>

                <!-- 评论列表 -->
                <view v-if="comments.length === 0" class="py-10 text-center text-gray-400">
                    暂无评论，快来抢沙发吧~
                </view>
                <view v-else class="px-4">
                    <view 
                        v-for="comment in comments" 
                        :key="comment.id"
                        class="py-4 border-b border-gray-50"
                    >
                        <view class="flex">
                            <image 
                                :src="comment.user_avatar || '/static/images/default-avatar.png'" 
                                class="w-9 h-9 rounded-full mr-3"
                                mode="aspectFill"
                            />
                            <view class="flex-1">
                                <view class="flex items-center justify-between">
                                    <text class="text-sm font-medium">{{ comment.user_nickname }}</text>
                                    <view 
                                        class="flex items-center text-xs"
                                        :class="comment.is_liked ? 'text-red-500' : 'text-gray-400'"
                                        @click="handleLikeComment(comment)"
                                    >
                                        <u-icon :name="comment.is_liked ? 'heart-fill' : 'heart'" size="28" />
                                        <text class="ml-1">{{ comment.like_count || '' }}</text>
                                    </view>
                                </view>
                                <view class="mt-1 text-sm text-gray-700">{{ comment.content }}</view>
                                <view class="mt-2 flex items-center text-xs text-gray-400">
                                    <text>{{ comment.create_time }}</text>
                                    <text class="ml-4" @click="replyComment(comment)">回复</text>
                                    <text v-if="comment.user_id === userId" class="ml-4 text-red-400" @click="deleteCommentItem(comment)">删除</text>
                                </view>

                                <!-- 回复列表 -->
                                <view v-if="comment.replies && comment.replies.length > 0" class="mt-3 bg-gray-50 rounded p-3">
                                    <view 
                                        v-for="reply in comment.replies" 
                                        :key="reply.id"
                                        class="mb-2 last:mb-0"
                                    >
                                        <text class="text-sm">
                                            <text class="text-primary">{{ reply.user_nickname }}</text>
                                            <text v-if="reply.reply_user_nickname" class="text-gray-400"> 回复 </text>
                                            <text v-if="reply.reply_user_nickname" class="text-primary">{{ reply.reply_user_nickname }}</text>
                                            <text class="text-gray-700">：{{ reply.content }}</text>
                                        </text>
                                        <view class="mt-1 flex items-center text-xs text-gray-400">
                                            <text>{{ reply.create_time }}</text>
                                            <text class="ml-4" @click="replyComment(reply, comment)">回复</text>
                                        </view>
                                    </view>
                                    <view 
                                        v-if="comment.reply_count > comment.replies.length"
                                        class="text-xs text-primary mt-2"
                                        @click="loadMoreReplies(comment)"
                                    >
                                        查看全部{{ comment.reply_count }}条回复 &gt;
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>

                    <!-- 加载更多评论 -->
                    <view v-if="commentHasMore" class="py-4 text-center text-gray-400 text-sm">
                        <text v-if="commentLoading">加载中...</text>
                        <text v-else>上拉加载更多</text>
                    </view>
                </view>
            </view>
            
            <!-- 底部占位 -->
            <view class="h-32"></view>
        </scroll-view>

        <!-- 底部操作栏 -->
        <view class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-100 px-4 py-2 flex items-center" style="padding-bottom: calc(16rpx + env(safe-area-inset-bottom))">
            <view 
                class="flex-1 bg-gray-100 rounded-full px-4 py-2 text-sm text-gray-400 mr-3"
                @click="showCommentInput"
            >
                说点什么...
            </view>
            <view class="flex items-center gap-4">
                <view 
                    class="flex flex-col items-center"
                    :class="detail.is_liked ? 'text-red-500' : 'text-gray-500'"
                    @click="handleLike"
                >
                    <u-icon :name="detail.is_liked ? 'heart-fill' : 'heart'" size="44" />
                    <text class="text-xs mt-0.5">{{ formatCount(detail.like_count) }}</text>
                </view>
                <view 
                    class="flex flex-col items-center"
                    :class="detail.is_collected ? 'text-yellow-500' : 'text-gray-500'"
                    @click="handleCollect"
                >
                    <u-icon :name="detail.is_collected ? 'star-fill' : 'star'" size="44" />
                    <text class="text-xs mt-0.5">{{ formatCount(detail.collect_count) }}</text>
                </view>
                <view class="flex flex-col items-center text-gray-500">
                    <u-icon name="chat" size="44" />
                    <text class="text-xs mt-0.5">{{ formatCount(detail.comment_count) }}</text>
                </view>
                <button class="share-btn flex flex-col items-center text-gray-500" open-type="share">
                    <u-icon name="share" size="44" />
                    <text class="text-xs mt-0.5">分享</text>
                </button>
            </view>
        </view>

        <!-- 评论输入弹窗 -->
        <u-popup v-model="showComment" mode="bottom" :safe-area-inset-bottom="true">
            <view class="p-4">
                <view class="text-center text-sm text-gray-500 mb-3">
                    {{ replyTo ? `回复 ${replyTo.user_nickname}` : '发表评论' }}
                </view>
                <textarea 
                    v-model="commentContent"
                    class="w-full bg-gray-100 rounded-lg p-3 text-sm"
                    :placeholder="replyTo ? `回复 ${replyTo.user_nickname}...` : '说点什么...'"
                    :maxlength="500"
                    :auto-height="true"
                    :focus="showComment"
                    style="min-height: 120rpx;"
                />
                <view class="flex justify-between items-center mt-3">
                    <text class="text-xs text-gray-400">{{ commentContent.length }}/500</text>
                    <button 
                        class="px-6 py-2 bg-primary text-white text-sm rounded-full"
                        :disabled="!commentContent.trim()"
                        @click="submitComment"
                    >
                        发送
                    </button>
                </view>
            </view>
        </u-popup>
    </view>

    <!-- 加载中 -->
    <view v-else class="flex items-center justify-center h-screen">
        <text class="text-gray-400">加载中...</text>
    </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { onLoad, onShareAppMessage } from '@dcloudio/uni-app'
import { useUserStore } from '@/stores/user'
import { 
    getDynamicDetail, 
    likeDynamic, 
    collectDynamic, 
    toggleFollow,
    getCommentList,
    addComment,
    deleteComment,
    likeComment
} from '@/api/dynamic'

const userStore = useUserStore()
const userId = computed(() => userStore.userInfo?.id)

const dynamicId = ref(0)
const detail = ref<any>(null)
const comments = ref<any[]>([])
const commentSort = ref('hot')
const commentPage = ref(1)
const commentHasMore = ref(true)
const commentLoading = ref(false)

const showComment = ref(false)
const commentContent = ref('')
const replyTo = ref<any>(null)
const parentComment = ref<any>(null)

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
    if (!count) return 0
    if (count >= 10000) {
        return (count / 10000).toFixed(1) + 'w'
    }
    if (count >= 1000) {
        return (count / 1000).toFixed(1) + 'k'
    }
    return count
}

const fetchDetail = async () => {
    try {
        const res = await getDynamicDetail({ id: dynamicId.value })
        detail.value = res
    } catch (e: any) {
        uni.showToast({ title: e.message || '加载失败', icon: 'none' })
        setTimeout(() => {
            uni.navigateBack()
        }, 1500)
    }
}

const fetchComments = async (refresh = false) => {
    if (commentLoading.value) return
    commentLoading.value = true

    try {
        if (refresh) {
            commentPage.value = 1
            comments.value = []
        }

        const res = await getCommentList({
            dynamic_id: dynamicId.value,
            page: commentPage.value,
            page_size: 20,
            sort: commentSort.value
        })
        
        const list = res.data || []
        if (refresh) {
            comments.value = list
        } else {
            comments.value.push(...list)
        }
        
        commentHasMore.value = list.length === 20
    } catch (e) {
        console.error(e)
    } finally {
        commentLoading.value = false
    }
}

const loadMoreComments = () => {
    if (commentHasMore.value && !commentLoading.value) {
        commentPage.value++
        fetchComments()
    }
}

const loadMoreReplies = async (comment: any) => {
    // TODO: 加载更多回复
}

const handleLike = async () => {
    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    try {
        await likeDynamic({ id: dynamicId.value })
        detail.value.is_liked = !detail.value.is_liked
        detail.value.like_count += detail.value.is_liked ? 1 : -1
    } catch (e: any) {
        uni.showToast({ title: e.message || '操作失败', icon: 'none' })
    }
}

const handleCollect = async () => {
    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    try {
        await collectDynamic({ id: dynamicId.value })
        detail.value.is_collected = !detail.value.is_collected
        detail.value.collect_count += detail.value.is_collected ? 1 : -1
        uni.showToast({ title: detail.value.is_collected ? '收藏成功' : '取消收藏', icon: 'none' })
    } catch (e: any) {
        uni.showToast({ title: e.message || '操作失败', icon: 'none' })
    }
}

const handleFollow = async () => {
    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    try {
        await toggleFollow({ 
            follow_type: detail.value.user_type, 
            follow_id: detail.value.user_id 
        })
        detail.value.is_followed = !detail.value.is_followed
        uni.showToast({ title: detail.value.is_followed ? '关注成功' : '取消关注' })
    } catch (e: any) {
        uni.showToast({ title: e.message || '操作失败', icon: 'none' })
    }
}

const handleLikeComment = async (comment: any) => {
    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    try {
        await likeComment({ id: comment.id })
        comment.is_liked = !comment.is_liked
        comment.like_count = (comment.like_count || 0) + (comment.is_liked ? 1 : -1)
    } catch (e: any) {
        uni.showToast({ title: e.message || '操作失败', icon: 'none' })
    }
}

const showCommentInput = () => {
    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    replyTo.value = null
    parentComment.value = null
    showComment.value = true
}

const replyComment = (comment: any, parent?: any) => {
    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    replyTo.value = comment
    parentComment.value = parent || comment
    showComment.value = true
}

const submitComment = async () => {
    if (!commentContent.value.trim()) return
    
    try {
        const params: any = {
            dynamic_id: dynamicId.value,
            content: commentContent.value.trim()
        }
        
        if (replyTo.value) {
            params.parent_id = parentComment.value.id
            params.reply_user_id = replyTo.value.user_id
        }
        
        await addComment(params)
        
        uni.showToast({ title: '评论成功' })
        commentContent.value = ''
        showComment.value = false
        replyTo.value = null
        parentComment.value = null
        
        // 刷新评论列表
        fetchComments(true)
        detail.value.comment_count++
    } catch (e: any) {
        uni.showToast({ title: e.message || '评论失败', icon: 'none' })
    }
}

const deleteCommentItem = async (comment: any) => {
    const res = await uni.showModal({
        title: '提示',
        content: '确定要删除该评论吗？'
    })
    if (res.confirm) {
        try {
            await deleteComment({ id: comment.id })
            uni.showToast({ title: '删除成功' })
            fetchComments(true)
            detail.value.comment_count--
        } catch (e: any) {
            uni.showToast({ title: e.message || '删除失败', icon: 'none' })
        }
    }
}

const previewImage = (images: string[], current: number) => {
    uni.previewImage({
        urls: images,
        current: current
    })
}

onLoad((options: any) => {
    if (options.id) {
        dynamicId.value = Number(options.id)
        fetchDetail()
        fetchComments(true)
    }
})

onShareAppMessage(() => {
    return {
        title: detail.value?.content?.slice(0, 30) || '精彩动态',
        path: `/pages/dynamic_detail/dynamic_detail?id=${dynamicId.value}`
    }
})
</script>

<style lang="scss" scoped>
.dynamic-detail {
    min-height: 100vh;
    background-color: #f5f5f5;
}

.content-scroll {
    height: calc(100vh - 120rpx - env(safe-area-inset-bottom));
}

.share-btn {
    padding: 0;
    margin: 0;
    background: none;
    border: none;
    line-height: 1;
    
    &::after {
        display: none;
    }
}
</style>
