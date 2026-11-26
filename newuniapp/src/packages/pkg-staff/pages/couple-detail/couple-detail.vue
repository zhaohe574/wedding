<template>
    <page-shell
        title="新人档案详情"
        bg="page"
        :show-back="true"
        :pull-refresh="true"
        @refresh="onRefresh"
    >
        <staff-guard
            v-if="!guardAllowed"
            :phase="guardPhase"
            :reason="guardReason"
            :error="guardError"
            @retry="onGuardRetry"
            @action="onGuardAction"
        />

        <view v-else class="page-wrap detail-page">
            <view v-if="loading" class="page-pad loading-pad">
                <base-skeleton :rows="5" />
            </view>

            <base-empty
                v-else-if="error"
                status="error"
                :error-kind="view?.errorKind || 'unknown'"
                :error-text="view?.title"
                :desc="view?.desc"
                :primary-action="view?.primaryAction"
                fixed
                @primary="onPrimary"
                @retry="reload"
            />

            <view v-else-if="detail" class="page-pad detail-body">
                <!-- 深墨金档案主卡 -->
                <view class="hero-card">
                    <view class="hero-card__top">
                        <view class="hero-card__title-wrap">
                            <text class="hero-card__eyebrow">{{
                                detail.profile.source_label
                            }}</text>
                            <text class="hero-card__name">{{ detail.profile.customer_name }}</text>
                            <text class="hero-card__pkg">{{ detail.profile.package_name }}</text>
                        </view>
                        <view class="hero-card__status-col">
                            <text class="hero-card__countdown">{{
                                detail.profile.countdown_text
                            }}</text>
                            <text class="hero-card__status">{{
                                detail.profile.status_desc
                            }}</text>
                        </view>
                    </view>

                    <view class="hero-card__meta">
                        <view class="hero-meta-row">
                            <text class="hero-meta-row__label">婚期日期</text>
                            <text class="hero-meta-row__value">{{
                                detail.profile.service_date || '待定婚期'
                            }}</text>
                        </view>
                        <view v-if="detail.profile.customer_mobile_masked" class="hero-meta-row">
                            <text class="hero-meta-row__label">联系电话</text>
                            <text class="hero-meta-row__value">{{
                                detail.profile.customer_mobile_masked
                            }}</text>
                        </view>
                        <view v-if="detail.profile.service_address" class="hero-meta-row">
                            <text class="hero-meta-row__label">服务场地</text>
                            <text class="hero-meta-row__value">{{
                                detail.profile.service_address
                            }}</text>
                        </view>
                    </view>

                    <view class="hero-card__actions">
                        <view
                            v-if="detail.profile.customer_mobile"
                            hover-class="is-pressed"
                            class="hero-btn"
                            @tap="dialPhone(detail.profile.customer_mobile)"
                        >
                            <text class="hero-btn__text">拨打电话</text>
                        </view>
                        <view
                            v-if="detail.profile.service_address"
                            hover-class="is-pressed"
                            class="hero-btn"
                            @tap="copyText(detail.profile.service_address, '已复制场地地址')"
                        >
                            <text class="hero-btn__text">复制场地</text>
                        </view>
                        <view hover-class="is-pressed" class="hero-btn" @tap="goCopyTemplates">
                            <text class="hero-btn__text">发话术</text>
                        </view>
                        <view
                            v-if="detail.profile.source_type === 'order'"
                            hover-class="is-pressed"
                            class="hero-btn"
                            @tap="goOrderDetail"
                        >
                            <text class="hero-btn__text">看订单</text>
                        </view>
                        <view
                            v-else-if="detail.profile.service_date"
                            hover-class="is-pressed"
                            class="hero-btn"
                            @tap="goServiceDay"
                        >
                            <text class="hero-btn__text">作战面板</text>
                        </view>
                    </view>
                </view>

                <!-- 置顶手记便签条 -->
                <view
                    v-if="pinnedNote"
                    hover-class="is-pressed"
                    class="pinned-callout"
                    @tap="activeTab = 'notes'"
                >
                    <view class="pinned-callout__head">
                        <text class="pinned-callout__badge">置顶手记 · {{ pinnedNote.category_label }}</text>
                        <text class="pinned-callout__time">{{ pinnedNote.update_time_text }}</text>
                    </view>
                    <text class="pinned-callout__body">{{ pinnedNote.content }}</text>
                </view>

                <!-- 四 Tab 切换 -->
                <view class="tab-bar">
                    <view
                        v-for="tab in tabs"
                        :key="tab.key"
                        hover-class="is-pressed"
                        class="tab-item"
                        :class="{ 'is-active': activeTab === tab.key }"
                        @tap="activeTab = tab.key"
                    >
                        <text class="tab-item__text">{{ tab.label }}</text>
                    </view>
                </view>

                <!-- Tab 1: 档案与新人问卷 -->
                <view v-if="activeTab === 'info'" class="tab-panel">
                    <base-card compact>
                        <section-header title="服务明细" flush />
                        <view class="kv-list">
                            <view v-if="detail.profile.order_sn" class="kv-row">
                                <text class="kv-row__label">订单编号</text>
                                <text class="kv-row__value">{{ detail.profile.order_sn }}</text>
                            </view>
                            <view class="kv-row">
                                <text class="kv-row__label">主服务项</text>
                                <text class="kv-row__value">{{ detail.profile.package_name }}</text>
                            </view>
                            <view v-if="detail.profile.addons_summary" class="kv-row">
                                <text class="kv-row__label">附加项</text>
                                <text class="kv-row__value">{{
                                    detail.profile.addons_summary
                                }}</text>
                            </view>
                            <view v-if="detail.profile.remark" class="kv-row">
                                <text class="kv-row__label">订单/档期备注</text>
                                <text class="kv-row__value">{{ detail.profile.remark }}</text>
                            </view>
                        </view>

                        <view v-if="detail.profile.teammates?.length" class="teammate-box">
                            <text class="teammate-box__title">同场协作人员</text>
                            <view class="teammate-list">
                                <text
                                    v-for="tm in detail.profile.teammates"
                                    :key="`${tm.staff_id}-${tm.category_name}`"
                                    class="teammate-chip"
                                >
                                    {{ tm.category_name }} · {{ tm.staff_name }}
                                </text>
                            </view>
                        </view>
                    </base-card>

                    <base-card v-if="detail.profile.source_type === 'order'" compact>
                        <view class="q-head">
                            <view class="q-head__texts">
                                <text class="q-head__title">新人婚礼问卷</text>
                                <text class="q-head__sub">
                                    {{
                                        detail.profile.questionnaire?.status_desc ||
                                        '尚未发送问卷任务'
                                    }}
                                    {{
                                        detail.profile.questionnaire?.submitted_time_text
                                            ? ` · 提交于 ${detail.profile.questionnaire.submitted_time_text}`
                                            : ''
                                    }}
                                </text>
                            </view>
                            <base-button
                                v-if="detail.profile.can_send_questionnaire"
                                :text="sendingQ ? '发送中…' : '发送/催填问卷'"
                                variant="gold"
                                size="sm"
                                :block="false"
                                :loading="sendingQ"
                                :disabled="sendingQ"
                                @click="handleSendQuestionnaire"
                            />
                        </view>

                        <view
                            v-if="detail.profile.questionnaire?.answers?.length"
                            class="q-answers"
                        >
                            <view
                                v-for="(ans, idx) in detail.profile.questionnaire.answers"
                                :key="idx"
                                class="q-item"
                                :class="{ 'is-taboo': ans.is_taboo }"
                            >
                                <text class="q-item__cat">
                                    {{ ans.category }} · {{ ans.title }}
                                </text>
                                <text class="q-item__ans">{{ ans.answer }}</text>
                            </view>
                        </view>
                        <text v-else class="empty-block-hint">
                            {{
                                detail.profile.questionnaire?.status === 1
                                    ? '新人暂未留下非空作答记录'
                                    : '新人尚未提交问卷，可点击右上角主动发送填写提醒'
                            }}
                        </text>
                    </base-card>
                </view>

                <!-- Tab 2: 婚礼 Checklist -->
                <view v-else-if="activeTab === 'checklist'" class="tab-panel">
                    <base-card compact>
                        <view class="check-head">
                            <view class="check-head__texts">
                                <text class="check-head__title">婚礼筹备 Checklist</text>
                                <text class="check-head__sub">
                                    已完成 {{ detail.checklist.done_count }} /
                                    {{ detail.checklist.total_count }} 项（{{
                                        detail.checklist.progress_percent
                                    }}%）
                                </text>
                            </view>
                            <base-button
                                :text="initingChecklist ? '生成中…' : '一键生成标准清单'"
                                variant="ghost"
                                size="sm"
                                :block="false"
                                :loading="initingChecklist"
                                :disabled="initingChecklist"
                                @click="handleInitChecklist"
                            />
                        </view>

                        <view class="progress-track">
                            <view
                                class="progress-fill"
                                :style="{ width: `${detail.checklist.progress_percent}%` }"
                            />
                        </view>

                        <view v-if="detail.checklist.items.length" class="check-items">
                            <view
                                v-for="(item, idx) in detail.checklist.items"
                                :key="item.id"
                                class="check-item"
                                :class="{
                                    'check-item--divided': idx > 0,
                                    'is-done': Number(item.is_done) === 1
                                }"
                            >
                                <view
                                    hover-class="is-pressed"
                                    class="check-item__main"
                                    @tap="handleToggleChecklist(item.id)"
                                >
                                    <view class="check-item__box">
                                        <tn-icon
                                            v-if="Number(item.is_done) === 1"
                                            name="success"
                                            :size="22"
                                            class="check-item__icon"
                                        />
                                    </view>
                                    <view class="check-item__body">
                                        <view class="check-item__top">
                                            <text class="check-item__stage">{{
                                                item.stage_label
                                            }}</text>
                                            <text v-if="item.done_time_text" class="check-item__time"
                                                >完成于 {{ item.done_time_text }}</text
                                            >
                                        </view>
                                        <text class="check-item__title">{{ item.title }}</text>
                                        <text v-if="item.remark" class="check-item__remark">{{
                                            item.remark
                                        }}</text>
                                    </view>
                                </view>
                                <view
                                    hover-class="is-pressed"
                                    class="check-item__del"
                                    @tap="handleDeleteChecklist(item.id)"
                                >
                                    <tn-icon name="delete" :size="26" class="check-item__del-icon" />
                                </view>
                            </view>
                        </view>
                        <text v-else class="empty-block-hint">
                            暂无检查项，点击右上角「一键生成标准清单」可立即生成 8 项专业婚礼节点
                        </text>
                    </base-card>

                    <!-- 内联新增自定义 Checklist 事项 -->
                    <base-card compact>
                        <section-header title="添加自定义待办" flush />
                        <view class="stage-row">
                            <view
                                v-for="st in detail.checklist.stages"
                                :key="st.value"
                                hover-class="is-pressed"
                                class="stage-chip"
                                :class="{ 'is-active': newCheckStage === st.value }"
                                @tap="newCheckStage = st.value"
                            >
                                <text class="stage-chip__text">{{ st.label }}</text>
                            </view>
                        </view>
                        <view class="inline-form">
                            <input
                                v-model="newCheckTitle"
                                class="inline-form__input"
                                placeholder="输入需核对的具体事项，如：核对草坪防雨预案"
                                placeholder-class="field__ph"
                                maxlength="120"
                            />
                            <base-button
                                text="添加"
                                variant="ink"
                                size="sm"
                                :block="false"
                                :loading="savingChecklist"
                                :disabled="savingChecklist"
                                @click="handleAddChecklist"
                            />
                        </view>
                    </base-card>
                </view>

                <!-- Tab 3: 服务手记 -->
                <view v-else-if="activeTab === 'notes'" class="tab-panel">
                    <view class="panel-top">
                        <base-button
                            text="写手记"
                            variant="ink"
                            size="sm"
                            :block="false"
                            @click="openCreateNote"
                        />
                    </view>

                    <base-card v-if="!detail.notes.length" compact>
                        <text class="empty-block-hint">还没有服务手记</text>
                    </base-card>

                    <base-card v-for="note in detail.notes" :key="note.id" compact class="note-card">
                        <view class="note-card__head">
                            <view class="note-card__tags">
                                <status-tag
                                    :label="note.category_label"
                                    :tone="note.category === 'preference' ? 'warning' : 'primary'"
                                    size="sm"
                                    :dot="false"
                                />
                                <status-tag
                                    v-if="Number(note.is_pinned) === 1"
                                    label="已置顶"
                                    tone="danger"
                                    size="sm"
                                    :dot="false"
                                />
                            </view>
                            <text class="note-card__time">{{ note.update_time_text }}</text>
                        </view>

                        <text class="note-card__content">{{ note.content }}</text>

                        <view v-if="note.images?.length" class="note-imgs">
                            <image
                                v-for="(imgUrl, idx) in note.images"
                                :key="idx"
                                :src="imgUrl"
                                class="note-imgs__pic"
                                mode="aspectFill"
                                @tap="previewNoteImages(note.images, idx)"
                            />
                        </view>

                        <view class="note-card__actions">
                            <base-button
                                :text="Number(note.is_pinned) === 1 ? '取消置顶' : '置顶'"
                                variant="ghost"
                                size="sm"
                                :block="false"
                                @click="handleToggleNotePin(note.id)"
                            />
                            <base-button
                                text="编辑"
                                variant="ghost"
                                size="sm"
                                :block="false"
                                @click="openEditNote(note)"
                            />
                            <base-button
                                text="删除"
                                variant="danger"
                                size="sm"
                                :block="false"
                                @click="handleDeleteNote(note.id)"
                            />
                        </view>
                    </base-card>
                </view>

                <!-- Tab 4: 跟进提醒 -->
                <view v-else class="tab-panel">
                    <view class="panel-top">
                        <base-button
                            text="设提醒"
                            variant="ink"
                            size="sm"
                            :block="false"
                            @click="openCreateFollowup"
                        />
                    </view>

                    <base-card v-if="!detail.followups.length" compact>
                        <text class="empty-block-hint">暂未设置跟进提醒</text>
                    </base-card>

                    <base-card
                        v-for="item in detail.followups"
                        :key="item.id"
                        compact
                        class="fup-card"
                    >
                        <view class="fup-card__head">
                            <view class="fup-card__left">
                                <text class="fup-card__title">{{ item.title }}</text>
                                <text class="fup-card__time">
                                    {{ item.remind_date }} {{ item.remind_time }} ·
                                    {{ item.remind_type_label }}
                                </text>
                            </view>
                            <status-tag
                                :label="item.status_desc"
                                :tone="followupTone(item)"
                                size="sm"
                            />
                        </view>

                        <text v-if="item.remark" class="fup-card__remark">{{ item.remark }}</text>

                        <view class="fup-card__actions">
                            <base-button
                                v-if="item.status === 0"
                                text="标记完成"
                                variant="ink"
                                size="sm"
                                :block="false"
                                @click="handleFollowupAct(item.id, 'done')"
                            />
                            <base-button
                                v-else
                                text="恢复待跟进"
                                variant="ghost"
                                size="sm"
                                :block="false"
                                @click="handleFollowupAct(item.id, 'reopen')"
                            />
                            <base-button
                                v-if="item.status === 0"
                                text="顺延 1 天"
                                variant="ghost"
                                size="sm"
                                :block="false"
                                @click="handleFollowupAct(item.id, 'snooze')"
                            />
                            <base-button
                                text="删除"
                                variant="danger"
                                size="sm"
                                :block="false"
                                @click="handleDeleteFollowup(item.id)"
                            />
                        </view>
                    </base-card>
                </view>
            </view>
        </view>

        <service-note-sheet
            v-model="noteSheetOpen"
            :categories="detail?.note_categories || []"
            :editing="editingNote"
            :saving="savingNote"
            @submit="handleNoteSubmit"
        />
        <followup-sheet
            v-model="followupSheetOpen"
            :remind-types="detail?.remind_types || []"
            :locked-target="lockedFollowupTarget"
            :saving="savingFollowup"
            @submit="handleFollowupSubmit"
        />
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
/**
 * 服务人员：新人全周期档案详情页（档案问卷 / Checklist / 服务手记 / 跟进提醒）。
 */
