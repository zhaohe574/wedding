<template>
    <div class="long-detail-editor">
        <div class="long-detail-editor__toolbar">
            <div class="long-detail-editor__tips">
                <div class="long-detail-editor__title">长图详情装修</div>
                <div class="long-detail-editor__desc">
                    支持图片模块、文本模块；图片会按顺序无间距展示。
                </div>
            </div>
            <div class="long-detail-editor__actions">
                <el-button plain @click="addTextBlock">添加文本</el-button>
                <el-button type="primary" plain @click="addImageBlock">添加图片</el-button>
            </div>
        </div>

        <div v-if="!blocks.length" class="long-detail-editor__empty">
            还没有内容，点击上方按钮开始装修。
        </div>

        <div v-else class="long-detail-editor__list">
            <div
                v-for="(block, index) in blocks"
                :key="block.id"
                class="long-detail-editor__item"
            >
                <div class="long-detail-editor__item-head">
                    <div class="long-detail-editor__item-meta">
                        <span class="long-detail-editor__item-index">#{{ index + 1 }}</span>
                        <span class="long-detail-editor__item-type">
                            {{ block.type === 'image' ? '图片模块' : '文本模块' }}
                        </span>
                    </div>
                    <div class="long-detail-editor__item-actions">
                        <el-button link :disabled="index === 0" @click="moveBlock(index, -1)">
                            上移
                        </el-button>
                        <el-button
                            link
                            :disabled="index === blocks.length - 1"
                            @click="moveBlock(index, 1)"
                        >
                            下移
                        </el-button>
                        <el-button link type="danger" @click="removeBlock(index)">删除</el-button>
                    </div>
                </div>

                <template v-if="block.type === 'image'">
                    <el-form-item class="!mb-0" label="图片列表" label-width="88px">
                        <div class="long-detail-editor__image-field">
                            <material-picker v-model="block.images" :limit="-1" />
                            <div class="long-detail-editor__helper">
                                建议按展示顺序选择图片，前台会首尾无间距连续展示。
                            </div>
                        </div>
                    </el-form-item>
                </template>

                <template v-else>
                    <el-form-item class="!mb-4" label="文本内容" label-width="88px">
                        <el-input
                            v-model="block.content"
                            type="textarea"
                            :rows="6"
                            maxlength="3000"
                            show-word-limit
                            placeholder="请输入要展示的文本内容"
                        />
                    </el-form-item>

                    <div class="long-detail-editor__text-style-grid">
                        <el-form-item label="字号" label-width="88px">
                            <el-segmented
                                v-model="block.style.fontSize"
                                :options="fontSizeOptions"
                            />
                        </el-form-item>

                        <el-form-item label="对齐" label-width="88px">
                            <el-segmented
                                v-model="block.style.align"
                                :options="alignOptions"
                            />
                        </el-form-item>

                        <el-form-item label="加粗" label-width="88px">
                            <el-switch v-model="block.style.bold" />
                        </el-form-item>
                    </div>

                    <el-form-item class="!mb-0" label="文字颜色" label-width="88px">
                        <div class="long-detail-editor__color-list">
                            <button
                                v-for="color in colorOptions"
                                :key="color"
                                type="button"
                                class="long-detail-editor__color"
                                :class="{
                                    'is-active': block.style.color === color
                                }"
                                :style="{ backgroundColor: color }"
                                @click="block.style.color = color"
                            ></button>
                        </div>
                    </el-form-item>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import MaterialPicker from '@/components/material/picker.vue'

type LongDetailTextStyle = {
    align: 'left' | 'center' | 'right'
    fontSize: 'sm' | 'md' | 'lg'
    color: string
    bold: boolean
}

type LongDetailBlock =
    | {
          id: string
          type: 'image'
          images: string[]
      }
    | {
          id: string
          type: 'text'
          content: string
          style: LongDetailTextStyle
      }

const props = defineProps<{
    modelValue?: string
}>()

const emit = defineEmits<{
    (event: 'update:modelValue', value: string): void
}>()

const fontSizeOptions = [
    { label: '小', value: 'sm' },
    { label: '中', value: 'md' },
    { label: '大', value: 'lg' }
]

const alignOptions = [
    { label: '左对齐', value: 'left' },
    { label: '居中', value: 'center' },
    { label: '右对齐', value: 'right' }
]

const colorOptions = ['#1E2432', '#3D3D3D', '#7F7B78', '#E85A4F', '#C98A4A', '#2F7D58']

const createId = () => `${Date.now()}_${Math.random().toString(36).slice(2, 8)}`

const createTextBlock = (): LongDetailBlock => ({
    id: createId(),
    type: 'text',
    content: '',
    style: {
        align: 'left',
        fontSize: 'md',
        color: '#1E2432',
        bold: false
    }
})

const createImageBlock = (): LongDetailBlock => ({
    id: createId(),
    type: 'image',
    images: []
})

