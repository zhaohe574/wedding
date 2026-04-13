<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="发起投诉" />

        <view class="aftersale-create-page">
            <view class="aftersale-create-page__wrapper">
                <BaseCard
                    variant="surface"
                    scene="consumer"
                    padding="24rpx 26rpx"
                    border-radius="44rpx"
                    background="rgba(255, 255, 255, 0.92)"
                    border="1rpx solid rgba(239, 230, 225, 0.96)"
                    box-shadow="0 18rpx 38rpx rgba(214, 185, 167, 0.12)"
                >
                    <view class="aftersale-create-section">
                        <text class="aftersale-create-section__title">投诉对象</text>
                        <view
                            v-for="(row, rowIndex) in complaintTypeRows"
                            :key="`type-row-${rowIndex}`"
                            class="aftersale-object-row"
                        >
                            <view
                                v-for="item in row"
                                :key="item.value"
                                class="aftersale-object-chip"
                                :class="{ 'is-active': form.type === item.value }"
                                @click="form.type = item.value"
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
                    background="rgba(255, 255, 255, 0.92)"
                    border="1rpx solid rgba(239, 230, 225, 0.96)"
                    box-shadow="0 18rpx 38rpx rgba(214, 185, 167, 0.12)"
                >
                    <view class="aftersale-create-section">
                        <view class="aftersale-create-section__head">
                            <text class="aftersale-create-section__title">关联订单</text>
                            <text class="aftersale-create-section__meta">投诉需关联具体订单</text>
                        </view>
                        <view class="aftersale-create-panel" @click="showOrderPicker = true">
                            <text
                                class="aftersale-create-panel__text"
                                :class="{ 'is-placeholder': !selectedOrder }"
                            >
                                {{ selectedOrder?.label || '请选择订单' }}
                            </text>
                            <tn-icon name="right" size="22" color="#B4ACA8" />
                        </view>
                    </view>
                </BaseCard>

                <BaseCard
                    variant="surface"
                    scene="consumer"
                    padding="24rpx 26rpx"
                    border-radius="44rpx"
                    background="rgba(255, 255, 255, 0.92)"
                    border="1rpx solid rgba(239, 230, 225, 0.96)"
                    box-shadow="0 18rpx 38rpx rgba(214, 185, 167, 0.12)"
                >
                    <view class="aftersale-create-section">
                        <view
                            class="aftersale-create-section__head aftersale-create-section__head--stack"
                        >
                            <text class="aftersale-create-section__title">投诉内容</text>
                            <text class="aftersale-create-section__meta">
                                提交时会自动生成标题：{{ previewTitle }}
                            </text>
                        </view>
                        <textarea
                            v-model="form.content"
                            class="aftersale-create-textarea"
                            maxlength="500"
                            placeholder="简述投诉内容"
                            placeholder-style="color: #B4ACA8;"
                        />

                        <view class="aftersale-inline-field">
                            <text class="aftersale-inline-field__title">处理紧急度</text>
                            <view class="aftersale-level-list">
                                <view
                                    v-for="level in complaintLevels"
                                    :key="level.value"
                                    class="aftersale-level-chip"
                                    :class="{ 'is-active': form.level === level.value }"
                                    @click="form.level = level.value"
                                >
                                    {{ level.label }}
                                </view>
                            </view>
                        </view>

                        <view class="aftersale-inline-field">
                            <text class="aftersale-inline-field__title">期望处理</text>
                            <input
                                v-model="form.expect_result"
                                class="aftersale-create-input"
                                maxlength="60"
                                placeholder="例如：退款、道歉、补偿说明"
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
                    background="rgba(255, 255, 255, 0.92)"
                    border="1rpx solid rgba(239, 230, 225, 0.96)"
                    box-shadow="0 18rpx 38rpx rgba(214, 185, 167, 0.12)"
                >
                    <view class="aftersale-create-section">
                        <view
                            class="aftersale-create-section__head aftersale-create-section__head--stack"
                        >
                            <text class="aftersale-create-section__title">上传凭证</text>
                            <text class="aftersale-create-section__meta">
                                现场截图、聊天记录等凭证有助于平台快速核查
                            </text>
                        </view>
                        <AfterSaleMediaUploader
                            v-model="form.images"
                            variant="ticket-evidence"
                            kind="image"
                            add-text="+ 上传"
                            :entry-labels="['上传', '现场截图', '聊天记录']"
                            :max="6"
                            @uploading-change="imageUploading = $event"
                        />
                    </view>
                </BaseCard>

                <BaseCard
                    variant="surface"
                    scene="consumer"
                    padding="24rpx 26rpx"
                    border-radius="44rpx"
                    background="rgba(255, 255, 255, 0.92)"
                    border="1rpx solid rgba(239, 230, 225, 0.96)"
                    box-shadow="0 18rpx 38rpx rgba(214, 185, 167, 0.12)"
                >
                    <view class="aftersale-create-section">
                        <view
                            class="aftersale-create-section__head aftersale-create-section__head--stack"
                        >
                            <text class="aftersale-create-section__title">联系方式</text>
                            <text class="aftersale-create-section__meta">
                                平台处理时会优先通过这组信息与你确认细节
                            </text>
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
                                v-model="form.contact_mobile"
                                class="aftersale-contact-panel__input"
                                type="number"
                                maxlength="11"
                                placeholder="请输入联系电话"
                                placeholder-style="color: #B4ACA8;"
                            />
                        </view>
                    </view>
                </BaseCard>

                <view class="aftersale-create-page__footer-copy">
                    提交后可查看进度，平台会根据投诉等级和凭证完整度优先核查。
                </view>
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
                    提交投诉
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
import { getOrderList } from '@/api/order'
import { submitComplaint } from '@/packages/common/api/aftersale'
import ActionArea from '@/components/base/ActionArea.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import { useThemeStore } from '@/stores/theme'
import { useUserStore } from '@/stores/user'
import { onLoad } from '@dcloudio/uni-app'
import AfterSaleMediaUploader from './components/AfterSaleMediaUploader.vue'
import {
    complaintLevelOptions,
    complaintTypeOptions,
    pickOrderByPicker,
    toOrderOptions
} from './shared'

