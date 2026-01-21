<template>
    <view class="complaint-page">
        <z-paging
            ref="paging"
            v-model="dataList"
            @query="queryList"
        >
            <!-- 状态筛选 -->
            <template #top>
                <view class="filter-tabs">
                    <view 
                        class="tab-item" 
                        :class="{ active: currentStatus === '' }"
                        @click="changeStatus('')"
                    >全部</view>
                    <view 
                        class="tab-item" 
                        :class="{ active: currentStatus === 0 }"
                        @click="changeStatus(0)"
                    >待处理</view>
                    <view 
                        class="tab-item" 
                        :class="{ active: currentStatus === 1 }"
                        @click="changeStatus(1)"
                    >处理中</view>
                    <view 
                        class="tab-item" 
                        :class="{ active: currentStatus === 2 }"
                        @click="changeStatus(2)"
                    >已处理</view>
                </view>
            </template>

            <!-- 投诉列表 -->
            <view class="complaint-list">
                <view 
                    class="complaint-item" 
                    v-for="item in dataList" 
                    :key="item.id"
                    @click="goDetail(item.id)"
                >
                    <view class="complaint-header">
                        <view class="complaint-level" :class="getLevelClass(item.level)">
                            {{ getLevelText(item.level) }}
                        </view>
                        <view class="complaint-status" :class="getStatusClass(item.status)">
                            {{ item.status_desc || getStatusText(item.status) }}
                        </view>
                    </view>
                    <view class="complaint-title">{{ item.title }}</view>
                    <view class="complaint-content" v-if="item.content">{{ item.content }}</view>
                    <view class="complaint-footer">
                        <text class="complaint-type">{{ item.type_desc || getTypeText(item.type) }}</text>
                        <text class="complaint-time">{{ item.create_time }}</text>
                    </view>
                </view>
            </view>

            <!-- 空状态 -->
            <template #empty>
                <view class="empty-state">
                    <u-icon name="warning" size="120" color="#ccc"></u-icon>
                    <text class="empty-text">暂无投诉记录</text>
                </view>
            </template>
        </z-paging>

        <!-- 提交投诉按钮 -->
        <view class="create-btn" @click="goCreate">
            <u-icon name="plus" size="40" color="#fff"></u-icon>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref, shallowRef } from 'vue'
import { getComplaintLists } from '@/api/aftersale'
import { onLoad } from '@dcloudio/uni-app'

const paging = shallowRef()
const dataList = ref<any[]>([])
const currentStatus = ref<number | string>('')

const changeStatus = (status: number | string) => {
    currentStatus.value = status
    paging.value?.reload()
}

const queryList = async (pageNo: number, pageSize: number) => {
    try {
        const params: any = {
            page: pageNo,
            limit: pageSize
        }
        if (currentStatus.value !== '') {
            params.status = currentStatus.value
        }
        const res = await getComplaintLists(params)
        const lists = res?.data?.lists || res?.lists || []
        paging.value?.complete(lists)
    } catch (error) {
        paging.value?.complete(false)
    }
}

const getStatusClass = (status: number) => {
    const map: Record<number, string> = {
        0: 'status-pending',
        1: 'status-processing',
        2: 'status-completed'
    }
    return map[status] || ''
}

const getStatusText = (status: number) => {
    const map: Record<number, string> = {
        0: '待处理',
        1: '处理中',
        2: '已处理'
    }
    return map[status] || '未知'
}

const getLevelClass = (level: number) => {
    const map: Record<number, string> = {
        1: 'level-low',
        2: 'level-medium',
        3: 'level-high'
    }
    return map[level] || ''
}

const getLevelText = (level: number) => {
    const map: Record<number, string> = {
        1: '一般',
        2: '重要',
        3: '紧急'
    }
    return map[level] || '一般'
}

const getTypeText = (type: number) => {
    const map: Record<number, string> = {
        1: '服务态度',
        2: '服务质量',
        3: '虚假宣传',
        4: '价格问题',
        5: '其他'
    }
    return map[type] || '其他'
}

const goDetail = (id: number) => {
    uni.navigateTo({ url: `/pages/aftersale/index?action=complaint_detail&id=${id}` })
}

const goCreate = () => {
    uni.navigateTo({ url: '/pages/aftersale/index?action=submit_complaint' })
}

onLoad((options: any) => {
    if (options?.status) {
        currentStatus.value = parseInt(options.status)
    }
})
</script>

<style lang="scss" scoped>
.complaint-page {
    min-height: 100vh;
    background: #f5f5f5;
}

.filter-tabs {
    display: flex;
    background: #fff;
    padding: 20rpx;
    border-bottom: 1rpx solid #eee;
}

.tab-item {
    flex: 1;
    text-align: center;
    padding: 16rpx 0;
    font-size: 28rpx;
    color: #666;
    border-radius: 8rpx;
    
    &.active {
        color: #f5576c;
        background: rgba(245, 87, 108, 0.1);
        font-weight: 600;
    }
}

.complaint-list {
    padding: 20rpx;
}

.complaint-item {
    background: #fff;
    border-radius: 16rpx;
    padding: 30rpx;
    margin-bottom: 20rpx;
}

.complaint-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16rpx;
}

.complaint-level {
    font-size: 24rpx;
    padding: 6rpx 16rpx;
    border-radius: 8rpx;
    
    &.level-low {
        color: #52c41a;
        background: rgba(82, 196, 26, 0.1);
    }
    &.level-medium {
        color: #faad14;
        background: rgba(250, 173, 20, 0.1);
    }
    &.level-high {
        color: #f5222d;
        background: rgba(245, 34, 45, 0.1);
    }
}

.complaint-status {
    font-size: 24rpx;
    padding: 6rpx 16rpx;
    border-radius: 8rpx;
    
    &.status-pending {
        color: #faad14;
        background: rgba(250, 173, 20, 0.1);
    }
    &.status-processing {
        color: #1890ff;
        background: rgba(24, 144, 255, 0.1);
    }
    &.status-completed {
        color: #52c41a;
        background: rgba(82, 196, 26, 0.1);
    }
}

.complaint-title {
    font-size: 30rpx;
    font-weight: 600;
    color: #333;
    margin-bottom: 12rpx;
}

.complaint-content {
    font-size: 26rpx;
    color: #666;
    margin-bottom: 16rpx;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.complaint-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.complaint-type {
    font-size: 24rpx;
    color: #999;
    padding: 4rpx 12rpx;
    background: #f5f5f5;
    border-radius: 4rpx;
}

.complaint-time {
    font-size: 24rpx;
    color: #999;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 100rpx 0;
}

.empty-text {
    font-size: 28rpx;
    color: #999;
    margin-top: 20rpx;
}

.create-btn {
    position: fixed;
    right: 40rpx;
    bottom: 100rpx;
    width: 100rpx;
    height: 100rpx;
    border-radius: 50%;
    background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8rpx 24rpx rgba(245, 87, 108, 0.4);
}
</style>
