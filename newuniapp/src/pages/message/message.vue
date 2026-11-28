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
    <page-shell
        title="通知与提醒"
        bg="page"
        :show-back="true"
        :pull-refresh="true"
        @refresh="onRefresh"
        @reach-bottom="loadMore"
    >
        <!--
            未读汇总条：数字来自接口，不是自己数的。
            「全部已读」放在这一行右侧，而不是导航右区 —— 导航标题是屏幕居中 + 胶囊避让，
            文字动作（约 104rpx）会和标题共用同一段横向空间，窄屏下直接压字。
            批量动作属于内容区操作，跟统计行同一行也更好理解。
        -->
        <view v-if="!loading && !error && list.length" class="bar">
            <text class="bar__text">
                <template v-if="unreadCount > 0">
                    <text class="bar__num t-num">{{ unreadCount }}</text>
                    <text> 条未读 · </text>
                </template>
                <text v-else>已全部读完 · </text>
                <text>共 {{ total }} 条</text>
            </text>

            <view
                class="bar__act"
                :class="{ 'is-off': unreadCount === 0 || marking }"
                hover-class="is-pressed"
                aria-role="button"
                aria-label="全部标为已读"
                @tap="onMarkAll"
            >
                <tn-icon name="check" :size="20" />
                <text class="bar__act-text">{{ marking ? '处理中' : '全部已读' }}</text>
            </view>
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
                        <!-- 真机底色：tn-swipe-action-item 的 content 自带白底（组件主题白），
                             不铺这层会在卡片四周露出整条白带。row 铺页面底色后，白底只留在卡片内。 -->
                        <view class="row">
                            <view
                                class="item"
                                :class="{ 'is-read': item.is_read === 1 }"
                                hover-class="is-pressed"
                                @tap="onOpen(item)"
                            >
                                <view class="item__ic" :class="`item__ic--${toneOf(item)}`">
                                    <tn-icon :name="iconOf(item)" :size="30" />
                                </view>
                                <view class="item__body">
                                    <view class="item__t">
                                        <text class="item__title">{{ item.title }}</text>
                                        <view v-if="item.is_read !== 1" class="item__dot" />
                                    </view>
                                    <text class="item__p">{{ item.content }}</text>
                                    <view class="item__foot">
                                        <text class="item__time t-num">{{ timeOf(item) }}</text>
                                        <view v-if="item.target_type" class="item__go">
                                            <text class="item__go-text">去看看</text>
                                            <tn-icon name="right" :size="18" class="ic-ink-3" />
                                        </view>
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
import { onShow } from '@dcloudio/uni-app'
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
import { useBadgeStore } from '@/stores/badge'
import { report } from '@/platform/report'
import { schemeColor } from '@/utils/scheme-color'

const tabs = [
    { key: 0, label: '全部' },
    { key: NotifyType.ORDER, label: '订单' },
    { key: NotifyType.ACTIVITY, label: '活动' },
    { key: NotifyType.INTERACT, label: '服务' }
]

const list = ref<NotificationItem[]>([])
const total = ref(0)
const unreadCount = ref(0)
const badgeStore = useBadgeStore()
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

/** tn-swipe-action 的 options.style 只认字面色值（行内样式里 wxss 变量不生效），走 schemeColor 镜像 */
const swipeOptions = computed(() => [
    { text: '删除', style: { backgroundColor: schemeColor('danger') } }
])

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
        // 角标与页内未读数同源，就地同步，不等下一次 /v2/me
        badgeStore.setUnread(unreadCount.value)
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
            ...(activeType.value ? { notify_type: activeType.value } : {})
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
        await markAllRead(activeType.value ? { notify_type: activeType.value } : {})
        list.value = list.value.map((i) => ({ ...i, is_read: 1 }))
        unreadCount.value = 0
        badgeStore.setUnread(0)
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
        badgeStore.setUnread(unreadCount.value)
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

function onRefresh(done: () => void) {
    Promise.resolve(reload()).finally(done)
}
</script>

