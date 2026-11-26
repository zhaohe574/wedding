<template>
    <div class="staff-questionnaire-editor" :class="{ 'is-embedded': embedded }">
        <div v-if="staffId <= 0" class="editor-placeholder">
            <el-empty description="请选择服务人员后再配置问卷" :image-size="96" />
        </div>

        <div v-else v-loading="loading" class="editor-body">
            <el-alert
                v-if="loadError"
                class="editor-alert"
                type="error"
                :title="loadError"
                :closable="false"
                show-icon
            >
                <template #default>
                    <el-button type="primary" link @click="loadDetail()">重试</el-button>
                </template>
            </el-alert>

            <template v-else>
                <section class="editor-summary">
                    <div class="editor-summary__identity">
                        <div class="editor-summary__avatar">{{ staffInitial }}</div>
                        <div class="editor-summary__identity-main">
                            <div class="editor-summary__name">{{ currentStaffName }}</div>
                            <div class="editor-summary__meta">
                                工号 {{ staffSn || '-' }}
                            </div>
                        </div>
                    </div>

                    <div class="editor-summary__stats">
                        <div class="editor-summary__stat">
                            <span class="editor-summary__stat-label">发布版本</span>
                            <el-tag
                                :type="latestVersionNo > 0 ? 'success' : 'info'"
                                effect="plain"
                                size="small"
                            >
                                {{ latestVersionNo > 0 ? `v${latestVersionNo}` : '未发布' }}
                            </el-tag>
                        </div>
                        <div class="editor-summary__stat">
                            <span class="editor-summary__stat-label">草稿题目</span>
                            <strong>{{ form.questions.length }}</strong>
                        </div>
                        <div class="editor-summary__stat">
                            <span class="editor-summary__stat-label">待填写任务</span>
                            <strong>{{ pendingTaskCount }}</strong>
                        </div>
                    </div>

                    <div class="editor-summary__actions">
                        <el-button v-if="embedded" size="small" @click="emit('close')">
                            关闭
                        </el-button>
                        <el-button size="small" :loading="loading" @click="loadDetail()">
                            刷新配置
                        </el-button>
                        <el-button
                            v-perms="['growth.coupleQuestionnaire/save']"
                            size="small"
                            :loading="saving"
                            @click="saveDraft"
                        >
                            保存草稿
                        </el-button>
                        <el-tooltip
                            content="仅对后续新生成的订单问卷生效；已发送任务保留原版本快照。"
                            placement="top"
                            :popper-style="labelTipStyle"
                        >
                            <el-button
                                v-perms="['growth.coupleQuestionnaire/publish']"
                                size="small"
                                type="primary"
                                :loading="publishing"
                                @click="publishVersion"
                            >
                                发布新版
                            </el-button>
                        </el-tooltip>
                    </div>
                </section>

                <el-row :gutter="16" class="editor-grid">
                    <el-col :xs="24" :lg="8">
                        <section class="editor-panel">
                            <header class="editor-panel__head">
                                <div class="editor-panel__title-row">
                                    <span class="editor-panel__title">问卷设置</span>
                                    <el-tag
                                        :type="latestVersionNo > 0 ? 'success' : 'info'"
                                        effect="plain"
                                        size="small"
                                    >
                                        {{ latestVersionNo > 0 ? `当前 v${latestVersionNo}` : '尚未发布' }}
                                    </el-tag>
                                </div>
                            </header>

                            <el-form label-position="top" class="editor-form">
                                <el-form-item label="问卷标题" required>
                                    <el-input
                                        v-model="form.title"
                                        maxlength="120"
                                        show-word-limit
                                        placeholder="例如：新人婚礼资料问卷"
                                    />
                                </el-form-item>
                                <el-form-item label="问卷说明">
                                    <el-input
                                        v-model="form.description"
                                        type="textarea"
                                        :rows="4"
                                        maxlength="500"
                                        show-word-limit
                                        placeholder="向新人说明填写用途、预计耗时与隐私范围"
                                    />
                                </el-form-item>
                                <el-form-item label="推送方式">
                                    <el-radio-group v-model="form.push_mode" class="push-mode-group">
                                        <el-radio
                                            v-for="option in PUSH_MODE_OPTIONS"
                                            :key="option.value"
                                            :value="option.value"
                                            class="push-mode-option"
                                        >
                                            <span class="push-mode-option__title">
                                                {{ option.label }}
                                            </span>
                                            <span class="push-mode-option__desc">
                                                {{
                                                    option.value === 1
                                                        ? '订单进入待服务后自动发送。'
                                                        : '在任务列表手动发送。'
                                                }}
                                            </span>
                                        </el-radio>
                                    </el-radio-group>
                                </el-form-item>
                                <el-form-item v-if="Number(form.push_mode) === 1" label="推送时间设置">
                                    <div class="auto-push">
                                        <div class="auto-push__block">
                                            <span class="auto-push__label">触发条件</span>
                                            <el-radio-group
                                                v-model="form.auto_push_trigger"
                                                class="auto-push__triggers"
                                                @change="handleTriggerChange"
                                            >
                                                <el-radio
                                                    v-for="option in AUTO_PUSH_TRIGGER_OPTIONS"
                                                    :key="option.value"
                                                    :value="option.value"
                                                    class="auto-push__trigger"
                                                >
                                                    <span class="auto-push__trigger-title">
                                                        {{ option.label }}
                                                    </span>
                                                    <span class="auto-push__trigger-desc">
                                                        {{ option.desc }}
                                                    </span>
                                                </el-radio>
                                            </el-radio-group>
                                        </div>
                                        <template
                                            v-if="
                                                form.auto_push_trigger === 2 ||
                                                form.auto_push_trigger === 3
                                            "
                                        >
                                            <div class="auto-push__block">
                                                <span class="auto-push__label">条件天数</span>
                                                <div class="auto-push__days">
                                                    <el-input-number
                                                        v-model="form.auto_push_days"
                                                        :min="1"
                                                        :max="
                                                            form.auto_push_trigger === 2 ? 365 : 90
                                                        "
                                                        :step="1"
                                                        controls-position="right"
                                                    />
                                                    <span class="auto-push__unit">天</span>
                                                </div>
                                                <div class="auto-push__presets">
                                                    <button
                                                        v-for="day in dayPresets"
                                                        :key="day"
                                                        type="button"
                                                        class="auto-push__preset"
                                                        :class="{
                                                            'is-active':
                                                                Number(form.auto_push_days) === day
                                                        }"
                                                        @click="form.auto_push_days = day"
                                                    >
                                                        {{ day }} 天
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="auto-push__block">
                                                <span class="auto-push__label">推送时间点</span>
                                                <el-time-select
                                                    v-model="form.auto_push_time"
                                                    start="07:00"
                                                    step="00:30"
                                                    end="22:00"
                                                    :clearable="false"
                                                    class="w-full"
                                                />
                                            </div>
                                        </template>
                                        <div class="auto-push__preview">
                                            <span class="auto-push__preview-label">生效规则</span>
                                            <span class="auto-push__preview-text">
                                                {{ pushSchedulePreview }}
                                            </span>
                                            <p
                                                v-if="
                                                    form.auto_push_trigger === 2 ||
                                                    form.auto_push_trigger === 3
                                                "
                                                class="auto-push__note"
                                            >
                                                若客户下单时已过计划时间或订单未填服务日期，将在支付后立即发送；订单改期后未发送任务会自动重算。
                                            </p>
                                        </div>
                                    </div>
                                </el-form-item>
                                <el-form-item label="启用状态">
                                    <el-switch
                                        v-model="form.status"
                                        :active-value="1"
                                        :inactive-value="0"
                                        active-text="启用"
                                        inactive-text="停用"
                                    />
                                </el-form-item>
                            </el-form>
                        </section>
                    </el-col>

                    <el-col :xs="24" :lg="16">
                        <section class="editor-panel">
                            <header class="editor-panel__head editor-panel__head--wrap">
                                <div class="editor-panel__title-row">
                                    <span class="editor-panel__title">题目编排</span>
                                    <el-tag type="info" effect="plain" size="small">
                                        {{ form.questions.length }} 题
                                    </el-tag>
                                </div>
                                <div class="editor-panel__actions">
                                    <el-button size="small" @click="openBankDialog">
                                        从题库挑选
                                    </el-button>
                                    <el-button size="small" @click="addBlankQuestion">
                                        新增空白题
                                    </el-button>
                                    <el-button
                                        v-perms="['growth.coupleQuestionnaire/save']"
                                        size="small"
                                        :loading="saving"
                                        @click="saveDraft"
                                    >
                                        保存草稿
                                    </el-button>
                                    <el-tooltip
                                        content="仅对后续新生成的订单问卷生效；已发送任务保留原版本快照。"
                                        placement="top"
                                        :popper-style="labelTipStyle"
                                    >
                                        <el-button
                                            v-perms="['growth.coupleQuestionnaire/publish']"
                                            size="small"
                                            type="primary"
                                            :loading="publishing"
                                            @click="publishVersion"
                                        >
                                            发布新版
                                        </el-button>
                                    </el-tooltip>
                                </div>
                            </header>

                            <div class="editor-mode">
                                <el-radio-group v-model="mode" size="small">
                                    <el-radio-button value="edit">编辑题目</el-radio-button>
                                    <el-radio-button value="preview">新人端预览</el-radio-button>
                                </el-radio-group>
                            </div>

                            <div v-if="mode === 'edit'" class="question-list">
                                <el-empty
                                    v-if="!form.questions.length"
                                    description="暂无题目，可新增空白题或从题库挑选"
                                    :image-size="88"
                                />
                                <article
                                    v-for="(item, index) in form.questions"
                                    :key="item.local_id"
                                    class="question-card"
                                >
                                    <header class="question-card__head">
                                        <span class="question-card__index">
                                            Q{{ String(index + 1).padStart(2, '0') }}
                                        </span>
                                        <el-tag effect="plain" size="small">
                                            {{ getQuestionTypeLabel(item.type, questionTypes) }}
                                        </el-tag>
                                        <el-tag
                                            v-if="Number(item.required) === 1"
                                            type="danger"
                                            effect="plain"
                                            size="small"
                                        >
                                            必填
                                        </el-tag>
                                        <div class="question-card__actions">
                                            <el-button
                                                link
                                                size="small"
                                                :disabled="index === 0"
                                                @click="moveQuestion(index, -1)"
                                            >
                                                上移
                                            </el-button>
                                            <el-button
                                                link
                                                size="small"
                                                :disabled="index === form.questions.length - 1"
                                                @click="moveQuestion(index, 1)"
                                            >
                                                下移
                                            </el-button>
                                            <el-button link size="small" @click="duplicateQuestion(index)">
                                                复制一题
                                            </el-button>
                                            <el-button
                                                link
                                                size="small"
                                                type="danger"
                                                @click="removeQuestion(index)"
                                            >
                                                移除
                                            </el-button>
                                        </div>
                                    </header>

                                    <div class="question-card__grid">
                                        <div class="question-card__field">
                                            <span class="question-card__label">分类</span>
                                            <el-select
                                                v-model="item.category"
                                                filterable
                                                allow-create
                                                default-first-option
                                                placeholder="选择或输入分类"
                                            >
                                                <el-option
                                                    v-for="category in categoryOptions"
                                                    :key="category"
                                                    :label="category"
                                                    :value="category"
                                                />
                                            </el-select>
                                        </div>
                                        <div class="question-card__field">
                                            <span class="question-card__label">题型</span>
                                            <el-select
                                                v-model="item.type"
                                                @change="handleTypeChange(item)"
                                            >
                                                <el-option
                                                    v-for="type in questionTypes"
                                                    :key="type.value"
                                                    :label="type.label"
                                                    :value="type.value"
                                                />
                                            </el-select>
                                        </div>
                                        <div class="question-card__field question-card__field--full">
                                            <span class="question-card__label">题目标题</span>
                                            <el-input
                                                :ref="(el) => setTitleInput(el, index)"
                                                v-model="item.title"
                                                :class="{ 'is-invalid': invalidIndex === index }"
                                                maxlength="120"
                                                show-word-limit
                                                placeholder="例如：你们第一次见面的场景"
                                            />
                                        </div>
                                        <div class="question-card__field question-card__field--full">
                                            <span class="question-card__label">提示文案</span>
                                            <el-input
                                                v-model="item.placeholder"
                                                maxlength="120"
                                                placeholder="填写示例或补充说明（选填）"
                                            />
                                        </div>
                                        <div
                                            v-if="isChoiceType(item.type)"
                                            class="question-card__field question-card__field--full"
                                        >
                                            <span class="question-card__label">选项</span>
                                            <el-select
                                                v-model="item.options"
                                                multiple
                                                filterable
                                                allow-create
                                                default-first-option
                                                class="w-full"
                                                placeholder="输入选项后回车，至少两个"
                                            />
                                        </div>
                                        <div class="question-card__field question-card__field--full">
                                            <span class="question-card__label">是否必填</span>
                                            <el-switch
                                                v-model="item.required"
                                                :active-value="1"
                                                :inactive-value="0"
                                                active-text="新人必须填写"
                                            />
                                        </div>
                                    </div>
                                </article>
                            </div>

                            <div v-else class="question-preview">
                                <div class="question-preview__phone">
                                    <header class="question-preview__header">
                                        <h3>{{ form.title || '新人婚礼资料问卷' }}</h3>
                                        <p>
                                            {{
                                                form.description ||
                                                '请补充你们的故事与偏好，方便服务人员准备仪式。'
                                            }}
                                        </p>
                                    </header>
                                    <el-empty
                                        v-if="!form.questions.length"
                                        description="暂无题目可预览"
                                        :image-size="72"
                                    />
                                    <div
                                        v-for="group in previewGroups"
                                        :key="group.category"
                                        class="question-preview__group"
                                    >
                                        <div class="question-preview__category">
                                            {{ group.category }}
                                        </div>
                                        <div
                                            v-for="item in group.items"
                                            :key="item.local_id"
                                            class="question-preview__item"
                                        >
                                            <div class="question-preview__title">
                                                {{ item.title || '未填写题干' }}
                                                <span
                                                    v-if="Number(item.required) === 1"
                                                    class="question-preview__required"
                                                >
                                                    *
                                                </span>
                                            </div>
                                            <div
                                                v-if="item.placeholder"
                                                class="question-preview__placeholder"
                                            >
                                                {{ item.placeholder }}
                                            </div>
                                            <div
                                                v-if="isChoiceType(item.type)"
                                                class="question-preview__chips"
                                            >
                                                <span
                                                    v-for="option in item.options"
                                                    :key="option"
                                                    class="question-preview__chip"
                                                >
                                                    {{ option }}
                                                </span>
                                                <span
                                                    v-if="!item.options.length"
                                                    class="question-preview__hint"
                                                >
                                                    暂未配置选项
                                                </span>
                                            </div>
                                            <el-rate
                                                v-else-if="item.type === 'rating'"
                                                disabled
                                                :model-value="0"
                                            />
                                            <div
                                                v-else-if="item.type === 'textarea'"
                                                class="question-preview__textarea"
                                            >
                                                请输入...
                                            </div>
                                            <div v-else class="question-preview__input">请输入...</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </el-col>
                </el-row>
            </template>
        </div>

        <el-dialog
            v-model="bankDialogVisible"
            title="从题库挑选"
            width="760px"
            destroy-on-close
            append-to-body
            @closed="handleBankDialogClosed"
        >
            <div class="bank-toolbar">
                <el-input
                    v-model="bankKeyword"
                    class="bank-toolbar__search"
                    clearable
                    placeholder="搜索题目、分类或提示"
                />
                <el-select
                    v-model="bankCategory"
                    class="bank-toolbar__category"
                    clearable
                    placeholder="全部分类"
                >
                    <el-option
                        v-for="category in bankCategories"
                        :key="category"
                        :label="category"
                        :value="category"
                    />
                </el-select>
            </div>

            <el-table
                ref="bankTableRef"
                :data="filteredBankQuestions"
                height="360"
                border
                @selection-change="handleBankSelectionChange"
            >
                <el-table-column type="selection" width="46" />
                <el-table-column prop="category" label="分类" width="120" show-overflow-tooltip />
                <el-table-column label="题目" min-width="220">
                    <template #default="{ row }">
                        <div class="bank-question__title">{{ row.title }}</div>
                        <div class="bank-question__placeholder">
                            {{ row.placeholder || '暂无提示文案' }}
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="题型" width="90">
                    <template #default="{ row }">
                        {{ getQuestionTypeLabel(row.type, questionTypes) }}
                    </template>
                </el-table-column>
                <el-table-column label="必填" width="70">
                    <template #default="{ row }">
                        {{ Number(row.required) === 1 ? '是' : '否' }}
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="170">
                    <template #default="{ row }">
                        <div class="bank-question__actions">
                            <el-button link type="primary" @click="appendBankQuestion(row)">
                                追加
                            </el-button>
                            <el-tag
                                v-if="isBankQuestionAdded(row)"
                                size="small"
                                type="info"
                                effect="plain"
                            >
                                已选过（可再复制）
                            </el-tag>
                        </div>
                    </template>
                </el-table-column>
            </el-table>

            <template #footer>
                <div class="bank-footer">
                    <span class="bank-footer__hint">
                        已勾选 {{ selectedBankQuestions.length }} 道题目
                    </span>
                    <div class="bank-footer__actions">
                        <el-button @click="bankDialogVisible = false">取消</el-button>
                        <el-button
                            type="primary"
                            :disabled="!selectedBankQuestions.length"
                            @click="appendSelectedBankQuestions"
                        >
                            批量添加到问卷（{{ selectedBankQuestions.length }}）
                        </el-button>
                    </div>
                </div>
            </template>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { ElMessage, ElMessageBox } from 'element-plus'

