<template>
    <view class="cart-page">
        <!-- 空状态 -->
        <view class="empty-state" v-if="!loading && cartData.items.length === 0">
            <image src="/static/images/empty_cart.png" class="empty-img" mode="aspectFit" />
            <text class="empty-text">购物车空空如也</text>
            <button class="btn-browse" @click="goBrowse">去逛逛</button>
        </view>

        <!-- 购物车列表 -->
        <view class="cart-list" v-else>
            <!-- 冲突提示 -->
            <view class="conflict-tip" v-if="cartData.conflicts.length > 0">
                <text class="iconfont icon-warning"></text>
                <text>{{ cartData.conflicts.length }}个项目档期已不可用，请处理</text>
            </view>

            <!-- 全选 -->
            <view class="select-all">
                <view class="check-box" :class="{ checked: isAllSelected }" @click="handleSelectAll">
                    <text class="iconfont icon-check" v-if="isAllSelected"></text>
                </view>
                <text>全选</text>
                <text class="count">({{ cartData.selected_count }}/{{ cartData.items.length }})</text>
            </view>

            <!-- 购物车项 -->
            <view 
                class="cart-item"
                v-for="item in cartData.items"
                :key="item.id"
                :class="{ unavailable: !item.is_available }"
            >
                <view class="check-box" :class="{ checked: item.is_selected }" @click="handleToggleSelect(item)">
                    <text class="iconfont icon-check" v-if="item.is_selected"></text>
                </view>
                
                <image :src="item.staff?.avatar" class="staff-avatar" mode="aspectFill" />
                
                <view class="item-info">
                    <view class="staff-name">{{ item.staff?.name || '未知' }}</view>
                    <view class="schedule-info">
                        <text>{{ item.schedule_date }}</text>
                        <text class="time-slot">{{ item.time_slot_desc }}</text>
                    </view>
                    <view class="package-name" v-if="item.package">{{ item.package.name }}</view>
                    <view class="unavailable-tip" v-if="!item.is_available">
                        <text class="iconfont icon-warning"></text>
                        档期已不可用
                    </view>
                </view>
                
                <view class="item-right">
                    <view class="price">¥{{ item.price }}</view>
                    <view class="delete-btn" @click="handleDelete(item)">
                        <text class="iconfont icon-delete"></text>
                    </view>
                </view>
            </view>
        </view>

        <!-- 方案操作 -->
        <view class="plan-actions" v-if="cartData.items.length > 0">
            <view class="action-item" @click="handleSavePlan">
                <text class="iconfont icon-save"></text>
                <text>保存方案</text>
            </view>
            <view class="action-item" @click="goMyPlans">
                <text class="iconfont icon-folder"></text>
                <text>我的方案</text>
            </view>
            <view class="action-item" @click="handleClearCart">
                <text class="iconfont icon-clear"></text>
                <text>清空购物车</text>
            </view>
        </view>

        <!-- 底部结算栏 -->
        <view class="bottom-bar" v-if="cartData.items.length > 0">
            <view class="total-info">
                <text>已选 {{ cartData.selected_count }} 项</text>
                <view class="total-price">
                    <text>合计: </text>
                    <text class="price">¥{{ cartData.total_price }}</text>
                </view>
            </view>
            <button 
                class="btn-checkout" 
                :disabled="cartData.selected_count === 0 || cartData.conflicts.length > 0"
                @click="handleCheckout"
            >
                去结算
            </button>
        </view>

        <!-- 保存方案弹窗 -->
        <uni-popup ref="planPopup" type="center">
            <view class="plan-popup">
                <view class="popup-title">保存为方案</view>
                <input 
                    class="plan-name-input" 
                    v-model="planName" 
                    placeholder="请输入方案名称"
                    maxlength="50"
                />
                <view class="popup-actions">
                    <button class="btn-cancel" @click="closePlanPopup">取消</button>
                    <button class="btn-confirm" @click="confirmSavePlan">确定</button>
                </view>
            </view>
        </uni-popup>
    </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { 
    getCartList, 
    deleteCartItem, 
    toggleCartSelect, 
    selectAllCart, 
    clearCart,
    saveCartPlan 
} from '@/api/cart'

const loading = ref(false)
const cartData = ref<any>({
    items: [],
    total_price: 0,
    total_count: 0,
    conflicts: [],
    selected_count: 0
})

const planPopup = ref()
const planName = ref('')

// 是否全选
const isAllSelected = computed(() => {
    return cartData.value.items.length > 0 && 
           cartData.value.selected_count === cartData.value.items.length
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
        title: '提示',
        content: '确定要删除该项吗？',
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
        title: '提示',
        content: '确定要清空购物车吗？',
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
    planPopup.value.open()
}

const closePlanPopup = () => {
    planPopup.value.close()
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
        uni.showToast({ title: '保存成功', icon: 'success' })
        closePlanPopup()
    } catch (e: any) {
        uni.showToast({ title: e.message || '保存失败', icon: 'none' })
    }
}

