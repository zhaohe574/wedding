<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" tone="form" hasSafeBottom>
        <BaseNavbar
            title="提交工单"
            variant="solid"
            bg-color="#191713"
            text-color="#FFFDF8"
        />

        <view class="aftersale-create-page">
            <view class="aftersale-create-page__wrapper wm-page-content">
                <BaseCard class="aftersale-create-card" variant="surface" scene="consumer">
                    <view class="aftersale-create-section">
                        <view class="aftersale-create-section__head">
                            <text class="aftersale-create-section__title">基础信息</text>
                        </view>

                        <view class="aftersale-field-block">
                            <text class="aftersale-inline-field__title">问题分类</text>
                            <view class="aftersale-object-grid">
                                <view
                                    v-for="item in ticketCategories"
                                    :key="item.label"
                                    class="aftersale-object-chip"
                                    :class="{ 'is-active': form.category === item.label }"
                                    @click="selectCategory(item.label)"
                                >
                                    {{ item.label }}
                                </view>
                            </view>
                        </view>

                        <view class="aftersale-field-block">
                            <text class="aftersale-inline-field__title">关联订单</text>
                            <view
                                class="aftersale-create-panel"
                                :class="{ 'is-selected': selectedOrder }"
                                @click="openOrderPicker"
                            >
                                <view class="aftersale-create-panel__copy">
                                    <text
                                        class="aftersale-create-panel__text text-ellipsis"
                                        :class="{ 'is-placeholder': !selectedOrder }"
                                    >
                                        {{ selectedOrderTitle || '可选关联订单' }}
                                    </text>
                                    <text
                                        v-if="selectedOrderMeta"
                                        class="aftersale-create-panel__meta text-ellipsis"
                                    >
                                        {{ selectedOrderMeta }}
                                    </text>
                                </view>
                                <BaseIcon
                                    name="right"
                                    size="22"
                                    color="var(--wm-text-tertiary, #9A9388)"
                                />
                            </view>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard class="aftersale-create-card" variant="surface" scene="consumer">
                    <view class="aftersale-create-section">
                        <view class="aftersale-create-section__head">
                            <text class="aftersale-create-section__title">问题描述</text>
                        </view>
                        <textarea
                            v-model="form.content"
                            class="aftersale-create-textarea"
                            maxlength="500"
                            placeholder="简述问题"
                            placeholder-style="color: #9A9388;"
                        />

                        <view class="aftersale-inline-field">
                            <text class="aftersale-inline-field__title">处理优先级</text>
                            <view class="aftersale-level-list">
                                <view
                                    v-for="item in priorityOptions"
                                    :key="item.value"
                                    class="aftersale-level-chip"
                                    :class="{ 'is-active': form.priority === item.value }"
                                    @click="form.priority = item.value"
                                >
                                    {{ item.label }}
                                </view>
                            </view>
                        </view>

                        <view class="aftersale-inline-field">
                            <text class="aftersale-inline-field__title">希望平台协助</text>
                            <input
                                v-model="form.assist_focus"
                                class="aftersale-create-input"
                                maxlength="60"
                                placeholder="如：确认排期、补发素材"
                                placeholder-style="color: #9A9388;"
                            />
                        </view>
                    </view>
                </BaseCard>

                <BaseCard class="aftersale-create-card" variant="surface" scene="consumer">
                    <view class="aftersale-create-section">
                        <view class="aftersale-create-section__head">
                            <text class="aftersale-create-section__title">上传凭证</text>
                        </view>
                        <AfterSaleMediaUploader
                            v-model="form.images"
                            variant="ticket-evidence"
                            kind="image"
                            add-text="+ 上传"
                            :entry-labels="['上传', '现场照片', '聊天记录']"
                            :max="6"
                            @uploading-change="uploading = $event"
                        />
                    </view>
                </BaseCard>

                <BaseCard class="aftersale-create-card" variant="surface" scene="consumer">
                    <view class="aftersale-create-section">
                        <view class="aftersale-create-section__head">
                            <text class="aftersale-create-section__title">联系方式</text>
                        </view>
                        <view class="aftersale-contact-panel">
                            <text class="aftersale-contact-panel__label">联系人</text>
                            <input
                                v-model="form.contact_name"
                                class="aftersale-contact-panel__input"
                                maxlength="20"
                                placeholder="请输入联系人姓名"
                                placeholder-style="color: #9A9388;"
                            />
                        </view>
                        <view class="aftersale-contact-panel">
                            <text class="aftersale-contact-panel__label">手机号</text>
                            <input
                                v-model="form.contact_phone"
                                class="aftersale-contact-panel__input"
                                type="number"
                                maxlength="11"
                                placeholder="请输入联系电话"
                                placeholder-style="color: #9A9388;"
                            />
                        </view>
                    </view>
                </BaseCard>

            </view>
        </view>

        <ActionArea class="aftersale-create-page__action-bar" sticky safeBottom>
            <BaseButton
                block
                size="lg"
                :disabled="submitDisabled"
                :loading="submitting"
                @click="handleSubmit"
            >
                提交工单
            </BaseButton>
        </ActionArea>

        <AfterSaleOrderPicker
            v-model="showOrderPicker"
            :orders="orderOptions"
            :selected-value="selectedOrderValue"
            @confirm="onOrderConfirm"
        />
    </PageShell>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { createTicket, getAftersaleOrderList } from '@/packages/common/api/aftersale'
