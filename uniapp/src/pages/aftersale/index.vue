<template>
    <view class="aftersale-container">
        <!-- 统计卡片 -->
        <view class="stat-cards">
            <view class="stat-card" @click="navigateTo('ticket')">
                <view class="stat-icon ticket-icon">
                    <tn-icon name="file-text" size="48rpx" color="#fff" />
                </view>
                <view class="stat-info">
                    <text class="stat-value">{{ statistics.ticket?.total || 0 }}</text>
                    <text class="stat-label">我的工单</text>
                </view>
                <view class="stat-badge" v-if="statistics.ticket?.pending > 0">
                    {{ statistics.ticket.pending }}
                </view>
            </view>
            <view class="stat-card" @click="navigateTo('complaint')">
                <view class="stat-icon complaint-icon">
                    <tn-icon name="warning" size="48rpx" color="#fff" />
                </view>
                <view class="stat-info">
                    <text class="stat-value">{{ statistics.complaint?.total || 0 }}</text>
                    <text class="stat-label">我的投诉</text>
                </view>
                <view class="stat-badge" v-if="statistics.complaint?.pending > 0">
                    {{ statistics.complaint.pending }}
                </view>
            </view>
            <view class="stat-card" @click="navigateTo('reshoot')">
                <view class="stat-icon reshoot-icon">
                    <tn-icon name="camera" size="48rpx" color="#fff" />
                </view>
                <view class="stat-info">
                    <text class="stat-value">{{ statistics.reshoot?.total || 0 }}</text>
                    <text class="stat-label">补拍申请</text>
                </view>
                <view class="stat-badge" v-if="statistics.reshoot?.pending > 0">
                    {{ statistics.reshoot.pending }}
                </view>
            </view>
            <view class="stat-card" @click="navigateTo('callback')">
                <view class="stat-icon callback-icon">
                    <tn-icon name="edit" size="48rpx" color="#fff" />
                </view>
                <view class="stat-info">
                    <text class="stat-value">{{ statistics.callback?.total || 0 }}</text>
                    <text class="stat-label">回访问卷</text>
                </view>
                <view class="stat-badge" v-if="statistics.callback?.pending > 0">
                    {{ statistics.callback.pending }}
                </view>
            </view>
        </view>

        <!-- 快捷入口 -->
        <view class="quick-actions">
            <view class="section-title">快捷服务</view>
            <view class="action-grid">
                <view class="action-item" @click="showCreateTicket">
                    <view class="action-icon">
                        <tn-icon name="add-circle" size="56rpx" color="#667eea" />
                    </view>
                    <text class="action-text">提交工单</text>
                </view>
                <view class="action-item" @click="showSubmitComplaint">
                    <view class="action-icon">
                        <tn-icon name="warning-circle" size="56rpx" color="#f5576c" />
                    </view>
                    <text class="action-text">我要投诉</text>
                </view>
                <view class="action-item" @click="showApplyReshoot">
                    <view class="action-icon">
                        <tn-icon name="camera" size="56rpx" color="#4facfe" />
                    </view>
                    <text class="action-text">申请补拍</text>
                </view>
                <view class="action-item" @click="contactService">
                    <view class="action-icon">
                        <tn-icon name="service" size="56rpx" color="#43e97b" />
                    </view>
                    <text class="action-text">联系客服</text>
                </view>
            </view>
        </view>

        <!-- 最近工单 -->
        <view class="recent-tickets" v-if="recentTickets.length > 0">
            <view class="section-title">
                <text>最近工单</text>
                <text class="more" @click="navigateTo('ticket')">查看全部</text>
            </view>
            <view class="ticket-list">
                <view
                    class="ticket-item"
                    v-for="item in recentTickets"
                    :key="item.id"
                    @click="viewTicket(item)"
                >
                    <view class="ticket-header">
                        <text class="ticket-sn">{{ item.ticket_sn }}</text>
                        <view class="ticket-status" :class="getStatusClass(item.status)">
                            {{ item.status_desc }}
                        </view>
                    </view>
                    <view class="ticket-title">{{ item.title }}</view>
                    <view class="ticket-footer">
                        <text class="ticket-type">{{ item.type_desc }}</text>
                        <text class="ticket-time">{{ item.create_time }}</text>
                    </view>
                </view>
            </view>
        </view>

        <!-- 常见问题 -->
        <view class="faq-section">
            <view class="section-title">常见问题</view>
            <view class="faq-list">
                <view
                    class="faq-item"
                    v-for="(item, index) in faqList"
                    :key="index"
                    @click="toggleFaq(index)"
                >
                    <view class="faq-question">
                        <text>{{ item.question }}</text>
                        <tn-icon :name="item.expanded ? 'up' : 'down'" size="32rpx" color="#999" />
                    </view>
                    <view class="faq-answer" v-if="item.expanded">
                        {{ item.answer }}
                    </view>
                </view>
            </view>
        </view>

        <!-- 提交工单弹窗 -->
        <tn-popup v-model="showTicketPopup" mode="bottom" :radius="24">
            <view class="popup-content">
                <view class="popup-header">
                    <text class="popup-title">提交工单</text>
                    <tn-icon name="close" size="40rpx" @click="showTicketPopup = false" />
                </view>
                <view class="popup-body">
                    <view class="form-item">
                        <text class="form-label">工单类型</text>
                        <view class="type-options">
                            <view
                                class="type-option"
                                :class="{ active: ticketForm.type === type.value }"
                                v-for="type in ticketTypes"
                                :key="type.value"
                                @click="ticketForm.type = type.value"
                            >
                                {{ type.label }}
                            </view>
                        </view>
                    </view>
                    <view class="form-item">
                        <text class="form-label">标题</text>
                        <input
                            class="form-input"
                            v-model="ticketForm.title"
                            placeholder="请输入标题"
                        />
                    </view>
                    <view class="form-item">
                        <text class="form-label">详细描述</text>
                        <textarea
                            class="form-textarea"
                            v-model="ticketForm.content"
                            placeholder="请详细描述您的问题"
                        />
                    </view>
                    <view class="form-item">
                        <text class="form-label">图片凭证</text>
                        <tn-image-upload
                            v-model="ticketForm.images"
                            :action="uploadUrl"
                            :maxCount="5"
                        />
                    </view>
                </view>
                <view class="popup-footer">
                    <tn-button type="primary" size="lg" :loading="submitting" @click="submitTicket">
                        提交工单
                    </tn-button>
                </view>
            </view>
        </tn-popup>

        <!-- 投诉弹窗 -->
        <tn-popup v-model="showComplaintPopup" mode="bottom" :radius="24">
            <view class="popup-content">
                <view class="popup-header">
                    <text class="popup-title">提交投诉</text>
                    <tn-icon name="close" size="40rpx" @click="showComplaintPopup = false" />
                </view>
                <view class="popup-body">
                    <view class="form-item">
                        <text class="form-label">投诉类型</text>
                        <view class="type-options">
                            <view
                                class="type-option"
                                :class="{ active: complaintForm.type === type.value }"
                                v-for="type in complaintTypes"
                                :key="type.value"
                                @click="complaintForm.type = type.value"
                            >
                                {{ type.label }}
                            </view>
                        </view>
                    </view>
                    <view class="form-item">
                        <text class="form-label">投诉等级</text>
                        <view class="type-options">
                            <view
                                class="type-option"
                                :class="{ active: complaintForm.level === level.value }"
                                v-for="level in complaintLevels"
                                :key="level.value"
                                @click="complaintForm.level = level.value"
                            >
                                {{ level.label }}
                            </view>
                        </view>
                    </view>
                    <view class="form-item">
                        <text class="form-label">投诉标题</text>
                        <input
                            class="form-input"
                            v-model="complaintForm.title"
                            placeholder="请输入投诉标题"
                        />
                    </view>
                    <view class="form-item">
                        <text class="form-label">详细描述</text>
                        <textarea
                            class="form-textarea"
                            v-model="complaintForm.content"
                            placeholder="请详细描述投诉内容"
                        />
                    </view>
                    <view class="form-item">
                        <text class="form-label">图片/视频凭证</text>
                        <tn-image-upload
                            v-model="complaintForm.images"
                            :action="uploadUrl"
                            :maxCount="9"
                        />
                    </view>
                </view>
                <view class="popup-footer">
                    <tn-button
                        type="primary"
                        size="lg"
                        :loading="submitting"
                        @click="submitComplaint"
                    >
                        提交投诉
                    </tn-button>
                </view>
            </view>
        </tn-popup>
    </view>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import {
    getMyStatistics,
    getTicketLists,
    createTicket,
    submitComplaint as submitComplaintApi
} from '@/api/aftersale'

