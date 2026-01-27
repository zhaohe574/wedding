<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar 
            title="我的方案"
            :front-color="$theme.navColor" 
            :background-color="$theme.navBgColor" 
        />
        <!-- #endif -->
    </page-meta>

    <view class="cart-plan-page">
        <!-- 顶部操作栏 -->
        <view class="header-actions">
            <view class="action-card" @click="goToShareInput">
                <tn-icon name="share" size="40" :color="$theme.primaryColor" />
                <text class="action-text">输入分享码</text>
            </view>
        </view>

        <!-- 空状态 -->
        <view class="empty-state" v-if="!loading && planList.length === 0">
            <tn-icon name="folder" size="160" color="#CCCCCC" />
            <text class="empty-title">暂无保存的方案</text>
            <text class="empty-desc">保存您的预约方案，随时查看和分享</text>
            <view class="btn-action" :style="{ backgroundColor: $theme.primaryColor }" @click="goToShareInput">
                <tn-icon name="share" size="32" color="#FFFFFF" />
                <text>输入分享码</text>
            </view>
        </view>

        <!-- 方案列表 -->
        <view class="plan-list">
            <view class="plan-card" v-for="plan in planList" :key="plan.id">
                <!-- 卡片头部 -->
                <view class="card-header" :style="{ backgroundColor: getColor('primary-light-9') }">
                    <view class="header-left">
                        <view class="plan-icon">
                            <tn-icon name="folder-open" size="48" :color="$theme.primaryColor" />
                        </view>
                        <view class="plan-title">
                            <text class="plan-name">{{ plan.plan_name }}</text>
                            <view class="plan-meta">
                                <tn-icon name="time" size="24" color="#999999" />
                                <text class="meta-text">{{ plan.cart_items?.length || 0 }} 项服务</text>
                            </view>
                        </view>
                    </view>
                    <view class="default-badge" v-if="plan.is_default">
                        <tn-icon name="star-fill" size="28" color="#FFD700" />
                    </view>
                </view>

                <!-- 价格区域 -->
                <view class="price-section">
                    <view class="price-label">方案总价</view>
                    <view class="price-amount">
                        <text class="price-symbol" :style="{ color: $theme.ctaColor }">¥</text>
                        <text class="price-value" :style="{ color: $theme.ctaColor }">{{ plan.actual_total_price || plan.total_price }}</text>
                    </view>
                </view>

                <!-- 方案项目预览 -->
                <view class="plan-preview">
                    <view class="preview-header">
                        <text class="preview-title">包含服务</text>
                        <text class="preview-count">{{ plan.cart_items?.length || 0 }}项</text>
                    </view>
                    <view class="preview-items">
                        <view class="preview-item" v-for="item in plan.cart_items?.slice(0, 3)" :key="item.id">
                            <image :src="item.staff?.avatar" class="preview-avatar" mode="aspectFill" />
                            <view class="preview-info">
                                <text class="preview-name">{{ item.staff?.name }}</text>
                                <text class="preview-date">{{ item.schedule_date }}</text>
                            </view>
                            <view class="preview-price" :style="{ color: $theme.ctaColor }">
                                <text>¥{{ item.price }}</text>
                            </view>
                        </view>
                        <view class="preview-more" v-if="plan.cart_items?.length > 3">
                            <tn-icon name="more" size="32" color="#999999" />
                            <text>还有 {{ plan.cart_items.length - 3 }} 项服务</text>
                        </view>
                    </view>
                </view>

                <!-- 操作按钮组 -->
                <view class="action-group">
                    <view class="action-row">
                        <view class="action-btn secondary" @click="handleSetDefault(plan)" v-if="!plan.is_default">
                            <tn-icon name="star" size="36" color="#999999" />
                            <text>设为默认</text>
                        </view>
                        <view class="action-btn secondary" @click="handleShare(plan)">
                            <tn-icon name="share" size="36" :color="$theme.primaryColor" />
                            <text>分享方案</text>
                        </view>
                    </view>
                    <view class="action-row">
                        <view class="action-btn primary" :style="{ backgroundColor: $theme.primaryColor }" @click="handleApply(plan)">
                            <tn-icon name="shopping-cart" size="36" color="#FFFFFF" />
                            <text>应用到购物车</text>
                        </view>
                        <view class="action-btn danger" @click="handleDelete(plan)">
                            <tn-icon name="delete" size="36" color="#FF2C3C" />
                        </view>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import {
    getMyCartPlans,
    deleteCartPlan,
    setDefaultCartPlan,
    generatePlanShareCode
} from '@/api/cart'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()

