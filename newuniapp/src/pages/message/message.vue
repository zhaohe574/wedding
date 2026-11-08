<template>
    <!--
        16 通知与提醒（设计稿 3562–3677）
        设计意图：通知是「还没做完的事」的索引，每条都指回一个能动手的地方。

        与设计稿的两处差异（均因为接口能力）：
        - 「删除已读」批量操作不做：后端只有 delete(单条) 与 clear(按类型清空)，
          clear 会把该类型下的未读一起清掉，风险大于收益 → 改为单条左滑删除。
        - 类型 chips 不带数量：数量需要按类型分别统计，现有接口没有分组计数，
          用已加载的那一页去数会给出错误数字（比不给更糟）。
    -->
    <page-shell title="通知与提醒" bg="page" :show-back="true" :nav-capsule-avoid="true">
        <template #nav-right>
            <view
                class="nav-act"
                :class="{ 'is-off': unreadCount === 0 || marking }"
                hover-class="is-pressed"
                aria-role="button"
                aria-label="全部标为已读"
                @tap="onMarkAll"
            >
                <text class="nav-act__text">全部已读</text>
            </view>
        </template>

        <!-- 未读汇总条：数字来自接口，不是自己数的 -->
        <view v-if="!loading && !error && list.length" class="bar">
            <text class="bar__text">
                <template v-if="unreadCount > 0">
                    <text class="bar__num">{{ unreadCount }}</text>
                    <text> 条未读 · </text>
                </template>
                <text v-else>已全部读完 · </text>
                <text>共 {{ total }} 条</text>
            </text>
        </view>

        <view class="chips">
            <view
                v-for="c in tabs"
                :key="c.key"
                class="chip"
                :class="{ 'is-on': activeType === c.key }"
                hover-class="is-pressed"
                @tap="switchType(c.key)"
            >
                <text class="chip__text">{{ c.label }}</text>
            </view>
        </view>

        <view v-if="loading" class="pad">
            <base-skeleton :rows="5" />
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
            empty-text="暂时没有通知"
            desc="订单进度、问卷与活动提醒会出现在这里。"
            fixed
        />

        <view v-else class="nt">
            <template v-for="g in groups" :key="g.label">
                <view class="nt__group">{{ g.label }}</view>

                <tn-swipe-action v-for="item in g.items" :key="item.id">
                    <tn-swipe-action-item :options="swipeOptions" @click="onDelete(item)">
                        <view
                            class="item"
                            :class="{ 'is-read': item.is_read === 1 }"
                            hover-class="is-pressed"
                            @tap="onOpen(item)"
                        >
                            <view class="item__ic" :class="`item__ic--${toneOf(item)}`">
                                <tn-icon
                                    :name="iconOf(item)"
                                    :size="30"
                                    :color="iconColorOf(item)"
                                />
                            </view>
                            <view class="item__body">
                                <view class="item__t">
                                    <text class="item__title">{{ item.title }}</text>
                                    <view v-if="item.is_read !== 1" class="item__dot" />
                                </view>
                                <text class="item__p">{{ item.content }}</text>
                                <view class="item__foot">
                                    <text class="item__time">{{ timeOf(item) }}</text>
                                    <view v-if="item.target_type" class="item__go">
                                        <text class="item__go-text">去看看</text>
                                        <tn-icon name="right" :size="18" color="var(--c-ink-3)" />
                                    </view>
                                </view>
                            </view>
                        </view>
                    </tn-swipe-action-item>
                </tn-swipe-action>
            </template>

            <load-more :status="loadMoreStatus" @retry="loadMore" />
            <view v-if="loadMoreStatus === 'nomore'" class="nt__end">
                <text class="nt__end-text">— 没有更多通知 —</text>
            </view>
        </view>
    </page-shell>
    <app-toast />
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onPullDownRefresh, onReachBottom, onShow } from '@dcloudio/uni-app'
import {
    getNotificationList,
    getUnreadCount,
    markRead,
    markAllRead,
    deleteNotification,
    type NotificationItem
} from '@/api/message'
import { normalizePage, toArray } from '@/request'
import { resolveNotificationRoute, NotifyType } from '@/composables/useNotificationRouter'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { toast } from '@/composables/useToast'
import { report } from '@/platform/report'

const tabs = [
    { key: 0, label: '全部' },
    { key: NotifyType.ORDER, label: '订单' },
    { key: NotifyType.ACTIVITY, label: '活动' },
    { key: NotifyType.INTERACT, label: '服务' }
]

const list = ref<NotificationItem[]>([])
const total = ref(0)
const unreadCount = ref(0)
const activeType = ref(0)
const loading = ref(true)
const marking = ref(false)
const rawError = ref<unknown>(null)
const error = computed(() => !!rawError.value)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))

