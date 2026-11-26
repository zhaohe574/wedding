<template>
    <page-shell
        bg="page"
        :bottom-bar="true"
        title="订单详情"
        nav-variant="solid"
        :nav-capsule-avoid="true"
    >
        <!-- 骨架：深色 hero + 里程碑 + 两张卡 -->
        <view v-if="loading" class="sk">
            <view class="sk__hero"><base-skeleton :rows="3" /></view>
            <view class="sk__card"><base-skeleton :rows="2" /></view>
            <view class="sk__card"><base-skeleton :rows="3" /></view>
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
            @retry="load"
        />

        <template v-else>
            <!-- Ord Hero（深色）：状态 chip + 倒计时 + 日期城市 + 金额三栏 -->
            <view class="ohero" :class="{ 'ohero--muted': isTerminal }">
                <view class="ohero__chip" :class="`is-${statusTone}`">
                    <view class="ohero__dot" aria-hidden="true" />
                    <text class="ohero__chip-text">{{ statusChip }}</text>
                </view>

                <!-- 状态解释：只写「现在为什么这样 + 下一步做什么」，不重复状态名 -->
                <text v-if="statusHint" class="ohero__hint">{{ statusHint }}</text>

                <view v-if="daysLeft !== null" class="ohero__days">
                    <text class="ohero__days-pre">距离婚礼还有</text>
                    <text class="ohero__days-num">{{ daysLeft }}</text>
                    <text class="ohero__days-suf">天</text>
                </view>
                <text v-else class="ohero__days-fallback">{{ statusTitle }}</text>

                <text v-if="heroDateText" class="ohero__meta">{{ heroDateText }}</text>

                <view class="ohero__nums">
                    <view class="ohero__num">
                        <text class="ohero__num-k">{{ amountLabels[0] }}</text>
                        <text class="ohero__num-v">{{ moneyText(paidAmount) }}</text>
                    </view>
                    <view class="ohero__num">
                        <text class="ohero__num-k">{{ amountLabels[1] }}</text>
                        <text class="ohero__num-v">{{ moneyText(dueAmount) }}</text>
                    </view>
                    <view class="ohero__num">
                        <text class="ohero__num-k">订单总额</text>
                        <text class="ohero__num-v">{{ moneyText(order.total_amount) }}</text>
                    </view>
                </view>
            </view>

            <!-- 待确认 / 线下凭证审核提示 -->
            <view v-if="statusNum === 0" class="notice">
                <tn-icon name="clock" :size="26" class="notice__ic" />
                <text class="notice__text">预计 1 小时内确认，请勿重复下单</text>
                <text
                    v-if="actions.contact_advisor"
                    class="notice__link"
                    hover-class="is-pressed"
                    @tap="contactAdvisor"
                    >联系顾问</text
                >
            </view>
            <view v-else-if="statusNum === 1 && isVoucherPending" class="notice">
                <tn-icon name="clock" :size="26" class="notice__ic" />
                <text class="notice__text">线下付款凭证已提交，正在等待后台核验到账。</text>
            </view>
            <view v-else-if="statusNum === 1 && isVoucherRejected" class="notice">
                <tn-icon name="tips" :size="26" class="notice__ic" />
                <text class="notice__text">{{
                    payVoucherRemark
                        ? `凭证未通过：${payVoucherRemark}`
                        : '线下付款凭证未通过审核，请重新上传'
                }}</text>
            </view>

            <!-- 四段里程碑：服务端没给就用真实字段派生，凑不出 2 个节点就不渲染 -->
            <view v-if="milestones.length" class="ms">
                <view v-for="m in milestones" :key="m.key" class="ms__i" :class="`is-${m.state}`">
                    <text class="ms__label">{{ m.label }}</text>
                    <text class="ms__value">{{ m.value || '' }}</text>
                </view>
            </view>

            <!-- 待重选协作服务提醒 -->
            <view v-if="pendingReselectItems.length" class="reselect">
                <view
                    v-for="item in pendingReselectItems"
                    :key="item.order_item_id"
                    class="reselect__card"
                >
                    <view class="reselect__head">
                        <text class="reselect__title">{{ item.role_label }}协作人员已取消</text>
                        <status-tag label="待挑选" tone="primary" size="sm" />
                    </view>
                    <text v-if="item.original_staff_name" class="reselect__meta"
                        >原服务人员：{{ item.original_staff_name }}</text
                    >
                    <text v-if="item.cancel_reason" class="reselect__meta"
                        >取消原因：{{ item.cancel_reason }}</text
                    >
                    <text v-if="item.cancel_time_text" class="reselect__meta"
                        >取消时间：{{ item.cancel_time_text }}</text
                    >
                    <text class="reselect__tip"
                        >主服务不受影响，可重选协作或暂不需要</text
                    >
                    <view class="reselect__actions">
                        <view class="reselect__btn">
                            <base-button
                                text="重新挑选"
                                variant="ink"
                                size="sm"
                                @click="openReselectPopup(item)"
                            />
                        </view>
                        <view class="reselect__btn">
                            <base-button
                                text="暂不需要"
                                variant="ghost"
                                size="sm"
                                :disabled="dismissingReselectItemId === item.order_item_id"
                                @click="handleDismissReselect(item)"
                            />
                        </view>
                    </view>
                </view>
            </view>

            <!-- 服务信息卡 -->
            <view class="card2">
                <text class="card2__title">服务信息</text>

                <view v-if="host.name" class="host">
                    <image
                        v-if="host.avatar"
                        class="host__avatar"
                        :src="imageUrl(host.avatar)"
                        mode="aspectFill"
                    />
                    <view class="host__body">
                        <view class="host__name-row">
                            <text class="host__name ellipsis">{{ host.name }}</text>
                            <text v-if="host.badge" class="host__badge">{{ host.badge }}</text>
                        </view>
                        <text v-if="hostDesc" class="host__desc ellipsis">{{ hostDesc }}</text>
                    </view>
                    <view
                        class="host__chat"
                        hover-class="is-pressed"
                        aria-role="button"
                        aria-label="与主持人会话"
                        @tap="goConsult"
                    >
                        <tn-icon name="chat" :size="28" />
                    </view>
                </view>

                <view v-if="extraServiceItems.length" class="svc">
                    <text class="svc__k">协作服务</text>
                    <text class="svc__v">{{ extraServiceItems.join(' · ') }}</text>
                </view>
                <view v-if="serviceDateText" class="svc">
                    <text class="svc__k">服务档期</text>
                    <text class="svc__v">{{ serviceDateText }}</text>
                </view>
                <view v-if="order.address" class="svc">
                    <text class="svc__k">举办场地</text>
                    <text class="svc__v">{{ order.address }}</text>
                </view>
                <view v-if="addonNames.length" class="svc">
                    <text class="svc__k">附加项</text>
                    <text class="svc__v">{{ addonNames.join(' · ') }}</text>
                </view>
                <view v-if="contactText" class="svc">
                    <text class="svc__k">联系人</text>
                    <text class="svc__v">{{ contactText }}</text>
                </view>
            </view>

            <!-- 待办卡：只有真实待办才渲染 -->
            <view v-if="todos.length || order.order_sn" class="card2">
                <template v-if="todos.length">
                    <text class="card2__title">待办 {{ todos.length }} 项</text>
                    <view
                        v-for="(t, i) in todos"
                        :key="t.key"
                        class="nx"
                        :class="{ 'nx--action': !!t.clickable }"
                        :hover-class="t.clickable ? 'is-pressed' : 'none'"
                        @tap="onTodoTap(t)"
                    >
                        <view class="nx__idx" :class="{ 'is-primary': t.primary }">
                            <text class="nx__idx-text">{{ i + 1 }}</text>
                        </view>
                        <view class="nx__body">
                            <text class="nx__title">{{ t.title }}</text>
                            <text v-if="t.desc" class="nx__desc">{{ t.desc }}</text>
                        </view>
                        <text v-if="t.tag" class="nx__tag">{{ t.tag }}</text>
                    </view>
                </template>

                <view v-if="order.order_sn" class="sn">
                    <text class="sn__text ellipsis">订单编号 {{ order.order_sn }}</text>
                    <text class="sn__copy" @tap="copySn">复制</text>
                </view>
            </view>

            <!-- 金额明细（金额只来自服务端；无字段就不显示行） -->
            <view v-if="hasAmountDetail" class="card2">
                <text class="card2__title">金额明细</text>
                <view class="kv">
                    <text class="kv__k">订单总额</text>
                    <text class="kv__v">{{ moneyText(order.total_amount) }}</text>
                </view>
                <view v-if="Number(order.discount_amount || 0) > 0" class="kv">
                    <text class="kv__k">优惠金额</text>
                    <text class="kv__v">-{{ moneyText(order.discount_amount) }}</text>
                </view>
                <view v-if="order.pay_amount != null" class="kv">
                    <text class="kv__k">应付金额</text>
                    <text class="kv__v">{{ moneyText(order.pay_amount) }}</text>
                </view>
                <view class="kv">
                    <text class="kv__k">已支付</text>
                    <text class="kv__v">{{ moneyText(paidAmount) }}</text>
                </view>
                <view v-if="refundableAmount !== null" class="kv">
                    <text class="kv__k">可退金额</text>
                    <text class="kv__v">{{ moneyText(refundableAmount) }}</text>
                </view>
                <view class="kv kv--total">
                    <text class="kv__k kv__k--total">待支付</text>
                    <text class="kv__v--total">{{ moneyText(dueAmount) }}</text>
                </view>
            </view>

            <!-- 取消订单：低频破坏性操作，放在页尾文字链，不占底栏主次位 -->
            <view v-if="actions.cancel" class="cancel">
                <text class="cancel__text" hover-class="is-pressed" @tap="handleCancel"
                    >取消订单</text
                >
            </view>

            <view class="foot-space" />

            <!-- 重新挑选协作人员 -->
            <tn-popup
                :model-value="reselectPopupVisible"
                open-direction="bottom"
                bg-color="transparent"
                radius="0"
                :safe-area-inset-bottom="true"
                :overlay-closeable="!reselectSubmitting"
                @update:model-value="onReselectPopupChange"
            >
                <view class="rsheet">
                    <view class="rsheet__head">
                        <view class="rsheet__title-wrap">
                            <text class="rsheet__title">重新挑选协作人员</text>
                            <text v-if="reselectItem" class="rsheet__sub"
                                >{{ reselectItem.role_label }} · 服务日期
                                {{ reselectItem.service_date || '待定' }}</text
                            >
                        </view>
                        <view
                            hover-class="is-pressed"
                            class="rsheet__close"
                            aria-role="button"
                            aria-label="关闭"
                            @tap="closeReselectPopup"
                        >
                            <tn-icon name="close" :size="28" class="rsheet__close-icon" />
                        </view>
                    </view>

                    <scroll-view class="rsheet__body" scroll-y>
                        <view v-if="reselectLoading" class="rsheet__pad">
                            <base-skeleton :rows="3" />
                        </view>

                        <base-empty
                            v-else-if="reselectError"
                            status="error"
                            error-kind="network"
                            error-text="候选人加载失败"
                            :desc="reselectError"
                            @retry="fetchReselectCandidates"
                        />

                        <base-empty
                            v-else-if="!reselectCandidates.length"
                            status="empty"
                            empty-text="当前档期暂无其他可约人员"
                            desc="您可以关闭提醒继续按主服务推进订单，或联系客服协助安排"
                        />

                        <view v-else class="rcand-list">
                            <view
                                v-for="c in reselectCandidates"
                                :key="c.staff_id"
                                class="rcand"
                                :class="{
                                    'is-selected': selectedCandidateStaffId === c.staff_id,
                                    'is-disabled': !c.schedule_available
                                }"
                                :hover-class="c.schedule_available ? 'is-pressed' : 'none'"
                                @tap="selectReselectCandidate(c)"
                            >
                                <image
                                    v-if="c.avatar"
                                    :src="imageUrl(c.avatar)"
                                    class="rcand__avatar"
                                    mode="aspectFill"
                                />
                                <view v-else class="rcand__avatar rcand__avatar--text">
                                    <text class="rcand__initial">{{
                                        c.name.slice(0, 1) || '师'
                                    }}</text>
                                </view>

                                <view class="rcand__body">
                                    <view class="rcand__name-row">
                                        <text class="rcand__name ellipsis">{{ c.name }}</text>
                                        <status-tag
                                            v-if="c.is_recommend === 1"
                                            label="推荐"
                                            tone="primary"
                                            size="sm"
                                            :dot="false"
                                        />
                                    </view>
                                    <text class="rcand__pkg ellipsis">{{ c.package_name }}</text>
                                    <text
                                        v-if="shortPackageDesc(c.package_description)"
                                        class="rcand__desc ellipsis"
                                        >{{ c.package_description }}</text
                                    >
                                    <view class="rcand__meta">
                                        <text v-if="c.rating">{{ candidateRating(c.rating) }}</text>
                                        <text v-if="c.order_count">{{ c.order_count }} 单</text>
                                    </view>
                                </view>

                                <view class="rcand__right">
                                    <text class="rcand__price">{{ moneyText(c.price) }}</text>
                                    <text
                                        class="rcand__sched"
                                        :class="{ 'is-ok': c.schedule_available }"
                                        >{{
                                            c.schedule_available
                                                ? '可约'
                                                : c.schedule_message || '档期不可约'
                                        }}</text
                                    >
                                </view>

                                <view
                                    class="rcand__check"
                                    :class="{ 'is-on': selectedCandidateStaffId === c.staff_id }"
                                    aria-hidden="true"
                                >
                                    <text class="rcand__check-text">✓</text>
                                </view>
                            </view>
                        </view>
                    </scroll-view>

                    <view class="rsheet__foot">
                        <view class="rsheet__btn">
                            <base-button
                                text="取消"
                                variant="ghost"
                                size="md"
                                :disabled="reselectSubmitting"
                                @click="closeReselectPopup"
                            />
                        </view>
                        <view class="rsheet__btn">
                            <base-button
                                :text="reselectConfirmText"
                                variant="ink"
                                size="md"
                                :loading="reselectSubmitting"
                                :disabled="!selectedCandidate || reselectSubmitting"
                                @click="submitReselect"
                            />
                        </view>
                    </view>
                </view>
            </tn-popup>
        </template>

        <template #bottom>
            <bottom-bar v-if="!loading && !error">
                <view class="abb">
                    <text class="abb__label">{{ barLabel }}</text>
                    <text v-if="barAmount !== null" class="abb__amount">{{
                        moneyText(barAmount)
                    }}</text>
                    <text v-else class="abb__na">—</text>
                </view>
                <template #actions>
                    <view class="abtns">
                        <base-button
                            v-if="secondaryAction"
                            :text="secondaryAction.text"
                            variant="ghost"
                            size="md"
                            :block="false"
                            @click="secondaryAction.onTap"
                        />
                        <base-button
                            v-if="mainAction"
                            :text="mainAction.text"
                            variant="ink"
                            size="md"
                            :block="false"
                            :loading="paying"
                            @click="mainAction.onTap"
                        />
                    </view>
                </template>
            </bottom-bar>
        </template>
    </page-shell>
    <app-toast />
