<template>
    <page-meta :page-style="$theme.pageStyle" />

    <PageShell scene="consumer" tone="workspace" hasSafeBottom>
        <BaseNavbar
            title="订单详情"
            title-align="center"
            variant="solid"
            bg-color="#191713"
            text-color="#FFFDF8"
        />

        <view
            v-if="order"
            class="order-detail"
            :class="{
                'order-detail--has-action': hasPrimaryOrSecondaryAction,
                'order-detail--floating-more': !hasPrimaryOrSecondaryAction && moreActionItems.length
            }"
        >
            <view class="page-body wm-page-content">
                <BaseCard
                    class="status-card wm-panel-card"
                    variant="panel"
                    :background="statusTheme.background"
                    border="1rpx solid var(--wm-color-border-strong, #D9BE82)"
                    box-shadow="var(--wm-shadow-soft, 0 16rpx 36rpx rgba(74, 43, 24, 0.07))"
                >
                    <view class="status-card__heading">
                        <StatusBadge
                            class="status-card__badge"
                            :tone="getOrderStatusTone(Number(order.order_status || 0))"
                            size="sm"
                            dot
                        >
                            {{ order.order_status_desc }}
                        </StatusBadge>

                        <text class="status-card__title">{{ statusHeadline }}</text>
                    </view>

                    <view class="status-card__sn">
                        <text class="status-card__sn-label">订单编号</text>

                        <text class="status-card__sn-value">{{ order.order_sn || '-' }}</text>
                    </view>

                    <view
                        v-if="
                            showConfirmCountdown ||
                            showConfirmTimeoutAction ||
                            showPayCountdown ||
                            showPayTimeoutAction ||
                            showVoucherPending ||
                            Number(needPayAmount) > 0
                        "
                        class="status-card__meta"
                    >
                        <view v-if="showConfirmCountdown" class="status-card__meta-item">
                            <text class="status-card__meta-label">剩余确认时间</text>

                            <text class="status-card__meta-value">{{ confirmCountdownText }}</text>
                        </view>

                        <view v-if="showConfirmTimeoutAction" class="status-card__meta-item">
                            <text class="status-card__meta-label">超时处理</text>

                            <text class="status-card__meta-value">
                                {{ confirmTimeoutActionText }}
                            </text>
                        </view>

                        <view v-if="showPayCountdown" class="status-card__meta-item">
                            <text class="status-card__meta-label">剩余支付时间</text>

                            <text class="status-card__meta-value">{{ payCountdownText }}</text>
                        </view>

                        <view v-if="showPayTimeoutAction" class="status-card__meta-item">
                            <text class="status-card__meta-label">支付超时处理</text>

                            <text class="status-card__meta-value">
                                {{ payTimeoutActionText }}
                            </text>
                        </view>

                        <view v-if="showVoucherPending" class="status-card__meta-item">
                            <text class="status-card__meta-label">线下凭证</text>

                            <text class="status-card__meta-value">审核中</text>
                        </view>

                        <view v-if="showNeedPayMeta" class="status-card__meta-item">
                            <text class="status-card__meta-label">{{ needPayMetaLabel }}</text>

                            <text class="status-card__meta-value"
                                >¥{{ formatAmount(needPayAmount) }}</text
                            >
                        </view>
                    </view>
                </BaseCard>

                <BaseCard
                    class="detail-card detail-card--panel wm-form-block"
                    variant="panel"
                    title="服务信息"
                >
                    <view class="service-summary">
                        <view class="service-summary__topbar">
                            <text class="service-summary__label">主套餐</text>

                            <text class="service-summary__price"
                                >¥{{ formatAmount(primaryServiceAmount) }}</text
                            >
                        </view>

                        <text class="service-summary__title">{{ serviceCardTitle }}</text>

                        <view class="detail-info-list service-summary__meta-list">
                            <BaseInfoRow
                                v-for="meta in primaryServiceMetaRows"
                                :key="meta.label"
                                :label="meta.label"
                                :value="meta.value"
                                multiline
                            />
                        </view>
                    </view>

                    <view class="service-addon-section">
                        <view class="service-addon-section__header">
                            <text class="service-addon-section__title">附加套餐</text>

                            <text class="service-addon-section__meta">{{
                                serviceAddonSummaryText
                            }}</text>
                        </view>

                        <view v-if="serviceAddonRows.length" class="service-addon-list">
                            <BaseCard
                                v-for="item in serviceAddonRows"
                                :key="item.key"
                                variant="list"
                                padding="22rpx 24rpx"
                                border-radius="30rpx"
                                class="service-addon-item"
                            >
                                <view class="service-addon-item__copy">
                                    <view class="service-addon-item__title-row">
                                        <text class="service-addon-item__title">{{
                                            item.title
                                        }}</text>

                                        <text class="service-addon-item__type">{{
                                            item.typeText
                                        }}</text>
                                    </view>

                                    <text v-if="item.metaText" class="service-addon-item__meta">
                                        {{ item.metaText }}
                                    </text>

                                    <text v-if="item.description" class="service-addon-item__desc">
                                        {{ item.description }}
                                    </text>
                                </view>

                                <text class="service-addon-item__price">{{ item.priceText }}</text>
                            </BaseCard>
                        </view>

                        <view v-else class="service-addon-empty">
                            <text>当前订单未配置附加套餐</text>
                        </view>
                    </view>

                    <view v-if="serviceRelatedRows.length" class="service-addon-section">
                        <view class="service-addon-section__header">
                            <text class="service-addon-section__title">协作服务</text>

                            <text class="service-addon-section__meta"
                                >共 {{ serviceRelatedRows.length }} 项</text
                            >
                        </view>

                        <view class="service-addon-list">
                            <BaseCard
                                v-for="item in serviceRelatedRows"
                                :key="item.key"
                                variant="list"
                                padding="22rpx 24rpx"
                                border-radius="30rpx"
                                class="service-addon-item service-addon-item--related"
                            >
                                <view class="service-addon-item__copy">
                                    <view class="service-addon-item__title-row">
                                        <text class="service-addon-item__title">{{
                                            item.title
                                        }}</text>

                                        <text
                                            class="service-addon-item__type service-addon-item__type--related"
                                            >{{ item.typeText }}</text
                                        >
                                    </view>

                                    <text v-if="item.metaText" class="service-addon-item__meta">
                                        {{ item.metaText }}
                                    </text>

                                    <text v-if="item.description" class="service-addon-item__desc">
                                        {{ item.description }}
                                    </text>
                                </view>

                                <text class="service-addon-item__price">{{ item.priceText }}</text>
                            </BaseCard>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard
                    class="detail-card detail-card--panel wm-form-block"
                    variant="panel"
                    title="金额与进度"
                >
                    <view class="detail-info-list">
                        <BaseInfoRow
                            label="总价"
                            :value="'¥' + formatAmount(totalOrderAmount)"
                            tone="price"
                        />

                        <BaseInfoRow label="已付" :value="paidAmountText" tone="success" />

                        <BaseInfoRow
                            label="待付"
                            :value="pendingAmountText"
                            :tone="Number(pendingAmount) > 0 ? 'warning' : 'muted'"
                        />

                        <view class="progress-info-list">
                            <view
                                v-for="item in progressItems"
                                :key="item.label"
                                class="progress-info-row"
                            >
                                <text class="progress-info-row__label">{{ item.label }}</text>

                                <text class="progress-info-row__value">{{ item.value }}</text>
                            </view>
                        </view>
                    </view>

                    <view
                        v-if="hasFinancialDetails"
                        class="inline-link"
                        @click="showFinancialDetails = !showFinancialDetails"
                    >
                        <text class="inline-link__text">
                            {{ showFinancialDetails ? '收起详细金额' : '查看详细金额' }}
                        </text>
                    </view>

                    <view v-if="showFinancialDetails" class="detail-info-list detail-info-list--nested">
                        <BaseInfoRow
                            label="主服务金额"
                            :value="'¥' + formatAmount(orderServiceAmount)"
                        />

                        <BaseInfoRow
                            v-if="Number(order.addon_amount || 0) > 0"
                            label="附加内容金额"
                            :value="'¥' + formatAmount(order.addon_amount)"
                        />

                        <BaseInfoRow
                            v-if="Number(order.discount_amount || 0) > 0"
                            label="优惠金额"
                            :value="'-¥' + formatAmount(order.discount_amount)"
                            tone="danger"
                        />

                        <BaseInfoRow
                            v-if="Number(order.deposit_amount || 0) > 0"
                            label="定金"
                            :value="'¥' + formatAmount(order.deposit_amount)"
                        />

                        <BaseInfoRow
                            v-if="Number(order.balance_amount || 0) > 0"
                            label="尾款"
                            :value="'¥' + formatAmount(order.balance_amount)"
                        />
                    </view>
                </BaseCard>

                <BaseCard class="detail-card detail-card--list" variant="list">
                    <template #header>
                        <view class="card__title-row">
                            <text class="card__title">订单信息</text>

                            <view class="inline-copy" @click="copyOrderSn">
                                <text class="inline-copy__text">复制编号</text>
                            </view>
                        </view>
                    </template>

                    <view class="detail-info-list">
                        <BaseInfoRow label="订单编号" :value="order.order_sn" />

                        <BaseInfoRow label="下单时间" :value="order.create_time || '-'" />

                        <BaseInfoRow label="付款渠道" :value="paymentChannelDesc" />

                        <BaseInfoRow v-if="order.pay_time" label="支付时间" :value="order.pay_time" />
                    </view>
                </BaseCard>

                <BaseCard
                    v-if="showOfflineVoucherCard"
                    class="detail-card detail-card--list"
                    variant="list"
                    title="线下支付凭证"
                >
                    <view class="detail-info-list">
                        <BaseInfoRow label="付款渠道" :value="paymentChannelDesc" />

                        <BaseInfoRow label="凭证状态">
                            <template #value>
                                <StatusBadge
                                    :tone="getVoucherStatusTone(Number(order.pay_voucher_status ?? -1))"
                                    size="sm"
                                >
                                    {{ order.pay_voucher_status_desc || '未上传' }}
                                </StatusBadge>
                            </template>
                        </BaseInfoRow>

                        <BaseInfoRow
                            v-if="order.pay_voucher_audit_remark"
                            label="审核备注"
                            :value="order.pay_voucher_audit_remark"
                            multiline
                        />
                    </view>

                    <view v-if="order.pay_voucher" class="voucher-image">
                        <image :src="order.pay_voucher" mode="aspectFill" />
                    </view>

                    <view v-else class="voucher-empty"><text>暂无凭证</text></view>
                </BaseCard>

                <BaseCard
                    v-if="order.refund"
                    class="detail-card detail-card--list"
                    variant="list"
                    title="退款信息"
                >
                    <view class="detail-info-list">
                        <BaseInfoRow label="退款状态">
                            <template #value>
                                <StatusBadge
                                    :tone="getRefundStatusTone(Number(order.refund.refund_status || 0))"
                                    size="sm"
                                >
                                    {{ order.refund.refund_status_desc }}
                                </StatusBadge>
                            </template>
                        </BaseInfoRow>

                        <BaseInfoRow
                            label="退款金额"
                            :value="'¥' + formatAmount(order.refund.refund_amount)"
                            tone="price"
                        />

                        <BaseInfoRow
                            label="实际退款金额"
                            :value="'¥' + formatAmount(order.refund.actual_refund_amount || 0)"
                            tone="price"
                        />

                        <BaseInfoRow
                            label="退款类型"
                            :value="order.refund.refund_type_desc || '退款申请'"
                        />

                        <BaseInfoRow
                            label="退款原因"
                            :value="order.refund.refund_reason"
                            multiline
                        />

                        <view
                            v-if="order.refund.refund_items && order.refund.refund_items.length"
                            class="detail-info-row detail-info-row--stack"
                        >
                            <text class="detail-info-row__label">退款明细</text>

                            <view class="refund-item-list">
                                <view
                                    v-for="item in order.refund.refund_items"
                                    :key="item.id || item.out_refund_no"
                                    class="refund-item"
                                >
                                    <view class="refund-item__head">
                                        <text class="refund-item__title">
                                            {{ getPayWayText(Number(item.pay_way || 0)) }}
                                        </text>

                                        <text class="refund-item__amount">
                                            ¥{{ formatAmount(item.refund_amount || 0) }}
                                        </text>
                                    </view>

                                    <view class="refund-item__meta">
                                        <text>{{
                                            getRefundItemStatusText(Number(item.refund_status || 0))
                                        }}</text>

                                        <text v-if="item.out_refund_no"
                                            >单号：{{ item.out_refund_no }}</text
                                        >
                                    </view>

                                    <text v-if="item.refund_msg" class="refund-item__desc">
                                        {{ item.refund_msg }}
                                    </text>
                                </view>
                            </view>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard
                    v-if="showOfflineCollectionCard"
                    class="detail-card detail-card--panel"
                    variant="panel"
                    title="线下收款"
                >
                    <view class="offline-collection">
                        <text class="offline-collection__text">待联系顾问确认收款方式。</text>
                        <view class="offline-collection__button" @click="handleContactAdvisor">
                            <text class="offline-collection__button-text">联系顾问</text>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard
                    v-if="showQuestionnairePromptCard"
                    class="detail-card detail-card--panel"
                    variant="panel"
                    title="新人问卷待填写"
                >
                    <view class="offline-collection">
                        <text class="offline-collection__text">请补充婚礼仪式资料。</text>
                        <view class="offline-collection__button" @click="goQuestionnaireTask">
                            <text class="offline-collection__button-text">去填写</text>
                        </view>
                    </view>
                </BaseCard>
            </view>

            <ActionArea
                v-if="hasPrimaryOrSecondaryAction"
                class="order-detail__action-area"
                sticky
                safeBottom
            >
                <view class="action-bar">
                    <view v-if="hasPrimaryOrSecondaryAction" class="action-bar__buttons">
                        <BaseButton
                            v-if="secondaryVisibleAction"
                            block
                            variant="secondary"
                            size="md"
                            height="80rpx"
                            font-size="25rpx"
                            :style="secondaryVisibleAction.style"
                            @click="secondaryVisibleAction.onClick"
                        >
                            {{ secondaryVisibleAction.label }}
                        </BaseButton>

                        <BaseButton
                            v-if="primaryVisibleAction"
                            block
                            variant="primary"
                            size="md"
                            height="80rpx"
                            font-size="25rpx"
                            :style="primaryVisibleAction.style"
                            @click="primaryVisibleAction.onClick"
                        >
                            {{ primaryVisibleAction.label }}
                        </BaseButton>
                    </view>

                    <view
                        v-if="moreActionItems.length"
                        class="action-bar__more"
                        @click="openMoreActions"
                    >
                        <BaseIcon name="more-circle" size="34" color="var(--wm-text-secondary, #665E52)" />

                        <text class="action-bar__more-text">更多</text>
                    </view>
                </view>
            </ActionArea>

            <view
                v-else-if="moreActionItems.length"
                class="more-floating-action"
                @click="openMoreActions"
            >
                <BaseIcon name="more-circle" size="30" color="var(--wm-text-secondary, #665E52)" />

                <text class="more-floating-action__text">更多</text>
            </view>

            <BaseOverlayMask :show="showMoreActionsPopup" @close="showMoreActionsPopup = false" />

            <tn-popup
                v-model="showMoreActionsPopup"
                open-direction="bottom"
                :radius="32"
                safe-area-inset-bottom
                :overlay="false"
                :overlay-closeable="true"
            >
                <view class="more-actions-sheet">
                    <view class="popup__header">
                        <view class="more-actions-sheet__heading">
                            <text class="popup__title">更多操作</text>
                        </view>

                        <BaseIcon
                            name="close"
                            size="40"
                            color="var(--wm-text-tertiary, #8A806F)"
                            @click="showMoreActionsPopup = false"
                        />
                    </view>

                    <view class="more-actions-sheet__list">
                        <view
                            v-for="item in moreActionItems"
                            :key="item.key"
                            class="more-action-item"
                            :class="{ 'more-action-item--danger': item.tone === 'danger' }"
                            @click="handleMoreAction(item)"
                        >
                            <view class="more-action-item__icon">
                                <BaseIcon
                                    :name="item.icon"
                                    size="34"
                                    :color="
                                        item.tone === 'danger'
                                            ? 'var(--wm-color-danger, #9A6B35)'
                                            : 'var(--wm-text-primary, #191713)'
                                    "
                                />
                            </view>

                            <view class="more-action-item__body">
                                <text class="more-action-item__label">{{ item.label }}</text>

                                <text class="more-action-item__desc">{{ item.description }}</text>
                            </view>

                            <BaseIcon
                                name="right"
                                size="28"
                                :color="
                                    item.tone === 'danger'
                                        ? 'var(--wm-color-danger, #9A6B35)'
                                        : 'var(--wm-text-tertiary, #8A806F)'
                                "
                            />
                        </view>
                    </view>
                </view>
            </tn-popup>

            <BaseOverlayMask :show="showRefundPopup" @close="showRefundPopup = false" />

            <tn-popup
                v-model="showRefundPopup"
                open-direction="bottom"
                :radius="32"
                safe-area-inset-bottom
                :overlay="false"
                :overlay-closeable="true"
            >
                <view class="popup">
                    <view class="popup__header"
                        ><text class="popup__title">申请退款</text
                        ><BaseIcon
                            name="close"
                            size="40"
                            color="var(--wm-text-tertiary, #8A806F)"
                            @click="showRefundPopup = false"
                    /></view>

                    <view class="popup__content">
                        <view class="form-item"
                            ><text class="form-item__label">退款金额</text>

                            <view class="refund-amount-card">
                                <text class="refund-amount-card__value"
                                    >¥{{ formatAmount(refundApplyAmount) }}</text
                                >

                                <text class="refund-amount-card__tip"
                                    >按当前剩余可退金额提交，提交后不可修改</text
                                >
                            </view>
                        </view>

                        <view class="form-item"
                            ><text class="form-item__label">退款原因</text
                            ><tn-input
                                v-model="refundForm.reason"
                                type="textarea"
                                placeholder="请输入退款原因"
                                :maxlength="200"
                                border
                                height="200"
                        /></view>
                    </view>

                    <view class="popup__actions">
                        <view class="popup__action">
                            <BaseButton
                                block
                                variant="secondary"
                                size="lg"
                                @click="showRefundPopup = false"
                            >
                                取消
                            </BaseButton>
                        </view>

                        <view class="popup__action">
                            <BaseButton block variant="primary" size="lg" @click="submitRefund">
                                提交申请
                            </BaseButton>
                        </view>
                    </view>
                </view>
            </tn-popup>

            <BaseOverlayMask :show="showVoucherPopup" @close="showVoucherPopup = false" />

            <tn-popup
                v-model="showVoucherPopup"
                open-direction="bottom"
                :radius="32"
                safe-area-inset-bottom
                :overlay="false"
                :overlay-closeable="true"
            >
                <view class="popup">
                    <view class="popup__header"
                        ><text class="popup__title">上传支付凭证</text
                        ><BaseIcon
                            name="close"
                            size="40"
                            color="var(--wm-text-tertiary, #8A806F)"
                            @click="showVoucherPopup = false"
                    /></view>

                    <view class="popup__content">
                        <view class="form-item"
                            ><text class="form-item__label">凭证图片</text
                            ><text class="form-item__tip">请上传转账截图或付款凭证</text></view
                        >

                        <view class="voucher-upload">
                            <view v-if="voucherForm.image" class="voucher-upload__preview">
                                <image :src="voucherForm.image" mode="aspectFill" />

                                <view class="voucher-upload__remove" @click="voucherForm.image = ''"
                                    ><BaseIcon name="close" size="32" color="var(--wm-text-inverse, #FFFDF8)"
                                /></view>
                            </view>

                            <view v-else class="voucher-upload__add" @click="chooseVoucherImage">
                                <BaseIcon name="add" size="64" color="var(--wm-color-border-strong, #D9BE82)" />

                                <text class="voucher-upload__text">选择图片</text>

                                <text class="voucher-upload__tip">支持 jpg、png 格式</text>
                            </view>
                        </view>
                    </view>

                    <view class="popup__actions">
                        <view class="popup__action">
                            <BaseButton
                                block
                                variant="secondary"
                                size="lg"
                                @click="showVoucherPopup = false"
                            >
                                取消
                            </BaseButton>
                        </view>

                        <view class="popup__action">
                            <BaseButton block variant="primary" size="lg" @click="submitVoucher">
                                {{ voucherForm.uploading ? '上传中...' : '提交审核' }}
                            </BaseButton>
                        </view>
                    </view>
                </view>
            </tn-popup>

            <payment
                v-model:show="payState.showPay"
                v-model:show-check="payState.showCheck"
                :order-id="orderId"
                :from="payState.from"
                :redirect="payState.redirect"
                :payment-sn="payState.paymentSn"
                @success="handlePaySuccess"
                @fail="handlePayFail"
            />

            <view class="safe-bottom"></view>
        </view>

        <view v-else-if="detailLoading" class="loading-container">
            <LoadingState text="订单详情加载中..." />
        </view>

        <view v-else class="detail-state-shell wm-page-content">
            <EmptyState
                :title="detailError?.title || '订单暂不可用'"
                :description="detailError?.message || '未找到订单，或当前网络不可用。'"
                :action-text="detailError?.actionText || '重新加载'"
                @action="handleDetailRecoveryAction"
            />

            <view class="detail-state-shell__actions">
                <view class="detail-state-shell__link" @click="goOrderList">
                    <text>查看全部订单</text>
                </view>

                <view class="detail-state-shell__divider" />

                <view class="detail-state-shell__link" @click="goHome">
                    <text>返回首页</text>
                </view>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'

