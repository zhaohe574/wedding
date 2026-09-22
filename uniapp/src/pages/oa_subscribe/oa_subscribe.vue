<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" tone="workspace" hasSafeBottom>
        <BaseNavbar title="服务号通知" variant="solid" @back="goBack" />
        <view class="binding-page">
            <view class="hero">
                <view class="hero__eyebrow"><BaseIcon name="notice" size="32" color="#D9BE82" /><text>让重要提醒，及时看见</text></view>
                <text class="hero__title">{{ status.can_receive ? '关注与绑定已完成' : invitation ? '确认账号，开启提醒' : '在微信里，接收重要提醒' }}</text>
                <text class="hero__description">订单进展、档期安排与工作事项，多一个接收提醒的方式。</text>
                <view class="hero__tags"><text>订单进展</text><text>档期安排</text><text>工作提醒</text></view>
            </view>
            <view class="card status-card">
                <view class="section-heading"><text class="title">当前状态</text><text class="status-hint">{{ busy.status ? '正在更新…' : '关注与绑定分别确认' }}</text></view>
                <view class="status-grid">
                    <view class="status-item"><text class="status-item__label">微信关注</text><text class="status-item__value" :class="{ 'is-complete': status.follow_status === 'followed' }">{{ followLabel }}</text></view>
                    <view class="status-item"><text class="status-item__label">平台账号绑定</text><text class="status-item__value" :class="{ 'is-complete': status.bound }">{{ status.bound ? '已绑定' : '未绑定' }}</text></view>
                </view>
                <text v-if="!status.channel_available" class="channel-note">平台通知暂不可用。您的绑定会保留，站内消息不受影响。</text>
                <text v-else-if="status.can_receive" class="muted">已具备服务号提醒接收资格，具体送达以微信和平台通知状态为准。</text>
            </view>
            <view v-if="invitation && inviteState.state !== 'completed'" class="card invitation-card">
                <view class="section-heading"><text class="title">确认本人账号</text><text class="invitation-badge">专属绑定邀请</text></view>
                <view class="account-panel"><view class="account-avatar"><BaseIcon name="my" size="38" color="#8A6936" /></view><view class="account-info"><text class="account-name">{{ user.userInfo.nickname || '已登录用户' }}</text><text class="muted">{{ maskedMobile || '当前登录的平台账号' }}</text></view></view>
                <text class="muted">{{ inviteState.message }}</text>
                <text v-if="inviteState.can_confirm" class="privacy-note">请确认这是您本人从服务号打开的邀请，不要使用他人转发的入口。</text>
                <BaseButton v-if="inviteState.can_confirm" block :loading="busy.confirm" :disabled="busy.status" @click="confirmBinding">确认绑定并开启通知</BaseButton>
            </view>
            <view v-if="!status.can_receive && !inviteState.can_confirm" class="card">
                <text class="title">{{ status.bound ? '重新关注，即可恢复接收' : '三步开启服务号提醒' }}</text>
                <view class="official-account"><view class="account-info"><text class="account-name">{{ status.official_name || '平台服务号' }}</text><text v-if="status.official_account" class="muted">微信号：{{ status.official_account }}</text></view><view v-if="status.official_account || status.official_name" class="copy-action" hover-class="action-pressed" @click="copyAccount">复制{{ status.official_account ? '微信号' : '名称' }}</view></view>
                <text v-if="status.bound" class="muted">在微信搜索并重新关注上述服务号。原账号绑定已保留，无需重复绑定，也不会补发历史提醒。</text>
                <view v-else class="steps">
                    <view class="step"><text class="step__number">1</text><view class="step__body"><text class="step__title">在微信搜索并关注服务号</text><text class="muted">可复制上方微信号或名称进行搜索。</text></view></view>
                    <view class="step"><text class="step__number">2</text><view class="step__body"><text class="step__title">从服务号打开绑定邀请</text><text class="muted">点击欢迎消息或菜单中的“绑定账号”。</text></view></view>
                    <view class="step"><text class="step__number">3</text><view class="step__body"><text class="step__title">回到小程序，确认本人账号</text><text class="muted">登录后点击“确认绑定并开启通知”。</text></view></view>
                </view>
                <text v-if="!status.bound" class="help-note">找不到菜单或邀请过期？在服务号发送“绑定账号”，重新获取专属入口。</text>
            </view>
            <text v-if="error" class="error-text">{{ error }}</text>
            <view class="page-actions">
                <BaseButton block :variant="status.can_receive ? 'primary' : 'light'" @click="goBack">{{ status.can_receive ? '完成，返回' : '暂不设置，返回' }}</BaseButton>
                <view class="secondary-actions"><view class="text-action" @click="refresh">{{ busy.status ? '正在刷新…' : '刷新状态' }}</view><view v-if="status.bound" class="text-action text-action--muted" @click="unbind">{{ busy.unbind ? '正在处理…' : '解除微信绑定' }}</view></view>
                <text class="footer-note">关注由您选择，站内消息始终保留</text>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { onLoad, onShow, onHide, onUnload } from '@dcloudio/uni-app'
