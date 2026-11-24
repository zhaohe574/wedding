<template>
    <admin-form-page
        title="交易与履约规则"
        description="集中配置定金与尾款拆分、线下收款入口、服务完成确认方及订单超时自动处理规则"
    >
        <el-form
            ref="formRef"
            :model="formData"
            :rules="rules"
            label-width="150px"
            class="trade-rules"
        >
            <el-card shadow="never" class="trade-rules__card !border-none">
                <div class="trade-rules__section-header">
                    <div class="trade-rules__section-title">定金支付与线下收款</div>
                    <div class="trade-rules__section-desc">
                        配置新订单是否按定金+尾款方式支付，以及定金的计算口径、展示说明与线下收款入口。
                    </div>
                </div>

                <el-form-item label="开启定金模式">
                    <div class="trade-rules__inline-control">
                        <el-switch v-model="formData.enable_deposit_mode" :active-value="1" :inactive-value="0" />
                        <span class="trade-rules__helper">开启后，新订单将按照定金加尾款的两段式支付方式处理。</span>
                    </div>
                </el-form-item>
                <el-form-item v-if="formData.enable_deposit_mode === 1" label="定金计算方式">
                    <div class="trade-rules__field">
                        <el-radio-group v-model="formData.deposit_type">
                            <el-radio label="ratio">按比例</el-radio>
                            <el-radio label="fixed">固定金额</el-radio>
                        </el-radio-group>
                        <span class="trade-rules__helper">比例模式按订单应付金额拆分定金；固定金额模式则统一收取固定定金。</span>
                    </div>
                </el-form-item>
                <el-form-item
                    v-if="formData.enable_deposit_mode === 1"
                    prop="deposit_value"
                    :label="formData.deposit_type === 'fixed' ? '定金金额' : '定金比例'"
                >
                    <div class="trade-rules__field trade-rules__field--compact">
                        <el-input-number
                            v-model="formData.deposit_value"
                            :min="0.01"
                            :max="formData.deposit_type === 'fixed' ? 999999 : 99.99"
                            :precision="2"
                            controls-position="right"
                        />
                        <span class="trade-rules__helper">
                            {{ formData.deposit_type === 'fixed' ? '固定金额将直接作为新订单定金。' : '比例需大于0且小于100，系统会按应付金额计算定金。' }}
                        </span>
                    </div>
                </el-form-item>
                <el-form-item v-if="formData.enable_deposit_mode === 1 && formData.deposit_type === 'ratio'" label="尾款向上凑整">
                    <div class="trade-rules__inline-control">
                        <el-switch v-model="formData.deposit_rounding_enabled" :active-value="1" :inactive-value="0" />
                        <span class="trade-rules__helper">仅影响新订单的比例定金拆分，系统会将尾款向上取整，定金允许保留小数。</span>
                    </div>
                </el-form-item>
                <el-form-item
                    v-if="formData.enable_deposit_mode === 1 && formData.deposit_type === 'ratio' && formData.deposit_rounding_enabled === 1"
                    label="凑整单位"
                >
                    <div class="trade-rules__field">
                        <el-radio-group v-model="formData.deposit_rounding_unit">
                            <el-radio :label="1">凑到个位元</el-radio>
                            <el-radio :label="10">凑到十位元</el-radio>
                            <el-radio :label="100">凑到百位元</el-radio>
                        </el-radio-group>
                        <span class="trade-rules__helper">系统会将比例算出的尾款实际支付金额向上取整，例如 673.45 凑到百位元为 700.00。</span>
                    </div>
                </el-form-item>
                <el-form-item label="定金说明">
                    <div class="trade-rules__field trade-rules__field--wide">
                        <el-input
                            v-model="formData.deposit_remark"
                            type="textarea"
                            :rows="3"
                            placeholder="请输入定金/尾款说明，将展示在用户端支付说明中"
                        />
                    </div>
                </el-form-item>
                <el-form-item label="用户端线下收款">
                    <div class="trade-rules__inline-control">
                        <el-switch v-model="formData.offline_collection_enabled" :active-value="1" :inactive-value="0" />
                        <span class="trade-rules__helper">开启后，用户进入待支付订单可看到联系顾问入口，由顾问线下收款后在后台确认。</span>
                    </div>
                </el-form-item>
            </el-card>

            <el-card shadow="never" class="trade-rules__card !border-none">
                <div class="trade-rules__section-header">
                    <div class="trade-rules__section-title">服务完成确认</div>
                    <div class="trade-rules__section-desc">决定订单服务完成后由哪一侧发起确认，影响履约收尾与尾款推进节奏。</div>
                </div>

                <el-form-item label="用户完成服务">
                    <div class="trade-rules__inline-control">
                        <el-switch v-model="formData.order_complete_by_user" :active-value="1" :inactive-value="0" />
                        <span class="trade-rules__helper">开启后，用户可在服务中订单里主动确认服务完成。</span>
                    </div>
                </el-form-item>
                <el-form-item label="服务人员完成服务">
                    <div class="trade-rules__inline-control">
                        <el-switch v-model="formData.order_complete_by_staff" :active-value="1" :inactive-value="0" />
                        <span class="trade-rules__helper">开启后，服务人员可完成服务并推动订单进入尾款流程。</span>
                    </div>
                </el-form-item>
            </el-card>

            <el-card shadow="never" class="trade-rules__card !border-none">
                <div class="trade-rules__section-header">
                    <div class="trade-rules__section-title">订单超时自动处理</div>
                    <div class="trade-rules__section-desc">统一管理待支付与待确认阶段的自动处理规则，减少人工跟进成本。</div>
                </div>

                <el-form-item label="待支付超时取消">
                    <div class="trade-rules__inline-control">
                        <el-switch v-model="formData.cancel_unpaid_orders" :active-value="1" :inactive-value="0" />
                        <span class="trade-rules__helper">开启后，待支付订单到期会自动取消。</span>
                    </div>
                </el-form-item>
                <el-form-item v-if="formData.cancel_unpaid_orders === 1" label="待支付超时分钟">
                    <div class="trade-rules__field trade-rules__field--compact">
                        <el-input-number
                            v-model="formData.cancel_unpaid_orders_times"
                            :min="1"
                            :precision="0"
                            controls-position="right"
                        />
                        <span class="trade-rules__helper">从订单进入待支付开始计时，到时自动取消。</span>
                    </div>
                </el-form-item>
                <el-form-item label="服务确认超时">
                    <div class="trade-rules__inline-control">
                        <el-switch
                            v-model="formData.staff_confirm_timeout_enabled"
                            :active-value="1"
                            :inactive-value="0"
                        />
                        <span class="trade-rules__helper">开启后，待确认订单会按照下方策略自动处理。</span>
                    </div>
                </el-form-item>
                <el-form-item v-if="formData.staff_confirm_timeout_enabled === 1" label="超时处理方式">
                    <div class="trade-rules__field">
                        <el-radio-group v-model="formData.staff_confirm_timeout_action">
                            <el-radio label="cancel">自动取消</el-radio>
                            <el-radio label="auto_confirm">自动同意</el-radio>
                        </el-radio-group>
                        <span class="trade-rules__helper">自动取消会终止整单；自动同意会确认剩余有效预约项，并将订单推进到待支付。</span>
                    </div>
                </el-form-item>
                <el-form-item v-if="formData.staff_confirm_timeout_enabled === 1" label="确认超时分钟">
                    <div class="trade-rules__field trade-rules__field--compact">
                        <el-input-number
                            v-model="formData.staff_confirm_timeout_minutes"
                            :min="1"
                            :precision="0"
                            controls-position="right"
                        />
                        <span class="trade-rules__helper">从用户下单进入待确认开始计时。</span>
                    </div>
                </el-form-item>
            </el-card>
        </el-form>

        <template #actions>
            <el-button
                v-perms="['setting.feature_switch/setConfig']"
                type="primary"
                :loading="saving"
                @click="handleSave"
            >
                保存
            </el-button>
            <el-button @click="handleReset">重置</el-button>
        </template>
    </admin-form-page>
