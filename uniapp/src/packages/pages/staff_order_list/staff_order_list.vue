<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="订单管理"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>

    <view class="page-container">
        <z-paging ref="pagingRef" v-model="orderList" @query="queryList" :auto="false">
            <template #top>
                <!-- 状态标签页 -->
                <view class="tabs-wrapper">
                    <scroll-view scroll-x class="tabs-scroll">
                        <view class="tabs-container">
                            <view
                                v-for="(tab, index) in statusTabs"
                                :key="index"
                                class="tab-item"
                                :class="{ active: currentTabIndex === index }"
                                :style="
                                    currentTabIndex === index
                                        ? {
                                              color: $theme.primaryColor,
                                              borderBottomColor: $theme.primaryColor
                                          }
                                        : {}
                                "
                                @click="switchTab(index)"
                            >
                                <text class="tab-text">{{ tab.label }}</text>
                                <view
                                    v-if="currentTabIndex === index"
                                    class="tab-indicator"
                                    :style="{ background: $theme.primaryColor }"
                                />
                            </view>
                        </view>
                    </scroll-view>
                </view>
            </template>

            <!-- 订单列表 -->
            <view class="order-list">
                <view
                    v-for="order in orderList"
                    :key="order.id"
                    class="order-card"
                    @click="goDetail(order.id)"
                >
                    <!-- 订单头部 -->
                    <view class="order-header">
                        <view class="order-no">
                            <tn-icon name="order" size="28" :color="$theme.primaryColor" />
                            <text>{{ order.orderNo }}</text>
                        </view>
                        <view class="order-status" :style="getStatusStyle(order.status)">
                            {{ getStatusText(order.status) }}
                        </view>
                    </view>

                    <!-- 订单项目 -->
                    <view v-for="item in order.items" :key="item.id" class="order-item">
                        <image class="staff-avatar" :src="item.staffAvatar" mode="aspectFill" />
                        <view class="item-info">
                            <view class="item-name">{{ item.packageName }}</view>
                            <view class="item-detail">
                                <tn-icon name="calendar" size="24" color="#999999" />
                                <text>{{ item.serviceDate }} {{ item.timeSlotDesc }}</text>
                            </view>
                            <view class="item-detail">
                                <tn-icon name="map-pin" size="24" color="#999999" />
                                <text>{{ order.location }}</text>
                            </view>
                        </view>
                    </view>

                    <!-- 订单金额 -->
                    <view class="order-price">
                        <view class="price-label">实付金额</view>
                        <view class="price-value" :style="{ color: $theme.ctaColor }">
                            <text class="price-symbol">¥</text>
                            <text class="price-number">{{ order.actualPrice }}</text>
                        </view>
                    </view>

                    <!-- 订单操作 -->
                    <view v-if="order.actions && order.actions.length > 0" class="order-actions">
                        <view
                            v-for="(action, idx) in order.actions"
                            :key="idx"
                            class="action-btn"
                            :style="{
                                background:
                                    action.type === 'primary'
                                        ? `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`
                                        : 'transparent',
                                color:
                                    action.type === 'primary'
                                        ? $theme.btnColor
                                        : $theme.primaryColor,
                                borderColor: $theme.primaryColor
                            }"
                            @click.stop="handleAction(action, order)"
                        >
                            {{ action.text }}
                        </view>
                    </view>
                </view>

                <!-- 空状态 -->
                <view v-if="orderList.length === 0" class="empty-state">
                    <tn-icon name="order" size="120" color="#E5E5E5" />
                    <text class="empty-text">暂无订单</text>
                </view>
            </view>
        </z-paging>
    </view>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { staffCenterOrderLists, staffCenterOrderConfirm } from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/utils/staff-center'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()
const pagingRef = ref<any>(null)
const orderList = ref<any[]>([])

