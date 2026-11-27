<template>
    <page-shell
        title="我的候补"
        bg="page"
        :show-back="true"
        :pull-refresh="true"
        @refresh="onRefresh"
        @reach-bottom="onReachBottomLoad"
    >
        <staff-guard
            v-if="!guardAllowed"
            :phase="guardPhase"
            :reason="guardReason"
            :error="guardError"
            @retry="onGuardRetry"
            @action="onGuardAction"
        />

        <view v-else class="page-wrap waitlist-page">
            <view class="tabs page-pad">
                <chip-group
                    :items="TABS.map((t) => ({ label: t.label, value: t.key }))"
                    :model-value="active"
                    scroll
                    @change="switchTab"
                />
            </view>

            <view v-if="loading" class="page-pad">
                <base-skeleton :rows="3" variant="card" />
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
                empty-text="暂无候补记录"
                desc="当客户在你的已满日期提交候补时会显示在这里"
                fixed
            />

            <view v-else class="page-pad list" :class="{ stagger: staggerIn, 'is-in': staggerIn }">
                <base-card
                    v-for="(item, index) in list"
                    :key="item.id"
                    compact
                    class="wait-card"
                    :style="{ '--i': index }"
                >
                    <view class="wait-card__head">
                        <view class="wait-card__title-wrap">
                            <text class="wait-card__date t-num">{{
                                item.schedule_date || '待定日期'
                            }}</text>
                            <text class="wait-card__pkg ellipsis">{{
                                item.package_name || '意向服务预约'
                            }}</text>
                        </view>
                        <status-tag
                            :label="statusLabel(item)"
                            :tone="waitlistTone(item.notify_status)"
                            size="sm"
                        />
                    </view>

                    <view class="wait-card__meta">
                        <view class="meta-row">
                            <text class="meta-row__label">客户称呼</text>
                            <text class="meta-row__value">
                                {{ item.customer_name || '客户' }}
                                <text v-if="item.customer_mobile" class="t-num"
                                    >({{ item.customer_mobile }})</text
                                >
                            </text>
                        </view>
                        <view v-if="item.create_time_text" class="meta-row">
                            <text class="meta-row__label">提交时间</text>
                            <text class="meta-row__value t-num">{{ item.create_time_text }}</text>
                        </view>
                        <view v-if="item.notify_time_text" class="meta-row">
                            <text class="meta-row__label">通知时间</text>
                            <text class="meta-row__value t-num">{{ item.notify_time_text }}</text>
                        </view>
                        <view v-if="item.expire_time_text" class="meta-row">
                            <text class="meta-row__label">有效截止</text>
                            <text class="meta-row__value t-num">{{ item.expire_time_text }}</text>
                        </view>
                        <view v-if="item.remark" class="meta-row">
                            <text class="meta-row__label">客户备注</text>
                            <text class="meta-row__value">{{ item.remark }}</text>
                        </view>
                    </view>

                    <view v-if="item.can_notify" class="wait-card__actions">
                        <base-button
                            text="通知客户预约"
                            variant="ink"
                            size="sm"
                            haptic-level="medium"
                            :loading="notifyingId === item.id"
                            :disabled="notifyingId > 0"
                            @click="onNotify(item)"
                        />
                    </view>
                    <view
                        v-else-if="item.notify_block_reason"
                        class="wait-card__hint"
                    >
                        <text class="wait-card__hint-text">{{ item.notify_block_reason }}</text>
                    </view>
                </base-card>

                <load-more :status="loadMoreStatus" @retry="fetchPage(false)" />
            </view>
        </view>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
/**
 * 服务人员：我的候补队列。
 *
 * 展示预约日期已满时提交候补的客户列表，在档期释放且满足 FIFO 顺位时
 * 可一键通知顺位首位客户发起预约。
 */
import { computed, onUnmounted, ref, watch } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import {
    WAITLIST_NOTIFY_STATUS_NAME_MAP,
    WaitlistNotifyStatus,
    resolveStatusLabel,
    type Tone
} from '@shared/contracts'
import { getStaffWaitlistList, staffWaitlistNotify } from '@/api/staff'
import StaffGuard from '../../components/staff-guard/staff-guard.vue'
import { useStaffGuard } from '@/composables/useStaffGuard'
import { normalizePage } from '@/request'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { toast } from '@/composables/useToast'
import { haptic } from '@/utils/haptics'

const {
    guardPhase,
    guardReason,
    guardError,
    guardAllowed,
    verify,
    onGuardRetry,
    onGuardAction
} = useStaffGuard(() => init(true))

const TABS = [
    { key: 'all', label: '全部', status: undefined as number | undefined },
    { key: 'pending', label: '等待中', status: WaitlistNotifyStatus.PENDING },
    { key: 'notified', label: '已通知', status: WaitlistNotifyStatus.NOTIFIED },
    { key: 'ordered', label: '已转正', status: WaitlistNotifyStatus.ORDERED },
    { key: 'expired', label: '已过期', status: WaitlistNotifyStatus.EXPIRED }
]

