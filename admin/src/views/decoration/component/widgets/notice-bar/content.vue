<template>
    <div class="notice-bar mx-[10px] mt-[10px]">
        <!-- 横向滚动样式 -->
        <div
            v-if="content.style == 1"
            class="notice-horizontal flex items-center px-4 py-3 rounded-lg"
            :style="{ backgroundColor: content.bg_color }"
        >
            <icon name="el-icon-Bell" :size="16" :color="content.text_color" class="mr-2" />
            <div class="flex-1 overflow-hidden">
                <div class="notice-scroll whitespace-nowrap" :style="{ color: content.text_color }">
                    <span v-for="(item, index) in showList" :key="index" class="mr-8">
                        {{ item.title }}
                    </span>
                </div>
            </div>
        </div>

        <!-- 纵向滚动样式 -->
        <div
            v-if="content.style == 2"
            class="notice-vertical rounded-lg overflow-hidden"
            :style="{ backgroundColor: content.bg_color }"
        >
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="flex items-center px-4 py-3 border-b border-gray-100 last:border-b-0"
            >
                <icon name="el-icon-Bell" :size="16" :color="content.text_color" class="mr-2" />
                <span class="flex-1 text-sm" :style="{ color: content.text_color }">
                    {{ item.title }}
                </span>
            </div>
        </div>

        <!-- 静态展示样式 -->
        <div
            v-if="content.style == 3"
            class="notice-static space-y-2"
        >
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="flex items-center px-4 py-3 rounded-lg"
                :style="{ backgroundColor: content.bg_color }"
            >
                <icon name="el-icon-Bell" :size="16" :color="content.text_color" class="mr-2" />
                <span class="flex-1 text-sm" :style="{ color: content.text_color }">
                    {{ item.title }}
                </span>
                <icon name="el-icon-ArrowRight" :size="14" :color="content.text_color" />
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

const showList = computed(() => {
    const data = props.content.data?.filter((item: any) => item.is_show == '1') || []
    const limit = props.content.show_count || data.length
    // 模拟数据用于预览
    return data.slice(0, limit).map((item: any, index: number) => ({
        ...item,
        title: `公告通知 ${index + 1}：这是一条示例公告内容`
    }))
})
</script>

<style lang="scss" scoped>
.notice-bar {
    .notice-scroll {
        animation: scroll 20s linear infinite;
    }

    @keyframes scroll {
        0% {
            transform: translateX(0);
        }
        100% {
            transform: translateX(-50%);
        }
    }
}
</style>