import { onHide, onLoad, onShow, onUnload } from '@dcloudio/uni-app'

import PageShell from '@/components/base/PageShell.vue'

import BaseNavbar from '@/components/base/BaseNavbar.vue'

import ActionArea from '@/components/base/ActionArea.vue'

import BaseCard from '@/components/base/BaseCard.vue'

import BaseButton from '@/components/base/BaseButton.vue'

import BaseInfoRow from '@/components/base/BaseInfoRow.vue'

import StatusBadge from '@/components/base/StatusBadge.vue'

import EmptyState from '@/components/base/EmptyState.vue'

import LoadingState from '@/components/base/LoadingState.vue'

import { ClientEnum } from '@/enums/appEnums'

import { useThemeStore } from '@/stores/theme'

import {
    applyRefund,
    cancelOrder,
    confirmOrder,
    deleteOrder,
    getOrderDetail,
    uploadPayVoucher
} from '@/api/order'

import { getMyReviews, getPendingOrders } from '@/api/review'

import { uploadImage } from '@/api/app'

import { client } from '@/utils/client'

import { confirmModal, showError, showSuccess } from '@/utils/feedback'

import { subscribeAfterSaleScenes } from '@/utils/subscribe'

import { getCoupleQuestionnaireLists } from '@/api/coupleQuestionnaire'
import { normalizeQuestionnaireLists } from '@/utils/coupleQuestionnaire'

