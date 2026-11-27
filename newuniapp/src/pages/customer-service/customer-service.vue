<template>
    <!--
        客服管家（pages/customer-service/customer-service）
        数据：customer_service.startConsult（免登录可用；未登录返回平台兜底客服）。
        小程序端主按钮直连微信原生客服（button open-type="contact"）。
    -->
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
            @retry="load(false)"
        />

        <view v-else class="cs">
            <!-- ============ 统一深色礼宾名片 + 主咨询入口 ============ -->
            <view class="concierge">
                <view class="concierge__glow" aria-hidden="true" />

                <view class="concierge__bar">
                    <text v-if="decorate.eyebrow" class="concierge__eyebrow">
                        {{ decorate.eyebrow }}
                    </text>
                    <view v-else />
                    <view class="status-pill" :class="{ 'is-live': online === true }">
                        <view
                            v-if="online !== null"
                            class="status-pill__dot"
                            :class="{ 'is-off': online === false }"
                        />
                        <text class="status-pill__text">{{ statusPillText }}</text>
                    </view>
                </view>

                <view class="concierge__who">
                    <view class="concierge__av">
                        <image
                            v-if="avatarUrl && !avatarFailed"
                            class="concierge__av-img"
                            :src="avatarUrl"
                            mode="aspectFill"
                            @error="avatarFailed = true"
                        />
                        <text v-else>{{ surname }}</text>
                    </view>
                    <view class="concierge__body">
                        <view class="concierge__n">
                            <text class="concierge__name">{{ contact.name || '统一客服' }}</text>
                            <text class="concierge__badge">{{ roleBadge }}</text>
                        </view>
                        <view class="concierge__hours">
                            <tn-icon name="clock" :size="22" />
                            <text class="concierge__hours-text">{{ serviceHoursText }}</text>
                        </view>
                    </view>
                </view>

                <text class="concierge__tips">{{ consultDesc }}</text>

                <view v-if="entryType === 'advisor' && advisorTags.length" class="concierge__tags">
                    <text v-for="tag in advisorTags" :key="tag" class="concierge__tag">
                        {{ tag }}
                    </text>
                </view>

                <view class="concierge__acts">
                    <!-- #ifdef MP-WEIXIN -->
                    <button
                        v-if="chatEnabled"
                        open-type="contact"
                        hover-class="is-pressed"
                        class="cbtn cbtn--btn cbtn--ivory"
                        :aria-label="`${decorate.button_text}，${contact.name || '统一客服'}`"
                    >
                        <tn-icon name="wechat" :size="30" />
                        <text class="cbtn__text cbtn__text--ivory">{{ decorate.button_text }}</text>
                    </button>
                    <view
                        v-else
                        hover-class="is-pressed"
                        class="cbtn cbtn--ivory"
                        aria-role="button"
                        aria-label="联系平台客服"
                        @tap="onFallbackChat"
                    >
                        <tn-icon name="wechat" :size="30" />
                        <text class="cbtn__text cbtn__text--ivory">在线咨询</text>
                    </view>
                    <!-- #endif -->
                    <!-- #ifndef MP-WEIXIN -->
                    <view
                        hover-class="is-pressed"
                        class="cbtn cbtn--ivory"
                        aria-role="button"
                        aria-label="联系平台客服"
                        @tap="onFallbackChat"
                    >
                        <tn-icon name="wechat" :size="30" />
                        <text class="cbtn__text cbtn__text--ivory">{{ decorate.button_text }}</text>
                    </view>
                    <!-- #endif -->

                    <view
                        v-if="contact.mobile"
                        hover-class="is-pressed"
                        class="cbtn cbtn--glass"
                        aria-role="button"
                        :aria-label="`拨打${decorate.phone_button_text} ${contact.mobile}`"
                        @tap="askCall"
                    >
                        <tn-icon name="phone" :size="26" />
                        <text class="cbtn__text cbtn__text--glass">
                            {{ decorate.phone_button_text }}
                        </text>
                    </view>
                </view>

                <view v-if="contact.mobile" class="concierge__meta">
                    <text class="concierge__meta-text">客服专线 {{ contact.mobile }}</text>
                    <view
                        hover-class="is-pressed"
                        class="concierge__meta-action hit-88"
                        aria-role="button"
                        aria-label="复制客服号码"
                        @tap="copyMobile"
                    >
                        <tn-icon name="copy" :size="22" />
                        <text class="concierge__meta-cta">复制号码</text>
                    </view>
                </view>
            </view>

            <!-- ============ 完善婚礼需求 / 预约顾问回电入口 ============ -->
            <view
                hover-class="is-pressed"
                class="intention-bar"
                :class="{ 'is-done': intentionSubmitted }"
                aria-role="button"
                aria-label="预约专属顾问回电"
                @tap="openIntentionSheet"
            >
                <view class="intention-bar__ic">
                    <tn-icon :name="intentionSubmitted ? 'check' : 'edit'" :size="26" />
                </view>
                <view class="intention-bar__body">
                    <text class="intention-bar__title">
                        {{ intentionSubmitted ? '已预约顾问回电' : '预约顾问回电' }}
                    </text>
                    <text v-if="intentionSubmitted" class="intention-bar__desc">
                        {{ intentionBarDesc }}
                    </text>
                </view>
                <view class="intention-bar__cta">
                    <text v-if="intentionSubmitted" class="intention-bar__cta-text">修改需求</text>
                    <tn-icon name="right" :size="18" />
                </view>
            </view>

            <!-- ============ 紧凑自助双联卡 ============ -->
            <view v-if="decorate.show_quick_bar === 1" class="quick-bar">
                <view
                    hover-class="is-pressed"
                    class="quick-bar__cell quick-bar__cell--left"
                    aria-role="button"
                    aria-label="前往我的订单"
                    @tap="goOrders"
                >
                    <view class="quick-bar__ic">
                        <tn-icon name="receipt" :size="26" />
                    </view>
                    <view class="quick-bar__body">
                        <text class="quick-bar__title">我的订单</text>
                        <text class="quick-bar__desc">合同与尾款</text>
                    </view>
                    <tn-icon name="right" :size="20" class="quick-bar__go" />
                </view>

                <view
                    hover-class="is-pressed"
                    class="quick-bar__cell"
                    aria-role="button"
                    aria-label="前往售后服务"
                    @tap="goAftersale"
                >
                    <view class="quick-bar__ic">
                        <tn-icon name="headset" :size="26" />
                    </view>
                    <view class="quick-bar__body">
                        <text class="quick-bar__title">售后服务</text>
                        <text class="quick-bar__desc">改期 · 工单</text>
                    </view>
                    <tn-icon name="right" :size="20" class="quick-bar__go" />
                </view>
            </view>

            <!-- ============ 平台服务保障（2×2 细线格） ============ -->
            <template v-if="visibleAssurances.length">
                <view class="sec">
                    <text class="sec__title">{{ decorate.assurance_title }}</text>
                    <text v-if="decorate.assurance_subtitle" class="sec__sub">
                        {{ decorate.assurance_subtitle }}
                    </text>
                </view>
                <view class="assurance-grid">
                    <view
                        v-for="(p, i) in visibleAssurances"
                        :key="`${p.title}-${i}`"
                        class="assurance-cell"
                    >
                        <view class="assurance-cell__head">
                            <view class="assurance-cell__ic">
                                <tn-icon :name="p.icon" :size="22" />
                            </view>
                            <text class="assurance-cell__title">{{ p.title }}</text>
                        </view>
                        <text v-if="p.desc" class="assurance-cell__desc">{{ p.desc }}</text>
                    </view>
                </view>
            </template>

            <!-- ============ 常见问题（序列手风琴） ============ -->
            <template v-if="visibleFaqs.length">
                <view class="sec">
                    <text class="sec__title">{{ decorate.faq_title }}</text>
                    <text v-if="decorate.faq_subtitle" class="sec__sub">
                        {{ decorate.faq_subtitle }}
                    </text>
                </view>
                <view class="faq-card">
                    <view v-for="(f, i) in visibleFaqs" :key="`${f.q}-${i}`" class="faq-row">
                        <view
                            hover-class="is-pressed"
                            class="faq-row__head"
                            aria-role="button"
                            :aria-label="`${f.q}，${openFaq === i ? '收起' : '展开'}`"
                            @tap="toggleFaq(i)"
                        >
                            <text class="faq-row__idx">{{ String(i + 1).padStart(2, '0') }}</text>
                            <text class="faq-row__q">{{ f.q }}</text>
                            <tn-icon
                                :name="openFaq === i ? 'up' : 'down'"
                                :size="22"
                                class="faq-row__arrow"
                            />
                        </view>
                        <text v-if="openFaq === i" class="faq-row__a">{{ f.a }}</text>
                    </view>
                </view>
            </template>

            <text v-if="decorate.footer_text" class="foot">
                {{ decorate.footer_text }}
            </text>
        </view>

        <template #bottom>
            <!-- 打电话前确认号码与服务时段 -->
            <tn-modal ref="modalRef" />

            <!-- 预约回电 / 婚礼需求抽屉：磁吸半屏，标题 / 关闭 / 吸底由 snap-sheet 统一承担 -->
            <snap-sheet
                v-model="intentionOpen"
                title="预约专属顾问回电"
                :enable-full="true"
                :has-foot="true"
            >
                <text class="intent-sub">完善婚期与需求，顾问将提前备好档期与参考报价</text>

                <view class="ifield">
                    <text class="ifield__label">您的称呼</text>
                    <input
                        v-model="intentionForm.customer_name"
                        class="ifield__input"
                        placeholder="如：林小姐 / 张先生"
                        placeholder-class="ifield__ph"
                        maxlength="50"
                    />
                </view>

                <view class="ifield">
                    <text class="ifield__label">联系电话 *</text>
                    <input
                        v-model="intentionForm.customer_mobile"
                        class="ifield__input"
                        type="number"
                        placeholder="请输入 11 位手机号，方便顾问回电"
                        placeholder-class="ifield__ph"
                        maxlength="20"
                    />
                </view>

                <view class="ifield-row">
                    <view class="ifield ifield--half">
                        <text class="ifield__label">计划婚期</text>
                        <picker
                            mode="date"
                            :value="intentionForm.wedding_date || todayStr"
                            @change="onPickWeddingDate"
                        >
                            <view class="ifield__picker">
                                <text :class="{ 'is-empty': !intentionForm.wedding_date }">{{
                                    intentionForm.wedding_date || '选择日期'
                                }}</text>
                                <tn-icon name="calendar" :size="22" />
                            </view>
                        </picker>
                    </view>
                    <view class="ifield ifield--half">
                        <text class="ifield__label">办婚城市</text>
                        <input
                            v-model="intentionForm.city"
                            class="ifield__input"
                            placeholder="如：杭州市"
                            placeholder-class="ifield__ph"
                            maxlength="50"
                        />
                    </view>
                </view>

                <view class="ifield">
                    <text class="ifield__label">婚礼预算范围</text>
                    <view class="ichip-row">
                        <view
                            v-for="opt in BUDGET_OPTIONS"
                            :key="opt"
                            hover-class="is-pressed"
                            class="ichip"
                            :class="{ 'is-active': intentionForm.budget_range === opt }"
                            @tap="pickBudget(opt)"
                        >
                            <text class="ichip__text">{{ opt }}</text>
                        </view>
                    </view>
                </view>

                <view class="ifield">
                    <text class="ifield__label">意向服务（可多选）</text>
                    <view class="ichip-row">
                        <view
                            v-for="need in NEED_OPTIONS"
                            :key="need"
                            hover-class="is-pressed"
                            class="ichip"
                            :class="{
                                'is-active': intentionForm.service_needs.includes(need)
                            }"
                            @tap="toggleNeed(need)"
                        >
                            <text class="ichip__text">{{ need }}</text>
                        </view>
                    </view>
                </view>

                <view class="ifield">
                    <text class="ifield__label">补充想法（选填）</text>
                    <textarea
                        v-model="intentionForm.remark"
                        class="ifield__textarea"
                        placeholder="如：偏好户外草坪/中式仪式、希望重点沟通主持人风格等"
                        placeholder-class="ifield__ph"
                        maxlength="500"
                    />
                </view>

                <template #foot>
                    <view class="intent-foot">
                        <base-button
                            text="取消"
                            variant="ghost"
                            size="md"
                            class="intent-btn"
                            @click="intentionOpen = false"
                        />
                        <base-button
                            :text="intentionSubmitting ? '提交中…' : '提交预约回电'"
                            variant="ink"
                            size="md"
                            class="intent-btn"
                            :loading="intentionSubmitting"
                            :disabled="intentionSubmitting"
                            @click="submitIntentionForm"
                        />
                    </view>
                </template>
            </snap-sheet>

            <app-toast />
        </template>
    </page-shell>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import {
    findWidget,
    normalizeCustomerServiceContent,
    parseDecoratePage,
    type CustomerServiceDecorateContent
} from '@shared/contracts'
import { getDecorate } from '@/api/home'
import {
    resolveConsultAvatarUrl,
    resolveConsultOnline,
    startConsult,
    submitConsultIntention,
    type ConsultContact,
    type ConsultScene
} from '@/api/service'
import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'
import { resolveBookingContext } from '@/utils/booking-context'
import { navigate } from '@/utils/link'
import { report } from '@/platform/report'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { toast } from '@/composables/useToast'
import { haptic } from '@/utils/haptics'

