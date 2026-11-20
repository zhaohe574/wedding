<template>
    <!--
        15 我的活动（设计稿 3453–3562）
        设计意图：让报过名的活动不烂在列表里——临近那场一眼找到，待支付的别悄悄过期。

        三条数据口径（读 ActivityRegistrationService 得出）：
        1. 分段只有 all / pending_pay / registered / cancel_refund，**没有「已结束」**
           → 「已结束」由前端按活动 end_time 派生，不向后端要枚举
        2. 待支付**不显示倒计时数字**（G-19：myRegistrations 没暴露 pay_expire_time），
           只写「超时释放名额」；按 30 分钟硬算会漂移
        3. 取消报名是「提交申请 + 原因」，不是直接取消
    -->
    <page-shell title="我的活动" bg="page" :show-back="true">
        <view class="seg">
            <view
                v-for="s in SEGS"
                :key="s.key"
                class="seg__item"
                :class="{ 'is-on': activeSeg === s.key }"
                hover-class="is-pressed"
                @tap="switchSeg(s.key)"
            >
                <text class="seg__text">{{ s.label }}</text>
            </view>
        </view>

        <view v-if="loading" class="pad">
            <base-skeleton :rows="4" />
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
            v-else-if="!shown.length"
            status="empty"
            empty-text="还没有报名记录"
            desc="近期有沙龙与工作坊，报名后会出现在这里。"
            action-text="看看近期活动"
            :show-action="true"
            fixed
            @action="goFeed"
        />

        <view v-else class="mv">
            <!-- 最近一场单独置顶：用户来这一页十次有九次是为了它 -->
            <view v-if="nextUp" class="next">
                <view class="next__top">
                    <text class="next__flag">即将参加</text>
                    <text v-if="daysLeft(nextUp) !== null" class="next__cd">
                        还有 {{ daysLeft(nextUp) }} 天
                    </text>
                </view>
                <text class="next__title">{{ titleOf(nextUp) }}</text>
                <text class="next__meta">{{ metaOf(nextUp) }}</text>
                <view class="next__tick">
                    <text class="next__tick-tip">凭报名手机号签到</text>
                    <view class="next__tick-btn" hover-class="is-pressed" @tap="showCode(nextUp)">
                        <text class="next__tick-text">出示入场码</text>
                        <tn-icon name="right" :size="18" />
                    </view>
                </view>
            </view>

            <view
                v-for="r in rest"
                :key="r.id"
                class="card"
                :class="{ 'is-done': isFinished(r) }"
                hover-class="is-pressed"
                @tap="onCardTap(r)"
            >
                <view class="card__h">
                    <view class="card__dt">
                        <text class="card__dt-d">{{ dayOf(r) }}</text>
                        <text class="card__dt-w">{{ weekOf(r) }}</text>
                    </view>
                    <view class="card__tt">
                        <text class="card__title">{{ titleOf(r) }}</text>
                        <text class="card__sub">{{ metaOf(r) }}</text>
                    </view>
                    <status-tag :label="tagOf(r).label" :tone="tagOf(r).tone" size="sm" />
                </view>

                <view class="card__foot">
                    <text class="card__info">{{ infoOf(r) }}</text>
                    <view class="card__acts">
                        <template v-if="isPendingPay(r)">
                            <view class="btn" hover-class="is-pressed" @tap.stop="onCancel(r)">
                                <text class="btn__text">取消</text>
                            </view>
                            <view
                                class="btn btn--ink"
                                hover-class="is-pressed"
                                @tap.stop="onPay(r)"
                            >
                                <text class="btn__text btn__text--on">继续支付</text>
                            </view>
                        </template>
                        <template v-else-if="isFinished(r)">
                            <view class="btn" hover-class="is-pressed" @tap.stop="goReview(r)">
                                <text class="btn__text">看回顾</text>
                                <tn-icon name="right" :size="16" />
                            </view>
                        </template>
                        <template v-else>
                            <view class="btn" hover-class="is-pressed" @tap.stop="showCode(r)">
                                <text class="btn__text">入场码</text>
                            </view>
                        </template>
                    </view>
                </view>
            </view>

            <view class="mv__end">
                <text class="mv__end-text">— 没有更多报名记录 —</text>
            </view>

            <view class="rec">
                <view class="rec__h">
                    <text class="rec__title">近期可报名</text>
                    <view class="rec__more" @tap="goFeed">
                        <text class="rec__more-text">去发现</text>
                        <tn-icon name="right" :size="16" />
                    </view>
                </view>
                <text class="rec__p"
                    >沙龙与工作坊都在「动态」里发布，报名后回到本页看入场码与地址。</text
                >
            </view>

            <text class="note">活动均由{{ brand.name }}主办 · 报名后可在本页查看入场码与地址</text>
        </view>
    </page-shell>

    <!-- 入场码：断网也要能显示（现场没网），所以码值在报名成功时落本地 -->
    <!-- v-model:show 是错的：tn-popup 收的是 modelValue，写 v-model:show 弹窗永远打不开 -->
    <tn-popup v-model="codePopup" open-direction="center" :radius="24">
        <view class="code">
            <text class="code__title">{{ codeTitle }}</text>
            <view class="code__box">
                <text class="code__value">{{ codeValue || '—' }}</text>
            </view>
            <text class="code__tip">向工作人员出示此号码，或报报名手机号签到</text>
            <base-button text="知道了" variant="ink" size="lg" @click="codePopup = false" />
        </view>
    </tn-popup>
    <app-toast />
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onLoad, onPullDownRefresh } from '@dcloudio/uni-app'
import {
    getMyActivityRegistrations,
    getActivityPayWay,
    activityPrepay,
    getActivityPayStatus,
    cancelActivityRegistration,
    type ActivityStatusGroup
} from '@/api/activity'
import { normalizePage } from '@/request'
import { requestPayment, sleep } from '@/platform/pay'
import { navigate } from '@/utils/link'
import { toast } from '@/composables/useToast'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { report } from '@/platform/report'
import { useBrand } from '@/composables/useBrand'