const $theme = useThemeStore()
const userStore = useUserStore()
const showOrderPicker = ref(false)
const imageUploading = ref(false)
const submitting = ref(false)
const orderOptions = ref<any[]>([])
const selectedOrder = ref<any>(null)

const form = reactive({
    order_id: 0,
    type: 1,
    level: 1,
    content: '',
    expect_result: '',
    images: [] as string[],
    videos: [] as string[],
    contact_name: '',
    contact_mobile: ''
})

const complaintTypes = complaintTypeOptions
const complaintLevels = complaintLevelOptions
const complaintTypeRows = [complaintTypes.slice(0, 3), complaintTypes.slice(3)]

const submitDisabled = computed(() => submitting.value || imageUploading.value)
const selectedComplaintType = computed(
    () => complaintTypes.find((item) => item.value === form.type) || complaintTypes[0]
)
const previewTitle = computed(() => buildComplaintTitle(String(form.content || '').trim()))

const isValidMobile = (mobile: string) => /^1[3-9]\d{9}$/.test(mobile)
const normalizeText = (value: string) => value.replace(/\s+/g, ' ').trim()

const buildComplaintTitle = (summary: string) => {
    const prefix = selectedComplaintType.value?.label || '其他'
    const headline = summary.slice(0, 18) || '待补充投诉内容'
    const suffix = summary.length > 18 ? '…' : ''
    return `${prefix}｜${headline}${suffix}`
}

const fillContactDefaults = () => {
    const realName = String(userStore.userInfo?.real_name || '').trim()
    const nickname = String(userStore.userInfo?.nickname || '').trim()
    const mobile = String(userStore.userInfo?.mobile || '').trim()
    if (!form.contact_name) {
        form.contact_name = realName || nickname
    }
    if (!form.contact_mobile && mobile) {
        form.contact_mobile = mobile
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

const handleSubmit = async () => {
    if (submitDisabled.value) {
        return
    }

    if (imageUploading.value) {
        return uni.showToast({ title: '请等待附件上传完成', icon: 'none' })
    }

    if (!form.order_id) {
        return uni.showToast({ title: '请选择关联订单', icon: 'none' })
    }

    const content = normalizeText(form.content)
    if (!content) {
        return uni.showToast({ title: '请填写投诉内容', icon: 'none' })
    }

    const contactName = String(form.contact_name || '').trim()
    if (!contactName) {
        return uni.showToast({ title: '请输入联系人姓名', icon: 'none' })
    }

    const contactMobile = String(form.contact_mobile || '').trim()
    if (!contactMobile) {
        return uni.showToast({ title: '请输入联系电话', icon: 'none' })
    }
    if (!isValidMobile(contactMobile)) {
        return uni.showToast({ title: '请输入正确的联系电话', icon: 'none' })
    }

    submitting.value = true
    try {
        await submitComplaint({
            order_id: form.order_id,
            type: form.type,
            level: form.level,
            title: buildComplaintTitle(content),
            content,
            expect_result: String(form.expect_result || '').trim(),
            images: form.images,
            videos: [],
            contact_name: contactName,
            contact_mobile: contactMobile
        })
        uni.showToast({ title: '投诉已提交', icon: 'none' })
        setTimeout(() => {
            uni.redirectTo({ url: '/packages/pages/aftersale/complaint' })
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
        form.type = Number(options.type) || 1
    }
    if (options?.level) {
        form.level = Number(options.level) || 1
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
    display: flex;
    flex-direction: column;
    gap: 18rpx;
    padding: 12rpx var(--wm-space-page-x, 37rpx) calc(var(--wm-safe-bottom-action, 160rpx) + 78rpx);
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
