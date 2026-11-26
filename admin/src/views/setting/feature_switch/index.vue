<template>
    <div class="feature-switch">
        <div class="feature-switch__split-bar">
            <span class="feature-switch__split-title">功能开关已按业务域拆分</span>
            <div class="feature-switch__split-links">
                <el-button v-if="tradeRulesPath" link type="primary" @click="goSplitPage(tradeRulesPath)">
                    交易与履约规则
                </el-button>
                <el-button v-if="contentSecurityPath" link type="primary" @click="goSplitPage(contentSecurityPath)">
                    内容风控与审核
                </el-button>
                <el-button v-if="staffGovernancePath" link type="primary" @click="goSplitPage(staffGovernancePath)">
                    资质标签与工作台
                </el-button>
                <el-button v-if="opsHubPath" link type="primary" @click="goSplitPage(opsHubPath)">
                    缓存环境与看板开关
                </el-button>
            </div>
        </div>

        <el-form :model="formData" label-width="150px" class="feature-switch__form">
            <el-card shadow="never" class="feature-switch__card !border-none">
                <div class="feature-switch__section-header">
                    <div class="feature-switch__section-title">动态社区</div>
                </div>

                <el-form-item>
                    <template #label>
                        <span class="form-label">
                            小程序送审模式
                            <el-tooltip
                                content="开启后小程序端隐藏并拦截发布动态、评论、评价与追评入口，已有内容继续展示；送审通过后请及时关闭。"
                                placement="top"
                                :popper-style="labelTipStyle"
                            >
                                <el-icon class="label-tip-icon"><QuestionFilled /></el-icon>
                            </el-tooltip>
                        </span>
                    </template>
                    <el-switch v-model="formData.mini_program_review_mode" :active-value="1" :inactive-value="0" />
                </el-form-item>
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
                                content="微信返回 prob 达到该值才拒绝保存；suggest=risky，或开启 review 按命中处理且 suggest=review 时生效。"
                                placement="top"
                                :popper-style="labelTipStyle"
                            >
                                <el-icon class="label-tip-icon"><QuestionFilled /></el-icon>
                            </el-tooltip>
                        </span>
                    </template>
                    <div class="feature-switch__unit-field">
                        <el-input-number
                            v-model="formData.wechat_text_check_profile_prob"
                            :min="0"
                            :max="100"
                            :precision="0"
                            controls-position="right"
                        />
                        <span class="feature-switch__unit">%</span>
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
                    <div class="feature-switch__unit-field">
                        <el-input-number
                            v-model="formData.wechat_text_check_comment_prob"
                            :min="0"
                            :max="100"
                            :precision="0"
                            controls-position="right"
                        />
                        <span class="feature-switch__unit">%</span>
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

            <el-card shadow="never" class="feature-switch__card !border-none">
                <div class="feature-switch__section-header feature-switch__section-header--with-action">
                    <div class="feature-switch__section-title">风险等级权限</div>
                    <el-button @click="restoreDefaultRiskPermissions">恢复默认策略</el-button>
                </div>

                <el-table :data="riskRankRows" border class="feature-switch__risk-table">
                    <el-table-column prop="label" label="风险等级" width="130" />
                    <el-table-column label="允许能力分组" min-width="720">
                        <template #default="{ row }">
                            <el-checkbox-group
                                v-model="formData.risk_rank_permissions[row.rank].enabled_abilities"
                                class="feature-switch__ability-group"
                            >
                                <el-checkbox
                                    v-for="ability in riskAbilityOptions"
                                    :key="ability.code"
                                    :label="ability.code"
                                >
                                    {{ ability.name }}
                                </el-checkbox>
                            </el-checkbox-group>
                        </template>
                    </el-table-column>
                    <el-table-column label="评论强制审核" width="150" align="center">
                        <template #default="{ row }">
                            <el-switch
                                v-model="formData.risk_rank_permissions[row.rank].comment_force_review"
                                :active-value="true"
                                :inactive-value="false"
                            />
                        </template>
                    </el-table-column>
                </el-table>
                <div class="feature-switch__risk-note">0–1 级全权限 · 2 级评论强审 · 3–4 级禁用纳管写操作</div>
            </el-card>

            <el-card shadow="never" class="feature-switch__card !border-none">
                <div class="feature-switch__section-header">
                    <div class="feature-switch__section-title">服务人员工作台</div>
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
                <el-form-item label="服务人员后台账号">
                    <el-tag type="info" effect="plain">创建时自动开通</el-tag>
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

            <el-card shadow="never" class="feature-switch__card !border-none">
                <div class="feature-switch__section-header">
                    <div class="feature-switch__section-title">管理员看板访问</div>
                </div>

                <el-form-item label="管理员看板入口">
                    <el-switch v-model="formData.admin_dashboard" :active-value="1" :inactive-value="0" />
                </el-form-item>
                <el-form-item>
                    <template #label>
                        <span class="form-label">
                            看板访问权限
                            <el-tooltip
                                content="在「消息与系统 › 权限与组织 › 角色」为角色勾选「经营看板」权限节点；移动端还需在「管理员」中把后台账号关联到对应用户账号。"
                                placement="top"
                                :popper-style="labelTipStyle"
                            >
                                <el-icon class="label-tip-icon"><QuestionFilled /></el-icon>
                            </el-tooltip>
                        </span>
                    </template>
                    <el-tag type="info" effect="plain">角色权限控制</el-tag>
                </el-form-item>
            </el-card>

            <el-card shadow="never" class="feature-switch__card !border-none">
                <div class="feature-switch__section-header">
                    <div class="feature-switch__section-title">服务完成确认</div>
                </div>

                <el-form-item label="用户完成服务">
                    <el-switch v-model="formData.order_complete_by_user" :active-value="1" :inactive-value="0" />
                </el-form-item>
                <el-form-item label="服务人员完成服务">
                    <el-switch v-model="formData.order_complete_by_staff" :active-value="1" :inactive-value="0" />
                </el-form-item>
            </el-card>

            <el-card shadow="never" class="feature-switch__card !border-none">
                <div class="feature-switch__section-header">
                    <div class="feature-switch__section-title">定金支付规则</div>
                </div>

                <el-form-item label="开启定金模式">
                    <el-switch v-model="formData.enable_deposit_mode" :active-value="1" :inactive-value="0" />
                </el-form-item>
                <el-form-item v-if="formData.enable_deposit_mode === 1" label="定金计算方式">
                    <el-radio-group v-model="formData.deposit_type">
                        <el-radio label="ratio">按比例</el-radio>
                        <el-radio label="fixed">固定金额</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item v-if="formData.enable_deposit_mode === 1" :label="formData.deposit_type === 'fixed' ? '定金金额' : '定金比例'">
                    <div class="feature-switch__unit-field">
                        <el-input-number
                            v-model="formData.deposit_value"
                            :min="0.01"
                            :max="formData.deposit_type === 'fixed' ? 999999 : 99.99"
                            :precision="2"
                            controls-position="right"
                        />
                        <span class="feature-switch__unit">{{ formData.deposit_type === 'fixed' ? '元' : '%' }}</span>
                    </div>
                </el-form-item>
                <el-form-item v-if="formData.enable_deposit_mode === 1 && formData.deposit_type === 'ratio'">
                    <template #label>
                        <span class="form-label">
                            尾款向上凑整
                            <el-tooltip
                                content="仅影响新订单的比例定金拆分：尾款向上取整，定金允许保留小数。"
                                placement="top"
                                :popper-style="labelTipStyle"
                            >
                                <el-icon class="label-tip-icon"><QuestionFilled /></el-icon>
                            </el-tooltip>
                        </span>
                    </template>
                    <el-switch v-model="formData.deposit_rounding_enabled" :active-value="1" :inactive-value="0" />
                </el-form-item>
                <el-form-item
                    v-if="formData.enable_deposit_mode === 1 && formData.deposit_type === 'ratio' && formData.deposit_rounding_enabled === 1"
                >
                    <template #label>
                        <span class="form-label">
                            凑整单位
                            <el-tooltip
                                content="尾款按所选单位向上取整，例如 673.45 凑到百位元为 700.00。"
                                placement="top"
                                :popper-style="labelTipStyle"
                            >
                                <el-icon class="label-tip-icon"><QuestionFilled /></el-icon>
                            </el-tooltip>
                        </span>
                    </template>
                    <el-radio-group v-model="formData.deposit_rounding_unit">
                        <el-radio :label="1">凑到个位元</el-radio>
                        <el-radio :label="10">凑到十位元</el-radio>
                        <el-radio :label="100">凑到百位元</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item label="定金说明">
                    <el-input
                        v-model="formData.deposit_remark"
                        type="textarea"
                        :rows="3"
                        placeholder="请输入定金/尾款说明，将展示在用户端支付说明中"
                    />
                </el-form-item>
                <el-form-item>
                    <template #label>
                        <span class="form-label">
                            用户端线下收款
                            <el-tooltip
                                content="开启后，用户进入待支付订单可看到联系顾问入口，由顾问线下收款后在后台确认。"
                                placement="top"
                                :popper-style="labelTipStyle"
                            >
                                <el-icon class="label-tip-icon"><QuestionFilled /></el-icon>
                            </el-tooltip>
                        </span>
                    </template>
                    <el-switch v-model="formData.offline_collection_enabled" :active-value="1" :inactive-value="0" />
                </el-form-item>
            </el-card>

            <el-card shadow="never" class="feature-switch__card !border-none">
                <div class="feature-switch__section-header">
                    <div class="feature-switch__section-title">订单超时策略</div>
                </div>

                <el-form-item label="待支付超时取消">
                    <el-switch v-model="formData.cancel_unpaid_orders" :active-value="1" :inactive-value="0" />
                </el-form-item>
                <el-form-item v-if="formData.cancel_unpaid_orders === 1" label="待支付超时">
                    <div class="feature-switch__unit-field">
                        <el-input-number
                            v-model="formData.cancel_unpaid_orders_times"
                            :min="1"
                            :precision="0"
                            controls-position="right"
                        />
                        <span class="feature-switch__unit">分钟</span>
                    </div>
                </el-form-item>
                <el-form-item label="服务确认超时">
                    <el-switch
                        v-model="formData.staff_confirm_timeout_enabled"
                        :active-value="1"
                        :inactive-value="0"
                    />
                </el-form-item>
                <el-form-item v-if="formData.staff_confirm_timeout_enabled === 1">
                    <template #label>
                        <span class="form-label">
                            超时处理方式
                            <el-tooltip
                                content="自动取消会终止整单；自动同意会确认剩余有效预约项，并将订单推进到待支付。"
                                placement="top"
                                :popper-style="labelTipStyle"
                            >
                                <el-icon class="label-tip-icon"><QuestionFilled /></el-icon>
                            </el-tooltip>
                        </span>
                    </template>
                    <el-radio-group v-model="formData.staff_confirm_timeout_action">
                        <el-radio label="cancel">自动取消</el-radio>
                        <el-radio label="auto_confirm">自动同意</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item v-if="formData.staff_confirm_timeout_enabled === 1" label="确认超时">
                    <div class="feature-switch__unit-field">
                        <el-input-number
                            v-model="formData.staff_confirm_timeout_minutes"
                            :min="1"
                            :precision="0"
                            controls-position="right"
                        />
                        <span class="feature-switch__unit">分钟</span>
                    </div>
                </el-form-item>
            </el-card>
        </el-form>

        <footer-btns v-perms="['setting.feature_switch/setConfig']">
            <el-button type="primary" @click="handleSubmit">保存</el-button>
        </footer-btns>
    </div>
