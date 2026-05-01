<template>
    <div class="feature-switch">
        <div class="feature-switch__intro">
            <div>
                <div class="feature-switch__page-title">功能开关</div>
                <div class="feature-switch__page-desc">
                    按业务环节拆分配置项，方便快速定位人员入口、内容审核、看板访问、支付规则与订单超时策略。
                </div>
            </div>
            <div class="feature-switch__intro-tags">
                <span class="feature-switch__intro-tag">服务人员工作台</span>
                <span class="feature-switch__intro-tag">小程序送审模式</span>
                <span class="feature-switch__intro-tag">动态评论审核</span>
                <span class="feature-switch__intro-tag">管理员看板访问</span>
                <span class="feature-switch__intro-tag">服务完成确认</span>
                <span class="feature-switch__intro-tag">定金支付</span>
                <span class="feature-switch__intro-tag">超时自动处理</span>
            </div>
        </div>

        <el-form :model="formData" label-width="140px" class="feature-switch__form">
            <el-card shadow="never" class="feature-switch__card !border-none">
                <div class="feature-switch__section-header">
                    <div class="feature-switch__section-title">动态社区</div>
                    <div class="feature-switch__section-desc">控制小程序送审期间的新增内容入口，以及动态评论发布后的审核策略。</div>
                </div>

                <el-form-item label="小程序送审模式">
                    <div class="feature-switch__inline-control">
                        <el-switch v-model="formData.mini_program_review_mode" :active-value="1" :inactive-value="0" />
                        <span class="feature-switch__helper">开启后，小程序端隐藏并拦截发布动态、发表评论、发表评价、追评和晒单奖励申请；已有内容继续展示。</span>
                    </div>
                </el-form-item>
                <el-form-item label="评论审核">
                    <div class="feature-switch__inline-control">
                        <el-switch v-model="formData.comment_review_enabled" :active-value="1" :inactive-value="0" />
                        <span class="feature-switch__helper">开启后，用户发表的评论需要管理员审核通过后才能显示。</span>
                    </div>
                </el-form-item>
            </el-card>

            <el-card shadow="never" class="feature-switch__card !border-none">
                <div class="feature-switch__section-header">
                    <div class="feature-switch__section-title">服务人员工作台</div>
                    <div class="feature-switch__section-desc">控制服务人员中心入口、后台协同能力与资料标签审核流程。</div>
                </div>

                <el-form-item label="服务人员中心">
                    <div class="feature-switch__inline-control">
                        <el-switch v-model="formData.staff_center" :active-value="1" :inactive-value="0" />
                        <span class="feature-switch__helper">开启后，移动端服务人员入口与相关接口能力对外可用。</span>
                    </div>
                </el-form-item>
                <el-form-item label="服务人员后台账号">
                    <div class="feature-switch__inline-control">
                        <el-switch v-model="formData.staff_admin" :active-value="1" :inactive-value="0" />
                        <span class="feature-switch__helper">创建服务人员时同步生成后台账号，便于后台协同管理。</span>
                    </div>
                </el-form-item>
                <el-form-item label="服务人员标签审核">
                    <div class="feature-switch__inline-control">
                        <el-switch v-model="formData.staff_tag_review_enabled" :active-value="1" :inactive-value="0" />
                        <span class="feature-switch__helper">开启后，服务人员自助修改标签需经管理员审核后生效。</span>
                    </div>
                </el-form-item>
            </el-card>

            <el-card shadow="never" class="feature-switch__card !border-none">
                <div class="feature-switch__section-header">
                    <div class="feature-switch__section-title">管理员看板访问</div>
                    <div class="feature-switch__section-desc">统一管理移动端管理员看板入口与访问白名单范围。</div>
                </div>

                <el-form-item label="管理员看板入口">
                    <div class="feature-switch__inline-control">
                        <el-switch v-model="formData.admin_dashboard" :active-value="1" :inactive-value="0" />
                        <span class="feature-switch__helper">控制移动端管理员看板入口是否展示。</span>
                    </div>
                </el-form-item>
                <el-form-item label="看板可访问用户ID">
                    <div class="feature-switch__field feature-switch__field--wide">
                        <el-input
                            v-model="formData.admin_dashboard_user_ids"
                            type="textarea"
                            :rows="3"
                            placeholder="请输入用户ID，多个用逗号分隔，例如：1,2,3"
                        />
                        <span class="feature-switch__helper">仅白名单用户可直接访问管理员看板，未配置则默认无人可访问。</span>
                    </div>
                </el-form-item>
            </el-card>

            <el-card shadow="never" class="feature-switch__card !border-none">
                <div class="feature-switch__section-header">
                    <div class="feature-switch__section-title">服务完成确认</div>
                    <div class="feature-switch__section-desc">决定订单服务完成后由哪一侧发起确认，影响履约收尾与尾款推进节奏。</div>
                </div>

                <el-form-item label="用户完成服务">
                    <div class="feature-switch__inline-control">
                        <el-switch v-model="formData.order_complete_by_user" :active-value="1" :inactive-value="0" />
                        <span class="feature-switch__helper">开启后，用户可在服务中订单里主动确认服务完成。</span>
                    </div>
                </el-form-item>
                <el-form-item label="服务人员完成服务">
                    <div class="feature-switch__inline-control">
                        <el-switch v-model="formData.order_complete_by_staff" :active-value="1" :inactive-value="0" />
                        <span class="feature-switch__helper">开启后，服务人员可完成服务并推动订单进入尾款流程。</span>
                    </div>
                </el-form-item>
            </el-card>

            <el-card shadow="never" class="feature-switch__card !border-none">
                <div class="feature-switch__section-header">
                    <div class="feature-switch__section-title">定金支付规则</div>
                    <div class="feature-switch__section-desc">配置新订单是否按定金+尾款方式支付，以及定金的计算口径和展示说明。</div>
                </div>

                <el-form-item label="开启定金模式">
                    <div class="feature-switch__inline-control">
                        <el-switch v-model="formData.enable_deposit_mode" :active-value="1" :inactive-value="0" />
                        <span class="feature-switch__helper">开启后，新订单将按照定金加尾款的两段式支付方式处理。</span>
                    </div>
                </el-form-item>
                <el-form-item v-if="formData.enable_deposit_mode === 1" label="定金计算方式">
                    <div class="feature-switch__field">
                        <el-radio-group v-model="formData.deposit_type">
                            <el-radio label="ratio">按比例</el-radio>
                            <el-radio label="fixed">固定金额</el-radio>
                        </el-radio-group>
                        <span class="feature-switch__helper">比例模式按订单应付金额拆分定金；固定金额模式则统一收取固定定金。</span>
                    </div>
                </el-form-item>
                <el-form-item v-if="formData.enable_deposit_mode === 1" :label="formData.deposit_type === 'fixed' ? '定金金额' : '定金比例'">
                    <div class="feature-switch__field feature-switch__field--compact">
                        <el-input-number
                            v-model="formData.deposit_value"
                            :min="0.01"
                            :max="formData.deposit_type === 'fixed' ? 999999 : 99.99"
                            :precision="2"
                            controls-position="right"
                        />
                        <span class="feature-switch__helper">
                            {{ formData.deposit_type === 'fixed' ? '固定金额将直接作为新订单定金。' : '比例需大于0且小于100，系统会按应付金额计算定金。' }}
                        </span>
                    </div>
                </el-form-item>
                <el-form-item label="定金说明">
                    <div class="feature-switch__field feature-switch__field--wide">
                        <el-input
                            v-model="formData.deposit_remark"
                            type="textarea"
                            :rows="3"
                            placeholder="请输入定金/尾款说明，将展示在用户端支付说明中"
                        />
                    </div>
                </el-form-item>
            </el-card>

            <el-card shadow="never" class="feature-switch__card !border-none">
                <div class="feature-switch__section-header">
                    <div class="feature-switch__section-title">订单超时策略</div>
                    <div class="feature-switch__section-desc">统一管理待支付与待确认阶段的自动处理规则，减少人工跟进成本。</div>
                </div>

                <el-form-item label="待支付超时取消">
                    <div class="feature-switch__inline-control">
                        <el-switch v-model="formData.cancel_unpaid_orders" :active-value="1" :inactive-value="0" />
                        <span class="feature-switch__helper">开启后，待支付订单到期会自动取消。</span>
                    </div>
                </el-form-item>
                <el-form-item v-if="formData.cancel_unpaid_orders === 1" label="待支付超时分钟">
                    <div class="feature-switch__field feature-switch__field--compact">
                        <el-input-number
                            v-model="formData.cancel_unpaid_orders_times"
                            :min="1"
                            :precision="0"
                            controls-position="right"
                        />
                        <span class="feature-switch__helper">从订单进入待支付开始计时，到时自动取消。</span>
                    </div>
                </el-form-item>
                <el-form-item label="服务确认超时">
                    <div class="feature-switch__inline-control">
                        <el-switch
                            v-model="formData.staff_confirm_timeout_enabled"
                            :active-value="1"
                            :inactive-value="0"
                        />
                        <span class="feature-switch__helper">开启后，待确认订单会按照下方策略自动处理。</span>
                    </div>
                </el-form-item>
                <el-form-item v-if="formData.staff_confirm_timeout_enabled === 1" label="超时处理方式">
                    <div class="feature-switch__field">
                        <el-radio-group v-model="formData.staff_confirm_timeout_action">
                            <el-radio label="cancel">自动取消</el-radio>
                            <el-radio label="auto_confirm">自动同意</el-radio>
                        </el-radio-group>
                        <span class="feature-switch__helper">自动取消会终止整单；自动同意会确认剩余有效预约项，并将订单推进到待支付。</span>
                    </div>
                </el-form-item>
                <el-form-item v-if="formData.staff_confirm_timeout_enabled === 1" label="确认超时分钟">
                    <div class="feature-switch__field feature-switch__field--compact">
                        <el-input-number
                            v-model="formData.staff_confirm_timeout_minutes"
                            :min="1"
                            :precision="0"
                            controls-position="right"
                        />
                        <span class="feature-switch__helper">从用户下单进入待确认开始计时。</span>
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
import { getFeatureSwitchConfig, setFeatureSwitchConfig } from '@/api/setting/featureSwitch'
import {
    getTransactionSettingsConfig,
    setTransactionSettingsConfig
} from '@/api/setting/transaction'

