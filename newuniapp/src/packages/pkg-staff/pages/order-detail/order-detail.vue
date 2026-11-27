<template>
    <page-shell
        title="服务订单详情"
        bg="page"
        :show-back="true"
        :pull-refresh="true"
        @refresh="onRefresh"
        :bottom-bar="guardAllowed && phase === 'ready' && (Boolean(fulfillAction) || canCancel)"
    >
        <staff-guard
            v-if="!guardAllowed"
            :phase="guardPhase"
            :reason="guardReason"
            :error="guardError"
            @retry="onGuardRetry"
            @action="onGuardAction"
        />

        <view v-else class="page-wrap detail">
            <view v-if="phase === 'loading' || phase === 'idle'">
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
                @retry="load"
            />

            <template v-else>
                <view class="hero">
                    <view class="hero__top">
                        <text class="hero__eyebrow">SERVICE ORDER</text>
                        <status-tag :label="statusLabel" :tone="statusTone" size="sm" />
                    </view>
                    <text class="hero__sn t-num">{{ order.order_sn || '服务订单' }}</text>
                    <view class="hero__facts">
                        <view v-for="fact in heroFacts" :key="fact.key" class="hero-fact">
                            <text class="hero-fact__label">{{ fact.label }}</text>
                            <text class="hero-fact__value t-num">{{ fact.value }}</text>
                            <view class="hero-fact__meta">
                                <text
                                    v-if="fact.stateText"
                                    class="hero-fact__state"
                                    :class="`hero-fact__state--${fact.stateTone || 'neutral'}`"
                                    >{{ fact.stateText }}</text
                                >
                                <text v-if="fact.note" class="hero-fact__note ellipsis">{{
                                    fact.note
                                }}</text>
                            </view>
                        </view>
                    </view>
                </view>

                <!-- 已取消 / 协作服务已取消 -->
                <view v-if="showCancelBanner" class="notice notice--neutral">
                    <view class="notice__head">
                        <text class="notice__title">{{
                            collaboratorOnlyCancelled ? '您的协作服务已取消' : '订单已取消'
                        }}</text>
                    </view>
                    <text v-if="cancelReasonText" class="notice__text"
                        >取消原因：{{ cancelReasonText }}</text
                    >
                    <text v-if="cancelTimeText" class="notice__text"
                        >取消时间：{{ cancelTimeText }}</text
                    >
                    <text v-if="collaboratorOnlyCancelled" class="notice__text"
                        >主服务不受影响，可在用户端重新挑选协作人员。</text
                    >
                    <text v-else-if="!cancelReasonText && !cancelTimeText" class="notice__text"
                        >本单已关闭</text
                    >
                </view>

                <!-- 线下付款凭证待审 -->
                <view v-if="order.receipt_pending" class="notice notice--warning">
                    <text class="notice__title">线下付款凭证待审核</text>
                    <text class="notice__text">审核完成前无法取消，如需处理请联系管理员</text>
                </view>

                <!-- 非整单归属：区分主服务 / 协作服务 -->
                <view v-if="order.is_whole_order === false" class="notice notice--info">
                    <text class="notice__title">{{ staffRoleNotice.title }}</text>
                    <text class="notice__text">{{ staffRoleNotice.text }}</text>
                </view>

                <view v-if="timeoutText" class="timeout">
                    <tn-icon name="clock" :size="26" class="timeout__icon" />
                    <text class="timeout__text">{{ timeoutText }}</text>
                </view>

                <section-header title="服务明细" :subtitle="`${serviceItems.length} 个服务项`" />
                <base-card compact>
                    <view v-if="serviceItems.length" class="services">
                        <view
                            v-for="(item, index) in serviceItems"
                            :key="item.id"
                            class="service"
                            :class="{ 'service--divided': index > 0 }"
                        >
                            <view class="service__head">
                                <text class="service__title ellipsis-2">{{ item.title }}</text>
                                <status-tag
                                    v-if="item.statusText"
                                    :label="item.statusText"
                                    :tone="item.statusTone"
                                    size="sm"
                                />
                            </view>
                            <text v-if="item.desc" class="service__desc ellipsis-2">{{
                                item.desc
                            }}</text>
                            <view class="service__meta">
                                <text class="t-num">{{ item.dateText }}</text>
                                <text class="t-num">{{ item.quantityText }}</text>
                            </view>
                            <view class="service__amount-row">
                                <text class="service__amount-label">服务小计</text>
                                <text class="service__amount t-num">{{ item.amountText }}</text>
                            </view>
                            <view v-if="item.addons.length" class="addons">
                                <text class="addons__title">附加服务</text>
                                <view
                                    v-for="addon in item.addons"
                                    :key="addon.id"
                                    class="addons__row"
                                >
                                    <text class="addons__name ellipsis">{{ addon.name }}</text>
                                    <text class="addons__quantity t-num">× {{ addon.quantity }}</text>
                                    <text class="addons__amount t-num">{{ addon.amountText }}</text>
                                </view>
                            </view>
                        </view>
                    </view>
                    <base-empty
                        v-else
                        status="empty"
                        empty-text="暂无可展示的服务项"
                        desc="当前订单没有指派给你的服务内容"
                    />
                </base-card>

                <section-header title="费用信息" :subtitle="order.current_pay_stage_desc || ''" />
                <base-card compact>
                    <view v-if="feeTags.length" class="fee-tags">
                        <text v-for="tag in feeTags" :key="tag.key" class="fee-tag">{{
                            tag.label
                        }}</text>
                    </view>

                    <view
                        v-for="(row, index) in amountRows"
                        :key="row.key"
                        class="row"
                        :class="{ 'row--divided': index > 0 }"
                    >
                        <text class="row__label">{{ row.label }}</text>
                        <text
                            class="row__value row__value--money t-num"
                            :class="{ 'row__value--strong': row.strong }"
                            >{{ money(row.value) }}</text
                        >
                    </view>

                    <view class="payment-state">
                        <text class="payment-state__label">支付状态</text>
                        <text class="payment-state__value">{{
                            order.pay_status_desc || '未支付'
                        }}</text>
                    </view>

                    <!-- 分期收款进度：定金 + 尾款两段 -->
                    <view v-if="installmentStages.length" class="installment">
                        <text class="installment__caption">分期收款进度</text>
                        <view
                            v-for="stage in installmentStages"
                            :key="stage.key"
                            class="installment__stage"
                        >
                            <view class="installment__stage-head">
                                <text class="installment__stage-title">{{ stage.title }}</text>
                                <text
                                    class="installment__state"
                                    :class="
                                        stage.unpaid > 0
                                            ? 'installment__state--due'
                                            : 'installment__state--paid'
                                    "
                                    >{{ stage.unpaid > 0 ? '待付' : '已缴' }}</text
                                >
                            </view>
                            <view class="installment__grid">
                                <view class="installment__cell">
                                    <text class="installment__cell-label">应收</text>
                                    <text class="installment__cell-value t-num">{{ money(stage.amount) }}</text>
                                </view>
                                <view class="installment__cell">
                                    <text class="installment__cell-label">已付</text>
                                    <text class="installment__cell-value t-num">{{ money(stage.paid) }}</text>
                                </view>
                                <view class="installment__cell">
                                    <text class="installment__cell-label">待付</text>
                                    <text class="installment__cell-value t-num">{{ money(stage.unpaid) }}</text>
                                </view>
                            </view>
                        </view>
                    </view>

                    <!-- 全款结算状态条 -->
                    <view v-else class="settle">
                        <view class="settle__head">
                            <text class="settle__title">全款结算</text>
                            <text
                                class="installment__state"
                                :class="
                                    fullSettle.unpaid > 0
                                        ? 'installment__state--due'
                                        : 'installment__state--paid'
                                "
                                >{{ fullSettle.unpaid > 0 ? '待付' : '已结清' }}</text
                            >
                        </view>
                        <view class="installment__grid">
                            <view class="installment__cell">
                                <text class="installment__cell-label">应付</text>
                                <text class="installment__cell-value t-num">{{ money(fullSettle.pay) }}</text>
                            </view>
                            <view class="installment__cell">
                                <text class="installment__cell-label">已付</text>
                                <text class="installment__cell-value t-num">{{ money(fullSettle.paid) }}</text>
                            </view>
                            <view class="installment__cell">
                                <text class="installment__cell-label">待付</text>
                                <text class="installment__cell-value t-num">{{ money(fullSettle.unpaid) }}</text>
                            </view>
                        </view>
                    </view>

                    <text v-if="order.deposit_remark" class="fee-note">{{
                        order.deposit_remark
                    }}</text>

                    <!-- 已收款流水 -->
                    <view v-if="paymentFlows.length" class="flows">
                        <text class="flows__title">已收款流水</text>
                        <view
                            v-for="(flow, index) in paymentFlows"
                            :key="flow.id"
                            class="flow"
                            :class="{ 'flow--divided': index > 0 }"
                        >
                            <view class="flow__head">
                                <text class="flow__type">{{ flow.typeText }}</text>
                                <text class="flow__amount t-num">{{ money(flow.amount) }}</text>
                            </view>
                            <view class="flow__meta">
                                <text class="flow__way">{{ flow.wayText }}</text>
                                <text v-if="flow.timeText" class="flow__time t-num">{{
                                    flow.timeText
                                }}</text>
                            </view>
                            <text v-if="flow.sn" class="flow__sn t-num">支付单号 {{ flow.sn }}</text>
                            <text v-if="flow.remark" class="flow__remark">备注：{{ flow.remark }}</text>
                        </view>
                    </view>
                </base-card>

                <section-header title="履约联系" subtitle="仅用于查看服务安排" />
                <base-card compact>
                    <view
                        v-for="(row, index) in contactRows"
                        :key="row.key"
                        class="row"
                        :class="{ 'row--divided': index > 0 }"
                    >
                        <text class="row__label">{{ row.label }}</text>
                        <view
                            v-if="row.key === 'mobile' && order.contact_mobile"
                            class="row__mobile-wrap"
                        >
                            <text class="row__value">{{ row.value || '未填写' }}</text>
                            <view
                                hover-class="is-pressed"
                                class="row__dial"
                                @tap="dialContactMobile"
                            >
                                <text class="row__dial-text">拨打</text>
                            </view>
                        </view>
                        <text v-else class="row__value">{{ row.value || '未填写' }}</text>
                    </view>
                    <view class="address">
                        <text class="address__label">服务地址</text>
                        <text class="address__value">{{
                            order.service_address || '未填写'
                        }}</text>
                    </view>
                </base-card>

                <section-header title="订单信息" />
                <base-card compact>
                    <view class="row">
                        <text class="row__label">订单编号</text>
                        <view class="order-sn">
                            <text class="order-sn__value t-num">{{ order.order_sn || '—' }}</text>
                            <text
                                v-if="order.order_sn"
                                class="order-sn__copy"
                                hover-class="is-pressed"
                                aria-role="button"
                                aria-label="复制订单编号"
                                @tap="copyOrderSn"
                                >复制</text
                            >
                        </view>
                    </view>
                    <view
                        v-for="row in orderInfoRows"
                        :key="row.key"
                        class="row row--divided"
                    >
                        <text class="row__label">{{ row.label }}</text>
                        <text class="row__value">{{ row.value || '—' }}</text>
                    </view>
                </base-card>

                <view v-if="orderChanges.length">
                    <section-header
                        title="变更与改期记录"
                        subtitle="改期与加项由顾问统一审核执行"
                    />
                    <base-card compact>
                        <view
                            v-for="(chg, index) in orderChanges"
                            :key="chg.id"
                            class="change-row"
                            :class="{ 'change-row--divided': index > 0 }"
                        >
                            <view class="change-row__head">
                                <text class="change-row__type">{{ chg.typeLabel }}</text>
                                <status-tag
                                    :label="chg.statusLabel"
                                    :tone="chg.statusTone"
                                    size="sm"
                                />
                            </view>
                            <text v-if="chg.dateDiff" class="change-row__dates">
                                改期安排：{{ chg.dateDiff }}
                            </text>
                            <text v-if="chg.apply_reason" class="change-row__text">
                                申请原因：{{ chg.apply_reason }}
                            </text>
                            <text v-if="chg.reject_reason" class="change-row__text change-row__text--danger">
                                拒绝原因：{{ chg.reject_reason }}
                            </text>
                            <text v-else-if="chg.audit_remark" class="change-row__text">
                                审核备注：{{ chg.audit_remark }}
                            </text>
                            <text v-if="chg.create_time" class="change-row__time">
                                提交于 {{ chg.create_time }}
                            </text>
                        </view>
                    </base-card>
                </view>

                <!-- 档期海报与常用工具 -->
                <section-header title="档期海报与快捷工具" subtitle="一键出图转发或引用常用话术" />
                <base-card compact>
                    <view v-if="latestLetter" class="letter-box">
                        <image
                            v-if="latestLetter.thumb_image_url || latestLetter.full_image_url"
                            :src="latestLetter.thumb_image_url || latestLetter.full_image_url"
                            class="letter-box__thumb"
                            mode="aspectFill"
                            @tap="previewLetterItem(latestLetter)"
                        />
                        <view class="letter-box__info">
                            <text class="letter-box__title"
                                >{{ latestLetter.config_name || '档期确认海报' }} · V{{
                                    latestLetter.version
                                }}</text
                            >
                            <text class="letter-box__meta">{{
                                latestLetter.confirm_date ||
                                latestLetter.create_time_text ||
                                '点击右侧按钮可刷新生成'
                            }}</text>
                        </view>
                        <base-button
                            text="预览大图"
                            variant="ghost"
                            size="sm"
                            :block="false"
                            @click="previewLetterItem(latestLetter)"
                        />
                    </view>
                    <view v-if="letterOutdated" class="letter-outdated">
                        <text class="letter-outdated__text">订单信息已变更，建议重新生成</text>
                    </view>
                    <text v-else-if="!latestLetter" class="letter-empty">
                        尚未生成本单档期海报，可一键生成发给新人确认，或先进入模板设置调整样式。
                    </text>

                    <scroll-view
                        v-if="templateVersions.length > 1"
                        class="template-strip"
                        scroll-x
                        :show-scrollbar="false"
                    >
                        <view class="template-strip__row">
                            <view
                                v-for="version in templateVersions"
                                :key="version.config_id"
                                hover-class="is-pressed"
                                class="template-chip"
                                :class="{ 'is-active': Number(version.config_id) === selectedConfigId }"
                                @tap="selectTemplateVersion(version.config_id)"
                            >
                                <text class="template-chip__text">{{
                                    version.template_name ||
                                    `模板 V${version.template_version || version.config_id}`
                                }}</text>
                                <text
                                    v-if="Number(version.is_default) === 1"
                                    class="template-chip__badge"
                                    >默认</text
                                >
                            </view>
                        </view>
                    </scroll-view>

                    <view class="letter-actions">
                        <base-button
                            :text="
                                letterGenerating
                                    ? '生成中…'
                                    : latestLetter
                                      ? '重新生成海报'
                                      : '生成档期海报'
                            "
                            variant="ink"
                            size="sm"
                            :block="false"
                            :loading="letterGenerating"
                            :disabled="letterGenerating"
                            @click="onGenerateOrderLetter"
                        />
                        <base-button
                            text="模板设置"
                            variant="ghost"
                            size="sm"
                            :block="false"
                            @click="goPosterEditor"
                        />
                        <base-button
                            text="常用话术"
                            variant="ghost"
                            size="sm"
                            :block="false"
                            @click="goCopyTemplates"
                        />
                        <base-button
                            text="新人档案"
                            variant="ghost"
                            size="sm"
                            :block="false"
                            @click="goCoupleDetail"
                        />
                    </view>

                    <view v-if="letterHistory.length > 1" class="letter-history">
                        <view
                            hover-class="is-pressed"
                            class="letter-history__toggle"
                            @tap="toggleHistory"
                        >
                            <text class="letter-history__toggle-text">{{
                                historyExpanded
                                    ? '收起历史版本'
                                    : `全部历史版本（${letterHistory.length}）`
                            }}</text>
                        </view>
                        <view v-if="historyExpanded" class="letter-history__list">
                            <view
                                v-for="item in letterHistory"
                                :key="item.letter_id"
                                hover-class="is-pressed"
                                class="history-row"
                                @tap="previewLetterItem(item)"
                            >
                                <view class="history-row__main">
                                    <text class="history-row__title"
                                        >V{{ item.version }} ·
                                        {{ item.config_name || '历史模板' }}</text
                                    >
                                    <text class="history-row__meta">{{
                                        item.confirm_date || item.create_time_text || '—'
                                    }}</text>
                                </view>
                                <text
                                    class="history-row__tag"
                                    :class="
                                        Number(item.is_outdated || 0) === 1
                                            ? 'history-row__tag--expired'
                                            : 'history-row__tag--valid'
                                    "
                                    >{{
                                        Number(item.is_outdated || 0) === 1 ? '已过期' : '当前有效'
                                    }}</text
                                >
                            </view>
                        </view>
                    </view>
                </base-card>

                <!-- 不可取消原因（服务端下发） -->
                <view v-if="cancelDisabledReason && !canCancel" class="readonly-note readonly-note--box">
                    <text class="readonly-note__title">暂不可取消</text>
                    <text class="readonly-note__text">{{ cancelDisabledReason }}</text>
                </view>
                <view v-else-if="!fulfillAction && !canCancel" class="readonly-note">
                    <text class="readonly-note__text">收款审核与异常处理请联系门店管理员</text>
                </view>

                <!-- 取消 / 拒绝接单原因弹窗。
                     这里是不可逆的破坏性操作，刻意**保留 tn-popup 不改 snap-sheet**：
                     底部抽屉支持下拉轻滑关闭，误滑一下就把已填写的取消原因丢掉，
                     风险高于「统一交互形态」的收益。内部表面按浮层口径升级（surface-float / r-48 /
                     shadow-sheet / control-border），快捷原因热区 88rpx。 -->
                <tn-popup
                    :model-value="cancelSheetVisible"
                    open-direction="bottom"
                    bg-color="transparent"
                    radius="0"
                    :safe-area-inset-bottom="true"
                    :overlay-closeable="!cancelSubmitting"
                    @update:model-value="onCancelPopupChange"
                >
                    <view class="cancel-sheet">
                        <view class="cancel-sheet__head">
                            <text class="cancel-sheet__title">{{ cancelButtonLabel }}</text>
                            <view
                                hover-class="is-pressed"
                                class="cancel-sheet__close"
                                aria-role="button"
                                aria-label="关闭"
                                @tap="closeCancelSheet"
                            >
                                <tn-icon name="close" :size="28" class="cancel-sheet__close-icon" />
                            </view>
                        </view>

                        <view class="cancel-sheet__body">
                            <view class="cancel-summary">
                                <view class="cancel-summary__row">
                                    <text class="cancel-summary__label">订单编号</text>
                                    <text class="cancel-summary__value ellipsis t-num">{{
                                        order.order_sn || '—'
                                    }}</text>
                                </view>
                                <text class="cancel-summary__tip">{{ cancelSheetHint }}</text>
                            </view>

                            <view class="cancel-field">
                                <text class="cancel-field__label">快捷原因</text>
                                <view class="cancel-chips">
                                    <view
                                        v-for="reason in quickCancelReasons"
                                        :key="reason"
                                        hover-class="is-pressed"
                                        class="cancel-chip"
                                        :class="{ 'is-on': cancelReason === reason }"
                                        @tap="pickQuickReason(reason)"
                                    >
                                        <text class="cancel-chip__text">{{ reason }}</text>
                                    </view>
                                </view>
                            </view>

                            <view class="cancel-field">
                                <view class="cancel-field__head">
                                    <text class="cancel-field__label">取消原因 *</text>
                                    <text class="cancel-field__count"
                                        >{{ cancelReasonLength }}/200</text
                                    >
                                </view>
                                <textarea
                                    v-model="cancelReason"
                                    class="cancel-input"
                                    :class="{ 'is-error': !!cancelError }"
                                    placeholder="请说明取消原因，便于平台与客户同步处理"
                                    placeholder-class="cancel-input__ph"
                                    :maxlength="200"
                                    :disabled="cancelSubmitting"
                                    @input="onCancelReasonInput"
                                />
                                <text v-if="cancelError" class="cancel-error">{{ cancelError }}</text>
                            </view>
                        </view>

                        <view class="cancel-sheet__foot">
                            <view class="cancel-sheet__btn">
                                <base-button
                                    text="再想想"
                                    variant="ghost"
                                    size="md"
                                    :disabled="cancelSubmitting"
                                    @click="closeCancelSheet"
                                />
                            </view>
                            <view class="cancel-sheet__btn">
                                <base-button
                                    :text="cancelSubmitting ? '提交中…' : `确认${cancelButtonLabel}`"
                                    variant="danger"
                                    size="md"
                                    :loading="cancelSubmitting"
                                    :disabled="cancelSubmitting"
                                    @click="submitCancel"
                                />
                            </view>
                        </view>
                    </view>
                </tn-popup>
            </template>
        </view>

        <template #bottom>
            <!-- 双操作：拒绝接单 + 确认接单（隐藏侧边说明，375px 下两按钮等分） -->
            <bottom-bar
                v-if="
                    guardAllowed &&
                    phase === 'ready' &&
                    Boolean(fulfillAction) &&
                    canCancel
                "
            >
                <view class="bar__duo">
                    <view class="bar__btn">
                        <base-button
                            :text="cancelButtonLabel"
                            variant="danger"
                            size="lg"
                            @click="openCancelSheet"
                        />
                    </view>
                    <view class="bar__btn">
                        <base-button
                            :text="acting ? '处理中…' : fulfillAction?.label"
                            variant="ink"
                            size="lg"
                            :loading="acting"
                            :disabled="acting"
                            @click="onFulfillAction"
                        />
                    </view>
                </view>
            </bottom-bar>

            <!-- 仅可取消（如待支付未付款） -->
            <bottom-bar
                v-else-if="guardAllowed && phase === 'ready' && canCancel"
            >
                <view class="bar__single">
                    <base-button
                        :text="cancelButtonLabel"
                        variant="danger"
                        size="lg"
                        :block="false"
                        @click="openCancelSheet"
                    />
                </view>
            </bottom-bar>

            <!-- 仅履约动作（开始履约 / 完成服务） -->
            <bottom-bar v-else-if="guardAllowed && phase === 'ready' && fulfillAction">
                <text class="bar__note">{{ fulfillAction?.note }}</text>
                <template #actions>
                    <base-button
                        :text="acting ? '处理中…' : fulfillAction?.label"
                        variant="ink"
                        size="lg"
                        :block="false"
                        :loading="acting"
                        :disabled="acting"
                        @click="onFulfillAction"
                    />
                </template>
            </bottom-bar>
        </template>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
