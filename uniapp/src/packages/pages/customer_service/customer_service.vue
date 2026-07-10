<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" tone="workspace">
        <BaseNavbar
            title="联系顾问"
            variant="solid"
            bg-color="#191713"
            text-color="#FFFDF8"
        />

        <view class="consult-page">
            <view class="consult-shell">
                <BaseCard
                    v-if="state.loading"
                    class="consult-state-card"
                    variant="quiet"
                    scene="consumer"
                    padding="42rpx 28rpx"
                    border-radius="34rpx"
                >
                    <LoadingState
                        text="正在接入顾问..."
                        tone="wedding"
                        compact
                    />
                </BaseCard>

                <BaseCard
                    v-else-if="state.error"
                    class="consult-state-card"
                    variant="quiet"
                    scene="consumer"
                    padding="42rpx 28rpx"
                    border-radius="34rpx"
                >
                    <EmptyState
                        title="顾问信息加载失败"
                        :description="state.error"
                        action-text="重试"
                        tone="error"
                        compact
                        @action="loadConsultContact"
                    />
                </BaseCard>

                <template v-else>
                    <BaseCard variant="hero" scene="consumer" class="consult-hero" padding="0">
                        <view class="consult-hero__inner">
                            <view class="consult-hero__top">
                                <StatusBadge :tone="chatReady ? 'success' : 'warning'" size="xs" dot>
                                    {{ chatReady ? '微信客服已接入' : '客服配置待确认' }}
                                </StatusBadge>
                                <text class="consult-hero__scene">{{ sceneLabel }}</text>
                            </view>

                            <view class="advisor-profile">
                                <view class="advisor-avatar-wrap">
                                    <image
                                        v-if="contact.avatar"
                                        class="advisor-avatar"
                                        :src="contact.avatar"
                                        mode="aspectFill"
                                    />
                                    <view v-else class="advisor-avatar avatar-placeholder">
                                        {{ displayInitial }}
                                    </view>
                                </view>

                                <view class="advisor-main">
                                    <view class="advisor-title-row">
                                        <text class="advisor-name">{{ displayName }}</text>
                                        <StatusBadge tone="warning" size="xs">
                                            {{ advisorBadgeText }}
                                        </StatusBadge>
                                    </view>

                                    <text class="advisor-role">{{ displayRole }}</text>

                                    <view v-if="contact.service_time" class="advisor-time">
                                        <BaseIcon name="clock" size="22" color="#D9BE82" />
                                        <text class="advisor-time__text">{{ contact.service_time }}</text>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </BaseCard>

                    <BaseCard variant="list" scene="consumer" class="chat-card" padding="0">
                        <view class="chat-card__inner">
                            <view class="chat-card__head">
                                <view class="chat-card__icon">
                                    <BaseIcon name="wechat-fill" size="40" color="#D9BE82" />
                                </view>

                                <view class="chat-card__copy">
                                    <text class="chat-card__title">微信客服</text>
                                    <text class="chat-card__meta">{{ chatMetaText }}</text>
                                </view>

                                <StatusBadge :tone="chatReady ? 'success' : 'warning'" size="xs">
                                    {{ chatReady ? '官方会话' : '待配置' }}
                                </StatusBadge>
                            </view>

                            <BaseButton
                                :label="primaryActionText"
                                icon="wechat-fill"
                                :variant="chatReady ? 'dark' : 'light'"
                                size="lg"
                                block
                                :loading="state.openingChat"
                                loading-text="打开中..."
                                @click="openCustomerServiceChat"
                            />
                        </view>
                    </BaseCard>

                    <view class="consult-info-grid">
                        <view
                            v-for="item in supportItems"
                            :key="item.key"
                            class="consult-info-item"
                        >
                            <view class="consult-info-item__icon">
                                <BaseIcon :name="item.icon" size="26" color="#9A6B35" />
                            </view>
                            <view class="consult-info-item__copy">
                                <text class="consult-info-item__label">{{ item.label }}</text>
                                <text class="consult-info-item__value">{{ item.value }}</text>
                            </view>
                        </view>
                    </view>

                    <BaseCard
                        v-if="contact.tips"
                        variant="quiet"
                        scene="consumer"
                        class="tips-card"
                        padding="0"
                    >
                        <view class="tips-card__inner">
                            <BaseIcon name="tip" size="24" color="#B8954A" />
                            <text class="tips-card__text">{{ contact.tips }}</text>
                        </view>
                    </BaseCard>
                </template>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, reactive } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { startConsult } from '@/packages/common/api/customerService'
import { useThemeStore } from '@/stores/theme'
import { showError } from '@/utils/feedback'

type ConsultScene = 'home' | 'staff_detail' | 'order_detail' | 'aftersale' | 'package_detail'

const $theme = useThemeStore()

const sceneTextMap: Record<ConsultScene, string> = {
    home: '首页咨询',
    staff_detail: '人员详情咨询',
    order_detail: '订单咨询',
    aftersale: '售后咨询',
    package_detail: '套餐咨询'
}

const query = reactive({
    scene: 'home' as ConsultScene,
    staff_id: 0,
    order_id: 0,
    category_id: 0
})

