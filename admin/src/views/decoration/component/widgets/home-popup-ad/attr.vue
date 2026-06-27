<template>
    <el-form label-width="92px">
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="flex items-end mb-4">
                <div class="text-base text-[#101010] dark:text-[#ffffff] font-medium">基础设置</div>
            </div>
            <el-form-item label="启用状态">
                <el-switch v-model="contentData.enabled" :active-value="1" :inactive-value="0" />
            </el-form-item>
            <el-form-item label="弹窗类型">
                <el-radio-group v-model="contentData.type">
                    <el-radio value="image">图片</el-radio>
                    <el-radio value="text">文字</el-radio>
                    <el-radio value="image_text">图文</el-radio>
                </el-radio-group>
            </el-form-item>
            <el-form-item label="关闭按钮">
                <el-switch v-model="contentData.show_close" :active-value="1" :inactive-value="0" />
            </el-form-item>
        </el-card>

        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="flex items-end mb-4">
                <div class="text-base text-[#101010] dark:text-[#ffffff] font-medium">展示规则</div>
                <div class="ml-2 text-xs text-tx-secondary">控制首页进入后的弹出节奏</div>
            </div>
            <el-form-item label="展示模式">
                <el-select v-model="contentData.frequency" class="!w-full">
                    <el-option label="每天一次" value="daily" />
                    <el-option label="每会话一次" value="session" />
                    <el-option label="每次进入首页" value="every_home_entry" />
                    <el-option label="永久一次" value="once" />
                    <el-option label="每隔 N 天" value="interval_days" />
                    <el-option label="自定义次数" value="custom_limit" />
                </el-select>
            </el-form-item>
            <el-form-item v-if="contentData.frequency === 'interval_days'" label="间隔天数">
                <el-input-number
                    v-model="contentData.interval_days"
                    :min="1"
                    :max="365"
                    :step="1"
                    controls-position="right"
                    class="!w-full"
                />
            </el-form-item>
            <el-form-item label="展示时机">
                <el-radio-group v-model="contentData.show_timing">
                    <el-radio value="page_ready">页面加载后</el-radio>
                    <el-radio value="delay">延迟展示</el-radio>
                </el-radio-group>
            </el-form-item>
            <el-form-item v-if="contentData.show_timing === 'delay'" label="延迟秒数">
                <el-input-number
                    v-model="contentData.delay_seconds"
                    :min="1"
                    :max="30"
                    :step="1"
                    controls-position="right"
                    class="!w-full"
                />
            </el-form-item>
            <el-form-item label="总次数上限">
                <el-input-number
                    v-model="contentData.max_total_count"
                    :min="0"
                    :max="999"
                    :step="1"
                    controls-position="right"
                    class="!w-full"
                />
            </el-form-item>
            <el-form-item label="每日上限">
                <el-input-number
                    v-model="contentData.max_daily_count"
                    :min="0"
                    :max="99"
                    :step="1"
                    controls-position="right"
                    class="!w-full"
                />
            </el-form-item>
            <el-form-item label="生效时间">
                <el-date-picker
                    v-model="activeTimeRange"
                    type="datetimerange"
                    start-placeholder="开始时间"
                    end-placeholder="结束时间"
                    value-format="YYYY-MM-DD HH:mm:ss"
                    class="!w-full"
                />
            </el-form-item>
            <el-form-item label="关闭计数">
                <el-switch
                    v-model="contentData.close_counts_as_shown"
                    :active-value="1"
                    :inactive-value="0"
                    active-text="计入"
                    inactive-text="不计入"
                />
            </el-form-item>
            <div class="p-[12px] rounded-[8px] bg-[#f3f8ff] text-[#136bdf] text-xs leading-5">
                总次数或每日上限填 0 表示不限；“关闭计数”关闭后，用户点关闭不会消耗展示次数。
            </div>
        </el-card>

        <el-card v-if="contentData.type !== 'text'" shadow="never" class="!border-none flex mt-2">
            <div class="flex items-end mb-4">
                <div class="text-base text-[#101010] dark:text-[#ffffff] font-medium">弹窗图片</div>
                <div class="ml-2 text-xs text-tx-secondary">建议宽 620px，高 760px 以内</div>
            </div>
            <material-picker
                width="220px"
                height="220px"
                v-model="contentData.image"
                upload-class="bg-body"
                exclude-domain
            >
                <template #upload>
                    <div class="popup-ad-upload-btn">弹窗图</div>
                </template>
            </material-picker>
            <el-form-item label="背景颜色" class="mt-4">
                <el-color-picker v-model="contentData.background_color" />
                <span class="ml-3 text-xs text-tx-secondary">
                    透明 PNG 会显示在该颜色上，建议浅色图片使用暖白或自定义浅色。
                </span>
            </el-form-item>
        </el-card>

        <el-card v-if="contentData.type !== 'image'" shadow="never" class="!border-none flex mt-2">
            <div class="flex items-end mb-4">
                <div class="text-base text-[#101010] dark:text-[#ffffff] font-medium">文字内容</div>
            </div>
            <el-form-item label="标题">
                <el-input v-model="contentData.title" maxlength="24" show-word-limit />
            </el-form-item>
            <el-form-item label="正文">
                <el-input
                    v-model="contentData.content"
                    type="textarea"
                    :rows="4"
                    maxlength="160"
                    show-word-limit
                />
            </el-form-item>
        </el-card>

        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="flex items-end mb-4">
                <div class="text-base text-[#101010] dark:text-[#ffffff] font-medium">按钮跳转</div>
            </div>
            <el-form-item label="按钮文案">
                <el-input v-model="contentData.button_text" maxlength="12" show-word-limit />
            </el-form-item>
            <el-form-item label="跳转链接">
                <link-picker v-if="type == 'mobile'" v-model="contentData.link" />
                <el-input v-if="type == 'pc'" v-model="contentData.link.path" placeholder="请输入链接" />
            </el-form-item>
            <div class="p-[12px] rounded-[8px] bg-[#f3f8ff] text-[#136bdf] text-xs leading-5">
                未配置跳转链接时，用户点击按钮只关闭弹窗。
            </div>
        </el-card>
    </el-form>
</template>

<script lang="ts" setup>
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
        type: Object as PropType<Record<string, never>>,
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

type TimeRange = [] | [string, string]

const activeTimeRange = computed<TimeRange>({
    get: (): TimeRange => {
        if (!contentData.value.start_time && !contentData.value.end_time) {
            return []
        }
        return [contentData.value.start_time || '', contentData.value.end_time || '']
    },
    set: (value: TimeRange) => {
        contentData.value.start_time = value?.[0] || ''
        contentData.value.end_time = value?.[1] || ''
    }
})
</script>

<style lang="scss" scoped>
.popup-ad-upload-btn {
    @apply text-tx-secondary box-border rounded border-br border-dashed border flex flex-col justify-center items-center;
    width: 220px;
    height: 220px;
}
</style>
