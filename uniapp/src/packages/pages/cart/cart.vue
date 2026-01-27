<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar 
            title="购物车"
            :front-color="$theme.navColor" 
            :background-color="$theme.navBgColor" 
        />
        <!-- #endif -->
    </page-meta>

    <view class="cart-page">
        <!-- 空状态 -->
        <view class="empty-state" v-if="!loading && cartData.items.length === 0">
            <tn-icon name="shopping-cart" size="160" color="#CCCCCC" />
            <text class="empty-title">购物车是空的</text>
            <text class="empty-desc">快去挑选心仪的服务吧</text>
            <view 
                class="btn-browse" 
                :style="{ 
                    background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
                    color: $theme.btnColor
                }"
                @click="goBrowse"
            >
                <tn-icon name="search" size="32" :color="$theme.btnColor" />
                <text>开始选购</text>
            </view>
        </view>

        <!-- 购物车内容 -->
        <view class="cart-content" v-else>
            <!-- 档期冲突提示 -->
            <view class="alert-card alert-warning" v-if="cartData.conflicts.length > 0">
                <tn-icon name="warning" size="40" color="#FA8C16" />
                <view class="alert-content">
                    <text class="alert-title">档期冲突提醒</text>
                    <text class="alert-desc">{{ cartData.conflicts.length }}个项目存在时间冲突，请调整</text>
                </view>
            </view>

            <!-- 套餐冲突提示 -->
            <view class="alert-card alert-info" v-if="packageConflicts.length > 0">
                <tn-icon name="info-circle" size="40" color="#1890FF" />
                <view class="alert-content">
                    <text class="alert-title">预订冲突提醒</text>
                    <text class="alert-desc">{{ packageConflicts.length }}个套餐当日已被预订</text>
                    <view class="alert-link" @click="showConflictDetail = !showConflictDetail">
                        <text>{{ showConflictDetail ? '收起详情' : '查看详情' }}</text>
                        <tn-icon :name="showConflictDetail ? 'arrow-up' : 'arrow-down'" size="24" />
                    </view>
                </view>
            </view>

            <!-- 冲突详情列表 -->
            <view class="conflict-details" v-if="showConflictDetail && packageConflicts.length > 0">
                <view class="conflict-item" v-for="conflict in packageConflicts" :key="conflict.package_id">
                    <view class="conflict-info">
                        <tn-icon name="alert-circle" size="32" color="#FF2C3C" />
                        <text class="conflict-name">{{ conflict.package_name }}</text>
                    </view>
                    <text class="conflict-date">{{ conflict.date }}</text>
                </view>
            </view>

            <!-- 全选栏 -->
            <view class="select-bar">
                <view class="select-all" @click="handleSelectAll">
                    <view 
                        class="checkbox" 
                        :class="{ checked: isAllSelected }"
                        :style="isAllSelected ? { 
                            background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
                            borderColor: $theme.primaryColor
                        } : {}"
                    >
                        <tn-icon v-if="isAllSelected" name="check" size="28" color="#FFFFFF" />
                    </view>
                    <text class="select-text">全选</text>
                </view>
                <text class="item-count">共 {{ cartData.items.length }} 项</text>
            </view>

            <!-- 购物车列表 -->
            <view class="cart-list">
                <view 
                    class="cart-card" 
                    v-for="item in cartData.items" 
                    :key="item.id"
                    :class="{ unavailable: !item.is_available }"
                >
                    <!-- 选择框 -->
                    <view 
                        class="checkbox" 
                        :class="{ checked: item.is_selected }"
                        :style="item.is_selected ? { 
                            background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
                            borderColor: $theme.primaryColor
                        } : {}"
                        @click="handleToggleSelect(item)"
                    >
                        <tn-icon v-if="item.is_selected" name="check" size="28" color="#FFFFFF" />
                    </view>

                    <!-- 卡片内容 -->
                    <view class="card-content">
                        <!-- 服务人员信息 -->
                        <view class="staff-section">
                            <image :src="item.staff?.avatar" class="staff-avatar" mode="aspectFill" />
                            <view class="staff-info">
                                <text class="staff-name">{{ item.staff?.name || '未知人员' }}</text>
                                <view class="package-tag" v-if="item.package">
                                    <tn-icon name="gift" size="24" />
                                    <text>{{ item.package.name }}</text>
                                </view>
                            </view>
                        </view>

                        <!-- 预约信息 -->
                        <view class="booking-info">
                            <view class="info-item">
                                <tn-icon name="calendar" size="32" color="#52C41A" />
                                <view class="info-text">
                                    <text class="info-label">预约日期</text>
                                    <text class="info-value">{{ item.schedule_date }}</text>
                                </view>
                            </view>
                            <view class="info-item">
                                <tn-icon name="clock" size="32" color="#52C41A" />
                                <view class="info-text">
                                    <text class="info-label">时间段</text>
                                    <text class="info-value">{{ item.time_slot_desc }}</text>
                                </view>
                            </view>
                        </view>

                        <!-- 不可用提示 -->
                        <view class="unavailable-badge" v-if="!item.is_available">
                            <tn-icon name="close-circle" size="28" color="#FF2C3C" />
                            <text>档期已不可用</text>
                        </view>

                        <!-- 底部操作栏 -->
                        <view class="card-footer">
                            <view class="price-section">
                                <text class="price-symbol" :style="{ color: $theme.ctaColor }">¥</text>
                                <text class="price-value" :style="{ color: $theme.ctaColor }">{{ item.price }}</text>
                            </view>
                            <view class="delete-btn" @click="handleDelete(item)">
                                <tn-icon name="delete" size="28" color="#999999" />
                                <text class="delete-text">删除</text>
                            </view>
                        </view>
                    </view>
                </view>
            </view>

            <!-- 方案操作栏 -->
            <view class="action-bar">
                <view class="action-btn" @click="handleSavePlan">
                    <tn-icon name="folder-add" size="48" :color="$theme.primaryColor" />
                    <text class="action-text">保存方案</text>
                </view>
                <view class="action-btn" @click="goMyPlans">
                    <tn-icon name="folder" size="48" :color="$theme.primaryColor" />
                    <text class="action-text">我的方案</text>
                </view>
                <view class="action-btn" @click="handleClearCart">
                    <tn-icon name="delete" size="48" color="#FF2C3C" />
                    <text class="action-text">清空</text>
                </view>
            </view>
        </view>

        <!-- 底部结算栏 -->
        <view class="checkout-bar" v-if="cartData.items.length > 0">
            <view class="checkout-info">
                <view class="selected-count">
                    <tn-icon name="check-circle" size="28" :color="$theme.primaryColor" />
                    <text>已选 {{ cartData.selected_count }} 项</text>
                </view>
                <view class="total-section">
                    <text class="total-label">合计</text>
                    <text class="total-symbol" :style="{ color: $theme.ctaColor }">¥</text>
                    <text class="total-value" :style="{ color: $theme.ctaColor }">{{ cartData.total_price }}</text>
                </view>
            </view>
            <view 
                class="checkout-btn"
                :class="{ disabled: cartData.selected_count === 0 || cartData.conflicts.length > 0 || packageConflicts.length > 0 }"
                :style="(cartData.selected_count === 0 || cartData.conflicts.length > 0 || packageConflicts.length > 0) ? {} : {
                    background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`
                }"
                @click="handleCheckout"
            >
                <text>去结算</text>
            </view>
        </view>

        <!-- 保存方案弹窗 -->
        <tn-popup v-model="showPlanPopup" mode="center" :border-radius="24" :mask-close-able="false">
            <view class="plan-modal">
                <text class="modal-title">保存为方案</text>
                <text class="modal-desc">为您的预约方案起个名字</text>
                <tn-input
                    v-model="planName"
                    placeholder="例如：婚礼策划方案"
                    :maxlength="50"
                    :focus="showPlanPopup"
                    :border="true"
                    :clearable="true"
                    height="80"
                />
                <view class="modal-actions">
                    <view class="modal-btn btn-cancel" @click="closePlanPopup">取消</view>
                    <view 
                        class="modal-btn btn-confirm" 
                        :style="{ 
                            background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`
                        }"
                        @click="confirmSavePlan"
                    >确定</view>
                </view>
            </view>
        </tn-popup>
    </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import {
    getCartList,
    deleteCartItem,
    toggleCartSelect,
    selectAllCart,
    clearCart,
    saveCartPlan
} from '@/api/cart'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()

