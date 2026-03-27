<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar
            :title="currentPageTitle"
            bgColor="rgba(252, 251, 249, 0.96)"
            textColor="#1E2432"
            @back="handleBackToDetail"
        />

        <view class="staff-booking-page">
            <view class="staff-booking-page__hero">
                <image class="staff-booking-page__hero-image" :src="heroImage" mode="aspectFill" />
                <view class="staff-booking-page__base-mask" />
                <view class="staff-booking-page__focus-mask" />

                <view v-if="loading" class="staff-booking-page__loading">
                    <LoadingState text="预约信息加载中..." />
                </view>

                <view v-else-if="currentStep" class="staff-booking-page__content">
                    <view class="step-badge">
                        <text class="step-badge__text">{{ currentStepTag }}</text>
                    </view>

                    <view class="staff-booking-page__main">
                        <text class="staff-booking-page__desc">{{ currentIntroText }}</text>

                        <scroll-view
                            scroll-x
                            class="choice-scroll"
                            show-scrollbar="false"
                            enhanced
                        >
                            <view class="choice-list" :class="choiceListClass">
                                <template v-if="currentStep.type === 'package'">
                                    <view
                                        v-for="item in displayPackages"
                                        :key="resolvePackageId(item)"
                                        class="choice-card"
                                        :class="{
                                            'choice-card--selected':
                                                resolvePackageId(item) === booking.package_id
                                        }"
                                        @click="handlePackageSelect(item)"
                                    >
                                        <view class="choice-card__body">
                                            <view class="choice-card__copy">
                                                <text class="choice-card__title">
                                                    {{ resolvePackageName(item) }}
                                                </text>
                                                <text class="choice-card__subline">
                                                    ¥{{ formatPrice(resolvePackagePrice(item)) }}
                                                </text>
                                            </view>
                                            <text
                                                v-if="resolvePackageId(item) === booking.package_id"
                                                class="choice-card__check"
                                            >
                                                ✓
                                            </text>
                                        </view>
                                    </view>
                                </template>

                                <template v-else-if="currentStep.type === 'custom'">
                                    <view
                                        class="choice-card"
                                        :class="{
                                            'choice-card--selected':
                                                !booking.custom_option_keys.includes(
                                                    currentStep.option.key
                                                )
                                        }"
                                        @click="
                                            handleCustomOptionSelect(
                                                currentStep.option.key,
                                                false
                                            )
                                        "
                                    >
                                        <view class="choice-card__body">
                                            <view class="choice-card__copy">
                                                <text class="choice-card__title">
                                                    {{
                                                        currentStep.option.skip_label ||
                                                        '否，暂不需要'
                                                    }}
                                                </text>
                                                <text class="choice-card__subline">费用不变</text>
                                            </view>
                                            <text
                                                v-if="
                                                    !booking.custom_option_keys.includes(
                                                        currentStep.option.key
                                                    )
                                                "
                                                class="choice-card__check"
                                            >
                                                ✓
                                            </text>
                                        </view>
                                    </view>

                                    <view
                                        class="choice-card"
                                        :class="{
                                            'choice-card--selected':
                                                booking.custom_option_keys.includes(
                                                    currentStep.option.key
                                                )
                                        }"
                                        @click="
                                            handleCustomOptionSelect(
                                                currentStep.option.key,
                                                true
                                            )
                                        "
                                    >
                                        <view class="choice-card__body">
                                            <view class="choice-card__copy">
                                                <text class="choice-card__title">
                                                    {{
                                                        currentStep.option.selected_label ||
                                                        `是，增加${currentStep.option.name}`
                                                    }}
                                                </text>
                                                <text class="choice-card__subline">
                                                    +¥{{ formatPrice(currentStep.option.price) }}
                                                </text>
                                            </view>
                                            <text
                                                v-if="
                                                    booking.custom_option_keys.includes(
                                                        currentStep.option.key
                                                    )
                                                "
                                                class="choice-card__check"
                                            >
                                                ✓
                                            </text>
                                        </view>
                                    </view>
                                </template>

                                <template v-else>
                                    <view
                                        class="choice-card"
                                        :class="{
                                            'choice-card--selected':
                                                !selectedRoleCandidates[currentStep.key]
                                        }"
                                        @click="handleRoleCandidateSelect(currentStep.key, null)"
                                    >
                                        <view class="choice-card__body">
                                            <view class="choice-card__copy">
                                                <text class="choice-card__title">
                                                    {{
                                                        currentStep.config.skip_option_label ||
                                                        '否，后续自行预约'
                                                    }}
                                                </text>
                                                <text class="choice-card__subline">费用不变</text>
                                            </view>
                                            <text
                                                v-if="!selectedRoleCandidates[currentStep.key]"
                                                class="choice-card__check"
                                            >
                                                ✓
                                            </text>
                                        </view>
                                    </view>

                                    <view
                                        v-for="candidate in currentRoleCandidates"
                                        :key="`${currentStep.key}-${candidate.staff_id}-${candidate.package_id}`"
                                        class="choice-card"
                                        :class="{
                                            'choice-card--selected':
                                                selectedRoleCandidates[currentStep.key]?.staff_id ===
                                                    candidate.staff_id &&
                                                selectedRoleCandidates[currentStep.key]?.package_id ===
                                                    candidate.package_id
                                        }"
                                        @click="
                                            handleRoleCandidateSelect(currentStep.key, candidate)
                                        "
                                    >
                                        <view class="choice-card__body">
                                            <view class="choice-card__copy">
                                                <text class="choice-card__title">{{ candidate.name }}</text>
                                                <text class="choice-card__subline">
                                                    +¥{{ formatPrice(candidate.price) }}
                                                </text>
                                            </view>
                                            <text
                                                v-if="
                                                    selectedRoleCandidates[currentStep.key]?.staff_id ===
                                                        candidate.staff_id &&
                                                    selectedRoleCandidates[currentStep.key]
                                                        ?.package_id === candidate.package_id
                                                "
                                                class="choice-card__check"
                                            >
                                                ✓
                                            </text>
                                        </view>
                                    </view>
                                </template>
                            </view>
                        </scroll-view>

                        <view
                            v-if="currentStep.type === 'package' && !displayPackages.length"
                            class="empty-state"
                        >
                            <text class="empty-state__text">当前档期暂无可预约套餐</text>
                        </view>
                    </view>
                </view>
            </view>

            <ActionArea sticky safeBottom>
                <view class="booking-action-bar">
                    <view class="booking-action-bar__shell">
                        <view class="total-pill" @click="openSummaryPopup">
                            <text class="total-pill__text">
                                总价 ¥{{ formatPrice(totalAmount) }}
                            </text>
                        </view>

                        <view class="booking-action-bar__buttons">
                            <view class="booking-action-btn booking-action-btn--prev" @click="handlePrevious">
                                <text class="booking-action-btn__text">上一步</text>
                            </view>
                            <view
                                class="booking-action-btn booking-action-btn--next"
                                :class="{ 'booking-action-btn--disabled': !canGoNext }"
                                @click="handleNext"
                            >
                                <text
                                    class="booking-action-btn__text booking-action-btn__text--inverse"
                                >
                                    下一步
                                </text>
                            </view>
                        </view>
                    </view>
                </view>
            </ActionArea>
        </view>

        <view v-if="showSummaryPopup" class="summary-popup" @click="closeSummaryPopup">
            <view class="summary-popup__mask" />
            <view class="summary-popup__dialog">
                <view class="summary-popup__panel" @click.stop>
                    <view class="summary-popup__tag">
                        <text class="summary-popup__tag-text">预约明细</text>
                    </view>
                    <text class="summary-popup__title">已选内容</text>

                    <view class="summary-popup__list">
                        <view
                            v-for="item in summaryItems"
                            :key="item.key"
                            class="summary-popup__item"
                        >
                            <text class="summary-popup__label">{{ item.label }}</text>
                            <text
                                class="summary-popup__value"
                                :class="{
                                    'summary-popup__value--accent': item.kind !== 'package'
                                }"
                            >
                                {{ formatSummaryPrice(item) }}
                            </text>
                        </view>
                    </view>

                    <view class="summary-popup__total">
                        <text class="summary-popup__total-label">总价</text>
                        <text class="summary-popup__total-value">
                            ¥{{ formatPrice(totalAmount) }}
                        </text>
                    </view>

                    <view class="summary-popup__close" @click="closeSummaryPopup">
                        <text class="summary-popup__close-text">关闭弹窗</text>
                    </view>
                </view>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import PageShell from '@/components/base/PageShell.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import ActionArea from '@/components/base/ActionArea.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import { getStaffBookingRoleCandidates, getStaffDetail } from '@/api/staff'