const loading = ref(false)
const planList = ref<any[]>([])

// 获取主题色浅色变体
const getColor = (type: string) => {
    const colors: Record<string, string> = {
        'primary-light-9': '#F3E8FF',
        'cta-light-9': '#FFF7E6'
    }
    return colors[type] || '#F5F5F5'
}

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
const handleShare = async (plan: any) => {
    try {
        const res = await generatePlanShareCode({ plan_id: plan.id })
        const shareCode = res.share_code

        uni.showModal({
            title: '分享方案',
            content: `分享码：${shareCode}\n\n将此分享码发送给好友，好友可以通过分享码查看并复制您的方案。`,
            confirmText: '复制分享码',
            confirmColor: $theme.primaryColor,
            success: (modalRes) => {
                if (modalRes.confirm) {
                    uni.setClipboardData({
                        data: shareCode,
                        success: () => {
                            uni.showToast({ title: '分享码已复制', icon: 'success' })
                        }
                    })
                }
            }
        })
    } catch (e: any) {
        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '生成分享码失败'
        uni.showToast({ title: errorMsg, icon: 'none' })
    }
}

// 应用方案
const handleApply = (plan: any) => {
    uni.showModal({
        title: '应用方案',
        content: '应用该方案将覆盖当前购物车，确定继续？',
        confirmColor: $theme.primaryColor,
        success: (res) => {
            if (res.confirm) {
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
        title: '删除方案',
        content: '确定要删除该方案吗？',
        confirmColor: '#FF2C3C',
        success: async (res) => {
            if (res.confirm) {
                await deleteCartPlan({ plan_id: plan.id })
                uni.showToast({ title: '删除成功', icon: 'success' })
                fetchPlans()
            }
        }
    })
}

// 跳转到输入分享码页面
const goToShareInput = () => {
    uni.navigateTo({
        url: '/packages/pages/share_plan/share_plan'
    })
}

onShow(() => {
    fetchPlans()
})
</script>

<style lang="scss" scoped>
.cart-plan-page {
    min-height: 100vh;
    background: #F5F5F5;
    padding: 24rpx;
    padding-bottom: 48rpx;
}

/* 顶部操作栏 */
.header-actions {
    margin-bottom: 24rpx;

    .action-card {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12rpx;
        background: #FFFFFF;
        padding: 24rpx 32rpx;
        border-radius: 56rpx;
        box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);
        transition: all 0.2s ease;

        &:active {
            transform: scale(0.98);
            box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.06);
        }

        .action-text {
            font-size: 28rpx;
            font-weight: 500;
            color: #333333;
        }
    }
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
        text-align: center;
    }

    .btn-action {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12rpx;
        color: #FFFFFF;
        font-size: 30rpx;
        font-weight: 600;
        padding: 28rpx 64rpx;
        border-radius: 64rpx;
        box-shadow: 0 12rpx 32rpx rgba(124, 58, 237, 0.4);
        transition: all 0.3s ease;

        &:active {
            transform: translateY(4rpx) scale(0.98);
            box-shadow: 0 6rpx 16rpx rgba(124, 58, 237, 0.3);
        }
    }
}

/* 方案列表 */
.plan-list {
    display: flex;
    flex-direction: column;
    gap: 24rpx;
}

