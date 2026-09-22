<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" tone="workspace" hasSafeBottom>
        <BaseNavbar
            title="专属客服管家"
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
                    padding="36rpx 24rpx"
                    border-radius="34rpx"
                >
                    <LoadingState
                        text="正在为您接入专属管家..."
                        tone="wedding"
                        compact
                    />
                </BaseCard>

                <BaseCard
                    v-else-if="state.error"
                    class="consult-state-card"
                    variant="quiet"
                    scene="consumer"
                    padding="36rpx 24rpx"
                    border-radius="34rpx"
                >
                    <EmptyState
                        title="管家信息加载失败"
                        :description="state.error"
                        action-text="重新连接"
                        tone="error"
                        compact
                        @action="loadConsultContact"
                    />
                </BaseCard>

                <template v-else>
                    <!-- 1. 黑金尊享专属管家 Hero 展板 -->
                    <BaseCard variant="hero" scene="consumer" class="consult-hero" padding="0">
                        <view class="consult-hero__inner">
                            <view class="consult-hero__top">
                                <StatusBadge :tone="chatReady ? 'success' : 'warning'" size="xs" dot>
                                    {{ chatReady ? '微信客服专席在线' : '客服排队接入中' }}
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
                                    <view class="advisor-avatar__badge">
                                        <BaseIcon name="shield-check" size="18" color="#191713" />
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

                                    <view class="advisor-time">
                                        <BaseIcon name="clock" size="20" color="#D9BE82" />
                                        <text class="advisor-time__text">
                                            {{ contact.service_time || '09:00 - 21:00 (紧急事项全天响应)' }}
                                        </text>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </BaseCard>

                    <!-- 2. 核心沟通会话卡片 -->
                    <BaseCard
                        variant="list"
                        scene="consumer"
                        class="chat-card"
                        padding="22rpx 20rpx"
                        border="1rpx solid rgba(217, 190, 130, 0.7)"
                        box-shadow="0 14rpx 36rpx rgba(74, 43, 24, 0.08)"
                    >
                        <view class="chat-card__head">
                            <view class="chat-card__icon">
                                <BaseIcon name="wechat-fill" size="36" color="#D9BE82" />
                            </view>

                            <view class="chat-card__copy">
                                <text class="chat-card__title">微信在线专属客服</text>
                                <text class="chat-card__meta">{{ chatMetaText }}</text>
                            </view>

                            <StatusBadge :tone="chatReady ? 'success' : 'warning'" size="xs">
                                {{ chatReady ? '官方认证' : '待配置' }}
                            </StatusBadge>
                        </view>

                        <!-- 高端定制微信在线客服按钮 -->
                        <button
                            class="chat-contact-btn"
                            open-type="contact"
                            :session-from="query.scene"
                            hover-class="chat-contact-btn--hover"
                        >
                            <BaseIcon name="wechat-fill" size="34" color="#191713" />
                            <text class="chat-contact-btn__text">进入微信在线咨询</text>
                        </button>

                        <!-- 电话拨打辅助通道 -->
                        <view v-if="contact.mobile" class="chat-phone-btn" @click="callAdvisor">
                            <BaseIcon name="phone" size="28" color="#7A5316" />
                            <text class="chat-phone-btn__text">拨打服务专线 ({{ contact.mobile }})</text>
                        </view>
                    </BaseCard>

                    <!-- 3. 关键服务维度 2x2 网格 -->
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

                    <!-- 4. 平台保障提示卡片 -->
                    <BaseCard
                        v-if="contact.tips"
                        variant="surface"
                        scene="consumer"
                        class="tips-card"
                        padding="16rpx 20rpx"
                        border="1rpx solid rgba(216, 201, 173, 0.72)"
                    >
                        <view class="tips-card__inner">
                            <view class="tips-card__icon-box">
                                <BaseIcon name="shield-check" size="24" color="#B8954A" />
                            </view>
                            <text class="tips-card__text">{{ contact.tips }}</text>
                        </view>
                    </BaseCard>

                    <!-- 5. 平台四大保障承诺 -->
                    <view class="consult-guarantees">
                        <view class="consult-guarantees__head">
                            <text class="consult-guarantees__title">平台管家服务承诺</text>
                            <text class="consult-guarantees__desc">全程守护您的备婚与礼成之旅</text>
                        </view>
                        <view class="consult-guarantees__grid">
                            <view
                                v-for="g in guarantees"
                                :key="g.title"
                                class="consult-guarantee-card"
                            >
                                <view class="consult-guarantee-card__icon">
                                    <BaseIcon :name="g.icon" size="26" color="#B8954A" />
                                </view>
                                <text class="consult-guarantee-card__title">{{ g.title }}</text>
                                <text class="consult-guarantee-card__desc">{{ g.desc }}</text>
                            </view>
                        </view>
                    </view>

                    <!-- 6. 常见咨询疑问 FAQ -->
                    <view class="consult-faq">
                        <view class="consult-faq__head">
                            <text class="consult-faq__title">常见备婚与售后咨询</text>
                            <text class="consult-faq__desc">点击可快速了解处理指引</text>
                        </view>
                        <view class="consult-faq__list">
                            <view
                                v-for="(faq, index) in faqList"
                                :key="faq.question"
                                class="consult-faq-item"
                                :class="{ 'is-open': faq.isOpen }"
                                @click="toggleFaq(index)"
                            >
                                <view class="consult-faq-item__header">
                                    <text class="consult-faq-item__q">{{ faq.question }}</text>
                                    <view class="consult-faq-item__arrow" :class="{ 'is-rotated': faq.isOpen }">
                                        <BaseIcon name="right" size="20" color="#9A9388" />
                                    </view>
                                </view>
                                <view v-if="faq.isOpen" class="consult-faq-item__body">
                                    <text class="consult-faq-item__a">{{ faq.answer }}</text>
                                </view>
                            </view>
                        </view>
                    </view>
                </template>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
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
    staff_detail: '服务人员咨询',
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
        tips: '',
        mobile: ''
    },
    customerServiceChat: { enabled: true }
})

