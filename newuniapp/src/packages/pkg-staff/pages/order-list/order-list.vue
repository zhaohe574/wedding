<template>
    <view class="page-wrap staff-orders">
        <view class="tabs">
            <view hover-class="is-pressed"
                v-for="t in TABS"
                :key="t.key"
                class="tabs__item"
                :class="{ 'is-active': active === t.key }"
                @tap="switchTab(t.key)"
            >
                <text class="tabs__text">{{ t.label }}</text>
                <view v-if="active === t.key" class="tabs__line" />
            </view>
        </view>

        <view v-if="loading" class="page-pad">
            <base-skeleton :rows="3" />
        </view>

        <base-empty v-else-if="error" status="error"
            :error-kind="view?.errorKind || 'unknown'"
            :error-text="view?.title" :desc="view?.desc"
            :primary-action="view?.primaryAction"
            fixed @primary="onPrimary" @retry="reload" />

        <base-empty
            v-else-if="!list.length"
            status="empty" empty-icon="empty-order"
            empty-text="暂无指派给你的订单"
            fixed
        />

        <view v-else class="page-pad list">
            <base-card v-for="item in list" :key="item.id" compact clickable @tap="goDetail(item)">
                <view class="head">
                    <text class="head__sn">{{ item.order_sn }}</text>
                    <status-tag :label="statusLabel(item)" :tone="statusTone(item)" size="sm" />
                </view>
                <view class="body">
                    <view class="body__main">
                        <text class="body__title ellipsis">{{ item.package_name || item.item_type_desc || '服务订单' }}</text>
                        <text v-if="item.service_date" class="body__meta">服务日期 {{ item.service_date }}</text>
                        <text v-if="item.contact_name" class="body__meta body__contact">
                            {{ item.contact_name }} {{ maskMobile(item.contact_mobile) }}
                        </text>
                    </view>
                    <price size="md"
                        v-if="item.total_amount !== undefined || item.order_amount !== undefined"
                        class="body__price"
                        :content="item.total_amount ?? item.order_amount ?? 0"
                    />
                </view>
                <view v-if="item.service_address" class="addr ellipsis-2">{{ item.service_address }}</view>
                <view class="foot">
                    <text class="foot__time">{{ item.create_time || '服务订单' }}</text>
                    <text class="foot__link">查看详情</text>
                </view>
            </base-card>

            <load-more :status="loadMoreStatus" @retry="fetchPage(false)" />
        </view>
    </view>
    <app-toast />
</template>

<script setup lang="ts">
/** 服务人员：指派给我的订单（只读） */
import { computed, ref } from 'vue'
import { onLoad, onReachBottom, onPullDownRefresh } from '@dcloudio/uni-app'
import { resolveErrorView } from '@/utils/resolveErrorView'
import {
    ORDER_STATUS_NAME_MAP,
    ORDER_STATUS_TONE,
    resolveStatusLabel,
    resolveTone
} from '@shared/contracts'
import { getStaffOrderList } from '@/api/staff'
import { normalizePage } from '@/request'
import { navigate } from '@/utils/link'

const TABS = [
    { key: 'all', label: '全部', status: undefined as number | undefined },
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

/** O5：真实错误驱动 errorKind / 文案 / 恢复动作（状态机骨架保持不变） */
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))
function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') { reload(); return }
    if (kind === 'reselect' || kind === 'back') { uni.navigateBack(); return }
    if (kind === 'login') { uni.navigateTo({ url: '/pages/login/login' }); return }
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
    const mapped = resolveStatusLabel(ORDER_STATUS_NAME_MAP as any, Number(item.order_status ?? item.status))
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
        const res = await getStaffOrderList({ page: page.value, limit: pageSize, status: activeStatus() })
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
    loading.value = true
    fetchPage(true)
}

function switchTab(key: string) {
    if (key === active.value) return
    active.value = key
    list.value = []
    reload()
}

function goDetail(item: any) {
    // 复用用户侧订单详情；服务人员看到的是同一张订单
    navigate({ path: '/packages/pkg-order/pages/detail/detail', query: { id: item.order_id || item.id } })
}

onLoad(() => fetchPage(true))
onPullDownRefresh(() => fetchPage(true))
onReachBottom(() => fetchPage(false))
</script>

<style lang="scss" scoped>
.staff-orders {
    background: var(--c-bg);
}

.tabs {
    display: flex;
    padding: 0 var(--sp-3);
    background: var(--c-surface);
    position: sticky;
    top: 0;
    z-index: 10;

    &__item {
        position: relative;
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 88rpx;
        padding: 0 var(--sp-1);
    }

    &__text {
        font-size: var(--fs-md);
        color: var(--c-ink-2);
        white-space: nowrap;
    }

    &__item.is-active &__text {
        color: var(--c-ink);
        font-weight: 500;
    }

    &__line {
        position: absolute;
        bottom: 8rpx;
        width: 40rpx;
        height: 4rpx;
        border-radius: var(--r-pill);
        background: var(--c-brand);
    }
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
        line-height: 1.5;
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
        line-height: 1.5;
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
    line-height: 1.6;
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
