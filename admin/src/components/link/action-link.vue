<template>
    <div class="action-link h-[540px] overflow-y-auto pr-1">
        <div class="text-lg font-medium text-[#101010] dark:text-white">快捷动作</div>
        <div class="text-xs text-tx-secondary mt-1 mb-4">
            不跳转新页面，直接触发电话拨打、微信原生客服或剪贴板复制
        </div>

        <div class="action-grid">
            <button
                v-for="item in DECORATE_ACTION_DEFS"
                :key="item.action"
                type="button"
                class="action-card"
                :class="{ 'is-active': selectedAction === item.action }"
                @click="selectAction(item.action)"
            >
                <div class="action-card__title">{{ item.label }}</div>
                <div class="action-card__desc">{{ item.desc }}</div>
            </button>
        </div>

        <div v-if="selectedAction" class="action-config mt-4">
            <template v-if="selectedAction === 'make_phone_call'">
                <el-form label-width="96px">
                    <el-form-item label="拨打号码">
                        <el-input
                            :model-value="modelValue?.query?.phone || ''"
                            maxlength="24"
                            clearable
                            placeholder="选填；留空时自动拨打平台客服热线"
                            @input="(val: string) => updateParam('phone', val)"
                        />
                    </el-form-item>
                </el-form>
                <div class="text-xs text-tx-secondary ml-[96px]">
                    支持座机或手机号（如 400-xxx-xxxx）；留空将自动调用客服接口获取最新服务热线。
                </div>
            </template>

            <template v-else-if="selectedAction === 'wechat_contact'">
                <el-alert
                    type="info"
                    show-icon
                    :closable="false"
                    title="微信小程序端将自动渲染为原生客服按钮（open-type='contact'）"
                    description="用户点击后直接打开微信原生客服会话；在 H5 等不支持原生客服的环境下会自动降级到「我的咨询」页面。"
                />
            </template>

            <template v-else-if="selectedAction === 'copy_text'">
                <el-form label-width="96px">
                    <el-form-item label="复制内容" required>
                        <el-input
                            :model-value="modelValue?.query?.text || ''"
                            type="textarea"
                            :rows="3"
                            maxlength="200"
                            show-word-limit
                            placeholder="必填，例如官方客服微信号、优惠口令或门店地址"
                            @input="(val: string) => updateParam('text', val)"
                        />
                    </el-form-item>
                    <el-form-item label="成功提示" class="!mb-0">
                        <el-input
                            :model-value="modelValue?.query?.toast || ''"
                            maxlength="30"
                            show-word-limit
                            clearable
                            placeholder="选填，默认提示「已复制」"
                            @input="(val: string) => updateParam('toast', val)"
                        />
                    </el-form-item>
                </el-form>
            </template>
        </div>
    </div>
</template>

<script lang="ts" setup>
import {
    DECORATE_ACTION_DEFS,
    type DecorateActionName
} from '@shared/contracts/decorate-links'
import { computed, type PropType } from 'vue'

import { type Link, LinkTypeEnum } from '.'

const props = defineProps({
    modelValue: {
        type: Object as PropType<Link>,
        default: () => ({ path: '' })
    }
})
const emit = defineEmits<{
    (event: 'update:modelValue', value: Link): void
}>()

const selectedAction = computed<DecorateActionName | ''>(() => {
    if (props.modelValue?.type !== LinkTypeEnum.ACTION) return ''
    return (props.modelValue.query?.action as DecorateActionName) || ''
})

function selectAction(action: DecorateActionName) {
    const def = DECORATE_ACTION_DEFS.find((a) => a.action === action)
    if (!def) return
    const prev = props.modelValue?.type === LinkTypeEnum.ACTION ? props.modelValue.query || {} : {}
    const query: Record<string, string> = { action }
    if (action === 'make_phone_call' && prev.phone) query.phone = prev.phone
    if (action === 'copy_text') {
        if (prev.text) query.text = prev.text
        if (prev.toast) query.toast = prev.toast
    }
    emit('update:modelValue', {
        path: def.path,
        name: def.label,
        type: LinkTypeEnum.ACTION,
        query
    })
}

function updateParam(key: 'phone' | 'text' | 'toast', value: string) {
    const action = selectedAction.value || 'make_phone_call'
    const def = DECORATE_ACTION_DEFS.find((a) => a.action === action)!
    const nextQuery: Record<string, string> = {
        ...(props.modelValue?.query || {}),
        action
    }
    const cleaned = String(value ?? '')
    if (cleaned.trim()) nextQuery[key] = cleaned
    else delete nextQuery[key]

    emit('update:modelValue', {
        path: def.path,
        name: def.label,
        type: LinkTypeEnum.ACTION,
        query: nextQuery
    })
}
</script>

<style scoped lang="scss">
.action-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
}

.action-card {
    padding: 14px;
    border: 1px solid var(--el-border-color);
    border-radius: 10px;
    background: var(--el-fill-color-blank);
    text-align: left;
    cursor: pointer;
    transition: all 0.16s ease;

    &:hover {
        border-color: var(--el-color-primary-light-5);
        background: var(--el-color-primary-light-9);
    }

    &.is-active {
        border-color: var(--el-color-primary);
        background: var(--el-color-primary-light-9);
        box-shadow: 0 0 0 1px var(--el-color-primary-light-7) inset;

        .action-card__title {
            color: var(--el-color-primary);
        }
    }

    &__title {
        font-size: 14px;
        font-weight: 600;
        color: var(--el-text-color-primary);
    }

    &__desc {
        margin-top: 6px;
        font-size: 12px;
        line-height: 1.5;
        color: var(--el-text-color-secondary);
    }
}

.action-config {
    padding: 16px;
    border: 1px solid var(--el-border-color-lighter);
    border-radius: 10px;
    background: var(--el-fill-color-extra-light);
}
</style>
