<template>
    <!-- 头部统一：走 page-shell 的 nav-bar，不再依赖系统导航 -->
    <page-shell
        title="服务工单"
        bg="page"
        :show-back="true"
        :bottom-bar="showBottomBar"
        :pull-refresh="true"
        @refresh="onRefresh"
        @reach-bottom="onReachBottomLoad"
    >
        <view class="page-wrap ticket-list">
            <!-- 顶部状态筛选胶囊栏 -->
            <view class="filter-bar">
                <view
                    v-for="tab in TABS"
                    :key="tab.key"
                    hover-class="is-pressed"
                    :hover-start-time="20"
                    :hover-stay-time="70"
                    :hover-stop-propagation="true"
                    class="filter-tab"
                    :class="{ 'is-active': activeTab === tab.key }"
                    aria-role="button"
                    :aria-label="`筛选${tab.label}工单`"
                    @tap="selectTab(tab.key)"
                >
                    <text class="filter-tab__text">{{ tab.label }}</text>
                </view>
            </view>

            <view v-if="loading" class="list-pad">
                <base-skeleton :rows="3" />
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
                empty-text="还没有工单"
                :desc="FB.EMPTY_HINT_SUBMITTED"
                action-text="提交工单"
                :show-action="true"
                fixed
                @action="goCreate"
            />

            <view v-else class="list-pad list">
                <base-card
                    v-for="item in list"
                    :key="item.id"
                    compact
                    clickable
                    @tap="goDetail(item)"
                >
                    <view class="head">
                        <view class="head__main">
                            <text class="type-pill">{{ typeText(item.type) }}</text>
                            <text class="head__title ellipsis">{{
                                item.title || typeText(item.type)
                            }}</text>
                        </view>
                        <status-tag :label="statusLabel(item)" :tone="statusTone(item)" size="sm" />
                    </view>
                    <text v-if="item.content" class="content ellipsis-2">{{ item.content }}</text>
                    <view class="foot">
                        <text class="foot__sn">{{ item.ticket_sn || '—' }}</text>
                        <view class="foot__right">
                            <text class="foot__time">{{ item.create_time }}</text>
                            <tn-icon name="right" :size="20" class="foot__arrow" />
                        </view>
                    </view>
                </base-card>

                <load-more :status="loadMoreStatus" @retry="fetchPage(false)" />
            </view>
        </view>
        <template #bottom>
            <bottom-bar v-if="showBottomBar">
                <base-button text="提交新工单" variant="ink" size="md" @click="goCreate" />
            </bottom-bar>
        </template>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
/** 工单列表 */
import { computed, ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { FB } from '@/constants/feedback'
import {
    AFTER_SALE_TICKET_STATUS_NAME_MAP,
    AFTER_SALE_TICKET_STATUS_TONE,
    AFTER_SALE_TICKET_TYPE_NAME_MAP,
    AfterSaleTicketStatus,
    resolveStatusLabel,
    resolveTone
} from '@shared/contracts'
import { getTicketList } from '@/api/aftersale'
import { normalizePage } from '@/request'
import { navigate } from '@/utils/link'

type TabKey = 'all' | 'unfinished' | 'completed'

const TABS: Array<{ key: TabKey; label: string; status?: number | string }> = [
    { key: 'all', label: '全部' },
    { key: 'unfinished', label: '处理中', status: 'unfinished' },
    { key: 'completed', label: '已完成', status: AfterSaleTicketStatus.COMPLETED }
]

const activeTab = ref<TabKey>('all')
const list = ref<any[]>([])
const page = ref(1)
const pageSize = 10
const hasMore = ref(true)
const loadingMore = ref(false)
const loading = ref(true)
const error = ref(false)
const showBottomBar = computed(() => !loading.value && !error.value && list.value.length > 0)

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

function typeText(t: number) {
    return (AFTER_SALE_TICKET_TYPE_NAME_MAP as Record<number, string>)[Number(t)] || '工单'
}

function statusLabel(item: any) {
    const mapped = resolveStatusLabel(AFTER_SALE_TICKET_STATUS_NAME_MAP as any, Number(item.status))
    return mapped.known ? mapped.label : item.status_desc || mapped.label
}

function statusTone(item: any) {
    return resolveTone(AFTER_SALE_TICKET_STATUS_TONE as any, Number(item.status))
}

function selectTab(key: TabKey) {
    if (activeTab.value === key) return
    activeTab.value = key
    loading.value = true
    fetchPage(true)
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
        const curTab = TABS.find((t) => t.key === activeTab.value)
        const res = await getTicketList({
            page: page.value,
            limit: pageSize,
            ...(curTab?.status !== undefined ? { status: curTab.status } : {})
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
    loading.value = true
    fetchPage(true)
}

function goDetail(item: any) {
    navigate({
        path: '/packages/pkg-service/pages/aftersale/ticket-detail',
        query: { id: item.id }
    })
}

function goCreate() {
    navigate({ path: '/packages/pkg-service/pages/aftersale/ticket-create' })
}

onLoad((options: any) => {
    const rawStatus = options?.status
    if (rawStatus === 'unfinished') {
        activeTab.value = 'unfinished'
    } else if (
        rawStatus !== undefined &&
        rawStatus !== '' &&
        Number(rawStatus) === AfterSaleTicketStatus.COMPLETED
    ) {
        activeTab.value = 'completed'
    }
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
.ticket-list {
    background: var(--c-bg);
}

.filter-bar {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    padding: var(--sp-3) var(--page-gutter) var(--sp-1);
}

.filter-tab {
    height: 60rpx;
    padding: 0 var(--sp-3);
    border-radius: var(--r-pill);
    background: var(--c-surface);
    border: 1rpx solid var(--c-control-border);
    display: flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &.is-active {
        background: var(--c-ink);
        border-color: var(--c-ink);

        .filter-tab__text {
            color: var(--c-on-dark);
            font-weight: var(--fw-medium);
        }
    }
}

.list-pad {
    padding-left: var(--page-gutter);
    padding-right: var(--page-gutter);
}

.list {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    padding-top: var(--sp-2);
    padding-bottom: var(--sp-4);
}

.head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);

    &__main {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: var(--sp-2);
    }

    &__title {
        flex: 1;
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        min-width: 0;
    }
}

.type-pill {
    flex: none;
    padding: 4rpx var(--sp-1);
    border-radius: var(--r-xs);
    font-size: var(--fs-xs);
    line-height: var(--lh-none);
    color: var(--c-brand-text);
    background: var(--c-brand-bg);
    border: 1rpx solid var(--c-primary-border);
}

.content {
    display: block;
    margin-top: var(--sp-2);
    font-size: var(--fs-xs);
    color: var(--c-ink-2);
    line-height: var(--lh-body);
    overflow-wrap: anywhere;
}

.foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-3);
    margin-top: var(--sp-3);
    padding-top: var(--sp-3);
    border-top: 1rpx solid var(--c-line);

    &__sn {
        flex: 1;
        min-width: 0;
        font-family: var(--font-serif);
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
        overflow-wrap: anywhere;
    }

    &__right {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        gap: var(--sp-1);
        color: var(--c-ink-3);
    }

    &__time {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }

    &__arrow {
        color: var(--c-ink-3);
    }
}
</style>
