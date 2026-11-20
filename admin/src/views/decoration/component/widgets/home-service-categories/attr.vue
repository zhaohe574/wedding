<template>
    <el-form label-width="96px">
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="flex items-end mb-4">
                <div class="text-base text-[#101010] dark:text-[#ffffff] font-medium">
                    四宫格入口
                </div>
                <div class="ml-2 text-xs text-tx-secondary">
                    首屏下方那排图标入口；建议 4 个，可拖动排序
                </div>
            </div>
            <el-form-item label="显示本区块">
                <el-switch v-model="contentData.enabled" :active-value="1" :inactive-value="0" />
            </el-form-item>
            <draggable
                v-model="contentData.data"
                animation="300"
                handle=".drag-move"
                item-key="index"
            >
                <template #item="{ element: item, index }">
                    <del-wrap :key="index" @close="handleDelete(index)" class="w-full">
                        <div class="bg-fill-light w-full p-4 mt-4">
                            <material-picker
                                size="100px"
                                v-model="item.image"
                                upload-class="bg-body"
                                exclude-domain
                            >
                                <template #upload>
                                    <div class="w-[100px] h-[100px] cat-upload-btn">图标</div>
                                </template>
                            </material-picker>
                            <div class="mt-2 text-xs text-tx-secondary">
                                正方形，建议 96×96 透明 PNG；不上传时用内置图标。
                            </div>
                            <el-form-item class="mt-[18px]" label="名称">
                                <el-input
                                    v-model="item.title"
                                    maxlength="16"
                                    show-word-limit
                                    placeholder="如 查档期"
                                />
                            </el-form-item>
                            <el-form-item class="mt-[18px]" label="点击跳转">
                                <link-picker v-if="type == 'mobile'" v-model="item.link" />
                                <el-input
                                    v-if="type == 'pc'"
                                    v-model="item.link.path"
                                    placeholder="请输入链接"
                                />
                            </el-form-item>
                            <el-form-item
                                v-if="canEditLinkParams(item)"
                                class="mt-[18px]"
                                label="跳转参数"
                            >
                                <div class="flex-1">
                                    <div
                                        v-for="(param, paramIndex) in getQueryParams(item)"
                                        :key="`${index}-${paramIndex}`"
                                        class="flex items-center gap-2 mb-2"
                                    >
                                        <el-input
                                            class="!w-[136px]"
                                            :model-value="param.key"
                                            placeholder="参数名"
                                            @input="
                                                (value: string) =>
                                                    handleQueryKeyInput(index, paramIndex, value)
                                            "
                                        />
                                        <el-input
                                            class="flex-1"
                                            :model-value="param.value"
                                            placeholder="参数值"
                                            @input="
                                                (value: string) =>
                                                    handleQueryValueInput(index, paramIndex, value)
                                            "
                                        />
                                        <el-button
                                            type="danger"
                                            link
                                            @click="handleDeleteQueryParam(index, paramIndex)"
                                        >
                                            删除
                                        </el-button>
                                    </div>
                                    <el-button type="primary" link @click="handleAddQueryParam(index)">
                                        添加参数
                                    </el-button>
                                    <div class="form-tips !mt-1">
                                        参数会追加到跳转链接后，如 keyword=西式主持
                                    </div>
                                </div>
                            </el-form-item>
                            <el-form-item label="显示这个入口" class="mt-[18px] !mb-0">
                                <div class="flex-1 flex items-center">
                                    <el-switch
                                        v-model="item.is_show"
                                        active-value="1"
                                        inactive-value="0"
                                    />
                                    <div class="drag-move cursor-move ml-auto">
                                        <icon name="el-icon-Rank" size="18" />
                                    </div>
                                </div>
                            </el-form-item>
                        </div>
                    </del-wrap>
                </template>
            </draggable>
            <div class="mt-4">
                <el-button class="w-full" type="primary" @click="handleAdd">添加入口</el-button>
            </div>
            <div class="mt-2 text-xs text-tx-secondary">
                名称或跳转链接缺一个，这个入口就不会出现在真机和预览里；多于 4 个会被挤窄。
            </div>
        </el-card>
    </el-form>
</template>

<script lang="ts" setup>
import { cloneDeep } from 'lodash-es'
import type { PropType } from 'vue'
import Draggable from 'vuedraggable'

import { LinkTypeEnum } from '@/components/link'
import feedback from '@/utils/feedback'

import type options from './options'

type OptionsType = ReturnType<typeof options>
type ServiceCategoryItem = OptionsType['content']['data'][number]
type QueryValue = string | number | boolean | null | undefined
type QueryParams = Record<string, QueryValue>

