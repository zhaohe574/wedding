<template>
    <page-meta :page-style="$theme.pageStyle" />

    <PageShell scene="consumer">
        <BaseNavbar :title="currentPageTitle" @back="handleBackToDetail" />

        <view class="staff-booking-page wm-page-content" :style="pageStageStyle">
            <view class="staff-booking-page__hero">
                <view class="staff-booking-page__hero-image" :style="heroImageStyle" />

                <view class="staff-booking-page__base-mask" />

                <view class="staff-booking-page__focus-mask" />

                <view v-if="loading" class="staff-booking-page__loading">
                    <LoadingState text="预约信息加载中..." />
                </view>

                <view v-else-if="currentStep" class="staff-booking-page__content">
                    <StatusBadge tone="info" size="sm" class="step-badge">
                        {{ currentStepTag }}
                    </StatusBadge>

                    <view class="staff-booking-page__main">
                        <text class="staff-booking-page__desc">{{ currentIntroText }}</text>

                        <text v-if="!mainLockReady" class="staff-booking-page__assist-text">
                            正在锁定当前档期，请稍候进入下一步
                        </text>

                        <text
                            v-else-if="currentStep.type === 'role' && roleLoadingMap[currentStep.key]"
                            class="staff-booking-page__assist-text"
                        >
                            正在加载可选人员...
                        </text>

                        <scroll-view scroll-x class="choice-scroll" show-scrollbar="false" enhanced>
                            <view class="choice-list" :class="choiceListClass">
                                <template v-if="currentStep.type === 'package'">
                                    <view
                                        v-for="item in displayPackages"
                                        :key="resolvePackageId(item)"
                                        class="choice-card wm-soft-card"
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

                                                    <text v-if="resolvePackageDurationText(item)">
                                                        ｜{{ resolvePackageDurationText(item) }}
                                                    </text>
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

                                <template v-else-if="currentStep.type === 'addon'">
                                    <view
                                        class="choice-card wm-soft-card"
                                        :class="{
                                            'choice-card--selected': !booking.addon_ids.includes(
                                                resolveAddonId(currentStep.addon)
                                            )
                                        }"
                                        @click="
                                            handleAddonSelect(
                                                resolveAddonId(currentStep.addon),

                                                false
                                            )
                                        "
                                    >
                                        <view class="choice-card__body">
                                            <view class="choice-card__copy">
                                                <text class="choice-card__title"> 暂不增加 </text>

                                                <text class="choice-card__subline">费用不变</text>
                                            </view>

                                            <text
                                                v-if="
                                                    !booking.addon_ids.includes(
                                                        resolveAddonId(currentStep.addon)
                                                    )
                                                "
                                                class="choice-card__check"
                                            >
                                                ✓
                                            </text>
                                        </view>
                                    </view>

                                    <view
                                        class="choice-card wm-soft-card"
                                        :class="{
                                            'choice-card--selected': booking.addon_ids.includes(
                                                resolveAddonId(currentStep.addon)
                                            )
                                        }"
                                        @click="
                                            handleAddonSelect(
                                                resolveAddonId(currentStep.addon),

                                                true
                                            )
                                        "
                                    >
                                        <view class="choice-card__body">
                                            <view class="choice-card__copy">
                                                <text class="choice-card__title">
                                                    {{ `增加${currentStep.addon.name}` }}
                                                </text>

                                                <text class="choice-card__subline">
                                                    +¥{{ formatPrice(currentStep.addon.price) }}
                                                </text>
                                            </view>

                                            <text
                                                v-if="
                                                    booking.addon_ids.includes(
                                                        resolveAddonId(currentStep.addon)
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
                                        class="choice-card wm-soft-card"
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
                                        class="choice-card wm-soft-card"
                                        :class="{
                                            'choice-card--selected':
                                                selectedRoleCandidates[currentStep.key]
                                                    ?.staff_id === candidate.staff_id &&
                                                selectedRoleCandidates[currentStep.key]
                                                    ?.package_id === candidate.package_id
                                        }"
                                        @click="
                                            handleRoleCandidateSelect(currentStep.key, candidate)
                                        "
                                    >
                                        <view class="choice-card__body">
                                            <view class="choice-card__copy">
                                                <text class="choice-card__title">{{
                                                    candidate.name
                                                }}</text>

                                                <text class="choice-card__subline">
                                                    +¥{{ formatPrice(candidate.price) }}
                                                </text>
                                            </view>

                                            <text
                                                v-if="
                                                    selectedRoleCandidates[currentStep.key]
                                                        ?.staff_id === candidate.staff_id &&
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

                        <view
                            v-else-if="
                                currentStep.type === 'role' &&
                                roleLoadingMap[currentStep.key] &&
                                !currentRoleCandidates.length
                            "
                            class="empty-state"
                        >
                            <text class="empty-state__text">正在同步可选人员...</text>
                        </view>
                    </view>
                </view>
            </view>

            <ActionArea safeBottom>
                <view class="booking-action-bar">
                    <view class="booking-action-bar__shell">
                        <view class="total-pill" @click="openSummaryPopup">
                            <text class="total-pill__text">
                                总价 ¥{{ formatPrice(totalAmount) }}
                            </text>
                        </view>

                        <view class="booking-action-bar__buttons">
                            <view
                                class="booking-action-btn booking-action-btn--prev"
                                @click="handlePrevious"
                            >
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

