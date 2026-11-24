<template>
    <div
        class="link-picker flex-1"
        :class="{ 'is-disabled': disabled }"
        @click="!disabled && popupRef?.open()"
    >
        <div class="link-picker__box">
            <div v-if="summary.configured" class="link-picker__content">
                <el-tag size="small" effect="light" class="flex-none">{{ summary.typeLabel }}</el-tag>
                <span class="link-picker__title">{{ summary.title }}</span>
                <div v-if="summary.paramTags.length" class="link-picker__tags">
                    <el-tag
                        v-for="(tag, idx) in summary.paramTags"
                        :key="idx"
                        size="small"
                        type="info"
                        effect="plain"
                    >
                        {{ tag }}
                    </el-tag>
                </div>
            </div>
            <span v-else class="link-picker__placeholder">{{ placeholder }}</span>

            <div class="link-picker__icons">
                <icon
                    v-if="summary.configured && !disabled"
                    name="el-icon-Close"
                    class="link-picker__clear"
                    @click.stop="handleClear"
                />
                <icon v-else name="el-icon-ArrowRight" class="link-picker__arrow" />
            </div>
        </div>

        <popup
            ref="popupRef"
            width="1080px"
            title="点击跳转与参数设置"
            :async="true"
            @open="handleOpen"
            @confirm="handleConfirm"
        >
            <link-content v-model="draftLink" :is-tab="props.isTab" />
        </popup>
    </div>
</template>

<script lang="ts" setup>
import {
    formatDecorateLinkSummary,
    normalizeDecorateLink
} from '@shared/contracts/decorate-links'
import { computed, type PropType, ref, shallowRef } from 'vue'

import Popup from '@/components/popup/index.vue'
import feedback from '@/utils/feedback'

import { type Link, LinkTypeEnum } from '.'
import LinkContent from './index.vue'

const props = defineProps({
    modelValue: {
        type: Object as PropType<Link | Record<string, any>>,
        default: () => ({})
    },
    disabled: {
        type: Boolean,
        default: false
    },
    isTab: {
        type: Boolean,
        default: false
    },
    placeholder: {
        type: String,
        default: '请选择跳转目标'
    }
})
const emit = defineEmits<{
    (event: 'update:modelValue', value: Link): void
}>()

const popupRef = shallowRef<InstanceType<typeof Popup>>()
const draftLink = ref<Link>({ path: '', type: LinkTypeEnum.SHOP_PAGES })

const summary = computed(() => formatDecorateLinkSummary(props.modelValue as Link))

const handleOpen = () => {
    const normalized = normalizeDecorateLink(props.modelValue)
    draftLink.value = normalized.path
        ? normalized
        : { path: '', type: LinkTypeEnum.SHOP_PAGES }
}

const handleClear = () => {
    if (props.disabled) return
    emit('update:modelValue', { path: '' })
}

const validateDraft = (link: Link): string | null => {
    if (!link.path && !link.type) return null

    if (link.type === LinkTypeEnum.CUSTOM_LINK) {
        const url = String(link.query?.url || '').trim()
        if (!/^https:\/\/\S+/i.test(url)) {
            return '请填写以 https:// 开头的外部网页地址'
        }
        return null
    }

    if (link.type === LinkTypeEnum.MINI_PROGRAM) {
        const appId = String(link.query?.appId || '').trim()
        const queryStr = String(link.query?.query || '').trim()
        if (!appId) {
            return '请填写目标小程序的 AppID'
        }
        if (queryStr && /[\u4e00-\u9fa5\s]/.test(queryStr)) {
            return '小程序跳转参数不允许包含中文或空格'
        }
        return null
    }

    if (link.type === LinkTypeEnum.STAFF_DETAIL) {
        const id = String(link.query?.id || '').trim()
        if (!/^[1-9]\d*$/.test(id)) {
            return '请在表格中选择一位服务人员'
        }
        return null
    }

    if (link.type === LinkTypeEnum.DYNAMIC_LIST) {
        const id = String(link.query?.id || '').trim()
        if (!/^[1-9]\d*$/.test(id)) {
            return '请在表格中选择一条动态'
        }
        return null
    }

    if (link.type === LinkTypeEnum.ACTION) {
        const action = String(link.query?.action || '').trim()
        if (!action) {
            return '请选择一个快捷动作'
        }
        if (action === 'copy_text' && !String(link.query?.text || '').trim()) {
            return '请填写要复制的文本内容'
        }
        return null
    }

    return null
}

const handleConfirm = () => {
    const error = validateDraft(draftLink.value)
    if (error) {
        feedback.msgError(error)
        return
    }

    const normalized = normalizeDecorateLink(draftLink.value)
    emit('update:modelValue', normalized)
    popupRef.value?.close()
}
</script>

<style scoped lang="scss">
.link-picker {
    min-width: 0;
    cursor: pointer;

    &.is-disabled {
        cursor: not-allowed;

        .link-picker__box {
            background-color: var(--el-disabled-bg-color);
            border-color: var(--el-disabled-border-color);
            color: var(--el-disabled-text-color);
        }
    }

    &__box {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        min-height: 32px;
        padding: 4px 11px;
        border: 1px solid var(--el-border-color);
        border-radius: var(--el-border-radius-base);
        background: var(--el-fill-color-blank);
        transition: border-color 0.2s;

        &:hover {
            border-color: var(--el-border-color-hover);
        }
    }

    &__content {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
        flex: 1;
        min-width: 0;
    }

    &__title {
        font-size: 13px;
        color: var(--el-text-color-primary);
        max-width: 220px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__tags {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
    }

    &__placeholder {
        flex: 1;
        font-size: 13px;
        color: var(--el-text-color-placeholder);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__icons {
        display: flex;
        align-items: center;
        flex: none;
        color: var(--el-text-color-placeholder);
    }

    &__clear:hover {
        color: var(--el-text-color-regular);
    }
}
</style>