// 统计数据
const statistics = ref<any>({})

// 最近工单
const recentTickets = ref<any[]>([])

// 弹窗控制
const showTicketPopup = ref(false)
const showComplaintPopup = ref(false)
const submitting = ref(false)

// 上传地址
const uploadUrl = '/api/upload/image'

// 工单类型
const ticketTypes = [
    { label: '咨询', value: 2 },
    { label: '售后', value: 3 },
    { label: '建议', value: 4 },
    { label: '其他', value: 5 }
]

// 投诉类型
const complaintTypes = [
    { label: '服务态度', value: 1 },
    { label: '专业能力', value: 2 },
    { label: '迟到早退', value: 3 },
    { label: '违规行为', value: 4 },
    { label: '其他', value: 5 }
]

// 投诉等级
const complaintLevels = [
    { label: '一般', value: 1 },
    { label: '严重', value: 2 },
    { label: '紧急', value: 3 }
]

// 表单数据
const ticketForm = reactive({
    type: 3,
    title: '',
    content: '',
    images: [] as string[]
})

const complaintForm = reactive({
    type: 1,
    level: 1,
    order_id: 0,
    staff_id: 0,
    title: '',
    content: '',
    images: [] as string[]
})

// FAQ列表
const faqList = ref([
    {
        question: '如何申请退款？',
        answer: '您可以在订单详情页面点击"申请退款"按钮，填写退款原因后提交申请，我们会在1-3个工作日内处理。',
        expanded: false
    },
    {
        question: '补拍/重拍需要额外付费吗？',
        answer: '如果是因为服务人员原因导致的效果不满意，我们将免费为您安排补拍。如果是个人原因，可能需要支付一定费用。',
        expanded: false
    },
    {
        question: '投诉后多久会有回复？',
        answer: '我们承诺在24小时内响应您的投诉，并在3个工作日内给出处理结果。紧急投诉会优先处理。',
        expanded: false
    },
    {
        question: '如何修改预约时间？',
        answer: '您可以在订单详情页面提交"改期申请"，提前3天申请可免费更改，临时更改可能需要支付差价。',
        expanded: false
    }
])

