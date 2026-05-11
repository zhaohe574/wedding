<template>
    <div class="pages-preview">
        <div class="pages-preview__stage">
            <!--    iframe预览    -->
            <iframe
                v-if="$route.query.url"
                ref="previewIframeRef"
                class="pages-preview__iframe"
                scrolling="no"
                :src="$route.query.url as string"
            ></iframe>
            <div class="pages-preview__canvas" :style="{ height: `${canvasHeight}px` }">
                <div
                    v-for="(widget, index) in pageData"
                    :key="widget.id"
                    class="absolute"
                    :class="{
                        'cursor-pointer': !widget?.disabled
                    }"
                    :style="normalizeStyles(widget.styles)"
                    @click="handleClick(widget, index)"
                >
                    <div
                        class="absolute w-full h-full z-[100] border-dashed"
                        :class="{
                            select: index == modelValue,
                            'border-[#dcdfe6] border-2': !widget?.disabled,
                            hide: canShowCom(widget.content)
                        }"
                    ></div>
                    <slot>
                        <component
                            :is="widgets[widget?.name]?.content"
                            :content="widget.content"
                            :styles="widget.styles"
                            :key="widget.id"
                            ref="commonComponentRef"
                        />
                    </slot>
                    <!--  部件操作按钮组  -->
                    <div
                        class="widget-btns py-[5px]"
                        v-if="index == modelValue"
                        :style="{
                            top: '0px',
                            left: normalizeStyles(widget.styles).width
                        }"
                    >
                        <div>
                            <el-tooltip effect="dark" content="编辑组件内容" placement="right">
                                <el-button
                                    class="py-[5px]"
                                    type="primary"
                                    :icon="Setting"
                                    @click="handleClickSetting(index)"
                                />
                            </el-tooltip>
                        </div>
                        <div>
                            <el-tooltip
                                effect="dark"
                                :content="canShowCom(widget.content) ? '显示' : '隐藏'"
                                placement="right"
                            >
                                <el-button
                                    class="py-[5px]"
                                    type="primary"
                                    :icon="canShowCom(widget.content) ? View : Hide"
                                    @click="changeShowCom(widget.content)"
                                />
                            </el-tooltip>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script lang="ts" setup>
import { Hide, Setting, View } from '@element-plus/icons-vue'
import type { CSSProperties, PropType } from 'vue'

import widgets from '../widgets'

const commonComponentRef = shallowRef<any>()

const props = defineProps({
    pageData: {
        type: Array as PropType<any[]>,
        default: () => []
    },
    modelValue: {
        type: Number,
        default: 0
    }
})

const emit = defineEmits<{
    (event: 'update:modelValue', value: number): void
}>()

const normalizeSize = (value: any) => {
    if (typeof value === 'number') {
        return `${value}px`
    }
    if (typeof value === 'string' && /^\d+$/.test(value)) {
        return `${value}px`
    }
    return value || '0px'
}

const getNumberSize = (value: any) => {
    const normalizedValue = normalizeSize(value)
    const matched = String(normalizedValue).match(/^-?\d+(\.\d+)?/)
    return matched ? Number(matched[0]) : 0
}

const normalizeStyles = (styles: Record<string, any> = {}): CSSProperties => ({
    position: 'absolute',
    left: normalizeSize(styles.left),
    top: normalizeSize(styles.top),
    width: normalizeSize(styles.width || 1200),
    height: normalizeSize(styles.height || 200),
    zIndex: styles.zIndex
})

const canvasHeight = computed(() => {
    const maxHeight = props.pageData.reduce((height, widget: any) => {
        const styles = normalizeStyles(widget?.styles || {})
        return Math.max(height, getNumberSize(styles.top) + getNumberSize(styles.height))
    }, 0)

    return Math.max(maxHeight, 900)
})

// 是否显示组件
const canShowCom = computed(() => {
    return (data: any) => {
        return data?.enabled == 0
    }
})

// 点击了组件设置
const handleClickSetting = (index: number) => {
    emit('update:modelValue', index)
    commonComponentRef.value?.[index]?.open?.()
}

// 修改组件显示/隐藏
const changeShowCom = (data: any) => {
    if (data.enabled === undefined) return
    data.enabled = data.enabled ? 0 : 1
}

const handleClick = (widget: any, index: number) => {
    if (widget.disabled) return
    emit('update:modelValue', index)
}
</script>

<style lang="scss" scoped>
.pages-preview {
    @apply w-full h-full relative;
    height: 100%;
    overflow: hidden;

    &__stage {
        height: 100%;
        overflow: auto;
        display: flex;
        justify-content: center;
        padding: 18px 24px 48px;
        box-sizing: border-box;
        background:
            linear-gradient(90deg, rgba(0, 0, 0, 0.04) 1px, transparent 1px),
            linear-gradient(rgba(0, 0, 0, 0.04) 1px, transparent 1px),
            #eef1f5;
        background-size: 24px 24px;
    }

    &__iframe {
        position: absolute;
        inset: 18px 24px 48px;
        width: calc(100% - 48px);
        height: calc(100% - 66px);
        border: 0;
        pointer-events: none;
        opacity: 0.28;
    }

    &__canvas {
        width: 1200px;
        flex: 0 0 1200px;
        position: relative;
        background: #ffffff;
        box-shadow: 0 16px 50px rgba(15, 23, 42, 0.14);
    }

    .select {
        @apply border-primary border-solid;
    }

    .hide::before {
        content: '已隐藏';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        color: white;
        font-size: 14px;
        background-color: rgba(0, 0, 0, 0.4);
    }

    .widget-btns {
        position: absolute;
        overflow: hidden;

        width: 46px;
        border-radius: 8px;
        @apply bg-primary;
        margin-left: 10px;

        :deep(.el-button) {
            width: 46px;
            border-radius: 0;
        }
    }
}
</style>