const loadMoreStatus = ref<'idle' | 'loading' | 'nomore' | 'error'>('idle')
let page = 1
const pageSize = 10
let hasMore = true
let seq = 0
let appending = false

const swipeOptions = [{ text: '删除', style: { backgroundColor: '#B4453C' } }]

/** 按「今天 / 更早」分组：用接口给的 create_time_text 判断，不自己解析时区 */
const groups = computed(() => {
    const today: NotificationItem[] = []
    const earlier: NotificationItem[] = []
    list.value.forEach((i) => {
        const t = timeOf(i)
        if (t.indexOf('今天') === 0 || /^\d{1,2}:\d{2}$/.test(t)) today.push(i)
        else earlier.push(i)
    })
    return [
        { label: `今天 · ${today.length} 条`, items: today },
        { label: '更早', items: earlier }
    ].filter((g) => g.items.length > 0)
})

function timeOf(item: NotificationItem) {
    return item.create_time_text || (item.create_time || '').slice(5, 16)
}

function iconOf(item: NotificationItem) {
    if (item.notify_type === NotifyType.ORDER) return 'money'
    if (item.notify_type === NotifyType.ACTIVITY) return 'ticket'
    return 'message'
}

function toneOf(item: NotificationItem) {
    if (item.notify_type === NotifyType.ORDER) return 'gold'
    if (item.notify_type === NotifyType.ACTIVITY) return 'gold'
    if (item.notify_type === NotifyType.INTERACT) return 'sage'
    return 'plain'
}

function iconColorOf(item: NotificationItem) {
    const tone = toneOf(item)
    if (tone === 'gold') return 'var(--c-brand-text)'
    if (tone === 'sage') return 'var(--c-success)'
    return 'var(--c-ink-2)'
}

function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        reload()
        return
    }
    if (kind === 'login') {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    if (kind === 'reselect' || kind === 'back') uni.navigateBack()
}

async function fetchUnread() {
    try {
        const res: any = await getUnreadCount()
        const n = Number(res?.total ?? res?.count ?? res?.unread ?? 0)
        unreadCount.value = Number.isFinite(n) ? n : 0
    } catch {
        // 未读数失败不影响列表
    }
}

async function fetchPage(reset = false) {
    const mySeq = ++seq
    if (reset) {
        page = 1
        hasMore = true
        loading.value = true
        rawError.value = null
    } else {
        if (appending || !hasMore) return
        appending = true
        loadMoreStatus.value = 'loading'
    }

    try {
        const res: any = await getNotificationList({
            page,
            page_size: pageSize,
            ...(activeType.value ? { type: activeType.value } : {})
        })
        if (mySeq !== seq) return

        const pageData = normalizePage<any>(res)
        const rows = toArray<NotificationItem>(pageData.list)
        total.value = Number(pageData.total || rows.length)
        list.value = reset ? rows : list.value.concat(rows)
        hasMore = list.value.length < total.value && rows.length > 0
        page += 1
        loadMoreStatus.value = hasMore ? 'idle' : 'nomore'
    } catch (e) {
        if (mySeq !== seq) return
        if (reset) rawError.value = e
        else loadMoreStatus.value = 'error'
        report('api_error', { endpoint: 'notification.lists', message: '通知列表加载失败' })
    } finally {
        if (mySeq === seq) {
            loading.value = false
            appending = false
        }
    }
}

function reload() {
    fetchUnread()
    fetchPage(true)
}

function loadMore() {
    fetchPage(false)
}

function switchType(key: number) {
    if (activeType.value === key) return
    activeType.value = key
    list.value = []
    reload()
}

async function onMarkAll() {
    if (unreadCount.value === 0 || marking.value) return
    marking.value = true
    try {
        await markAllRead(activeType.value ? { type: activeType.value } : {})
        list.value = list.value.map((i) => ({ ...i, is_read: 1 }))
        unreadCount.value = 0
        toast.success('已全部标为已读')
    } catch {
        toast.error('操作失败，请重试')
    } finally {
        marking.value = false
    }
}

async function onOpen(item: NotificationItem) {
    if (item.is_read !== 1) {
        // 先本地置读，再上报；失败不回滚（用户已经看过了）
        item.is_read = 1
        unreadCount.value = Math.max(0, unreadCount.value - 1)
        markRead({ id: item.id }).catch(() => undefined)
    }
    if (item.target_type) resolveNotificationRoute(item)
}

async function onDelete(item: NotificationItem) {
    try {
        await deleteNotification({ id: item.id })
        list.value = list.value.filter((i) => i.id !== item.id)
        total.value = Math.max(0, total.value - 1)
        if (item.is_read !== 1) unreadCount.value = Math.max(0, unreadCount.value - 1)
        toast.info('已删除')
    } catch {
        toast.error('删除失败，请重试')
    }
}

