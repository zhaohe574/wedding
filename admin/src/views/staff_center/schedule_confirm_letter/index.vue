<template>
    <admin-page-shell class="staff-schedule-confirm-letter" title="档期确认函">
        <el-card class="!border-none" shadow="never">
            <div class="page-head">
                <div>
                    <div class="page-head__title">档期确认函设计</div>
                    <div class="page-head__desc">按模板版本管理朋友圈海报，生成订单海报前可选择具体模板。</div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <el-button :loading="loading" @click="loadConfig(form.config_id)">刷新</el-button>
                    <el-button @click="handleCreateTemplate">新增模板</el-button>
                    <el-button :disabled="!form.config_id" @click="handleCopyTemplate">复制当前</el-button>
                    <el-button type="primary" :loading="saving" @click="handleSave">保存设计</el-button>
                </div>
            </div>
        </el-card>

        <div class="template-workspace mt-4">
            <el-card class="template-list-card !border-none" shadow="never">
                <div class="template-list-card__head">
                    <div>
                        <div class="template-list-card__title">模板版本</div>
                        <div class="template-list-card__meta">{{ activeVersions.length }} 个启用</div>
                    </div>
                    <el-switch
                        v-model="showDisabled"
                        size="small"
                        active-text="含停用"
                        @change="loadConfig(form.config_id)"
                    />
                </div>
                <div class="template-list">
                    <button
                        v-for="item in visibleVersions"
                        :key="item.config_id"
                        type="button"
                        :class="[
                            'template-list__item',
                            {
                                'template-list__item--active': Number(item.config_id) === Number(form.config_id),
                                'template-list__item--disabled': Number(item.status) !== 1,
                            },
                        ]"
                        @click="loadConfig(Number(item.config_id))"
                    >
                        <span class="template-list__main">
                            <span class="template-list__name">{{ item.template_name || '未命名模板' }}</span>
                            <span class="template-list__desc">
                                模板 v{{ item.template_version || 1 }} · 排序 {{ item.sort || 0 }}
                            </span>
                        </span>
                        <span class="template-list__tags">
                            <el-tag v-if="Number(item.is_default) === 1" size="small" type="warning">默认</el-tag>
                            <el-tag v-if="Number(item.status) !== 1" size="small" type="info">停用</el-tag>
                        </span>
                    </button>
                </div>
            </el-card>

            <el-card class="template-designer-card !border-none" shadow="never">
                <div class="template-meta">
                    <el-form class="template-meta__form" label-width="76px">
                        <el-form-item label="模板名称">
                            <el-input v-model="form.template_name" maxlength="40" placeholder="请输入模板名称" />
                        </el-form-item>
                        <el-form-item label="排序">
                            <el-input-number v-model="form.sort" :min="0" :max="9999" />
                        </el-form-item>
                    </el-form>
                    <div class="template-meta__actions">
                        <el-button :disabled="!form.config_id || Number(form.is_default) === 1" @click="handleSetDefault">
                            设为默认
                        </el-button>
                        <el-button
                            type="danger"
                            plain
                            :disabled="!form.config_id || activeVersions.length <= 1"
                            @click="handleDisableTemplate"
                        >
                            停用模板
                        </el-button>
                    </div>
                </div>
                <schedule-confirm-letter-designer
                    v-model="form.design_config"
                    :preview-snapshot="previewSnapshot"
                />
            </el-card>
        </div>
    </admin-page-shell>
</template>

<script setup lang="ts" name="staffScheduleConfirmLetter">
import { computed, onMounted, reactive, ref } from 'vue'
import ScheduleConfirmLetterDesigner from '@/components/staff/schedule-confirm-letter-designer.vue'
import feedback from '@/utils/feedback'
import {
    myScheduleConfirmLetterConfig,
    myScheduleConfirmLetterCopy,
    myScheduleConfirmLetterDisable,
    myScheduleConfirmLetterPreview,
    myScheduleConfirmLetterSave,
    myScheduleConfirmLetterSetDefault,
} from '@/api/staff-center'

const loading = ref(false)
const saving = ref(false)
const showDisabled = ref(true)
const versions = ref<any[]>([])
const previewSnapshot = ref<any>({
    service_date_label: '2026年08月18日',
    customer_alias: '张姓新人',
    service_name: '婚礼跟拍',
    city_label: '杭州 西湖区',
    staff_name: '服务人员',
    variables: {
        service_date_label: '2026年08月18日',
        customer_alias: '张姓新人',
        service_name: '婚礼跟拍',
        city_label: '杭州 西湖区',
        staff_name: '服务人员',
    },
})

const form = reactive<any>({
    config_id: 0,
    template_name: '默认海报',
    template_version: 1,
    is_default: 1,
    status: 1,
    sort: 0,
    design_version: 'staff-schedule-designer-v2',
    design_config: {
        canvas: { width: 1080, height: 1920 },
        background: { type: 'color', color: '#191713', image: '', opacity: 1 },
        layers: [],
    },
})

const visibleVersions = computed(() => {
    return versions.value.filter((item) => showDisabled.value || Number(item.status) === 1)
})

const activeVersions = computed(() => {
    return versions.value.filter((item) => Number(item.status) === 1)
})

