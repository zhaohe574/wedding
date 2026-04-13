<template>
    <div class="wecom-setting-page">
        <el-card shadow="never" class="!border-none">
            <div class="font-medium mb-7">企微通知设置</div>
            <el-form :model="configForm" label-width="140px">
                <el-form-item label="启用企微内部通知">
                    <div class="flex items-center gap-3">
                        <el-switch v-model="configForm.wecom_enabled" :active-value="1" :inactive-value="0" />
                        <span class="text-gray-500 text-xs">开启后，咨询分配和订单内部提醒会尝试发送企业微信消息。</span>
                    </div>
                </el-form-item>
                <el-form-item label="Corp ID">
                    <div class="w-[420px] flex flex-col gap-2">
                        <el-input v-model="configForm.wecom_corp_id" placeholder="请输入企业微信 Corp ID" />
                        <span class="text-gray-500 text-xs">来自企业微信管理后台，不填写则不会实际发送企微内部消息。</span>
                    </div>
                </el-form-item>
                <el-form-item label="Secret">
                    <div class="w-[420px] flex flex-col gap-2">
                        <el-input
                            v-model="configForm.wecom_secret"
                            type="password"
                            show-password
                            placeholder="请输入企业微信应用 Secret"
                        />
                        <span class="text-gray-500 text-xs">请填写应用 Secret，并确保仅由具备权限的管理员维护。</span>
                    </div>
                </el-form-item>
                <el-form-item label="Agent ID">
                    <div class="w-[320px] flex flex-col gap-2">
                        <el-input-number v-model="configForm.wecom_agent_id" :min="0" controls-position="right" />
                        <span class="text-gray-500 text-xs">对应企业微信应用 AgentId。</span>
                    </div>
                </el-form-item>
            </el-form>
        </el-card>

        <el-card shadow="never" class="!border-none mt-4">
            <div class="wecom-setting-page__header">
                <div>
                    <div class="font-medium">顾问企微成员维护</div>
                    <div class="text-gray-500 text-xs mt-1">咨询自动分配后，系统会按这里维护的企微成员 ID 给顾问发送内部提醒。</div>
                </div>
                <div class="wecom-setting-page__toolbar">
                    <el-input
                        v-model="searchForm.keyword"
                        clearable
                        placeholder="搜索顾问姓名/手机号/企微成员ID"
                        style="width: 260px"
                        @keyup.enter="fetchRecipients"
                        @clear="fetchRecipients"
                    />
                    <el-select v-model="searchForm.status" clearable placeholder="顾问状态" style="width: 140px" @change="fetchRecipients">
                        <el-option label="正常" :value="1" />
                        <el-option label="离职" :value="0" />
                        <el-option label="休假" :value="2" />
                    </el-select>
                    <el-button @click="fetchRecipients">查询</el-button>
                </div>
            </div>

            <el-table v-loading="loading" :data="recipientList" border class="mt-4">
                <el-table-column prop="advisor_name" label="顾问" min-width="140" />
                <el-table-column label="状态" width="100" align="center">
                    <template #default="{ row }">
                        <el-tag :type="getAdvisorStatusType(row.status)">{{ row.status_desc }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="联系方式" min-width="220">
                    <template #default="{ row }">
                        <div class="wecom-setting-page__contact-cell">
                            <div>手机：{{ row.mobile || '-' }}</div>
                            <div>微信：{{ row.wechat || '-' }}</div>
                            <div>联系链接：{{ row.contact_link || '-' }}</div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="负责区域 / 专长" min-width="220">
                    <template #default="{ row }">
                        <div class="wecom-setting-page__contact-cell">
                            <div>区域：{{ row.areas_text || '-' }}</div>
                            <div>专长：{{ row.specialties_text || '-' }}</div>
                            <div>负载：{{ row.load_text }}</div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="企微成员ID" min-width="240">
                    <template #default="{ row }">
                        <el-input v-model="row.wecom_userid" maxlength="64" placeholder="请输入企业微信成员ID" />
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="120" fixed="right" align="center">
                    <template #default="{ row }">
                        <el-button link type="primary" :loading="savingId === row.id" @click="handleSaveAdvisor(row)">保存</el-button>
                    </template>
                </el-table-column>
            </el-table>
        </el-card>

        <footer-btns>
            <el-button type="primary" :loading="configSubmitting" @click="handleSaveConfig">保存企微配置</el-button>
        </footer-btns>
    </div>
</template>

<script lang="ts" setup name="settingWecom">
import { reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import {
    getWecomConfig,
    getWecomRecipients,
    setWecomConfig,
    updateWecomAdvisor
} from '@/api/setting/wecom'

const loading = ref(false)
const configSubmitting = ref(false)
const savingId = ref<number>(0)
const recipientList = ref<any[]>([])

const searchForm = reactive({
    keyword: '',
    status: '' as '' | number
})

const configForm = reactive({
    wecom_enabled: 0,
    wecom_corp_id: '',
    wecom_secret: '',
    wecom_agent_id: 0
})

const getAdvisorStatusType = (status: number): 'success' | 'info' | 'warning' => {
    const map: Record<number, 'success' | 'info' | 'warning'> = {
        1: 'success',
        0: 'info',
        2: 'warning'
    }
    return map[Number(status || 0)] || 'info'
}

const fetchConfig = async () => {
    const data = await getWecomConfig()
    configForm.wecom_enabled = Number(data?.wecom_enabled || 0)
    configForm.wecom_corp_id = String(data?.wecom_corp_id || '')
    configForm.wecom_secret = String(data?.wecom_secret || '')
    configForm.wecom_agent_id = Number(data?.wecom_agent_id || 0)
}

const fetchRecipients = async () => {
    loading.value = true
    try {
        const data = await getWecomRecipients({
            keyword: searchForm.keyword,
            status: searchForm.status
        })
        recipientList.value = Array.isArray(data) ? data : []
    } finally {
        loading.value = false
    }
}

const handleSaveConfig = async () => {
    configSubmitting.value = true
    try {
        await setWecomConfig({
            wecom_enabled: configForm.wecom_enabled,
            wecom_corp_id: configForm.wecom_corp_id.trim(),
            wecom_secret: configForm.wecom_secret.trim(),
            wecom_agent_id: Number(configForm.wecom_agent_id || 0)
        })
        ElMessage.success('企微配置已保存')
        await fetchConfig()
    } finally {
        configSubmitting.value = false
    }
}

const handleSaveAdvisor = async (row: any) => {
    savingId.value = Number(row.id || 0)
    try {
        await updateWecomAdvisor({
            id: Number(row.id || 0),
            wecom_userid: String(row.wecom_userid || '').trim()
        })
        ElMessage.success('顾问企微成员ID已保存')
        await fetchRecipients()
    } finally {
        savingId.value = 0
    }
}

const bootstrap = async () => {
    await Promise.all([fetchConfig(), fetchRecipients()])
}

bootstrap()
</script>

<style lang="scss" scoped>
.wecom-setting-page {
    &__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
    }

    &__toolbar {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    &__contact-cell {
        display: flex;
        flex-direction: column;
        gap: 6px;
        color: var(--el-text-color-regular);
        line-height: 1.5;
    }
}
</style>