</template>

<script setup lang="ts">
/**
 * 10 订单详情（设计稿 2919–2999）。
 *
 * 可执行动作一律读服务端 actions / action_reasons，不在页面里组合状态判断。
 * 展示态由业务态派生（02 §4.10），不新增枚举。
 *
 * 口径：
 *  - 主按钮只看 current_pay_stage / actions.pay，不看金额（need_pay_amount > 0 也可能现在不能付）；
 *  - 尾款未开放时主按钮就是「填写新人问卷」，绝不放一个点了没反应的支付键；
 *  - `can_user_refund` 为真才出现售后入口，没有就退化为「联系顾问」；
 *  - 里程碑没有服务端数据时，只用真实字段（已付时间 / 问卷任务 / 服务日期）派生，凑不出 2 个节点就不渲染。
 */
import { computed, ref } from 'vue'
import { onLoad, onShow, onUnload } from '@dcloudio/uni-app'
import { ORDER_STATUS_NAME_MAP, resolveStatusLabel, type OrderActions } from '@shared/contracts'
import {
    getOrderDetailV2,
    getOrderDetail,
    cancelOrder,
    uploadPayVoucher,
    getOrderReselectCandidates,
    reselectOrderCollaborator,
    dismissOrderReselect
} from '@/api/order'
import type { PendingReselectItem, ReselectCandidate } from '@/api/types'
import { useBadgeStore } from '@/stores/badge'
import { uploadImage } from '@/api/app'
import { prepay, getPayStatus } from '@/api/pay'
import { requestPayment } from '@/platform/pay'
import { formatDate, formatMoney, parseDateValue } from '@shared/utils/format'
import { navigate } from '@/utils/link'
import { report } from '@/platform/report'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { remindBeforeOaAction } from '@/utils/oa-reminder'
import { toast } from '@/composables/useToast'
import { chooseImages } from '@/composables/useChooseImage'
import { FB } from '@/constants/feedback'

