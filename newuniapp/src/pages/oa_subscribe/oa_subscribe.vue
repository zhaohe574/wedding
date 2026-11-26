<template>
    <!-- 服务号通知与专属邀请确认页（登录页中转后由 BACK_URL 纯路径回到这里） -->
    <page-shell
        title="服务号通知"
        bg="page"
        :show-back="true"
        :pull-refresh="true"
        nav-back-mode="none"
        @nav-back="goBack"
        @refresh="onPullRefresh"
    >
        <view class="oa">
            <!-- 1. 深墨黑金状态头卡 -->
            <view class="hero">
                <view class="hero__top">
                    <view class="hero__pill" :class="statusPill.tone">
                        <tn-icon name="notice" :size="22" />
                        <text>{{ statusPill.text }}</text>
                    </view>
                    <view class="hero__refresh" @tap="refresh">
                        <text>{{ busy.status ? '同步中' : '刷新' }}</text>
                    </view>
                </view>

                <text class="hero__title">{{ heroTitle }}</text>

                <view class="hero__stats">
                    <view class="hero-stat">
                        <text class="hero-stat__label">微信关注</text>
                        <view class="hero-stat__value">
                            <view
                                class="hero-stat__dot"
                                :class="{ 'is-on': status.follow_status === 'followed' }"
                            />
                            <text
                                class="hero-stat__text"
                                :class="{ 'is-on': status.follow_status === 'followed' }"
                                >{{ followLabel }}</text
                            >
                        </view>
                    </view>
                    <view class="hero-stat">
                        <text class="hero-stat__label">账号绑定</text>
                        <view class="hero-stat__value">
                            <view class="hero-stat__dot" :class="{ 'is-on': status.bound }" />
                            <text class="hero-stat__text" :class="{ 'is-on': status.bound }">{{
                                status.bound ? '已绑定' : '未绑定'
                            }}</text>
                        </view>
                    </view>
                </view>

                <view v-if="!status.channel_available" class="hero__alert">
                    <tn-icon name="warning" :size="24" />
                    <text>通知通道暂不可用，站内消息正常</text>
                </view>
            </view>

            <!-- 2. 单一核心操作卡（按状态互斥呈现） -->
            <!-- 2.1 专属邀请确认 -->
            <view v-if="invitation && inviteState.state !== 'completed'" class="panel">
                <view class="panel__head">
                    <text class="panel__title">确认本人账号</text>
                    <text class="invite-badge">专属邀请</text>
                </view>

                <view class="account-tile">
                    <view class="account-tile__badge">
                        <text v-if="accountInitial">{{ accountInitial }}</text>
                        <tn-icon v-else name="wechat" :size="32" />
                    </view>
                    <view class="account-tile__info">
                        <text class="account-tile__name">{{ nickname || '已登录用户' }}</text>
                        <text class="account-tile__sub">{{ maskedMobile || '当前账号' }}</text>
                    </view>
                </view>

                <text class="note">{{ inviteState.message }}</text>

                <base-button
                    v-if="inviteState.can_confirm"
                    text="确认绑定并开启"
                    variant="ink"
                    size="lg"
                    :loading="busy.confirm"
                    :disabled="busy.status"
                    @click="confirmBinding"
                />
            </view>

            <!-- 2.2 引导关注 / 绑定 -->
            <view v-else-if="!status.can_receive" class="panel">
                <view class="oa-head">
                    <view class="oa-head__seal">
                        <tn-icon name="wechat" :size="44" />
                    </view>
                    <view class="oa-head__info">
                        <text class="oa-head__name">{{ status.official_name || '官方服务号' }}</text>
                        <text class="oa-head__account">微信服务号</text>
                    </view>
                </view>

                <base-button
                    class="oa-cta"
                    :text="primaryCtaText"
                    variant="ink"
                    size="lg"
                    @click="handlePrimaryAction"
                />

                <view v-if="hasQrCode" class="qr-box" :class="{ 'is-highlight': qrHighlighted }">
                    <view class="qr-box__frame">
                        <image
                            class="qr-box__img"
                            :src="status.official_qr_url"
                            mode="aspectFit"
                            :show-menu-by-longpress="true"
                            @tap="previewQrCode"
                        />
                    </view>
                    <text class="qr-box__tip">长按二维码识别关注，或点击放大</text>
                </view>

                <view
                    v-if="hasAccountText"
                    class="copy-row"
                    hover-class="is-pressed"
                    @tap="copyAccount('已复制微信号，请打开微信搜索关注')"
                >
                    <view class="copy-row__info">
                        <text class="copy-row__label">{{
                            status.official_account ? '微信号' : '服务号'
                        }}</text>
                        <text class="copy-row__val">{{
                            status.official_account || status.official_name
                        }}</text>
                    </view>
                    <view class="copy-row__btn">
                        <tn-icon name="copy" :size="24" />
                        <text>复制</text>
                    </view>
                </view>

                <view v-if="!status.bound" class="flow">
                    <view class="flow__step">
                        <view
                            class="flow__dot"
                            :class="{ 'is-done': status.follow_status === 'followed' }"
                        >
                            <tn-icon
                                v-if="status.follow_status === 'followed'"
                                name="check"
                                :size="22"
                            />
                            <text v-else>1</text>
                        </view>
                        <text class="flow__label">关注服务号</text>
                    </view>
                    <view class="flow__step">
                        <view class="flow__dot"><text>2</text></view>
                        <text class="flow__label">点「绑定账号」</text>
                    </view>
                    <view class="flow__step">
                        <view class="flow__dot"><text>3</text></view>
                        <text class="flow__label">确认绑定</text>
                    </view>
                </view>
                <text v-if="!status.bound" class="flow__tip">未见菜单可回复「绑定账号」</text>
                <text v-else class="note">原绑定已保留，重新关注即可恢复</text>
            </view>

            <!-- 2.3 已开启 -->
            <view v-else class="panel">
                <text class="panel__title">已开启以下提醒</text>
                <view class="benefits">
                    <view class="benefit">
                        <view class="benefit__icon"><tn-icon name="clock" :size="36" /></view>
                        <text class="benefit__label">订单进展</text>
                    </view>
                    <view class="benefit">
                        <view class="benefit__icon"><tn-icon name="calendar" :size="36" /></view>
                        <text class="benefit__label">档期安排</text>
                    </view>
                    <view class="benefit">
                        <view class="benefit__icon"><tn-icon name="notice" :size="36" /></view>
                        <text class="benefit__label">工作提醒</text>
                    </view>
                </view>
            </view>

            <!-- 3. 错误提示与极简底区 -->
            <view class="foot">
                <text v-if="error" class="error-text">{{ error }}</text>
                <base-button
                    v-if="status.can_receive"
                    text="完成"
                    variant="ink"
                    size="lg"
                    @click="goBack"
                />
                <text v-if="status.bound" class="foot__link" @tap="unbind">{{
                    busy.unbind ? '处理中…' : '解除绑定'
                }}</text>
            </view>
        </view>
    </page-shell>
