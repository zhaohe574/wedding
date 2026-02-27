<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="动态管理"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>

    <view class="page-container">
        <z-paging ref="pagingRef" v-model="dynamicList" @query="queryList" :auto="false">
            <template #top>
                <view class="top-bar">
                    <view
                        class="publish-btn"
                        :style="{ background: $theme.primaryColor, color: $theme.btnColor }"
                        @click="handleAdd"
                    >
                        <tn-icon name="plus" size="30" :color="$theme.btnColor" />
                        <text>发布动态</text>
                    </view>
                </view>
            </template>

            <view class="dynamic-list">
                <view
                    v-for="item in dynamicList"
                    :key="item.id"
                    class="card"
                    @click="handleEdit(item)"
                >
                    <!-- 统一顶部行：类型 + 状态 -->
                    <view class="card-head">
                        <view
                            class="type-badge"
                            :style="{ background: `${$theme.primaryColor}15`, color: $theme.primaryColor }"
                        >
                            <tn-icon :name="getTypeIcon(item.dynamic_type)" size="22" :color="$theme.primaryColor" />
                            <text>{{ typeText(item.dynamic_type) }}</text>
                        </view>
                        <view class="status-tag" :style="getStatusStyle(item.status)">{{ statusText(item.status) }}</view>
                    </view>

                    <!-- 图文类型：左文右图 -->
                    <view v-if="item.dynamic_type === 1 && getImageList(item).length > 0" class="card-row">
                        <view class="row-text">
                            <text class="content-text">{{ item.content }}</text>
                            <view v-if="getTagsList(item).length > 0" class="tags-row">
                                <text
                                    v-for="(tag, idx) in getTagsList(item).slice(0, 3)"
                                    :key="idx"
                                    class="tag-text"
                                    :style="{ color: $theme.primaryColor }"
                                >#{{ tag }}</text>
                            </view>
                        </view>
                        <view class="row-thumb">
                            <image :src="appStore.getImageUrl(getImageList(item)[0])" class="thumb-img" mode="aspectFill" />
                            <view v-if="getImageList(item).length > 1" class="thumb-badge">{{ getImageList(item).length }}图</view>
                        </view>
                    </view>

                    <!-- 视频类型 -->
                    <view v-else-if="item.dynamic_type === 2" class="card-video">
                        <view v-if="getVideoCover(item)" class="video-box">
                            <image :src="appStore.getImageUrl(getVideoCover(item))" class="video-img" mode="aspectFill" />
                            <view class="play-btn">
                                <text class="play-icon">▶</text>
                            </view>
                        </view>
                        <text class="content-text content-text--block">{{ item.content }}</text>
                        <view v-if="getTagsList(item).length > 0" class="tags-row">
                            <text
                                v-for="(tag, idx) in getTagsList(item).slice(0, 3)"
                                :key="idx"
                                class="tag-text"
                                :style="{ color: $theme.primaryColor }"
                            >#{{ tag }}</text>
                        </view>
                    </view>

                    <!-- 纯文字 / 活动 -->
                    <view v-else class="card-plain">
                        <text class="content-text content-text--block">{{ item.content }}</text>
                        <view v-if="getTagsList(item).length > 0" class="tags-row">
                            <text
                                v-for="(tag, idx) in getTagsList(item).slice(0, 3)"
                                :key="idx"
                                class="tag-text"
                                :style="{ color: $theme.primaryColor }"
                            >#{{ tag }}</text>
                        </view>
                    </view>

                    <!-- 底部栏 -->
                    <view class="card-foot">
                        <view class="foot-left">
                            <text class="foot-time">{{ formatTime(item.create_time) }}</text>
                            <text class="foot-sep">·</text>
                            <tn-icon name="eye" size="22" color="#C0C0C0" />
                            <text class="foot-num">{{ item.view_count || 0 }}</text>
                            <tn-icon name="like" size="22" color="#C0C0C0" />
                            <text class="foot-num">{{ item.like_count || 0 }}</text>
                        </view>
                        <view class="foot-right">
                            <view class="foot-btn" @click.stop="handleEdit(item)">
                                <tn-icon name="edit" size="28" :color="$theme.primaryColor" />
                            </view>
                            <view class="foot-btn" @click.stop="handleDelete(item)">
                                <tn-icon name="delete" size="28" color="#FF4D4F" />
                            </view>
                        </view>
                    </view>
                </view>

                <!-- 空状态 -->
                <view v-if="dynamicList.length === 0" class="empty-state">
                    <tn-icon name="edit" size="100" color="#E0E0E0" />
                    <text class="empty-text">还没有发布动态</text>
                    <view
                        class="empty-btn"
                        :style="{ background: $theme.primaryColor, color: $theme.btnColor }"
                        @click="handleAdd"
                    >立即发布</view>
                </view>
            </view>
        </z-paging>
    </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { staffCenterDynamicLists, staffCenterDynamicDelete } from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/utils/staff-center'
