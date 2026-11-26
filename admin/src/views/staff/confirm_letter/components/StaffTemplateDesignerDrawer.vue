<template>
    <el-drawer
        v-model="visible"
        :title="drawerTitle"
        size="min(1260px, 94vw)"
        destroy-on-close
        @closed="handleClosed"
    >
        <div v-loading="loading" class="template-designer">
            <el-alert
                v-if="form.config_id && Number(form.status) !== 1"
                class="mb-3"
                type="warning"
                :closable="false"
                show-icon
            >
                <template #title>
                    <div class="disable-alert">
                        <span>当前模板已停用，服务人员无法使用它生成海报，历史出图不受影响。</span>
                        <el-button
                            v-perms="['ops.staff/edit']"
                            type="primary"
                            link
                            :loading="enabling"
                            @click="handleEnable"
                        >
                            重新启用
                        </el-button>
                    </div>
                </template>
            </el-alert>

            <div class="template-designer__layout">
                <aside class="template-version">
                    <div class="template-version__head">
                        <div>
                            <div class="template-version__title">模板版本</div>
                            <div class="template-version__desc">{{ activeVersionCount }} 个启用模板</div>
                        </div>
                        <el-switch
                            v-model="showDisabled"
                            size="small"
                            active-text="含停用"
                            @change="handleToggleDisabled"
                        />
                    </div>
                    <div class="template-version__list">
                        <button
                            v-for="item in visibleVersions"
                            :key="item.config_id"
                            type="button"
                            class="template-version__item"
                            :class="{
                                'is-active': Number(item.config_id) === Number(form.config_id),
                                'is-disabled': Number(item.status) !== 1
                            }"
                            @click="loadConfig(Number(item.config_id))"
                        >
                            <span class="template-version__main">
                                <span class="template-version__name">
                                    {{ item.template_name || '未命名模板' }}
                                </span>
                                <span class="template-version__meta">
                                    模板 v{{ item.template_version || 1 }} · 排序 {{ item.sort || 0 }}
                                </span>
                            </span>
                            <span class="template-version__tags">
                                <el-tag v-if="Number(item.is_default) === 1" size="small" type="warning">默认</el-tag>
                                <el-tag v-if="Number(item.status) !== 1" size="small" type="info">停用</el-tag>
                            </span>
                        </button>
                        <el-empty v-if="!visibleVersions.length" description="暂无模板" :image-size="64" />
                    </div>
                    <div class="template-version__actions">
                        <el-button
                            v-perms="['ops.staff/edit']"
                            class="w-full"
                            :loading="creating"
                            @click="handleCreate"
                        >
                            新增模板
                        </el-button>
                        <el-button
                            v-perms="['ops.staff/edit']"
                            class="w-full"
                            :disabled="!form.config_id"
                            :loading="copying"
                            @click="handleCopy"
                        >
                            复制当前
                        </el-button>
                    </div>
                </aside>

                <section class="template-main">
                    <div class="template-main__meta">
                        <el-form class="template-meta-form" label-width="76px">
                            <el-form-item label="模板名称">
                                <el-input
                                    v-model="form.template_name"
                                    maxlength="40"
                                    placeholder="请输入模板名称"
                                />
                            </el-form-item>
                            <el-form-item label="排序">
                                <el-input-number v-model="form.sort" :min="0" :max="9999" />
                            </el-form-item>
                        </el-form>
                        <div class="template-main__actions">
                            <el-button
                                v-perms="['ops.staff/edit']"
                                :disabled="!form.config_id || Number(form.is_default) === 1"
                                :loading="defaulting"
                                @click="handleSetDefault"
                            >
                                设为默认
                            </el-button>
                            <el-button
                                v-perms="['ops.staff/edit']"
                                type="danger"
                                plain
                                :disabled="!form.config_id || activeVersionCount <= 1"
                                :loading="disabling"
                                @click="handleDisable"
                            >
                                停用
                            </el-button>
                            <el-button
                                v-perms="['ops.staff/edit']"
                                type="primary"
                                :loading="saving"
                                @click="handleSave"
                            >
                                保存配置
                            </el-button>
                            <el-button :loading="previewing" @click="handleGeneratePreview">
                                生成并查看服务端真机预览
                            </el-button>
                        </div>
                    </div>

                    <schedule-confirm-letter-designer
                        v-model="form.design_config"
                        class="mt-3"
                        :preview-snapshot="previewSnapshot"
                    />
                </section>
            </div>
        </div>

        <el-dialog
            v-model="previewDialogVisible"
            title="服务端真机预览"
            width="520px"
            append-to-body
            destroy-on-close
        >
            <div v-loading="previewing" class="server-preview">
                <el-image
                    v-if="previewImage"
                    :src="previewImage"
                    fit="contain"
                    class="server-preview__image"
                    :preview-src-list="[previewImage]"
                    preview-teleported
                />
                <el-empty v-else description="暂无预览图，请稍后重试" :image-size="80" />
            </div>
            <template #footer>
                <el-button @click="previewDialogVisible = false">关闭</el-button>
                <el-button
                    v-if="previewImage"
                    type="primary"
                    @click="downloadPreview"
                >
                    下载预览
                </el-button>
            </template>
        </el-dialog>
    </el-drawer>
