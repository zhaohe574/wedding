<template>
    <page-meta :page-style="$theme.pageStyle" />

    <PageShell scene="consumer" tone="showcase" suppressOverlay>
        <BaseNavbar
            :title="currentPageTitle"
            variant="solid"
            bg-color="#191713"
            text-color="#FFFDF8"
            @back="handleBackToDetail"
        />

        <view class="staff-booking-page" :style="pageStageStyle">
            <view class="staff-booking-page__hero">
                <view class="staff-booking-page__hero-image" :style="heroImageStyle" />

                <view class="staff-booking-page__base-mask" />

                <view class="staff-booking-page__focus-mask" />

                <view v-if="loading" class="staff-booking-page__loading">
                    <BaseCard variant="glass" scene="consumer" class="state-card" padding="0">
                        <LoadingState text="预约信息加载中..." />
                    </BaseCard>
                </view>

                <view v-else-if="pageError" class="staff-booking-page__error">
                    <BaseCard variant="glass" scene="consumer" class="state-card" padding="0">
                        <EmptyState
                            :title="pageError.title"
                            :description="pageError.message"
                            :action-text="pageError.actionText"
                            @action="handlePageErrorAction"
                        />
                    </BaseCard>

                    <view class="staff-booking-page__error-actions">
                        <view class="staff-booking-page__error-link" @click="redirectToStaffDetail">
                            <text>返回人员详情</text>
                        </view>

                        <view class="staff-booking-page__error-divider" />

                        <view class="staff-booking-page__error-link" @click="goHome">
                            <text>返回首页</text>
                        </view>
                    </view>
                </view>

                <view v-else-if="currentStep" class="staff-booking-page__content">
                    <view :key="currentStepBadgeKey" class="step-badge">
                        <text class="step-badge__count">{{ currentStepCountText }}</text>

                        <text class="step-badge__divider">｜</text>

                        <text class="step-badge__label">{{ currentStepActionText }}</text>
                    </view>

                    <view class="staff-booking-page__main">
                        <text class="staff-booking-page__title">{{ currentStepTitle }}</text>

                        <text class="staff-booking-page__desc">{{ currentIntroText }}</text>

                        <text class="staff-booking-page__assist-text">
                            付款成功后系统会为你正式锁定档期。
                        </text>

                        <text
                            v-if="currentStep.type === 'role' && roleLoadingMap[currentStep.key]"
                            class="staff-booking-page__assist-text"
                        >
                            正在加载可选人员...
                        </text>

                        <view class="choice-scroll-shell">
                            <view
                                v-if="
                                    currentStep.type === 'package' && displayPackages.length > 1
                                "
                                class="choice-scroll-hint"
                            >
                                <text class="choice-scroll-hint__text">右滑查看更多</text>

                                <text class="choice-scroll-hint__arrow">›</text>
                            </view>

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
                                            class="choice-card choice-card--package"
                                            :class="{
                                                'choice-card--selected':
                                                    resolvePackageId(item) === booking.package_id,
                                                'choice-card--recommended': isRecommendedPackage(
                                                    item
                                                )
                                            }"
                                            @click="handlePackageSelect(item)"
                                        >
                                            <view class="choice-card__body">
                                                <text
                                                    v-if="isRecommendedPackage(item)"
                                                    class="choice-card__recommend-badge"
                                                >
                                                    推荐
                                                </text>

                                                <view class="choice-card__copy">
                                                    <text class="choice-card__title">
                                                        {{ resolvePackageName(item) }}
                                                    </text>

                                                    <text class="choice-card__subline">
                                                        ¥{{
                                                            formatPrice(resolvePackagePrice(item))
                                                        }}

                                                        <text
                                                            v-if="resolvePackageDurationText(item)"
                                                        >
                                                            ｜{{ resolvePackageDurationText(item) }}
                                                        </text>
                                                    </text>
                                                </view>

                                                <text
                                                    v-if="
                                                        resolvePackageId(item) ===
                                                        booking.package_id
                                                    "
                                                    class="choice-card__check"
                                                >
                                                    ✓
                                                </text>
                                            </view>
                                        </view>
                                    </template>

                                    <template v-else-if="currentStep.type === 'addon'">
                                        <view
                                            class="choice-card choice-card--addon"
                                            :class="{
                                                'choice-card--selected':
                                                    !booking.addon_ids.includes(
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
                                                    <text class="choice-card__title">
                                                        暂不增加
                                                    </text>

                                                    <text class="choice-card__subline"
                                                        >费用不变</text
                                                    >
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
                                            class="choice-card choice-card--addon"
                                            :class="{
                                                'choice-card--selected':
                                                    booking.addon_ids.includes(
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
                                            class="choice-card choice-card--role"
                                            :class="{
                                                'choice-card--selected':
                                                    !selectedRoleCandidates[currentStep.key]
                                            }"
                                            @click="
                                                handleRoleCandidateSelect(currentStep.key, null)
                                            "
                                        >
                                            <view class="choice-card__body">
                                                <view class="choice-card__copy">
                                                    <text class="choice-card__title">
                                                        {{
                                                            currentStep.config
                                                                .skip_option_label ||
                                                            '否，后续自行预约'
                                                        }}
                                                    </text>

                                                    <text class="choice-card__subline"
                                                        >费用不变</text
                                                    >
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
                                            class="choice-card choice-card--role"
                                            :class="{
                                                'choice-card--selected':
                                                    selectedRoleCandidates[currentStep.key]
                                                        ?.staff_id === candidate.staff_id &&
                                                    selectedRoleCandidates[currentStep.key]
                                                        ?.package_id === candidate.package_id
                                            }"
                                            @click="
                                                handleRoleCandidateSelect(
                                                    currentStep.key,
                                                    candidate
                                                )
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
                        </view>

                        <BaseCard
                            v-if="currentStep.type === 'package' && !displayPackages.length"
                            variant="glass"
                            scene="consumer"
                            padding="0"
                            class="empty-state"
                        >
                            <text class="empty-state__text">当前档期暂无可预约套餐</text>
                        </BaseCard>

                        <BaseCard
                            v-else-if="
                                currentStep.type === 'role' &&
                                roleLoadingMap[currentStep.key] &&
                                !currentRoleCandidates.length
                            "
                            variant="glass"
                            scene="consumer"
                            padding="0"
                            class="empty-state"
                        >
                            <text class="empty-state__text">正在同步可选人员...</text>
                        </BaseCard>
                    </view>
                </view>
            </view>

            <ActionArea v-if="!pageError" tone="transparent" safeBottom>
                <view class="booking-action-bar">
                    <view class="booking-action-bar__shell">
                        <view class="total-pill" @click="openSummaryPopup">
                            <text class="total-pill__text">
                                总价 ¥{{ formatPrice(totalAmount) }}
                            </text>
                        </view>

                        <view class="booking-action-bar__buttons">
                            <BaseButton
                                label="上一步"
                                variant="light"
                                size="md"
                                height="92rpx"
                                class="booking-action-btn booking-action-btn--prev"
                                @click="handlePrevious"
                            />

                            <BaseButton
                                :label="nextActionText"
                                variant="dark"
                                size="md"
                                height="92rpx"
                                icon="arrow-right"
                                icon-position="right"
                                :disabled="!canGoNext"
                                class="booking-action-btn booking-action-btn--next"
                                @click="handleNext"
                            />
                        </view>
                    </view>
                </view>
            </ActionArea>
        </view>

        <view v-if="showSummaryPopup" class="summary-popup" @click="closeSummaryPopup">
            <BaseOverlayMask
                :show="showSummaryPopup"
                :z-index="120"
                background="rgba(11, 11, 11, 0.28)"
                @close="closeSummaryPopup"
            />

            <view class="summary-popup__dialog">
                <BaseCard
                    variant="glass"
                    scene="consumer"
                    padding="38rpx 34rpx 32rpx"
                    border-radius="36rpx"
                    background="#FFFDF8"
                    border="1rpx solid #D8C9AD"
                    box-shadow="0 18rpx 44rpx rgba(74, 43, 24, 0.16)"
                    class="summary-popup__panel"
                    @click.stop
                >
                    <view class="summary-popup__header">
                        <view class="summary-popup__title-group">
                            <text class="summary-popup__eyebrow">预约明细</text>

                            <text class="summary-popup__title">已选内容</text>
                        </view>

                        <text class="summary-popup__count">{{ summaryCountText }}</text>
                    </view>

                    <view class="summary-popup__list">
                        <view
                            v-for="item in summaryItems"
                            :key="item.key"
                            class="summary-popup__item"
                        >
                            <text class="summary-popup__item-label">{{ item.label }}</text>

                            <text
                                class="summary-popup__item-price"
                                :class="{
                                    'summary-popup__item-price--package': item.kind === 'package'
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

                    <view class="summary-popup__actions">
                        <BaseButton
                            label="关闭弹窗"
                            variant="light"
                            size="md"
                            height="76rpx"
                            block
                            @click="closeSummaryPopup"
                        />
                    </view>
                </BaseCard>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'