import { useThemeStore } from '@/stores/theme'
import {
    BOOKING_ROLE_KEYS,
    type BookingOptionKey,
    type BookingRoleKey,
    getOrderConfirmPageUrl,
    getStaffDetailPageUrl,
    normalizeBookingQuery
} from '@/utils/staff-booking'
import {
    hasServiceRegion,
    loadServiceRegionSelection,
    saveServiceRegionSelection
} from '@/utils/service-region'

type StaffPackage = {
    id?: number
    package_id?: number
    name?: string
    price?: number | string
    description?: string
    package?: {
        id?: number
        name?: string
        price?: number | string
        description?: string
    }
}

type BookingOption = {
    key: BookingOptionKey
    name: string
    price: number
    selected_label?: string
    skip_label?: string
}

type RoleConfig = {
    role_key: BookingRoleKey
    role_label: string
    related_category_id?: number
    related_category_name?: string
    skip_option_label?: string
}

type RoleCandidate = {
    role_key: BookingRoleKey
    role_label: string
    staff_id: number
    name: string
    avatar?: string
    category_id?: number
    package_id: number
    package_name?: string
    package_description?: string
    price: number
    original_price?: number
    schedule_available?: boolean
    schedule_message?: string
}

type BookingStep =
    | {
          type: 'package'
          key: 'package'
      }
    | {
          type: 'custom'
          key: BookingOptionKey
          option: BookingOption
      }
    | {
          type: 'role'
          key: BookingRoleKey
          config: RoleConfig
      }

