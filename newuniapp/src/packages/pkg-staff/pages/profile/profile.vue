<template>
    <page-shell
        title="我的资料"
        bg="page"
        :show-back="true"
        :pull-refresh="true"
        @refresh="onRefresh"
    >
        <staff-guard
            v-if="!guardAllowed"
            :phase="guardPhase"
            :reason="guardReason"
            :error="guardError"
            @retry="onGuardRetry"
            @action="onGuardAction"
        />

        <view v-else class="page-wrap prof">
            <view v-if="phase === 'loading' || phase === 'idle'" class="prof__loading">
                <base-skeleton :rows="4" variant="card" />
            </view>

            <base-empty
                v-else-if="phase === 'error'"
                status="error"
                :error-kind="view?.errorKind || 'unknown'"
                :error-text="view?.title"
                :desc="view?.desc"
                :primary-action="view?.primaryAction"
                fixed
                @primary="onPrimary"
                @retry="() => load()"
            />

            <template v-else>
                <!-- 身份名片：先确认「我是谁、资料是否可展示」 -->
                <view class="hero">
                    <view class="hero__top">
                        <text class="hero__eyebrow">STAFF PROFILE</text>
                        <view class="hero__status">
                            <view
                                class="hero__status-dot"
                                :class="{ 'is-active': Number(info.status) === 1 }"
                            />
                            <text>{{ info.status_desc || '状态待确认' }}</text>
                        </view>
                    </view>

                    <view class="hero__person">
                        <image
                            v-if="info.avatar && !avatarFailed"
                            class="hero__avatar"
                            :src="info.avatar"
                            mode="aspectFill"
                            aria-label="服务人员头像"
                            @error="avatarFailed = true"
                        />
                        <view v-else class="hero__avatar hero__avatar--text">
                            <text class="hero__initial">{{ initial }}</text>
                        </view>
                        <view class="hero__body">
                            <view class="hero__name-row">
                                <text class="hero__name ellipsis">{{
                                    info.name || '服务人员'
                                }}</text>
                                <view v-if="ratingText" class="hero__rating">
                                    <text class="hero__rating-star">★</text>
                                    <text class="t-num">{{ ratingText }}</text>
                                </view>
                                <view v-else class="hero__rating hero__rating--muted">
                                    <text>暂无评分</text>
                                </view>
                            </view>
                            <text class="hero__meta ellipsis">{{ profileMeta }}</text>
                            <text class="hero__price ellipsis t-num">
                                {{ info.price_text || '展示价格待完善' }}
                            </text>
                        </view>
                    </view>

                    <view class="hero__foot">
                        <text class="hero__foot-item t-num">工号 {{ info.sn || '—' }}</text>
                        <text v-if="joinedText" class="hero__foot-item t-num"
                            >入驻 {{ joinedText }}</text
                        >
                    </view>
                </view>

                <!-- 审核结论决定资料是否能被客户看到，单独提级展示 -->
                <view class="audit" :class="`audit--${audit.tone}`">
                    <view class="audit__icon-wrap">
                        <tn-icon :name="audit.icon" :size="28" class="audit__icon" />
                    </view>
                    <view class="audit__body">
                        <text class="audit__title">{{ audit.title }}</text>
                        <text class="audit__desc">{{ audit.desc }}</text>
                    </view>
                </view>

                <section-header title="数据概览" />
                <base-card compact>
                    <view class="metrics">
                        <view
                            v-for="item in metrics"
                            :key="item.key"
                            class="metric"
                            hover-class="is-pressed"
                            aria-role="button"
                            :aria-label="`${item.label} ${item.value}，查看${item.label}`"
                            @tap="go(item.path)"
                        >
                            <text class="metric__value t-num">{{ item.value }}</text>
                            <text class="metric__label">{{ item.label }}</text>
                        </view>
                    </view>
                </base-card>

                <section-header
                    title="基本资料"
                    :more="canWrite ? '编辑资料' : ''"
                    @more="goEditProfile"
                />
                <base-card compact>
                    <view
                        v-for="(row, index) in infoRows"
                        :key="row.key"
                        class="row"
                        :class="{ 'row--divided': index > 0 }"
                    >
                        <text class="row__label">{{ row.label }}</text>
                        <text class="row__value t-num" :class="{ 'row__value--muted': row.muted }">{{
                            row.value || '未填写'
                        }}</text>
                    </view>
                </base-card>

                <section-header title="专业标签" />
                <base-card compact>
                    <view v-if="tags.length" class="tags">
                        <status-tag
                            v-for="tag in tags"
                            :key="tag"
                            :label="tag"
                            tone="neutral"
                            size="sm"
                            :dot="false"
                        />
                    </view>
                    <view v-else class="tags-empty">
                        <text class="tags-empty__text">暂未设置专业标签</text>
                    </view>

                    <view
                        v-if="tagReview"
                        class="tag-review"
                        :class="`tag-review--${tagReview.tone}`"
                    >
                        <tn-icon name="tips" :size="24" class="tag-review__icon" />
                        <view class="tag-review__body">
                            <text class="tag-review__title">{{ tagReview.title }}</text>
                            <text class="tag-review__desc">{{ tagReview.desc }}</text>
                        </view>
                    </view>
                </base-card>

                <template v-if="listIntro || introText || serviceDesc">
                    <section-header title="个人介绍" />
                    <base-card compact>
                        <view v-if="listIntro" class="copy">
                            <text class="copy__label">列表页简介</text>
                            <text class="copy__text">{{ listIntro }}</text>
                        </view>
                        <view
                            v-if="introText"
                            class="copy"
                            :class="{ 'copy--divided': !!listIntro }"
                        >
                            <text class="copy__label">个人简介</text>
                            <text class="copy__text">{{ introText }}</text>
                        </view>
                        <view
                            v-if="serviceDesc"
                            class="copy"
                            :class="{ 'copy--divided': !!(listIntro || introText) }"
                        >
                            <text class="copy__label">服务说明</text>
                            <text class="copy__text">{{ serviceDesc }}</text>
                        </view>
                    </base-card>
                </template>

                <section-header title="资质认证" />
                <base-card compact clickable aria-label="查看我的资质证书" @tap="go(certificatePath)">
                    <view class="cert">
                        <view class="cert__icon-wrap">
                            <tn-icon name="medal" :size="30" class="cert__icon" />
                        </view>
                        <view class="cert__body">
                            <text class="cert__title">我的证书</text>
                            <text class="cert__desc">查看认证证书、有效期与审核结果</text>
                        </view>
                        <tn-icon name="right" :size="22" class="cert__arrow" />
                    </view>
                </base-card>
            </template>
        </view>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
