<template>
    <el-form label-width="96px" class="cs-attr">
        <!-- 礼宾主卡与页面开关 -->
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="flex items-center gap-1 mb-4">
                <span class="text-base text-[#101010] font-medium">礼宾主卡与页面开关</span>
                <el-tooltip
                    content="客服基础信息由系统设置及顾问管理下发"
                    placement="top"
                    :popper-style="labelTipStyle"
                >
                    <el-icon class="label-tip-icon"><QuestionFilled /></el-icon>
                </el-tooltip>
            </div>

            <el-form-item label="顶部英文标">
                <el-input
                    v-model="contentData.eyebrow"
                    maxlength="20"
                    show-word-limit
                    placeholder="如 CONCIERGE，留空则隐藏"
                />
            </el-form-item>

            <el-form-item label="主按钮文案">
                <el-input
                    v-model="contentData.button_text"
                    maxlength="16"
                    show-word-limit
                    placeholder="默认：微信在线咨询"
                />
            </el-form-item>

            <el-form-item label="电话按钮文案">
                <el-input
                    v-model="contentData.phone_button_text"
                    maxlength="12"
                    show-word-limit
                    placeholder="默认：电话专线"
                />
            </el-form-item>

            <el-form-item label="默认咨询引导">
                <el-input
                    v-model="contentData.tips"
                    type="textarea"
                    :rows="2"
                    maxlength="100"
                    show-word-limit
                    placeholder="仅当未配置实时咨询提示时作为兜底文案展示"
                />
            </el-form-item>

            <el-form-item label="显示自助服务">
                <el-switch
                    v-model="contentData.show_quick_bar"
                    :active-value="1"
                    :inactive-value="0"
                />
            </el-form-item>

            <el-form-item label="底部说明文案" class="!mb-0">
                <el-input
                    v-model="contentData.footer_text"
                    maxlength="60"
                    show-word-limit
                    placeholder="留空则不显示底部说明"
                />
            </el-form-item>
        </el-card>

        <!-- 服务保障 -->
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="flex items-center justify-between mb-4">
                <span class="text-base text-[#101010] font-medium">服务保障</span>
                <span class="text-xs text-tx-secondary">上限 6 项</span>
            </div>

            <el-form-item label="显示服务保障">
                <el-switch
                    v-model="contentData.show_assurance"
                    :active-value="1"
                    :inactive-value="0"
                />
            </el-form-item>

            <el-form-item label="区块标题">
                <el-input
                    v-model="contentData.assurance_title"
                    maxlength="16"
                    show-word-limit
                    placeholder="默认：服务保障"
                />
            </el-form-item>

            <el-form-item label="右侧副标题">
                <el-input
                    v-model="contentData.assurance_subtitle"
                    maxlength="24"
                    show-word-limit
                    placeholder="默认：全程守护备婚与礼成"
                />
            </el-form-item>

            <div class="list-wrap">
                <div
                    v-for="(item, index) in contentData.assurances"
                    :key="index"
                    class="list-item bg-fill-light"
                >
                    <div class="list-item__head">
                        <span class="text-xs text-tx-secondary font-medium">
                            保障项 {{ index + 1 }}
                        </span>
                        <div class="flex items-center gap-1">
                            <el-button
                                link
                                size="small"
                                :icon="ArrowUp"
                                :disabled="index === 0"
                                @click="moveItem('assurances', index, -1)"
                            />
                            <el-button
                                link
                                size="small"
                                :icon="ArrowDown"
                                :disabled="index === contentData.assurances.length - 1"
                                @click="moveItem('assurances', index, 1)"
                            />
                            <el-button
                                link
                                type="danger"
                                size="small"
                                :icon="Delete"
                                @click="removeItem('assurances', index)"
                            />
                        </div>
                    </div>

                    <el-form-item label="图标" label-width="64px" class="mb-3">
                        <el-select v-model="item.icon" class="w-full">
                            <el-option
                                v-for="opt in CUSTOMER_SERVICE_ASSURANCE_ICONS"
                                :key="opt.value"
                                :label="opt.label"
                                :value="opt.value"
                            />
                        </el-select>
                    </el-form-item>

                    <el-form-item label="标题" label-width="64px" class="mb-3">
                        <el-input
                            v-model="item.title"
                            maxlength="12"
                            show-word-limit
                            placeholder="如：实名甄选"
                        />
                    </el-form-item>

                    <el-form-item label="短句说明" label-width="64px" class="!mb-0">
                        <el-input
                            v-model="item.desc"
                            maxlength="16"
                            show-word-limit
                            placeholder="建议 8–9 字，如：服务人员严格认证"
                        />
                    </el-form-item>
                </div>

                <el-button
                    class="w-full mt-3"
                    type="primary"
                    plain
                    :disabled="(contentData.assurances?.length || 0) >= MAX_ASSURANCES"
                    @click="addAssurance"
                >
                    添加保障项（{{ contentData.assurances?.length || 0 }}/{{ MAX_ASSURANCES }}）
                </el-button>
            </div>
        </el-card>

        <!-- 常见问题 -->
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="flex items-center justify-between mb-4">
                <span class="text-base text-[#101010] font-medium">常见问题（FAQ）</span>
                <span class="text-xs text-tx-secondary">上限 10 条</span>
            </div>

            <el-form-item label="显示常见问题">
                <el-switch v-model="contentData.show_faq" :active-value="1" :inactive-value="0" />
            </el-form-item>

            <el-form-item label="区块标题">
                <el-input
                    v-model="contentData.faq_title"
                    maxlength="16"
                    show-word-limit
                    placeholder="默认：常见问题"
                />
            </el-form-item>

            <el-form-item label="右侧副标题">
                <el-input
                    v-model="contentData.faq_subtitle"
                    maxlength="24"
                    show-word-limit
                    placeholder="默认：点击展开指引"
                />
            </el-form-item>

            <div class="list-wrap">
                <div
                    v-for="(faq, index) in contentData.faqs"
                    :key="index"
                    class="list-item bg-fill-light"
                >
                    <div class="list-item__head">
                        <span class="text-xs text-tx-secondary font-medium">
                            问题 {{ String(index + 1).padStart(2, '0') }}
                        </span>
                        <div class="flex items-center gap-1">
                            <el-button
                                link
                                size="small"
                                :icon="ArrowUp"
                                :disabled="index === 0"
                                @click="moveItem('faqs', index, -1)"
                            />
                            <el-button
                                link
                                size="small"
                                :icon="ArrowDown"
                                :disabled="index === contentData.faqs.length - 1"
                                @click="moveItem('faqs', index, 1)"
                            />
                            <el-button
                                link
                                type="danger"
                                size="small"
                                :icon="Delete"
                                @click="removeItem('faqs', index)"
                            />
                        </div>
                    </div>

                    <el-form-item label="问题" label-width="56px" class="mb-3">
                        <el-input
                            v-model="faq.q"
                            maxlength="40"
                            show-word-limit
                            placeholder="请输入用户常见疑问"
                        />
                    </el-form-item>

                    <el-form-item label="回答" label-width="56px" class="!mb-0">
                        <el-input
                            v-model="faq.a"
                            type="textarea"
                            :rows="3"
                            maxlength="200"
                            show-word-limit
                            placeholder="请输入清晰的处理指引"
                        />
                    </el-form-item>
                </div>

                <el-button
                    class="w-full mt-3"
                    type="primary"
                    plain
                    :disabled="(contentData.faqs?.length || 0) >= MAX_FAQS"
                    @click="addFaq"
                >
                    添加常见问题（{{ contentData.faqs?.length || 0 }}/{{ MAX_FAQS }}）
                </el-button>
            </div>
        </el-card>
    </el-form>