import { computed, ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import {
    actStaffFollowup,
    deleteStaffChecklistItem,
    deleteStaffFollowup,
    deleteStaffServiceNote,
    getStaffCoupleDetail,
    initStaffChecklistPresets,
    saveStaffChecklistItem,
    saveStaffFollowup,
    saveStaffServiceNote,
    sendStaffCoupleQuestionnaire,
    toggleStaffChecklistItem,
    toggleStaffServiceNotePin,
    type StaffCoupleDetailResponse,
    type StaffFollowupReminderItem,
    type StaffServiceNoteItem
} from '@/api/staff'
import type { Tone } from '@shared/contracts'
import StaffGuard from '../../components/staff-guard/staff-guard.vue'
import ServiceNoteSheet from '../../components/service-note-sheet/service-note-sheet.vue'
import FollowupSheet from '../../components/followup-sheet/followup-sheet.vue'
import { useStaffGuard } from '@/composables/useStaffGuard'
import { toast } from '@/composables/useToast'
import { navigate } from '@/utils/link'
import { resolveErrorView } from '@/utils/resolveErrorView'

const {
    guardPhase,
    guardReason,
    guardError,
    guardAllowed,
    verify,
    onGuardRetry,
    onGuardAction
} = useStaffGuard(() => init(true))

const sourceType = ref<'order' | 'manual'>('order')
const targetId = ref(0)
const activeTab = ref<'info' | 'checklist' | 'notes' | 'followups'>('info')
const detail = ref<StaffCoupleDetailResponse | null>(null)
const loading = ref(true)
const error = ref(false)
const hasLoadedOnce = ref(false)

const sendingQ = ref(false)
const initingChecklist = ref(false)
const savingChecklist = ref(false)
const newCheckStage = ref('pre_wedding')
const newCheckTitle = ref('')

const noteSheetOpen = ref(false)
const editingNote = ref<StaffServiceNoteItem | null>(null)
const savingNote = ref(false)

const followupSheetOpen = ref(false)
const savingFollowup = ref(false)

let firstShow = true

const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))

