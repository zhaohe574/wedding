<template>
    <view v-if="content.enabled && showList.length" class="faq-widget">
        <!-- 标题区域 -->
        <view v-if="content.title" class="faq-header">
            <view class="header-decoration" :style="$theme.titleBar.value"></view>
            <text class="header-title">{{ content.title }}</text>
        </view>

        <!-- 搜索框 -->
        <view v-if="content.show_search" class="search-container">
            <view class="search-box" :style="$theme.searchBorder.value">
                <view class="search-icon">
                    <tn-icon name="search" size="28" :color="$theme.iconColor.value"></tn-icon>
                </view>
                <input
                    v-model="searchKeyword"
                    class="search-input"
                    placeholder="搜索您的问题..."
                    @input="handleSearch"
                />
                <view v-if="searchKeyword" class="clear-icon" @click="clearSearch">
                    <tn-icon name="close" size="22" color="#64748b"></tn-icon>
                </view>
            </view>
        </view>

        <!-- 折叠面板样式 -->
        <view v-if="content.style == 1" class="accordion-style">
            <view
                v-for="(item, index) in filteredList"
                :key="index"
                class="accordion-item"
                :class="{ 'is-active': activeIndex === index }"
                :style="activeIndex === index ? $theme.activeBorder.value : $theme.normalBorder.value"
            >
                <view class="accordion-header" @click="toggleAccordion(index)">
                    <view class="question-wrapper">
                        <view class="question-icon" :style="$theme.qBadge.value">Q</view>
                        <text class="question-text">{{ item.question }}</text>
                    </view>
                    <view class="expand-icon" :style="$theme.expandBg.value" :class="{ 'is-expanded': activeIndex === index }">
                        <text class="icon-text" :style="$theme.expandIcon.value">›</text>
                    </view>
                </view>
                <view class="accordion-content" :class="{ 'is-expanded': activeIndex === index }">
                    <view class="answer-wrapper">
                        <view class="answer-icon">A</view>
                        <view class="answer-text">
                            <rich-text :nodes="item.answer"></rich-text>
                        </view>
                    </view>
                    <view v-if="item.category" class="category-tag" :style="$theme.tagBg.value">
                        <text class="tag-text" :style="$theme.tagText.value">{{ item.category }}</text>
                    </view>
                </view>
            </view>
        </view>

        <!-- 列表式样式 -->
        <view v-if="content.style == 2" class="list-style">
            <view
                v-for="(item, index) in filteredList"
                :key="index"
                class="faq-card"
                :style="$theme.normalBorder.value"
            >
                <!-- 问题部分 -->
                <view class="question-section">
                    <view class="q-badge" :style="$theme.qBadge.value">Q</view>
                    <view class="q-content">
                        <text class="q-text">{{ item.question }}</text>
                        <view v-if="item.category" class="q-category" :style="$theme.tagBg.value">
                            <text class="category-text" :style="$theme.tagText.value">{{ item.category }}</text>
                        </view>
                    </view>
                </view>

                <!-- 答案部分 -->
                <view class="answer-section">
                    <view class="a-badge">A</view>
                    <view class="a-content">
                        <rich-text :nodes="item.answer"></rich-text>
                    </view>
                </view>
            </view>
        </view>

        <!-- 无搜索结果 -->
        <view v-if="content.show_search && filteredList.length === 0" class="no-result">
            <tn-icon name="search" size="80" color="#cbd5e1"></tn-icon>
            <text class="no-result-text">未找到相关问题</text>
            <text class="no-result-hint">试试其他关键词</text>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useThemeStore } from '@/stores/theme'
import { tintColor, alphaColor } from '@/utils/color'

const props = defineProps({
    content: {
        type: Object,
        default: () => ({})
    },
    styles: {
        type: Object,
        default: () => ({})
    }
})

const themeStore = useThemeStore()
const primaryColor = computed(() => themeStore.primaryColor || '#E85A4F')