import type {
    CoupleQuestionnaireConfigDetail,
    CoupleQuestionnaireSavePayload,
    QuestionBankItem,
    QuestionnaireOptionItem
} from '@/api/questionnaire'
import {
    coupleQuestionnaireDetail,
    coupleQuestionnairePublish,
    coupleQuestionnaireSave
} from '@/api/questionnaire'
import { staffAll } from '@/api/staff'

import type { EditableQuestionItem } from '../shared'
import {
    AFTER_PAID_DAY_PRESETS,
    AUTO_PUSH_TRIGGER_OPTIONS,
    BEFORE_SERVICE_DAY_PRESETS,
    DEFAULT_CATEGORIES,
    extractErrorMessage,
    formatPushScheduleSummary,
    getQuestionTypeLabel,
    normalizeEditableQuestion,
    PUSH_MODE_OPTIONS,
    QUESTION_TYPES,
    serializeEditableQuestions,
    validateAutoPushConfig,
    validateQuestionnaireForm
} from '../shared'

const props = withDefaults(
    defineProps<{
        staffId: number
        embedded?: boolean
    }>(),
    {
        embedded: false
    }
)

const emit = defineEmits<{
    (e: 'saved'): void
    (e: 'published'): void
    (e: 'loaded', data: any): void
    (e: 'close'): void
}>()