const appStore = useAppStore()
const userStore = useUserStore()

const loading = ref(true)
const loaded = ref(false)
const avatarFailed = ref(false)
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))
const error = computed(() => !!rawError.value)

const decorate = ref<CustomerServiceDecorateContent>(normalizeCustomerServiceContent(null))
const entryType = ref<'advisor' | 'fallback'>('fallback')
const chatEnabled = ref(true)
const openFaq = ref(-1)
const modalRef = ref<any>(null)

const consultParams = reactive<{
    scene: ConsultScene
    staff_id?: number
    order_id?: number
    category_id?: number
}>({
    scene: 'home'
})

const BUDGET_OPTIONS = ['1万以内', '1-3万', '3-5万', '5-10万', '10万以上']
const NEED_OPTIONS = ['全案策划', '婚礼主持', '婚礼跟拍', '婚礼摄像', '新娘跟妆', '场地布置']

const intentionOpen = ref(false)
const intentionSubmitting = ref(false)
const intentionSubmitted = ref(false)
const syncedCustomerId = ref<number>(0)
const intentionForm = reactive({
    customer_name: '',
    customer_mobile: '',
    customer_wechat: '',
    wedding_date: '',
    city: '',
    district: '',
    budget_range: '',
    service_needs: [] as string[],
    remark: ''
})

