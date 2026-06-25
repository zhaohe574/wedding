<template>
    <admin-page-shell class="monthly-report-page" title="单量月报">
        <el-card class="!border-none" shadow="never">
            <div class="page-head">
                <div>
                    <div class="page-head__title">单量月报</div>
                    <div class="page-head__desc">独立维护月报素材、模板和确认记录，确认后覆盖该月份最新版本，历史版本保留。</div>
                </div>
                <div class="page-head__actions">
                    <el-button :loading="bootLoading" @click="reloadAll">刷新</el-button>
                </div>
            </div>
        </el-card>

        <el-card class="workspace-card !border-none mt-4" shadow="never">
            <el-tabs v-model="activeTab" @tab-change="handleTabChange">
                <el-tab-pane label="生成月报" name="generate">
                    <div class="generate-toolbar">
                        <el-form :inline="true" :model="reportForm" class="mb-[-16px]">
                            <el-form-item label="统计月份">
                                <el-date-picker
                                    v-model="reportForm.report_month"
                                    type="month"
                                    value-format="YYYY-MM"
                                    placeholder="选择月份"
                                    clearable
                                />
                            </el-form-item>
                            <el-form-item label="服务分类">
                                <el-select
                                    v-model="reportForm.category_ids"
                                    multiple
                                    collapse-tags
                                    collapse-tags-tooltip
                                    clearable
                                    filterable
                                    placeholder="选择主持类分类"
                                    class="w-[320px]"
                                >
                                    <el-option
                                        v-for="item in categoryOptions"
                                        :key="item.id"
                                        :label="item.name"
                                        :value="Number(item.id)"
                                    />
                                </el-select>
                            </el-form-item>
                            <el-form-item>
                                <el-button type="primary" :loading="previewLoading" @click="handlePreview">生成预览</el-button>
                                <el-button
                                    type="success"
                                    :loading="confirmLoading"
                                    :disabled="!previewData || !previewIsCurrent || Number(previewData.can_confirm || 0) !== 1"
                                    @click="handleConfirm"
                                >
                                    确认覆盖最新
                                </el-button>
                            </el-form-item>
                        </el-form>
                    </div>

                    <el-alert
                        v-if="blockingIssues.length"
                        class="mt-4"
                        type="error"
                        :closable="false"
                        show-icon
                        title="当前预览存在阻断问题，处理后才能确认"
                    >
                        <template #default>
                            <div class="issue-list">
                                <div v-for="issue in blockingIssues" :key="issue.code + issue.message">{{ issue.message }}</div>
                            </div>
                        </template>
                    </el-alert>
                    <el-alert
                        v-else-if="warningIssues.length"
                        class="mt-4"
                        type="warning"
                        :closable="false"
                        show-icon
                        title="当前预览有提醒"
                    >
                        <template #default>
                            <div class="issue-list">
                                <div v-for="issue in warningIssues" :key="issue.code + issue.message">{{ issue.message }}</div>
                            </div>
                        </template>
                    </el-alert>

                    <div class="generate-grid mt-4">
                        <div class="draft-panel">
                            <div class="section-title">草稿修正</div>
                            <div class="stat-edit-grid">
                                <el-form-item label="新增档期">
                                    <el-input-number v-model="draft.addition_count" :min="0" :max="9999" class="w-full" />
                                </el-form-item>
                                <el-form-item label="共执行">
                                    <el-input-number v-model="draft.executed_count" :min="0" :max="9999" class="w-full" />
                                </el-form-item>
                                <el-form-item label="单王场次">
                                    <el-input-number v-model="draft.top_count" :min="0" :max="9999" class="w-full" disabled />
                                </el-form-item>
                            </div>
                            <el-form label-width="92px">
                                <el-form-item label="新增标题">
                                    <el-input v-model="draft.copywriting.addition_title" maxlength="80" />
                                </el-form-item>
                                <el-form-item label="新增副标题">
                                    <el-input v-model="draft.copywriting.addition_subtitle" maxlength="120" />
                                </el-form-item>
                                <el-form-item label="榜单标题">
                                    <el-input v-model="draft.copywriting.ranking_title" maxlength="120" />
                                </el-form-item>
                                <el-form-item label="单王标题">
                                    <el-input v-model="draft.copywriting.top_title" maxlength="80" />
                                </el-form-item>
                                <el-form-item label="页脚文案">
                                    <el-input v-model="draft.copywriting.footer_note" maxlength="120" />
                                </el-form-item>
                            </el-form>

                            <div class="section-title mt-4">人员场次</div>
                            <div class="staff-add-row">
                                <el-select v-model="staffAppend.staff_id" filterable clearable placeholder="补入人员" class="flex-1">
                                    <el-option
                                        v-for="item in staffOptions"
                                        :key="item.id"
                                        :label="item.name"
                                        :value="Number(item.id)"
                                    />
                                </el-select>
                                <el-input-number v-model="staffAppend.count" :min="1" :max="999" />
                                <el-button @click="appendStaffRow">补入</el-button>
                            </div>
                            <el-table class="mt-3" :data="draft.ranking_staffs" size="small" border max-height="420">
                                <el-table-column label="排名" width="70">
                                    <template #default="{ $index }">{{ $index + 1 }}</template>
                                </el-table-column>
                                <el-table-column label="人员" min-width="120">
                                    <template #default="{ row }">
                                        <div class="staff-cell">
                                            <el-avatar :src="row.avatar_photo_url || row.photo_url" :size="30">{{ row.chinese_name || row.staff_name || '-' }}</el-avatar>
                                            <div>
                                                <div>{{ row.chinese_name || row.staff_name || '-' }}</div>
                                                <div class="muted">{{ row.english_name || '未填英文名/拼音' }}</div>
                                            </div>
                                        </div>
                                    </template>
                                </el-table-column>
                                <el-table-column label="场次" width="120">
                                    <template #default="{ row }">
                                        <el-input-number
                                            v-model="row.count_raw"
                                            :min="0"
                                            :max="999"
                                            size="small"
                                            @change="syncRowCount(row)"
                                        />
                                    </template>
                                </el-table-column>
                                <el-table-column label="素材" width="120">
                                    <template #default="{ row }">
                                        <div class="material-tags">
                                            <el-tag :type="Number(row.ranking_material_complete ?? row.material_complete ?? 0) === 1 ? 'success' : 'danger'" size="small">
                                                头像{{ Number(row.ranking_material_complete ?? row.material_complete ?? 0) === 1 ? '完整' : '缺失' }}
                                            </el-tag>
                                            <el-tag :type="Number(row.top_material_complete ?? row.material_complete ?? 0) === 1 ? 'success' : 'danger'" size="small">
                                                半身{{ Number(row.top_material_complete ?? row.material_complete ?? 0) === 1 ? '完整' : '缺失' }}
                                            </el-tag>
                                        </div>
                                    </template>
                                </el-table-column>
                                <el-table-column label="操作" width="150">
                                    <template #default="{ $index }">
                                        <el-button type="primary" link @click="moveDraftRow($index, -1)">上移</el-button>
                                        <el-button type="primary" link @click="moveDraftRow($index, 1)">下移</el-button>
                                        <el-button type="danger" link @click="removeDraftRow($index)">移除</el-button>
                                    </template>
                                </el-table-column>
                            </el-table>
                            <div class="draft-actions">
                                <el-button :loading="previewLoading" type="primary" @click="handlePreview">按修正重新预览</el-button>
                            </div>
                        </div>

                        <div class="preview-panel">
                            <div class="section-title">图片预览</div>
                            <div class="image-preview-grid">
                                <div v-for="type in templateTypes" :key="type.value" class="image-preview-card">
                                    <div class="image-preview-card__head">
                                        <span>{{ type.label }}</span>
                                        <el-tag v-if="previewData?.template_snapshot?.[type.value]" size="small" type="info">
                                            v{{ previewData.template_snapshot[type.value].template_version }}
                                        </el-tag>
                                    </div>
                                    <div class="image-preview-card__body">
                                        <div
                                            v-if="svgDataUri(type.value)"
                                            class="image-preview-card__canvas"
                                            :style="previewCanvasStyle(type.value)"
                                        >
                                            <img :src="svgDataUri(type.value)" alt="" />
                                        </div>
                                        <el-empty v-else description="暂无预览" :image-size="80" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </el-tab-pane>

                <el-tab-pane label="月报素材库" name="materials">
                    <div class="table-toolbar">
                        <el-form :inline="true" :model="materialQuery" class="mb-[-16px]">
                            <el-form-item label="关键词">
                                <el-input v-model="materialQuery.keyword" placeholder="人员/拼音" clearable @keyup.enter="loadMaterials(1)" />
                            </el-form-item>
                            <el-form-item label="分类">
                                <el-select v-model="materialQuery.category_id" placeholder="全部分类" clearable class="w-[180px]">
                                    <el-option v-for="item in categoryOptions" :key="item.id" :label="item.name" :value="Number(item.id)" />
                                </el-select>
                            </el-form-item>
                            <el-form-item label="状态">
                                <el-select v-model="materialQuery.status" placeholder="全部" clearable class="w-[140px]">
                                    <el-option label="启用" :value="1" />
                                    <el-option label="停用" :value="0" />
                                </el-select>
                            </el-form-item>
                            <el-form-item>
                                <el-button type="primary" @click="loadMaterials(1)">查询</el-button>
                                <el-button @click="resetMaterialQuery">重置</el-button>
                                <el-button type="primary" plain @click="openMaterialDialog()">新增素材</el-button>
                            </el-form-item>
                        </el-form>
                    </div>
                    <el-table class="mt-4" v-loading="materialPager.loading" :data="materialPager.lists" size="large">
                        <el-table-column label="头像素材" width="96">
                            <template #default="{ row }">
                                <el-image
                                    v-if="row.avatar_photo_url"
                                    class="material-photo"
                                    :src="row.avatar_photo_url"
                                    :preview-src-list="[row.avatar_photo_url]"
                                    fit="cover"
                                />
                                <span v-else>-</span>
                            </template>
                        </el-table-column>
                        <el-table-column label="半身素材" width="96">
                            <template #default="{ row }">
                                <el-image
                                    v-if="row.half_body_photo_url"
                                    class="material-photo material-photo--half"
                                    :src="row.half_body_photo_url"
                                    :preview-src-list="[row.half_body_photo_url]"
                                    fit="cover"
                                />
                                <span v-else>-</span>
                            </template>
                        </el-table-column>
                        <el-table-column label="绑定人员" min-width="140">
                            <template #default="{ row }">
                                <div>{{ row.staff_name || '-' }}</div>
                                <div class="muted">{{ row.category_name || '-' }}</div>
                            </template>
                        </el-table-column>
                        <el-table-column label="英文名/拼音" prop="english_name" min-width="140" />
                        <el-table-column label="排序" prop="sort" width="90" />
                        <el-table-column label="状态" width="90">
                            <template #default="{ row }">
                                <el-tag :type="Number(row.status || 0) === 1 ? 'success' : 'info'">
                                    {{ Number(row.status || 0) === 1 ? '启用' : '停用' }}
                                </el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column label="操作" width="160" fixed="right">
                            <template #default="{ row }">
                                <el-button type="primary" link @click="openMaterialDialog(row)">编辑</el-button>
                                <el-button type="danger" link @click="deleteMaterial(row)">删除</el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                    <div class="flex justify-end mt-4">
                        <pagination :model-value="materialPager" @change="loadMaterials()" />
                    </div>
                </el-tab-pane>

                <el-tab-pane label="模板设置" name="templates">
                    <div class="template-toolbar">
                        <el-radio-group v-model="templateForm.template_type" @change="loadTemplateConfig(0)">
                            <el-radio-button v-for="item in templateTypes" :key="item.value" :label="item.value">{{ item.label }}</el-radio-button>
                        </el-radio-group>
                        <div class="template-toolbar__actions">
                            <el-button :loading="templateLoading" @click="loadTemplateConfig(templateForm.template_id)">刷新</el-button>
                            <el-button @click="createTemplate">新增模板</el-button>
                            <el-button :disabled="!templateForm.template_id" @click="copyTemplate">复制当前</el-button>
                            <el-button type="primary" :loading="templateSaving" @click="saveTemplate">保存设计</el-button>
                        </div>
                    </div>

                    <div class="template-workspace mt-4">
                        <div class="template-list">
                            <div class="section-title">模板版本</div>
                            <button
                                v-for="item in templateVersions"
                                :key="item.template_id"
                                type="button"
                                :class="['template-item', { 'is-active': Number(item.template_id) === Number(templateForm.template_id), 'is-disabled': Number(item.status) !== 1 }]"
                                @click="loadTemplateConfig(Number(item.template_id))"
                            >
                                <span>
                                    <strong>{{ item.template_name }}</strong>
                                    <em>v{{ item.template_version }} · 排序 {{ item.sort || 0 }}</em>
                                </span>
                                <span class="template-item__tags">
                                    <el-tag v-if="Number(item.is_default || 0) === 1" type="warning" size="small">默认</el-tag>
                                    <el-tag v-if="Number(item.status || 0) !== 1" type="info" size="small">停用</el-tag>
                                </span>
                            </button>
                        </div>
                        <div class="template-main">
                            <el-form :inline="true" :model="templateForm">
                                <el-form-item label="模板名称">
                                    <el-input v-model="templateForm.template_name" maxlength="40" class="w-[240px]" />
                                </el-form-item>
                                <el-form-item label="排序">
                                    <el-input-number v-model="templateForm.sort" :min="0" :max="9999" />
                                </el-form-item>
                                <el-form-item>
                                    <el-button :disabled="!templateForm.template_id || Number(templateForm.is_default || 0) === 1" @click="setTemplateDefault">
                                        设为默认
                                    </el-button>
                                    <el-button
                                        type="danger"
                                        plain
                                        :disabled="!templateForm.template_id || activeTemplateVersions.length <= 1"
                                        @click="disableTemplate"
                                    >
                                        停用模板
                                    </el-button>
                                </el-form-item>
                            </el-form>
                            <monthly-report-designer
                                v-model="templateForm.design_config"
                                :template-type="templateForm.template_type"
                                :preview-snapshot="templatePreviewSnapshot"
                                :preview-width="MONTHLY_REPORT_PREVIEW_WIDTH"
                                @preview="refreshTemplatePreview"
                            />
                        </div>
                    </div>
                </el-tab-pane>

                <el-tab-pane label="历史版本" name="history">
                    <div class="table-toolbar">
                        <el-form :inline="true" :model="historyQuery" class="mb-[-16px]">
                            <el-form-item label="月份">
                                <el-date-picker
                                    v-model="historyQuery.report_month"
                                    type="month"
                                    value-format="YYYY-MM"
                                    placeholder="全部月份"
                                    clearable
                                />
                            </el-form-item>
                            <el-form-item>
                                <el-button type="primary" @click="loadHistory(1)">查询</el-button>
                                <el-button @click="resetHistoryQuery">重置</el-button>
                            </el-form-item>
                        </el-form>
                    </div>
                    <el-table class="mt-4" v-loading="historyPager.loading" :data="historyPager.lists" size="large">
                        <el-table-column label="月份/版本" width="140">
                            <template #default="{ row }">
                                <div>{{ row.report_month }}</div>
                                <div class="muted">第 {{ row.version }} 版</div>
                                <el-tag v-if="Number(row.is_current || 0) === 1" class="mt-1" type="success" size="small">最新</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column label="数据" min-width="180">
                            <template #default="{ row }">
                                <div>新增：{{ row.addition_count }} 场</div>
                                <div>执行：{{ row.executed_count }} 场</div>
                                <div>单王：{{ row.top_count }} 场</div>
                            </template>
                        </el-table-column>
                        <el-table-column label="图片" min-width="240">
                            <template #default="{ row }">
                                <div class="history-images">
                                    <el-image v-if="row.addition_image_full_url" :src="row.addition_image_full_url" fit="cover" />
                                    <el-image v-if="row.ranking_image_full_url" :src="row.ranking_image_full_url" fit="cover" />
                                    <el-image v-if="row.top_image_full_url" :src="row.top_image_full_url" fit="cover" />
                                </div>
                            </template>
                        </el-table-column>
                        <el-table-column label="确认时间" width="170">
                            <template #default="{ row }">{{ formatTime(row.confirm_time || row.create_time) }}</template>
                        </el-table-column>
                        <el-table-column label="操作" width="220" fixed="right">
                            <template #default="{ row }">
                                <el-button type="primary" link @click="openHistoryDetail(row)">详情</el-button>
                                <el-button type="primary" link @click="regenerateAssets(row)">重建图片</el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                    <div class="flex justify-end mt-4">
                        <pagination :model-value="historyPager" @change="loadHistory()" />
                    </div>
                </el-tab-pane>
            </el-tabs>
        </el-card>

        <el-dialog v-model="materialDialog.visible" :title="materialDialog.form.id ? '编辑月报素材' : '新增月报素材'" width="620px" destroy-on-close>
            <el-form :model="materialDialog.form" label-width="104px">
                <el-form-item label="绑定人员" required>
                    <el-select v-model="materialDialog.form.staff_id" filterable clearable placeholder="选择服务人员" class="w-full">
                        <el-option v-for="item in staffOptions" :key="item.id" :label="item.name" :value="Number(item.id)" />
                    </el-select>
                </el-form-item>
                <el-form-item label="头像素材" required>
                    <material-picker v-model="materialDialog.form.avatar_photo" :limit="1" />
                </el-form-item>
                <el-form-item label="半身素材" required>
                    <material-picker v-model="materialDialog.form.half_body_photo" :limit="1" />
                </el-form-item>
                <el-form-item label="英文名/拼音" required>
                    <el-input v-model="materialDialog.form.english_name" maxlength="80" />
                </el-form-item>
                <el-form-item label="排序">
                    <el-input-number v-model="materialDialog.form.sort" :min="0" :max="9999" />
                </el-form-item>
                <el-form-item label="状态">
                    <el-radio-group v-model="materialDialog.form.status">
                        <el-radio-button :label="1">启用</el-radio-button>
                        <el-radio-button :label="0">停用</el-radio-button>
                    </el-radio-group>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="materialDialog.visible = false">取消</el-button>
                <el-button type="primary" :loading="materialDialog.saving" @click="saveMaterial">保存</el-button>
            </template>
        </el-dialog>

        <el-dialog v-model="historyDetail.visible" title="月报版本详情" width="980px" destroy-on-close>
            <div v-if="historyDetail.data">
                <div class="detail-meta">
                    <el-tag type="info">{{ historyDetail.data.report_month }}</el-tag>
                    <el-tag type="warning">第 {{ historyDetail.data.version }} 版</el-tag>
                    <el-tag v-if="Number(historyDetail.data.is_current || 0) === 1" type="success">最新版本</el-tag>
                </div>
                <div class="asset-grid mt-4">
                    <asset-preview title="新增档期" :url="historyDetail.data.addition_image_full_url" />
                    <asset-preview title="执行榜" :url="historyDetail.data.ranking_image_full_url" />
                    <asset-preview title="月度单王" :url="historyDetail.data.top_image_full_url" />
                </div>
                <el-table class="mt-4" :data="historyDetail.data?.final_snapshot?.ranking_staffs || []" size="small" border max-height="360">
                    <el-table-column label="排名" prop="rank" width="80" />
                    <el-table-column label="人员姓名" prop="chinese_name" />
                    <el-table-column label="英文名/拼音" prop="english_name" />
                    <el-table-column label="场次" prop="count_raw" width="90" />
                </el-table>
            </div>
        </el-dialog>
    </admin-page-shell>