const orderId = ref(0)
const loading = ref(true)
const error = ref(false)
const paying = ref(false)
const hasLoadedOnce = ref(false)

const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))

const order = ref<any>({})
const raw = ref<any>({})
const items = ref<any[]>([])
const payments = ref<any[]>([])
const payInfo = ref<any>({})
const payment = ref<any>({})
const offlineInfo = ref<any>({})
const pendingQuestionnaire = ref<any>(null)
/** 问卷是否已由服务人员发放（仅 send_status=1/2 或 is_issued=1 才可点击填写） */
const isQuestionnaireIssued = computed(() => {
    const q = pendingQuestionnaire.value
    if (!q || !q.id) return false
    if (q.is_issued !== undefined && q.is_issued !== null) {
        return Number(q.is_issued) === 1
    }
    if (q.send_status !== undefined && q.send_status !== null) {
        return [1, 2].includes(Number(q.send_status))
    }
    return false
})
const pendingReview = ref<any>(null)
const serverMilestones = ref<any[]>([])
const actions = ref<OrderActions & { apply_refund?: boolean; upload_voucher?: boolean; review?: boolean }>({
    pay: false,
    cancel: false,
    change_date: false,
    pause: false,
    contact_advisor: true
})
const payReason = ref('')
const remainSeconds = ref(0)

const badgeStore = useBadgeStore()

/* ---------------- 待重选协作服务 ---------------- */

/** 聚合响应根（v2/order 与降级 order/detail 都落在 raw 上） */
const root = computed<any>(() => raw.value || {})

const pendingReselectItems = computed<PendingReselectItem[]>(() =>
    Array.isArray(root.value?.pending_reselect_items) ? root.value.pending_reselect_items : []
)

const reselectPopupVisible = ref(false)
const reselectItem = ref<PendingReselectItem | null>(null)
const reselectLoading = ref(false)
const reselectError = ref('')
const reselectCandidates = ref<ReselectCandidate[]>([])
const selectedCandidateStaffId = ref(0)
const reselectSubmitting = ref(false)
const dismissingReselectItemId = ref(0)

let timer: any = null

/* ---------------- 展示态派生 ---------------- */

const statusNum = computed(() => Number(order.value?.order_status))
const isTerminal = computed(() => [4, 5, 6, 8, 9, 10].includes(statusNum.value))

const statusTitle = computed(() => {
    const mapped = resolveStatusLabel(ORDER_STATUS_NAME_MAP as any, statusNum.value)
    return order.value?.order_status_desc || (mapped.known ? mapped.label : '订单状态未知')
})

/** Hero chip：状态 + 支付阶段（都来自服务端字段） */
const statusChip = computed(() => {
    if ([6, 8, 9].includes(statusNum.value)) {
        return statusTitle.value
    }
    const stage = String(payInfo.value?.current_pay_stage || '')
    const paidText = stage === 'paid' ? '已付清' : Number(paidAmount.value) > 0 ? '定金已付' : ''
    return [statusTitle.value, paidText].filter(Boolean).join(' · ')
})

/** 状态 chip 的语义色：待确认/待支付/已暂停/退款中=琥珀，待服务/服务中/已完成/已评价=绿，取消/已退款/已删除=中性 */
const statusTone = computed<'brand' | 'warn' | 'success' | 'muted'>(() => {
    const s = statusNum.value
    if (s === 0 || s === 1 || s === 7 || s === 10) return 'warn'
    if (s === 2 || s === 3 || s === 4 || s === 5) return 'success'
    if (s === 6 || s === 8 || s === 9) return 'muted'
    return 'brand'
})

/**
 * 状态解释：只回答「现在为什么这样 + 下一步做什么」。
 * 待确认（status=0）是用户最容易误解的一态 —— 订单已创建但还不能付，
 * 必须明确写出「等商家确认」，否则用户会反复点支付。
 */
const isOfflineChannel = computed(
    () =>
        Number(order.value?.payment_channel ?? 1) === 2 ||
        Boolean(offlineInfo.value?.available) ||
        Boolean(actions.value.upload_voucher)
)
const payVoucher = computed(() =>
    String(offlineInfo.value?.pay_voucher ?? order.value?.pay_voucher ?? '').trim()
)
const payVoucherStatus = computed(() => {
    const v = offlineInfo.value?.pay_voucher_status ?? order.value?.pay_voucher_status
    return v === null || v === undefined || v === '' ? -1 : Number(v)
})
const payVoucherRemark = computed(() =>
    String(offlineInfo.value?.pay_voucher_audit_remark ?? order.value?.pay_voucher_audit_remark ?? '').trim()
)
const isVoucherPending = computed(
    () => isOfflineChannel.value && !!payVoucher.value && payVoucherStatus.value === 0
)
const isVoucherRejected = computed(
    () => isOfflineChannel.value && !!payVoucher.value && payVoucherStatus.value === 2
)
const canUploadVoucher = computed(
    () =>
        statusNum.value === 1 &&
        (Boolean(actions.value.upload_voucher) ||
            (isOfflineChannel.value && !isVoucherPending.value && !offlineInfo.value?.receipt_pending))
)

const statusHint = computed(() => {
    const s = statusNum.value
    if (s === 0) return '等待商家确认后开放支付'
    if (s === 1) {
        if (isVoucherPending.value) return '线下付款凭证审核中，请耐心等待'
        if (isVoucherRejected.value) {
            return payVoucherRemark.value
                ? `凭证未通过：${payVoucherRemark.value}`
                : '凭证未通过，请重新上传'
        }
        if (canUploadVoucher.value) return '当前需线下付款并上传支付凭证'
        return remainSeconds.value > 0 ? '请在倒计时结束前完成支付' : '档期已为你保留，请尽快支付'
    }
    if (s === 2) return '服务已确认，请按约定时间到场'
    if (s === 3) return '服务进行中'
    if (s === 4) return '服务已完成，可发表评价'
    if (s === 5) return '服务已完成，感谢你的评价'
    if (s === 6) return '订单已取消'
    if (s === 7) return '订单已暂停，如需恢复请联系顾问'
    if (s === 8) return '退款已完成，到账时间以支付渠道为准'
    if (s === 9) return '订单已关闭'
    if (s === 10) return '退款处理中，到账时间以支付渠道为准'
    return ''
})

const serviceDate = computed(() => parseDateValue(order.value?.service_date))