/**
 * 服务人员订单详情（含接单/开始履约/完成服务三步操作 + 拒绝接单/取消订单）。
 *
 * 使用 /staff_center/orderDetail，服务端按 staff_id 过滤服务项并鉴权。
 * 不复用用户订单详情，避免误入下单人校验及支付、取消等客户动作。
 * 取消能力（can_staff_cancel / staff_cancel_label / staff_cancel_disabled_reason）
 * 一律以服务端下发为准，页面不做二次推断。
 */
import { computed, ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import {
    ORDER_CHANGE_STATUS_NAME_MAP,
    ORDER_CHANGE_STATUS_TONE,
    ORDER_CHANGE_TYPE_NAME_MAP,
    ORDER_STATUS_NAME_MAP,
    ORDER_STATUS_TONE,
    resolveStatusLabel,
    resolveTone
} from '@shared/contracts'
import { formatMoney } from '@shared/utils/format'
import {
    generateStaffConfirmLetter,
    getStaffConfirmLetterConfig,
    getStaffConfirmLetterDetail,
    getStaffConfirmLetterHistory,
    getStaffOrderDetail,
    staffOrderCancel,
    staffOrderConfirm,
    staffOrderStartService,
    staffOrderComplete,
    type StaffConfirmLetterRecord,
    type StaffConfirmLetterVersion
} from '@/api/staff'
import StaffGuard from '../../components/staff-guard/staff-guard.vue'
import { useStaffGuard } from '@/composables/useStaffGuard'
import { useBadgeStore } from '@/stores/badge'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { navigate } from '@/utils/link'
import { haptic } from '@/utils/haptics'
import { toast } from '@/composables/useToast'
import type { LoadPhase } from '@/api/types'

const badgeStore = useBadgeStore()

const {
    guardPhase,
    guardReason,
    guardError,
    guardAllowed,
    verify,
    onGuardRetry,
    onGuardAction
} = useStaffGuard(() => load(true))

const phase = ref<LoadPhase>('idle')
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))
const orderId = ref(0)
const order = ref<Record<string, any>>({})
const loading = ref(false)
const acting = ref(false)
const hasLoadedOnce = ref(false)
const letterHistory = ref<StaffConfirmLetterRecord[]>([])
const letterGenerating = ref(false)
const templateVersions = ref<StaffConfirmLetterVersion[]>([])
const selectedConfigId = ref(0)
const historyExpanded = ref(false)