</template>

<script setup lang="ts" name="monthlyReport">
import { computed, defineComponent, h, onMounted, reactive, ref } from 'vue'
import feedback from '@/utils/feedback'
import MaterialPicker from '@/components/material/picker.vue'
import MonthlyReportDesigner from '@/components/staff/monthly-report-designer.vue'
import {
    monthlyReportCategoryOptions,
    monthlyReportConfirm,
    monthlyReportDetail,
    monthlyReportHistory,
    monthlyReportMaterialDelete,
    monthlyReportMaterialLists,
    monthlyReportMaterialSave,
    monthlyReportPreview,
    monthlyReportRegenerateAssets,
    monthlyReportStaffOptions,
    monthlyReportTemplateConfig,
    monthlyReportTemplateCopy,
    monthlyReportTemplateDisable,
    monthlyReportTemplatePreview,
    monthlyReportTemplateSave,
    monthlyReportTemplateSetDefault,
} from '@/api/monthly-report'

const MONTHLY_REPORT_PREVIEW_WIDTH = 290

const AssetPreview = defineComponent({
    props: {
        title: { type: String, default: '' },
        url: { type: String, default: '' },
    },
    setup(props) {
        return () => h('div', { class: 'asset-preview' }, [
            h('div', { class: 'asset-preview__title' }, props.title),
            props.url
                ? h('img', { src: props.url, alt: '' })
                : h('div', { class: 'asset-preview__empty' }, '暂无图片'),
            props.url
                ? h('div', { class: 'asset-preview__actions' }, [
                    h('button', { type: 'button', onClick: () => window.open(props.url, '_blank') }, '打开'),
                    h('button', { type: 'button', onClick: () => downloadImage(props.url, props.title) }, '下载'),
                    h('button', { type: 'button', onClick: () => copyLink(props.url) }, '复制链接'),
                ])
                : null,
        ])
    },
})

