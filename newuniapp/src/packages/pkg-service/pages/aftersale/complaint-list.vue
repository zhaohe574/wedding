<template>
    <view class="page-wrap list-page">
        <view v-if="loading" class="page-pad">
            <base-skeleton :rows="3" />
        </view>

        <base-empty v-else-if="error" status="error" error-kind="network" fixed @retry="reload" />

        <base-empty
            v-else-if="!list.length"
            status="empty"
            empty-text="还没有投诉记录"
            action-text="提交投诉"
            :show-action="true"
            fixed
            @action="goCreate"
        />

        <view v-else class="page-pad list">
            <base-card v-for="item in list" :key="item.id" compact clickable @tap="goDetail(item)">
                <view class="head">
                    <text class="head__title ellipsis">{{ item.title || typeText(item) }}</text>
                    <status-tag :label="statusLabel(item)" :tone="statusTone(item)" size="sm" />
                </view>
                <text v-if="item.content" class="content ellipsis-2">{{ item.content }}</text>
                <view class="foot">
                    <text class="foot__meta">{{ typeText(item) }} · {{ levelText(item) }}</text>
                    <text class="foot__time">{{ item.create_time }}</text>
                </view>
            </base-card>

            <view class="loadmore">
                <text v-if="loadingMore" class="loadmore__text">加载中…</text>
                <text v-else-if="!hasMore" class="loadmore__text">没有更多了</text>
            </view>
        </view>

        <view class="action-bar">
            <base-button text="提交投诉" variant="brand" size="md" @click="goCreate" />
        </view>
    </view>
</template>

<script setup lang="ts">
/** 投诉列表 */
import { ref } from 'vue'
import { onLoad, onReachBottom, onShow } from '@dcloudio/uni-app'
import {
    COMPLAINT_STATUS_NAME_MAP,
    COMPLAINT_STATUS_TONE,
    COMPLAINT_TYPE_NAME_MAP,
    COMPLAINT_LEVEL_NAME_MAP,
    resolveStatusLabel,
    resolveTone
} from '@shared/contracts'
import { getComplaintList } from '@/api/aftersale'
import { normalizePage } from '@/request'
import { navigate } from '@/utils/link'

const list = ref<any[]>([])
const page = ref(1)
const pageSize = 10
const hasMore = ref(true)
const loadingMore = ref(false)
const loading = ref(true)
const error = ref(false)

function typeText(item: any) {
    return (COMPLAINT_TYPE_NAME_MAP as Record<number, string>)[Number(item.type)] || '投诉'
}

function levelText(item: any) {
    return (COMPLAINT_LEVEL_NAME_MAP as Record<number, string>)[Number(item.level)] || '-'
}

function statusLabel(item: any) {
    const mapped = resolveStatusLabel(COMPLAINT_STATUS_NAME_MAP as any, Number(item.status))
    return mapped.known ? mapped.label : item.status_desc || mapped.label
}

function statusTone(item: any) {
    return resolveTone(COMPLAINT_STATUS_TONE as any, Number(item.status))
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
        const res = await getComplaintList({ page: page.value, limit: pageSize })
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

function goDetail(item: any) {
    navigate({ path: '/packages/pkg-service/pages/aftersale/complaint-detail', query: { id: item.id } })
}

function goCreate() {
    navigate({ path: '/packages/pkg-service/pages/aftersale/complaint-create' })
}

onLoad(() => fetchPage(true))
onShow(() => {
    if (!loading.value) fetchPage(true)
})
onReachBottom(() => fetchPage(false))
</script>

<style lang="scss" scoped>
.list-page {
    background: var(--c-bg);
    padding-bottom: calc(160rpx + var(--safe-bottom));
}

.list {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    padding-top: var(--sp-3);
}

.head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-3);

    &__title {
        flex: 1;
        font-size: var(--fs-md);
        font-weight: 500;
        color: var(--c-ink);
        min-width: 0;
    }
}

.content {
    display: block;
    margin-top: var(--sp-2);
    font-size: var(--fs-xs);
    color: var(--c-ink-2);
    line-height: 1.7;
    overflow-wrap: anywhere;
}

.foot {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--sp-3);
    margin-top: var(--sp-3);
    padding-top: var(--sp-3);
    border-top: 1rpx solid var(--c-line);

    &__meta,
    &__time {
        font-size: var(--fs-xs);
        line-height: 1.5;
        color: var(--c-ink-3);
    }

    &__meta {
        flex: 1;
        min-width: 0;
        overflow-wrap: anywhere;
    }

    &__time {
        flex-shrink: 0;
        text-align: right;
    }
}

.loadmore {
    padding: var(--sp-4) 0 var(--sp-5);
    text-align: center;

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.action-bar {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 50;
    padding: var(--sp-3) var(--sp-4) calc(var(--sp-3) + var(--safe-bottom));
    background: var(--c-surface);
    border-top: 1rpx solid var(--c-line);
    box-sizing: border-box;
}
</style>