const cancelSheetVisible = ref(false)
const cancelReason = ref('')
const cancelError = ref('')
const cancelSubmitting = ref(false)

const latestLetter = computed(() => letterHistory.value[0] || null)

const letterOutdated = computed(() => Number(latestLetter.value?.is_outdated || 0) === 1)

function dialContactMobile() {
    const mobile = String(order.value.contact_mobile || '').trim()
    if (!mobile) return
    haptic('light', 'staff-order-dial')
    uni.makePhoneCall({ phoneNumber: mobile, fail: () => {} })
}

async function fetchLetterHistory() {
    if (!orderId.value) return
    try {
        const res = await getStaffConfirmLetterHistory({ order_id: orderId.value })
        letterHistory.value = Array.isArray(res) ? res : []
    } catch {
        letterHistory.value = []
    }
}

/** 静默拉取可用模板版本：仅保留启用项，默认选中 is_default / 当前 config_id / 首项 */
async function fetchTemplateVersions() {
    try {
        const res = await getStaffConfirmLetterConfig()
        const raw = Array.isArray(res?.versions) ? res.versions : []
        const enabled = raw.filter((item) => Number((item as any)?.status ?? 1) === 1)
        templateVersions.value = enabled
        if (!enabled.length) {
            selectedConfigId.value = Number(res?.config_id || 0)
            return
        }
        const stillExists = enabled.some(
            (item) => Number(item.config_id || 0) === selectedConfigId.value
        )
        if (stillExists) return
        const defaultOne = enabled.find((item) => Number(item.is_default) === 1)
        selectedConfigId.value = Number(
            defaultOne?.config_id || res?.config_id || enabled[0]?.config_id || 0
        )
    } catch {
        // 静默失败：模板列表不可用时仍按默认模板生成
        templateVersions.value = []
    }
}