// 获取主题色浅色变体
const getColor = (type: string) => {
    const colors: Record<string, string> = {
        'primary-light-9': '#F3E8FF',
        'primary-light-7': '#D8B4FE',
        'cta-light-9': '#FFF7E6'
    }
    return colors[type] || '#F5F5F5'
}

const loading = ref(false)
const cartData = ref<any>({
    items: [],
    total_price: 0,
    total_count: 0,
    conflicts: [],
    package_conflicts: [],
    selected_count: 0
})

const showPlanPopup = ref(false)
const planName = ref('')
const showConflictDetail = ref(false)

// 套餐预订冲突列表
const packageConflicts = computed(() => {
    return cartData.value.package_conflicts || []
})

// 是否全选
const isAllSelected = computed(() => {
    return (
        cartData.value.items.length > 0 &&
        cartData.value.selected_count === cartData.value.items.length
    )
})

// 获取购物车数据
const fetchCart = async () => {
    loading.value = true
    try {
        const res = await getCartList()
        cartData.value = res
    } finally {
        loading.value = false
    }
}

// 切换选中
const handleToggleSelect = async (item: any) => {
    await toggleCartSelect({ id: item.id })
    fetchCart()
}

// 全选
const handleSelectAll = async () => {
    await selectAllCart({ selected: !isAllSelected.value })
    fetchCart()
}