function resetIntentionForm() {
    intentionForm.customer_name = ''
    intentionForm.customer_mobile = ''
    intentionForm.customer_wechat = ''
    intentionForm.wedding_date = ''
    intentionForm.city = ''
    intentionForm.district = ''
    intentionForm.budget_range = ''
    intentionForm.service_needs = []
    intentionForm.remark = ''
}

const todayStr = computed(() => {
    const d = new Date()
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
})

const contact = reactive<ConsultContact>({
    name: '',
    role: '',
    mobile: '',
    avatar: '',
    service_time: '',
    tips: '',
    specialties: [],
    areas: []
})

const advisorTags = computed(() => {
    const tags: string[] = []
    ;(contact.specialties || []).forEach((s) => {
        if (s && !tags.includes(s)) tags.push(s)
    })
    ;(contact.areas || []).forEach((a) => {
        if (a && !tags.includes(a)) tags.push(a)
    })
    return tags.slice(0, 4)
})

const intentionBarDesc = computed(() => {
    if (!intentionSubmitted.value) {
        return entryType.value === 'advisor'
            ? `填写婚期与预算，${contact.name || '专属顾问'}将按需回电`
            : '留下婚期与联系方式，专属婚礼顾问尽快与您对接'
    }
    const parts: string[] = []
    if (intentionForm.wedding_date) parts.push(`婚期 ${intentionForm.wedding_date}`)
    if (intentionForm.city) parts.push(intentionForm.city)
    if (intentionForm.budget_range) parts.push(intentionForm.budget_range)
    if (parts.length) return parts.join(' · ')
    return '已提交预约回电，顾问将尽快联系您'
})

