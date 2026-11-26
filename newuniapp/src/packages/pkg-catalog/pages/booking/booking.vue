<template>
    <!-- 沉浸式导航只在「顶部真有图承接」时成立：
         步骤页有 .bhero 大图（配 0.56 深色 scrim），浅色返回键才看得清，滚过主图再切实底；
         loading 的 .sk__hero 是米色块、无图时是 .bhero__blank，白图标压上去会糊成一片，
         所以 navLight 额外要求 heroImage 已就位（见 script 里的 computed）。 -->
    <page-shell
        bg="page"
        :bottom-bar="true"
        :title="isConfirm ? '确认订单' : ''"
        :nav-variant="!isConfirm && !error ? 'scroll-solid' : 'solid'"
        :nav-tone="navLight ? 'light' : 'dark'"
        nav-background="page"
        :scroll-top="navScrollTop"
        :nav-solid-threshold="navSolidThreshold"
        :nav-capsule-avoid="true"
        nav-back-mode="none"
        @nav-back="onBack"
    >
        <!-- 首屏骨架：hero 米色块 + 卡片骨架 + 底栏金额占位 -->
        <view v-if="loading" class="sk">
            <view class="sk__hero">
                <base-skeleton :rows="2" />
            </view>
            <view class="sk__body">
                <base-skeleton :rows="3" />
            </view>
        </view>

        <!-- 缺参数（business，不可重试）/ 接口失败（可重试） -->
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

        <template v-else>
            <!-- 步骤页统一头图：放大到 860rpx，只保留「日期/地区入口 + 标题 + 价格」三层信息 -->
            <view v-if="!isConfirm" class="bhero">
                <image
                    v-if="heroImage"
                    class="bhero__img"
                    :src="imageUrl(heroImage)"
                    mode="aspectFill"
                />
                <view v-else class="bhero__blank" />
                <view class="bhero__scrim" aria-hidden="true" />

                <!-- 日期 + 地区胶囊：同一点击入口，弹层里一次选完 -->
                <view
                    class="bhero__date"
                    :class="{ 'is-empty': !date || !regionReady }"
                    hover-class="is-pressed"
                    aria-role="button"
                    aria-label="选择服务日期与地区"
                    @tap="openDatePicker"
                >
                    <tn-icon name="calendar" :size="24" />
                    <text class="bhero__date-text">{{ dateCapsuleText }}</text>
                    <tn-icon name="down" :size="20" />
                </view>

                <view class="bhero__info">
                    <text class="bhero__eyebrow">{{ heroEyebrow }}</text>
                    <text class="bhero__name ellipsis">{{ heroTitle }}</text>
                    <view v-if="heroPrice != null" class="bhero__price">
                        <price size="lg" :content="heroPrice" color="var(--c-brand-soft)" />
                        <text class="bhero__unit">{{ heroUnit }}</text>
                    </view>
                </view>
            </view>

            <!-- 步骤条：2–4 段由后台「预约关联」开关决定，确认页不显示 -->
            <view v-if="!isConfirm" class="steps-wrap">
                <steps :steps="flowLabels" :current="step - 1" />
            </view>

            <!-- 日期/地区缺失：每一步都顶一条，点了直接开弹层，不允许带着空条件往下走 -->
            <view
                v-if="!isConfirm && (!date || !regionReady)"
                class="needbar"
                hover-class="is-pressed"
                aria-role="button"
                @tap="openDatePicker"
            >
                <tn-icon name="warning" :size="26" class="needbar__icon" />
                <text class="needbar__text">{{ needBarText }}</text>
                <text class="needbar__action">去选择</text>
            </view>

            <!-- ============ 06 选择套餐 ============ -->
            <view v-if="step === 1" class="panel">
                <view class="stephead">
                    <text class="stephead__title">选择服务套餐</text>
                    <text class="stephead__hint">左右滑动查看，价格按所选地区计算</text>
                </view>

                <scroll-view v-if="packages.length" scroll-x class="opts" :show-scrollbar="false">
                    <view class="opts__track">
                        <view
                            v-for="p in packages"
                            :key="pkgId(p)"
                            class="optcard"
                            :class="{ 'is-on': Number(packageId) === Number(pkgId(p)) }"
                            hover-class="is-pressed"
                            aria-role="radio"
                            :aria-checked="Number(packageId) === Number(pkgId(p))"
                            @tap="selectPackage(p)"
                        >
                            <view class="optcard__head">
                                <text class="optcard__name ellipsis-2">{{ pkgName(p) }}</text>
                                <view class="optcard__tick" aria-hidden="true">
                                    <tn-icon name="check" :size="18" />
                                </view>
                            </view>
                            <view class="optcard__price">
                                <price size="sm" :content="pkgPrice(p)" />
                                <text class="optcard__unit">/ 场</text>
                            </view>
                        </view>
                    </view>
                </scroll-view>
                <base-empty
                    v-else
                    status="empty"
                    empty-text="这档服务暂时不可选"
                    desc="换一位主持人，或过两天再来"
                    action-text="返回详情"
                    show-action
                    @action="onBack"
                />

                <view v-if="packageRules" class="bkline">
                    <text class="bkline__sum">{{ packageRules }}</text>
                </view>
            </view>

            <!-- ============ 07 选择附加项 ============ -->
            <view v-else-if="step === 2" class="panel">
                <view class="stephead">
                    <text class="stephead__title">选择附加项</text>
                    <text class="stephead__hint">可多选，左右滑动查看；不需要可直接跳过</text>
                </view>

                <template v-if="addons.length">
                    <scroll-view scroll-x class="opts" :show-scrollbar="false">
                        <view class="opts__track">
                            <view
                                v-for="a in addons"
                                :key="addonId(a)"
                                class="optcard"
                                :class="{ 'is-on': selectedAddons.includes(addonId(a)) }"
                                hover-class="is-pressed"
                                aria-role="checkbox"
                                :aria-checked="selectedAddons.includes(addonId(a))"
                                @tap="toggleAddon(addonId(a))"
                            >
                                <view class="optcard__head">
                                    <text class="optcard__name ellipsis-2">{{ a.name }}</text>
                                    <view class="optcard__tick" aria-hidden="true">
                                        <tn-icon name="check" :size="18" />
                                    </view>
                                </view>
                                <view class="optcard__price">
                                    <price
                                        size="sm"
                                        :content="addonPrice(a)"
                                        prefix="+¥"
                                        :trim-zeros="true"
                                    />
                                </view>
                            </view>
                        </view>
                    </scroll-view>

                    <view class="bkline">
                        <text class="bkline__sum">
                            已选
                            <text class="bkline__strong">{{ selectedAddons.length }}</text> 项
                            <text class="bkline__strong"> {{ addonSumText }}</text>
                        </text>
                        <view
                            class="bkline__skip"
                            hover-class="is-pressed"
                            aria-role="button"
                            @tap="skipAddons"
                        >
                            <text class="bkline__skip-text">不需要，直接下一步</text>
                            <tn-icon name="right" :size="20" />
                        </view>
                    </view>
                </template>

                <base-empty
                    v-else
                    status="empty"
                    empty-text="这档套餐已含全部附加服务"
                    desc="直接进入下一步即可"
                    :action-text="nextButtonText"
                    show-action
                    @action="nextStep"
                />
            </view>

            <!-- ============ 可选服务：婚礼管家 / 婚礼督导 ============ -->
            <booking-role-step
                v-else-if="activeRole"
                :role="activeRole"
                :needed="activeRoleNeeded"
                :picked="activeRolePicked"
                :candidates="activeRoleCandidates"
                :loading="activeRoleLoading"
                :price-text="activeRolePriceText"
                :foot-text="activeRoleFootText"
                :image-url="imageUrl"
                @set-needed="setActiveRoleNeeded"
                @pick="pickRoleCandidate"
                @open-detail="openRolePersonDetail"
            />

            <!-- ============ 08 订单确认 ============ -->
            <booking-confirm-step
                v-else
                ref="confirmStepRef"
                :date-day="dateDay"
                :date-year-week="dateYearWeek"
                :order-badge="orderBadge"
                :current-package-name="currentPackageName"
                :staff-name="staffName"
                :staff-avatar="staffAvatar"
                :staff-meta-text="staffMetaText"
                :city-venue-text="cityVenueText"
                :lock-state="lockState"
                :lock-summary-text="lockSummaryText"
                :order-id="orderId"
                :order-pending-confirm="orderPendingConfirm"
                :package-price-text="packagePriceText"
                :package-duration-text="packageDurationText"
                :package-rules="packageRules"
                :quote-role-rows="quoteRoleRows"
                :addon-title="addonTitle"
                :selected-addon-list="selectedAddonList"
                :addon-sum-text="addonSumText"
                :upsell-addon="upsellAddon"
                :contact-line="contactLine"
                :contact-block-error="contactBlockError"
                :service-region-text="serviceRegionText"
                :service-address="form.service_address"
                :address-block-error="addressBlockError"
                :flash-target="flashTarget"
                :quote="quote"
                :quote-state="quoteState"
                :addon-only-amount="addonOnlyAmount"
                :role-price-sum="rolePriceSum"
                :amount-mismatch="amountMismatch"
                :is-full-payment="isFullPayment"
                :deposit-text="depositText"
                :balance-text="balanceText"
                :balance-due-text="balanceDueText"
                :remark="form.remark"
                :validation-errors="validationErrors"
                :validation-warns="validationWarns"
                :submit-error="submitError"
                :agreed="agreed"
                :agree-error="agreeError"
                :image-url="imageUrl"
                @open-date-picker="openDatePicker"
                @go-order-detail="goOrderDetail"
                @relock="relockSchedule"
                @edit-step="editStep"
                @add-upsell="addUpsell"
                @open-contact="openSheet('contact')"
                @open-address="openSheet('address')"
                @open-remark="openSheet('remark')"
                @retry-preview="runPreview"
                @update:agreed="onAgreedChange"
            />
        </template>

        <!-- 吸底操作栏：左侧金额 + 右侧主操作，随 step 变化 -->
        <template #bottom>
            <bottom-bar v-if="!loading && !error">
                <view class="abp">
                    <text class="abp__label">{{ barLabelView }}</text>
                    <view class="abp__row">
                        <view v-if="barAmountView != null" class="abp__amount">
                            <price size="lg" :content="barAmountView" :trim-zeros="false" />
                        </view>
                        <text v-else class="abp__na">—</text>
                        <text v-if="totalHintText" class="abp__hint">{{ totalHintText }}</text>
                    </view>
                </view>
                <template #actions>
                    <view class="abtns">
                        <base-button
                            v-if="step > 1 && !isConfirm"
                            text="上一步"
                            variant="ghost"
                            size="md"
                            :block="false"
                            @click="prevStep"
                        />
                        <base-button
                            v-if="!isConfirm"
                            :text="nextButtonText"
                            variant="ink"
                            size="md"
                            :block="false"
                            @click="nextStep"
                        />
                        <base-button
                            v-else
                            :text="submitButtonText"
                            variant="ink"
                            size="md"
                            :block="false"
                            :loading="submitting"
                            @click="onPrimaryTap"
                        />
                    </view>
                </template>
            </bottom-bar>
        </template>
    </page-shell>

    <booking-date-sheet
        v-model="datePickerVisible"
        :date="date"
        :region="region"
        :min-date="minSelectableDate"
        :max-date="maxSelectableDate"
        @confirm="onDatePickerConfirm"
    />

    <booking-edit-sheet
        v-model="sheetVisible"
        :mode="sheetMode"
        :form="form"
        :service-region-text="serviceRegionText"
        @switch-region="switchRegionFromSheet"
        @save="saveSheet"
    />

    <app-toast />
