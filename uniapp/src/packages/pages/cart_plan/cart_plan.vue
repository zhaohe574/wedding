<template>
    <view class="cart-plan-page">
        <!-- 空状态 -->
        <view class="empty-state" v-if="!loading && planList.length === 0">
            <text class="empty-text">暂无保存的方案</text>
        </view>

        <!-- 方案列表 -->
        <view class="plan-list">
            <view 
                class="plan-item"
                v-for="plan in planList"
                :key="plan.id"
            >
                <view class="plan-header">
                    <view class="plan-name">
                        <text>{{ plan.plan_name }}</text>
                        <text class="default-tag" v-if="plan.is_default">默认</text>
                    </view>
                    <view class="plan-price">¥{{ plan.actual_total_price || plan.total_price }}</view>
                </view>
                
                <view class="plan-items">
                    <view class="item-row" v-for="item in plan.cart_items?.slice(0, 3)" :key="item.id">
                        <image :src="item.staff?.avatar" class="mini-avatar" mode="aspectFill" />
                        <text class="item-name">{{ item.staff?.name }}</text>
                        <text class="item-date">{{ item.schedule_date }}</text>
                    </view>
                    <view class="more-items" v-if="plan.cart_items?.length > 3">
                        +{{ plan.cart_items.length - 3 }} 更多
                    </view>
                </view>
                
                <view class="plan-actions">
                    <view class="action-btn" @click="handleSetDefault(plan)" v-if="!plan.is_default">
                        <text>设为默认</text>
                    </view>
                    <view class="action-btn" @click="handleShare(plan)">
                        <text>分享</text>
                    </view>
                    <view class="action-btn" @click="handleApply(plan)">
                        <text>应用方案</text>
                    </view>
                    <view class="action-btn delete" @click="handleDelete(plan)">
                        <text>删除</text>
                    </view>
                </view>
            </view>
        </view>

        <!-- 对比弹窗 -->
        <view class="compare-bar" v-if="selectedPlans.length > 0">
            <text>已选 {{ selectedPlans.length }} 个方案</text>
            <button class="btn-compare" @click="handleCompare" :disabled="selectedPlans.length !== 2">
                对比方案
            </button>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { getMyCartPlans, deleteCartPlan, setDefaultCartPlan } from '@/api/cart'

const loading = ref(false)
const planList = ref<any[]>([])
const selectedPlans = ref<number[]>([])

// 获取方案列表
const fetchPlans = async () => {
    loading.value = true
    try {
        const res = await getMyCartPlans()
        planList.value = res || []
    } finally {
        loading.value = false
    }
}

// 设为默认
const handleSetDefault = async (plan: any) => {
    try {
        await setDefaultCartPlan({ plan_id: plan.id })
        uni.showToast({ title: '设置成功', icon: 'success' })
        fetchPlans()
    } catch (e: any) {
        uni.showToast({ title: e.message || '操作失败', icon: 'none' })
    }
}

// 分享
const handleShare = (plan: any) => {
    uni.showToast({ title: '分享功能开发中', icon: 'none' })
}

// 应用方案
const handleApply = (plan: any) => {
    uni.showModal({
        title: '提示',
        content: '应用该方案将覆盖当前购物车，确定继续？',
        success: (res) => {
            if (res.confirm) {
                // 跳转到购物车并应用方案
                uni.navigateTo({ 
                    url: `/packages/pages/cart/cart?apply_plan=${plan.id}` 
                })
            }
        }
    })
}

// 删除
const handleDelete = (plan: any) => {
    uni.showModal({
        title: '提示',
        content: '确定要删除该方案吗？',
        success: async (res) => {
            if (res.confirm) {
                await deleteCartPlan({ plan_id: plan.id })
                uni.showToast({ title: '删除成功', icon: 'success' })
                fetchPlans()
            }
        }
    })
}

// 对比方案
const handleCompare = () => {
    if (selectedPlans.value.length !== 2) {
        uni.showToast({ title: '请选择2个方案进行对比', icon: 'none' })
        return
    }
    // 跳转到对比页面
    uni.navigateTo({
        url: `/packages/pages/plan_compare/plan_compare?id1=${selectedPlans.value[0]}&id2=${selectedPlans.value[1]}`
    })
}

onShow(() => {
    fetchPlans()
})
</script>

<style lang="scss" scoped>
.cart-plan-page {
    min-height: 100vh;
    background: #f5f5f5;
    padding: 20rpx;
}

.empty-state {
    display: flex;
    justify-content: center;
    padding-top: 200rpx;
    
    .empty-text {
        font-size: 28rpx;
        color: #999;
    }
}

.plan-item {
    background: #fff;
    border-radius: 16rpx;
    padding: 30rpx;
    margin-bottom: 20rpx;
    
    .plan-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20rpx;
        
        .plan-name {
            font-size: 32rpx;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10rpx;
            
            .default-tag {
                font-size: 20rpx;
                background: #ff6b6b;
                color: #fff;
                padding: 4rpx 12rpx;
                border-radius: 4rpx;
                font-weight: normal;
            }
        }
        
        .plan-price {
            font-size: 36rpx;
            color: #ff6b6b;
            font-weight: bold;
        }
    }
    
    .plan-items {
        background: #f9f9f9;
        padding: 20rpx;
        border-radius: 8rpx;
        
        .item-row {
            display: flex;
            align-items: center;
            padding: 10rpx 0;
            
            .mini-avatar {
                width: 50rpx;
                height: 50rpx;
                border-radius: 50%;
                margin-right: 16rpx;
            }
            
            .item-name {
                flex: 1;
                font-size: 26rpx;
            }
            
            .item-date {
                font-size: 24rpx;
                color: #999;
            }
        }
        
        .more-items {
            text-align: center;
            font-size: 24rpx;
            color: #999;
            margin-top: 10rpx;
        }
    }
    
    .plan-actions {
        display: flex;
        margin-top: 20rpx;
        gap: 20rpx;
        
        .action-btn {
            flex: 1;
            text-align: center;
            padding: 16rpx;
            background: #f5f5f5;
            border-radius: 8rpx;
            font-size: 26rpx;
            
            &.delete {
                color: #ff4d4f;
            }
        }
    }
}

.compare-bar {
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
    
    .btn-compare {
        background: #1890ff;
        color: #fff;
        font-size: 28rpx;
        padding: 16rpx 40rpx;
        border-radius: 8rpx;
        
        &[disabled] {
            background: #ccc;
        }
    }
}
</style>