const assignConfig = (data: any) => {
    form.config_id = Number(data?.config_id || 0)
    form.template_name = data?.template_name || '默认海报'
    form.template_version = Number(data?.template_version || 1)
    form.is_default = Number(data?.is_default ?? 1)
    form.status = Number(data?.status ?? 1)
    form.sort = Number(data?.sort || 0)
    form.design_version = data?.design_version || 'staff-schedule-designer-v2'
    form.design_config = data?.design_config || form.design_config
    versions.value = Array.isArray(data?.versions) ? data.versions : versions.value
}

const buildPayload = () => ({
    config_id: form.config_id,
    template_name: form.template_name,
    sort: form.sort,
    is_default: form.is_default,
    design_version: form.design_version,
    design_config: form.design_config,
})

const loadConfig = async (configId = Number(form.config_id || 0)) => {
    loading.value = true
    try {
        const data = await myScheduleConfirmLetterConfig({
            config_id: configId,
            include_disabled: showDisabled.value ? 1 : 0,
        })
        assignConfig(data || {})
        await refreshPreview()
    } finally {
        loading.value = false
    }
}

const refreshPreview = async () => {
    const data = await myScheduleConfirmLetterPreview(buildPayload())
    previewSnapshot.value = data?.preview?.rendered_snapshot || previewSnapshot.value
}

const handleSave = async () => {
    saving.value = true
    try {
        const data = await myScheduleConfirmLetterSave(buildPayload())
        assignConfig(data || {})
        await refreshPreview()
        feedback.msgSuccess('保存成功')
    } finally {
        saving.value = false
    }
}

const handleCreateTemplate = async () => {
    saving.value = true
    try {
        const data = await myScheduleConfirmLetterSave({
            config_id: 0,
            template_name: '新海报模板',
            design_version: 'staff-schedule-designer-v2',
        })
        assignConfig(data || {})
        await refreshPreview()
        feedback.msgSuccess('模板已创建')
    } finally {
        saving.value = false
    }
}

const handleCopyTemplate = async () => {
    if (!form.config_id) return
    const { value } = await feedback.prompt('请输入新模板名称', '复制模板', {
        inputValue: `${form.template_name || '海报模板'} 副本`,
        inputValidator: (val: string) => Boolean(String(val || '').trim()),
        inputErrorMessage: '请输入模板名称',
    })
    const data = await myScheduleConfirmLetterCopy({
        config_id: form.config_id,
        template_name: value,
    })
    assignConfig(data || {})
    await refreshPreview()
    feedback.msgSuccess('模板已复制')
}

const handleSetDefault = async () => {
    if (!form.config_id) return
    const data = await myScheduleConfirmLetterSetDefault({ config_id: form.config_id })
    assignConfig(data || {})
    feedback.msgSuccess('已设为默认模板')
}

const handleDisableTemplate = async () => {
    if (!form.config_id) return
    await feedback.confirm(`确定停用「${form.template_name || '当前模板'}」吗？`)
    const data = await myScheduleConfirmLetterDisable({ config_id: form.config_id })
    assignConfig(data || {})
    await refreshPreview()
    feedback.msgSuccess('模板已停用')
}

onMounted(loadConfig)
</script>

<style scoped lang="scss">
.page-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;

    &__title {
        font-size: 20px;
        font-weight: 600;
        color: #1f2933;
    }

    &__desc {
        margin-top: 6px;
        color: #7b8794;
        font-size: 13px;
    }
}

.template-workspace {
    display: grid;
    grid-template-columns: 280px minmax(0, 1fr);
    gap: 16px;
    align-items: start;
}

.template-list-card {
    position: sticky;
    top: 76px;

    &__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
    }

    &__title {
        font-size: 16px;
        font-weight: 600;
        color: #1f2933;
    }

    &__meta {
        margin-top: 4px;
        font-size: 12px;
        color: #7b8794;
    }
}

.template-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.template-list__item {
    width: 100%;
    min-height: 74px;
    padding: 12px;
    border: 1px solid #e8ecf3;
    border-radius: 8px;
    background: #fff;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
    text-align: left;
    cursor: pointer;
}

.template-list__item--active {
    border-color: var(--el-color-primary);
    background: #f6f8ff;
}

.template-list__item--disabled {
    opacity: 0.62;
}

.template-list__main {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.template-list__name {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 14px;
    font-weight: 600;
    color: #1f2933;
}

.template-list__desc {
    font-size: 12px;
    color: #7b8794;
}

.template-list__tags {
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    gap: 6px;
    align-items: flex-end;
}

.template-designer-card {
    min-width: 0;
}

.template-meta {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 16px;
}

.template-meta__form {
    display: grid;
    grid-template-columns: minmax(280px, 420px) 180px;
    gap: 12px;
    align-items: start;

    :deep(.el-form-item) {
        margin-bottom: 0;
    }
}

.template-meta__actions {
    flex-shrink: 0;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

@media (max-width: 1180px) {
    .template-workspace {
        grid-template-columns: minmax(0, 1fr);
    }

    .template-list-card {
        position: static;
    }

    .template-meta {
        flex-direction: column;
    }

    .template-meta__form {
        width: 100%;
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