function selectTemplateVersion(configId: number) {
    const id = Number(configId || 0)
    if (id <= 0 || id === selectedConfigId.value) return
    haptic('light', 'staff-order-template')
    selectedConfigId.value = id
}

function toggleHistory() {
    haptic('light', 'staff-order-history')
    historyExpanded.value = !historyExpanded.value
}

async function previewLetterItem(item: StaffConfirmLetterRecord | null) {
    if (!item) return
    let fullUrl = String(item.full_image_url || item.thumb_image_url || '').trim()
    if (!fullUrl && item.letter_id > 0) {
        try {
            const detail = await getStaffConfirmLetterDetail({ letter_id: item.letter_id })
            fullUrl = String(detail?.full_image_url || detail?.thumb_image_url || '').trim()
        } catch {
            fullUrl = ''
        }
    }
    if (!fullUrl) {
        toast.warning('海报图片链接暂不可用')
        return
    }
    uni.previewImage({ urls: [fullUrl], current: fullUrl })
}

async function onGenerateOrderLetter() {
    if (!orderId.value || letterGenerating.value) return
    letterGenerating.value = true
    try {
        const res = await generateStaffConfirmLetter({
            order_id: orderId.value,
            ...(selectedConfigId.value > 0 ? { config_id: selectedConfigId.value } : {})
        })
        toast.success('档期海报已生成')
        await fetchLetterHistory()
        const imgUrl = String(res?.full_image_url || res?.thumb_image_url || '').trim()
        if (imgUrl) {
            uni.previewImage({ urls: [imgUrl], current: imgUrl })
        }
    } catch (e: any) {
        toast.error(String(e?.message || '生成档期海报失败'))
    } finally {
        letterGenerating.value = false
    }
}

function goPosterEditor() {
    navigate('/packages/pkg-staff/pages/card-poster/card-poster?tab=poster')
}

function goCopyTemplates() {
    navigate({
        path: '/packages/pkg-staff/pages/copy-templates/copy-templates',
        query: orderId.value > 0 ? { order_id: orderId.value } : undefined
    })
}

function goCoupleDetail() {
    if (!orderId.value) return
    navigate({
        path: '/packages/pkg-staff/pages/couple-detail/couple-detail',
        query: { source: 'order', id: orderId.value }
    })
}

function toNumber(value: unknown) {
    const n = Number(value ?? 0)
    return Number.isFinite(n) ? n : 0
}

function money(value: unknown) {
    const n = Number(value ?? 0)
    if (!Number.isFinite(n)) return '—'
    return formatMoney(n, {
        symbol: '¥',
        thousand: true,
        hideZeroDecimals: true
    })
}

function maskMobile(value: unknown) {
    const mobile = String(value || '')
    return mobile.replace(/^(\d{3})\d{4}(\d{4})$/, '$1****$2')
}

const statusLabel = computed(() => {
    const mapped = resolveStatusLabel(
        ORDER_STATUS_NAME_MAP as any,
        Number(order.value.order_status)
    )
    return String(order.value.order_status_desc || mapped.label)
})

const statusTone = computed(() =>
    resolveTone(ORDER_STATUS_TONE as any, Number(order.value.order_status))
)

function resolveServiceItemStatus(orderStatus: number, item: any): {
    statusText: string
    statusTone: 'success' | 'warning' | 'neutral'
} {
    const itemStatus = Number(item.item_status)
    const confirmed = Number(item.confirm_status) === 1
    if (itemStatus === 3 || orderStatus === 6) {
        return { statusText: '已取消', statusTone: 'neutral' }
    }
    if (orderStatus === 8) {
        return { statusText: '已退款', statusTone: 'neutral' }
    }
    if (orderStatus === 10) {
        return { statusText: '退款中', statusTone: 'warning' }
    }
    if (orderStatus === 9) {
        return { statusText: '已关闭', statusTone: 'neutral' }
    }
    if (confirmed) {
        return { statusText: '已确认', statusTone: 'success' }
    }
    if (orderStatus === 0) {
        return { statusText: '待确认', statusTone: 'warning' }
    }
    return { statusText: '', statusTone: 'neutral' }
}

const serviceItems = computed(() => {
    const items = Array.isArray(order.value.items) ? order.value.items : []
    const orderStatus = Number(order.value.order_status)
    return items.map((item: any) => {
        const status = resolveServiceItemStatus(orderStatus, item)
        const addons = Array.isArray(item.addons) ? item.addons : []
        return {
            id: Number(item.id),
            title: String(item.package_name || item.item_type_desc || '服务项'),
            desc: String(item.package_description || ''),
            dateText: item.service_date ? `服务日期 ${item.service_date}` : '服务日期待安排',
            quantityText: `数量 ${Math.max(1, toNumber(item.quantity) || 1)}`,
            amountText: money(item.subtotal ?? item.price),
            statusText: status.statusText,
            statusTone: status.statusTone,
            addons: addons.map((addon: any) => ({
                id: Number(addon.id),
                name: String(addon.addon_name || '附加服务'),
                quantity: Math.max(1, toNumber(addon.quantity) || 1),
                amountText: money(addon.subtotal ?? addon.price)
            }))
        }
    })
})

const serviceDateSummary = computed(() => {
    const dates = Array.from(
        new Set(
            serviceItems.value
                .map((item) => item.dateText.replace('服务日期 ', ''))
                .filter((text) => text && text !== '服务日期待安排')
        )
    )
    if (!dates.length) return '待安排'
    if (dates.length === 1) return dates[0]
    return `${dates[0]} 等 ${dates.length} 个日期`
})