const templateTypes = [
    { label: '上月新增档期', value: 'addition' },
    { label: '上月共执行榜', value: 'ranking' },
    { label: '月度单王', value: 'top' },
]

const activeTab = ref('generate')
const bootLoading = ref(false)
const previewLoading = ref(false)
const confirmLoading = ref(false)
const categoryOptions = ref<any[]>([])
const staffOptions = ref<any[]>([])
const previewData = ref<any>(null)
const lastPreviewKey = ref('')
const reportForm = reactive<any>({
    report_month: defaultReportMonth(),
    category_ids: [],
})
const draft = reactive<any>({
    addition_count: 0,
    executed_count: 0,
    top_count: 0,
    ranking_staffs: [],
    copywriting: {},
})
const staffAppend = reactive<any>({
    staff_id: undefined,
    count: 1,
})

const materialQuery = reactive<any>({
    keyword: '',
    category_id: '',
    status: '',
})
const materialPager = reactive<any>({
    page: 1,
    size: 15,
    count: 0,
    lists: [],
    loading: false,
})
const materialDialog = reactive<any>({
    visible: false,
    saving: false,
    form: {},
})

const templateLoading = ref(false)
const templateSaving = ref(false)
const templateVersions = ref<any[]>([])
const templatePreviewSnapshot = ref<any>({})
const templateForm = reactive<any>({
    template_id: 0,
    template_type: 'addition',
    template_name: '',
    template_version: 1,
    is_default: 1,
    status: 1,
    sort: 0,
    design_config: {},
})

