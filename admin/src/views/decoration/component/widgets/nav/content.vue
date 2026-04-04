<template>
    <div class="nav bg-white pt-[15px] pb-[8px]">
        <!-- 固定显示模式 -->
        <template v-if="content.style !== 2">
            <div
                class="grid grid-rows-auto gap-y-3 w-full"
                :style="{ 'grid-template-columns': `repeat(${content.per_line}, 1fr)` }"
            >
                <div
                    v-for="(item, index) in showList"
                    :key="index"
                    class="flex flex-col items-center"
                >
                    <decoration-img width="41px" height="41px" :src="item.image" alt="" />
                    <div class="mt-[7px] text-xs text-center truncate max-w-[60px]">
                        {{ item.name }}
                    </div>
                </div>
            </div>
        </template>
        <!-- 分页滑动模式 -->
        <template v-else>
            <div class="swiper-container">
                <div
                    class="swiper-slide"
                    :style="{ transform: `translateX(-${currentPage * 100}%)` }"
                >
                    <div
                        v-for="(page, pIndex) in pagedList"
                        :key="pIndex"
                        class="swiper-page"
                    >
                        <div
                            class="grid grid-rows-auto gap-y-3 w-full"
                            :style="{
                                'grid-template-columns': `repeat(${content.per_line}, 1fr)`
                            }"
                        >
                            <div
                                v-for="(item, index) in page"
                                :key="index"
                                class="flex flex-col items-center"
                            >
                                <decoration-img
                                    width="41px"
                                    height="41px"
                                    :src="item.image"
                                    alt=""
                                />
                                <div
                                    class="mt-[7px] text-xs text-center truncate max-w-[60px]"
                                >
                                    {{ item.name }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- 分页指示器 -->
                <div v-if="pagedList.length > 1" class="flex justify-center gap-1 mt-2 pb-1">
                    <span
                        v-for="(_, i) in pagedList"
                        :key="i"
                        class="inline-block w-[6px] h-[6px] rounded-full cursor-pointer"
                        :class="i === currentPage ? 'bg-primary' : 'bg-gray-300'"
                        @click="currentPage = i"
                    />
                </div>
            </div>
        </template>
    </div>
</template>
<script lang="ts" setup>
import { computed, ref } from 'vue'
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

// 过滤可见菜单项
const visibleData = computed(() => {
    return props.content.data?.filter((item: any) => item.is_show !== '0') || []
})

// 每页显示数量
const pageSize = computed(() => {
    return (props.content.per_line || 5) * (props.content.show_line || 2)
})

// 固定模式：截取显示
const showList = computed(() => {
    return visibleData.value.slice(0, pageSize.value)
})

// 分页模式：按页分组
const pagedList = computed(() => {
    const size = pageSize.value
    const result = []
    for (let i = 0; i < visibleData.value.length; i += size) {
        result.push(visibleData.value.slice(i, i + size))
    }
    return result.length ? result : [[]]
})

const currentPage = ref(0)
</script>

<style lang="scss" scoped>
.swiper-container {
    overflow: hidden;
    position: relative;
}

.swiper-slide {
    display: flex;
    transition: transform 0.3s ease;
}

.swiper-page {
    min-width: 100%;
    flex-shrink: 0;
    padding: 0 4px;
}
</style>