</template>

<script setup lang="ts">
/**
 * 06 选择套餐 / 07 选择附加项 / 08 订单确认（设计稿 2585–2855）。
 * 页面壳负责编排：步骤流转、头图/底栏展示、弹层调度，
 * 锁档状态机 / 角色选人 / 核价下单分别下沉到同目录 composables 与子组件。
 */
import { computed, nextTick, reactive, ref } from 'vue'
import { onLoad, onPageScroll, onShow, onUnload } from '@dcloudio/uni-app'
import { formatDate, parseDateValue } from '@shared/utils/format'
import { getScheduleRule, getStaffDetail } from '@/api/catalog'
import { getNavBarHeight } from '@/platform/device'
import { toast } from '@/composables/useToast'
import { FB } from '@/constants/feedback'
import { report } from '@/platform/report'
import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'
import { resolveBookingContext, saveBookingContext } from '@/utils/booking-context'
import { navigate } from '@/utils/link'
import { EMPTY_REGION, hasRegionContext, regionParams, type RegionContext } from '@/utils/region'
import { resolveErrorView } from '@/utils/resolveErrorView'
import {
    addonId,
    addonPrice,
    maskMobile,
    money,
    pkgId,
    pkgName,
    pkgPrice,
    validateBookingField,
    type BookingEditSheetSavePayload,
    type BookingFocusTarget,
    type BookingFormData,
    type BookingSheetMode,
    type BookingValidateField
} from './booking-helpers'
import BookingConfirmStep from './components/booking-confirm-step.vue'
import BookingDateSheet from './components/booking-date-sheet.vue'
import BookingEditSheet from './components/booking-edit-sheet.vue'
import BookingRoleStep from './components/booking-role-step.vue'
import { useBookingLock } from './useBookingLock'
import { useBookingOrder } from './useBookingOrder'
import { useBookingRoles } from './useBookingRoles'

