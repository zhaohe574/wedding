<template>
    <!-- 头部统一：走 page-shell 的 nav-bar，不再依赖系统导航 -->
    <page-shell title="服务回访" bg="page" :show-back="true">
        <view class="page-wrap list-page">
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

            <!-- P2 §3-1：空态补 icon + desc。不给 action-text —— 本页是「等待商家发起」，
                 用户没有可执行动作，写不出下一步就不写（只解释为什么空）。 -->
            <base-empty
                v-else-if="!list.length"
                status="empty"
                empty-icon="empty-comment"
                empty-text="暂无回访任务"
                desc="服务完成后，商家会发起回访"
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
                        <text class="head__title">{{ item.type_desc || '服务回访' }}</text>
                        <status-tag :label="statusLabel(item)" :tone="statusTone(item)" size="sm" />
                    </view>
                    <!-- P2 §3-1：原来 3 个 .kv 行（单号/人员/时间）各占一行，三项都是元信息、
                         都不是用户要判断的东西，三行占掉卡片一半高度，把标题/状态/填写按钮挤到看不见。
                         压成一行 meta，缺项自动跳过（不留空的分隔符）。 -->
                    <text v-if="metaText(item)" class="meta-row ellipsis">{{ metaText(item) }}</text>

                    <view v-if="canFill(item)" class="foot">
                        <base-button
                            text="填写回访问卷"
                            variant="ghost"
                            size="sm"
                            :block="false"
                            @click.stop="goDetail(item)"
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
/** 服务回访列表 */
import { computed, ref } from 'vue'
import { onLoad, onReachBottom, onShow } from '@dcloudio/uni-app'
import { resolveErrorView } from '@/utils/resolveErrorView'
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

function statusLabel(item: any) {
    const mapped = resolveStatusLabel(SERVICE_CALLBACK_STATUS_NAME_MAP as any, Number(item.status))
    return mapped.known ? mapped.label : item.status_desc || mapped.label
}

function statusTone(item: any) {
    return resolveTone(SERVICE_CALLBACK_STATUS_TONE as any, Number(item.status))
}

/** 卡片元信息：单号 · 人员 · 时间（缺项自动跳过，不留空的分隔符） */
function metaText(item: any) {
    return [item.order_sn, item.staff_name, item.create_time].filter(Boolean).join(' · ')
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
    loadMoreFailed.value = false
    try {
        const res = await getCallbackList({ page: page.value, limit: pageSize })
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
        path: '/packages/pkg-service/pages/aftersale/callback-detail',
        query: { id: item.id }
    })
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

/* 元信息一行（P2 §3-1）：单号 · 人员 · 时间，纯数字/短标签走 --fs-xs */
.meta-row {
    display: block;
    margin-top: var(--sp-1);
    font-size: var(--fs-xs);
    line-height: var(--lh-body);
    color: var(--c-ink-3);
}

.foot {
    display: flex;
    justify-content: flex-end;
    margin-top: var(--sp-3);
    padding-top: var(--sp-3);
    border-top: 1rpx solid var(--c-line);
}
</style>