</template>

<script lang="ts" setup name="orderTradeRules">
/**
 * 交易与履约规则（批次 2 拆分自 setting/feature_switch）。
 *
 * 保存只局部提交本域字段：feature_switch 的 9 个支付/确认开关，
 * 以及 transaction_settings 的 5 个超时策略字段，
 * 其余域的配置不随本页保存被覆盖。
 */
import type { FormInstance, FormRules } from 'element-plus'

import { getFeatureSwitchConfig, setFeatureSwitchConfig } from '@/api/setting/featureSwitch'
import {
    getTransactionSettingsConfig,
    setTransactionSettingsConfig
} from '@/api/setting/transaction'
import feedback from '@/utils/feedback'

const formRef = shallowRef<FormInstance>()
const saving = ref(false)

const createDefault = () => ({
    enable_deposit_mode: 0,
    deposit_type: 'ratio',
    deposit_value: 30,
    deposit_remark: '',
    deposit_rounding_enabled: 0,
    deposit_rounding_unit: 1,
    offline_collection_enabled: 1,
    order_complete_by_user: 0,
    order_complete_by_staff: 0,
    cancel_unpaid_orders: 1,
    cancel_unpaid_orders_times: 30,
    staff_confirm_timeout_enabled: 0,
    staff_confirm_timeout_action: 'cancel',
    staff_confirm_timeout_minutes: 60
})