const loading = ref(false)
const loadError = ref('')
const saving = ref(false)
const publishing = ref(false)
const invalidIndex = ref(-1)
const mode = ref<string>('edit')
const labelTipStyle = { maxWidth: '320px', lineHeight: '1.6' }
const config = ref<CoupleQuestionnaireConfigDetail>({})
const staffOptions = ref<any[]>([])
const staffOptionsLoaded = ref(false)
const questionTypes = ref<QuestionnaireOptionItem[]>([...QUESTION_TYPES])
const customCategories = ref<string[]>([])

const form = reactive({
    title: '',
    description: '',
    push_mode: 1,
    status: 1,
    auto_push_trigger: 1,
    auto_push_days: 15,
    auto_push_time: '10:00',
    questions: [] as EditableQuestionItem[]
})

const bankDialogVisible = ref(false)
const bankKeyword = ref('')
const bankCategory = ref('')
const bankTableRef = ref<any>(null)
const selectedBankQuestions = ref<any[]>([])

const titleInputRefs = new Map<number, any>()
let loadToken = 0

const currentStaffName = computed(() => {
    const staff = config.value?.staff
    if (staff?.name) {
        return staff.sn ? `${staff.name}（${staff.sn}）` : staff.name
    }
    const fallback = staffOptions.value.find((item) => Number(item.id) === props.staffId)
    return fallback?.name ? `${fallback.name}` : `服务人员 #${props.staffId}`
})