// 删除
const handleDelete = (item: any) => {
    uni.showModal({
        title: '删除确认',
        content: '确定要删除该项吗？',
        confirmColor: '#FF2C3C',
        success: async (res) => {
            if (res.confirm) {
                await deleteCartItem({ id: item.id })
                uni.showToast({ title: '删除成功', icon: 'success' })
                fetchCart()
            }
        }
    })
}

// 清空购物车
const handleClearCart = () => {
    uni.showModal({
        title: '清空购物车',
        content: '确定要清空购物车吗？',
        confirmColor: '#FF2C3C',
        success: async (res) => {
            if (res.confirm) {
                await clearCart()
                uni.showToast({ title: '已清空', icon: 'success' })
                fetchCart()
            }
        }
    })
}

// 保存方案
const handleSavePlan = () => {
    if (cartData.value.selected_count === 0) {
        uni.showToast({ title: '请先选择项目', icon: 'none' })
        return
    }
    planName.value = ''
    showPlanPopup.value = true
}

const closePlanPopup = () => {
    showPlanPopup.value = false
}

const confirmSavePlan = async () => {
    if (!planName.value.trim()) {
        uni.showToast({ title: '请输入方案名称', icon: 'none' })
        return
    }

    const selectedIds = cartData.value.items
        .filter((item: any) => item.is_selected)
        .map((item: any) => item.id)

    try {
        await saveCartPlan({
            plan_name: planName.value,
            cart_ids: selectedIds
        })

        planName.value = ''
        showPlanPopup.value = false
        uni.showToast({ title: '保存成功', icon: 'success' })
    } catch (e: any) {
        console.error('保存方案失败:', e)
        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '保存失败'
        uni.showToast({ title: errorMsg, icon: 'none' })
    }
}

// 去结算
const handleCheckout = () => {
    if (cartData.value.selected_count === 0) {
        uni.showToast({ title: '请先选择项目', icon: 'none' })
        return
    }
    if (cartData.value.conflicts.length > 0) {
        uni.showToast({ title: '请先处理档期冲突', icon: 'none' })
        return
    }
    if (packageConflicts.value.length > 0) {
        uni.showToast({ title: '存在套餐预订冲突，请处理', icon: 'none' })
        return
    }
    uni.navigateTo({ url: '/packages/pages/order_confirm/order_confirm' })
}

// 去逛逛
const goBrowse = () => {
    uni.navigateTo({ url: '/packages/pages/staff_list/staff_list' })
}

// 我的方案
const goMyPlans = () => {
    uni.navigateTo({ url: '/packages/pages/cart_plan/cart_plan' })
}

onShow(() => {
    fetchCart()
})
</script>

<style lang="scss" scoped>
.cart-page {
    min-height: 100vh;
    background: #F5F5F5;
    padding-bottom: 180rpx;
}

/* 空状态 */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 200rpx 48rpx;

    .empty-title {
        font-size: 32rpx;
        font-weight: 600;
        color: #333333;
        margin-top: 32rpx;
        margin-bottom: 16rpx;
    }

    .empty-desc {
        font-size: 26rpx;
        color: #999999;
        margin-bottom: 48rpx;
    }

    .btn-browse {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12rpx;
        font-size: 32rpx;
        font-weight: 700;
        padding: 32rpx 80rpx;
        border-radius: 64rpx;
        box-shadow: 0 16rpx 48rpx rgba(124, 58, 237, 0.4),
                    0 8rpx 24rpx rgba(124, 58, 237, 0.2);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;

        /* 光泽效果 */
        &::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent 0%, 
                rgba(255, 255, 255, 0.3) 50%, 
                transparent 100%);
            transition: left 0.6s ease;
        }

        &:active {
            transform: translateY(4rpx) scale(0.98);
            box-shadow: 0 8rpx 24rpx rgba(124, 58, 237, 0.3),
                        0 4rpx 12rpx rgba(124, 58, 237, 0.15);
        }

        /* 悬停时的光泽动画 */
        &:hover::before {
            left: 100%;
        }
    }
}

