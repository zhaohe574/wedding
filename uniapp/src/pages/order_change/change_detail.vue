<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="变更详情"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>
    <view class="change-detail">
        <view v-if="loading" class="py-20 text-center text-gray-400"> 加载中... </view>
        <template v-else-if="detail">
            <!-- 状态卡片 -->
            <view class="status-card" :class="getStatusBgClass(detail.change_status)">
                <view class="text-lg font-bold">{{ detail.change_status_desc }}</view>
                <view class="text-sm mt-1 opacity-80">{{
                    getStatusTip(detail.change_status)
                }}</view>
            </view>

            <!-- 基本信息 -->
            <view class="bg-white mt-3 p-4">
                <view class="section-title">变更信息</view>
                <view class="info-row">
                    <text class="label">变更单号</text>
                    <text class="value">{{ detail.change_sn }}</text>
                </view>
                <view class="info-row">
                    <text class="label">变更类型</text>
                    <view class="tag" :class="getTypeClass(detail.change_type)">
                        {{ detail.change_type_desc }}
                    </view>
                </view>
                <view class="info-row">
                    <text class="label">申请时间</text>
                    <text class="value">{{ detail.create_time }}</text>
                </view>
                <view class="info-row" v-if="detail.audit_time">
                    <text class="label">审核时间</text>
                    <text class="value">{{ detail.audit_time }}</text>
                </view>
                <view class="info-row" v-if="detail.execute_time">
                    <text class="label">执行时间</text>
                    <text class="value">{{ detail.execute_time }}</text>
                </view>
            </view>

            <!-- 变更内容 -->
            <view class="bg-white mt-3 p-4">
                <view class="section-title">变更内容</view>

                <!-- 改期 -->
                <template v-if="detail.change_type === 1">
                    <view class="change-content">
                        <view class="change-item">
                            <view class="change-label">原服务日期</view>
                            <view class="change-value old">{{ detail.old_service_date }}</view>
                        </view>
                        <view class="change-arrow">
                            <uni-icons type="arrowright" size="20" color="#999"></uni-icons>
                        </view>
                        <view class="change-item">
                            <view class="change-label">新服务日期</view>
                            <view class="change-value new">{{ detail.new_service_date }}</view>
                        </view>
                    </view>
                </template>

                <!-- 换人 -->
                <template v-else-if="detail.change_type === 2">
                    <view class="change-content">
                        <view class="change-item">
                            <view class="change-label">原工作人员</view>
                            <view class="staff-info" v-if="detail.old_staff">
                                <image
                                    :src="detail.old_staff.avatar"
                                    class="staff-avatar"
                                    mode="aspectFill"
                                />
                                <text class="staff-name">{{ detail.old_staff.name }}</text>
                            </view>
                            <view class="change-price">¥{{ detail.old_price }}</view>
                        </view>
                        <view class="change-arrow">
                            <uni-icons type="arrowright" size="20" color="#999"></uni-icons>
                        </view>
                        <view class="change-item">
                            <view class="change-label">新工作人员</view>
                            <view class="staff-info" v-if="detail.new_staff">
                                <image
                                    :src="detail.new_staff.avatar"
                                    class="staff-avatar"
                                    mode="aspectFill"
                                />
                                <text class="staff-name">{{ detail.new_staff.name }}</text>
                            </view>
                            <view class="change-price">¥{{ detail.new_price }}</view>
                        </view>
                    </view>
                    <view class="price-diff mt-4 text-center" v-if="detail.price_diff !== 0">
                        <text class="text-gray-500">差价: </text>
                        <text
                            :class="detail.price_diff > 0 ? 'text-red-500' : 'text-green-500'"
                            class="text-lg font-bold"
                        >
                            {{ detail.price_diff > 0 ? '+' : '' }}{{ detail.price_diff }}元
                        </text>
                        <view class="text-xs text-gray-400 mt-1">
                            {{ detail.price_diff > 0 ? '需补差价' : '差价将退还' }}
                        </view>
                    </view>
                </template>

                <!-- 加项 -->
                <template v-else-if="detail.change_type === 3">
                    <view class="add-item-content">
                        <view class="staff-card" v-if="detail.add_staff">
                            <image
                                :src="detail.add_staff.avatar"
                                class="staff-avatar-large"
                                mode="aspectFill"
                            />
                            <view class="staff-info-detail">
                                <view class="staff-name-large">{{ detail.add_staff.name }}</view>
                                <view class="text-gray-400 text-sm">{{
                                    detail.add_package_name
                                }}</view>
                                <view class="text-gray-400 text-sm">{{
                                    detail.add_service_date
                                }}</view>
                            </view>
                            <view class="add-price">
                                <text class="text-red-500 font-bold text-lg"
                                    >+¥{{ detail.add_price }}</text
                                >
                            </view>
                        </view>
                    </view>
                </template>
            </view>

            <!-- 申请原因 -->
            <view class="bg-white mt-3 p-4" v-if="detail.apply_reason">
                <view class="section-title">申请原因</view>
                <view class="text-gray-600 text-sm">{{ detail.apply_reason }}</view>
            </view>

            <!-- 附件图片 -->
            <view
                class="bg-white mt-3 p-4"
                v-if="detail.attach_images && detail.attach_images.length > 0"
            >
                <view class="section-title">附件图片</view>
                <view class="image-list">
                    <image
                        v-for="(img, index) in detail.attach_images"
                        :key="index"
                        :src="img"
                        class="attach-image"
                        mode="aspectFill"
                        @click="previewImage(index)"
                    />
                </view>
            </view>

            <!-- 审核结果 -->
            <view class="bg-white mt-3 p-4" v-if="detail.change_status >= 2">
                <view class="section-title">审核结果</view>
                <view class="info-row" v-if="detail.audit_remark">
                    <text class="label">审核备注</text>
                    <text class="value">{{ detail.audit_remark }}</text>
                </view>
                <view class="info-row" v-if="detail.reject_reason">
                    <text class="label">拒绝原因</text>
                    <text class="value text-red-500">{{ detail.reject_reason }}</text>
                </view>
            </view>

            <!-- 关联订单 -->
            <view class="bg-white mt-3 p-4" v-if="detail.order">
                <view class="section-title">关联订单</view>
                <view class="order-card" @click="goOrder(detail.order.id)">
                    <view class="flex justify-between items-center">
                        <text class="text-sm text-gray-500">{{ detail.order.order_sn }}</text>
                        <text class="text-sm text-primary">查看订单 ></text>
                    </view>
                    <view class="text-sm text-gray-600 mt-2">
                        服务日期: {{ detail.order.service_date }}
                    </view>
                </view>
            </view>

            <!-- 底部操作 -->
            <view class="bottom-actions" v-if="detail.change_status === 0">
                <button class="btn-cancel" @click="handleCancel">取消申请</button>
            </view>
        </template>
        <view v-else class="py-20 text-center text-gray-400"> 数据不存在 </view>
    </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { getChangeDetail, cancelChange } from '@/api/orderChange'

