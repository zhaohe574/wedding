<template>
    <div class="data-stats bg-white pt-[15px] pb-[15px] px-[10px]">
        <div v-if="!showList.length" class="data-stats__empty">
            暂无可预览统计项，请先选择需要展示的真实字段。
        </div>

        <!-- 横向排列 -->
        <div v-else-if="content.style == 1" class="flex gap-x-4 overflow-x-auto pb-2">
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
                <div class="mt-[8px] text-lg font-bold text-gray-900">{{ item.previewValue }}</div>
                <div class="text-xs text-gray-500">{{ item.title }}</div>
            </div>
        </div>

        <!-- 纵向排列 -->
        <div v-else-if="content.style == 2" class="space-y-3">
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
                        {{ item.previewValue }}
                        <span class="text-xs text-gray-500 font-normal ml-1">{{ item.unit }}</span>
                    </div>
                    <div class="text-xs text-gray-400 mt-1">{{ item.previewDesc }}</div>
                </div>
            </div>
        </div>

        <!-- 网格布局 -->
        <div v-else class="grid grid-cols-2 gap-3">
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
                <div class="mt-[8px] text-lg font-bold text-gray-900">{{ item.previewValue }}</div>
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
    return data.map((item: any) => ({
        ...item,
        previewValue: getPreviewValue(item.value),
        previewDesc: getPreviewDesc(item.value)
    }))
})

function getPreviewValue(type: string): string {
    const previewMap: Record<string, string> = {
        order_count: '用户实时订单数',
        collect_count: '用户实时收藏数',
        view_count: '用户实时浏览数',
        points: '用户实时积分',
        balance: '用户实时账户余额'
    }

    return previewMap[type] || '实时数据'
}

function getPreviewDesc(type: string): string {
    const descMap: Record<string, string> = {
        order_count: '前台按当前登录用户订单数据渲染',
        collect_count: '前台按当前登录用户收藏数据渲染',
        view_count: '前台按当前登录用户浏览记录渲染',
        points: '前台按当前登录用户积分数据渲染',
        balance: '前台按当前登录用户钱包余额渲染'
    }

    return descMap[type] || '前台按真实用户态数据渲染'
}
</script>

<style lang="scss" scoped>
.data-stats {
    &__empty {
        padding: 24px 16px;
        border-radius: 16px;
        background: #fffaf7;
        border: 1px dashed #f0d6cb;
        font-size: 14px;
        line-height: 1.6;
        color: #8d6e63;
    }

    .overflow-x-auto {
        &::-webkit-scrollbar {
            display: none;
        }
    }
}
</style>
