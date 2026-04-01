<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="售后服务" />

        <view class="aftersale-page">
            <view class="aftersale-page__hero">
                <text class="aftersale-page__eyebrow">Support Center</text>
                <text class="aftersale-page__title">售后服务中心</text>
                <text class="aftersale-page__desc">
                    统一处理工单、投诉、补拍申请和回访问卷，流程状态一目了然。
                </text>
            </view>

            <view class="aftersale-stats">
                <view class="aftersale-stat" @click="navigateToList('ticket')">
                    <text class="aftersale-stat__label">我的工单</text>
                    <text class="aftersale-stat__value">{{ statistics.ticket?.total || 0 }}</text>
                    <text class="aftersale-stat__meta">
                        待跟进 {{ statistics.ticket?.pending || 0 }}
                    </text>
                </view>
                <view class="aftersale-stat" @click="navigateToList('complaint')">
                    <text class="aftersale-stat__label">我的投诉</text>
                    <text class="aftersale-stat__value">{{ statistics.complaint?.total || 0 }}</text>
                    <text class="aftersale-stat__meta">
                        待处理 {{ statistics.complaint?.pending || 0 }}
                    </text>
                </view>
                <view class="aftersale-stat" @click="navigateToList('reshoot')">
                    <text class="aftersale-stat__label">补拍申请</text>
                    <text class="aftersale-stat__value">{{ statistics.reshoot?.total || 0 }}</text>
                    <text class="aftersale-stat__meta">
                        处理中 {{ statistics.reshoot?.pending || 0 }}
                    </text>
                </view>
                <view class="aftersale-stat" @click="navigateToList('callback')">
                    <text class="aftersale-stat__label">回访问卷</text>
                    <text class="aftersale-stat__value">{{ statistics.callback?.total || 0 }}</text>
                    <text class="aftersale-stat__meta">
                        待填写 {{ statistics.callback?.pending || 0 }}
                    </text>
                </view>
            </view>

            <view class="aftersale-section">
                <view class="aftersale-section__head">
                    <text class="aftersale-section__title">快捷处理</text>
                    <text class="aftersale-section__desc">常见售后流程从这里直接发起。</text>
                </view>
                <view class="action-grid">
                    <view class="action-item" @click="openTicketPopup">
                        <tn-icon name="file-text" size="34" color="#E85A4F" />
                        <text class="action-item__title">提交工单</text>
                        <text class="action-item__desc">咨询与售后问题</text>
                    </view>
                    <view class="action-item" @click="openComplaintPopup">
                        <tn-icon name="warning-circle" size="34" color="#E85A4F" />
                        <text class="action-item__title">发起投诉</text>
                        <text class="action-item__desc">反馈履约异常</text>
                    </view>
                    <view class="action-item" @click="showApplyReshoot">
                        <tn-icon name="camera" size="34" color="#E85A4F" />
                        <text class="action-item__title">申请补拍</text>
                        <text class="action-item__desc">重新安排服务</text>
                    </view>
                    <view class="action-item" @click="contactService">
                        <tn-icon name="service" size="34" color="#E85A4F" />
                        <text class="action-item__title">联系顾问</text>
                        <text class="action-item__desc">人工协助处理</text>
                    </view>
                </view>
            </view>

            <view v-if="recentTickets.length" class="aftersale-section">
                <view class="aftersale-section__head">
                    <text class="aftersale-section__title">最近工单</text>
                    <text class="aftersale-section__link" @click="navigateToList('ticket')">
                        查看全部
                    </text>
                </view>
                <view class="ticket-list">
                    <view
                        v-for="item in recentTickets"
                        :key="item.id"
                        class="ticket-item"
                        @click="viewTicket(item.id)"
                    >
                        <view class="ticket-item__head">
                            <text class="ticket-item__sn">{{ item.ticket_sn }}</text>
                            <view class="ticket-item__status">
                                {{ item.status_desc }}
                            </view>
                        </view>
                        <text class="ticket-item__title">{{ item.title }}</text>
                        <text v-if="item.content" class="ticket-item__content">{{ item.content }}</text>
                    </view>
                </view>
            </view>

            <view class="aftersale-section">
                <view class="aftersale-section__head">
                    <text class="aftersale-section__title">常见问题</text>
                    <text class="aftersale-section__desc">高频问题提前说明，降低沟通成本。</text>
                </view>
                <view class="faq-list">
                    <view
                        v-for="(item, index) in faqList"
                        :key="index"
                        class="faq-item"
                        @click="toggleFaq(index)"
                    >
                        <view class="faq-item__head">
                            <text class="faq-item__question">{{ item.question }}</text>
                            <tn-icon
                                :name="item.expanded ? 'up' : 'down'"
                                size="24"
                                color="#978B83"
                            />
                        </view>
                        <text v-if="item.expanded" class="faq-item__answer">{{ item.answer }}</text>
                    </view>
                </view>
            </view>
        </view>

        <tn-popup
            v-model="showTicketPopup"
            open-direction="bottom"
            :radius="28"
            safe-area-inset-bottom
        >
            <view class="popup-panel">
                <view class="popup-panel__head">
                    <text class="popup-panel__title">提交工单</text>
                    <tn-icon name="close" size="28" color="#978B83" @click="showTicketPopup = false" />
                </view>

                <view class="popup-panel__body">
                    <view class="popup-field">
                        <text class="popup-field__label">关联订单</text>
                        <view class="popup-picker" @click="showTicketOrderPicker = true">
                            <text :class="{ 'popup-picker__placeholder': !selectedTicketOrder }">
                                {{ selectedTicketOrder?.label || '可选，方便平台更快定位问题' }}
                            </text>
                            <tn-icon name="right" size="24" color="#B4ACA8" />
                        </view>
                    </view>

                    <view class="popup-field">
                        <text class="popup-field__label">工单类型</text>
                        <view class="popup-chip-group">
                            <view
                                v-for="type in ticketTypes"
                                :key="type.value"
                                class="popup-chip"
                                :class="{ 'is-active': ticketForm.type === type.value }"
                                @click="ticketForm.type = type.value"
                            >
                                {{ type.label }}
                            </view>
                        </view>
                    </view>

                    <view class="popup-field">
                        <text class="popup-field__label">标题</text>
                        <BaseInput v-model="ticketForm.title" placeholder="请输入问题标题" />
                    </view>

                    <view class="popup-field">
                        <text class="popup-field__label">详细描述</text>
                        <textarea
                            v-model="ticketForm.content"
                            class="popup-textarea"
                            placeholder="请详细描述您的问题，方便售后快速跟进"
                            maxlength="300"
                        />
                    </view>
                </view>

                <view class="popup-panel__footer">
                    <BaseButton block size="lg" :loading="submitting" @click="submitTicket">
                        提交工单
                    </BaseButton>
                </view>
            </view>
        </tn-popup>

        <tn-popup
            v-model="showComplaintPopup"
            open-direction="bottom"
            :radius="28"
            safe-area-inset-bottom
        >
            <view class="popup-panel">
                <view class="popup-panel__head">
                    <text class="popup-panel__title">发起投诉</text>
                    <tn-icon
                        name="close"
                        size="28"
                        color="#978B83"
                        @click="showComplaintPopup = false"
                    />
                </view>

                <view class="popup-panel__body">
                    <view class="popup-field">
                        <text class="popup-field__label">关联订单</text>
                        <view class="popup-picker" @click="showComplaintOrderPicker = true">
                            <text :class="{ 'popup-picker__placeholder': !selectedComplaintOrder }">
                                {{ selectedComplaintOrder?.label || '请选择订单' }}
                            </text>
                            <tn-icon name="right" size="24" color="#B4ACA8" />
                        </view>
                    </view>

                    <view class="popup-field">
                        <text class="popup-field__label">投诉类型</text>
                        <view class="popup-chip-group">
                            <view
                                v-for="type in complaintTypes"
                                :key="type.value"
                                class="popup-chip"
                                :class="{ 'is-active': complaintForm.type === type.value }"
                                @click="complaintForm.type = type.value"
                            >
                                {{ type.label }}
                            </view>
                        </view>
                    </view>

                    <view class="popup-field">
                        <text class="popup-field__label">严重程度</text>
                        <view class="popup-chip-group">
                            <view
                                v-for="level in complaintLevels"
                                :key="level.value"
                                class="popup-chip"
                                :class="{ 'is-active': complaintForm.level === level.value }"
                                @click="complaintForm.level = level.value"
                            >
                                {{ level.label }}
                            </view>
                        </view>
                    </view>

                    <view class="popup-field">
                        <text class="popup-field__label">投诉标题</text>
                        <BaseInput v-model="complaintForm.title" placeholder="请输入投诉标题" />
                    </view>

                    <view class="popup-field">
                        <text class="popup-field__label">详细描述</text>
                        <textarea
                            v-model="complaintForm.content"
                            class="popup-textarea"
                            placeholder="请描述具体问题、影响范围和期望结果"
                            maxlength="300"
                        />
                    </view>
                </view>

                <view class="popup-panel__footer">
                    <BaseButton block size="lg" :loading="submitting" @click="submitComplaint">
                        提交投诉
                    </BaseButton>
                </view>
            </view>
        </tn-popup>

        <tn-picker
            v-model="showTicketOrderPicker"
            mode="selector"
            :range="orderOptions"
            range-key="label"
            @confirm="onTicketOrderConfirm"
        />
        <tn-picker
            v-model="showComplaintOrderPicker"
            mode="selector"
            :range="orderOptions"
            range-key="label"
            @confirm="onComplaintOrderConfirm"
        />
    </PageShell>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseInput from '@/components/base/BaseInput.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import {
    getMyStatistics,
    getTicketLists,
    createTicket,
    submitComplaint as submitComplaintApi
} from '@/api/aftersale'
import { getOrderList } from '@/api/order'
import { useThemeStore } from '@/stores/theme'
import { onLoad, onShow } from '@dcloudio/uni-app'

