<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar 
            title="输入分享码"
            :front-color="$theme.navColor" 
            :background-color="$theme.navBgColor" 
        />
        <!-- #endif -->
    </page-meta>

    <view class="share-plan-page">
        <view class="container">
            <!-- 顶部图标 -->
            <view class="header-icon">
                <view class="icon-wrapper" :style="{ backgroundColor: getColor('primary-light-9') }">
                    <tn-icon name="share" size="96" :color="$theme.primaryColor" />
                </view>
            </view>

            <!-- 标题说明 -->
            <view class="header">
                <text class="title">输入分享码</text>
                <text class="desc">输入好友分享的方案码，查看并复制方案到购物车</text>
            </view>

            <!-- 输入区域 -->
            <view class="input-section">
                <view class="input-wrapper">
                    <view class="input-icon">
                        <tn-icon name="key" size="40" :color="$theme.primaryColor" />
                    </view>
                    <tn-input
                        v-model="shareCode"
                        placeholder="请输入16位分享码"
                        :border="false"
                        :clearable="true"
                        maxlength="32"
                        height="88"
                        @confirm="handleSubmit"
                    />
                </view>
                <view class="input-tip">
                    <tn-icon name="info-circle" size="28" color="#999999" />
                    <text>分享码由好友在"我的方案"中生成</text>
                </view>
            </view>

            <!-- 提交按钮 -->
            <view class="submit-section">
                <view 
                    class="submit-btn"
                    :class="{ disabled: !shareCode.trim() || loading }"
                    :style="(!shareCode.trim() || loading) ? {} : {
                        background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`
                    }"
                    @click="handleSubmit"
                >
                    <tn-icon v-if="loading" name="loading" size="32" color="#FFFFFF" />
                    <tn-icon v-else name="search" size="32" color="#FFFFFF" />
                    <text>{{ loading ? '查询中...' : '查看方案' }}</text>
                </view>
            </view>
        </view>

        <!-- 方案详情弹窗 -->
        <tn-popup v-model="showPlanDetail" mode="center" :border-radius="32" :mask-close-able="false">
            <view class="plan-detail-popup">
                <!-- 弹窗头部 -->
                <view class="popup-header" :style="{ backgroundColor: getColor('primary-light-9') }">
                    <view class="header-content">
                        <view class="header-icon">
                            <tn-icon name="folder-open" size="56" :color="$theme.primaryColor" />
                        </view>
                        <view class="header-text">
                            <text class="popup-title">{{ planDetail?.plan_name }}</text>
                            <view class="popup-meta">
                                <tn-icon name="time" size="24" color="#999999" />
                                <text>{{ planDetail?.items?.length || 0 }} 项服务</text>
                            </view>
                        </view>
                    </view>
                    <view class="popup-close" @click="showPlanDetail = false">
                        <tn-icon name="close" size="32" color="#999999" />
                    </view>
                </view>

                <!-- 弹窗内容 -->
                <view class="popup-content">
                    <!-- 价格区域 -->
                    <view class="price-section">
                        <text class="price-label">方案总价</text>
                        <view class="price-amount">
                            <text class="price-symbol" :style="{ color: $theme.ctaColor }">¥</text>
                            <text class="price-value" :style="{ color: $theme.ctaColor }">{{ planDetail?.total_price || 0 }}</text>
                        </view>
                    </view>

                    <!-- 服务项列表 -->
                    <view class="items-list" v-if="planDetail?.items?.length">
                        <view class="list-header">
                            <text class="list-title">包含服务</text>
                            <text class="list-count">{{ planDetail.items.length }}项</text>
                        </view>
                        <view class="item-card" v-for="(item, index) in planDetail.items" :key="index">
                            <view class="item-left">
                                <view class="item-index" :style="{ backgroundColor: getColor('primary-light-9') }">
                                    <text :style="{ color: $theme.primaryColor }">{{ index + 1 }}</text>
                                </view>
                                <view class="item-info">
                                    <text class="item-name">{{ item.staff_name }}</text>
                                    <view class="item-date">
                                        <tn-icon name="calendar" size="24" color="#999999" />
                                        <text>{{ item.date }}</text>
                                    </view>
                                </view>
                            </view>
                            <view class="item-price" :style="{ color: $theme.ctaColor }">
                                <text>¥{{ item.price }}</text>
                            </view>
                        </view>
                    </view>
                </view>

                <!-- 弹窗操作 -->
                <view class="popup-actions">
                    <view class="action-btn secondary" @click="showPlanDetail = false">
                        <tn-icon name="close" size="32" color="#666666" />
                        <text>取消</text>
                    </view>
                    <view 
                        class="action-btn primary"
                        :style="{ 
                            background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`
                        }"
                        @click="handleCopyPlan"
                    >
                        <tn-icon v-if="copying" name="loading" size="32" color="#FFFFFF" />
                        <tn-icon v-else name="shopping-cart" size="32" color="#FFFFFF" />
                        <text>{{ copying ? '复制中...' : '复制到购物车' }}</text>
                    </view>
                </view>
            </view>
        </tn-popup>
    </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { getPlanByShareCode, copyPlanByShareCode } from '@/api/cart'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()