const tabs = computed(() => {
    const d = detail.value
    const pendingFup = d ? d.followups.filter((f) => f.status === 0).length : 0
    return [
        { key: 'info' as const, label: '档案问卷' },
        {
            key: 'checklist' as const,
            label: d ? `清单(${d.checklist.done_count}/${d.checklist.total_count})` : '清单'
        },
        { key: 'notes' as const, label: d ? `手记(${d.notes.length})` : '手记' },
        { key: 'followups' as const, label: pendingFup > 0 ? `提醒(${pendingFup})` : '提醒' }
    ]
})

const pinnedNote = computed(
    () => detail.value?.notes.find((n) => Number(n.is_pinned) === 1) || null
)

const lockedFollowupTarget = computed(() => {
    const p = detail.value?.profile
    if (!p) return null
    return {
        source_type: p.source_type,
        target_id: p.target_id,
        label: `${p.service_date || '待定婚期'} · ${p.customer_name}`,
        service_date: p.service_date
    }
})

function followupTone(item: StaffFollowupReminderItem): Tone {
    if (item.status === 1) return 'success'
    if (item.status === 2) return 'neutral'
    if (item.is_overdue) return 'danger'
    if (item.is_today) return 'warning'
    return 'info'
}

async function fetchDetail(): Promise<void> {
    if (!guardAllowed.value || !targetId.value) return
    loading.value = true
    error.value = false
    rawError.value = null
    try {
        detail.value = await getStaffCoupleDetail({
            source_type: sourceType.value,
            target_id: targetId.value
        })
    } catch (e) {
        rawError.value = e
        error.value = true
    } finally {
        loading.value = false
    }
}