/**
 * 服务人员：我的资料。
 *
 * P0 起资料可在本端编辑（profile-edit）：基础资料保存后即时生效，
 * 标签变更走审核（tag_action=pending）；手机号变更需短信验证码。
 * 本页仍承担「当前值 + 审核结论 + 标签申请状态」的说明职责 ——
 * 改动是否可展示由审核状态决定，编辑页说了不算，这里说了算。
 */
import { computed, reactive, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { formatDate } from '@shared/utils/format'
import { getStaffCenterProfile } from '@/api/staff-center'
import StaffGuard from '../../components/staff-guard/staff-guard.vue'
import { useStaffGuard } from '@/composables/useStaffGuard'
import { useStaffWriteGate } from '@/composables/useStaffWriteGate'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { navigate } from '@/utils/link'
import { toast } from '@/composables/useToast'
import { haptic } from '@/utils/haptics'
import type { LoadPhase } from '@/api/types'

const certificatePath = '/packages/pkg-staff/pages/certificate-list/certificate-list'

/** 写能力灰度开关：关闭时「编辑资料」入口不渲染（基础资料页保持只读浏览） */
const { canWrite } = useStaffWriteGate()
const {
    guardPhase,
    guardReason,
    guardError,
    guardAllowed,
    verify,
    onGuardRetry,
    onGuardAction
} = useStaffGuard(() => load(true))

function go(path: string) {
    haptic('light', 'staff-profile-nav')
    navigate({ path })
}

/** 编辑资料入口（P0 写能力） */
function goEditProfile() {
    haptic('light', 'staff-profile-edit')
    navigate({ path: '/packages/pkg-staff/pages/profile-edit/profile-edit' })
}

const phase = ref<LoadPhase>('idle')
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))
const info = reactive<Record<string, any>>({})
const avatarFailed = ref(false)
const hasLoaded = ref(false)
const loading = ref(false)