<style lang="scss" scoped>
/*
 * 「全部已读」在统计行右侧，不在导航右区。
 * 曾经放在 nav-right：文字动作约 104rpx，比 88rpx 最小热区宽，导航标题又是屏幕居中 +
 * 胶囊避让，两者共用同一段横向空间 —— 窄屏 / 系统字号放大时会直接压字。
 * 别再把它挪回导航：nav-bar 的 .nav__right 宽度不能为这一处特殊化，会影响所有页面。
 */
.bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24rpx;
    padding: 26rpx 42rpx 0;

    &__text {
        min-width: 0;
        font-size: var(--fs-22);
        color: var(--c-ink-3);
        letter-spacing: 0.04em;
    }

    &__num {
        font-family: var(--font-serif);
        font-size: var(--fs-md);
        color: var(--c-brand-text);
    }

    /*
     * 文字动作：视觉只占一行，热区做到 88rpx。
     * 负外边距把多出来的高度还给统计行（26rpx 正好是 .bar 的上内边距），
     * 统计行高度仍由左侧文字决定，chips 不会被推下去。
     */
    &__act {
        flex: none;
        display: flex;
        align-items: center;
        gap: 8rpx;
        min-height: 88rpx;
        margin: -26rpx -8rpx -26rpx 0;
        padding: 0 8rpx;
        color: var(--c-ink-2);

        &-text {
            font-size: var(--fs-23);
            letter-spacing: 0.04em;
        }

        /* 没有未读 / 正在提交：降为三级灰，点不动（onMarkAll 里也有守卫） */
        &.is-off {
            color: var(--c-ink-3);
        }
    }
}

.chips {
    display: flex;
    gap: 12rpx;
    /* 底部 18rpx 用来承接 chip 的 :before 热区扩展，避免被横向滚动容器裁掉 */
    padding: 22rpx 42rpx 18rpx;
    overflow-x: auto;
}

.chip {
    flex: none;
    position: relative;
    height: 52rpx;
    padding: 0 24rpx;
    border-radius: var(--r-pill);
    /* 可点控件边界（模板 @tap="switchType" + hover-class）：原 --c-line-strong 对白底 1.60:1 不可见，
       按 chip-group 既有注释「控件边界 ≥3:1，禁用 --c-line」改 --c-control-border（3.77:1）。 */
    border: 1rpx solid var(--c-control-border);
    background: var(--c-surface);
    display: flex;
    align-items: center;

    /* 视觉 52rpx，纵向热区扩到 88rpx（上下各 18rpx），横向不动避免与相邻 chip 重叠 */
    &::before {
        content: '';
        position: absolute;
        top: -18rpx;
        bottom: -18rpx;
        left: 0;
        right: 0;
    }

    &__text {
        font-size: var(--fs-22);
        color: var(--c-ink-2);
    }

    &.is-on {
        background: var(--c-active-bg);
        border-color: var(--c-active-border);

        .chip__text {
            color: var(--c-active-fg);
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
        font-size: var(--fs-20);
        letter-spacing: 0.16em;
        color: var(--c-ink-3);
    }

    &__end {
        padding: 28rpx 0 10rpx;
        text-align: center;
    }

    &__end-text {
        font-size: var(--fs-20);
        color: var(--c-ink-3);
        letter-spacing: 0.1em;
    }
}

/* 行容器：铺页面底色，盖住 tn-swipe-action-item content 的白底，同时承担卡片侧边距与行距 */
.row {
    padding: 0 42rpx 16rpx;
    background: var(--c-bg);
}

.item {
    display: flex;
    gap: 20rpx;
    min-height: 96rpx;
    padding: 24rpx;
    box-sizing: border-box;
    border-radius: var(--r-md);
    background: var(--c-surface);
    border: 1rpx solid var(--c-card-border);
    box-shadow: var(--shadow-card);

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
        line-height: var(--lh-body);
        color: var(--c-ink-2);
    }

    &__foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 12rpx;
    }

    &__time {
        font-size: var(--fs-20);
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

/* tn-icon 颜色工具类（本地 scoped 版）：tn-icon 的 color prop 不认 var()，
   颜色必须靠 CSS 落在图标根节点上再由内部 <text> 继承。 */
.ic-ink-3 {
    color: var(--c-ink-3);
}
.item__ic {
    color: var(--c-ink-2);
}
.item__ic--gold {
    color: var(--c-brand-text);
}
.item__ic--sage {
    color: var(--c-success);
}
</style>