const meta = reactive<{ online?: number; reply_avg_minutes?: number }>({})

const avatarUrl = computed(() => resolveConsultAvatarUrl(contact.avatar, appStore.config?.domain))
const roleBadge = computed(
    () => contact.role.trim() || (entryType.value === 'advisor' ? '专属客服管家' : '平台客服')
)
const surname = computed(() => (contact.name || '客').trim().slice(0, 1))
const consultDesc = computed(() => contact.tips.trim() || decorate.value.tips)
const visibleAssurances = computed(() =>
    decorate.value.show_assurance === 1
        ? decorate.value.assurances.filter((item) => item.title.trim())
        : []
)
const visibleFaqs = computed(() =>
    decorate.value.show_faq === 1
        ? decorate.value.faqs.filter((item) => item.q.trim() && item.a.trim())
        : []
)
const replyMinutes = computed(() =>
    typeof meta.reply_avg_minutes === 'number' && meta.reply_avg_minutes > 0
        ? meta.reply_avg_minutes
        : 0
)

const online = computed<boolean | null>(() =>
    resolveConsultOnline(contact.service_time, meta.online)
)

const statusPillText = computed(() => {
    if (online.value === true) return '在线服务中'
    if (online.value === false) return '非服务时段'
    return '礼宾服务'
})

const serviceHoursText = computed(() => {
    const base = (contact.service_time || '').trim() || '服务时段以客服告知为准'
    return replyMinutes.value > 0 ? `${base} · 平均 ${replyMinutes.value} 分钟回复` : base
})

const stateText = computed(() => {
    const time = (contact.service_time || '').trim()
    if (online.value === null) return time ? `服务时段 ${time}` : '服务时段以客服告知为准'
    if (online.value) return time ? `在线 · ${time}` : '在线'
    return time ? `现在不在服务时段 · ${time}` : '现在不在服务时段'
})

