<template>
    <!--
        15 我的活动（v7 重设计）
        - 4 分段带后端真实计数：全部 / 待参加 / 待支付 / 历史记录
        - 置顶深墨黑金电子票根 + 轻票根列表卡（真实 Unix 秒时间戳、地点、封面、实时倒计时）
        - 页内半屏凭证与详情抽屉（8 位核销码 + 离线 PNG 二维码）+ 取消申请抽屉
    -->
    <page-shell
        title="我的活动"
        bg="page"
        :show-back="true"
        :pull-refresh="true"
        @refresh="onRefresh"
        @reach-bottom="onReachBottomLoad"
    >
        <view class="seg">
            <view
                v-for="s in SEGS"
                :key="s.key"
                class="seg__item"
                :class="{ 'is-on': activeSeg === s.key }"
                hover-class="is-pressed"
                aria-role="button"
                :aria-label="`${s.label} ${counts[s.key] ?? 0}`"
                @tap="switchSeg(s.key)"
            >
                <view
                    v-if="s.key === 'pending_pay' && counts.pending_pay > 0 && activeSeg !== s.key"
                    class="seg__dot"
                />
                <text class="seg__text">{{ s.label }}</text>
                <text class="seg__count">{{ counts[s.key] ?? 0 }}</text>
            </view>
        </view>

        <view v-if="loading" class="pad">
            <base-skeleton :rows="4" />
        </view>

        <view v-else-if="error" class="err-wrap">
            <base-empty
                status="error"
                :error-kind="view?.errorKind || 'unknown'"
                :error-text="view?.title"
                :desc="view?.desc"
                :primary-action="view?.primaryAction"
                :fixed="!offlinePasses.length"
                @primary="onPrimary"
                @retry="reload"
            />
            <view v-if="offlinePasses.length" class="offline">
                <text class="offline__head">离线可用 · 已缓存入场凭证</text>
                <view
                    v-for="pass in offlinePasses"
                    :key="pass.id"
                    class="offline__card"
                    hover-class="is-pressed"
                    @tap="openDetail(pass)"
                >
                    <view class="offline__main">
                        <text class="offline__title ellipsis">{{ activityTitle(pass) }}</text>
                        <text class="offline__meta ellipsis">
                            {{ formatShortTime(activityStartTs(pass)) }} · {{ pass.checkin_code }}
                        </text>
                    </view>
                    <view class="btn btn--ink">
                        <text class="btn__text btn__text--on">出示凭证</text>
                    </view>
                </view>
            </view>
        </view>

        <base-empty
            v-else-if="!pinnedItem && !displayList.length"
            status="empty"
            :empty-text="emptyCopy.title"
            :desc="emptyCopy.desc"
            :action-text="activeSeg === 'all' ? '看看近期活动' : '查看全部报名'"
            :show-action="true"
            fixed
            @action="onEmptyAction"
        />

        <view v-else class="mv">
            <!-- 置顶深墨黑金票根卡（仅全部 / 待参加且存在最近一场待参加记录时展示） -->
            <view
                v-if="pinnedItem"
                class="next"
                hover-class="is-pressed"
                @tap="openDetail(pinnedItem)"
            >
                <view class="next__top">
                    <view class="next__kicker-row">
                        <text class="next__flag">NEXT INVITATION · 即将参加</text>
                        <view class="next__cd-pill">
                            <text class="next__cd">{{ countdownBadgeText(pinnedItem, nowMs) }}</text>
                        </view>
                    </view>

                    <view class="next__body">
                        <image
                            v-if="activityCover(pinnedItem)"
                            class="next__cover"
                            :src="activityCover(pinnedItem)"
                            mode="aspectFill"
                            aria-label="活动封面"
                        />
                        <view v-else class="next__cover next__cover--monogram">
                            <text class="next__mono">{{ brandInitial }}</text>
                        </view>

                        <view class="next__info">
                            <text class="next__title ellipsis-2">{{ activityTitle(pinnedItem) }}</text>
                            <text class="next__meta">{{ formatShortTime(activityStartTs(pinnedItem)) }}</text>
                            <text class="next__meta ellipsis">{{ buildMetaLine(pinnedItem) }}</text>
                        </view>
                    </view>
                </view>

                <view class="next__perf">
                    <view class="next__notch next__notch--l" />
                    <view class="next__dash" />
                    <view class="next__notch next__notch--r" />
                </view>

                <view class="next__foot">
                    <view class="next__code-wrap">
                        <text class="next__code-label">
                            {{
                                Number(pinnedItem.checkin_status) === 1
                                    ? '已完成现场核销'
                                    : '现场核销码（离线可用）'
                            }}
                        </text>
                        <text class="next__code-val">{{ pinnedItem.checkin_code || '—' }}</text>
                    </view>
                    <view
                        class="next__cta"
                        hover-class="is-pressed"
                        @tap.stop="openDetail(pinnedItem)"
                    >
                        <text class="next__cta-text">出示凭证</text>
                        <tn-icon name="right" :size="20" class="next__cta-ic" />
                    </view>
                </view>
            </view>

            <!-- 轻票根列表卡 -->
            <view
                v-for="r in displayList"
                :key="r.id"
                class="card"
                :class="{ 'is-done': isMutedCard(r) }"
                hover-class="is-pressed"
                @tap="openDetail(r)"
            >
                <view class="card__h">
                    <view class="card__dt">
                        <text class="card__dt-d">{{ formatDayOf(activityStartTs(r)) }}</text>
                        <text class="card__dt-w">{{ formatWeekOf(activityStartTs(r)) }}</text>
                    </view>

                    <view class="card__tt">
                        <text class="card__title ellipsis-2">{{ activityTitle(r) }}</text>
                        <text class="card__sub ellipsis">{{ buildMetaLine(r) || '详情点击查看' }}</text>
                    </view>

                    <status-tag
                        :label="resolveStatusBadge(r, nowMs).label"
                        :tone="resolveStatusBadge(r, nowMs).tone"
                        size="sm"
                    />
                </view>

                <view
                    v-if="bannerOf(r)"
                    class="card__banner"
                    :class="`card__banner--${bannerOf(r)?.tone}`"
                >
                    <text class="card__banner-text">{{ bannerOf(r)?.text }}</text>
                    <text v-if="bannerOf(r)?.right" class="card__banner-right">{{
                        bannerOf(r)?.right
                    }}</text>
                </view>

                <view class="card__foot">
                    <text class="card__info ellipsis">{{ formatShortTime(activityStartTs(r)) }}</text>
                    <view class="card__acts">
                        <template v-if="Number(r.registration_status) === 0">
                            <view class="btn" hover-class="is-pressed" @tap.stop="openCancel(r)">
                                <text class="btn__text">取消</text>
                            </view>
                            <view
                                class="btn btn--ink"
                                hover-class="is-pressed"
                                @tap.stop="onPay(r)"
                            >
                                <text class="btn__text btn__text--on">
                                    {{ payingId === r.id ? '支付中…' : '继续支付' }}
                                </text>
                            </view>
                        </template>

                        <template
                            v-else-if="
                                Number(r.registration_status) === 1 && !isActivityEnded(r, nowMs)
                            "
                        >
                            <view
                                v-if="Number(r.can_cancel) === 1"
                                class="btn"
                                hover-class="is-pressed"
                                @tap.stop="openCancel(r)"
                            >
                                <text class="btn__text">取消报名</text>
                            </view>
                            <view
                                class="btn btn--ink"
                                hover-class="is-pressed"
                                @tap.stop="openDetail(r)"
                            >
                                <text class="btn__text btn__text--on">
                                    {{ Number(r.checkin_status) === 1 ? '查看凭证' : '入场凭证' }}
                                </text>
                            </view>
                        </template>

                        <template v-else-if="Number(r.registration_status) === 5">
                            <view
                                class="btn btn--ink"
                                hover-class="is-pressed"
                                @tap.stop="openCancel(r)"
                            >
                                <text class="btn__text btn__text--on">重新申请</text>
                            </view>
                        </template>

                        <template v-else>
                            <view class="btn" hover-class="is-pressed" @tap.stop="openDetail(r)">
                                <text class="btn__text">查看详情</text>
                                <tn-icon name="right" :size="18" class="btn__ic" />
                            </view>
                        </template>
                    </view>
                </view>
            </view>

            <load-more v-if="loadingMore" status="loading" />
            <view v-else-if="!hasMore" class="mv__end">
                <text class="mv__end-text">— 没有更多报名记录 —</text>
            </view>

            <!-- 底部轻量引导条 -->
            <view class="rec" hover-class="is-pressed" @tap="goFeed">
                <view class="rec__h">
                    <text class="rec__title">近期可报名</text>
                    <view class="rec__more">
                        <text class="rec__more-text">去发现</text>
                        <tn-icon name="right" :size="18" class="rec__more-ic" />
                    </view>
                </view>
                <text class="rec__p">
                    沙龙与工作坊都在「动态」里发布，报名后回到本页查看入场凭证与地址。
                </text>
            </view>

            <text class="note">活动均由{{ brand.name }}主办 · 入场凭证已自动离线缓存</text>
        </view>
    </page-shell>

    <!-- 页内半屏凭证与详情抽屉 -->
    <activity-detail-sheet
        v-model:show="detailShow"
        :item="detailItem"
        :now-sec="nowSec"
        :paying-id="payingId"
        @pay="onPay"
        @cancel="openCancelFromDetail"
        @open-activity="goActivityDetail"
    />

    <!-- 取消报名半屏抽屉 -->
    <activity-cancel-sheet
        v-model:show="cancelShow"
        :item="cancelItem"
        @cancelled="onCancelled"
    />

    <app-toast />
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onLoad, onShow, onUnload } from '@dcloudio/uni-app'
import {
    getActivityPayStatus,
    getActivityPayWay,
    getActivityRegistrationDetail,
    getMyActivityRegistrations,
    activityPrepay,
    type ActivityStatusGroup
} from '@/api/activity'
import type { ActivityGroupCountsVO, ActivityRegistrationVO } from '@/api/types/activity'
import { normalizePage } from '@/request'
import { requestPayment, sleep } from '@/platform/pay'
import { navigate } from '@/utils/link'
import { toast } from '@/composables/useToast'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { remindBeforeOaAction } from '@/utils/oa-reminder'
import { report } from '@/platform/report'
import { useBrand } from '@/composables/useBrand'
import ActivityDetailSheet from './components/activity-detail-sheet.vue'
import ActivityCancelSheet from './components/activity-cancel-sheet.vue'
import {
    activityCover,
    activityStartTs,
    activityTitle,
    buildMetaLine,
    countdownBadgeText,
    formatDayOf,
    formatShortTime,
    formatWeekOf,
    getPayRemainSeconds,
    isActivityEnded,
    resolveBannerInfo,
    resolveStatusBadge
} from '@/utils/activityFormat'