const historyQuery = reactive<any>({
    report_month: '',
})
const historyPager = reactive<any>({
    page: 1,
    size: 15,
    count: 0,
    lists: [],
    loading: false,
})
const historyDetail = reactive<any>({
    visible: false,
    data: null,
})

const blockingIssues = computed(() => {
    return (previewData.value?.issues || []).filter((item: any) => Number(item.blocking || 0) === 1)
})

const warningIssues = computed(() => {
    return (previewData.value?.issues || []).filter((item: any) => Number(item.blocking || 0) !== 1)
})

const activeTemplateVersions = computed(() => {
    return templateVersions.value.filter((item: any) => Number(item.status || 0) === 1)
})

const previewIsCurrent = computed(() => {
    return Boolean(previewData.value && lastPreviewKey.value === buildPreviewKey())
})

const reloadAll = async () => {
    bootLoading.value = true
    try {
        await Promise.all([loadBaseOptions(), handlePreview(), loadMaterials(1), loadTemplateConfig(0), loadHistory(1)])
    } finally {
        bootLoading.value = false
    }
}

const loadBaseOptions = async () => {
    const [categories, staffs] = await Promise.all([
        monthlyReportCategoryOptions(),
        monthlyReportStaffOptions(),
    ])
    categoryOptions.value = Array.isArray(categories) ? categories : []
    staffOptions.value = Array.isArray(staffs) ? staffs : []
}