const $theme = useThemeStore()
const statistics = ref<any>({})
const recentTickets = ref<any[]>([])
const orderOptions = ref<any[]>([])
const selectedTicketOrder = ref<any>(null)
const selectedComplaintOrder = ref<any>(null)

const showTicketPopup = ref(false)
const showComplaintPopup = ref(false)
const showTicketOrderPicker = ref(false)
const showComplaintOrderPicker = ref(false)
const submitting = ref(false)

const ticketTypes = [
    { label: '咨询', value: 2 },
    { label: '售后', value: 3 },
    { label: '建议', value: 4 },
    { label: '其他', value: 5 }
]

const complaintTypes = [
    { label: '服务态度', value: 1 },
    { label: '专业能力', value: 2 },
    { label: '迟到早退', value: 3 },
    { label: '违规行为', value: 4 },
    { label: '其他', value: 5 }
]

const complaintLevels = [
    { label: '一般', value: 1 },
    { label: '严重', value: 2 },
    { label: '紧急', value: 3 }
]

const ticketForm = reactive({
    order_id: 0,
    type: 3,
    title: '',
    content: ''
})

const complaintForm = reactive({
    order_id: 0,
    type: 1,
    level: 1,
    title: '',
    content: ''
})

const faqList = ref([
    {
        question: '提交售后工单后多久会有人处理？',
        answer: '系统会按照优先级自动分发，常规问题通常在 24 小时内响应。',
        expanded: false
    },
    {
        question: '补拍申请审核通过后怎么安排档期？',
        answer: '平台审核通过后会根据人员档期重新协调，安排结果会同步到申请详情中。',
        expanded: false
    },
    {
        question: '投诉处理完成后还能补充反馈吗？',
        answer: '可以在投诉详情页查看处理结果；如对结论仍有异议，可再次联系客服人工介入。',
        expanded: false
    }
])