const appStore = useAppStore()
const userStore = useUserStore()

const step = ref(1)
const loading = ref(true)
const error = ref(false)
const confirmStepRef = ref<InstanceType<typeof BookingConfirmStep> | null>(null)

const rawError = ref<unknown>(null)
const view = computed(() =>
    resolveErrorView(staffId.value ? (rawError.value as any) : new Error(FB.ERR_PARAM_MISSING), {
        hasForm: true,
        reason: staffId.value ? undefined : 'param-missing'
    })
)

const staffId = ref(0)
const date = ref('')
const region = ref<RegionContext>({ ...EMPTY_REGION })
const packages = ref<any[]>([])
const addons = ref<any[]>([])
const roleConfigs = ref<any[]>([])

const packageId = ref(0)
const selectedAddons = ref<number[]>([])

const staffName = ref('')
const staffAvatar = ref('')
const staffCover = ref('')
const staffRole = ref('')
const staffRating = ref(0)
const staffOrderCount = ref(0)
const city = ref('')

const agreed = ref(false)
const agreeError = ref('')
const orderId = ref(0)

const form = reactive<BookingFormData>({
    contact_name: '',
    contact_mobile: '',
    service_address: '',
    remark: ''
})
const errors = reactive<Record<BookingValidateField, string>>({
    contact_name: '',
    contact_mobile: '',
    service_address: ''
})
const formErrorVisible = ref(false)
const flashTarget = ref<'' | BookingFocusTarget>('')
let flashTimer: ReturnType<typeof setTimeout> | null = null

const datePickerVisible = ref(false)
const sheetVisible = ref(false)
const sheetMode = ref<BookingSheetMode>('contact')
const minSelectableDate = ref('')
const maxSelectableDate = ref('')

const {
    lockState,
    orderBadge,
    lockSummaryText,
    stopLockTimer,
    doLock,
    relockSchedule,
    releaseActiveLock,
    markOrderCreated
} = useBookingLock({ staffId, date, orderId })

const preConfirmCount = computed(
    () =>
        2 +
        roleConfigs.value.filter((r) => r?.role_key === 'butler' || r?.role_key === 'director')
            .length
)
const confirmStep = computed(() => preConfirmCount.value + 1)
const isConfirm = computed(() => step.value === confirmStep.value)

let invalidateQuoteRef: () => void = () => undefined