async function load(silent = false) {
    if (!silent || !loaded.value) {
        loading.value = true
    }
    rawError.value = null
    try {
        const ctx = await resolveBookingContext().catch(() => null)
        const [res, dec] = await Promise.all([
            startConsult({
                scene: consultParams.scene,
                staff_id: consultParams.staff_id,
                order_id: consultParams.order_id,
                category_id: consultParams.category_id,
                wedding_date: ctx?.date || undefined,
                city: ctx?.region?.city_name || undefined,
                district: ctx?.region?.district_name || undefined
            }),
            getDecorate(3).catch(() => null)
        ])
        if (dec) {
            const parsed = parseDecoratePage(dec.data, dec.meta)
            const widget = findWidget(parsed, 'customer-service')
            decorate.value = normalizeCustomerServiceContent(widget)
            if (openFaq.value >= visibleFaqs.value.length) {
                openFaq.value = -1
            }
        }
        if (res) {
            entryType.value = res.entry_type || 'fallback'
            chatEnabled.value = res.customer_service_chat?.enabled !== false
            avatarFailed.value = false
            Object.assign(
                contact,
                {
                    name: '',
                    role: '',
                    mobile: '',
                    avatar: '',
                    service_time: '',
                    tips: '',
                    specialties: [],
                    areas: []
                },
                res.contact || {}
            )
            const c = res.contact || ({} as Partial<ConsultContact>)
            meta.online = typeof c.online === 'number' ? c.online : undefined
            meta.reply_avg_minutes =
                typeof c.reply_avg_minutes === 'number' ? c.reply_avg_minutes : undefined

            const nextCustomerId = Number(res.customer_id || 0)
            if (nextCustomerId !== syncedCustomerId.value) {
                resetIntentionForm()
                syncedCustomerId.value = nextCustomerId
            }

            const profile = res.customer_profile
            intentionSubmitted.value = Boolean(profile?.intention_submitted)
            if (profile) {
                if (!intentionForm.customer_name && profile.customer_name) {
                    intentionForm.customer_name = profile.customer_name
                }
                if (!intentionForm.customer_mobile && profile.customer_mobile) {
                    intentionForm.customer_mobile = profile.customer_mobile
                }
                if (!intentionForm.customer_wechat && profile.customer_wechat) {
                    intentionForm.customer_wechat = profile.customer_wechat
                }
                if (!intentionForm.wedding_date && (profile.wedding_date || ctx?.date)) {
                    intentionForm.wedding_date = profile.wedding_date || ctx?.date || ''
                }
                if (!intentionForm.city && (profile.city || ctx?.region?.city_name)) {
                    intentionForm.city = profile.city || ctx?.region?.city_name || ''
                }
                if (!intentionForm.district && (profile.district || ctx?.region?.district_name)) {
                    intentionForm.district = profile.district || ctx?.region?.district_name || ''
                }
                if (!intentionForm.budget_range && profile.budget_range) {
                    intentionForm.budget_range = profile.budget_range
                }
                if (!intentionForm.service_needs.length && Array.isArray(profile.service_needs)) {
                    intentionForm.service_needs = [...profile.service_needs]
                }
                if (!intentionForm.remark && profile.remark) {
                    intentionForm.remark = profile.remark
                }
            } else if (ctx) {
                if (!intentionForm.wedding_date && ctx.date) intentionForm.wedding_date = ctx.date
                if (!intentionForm.city && ctx.region?.city_name) intentionForm.city = ctx.region.city_name
                if (!intentionForm.district && ctx.region?.district_name) {
                    intentionForm.district = ctx.region.district_name
                }
            }
        }
        loaded.value = true
    } catch (e) {
        if (!loaded.value) {
            rawError.value = e
        }
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
        goLogin()
        return
    }
    load(false)
}

function toggleFaq(i: number) {
    haptic('light', 'cs-faq')
    openFaq.value = openFaq.value === i ? -1 : i
}

function goLogin() {
    navigate({ path: '/pages/login/login' })
}

function goOrders() {
    haptic('light', 'cs-quick')
    navigate({ path: '/pages/order/order' })
}

function goAftersale() {
    haptic('light', 'cs-quick')
    const pages = getCurrentPages()
    const prevRoute = pages.length >= 2 ? String((pages[pages.length - 2] as any)?.route || '') : ''
    if (prevRoute === 'packages/pkg-service/pages/aftersale/index') {
        uni.navigateBack()
        return
    }
    navigate({ path: '/packages/pkg-service/pages/aftersale/index' })
}

function onFallbackChat() {
    haptic('light', 'cs-chat')
    if (contact.mobile) {
        askCall()
        return
    }
    toast.info(chatEnabled.value ? '请在微信小程序内发起在线客服' : '在线客服暂不可用，请稍后再试')
}

function copyMobile() {
    if (!contact.mobile) return
    haptic('light', 'cs-copy')
    uni.setClipboardData({
        data: contact.mobile,
        success: () => toast.success('号码已复制'),
        fail: () => toast.error('复制失败')
    })
}