function toNumber(value: unknown) {
    const n = Number(value ?? 0)
    return Number.isFinite(n) ? n : 0
}

function normalizeTags(value: unknown) {
    if (Array.isArray(value)) return value.map(String).filter(Boolean)
    if (typeof value === 'string' && value.trim()) {
        return value
            .split(/[,，]/)
            .map((item) => item.trim())
            .filter(Boolean)
    }
    return []
}

function maskMobile(value: unknown) {
    const mobile = String(value || '')
    return mobile.replace(/^(\d{3})\d{4}(\d{4})$/, '$1****$2')
}

const initial = computed(() => String(info.name || '服').slice(0, 1))
const ratingText = computed(() => {
    const rating = toNumber(info.rating)
    const reviewCount = toNumber(info.review_count)
    if (rating <= 0 || reviewCount <= 0) return ''
    return rating.toFixed(1)
})
const joinedText = computed(() => formatDate(info.create_time, 'YYYY-MM-DD'))
const profileMeta = computed(() => {
    const years = toNumber(info.experience_years)
    return [
        info.category_name || '未分类',
        years > 0 ? `${years} 年经验` : '经验待完善'
    ].join(' · ')
})

const audit = computed(() => {
    const status = Number(info.audit_status)
    if (status === 1) {
        return {
            tone: 'success',
            icon: 'check',
            title: '资料审核已通过',
            desc: '当前资料可以正常展示给客户'
        }
    }
    if (status === 2) {
        return {
            tone: 'danger',
            icon: 'warning',
            title: '资料审核未通过',
            desc: '请联系运营确认需要调整的资料'
        }
    }
    return {
        tone: 'warning',
        icon: 'tips',
        title: '资料审核中',
        desc: '审核通过后，更新内容才会在前台生效'
    }
})

const metrics = computed(() => [
    {
        key: 'order',
        label: '订单',
        value: toNumber(info.orderCount),
        path: '/packages/pkg-staff/pages/order-list/order-list'
    },
    {
        key: 'work',
        label: '作品',
        value: toNumber(info.workCount),
        path: '/packages/pkg-staff/pages/work-list/work-list'
    },
    {
        key: 'package',
        label: '套餐',
        value: toNumber(info.packageCount),
        path: '/packages/pkg-staff/pages/package-list/package-list'
    },
    {
        key: 'addon',
        label: '附加项',
        value: toNumber(info.addonCount),
        path: '/packages/pkg-staff/pages/addon-list/addon-list'
    },
    {
        key: 'schedule',
        label: '档期',
        value: toNumber(info.scheduleCount),
        path: '/packages/pkg-staff/pages/schedule/schedule'
    }
])

const infoRows = computed(() => [
    { key: 'sn', label: '工号', value: String(info.sn || '') },
    {
        key: 'mobile',
        label: '手机号',
        value: maskMobile(info.mobile_full || info.mobile),
        muted: true
    },
    { key: 'category', label: '服务分类', value: String(info.category_name || '') },
    {
        key: 'experience',
        label: '从业年限',
        value: toNumber(info.experience_years) > 0 ? `${toNumber(info.experience_years)} 年` : ''
    },
    { key: 'price', label: '展示价格', value: String(info.price_text || '') },
    { key: 'status', label: '接单状态', value: String(info.status_desc || '') }
])

const tags = computed(() => normalizeTags(info.tag_names))
const pendingTags = computed(() => normalizeTags(info.pending_tag_names))

const tagReview = computed(() => {
    const status = info.tag_apply_status
    if (status === null || status === undefined || status === '') return null
    if (Number(status) === 0) {
        return {
            tone: 'warning',
            title: '标签变更审核中',
            desc: pendingTags.value.length
                ? `待审核标签：${pendingTags.value.join('、')}`
                : '审核通过后将更新展示标签'
        }
    }
    if (Number(status) === 2) {
        return {
            tone: 'danger',
            title: '标签申请未通过',
            desc: String(info.tag_apply_reject_reason || '请联系运营确认后重新提交')
        }
    }
    return null
})

const listIntro = computed(() => String(info.list_intro || '').trim())
const introText = computed(() => String(info.profile || '').trim())
const serviceDesc = computed(() => String(info.service_desc || '').trim())