import { onLoad, onShow, onUnload } from '@dcloudio/uni-app'

import PageShell from '@/components/base/PageShell.vue'

import BaseNavbar from '@/components/base/BaseNavbar.vue'

import ActionArea from '@/components/base/ActionArea.vue'

import LoadingState from '@/components/base/LoadingState.vue'

import StatusBadge from '@/components/base/StatusBadge.vue'

import { BACK_URL } from '@/enums/constantEnums'

import { useNavBarMetrics } from '@/hooks/useNavBarMetrics'

import { getStaffBookingRoleCandidates, getStaffDetail } from '@/api/staff'

import { useThemeStore } from '@/stores/theme'

import { useUserStore } from '@/stores/user'

import cache from '@/utils/cache'

import {
    ensureMainBookingLock,
    releaseAllBookingLocks,
    renewAllBookingLocks,
    replaceRoleBookingLock
} from '@/packages/common/utils/booking-lock-session'

import {
    BOOKING_RETURN_MODE_DETAIL_BACK,
    BOOKING_ROLE_KEYS,
    type BookingRoleKey,
    clearStaffDetailRestoreSnapshot,
    getOrderConfirmPageUrl,
    getStaffBookingPageUrl,
    getStaffDetailPageUrl,
    loadStaffDetailRestoreSnapshot,
    normalizeBookingQuery,
    saveStaffDetailRestoreSnapshot,
    saveStaffDetailReturnState
} from '@/packages/common/utils/staff-booking'

import {
    hasServiceRegion,
    loadServiceRegionSelection,
    saveServiceRegionSelection
} from '@/utils/service-region'
import { isDevMode } from '@/utils/env'

type StaffPackage = {
    id?: number

    package_id?: number

    addon_ids?: number[]

    name?: string

    price?: number | string

    description?: string

    image?: string

    duration?: number | string

    duration_desc?: string

    package?: {
        id?: number

        addon_ids?: number[]

        name?: string

        price?: number | string

        description?: string

        image?: string

        duration?: number | string

        duration_desc?: string
    }
}

