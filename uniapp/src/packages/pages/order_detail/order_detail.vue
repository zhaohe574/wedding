<template>
    <page-meta :page-style="$theme.pageStyle" />

    <PageShell scene="consumer" tone="workspace" hasSafeBottom>
        <BaseNavbar
            title="订单详情"
            title-align="center"
            variant="solid"
            bg-color="#181614"
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
            <view class="order-detail__body wm-page-content">
                <!-- 1. 沉浸式黑金奢华状态 Hero 卡片 -->
                <view class="status-hero">
                    <view class="status-hero__top">
                        <view class="status-hero__main">
                            <view class="status-hero__badge-row">
                                <StatusBadge
                                    :tone="getOrderStatusTone(Number(order.order_status || 0))"
                                    size="sm"
                                    dot
                                >
                                    {{ order.order_status_desc }}
                                </StatusBadge>
                            </view>
                            <text class="status-hero__title">{{ statusHeadline }}</text>
                        </view>
                        <view class="status-hero__sn-box" @click="copyOrderSn">
                            <text class="status-hero__sn-text">{{ order.order_sn || '-' }}</text>
                            <BaseIcon name="copy" size="20" color="#D9BE82" />
                        </view>
                    </view>

                    <!-- 动态倒计时 / 预警芯片条 -->
                    <view
                        v-if="
                            showConfirmCountdown ||
                            showConfirmTimeoutAction ||
                            showPayCountdown ||
                            showPayTimeoutAction ||
                            showVoucherPending ||
                            showNeedPayMeta
                        "
                        class="status-hero__alert-strip"
                    >
                        <view v-if="showConfirmCountdown" class="status-hero__alert-item">
                            <BaseIcon name="clock" size="22" color="#D9BE82" />
                            <text class="status-hero__alert-label">确认剩余</text>
                            <text class="status-hero__alert-value">{{ confirmCountdownText }}</text>
                        </view>

                        <view v-if="showConfirmTimeoutAction" class="status-hero__alert-item">
                            <text class="status-hero__alert-desc">({{ confirmTimeoutActionText }})</text>
                        </view>

                        <view v-if="showPayCountdown" class="status-hero__alert-item">
                            <BaseIcon name="clock" size="22" color="#E57373" />
                            <text class="status-hero__alert-label">支付剩余</text>
                            <text class="status-hero__alert-value">{{ payCountdownText }}</text>
                        </view>

                        <view v-if="showPayTimeoutAction" class="status-hero__alert-item">
                            <text class="status-hero__alert-desc">({{ payTimeoutActionText }})</text>
                        </view>

                        <view v-if="showVoucherPending" class="status-hero__alert-item status-hero__alert-item--gold">
                            <text class="status-hero__alert-label">线下凭证</text>
                            <text class="status-hero__alert-value">审核中</text>
                        </view>

                        <view v-if="showNeedPayMeta" class="status-hero__alert-item status-hero__alert-item--highlight">
                            <text class="status-hero__alert-label">{{ needPayMetaLabel }}</text>
                            <text class="status-hero__alert-value">¥{{ formatAmount(needPayAmount) }}</text>
                        </view>
                    </view>

                    <!-- 婚礼全周期履约里程碑轨迹 (Milestone Stepper) -->
                    <view class="milestone-stepper">
                        <view
                            v-for="(step, sIdx) in milestoneSteps"
                            :key="step.key"
                            class="milestone-stepper__item"
                            :class="[`milestone-stepper__item--${step.state}`]"
                        >
                            <view class="milestone-stepper__node-wrap">
                                <view class="milestone-stepper__node">
                                    <BaseIcon
                                        v-if="step.state === 'done'"
                                        name="check"
                                        size="20"
                                        color="#181614"
                                    />
                                    <view v-else-if="step.state === 'active'" class="milestone-stepper__pulse-dot" />
                                    <view v-else class="milestone-stepper__pending-dot" />
                                </view>
                                <view
                                    v-if="sIdx < milestoneSteps.length - 1"
                                    class="milestone-stepper__line"
                                    :class="{ 'milestone-stepper__line--done': step.state === 'done' }"
                                />
                            </view>
                            <text class="milestone-stepper__title">{{ step.title }}</text>
                            <text class="milestone-stepper__sub">{{ step.subtext }}</text>
                        </view>
                    </view>
                </view>

                <!-- 2. 服务信息与匠人名片卡片 -->
                <view class="detail-card">
                    <view class="detail-card__header">
                        <view class="detail-card__title-row">
                            <text class="detail-card__title">服务信息</text>
                            <text class="detail-card__subtitle">专属定制方案</text>
                        </view>
                        <view class="detail-card__contact-btn" @click="handleContactAdvisor">
                            <BaseIcon name="service" size="22" color="#C6A15B" />
                            <text class="detail-card__contact-text">联系顾问</text>
                        </view>
                    </view>

                    <!-- 主服务方案卡 -->
                    <view class="service-hero">
                        <view class="service-hero__head">
                            <view class="service-hero__avatar-ring">
                                <image
                                    class="service-hero__avatar"
                                    :src="primaryItem?.staff?.avatar || primaryItem?.staff_avatar || '/static/images/user/default_avatar.png'"
                                    mode="aspectFill"
                                />
                            </view>
                            <view class="service-hero__info">
                                <view class="service-hero__title-row">
                                    <text class="service-hero__staff-name">{{ primaryStaffName }}</text>
                                    <text class="service-hero__tag">主服务团队</text>
                                </view>
                                <text class="service-hero__package-title">{{ serviceCardTitle }}</text>
                            </view>
                            <view class="service-hero__price-box">
                                <text class="service-hero__price-label">主套餐</text>
                                <text class="service-hero__price">¥{{ formatAmount(primaryServiceAmount) }}</text>
                            </view>
                        </view>

                        <view class="service-hero__meta-grid">
                            <view class="service-hero__meta-cell">
                                <view class="service-hero__meta-icon">
                                    <BaseIcon name="calendar" size="22" color="#C6A15B" />
                                </view>
                                <view class="service-hero__meta-content">
                                    <text class="service-hero__meta-label">服务档期</text>
                                    <text class="service-hero__meta-value">{{ primaryServiceDate }}</text>
                                </view>
                            </view>
                            <view class="service-hero__meta-cell">
                                <view class="service-hero__meta-icon">
                                    <BaseIcon name="location" size="22" color="#C6A15B" />
                                </view>
                                <view class="service-hero__meta-content">
                                    <text class="service-hero__meta-label">举办场地</text>
                                    <text class="service-hero__meta-value">{{ order.service_region_text || order.service_address || '待确认场地地点' }}</text>
                                </view>
                            </view>
                        </view>
                    </view>

                    <!-- 附加套餐列表 -->
                    <view class="addon-block">
                        <view class="addon-block__head">
                            <text class="addon-block__title">附加套餐</text>
                            <text class="addon-block__count">{{ serviceAddonSummaryText }}</text>
                        </view>

                        <view v-if="serviceAddonRows.length" class="addon-list">
                            <view
                                v-for="item in serviceAddonRows"
                                :key="item.key"
                                class="addon-item"
                            >
                                <view class="addon-item__left">
                                    <view class="addon-item__title-row">
                                        <text class="addon-item__title">{{ item.title }}</text>
                                        <text class="addon-item__badge">{{ item.typeText }}</text>
                                    </view>
                                    <text v-if="item.metaText" class="addon-item__meta">{{ item.metaText }}</text>
                                    <text v-if="item.description" class="addon-item__desc">{{ item.description }}</text>
                                </view>
                                <text class="addon-item__price">{{ item.priceText }}</text>
                            </view>
                        </view>
                        <view v-else class="addon-empty">
                            <text class="addon-empty__text">当前方案未包含附加套餐</text>
                        </view>
                    </view>

                    <!-- 协作服务列表 -->
                    <view v-if="serviceRelatedRows.length" class="addon-block">
                        <view class="addon-block__head">
                            <text class="addon-block__title">协作服务团队</text>
                            <text class="addon-block__count">共 {{ serviceRelatedRows.length }} 项</text>
                        </view>

                        <view class="addon-list">
                            <view
                                v-for="item in serviceRelatedRows"
                                :key="item.key"
                                class="addon-item addon-item--related"
                            >
                                <view class="addon-item__left">
                                    <view class="addon-item__title-row">
                                        <text class="addon-item__title">{{ item.title }}</text>
                                        <text class="addon-item__badge addon-item__badge--related">{{ item.typeText }}</text>
                                    </view>
                                    <text v-if="item.metaText" class="addon-item__meta">{{ item.metaText }}</text>
                                    <text v-if="item.description" class="addon-item__desc">{{ item.description }}</text>
                                </view>
                                <text class="addon-item__price">{{ item.priceText }}</text>
                            </view>
                        </view>
                    </view>
                </view>

                <!-- 3. 金额与履约进度卡片 -->
                <view class="detail-card">
                    <view class="detail-card__header">
                        <view class="detail-card__title-row">
                            <text class="detail-card__title">金额与进度</text>
                            <text class="detail-card__subtitle">款项明细透明清晰</text>
                        </view>
                    </view>

                    <!-- 三栏核心金融看板 -->
                    <view class="finance-trio">
                        <view class="finance-trio__cell">
                            <text class="finance-trio__label">订单总价</text>
                            <view class="finance-trio__price-row">
                                <text class="finance-trio__symbol">¥</text>
                                <text class="finance-trio__value">{{ formatAmount(totalOrderAmount) }}</text>
                            </view>
                        </view>
                        <view class="finance-trio__cell finance-trio__cell--paid">
                            <text class="finance-trio__label">已支付</text>
                            <view class="finance-trio__price-row">
                                <text class="finance-trio__symbol">¥</text>
                                <text class="finance-trio__value">{{ formatAmount(paidAmount) }}</text>
                            </view>
                        </view>
                        <view class="finance-trio__cell finance-trio__cell--pending">
                            <text class="finance-trio__label">待支付</text>
                            <view class="finance-trio__price-row">
                                <text class="finance-trio__symbol">¥</text>
                                <text class="finance-trio__value">{{ formatAmount(pendingAmount) }}</text>
                            </view>
                        </view>
                    </view>

                    <!-- 进度列表 -->
                    <view class="progress-timeline">
                        <view
                            v-for="item in progressItems"
                            :key="item.label"
                            class="progress-timeline__row"
                        >
                            <text class="progress-timeline__label">{{ item.label }}</text>
                            <text class="progress-timeline__value">{{ item.value }}</text>
                        </view>
                    </view>

                    <!-- 查看/收起详细金额 -->
                    <view
                        v-if="hasFinancialDetails"
                        class="detail-collapse-toggle"
                        @click="showFinancialDetails = !showFinancialDetails"
                    >
                        <text class="detail-collapse-toggle__text">
                            {{ showFinancialDetails ? '收起详细金额' : '查看详细金额' }}
                        </text>
                        <BaseIcon
                            :name="showFinancialDetails ? 'up' : 'down'"
                            size="22"
                            color="#C6A15B"
                        />
                    </view>

                    <!-- 详细金额嵌套面板 -->
                    <view v-if="showFinancialDetails" class="finance-breakdown">
                        <BaseInfoRow
                            label="主服务金额"
                            :value="'¥' + formatAmount(orderServiceAmount)"
                        />
                        <BaseInfoRow
                            v-if="Number(order.addon_amount || 0) > 0"
                            label="附加套餐金额"
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
                </view>

                <!-- 4. 订单基本信息卡片 -->
                <view class="detail-card">
                    <view class="detail-card__header">
                        <view class="detail-card__title-row">
                            <text class="detail-card__title">订单信息</text>
                        </view>
                        <view class="detail-card__copy-btn" @click="copyOrderSn">
                            <BaseIcon name="copy" size="20" color="#C6A15B" />
                            <text class="detail-card__copy-text">复制编号</text>
                        </view>
                    </view>

                    <view class="detail-info-list">
                        <BaseInfoRow label="订单编号" :value="order.order_sn" />
                        <BaseInfoRow label="下单时间" :value="order.create_time || '-'" />
                        <BaseInfoRow label="付款渠道" :value="paymentChannelDesc" />
                        <BaseInfoRow v-if="order.pay_time" label="支付时间" :value="order.pay_time" />
                    </view>
                </view>

                <!-- 5. 线下支付凭证卡片 -->
                <view v-if="showOfflineVoucherCard" class="detail-card">
                    <view class="detail-card__header">
                        <view class="detail-card__title-row">
                            <text class="detail-card__title">线下支付凭证</text>
                        </view>
                        <StatusBadge
                            :tone="getVoucherStatusTone(Number(order.pay_voucher_status ?? -1))"
                            size="sm"
                        >
                            {{ order.pay_voucher_status_desc || '未上传' }}
                        </StatusBadge>
                    </view>

                    <view class="detail-info-list">
                        <BaseInfoRow label="付款渠道" :value="paymentChannelDesc" />
                        <BaseInfoRow
                            v-if="order.pay_voucher_audit_remark"
                            label="审核备注"
                            :value="order.pay_voucher_audit_remark"
                            multiline
                        />
                    </view>

                    <view v-if="order.pay_voucher" class="voucher-preview" @click="previewVoucherImage(order.pay_voucher)">
                        <image class="voucher-preview__img" :src="order.pay_voucher" mode="aspectFill" />
                        <view class="voucher-preview__mask">
                            <BaseIcon name="search" size="28" color="#FFFDF8" />
                            <text class="voucher-preview__hint">轻触查看原图</text>
                        </view>
                    </view>

                    <view v-else class="voucher-empty">
                        <text class="voucher-empty__text">暂无凭证，可从下方点击上传</text>
                    </view>
                </view>

                <!-- 6. 退款信息卡片 -->
                <view v-if="order.refund" class="detail-card">
                    <view class="detail-card__header">
                        <view class="detail-card__title-row">
                            <text class="detail-card__title">退款信息</text>
                        </view>
                        <StatusBadge
                            :tone="getRefundStatusTone(Number(order.refund.refund_status || 0))"
                            size="sm"
                        >
                            {{ order.refund.refund_status_desc }}
                        </StatusBadge>
                    </view>

                    <view class="detail-info-list">
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
                    </view>

                    <view
                        v-if="order.refund.refund_items && order.refund.refund_items.length"
                        class="refund-items-block"
                    >
                        <text class="refund-items-block__title">退款渠道明细</text>
                        <view class="refund-item-list">
                            <view
                                v-for="item in order.refund.refund_items"
                                :key="item.id || item.out_refund_no"
                                class="refund-item-card"
                            >
                                <view class="refund-item-card__head">
                                    <text class="refund-item-card__title">
                                        {{ getPayWayText(Number(item.pay_way || 0)) }}
                                    </text>
                                    <text class="refund-item-card__amount">
                                        ¥{{ formatAmount(item.refund_amount || 0) }}
                                    </text>
                                </view>
                                <view class="refund-item-card__meta">
                                    <text>{{ getRefundItemStatusText(Number(item.refund_status || 0)) }}</text>
                                    <text v-if="item.out_refund_no">单号：{{ item.out_refund_no }}</text>
                                </view>
                                <text v-if="item.refund_msg" class="refund-item-card__desc">
                                    {{ item.refund_msg }}
                                </text>
                            </view>
                        </view>
                    </view>
                </view>

                <!-- 7. 线下收款引导卡片 -->
                <view v-if="showOfflineCollectionCard" class="advisory-card">
                    <view class="advisory-card__icon">
                        <BaseIcon name="wallet" size="36" color="#C6A15B" />
                    </view>
                    <view class="advisory-card__body">
                        <text class="advisory-card__title">线下转账收款服务</text>
                        <text class="advisory-card__desc">当前订单支持对公或线下转账，请联系专属顾问获取收款信息与流水确认。</text>
                    </view>
                    <view class="advisory-card__action" @click="handleContactAdvisor">
                        <text class="advisory-card__btn-text">联系顾问</text>
                    </view>
                </view>

                <!-- 8. 新人问卷待填写引导卡片 -->
                <view v-if="showQuestionnairePromptCard" class="advisory-card advisory-card--questionnaire">
                    <view class="advisory-card__icon">
                        <BaseIcon name="edit" size="36" color="#C6A15B" />
                    </view>
                    <view class="advisory-card__body">
                        <text class="advisory-card__title">新人婚礼问卷待填写</text>
                        <text class="advisory-card__desc">请完善仪式偏好、迎亲细节与音乐流程，以便团队精心备婚。</text>
                    </view>
                    <view class="advisory-card__action advisory-card__action--gold" @click="goQuestionnaireTask">
                        <text class="advisory-card__btn-text">立即填写</text>
                    </view>
                </view>
            </view>

            <!-- 底部操作底栏 (毛玻璃高定设计) -->
            <ActionArea
                v-if="hasPrimaryOrSecondaryAction"
                class="order-detail__action-area"
                sticky
                safeBottom
            >
                <view class="action-bar">
                    <view class="action-bar__buttons">
                        <BaseButton
                            v-if="secondaryVisibleAction"
                            block
                            variant="secondary"
                            size="md"
                            height="84rpx"
                            font-size="26rpx"
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
                            height="84rpx"
                            font-size="26rpx"
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
                        <BaseIcon name="more-circle" size="32" color="#5E564B" />
                        <text class="action-bar__more-text">更多</text>
                    </view>
                </view>
            </ActionArea>

            <!-- 仅存在更多操作时的悬浮按钮 -->
            <view
                v-else-if="moreActionItems.length"
                class="more-floating-action"
                @click="openMoreActions"
            >
                <BaseIcon name="more-circle" size="30" color="#5E564B" />
                <text class="more-floating-action__text">更多操作</text>
            </view>

            <!-- 更多操作抽屉 -->
            <BaseOverlayMask :show="showMoreActionsPopup" @close="showMoreActionsPopup = false" />
            <tn-popup
                v-model="showMoreActionsPopup"
                open-direction="bottom"
                :radius="36"
                safe-area-inset-bottom
                :overlay="false"
                :overlay-closeable="true"
            >
                <view class="more-actions-sheet">
                    <view class="popup-head">
                        <text class="popup-head__title">更多操作</text>
                        <BaseIcon
                            name="close"
                            size="36"
                            color="#8C8273"
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
                                    size="32"
                                    :color="item.tone === 'danger' ? '#B84A39' : '#181614'"
                                />
                            </view>
                            <view class="more-action-item__body">
                                <text class="more-action-item__label">{{ item.label }}</text>
                                <text class="more-action-item__desc">{{ item.description }}</text>
                            </view>
                            <BaseIcon
                                name="right"
                                size="26"
                                :color="item.tone === 'danger' ? '#B84A39' : '#8C8273'"
                            />
                        </view>
                    </view>
                </view>
            </tn-popup>

            <!-- 申请退款抽屉 -->
            <BaseOverlayMask :show="showRefundPopup" @close="showRefundPopup = false" />
            <tn-popup
                v-model="showRefundPopup"
                open-direction="bottom"
                :radius="36"
                safe-area-inset-bottom
                :overlay="false"
                :overlay-closeable="true"
            >
                <view class="refund-popup">
                    <view class="popup-head">
                        <text class="popup-head__title">申请退款</text>
                        <BaseIcon
                            name="close"
                            size="36"
                            color="#8C8273"
                            @click="showRefundPopup = false"
                        />
                    </view>

                    <view class="refund-popup__content">
                        <view class="form-group">
                            <text class="form-group__label">退款金额</text>
                            <view class="refund-card">
                                <text class="refund-card__amount">¥{{ formatAmount(refundApplyAmount) }}</text>
                                <text class="refund-card__tip">系统按当前剩余可退金额提交，审核通过后原路退回</text>
                            </view>
                        </view>

                        <view class="form-group">
                            <text class="form-group__label">退款原因</text>
                            <tn-input
                                v-model="refundForm.reason"
                                type="textarea"
                                placeholder="请输入申请退款的原因或诉求说明"
                                :maxlength="200"
                                border
                                height="200"
                            />
                        </view>
                    </view>

                    <view class="popup-actions">
                        <view class="popup-actions__btn">
                            <BaseButton
                                block
                                variant="secondary"
                                size="lg"
                                height="84rpx"
                                @click="showRefundPopup = false"
                            >
                                取消
                            </BaseButton>
                        </view>
                        <view class="popup-actions__btn">
                            <BaseButton
                                block
                                variant="primary"
                                size="lg"
                                height="84rpx"
                                @click="submitRefund"
                            >
                                {{ refundSubmitting ? '提交中...' : '提交申请' }}
                            </BaseButton>
                        </view>
                    </view>
                </view>
            </tn-popup>

            <!-- 上传线下支付凭证抽屉 -->
            <BaseOverlayMask :show="showVoucherPopup" @close="showVoucherPopup = false" />
            <tn-popup
                v-model="showVoucherPopup"
                open-direction="bottom"
                :radius="36"
                safe-area-inset-bottom
                :overlay="false"
                :overlay-closeable="true"
            >
                <view class="voucher-popup">
                    <view class="popup-head">
                        <text class="popup-head__title">上传支付凭证</text>
                        <BaseIcon
                            name="close"
                            size="36"
                            color="#8C8273"
                            @click="showVoucherPopup = false"
                        />
                    </view>

                    <view class="voucher-popup__content">
                        <text class="voucher-popup__tip">请上传银行转账截图、对公汇款回执或付款凭据</text>

                        <view class="voucher-uploader">
                            <view v-if="voucherForm.image" class="voucher-uploader__preview">
                                <image :src="voucherForm.image" mode="aspectFill" />
                                <view class="voucher-uploader__remove" @click="voucherForm.image = ''">
                                    <BaseIcon name="close" size="28" color="#FFFDF8" />
                                </view>
                            </view>

                            <view v-else class="voucher-uploader__placeholder" @click="chooseVoucherImage">
                                <BaseIcon name="add" size="56" color="#C6A15B" />
                                <text class="voucher-uploader__label">点击选择凭证图片</text>
                                <text class="voucher-uploader__format">支持 JPG、PNG 格式截图</text>
                            </view>
                        </view>
                    </view>

                    <view class="popup-actions">
                        <view class="popup-actions__btn">
                            <BaseButton
                                block
                                variant="secondary"
                                size="lg"
                                height="84rpx"
                                @click="showVoucherPopup = false"
                            >
                                取消
                            </BaseButton>
                        </view>
                        <view class="popup-actions__btn">
                            <BaseButton
                                block
                                variant="primary"
                                size="lg"
                                height="84rpx"
                                @click="submitVoucher"
                            >
                                {{ voucherForm.uploading ? '上传中...' : '提交审核' }}
                            </BaseButton>
                        </view>
                    </view>
                </view>
            </tn-popup>

            <!-- 线上收银组件 -->
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
        </view>

        <!-- 加载状态 -->
        <view v-else-if="detailLoading" class="loading-state-wrapper">
            <LoadingState text="订单详情加载中..." />
        </view>

        <!-- 异常空状态 -->
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
import { remindBeforeOaAction } from '@/utils/oa-reminder'
import { computed, reactive, ref } from 'vue'
import { onHide, onLoad, onShow, onUnload } from '@dcloudio/uni-app'
import PageShell from '@/components/base/PageShell.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import ActionArea from '@/components/base/ActionArea.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseInfoRow from '@/components/base/BaseInfoRow.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import BaseOverlayMask from '@/components/base/BaseOverlayMask.vue'
import { useThemeStore } from '@/stores/theme'
import {
    applyRefund,
    cancelOrder,
    confirmOrder,
    deleteOrder,
    getOrderDetail,
    uploadPayVoucher
} from '@/api/order'
import { getMyReviews, getPendingOrders } from '@/packages/common/api/review'
import { uploadImage } from '@/api/app'
import { confirmModal, showError, showSuccess } from '@/utils/feedback'
import { getCoupleQuestionnaireLists } from '@/packages/common/api/coupleQuestionnaire'
import { normalizeQuestionnaireLists } from '@/packages/common/utils/coupleQuestionnaire'
import { resolvePaymentChannel, shouldUseOfflineCollection } from '@/utils/paymentChannel'
import {
    goHome,
    goLoginWithBack,
    goOrderList,
    normalizePageRecoveryError
} from '@/packages/common/utils/page-recovery'

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
    redirect: '/packages/pages/order_detail/order_detail',
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
    if (description) parts.push(description)
    const quantity = getItemQuantity(item)
    if (quantity > 1) parts.push(`数量 x${quantity}`)
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
    return String(primaryPackageName.value || '').trim() || '定制婚礼服务'
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
        (paymentChannel.value === 2 ? '线下支付' : '线上微信支付')
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
        !!order.value?.receipt_pending || (!!order.value &&
        canUploadOfflineVoucherStage.value &&
        paymentChannel.value === 2 &&
        Number(order.value.pay_voucher_status) === 0)
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
        !order.value.receipt_pending &&
        Number(order.value.order_status) === 1 &&
        Number(order.value.need_pay_amount || 0) > 0 &&
        paymentChannel.value === 1 &&
        !isOfflineCollectionMode.value
)

