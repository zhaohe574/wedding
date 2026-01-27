<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar 
            title="动态详情"
            :front-color="$theme.navColor" 
            :background-color="$theme.navBgColor" 
        />
        <!-- #endif -->
    </page-meta>
    <view class="dynamic-detail" v-if="detail">
        <!-- 主体内容 -->
        <scroll-view scroll-y class="content-scroll">
            <!-- 用户信息卡片 -->
            <view class="user-card">
                <view class="flex items-start">
                    <image
                        :src="detail.user_avatar || '/static/images/user/default_avatar.png'"
                        class="user-avatar"
                        mode="aspectFill"
                    />
                    <view class="flex-1">
                        <view class="flex items-center justify-between">
                            <view>
                                <view class="flex items-center">
                                    <text class="user-name">{{ detail.user_nickname }}</text>
                                    <view v-if="detail.user_type === 2" class="user-badge badge-staff">
                                        服务人员
                                    </view>
                                    <view v-if="detail.user_type === 3" class="user-badge badge-official">
                                        官方
                                    </view>
                                    <view v-if="detail.is_top === 1" class="user-badge badge-top">
                                        置顶
                                    </view>
                                    <view v-if="detail.is_hot === 1" class="user-badge badge-hot">
                                        热门
                                    </view>
                                </view>
                                <text class="user-time">{{ detail.create_time }}</text>
                            </view>
                            <view
                                v-if="detail.user_id !== userId"
                                class="follow-btn"
                                :class="detail.is_followed ? 'follow-btn-active' : 'follow-btn-inactive'"
                                @click="handleFollow"
                            >
                                {{ detail.is_followed ? '已关注' : '+ 关注' }}
                            </view>
                        </view>
                    </view>
                </view>

                <!-- 内容区域 -->
                <view class="content-wrapper">
                    <!-- 类型标签和动态标签 -->
                    <view class="badges-row">
                        <text v-if="detail.dynamic_type" class="type-badge" :class="getTypeClass(detail.dynamic_type)">
                            {{ getTypeText(detail.dynamic_type) }}
                        </text>
                        <view
                            v-for="(tag, tagIdx) in detail.tags"
                            :key="tagIdx"
                            class="tag-item"
                        >
                            <text class="tag-text">#{{ tag }}</text>
                        </view>
                    </view>
                    
                    <!-- 文字内容 -->
                    <view class="content-text">{{ detail.content }}</view>

                    <!-- 图片 -->
                    <view
                        v-if="detail.images && detail.images.length > 0"
                        class="image-grid"
                        :class="getImageGridClass(detail.images.length)"
                    >
                        <image
                            v-for="(img, idx) in detail.images"
                            :key="idx"
                            :src="img"
                            class="grid-image"
                            mode="aspectFill"
                            @click="previewImage(detail.images, idx)"
                        />
                    </view>

                    <!-- 视频 -->
                    <view v-if="detail.video" class="video-wrapper">
                        <video
                            :src="detail.video"
                            class="video-player"
                            :poster="detail.video_cover"
                            controls
                            object-fit="contain"
                        />
                    </view>
                </view>

                <!-- 互动数据 -->
                <view class="stats-bar">
                    <view class="stats-left">
                        <view class="stat-item">
                            <tn-icon name="eye" size="32" />
                            <text>{{ detail.view_count }} 浏览</text>
                        </view>
                        <view class="stat-item">
                            <tn-icon name="heart" size="32" />
                            <text>{{ detail.like_count }} 点赞</text>
                        </view>
                        <view class="stat-item">
                            <tn-icon name="star" size="32" />
                            <text>{{ detail.collect_count }} 收藏</text>
                        </view>
                    </view>
                    <!-- 位置信息 -->
                    <view v-if="detail.location" class="location-info">
                        <tn-icon name="map-pin" size="28" color="#64748b" />
                        <text class="location-text">{{ detail.location }}</text>
                    </view>
                </view>
            </view>

            <!-- 评论区 -->
            <view class="comment-section">
                <view class="comment-header">
                    <text class="comment-title">评论 ({{ detail.comment_count }})</text>
                    <view class="comment-sort">
                        <text
                            class="sort-item"
                            :class="{ 'sort-item-active': commentSort === 'hot' }"
                            @click="changeCommentSort('hot')"
                        >
                            最热
                        </text>
                        <text class="sort-divider">|</text>
                        <text
                            class="sort-item"
                            :class="{ 'sort-item-active': commentSort === 'new' }"
                            @click="changeCommentSort('new')"
                        >
                            最新
                        </text>
                    </view>
                </view>

                <!-- 评论列表 - 使用图鸟评论列表组件 -->
                <view v-if="comments.length === 0" class="comment-empty">
                    暂无评论，快来抢沙发吧~
                </view>
                <view v-else class="comment-list-wrapper">
                    <TnCommentList
                        ref="commentListRef"
                        :data="comments"
                        :show-dislike="false"
                        like-icon="like-fill"
                        active-like-icon="like"
                        active-like-icon-color="tn-red"
                        @like="handleLikeComment"
                        @reply="replyComment"
                        @delete="deleteCommentItem"
                        @show-more="loadMoreReplies"
                    />
                </view>

                <!-- 加载更多评论 -->
                <view v-if="commentHasMore && comments.length > 0" class="load-more">
                    <text v-if="commentLoading">加载中...</text>
                    <text v-else @click="loadMoreComments">点击加载更多</text>
                </view>
            </view>

            <!-- 底部占位 -->
            <view class="bottom-spacer"></view>
        </scroll-view>

        <!-- 底部操作栏 -->
        <view class="action-bar" style="padding-bottom: calc(16rpx + env(safe-area-inset-bottom))">
            <!-- 评论输入框 - 根据 allow_comment 控制显示 -->
            <view v-if="detail.allow_comment === 1" class="comment-input-trigger" @click="showCommentInput">
                说点什么...
            </view>
            <!-- 评论已关闭提示 -->
            <view v-else class="comment-disabled-tip">
                <tn-icon name="info-circle" size="28" color="#999" />
                <text class="tip-text">该动态已关闭评论</text>
            </view>
            
            <view class="action-buttons">
                <view
                    class="action-btn"
                    :class="detail.is_liked ? 'action-btn-like-active' : 'action-btn-like'"
                    @click="handleLike"
                >
                    <tn-icon :name="detail.is_liked ? 'like-fill' : 'like'" size="44" />
                    <text class="action-btn-text">{{ formatCount(detail.like_count) }}</text>
                </view>
                <view
                    class="action-btn"
                    :class="detail.is_collected ? 'action-btn-collect-active' : 'action-btn-collect'"
                    @click="handleCollect"
                >
                    <tn-icon :name="detail.is_collected ? 'star-fill' : 'star'" size="44" />
                    <text class="action-btn-text">{{ formatCount(detail.collect_count) }}</text>
                </view>
                <view class="action-btn action-btn-comment">
                    <tn-icon name="chat" size="44" />
                    <text class="action-btn-text">{{ formatCount(detail.comment_count) }}</text>
                </view>
                <button class="share-btn action-btn action-btn-share" open-type="share">
                    <tn-icon name="share" size="44" />
                    <text class="action-btn-text">分享</text>
                </button>
            </view>
        </view>

        <!-- 评论输入弹窗 -->
        <tn-popup 
            v-model="showComment" 
            open-direction="bottom" 
            :safe-area-inset-bottom="true"
            :radius="24"
            height="60%"
        >
            <view class="comment-popup">
                <view class="popup-header">
                    <view class="popup-title-bar">
                        <text class="popup-title-text">
                            {{ replyTo ? `回复 @${replyTo.user_nickname}` : '发表评论' }}
                        </text>
                        <view class="popup-close" @click="showComment = false">
                            <tn-icon name="close" size="32" color="#94a3b8" />
                        </view>
                    </view>
                </view>
                <view class="popup-body">
                    <textarea
                        v-model="commentContent"
                        class="comment-textarea"
                        :placeholder="replyTo ? `回复 @${replyTo.user_nickname}` : '说点什么...'"
                        :maxlength="500"
                        :auto-height="true"
                        :focus="showComment"
                        placeholder-class="textarea-placeholder"
                    />
                </view>
                <view class="popup-footer">
                    <text class="char-count">{{ commentContent.length }}/500</text>
                    <button
                        class="submit-btn"
                        :class="{ 'submit-btn-disabled': !commentContent.trim() }"
                        :disabled="!commentContent.trim()"
                        @click="submitComment"
                    >
                        发送
                    </button>
                </view>
            </view>
        </tn-popup>
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
import TnCommentList from 'tnuiv3p-tn-comment-list/index.vue'
import type {
    TnCommentListInstance,
    TnCommentListData,
    TnReplyCommentParams,
    TnShowMoreCommentParams
} from 'tnuiv3p-tn-comment-list'
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
const comments = ref<TnCommentListData>([])
const commentSort = ref('hot')
const commentPage = ref(1)
const commentHasMore = ref(true)
const commentLoading = ref(false)