type SummaryItem = {
    key: string
    label: string
    price: number
    kind: 'package' | 'addon'
}

const DEFAULT_HERO_IMAGE =
    'https://images.unsplash.com/photo-1739047597919-5a047d0d736a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixlib=rb-4.1.0&q=80&w=1080'

const $theme = useThemeStore()

const loading = ref(true)
const staffDetail = ref<Record<string, any> | null>(null)
const currentStepIndex = ref(0)
const showSummaryPopup = ref(false)

const booking = reactive(normalizeBookingQuery(loadServiceRegionSelection()))

const roleCandidatesMap = reactive<Record<BookingRoleKey, RoleCandidate[]>>({
    butler: [],
    director: []
})

const displayPackages = computed<StaffPackage[]>(() =>
    Array.isArray(staffDetail.value?.packages) ? staffDetail.value?.packages : []
)

const customOptions = computed<BookingOption[]>(() =>
    Array.isArray(staffDetail.value?.booking_options) ? staffDetail.value?.booking_options : []
)

const roleConfigs = computed<RoleConfig[]>(() =>
    Array.isArray(staffDetail.value?.related_role_configs)
        ? staffDetail.value?.related_role_configs
        : []
)

const bookingSteps = computed<BookingStep[]>(() => {
    const steps: BookingStep[] = [
        {
            type: 'package',
            key: 'package'
        }
    ]

    customOptions.value.forEach((option) => {
        steps.push({
            type: 'custom',
            key: option.key,
            option
        })
    })

    roleConfigs.value.forEach((config) => {
        steps.push({
            type: 'role',
            key: config.role_key,
            config
        })
    })

    return steps
})

const currentStep = computed(() => bookingSteps.value[currentStepIndex.value] || null)

const currentRoleCandidates = computed<RoleCandidate[]>(() => {
    const step = currentStep.value
    if (!step || step.type !== 'role') {
        return []
    }

    return roleCandidatesMap[step.key] || []
})

const selectedPackage = computed<StaffPackage | null>(() => {
    return (
        displayPackages.value.find((item) => resolvePackageId(item) === booking.package_id) || null
    )
})

const selectedRoleCandidates = computed<Record<string, RoleCandidate | null>>(() => {
    return BOOKING_ROLE_KEYS.reduce(
        (result, roleKey) => {
            result[roleKey] = findSelectedRoleCandidate(roleKey)
            return result
        },
        {} as Record<string, RoleCandidate | null>
    )
})

