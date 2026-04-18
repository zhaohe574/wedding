<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="提交工单" />

        <view class="aftersale-create-page">
            <view class="aftersale-create-page__wrapper wm-page-content">
                <BaseCard
                    variant="surface"
                    scene="consumer"
                    padding="24rpx 26rpx"
                    border-radius="44rpx"
                >
                    <view class="aftersale-create-section">
                        <text class="aftersale-create-section__title">问题分类</text>
                        <view
                            v-for="(row, rowIndex) in categoryRows"
                            :key="`category-row-${rowIndex}`"
                            class="aftersale-object-row"
                        >
                            <view
                                v-for="item in row"
                                :key="item.label"
                                class="aftersale-object-chip"
                                :class="{ 'is-active': form.category === item.label }"
                                @click="selectCategory(item.label)"
                            >
                                {{ item.label }}
                            </view>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard
                    variant="surface"
                    scene="consumer"
                    padding="24rpx 26rpx"
                    border-radius="44rpx"
                >
                    <view class="aftersale-create-section">
                        <view class="aftersale-create-section__head">
                            <text class="aftersale-create-section__title">关联订单</text>
                            <text class="aftersale-create-section__meta">选填</text>
                        </view>
                        <view class="aftersale-create-panel" @click="showOrderPicker = true">
                            <text
                                class="aftersale-create-panel__text"
                                :class="{ 'is-placeholder': !selectedOrder }"
                            >
                                {{ selectedOrder?.label || '可选关联订单' }}
                            </text>
                            <tn-icon
                                name="right"
                                size="22"
                                color="var(--wm-text-tertiary, #B4ACA8)"
                            />
                        </view>
                    </view>
                </BaseCard>

                <BaseCard
                    variant="surface"
                    scene="consumer"
                    padding="24rpx 26rpx"
                    border-radius="44rpx"
                >
                    <view class="aftersale-create-section">
                        <view
                            class="aftersale-create-section__head aftersale-create-section__head--stack"
                        >
                            <text class="aftersale-create-section__title">问题描述</text>
                            <text class="aftersale-create-section__meta">
                                标题预览：{{ previewTitle }}
                            </text>
                        </view>
                        <textarea
                            v-model="form.content"
                            class="aftersale-create-textarea"
                            maxlength="500"
                            placeholder="简述问题"
                            placeholder-style="color: #B4ACA8;"
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
                            <text class="aftersale-inline-field__hint">{{ priorityHint }}</text>
                        </view>

                        <view class="aftersale-inline-field">
                            <text class="aftersale-inline-field__title">希望平台协助</text>
                            <input
                                v-model="form.assist_focus"
                                class="aftersale-create-input"
                                maxlength="60"
                                placeholder="如：确认排期、补发素材"
                                placeholder-style="color: #B4ACA8;"
                            />
                        </view>
                    </view>
                </BaseCard>

                <BaseCard
                    variant="surface"
                    scene="consumer"
                    padding="24rpx 26rpx"
                    border-radius="44rpx"
                >
                    <view class="aftersale-create-section">
                        <view
                            class="aftersale-create-section__head aftersale-create-section__head--stack"
                        >
                            <text class="aftersale-create-section__title">上传凭证</text>
                            <text class="aftersale-create-section__meta">
                                可上传凭证，最多 6 张。
                            </text>
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

                <BaseCard
                    variant="surface"
                    scene="consumer"
                    padding="24rpx 26rpx"
                    border-radius="44rpx"
                >
                    <view class="aftersale-create-section">
                        <view
                            class="aftersale-create-section__head aftersale-create-section__head--stack"
                        >
                            <text class="aftersale-create-section__title">联系方式</text>
                            <text class="aftersale-create-section__meta"> 平台会优先联系此人 </text>
                        </view>
                        <view class="aftersale-contact-panel">
                            <text class="aftersale-contact-panel__label">联系人</text>
                            <input
                                v-model="form.contact_name"
                                class="aftersale-contact-panel__input"
                                maxlength="20"
                                placeholder="请输入联系人姓名"
                                placeholder-style="color: #B4ACA8;"
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
                                placeholder-style="color: #B4ACA8;"
                            />
                        </view>
                    </view>
                </BaseCard>

                <view class="aftersale-create-page__footer-copy"> 提交后可查看进度。 </view>
            </view>
        </view>

        <ActionArea class="aftersale-create-page__action-bar" sticky safeBottom>
            <view class="aftersale-create-page__action-slot">
                <BaseButton
                    block
                    size="lg"
                    :disabled="submitDisabled"
                    :loading="submitting"
                    @click="handleSubmit"
                >
                    提交工单
                </BaseButton>
            </view>
        </ActionArea>

        <tn-picker
            v-model="showOrderPicker"
            mode="selector"
            :range="orderOptions"
            range-key="label"
            @confirm="onOrderConfirm"
        />
    </PageShell>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { createTicket } from '@/packages/common/api/aftersale'