const staffSn = computed(() => {
    const staff = config.value?.staff
    if (staff?.sn) {
        return staff.sn
    }
    const fallback = staffOptions.value.find((item) => Number(item.id) === props.staffId)
    return fallback?.sn ? String(fallback.sn) : ''
})

const staffInitial = computed(() => currentStaffName.value.slice(0, 1) || '问')

const latestVersionNo = computed(() =>
    Number(config.value?.latest_version?.version_no || config.value?.published_version_no || 0)
)

const pendingTaskCount = computed(() => Number(config.value?.pending_task_count || 0))

const categoryOptions = computed(() => {
    const categories = new Set<string>(DEFAULT_CATEGORIES)
    customCategories.value.forEach((category) => {
        if (category) {
            categories.add(String(category))
        }
    })
    config.value?.bank_questions?.forEach((item) => {
        if (item.category) {
            categories.add(String(item.category))
        }
    })
    form.questions.forEach((item) => {
        if (item.category) {
            categories.add(item.category)
        }
    })
    return Array.from(categories)
})

const bankQuestions = computed<QuestionBankItem[]>(() => {
    const source = Array.isArray(config.value?.bank_questions)
        ? config.value.bank_questions
        : []
    return source.map((item) => ({
        ...item,
        options: Array.isArray(item.options) ? item.options.map(String) : []
    }))
})