</template>

<script lang="ts" setup>
import { ArrowDown, ArrowUp, Delete, QuestionFilled } from '@element-plus/icons-vue'
import {
    CUSTOMER_SERVICE_ASSURANCE_ICONS,
    normalizeCustomerServiceContent
} from '@shared/contracts/decorate'
import { cloneDeep } from 'lodash-es'
import type { PropType } from 'vue'
import { computed, onMounted } from 'vue'

import type options from './options'

type OptionsType = ReturnType<typeof options>

const MAX_ASSURANCES = 6
const MAX_FAQS = 10
const labelTipStyle = { maxWidth: '320px', lineHeight: '1.6' }

const emits = defineEmits<(event: 'update:content', data: OptionsType['content']) => void>()
const props = defineProps({
    content: {
        type: Object as PropType<OptionsType['content']>,
        default: () => ({})
    },
    styles: {
        type: Object as PropType<Record<string, any>>,
        default: () => ({})
    }
})

const contentData = computed({
    get: () => props.content as OptionsType['content'],
    set: (newValue) => {
        emits('update:content', newValue)
    }
})

onMounted(() => {
    if (!Array.isArray(props.content?.assurances) || !Array.isArray(props.content?.faqs)) {
        emits('update:content', normalizeCustomerServiceContent(props.content))
    }
})

function cloneNormalized(): OptionsType['content'] {
    return cloneDeep(normalizeCustomerServiceContent(props.content))
}

function addAssurance() {
    const next = cloneNormalized()
    if (next.assurances.length >= MAX_ASSURANCES) return
    next.assurances.push({ icon: 'check', title: '', desc: '' })
    emits('update:content', next)
}

function addFaq() {
    const next = cloneNormalized()
    if (next.faqs.length >= MAX_FAQS) return
    next.faqs.push({ q: '', a: '' })
    emits('update:content', next)
}

function moveItem(field: 'assurances' | 'faqs', index: number, delta: -1 | 1) {
    const next = cloneNormalized()
    const list = next[field] as any[]
    const target = index + delta
    if (target < 0 || target >= list.length) return
    const [moved] = list.splice(index, 1)
    list.splice(target, 0, moved)
    emits('update:content', next)
}

function removeItem(field: 'assurances' | 'faqs', index: number) {
    const next = cloneNormalized()
    ;(next[field] as any[]).splice(index, 1)
    emits('update:content', next)
}
</script>

<style lang="scss" scoped>
.cs-attr {
    padding-bottom: 12px;

    .label-tip-icon {
        font-size: 13px;
        color: var(--el-text-color-placeholder);
        cursor: help;
    }
}

.list-wrap {
    width: 100%;
}

.list-item {
    padding: 12px 14px 14px;
    border-radius: 8px;
    margin-bottom: 10px;

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
    }
}
</style>