import { onLoad, onShow } from '@dcloudio/uni-app'

import PageShell from '@/components/base/PageShell.vue'

import BaseNavbar from '@/components/base/BaseNavbar.vue'

import ActionArea from '@/components/base/ActionArea.vue'

import BaseButton from '@/components/base/BaseButton.vue'

import BaseCard from '@/components/base/BaseCard.vue'

import BaseOverlayMask from '@/components/base/BaseOverlayMask.vue'

import EmptyState from '@/components/base/EmptyState.vue'

import LoadingState from '@/components/base/LoadingState.vue'

import { BACK_URL } from '@/enums/constantEnums'

import { useNavBarMetrics } from '@/hooks/useNavBarMetrics'

import { getStaffBookingRoleCandidates, getStaffDetail } from '@/api/staff'

import { useThemeStore } from '@/stores/theme'

import { useUserStore } from '@/stores/user'

import cache from '@/utils/cache'

import { goHome, goLoginWithBack, normalizePageRecoveryError } from '@/utils/page-recovery'

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
import { showError } from '@/utils/feedback'

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

const pageError = ref<ReturnType<typeof normalizePageRecoveryError> | null>(null)

const detailReady = ref(false)

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

const displayPackages = computed<StaffPackage[]>(() => {
    const packages = staffDetail.value?.packages
    return Array.isArray(packages) ? packages : []
})