const bankCategories = computed(() => {
    const categories = new Set<string>()
    bankQuestions.value.forEach((item) => {
        if (item.category) {
            categories.add(String(item.category))
        }
    })
    return Array.from(categories)
})

const filteredBankQuestions = computed(() => {
    const keyword = bankKeyword.value.trim()
    return bankQuestions.value.filter((item) => {
        if (bankCategory.value && item.category !== bankCategory.value) {
            return false
        }
        if (!keyword) {
            return true
        }
        return `${item.category || ''}${item.title || ''}${item.placeholder || ''}`.includes(keyword)
    })
})

const previewGroups = computed(() => {
    const groups: Array<{ category: string; items: EditableQuestionItem[] }> = []
    form.questions.forEach((item) => {
        const category = item.category || '未分类'
        const group = groups.find((current) => current.category === category)
        if (group) {
            group.items.push(item)
        } else {
            groups.push({ category, items: [item] })
        }
    })
    return groups
})

const dayPresets = computed(() =>
    form.auto_push_trigger === 3 ? AFTER_PAID_DAY_PRESETS : BEFORE_SERVICE_DAY_PRESETS
)

const pushSchedulePreview = computed(() =>
    formatPushScheduleSummary(
        form.push_mode,
        form.auto_push_trigger,
        form.auto_push_days,
        form.auto_push_time
    )
)

/**
 * 切换触发条件时给出合理默认天数：
 * 距服务日前默认 15 天，支付后延迟默认 3 天；用户手改且仍在区间内的值尽量保留。
 */
const handleTriggerChange = (value: string | number | boolean | undefined) => {
    const trigger = Number(value)
    if (trigger === 2) {
        if (!form.auto_push_days || form.auto_push_days === 3 || form.auto_push_days > 365) {
            form.auto_push_days = 15
        }
        return
    }
    if (trigger === 3) {
        if (!form.auto_push_days || form.auto_push_days === 15 || form.auto_push_days > 90) {
            form.auto_push_days = 3
        }
    }
}

const isChoiceType = (type?: string) => ['single', 'multiple'].includes(String(type || ''))

const setTitleInput = (el: any, index: number) => {
    if (el) {
        titleInputRefs.set(index, el)
    } else {
        titleInputRefs.delete(index)
    }
}

const focusInvalidQuestion = async () => {
    await nextTick()
    if (invalidIndex.value < 0) {
        return
    }
    titleInputRefs.get(invalidIndex.value)?.focus?.()
}

const resetEditor = () => {
    config.value = {}
    customCategories.value = []
    questionTypes.value = [...QUESTION_TYPES]
    form.title = ''
    form.description = ''
    form.push_mode = 1
    form.status = 1
    form.auto_push_trigger = 1
    form.auto_push_days = 15
    form.auto_push_time = '10:00'
    form.questions = []
    invalidIndex.value = -1
    loadError.value = ''
    mode.value = 'edit'
}

const applyDetail = (data: CoupleQuestionnaireConfigDetail | null | undefined) => {
    config.value = data || {}
    customCategories.value = Array.isArray(data?.categories)
        ? data.categories.map((item) => String(item || '')).filter(Boolean)
        : []
    const detailTypes = Array.isArray(data?.question_types)
        ? data.question_types
              .map((item) => ({ value: String(item?.value || ''), label: String(item?.label || '') }))
              .filter((item) => item.value)
        : []
    questionTypes.value = detailTypes.length ? detailTypes : [...QUESTION_TYPES]
    form.title = String(data?.title || '新人婚礼资料问卷')
    form.description = String(data?.description || '')
    form.push_mode = Number(data?.push_mode ?? 1) === 2 ? 2 : 1
    form.status = Number(data?.status ?? 1) === 0 ? 0 : 1
    const trigger = [1, 2, 3].includes(Number(data?.auto_push_trigger))
        ? Number(data?.auto_push_trigger)
        : 1
    form.auto_push_trigger = trigger
    const rawDays = Number(data?.auto_push_days || 0)
    form.auto_push_days = rawDays > 0 ? rawDays : trigger === 3 ? 3 : 15
    form.auto_push_time = String(data?.auto_push_time || '10:00')
    form.questions = Array.isArray(data?.draft_questions)
        ? data.draft_questions.map((item, index) => normalizeEditableQuestion(item, index))
        : []
    invalidIndex.value = -1
}

const loadStaffOptions = async () => {
    if (staffOptionsLoaded.value) {
        return
    }
    try {
        const res: any = await staffAll()
        staffOptions.value = Array.isArray(res) ? res : Array.isArray(res?.data) ? res.data : []
        staffOptionsLoaded.value = true
    } catch {
        staffOptions.value = []
    }
}

