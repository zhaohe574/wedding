<template>
    <!--
        20 我的候补（设计稿 3957–4062）
        ⚠ 口径：
        · 后端**没有排队位次模型**（G-13/G19），列表不显示「第 N 位」，副文案一律用后端 `status_hint`；
        · 倒计时以服务端 `expire_time` 绝对时间为基准，每次 onShow 重算，不靠 interval 累积（R6）；
        · 「立即预约锁定」亮不亮只由 `can_book_now` 决定，置灰原因原样用 `book_block_reason`，前端不自己判定。
    -->
    <page-shell bg="page" title="我的候补">
        <view v-if="loading" class="pad sk">
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
            v-else-if="!hasAny"
            status="empty"
            empty-icon="empty-history"
            empty-text="还没有候补申请"
            desc="遇到满档的主持人，加入候补有释放会第一时间通知你"
            action-text="去查档期"
            :show-action="true"
            fixed
            @action="goQuery"
        />

        <view v-else class="wl">
            <!-- 释放横幅：紧迫感来自数字本身 -->
            <view v-if="banner" class="rel">
                <view class="rel__top">
                    <view class="rel__badge">
                        <view class="rel__badge-dot" />
                        <text class="rel__badge-text">档期已释放</text>
                    </view>
                    <text class="rel__time">{{ banner.remainText }}</text>
                </view>
                <text class="rel__desc">
                    <text class="rel__desc-b">{{ banner.title }}</text>
                    的档期已释放，请在倒计时内完成锁定，超时将顺延给下一位。
                </text>
            </view>

            <!-- 待锁定卡 -->
            <base-card v-for="item in releasedList" :key="`r-${item.id}`" class="blk">
                <view class="row">
                    <image
                        v-if="item.staff_avatar"
                        class="row__av"
                        :src="item.staff_avatar"
                        mode="aspectFill"
                    />
                    <view v-else class="row__av row__av--text">
                        {{ (item.staff_name || '候').slice(0, 1) }}
                    </view>
                    <view class="row__m">
                        <text class="row__name">{{ item.staff_name || '服务人员' }}</text>
                        <text class="row__sub">{{ metaOf(item) }}</text>
                    </view>
                    <status-tag label="待锁定" tone="warning" />
                </view>

                <view class="prog">
                    <text class="prog__s prog__s--done">已排队</text>
                    <view class="prog__line" />
                    <text class="prog__s prog__s--now">已释放</text>
                    <view class="prog__line" />
                    <text class="prog__s">待锁定</text>
                </view>

                <text class="meta"> 提交于 {{ item.create_time }} · 已释放，请在倒计时内锁定 </text>

                <view class="acts">
                    <base-button
                        v-if="canCancel(item)"
                        text="取消候补"
                        variant="ghost"
                        size="md"
                        :block="false"
                        class="acts__ghost"
                        @click="askCancel(item)"
                    />
                    <view class="acts__main">
                        <base-button
                            :text="canBook(item) ? '立即预约锁定' : '暂不可锁定'"
                            variant="ink"
                            size="md"
                            :disabled="!canBook(item)"
                            @click="bookNow(item)"
                        />
                    </view>
                </view>
                <text v-if="!canBook(item) && blockReason(item)" class="reason">
                    {{ blockReason(item) }}
                </text>
            </base-card>

            <!-- 排队中卡 -->
            <base-card v-for="item in queuedList" :key="`q-${item.id}`" class="blk">
                <view class="row">
                    <image
                        v-if="item.staff_avatar"
                        class="row__av"
                        :src="item.staff_avatar"
                        mode="aspectFill"
                    />
                    <view v-else class="row__av row__av--text">
                        {{ (item.staff_name || '候').slice(0, 1) }}
                    </view>
                    <view class="row__m">
                        <text class="row__name">{{ item.staff_name || '服务人员' }}</text>
                        <text class="row__sub">{{ metaOf(item) }}</text>
                    </view>
                    <status-tag label="排队中" tone="neutral" />
                </view>

                <view class="prog">
                    <text class="prog__s prog__s--now prog__s--calm">已排队</text>
                    <view class="prog__line" />
                    <text class="prog__s">等待释放</text>
                    <view class="prog__line" />
                    <text class="prog__s">待锁定</text>
                </view>

                <!-- 副文案取后端 status_hint，没有就只写提交时间；不编位次 -->
                <text class="meta">提交于 {{ item.create_time }}{{ hintOf(item) }}</text>

                <view v-if="canCancel(item)" class="acts">
                    <base-button
                        text="取消候补"
                        variant="ghost"
                        size="md"
                        :block="false"
                        @click="askCancel(item)"
                    />
                </view>
            </base-card>

            <!-- 历史记录：已失效 / 已下单，只留记录不给操作 -->
            <template v-if="historyList.length">
                <text class="hist">历史记录</text>
                <base-card v-for="item in historyList" :key="`h-${item.id}`" class="blk blk--hist">
                    <view class="row">
                        <image
                            v-if="item.staff_avatar"
                            class="row__av"
                            :src="item.staff_avatar"
                            mode="aspectFill"
                        />
                        <view v-else class="row__av row__av--text">
                            {{ (item.staff_name || '候').slice(0, 1) }}
                        </view>
                        <view class="row__m">
                            <text class="row__name">{{ item.staff_name || '服务人员' }}</text>
                            <text class="row__sub">{{ metaOf(item) }}</text>
                        </view>
                        <status-tag
                            :label="stageOf(item) === 'ordered' ? '已下单' : '已失效'"
                            tone="neutral"
                        />
                    </view>
                    <text class="meta meta--flush">{{ historyLine(item) }}</text>
                </base-card>
            </template>

            <!-- 规则重申 + 兜底出口 -->
            <text class="rule"> 候补不锁定档期、不收费。释放后 30 分钟内未支付将自动顺延。 </text>
            <view
                hover-class="is-pressed"
                class="end"
                aria-role="button"
                aria-label="去看其他可约的主持人"
                @tap="goQuery"
            >
                <text class="end__text">没等到？去看看其他可约的主持人</text>
                <tn-icon name="right" :size="18" class="ic-ink-3" />
            </view>
        </view>
    </page-shell>

    <tn-modal ref="modalRef" />
    <app-toast />