// 主题内联样式（兼容小程序，不使用CSS变量）
const $theme = {
    titleBar: computed(() => ({
        background: `linear-gradient(180deg, ${primaryColor.value} 0%, ${tintColor(primaryColor.value, 0.3)} 100%)`
    })),
    searchBorder: computed(() => ({
        border: `2rpx solid ${alphaColor(primaryColor.value, 0.1)}`,
        boxShadow: `0 6rpx 18rpx ${alphaColor(primaryColor.value, 0.08)}`
    })),
    iconColor: computed(() => alphaColor(primaryColor.value, 0.6)),
    normalBorder: computed(() => ({
        border: `2rpx solid ${alphaColor(primaryColor.value, 0.1)}`,
        boxShadow: `0 4rpx 20rpx ${alphaColor(primaryColor.value, 0.06)}`
    })),
    activeBorder: computed(() => ({
        border: `2rpx solid ${alphaColor(primaryColor.value, 0.3)}`,
        boxShadow: `0 8rpx 32rpx ${alphaColor(primaryColor.value, 0.12)}`
    })),
    qBadge: computed(() => ({
        background: `linear-gradient(135deg, ${primaryColor.value} 0%, ${tintColor(primaryColor.value, 0.3)} 100%)`,
        boxShadow: `0 4rpx 16rpx ${alphaColor(primaryColor.value, 0.3)}`
    })),
    expandBg: computed(() => ({
        background: alphaColor(primaryColor.value, 0.08)
    })),
    expandIcon: computed(() => ({
        color: primaryColor.value
    })),
    tagBg: computed(() => ({
        background: `linear-gradient(135deg, ${alphaColor(primaryColor.value, 0.1)} 0%, ${alphaColor(primaryColor.value, 0.1)} 100%)`
    })),
    tagText: computed(() => ({
        color: primaryColor.value
    }))
}

const activeIndex = ref<number>(-1)
const searchKeyword = ref('')

// 过滤显示的问题
const showList = computed(() => {
    return props.content.data?.filter((item: any) => item.is_show == '1') || []
})

// 搜索过滤后的列表
const filteredList = computed(() => {
    if (!searchKeyword.value) {
        return showList.value
    }

    const keyword = searchKeyword.value.toLowerCase()
    return showList.value.filter((item: any) => {
        return (
            item.question.toLowerCase().includes(keyword) ||
            item.answer.toLowerCase().includes(keyword) ||
            (item.category && item.category.toLowerCase().includes(keyword))
        )
    })
})

// 切换折叠面板
const toggleAccordion = (index: number) => {
    activeIndex.value = activeIndex.value === index ? -1 : index
}

// 处理搜索
const handleSearch = () => {
    // 搜索时自动展开第一个结果
    if (filteredList.value.length > 0 && props.content.style == 1) {
        activeIndex.value = 0
    }
}

// 清除搜索
const clearSearch = () => {
    searchKeyword.value = ''
    activeIndex.value = -1
}
</script>