const statusTabs = [
    { label: '全部', value: '' },
    { label: '待确认', value: 0 },
    { label: '待支付', value: 1 },
    { label: '已支付', value: 2 },
    { label: '服务中', value: 3 },
    { label: '已完成', value: 4 },
    { label: '已评价', value: 5 },
    { label: '已取消', value: 6 },
    { label: '已暂停', value: 7 },
    { label: '已退款', value: 8 }
]

const currentTabIndex = ref(0)
const currentStatus = computed(() => statusTabs[currentTabIndex.value].value)

// 状态映射
const getStatusKey = (status: number) => {
    const statusMap: Record<number, string> = {
        0: 'pending_confirm',
        1: 'pending_pay',
        2: 'paid',
        3: 'in_service',
        4: 'completed',
        5: 'reviewed',
        6: 'cancelled',
        7: 'paused',
        8: 'refunded'
    }
    return statusMap[status] || 'pending_pay'
}

// 获取状态样式
const getStatusStyle = (status: string) => {
    const styles: Record<string, any> = {
        pending_confirm: { background: 'rgba(255, 153, 0, 0.1)', color: '#FF9900' },
        pending_pay: { background: `${$theme.ctaColor}15`, color: $theme.ctaColor },
        paid: { background: 'rgba(25, 190, 107, 0.1)', color: '#19BE6B' },
        in_service: { background: `${$theme.primaryColor}15`, color: $theme.primaryColor },
        completed: { background: 'rgba(153, 153, 153, 0.1)', color: '#999999' },
        reviewed: { background: 'rgba(153, 153, 153, 0.1)', color: '#999999' },
        cancelled: { background: 'rgba(255, 44, 60, 0.1)', color: '#FF2C3C' },
        paused: { background: 'rgba(255, 153, 0, 0.1)', color: '#FF9900' },
        refunded: { background: 'rgba(255, 44, 60, 0.1)', color: '#FF2C3C' }
    }
    return styles[status] || styles.pending_pay
}

// 获取状态文本
const getStatusText = (status: string) => {
    const texts: Record<string, string> = {
        pending_confirm: '待确认',
        pending_pay: '待支付',
        paid: '已支付',
        in_service: '服务中',
        completed: '已完成',
        reviewed: '已评价',
        cancelled: '已取消',
        paused: '已暂停',
        refunded: '已退款'
    }
    return texts[status] || '未知'
}

// 格式化订单数据
const formatOrder = (order: any) => {
    const orderStatus = Number(order.order_status ?? order.status ?? -1)
    const discount = Number(order.discount_amount || 0) + Number(order.coupon_amount || 0)
    const hasPendingConfirm = (order.items || []).some(
        (item: any) => Number(item.confirm_status ?? 0) === 0
    )
    return {
        id: order.id,
        orderNo: order.order_sn,
        status: getStatusKey(orderStatus),
        createTime: order.create_time,
        location: order.service_address || '服务地址未填写',
        originalPrice: Number(order.total_amount || 0),
        discount,
        actualPrice: Number(order.pay_amount || 0),
        items: (order.items || []).map((item: any) => ({
            id: item.id,
            staffId: item.staff_id,
            staffName: item.staff_name || item.staff?.name || '服务人员',
            staffAvatar: item.staff?.avatar || '/static/images/user/default_avatar.png',
            packageName: item.package_name,
            serviceDate: item.service_date,
            timeSlot: item.time_slot,
            timeSlotDesc: item.time_slot_desc
        })),
        actions:
            orderStatus === 0 && hasPendingConfirm
                ? [{ text: '确认订单', type: 'primary', action: 'confirm' }]
                : []
    }
}

// 切换标签
const switchTab = (index: number) => {
    currentTabIndex.value = index
}

// 查询列表
const queryList = async (pageNo: number, pageSize: number) => {
    try {
        const params: any = { page: pageNo, page_size: pageSize }
        if (currentStatus.value !== '') {
            params.status = currentStatus.value
        }
        const res: any = await staffCenterOrderLists(params)
        const list = Array.isArray(res?.data) ? res.data : []
        pagingRef.value.complete(list.map(formatOrder))
    } catch (e) {
        pagingRef.value.complete(false)
    }
}