import PageShell from '@/components/base/PageShell.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import { oaSubscribeStatus, oaSubscribeUnbind, oaInvitationStatus, oaInvitationConfirm } from '@/api/app'
import { useThemeStore } from '@/stores/theme'
import { useUserStore } from '@/stores/user'
import { getOaInvitation, captureOaInvitation, clearOaInvitation, OA_BINDING_PATH } from '@/utils/oa-invitation'
import { showError, showSuccess, confirmModal } from '@/utils/feedback'
const $theme = useThemeStore()
const user = useUserStore()
const status = reactive({ bound: false, follow_status: 'unknown', can_receive: false, channel_available: true, official_name: '', official_account: '' })
const inviteState = reactive({ state: 'loading', can_confirm: false, message: '正在读取绑定邀请…', expires_time: 0 })
const invitation = ref('')
const busy = reactive({ status: false, confirm: false, unbind: false })
const error = ref('')
let generation = 0
let visible = false
let expiryTimer: ReturnType<typeof setTimeout> | undefined
const followLabel = computed(() => ({ followed: '已关注', unfollowed: '未关注', unknown: '待确认' }[status.follow_status] || '待确认'))
const maskedMobile = computed(() => String(user.userInfo.mobile || '').replace(/^(\d{3})\d{4}(\d{4})$/, '$1****$2'))
const applyInvitation = (data: any) => {
    Object.assign(inviteState, data)
    if (data.binding) Object.assign(status, data.binding)
    clearTimeout(expiryTimer)
    if (data.state === 'completed') { clearOaInvitation(); invitation.value = ''; return }
    if (data.can_confirm && data.expires_time) expiryTimer = setTimeout(() => {
        inviteState.can_confirm = false; inviteState.state = 'expired'
        inviteState.message = '入口已过期，请回服务号点击“绑定账号”重新获取。'
    }, Math.max(0, data.expires_time * 1000 - Date.now()))
}
const refresh = async () => {
    if (busy.status || !user.isLogin) return
    const version = generation
    const userToken = user.token
    busy.status = true; inviteState.can_confirm = false
    try {
        const data = await oaSubscribeStatus()
        if (version !== generation || userToken !== user.token) return
        Object.assign(status, data)
        if (invitation.value) {
            const state = await oaInvitationStatus(invitation.value)
            if (version !== generation || userToken !== user.token) return
            applyInvitation(state)
        }
        error.value = ''
        uni.$emit?.('oa_binding_changed', { bound: !!status.bound })
    } catch { if (version === generation && userToken === user.token) error.value = '状态读取失败，请稍后刷新重试。' }
    finally { busy.status = false; if (version !== generation && visible) void refresh() }
}
const confirmBinding = async () => {
    if (busy.confirm || !inviteState.can_confirm) return
    busy.confirm = true
    const version = generation
    const userToken = user.token
    try {
        const result = await oaInvitationConfirm(invitation.value)
        if (version !== generation || userToken !== user.token) return
        applyInvitation(result); showSuccess('绑定已完成')
        uni.$emit?.('oa_binding_changed', { bound: true })
    }
    catch { if (version === generation && userToken === user.token) { inviteState.can_confirm = false; await refresh(); showError('暂未完成绑定，请查看状态后重试。') } }
    finally { busy.confirm = false }
}
const unbind = async () => {
    if (busy.unbind) return
    busy.unbind = true
    try {
        if (!await confirmModal({ title: '解除绑定', content: '解除后停止服务号通知，站内消息保留。工作身份不受影响。' })) return
        await oaSubscribeUnbind(); clearOaInvitation(); invitation.value = ''; await refresh()
        uni.$emit?.('oa_binding_changed', { bound: false })
    } catch { showError('解除绑定失败，请稍后重试。') } finally { busy.unbind = false }
}
const copyAccount = () => uni.setClipboardData({ data: status.official_account || status.official_name })
const goBack = () => {
    clearOaInvitation()
    const pages = getCurrentPages()
    const skipped = [OA_BINDING_PATH, '/pages/login/login', '/pages/bind_mobile/bind_mobile', '/packages/pages/404/404']
    const fallback = () => uni.switchTab({ url: '/pages/user/user', fail: () => uni.reLaunch({ url: '/pages/user/user' }) })
    // 跳过登录中转和重复邀请页；服务号直接进入时回到个人中心。
    for (let index = pages.length - 2; index >= 0; index--) {
        const route = '/' + String(pages[index].route || '').replace(/^\//, '')
        if (route === '/' || skipped.includes(route)) continue
        uni.navigateBack({ delta: pages.length - 1 - index, fail: fallback })
        return
    }
    fallback()
}
onLoad((query: any) => { captureOaInvitation(OA_BINDING_PATH, query || {}); invitation.value = getOaInvitation() })
onShow(() => { visible = true; invitation.value = getOaInvitation() || invitation.value; generation++; void refresh() })
onHide(() => { visible = false; generation++; clearTimeout(expiryTimer) })
onUnload(() => { visible = false; generation++; clearTimeout(expiryTimer) })
</script>

<style scoped>
.binding-page { padding: 28rpx 28rpx 40rpx; display: flex; flex-direction: column; gap: 24rpx; }
.hero,.card { display: flex; flex-direction: column; gap: 24rpx; padding: 30rpx; border-radius: 30rpx; }
.hero { padding: 36rpx 32rpx; background: linear-gradient(135deg, #302a20, #191713); border: 1rpx solid #685638; box-shadow: 0 14rpx 32rpx rgba(48, 42, 32, .12); }
.hero__eyebrow { display: flex; align-items: center; gap: 14rpx; color: #d9be82; font-size: 23rpx; letter-spacing: 2rpx; }
.hero__title { font-size: 40rpx; font-weight: 600; color: #fffdf8; line-height: 1.45; }
.hero__description { font-size: 26rpx; line-height: 1.8; color: #d5cebf; }
.hero__tags { display: flex; flex-wrap: wrap; gap: 14rpx; }
.hero__tags text { padding: 8rpx 18rpx; border-radius: 12rpx; background: rgba(217, 190, 130, .1); color: #e7d4ad; font-size: 22rpx; }
.card { background: #fffdf8; border: 1rpx solid #e7ddce; box-shadow: 0 6rpx 20rpx rgba(74, 43, 24, .03); }
.title { font-size: 29rpx; font-weight: 600; color: #302b24; }
.section-heading { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12rpx; }
.status-hint { font-size: 21rpx; color: #8a8174; }
.status-grid { display: flex; border-radius: 20rpx; background: #f6f2eb; padding: 24rpx 0; }
.status-item { flex: 1; display: flex; flex-direction: column; gap: 12rpx; padding: 0 24rpx; }
.status-item + .status-item { border-left: 1rpx solid #e2d8c9; }
.status-item__label { font-size: 23rpx; color: #817668; }
.status-item__value { font-size: 30rpx; color: #51493d; font-weight: 600; }
.is-complete { color: #3c7358; }
.muted { color: #7a7165; font-size: 24rpx; line-height: 1.75; }
.channel-note,.help-note { padding: 20rpx 22rpx; background: #f6f0e4; color: #806337; font-size: 23rpx; line-height: 1.8; border-radius: 16rpx; }
.invitation-card { border-color: #d9be82; }
.invitation-badge { font-size: 21rpx; background: #f3e9d5; color: #8a6936; border-radius: 10rpx; padding: 7rpx 12rpx; }
.account-panel,.official-account { display: flex; align-items: center; gap: 20rpx; padding: 24rpx; background: #f7f3ec; border-radius: 20rpx; }
.account-avatar { width: 72rpx; height: 72rpx; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: #ece1cc; border-radius: 50%; }
.account-info { display: flex; flex-direction: column; gap: 8rpx; flex: 1; min-width: 0; overflow-wrap: anywhere; }
.account-name { font-size: 28rpx; font-weight: 600; color: #3d3428; }
.privacy-note { font-size: 23rpx; color: #8a7760; line-height: 1.7; }
.copy-action { flex-shrink: 0; padding: 16rpx 14rpx; font-size: 22rpx; font-weight: 600; color: #89672f; border: 1rpx solid #dacaad; border-radius: 14rpx; }
.steps { display: flex; flex-direction: column; gap: 26rpx; }
.step { display: flex; gap: 20rpx; }
.step__number { width: 42rpx; height: 42rpx; flex-shrink: 0; display: flex; align-items: center; justify-content: center; border-radius: 50%; background: #eee3cf; color: #8a6936; font-size: 23rpx; font-weight: 600; }
.step__body { display: flex; flex-direction: column; gap: 6rpx; padding-top: 2rpx; }
.step__title { font-size: 26rpx; font-weight: 500; color: #473e32; }
.page-actions { display: flex; flex-direction: column; gap: 10rpx; }
.secondary-actions { display: flex; align-items: center; justify-content: center; gap: 24rpx; flex-wrap: wrap; }
.text-action { padding: 22rpx 16rpx; font-size: 24rpx; color: #896b3f; }
.text-action--muted { color: #928779; }
.footer-note { text-align: center; color: #9a9083; font-size: 22rpx; line-height: 1.7; }
.action-pressed { opacity: .65; }
.error-text { padding: 20rpx 24rpx; border-radius: 16rpx; background: #fff0ed; color: #b42318; font-size: 24rpx; line-height: 1.7; }
</style>
