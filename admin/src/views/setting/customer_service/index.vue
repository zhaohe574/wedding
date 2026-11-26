<template>
    <admin-form-page title="客服与咨询">
        <el-card class="!border-none" shadow="never" v-loading="loading">
            <el-form ref="formRef" :model="form" label-width="120px" class="max-w-[640px]">
                <el-form-item label="客服名称" prop="name">
                    <el-input v-model="form.name" placeholder="如：统一客服（留空则用默认值）" maxlength="30" show-word-limit />
                </el-form-item>
                <el-form-item label="客服角色" prop="role">
                    <el-input v-model="form.role" placeholder="如：婚礼顾问（留空则用默认值）" maxlength="30" show-word-limit />
                </el-form-item>
                <el-form-item label="服务时间" prop="service_time">
                    <el-input v-model="form.service_time" placeholder="如：工作日 09:00 - 18:00" maxlength="60" />
                </el-form-item>
                <el-form-item label="联系手机号" prop="mobile">
                    <el-input v-model="form.mobile" placeholder="客户点「拨打电话」时使用" maxlength="20" />
                </el-form-item>
                <el-form-item label="提示文案" prop="tips">
                    <el-input
                        v-model="form.tips"
                        type="textarea"
                        :rows="3"
                        maxlength="200"
                        show-word-limit
                        placeholder="如：进入微信客服后可继续沟通"
                    />
                </el-form-item>

            </el-form>
        </el-card>

        <template #actions>
            <el-button
                v-perms="['setting.customerService/setConfig']"
                type="primary"
                :loading="saving"
                @click="handleSave"
            >
                保存
            </el-button>
            <el-button @click="load">重置</el-button>
        </template>

        <el-card class="!border-none mt-4" shadow="never">
            <template #header>
                <span class="font-medium">展示逻辑</span>
            </template>
            <ul class="text-tx-secondary text-sm leading-7 pl-4 list-disc">
                <li>客户有专属顾问时，优先展示顾问的姓名、手机号与头像。</li>
                <li>没有专属顾问时，回落到本页配置的名称 / 角色 / 服务时间 / 提示文案。</li>
                <li>名称与角色留空时，小程序使用「统一客服」「婚礼顾问」作为默认值，不会显示空白。</li>
            </ul>
        </el-card>
    </admin-form-page>
</template>

<script lang="ts" setup name="settingCustomerService">
/**
 * 客服与咨询配置。
 *
 * 小程序 app/api/logic/CustomerServiceLogic::getFallbackContact 会读 customer_service 组的
 * name / role / service_time / tips / mobile。此前后台没有任何页面能写 name 与 role，
 * 运营只能改库。本页补齐这两个字段。
 */
import type { FormInstance } from 'element-plus'

import {
    customerServiceGetConfig,
    customerServiceSetConfig
} from '@/api/setting/customerService'
import feedback from '@/utils/feedback'

const loading = ref(false)
const saving = ref(false)
const formRef = shallowRef<FormInstance>()

const createDefault = () => ({
    name: '',
    role: '',
    service_time: '',
    tips: '',
    mobile: ''
})

const form = reactive(createDefault())

async function load() {
    loading.value = true
    try {
        const data = await customerServiceGetConfig()
        Object.assign(form, createDefault(), data || {})
    } finally {
        loading.value = false
    }
}

async function handleSave() {
    saving.value = true
    try {
        await customerServiceSetConfig({ ...form })
        feedback.msgSuccess('保存成功')
        load()
    } finally {
        saving.value = false
    }
}

onMounted(load)
</script>
