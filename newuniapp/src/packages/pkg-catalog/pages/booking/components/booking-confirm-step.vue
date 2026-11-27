<template>
    <!--
        确认页是「一份能逐项核对的确认单」，不是收银台：
        · 墨色摘要卡负责 3 秒确认「哪天 / 什么套餐 / 跟谁 / 在哪」；
        · 01–06 六个块各回答一个问题，每块可单独回改；
        · 报价失败只坏 05/06，摘要与已选信息始终可读；
        · 金额只读服务端 quote，前端不做加减。
    -->
    <view class="panel cf">
        <!-- 00 服务摘要 -->
        <view class="cf-sum">
            <view class="cf-sum__top">
                <view class="cf-sum__datewrap">
                    <text class="cf-sum__eyebrow">ORDER REVIEW</text>
                    <text class="cf-sum__date">{{ dateDay }}</text>
                    <text class="cf-sum__week">{{ dateYearWeek }}</text>
                </view>
                <status-tag :label="orderBadge.label" :tone="orderBadge.tone" />
            </view>
            <text class="cf-sum__name ellipsis-2">{{ currentPackageName || '服务套餐' }}</text>
            <view class="cf-sum__meta">
                <text class="cf-sum__host ellipsis">{{ staffName || '服务人员待安排' }}</text>
                <text class="cf-sum__dot" aria-hidden="true">·</text>
                <text class="cf-sum__city ellipsis">{{ cityVenueText }}</text>
            </view>
            <view class="cf-sum__lock" :class="`is-${lockState}`">
                <tn-icon
                    :name="lockState === 'failed' ? 'warning' : 'clock'"
                    :size="22"
                    class="cf-sum__lock-ic"
                />
                <text class="cf-sum__lock-text">{{ lockSummaryText }}</text>
                <text
                    v-if="!orderId"
                    class="cf-sum__edit"
                    hover-class="is-pressed"
                    aria-role="button"
                    aria-label="修改服务日期"
                    @tap="emit('openDatePicker')"
                    >{{ lockState === 'failed' ? '换日期' : '改期' }}</text
                >
            </view>
        </view>

        <!-- 订单已提交但服务端判定不可支付：页面进入「待确认」只读态 -->
        <view v-if="orderPendingConfirm" class="cf-alert cf-alert--warning">
            <tn-icon name="clock" :size="26" class="cf-alert__ic" />
            <view class="cf-alert__body">
                <text class="cf-alert__title">预约申请已提交，等待服务人员确认</text>
                <text class="cf-alert__text"
                    >订单当前状态为「待确认」，待服务人员确认档期后将进入「待支付」，届时可在订单详情中完成支付。</text
                >
            </view>
            <view class="cf-alert__btn" hover-class="is-pressed" @tap="emit('goOrderDetail')">
                <text class="cf-alert__btn-text">查看订单</text>
            </view>
        </view>

        <!-- 锁档失败：不踢回上一步，留在本页给「换日期」 -->
        <view v-else-if="lockState === 'failed'" class="cf-alert cf-alert--danger">
            <tn-icon name="warning" :size="26" class="cf-alert__ic" />
            <view class="cf-alert__body">
                <text class="cf-alert__title">该档期刚被订走</text>
                <text class="cf-alert__text"
                    >档期暂未预留成功，请换一个日期或服务人员。</text
                >
            </view>
            <view class="cf-alert__btn" hover-class="is-pressed" @tap="emit('openDatePicker')">
                <text class="cf-alert__btn-text">换日期</text>
            </view>
        </view>

        <!-- 锁档超时：可原地重锁，和「被别人订走」不是同一件事 -->
        <view v-else-if="lockState === 'expired'" class="cf-alert cf-alert--warning">
            <tn-icon name="clock" :size="26" class="cf-alert__ic" />
            <view class="cf-alert__body">
                <text class="cf-alert__title">档期保留已超时</text>
                <text class="cf-alert__text"
                    >可以重新锁定后继续提交申请；若已被他人预订，请换日期。</text
                >
            </view>
            <view class="cf-alert__btn" hover-class="is-pressed" @tap="emit('relock')">
                <text class="cf-alert__btn-text">重新锁档</text>
            </view>
        </view>

        <!-- 01 套餐与服务 -->
        <confirm-section
            index="01"
            title="套餐与服务"
            :action="orderId ? '' : '修改'"
            @action="emit('editStep', 'package')"
        >
            <view class="cf-row">
                <text class="cf-row__k">套餐</text>
                <text class="cf-row__v ellipsis">{{ currentPackageName || '服务套餐' }}</text>
                <text v-if="packagePriceText" class="cf-row__m">{{ packagePriceText }}</text>
            </view>
            <view v-if="packageDurationText" class="cf-row">
                <text class="cf-row__k">服务时长</text>
                <text class="cf-row__v">{{ packageDurationText }}</text>
            </view>
            <view class="cf-divider" aria-hidden="true" />
            <view class="cf-staff">
                <image
                    v-if="staffAvatar"
                    class="cf-staff__avatar"
                    :src="imageUrl(staffAvatar)"
                    mode="aspectFill"
                />
                <view v-else class="cf-staff__avatar cf-staff__avatar--blank" />
                <view class="cf-staff__body">
                    <text class="cf-staff__name ellipsis">{{ staffName || '平台安排' }}</text>
                    <text v-if="staffMetaText" class="cf-staff__meta ellipsis">{{
                        staffMetaText
                    }}</text>
                </view>
            </view>
            <view v-for="r in quoteRoleRows" :key="r.role_key" class="cf-row">
                <text class="cf-row__k">{{ r.role_label }}</text>
                <text class="cf-row__v ellipsis">{{ r.text }}</text>
            </view>
            <text v-if="packageRules" class="cf-note">{{ packageRules }}</text>
        </confirm-section>

        <!-- 02 附加项：套餐本身没有可加购项时整块隐藏，不给指向跳过步骤的「去挑选」 -->
        <confirm-section
            v-if="hasAvailableAddons || selectedAddonList.length"
            index="02"
            :title="addonTitle"
            :action="orderId ? '' : selectedAddonList.length ? '修改' : '去挑选'"
            @action="emit('editStep', 'addon')"
        >
            <template v-if="selectedAddonList.length">
                <view v-for="a in selectedAddonList" :key="addonId(a)" class="cf-line">
                    <text class="cf-line__n ellipsis">{{ a.name }}</text>
                    <text class="cf-line__m">+¥{{ money(addonPrice(a)) }}</text>
                </view>
                <view v-if="selectedAddonList.length > 1" class="cf-line cf-line--sub">
                    <text class="cf-line__n">小计</text>
                    <text class="cf-line__m">{{ addonSumText }}</text>
                </view>
            </template>
            <text v-else class="cf-empty-line">未加购附加项，可直接提交</text>
            <!-- 加购推荐：只给「还能加什么 + 多少钱」，不编百分比 -->
            <view v-if="upsellAddon && !orderId" class="cf-upsell">
                <text class="cf-upsell__t ellipsis">还可以加「{{ upsellAddon.name }}」</text>
                <view class="cf-upsell__btn" hover-class="is-pressed" @tap="emit('addUpsell')">
                    <text class="cf-upsell__btn-text"
                        >+¥{{ money(addonPrice(upsellAddon)) }} 加上</text
                    >
                </view>
            </view>
        </confirm-section>

        <!-- 03 联系人：id 挂在原生 view 上，小程序 boundingClientRect 才能拿到真实高度 -->
        <view id="cf-contact">
            <confirm-section
                index="03"
                title="联系人"
                :action="orderId ? '' : '修改'"
                :flash="flashTarget === 'contact'"
                @action="emit('openContact')"
            >
                <text class="cf-value" :class="{ 'is-missing': !contactLine }">{{
                    contactLine || '未填写联系人'
                }}</text>
                <text class="cf-note">用于档期确认与到场联系。</text>
                <text v-if="contactBlockError" class="cf-block-err" aria-live="polite">{{
                    contactBlockError
                }}</text>
            </confirm-section>
        </view>

        <!-- 04 服务地址 -->
        <view id="cf-address">
            <confirm-section
                index="04"
                title="服务地址"
                :action="orderId ? '' : '修改'"
                :flash="flashTarget === 'address'"
                @action="emit('openAddress')"
            >
                <view class="cf-region">
                    <text class="cf-region__text ellipsis">{{
                        serviceRegionText || '未选择服务地区'
                    }}</text>
                    <text class="cf-region__badge">定价地区</text>
                </view>
                <text
                    class="cf-value cf-value--addr ellipsis-2"
                    :class="{ 'is-missing': !serviceAddress }"
                    >{{ serviceAddress || '未填写详细地址' }}</text
                >
                <text class="cf-note">地区决定套餐价格；详细地址用于服务人员到场。</text>
                <text v-if="addressBlockError" class="cf-block-err" aria-live="polite">{{
                    addressBlockError
                }}</text>
            </confirm-section>
        </view>

        <!-- 05 价格明细 -->
        <confirm-section index="05" title="价格明细">
            <view v-if="quoteState === 'failed'" class="cf-alert cf-alert--danger">
                <tn-icon name="warning" :size="26" class="cf-alert__ic" />
                <view class="cf-alert__body">
                    <text class="cf-alert__title">报价没有算出来</text>
                    <text class="cf-alert__text"
                        >价格必须以服务端报价为准，请重新获取。你已选的套餐与附加项不会丢失。</text
                    >
                </view>
            </view>
            <template v-else-if="quoteState === 'ready'">
                <view class="cf-line">
                    <text class="cf-line__n">套餐金额</text>
                    <text class="cf-line__m">{{ quoteText('service_amount') }}</text>
                </view>
                <view class="cf-line">
                    <text class="cf-line__n">附加项（{{ selectedAddonList.length }} 项）</text>
                    <text class="cf-line__m">¥{{ money(addonOnlyAmount) }}</text>
                </view>
                <view v-if="rolePriceSum > 0" class="cf-line">
                    <text class="cf-line__n">管家 / 督导</text>
                    <text class="cf-line__m">¥{{ money(rolePriceSum) }}</text>
                </view>
                <view class="cf-total">
                    <text class="cf-total__k">合计</text>
                    <text class="cf-total__m">{{ quoteText('total_amount') }}</text>
                </view>
                <text class="cf-note">价格由系统按服务日期与地区计算，以提交时为准。</text>
            </template>
            <view v-else class="cf-sk" aria-label="正在核价">
                <view class="cf-sk__row" />
                <view class="cf-sk__row" />
                <view class="cf-sk__row cf-sk__row--total" />
                <text class="cf-note">正在核价…</text>
            </view>

            <view v-if="amountMismatch" class="cf-alert cf-alert--danger">
                <tn-icon name="warning" :size="26" class="cf-alert__ic" />
                <view class="cf-alert__body">
                    <text class="cf-alert__title">报价金额不一致</text>
                    <text class="cf-alert__text"
                        >定金 + 尾款 ≠ 总额，已为你阻断提交，请重新获取报价。</text
                    >
                </view>
            </view>
            <view v-if="quoteState === 'failed' || amountMismatch" class="cf-foot">
                <base-button
                    text="重新获取报价"
                    variant="ghost"
                    size="sm"
                    :block="false"
                    @click="emit('retryPreview')"
                />
            </view>
        </confirm-section>

        <!-- 06 支付安排：只回答「什么时候付多少」，不重复明细 -->
        <confirm-section v-if="quoteState !== 'failed'" index="06" title="支付安排">
            <view v-if="quoteState !== 'ready'" class="cf-sk">
                <view class="cf-sk__row" />
                <view class="cf-sk__row cf-sk__row--total" />
                <text class="cf-note">正在计算支付安排…</text>
            </view>
            <view v-else class="cf-pay">
                <view class="cf-pay__step is-now">
                    <view class="cf-pay__dot" aria-hidden="true" />
                    <view class="cf-pay__top">
                        <text class="cf-pay__label">{{
                            isFullPayment ? '确认后 · 支付全款' : '确认后 · 支付定金'
                        }}</text>
                        <text class="cf-pay__amt">{{
                            isFullPayment ? quoteText('total_amount') : depositText
                        }}</text>
                    </view>
                    <text class="cf-pay__sub">提交申请后待服务人员确认，确认后开放支付</text>
                </view>
                <view v-if="!isFullPayment" class="cf-pay__step">
                    <view class="cf-pay__dot" aria-hidden="true" />
                    <view class="cf-pay__top">
                        <text class="cf-pay__label">尾款</text>
                        <text class="cf-pay__amt">{{ balanceText }}</text>
                    </view>
                    <text class="cf-pay__sub">{{ balanceDueText }}</text>
                </view>
            </view>
        </confirm-section>

        <!-- 07 备注：不影响金额，放在价格之后，不打断核对节奏 -->
        <confirm-section
            title="备注"
            :action="orderId ? '' : remark ? '修改' : '填写'"
            @action="emit('openRemark')"
        >
            <text class="cf-value ellipsis" :class="{ 'is-missing': !remark }">{{
                remark || '选填，可填写特殊要求'
            }}</text>
        </confirm-section>

        <!-- 服务端校验：error 与 warn 分开，避免所有提示长一个样 -->
        <view v-if="validationErrors.length" class="cf-alert cf-alert--danger">
            <tn-icon name="warning" :size="26" class="cf-alert__ic" />
            <view class="cf-alert__body">
                <text v-for="(v, i) in validationErrors" :key="i" class="cf-alert__line">{{
                    v.message
                }}</text>
            </view>
        </view>
        <view v-if="validationWarns.length" class="cf-alert cf-alert--warning">
            <tn-icon name="warning" :size="26" class="cf-alert__ic" />
            <view class="cf-alert__body">
                <text v-for="(v, i) in validationWarns" :key="i" class="cf-alert__line">{{
                    v.message
                }}</text>
            </view>
        </view>

        <!-- 提交失败：留在本页就地重试，不换页 -->
        <view v-if="submitError" class="cf-alert cf-alert--danger">
            <tn-icon name="warning" :size="26" class="cf-alert__ic" />
            <view class="cf-alert__body">
                <text class="cf-alert__title">未能提交申请</text>
                <text class="cf-alert__text">{{ submitError }}</text>
            </view>
            <view
                v-if="orderId"
                class="cf-alert__btn"
                hover-class="is-pressed"
                @tap="emit('goOrderDetail')"
            >
                <text class="cf-alert__btn-text">查看订单</text>
            </view>
        </view>

        <!-- 08 协议：默认未勾；未勾不置灰按钮，点按在这里出红字 -->
        <view id="cf-agree" class="cf-agree" :class="{ 'cf-agree--flash': flashTarget === 'agree' }">
            <agreement-check
                :model-value="agreed"
                :error="agreeError"
                @update:model-value="emit('update:agreed', $event)"
            >
                <text>我已阅读并同意</text>
                <text
                    class="cf-agree__link"
                    hover-class="is-pressed"
                    @tap.stop="openAgreement('booking')"
                    >《预约服务协议》</text
                >
                <text>与</text>
                <text
                    class="cf-agree__link"
                    hover-class="is-pressed"
                    @tap.stop="openAgreement('aftersale')"
                    >《售后服务规则》</text
                >
                <text>，提交申请后待服务人员确认，确认后按约定支付。</text>
            </agreement-check>
        </view>
    </view>
