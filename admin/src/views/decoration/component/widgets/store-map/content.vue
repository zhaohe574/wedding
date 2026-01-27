<template>
    <div class="store-map mx-[10px] mt-[10px]">
        <!-- 标题 -->
        <div v-if="content.title" class="flex items-center mb-[12px]">
            <div class="w-[4px] h-[17px] bg-primary rounded-full mr-[8px]"></div>
            <span class="text-base font-medium text-gray-900">{{ content.title }}</span>
        </div>

        <!-- 地图+列表样式 -->
        <div v-if="content.style == 1" class="map-with-list">
            <!-- 地图预览 -->
            <div class="map-preview w-full h-[200px] bg-gray-100 rounded-lg mb-3 flex items-center justify-center">
                <div class="text-center text-gray-500">
                    <icon name="el-icon-Location" :size="40" />
                    <div class="text-sm mt-2">地图预览</div>
                </div>
            </div>
            <!-- 门店列表 -->
            <div class="store-list space-y-2">
                <div
                    v-for="(item, index) in showList"
                    :key="index"
                    class="store-card p-3 bg-white rounded-lg border border-gray-200"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-900 mb-1">{{ item.name }}</div>
                            <div class="text-xs text-gray-500 mb-1">
                                <icon name="el-icon-Location" :size="12" class="mr-1" />
                                {{ item.address }}
                            </div>
                            <div class="text-xs text-gray-500 mb-1">
                                <icon name="el-icon-Phone" :size="12" class="mr-1" />
                                {{ item.phone }}
                            </div>
                            <div class="text-xs text-gray-500">
                                <icon name="el-icon-Clock" :size="12" class="mr-1" />
                                {{ item.business_hours }}
                            </div>
                        </div>
                        <div class="flex flex-col gap-1 ml-3">
                            <el-button size="small" type="primary" plain>导航</el-button>
                            <el-button size="small" plain>电话</el-button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 纯地图样式 -->
        <div v-if="content.style == 2" class="map-only">
            <div class="map-preview w-full h-[300px] bg-gray-100 rounded-lg flex items-center justify-center">
                <div class="text-center text-gray-500">
                    <icon name="el-icon-Location" :size="50" />
                    <div class="text-sm mt-2">地图预览</div>
                    <div class="text-xs mt-1">显示 {{ showList.length }} 个门店</div>
                </div>
            </div>
        </div>

        <!-- 纯列表样式 -->
        <div v-if="content.style == 3" class="list-only space-y-3">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="store-card p-4 bg-white rounded-lg border border-gray-200"
            >
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center mr-3">
                        <icon name="el-icon-Location" :size="24" color="var(--el-color-primary)" />
                    </div>
                    <div class="flex-1">
                        <div class="text-base font-medium text-gray-900 mb-2">{{ item.name }}</div>
                        <div class="text-sm text-gray-600 mb-1">
                            <icon name="el-icon-Location" :size="14" class="mr-1" />
                            {{ item.address }}
                        </div>
                        <div class="flex items-center gap-4 text-sm text-gray-600">
                            <span>
                                <icon name="el-icon-Phone" :size="14" class="mr-1" />
                                {{ item.phone }}
                            </span>
                            <span>
                                <icon name="el-icon-Clock" :size="14" class="mr-1" />
                                {{ item.business_hours }}
                            </span>
                        </div>
                        <div class="flex gap-2 mt-3">
                            <el-button size="small" type="primary">导航</el-button>
                            <el-button size="small">拨打电话</el-button>
                        </div>
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

const showList = computed(() => {
    const data = props.content.data?.filter((item: any) => item.is_show == '1') || []
    return data
})
</script>

<style lang="scss" scoped>
.store-map {
    .store-card {
        transition: all 0.2s ease;

        &:hover {
            border-color: var(--el-color-primary-light-5);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
    }
}
</style>
