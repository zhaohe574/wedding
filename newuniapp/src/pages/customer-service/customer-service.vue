<template>
    <!--
        24 客服管家（设计稿 4335–4441）
        目标：找不到具体某个人时，还有一个确定能找到人的地方，并说清这个人能办什么。
        数据：customer_service.startConsult（免登录可用，未登录返回兜底客服）。
        在线态由 service_time 本地推算；reply_avg_minutes 缺失时不显示「N 分钟平均回复」（F-37）。
    -->
    <!-- pages.json 该页仍是系统导航（navigationBarTitleText: 在线客服），故 nav=false；
         设计稿标题为「客服管家」，标题文案需架构师改 pages.json，不由页面自行画两套导航 -->
    <page-shell bg="page" title="客服管家">
        <view v-if="loading" class="pad sk">
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
            @retry="load"
        />

        <view v-else class="cs">
            <!-- CS Hero（深色）：先给一个人，再谈保障 -->
            <view class="hero">
                <view class="hero__top">
                    <view class="hero__av">{{ surname }}</view>
                    <view class="hero__who">
                        <view class="hero__n">
                            <text class="hero__name">{{ contact.name || '平台客服' }}</text>
                            <text class="hero__badge">{{ roleBadge }}</text>
                        </view>
                        <view class="hero__state">
                            <view class="hero__dot" :class="{ 'hero__dot--off': !online }" />
                            <text class="hero__state-text">{{ stateText }}</text>
                        </view>
                    </view>
                </view>
                <view v-if="heroCells.length" class="hero__line">
                    <template v-for="(c, i) in heroCells" :key="c.label">
                        <view v-if="i > 0" class="hero__sep" />
                        <view class="hero__cell">
                            <text v-if="c.num" class="hero__cell-num">{{ c.num }}</text>
                            <text class="hero__cell-label">{{ c.label }}</text>
                        </view>
                    </template>
                </view>
            </view>

            <!-- 在线咨询：两个通道并列，微信在线（主）+ 电话专线（备） -->
            <base-card class="blk">
                <section-header title="在线咨询" flush />
                <text class="desc">排期、履约、售后，先在这里说一句。</text>
                <view class="acts">
                    <view
                        hover-class="is-pressed"
                        class="act act--ink"
                        aria-role="button"
                        :aria-label="`微信咨询，${contact.name || '平台客服'}`"
                        @tap="goConcierge"
                    >
                        <tn-icon name="wechat" :size="30" color="var(--c-brand-soft)" />
                        <text class="act__text act__text--ink">微信咨询</text>
                    </view>
                    <view
                        v-if="contact.mobile"
                        hover-class="is-pressed"
                        class="act act--line"
                        aria-role="button"
                        :aria-label="`电话专线 ${contact.mobile}`"
                        @tap="askCall"
                    >
                        <tn-icon name="phone" :size="28" color="var(--c-ink-2)" />
                        <text class="act__text">电话专线</text>
                    </view>
                </view>
                <text v-if="!contact.mobile" class="acts__hint">
                    暂未配置电话专线，请在微信咨询里留言。
                </text>
            </base-card>

            <!-- 能帮到什么：用可核对的数字替代形容词 -->
            <base-card class="blk">
                <section-header title="能帮到什么" flush />
                <view class="grid2">
                    <view v-for="it in capabilities" :key="it.label" class="grid2__it">
                        <text class="grid2__label">{{ it.label }}</text>
                        <text class="grid2__value">{{ it.value }}</text>
                    </view>
                </view>
            </base-card>

            <!-- 平台承诺：放在能力之后，顺序反了就成免责声明 -->
            <base-card class="blk">
                <section-header title="平台管家服务承诺" subtitle="全程守护备婚与礼成" flush />
                <view class="promise">
                    <view v-for="p in promises" :key="p.title" class="promise__g">
                        <view class="promise__i">
                            <tn-icon :name="p.icon" :size="24" color="var(--c-brand-text)" />
                        </view>
                        <view class="promise__b">
                            <text class="promise__t">{{ p.title }}</text>
                            <text class="promise__d">{{ p.desc }}</text>
                        </view>
                    </view>
                </view>
            </base-card>

            <!-- FAQ：只给问题，答案就地展开（不弹层） -->
            <base-card class="blk">
                <section-header title="常见问题" subtitle="点击查看处理指引" flush />
                <view class="faq">
                    <view v-for="(f, i) in FAQ" :key="f.q" class="faq__row">
                        <view
                            hover-class="is-pressed"
                            class="faq__head"
                            :class="{ 'faq__head--last': i === FAQ.length - 1 && openFaq !== i }"
                            aria-role="button"
                            :aria-label="`${f.q}，${openFaq === i ? '收起' : '展开'}`"
                            @tap="toggleFaq(i)"
                        >
                            <text class="faq__q">{{ f.q }}</text>
                            <tn-icon
                                :name="openFaq === i ? 'up' : 'down'"
                                :size="22"
                                color="var(--c-ink-3)"
                            />
                        </view>
                        <text v-if="openFaq === i" class="faq__a">{{ f.a }}</text>
                    </view>
                </view>
            </base-card>

            <text class="foot">
                客服管家由平台统一安排；服务时段外留言，我们会在下一个服务时段内回复。
            </text>
        </view>
    </page-shell>

    <!-- 打电话前确认号码与服务时段 -->
    <tn-modal ref="modalRef" />
    <app-toast />
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { startConsult, type ConsultContact } from '@/api/service'
import { navigate } from '@/utils/link'
import { report } from '@/platform/report'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { toast } from '@/composables/useToast'

