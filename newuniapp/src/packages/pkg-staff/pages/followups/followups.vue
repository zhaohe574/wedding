<template>
    <page-shell
        title="跟进提醒"
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

        <view v-else class="page-wrap fup-page">
            <!-- 深墨跟进汇总卡 -->
            <view class="page-pad top-pad">
                <view class="summary-card">
                    <view class="summary-card__head">
                        <view class="summary-card__texts">
                            <text class="summary-card__eyebrow">FOLLOW-UP CENTER</text>
                            <text class="summary-card__title">客户跟进节拍看板</text>
                        </view>
                        <base-button
                            text="+ 新建提醒"
                            variant="gold"
                            size="sm"
                            :block="false"
                            @click="openCreateSheet"
                        />
                    </view>

                    <view class="summary-card__grid">
                        <view
                            v-for="card in sumCards"
                            :key="card.key"
                            class="sum-cell"
                            @tap="onSumTap(card.key)"
                        >
                            <rolling-number
                                class="sum-cell__num"
                                :class="{ 'is-danger': card.danger }"
                                :value="card.value"
                            />
                            <text class="sum-cell__label">{{ card.label }}</text>
                        </view>
                    </view>
                </view>
            </view>

            <!-- 筛选栏 -->
            <view class="page-pad filter-bar">
                <chip-group
                    :items="SCOPE_ITEMS"
                    :model-value="activeScope"
                    scroll
                    @change="onScopeChange"
                />
            </view>

            <view v-if="loading" class="page-pad">
                <base-skeleton :rows="4" variant="card" />
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

            <base-empty
                v-else-if="!list.length"
                status="empty"
                empty-icon="empty-order"
                empty-text="当前筛选下暂无跟进提醒"
                desc="点击右上角「+ 新建提醒」为婚礼客户安排沟通或彩排节点"
            />

            <view v-else class="page-pad list">
                <view class="list__items" :class="{ stagger: staggerIn, 'is-in': staggerIn }">
                    <base-card
                        v-for="(item, index) in list"
                        :key="item.id"
                        compact
                        class="item-card"
                        :style="{ '--i': index }"
                    >
                        <view class="item-card__head">
                            <view class="item-card__title-wrap">
                                <text class="item-card__title">{{ item.title }}</text>
                                <text class="item-card__time t-num">
                                    {{ item.remind_date }} {{ item.remind_time }} ·
                                    {{ item.remind_type_label }}
                                </text>
                            </view>
                            <status-tag
                                :label="item.status_desc"
                                :tone="statusTone(item)"
                                size="sm"
                            />
                        </view>

                        <view v-if="item.source_type !== 'general'" class="target-box">
                            <text class="target-box__name">{{ item.customer_name }}</text>
                            <text class="target-box__meta t-num">
                                {{ item.service_date ? `${item.service_date} · ` : ''
                                }}{{ item.package_name || item.service_address || '服务档期' }}
                            </text>
                        </view>

                        <text v-if="item.remark" class="item-card__remark">{{ item.remark }}</text>

                        <view class="item-card__actions">
                            <base-button
                                v-if="item.status === 0"
                                text="完成"
                                variant="ink"
                                size="sm"
                                :block="false"
                                :disabled="actingId === item.id"
                                @click="handleAction(item.id, 'done')"
                            />
                            <base-button
                                v-else
                                text="恢复"
                                variant="ghost"
                                size="sm"
                                :block="false"
                                :disabled="actingId === item.id"
                                @click="handleAction(item.id, 'reopen')"
                            />
                            <base-button
                                v-if="item.status === 0"
                                text="顺延1天"
                                variant="ghost"
                                size="sm"
                                :block="false"
                                :disabled="actingId === item.id"
                                @click="handleAction(item.id, 'snooze')"
                            />
                            <base-button
                                v-if="item.source_type !== 'general'"
                                text="看档案"
                                variant="ghost"
                                size="sm"
                                :block="false"
                                @click="openCoupleDetail(item)"
                            />
                            <base-button
                                v-if="item.customer_mobile"
                                text="拨打"
                                variant="ghost"
                                size="sm"
                                :block="false"
                                @click="dialPhone(item.customer_mobile)"
                            />
                            <base-button
                                text="编辑"
                                variant="ghost"
                                size="sm"
                                :block="false"
                                @click="openEditSheet(item)"
                            />
                            <base-button
                                text="删除"
                                variant="danger"
                                size="sm"
                                :block="false"
                                haptic-level="none"
                                @click="handleDelete(item.id)"
                            />
                        </view>
                    </base-card>
                </view>
            </view>
        </view>

        <followup-sheet
            v-model="sheetOpen"
            :remind-types="remindTypes"
            :recent-targets="recentTargets"
            :editing="editingItem"
            :saving="saving"
            @submit="handleSheetSubmit"
        />
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
/**
 * 服务人员：客户跟进提醒队列页。
 */
