<template>
    <div class="data-stats bg-white pt-[15px] pb-[15px] px-[10px]">
        <!-- 横向排列 -->
        <div v-if="content.style == 1" class="flex gap-x-4 overflow-x-auto pb-2">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="flex flex-col items-center flex-shrink-0 min-w-[80px]"
            >
                <decoration-img
                    width="40px"
                    height="40px"
                    :src="item.icon"
                    class="rounded-lg"
                />
                <div class="mt-[8px] text-lg font-bold text-gray-900">{{ item.mockValue }}</div>
                <div class="text-xs text-gray-500">{{ item.title }}</div>
            </div>
        </div>

        <!-- 纵向排列 -->
        <div v-if="content.style == 2" class="space-y-3">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="flex items-center p-3 bg-gray-50 rounded-lg"
            >
                <decoration-img
                    width="40px"
                    height="40px"
                    :src="item.icon"
                    class="rounded-lg mr-3"
                />
                <div class="flex-1">
                    <div class="text-sm text-gray-700">{{ item.title }}</div>
                    <div class="text-lg font-bold text-gray-900 mt-1">
                        {{ item.mockValue }}
                        <span class="text-xs text-gray-500 font-normal ml-1">{{ item.unit }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 网格布局 -->
        <div v-if="content.style == 3" class="grid grid-cols-2 gap-3">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="flex flex-col items-center p-3 bg-gray-50 rounded-lg"
            >
                <decoration-img
                    width="40px"
                    height="40px"
                    :src="item.icon"
                    class="rounded-lg"
                />
                <div class="mt-[8px] text-lg font-bold text-gray-900">{{ item.mockValue }}</div>
                <div class="text-xs text-gray-500">{{ item.title }}</div>
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
    // 添加模拟数据用于预览
    return data.map((item: any) => ({
        ...item,
        mockValue: getMockValue(item.value)
    }))
})

// 获取模拟数据
function getMockValue(type: string): number {
    const mockData: Record<string, number> = {
        order_count: 12,
        coupon_count: 5,
        collect_count: 28,
        view_count: 156,
        points: 2580,
        balance: 368
    }
    return mockData[type] || 0
}
</script>

<style lang="scss" scoped>
.data-stats {
    .overflow-x-auto {
        &::-webkit-scrollbar {
            display: none;
        }
    }
}
</style>
