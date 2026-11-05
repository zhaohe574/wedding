<template>
    <view class="page-wrap list-page">
        <view v-if="loading" class="page-pad">
            <base-skeleton :rows="3" />
        </view>

        <base-empty v-else-if="error" status="error" error-kind="network" fixed @retry="reload" />

        <base-empty v-else-if="!list.length" status="empty" empty-text="暂无回访任务" fixed />

        <view v-else class="page-pad list">
            <base-card v-for="item in list" :key="item.id" compact clickable @tap="goDetail(item)">
                <view class="head">
                    <text class="head__title">{{ item.type_desc || '服务回访' }}</text>
                    <status-tag :label="statusLabel(item)" :tone="statusTone(item)" size="sm" />
                </view>
                <view v-if="item.order_sn" class="kv">
                    <text class="kv__k">关联订单</text>
                    <text class="kv__v">{{ item.order_sn }}</text>
                </view>
                <view v-if="item.staff_name" class="kv">
                    <text class="kv__k">服务人员</text>
                    <text class="kv__v">{{ item.staff_name }}</text>
                </view>
                <view class="kv">
                    <text class="kv__k">发起时间</text>
                    <text class="kv__v">{{ item.create_time }}</text>
                </view>

                <view v-if="canFill(item)" class="foot">
                    <base-button
                        text="填写回访问卷"
                        variant="brand"
                        size="sm"
                        :block="false"
                        @click.stop="goDetail(item)"
                    />
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
/** 服务回访列表 */
import { ref } from 'vue'
import { onLoad, onReachBottom, onShow } from '@dcloudio/uni-app'
import {
    SERVICE_CALLBACK_STATUS_NAME_MAP,
    SERVICE_CALLBACK_STATUS_TONE,
    ServiceCallbackStatus,
    resolveStatusLabel,
    resolveTone
} from '@shared/contracts'
import { getCallbackList } from '@/api/aftersale'
import { normalizePage } from '@/request'
import { navigate } from '@/utils/link'

const list = ref<any[]>([])
const page = ref(1)
const pageSize = 10
const hasMore = ref(true)
const loadingMore = ref(false)
const loading = ref(true)
const error = ref(false)

function statusLabel(item: any) {
    const mapped = resolveStatusLabel(SERVICE_CALLBACK_STATUS_NAME_MAP as any, Number(item.status))
    return mapped.known ? mapped.label : item.status_desc || mapped.label
}

function statusTone(item: any) {
    return resolveTone(SERVICE_CALLBACK_STATUS_TONE as any, Number(item.status))
}

/** 待回访 + 方式是问卷，才可填写 */
function canFill(item: any) {
    return Number(item.status) === ServiceCallbackStatus.PENDING && Number(item.method) === 2
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
        const res = await getCallbackList({ page: page.value, limit: pageSize })
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
    navigate({ path: '/packages/pkg-service/pages/aftersale/callback-detail', query: { id: item.id } })
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
    margin-top: var(--sp-3);
    padding-top: var(--sp-3);
    border-top: 1rpx solid var(--c-line);
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