const handlePreview = async () => {
    previewLoading.value = true
    try {
        const currentPreviewKey = buildPreviewKey()
        const payloadDraft = previewData.value && lastPreviewKey.value === currentPreviewKey ? buildDraftPayload() : {}
        const data = await monthlyReportPreview({
            report_month: reportForm.report_month,
            category_ids: reportForm.category_ids,
            draft: payloadDraft,
            template_ids: buildTemplateIds(),
        })
        previewData.value = data || null
        lastPreviewKey.value = currentPreviewKey
        assignDraft(data?.draft || {})
    } finally {
        previewLoading.value = false
    }
}

const handleConfirm = async () => {
    if (!previewData.value) return
    if (!previewIsCurrent.value) {
        feedback.msgWarning('月份或分类已变化，请先重新生成预览')
        return
    }
    await feedback.confirm(`确定将 ${reportForm.report_month} 月报确认为最新版本吗？同月旧版本会进入历史。`)
    confirmLoading.value = true
    try {
        const data = await monthlyReportConfirm({
            report_month: reportForm.report_month,
            category_ids: reportForm.category_ids,
            draft: buildDraftPayload(),
            template_ids: buildTemplateIds(),
        })
        feedback.msgSuccess('月报已确认并生成图片')
        await Promise.all([loadHistory(1), handlePreview()])
        historyDetail.data = data
    } finally {
        confirmLoading.value = false
    }
}