import { navigateTo } from '@/utils/util'

import { resolvePaymentChannel, shouldUseOfflineCollection } from '@/utils/paymentChannel'

import {
    goHome,
    goLoginWithBack,
    goOrderList,
    normalizePageRecoveryError
} from '@/utils/page-recovery'

const $theme = useThemeStore()

const orderId = ref(0)

const order = ref<any>(null)

const detailLoading = ref(true)

const detailError = ref<ReturnType<typeof normalizePageRecoveryError> | null>(null)

const showRefundPopup = ref(false)

const showVoucherPopup = ref(false)

const payState = reactive({
    showPay: false,

    showCheck: false,

    from: 'order',

    redirect: '/pages/order_detail/order_detail',

    paymentSn: ''
})

const refundForm = reactive({ reason: '' })

const voucherForm = reactive({ image: '', uploading: false })

const showMoreActionsPopup = ref(false)

const showFinancialDetails = ref(false)

const pendingQuestionnaireTask = ref<any>(null)

const pendingReviewItem = ref<any>(null)

const orderReviewItem = ref<any>(null)

const payCountdownSeconds = ref(0)

const confirmCountdownSeconds = ref(0)

let payCountdownTimer: ReturnType<typeof setInterval> | null = null

let confirmCountdownTimer: ReturnType<typeof setInterval> | null = null

let payCountdownRefreshing = false

let confirmCountdownRefreshing = false

let detailRequestPromise: Promise<void> | null = null

let hasLoadedOnce = false

let hasBeenHidden = false

const formatAmount = (value: any) => Number(value || 0).toFixed(2)

const refundApplyAmount = computed(() =>
    Number(order.value?.refund_apply_amount ?? order.value?.refundable_amount ?? 0)
)


const formatCountdown = (seconds: number | string | undefined) => {
    const total = Math.max(Number(seconds || 0), 0)

    if (total <= 0) return '已超时，等待系统处理'

    const hours = Math.floor(total / 3600)

    const minutes = Math.floor((total % 3600) / 60)

    const remainSeconds = total % 60

    return [hours, minutes, remainSeconds].map((item) => String(item).padStart(2, '0')).join(':')
}

const getStatusTheme = (status: number) =>
    ((
        {
            0: {
                background: 'linear-gradient(180deg, #F1E5C8 0%, #FFFDF8 100%)',

                iconBg: '#9A6B35'
            },

            1: {
                background: 'linear-gradient(180deg, #FAF6EE 0%, #FFFDF8 100%)',

                iconBg: '#191713'
            },

            2: {
                background: 'linear-gradient(180deg, #FFFDF8 0%, #FFFDF8 100%)',

                iconBg: '#191713'
            },

            3: {
                background: 'linear-gradient(180deg, #F5F1E8 0%, #FFFDF8 100%)',

                iconBg: '#665E52'
            },

            4: {
                background: 'linear-gradient(180deg, #F5F1E8 0%, #FFFDF8 100%)',

                iconBg: '#665E52'
            },

            5: {
                background: 'linear-gradient(180deg, #F5F1E8 0%, #FFFDF8 100%)',

                iconBg: '#665E52'
            },

            6: {
                background: 'linear-gradient(180deg, #ECE4D6 0%, #FFFDF8 100%)',

                iconBg: '#665E52'
            },

            7: {
                background: 'linear-gradient(180deg, #F1E5C8 0%, #FFFDF8 100%)',

                iconBg: '#9A6B35'
            },

            10: {
                background: 'linear-gradient(180deg, #F2DDD5 0%, #FFFDF8 100%)',

                iconBg: '#9A6B35'
            },

            8: {
                background: 'linear-gradient(180deg, #ECE4D6 0%, #FFFDF8 100%)',

                iconBg: '#665E52'
            }
        } as Record<number, { background: string; iconBg: string }>
    )[status] || {
        background: 'linear-gradient(180deg, #F5F1E8 0%, #FFFDF8 100%)',

        iconBg: '#665E52'
    })

type StatusBadgeTone =
    | 'neutral'
    | 'success'
    | 'warning'
    | 'danger'
    | 'info'
    | 'primary'
    | 'paid'
    | 'running'
    | 'pending'
    | 'risk'

const getOrderStatusTone = (status: number): StatusBadgeTone =>
    (({
        0: 'pending',
        1: 'warning',
        2: 'paid',
        3: 'running',
        4: 'success',
        5: 'success',
        6: 'neutral',
        7: 'warning',
        8: 'neutral',
        10: 'risk'
    } as Record<number, StatusBadgeTone>)[status] || 'neutral')

const getRefundStatusTone = (status: number): StatusBadgeTone =>
    (({
        0: 'pending',
        1: 'running',
        2: 'success',
        3: 'danger',
        4: 'neutral',
        5: 'neutral'
    } as Record<number, StatusBadgeTone>)[status] || 'neutral')

const getVoucherStatusTone = (status: number): StatusBadgeTone =>
    (({
        0: 'pending',
        1: 'success',
        2: 'danger'
    } as Record<number, StatusBadgeTone>)[status] || 'neutral')

const getPayWayText = (payWay: number) => {
    const texts: Record<number, string> = {
        1: '微信支付',

        2: '支付宝',

        3: '余额支付',

        4: '线下支付'
    }

    return texts[payWay] || '未知方式'
}

const getRefundItemStatusText = (status: number) => {
    const texts: Record<number, string> = {
        0: '待执行',

        1: '处理中',

        2: '已完成',

        3: '失败'
    }

    return texts[status] || '未知状态'
}

const primaryItem = computed(() => {
    const items = Array.isArray(order.value?.items) ? order.value.items : []

    return items.find((item: any) => Number(item?.item_type || 1) === 1) || items[0] || null
})

const extraItems = computed(() => {
    const items = Array.isArray(order.value?.items) ? order.value.items : []

    return items.filter((item: any) => Number(item?.item_type || 1) !== 1)
})

const primaryStaffName = computed(
    () => primaryItem.value?.staff?.name || primaryItem.value?.staff_name || '待分配服务人员'
)

const primaryPackageName = computed(
    () => primaryItem.value?.package?.name || primaryItem.value?.package_name || '待确认主套餐'
)

const primaryServiceDate = computed(
    () =>
        primaryItem.value?.service_date ||
        primaryItem.value?.schedule_date ||
        order.value?.service_date ||
        '待确认服务日期'
)

const primaryServiceMetaRows = computed(() =>
    [
        { label: '服务人员', value: primaryStaffName.value || '待分配服务人员' },

        { label: '服务日期', value: primaryServiceDate.value || '待确认服务日期' },

        {
            label: '服务地点',

            value:
                [order.value?.service_region_text, order.value?.service_address]

                    .map((item: any) => String(item || '').trim())

                    .filter(Boolean)

                    .join(' · ') || '待确认服务地点'
        }
    ].filter((item) => String(item.value || '').trim() !== '')
)

const getItemQuantity = (item: any) => Math.max(Number(item?.quantity || 1), 1)

const getItemDisplayAmount = (item: any) => {
    const subtotal = Number(item?.subtotal)

    if (Number.isFinite(subtotal) && subtotal >= 0) {
        return subtotal
    }

    return Math.max(Number(item?.price || 0) * getItemQuantity(item), 0)
}

const getAddonDisplayAmount = (addon: any) => {
    const subtotal = Number(addon?.subtotal)

    if (Number.isFinite(subtotal) && subtotal >= 0) {
        return subtotal
    }

    return Math.max(Number(addon?.price || 0) * Math.max(Number(addon?.quantity || 1), 1), 0)
}

const orderServiceAmount = computed(() => {
    const serviceAmount = Number(order.value?.service_amount ?? -1)

    return serviceAmount >= 0 ? serviceAmount : Math.max(0, Number(order.value?.total_amount || 0))
})

const primaryServiceAmount = computed(() =>
    primaryItem.value ? getItemDisplayAmount(primaryItem.value) : orderServiceAmount.value
)

const buildAddonRowKey = (kind: string, title: string, amount: number, quantity = 1, extra = '') =>
    `${kind}:${title.trim()}:${formatAmount(amount)}:${quantity}:${extra}`

const getExtraItemTitle = (item: any) => {
    if (Number(item?.item_type || 1) === 2) {
        return item?.item_meta?.label || item?.package_name || '附加套餐'
    }

    if (Number(item?.item_type || 1) === 3) {
        const roleLabel = item?.item_meta?.role_label || '协作服务'

        const staffName = item?.staff?.name || item?.staff_name || ''

        return staffName ? `${roleLabel} · ${staffName}` : roleLabel
    }

    return item?.package_name || '服务项目'
}

const getExtraItemTypeText = (item: any) => {
    const itemType = Number(item?.item_type || 1)

    if (itemType === 2) return '附加套餐'

    if (itemType === 3) return '协作服务'

    return item?.item_type_desc || '服务项目'
}

const getExtraItemDescription = (item: any) => {
    const parts: string[] = []

    const description = String(item?.package_description || item?.package?.description || '').trim()

    if (description) {
        parts.push(description)
    }

    const quantity = getItemQuantity(item)

    if (quantity > 1) {
        parts.push(`数量 x${quantity}`)
    }

    return parts.join(' · ')
}