onShow(() => reload())
onPullDownRefresh(() => {
    reload()
    uni.stopPullDownRefresh()
})
onReachBottom(() => loadMore())
</script>

<style lang="scss" scoped>
/*
 * ⚠ 已知非缺陷：「全部已读」是文字动作，宽约 104rpx，比 88rpx 最小热区宽，
 * 会把 nav-bar 的居中标题盒压窄，标题因此比屏幕中线偏左约 10–20rpx（叠上胶囊避让更明显）。
 * 这是「文字动作 + 居中标题 + 胶囊避让」三者在窄屏上的必然结果，不是样式写错。
 * 根治方向：把批量已读下沉到内容区顶部（属于交互改版，本次不做）。
 * 别为了让它「看起来居中」去改 nav-bar 的 .nav__right 宽度 —— 会把别的页面挤换行。
 */
.nav-act {
    display: flex;
    align-items: center;
    min-height: 88rpx;
    padding: 0 var(--sp-2);

    &__text {
        font-size: var(--fs-23);
        color: var(--c-ink-2);
        letter-spacing: 0.04em;
    }

    &.is-off .nav-act__text {
        color: var(--c-ink-3);
    }
}

.bar {
    padding: 26rpx 42rpx 0;

    &__text {
        font-size: var(--fs-22);
        color: var(--c-ink-3);
        letter-spacing: 0.04em;
    }

    &__num {
        font-family: var(--font-serif);
        font-size: 28rpx;
        color: var(--c-brand-text);
    }
}

.chips {
    display: flex;
    gap: 12rpx;
    padding: 22rpx 42rpx 8rpx;
    overflow-x: auto;
}

.chip {
    flex: none;
    height: 52rpx;
    padding: 0 24rpx;
    border-radius: var(--r-pill);
    /* 可点控件边界（模板 @tap="switchType" + hover-class）：原 --c-line-strong 对白底 1.60:1 不可见，
       按 chip-group 既有注释「控件边界 ≥3:1，禁用 --c-line」改 --c-control-border（3.77:1）。 */
    border: 1rpx solid var(--c-control-border);
    background: var(--c-surface);
    display: flex;
    align-items: center;

    &__text {
        font-size: var(--fs-22);
        color: var(--c-ink-2);
    }

    &.is-on {
        background: var(--c-ink);
        border-color: var(--c-ink);

        .chip__text {
            color: var(--c-on-dark);
        }
    }
}

.pad {
    padding: var(--sp-4);
}

.nt {
    padding: 0 0 calc(var(--sp-6) + var(--safe-bottom));

    &__group {
        padding: 26rpx 42rpx 10rpx;
        font-size: 20rpx;
        letter-spacing: 0.16em;
        color: var(--c-ink-3);
    }

    &__end {
        padding: 28rpx 0 10rpx;
        text-align: center;
    }

    &__end-text {
        font-size: 20rpx;
        color: var(--c-ink-3);
        letter-spacing: 0.1em;
    }
}

.item {
    display: flex;
    gap: 20rpx;
    margin: 0 42rpx 16rpx;
    padding: 24rpx;
    border-radius: var(--r-md);
    background: var(--c-surface);
    border: 1rpx solid var(--c-line);

    &__ic {
        width: 64rpx;
        height: 64rpx;
        border-radius: var(--r-20);
        display: flex;
        align-items: center;
        justify-content: center;
        flex: none;
        background: var(--c-surface-2);

        &--gold {
            background: var(--c-brand-bg);
        }

        &--sage {
            background: var(--c-success-bg);
        }
    }

    &__body {
        flex: 1;
        min-width: 0;
    }

    &__t {
        display: flex;
        align-items: center;
        gap: 10rpx;
    }

    &__title {
        font-size: var(--fs-27);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__dot {
        width: 12rpx;
        height: 12rpx;
        border-radius: var(--r-pill);
        background: var(--c-danger);
        flex: none;
    }

    &__p {
        display: block;
        margin-top: 8rpx;
        font-size: var(--fs-22);
        line-height: 1.65;
        color: var(--c-ink-2);
    }

    &__foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 12rpx;
    }

    &__time {
        font-size: 20rpx;
        color: var(--c-ink-3);
    }

    &__go {
        display: flex;
        align-items: center;
        gap: 4rpx;
    }

    &__go-text {
        font-size: var(--fs-21);
        color: var(--c-ink-3);
    }

    /* 已读降一档：字重降、标题变灰，未读只靠红点 + 深色标题 */
    &.is-read {
        background: var(--c-bg);

        .item__title {
            font-weight: var(--fw-regular);
            color: var(--c-ink-2);
        }

        .item__ic {
            opacity: 0.7;
        }
    }
}
</style>