function reload(): void {
    void fetchDetail()
}

function dialPhone(mobile: string): void {
    if (!mobile) return
    uni.makePhoneCall({ phoneNumber: mobile, fail: () => {} })
}

function copyText(text: string, msg: string): void {
    if (!text) return
    uni.setClipboardData({
        data: text,
        success: () => toast.success(msg)
    })
}

function goCopyTemplates(): void {
    const p = detail.value?.profile
    if (!p) return
    const query =
        p.source_type === 'order' ? { order_id: p.order_id } : { manual_schedule_id: p.target_id }
    navigate({ path: '/packages/pkg-staff/pages/copy-templates/copy-templates', query })
}

function goOrderDetail(): void {
    const p = detail.value?.profile
    if (!p?.order_id) return
    navigate({
        path: '/packages/pkg-staff/pages/order-detail/order-detail',
        query: { id: p.order_id }
    })
}

function goServiceDay(): void {
    const sDate = detail.value?.profile?.service_date
    navigate({
        path: '/packages/pkg-staff/pages/service-day/service-day',
        query: sDate ? { date: sDate } : undefined
    })
}

async function handleSendQuestionnaire(): Promise<void> {
    const oid = Number(detail.value?.profile?.order_id || 0)
    if (!oid || sendingQ.value) return
    sendingQ.value = true
    try {
        await sendStaffCoupleQuestionnaire({ order_id: oid })
        toast.success('已向新人发送问卷提醒')
        await fetchDetail()
    } catch (e: any) {
        toast.error(String(e?.message || '发送失败，请稍后重试'))
    } finally {
        sendingQ.value = false
    }
}