const serviceAddonRows = computed(() => {
    const rows: Array<{
        key: string

        title: string

        typeText: string

        description: string

        metaText: string

        priceText: string
    }> = []

    const seen = new Set<string>()

    const pushRow = (
        kind: string,

        title: string,

        amount: number,

        quantity: number,

        typeText: string,

        description = '',

        metaText = '',

        extra = ''
    ) => {
        const normalizedTitle = String(title || '').trim()

        if (!normalizedTitle) return

        const key = buildAddonRowKey(kind, normalizedTitle, amount, quantity, extra)

        if (seen.has(key)) return

        seen.add(key)

        rows.push({
            key,

            title: normalizedTitle,

            typeText,

            description: String(description || '').trim(),

            metaText: String(metaText || '').trim(),

            priceText: `¥${formatAmount(amount)}`
        })
    }

    ;(Array.isArray(order.value?.items) ? order.value.items : []).forEach((item: any) => {
        ;(item?.addons || []).forEach((addon: any) => {
            const quantity = Math.max(Number(addon?.quantity || 1), 1)

            pushRow(
                'addon',

                addon?.addon_name || addon?.name || '附加套餐',

                getAddonDisplayAmount(addon),

                quantity,

                '附加套餐',

                '',

                `数量 x${quantity}`
            )
        })
    })

    extraItems.value

        .filter((item: any) => Number(item?.item_type || 1) === 2)

        .forEach((item: any) => {
            const quantity = getItemQuantity(item)

            pushRow(
                'addon',

                getExtraItemTitle(item),

                getItemDisplayAmount(item),

                quantity,

                getExtraItemTypeText(item),

                getExtraItemDescription(item),

                [item?.service_date, `数量 x${quantity}`].filter(Boolean).join(' · '),

                item?.service_date || ''
            )
        })

    return rows
})

const serviceAddonSummaryText = computed(() => `共 ${serviceAddonRows.value.length} 项`)

const serviceRelatedRows = computed(() =>
    extraItems.value

        .filter((item: any) => Number(item?.item_type || 1) === 3)

        .map((item: any) => {
            const quantity = getItemQuantity(item)

            return {
                key: buildAddonRowKey(
                    'related',

                    getExtraItemTitle(item),

                    getItemDisplayAmount(item),

                    quantity,

                    item?.service_date || ''
                ),

                title: getExtraItemTitle(item),

                typeText: getExtraItemTypeText(item),

                description: getExtraItemDescription(item),

                metaText: [item?.service_date, `数量 x${quantity}`].filter(Boolean).join(' · '),

                priceText: `¥${formatAmount(getItemDisplayAmount(item))}`
            }
        })
)

const serviceCardTitle = computed(() => {
    return String(primaryPackageName.value || '').trim() || '服务订单'
})

const paymentChannel = computed(() => resolvePaymentChannel(order.value))

const currentNeedPayStage = computed(() => String(order.value?.need_pay || '').trim())

const canUploadOfflineVoucherStage = computed(() =>
    currentNeedPayStage.value === 'balance' || currentNeedPayStage.value === 'full'
)

const isOfflineCollectionMode = computed(() => shouldUseOfflineCollection(order.value))

const isOfflineCollectionPaymentStage = computed(
    () =>
        !!order.value &&
        Number(order.value.order_status || 0) === 1 &&
        isOfflineCollectionMode.value &&
        Number(order.value.need_pay_amount || 0) > 0
)

const paymentChannelDesc = computed(
    () =>
        (isOfflineCollectionMode.value ? '线下支付' : String(order.value?.payment_channel_desc || '').trim()) ||
        (paymentChannel.value === 2 ? '线下支付' : '线上支付')
)

const needPayAmount = computed(() => {
    if (!order.value) return 0

    return Number(order.value.need_pay_amount || 0)
})

const needPayMetaLabel = computed(() => {
    if (!order.value) return '当前待支付'

    const status = Number(order.value.order_status || 0)

    if (
        [2, 3].includes(status) &&
        String(order.value.current_pay_stage || '').trim() === 'balance_after_service'
    ) {
        return '预计尾款'
    }

    return '当前待支付'
})

const showNeedPayMeta = computed(() => {
    if (!order.value || Number(needPayAmount.value) <= 0) return false

    const status = Number(order.value.order_status || 0)

    if (status === 1) return true

    return (
        [2, 3].includes(status) &&
        String(order.value.current_pay_stage || '').trim() === 'balance_after_service'
    )
})

const showOfflineVoucherCard = computed(
    () =>
        !!order.value &&
        canUploadOfflineVoucherStage.value &&
        (paymentChannel.value === 2 || !!order.value?.pay_voucher)
)

const showVoucherPending = computed(
    () =>
        !!order.value &&
        canUploadOfflineVoucherStage.value &&
        paymentChannel.value === 2 &&
        Number(order.value.pay_voucher_status) === 0
)

const showConfirmCountdown = computed(
    () =>
        !!order.value &&
        Number(order.value.order_status) === 0 &&
        Number(order.value.confirm_deadline_time || 0) > 0
)

const confirmCountdownText = computed(() =>
    showConfirmCountdown.value ? formatCountdown(confirmCountdownSeconds.value) : '-'
)

const confirmTimeoutActionText = computed(() =>
    String(order.value?.confirm_timeout_action_desc || '').trim()
)

const showConfirmTimeoutAction = computed(
    () =>
        !!order.value && Number(order.value.order_status) === 0 && !!confirmTimeoutActionText.value
)

const showPayCountdown = computed(() => !!order.value && payCountdownSeconds.value > 0)

const payCountdownText = computed(() => formatCountdown(payCountdownSeconds.value))

const payTimeoutActionText = computed(() =>
    String(order.value?.pay_timeout_action_desc || '').trim()
)

const showPayTimeoutAction = computed(
    () => !!order.value && Number(order.value.order_status) === 1 && !!payTimeoutActionText.value
)

const canPayOnline = computed(
    () =>
        !!order.value &&
        Number(order.value.order_status) === 1 &&
        Number(order.value.need_pay_amount || 0) > 0 &&
        paymentChannel.value === 1 &&
        !isOfflineCollectionMode.value
)

const canUploadVoucher = computed(
    () =>
        !!order.value &&
        Number(order.value.order_status) === 1 &&
        canUploadOfflineVoucherStage.value &&
        Number(order.value.pay_voucher_status) !== 0 &&
        Number(order.value.offline_collection_available ?? 0) === 1
)

const showOfflineCollectionCard = computed(
    () =>
        !!order.value &&
        isOfflineCollectionMode.value &&
        Number(order.value.order_status) === 1 &&
        Number(order.value.need_pay_amount || 0) > 0
)

const showQuestionnairePromptCard = computed(() => Number(pendingQuestionnaireTask.value?.id || 0) > 0)

const reviewActionStatusMatched = computed(() =>
    [4, 5].includes(Number(order.value?.order_status ?? -1))
)

const canGoReviewAction = computed(
    () => reviewActionStatusMatched.value && Number(pendingReviewItem.value?.id || 0) > 0
)

const canViewReviewAction = computed(
    () => reviewActionStatusMatched.value && Number(orderReviewItem.value?.id || 0) > 0
)

const resolveReviewOrderId = (item: any) =>
    Number(
        item?.order_id ||
            item?.order?.id ||
            item?.orderItem?.order_id ||
            item?.order_item?.order_id ||
            0
    )

const clearOrderReviewEntry = () => {
    pendingReviewItem.value = null
    orderReviewItem.value = null
}

const findCurrentOrderReviewItem = (lists: any[]) => {
    return lists.find((item) => resolveReviewOrderId(item) === orderId.value) || null
}

const fetchOrderReviewEntry = async () => {
    clearOrderReviewEntry()

    if (orderId.value <= 0 || !reviewActionStatusMatched.value) {
        return
    }

    try {
        const [pendingRes, reviewedRes] = (await Promise.all([
            getPendingOrders({
                page: 1,
                limit: 50,
                order_id: orderId.value
            }),
            getMyReviews({
                page: 1,
                limit: 50,
                order_id: orderId.value
            })
        ])) as any[]

        pendingReviewItem.value = findCurrentOrderReviewItem(
            Array.isArray(pendingRes?.lists) ? pendingRes.lists : []
        )
        orderReviewItem.value = findCurrentOrderReviewItem(
            Array.isArray(reviewedRes?.lists) ? reviewedRes.lists : []
        )
    } catch {
        clearOrderReviewEntry()
    }
}

const goReviewFromOrder = () => {
    const id = Number(pendingReviewItem.value?.id || 0)

    if (id <= 0) {
        showError('暂无可评价订单项')
        return
    }

    uni.navigateTo({
        url: `/packages/pages/review/publish?order_item_id=${id}`
    })
}

const goReviewDetailFromOrder = () => {
    const id = Number(orderReviewItem.value?.id || 0)

    if (id <= 0) {
        showError('暂无评价记录')
        return
    }

    uni.navigateTo({
        url: `/packages/pages/review/detail?id=${id}`
    })
}

const statusTheme = computed(() => getStatusTheme(Number(order.value?.order_status ?? 6)))

const statusHeadline = computed(() => {
    const orderStatus = Number(order.value?.order_status ?? -1)

    const serviceName = String(primaryPackageName.value || '服务').trim()

    const headlines: Record<number, string> = {
        0: `${serviceName}订单待确认`,

        1: `${serviceName}订单待支付`,

        2: `${serviceName}订单待服务`,

        3: `${serviceName}服务进行中`,

        4: `${serviceName}订单已完成`,

        5: `${serviceName}订单已评价`,

        6: `${serviceName}订单已取消`,

        7: `${serviceName}订单已暂停`,

        10: `${serviceName}退款处理中`,

        8: `${serviceName}订单已退款`
    }

    return headlines[orderStatus] || `${serviceName}订单状态已更新`
})

const totalOrderAmount = computed(() =>
    Math.max(Number(order.value?.total_amount || 0), Number(order.value?.pay_amount || 0))
)

const paidAmount = computed(() => {
    if (!order.value) return 0

    const depositAmount = Number(order.value.deposit_amount || 0)

    const balanceAmount = Number(order.value.balance_amount || 0)

    if (depositAmount > 0 || balanceAmount > 0) {
        return (
            (order.value.deposit_paid ? depositAmount : 0) +
            (order.value.balance_paid ? balanceAmount : 0)
        )
    }

    return [2, 3, 4, 5, 8, 10].includes(Number(order.value.order_status || 0))
        ? Number(order.value.pay_amount || 0)
        : 0
})

const pendingAmount = computed(() => {
    if (Number(order.value?.order_status || 0) === 1 && Number(needPayAmount.value) > 0) {
        return Number(needPayAmount.value)
    }

    return Math.max(totalOrderAmount.value - paidAmount.value, 0)
})

const paidAmountText = computed(() => `¥${formatAmount(paidAmount.value)}`)

const pendingAmountText = computed(() => `¥${formatAmount(pendingAmount.value)}`)

const hasFinancialDetails = computed(
    () =>
        Number(order.value?.addon_amount || 0) > 0 ||
        Number(order.value?.discount_amount || 0) > 0 ||
        Number(order.value?.deposit_amount || 0) > 0 ||
        Number(order.value?.balance_amount || 0) > 0
)