const timeoutText = computed(() => {
    if (Number(order.value.order_status) === 0 && order.value.confirm_timeout_action_desc) {
        return `请及时确认接单，${order.value.confirm_timeout_action_desc}`
    }
    if (Number(order.value.order_status) === 1 && order.value.pay_timeout_action_desc) {
        return `客户支付时限：${order.value.pay_timeout_action_desc}`
    }
    return ''
})

/** 定金分期：金额 > 0，或服务端 payment_mode 标记为定金（兼容数字枚举与 'deposit' 字符串） */
const isDepositMode = computed(() => {
    const mode = order.value.payment_mode
    return (
        toNumber(order.value.deposit_amount) > 0 ||
        Number(mode) === 1 ||
        String(mode || '') === 'deposit'
    )
})

type HeroFactTone = 'paid' | 'due' | 'neutral'

interface HeroFact {
    key: string
    label: string
    value: string
    stateText?: string
    stateTone?: HeroFactTone
    note?: string
}

const heroFacts = computed<HeroFact[]>(() => {
    const pay = toNumber(order.value.pay_amount)
    const total = toNumber(order.value.total_amount)
    const discount = toNumber(order.value.discount_amount)
    const deposit = toNumber(order.value.deposit_amount)
    const depositUnpaid = toNumber(order.value.deposit_unpaid_amount)
    const balance = toNumber(order.value.balance_amount)
    const balanceUnpaid = toNumber(order.value.balance_unpaid_amount)
    const paid = toNumber(order.value.paid_amount)
    const unpaid = toNumber(order.value.unpaid_amount)

    let payNote = ''
    if (discount > 0) {
        payNote = `已优惠 ${money(discount)}`
    } else if (total - pay > 0.01) {
        payNote = `原价 ${money(total)}`
    }

    const facts: HeroFact[] = [
        { key: 'pay', label: '订单应付', value: money(pay), note: payNote }
    ]

    if (isDepositMode.value) {
        facts.push({
            key: 'deposit',
            label: '定金',
            value: money(deposit),
            stateText: depositUnpaid > 0 ? '待付' : '已缴',
            stateTone: depositUnpaid > 0 ? 'due' : 'paid'
        })
        facts.push({
            key: 'balance',
            label: '尾款',
            value: money(balance),
            stateText: balanceUnpaid > 0 ? '待付' : '已缴',
            stateTone: balanceUnpaid > 0 ? 'due' : 'paid'
        })
    } else {
        facts.push({
            key: 'deposit',
            label: '全款模式',
            value: money(pay),
            stateText: '无需分期',
            stateTone: 'neutral'
        })
        facts.push({
            key: 'balance',
            label: '已付 / 待付',
            value: `${money(paid)} / ${money(unpaid)}`
        })
    }

    const stageDesc = String(order.value.current_pay_stage_desc || '').trim()
    const channelDesc = String(order.value.payment_channel_desc || '').trim()
    facts.push({
        key: 'mode',
        label: '支付方式',
        value: String(order.value.payment_mode_desc || channelDesc || '—'),
        stateText: stageDesc || undefined,
        stateTone: 'neutral',
        note: [channelDesc, `服务 ${serviceDateSummary.value}`].filter(Boolean).join(' · ')
    })

    return facts
})

const showCancelBanner = computed(() => {
    return (
        Number(order.value.order_status) === 6 ||
        Boolean(order.value.collaborator_cancelled) ||
        Boolean(cancelReasonText.value) ||
        Boolean(cancelTimeText.value)
    )
})

/** 本人在多服务人员单里的角色（main / collaborator / 未识别） */
const staffRole = computed(() => String(order.value.staff_role || '').trim())

const isCollaborator = computed(() => staffRole.value === 'collaborator')

const staffRoleNotice = computed(() => {
    if (staffRole.value === 'main') {
        return {
            title: '多服务人员协作单',
            text: '金额仅统计你名下部分；取消将同步取消其他协作服务。'
        }
    }
    if (staffRole.value === 'collaborator') {
        return {
            title: '协作服务订单',
            text: '金额仅统计本人部分；取消仅释放本人档期。'
        }
    }
    return {
        title: '多服务人员拼单',
        text: '金额仅统计你名下部分，不支持整单取消。'
    }
})

/** 协作服务已被取消，但整单未关闭 —— 横幅不能误导成「整个订单已关闭」 */
const collaboratorOnlyCancelled = computed(
    () =>
        Boolean(order.value.collaborator_cancelled) && Number(order.value.order_status) !== 6
)

const cancelReasonText = computed(() => String(order.value.cancel_reason || '').trim())
const cancelTimeText = computed(() => String(order.value.cancel_time_text || '').trim())

const feeTags = computed(() => {
    const tags: Array<{ key: string; label: string }> = []
    if (order.value.payment_mode_desc) {
        tags.push({ key: 'mode', label: String(order.value.payment_mode_desc) })
    }
    if (order.value.payment_channel_desc) {
        tags.push({ key: 'channel', label: String(order.value.payment_channel_desc) })
    }
    if (order.value.current_pay_stage_desc) {
        tags.push({ key: 'stage', label: String(order.value.current_pay_stage_desc) })
    }
    return tags
})

const installmentStages = computed(() => {
    if (!isDepositMode.value) return []
    return [
        {
            key: 'deposit',
            title: '第一阶段 · 定金',
            amount: toNumber(order.value.deposit_amount),
            paid: toNumber(order.value.deposit_paid_amount),
            unpaid: toNumber(order.value.deposit_unpaid_amount)
        },
        {
            key: 'balance',
            title: '第二阶段 · 尾款',
            amount: toNumber(order.value.balance_amount),
            paid: toNumber(order.value.balance_paid_amount),
            unpaid: toNumber(order.value.balance_unpaid_amount)
        }
    ]
})

const fullSettle = computed(() => ({
    pay: toNumber(order.value.pay_amount),
    paid: toNumber(order.value.paid_amount),
    unpaid: toNumber(order.value.unpaid_amount)
}))

const paymentFlows = computed(() => {
    if (order.value.is_whole_order === false) return []
    const rows = Array.isArray(order.value.payments) ? order.value.payments : []
    return rows.map((item: any) => ({
        id: Number(item.id),
        typeText: String(item.pay_type_desc || '支付'),
        amount: item.pay_amount,
        wayText: String(item.pay_way_desc || '—'),
        timeText: String(item.pay_time_text || ''),
        sn: String(item.payment_sn || ''),
        remark: String(item.remark || '')
    }))
})

const fulfillAction = computed(() => {
    if (Number(order.value.can_staff_confirm) === 1) {
        return {
            key: 'confirm' as const,
            label: '确认接单',
            note: '确认后将通知客户支付',
            modalTitle: '确认接单',
            modalContent: '确认接受本订单的服务安排？确认后将通知客户支付。',
            successText: '已确认接单'
        }
    }
    if (Number(order.value.can_staff_start) === 1) {
        return {
            key: 'start' as const,
            label: '开始履约',
            note: '到达现场后点击开始服务',
            modalTitle: '开始履约',
            modalContent: '确认开始本订单的服务履约？',
            successText: '已开始履约'
        }
    }
    if (Boolean(order.value.can_staff_complete)) {
        return {
            key: 'complete' as const,
            label: '完成服务',
            note: '服务全部结束后点击完成',
            modalTitle: '完成服务',
            modalContent: '确认本订单的服务已全部完成？',
            successText: '已完成服务'
        }
    }
    return null
})

const canCancel = computed(() => Boolean(order.value?.can_staff_cancel))

const cancelButtonLabel = computed(
    () =>
        String(
            order.value?.staff_cancel_label ||
                (Number(order.value?.order_status) === 0 ? '拒绝接单' : '取消订单')
        )
)

const cancelDisabledReason = computed(() =>
    String(order.value?.staff_cancel_disabled_reason || '').trim()
)

const cancelReasonLength = computed(() => cancelReason.value.trim().length)