import { computed, onUnmounted, reactive, ref, watch } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import {
    actStaffFollowup,
    deleteStaffFollowup,
    getStaffFollowupList,
    saveStaffFollowup,
    type StaffFollowupReminderItem,
    type StaffFollowupTargetOption
} from '@/api/staff'
import type { Tone } from '@shared/contracts'
import StaffGuard from '../../components/staff-guard/staff-guard.vue'
import FollowupSheet from '../../components/followup-sheet/followup-sheet.vue'
import { useStaffGuard } from '@/composables/useStaffGuard'
import { toast } from '@/composables/useToast'
import { navigate } from '@/utils/link'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { haptic } from '@/utils/haptics'

const SCOPE_ITEMS = [
    { label: '待跟进', value: 'pending' },
    { label: '已逾期', value: 'overdue' },
    { label: '今日', value: 'today' },
    { label: '近 7 日', value: 'week' },
    { label: '已处理', value: 'done' },
    { label: '全部', value: 'all' }
]

const {
    guardPhase,
    guardReason,
    guardError,
    guardAllowed,
    verify,
    onGuardRetry,
    onGuardAction
} = useStaffGuard(() => init(true))

const activeScope = ref('pending')
const summary = reactive({
    pending_total: 0,
    overdue_count: 0,
    today_count: 0,
    week_count: 0,
    done_count: 0
})
const remindTypes = ref<Array<{ value: string; label: string }>>([])
const recentTargets = ref<StaffFollowupTargetOption[]>([])
const list = ref<StaffFollowupReminderItem[]>([])
const loading = ref(true)
const error = ref(false)
const hasLoadedOnce = ref(false)
const actingId = ref(0)

const sheetOpen = ref(false)
const editingItem = ref<StaffFollowupReminderItem | null>(null)
const saving = ref(false)

let firstShow = true

const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))

/** 汇总计数必须是有限数：接口可能回字符串 / null，rolling-number 收到 NaN 会渲染异常 */
function toCount(v: unknown): number {
    const n = Number(v ?? 0)
    return Number.isFinite(n) ? n : 0
}

const sumCards = computed(() => [
    {
        key: 'overdue',
        label: '已逾期',
        value: toCount(summary.overdue_count),
        danger: toCount(summary.overdue_count) > 0
    },
    { key: 'today', label: '今日待办', value: toCount(summary.today_count), danger: false },
    { key: 'week', label: '近 7 日', value: toCount(summary.week_count), danger: false },
    { key: 'done', label: '已完成', value: toCount(summary.done_count), danger: false }
])

function onSumTap(scope: string): void {
    switchScope(scope)
}

/* ---- 列表错峰入场（§3.3）：先挂 .stagger，下一拍再放 .is-in，动画才有起点 ---- */
const staggerIn = ref(false)
let staggerTimer: ReturnType<typeof setTimeout> | null = null

function playStagger(): void {
    if (staggerIn.value || staggerTimer) return
    staggerTimer = setTimeout(() => {
        staggerTimer = null
        staggerIn.value = true
    }, 40)
}

watch(
    () => list.value.length,
    (len) => {
        if (len) playStagger()
    }
)

onUnmounted(() => {
    if (staggerTimer) clearTimeout(staggerTimer)
})

function statusTone(item: StaffFollowupReminderItem): Tone {
    if (item.status === 1) return 'success'
    if (item.status === 2) return 'neutral'
    if (item.is_overdue) return 'danger'
    if (item.is_today) return 'warning'
    return 'info'
}

async function fetchList(): Promise<void> {
    if (!guardAllowed.value) return
    loading.value = true
    error.value = false
    rawError.value = null
    try {
        const res = await getStaffFollowupList({ scope: activeScope.value })
        Object.assign(summary, res?.summary || {})
        remindTypes.value = Array.isArray(res?.remind_types) ? res.remind_types : []
        recentTargets.value = Array.isArray(res?.recent_targets) ? res.recent_targets : []
        list.value = Array.isArray(res?.lists) ? res.lists : []
    } catch (e) {
        rawError.value = e
        error.value = true
    } finally {
        loading.value = false
    }
}

function reload(): void {
    void fetchList()
}

function switchScope(scope: string): void {
    if (scope === activeScope.value) return
    activeScope.value = scope
    void fetchList()
}

function onScopeChange(val: string | number): void {
    switchScope(String(val || 'pending'))
}

function openCreateSheet(): void {
    editingItem.value = null
    sheetOpen.value = true
}

function openEditSheet(item: StaffFollowupReminderItem): void {
    editingItem.value = item
    sheetOpen.value = true
}