const paymentProgressText = computed(() => {
    if (!order.value) return '待开始'

    if (showVoucherPending.value) return '凭证审核中'

    if (isOfflineCollectionPaymentStage.value) {
        if (canUploadVoucher.value) {
            return order.value.need_pay === 'balance'
                ? '待上传尾款凭证'
                : '待上传线下凭证'
        }

        return '待线下收款'
    }

    if (canPayOnline.value)
        return order.value.need_pay === 'balance'
            ? '待支付尾款'
            : order.value.need_pay === 'deposit'
            ? '待支付定金'
            : '待支付'

    if (
        Number(order.value.deposit_amount || 0) > 0 ||
        Number(order.value.balance_amount || 0) > 0
    ) {
        if (order.value.deposit_paid && order.value.balance_paid) return '已完成'

        if (order.value.deposit_paid) return '定金已付'
    }

    if (Number(order.value.order_status || 0) === 10) return '退款处理中'

    if ([2, 3, 4, 5, 8].includes(Number(order.value.order_status || 0))) return '已完成支付'

    return '待开始'
})

const isBalancePendingPayment = computed(() => {
    if (!order.value) return false

    return (
        Number(order.value.order_status || -1) === 1 &&
        (order.value.need_pay === 'balance' ||
            (Number(order.value.deposit_amount || 0) > 0 &&
                Number(order.value.deposit_paid || 0) === 1 &&
                Number(order.value.balance_paid || 0) === 0 &&
                Number(order.value.balance_amount || 0) > 0))
    )
})

const progressItems = computed(() => [
    {
        label: '1. 档期确认',

        value:
            Number(order.value?.order_status || 0) >= 1
                ? '已完成'
                : showConfirmCountdown.value
                ? `${confirmCountdownText.value}${
                      confirmTimeoutActionText.value
                          ? `，超时后${confirmTimeoutActionText.value}`
                          : ''
                  }`
                : '待确认'
    },

    {
        label: '2. 支付进度',

        value:
            showPayCountdown.value && payTimeoutActionText.value
                ? `${paymentProgressText.value}，剩余 ${payCountdownText.value}，超时后${payTimeoutActionText.value}`
                : showPayCountdown.value
                ? `${paymentProgressText.value}，剩余 ${payCountdownText.value}`
                : paymentProgressText.value
    },

    {
        label: '3. 婚礼执行',

        value:
            Number(order.value?.order_status || 0) === 10
                ? '退款处理中'
                : Number(order.value?.order_status || 0) === 8
                ? '已结束'
                : primaryServiceDate.value || '待安排'
    },

    {
        label: '4. 尾款结清',

        value:
            Number(order.value?.order_status || 0) === 10
                ? '退款处理中'
                : Number(order.value?.order_status || 0) === 8
                ? '已退款'
                : Number(order.value?.balance_amount || 0) > 0
                ? order.value?.balance_paid
                    ? '已完成'
                    : '婚礼结束后结清'
                : '无尾款'
    }
])

const primaryVisibleAction = computed(() => {
    if (!order.value) return null

    const baseStyle = {
        background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,

        color: $theme.btnColor
    }

    if (canPayOnline.value) {
        return {
            key: 'pay',

            label: order.value?.need_pay_label || '立即支付',

            style: baseStyle,

            onClick: handlePay
        }
    }

    if (showOfflineCollectionCard.value && !canUploadVoucher.value) {
        return {
            key: 'contact',

            label: '联系顾问',

            style: baseStyle,

            onClick: handleContactAdvisor
        }
    }

    if (canUploadVoucher.value) {
        return {
            key: 'voucher',

            label: '上传凭证',

            style: baseStyle,

            onClick: () => {
                showVoucherPopup.value = true
            }
        }
    }

    if (
        Number(order.value.order_status) === 3 &&
        Number(order.value?.can_user_complete || 0) === 1
    ) {
        return {
            key: 'confirm',

            label: '确认完成',

            style: baseStyle,

            onClick: handleConfirm
        }
    }

    if (canGoReviewAction.value) {
        return {
            key: 'review',

            label: '去评价',

            style: baseStyle,

            onClick: goReviewFromOrder
        }
    }

    if (canViewReviewAction.value) {
        return {
            key: 'reviewDetail',

            label: '查看评价',

            style: baseStyle,

            onClick: goReviewDetailFromOrder
        }
    }

    return null
})

const canApplyRefund = computed(() => {
    return !!Number(order.value?.can_user_refund || 0)
})

const secondaryVisibleAction = computed(() => {
    if (showOfflineCollectionCard.value) {
        return {
            key: 'contact',

            label: '联系顾问',

            style: {
                borderColor: 'var(--wm-color-border, #D8C9AD)',

                color: 'var(--wm-text-primary, #191713)'
            },

            onClick: handleContactAdvisor
        }
    }

    if (canGoReviewAction.value && canViewReviewAction.value) {
        return {
            key: 'reviewDetail',

            label: '查看评价',

            style: {
                borderColor: 'var(--wm-color-border, #D8C9AD)',

                color: 'var(--wm-text-primary, #191713)'
            },

            onClick: goReviewDetailFromOrder
        }
    }

    return null
})

const hasPrimaryOrSecondaryAction = computed(
    () => Boolean(primaryVisibleAction.value) || Boolean(secondaryVisibleAction.value)
)

const moreActionItems = computed(() => {
    if (!order.value) return []

    const status = Number(order.value.order_status || -1)

    const items: Array<{
        key: string
        label: string
        description: string
        icon: string
        tone: 'default' | 'danger'
        onClick: () => void
    }> = []

    if ([0, 1].includes(status) && !isBalancePendingPayment.value) {
        items.push({
            key: 'cancel',
            label: '取消订单',
            description: '结束当前订单，取消后需重新预约',
            icon: 'close-circle',
            tone: 'danger',
            onClick: handleCancel
        })
    }

    if (
        canUploadVoucher.value &&
        primaryVisibleAction.value?.key !== 'voucher'
    ) {
        items.push({
            key: 'voucher',
            label: '上传凭证',
            description: '补充线下转账截图，提交后等待审核',
            icon: 'image',
            tone: 'default',

            onClick: () => {
                showVoucherPopup.value = true
            }
        })
    }

    if (canApplyRefund.value) {
        items.push({
            key: 'refund',
            label: '申请退款',
            description: '提交退款原因，等待平台审核处理',
            icon: 'refund',
            tone: 'danger',

            onClick: () => {
                showRefundPopup.value = true
            }
        })
    }

    if ([4, 5, 6, 8].includes(status) && !isBalancePendingPayment.value) {
        items.push({
            key: 'delete',
            label: '删除订单',
            description: '从订单列表移除该记录，操作需确认',
            icon: 'delete',
            tone: 'danger',
            onClick: handleDelete
        })
    }

    return items
})

const openMoreActions = () => {
    if (!moreActionItems.value.length) return

    showMoreActionsPopup.value = true
}

const handleMoreAction = (item: (typeof moreActionItems.value)[number]) => {
    showMoreActionsPopup.value = false

    setTimeout(() => {
        item.onClick()
    }, 180)
}

const clearPayCountdown = () => {
    if (payCountdownTimer) {
        clearInterval(payCountdownTimer)

        payCountdownTimer = null
    }
}

const clearConfirmCountdown = () => {
    if (confirmCountdownTimer) {
        clearInterval(confirmCountdownTimer)

        confirmCountdownTimer = null
    }
}

const syncPayCountdown = (seconds: number | string) => {
    clearPayCountdown()

    payCountdownSeconds.value = Math.max(Number(seconds || 0), 0)

    if (payCountdownSeconds.value <= 0) return

    payCountdownTimer = setInterval(async () => {
        if (payCountdownSeconds.value > 0) {
            payCountdownSeconds.value -= 1
        }

        if (payCountdownSeconds.value <= 0) {
            clearPayCountdown()

            if (payCountdownRefreshing) return

            payCountdownRefreshing = true

            try {
                await fetchDetail()
            } finally {
                payCountdownRefreshing = false
            }
        }
    }, 1000)
}

const syncConfirmCountdown = (seconds: number | string) => {
    clearConfirmCountdown()

    confirmCountdownSeconds.value = Math.max(Number(seconds || 0), 0)

    if (!showConfirmCountdown.value || confirmCountdownSeconds.value <= 0) return

    confirmCountdownTimer = setInterval(async () => {
        if (confirmCountdownSeconds.value > 0) {
            confirmCountdownSeconds.value -= 1
        }

        if (confirmCountdownSeconds.value <= 0) {
            clearConfirmCountdown()

            if (confirmCountdownRefreshing) return

            confirmCountdownRefreshing = true

            try {
                await fetchDetail()
            } finally {
                confirmCountdownRefreshing = false
            }
        }
    }, 1000)
}

const fetchDetail = async () => {
    if (orderId.value <= 0) {
        order.value = null
        clearOrderReviewEntry()
        detailLoading.value = false
        detailError.value = normalizePageRecoveryError(
            '缺少订单信息，请从订单列表重新进入',
            '缺少订单信息，请从订单列表重新进入'
        )
        return
    }

    if (detailRequestPromise) return detailRequestPromise

    detailLoading.value = !order.value
    detailError.value = null

    detailRequestPromise = (async () => {
        try {
            const detail = await getOrderDetail({ id: orderId.value })

            if (!detail?.id && !detail?.order_id) {
                throw new Error('订单不存在或已被删除，请返回订单列表查看')
            }

            order.value = detail

            await fetchPendingQuestionnaireTask()

            await fetchOrderReviewEntry()

            syncPayCountdown(order.value?.pay_remain_seconds || 0)

            syncConfirmCountdown(order.value?.confirm_remain_seconds || 0)

            hasLoadedOnce = true
        } catch (e: any) {
            order.value = null

            clearOrderReviewEntry()

            clearPayCountdown()

            clearConfirmCountdown()

            detailError.value = normalizePageRecoveryError(e, '加载订单详情失败，请稍后重试')
        } finally {
            detailLoading.value = false
            detailRequestPromise = null
        }
    })()

    return detailRequestPromise
}

const copyOrderSn = () => {
    if (!order.value?.order_sn) return

    uni.setClipboardData({
        data: order.value.order_sn,

        success: () => showSuccess('已复制订单编号')
    })
}

const handleDetailRecoveryAction = () => {
    if (detailError.value?.kind === 'auth') {
        goLoginWithBack(
            orderId.value > 0 ? `/pages/order_detail/order_detail?id=${orderId.value}` : '/pages/order/order'
        )
        return
    }

    void fetchDetail()
}

const handleContactAdvisor = () =>
    uni.navigateTo({
        url: `/packages/pages/customer_service/customer_service?scene=order_detail&order_id=${orderId.value}`
    })

const fetchPendingQuestionnaireTask = async () => {
    if (orderId.value <= 0) {
        pendingQuestionnaireTask.value = null

        return
    }

    try {
        const res = await getCoupleQuestionnaireLists({
            page: 1,

            limit: 1,

            status: 0,

            order_id: orderId.value
        })

        pendingQuestionnaireTask.value = normalizeQuestionnaireLists(res)[0] || null
    } catch {
        pendingQuestionnaireTask.value = null
    }
}