type SegKey = 'all' | 'upcoming' | 'pending_pay' | 'history'

const SEGS: Array<{ key: SegKey; label: string }> = [
    { key: 'all', label: '全部' },
    { key: 'upcoming', label: '待参加' },
    { key: 'pending_pay', label: '待支付' },
    { key: 'history', label: '历史记录' }
]

const OFFLINE_CACHE_KEY = 'activity_offline_passes'
const PAGE_SIZE = 15

const { brand } = useBrand()
const brandInitial = computed(() => (brand.value.name ? brand.value.name.slice(0, 1) : '礼'))

const list = ref<ActivityRegistrationVO[]>([])
const nextUp = ref<ActivityRegistrationVO | null>(null)
const counts = ref<ActivityGroupCountsVO>({ all: 0, upcoming: 0, pending_pay: 0, history: 0 })
const activeSeg = ref<SegKey>('all')

const page = ref(1)
const hasMore = ref(false)
const loading = ref(true)
const loadingMore = ref(false)
const rawError = ref<unknown>(null)
const error = computed(() => !!rawError.value)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))

const payingId = ref(0)
const detailShow = ref(false)
const detailItem = ref<ActivityRegistrationVO | null>(null)
const cancelShow = ref(false)
const cancelItem = ref<ActivityRegistrationVO | null>(null)