// 获取统计数据
const getStatistics = async () => {
    try {
        const res = await getMyStatistics()
        statistics.value = res
    } catch (error) {
        console.error(error)
    }
}

// 获取最近工单
const getRecentTickets = async () => {
    try {
        const res = await getTicketLists({ page: 1, limit: 3 })
        recentTickets.value = res.lists || []
    } catch (error) {
        console.error(error)
    }
}

// 页面导航
const navigateTo = (type: string) => {
    uni.navigateTo({
        url: `/pages/aftersale/${type}`
    })
}

// 查看工单详情
const viewTicket = (item: any) => {
    uni.navigateTo({
        url: `/pages/aftersale/ticket_detail?id=${item.id}`
    })
}

// 显示提交工单弹窗
const showCreateTicket = () => {
    ticketForm.type = 3
    ticketForm.title = ''
    ticketForm.content = ''
    ticketForm.images = []
    showTicketPopup.value = true
}

// 显示投诉弹窗
const showSubmitComplaint = () => {
    complaintForm.type = 1
    complaintForm.level = 1
    complaintForm.title = ''
    complaintForm.content = ''
    complaintForm.images = []
    showComplaintPopup.value = true
}

// 显示补拍申请
const showApplyReshoot = () => {
    uni.navigateTo({
        url: '/pages/aftersale/apply_reshoot'
    })
}