const goQuestionnaireTask = () => {
    const id = Number(pendingQuestionnaireTask.value?.id || 0)

    if (id <= 0) {
        return
    }

    uni.navigateTo({
        url: `/packages/pages/couple_questionnaire/detail?id=${id}`
    })
}

const handlePay = () => {
    if (paymentChannel.value !== 1 || isOfflineCollectionMode.value) {
        showError('该订单需线下收款，请联系顾问确认')

        return
    }

    if (Number(order.value?.pay_deadline_time || 0) > 0 && payCountdownSeconds.value <= 0) {
        showError('支付时间已到，正在刷新订单')

        fetchDetail()

        return
    }

    payState.paymentSn = ''

    payState.showPay = true
}

const handlePaySuccess = async (payload?: { paymentSn?: string }) => {
    payState.showPay = false

    payState.showCheck = false

    const paymentSn = String(payload?.paymentSn || payState.paymentSn || '')

    payState.paymentSn = ''

    uni.navigateTo({
        url: `/pages/payment_result/payment_result?id=${orderId.value}&from=${payState.from}${
            paymentSn ? `&payment_sn=${paymentSn}` : ''
        }`
    })
}

const handlePayFail = async (payload?: { reason?: string; message?: string }) => {
    if (payload?.reason === 'timeout') {
        await fetchDetail()

        return
    }

    if (payload?.reason === 'offline_collection') {
        await fetchDetail()

        return
    }

    showError(payload?.message || '支付失败，请重试')
}

const handleCancel = async () => {
    if (isBalancePendingPayment.value) {
        showError('服务已完成，待支付尾款，订单不可取消')
        return
    }

    const confirmed = await confirmModal({ title: '提示', content: '确定要取消该订单吗？' })

    if (!confirmed) return

    try {
        await cancelOrder({ id: orderId.value, reason: '用户取消' })

        showSuccess('订单已取消')

        await fetchDetail()
    } catch (e: any) {
        showError(e)
    }
}

const handleConfirm = async () => {
    const confirmed = await confirmModal({ title: '提示', content: '确定服务已完成吗？' })

    if (!confirmed) return

    try {
        await confirmOrder({ id: orderId.value })

        await fetchDetail()

        const successText =
            Number(order.value?.order_status || 0) === 1 ? '服务已完成，待支付尾款' : '订单已完成'

        showSuccess(successText)
    } catch (e: any) {
        showError(e)
    }
}

const handleDelete = async () => {
    if (isBalancePendingPayment.value) {
        showError('服务已完成，待支付尾款，订单不可删除')
        return
    }

    const confirmed = await confirmModal({ title: '提示', content: '确定要删除该订单吗？' })

    if (!confirmed) return

    try {
        await deleteOrder({ id: orderId.value })

        showSuccess('删除成功')

        setTimeout(() => uni.navigateBack(), 1500)
    } catch (e: any) {
        showError(e)
    }
}

const promptAfterSaleSubscribe = async () => {
    if (client !== ClientEnum.MP_WEIXIN) {
        return true
    }

    const confirmed = await confirmModal({
        title: '接收售后进度提醒',
        content: '订阅后可接收退款结果和工单进度提醒。',
        confirmText: '去订阅',
        cancelText: '暂不订阅'
    })

    if (!confirmed) {
        return false
    }

    try {
        await subscribeAfterSaleScenes()
    } catch (error) {
        console.error('请求售后订阅失败', error)
    }

    return true
}

const submitRefund = async () => {
    if (!canApplyRefund.value || refundApplyAmount.value <= 0) {
        showError('当前订单暂不支持申请退款')
        return
    }

    if (!refundForm.reason.trim()) {
        showError('请输入退款原因')
        return
    }

    try {
        await promptAfterSaleSubscribe()

        await applyRefund({
            id: orderId.value,

            reason: refundForm.reason
        })

        showSuccess('申请已提交')

        showRefundPopup.value = false

        refundForm.reason = ''

        await fetchDetail()
    } catch (e: any) {
        showError(e, '申请失败')
    }
}

const chooseVoucherImage = () => {
    if (voucherForm.uploading) return

    uni.chooseImage({
        count: 1,

        sizeType: ['compressed'],

        sourceType: ['album', 'camera'],

        success: async (res) => {
            const path = res.tempFilePaths?.[0]

            if (!path) return

            try {
                voucherForm.uploading = true

                const uploadRes: any = await uploadImage(path)

                if (uploadRes?.uri) voucherForm.image = uploadRes.uri
                else showError('上传失败，请重试')
            } catch (e: any) {
                showError(e, '上传失败')
            } finally {
                voucherForm.uploading = false
            }
        }
    })
}

const submitVoucher = async () => {
    if (voucherForm.uploading) return

    if (!canUploadVoucher.value) {
        showError('当前订单暂不支持上传凭证')

        return
    }

    if (!voucherForm.image) {
        showError('请先选择凭证图片')
        return
    }

    try {
        await uploadPayVoucher({ id: orderId.value, voucher: voucherForm.image })

        showSuccess('凭证已提交')

        showVoucherPopup.value = false

        voucherForm.image = ''

        await fetchDetail()
    } catch (e: any) {
        showError(e, '提交失败')
    }
}

onLoad(async (options: any) => {
    $theme.setScene('consumer')

    hasLoadedOnce = false

    hasBeenHidden = false

    detailLoading.value = true

    detailError.value = null

    order.value = null

    detailRequestPromise = null

    orderId.value = Number(options?.id || 0)

    if (options?.payment_sn) payState.paymentSn = String(options.payment_sn)

    if (options?.checkPay) payState.showCheck = true

    if (orderId.value > 0) {
        try {
            await fetchDetail()
        } catch (error) {
            void error
        }
    } else {
        await fetchDetail()
    }
})

onShow(() => {
    $theme.setScene('consumer')

    if (!hasLoadedOnce || !hasBeenHidden || orderId.value <= 0) {
        return
    }

    hasBeenHidden = false

    void fetchDetail()
})

onHide(() => {
    hasBeenHidden = hasLoadedOnce

    clearPayCountdown()

    clearConfirmCountdown()
})

onUnload(() => {
    detailRequestPromise = null

    hasLoadedOnce = false

    hasBeenHidden = false

    clearPayCountdown()

    clearConfirmCountdown()
})
</script>