const offlinePasses = ref<ActivityRegistrationVO[]>(loadOfflineCache())

/** 统一秒级时钟，驱动所有待支付实时倒计时 */
const nowSec = ref(Math.floor(Date.now() / 1000))
const nowMs = computed(() => nowSec.value * 1000)
let timerHandle: ReturnType<typeof setInterval> | null = null
const expiredTriggeredIds = new Set<number>()
let firstShown = false
let pendingTargetDetailId = 0

function startClock() {
    if (timerHandle) return
    timerHandle = setInterval(() => {
        nowSec.value = Math.floor(Date.now() / 1000)
        const hasJustExpired = list.value.some((item) => {
            if (Number(item.registration_status) !== 0) return false
            if (expiredTriggeredIds.has(item.id)) return false
            const remain = getPayRemainSeconds(item, nowSec.value)
            if (remain <= 0) {
                expiredTriggeredIds.add(item.id)
                return true
            }
            return false
        })
        if (hasJustExpired) {
            load(true, true)
        }
    }, 1000)
}

function stopClock() {
    if (timerHandle) {
        clearInterval(timerHandle)
        timerHandle = null
    }
}

const pinnedItem = computed(() => {
    if (activeSeg.value !== 'all' && activeSeg.value !== 'upcoming') return null
    return nextUp.value
})

