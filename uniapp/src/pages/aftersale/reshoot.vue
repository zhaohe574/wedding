<template>
    <view class="reshoot-page">
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
                    >待审核</view>
                    <view 
                        class="tab-item" 
                        :class="{ active: currentStatus === 1 }"
                        @click="changeStatus(1)"
                    >已通过</view>
                    <view 
                        class="tab-item" 
                        :class="{ active: currentStatus === 2 }"
                        @click="changeStatus(2)"
                    >已拒绝</view>
                </view>
            </template>

            <!-- 补拍申请列表 -->
            <view class="reshoot-list">
                <view 
                    class="reshoot-item" 
                    v-for="item in dataList" 
                    :key="item.id"
                    @click="goDetail(item)"
                >
                    <view class="reshoot-header">
                        <text class="reshoot-sn">{{ item.reshoot_sn || `补拍#${item.id}` }}</text>
                        <view class="reshoot-status" :class="getStatusClass(item.status)">
                            {{ item.status_desc || getStatusText(item.status) }}
                        </view>
                    </view>
                    <view class="reshoot-info">
                        <view class="info-row">
                            <text class="info-label">补拍类型：</text>
                            <text class="info-value">{{ item.type_desc || getTypeText(item.type) }}</text>
                        </view>
                        <view class="info-row">
                            <text class="info-label">申请原因：</text>
                            <text class="info-value">{{ item.reason_type_desc || getReasonText(item.reason_type) }}</text>
                        </view>
                        <view class="info-row" v-if="item.expect_date">
                            <text class="info-label">期望日期：</text>
                            <text class="info-value">{{ item.expect_date }}</text>
                        </view>
                    </view>
                    <view class="reshoot-footer">
                        <text class="reshoot-time">{{ item.create_time }}</text>
                        <text class="reshoot-action" v-if="item.status === 0" @click.stop="handleCancel(item.id)">取消申请</text>
                    </view>
                </view>
            </view>

            <!-- 空状态 -->
            <template #empty>
                <view class="empty-state">
                    <u-icon name="camera" size="120" color="#ccc"></u-icon>
                    <text class="empty-text">暂无补拍申请</text>
                </view>
            </template>
        </z-paging>

        <!-- 提交补拍按钮 -->
        <view class="create-btn" @click="goCreate">
            <u-icon name="plus" size="40" color="#fff"></u-icon>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref, shallowRef } from 'vue'
import { getReshootLists, cancelReshoot } from '@/api/aftersale'
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
        const res = await getReshootLists(params)
        const lists = res?.data?.lists || res?.lists || []
        paging.value?.complete(lists)
    } catch (error) {
        paging.value?.complete(false)
    }
}

const getStatusClass = (status: number) => {
    const map: Record<number, string> = {
        0: 'status-pending',
        1: 'status-approved',
        2: 'status-rejected',
        3: 'status-completed'
    }
    return map[status] || ''
}

const getStatusText = (status: number) => {
    const map: Record<number, string> = {
        0: '待审核',
        1: '已通过',
        2: '已拒绝',
        3: '已完成'
    }
    return map[status] || '未知'
}

const getTypeText = (type: number) => {
    const map: Record<number, string> = {
        1: '全部重拍',
        2: '部分重拍',
        3: '补拍加片'
    }
    return map[type] || '其他'
}

const getReasonText = (reasonType: number) => {
    const map: Record<number, string> = {
        1: '拍摄效果不满意',
        2: '照片质量问题',
        3: '服务态度问题',
        4: '天气原因',
        5: '其他原因'
    }
    return map[reasonType] || '其他'
}

const goDetail = (item: any) => {
    uni.navigateTo({ url: `/pages/aftersale/index?action=reshoot_detail&id=${item.id}` })
}

const goCreate = () => {
    uni.navigateTo({ url: '/pages/aftersale/apply_reshoot' })
}

const handleCancel = async (id: number) => {
    uni.showModal({
        title: '确认取消',
        content: '确定要取消此补拍申请吗？',
        success: async (res) => {
            if (res.confirm) {
                try {
                    await cancelReshoot(id)
                    uni.showToast({ title: '取消成功' })
                    paging.value?.reload()
                } catch (e) {
                    uni.showToast({ title: '取消失败', icon: 'none' })
                }
            }
        }
    })
}

onLoad((options: any) => {
    if (options?.status) {
        currentStatus.value = parseInt(options.status)
    }
})
</script>

<style lang="scss" scoped>
.reshoot-page {
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
        color: #4facfe;
        background: rgba(79, 172, 254, 0.1);
        font-weight: 600;
    }
}

.reshoot-list {
    padding: 20rpx;
}

.reshoot-item {
    background: #fff;
    border-radius: 16rpx;
    padding: 30rpx;
    margin-bottom: 20rpx;
}

.reshoot-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20rpx;
    padding-bottom: 20rpx;
    border-bottom: 1rpx solid #f5f5f5;
}

.reshoot-sn {
    font-size: 28rpx;
    font-weight: 600;
    color: #333;
}

.reshoot-status {
    font-size: 24rpx;
    padding: 6rpx 16rpx;
    border-radius: 8rpx;
    
    &.status-pending {
        color: #faad14;
        background: rgba(250, 173, 20, 0.1);
    }
    &.status-approved {
        color: #52c41a;
        background: rgba(82, 196, 26, 0.1);
    }
    &.status-rejected {
        color: #f5222d;
        background: rgba(245, 34, 45, 0.1);
    }
    &.status-completed {
        color: #1890ff;
        background: rgba(24, 144, 255, 0.1);
    }
}

.reshoot-info {
    margin-bottom: 20rpx;
}

.info-row {
    display: flex;
    margin-bottom: 12rpx;
    
    &:last-child {
        margin-bottom: 0;
    }
}

.info-label {
    font-size: 26rpx;
    color: #999;
    width: 160rpx;
}

.info-value {
    font-size: 26rpx;
    color: #333;
    flex: 1;
}

.reshoot-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 20rpx;
    border-top: 1rpx solid #f5f5f5;
}

.reshoot-time {
    font-size: 24rpx;
    color: #999;
}

.reshoot-action {
    font-size: 26rpx;
    color: #f5222d;
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
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8rpx 24rpx rgba(79, 172, 254, 0.4);
}
</style>
