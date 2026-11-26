<template>
    <admin-page-shell class="staff-confirm-letter" title="档期确认函">
        <template #extra>
            <el-button type="primary" @click="generateDialogVisible = true">按订单生成</el-button>
            <el-button :loading="saving" @click="handleCreateTemplate">新建模板版本</el-button>
            <el-button :loading="refreshing" @click="refreshActive">刷新</el-button>
        </template>

        <template #stats>
            <div class="staff-confirm-letter__stats">
                <div class="staff-confirm-letter__stat" @click="focusTemplates">
                    <span class="staff-confirm-letter__stat-label">当前启用模板</span>
                    <strong class="staff-confirm-letter__stat-value">{{ activeVersions.length }}</strong>
                    <span class="staff-confirm-letter__stat-hint">
                        共 {{ versions.length }} 个版本 · 默认「{{ defaultTemplateName }}」
                    </span>
                </div>
                <div
                    class="staff-confirm-letter__stat"
                    @click="focusRecords({ source: '', is_outdated: '' })"
                >
                    <span class="staff-confirm-letter__stat-label">累计生成记录</span>
                    <strong class="staff-confirm-letter__stat-value">{{ recordSummary.total }}</strong>
                    <span class="staff-confirm-letter__stat-hint">
                        平台 {{ recordSummary.platform_count }} · 线下 {{ recordSummary.manual_count }}
                    </span>
                </div>
                <div
                    class="staff-confirm-letter__stat"
                    @click="focusRecords({ source: '', is_outdated: '0' })"
                >
                    <span class="staff-confirm-letter__stat-label">当前有效确认函</span>
                    <strong class="staff-confirm-letter__stat-value">
                        {{ recordSummary.active_count }}
                    </strong>
                </div>
                <div
                    class="staff-confirm-letter__stat"
                    :class="{ 'is-danger': recordSummary.outdated_count > 0 }"
                    @click="focusRecords({ source: '', is_outdated: '1' })"
                >
                    <span class="staff-confirm-letter__stat-label">已失效 / 待更新</span>
                    <strong class="staff-confirm-letter__stat-value">
                        {{ recordSummary.outdated_count }}
                    </strong>
                </div>
            </div>
        </template>

        <el-card shadow="never" class="!border-none admin-hub-card">
            <el-tabs v-model="activeTab" class="staff-confirm-letter__tabs" @tab-change="handleTabChange">
                <el-tab-pane label="我的模板" name="templates">
                    <el-alert
                        v-if="configError"
                        class="staff-confirm-letter__alert"
                        type="error"
                        :title="configError"
                        :closable="false"
                        show-icon
                    >
                        <template #default>
                            <el-button type="primary" link @click="loadConfig(form.config_id)">重试</el-button>
                        </template>
                    </el-alert>

                    <el-alert
                        v-if="form.config_id && Number(form.status) !== 1"
                        class="staff-confirm-letter__alert"
                        type="warning"
                        :closable="false"
                        show-icon
                        title="当前模板已停用，无法生成档期确认函"
                    >
                        <template #default>
                            <el-button
                                type="primary"
                                link
                                :loading="enabling"
                                @click="handleEnableTemplate"
                            >
                                重新启用
                            </el-button>
                        </template>
                    </el-alert>

                    <div class="letter-workspace">
                        <section class="letter-versions">
                            <div class="letter-versions__head">
                                <div>
                                    <div class="letter-versions__title">模板版本</div>
                                    <div class="letter-versions__meta">
                                        {{ activeVersions.length }} 个启用 · 共 {{ versions.length }} 个
                                    </div>
                                </div>
                                <el-switch v-model="showDisabled" size="small" active-text="含停用" />
                            </div>

                            <div class="letter-versions__list">
                                <button
                                    v-for="item in visibleVersions"
                                    :key="item.config_id"
                                    type="button"
                                    :class="[
                                        'letter-versions__item',
                                        {
                                            'is-active':
                                                Number(item.config_id) === Number(form.config_id),
                                            'is-disabled': Number(item.status) !== 1
                                        }
                                    ]"
                                    @click="loadConfig(Number(item.config_id))"
                                >
                                    <span class="letter-versions__main">
                                        <span class="letter-versions__name">
                                            {{ item.template_name || '未命名模板' }}
                                        </span>
                                        <span class="letter-versions__desc">
                                            v{{ item.template_version || 1 }} · 排序 {{ item.sort || 0 }}
                                        </span>
                                    </span>
                                    <span class="letter-versions__tags">
                                        <el-tag
                                            v-if="Number(item.is_default) === 1"
                                            size="small"
                                            type="warning"
                                        >
                                            默认
                                        </el-tag>
                                        <el-tag
                                            v-if="Number(item.status) !== 1"
                                            size="small"
                                            type="info"
                                        >
                                            停用
                                        </el-tag>
                                    </span>
                                </button>
                                <div v-if="!visibleVersions.length" class="letter-versions__empty">
                                    暂无模板版本
                                </div>
                            </div>

                            <div class="letter-versions__actions">
                                <el-button @click="handleCreateTemplate">新建版本</el-button>
                                <el-button :disabled="!form.config_id" @click="handleCopyTemplate">
                                    复制当前
                                </el-button>
                            </div>
                        </section>

                        <section class="letter-designer">
                            <div class="letter-designer__head">
                                <el-form class="letter-designer__form" label-width="76px">
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
                                <div class="letter-designer__actions">
                                    <el-button
                                        :disabled="!canOperateCurrent || Number(form.is_default) === 1"
                                        @click="handleSetDefault"
                                    >
                                        设为默认
                                    </el-button>
                                    <el-button
                                        type="danger"
                                        plain
                                        :disabled="!canDisableCurrent"
                                        @click="handleDisableTemplate"
                                    >
                                        停用
                                    </el-button>
                                    <el-button
                                        type="primary"
                                        :loading="saving"
                                        :disabled="!canOperateCurrent"
                                        @click="handleSave"
                                    >
                                        保存设计
                                    </el-button>
                                    <el-button
                                        :loading="previewLoading"
                                        :disabled="!form.config_id"
                                        @click="refreshPreview"
                                    >
                                        生成导出预览
                                    </el-button>
                                </div>
                            </div>

                            <schedule-confirm-letter-designer
                                v-model="form.design_config"
                                :preview-snapshot="previewSnapshot"
                            />

                            <div class="letter-preview">
                                <div class="letter-preview__head">
                                    <div>
                                        <div class="letter-preview__title">导出效果预览</div>
                                    </div>
                                    <div class="letter-preview__actions">
                                        <el-button
                                            :disabled="!previewImage"
                                            @click="previewDialogVisible = true"
                                        >
                                            放大预览
                                        </el-button>
                                        <el-button
                                            type="primary"
                                            plain
                                            :disabled="!previewImage"
                                            @click="downloadPreviewImage"
                                        >
                                            下载示例图
                                        </el-button>
                                    </div>
                                </div>
                                <el-alert
                                    v-if="previewError"
                                    class="letter-preview__alert"
                                    type="warning"
                                    :title="previewError"
                                    :closable="false"
                                    show-icon
                                />
                                <div v-if="previewImage" class="letter-preview__canvas">
                                    <el-image
                                        :src="previewImage"
                                        fit="contain"
                                        class="letter-preview__image"
                                        :preview-src-list="[previewImage]"
                                        preview-teleported
                                    />
                                </div>
                                <div v-else class="letter-preview__empty">
                                    暂无导出预览
                                </div>
                            </div>
                        </section>
                    </div>
                </el-tab-pane>

                <el-tab-pane name="records" lazy>
                    <template #label>
                        <span class="staff-confirm-letter__tab-label">
                            生成记录
                            <span
                                v-if="recordSummary.outdated_count > 0"
                                class="staff-confirm-letter__tab-badge"
                            >
                                {{ recordSummary.outdated_count }}
                            </span>
                        </span>
                    </template>

                    <search-panel>
                        <el-form class="mb-[-16px]" :inline="true" :model="recordQuery">
                            <el-form-item class="w-[240px]" label="关键词">
                                <el-input
                                    v-model="recordQuery.keyword"
                                    clearable
                                    placeholder="订单号、客户称呼或模板名"
                                    @keyup.enter="handleRecordSearch"
                                />
                            </el-form-item>
                            <el-form-item class="w-[150px]" label="来源">
                                <el-select v-model="recordQuery.source" clearable placeholder="全部">
                                    <el-option label="平台订单" value="platform" />
                                    <el-option label="线下档期" value="manual" />
                                </el-select>
                            </el-form-item>
                            <el-form-item class="w-[160px]" label="生成端">
                                <el-select
                                    v-model="recordQuery.generate_source"
                                    clearable
                                    placeholder="全部"
                                >
                                    <el-option label="服务人员" value="staff" />
                                    <el-option label="后台代生成" value="admin" />
                                </el-select>
                            </el-form-item>
                            <el-form-item class="w-[140px]" label="状态">
                                <el-select
                                    v-model="recordQuery.is_outdated"
                                    clearable
                                    placeholder="全部"
                                >
                                    <el-option label="当前有效" value="0" />
                                    <el-option label="已失效" value="1" />
                                </el-select>
                            </el-form-item>
                            <el-form-item>
                                <el-button type="primary" @click="handleRecordSearch">查询</el-button>
                                <el-button @click="handleRecordReset">重置</el-button>
                            </el-form-item>
                        </el-form>
                    </search-panel>

                    <admin-table-toolbar class="mt-4">
                        <template #left>
                            <div class="staff-confirm-letter__quick-views">
                                <button
                                    v-for="view in QUICK_VIEWS"
                                    :key="view.value"
                                    type="button"
                                    class="staff-confirm-letter__quick-view"
                                    :class="{ 'is-active': activeQuickView === view.value }"
                                    @click="applyQuickView(view.value)"
                                >
                                    {{ view.label }}
                                </button>
                            </div>
                        </template>
                        <template #right>
                            <el-button type="primary" @click="generateDialogVisible = true">
                                按订单生成
                            </el-button>
                            <el-button :loading="recordPager.loading" @click="getRecordLists">
                                刷新
                            </el-button>
                        </template>
                    </admin-table-toolbar>

                    <el-table v-loading="recordPager.loading" :data="recordPager.lists" border>
                        <el-table-column label="海报" width="88">
                            <template #default="{ row }">
                                <el-image
                                    class="record-thumb"
                                    :src="row.thumb_image_url || row.full_image_url"
                                    fit="cover"
                                    :preview-src-list="buildPreviewList(row)"
                                    preview-teleported
                                >
                                    <template #error>
                                        <div class="record-thumb__empty">无图</div>
                                    </template>
                                </el-image>
                            </template>
                        </el-table-column>
                        <el-table-column label="来源与订单" min-width="210">
                            <template #default="{ row }">
                                <div class="cell-main">{{ recordSourceTitle(row) }}</div>
                                <div class="cell-sub">
                                    服务日期 {{ row.service_date_label || row.confirm_date || '-' }}
                                </div>
                            </template>
                        </el-table-column>
                        <el-table-column label="客户与服务项目" min-width="190">
                            <template #default="{ row }">
                                <div class="cell-main">{{ row.customer_alias || '脱敏客户' }}</div>
                                <div class="cell-sub">{{ row.service_name || '-' }}</div>
                            </template>
                        </el-table-column>
                        <el-table-column label="模板与版本" min-width="180">
                            <template #default="{ row }">
                                <div class="cell-main">{{ row.config_name || '历史配置' }}</div>
                                <div class="cell-sub">
                                    模板 V{{ row.config_template_version || 1 }} · 第
                                    {{ row.version || 1 }} 版
                                </div>
                            </template>
                        </el-table-column>
                        <el-table-column label="状态与生成端" width="180">
                            <template #default="{ row }">
                                <div class="tag-row">
                                    <el-tag
                                        size="small"
                                        effect="plain"
                                        :type="Number(row.is_outdated || 0) === 1 ? 'info' : 'success'"
                                    >
                                        {{ Number(row.is_outdated || 0) === 1 ? '已失效' : '当前有效' }}
                                    </el-tag>
                                    <el-tag size="small" effect="plain" type="warning">
                                        {{ generateSourceLabel(row.generate_source) }}
                                    </el-tag>
                                </div>
                            </template>
                        </el-table-column>
                        <el-table-column label="生成时间" width="170">
                            <template #default="{ row }">{{ row.create_time_text || '-' }}</template>
                        </el-table-column>
                        <el-table-column label="操作" width="220" fixed="right">
                            <template #default="{ row }">
                                <el-button type="primary" link @click="openRecordDetail(row)">
                                    详情
                                </el-button>
                                <el-button
                                    type="warning"
                                    link
                                    :loading="rebuildingId === Number(row.letter_id || 0)"
                                    :disabled="Number(row.letter_id || 0) <= 0"
                                    @click="rebuildRecord(row)"
                                >
                                    重算海报
                                </el-button>
                                <el-button type="primary" link @click="downloadRecord(row)">
                                    下载原图
                                </el-button>
                            </template>
                        </el-table-column>
                    </el-table>

                    <div class="flex justify-end mt-4">
                        <pagination v-model="recordPager" @change="getRecordLists" />
                    </div>
                </el-tab-pane>
            </el-tabs>
        </el-card>

        <GenerateByOrderDialog
            v-model="generateDialogVisible"
            :default-config-id="defaultActiveConfigId"
            @success="handleGeneratedOrderSuccess"
        />

        <PosterRecordDetailDrawer
            v-model="recordDetailVisible"
            :record="activeRecord"
            :staff-mode="true"
            :fetch-detail-api="myScheduleConfirmLetterDetail"
            :regenerate-assets-api="myScheduleConfirmLetterAssets"
            @regenerated="handleRecordRegenerated"
        />

        <el-dialog v-model="previewDialogVisible" title="导出效果预览" width="560px" append-to-body>
            <el-image
                v-if="previewImage"
                :src="previewImage"
                fit="contain"
                class="letter-preview-dialog__image"
            />
            <template #footer>
                <el-button :disabled="!previewImage" @click="downloadPreviewImage">
                    下载示例图
                </el-button>
                <el-button type="primary" @click="previewDialogVisible = false">关闭</el-button>
            </template>
        </el-dialog>
    </admin-page-shell>
