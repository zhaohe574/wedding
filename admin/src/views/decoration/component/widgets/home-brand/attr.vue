<template>
    <el-form label-width="96px">
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="flex items-end mb-4">
                <div class="text-base text-[#101010] dark:text-[#ffffff] font-medium">数据条</div>
                <div class="ml-2 text-xs text-tx-secondary">
                    首屏主图下方的三格数字
                </div>
            </div>
            <el-form-item label="显示本区块">
                <el-switch v-model="contentData.enabled" :active-value="1" :inactive-value="0" />
            </el-form-item>
            <el-form-item label="卡片样式">
                <el-radio-group v-model="contentData.style">
                    <el-radio-button
                        v-for="opt in HOME_BRAND_STYLES"
                        :key="opt.value"
                        :value="opt.value"
                    >
                        {{ opt.label }}
                    </el-radio-button>
                </el-radio-group>
            </el-form-item>
            <el-form-item label="三格数据" class="!mb-0">
                <div class="flex-1 space-y-3">
                    <div
                        v-for="(_, index) in contentData.stats"
                        :key="index"
                        class="grid grid-cols-2 gap-3"
                    >
                        <el-input
                            v-model="contentData.stats[index].value"
                            maxlength="12"
                            placeholder="数值，如 1000+"
                        />
                        <el-input
                            v-model="contentData.stats[index].label"
                            maxlength="12"
                            placeholder="说明，如 场仪式"
                        />
                    </div>
                </div>
            </el-form-item>
            <div class="mt-3 text-xs text-tx-secondary">
                固定 3 格；某一格数值留空，该格就不显示数字；三格全空时整块不显示。请按真实数据填写。
            </div>
        </el-card>
    </el-form>
</template>

<script lang="ts" setup>
import { HOME_BRAND_DEFAULTS, HOME_BRAND_STYLES } from '@shared/contracts/decorate'
import type { PropType } from 'vue'

import type options from './options'

type OptionsType = ReturnType<typeof options>

const emits = defineEmits<(event: 'update:content', data: OptionsType['content']) => void>()
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

const contentData = computed({
    get: () => props.content,
    set: (newValue) => {
        emits('update:content', newValue)
    }
})

/** 旧数据可能少于 3 格或缺 style：补齐后一次性回写，避免模板 v-model 写到 undefined 上 */
const ensureContent = () => {
    const patch: Record<string, any> = {}
    if (!Array.isArray(props.content.stats) || props.content.stats.length !== 3) {
        patch.stats = HOME_BRAND_DEFAULTS.stats.map((item, index) => ({
            ...item,
            ...(Array.isArray(props.content.stats) ? props.content.stats[index] : {})
        }))
    }
    if (props.content.style !== 'glass' && props.content.style !== 'card') {
        patch.style = HOME_BRAND_DEFAULTS.style
    }
    if (Object.keys(patch).length) {
        emits('update:content', { ...props.content, ...patch })
    }
}

watchEffect(ensureContent)
</script>