</template>

<script lang="ts" setup name="StaffTemplateDesignerDrawer">
/**
 * 服务人员档期确认函模板设计抽屉：
 * 左侧版本列表（新增/复制/含停用），顶部元数据与写操作，主体复用共享设计器，
 * 并支持生成服务端真机预览（Imagick 渲染结果）。
 */
import {
    staffScheduleConfirmLetterConfig,
    staffScheduleConfirmLetterCopy,
    staffScheduleConfirmLetterDisable,
    staffScheduleConfirmLetterEnable,
    staffScheduleConfirmLetterPreview,
    staffScheduleConfirmLetterSave,
    staffScheduleConfirmLetterSetDefault
} from '@/api/staff'
import ScheduleConfirmLetterDesigner from '@/components/staff/schedule-confirm-letter-designer.vue'
import feedback from '@/utils/feedback'

const props = defineProps<{
    modelValue: boolean
    staffId: number
    staffName?: string
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'changed'): void
}>()

const visible = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value)
})

const drawerTitle = computed(() =>
    props.staffName ? `档期确认函设计 · ${props.staffName}` : '档期确认函设计'
)

const loading = ref(false)
const saving = ref(false)
const creating = ref(false)
const copying = ref(false)
const defaulting = ref(false)
const disabling = ref(false)
const enabling = ref(false)
const previewing = ref(false)
const showDisabled = ref(true)
const versions = ref<any[]>([])
const previewSnapshot = ref<any>(createPreviewSnapshot())
const previewImage = ref('')
const previewDialogVisible = ref(false)
let loadedStaffId = 0

const form = reactive<any>({
    config_id: 0,
    template_name: '默认海报',
    template_version: 1,
    is_default: 0,
    status: 1,
    sort: 0,
    design_version: 'staff-schedule-designer-v2',
    design_config: createDefaultDesignConfig()
})

const visibleVersions = computed(() =>
    versions.value.filter((item) => showDisabled.value || Number(item.status) === 1)
)

const activeVersionCount = computed(
    () => versions.value.filter((item) => Number(item.status) === 1).length
)

function createPreviewSnapshot() {
    const variables = {
        service_date_label: '2026年08月18日',
        customer_alias: '张姓新人',
        service_name: '婚礼跟拍',
        city_label: '杭州 西湖区',
        staff_name: '服务人员'
    }
    return {
        ...variables,
        variables,
        qrcode_image: ''
    }
}

function createDefaultDesignConfig() {
    return {
        canvas: { width: 1080, height: 1920 },
        background: { type: 'color', color: '#191713', image: '', fit: 'cover', opacity: 1 },
        layers: []
    }
}

const buildPayload = () => ({
    staff_id: Number(props.staffId || 0),
    config_id: Number(form.config_id || 0),
    template_name: form.template_name,
    sort: Number(form.sort || 0),
    is_default: Number(form.is_default || 0),
    design_version: form.design_version,
    design_config: form.design_config
})