const canUploadVoucher = computed(
    () =>
        !!order.value &&
        !order.value.receipt_pending &&
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

const statusHeadline = computed(() => {
    const orderStatus = Number(order.value?.order_status ?? -1)
    const serviceName = String(primaryPackageName.value || '婚礼服务').trim()

    const headlines: Record<number, string> = {
        0: `${serviceName} · 待团队确认`,
        1: `${serviceName} · 待支付定金`,
        2: `${serviceName} · 待服务排期`,
        3: `${serviceName} · 婚礼履约进行中`,
        4: `${serviceName} · 订单已圆满完成`,
        5: `${serviceName} · 订单已评价`,
        6: `${serviceName} · 订单已取消`,
        7: `${serviceName} · 服务档期已暂停`,
        10: `${serviceName} · 退款申请处理中`,
        8: `${serviceName} · 订单已退款`
    }

    return headlines[orderStatus] || `${serviceName} · 状态已更新`
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
        if (order.value.deposit_paid && order.value.balance_paid) return '已结清'
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
                ? '已确认'
                : showConfirmCountdown.value
                ? `${confirmCountdownText.value}${
                      confirmTimeoutActionText.value
                          ? ` (超时${confirmTimeoutActionText.value})`
                          : ''
                  }`
                : '待确认'
    },
    {
        label: '2. 支付进度',
        value:
            showPayCountdown.value && payTimeoutActionText.value
                ? `${paymentProgressText.value}，剩余 ${payCountdownText.value}`
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
                    ? '已结清'
                    : '婚礼服务后结清'
                : '无尾款'
    }
])