const serviceDateText = computed(() => {
    const d = serviceDate.value
    if (!d) return ''
    const w = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'][d.getDay()]
    const time = order.value?.service_time ? ` ${order.value.service_time}` : ''
    return `${formatDate(d, 'YYYY.MM.DD')} ${w}${time}`
})

const heroDateText = computed(() => {
    const d = serviceDate.value
    if (!d) return order.value?.address ? String(order.value.address) : ''
    const w = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'][d.getDay()]
    return [formatDate(d, 'YYYY.MM.DD'), w, order.value?.address].filter(Boolean).join(' · ')
})

/** 倒计时天数：只在服务日期在未来且订单未终止时给 */
const daysLeft = computed<number | null>(() => {
    const d = serviceDate.value
    if (!d || isTerminal.value) return null
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    const target = new Date(d.getFullYear(), d.getMonth(), d.getDate())
    const diff = Math.round((target.getTime() - today.getTime()) / 86400000)
    return diff >= 0 ? diff : null
})

/* ---------------- 金额 ---------------- */

const paidAmount = computed(() => {
    const v = order.value?.paid_amount ?? payment.value?.paid_amount
    if (v !== undefined && v !== null && v !== '') return v
    return null
})

const dueAmount = computed(() => {
    const v = payInfo.value?.need_pay_amount ?? payment.value?.unpaid_amount
    if (v !== undefined && v !== null && v !== '') return v
    return null
})

const refundableAmount = computed(() => {
    const v = payInfo.value?.refundable_amount ?? payment.value?.refundable_amount
    return v === undefined || v === null || v === '' ? null : v
})

const canUserRefund = computed(
    () =>
        Boolean(actions.value.apply_refund) ||
        Number(payInfo.value?.can_user_refund ?? payment.value?.can_user_refund ?? 0) === 1
)
const showAftersale = computed(
    () => canUserRefund.value || Boolean(actions.value.change_date) || Boolean(actions.value.pause)
)

const amountLabels = computed<[string, string]>(() => {
    const stage = String(payInfo.value?.current_pay_stage || '')
    if (stage === 'deposit') return ['已支付', '待付定金']
    if (stage === 'balance' || stage === 'balance_after_service') return ['已付定金', '待付尾款']
    return ['已支付', '待支付']
})

const hasAmountDetail = computed(
    () => order.value?.total_amount != null || paidAmount.value !== null || dueAmount.value !== null
)

function moneyText(v: any) {
    if (v === null || v === undefined || v === '') return '—'
    return `¥${formatMoney(v, { thousand: true })}`
}

/* ---------------- 服务信息 ---------------- */

const firstItem = computed(() => items.value[0] || null)

const host = computed(() => {
    const s = firstItem.value?.staff
    if (!s || !s.name) return { name: '', avatar: '', badge: '', rating: null as any }
    return {
        name: s.name,
        avatar: s.avatar || '',
        badge: s.rank_name || s.rank || '',
        rating: s.rating ?? null
    }
})

const hostDesc = computed(() => {
    const parts: string[] = []
    const pkg = firstItem.value?.package?.name
    if (pkg) parts.push(pkg)
    if (host.value.rating) parts.push(`${host.value.rating} 分`)
    return parts.join(' · ')
})

const addonNames = computed(() => {
    const list: string[] = []
    for (const it of items.value) {
        if (Number(it?.item_status ?? 0) === 3) continue
        if (Number(it?.item_type) === 2) {
            const legacyName = String(it?.package_name || it?.item_meta?.label || '').trim()
            if (legacyName) list.push(legacyName)
        }
        if (Array.isArray(it?.addons)) {
            for (const a of it.addons) {
                const name = String(a?.addon_name || a?.name || '').trim()
                if (name) list.push(name)
            }
        }
    }
    return list
})

const extraServiceItems = computed(() => {
    const list: string[] = []
    items.value.forEach((it: any, idx: number) => {
        if (idx === 0 || Number(it?.item_type) === 2 || Number(it?.item_status ?? 0) === 3) return
        const staffName = String(it?.staff?.name || it?.staff_name || '').trim()
        const pkgName = String(it?.package?.name || it?.package_name || '').trim()
        const roleLabel = String(it?.item_meta?.role_label || '').trim()
        const text = [roleLabel, staffName, pkgName].filter(Boolean).join(' · ')
        if (text) list.push(text)
    })
    return list
})

const contactText = computed(() => {
    const name = order.value?.contact_name || ''
    const mobile = order.value?.contact_mobile || ''
    if (!name && !mobile) return ''
    const masked = /^\d{11}$/.test(mobile) ? `${mobile.slice(0, 3)}****${mobile.slice(7)}` : mobile
    return [name, masked].filter(Boolean).join(' · ')
})

/* ---------------- 里程碑 ---------------- */

interface MilestoneView {
    key: string
    label: string
    value: string
    state: 'done' | 'now' | 'todo'
}

const milestones = computed<MilestoneView[]>(() => {
    // 服务端给了就用服务端的（含 rehearsal 节点）
    if (serverMilestones.value.length >= 2) {
        return serverMilestones.value.map((m: any) => ({
            key: String(m.key || m.label),
            label: String(m.label || ''),
            value: String(m.value_text || ''),
            state: (m.state === 'done' || m.state === 'now'
                ? m.state
                : 'todo') as MilestoneView['state']
        }))
    }
    // 派生：只用真实字段，缺的节点不补
    const list: MilestoneView[] = []
    const paid = payments.value.find(
        (p) => Number(p?.pay_status) === 1 && Number(p?.pay_amount) > 0
    )
    if (paid) {
        const hasDeposit = Number(payInfo.value?.deposit_amount ?? order.value?.deposit_amount ?? 0) > 0
        const balancePaid = Number(payInfo.value?.balance_paid ?? order.value?.balance_paid ?? 0) === 1
        list.push({
            key: 'deposit',
            label: hasDeposit && !balancePaid ? '已付定金' : '已支付',
            value: formatDate(paid.pay_time, 'MM.DD', '已付'),
            state: 'done'
        })
    }
    if (pendingQuestionnaire.value) {
        list.push(
            isQuestionnaireIssued.value
                ? { key: 'questionnaire', label: '新人问卷', value: '待填写', state: 'now' }
                : { key: 'questionnaire', label: '新人问卷', value: '待发放', state: 'todo' }
        )
    }
    const d = serviceDate.value
    if (d) {
        list.push({
            key: 'ceremony',
            label: '婚礼当天',
            value: formatDate(d, 'MM.DD'),
            state: 'todo'
        })
    }
    return list.length >= 2 ? list : []
})

/* ---------------- 待重选协作服务：挑选弹窗 ---------------- */

const selectedCandidate = computed<ReselectCandidate | null>(
    () =>
        reselectCandidates.value.find((c) => c.staff_id === selectedCandidateStaffId.value) ||
        null
)

const reselectConfirmText = computed(() =>
    selectedCandidate.value ? `确认挑选（${moneyText(selectedCandidate.value.price)}）` : '确认挑选'
)

/** 套餐描述只在短句时展示，避免把弹窗撑高 */
function shortPackageDesc(desc?: string) {
    const text = String(desc || '').trim()
    return text && [...text].length <= 20 ? text : ''
}

function candidateRating(rating: number | string) {
    const n = Number(rating)
    return Number.isFinite(n) && n > 0 ? `${n} 分` : ''
}

async function openReselectPopup(item: PendingReselectItem) {
    if (!orderId.value || !item?.order_item_id) return
    reselectItem.value = item
    reselectPopupVisible.value = true
    selectedCandidateStaffId.value = 0
    await fetchReselectCandidates()
}

async function fetchReselectCandidates() {
    const item = reselectItem.value
    if (!item || !orderId.value || reselectLoading.value) return
    reselectLoading.value = true
    reselectError.value = ''
    try {
        const res = await getOrderReselectCandidates({
            order_id: orderId.value,
            order_item_id: Number(item.order_item_id)
        })
        reselectCandidates.value = Array.isArray(res?.candidates) ? res.candidates : []
        selectedCandidateStaffId.value = 0
    } catch (e: any) {
        reselectError.value = String(e?.message || '候选人加载失败')
        reselectCandidates.value = []
    } finally {
        reselectLoading.value = false
    }
}