</template>

<script setup lang="ts" name="staffScheduleConfirmLetter">
/**
 * 服务人员工作台「档期确认函」：模板版本设计 + 订单/线下出图记录管理。
 * 模板设计保留单页双栏工作区，出图记录复用运营 Hub 的快捷视图与详情抽屉。
 */
import {
    myScheduleConfirmLetterAssets,
    myScheduleConfirmLetterConfig,
    myScheduleConfirmLetterCopy,
    myScheduleConfirmLetterDetail,
    myScheduleConfirmLetterDisable,
    myScheduleConfirmLetterEnable,
    myScheduleConfirmLetterPreview,
    myScheduleConfirmLetterRecords,
    myScheduleConfirmLetterSave,
    myScheduleConfirmLetterSetDefault
} from '@/api/staff-center'
import ScheduleConfirmLetterDesigner from '@/components/staff/schedule-confirm-letter-designer.vue'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'
import PosterRecordDetailDrawer from '@/views/staff/confirm_letter/components/PosterRecordDetailDrawer.vue'

import { extractErrorMessage } from '../../questionnaire/shared'
import GenerateByOrderDialog from './components/GenerateByOrderDialog.vue'

type TabName = 'templates' | 'records'
type QuickViewValue = 'all' | 'current' | 'outdated' | 'platform' | 'manual'