const {
    rolePicked,
    roleWanted,
    flowRoles,
    activeRole,
    activeRoleNeeded,
    activeRolePicked,
    activeRoleCandidates,
    activeRoleLoading,
    activeRolePriceText,
    activeRoleFootText,
    roleEstimateSum,
    setActiveRoleNeeded,
    pickRoleCandidate,
    openRolePersonDetail,
    consumePickedFromDetail,
    resetAllRoles,
    pruneUnavailableRoles
} = useBookingRoles({
    staffId,
    date,
    region,
    roleConfigs,
    step,
    isConfirm,
    onInvalidateQuote: () => invalidateQuoteRef()
})

function buildParams() {
    const params: Record<string, any> = {
        staff_id: staffId.value,
        package_id: packageId.value,
        ...regionParams(region.value)
    }
    if (date.value) params.date = date.value
    if (selectedAddons.value.length) params.addon_ids = [...selectedAddons.value]
    for (const key of Object.keys(rolePicked)) {
        const picked = rolePicked[key]
        if (!picked?.staff_id || !picked?.package_id) continue
        params[`${key}_staff_id`] = picked.staff_id
        params[`${key}_package_id`] = picked.package_id
    }
    return params
}

const {
    quote,
    quoteRoleItems,
    validationErrors,
    validationWarns,
    quoteState,
    submitError,
    orderPendingConfirm,
    submitting,
    balanceCents,
    amountMismatch,
    depositText,
    balanceText,
    isFullPayment,
    balanceDueText,
    invalidateQuote,
    runPreview,
    handleSubmit,
    goOrderDetail
} = useBookingOrder({
    staffId,
    date,
    form,
    orderId,
    lockState,
    agreed,
    agreeError,
    formErrorVisible,
    getBarAmount: () => barAmount.value,
    buildParams,
    validate,
    focusFirstError,
    relockSchedule,
    markOrderCreated
})
invalidateQuoteRef = invalidateQuote

/* ---------------- 展示与金额派生 ---------------- */

const flowLabels = computed(() => [
    '选套餐',
    '附加项',
    ...flowRoles.value.map((r) => String(r.role_label || ''))
])
const nextButtonText = computed(() => {
    if (step.value === 1) return '下一步 · 附加项'
    const nextRole = flowRoles.value[step.value - 2]
    return nextRole ? `下一步 · ${nextRole.role_label}` : '下一步 · 确认订单'
})

function imageUrl(url?: string) {
    if (!url) return ''
    if (/^https?:/.test(url)) return url
    const domain = (appStore.config && appStore.config.domain) || ''
    return domain ? `${domain}${url}` : url
}

const currentPackage = computed(() =>
    packages.value.find((p) => pkgId(p) === Number(packageId.value))
)
const currentPackageName = computed(() => (currentPackage.value ? pkgName(currentPackage.value) : ''))
const regionReady = computed(() => hasRegionContext(region.value))
const dateLabel = computed(() => {
    const d = parseDateValue(date.value)
    if (!d) return '选择日期'
    const w = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'][d.getDay()]
    return `${formatDate(d, 'MM.DD')} ${w}${city.value ? ` · ${city.value}` : ''}`
})
const dateCapsuleText = computed(() => {
    if (!date.value && !regionReady.value) return '选择日期与地区'
    if (!date.value) return `选择日期 · ${city.value || '已选地区'}`
    if (!regionReady.value) return `${dateLabel.value} · 选择地区`
    return dateLabel.value
})
const needBarText = computed(() => {
    if (!date.value && !regionReady.value) return '请先选择服务日期与地区，价格按地区计算'
    if (!date.value) return '请先选择服务日期，未选日期无法算价'
    return '请先选择服务地区，未选地区无法算价'
})
const dateDay = computed(() => (date.value ? formatDate(date.value, 'MM.DD', '待定') : '待定'))
const dateYearWeek = computed(() => {
    const d = parseDateValue(date.value)
    if (!d) return '日期待定'
    const w = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'][d.getDay()]
    return `${d.getFullYear()} ${w}`
})

const heroImage = computed(() => (activeRole.value ? activeRole.value.image || '' : staffCover.value))
const navLight = computed(
    () => !isConfirm.value && !error.value && !loading.value && !!heroImage.value
)

/**
 * 头图（.bhero 860rpx）一过顶就切实底导航：
 * 阈值 = 主图像素高 - 导航总高（状态栏 + 胶囊行），下限 40px 兜底。
 * scrollTop 只写 0 / 阈值+1 两档 —— 连续值会让 mp-weixin 每帧 setData，
 * 长列表滚动肉眼可见地掉帧。
 */
const navSolidThreshold = Math.max(40, Math.round(uni.upx2px(860) - getNavBarHeight()))
const navScrollTop = ref(0)
onPageScroll((e: any) => {
    const top = Number(e?.scrollTop || 0)
    const next = top > navSolidThreshold ? navSolidThreshold + 1 : 0
    if (navScrollTop.value !== next) navScrollTop.value = next
})

