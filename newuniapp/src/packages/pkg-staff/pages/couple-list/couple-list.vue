<template>
    <page-shell
        title="新人档案"
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

        <view v-else class="page-wrap couple-page">
            <!-- 跟进提醒入口条 -->
            <view
                v-if="summary.pending_followup_count > 0 || summary.overdue_followup_count > 0"
                hover-class="is-pressed"
                class="followup-banner page-pad"
                @tap="goFollowups"
            >
                <view class="followup-banner__inner">
                    <view class="followup-banner__left">
                        <tn-icon name="notice" :size="30" class="followup-banner__icon" />
                        <view class="followup-banner__texts">
                            <text class="followup-banner__title">
                                {{
                                    summary.overdue_followup_count > 0
                                        ? `${summary.overdue_followup_count} 位新人已逾期跟进`
                                        : `${summary.pending_followup_count} 位新人有待办跟进`
                                }}
                            </text>
                            <text class="followup-banner__sub"
                                >点击进入跟进提醒工作台统一处理</text
                            >
                        </view>
                    </view>
                    <tn-icon name="right" :size="24" class="followup-banner__arrow" />
                </view>
            </view>

            <!-- 搜索与筛选栏 -->
            <view class="toolbar page-pad">
                <view class="search-box">
                    <tn-icon name="search" :size="26" class="search-box__icon" />
                    <input
                        v-model="keyword"
                        class="search-box__input"
                        placeholder="搜索新人姓名、手机、场地或套餐"
                        placeholder-class="field__ph"
                        confirm-type="search"
                        @confirm="fetchList"
                    />
                    <text
                        v-if="keyword"
                        class="search-box__clear hit-88"
                        @tap="clearKeyword"
                    >
                        清空
                    </text>
                </view>

                <chip-group
                    :items="FILTER_ITEMS"
                    :model-value="activeFilter"
                    scroll
                    @change="onFilterChange"
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
                empty-text="暂无匹配的新人档案"
                desc="平台订单与线下自主档期的新人信息会自动沉淀在此"
            />

            <view v-else class="page-pad list">
                <view class="list__items" :class="{ stagger: staggerIn, 'is-in': staggerIn }">
                    <base-card
                        v-for="(item, index) in list"
                        :key="item.target_key"
                        compact
                        clickable
                        class="couple-card"
                        :style="{ '--i': index }"
                        @tap="openDetail(item)"
                    >
                        <view class="couple-card__head">
                            <view class="couple-card__title-wrap">
                                <view class="couple-card__name-row">
                                    <text class="couple-card__name">{{ item.customer_name }}</text>
                                    <status-tag
                                        :label="item.source_label"
                                        :tone="item.source_type === 'order' ? 'primary' : 'neutral'"
                                        size="sm"
                                        :dot="false"
                                    />
                                </view>
                                <text class="couple-card__sub t-num">
                                    {{ item.service_date || '待定婚期' }} ·
                                    {{ item.package_name || '婚礼服务' }}
                                </text>
                            </view>
                            <view
                                class="countdown-pill"
                                :class="{
                                    'is-soon':
                                        item.countdown_days !== null &&
                                        item.countdown_days >= 0 &&
                                        item.countdown_days <= 7 &&
                                        !item.is_completed,
                                    'is-done': item.is_completed
                                }"
                            >
                                <text class="countdown-pill__text t-num">{{
                                    item.countdown_text
                                }}</text>
                            </view>
                        </view>

                        <view v-if="item.service_address" class="couple-card__addr">
                            <tn-icon name="pin" :size="22" class="couple-card__addr-icon" />
                            <text class="couple-card__addr-text ellipsis">{{
                                item.service_address
                            }}</text>
                        </view>

                        <view v-if="item.pinned_note_preview" class="pinned-preview">
                            <text class="pinned-preview__tag">置顶手记</text>
                            <text class="pinned-preview__text ellipsis">{{
                                item.pinned_note_preview
                            }}</text>
                        </view>

                        <view class="couple-card__foot">
                            <view class="badge-row">
                                <text class="mini-badge">{{ item.questionnaire_status_desc }}</text>
                                <text class="mini-badge t-num">
                                    {{
                                        item.checklist_total > 0
                                            ? `清单 ${item.checklist_done}/${item.checklist_total}`
                                            : '清单待建'
                                    }}
                                </text>
                                <text class="mini-badge t-num">{{ item.note_count }} 篇手记</text>
                                <text
                                    v-if="item.pending_followup_count > 0"
                                    class="mini-badge t-num"
                                    :class="
                                        item.overdue_followup_count > 0 ? 'is-danger' : 'is-warn'
                                    "
                                >
                                    {{
                                        item.overdue_followup_count > 0
                                            ? `逾期 ${item.overdue_followup_count}`
                                            : `待跟进 ${item.pending_followup_count}`
                                    }}
                                </text>
                            </view>
                            <tn-icon name="right" :size="22" class="couple-card__arrow" />
                        </view>
                    </base-card>
                </view>
            </view>
        </view>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
/**
 * 服务人员：新人全周期档案列表（订单/线下档期统一沉淀，关联问卷/Checklist/手记/跟进）。
 */
import { computed, onUnmounted, reactive, ref, watch } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { getStaffCoupleList, type StaffCoupleSummaryItem } from '@/api/staff'
import StaffGuard from '../../components/staff-guard/staff-guard.vue'
import { useStaffGuard } from '@/composables/useStaffGuard'
import { navigate } from '@/utils/link'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { haptic } from '@/utils/haptics'