function selectReselectCandidate(candidate: ReselectCandidate) {
    if (!candidate.schedule_available || reselectSubmitting.value) return
    selectedCandidateStaffId.value =
        selectedCandidateStaffId.value === candidate.staff_id ? 0 : candidate.staff_id
}

function closeReselectPopup() {
    if (reselectSubmitting.value) return
    reselectPopupVisible.value = false
}

function onReselectPopupChange(visible: boolean) {
    if (!visible && reselectSubmitting.value) return
    reselectPopupVisible.value = visible
}

async function submitReselect() {
    const item = reselectItem.value
    const candidate = selectedCandidate.value
    if (!item || !candidate || reselectSubmitting.value || !orderId.value) return
    reselectSubmitting.value = true
    try {
        await reselectOrderCollaborator({
            order_id: orderId.value,
            order_item_id: Number(item.order_item_id),
            staff_id: candidate.staff_id,
            package_id: candidate.package_id
        })
        reselectPopupVisible.value = false
        toast.success('已重新挑选，等待服务人员确认')
        badgeStore.invalidate()
        await load()
    } catch {
        // 请求层已提示
    } finally {
        reselectSubmitting.value = false
    }
}

async function handleDismissReselect(item: PendingReselectItem) {
    if (!orderId.value || dismissingReselectItemId.value > 0) return
    const confirmed = await new Promise<boolean>((resolve) => {
        uni.showModal({
            title: '暂不需要',
            content: '确认不需要重新挑选该协作服务？订单将按当前主服务继续推进。',
            success: (r) => resolve(!!r.confirm),
            fail: () => resolve(false)
        })
    })
    if (!confirmed) return
    dismissingReselectItemId.value = item.order_item_id
    try {
        await dismissOrderReselect({
            order_id: orderId.value,
            order_item_id: Number(item.order_item_id)
        })
        toast.success('已关闭重选提醒')
        badgeStore.invalidate()
        await load()
    } catch {
        // 请求层已提示
    } finally {
        dismissingReselectItemId.value = 0
    }
}

/* ---------------- 待办 ---------------- */

interface TodoView {
    key: string
    title: string
    desc: string
    tag: string
    primary: boolean
    /** 整行是否可点击（待重选协作、已发放问卷） */
    clickable?: boolean
    /** 待重选协作服务项：存在时点击整行唤起挑选弹窗 */
    reselectOrderItemId?: number
}

const todos = computed<TodoView[]>(() => {
    const list: TodoView[] = []
    for (const item of pendingReselectItems.value) {
        list.push({
            key: 'reselect-' + item.order_item_id,
            title: `${item.role_label}已取消，请重新挑选`,
            desc: '',
            tag: '待挑选',
            primary: true,
            clickable: true,
            reselectOrderItemId: item.order_item_id
        })
    }
    if (pendingQuestionnaire.value) {
        if (isQuestionnaireIssued.value) {
            list.push({
                key: 'questionnaire',
                title: '新人问卷未填写',
                desc: '影响台本与音乐编排，建议尽快完成',
                tag: '去填写',
                primary: true,
                clickable: true
            })
        } else {
            list.push({
                key: 'questionnaire_pending',
                title: '新人问卷',
                desc: pendingQuestionnaire.value.planned_send_time
                    ? `预计 ${pendingQuestionnaire.value.planned_send_time} 自动发放，发放后将通知你填写`
                    : '服务人员将在临近婚期时发放，发放后将通知你填写',
                tag: '待发放问卷',
                primary: false,
                clickable: false
            })
        }
    }
    const need = Number(payInfo.value?.need_pay_amount ?? 0)
    const stage = String(payInfo.value?.current_pay_stage || '')
    if (need > 0 && stage !== 'paid') {
        const stageName = stage === 'deposit' ? '定金' : stage === 'full' ? '全款' : '尾款'
        const openable =
            ['deposit', 'full', 'balance'].includes(stage) &&
            (actions.value.pay || canUploadVoucher.value)
        list.push({
            key: 'balance',
            title: `${stageName} ${moneyText(need)} ${isVoucherPending.value ? '凭证审核中' : openable ? '待支付' : '待开放'}`,
            desc: isVoucherPending.value
                ? '已提交线下付款凭证，等待后台审核'
                : openable
                  ? canUploadVoucher.value
                      ? '线下付款后请在本页上传凭证'
                      : `现在可以支付${stageName}`
                  : '到开放时间后可在本页支付',
            tag: isVoucherPending.value
                ? '审核中'
                : openable
                  ? canUploadVoucher.value
                      ? '传凭证'
                      : '可支付'
                  : '待开放',
            primary: false
        })
    }
    if (statusNum.value === 4 && (pendingReview.value?.id || actions.value.review)) {
        list.push({
            key: 'review',
            title: '服务已完成，待评价',
            desc: '分享本次服务体验，帮助更多新人',
            tag: '去评价',
            primary: true
        })
    }
    return list
})

/* ---------------- 主操作 ---------------- */

interface MainAction {
    text: string
    onTap: () => void
}

const canReviewEntry = computed(
    () => statusNum.value === 4 && (!!pendingReview.value?.id || Boolean(actions.value.review))
)

const mainAction = computed<MainAction | null>(() => {
    if (canReviewEntry.value) {
        return { text: '发表评价', onTap: goReview }
    }
    if (isTerminal.value) return null
    if (canUploadVoucher.value) {
        return { text: isVoucherRejected.value ? '重新上传凭证' : '上传付款凭证', onTap: handleUploadVoucher }
    }
    if (actions.value.pay && !isVoucherPending.value) {
        const stage = String(payInfo.value?.current_pay_stage || '')
        const label = stage === 'balance' ? '支付尾款' : stage === 'full' ? '支付全款' : '支付定金'
        return { text: label, onTap: handlePay }
    }
    if (isQuestionnaireIssued.value) {
        return { text: '填写新人问卷', onTap: goQuestionnaire }
    }
    if (actions.value.contact_advisor) {
        return { text: '联系顾问', onTap: contactAdvisor }
    }
    return null
})

const barLabel = computed(() => {
    // 终态不能再写「待支付」：取消/退款/已完成的订单没有应付语义
    if (isTerminal.value) return statusTitle.value
    if (payReason.value) return payReason.value
    const stage = String(payInfo.value?.current_pay_stage || '')
    if (stage === 'paid') return '已付清'
    return '待支付'
})

/**
 * 次要动作：一屏最多两个按钮。
 * 之前「联系顾问」可能同时出现在次要位和主按钮，用户看到两个一样的入口；
 * 这里保证主按钮已经是「联系顾问」时，次要位不再重复。
 */
const secondaryAction = computed<MainAction | null>(() => {
    if (showAftersale.value) return { text: '订单售后', onTap: goAftersale }
    if (actions.value.contact_advisor && mainAction.value?.text !== '联系顾问') {
        return { text: '联系顾问', onTap: contactAdvisor }
    }
    return null
})

const barAmount = computed(() => {
    if (isTerminal.value) return null
    const need = payInfo.value?.need_pay_amount
    if (need !== undefined && need !== null && need !== '' && Number(need) > 0) return need
    return null
})

/* ---------------- 工具 ---------------- */

function imageUrl(url?: string) {
    if (!url) return ''
    if (/^https?:/.test(url)) return url
    const domain = (uni.getStorageSync('domain') as string) || ''
    return domain ? `${domain}${url}` : url
}

function goBack() {
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
        goBack()
        return
    }
    if (kind === 'login') navigate({ path: '/pages/login/login' })
}