// 获取主题色浅色变体
const getColor = (type: string) => {
    const colors: Record<string, string> = {
        'primary-light-9': '#F3E8FF',
        'primary-light-7': '#D8B4FE'
    }
    return colors[type] || '#F5F5F5'
}

const shareCode = ref('')
const loading = ref(false)
const copying = ref(false)
const showPlanDetail = ref(false)
const planDetail = ref<any>(null)

// 查看方案
const handleSubmit = async () => {
    if (!shareCode.value.trim()) {
        uni.showToast({ title: '请输入分享码', icon: 'none' })
        return
    }

    if (loading.value) return

    loading.value = true
    try {
        const result = await getPlanByShareCode({ share_code: shareCode.value.trim() })
        planDetail.value = result
        showPlanDetail.value = true
    } catch (e: any) {
        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '分享码无效或已过期'
        uni.showToast({ title: errorMsg, icon: 'none' })
    } finally {
        loading.value = false
    }
}

// 复制方案到购物车
const handleCopyPlan = async () => {
    if (copying.value) return

    copying.value = true
    try {
        const result = await copyPlanByShareCode({ share_code: shareCode.value.trim() })
        uni.showToast({
            title: `已复制${result.copied_count || 0}项到购物车`,
            icon: 'success'
        })

        showPlanDetail.value = false

        // 延迟跳转到购物车
        setTimeout(() => {
            uni.navigateTo({ url: '/packages/pages/cart/cart' })
        }, 1500)
    } catch (e: any) {
        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '复制失败'
        uni.showToast({ title: errorMsg, icon: 'none' })
    } finally {
        copying.value = false
    }
}
</script>

<style lang="scss" scoped>
.share-plan-page {
    min-height: 100vh;
    background: linear-gradient(180deg, #F3E8FF 0%, #F5F5F5 100%);
    padding: 48rpx 24rpx;
}

.container {
    background: #FFFFFF;
    border-radius: 32rpx;
    padding: 48rpx 32rpx;
    box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.08);
}

/* 顶部图标 */
.header-icon {
    display: flex;
    justify-content: center;
    margin-bottom: 32rpx;

    .icon-wrapper {
        width: 160rpx;
        height: 160rpx;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8rpx 24rpx rgba(124, 58, 237, 0.2);
    }
}

/* 标题说明 */
.header {
    text-align: center;
    margin-bottom: 48rpx;

    .title {
        display: block;
        font-size: 40rpx;
        font-weight: 700;
        color: #333333;
        margin-bottom: 16rpx;
    }

    .desc {
        display: block;
        font-size: 26rpx;
        color: #999999;
        line-height: 1.6;
        padding: 0 24rpx;
    }
}

/* 输入区域 */
.input-section {
    margin-bottom: 48rpx;

    .input-wrapper {
        display: flex;
        align-items: center;
        background: #F9FAFB;
        border-radius: 16rpx;
        padding: 0 24rpx;
        border: 2rpx solid #E5E7EB;
        transition: all 0.2s ease;

        &:focus-within {
            background: #FFFFFF;
            border-color: #7C3AED;
            box-shadow: 0 0 0 6rpx rgba(124, 58, 237, 0.1);
        }

        .input-icon {
            margin-right: 16rpx;
            display: flex;
            align-items: center;
        }
    }

    .input-tip {
        display: flex;
        align-items: center;
        gap: 8rpx;
        margin-top: 16rpx;
        padding: 0 8rpx;

        text {
            font-size: 24rpx;
            color: #999999;
        }
    }
}