</template>

<script setup lang="ts">
import { getCurrentInstance, nextTick } from 'vue'
import type { Tone } from '@shared/contracts'
import { navigate } from '@/utils/link'
import {
    addonId,
    addonPrice,
    money,
    type BookingFocusTarget,
    type BookingLockState,
    type BookingQuoteState,
    type QuoteRoleRow
} from '../booking-helpers'

const props = defineProps<{
    dateDay: string
    dateYearWeek: string
    orderBadge: { label: string; tone: Tone }
    currentPackageName: string
    staffName: string
    staffAvatar: string
    staffMetaText: string
    cityVenueText: string
    lockState: BookingLockState
    lockSummaryText: string
    orderId: number
    orderPendingConfirm: boolean
    packagePriceText: string
    packageDurationText: string
    packageRules: string
    quoteRoleRows: QuoteRoleRow[]
    /** 当前套餐是否绑定可加购附加项：为 false 时整块隐藏，避免出现空的「附加项」区 */
    hasAvailableAddons: boolean
    addonTitle: string
    selectedAddonList: any[]
    addonSumText: string
    upsellAddon: any
    contactLine: string
    contactBlockError: string
    serviceRegionText: string
    serviceAddress: string
    addressBlockError: string
    flashTarget: '' | BookingFocusTarget
    quote: any
    quoteState: BookingQuoteState
    addonOnlyAmount: number
    rolePriceSum: number
    amountMismatch: boolean
    isFullPayment: boolean
    depositText: string
    balanceText: string
    balanceDueText: string
    remark: string
    validationErrors: Array<{ message: string; level?: string }>
    validationWarns: Array<{ message: string; level?: string }>
    submitError: string
    agreed: boolean
    agreeError: string
    imageUrl: (url?: string) => string
}>()