const assignDraft = (value: any) => {
    draft.addition_count = Number(value?.addition_count || 0)
    draft.executed_count = Number(value?.executed_count || 0)
    draft.top_count = Number(value?.top_count || 0)
    draft.ranking_staffs = Array.isArray(value?.ranking_staffs) ? value.ranking_staffs.map((item: any) => ({ ...item })) : []
    draft.copywriting = {
        addition_title: value?.copywriting?.addition_title || 'ADDITION',
        addition_subtitle: value?.copywriting?.addition_subtitle || `${reportForm.report_month || ''}新增婚礼档期`,
        ranking_title: value?.copywriting?.ranking_title || `${reportForm.report_month || ''}共计执行`,
        top_title: value?.copywriting?.top_title || 'THE MOST',
        footer_note: value?.copywriting?.footer_note || '感谢您的选择',
    }
}

const buildDraftPayload = () => {
    if (!previewData.value) {
        return {}
    }
    const payload: any = {
        addition_count: Number(draft.addition_count || 0),
        executed_count: Number(draft.executed_count || 0),
        copywriting: { ...draft.copywriting },
    }
    payload.ranking_staffs = (draft.ranking_staffs || []).map((item: any, index: number) => ({
        staff_id: Number(item.staff_id || 0),
        staff_name: item.staff_name || item.chinese_name || '',
        count_raw: Number(item.count_raw || 0),
        rank: index + 1,
    }))
    return payload
}

const buildTemplateIds = () => {
    const snapshot = previewData.value?.template_snapshot || {}
    return {
        addition: Number(snapshot?.addition?.template_id || 0),
        ranking: Number(snapshot?.ranking?.template_id || 0),
        top: Number(snapshot?.top?.template_id || 0),
    }
}

const buildPreviewKey = () => {
    const categoryIds = Array.isArray(reportForm.category_ids)
        ? [...reportForm.category_ids].map((id: any) => Number(id || 0)).filter(Boolean).sort((a, b) => a - b)
        : []
    return JSON.stringify({
        report_month: reportForm.report_month || '',
        category_ids: categoryIds,
    })
}

const svgDataUri = (type: string) => {
    const svg = previewData.value?.svg?.[type] || ''
    return svg ? `data:image/svg+xml;charset=utf-8,${encodeURIComponent(svg)}` : ''
}

const previewCanvasStyle = (type: string) => {
    const canvas = previewData.value?.rendered_snapshots?.[type]?.design_config?.canvas || {}
    const width = Math.max(1, Number(canvas.width || 1080))
    const height = Math.max(1, Number(canvas.height || 1920))
    const scale = MONTHLY_REPORT_PREVIEW_WIDTH / width
    return {
        width: `${MONTHLY_REPORT_PREVIEW_WIDTH}px`,
        height: `${Math.round(height * scale)}px`,
    }
}

const syncRowCount = (row: any) => {
    row.count_raw = Number(row.count_raw || 0)
    row.count = String(Math.max(0, row.count_raw)).padStart(2, '0')
}

const moveDraftRow = (index: number, step: number) => {
    const nextIndex = index + step
    if (nextIndex < 0 || nextIndex >= draft.ranking_staffs.length) return
    const rows = draft.ranking_staffs
    const current = rows[index]
    rows.splice(index, 1)
    rows.splice(nextIndex, 0, current)
}

const removeDraftRow = (index: number) => {
    draft.ranking_staffs.splice(index, 1)
}

const appendStaffRow = () => {
    const staffId = Number(staffAppend.staff_id || 0)
    if (!staffId) {
        feedback.msgWarning('请选择要补入的人员')
        return
    }
    if (draft.ranking_staffs.some((item: any) => Number(item.staff_id || 0) === staffId)) {
        feedback.msgWarning('该人员已在榜单中')
        return
    }
    const staff = staffOptions.value.find((item: any) => Number(item.id || 0) === staffId)
    draft.ranking_staffs.push({
        staff_id: staffId,
        staff_name: staff?.name || '',
        chinese_name: staff?.name || '',
        english_name: '',
        photo_url: '',
        avatar_photo_url: '',
        half_body_photo_url: '',
        count_raw: Number(staffAppend.count || 1),
        count: String(Number(staffAppend.count || 1)).padStart(2, '0'),
        rank: draft.ranking_staffs.length + 1,
        material_complete: 0,
        ranking_material_complete: 0,
        top_material_complete: 0,
    })
    staffAppend.staff_id = undefined
    staffAppend.count = 1
}

const loadMaterials = async (page?: number) => {
    if (page) materialPager.page = page
    materialPager.loading = true
    try {
        const data = await monthlyReportMaterialLists({
            page_no: materialPager.page,
            page_size: materialPager.size,
            ...materialQuery,
        })
        materialPager.lists = data?.lists || []
        materialPager.count = Number(data?.count || data?.total || 0)
    } finally {
        materialPager.loading = false
    }
}

const resetMaterialQuery = () => {
    materialQuery.keyword = ''
    materialQuery.category_id = ''
    materialQuery.status = ''
    loadMaterials(1)
}

const openMaterialDialog = (row?: any) => {
    materialDialog.form = row
        ? {
            id: Number(row.id || 0),
            staff_id: Number(row.staff_id || 0),
            avatar_photo: row.avatar_photo || row.photo || '',
            half_body_photo: row.half_body_photo || row.photo || '',
            english_name: row.english_name || '',
            sort: Number(row.sort || 0),
            status: Number(row.status ?? 1),
        }
        : {
            id: 0,
            staff_id: undefined,
            avatar_photo: '',
            half_body_photo: '',
            english_name: '',
            sort: 0,
            status: 1,
        }
    materialDialog.visible = true
}