// 跳转详情
const goDetail = (id: number) => {
    uni.navigateTo({ url: `/packages/pages/staff_order_detail/staff_order_detail?id=${id}` })
}

// 处理操作
const handleAction = (action: { action: string }, order: any) => {
    if (action.action === 'confirm') {
        confirmOrder(order)
    }
}

// 确认订单
const confirmOrder = (order: any) => {
    uni.showModal({
        title: '确认订单',
        content: '确认后客户可进行支付，是否继续？',
        success: async (res) => {
            if (!res.confirm) return
            try {
                await staffCenterOrderConfirm({ id: order.id })
                uni.showToast({ title: '确认成功', icon: 'success' })
                pagingRef.value?.reload()
            } catch (e: any) {
                const msg = typeof e === 'string' ? e : e?.msg || e?.message || '确认失败'
                uni.showToast({ title: msg, icon: 'none' })
            }
        }
    })
}

watch(currentTabIndex, () => {
    pagingRef.value?.reload()
})

onShow(async () => {
    if (!(await ensureStaffCenterAccess())) return
    pagingRef.value?.reload()
})
</script>

<style lang="scss" scoped>
.page-container {
    min-height: 100vh;
    background: linear-gradient(180deg, rgba(124, 58, 237, 0.05) 0%, #f6f6f6 100%);
}

/* 标签页 */
.tabs-wrapper {
    background: #ffffff;
    box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.04);
}

.tabs-scroll {
    white-space: nowrap;
}

.tabs-container {
    display: inline-flex;
    padding: 0 12rpx;
}

.tab-item {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 28rpx 24rpx;
    color: #666666;
    font-size: 28rpx;
    transition: all 0.2s ease;

    &.active {
        font-weight: 600;
    }
}

.tab-text {
    white-space: nowrap;
}

.tab-indicator {
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 40rpx;
    height: 6rpx;
    border-radius: 3rpx;
}

/* 订单列表 */
.order-list {
    padding: 24rpx;
}

.order-card {
    margin-bottom: 24rpx;
    padding: 24rpx;
    background: #ffffff;
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

/* 订单头部 */
.order-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 20rpx;
    border-bottom: 1rpx solid #f5f5f5;
    margin-bottom: 20rpx;
}

.order-no {
    display: flex;
    align-items: center;
    gap: 8rpx;
    font-size: 26rpx;
    color: #666666;
}

.order-status {
    padding: 6rpx 16rpx;
    border-radius: 24rpx;
    font-size: 24rpx;
    font-weight: 500;
}

/* 订单项目 */
.order-item {
    display: flex;
    gap: 16rpx;
    padding: 16rpx 0;
}

.staff-avatar {
    width: 96rpx;
    height: 96rpx;
    border-radius: 48rpx;
    background: #f5f5f5;
}

.item-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.item-name {
    font-size: 30rpx;
    font-weight: 500;
    color: #333333;
}

.item-detail {
    display: flex;
    align-items: center;
    gap: 8rpx;
    font-size: 24rpx;
    color: #999999;
}

/* 订单金额 */
.order-price {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20rpx 0;
    border-top: 1rpx solid #f5f5f5;
    margin-top: 16rpx;
}

.price-label {
    font-size: 28rpx;
    color: #666666;
}

.price-value {
    display: flex;
    align-items: baseline;
    gap: 4rpx;
}

.price-symbol {
    font-size: 24rpx;
    font-weight: 600;
}

.price-number {
    font-size: 40rpx;
    font-weight: 700;
}

/* 订单操作 */
.order-actions {
    display: flex;
    gap: 16rpx;
    justify-content: flex-end;
    margin-top: 20rpx;
}

.action-btn {
    padding: 12rpx 32rpx;
    border-radius: 48rpx;
    font-size: 26rpx;
    font-weight: 500;
    border: 2rpx solid;
    transition: all 0.2s ease;

    &:active {
        opacity: 0.8;
    }
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
</style>