const emit = defineEmits<{
    (e: 'openDatePicker'): void
    (e: 'goOrderDetail'): void
    (e: 'relock'): void
    (e: 'editStep', target: number | 'package' | 'addon'): void
    (e: 'addUpsell'): void
    (e: 'openContact'): void
    (e: 'openAddress'): void
    (e: 'openRemark'): void
    (e: 'retryPreview'): void
    (e: 'update:agreed', val: boolean): void
}>()

const instance = getCurrentInstance()

function quoteText(key: string) {
    const v = props.quote?.[key]
    if (v === null || v === undefined || v === '') return '—'
    return `¥${money(v)}`
}

/** 协议栏文案里的《预约服务协议》/《售后服务规则》可点开查看 */
function openAgreement(type: 'booking' | 'aftersale') {
    navigate({ path: '/pages/agreement/agreement', query: { type } })
}

/** 校验定位：在组件自身作用域内查询 #cf-contact / #cf-address / #cf-agree（小程序跨组件必须 .in(proxy)） */
function scrollToBlock(target: BookingFocusTarget) {
    const selector = target === 'contact' ? '#cf-contact' : target === 'address' ? '#cf-address' : '#cf-agree'
    const offset = target === 'agree' ? 160 : 120
    nextTick(() => {
        const query = instance?.proxy
            ? uni.createSelectorQuery().in(instance.proxy)
            : uni.createSelectorQuery()
        query.selectViewport().scrollOffset(() => undefined)
        query.select(selector).boundingClientRect(() => undefined)
        query.exec((res: any[]) => {
            const current = Number(res?.[0]?.scrollTop || 0)
            const rect = res?.[1]
            if (!rect) return
            uni.pageScrollTo({
                scrollTop: Math.max(0, current + Number(rect.top || 0) - offset),
                duration: 280
            })
        })
    })
}