import { getOrderList } from '@/api/order'
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
import { subscribeAfterSaleScenes } from '@/utils/subscribe'
import AfterSaleMediaUploader from './components/AfterSaleMediaUploader.vue'
import { pickOrderByPicker, toOrderOptions } from './shared'

interface TicketCategoryItem {
    label: string
    type: number
}

interface PriorityOptionItem {
    value: number
    label: string
    desc: string
    hint: string
}

const $theme = useThemeStore()
const userStore = useUserStore()
const showOrderPicker = ref(false)
const uploading = ref(false)
const submitting = ref(false)
const orderOptions = ref<any[]>([])
const selectedOrder = ref<any>(null)

const ticketCategories: TicketCategoryItem[] = [
    { label: '素材交付', type: 3 },
    { label: '流程确认', type: 2 },
    { label: '档期协调', type: 3 },
    { label: '服务建议', type: 4 },
    { label: '其他问题', type: 5 }
]

const priorityOptions: PriorityOptionItem[] = [
    {
        value: 1,
        label: '低',
        desc: '常规处理',
        hint: '常规排队处理。'
    },
    { value: 2, label: '中', desc: '尽快跟进', hint: '默认优先级。' },
    {
        value: 3,
        label: '高',
        desc: '影响较大',
        hint: '高优先级优先处理。'
    },
    {
        value: 4,
        label: '紧急',
        desc: '需要立即响应',
        hint: '紧急问题优先处理。'
    }
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

const categoryRows = [ticketCategories.slice(0, 3), ticketCategories.slice(3)]
const selectedCategory = computed(
    () => ticketCategories.find((item) => item.label === form.category) || ticketCategories[0]
)
const selectedPriority = computed(
    () => priorityOptions.find((item) => item.value === form.priority) || priorityOptions[1]
)
const priorityHint = computed(() => selectedPriority.value.hint)
const submitDisabled = computed(() => submitting.value || uploading.value)
const previewTitle = computed(() => buildTicketTitle(normalizeText(form.content)))

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
    const nickname = String(userStore.userInfo?.nickname || '').trim()
    const mobile = String(userStore.userInfo?.mobile || '').trim()

    if (!form.contact_name) {
        form.contact_name = realName || nickname
    }

    if (!form.contact_phone) {
        form.contact_phone = mobile
    }
}

const loadOrders = async () => {
    try {
        const res = await getOrderList()
        const lists = res?.lists || res?.data?.lists || []
        orderOptions.value = toOrderOptions(lists)
        if (form.order_id) {
            selectedOrder.value =
                orderOptions.value.find(
                    (item: any) => Number(item.value) === Number(form.order_id)
                ) || null
        }
    } catch (error) {
        console.error('获取订单列表失败', error)
    }
}

const onOrderConfirm = (event: any) => {
    const order = pickOrderByPicker(orderOptions.value, event)
    if (!order) {
        return
    }
    selectedOrder.value = order
    form.order_id = Number(order.value || 0)
}

const selectCategory = (label: string) => {
    form.category = label
}

const promptAfterSaleSubscribe = async () => {
    if (client !== ClientEnum.MP_WEIXIN) {
        return true
    }

    const result = await uni.showModal({
        title: '接收售后处理提醒',
        content: '订阅后可接收处理提醒。',
        confirmText: '去订阅',
        cancelText: '暂不订阅'
    })

    if (!result.confirm) {
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
        return uni.showToast({ title: '请等待附件上传完成', icon: 'none' })
    }

    const summary = normalizeText(form.content)
    if (!summary) {
        return uni.showToast({ title: '请填写问题描述', icon: 'none' })
    }

    const contactName = String(form.contact_name || '').trim()
    const contactPhone = String(form.contact_phone || '').trim()
    if (!contactName) {
        return uni.showToast({ title: '请输入联系人姓名', icon: 'none' })
    }
    if (!contactPhone) {
        return uni.showToast({ title: '请输入联系电话', icon: 'none' })
    }
    if (!isValidMobile(contactPhone)) {
        return uni.showToast({ title: '请输入正确的联系电话', icon: 'none' })
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
        uni.showToast({ title: '工单已提交', icon: 'none' })
        setTimeout(() => {
            uni.redirectTo({ url: '/packages/pages/aftersale/ticket' })
        }, 500)
    } catch (error: any) {
        uni.showToast({ title: error?.message || error || '提交失败', icon: 'none' })
    } finally {
        submitting.value = false
    }
}

