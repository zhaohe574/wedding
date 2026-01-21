<template>
    <div class="staff-showcase mx-[10px] mt-[10px]">
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

        <!-- 卡片样式 -->
        <div v-if="content.style == 1" class="staff-grid">
            <div
                class="grid gap-[10px]"
                :style="{ 'grid-template-columns': `repeat(${content.per_line || 2}, 1fr)` }"
            >
                <div
                    v-for="(item, index) in showList"
                    :key="index"
                    class="staff-card bg-white rounded-lg overflow-hidden shadow-sm"
                >
                    <!-- 头像 -->
                    <div class="relative">
                        <decoration-img
                            width="100%"
                            height="140px"
                            :src="item.avatar"
                            fit="cover"
                        />
                        <!-- 角色标签 -->
                        <div class="absolute top-[8px] left-[8px] px-[8px] py-[3px] bg-primary/90 rounded-full">
                            <span class="text-white text-xs">{{ item.role || '服务人员' }}</span>
                        </div>
                    </div>
                    <!-- 信息 -->
                    <div class="p-[10px]">
                        <div class="flex items-center justify-between mb-[6px]">
                            <span class="text-sm font-medium text-gray-900 truncate flex-1">{{ item.name }}</span>
                        </div>
                        <!-- 评分和订单数 -->
                        <div class="flex items-center mb-[6px]">
                            <div class="flex items-center">
                                <icon name="el-icon-StarFilled" :size="14" color="#f59e0b" />
                                <span class="text-xs text-amber-500 ml-1">{{ item.rating || '5.0' }}</span>
                            </div>
                            <span class="text-xs text-gray-400 ml-[10px]">{{ item.order_count || 0 }}单</span>
                        </div>
                        <!-- 标签 -->
                        <div v-if="item.tags && item.tags.length" class="flex flex-wrap gap-[4px]">
                            <span
                                v-for="(tag, tagIndex) in item.tags.slice(0, 3)"
                                :key="tagIndex"
                                class="px-[6px] py-[2px] bg-primary/10 rounded text-xs text-primary"
                            >
                                {{ tag }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 列表样式 -->
        <div v-if="content.style == 2" class="staff-list bg-white rounded-lg overflow-hidden">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="staff-item flex items-center p-[12px]"
                :class="{ 'border-b border-gray-100': index < showList.length - 1 }"
            >
                <!-- 头像 -->
                <div class="relative flex-shrink-0">
                    <decoration-img
                        width="60px"
                        height="60px"
                        :src="item.avatar"
                        class="rounded-full"
                        fit="cover"
                    />
                </div>
                <!-- 信息 -->
                <div class="flex-1 ml-[12px] overflow-hidden">
                    <div class="flex items-center mb-[4px]">
                        <span class="text-sm font-medium text-gray-900 truncate">{{ item.name }}</span>
                        <span class="ml-[6px] px-[6px] py-[2px] bg-primary/10 rounded text-xs text-primary">
                            {{ item.role || '服务人员' }}
                        </span>
                    </div>
                    <!-- 评分和订单数 -->
                    <div class="flex items-center mb-[4px]">
                        <div class="flex items-center">
                            <icon name="el-icon-StarFilled" :size="14" color="#f59e0b" />
                            <span class="text-xs text-amber-500 ml-1">{{ item.rating || '5.0' }}</span>
                        </div>
                        <span class="text-xs text-gray-400 ml-[10px]">已服务{{ item.order_count || 0 }}单</span>
                    </div>
                    <!-- 标签 -->
                    <div v-if="item.tags && item.tags.length" class="flex flex-wrap gap-[4px]">
                        <span class="text-xs text-gray-500">
                            {{ item.tags.slice(0, 3).join(' · ') }}
                        </span>
                    </div>
                </div>
                <!-- 箭头 -->
                <div class="flex-shrink-0 ml-[8px]">
                    <icon name="el-icon-ArrowRight" :size="16" color="#9ca3af" />
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
.staff-showcase {
    .staff-card {
        border: 1px solid #f3f4f6;
    }
}
</style>