</template>

<script setup lang="ts">
import { computed, onUnmounted, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { getMyWaitlist, cancelWaitlist } from '@/api/catalog'
import { navigate } from '@/utils/link'
import { toast } from '@/composables/useToast'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { WaitlistNotifyStatus } from '@shared/contracts'

type Stage = 'queued' | 'released' | 'ordered' | 'expired'

interface WlItem {
    id: number
    staff_id?: number
    staff_name?: string
    staff_avatar?: string
    schedule_date?: string
    date?: string
    create_time?: string
    notify_status: number
    notify_status_desc?: string
    status_hint?: string
    expire_time?: number | string
    expire_time_text?: string
    can_cancel?: number | boolean
    can_book_now?: number | boolean
    book_block_reason?: string
    package_id?: number
    package_name?: string
    released_at?: string
    [k: string]: any
}

const list = ref<WlItem[]>([])
const loading = ref(true)
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))
const error = computed(() => !!rawError.value)
const modalRef = ref<any>(null)

/** 1s 心跳：只用于驱动倒计时文本与「归零就地失效」，真实截止时间永远来自 expire_time */
const now = ref(Date.now())
let timer: ReturnType<typeof setInterval> | null = null

/** 已经提示过「锁定超时」的 id，避免每秒重复 toast */
const expiredNotified = new Set<number>()

/** 兼容秒/毫秒时间戳与日期字符串；拿不到返回 0（表示不可倒计时） */
function deadlineOf(item: WlItem): number {
    const raw = item.expire_time
    if (raw === undefined || raw === null || raw === '') return 0
    if (typeof raw === 'number') return raw > 1e12 ? raw : raw * 1000
    const s = String(raw)
    if (/^\d+$/.test(s)) {
        const n = Number(s)
        return n > 1e12 ? n : n * 1000
    }
    const t = new Date(s.replace(/-/g, '/')).getTime()
    return Number.isFinite(t) ? t : 0
}

function remainMs(item: WlItem): number {
    const d = deadlineOf(item)
    return d ? Math.max(0, d - now.value) : 0
}