</template>

<script setup lang="ts">
/**
 * 服务号绑定与状态页。
 *
 * 邀请凭据只存 oa-invitation 缓存（10 分钟），绝不写进 BACK_URL / 路由日志；
 * goBack 会跳过登录中转页与本页，避免在「登录→邀请→登录」里打转。
 */
import { computed, reactive, ref } from 'vue'
import { onLoad, onShow, onHide, onUnload } from '@dcloudio/uni-app'
import {
    confirmOaInvitation,
    getOaInvitationStatus,
    getOaSubscribeStatus,
    unbindOaSubscribe,
    type OaInvitationStatusResult
} from '@/api/user'
import { toast } from '@/composables/useToast'
import { useUserStore } from '@/stores/user'
import {
    captureOaInvitation,
    clearOaInvitation,
    getOaInvitation,
    OA_BINDING_PATH
} from '@/utils/oa-invitation'

declare const wx: any

const userStore = useUserStore()

const status = reactive({
    bound: false,
    follow_status: 'unknown' as 'followed' | 'unfollowed' | 'unknown',
    can_receive: false,
    channel_available: true,
    official_name: '',
    official_account: '',
    official_original_id: '',
    official_qr_url: ''
})
const inviteState = reactive({
    state: 'loading',
    can_confirm: false,
    message: '正在读取绑定邀请…',
    expires_time: 0
})
const invitation = ref('')
const busy = reactive({ status: false, confirm: false, unbind: false })
const error = ref('')
const qrHighlighted = ref(false)

/** 并发防串台：离页 / onShow 重入会递增，旧请求返回后按版本号丢弃 */
let generation = 0
let visible = false
let expiryTimer: ReturnType<typeof setTimeout> | undefined
let highlightTimer: ReturnType<typeof setTimeout> | undefined