const state = reactive({
    loading: true,
    error: '',
    openingChat: false,
    entryType: 'fallback' as 'advisor' | 'fallback',
    contact: {
        name: '',
        role: '',
        avatar: '',
        service_time: '',
        tips: ''
    },
    customerServiceChat: {
        enabled: false,
        url: '',
        corp_id: ''
    }
})

const contact = computed(() => state.contact)

const advisorBadgeText = computed(() => (state.entryType === 'advisor' ? '专属顾问' : '统一客服'))

const displayName = computed(() => String(contact.value.name || '').trim() || '婚礼顾问')

const displayRole = computed(() => String(contact.value.role || '').trim() || '服务咨询')

const displayInitial = computed(() => displayName.value.slice(0, 1) || '顾')

const sceneLabel = computed(() => sceneTextMap[query.scene] || '服务咨询')

const chatReady = computed(() =>
    Boolean(
        state.customerServiceChat.enabled &&
            state.customerServiceChat.url.trim() &&
            state.customerServiceChat.corp_id.trim()
    )
)

const chatMetaText = computed(() =>
    chatReady.value ? '进入官方微信客服会话' : '请稍后再试或联系管理员配置'
)

const primaryActionText = computed(() => (chatReady.value ? '进入微信客服' : '客服暂未配置'))

const supportItems = computed(() => [
    {
        key: 'scene',
        label: '咨询来源',
        value: sceneLabel.value,
        icon: 'service'
    },
    {
        key: 'time',
        label: '服务时间',
        value: contact.value.service_time || '在线客服',
        icon: 'clock'
    },
    {
        key: 'chat',
        label: '会话状态',
        value: chatReady.value ? '微信客服' : '待配置',
        icon: chatReady.value ? 'success-circle' : 'warning'
    }
])

const loadConsultContact = async () => {
    state.loading = true
    state.error = ''
    try {
        const data = await startConsult({
            scene: query.scene,
            staff_id: query.staff_id || undefined,
            order_id: query.order_id || undefined,
            category_id: query.category_id || undefined
        })
        state.entryType = data.entry_type || 'fallback'
        state.contact = {
            name: data.contact?.name || '',
            role: data.contact?.role || '',
            avatar: data.contact?.avatar || '',
            service_time: data.contact?.service_time || '',
            tips: data.contact?.tips || ''
        }
        state.customerServiceChat = {
            enabled: Boolean(data.customer_service_chat?.enabled),
            url: String(data.customer_service_chat?.url || ''),
            corp_id: String(data.customer_service_chat?.corp_id || '')
        }
    } catch (error: any) {
        state.error = error?.message || '加载失败，请稍后重试'
    } finally {
        state.loading = false
    }
}

const openCustomerServiceChat = () => {
    // #ifndef MP-WEIXIN
    showError('请在微信小程序内使用客服')
    return
    // #endif

    const chatUrl = state.customerServiceChat.url.trim()
    const corpId = state.customerServiceChat.corp_id.trim()

    if (!state.customerServiceChat.enabled || !chatUrl || !corpId) {
        showError('微信客服暂未配置，请稍后再试')
        return
    }

    // #ifdef MP-WEIXIN
    if (typeof wx?.openCustomerServiceChat !== 'function') {
        showError('当前微信版本暂不支持客服会话，请升级微信后再试')
        return
    }

    state.openingChat = true
    wx.openCustomerServiceChat({
        extInfo: {
            url: chatUrl
        },
        corpId,
        fail: (error: any) => {
            showError(error?.errMsg || '打开微信客服失败，请稍后重试')
        },
        complete: () => {
            state.openingChat = false
        }
    })
    // #endif
}

onLoad((options?: Record<string, string>) => {
    query.scene = (options?.scene as typeof query.scene) || 'home'
    query.staff_id = Number(options?.staff_id || 0)
    query.order_id = Number(options?.order_id || 0)
    query.category_id = Number(options?.category_id || 0)
    loadConsultContact()
})
</script>

<style lang="scss" scoped>
.consult-page {
    min-height: 100vh;
    padding: 18rpx 24rpx calc(36rpx + env(safe-area-inset-bottom));
    box-sizing: border-box;
    background:
        radial-gradient(circle at 18% 0%, rgba(217, 190, 130, 0.2) 0, transparent 300rpx),
        linear-gradient(180deg, #fffdf8 0%, #f5f1e8 100%);
}

.consult-shell {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
}

.consult-state-card {
    margin-top: 6rpx;
}

.consult-hero {
    --wm-radius-card: 40rpx;
}

.consult-hero__inner {
    position: relative;
    z-index: 1;
    padding: 28rpx;
}

.consult-hero__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    min-width: 0;
    margin-bottom: 26rpx;
}

