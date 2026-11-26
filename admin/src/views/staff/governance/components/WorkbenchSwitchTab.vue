<template>
    <div class="workbench-switch-tab">
        <el-form :model="formData" label-width="150px">
            <el-card shadow="never" class="workbench-switch-tab__card !border-none">
                <div class="workbench-switch-tab__section-header">
                    <div class="workbench-switch-tab__section-title">移动端工作台入口</div>
                </div>

                <el-form-item label="服务人员中心">
                    <el-switch v-model="formData.staff_center" :active-value="1" :inactive-value="0" />
                </el-form-item>
                <el-form-item>
                    <template #label>
                        <span class="form-label">
                            工作台写能力灰度
                            <el-tooltip
                                content="关闭时工作台仅开放只读浏览；开启后放开资料编辑、动态发布、作品与证书管理，写操作仍受内容安全检测。"
                                placement="top"
                                :popper-style="labelTipStyle"
                            >
                                <el-icon class="label-tip-icon"><QuestionFilled /></el-icon>
                            </el-tooltip>
                        </span>
                    </template>
                    <el-switch v-model="formData.staff_center_write" :active-value="1" :inactive-value="0" />
                </el-form-item>
                <el-form-item>
                    <template #label>
                        <span class="form-label">
                            服务人员后台账号
                            <el-tooltip
                                content="创建服务人员时同步生成后台账号，账号关系由系统统一管理，用于后台协同。"
                                placement="top"
                                :popper-style="labelTipStyle"
                            >
                                <el-icon class="label-tip-icon"><QuestionFilled /></el-icon>
                            </el-tooltip>
                        </span>
                    </template>
                    <el-tag type="info" effect="plain">自动同步开通</el-tag>
                </el-form-item>
                <el-form-item>
                    <template #label>
                        <span class="form-label">
                            服务人员标签审核
                            <el-tooltip
                                content="开启后，服务人员自助修改标签需经管理员审核后生效。"
                                placement="top"
                                :popper-style="labelTipStyle"
                            >
                                <el-icon class="label-tip-icon"><QuestionFilled /></el-icon>
                            </el-tooltip>
                        </span>
                    </template>
                    <el-switch v-model="formData.staff_tag_review_enabled" :active-value="1" :inactive-value="0" />
                </el-form-item>
            </el-card>
        </el-form>

        <div class="workbench-switch-tab__actions">
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

<script lang="ts" setup name="workbenchSwitchTab">
/**
 * 工作台与协同开关 Tab：只负责服务人员工作台域的 3 个开关字段，
 * 保存仅局部提交这些字段，不影响其他业务域配置。
 */
import { QuestionFilled } from '@element-plus/icons-vue'

import { getFeatureSwitchConfig, setFeatureSwitchConfig } from '@/api/setting/featureSwitch'
import feedback from '@/utils/feedback'

// 规则说明较长，限制浮层宽度避免撑成一行超宽提示。
const labelTipStyle = { maxWidth: '320px', lineHeight: '1.6' }

const saving = ref(false)

const createDefault = () => ({
    staff_center: 1,
    staff_center_write: 1,
    staff_tag_review_enabled: 0
})

const formData = reactive(createDefault())

const getData = async () => {
    const data = await getFeatureSwitchConfig()
    Object.assign(formData, {
        staff_center: Number(data?.staff_center ?? 1),
        staff_center_write: Number(data?.staff_center_write ?? 1),
        staff_tag_review_enabled: Number(data?.staff_tag_review_enabled ?? 0)
    })
}

const handleSave = async () => {
    saving.value = true
    try {
        await setFeatureSwitchConfig({
            staff_center: formData.staff_center,
            staff_center_write: formData.staff_center_write,
            staff_tag_review_enabled: formData.staff_tag_review_enabled
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
.workbench-switch-tab {
    &__card {
        :deep(.el-card__body) {
            padding: 24px;
        }
    }

    &__section-header {
        margin-bottom: 16px;
    }

    &__section-title {
        font-size: 16px;
        font-weight: 600;
        color: var(--el-text-color-primary);
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
}
</style>