/** 展示阶段：notify_status 为主；已通知但倒计时归零 → 当场降级为 expired */
function stageOf(item: WlItem): Stage {
    const s = Number(item.notify_status)
    if (s === WaitlistNotifyStatus.ORDERED) return 'ordered'
    if (s === WaitlistNotifyStatus.EXPIRED) return 'expired'
    if (s === WaitlistNotifyStatus.NOTIFIED) {
        const d = deadlineOf(item)
        if (d && remainMs(item) <= 0) return 'expired'
        return 'released'
    }
    return 'queued'
}

const hasAny = computed(() => list.value.length > 0)

const releasedList = computed(() =>
    list.value
        .filter((i) => stageOf(i) === 'released')
        .sort((a, b) => deadlineOf(a) - deadlineOf(b))
)

const queuedList = computed(() => list.value.filter((i) => stageOf(i) === 'queued'))

const historyList = computed(() =>
    list.value.filter((i) => {
        const st = stageOf(i)
        return st === 'expired' || st === 'ordered'
    })
)

function pad2(n: number) {
    return n < 10 ? `0${n}` : String(n)
}

/** 02:14:36 */
function fmtRemain(ms: number) {
    const total = Math.floor(ms / 1000)
    const h = Math.floor(total / 3600)
    const m = Math.floor((total % 3600) / 60)
    const s = total % 60
    return `${pad2(h)}:${pad2(m)}:${pad2(s)}`
}

const banner = computed(() => {
    const first = releasedList.value[0]
    if (!first) return null
    const d = deadlineOf(first)
    return {
        title: `${first.staff_name || '服务人员'}${
            first.schedule_date || first.date ? ` · ${shortDate(first)}` : ''
        }`,
        remainText: d ? fmtRemain(remainMs(first)) : first.expire_time_text || '请尽快锁定'
    }
})

function shortDate(item: WlItem) {
    const d = String(item.schedule_date || item.date || '')
    return d.length >= 10 ? `${d.slice(5, 7)}.${d.slice(8, 10)}` : d
}

/** 卡片副标题：日期 + 套餐名，全部来自服务端，缺了就不写 */
function metaOf(item: WlItem) {
    const parts: string[] = []
    const d = String(item.schedule_date || item.date || '')
    if (d.length >= 10) {
        const week = '日一二三四五六'[new Date(`${d}T00:00:00`).getDay()]
        parts.push(`${d.slice(5).replace('-', '.')} 周${week}`)
    }
    if (item.package_name) parts.push(item.package_name)
    return parts.join(' · ') || '档期信息以服务端为准'
}

function hintOf(item: WlItem) {
    return item.status_hint ? ` · ${item.status_hint}` : ''
}

function canCancel(item: WlItem) {
    // 后端给 can_cancel 才显示取消；缺字段时按 notify_status 兜底（未通知/已通知可取消）
    if (typeof item.can_cancel !== 'undefined') return Number(item.can_cancel) === 1
    const s = Number(item.notify_status)
    return s === WaitlistNotifyStatus.PENDING || s === WaitlistNotifyStatus.NOTIFIED
}

function canBook(item: WlItem) {
    return Number(item.can_book_now) === 1
}

function blockReason(item: WlItem) {
    return item.book_block_reason || '当前不可锁定，请以客服告知为准'
}

function historyLine(item: WlItem) {
    if (stageOf(item) === 'ordered') {
        return `${item.released_at || item.create_time || ''} 释放 · 已下单`.trim()
    }
    if (item.status_hint) return item.status_hint
    const d = item.released_at || item.expire_time_text
    return d ? `${d} 释放 · 超时未锁定，名额已顺延给下一位` : '超时未锁定，名额已顺延给下一位'
}

async function load() {
    loading.value = true
    rawError.value = null
    try {
        const res: any = await getMyWaitlist()
        const arr = Array.isArray(res) ? res : res?.lists || res?.data || []
        list.value = (arr || []).map((i: any) => ({
            ...i,
            staff_name: i.staff_name || i.staff?.name || '',
            staff_avatar: i.staff_avatar || i.staff?.avatar || '',
            staff_id: i.staff_id ?? i.staff?.id,
            package_name: i.package_name || i.package?.name || '',
            package_id: i.package_id ?? i.package?.id,
            schedule_date: i.schedule_date || i.date || ''
        }))
        // 新加载后允许重新提示一次
        expiredNotified.clear()
        syncTimer()
        checkExpired()
    } catch (e) {
        rawError.value = e
    } finally {
        loading.value = false
    }
}

