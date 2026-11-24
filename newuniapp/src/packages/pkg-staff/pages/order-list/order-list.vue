<template>
    <!-- 头部统一：走 page-shell 的 nav-bar，不再依赖系统导航 -->
    <page-shell title="我的订单" bg="page" :show-back="true">
        <staff-guard
            v-if="!guardAllowed"
            :phase="guardPhase"
            :reason="guardReason"
            :error="guardError"
            @retry="onGuardRetry"
            @action="onGuardAction"
        />

        <view v-else class="page-wrap staff-orders">
            <!-- 裁决 3a：这是全站第 5 份手写 chip 实现，收编到 chip-group（组件注释原话
                 「全站唯一实现 … 不要在第 3 处自己写 chip」）。
                 顺带修掉三处口径漂移：字号 --fs-md→--fs-xs、边框 --c-line-strong(1.59:1)→
                 --c-control-border(3.77:1)、选中字色 --c-ink-inverse→--c-on-dark。
                 其中边框那条是实打实的缺陷：可交互控件边界要求 ≥3:1，2–3 字的胶囊摆白底上边框等于没有。 -->
            <view class="tabs page-pad">
                <chip-group
                    :items="TABS.map((t) => ({ label: t.label, value: t.key }))"
                    :model-value="active"
                    scroll
                    @change="switchTab"
                />
            </view>

            <view v-if="loading" class="page-pad">
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
                empty-icon="empty-order"
                empty-text="暂无指派给你的订单"
                desc="有新的服务指派会出现在这里"
                fixed
            />

            <view v-else class="page-pad list">
                <base-card
                    v-for="item in list"
                    :key="item.id"
                    compact
                    clickable
                    @tap="goDetail(item)"
                >
                    <view class="head">
                        <text class="head__sn">{{ item.order_sn }}</text>
                        <status-tag :label="statusLabel(item)" :tone="statusTone(item)" size="sm" />
                    </view>
                    <view class="body">
                        <view class="body__main">
                            <text class="body__title ellipsis">{{
                                item.package_name || item.item_type_desc || '服务订单'
                            }}</text>
                            <text v-if="item.service_date" class="body__meta"
                                >服务日期 {{ item.service_date }}</text
                            >
                            <text v-if="item.contact_name" class="body__meta body__contact">
                                {{ item.contact_name }} {{ maskMobile(item.contact_mobile) }}
                            </text>
                        </view>
                        <price
                            size="md"
                            v-if="
                                item.total_amount !== undefined || item.order_amount !== undefined
                            "
                            class="body__price"
                            :content="item.total_amount ?? item.order_amount ?? 0"
                        />
                    </view>
                    <view v-if="item.service_address" class="addr ellipsis-2">{{
                        item.service_address
                    }}</view>
                    <view class="foot">
                        <text class="foot__time">{{ item.create_time || '服务订单' }}</text>
                        <text class="foot__link">查看详情</text>
                    </view>
                </base-card>

                <load-more :status="loadMoreStatus" @retry="fetchPage(false)" />
            </view>
        </view>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
/** 服务人员：指派给我的订单 */
import { computed, ref } from 'vue'
import { onLoad, onPullDownRefresh, onReachBottom, onShow } from '@dcloudio/uni-app'
import { resolveErrorView } from '@/utils/resolveErrorView'
import {
    ORDER_STATUS_NAME_MAP,
    ORDER_STATUS_TONE,
    resolveStatusLabel,
    resolveTone
} from '@shared/contracts'
import { getStaffOrderList } from '@/api/staff'
import StaffGuard from '../../components/staff-guard/staff-guard.vue'
import { useStaffGuard } from '@/composables/useStaffGuard'
import { normalizePage } from '@/request'
import { navigate } from '@/utils/link'

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
    { key: 'confirm', label: '待确认', status: 0 },
    { key: 'pending', label: '待服务', status: 2 },
    { key: 'serving', label: '服务中', status: 3 },
    { key: 'done', label: '已完成', status: 4 }
]

