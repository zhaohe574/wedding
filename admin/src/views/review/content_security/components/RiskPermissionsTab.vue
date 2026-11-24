<template>
    <div class="risk-permissions-tab">
        <el-card shadow="never" class="risk-permissions-tab__card !border-none">
            <div class="risk-permissions-tab__section-header risk-permissions-tab__section-header--with-action">
                <div>
                    <div class="risk-permissions-tab__section-title">风险等级权限</div>
                    <div class="risk-permissions-tab__section-desc">
                        按风险等级配置可用能力分组；后端仍执行最终拦截，未纳管接口默认放行。
                    </div>
                </div>
                <el-button @click="restoreDefault">恢复默认策略</el-button>
            </div>

            <el-table :data="riskRankRows" border class="risk-permissions-tab__table">
                <el-table-column prop="label" label="风险等级" width="130" />
                <el-table-column label="允许能力分组" min-width="720">
                    <template #default="{ row }">
                        <el-checkbox-group
                            v-model="permissions[row.rank].enabled_abilities"
                            class="risk-permissions-tab__ability-group"
                        >
                            <el-checkbox v-for="ability in abilityOptions" :key="ability.code" :label="ability.code">
                                {{ ability.name }}
                            </el-checkbox>
                        </el-checkbox-group>
                    </template>
                </el-table-column>
                <el-table-column label="评论强制审核" width="150" align="center">
                    <template #default="{ row }">
                        <el-switch
                            v-model="permissions[row.rank].comment_force_review"
                            :active-value="true"
                            :inactive-value="false"
                        />
                    </template>
                </el-table-column>
            </el-table>
            <div class="risk-permissions-tab__helper risk-permissions-tab__note">
                默认策略：0、1级允许全部能力；2级允许全部能力但评论强制审核；3、4级禁止所有已纳管写操作。
            </div>

            <div class="risk-permissions-tab__actions">
                <el-button
                    v-perms="['setting.feature_switch/setConfig']"
                    type="primary"
                    :loading="saving"
                    @click="handleSave"
                >
                    保存
                </el-button>
            </div>
        </el-card>
    </div>
</template>

<script lang="ts" setup name="riskPermissionsTab">
/**
 * 风险等级权限 Tab：0~4 级能力分组矩阵。
 * 保存仅提交 { risk_rank_permissions }，不影响其他业务域字段。
 */
import { getFeatureSwitchConfig, setFeatureSwitchConfig } from '@/api/setting/featureSwitch'
import feedback from '@/utils/feedback'
import {
    createDefaultRiskPermissions,
    defaultRiskAbilityOptions,
    normalizeRiskPermissions,
    type RiskAbilityOption,
    riskRankRows} from '@/views/setting/feature_switch/shared'

const saving = ref(false)

const abilityOptions = ref<RiskAbilityOption[]>(defaultRiskAbilityOptions)
const defaultPermissions = ref(createDefaultRiskPermissions())
const permissions = ref(createDefaultRiskPermissions())

const getData = async () => {
    const data = await getFeatureSwitchConfig()
    const remoteOptions = Array.isArray(data?.risk_rank_ability_options)
        ? data.risk_rank_ability_options.filter(
              (item: { code?: unknown; name?: unknown }) =>
                  item && typeof item.code === 'string' && typeof item.name === 'string'
          )
        : []
    abilityOptions.value = remoteOptions.length > 0 ? remoteOptions : defaultRiskAbilityOptions
    defaultPermissions.value = normalizeRiskPermissions(data?.risk_rank_default_permissions)
    permissions.value = normalizeRiskPermissions(data?.risk_rank_permissions)
}

const restoreDefault = () => {
    permissions.value = normalizeRiskPermissions(defaultPermissions.value)
}

const handleSave = async () => {
    saving.value = true
    try {
        await setFeatureSwitchConfig({
            risk_rank_permissions: normalizeRiskPermissions(permissions.value)
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
.risk-permissions-tab {
    &__card {
        :deep(.el-card__body) {
            padding: 24px;
        }
    }

    &__section-header {
        margin-bottom: 24px;

        &--with-action {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
        }
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

    &__table {
        width: 100%;
    }

    &__ability-group {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(128px, 1fr));
        gap: 8px 14px;

        :deep(.el-checkbox) {
            margin-right: 0;
            height: auto;
            min-height: 28px;
            white-space: normal;
        }
    }

    &__helper {
        font-size: 12px;
        line-height: 1.6;
        color: var(--el-text-color-secondary);
    }

    &__note {
        margin-top: 12px;
    }

    &__actions {
        margin-top: 16px;
    }
}

@media (max-width: 768px) {
    .risk-permissions-tab {
        &__card :deep(.el-card__body) {
            padding: 18px;
        }

        &__section-header--with-action {
            flex-direction: column;
        }
    }
}
</style>