async function handleInitChecklist(): Promise<void> {
    if (initingChecklist.value || !detail.value) return
    initingChecklist.value = true
    try {
        const block = await initStaffChecklistPresets({
            source_type: sourceType.value,
            target_id: targetId.value
        })
        detail.value.checklist = block
        toast.success('已补齐标准婚礼 Checklist')
    } catch (e: any) {
        toast.error(String(e?.message || '生成失败'))
    } finally {
        initingChecklist.value = false
    }
}

async function handleAddChecklist(): Promise<void> {
    if (savingChecklist.value || !detail.value) return
    const title = newCheckTitle.value.trim()
    if (!title) {
        toast.warning('请输入检查项内容')
        return
    }
    savingChecklist.value = true
    try {
        const block = await saveStaffChecklistItem({
            source_type: sourceType.value,
            target_id: targetId.value,
            stage: newCheckStage.value,
            title
        })
        detail.value.checklist = block
        newCheckTitle.value = ''
        toast.success('已添加检查项')
    } catch (e: any) {
        toast.error(String(e?.message || '添加失败'))
    } finally {
        savingChecklist.value = false
    }
}

async function handleToggleChecklist(id: number): Promise<void> {
    if (!detail.value || !id) return
    try {
        detail.value.checklist = await toggleStaffChecklistItem({ id })
    } catch (e: any) {
        toast.error(String(e?.message || '更新状态失败'))
    }
}