/** 只在有「待锁定」时才跑心跳，其余情况不占 CPU */
function syncTimer() {
    const need = releasedList.value.some((i) => deadlineOf(i) > 0)
    if (need && !timer) {
        timer = setInterval(() => {
            now.value = Date.now()
            checkExpired()
            if (!releasedList.value.some((i) => deadlineOf(i) > 0)) stopTimer()
        }, 1000)
    }
    if (!need) stopTimer()
}

function stopTimer() {
    if (timer) {
        clearInterval(timer)
        timer = null
    }
}

/** 归零就地失效：卡自动进历史分组（stageOf 依赖 now），只轻提示一次 */
function checkExpired() {
    for (const item of list.value) {
        if (Number(item.notify_status) !== WaitlistNotifyStatus.NOTIFIED) continue
        if (stageOf(item) !== 'expired') continue
        if (expiredNotified.has(item.id)) continue
        expiredNotified.add(item.id)
        toast.info('锁定时间到了，名额已顺延给下一位')
    }
}

function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'login') {
        navigate({ path: '/pages/login/login' })
        return
    }
    load()
}

function askCancel(item: WlItem) {
    modalRef.value?.showModal({
        title: '取消候补',
        content: '取消后你在这个档期的排队位次不再保留。',
        showCancel: true,
        cancelText: '再想想',
        confirmText: '取消候补',
        confirm: async () => {
            await doCancel(item)
            return true
        }
    })
}

async function doCancel(item: WlItem) {
    try {
        await cancelWaitlist({ id: item.id })
        list.value = list.value.filter((i) => i.id !== item.id)
        toast.info('已取消候补')
        syncTimer()
    } catch {
        // 请求层已提示
    }
}

function bookNow(item: WlItem) {
    if (!canBook(item)) return
    navigate({
        path: '/packages/pkg-catalog/pages/booking/booking',
        query: {
            staffId: item.staff_id || item.staff?.id || '',
            packageId: item.package_id || '',
            date: item.schedule_date || item.date || '',
            waitlistId: item.id
        }
    })
}

function goQuery() {
    navigate({ path: '/pages/query/query' })
}

onShow(() => {
    now.value = Date.now()
    load()
})

onUnmounted(() => stopTimer())
</script>

<style lang="scss" scoped>
.pad {
    padding: 0 42rpx;
}
.sk {
    padding-top: var(--sp-3);
}
.wl {
    padding: 0 42rpx calc(var(--sp-5) + var(--safe-bottom));
}

/* ---------- 释放横幅 ---------- */
.rel {
    margin-top: var(--sp-2);
    border-radius: var(--r-md);
    padding: var(--sp-3) var(--sp-4);
    background: var(--c-warning-bg);
    border: 1rpx solid var(--c-warning-border);

    &__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__badge {
        display: inline-flex;
        align-items: center;
        gap: var(--sp-1);
        height: 36rpx;
        padding: 0 var(--sp-2);
        border-radius: var(--r-xs);
        background: var(--c-ink);
    }

    &__badge-dot {
        width: 8rpx;
        height: 8rpx;
        border-radius: var(--r-pill);
        background: var(--c-brand-soft);
        flex: none;
    }

    &__badge-text {
        font-size: var(--fs-xs);
        letter-spacing: 0.06em;
        color: var(--c-brand-soft);
    }

    &__time {
        font-family: var(--font-serif);
        font-size: var(--fs-lg);
        letter-spacing: 0.02em;
        color: var(--c-warning);
    }

    &__desc {
        display: block;
        margin-top: var(--sp-2);
        font-size: var(--fs-xs);
        line-height: var(--lh-loose);
        color: var(--c-warning);
    }

    &__desc-b {
        font-weight: var(--fw-semibold);
    }
}

/* ---------- 卡片 ---------- */
.blk {
    margin-top: var(--sp-3);

    &--hist {
        background: var(--c-surface-2);
    }
}

.row {
    display: flex;
    align-items: flex-start;
    gap: var(--sp-2);

    &__av {
        width: 76rpx;
        height: 76rpx;
        border-radius: var(--r-sm);
        flex: none;
        background: var(--c-neutral-bg);

        &--text {
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--font-serif);
            font-size: var(--fs-lg);
            color: var(--c-ink-3);
        }
    }

    &__m {
        flex: 1;
        min-width: 0;
    }

    &__name {
        display: block;
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        letter-spacing: 0.02em;
        color: var(--c-ink);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    &__sub {
        display: block;
        margin-top: var(--sp-1);
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
        overflow-wrap: anywhere;
    }
}