const QUICK_VIEWS: Array<{ value: QuickViewValue; label: string }> = [
    { value: 'all', label: '全部' },
    { value: 'current', label: '当前有效' },
    { value: 'outdated', label: '已失效' },
    { value: 'platform', label: '平台订单' },
    { value: 'manual', label: '线下档期' }
]

const route = useRoute()
const router = useRouter()

const normalizeSource = (value: unknown) =>
    value === 'platform' || value === 'manual' ? value : ''
const normalizeGenerateSource = (value: unknown) =>
    value === 'staff' || value === 'admin' ? value : ''
const normalizeOutdated = (value: unknown) => (value === '0' || value === '1' ? value : '')
const resolveTab = (value: unknown): TabName =>
    String(value ?? '') === 'records' ? 'records' : 'templates'

const activeTab = ref<TabName>(resolveTab(route.query.tab))
const loading = ref(false)
const saving = ref(false)
const previewLoading = ref(false)
const enabling = ref(false)
const showDisabled = ref(true)
const versions = ref<any[]>([])
const previewImage = ref('')
const previewError = ref('')
const configError = ref('')
const previewDialogVisible = ref(false)
const generateDialogVisible = ref(false)
const recordDetailVisible = ref(false)
const activeRecord = ref<any>(null)
const rebuildingId = ref(0)

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
        staff_name: '服务人员'
    }
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
        layers: []
    }
})