const heroTitle = computed(() => {
    if (status.can_receive) return '微信提醒已开启'
    if (inviteState.can_confirm) return '确认绑定当前账号'
    if (status.bound) return '重新关注恢复提醒'
    return '开启服务号提醒'
})
const statusPill = computed(() => {
    if (status.can_receive) return { text: '已开启', tone: 'hero__pill--ok' }
    if (inviteState.can_confirm) return { text: '待确认绑定', tone: 'hero__pill--brand' }
    if (status.bound) return { text: '待重新关注', tone: 'hero__pill--warn' }
    return { text: '未开启', tone: '' }
})
const followLabel = computed(
    () => ({ followed: '已关注', unfollowed: '未关注', unknown: '待确认' })[status.follow_status] || '待确认'
)
const nickname = computed(() => String(userStore.userInfo?.nickname || ''))
const maskedMobile = computed(() =>
    String(userStore.userInfo?.mobile || '').replace(/^(\d{3})\d{4}(\d{4})$/, '$1****$2')
)
const accountInitial = computed(() => nickname.value.trim().slice(0, 1))

const hasOriginalId = computed(() => Boolean(String(status.official_original_id || '').trim()))
const hasAccountText = computed(() =>
    Boolean(String(status.official_account || status.official_name || '').trim())
)
const hasQrCode = computed(() => Boolean(String(status.official_qr_url || '').trim()))

/** 主按钮永不消失：按现有信息依次降级到可执行的下一步 */
const primaryCtaText = computed(() => {
    if (hasOriginalId.value) {
        if (status.bound) return '一键前往服务号重新关注'
        if (status.follow_status === 'followed') return '一键前往服务号绑定'
        return '一键前往服务号关注'
    }
    if (hasQrCode.value) return '识别二维码关注'
    if (hasAccountText.value) return '复制微信号去关注'
    return '刷新状态'
})

function clearExpiryTimer() {
    if (expiryTimer) {
        clearTimeout(expiryTimer)
        expiryTimer = undefined
    }
}

function clearHighlightTimer() {
    if (highlightTimer) {
        clearTimeout(highlightTimer)
        highlightTimer = undefined
    }
    qrHighlighted.value = false
}

/** 邀请状态落盘：completed 清凭据；可确认时挂过期定时器 */
function applyInvitation(data: Partial<OaInvitationStatusResult> & Record<string, any>) {
    Object.assign(inviteState, data)
    if (data.binding) Object.assign(status, data.binding)
    clearExpiryTimer()
    if (data.state === 'completed') {
        clearOaInvitation()
        invitation.value = ''
        return
    }
    if (data.can_confirm && data.expires_time) {
        expiryTimer = setTimeout(
            () => {
                inviteState.can_confirm = false
                inviteState.state = 'expired'
                inviteState.message = '入口已过期，请回服务号点击“绑定账号”重新获取。'
            },
            Math.max(0, data.expires_time * 1000 - Date.now())
        )
    }
}

async function refresh() {
    if (busy.status || !userStore.isLogin) return
    const version = generation
    const userToken = userStore.token
    busy.status = true
    inviteState.can_confirm = false
    try {
        const data = await getOaSubscribeStatus()
        if (version !== generation || userToken !== userStore.token) return
        Object.assign(status, data)
        if (invitation.value) {
            const state = await getOaInvitationStatus(invitation.value)
            if (version !== generation || userToken !== userStore.token) return
            applyInvitation(state)
        }
        error.value = ''
        uni.$emit('oa_binding_changed', { bound: !!status.bound })
    } catch {
        if (version === generation && userToken === userStore.token) {
            error.value = '状态读取失败，请稍后刷新重试。'
        }
    } finally {
        busy.status = false
        // 页面重新可见期间来过新的一轮 onShow：补一轮刷新拿最新状态
        if (version !== generation && visible) void refresh()
    }
}

async function confirmBinding() {
    if (busy.confirm || !inviteState.can_confirm) return
    busy.confirm = true
    const version = generation
    const userToken = userStore.token
    try {
        const result = await confirmOaInvitation(invitation.value)
        if (version !== generation || userToken !== userStore.token) return
        applyInvitation(result)
        toast.success('绑定已完成')
        uni.$emit('oa_binding_changed', { bound: true })
    } catch {
        if (version === generation && userToken === userStore.token) {
            inviteState.can_confirm = false
            await refresh()
            toast.error('暂未完成绑定，请查看状态后重试。')
        }
    } finally {
        busy.confirm = false
    }
}

