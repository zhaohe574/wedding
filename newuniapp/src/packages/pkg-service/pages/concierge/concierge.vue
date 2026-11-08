<template>
    <!--
        17 我的咨询（设计稿 3677–3784）
        ⚠ 本屏**不做 IM**：后端只有 customer_service.startConsult（返回该找谁），
        会话本体走微信原生客服（<button open-type="contact">），平台侧没有 conversation/message 表。
        设计稿那版消息流（气泡 / 时间分隔 / 新消息金线 / 订单卡消息 / 输入框 / 快捷问题）是被否决的方案，
        本页只交付「顾问名片 + 进入微信客服」，不画聊天记录、不放输入框。
    -->
    <page-shell title="我的咨询" bg="page">
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

        <view v-else class="cc">
            <!-- 顾问名片：先给一个人，再给一个入口 -->
            <view class="who">
                <view class="who__top">
                    <view class="who__av">{{ surname }}</view>
                    <view class="who__body">
                        <view class="who__n">
                            <text class="who__name">{{ contact.name || '平台客服' }}</text>
                            <text class="who__badge">{{ roleBadge }}</text>
                        </view>
                        <view class="who__state">
                            <view class="who__dot" :class="{ 'who__dot--off': online === false }" />
                            <text class="who__state-text">{{ stateText }}</text>
                        </view>
                    </view>
                </view>

                <text v-if="contact.tips" class="who__tips">{{ contact.tips }}</text>

                <view v-if="contact.service_time" class="who__row">
                    <tn-icon name="clock" :size="24" color="var(--c-ink-3)" />
                    <text class="who__row-text">服务时段 {{ contact.service_time }}</text>
                </view>
            </view>

            <!-- 关联订单条：只在带 orderId 时出现，内容全部来自订单详情 -->
            <view
                v-if="orderId"
                hover-class="is-pressed"
                class="ctx"
                aria-role="button"
                aria-label="查看关联订单"
                @tap="goOrder"
            >
                <tn-icon name="receipt" :size="26" color="var(--c-brand-text)" />
                <text class="ctx__text">{{ orderLine }}</text>
                <view class="ctx__go">
                    <text class="ctx__go-text">查看</text>
                    <tn-icon name="right" :size="18" color="var(--c-brand-text)" />
                </view>
            </view>

            <!-- 主通道：微信原生客服会话（唯一能真正"发出消息"的通道） -->
            <button v-if="chatEnabled" class="cta" open-type="contact">
                <tn-icon name="wechat" :size="32" color="var(--c-brand-soft)" />
                <text class="cta__text">进入微信客服</text>
            </button>
            <view v-else class="off">
                <text class="off__title">在线客服暂不可用</text>
                <text class="off__desc">
                    {{
                        contact.mobile
                            ? '可以先拨打客服电话，我们会记录你的问题。'
                            : '请稍后再试，或前往客服管家查看其他联系方式。'
                    }}
                </text>
                <base-button
                    v-if="contact.mobile"
                    text="拨打客服电话"
                    variant="ink"
                    size="lg"
                    @click="askCall"
                />
                <base-button
                    v-else
                    text="前往客服管家"
                    variant="ghost"
                    size="lg"
                    @click="goConcierge"
                />
            </view>

            <!-- 次要出口：电话 / 复制 -->
            <base-card v-if="contact.mobile" class="blk" :flush="true">
                <view
                    hover-class="is-pressed"
                    class="row"
                    aria-role="button"
                    aria-label="拨打客服电话"
                    @tap="askCall"
                >
                    <tn-icon name="phone" :size="26" color="var(--c-ink-2)" />
                    <text class="row__label">拨打电话</text>
                    <text class="row__value">{{ contact.mobile }}</text>
                    <tn-icon name="right" :size="20" color="var(--c-ink-3)" />
                </view>
                <view
                    hover-class="is-pressed"
                    class="row row--last"
                    aria-role="button"
                    aria-label="复制客服号码"
                    @tap="copyMobile"
                >
                    <tn-icon name="copy" :size="26" color="var(--c-ink-2)" />
                    <text class="row__label">复制号码</text>
                    <tn-icon name="right" :size="20" color="var(--c-ink-3)" />
                </view>
            </base-card>

            <!-- 更多问题：不是聊天，是分流 -->
            <view
                hover-class="is-pressed"
                class="blk more"
                aria-role="button"
                aria-label="前往客服管家查看常见问题"
                @tap="goConcierge"
            >
                <text class="more__text">常见问题与处理指引</text>
                <tn-icon name="right" :size="20" color="var(--c-ink-3)" />
            </view>

            <text class="foot">
                对话在微信客服会话里进行，聊天记录由微信保存，可随时回看；我们不显示「已读」状态。
            </text>
        </view>
    </page-shell>

    <tn-modal ref="modalRef" />
    <app-toast />
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { startConsult, type ConsultContact } from '@/api/service'
import { getOrderDetailV2 } from '@/api/order'
import { navigate } from '@/utils/link'
import { report } from '@/platform/report'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { toast } from '@/composables/useToast'