const recordQuery = reactive({
    keyword: String(route.query.keyword || ''),
    source: normalizeSource(route.query.source),
    generate_source: normalizeGenerateSource(route.query.generate_source),
    is_outdated: normalizeOutdated(route.query.is_outdated)
})

const recordSummary = reactive({
    total: 0,
    active_count: 0,
    outdated_count: 0,
    platform_count: 0,
    manual_count: 0
})

const visibleVersions = computed(() =>
    versions.value.filter((item) => showDisabled.value || Number(item.status) === 1)
)

const activeVersions = computed(() =>
    versions.value.filter((item) => Number(item.status) === 1)
)

const defaultTemplateName = computed(() => {
    const hit = versions.value.find(
        (item) => Number(item.is_default) === 1 && Number(item.status) === 1
    )
    return hit?.template_name || '未设置'
})

const canOperateCurrent = computed(
    () => Boolean(form.config_id) && Number(form.status) === 1
)

const canDisableCurrent = computed(
    () => canOperateCurrent.value && activeVersions.value.length > 1
)

const defaultActiveConfigId = computed(() =>
    Number(form.status) === 1 ? Number(form.config_id || 0) : 0
)

const refreshing = computed(() =>
    activeTab.value === 'records' ? recordPager.loading : loading.value
)

