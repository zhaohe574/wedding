<template>
    <view class="callback-page">
        <z-paging ref="paging" v-model="dataList" @query="queryList">
            <!-- 状态筛选 -->
            <template #top>
                <view class="filter-tabs">
                    <view
                        class="tab-item"
                        :class="{ active: currentStatus === '' }"
                        @click="changeStatus('')"
                        >全部</view
                    >
                    <view
                        class="tab-item"
                        :class="{ active: currentStatus === 0 }"
                        @click="changeStatus(0)"
                        >待填写</view
                    >
                    <view
                        class="tab-item"
                        :class="{ active: currentStatus === 1 }"
                        @click="changeStatus(1)"
                        >已完成</view
                    >
                </view>
            </template>

            <!-- 回访列表 -->
            <view class="callback-list">
                <view
                    class="callback-item"
                    v-for="item in dataList"
                    :key="item.id"
                    @click="goFill(item)"
                >
                    <view class="callback-header">
                        <view class="callback-order">
                            <text class="order-label">订单编号：</text>
                            <text class="order-sn">{{ item.order_sn }}</text>
                        </view>
                        <view class="callback-status" :class="getStatusClass(item.status)">
                            {{ item.status === 0 ? '待填写' : '已完成' }}
                        </view>
                    </view>
                    <view class="callback-info">
                        <view class="info-row">
                            <text class="info-label">服务日期：</text>
                            <text class="info-value">{{ item.service_date }}</text>
                        </view>
                        <view class="info-row" v-if="item.staff_name">
                            <text class="info-label">服务人员：</text>
                            <text class="info-value">{{ item.staff_name }}</text>
                        </view>
                    </view>
                    <view class="callback-footer">
                        <text class="callback-time">{{ item.create_time }}</text>
                        <view class="callback-action" v-if="item.status === 0">
                            <text>立即填写</text>
                            <tn-icon name="right" size="24" color="#4facfe"></tn-icon>
                        </view>
                        <view class="callback-score" v-else-if="item.score">
                            <tn-icon name="star-fill" size="28" color="#FFB800"></tn-icon>
                            <text>{{ item.score }}分</text>
                        </view>
                    </view>
                </view>
            </view>

            <!-- 空状态 -->
            <template #empty>
                <view class="empty-state">
                    <tn-icon name="edit" size="120" color="#ccc"></tn-icon>
                    <text class="empty-text">暂无回访问卷</text>
                </view>
            </template>
        </z-paging>
    </view>
</template>

<script setup lang="ts">
import { ref, shallowRef } from 'vue'
import { getCallbackLists } from '@/api/aftersale'
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
        const res = await getCallbackLists(params)
        const lists = res?.data?.lists || res?.lists || []
        paging.value?.complete(lists)
    } catch (error) {
        paging.value?.complete(false)
    }
}

const getStatusClass = (status: number) => {
    return status === 0 ? 'status-pending' : 'status-completed'
}

const goFill = (item: any) => {
    if (item.status === 0) {
        uni.navigateTo({ url: `/pages/aftersale/callback_fill?id=${item.id}` })
    } else {
        uni.navigateTo({ url: `/pages/aftersale/callback_detail?id=${item.id}` })
    }
}

onLoad((options: any) => {
    if (options?.status) {
        currentStatus.value = parseInt(options.status)
    }
})
</script>

<style lang="scss" scoped>
.callback-page {
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
        color: #43e97b;
        background: rgba(67, 233, 123, 0.1);
        font-weight: 600;
    }
}

.callback-list {
    padding: 20rpx;
}

.callback-item {
    background: #fff;
    border-radius: 16rpx;
    padding: 30rpx;
    margin-bottom: 20rpx;
}

.callback-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20rpx;
    padding-bottom: 20rpx;
    border-bottom: 1rpx solid #f5f5f5;
}

.callback-order {
    display: flex;
    align-items: center;
}

.order-label {
    font-size: 26rpx;
    color: #999;
}

.order-sn {
    font-size: 26rpx;
    color: #333;
    font-weight: 600;
}

.callback-status {
    font-size: 24rpx;
    padding: 6rpx 16rpx;
    border-radius: 8rpx;

    &.status-pending {
        color: #faad14;
        background: rgba(250, 173, 20, 0.1);
    }
    &.status-completed {
        color: #52c41a;
        background: rgba(82, 196, 26, 0.1);
    }
}

.callback-info {
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
}

.info-value {
    font-size: 26rpx;
    color: #333;
}

.callback-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 20rpx;
    border-top: 1rpx solid #f5f5f5;
}

.callback-time {
    font-size: 24rpx;
    color: #999;
}

.callback-action {
    display: flex;
    align-items: center;
    font-size: 26rpx;
    color: #4facfe;
}

.callback-score {
    display: flex;
    align-items: center;
    font-size: 26rpx;
    color: #ffb800;

    text {
        margin-left: 8rpx;
    }
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
</style>