import { useThemeStore } from '@/stores/theme'
import { useAppStore } from '@/stores/app'

const $theme = useThemeStore()
const appStore = useAppStore()
const pagingRef = ref<any>(null)
const dynamicList = ref<any[]>([])

const typeText = (type: number) => {
    const map: Record<number, string> = { 1: '图文', 2: '视频', 4: '活动' }
    return map[type] || '动态'
}

const getTypeIcon = (type: number) => {
    const icons: Record<number, string> = { 1: 'image', 2: 'video', 4: 'gift' }
    return icons[type] || 'edit'
}

const statusText = (status: number) => {
    const map: Record<number, string> = { 0: '待审核', 1: '已发布', 2: '已下架', 3: '已拒绝' }
    return map[status] || '未知'
}

const getStatusStyle = (status: number) => {
    const styles: Record<number, any> = {
        0: { background: 'rgba(255,153,0,0.1)', color: '#FF9900' },
        1: { background: 'rgba(25,190,107,0.1)', color: '#19BE6B' },
        2: { background: 'rgba(153,153,153,0.1)', color: '#999' },
        3: { background: 'rgba(255,44,60,0.1)', color: '#FF2C3C' }
    }
    return styles[status] || styles[0]
}

const getImageList = (item: any): string[] => {
    if (item.images && Array.isArray(item.images) && item.images.length > 0) return item.images
    return []
}

const getVideoCover = (item: any): string => {
    if (item.video_cover) return item.video_cover
    const images = getImageList(item)
    if (images.length > 0) return images[0]
    return ''
}

const getTagsList = (item: any): string[] => {
    if (item.tags_arr && Array.isArray(item.tags_arr) && item.tags_arr.length > 0) return item.tags_arr
    if (item.tags && typeof item.tags === 'string') return item.tags.split(',').filter((t: string) => t.trim())
    return []
}