const selectedPackage = computed<StaffPackage | null>(() => {
    return (
        displayPackages.value.find((item) => resolvePackageId(item) === booking.package_id) || null
    )
})

const currentPackageAddonIds = computed<number[]>(() =>
    resolvePackageAddonIds(selectedPackage.value)
)

const displayAddons = computed<StaffAddon[]>(() => {
    const sourceAddons = staffDetail.value?.addons
    const addons = Array.isArray(sourceAddons) ? sourceAddons : []

    if (!selectedPackage.value) {
        return []
    }

    const allowedAddonIds = new Set(currentPackageAddonIds.value)

    if (!allowedAddonIds.size) {
        return []
    }

    return addons.filter((item) => allowedAddonIds.has(resolveAddonId(item)))
})

const roleConfigs = computed<RoleConfig[]>(() => {
    const configs = staffDetail.value?.related_role_configs
    return Array.isArray(configs) ? configs : []
})

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

const summaryCountText = computed(() => `已选 ${summaryItems.value.length} 项`)

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

const currentStepTitle = computed(() => {
    const step = currentStep.value

    if (!step) {
        return '确认预约内容'
    }

    if (step.type === 'package') {
        return '选择基础套餐'
    }

    if (step.type === 'addon') {
        return `是否增加${step.addon.name}`
    }

    return `是否增加${step.config.role_label}`
})