import ActionArea from '@/components/base/ActionArea.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import { ClientEnum } from '@/enums/appEnums'
import { useThemeStore } from '@/stores/theme'
import { useUserStore } from '@/stores/user'
import { onLoad } from '@dcloudio/uni-app'
import { client } from '@/utils/client'
import { confirmModal, showError, showSuccess } from '@/utils/feedback'
import { subscribeAfterSaleScenes } from '@/packages/common/utils/subscribe'
import AfterSaleMediaUploader from './components/AfterSaleMediaUploader.vue'
import AfterSaleOrderPicker from './components/AfterSaleOrderPicker.vue'
import {
    extractOrderList,
    getOrderDisplayInfo,
    toOrderOptions,
    type OrderOption
} from './shared'

interface TicketCategoryItem {
    label: string
    type: number
}

interface PriorityOptionItem {
    value: number
    label: string
}

const $theme = useThemeStore()
const userStore = useUserStore()
const showOrderPicker = ref(false)
const uploading = ref(false)
const submitting = ref(false)
const orderLoading = ref(false)
const orderLoaded = ref(false)
const orderOptions = ref<any[]>([])
const selectedOrderValue = ref<number | string>('')
const selectedOrder = ref<any>(null)
let orderLoadingPromise: Promise<boolean> | null = null

const ticketCategories: TicketCategoryItem[] = [
    { label: '素材交付', type: 3 },
    { label: '流程确认', type: 2 },
    { label: '档期协调', type: 3 },
    { label: '服务建议', type: 4 },
    { label: '其他问题', type: 5 }
]

const priorityOptions: PriorityOptionItem[] = [
    { value: 1, label: '低' },
    { value: 2, label: '中' },
    { value: 3, label: '高' },
    { value: 4, label: '紧急' }
]

const form = reactive({
    order_id: 0,
    category: '素材交付',
    priority: 2,
    content: '',
    assist_focus: '',
    images: [] as string[],
    contact_name: '',
    contact_phone: ''
})

const selectedCategory = computed(
    () => ticketCategories.find((item) => item.label === form.category) || ticketCategories[0]
)
const selectedPriority = computed(
    () => priorityOptions.find((item) => item.value === form.priority) || priorityOptions[1]
)
const submitDisabled = computed(() => submitting.value || uploading.value)
const selectedOrderDisplay = computed(() =>
    selectedOrder.value ? getOrderDisplayInfo(selectedOrder.value) : null
)
const selectedOrderTitle = computed(() => selectedOrderDisplay.value?.title || '')
const selectedOrderMeta = computed(() => {
    if (!selectedOrderDisplay.value) {
        return ''
    }
    return selectedOrderDisplay.value.subtitle || selectedOrderDisplay.value.meta
})

const normalizeText = (value: string) => value.replace(/\s+/g, ' ').trim()
const isValidMobile = (mobile: string) => /^1[3-9]\d{9}$/.test(mobile)

const buildTicketTitle = (summary: string) => {
    const prefix = selectedCategory.value?.label || '其他问题'
    const headline = summary.slice(0, 18) || '待补充问题描述'
    const suffix = summary.length > 18 ? '…' : ''
    return `${prefix}｜${headline}${suffix}`
}