const applyConfig = (data: any) => {
    if (!data) {
        return
    }
    Object.assign(form, {
        config_id: Number(data.config_id || 0),
        template_name: data.template_name || '默认海报',
        template_version: Number(data.template_version || 1),
        is_default: Number(data.is_default ?? 0),
        status: Number(data.status ?? 1),
        sort: Number(data.sort || 0),
        design_version: data.design_version || 'staff-schedule-designer-v2',
        design_config: data.design_config || createDefaultDesignConfig()
    })
    if (Array.isArray(data.versions)) {
        versions.value = data.versions
    }
}

const notifyChanged = () => {
    emit('changed')
}

const loadServerPreview = async () => {
    if (!props.staffId) {
        return
    }
    previewing.value = true
    try {
        const data: any = await staffScheduleConfirmLetterPreview(buildPayload())
        const snapshot = data?.preview?.rendered_snapshot
        if (snapshot) {
            previewSnapshot.value = snapshot
        }
        previewImage.value = data?.preview?.image_data_url || ''
    } finally {
        previewing.value = false
    }
}

const loadConfig = async (configId = 0) => {
    if (!props.staffId) {
        return
    }
    loading.value = true
    try {
        const data: any = await staffScheduleConfirmLetterConfig({
            staff_id: props.staffId,
            config_id: configId || undefined,
            include_disabled: showDisabled.value ? 1 : 0
        })
        applyConfig(data)
        await loadServerPreview()
    } finally {
        loading.value = false
    }
}

const handleToggleDisabled = () => {
    loadConfig(Number(form.config_id || 0))
}

const handleCreate = async () => {
    if (!props.staffId) {
        return
    }
    creating.value = true
    try {
        const data: any = await staffScheduleConfirmLetterSave({
            staff_id: props.staffId,
            config_id: 0,
            template_name: '新海报模板',
            design_version: 'staff-schedule-designer-v2'
        })
        applyConfig(data)
        await loadServerPreview()
        feedback.msgSuccess('模板已创建')
        notifyChanged()
    } finally {
        creating.value = false
    }
}

const handleCopy = async () => {
    if (!props.staffId || !form.config_id) {
        return
    }
    copying.value = true
    try {
        const { value } = await feedback.prompt('请输入新模板名称', '复制模板', {
            inputValue: `${form.template_name || '海报模板'} 副本`,
            inputValidator: (val: string) => Boolean(String(val || '').trim()),
            inputErrorMessage: '请输入模板名称'
        })
        const data: any = await staffScheduleConfirmLetterCopy({
            staff_id: props.staffId,
            config_id: form.config_id,
            template_name: value
        })
        applyConfig(data)
        await loadServerPreview()
        feedback.msgSuccess('模板已复制')
        notifyChanged()
    } catch (error: any) {
        if (error === 'cancel' || error === 'close') {
            return
        }
        // 业务错误已由 request 层统一提示，这里不重复弹窗
    } finally {
        copying.value = false
    }
}

const handleSetDefault = async () => {
    if (!props.staffId || !form.config_id) {
        return
    }
    defaulting.value = true
    try {
        const data: any = await staffScheduleConfirmLetterSetDefault({
            staff_id: props.staffId,
            config_id: form.config_id
        })
        applyConfig(data)
        feedback.msgSuccess('已设为默认模板')
        notifyChanged()
    } finally {
        defaulting.value = false
    }
}

const handleDisable = async () => {
    if (!props.staffId || !form.config_id) {
        return
    }
    await feedback.confirm(`确定停用「${form.template_name || '当前模板'}」吗？停用后服务人员无法使用该模板生成海报。`)
    disabling.value = true
    try {
        const data: any = await staffScheduleConfirmLetterDisable({
            staff_id: props.staffId,
            config_id: form.config_id
        })
        applyConfig(data)
        await loadServerPreview()
        feedback.msgSuccess('模板已停用')
        notifyChanged()
    } finally {
        disabling.value = false
    }
}

