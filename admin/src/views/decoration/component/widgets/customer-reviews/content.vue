<template>
    <div class="customer-reviews mx-[10px] mt-[10px]">
        <!-- 标题 -->
        <div v-if="content.title" class="flex items-center mb-[12px]">
            <div class="w-[4px] h-[17px] bg-primary rounded-full mr-[8px]"></div>
            <span class="text-base font-medium text-gray-900">{{ content.title }}</span>
            <div v-if="content.show_stats" class="ml-auto flex items-center">
                <icon name="el-icon-StarFilled" :size="16" color="#f59e0b" />
                <span class="text-base font-bold text-amber-500 ml-[4px]">{{ avgRating }}</span>
                <span class="text-xs text-gray-500 ml-[12px]">{{ showList.length }}条评价</span>
            </div>
        </div>

        <!-- 卡片样式 -->
        <div v-if="content.style == 1" class="review-cards space-y-[10px]">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="review-card bg-white rounded-lg p-[12px] shadow-sm"
            >
                <div class="flex items-center mb-[8px]">
                    <decoration-img
                        width="40px"
                        height="40px"
                        :src="item.avatar"
                        class="rounded-full"
                        fit="cover"
                    />
                    <div class="ml-[8px] flex-1">
                        <span class="text-sm font-medium text-gray-900">{{ item.name }}</span>
                        <div class="flex items-center mt-[2px]">
                            <icon
                                v-for="star in 5"
                                :key="star"
                                :name="star <= item.rating ? 'el-icon-StarFilled' : 'el-icon-Star'"
                                :size="12"
                                :color="star <= item.rating ? '#f59e0b' : '#e5e7eb'"
                            />
                            <span class="text-xs text-gray-400 ml-[6px]">{{ item.date }}</span>
                        </div>
                    </div>
                    <span v-if="item.tag" class="px-[8px] py-[3px] bg-primary/10 rounded-full text-xs text-primary">
                        {{ item.tag }}
                    </span>
                </div>
                <p class="text-xs text-gray-600 leading-relaxed line-clamp-2">{{ item.content }}</p>
                <div v-if="item.images && item.images.length" class="flex gap-[6px] mt-[8px]">
                    <decoration-img
                        v-for="(img, imgIndex) in item.images.slice(0, 3)"
                        :key="imgIndex"
                        width="60px"
                        height="60px"
                        :src="img"
                        class="rounded"
                        fit="cover"
                    />
                </div>
            </div>
        </div>

        <!-- 横向滑动样式 -->
        <div v-if="content.style == 2" class="review-scroll flex gap-[8px] overflow-x-auto pb-2">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="review-card bg-white rounded-lg p-[12px] shadow-sm flex-shrink-0"
                style="width: 280px"
            >
                <div class="flex items-center mb-[8px]">
                    <decoration-img
                        width="36px"
                        height="36px"
                        :src="item.avatar"
                        class="rounded-full"
                        fit="cover"
                    />
                    <div class="ml-[6px] flex-1">
                        <span class="text-xs font-medium text-gray-900">{{ item.name }}</span>
                        <div class="flex items-center mt-[2px]">
                            <icon
                                v-for="star in 5"
                                :key="star"
                                :name="star <= item.rating ? 'el-icon-StarFilled' : 'el-icon-Star'"
                                :size="10"
                                :color="star <= item.rating ? '#f59e0b' : '#e5e7eb'"
                            />
                        </div>
                    </div>
                </div>
                <p class="text-xs text-gray-600 line-clamp-3">{{ item.content }}</p>
            </div>
        </div>

        <!-- 简洁列表样式 -->
        <div v-if="content.style == 3" class="review-list bg-white rounded-lg overflow-hidden">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="review-item p-[12px]"
                :class="{ 'border-b border-gray-100': index < showList.length - 1 }"
            >
                <div class="flex items-start">
                    <decoration-img
                        width="32px"
                        height="32px"
                        :src="item.avatar"
                        class="rounded-full"
                        fit="cover"
                    />
                    <div class="ml-[8px] flex-1">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-gray-900">{{ item.name }}</span>
                            <div class="flex items-center">
                                <icon name="el-icon-StarFilled" :size="12" color="#f59e0b" />
                                <span class="text-xs text-amber-500 ml-[2px]">{{ item.rating }}</span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-600 mt-[4px] line-clamp-2">{{ item.content }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import type { PropType } from 'vue'
import DecorationImg from '../../decoration-img.vue'
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

const showList = computed(() => {
    const data = props.content.data?.filter((item: any) => item.is_show == '1') || []
    const limit = props.content.show_count || data.length
    return data.slice(0, limit)
})

const avgRating = computed(() => {
    if (!showList.value.length) return '5.0'
    const total = showList.value.reduce((sum: number, item: any) => sum + (item.rating || 5), 0)
    return (total / showList.value.length).toFixed(1)
})
</script>

<style lang="scss" scoped>
.customer-reviews {
    .review-card {
        border: 1px solid #f3f4f6;
    }
    
    .review-scroll {
        &::-webkit-scrollbar {
            display: none;
        }
    }
}
</style>