const summaryItems = computed<SummaryItem[]>(() => {
    const items: SummaryItem[] = []

    if (selectedPackage.value) {
        items.push({
            key: `package-${resolvePackageId(selectedPackage.value)}`,
            label: resolvePackageName(selectedPackage.value),
            price: resolvePackagePrice(selectedPackage.value),
            kind: 'package'
        })
    }

    customOptions.value.forEach((option) => {
        if (!booking.custom_option_keys.includes(option.key)) {
            return
        }

        items.push({
            key: `custom-${option.key}`,
            label: option.name,
            price: Number(option.price || 0),
            kind: 'addon'
        })
    })

    roleConfigs.value.forEach((config) => {
        const candidate = selectedRoleCandidates.value[config.role_key]
        if (!candidate) {
            return
        }

        items.push({
            key: `role-${config.role_key}-${candidate.staff_id}-${candidate.package_id}`,
            label: config.role_label,
            price: Number(candidate.price || 0),
            kind: 'addon'
        })
    })

    return items
})

const totalAmount = computed(() =>
    summaryItems.value.reduce((total, item) => total + Number(item.price || 0), 0)
)

const currentPageTitle = computed(() => {
    const step = currentStep.value
    if (!step) {
        return '预约服务'
    }

    if (step.type === 'package') {
        return '选择基础套餐'
    }

    if (step.type === 'custom') {
        return `是否附加${step.option.name}`
    }

    return `是否需要${step.config.role_label}`
})

const currentStepTag = computed(() => {
    const step = currentStep.value
    const stepNumber = currentStepIndex.value + 1
    const total = bookingSteps.value.length || 1

    if (!step) {
        return `步骤 ${stepNumber}/${total}`
    }

    if (step.type === 'package') {
        return `步骤 ${stepNumber}/${total}｜先确定一个基础套餐`
    }

    if (step.type === 'custom') {
        return `步骤 ${stepNumber}/${total}｜确认是否增加${step.option.name}`
    }

    if (stepNumber === total) {
        return `步骤 ${stepNumber}/${total}｜确认最后一个附加项`
    }

    return `步骤 ${stepNumber}/${total}｜是否增加${step.config.role_label}`
})

const currentIntroText = computed(() => {
    const step = currentStep.value
    if (!step) {
        return '请按照预约流程完成本次服务选择。'
    }

    if (step.type === 'package') {
        const description = resolvePackageDescription(selectedPackage.value || displayPackages.value[0])
        return (
            description ||
            '先为当前婚礼档期确定一个基础套餐，后续附加项会在这个套餐基础上继续叠加。'
        )
    }

    if (step.type === 'custom') {
        return `如果当前场次需要“${step.option.name}”，可以在这里一并加入预约，费用会同步计入总价。`
    }

    return `可为当前档期补充${step.config.role_label}服务，如暂时未确定，也可以先跳过，后续再自行预约。`
})

const heroImage = computed(() => {
    return String(
        staffDetail.value?.banners?.[0]?.image || staffDetail.value?.avatar || DEFAULT_HERO_IMAGE
    )
})

const canGoNext = computed(() => {
    const step = currentStep.value
    if (loading.value || !step) {
        return false
    }

    if (step.type === 'package') {
        return Boolean(selectedPackage.value)
    }

    return true
})

const choiceListClass = computed(() => {
    const step = currentStep.value
    return {
        'choice-list--package': step?.type === 'package',
        'choice-list--compact': step?.type === 'custom',
        'choice-list--role': step?.type === 'role'
    }
})

watch(
    () => bookingSteps.value.length,
    (length) => {
        if (!length) {
            currentStepIndex.value = 0
            return
        }

        if (currentStepIndex.value > length - 1) {
            currentStepIndex.value = length - 1
        }
    },
    {
        immediate: true
    }
)

const resolvePackageId = (item: StaffPackage | null | undefined) => {
    return Number(item?.package_id || item?.id || item?.package?.id || 0)
}

const resolvePackageName = (item: StaffPackage | null | undefined) => {
    return String(item?.package?.name || item?.name || '服务套餐')
}

const resolvePackageDescription = (item: StaffPackage | null | undefined) => {
    return String(item?.package?.description || item?.description || '')
}

const resolvePackagePrice = (item: StaffPackage | null | undefined) => {
    return Number(item?.price ?? item?.package?.price ?? 0)
}

const formatPrice = (value: number | string) => {
    const amount = Number(value || 0)
    if (!Number.isFinite(amount)) {
        return '0'
    }

    const rounded = Math.round(amount * 100) / 100
    const isInteger = Math.abs(rounded - Math.trunc(rounded)) < 0.00001
    return rounded.toLocaleString('zh-CN', {
        minimumFractionDigits: isInteger ? 0 : 2,
        maximumFractionDigits: 2
    })
}