function askCall() {
    haptic('light', 'cs-call')
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

function openIntentionSheet() {
    if (!userStore.isLogin) {
        toast.info('登录后即可预约专属顾问回电')
        goLogin()
        return
    }
    haptic('light', 'cs-intent')
    intentionOpen.value = true
}

function pickBudget(opt: string) {
    haptic('light', 'cs-chip')
    intentionForm.budget_range = intentionForm.budget_range === opt ? '' : opt
}

function onPickWeddingDate(e: any) {
    const val = String(e?.detail?.value || '').trim().slice(0, 10)
    if (val) intentionForm.wedding_date = val
}

function toggleNeed(need: string) {
    haptic('light', 'cs-chip')
    const idx = intentionForm.service_needs.indexOf(need)
    if (idx >= 0) {
        intentionForm.service_needs.splice(idx, 1)
    } else {
        intentionForm.service_needs.push(need)
    }
}

async function submitIntentionForm() {
    if (intentionSubmitting.value) return
    const mobile = intentionForm.customer_mobile.trim()
    if (!/^1[3-9]\d{9}$/.test(mobile)) {
        toast.warning('请填写正确的 11 位联系手机号')
        return
    }
    intentionSubmitting.value = true
    try {
        const res = await submitConsultIntention({
            customer_name: intentionForm.customer_name.trim(),
            customer_mobile: mobile,
            customer_wechat: intentionForm.customer_wechat.trim(),
            wedding_date: intentionForm.wedding_date || undefined,
            city: intentionForm.city.trim(),
            district: intentionForm.district.trim(),
            budget_range: intentionForm.budget_range,
            service_needs: intentionForm.service_needs,
            remark: intentionForm.remark.trim()
        })
        intentionSubmitted.value = true
        intentionOpen.value = false
        toast.success(
            res?.advisor_name
                ? `已预约 ${res.advisor_name} 回电，请保持电话畅通`
                : '需求已提交，顾问将尽快联系您'
        )
        void load(true)
    } catch (e: any) {
        toast.error(String(e?.message || '提交失败，请稍后重试'))
    } finally {
        intentionSubmitting.value = false
    }
}

function normalizeConsultScene(raw: string): ConsultScene {
    if (raw === 'staff' || raw === 'staff_detail') return 'staff_detail'
    if (raw === 'order' || raw === 'order_detail') return 'order_detail'
    if (raw === 'aftersale') return 'aftersale'
    if (raw === 'package_detail') return 'package_detail'
    return 'home'
}

onLoad((options?: Record<string, any>) => {
    const sceneRaw = String(options?.scene || 'home').trim()
    const staffId = Number(options?.staff_id || options?.staffId || 0)
    const orderId = Number(options?.order_id || options?.orderId || 0)
    const categoryId = Number(options?.category_id || options?.categoryId || 0)
    consultParams.scene = normalizeConsultScene(sceneRaw)
    consultParams.staff_id = staffId > 0 ? staffId : undefined
    consultParams.order_id = orderId > 0 ? orderId : undefined
    consultParams.category_id = categoryId > 0 ? categoryId : undefined
})

onShow(() => load(loaded.value))
</script>

<style lang="scss" scoped>
.pad {
    padding: 0 42rpx;
}
.sk {
    padding-top: var(--sp-3);
}
.cs {
    padding: 0 42rpx calc(var(--sp-5) + var(--safe-bottom));
}

/* ============ 统一深色礼宾名片 ============ */
.concierge {
    position: relative;
    margin-top: var(--sp-2);
    border-radius: var(--r-lg);
    padding: 34rpx 32rpx 30rpx;
    overflow: hidden;
    background: linear-gradient(150deg, var(--c-dark-1) 0%, var(--c-dark-2) 100%);
    border: 1rpx solid var(--c-line-strong);
    box-shadow: var(--shadow-float);

    &__glow {
        position: absolute;
        top: -80rpx;
        right: -60rpx;
        width: 280rpx;
        height: 280rpx;
        border-radius: var(--r-pill);
        background: radial-gradient(circle, rgba(217, 190, 130, 0.14) 0%, rgba(217, 190, 130, 0) 70%);
        pointer-events: none;
    }

    &__bar {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__eyebrow {
        font-family: var(--font-serif);
        font-size: var(--fs-18);
        letter-spacing: 0.22em;
        color: var(--c-brand-soft);
        line-height: var(--lh-none);
    }

    &__who {
        position: relative;
        display: flex;
        align-items: center;
        gap: 24rpx;
        margin-top: 24rpx;
    }

    &__av {
        width: 96rpx;
        height: 96rpx;
        border-radius: var(--r-pill);
        overflow: hidden;
        flex: none;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: var(--font-serif);
        font-size: var(--fs-xl);
        color: var(--c-brand-soft);
        background: var(--c-dark-3);
        border: 1rpx solid rgba(217, 190, 130, 0.45);
    }

    &__av-img {
        width: 100%;
        height: 100%;
    }

    &__body {
        flex: 1;
        min-width: 0;
    }

    &__n {
        display: flex;
        align-items: center;
        gap: 12rpx;
        flex-wrap: wrap;
    }

    &__name {
        font-size: var(--fs-34);
        font-weight: var(--fw-semibold);
        letter-spacing: 0.02em;
        line-height: var(--lh-tight);
        color: var(--c-on-dark);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    &__badge {
        flex: none;
        padding: 4rpx 14rpx;
        border-radius: var(--r-pill);
        font-size: var(--fs-20);
        line-height: var(--lh-tight);
        color: var(--c-brand-soft);
        background: var(--c-on-dark-fill-2);
        border: 1rpx solid var(--c-on-dark-fill-3);
    }

    &__hours {
        display: flex;
        align-items: center;
        gap: 8rpx;
        margin-top: 10rpx;
        color: var(--c-on-dark-2);
    }

    &__hours-text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-22);
        line-height: var(--lh-tight);
        color: var(--c-on-dark-2);
        overflow-wrap: anywhere;
    }

    &__tips {
        position: relative;
        display: block;
        margin-top: 22rpx;
        font-size: var(--fs-22);
        line-height: var(--lh-body);
        color: var(--c-on-dark-2);
        overflow-wrap: anywhere;
    }

    &__tags {
        position: relative;
        display: flex;
        flex-wrap: wrap;
        gap: 10rpx;
        margin-top: 16rpx;
    }

    &__tag {
        padding: 4rpx 14rpx;
        border-radius: var(--r-pill);
        font-size: var(--fs-20);
        color: var(--c-brand-soft);
        background: var(--c-on-dark-fill-2);
        border: 1rpx solid var(--c-on-dark-fill-3);
    }

    &__acts {
        position: relative;
        display: flex;
        align-items: center;
        gap: 16rpx;
        margin-top: 26rpx;
    }

    &__meta {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
        margin-top: 24rpx;
        padding-top: 20rpx;
        border-top: 1rpx solid var(--c-on-dark-fill-3);
    }

    &__meta-text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-22);
        color: var(--c-on-dark-2);
    }

    &__meta-action {
        flex: none;
        display: inline-flex;
        align-items: center;
        gap: 6rpx;
        color: var(--c-brand-soft);
    }

    &__meta-cta {
        font-size: var(--fs-22);
        font-weight: var(--fw-medium);
        color: var(--c-brand-soft);
    }
}