function copySn() {
    const sn = String(order.value?.order_sn || '')
    if (!sn) return
    uni.setClipboardData({
        data: sn,
        success: () => toast.info('订单号已复制')
    })
}

/** 待办行点击：仅可交互项（待重选协作、已发放问卷）响应点击 */
function onTodoTap(todo: TodoView) {
    if (!todo.clickable) return
    if (todo.reselectOrderItemId) {
        const item = pendingReselectItems.value.find(
            (i) => Number(i.order_item_id) === Number(todo.reselectOrderItemId)
        )
        if (item) void openReselectPopup(item)
        return
    }
    if (todo.key === 'questionnaire') {
        goQuestionnaire()
    }
}

function goQuestionnaire() {
    const id = pendingQuestionnaire.value?.id
    if (!isQuestionnaireIssued.value || !id) {
        toast.info('待发放问卷，服务人员发放后将通知你填写')
        return
    }
    navigate({
        path: '/packages/pkg-service/pages/questionnaire/detail',
        query: { id }
    })
}

function goAftersale() {
    navigate({
        path: '/packages/pkg-service/pages/aftersale/index',
        query: { order_id: orderId.value }
    })
}

function goReview() {
    const item = pendingReview.value
    if (item && Number(item.id || 0) > 0) {
        navigate({
            path: '/packages/pkg-order/pages/review/publish',
            query: {
                order_item_id: Number(item.id),
                ...(item.staff_name ? { staff_name: encodeURIComponent(String(item.staff_name)) } : {}),
                ...(item.package_name ? { package_name: encodeURIComponent(String(item.package_name)) } : {})
            }
        })
        return
    }
    navigate({ path: '/packages/pkg-order/pages/review/list' })
}

function contactAdvisor() {
    navigate({
        path: '/pages/customer-service/customer-service',
        query: { scene: 'order', order_id: orderId.value }
    })
}

function goConsult() {
    navigate({
        path: '/pages/customer-service/customer-service',
        query: {
            scene: 'order',
            order_id: orderId.value,
            staff_id: firstItem.value?.staff?.id || ''
        }
    })
}

/* ---------------- 数据 ---------------- */

async function load() {
    loading.value = true
    error.value = false
    try {
        try {
            const res: any = await getOrderDetailV2(orderId.value)
            applyDetail(res)
        } catch (e) {
            // v1 降级：只有 actions 需要本地推导
            const legacy: any = await getOrderDetail({ id: orderId.value })
            if (!legacy || !legacy.id) throw new Error('empty')
            applyDetail(legacy, true)
        }
        startCountdown()
    } catch (e) {
        rawError.value = e
        error.value = true
        report('api_error', { endpoint: 'order.detail', message: '订单详情加载失败' })
    } finally {
        loading.value = false
        hasLoadedOnce.value = true
    }
}

function applyDetail(res: any, legacy = false) {
    raw.value = res
    order.value = res?.order || res || {}
    items.value = Array.isArray(res?.items) ? res.items : []
    payments.value = Array.isArray(res?.payments) ? res.payments : []
    payInfo.value = res?.pay || (legacy ? order.value : {})
    // can_user_refund / refundable_amount 在付款摘要里（v2 的 pay / payment，老接口在 order 上）
    payment.value = res?.payment || res?.order?.payment || res?.pay || (legacy ? order.value : {})
    offlineInfo.value = res?.offline || {}
    pendingQuestionnaire.value = res?.pending_questionnaire || null
    pendingReview.value = res?.pending_review || null
    serverMilestones.value = Array.isArray(res?.milestones) ? res.milestones : []
    remainSeconds.value = Number(res?.pay?.pay_remain_seconds ?? res?.pay_remain_seconds ?? 0)

    if (legacy || !res?.actions) {
        actions.value = deriveActionsLegacy(order.value)
        payReason.value = Number(order.value?.order_status) === 0 ? '待商家确认' : ''
    } else {
        actions.value = res.actions
        payReason.value = res.action_reasons?.pay || ''
    }
}

function deriveActionsLegacy(o: any): OrderActions & { review?: boolean; upload_voucher?: boolean } {
    const s = Number(o?.order_status)
    const offline = Number(o?.payment_channel ?? 1) === 2 || Number(o?.offline_collection_available ?? 0) === 1
    return {
        pay: s === 1 && Number(o?.need_pay_amount ?? 0) > 0 && !offline,
        cancel: (s === 0 || s === 1) && Number(o?.complete_time ?? 0) === 0 && !o?.receipt_pending,
        change_date: s === 2,
        pause: s === 2,
        review: s === 4,
        upload_voucher: s === 1 && Number(o?.need_pay_amount ?? 0) > 0 && offline && !o?.receipt_pending,
        contact_advisor: true
    }
}

function startCountdown() {
    stopCountdown()
    if (remainSeconds.value <= 0) return
    timer = setInterval(() => {
        remainSeconds.value = Math.max(0, remainSeconds.value - 1)
        if (remainSeconds.value === 0) stopCountdown()
    }, 1000)
}

function stopCountdown() {
    if (timer) {
        clearInterval(timer)
        timer = null
    }
}

/* ---------------- 支付 / 取消 ---------------- */

async function handleUploadVoucher() {
    if (paying.value || !orderId.value) return
    const paths = await chooseImages({ count: 1 })
    if (!paths.length) return
    paying.value = true
    try {
        const up: any = await uploadImage(paths[0])
        const voucherUrl = String(up?.url || up?.uri || up?.path || '').trim()
        if (!voucherUrl) {
            toast.error('凭证图片上传失败，请重试')
            return
        }
        await uploadPayVoucher({ id: orderId.value, voucher: voucherUrl })
        toast.success('凭证已提交，请等待审核')
        await load()
    } catch (e: any) {
        toast.error(e?.message || '上传凭证失败，请稍后重试')
    } finally {
        paying.value = false
    }
}

async function handlePay() {
    if (paying.value) return
    paying.value = true
    try {
        // 资金安全：重付前幂等回查，避免对已支付订单重复扣款
        try {
            const st: any = await getPayStatus({ from: 'order', order_id: orderId.value })
            const ps = Number(st?.pay_status ?? st?.payment?.pay_status ?? -1)
            if (ps === 1) {
                toast.info(FB.PAY_ALREADY_PAID)
                load()
                return
            }
            if (ps !== 0) {
                toast.error(FB.PAY_RECHECK_UNKNOWN)
                return
            }
        } catch {
            toast.error(FB.PAY_RECHECK_UNKNOWN)
            return
        }

        // 服务号提醒：支付状态校验已通过，只在创建预支付单前弹一次；
        // 暂停时直接退出，paying 由 finally 释放，不留一个转圈的支付键
        if (!(await remindBeforeOaAction())) return

        const pre = await prepay({ from: 'order', order_id: orderId.value, pay_way: 2 })
        if (!pre || !pre.config) throw new Error('预支付参数缺失')

        const result = await requestPayment(pre.config)
        if (result === 'cancel') {
            toast.info('已取消支付')
            return
        }
        if (result === 'fail') {
            toast.error('支付失败，请重试')
            return
        }

        // 结果判定交给支付结果页统一轮询，避免两处各写一套
        const amount = Number(payInfo.value?.need_pay_amount ?? 0) || ''
        uni.navigateTo({
            url:
                `/packages/pkg-order/pages/payment-result/payment-result?order_id=${orderId.value}` +
                `&payment_sn=${encodeURIComponent(pre.payment_sn || '')}&amount=${amount}`
        })
    } catch (e: any) {
        report('api_error', { endpoint: 'pay.prepay', message: e?.message || '支付异常' })
        toast.error(e?.message || '支付发起失败，请稍后重试')
    } finally {
        paying.value = false
    }
}

