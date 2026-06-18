<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer">
        <BaseNavbar
            title="联系顾问"
            variant="solid"
            bg-color="#000000"
            text-color="#FFFDF8"
        />

        <view class="consult-page wm-page-content">
            <view class="consult-shell">
                <BaseCard v-if="state.loading" variant="list" scene="consumer" padding="0">
                    <LoadingState
                        text="匹配顾问中..."
                        tone="wedding"
                        compact
                    />
                </BaseCard>

                <BaseCard v-else-if="state.error" variant="list" scene="consumer" padding="0">
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
                    <BaseCard variant="dark" scene="consumer" class="advisor-card" padding="0">
                        <view class="advisor-card__inner">
                            <view class="advisor-header">
                                <image
                                    v-if="contact.avatar"
                                    class="advisor-avatar"
                                    :src="contact.avatar"
                                    mode="aspectFill"
                                />
                                <view v-else class="advisor-avatar avatar-placeholder">
                                    {{ displayInitial }}
                                </view>

                                <view class="advisor-main">
                                    <view class="advisor-meta-row">
                                        <StatusBadge tone="warning" size="xs">
                                            {{ advisorBadgeText }}
                                        </StatusBadge>
                                    </view>

                                    <text class="advisor-name">{{ displayName }}</text>

                                    <text class="advisor-role">{{ displayRole }}</text>

                                    <text v-if="contact.service_time" class="advisor-service-time">
                                        服务时间：{{ contact.service_time }}
                                    </text>
                                </view>
                            </view>
                        </view>
                    </BaseCard>

                    <BaseCard variant="list" scene="consumer" class="qr-card" padding="0">
                        <view class="qr-card__inner">
                            <view class="qr-card__header">
                                <text class="qr-card__title">{{ qrTitle }}</text>
                            </view>

                            <view v-if="contact.contact_qr_code" class="qr-frame">
                                <image
                                    class="qr-image"
                                    :src="contact.contact_qr_code"
                                    mode="aspectFit"
                                    show-menu-by-longpress
                                />
                            </view>

                            <EmptyState
                                v-else
                                title="暂无二维码"
                                tone="neutral"
                                icon="service"
                                compact
                            />
                        </view>
                    </BaseCard>

                    <view v-if="hasAnyContactAction" class="action-list">
                        <BaseButton
                            v-if="contact.wechat_alias"
                            label="复制企微号"
                            icon="wechat-fill"
                            variant="dark"
                            size="lg"
                            block
                            @click="copyWechatAlias"
                        />

                        <view
                            v-if="contact.mobile || contact.contact_link"
                            class="action-grid"
                            :class="{
                                'action-grid--single': !(contact.mobile && contact.contact_link)
                            }"
                        >
                            <BaseButton
                                v-if="contact.mobile"
                                label="拨打电话"
                                icon="phone"
                                variant="light"
                                size="md"
                                block
                                @click="handleCall"
                            />

                            <BaseButton
                                v-if="contact.contact_link"
                                label="打开联系入口"
                                icon="arrow-right"
                                variant="secondary"
                                size="md"
                                block
                                @click="openContactLink"
                            />
                        </view>
                    </view>

                    <EmptyState
                        v-else
                        title="暂无联系方式"
                        tone="neutral"
                        icon="service"
                        compact
                    />

                    <BaseCard
                        v-if="contactInfoList.length"
                        variant="list"
                        scene="consumer"
                        class="contact-info-card"
                        padding="0"
                    >
                        <view class="contact-info-card__inner">
                            <view class="section-heading">
                                <text class="section-heading__title">联系信息</text>
                            </view>

                            <view class="contact-info-list">
                                <view
                                    v-for="item in contactInfoList"
                                    :key="item.label"
                                    class="contact-row"
                                >
                                    <text class="contact-row__label">{{ item.label }}</text>
                                    <text
                                        class="contact-row__value"
                                        :class="{ 'contact-row__value--link': item.isLink }"
                                    >
                                        {{ item.value }}
                                    </text>
                                </view>
                            </view>
                        </view>
                    </BaseCard>

                    <BaseCard
                        v-if="contact.tips"
                        variant="quiet"
                        scene="consumer"
                        class="tips-card"
                        padding="0"
                    >
                        <view class="tips-card__inner">
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
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { startConsult } from '@/packages/common/api/customerService'
import { useThemeStore } from '@/stores/theme'
import { showError, showSuccess } from '@/utils/feedback'