.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    padding: 6rpx 16rpx;
    border-radius: var(--r-pill);
    background: var(--c-on-dark-fill-2);
    border: 1rpx solid var(--c-on-dark-fill-3);

    &.is-live {
        background: rgba(169, 192, 172, 0.14);
        border-color: rgba(169, 192, 172, 0.3);
    }

    &__dot {
        width: 10rpx;
        height: 10rpx;
        border-radius: var(--r-pill);
        background: var(--c-success-on-dark);
        flex: none;

        &.is-off {
            background: var(--c-ink-3);
        }
    }

    &__text {
        font-size: var(--fs-20);
        line-height: var(--lh-none);
        color: var(--c-on-dark-2);
    }
}

.cbtn {
    flex: 1;
    min-width: 0;
    height: 88rpx;
    border-radius: var(--r-pill);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10rpx;
    box-sizing: border-box;

    &--btn {
        margin: 0;
        padding: 0;
        border: 0;
        line-height: var(--lh-none);
        font-size: inherit;

        &::after {
            border: 0;
        }
    }

    &--ivory {
        background: var(--c-hero-cta-bg);
        color: var(--c-on-brand);
    }

    &--glass {
        background: var(--c-on-dark-fill-1);
        border: 1rpx solid var(--c-on-dark-fill-3);
        color: var(--c-on-dark);
    }

    &__text {
        font-size: var(--fs-md);
        letter-spacing: 0.03em;

        &--ivory {
            color: var(--c-on-brand);
            font-weight: var(--fw-semibold);
        }

        &--glass {
            color: var(--c-on-dark);
            font-weight: var(--fw-medium);
        }
    }
}