/* ---------- 三段进度 ---------- */
.prog {
    display: flex;
    align-items: center;
    margin-top: var(--sp-3);

    &__line {
        flex: 1;
        height: 1rpx;
        background: var(--c-line-strong);
        margin: 0 var(--sp-2);
    }

    &__s {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        display: flex;
        align-items: center;
        gap: var(--sp-1);
        flex: none;

        &::before {
            content: '';
            width: 12rpx;
            height: 12rpx;
            border-radius: var(--r-pill);
            border: 1rpx solid var(--c-line-strong);
            background: var(--c-surface);
            flex: none;
        }

        &--done::before {
            /* 批 1.5 ⑤：--c-brand(#c6a15b) 对白底仅 2.39:1（<3:1），改深金 5.26:1。
               同 order/detail：圆点仅 12rpx 且无勾形，不套 01 §7.15 的 --c-ink 实心。 */
            background: var(--c-brand-text);
            border-color: var(--c-brand-text);
        }

        &--now {
            /* G1 ⑦：当前步文字也改 --c-ink（01 §7.15：当前步 = --c-ink 实心 + 文字 --c-ink）。
               原来圆点墨黑、文字琥珀，同一状态两个色，比全琥珀更怪。 */
            color: var(--c-ink);
            font-weight: var(--fw-semibold);

            &::before {
                /* G1 ⑦（定稿）：当前步 = 墨黑实心 + 6rpx --c-ink-3 外环。
                   填充必须同步改 --c-ink：--c-ink-3 压在 --c-warning 上只有 1.02:1，环等于不可见。
                   净空 = gap(--sp-1 = 8rpx) − 环单边 6rpx = 2rpx，不贴文字；
                   gap 保持 --sp-1 不动，否则三个进度项间距不等。 */
                background: var(--c-ink);
                border-color: var(--c-ink);
                box-shadow: 0 0 0 6rpx var(--c-ink-3);
            }
        }

        &--calm {
            /* 只压语气，不动圆点与环。
               --calm 全仓仅 waitlist.vue:128 一处、且永远与 --now 同时出现，没有单独用例；
               原来这里的 &::before 会把圆点改成 --c-ink-2，与 --c-ink-3 环只差 1.28:1 —— 环等于白画。 */
            color: var(--c-ink-2);
        }
    }
}

.meta {
    display: block;
    margin-top: var(--sp-3);
    padding-bottom: var(--sp-3);
    border-bottom: 1rpx solid var(--c-line);
    font-size: var(--fs-xs);
    line-height: var(--lh-body);
    color: var(--c-ink-3);

    &--flush {
        border-bottom: 0;
        padding-bottom: 0;
    }
}

/* ---------- 操作区 ---------- */
.acts {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    margin-top: var(--sp-3);

    &__ghost {
        flex: none;
    }

    &__main {
        flex: 2;
        min-width: 0;
    }
}

.reason {
    display: block;
    margin-top: var(--sp-2);
    font-size: var(--fs-xs);
    line-height: var(--lh-body);
    color: var(--c-ink-3);
}

/* ---------- 历史 ---------- */
.hist {
    display: block;
    margin: var(--sp-4) 0 var(--sp-1);
    font-size: var(--fs-xs);
    letter-spacing: 0.14em;
    color: var(--c-ink-3);
}

/* ---------- 页尾 ---------- */
.rule {
    display: block;
    margin-top: var(--sp-3);
    font-size: var(--fs-xs);
    line-height: var(--lh-loose);
    color: var(--c-ink-3);
}

.end {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--sp-1);
    margin-top: var(--sp-4);
    padding-top: var(--sp-4);
    border-top: 1rpx solid var(--c-line);

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }
}

/* tn-icon 颜色工具类（本地 scoped 版）：tn-icon 的 color prop 不认 var()，
   颜色必须靠 CSS 落在图标根节点上再由内部 <text> 继承。 */
.ic-ink-3 {
    color: var(--c-ink-3);
}
</style>