// 去结算
const handleCheckout = () => {
    if (cartData.value.conflicts.length > 0) {
        uni.showToast({ title: '请先处理不可用的项目', icon: 'none' })
        return
    }
    // 跳转到订单确认页
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
    background: #f5f5f5;
    padding-bottom: 140rpx;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding-top: 200rpx;
    
    .empty-img {
        width: 300rpx;
        height: 300rpx;
    }
    
    .empty-text {
        font-size: 28rpx;
        color: #999;
        margin-top: 20rpx;
    }
    
    .btn-browse {
        margin-top: 40rpx;
        background: #ff6b6b;
        color: #fff;
        font-size: 28rpx;
        padding: 16rpx 60rpx;
        border-radius: 40rpx;
    }
}

.conflict-tip {
    background: #fff3cd;
    padding: 20rpx 30rpx;
    display: flex;
    align-items: center;
    font-size: 26rpx;
    color: #856404;
    
    .iconfont {
        margin-right: 10rpx;
    }
}

.select-all {
    background: #fff;
    padding: 20rpx 30rpx;
    display: flex;
    align-items: center;
    font-size: 28rpx;
    
    .count {
        color: #999;
        margin-left: 10rpx;
    }
}

.check-box {
    width: 40rpx;
    height: 40rpx;
    border: 2rpx solid #ddd;
    border-radius: 50%;
    margin-right: 20rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    
    &.checked {
        background: #ff6b6b;
        border-color: #ff6b6b;
        
        .iconfont {
            color: #fff;
            font-size: 24rpx;
        }
    }
}

.cart-item {
    background: #fff;
    padding: 30rpx;
    display: flex;
    align-items: flex-start;
    border-bottom: 1rpx solid #f0f0f0;
    
    &.unavailable {
        opacity: 0.6;
    }
    
    .staff-avatar {
        width: 120rpx;
        height: 120rpx;
        border-radius: 8rpx;
        margin-right: 20rpx;
    }
    
    .item-info {
        flex: 1;
        
        .staff-name {
            font-size: 30rpx;
            font-weight: bold;
        }
        
        .schedule-info {
            font-size: 26rpx;
            color: #666;
            margin-top: 10rpx;
            
            .time-slot {
                margin-left: 20rpx;
                background: #f0f0f0;
                padding: 4rpx 12rpx;
                border-radius: 4rpx;
            }
        }
        
        .package-name {
            font-size: 24rpx;
            color: #999;
            margin-top: 8rpx;
        }
        
        .unavailable-tip {
            font-size: 24rpx;
            color: #ff4d4f;
            margin-top: 10rpx;
            
            .iconfont {
                margin-right: 6rpx;
            }
        }
    }
    
    .item-right {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        
        .price {
            font-size: 32rpx;
            color: #ff6b6b;
            font-weight: bold;
        }
        
        .delete-btn {
            margin-top: 20rpx;
            padding: 10rpx;
            
            .iconfont {
                font-size: 36rpx;
                color: #999;
            }
        }
    }
}

.plan-actions {
    background: #fff;
    margin-top: 20rpx;
    display: flex;
    padding: 20rpx 0;
    
    .action-item {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        font-size: 24rpx;
        color: #666;
        
        .iconfont {
            font-size: 40rpx;
            margin-bottom: 8rpx;
        }
    }
}

.bottom-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #fff;
    padding: 20rpx 30rpx;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 -2rpx 10rpx rgba(0, 0, 0, 0.1);
    padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
    
    .total-info {
        .total-price {
            margin-top: 8rpx;
            
            .price {
                font-size: 36rpx;
                color: #ff6b6b;
                font-weight: bold;
            }
        }
    }
    
    .btn-checkout {
        background: #ff6b6b;
        color: #fff;
        font-size: 30rpx;
        padding: 20rpx 60rpx;
        border-radius: 40rpx;
        
        &[disabled] {
            background: #ccc;
        }
    }
}

.plan-popup {
    background: #fff;
    padding: 40rpx;
    border-radius: 16rpx;
    width: 600rpx;
    
    .popup-title {
        font-size: 32rpx;
        font-weight: bold;
        text-align: center;
        margin-bottom: 30rpx;
    }
    
    .plan-name-input {
        border: 1rpx solid #ddd;
        padding: 20rpx;
        border-radius: 8rpx;
        font-size: 28rpx;
    }
    
    .popup-actions {
        display: flex;
        margin-top: 40rpx;
        gap: 20rpx;
        
        button {
            flex: 1;
            font-size: 28rpx;
            padding: 20rpx;
            border-radius: 8rpx;
            
            &.btn-cancel {
                background: #f5f5f5;
                color: #666;
            }
            
            &.btn-confirm {
                background: #ff6b6b;
                color: #fff;
            }
        }
    }
}
</style>