function handleDeleteChecklist(id: number): void {
    if (!id) return
    uni.showModal({
        title: '删除待办项',
        content: '确认删除这条 Checklist 事项？',
        confirmText: '确认删除',
        cancelText: '取消',
        success: async (r) => {
            if (!r.confirm) return
            try {
                await deleteStaffChecklistItem({ id })
                toast.success('已删除')
                await fetchDetail()
            } catch (e: any) {
                toast.error(String(e?.message || '删除失败'))
            }
        }
    })
}

function openCreateNote(): void {
    editingNote.value = null
    noteSheetOpen.value = true
}

function openEditNote(note: StaffServiceNoteItem): void {
    editingNote.value = note
    noteSheetOpen.value = true
}

function previewNoteImages(urls: string[], idx: number): void {
    if (!urls.length) return
    uni.previewImage({ urls, current: urls[idx] || urls[0] })
}

async function handleNoteSubmit(payload: {
    id?: number
    category: string
    content: string
    images: string[]
    is_pinned: number
}): Promise<void> {
    if (savingNote.value) return
    savingNote.value = true
    try {
        await saveStaffServiceNote({
            ...payload,
            source_type: sourceType.value,
            target_id: targetId.value
        })
        toast.success(payload.id ? '手记已更新' : '已保存服务手记')
        noteSheetOpen.value = false
        editingNote.value = null
        await fetchDetail()
    } catch (e: any) {
        toast.error(String(e?.message || '保存手记失败'))
    } finally {
        savingNote.value = false
    }
}

async function handleToggleNotePin(id: number): Promise<void> {
    if (!id) return
    try {
        await toggleStaffServiceNotePin({ id })
        toast.success('置顶状态已更新')
        await fetchDetail()
    } catch (e: any) {
        toast.error(String(e?.message || '操作失败'))
    }
}

function handleDeleteNote(id: number): void {
    if (!id) return
    uni.showModal({
        title: '删除服务手记',
        content: '确认删除这条服务手记？',
        confirmText: '确认删除',
        cancelText: '取消',
        success: async (r) => {
            if (!r.confirm) return
            try {
                await deleteStaffServiceNote({ id })
                toast.success('手记已删除')
                await fetchDetail()
            } catch (e: any) {
                toast.error(String(e?.message || '删除失败'))
            }
        }
    })
}

function openCreateFollowup(): void {
    followupSheetOpen.value = true
}

async function handleFollowupSubmit(payload: {
    id?: number
    source_type: 'order' | 'manual' | 'general'
    target_id: number
    remind_type: string
    title: string
    remind_date: string
    remind_time: string
    remark: string
}): Promise<void> {
    if (savingFollowup.value) return
    savingFollowup.value = true
    try {
        await saveStaffFollowup(payload)
        toast.success('已添加跟进提醒')
        followupSheetOpen.value = false
        await fetchDetail()
    } catch (e: any) {
        toast.error(String(e?.message || '保存失败'))
    } finally {
        savingFollowup.value = false
    }
}

async function handleFollowupAct(
    id: number,
    action: 'done' | 'reopen' | 'snooze' | 'ignore'
): Promise<void> {
    try {
        await actStaffFollowup({ id, action })
        toast.success(action === 'snooze' ? '已顺延 1 天' : '提醒状态已更新')
        await fetchDetail()
    } catch (e: any) {
        toast.error(String(e?.message || '操作失败'))
    }
}

function handleDeleteFollowup(id: number): void {
    uni.showModal({
        title: '删除提醒',
        content: '确认删除这条跟进提醒？',
        confirmText: '确认删除',
        cancelText: '取消',
        success: async (r) => {
            if (!r.confirm) return
            try {
                await deleteStaffFollowup({ id })
                toast.success('已删除提醒')
                await fetchDetail()
            } catch (e: any) {
                toast.error(String(e?.message || '删除失败'))
            }
        }
    })
}

function onPrimary(): void {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        reload()
    } else if (kind === 'reselect' || kind === 'back') {
        uni.navigateBack()
    } else if (kind === 'login') {
        uni.navigateTo({ url: '/pages/login/login' })
    }
}

async function init(forceGuard = false): Promise<void> {
    if (!(await verify(forceGuard))) return
    await fetchDetail()
    hasLoadedOnce.value = true
}