const handleEnable = async () => {
    if (!props.staffId || !form.config_id) {
        return
    }
    enabling.value = true
    try {
        const data: any = await staffScheduleConfirmLetterEnable({
            staff_id: props.staffId,
            config_id: form.config_id
        })
        applyConfig(data)
        feedback.msgSuccess('模板已重新启用')
        notifyChanged()
    } finally {
        enabling.value = false
    }
}

const handleSave = async () => {
    if (!props.staffId) {
        return
    }
    saving.value = true
    try {
        const data: any = await staffScheduleConfirmLetterSave(buildPayload())
        applyConfig(data)
        await loadServerPreview()
        feedback.msgSuccess('档期确认函配置已保存')
        notifyChanged()
    } finally {
        saving.value = false
    }
}

const handleGeneratePreview = async () => {
    await loadServerPreview()
    previewDialogVisible.value = true
}

const downloadPreview = () => {
    if (!previewImage.value) {
        return
    }
    const link = document.createElement('a')
    link.href = previewImage.value
    link.download = `档期确认函-${form.template_name || '模板'}.png`
    link.click()
}

const handleClosed = () => {
    notifyChanged()
}

watch(
    () => props.modelValue,
    (value) => {
        if (!value) {
            return
        }
        // 切换到另一位服务人员时从默认模板开始，避免沿用上一个人的 config_id
        const configId = loadedStaffId === Number(props.staffId || 0) ? Number(form.config_id || 0) : 0
        loadedStaffId = Number(props.staffId || 0)
        loadConfig(configId)
    },
    { immediate: true }
)
</script>

<style scoped lang="scss">
.template-designer {
    min-height: 320px;

    &__layout {
        display: grid;
        grid-template-columns: 280px minmax(0, 1fr);
        gap: 16px;
        align-items: start;
    }
}

.disable-alert {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
    width: 100%;
}

.template-version {
    position: sticky;
    top: 0;
    padding: 14px;
    border: 1px solid var(--el-border-color-light);
    border-radius: 8px;
    background: var(--el-bg-color);

    &__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 12px;
    }

    &__title {
        font-size: 14px;
        font-weight: 600;
        color: var(--el-text-color-primary);
    }

    &__desc {
        margin-top: 2px;
        font-size: 12px;
        color: var(--el-text-color-secondary);
    }

    &__list {
        display: flex;
        flex-direction: column;
        gap: 8px;
        max-height: 520px;
        overflow: auto;
    }

    &__item {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid var(--el-border-color-light);
        border-radius: 8px;
        background: var(--el-bg-color);
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 8px;
        text-align: left;
        cursor: pointer;

        &.is-active {
            border-color: var(--el-color-primary);
            background: var(--el-color-primary-light-9);
        }

        &.is-disabled {
            opacity: 0.62;
        }
    }

    &__main {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    &__name {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 13px;
        font-weight: 600;
        color: var(--el-text-color-primary);
    }

    &__meta {
        font-size: 12px;
        color: var(--el-text-color-secondary);
    }

    &__tags {
        flex: none;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 4px;
    }

    &__actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-top: 12px;

        :deep(.el-button + .el-button) {
            margin-left: 0;
        }
    }
}

.template-main {
    min-width: 0;

    &__meta {
        padding: 12px 14px;
        border: 1px solid var(--el-border-color-light);
        border-radius: 8px;
        background: var(--el-bg-color);
    }

    &__actions {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 4px;
    }
}

.template-meta-form {
    display: grid;
    grid-template-columns: minmax(260px, 420px) 180px;
    gap: 12px;

    :deep(.el-form-item) {
        margin-bottom: 0;
    }
}

.server-preview {
    display: flex;
    justify-content: center;
    min-height: 200px;

    &__image {
        max-width: 100%;
        max-height: 560px;
        border-radius: 6px;
        border: 1px solid var(--el-border-color-light);
    }
}

@media (max-width: 1200px) {
    .template-designer__layout {
        grid-template-columns: minmax(0, 1fr);
    }

    .template-version {
        position: static;
    }

    .template-meta-form {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