const displayList = computed(() => {
    const pinId = pinnedItem.value?.id
    return pinId ? list.value.filter((item) => item.id !== pinId) : list.value
})

const emptyCopy = computed(() => {
    if (activeSeg.value === 'upcoming') {
        return { title: '暂无待参加的活动', desc: '近期如有心仪的沙龙或工作坊，报名后会展示在这里。' }
    }
    if (activeSeg.value === 'pending_pay') {
        return { title: '当前没有待支付活动', desc: '待支付报名记录会保留至支付截止时间。' }
    }
    if (activeSeg.value === 'history') {
        return { title: '暂无历史活动记录', desc: '已结束或已取消的活动记录会归集在这里。' }
    }
    return { title: '还没有报名记录', desc: '近期有沙龙与工作坊，报名后会出现在这里。' }
})

function bannerOf(r: ActivityRegistrationVO) {
    return resolveBannerInfo(r, nowSec.value)
}

function isMutedCard(r: ActivityRegistrationVO): boolean {
    const st = Number(r.registration_status)
    return st === 3 || (st === 1 && isActivityEnded(r, nowMs.value))
}

function loadOfflineCache(): ActivityRegistrationVO[] {
    try {
        const cached = uni.getStorageSync(OFFLINE_CACHE_KEY)
        return Array.isArray(cached) ? cached : []
    } catch {
        return []
    }
}

function saveOfflineCache(rows: ActivityRegistrationVO[], pinned: ActivityRegistrationVO | null) {
    try {
        const map = new Map<number, ActivityRegistrationVO>()
        if (pinned && Number(pinned.registration_status) === 1 && pinned.checkin_code) {
            map.set(pinned.id, pinned)
        }
        for (const r of rows) {
            if (Number(r.registration_status) === 1 && r.checkin_code && !isActivityEnded(r)) {
                map.set(r.id, r)
            }
        }
        const merged = Array.from(map.values()).slice(0, 10)
        uni.setStorageSync(OFFLINE_CACHE_KEY, merged)
        offlinePasses.value = merged
    } catch {
        // 缓存异常不影响主流程
    }
}