function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        load()
        return
    }
    if (kind === 'reselect' || kind === 'back') {
        uni.navigateBack()
        return
    }
    if (kind === 'login') {
        uni.navigateTo({ url: '/pages/login/login' })
    }
}

function assign(target: Record<string, any>, source: any) {
    Object.keys(target).forEach((key) => delete target[key])
    if (source && typeof source === 'object') Object.assign(target, source)
}

async function load(forceGuard = false) {
    if (loading.value) return
    loading.value = true
    try {
        if (!(await verify(forceGuard))) return
        if (!hasLoaded.value) phase.value = 'loading'
        rawError.value = null
        const res = (await getStaffCenterProfile()) || {}
        if (!res || Array.isArray(res) || Object.keys(res).length === 0) {
            throw new Error('invalid-staff-profile')
        }

        assign(info, res)
        avatarFailed.value = false
        hasLoaded.value = true
        phase.value = 'ready'
    } catch (e) {
        rawError.value = e
        if (hasLoaded.value) {
            phase.value = 'ready'
            toast.error('刷新失败，请稍后重试')
        } else {
            phase.value = 'error'
        }
    } finally {
        loading.value = false
    }
}

onShow(() => {
    void load()
})

function onRefresh(done: () => void) {
    Promise.resolve(load(true)).finally(done)
}
</script>

<style lang="scss" scoped>
.prof {
    box-sizing: border-box;
    padding: var(--sp-2) var(--page-gutter) calc(var(--sp-5) + var(--safe-bottom));

    &__loading {
        padding-top: var(--sp-2);
    }
}

/* 身份名片 */
.hero {
    padding: var(--sp-4);
    border: 1rpx solid var(--c-brand-text);
    border-radius: var(--r-lg);
    background: linear-gradient(145deg, var(--c-dark-1), var(--c-dark-2));

    &__top,
    &__status,
    &__person,
    &__name-row,
    &__rating,
    &__foot {
        display: flex;
        align-items: center;
    }

    &__top {
        justify-content: space-between;
        gap: var(--sp-3);
    }

    &__eyebrow {
        font-family: var(--font-serif);
        font-size: var(--fs-xs);
        letter-spacing: 0.16em;
        color: var(--c-brand-soft);
    }

    &__status {
        flex-shrink: 0;
        gap: var(--sp-1);
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
    }

    &__status-dot {
        width: 12rpx;
        height: 12rpx;
        border-radius: var(--r-pill);
        background: var(--c-warning-on-dark);

        &.is-active {
            background: var(--c-success-on-dark);
        }
    }

    &__person {
        gap: var(--sp-3);
        margin-top: var(--sp-4);
    }

    &__avatar {
        flex-shrink: 0;
        width: 132rpx;
        height: 132rpx;
        border: 2rpx solid var(--c-brand-soft);
        border-radius: var(--r-pill);
        background: var(--c-dark-3);

        &--text {
            display: flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
        }
    }

    &__initial {
        font-family: var(--font-serif);
        font-size: var(--fs-2xl);
        color: var(--c-brand-soft);
    }

    &__body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
    }

    &__name-row {
        min-width: 0;
        gap: var(--sp-2);
    }

    &__name {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xl);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
        color: var(--c-on-dark);
    }

    &__rating {
        flex-shrink: 0;
        gap: var(--sp-1);
        padding: var(--sp-1) var(--sp-2);
        border-radius: var(--r-pill);
        background: var(--c-dark-3);
        font-size: var(--fs-xs);
        color: var(--c-on-dark);
    }

    &__rating--muted {
        color: var(--c-on-dark-2);
    }

    &__rating-star {
        color: var(--c-brand-soft);
    }

    &__meta,
    &__price {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-on-dark-2);
    }

    &__price {
        color: var(--c-brand-soft);
    }

    &__foot {
        justify-content: space-between;
        gap: var(--sp-3);
        margin-top: var(--sp-4);
        padding-top: var(--sp-3);
        border-top: 1rpx solid var(--c-dark-3);
    }

    &__foot-item {
        min-width: 0;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-on-dark-2);
        overflow-wrap: anywhere;
    }
}