const showComment = ref(false)
const commentContent = ref('')
const replyTo = ref<any>(null)
const parentComment = ref<any>(null)

const commentListRef = ref<TnCommentListInstance>()

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
        
        // 处理标签数据
        let tags: string[] = []
        if (res.tags) {
            if (typeof res.tags === 'string') {
                // 如果是字符串，按逗号分割
                tags = res.tags.split(',').map((t: string) => t.trim()).filter(Boolean)
            } else if (Array.isArray(res.tags)) {
                tags = res.tags
            }
        }
        
        detail.value = {
            ...res,
            tags: tags,
            // 统一视频字段名
            video: res.video_url || res.video || '',
            video_cover: res.video_cover || ''
        }
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

        // 转换为图鸟评论列表组件所需的数据格式
        const list = (res.data || []).map((item: any) => {
            return {
                id: item.id,
                avatar: item.user?.avatar || '/static/images/user/default_avatar.png',
                nickname: item.user?.nickname || '匿名用户',
                date: item.create_time || '',
                position: item.location || '',
                content: item.content || '',
                likeActive: item.is_liked || false,
                likeCount: item.like_count || 0,
                dislikeActive: false,
                disabledReply: false,
                allowDelete: item.user_id === userId.value,
                commentCount: item.reply_count || 0,
                comment: [] // 默认为空,点击"查看更多"时再加载
            }
        })
        
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