const heroEyebrow = computed(() => {
    const prefix = `STEP ${step.value} / ${preConfirmCount.value}`
    if (activeRole.value) return `${prefix} · 可选服务`
    return `${prefix} · ${step.value === 1 ? '服务套餐' : '附加项'}`
})
const heroTitle = computed(() => {
    if (isConfirm.value || step.value === 1) return currentPackageName.value || '服务套餐'
    if (activeRole.value) return activeRole.value.role_label || '可选服务'
    return selectedAddons.value.length ? `已选 ${selectedAddons.value.length} 项附加` : '附加项'
})
const selectedAddonList = computed(() =>
    addons.value.filter((a) => selectedAddons.value.includes(addonId(a)))
)
const upsellAddon = computed(
    () => addons.value.find((a) => !selectedAddons.value.includes(addonId(a))) || null
)
const addonSumText = computed(() => {
    const sum = selectedAddonList.value.reduce((acc, a) => acc + Number(addonPrice(a) || 0), 0)
    return sum > 0 ? `+¥${money(sum)}` : '+¥0'
})
const heroPrice = computed(() => {
    if (isConfirm.value || step.value === 1) {
        return currentPackage.value ? pkgPrice(currentPackage.value) : null
    }
    if (activeRole.value) {
        const price = Number(activeRole.value.default_price || 0)
        return price > 0 ? price : null
    }
    const sum = selectedAddonList.value.reduce((acc, a) => acc + Number(addonPrice(a) || 0), 0)
    return sum > 0 ? sum : null
})
const heroUnit = computed(() => {
    if (isConfirm.value || step.value === 1) return '/ 场'
    if (activeRole.value) return '/ 位'
    return '/ 项'
})

const rolePriceSum = computed(() => {
    const quoted = quoteRoleItems.value.reduce(
        (acc: number, i: any) => acc + Number(i?.price || 0),
        0
    )
    return quoted > 0 ? quoted : roleEstimateSum.value
})
const quoteRoleRows = computed(() =>
    flowRoles.value
        .filter((r) => !!rolePicked[r.role_key])
        .map((r) => {
            const picked = rolePicked[r.role_key]
            const quoted = quoteRoleItems.value.find(
                (i: any) => String(i?.item_meta?.role_key || '') === r.role_key
            )
            const name = quoted?.staff?.name || picked.name
            return {
                role_key: r.role_key,
                role_label: String(r.role_label || '关联服务'),
                text: `${name} · ¥${money(picked.price)}`
            }
        })
)
const addonOnlyAmount = computed(() =>
    Math.max(0, Number(quote.value?.addon_amount || 0) - rolePriceSum.value)
)

const packageRules = computed(() => {
    const p = currentPackage.value
    const rules = p?.rules || p?.package?.rules || p?.duration_desc || ''
    return rules ? String(rules) : ''
})
const packageDurationText = computed(() => {
    const p = currentPackage.value
    const text = p?.duration_desc || p?.package?.duration_desc || ''
    return text ? String(text) : ''
})
const packagePriceText = computed(() => {
    const quoted = Number(quote.value?.service_amount || 0)
    const price =
        quoted > 0 ? quoted : Number(currentPackage.value ? pkgPrice(currentPackage.value) : 0)
    return price > 0 ? `¥${money(price)}` : ''
})
const staffMetaText = computed(() => {
    const parts: string[] = []
    if (staffRole.value) parts.push(staffRole.value)
    if (staffRating.value > 0) parts.push(`${staffRating.value} 分`)
    if (staffOrderCount.value > 0) parts.push(`${staffOrderCount.value} 单`)
    return parts.join(' · ')
})
const addonTitle = computed(() =>
    selectedAddonList.value.length ? `附加项 · ${selectedAddonList.value.length} 项` : '附加项'
)
const contactLine = computed(() => {
    const name = String(form.contact_name || '').trim()
    const mobile = String(form.contact_mobile || '').trim()
    if (!name || !mobile) return ''
    return `${name} · ${maskMobile(mobile)}`
})
const contactBlockError = computed(() => {
    if (!formErrorVisible.value) return ''
    const nameErr = errors.contact_name
    const mobileErr = errors.contact_mobile
    if (nameErr && mobileErr) return '请完善联系人姓名和手机号'
    return nameErr || mobileErr || ''
})
const addressBlockError = computed(() =>
    formErrorVisible.value && errors.service_address ? '请填写详细地址' : ''
)
const serviceRegionText = computed(() => {
    const r = region.value
    const text = [r.province_name, r.city_name, r.district_name].filter(Boolean).join(' · ')
    return text || city.value
})
const cityVenueText = computed(() => {
    const addr = String(form.service_address || '').trim()
    const area = serviceRegionText.value
    if (area && addr) return `${area} · ${addr}`
    return addr || area || '场地待确认'
})

const barAmount = computed<number | string | null>(() => {
    if (step.value === 1) return currentPackage.value ? pkgPrice(currentPackage.value) : null
    if (!isConfirm.value) {
        const base = Number(currentPackage.value ? pkgPrice(currentPackage.value) : 0)
        const add = selectedAddonList.value.reduce((acc, a) => acc + Number(addonPrice(a) || 0), 0)
        return base + add + roleEstimateSum.value
    }
    const dep = quote.value?.deposit_amount
    if (dep !== undefined && dep !== null && Number(dep) > 0) return dep
    const t = quote.value?.total_amount
    return t === undefined || t === null || t === '' ? null : t
})
const barLabel = computed(() => {
    if (step.value === 1) return '当前套餐'
    if (!isConfirm.value) return '当前合计'
    if (!(balanceCents.value && balanceCents.value > 0)) return '订单总额'
    return quote.value?.deposit_amount && Number(quote.value.deposit_amount) > 0
        ? '确认后定金'
        : '订单总额'
})
const barAmountView = computed(() =>
    isConfirm.value && (lockState.value === 'failed' || orderPendingConfirm.value)
        ? null
        : barAmount.value
)
const barLabelView = computed(() => {
    if (!isConfirm.value) return barLabel.value
    if (orderPendingConfirm.value) return '待确认'
    if (lockState.value === 'failed') return '档期已被占用'
    if (lockState.value === 'expired') return '档期已释放'
    return barLabel.value
})
const totalHintText = computed(() => {
    if (!isConfirm.value || quoteState.value !== 'ready' || orderPendingConfirm.value) return ''
    const total = Number(quote.value?.total_amount || 0)
    const pay = Number(barAmount.value || 0)
    if (!(total > 0) || Math.abs(total - pay) < 0.01) return ''
    return `合计 ¥${money(total)}`
})
const submitButtonText = computed(() => {
    if (submitting.value) return '提交中…'
    if (!isConfirm.value) return '下一步'
    if (orderPendingConfirm.value || orderId.value) return '查看订单'
    if (lockState.value === 'failed') return '重新选择日期'
    if (lockState.value === 'expired') return '重新锁档'
    if (quoteState.value === 'failed') return '重新获取报价'
    if (quoteState.value === 'loading' || quoteState.value === 'idle') return '正在核价…'
    if (barAmount.value === null) return '金额待确认'
    return '提交申请'
})