const normalizeBlocks = (value?: string): LongDetailBlock[] => {
    if (!value) {
        return []
    }

    try {
        const parsed = JSON.parse(value)
        if (!Array.isArray(parsed)) {
            return []
        }

        return parsed.reduce<LongDetailBlock[]>((acc, item) => {
            if (!item || typeof item !== 'object') {
                return acc
            }

            if (item.type === 'image') {
                acc.push({
                    id: String(item.id || createId()),
                    type: 'image',
                    images: Array.isArray(item.images)
                        ? item.images
                              .map((image: unknown) => String(image || '').trim())
                              .filter(Boolean)
                        : []
                })
                return acc
            }

            if (item.type === 'text') {
                acc.push({
                    id: String(item.id || createId()),
                    type: 'text',
                    content: String(item.content || ''),
                    style: {
                        align: ['left', 'center', 'right'].includes(item.style?.align)
                            ? item.style.align
                            : 'left',
                        fontSize: ['sm', 'md', 'lg'].includes(item.style?.fontSize)
                            ? item.style.fontSize
                            : 'md',
                        color: String(item.style?.color || '#1E2432'),
                        bold: Boolean(item.style?.bold)
                    }
                })
            }

            return acc
        }, [])
    } catch (_error) {
        return []
    }
}

const stringifyBlocks = (value: LongDetailBlock[]) => {
    const normalized = value.reduce<LongDetailBlock[]>((acc, block) => {
        if (block.type === 'image') {
            const images = block.images.map((item) => String(item || '').trim()).filter(Boolean)
            if (images.length) {
                acc.push({
                    id: block.id,
                    type: 'image',
                    images
                })
            }
            return acc
        }

        const content = String(block.content || '')
        if (content.trim()) {
            acc.push({
                id: block.id,
                type: 'text',
                content,
                style: {
                    align: block.style.align,
                    fontSize: block.style.fontSize,
                    color: block.style.color || '#1E2432',
                    bold: Boolean(block.style.bold)
                }
            })
        }
        return acc
    }, [])

    return normalized.length ? JSON.stringify(normalized) : ''
}

const blocks = ref<LongDetailBlock[]>(normalizeBlocks(props.modelValue))

watch(
    () => props.modelValue,
    (value) => {
        const next = normalizeBlocks(value)
        if (JSON.stringify(next) !== JSON.stringify(blocks.value)) {
            blocks.value = next
        }
    }
)

watch(
    blocks,
    (value) => {
        emit('update:modelValue', stringifyBlocks(value))
    },
    { deep: true }
)

const addTextBlock = () => {
    blocks.value.push(createTextBlock())
}

const addImageBlock = () => {
    blocks.value.push(createImageBlock())
}

const removeBlock = (index: number) => {
    blocks.value.splice(index, 1)
}

const moveBlock = (index: number, direction: -1 | 1) => {
    const targetIndex = index + direction
    if (targetIndex < 0 || targetIndex >= blocks.value.length) {
        return
    }

    const list = [...blocks.value]
    ;[list[index], list[targetIndex]] = [list[targetIndex], list[index]]
    blocks.value = list
}
</script>

<style scoped lang="scss">
.long-detail-editor {
    display: flex;
    flex-direction: column;
    gap: 16px;
    width: 100%;
}

.long-detail-editor__toolbar {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    padding: 16px 18px;
    border-radius: 16px;
    background: #f8fafc;
    border: 1px solid #e8ecf3;
}

.long-detail-editor__title {
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
}

.long-detail-editor__desc,
.long-detail-editor__helper,
.long-detail-editor__empty {
    font-size: 12px;
    line-height: 1.6;
    color: #6b7280;
}

.long-detail-editor__actions {
    display: flex;
    gap: 10px;
    flex-shrink: 0;
}

.long-detail-editor__empty {
    padding: 28px 20px;
    border-radius: 16px;
    border: 1px dashed #d6dbe6;
    text-align: center;
    background: #ffffff;
}

.long-detail-editor__list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.long-detail-editor__item {
    padding: 18px;
    border-radius: 18px;
    border: 1px solid #e8ecf3;
    background: #ffffff;
}

.long-detail-editor__item-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.long-detail-editor__item-meta,
.long-detail-editor__item-actions,
.long-detail-editor__color-list,
.long-detail-editor__text-style-grid {
    display: flex;
    align-items: center;
}

.long-detail-editor__item-meta {
    gap: 10px;
}

.long-detail-editor__item-actions {
    gap: 8px;
}

.long-detail-editor__item-index {
    font-size: 12px;
    font-weight: 700;
    color: var(--el-color-primary);
}

.long-detail-editor__item-type {
    font-size: 13px;
    font-weight: 600;
    color: #374151;
}

.long-detail-editor__text-style-grid {
    gap: 16px;
    flex-wrap: wrap;
}

.long-detail-editor__color-list {
    gap: 10px;
    flex-wrap: wrap;
}

.long-detail-editor__color {
    width: 28px;
    height: 28px;
    border-radius: 999px;
    border: 2px solid transparent;
    cursor: pointer;
}

.long-detail-editor__color.is-active {
    box-shadow: 0 0 0 2px #ffffff, 0 0 0 4px var(--el-color-primary);
}

.long-detail-editor__image-field {
    width: 100%;
}

@media (max-width: 960px) {
    .long-detail-editor__toolbar,
    .long-detail-editor__item-head {
        flex-direction: column;
        align-items: stretch;
    }

    .long-detail-editor__actions,
    .long-detail-editor__item-actions {
        justify-content: flex-start;
        flex-wrap: wrap;
    }
}
</style>