const changeCommentSort = (sort: string) => {
    commentSort.value = sort
    fetchComments(true)
}

const loadMoreComments = () => {
    if (commentHasMore.value && !commentLoading.value) {
        commentPage.value++
        fetchComments()
    }
}

const loadMoreReplies = async ({ id, currentCommentCount }: TnShowMoreCommentParams) => {
    try {
        // 请求该评论的更多回复
        const res = await getCommentList({
            dynamic_id: dynamicId.value,
            parent_id: id,
            page: Math.floor(currentCommentCount / 20) + 1,
            page_size: 20
        })

        const replyList = (res.data || []).map((reply: any) => ({
            id: reply.id,
            avatar: reply.user_avatar || '/static/images/user/default_avatar.png',
            nickname: reply.user_nickname || '匿名用户',
            date: reply.create_time || '',
            position: reply.location || '',
            content: reply.reply_user_nickname 
                ? `回复 @${reply.reply_user_nickname}：${reply.content}`
                : reply.content,
            likeActive: reply.is_liked || false, // 正确映射点赞状态
            likeCount: reply.like_count || 0,
            dislikeActive: false,
            disabledReply: true, // 子评论不允许回复
            allowDelete: reply.user_id === userId.value
        }))

        commentListRef.value?.addCommentData(id, replyList)
    } catch (e: any) {
        uni.showToast({ title: e.message || '加载失败', icon: 'none' })
    }
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

const handleLikeComment = async (id: string | number) => {
    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    try {
        await likeComment({ id })
        // 图鸟组件会自动更新UI状态
    } catch (e: any) {
        uni.showToast({ title: e.message || '操作失败', icon: 'none' })
    }
}

const showCommentInput = () => {
    // 检查评论开关
    if (detail.value.allow_comment !== 1) {
        uni.showToast({
            title: '该动态不允许评论',
            icon: 'none'
        })
        return
    }
    
    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    replyTo.value = null
    parentComment.value = null
    showComment.value = true
}

const replyComment = ({ id, nickname }: TnReplyCommentParams) => {
    // 检查评论开关
    if (detail.value.allow_comment !== 1) {
        uni.showToast({
            title: '该动态不允许评论',
            icon: 'none'
        })
        return
    }
    
    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    
    // 从评论列表中找到对应的评论
    const findComment = (list: any[], commentId: string | number): any => {
        for (const item of list) {
            if (item.id === commentId) return item
            if (item.comment && item.comment.length > 0) {
                const found = findComment(item.comment, commentId)
                if (found) return found
            }
        }
        return null
    }
    
    const comment = findComment(comments.value, id)
    if (comment) {
        replyTo.value = { id, user_nickname: nickname }
        parentComment.value = comment
        showComment.value = true
    }
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
            params.reply_user_id = replyTo.value.id
        }

        const res = await addComment(params)

        uni.showToast({ title: '评论成功' })
        
        // 构造新评论数据
        const newCommentData = {
            id: res.comment_id || Date.now(),
            avatar: userStore.userInfo?.avatar || '/static/images/user/default_avatar.png',
            nickname: userStore.userInfo?.nickname || '我',
            date: '刚刚',
            position: '',
            content: commentContent.value.trim(),
            likeActive: false,
            likeCount: 0,
            dislikeActive: false,
            disabledReply: replyTo.value ? true : false, // 子评论不允许回复
            allowDelete: true
        }

        // 如果是回复评论，添加到对应评论的回复列表
        if (replyTo.value) {
            commentListRef.value?.addCommentReply(parentComment.value.id, newCommentData)
        } else {
            // 如果是新评论，刷新列表
            fetchComments(true)
        }

        commentContent.value = ''
        showComment.value = false
        replyTo.value = null
        parentComment.value = null
        detail.value.comment_count++
    } catch (e: any) {
        uni.showToast({ title: e.message || '评论失败', icon: 'none' })
    }
}