/* ---------------- 交互与流程控制 ---------------- */

function selectPackage(p: any) {
    const next = pkgId(p)
    if (Number(next) === Number(packageId.value)) return
    packageId.value = next
    invalidateQuote()
}

function toggleAddon(id: number) {
    const i = selectedAddons.value.indexOf(id)
    if (i >= 0) selectedAddons.value.splice(i, 1)
    else selectedAddons.value.push(id)
    invalidateQuote()
}

function addUpsell() {
    const a = upsellAddon.value
    if (!a) return
    if (!selectedAddons.value.includes(addonId(a))) selectedAddons.value.push(addonId(a))
    invalidateQuote()
}

function validateField(key: BookingValidateField) {
    errors[key] = validateBookingField(key, form[key])
}

function validate() {
    validateField('contact_name')
    validateField('contact_mobile')
    validateField('service_address')
    return !errors.contact_name && !errors.contact_mobile && !errors.service_address
}

function focusFirstError(explicitTarget?: BookingFocusTarget) {
    const target: BookingFocusTarget =
        explicitTarget ?? (errors.contact_name || errors.contact_mobile ? 'contact' : 'address')
    if (target !== 'agree') formErrorVisible.value = true
    // 连续点击命中同一区块时也要重新触发闪烁：先清空，再在下一帧挂回目标
    flashTarget.value = ''
    if (flashTimer) clearTimeout(flashTimer)
    nextTick(() => {
        flashTarget.value = target
        flashTimer = setTimeout(() => {
            flashTarget.value = ''
        }, 1500)
    })
    confirmStepRef.value?.scrollToBlock?.(target)
    toast.info(
        target === 'contact'
            ? '请完善联系人信息'
            : target === 'address'
              ? '请填写服务地址'
              : '请先阅读并勾选协议'
    )
}

function onAgreedChange(val: boolean) {
    agreed.value = val
    if (val) agreeError.value = ''
}

function openSheet(mode: BookingSheetMode) {
    sheetMode.value = mode
    sheetVisible.value = true
}

function switchRegionFromSheet() {
    sheetVisible.value = false
    setTimeout(() => openDatePicker(), 260)
}

function saveSheet(payload: BookingEditSheetSavePayload) {
    if (payload.mode === 'contact') {
        form.contact_name = payload.contact_name
        form.contact_mobile = payload.contact_mobile
        errors.contact_name = ''
        errors.contact_mobile = ''
    } else if (payload.mode === 'address') {
        form.service_address = payload.service_address
        errors.service_address = ''
    } else {
        form.remark = payload.remark
    }
    sheetVisible.value = false
}

function onBack() {
    if (step.value > 1) {
        prevStep()
        return
    }
    const pages = getCurrentPages()
    if (pages.length > 1) uni.navigateBack()
    else uni.switchTab({ url: '/pages/index/index' })
}

function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        load()
        return
    }
    if (kind === 'reselect' || kind === 'back') {
        onBack()
        return
    }
    if (kind === 'login') navigate({ path: '/pages/login/login' })
}

function resetScrollAfterStepChange() {
    // 归零同步复位导航实底态：pageScrollTo 的回调/onPageScroll 不一定在切步后立即触发，
    // 不复位会带着上一屏的 threshold+1 进新步骤，导航停在实底态（该透明却实底）
    navScrollTop.value = 0
    nextTick(() => uni.pageScrollTo({ scrollTop: 0, duration: 0 }))
}

function ensureDateTime(): boolean {
    if (date.value && regionReady.value) return true
    toast.info(!date.value ? '请先选择服务日期' : '请先选择服务地区')
    openDatePicker()
    return false
}

function nextStep() {
    if (!ensureDateTime()) return
    if (step.value === 1) {
        if (!packageId.value) {
            toast.info('请选择套餐')
            return
        }
        step.value = 2
        resetScrollAfterStepChange()
        return
    }
    const role = activeRole.value
    if (role) {
        const wanted = !!roleWanted[role.role_key]
        const picked = rolePicked[role.role_key]
        if (wanted && (!picked || !picked.staff_id || !picked.package_id)) {
            toast.info(`请选择${role.role_label}人员`)
            return
        }
    }
    if (isConfirm.value) return
    step.value += 1
    resetScrollAfterStepChange()
    if (isConfirm.value) {
        if (quoteState.value !== 'ready') runPreview()
        doLock()
    }
}

function skipAddons() {
    selectedAddons.value = []
    invalidateQuote()
    nextStep()
}

