<template>
    <view v-if="content.enabled !== 0 && showList.length" class="quick-entry-widget mx-[24rpx] mt-[16rpx]">
        <view v-if="isProfileStyle" class="profile-quick-card">
            <view class="profile-quick-title-row">
                <text class="profile-quick-title">快捷功能</text>
                <text class="profile-quick-subtitle">常用入口</text>
            </view>

            <view class="profile-quick-grid">
                <view
                    v-for="(item, index) in showList"
                    :key="item.key || index"
                    class="profile-quick-item"
                    :class="{
                        'profile-quick-item--primary': index === 0,
                        'profile-quick-item--disabled': !!item.disabled
                    }"
                    @click="handleClick(item)"
                >
                    <text class="profile-quick-item-title">{{ item.title }}</text>
                    <text class="profile-quick-item-desc">{{ item.subtitle || '点击进入' }}</text>
                </view>
            </view>
        </view>

        <view v-else-if="content.style == 1" class="grid-layout">
            <view class="entries-grid" :style="{ 'grid-template-columns': `repeat(${content.per_line || 4}, 1fr)` }">
                <view
                    v-for="(item, index) in showList"
                    :key="index"
                    class="entry-item"
                    @click="handleClick(item)"
                >
                    <view class="icon-wrapper">
                        <view class="icon-bg" :style="{ backgroundColor: getIconBg(index) }" />
                        <image
                            lazy-load
                            class="entry-icon"
                            :src="getImageUrl(item.icon || '')"
                            :alt="item.title"
                            mode="aspectFit"
                        />
                    </view>
                    <text class="entry-title">{{ item.title }}</text>
                </view>
            </view>
        </view>

        <view v-else class="scroll-layout">
            <scroll-view scroll-x class="scroll-container" show-scrollbar="false">
                <view class="entries-scroll">
                    <view
                        v-for="(item, index) in showList"
                        :key="index"
                        class="entry-item"
                        @click="handleClick(item)"
                    >
                        <view class="icon-wrapper">
                            <view class="icon-bg" :style="{ backgroundColor: getIconBg(index) }"></view>
                            <image
                                lazy-load
                                class="entry-icon"
                                :src="getImageUrl(item.icon || '')"
                                :alt="item.title"
                                mode="aspectFit"
                            />
                        </view>
                        <text class="entry-title">{{ item.title }}</text>
                    </view>
                </view>
            </scroll-view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { useAppStore } from '@/stores/app'
import { useThemeStore } from '@/stores/theme'
import { navigateTo } from '@/utils/util'
import { computed } from 'vue'

interface QuickEntryItem {
    key?: string
    icon?: string
    title: string
    subtitle?: string
    link?: any
    is_show?: string
    disabled?: boolean
}

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

const appStore = useAppStore()
const $theme = useThemeStore()
const { getImageUrl } = appStore

const isProfileStyle = computed(() => Number(props.content?.style || 0) === 3)

const showList = computed<QuickEntryItem[]>(() => {
    const list = Array.isArray(props.content?.data) ? props.content.data : []
    return list.filter((item: QuickEntryItem) => String(item.is_show ?? '1') !== '0')
})

const getIconBg = (index: number) => {
    const colors = [
        $theme.primaryColor + '15',
        $theme.secondaryColor + '15',
        $theme.ctaColor + '15',
        $theme.accentColor + '15'
    ]
    return colors[index % colors.length]
}

const handleClick = (item: QuickEntryItem) => {
    if (item.disabled) {
        uni.showToast({ title: item.subtitle || '当前不可用', icon: 'none' })
        return
    }
    navigateTo(item.link)
}
</script>

<style scoped lang="scss">
.quick-entry-widget {
    position: relative;

    .profile-quick-card {
        background: rgba(255, 255, 255, 0.84);
        border: 1rpx solid #efe6e1;
        border-radius: 24rpx;
        padding: 18rpx 16rpx 16rpx;
        box-shadow: 0 16rpx 38rpx rgba(214, 185, 167, 0.12);
    }

    .profile-quick-title-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12rpx;
        padding: 0 6rpx;
    }

    .profile-quick-title {
        font-size: 30rpx;
        font-weight: 700;
        color: #1e2432;
    }

    .profile-quick-subtitle {
        font-size: 22rpx;
        font-weight: 600;
        color: #7f7b78;
    }

    .profile-quick-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10rpx;
    }

    .profile-quick-item {
        border-radius: 18rpx;
        padding: 16rpx 14rpx;
        background: #fff8f5;
        border: 1rpx solid #efe6e1;
        transition: all 0.2s ease;

        &:active {
            transform: translateY(1rpx);
            opacity: 0.92;
        }
    }

    .profile-quick-item--primary {
        background: #fff1ee;
        border-color: #f4c7bf;
    }

    .profile-quick-item--disabled {
        opacity: 0.56;
    }

    .profile-quick-item-title {
        display: block;
        font-size: 25rpx;
        line-height: 1.4;
        font-weight: 700;
        color: #1e2432;
    }

    .profile-quick-item-desc {
        display: block;
        margin-top: 6rpx;
        font-size: 21rpx;
        line-height: 1.5;
        color: #7f7b78;
    }

    .grid-layout {
        background: linear-gradient(180deg, var(--cinema-surface-elevated, #fffdf8) 0%, var(--cinema-surface, #f6f2ea) 100%);
        border: 1rpx solid var(--cinema-border, rgba(198, 168, 106, 0.24));
        border-radius: 28rpx;
        padding: 28rpx 18rpx;
        box-shadow: var(--cinema-shadow-soft, 0 18rpx 44rpx rgba(8, 10, 16, 0.08));

        .entries-grid {
            display: grid;
            gap: 20rpx 12rpx;
            width: 100%;
        }
    }

    .scroll-layout {
        background: linear-gradient(180deg, var(--cinema-surface-elevated, #fffdf8) 0%, var(--cinema-surface, #f6f2ea) 100%);
        border: 1rpx solid var(--cinema-border, rgba(198, 168, 106, 0.24));
        border-radius: 28rpx;
        padding: 28rpx 0;
        box-shadow: var(--cinema-shadow-soft, 0 18rpx 44rpx rgba(8, 10, 16, 0.08));

        .scroll-container {
            white-space: nowrap;
        }

        .entries-scroll {
            display: inline-flex;
            gap: 20rpx;
            padding: 0 20rpx;
        }
    }

    .entry-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12rpx;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);

        &:active {
            transform: translateY(2rpx) scale(0.97);
            opacity: 0.86;
        }
    }

    .icon-wrapper {
        position: relative;
        width: 80rpx;
        height: 80rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;

        .icon-bg {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 22rpx;
            transition: all 0.2s ease;
            z-index: 0;
            border: 1rpx solid rgba(255, 255, 255, 0.42);
            box-shadow: inset 0 1rpx 0 rgba(255, 255, 255, 0.45);
        }

        .entry-icon {
            width: 44rpx;
            height: 44rpx;
            position: relative;
            z-index: 2;
        }
    }

    .entry-title {
        font-size: 26rpx;
        font-weight: 600;
        color: var(--cinema-text-primary, #151a23);
        text-align: center;
        line-height: 1.4;
        max-width: 120rpx;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
}
</style>