const emits = defineEmits<(event: 'update:content', data: OptionsType['content']) => void>()
const props = defineProps({
    content: {
        type: Object as PropType<OptionsType['content']>,
        default: () => ({})
    },
    styles: {
        type: Object as PropType<OptionsType['styles']>,
        default: () => ({})
    },
    type: {
        type: String as PropType<'mobile' | 'pc'>,
        default: 'mobile'
    }
})

const contentData = computed({
    get: () => props.content,
    set: (newValue) => {
        emits('update:content', newValue)
    }
})

const normalizeQuery = (value: unknown): QueryParams => {
    if (!value || Array.isArray(value) || typeof value !== 'object') {
        return {}
    }

    return value as QueryParams
}

const getQueryParams = (item: ServiceCategoryItem) => {
    return Object.entries(normalizeQuery(item.link?.query)).map(([key, value]) => ({
        key,
        value: value === null || value === undefined ? '' : String(value)
    }))
}

const canEditLinkParams = (item: ServiceCategoryItem) => {
    return props.type === 'mobile' && item.link?.type === LinkTypeEnum.SHOP_PAGES
}

const buildUniqueQueryKey = (query: QueryParams) => {
    const baseKey = 'param'
    if (!Object.prototype.hasOwnProperty.call(query, baseKey)) {
        return baseKey
    }

    let index = 1
    while (Object.prototype.hasOwnProperty.call(query, `${baseKey}${index}`)) {
        index += 1
    }

    return `${baseKey}${index}`
}

const setItemQuery = (item: ServiceCategoryItem, query: QueryParams) => {
    if (!item.link || typeof item.link !== 'object') {
        item.link = { path: '' }
    }

    const normalizedQuery = Object.fromEntries(
        Object.entries(query)
            .filter(([key]) => key.trim() !== '')
            .map(([key, value]) => [
                key.trim(),
                value === null || value === undefined ? '' : String(value)
            ])
    )

    if (Object.keys(normalizedQuery).length) {
        item.link.query = normalizedQuery
        return
    }

    delete item.link.query
}

const updateQueryByItemIndex = (index: number, query: QueryParams) => {
    const item = props.content.data?.[index]
    if (!item) {
        return
    }

    setItemQuery(item, query)
    emits('update:content', props.content)
}

const handleAddQueryParam = (index: number) => {
    const item = props.content.data?.[index]
    if (!item) {
        return
    }

    const query = normalizeQuery(item.link?.query)
    updateQueryByItemIndex(index, {
        ...query,
        [buildUniqueQueryKey(query)]: ''
    })
}

const handleDeleteQueryParam = (index: number, paramIndex: number) => {
    const item = props.content.data?.[index]
    if (!item) {
        return
    }

    const entries = Object.entries(normalizeQuery(item.link?.query))
    entries.splice(paramIndex, 1)
    updateQueryByItemIndex(index, Object.fromEntries(entries))
}

const handleQueryKeyInput = (index: number, paramIndex: number, value: string) => {
    const newKey = String(value || '').trim()
    if (!newKey) {
        feedback.msgError('参数名不能为空')
        return
    }

    const item = props.content.data?.[index]
    if (!item) {
        return
    }

    const entries = Object.entries(normalizeQuery(item.link?.query))
    const currentEntry = entries[paramIndex]
    if (!currentEntry) {
        return
    }

    const [oldKey, oldValue] = currentEntry
    if (newKey === oldKey) {
        return
    }

    const duplicate = entries.some(([key], index) => index !== paramIndex && key === newKey)
    if (duplicate) {
        feedback.msgError('参数名不能重复')
        return
    }

    entries.splice(paramIndex, 1, [newKey, oldValue])
    updateQueryByItemIndex(index, Object.fromEntries(entries))
}

const handleQueryValueInput = (index: number, paramIndex: number, value: string) => {
    const item = props.content.data?.[index]
    if (!item) {
        return
    }

    const entries = Object.entries(normalizeQuery(item.link?.query))
    const currentEntry = entries[paramIndex]
    if (!currentEntry) {
        return
    }

    entries.splice(paramIndex, 1, [currentEntry[0], String(value ?? '')])
    updateQueryByItemIndex(index, Object.fromEntries(entries))
}

const handleAdd = () => {
    const content = cloneDeep(props.content)
    if (!Array.isArray(content.data)) {
        content.data = []
    }
    content.data.push({
        is_show: '1',
        title: '新入口',
        image: '',
        link: {
            path: '',
            type: 'shop'
        }
    })
    emits('update:content', content)
}

const handleDelete = (index: number) => {
    const content = cloneDeep(props.content)
    if (!Array.isArray(content.data)) {
        content.data = []
    }
    content.data.splice(index, 1)
    emits('update:content', content)
}
</script>

<style lang="scss" scoped>
.cat-upload-btn {
    @apply text-tx-secondary box-border rounded border-br border-dashed border flex flex-col justify-center items-center;
}
</style>