function prevStep() {
    if (step.value > 1) {
        step.value -= 1
        resetScrollAfterStepChange()
    }
}

function editStep(target: number) {
    if (!isConfirm.value) return
    step.value = Math.min(Math.max(1, target), confirmStep.value)
    resetScrollAfterStepChange()
}

function onPrimaryTap() {
    if (submitting.value) return
    if (orderPendingConfirm.value || orderId.value) {
        goOrderDetail()
        return
    }
    if (lockState.value === 'failed') {
        openDatePicker()
        return
    }
    if (lockState.value === 'expired') {
        relockSchedule()
        return
    }
    if (quoteState.value === 'failed') {
        runPreview()
        return
    }
    if (quoteState.value === 'loading' || quoteState.value === 'idle') {
        toast.info('正在核价，请稍候')
        return
    }
    if (barAmount.value === null) {
        runPreview()
        return
    }
    handleSubmit()
}

async function loadScheduleRule() {
    try {
        const rule: any = await getScheduleRule()
        minSelectableDate.value = String(rule?.min_selectable_date || '')
        maxSelectableDate.value = String(rule?.max_selectable_date || '')
    } catch {
        // 规则接口失败不阻断
    }
}

function openDatePicker() {
    datePickerVisible.value = true
    if (!minSelectableDate.value) loadScheduleRule()
}

function onDatePickerConfirm(payload: { date: string; region: RegionContext }) {
    const nextRegion = payload.region
    const nextDate = payload.date
    const prevDate = date.value

    const changed =
        nextDate !== date.value ||
        nextRegion.district_code !== region.value.district_code ||
        nextRegion.city_code !== region.value.city_code

    date.value = nextDate
    region.value = { ...nextRegion }
    city.value = nextRegion.city_name || city.value
    saveBookingContext({ date: nextDate, region: nextRegion })

    if (!changed) return

    invalidateQuote()
    resetAllRoles()
    load({ silent: true })

    if (!isConfirm.value) return
    if (prevDate && prevDate !== nextDate && lockState.value === 'locked') {
        releaseActiveLock(prevDate)
    }
    runPreview()
    doLock()
}

async function load(options: { silent?: boolean } = {}) {
    if (!staffId.value) {
        error.value = true
        loading.value = false
        return
    }
    if (!options.silent) loading.value = true
    error.value = false
    try {
        const detail: any = await getStaffDetail({
            id: staffId.value,
            date: date.value || undefined,
            ...regionParams(region.value)
        })
        if (!detail || !detail.id) throw new Error('empty')

        staffName.value = detail.name || ''
        staffAvatar.value = detail.avatar || ''
        staffCover.value = detail.cover || detail.avatar || ''
        staffRating.value = Number(detail.rating || 0)
        staffOrderCount.value = Number(detail.order_count || 0)
        staffRole.value = String(detail.category_name || detail.role_name || '主持人')
        city.value = region.value.city_name || detail.city || detail.city_name || ''
        packages.value = Array.isArray(detail.packages) ? detail.packages : []
        addons.value = Array.isArray(detail.addons) ? detail.addons : []
        roleConfigs.value = Array.isArray(detail.related_role_configs)
            ? detail.related_role_configs
            : []

        if (packages.value.length) {
            const preferred = packages.value.find((p) => pkgId(p) === Number(packageId.value))
            packageId.value = preferred ? packageId.value : pkgId(packages.value[0])
        }

        if (options.silent) {
            selectedAddons.value = selectedAddons.value.filter((id) =>
                addons.value.some((a) => addonId(a) === id)
            )
            pruneUnavailableRoles()
        } else {
            selectedAddons.value = []
        }
    } catch (e) {
        rawError.value = e
        if (!options.silent) error.value = true
        report('api_error', { endpoint: 'staff.detail', message: '预约配置加载失败' })
    } finally {
        if (!options.silent) loading.value = false
    }
}

let wasLogin = userStore.isLogin
onShow(() => {
    consumePickedFromDetail()
    const now = userStore.isLogin
    if (!wasLogin && now && isConfirm.value) {
        if (quoteState.value !== 'ready') runPreview()
        if (lockState.value === 'idle' || lockState.value === 'error') doLock()
    }
    wasLogin = now
})

onUnload(() => {
    stopLockTimer()
    releaseActiveLock()
})

onLoad((options: any) => {
    staffId.value = Number(options?.staff_id || 0)
    packageId.value = Number(options?.package_id || 0)
    if (!staffId.value) {
        error.value = true
        loading.value = false
        return
    }
    resolveBookingContext(options || {}).then((ctx) => {
        date.value = ctx.date
        region.value = ctx.region
        loadScheduleRule()
        load().then(() => {
            if (!date.value || !regionReady.value) nextTick(() => openDatePicker())
        })
    })
})
</script>

<style lang="scss" scoped>
/* ========== 骨架 ========== */
.sk {
    &__hero {
        height: 720rpx;
        padding: var(--sp-4);
        background: var(--c-hero-bg);
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
    }

    &__body {
        padding: var(--sp-4);
    }
}