/* 提交按钮 */
.submit-section {
    .submit-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12rpx;
        color: #FFFFFF;
        font-size: 32rpx;
        font-weight: 700;
        padding: 32rpx;
        border-radius: 64rpx;
        box-shadow: 0 12rpx 32rpx rgba(124, 58, 237, 0.4);
        transition: all 0.3s ease;

        &:active:not(.disabled) {
            transform: translateY(4rpx) scale(0.98);
            box-shadow: 0 6rpx 16rpx rgba(124, 58, 237, 0.3);
        }

        &.disabled {
            background: linear-gradient(135deg, #CCCCCC 0%, #AAAAAA 100%) !important;
            box-shadow: none;
            opacity: 0.6;
        }
    }
}

/* 方案详情弹窗 */
.plan-detail-popup {
    width: 640rpx;
    background: #FFFFFF;
    border-radius: 32rpx;
    overflow: hidden;
    max-height: 80vh;
    display: flex;
    flex-direction: column;

    /* 弹窗头部 */
    .popup-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 32rpx;
        border-bottom: 1rpx solid #F0F0F0;

        .header-content {
            display: flex;
            align-items: center;
            gap: 20rpx;
            flex: 1;

            .header-icon {
                width: 96rpx;
                height: 96rpx;
                background: #FFFFFF;
                border-radius: 24rpx;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 4rpx 16rpx rgba(124, 58, 237, 0.15);
            }

            .header-text {
                flex: 1;

                .popup-title {
                    display: block;
                    font-size: 32rpx;
                    font-weight: 700;
                    color: #333333;
                    margin-bottom: 8rpx;
                }

                .popup-meta {
                    display: flex;
                    align-items: center;
                    gap: 8rpx;

                    text {
                        font-size: 24rpx;
                        color: #999999;
                    }
                }
            }
        }

        .popup-close {
            width: 64rpx;
            height: 64rpx;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #F5F5F5;
            border-radius: 50%;
            transition: all 0.2s ease;

            &:active {
                background: #E5E5E5;
                transform: scale(0.95);
            }
        }
    }

    /* 弹窗内容 */
    .popup-content {
        flex: 1;
        overflow-y: auto;
        padding: 32rpx;

        /* 价格区域 */
        .price-section {
            background: #FFF7E6;
            border-radius: 16rpx;
            padding: 32rpx;
            margin-bottom: 32rpx;

            .price-label {
                display: block;
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

        /* 服务项列表 */
        .items-list {
            .list-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20rpx;

                .list-title {
                    font-size: 28rpx;
                    font-weight: 600;
                    color: #333333;
                }

                .list-count {
                    font-size: 24rpx;
                    color: #999999;
                    background: #F5F5F5;
                    padding: 6rpx 16rpx;
                    border-radius: 24rpx;
                }
            }

            .item-card {
                display: flex;
                align-items: center;
                justify-content: space-between;
                background: #F9FAFB;
                border-radius: 16rpx;
                padding: 24rpx;
                margin-bottom: 16rpx;

                &:last-child {
                    margin-bottom: 0;
                }

                .item-left {
                    display: flex;
                    align-items: center;
                    gap: 20rpx;
                    flex: 1;

                    .item-index {
                        width: 56rpx;
                        height: 56rpx;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        flex-shrink: 0;

                        text {
                            font-size: 28rpx;
                            font-weight: 700;
                        }
                    }

                    .item-info {
                        flex: 1;

                        .item-name {
                            display: block;
                            font-size: 28rpx;
                            font-weight: 500;
                            color: #333333;
                            margin-bottom: 8rpx;
                        }

                        .item-date {
                            display: flex;
                            align-items: center;
                            gap: 8rpx;

                            text {
                                font-size: 24rpx;
                                color: #999999;
                            }
                        }
                    }
                }

                .item-price {
                    font-size: 28rpx;
                    font-weight: 600;
                    flex-shrink: 0;
                }
            }
        }
    }

    /* 弹窗操作 */
    .popup-actions {
        display: flex;
        gap: 16rpx;
        padding: 24rpx 32rpx;
        padding-bottom: calc(24rpx + env(safe-area-inset-bottom));
        border-top: 1rpx solid #F0F0F0;

        .action-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12rpx;
            padding: 28rpx;
            border-radius: 56rpx;
            font-size: 28rpx;
            font-weight: 600;
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
                color: #FFFFFF;
                box-shadow: 0 8rpx 24rpx rgba(124, 58, 237, 0.35);

                &:active {
                    transform: scale(0.98);
                    box-shadow: 0 4rpx 12rpx rgba(124, 58, 237, 0.35);
                }
            }
        }
    }
}
</style>