async function load(reset = true, silent = false) {
    if (reset) {
        page.value = 1
        if (!silent) loading.value = true
        rawError.value = null
    } else {
        loadingMore.value = true
    }

    try {
        const res: any = await getMyActivityRegistrations({
            status_group: activeSeg.value as ActivityStatusGroup,
            page: page.value,
            page_size: PAGE_SIZE
        })
        const paged = normalizePage<ActivityRegistrationVO>(res)
        const rows = paged.list || []

        if (reset) {
            list.value = rows
        } else {
            const seen = new Set(list.value.map((x) => x.id))
            list.value = list.value.concat(rows.filter((x) => !seen.has(x.id)))
        }

        if (res?.counts && typeof res.counts === 'object') {
            counts.value = {
                all: Number(res.counts.all ?? 0),
                upcoming: Number(res.counts.upcoming ?? 0),
                pending_pay: Number(res.counts.pending_pay ?? 0),
                history: Number(res.counts.history ?? 0)
            }
        }
        if (reset) {
            nextUp.value = res?.next_up || null
        }

        hasMore.value = paged.hasMore
        nowSec.value = Math.floor(Date.now() / 1000)

        if (activeSeg.value === 'all' || activeSeg.value === 'upcoming') {
            saveOfflineCache(list.value, nextUp.value)
        }

        // 若详情抽屉正打开，顺手同步当前记录最新状态
        if (detailShow.value && detailItem.value) {
            const updated =
                list.value.find((x) => x.id === detailItem.value?.id) ||
                (nextUp.value?.id === detailItem.value.id ? nextUp.value : null)
            if (updated) detailItem.value = updated
        }

        // 首次从旧链接 ?id=xxx 进入时自动弹出对应凭证抽屉
        if (pendingTargetDetailId > 0) {
            const targetId = pendingTargetDetailId
            pendingTargetDetailId = 0
            await openDetailById(targetId)
        }
    } catch (e) {
        if (reset && !silent) {
            rawError.value = e
        } else if (!silent) {
            toast.error('加载失败，请稍后重试')
        }
        report('api_error', {
            endpoint: 'dynamic.activityRegistrations',
            message: '我的活动加载失败'
        })
    } finally {
        loading.value = false
        loadingMore.value = false
    }
}

async function openDetailById(id: number) {
    const local =
        list.value.find((x) => x.id === id) || (nextUp.value?.id === id ? nextUp.value : null)
    if (local) {
        openDetail(local)
        return
    }
    try {
        const res = await getActivityRegistrationDetail({ id })
        if (res && res.id) {
            openDetail(res as ActivityRegistrationVO)
        } else {
            toast.info('未找到对应报名记录')
        }
    } catch {
        toast.info('报名详情加载失败')
    }
}

function reload() {
    load(true)
}

function switchSeg(key: SegKey) {
    if (activeSeg.value === key) return
    activeSeg.value = key
    load(true)
}

function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        reload()
        return
    }
    if (kind === 'login') {
        navigate({ path: '/pages/login/login' })
        return
    }
    if (kind === 'reselect' || kind === 'back') uni.navigateBack()
}

function onEmptyAction() {
    if (activeSeg.value === 'all') {
        goFeed()
    } else {
        switchSeg('all')
    }
}

function openDetail(item: ActivityRegistrationVO) {
    detailItem.value = item
    detailShow.value = true
}

function openCancel(item: ActivityRegistrationVO) {
    if (Number(item.can_cancel) !== 1) {
        toast.info(item.cancel_disabled_reason || '当前状态不可取消')
        return
    }
    cancelItem.value = item
    cancelShow.value = true
}

function openCancelFromDetail(item: ActivityRegistrationVO) {
    detailShow.value = false
    openCancel(item)
}

function onCancelled() {
    load(true, true)
}

