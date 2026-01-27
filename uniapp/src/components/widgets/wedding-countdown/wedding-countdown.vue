<template>
    <view v-if="content.enabled && hasWedding" class="wedding-countdown mx-[20rpx] mt-[20rpx]">
        <!-- 卡片式样式 -->
        <view
            v-if="content.style == 1"
            class="card-style rounded-lg p-[40rpx] overflow-hidden relative"
            :style="{ backgroundColor: content.bg_color || '#FFF5F5' }"
        >
            <!-- 标题 -->
            <view class="text-center mb-[40rpx]">
                <text class="text-xl font-medium" :style="{ color: content.text_color || '#333333' }">
                    {{ content.title || '距离我们的婚礼还有' }}
                </text>
            </view>

            <!-- 倒计时数字 -->
            <view class="flex justify-center items-center gap-3">
                <view v-if="content.show_days" class="time-unit text-center">
                    <view
                        class="time-number text-4xl font-bold mb-[8rpx]"
                        :style="{ color: content.number_color || '#FF6B9D' }"
                    >
                        {{ countdown.days }}
                    </view>
                    <text class="text-sm" :style="{ color: content.text_color || '#333333' }">天</text>
                </view>

                <view v-if="content.show_hours" class="time-unit text-center">
                    <view
                        class="time-number text-4xl font-bold mb-[8rpx]"
                        :style="{ color: content.number_color || '#FF6B9D' }"
                    >
                        {{ countdown.hours }}
                    </view>
                    <text class="text-sm" :style="{ color: content.text_color || '#333333' }">时</text>
                </view>

                <view v-if="content.show_minutes" class="time-unit text-center">
                    <view
                        class="time-number text-4xl font-bold mb-[8rpx]"
                        :style="{ color: content.number_color || '#FF6B9D' }"
                    >
                        {{ countdown.minutes }}
                    </view>
                    <text class="text-sm" :style="{ color: content.text_color || '#333333' }">分</text>
                </view>

                <view v-if="content.show_seconds" class="time-unit text-center">
                    <view
                        class="time-number text-4xl font-bold mb-[8rpx]"
                        :style="{ color: content.number_color || '#FF6B9D' }"
                    >
                        {{ countdown.seconds }}
                    </view>
                    <text class="text-sm" :style="{ color: content.text_color || '#333333' }">秒</text>
                </view>
            </view>

            <!-- 装饰元素 -->
            <view class="absolute top-[20rpx] left-[20rpx] opacity-20">
                <tn-icon name="heart" size="60" :color="content.number_color || '#FF6B9D'"></tn-icon>
            </view>
            <view class="absolute bottom-[20rpx] right-[20rpx] opacity-20">
                <tn-icon name="heart" size="60" :color="content.number_color || '#FF6B9D'"></tn-icon>
            </view>
        </view>

        <!-- 简约式样式 -->
        <view
            v-if="content.style == 2"
            class="simple-style rounded-lg p-[32rpx]"
            :style="{ backgroundColor: content.bg_color || '#FFFFFF' }"
        >
            <!-- 标题 -->
            <view class="text-center mb-[24rpx]">
                <text class="text-base" :style="{ color: content.text_color || '#666666' }">
                    {{ content.title || '距离我们的婚礼还有' }}
                </text>
            </view>

            <!-- 倒计时数字（横向排列） -->
            <view class="flex justify-center items-center">
                <view v-if="content.show_days" class="flex items-baseline mr-[16rpx]">
                    <text class="text-3xl font-bold" :style="{ color: content.number_color || '#FF6B9D' }">
                        {{ countdown.days }}
                    </text>
                    <text class="text-xs ml-[4rpx]" :style="{ color: content.text_color || '#666666' }">天</text>
                </view>

                <view v-if="content.show_hours" class="flex items-baseline mr-[16rpx]">
                    <text class="text-3xl font-bold" :style="{ color: content.number_color || '#FF6B9D' }">
                        {{ countdown.hours }}
                    </text>
                    <text class="text-xs ml-[4rpx]" :style="{ color: content.text_color || '#666666' }">时</text>
                </view>

                <view v-if="content.show_minutes" class="flex items-baseline mr-[16rpx]">
                    <text class="text-3xl font-bold" :style="{ color: content.number_color || '#FF6B9D' }">
                        {{ countdown.minutes }}
                    </text>
                    <text class="text-xs ml-[4rpx]" :style="{ color: content.text_color || '#666666' }">分</text>
                </view>

                <view v-if="content.show_seconds" class="flex items-baseline">
                    <text class="text-3xl font-bold" :style="{ color: content.number_color || '#FF6B9D' }">
                        {{ countdown.seconds }}
                    </text>
                    <text class="text-xs ml-[4rpx]" :style="{ color: content.text_color || '#666666' }">秒</text>
                </view>
            </view>
        </view>

        <!-- 浪漫式样式 -->
        <view
            v-if="content.style == 3"
            class="romantic-style rounded-lg p-[40rpx] overflow-hidden relative"
            :style="{ backgroundColor: content.bg_color || '#FFE8F0' }"
        >
            <!-- 装饰图案 -->
            <view class="absolute top-0 left-0 right-0 h-[100rpx] flex justify-around items-center opacity-30">
                <tn-icon name="heart-fill" size="40" :color="content.number_color || '#FF6B9D'"></tn-icon>
                <tn-icon name="heart-fill" size="32" :color="content.number_color || '#FF6B9D'"></tn-icon>
                <tn-icon name="heart-fill" size="40" :color="content.number_color || '#FF6B9D'"></tn-icon>
            </view>

            <!-- 标题 -->
            <view class="text-center mb-[32rpx] relative z-10">
                <text class="text-lg font-medium" :style="{ color: content.text_color || '#333333' }">
                    {{ content.title || '距离我们的婚礼还有' }}
                </text>
            </view>

            <!-- 倒计时数字（网格布局） -->
            <view class="grid grid-cols-2 gap-4 relative z-10">
                <view
                    v-if="content.show_days"
                    class="time-box bg-white/80 rounded-lg p-[24rpx] text-center"
                >
                    <view
                        class="time-number text-3xl font-bold mb-[8rpx]"
                        :style="{ color: content.number_color || '#FF6B9D' }"
                    >
                        {{ countdown.days }}
                    </view>
                    <text class="text-xs" :style="{ color: content.text_color || '#666666' }">天</text>
                </view>

                <view
                    v-if="content.show_hours"
                    class="time-box bg-white/80 rounded-lg p-[24rpx] text-center"
                >
                    <view
                        class="time-number text-3xl font-bold mb-[8rpx]"
                        :style="{ color: content.number_color || '#FF6B9D' }"
                    >
                        {{ countdown.hours }}
                    </view>
                    <text class="text-xs" :style="{ color: content.text_color || '#666666' }">时</text>
                </view>

                <view
                    v-if="content.show_minutes"
                    class="time-box bg-white/80 rounded-lg p-[24rpx] text-center"
                >
                    <view
                        class="time-number text-3xl font-bold mb-[8rpx]"
                        :style="{ color: content.number_color || '#FF6B9D' }"
                    >
                        {{ countdown.minutes }}
                    </view>
                    <text class="text-xs" :style="{ color: content.text_color || '#666666' }">分</text>
                </view>

                <view
                    v-if="content.show_seconds"
                    class="time-box bg-white/80 rounded-lg p-[24rpx] text-center"
                >
                    <view
                        class="time-number text-3xl font-bold mb-[8rpx]"
                        :style="{ color: content.number_color || '#FF6B9D' }"
                    >
                        {{ countdown.seconds }}
                    </view>
                    <text class="text-xs" :style="{ color: content.text_color || '#666666' }">秒</text>
                </view>
            </view>

            <!-- 底部装饰 -->
            <view class="absolute bottom-0 left-0 right-0 h-[100rpx] flex justify-around items-center opacity-30">
                <tn-icon name="heart-fill" size="32" :color="content.number_color || '#FF6B9D'"></tn-icon>
                <tn-icon name="heart-fill" size="40" :color="content.number_color || '#FF6B9D'"></tn-icon>
                <tn-icon name="heart-fill" size="32" :color="content.number_color || '#FF6B9D'"></tn-icon>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { getUserWeddingDate } from '@/api/user'

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

