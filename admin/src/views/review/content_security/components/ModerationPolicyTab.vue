<template>
    <div class="moderation-policy-tab">
        <el-alert
            v-if="formData.mini_program_review_mode === 1"
            type="warning"
            show-icon
            :closable="false"
            class="moderation-policy-tab__alert"
            title="小程序送审模式已开启：新增动态、评论与评价入口已隐藏，请于过审后及时关闭"
        />

        <el-form :model="formData" label-width="150px">
            <el-card shadow="never" class="moderation-policy-tab__card !border-none">
                <div class="moderation-policy-tab__section-header">
                    <div class="moderation-policy-tab__section-title">小程序送审模式</div>
                </div>

                <el-form-item>
                    <template #label>
                        <span class="form-label">
                            送审模式
                            <el-tooltip
                                content="开启后小程序端隐藏并拦截发布动态、评论、评价与追评入口，已有内容继续展示；变更写入服务端审计日志。"
                                placement="top"
                                :popper-style="labelTipStyle"
                            >
                                <el-icon class="label-tip-icon"><QuestionFilled /></el-icon>
                            </el-tooltip>
                        </span>
                    </template>
                    <el-switch v-model="formData.mini_program_review_mode" :active-value="1" :inactive-value="0" />
                </el-form-item>
            </el-card>

            <el-card shadow="never" class="moderation-policy-tab__card !border-none">
                <div class="moderation-policy-tab__section-header">
                    <div class="moderation-policy-tab__section-title">评论审核与微信文本安全</div>
                </div>

                <el-form-item label="评论审核">
                    <el-switch v-model="formData.comment_review_enabled" :active-value="1" :inactive-value="0" />
                </el-form-item>
                <el-form-item>
                    <template #label>
                        <span class="form-label">
                            微信文本检测
                            <el-tooltip
                                content="资料更新与评论发布按微信文本内容安全结果限制；评论审核开启时评论不再重复调用微信检测。"
                                placement="top"
                                :popper-style="labelTipStyle"
                            >
                                <el-icon class="label-tip-icon"><QuestionFilled /></el-icon>
                            </el-tooltip>
                        </span>
                    </template>
                    <el-switch v-model="formData.wechat_text_check_enabled" :active-value="1" :inactive-value="0" />
                </el-form-item>
                <el-form-item>
                    <template #label>
                        <span class="form-label">
                            资料检测置信度
                            <el-tooltip
                                content="资料更新命中 risky，或开启 review 按命中处理时，微信返回 prob 达到该值才拒绝保存。"
                                placement="top"
                                :popper-style="labelTipStyle"
                            >
                                <el-icon class="label-tip-icon"><QuestionFilled /></el-icon>
                            </el-tooltip>
                        </span>
                    </template>
                    <div class="moderation-policy-tab__unit-field">
                        <el-input-number
                            v-model="formData.wechat_text_check_profile_prob"
                            :min="0"
                            :max="100"
                            :precision="0"
                            controls-position="right"
                        />
                        <span class="moderation-policy-tab__unit">%</span>
                    </div>
                </el-form-item>
                <el-form-item>
                    <template #label>
                        <span class="form-label">
                            评论检测置信度
                            <el-tooltip
                                content="评论审核关闭时，评论命中微信检测且 prob 达到该值后进入待审核。"
                                placement="top"
                                :popper-style="labelTipStyle"
                            >
                                <el-icon class="label-tip-icon"><QuestionFilled /></el-icon>
                            </el-tooltip>
                        </span>
                    </template>
                    <div class="moderation-policy-tab__unit-field">
                        <el-input-number
                            v-model="formData.wechat_text_check_comment_prob"
                            :min="0"
                            :max="100"
                            :precision="0"
                            controls-position="right"
                        />
                        <span class="moderation-policy-tab__unit">%</span>
                    </div>
                </el-form-item>
                <el-form-item>
                    <template #label>
                        <span class="form-label">
                            review按命中处理
                            <el-tooltip
                                content="开启后 suggest=review 且置信度达标按命中处理；suggest=risky 始终按置信度阈值判断。"
                                placement="top"
                                :popper-style="labelTipStyle"
                            >
                                <el-icon class="label-tip-icon"><QuestionFilled /></el-icon>
                            </el-tooltip>
                        </span>
                    </template>
                    <el-switch v-model="formData.wechat_text_check_review_as_hit" :active-value="1" :inactive-value="0" />
                </el-form-item>
            </el-card>
        </el-form>

        <div class="moderation-policy-tab__actions">
            <el-button
                v-perms="['setting.feature_switch/setConfig']"
                type="primary"
                :loading="saving"
                @click="handleSave"
            >
                保存
            </el-button>
        </div>
    </div>
