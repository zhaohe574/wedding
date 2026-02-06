<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar title="动态管理" :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>

    <view class="page-container">
        <z-paging ref="pagingRef" v-model="dynamicList" @query="queryList" :auto="true">
            <template #top>
                <!-- 顶部操作栏 -->
                <view class="top-bar">
                    <view 
                        class="publish-btn"
                        :style="{ 
                            background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
                            color: $theme.btnColor
                        }"
                        @click="handleAdd"
                    >
                        <tn-icon name="edit" size="32" :color="$theme.btnColor" />
                        <text>发布动态</text>
                    </view>
                </view>
            </template>

            <!-- 动态列表 -->
            <view class="dynamic-list">
                <view
                    v-for="item in dynamicList"
                    :key="item.id"
                    class="dynamic-card"
                    @click="handleEdit(item)"
                >
                    <!-- 动态头部 -->
                    <view class="dynamic-header">
                        <view class="dynamic-type">
                            <tn-icon :name="getTypeIcon(item.dynamic_type)" size="28" :color="$theme.primaryColor" />
                            <text>{{ typeText(item.dynamic_type) }}</text>
                        </view>
                        <view 
                            class="dynamic-status"
                            :style="getStatusStyle(item.status)"
                        >
                            <tn-icon :name="getStatusIcon(item.status)" size="24" color="inherit" />
                            <text>{{ statusText(item.status) }}</text>
                        </view>
                    </view>

                    <!-- 动态内容 -->
                    <view class="dynamic-content">
                        <text class="content-text">{{ item.content }}</text>
                    </view>

                    <!-- 媒体信息 -->
                    <view class="dynamic-media">
                        <view v-if="item.images && item.images.length > 0" class="media-item">
                            <tn-icon name="image" size="24" color="#999999" />
                            <text>图片 {{ item.images.length }}</text>
                        </view>
                        <view v-if="item.video_url" class="media-item">
                            <tn-icon name="video" size="24" color="#999999" />
                            <text>视频 1</text>
                        </view>
                        <view class="media-item">
                            <tn-icon name="eye" size="24" color="#999999" />
                            <text>{{ item.view_count || 0 }}</text>
                        </view>
                        <view class="media-item">
                            <tn-icon name="like" size="24" color="#999999" />
                            <text>{{ item.like_count || 0 }}</text>
                        </view>
                    </view>

                    <!-- 操作按钮 -->
                    <view class="dynamic-actions">
                        <view
                            class="action-btn edit-btn"
                            :style="{ 
                                color: $theme.primaryColor,
                                borderColor: $theme.primaryColor
                            }"
                            @click.stop="handleEdit(item)"
                        >
                            <tn-icon name="edit" size="28" :color="$theme.primaryColor" />
                            <text>编辑</text>
                        </view>
                        <view
                            class="action-btn delete-btn"
                            @click.stop="handleDelete(item)"
                        >
                            <tn-icon name="delete" size="28" color="#FF2C3C" />
                            <text>删除</text>
                        </view>
                    </view>
                </view>

                <!-- 空状态 -->
                <view v-if="dynamicList.length === 0" class="empty-state">
                    <tn-icon name="edit" size="120" color="#E5E5E5" />
                    <text class="empty-text">暂无动态</text>
                    <view 
                        class="empty-btn"
                        :style="{ 
                            background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
                            color: $theme.btnColor
                        }"
                        @click="handleAdd"
                    >
                        立即发布
                    </view>
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

const $theme = useThemeStore()
const pagingRef = ref<any>(null)
const dynamicList = ref<any[]>([])

// 动态类型文本
const typeText = (type: number) => {
    const map: Record<number, string> = { 1: '图文', 2: '视频', 3: '案例', 4: '活动' }
    return map[type] || '动态'
}

// 动态类型图标
const getTypeIcon = (type: number) => {
    const icons: Record<number, string> = { 1: 'image', 2: 'video', 3: 'star', 4: 'gift' }
    return icons[type] || 'edit'
}

// 状态文本
const statusText = (status: number) => {
    const map: Record<number, string> = { 0: '待审核', 1: '已发布', 2: '已下架', 3: '审核拒绝' }
    return map[status] || '未知'
}

// 状态样式
const getStatusStyle = (status: number) => {
    const styles: Record<number, any> = {
        0: { background: 'rgba(255, 153, 0, 0.1)', color: '#FF9900' },
        1: { background: 'rgba(25, 190, 107, 0.1)', color: '#19BE6B' },
        2: { background: 'rgba(153, 153, 153, 0.1)', color: '#999999' },
        3: { background: 'rgba(255, 44, 60, 0.1)', color: '#FF2C3C' }
    }
    return styles[status] || styles[0]
}