/* 婚礼全周期里程碑步进数据 */
interface MilestoneStepItem {
    key: string
    title: string
    subtext: string
    state: 'done' | 'active' | 'pending' | 'cancelled' | 'refunded'
}

const milestoneSteps = computed<MilestoneStepItem[]>(() => {
    if (!order.value) return []
    const status = Number(order.value.order_status ?? -1)

    if (status === 6) {
        return [
            { key: 'submit', title: '提交', subtext: '已提交', state: 'done' },
            { key: 'confirm', title: '确认', subtext: '已取消', state: 'cancelled' },
            { key: 'pay', title: '定金', subtext: '未开启', state: 'pending' },
            { key: 'service', title: '履约', subtext: '未开启', state: 'pending' },
            { key: 'done', title: '礼成', subtext: '未开启', state: 'pending' }
        ]
    }

    if (status === 8 || status === 10) {
        return [
            { key: 'submit', title: '提交', subtext: '已提交', state: 'done' },
            { key: 'confirm', title: '确认', subtext: '已确认', state: 'done' },
            { key: 'pay', title: '支付', subtext: status === 10 ? '退款中' : '已退款', state: 'refunded' },
            { key: 'service', title: '履约', subtext: '已终止', state: 'pending' },
            { key: 'done', title: '礼成', subtext: '已终止', state: 'pending' }
        ]
    }

    let s2State: MilestoneStepItem['state'] = 'pending'
    let s2Sub = '待确认'
    if (status === 0) {
        s2State = 'active'
        s2Sub = '确认中'
    } else if (status >= 1) {
        s2State = 'done'
        s2Sub = '已确认'
    }

    let s3State: MilestoneStepItem['state'] = 'pending'
    let s3Sub = '待支付'
    if (status === 1) {
        s3State = 'active'
        s3Sub = order.value.need_pay === 'balance' ? '待付尾款' : '待付定金'
    } else if (status >= 2) {
        s3State = 'done'
        s3Sub = Number(order.value.balance_amount || 0) > 0 && !order.value.balance_paid ? '定金已付' : '已结清'
    }

    let s4State: MilestoneStepItem['state'] = 'pending'
    let s4Sub = '待履约'
    if (status === 2) {
        s4State = 'active'
        s4Sub = '待服务'
    } else if (status === 3) {
        s4State = 'active'
        s4Sub = '服务中'
    } else if (status >= 4) {
        s4State = 'done'
        s4Sub = '服务完成'
    }

    let s5State: MilestoneStepItem['state'] = 'pending'
    let s5Sub = '待结案'
    if (status === 4) {
        s5State = 'active'
        s5Sub = '待评价'
    } else if (status === 5) {
        s5State = 'done'
        s5Sub = '已评价'
    }

    return [
        { key: 'submit', title: '提交', subtext: '已提交', state: 'done' },
        { key: 'confirm', title: '确认', subtext: s2Sub, state: s2State },
        { key: 'pay', title: '定金', subtext: s3Sub, state: s3State },
        { key: 'service', title: '履约', subtext: s4Sub, state: s4State },
        { key: 'done', title: '礼成', subtext: s5Sub, state: s5State }
    ]
})