// 联系客服
const contactService = () => {
    // 调用微信客服
    // @ts-ignore
    if (uni.openCustomerServiceChat) {
        // @ts-ignore
        uni.openCustomerServiceChat({
            extInfo: { url: '' },
            corpId: '',
            success: () => {},
            fail: () => {
                uni.showToast({ title: '暂无法联系客服', icon: 'none' })
            }
        })
    } else {
        uni.makePhoneCall({
            phoneNumber: '400-xxx-xxxx',
            fail: () => {
                uni.showToast({ title: '拨打失败', icon: 'none' })
            }
        })
    }
}

// 提交工单
const submitTicket = async () => {
    if (!ticketForm.title) {
        uni.showToast({ title: '请输入标题', icon: 'none' })
        return
    }
    if (!ticketForm.content) {
        uni.showToast({ title: '请输入详细描述', icon: 'none' })
        return
    }

    submitting.value = true
    try {
        await createTicket(ticketForm)
        uni.showToast({ title: '提交成功', icon: 'success' })
        showTicketPopup.value = false
        getStatistics()
        getRecentTickets()
    } catch (error) {
        console.error(error)
    } finally {
        submitting.value = false
    }
}

// 提交投诉
const submitComplaint = async () => {
    if (!complaintForm.title) {
        uni.showToast({ title: '请输入投诉标题', icon: 'none' })
        return
    }
    if (!complaintForm.content) {
        uni.showToast({ title: '请输入详细描述', icon: 'none' })
        return
    }

    submitting.value = true
    try {
        await submitComplaintApi(complaintForm)
        uni.showToast({ title: '投诉提交成功', icon: 'success' })
        showComplaintPopup.value = false
        getStatistics()
    } catch (error) {
        console.error(error)
    } finally {
        submitting.value = false
    }
}

// 切换FAQ展开
const toggleFaq = (index: number) => {
    faqList.value[index].expanded = !faqList.value[index].expanded
}

// 获取状态样式类
const getStatusClass = (status: number) => {
    const map: any = {
        0: 'status-pending',
        1: 'status-processing',
        2: 'status-confirming',
        3: 'status-completed',
        4: 'status-closed'
    }
    return map[status] || ''
}

onMounted(() => {
    getStatistics()
    getRecentTickets()
})
</script>

<style lang="scss" scoped>
.aftersale-container {
    min-height: 100vh;
    background: #f5f6f8;
    padding-bottom: 40rpx;
}

.stat-cards {
    display: flex;
    flex-wrap: wrap;
    padding: 24rpx;
    gap: 24rpx;
}

.stat-card {
    width: calc(50% - 12rpx);
    background: #fff;
    border-radius: 16rpx;
    padding: 24rpx;
    display: flex;
    align-items: center;
    gap: 20rpx;
    position: relative;
    box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.05);
}

