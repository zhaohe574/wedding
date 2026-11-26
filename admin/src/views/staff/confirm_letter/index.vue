<template>
    <div class="confirm-letter-hub">
        <div class="confirm-letter-hub__stats">
            <div
                v-for="stat in statCards"
                :key="stat.key"
                class="confirm-letter-hub__stat"
                :class="{ 'is-active': activeStatKey === stat.key, 'is-danger': stat.danger }"
                @click="applyStat(stat.key)"
            >
                <span class="confirm-letter-hub__stat-label">{{ stat.label }}</span>
                <strong class="confirm-letter-hub__stat-value" :class="{ 'is-danger': stat.danger }">
                    {{ stat.value }}
                </strong>
                <span class="confirm-letter-hub__stat-hint" :class="{ 'is-danger': stat.danger }">
                    {{ stat.hint }}
                </span>
            </div>
        </div>

        <div class="confirm-letter-hub__switch">
            <button
                v-for="view in VIEWS"
                :key="view.value"
                type="button"
                class="confirm-letter-hub__switch-item"
                :class="{ 'is-active': activeView === view.value }"
                @click="switchView(view.value)"
            >
                {{ view.label }}
            </button>
        </div>

        <!-- 视图 A：人员模板配置 -->
        <div v-if="activeView === 'templates'" class="confirm-letter-hub__panel">
            <search-panel>
                <el-form class="mb-[-16px]" :inline="true" :model="staffQuery">
                    <el-form-item class="w-[260px]" label="关键词">
                        <el-input
                            v-model="staffQuery.keyword"
                            clearable
                            placeholder="姓名、工号、手机号或模板名"
                            @keyup.enter="resetStaffPage"
                        />
                    </el-form-item>
                    <el-form-item class="w-[170px]" label="服务分类">
                        <el-select v-model="staffQuery.category_id" clearable filterable placeholder="全部">
                            <el-option
                                v-for="item in categoryOptions"
                                :key="item.id"
                                :label="item.name"
                                :value="Number(item.id)"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[180px]" label="配置状态">
                        <el-select v-model="staffQuery.configured" clearable placeholder="全部">
                            <el-option label="已初始化模板" value="1" />
                            <el-option label="尚未配置" value="0" />
                        </el-select>
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" @click="resetStaffPage">查询</el-button>
                        <el-button @click="handleStaffReset">重置</el-button>
                    </el-form-item>
                </el-form>
            </search-panel>

            <el-alert
                v-if="staffListError"
                class="mt-4"
                type="error"
                :title="staffListError"
                :closable="false"
                show-icon
            >
                <template #default>
                    <el-button type="primary" link @click="getStaffLists()">重试</el-button>
                </template>
            </el-alert>

            <admin-table-toolbar class="mt-4" :hint="staffToolbarHint">
                <template #right>
                    <el-button :loading="staffPager.loading" @click="refreshAll">刷新</el-button>
                </template>
            </admin-table-toolbar>

            <el-table v-loading="staffPager.loading" :data="staffPager.lists" border>
                <el-table-column label="服务人员" min-width="180">
                    <template #default="{ row }">
                        <div class="staff-cell">
                            <el-avatar :src="row.avatar" :size="34">
                                {{ String(row.staff_name || '?').slice(0, 1) }}
                            </el-avatar>
                            <div class="staff-cell__main">
                                <div class="cell-main">{{ row.staff_name || '-' }}</div>
                                <div class="cell-sub">{{ row.staff_sn || '-' }}</div>
                            </div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="服务分类" width="130">
                    <template #default="{ row }">{{ row.category_name || '-' }}</template>
                </el-table-column>
                <el-table-column label="启用 / 停用模板" width="150">
                    <template #default="{ row }">
                        <div class="cell-main">
                            启用 <strong>{{ Number(row.active_template_count || 0) }}</strong>
                        </div>
                        <div class="cell-sub">停用 {{ Number(row.disabled_template_count || 0) }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="默认模板与版本" min-width="180">
                    <template #default="{ row }">
                        <template v-if="row.default_config_id">
                            <div class="cell-main">{{ row.default_template_name || '未命名模板' }}</div>
                            <el-tag class="mt-1" size="small" effect="plain" type="info">
                                V{{ row.default_template_version || 1 }}
                            </el-tag>
                        </template>
                        <span v-else class="cell-sub">尚未配置模板</span>
                    </template>
                </el-table-column>
                <el-table-column label="累计 / 有效出图" width="140">
                    <template #default="{ row }">
                        <div class="cell-main">{{ Number(row.letter_count || 0) }} 张</div>
                        <div class="cell-sub">有效 {{ Number(row.active_letter_count || 0) }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="最近出图 / 更新时间" width="190">
                    <template #default="{ row }">
                        <div class="cell-main">{{ row.last_generate_time_text || '暂无出图' }}</div>
                        <div class="cell-sub">更新 {{ row.last_config_update_time_text || '-' }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="180" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="openDesigner(row)">设计模板</el-button>
                        <el-button type="success" link @click="viewStaffRecords(row)">查看出图</el-button>
                    </template>
                </el-table-column>
            </el-table>

            <div class="flex justify-end mt-4">
                <pagination v-model="staffPager" @change="getStaffLists" />
            </div>
        </div>

        <!-- 视图 B：出图记录审计 -->
        <div v-if="activeView === 'records'" class="confirm-letter-hub__panel">
            <search-panel>
                <el-form class="mb-[-16px]" :inline="true" :model="recordQuery">
                    <el-form-item class="w-[240px]" label="关键词">
                        <el-input
                            v-model="recordQuery.keyword"
                            clearable
                            placeholder="订单号、人员、客户称呼或模板名"
                            @keyup.enter="resetRecordPage"
                        />
                    </el-form-item>
                    <el-form-item class="w-[150px]" label="服务人员">
                        <el-select v-model="recordQuery.staff_id" clearable filterable placeholder="全部">
                            <el-option
                                v-for="item in staffOptions"
                                :key="item.id"
                                :label="item.name"
                                :value="Number(item.id)"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[150px]" label="来源">
                        <el-select v-model="recordQuery.source" clearable placeholder="全部">
                            <el-option label="平台订单" value="platform" />
                            <el-option label="线下档期" value="manual" />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[160px]" label="生成端">
                        <el-select v-model="recordQuery.generate_source" clearable placeholder="全部">
                            <el-option label="服务人员" value="staff" />
                            <el-option label="后台代生成" value="admin" />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[140px]" label="状态">
                        <el-select v-model="recordQuery.is_outdated" clearable placeholder="全部">
                            <el-option label="当前有效" value="0" />
                            <el-option label="已失效" value="1" />
                        </el-select>
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" @click="resetRecordPage">查询</el-button>
                        <el-button @click="handleRecordReset">重置</el-button>
                    </el-form-item>
                </el-form>
            </search-panel>

            <el-alert
                v-if="recordListError"
                class="mt-4"
                type="error"
                :title="recordListError"
                :closable="false"
                show-icon
            >
                <template #default>
                    <el-button type="primary" link @click="getRecordLists()">重试</el-button>
                </template>
            </el-alert>

            <admin-table-toolbar class="mt-4" :hint="recordToolbarHint">
                <template #left>
                    <div class="quick-views">
                        <button
                            v-for="view in QUICK_VIEWS"
                            :key="view.value"
                            type="button"
                            class="quick-views__item"
                            :class="{ 'is-active': activeQuickView === view.value }"
                            @click="applyQuickView(view.value)"
                        >
                            {{ view.label }}
                        </button>
                    </div>
                </template>
                <template #right>
                    <el-button :loading="recordPager.loading" @click="refreshAll">刷新</el-button>
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
                <el-table-column label="订单 / 线下档期" min-width="200">
                    <template #default="{ row }">
                        <div class="cell-main">{{ recordSourceTitle(row) }}</div>
                        <div class="cell-sub">服务日期 {{ row.service_date_label || row.confirm_date || '-' }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="客户与服务项目" min-width="190">
                    <template #default="{ row }">
                        <div class="cell-main">{{ row.customer_alias || '脱敏客户' }}</div>
                        <div class="cell-sub">{{ row.service_name || '-' }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="归属人员" width="120">
                    <template #default="{ row }">{{ row.staff_name || '-' }}</template>
                </el-table-column>
                <el-table-column label="模板名称与版本" min-width="180">
                    <template #default="{ row }">
                        <div class="cell-main">{{ row.config_name || '历史配置' }}</div>
                        <div class="cell-sub">
                            模板 V{{ row.config_template_version || 1 }} · 第 {{ row.version || 1 }} 版
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="状态与生成端" width="170">
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
                <el-table-column label="操作" width="200" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="openRecordDetail(row)">详情</el-button>
                        <el-button
                            type="primary"
                            link
                            :disabled="!row.full_image_url"
                            @click="downloadRecord(row)"
                        >
                            下载原图
                        </el-button>
                        <el-tooltip
                            :disabled="row.source === 'platform'"
                            content="线下档期确认函请在服务人员工作台重建，后台审计仅支持详情与下载"
                            placement="top"
                        >
                            <span>
                                <el-button
                                    type="warning"
                                    link
                                    :disabled="row.source !== 'platform'"
                                    :loading="regeneratingId === Number(row.letter_id || 0)"
                                    @click="rebuildRecord(row)"
                                >
                                    重建图片
                                </el-button>
                            </span>
                        </el-tooltip>
                    </template>
                </el-table-column>
            </el-table>

            <div class="flex justify-end mt-4">
                <pagination v-model="recordPager" @change="getRecordLists" />
            </div>
        </div>

        <StaffTemplateDesignerDrawer
            v-model="designerVisible"
            :staff-id="designerStaffId"
            :staff-name="designerStaffName"
            @changed="handleDesignerChanged"
        />

        <PosterRecordDetailDrawer
            v-model="recordDetailVisible"
            :record="activeRecord"
            @regenerated="handleRecordRegenerated"
        />
    </div>
</template>

<script lang="ts" setup name="staffConfirmLetterHub">
/**
 * 档期确认函运营 Hub：模板配置率 / 出图审计 / 渲染链路健康度集中到一页。
 * 挂在 AdminHubTabs 内，不嵌套 admin-page-shell；KPI 卡点击携带预设筛选切子视图。
 */
import { orderConfirmLetterAssets } from '@/api/order'
import { categoryAll } from '@/api/service'
import {
    staffAll,
    staffScheduleConfirmLetterOverview,
    staffScheduleConfirmLetterRecordLists,
    staffScheduleConfirmLetterStaffLists
} from '@/api/staff'
import { hasPermission } from '@/utils/perm'
import { usePaging } from '@/hooks/usePaging'
import { getMenuPath } from '@/router'

import PosterRecordDetailDrawer from './components/PosterRecordDetailDrawer.vue'
import StaffTemplateDesignerDrawer from './components/StaffTemplateDesignerDrawer.vue'

type HubView = 'templates' | 'records'
type QuickViewValue = 'all' | 'current' | 'platform' | 'manual' | 'outdated'

interface ConfirmLetterOverview {
    staff_total: number
    configured_staff_count: number
    customized_staff_count: number
    active_template_count: number
    total_letter_count: number
    active_letter_count: number
    month_letter_count: number
    platform_letter_count: number
    manual_letter_count: number
    qrcode_configured: number
    qrcode_image_url: string
    imagick_loaded: number
    font_ready: number
    font_message: string
}

const VIEWS: Array<{ value: HubView; label: string }> = [
    { value: 'templates', label: '人员模板配置' },
    { value: 'records', label: '出图记录审计' }
]

const QUICK_VIEWS: Array<{ value: QuickViewValue; label: string }> = [
    { value: 'all', label: '全部' },
    { value: 'current', label: '当前有效' },
    { value: 'platform', label: '平台订单' },
    { value: 'manual', label: '线下档期' },
    { value: 'outdated', label: '已失效' }
]

const createEmptyOverview = (): ConfirmLetterOverview => ({
    staff_total: 0,
    configured_staff_count: 0,
    customized_staff_count: 0,
    active_template_count: 0,
    total_letter_count: 0,
    active_letter_count: 0,
    month_letter_count: 0,
    platform_letter_count: 0,
    manual_letter_count: 0,
    qrcode_configured: 0,
    qrcode_image_url: '',
    imagick_loaded: 0,
    font_ready: 0,
    font_message: ''
})

const router = useRouter()
const activeView = ref<HubView>('templates')
const activeStatKey = ref('')
const overview = reactive<ConfirmLetterOverview>(createEmptyOverview())

const categoryOptions = ref<any[]>([])
const staffOptions = ref<any[]>([])
const designerVisible = ref(false)
const designerStaffId = ref(0)
const designerStaffName = ref('')
const recordDetailVisible = ref(false)
const activeRecord = ref<any>(null)
const regeneratingId = ref(0)

const staffQuery = reactive({
    keyword: '',
    category_id: '' as number | '',
    configured: '' as '' | '0' | '1'
})
const recordQuery = reactive({
    keyword: '',
    staff_id: '' as number | '',
    source: '',
    generate_source: '',
    is_outdated: ''
})

const staffListError = ref('')
const recordListError = ref('')

const applyOverview = (data: any) => {
    if (!data) {
        return
    }
    Object.assign(overview, createEmptyOverview(), data)
}

const refreshOverview = async () => {
    try {
        const data = await staffScheduleConfirmLetterOverview()
        applyOverview(data)
    } catch {
        // 列表接口会同步返回 overview，单次失败不打断页面
    }
}

const fetchStaffTemplates = async (params: Record<string, any>) => {
    staffListError.value = ''
    try {
        const res: any = await staffScheduleConfirmLetterStaffLists(params)
        applyOverview(res?.overview)
        return res && Array.isArray(res.lists) ? res : { lists: [], count: 0 }
    } catch {
        staffListError.value = '人员模板列表加载失败，请稍后重试'
        return { lists: [], count: 0 }
    }
}

const fetchRecordLists = async (params: Record<string, any>) => {
    recordListError.value = ''
    try {
        const res: any = await staffScheduleConfirmLetterRecordLists(params)
        return res && Array.isArray(res.lists) ? res : { lists: [], count: 0 }
    } catch {
        recordListError.value = '出图记录加载失败，请稍后重试'
        return { lists: [], count: 0 }
    }
}

const { pager: staffPager, getLists: getStaffLists, resetPage: resetStaffPage } = usePaging({
    fetchFun: fetchStaffTemplates,
    params: staffQuery,
    firstLoading: true
})

const { pager: recordPager, getLists: getRecordLists, resetPage: resetRecordPage } = usePaging({
    fetchFun: fetchRecordLists,
    params: recordQuery,
    firstLoading: false
})

const renderHealthy = computed(
    () =>
        Number(overview.qrcode_configured || 0) === 1 &&
        Number(overview.imagick_loaded || 0) === 1 &&
        Number(overview.font_ready || 0) === 1
)

const missingRenderItems = computed(() => {
    const items: string[] = []
    if (Number(overview.qrcode_configured || 0) !== 1) {
        items.push('未配置统一二维码')
    }
    if (Number(overview.imagick_loaded || 0) !== 1) {
        items.push('Imagick 扩展未启用')
    }
    if (Number(overview.font_ready || 0) !== 1) {
        items.push(overview.font_message || '中文字体渲染未通过')
    }
    return items
})

const statCards = computed(() => [
    {
        key: 'configured',
        label: '已配置模板人员',
        value: `${Number(overview.configured_staff_count || 0)} / ${Number(overview.staff_total || 0)}`,
        hint: `自定义多模板 ${Number(overview.customized_staff_count || 0)} 人`,
        danger: false
    },
    {
        key: 'templates',
        label: '启用模板总数',
        value: String(Number(overview.active_template_count || 0)),
        hint: '含默认及多版本海报模板',
        danger: false
    },
    {
        key: 'activeLetters',
        label: '当前有效确认函',
        value: String(Number(overview.active_letter_count || 0)),
        hint: `累计出图 ${Number(overview.total_letter_count || 0)} 张`,
        danger: false
    },
    {
        key: 'monthLetters',
        label: '本月累计出图',
        value: String(Number(overview.month_letter_count || 0)),
        hint: `平台 ${Number(overview.platform_letter_count || 0)} · 线下 ${Number(overview.manual_letter_count || 0)}`,
        danger: false
    },
    {
        key: 'health',
        label: '渲染链路健康度',
        value: renderHealthy.value ? '正常' : '待处理',
        hint: renderHealthy.value ? '二维码、Imagick 与中文字体均就绪' : missingRenderItems.value.join(' · '),
        danger: !renderHealthy.value
    }
])

const staffToolbarHint = computed(
    () => `共 ${staffPager.count || 0} 位服务人员 · 已配置 ${overview.configured_staff_count} 人`
)

const recordToolbarHint = computed(() => `共 ${recordPager.count || 0} 条出图记录`)

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

const switchView = (view: HubView) => {
    activeView.value = view
    if (view === 'templates' && !staffPager.lists.length) {
        getStaffLists()
    }
    if (view === 'records' && !recordPager.lists.length) {
        getRecordLists()
    }
}

const goPosterSettings = () => {
    router.push({
        path: getMenuPath('page.setting.website.hub') || '/setting/brand-site',
        query: { tab: 'poster' }
    })
}

const applyStat = (key: string) => {
    activeStatKey.value = key
    if (key === 'health') {
        goPosterSettings()
        return
    }
    if (key === 'configured') {
        activeView.value = 'templates'
        staffQuery.configured = '1'
        staffQuery.keyword = ''
        staffQuery.category_id = ''
        resetStaffPage()
        return
    }
    if (key === 'templates') {
        activeView.value = 'templates'
        staffQuery.configured = ''
        staffQuery.keyword = ''
        staffQuery.category_id = ''
        resetStaffPage()
        return
    }
    activeView.value = 'records'
    recordQuery.keyword = ''
    recordQuery.staff_id = ''
    recordQuery.source = ''
    recordQuery.generate_source = ''
    recordQuery.is_outdated = key === 'activeLetters' ? '0' : ''
    resetRecordPage()
}

const applyQuickView = (view: QuickViewValue) => {
    if (view === 'all') {
        recordQuery.source = ''
        recordQuery.is_outdated = ''
    } else if (view === 'current') {
        recordQuery.source = ''
        recordQuery.is_outdated = '0'
    } else if (view === 'platform') {
        recordQuery.source = 'platform'
        recordQuery.is_outdated = ''
    } else if (view === 'manual') {
        recordQuery.source = 'manual'
        recordQuery.is_outdated = ''
    } else {
        recordQuery.source = ''
        recordQuery.is_outdated = '1'
    }
    activeStatKey.value = ''
    resetRecordPage()
}

const handleStaffReset = () => {
    staffQuery.keyword = ''
    staffQuery.category_id = ''
    staffQuery.configured = ''
    activeStatKey.value = ''
    resetStaffPage()
}

const handleRecordReset = () => {
    recordQuery.keyword = ''
    recordQuery.staff_id = ''
    recordQuery.source = ''
    recordQuery.generate_source = ''
    recordQuery.is_outdated = ''
    activeStatKey.value = ''
    resetRecordPage()
}

const refreshAll = () => {
    refreshOverview()
    if (activeView.value === 'templates') {
        getStaffLists()
    } else {
        getRecordLists()
    }
}

const loadCategoryOptions = async () => {
    try {
        const res: any = await categoryAll()
        categoryOptions.value = Array.isArray(res) ? res : Array.isArray(res?.data) ? res.data : []
    } catch {
        categoryOptions.value = []
    }
}

const loadStaffOptions = async () => {
    try {
        const res: any = await staffAll()
        staffOptions.value = Array.isArray(res) ? res : Array.isArray(res?.data) ? res.data : []
    } catch {
        staffOptions.value = []
    }
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

const openDesigner = (row: any) => {
    designerStaffId.value = Number(row?.staff_id || 0)
    designerStaffName.value = String(row?.staff_name || '')
    designerVisible.value = true
}

const handleDesignerChanged = () => {
    refreshOverview()
    getStaffLists()
    getRecordLists()
}

const viewStaffRecords = (row: any) => {
    activeView.value = 'records'
    recordQuery.staff_id = Number(row?.staff_id || 0) || ''
    recordQuery.keyword = ''
    recordQuery.source = ''
    recordQuery.generate_source = ''
    recordQuery.is_outdated = ''
    activeStatKey.value = ''
    resetRecordPage()
}

const openRecordDetail = (row: any) => {
    activeRecord.value = row
    recordDetailVisible.value = true
}

const rebuildRecord = async (row: any) => {
    if (row?.source !== 'platform') {
        return
    }
    regeneratingId.value = Number(row.letter_id || 0)
    try {
        await orderConfirmLetterAssets({
            letter_id: Number(row.letter_id || 0),
            staff_id: Number(row.staff_id || 0),
            snapshot_hash: String(row.snapshot_hash || '')
        })
        await Promise.all([getRecordLists(), refreshOverview()])
    } finally {
        regeneratingId.value = 0
    }
}

const handleRecordRegenerated = () => {
    getRecordLists()
    refreshOverview()
}

onMounted(() => {
    refreshOverview()
    loadCategoryOptions()
    loadStaffOptions()
    if (hasPermission(['ops.staff/lists'])) {
        getStaffLists()
    }
})
</script>

<style scoped lang="scss">
.confirm-letter-hub {
    min-width: 0;

    &__stats {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
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

        &:hover,
        &.is-active {
            border-color: var(--el-color-primary);
            background: var(--el-color-primary-light-9);
        }

        &.is-danger {
            border-color: var(--el-color-danger-light-5);

            &:hover,
            &.is-active {
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

        &.is-danger {
            color: var(--el-color-danger);
        }
    }

    &__stat-hint {
        color: var(--el-text-color-secondary);
        font-size: 12px;
        line-height: 1.4;

        &.is-danger {
            color: var(--el-color-danger);
        }
    }

    &__switch {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin: 16px 0;
        padding: 4px;
        border-radius: 999px;
        background: var(--el-fill-color-light);
    }

    &__switch-item {
        padding: 6px 16px;
        border: none;
        border-radius: 999px;
        background: transparent;
        color: var(--el-text-color-secondary);
        font-size: 13px;
        line-height: 1.4;
        cursor: pointer;
        transition:
            color 0.2s ease,
            background 0.2s ease;

        &.is-active {
            background: var(--el-bg-color);
            color: var(--el-color-primary);
            font-weight: 600;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
        }
    }

    &__panel {
        min-width: 0;
    }
}

.staff-cell {
    display: flex;
    align-items: center;
    gap: 10px;

    &__main {
        min-width: 0;
    }
}

.cell-main {
    font-size: 13px;
    font-weight: 500;
    line-height: 1.5;
    color: var(--el-text-color-primary);
}

.cell-sub {
    margin-top: 2px;
    font-size: 12px;
    line-height: 1.5;
    color: var(--el-text-color-secondary);
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

.quick-views {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;

    &__item {
        padding: 5px 12px;
        border: 1px solid var(--el-border-color-light);
        border-radius: 999px;
        background: var(--el-bg-color);
        color: var(--el-text-color-secondary);
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

@media (max-width: 1400px) {
    .confirm-letter-hub__stats {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 900px) {
    .confirm-letter-hub__stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