const primaryVisibleAction = computed(() => {
    if (!order.value) return null

    const baseStyle = {
        background: 'linear-gradient(135deg, #181614 0%, #2A241B 100%)',
        color: '#FFFDF8',
        boxShadow: '0 8rpx 24rpx rgba(24, 22, 20, 0.25)'
    }

    if (canPayOnline.value) {
        return {
            key: 'pay',
            label: order.value?.need_pay_label ? `${order.value.need_pay_label} (¥${formatAmount(needPayAmount.value)})` : `立即支付 ¥${formatAmount(needPayAmount.value)}`,
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
            label: '上传支付凭证',
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
            label: '确认服务完成',
            style: baseStyle,
            onClick: handleConfirm
        }
    }

    if (canGoReviewAction.value) {
        return {
            key: 'review',
            label: '发表婚礼评价',
            style: baseStyle,
            onClick: goReviewFromOrder
        }
    }

    if (canViewReviewAction.value) {
        return {
            key: 'reviewDetail',
            label: '查看评价详情',
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
                borderColor: 'var(--wm-color-border-strong, #D9BE82)',
                color: 'var(--wm-text-primary, #181614)',
                background: 'rgba(255, 253, 248, 0.95)'
            },
            onClick: handleContactAdvisor
        }
    }

    if (canGoReviewAction.value && canViewReviewAction.value) {
        return {
            key: 'reviewDetail',
            label: '查看评价',
            style: {
                borderColor: 'var(--wm-color-border-strong, #D9BE82)',
                color: 'var(--wm-text-primary, #181614)',
                background: 'rgba(255, 253, 248, 0.95)'
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
            description: '取消并结束当前订单，释放档期锁定',
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
            label: '上传支付凭证',
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
            description: '提交退款申请原因，等待平台快速处理',
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
            description: '从订单列表移除该记录，删除后不可恢复',
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

const previewVoucherImage = (url: string) => {
    if (!url) return
    uni.previewImage({
        urls: [url],
        current: url
    })
}

const handleDetailRecoveryAction = () => {
    if (detailError.value?.kind === 'auth') {
        goLoginWithBack(
            orderId.value > 0 ? `/packages/pages/order_detail/order_detail?id=${orderId.value}` : '/pages/order/order'
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
    if (id <= 0) return
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
        url: `/packages/pages/payment_result/payment_result?id=${orderId.value}&from=${payState.from}${
            paymentSn ? `&payment_sn=${paymentSn}` : ''
        }`
    })
}

const handlePayFail = async (payload?: { reason?: string; message?: string }) => {
    if (payload?.reason === 'timeout' || payload?.reason === 'offline_collection') {
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

const refundSubmitting = ref(false)
const submitRefund = async () => {
    if (refundSubmitting.value) return
    if (!canApplyRefund.value || refundApplyAmount.value <= 0) {
        showError('当前订单暂不支持申请退款')
        return
    }

    if (!refundForm.reason.trim()) {
        showError('请输入退款原因')
        return
    }

    try {
        refundSubmitting.value = true
        if (!await remindBeforeOaAction()) return
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
    } finally {
        refundSubmitting.value = false
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
    min-height: 100vh;
    background:
        radial-gradient(ellipse at 50% 0%, rgba(217, 190, 130, 0.1) 0%, rgba(248, 246, 240, 0) 65%),
        var(--wm-color-bg-page, #F8F6F0);
    box-sizing: border-box;
    padding-bottom: calc(44rpx + env(safe-area-inset-bottom));

    &--has-action {
        padding-bottom: calc(130rpx + env(safe-area-inset-bottom));
    }

    &--floating-more {
        padding-bottom: calc(120rpx + env(safe-area-inset-bottom));
    }

    &__body {
        padding: 24rpx var(--wm-space-page-x, 28rpx) 40rpx;
        display: flex;
        flex-direction: column;
        gap: 24rpx;
        box-sizing: border-box;
    }
}

/* ============================================================
   1. 沉浸式黑金奢华状态 Hero 卡片
============================================================ */
.status-hero {
    position: relative;
    padding: 34rpx 32rpx;
    border-radius: 36rpx;
    background: linear-gradient(145deg, #181614 0%, #29241C 100%);
    border: 1rpx solid rgba(217, 190, 130, 0.45);
    box-shadow: 0 18rpx 44rpx rgba(24, 22, 20, 0.22);
    display: flex;
    flex-direction: column;
    gap: 24rpx;
    box-sizing: border-box;

    &__top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16rpx;
    }

    &__main {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 10rpx;
    }

    &__badge-row {
        display: flex;
        align-items: center;
    }

    &__title {
        font-size: 38rpx;
        font-weight: 800;
        line-height: 1.35;
        color: #FFFDF8;
        letter-spacing: 0.5rpx;
    }

    &__sn-box {
        display: inline-flex;
        align-items: center;
        gap: 8rpx;
        padding: 6rpx 16rpx;
        border-radius: 999rpx;
        background: rgba(255, 253, 248, 0.12);
        border: 1rpx solid rgba(217, 190, 130, 0.35);
        flex-shrink: 0;

        &:active {
            opacity: 0.75;
        }
    }

    &__sn-text {
        font-size: 20rpx;
        font-family: monospace;
        color: #D9BE82;
        max-width: 240rpx;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* 倒计时与提醒条 */
    &__alert-strip {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 12rpx;
        padding: 14rpx 20rpx;
        border-radius: 20rpx;
        background: rgba(255, 253, 248, 0.08);
        border: 1rpx solid rgba(217, 190, 130, 0.25);
    }

    &__alert-item {
        display: inline-flex;
        align-items: center;
        gap: 8rpx;

        &--gold {
            background: rgba(217, 190, 130, 0.2);
            padding: 4rpx 14rpx;
            border-radius: 999rpx;
        }

        &--highlight {
            background: rgba(255, 253, 248, 0.18);
            padding: 4rpx 16rpx;
            border-radius: 999rpx;
        }
    }

    &__alert-label {
        font-size: 21rpx;
        color: #C6A15B;
    }

    &__alert-value {
        font-size: 23rpx;
        font-weight: 700;
        color: #FFFDF8;
    }

    &__alert-desc {
        font-size: 20rpx;
        color: rgba(255, 253, 248, 0.7);
    }
}

/* 5 阶段里程碑步进器 */
.milestone-stepper {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding-top: 14rpx;
    border-top: 1rpx solid rgba(217, 190, 130, 0.2);
    box-sizing: border-box;

    &__item {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        flex: 1;
        min-width: 0;
        position: relative;
    }

    &__node-wrap {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 48rpx;
        margin-bottom: 8rpx;
    }

    &__node {
        width: 36rpx;
        height: 36rpx;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2;
        background: #2A241B;
        border: 2rpx solid rgba(217, 190, 130, 0.35);
        transition: all 0.25s ease;
    }

    &__pulse-dot {
        width: 16rpx;
        height: 16rpx;
        border-radius: 50%;
        background: #C6A15B;
        box-shadow: 0 0 12rpx #D9BE82;
    }

    &__pending-dot {
        width: 10rpx;
        height: 10rpx;
        border-radius: 50%;
        background: rgba(217, 190, 130, 0.4);
    }

    &__line {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 100%;
        height: 2rpx;
        background: rgba(217, 190, 130, 0.22);
        z-index: 1;
        transform: translateY(-50%);

        &--done {
            background: #C6A15B;
        }
    }

    &__title {
        font-size: 21rpx;
        font-weight: 700;
        color: rgba(255, 253, 248, 0.6);
        line-height: 1.3;
    }

    &__sub {
        font-size: 18rpx;
        color: rgba(255, 253, 248, 0.45);
        margin-top: 2rpx;
    }

    /* 状态态 */
    &--done {
        .milestone-stepper__node {
            background: #C6A15B;
            border-color: #D9BE82;
        }

        .milestone-stepper__title {
            color: #FFFDF8;
        }

        .milestone-stepper__sub {
            color: #D9BE82;
        }
    }

    &--active {
        .milestone-stepper__node {
            background: #181614;
            border: 3rpx solid #D9BE82;
            box-shadow: 0 0 16rpx rgba(217, 190, 130, 0.5);
            transform: scale(1.15);
        }

        .milestone-stepper__title {
            color: #D9BE82;
            font-weight: 800;
        }

        .milestone-stepper__sub {
            color: #FFFDF8;
        }
    }

    &--cancelled,
    &--refunded {
        .milestone-stepper__node {
            background: #B84A39;
            border-color: #B84A39;
        }

        .milestone-stepper__title {
            color: #E57373;
        }
    }
}

/* ============================================================
   通用卡片规范
============================================================ */
.detail-card {
    padding: 30rpx 28rpx;
    border-radius: 32rpx;
    background: linear-gradient(180deg, #FFFFFF 0%, #FAF8F5 100%);
    border: 1rpx solid rgba(217, 190, 130, 0.4);
    box-shadow: 0 12rpx 32rpx rgba(24, 22, 20, 0.05);
    display: flex;
    flex-direction: column;
    gap: 20rpx;
    box-sizing: border-box;

    &__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
    }

    &__title-row {
        display: flex;
        align-items: baseline;
        gap: 12rpx;
    }

    &__title {
        font-size: 30rpx;
        font-weight: 800;
        color: var(--wm-color-primary, #181614);
    }

    &__subtitle {
        font-size: 21rpx;
        color: var(--wm-color-text-tertiary, #8C8273);
    }

    &__contact-btn,
    &__copy-btn {
        display: inline-flex;
        align-items: center;
        gap: 6rpx;
        padding: 8rpx 16rpx;
        border-radius: 999rpx;
        background: rgba(248, 242, 228, 0.7);
        border: 1rpx solid rgba(217, 190, 130, 0.4);

        &:active {
            opacity: 0.7;
        }
    }

    &__contact-text,
    &__copy-text {
        font-size: 21rpx;
        font-weight: 600;
        color: var(--wm-color-gold, #C6A15B);
    }
}

/* ============================================================
   2. 服务信息与主服务方案卡
============================================================ */
.service-hero {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
    padding: 24rpx;
    border-radius: 26rpx;
    background: linear-gradient(180deg, rgba(250, 246, 238, 0.95) 0%, #FFFFFF 100%);
    border: 1rpx solid rgba(217, 190, 130, 0.35);

    &__head {
        display: flex;
        align-items: center;
        gap: 18rpx;
    }

    &__avatar-ring {
        width: 88rpx;
        height: 88rpx;
        border-radius: 50%;
        padding: 3rpx;
        box-sizing: border-box;
        background: linear-gradient(135deg, #D9BE82 0%, #FAF6EE 100%);
        box-shadow: 0 6rpx 14rpx rgba(24, 22, 20, 0.08);
        flex-shrink: 0;
    }

    &__avatar {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: #FFFFFF;
        display: block;
    }

    &__info {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 6rpx;
    }

    &__title-row {
        display: flex;
        align-items: center;
        gap: 10rpx;
    }

    &__staff-name {
        font-size: 28rpx;
        font-weight: 800;
        color: var(--wm-color-primary, #181614);
    }

    &__tag {
        font-size: 19rpx;
        font-weight: 600;
        padding: 2rpx 12rpx;
        border-radius: 999rpx;
        background: rgba(198, 161, 91, 0.18);
        color: var(--wm-color-clay, #9A6B35);
    }

    &__package-title {
        font-size: 24rpx;
        color: var(--wm-color-text-secondary, #5E564B);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__price-box {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 4rpx;
        flex-shrink: 0;
    }

    &__price-label {
        font-size: 20rpx;
        color: var(--wm-color-text-tertiary, #8C8273);
    }

    &__price {
        font-size: 28rpx;
        font-weight: 800;
        color: var(--wm-color-primary, #181614);
    }

    &__meta-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12rpx;
        padding-top: 16rpx;
        border-top: 1rpx solid rgba(231, 224, 211, 0.8);
    }

    &__meta-cell {
        display: flex;
        align-items: center;
        gap: 12rpx;
        padding: 12rpx 14rpx;
        border-radius: 18rpx;
        background: rgba(255, 253, 248, 0.8);
        border: 1rpx solid rgba(217, 190, 130, 0.25);
    }

    &__meta-icon {
        width: 44rpx;
        height: 44rpx;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(217, 190, 130, 0.18);
        flex-shrink: 0;
    }

    &__meta-content {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__meta-label {
        font-size: 19rpx;
        color: var(--wm-color-text-tertiary, #8C8273);
    }

    &__meta-value {
        font-size: 23rpx;
        font-weight: 700;
        color: var(--wm-color-primary, #181614);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
}

/* 附加套餐与协作服务 */
.addon-block {
    display: flex;
    flex-direction: column;
    gap: 14rpx;

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    &__title {
        font-size: 26rpx;
        font-weight: 700;
        color: var(--wm-color-primary, #181614);
    }

    &__count {
        font-size: 21rpx;
        color: var(--wm-color-text-tertiary, #8C8273);
    }
}

.addon-list {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
}

.addon-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    padding: 20rpx 22rpx;
    border-radius: 22rpx;
    background: rgba(255, 255, 255, 0.95);
    border: 1rpx solid rgba(217, 190, 130, 0.35);

    &__left {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 6rpx;
    }

    &__title-row {
        display: flex;
        align-items: center;
        gap: 10rpx;
        flex-wrap: wrap;
    }

    &__title {
        font-size: 25rpx;
        font-weight: 700;
        color: var(--wm-color-primary, #181614);
    }

    &__badge {
        font-size: 19rpx;
        font-weight: 600;
        padding: 2rpx 10rpx;
        border-radius: 999rpx;
        background: rgba(198, 161, 91, 0.16);
        color: var(--wm-color-clay, #9A6B35);

        &--related {
            background: rgba(96, 115, 97, 0.15);
            color: var(--wm-color-sage, #607361);
        }
    }

    &__meta,
    &__desc {
        font-size: 21rpx;
        color: var(--wm-color-text-secondary, #5E564B);
        line-height: 1.4;
    }

    &__price {
        font-size: 27rpx;
        font-weight: 800;
        color: var(--wm-color-primary, #181614);
        flex-shrink: 0;
    }

    &--related {
        background: linear-gradient(180deg, #FAF8F5 0%, #FFFFFF 100%);
    }
}

.addon-empty {
    padding: 22rpx;
    border-radius: 20rpx;
    border: 1rpx dashed rgba(217, 190, 130, 0.4);
    background: rgba(255, 253, 248, 0.6);
    text-align: center;

    &__text {
        font-size: 22rpx;
        color: var(--wm-color-text-tertiary, #8C8273);
    }
}

/* ============================================================
   3. 金额与履约进度卡片
============================================================ */
.finance-trio {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12rpx;

    &__cell {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6rpx;
        padding: 20rpx 10rpx;
        border-radius: 22rpx;
        background: rgba(250, 246, 238, 0.9);
        border: 1rpx solid rgba(217, 190, 130, 0.35);

        &--paid {
            background: rgba(96, 115, 97, 0.08);
            border-color: rgba(96, 115, 97, 0.25);

            .finance-trio__value {
                color: var(--wm-color-sage, #607361);
            }
        }

        &--pending {
            background: rgba(212, 141, 59, 0.08);
            border-color: rgba(212, 141, 59, 0.25);

            .finance-trio__value {
                color: var(--wm-color-warning, #D48D3B);
            }
        }
    }

    &__label {
        font-size: 20rpx;
        font-weight: 600;
        color: var(--wm-color-text-tertiary, #8C8273);
    }

    &__price-row {
        display: inline-flex;
        align-items: baseline;
    }

    &__symbol {
        font-size: 20rpx;
        font-weight: 700;
        color: var(--wm-color-primary, #181614);
    }

    &__value {
        font-size: 30rpx;
        font-weight: 900;
        color: var(--wm-color-primary, #181614);
    }
}

.progress-timeline {
    display: flex;
    flex-direction: column;
    gap: 0;
    margin-top: 10rpx;
    border-top: 1rpx solid rgba(231, 224, 211, 0.8);

    &__row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20rpx;
        padding: 16rpx 0;
        border-bottom: 1rpx solid rgba(231, 224, 211, 0.5);

        &:last-child {
            border-bottom: none;
        }
    }

    &__label {
        font-size: 23rpx;
        font-weight: 700;
        color: var(--wm-color-text-secondary, #5E564B);
        flex-shrink: 0;
    }

    &__value {
        font-size: 23rpx;
        font-weight: 700;
        color: var(--wm-color-primary, #181614);
        text-align: right;
        flex: 1;
    }
}

.detail-collapse-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    padding: 14rpx 0 4rpx;

    &__text {
        font-size: 22rpx;
        font-weight: 600;
        color: var(--wm-color-gold, #C6A15B);
    }
}

.finance-breakdown {
    display: flex;
    flex-direction: column;
    gap: 0;
    margin-top: 8rpx;
    padding: 6rpx 20rpx;
    border-radius: 22rpx;
    background: rgba(250, 246, 238, 0.85);
    border: 1rpx solid rgba(217, 190, 130, 0.35);

    :deep(.base-info-row + .base-info-row) {
        border-top: 1rpx solid rgba(217, 190, 130, 0.25);
    }
}

/* ============================================================
   4. 信息列表
============================================================ */
.detail-info-list {
    display: flex;
    flex-direction: column;
    gap: 0;

    :deep(.base-info-row + .base-info-row) {
        border-top: 1rpx solid rgba(231, 224, 211, 0.7);
    }
}

/* ============================================================
   5. 线下支付凭证与退款组件
============================================================ */
.voucher-preview {
    position: relative;
    width: 100%;
    height: 380rpx;
    border-radius: 24rpx;
    overflow: hidden;
    border: 1rpx solid rgba(217, 190, 130, 0.5);

    &__img {
        width: 100%;
        height: 100%;
        display: block;
    }

    &__mask {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 12rpx 20rpx;
        background: linear-gradient(180deg, transparent, rgba(24, 22, 20, 0.75));
        display: flex;
        align-items: center;
        gap: 10rpx;
    }

    &__hint {
        font-size: 21rpx;
        color: #FFFDF8;
    }
}

.voucher-empty {
    padding: 30rpx;
    text-align: center;
    border-radius: 22rpx;
    background: rgba(250, 246, 238, 0.7);
    border: 1rpx dashed rgba(217, 190, 130, 0.4);

    &__text {
        font-size: 23rpx;
        color: var(--wm-color-text-tertiary, #8C8273);
    }
}

.refund-items-block {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
    margin-top: 10rpx;
    padding-top: 16rpx;
    border-top: 1rpx solid rgba(231, 224, 211, 0.7);

    &__title {
        font-size: 24rpx;
        font-weight: 700;
        color: var(--wm-color-primary, #181614);
    }
}

.refund-item-list {
    display: flex;
    flex-direction: column;
    gap: 10rpx;
}

.refund-item-card {
    padding: 18rpx 20rpx;
    border-radius: 20rpx;
    background: rgba(250, 246, 238, 0.85);
    border: 1rpx solid rgba(217, 190, 130, 0.3);
    display: flex;
    flex-direction: column;
    gap: 8rpx;

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    &__title {
        font-size: 24rpx;
        font-weight: 700;
        color: var(--wm-color-primary, #181614);
    }

    &__amount {
        font-size: 24rpx;
        font-weight: 800;
        color: var(--wm-color-danger, #B84A39);
    }

    &__meta {
        font-size: 21rpx;
        color: var(--wm-color-text-secondary, #5E564B);
        display: flex;
        gap: 16rpx;
    }

    &__desc {
        font-size: 20rpx;
        color: var(--wm-color-text-tertiary, #8C8273);
    }
}

/* 提示与引导卡片 */
.advisory-card {
    display: flex;
    align-items: center;
    gap: 18rpx;
    padding: 24rpx 26rpx;
    border-radius: 26rpx;
    background: linear-gradient(135deg, rgba(255, 253, 248, 0.98) 0%, rgba(248, 242, 228, 0.9) 100%);
    border: 1rpx solid rgba(217, 190, 130, 0.45);
    box-shadow: 0 10rpx 24rpx rgba(24, 22, 20, 0.04);

    &__icon {
        width: 68rpx;
        height: 68rpx;
        border-radius: 50%;
        background: rgba(217, 190, 130, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    &__body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__title {
        font-size: 26rpx;
        font-weight: 800;
        color: var(--wm-color-primary, #181614);
    }

    &__desc {
        font-size: 21rpx;
        color: var(--wm-color-text-secondary, #5E564B);
        line-height: 1.4;
    }

    &__action {
        padding: 10rpx 24rpx;
        border-radius: 999rpx;
        background: var(--wm-color-primary, #181614);
        flex-shrink: 0;

        &:active {
            opacity: 0.8;
        }

        &--gold {
            background: linear-gradient(135deg, #D9BE82 0%, #C6A15B 100%);
        }
    }

    &__btn-text {
        font-size: 22rpx;
        font-weight: 700;
        color: #FFFDF8;
    }
}

/* ============================================================
   6. 底部操作栏与浮动按钮
============================================================ */
.action-bar {
    display: flex;
    align-items: center;
    gap: 16rpx;
    width: 100%;

    &__buttons {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 16rpx;
    }

    &__more {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6rpx;
        height: 84rpx;
        padding: 0 24rpx;
        border-radius: 999rpx;
        background: rgba(255, 253, 248, 0.95);
        border: 1rpx solid rgba(217, 190, 130, 0.45);
        box-shadow: 0 6rpx 16rpx rgba(24, 22, 20, 0.06);
        flex-shrink: 0;

        &:active {
            opacity: 0.7;
            transform: scale(0.97);
        }
    }

    &__more-text {
        font-size: 23rpx;
        font-weight: 600;
        color: var(--wm-color-text-secondary, #5E564B);
    }
}

.more-floating-action {
    position: fixed;
    right: 32rpx;
    bottom: calc(40rpx + env(safe-area-inset-bottom));
    z-index: 90;
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    height: 80rpx;
    padding: 0 28rpx;
    border-radius: 999rpx;
    background: rgba(255, 253, 248, 0.98);
    border: 1rpx solid rgba(217, 190, 130, 0.5);
    box-shadow: 0 12rpx 28rpx rgba(24, 22, 20, 0.12);

    &:active {
        transform: scale(0.96);
    }

    &__text {
        font-size: 25rpx;
        font-weight: 700;
        color: var(--wm-color-primary, #181614);
    }
}

/* ============================================================
   7. 弹窗与抽屉设计
============================================================ */
.more-actions-sheet,
.refund-popup,
.voucher-popup {
    padding: 34rpx 32rpx calc(38rpx + env(safe-area-inset-bottom));
    background: #FFFDF8;
    border-top-left-radius: 36rpx;
    border-top-right-radius: 36rpx;
    box-sizing: border-box;
}

.popup-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 24rpx;
    border-bottom: 1rpx solid rgba(231, 224, 211, 0.7);

    &__title {
        font-size: 32rpx;
        font-weight: 800;
        color: var(--wm-color-primary, #181614);
    }
}

.more-actions-sheet__list {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
    margin-top: 24rpx;
}

.more-action-item {
    display: flex;
    align-items: center;
    gap: 20rpx;
    padding: 22rpx 24rpx;
    border-radius: 26rpx;
    background: rgba(250, 246, 238, 0.8);
    border: 1rpx solid rgba(217, 190, 130, 0.35);

    &:active {
        background: rgba(248, 242, 228, 0.95);
    }

    &__icon {
        width: 60rpx;
        height: 60rpx;
        border-radius: 50%;
        background: #FFFFFF;
        border: 1rpx solid rgba(217, 190, 130, 0.35);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    &__body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__label {
        font-size: 28rpx;
        font-weight: 800;
        color: var(--wm-color-primary, #181614);
    }

    &__desc {
        font-size: 21rpx;
        color: var(--wm-color-text-tertiary, #8C8273);
    }

    &--danger {
        border-color: rgba(184, 74, 57, 0.25);
        background: rgba(184, 74, 57, 0.05);

        .more-action-item__label {
            color: var(--wm-color-danger, #B84A39);
        }
    }
}

/* 退款表单 */
.refund-popup__content,
.voucher-popup__content {
    display: flex;
    flex-direction: column;
    gap: 24rpx;
    margin-top: 24rpx;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 12rpx;

    &__label {
        font-size: 26rpx;
        font-weight: 700;
        color: var(--wm-color-primary, #181614);
    }
}

.refund-card {
    padding: 24rpx;
    border-radius: 22rpx;
    background: rgba(250, 246, 238, 0.85);
    border: 1rpx solid rgba(217, 190, 130, 0.4);
    display: flex;
    flex-direction: column;
    gap: 6rpx;

    &__amount {
        font-size: 38rpx;
        font-weight: 900;
        color: var(--wm-color-danger, #B84A39);
    }

    &__tip {
        font-size: 21rpx;
        color: var(--wm-color-text-tertiary, #8C8273);
    }
}

/* 凭证上传 */
.voucher-popup__tip {
    font-size: 23rpx;
    color: var(--wm-color-text-secondary, #5E564B);
    line-height: 1.4;
}

.voucher-uploader {
    width: 100%;
    height: 360rpx;
    border-radius: 26rpx;
    overflow: hidden;

    &__preview {
        position: relative;
        width: 100%;
        height: 100%;

        image {
            width: 100%;
            height: 100%;
            display: block;
        }
    }

    &__remove {
        position: absolute;
        top: 16rpx;
        right: 16rpx;
        width: 52rpx;
        height: 52rpx;
        border-radius: 50%;
        background: rgba(24, 22, 20, 0.65);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__placeholder {
        width: 100%;
        height: 100%;
        border-radius: 26rpx;
        border: 2rpx dashed rgba(217, 190, 130, 0.6);
        background: rgba(250, 246, 238, 0.7);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8rpx;
    }

    &__label {
        font-size: 26rpx;
        font-weight: 700;
        color: var(--wm-color-text-secondary, #5E564B);
    }

    &__format {
        font-size: 20rpx;
        color: var(--wm-color-text-tertiary, #8C8273);
    }
}

.popup-actions {
    display: flex;
    gap: 18rpx;
    margin-top: 32rpx;

    &__btn {
        flex: 1;
    }
}

/* ============================================================
   8. 加载与异常状态
============================================================ */
.loading-state-wrapper,
.detail-state-shell {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: var(--wm-color-bg-page, #F8F6F0);
}

.detail-state-shell {
    padding-bottom: calc(160rpx + env(safe-area-inset-bottom));
    box-sizing: border-box;

    &__actions {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 16rpx;
        margin-top: 24rpx;
    }

    &__link {
        font-size: 25rpx;
        font-weight: 700;
        color: var(--wm-color-gold, #C6A15B);
        padding: 8rpx 16rpx;
    }

    &__divider {
        width: 1rpx;
        height: 24rpx;
        background: rgba(217, 190, 130, 0.5);
    }
}
</style>