const formatSummaryPrice = (item: SummaryItem) => {
    const prefix = item.kind === 'package' ? '' : '+'
    return `${prefix}¥${formatPrice(item.price)}`
}

const getRoleSelection = (roleKey: BookingRoleKey) => {
    if (roleKey === 'butler') {
        return {
            staff_id: booking.butler_staff_id,
            package_id: booking.butler_package_id
        }
    }

    return {
        staff_id: booking.director_staff_id,
        package_id: booking.director_package_id
    }
}

const setRoleSelection = (roleKey: BookingRoleKey, candidate: RoleCandidate | null) => {
    if (roleKey === 'butler') {
        booking.butler_staff_id = candidate?.staff_id || 0
        booking.butler_package_id = candidate?.package_id || 0
        return
    }

    booking.director_staff_id = candidate?.staff_id || 0
    booking.director_package_id = candidate?.package_id || 0
}

const findSelectedRoleCandidate = (roleKey: BookingRoleKey) => {
    const currentSelection = getRoleSelection(roleKey)
    if (!currentSelection.staff_id || !currentSelection.package_id) {
        return null
    }

    return (
        roleCandidatesMap[roleKey].find(
            (candidate) =>
                Number(candidate.staff_id || 0) === currentSelection.staff_id &&
                Number(candidate.package_id || 0) === currentSelection.package_id
        ) || null
    )
}

const handlePackageSelect = (item: StaffPackage) => {
    booking.package_id = resolvePackageId(item)
}

const handleCustomOptionSelect = (key: BookingOptionKey, selected: boolean) => {
    const nextKeys = booking.custom_option_keys.filter((item) => item !== key)
    booking.custom_option_keys = selected ? [...nextKeys, key] : nextKeys
}

const handleRoleCandidateSelect = (
    roleKey: BookingRoleKey,
    candidate: RoleCandidate | null
) => {
    setRoleSelection(roleKey, candidate)
}

const openSummaryPopup = () => {
    if (!summaryItems.value.length) {
        uni.showToast({
            title: '请先选择基础套餐',
            icon: 'none'
        })
        return
    }

    showSummaryPopup.value = true
}

const closeSummaryPopup = () => {
    showSummaryPopup.value = false
}

const goOrderConfirm = () => {
    if (!booking.package_id) {
        uni.showToast({
            title: '请先选择基础套餐',
            icon: 'none'
        })
        return
    }

    uni.navigateTo({
        url: getOrderConfirmPageUrl(booking)
    })
}

const redirectToStaffDetail = () => {
    if (!booking.staff_id) {
        if (getCurrentPages().length > 1) {
            uni.navigateBack()
            return
        }

        uni.switchTab({
            url: '/pages/index/index'
        })
        return
    }

    uni.redirectTo({
        url: getStaffDetailPageUrl(booking)
    })
}

const handleBackToDetail = () => {
    redirectToStaffDetail()
}

const handlePrevious = () => {
    if (currentStepIndex.value <= 0) {
        redirectToStaffDetail()
        return
    }

    currentStepIndex.value -= 1
}

const handleNext = () => {
    if (!canGoNext.value) {
        if (!booking.package_id) {
            uni.showToast({
                title: '请先选择基础套餐',
                icon: 'none'
            })
        }
        return
    }

    if (currentStepIndex.value >= bookingSteps.value.length - 1) {
        goOrderConfirm()
        return
    }

    currentStepIndex.value += 1
}

const syncPackageSelection = () => {
    if (!booking.package_id) {
        return
    }

    const matched = displayPackages.value.some((item) => resolvePackageId(item) === booking.package_id)
    if (!matched) {
        booking.package_id = 0
    }
}

const syncCustomSelections = () => {
    const validKeys = new Set(customOptions.value.map((item) => item.key))
    booking.custom_option_keys = booking.custom_option_keys.filter((key) => validKeys.has(key))
}

const syncRoleSelections = () => {
    const enabledRoleKeys = new Set(roleConfigs.value.map((item) => item.role_key))

    BOOKING_ROLE_KEYS.forEach((roleKey) => {
        if (!enabledRoleKeys.has(roleKey)) {
            setRoleSelection(roleKey, null)
            roleCandidatesMap[roleKey] = []
            return
        }

        const candidate = findSelectedRoleCandidate(roleKey)
        const currentSelection = getRoleSelection(roleKey)
        if (currentSelection.staff_id > 0 && currentSelection.package_id > 0 && !candidate) {
            setRoleSelection(roleKey, null)
        }
    })
}