// 是否有婚期
const hasWedding = ref(false)

// 婚期日期
const weddingDate = ref<Date | null>(null)

// 倒计时数据
const countdown = ref({
    days: 0,
    hours: 0,
    minutes: 0,
    seconds: 0
})

// 定时器
let timer: any = null

// 计算倒计时
const calculateCountdown = () => {
    if (!weddingDate.value) return

    const now = new Date().getTime()
    const target = weddingDate.value.getTime()
    const diff = target - now

    if (diff <= 0) {
        // 婚期已过
        countdown.value = { days: 0, hours: 0, minutes: 0, seconds: 0 }
        clearInterval(timer)
        return
    }

    // 计算天、时、分、秒
    countdown.value.days = Math.floor(diff / (1000 * 60 * 60 * 24))
    countdown.value.hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
    countdown.value.minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))
    countdown.value.seconds = Math.floor((diff % (1000 * 60)) / 1000)
}

// 加载婚期数据
const loadWeddingDate = async () => {
    try {
        const res = await getUserWeddingDate()
        if (res.has_order && res.wedding_date) {
            hasWedding.value = true
            weddingDate.value = new Date(res.wedding_date)
            
            // 立即计算一次
            calculateCountdown()
            
            // 启动定时器，每秒更新
            timer = setInterval(() => {
                calculateCountdown()
            }, 1000)
        } else {
            hasWedding.value = false
        }
    } catch (error) {
        console.error('获取婚期失败:', error)
        hasWedding.value = false
    }
}

onMounted(() => {
    loadWeddingDate()
})

onUnmounted(() => {
    if (timer) {
        clearInterval(timer)
    }
})
</script>

<style scoped lang="scss">
.wedding-countdown {
    .card-style,
    .simple-style,
    .romantic-style {
        box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.05);
    }

    .time-unit {
        min-width: 100rpx;
    }

    .time-number {
        line-height: 1;
    }

    .grid {
        display: grid;
    }

    .grid-cols-2 {
        grid-template-columns: repeat(2, 1fr);
    }

    .gap-3 {
        gap: 24rpx;
    }

    .gap-4 {
        gap: 32rpx;
    }
}
</style>