const deleteCommentItem = async (id: string | number) => {
    const res = await uni.showModal({
        title: '提示',
        content: '确定要删除该评论吗？'
    })
    if (res.confirm) {
        try {
            await deleteComment({ comment_id: id })
            uni.showToast({ title: '删除成功' })
            commentListRef.value?.deleteCommentReply(id)
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
    background: linear-gradient(180deg, #f8fafc 0%, #f5f5f5 100%);
}

.content-scroll {
    height: calc(100vh - 120rpx - env(safe-area-inset-bottom));
}

/* 用户信息卡片 */
.user-card {
    background: #ffffff;
    padding: 32rpx;
    border-radius: 0 0 24rpx 24rpx;
    box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.04);
}

.user-avatar {
    width: 96rpx;
    height: 96rpx;
    border-radius: 50%;
    margin-right: 24rpx;
    border: 3rpx solid #f5f5f5;
}

.user-name {
    font-size: 32rpx;
    font-weight: 600;
    color: #1e293b;
}

.user-badge {
    padding: 6rpx 16rpx;
    border-radius: 24rpx;
    font-size: 20rpx;
    font-weight: 500;
    margin-left: 12rpx;
}

.badge-staff {
    background: rgba(37, 99, 235, 0.1);
    color: #2563eb;
}

.badge-official {
    background: rgba(96, 165, 250, 0.1);
    color: #60a5fa;
}

.badge-top {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

.badge-hot {
    background: rgba(249, 115, 22, 0.1);
    color: #f97316;
}

.user-time {
    font-size: 24rpx;
    color: #94a3b8;
    margin-top: 8rpx;
}

.follow-btn {
    padding: 12rpx 32rpx;
    border-radius: 48rpx;
    font-size: 26rpx;
    font-weight: 500;
    transition: all 0.2s ease;
}

.follow-btn-active {
    background: #f1f5f9;
    color: #64748b;
}

.follow-btn-inactive {
    background: linear-gradient(135deg, #2563eb 0%, #60a5fa 100%);
    color: #ffffff;
    box-shadow: 0 4rpx 12rpx rgba(37, 99, 235, 0.25);
}

/* 内容区域 */
.content-wrapper {
    margin-top: 16rpx;
}

.badges-row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12rpx;
    margin-bottom: 16rpx;
}

.type-badge {
    display: inline-block;
    padding: 8rpx 20rpx;
    border-radius: 24rpx;
    font-size: 22rpx;
    font-weight: 500;
}

.type-badge.bg-blue-100 {
    background: rgba(37, 99, 235, 0.1);
    color: #2563eb;
}

.type-badge.bg-purple-100 {
    background: rgba(168, 85, 247, 0.1);
    color: #a855f7;
}

.type-badge.bg-green-100 {
    background: rgba(34, 197, 94, 0.1);
    color: #22c55e;
}

.type-badge.bg-orange-100 {
    background: rgba(244, 63, 94, 0.1);
    color: #f43f5e;
}

/* 动态标签 */
.tag-item {
    padding: 8rpx 20rpx;
    background: var(--color-primary-light-9);
    border-radius: 24rpx;
    border: 2rpx solid var(--color-primary-light-7);
}

.tag-text {
    font-size: 22rpx;
    color: var(--color-primary);
    font-weight: 500;
}

/* 位置信息 */
.location-info {
    display: flex;
    align-items: center;
    gap: 8rpx;
}

.location-text {
    font-size: 24rpx;
    color: #64748b;
    max-width: 200rpx;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.content-text {
    font-size: 30rpx;
    line-height: 1.8;
    color: #1e293b;
    white-space: pre-wrap;
    word-break: break-word;
}

/* 图片网格 */
.image-grid {
    display: grid;
    gap: 12rpx;
    margin-top: 32rpx;
}

.image-grid.grid-cols-1 {
    grid-template-columns: 1fr;
}

.image-grid.grid-cols-2 {
    grid-template-columns: repeat(2, 1fr);
}

.image-grid.grid-cols-3 {
    grid-template-columns: repeat(3, 1fr);
}

.grid-image {
    width: 100%;
    aspect-ratio: 1;
    border-radius: 16rpx;
    background: #f5f5f5;
}

/* 视频播放器 */
.video-wrapper {
    margin-top: 32rpx;
    border-radius: 16rpx;
    overflow: hidden;
}

.video-player {
    width: 100%;
    border-radius: 16rpx;
}

/* 互动数据 */
.stats-bar {
    margin-top: 32rpx;
    padding-top: 32rpx;
    border-top: 2rpx solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.stats-left {
    display: flex;
    align-items: center;
    gap: 48rpx;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 8rpx;
    font-size: 26rpx;
    color: #64748b;
}

/* 评论区 */
.comment-section {
    margin-top: 16rpx;
    background: #ffffff;
    border-radius: 24rpx 24rpx 0 0;
}

.comment-header {
    padding: 32rpx;
    border-bottom: 2rpx solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.comment-title {
    font-size: 32rpx;
    font-weight: 600;
    color: #1e293b;
}

.comment-sort {
    display: flex;
    align-items: center;
    gap: 24rpx;
    font-size: 26rpx;
}

.sort-item {
    color: #94a3b8;
    transition: all 0.2s ease;
}

.sort-item-active {
    color: #2563eb;
    font-weight: 500;
}

.sort-divider {
    color: #e2e8f0;
}

/* 评论列表 */
.comment-empty {
    padding: 160rpx 0;
    text-align: center;
    font-size: 28rpx;
    color: #94a3b8;
}

.comment-list-wrapper {
    padding: 0;
}

/* 加载更多 */
.load-more {
    padding: 32rpx 0;
    text-align: center;
    font-size: 26rpx;
    color: #94a3b8;
    cursor: pointer;
}

/* 底部操作栏 */
.action-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #ffffff;
    border-top: 2rpx solid #f1f5f9;
    padding: 16rpx 32rpx;
    display: flex;
    align-items: center;
    gap: 24rpx;
    box-shadow: 0 -4rpx 16rpx rgba(0, 0, 0, 0.04);
}

.comment-input-trigger {
    flex: 1;
    background: #f1f5f9;
    border-radius: 48rpx;
    padding: 16rpx 32rpx;
    font-size: 28rpx;
    color: #94a3b8;
}

/* 评论已关闭提示 */
.comment-disabled-tip {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12rpx;
    background: #f5f5f5;
    border-radius: 48rpx;
    padding: 16rpx 32rpx;
}

.tip-text {
    font-size: 26rpx;
    color: #999999;
}

.action-buttons {
    display: flex;
    align-items: center;
    gap: 32rpx;
}

.action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4rpx;
    transition: all 0.2s ease;
}

.action-btn-text {
    font-size: 20rpx;
}

.action-btn-like {
    color: #64748b;
}

.action-btn-like-active {
    color: #ef4444;
}

.action-btn-collect {
    color: #64748b;
}

.action-btn-collect-active {
    color: #f59e0b;
}

.action-btn-comment {
    color: #64748b;
}

.action-btn-share {
    color: #64748b;
}

.share-btn {
    padding: 0;
    margin: 0;
    background: none;
    border: none;
    line-height: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4rpx;

    &::after {
        display: none;
    }
}

/* 评论输入弹窗 */
.comment-popup {
    height: 100%;
    display: flex;
    flex-direction: column;
    background: #ffffff;
}

.popup-header {
    flex-shrink: 0;
    padding: 32rpx 32rpx 24rpx;
    border-bottom: 2rpx solid #f1f5f9;
}

.popup-title-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.popup-title-text {
    font-size: 32rpx;
    font-weight: 600;
    color: #1e293b;
}

.popup-close {
    width: 64rpx;
    height: 64rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #f8fafc;
    transition: all 0.2s ease;
}

.popup-body {
    flex: 1;
    padding: 0;
    overflow: hidden;
}

.comment-textarea {
    width: 100%;
    height: 100%;
    background: #ffffff;
    padding: 32rpx;
    font-size: 28rpx;
    line-height: 1.6;
    color: #1e293b;
    border: none;
    box-sizing: border-box;
}

.textarea-placeholder {
    color: #94a3b8;
}

.popup-footer {
    flex-shrink: 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24rpx 32rpx;
    border-top: 2rpx solid #f1f5f9;
}

.char-count {
    font-size: 24rpx;
    color: #94a3b8;
}

.submit-btn {
    padding: 12rpx 80rpx;
    background: linear-gradient(135deg, #2563eb 0%, #60a5fa 100%);
    color: #ffffff;
    font-size: 26rpx;
    font-weight: 500;
    border-radius: 40rpx;
    box-shadow: 0 2rpx 8rpx rgba(37, 99, 235, 0.2);
    border: none;
    transition: all 0.2s ease;
    min-width: 200rpx;
}

.submit-btn::after {
    display: none;
}

.submit-btn-disabled {
    opacity: 0.4;
    box-shadow: none;
}

/* 底部占位 */
.bottom-spacer {
    height: 256rpx;
}
</style>

<!-- 全局样式覆盖图鸟评论组件 -->
<style lang="scss">
/* 强制覆盖图鸟评论组件的底部操作栏样式 - 使用更高优先级 */
uni-view.tn-tn-comment-bottom-operation {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    justify-content: space-between !important;
    flex-wrap: nowrap !important;
}

uni-view.tn-tn-comment-bottom-operation__left {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    gap: 24rpx !important;
    flex: 1 !important;
    min-width: 0 !important;
    flex-wrap: nowrap !important;
}

uni-view.tn-tn-comment-bottom-operation__date {
    flex-shrink: 1 !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    min-width: 0 !important;
}

uni-view.tn-tn-comment-bottom-operation__reply,
uni-view.tn-tn-comment-bottom-operation__delete {
    flex-shrink: 0 !important;
    white-space: nowrap !important;
}

uni-view.tn-tn-comment-bottom-operation__right {
    flex-shrink: 0 !important;
    margin-left: 16rpx !important;
}

/* 如果上面的还不行，尝试覆盖父容器 */
uni-view.tn-tn-comment-list__bottom-operation {
    width: 100% !important;
}
</style>