</template>

<script lang="ts" setup name="featureSwitch">
import { QuestionFilled } from '@element-plus/icons-vue'

import { getFeatureSwitchConfig, setFeatureSwitchConfig } from '@/api/setting/featureSwitch'
import {
    getTransactionSettingsConfig,
    setTransactionSettingsConfig
} from '@/api/setting/transaction'
import { getMenuPath } from '@/router'

import {
    createDefaultRiskPermissions,
    defaultRiskAbilityOptions,
    normalizeRiskPermissions,
    type RiskAbilityOption,
    riskRankRows} from './shared'

// 规则说明较长，限制浮层宽度避免撑成一行超宽提示。
const labelTipStyle = { maxWidth: '320px', lineHeight: '1.6' }

const formData = reactive({
    comment_review_enabled: 0,
    wechat_text_check_enabled: 1,
    wechat_text_check_profile_prob: 80,
    wechat_text_check_comment_prob: 70,
    wechat_text_check_review_as_hit: 1,
    risk_rank_ability_options: defaultRiskAbilityOptions,
    risk_rank_default_permissions: createDefaultRiskPermissions(),
    risk_rank_permissions: createDefaultRiskPermissions(),
    mini_program_review_mode: 0,
    staff_center: 1,
    staff_center_write: 1,
    staff_admin: 1,
    staff_tag_review_enabled: 0,
    admin_dashboard: 1,
    order_complete_by_user: 0,
    order_complete_by_staff: 0,
    enable_deposit_mode: 0,
    deposit_type: 'ratio',
    deposit_value: 30,
    deposit_remark: '',
    deposit_rounding_enabled: 0,
    deposit_rounding_unit: 1,
    offline_collection_enabled: 1,
    cancel_unpaid_orders: 1,
    cancel_unpaid_orders_times: 30,
    staff_confirm_timeout_enabled: 0,
    staff_confirm_timeout_action: 'cancel',
    staff_confirm_timeout_minutes: 60,
    verification_orders: 1,
    verification_orders_times: 24
})