const saveMaterial = async () => {
    materialDialog.saving = true
    try {
        await monthlyReportMaterialSave(materialDialog.form)
        materialDialog.visible = false
        feedback.msgSuccess('素材已保存')
        await Promise.all([loadMaterials(), handlePreview()])
    } finally {
        materialDialog.saving = false
    }
}

const deleteMaterial = async (row: any) => {
    await feedback.confirm(`确定删除「${row.chinese_name || row.staff_name || '该素材'}」吗？`)
    await monthlyReportMaterialDelete({ id: row.id })
    feedback.msgSuccess('素材已删除')
    await Promise.all([loadMaterials(), handlePreview()])
}

const loadTemplateConfig = async (templateId = 0) => {
    templateLoading.value = true
    try {
        const data = await monthlyReportTemplateConfig({
            template_type: templateForm.template_type,
            template_id: templateId,
            include_disabled: 1,
        })
        assignTemplate(data || {})
        await refreshTemplatePreview()
    } finally {
        templateLoading.value = false
    }
}

const assignTemplate = (data: any) => {
    templateForm.template_id = Number(data?.template_id || data?.id || 0)
    templateForm.template_type = data?.template_type || templateForm.template_type
    templateForm.template_name = data?.template_name || ''
    templateForm.template_version = Number(data?.template_version || 1)
    templateForm.is_default = Number(data?.is_default ?? 1)
    templateForm.status = Number(data?.status ?? 1)
    templateForm.sort = Number(data?.sort || 0)
    templateForm.design_config = data?.design_config || {}
    templateVersions.value = Array.isArray(data?.versions) ? data.versions : []
}

const saveTemplate = async () => {
    templateSaving.value = true
    try {
        const data = await monthlyReportTemplateSave({
            template_id: templateForm.template_id,
            template_type: templateForm.template_type,
            template_name: templateForm.template_name,
            sort: templateForm.sort,
            is_default: templateForm.is_default,
            status: templateForm.status,
            design_config: templateForm.design_config,
        })
        assignTemplate(data || {})
        await refreshTemplatePreview()
        feedback.msgSuccess('模板已保存')
    } finally {
        templateSaving.value = false
    }
}

const createTemplate = async () => {
    templateSaving.value = true
    try {
        const data = await monthlyReportTemplateSave({
            template_id: 0,
            template_type: templateForm.template_type,
            template_name: `${currentTemplateTypeLabel()}模板`,
            design_config: templateForm.design_config,
        })
        assignTemplate(data || {})
        await refreshTemplatePreview()
        feedback.msgSuccess('模板已创建')
    } finally {
        templateSaving.value = false
    }
}

const copyTemplate = async () => {
    if (!templateForm.template_id) return
    const { value } = await feedback.prompt('请输入新模板名称', '复制模板', {
        inputValue: `${templateForm.template_name || '月报模板'} 副本`,
        inputValidator: (val: string) => Boolean(String(val || '').trim()),
        inputErrorMessage: '请输入模板名称',
    })
    const data = await monthlyReportTemplateCopy({
        template_id: templateForm.template_id,
        template_name: value,
    })
    assignTemplate(data || {})
    await refreshTemplatePreview()
    feedback.msgSuccess('模板已复制')
}

const setTemplateDefault = async () => {
    if (!templateForm.template_id) return
    const data = await monthlyReportTemplateSetDefault({ template_id: templateForm.template_id })
    assignTemplate(data || {})
    feedback.msgSuccess('已设为默认模板')
}

const disableTemplate = async () => {
    if (!templateForm.template_id) return
    await feedback.confirm(`确定停用「${templateForm.template_name || '当前模板'}」吗？`)
    const data = await monthlyReportTemplateDisable({ template_id: templateForm.template_id })
    assignTemplate(data || {})
    await refreshTemplatePreview()
    feedback.msgSuccess('模板已停用')
}

const refreshTemplatePreview = async () => {
    const data = await monthlyReportTemplatePreview({
        template_type: templateForm.template_type,
        report_month: reportForm.report_month,
        design_config: templateForm.design_config,
    })
    templatePreviewSnapshot.value = data?.rendered_snapshot || {}
}

const loadHistory = async (page?: number) => {
    if (page) historyPager.page = page
    historyPager.loading = true
    try {
        const data = await monthlyReportHistory({
            page_no: historyPager.page,
            page_size: historyPager.size,
            report_month: historyQuery.report_month,
        })
        historyPager.lists = data?.lists || []
        historyPager.count = Number(data?.count || data?.total || 0)
    } finally {
        historyPager.loading = false
    }
}

const resetHistoryQuery = () => {
    historyQuery.report_month = ''
    loadHistory(1)
}

const openHistoryDetail = async (row: any) => {
    const data = await monthlyReportDetail({ id: row.id })
    historyDetail.data = data
    historyDetail.visible = true
}

const regenerateAssets = async (row: any) => {
    await feedback.confirm(`确定重建 ${row.report_month} 第 ${row.version} 版图片吗？`)
    await monthlyReportRegenerateAssets({ id: row.id, snapshot_hash: row.snapshot_hash })
    feedback.msgSuccess('图片已重建')
    await loadHistory()
}