const loadRoleCandidates = async (roleKey: BookingRoleKey) => {
    try {
        const result = await getStaffBookingRoleCandidates({
            staff_id: booking.staff_id,
            role_key: roleKey,
            date: booking.date,
            province_code: booking.province_code,
            province_name: booking.province_name,
            city_code: booking.city_code,
            city_name: booking.city_name,
            district_code: booking.district_code,
            district_name: booking.district_name
        })

        roleCandidatesMap[roleKey] = Array.isArray(result)
            ? result.filter((item) => item?.schedule_available !== false)
            : []
    } catch (error) {
        roleCandidatesMap[roleKey] = []
    }
}

const applyBookingQuery = (value: Record<string, any>) => {
    const normalized = normalizeBookingQuery(value)
    booking.staff_id = normalized.staff_id
    booking.package_id = normalized.package_id
    booking.date = normalized.date
    booking.province_code = normalized.province_code
    booking.province_name = normalized.province_name
    booking.city_code = normalized.city_code
    booking.city_name = normalized.city_name
    booking.district_code = normalized.district_code
    booking.district_name = normalized.district_name
    booking.custom_option_keys = normalized.custom_option_keys
    booking.butler_staff_id = normalized.butler_staff_id
    booking.butler_package_id = normalized.butler_package_id
    booking.director_staff_id = normalized.director_staff_id
    booking.director_package_id = normalized.director_package_id
}

const handleLoadError = (message: string) => {
    uni.showToast({
        title: message,
        icon: 'none'
    })

    setTimeout(() => {
        redirectToStaffDetail()
    }, 1200)
}

const initPage = async () => {
    loading.value = true
    showSummaryPopup.value = false

    BOOKING_ROLE_KEYS.forEach((roleKey) => {
        roleCandidatesMap[roleKey] = []
    })

    try {
        const detail = await getStaffDetail({
            id: booking.staff_id,
            date: booking.date,
            province_code: booking.province_code,
            province_name: booking.province_name,
            city_code: booking.city_code,
            city_name: booking.city_name,
            district_code: booking.district_code,
            district_name: booking.district_name
        })

        if (!detail?.id) {
            throw new Error('服务人员不存在或已下架')
        }

        if (detail.schedule_available === false) {
            throw new Error(detail.schedule_message || '当前档期不可预约，请重新选择日期')
        }

        staffDetail.value = detail
        syncPackageSelection()
        syncCustomSelections()

        await Promise.all(roleConfigs.value.map((config) => loadRoleCandidates(config.role_key)))
        syncRoleSelections()
    } catch (error: any) {
        const message = typeof error === 'string' ? error : error?.message || '预约信息加载失败'
        handleLoadError(message)
    } finally {
        loading.value = false
    }
}

onLoad((options) => {
    $theme.setScene('consumer')

    applyBookingQuery({
        ...loadServiceRegionSelection(),
        ...options
    })

    if (hasServiceRegion(booking)) {
        saveServiceRegionSelection(booking)
    }

    if (!booking.staff_id || !booking.date || !hasServiceRegion(booking)) {
        handleLoadError('预约信息不完整，请重新选择服务地区和日期')
        return
    }

    initPage()
})
</script>

<style lang="scss" scoped>
.staff-booking-page {
    min-height: 100vh;
    background: #fcfbf9;
}

.staff-booking-page__hero {
    position: relative;
    min-height: calc(100vh - 120rpx);
    overflow: hidden;
    background: #fcfbf9;
}

.staff-booking-page__hero-image,
.staff-booking-page__base-mask,
.staff-booking-page__focus-mask {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
}

.staff-booking-page__hero-image {
    z-index: 0;
}

.staff-booking-page__base-mask {
    z-index: 1;
    background: linear-gradient(
        180deg,
        rgba(9, 9, 11, 0.06) 0%,
        rgba(9, 9, 11, 0.13) 36%,
        rgba(9, 9, 11, 0.28) 100%
    );
}

.staff-booking-page__focus-mask {
    z-index: 1;
    background: linear-gradient(
        180deg,
        rgba(9, 9, 11, 0) 0%,
        rgba(9, 9, 11, 0.16) 24%,
        rgba(9, 9, 11, 0.51) 100%
    );
}

.staff-booking-page__loading,
.staff-booking-page__content {
    position: relative;
    z-index: 2;
}