onLoad((options?: Record<string, any>) => {
    firstShow = true
    const src = String(options?.source || '').trim()
    const idVal = Number(options?.id || 0)
    const orderId = Number(options?.order_id || 0)
    const manualId = Number(options?.manual_schedule_id || 0)
    if (orderId > 0) {
        sourceType.value = 'order'
        targetId.value = orderId
    } else if (manualId > 0) {
        sourceType.value = 'manual'
        targetId.value = manualId
    } else {
        sourceType.value = src === 'manual' ? 'manual' : 'order'
        targetId.value = idVal
    }
    const tabParam = String(options?.tab || '').trim()
    if (tabParam === 'checklist' || tabParam === 'notes' || tabParam === 'followups') {
        activeTab.value = tabParam
    }
})

onShow(() => {
    if (firstShow) {
        firstShow = false
        void init()
        return
    }
    if (hasLoadedOnce.value) {
        void init(true)
    }
})

function onRefresh(done: () => void) {
    if (!guardAllowed.value) {
        done()
        return
    }
    Promise.resolve(fetchDetail()).finally(done)
}
</script>

<style lang="scss" scoped>
.detail-page {
    padding-bottom: calc(var(--sp-5) + var(--safe-bottom));
}

.loading-pad {
    padding-top: var(--sp-3);
}

.detail-body {
    padding-top: var(--sp-3);
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
}

.hero-card {
    padding: var(--sp-4);
    border-radius: var(--r-lg);
    background: linear-gradient(155deg, var(--c-dark-1), var(--c-dark-2));
    border: 1rpx solid var(--c-primary-border);
    color: var(--c-on-dark);
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);

    &__top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__title-wrap {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__eyebrow {
        font-size: var(--fs-20);
        color: var(--c-brand-soft);
    }

    &__name {
        font-size: var(--fs-xl);
        font-weight: var(--fw-semibold);
        color: var(--c-on-dark);
    }

    &__pkg {
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
    }

    &__status-col {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 4rpx;
        flex-shrink: 0;
    }

    &__countdown {
        font-family: var(--font-serif);
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-soft);
    }

    &__status {
        font-size: var(--fs-20);
        color: var(--c-on-dark-2);
    }

    &__meta {
        padding-top: var(--sp-2);
        border-top: 1rpx solid var(--c-line-strong);
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
    }

    &__actions {
        display: flex;
        flex-wrap: wrap;
        gap: var(--sp-2);
    }
}

.hero-meta-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--sp-2);

    &__label {
        width: 120rpx;
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
        flex-shrink: 0;
    }

    &__value {
        flex: 1;
        min-width: 0;
        text-align: right;
        font-size: var(--fs-xs);
        color: var(--c-on-dark);
        overflow-wrap: anywhere;
    }
}

.hero-btn {
    padding: 8rpx var(--sp-3);
    border-radius: var(--r-pill);
    background: var(--c-dark-3);
    border: 1rpx solid var(--c-brand-soft);

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-brand-soft);
        font-weight: var(--fw-medium);
    }
}

.pinned-callout {
    padding: var(--sp-3);
    border-radius: var(--r-md);
    background: var(--c-warning-bg);
    border: 1rpx solid var(--c-warning-border);
    display: flex;
    flex-direction: column;
    gap: var(--sp-1);

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__badge {
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
        color: var(--c-warning);
    }

    &__time {
        font-size: var(--fs-20);
        color: var(--c-ink-3);
    }

    &__body {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink);
    }
}

.tab-bar {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    padding: 6rpx;
    border-radius: var(--r-pill);
    background: var(--c-surface-2);
    border: 1rpx solid var(--c-line);
}

.tab-item {
    height: 64rpx;
    border-radius: var(--r-pill);
    display: flex;
    align-items: center;
    justify-content: center;

    &.is-active {
        background: var(--c-ink);

        .tab-item__text {
            color: var(--c-on-dark);
            font-weight: var(--fw-semibold);
        }
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
        white-space: nowrap;
    }
}

.tab-panel {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
}

.kv-list {
    margin-top: var(--sp-2);
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
}

.kv-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--sp-2);

    &__label {
        width: 144rpx;
        flex-shrink: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__value {
        flex: 1;
        min-width: 0;
        text-align: right;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink);
        overflow-wrap: anywhere;
    }
}

.teammate-box {
    margin-top: var(--sp-3);
    padding-top: var(--sp-2);
    border-top: 1rpx solid var(--c-line);
    display: flex;
    flex-direction: column;
    gap: var(--sp-1);

    &__title {
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }
}

.teammate-list {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-1);
}

.teammate-chip {
    padding: 4rpx var(--sp-2);
    border-radius: var(--r-xs);
    background: var(--c-surface-2);
    font-size: var(--fs-20);
    color: var(--c-ink-2);
}