const loadDetail = async () => {
    if (props.staffId <= 0) {
        return
    }
    const token = ++loadToken
    loading.value = true
    loadError.value = ''
    try {
        const res: any = await coupleQuestionnaireDetail({ staff_id: props.staffId })
        if (token !== loadToken) {
            return
        }
        const payload: any =
            res && typeof res === 'object' && !Array.isArray(res) && 'draft_questions' in res
                ? res
                : (res?.data ?? res)
        applyDetail(payload)
        emit('loaded', payload)
    } catch (error) {
        if (token !== loadToken) {
            return
        }
        loadError.value = extractErrorMessage(error, '问卷配置加载失败，请稍后重试')
    } finally {
        if (token === loadToken) {
            loading.value = false
        }
    }
}

watch(
    () => props.staffId,
    async (staffId) => {
        loadToken++
        resetEditor()
        if (staffId > 0) {
            await Promise.all([loadDetail(), loadStaffOptions()])
        }
    },
    { immediate: true }
)

const handleTypeChange = (item: EditableQuestionItem) => {
    if (!isChoiceType(item.type)) {
        item.options = []
        return
    }
    const options = Array.from(
        new Set(item.options.map((option) => String(option ?? '').trim()).filter(Boolean))
    )
    item.options = options.length >= 2 ? options : ['选项一', '选项二']
}

const addBlankQuestion = () => {
    form.questions.push(
        normalizeEditableQuestion(
            { type: 'textarea', category: '补充说明', required: 0 },
            form.questions.length
        )
    )
    mode.value = 'edit'
}

const moveQuestion = (index: number, delta: number) => {
    const target = index + delta
    if (target < 0 || target >= form.questions.length) {
        return
    }
    const [item] = form.questions.splice(index, 1)
    form.questions.splice(target, 0, item)
    invalidIndex.value = -1
}

const duplicateQuestion = (index: number) => {
    const source = form.questions[index]
    if (!source) {
        return
    }
    form.questions.splice(
        index + 1,
        0,
        normalizeEditableQuestion({ ...source, id: 0 }, form.questions.length)
    )
    invalidIndex.value = -1
}

const removeQuestion = (index: number) => {
    form.questions.splice(index, 1)
    invalidIndex.value = -1
}

const buildPayload = (): CoupleQuestionnaireSavePayload | null => {
    const result = validateQuestionnaireForm({
        title: form.title,
        questions: form.questions
    })
    if (!result.valid) {
        invalidIndex.value = result.invalidIndex ?? -1
        ElMessage.warning(result.message || '请先完善问卷配置')
        void focusInvalidQuestion()
        return null
    }
    invalidIndex.value = -1
    const pushCheck = validateAutoPushConfig({
        push_mode: form.push_mode,
        auto_push_trigger: form.auto_push_trigger,
        auto_push_days: form.auto_push_days,
        auto_push_time: form.auto_push_time
    })
    if (!pushCheck.valid) {
        ElMessage.warning(pushCheck.message || '请先完善自动推送时间设置')
        return null
    }
    return {
        staff_id: props.staffId,
        title: form.title.trim(),
        description: form.description.trim(),
        push_mode: form.push_mode,
        status: form.status,
        auto_push_trigger: form.auto_push_trigger,
        auto_push_days: form.auto_push_trigger === 1 ? 0 : form.auto_push_days,
        auto_push_time: form.auto_push_trigger === 1 ? '10:00' : form.auto_push_time,
        questions: serializeEditableQuestions(form.questions)
    }
}

const saveDraft = async () => {
    if (props.staffId <= 0 || saving.value) {
        return
    }
    const payload = buildPayload()
    if (!payload) {
        return
    }
    saving.value = true
    try {
        await coupleQuestionnaireSave(payload)
        ElMessage.success('草稿已保存')
        await loadDetail()
        emit('saved')
    } catch {
        // 业务错误已由 request 层统一提示，这里不重复弹窗
    } finally {
        saving.value = false
    }
}

const publishVersion = async () => {
    if (props.staffId <= 0 || publishing.value) {
        return
    }
    const payload = buildPayload()
    if (!payload) {
        return
    }
    try {
        await ElMessageBox.confirm(
            '发布后将生成新的问卷版本。仅对后续新生成的订单问卷生效；已创建或已发送的任务保留下发时的版本与题目快照。',
            '发布新版问卷',
            { type: 'warning', confirmButtonText: '确认发布', cancelButtonText: '再检查一下' }
        )
    } catch {
        return
    }
    publishing.value = true
    try {
        await coupleQuestionnairePublish(payload)
        ElMessage.success('新版问卷已发布')
        await loadDetail()
        emit('published')
    } catch {
        // 业务错误已由 request 层统一提示，这里不重复弹窗
    } finally {
        publishing.value = false
    }
}

const openBankDialog = () => {
    bankKeyword.value = ''
    bankCategory.value = ''
    selectedBankQuestions.value = []
    bankDialogVisible.value = true
}

const handleBankSelectionChange = (rows: any[]) => {
    selectedBankQuestions.value = rows
}

const handleBankDialogClosed = () => {
    selectedBankQuestions.value = []
    bankTableRef.value?.clearSelection?.()
}

const isBankQuestionAdded = (row: any) =>
    form.questions.some((item) => Number(item.bank_id) === Number(row?.id))

