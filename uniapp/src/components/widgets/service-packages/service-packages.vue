<template>
    <view class="service-packages mx-md mt-md" v-if="content.enabled && showList.length">
        <!-- 标题 -->
        <view v-if="content.title" class="flex items-center mb-md">
            <view class="title-decoration">
                <view class="title-line" :style="$theme.titleLine.value"></view>
            </view>
            <text class="title-text">{{ content.title }}</text>
            <view class="flex-1"></view>
            <view v-if="content.show_more" class="more-btn" :style="$theme.moreBtn.value" @click="handleMore">
                <text class="more-text" :style="$theme.moreText.value">查看更多</text>
                <tn-icon
                    name="right"
                    size="24"
                    :color="primaryColor"
                    class="ml-1"
                ></tn-icon>
            </view>
        </view>

        <!-- 横向滑动样式 -->
        <scroll-view
            v-if="content.style == 1"
            scroll-x
            class="package-scroll"
            :show-scrollbar="false"
        >
            <view class="flex gap-[20rpx]">
                <view
                    v-for="(item, index) in showList"
                    :key="index"
                    class="package-card"
                    :style="{ width: content.card_width || '520rpx' }"
                >
                    <!-- 卡片头部 -->
                    <view class="card-header">
                        <view class="card-header-content">
                            <text class="card-title">{{ item.name }}</text>
                            <view v-if="item.tag" class="tag-badge">
                                <text class="tag-text">{{ item.tag }}</text>
                            </view>
                        </view>
                        <view class="price-wrapper">
                            <view class="price-main">
                                <text class="price-symbol" :style="$theme.priceColor.value">¥</text>
                                <text class="price-value" :style="$theme.priceColor.value">{{ item.price }}</text>
                            </view>
                            <text v-if="item.original_price" class="price-original">
                                ¥{{ item.original_price }}
                            </text>
                        </view>
                    </view>

                    <!-- 服务项列表 -->
                    <view v-if="item.services && item.services.length" class="services-list">
                        <view
                            v-for="(service, sIndex) in item.services"
                            :key="sIndex"
                            class="service-item"
                        >
                            <view class="service-dot" :style="$theme.serviceDot.value"></view>
                            <text class="service-text">{{ service }}</text>
                        </view>
                    </view>

                    <!-- 描述 -->
                    <text v-if="item.desc" class="card-desc">{{ item.desc }}</text>
                </view>
            </view>
        </scroll-view>

        <!-- 纵向列表样式 -->
        <view v-if="content.style == 2" class="package-list">
            <view v-for="(item, index) in showList" :key="index" class="package-card-vertical">
                <!-- 卡片头部 -->
                <view class="card-header-vertical">
                    <view class="card-header-left">
                        <text class="card-title-vertical">{{ item.name }}</text>
                        <text v-if="item.desc" class="card-desc-vertical">{{ item.desc }}</text>
                    </view>
                    <view v-if="item.tag" class="tag-badge-vertical">
                        <text class="tag-text-vertical">{{ item.tag }}</text>
                    </view>
                </view>

                <!-- 服务项列表 -->
                <view v-if="item.services && item.services.length" class="services-list-vertical">
                    <view
                        v-for="(service, sIndex) in item.services"
                        :key="sIndex"
                        class="service-item-vertical"
                    >
                        <view class="service-dot-vertical" :style="$theme.serviceDot.value"></view>
                        <text class="service-text-vertical">{{ service }}</text>
                    </view>
                </view>

                <!-- 价格 -->
                <view class="price-wrapper-vertical">
                    <view class="price-main-vertical">
                        <text class="price-symbol-vertical" :style="$theme.priceColor.value">¥</text>
                        <text class="price-value-vertical" :style="$theme.priceColor.value">{{ item.price }}</text>
                        <text v-if="item.original_price" class="price-original-vertical">
                            ¥{{ item.original_price }}
                        </text>
                    </view>
                </view>
            </view>
        </view>

        <!-- 大卡片样式 -->
        <view v-if="content.style == 3" class="package-grid">
            <view v-for="(item, index) in showList" :key="index" class="package-card-large">
                <!-- 卡片头部 -->
                <view class="card-header-large">
                    <view class="card-header-large-top">
                        <text class="card-title-large">{{ item.name }}</text>
                        <view v-if="item.tag" class="tag-badge-large">
                            <text class="tag-text-large">{{ item.tag }}</text>
                        </view>
                    </view>
                    <text v-if="item.desc" class="card-desc-large">{{ item.desc }}</text>
                </view>

                <!-- 服务项网格 -->
                <view v-if="item.services && item.services.length" class="services-grid">
                    <view
                        v-for="(service, sIndex) in item.services"
                        :key="sIndex"
                        class="service-badge"
                        :style="$theme.serviceBadge.value"
                    >
                        <text class="service-badge-text" :style="$theme.serviceBadgeText.value">{{ service }}</text>
                    </view>
                </view>

                <!-- 价格 -->
                <view class="price-wrapper-large">
                    <view class="price-main-large">
                        <text class="price-symbol-large" :style="$theme.priceColor.value">¥</text>
                        <text class="price-value-large" :style="$theme.priceColor.value">{{ item.price }}</text>
                        <text v-if="item.original_price" class="price-original-large">
                            ¥{{ item.original_price }}
                        </text>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { navigateTo } from '@/utils/util'