const loadStatistics = async () => {
    try {
        statistics.value = await getMyStatistics()
    } catch (error) {
        console.error('获取售后统计失败', error)
    }
}

const loadRecentTickets = async () => {
    try {
        const res = await getTicketLists({ page: 1, limit: 3 })
        recentTickets.value = res?.lists || res?.data?.lists || []
    } catch (error) {
        console.error('获取最近工单失败', error)
    }
}

const loadOrders = async () => {
    try {
        const res = await getOrderList()
        const lists = res?.lists || res?.data?.lists || []
        orderOptions.value = lists.map((item: any) => ({
            value: item.id,
            label: item.order_sn,
            ...item
        }))
    } catch (error) {
        console.error('获取订单列表失败', error)
    }
}

const resetTicketForm = () => {
    ticketForm.order_id = 0
    ticketForm.type = 3
    ticketForm.title = ''
    ticketForm.content = ''
    selectedTicketOrder.value = null
}

const resetComplaintForm = () => {
    complaintForm.order_id = 0
    complaintForm.type = 1
    complaintForm.level = 1
    complaintForm.title = ''
    complaintForm.content = ''
    selectedComplaintOrder.value = null
}

const openTicketPopup = () => {
    resetTicketForm()
    showTicketPopup.value = true
}

const openComplaintPopup = () => {
    resetComplaintForm()
    showComplaintPopup.value = true
}