/** 继续支付：activityPayWay → activityPrepay → requestPayment → 轮询 payStatus */
async function onPay(item: ActivityRegistrationVO) {
    if (payingId.value > 0) return
    payingId.value = item.id
    try {
        await getActivityPayWay({ registration_id: item.id })
        // 服务号提醒：支付方式校验已通过，只在创建预支付单前弹一次；
        // 暂停时直接退出，payingId 由 finally 释放
        if (!(await remindBeforeOaAction())) return
        const res: any = await activityPrepay({ registration_id: item.id, pay_way: 2 })
        const config = res?.config || res
        if (!config) {
            toast.error('支付参数获取失败，请稍后重试')
            return
        }
        const result = await requestPayment(config)
        if (result === 'cancel') {
            toast.info('已取消支付')
            return
        }
        if (result === 'fail') {
            toast.error('支付未完成，请重试')
            return
        }
        for (let i = 0; i < 5; i++) {
            await sleep(1200)
            const st: any = await getActivityPayStatus({ registration_id: item.id })
            if (Number(st?.pay_status) === 1) {
                toast.success('支付成功，入场凭证已生成')
                await load(true, true)
                return
            }
        }
        toast.info('支付结果确认中，稍后可在本页查看')
        await load(true, true)
    } catch (e: any) {
        toast.error(e?.message || '支付失败，请重试')
        report('api_error', { endpoint: 'dynamic.activityPrepay', message: '活动支付失败' })
    } finally {
        payingId.value = 0
    }
}

function goActivityDetail(item: ActivityRegistrationVO) {
    const id = Number(item.dynamic_id || item.dynamic?.id || 0)
    if (id > 0) {
        detailShow.value = false
        navigate({
            path: '/packages/pkg-catalog/pages/feed-detail/feed-detail',
            query: { id }
        })
        return
    }
    toast.info('原活动详情暂不可用')
}

function goFeed() {
    uni.switchTab({ url: '/pages/feed/feed' })
}

onLoad((options?: Record<string, any>) => {
    const targetId = Number(options?.id || 0)
    if (targetId > 0) {
        pendingTargetDetailId = targetId
    }
    startClock()
    load(true)
})

onShow(() => {
    startClock()
    if (!firstShown) {
        firstShown = true
        return
    }
    load(true, true)
})

/** page-shell 内置下拉刷新：无论同步/异步成功或异常，都必定调 done 收起刷新态 */
function onRefresh(done: () => void) {
    Promise.resolve(load(true, true)).finally(done)
}

/** page-shell 内置触底加载：保留原 onReachBottom 的防重入判断 */
function onReachBottomLoad() {
    if (!hasMore.value || loading.value || loadingMore.value) return
    page.value += 1
    load(false)
}

onUnload(() => {
    stopClock()
})
</script>

<style lang="scss" scoped>
.pad {
    padding: var(--sp-4);
}

.seg {
    display: flex;
    gap: 12rpx;
    padding: var(--sp-3) var(--page-gutter) var(--sp-1);
    overflow-x: auto;
    white-space: nowrap;

    &__item {
        flex: none;
        height: 56rpx;
        padding: 0 24rpx;
        border-radius: var(--r-pill);
        border: 1rpx solid var(--c-control-border);
        background: var(--c-surface);
        display: flex;
        align-items: center;
        gap: 8rpx;
    }

    &__dot {
        width: 10rpx;
        height: 10rpx;
        border-radius: var(--r-pill);
        background: var(--c-warning);
    }

    &__text {
        font-size: var(--fs-23);
        color: var(--c-ink-2);
        font-weight: var(--fw-medium);
    }

    &__count {
        font-size: var(--fs-20);
        color: var(--c-ink-3);
    }

    &__item.is-on {
        background: var(--c-ink);
        border-color: var(--c-ink);

        .seg__text,
        .seg__count {
            color: var(--c-on-dark);
        }
    }
}

.err-wrap {
    padding-bottom: calc(var(--sp-6) + var(--safe-bottom));
}

.offline {
    margin: var(--sp-3) var(--page-gutter) 0;

    &__head {
        display: block;
        margin-bottom: var(--sp-2);
        font-size: var(--fs-22);
        font-weight: var(--fw-semibold);
        color: var(--c-ink-2);
    }

    &__card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
        padding: var(--sp-3);
        margin-bottom: var(--sp-2);
        border-radius: var(--r-md);
        background: var(--c-surface);
        border: 1rpx solid var(--c-primary-border);
    }

    &__main {
        flex: 1;
        min-width: 0;
    }

    &__title {
        display: block;
        font-size: var(--fs-25);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &__meta {
        display: block;
        margin-top: 6rpx;
        font-size: var(--fs-20);
        color: var(--c-brand-text);
    }
}