const riskAbilityOptions = computed<RiskAbilityOption[]>(() => {
    const remoteOptions = Array.isArray(formData.risk_rank_ability_options)
        ? formData.risk_rank_ability_options.filter((item) => item && typeof item.code === 'string' && typeof item.name === 'string')
        : []
    return remoteOptions.length > 0 ? remoteOptions : defaultRiskAbilityOptions
})

const restoreDefaultRiskPermissions = () => {
    formData.risk_rank_permissions = normalizeRiskPermissions(formData.risk_rank_default_permissions)
}

// 业务域拆分跳转：menuKey 来自菜单清单，查不到（未注册菜单或无权限）返回空串则不展示按钮。
const router = useRouter()

const tradeRulesPath = computed(() => getMenuPath('page.order.trade_rules'))
const contentSecurityPath = computed(() => getMenuPath('page.review.content_security'))
const staffGovernancePath = computed(() => getMenuPath('page.staff.governance'))
const opsHubPath = computed(() => getMenuPath('page.setting.system.ops_hub'))

const goSplitPage = (path: string) => {
    if (!path) {
        return
    }
    router.push(path)
}

const getData = async () => {
    const [featureData, transactionData] = await Promise.all([
        getFeatureSwitchConfig(),
        getTransactionSettingsConfig()
    ])
    Object.assign(formData, featureData || {}, transactionData || {})
    formData.risk_rank_ability_options = Array.isArray(featureData?.risk_rank_ability_options)
        ? featureData.risk_rank_ability_options
        : defaultRiskAbilityOptions
    formData.risk_rank_default_permissions = normalizeRiskPermissions(featureData?.risk_rank_default_permissions)
    formData.risk_rank_permissions = normalizeRiskPermissions(featureData?.risk_rank_permissions)
}

