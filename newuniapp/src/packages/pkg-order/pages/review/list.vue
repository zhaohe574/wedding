<template>
    <!-- 头部统一：走 page-shell 的 nav-bar，不再依赖系统导航 -->
    <page-shell title="我的评价" bg="page" :show-back="true">
        <view class="page-wrap review-list">
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
                @retry="load"
            />

            <base-empty
                v-else-if="!rows.length"
                status="empty"
                empty-icon="empty-order"
                :empty-text="active === 'pending' ? '没有待评价的服务' : '还没有评价记录'"
                :desc="FB.EMPTY_HINT_REVIEW"
                fixed
            />

            <view v-else class="page-pad list">
                <!-- 待评价：按订单项展开 -->
                <template v-if="active === 'pending'">
                    <base-card v-for="o in rows" :key="o.id" compact>
                        <view class="order-head">
                            <text class="order-head__sn">{{ o.order_sn }}</text>
                            <text class="order-head__date">{{ o.create_time }}</text>
                        </view>
                        <view v-for="it in o.items || []" :key="it.id" class="item">
                            <view class="item__body">
                                <text class="item__title ellipsis">{{
                                    it.package_name || it.staff_name || '婚庆服务'
                                }}</text>
                                <text v-if="it.staff_name" class="item__meta">{{
                                    it.staff_name
                                }}</text>
                            </view>
                            <base-button
                                text="去评价"
                                size="sm"
                                variant="ghost"
                                :block="false"
                                @click="goPublish(it)"
                            />
                        </view>
                    </base-card>
                </template>

                <!-- 已评价 -->
                <template v-else>
                    <base-card v-for="r in rows" :key="r.id">
                        <view class="rv">
                            <view class="rv__head">
                                <text class="rv__score">★ {{ r.score }}</text>
                                <status-tag
                                    :label="reviewStatusLabel(r)"
                                    :tone="reviewStatusTone(r)"
                                    size="sm"
                                />
                            </view>
                            <text class="rv__content ellipsis-2">{{
                                r.content || '（无文字评价）'
                            }}</text>
                            <view class="rv__foot">
                                <text class="rv__time">{{ r.create_time }}</text>
                                <text v-if="r.staff_name" class="rv__staff">{{
                                    r.staff_name
                                }}</text>
                            </view>
                        </view>
                    </base-card>
                </template>
            </view>
        </view>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
/** 我的评价：待评价（按订单项）/ 已评价 */
import { computed, ref } from 'vue'
import { onLoad, onShow, onPullDownRefresh } from '@dcloudio/uni-app'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { FB } from '@/constants/feedback'
import {
    REVIEW_STATUS_NAME_MAP,
    REVIEW_STATUS_TONE,
    resolveStatusLabel,
    resolveTone
} from '@shared/contracts'
import { getPendingReviews, getMyReviews } from '@/api/content'
import { navigate } from '@/utils/link'

const TABS = [
    { key: 'pending', label: '待评价' },
    { key: 'done', label: '已评价' }
]

const active = ref('pending')
const rows = ref<any[]>([])
const loading = ref(true)
const error = ref(false)

/** O5：真实错误驱动 errorKind / 文案 / 恢复动作（状态机骨架保持不变） */
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))
function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        load()
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

function reviewStatusLabel(r: any) {
    const mapped = resolveStatusLabel(REVIEW_STATUS_NAME_MAP as any, Number(r.status))
    return mapped.known ? mapped.label : r.status_desc || mapped.label
}

function reviewStatusTone(r: any) {
    return resolveTone(REVIEW_STATUS_TONE as any, Number(r.status))
}

async function load() {
    loading.value = true
    error.value = false
    try {
        if (active.value === 'pending') {
            const res = await getPendingReviews()
            rows.value = Array.isArray(res) ? res : res?.lists || res?.data || []
        } else {
            const res = await getMyReviews({ page: 1, page_size: 20 })
            rows.value = Array.isArray(res) ? res : res?.lists || res?.data || []
        }
    } catch (e) {
        rawError.value = e
        error.value = true
    } finally {
        loading.value = false
    }
}

function switchTab(key: string) {
    if (key === active.value) return
    active.value = key
    rows.value = []
    load()
}

function goPublish(item: any) {
    navigate({
        path: '/packages/pkg-order/pages/review/publish',
        query: {
            order_item_id: item.id,
            staff_name: item.staff_name || '',
            package_name: item.package_name || ''
        }
    })
}

onLoad(() => load())
onShow(() => {
    if (!loading.value) load()
})
/** P1 成对条目：pages.json 的 enablePullDownRefresh 已配；finally 复位，失败也不让圈一直转 */
onPullDownRefresh(async () => {
    try {
        await load()
    } finally {
        uni.stopPullDownRefresh()
    }
})
</script>

<style lang="scss" scoped>
.review-list {
    background: var(--c-bg);
}

/* P1 §3-1：下划线 tab → 胶囊版式（照 P0 的 search / case-list 成品，不新起样式） */
.tabs {
    display: flex;
    gap: var(--sp-2);
    padding: var(--sp-2) var(--sp-4);
    background: var(--c-surface);
    position: sticky;
    top: 0;
    z-index: 10;

    &__item {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 56rpx;
        padding: 0 var(--sp-3);
        box-sizing: border-box;
        border: 1rpx solid var(--c-line-strong);
        border-radius: var(--r-pill);
        background: var(--c-surface);

        /* 视觉 56rpx，触控 88rpx（56 + 16×2）；只做纵向，避免与相邻 tab 热区重叠 */
        &::before {
            content: '';
            position: absolute;
            top: -16rpx;
            bottom: -16rpx;
            left: 0;
            right: 0;
        }

        &.is-active {
            background: var(--c-ink);
            border-color: var(--c-ink);
        }
    }

    &__text {
        font-size: var(--fs-md);
        color: var(--c-ink-2);
    }

    &__item.is-active &__text {
        color: var(--c-ink-inverse);
        font-weight: 500;
    }
}

.list {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    padding-top: var(--sp-3);
    padding-bottom: calc(var(--sp-4) + var(--safe-bottom));
}

.order-head {
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

    &__date {
        flex-shrink: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.item {
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    padding: var(--sp-3) 0 0;

    & + & {
        padding-bottom: var(--sp-3);
        border-bottom: 1rpx solid var(--c-line);
    }

    &__body {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
        min-width: 0;
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: 500;
        color: var(--c-ink);
    }

    &__meta {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        overflow-wrap: anywhere;
    }
}

.rv {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-3);
    }

    &__score {
        font-size: var(--fs-md);
        color: var(--c-warning);
        font-weight: 500;
    }

    &__content {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
        line-height: 1.7;
        overflow-wrap: anywhere;
    }

    &__foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-3);
        padding-top: var(--sp-2);
        border-top: 1rpx solid var(--c-line);
    }

    &__time,
    &__staff {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__staff {
        min-width: 0;
        text-align: right;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }
}
</style>