const loading = ref(true)
const detail = ref<any>(null)
const changeId = ref(0)

const getStatusBgClass = (status: number) => {
    const classes: Record<number, string> = {
        0: 'bg-orange',
        1: 'bg-blue',
        2: 'bg-red',
        3: 'bg-green',
        4: 'bg-gray'
    }
    return classes[status] || 'bg-gray'
}

const getStatusTip = (status: number) => {
    const tips: Record<number, string> = {
        0: '您的申请正在等待审核中',
        1: '审核已通过，等待执行中',
        2: '很抱歉，您的申请未通过审核',
        3: '变更已执行完成',
        4: '该申请已取消'
    }
    return tips[status] || ''
}

const getTypeClass = (type: number) => {
    const classes: Record<number, string> = {
        1: 'bg-blue-100 text-blue-600',
        2: 'bg-orange-100 text-orange-600',
        3: 'bg-green-100 text-green-600'
    }
    return classes[type] || 'bg-gray-100 text-gray-600'
}

const fetchDetail = async () => {
    loading.value = true
    try {
        const res = await getChangeDetail({ id: changeId.value })
        detail.value = res
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

const previewImage = (index: number) => {
    uni.previewImage({
        current: index,
        urls: detail.value.attach_images
    })
}

const goOrder = (orderId: number) => {
    uni.navigateTo({ url: `/pages/order_detail/order_detail?id=${orderId}` })
}

const handleCancel = async () => {
    const res = await uni.showModal({
        title: '提示',
        content: '确定要取消该变更申请吗？'
    })
    if (res.confirm) {
        try {
            await cancelChange({ id: changeId.value })
            uni.showToast({ title: '已取消' })
            fetchDetail()
        } catch (e: any) {
            uni.showToast({ title: e.message || '操作失败', icon: 'none' })
        }
    }
}

onLoad((options: any) => {
    if (options.id) {
        changeId.value = Number(options.id)
        fetchDetail()
    }
})
</script>

<style lang="scss" scoped>
.change-detail {
    min-height: 100vh;
    background-color: #f5f5f5;
    padding-bottom: 120rpx;
}

.status-card {
    padding: 40rpx 30rpx;
    color: #fff;

    &.bg-orange {
        background: linear-gradient(135deg, #ff9500, #ff6b00);
    }
    &.bg-blue {
        background: linear-gradient(135deg, #007aff, #0056d6);
    }
    &.bg-red {
        background: linear-gradient(135deg, #ff3b30, #d63027);
    }
    &.bg-green {
        background: linear-gradient(135deg, #34c759, #28a745);
    }
    &.bg-gray {
        background: linear-gradient(135deg, #8e8e93, #636366);
    }
}

.section-title {
    font-size: 28rpx;
    font-weight: bold;
    color: #333;
    margin-bottom: 20rpx;
    padding-left: 16rpx;
    border-left: 6rpx solid var(--primary-color, #ff6b35);
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16rpx 0;
    border-bottom: 1rpx solid #f5f5f5;

    &:last-child {
        border-bottom: none;
    }

    .label {
        color: #999;
        font-size: 26rpx;
    }

    .value {
        color: #333;
        font-size: 26rpx;
    }
}

.tag {
    display: inline-block;
    padding: 4rpx 16rpx;
    font-size: 22rpx;
    border-radius: 6rpx;
}

.change-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20rpx 0;
}

.change-item {
    flex: 1;
    text-align: center;
}

.change-label {
    font-size: 24rpx;
    color: #999;
    margin-bottom: 10rpx;
}

.change-value {
    font-size: 28rpx;
    font-weight: bold;

    &.old {
        color: #999;
    }
    &.new {
        color: var(--primary-color, #ff6b35);
    }
}

.change-arrow {
    padding: 0 20rpx;
}

.staff-info {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.staff-avatar {
    width: 80rpx;
    height: 80rpx;
    border-radius: 50%;
    margin-bottom: 10rpx;
}

.staff-name {
    font-size: 26rpx;
    color: #333;
}

.change-price {
    font-size: 24rpx;
    color: #999;
    margin-top: 10rpx;
}

.add-item-content {
    padding: 20rpx 0;
}

.staff-card {
    display: flex;
    align-items: center;
    padding: 20rpx;
    background: #f9f9f9;
    border-radius: 12rpx;
}

.staff-avatar-large {
    width: 120rpx;
    height: 120rpx;
    border-radius: 12rpx;
    margin-right: 20rpx;
}

.staff-info-detail {
    flex: 1;
}

.staff-name-large {
    font-size: 30rpx;
    font-weight: bold;
    color: #333;
    margin-bottom: 6rpx;
}

.add-price {
    text-align: right;
}

.image-list {
    display: flex;
    flex-wrap: wrap;
    gap: 16rpx;
}

.attach-image {
    width: 160rpx;
    height: 160rpx;
    border-radius: 8rpx;
}

.order-card {
    padding: 20rpx;
    background: #f9f9f9;
    border-radius: 12rpx;
}

.bottom-actions {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 20rpx 30rpx;
    background: #fff;
    box-shadow: 0 -2rpx 10rpx rgba(0, 0, 0, 0.05);
}

.btn-cancel {
    width: 100%;
    height: 88rpx;
    line-height: 88rpx;
    background: #fff;
    border: 1rpx solid #ff3b30;
    color: #ff3b30;
    border-radius: 44rpx;
    font-size: 30rpx;
}
</style>