/* 审核结论 */
.audit {
    display: flex;
    align-items: flex-start;
    gap: var(--sp-3);
    margin-top: var(--sp-2);
    padding: var(--sp-3);
    border: 1rpx solid;
    border-radius: var(--r-md);

    &--success {
        border-color: var(--c-success-border);
        background: var(--c-success-bg);

        .audit__icon {
            color: var(--c-success);
        }
    }

    &--warning {
        border-color: var(--c-warning-border);
        background: var(--c-warning-bg);

        .audit__icon {
            color: var(--c-warning);
        }
    }

    &--danger {
        border-color: var(--c-danger-border);
        background: var(--c-danger-bg);

        .audit__icon {
            color: var(--c-danger);
        }
    }

    &__icon-wrap {
        flex-shrink: 0;
        width: 56rpx;
        height: 56rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--r-pill);
        background: var(--c-surface);
    }

    &__body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
        color: var(--c-ink);
    }

    &__desc {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }
}

/* 数据概览 */
.metrics {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
}

.metric {
    min-width: 0;
    min-height: 112rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: var(--sp-1);
    padding: var(--sp-2) 0;
    transition: transform var(--dur-press) var(--ease-out);

    &.is-pressed {
        transform: scale(0.96);
    }

    & + & {
        border-left: 1rpx solid var(--c-line);
    }

    &__value {
        font-family: var(--font-serif);
        font-size: var(--fs-xl);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
        color: var(--c-brand-text);
        font-variant-numeric: tabular-nums;
    }

    &__label {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
        white-space: nowrap;
    }
}

/* 信息行 */
.row {
    display: flex;
    align-items: baseline;
    gap: var(--sp-3);
    min-height: 80rpx;
    padding: var(--sp-2) 0;

    &--divided {
        border-top: 1rpx solid var(--c-line);
    }

    &__label {
        flex-shrink: 0;
        width: 152rpx;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }

    &__value {
        flex: 1;
        min-width: 0;
        text-align: right;
        font-size: var(--fs-md);
        line-height: var(--lh-body);
        color: var(--c-ink);
        overflow-wrap: anywhere;
    }

    &__value--muted {
        color: var(--c-ink-2);
        font-variant-numeric: tabular-nums;
    }
}

/* 专业标签 */
.tags {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-2);
}

.tags-empty {
    padding: var(--sp-3) 0;

    &__text {
        font-size: var(--fs-md);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }
}

.tag-review {
    display: flex;
    align-items: flex-start;
    gap: var(--sp-2);
    margin-top: var(--sp-3);
    padding: var(--sp-3);
    border-radius: var(--r-sm);

    &--warning {
        background: var(--c-warning-bg);

        .tag-review__icon {
            color: var(--c-warning);
        }
    }

    &--danger {
        background: var(--c-danger-bg);

        .tag-review__icon {
            color: var(--c-danger);
        }
    }

    &__icon {
        flex-shrink: 0;
        margin-top: 4rpx;
    }

    &__body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
    }

    &__title {
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
        color: var(--c-ink);
    }

    &__desc {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
        overflow-wrap: anywhere;
    }
}

/* 介绍 */
.copy {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);

    &--divided {
        margin-top: var(--sp-3);
        padding-top: var(--sp-3);
        border-top: 1rpx solid var(--c-line);
    }

    &__label {
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
        color: var(--c-ink-2);
    }

    &__text {
        font-size: var(--fs-md);
        line-height: var(--lh-loose);
        color: var(--c-ink);
        white-space: pre-wrap;
        overflow-wrap: anywhere;
    }
}

/* 证书入口 */
.cert {
    display: flex;
    align-items: center;
    gap: var(--sp-3);

    &__icon-wrap {
        flex-shrink: 0;
        width: 64rpx;
        height: 64rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--r-pill);
        background: var(--c-brand-bg);
    }

    &__icon {
        color: var(--c-brand-text);
    }

    &__body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
        color: var(--c-ink);
    }

    &__desc {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }

    &__arrow {
        flex-shrink: 0;
        color: var(--c-ink-3);
    }
}

/* v10 §3.13：减弱动效下按压不缩放 */
@media (prefers-reduced-motion: reduce) {
    .metric {
        transition: none;
    }

    .metric.is-pressed {
        transform: none;
    }
}
</style>