type StaffAddon = {
    id?: number | string

    name: string

    price: number

    description?: string

    image?: string
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
          type: 'addon'

          key: number

          addon: StaffAddon
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

const $theme = useThemeStore()

const userStore = useUserStore()

const navBarMetrics = useNavBarMetrics()

const loading = ref(true)

const initialized = ref(false)

const detailReady = ref(false)

const mainLockReady = ref(false)

const staffDetail = ref<Record<string, any> | null>(null)

const currentStepIndex = ref(0)

const showSummaryPopup = ref(false)

const roleSwitchingKey = ref<BookingRoleKey | ''>('')

const booking = reactive(normalizeBookingQuery(loadServiceRegionSelection()))

const roleCandidatesMap = reactive<Record<BookingRoleKey, RoleCandidate[]>>({
    butler: [],

    director: []
})

const roleLoadingMap = reactive<Record<BookingRoleKey, boolean>>({
    butler: false,

    director: false
})

const roleLoadedMap = reactive<Record<BookingRoleKey, boolean>>({
    butler: false,

    director: false
})

const roleLoadTaskMap: Partial<Record<BookingRoleKey, Promise<void>>> = {}

const displayPackages = computed<StaffPackage[]>(() =>
    Array.isArray(staffDetail.value?.packages) ? staffDetail.value?.packages : []
)

const selectedPackage = computed<StaffPackage | null>(() => {
    return (
        displayPackages.value.find((item) => resolvePackageId(item) === booking.package_id) || null
    )
})

const currentPackageAddonIds = computed<number[]>(() =>
    resolvePackageAddonIds(selectedPackage.value)
)

const displayAddons = computed<StaffAddon[]>(() => {
    const addons = Array.isArray(staffDetail.value?.addons) ? staffDetail.value?.addons : []

    if (!selectedPackage.value) {
        return []
    }

    const allowedAddonIds = new Set(currentPackageAddonIds.value)

    if (!allowedAddonIds.size) {
        return []
    }

    return addons.filter((item) => allowedAddonIds.has(resolveAddonId(item)))
})

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

    displayAddons.value.forEach((addon) => {
        steps.push({
            type: 'addon',

            key: resolveAddonId(addon),

            addon
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

const flowTotalSteps = computed(() => bookingSteps.value.length + 1)

const currentStep = computed(() => bookingSteps.value[currentStepIndex.value] || null)

const currentRoleCandidates = computed<RoleCandidate[]>(() => {
    const step = currentStep.value

    if (!step || step.type !== 'role') {
        return []
    }

    return roleCandidatesMap[step.key] || []
})

const selectedRoleCandidates = computed<Record<string, RoleCandidate | null>>(() => {
    return BOOKING_ROLE_KEYS.reduce((result, roleKey) => {
        result[roleKey] = findSelectedRoleCandidate(roleKey)

        return result
    }, {} as Record<string, RoleCandidate | null>)
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

    displayAddons.value.forEach((addon) => {
        const addonId = resolveAddonId(addon)

        if (!booking.addon_ids.includes(addonId)) {
            return
        }

        items.push({
            key: `addon-${addonId}`,

            label: addon.name,

            price: Number(addon.price || 0),

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
        return '套餐'
    }

    if (step.type === 'addon') {
        return `${step.addon.name}`
    }

    return `${step.config.role_label}`
})

const currentStepTag = computed(() => {
    const step = currentStep.value

    const stepNumber = currentStepIndex.value + 1

    const total = flowTotalSteps.value || 1

    const isLastSelectionStep = currentStepIndex.value >= bookingSteps.value.length - 1

    if (!step) {
        return `步骤 ${stepNumber}/${total}`
    }

    if (step.type === 'package') {
        return `步骤 ${stepNumber}/${total}｜先确定一个基础套餐`
    }

    if (step.type === 'addon') {
        return `步骤 ${stepNumber}/${total}｜确认是否增加${step.addon.name}`
    }

    if (isLastSelectionStep) {
        return `步骤 ${stepNumber}/${total}｜确认最后一个附加项`
    }

    return `步骤 ${stepNumber}/${total}｜是否增加${step.config.role_label}`
})

const currentIntroText = computed(() => {
    const step = currentStep.value

    if (!step) {
        return '请完成本次预约选择。'
    }

    if (step.type === 'package') {
        const description = resolvePackageDescription(
            selectedPackage.value || displayPackages.value[0]
        )

        return description || '先确定基础套餐。'
    }

    if (step.type === 'addon') {
        return step.addon.description || `如果当前场次需要“${step.addon.name}”，可一并加入预约。`
    }

    return `可为当前档期补充${step.config.role_label}服务，未确定可先跳过。`
})

const pageStageStyle = computed(() => ({
    height: `calc(100vh - ${navBarMetrics.navBarHeight}px)`
}))

const heroImage = computed(() => {
    const step = currentStep.value

    const image =
        (step?.type === 'addon' ? step.addon.image : '') ||
        resolvePackageImage(selectedPackage.value || displayPackages.value[0]) ||
        staffDetail.value?.banners?.[0]?.image ||
        staffDetail.value?.avatar

    return image ? String(image) : ''
})

const heroImageStyle = computed(() => ({
    backgroundImage: heroImage.value ? `url("${heroImage.value}")` : 'none'
}))

const canGoNext = computed(() => {
    const step = currentStep.value

    if (loading.value || !step || !mainLockReady.value) {
        return false
    }

    if (step.type === 'package') {
        return Boolean(selectedPackage.value)
    }

    if (step.type === 'role') {
        return roleLoadedMap[step.key]
    }

    return true
})

const choiceListClass = computed(() => {
    const step = currentStep.value

    return {
        'choice-list--package': step?.type === 'package',

        'choice-list--compact': step?.type === 'addon',

        'choice-list--role': step?.type === 'role'
    }
})

const resolvePackageId = (item: StaffPackage | null | undefined) => {
    return Number(item?.package_id || item?.id || item?.package?.id || 0)
}

const resolvePackageName = (item: StaffPackage | null | undefined) => {
    return String(item?.package?.name || item?.name || '服务套餐')
}

const resolvePackageAddonIds = (item: StaffPackage | null | undefined) => {
    const rawList = item?.addon_ids || item?.package?.addon_ids || []

    if (!Array.isArray(rawList)) {
        return []
    }

    return rawList

        .map((addonId) => Number(addonId))

        .filter((addonId) => Number.isInteger(addonId) && addonId > 0)

        .filter((addonId, index, list) => list.indexOf(addonId) === index)
}

const resolvePackageDescription = (item: StaffPackage | null | undefined) => {
    return String(item?.package?.description || item?.description || '')
}

const resolvePackageImage = (item: StaffPackage | null | undefined) => {
    return String(item?.package?.image || item?.image || '')
}

const resolveAddonId = (item: StaffAddon | null | undefined) => {
    return Number(item?.id || 0)
}

const resolvePackagePrice = (item: StaffPackage | null | undefined) => {
    return Number(item?.price ?? item?.package?.price ?? 0)
}

const resolvePackageDurationText = (item: StaffPackage | null | undefined) => {
    const durationDesc = String(item?.duration_desc || item?.package?.duration_desc || '').trim()

    if (durationDesc) {
        return durationDesc
    }

    const duration = Number(item?.duration ?? item?.package?.duration ?? 0)

    return duration > 0 ? `${duration}小时` : ''
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

const refreshInitializedState = () => {
    initialized.value = detailReady.value && mainLockReady.value
}

const resetRoleCandidateState = () => {
    BOOKING_ROLE_KEYS.forEach((roleKey) => {
        roleCandidatesMap[roleKey] = []
        roleLoadingMap[roleKey] = false
        roleLoadedMap[roleKey] = false
        roleLoadTaskMap[roleKey] = undefined
    })
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

const handleAddonSelect = (addonId: number, selected: boolean) => {
    const nextIds = booking.addon_ids.filter((item) => item !== addonId)

    booking.addon_ids = selected ? [...nextIds, addonId] : nextIds
}

const handleRoleCandidateSelect = async (
    roleKey: BookingRoleKey,

    candidate: RoleCandidate | null
) => {
    if (roleSwitchingKey.value === roleKey) {
        return
    }

    roleSwitchingKey.value = roleKey

    try {
        await replaceRoleBookingLock(
            roleKey,

            candidate
                ? {
                      staff_id: candidate.staff_id,

                      date: booking.date
                  }
                : null
        )

        setRoleSelection(roleKey, candidate)
    } catch (error: any) {
        const message =
            typeof error === 'string'
                ? error
                : error?.msg || error?.message || '关联人员档期锁定失败'

        uni.showToast({ title: message, icon: 'none' })
    } finally {
        roleSwitchingKey.value = ''
    }
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

const getBookingPageUrl = () => getStaffBookingPageUrl(booking)

const STAFF_DETAIL_PAGE_ROUTE = 'packages/pages/staff_detail/staff_detail'

const ensureBookingLogin = (message = '请先登录后预约') => {
    if (userStore.isLogin) {
        return true
    }

    cache.set(BACK_URL, getBookingPageUrl())

    uni.showToast({
        title: message,

        icon: 'none'
    })

    setTimeout(() => {
        uni.navigateTo({ url: '/pages/login/login' })
    }, 300)

    return false
}

const goOrderConfirm = async () => {
    if (!booking.package_id) {
        uni.showToast({
            title: '请先选择基础套餐',

            icon: 'none'
        })

        return
    }

    try {
        await loadSelectedRoleCandidates(false)

        await reconcileRoleLocksWithSelection(false)

        uni.navigateTo({
            url: getOrderConfirmPageUrl({
                ...booking,

                flow_total_steps: flowTotalSteps.value
            })
        })
    } catch (error: any) {
        const message = typeof error === 'string' ? error : error?.message || '档期锁定失败'

        await handleLoadError(message)
    }
}

const getPageRoute = (page: Record<string, any> | null | undefined) => {
    return String(page?.route || page?.__route__ || '')
}

const getPageRouteList = () => {
    const pages = getCurrentPages() as Array<Record<string, any>>
    return pages.map((page) => getPageRoute(page))
}

const getPreviousPageRoute = () => {
    const routes = getPageRouteList()
    return routes[routes.length - 2] || ''
}

const findStaffDetailPageDelta = () => {
    const pages = getCurrentPages() as Array<Record<string, any>>

    for (let index = pages.length - 2; index >= 0; index -= 1) {
        if (getPageRoute(pages[index]) === STAFF_DETAIL_PAGE_ROUTE) {
            return pages.length - 1 - index
        }
    }

    return 0
}

const syncBookingResultToDetailPage = () => {
    saveStaffDetailReturnState({
        staff_id: booking.staff_id,
        package_id: booking.package_id
    })
}

const syncBookingResultToDetailSnapshot = () => {
    const snapshot = loadStaffDetailRestoreSnapshot()

    if (!snapshot || snapshot.staff_id !== booking.staff_id) {
        return
    }

    saveStaffDetailRestoreSnapshot({
        ...snapshot,
        package_id: booking.package_id
    })
}

const logBookingPageStack = (scene: 'onLoad' | 'return') => {
    if (!isDevMode()) {
        return
    }

    console.log('套餐页页面栈调试', {
        scene,
        routes: getPageRouteList(),
        return_mode: booking.return_mode,
        staff_id: booking.staff_id
    })
}

const logBookingReturn = (action: 'navigateBack' | 'redirectTo' | 'switchTab') => {
    if (!isDevMode()) {
        return
    }

    console.log('套餐页返回调试', {
        action,
        return_mode: booking.return_mode,
        page_count: getCurrentPages().length,
        previous_route: getPreviousPageRoute(),
        page_routes: getPageRouteList(),
        detail_delta: findStaffDetailPageDelta(),
        staff_id: booking.staff_id,
        package_id: booking.package_id
    })
}

let releaseBookingLocksTask: Promise<void> | null = null

const startReleaseBookingLocks = () => {
    if (!releaseBookingLocksTask) {
        releaseBookingLocksTask = releaseAllBookingLocks()
            .catch(() => null)
            .finally(() => {
                releaseBookingLocksTask = null
            })
    }

    return releaseBookingLocksTask
}

const redirectToStaffDetail = () => {
    if (!booking.staff_id) {
        clearStaffDetailRestoreSnapshot()

        if (getCurrentPages().length > 1) {
            logBookingReturn('navigateBack')
            uni.navigateBack()

            return
        }

        logBookingReturn('switchTab')
        uni.switchTab({
            url: '/pages/index/index'
        })

        return
    }

    const detailDelta = findStaffDetailPageDelta()

    if (booking.return_mode === BOOKING_RETURN_MODE_DETAIL_BACK && detailDelta > 0) {
        syncBookingResultToDetailPage()

        clearStaffDetailRestoreSnapshot()

        logBookingReturn('navigateBack')
        uni.navigateBack({
            delta: detailDelta
        })

        return
    }

    syncBookingResultToDetailPage()
    syncBookingResultToDetailSnapshot()

    logBookingReturn('redirectTo')
    uni.redirectTo({
        url: getStaffDetailPageUrl(booking)
    })
}

const leaveBookingFlow = () => {
    void startReleaseBookingLocks()
    redirectToStaffDetail()
}

const handleBackToDetail = () => {
    leaveBookingFlow()
}

const handlePrevious = () => {
    if (currentStepIndex.value <= 0) {
        leaveBookingFlow()

        return
    }

    currentStepIndex.value -= 1
}

const handleNext = async () => {
    if (!canGoNext.value) {
        if (!mainLockReady.value) {
            uni.showToast({
                title: '正在锁定档期，请稍候',

                icon: 'none'
            })
        } else if (!booking.package_id) {
            uni.showToast({
                title: '请先选择基础套餐',

                icon: 'none'
            })
        } else if (
            currentStep.value?.type === 'role' &&
            roleLoadingMap[currentStep.value.key] &&
            !roleLoadedMap[currentStep.value.key]
        ) {
            uni.showToast({
                title: '正在加载可选人员，请稍候',

                icon: 'none'
            })
        }

        return
    }

    if (currentStepIndex.value >= bookingSteps.value.length - 1) {
        await goOrderConfirm()

        return
    }

    currentStepIndex.value += 1
}

const syncPackageSelection = () => {
    if (!booking.package_id) {
        return
    }

    const matched = displayPackages.value.some(
        (item) => resolvePackageId(item) === booking.package_id
    )

    if (!matched) {
        booking.package_id = 0
    }
}

const syncAddonSelections = () => {
    const validIds = new Set(displayAddons.value.map((item) => Number(item.id || 0)))

    booking.addon_ids = booking.addon_ids.filter((id) => validIds.has(id))
}

const syncRoleSelections = () => {
    const enabledRoleKeys = new Set(roleConfigs.value.map((item) => item.role_key))

    BOOKING_ROLE_KEYS.forEach((roleKey) => {
        if (!enabledRoleKeys.has(roleKey)) {
            setRoleSelection(roleKey, null)

            roleCandidatesMap[roleKey] = []

            roleLoadingMap[roleKey] = false

            roleLoadedMap[roleKey] = false

            return
        }

        if (!roleLoadedMap[roleKey]) {
            return
        }

        const candidate = findSelectedRoleCandidate(roleKey)

        const currentSelection = getRoleSelection(roleKey)

        if (currentSelection.staff_id > 0 && currentSelection.package_id > 0 && !candidate) {
            setRoleSelection(roleKey, null)
        }
    })
}

const reconcileRoleLocksWithSelection = async (
    showError = true,
    roleKeys: BookingRoleKey[] = [...BOOKING_ROLE_KEYS]
) => {
    for (const roleKey of roleKeys) {
        const candidate = findSelectedRoleCandidate(roleKey)

        try {
            await replaceRoleBookingLock(
                roleKey,

                candidate
                    ? {
                          staff_id: candidate.staff_id,

                          date: booking.date
                      }
                    : null
            )
        } catch (error: any) {
            setRoleSelection(roleKey, null)

            await replaceRoleBookingLock(roleKey, null).catch(() => null)

            if (showError) {
                const message =
                    typeof error === 'string'
                        ? error
                        : error?.msg || error?.message || '关联人员档期锁定失败'

                uni.showToast({ title: message, icon: 'none' })
            }
        }
    }
}

const loadRoleCandidates = async (
    roleKey: BookingRoleKey,
    options: { force?: boolean; silent?: boolean } = {}
) => {
    if (roleLoadingMap[roleKey]) {
        return roleLoadTaskMap[roleKey]
    }

    if (!options.force && roleLoadedMap[roleKey]) {
        return
    }

    roleLoadingMap[roleKey] = true

    const task = (async () => {
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
        } catch (error: any) {
            roleCandidatesMap[roleKey] = []

            if (!options.silent) {
                const roleLabel =
                    roleConfigs.value.find((item) => item.role_key === roleKey)?.role_label ||
                    '关联人员'

                const message =
                    typeof error === 'string'
                        ? error
                        : error?.msg || error?.message || `加载${roleLabel}候选人失败`

                uni.showToast({ title: message, icon: 'none' })
            }
        } finally {
            roleLoadingMap[roleKey] = false
            roleLoadedMap[roleKey] = true
            roleLoadTaskMap[roleKey] = undefined
            syncRoleSelections()
        }
    })()

    roleLoadTaskMap[roleKey] = task

    return task
}

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

watch(
    () => currentPackageAddonIds.value.join(','),

    () => {
        syncAddonSelections()
    }
)

watch(
    () => currentStep.value,

    (step) => {
        if (!step || step.type !== 'role') {
            return
        }

        if (roleLoadedMap[step.key] || roleLoadingMap[step.key]) {
            return
        }

        void loadRoleCandidates(step.key)
    },

    {
        immediate: true
    }
)

const applyBookingQuery = (value: Record<string, any>) => {
    const normalized = normalizeBookingQuery(value)

    booking.staff_id = normalized.staff_id

    booking.package_id = normalized.package_id

    booking.waitlist_id = normalized.waitlist_id

    booking.date = normalized.date

    booking.province_code = normalized.province_code

    booking.province_name = normalized.province_name

    booking.city_code = normalized.city_code

    booking.city_name = normalized.city_name

    booking.district_code = normalized.district_code

    booking.district_name = normalized.district_name

    booking.addon_ids = normalized.addon_ids

    booking.butler_staff_id = normalized.butler_staff_id

    booking.butler_package_id = normalized.butler_package_id

    booking.director_staff_id = normalized.director_staff_id

    booking.director_package_id = normalized.director_package_id

    booking.return_mode = normalized.return_mode

    booking.flow_total_steps = normalized.flow_total_steps
}

const handleLoadError = async (message: string) => {
    loading.value = false

    initialized.value = false

    detailReady.value = false

    mainLockReady.value = false

    staffDetail.value = null

    void startReleaseBookingLocks()

    uni.showToast({
        title: message,

        icon: 'none'
    })

    setTimeout(() => {
        redirectToStaffDetail()
    }, 1200)
}

const fetchStaffDetail = async () => {
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

    return detail
}

const loadSelectedRoleCandidates = async (silent = true) => {
    const selectedRoleKeys = BOOKING_ROLE_KEYS.filter((roleKey) => {
        const selection = getRoleSelection(roleKey)
        return selection.staff_id > 0 && selection.package_id > 0
    })

    if (!selectedRoleKeys.length) {
        return []
    }

    await Promise.all(
        selectedRoleKeys.map((roleKey) =>
            loadRoleCandidates(roleKey, {
                silent
            })
        )
    )

    syncRoleSelections()

    return selectedRoleKeys
}

const initPage = async () => {
    let initFailed = false

    loading.value = true

    initialized.value = false

    detailReady.value = false

    mainLockReady.value = false

    staffDetail.value = null

    showSummaryPopup.value = false

    resetRoleCandidateState()

    const mainLockPromise = ensureMainBookingLock({
        staff_id: booking.staff_id,

        date: booking.date
    })
        .then(() => {
            if (initFailed) {
                return false
            }

            mainLockReady.value = true
            refreshInitializedState()
            return true
        })
        .catch(async (error: any) => {
            if (initFailed) {
                return false
            }

            initFailed = true

            const message = typeof error === 'string' ? error : error?.message || '档期锁定失败'

            await handleLoadError(message)
            return false
        })

    try {
        const detail = await fetchStaffDetail()

        if (initFailed) {
            return
        }

        staffDetail.value = detail

        syncPackageSelection()

        syncAddonSelections()

        syncRoleSelections()

        detailReady.value = true

        loading.value = false

        refreshInitializedState()

        void (async () => {
            const selectedRoleKeys = await loadSelectedRoleCandidates(true)

            if (!selectedRoleKeys.length) {
                return
            }

            const isMainLockReady = await mainLockPromise

            if (!isMainLockReady) {
                return
            }

            await reconcileRoleLocksWithSelection(false, selectedRoleKeys)
        })().catch(() => null)
    } catch (error: any) {
        if (initFailed) {
            return
        }

        initFailed = true

        const message = typeof error === 'string' ? error : error?.message || '预约信息加载失败'

        await handleLoadError(message)
    }
}

onLoad((options) => {
    $theme.setScene('consumer')

    applyBookingQuery({
        ...loadServiceRegionSelection(),

        ...options
    })

    logBookingPageStack('onLoad')

    if (hasServiceRegion(booking)) {
        saveServiceRegionSelection(booking)
    }

    if (!booking.staff_id || !booking.date || !hasServiceRegion(booking)) {
        void handleLoadError('预约信息不完整，请重新选择服务地区和日期')

        return
    }

    if (!ensureBookingLogin()) {
        return
    }

    void initPage()
})

onShow(() => {
    if (!initialized.value || !userStore.isLogin) {
        return
    }

    void renewAllBookingLocks().catch(async (error: any) => {
        const message = typeof error === 'string' ? error : error?.message || '档期锁定失败'

        await handleLoadError(message)
    })
})

onUnload(() => {
    void startReleaseBookingLocks()
})
</script>

<style lang="scss" scoped>
.staff-booking-page {
    width: 100%;

    height: 100%;

    display: flex;

    flex-direction: column;

    overflow: hidden;

    background: #fcfbf9;
}

.staff-booking-page__hero {
    position: relative;

    flex: 1;

    min-height: 0;

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

    background-position: center;

    background-repeat: no-repeat;

    background-size: cover;

    transform: scale(1.02);
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

    height: 100%;

    box-sizing: border-box;
}

.staff-booking-page__loading {
    display: flex;

    align-items: center;

    justify-content: center;

    padding: 45rpx 37rpx 30rpx;
}

.staff-booking-page__content {
    display: flex;

    flex-direction: column;

    padding: 22rpx 37rpx 30rpx;

    overflow: hidden;
}

.staff-booking-page__main {
    margin-top: auto;

    display: flex;

    flex-direction: column;

    gap: 11rpx;
}

.staff-booking-page__desc {
    display: block;

    max-width: 688rpx;

    font-size: 24rpx;

    line-height: 1.45;

    color: rgba(255, 255, 255, 0.96);

    text-shadow: 0 4rpx 12rpx rgba(9, 9, 11, 0.2);
}

.staff-booking-page__assist-text {
    display: block;

    font-size: 22rpx;

    line-height: 1.4;

    color: rgba(255, 241, 238, 0.92);

    text-shadow: 0 4rpx 12rpx rgba(9, 9, 11, 0.18);
}

.step-badge {
    align-self: flex-start;

    box-shadow: 0 8rpx 20rpx rgba(232, 90, 79, 0.08);
}

.choice-scroll {
    width: 100%;

    white-space: nowrap;
}

.choice-list {
    display: inline-flex;

    gap: 22rpx;

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

    padding: 30rpx;

    border-radius: 37rpx;

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
    padding: 30rpx;

    border-radius: 45rpx;

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

    flex-shrink: 0;

    padding: 22rpx 22rpx calc(39rpx + env(safe-area-inset-bottom));

    background: #fcfbf9;

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

    gap: 22rpx;

    padding: 22rpx;

    border-radius: 45rpx;

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

    min-height: 82rpx;

    padding: 19rpx 26rpx;

    border-radius: 37rpx;

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

    gap: 19rpx;

    flex-shrink: 0;
}

.booking-action-btn {
    display: inline-flex;

    align-items: center;

    justify-content: center;

    height: 90rpx;

    border-radius: 37rpx;

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

    padding: 45rpx 37rpx calc(45rpx + env(safe-area-inset-bottom));

    box-sizing: border-box;
}

.summary-popup__panel {
    display: flex;

    flex-direction: column;

    width: 640rpx;

    max-width: 100%;

    max-height: calc(100vh - 96rpx - env(safe-area-inset-bottom));

    padding: 30rpx 30rpx 34rpx;

    border-radius: 52rpx;

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

    padding: 13rpx 22rpx;

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

    gap: 12rpx;

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

    padding: 26rpx 30rpx;

    border-radius: 37rpx;

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

    height: 90rpx;

    margin-top: 20rpx;

    border-radius: 37rpx;

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