const buildTicketContent = () => {
    const summary = normalizeText(form.content)
    const assistFocus = normalizeText(form.assist_focus)
    if (!assistFocus) {
        return summary
    }
    return `${summary}\n\n希望平台协助：${assistFocus}`
}

const fillContactDefaults = () => {
    const realName = String(userStore.userInfo?.real_name || '').trim()
    const mobile = String(userStore.userInfo?.mobile || '').trim()

    if (!form.contact_name) {
        form.contact_name = realName
    }

    if (!form.contact_phone) {
        form.contact_phone = mobile
    }
}

const syncSelectedOrder = () => {
    if (!form.order_id) {
        selectedOrder.value = null
        selectedOrderValue.value = ''
        return
    }

    const order =
        orderOptions.value.find((item: any) => Number(item.value) === Number(form.order_id)) ||
        null
    selectedOrder.value = order
    selectedOrderValue.value = order?.value || form.order_id
}

const loadOrders = async () => {
    if (orderLoadingPromise) {
        return orderLoadingPromise
    }

    orderLoadingPromise = (async () => {
        orderLoading.value = true
        try {
            const res = await getAftersaleOrderList()
            const lists = extractOrderList(res)
            orderOptions.value = toOrderOptions(lists)
            orderLoaded.value = true
            syncSelectedOrder()
            return true
        } catch (error) {
            console.error('获取订单列表失败', error)
            orderLoaded.value = false
            showError('订单加载失败')
            return false
        } finally {
            orderLoading.value = false
            orderLoadingPromise = null
        }
    })()

    return orderLoadingPromise
}

const openOrderPicker = async () => {
    if (form.order_id) {
        selectedOrderValue.value = form.order_id
    }

    if (orderLoading.value) {
        showError('订单加载中，请稍候')
    }

    const loaded = orderLoaded.value && orderOptions.value.length ? true : await loadOrders()
    if (!loaded) {
        return
    }

    if (!orderOptions.value.length) {
        showError('暂无可关联订单')
        return
    }

    showOrderPicker.value = true
}

const onOrderConfirm = (order: OrderOption) => {
    selectedOrder.value = order
    selectedOrderValue.value = order.value
    form.order_id = Number(order.value || 0)
}

