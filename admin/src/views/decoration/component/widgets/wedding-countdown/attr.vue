<template>
    <el-form label-width="96px">
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="flex items-end mb-4">
                <div class="text-base text-[#101010] dark:text-[#ffffff] font-medium">婚期卡片</div>
                <div class="ml-2 text-xs text-tx-secondary">个人中心顶部深色卡</div>
            </div>
            <el-form-item label="显示本区块">
                <div class="flex items-center gap-3">
                    <el-switch
                        v-model="contentData.enabled"
                        :active-value="1"
                        :inactive-value="0"
                    />
                    <span class="text-xs text-tx-secondary">
                        关闭后个人中心不显示婚期卡（有预约订单和无预约订单两种状态都不显示）
                    </span>
                </div>
            </el-form-item>
            <el-alert type="info" :closable="false" show-icon>
                <template #default>
                    剩余天数、待确认事项、婚礼场地自动读取用户资料与订单，不需要在这里配置；
                    用户没有已预约订单时自动切换为引导卡，不再要求手动填写婚期。
                </template>
            </el-alert>
        </el-card>

        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="flex items-end mb-4">
                <div class="text-base text-[#101010] dark:text-[#ffffff] font-medium">文案</div>
            </div>
            <el-form-item label="顶部小标">
                <el-input
                    v-model="contentData.label"
                    maxlength="32"
                    show-word-limit
                    placeholder="COUNTDOWN TO THE DAY"
                />
            </el-form-item>
            <el-form-item label="单位文案">
                <el-input
                    v-model="contentData.unit_text"
                    maxlength="24"
                    show-word-limit
                    placeholder="天后，就是我们的婚礼"
                />
                <div class="text-xs text-tx-secondary mt-1">
                    显示在剩余天数右侧；没有已预约订单时不出现
                </div>
            </el-form-item>
            <el-form-item label="未预约标题">
                <el-input
                    v-model="contentData.empty_title"
                    maxlength="20"
                    show-word-limit
                    placeholder="还没预约"
                />
            </el-form-item>
            <el-form-item label="未预约说明">
                <el-input
                    v-model="contentData.empty_desc"
                    maxlength="40"
                    show-word-limit
                    placeholder="预约档期后，这里会显示婚礼倒计时"
                />
            </el-form-item>
            <el-form-item label="预约按钮">
                <el-input
                    v-model="contentData.empty_action_text"
                    maxlength="8"
                    show-word-limit
                    placeholder="去预约"
                />
            </el-form-item>
            <div class="p-[12px] rounded-[8px] bg-[#f3f8ff] text-[#136bdf] text-xs leading-5">
                保存后，用户下次进入个人中心即可看到新文案。
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