.staff-booking-page__loading {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: calc(100vh - 220rpx);
    padding: 48rpx 24rpx 220rpx;
}

.staff-booking-page__content {
    display: flex;
    flex-direction: column;
    min-height: calc(100vh - 120rpx);
    padding: 12rpx 20rpx 220rpx;
}

.staff-booking-page__main {
    margin-top: auto;
    display: flex;
    flex-direction: column;
    gap: 12rpx;
}

.staff-booking-page__desc {
    display: block;
    max-width: 688rpx;
    font-size: 24rpx;
    line-height: 1.45;
    color: rgba(255, 255, 255, 0.96);
    text-shadow: 0 4rpx 12rpx rgba(9, 9, 11, 0.2);
}

.step-badge {
    align-self: flex-start;
    padding: 7rpx 12rpx;
    border-radius: 999rpx;
    background: #fff1ee;
    border: 1rpx solid #f4c7bf;
    box-shadow: 0 8rpx 20rpx rgba(232, 90, 79, 0.08);
}

.step-badge__text {
    font-size: 22rpx;
    line-height: 1.2;
    font-weight: 600;
    color: #e85a4f;
}

.choice-scroll {
    width: 100%;
    white-space: nowrap;
}

.choice-list {
    display: inline-flex;
    gap: 10rpx;
    padding-right: 120rpx;
    min-width: 100%;
    box-sizing: border-box;
}

.choice-list--compact {
    min-width: auto;
}

.choice-list--role {
    min-width: auto;
}

.choice-card {
    width: 292rpx;
    height: 112rpx;
    padding: 18rpx 22rpx;
    border-radius: 20rpx;
    background: rgba(255, 255, 255, 0.92);
    border: 1rpx solid rgba(239, 230, 225, 0.96);
    box-sizing: border-box;
    backdrop-filter: blur(10rpx);
    -webkit-backdrop-filter: blur(10rpx);

    &:active {
        transform: scale(0.985);
    }
}

.choice-list--role .choice-card {
    width: 292rpx;
}

.choice-list--compact .choice-card {
    width: 292rpx;
}

.choice-card--selected {
    background: #fff1ee;
    border-color: #f4c7bf;
    box-shadow: 0 12rpx 28rpx rgba(232, 90, 79, 0.14);
}

.choice-card__body {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12rpx;
    height: 100%;
}

.choice-card__copy {
    display: flex;
    flex-direction: column;
    gap: 2rpx;
    min-width: 0;
    flex: 1;
}

.choice-card__title {
    display: block;
    font-size: 24rpx;
    line-height: 1.3;
    font-weight: 700;
    color: #1e2432;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.choice-card__subline {
    display: block;
    font-size: 22rpx;
    line-height: 1.25;
    font-weight: 500;
    color: #7f7b78;
}

.choice-card__check {
    width: 36rpx;
    height: 36rpx;
    border-radius: 50%;
    background: #e85a4f;
    color: #ffffff;
    font-size: 20rpx;
    font-weight: 700;
    line-height: 36rpx;
    text-align: center;
    flex-shrink: 0;
    box-shadow: 0 8rpx 18rpx rgba(232, 90, 79, 0.2);
}

.empty-state {
    padding: 24rpx;
    border-radius: 24rpx;
    background: rgba(255, 255, 255, 0.88);
    border: 1rpx solid rgba(239, 230, 225, 0.96);
    backdrop-filter: blur(18rpx);
    -webkit-backdrop-filter: blur(18rpx);
}

.empty-state__text {
    font-size: 24rpx;
    line-height: 1.5;
    color: #7f7b78;
}

.staff-booking-page :deep(.wm-action-area) {
    align-items: stretch;
    gap: 0;
    padding: 0 12rpx 20rpx;
    background: transparent;
    border-top: none;
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
}

.booking-action-bar {
    width: 100%;
}

.booking-action-bar__shell {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12rpx;
    padding: 12rpx;
    border-radius: 24rpx;
    background: rgba(255, 255, 255, 0.91);
    border: 1rpx solid rgba(239, 230, 225, 0.96);
    backdrop-filter: blur(20rpx);
    -webkit-backdrop-filter: blur(20rpx);
    box-shadow: 0 18rpx 34rpx rgba(9, 9, 11, 0.08);
}

.total-pill {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    min-height: 44rpx;
    padding: 10rpx 14rpx;
    border-radius: 18rpx;
    background: #fff1ee;
    border: 1rpx solid #f4c7bf;
}

