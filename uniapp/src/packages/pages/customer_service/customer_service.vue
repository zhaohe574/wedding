<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer">
        <BaseNavbar title="联系顾问" />

        <view class="consult-page">
            <view class="consult-shell">
                <view class="consult-hero">
                    <view class="consult-badge">{{
                        state.entryType === 'advisor' ? '专属顾问' : '统一客服'
                    }}</view>
                    <view class="consult-title">{{
                        state.entryType === 'advisor' ? '联系顾问' : '联系客服'
                    }}</view>
                    <view class="consult-subtitle">{{ sceneTitle }}</view>
                </view>

                <view v-if="state.loading" class="loading-card">
                    <tn-icon name="loading" size="40" :color="$theme.primaryColor" />
                    <text>正在匹配顾问信息...</text>
                </view>

                <view v-else-if="state.error" class="error-card">
                    <tn-icon name="warning" size="40" color="#F56C6C" />
                    <text class="error-text">{{ state.error }}</text>
                    <view class="retry-btn" @click="loadConsultContact">重试</view>
                </view>

                <view v-else class="contact-card">
                    <view class="advisor-header">
                        <image
                            v-if="contact.avatar"
                            class="advisor-avatar"
                            :src="contact.avatar"
                            mode="aspectFill"
                        />
                        <view v-else class="advisor-avatar avatar-placeholder">
                            {{ contact.name?.slice(0, 1) || '顾' }}
                        </view>
                        <view class="advisor-main">
                            <view class="advisor-name">{{ contact.name }}</view>
                            <view class="advisor-role">{{ contact.role }}</view>
                            <view v-if="contact.service_time" class="advisor-service-time">
                                服务时间：{{ contact.service_time }}
                            </view>
                        </view>
                    </view>

                    <view class="contact-panel">
                        <view class="contact-panel-title">联系信息</view>
                        <view class="contact-row" v-if="contact.mobile">
                            <text class="label">手机号</text>
                            <text class="value">{{ contact.mobile }}</text>
                        </view>
                        <view class="contact-row" v-if="contact.wechat_alias">
                            <text class="label">企微号</text>
                            <text class="value">{{ contact.wechat_alias }}</text>
                        </view>
                        <view class="contact-row" v-if="contact.contact_link">
                            <text class="label">联系入口</text>
                            <text class="value value-link">已配置</text>
                        </view>
                    </view>

                    <view class="qr-section">
                        <view class="qr-title">
                            {{
                                state.entryType === 'advisor' ? '专属顾问二维码' : '统一客服二维码'
                            }}
                        </view>
                        <image
                            v-if="contact.contact_qr_code"
                            class="qr-image"
                            :src="contact.contact_qr_code"
                            mode="aspectFit"
                            show-menu-by-longpress
                        />
                        <view v-else class="qr-empty">暂未配置二维码，请使用下方联系方式</view>
                    </view>

                    <view class="action-list">
                        <view
                            v-if="contact.wechat_alias"
                            class="action-btn primary"
                            @click="copyWechatAlias"
                        >
                            复制企微号
                        </view>
                        <view
                            v-if="contact.mobile"
                            class="action-btn secondary"
                            @click="handleCall"
                        >
                            拨打电话
                        </view>
                        <view
                            v-if="contact.contact_link"
                            class="action-btn secondary"
                            @click="openContactLink"
                        >
                            打开联系入口
                        </view>
                    </view>

                    <view class="tips-card">
                        <view class="tips-title">联系说明</view>
                        <view class="tips-text">{{ contact.tips || defaultTips }}</view>
                    </view>
                </view>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, reactive } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { startConsult } from '@/api/customerService'
import { useThemeStore } from '@/stores/theme'

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

const sceneTitleMap: Record<string, string> = {
    home: '首页咨询',
    staff_detail: '人员咨询',
    order_detail: '订单咨询',
    aftersale: '售后咨询',
    package_detail: '套餐咨询'
}

const defaultTips = '如需帮助，请直接联系'

const sceneTitle = computed(() => sceneTitleMap[query.scene] || sceneTitleMap.home)
const contact = computed(() => state.contact)

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
        state.error = error?.message || '顾问信息加载失败，请稍后重试'
    } finally {
        state.loading = false
    }
}