.mv {
    padding: var(--sp-2) 0 calc(var(--sp-6) + var(--safe-bottom));

    &__end {
        padding: var(--sp-3) 0 var(--sp-1);
        text-align: center;
    }

    &__end-text {
        font-size: var(--fs-20);
        color: var(--c-ink-3);
        letter-spacing: 0.1em;
    }
}

/* 置顶：深墨黑金电子票根卡 */
.next {
    margin: 0 var(--page-gutter) var(--sp-3);
    border-radius: var(--r-md);
    background: linear-gradient(
        152deg,
        var(--c-dark-1) 0%,
        var(--c-dark-2) 58%,
        var(--c-dark-3) 100%
    );
    border: 1rpx solid var(--c-primary-border);
    overflow: hidden;

    &__top {
        padding: 28rpx 28rpx 22rpx;
    }

    &__kicker-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__flag {
        font-family: var(--font-serif);
        font-size: var(--fs-18);
        letter-spacing: 0.16em;
        color: var(--c-brand-soft);
    }

    &__cd-pill {
        padding: 4rpx 16rpx;
        border-radius: var(--r-pill);
        background: rgba(217, 190, 130, 0.14);
        border: 1rpx solid rgba(217, 190, 130, 0.3);
    }

    &__cd {
        font-size: var(--fs-20);
        color: var(--c-brand-soft);
        font-weight: var(--fw-medium);
    }

    &__body {
        display: flex;
        align-items: center;
        gap: var(--sp-3);
        margin-top: var(--sp-3);
    }

    &__cover {
        width: 112rpx;
        height: 112rpx;
        border-radius: var(--r-sm);
        flex: none;
        border: 1rpx solid rgba(217, 190, 130, 0.35);

        &--monogram {
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(217, 190, 130, 0.12);
        }
    }

    &__mono {
        font-family: var(--font-serif);
        font-size: var(--fs-xl);
        color: var(--c-brand-soft);
    }

    &__info {
        flex: 1;
        min-width: 0;
    }

    &__title {
        display: block;
        font-size: var(--fs-lg);
        font-weight: var(--fw-medium);
        line-height: var(--lh-tight);
        color: var(--c-on-dark);
    }

    &__meta {
        display: block;
        margin-top: 8rpx;
        font-size: var(--fs-21);
        line-height: var(--lh-body);
        color: var(--c-on-dark-2);
    }

    &__perf {
        position: relative;
        height: 28rpx;
        display: flex;
        align-items: center;
    }

    &__notch {
        position: absolute;
        top: 2rpx;
        width: 24rpx;
        height: 24rpx;
        border-radius: var(--r-pill);
        background: var(--c-bg);
        border: 1rpx solid var(--c-primary-border);

        &--l {
            left: -13rpx;
        }

        &--r {
            right: -13rpx;
        }
    }

    &__dash {
        width: calc(100% - 36rpx);
        margin: 0 auto;
        border-top: 1rpx dashed rgba(217, 190, 130, 0.32);
    }

    &__foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
        padding: 16rpx 28rpx 26rpx;
    }

    &__code-wrap {
        flex: 1;
        min-width: 0;
    }

    &__code-label {
        display: block;
        font-size: var(--fs-19);
        color: var(--c-on-dark-2);
    }

    &__code-val {
        display: block;
        margin-top: 4rpx;
        font-family: var(--font-serif);
        font-size: var(--fs-lg);
        letter-spacing: 0.14em;
        color: var(--c-brand-soft);
    }

    &__cta {
        flex: none;
        height: 62rpx;
        padding: 0 26rpx;
        border-radius: var(--r-pill);
        background: var(--c-brand-soft);
        display: flex;
        align-items: center;
        gap: 4rpx;
    }

    &__cta-text {
        font-size: var(--fs-23);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__cta-ic {
        color: var(--c-ink);
    }
}