</template>

<script lang="ts" setup name="moderationPolicyTab">
/**
 * 审核与送审策略 Tab：只负责内容风控域的 6 个开关字段，
 * 保存仅局部提交这些字段，不影响其他业务域配置。
 */
import { QuestionFilled } from '@element-plus/icons-vue'

import { getFeatureSwitchConfig, setFeatureSwitchConfig } from '@/api/setting/featureSwitch'
import feedback from '@/utils/feedback'

// 规则说明较长，限制浮层宽度避免撑成一行超宽提示。
const labelTipStyle = { maxWidth: '320px', lineHeight: '1.6' }

const saving = ref(false)

const createDefault = () => ({
    mini_program_review_mode: 0,
    comment_review_enabled: 0,
    wechat_text_check_enabled: 1,
    wechat_text_check_profile_prob: 80,
    wechat_text_check_comment_prob: 70,
    wechat_text_check_review_as_hit: 1
})

const formData = reactive(createDefault())

const getData = async () => {
    const data = await getFeatureSwitchConfig()
    Object.assign(formData, {
        mini_program_review_mode: Number(data?.mini_program_review_mode ?? 0),
        comment_review_enabled: Number(data?.comment_review_enabled ?? 0),
        wechat_text_check_enabled: Number(data?.wechat_text_check_enabled ?? 1),
        wechat_text_check_profile_prob: Number(data?.wechat_text_check_profile_prob ?? 80),
        wechat_text_check_comment_prob: Number(data?.wechat_text_check_comment_prob ?? 70),
        wechat_text_check_review_as_hit: Number(data?.wechat_text_check_review_as_hit ?? 1)
    })
}

const handleSave = async () => {
    saving.value = true
    try {
        await setFeatureSwitchConfig({
            mini_program_review_mode: formData.mini_program_review_mode,
            comment_review_enabled: formData.comment_review_enabled,
            wechat_text_check_enabled: formData.wechat_text_check_enabled,
            wechat_text_check_profile_prob: formData.wechat_text_check_profile_prob,
            wechat_text_check_comment_prob: formData.wechat_text_check_comment_prob,
            wechat_text_check_review_as_hit: formData.wechat_text_check_review_as_hit
        })
        feedback.msgSuccess('保存成功')
        getData()
    } finally {
        saving.value = false
    }
}

onMounted(getData)
</script>

<style lang="scss" scoped>
.moderation-policy-tab {
    &__alert {
        margin-bottom: 16px;
    }

    &__card {
        :deep(.el-card__body) {
            padding: 24px;
        }
    }

    &__card + &__card {
        margin-top: 12px;
    }

    &__section-header {
        margin-bottom: 16px;
    }

    &__section-title {
        font-size: 16px;
        font-weight: 600;
        color: var(--el-text-color-primary);
    }

    &__unit-field {
        display: flex;
        align-items: center;
        gap: 8px;
        width: min(100%, 360px);

        :deep(.el-input-number) {
            flex: 1;
            width: auto;
            min-width: 0;
        }
    }

    &__unit {
        flex-shrink: 0;
        font-size: 13px;
        color: var(--el-text-color-secondary);
    }

    .form-label {
        display: inline-flex;
        align-items: center;
    }

    .label-tip-icon {
        margin-left: 4px;
        font-size: 13px;
        color: var(--el-text-color-placeholder);
        cursor: help;
    }

    &__actions {
        margin-top: 16px;
    }

    :deep(.el-form-item) {
        margin-bottom: 20px;
    }

    :deep(.el-form-item:last-child) {
        margin-bottom: 0;
    }

    :deep(.el-input-number) {
        width: 100%;
    }
}
</style>