<style lang="scss" scoped>
.order-detail {
    padding-bottom: calc(44rpx + env(safe-area-inset-bottom));

    background: var(--wm-color-page, #FFFDF8);
}

.order-detail--has-action {
    padding-bottom: calc(116rpx + env(safe-area-inset-bottom));
}

.order-detail--floating-more {
    padding-bottom: calc(104rpx + env(safe-area-inset-bottom));
}

.page-body {
    padding: 22rpx var(--wm-space-page-x, 37rpx) 37rpx;

    display: flex;

    flex-direction: column;

    gap: 22rpx;
}

.status-card {
    display: flex;

    flex-direction: column;

    padding: 34rpx 34rpx 37rpx;

    border-radius: var(--wm-radius-card-lg, 32rpx);
}

.status-card__heading {
    display: flex;

    align-items: center;

    min-width: 0;

    margin-bottom: 18rpx;
}

.status-card__badge {
    flex-shrink: 0;

    margin-right: 12rpx;
}

.status-card__title {
    flex: 1;

    min-width: 0;

    font-size: 44rpx;

    font-weight: 700;

    line-height: 1.35;

    color: var(--wm-text-primary, #111111);

    overflow: hidden;

    text-overflow: ellipsis;

    white-space: nowrap;
}

.status-card__sn {
    min-height: 54rpx;

    margin-bottom: 16rpx;

    padding: 0 22rpx;

    border-radius: 999rpx;

    display: inline-flex;

    align-items: center;

    align-self: flex-start;

    max-width: 100%;

    background: rgba(255, 253, 248, 0.78);

    border: 1rpx solid rgba(217, 190, 130, 0.7);

    box-sizing: border-box;
}

.status-card__sn-label {
    flex-shrink: 0;

    margin-right: 14rpx;

    font-size: 22rpx;

    font-weight: 600;

    color: var(--wm-text-secondary, #665E52);
}

.status-card__sn-value {
    min-width: 0;

    font-size: 24rpx;

    font-weight: 700;

    color: var(--wm-color-primary, #0B0B0B);

    overflow: hidden;

    text-overflow: ellipsis;

    white-space: nowrap;
}

.status-card__meta {
    display: flex;

    flex-wrap: wrap;

    margin: -7rpx -8rpx 0;
}

.status-card__meta-item {
    min-height: 52rpx;

    margin: 7rpx 8rpx;

    padding: 0 24rpx;

    border-radius: 999rpx;

    display: inline-flex;

    align-items: center;

    background: rgba(255, 253, 248, 0.76);

    border: 1rpx solid rgba(217, 190, 130, 0.82);

    box-sizing: border-box;
}

.status-card__meta-label {
    margin-right: 10rpx;

    font-size: 22rpx;

    color: var(--wm-text-secondary, #5f5a50);
}

.status-card__meta-value {
    font-size: 24rpx;

    font-weight: 700;

    color: var(--wm-color-primary, #0b0b0b);
}

.detail-card {
    display: block;
}

.card__title {
    font-size: 28rpx;

    font-weight: 700;

    line-height: 1.4;

    color: var(--wm-text-primary, #111111);
}

.card__title-row {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 16rpx;
}

.card__title-actions {
    display: inline-flex;

    align-items: center;

    gap: 20rpx;

    flex-wrap: wrap;

    justify-content: flex-end;
}

.service-summary {
    display: flex;

    flex-direction: column;

    gap: 18rpx;

    padding: 28rpx;

    border-radius: 34rpx;

    background: linear-gradient(180deg, rgba(248, 247, 242, 0.96) 0%, #ffffff 100%);

    border: 1rpx solid var(--wm-color-border, #e7e2d6);
}

.service-summary__topbar {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20rpx;
}

.service-summary__label {
    font-size: 22rpx;

    font-weight: 600;

    color: var(--wm-text-tertiary, #9a9388);
}

.service-summary__title {
    font-size: 30rpx;

    font-weight: 700;

    line-height: 1.5;

    color: var(--wm-text-primary, #111111);
}

.service-summary__price {
    flex-shrink: 0;

    font-size: 30rpx;

    font-weight: 700;

    line-height: 1.4;

    color: var(--wm-color-primary, #0b0b0b);
}

.service-summary__meta-grid {
    display: flex;

    flex-direction: column;

    gap: 14rpx;
}

.service-summary__meta-card {
    padding: 20rpx 22rpx;

    border-radius: 26rpx;

    border: 1rpx solid rgba(231, 226, 214, 0.92);

    background: rgba(255, 255, 255, 0.94);

    display: flex;

    flex-direction: column;

    gap: 6rpx;
}

.service-summary__meta-label {
    font-size: 22rpx;

    color: var(--wm-text-tertiary, #9a9388);
}

.service-summary__meta-value {
    font-size: 24rpx;

    line-height: 1.6;

    color: var(--wm-text-primary, #111111);
}

.service-addon-section {
    display: flex;

    flex-direction: column;

    gap: 18rpx;

    padding-top: 4rpx;
}

.service-addon-section__header {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 16rpx;
}

.service-addon-section__title {
    font-size: 26rpx;

    font-weight: 700;

    line-height: 1.5;

    color: var(--wm-text-primary, #111111);
}

.service-addon-section__meta {
    flex-shrink: 0;

    font-size: 22rpx;

    color: var(--wm-text-tertiary, #9a9388);
}

.service-addon-list {
    display: flex;

    flex-direction: column;

    gap: 14rpx;
}

.service-addon-item {
    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 20rpx;

    padding: 22rpx 24rpx;

    border-radius: 30rpx;

    border: 1rpx solid var(--wm-color-border, #e7e2d6);

    background: #ffffff;
}

.service-addon-item__copy {
    flex: 1;

    min-width: 0;

    display: flex;

    flex-direction: column;

    gap: 8rpx;
}

.service-addon-item__title-row {
    display: flex;

    align-items: center;

    flex-wrap: wrap;

    gap: 10rpx;
}

.service-addon-item__title {
    font-size: 26rpx;

    font-weight: 600;

    line-height: 1.5;

    color: var(--wm-text-primary, #111111);
}

.service-addon-item__type {
    min-height: 38rpx;

    padding: 0 14rpx;

    border-radius: 999rpx;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    font-size: 20rpx;

    font-weight: 600;

    color: var(--wm-color-primary, #0b0b0b);

    background: rgba(247, 240, 223, 0.92);

    border: 1rpx solid var(--wm-color-border-strong, #d8c28a);

    box-sizing: border-box;
}

.service-addon-item__desc {
    font-size: 22rpx;

    line-height: 1.6;

    color: var(--wm-text-secondary, #5f5a50);
}

.service-addon-item__meta {
    font-size: 22rpx;

    line-height: 1.6;

    color: var(--wm-text-tertiary, #9a9388);
}

.service-addon-item__price {
    flex-shrink: 0;

    font-size: 28rpx;

    font-weight: 700;

    line-height: 1.5;

    color: var(--wm-color-primary, #0b0b0b);
}

.service-addon-item--related {
    background: linear-gradient(180deg, #F3F2EE 0%, #ffffff 100%);

    border-color: rgba(231, 226, 214, 0.96);
}

.service-addon-item__type--related {
    color: #4D4A42;

    background: rgba(248, 247, 242, 0.96);

    border-color: rgba(231, 226, 214, 0.96);
}

.service-addon-empty {
    padding: 28rpx 30rpx;

    border-radius: 28rpx;

    border: 1rpx dashed var(--wm-color-border, #e7e2d6);

    background: #ffffff;
}

.service-addon-empty text {
    font-size: 24rpx;

    line-height: 1.6;

    color: var(--wm-text-tertiary, #9a9388);
}

.inline-link {
    padding-top: 4rpx;
}

.inline-link__text,
.inline-copy__text {
    font-size: 24rpx;

    font-weight: 600;

    color: var(--wm-color-primary, #0b0b0b);
}

.sub-panel {
    display: flex;

    flex-direction: column;

    gap: 0;
}

.sub-panel__row {
    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 24rpx;

    padding: 18rpx 0;
}

.sub-panel__row + .sub-panel__row {
    border-top: 1rpx solid var(--wm-color-border, #e7e2d6);
}

.sub-panel__row--stack {
    flex-direction: column;

    gap: 12rpx;
}

.sub-panel__label {
    flex-shrink: 0;

    font-size: 24rpx;

    line-height: 1.5;

    color: var(--wm-text-secondary, #5f5a50);
}

.sub-panel__value {
    flex: 1;

    font-size: 26rpx;

    line-height: 1.6;

    text-align: right;

    color: var(--wm-text-primary, #111111);
}

.sub-panel__value--left {
    text-align: left;
}

.sub-panel__value--danger {
    color: #5a4433;
}

.progress-list {
    display: flex;

    flex-direction: column;

    gap: 16rpx;
}

.progress-list__row {
    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 20rpx;
}

.progress-list__label {
    flex-shrink: 0;

    font-size: 24rpx;

    line-height: 1.6;

    color: var(--wm-text-primary, #111111);
}

.progress-list__value {
    flex: 1;

    font-size: 24rpx;

    line-height: 1.6;

    text-align: right;

    color: var(--wm-text-secondary, #5f5a50);
}

.info-list {
    display: flex;

    flex-direction: column;

    gap: 10rpx;
}

.info-list__row {
    display: flex;
}

.info-list__main {
    font-size: 26rpx;

    font-weight: 600;

    line-height: 1.65;

    color: var(--wm-text-primary, #111111);
}

.info-list__sub {
    font-size: 24rpx;

    line-height: 1.65;

    color: var(--wm-text-secondary, #5f5a50);
}

.voucher-image {
    margin-top: 4rpx;

    border-radius: var(--wm-radius-card-lg, 28rpx);

    overflow: hidden;

    background: var(--wm-color-bg-soft, #ffffff);
}

.voucher-image image {
    width: 100%;

    height: 420rpx;

    display: block;
}

.voucher-empty {
    margin-top: 4rpx;

    min-height: 160rpx;

    border-radius: var(--wm-radius-card-lg, 28rpx);

    display: flex;

    align-items: center;

    justify-content: center;

    background: var(--wm-color-bg-soft, #ffffff);
}

.voucher-empty text {
    font-size: 24rpx;

    color: var(--wm-text-tertiary, #9a9388);
}

.offline-collection {
    display: flex;

    flex-direction: column;

    gap: 22rpx;
}

.offline-collection__text {
    font-size: 26rpx;

    line-height: 1.7;

    color: var(--wm-text-secondary, #665E52);
}

.offline-collection__button {
    height: 76rpx;

    border-radius: 38rpx;

    display: flex;

    align-items: center;

    justify-content: center;

    background: var(--wm-color-primary, #5a4433);
}

.offline-collection__button-text {
    font-size: 26rpx;

    font-weight: 900;

    color: var(--wm-btn-color, #ffffff);
}

.refund-status {
    padding: 8rpx 18rpx;

    border-radius: 999rpx;

    font-size: 22rpx;

    font-weight: 600;
}

.refund-item-list {
    display: flex;

    flex-direction: column;

    gap: 16rpx;

    width: 100%;
}

.refund-item {
    display: flex;

    flex-direction: column;

    gap: 8rpx;

    padding: 20rpx 22rpx;

    border-radius: 24rpx;

    background: rgba(248, 247, 242, 0.88);

    border: 1rpx solid rgba(231, 226, 214, 0.9);
}

.refund-item__head {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 16rpx;
}

.refund-item__title {
    font-size: 24rpx;

    font-weight: 600;

    color: var(--wm-text-primary, #111111);
}

.refund-item__amount {
    font-size: 24rpx;

    font-weight: 700;

    color: var(--wm-color-primary, #191713);
}

.refund-item__meta {
    display: flex;

    flex-wrap: wrap;

    gap: 12rpx 18rpx;

    font-size: 22rpx;

    color: var(--wm-text-secondary, #5f5a50);
}

.refund-item__desc {
    font-size: 22rpx;

    line-height: 1.6;

    color: var(--wm-text-secondary, #5f5a50);
}

.order-detail__action-area {
    --wm-space-action-top: 14rpx;
    --wm-space-action-x: 24rpx;
    --wm-space-action-bottom: 18rpx;
}

.action-bar {
    display: flex;

    align-items: center;

    gap: 14rpx;

    width: 100%;

    padding-bottom: 0;

    background: transparent;

    border-top: 0;
}

.action-bar__buttons {
    display: flex;

    flex: 1;

    gap: 14rpx;

    min-width: 0;
}

.action-bar__more {
    flex-shrink: 0;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 6rpx;

    min-width: 104rpx;
    min-height: 68rpx;

    padding: 0 16rpx;

    box-sizing: border-box;

    border-radius: var(--wm-radius-pill, 999rpx);

    background: rgba(255, 255, 255, 0.96);

    border: 1rpx solid var(--wm-color-border, #e7e2d6);

    box-shadow: 0 8rpx 18rpx rgba(11, 11, 11, 0.05);

    transition: transform var(--wm-motion-fast, 160ms) ease,
        box-shadow var(--wm-motion-fast, 160ms) ease;
}

.action-bar__more:active {
    transform: scale(0.98);

    box-shadow: 0 6rpx 14rpx rgba(11, 11, 11, 0.05);
}

.action-bar__more-text {
    font-size: 22rpx;

    font-weight: 600;

    color: var(--wm-text-secondary, #5f5a50);

    white-space: nowrap;
}

.more-floating-action {
    position: fixed;

    right: 32rpx;

    bottom: calc(34rpx + env(safe-area-inset-bottom));

    z-index: 90;

    min-width: 120rpx;

    height: 72rpx;

    padding: 0 20rpx;

    box-sizing: border-box;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 8rpx;

    border-radius: var(--wm-radius-pill, 999rpx);

    background: rgba(255, 255, 255, 0.98);

    border: 1rpx solid var(--wm-color-border, #e7e2d6);

    box-shadow: 0 10rpx 24rpx rgba(11, 11, 11, 0.1);

    transition: transform var(--wm-motion-fast, 160ms) ease,
        box-shadow var(--wm-motion-fast, 160ms) ease;
}

.more-floating-action:active {
    transform: scale(0.98);

    box-shadow: 0 6rpx 16rpx rgba(11, 11, 11, 0.08);
}

.more-floating-action__text {
    font-size: 24rpx;

    font-weight: 900;

    line-height: 1;

    color: var(--wm-text-primary, #191713);

    white-space: nowrap;
}

.more-actions-sheet {
    background: rgba(255, 255, 255, 0.98);

    border-top-left-radius: var(--wm-radius-popup, 24rpx);

    border-top-right-radius: var(--wm-radius-popup, 24rpx);

    padding: 34rpx 32rpx 38rpx;
}

.more-actions-sheet__heading {
    min-width: 0;

    display: flex;

    flex-direction: column;
}

.more-actions-sheet__list {
    display: flex;

    flex-direction: column;

    gap: 16rpx;

    margin-top: 30rpx;
}

.more-action-item {
    display: flex;

    align-items: center;

    gap: 20rpx;

    min-height: 116rpx;

    padding: 20rpx 22rpx;

    box-sizing: border-box;

    border-radius: 28rpx;

    background: var(--wm-color-bg-soft, #fbfaf7);

    border: 1rpx solid var(--wm-color-border, #e7e2d6);

    transition: transform var(--wm-motion-fast, 160ms) ease,
        background var(--wm-motion-fast, 160ms) ease;
}

.more-action-item:active {
    transform: scale(0.99);

    background: #f7f1e6;
}

.more-action-item--danger {
    background: rgba(138, 75, 69, 0.06);

    border-color: rgba(138, 75, 69, 0.18);
}

.more-action-item__icon {
    flex-shrink: 0;

    width: 66rpx;

    height: 66rpx;

    border-radius: 50%;

    display: flex;

    align-items: center;

    justify-content: center;

    background: rgba(255, 255, 255, 0.9);

    border: 1rpx solid rgba(231, 226, 214, 0.9);
}

.more-action-item--danger .more-action-item__icon {
    background: rgba(255, 255, 255, 0.86);

    border-color: rgba(138, 75, 69, 0.2);
}

.more-action-item__body {
    flex: 1;

    min-width: 0;

    display: flex;

    flex-direction: column;

    gap: 8rpx;
}

.more-action-item__label {
    font-size: 28rpx;

    font-weight: 700;

    line-height: 1.2;

    color: var(--wm-text-primary, #191713);
}

.more-action-item--danger .more-action-item__label {
    color: var(--wm-color-danger, #8a4b45);
}

.more-action-item__desc {
    font-size: 22rpx;

    line-height: 1.45;

    color: var(--wm-text-tertiary, #9a9388);
}

.popup {
    background: rgba(255, 255, 255, 0.98);

    border-top-left-radius: var(--wm-radius-popup, 24rpx);

    border-top-right-radius: var(--wm-radius-popup, 24rpx);

    padding: 34rpx 37rpx 37rpx;
}

.popup__header {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20rpx;
}

.popup__title {
    font-size: 32rpx;

    font-weight: 700;

    color: var(--wm-text-primary, #111111);
}

.popup__content {
    margin-top: 28rpx;
}

.form-item + .form-item {
    margin-top: 20rpx;
}

.form-item__label {
    display: block;

    margin-bottom: 12rpx;

    font-size: 26rpx;

    font-weight: 600;

    color: var(--wm-text-primary, #111111);
}

.form-item__tip {
    display: block;

    margin-top: 8rpx;

    font-size: 22rpx;

    color: var(--wm-text-tertiary, #9a9388);
}

.refund-amount-card {
    display: flex;

    flex-direction: column;

    gap: 10rpx;

    padding: 24rpx 28rpx;

    border-radius: 28rpx;

    background: var(--wm-color-bg-soft, #ffffff);

    border: 2rpx solid var(--wm-color-border, #e7e2d6);
}

.refund-amount-card__value {
    font-size: 34rpx;

    font-weight: 700;

    color: var(--wm-color-danger, #5a4433);
}

.refund-amount-card__tip {
    font-size: 22rpx;

    line-height: 1.6;

    color: var(--wm-text-tertiary, #9a9388);
}

.popup__actions {
    display: flex;

    gap: 22rpx;

    margin-top: 28rpx;
}

.popup__action {
    flex: 1;
}

.voucher-upload {
    margin-top: 16rpx;
}

.voucher-upload__preview,
.voucher-upload__add {
    width: 100%;

    height: 360rpx;

    border-radius: var(--wm-radius-card-lg, 28rpx);

    overflow: hidden;
}

.voucher-upload__preview {
    position: relative;

    background: var(--wm-color-bg-soft, #ffffff);
}

.voucher-upload__preview image {
    width: 100%;

    height: 100%;

    display: block;
}

.voucher-upload__remove {
    position: absolute;

    top: 16rpx;

    right: 16rpx;

    width: 48rpx;

    height: 48rpx;

    border-radius: 50%;

    background: rgba(17, 17, 17, 0.58);

    display: flex;

    align-items: center;

    justify-content: center;
}

.voucher-upload__add {
    border: 2rpx dashed var(--wm-color-border, #e7e2d6);

    background: var(--wm-color-bg-soft, #ffffff);

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;
}

.voucher-upload__text {
    margin-top: 16rpx;

    font-size: 28rpx;

    color: var(--wm-text-secondary, #665E52);

    font-weight: 600;
}

.voucher-upload__tip {
    margin-top: 8rpx;

    font-size: 22rpx;

    color: var(--wm-text-tertiary, #9a9388);
}

.safe-bottom {
    height: calc(112rpx + env(safe-area-inset-bottom));
}

.loading-container,
.detail-state-shell {
    min-height: 100vh;

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;

    gap: 16rpx;

    background: var(--wm-color-bg-page, #ffffff);
}

.detail-state-shell {
    box-sizing: border-box;

    padding-bottom: calc(160rpx + env(safe-area-inset-bottom));
}

.detail-state-shell__actions {
    display: flex;

    align-items: center;

    justify-content: center;

    gap: 16rpx;
}

.detail-state-shell__link {
    min-height: 64rpx;

    padding: 0 18rpx;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    font-size: 24rpx;

    font-weight: 600;

    color: var(--wm-text-secondary, #5f5a50);
}

.detail-state-shell__divider {
    width: 1rpx;

    height: 28rpx;

    background: var(--wm-color-border, #e7e2d6);
}

.loading-text {
    font-size: 26rpx;

    color: var(--wm-text-tertiary, #9a9388);
}

/* 订单详情组件化后的局部 token 覆盖，避免旧黑金色值继续影响新信息组。 */
.status-card__title,
.card__title,
.service-summary__title,
.service-addon-section__title,
.service-addon-item__title,
.refund-item__title,
.more-floating-action__text,
.more-action-item__label,
.popup__title,
.form-item__label {
    color: var(--wm-text-primary, #191713);
}

.status-card__meta-label,
.service-addon-item__desc,
.offline-collection__text,
.refund-item__meta,
.refund-item__desc,
.action-bar__more-text,
.detail-state-shell__link {
    color: var(--wm-text-secondary, #665E52);
}

.service-summary__label,
.service-addon-section__meta,
.service-addon-item__meta,
.service-addon-empty text,
.voucher-empty text,
.more-action-item__desc,
.form-item__tip,
.refund-amount-card__tip,
.voucher-upload__tip,
.loading-text {
    color: var(--wm-text-tertiary, #8A806F);
}

.status-card__meta-value,
.inline-link__text,
.inline-copy__text,
.service-summary__price,
.service-addon-item__price,
.refund-item__amount {
    color: var(--wm-color-gold, #B8954A);
}

.service-summary {
    background: linear-gradient(180deg, rgba(250, 246, 238, 0.96) 0%, #FFFDF8 100%);
    border-color: var(--wm-color-border, #D8C9AD);
}

.service-summary__meta-list {
    padding: 4rpx 22rpx;
    border-radius: 26rpx;
    border: 1rpx solid rgba(216, 201, 173, 0.92);
    background: rgba(255, 253, 248, 0.94);
}

.service-addon-item,
.service-addon-empty {
    background: #FFFDF8;
    border-color: var(--wm-color-border, #D8C9AD);
}

.service-addon-item__type {
    color: var(--wm-color-clay, #9A6B35);
    background: rgba(241, 229, 200, 0.92);
    border-color: var(--wm-color-border-strong, #D9BE82);
}

.service-addon-item--related {
    background: linear-gradient(180deg, #FAF6EE 0%, #FFFDF8 100%);
    border-color: rgba(216, 201, 173, 0.96);
}

.service-addon-item__type--related {
    color: var(--wm-text-secondary, #665E52);
    background: rgba(250, 246, 238, 0.96);
    border-color: rgba(216, 201, 173, 0.96);
}

.detail-info-list {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.progress-info-list {
    margin-top: 4rpx;

    border-top: 1rpx solid var(--wm-list-divider, rgba(216, 201, 173, 0.72));
}

.progress-info-row {
    min-height: 88rpx;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 24rpx;

    border-bottom: 1rpx solid var(--wm-list-divider, rgba(216, 201, 173, 0.72));
}

.progress-info-row:last-child {
    border-bottom: none;
}

.progress-info-row__label {
    flex-shrink: 0;

    font-size: 24rpx;

    font-weight: 800;

    color: var(--wm-text-secondary, #665E52);
}

.progress-info-row__value {
    flex: 1;

    min-width: 0;

    text-align: right;

    font-size: 26rpx;

    font-weight: 900;

    line-height: 1.45;

    color: var(--wm-text-primary, #191713);

    overflow: hidden;

    text-overflow: ellipsis;

    white-space: nowrap;
}

.detail-info-list :deep(.base-info-row + .base-info-row),
.detail-info-row + :deep(.base-info-row),
.detail-info-list :deep(.base-info-row) + .detail-info-row {
    border-top: 1rpx solid var(--wm-list-divider, rgba(216, 201, 173, 0.72));
}

.detail-info-list :deep(.base-info-row--multiline) {
    min-height: 92rpx;
}

.detail-info-list--nested {
    margin-top: 18rpx;
    padding: 8rpx 22rpx;
    border-radius: 26rpx;
    background: var(--wm-color-bg-soft, #FAF6EE);
    border: 1rpx solid var(--wm-color-border, #D8C9AD);
}

.detail-info-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 24rpx;
    padding: 18rpx 0;
}

.detail-info-row--stack {
    flex-direction: column;
    gap: 12rpx;
}

.detail-info-row__label {
    flex-shrink: 0;
    font-size: 24rpx;
    line-height: 1.5;
    font-weight: 800;
    color: var(--wm-text-secondary, #665E52);
}

.voucher-image,
.voucher-empty,
.refund-amount-card,
.voucher-upload__preview,
.voucher-upload__add {
    background: var(--wm-color-bg-soft, #FAF6EE);
}

.offline-collection__button {
    background: var(--wm-color-primary, #191713);
}

.offline-collection__button-text {
    color: var(--wm-btn-color, #FFFDF8);
}

.refund-item {
    background: rgba(250, 246, 238, 0.88);
    border-color: rgba(216, 201, 173, 0.9);
}

.action-bar__more,
.more-floating-action {
    background: rgba(255, 253, 248, 0.98);
    border-color: var(--wm-color-border, #D8C9AD);
    box-shadow: 0 10rpx 24rpx rgba(74, 43, 24, 0.08);
}

.more-actions-sheet,
.popup {
    background: rgba(255, 253, 248, 0.98);
}

.more-action-item {
    background: var(--wm-color-bg-soft, #FAF6EE);
    border-color: var(--wm-color-border, #D8C9AD);
}

.more-action-item:active {
    background: var(--wm-color-gold-soft, #F1E5C8);
}

.more-action-item--danger {
    background: rgba(154, 107, 53, 0.08);
    border-color: rgba(154, 107, 53, 0.2);
}

.more-action-item__icon {
    background: rgba(255, 253, 248, 0.9);
    border-color: rgba(216, 201, 173, 0.9);
}

.more-action-item--danger .more-action-item__icon {
    background: rgba(255, 253, 248, 0.86);
    border-color: rgba(154, 107, 53, 0.22);
}

.more-action-item--danger .more-action-item__label,
.refund-amount-card__value {
    color: var(--wm-color-danger, #9A6B35);
}

.refund-amount-card,
.voucher-upload__add {
    border-color: var(--wm-color-border, #D8C9AD);
}

.loading-container,
.detail-state-shell {
    background: var(--wm-color-bg-page, #FFFDF8);
}

.detail-state-shell__divider {
    background: var(--wm-color-border, #D8C9AD);
}
</style>
