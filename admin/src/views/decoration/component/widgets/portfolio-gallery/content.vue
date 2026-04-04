<template>
    <div class="portfolio-gallery mx-[10px] mt-[10px]">
        <!-- 标题 -->
        <div v-if="content.title" class="flex items-center mb-[12px]">
            <div class="w-[4px] h-[17px] bg-primary rounded-full mr-[8px]"></div>
            <span class="text-base font-medium text-gray-900">{{ content.title }}</span>
            <div class="flex-1"></div>
            <div v-if="content.show_more" class="flex items-center text-xs text-gray-500">
                <span>查看更多</span>
                <icon name="el-icon-ArrowRight" :size="12" />
            </div>
        </div>

        <!-- 网格样式 -->
        <div v-if="content.style == 1" class="grid gap-[6px]" :style="gridStyle">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="portfolio-item relative rounded-lg overflow-hidden"
            >
                <decoration-img
                    width="100%"
                    :height="content.per_line == 2 ? '170px' : '120px'"
                    :src="item.cover"
                    fit="cover"
                />
                <div v-if="item.type === 'video'" class="absolute inset-0 flex items-center justify-center">
                    <div class="w-[40px] h-[40px] bg-black/50 rounded-full flex items-center justify-center">
                        <icon name="el-icon-VideoPlay" :size="20" color="#fff" />
                    </div>
                </div>
                <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-[8px]">
                    <span class="text-white text-xs line-clamp-1">{{ item.title }}</span>
                </div>
            </div>
        </div>

        <!-- 瀑布流样式 -->
        <div v-if="content.style == 2" class="waterfall flex gap-[6px]">
            <div class="flex-1 flex flex-col gap-[6px]">
                <div
                    v-for="(item, index) in leftColumn"
                    :key="index"
                    class="portfolio-item relative rounded-lg overflow-hidden"
                >
                    <decoration-img
                        width="100%"
                        height="180px"
                        :src="item.cover"
                        fit="cover"
                    />
                    <div v-if="item.type === 'video'" class="absolute inset-0 flex items-center justify-center">
                        <div class="w-[40px] h-[40px] bg-black/50 rounded-full flex items-center justify-center">
                            <icon name="el-icon-VideoPlay" :size="20" color="#fff" />
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-[8px]">
                        <span class="text-white text-xs line-clamp-1">{{ item.title }}</span>
                    </div>
                </div>
            </div>
            <div class="flex-1 flex flex-col gap-[6px]">
                <div
                    v-for="(item, index) in rightColumn"
                    :key="index"
                    class="portfolio-item relative rounded-lg overflow-hidden"
                >
                    <decoration-img
                        width="100%"
                        height="180px"
                        :src="item.cover"
                        fit="cover"
                    />
                    <div v-if="item.type === 'video'" class="absolute inset-0 flex items-center justify-center">
                        <div class="w-[40px] h-[40px] bg-black/50 rounded-full flex items-center justify-center">
                            <icon name="el-icon-VideoPlay" :size="20" color="#fff" />
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-[8px]">
                        <span class="text-white text-xs line-clamp-1">{{ item.title }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 横向滑动样式 -->
        <div v-if="content.style == 3" class="portfolio-scroll flex gap-[8px] overflow-x-auto pb-2">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="portfolio-item relative rounded-lg overflow-hidden flex-shrink-0"
                style="width: 240px"
            >
                <decoration-img
                    width="240px"
                    height="180px"
                    :src="item.cover"
                    fit="cover"
                />
                <div v-if="item.type === 'video'" class="absolute inset-0 flex items-center justify-center">
                    <div class="w-[50px] h-[50px] bg-black/50 rounded-full flex items-center justify-center">
                        <icon name="el-icon-VideoPlay" :size="24" color="#fff" />
                    </div>
                </div>
                <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-[10px]">
                    <span class="text-white text-sm font-medium line-clamp-1">{{ item.title }}</span>
                    <span v-if="item.category" class="text-white/70 text-xs">{{ item.category }}</span>
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

const gridStyle = computed(() => {
    const perLine = props.content.per_line || 2
    return {
        'grid-template-columns': `repeat(${perLine}, 1fr)`
    }
})

const leftColumn = computed(() => {
    return showList.value.filter((_: any, i: number) => i % 2 === 0)
})

const rightColumn = computed(() => {
    return showList.value.filter((_: any, i: number) => i % 2 === 1)
})
</script>

<style lang="scss" scoped>
.portfolio-gallery {
    .portfolio-item {
        border: 1px solid #f3f4f6;
    }
    
    .portfolio-scroll {
        &::-webkit-scrollbar {
            display: none;
        }
    }
}
</style>