/** 与 17 我的咨询同页面的入口路径 */
const CONCIERGE_PATH = '/packages/pkg-service/pages/concierge/concierge'

/** FAQ 文案：设计稿只给问题，答案按现有业务口径写，不含未定义的费用规则 */
const FAQ: Array<{ q: string; a: string }> = [
    {
        q: '遇到档期调整或时间变更怎么处理',
        a: '在订单详情进入「订单售后」，提交改期申请并选期望的新档期。顾问会在 24 小时内与你确认档期与差价；审核期间原档期仍然保留，不会二次售出。'
    },
    {
        q: '服务过程中有分歧或不满意怎么办',
        a: '直接联系专属客服管家，或在订单售后里提交诉求。平台会跟进到闭环，处理结果通过服务号通知。'
    },
    {
        q: '怎么查服务合同与尾款结算',
        a: '订单详情里可以查看合同与每次支付的记录；尾款开放支付时会通过服务号提醒，金额以服务端账单为准。'
    }
]

/** 能力四格：静态平台口径，非用户数据 */
const capabilities = [
    { label: '服务响应', value: '2 小时内' },
    { label: '协助范围', value: '排期 / 履约 / 售后' },
    { label: '咨询场景', value: '档期调整 · 合同款项' },
    { label: '服务保障', value: '满意闭环跟进' }
]

/** 平台承诺四格 */
const promises = [
    { icon: 'check', title: '实名甄选', desc: '服务人员严格认证' },
    { icon: 'pay', title: '资金存管', desc: '满意验收后结算' },
    { icon: 'clock', title: '极速响应', desc: '管家 2 小时跟进' },
    { icon: 'like', title: '先行垫付', desc: '平台全程托底' }
]

const loading = ref(true)
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))
const error = computed(() => !!rawError.value)

const entryType = ref<'advisor' | 'fallback'>('fallback')
const chatEnabled = ref(true)
const openFaq = ref(-1)
const modalRef = ref<any>(null)

const contact = reactive<ConsultContact>({
    name: '',
    role: '',
    mobile: '',
    avatar: '',
    service_time: '',
    tips: ''
})

/** ✘ F-37：在线态与平均回复时长后端没有正式字段，按可选读取，缺了就不显示 */
const meta = reactive<{ online?: number; reply_avg_minutes?: number }>({})

const roleBadge = computed(() => (entryType.value === 'advisor' ? '专属客服管家' : '平台客服'))
const surname = computed(() => (contact.name || '客').trim().slice(0, 1))

/** 「09:00-21:00」→ [起, 止] 分钟数；解析不出来返回 null（不猜时段） */
function parseServiceWindow(text: string): [number, number] | null {
    const m = /(\d{1,2})\s*[:：]\s*(\d{2})\s*[-–—~～至]\s*(\d{1,2})\s*[:：]\s*(\d{2})/.exec(
        text || ''
    )
    if (!m) return null
    return [Number(m[1]) * 60 + Number(m[2]), Number(m[3]) * 60 + Number(m[4])]
}

const window = computed(() => parseServiceWindow(contact.service_time))

/**
 * 在线判定：后端给 online 就听后端；否则按 service_time 本地推算。
 * 都拿不到就返回 null —— 不写死的「在线」，避免晚上八点还告诉用户有人值班。
 */
