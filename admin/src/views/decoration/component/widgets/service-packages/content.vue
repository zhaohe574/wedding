<template>
    <div class="service-packages mx-[10px] mt-[10px]">
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

        <!-- 横向滑动样式 -->
        <div v-if="content.style == 1" class="package-scroll flex gap-[10px] overflow-x-auto pb-2">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="package-card flex-shrink-0 bg-white rounded-lg overflow-hidden shadow-sm"
                style="width: 260px"
            >
                <!-- 封面图 -->
                <div class="relative">
                    <decoration-img
                        width="100%"
                        height="150px"
                        :src="item.image"
                        fit="cover"
                    />
                    <div 
                        v-if="item.tag" 
                        class="absolute top-[8px] left-[8px] px-[8px] py-[3px] bg-primary rounded-full"
                    >
                        <span class="text-white text-xs">{{ item.tag }}</span>
                    </div>
                </div>
                <!-- 信息 -->
                <div class="p-[12px]">
                    <span class="text-sm font-medium text-gray-900 line-clamp-1">{{ item.name }}</span>
                    <div class="flex items-baseline mt-[6px]">
                        <span class="text-xs text-primary">¥</span>
                        <span class="text-lg font-bold text-primary">{{ item.price }}</span>
                        <span v-if="item.original_price" class="text-xs text-gray-400 line-through ml-[6px]">
                            ¥{{ item.original_price }}
                        </span>
                    </div>
                    <!-- 服务项 -->
                    <div v-if="item.services && item.services.length" class="mt-[8px]">
                        <div class="flex flex-wrap gap-[4px]">
                            <span
                                v-for="(service, sIndex) in item.services.slice(0, 3)"
                                :key="sIndex"
                                class="text-xs text-gray-500"
                            >
                                ✓ {{ service }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 纵向列表样式 -->
        <div v-if="content.style == 2" class="package-list space-y-[10px]">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="package-card bg-white rounded-lg overflow-hidden shadow-sm"
            >
                <div class="flex">
                    <!-- 封面图 -->
                    <div class="relative flex-shrink-0">
                        <decoration-img
                            width="120px"
                            height="120px"
                            :src="item.image"
                            fit="cover"
                        />
                        <div 
                            v-if="item.tag" 
                            class="absolute top-[6px] left-[6px] px-[6px] py-[2px] bg-primary rounded-full"
                        >
                            <span class="text-white text-xs">{{ item.tag }}</span>
                        </div>
                    </div>
                    <!-- 信息 -->
                    <div class="flex-1 p-[12px] flex flex-col justify-between">
                        <div>
                            <span class="text-sm font-medium text-gray-900 line-clamp-1">{{ item.name }}</span>
                            <span v-if="item.desc" class="text-xs text-gray-500 mt-[4px] line-clamp-2">{{ item.desc }}</span>
                        </div>
                        <div class="flex items-center justify-between mt-[6px]">
                            <div class="flex items-baseline">
                                <span class="text-xs text-primary">¥</span>
                                <span class="text-base font-bold text-primary">{{ item.price }}</span>
                            </div>
                            <span class="px-[10px] py-[4px] bg-primary rounded-full text-white text-xs">查看详情</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 大卡片样式 -->
        <div v-if="content.style == 3" class="package-grid space-y-[10px]">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="package-card bg-white rounded-lg overflow-hidden shadow-sm"
            >
                <!-- 封面图 -->
                <div class="relative">
                    <decoration-img
                        width="100%"
                        height="180px"
                        :src="item.image"
                        fit="cover"
                    />
                    <div 
                        v-if="item.tag" 
                        class="absolute top-[10px] left-[10px] px-[10px] py-[4px] bg-primary rounded-full"
                    >
                        <span class="text-white text-sm">{{ item.tag }}</span>
                    </div>
                    <!-- 价格悬浮 -->
                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-[12px]">
                        <div class="flex items-baseline">
                            <span class="text-sm text-white/80">¥</span>
                            <span class="text-xl font-bold text-white">{{ item.price }}</span>
                            <span class="text-sm text-white/60 ml-[4px]">起</span>
                        </div>
                    </div>
                </div>
                <!-- 信息 -->
                <div class="p-[12px]">
                    <span class="text-base font-medium text-gray-900">{{ item.name }}</span>
                    <span v-if="item.desc" class="text-xs text-gray-500 mt-[4px] line-clamp-2">{{ item.desc }}</span>
                    <!-- 服务项 -->
                    <div v-if="item.services && item.services.length" class="mt-[8px]">
                        <div class="flex flex-wrap gap-[6px]">
                            <span
                                v-for="(service, sIndex) in item.services.slice(0, 4)"
                                :key="sIndex"
                                class="px-[8px] py-[3px] bg-gray-100 rounded-full text-xs text-gray-600"
                            >
                                {{ service }}
                            </span>
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
.service-packages {
    .package-card {
        border: 1px solid #f3f4f6;
    }
    
    .package-scroll {
        &::-webkit-scrollbar {
            display: none;
        }
    }
}
</style>
