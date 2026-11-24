<template>
    <div class="dashboard-access-tab">
        <el-form :model="formData" label-width="150px">
            <el-card shadow="never" class="dashboard-access-tab__card !border-none">
                <div class="dashboard-access-tab__section-header">
                    <div class="dashboard-access-tab__section-title">移动端管理员看板</div>
                    <div class="dashboard-access-tab__section-desc">控制移动端管理员看板入口是否展示。</div>
                </div>

                <el-form-item label="管理员看板入口">
                    <div class="dashboard-access-tab__inline-control">
                        <el-switch v-model="formData.admin_dashboard" :active-value="1" :inactive-value="0" />
                        <span class="dashboard-access-tab__helper">控制移动端管理员看板入口是否展示。</span>
                    </div>
                </el-form-item>
                <el-form-item label="看板访问权限">
                    <div class="dashboard-access-tab__field dashboard-access-tab__field--wide">
                        <span class="dashboard-access-tab__helper">
                            看板权限由后台 RBAC 决定：在「消息与系统 › 权限与组织 › 角色」中为角色勾选「经营看板」权限节点；移动端还需在「管理员」中把后台账号关联到对应用户账号。
                        </span>
                    </div>
                </el-form-item>
            </el-card>
        </el-form>

        <div class="dashboard-access-tab__actions">
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

<script lang="ts" setup name="dashboardAccessTab">
/**
 * 移动端看板开关 Tab：只负责管理员看板入口开关，
 * 保存仅提交 { admin_dashboard }，不影响其他业务域配置。
 */
import { getFeatureSwitchConfig, setFeatureSwitchConfig } from '@/api/setting/featureSwitch'
import feedback from '@/utils/feedback'

const saving = ref(false)

const formData = reactive({
    admin_dashboard: 1
})

const getData = async () => {
    const data = await getFeatureSwitchConfig()
    Object.assign(formData, {
        admin_dashboard: Number(data?.admin_dashboard ?? 1)
    })
}

const handleSave = async () => {
    saving.value = true
    try {
        await setFeatureSwitchConfig({
            admin_dashboard: formData.admin_dashboard
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
.dashboard-access-tab {
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

    &__inline-control,
    &__field {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    &__field {
        &--wide {
            width: min(100%, 640px);
        }
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