watch(
    () => [userStore.userInfo?.real_name, userStore.userInfo?.nickname, userStore.userInfo?.mobile],
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
    @include aftersale-page-wrapper;
    gap: 18rpx;
    padding: 12rpx 0 calc(var(--wm-safe-bottom-action, 160rpx) + 78rpx);
}

.aftersale-create-section {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.aftersale-create-section__title {
    font-size: 28rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.aftersale-create-section__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.aftersale-create-section__head--stack {
    align-items: flex-start;
    flex-direction: column;
}

.aftersale-create-section__meta {
    font-size: 22rpx;
    line-height: 1.45;
    color: var(--wm-text-tertiary, #b4aca8);
}

.aftersale-object-row {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12rpx;
}

.aftersale-object-chip {
    min-height: 68rpx;
    padding: 0 12rpx;
    border-radius: 999rpx;
    border: 1rpx solid rgba(239, 230, 225, 0.96);
    background: rgba(255, 248, 245, 0.96);
    display: flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    font-size: 24rpx;
    line-height: 1.4;
    font-weight: 600;
    color: var(--wm-text-secondary, #7f7b78);
    text-align: center;

    &.is-active {
        background: rgba(255, 241, 238, 0.98);
        border-color: rgba(244, 199, 191, 0.96);
        color: var(--wm-color-primary, #e85a4f);
        font-weight: 700;
    }
}

.aftersale-create-panel,
.aftersale-create-input,
.aftersale-contact-panel {
    border-radius: 34rpx;
    border: 1rpx solid rgba(239, 230, 225, 0.96);
    background: rgba(255, 248, 245, 0.92);
    box-sizing: border-box;
}

.aftersale-create-panel {
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 92rpx;
    padding: 0 24rpx;
}

.aftersale-create-panel__text {
    flex: 1;
    min-width: 0;
    font-size: 26rpx;
    line-height: 1.5;
    font-weight: 600;
    color: var(--wm-text-primary, #1e2432);
}

.aftersale-create-panel__text.is-placeholder {
    color: var(--wm-text-tertiary, #b4aca8);
}

.aftersale-create-textarea {
    width: 100%;
    min-height: 180rpx;
    padding: 24rpx;
    border-radius: 34rpx;
    border: 1rpx solid rgba(239, 230, 225, 0.96);
    background: rgba(255, 248, 245, 0.92);
    box-sizing: border-box;
    font-size: 26rpx;
    line-height: 1.7;
    color: var(--wm-text-primary, #1e2432);
}

.aftersale-inline-field {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
}

.aftersale-inline-field__title {
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-text-secondary, #7f7b78);
}

.aftersale-inline-field__hint {
    font-size: 22rpx;
    line-height: 1.45;
    color: var(--wm-text-tertiary, #b4aca8);
}

.aftersale-level-list {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
}

.aftersale-level-chip {
    min-width: 122rpx;
    min-height: 58rpx;
    padding: 0 20rpx;
    border-radius: 999rpx;
    border: 1rpx solid rgba(239, 230, 225, 0.96);
    background: rgba(255, 255, 255, 0.88);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    font-size: 22rpx;
    font-weight: 600;
    color: var(--wm-text-secondary, #7f7b78);

    &.is-active {
        background: rgba(255, 241, 238, 0.98);
        border-color: rgba(244, 199, 191, 0.96);
        color: var(--wm-color-primary, #e85a4f);
    }
}

.aftersale-create-input {
    width: 100%;
    min-height: 88rpx;
    padding: 0 24rpx;
    font-size: 26rpx;
    color: var(--wm-text-primary, #1e2432);
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
    font-size: 26rpx;
    font-weight: 600;
    color: var(--wm-text-primary, #1e2432);
}

.aftersale-contact-panel__input {
    flex: 1;
    min-width: 0;
    height: 88rpx;
    font-size: 26rpx;
    color: var(--wm-text-primary, #1e2432);
}

.aftersale-create-page__footer-copy {
    padding: 2rpx 2rpx 0;
    font-size: 22rpx;
    line-height: 1.45;
    color: var(--wm-text-secondary, #7f7b78);
}

.aftersale-create-page__action-slot {
    flex: 1;
    width: 100%;
}
</style>
