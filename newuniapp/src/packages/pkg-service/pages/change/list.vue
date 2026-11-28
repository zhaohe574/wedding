<template>
    <!-- 头部统一：走 page-shell 的 nav-bar，不再依赖系统导航 -->
    <page-shell
        title="变更记录"
        bg="page"
        :show-back="true"
        :pull-refresh="true"
        @refresh="onRefresh"
        @reach-bottom="onReachBottomLoad"
    >
        <view class="page-wrap change-list">
            <view class="tabs">
                <view
                    hover-class="is-pressed"
                    v-for="t in TABS"
                    :key="t.key"
                    class="tabs__item"
                    :class="{ 'is-active': active === t.key }"
                    @tap="switchTab(t.key)"
                >
                    <text class="tabs__text">{{ t.label }}</text>
                </view>
            </view>

            <view v-if="loading" class="page-pad">
                <base-skeleton variant="card" :rows="3" />
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
                :empty-text="emptyText"
                :desc="FB.EMPTY_HINT_CHANGE"
                fixed
            />

            <view v-else class="page-pad list" :class="{ stagger: staggerOn, 'is-in': staggerIn }">
                <base-card
                    v-for="(item, index) in list"
                    :key="item.id"
                    :style="{ '--i': index }"
                    compact
                    clickable
                    @tap="goDetail(item)"
                >
                    <view class="head">
                        <text class="head__title">{{ titleOf(item) }}</text>
                        <status-tag :label="statusLabel(item)" :tone="statusTone(item)" size="sm" />
                    </view>

                    <view v-if="active === 'change' && item.reason" class="reason ellipsis-2">{{
                        item.reason
                    }}</view>
                    <view v-if="active === 'pause' && item.reason" class="reason ellipsis-2">{{
                        item.reason
                    }}</view>

                    <view class="kv">
                        <!-- 改期 tab 下用户最关心"改到哪天"，不是"什么时候申请的"（P1 §3-8）。
                             ⚠️ start_date 不在 ChangeListItem 契约里（该接口有 [k: string]: any），
                             语义待后端确认：若后端表示它是"原日期"而非"新日期"，本行取值需改为正确字段。 -->
                        <text class="kv__k">{{
                            active === 'pause' ? '暂停区间' : '新服务日期'
                        }}</text>
                        <text class="kv__v t-num">{{
                            active === 'pause' ? rangeText(item) : item.start_date || '-'
                        }}</text>
                    </view>
                    <view v-if="item.order_sn" class="kv">
                        <text class="kv__k">关联订单</text>
                        <text class="kv__v t-num">{{ item.order_sn }}</text>
                    </view>

                    <view v-if="canCancel(item)" class="foot">
                        <base-button
                            text="撤销申请"
                            variant="danger"
                            size="sm"
                            :block="false"
                            @click.stop="handleCancel(item)"
                        />
                    </view>
                </base-card>

                <load-more :status="loadMoreStatus" @retry="fetchPage(false)" />
            </view>
        </view>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
import { toast } from '@/composables/useToast'
/** 变更记录：改期/加项 与 暂停 两条独立列表（后端也是两套接口） */
import { computed, onUnmounted, ref, watch } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { FB } from '@/constants/feedback'
import {
    ORDER_CHANGE_STATUS_NAME_MAP,
    ORDER_CHANGE_STATUS_TONE,
    ORDER_CHANGE_TYPE_NAME_MAP,
    ORDER_PAUSE_STATUS_NAME_MAP,
    ORDER_PAUSE_STATUS_TONE,
    ORDER_PAUSE_TYPE_NAME_MAP,
    OrderChangeStatus,
    OrderPauseStatus,
    resolveStatusLabel,
    resolveTone
} from '@shared/contracts'
import { getChangeList, getPauseList, cancelChange, cancelPause } from '@/api/change'
import { normalizePage } from '@/request'
import { navigate } from '@/utils/link'

const TABS = [
    { key: 'change', label: '变更申请' },
    { key: 'pause', label: '暂停申请' }
]

const active = ref('change')
const list = ref<any[]>([])
const page = ref(1)
const pageSize = 10
const hasMore = ref(true)
const loadingMore = ref(false)
const loading = ref(true)
const error = ref(false)

/** O5：真实错误驱动 errorKind / 文案 / 恢复动作（状态机骨架保持不变） */
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))
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
        return
    }
    // contact / 其余：保持现有客服入口，不新增跳转
}
/** LoadMore 四态（受控）：idle 静默 / loading / nomore / error 可点重试 */
const loadMoreFailed = ref(false)
const loadMoreStatus = computed<'idle' | 'loading' | 'nomore' | 'error'>(() => {
    if (loadMoreFailed.value) return 'error'
    if (loadingMore.value && list.value.length) return 'loading'
    if (!hasMore.value) return 'nomore'
    return 'idle'
})

const emptyText = computed(() => (active.value === 'pause' ? '还没有暂停申请' : '还没有变更申请'))

function titleOf(item: any) {
    if (active.value === 'pause') {
        return (
            (ORDER_PAUSE_TYPE_NAME_MAP as Record<number, string>)[Number(item.pause_type)] ||
            '暂停申请'
        )
    }
    return (
        (ORDER_CHANGE_TYPE_NAME_MAP as Record<number, string>)[Number(item.change_type)] ||
        '变更申请'
    )
}

function statusLabel(item: any) {
    // 后端字段：change_status / pause_status（模型无 status、status_desc）
    if (active.value === 'pause') {
        const mapped = resolveStatusLabel(ORDER_PAUSE_STATUS_NAME_MAP as any, Number(item.pause_status))
        return mapped.known ? mapped.label : item.pause_status_desc || mapped.label
    }
    const mapped = resolveStatusLabel(ORDER_CHANGE_STATUS_NAME_MAP as any, Number(item.change_status))
    return mapped.known ? mapped.label : item.change_status_desc || mapped.label
}