.stat-icon {
    width: 80rpx;
    height: 80rpx;
    border-radius: 16rpx;
    display: flex;
    align-items: center;
    justify-content: center;

    &.ticket-icon {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    &.complaint-icon {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }
    &.reshoot-icon {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }
    &.callback-icon {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    }
}

.stat-info {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.stat-value {
    font-size: 40rpx;
    font-weight: 600;
    color: #333;
}

.stat-label {
    font-size: 24rpx;
    color: #999;
    margin-top: 4rpx;
}

.stat-badge {
    position: absolute;
    top: 16rpx;
    right: 16rpx;
    background: #f56c6c;
    color: #fff;
    font-size: 20rpx;
    padding: 4rpx 12rpx;
    border-radius: 20rpx;
    min-width: 32rpx;
    text-align: center;
}

.quick-actions {
    background: #fff;
    margin: 0 24rpx;
    border-radius: 16rpx;
    padding: 24rpx;
}

.section-title {
    font-size: 32rpx;
    font-weight: 600;
    color: #333;
    margin-bottom: 24rpx;
    display: flex;
    justify-content: space-between;
    align-items: center;

    .more {
        font-size: 26rpx;
        font-weight: 400;
        color: #666;
    }
}

.action-grid {
    display: flex;
    justify-content: space-between;
}

.action-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 25%;
}

.action-icon {
    width: 96rpx;
    height: 96rpx;
    background: #f5f6f8;
    border-radius: 24rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12rpx;
}

.action-text {
    font-size: 24rpx;
    color: #666;
}

.recent-tickets {
    background: #fff;
    margin: 24rpx;
    border-radius: 16rpx;
    padding: 24rpx;
}

.ticket-list {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.ticket-item {
    background: #f9fafb;
    border-radius: 12rpx;
    padding: 20rpx;
}

.ticket-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12rpx;
}

.ticket-sn {
    font-size: 24rpx;
    color: #999;
}

.ticket-status {
    font-size: 22rpx;
    padding: 4rpx 16rpx;
    border-radius: 8rpx;

    &.status-pending {
        background: #f0f0f0;
        color: #666;
    }
    &.status-processing {
        background: #fff7e6;
        color: #fa8c16;
    }
    &.status-confirming {
        background: #e6f7ff;
        color: #1890ff;
    }
    &.status-completed {
        background: #f6ffed;
        color: #52c41a;
    }
    &.status-closed {
        background: #f5f5f5;
        color: #999;
    }
}

.ticket-title {
    font-size: 28rpx;
    color: #333;
    margin-bottom: 12rpx;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ticket-footer {
    display: flex;
    justify-content: space-between;
    font-size: 24rpx;
    color: #999;
}

.faq-section {
    background: #fff;
    margin: 24rpx;
    border-radius: 16rpx;
    padding: 24rpx;
}

.faq-list {
    display: flex;
    flex-direction: column;
}

.faq-item {
    border-bottom: 1rpx solid #f0f0f0;
    padding: 24rpx 0;

    &:last-child {
        border-bottom: none;
    }
}

.faq-question {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 28rpx;
    color: #333;
}

.faq-answer {
    margin-top: 16rpx;
    font-size: 26rpx;
    color: #666;
    line-height: 1.6;
}

.popup-content {
    padding: 32rpx;
}

.popup-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32rpx;
}

.popup-title {
    font-size: 36rpx;
    font-weight: 600;
    color: #333;
}

.popup-body {
    max-height: 60vh;
    overflow-y: auto;
}

.form-item {
    margin-bottom: 24rpx;
}

.form-label {
    display: block;
    font-size: 28rpx;
    color: #333;
    margin-bottom: 12rpx;
}

.form-input {
    width: 100%;
    height: 80rpx;
    background: #f5f6f8;
    border-radius: 12rpx;
    padding: 0 24rpx;
    font-size: 28rpx;
}

.form-textarea {
    width: 100%;
    height: 200rpx;
    background: #f5f6f8;
    border-radius: 12rpx;
    padding: 20rpx 24rpx;
    font-size: 28rpx;
}

.type-options {
    display: flex;
    flex-wrap: wrap;
    gap: 16rpx;
}

.type-option {
    padding: 12rpx 24rpx;
    background: #f5f6f8;
    border-radius: 8rpx;
    font-size: 26rpx;
    color: #666;

    &.active {
        background: #667eea;
        color: #fff;
    }
}

.popup-footer {
    margin-top: 32rpx;
}
</style>
