<template>
    <view class="page-wrap change-list">
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

        <base-empty v-else-if="error" status="error" error-kind="network" fixed @retry="reload" />

        <base-empty v-else-if="!list.length" status="empty" :empty-text="emptyText" fixed />

        <view v-else class="page-pad list">
            <base-card v-for="item in list" :key="item.id" compact clickable @tap="goDetail(item)">
                <view class="head">
                    <text class="head__title">{{ titleOf(item) }}</text>
                    <status-tag :label="statusLabel(item)" :tone="statusTone(item)" size="sm" />
                </view>

                <view v-if="active === 'change' && item.reason" class="reason ellipsis-2">{{ item.reason }}</view>
                <view v-if="active === 'pause' && item.reason" class="reason ellipsis-2">{{ item.reason }}</view>

                <view class="kv">
                    <text class="kv__k">{{ active === 'pause' ? '暂停区间' : '申请时间' }}</text>
                    <text class="kv__v">{{ active === 'pause' ? rangeText(item) : item.create_time }}</text>
                </view>
                <view v-if="item.order_sn" class="kv">
                    <text class="kv__k">关联订单</text>
                    <text class="kv__v">{{ item.order_sn }}</text>
                </view>

                <view v-if="canCancel(item)" class="foot">
                    <base-button text="撤销申请" variant="danger" size="sm" :block="false" @click.stop="handleCancel(item)" />
                </view>
            </base-card>

            <view class="loadmore">
                <text v-if="loadingMore" class="loadmore__text">加载中…</text>
                <text v-else-if="!hasMore" class="loadmore__text">没有更多了</text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
/** 变更记录：改期/加项 与 暂停 两条独立列表（后端也是两套接口） */
import { computed, ref } from 'vue'
import { onLoad, onReachBottom, onShow } from '@dcloudio/uni-app'
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

const emptyText = computed(() => (active.value === 'pause' ? '还没有暂停申请' : '还没有变更申请'))

function titleOf(item: any) {
    if (active.value === 'pause') {
        return (ORDER_PAUSE_TYPE_NAME_MAP as Record<number, string>)[Number(item.pause_type)] || '暂停申请'
    }
    return (ORDER_CHANGE_TYPE_NAME_MAP as Record<number, string>)[Number(item.change_type)] || '变更申请'
}

function statusLabel(item: any) {
    if (active.value === 'pause') {
        const mapped = resolveStatusLabel(ORDER_PAUSE_STATUS_NAME_MAP as any, Number(item.status))
        return mapped.known ? mapped.label : item.status_desc || mapped.label
    }
    const mapped = resolveStatusLabel(ORDER_CHANGE_STATUS_NAME_MAP as any, Number(item.status))
    return mapped.known ? mapped.label : item.status_desc || mapped.label
}

function statusTone(item: any) {
    return active.value === 'pause'
        ? resolveTone(ORDER_PAUSE_STATUS_TONE as any, Number(item.status))
        : resolveTone(ORDER_CHANGE_STATUS_TONE as any, Number(item.status))
}

function rangeText(item: any) {
    if (!item.start_date && !item.end_date) return '-'
    return `${item.start_date || ''} ~ ${item.end_date || ''}`
}

function canCancel(item: any) {
    const s = Number(item.status)
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
    try {
        const res =
            active.value === 'pause'
                ? await getPauseList({ page: page.value, limit: pageSize })
                : await getChangeList({ page: page.value, limit: pageSize })
        const paged = normalizePage<any>(res, page.value, pageSize)
        list.value = reset ? paged.list : [...list.value, ...paged.list]
        hasMore.value = paged.hasMore
        if (hasMore.value) page.value += 1
    } catch {
        if (reset) error.value = true
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

function goDetail(item: any) {
    navigate({
        path: '/packages/pkg-service/pages/change/detail',
        query: { id: item.id, type: active.value }
    })
}

async function handleCancel(item: any) {
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
        uni.showToast({ title: '已撤销', icon: 'none' })
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

onReachBottom(() => fetchPage(false))
</script>

<style lang="scss" scoped>
.change-list {
    background: var(--c-bg);
}

.tabs {
    display: flex;
    gap: var(--sp-5);
    padding: 0 var(--sp-4);
    background: var(--c-surface);
    position: sticky;
    top: 0;
    z-index: 10;

    &__item {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 88rpx;
        padding: 0 var(--sp-2);
    }

    &__text {
        font-size: var(--fs-md);
        color: var(--c-ink-2);
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
        font-weight: 500;
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
    line-height: 1.7;
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
        line-height: 1.5;
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

.loadmore {
    padding: var(--sp-4) 0 var(--sp-5);
    text-align: center;

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}
</style>