const $theme = useThemeStore()

const query = reactive({
    scene: 'home' as 'home' | 'staff_detail' | 'order_detail' | 'aftersale' | 'package_detail',
    staff_id: 0,
    order_id: 0,
    category_id: 0
})

const state = reactive({
    loading: true,
    error: '',
    entryType: 'fallback' as 'advisor' | 'fallback',
    contact: {
        name: '',
        role: '',
        avatar: '',
        mobile: '',
        wechat_alias: '',
        contact_qr_code: '',
        contact_link: '',
        service_time: '',
        tips: ''
    }
})

const contact = computed(() => state.contact)

const advisorBadgeText = computed(() => (state.entryType === 'advisor' ? '专属顾问' : '统一客服'))

const displayName = computed(() => String(contact.value.name || '').trim() || '婚礼顾问')

const displayRole = computed(() => String(contact.value.role || '').trim() || '服务咨询')

const displayInitial = computed(() => displayName.value.slice(0, 1) || '顾')

const qrTitle = computed(() =>
    state.entryType === 'advisor' ? '顾问二维码' : '客服二维码'
)

const contactInfoList = computed(() => {
    const list: Array<{ label: string; value: string; isLink?: boolean }> = []

    if (contact.value.mobile) {
        list.push({ label: '手机号', value: contact.value.mobile })
    }

    if (contact.value.wechat_alias) {
        list.push({ label: '企微号', value: contact.value.wechat_alias })
    }

    if (contact.value.contact_link) {
        list.push({ label: '联系入口', value: '已配置', isLink: true })
    }

    return list
})

const hasAnyContactAction = computed(
    () => Boolean(contact.value.wechat_alias || contact.value.mobile || contact.value.contact_link)
)

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
            mobile: data.contact?.mobile || '',
            wechat_alias: data.contact?.wechat_alias || '',
            contact_qr_code: data.contact?.contact_qr_code || '',
            contact_link: data.contact?.contact_link || '',
            service_time: data.contact?.service_time || '',
            tips: data.contact?.tips || ''
        }
    } catch (error: any) {
        state.error = error?.message || '加载失败，请稍后重试'
    } finally {
        state.loading = false
    }
}

const copyWechatAlias = () => {
    const wechatAlias = String(contact.value.wechat_alias || '').trim()
    if (!wechatAlias) {
        showError('暂无可复制企微号')
        return
    }

    uni.setClipboardData({
        data: wechatAlias,
        success: () => {
            showSuccess('企微号已复制')
        },
        fail: () => {
            showError('复制失败，请长按企微号手动复制')
        }
    })
}

const handleCall = () => {
    if (!contact.value.mobile) return
    uni.makePhoneCall({
        phoneNumber: contact.value.mobile
    })
}