import { useThemeStore } from '@/stores/theme'
import { tintColor, alphaColor } from '@/utils/color'

const themeStore = useThemeStore()
const primaryColor = computed(() => themeStore.primaryColor || '#7C3AED')

// 内联样式计算属性（替代CSS变量，兼容小程序）
const $theme = {
    titleLine: computed(() => ({
        background: primaryColor.value,
        borderRadius: '999rpx',
        boxShadow: `0 2rpx 8rpx ${alphaColor(primaryColor.value, 0.3)}`
    })),
    moreBtn: computed(() => ({
        background: tintColor(primaryColor.value, 0.92)
    })),
    moreBtnActive: computed(() => ({
        background: tintColor(primaryColor.value, 0.85)
    })),
    moreText: computed(() => ({
        color: primaryColor.value
    })),
    priceColor: computed(() => ({
        color: primaryColor.value
    })),
    serviceDot: computed(() => ({
        background: primaryColor.value
    })),
    serviceBadge: computed(() => ({
        background: tintColor(primaryColor.value, 0.92),
        border: `1rpx solid ${tintColor(primaryColor.value, 0.85)}`
    })),
    serviceBadgeText: computed(() => ({
        color: primaryColor.value
    }))
}

const props = defineProps({
    content: {
        type: Object,
        default: () => ({
            data: [],
            enabled: true,
            title: '服务套餐',
            show_more: true,
            show_count: 10,
            style: 1, // 1: 横向滑动, 2: 纵向列表, 3: 大卡片
            card_width: '520rpx',
            more_link: {}
        })
    },
    styles: {
        type: Object,
        default: () => ({})
    }
})

// 过滤显示的列表
const showList = computed(() => {
    const data = props.content.data?.filter((item: any) => item.is_show !== '0') || []
    const limit = props.content.show_count || data.length
    return data.slice(0, limit)
})

// 查看更多
const handleMore = () => {
    if (props.content.more_link && Object.keys(props.content.more_link).length > 0) {
        navigateTo(props.content.more_link)
    }
}
</script>