const contact = computed(() => state.contact)

const advisorBadgeText = computed(() => (state.entryType === 'advisor' ? '专属管家' : '官方客服'))

const displayName = computed(() => String(contact.value.name || '').trim() || '婚礼管家专席')

const displayRole = computed(() => String(contact.value.role || '').trim() || '全案统筹 · 售后协调 · 档期跟进')

const displayInitial = computed(() => displayName.value.slice(0, 1) || '管')

const sceneLabel = computed(() => sceneTextMap[query.scene] || '专属咨询')

const chatReady = computed(() => state.customerServiceChat.enabled)

const chatMetaText = computed(() =>
    chatReady.value ? '点击即可接入官方微信客服，专人协同' : '专属客服排队中，也可拨打电话联系'
)

const supportItems = computed(() => [
    {
        key: 'scene',
        label: '咨询场景',
        value: sceneLabel.value,
        icon: 'service'
    },
    {
        key: 'time',
        label: '服务响应',
        value: '2 小时内响应',
        icon: 'clock'
    },
    {
        key: 'scope',
        label: '协助范围',
        value: '排期 / 履约 / 售后',
        icon: 'order'
    },
    {
        key: 'chat',
        label: '服务保障',
        value: '满意闭环跟进',
        icon: 'heart'
    }
])

const guarantees = [
    { title: '实名甄选', desc: '服务人员严格认证', icon: 'shield-check' },
    { title: '资金存管', desc: '满意验收后结算', icon: 'order' },
    { title: '极速响应', desc: '管家 2 小时跟进', icon: 'clock' },
    { title: '先行垫付', desc: '平台全程托底', icon: 'heart' }
]

interface FaqItem {
    question: string
    answer: string
    isOpen: boolean
}

const faqList = ref<FaqItem[]>([
    {
        question: '遇到婚礼档期调整或时间变更如何处理？',
        answer: '如需调整婚礼日期或服务时间，专属管家将在系统内为您协调服务人员剩余排期，或在必要时协助申请无缝换派同级服务人员。',
        isOpen: false
    },
    {
        question: '服务过程中发生分歧或不满意如何保障？',
        answer: '平台所有订单均受“婚礼保障基金”护航。您可通过客服或售后中心发起监督投诉，平台直属督办组将在 24 小时内保密调查并妥善答复。',
        isOpen: false
    },
    {
        question: '如何查询我的服务合同与尾款结算？',
        answer: '您可在“订单详情”中查阅电子签约协议、服务明细及款项进度，所有款项经由平台资金托管，服务满意验收后方予结算。',
        isOpen: false
    }
])

const toggleFaq = (index: number) => {
    faqList.value[index].isOpen = !faqList.value[index].isOpen
}

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
            tips: data.contact?.tips || '',
            mobile: data.contact?.mobile || ''
        }
        state.customerServiceChat = { enabled: Boolean(data.customer_service_chat?.enabled) }
    } catch (error: any) {
        state.error = error?.message || '加载失败，请稍后重试'
    } finally {
        state.loading = false
    }
}