async function unbind() {
    if (busy.unbind) return
    const confirmed = await new Promise<boolean>((resolve) => {
        uni.showModal({
            title: '解除绑定',
            content: '解除后停止服务号通知，站内消息保留。工作身份不受影响。',
            confirmText: '解除绑定',
            cancelText: '取消',
            success: (r) => resolve(!!r.confirm),
            fail: () => resolve(false)
        })
    })
    if (!confirmed) return
    busy.unbind = true
    try {
        await unbindOaSubscribe()
        clearOaInvitation()
        invitation.value = ''
        await refresh()
        uni.$emit('oa_binding_changed', { bound: false })
    } catch {
        toast.error('解除绑定失败，请稍后重试。')
    } finally {
        busy.unbind = false
    }
}

function copyAccount(successText = '已复制') {
    uni.setClipboardData({
        data: status.official_account || status.official_name,
        success: () => toast.success(typeof successText === 'string' ? successText : '已复制'),
        fail: () => toast.error('复制失败')
    })
}

function previewQrCode() {
    const url = String(status.official_qr_url || '').trim()
    if (!url) return
    uni.previewImage({ urls: [url], current: url })
}

/**
 * 探测直达服务号 API。优先读微信小程序沙箱全局 wx（globalThis.wx 在部分基础库
 * 取不到），再用 uni 兜底；均做 typeof 判定，避免低版本 / 非微信端引用未定义方法。
 */
function getOpenOfficialAccountFns(): {
    openProfile?: (opts: Record<string, any>) => void
    openChat?: (opts: Record<string, any>) => void
} {
    // #ifdef MP-WEIXIN
    const wxGlobal = typeof wx !== 'undefined' ? wx : (globalThis as any)?.wx
    const uniApi = uni as any
    const openProfile =
        typeof wxGlobal?.openOfficialAccountProfile === 'function'
            ? wxGlobal.openOfficialAccountProfile.bind(wxGlobal)
            : typeof uniApi?.openOfficialAccountProfile === 'function'
              ? uniApi.openOfficialAccountProfile.bind(uniApi)
              : undefined
    const openChat =
        typeof wxGlobal?.openOfficialAccountChat === 'function'
            ? wxGlobal.openOfficialAccountChat.bind(wxGlobal)
            : typeof uniApi?.openOfficialAccountChat === 'function'
              ? uniApi.openOfficialAccountChat.bind(uniApi)
              : undefined
    return { openProfile, openChat }
    // #endif
    // #ifndef MP-WEIXIN
    return {}
    // #endif
}

/** 用户主动取消不是失败：不提示、不兜底复制 */
function isUserCancelError(err: any): boolean {
    return String(err?.errMsg || '')
        .toLowerCase()
        .includes('cancel')
}

/** 低版本 / 调用失败时的降级：先引导长按二维码，再退回复制微信号 */
function fallbackOfficialAccount(err?: any) {
    if (isUserCancelError(err)) return
    if (hasQrCode.value) {
        clearHighlightTimer()
        qrHighlighted.value = true
        highlightTimer = setTimeout(() => {
            qrHighlighted.value = false
        }, 3000)
        toast.info('请长按下方二维码识别关注')
        return
    }
    if (hasAccountText.value) {
        uni.setClipboardData({
            data: status.official_account || status.official_name,
            success: () => toast.info('已复制微信号，请打开微信搜索关注'),
            fail: () => toast.info('暂不支持直接跳转，请在微信搜索关注')
        })
        return
    }
    toast.info('暂不支持直接跳转，请在微信搜索关注')
}

function openOfficialAccount() {
    const username = String(status.official_original_id || '').trim()
    if (!username) {
        fallbackOfficialAccount()
        return
    }
    try {
        const { openProfile, openChat } = getOpenOfficialAccountFns()
        // 已关注用户直接进会话；未关注/未知时先进主页（主页可完成关注）
        const preferChat = status.follow_status === 'followed' && !!openChat
        const primary = preferChat ? openChat : openProfile
        const secondary = preferChat ? openProfile : openChat

        const openSecondary = (err?: any) => {
            if (isUserCancelError(err)) return
            if (!secondary) {
                fallbackOfficialAccount(err)
                return
            }
            try {
                secondary({
                    username,
                    fail: (secondaryErr: any) => {
                        if (isUserCancelError(secondaryErr)) return
                        fallbackOfficialAccount(secondaryErr)
                    }
                })
            } catch {
                fallbackOfficialAccount()
            }
        }

        if (!primary) {
            openSecondary()
            return
        }
        primary({ username, fail: openSecondary })
    } catch {
        fallbackOfficialAccount()
    }
}