async function handleCancel() {
    const confirmed = await new Promise<boolean>((resolve) => {
        uni.showModal({
            title: '取消订单',
            content: '取消后档期将释放，确定取消吗？',
            success: (r) => resolve(!!r.confirm),
            fail: () => resolve(false)
        })
    })
    if (!confirmed) return
    try {
        await cancelOrder({ id: orderId.value })
        // 取消后待支付数归零，标脏下一次 refresh 立即回源
        badgeStore.invalidate()
        toast.info('已取消')
        load()
    } catch {
        // 请求层已提示
    }
}

onLoad((options: any) => {
    orderId.value = Number(options?.id || options?.order_id || 0)
    if (!orderId.value) {
        rawError.value = new Error('missing-order-id')
        error.value = true
        loading.value = false
        return
    }
    load()
})

onShow(() => {
    if (!hasLoadedOnce.value || !orderId.value) return
    void load()
})

onUnload(() => stopCountdown())
</script>

<style lang="scss" scoped>
/* ========== 骨架 ========== */
.sk {
    padding: var(--sp-3) 42rpx;

    &__hero {
        padding: var(--sp-5);
        border-radius: var(--r-md);
        background: var(--c-ink);
    }

    &__card {
        margin-top: var(--sp-3);
        padding: var(--sp-4);
        border-radius: var(--r-md);
        background: var(--c-surface);
    }
}

/* ========== Ord Hero（深色卡，全屏仅此 1 个） ========== */
.ohero {
    margin: 21rpx 42rpx 0;
    padding: 29rpx 31rpx 25rpx;
    border-radius: var(--r-md);
    position: relative;
    overflow: hidden;
    background: linear-gradient(145deg, var(--c-ink) 0%, var(--c-ink-2) 62%, var(--c-ink) 100%);

    &--muted {
        background: var(--c-neutral);
    }

    &__chip {
        display: inline-flex;
        align-items: center;
        gap: 10rpx;
        height: 38rpx;
        padding: 0 15rpx;
        border-radius: var(--r-xs);
        background: var(--c-brand-bg);
        border: 1rpx solid var(--c-primary-border);
    }

    &__dot {
        width: 8rpx;
        height: 8rpx;
        border-radius: var(--r-pill);
        background: var(--c-brand-text);
    }

    &__chip-text {
        font-size: var(--fs-18);
        letter-spacing: 1rpx;
        color: var(--c-brand-text);
        line-height: var(--lh-none);
    }

    /* 状态语义色：chip 是首屏唯一的「状态」信号，不能所有状态都一个金色 */
    &__chip.is-warn {
        background: var(--c-warning-bg);
        border-color: var(--c-warning-border);
    }
    &__chip.is-warn &__chip-text {
        color: var(--c-warning);
    }
    &__chip.is-warn &__dot {
        background: var(--c-warning);
    }

    &__chip.is-success {
        background: var(--c-success-bg);
        border-color: var(--c-success-border);
    }
    &__chip.is-success &__chip-text {
        color: var(--c-success);
    }
    &__chip.is-success &__dot {
        background: var(--c-success);
    }

    &__chip.is-muted {
        background: var(--c-neutral-bg);
        border-color: var(--c-neutral-border);
    }
    &__chip.is-muted &__chip-text {
        color: var(--c-neutral);
    }
    &__chip.is-muted &__dot {
        background: var(--c-neutral);
    }

    &__days {
        display: flex;
        align-items: baseline;
        gap: 6rpx;
        margin-top: 25rpx;
        color: var(--c-on-dark-2);
        font-size: var(--fs-25);
    }

    &__days-num {
        font-family: var(--font-serif);
        font-size: var(--fs-58);
        font-weight: var(--fw-medium);
        color: var(--c-on-dark);
    }

    &__days-fallback {
        display: block;
        margin-top: 25rpx;
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        color: var(--c-on-dark);
    }

    &__meta {
        display: block;
        margin-top: 12rpx;
        font-size: var(--fs-18);
        letter-spacing: 1rpx;
        color: var(--c-on-dark-2);
    }

    &__nums {
        display: flex;
        gap: 18rpx;
        margin-top: 27rpx;
        padding-top: 23rpx;
        border-top: 1rpx solid var(--c-on-dark-2);
    }

    &__num {
        flex: 1;
        min-width: 0;
    }

    &__num-k {
        display: block;
        font-size: var(--fs-18);
        letter-spacing: 1rpx;
        color: var(--c-on-dark-2);
    }

    &__num-v {
        display: block;
        margin-top: var(--sp-1);
        font-family: var(--font-serif);
        font-size: var(--fs-29);
        color: var(--c-brand-soft);
    }

    /* 状态解释：24rpx 是信息字号下限，不再用 18rpx 的标签档 */
    &__hint {
        display: block;
        margin-top: 10rpx;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-on-dark-2);
    }
}

/* ========== 待确认说明条 ========== */
.notice {
    display: flex;
    align-items: center;
    gap: 12rpx;
    margin: 21rpx 42rpx 0;
    padding: 18rpx 22rpx;
    border-radius: var(--r-sm);
    background: var(--c-warning-bg);
    border: 1rpx solid var(--c-warning-border);
    color: var(--c-warning);
    font-size: var(--fs-xs);
    line-height: var(--lh-body);

    &__ic {
        flex: none;
    }

    &__text {
        flex: 1;
        min-width: 0;
    }

    &__link {
        flex: none;
        padding: 12rpx 0;
        margin: -12rpx 0;
        text-decoration: underline;
        text-underline-offset: 3rpx;
    }
}

/* ========== 四段里程碑 ========== */
.ms {
    display: flex;
    position: relative;
    margin: 38rpx 46rpx 0;

    &::before {
        content: '';
        position: absolute;
        left: 19rpx;
        right: 19rpx;
        top: 10rpx;
        height: 1rpx;
        background: var(--c-line-strong);
    }

    &__i {
        flex: 1;
        text-align: center;
        position: relative;
        padding-top: 31rpx;

        &::before {
            content: '';
            position: absolute;
            /* top 保持 0：圆点 21rpx 时圆心 10.5rpx，正好被 .ms::before 的水平连接线（top:10rpx,高 1rpx）穿过。
               改 4rpx 会让圆心变 14.5rpx，连线从圆心上方掠过，四个里程碑看起来「点浮在线上」。 */
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 21rpx;
            height: 21rpx;
            border-radius: var(--r-pill);
            background: var(--c-surface);
            border: 1rpx solid var(--c-line-strong);
            box-sizing: border-box;
        }

        &.is-done::before {
            /* 批 1.5 ⑤：--c-brand(#c6a15b) 对白底仅 2.39:1（<3:1），改深金 5.26:1。
               注：01 §7.15「已完成 = --c-ink 实心」隐含「32rpx 圆 + 反白勾形」前提，
               本步骤圆是 21rpx 且无勾形，改墨黑会与「当前步」混，故保持金色可读档。 */
            background: var(--c-brand-text);
            border-color: var(--c-brand-text);
        }

        &.is-now::before {
            background: var(--c-ink);
            border-color: var(--c-ink);
            /* G1 ④（定稿）：当前步 = 墨黑实心 + 6rpx --c-ink-3 外环（单令牌，无底色间隙层）。
               环 vs 实心圆 3.13:1 —— 环的作用是「多一层形状」，不是高对比边框。
               净空 = (padding-top 31 − 圆点 21) − 环单边 6 = 4rpx，不压标签；
               环向上扩 6rpx 落在 .ms 的 margin: 38rpx 空白里，不裁切。 */
            box-shadow: 0 0 0 6rpx var(--c-ink-3);
        }
    }

    &__label {
        display: block;
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }

    &__i.is-now &__label {
        color: var(--c-ink);
        font-weight: var(--fw-semibold);
    }

    &__value {
        display: block;
        margin-top: 6rpx;
        font-family: var(--font-serif);
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }
}

/* ========== 卡片 ========== */
.card2 {
    margin: 21rpx 42rpx 0;
    padding: 27rpx 29rpx;
    background: var(--c-surface);
    border: 1rpx solid var(--c-line-strong);
    border-radius: var(--r-md);

    &__title {
        display: block;
        margin-bottom: var(--sp-2);
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }
}