/* 轻票根列表卡 */
.card {
    margin: 0 var(--page-gutter) var(--sp-3);
    padding: var(--sp-3);
    border-radius: var(--r-md);
    background: var(--c-surface);
    border: 1rpx solid var(--c-line);

    &__h {
        display: flex;
        align-items: flex-start;
        gap: 18rpx;
    }

    &__dt {
        width: 92rpx;
        flex: none;
        text-align: center;
        border-right: 1rpx solid var(--c-line);
        padding-right: 14rpx;
    }

    &__dt-d {
        display: block;
        font-family: var(--font-serif);
        font-size: var(--fs-md);
        color: var(--c-ink);
    }

    &__dt-w {
        display: block;
        margin-top: 4rpx;
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }

    &__tt {
        flex: 1;
        min-width: 0;
    }

    &__title {
        display: block;
        font-size: var(--fs-26);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
        line-height: var(--lh-tight);
    }

    &__sub {
        display: block;
        margin-top: 8rpx;
        font-size: var(--fs-21);
        color: var(--c-ink-3);
        line-height: var(--lh-body);
    }

    &__banner {
        margin-top: var(--sp-2);
        padding: 12rpx 18rpx;
        border-radius: var(--r-sm);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);

        &--warn {
            background: var(--c-warning-bg);
            border: 1rpx solid var(--c-warning-border);
            color: var(--c-warning);
        }

        &--info {
            background: var(--c-info-bg);
            border: 1rpx solid var(--c-info-border);
            color: var(--c-info);
        }

        &--danger {
            background: var(--c-danger-bg);
            border: 1rpx solid var(--c-danger-border);
            color: var(--c-danger);
        }

        &--gold {
            background: var(--c-brand-bg);
            border: 1rpx solid var(--c-primary-border);
            color: var(--c-brand-text);
        }
    }

    &__banner-text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-21);
        line-height: var(--lh-body);
    }

    &__banner-right {
        flex: none;
        font-family: var(--font-serif);
        font-size: var(--fs-21);
        font-weight: var(--fw-semibold);
        letter-spacing: 0.06em;
    }

    &__foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: var(--sp-2);
        padding-top: var(--sp-2);
        border-top: 1rpx solid var(--c-line);
    }

    &__info {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-20);
        color: var(--c-ink-3);
        line-height: var(--lh-body);
    }

    &__acts {
        display: flex;
        gap: 12rpx;
        flex: none;
        margin-left: 12rpx;
    }

    &.is-done {
        background: var(--c-bg);
        border-style: dashed;

        .card__title {
            color: var(--c-ink-2);
        }
    }
}

.btn {
    color: var(--c-ink-2);
    height: 58rpx;
    padding: 0 22rpx;
    border-radius: var(--r-pill);
    border: 1rpx solid var(--c-control-border);
    display: flex;
    align-items: center;
    gap: 2rpx;

    &__text {
        font-size: var(--fs-21);
        color: var(--c-ink-2);
        font-weight: var(--fw-medium);

        &--on {
            color: var(--c-on-dark);
        }
    }

    &__ic {
        color: var(--c-ink-2);
    }

    &--ink {
        background: var(--c-ink);
        border-color: var(--c-ink);
    }
}

.rec {
    margin: var(--sp-3) var(--page-gutter) 0;
    padding: var(--sp-3);
    border-radius: var(--r-md);
    background: var(--c-brand-bg);
    border: 1rpx solid var(--c-primary-border);

    &__h {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    &__title {
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-text);
    }

    &__more {
        display: flex;
        align-items: center;
        gap: 2rpx;
    }

    &__more-text {
        font-size: var(--fs-21);
        color: var(--c-ink-2);
    }

    &__more-ic {
        color: var(--c-ink-3);
    }

    &__p {
        display: block;
        margin-top: 10rpx;
        font-size: var(--fs-21);
        line-height: var(--lh-loose);
        color: var(--c-ink-2);
    }
}

.note {
    display: block;
    margin: var(--sp-3) var(--page-gutter) 0;
    text-align: center;
    font-size: var(--fs-19);
    color: var(--c-ink-3);
}
</style>
