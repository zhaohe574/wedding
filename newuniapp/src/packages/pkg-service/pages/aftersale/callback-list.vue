<template>
    <!-- 头部统一：走 page-shell 的 nav-bar，不再依赖系统导航 -->
    <page-shell title="服务回访" bg="page" :show-back="true">
        <view class="page-wrap list-page">
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

            <view v-else class="list-pad list">
                <!-- 顶部轻概览卡 -->
                <view class="intro-card" :class="{ 'intro-card--highlight': pendingCount > 0 }">
                    <view class="intro-card__ic">
                        <tn-icon name="chat" :size="26" />
                    </view>
                    <view class="intro-card__body">
                        <text class="intro-card__title">{{
                            pendingCount > 0
                                ? `有 ${pendingCount} 份服务回访问卷待填写`
                                : '服务回访记录'
                        }}</text>
                        <text class="intro-card__desc">{{
                            pendingCount > 0
                                ? '填写只需 1 分钟，帮助我们持续优化服务品质'
                                : '您的每一次反馈都在帮助我们打磨婚礼服务体验'
                        }}</text>
                    </view>
                </view>

                <base-card
                    v-for="item in list"
                    :key="item.id"
                    compact
                    clickable
                    @tap="goDetail(item)"
                >
                    <view class="head">
                        <view class="head__left">
                            <view
                                class="head__ic"
                                :class="canFill(item) ? 'head__ic--pending' : 'head__ic--done'"
                            >
                                <tn-icon name="chat" :size="24" />
                            </view>
                            <text class="head__title ellipsis">{{
                                item.type_desc || '服务回访'
                            }}</text>
                        </view>
                        <status-tag :label="statusLabel(item)" :tone="statusTone(item)" size="sm" />
                    </view>

                    <view v-if="metaItems(item).length" class="meta-list">
                        <text v-for="(m, mi) in metaItems(item)" :key="mi" class="meta-row">{{
                            m
                        }}</text>
                    </view>

                    <view v-if="canFill(item)" class="foot">
                        <text class="foot__hint">待您反馈本次服务体验</text>
                        <base-button
                            text="立即填写问卷"
                            variant="ink"
                            size="sm"
                            :block="false"
                            @click.stop="goDetail(item)"
                        />
                    </view>
                    <view v-else class="foot foot--done">
                        <text class="foot__done-text">感谢您的宝贵反馈，点击可查看回访详情</text>
                        <tn-icon name="right" :size="20" class="foot__arrow" />
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

const pendingCount = computed(() => list.value.filter((item) => canFill(item)).length)

/** 卡片元信息项：单号 / 人员 / 时间（缺项自动跳过） */
function metaItems(item: any): string[] {
    const items: string[] = []
    if (item.order_sn) items.push(`订单 ${item.order_sn}`)
    if (item.staff_name) items.push(`服务人员 ${item.staff_name}`)
    if (item.create_time) items.push(`发起时间 ${item.create_time}`)
    return items
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

.list-pad {
    padding-left: var(--page-gutter);
    padding-right: var(--page-gutter);
}

.list {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    padding-top: var(--sp-3);
    padding-bottom: calc(var(--sp-4) + var(--safe-bottom));
}

.intro-card {
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    padding: var(--sp-3) var(--sp-4);
    border-radius: var(--r-md);
    background: var(--c-surface);
    border: 1rpx solid var(--c-line);

    &--highlight {
        background: var(--c-brand-bg);
        border-color: var(--c-primary-border);

        .intro-card__ic {
            background: var(--c-dark-1);
            color: var(--c-brand-soft);
        }

        .intro-card__title {
            color: var(--c-brand-text);
        }
    }

    &__ic {
        width: 56rpx;
        height: 56rpx;
        border-radius: var(--r-sm);
        flex: none;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--c-surface-2);
        color: var(--c-ink-2);
    }

    &__body {
        flex: 1;
        min-width: 0;
    }

    &__title {
        display: block;
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__desc {
        display: block;
        margin-top: 4rpx;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }
}

.head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);
    padding-bottom: var(--sp-3);
    border-bottom: 1rpx solid var(--c-line);

    &__left {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: var(--sp-2);
    }

    &__ic {
        width: 48rpx;
        height: 48rpx;
        border-radius: var(--r-sm);
        flex: none;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1rpx solid var(--c-line);

        &--pending {
            background: var(--c-brand-bg);
            border-color: var(--c-primary-border);
            color: var(--c-brand-text);
        }

        &--done {
            background: var(--c-success-bg);
            border-color: var(--c-success-border);
            color: var(--c-success);
        }
    }

    &__title {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }
}

.meta-list {
    display: flex;
    flex-direction: column;
    gap: var(--sp-1);
    margin-top: var(--sp-2);
}

.meta-row {
    display: block;
    font-size: var(--fs-xs);
    line-height: var(--lh-body);
    color: var(--c-ink-3);
    overflow-wrap: anywhere;
}

.foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);
    margin-top: var(--sp-3);
    padding-top: var(--sp-3);
    border-top: 1rpx solid var(--c-line);

    &__hint {
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
    }

    &__done-text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__arrow {
        color: var(--c-ink-3);
        flex: none;
    }
}
</style>
