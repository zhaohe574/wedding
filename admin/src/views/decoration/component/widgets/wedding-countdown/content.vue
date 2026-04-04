<template>
    <div class="wedding-countdown mx-[10px] mt-[10px]">
        <!-- 卡片式 -->
        <div
            v-if="content.style == 1"
            class="countdown-card p-6 rounded-2xl"
            :style="{ backgroundColor: content.bg_color }"
        >
            <div class="text-center">
                <div
                    class="text-lg font-medium mb-4"
                    :style="{ color: content.text_color }"
                >
                    {{ content.title }}
                </div>
                <div class="flex justify-center gap-3">
                    <div v-if="content.show_days" class="time-unit">
                        <div
                            class="time-number text-3xl font-bold mb-1"
                            :style="{ color: content.number_color }"
                        >
                            88
                        </div>
                        <div class="time-label text-sm" :style="{ color: content.text_color }">天</div>
                    </div>
                    <div v-if="content.show_hours" class="time-unit">
                        <div
                            class="time-number text-3xl font-bold mb-1"
                            :style="{ color: content.number_color }"
                        >
                            12
                        </div>
                        <div class="time-label text-sm" :style="{ color: content.text_color }">时</div>
                    </div>
                    <div v-if="content.show_minutes" class="time-unit">
                        <div
                            class="time-number text-3xl font-bold mb-1"
                            :style="{ color: content.number_color }"
                        >
                            30
                        </div>
                        <div class="time-label text-sm" :style="{ color: content.text_color }">分</div>
                    </div>
                    <div v-if="content.show_seconds" class="time-unit">
                        <div
                            class="time-number text-3xl font-bold mb-1"
                            :style="{ color: content.number_color }"
                        >
                            45
                        </div>
                        <div class="time-label text-sm" :style="{ color: content.text_color }">秒</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 简约式 -->
        <div
            v-if="content.style == 2"
            class="countdown-simple py-4 px-6 rounded-lg"
            :style="{ backgroundColor: content.bg_color }"
        >
            <div class="flex items-center justify-between">
                <div class="text-base font-medium" :style="{ color: content.text_color }">
                    {{ content.title }}
                </div>
                <div class="flex items-center gap-2">
                    <span v-if="content.show_days" class="time-item">
                        <span class="font-bold text-xl" :style="{ color: content.number_color }">88</span>
                        <span class="text-sm ml-1" :style="{ color: content.text_color }">天</span>
                    </span>
                    <span v-if="content.show_hours" class="time-item">
                        <span class="font-bold text-xl" :style="{ color: content.number_color }">12</span>
                        <span class="text-sm ml-1" :style="{ color: content.text_color }">时</span>
                    </span>
                    <span v-if="content.show_minutes" class="time-item">
                        <span class="font-bold text-xl" :style="{ color: content.number_color }">30</span>
                        <span class="text-sm ml-1" :style="{ color: content.text_color }">分</span>
                    </span>
                    <span v-if="content.show_seconds" class="time-item">
                        <span class="font-bold text-xl" :style="{ color: content.number_color }">45</span>
                        <span class="text-sm ml-1" :style="{ color: content.text_color }">秒</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- 浪漫式 -->
        <div
            v-if="content.style == 3"
            class="countdown-romantic p-6 rounded-2xl relative overflow-hidden"
            :style="{ backgroundColor: content.bg_color }"
        >
            <!-- 装饰元素 -->
            <div class="absolute top-2 right-2 text-4xl opacity-20">💕</div>
            <div class="absolute bottom-2 left-2 text-4xl opacity-20">💍</div>
            
            <div class="text-center relative z-10">
                <div
                    class="text-xl font-medium mb-6"
                    :style="{ color: content.text_color }"
                >
                    {{ content.title }}
                </div>
                <div class="flex justify-center gap-4">
                    <div v-if="content.show_days" class="time-box">
                        <div
                            class="w-16 h-16 rounded-xl bg-white/50 flex items-center justify-center mb-2"
                        >
                            <span
                                class="text-2xl font-bold"
                                :style="{ color: content.number_color }"
                            >
                                88
                            </span>
                        </div>
                        <div class="text-sm" :style="{ color: content.text_color }">天</div>
                    </div>
                    <div v-if="content.show_hours" class="time-box">
                        <div
                            class="w-16 h-16 rounded-xl bg-white/50 flex items-center justify-center mb-2"
                        >
                            <span
                                class="text-2xl font-bold"
                                :style="{ color: content.number_color }"
                            >
                                12
                            </span>
                        </div>
                        <div class="text-sm" :style="{ color: content.text_color }">时</div>
                    </div>
                    <div v-if="content.show_minutes" class="time-box">
                        <div
                            class="w-16 h-16 rounded-xl bg-white/50 flex items-center justify-center mb-2"
                        >
                            <span
                                class="text-2xl font-bold"
                                :style="{ color: content.number_color }"
                            >
                                30
                            </span>
                        </div>
                        <div class="text-sm" :style="{ color: content.text_color }">分</div>
                    </div>
                    <div v-if="content.show_seconds" class="time-box">
                        <div
                            class="w-16 h-16 rounded-xl bg-white/50 flex items-center justify-center mb-2"
                        >
                            <span
                                class="text-2xl font-bold"
                                :style="{ color: content.number_color }"
                            >
                                45
                            </span>
                        </div>
                        <div class="text-sm" :style="{ color: content.text_color }">秒</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import type { PropType } from 'vue'
import type options from './options'

type OptionsType = ReturnType<typeof options>

const props = defineProps({
    content: {
        type: Object as PropType<OptionsType['content']>,
        default: () => ({})
    },
    styles: {
        type: Object as PropType<OptionsType['styles']>,
        default: () => ({})
    }
})
</script>

<style lang="scss" scoped>
.wedding-countdown {
    .time-unit {
        text-align: center;
    }

    .time-item {
        display: inline-flex;
        align-items: baseline;
    }

    .time-box {
        text-align: center;
    }
}
</style>