const currentStepCountText = computed(() => {
    const stepNumber = currentStepIndex.value + 1

    const total = flowTotalSteps.value || 1

    return `步骤 ${stepNumber}/${total}`
})

const currentStepActionText = computed(() => {
    const step = currentStep.value

    if (!step) {
        return '确认预约内容'
    }

    if (step.type === 'package') {
        return '选择基础套餐'
    }

    if (step.type === 'addon') {
        return `确认是否增加${step.addon.name}`
    }

    return `确认是否增加${step.config.role_label}`
})

const currentStepBadgeKey = computed(() => {
    const step = currentStep.value

    return `${currentStepIndex.value}-${step?.type || 'empty'}-${String(step?.key || '')}`
})

const nextActionText = computed(() =>
    currentStepIndex.value >= bookingSteps.value.length - 1 ? '确认预约' : '下一步'
)

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

    if (loading.value || pageError.value || !step) {
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

const isRecommendedPackage = (item: StaffPackage | null | undefined) => {
    const rawValue =
        (item as Record<string, any> | null | undefined)?.is_recommend ??
        (item as Record<string, any> | null | undefined)?.is_recommended ??
        (item as Record<string, any> | null | undefined)?.recommend ??
        (item?.package as Record<string, any> | null | undefined)?.is_recommend ??
        (item?.package as Record<string, any> | null | undefined)?.is_recommended ??
        (item?.package as Record<string, any> | null | undefined)?.recommend

    if (typeof rawValue === 'boolean') {
        return rawValue
    }

    return Number(rawValue || 0) === 1
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
    initialized.value = detailReady.value
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
        setRoleSelection(roleKey, candidate)
    } catch (error: any) {
        const message =
            typeof error === 'string'
                ? error
                : error?.msg || error?.message || '关联人员选择失败'

        showError(message)
    } finally {
        roleSwitchingKey.value = ''
    }
}