// 状态图标
const getStatusIcon = (status: number) => {
    const icons: Record<number, string> = {
        0: 'clock',
        1: 'check-circle',
        2: 'eye-off',
        3: 'close-circle'
    }
    return icons[status] || 'info'
}

// 查询列表
const queryList = async (pageNo: number, pageSize: number) => {
    try {
        const res: any = await staffCenterDynamicLists({ page: pageNo, page_size: pageSize })
        const list = res?.data || []
        pagingRef.value.complete(list)
    } catch (e) {
        pagingRef.value.complete(false)
    }
}

// 发布动态
const handleAdd = () => {
    uni.navigateTo({ url: '/packages/pages/staff_dynamic_edit/staff_dynamic_edit' })
}

// 编辑动态
const handleEdit = (item: any) => {
    uni.navigateTo({
        url: `/packages/pages/staff_dynamic_edit/staff_dynamic_edit?id=${item.id}`,
        success: (res) => {
            res.eventChannel.emit('detail', item)
        }
    })
}

// 删除动态
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
                const msg = typeof e === 'string' ? e : e?.msg || e?.message || '删除失败'
                uni.showToast({ title: msg, icon: 'none' })
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
    background: linear-gradient(180deg, rgba(124, 58, 237, 0.05) 0%, #F6F6F6 100%);
}

/* 顶部操作栏 */
.top-bar {
    padding: 24rpx;
    background: #FFFFFF;
    box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.04);
}

.publish-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12rpx;
    height: 72rpx;
    border-radius: 48rpx;
    font-size: 30rpx;
    font-weight: 600;
    box-shadow: 0 8rpx 24rpx rgba(124, 58, 237, 0.3);
    transition: all 0.2s ease;
    
    &:active {
        transform: translateY(2rpx);
        box-shadow: 0 4rpx 12rpx rgba(124, 58, 237, 0.3);
    }
}

/* 动态列表 */
.dynamic-list {
    padding: 24rpx;
}

.dynamic-card {
    margin-bottom: 24rpx;
    padding: 24rpx;
    background: #FFFFFF;
    border-radius: 24rpx;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);
    transition: all 0.2s ease;
    
    &:active {
        transform: translateY(-2rpx);
        box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.1);
    }
    
    &:last-child {
        margin-bottom: 0;
    }
}

/* 动态头部 */
.dynamic-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 16rpx;
    border-bottom: 1rpx solid #F5F5F5;
    margin-bottom: 16rpx;
}

.dynamic-type {
    display: flex;
    align-items: center;
    gap: 8rpx;
    font-size: 28rpx;
    font-weight: 500;
    color: #666666;
}

.dynamic-status {
    display: flex;
    align-items: center;
    gap: 6rpx;
    padding: 6rpx 16rpx;
    border-radius: 24rpx;
    font-size: 24rpx;
    font-weight: 500;
}

/* 动态内容 */
.dynamic-content {
    margin-bottom: 16rpx;
}

.content-text {
    font-size: 30rpx;
    line-height: 1.6;
    color: #333333;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 3;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* 媒体信息 */
.dynamic-media {
    display: flex;
    align-items: center;
    gap: 24rpx;
    padding: 16rpx 0;
    border-top: 1rpx solid #F5F5F5;
    border-bottom: 1rpx solid #F5F5F5;
    margin-bottom: 16rpx;
}

.media-item {
    display: flex;
    align-items: center;
    gap: 6rpx;
    font-size: 24rpx;
    color: #999999;
}

/* 操作按钮 */
.dynamic-actions {
    display: flex;
    gap: 16rpx;
}

.action-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    height: 64rpx;
    border-radius: 48rpx;
    font-size: 28rpx;
    font-weight: 500;
    border: 2rpx solid;
    transition: all 0.2s ease;
    
    &:active {
        opacity: 0.8;
    }
}

.edit-btn {
    background: transparent;
}

.delete-btn {
    background: transparent;
    color: #FF2C3C;
    border-color: #FF2C3C;
}

/* 空状态 */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 120rpx 0;
    gap: 24rpx;
}

.empty-text {
    font-size: 28rpx;
    color: #999999;
}

.empty-btn {
    margin-top: 16rpx;
    padding: 16rpx 48rpx;
    border-radius: 48rpx;
    font-size: 28rpx;
    font-weight: 600;
    box-shadow: 0 8rpx 24rpx rgba(124, 58, 237, 0.3);
    
    &:active {
        opacity: 0.9;
    }
}
</style>