const callAdvisor = () => {
    if (!contact.value.mobile) {
        showError('暂未提供联系电话')
        return
    }
    uni.makePhoneCall({
        phoneNumber: contact.value.mobile,
        fail: () => showError('拨号失败')
    })
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
    padding: 18rpx 22rpx calc(48rpx + env(safe-area-inset-bottom));
    box-sizing: border-box;
    background:
        radial-gradient(circle at 18% 0%, rgba(217, 190, 130, 0.16) 0, transparent 320rpx),
        linear-gradient(180deg, #fffdf8 0%, #f7f4ee 100%);
}

.consult-shell {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.consult-state-card {
    margin-top: 4rpx;
}

/* 1. Hero 展板 */
.consult-hero {
    --wm-radius-card: 32rpx;
    border: 1rpx solid rgba(217, 190, 130, 0.45) !important;
    background: radial-gradient(circle at 12% 0%, rgba(217, 190, 130, 0.22) 0, transparent 42%),
        linear-gradient(145deg, #2b261d 0%, #191713 62%, #3a2a16 100%) !important;
}

.consult-hero__inner {
    position: relative;
    z-index: 1;
    padding: 24rpx 22rpx;
}

.consult-hero__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14rpx;
    min-width: 0;
    margin-bottom: 18rpx;
}

.consult-hero__scene {
    min-width: 0;
    max-width: 260rpx;
    font-size: 21rpx;
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
    gap: 20rpx;
    min-width: 0;
}

.advisor-avatar-wrap {
    position: relative;
    flex: 0 0 114rpx;
    width: 114rpx;
    height: 114rpx;
    padding: 5rpx;
    border-radius: 32rpx;
    background: rgba(217, 190, 130, 0.22);
    border: 1rpx solid rgba(217, 190, 130, 0.72);
    box-sizing: border-box;
}

.advisor-avatar {
    width: 100%;
    height: 100%;
    display: block;
    border-radius: 26rpx;
    background: rgba(255, 253, 248, 0.12);
}

.advisor-avatar__badge {
    position: absolute;
    right: -4rpx;
    bottom: -4rpx;
    width: 34rpx;
    height: 34rpx;
    border-radius: 999rpx;
    background: #d9be82;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2rpx solid #191713;
}

.avatar-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #d9be82 0%, #9a6b35 100%);
    color: #191713;
    font-size: 42rpx;
    font-weight: 900;
}

.advisor-main {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 6rpx;
}

.advisor-title-row {
    display: flex;
    align-items: center;
    gap: 12rpx;
    min-width: 0;
}

.advisor-name {
    min-width: 0;
    display: block;
    font-size: 34rpx;
    line-height: 1.2;
    font-weight: 900;
    color: #fffdf8;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.advisor-role {
    display: block;
    max-width: 380rpx;
    font-size: 22rpx;
    line-height: 1.35;
    color: rgba(255, 253, 248, 0.76);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.advisor-time {
    align-self: flex-start;
    max-width: 100%;
    min-height: 38rpx;
    padding: 0 14rpx;
    margin-top: 2rpx;
    border-radius: 999rpx;
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    background: rgba(255, 253, 248, 0.12);
    border: 1rpx solid rgba(217, 190, 130, 0.36);
    box-sizing: border-box;
}

.advisor-time__text {
    min-width: 0;
    font-size: 20rpx;
    font-weight: 800;
    line-height: 1;
    color: rgba(255, 253, 248, 0.88);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* 2. 核心沟通卡片 */
.chat-card {
    background: #fffdf8 !important;
}

.chat-card__head {
    display: flex;
    align-items: center;
    gap: 14rpx;
    min-width: 0;
    margin-bottom: 18rpx;
}

.chat-card__icon {
    width: 68rpx;
    height: 68rpx;
    flex: 0 0 68rpx;
    border-radius: 20rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #191713;
    border: 1rpx solid rgba(217, 190, 130, 0.68);
    box-shadow: 0 12rpx 26rpx rgba(74, 43, 24, 0.12);
}

.chat-card__copy {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 4rpx;
}

.chat-card__title {
    display: block;
    font-size: 29rpx;
    line-height: 1.25;
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
}

.chat-card__meta {
    display: block;
    font-size: 21rpx;
    color: var(--wm-text-secondary, #665e52);
    line-height: 1.35;
}

.chat-contact-btn {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12rpx;
    width: 100%;
    height: 86rpx;
    padding: 0 28rpx;
    border-radius: 999rpx;
    background: linear-gradient(135deg, #f0dfb8 0%, #d9be82 50%, #b8954a 100%);
    box-shadow: 0 10rpx 26rpx rgba(184, 149, 74, 0.32);
    border: none;
    outline: none;
    margin: 0;
    line-height: normal;

    &::after {
        border: none;
    }

    &__text {
        font-size: 28rpx;
        font-weight: 900;
        letter-spacing: 1rpx;
        color: #191713;
    }

    &--hover {
        transform: scale(0.985);
        opacity: 0.92;
    }
}

.chat-phone-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10rpx;
    width: 100%;
    height: 80rpx;
    margin-top: 14rpx;
    border-radius: 999rpx;
    background: rgba(248, 242, 228, 0.7);
    border: 1rpx solid rgba(216, 201, 173, 0.85);
    transition: all 0.2s ease;

    &:active {
        background: rgba(240, 230, 212, 0.95);
        transform: scale(0.985);
    }

    &__text {
        font-size: 25rpx;
        font-weight: 800;
        color: #7a5316;
    }
}

/* 3. 服务维度 2x2 网格 */
.consult-info-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12rpx;
}

.consult-info-item {
    min-width: 0;
    padding: 16rpx 18rpx;
    border-radius: 22rpx;
    background: rgba(255, 253, 248, 0.92);
    border: 1rpx solid rgba(216, 201, 173, 0.75);
    box-shadow: 0 6rpx 16rpx rgba(74, 43, 24, 0.03);
    box-sizing: border-box;
    display: flex;
    align-items: center;
    gap: 14rpx;
}

.consult-info-item__icon {
    width: 52rpx;
    height: 52rpx;
    flex-shrink: 0;
    border-radius: 16rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(241, 229, 200, 0.65);
    border: 1rpx solid rgba(216, 201, 173, 0.5);
}

.consult-info-item__copy {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4rpx;
}

.consult-info-item__label {
    font-size: 20rpx;
    font-weight: 700;
    color: var(--wm-text-tertiary, #8a806f);
}

.consult-info-item__value {
    font-size: 23rpx;
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* 4. 提示卡片 */
.tips-card__inner {
    display: flex;
    align-items: flex-start;
    gap: 12rpx;
}

.tips-card__icon-box {
    width: 40rpx;
    height: 40rpx;
    border-radius: 12rpx;
    background: rgba(217, 190, 130, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.tips-card__text {
    flex: 1;
    font-size: 22rpx;
    line-height: 1.5;
    color: var(--wm-text-secondary, #665e52);
}

/* 5. 平台四大保障 */
.consult-guarantees {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
    margin-top: 4rpx;
}

.consult-guarantees__head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    padding: 0 4rpx;
}

.consult-guarantees__title {
    font-size: 27rpx;
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
}

.consult-guarantees__desc {
    font-size: 20rpx;
    color: var(--wm-text-tertiary, #9a9388);
}

.consult-guarantees__grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10rpx;
}

.consult-guarantee-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 16rpx 6rpx;
    border-radius: 20rpx;
    background: rgba(255, 253, 248, 0.88);
    border: 1rpx solid rgba(216, 201, 173, 0.65);
    box-sizing: border-box;
    gap: 6rpx;
}

.consult-guarantee-card__icon {
    width: 48rpx;
    height: 48rpx;
    border-radius: 14rpx;
    background: rgba(217, 190, 130, 0.16);
    display: flex;
    align-items: center;
    justify-content: center;
}

.consult-guarantee-card__title {
    font-size: 21rpx;
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
}

.consult-guarantee-card__desc {
    font-size: 18rpx;
    color: var(--wm-text-tertiary, #9a9388);
    line-height: 1.25;
}

/* 6. FAQ 手风琴 */
.consult-faq {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
    margin-top: 4rpx;
}

.consult-faq__head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    padding: 0 4rpx;
}

.consult-faq__title {
    font-size: 27rpx;
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
}

.consult-faq__desc {
    font-size: 20rpx;
    color: var(--wm-text-tertiary, #9a9388);
}

.consult-faq__list {
    display: flex;
    flex-direction: column;
    gap: 10rpx;
}

.consult-faq-item {
    border-radius: 20rpx;
    background: rgba(255, 253, 248, 0.95);
    border: 1rpx solid rgba(216, 201, 173, 0.7);
    padding: 18rpx 22rpx;
    box-sizing: border-box;
    transition: border-color 0.2s ease;

    &.is-open {
        border-color: rgba(217, 190, 130, 0.95);
    }
}

.consult-faq-item__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14rpx;
}

.consult-faq-item__q {
    flex: 1;
    font-size: 24rpx;
    line-height: 1.4;
    font-weight: 800;
    color: var(--wm-text-primary, #191713);
}

.consult-faq-item__arrow {
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s ease;

    &.is-rotated {
        transform: rotate(90deg);
    }
}

.consult-faq-item__body {
    margin-top: 12rpx;
    padding-top: 12rpx;
    border-top: 1rpx dashed rgba(216, 201, 173, 0.65);
}

.consult-faq-item__a {
    font-size: 22rpx;
    line-height: 1.55;
    color: var(--wm-text-secondary, #5f5a50);
}

@media (max-width: 360px) {
    .consult-info-grid,
    .consult-guarantees__grid {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