const openSummaryPopup = () => {
    if (!summaryItems.value.length) {
        showError('请先选择基础套餐')

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

    showError(message)

    setTimeout(() => {
        uni.navigateTo({ url: '/pages/login/login' })
    }, 300)

    return false
}

const goOrderConfirm = async () => {
    if (!booking.package_id) {
        showError('请先选择基础套餐')

        return
    }

    try {
        await loadSelectedRoleCandidates(false)

        await reconcileRoleSelections(false)

        uni.navigateTo({
            url: getOrderConfirmPageUrl({
                ...booking,

                flow_total_steps: flowTotalSteps.value
            })
        })
    } catch (error: any) {
        const message = typeof error === 'string' ? error : error?.message || '预约信息确认失败'

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
    void scene
}

const logBookingReturn = (action: 'navigateBack' | 'redirectTo' | 'switchTab') => {
    void action
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
    redirectToStaffDetail()
}

const handlePageErrorAction = () => {
    if (pageError.value?.kind === 'auth') {
        goLoginWithBack(getBookingPageUrl())
        return
    }

    void initPage()
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
        if (!booking.package_id) {
            showError('请先选择基础套餐')
        } else if (
            currentStep.value?.type === 'role' &&
            roleLoadingMap[currentStep.value.key] &&
            !roleLoadedMap[currentStep.value.key]
        ) {
            showError('正在加载可选人员，请稍候')
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

const reconcileRoleSelections = async (
    shouldNotifyError = true,
    roleKeys: BookingRoleKey[] = [...BOOKING_ROLE_KEYS]
) => {
    for (const roleKey of roleKeys) {
        const candidate = findSelectedRoleCandidate(roleKey)

        try {
            setRoleSelection(roleKey, candidate)
        } catch (error: any) {
            setRoleSelection(roleKey, null)

            if (shouldNotifyError) {
                const message =
                    typeof error === 'string'
                        ? error
                        : error?.msg || error?.message || '关联人员选择失败'

                showError(message)
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

                showError(message)
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

    staffDetail.value = null

    pageError.value = normalizePageRecoveryError(
        message || '预约信息加载失败，请重新选择档期',
        '预约信息加载失败，请重新选择档期'
    )
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

    pageError.value = null

    initialized.value = false

    detailReady.value = false

    staffDetail.value = null

    showSummaryPopup.value = false

    resetRoleCandidateState()

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

            await reconcileRoleSelections(false, selectedRoleKeys)
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
})
</script>

<style lang="scss" scoped>
.staff-booking-page {
    width: 100%;

    height: 100%;

    display: flex;

    flex-direction: column;

    overflow: hidden;

    background: var(--wm-color-primary, #191713);
}

.staff-booking-page__hero {
    position: relative;

    flex: 1;

    min-height: 0;

    overflow: hidden;

    background: var(--wm-color-primary, #191713);
}

.staff-booking-page :deep(.base-navbar) {
    border-bottom-color: rgba(217, 190, 130, 0.28);

    box-shadow: 0 12rpx 30rpx rgba(11, 11, 11, 0.18);
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

    background-position: center top;

    background-repeat: no-repeat;

    background-size: cover;
}

.staff-booking-page__base-mask {
    z-index: 1;

    background: linear-gradient(
        180deg,
        rgba(11, 11, 11, 0.06) 0%,

        rgba(11, 11, 11, 0.2) 42%,

        rgba(11, 11, 11, 0.62) 100%
    );
}

.staff-booking-page__focus-mask {
    z-index: 1;

    background: linear-gradient(
        180deg,
        rgba(11, 11, 11, 0) 0%,

        rgba(11, 11, 11, 0.1) 26%,

        rgba(25, 23, 19, 0.76) 100%
    );
}

.staff-booking-page__loading,
.staff-booking-page__error,
.staff-booking-page__content {
    position: relative;

    z-index: 2;

    height: 100%;

    box-sizing: border-box;
}

.staff-booking-page__loading,
.staff-booking-page__error {
    display: flex;

    align-items: center;

    justify-content: center;

    padding: 45rpx 37rpx 30rpx;
}

.state-card {
    width: 100%;

    max-width: 640rpx;

    padding: 22rpx;

    background: rgba(255, 253, 248, 0.94);

    border-color: rgba(217, 190, 130, 0.72);

    backdrop-filter: blur(18rpx);

    -webkit-backdrop-filter: blur(18rpx);
}

.staff-booking-page__error {
    flex-direction: column;

    gap: 18rpx;

    color: var(--wm-text-primary, #111111);
}

.staff-booking-page__error-actions {
    display: flex;

    align-items: center;

    justify-content: center;

    gap: 16rpx;
}

.staff-booking-page__error-link {
    min-height: 64rpx;

    padding: 0 18rpx;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    font-size: 24rpx;

    font-weight: 600;

    color: rgba(255, 255, 255, 0.92);

    text-shadow: 0 4rpx 12rpx rgba(11, 11, 11, 0.2);
}

.staff-booking-page__error-divider {
    width: 1rpx;

    height: 28rpx;

    background: rgba(255, 255, 255, 0.38);
}

.staff-booking-page__content {
    display: flex;

    flex-direction: column;

    padding: 24rpx 36rpx 32rpx;

    overflow: hidden;
}

.staff-booking-page__main {
    margin-top: auto;

    display: flex;

    flex-direction: column;

    gap: 12rpx;

    padding-bottom: 8rpx;
}

.staff-booking-page__title {
    display: block;

    max-width: 688rpx;

    font-size: 46rpx;

    line-height: 1.15;

    font-weight: 900;

    color: var(--wm-text-inverse, #fffdf8);

    text-shadow: 0 8rpx 20rpx rgba(11, 11, 11, 0.28);
}

.staff-booking-page__desc {
    display: block;

    max-width: 688rpx;

    font-size: 25rpx;

    line-height: 1.45;

    color: rgba(255, 253, 248, 0.92);

    text-shadow: 0 4rpx 12rpx rgba(11, 11, 11, 0.2);
}

.staff-booking-page__assist-text {
    display: block;

    font-size: 22rpx;

    line-height: 1.4;

    color: rgba(241, 229, 200, 0.92);

    text-shadow: 0 4rpx 12rpx rgba(11, 11, 11, 0.18);
}

.step-badge {
    align-self: flex-start;

    display: inline-flex;

    align-items: center;

    max-width: 640rpx;

    min-height: 38rpx;

    padding: 0 14rpx;

    border-radius: 999rpx;

    background: rgba(255, 253, 248, 0.92);

    border: 1rpx solid rgba(217, 190, 130, 0.9);

    box-shadow: 0 8rpx 20rpx rgba(11, 11, 11, 0.1);

    box-sizing: border-box;
}

.step-badge__count,
.step-badge__divider,
.step-badge__label {
    display: block;

    font-size: 22rpx;

    line-height: 1;

    font-weight: 900;

    color: #6f521b;
}

.step-badge__count {
    flex-shrink: 0;
}

.step-badge__divider {
    flex-shrink: 0;

    padding: 0 2rpx;

    color: rgba(154, 107, 53, 0.72);
}

.step-badge__label {
    min-width: 0;

    max-width: 460rpx;

    overflow: hidden;

    text-overflow: ellipsis;

    white-space: nowrap;
}

.choice-scroll-shell {
    position: relative;

    width: 100%;

    margin-top: 10rpx;
}

.choice-scroll-hint {
    position: absolute;

    top: -46rpx;

    right: 0;

    z-index: 3;

    display: inline-flex;

    align-items: center;

    gap: 6rpx;

    min-height: 36rpx;

    padding: 0 14rpx;

    border-radius: 999rpx;

    background: rgba(255, 253, 248, 0.92);

    border: 1rpx solid rgba(217, 190, 130, 0.72);

    box-shadow: 0 8rpx 20rpx rgba(11, 11, 11, 0.16);

    pointer-events: none;

    box-sizing: border-box;
}

.choice-scroll-hint__text,
.choice-scroll-hint__arrow {
    display: block;

    font-size: 20rpx;

    line-height: 1;

    font-weight: 900;

    color: #6f521b;
}

.choice-scroll-hint__arrow {
    transform: translateY(-1rpx);

    font-size: 28rpx;

    color: var(--wm-color-primary, #191713);
}

.choice-scroll {
    width: 100%;

    white-space: nowrap;
}

.choice-list {
    display: inline-flex;

    gap: 18rpx;

    padding: 4rpx 112rpx 6rpx 0;

    min-width: 100%;

    box-sizing: border-box;
}

.choice-list--compact {
    width: 100%;

    min-width: 100%;

    display: flex;

    gap: 14rpx;

    padding-right: 0;
}

.choice-list--package {
    gap: 14rpx;

    min-width: 100%;
}

.choice-list--role {
    min-width: auto;
}

.choice-card {
    width: 304rpx;

    flex: 0 0 304rpx;

    height: 126rpx;

    padding: 0;

    border-radius: 34rpx;

    background: rgba(255, 253, 248, 0.92);

    border: 1rpx solid rgba(216, 201, 173, 0.9);

    box-sizing: border-box;

    backdrop-filter: blur(10rpx);

    -webkit-backdrop-filter: blur(10rpx);

    box-shadow: 0 16rpx 34rpx rgba(11, 11, 11, 0.12);
}

.choice-card:active {
    transform: scale(0.985);
}

.choice-card--package {
    width: 332rpx;

    flex: 0 0 332rpx;
}

.choice-card--addon {
    flex: 1 1 0;

    width: auto;

    min-width: 0;
}

.choice-card--recommended {
    background: linear-gradient(180deg, rgba(255, 249, 232, 0.98) 0%, rgba(255, 253, 248, 0.96) 100%);

    border-color: var(--wm-color-champagne, #d9be82);

    box-shadow: 0 18rpx 38rpx rgba(184, 149, 74, 0.22);
}

.choice-list--role .choice-card {
    width: 304rpx;

    flex: 0 0 304rpx;
}

.choice-card--role {
    width: 304rpx;

    flex: 0 0 304rpx;
}

.choice-card--selected {
    background: rgba(255, 253, 248, 0.98);

    border-color: var(--wm-color-champagne, #d9be82);

    box-shadow: 0 18rpx 38rpx rgba(11, 11, 11, 0.2);
}

.choice-card::before {
    opacity: 0.72;
}

.choice-card__body {
    position: relative;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 14rpx;

    height: 100%;

    padding: 28rpx;

    box-sizing: border-box;
}

.choice-card__recommend-badge {
    position: absolute;

    top: 8rpx;

    right: 14rpx;

    height: 32rpx;

    padding: 0 12rpx;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    border-radius: 999rpx;

    background: var(--wm-color-primary, #191713);

    color: var(--wm-color-champagne, #d9be82);

    font-size: 18rpx;

    line-height: 32rpx;

    font-weight: 900;

    box-shadow: 0 8rpx 18rpx rgba(11, 11, 11, 0.16);
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

    font-size: 25rpx;

    line-height: 1.3;

    font-weight: 900;

    color: #111111;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;
}

.choice-card__subline {
    display: block;

    font-size: 22rpx;

    line-height: 1.25;

    font-weight: 700;

    color: var(--wm-text-secondary, #665e52);

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;
}

.choice-card__check {
    width: 40rpx;

    height: 40rpx;

    border-radius: 50%;

    background: var(--wm-color-primary, #191713);

    color: var(--wm-color-champagne, #d9be82);

    font-size: 20rpx;

    font-weight: 700;

    line-height: 40rpx;

    text-align: center;

    flex-shrink: 0;

    box-shadow: 0 8rpx 18rpx rgba(11, 11, 11, 0.2);
}

.choice-card--package.choice-card--recommended .choice-card__check {
    margin-top: 30rpx;
}

.empty-state {
    padding: 28rpx 30rpx;

    border-radius: var(--wm-radius-card-lg, 28rpx);

    background: rgba(255, 253, 248, 0.92);

    border: 1rpx solid rgba(231, 226, 214, 0.96);

    backdrop-filter: blur(18rpx);

    -webkit-backdrop-filter: blur(18rpx);
}

.empty-state__text {
    font-size: 24rpx;

    line-height: 1.5;

    color: #5f5a50;
}

.staff-booking-page :deep(.wm-action-area) {
    align-items: stretch;

    gap: 0;

    flex-shrink: 0;

    padding: 18rpx 24rpx calc(34rpx + env(safe-area-inset-bottom));

    background: transparent;

    border-top: none;

    box-shadow: none;
}

.booking-action-bar {
    width: 100%;
}

.booking-action-bar__shell {
    display: flex;

    align-items: center;

    justify-content: flex-start;

    gap: 14rpx;

    padding: 18rpx;

    border-radius: 34rpx;

    background: rgba(255, 253, 248, 0.94);

    border: 1rpx solid rgba(231, 226, 214, 0.96);

    backdrop-filter: blur(20rpx);

    -webkit-backdrop-filter: blur(20rpx);

    box-shadow: 0 18rpx 34rpx rgba(11, 11, 11, 0.18);
}

.total-pill {
    flex-shrink: 0;

    display: inline-flex;

    align-items: center;

    min-height: 92rpx;

    width: 212rpx;

    max-width: none;

    padding: 20rpx 18rpx;

    justify-content: center;

    border-radius: 42rpx;

    background: var(--wm-color-gold-soft, #f1e5c8);

    border: 1rpx solid var(--wm-color-champagne, #d9be82);
}

.total-pill__text {
    font-size: 25rpx;

    line-height: 1.2;

    font-weight: 700;

    color: #111111;

    white-space: nowrap;
}

.booking-action-bar__buttons {
    display: flex;

    gap: 12rpx;

    flex: 0 0 auto;

    margin-left: auto;

    min-width: 0;
}

.booking-action-btn {
    flex-shrink: 0;
}

.booking-action-btn--prev {
    width: 160rpx;
}

.booking-action-btn--next {
    width: 196rpx;

    min-width: 0;
}

.summary-popup {
    position: fixed;

    inset: 0;

    z-index: 120;
}

.summary-popup__dialog {
    position: fixed;

    z-index: 121;

    display: flex;

    align-items: center;

    justify-content: center;

    inset: 0;

    padding: 48rpx 32rpx;

    box-sizing: border-box;
}

.summary-popup__panel {
    display: flex;

    flex-direction: column;

    width: 640rpx;

    max-width: calc(100vw - 64rpx);

    max-height: 68vh;

    box-sizing: border-box;

    overflow: hidden;
}

.summary-popup__header {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20rpx;
}

.summary-popup__title-group {
    display: flex;

    flex-direction: column;

    gap: 8rpx;

    min-width: 0;
}

.summary-popup__eyebrow {
    display: block;

    font-size: 24rpx;

    line-height: 1.2;

    font-weight: 900;

    color: var(--wm-color-gold, #b8954a);
}

.summary-popup__title {
    display: block;

    font-size: 36rpx;

    line-height: 1.2;

    font-weight: 900;

    color: #1a1a1a;
}

.summary-popup__count {
    flex-shrink: 0;

    padding: 12rpx 20rpx;

    font-size: 22rpx;

    line-height: 1.2;

    font-weight: 900;

    color: #7d4c35;

    border-radius: 999rpx;

    background: #f6e2d6;

    border: 1rpx solid #e9c7a7;
}

.summary-popup__list {
    margin-top: 24rpx;

    display: flex;

    flex-direction: column;

    gap: 0rpx;

    min-height: 0;

    max-height: 28vh;

    overflow-y: auto;

    padding-right: 2rpx;
}

.summary-popup__item {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 24rpx;

    min-height: 66rpx;

    padding: 12rpx 0;

    border-bottom: 1rpx solid #f3eadf;
}

.summary-popup__item:last-child {
    border-bottom: none;
}

.summary-popup__item-label {
    display: block;

    flex: 1;

    min-width: 0;

    overflow: hidden;

    text-overflow: ellipsis;

    white-space: nowrap;

    font-size: 26rpx;

    line-height: 1.3;

    font-weight: 700;

    color: #2a2824;
}

.summary-popup__item-price {
    flex-shrink: 0;

    max-width: 220rpx;

    overflow: hidden;

    text-overflow: ellipsis;

    white-space: nowrap;

    font-size: 26rpx;

    line-height: 1.3;

    font-weight: 900;

    text-align: right;

    color: #7d4c35;
}

.summary-popup__item-price--package {
    color: #1a1a1a;
}

.summary-popup__total {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 16rpx;

    margin-top: 20rpx;

    min-height: 80rpx;

    padding: 0 28rpx;

    border-radius: 28rpx;

    background: #f8f1e1;

    border: 1rpx solid #d9be82;
}

.summary-popup__total-label {
    font-size: 28rpx;

    line-height: 1.2;

    font-weight: 900;

    color: #1a1a1a;
}

.summary-popup__total-value {
    font-size: 36rpx;

    line-height: 1.2;

    font-weight: 900;

    color: #0b0b0b;
}

.summary-popup__actions {
    margin-top: 24rpx;
}
</style>