const FILTER_ITEMS = [
    { label: '全部档案', value: 'all' },
    { label: '即将服务', value: 'upcoming' },
    { label: '待跟进', value: 'followup' },
    { label: '已履约', value: 'completed' }
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

const activeFilter = ref('all')
const keyword = ref('')
const list = ref<StaffCoupleSummaryItem[]>([])
const summary = reactive({
    total_count: 0,
    upcoming_count: 0,
    pending_followup_count: 0,
    overdue_followup_count: 0,
    completed_count: 0
})
const loading = ref(true)
const error = ref(false)
const hasLoadedOnce = ref(false)

let firstShow = true

const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))

async function fetchList(): Promise<void> {
    if (!guardAllowed.value) return
    loading.value = true
    error.value = false
    rawError.value = null
    try {
        const res = await getStaffCoupleList({
            filter: activeFilter.value,
            keyword: keyword.value.trim() || undefined
        })
        Object.assign(summary, res?.summary || {})
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

function onFilterChange(val: string | number): void {
    const next = String(val || 'all')
    if (next === activeFilter.value) return
    /* chip-group 内部已发 light 触觉，这里不再补，避免双震 */
    activeFilter.value = next
    void fetchList()
}

function clearKeyword(): void {
    haptic('light', 'couple-clear-keyword')
    keyword.value = ''
    void fetchList()
}

function openDetail(item: StaffCoupleSummaryItem): void {
    haptic('light', 'couple-open-detail')
    navigate({
        path: '/packages/pkg-staff/pages/couple-detail/couple-detail',
        query: {
            source: item.source_type,
            id: item.target_id
        }
    })
}

function goFollowups(): void {
    haptic('light', 'couple-open-followups')
    navigate('/packages/pkg-staff/pages/followups/followups')
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
    const filterParam = String(options?.filter || '').trim()
    if (['all', 'upcoming', 'followup', 'completed'].includes(filterParam)) {
        activeFilter.value = filterParam
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
.couple-page {
    padding-bottom: calc(var(--sp-5) + var(--safe-bottom));
}

.followup-banner {
    padding-top: var(--sp-3);

    &__inner {
        padding: var(--sp-3);
        min-height: 88rpx;
        box-sizing: border-box;
        border-radius: var(--r-md);
        background: linear-gradient(135deg, var(--c-dark-1), var(--c-dark-2));
        border: 1rpx solid var(--c-card-border);
        box-shadow: var(--shadow-card);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
        color: var(--c-on-dark);
    }

    &__left {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: var(--sp-2);
    }

    &__icon {
        color: var(--c-brand-soft);
        flex-shrink: 0;
    }

    &__texts {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 2rpx;
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-on-dark);
    }

    &__sub {
        font-size: var(--fs-20);
        color: var(--c-on-dark-2);
    }

    &__arrow {
        color: var(--c-brand-soft);
        flex-shrink: 0;
    }
}

.toolbar {
    padding-top: var(--sp-3);
    padding-bottom: var(--sp-2);
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
}

.search-box {
    height: 88rpx;
    padding: 0 var(--sp-3);
    border-radius: var(--r-pill);
    border: 1rpx solid var(--c-control-border);
    background: var(--c-surface);
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    color: var(--c-ink-3);
    box-sizing: border-box;

    &__icon {
        color: inherit;
        flex-shrink: 0;
    }

    &__input {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-md);
        color: var(--c-ink);
    }

    &__clear {
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
        flex-shrink: 0;
    }
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

.couple-card {
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

    &__name-row {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
    }

    &__name {
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__sub {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
        font-variant-numeric: tabular-nums;
    }

    &__addr {
        display: flex;
        align-items: center;
        gap: var(--sp-1);
        color: var(--c-ink-3);
    }

    &__addr-icon {
        color: inherit;
        flex-shrink: 0;
    }

    &__addr-text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__foot {
        padding-top: var(--sp-2);
        border-top: 1rpx solid var(--c-line);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__arrow {
        color: var(--c-ink-3);
        flex-shrink: 0;
    }
}

.countdown-pill {
    padding: 6rpx var(--sp-2);
    border-radius: var(--r-pill);
    background: var(--c-brand-bg);
    border: 1rpx solid var(--c-primary-border);
    flex-shrink: 0;

    &__text {
        font-size: var(--fs-20);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-text);
        font-variant-numeric: tabular-nums;
    }

    &.is-soon {
        background: var(--c-warning-bg);
        border-color: var(--c-warning-border);

        .countdown-pill__text {
            color: var(--c-warning);
        }
    }

    &.is-done {
        background: var(--c-neutral-bg);
        border-color: var(--c-neutral-border);

        .countdown-pill__text {
            color: var(--c-ink-3);
        }
    }
}

.pinned-preview {
    padding: var(--sp-1) var(--sp-2);
    border-radius: var(--r-xs);
    background: var(--c-warning-bg);
    border: 1rpx solid var(--c-warning-border);
    display: flex;
    align-items: center;
    gap: var(--sp-1);

    &__tag {
        font-size: var(--fs-18);
        font-weight: var(--fw-semibold);
        color: var(--c-warning);
        flex-shrink: 0;
    }

    &__text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-20);
        color: var(--c-ink-2);
    }
}

.badge-row {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-1);
}

.mini-badge {
    padding: 4rpx var(--sp-2);
    border-radius: var(--r-xs);
    background: var(--c-surface-2);
    font-size: var(--fs-18);
    color: var(--c-ink-2);
    font-variant-numeric: tabular-nums;

    &.is-warn {
        background: var(--c-warning-bg);
        color: var(--c-warning);
    }

    &.is-danger {
        background: var(--c-danger-bg);
        color: var(--c-danger);
        font-weight: var(--fw-semibold);
    }
}
</style>