/* ========== Bk Hero ========== */
.bhero {
    position: relative;
    height: 860rpx;
    overflow: hidden;
    background: var(--c-ink);

    &__img {
        width: 100%;
        height: 100%;
    }

    &__blank {
        width: 100%;
        height: 100%;
        background: var(--c-hero-bg);
    }

    &__scrim {
        position: absolute;
        left: 0;
        right: 0;
        top: 0;
        bottom: 0;
        background: linear-gradient(
            180deg,
            rgba(14, 12, 10, 0.56) 0%,
            rgba(14, 12, 10, 0.04) 30%,
            rgba(14, 12, 10, 0.34) 60%,
            rgba(14, 12, 10, 0.94) 100%
        );
    }

    &__date {
        position: absolute;
        left: 42rpx;
        top: 23%;
        z-index: 3;
        display: inline-flex;
        align-items: center;
        gap: 12rpx;
        height: 58rpx;
        padding: 0 21rpx;
        border-radius: var(--r-pill);
        background: rgba(255, 255, 255, 0.16);
        border: 1rpx solid rgba(255, 255, 255, 0.26);
        color: var(--c-on-dark);

        &.is-empty {
            border-color: var(--c-brand-soft);
            background: rgba(198, 161, 91, 0.24);
        }
    }

    &__date-text {
        font-size: var(--fs-20);
        letter-spacing: 1rpx;
    }

    &__info {
        position: absolute;
        left: 42rpx;
        right: 42rpx;
        bottom: 42rpx;
        z-index: 2;
        color: var(--c-on-dark);
    }

    &__eyebrow {
        display: block;
        font-family: var(--font-serif);
        font-size: var(--fs-18);
        letter-spacing: 2rpx;
        text-transform: uppercase;
        color: var(--c-brand-soft);
    }

    &__name {
        display: block;
        margin-top: 17rpx;
        font-size: var(--fs-44);
        font-weight: var(--fw-semibold);
        letter-spacing: 2rpx;
        line-height: var(--lh-tight);
    }

    &__price {
        display: flex;
        align-items: baseline;
        gap: 10rpx;
        margin-top: 21rpx;
    }

    &__unit {
        font-size: var(--fs-18);
        color: var(--c-on-dark-2);
    }
}

.steps-wrap {
    margin-top: var(--sp-2);
}

.panel {
    padding: 24rpx 42rpx 0;
}

.stephead {
    margin-bottom: 20rpx;

    &__title {
        display: block;
        font-size: var(--fs-30);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        line-height: var(--lh-tight);
    }

    &__hint {
        display: block;
        margin-top: 8rpx;
        font-size: var(--fs-20);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }
}

.needbar {
    display: flex;
    align-items: center;
    gap: 12rpx;
    margin: 20rpx 42rpx 0;
    padding: 18rpx 20rpx;
    border: 1rpx solid var(--c-warning-border);
    border-radius: var(--r-sm);
    background: var(--c-warning-bg);
}

.needbar__icon {
    flex: none;
    color: var(--c-warning);
}

.needbar__text {
    flex: 1;
    min-width: 0;
    font-size: var(--fs-20);
    line-height: var(--lh-body);
    color: var(--c-warning);
}

.needbar__action {
    flex: none;
    font-size: var(--fs-20);
    color: var(--c-brand-text);
    text-decoration: underline;
}

.opts {
    margin: 0 -42rpx;
    width: auto;
    white-space: nowrap;
}

.opts__track {
    display: inline-flex;
    align-items: stretch;
    gap: 20rpx;
    padding: 0 42rpx;
}

.optcard {
    width: 300rpx;
    flex: none;
    min-width: 0;
    position: relative;
    padding: 22rpx 22rpx 20rpx;
    border-radius: var(--r-sm);
    border: 1rpx solid var(--c-line-strong);
    background: var(--c-surface);

    &.is-on {
        border-color: var(--c-brand-text);

        .optcard__tick {
            opacity: 1;
            transform: scale(1);
        }
    }

    &__head {
        display: flex;
        align-items: flex-start;
        gap: 8rpx;
        min-height: 88rpx;
    }

    &__name {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-24);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        line-height: var(--lh-tight);
    }

    &__tick {
        flex: none;
        width: 32rpx;
        height: 32rpx;
        border-radius: var(--r-pill);
        background: var(--c-ink);
        color: var(--c-on-dark);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transform: scale(0.7);
        transition: opacity var(--dur-fast) var(--ease-out),
            transform var(--dur-fast) var(--ease-out);
    }

    &__price {
        display: flex;
        align-items: baseline;
        gap: 4rpx;
        margin-top: 12rpx;
    }

    &__unit {
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }
}

.bkline {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);
    margin-top: 20rpx;
    font-size: var(--fs-20);
    color: var(--c-ink-3);

    &__sum {
        flex: 1;
        min-width: 0;
    }

    &__strong {
        color: var(--c-ink-2);
    }

    &__skip {
        display: inline-flex;
        align-items: center;
        gap: 2rpx;
        flex: none;
        padding: 22rpx 0;
        margin: -22rpx 0;
    }

    &__skip-text {
        font-size: var(--fs-20);
        color: var(--c-ink-3);
        text-decoration: underline;
    }
}

.abp {
    display: flex;
    flex-direction: column;
    gap: 4rpx;
    min-width: 0;

    &__label {
        font-size: var(--fs-xs);
        letter-spacing: 1rpx;
        color: var(--c-ink-3);
    }

    &__row {
        display: flex;
        align-items: baseline;
        gap: 12rpx;
        min-width: 0;
    }

    &__na {
        font-size: var(--fs-md);
        color: var(--c-ink-3);
    }

    &__hint {
        overflow: hidden;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        white-space: nowrap;
        text-overflow: ellipsis;
    }
}

.abtns {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
}
</style>