function statusTone(item: any) {
    return active.value === 'pause'
        ? resolveTone(ORDER_PAUSE_STATUS_TONE as any, Number(item.pause_status))
        : resolveTone(ORDER_CHANGE_STATUS_TONE as any, Number(item.change_status))
}

function rangeText(item: any) {
    if (!item.start_date && !item.end_date) return '-'
    return `${item.start_date || ''} ~ ${item.end_date || ''}`
}

function canCancel(item: any) {
    const s = active.value === 'pause' ? Number(item.pause_status) : Number(item.change_status)
    if (active.value === 'pause') return s === OrderPauseStatus.PENDING
    return s === OrderChangeStatus.PENDING
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
        const res =
            active.value === 'pause'
                ? await getPauseList({ page: page.value, page_size: pageSize })
                : await getChangeList({ page: page.value, page_size: pageSize })
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
    loading.value = true
    fetchPage(true)
}

function switchTab(key: string) {
    if (key === active.value) return
    active.value = key
    list.value = []
    reload()
}

/* ---- 首屏错峰入场（§3.3）：先挂 .stagger，下一拍再放 .is-in，动画才有起点 ---- */
const staggerOn = ref(false)
const staggerIn = ref(false)
let staggerTimer: ReturnType<typeof setTimeout> | null = null

function playStagger() {
    if (!list.value.length || staggerIn.value) return
    staggerOn.value = true
    if (staggerTimer) return
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

function goDetail(item: any) {
    navigate({
        path: '/packages/pkg-service/pages/change/detail',
        query: { id: item.id, type: active.value }
    })
}

async function handleCancel(item: any) {
    /* 撤销申请属不可逆操作：确认类二次弹窗保留居中形态，不做底部抽屉 */
    const confirmed = await new Promise<boolean>((resolve) => {
        uni.showModal({
            title: '撤销申请',
            content: '撤销后需要重新提交，确定吗？',
            success: (r) => resolve(!!r.confirm),
            fail: () => resolve(false)
        })
    })
    if (!confirmed) return
    try {
        if (active.value === 'pause') await cancelPause({ id: item.id })
        else await cancelChange({ id: item.id })
        toast.info('已撤销')
        reload()
    } catch {
        // 请求层已提示
    }
}

onLoad((options: any) => {
    if (options?.type === 'pause') active.value = 'pause'
    fetchPage(true)
})

onShow(() => {
    if (!loading.value) fetchPage(true)
})

/** page-shell 内置下拉刷新：无论同步/异步成功或异常，都必定调 done 收起刷新态 */
function onRefresh(done: () => void) {
    Promise.resolve(fetchPage(true)).finally(done)
}

/** page-shell 内置触底加载 */
function onReachBottomLoad() {
    fetchPage(false)
}
</script>

<style lang="scss" scoped>
.change-list {
    background: var(--c-bg);
}

.tabs {
    display: flex;
    gap: var(--sp-2);
    padding: var(--sp-2) var(--sp-4);
    background: var(--c-surface);
    position: sticky;
    top: 0;
    z-index: 10;
    border-bottom: 1rpx solid var(--c-line);

    /* P1 §3-8：下划线 tab → 胶囊版式（照 P0 的 search / case-list 成品，不新起样式）
       高 56rpx / --r-pill / 可点控件边界 / 选中 --c-active-* */
    &__item {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 56rpx;
        padding: 0 var(--sp-3);
        box-sizing: border-box;
        border: 1rpx solid var(--c-control-border);
        border-radius: var(--r-pill);
        background: var(--c-surface);
        transition:
            background-color var(--dur-base) var(--ease-out),
            border-color var(--dur-base) var(--ease-out);

        /* 视觉 56rpx，触控 88rpx（56 + 16×2）；只做纵向，横向留 0 避免与相邻 tab 热区重叠 */
        &::before {
            content: '';
            position: absolute;
            top: -16rpx;
            bottom: -16rpx;
            left: 0;
            right: 0;
        }

        &.is-active {
            background: var(--c-active-bg);
            border-color: var(--c-active-border);
        }
    }

    &__text {
        font-size: var(--fs-md);
        color: var(--c-ink-2);
        transition: color var(--dur-base) var(--ease-out);
    }

    &__item.is-active &__text {
        color: var(--c-active-fg);
        font-weight: var(--fw-medium);
    }
}

/* v10 §3.13：减弱动效下筛选态直接切换，不做过渡 */
@media (prefers-reduced-motion: reduce) {
    .tabs__item,
    .tabs__text {
        transition: none;
    }
}

.list {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    padding-top: var(--sp-3);
    padding-bottom: calc(var(--sp-4) + var(--safe-bottom));
}

.head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-3);
    padding-bottom: var(--sp-3);
    border-bottom: 1rpx solid var(--c-line);

    &__title {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }
}

.reason {
    display: block;
    margin: var(--sp-3) 0 0;
    font-size: var(--fs-xs);
    color: var(--c-ink-2);
    line-height: var(--lh-loose);
    overflow-wrap: anywhere;
}

.kv {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--sp-3);
    min-height: 56rpx;
    padding: var(--sp-2) 0 0;
    box-sizing: border-box;

    &__k {
        flex-shrink: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__v {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink);
        text-align: right;
        overflow-wrap: anywhere;
    }
}

.foot {
    display: flex;
    justify-content: flex-end;
    padding-top: var(--sp-3);
    border-top: 1rpx solid var(--c-line);
    margin-top: var(--sp-3);
}
</style>