const selectCategory = (label: string) => {
    form.category = label
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

const handleSubmit = async () => {
    if (submitDisabled.value) {
        return
    }

    if (uploading.value) {
        showError('请等待附件上传完成')
        return
    }

    const summary = normalizeText(form.content)
    if (!summary) {
        showError('请填写问题描述')
        return
    }

    const contactName = String(form.contact_name || '').trim()
    const contactPhone = String(form.contact_phone || '').trim()
    if (!contactName) {
        showError('请输入联系人姓名')
        return
    }
    if (!contactPhone) {
        showError('请输入联系电话')
        return
    }
    if (!isValidMobile(contactPhone)) {
        showError('请输入正确的联系电话')
        return
    }

    submitting.value = true
    try {
        await promptAfterSaleSubscribe()
        await createTicket({
            order_id: form.order_id,
            type: Number(selectedCategory.value?.type || 3),
            priority: Number(selectedPriority.value?.value || 2),
            title: buildTicketTitle(summary),
            content: buildTicketContent(),
            images: form.images,
            contact_name: contactName,
            contact_phone: contactPhone
        })
        showSuccess('工单已提交')
        setTimeout(() => {
            uni.redirectTo({ url: '/packages/pages/aftersale/ticket' })
        }, 500)
    } catch (error: any) {
        showError(error, '提交失败')
    } finally {
        submitting.value = false
    }
}

watch(
    () => [userStore.userInfo?.real_name, userStore.userInfo?.mobile],
    () => {
        fillContactDefaults()
    },
    { immediate: true }
)

onLoad((options: any) => {
    if (options?.order_id) {
        form.order_id = Number(options.order_id)
    }
    if (options?.type) {
        const nextType = Number(options.type) || 3
        const matched = ticketCategories.find((item) => item.type === nextType)
        if (matched) {
            form.category = matched.label
        }
    }
    if (!String(userStore.userInfo?.mobile || '').trim() && userStore.isLogin) {
        void userStore.getUser()
    }
    fillContactDefaults()
    void loadOrders()
})
</script>

<style lang="scss" scoped>
@import '../../../styles/aftersale.scss';

.aftersale-create-page {
    @include aftersale-page-base;
    min-height: 100vh;
}

.aftersale-create-page__wrapper {
    @include aftersale-page-wrapper-with-action;
    gap: 16rpx;
    padding-top: 16rpx;
}

.aftersale-create-card {
    display: block;
    padding: 26rpx !important;
    border-radius: 28rpx !important;
    border-color: rgba(216, 201, 173, 0.9) !important;
    box-shadow: 0 12rpx 28rpx rgba(74, 43, 24, 0.06) !important;
}

.aftersale-create-section {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.aftersale-create-section__title {
    font-size: 29rpx;
    line-height: 1.3;
    font-weight: 900;
    color: var(--wm-text-primary, #111111);
}

.aftersale-create-section__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    min-height: 38rpx;
}

.aftersale-field-block {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
}

.aftersale-object-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12rpx;
}

.aftersale-object-chip {
    @include aftersale-choice-chip;
    width: 100%;
    min-height: 70rpx;
    padding: 0 18rpx;
    line-height: 1.4;
    font-weight: 800;
    text-align: center;

    &.is-active {
        @include aftersale-choice-chip-active;
        border-color: var(--wm-color-champagne, #d9be82);
    }
}

.aftersale-create-panel,
.aftersale-create-input,
.aftersale-contact-panel {
    @include aftersale-form-surface;
}

.aftersale-create-panel {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14rpx;
    min-height: 88rpx;
    padding: 16rpx 22rpx;
}

.aftersale-create-panel.is-selected {
    border-color: rgba(216, 194, 138, 0.96);
    background: rgba(255, 253, 248, 0.96);
}

.aftersale-create-panel__copy {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 4rpx;
}

.aftersale-create-panel__text {
    min-width: 0;
    display: block;
    font-size: 26rpx;
    line-height: 1.5;
    font-weight: 600;
    color: var(--wm-text-primary, #111111);
}

.aftersale-create-panel__text.is-placeholder {
    color: var(--wm-text-tertiary, #9a9388);
}

.aftersale-create-panel__meta {
    flex: 1;
    min-width: 0;
    display: block;
    font-size: 22rpx;
    line-height: 1.4;
    color: var(--wm-text-tertiary, #9a9388);
}

.aftersale-create-textarea {
    width: 100%;
    min-height: 190rpx;
    padding: 22rpx;
    @include aftersale-form-surface;
    font-size: 26rpx;
    line-height: 1.7;
    color: var(--wm-text-primary, #111111);
}

.aftersale-inline-field {
    display: flex;
    flex-direction: column;
    gap: 10rpx;
}

.aftersale-inline-field__title {
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-text-secondary, #5f5a50);
}

.aftersale-level-list {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -6rpx -12rpx;
}

.aftersale-level-chip {
    @include aftersale-choice-chip;
    min-width: 122rpx;
    min-height: 58rpx;
    margin: 0 6rpx 12rpx;
    padding: 0 20rpx;
    font-size: 22rpx;
    font-weight: 800;

    &.is-active {
        @include aftersale-choice-chip-active;
        border-color: var(--wm-color-champagne, #d9be82);
    }
}

.aftersale-create-input {
    width: 100%;
    height: 88rpx;
    min-height: 88rpx;
    padding: 0 22rpx;
    font-size: 26rpx;
    color: var(--wm-text-primary, #111111);
}

.aftersale-contact-panel {
    min-height: 88rpx;
    padding: 0 22rpx;
    display: flex;
    align-items: center;
    gap: 16rpx;
}

.aftersale-contact-panel__label {
    flex-shrink: 0;
    width: 112rpx;
    font-size: 26rpx;
    font-weight: 800;
    color: var(--wm-text-primary, #111111);
}

.aftersale-contact-panel__input {
    flex: 1;
    min-width: 0;
    height: 88rpx;
    font-size: 26rpx;
    color: var(--wm-text-primary, #111111);
}

@media screen and (max-width: 360px) {
    .aftersale-create-card {
        padding: 24rpx 22rpx !important;
    }

    .aftersale-object-grid {
        grid-template-columns: 1fr;
    }

    .aftersale-contact-panel {
        align-items: flex-start;
        flex-direction: column;
        padding: 18rpx 20rpx;
        gap: 10rpx;
    }

    .aftersale-contact-panel__label {
        width: auto;
    }

    .aftersale-contact-panel__input {
        width: 100%;
        height: 58rpx;
    }
}
</style>