const activeQuickView = computed<QuickViewValue>(() => {
    if (recordQuery.source === 'platform') {
        return 'platform'
    }
    if (recordQuery.source === 'manual') {
        return 'manual'
    }
    if (recordQuery.is_outdated === '1') {
        return 'outdated'
    }
    if (recordQuery.is_outdated === '0') {
        return 'current'
    }
    return 'all'
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
    design_config: form.design_config
})

const loadConfig = async (configId = Number(form.config_id || 0)) => {
    loading.value = true
    configError.value = ''
    try {
        const data = await myScheduleConfirmLetterConfig({
            config_id: configId,
            include_disabled: showDisabled.value ? 1 : 0
        })
        assignConfig(data || {})
        previewImage.value = ''
        await refreshPreview()
    } catch (error) {
        configError.value = extractErrorMessage(error, '档期确认函模板加载失败，请稍后重试')
    } finally {
        loading.value = false
    }
}

/**
 * 拉取导出预览图。
 * 原实现漏了 preview.image_data_url 赋值，导致预览永远空白；
 * 这里把渲染失败收敛为预览区提示，避免二维码/字体未就绪时整页加载中断。
 */
const refreshPreview = async () => {
    if (!form.config_id) {
        return
    }
    previewLoading.value = true
    previewError.value = ''
    try {
        const data: any = await myScheduleConfirmLetterPreview(buildPayload())
        previewImage.value = data?.preview?.image_data_url || ''
        previewSnapshot.value = data?.preview?.rendered_snapshot || previewSnapshot.value
    } catch (error) {
        previewImage.value = ''
        previewError.value = extractErrorMessage(
            error,
            '导出预览生成失败，请检查海报二维码与字体配置后重试'
        )
    } finally {
        previewLoading.value = false
    }
}

const handleSave = async () => {
    if (saving.value || !canOperateCurrent.value) {
        return
    }
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
    if (saving.value) {
        return
    }
    saving.value = true
    try {
        const data = await myScheduleConfirmLetterSave({
            config_id: 0,
            template_name: '新海报模板',
            design_version: 'staff-schedule-designer-v2'
        })
        assignConfig(data || {})
        activeTab.value = 'templates'
        syncQuery()
        await refreshPreview()
        feedback.msgSuccess('模板版本已创建')
    } finally {
        saving.value = false
    }
}

const handleCopyTemplate = async () => {
    if (!form.config_id) {
        return
    }
    let templateName = ''
    try {
        const { value } = await feedback.prompt('请输入新模板名称', '复制模板', {
            inputValue: `${form.template_name || '海报模板'} 副本`,
            inputValidator: (val: string) => Boolean(String(val || '').trim()),
            inputErrorMessage: '请输入模板名称'
        })
        templateName = String(value || '').trim()
    } catch {
        return
    }
    if (!templateName) {
        return
    }
    const data = await myScheduleConfirmLetterCopy({
        config_id: form.config_id,
        template_name: templateName
    })
    assignConfig(data || {})
    await refreshPreview()
    feedback.msgSuccess('模板已复制')
}

const handleSetDefault = async () => {
    if (!canOperateCurrent.value) {
        return
    }
    const data = await myScheduleConfirmLetterSetDefault({ config_id: form.config_id })
    assignConfig(data || {})
    feedback.msgSuccess('已设为默认模板')
}

const handleDisableTemplate = async () => {
    if (!canDisableCurrent.value) {
        return
    }
    try {
        await feedback.confirm(`确定停用「${form.template_name || '当前模板'}」吗？`)
    } catch {
        return
    }
    const data = await myScheduleConfirmLetterDisable({ config_id: form.config_id })
    assignConfig(data || {})
    await refreshPreview()
    feedback.msgSuccess('模板已停用')
}

const handleEnableTemplate = async () => {
    if (!form.config_id || enabling.value) {
        return
    }
    enabling.value = true
    try {
        const data = await myScheduleConfirmLetterEnable({ config_id: form.config_id })
        assignConfig(data || {})
        await refreshPreview()
        await refreshRecordSummary()
        feedback.msgSuccess('模板已重新启用')
    } finally {
        enabling.value = false
    }
}

const downloadPreviewImage = () => {
    if (!previewImage.value) {
        return
    }
    const link = document.createElement('a')
    link.href = previewImage.value
    link.download = `档期确认函预览-${Date.now()}.jpg`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
}

const isRecordQueryClean = () =>
    recordQuery.keyword.trim() === '' &&
    recordQuery.source === '' &&
    recordQuery.generate_source === '' &&
    recordQuery.is_outdated === ''

