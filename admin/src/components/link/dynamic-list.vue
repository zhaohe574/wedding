<template>
    <div class="dynamic-list">
        <el-form ref="formRef" :model="queryParams" :inline="true">
            <el-form-item class="w-[180px]" label="动态分类">
                <el-select v-model="queryParams.dynamic_type" placeholder="全部分类" clearable>
                    <el-option label="全部" value="" />
                    <el-option label="图文" :value="1" />
                    <el-option label="视频" :value="2" />
                    <el-option label="活动" :value="4" />
                </el-select>
            </el-form-item>
            <el-form-item label="关键词">
                <div class="flex items-center gap-2 whitespace-nowrap">
                    <el-input
                        v-model="queryParams.keyword"
                        class="w-[270px]"
                        placeholder="搜索标题、内容、标签"
                        clearable
                        @keyup.enter="resetPage"
                    />
                    <el-button type="primary" class="flex-none" :icon="Search" @click="resetPage" />
                </div>
            </el-form-item>
        </el-form>

        <el-table
            size="large"
            v-loading="pager.loading"
            :data="pager.lists"
            height="432px"
            @row-click="handleSelectItem"
        >
            <el-table-column label="选择" min-width="50">
                <template #default="{ row }">
                    <div class="flex row-center">
                        <el-checkbox
                            :model-value="getSelectItem(row.id)"
                            size="large"
                            @change="handleSelectItem(row)"
                        ></el-checkbox>
                    </div>
                </template>
            </el-table-column>
            <el-table-column label="动态内容" min-width="260">
                <template #default="{ row }">
                    <div class="flex items-center">
                        <el-image
                            v-if="getDynamicCover(row)"
                            fit="cover"
                            :src="getDynamicCover(row)"
                            class="flex-none w-[58px] h-[58px] rounded"
                        />
                        <div
                            v-else
                            class="flex-none w-[58px] h-[58px] rounded bg-gray-100 text-gray-400 flex items-center justify-center text-xs"
                        >
                            无图
                        </div>
                        <div class="ml-4 overflow-hidden">
                            <el-tooltip effect="dark" :content="getDynamicName(row)" placement="top">
                                <div class="text-base line-clamp-2">
                                    {{ getDynamicName(row) }}
                                </div>
                            </el-tooltip>
                            <div class="mt-1 text-xs text-gray-400">
                                {{ row.publisher?.nickname || row.user_type_desc || '未知发布者' }}
                            </div>
                        </div>
                    </div>
                </template>
            </el-table-column>
            <el-table-column label="类型" min-width="90">
                <template #default="{ row }">
                    <el-tag size="small">{{ row.type_desc || '-' }}</el-tag>
                </template>
            </el-table-column>
            <el-table-column label="状态" min-width="90">
                <template #default="{ row }">
                    <el-tag type="success" size="small">{{ row.status_desc || '已发布' }}</el-tag>
                </template>
            </el-table-column>
            <el-table-column label="发布时间" prop="create_time" min-width="150" />
        </el-table>

        <div class="flex justify-end mt-4">
            <pagination v-model="pager" @change="getLists" />
        </div>
    </div>
</template>

<script lang="ts" setup>
import { Search } from '@element-plus/icons-vue'
import type { PropType } from 'vue'

import { dynamicLists } from '@/api/dynamic'
import { usePaging } from '@/hooks/usePaging'

import { LinkTypeEnum } from '.'

const DYNAMIC_DETAIL_PATH = '/pages/dynamic_detail/dynamic_detail'

const props = defineProps({
    modelValue: {
        type: Object as PropType<any>,
        default: () => ({})
    }
})
const emit = defineEmits<{
    (event: 'update:modelValue', value: any): void
}>()

const selectData = ref<any>({
    path: DYNAMIC_DETAIL_PATH,
    name: '',
    query: {},
    type: LinkTypeEnum.DYNAMIC_LIST
})

const queryParams = reactive<any>({
    dynamic_type: '',
    keyword: '',
    status: 1
})

const { pager, getLists, resetPage } = usePaging({
    fetchFun: dynamicLists,
    params: queryParams
})

const stripContent = (value: any) => {
    return String(value ?? '')
        .replace(/<[^>]+>/g, '')
        .replace(/\s+/g, ' ')
        .trim()
}

const getDynamicName = (row: any) => {
    const title = stripContent(row.title)
    const content = stripContent(row.content)
    const text = title || content

    if (text) {
        return text.length > 30 ? `${text.slice(0, 30)}...` : text
    }

    return `动态#${row.id}`
}

const getDynamicCover = (row: any) => {
    if (Array.isArray(row.images) && row.images.length > 0) {
        return row.images[0]
    }

    return row.video_cover || ''
}

const getSelectItem = (id: number) => {
    return id == Number(selectData.value.id)
}

const handleSelectItem = (event: any) => {
    selectData.value = {
        id: event.id,
        name: getDynamicName(event),
        path: DYNAMIC_DETAIL_PATH,
        query: {
            id: event.id
        },
        type: LinkTypeEnum.DYNAMIC_LIST
    }

    emit('update:modelValue', selectData.value)
}

watch(
    () => props.modelValue,
    (value) => {
        if (value.type != LinkTypeEnum.DYNAMIC_LIST) {
            return (selectData.value = {
                id: '',
                name: '',
                path: DYNAMIC_DETAIL_PATH,
                query: {},
                type: LinkTypeEnum.DYNAMIC_LIST
            })
        }
        selectData.value = value
    },
    {
        immediate: true
    }
)

getLists()
</script>

<style lang="scss" scoped>
:deep(.el-input-group__append) {
    .el-button {
        margin: 0 0;
    }
}
</style>