const copyWechatAlias = () => {
    if (!contact.value.wechat_alias) return
    uni.setClipboardData({
        data: contact.value.wechat_alias,
        success: () => {
            uni.showToast({ title: '企微号已复制', icon: 'success' })
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
    if (!contact.value.contact_link) return
    // #ifdef H5
    window.open(contact.value.contact_link, '_blank')
    // #endif

    // #ifndef H5
    uni.setClipboardData({
        data: contact.value.contact_link,
        success: () => {
            uni.showToast({ title: '联系链接已复制', icon: 'none' })
        }
    })
    // #endif
}

onLoad((options: Record<string, string>) => {
    query.scene = (options.scene as typeof query.scene) || 'home'
    query.staff_id = Number(options.staff_id || 0)
    query.order_id = Number(options.order_id || 0)
    query.category_id = Number(options.category_id || 0)
    loadConsultContact()
})
</script>

<style lang="scss" scoped>
.consult-page {
    min-height: 100vh;
    background: radial-gradient(circle at top, rgba(229, 107, 111, 0.16), transparent 38%),
        linear-gradient(180deg, #fffaf8 0%, #f5f7fb 100%);
    padding: 24rpx;
    box-sizing: border-box;
}

.consult-shell {
    display: flex;
    flex-direction: column;
    gap: 24rpx;
}

.consult-hero {
    padding: 40rpx 32rpx;
    border-radius: 28rpx;
    background: linear-gradient(135deg, #fff6ef 0%, #ffffff 100%);
    box-shadow: 0 20rpx 48rpx rgba(54, 67, 94, 0.08);
}

.consult-badge {
    display: inline-flex;
    align-items: center;
    padding: 10rpx 18rpx;
    border-radius: 999rpx;
    background: rgba(229, 107, 111, 0.12);
    color: #d85c61;
    font-size: 22rpx;
    font-weight: 600;
}

.consult-title {
    margin-top: 18rpx;
    font-size: 40rpx;
    font-weight: 700;
    color: #1f2937;
}

.consult-subtitle {
    margin-top: 12rpx;
    font-size: 25rpx;
    line-height: 1.7;
    color: #6b7280;
}

.loading-card,
.error-card,
.contact-card {
    border-radius: 28rpx;
    background: #ffffff;
    box-shadow: 0 20rpx 48rpx rgba(54, 67, 94, 0.08);
}

.loading-card,
.error-card {
    padding: 80rpx 32rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20rpx;
    color: #6b7280;
}

.error-text {
    font-size: 26rpx;
    line-height: 1.6;
}

.retry-btn {
    margin-top: 8rpx;
    padding: 18rpx 40rpx;
    border-radius: 999rpx;
    background: #1f2937;
    color: #ffffff;
    font-size: 26rpx;
}

.contact-card {
    padding: 32rpx;
}

.advisor-header {
    display: flex;
    align-items: center;
    gap: 24rpx;
}

.advisor-avatar {
    width: 112rpx;
    height: 112rpx;
    border-radius: 28rpx;
    flex-shrink: 0;
}

.avatar-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f7c2b2 0%, #f0a1a4 100%);
    color: #ffffff;
    font-size: 42rpx;
    font-weight: 700;
}

.advisor-main {
    flex: 1;
    min-width: 0;
}

.advisor-name {
    font-size: 38rpx;
    font-weight: 700;
    color: #1f2937;
}

.advisor-role,
.advisor-service-time {
    margin-top: 8rpx;
    font-size: 24rpx;
    color: #6b7280;
}

.contact-panel {
    margin-top: 28rpx;
    padding: 24rpx;
    border-radius: 24rpx;
    background: #f8fafc;
}

.contact-panel-title,
.tips-title,
.qr-title {
    font-size: 28rpx;
    font-weight: 600;
    color: #1f2937;
}

.contact-row {
    margin-top: 16rpx;
    display: flex;
    justify-content: space-between;
    gap: 20rpx;
}

.label {
    font-size: 25rpx;
    color: #6b7280;
}

.value {
    font-size: 25rpx;
    color: #1f2937;
    text-align: right;
    word-break: break-all;
}

.value-link {
    color: #d85c61;
}

.qr-section {
    margin-top: 28rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.qr-image {
    width: 360rpx;
    height: 360rpx;
    margin-top: 24rpx;
    border-radius: 24rpx;
    background: #ffffff;
    border: 2rpx solid #f1f5f9;
}

.qr-empty {
    margin-top: 24rpx;
    width: 100%;
    padding: 28rpx 24rpx;
    border-radius: 24rpx;
    background: #f8fafc;
    font-size: 25rpx;
    line-height: 1.6;
    text-align: center;
    color: #94a3b8;
}

.action-list {
    margin-top: 32rpx;
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.action-btn {
    height: 92rpx;
    border-radius: 999rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28rpx;
    font-weight: 600;
}

.action-btn.primary {
    background: linear-gradient(135deg, #df6c63 0%, #e98d69 100%);
    color: #ffffff;
}

.action-btn.secondary {
    background: #f8fafc;
    color: #1f2937;
    border: 2rpx solid #e2e8f0;
}

.tips-card {
    margin-top: 28rpx;
    padding: 24rpx;
    border-radius: 24rpx;
    background: rgba(229, 107, 111, 0.08);
}

.tips-text {
    margin-top: 12rpx;
    font-size: 25rpx;
    line-height: 1.7;
    color: #4b5563;
}
</style>
