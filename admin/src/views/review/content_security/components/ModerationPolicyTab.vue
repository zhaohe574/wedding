<template>
    <div class="moderation-policy-tab">
        <el-alert
            v-if="formData.mini_program_review_mode === 1"
            type="warning"
            show-icon
            :closable="false"
            class="moderation-policy-tab__alert"
            title="小程序送审模式已开启"
            description="开启期间，小程序端隐藏并拦截发布动态、发表评论、发表评价和追评入口，已有内容继续展示；开关变更会写入服务端审计日志，送审通过后请及时关闭。"
        />

        <el-form :model="formData" label-width="150px">
            <el-card shadow="never" class="moderation-policy-tab__card !border-none">
                <div class="moderation-policy-tab__section-header">
                    <div class="moderation-policy-tab__section-title">小程序送审模式</div>
                    <div class="moderation-policy-tab__section-desc">送审期间临时隐藏小程序内新增内容入口。</div>
                </div>

                <el-form-item label="送审模式">
                    <div class="moderation-policy-tab__inline-control">
                        <el-switch v-model="formData.mini_program_review_mode" :active-value="1" :inactive-value="0" />
                        <span class="moderation-policy-tab__helper">
                            开启后，小程序端隐藏并拦截发布动态、发表评论、发表评价和追评；已有内容继续展示。变更写入服务端审计日志。
                        </span>
                    </div>
                </el-form-item>
            </el-card>

            <el-card shadow="never" class="moderation-policy-tab__card !border-none">
                <div class="moderation-policy-tab__section-header">
                    <div class="moderation-policy-tab__section-title">评论审核与微信文本安全</div>
                    <div class="moderation-policy-tab__section-desc">控制评论发布后的审核策略，以及微信文本内容安全的拦截阈值。</div>
                </div>

                <el-form-item label="评论审核">
                    <div class="moderation-policy-tab__inline-control">
                        <el-switch v-model="formData.comment_review_enabled" :active-value="1" :inactive-value="0" />
                        <span class="moderation-policy-tab__helper">开启后，用户发表的评论需要管理员审核通过后才能显示。</span>
                    </div>
                </el-form-item>
                <el-form-item label="微信文本检测">
                    <div class="moderation-policy-tab__inline-control">
                        <el-switch v-model="formData.wechat_text_check_enabled" :active-value="1" :inactive-value="0" />
                        <span class="moderation-policy-tab__helper">
                            开启后，资料更新和评论发布会按微信文本内容安全结果进行限制；评论审核开启时评论不重复调用微信检测。
                        </span>
                    </div>
                </el-form-item>
                <el-form-item label="资料检测置信度">
                    <div class="moderation-policy-tab__field moderation-policy-tab__field--compact">
                        <el-input-number
                            v-model="formData.wechat_text_check_profile_prob"
                            :min="0"
                            :max="100"
                            :precision="0"
                            controls-position="right"
                        />
                        <span class="moderation-policy-tab__helper">
                            资料更新命中 risky 或开启 review 命中处理时，微信返回 prob 达到该阈值才拒绝保存。
                        </span>
                    </div>
                </el-form-item>
                <el-form-item label="评论检测置信度">
                    <div class="moderation-policy-tab__field moderation-policy-tab__field--compact">
                        <el-input-number
                            v-model="formData.wechat_text_check_comment_prob"
                            :min="0"
                            :max="100"
                            :precision="0"
                            controls-position="right"
                        />
                        <span class="moderation-policy-tab__helper">评论审核关闭时，评论命中微信检测且 prob 达到该阈值后进入待审核。</span>
                    </div>
                </el-form-item>
                <el-form-item label="review按命中处理">
                    <div class="moderation-policy-tab__inline-control">
                        <el-switch v-model="formData.wechat_text_check_review_as_hit" :active-value="1" :inactive-value="0" />
                        <span class="moderation-policy-tab__helper">
                            开启后，微信 suggest=review 且置信度达标时按命中处理；suggest=risky 不受该开关影响，但仍按置信度阈值判断。
                        </span>
                    </div>
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
import { getFeatureSwitchConfig, setFeatureSwitchConfig } from '@/api/setting/featureSwitch'
import feedback from '@/utils/feedback'

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
        margin-top: 16px;
    }

    &__section-header {
        margin-bottom: 24px;
    }

    &__section-title {
        font-size: 16px;
        font-weight: 600;
        color: var(--el-text-color-primary);
    }

    &__section-desc {
        margin-top: 6px;
        font-size: 13px;
        line-height: 1.6;
        color: var(--el-text-color-secondary);
    }

    &__inline-control,
    &__field {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    &__field {
        width: min(100%, 360px);
    }

    &__helper {
        font-size: 12px;
        line-height: 1.6;
        color: var(--el-text-color-secondary);
    }

    &__actions {
        margin-top: 16px;
    }

    :deep(.el-form-item) {
        margin-bottom: 24px;
    }

    :deep(.el-form-item:last-child) {
        margin-bottom: 0;
    }

    :deep(.el-input-number) {
        width: 100%;
    }
}
</style>