const online = computed<boolean | null>(() => {
    if (typeof meta.online === 'number') return Number(meta.online) === 1
    const w = window.value
    if (!w) return null
    const now = new Date()
    const cur = now.getHours() * 60 + now.getMinutes()
    const [start, end] = w
    return start <= end ? cur >= start && cur <= end : cur >= start || cur <= end
})

const stateText = computed(() => {
    const time = (contact.service_time || '').trim()
    if (online.value === null) return time ? `服务时段 ${time}` : '服务时段以客服告知为准'
    if (online.value) return time ? `在线 · ${time}` : '在线'
    return time ? `现在不在服务时段 · ${time}` : '现在不在服务时段'
})

/** Hero 底部一行三格：只渲染有数据的格子，不拼假数字 */
const heroCells = computed(() => {
    const cells: Array<{ num?: number | string; label: string }> = []
    if (typeof meta.reply_avg_minutes === 'number' && meta.reply_avg_minutes > 0) {
        cells.push({ num: meta.reply_avg_minutes, label: '分钟平均回复' })
    }
    cells.push({ num: 2, label: '小时内跟进' })
    cells.push({ label: '紧急事项全天响应' })
    return cells
})

async function load() {
    loading.value = true
    rawError.value = null
    try {
        const res = await startConsult({ scene: 'support' })
        if (res) {
            entryType.value = res.entry_type || 'fallback'
            chatEnabled.value = res.customer_service_chat?.enabled !== false
            Object.assign(contact, res.contact || {})
            const c = (res.contact || {}) as any
            meta.online = typeof c.online === 'number' ? c.online : undefined
            meta.reply_avg_minutes =
                typeof c.reply_avg_minutes === 'number' ? c.reply_avg_minutes : undefined
        }
    } catch (e) {
        rawError.value = e
        report('api_error', {
            endpoint: 'customer_service.startConsult',
            message: '客服管家入口初始化失败'
        })
    } finally {
        loading.value = false
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

function toggleFaq(i: number) {
    openFaq.value = openFaq.value === i ? -1 : i
}

function goConcierge() {
    if (!chatEnabled.value) {
        // 微信客服通道被后台关闭时，不静默失败：直接给电话，没有电话就明说
        if (contact.mobile) {
            askCall()
            return
        }
        toast.info('在线客服暂不可用，请稍后再试')
        return
    }
    navigate({ path: CONCIERGE_PATH, query: { scene: 'support' } })
}

function askCall() {
    if (!contact.mobile) return
    modalRef.value?.showModal({
        title: '拨打客服专线',
        content: `${contact.mobile}\n${stateText.value}`,
        showCancel: true,
        cancelText: '取消',
        confirmText: '拨打',
        confirm: () => {
            uni.makePhoneCall({
                phoneNumber: contact.mobile,
                fail: () => {
                    /* 用户取消或设备不支持，静默 */
                }
            })
            return true
        }
    })
}

onShow(() => load())
</script>

<style lang="scss" scoped>
/* 设计稿左右边距 22px × 1.923 ≈ 42rpx */
.pad {
    padding: 0 42rpx;
}
.sk {
    padding-top: var(--sp-3);
}
.cs {
    padding: 0 42rpx calc(var(--sp-5) + var(--safe-bottom));
}

/* ---------- CS Hero ---------- */
.hero {
    position: relative;
    margin-top: var(--sp-2);
    border-radius: var(--r-36);
    padding: 32rpx 32rpx 28rpx;
    overflow: hidden;
    background: var(--c-ink);
    border: 1rpx solid var(--c-line-strong);

    &__top {
        display: flex;
        align-items: center;
        gap: var(--sp-3);
        position: relative;
    }

    &__av {
        width: 100rpx;
        height: 100rpx;
        border-radius: var(--r-pill);
        flex: none;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: var(--font-serif);
        font-size: var(--fs-38);
        color: var(--c-brand-soft);
        background: var(--c-ink-2);
        border: 1rpx solid var(--c-brand-text);
    }

    &__who {
        flex: 1;
        min-width: 0;
    }

    &__n {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
    }

    &__name {
        font-size: var(--fs-30);
        font-weight: var(--fw-semibold);
        letter-spacing: 0.03em;
        color: var(--c-on-dark);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    &__badge {
        flex: none;
        height: 32rpx;
        padding: 0 var(--sp-2);
        border-radius: var(--r-xs);
        font-size: var(--fs-xs);
        line-height: 32rpx;
        letter-spacing: 0.05em;
        background: var(--c-brand-text);
        color: var(--c-on-dark);
    }

    &__state {
        display: flex;
        align-items: center;
        gap: var(--sp-1);
        margin-top: var(--sp-2);
    }

    &__dot {
        width: 8rpx;
        height: 8rpx;
        border-radius: var(--r-pill);
        background: var(--c-success);
        flex: none;

        &--off {
            background: var(--c-ink-3);
        }
    }

    &__state-text {
        font-size: var(--fs-xs);
        letter-spacing: 0.04em;
        color: var(--c-on-dark-2);
    }

    &__line {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
        margin-top: var(--sp-3);
        padding-top: var(--sp-3);
        border-top: 1rpx solid var(--c-ink-2);
        flex-wrap: wrap;
    }

    &__sep {
        width: 1rpx;
        height: 18rpx;
        background: var(--c-ink-3);
        flex: none;
    }

    &__cell {
        display: flex;
        align-items: baseline;
        gap: 2rpx;
    }

    &__cell-num {
        font-family: var(--font-serif);
        font-size: var(--fs-26);
        color: var(--c-brand-soft);
        margin-right: 2rpx;
    }

    &__cell-label {
        font-size: var(--fs-xs);
        letter-spacing: 0.04em;
        color: var(--c-on-dark-2);
    }
}

/* ---------- 通用区块 ---------- */
.blk {
    margin-top: var(--sp-3);
}

.desc {
    display: block;
    margin-top: var(--sp-1);
    font-size: var(--fs-xs);
    line-height: var(--lh-loose);
    color: var(--c-ink-3);
}

/* ---------- 双通道 ---------- */
.acts {
    display: flex;
    gap: var(--sp-2);
    margin-top: var(--sp-3);

    &__hint {
        display: block;
        margin-top: var(--sp-2);
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }
}

.act {
    flex: 1;
    min-width: 0;
    height: 84rpx;
    border-radius: var(--r-md);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--sp-1);

    &--ink {
        background: var(--c-ink);
    }

    &--line {
        border: 1rpx solid var(--c-control-border);
        background: transparent;
    }

    &__text {
        font-size: var(--fs-md);
        letter-spacing: 0.04em;
        color: var(--c-ink);

        &--ink {
            color: var(--c-on-dark);
            font-weight: var(--fw-semibold);
        }
    }
}

/* ---------- 能力四格 ---------- */
.grid2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--sp-3) var(--sp-3);
    margin-top: var(--sp-3);
    padding-top: var(--sp-3);
    border-top: 1rpx solid var(--c-line);

    &__it {
        min-width: 0;
    }

    &__label {
        display: block;
        font-size: var(--fs-xs);
        letter-spacing: 0.06em;
        color: var(--c-ink-3);
    }

    &__value {
        display: block;
        margin-top: var(--sp-1);
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
        overflow-wrap: anywhere;
    }
}