const active = ref('all')
const list = ref<any[]>([])
const page = ref(1)
const pageSize = 15
const hasMore = ref(true)
const loadingMore = ref(false)
const loading = ref(true)
const error = ref(false)
const hasLoadedOnce = ref(false)
const loadMoreFailed = ref(false)
const notifyingId = ref(0)

let firstShow = true

const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))

const loadMoreStatus = computed<'idle' | 'loading' | 'nomore' | 'error'>(() => {
    if (loadMoreFailed.value) return 'error'
    if (loadingMore.value && list.value.length) return 'loading'
    if (!hasMore.value) return 'nomore'
    return 'idle'
})

const activeStatus = () => TABS.find((t) => t.key === active.value)?.status

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

function waitlistTone(status: unknown): Tone {
    const n = Number(status)
    if (n === WaitlistNotifyStatus.PENDING) return 'warning'
    if (n === WaitlistNotifyStatus.NOTIFIED) return 'info'
    if (n === WaitlistNotifyStatus.ORDERED) return 'success'
    return 'neutral'
}

function statusLabel(item: any) {
    if (item?.notify_status_desc) return String(item.notify_status_desc)
    return resolveStatusLabel(
        WAITLIST_NOTIFY_STATUS_NAME_MAP as any,
        Number(item?.notify_status)
    ).label
}

async function fetchPage(reset = false) {
    if (reset) {
        page.value = 1
        hasMore.value = true
        error.value = false
    }
    if (!hasMore.value && !reset) return

    loadingMore.value = true
    loadMoreFailed.value = false
    try {
        const res = await getStaffWaitlistList({
            page_no: page.value,
            page_size: pageSize,
            notify_status: activeStatus()
        })
        const paged = normalizePage<any>(res, page.value, pageSize)
        list.value = reset ? paged.list : [...list.value, ...paged.list]
        hasMore.value = paged.hasMore
        if (hasMore.value) page.value += 1
    } catch (e) {
        rawError.value = e
        if (reset) error.value = true
        else loadMoreFailed.value = true
    } finally {
        loadingMore.value = false
        loading.value = false
    }
}

function reload() {
    if (!guardAllowed.value) return
    loading.value = true
    void fetchPage(true)
}

function switchTab(key: string) {
    if (!guardAllowed.value || key === active.value) return
    active.value = key
    list.value = []
    reload()
}

function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        reload()
        return
    }
    if (kind === 'reselect' || kind === 'back') {
        uni.navigateBack()
        return
    }
    if (kind === 'login') {
        uni.navigateTo({ url: '/pages/login/login' })
    }
}

function onNotify(item: any) {
    const id = Number(item?.id || 0)
    if (!guardAllowed.value || notifyingId.value > 0 || !id) return
    // 确认类二次弹窗保留居中形态：uni.showModal 是系统原生模态，不做可下拉关闭的抽屉
    uni.showModal({
        title: '通知客户预约',
        content: `确认通知客户「${item.customer_name || '客户'}」预约 ${item.schedule_date || ''} 的已释放档期？`,
        confirmText: '确认通知',
        cancelText: '取消',
        success: async (r) => {
            if (!r.confirm) return
            notifyingId.value = id
            try {
                await staffWaitlistNotify({ id })
                toast.success('已通知客户预约')
                await fetchPage(true)
            } catch (e: any) {
                toast.error(String(e?.message || '通知失败，请稍后重试'))
            } finally {
                notifyingId.value = 0
            }
        }
    })
}

async function init(forceGuard = false) {
    if (!(await verify(forceGuard))) return
    await fetchPage(true)
    hasLoadedOnce.value = true
}

onLoad(() => {
    firstShow = true
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
    Promise.resolve(fetchPage(true)).finally(done)
}

function onReachBottomLoad() {
    if (!guardAllowed.value || loadingMore.value || !hasMore.value) return
    void fetchPage(false)
}
</script>

<style lang="scss" scoped>
.waitlist-page {
    padding-bottom: calc(var(--sp-5) + var(--safe-bottom));
}

.tabs {
    padding-top: var(--sp-3);
    padding-bottom: var(--sp-2);
}

.list {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
}

.wait-card {
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
        gap: var(--sp-1);
    }

    &__date {
        font-family: var(--font-serif);
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
        color: var(--c-ink);
        font-variant-numeric: tabular-nums;
    }

    &__pkg {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-brand-text);
        font-weight: var(--fw-medium);
    }

    &__meta {
        margin-top: var(--sp-3);
        padding-top: var(--sp-2);
        border-top: 1rpx solid var(--c-line);
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
    }

    &__actions {
        margin-top: var(--sp-3);
    }

    &__hint {
        margin-top: var(--sp-2);
        padding: var(--sp-2) var(--sp-3);
        border-radius: var(--r-sm);
        background: var(--c-neutral-bg);
    }

    &__hint-text {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }
}

.meta-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--sp-3);

    &__label {
        flex-shrink: 0;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }

    &__value {
        flex: 1;
        min-width: 0;
        text-align: right;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
        overflow-wrap: anywhere;
    }
}
</style>