const onTicketOrderConfirm = (event: any) => {
    const index = Number(event?.detail?.value ?? event?.[0] ?? 0)
    const order = orderOptions.value[index]
    if (!order) return
    selectedTicketOrder.value = order
    ticketForm.order_id = Number(order.value || 0)
}

const onComplaintOrderConfirm = (event: any) => {
    const index = Number(event?.detail?.value ?? event?.[0] ?? 0)
    const order = orderOptions.value[index]
    if (!order) return
    selectedComplaintOrder.value = order
    complaintForm.order_id = Number(order.value || 0)
}

const submitTicket = async () => {
    if (!ticketForm.title.trim()) {
        return uni.showToast({ title: '请输入问题标题', icon: 'none' })
    }

    submitting.value = true
    try {
        await createTicket({
            ...ticketForm,
            title: ticketForm.title.trim(),
            content: ticketForm.content.trim()
        })
        uni.showToast({ title: '提交成功', icon: 'none' })
        showTicketPopup.value = false
        await Promise.all([loadStatistics(), loadRecentTickets()])
    } catch (error: any) {
        uni.showToast({ title: error?.message || error || '提交失败', icon: 'none' })
    } finally {
        submitting.value = false
    }
}

const submitComplaint = async () => {
    if (!complaintForm.order_id) {
        return uni.showToast({ title: '请选择关联订单', icon: 'none' })
    }
    if (!complaintForm.title.trim()) {
        return uni.showToast({ title: '请输入投诉标题', icon: 'none' })
    }

    submitting.value = true
    try {
        await submitComplaintApi({
            ...complaintForm,
            title: complaintForm.title.trim(),
            content: complaintForm.content.trim()
        })
        uni.showToast({ title: '投诉已提交', icon: 'none' })
        showComplaintPopup.value = false
        await loadStatistics()
    } catch (error: any) {
        uni.showToast({ title: error?.message || error || '提交失败', icon: 'none' })
    } finally {
        submitting.value = false
    }
}

const toggleFaq = (index: number) => {
    faqList.value[index].expanded = !faqList.value[index].expanded
}

const navigateToList = (type: 'ticket' | 'complaint' | 'reshoot' | 'callback') => {
    uni.navigateTo({
        url: `/packages/pages/aftersale/${type}`
    })
}

const viewTicket = (id: number) => {
    uni.navigateTo({
        url: `/packages/pages/aftersale/ticket_detail?id=${id}`
    })
}

const showApplyReshoot = () => {
    uni.navigateTo({
        url: '/packages/pages/aftersale/apply_reshoot'
    })
}

const contactService = () => {
    uni.navigateTo({
        url: '/packages/pages/customer_service/customer_service?scene=aftersale'
    })
}

const applyRouteAction = (action?: string) => {
    if (action === 'create_ticket') {
        openTicketPopup()
    }
    if (action === 'submit_complaint') {
        openComplaintPopup()
    }
}

onLoad((options: any) => {
    applyRouteAction(options?.action)
})

onShow(() => {
    Promise.all([loadStatistics(), loadRecentTickets(), loadOrders()])
})
</script>

<style lang="scss" scoped>
@import '../../../styles/aftersale.scss';

.aftersale-page {
    @include aftersale-page-base;
    padding: 0 20rpx 140rpx;
}

.aftersale-page__hero {
    padding: 16rpx 0 26rpx;
}