const formData = reactive(createDefault())

const validateDepositValue = (
    _rule: unknown,
    value: number | undefined,
    callback: (error?: string | Error) => void
) => {
    if (formData.enable_deposit_mode !== 1) {
        callback()
        return
    }
    const amount = Number(value ?? 0)
    if (formData.deposit_type === 'ratio' && (amount <= 0 || amount >= 100)) {
        callback(new Error('定金比例需大于 0 且小于 100'))
        return
    }
    if (formData.deposit_type === 'fixed' && amount <= 0) {
        callback(new Error('定金金额需大于 0'))
        return
    }
    callback()
}

const rules: FormRules = {
    deposit_value: [{ validator: validateDepositValue, trigger: 'blur' }]
}

const getData = async () => {
    const [featureData, transactionData] = await Promise.all([
        getFeatureSwitchConfig(),
        getTransactionSettingsConfig()
    ])
    Object.assign(formData, {
        enable_deposit_mode: Number(featureData?.enable_deposit_mode ?? 0),
        deposit_type: String(featureData?.deposit_type ?? 'ratio'),
        deposit_value: Number(featureData?.deposit_value ?? 30),
        deposit_remark: String(featureData?.deposit_remark ?? ''),
        deposit_rounding_enabled: Number(featureData?.deposit_rounding_enabled ?? 0),
        deposit_rounding_unit: Number(featureData?.deposit_rounding_unit ?? 1),
        offline_collection_enabled: Number(featureData?.offline_collection_enabled ?? 1),
        order_complete_by_user: Number(featureData?.order_complete_by_user ?? 0),
        order_complete_by_staff: Number(featureData?.order_complete_by_staff ?? 0),
        cancel_unpaid_orders: Number(transactionData?.cancel_unpaid_orders ?? 1),
        cancel_unpaid_orders_times: Number(transactionData?.cancel_unpaid_orders_times ?? 30),
        staff_confirm_timeout_enabled: Number(transactionData?.staff_confirm_timeout_enabled ?? 0),
        staff_confirm_timeout_action: String(transactionData?.staff_confirm_timeout_action ?? 'cancel'),
        staff_confirm_timeout_minutes: Number(transactionData?.staff_confirm_timeout_minutes ?? 60)
    })
    formRef.value?.clearValidate()
}

const handleReset = () => {
    getData()
}

const handleSave = async () => {
    await formRef.value?.validate()
    saving.value = true
    try {
        await Promise.all([
            setFeatureSwitchConfig({
                enable_deposit_mode: formData.enable_deposit_mode,
                deposit_type: formData.deposit_type,
                deposit_value: formData.deposit_value,
                deposit_remark: formData.deposit_remark,
                deposit_rounding_enabled: formData.deposit_rounding_enabled,
                deposit_rounding_unit: formData.deposit_rounding_unit,
                offline_collection_enabled: formData.offline_collection_enabled,
                order_complete_by_user: formData.order_complete_by_user,
                order_complete_by_staff: formData.order_complete_by_staff
            }),
            setTransactionSettingsConfig({
                cancel_unpaid_orders: formData.cancel_unpaid_orders,
                cancel_unpaid_orders_times: formData.cancel_unpaid_orders_times,
                staff_confirm_timeout_enabled: formData.staff_confirm_timeout_enabled,
                staff_confirm_timeout_action: formData.staff_confirm_timeout_action,
                staff_confirm_timeout_minutes: formData.staff_confirm_timeout_minutes
            })
        ])
        feedback.msgSuccess('保存成功')
        getData()
    } finally {
        saving.value = false
    }
}

onMounted(getData)
</script>

<style lang="scss" scoped>
.trade-rules {
    display: flex;
    flex-direction: column;
    gap: 16px;

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
        width: min(100%, 560px);

        &--wide {
            width: min(100%, 640px);
        }

        &--compact {
            width: min(100%, 360px);
        }
    }

    &__helper {
        font-size: 12px;
        line-height: 1.6;
        color: var(--el-text-color-secondary);
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

@media (max-width: 768px) {
    .trade-rules {
        --el-form-label-width: 110px;

        &__card :deep(.el-card__body) {
            padding: 18px;
        }
    }
}
</style>