type SegKey = 'all' | 'registered' | 'pending_pay' | 'finished'

const SEGS: Array<{ key: SegKey; label: string }> = [
    { key: 'all', label: '全部' },
    { key: 'registered', label: '待参加' },
    { key: 'pending_pay', label: '待支付' },
    { key: 'finished', label: '已结束' }
]

/** 主办方名称取自后台品牌配置（useBrand），页面不写死品牌 */
const { brand } = useBrand()

const list = ref<any[]>([])
const loading = ref(true)
const rawError = ref<unknown>(null)
const error = computed(() => !!rawError.value)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))
const activeSeg = ref<'all' | 'registered' | 'pending_pay' | 'finished'>('all')

const codePopup = ref(false)
const codeTitle = ref('')
const codeValue = ref('')

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

function dynamicOf(r: any) {
    return r?.dynamic || r?.activity || {}
}

function titleOf(r: any) {
    return dynamicOf(r).title || '活动'
}

function endTimeOf(r: any) {
    const d = dynamicOf(r)
    return d.end_time || d.start_time || d.activity_time || ''
}

function startTimeOf(r: any) {
    const d = dynamicOf(r)
    return d.start_time || d.activity_time || d.begin_time || ''
}

function isFinished(r: any) {
    const t = endTimeOf(r)
    if (!t) return Number(r.registration_status) === 2 && Number(r.pay_status) === 1 && false
    const dt = new Date(String(t).replace(/-/g, '/'))
    if (Number.isNaN(dt.getTime())) return false
    return dt.getTime() < Date.now()
}

function isPendingPay(r: any) {
    return (
        Number(r.pay_status) === 0 ||
        String(r.registration_status_desc || '').indexOf('待支付') > -1
    )
}

function dayOf(r: any) {
    const t = String(startTimeOf(r) || '')
    return t ? t.slice(5, 10).replace('-', '.') : '—'
}

function weekOf(r: any) {
    const t = String(startTimeOf(r) || '')
    if (!t) return ''
    const dt = new Date(t.replace(/-/g, '/'))
    if (Number.isNaN(dt.getTime())) return ''
    return ['周日', '周一', '周二', '周三', '周四', '周五', '周六'][dt.getDay()]
}

