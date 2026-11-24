<template>
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-4">
        <el-card
            v-for="item in agreements"
            :key="item.key"
            class="!border-none"
            shadow="never"
        >
            <template #header>
                <span class="font-medium">{{ item.name }}</span>
                <span class="ml-2 text-tx-secondary text-xs">{{ item.desc }}</span>
            </template>
            <el-form :model="formData" label-width="80px">
                <el-form-item label="协议名称">
                    <el-input
                        v-model="formData[item.titleField]"
                        :placeholder="item.placeholder"
                        clearable
                    />
                </el-form-item>
            </el-form>

            <editor class="mb-4" v-model="formData[item.contentField]" height="460"></editor>
        </el-card>
    </div>
    <footer-btns v-perms="['setting.web.web_setting/setAgreement']">
        <el-button type="primary" :loading="saving" @click="handleProtocolEdit">保存</el-button>
    </footer-btns>
</template>

<script setup lang="ts" name="webProtocol">
import { getProtocol, setProtocol } from '@/api/setting/website'

interface formDataObj {
    service_title: string
    service_content: string
    privacy_title: string
    privacy_content: string
    booking_title: string
    booking_content: string
    aftersale_title: string
    aftersale_content: string
}

/** 4 份协议共用一套卡片渲染，字段与文案在这里集中声明 */
const agreements: Array<{
    key: 'service' | 'privacy' | 'booking' | 'aftersale'
    name: string
    desc: string
    placeholder: string
    titleField: keyof formDataObj
    contentField: keyof formDataObj
}> = [
    {
        key: 'service',
        name: '服务协议',
        desc: '登录注册与平台通用服务条款',
        placeholder: '请输入协议名称，默认：服务协议',
        titleField: 'service_title',
        contentField: 'service_content'
    },
    {
        key: 'privacy',
        name: '隐私协议',
        desc: '个人信息收集与使用说明',
        placeholder: '请输入协议名称，默认：隐私政策',
        titleField: 'privacy_title',
        contentField: 'privacy_content'
    },
    {
        key: 'booking',
        name: '预约服务协议',
        desc: '下单确认页《预约服务协议》入口',
        placeholder: '请输入协议名称，默认：预约服务协议',
        titleField: 'booking_title',
        contentField: 'booking_content'
    },
    {
        key: 'aftersale',
        name: '售后服务规则',
        desc: '下单确认页与售后中心《售后服务规则》入口',
        placeholder: '请输入协议名称，默认：售后服务规则',
        titleField: 'aftersale_title',
        contentField: 'aftersale_content'
    }
]

const formData = ref<formDataObj>({
    service_title: '',
    service_content: '',
    privacy_title: '',
    privacy_content: '',
    booking_title: '',
    booking_content: '',
    aftersale_title: '',
    aftersale_content: ''
})
const saving = ref(false)

const protocolGet = async () => {
    const data = await getProtocol()
    // 与空串默认值合并，避免后端返回 null/undefined 时表单项异常
    formData.value = { ...formData.value, ...data } as formDataObj
}

const handleProtocolEdit = async (): Promise<void> => {
    if (saving.value) return
    saving.value = true
    try {
        await setProtocol({ ...formData.value })
        protocolGet()
    } finally {
        saving.value = false
    }
}
protocolGet()
</script>