.q-head,
.check-head,
.panel-top {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: var(--sp-2);

    &__texts {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }
}

.q-answers {
    margin-top: var(--sp-3);
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
}

.q-item {
    padding: var(--sp-2);
    border-radius: var(--r-sm);
    background: var(--c-surface-2);
    display: flex;
    flex-direction: column;
    gap: 4rpx;

    &.is-taboo {
        background: var(--c-warning-bg);
        border: 1rpx solid var(--c-warning-border);

        .q-item__cat,
        .q-item__ans {
            color: var(--c-warning);
        }
    }

    &__cat {
        font-size: var(--fs-20);
        font-weight: var(--fw-medium);
        color: var(--c-ink-3);
    }

    &__ans {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink);
        overflow-wrap: anywhere;
    }
}

.empty-block-hint {
    display: block;
    margin-top: var(--sp-2);
    font-size: var(--fs-xs);
    line-height: var(--lh-body);
    color: var(--c-ink-3);
}

.progress-track {
    margin-top: var(--sp-2);
    height: 12rpx;
    border-radius: var(--r-pill);
    background: var(--c-neutral-bg);
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    border-radius: var(--r-pill);
    background: var(--c-brand-text);
}

.check-items {
    margin-top: var(--sp-3);
}

.check-item {
    display: flex;
    align-items: flex-start;
    gap: var(--sp-2);
    padding: var(--sp-2) 0;

    &--divided {
        border-top: 1rpx solid var(--c-line);
    }

    &__main {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: flex-start;
        gap: var(--sp-2);
    }

    &__box {
        width: 36rpx;
        height: 36rpx;
        margin-top: 2rpx;
        border-radius: var(--r-xs);
        border: 1rpx solid var(--c-control-border);
        background: var(--c-surface);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        color: var(--c-ink-inverse);
    }

    &__icon {
        color: inherit;
    }

    &__body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__top {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
    }

    &__stage {
        padding: 2rpx var(--sp-1);
        border-radius: var(--r-xs);
        background: var(--c-brand-bg);
        font-size: var(--fs-18);
        color: var(--c-brand-text);
    }

    &__time {
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }

    &__title {
        font-size: var(--fs-md);
        color: var(--c-ink);
        line-height: var(--lh-tight);
    }

    &__remark {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        line-height: var(--lh-body);
    }

    &__del {
        padding: var(--sp-1);
        color: var(--c-ink-3);
        flex-shrink: 0;
    }

    &__del-icon {
        color: inherit;
    }

    &.is-done {
        .check-item__box {
            border-color: var(--c-success);
            background: var(--c-success);
        }

        .check-item__title {
            color: var(--c-ink-3);
            text-decoration: line-through;
        }
    }
}

.stage-row {
    margin-top: var(--sp-2);
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-1);
}

.stage-chip {
    padding: 6rpx var(--sp-2);
    border-radius: var(--r-pill);
    border: 1rpx solid var(--c-control-border);
    background: var(--c-surface);

    &.is-active {
        border-color: var(--c-brand-text);
        background: var(--c-brand-bg);

        .stage-chip__text {
            color: var(--c-brand-text);
            font-weight: var(--fw-semibold);
        }
    }

    &__text {
        font-size: var(--fs-20);
        color: var(--c-ink-2);
    }
}

.inline-form {
    margin-top: var(--sp-2);
    display: flex;
    align-items: center;
    gap: var(--sp-2);

    &__input {
        flex: 1;
        min-width: 0;
        height: 76rpx;
        padding: 0 var(--sp-3);
        border-radius: var(--r-sm);
        border: 1rpx solid var(--c-control-border);
        background: var(--c-surface);
        font-size: var(--fs-md);
        color: var(--c-ink);
        box-sizing: border-box;
    }
}

.note-card,
.fup-card {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);

    &__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__tags {
        display: flex;
        flex-wrap: wrap;
        gap: var(--sp-1);
    }

    &__time {
        font-size: var(--fs-20);
        color: var(--c-ink-3);
        font-variant-numeric: tabular-nums;
    }

    &__content,
    &__remark {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
        white-space: pre-wrap;
        overflow-wrap: anywhere;
    }

    &__actions {
        padding-top: var(--sp-1);
        border-top: 1rpx solid var(--c-line);
        display: flex;
        flex-wrap: wrap;
        gap: var(--sp-2);
    }

    &__left {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }
}

.note-imgs {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: var(--sp-1);

    &__pic {
        width: 100%;
        height: 144rpx;
        border-radius: var(--r-xs);
        background: var(--c-surface-2);
    }
}
</style>