/** 「双人票 · ¥199 · 杭州 天目里 3F」：每段各自可缺 */
function metaOf(r: any) {
    const parts: string[] = []
    const ticket = r?.ticket || {}
    const name = ticket.name || r.ticket_name || ''
    if (name) parts.push(name)
    const price = r.ticket_price_label || (r.ticket_price ? `¥${r.ticket_price}` : '')
    if (price) parts.push(price)
    const d = dynamicOf(r)
    const place = [d.city_name, d.address].filter(Boolean).join(' ')
    if (place) parts.push(place)
    return parts.join(' · ')
}

function tagOf(r: any) {
    if (isFinished(r)) return { label: '已结束', tone: 'neutral' as const }
    if (isPendingPay(r)) return { label: '待支付', tone: 'warning' as const }
    if (String(r.cancel_status_desc || ''))
        return { label: r.cancel_status_desc, tone: 'neutral' as const }
    return { label: r.registration_status_desc || '报名成功', tone: 'success' as const }
}

/** 待支付不写倒计时数字（G-19），只说后果 */
function infoOf(r: any) {
    if (isPendingPay(r)) return '支付超时后名额将释放，建议尽快完成支付'
    if (isFinished(r)) return '回顾内容已开放'
    const d = dynamicOf(r)
    const s = String(startTimeOf(r) || '')
    if (s.length >= 16) return `${s.slice(11, 16)} 开始 · 凭报名手机号签到`
    return d.address || '凭报名手机号签到'
}

function daysLeft(r: any) {
    const t = String(startTimeOf(r) || '')
    if (!t) return null
    const dt = new Date(t.replace(/-/g, '/'))
    if (Number.isNaN(dt.getTime())) return null
    const diff = dt.getTime() - Date.now()
    if (diff <= 0) return null
    return Math.max(1, Math.ceil(diff / 86400000))
}

/** 置顶：最近一场「已报名且未结束」 */
const nextUp = computed(() => {
    const upcoming = list.value
        .filter((r) => !isFinished(r) && !isPendingPay(r))
        .sort((a, b) => {
            const ta = new Date(String(startTimeOf(a)).replace(/-/g, '/')).getTime() || 0
            const tb = new Date(String(startTimeOf(b)).replace(/-/g, '/')).getTime() || 0
            return ta - tb
        })
    return upcoming[0] || null
})

const rest = computed(() => list.value.filter((r) => r.id !== nextUp.value?.id))

const shown = computed(() => {
    if (activeSeg.value === 'all') return list.value
    if (activeSeg.value === 'finished') return list.value.filter((r) => isFinished(r))
    if (activeSeg.value === 'pending_pay') return list.value.filter((r) => isPendingPay(r))
    return list.value.filter((r) => !isFinished(r) && !isPendingPay(r))
})

async function load() {
    loading.value = true
    rawError.value = null
    try {
        // 「已结束」没有后端分组，取全量后前端派生；其余分组走服务端
        const group: ActivityStatusGroup | undefined =
            activeSeg.value === 'registered'
                ? 'registered'
                : activeSeg.value === 'pending_pay'
                ? 'pending_pay'
                : undefined
        const res: any = await getMyActivityRegistrations({
            status_group: group,
            page: 1,
            page_size: 50
        })
        const paged = normalizePage<any>(res)
        list.value = paged.list || []
        cacheCodes(list.value)
    } catch (e) {
        rawError.value = e
        report('api_error', {
            endpoint: 'dynamic.activityRegistrations',
            message: '我的活动加载失败'
        })
    } finally {
        loading.value = false
    }
}

function reload() {
    load()
}

function switchSeg(key: typeof activeSeg.value) {
    if (activeSeg.value === key) return
    activeSeg.value = key
    load()
}

/** 入场码落本地：现场常常没网，码不能依赖网络再取一次 */
const CODE_KEY = 'activity_entry_codes'
function cacheCodes(rows: any[]) {
    try {
        const old = uni.getStorageSync(CODE_KEY) || {}
        const next: Record<string, string> = { ...old }
        rows.forEach((r) => {
            const code = r.entry_code || r.checkin_code || r.ticket_no || ''
            if (code) next[String(r.id)] = String(code)
        })
        uni.setStorageSync(CODE_KEY, next)
    } catch {
        // 缓存失败不影响主流程
    }
}