const handleTabChange = (tab: any) => {
    if (tab === 'materials') loadMaterials()
    if (tab === 'templates') loadTemplateConfig(templateForm.template_id)
    if (tab === 'history') loadHistory()
}

const currentTemplateTypeLabel = () => {
    return templateTypes.find((item) => item.value === templateForm.template_type)?.label || '月报'
}

const formatTime = (value: any) => {
    const time = Number(value || 0)
    if (!time) return '-'
    const date = new Date(time * 1000)
    const pad = (num: number) => String(num).padStart(2, '0')
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}`
}

function downloadImage(url: string, title = '月报图片') {
    if (!url) return
    const link = document.createElement('a')
    link.href = url
    link.download = `${title}.jpg`
    link.target = '_blank'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
}

async function copyLink(url: string) {
    if (!url) return
    try {
        await navigator.clipboard.writeText(url)
        feedback.msgSuccess('链接已复制')
    } catch (e) {
        feedback.msgWarning('当前浏览器不支持自动复制，请手动复制链接')
    }
}

function defaultReportMonth() {
    const date = new Date()
    date.setDate(1)
    date.setMonth(date.getMonth() - 1)
    const month = String(date.getMonth() + 1).padStart(2, '0')
    return `${date.getFullYear()}-${month}`
}

onMounted(async () => {
    await reloadAll()
})
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
        color: #1f2937;
    }

    &__desc {
        margin-top: 6px;
        color: #6b7280;
    }

    &__actions {
        display: flex;
        gap: 8px;
    }
}

.workspace-card {
    min-height: 720px;
}

.generate-toolbar,
.table-toolbar,
.template-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.template-toolbar__actions {
    display: flex;
    gap: 8px;
}

.issue-list {
    display: grid;
    gap: 4px;
}

.generate-grid {
    display: grid;
    grid-template-columns: minmax(440px, 520px) minmax(0, 1fr);
    gap: 18px;
}

.draft-panel,
.preview-panel,
.template-list,
.template-main {
    border: 1px solid #ebeef5;
    border-radius: 8px;
    padding: 16px;
    background: #fff;
}

.section-title {
    margin-bottom: 12px;
    color: #1f2937;
    font-size: 16px;
    font-weight: 600;
}

.stat-edit-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 14px;
}

.staff-add-row {
    display: flex;
    gap: 8px;
}

.staff-cell {
    display: flex;
    align-items: center;
    gap: 8px;
}

.muted {
    color: #909399;
    font-size: 12px;
}

.draft-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 12px;
}

.image-preview-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
}

.image-preview-card {
    border: 1px solid #ebeef5;
    border-radius: 8px;
    overflow: hidden;

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 12px;
        border-bottom: 1px solid #ebeef5;
        font-weight: 600;
    }

    &__body {
        height: 520px;
        padding: 12px;
        background: #f5f7fa;
        overflow: auto;
        text-align: center;
    }

    &__canvas {
        display: inline-block;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.12);

        img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #fff;
        }
    }
}

.material-photo {
    width: 54px;
    height: 54px;
    border-radius: 6px;
}

.material-photo--half {
    height: 72px;
}

.material-tags {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
}

.template-workspace {
    display: grid;
    grid-template-columns: 280px minmax(0, 1fr);
    gap: 16px;
}

.template-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    align-self: start;
}

.template-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    min-height: 58px;
    padding: 10px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    background: #fff;
    text-align: left;

    strong,
    em {
        display: block;
    }

    em {
        margin-top: 4px;
        color: #909399;
        font-size: 12px;
        font-style: normal;
    }

    &.is-active {
        border-color: #409eff;
        background: #ecf5ff;
    }

    &.is-disabled {
        opacity: 0.58;
    }

    &__tags {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
}

.history-images {
    display: flex;
    gap: 8px;

    .el-image {
        width: 56px;
        height: 78px;
        border-radius: 4px;
        background: #f5f7fa;
    }
}

.detail-meta {
    display: flex;
    gap: 8px;
}

.asset-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
}

:deep(.asset-preview) {
    border: 1px solid #ebeef5;
    border-radius: 8px;
    overflow: hidden;
    background: #fff;
}

:deep(.asset-preview__title) {
    padding: 10px 12px;
    border-bottom: 1px solid #ebeef5;
    font-weight: 600;
}

:deep(.asset-preview img) {
    display: block;
    width: 100%;
    max-height: 520px;
    object-fit: contain;
    background: #f5f7fa;
}

:deep(.asset-preview__empty) {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 240px;
    color: #909399;
    background: #f5f7fa;
}

:deep(.asset-preview__actions) {
    display: flex;
    gap: 8px;
    padding: 10px 12px;
}

:deep(.asset-preview__actions button) {
    height: 28px;
    padding: 0 10px;
    border: 1px solid #dcdfe6;
    border-radius: 4px;
    background: #fff;
    color: #606266;
}

@media (max-width: 1280px) {
    .generate-grid,
    .template-workspace {
        grid-template-columns: 1fr;
    }

    .image-preview-grid,
    .asset-grid {
        grid-template-columns: 1fr;
    }
}
</style>