.consult-hero__scene {
    min-width: 0;
    max-width: 240rpx;
    font-size: 22rpx;
    font-weight: 800;
    line-height: 1.2;
    color: rgba(255, 253, 248, 0.72);
    text-align: right;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.advisor-profile {
    display: flex;
    align-items: center;
    gap: 22rpx;
    min-width: 0;
}

.advisor-avatar-wrap {
    flex: 0 0 124rpx;
    width: 124rpx;
    height: 124rpx;
    padding: 6rpx;
    border-radius: 34rpx;
    background: rgba(217, 190, 130, 0.22);
    border: 1rpx solid rgba(217, 190, 130, 0.72);
    box-sizing: border-box;
}

.advisor-avatar {
    width: 100%;
    height: 100%;
    display: block;
    border-radius: 28rpx;
    background: rgba(255, 253, 248, 0.12);
}

.avatar-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #d9be82 0%, #9a6b35 100%);
    color: #191713;
    font-size: 44rpx;
    font-weight: 900;
}

.advisor-main {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 10rpx;
}

.advisor-title-row {
    display: flex;
    align-items: flex-start;
    gap: 12rpx;
    min-width: 0;
}

.advisor-name {
    flex: 1;
    min-width: 0;
    display: block;
    font-size: 38rpx;
    line-height: 1.15;
    font-weight: 900;
    color: #fffdf8;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.advisor-role {
    display: block;
    max-width: 360rpx;
    font-size: 24rpx;
    line-height: 1.35;
    color: rgba(255, 253, 248, 0.72);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.advisor-time {
    align-self: flex-start;
    max-width: 100%;
    min-height: 42rpx;
    padding: 0 14rpx;
    border-radius: 999rpx;
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    background: rgba(255, 253, 248, 0.1);
    border: 1rpx solid rgba(217, 190, 130, 0.36);
    box-sizing: border-box;
}

.advisor-time__text {
    min-width: 0;
    font-size: 21rpx;
    font-weight: 800;
    line-height: 1;
    color: rgba(255, 253, 248, 0.82);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.chat-card {
    --wm-radius-list-panel: 36rpx;
}

.chat-card__inner {
    position: relative;
    z-index: 1;
    padding: 24rpx;
}

.chat-card__head {
    display: flex;
    align-items: center;
    gap: 16rpx;
    min-width: 0;
    margin-bottom: 22rpx;
}

.chat-card__icon {
    width: 76rpx;
    height: 76rpx;
    flex: 0 0 76rpx;
    border-radius: 24rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--wm-color-primary, #191713);
    border: 1rpx solid rgba(217, 190, 130, 0.68);
    box-shadow: 0 14rpx 30rpx rgba(74, 43, 24, 0.14);
}

.chat-card__copy {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.chat-card__title {
    display: block;
    font-size: 30rpx;
    line-height: 1.2;
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.chat-card__meta {
    display: block;
    max-width: 100%;
    font-size: 23rpx;
    font-weight: 700;
    line-height: 1.25;
    color: var(--wm-text-secondary, #665e52);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.chat-card :deep(.base-button__text) {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.consult-info-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12rpx;
}

.consult-info-item {
    min-width: 0;
    min-height: 142rpx;
    padding: 16rpx 12rpx;
    border-radius: 28rpx;
    background: rgba(255, 253, 248, 0.9);
    border: 1rpx solid rgba(216, 201, 173, 0.86);
    box-shadow: 0 10rpx 24rpx rgba(74, 43, 24, 0.05);
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 12rpx;
}

.consult-info-item__icon {
    width: 48rpx;
    height: 48rpx;
    border-radius: 18rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(241, 229, 200, 0.72);
}

.consult-info-item__copy {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 6rpx;
}

.consult-info-item__label,
.consult-info-item__value {
    display: block;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.consult-info-item__label {
    font-size: 20rpx;
    font-weight: 800;
    line-height: 1.2;
    color: var(--wm-text-tertiary, #8a806f);
}

.consult-info-item__value {
    font-size: 24rpx;
    font-weight: 900;
    line-height: 1.2;
    color: var(--wm-text-primary, #191713);
}

.tips-card__inner {
    position: relative;
    z-index: 1;
    padding: 18rpx 20rpx;
    border-radius: 28rpx;
    background: rgba(255, 253, 248, 0.9);
    border: 1rpx solid rgba(216, 201, 173, 0.86);
    display: flex;
    align-items: flex-start;
    gap: 12rpx;
}

.tips-card__text {
    display: block;
    flex: 1;
    min-width: 0;
    font-size: 24rpx;
    line-height: 1.5;
    color: var(--wm-text-secondary, #665e52);
}

@media (max-width: 360px) {
    .consult-page {
        padding-left: 20rpx;
        padding-right: 20rpx;
    }

    .consult-hero__inner,
    .chat-card__inner {
        padding: 22rpx;
    }

    .advisor-avatar-wrap {
        width: 108rpx;
        height: 108rpx;
        flex-basis: 108rpx;
        border-radius: 30rpx;
    }

    .advisor-avatar {
        border-radius: 24rpx;
    }

    .advisor-name {
        font-size: 34rpx;
    }

    .consult-info-grid {
        grid-template-columns: minmax(0, 1fr);
    }

    .consult-info-item {
        min-height: 96rpx;
        flex-direction: row;
        align-items: center;
        justify-content: flex-start;
        padding: 18rpx 20rpx;
    }
}
</style>