const applySummary = (summary: any) => {
    if (!summary || typeof summary !== 'object') {
        return
    }
    recordSummary.total = Number(summary.total || 0)
    recordSummary.active_count = Number(summary.active_count || 0)
    recordSummary.outdated_count = Number(summary.outdated_count || 0)
    recordSummary.platform_count = Number(summary.platform_count || 0)
    recordSummary.manual_count = Number(summary.manual_count || 0)
}

const fetchRecordLists = async (params: Record<string, any>) => {
    try {
        const res: any = await myScheduleConfirmLetterRecords(params)
        // 接口 summary 随筛选条件变化，仅在无筛选时同步 KPI，避免统计卡被局部结果覆盖
        if (isRecordQueryClean()) {
            applySummary(res?.summary)
        }
        return res && Array.isArray(res.lists) ? res : { lists: [], count: 0 }
    } catch {
        return { lists: [], count: 0 }
    }
}

const { pager: recordPager, getLists: getRecordLists } = usePaging({
    fetchFun: fetchRecordLists,
    params: recordQuery,
    firstLoading: false
})

/** 静默同步 KPI 摘要：首次进入模板页时统计卡也要准确。 */
const refreshRecordSummary = async () => {
    try {
        const res: any = await myScheduleConfirmLetterRecords({ page_no: 1, page_size: 15 })
        applySummary(res?.summary)
    } catch {
        // KPI 摘要刷新失败不阻断页面
    }
}

const assignQueryValue = (query: Record<string, any>, key: string, value: string) => {
    if (value) {
        query[key] = value
    } else {
        delete query[key]
    }
}

const syncQuery = () => {
    const query: Record<string, any> = { ...route.query, tab: activeTab.value }
    if (activeTab.value === 'records') {
        assignQueryValue(query, 'keyword', recordQuery.keyword.trim())
        assignQueryValue(query, 'source', recordQuery.source)
        assignQueryValue(query, 'generate_source', recordQuery.generate_source)
        assignQueryValue(query, 'is_outdated', recordQuery.is_outdated)
    } else {
        delete query.keyword
        delete query.source
        delete query.generate_source
        delete query.is_outdated
    }
    router.replace({ query })
}

const handleTabChange = (name: string | number) => {
    const target = String(name)
    if (target !== 'templates' && target !== 'records') {
        return
    }
    syncQuery()
    if (target === 'records') {
        recordPager.page = 1
        void getRecordLists()
    }
}

const focusTemplates = () => {
    activeTab.value = 'templates'
    syncQuery()
}

const focusRecords = (patch: { source?: string; is_outdated?: string }) => {
    activeTab.value = 'records'
    recordQuery.keyword = ''
    recordQuery.source = patch.source || ''
    recordQuery.generate_source = ''
    recordQuery.is_outdated = patch.is_outdated || ''
    recordPager.page = 1
    syncQuery()
    void getRecordLists()
}

const applyQuickView = (view: QuickViewValue) => {
    recordQuery.source = ''
    recordQuery.is_outdated = ''
    if (view === 'current') {
        recordQuery.is_outdated = '0'
    } else if (view === 'outdated') {
        recordQuery.is_outdated = '1'
    } else if (view === 'platform') {
        recordQuery.source = 'platform'
    } else if (view === 'manual') {
        recordQuery.source = 'manual'
    }
    recordPager.page = 1
    syncQuery()
    void getRecordLists()
}

const handleRecordSearch = () => {
    recordQuery.keyword = recordQuery.keyword.trim()
    recordPager.page = 1
    syncQuery()
    void getRecordLists()
}

const handleRecordReset = () => {
    recordQuery.keyword = ''
    recordQuery.source = ''
    recordQuery.generate_source = ''
    recordQuery.is_outdated = ''
    recordPager.page = 1
    syncQuery()
    void getRecordLists()
}

const refreshActive = () => {
    void refreshRecordSummary()
    if (activeTab.value === 'records') {
        void getRecordLists()
        return
    }
    void loadConfig(Number(form.config_id || 0))
}

const recordSourceTitle = (row: any) => {
    if (row?.source === 'manual') {
        return `线下档期 #${row.manual_schedule_id || '-'}`
    }
    return row?.order_sn || `平台订单 #${row?.order_id || '-'}`
}

const generateSourceLabel = (value: string) => {
    if (value === 'admin') {
        return '后台代生成'
    }
    if (value === 'staff') {
        return '服务人员'
    }
    return '未知来源'
}

const buildPreviewList = (row: any) => {
    const url = row?.full_image_url || row?.thumb_image_url || ''
    return url ? [url] : []
}