const appendBankQuestion = (row: any) => {
    form.questions.push(
        normalizeEditableQuestion({ ...row, id: 0, bank_id: row?.id }, form.questions.length)
    )
    mode.value = 'edit'
    ElMessage.success('已追加到问卷，可继续微调')
}

const appendSelectedBankQuestions = () => {
    if (!selectedBankQuestions.value.length) {
        return
    }
    const total = selectedBankQuestions.value.length
    selectedBankQuestions.value.forEach((row) => {
        form.questions.push(
            normalizeEditableQuestion({ ...row, id: 0, bank_id: row?.id }, form.questions.length)
        )
    })
    bankDialogVisible.value = false
    mode.value = 'edit'
    ElMessage.success(`已批量添加 ${total} 道题目`)
}

defineExpose({ reload: loadDetail })
</script>

<style scoped lang="scss">
.staff-questionnaire-editor {
    min-height: 240px;
}

.editor-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 32px 16px;
}

.editor-body {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.editor-alert {
    border-radius: var(--admin-radius-sm);
}

.editor-summary {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 16px 18px;
    border: 1px solid var(--admin-color-border);
    border-radius: var(--admin-radius-md);
    background: var(--admin-surface-card);

    &__identity {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    &__avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        flex: none;
        border-radius: 12px;
        background: var(--el-color-primary-light-9);
        color: var(--el-color-primary);
        font-size: 16px;
        font-weight: 600;
    }

    &__identity-main {
        min-width: 0;
    }

    &__name {
        font-size: 16px;
        font-weight: 600;
        color: var(--admin-color-title);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    &__meta {
        margin-top: 4px;
        font-size: 12px;
        color: var(--admin-color-muted);
    }

    &__stats {
        display: flex;
        align-items: center;
        gap: 22px;
        padding-left: 20px;
        border-left: 1px solid var(--admin-color-border);
    }

    &__stat {
        display: flex;
        flex-direction: column;
        gap: 6px;
        font-size: 18px;
        font-weight: 600;
        color: var(--admin-color-title);
    }

    &__stat-label {
        font-size: 12px;
        font-weight: 400;
        color: var(--admin-color-muted);
    }

    &__actions {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-left: auto;
        flex-wrap: wrap;
    }
}

.editor-grid {
    margin-top: 0;
}

.editor-panel {
    display: flex;
    flex-direction: column;
    gap: 14px;
    padding: 16px;
    border: 1px solid var(--admin-color-border);
    border-radius: var(--admin-radius-md);
    background: var(--admin-surface-card);

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;

        &--wrap {
            flex-wrap: wrap;
        }
    }

    &__title-row {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    &__title {
        font-size: 15px;
        font-weight: 600;
        color: var(--admin-color-title);
    }

    &__actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
}

.editor-form {
    :deep(.el-form-item) {
        margin-bottom: 16px;
    }

    :deep(.el-form-item__label) {
        font-weight: 500;
    }
}

.push-mode-group {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
    width: 100%;
}

.push-mode-option {
    height: auto;
    margin-right: 0;
    padding: 10px 12px;
    border: 1px solid var(--admin-color-border);
    border-radius: var(--admin-radius-sm);
    background: var(--admin-surface-soft);
    transition: border-color 0.2s ease;

    :deep(.el-radio__label) {
        display: flex;
        flex-direction: column;
        gap: 4px;
        line-height: 1.5;
        white-space: normal;
    }

    &__title {
        font-size: 14px;
        font-weight: 500;
        color: var(--admin-color-title);
    }

    &__desc {
        font-size: 12px;
        font-weight: 400;
        color: var(--admin-color-muted);
    }

    &.is-checked {
        border-color: var(--el-color-primary);
        background: var(--el-color-primary-light-9);
    }
}

.auto-push {
    display: flex;
    flex-direction: column;
    gap: 12px;
    width: 100%;
    min-width: 0;
    padding: 12px;
    border: 1px solid var(--admin-color-border);
    border-radius: var(--admin-radius-sm);
    background: var(--admin-surface-soft);

    &__block {
        display: flex;
        flex-direction: column;
        gap: 8px;
        min-width: 0;
    }

    &__label {
        font-size: 12px;
        color: var(--admin-color-muted);
    }

    &__triggers {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
        width: 100%;
    }

    &__trigger {
        width: 100%;
        height: auto;
        margin-right: 0;
        padding: 8px 10px;
        border: 1px solid var(--admin-color-border);
        border-radius: var(--admin-radius-sm);
        background: var(--admin-surface-card);
        transition: border-color 0.2s ease;

        :deep(.el-radio__label) {
            display: flex;
            flex-direction: column;
            gap: 2px;
            line-height: 1.5;
            white-space: normal;
        }

        &.is-checked {
            border-color: var(--el-color-primary);
            background: var(--el-color-primary-light-9);
        }
    }

    &__trigger-title {
        font-size: 13px;
        font-weight: 500;
        color: var(--admin-color-title);
    }

    &__trigger-desc {
        font-size: 12px;
        font-weight: 400;
        color: var(--admin-color-muted);
    }

    &__days {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;

        :deep(.el-input-number) {
            width: 140px;
        }
    }

    &__unit {
        font-size: 12px;
        color: var(--admin-color-muted);
    }

    &__presets {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    &__preset {
        padding: 3px 10px;
        border: 1px dashed var(--admin-color-border);
        border-radius: 999px;
        background: transparent;
        color: var(--admin-color-muted);
        font-size: 12px;
        line-height: 1.5;
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
            border-style: solid;
            border-color: var(--el-color-primary);
            background: var(--el-color-primary-light-9);
        }
    }

    &__preview {
        display: flex;
        flex-direction: column;
        gap: 4px;
        padding: 8px 10px;
        border-radius: var(--admin-radius-sm);
        background: var(--el-color-primary-light-9);
    }

    &__preview-label {
        font-size: 12px;
        color: var(--admin-color-muted);
    }

    &__preview-text {
        font-size: 13px;
        font-weight: 500;
        line-height: 1.5;
        color: var(--el-color-primary);
        word-break: break-word;
    }

    &__note {
        margin: 2px 0 0;
        font-size: 12px;
        line-height: 1.6;
        color: var(--admin-color-muted);
    }
}

.editor-mode {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    padding: 10px 12px;
    border-radius: var(--admin-radius-sm);
    background: var(--admin-surface-soft);
}

.question-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.question-card {
    padding: 14px;
    border: 1px solid var(--admin-color-border);
    border-radius: var(--admin-radius-sm);
    background: var(--admin-surface-soft);

    &__head {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }

    &__index {
        font-size: 13px;
        font-weight: 600;
        color: var(--el-color-primary);
        letter-spacing: 0.4px;
    }

    &__actions {
        display: flex;
        align-items: center;
        gap: 2px;
        margin-left: auto;
    }

    &__grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    &__field {
        display: flex;
        flex-direction: column;
        gap: 6px;
        min-width: 0;

        &--full {
            grid-column: 1 / -1;
        }
    }

    &__label {
        font-size: 12px;
        color: var(--admin-color-muted);
    }
}

.is-invalid {
    :deep(.el-input__wrapper) {
        box-shadow: 0 0 0 1px var(--el-color-danger) inset;
    }
}

.question-preview {
    display: flex;
    justify-content: center;

    &__phone {
        width: 100%;
        max-width: 420px;
        padding: 18px 16px 22px;
        border: 1px solid var(--admin-color-border);
        border-radius: 22px;
        background: linear-gradient(180deg, #fff8f2 0%, var(--admin-surface-card) 42%);
        box-shadow: var(--admin-shadow-soft);
    }

    &__header {
        padding: 4px 4px 12px;
        border-bottom: 1px dashed var(--admin-color-border);

        h3 {
            margin: 0;
            font-size: 17px;
            color: var(--admin-color-title);
        }

        p {
            margin: 8px 0 0;
            font-size: 12px;
            line-height: 1.7;
            color: var(--admin-color-muted);
        }
    }

    &__group {
        margin-top: 16px;
    }

    &__category {
        position: relative;
        margin-bottom: 10px;
        padding-left: 10px;
        font-size: 13px;
        font-weight: 600;
        color: var(--admin-color-title);

        &::before {
            content: '';
            position: absolute;
            left: 0;
            top: 3px;
            width: 3px;
            height: 13px;
            border-radius: 2px;
            background: var(--el-color-primary);
        }
    }

    &__item {
        padding: 12px;
        margin-bottom: 10px;
        border: 1px solid var(--admin-color-border);
        border-radius: var(--admin-radius-sm);
        background: var(--admin-surface-card);
    }

    &__title {
        font-size: 14px;
        font-weight: 500;
        line-height: 1.6;
        color: var(--admin-color-title);
    }

    &__required {
        color: var(--el-color-danger);
    }

    &__placeholder {
        margin-top: 4px;
        font-size: 12px;
        color: var(--admin-color-muted);
    }

    &__chips {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 8px;
    }

    &__chip {
        padding: 4px 10px;
        border-radius: 999px;
        border: 1px solid var(--el-color-primary-light-7);
        background: var(--el-color-primary-light-9);
        color: var(--el-color-primary);
        font-size: 12px;
    }

    &__hint {
        font-size: 12px;
        color: var(--admin-color-muted);
    }

    &__input,
    &__textarea {
        margin-top: 8px;
        padding: 8px 10px;
        border-radius: var(--admin-radius-sm);
        background: var(--admin-surface-soft);
        color: var(--el-text-color-placeholder);
        font-size: 12px;
    }

    &__textarea {
        min-height: 64px;
    }
}

.bank-toolbar {
    display: flex;
    gap: 10px;
    margin-bottom: 12px;

    &__search {
        flex: 1;
    }

    &__category {
        width: 180px;
        flex: none;
    }
}

.bank-question {
    &__title {
        font-weight: 500;
        color: var(--admin-color-title);
    }

    &__placeholder {
        margin-top: 2px;
        font-size: 12px;
        color: var(--admin-color-muted);
    }

    &__actions {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }
}

.bank-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;

    &__hint {
        font-size: 12px;
        color: var(--admin-color-muted);
    }

    &__actions {
        display: flex;
        gap: 8px;
    }
}

@media (max-width: 1200px) {
    .editor-summary {
        flex-wrap: wrap;

        &__stats {
            padding-left: 0;
            border-left: none;
        }

        &__actions {
            margin-left: 0;
        }
    }
}

@media (max-width: 768px) {
    .question-card__grid {
        grid-template-columns: 1fr;
    }

    .editor-summary__stats {
        width: 100%;
        flex-wrap: wrap;
        gap: 14px;
    }

    .bank-toolbar {
        flex-direction: column;
    }

    .bank-toolbar__category {
        width: 100%;
    }
}
</style>