const formData = reactive({
    comment_review_enabled: 0,
    mini_program_review_mode: 0,
    staff_center: 1,
    staff_admin: 1,
    staff_tag_review_enabled: 0,
    admin_dashboard: 1,
    order_complete_by_user: 0,
    order_complete_by_staff: 0,
    admin_dashboard_user_ids: '',
    enable_deposit_mode: 0,
    deposit_type: 'ratio',
    deposit_value: 30,
    deposit_remark: '',
    cancel_unpaid_orders: 1,
    cancel_unpaid_orders_times: 30,
    staff_confirm_timeout_enabled: 0,
    staff_confirm_timeout_action: 'cancel',
    staff_confirm_timeout_minutes: 60,
    verification_orders: 1,
    verification_orders_times: 24
})

const getData = async () => {
    const [featureData, transactionData] = await Promise.all([
        getFeatureSwitchConfig(),
        getTransactionSettingsConfig()
    ])
    Object.assign(formData, featureData || {}, transactionData || {})
}

const handleSubmit = async () => {
    await Promise.all([
        setFeatureSwitchConfig({
            comment_review_enabled: formData.comment_review_enabled,
            mini_program_review_mode: formData.mini_program_review_mode,
            staff_center: formData.staff_center,
            staff_admin: formData.staff_admin,
            staff_tag_review_enabled: formData.staff_tag_review_enabled,
            admin_dashboard: formData.admin_dashboard,
            order_complete_by_user: formData.order_complete_by_user,
            order_complete_by_staff: formData.order_complete_by_staff,
            admin_dashboard_user_ids: formData.admin_dashboard_user_ids,
            enable_deposit_mode: formData.enable_deposit_mode,
            deposit_type: formData.deposit_type,
            deposit_value: formData.deposit_value,
            deposit_remark: formData.deposit_remark
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
    &__intro {
        padding: 22px 24px;
        border-radius: 12px;
        background: linear-gradient(135deg, rgba(64, 158, 255, 0.1) 0%, rgba(64, 158, 255, 0.04) 100%);
        border: 1px solid rgba(64, 158, 255, 0.16);
        margin-bottom: 16px;
    }

    &__page-title {
        font-size: 18px;
        font-weight: 600;
        color: var(--el-text-color-primary);
    }

    &__page-desc {
        margin-top: 8px;
        line-height: 1.6;
        color: var(--el-text-color-regular);
    }

    &__intro-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 16px;
    }

    &__intro-tag {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.78);
        color: var(--el-color-primary);
        font-size: 12px;
        line-height: 1;
    }

    &__form {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

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
    .feature-switch {
        &__intro,
        &__card :deep(.el-card__body) {
            padding: 18px;
        }

        :deep(.el-form) {
            --el-form-label-width: 110px;
        }
    }
}
</style>