function handlePrimaryAction() {
    if (hasOriginalId.value) {
        openOfficialAccount()
        return
    }
    if (hasQrCode.value) {
        previewQrCode()
        return
    }
    if (hasAccountText.value) {
        copyAccount('已复制微信号，请打开微信搜索关注')
        return
    }
    void refresh()
}

function onPullRefresh(done: () => void) {
    Promise.resolve(refresh()).finally(done)
}

function goBack() {
    clearOaInvitation()
    const pages = getCurrentPages()
    // 跳过登录中转和重复邀请页；服务号直接进入时回到个人中心
    const skipped = [OA_BINDING_PATH, '/pages/login/login']
    const fallback = () =>
        uni.switchTab({ url: '/pages/user/user', fail: () => uni.reLaunch({ url: '/pages/user/user' }) })
    for (let index = pages.length - 2; index >= 0; index--) {
        const route = '/' + String(pages[index].route || '').replace(/^\//, '')
        if (skipped.includes(route)) continue
        uni.navigateBack({ delta: pages.length - 1 - index, fail: fallback })
        return
    }
    fallback()
}

onLoad((query: Record<string, any> = {}) => {
    captureOaInvitation(OA_BINDING_PATH, query || {})
    invitation.value = getOaInvitation()
})

onShow(() => {
    visible = true
    invitation.value = getOaInvitation() || invitation.value
    generation += 1
    void refresh()
})

onHide(() => {
    visible = false
    generation += 1
    clearExpiryTimer()
    clearHighlightTimer()
})

onUnload(() => {
    visible = false
    generation += 1
    clearExpiryTimer()
    clearHighlightTimer()
})
</script>

<style lang="scss" scoped>
.oa {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    padding: var(--sp-4) var(--page-gutter) calc(var(--sp-6) + var(--safe-bottom));
}

/* ---- 1. 深墨黑金状态头卡 ---- */
.hero {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
    padding: var(--sp-4);
    border-radius: var(--r-lg);
    border: 1rpx solid var(--c-brand-text);
    background: linear-gradient(145deg, var(--c-dark-1), var(--c-dark-2));

    &__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__pill {
        display: inline-flex;
        align-items: center;
        gap: var(--sp-1);
        padding: 6rpx 20rpx;
        border-radius: var(--r-pill);
        border: 1rpx solid var(--c-line-strong);
        color: var(--c-on-dark-2);
        font-size: var(--fs-xs);
        line-height: var(--lh-body);

        &--ok {
            color: var(--c-success-on-dark);
            border-color: var(--c-success-on-dark);
        }

        &--warn {
            color: var(--c-warning-on-dark);
            border-color: var(--c-warning-on-dark);
        }

        &--brand {
            color: var(--c-brand-soft);
            border-color: var(--c-brand-soft);
        }
    }

    &__refresh {
        padding: var(--sp-1) var(--sp-2);
        color: var(--c-on-dark-2);
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
    }

    &__title {
        font-size: var(--fs-xl);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
        color: var(--c-on-dark);
    }

    &__stats {
        display: flex;
        gap: var(--sp-2);
    }

    &__alert {
        display: flex;
        align-items: center;
        gap: var(--sp-1);
        padding: var(--sp-2) var(--sp-3);
        border-radius: var(--r-sm);
        background: var(--c-dark-3);
        color: var(--c-warning-on-dark);
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
    }
}

.hero-stat {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: var(--sp-1);
    padding: var(--sp-3);
    border-radius: var(--r-sm);
    background: var(--c-dark-3);

    &__label {
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
    }

    &__value {
        display: flex;
        align-items: center;
        gap: var(--sp-1);
    }

    &__dot {
        width: 12rpx;
        height: 12rpx;
        flex: none;
        border-radius: var(--r-pill);
        background: var(--c-on-dark-2);

        &.is-on {
            background: var(--c-success-on-dark);
        }
    }

    &__text {
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        color: var(--c-on-dark);

        &.is-on {
            color: var(--c-success-on-dark);
        }
    }
}

/* ---- 2. 单一核心操作卡 ---- */
.panel {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    padding: var(--sp-4);
    border-radius: var(--r-lg);
    border: 1rpx solid var(--c-line);
    background: var(--c-surface);

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__title {
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }
}

.invite-badge {
    flex: none;
    padding: 6rpx 18rpx;
    border-radius: var(--r-pill);
    background: var(--c-brand-bg);
    color: var(--c-brand-text);
    font-size: var(--fs-xs);
    line-height: var(--lh-body);
}

.account-tile {
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    padding: var(--sp-3);
    border-radius: var(--r-sm);
    background: var(--c-surface-2);

    &__badge {
        width: 72rpx;
        height: 72rpx;
        flex: none;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--r-pill);
        background: var(--c-brand-bg);
        color: var(--c-brand-text);
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
    }

    &__info {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
    }

    &__name {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__sub {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.note {
    font-size: var(--fs-xs);
    line-height: var(--lh-body);
    color: var(--c-ink-3);
}

.oa-head {
    display: flex;
    align-items: center;
    gap: var(--sp-3);

    &__seal {
        width: 84rpx;
        height: 84rpx;
        flex: none;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--r-md);
        background: var(--c-brand-bg);
        color: var(--c-brand-text);
    }

    &__info {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
    }

    &__name {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__account {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
}

.oa-cta {
    margin-top: var(--sp-1);
}

/* ---- 回退：长按二维码识别 ---- */
.qr-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--sp-2);
    padding: var(--sp-3);
    border-radius: var(--r-md);
    border: 1rpx solid var(--c-line);
    background: var(--c-surface-2);

    &.is-highlight {
        border-color: var(--c-brand-text);
    }

    &__frame {
        padding: var(--sp-2);
        border-radius: var(--r-sm);
        background: var(--c-surface);
        border: 1rpx solid var(--c-line);
    }

    &__img {
        display: block;
        width: 260rpx;
        height: 260rpx;
    }

    &__tip {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
        text-align: center;
    }
}

/* ---- 最后：复制微信号 ---- */
.copy-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);
    padding: var(--sp-2) var(--sp-3);
    border-radius: var(--r-sm);
    background: var(--c-surface-2);

    &__info {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: var(--sp-2);
    }

    &__label {
        flex: none;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__val {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__btn {
        flex: none;
        display: inline-flex;
        align-items: center;
        gap: var(--sp-1);
        color: var(--c-brand-text);
        font-size: var(--fs-xs);
        font-weight: var(--fw-medium);
    }
}

/* ---- 紧凑横向三步条 ---- */
.flow {
    display: flex;
}

.flow__step {
    position: relative;
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--sp-1);
}

.flow__step + .flow__step::before {
    content: '';
    position: absolute;
    top: 21rpx;
    right: 50%;
    width: 100%;
    height: 1rpx;
    background: var(--c-line);
}

.flow__dot {
    position: relative;
    z-index: 1;
    width: 44rpx;
    height: 44rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--r-pill);
    border: 1rpx solid var(--c-line);
    background: var(--c-surface-2);
    color: var(--c-ink-3);
    font-size: var(--fs-xs);
    font-weight: var(--fw-semibold);

    &.is-done {
        background: var(--c-brand-bg);
        border-color: var(--c-primary-border);
        color: var(--c-brand-text);
    }
}

.flow__label {
    font-size: var(--fs-xs);
    line-height: var(--lh-tight);
    color: var(--c-ink-3);
    text-align: center;
}

.flow__tip {
    font-size: var(--fs-xs);
    line-height: var(--lh-body);
    color: var(--c-ink-3);
    text-align: center;
}

/* ---- 已开启：提醒收益 ---- */
.benefits {
    display: flex;
    gap: var(--sp-2);
}

.benefit {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--sp-1);
    padding: var(--sp-3) var(--sp-1);
    border-radius: var(--r-sm);
    background: var(--c-surface-2);

    &__icon {
        color: var(--c-brand-text);
    }

    &__label {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }
}

/* ---- 3. 错误提示与极简底区 ---- */
.foot {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);

    &__link {
        padding: var(--sp-2);
        text-align: center;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.error-text {
    padding: var(--sp-2) var(--sp-3);
    border-radius: var(--r-xs);
    background: var(--c-danger-bg);
    color: var(--c-danger);
    font-size: var(--fs-xs);
    line-height: var(--lh-body);
}
</style>