const handleSubmit = async () => {
    await Promise.all([
        setFeatureSwitchConfig({
            comment_review_enabled: formData.comment_review_enabled,
            wechat_text_check_enabled: formData.wechat_text_check_enabled,
            wechat_text_check_profile_prob: formData.wechat_text_check_profile_prob,
            wechat_text_check_comment_prob: formData.wechat_text_check_comment_prob,
            wechat_text_check_review_as_hit: formData.wechat_text_check_review_as_hit,
            risk_rank_permissions: normalizeRiskPermissions(formData.risk_rank_permissions),
            mini_program_review_mode: formData.mini_program_review_mode,
            staff_center: formData.staff_center,
            staff_center_write: formData.staff_center_write,
            staff_admin: formData.staff_admin,
            staff_tag_review_enabled: formData.staff_tag_review_enabled,
            admin_dashboard: formData.admin_dashboard,
            order_complete_by_user: formData.order_complete_by_user,
            order_complete_by_staff: formData.order_complete_by_staff,
            enable_deposit_mode: formData.enable_deposit_mode,
            deposit_type: formData.deposit_type,
            deposit_value: formData.deposit_value,
            deposit_remark: formData.deposit_remark,
            deposit_rounding_enabled: formData.deposit_rounding_enabled,
            deposit_rounding_unit: formData.deposit_rounding_unit,
            offline_collection_enabled: formData.offline_collection_enabled
        }),
        setTransactionSettingsConfig({
            cancel_unpaid_orders: formData.cancel_unpaid_orders,
            cancel_unpaid_orders_times: formData.cancel_unpaid_orders_times,
            staff_confirm_timeout_enabled: formData.staff_confirm_timeout_enabled,
            staff_confirm_timeout_action: formData.staff_confirm_timeout_action,
            staff_confirm_timeout_minutes: formData.staff_confirm_timeout_minutes,
            verification_orders: formData.verification_orders,
            verification_orders_times: formData.verification_orders_times
        })
    ])
    getData()
}

getData()
</script>

<style lang="scss" scoped>
.feature-switch {
    &__split-bar {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px 16px;
        padding: 0 2px;
    }

    &__split-title {
        font-size: 14px;
        font-weight: 600;
        color: var(--el-text-color-primary);
    }

    &__split-links {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 4px 12px;
    }

    &__form {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: 16px;
    }

    &__card {
        :deep(.el-card__body) {
            padding: 24px;
        }
    }

    &__section-header {
        margin-bottom: 16px;

        &--with-action {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
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

    &__risk-table {
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

    &__risk-note {
        margin-top: 12px;
        font-size: 12px;
        line-height: 1.6;
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

    :deep(.el-form-item) {
        margin-bottom: 20px;
    }

    :deep(.el-form-item:last-child) {
        margin-bottom: 0;
    }

    :deep(.el-textarea) {
        width: min(100%, 640px);
    }

    :deep(.el-input-number) {
        width: 100%;
    }
}

@media (max-width: 768px) {
    .feature-switch {
        &__card :deep(.el-card__body) {
            padding: 18px;
        }

        &__section-header--with-action {
            flex-direction: column;
            align-items: flex-start;
        }

        :deep(.el-form) {
            --el-form-label-width: 110px;
        }
    }
}
</style>