const active = ref('all')
const list = ref<any[]>([])
const page = ref(1)
const pageSize = 10
const hasMore = ref(true)
const loadingMore = ref(false)
const loading = ref(true)
const error = ref(false)
const hasLoadedOnce = ref(false)

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

const activeStatus = () => TABS.find((t) => t.key === active.value)?.status

function statusLabel(item: any) {
    const mapped = resolveStatusLabel(
        ORDER_STATUS_NAME_MAP as any,
        Number(item.order_status ?? item.status)
    )
    return mapped.known ? mapped.label : item.status_desc || mapped.label
}

function statusTone(item: any) {
    return resolveTone(ORDER_STATUS_TONE as any, Number(item.order_status ?? item.status))
}

function maskMobile(m?: string) {
    if (!m) return ''
    return String(m).replace(/^(\d{3})\d{4}(\d{4})$/, '$1****$2')
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
        const res = await getStaffOrderList({
            page: page.value,
            page_size: pageSize,
            status: activeStatus()
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
        uni.stopPullDownRefresh()
    }
}

function reload() {
    if (!guardAllowed.value) return
    loading.value = true
    fetchPage(true)
}

function switchTab(key: string) {
    if (!guardAllowed.value || key === active.value) return
    active.value = key
    list.value = []
    reload()
}

function goDetail(item: any) {
    if (!guardAllowed.value) return
    navigate({
        path: '/packages/pkg-staff/pages/order-detail/order-detail',
        query: { id: item.order_id || item.id }
    })
}

async function init(forceGuard = false) {
    if (!(await verify(forceGuard))) return
    await fetchPage(true)
    hasLoadedOnce.value = true
}

onLoad((options: any) => {
    const rawStatus = options?.status
    if (rawStatus !== undefined && rawStatus !== '') {
        const status = Number(rawStatus)
        const target = TABS.find((tab) => tab.status === status)
        if (target) active.value = target.key
    }
    void init()
})
onShow(() => {
    if (hasLoadedOnce.value && guardAllowed.value && !loadingMore.value && !loading.value) {
        void fetchPage(true)
    }
})
onPullDownRefresh(async () => {
    if (await verify(true)) {
        await fetchPage(true)
    } else {
        uni.stopPullDownRefresh()
    }
})
onReachBottom(() => {
    if (guardAllowed.value) fetchPage(false)
})
</script>

<style lang="scss" scoped>
.staff-orders {
    background: var(--c-bg);
}

/* P2 §3-3：下划线 tab → 胶囊版式（照 P0 的 search/case-list、P1 的 review-list/change-list 成品） */
/* 裁决 3a/3b：内部结构交给 chip-group，这里只负责纵向节奏与横向留白。
   注意：**不要**加回吸顶（sticky）—— nav-bar 是 fixed + z-index:99，
   筛选条钉在视口顶部会钻到导航栏底下（10 < 99），滚过一屏就消失。这是 bug 不是特性。
   吸顶到导航栏下方需要 nav 总高（nav-bar 里 JS 算的 px，page-shell 未暴露），记下一批候选。 */
.tabs {
    padding-top: var(--sp-3);
    padding-bottom: var(--sp-2);
}

.list {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    padding-top: var(--sp-3);
}

.head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--sp-3);
    padding-bottom: var(--sp-3);
    border-bottom: 1rpx solid var(--c-line);

    &__sn {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
        overflow-wrap: anywhere;
    }
}

.body {
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    padding: var(--sp-3) 0;

    &__main {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: 500;
        color: var(--c-ink);
    }

    &__meta {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }

    &__contact {
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    &__price {
        flex-shrink: 0;
    }
}

.addr {
    padding: var(--sp-3) 0;
    border-top: 1rpx solid var(--c-line);
    font-size: var(--fs-xs);
    line-height: var(--lh-body);
    color: var(--c-ink-2);
    overflow-wrap: anywhere;
}

.foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-3);
    padding-top: var(--sp-3);
    border-top: 1rpx solid var(--c-line);

    &__time {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    &__link {
        flex-shrink: 0;
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
    }
}
</style>