const downloadRecord = (row: any) => {
    const url = row?.full_image_url || row?.thumb_image_url || ''
    if (!url) {
        return
    }
    window.open(url, '_blank')
}

const openRecordDetail = (row: any) => {
    activeRecord.value = row
    recordDetailVisible.value = true
}

const rebuildRecord = async (row: any) => {
    const letterId = Number(row?.letter_id || 0)
    if (letterId <= 0 || rebuildingId.value === letterId) {
        return
    }
    rebuildingId.value = letterId
    try {
        await myScheduleConfirmLetterAssets({
            letter_id: letterId,
            snapshot_hash: String(row?.snapshot_hash || '')
        })
        feedback.msgSuccess('海报图片已重新生成')
        await Promise.all([getRecordLists(), refreshRecordSummary()])
    } finally {
        rebuildingId.value = 0
    }
}

const handleRecordRegenerated = () => {
    void getRecordLists()
    void refreshRecordSummary()
}

const handleGeneratedOrderSuccess = async () => {
    generateDialogVisible.value = false
    activeTab.value = 'records'
    recordPager.page = 1
    syncQuery()
    await Promise.all([getRecordLists(), refreshRecordSummary()])
}

watch(
    () => route.query,
    (query) => {
        const nextTab = resolveTab(query.tab)
        const nextKeyword = String(query.keyword || '')
        const nextSource = normalizeSource(query.source)
        const nextGenerateSource = normalizeGenerateSource(query.generate_source)
        const nextOutdated = normalizeOutdated(query.is_outdated)
        const tabChanged = nextTab !== activeTab.value
        const filtersChanged =
            recordQuery.keyword !== nextKeyword ||
            recordQuery.source !== nextSource ||
            recordQuery.generate_source !== nextGenerateSource ||
            recordQuery.is_outdated !== nextOutdated
        if (!tabChanged && !filtersChanged) {
            return
        }
        activeTab.value = nextTab
        recordQuery.keyword = nextKeyword
        recordQuery.source = nextSource
        recordQuery.generate_source = nextGenerateSource
        recordQuery.is_outdated = nextOutdated
        recordPager.page = 1
        if (nextTab === 'records') {
            void getRecordLists()
        }
    },
    { deep: true }
)

onMounted(() => {
    void loadConfig()
    void refreshRecordSummary()
    if (activeTab.value === 'records') {
        void getRecordLists()
    }
})
</script>

<style scoped lang="scss">
.staff-confirm-letter {
    &__stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    &__stat {
        display: flex;
        flex-direction: column;
        gap: 6px;
        padding: 12px 14px;
        border: 1px solid var(--el-border-color-light);
        border-radius: 8px;
        background: var(--el-bg-color);
        cursor: pointer;
        transition:
            border-color 0.2s,
            background-color 0.2s;

        &:hover {
            border-color: var(--el-color-primary);
            background: var(--el-color-primary-light-9);
        }

        &.is-danger {
            border-color: var(--el-color-danger-light-5);

            &:hover {
                border-color: var(--el-color-danger);
                background: var(--el-color-danger-light-9);
            }
        }
    }

    &__stat-label {
        color: var(--el-text-color-secondary);
        font-size: 12px;
    }

    &__stat-value {
        color: var(--el-text-color-primary);
        font-size: 20px;
        font-weight: 600;
        line-height: 1.1;
        font-variant-numeric: tabular-nums;

        .is-danger & {
            color: var(--el-color-danger);
        }
    }

    &__stat-hint {
        color: var(--el-text-color-secondary);
        font-size: 12px;
        line-height: 1.4;
    }

    &__tabs {
        :deep(.el-tabs__content) {
            padding-top: 8px;
        }
    }

    &__tab-label {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    &__tab-badge {
        min-width: 18px;
        padding: 0 5px;
        border-radius: 9px;
        background: var(--el-color-warning-light-9);
        color: var(--el-color-warning);
        font-size: 11px;
        line-height: 16px;
        text-align: center;
    }

    &__alert {
        margin-bottom: 12px;
        border-radius: var(--admin-radius-sm);
    }

    &__quick-views {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    &__quick-view {
        padding: 5px 12px;
        border: 1px solid var(--admin-color-border);
        border-radius: 999px;
        background: var(--admin-surface-card);
        color: var(--admin-color-muted);
        font-size: 12px;
        line-height: 1.4;
        cursor: pointer;
        transition:
            color 0.2s ease,
            border-color 0.2s ease,
            background 0.2s ease;

        &:hover {
            color: var(--el-color-primary);
            border-color: var(--el-color-primary-light-5);
        }

        &.is-active {
            color: var(--el-color-primary);
            border-color: var(--el-color-primary-light-5);
            background: var(--el-color-primary-light-9);
            font-weight: 500;
        }
    }
}

.admin-hub-card {
    :deep(.el-card__body) {
        padding-top: 8px;
    }
}

.letter-workspace {
    display: grid;
    grid-template-columns: 280px minmax(0, 1fr);
    gap: 16px;
    align-items: start;
}

.letter-versions {
    position: sticky;
    top: 76px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 14px;
    border: 1px solid var(--admin-color-border);
    border-radius: var(--admin-radius-sm);
    background: var(--admin-surface-card);

    &__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
    }

    &__title {
        font-size: 15px;
        font-weight: 600;
        color: var(--admin-color-title);
    }

    &__meta {
        margin-top: 4px;
        font-size: 12px;
        color: var(--admin-color-muted);
    }

    &__list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    &__item {
        width: 100%;
        padding: 12px;
        border: 1px solid var(--admin-color-border);
        border-radius: var(--admin-radius-sm);
        background: var(--admin-surface-card);
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
        text-align: left;
        cursor: pointer;
        transition:
            border-color 0.2s ease,
            background 0.2s ease;

        &:hover {
            border-color: var(--el-color-primary-light-5);
        }

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
        gap: 6px;
    }

    &__name {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 14px;
        font-weight: 600;
        color: var(--admin-color-title);
    }

    &__desc {
        font-size: 12px;
        color: var(--admin-color-muted);
    }

    &__tags {
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        gap: 6px;
        align-items: flex-end;
    }

    &__empty {
        padding: 18px 10px;
        font-size: 12px;
        line-height: 1.7;
        color: var(--admin-color-muted);
        text-align: center;
    }

    &__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;

        :deep(.el-button) {
            flex: 1;
            margin-left: 0;
        }
    }
}