<style lang="scss" scoped>
.service-packages {
    // 标题样式
    .title-decoration {
        position: relative;
        width: 8rpx;
        height: 34rpx;
        margin-right: 16rpx;

        .title-line {
            width: 100%;
            height: 100%;
            border-radius: 999rpx;
        }
    }

    .title-text {
        font-size: 36rpx;
        font-weight: 600;
        color: #1f2937;
        letter-spacing: 0.5rpx;
    }

    .more-btn {
        display: flex;
        align-items: center;
        padding: 8rpx 16rpx;
        border-radius: 999rpx;
        transition: all 0.2s ease;

        .more-text {
            font-size: 24rpx;
            font-weight: 500;
        }
    }

    // 横向滑动样式
    .package-scroll {
        margin: 0 -16rpx;
        padding: 0 16rpx;
    }

    .package-card {
        flex-shrink: 0;
        background: #ffffff;
        border-radius: 14rpx;
        padding: 20rpx;
        border: 2rpx solid #f3f4f6;
        box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.08);

        .card-header {
            .card-header-content {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 12rpx;

                .card-title {
                    flex: 1;
                    font-size: 32rpx;
                    font-weight: 600;
                    color: #111827;
                    line-height: 1.4;
                }

                .tag-badge {
                    margin-left: 12rpx;
                    padding: 6rpx 12rpx;
                    background: linear-gradient(135deg, #f97316 0%, #fb923c 100%);
                    border-radius: 999rpx;
                    box-shadow: 0 2rpx 8rpx rgba(249, 115, 22, 0.3);

                    .tag-text {
                        font-size: 20rpx;
                        color: #ffffff;
                        font-weight: 600;
                    }
                }
            }

            .price-wrapper {
                display: flex;
                align-items: baseline;
                margin-bottom: 20rpx;

                .price-main {
                    display: flex;
                    align-items: baseline;

                    .price-symbol {
                        font-size: 28rpx;
                        font-weight: 600;
                        margin-right: 4rpx;
                    }

                    .price-value {
                        font-size: 44rpx;
                        font-weight: 700;
                        line-height: 1;
                    }
                }

                .price-original {
                    font-size: 24rpx;
                    color: #9ca3af;
                    text-decoration: line-through;
                    margin-left: 12rpx;
                }
            }
        }

        .services-list {
            padding: 16rpx 0;
            border-top: 1rpx solid #f3f4f6;
            display: flex;
            flex-direction: column;
            gap: 10rpx;

            .service-item {
                display: flex;
                align-items: center;

                .service-dot {
                    width: 8rpx;
                    height: 8rpx;
                    border-radius: 50%;
                    margin-right: 12rpx;
                    flex-shrink: 0;
                }

                .service-text {
                    font-size: 26rpx;
                    color: #4b5563;
                    line-height: 1.5;
                }
            }
        }

        .card-desc {
            display: block;
            font-size: 24rpx;
            color: #6b7280;
            line-height: 1.6;
            margin-top: 12rpx;
            padding-top: 12rpx;
            border-top: 1rpx solid #f3f4f6;
        }
    }

    // 纵向列表样式
    .package-list {
        display: flex;
        flex-direction: column;
        gap: 16rpx;
    }

    .package-card-vertical {
        background: #ffffff;
        border-radius: 14rpx;
        padding: 20rpx;
        border: 2rpx solid #f3f4f6;
        box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.08);

        .card-header-vertical {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 16rpx;

            .card-header-left {
                flex: 1;

                .card-title-vertical {
                    display: block;
                    font-size: 32rpx;
                    font-weight: 600;
                    color: #111827;
                    line-height: 1.4;
                    margin-bottom: 6rpx;
                }

                .card-desc-vertical {
                    display: block;
                    font-size: 24rpx;
                    color: #6b7280;
                    line-height: 1.6;
                }
            }

            .tag-badge-vertical {
                margin-left: 12rpx;
                padding: 6rpx 12rpx;
                background: linear-gradient(135deg, #f97316 0%, #fb923c 100%);
                border-radius: 999rpx;
                box-shadow: 0 2rpx 8rpx rgba(249, 115, 22, 0.3);
                flex-shrink: 0;

                .tag-text-vertical {
                    font-size: 20rpx;
                    color: #ffffff;
                    font-weight: 600;
                }
            }
        }

        .services-list-vertical {
            padding: 16rpx 0;
            border-top: 1rpx solid #f3f4f6;
            border-bottom: 1rpx solid #f3f4f6;
            display: flex;
            flex-direction: column;
            gap: 10rpx;

            .service-item-vertical {
                display: flex;
                align-items: center;

                .service-dot-vertical {
                    width: 8rpx;
                    height: 8rpx;
                    border-radius: 50%;
                    margin-right: 12rpx;
                    flex-shrink: 0;
                }

                .service-text-vertical {
                    font-size: 26rpx;
                    color: #4b5563;
                    line-height: 1.5;
                }
            }
        }

        .price-wrapper-vertical {
            margin-top: 16rpx;

            .price-main-vertical {
                display: flex;
                align-items: baseline;

                .price-symbol-vertical {
                    font-size: 28rpx;
                    font-weight: 600;
                    margin-right: 4rpx;
                }

                .price-value-vertical {
                    font-size: 44rpx;
                    font-weight: 700;
                    line-height: 1;
                }

                .price-original-vertical {
                    font-size: 24rpx;
                    color: #9ca3af;
                    text-decoration: line-through;
                    margin-left: 12rpx;
                }
            }
        }
    }

    // 大卡片样式
    .package-grid {
        display: flex;
        flex-direction: column;
        gap: 16rpx;
    }

    .package-card-large {
        background: #ffffff;
        border-radius: 14rpx;
        padding: 24rpx;
        border: 2rpx solid #f3f4f6;
        box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.08);

        .card-header-large {
            margin-bottom: 16rpx;

            .card-header-large-top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 8rpx;

                .card-title-large {
                    flex: 1;
                    font-size: 36rpx;
                    font-weight: 600;
                    color: #111827;
                    line-height: 1.4;
                }

                .tag-badge-large {
                    margin-left: 12rpx;
                    padding: 6rpx 16rpx;
                    background: linear-gradient(135deg, #f97316 0%, #fb923c 100%);
                    border-radius: 999rpx;
                    box-shadow: 0 2rpx 8rpx rgba(249, 115, 22, 0.3);

                    .tag-text-large {
                        font-size: 22rpx;
                        color: #ffffff;
                        font-weight: 600;
                    }
                }
            }

            .card-desc-large {
                display: block;
                font-size: 26rpx;
                color: #6b7280;
                line-height: 1.6;
            }
        }

        .services-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10rpx;
            padding: 16rpx 0;
            border-top: 1rpx solid #f3f4f6;
            border-bottom: 1rpx solid #f3f4f6;

            .service-badge {
                padding: 8rpx 16rpx;
                border-radius: 999rpx;

                .service-badge-text {
                    font-size: 24rpx;
                    font-weight: 500;
                }
            }
        }

        .price-wrapper-large {
            margin-top: 16rpx;

            .price-main-large {
                display: flex;
                align-items: baseline;

                .price-symbol-large {
                    font-size: 32rpx;
                    font-weight: 600;
                    margin-right: 4rpx;
                }

                .price-value-large {
                    font-size: 48rpx;
                    font-weight: 700;
                    line-height: 1;
                }

                .price-original-large {
                    font-size: 28rpx;
                    color: #9ca3af;
                    text-decoration: line-through;
                    margin-left: 12rpx;
                }
            }
        }
    }
}
</style>
