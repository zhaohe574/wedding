<template>
    <div class="feature-switch">
        <el-card shadow="never" class="!border-none">
            <div class="font-medium mb-7">功能开关</div>
            <el-form ref="formRef" :model="formData" label-width="140px">
                <el-form-item label="服务人员中心">
                    <div class="flex items-center gap-3">
                        <el-switch v-model="formData.staff_center" :active-value="1" :inactive-value="0" />
                        <span class="text-gray-500 text-xs">移动端服务人员入口与接口</span>
                    </div>
                </el-form-item>
                <el-form-item label="服务人员后台账号">
                    <div class="flex items-center gap-3">
                        <el-switch v-model="formData.staff_admin" :active-value="1" :inactive-value="0" />
                        <span class="text-gray-500 text-xs">创建服务人员时同步生成后台账号</span>
                    </div>
                </el-form-item>
                <el-form-item label="服务人员标签审核">
                    <div class="flex items-center gap-3">
                        <el-switch v-model="formData.staff_tag_review_enabled" :active-value="1" :inactive-value="0" />
                        <span class="text-gray-500 text-xs">开启后，服务人员自助修改标签需要管理员审核</span>
                    </div>
                </el-form-item>
                <el-form-item label="管理员看板入口">
                    <div class="flex items-center gap-3">
                        <el-switch v-model="formData.admin_dashboard" :active-value="1" :inactive-value="0" />
                        <span class="text-gray-500 text-xs">移动端管理员看板入口</span>
                    </div>
                </el-form-item>
                <el-form-item label="用户完成服务">
                    <div class="flex items-center gap-3">
                        <el-switch v-model="formData.order_complete_by_user" :active-value="1" :inactive-value="0" />
                        <span class="text-gray-500 text-xs">开启后，用户可在服务中订单中确认服务完成</span>
                    </div>
                </el-form-item>
                <el-form-item label="服务人员完成服务">
                    <div class="flex items-center gap-3">
                        <el-switch v-model="formData.order_complete_by_staff" :active-value="1" :inactive-value="0" />
                        <span class="text-gray-500 text-xs">开启后，服务人员可在服务中订单中完成服务并推进尾款流程</span>
                    </div>
                </el-form-item>
                <el-form-item label="看板可访问用户ID">
                    <div class="w-[420px] flex flex-col gap-2">
                        <el-input
                            v-model="formData.admin_dashboard_user_ids"
                            type="textarea"
                            :rows="3"
                            placeholder="请输入用户ID，多个用逗号分隔，例如：1,2,3"
                        />
                        <span class="text-gray-500 text-xs"
                            >仅白名单用户可直接访问管理员看板，未配置则默认无人可访问。</span
                        >
                    </div>
                </el-form-item>
                <el-form-item label="人员详情页风格">
                    <div class="flex flex-col gap-2">
                        <el-radio-group v-model="formData.staff_detail_style">
                            <el-radio label="classic">经典信息型</el-radio>
                            <el-radio label="immersive">沉浸视觉型</el-radio>
                            <el-radio label="conversion">高转化营销型</el-radio>
                        </el-radio-group>
                        <span class="text-gray-500 text-xs">控制移动端服务人员详情页展示风格</span>
                    </div>
                </el-form-item>
                <el-divider content-position="left">订单支付设置</el-divider>
                <el-form-item label="开启定金模式">
                    <div class="flex items-center gap-3">
                        <el-switch v-model="formData.enable_deposit_mode" :active-value="1" :inactive-value="0" />
                        <span class="text-gray-500 text-xs">开启后，新订单按定金+尾款方式支付</span>
                    </div>
                </el-form-item>
                <el-form-item v-if="formData.enable_deposit_mode === 1" label="定金计算方式">
                    <div class="flex flex-col gap-2">
                        <el-radio-group v-model="formData.deposit_type">
                            <el-radio label="ratio">按比例</el-radio>
                            <el-radio label="fixed">固定金额</el-radio>
                        </el-radio-group>
                        <span class="text-gray-500 text-xs">比例模式用于按订单应付金额拆分定金；固定金额模式用于统一收取定金。</span>
                    </div>
                </el-form-item>
                <el-form-item v-if="formData.enable_deposit_mode === 1" :label="formData.deposit_type === 'fixed' ? '定金金额' : '定金比例'">
                    <div class="w-[320px] flex flex-col gap-2">
                        <el-input-number
                            v-model="formData.deposit_value"
                            :min="0.01"
                            :max="formData.deposit_type === 'fixed' ? 999999 : 99.99"
                            :precision="2"
                            controls-position="right"
                        />
                        <span class="text-gray-500 text-xs">
                            {{ formData.deposit_type === 'fixed' ? '固定金额将直接作为新订单定金。' : '比例需大于0且小于100，按应付金额计算定金。' }}
                        </span>
                    </div>
                </el-form-item>
                <el-form-item label="定金说明">
                    <div class="w-[420px] flex flex-col gap-2">
                        <el-input
                            v-model="formData.deposit_remark"
                            type="textarea"
                            :rows="3"
                            placeholder="请输入定金/尾款说明，将展示在用户端支付说明中"
                        />
                    </div>
                </el-form-item>
                <el-divider content-position="left">订单超时设置</el-divider>
                <el-form-item label="待支付超时取消">
                    <div class="flex items-center gap-3">
                        <el-switch v-model="formData.cancel_unpaid_orders" :active-value="1" :inactive-value="0" />
                        <span class="text-gray-500 text-xs">开启后，待支付订单到期自动取消。</span>
                    </div>
                </el-form-item>
                <el-form-item v-if="formData.cancel_unpaid_orders === 1" label="待支付超时分钟">
                    <div class="w-[320px] flex flex-col gap-2">
                        <el-input-number
                            v-model="formData.cancel_unpaid_orders_times"
                            :min="1"
                            :precision="0"
                            controls-position="right"
                        />
                        <span class="text-gray-500 text-xs">从订单进入待支付开始计时，到时自动取消。</span>
                    </div>
                </el-form-item>
                <el-form-item label="服务确认超时">
                    <div class="flex items-center gap-3">
                        <el-switch
                            v-model="formData.staff_confirm_timeout_enabled"
                            :active-value="1"
                            :inactive-value="0"
                        />
                        <span class="text-gray-500 text-xs">开启后，待确认订单到期按下方策略自动处理。</span>
                    </div>
                </el-form-item>
                <el-form-item v-if="formData.staff_confirm_timeout_enabled === 1" label="超时处理方式">
                    <div class="flex flex-col gap-2">
                        <el-radio-group v-model="formData.staff_confirm_timeout_action">
                            <el-radio label="cancel">自动取消</el-radio>
                            <el-radio label="auto_confirm">自动同意</el-radio>
                        </el-radio-group>
                        <span class="text-gray-500 text-xs">
                            自动取消会终止整单；自动同意会确认剩余有效预约项，并将订单推进到待支付。
                        </span>
                    </div>
                </el-form-item>
                <el-form-item v-if="formData.staff_confirm_timeout_enabled === 1" label="确认超时分钟">
                    <div class="w-[320px] flex flex-col gap-2">
                        <el-input-number
                            v-model="formData.staff_confirm_timeout_minutes"
                            :min="1"
                            :precision="0"
                            controls-position="right"
                        />
                        <span class="text-gray-500 text-xs">从用户下单进入待确认开始计时。</span>
                    </div>
                </el-form-item>
            </el-form>
        </el-card>

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
    staff_center: 1,
    staff_admin: 1,
    staff_tag_review_enabled: 0,
    admin_dashboard: 1,
    order_complete_by_user: 0,
    order_complete_by_staff: 0,
    admin_dashboard_user_ids: '',
    staff_detail_style: 'classic',
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
            staff_center: formData.staff_center,
            staff_admin: formData.staff_admin,
            staff_tag_review_enabled: formData.staff_tag_review_enabled,
            admin_dashboard: formData.admin_dashboard,
            order_complete_by_user: formData.order_complete_by_user,
            order_complete_by_staff: formData.order_complete_by_staff,
            admin_dashboard_user_ids: formData.admin_dashboard_user_ids,
            staff_detail_style: formData.staff_detail_style,
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

<style lang="scss" scoped></style>