const quickCancelReasons = computed(() =>
    isCollaborator.value
        ? ['档期冲突无法协作', '路途较远无法到场', '个人安排变动', '其他原因']
        : Number(order.value.order_status) === 0
          ? ['档期冲突无法承接', '服务内容与需求不符', '距离过远无法到场', '其他原因']
          : ['客户临时改期', '客户要求取消订单', '无法联系上客户', '其他原因']
)

const cancelSheetHint = computed(() => {
    const notice = String(order.value?.staff_cancel_notice || '').trim()
    if (notice) return notice
    if (String(order.value?.staff_cancel_scope || '') === 'item') {
        return '取消后仅释放你的协作服务与本人档期，主服务不受影响，操作不可撤销。'
    }
    return cancelButtonLabel.value === '拒绝接单'
        ? '拒绝后订单将关闭，档期立即释放并通知客户，操作不可撤销。'
        : '取消后订单将关闭，已占用档期立即释放并通知客户，操作不可撤销。'
})

/** 取消成功 Toast：动作是「拒绝协作」「取消协作」还是普通拒单/取消 */
function cancelSuccessText(label: string) {
    if (label === '拒绝接单') return '已拒绝接单'
    if (label.includes('协作')) return label.includes('拒绝') ? '已拒绝协作' : '已取消协作'
    return '订单已取消'
}

function onFulfillAction() {
    const action = fulfillAction.value
    if (!guardAllowed.value || acting.value || !action || !orderId.value) return
    haptic('medium', 'staff-order-fulfill')
    // 确认类二次弹窗保留居中形态：uni.showModal 是系统原生模态，不做可下拉关闭的抽屉
    uni.showModal({
        title: action.modalTitle,
        content: action.modalContent,
        confirmText: action.label,
        cancelText: '取消',
        success: async (r) => {
            if (!r.confirm) return
            acting.value = true
            try {
                if (action.key === 'confirm') {
                    await staffOrderConfirm({ id: orderId.value })
                } else if (action.key === 'start') {
                    await staffOrderStartService({ id: orderId.value })
                } else {
                    await staffOrderComplete({ id: orderId.value })
                }
                badgeStore.invalidate()
                toast.success(action.successText)
                await load()
            } catch (e: any) {
                toast.error(String(e?.message || '操作失败，请稍后重试'))
            } finally {
                acting.value = false
            }
        }
    })
}

function openCancelSheet() {
    if (!canCancel.value || cancelSubmitting.value) return
    haptic('heavy', 'staff-order-cancel')
    cancelReason.value = ''
    cancelError.value = ''
    cancelSheetVisible.value = true
}

function closeCancelSheet() {
    if (cancelSubmitting.value) return
    cancelSheetVisible.value = false
}

function onCancelPopupChange(visible: boolean) {
    if (!visible && cancelSubmitting.value) return
    cancelSheetVisible.value = visible
}

function pickQuickReason(reason: string) {
    if (cancelSubmitting.value) return
    haptic('light', 'staff-order-cancel-reason')
    cancelReason.value = cancelReason.value === reason ? '' : reason
    cancelError.value = ''
}

function onCancelReasonInput() {
    if (cancelError.value) cancelError.value = ''
}

async function submitCancel() {
    if (cancelSubmitting.value || !orderId.value) return
    const reason = cancelReason.value.trim()
    if (reason.length < 2) {
        cancelError.value = '请填写至少 2 个字的取消原因'
        return
    }
    if (reason.length > 200) {
        cancelError.value = '取消原因不能超过 200 字'
        return
    }
    cancelError.value = ''
    cancelSubmitting.value = true
    haptic('heavy', 'staff-order-cancel-submit')
    try {
        await staffOrderCancel({ id: orderId.value, reason })
        badgeStore.invalidate()
        cancelSheetVisible.value = false
        toast.success(cancelSuccessText(cancelButtonLabel.value))
        await load()
    } catch (e: any) {
        // 保持弹窗与已填原因，错误就地展示，避免只弹 Toast 后内容丢失
        cancelError.value = String(e?.message || '操作失败，请稍后重试')
    } finally {
        cancelSubmitting.value = false
    }
}

const amountRows = computed(() => [
    { key: 'service', label: '服务金额', value: order.value.service_amount },
    { key: 'addon', label: '附加项金额', value: order.value.addon_amount },
    { key: 'discount', label: '优惠金额', value: order.value.discount_amount },
    { key: 'pay', label: '应付金额', value: order.value.pay_amount, strong: true },
    { key: 'paid', label: '已支付', value: order.value.paid_amount },
    { key: 'unpaid', label: '待支付', value: order.value.unpaid_amount }
])

const contactRows = computed(() => [
    { key: 'contact', label: '联系人', value: order.value.contact_name || '' },
    { key: 'mobile', label: '联系电话', value: maskMobile(order.value.contact_mobile) },
    { key: 'wedding', label: '婚期', value: order.value.wedding_date || '' }
])

const orderInfoRows = computed(() => [
    { key: 'source', label: '订单来源', value: order.value.source_desc || '' },
    { key: 'payType', label: '支付方式', value: order.value.pay_type_desc || '' },
    { key: 'created', label: '创建时间', value: order.value.create_time || '' },
    { key: 'remark', label: '订单备注', value: order.value.remark || '' }
])

const orderChanges = computed(() => {
    const raw = Array.isArray(order.value.changes) ? order.value.changes : []
    return raw.map((item: any) => {
        const typeMapped = resolveStatusLabel(
            ORDER_CHANGE_TYPE_NAME_MAP as any,
            Number(item.change_type)
        )
        const statusMapped = resolveStatusLabel(
            ORDER_CHANGE_STATUS_NAME_MAP as any,
            Number(item.change_status)
        )
        const oldDate = String(item.old_service_date || '').trim()
        const newDate = String(item.new_service_date || '').trim()
        const dateDiff = oldDate && newDate ? `${oldDate} → ${newDate}` : newDate || ''
        return {
            id: Number(item.id || 0),
            typeLabel: String(item.change_type_desc || typeMapped.label),
            statusLabel: String(item.change_status_desc || statusMapped.label),
            statusTone: resolveTone(ORDER_CHANGE_STATUS_TONE as any, Number(item.change_status)),
            dateDiff,
            apply_reason: String(item.apply_reason || ''),
            audit_remark: String(item.audit_remark || ''),
            reject_reason: String(item.reject_reason || ''),
            create_time: String(item.create_time || '')
        }
    })
})

function copyOrderSn() {
    haptic('light', 'staff-order-copy')
    uni.setClipboardData({
        data: String(order.value.order_sn || ''),
        success: () => toast.success('订单编号已复制')
    })
}

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

async function load(forceGuard = false) {
    if (loading.value) return
    loading.value = true
    try {
        if (!(await verify(forceGuard))) return
        if (!orderId.value) {
            rawError.value = new Error('missing-order-id')
            phase.value = 'error'
            return
        }
        if (!order.value.id) phase.value = 'loading'
        rawError.value = null
        const res = (await getStaffOrderDetail({ id: orderId.value })) || {}
        if (!res.id) throw new Error('empty-staff-order')
        order.value = res
        phase.value = 'ready'
        void fetchLetterHistory()
        void fetchTemplateVersions()
    } catch (e) {
        rawError.value = e
        if (order.value.id) {
            phase.value = 'ready'
            toast.error('刷新失败，请稍后重试')
        } else {
            phase.value = 'error'
        }
    } finally {
        loading.value = false
    }
}

onLoad((options: any) => {
    orderId.value = Number(options?.id || options?.order_id || 0)
    void load().then(() => {
        hasLoadedOnce.value = true
    })
})

onShow(() => {
    if (!hasLoadedOnce.value || !orderId.value) return
    void load()
})

function onRefresh(done: () => void) {
    Promise.resolve(load(true)).finally(done)
}
</script>

<style lang="scss" scoped>
.detail {
    box-sizing: border-box;
    padding: var(--sp-2) var(--page-gutter) calc(var(--sp-5) + var(--safe-bottom));
}