const openContactLink = () => {
    const contactLink = String(contact.value.contact_link || '').trim()
    if (!contactLink) return
    // #ifdef H5
    window.open(contactLink, '_blank')
    // #endif

    // #ifndef H5
    uni.setClipboardData({
        data: contactLink,
        success: () => {
            showSuccess('链接已复制')
        },
        fail: () => {
            showError('复制失败，请长按联系入口手动复制')
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
    padding: 16rpx 24rpx 34rpx;
    box-sizing: border-box;
    background:
        radial-gradient(circle at 16% 0%, rgba(217, 190, 130, 0.2) 0, transparent 34%),
        linear-gradient(180deg, #fffdf8 0%, #f8f4ea 100%);
}

.consult-shell {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.advisor-card__inner {
    padding: 24rpx;
}

.advisor-header {
    display: flex;
    align-items: center;
    gap: 20rpx;
    min-width: 0;
}

.advisor-avatar {
    flex-shrink: 0;
    width: 108rpx;
    height: 108rpx;
    border-radius: 28rpx;
    border: 1rpx solid rgba(217, 190, 130, 0.72);
    background: rgba(255, 253, 248, 0.12);
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
    gap: 7rpx;
}

.advisor-meta-row {
    display: flex;
    align-items: center;
    min-width: 0;
}

.advisor-name {
    display: block;
    max-width: 100%;
    font-size: 37rpx;
    line-height: 1.15;
    font-weight: 900;
    color: #fffdf8;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.advisor-role,
.advisor-service-time {
    display: block;
    max-width: 100%;
    font-size: 23rpx;
    line-height: 1.35;
    color: rgba(255, 253, 248, 0.72);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.qr-card__inner,
.contact-info-card__inner {
    padding: 22rpx;
}

.qr-card__header,
.section-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    min-width: 0;
}

.qr-card__title,
.section-heading__title {
    display: block;
    font-size: 30rpx;
    line-height: 1.2;
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.qr-frame {
    margin-top: 18rpx;
    padding: 14rpx;
    border-radius: 26rpx;
    background:
        linear-gradient(180deg, rgba(255, 253, 248, 0.98), rgba(248, 244, 234, 0.98));
    border: 1rpx solid rgba(217, 190, 130, 0.72);
    box-shadow: inset 0 0 0 6rpx rgba(241, 229, 200, 0.34);
}

.qr-card :deep(.empty-state-block) {
    margin-top: 22rpx;
    min-height: 260rpx;
}

.qr-image {
    display: block;
    width: 100%;
    height: 408rpx;
    border-radius: 20rpx;
    background: #fffdf8;
}

.contact-info-list {
    display: flex;
    flex-direction: column;
    gap: 10rpx;
    margin-top: 16rpx;
}

.contact-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18rpx;
    min-height: 60rpx;
    padding: 0 16rpx;
    border-radius: 18rpx;
    background: rgba(255, 253, 248, 0.78);
    border: 1rpx solid rgba(216, 201, 173, 0.72);
}

.contact-row__label {
    flex-shrink: 0;
    font-size: 23rpx;
    line-height: 1.2;
    font-weight: 800;
    color: var(--wm-text-secondary, #665e52);
}

.contact-row__value {
    min-width: 0;
    flex: 1;
    font-size: 24rpx;
    line-height: 1.2;
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
    text-align: right;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.contact-row__value--link {
    color: var(--wm-color-clay, #9a6b35);
}

.action-list {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
}

.action-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12rpx;
}

.action-grid--single {
    grid-template-columns: minmax(0, 1fr);
}

.action-list :deep(.base-button__text) {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.tips-card__inner {
    padding: 18rpx 22rpx;
    border-radius: 22rpx;
    background: rgba(255, 253, 248, 0.84);
    border: 1rpx solid rgba(216, 201, 173, 0.72);
}

.tips-card__text {
    display: block;
    font-size: 24rpx;
    line-height: 1.5;
    color: var(--wm-text-secondary, #665e52);
}

@media (max-width: 360px) {
    .consult-page {
        padding-left: 20rpx;
        padding-right: 20rpx;
    }

    .advisor-card__inner,
    .qr-card__inner,
    .contact-info-card__inner {
        padding: 20rpx;
    }

    .advisor-avatar {
        width: 96rpx;
        height: 96rpx;
        border-radius: 24rpx;
    }

    .advisor-name {
        font-size: 34rpx;
    }

    .qr-image {
        height: 360rpx;
    }
}
</style>