function showCode(r: any) {
    const code = r.entry_code || r.checkin_code || r.ticket_no || ''
    codeTitle.value = titleOf(r)
    codeValue.value = String(code || '')
    if (!code) {
        const cached = (uni.getStorageSync(CODE_KEY) || {})[String(r.id)] || ''
        codeValue.value = cached
    }
    codePopup.value = true
    if (!codeValue.value) {
        toast.info('入场码将在报名成功后由短信下发')
    }
}

function onCardTap(r: any) {
    if (isPendingPay(r)) {
        onPay(r)
        return
    }
    const d = dynamicOf(r)
    if (d.id) {
        navigate({
            path: '/packages/pkg-catalog/pages/feed-detail/feed-detail',
            query: { id: d.id }
        })
    }
}

/** 继续支付：activityPayWay → activityPrepay → requestPayment → 轮询 payStatus */
async function onPay(r: any) {
    try {
        await getActivityPayWay({ registration_id: r.id })
        const res: any = await activityPrepay({ registration_id: r.id, pay_way: 2 })
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
        // requestPayment 成功 ≠ 支付成功，必须轮询
        for (let i = 0; i < 5; i++) {
            await sleep(1200)
            const st: any = await getActivityPayStatus({ registration_id: r.id })
            if (Number(st?.pay_status) === 1) {
                toast.success('支付成功')
                load()
                return
            }
        }
        toast.info('支付结果确认中，稍后可在本页查看')
        load()
    } catch (e: any) {
        toast.error(e?.message || '支付失败，请重试')
        report('api_error', { endpoint: 'dynamic.activityPrepay', message: '活动支付失败' })
    }
}

async function onCancel(r: any) {
    uni.showModal({
        title: '取消报名',
        content: '取消后名额将释放，确定要取消吗？',
        confirmText: '确定取消',
        confirmColor: '#B4453C',
        success: async (res) => {
            if (!res.confirm) return
            try {
                await cancelActivityRegistration({ registration_id: r.id, reason: '用户主动取消' })
                toast.success('已提交取消申请')
                load()
            } catch {
                toast.error('取消失败，请重试')
            }
        }
    })
}

function goReview(r: any) {
    const d = dynamicOf(r)
    if (d.id) {
        navigate({
            path: '/packages/pkg-catalog/pages/feed-detail/feed-detail',
            query: { id: d.id }
        })
        return
    }
    toast.info('回顾内容整理中')
}

function goFeed() {
    uni.switchTab({ url: '/pages/feed/feed' })
}

onLoad(() => load())
onPullDownRefresh(() => {
    load()
    uni.stopPullDownRefresh()
})
</script>

<style lang="scss" scoped>
.pad {
    padding: var(--sp-4);
}

.seg {
    display: flex;
    gap: 12rpx;
    padding: 24rpx 42rpx 8rpx;
    overflow-x: auto;
    white-space: nowrap;

    &__item {
        flex: none;
        height: 56rpx;
        padding: 0 26rpx;
        border-radius: var(--r-pill);
        /* 可点控件边界（模板 @tap="switchSeg" + hover-class + is-on）：--c-line-strong 1.60:1 → 3.77:1 */
        border: 1rpx solid var(--c-control-border);
        background: var(--c-surface);
        display: flex;
        align-items: center;
    }

    &__text {
        font-size: var(--fs-23);
        color: var(--c-ink-2);
    }

    &__item.is-on {
        background: var(--c-ink);
        border-color: var(--c-ink);

        .seg__text {
            color: var(--c-on-dark);
        }
    }
}

.mv {
    padding: 12rpx 0 calc(var(--sp-6) + var(--safe-bottom));

    &__end {
        padding: 22rpx 0 6rpx;
        text-align: center;
    }

    &__end-text {
        font-size: 20rpx;
        color: var(--c-ink-3);
        letter-spacing: 0.1em;
    }
}

