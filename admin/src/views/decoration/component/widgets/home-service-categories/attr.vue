<template>
    <el-form label-width="80px">
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="flex items-end">
                <div class="text-base text-[#101010] font-medium">分类设置</div>
                <div class="text-xs text-tx-secondary ml-2">建议图片尺寸：宽图690px*220px，小图335px*170px</div>
            </div>
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
                                width="396px"
                                height="148px"
                                v-model="item.image"
                                upload-class="bg-body"
                                exclude-domain
                            />
                            <el-form-item class="mt-[18px]" label="标题">
                                <el-input v-model="item.title" maxlength="16" show-word-limit />
                            </el-form-item>
                            <el-form-item class="mt-[18px]" label="副标题">
                                <el-input v-model="item.subtitle" maxlength="24" show-word-limit />
                            </el-form-item>
                            <el-form-item class="mt-[18px]" label="版式">
                                <el-select v-model="item.size" class="!w-full">
                                    <el-option label="大图" value="large" />
                                    <el-option label="小图" value="small" />
                                    <el-option label="通栏" value="wide" />
                                </el-select>
                            </el-form-item>
                            <el-form-item class="mt-[18px]" label="文字位置">
                                <el-radio-group v-model="item.text_position">
                                    <el-radio-button label="top">上</el-radio-button>
                                    <el-radio-button label="middle">中</el-radio-button>
                                    <el-radio-button label="bottom">下</el-radio-button>
                                </el-radio-group>
                            </el-form-item>
                            <el-form-item class="mt-[18px]" label="文字对齐">
                                <el-radio-group v-model="item.text_align">
                                    <el-radio-button label="left">左</el-radio-button>
                                    <el-radio-button label="center">中</el-radio-button>
                                    <el-radio-button label="right">右</el-radio-button>
                                </el-radio-group>
                            </el-form-item>
                            <el-form-item class="mt-[18px]" label="跳转链接">
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
                                                (value) =>
                                                    handleQueryKeyInput(index, paramIndex, value)
                                            "
                                        />
                                        <el-input
                                            class="flex-1"
                                            :model-value="param.value"
                                            placeholder="参数值"
                                            @input="
                                                (value) =>
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
                            <el-form-item label="是否显示" class="mt-[18px] !mb-0">
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
                <el-button class="w-full" type="primary" @click="handleAdd">添加分类</el-button>
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
        item.link = {
            path: '/pages/schedule_query/schedule_query',
            type: LinkTypeEnum.SHOP_PAGES
        }
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
    content.data.push({
        is_show: '1',
        title: '新服务分类',
        subtitle: 'SERVICE',
        image: '',
        size: 'small',
        text_position: 'bottom',
        text_align: 'left',
        link: {
            path: '/pages/schedule_query/schedule_query',
            type: 'shop'
        }
    })
    emits('update:content', content)
}

const handleDelete = (index: number) => {
    if (props.content.data?.length <= 1) {
        return feedback.msgError('最少保留一个分类')
    }
    const content = cloneDeep(props.content)
    content.data.splice(index, 1)
    emits('update:content', content)
}
</script>