defineExpose({ scrollToBlock })
</script>

<style lang="scss" scoped>
.panel {
    padding: 24rpx 42rpx 0;
}

.cf {
    padding-top: 0;
}

/* ---- 00 摘要卡：页面唯一深色面 ---- */
.cf-sum {
    position: relative;
    padding: 30rpx 30rpx 0;
    border-radius: var(--r-lg);
    background: linear-gradient(135deg, var(--c-dark-1) 0%, var(--c-dark-2) 100%);
    color: var(--c-on-dark);
    overflow: hidden;

    &::after {
        content: '';
        position: absolute;
        right: -60rpx;
        top: -80rpx;
        width: 300rpx;
        height: 300rpx;
        border-radius: var(--r-pill);
        background: radial-gradient(circle, rgba(198, 161, 91, 0.2), rgba(198, 161, 91, 0) 68%);
        pointer-events: none;
    }

    &__top {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__datewrap {
        min-width: 0;
    }

    &__eyebrow {
        display: block;
        font-family: var(--font-serif);
        font-size: var(--fs-20);
        letter-spacing: 4rpx;
        color: var(--c-brand-soft);
    }

    &__date {
        display: block;
        margin-top: 12rpx;
        font-family: var(--font-serif);
        font-size: var(--fs-number);
        line-height: var(--lh-none);
        letter-spacing: 1rpx;
    }

    &__week {
        display: block;
        margin-top: 6rpx;
        font-size: var(--fs-xs);
        letter-spacing: 1rpx;
        color: var(--c-on-dark-2);
    }

    &__name {
        position: relative;
        z-index: 1;
        display: block;
        margin-top: var(--sp-3);
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        line-height: 1.35;
    }

    &__meta {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 10rpx;
        margin-top: 10rpx;
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
    }

    &__host {
        flex: none;
        max-width: 220rpx;
    }

    &__dot {
        flex: none;
        color: var(--c-on-dark-2);
    }

    &__city {
        flex: 1;
        min-width: 0;
    }

    &__lock {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 10rpx;
        margin-top: var(--sp-3);
        padding: 20rpx 0;
        border-top: 1rpx solid rgba(255, 255, 255, 0.12);
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
    }

    &__lock.is-failed {
        color: var(--c-danger-border);
    }
    &__lock.is-failed &__lock-ic {
        color: var(--c-danger-border);
    }
    &__lock.is-expired {
        color: var(--c-warning-on-dark);
    }
    &__lock.is-expired &__lock-ic {
        color: var(--c-warning-on-dark);
    }

    &__lock-ic {
        flex: none;
        color: var(--c-brand-soft);
    }

    &__lock-text {
        flex: 1;
        min-width: 0;
        line-height: var(--lh-body);
    }

    &__edit {
        flex: none;
        padding: 16rpx 0;
        margin: -16rpx 0;
        color: var(--c-on-dark);
        text-decoration: underline;
        text-underline-offset: 3rpx;
    }
}

.cf-row {
    display: flex;
    align-items: baseline;
    gap: var(--sp-3);
    padding: 12rpx 0;
    font-size: var(--fs-md);

    &__k {
        flex: none;
        min-width: 112rpx;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__v {
        flex: 1;
        min-width: 0;
        color: var(--c-ink);
    }

    &__m {
        flex: none;
        font-family: var(--font-serif);
        color: var(--c-ink);
    }
}

.cf-divider {
    height: 1rpx;
    margin: var(--sp-2) 0;
    background: var(--c-line);
}

.cf-staff {
    display: flex;
    align-items: center;
    gap: 18rpx;
    padding: 10rpx 0 6rpx;

    &__avatar {
        flex: none;
        width: 60rpx;
        height: 60rpx;
        border-radius: var(--r-sm);
        background: var(--c-neutral-bg);

        &--blank {
            border: 1rpx solid var(--c-line);
        }
    }

    &__body {
        min-width: 0;
    }

    &__name {
        display: block;
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
    }

    &__meta {
        display: block;
        margin-top: 4rpx;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.cf-note {
    display: block;
    margin-top: 12rpx;
    font-size: var(--fs-xs);
    line-height: var(--lh-body);
    color: var(--c-ink-3);
}

.cf-block-err {
    display: block;
    margin-top: 12rpx;
    font-size: var(--fs-xs);
    line-height: var(--lh-body);
    color: var(--c-danger);
}

.cf-line {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: var(--sp-3);
    padding: 10rpx 0;
    font-size: var(--fs-md);

    &__n {
        flex: 1;
        min-width: 0;
        color: var(--c-ink-2);
    }

    &__m {
        flex: none;
        font-family: var(--font-serif);
        color: var(--c-ink);
    }

    &--sub {
        margin-top: 6rpx;
        padding-top: 16rpx;
        border-top: 1rpx solid var(--c-line);
    }

    &--sub &__n {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.cf-empty-line {
    display: block;
    font-size: var(--fs-xs);
    color: var(--c-ink-3);
}

.cf-upsell {
    display: flex;
    align-items: center;
    gap: 12rpx;
    margin-top: 18rpx;
    padding: 14rpx 16rpx;
    border-radius: var(--r-sm);
    background: var(--c-surface-2);

    &__t {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &__btn {
        flex: none;
        display: flex;
        align-items: center;
        height: 52rpx;
        padding: 0 18rpx;
        border-radius: var(--r-pill);
        background: var(--c-action-bg);
        color: var(--c-action-fg);
    }

    &__btn-text {
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
        color: var(--c-action-fg);
    }
}

.cf-value {
    display: block;
    font-size: var(--fs-md);
    font-weight: var(--fw-medium);
    line-height: var(--lh-body);
    color: var(--c-ink);

    &.is-missing {
        font-weight: var(--fw-regular);
        color: var(--c-danger);
    }

    &--addr {
        margin-top: 6rpx;
        font-weight: var(--fw-regular);
    }
}

.cf-region {
    display: flex;
    align-items: center;
    gap: 12rpx;
    flex-wrap: wrap;

    &__text {
        min-width: 0;
        font-size: var(--fs-md);
        color: var(--c-ink);
    }

    &__badge {
        flex: none;
        height: 40rpx;
        padding: 0 10rpx;
        border-radius: var(--r-xs);
        background: var(--c-brand-bg);
        border: 1rpx solid var(--c-primary-border);
        color: var(--c-brand-text);
        font-size: var(--fs-xs);
        line-height: 38rpx;
    }
}

.cf-total {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: var(--sp-3);
    margin-top: 12rpx;
    padding-top: 20rpx;
    border-top: 1rpx solid var(--c-line);

    &__k {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
    }

    &__m {
        font-family: var(--font-serif);
        font-size: var(--fs-40);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
    }
}

.cf-sk {
    padding: 8rpx 0 4rpx;

    &__row {
        width: 62%;
        height: 28rpx;
        margin: 16rpx 0;
        border-radius: var(--r-xs);
        background: var(--c-surface-2);
    }

    &__row--total {
        width: 40%;
        height: 40rpx;
        margin: 26rpx 0 20rpx auto;
    }
}

.cf-foot {
    display: flex;
    justify-content: flex-end;
    margin-top: 18rpx;
}

.cf-pay {
    position: relative;
    padding-left: 34rpx;

    &::before {
        content: '';
        position: absolute;
        left: 8rpx;
        top: 18rpx;
        bottom: 44rpx;
        width: 1rpx;
        background: var(--c-line-strong);
    }

    &__step {
        position: relative;
        padding: 6rpx 0 28rpx;

        &:last-child {
            padding-bottom: 0;
        }
    }

    &__dot {
        position: absolute;
        left: -34rpx;
        top: 14rpx;
        width: 18rpx;
        height: 18rpx;
        border-radius: var(--r-pill);
        border: 3rpx solid var(--c-line-strong);
        background: var(--c-surface);
        box-sizing: border-box;
    }

    &__step.is-now &__dot {
        border-color: var(--c-ink);
        background: var(--c-ink);
    }

    &__top {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: var(--sp-3);
    }

    &__label {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
    }

    &__amt {
        flex: none;
        font-family: var(--font-serif);
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
    }

    &__sub {
        display: block;
        margin-top: 6rpx;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.cf-alert {
    display: flex;
    align-items: flex-start;
    gap: 14rpx;
    margin-top: 24rpx;
    padding: 22rpx 24rpx;
    border-radius: var(--r-sm);
    font-size: var(--fs-xs);
    line-height: var(--lh-body);

    &--danger {
        background: var(--c-danger-bg);
        border: 1rpx solid var(--c-danger-border);
        color: var(--c-danger);
    }

    &--warning {
        background: var(--c-warning-bg);
        border: 1rpx solid var(--c-warning-border);
        color: var(--c-warning);
    }

    &__ic {
        flex: none;
        margin-top: 2rpx;
    }

    &__body {
        flex: 1;
        min-width: 0;
    }

    &__title {
        display: block;
        margin-bottom: 4rpx;
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
    }

    &__text,
    &__line {
        display: block;
    }

    &__btn {
        flex: none;
        align-self: center;
        display: flex;
        align-items: center;
        height: 56rpx;
        padding: 0 20rpx;
        border-radius: var(--r-pill);
        border: 1rpx solid currentColor;
    }

    &__btn-text {
        font-size: var(--fs-xs);
        white-space: nowrap;
    }
}

/*
    协议区：左右负 margin + padding 保持正常态视觉与原来对齐，
    但预留透明描边，供未勾协议提交时闪烁高亮（不用位移，避免跳动）。
*/
.cf-agree {
    margin-top: 28rpx;
    margin-left: calc(var(--sp-2) * -1);
    margin-right: calc(var(--sp-2) * -1);
    /* 左右 padding 被负 margin 抵消，上下 padding 不改变原有视觉位置 */
    padding: var(--sp-2);
    padding-top: 0;
    padding-bottom: var(--sp-2);
    border: 1rpx solid transparent;
    border-radius: var(--r-sm);
    box-sizing: border-box;

    &__link {
        color: var(--c-brand-text);
        font-weight: var(--fw-medium);
    }

    &--flash {
        animation: cf-agree-flash 1.4s var(--ease-out);
    }
}

@keyframes cf-agree-flash {
    0%,
    100% {
        border-color: transparent;
        background: transparent;
    }
    15%,
    45%,
    75% {
        border-color: var(--c-danger);
        background: var(--c-danger-bg);
    }
    30%,
    60% {
        border-color: transparent;
        background: transparent;
    }
}
</style>