/* 置顶：深色卡，全站仅此一处与结果页同款 */
.next {
    margin: 0 42rpx 20rpx;
    padding: 28rpx;
    border-radius: var(--r-md);
    background: linear-gradient(
        152deg,
        var(--c-dark-1) 0%,
        var(--c-dark-2) 58%,
        var(--c-dark-3) 100%
    );
    border: 1rpx solid var(--c-primary-border);

    &__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    &__flag {
        font-family: var(--font-serif);
        font-size: var(--fs-18);
        letter-spacing: 0.22em;
        color: var(--c-brand-soft);
        text-transform: uppercase;
    }

    &__cd {
        font-size: 20rpx;
        color: rgba(255, 253, 248, 0.6);
    }

    &__title {
        display: block;
        margin-top: 16rpx;
        font-size: 32rpx;
        font-weight: var(--fw-medium);
        color: var(--c-on-dark);
        letter-spacing: 0.02em;
    }

    &__meta {
        display: block;
        margin-top: 12rpx;
        font-size: var(--fs-21);
        line-height: 1.7;
        color: rgba(255, 253, 248, 0.62);
    }

    &__tick {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 22rpx;
        padding-top: 20rpx;
        border-top: 1rpx solid rgba(217, 190, 130, 0.22);
    }

    &__tick-tip {
        font-size: 20rpx;
        color: rgba(255, 253, 248, 0.5);
    }

    &__tick-btn {
        display: flex;
        align-items: center;
        gap: 4rpx;
        /* 箭头与「出示入场码」同色；&-text 自己声明了 --c-brand-soft */
        color: var(--c-brand-soft);
    }

    &__tick-text {
        font-size: var(--fs-22);
        color: var(--c-brand-soft);
    }
}

.card {
    margin: 0 42rpx 18rpx;
    padding: 24rpx;
    border-radius: var(--r-md);
    background: var(--c-surface);
    border: 1rpx solid var(--c-line);

    &__h {
        display: flex;
        align-items: flex-start;
        gap: 18rpx;
    }

    &__dt {
        width: 90rpx;
        flex: none;
        text-align: center;
        border-right: 1rpx solid var(--c-line);
        padding-right: 14rpx;
    }

    &__dt-d {
        display: block;
        font-family: var(--font-serif);
        font-size: 28rpx;
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
        line-height: var(--lh-body);
    }

    &__sub {
        display: block;
        margin-top: 8rpx;
        font-size: 20rpx;
        color: var(--c-ink-3);
    }

    &__foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 18rpx;
        padding-top: 16rpx;
        border-top: 1rpx solid var(--c-line);
    }

    &__info {
        flex: 1;
        min-width: 0;
        font-size: 20rpx;
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
    /* 「看回顾」箭头；&-text 自己声明了 --c-ink-2 */
    color: var(--c-ink-2);
    height: 58rpx;
    padding: 0 22rpx;
    border-radius: var(--r-pill);
    /* 可点控件边界（模板 hover-class + @tap.stop="onCancel/goReview"）：--c-line-strong 1.60:1 → 3.77:1 */
    border: 1rpx solid var(--c-control-border);
    display: flex;
    align-items: center;
    gap: 2rpx;

    &__text {
        font-size: var(--fs-21);
        color: var(--c-ink-2);

        &--on {
            color: var(--c-on-dark);
        }
    }

    &--ink {
        background: var(--c-ink);
        border-color: var(--c-ink);
    }
}

.rec {
    margin: 20rpx 42rpx 0;
    padding: 24rpx;
    border-radius: var(--r-md);
    background: var(--c-brand-bg);

    &__h {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    &__title {
        font-size: 24rpx;
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

    /* 「去发现」箭头 */
    &__more {
        color: var(--c-ink-3);
    }

    &__p {
        display: block;
        margin-top: 10rpx;
        font-size: 20rpx;
        line-height: 1.7;
        color: var(--c-ink-2);
    }
}

.note {
    display: block;
    margin: 26rpx 42rpx 0;
    text-align: center;
    font-size: var(--fs-19);
    color: var(--c-ink-3);
}

.code {
    width: 560rpx;
    padding: 40rpx 34rpx calc(30rpx);
    display: flex;
    flex-direction: column;
    align-items: center;

    &__title {
        font-size: 28rpx;
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        text-align: center;
    }

    &__box {
        margin: 28rpx 0 20rpx;
        padding: 26rpx 40rpx;
        border-radius: var(--r-md);
        background: var(--c-brand-bg);
        border: 1rpx dashed var(--c-primary-border);
    }

    &__value {
        font-family: var(--font-serif);
        font-size: var(--fs-46);
        letter-spacing: 0.16em;
        color: var(--c-brand-text);
    }

    &__tip {
        margin-bottom: 26rpx;
        font-size: 20rpx;
        line-height: var(--lh-body);
        color: var(--c-ink-3);
        text-align: center;
    }
}
</style>