.aftersale-page__eyebrow {
    display: block;
    font-size: 22rpx;
    font-weight: 600;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: var(--wm-color-primary, #e85a4f);
}

.aftersale-page__title {
    display: block;
    margin-top: 14rpx;
    font-size: 46rpx;
    font-weight: 700;
    line-height: 1.18;
    color: var(--wm-text-primary, #1e2432);
}

.aftersale-page__desc {
    display: block;
    margin-top: 12rpx;
    font-size: 25rpx;
    line-height: 1.7;
    color: var(--wm-text-secondary, #7f7b78);
}

.aftersale-stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16rpx;
}

.aftersale-stat {
    @include aftersale-section-card;
}

.aftersale-stat__label {
    display: block;
    font-size: 24rpx;
    color: var(--wm-text-secondary, #7f7b78);
}

.aftersale-stat__value {
    display: block;
    margin-top: 16rpx;
    font-size: 46rpx;
    font-weight: 700;
    line-height: 1;
    color: var(--wm-text-primary, #1e2432);
}

.aftersale-stat__meta {
    display: block;
    margin-top: 16rpx;
    font-size: 22rpx;
    color: var(--wm-color-primary, #e85a4f);
}

.aftersale-section {
    @include aftersale-section-card;
    margin-top: 18rpx;
}

.aftersale-section__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16rpx;
    margin-bottom: 20rpx;
}

.aftersale-section__title {
    font-size: 30rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.aftersale-section__desc,
.aftersale-section__link {
    font-size: 22rpx;
    line-height: 1.6;
    color: var(--wm-text-secondary, #7f7b78);
}

.aftersale-section__link {
    color: var(--wm-color-primary, #e85a4f);
}

.action-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14rpx;
}

.action-item {
    padding: 22rpx 18rpx;
    border-radius: 20rpx;
    background: var(--wm-color-bg-soft, #fff7f4);
}

.action-item__title {
    display: block;
    margin-top: 14rpx;
    font-size: 28rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.action-item__desc {
    display: block;
    margin-top: 8rpx;
    font-size: 22rpx;
    line-height: 1.6;
    color: var(--wm-text-secondary, #7f7b78);
}

.ticket-list {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
}

.ticket-item {
    padding: 20rpx;
    border-radius: 18rpx;
    background: rgba(255, 247, 244, 0.78);
}

.ticket-item__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12rpx;
}

.ticket-item__sn {
    font-size: 22rpx;
    color: var(--wm-text-tertiary, #b4aca8);
}

.ticket-item__status {
    padding: 6rpx 14rpx;
    border-radius: 999rpx;
    background: rgba(201, 133, 36, 0.12);
    font-size: 22rpx;
    color: #c98524;
}

.ticket-item__title {
    display: block;
    margin-top: 12rpx;
    font-size: 28rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.ticket-item__content {
    display: block;
    margin-top: 8rpx;
    font-size: 24rpx;
    line-height: 1.6;
    color: var(--wm-text-secondary, #7f7b78);
}

.faq-list {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
}

.faq-item {
    padding: 20rpx;
    border-radius: 18rpx;
    background: rgba(255, 247, 244, 0.78);
}

.faq-item__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.faq-item__question {
    font-size: 26rpx;
    line-height: 1.5;
    color: var(--wm-text-primary, #1e2432);
}

.faq-item__answer {
    display: block;
    margin-top: 12rpx;
    font-size: 24rpx;
    line-height: 1.7;
    color: var(--wm-text-secondary, #7f7b78);
}

.popup-panel {
    padding: 24rpx 20rpx 28rpx;
    background: var(--wm-color-bg-page, #fcfbf9);
}

.popup-panel__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12rpx;
}

.popup-panel__title {
    font-size: 32rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.popup-panel__body {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
    margin-top: 20rpx;
}

.popup-panel__footer {
    margin-top: 24rpx;
}

.popup-field {
    display: flex;
    flex-direction: column;
    gap: 10rpx;
}

.popup-field__label {
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-text-secondary, #7f7b78);
}

.popup-picker {
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 88rpx;
    padding: 0 20rpx;
    border-radius: 18rpx;
    border: 1rpx solid $aftersale-border;
    background: rgba(255, 255, 255, 0.82);
    font-size: 28rpx;
    color: var(--wm-text-primary, #1e2432);
}

.popup-picker__placeholder {
    color: var(--wm-text-tertiary, #b4aca8);
}

.popup-chip-group {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
}

.popup-chip {
    padding: 12rpx 18rpx;
    border-radius: 999rpx;
    border: 1rpx solid $aftersale-border;
    background: rgba(255, 255, 255, 0.8);
    font-size: 24rpx;
    color: var(--wm-text-secondary, #7f7b78);
}

.popup-chip.is-active {
    color: #ffffff;
    background: var(--wm-color-primary, #e85a4f);
    border-color: var(--wm-color-primary, #e85a4f);
}

.popup-textarea {
    min-height: 188rpx;
    padding: 20rpx;
    border-radius: 18rpx;
    border: 1rpx solid $aftersale-border;
    background: rgba(255, 255, 255, 0.82);
    font-size: 26rpx;
    line-height: 1.7;
    color: var(--wm-text-primary, #1e2432);
    box-sizing: border-box;
}
</style>