.hero {
    padding: var(--sp-4);
    border: 1rpx solid var(--c-brand-text);
    border-radius: var(--r-lg);
    background: linear-gradient(145deg, var(--c-dark-1), var(--c-dark-2));

    &__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-3);
    }

    &__eyebrow {
        font-family: var(--font-serif);
        font-size: var(--fs-xs);
        letter-spacing: 0.16em;
        color: var(--c-brand-soft);
    }

    &__sn {
        display: block;
        margin-top: var(--sp-3);
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
        color: var(--c-on-dark);
        overflow-wrap: anywhere;
    }

    &__facts {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: var(--sp-2);
        margin-top: var(--sp-4);
    }
}

.hero-fact {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 4rpx;
    padding: var(--sp-3);
    border-radius: var(--r-md);
    background: var(--c-dark-3);

    &__label {
        font-size: var(--fs-xs);
        line-height: var(--lh-tight);
        color: var(--c-on-dark-2);
    }

    &__value {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-body);
        color: var(--c-on-dark);
        font-variant-numeric: tabular-nums;
        overflow-wrap: anywhere;
    }

    &__meta {
        min-width: 0;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 2rpx;
    }

    &__state {
        max-width: 100%;
        font-size: var(--fs-xs);
        line-height: var(--lh-tight);
        font-weight: var(--fw-medium);
        overflow-wrap: anywhere;

        &--paid {
            color: var(--c-success-on-dark);
        }

        &--due {
            color: var(--c-warning-on-dark);
        }

        &--neutral {
            color: var(--c-on-dark-2);
        }
    }

    &__note {
        min-width: 0;
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
    }
}

.notice {
    display: flex;
    flex-direction: column;
    gap: var(--sp-1);
    margin-top: var(--sp-3);
    padding: var(--sp-3);
    border: 1rpx solid var(--c-neutral-border);
    border-left: 6rpx solid var(--c-neutral);
    border-radius: var(--r-md);
    background: var(--c-neutral-bg);

    &--warning {
        border-color: var(--c-warning-border);
        border-left-color: var(--c-warning);
        background: var(--c-warning-bg);
    }

    &--info {
        border-color: var(--c-info-border);
        border-left-color: var(--c-info);
        background: var(--c-info-bg);
    }

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
        color: var(--c-ink);
    }

    &__text {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
        overflow-wrap: anywhere;
    }

    &--warning &__title {
        color: var(--c-warning);
    }

    &--info &__title {
        color: var(--c-info);
    }
}

.timeout {
    display: flex;
    align-items: flex-start;
    gap: var(--sp-2);
    margin-top: var(--sp-2);
    padding: var(--sp-3);
    border: 1rpx solid var(--c-warning-border);
    border-radius: var(--r-md);
    background: var(--c-warning-bg);

    &__icon {
        flex-shrink: 0;
        margin-top: var(--sp-1);
        color: var(--c-warning);
    }

    &__text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
    }
}

.service {
    &--divided {
        margin-top: var(--sp-4);
        padding-top: var(--sp-4);
        border-top: 1rpx solid var(--c-line);
    }

    &__head {
        display: flex;
        align-items: flex-start;
        gap: var(--sp-3);
    }

    &__title {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-body);
        color: var(--c-ink);
    }

    &__desc {
        display: block;
        margin-top: var(--sp-2);
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }

    &__meta,
    &__amount-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-3);
    }

    &__meta {
        margin-top: var(--sp-2);
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }

    &__amount-row {
        margin-top: var(--sp-3);
    }

    &__amount-label {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__amount {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-text);
        font-variant-numeric: tabular-nums;
    }
}

.addons {
    margin-top: var(--sp-3);
    padding: var(--sp-3);
    border-radius: var(--r-sm);
    background: var(--c-neutral-bg);

    &__title {
        display: block;
        margin-bottom: var(--sp-2);
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
        color: var(--c-ink-2);
    }

    &__row {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
        min-height: 48rpx;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__name {
        flex: 1;
        min-width: 0;
    }

    &__quantity {
        flex-shrink: 0;
    }

    &__amount {
        flex-shrink: 0;
        color: var(--c-ink-2);
        font-variant-numeric: tabular-nums;
    }
}

.row {
    display: flex;
    align-items: baseline;
    gap: var(--sp-3);
    min-height: 72rpx;
    padding: var(--sp-2) 0;

    &--divided {
        border-top: 1rpx solid var(--c-line);
    }

    &__label {
        flex-shrink: 0;
        width: 144rpx;
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

    &__value--money {
        font-variant-numeric: tabular-nums;
    }

    &__value--strong {
        font-weight: var(--fw-semibold);
        color: var(--c-brand-text);
    }
}

.fee-tags {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-2);
    margin-bottom: var(--sp-1);
}

.fee-tag {
    padding: 4rpx var(--sp-2);
    border: 1rpx solid var(--c-primary-border);
    border-radius: var(--r-pill);
    background: var(--c-brand-bg);
    font-size: var(--fs-xs);
    line-height: var(--lh-body);
    color: var(--c-brand-text);
}

.payment-state {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-3);
    margin-top: var(--sp-3);
    padding: var(--sp-3);
    border-radius: var(--r-sm);
    background: var(--c-neutral-bg);

    &__label {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__value {
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
        color: var(--c-ink-2);
    }
}

.installment {
    margin-top: var(--sp-3);

    &__caption {
        display: block;
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
        color: var(--c-ink-2);
    }

    &__stage {
        margin-top: var(--sp-2);
        padding: var(--sp-3);
        border: 1rpx solid var(--c-line);
        border-radius: var(--r-sm);
        background: var(--c-bg);
    }

    &__stage-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__stage-title {
        font-size: var(--fs-xs);
        font-weight: var(--fw-medium);
        color: var(--c-ink-2);
    }

    &__state {
        flex-shrink: 0;
        padding: 2rpx var(--sp-2);
        border-radius: var(--r-pill);
        font-size: var(--fs-xs);
        font-weight: var(--fw-medium);

        &--paid {
            color: var(--c-success);
            background: var(--c-success-bg);
            border: 1rpx solid var(--c-success-border);
        }

        &--due {
            color: var(--c-warning);
            background: var(--c-warning-bg);
            border: 1rpx solid var(--c-warning-border);
        }
    }

    &__grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: var(--sp-2);
        margin-top: var(--sp-2);
    }

    &__cell {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 2rpx;
    }

    &__cell-label {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__cell-value {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        font-variant-numeric: tabular-nums;
        overflow-wrap: anywhere;
    }
}

.settle {
    margin-top: var(--sp-3);
    padding: var(--sp-3);
    border: 1rpx solid var(--c-line);
    border-radius: var(--r-sm);
    background: var(--c-bg);

    &__title {
        font-size: var(--fs-xs);
        font-weight: var(--fw-medium);
        color: var(--c-ink-2);
    }

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }
}

.fee-note {
    display: block;
    margin-top: var(--sp-3);
    font-size: var(--fs-xs);
    line-height: var(--lh-body);
    color: var(--c-ink-3);
}

.flows {
    margin-top: var(--sp-4);
    padding-top: var(--sp-3);
    border-top: 1rpx solid var(--c-line);

    &__title {
        display: block;
        margin-bottom: var(--sp-1);
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
        color: var(--c-ink-2);
    }
}

.flow {
    padding: var(--sp-2) 0;

    &--divided {
        border-top: 1rpx solid var(--c-line);
    }

    &__head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: var(--sp-3);
    }

    &__type {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &__amount {
        flex-shrink: 0;
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-text);
        font-variant-numeric: tabular-nums;
    }

    &__meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
        margin-top: 2rpx;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__way {
        flex-shrink: 0;
    }

    &__time {
        flex-shrink: 0;
        font-variant-numeric: tabular-nums;
    }

    &__sn,
    &__remark {
        display: block;
        margin-top: 2rpx;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
        overflow-wrap: anywhere;
    }
}

.address {
    display: flex;
    align-items: flex-start;
    gap: var(--sp-3);
    padding: var(--sp-3) 0 var(--sp-2);
    border-top: 1rpx solid var(--c-line);

    &__label {
        flex-shrink: 0;
        width: 144rpx;
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
}

.order-sn {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: var(--sp-2);

    &__value {
        min-width: 0;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink);
        overflow-wrap: anywhere;
    }

    &__copy {
        flex-shrink: 0;
        padding: var(--sp-2) 0 var(--sp-2) var(--sp-2);
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
    }
}