const CONCIERGE_PATH = '/packages/pkg-service/pages/concierge/concierge'

const loading = ref(true)
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))
const error = computed(() => !!rawError.value)

const orderId = ref(0)
const entryType = ref<'advisor' | 'fallback'>('fallback')
const chatEnabled = ref(true)
const modalRef = ref<any>(null)

const contact = reactive<ConsultContact>({
    name: '',
    role: '',
    mobile: '',
    avatar: '',
    service_time: '',
    tips: ''
})
/** ✘ F-37：在线态 / 平均回复时长无正式字段，按可选读取，缺了就不显示 */
const meta = reactive<{ online?: number; reply_avg_minutes?: number }>({})

/** 关联订单：只用服务端真字段拼，拿不到就退成「关联订单」四个字 */
const orderInfo = reactive<{ service_date: string; package_name: string }>({
    service_date: '',
    package_name: ''
})

const roleBadge = computed(() => (entryType.value === 'advisor' ? '专属策划师' : '平台客服'))
const surname = computed(() => (contact.name || '客').trim().slice(0, 1))

/** 「09:00-21:00」→ [起, 止] 分钟数；解析不出来返回 null（不猜时段） */
function parseServiceWindow(text: string): [number, number] | null {
    const m = /(\d{1,2})\s*[:：]\s*(\d{2})\s*[-–—~～至]\s*(\d{1,2})\s*[:：]\s*(\d{2})/.exec(
        text || ''
    )
    if (!m) return null
    return [Number(m[1]) * 60 + Number(m[2]), Number(m[3]) * 60 + Number(m[4])]
}

/** 在线判定：后端给 online 就听后端，否则按 service_time 本地推算，都拿不到就返回 null */
const online = computed<boolean | null>(() => {
    if (typeof meta.online === 'number') return Number(meta.online) === 1
    const w = parseServiceWindow(contact.service_time)
    if (!w) return null
    const now = new Date()
    const cur = now.getHours() * 60 + now.getMinutes()
    const [start, end] = w
    return start <= end ? cur >= start && cur <= end : cur >= start || cur <= end
})

const stateText = computed(() => {
    const time = (contact.service_time || '').trim()
    const reply =
        typeof meta.reply_avg_minutes === 'number' && meta.reply_avg_minutes > 0
            ? `平均 ${meta.reply_avg_minutes} 分钟回复`
            : ''
    if (online.value === null) {
        return reply || (time ? `服务时段 ${time}` : '服务时段以客服告知为准')
    }
    const base = online.value ? '在线' : '现在不在服务时段'
    return [base, reply].filter(Boolean).join(' · ')
})

/** 「05.20 婚礼 · 全案统筹」；日期或套餐缺失就少写一段，不编 */
const orderLine = computed(() => {
    const parts: string[] = ['关联订单']
    if (orderInfo.service_date) {
        parts.push(`${orderInfo.service_date.slice(5).replace('-', '.')} 婚礼`)
    }
    if (orderInfo.package_name) parts.push(orderInfo.package_name)
    return parts.join(' · ')
})

/** 关联订单：失败不影响咨询入口，条上只显示「关联订单」 */
async function loadOrder() {
    if (!orderId.value) return
    try {
        const res: any = await getOrderDetailV2(orderId.value)
        const order = res?.order || {}
        const item = (res?.items || [])[0] || {}
        orderInfo.service_date = order.service_date || ''
        orderInfo.package_name = item?.package?.name || ''
    } catch {
        report('api_error', { endpoint: 'v2.order', message: '咨询页关联订单加载失败' })
    }
}