/* 购物车内容 */
.cart-content {
    padding: 24rpx;
}

/* 提示卡片 */
.alert-card {
    display: flex;
    align-items: flex-start;
    gap: 16rpx;
    padding: 24rpx;
    border-radius: 16rpx;
    margin-bottom: 24rpx;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);

    .alert-content {
        flex: 1;

        .alert-title {
            display: block;
            font-size: 28rpx;
            font-weight: 600;
            margin-bottom: 8rpx;
        }

        .alert-desc {
            display: block;
            font-size: 26rpx;
            opacity: 0.8;
            margin-bottom: 12rpx;
        }

        .alert-link {
            display: flex;
            align-items: center;
            gap: 8rpx;
            font-size: 26rpx;
            color: inherit;
            margin-top: 12rpx;
        }
    }

    &.alert-warning {
        background: #FFF7E6;
        color: #FA8C16;
    }

    &.alert-info {
        background: #E6F7FF;
        color: #1890FF;
    }
}

/* 冲突详情 */
.conflict-details {
    background: #FFFFFF;
    border-radius: 16rpx;
    padding: 24rpx;
    margin-bottom: 24rpx;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);

    .conflict-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20rpx 0;
        border-bottom: 1rpx solid #F0F0F0;

        &:last-child {
            border-bottom: none;
        }

        .conflict-info {
            display: flex;
            align-items: center;
            gap: 12rpx;
            flex: 1;

            .conflict-name {
                font-size: 28rpx;
                color: #333333;
                font-weight: 500;
            }
        }

        .conflict-date {
            font-size: 26rpx;
            color: #FF2C3C;
            font-weight: 500;
        }
    }
}

/* 全选栏 */
.select-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fff;
    padding: 24rpx;
    border-radius: 16rpx;
    margin-bottom: 24rpx;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);

    .select-all {
        display: flex;
        align-items: center;
        cursor: pointer;

        .select-text {
            font-size: 28rpx;
            color: #333;
            font-weight: 500;
        }
    }

    .item-count {
        font-size: 26rpx;
        color: #999;
    }
}

/* 复选框 */
.checkbox {
    width: 44rpx;
    height: 44rpx;
    border: 3rpx solid #ddd;
    border-radius: 50%;
    margin-right: 20rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 200ms ease;
    cursor: pointer;

    .check-icon {
        color: #fff;
        font-size: 28rpx;
        font-weight: bold;
    }
}

/* 购物车列表 */
.cart-list {
    display: flex;
    flex-direction: column;
    gap: 24rpx;
}

/* 购物车卡片 */
.cart-card {
    display: flex;
    background: #fff;
    border-radius: 20rpx;
    padding: 24rpx;
    box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.08);
    transition: all 200ms ease;

    &.unavailable {
        opacity: 0.5;
    }

    .card-content {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    /* 服务人员区域 */
    .staff-section {
        display: flex;
        align-items: center;
        margin-bottom: 20rpx;

        .staff-avatar {
            width: 96rpx;
            height: 96rpx;
            border-radius: 16rpx;
            margin-right: 20rpx;
            flex-shrink: 0;
        }

        .staff-info {
            flex: 1;

            .staff-name {
                display: block;
                font-size: 32rpx;
                font-weight: 600;
                color: #333;
                margin-bottom: 8rpx;
            }

            .package-name {
                display: block;
                font-size: 26rpx;
                color: #7c3aed;
                background: #f3e8ff;
                padding: 6rpx 16rpx;
                border-radius: 8rpx;
                display: inline-block;
            }
        }
    }

    /* 预约信息 */
    .booking-info {
        background: #f8f9fa;
        border-radius: 12rpx;
        padding: 20rpx;
        margin-bottom: 20rpx;

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12rpx;

            &:last-child {
                margin-bottom: 0;
            }

            .info-label {
                font-size: 26rpx;
                color: #666;
            }

            .info-value {
                font-size: 26rpx;
                color: #333;
                font-weight: 500;

                &.time-slot {
                    background: #7c3aed;
                    color: #fff;
                    padding: 6rpx 16rpx;
                    border-radius: 8rpx;
                }
            }
        }
    }

    /* 不可用标签 */
    .unavailable-badge {
        background: #fee;
        color: #dc3545;
        font-size: 24rpx;
        padding: 8rpx 16rpx;
        border-radius: 8rpx;
        text-align: center;
        margin-bottom: 20rpx;
    }

    /* 卡片底部 */
    .card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 20rpx;
        border-top: 1rpx solid #f0f0f0;

        .price-section {
            display: flex;
            align-items: baseline;

            .price-symbol {
                font-size: 28rpx;
                font-weight: 600;
                margin-right: 4rpx;
            }

            .price-value {
                font-size: 40rpx;
                font-weight: 700;
            }
        }

        .delete-btn {
            padding: 12rpx 24rpx;
            background: #f5f5f5;
            border-radius: 8rpx;
            cursor: pointer;
            transition: all 200ms ease;

            &:active {
                background: #e0e0e0;
            }

            .delete-text {
                font-size: 26rpx;
                color: #666;
            }
        }
    }
}