<style scoped lang="scss">
.faq-widget {
    margin: 20rpx;

    /* 标题区域 */
    .faq-header {
        display: flex;
        align-items: center;
        margin-bottom: 20rpx;
        gap: 12rpx;

        .header-decoration {
            width: 6rpx;
            height: 32rpx;
            border-radius: 3rpx;
        }

        .header-title {
            font-size: 32rpx;
            font-weight: 700;
            color: #1e293b;
            letter-spacing: -0.5rpx;
        }
    }

    /* 搜索容器 */
    .search-container {
        margin-bottom: 24rpx;

        .search-box {
            position: relative;
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20rpx);
            border-radius: 16rpx;
            padding: 16rpx 24rpx;
            transition: all 0.3s;
        }

        .search-icon {
            margin-right: 16rpx;
            display: flex;
            align-items: center;
        }

        .search-input {
            flex: 1;
            font-size: 28rpx;
            color: #1e293b;
            background: transparent;
            border: none;
            outline: none;

            &::placeholder {
                color: #94a3b8;
            }
        }

        .clear-icon {
            width: 40rpx;
            height: 40rpx;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(148, 163, 184, 0.1);
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.2s;

            &:active {
                background: rgba(148, 163, 184, 0.2);
                transform: scale(0.9);
            }
        }
    }

    /* 折叠面板样式 */
    .accordion-style {
        display: flex;
        flex-direction: column;
        gap: 16rpx;

        .accordion-item {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20rpx);
            border-radius: 20rpx;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .accordion-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20rpx;
            cursor: pointer;
        }

        .question-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 16rpx;
        }

        .question-icon {
            width: 48rpx;
            height: 48rpx;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24rpx;
            font-weight: 800;
            color: #ffffff;
            flex-shrink: 0;
        }

        .question-text {
            font-size: 28rpx;
            font-weight: 600;
            color: #1e293b;
            line-height: 1.5;
        }

        .expand-icon {
            width: 48rpx;
            height: 48rpx;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.3s;

            .icon-text {
                font-size: 40rpx;
                font-weight: 300;
                transform: rotate(90deg);
                transition: transform 0.3s;
            }

            &.is-expanded .icon-text {
                transform: rotate(270deg);
            }
        }

        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1);

            &.is-expanded {
                max-height: 2000rpx;
            }
        }

        .answer-wrapper {
            display: flex;
            gap: 16rpx;
            padding: 0 20rpx 20rpx 20rpx;
        }

        .answer-icon {
            width: 48rpx;
            height: 48rpx;
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24rpx;
            font-weight: 800;
            color: #ffffff;
            flex-shrink: 0;
            box-shadow: 0 4rpx 16rpx rgba(16, 185, 129, 0.3);
        }

        .answer-text {
            flex: 1;
            font-size: 26rpx;
            color: #475569;
            line-height: 1.7;
            padding-top: 4rpx;
        }

        .category-tag {
            margin: 12rpx 20rpx 20rpx 72rpx;
            display: inline-block;
            padding: 6rpx 16rpx;
            border-radius: 12rpx;

            .tag-text {
                font-size: 22rpx;
                font-weight: 600;
            }
        }
    }

    /* 列表式样式 */
    .list-style {
        display: flex;
        flex-direction: column;
        gap: 16rpx;

        .faq-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20rpx);
            border-radius: 14rpx;
            padding: 20rpx;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);

            &:active {
                transform: translateY(4rpx);
            }
        }

        .question-section {
            display: flex;
            gap: 16rpx;
            margin-bottom: 16rpx;
        }

        .q-badge {
            width: 48rpx;
            height: 48rpx;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24rpx;
            font-weight: 800;
            color: #ffffff;
            flex-shrink: 0;
        }

        .q-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8rpx;
        }

        .q-text {
            font-size: 28rpx;
            font-weight: 600;
            color: #1e293b;
            line-height: 1.5;
        }

        .q-category {
            display: inline-block;
            align-self: flex-start;
            padding: 6rpx 16rpx;
            border-radius: 12rpx;

            .category-text {
                font-size: 22rpx;
                font-weight: 600;
            }
        }

        .answer-section {
            display: flex;
            gap: 16rpx;
        }

        .a-badge {
            width: 48rpx;
            height: 48rpx;
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24rpx;
            font-weight: 800;
            color: #ffffff;
            flex-shrink: 0;
            box-shadow: 0 4rpx 16rpx rgba(16, 185, 129, 0.3);
        }

        .a-content {
            flex: 1;
            font-size: 26rpx;
            color: #475569;
            line-height: 1.7;
            padding-top: 4rpx;
        }
    }

    /* 无结果状态 */
    .no-result {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 80rpx 32rpx;
        background: rgba(255, 255, 255, 0.6);
        backdrop-filter: blur(20rpx);
        border: 2rpx dashed rgba(148, 163, 184, 0.3);
        border-radius: 14rpx;

        .no-result-text {
            font-size: 28rpx;
            color: #64748b;
            font-weight: 600;
            margin-top: 24rpx;
            margin-bottom: 8rpx;
        }

        .no-result-hint {
            font-size: 24rpx;
            color: #94a3b8;
        }
    }

    /* 响应式适配 */
    @media (prefers-reduced-motion: reduce) {
        .faq-widget * {
            transition: none !important;
            animation: none !important;
        }
    }
}
</style>