/* 方案卡片 */
.plan-card {
    background: #FFFFFF;
    border-radius: 32rpx;
    padding: 0;
    box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.08);
    overflow: hidden;
    margin-bottom: 24rpx;

    /* 卡片头部 */
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 32rpx;

        .header-left {
            display: flex;
            align-items: center;
            gap: 20rpx;
            flex: 1;

            .plan-icon {
                width: 96rpx;
                height: 96rpx;
                background: #FFFFFF;
                border-radius: 24rpx;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 4rpx 16rpx rgba(124, 58, 237, 0.15);
            }

            .plan-title {
                flex: 1;

                .plan-name {
                    display: block;
                    font-size: 34rpx;
                    font-weight: 700;
                    color: #333333;
                    margin-bottom: 8rpx;
                }

                .plan-meta {
                    display: flex;
                    align-items: center;
                    gap: 8rpx;

                    .meta-text {
                        font-size: 24rpx;
                        color: #999999;
                    }
                }
            }
        }

        .default-badge {
            width: 64rpx;
            height: 64rpx;
            background: #FFF7E6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4rpx 12rpx rgba(255, 215, 0, 0.3);
        }
    }

    /* 价格区域 */
    .price-section {
        padding: 32rpx;
        background: #FFF7E6;
        border-top: 1rpx solid #F0F0F0;
        border-bottom: 1rpx solid #F0F0F0;

        .price-label {
            font-size: 24rpx;
            color: #999999;
            margin-bottom: 8rpx;
        }

        .price-amount {
            display: flex;
            align-items: baseline;

            .price-symbol {
                font-size: 32rpx;
                font-weight: 700;
                margin-right: 4rpx;
            }

            .price-value {
                font-size: 56rpx;
                font-weight: 800;
                line-height: 1;
            }
        }
    }

    /* 方案项目预览 */
    .plan-preview {
        padding: 32rpx;

        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20rpx;

            .preview-title {
                font-size: 28rpx;
                font-weight: 600;
                color: #333333;
            }

            .preview-count {
                font-size: 24rpx;
                color: #999999;
                background: #F5F5F5;
                padding: 6rpx 16rpx;
                border-radius: 24rpx;
            }
        }

        .preview-items {
            .preview-item {
                display: flex;
                align-items: center;
                gap: 20rpx;
                padding: 20rpx;
                background: #F9FAFB;
                border-radius: 16rpx;
                margin-bottom: 12rpx;

                &:last-child {
                    margin-bottom: 0;
                }

                .preview-avatar {
                    width: 72rpx;
                    height: 72rpx;
                    border-radius: 50%;
                    border: 3rpx solid #FFFFFF;
                    box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.08);
                }

                .preview-info {
                    flex: 1;
                    display: flex;
                    flex-direction: column;
                    gap: 6rpx;

                    .preview-name {
                        font-size: 28rpx;
                        font-weight: 500;
                        color: #333333;
                    }

                    .preview-date {
                        font-size: 24rpx;
                        color: #999999;
                    }
                }

                .preview-price {
                    font-size: 28rpx;
                    font-weight: 600;
                }
            }

            .preview-more {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 12rpx;
                padding: 20rpx;
                margin-top: 12rpx;
                background: #F5F5F5;
                border-radius: 16rpx;
                font-size: 24rpx;
                color: #999999;
            }
        }
    }

    /* 操作按钮组 */
    .action-group {
        padding: 24rpx 32rpx 32rpx;
        display: flex;
        flex-direction: column;
        gap: 16rpx;

        .action-row {
            display: flex;
            gap: 16rpx;

            .action-btn {
                flex: 1;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 12rpx;
                padding: 24rpx;
                border-radius: 56rpx;
                font-size: 26rpx;
                font-weight: 500;
                transition: all 0.2s ease;

                &.secondary {
                    background: #F5F5F5;
                    color: #666666;

                    &:active {
                        background: #E5E5E5;
                        transform: scale(0.98);
                    }
                }

                &.primary {
                    flex: 3;
                    color: #FFFFFF;
                    box-shadow: 0 8rpx 24rpx rgba(124, 58, 237, 0.35);

                    &:active {
                        transform: scale(0.98);
                        box-shadow: 0 4rpx 12rpx rgba(124, 58, 237, 0.35);
                    }
                }

                &.danger {
                    flex: 0 0 auto;
                    width: 96rpx;
                    background: #FEE;
                    color: #FF2C3C;

                    &:active {
                        background: #FDD;
                        transform: scale(0.95);
                    }
                }
            }
        }
    }
}
</style>