async function load() {
    loading.value = true
    rawError.value = null
    try {
        const [res] = await Promise.all([
            startConsult({ scene: 'order', order_id: orderId.value || undefined }),
            loadOrder()
        ])
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
            message: '咨询页顾问加载失败'
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

function goOrder() {
    if (!orderId.value) return
    navigate({ path: '/packages/pkg-order/pages/detail/detail', query: { id: orderId.value } })
}

function goConcierge() {
    navigate({ path: CONCIERGE_PATH, query: { scene: 'support' } })
}

function askCall() {
    if (!contact.mobile) return
    modalRef.value?.showModal({
        title: '拨打客服电话',
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

function copyMobile() {
    if (!contact.mobile) return
    uni.setClipboardData({
        data: contact.mobile,
        success: () => toast.success('号码已复制'),
        fail: () => toast.error('复制失败')
    })
}

onLoad((options: any) => {
    orderId.value = Number(options?.orderId || options?.order_id || 0) || 0
    // conversationId 仅接收不消费：平台侧没有消息表（G-13），不做会话加载
})

onShow(() => load())
</script>

<style lang="scss" scoped>
.pad {
    padding: 0 42rpx;
}
.sk {
    padding-top: var(--sp-3);
}
.cc {
    padding: 0 42rpx calc(var(--sp-5) + var(--safe-bottom));
}

/* ---------- 顾问名片 ---------- */
.who {
    margin-top: var(--sp-2);
    border-radius: var(--r-lg);
    background: var(--c-surface);
    border: 1rpx solid var(--c-line);
    padding: var(--sp-4);

    &__top {
        display: flex;
        align-items: center;
        gap: var(--sp-3);
    }

    &__av {
        width: 104rpx;
        height: 104rpx;
        border-radius: var(--r-pill);
        flex: none;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: var(--font-serif);
        font-size: var(--fs-40);
        color: var(--c-brand-text);
        background: var(--c-brand-bg);
        border: 1rpx solid var(--c-brand-soft);
    }

    &__body {
        flex: 1;
        min-width: 0;
    }

    &__n {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
        flex-wrap: wrap;
    }

    &__name {
        font-size: var(--fs-34);
        font-weight: var(--fw-semibold);
        letter-spacing: 0.03em;
        color: var(--c-ink);
    }

    &__badge {
        height: 32rpx;
        padding: 0 var(--sp-2);
        border-radius: var(--r-xs);
        font-size: var(--fs-xs);
        line-height: 32rpx;
        letter-spacing: 0.04em;
        background: var(--c-brand-bg);
        color: var(--c-brand-text);
        border: 1rpx solid var(--c-brand-soft);
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
        line-height: var(--lh-body);
        color: var(--c-ink-3);
        overflow-wrap: anywhere;
    }

    &__tips {
        display: block;
        margin-top: var(--sp-3);
        padding-top: var(--sp-3);
        border-top: 1rpx solid var(--c-line);
        font-size: var(--fs-xs);
        line-height: var(--lh-loose);
        color: var(--c-ink-3);
        overflow-wrap: anywhere;
    }

    &__row {
        display: flex;
        align-items: center;
        gap: var(--sp-1);
        margin-top: var(--sp-2);
    }

    &__row-text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

/* ---------- 关联订单条 ---------- */
.ctx {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    margin-top: var(--sp-3);
    padding: var(--sp-3);
    border-radius: var(--r-md);
    background: var(--c-brand-bg);
    border: 1rpx solid var(--c-brand-soft);

    &__text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-brand-text);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    &__go {
        flex: none;
        display: flex;
        align-items: center;
        gap: 2rpx;
    }

    &__go-text {
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-text);
    }
}

/* ---------- 主通道（微信原生客服按钮） ---------- */
.cta {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--sp-2);
    width: 100%;
    height: 176rpx;
    margin: var(--sp-4) 0 0;
    padding: 0;
    border-radius: var(--r-lg);
    background: var(--c-ink);
    border: 0;
    line-height: normal;
    box-sizing: border-box;

    /* mp-weixin 原生 button 默认带 ::after 描边，必须清掉 */
    &::after {
        border: 0;
    }

    &__text {
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        letter-spacing: 0.04em;
        color: var(--c-on-dark);
    }
}

/* ---------- 通道关闭的降级 ---------- */
.off {
    margin-top: var(--sp-4);
    padding: var(--sp-4);
    border-radius: var(--r-lg);
    background: var(--c-warning-bg);
    border: 1rpx solid var(--c-warning-border);

    &__title {
        display: block;
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-warning);
    }

    &__desc {
        display: block;
        margin: var(--sp-2) 0 var(--sp-4);
        font-size: var(--fs-xs);
        line-height: var(--lh-loose);
        color: var(--c-warning);
    }
}

/* ---------- 行入口 ---------- */
.blk {
    margin-top: var(--sp-3);
}

.row {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    min-height: 96rpx;
    padding: 0 var(--sp-4);
    border-bottom: 1rpx solid var(--c-line);

    &--last {
        border-bottom: 0;
    }

    &__label {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-md);
        color: var(--c-ink);
    }

    &__value {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.more {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);
    min-height: 96rpx;
    padding: 0 var(--sp-4);
    border-radius: var(--r-md);
    background: var(--c-surface);
    border: 1rpx solid var(--c-line);

    &__text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-md);
        color: var(--c-ink-2);
    }
}

.foot {
    display: block;
    margin-top: var(--sp-4);
    font-size: var(--fs-xs);
    line-height: var(--lh-loose);
    color: var(--c-ink-3);
}
</style>
