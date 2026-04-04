<template>
    <div>
        <el-form label-width="80px">
            <!-- 基础设置 -->
            <el-card shadow="never" class="!border-none flex mt-2">
                <div class="flex items-end mb-4">
                    <div class="text-base text-[#101010] font-medium">基础设置</div>
                </div>
                <el-form-item label="标题文字">
                    <el-input
                        v-model="contentData.title"
                        placeholder="请输入标题"
                        style="width: 300px"
                    />
                </el-form-item>
                <el-form-item label="数据来源">
                    <el-alert
                        type="info"
                        :closable="false"
                        show-icon
                    >
                        <template #default>
                            倒计时将自动读取用户设置的婚期，如用户未设置婚期则不显示此组件
                        </template>
                    </el-alert>
                </el-form-item>
            </el-card>

            <!-- 展示样式 -->
            <el-card shadow="never" class="!border-none flex mt-2">
                <div class="flex items-end mb-4">
                    <div class="text-base text-[#101010] font-medium">展示样式</div>
                </div>
                <el-radio-group v-model="contentData.style">
                    <el-radio :value="1">卡片式</el-radio>
                    <el-radio :value="2">简约式</el-radio>
                    <el-radio :value="3">浪漫式</el-radio>
                </el-radio-group>
            </el-card>

            <!-- 颜色设置 -->
            <el-card shadow="never" class="!border-none flex mt-2">
                <div class="flex items-end mb-4">
                    <div class="text-base text-[#101010] font-medium">颜色设置</div>
                </div>
                <el-form-item label="背景颜色">
                    <el-color-picker v-model="contentData.bg_color" />
                </el-form-item>
                <el-form-item label="文字颜色">
                    <el-color-picker v-model="contentData.text_color" />
                </el-form-item>
                <el-form-item label="数字颜色">
                    <el-color-picker v-model="contentData.number_color" />
                </el-form-item>
            </el-card>

            <!-- 显示设置 -->
            <el-card shadow="never" class="!border-none flex mt-2">
                <div class="flex items-end mb-4">
                    <div class="text-base text-[#101010] font-medium">显示设置</div>
                </div>
                <el-form-item label="显示天数">
                    <el-switch
                        v-model="contentData.show_days"
                        :active-value="1"
                        :inactive-value="0"
                    />
                </el-form-item>
                <el-form-item label="显示小时">
                    <el-switch
                        v-model="contentData.show_hours"
                        :active-value="1"
                        :inactive-value="0"
                    />
                </el-form-item>
                <el-form-item label="显示分钟">
                    <el-switch
                        v-model="contentData.show_minutes"
                        :active-value="1"
                        :inactive-value="0"
                    />
                </el-form-item>
                <el-form-item label="显示秒数">
                    <el-switch
                        v-model="contentData.show_seconds"
                        :active-value="1"
                        :inactive-value="0"
                    />
                </el-form-item>
            </el-card>
        </el-form>
    </div>
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
</script>