const formatTime = (time: any): string => {
    if (!time) return ''
    let date: Date
    if (typeof time === 'number') {
        date = new Date(time < 1e12 ? time * 1000 : time)
    } else {
        date = new Date(String(time).replace(/-/g, '/'))
    }
    if (isNaN(date.getTime())) return String(time)
    const now = Date.now()
    const diff = now - date.getTime()
    const min = Math.floor(diff / 60000)
    if (min < 1) return '刚刚'
    if (min < 60) return `${min}分钟前`
    const hr = Math.floor(min / 60)
    if (hr < 24) return `${hr}小时前`
    const d = Math.floor(hr / 24)
    if (d < 7) return `${d}天前`
    const pad = (n: number) => String(n).padStart(2, '0')
    return `${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
}

const queryList = async (pageNo: number, pageSize: number) => {
    try {
        const res: any = await staffCenterDynamicLists({ page: pageNo, page_size: pageSize })
        pagingRef.value.complete(res?.data || [])
    } catch (e) {
        pagingRef.value.complete(false)
    }
}

const handleAdd = () => {
    uni.navigateTo({ url: '/packages/pages/staff_dynamic_edit/staff_dynamic_edit' })
}

const handleEdit = (item: any) => {
    uni.setStorageSync('_temp_dynamic_detail', JSON.stringify(item))
    uni.navigateTo({
        url: `/packages/pages/staff_dynamic_edit/staff_dynamic_edit?id=${item.id}`,
        success: (res) => { res.eventChannel.emit('detail', item) }
    })
}

const handleDelete = (item: any) => {
    uni.showModal({
        title: '确认删除',
        content: '删除后不可恢复，是否继续？',
        success: async (res) => {
            if (!res.confirm) return
            try {
                await staffCenterDynamicDelete({ id: item.id })
                uni.showToast({ title: '删除成功', icon: 'success' })
                pagingRef.value.reload()
            } catch (e: any) {
                uni.showToast({ title: e?.msg || e?.message || '删除失败', icon: 'none' })
            }
        }
    })
}

onShow(async () => {
    if (!(await ensureStaffCenterAccess())) return
    pagingRef.value?.reload()
})
</script>

<style lang="scss" scoped>
.page-container {
    min-height: 100vh;
    background: #F4F5F7;
}

.top-bar {
    padding: 16rpx 24rpx;
    background: #FFF;
}

.publish-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    height: 68rpx;
    border-radius: 40rpx;
    font-size: 28rpx;
    font-weight: 600;
    &:active { opacity: 0.85; }
}

.dynamic-list {
    padding: 16rpx;
}

/* 卡片 */
.card {
    margin-bottom: 12rpx;
    padding: 16rpx 20rpx;
    background: #FFF;
    border-radius: 16rpx;
    box-shadow: 0 1rpx 8rpx rgba(0,0,0,0.03);
    &:active { background: #FAFAFA; }
}

/* 卡片头部 */
.card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12rpx;
}

.type-badge {
    display: inline-flex;
    align-items: center;
    gap: 4rpx;
    padding: 2rpx 12rpx;
    border-radius: 6rpx;
    font-size: 22rpx;
    font-weight: 500;
    line-height: 36rpx;
}

.status-tag {
    padding: 2rpx 12rpx;
    border-radius: 6rpx;
    font-size: 22rpx;
    font-weight: 500;
    line-height: 36rpx;
    flex-shrink: 0;
}

/* 图文：左文右图 */
.card-row {
    display: flex;
    gap: 16rpx;
    margin-bottom: 12rpx;
}

.row-text {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    gap: 6rpx;
}

.row-thumb {
    position: relative;
    flex-shrink: 0;
    width: 140rpx;
    height: 140rpx;
    border-radius: 10rpx;
    overflow: hidden;
}

.thumb-img {
    width: 140rpx;
    height: 140rpx;
}

.thumb-badge {
    position: absolute;
    left: 0;
    bottom: 0;
    padding: 0 10rpx;
    background: rgba(0,0,0,0.5);
    border-top-right-radius: 8rpx;
    font-size: 20rpx;
    color: #FFF;
    line-height: 32rpx;
}

/* 内容文字 */
.content-text {
    font-size: 28rpx;
    line-height: 1.45;
    color: #333;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 3;
    overflow: hidden;
    text-overflow: ellipsis;
    word-break: break-all;
}

.content-text--block {
    margin-bottom: 8rpx;
}

/* 标签 */
.tags-row {
    display: flex;
    flex-wrap: wrap;
    gap: 10rpx;
    margin-top: 6rpx;
}

.tag-text {
    font-size: 22rpx;
    font-weight: 500;
}

/* 视频 */
.card-video {
    margin-bottom: 12rpx;
}

.video-box {
    position: relative;
    width: 100%;
    height: 280rpx;
    border-radius: 10rpx;
    overflow: hidden;
    margin-bottom: 10rpx;
}

.video-img {
    width: 100%;
    height: 100%;
}

.play-btn {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 72rpx;
    height: 72rpx;
    border-radius: 50%;
    background: rgba(0,0,0,0.4);
    display: flex;
    align-items: center;
    justify-content: center;
}

.play-icon {
    font-size: 28rpx;
    color: #FFF;
    margin-left: 4rpx;
}

/* 纯文字 */
.card-plain {
    margin-bottom: 12rpx;
}

/* 底部栏 */
.card-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 10rpx;
    border-top: 1rpx solid #F2F2F2;
}

.foot-left {
    display: flex;
    align-items: center;
    gap: 6rpx;
}

.foot-time {
    font-size: 22rpx;
    color: #C0C0C0;
}

.foot-sep {
    font-size: 22rpx;
    color: #D9D9D9;
    margin: 0 2rpx;
}

.foot-num {
    font-size: 22rpx;
    color: #C0C0C0;
    margin-right: 4rpx;
}

.foot-right {
    display: flex;
    align-items: center;
}

.foot-btn {
    width: 52rpx;
    height: 52rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    &:active { background: rgba(0,0,0,0.04); }
}

/* 空状态 */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 100rpx 0;
    gap: 16rpx;
}

.empty-text {
    font-size: 28rpx;
    color: #999;
}

.empty-btn {
    margin-top: 8rpx;
    padding: 12rpx 44rpx;
    border-radius: 40rpx;
    font-size: 28rpx;
    font-weight: 600;
    &:active { opacity: 0.85; }
}
</style>