.letter-designer {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 16px;

    &__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
    }

    &__form {
        display: grid;
        grid-template-columns: minmax(240px, 380px) 180px;
        gap: 12px;
        align-items: start;

        :deep(.el-form-item) {
            margin-bottom: 0;
        }
    }

    &__actions {
        flex-shrink: 0;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
}

.letter-preview {
    padding: 16px;
    border: 1px solid var(--admin-color-border);
    border-radius: var(--admin-radius-sm);
    background: var(--admin-surface-card);

    &__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 12px;
    }

    &__title {
        font-size: 14px;
        font-weight: 600;
        color: var(--admin-color-title);
    }

    &__actions {
        flex-shrink: 0;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    &__alert {
        margin-bottom: 12px;
        border-radius: var(--admin-radius-sm);
    }

    &__canvas {
        display: flex;
        justify-content: center;
        padding: 12px;
        border: 1px solid var(--el-border-color-light);
        border-radius: var(--admin-radius-sm);
        background: var(--el-fill-color-lighter);
    }

    &__image {
        width: 240px;
        max-width: 100%;
        max-height: 520px;
        border-radius: 6px;
        border: 1px solid var(--el-border-color-light);
    }

    &__empty {
        padding: 36px 16px;
        text-align: center;
        font-size: 13px;
        line-height: 1.7;
        color: var(--admin-color-muted);
        border: 1px dashed var(--admin-color-border);
        border-radius: var(--admin-radius-sm);
    }
}

.letter-preview-dialog__image {
    width: 100%;
    max-height: 70vh;
}

.cell-main {
    font-size: 13px;
    font-weight: 500;
    line-height: 1.5;
    color: var(--admin-color-title);
}

.cell-sub {
    margin-top: 2px;
    font-size: 12px;
    line-height: 1.5;
    color: var(--admin-color-muted);
}

.tag-row {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.record-thumb {
    width: 48px;
    height: 85px;
    border-radius: 4px;
    border: 1px solid var(--el-border-color-light);
    background: var(--el-fill-color-light);

    &__empty {
        display: grid;
        place-items: center;
        width: 100%;
        height: 100%;
        font-size: 12px;
        color: var(--el-text-color-secondary);
    }
}

@media (max-width: 1180px) {
    .letter-workspace {
        grid-template-columns: minmax(0, 1fr);
    }

    .letter-versions {
        position: static;
    }

    .letter-designer__head {
        flex-direction: column;
    }

    .letter-designer__form {
        width: 100%;
        grid-template-columns: minmax(0, 1fr);
    }
}

@media (max-width: 1200px) {
    .staff-confirm-letter__stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 768px) {
    .staff-confirm-letter__stats {
        grid-template-columns: 1fr;
    }
}
</style>