/* 操作栏 */
.action-bar {
    display: flex;
    justify-content: space-around;
    background: #fff;
    border-radius: 16rpx;
    padding: 32rpx 24rpx;
    margin-top: 24rpx;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);

    .action-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        cursor: pointer;
        transition: all 200ms ease;

        &:active {
            transform: scale(0.95);
        }

        .action-icon {
            font-size: 48rpx;
            margin-bottom: 12rpx;
        }

        .action-text {
            font-size: 24rpx;
            color: #666;
        }
    }
}

/* 底部结算栏 */
.checkout-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #fff;
    padding: 24rpx 32rpx;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 -4rpx 24rpx rgba(0, 0, 0, 0.1);
    padding-bottom: calc(24rpx + env(safe-area-inset-bottom));

    .checkout-info {
        flex: 1;

        .selected-count {
            font-size: 24rpx;
            color: #999;
            margin-bottom: 8rpx;
        }

        .total-section {
            display: flex;
            align-items: baseline;

            .total-label {
                font-size: 26rpx;
                color: #666;
                margin-right: 12rpx;
            }

            .total-symbol {
                font-size: 28rpx;
                font-weight: 600;
                margin-right: 4rpx;
            }

            .total-value {
                font-size: 44rpx;
                font-weight: 700;
            }
        }
    }

    .checkout-btn {
        color: #FFFFFF;
        font-size: 32rpx;
        font-weight: 700;
        padding: 28rpx 56rpx;
        border-radius: 56rpx;
        box-shadow: 0 12rpx 32rpx rgba(124, 58, 237, 0.4);
        transition: all 0.3s ease;
        min-width: 200rpx;
        text-align: center;
        line-height: 1.2;

        &:active:not(.disabled) {
            transform: translateY(2rpx);
            box-shadow: 0 6rpx 16rpx rgba(124, 58, 237, 0.4);
        }

        &.disabled {
            background: linear-gradient(135deg, #CCCCCC 0%, #AAAAAA 100%) !important;
            box-shadow: none;
            opacity: 0.6;
        }
    }
}

/* 保存方案弹窗 */
.plan-modal {
    background: #fff;
    padding: 40rpx 32rpx 32rpx;
    border-radius: 24rpx;
    width: 560rpx;
    box-shadow: 0 16rpx 48rpx rgba(0, 0, 0, 0.2);

    .modal-title {
        display: block;
        font-size: 32rpx;
        font-weight: 600;
        color: #333;
        text-align: center;
        margin-bottom: 12rpx;
    }

    .modal-desc {
        display: block;
        font-size: 24rpx;
        color: #999;
        text-align: center;
        margin-bottom: 24rpx;
    }

    .modal-actions {
        display: flex;
        gap: 16rpx;
        margin-top: 24rpx;

        .modal-btn {
            flex: 1;
            font-size: 28rpx;
            font-weight: 500;
            padding: 18rpx 24rpx;
            border-radius: 12rpx;
            border: none;
            transition: all 200ms ease;
            line-height: 1.4;

            &.btn-cancel {
                background: #f5f5f5;
                color: #666;

                &:active {
                    background: #e0e0e0;
                }
            }

            &.btn-confirm {
                color: #fff;
                box-shadow: 0 4rpx 12rpx rgba(124, 58, 237, 0.3);

                &:active {
                    transform: scale(0.98);
                }
            }
        }
    }
}
</style>
