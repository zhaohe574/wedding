<template>
    <div class="workbench-switch-tab">
        <el-form :model="formData" label-width="150px">
            <el-card shadow="never" class="workbench-switch-tab__card !border-none">
                <div class="workbench-switch-tab__section-header">
                    <div class="workbench-switch-tab__section-title">移动端工作台入口</div>
                    <div class="workbench-switch-tab__section-desc">
                        控制服务人员中心入口、工作台写能力灰度与自助标签审核流程。
                    </div>
                </div>

                <el-form-item label="服务人员中心">
                    <div class="workbench-switch-tab__inline-control">
                        <el-switch v-model="formData.staff_center" :active-value="1" :inactive-value="0" />
                        <span class="workbench-switch-tab__helper">开启后，移动端服务人员入口与相关接口能力对外可用。</span>
                    </div>
                </el-form-item>
                <el-form-item label="工作台写能力灰度">
                    <div class="workbench-switch-tab__inline-control">
                        <el-switch v-model="formData.staff_center_write" :active-value="1" :inactive-value="0" />
                        <span class="workbench-switch-tab__helper">
                            关闭时小程序服务人员工作台仅开放只读浏览；开启后放开资料编辑、动态发布、作品与证书管理（写操作受内容安全检测）。
                        </span>
                    </div>
                </el-form-item>
                <el-form-item label="服务人员后台账号">
                    <div class="workbench-switch-tab__inline-control">
                        <span>服务人员必须同时开通后台账号，账号关系统一管理。</span>
                        <span class="workbench-switch-tab__helper">创建服务人员时同步生成后台账号，便于后台协同管理。</span>
                    </div>
                </el-form-item>
                <el-form-item label="服务人员标签审核">
                    <div class="workbench-switch-tab__inline-control">
                        <el-switch v-model="formData.staff_tag_review_enabled" :active-value="1" :inactive-value="0" />
                        <span class="workbench-switch-tab__helper">开启后，服务人员自助修改标签需经管理员审核后生效。</span>
                    </div>
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
import { getFeatureSwitchConfig, setFeatureSwitchConfig } from '@/api/setting/featureSwitch'
import feedback from '@/utils/feedback'

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

    &__inline-control {
        display: flex;
        flex-direction: column;
        gap: 8px;
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
}
</style>