async function handleSheetSubmit(payload: {
    id?: number
    source_type: 'order' | 'manual' | 'general'
    target_id: number
    remind_type: string
    title: string
    remind_date: string
    remind_time: string
    remark: string
}): Promise<void> {
    if (saving.value) return
    saving.value = true
    try {
        await saveStaffFollowup(payload)
        toast.success(payload.id ? '提醒已更新' : '已创建跟进提醒')
        sheetOpen.value = false
        editingItem.value = null
        await fetchList()
    } catch (e: any) {
        toast.error(String(e?.message || '保存失败'))
    } finally {
        saving.value = false
    }
}

async function handleAction(
    id: number,
    action: 'done' | 'reopen' | 'snooze' | 'ignore'
): Promise<void> {
    if (actingId.value > 0) return
    actingId.value = id
    try {
        await actStaffFollowup({ id, action })
        toast.success(action === 'snooze' ? '已顺延 1 天' : '已更新状态')
        await fetchList()
    } catch (e: any) {
        toast.error(String(e?.message || '操作失败'))
    } finally {
        actingId.value = 0
    }
}

function handleDelete(id: number): void {
    // 确认类二次弹窗保留居中形态：uni.showModal 是系统原生模态，不做可下拉关闭的抽屉
    uni.showModal({
        title: '删除跟进提醒',
        content: '确认删除这条提醒？',
        confirmText: '确认删除',
        cancelText: '取消',
        success: async (r) => {
            if (!r.confirm) return
            haptic('heavy', 'followup-delete')
            actingId.value = id
            try {
                await deleteStaffFollowup({ id })
                toast.success('已删除')
                await fetchList()
            } catch (e: any) {
                toast.error(String(e?.message || '删除失败'))
            } finally {
                actingId.value = 0
            }
        }
    })
}

function openCoupleDetail(item: StaffFollowupReminderItem): void {
    if (item.source_type === 'general' || !item.target_id) return
    navigate({
        path: '/packages/pkg-staff/pages/couple-detail/couple-detail',
        query: {
            source: item.source_type,
            id: item.target_id
        }
    })
}

function dialPhone(mobile: string): void {
    if (!mobile) return
    uni.makePhoneCall({ phoneNumber: mobile, fail: () => {} })
}

function onPrimary(): void {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        reload()
    } else if (kind === 'login') {
        uni.navigateTo({ url: '/pages/login/login' })
    }
}

async function init(forceGuard = false): Promise<void> {
    if (!(await verify(forceGuard))) return
    await fetchList()
    hasLoadedOnce.value = true
}

onLoad((options?: Record<string, any>) => {
    firstShow = true
    const scopeParam = String(options?.scope || '').trim()
    if (['pending', 'overdue', 'today', 'week', 'done', 'all'].includes(scopeParam)) {
        activeScope.value = scopeParam
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
    Promise.resolve(fetchList()).finally(done)
}
</script>

<style lang="scss" scoped>
.fup-page {
    padding-bottom: calc(var(--sp-5) + var(--safe-bottom));
}

.top-pad {
    padding-top: var(--sp-3);
}

.summary-card {
    padding: var(--sp-4);
    border-radius: var(--r-lg);
    background: linear-gradient(145deg, var(--c-dark-1), var(--c-dark-2));
    border: 1rpx solid var(--c-card-border);
    box-shadow: var(--shadow-card);
    color: var(--c-on-dark);
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__texts {
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__eyebrow {
        font-family: var(--font-serif);
        font-size: var(--fs-18);
        letter-spacing: 2rpx;
        color: var(--c-brand-soft);
    }

    &__title {
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        color: var(--c-on-dark);
    }

    &__grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        padding-top: var(--sp-3);
        border-top: 1rpx solid var(--c-line-strong);
    }
}

.sum-cell {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4rpx;
    min-height: 88rpx;

    &__num {
        font-family: var(--font-serif);
        font-size: var(--fs-xl);
        font-weight: var(--fw-semibold);
        color: var(--c-on-dark);
        font-variant-numeric: tabular-nums;

        &.is-danger {
            color: var(--c-brand-soft);
        }
    }

    &__label {
        font-size: var(--fs-20);
        color: var(--c-on-dark-2);
    }
}

.filter-bar {
    padding-top: var(--sp-3);
    padding-bottom: var(--sp-2);
}

.list {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
}

.list__items {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
}

.item-card {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);

    &__head {
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

    &__title {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__time {
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
        font-variant-numeric: tabular-nums;
    }

    &__remark {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
        overflow-wrap: anywhere;
    }

    &__actions {
        padding-top: var(--sp-2);
        border-top: 1rpx solid var(--c-line);
        display: flex;
        flex-wrap: wrap;
        gap: var(--sp-2);
    }
}

.target-box {
    padding: var(--sp-2);
    border-radius: var(--r-sm);
    background: var(--c-surface-2);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);

    &__name {
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        flex-shrink: 0;
    }

    &__meta {
        flex: 1;
        min-width: 0;
        text-align: right;
        font-size: var(--fs-20);
        color: var(--c-ink-3);
    }
}
</style>