/* ============ 紧凑自助双联条 ============ */
.quick-bar {
    display: flex;
    align-items: stretch;
    margin-top: 24rpx;
    border-radius: var(--r-md);
    background: var(--c-surface);
    border: 1rpx solid var(--c-card-border);
    box-shadow: var(--shadow-card);
    overflow: hidden;

    &__cell {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 16rpx;
        padding: 26rpx 24rpx;

        &--left {
            border-right: 1rpx solid var(--c-line);
        }
    }

    &__ic {
        flex: none;
        width: 52rpx;
        height: 52rpx;
        border-radius: var(--r-xs);
        background: var(--c-brand-bg);
        color: var(--c-brand-text);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__body {
        flex: 1;
        min-width: 0;
    }

    &__title {
        display: block;
        font-size: var(--fs-26);
        font-weight: var(--fw-medium);
        line-height: var(--lh-tight);
        color: var(--c-ink);
    }

    &__desc {
        display: block;
        margin-top: 4rpx;
        font-size: var(--fs-20);
        line-height: var(--lh-tight);
        color: var(--c-ink-3);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    &__go {
        flex: none;
        color: var(--c-ink-3);
    }
}

/* ============ 轻量页面章节头 ============ */
.sec {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: var(--sp-2);
    margin: 40rpx 4rpx 16rpx;

    &__title {
        font-size: var(--fs-26);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
        color: var(--c-ink);
    }

    &__sub {
        font-size: var(--fs-20);
        color: var(--c-ink-3);
    }
}

/* ============ 服务保障（2×2 细线格） ============ */
.assurance-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    border-radius: var(--r-md);
    background: var(--c-surface);
    border: 1rpx solid var(--c-card-border);
    box-shadow: var(--shadow-card);
    overflow: hidden;
}

.assurance-cell {
    padding: 26rpx 24rpx;
    min-width: 0;

    &:nth-child(odd) {
        border-right: 1rpx solid var(--c-line);
    }

    &:nth-child(n + 3) {
        border-top: 1rpx solid var(--c-line);
    }

    &__head {
        display: flex;
        align-items: center;
        gap: 12rpx;
    }

    &__ic {
        flex: none;
        width: 40rpx;
        height: 40rpx;
        border-radius: var(--r-xs);
        background: var(--c-brand-bg);
        color: var(--c-brand-text);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__title {
        font-size: var(--fs-26);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
        color: var(--c-ink);
    }

    &__desc {
        display: block;
        margin-top: 10rpx;
        font-size: var(--fs-22);
        line-height: var(--lh-tight);
        color: var(--c-ink-3);
    }
}

/* ============ 常见问题（序列手风琴） ============ */
.faq-card {
    padding: 0 32rpx;
    border-radius: var(--r-md);
    background: var(--c-surface);
    border: 1rpx solid var(--c-card-border);
    box-shadow: var(--shadow-card);
}

.faq-row {
    border-bottom: 1rpx solid var(--c-line);

    &:last-child {
        border-bottom: 0;
    }

    &__head {
        display: flex;
        align-items: center;
        gap: 12rpx;
        min-height: 92rpx;
        padding: 18rpx 0;
        box-sizing: border-box;
    }

    &__idx {
        flex: none;
        width: 34rpx;
        font-family: var(--font-serif);
        font-size: var(--fs-22);
        color: var(--c-brand-text);
    }

    &__q {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-26);
        line-height: var(--lh-body);
        color: var(--c-ink);
        overflow-wrap: anywhere;
    }

    &__arrow {
        flex: none;
        color: var(--c-ink-3);
    }

    &__a {
        display: block;
        padding: 0 0 26rpx 46rpx;
        font-size: var(--fs-xs);
        line-height: var(--lh-loose);
        color: var(--c-ink-2);
        overflow-wrap: anywhere;
    }
}

.foot {
    display: block;
    margin-top: 40rpx;
    text-align: center;
    font-size: var(--fs-20);
    line-height: var(--lh-body);
    color: var(--c-ink-3);
}

/* ============ 预约顾问回电卡片 & 抽屉 ============ */
.intention-bar {
    display: flex;
    align-items: center;
    gap: 18rpx;
    margin-top: 24rpx;
    padding: 24rpx 26rpx;
    border-radius: var(--r-md);
    background: var(--c-surface);
    border: 1rpx solid var(--c-brand-soft);
    box-shadow: var(--shadow-card);

    &.is-done {
        border-color: var(--c-line);
    }

    &__ic {
        flex: none;
        width: 56rpx;
        height: 56rpx;
        border-radius: var(--r-sm);
        background: var(--c-brand-bg);
        color: var(--c-brand-text);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__body {
        flex: 1;
        min-width: 0;
    }

    &__title {
        display: block;
        font-size: var(--fs-26);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        line-height: var(--lh-tight);
    }

    &__desc {
        display: block;
        margin-top: 6rpx;
        font-size: var(--fs-20);
        color: var(--c-ink-3);
        line-height: var(--lh-tight);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    &__cta {
        flex: none;
        display: flex;
        align-items: center;
        gap: 4rpx;
        color: var(--c-brand-text);
    }

    &__cta-text {
        font-size: var(--fs-22);
        font-weight: var(--fw-medium);
        color: var(--c-brand-text);
    }
}

/* 抽屉内容直接挂在 snap-sheet 的 body / foot 插槽内（已无 .intent-sheet 包裹节点） */
.intent-sub {
    display: block;
    margin-bottom: var(--sp-4);
    font-size: var(--fs-22);
    line-height: var(--lh-body);
    color: var(--c-ink-3);
}

.intent-foot {
    display: flex;
    gap: 20rpx;
}

.intent-btn {
    flex: 1;
    min-width: 0;
}

.ifield-row {
    display: flex;
    gap: 16rpx;
}

.ifield {
    margin-bottom: 22rpx;

    &--half {
        flex: 1;
        min-width: 0;
    }

    &__label {
        display: block;
        margin-bottom: 10rpx;
        font-size: var(--fs-22);
        font-weight: var(--fw-medium);
        color: var(--c-ink-2);
    }

    /* 输入 / 选择控件：高 88rpx 满足触控下限，边界用可辨识的 --c-control-border */
    &__input,
    &__picker {
        height: 88rpx;
        padding: 0 22rpx;
        border-radius: var(--r-sm);
        background: var(--c-surface-2);
        border: 1rpx solid var(--c-control-border);
        font-size: var(--fs-26);
        color: var(--c-ink);
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-sizing: border-box;
    }

    &__picker .is-empty {
        color: var(--c-ink-3);
    }

    &__textarea {
        width: 100%;
        min-height: 132rpx;
        padding: 18rpx 22rpx;
        border-radius: var(--r-sm);
        background: var(--c-surface-2);
        border: 1rpx solid var(--c-control-border);
        font-size: var(--fs-md);
        color: var(--c-ink);
        box-sizing: border-box;
    }

    &__ph {
        color: var(--c-ink-3);
    }
}

.ichip-row {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
}

.ichip {
    position: relative;
    display: inline-flex;
    align-items: center;
    min-height: 64rpx;
    padding: 0 22rpx;
    border-radius: var(--r-pill);
    background: var(--c-surface-2);
    border: 1rpx solid var(--c-control-border);
    box-sizing: border-box;

    /* 视觉 64rpx，纵向热区扩到 88rpx（上下各 12rpx），与 12rpx 行间距正好对接 */
    &::before {
        content: '';
        position: absolute;
        top: -12rpx;
        bottom: -12rpx;
        left: 0;
        right: 0;
    }

    &__text {
        font-size: var(--fs-22);
        color: var(--c-ink-2);
    }

    &.is-active {
        background: var(--c-brand-bg);
        border-color: var(--c-brand-text);

        .ichip__text {
            color: var(--c-brand-text);
            font-weight: var(--fw-medium);
        }
    }
}
</style>