/* ---------- 承诺四格 ---------- */
.promise {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--sp-3) var(--sp-3);
    margin-top: var(--sp-3);

    &__g {
        display: flex;
        align-items: flex-start;
        gap: var(--sp-2);
        min-width: 0;
    }

    &__i {
        width: 48rpx;
        height: 48rpx;
        border-radius: var(--r-xs);
        flex: none;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--c-brand-bg);
    }

    &__b {
        flex: 1;
        min-width: 0;
    }

    &__t {
        display: block;
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        letter-spacing: 0.02em;
        color: var(--c-ink);
    }

    &__d {
        display: block;
        margin-top: 2rpx;
        font-size: var(--fs-xs);
        line-height: var(--lh-tight);
        color: var(--c-ink-3);
    }
}

/* ---------- FAQ ---------- */
.faq {
    margin-top: var(--sp-2);

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-3);
        min-height: 88rpx;
        border-bottom: 1rpx solid var(--c-line);

        &--last {
            border-bottom: 0;
        }
    }

    &__q {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-md);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
        overflow-wrap: anywhere;
    }

    &__a {
        display: block;
        padding: var(--sp-2) 0 var(--sp-3);
        font-size: var(--fs-xs);
        line-height: var(--lh-loose);
        color: var(--c-ink-3);
        border-bottom: 1rpx solid var(--c-line);
        overflow-wrap: anywhere;
    }
}

.foot {
    display: block;
    margin-top: var(--sp-4);
    padding-top: var(--sp-3);
    border-top: 1rpx solid var(--c-line);
    font-size: var(--fs-xs);
    line-height: var(--lh-loose);
    color: var(--c-ink-3);
}
</style>