.host {
    display: flex;
    align-items: flex-start;
    gap: 18rpx;
    margin-bottom: var(--sp-2);

    &__avatar {
        width: 74rpx;
        height: 74rpx;
        border-radius: var(--r-sm);
        background: var(--c-neutral-bg);
        flex: none;
    }

    &__body {
        flex: 1;
        min-width: 0;
    }

    &__name-row {
        display: flex;
        align-items: center;
        gap: 10rpx;
        min-width: 0;
    }

    &__name {
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        max-width: 240rpx;
    }

    &__badge {
        flex: none;
        height: 30rpx;
        padding: 0 10rpx;
        border-radius: var(--r-xs);
        border: 1rpx solid var(--c-primary-border);
        background: var(--c-brand-bg);
        color: var(--c-brand-text);
        font-size: var(--fs-18);
        line-height: 28rpx;
    }

    &__desc {
        display: block;
        margin-top: var(--sp-1);
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }

    &__chat {
        flex: none;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 88rpx;
        height: 88rpx;
        margin: -14rpx -14rpx 0 0;
        color: var(--c-ink-2);
    }
}

.svc {
    display: flex;
    align-items: flex-start;
    gap: 18rpx;
    padding: 17rpx 0;
    border-bottom: 1rpx solid var(--c-line);

    &:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    &__k {
        flex: none;
        width: 108rpx;
        font-size: var(--fs-20);
        color: var(--c-ink-3);
    }

    &__v {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-22);
        line-height: var(--lh-body);
        color: var(--c-ink);
    }
}

/* ========== 待办 ========== */
.nx {
    display: flex;
    align-items: flex-start;
    gap: 18rpx;
    padding: 17rpx 0;
    border-bottom: 1rpx solid var(--c-line);

    &:last-of-type {
        border-bottom: 0;
    }

    &__idx {
        flex: none;
        width: 32rpx;
        height: 32rpx;
        margin-top: 2rpx;
        border-radius: var(--r-pill);
        border: 1rpx solid var(--c-primary-border);
        background: var(--c-brand-bg);
        display: flex;
        align-items: center;
        justify-content: center;

        &.is-primary {
            background: var(--c-ink);
            border-color: var(--c-ink);
        }
    }

    &__idx-text {
        font-family: var(--font-serif);
        font-size: var(--fs-18);
        color: var(--c-brand-text);
        line-height: var(--lh-none);
    }

    &__idx.is-primary &__idx-text {
        color: var(--c-brand-soft);
    }

    &__body {
        flex: 1;
        min-width: 0;
    }

    &__title {
        display: block;
        font-size: var(--fs-22);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &__desc {
        display: block;
        margin-top: 6rpx;
        font-size: var(--fs-18);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }

    &__tag {
        flex: none;
        padding-top: 4rpx;
        font-family: var(--font-serif);
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }
}

.sn {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);
    margin-top: 21rpx;
    padding-top: 19rpx;
    border-top: 1rpx solid var(--c-line);

    &__text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }

    &__copy {
        flex: none;
        padding: 12rpx 0;
        margin: -12rpx 0;
        font-size: var(--fs-18);
        color: var(--c-ink-3);
        text-decoration: underline;
    }
}

/* ========== 金额明细 ========== */
.kv {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-3);
    padding: 10rpx 0;

    &__k {
        font-size: var(--fs-22);
        color: var(--c-ink-3);

        &--total {
            font-size: var(--fs-xs);
            color: var(--c-ink);
        }
    }

    &__v {
        font-family: var(--font-serif);
        font-size: var(--fs-25);
        color: var(--c-ink);
    }

    &__v--total {
        font-family: var(--font-serif);
        font-size: var(--fs-35);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &--total {
        margin-top: 12rpx;
        padding-top: 17rpx;
        border-top: 1rpx solid var(--c-line);
    }
}

.foot-space {
    height: var(--sp-4);
}

/* 取消订单：页尾文字链，与底栏主次操作分开 */
.cancel {
    display: flex;
    justify-content: center;
    padding: 32rpx 0 8rpx;

    &__text {
        padding: 16rpx 24rpx;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        text-decoration: underline;
        text-underline-offset: 3rpx;
    }
}

/* ========== 吸底栏 ========== */
.abb {
    display: flex;
    flex-direction: column;
    gap: 4rpx;
    min-width: 0;

    &__label {
        font-size: var(--fs-18);
        letter-spacing: 1rpx;
        color: var(--c-ink-3);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    &__amount {
        font-family: var(--font-serif);
        font-size: var(--fs-lg);
        color: var(--c-ink);
    }

    &__na {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.abtns {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
}

/* ========== 待重选协作提醒 ========== */
.reselect {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
    margin: 21rpx 42rpx 0;

    &__card {
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
        padding: 27rpx 29rpx;
        border: 1rpx solid var(--c-warning-border);
        border-radius: var(--r-md);
        background: var(--c-warning-bg);
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
        color: var(--c-warning);
    }

    &__meta,
    &__tip {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
    }

    &__actions {
        display: flex;
        gap: var(--sp-2);
        margin-top: var(--sp-2);
    }

    &__btn {
        flex: 1;
        min-width: 0;
    }
}

.nx--action .nx__tag {
    color: var(--c-brand-text);
    font-weight: var(--fw-medium);
}

/* ========== 重新挑选协作人员弹窗 ========== */
.rsheet {
    display: flex;
    flex-direction: column;
    padding: var(--sp-4) var(--page-gutter) calc(var(--sp-4) + var(--safe-bottom));
    border-radius: var(--r-lg) var(--r-lg) 0 0;
    background: var(--c-surface);

    &__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__title-wrap {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
    }

    &__title {
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__sub {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }

    &__close {
        flex: none;
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
        max-height: 56vh;
        margin-top: var(--sp-3);
    }

    &__pad {
        padding: var(--sp-2) 0;
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

.rcand-list {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
}

.rcand {
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    padding: var(--sp-3);
    border: 1rpx solid var(--c-line);
    border-radius: var(--r-md);
    background: var(--c-surface);

    &.is-selected {
        border-color: var(--c-brand-text);
        background: var(--c-brand-bg);
    }

    &.is-disabled {
        opacity: 0.55;
    }

    &__avatar {
        flex: none;
        width: 80rpx;
        height: 80rpx;
        border-radius: var(--r-pill);
        background: var(--c-surface-2);

        &--text {
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--c-brand-bg);
        }
    }

    &__initial {
        font-family: var(--font-serif);
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-text);
    }

    &__body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__name-row {
        display: flex;
        align-items: center;
        gap: var(--sp-1);
        min-width: 0;
    }

    &__name {
        max-width: 220rpx;
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__pkg {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &__desc {
        font-size: var(--fs-20);
        color: var(--c-ink-3);
    }

    &__meta {
        display: flex;
        gap: var(--sp-2);
        font-size: var(--fs-20);
        color: var(--c-ink-3);
        font-variant-numeric: tabular-nums;
    }

    &__right {
        flex: none;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: var(--sp-1);
    }

    &__price {
        font-family: var(--font-serif);
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        color: var(--c-brand-text);
    }

    &__sched {
        font-size: var(--fs-20);
        color: var(--c-danger);

        &.is-ok {
            color: var(--c-success);
        }
    }

    &__check {
        flex: none;
        width: 36rpx;
        height: 36rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1rpx solid var(--c-line-strong);
        border-radius: var(--r-pill);
        background: var(--c-surface);

        &.is-on {
            border-color: var(--c-ink);
            background: var(--c-ink);
        }
    }

    &__check-text {
        font-size: var(--fs-20);
        line-height: var(--lh-none);
        color: transparent;

        .rcand__check.is-on & {
            color: var(--c-on-dark);
        }
    }
}
</style>
