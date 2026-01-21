<template>
    <div class="activity-zone mx-[10px] mt-[10px]">
        <!-- 标题 -->
        <div v-if="content.title" class="flex items-center mb-[12px]">
            <div class="w-[4px] h-[17px] bg-primary rounded-full mr-[8px]"></div>
            <span class="text-base font-medium text-gray-900">{{ content.title }}</span>
            <div class="flex-1"></div>
            <div v-if="content.show_more" class="flex items-center text-xs text-gray-500">
                <span>更多活动</span>
                <icon name="el-icon-ArrowRight" :size="12" />
            </div>
        </div>

        <!-- 大图样式 -->
        <div v-if="content.style == 1" class="activity-banner space-y-[8px]">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="activity-item relative rounded-lg overflow-hidden"
            >
                <decoration-img
                    width="100%"
                    height="160px"
                    :src="item.image"
                    fit="cover"
                />
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent flex flex-col justify-end p-[12px]">
                    <span v-if="item.tag" class="absolute top-[8px] left-[8px] px-[8px] py-[3px] bg-red-500 rounded-full text-white text-xs">
                        {{ item.tag }}
                    </span>
                    <span class="text-white text-base font-bold">{{ item.title }}</span>
                    <div class="flex items-center justify-between mt-[6px]">
                        <div v-if="item.price" class="flex items-baseline">
                            <span class="text-xs text-white/80">¥</span>
                            <span class="text-lg font-bold text-white">{{ item.price }}</span>
                        </div>
                        <span class="px-[12px] py-[6px] bg-white rounded-full text-primary text-xs font-medium">立即参与</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 卡片网格样式 -->
        <div v-if="content.style == 2" class="activity-grid grid grid-cols-2 gap-[8px]">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="activity-item bg-white rounded-lg overflow-hidden shadow-sm"
            >
                <div class="relative">
                    <decoration-img
                        width="100%"
                        height="100px"
                        :src="item.image"
                        fit="cover"
                    />
                    <span v-if="item.tag" class="absolute top-[6px] left-[6px] px-[6px] py-[2px] bg-red-500 rounded-full text-white text-xs">
                        {{ item.tag }}
                    </span>
                </div>
                <div class="p-[8px]">
                    <span class="text-xs font-medium text-gray-900 line-clamp-1">{{ item.title }}</span>
                    <div v-if="item.price" class="flex items-baseline mt-[4px]">
                        <span class="text-xs text-primary">¥</span>
                        <span class="text-sm font-bold text-primary">{{ item.price }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 横向滑动样式 -->
        <div v-if="content.style == 3" class="activity-scroll flex gap-[8px] overflow-x-auto pb-2">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="activity-item relative rounded-lg overflow-hidden flex-shrink-0"
                style="width: 280px"
            >
                <decoration-img
                    width="280px"
                    height="140px"
                    :src="item.image"
                    fit="cover"
                />
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent flex flex-col justify-end p-[10px]">
                    <span v-if="item.tag" class="absolute top-[6px] left-[6px] px-[6px] py-[2px] bg-red-500 rounded-full text-white text-xs">
                        {{ item.tag }}
                    </span>
                    <span class="text-white text-sm font-bold line-clamp-1">{{ item.title }}</span>
                </div>
            </div>
        </div>

        <!-- 列表样式 -->
        <div v-if="content.style == 4" class="activity-list space-y-[8px]">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="activity-item bg-white rounded-lg overflow-hidden shadow-sm"
            >
                <div class="flex">
                    <div class="relative flex-shrink-0">
                        <decoration-img
                            width="120px"
                            height="90px"
                            :src="item.image"
                            fit="cover"
                        />
                        <span v-if="item.tag" class="absolute top-[6px] left-[6px] px-[6px] py-[2px] bg-red-500 rounded-full text-white text-xs">
                            {{ item.tag }}
                        </span>
                    </div>
                    <div class="flex-1 p-[10px] flex flex-col justify-between">
                        <span class="text-sm font-medium text-gray-900 line-clamp-1">{{ item.title }}</span>
                        <div class="flex items-center justify-between">
                            <div v-if="item.price" class="flex items-baseline">
                                <span class="text-xs text-primary">¥</span>
                                <span class="text-base font-bold text-primary">{{ item.price }}</span>
                            </div>
                            <span class="px-[10px] py-[4px] bg-primary rounded-full text-white text-xs">立即参与</span>
                        </div>
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
</script>

<style lang="scss" scoped>
.activity-zone {
    .activity-item {
        border: 1px solid #f3f4f6;
    }
    
    .activity-scroll {
        &::-webkit-scrollbar {
            display: none;
        }
    }
}
</style>