.readonly-note {
    padding: var(--sp-4) var(--sp-2) 0;
    text-align: center;

    &--box {
        margin-top: var(--sp-4);
        padding: var(--sp-3);
        border: 1rpx solid var(--c-line);
        border-radius: var(--r-md);
        background: var(--c-surface-2);
        text-align: left;
    }

    &__title {
        display: block;
        margin-bottom: var(--sp-1);
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
        color: var(--c-ink-2);
    }

    &__text {
        display: block;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
        overflow-wrap: anywhere;
    }
}

.change-row {
    display: flex;
    flex-direction: column;
    gap: var(--sp-1);
    padding: var(--sp-1) 0;

    &--divided {
        margin-top: var(--sp-2);
        padding-top: var(--sp-3);
        border-top: 1rpx solid var(--c-line);
    }

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__type {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
        color: var(--c-ink);
    }

    &__dates {
        font-size: var(--fs-xs);
        font-weight: var(--fw-medium);
        line-height: var(--lh-body);
        color: var(--c-brand-text);
    }

    &__text {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-2);

        &--danger {
            color: var(--c-danger);
        }
    }

    &__time {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }
}

/* ---- 吸底操作栏布局 ---- */
.bar__duo {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    width: 100%;
}

.bar__btn {
    flex: 1;
    min-width: 0;
}

.bar__single {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: var(--sp-3);
    width: 100%;
}

.bar__note {
    font-size: var(--fs-xs);
    color: var(--c-ink-3);
}

.row__mobile-wrap {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: var(--sp-2);
}

.row__dial {
    padding: 4rpx var(--sp-2);
    border-radius: var(--r-pill);
    background: var(--c-brand-bg);
    border: 1rpx solid var(--c-primary-border);
    flex-shrink: 0;

    &-text {
        font-size: var(--fs-20);
        color: var(--c-brand-text);
        font-weight: var(--fw-medium);
    }
}

.letter-box {
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    margin-bottom: var(--sp-3);

    &__thumb {
        width: 96rpx;
        height: 128rpx;
        border-radius: var(--r-sm);
        background: var(--c-surface-2);
        border: 1rpx solid var(--c-line);
        flex-shrink: 0;
    }

    &__info {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__meta {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.letter-empty {
    display: block;
    margin-bottom: var(--sp-3);
    font-size: var(--fs-xs);
    line-height: var(--lh-body);
    color: var(--c-ink-3);
}

.letter-outdated {
    display: flex;
    align-items: center;
    margin-bottom: var(--sp-3);
    padding: var(--sp-2) var(--sp-3);
    border: 1rpx solid var(--c-warning-border);
    border-radius: var(--r-sm);
    background: var(--c-warning-bg);

    &__text {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        font-weight: var(--fw-medium);
        color: var(--c-warning);
    }
}

.template-strip {
    margin-bottom: var(--sp-3);
    white-space: nowrap;

    &__row {
        display: inline-flex;
        align-items: center;
        gap: var(--sp-2);
    }
}

.template-chip {
    display: inline-flex;
    align-items: center;
    gap: var(--sp-1);
    padding: var(--sp-1) var(--sp-3);
    border: 1rpx solid var(--c-control-border);
    border-radius: var(--r-pill);
    background: var(--c-surface);

    &.is-active {
        border-color: var(--c-brand-text);
        background: var(--c-brand-bg);
    }

    &__text {
        font-size: var(--fs-xs);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &__badge {
        font-size: var(--fs-20);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-text);
    }
}

.letter-actions {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-2);
}

.letter-history {
    margin-top: var(--sp-3);
    padding-top: var(--sp-3);
    border-top: 1rpx solid var(--c-line);

    &__toggle {
        display: inline-flex;
        align-items: center;
        padding: var(--sp-1) 0;
    }

    &__toggle-text {
        font-size: var(--fs-xs);
        font-weight: var(--fw-medium);
        color: var(--c-brand-text);
    }

    &__list {
        display: flex;
        flex-direction: column;
        gap: var(--sp-2);
        margin-top: var(--sp-2);
    }
}

.history-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);
    padding: var(--sp-2) var(--sp-3);
    border: 1rpx solid var(--c-line);
    border-radius: var(--r-sm);
    background: var(--c-surface-2);

    &__main {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 2rpx;
    }

    &__title {
        font-size: var(--fs-xs);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &__meta {
        font-size: var(--fs-20);
        color: var(--c-ink-3);
        font-variant-numeric: tabular-nums;
    }

    &__tag {
        flex-shrink: 0;
        padding: 2rpx var(--sp-2);
        border-radius: var(--r-pill);
        font-size: var(--fs-20);
        font-weight: var(--fw-medium);

        &--valid {
            color: var(--c-success);
            background: var(--c-success-bg);
            border: 1rpx solid var(--c-success-border);
        }

        &--expired {
            color: var(--c-warning);
            background: var(--c-warning-bg);
            border: 1rpx solid var(--c-warning-border);
        }
    }
}

/* ---- 取消 / 拒绝接单弹窗（保留 tn-popup：破坏性操作不做可下拉关闭的抽屉） ---- */
.cancel-sheet {
    background: var(--c-surface-float);
    padding: var(--sp-4) var(--page-gutter) calc(var(--sp-4) + var(--safe-bottom));
    border-radius: var(--r-48) var(--r-48) 0 0;
    box-shadow: var(--shadow-sheet);

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__title {
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__close {
        width: 56rpx;
        height: 56rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--r-pill);
    }

    &__close-icon {
        color: var(--c-ink-3);
    }

    &__body {
        margin-top: var(--sp-3);
    }

    &__foot {
        display: flex;
        gap: var(--sp-2);
        margin-top: var(--sp-4);
    }

    &__btn {
        flex: 1;
        min-width: 0;
    }
}

.cancel-summary {
    padding: var(--sp-3);
    border: 1rpx solid var(--c-warning-border);
    border-radius: var(--r-md);
    background: var(--c-warning-bg);

    &__row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-3);
    }

    &__label {
        flex-shrink: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__value {
        flex: 1;
        min-width: 0;
        text-align: right;
        font-size: var(--fs-xs);
        color: var(--c-ink);
    }

    &__tip {
        display: block;
        margin-top: var(--sp-2);
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-warning);
    }
}

.cancel-field {
    margin-top: var(--sp-3);

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
        margin-bottom: var(--sp-1);
    }

    &__label {
        display: block;
        margin-bottom: var(--sp-1);
        font-size: var(--fs-xs);
        font-weight: var(--fw-medium);
        color: var(--c-ink-2);
    }

    &__count {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        font-variant-numeric: tabular-nums;
    }
}

.cancel-chips {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-2);
}

.cancel-chip {
    display: inline-flex;
    align-items: center;
    min-height: 88rpx;
    padding: 0 var(--sp-3);
    border: 1rpx solid var(--c-control-border);
    border-radius: var(--r-pill);
    background: var(--c-surface);
    transition:
        background-color var(--dur-fast) var(--ease-out),
        border-color var(--dur-fast) var(--ease-out),
        transform var(--dur-press) var(--ease-out);

    &.is-pressed {
        transform: scale(0.97);
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &.is-on {
        border-color: var(--c-brand-text);
        background: var(--c-brand-bg);

        .cancel-chip__text {
            color: var(--c-brand-text);
            font-weight: var(--fw-semibold);
        }
    }
}

.cancel-input {
    width: 100%;
    height: 168rpx;
    padding: var(--sp-2) var(--sp-3);
    border: 1rpx solid var(--c-control-border);
    border-radius: var(--r-sm);
    background: var(--c-surface);
    box-sizing: border-box;
    font-size: var(--fs-md);
    line-height: var(--lh-body);
    color: var(--c-ink);

    &__ph {
        color: var(--c-ink-3);
    }

    &.is-error {
        border-color: var(--c-danger);
    }
}

.cancel-error {
    display: block;
    margin-top: var(--sp-1);
    font-size: var(--fs-xs);
    line-height: var(--lh-body);
    color: var(--c-danger);
    overflow-wrap: anywhere;
}

@media (prefers-reduced-motion: reduce) {
    .cancel-chip {
        transition: none;

        &.is-pressed {
            transform: none;
        }
    }
}
</style>