.total-pill__text {
    font-size: 26rpx;
    line-height: 1.2;
    font-weight: 700;
    color: #1e2432;
}

.booking-action-bar__buttons {
    display: flex;
    gap: 8rpx;
    flex-shrink: 0;
}

.booking-action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 88rpx;
    border-radius: 18rpx;
    box-sizing: border-box;
    flex-shrink: 0;

    &:active {
        transform: scale(0.985);
    }
}

.booking-action-btn--prev {
    width: 164rpx;
    background: #fcfbf9;
    border: 1rpx solid #efe6e1;
}

.booking-action-btn--next {
    width: 220rpx;
    background: #e85a4f;
    box-shadow: 0 10rpx 18rpx rgba(232, 90, 79, 0.16);
}

.booking-action-btn--disabled {
    opacity: 0.52;
    pointer-events: none;
}

.booking-action-btn__text {
    font-size: 28rpx;
    line-height: 1.2;
    font-weight: 600;
    color: #1e2432;
}

.booking-action-btn__text--inverse {
    color: #ffffff;
}

.summary-popup {
    position: fixed;
    inset: 0;
    z-index: 120;
}

.summary-popup__mask {
    position: absolute;
    inset: 0;
    background: rgba(9, 9, 11, 0.45);
}

.summary-popup__dialog {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100%;
    padding: 48rpx 28rpx calc(48rpx + env(safe-area-inset-bottom));
    box-sizing: border-box;
}

.summary-popup__panel {
    display: flex;
    flex-direction: column;
    width: 640rpx;
    max-width: 100%;
    max-height: calc(100vh - 96rpx - env(safe-area-inset-bottom));
    padding: 18rpx 18rpx 20rpx;
    border-radius: 28rpx;
    background: rgba(255, 255, 255, 0.94);
    border: 1rpx solid rgba(239, 230, 225, 0.96);
    backdrop-filter: blur(24rpx);
    -webkit-backdrop-filter: blur(24rpx);
    box-shadow: 0 18rpx 36rpx rgba(9, 9, 11, 0.14);
    box-sizing: border-box;
    overflow: hidden;
}

.summary-popup__tag {
    align-self: flex-start;
    display: inline-flex;
    padding: 7rpx 12rpx;
    border-radius: 999rpx;
    background: #fff1ee;
    border: 1rpx solid #f4c7bf;
}

.summary-popup__tag-text {
    font-size: 22rpx;
    line-height: 1.2;
    font-weight: 600;
    color: #e85a4f;
}

.summary-popup__title {
    display: block;
    margin-top: 16rpx;
    font-size: 40rpx;
    line-height: 1.2;
    font-weight: 700;
    color: #1e2432;
}

.summary-popup__list {
    margin-top: 18rpx;
    display: flex;
    flex-direction: column;
    gap: 10rpx;
    min-height: 0;
    overflow-y: auto;
    padding-right: 4rpx;
}

.summary-popup__item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    padding: 10rpx 0;
    border-bottom: 1rpx solid #f7f1ed;
}

.summary-popup__item:last-child {
    border-bottom: none;
}

.summary-popup__label {
    flex: 1;
    min-width: 0;
    font-size: 28rpx;
    line-height: 1.35;
    font-weight: 600;
    color: #1e2432;
}

.summary-popup__value {
    flex-shrink: 0;
    font-size: 28rpx;
    line-height: 1.35;
    font-weight: 700;
    color: #1e2432;
}

.summary-popup__value--accent {
    color: #e85a4f;
}

.summary-popup__total {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    margin-top: 14rpx;
    padding: 14rpx 16rpx;
    border-radius: 22rpx;
    background: #fff1ee;
    border: 1rpx solid #f4c7bf;
}

.summary-popup__total-label {
    font-size: 30rpx;
    line-height: 1.2;
    font-weight: 700;
    color: #1e2432;
}

.summary-popup__total-value {
    font-size: 34rpx;
    line-height: 1.2;
    font-weight: 700;
    color: #e85a4f;
}

.summary-popup__close {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 88rpx;
    margin-top: 16rpx;
    border-radius: 18rpx;
    background: #fcfbf9;
    border: 1rpx solid #efe6e1;

    &:active {
        transform: scale(0.985);
    }
}

.summary-popup__close-text {
    font-size: 28rpx;
    line-height: 1.2;
    font-weight: 600;
    color: #1e2432;
}
</style>
