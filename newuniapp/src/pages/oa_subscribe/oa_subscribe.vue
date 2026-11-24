<template>
    <!-- 服务号通知与专属邀请确认页（登录页中转后由 BACK_URL 纯路径回到这里） -->
    <page-shell
        title="服务号通知"
        bg="page"
        :show-back="true"
        nav-back-mode="none"
        @nav-back="goBack"
    >
        <view class="page-wrap oa">
            <!-- 1. 主题头卡 -->
            <view class="hero">
                <view class="hero__eyebrow">
                    <tn-icon name="notice" :size="28" />
                    <text>让重要提醒，及时看见</text>
                </view>
                <text class="hero__title">{{ heroTitle }}</text>
                <text class="hero__desc">订单进展、档期安排与工作事项，多一个接收提醒的方式。</text>
                <view class="hero__tags">
                    <text class="hero__tag">订单进展</text>
                    <text class="hero__tag">档期安排</text>
                    <text class="hero__tag">工作提醒</text>
                </view>
            </view>

            <!-- 2. 当前状态卡 -->
            <base-card>
                <view class="section-head">
                    <text class="section-head__title">当前状态</text>
                    <text class="section-head__hint">{{
                        busy.status ? '正在更新…' : '关注与绑定分别确认'
                    }}</text>
                </view>
                <view class="status-grid">
                    <view class="status-grid__item">
                        <text class="status-grid__label">微信关注</text>
                        <text
                            class="status-grid__value"
                            :class="{ 'is-done': status.follow_status === 'followed' }"
                            >{{ followLabel }}</text
                        >
                    </view>
                    <view class="status-grid__item">
                        <text class="status-grid__label">平台账号绑定</text>
                        <text class="status-grid__value" :class="{ 'is-done': status.bound }">{{
                            status.bound ? '已绑定' : '未绑定'
                        }}</text>
                    </view>
                </view>
                <text v-if="!status.channel_available" class="note note--warn"
                    >平台通知暂不可用。您的绑定会保留，站内消息不受影响。</text
                >
                <text v-else-if="status.can_receive" class="note"
                    >已具备服务号提醒接收资格，具体送达以微信和平台通知状态为准。</text
                >
            </base-card>

            <!-- 3. 专属绑定邀请卡 -->
            <base-card v-if="invitation && inviteState.state !== 'completed'">
                <view class="section-head">
                    <text class="section-head__title">确认本人账号</text>
                    <text class="invite-badge">专属绑定邀请</text>
                </view>
                <view class="account-panel">
                    <text class="account-panel__name">{{ nickname || '已登录用户' }}</text>
                    <text class="account-panel__sub">{{ maskedMobile || '当前登录的平台账号' }}</text>
                </view>
                <text class="note">{{ inviteState.message }}</text>
                <text v-if="inviteState.can_confirm" class="privacy-note"
                    >请确认这是您本人从服务号打开的邀请，不要使用他人转发的入口。</text
                >
                <base-button
                    v-if="inviteState.can_confirm"
                    text="确认绑定并开启通知"
                    variant="ink"
                    size="lg"
                    :loading="busy.confirm"
                    :disabled="busy.status"
                    @click="confirmBinding"
                />
            </base-card>

            <!-- 4. 绑定 / 重新关注引导卡 -->
            <base-card v-if="!status.can_receive && !inviteState.can_confirm">
                <text class="section-head__title">{{
                    status.bound ? '重新关注，即可恢复接收' : '三步开启服务号提醒'
                }}</text>
                <view class="official">
                    <view class="official__info">
                        <text class="official__name">{{ status.official_name || '平台服务号' }}</text>
                        <text v-if="status.official_account" class="official__account"
                            >微信号：{{ status.official_account }}</text
                        >
                    </view>
                    <base-button
                        v-if="status.official_account || status.official_name"
                        :text="status.official_account ? '复制微信号' : '复制名称'"
                        variant="ghost"
                        size="sm"
                        :block="false"
                        @click="copyAccount"
                    />
                </view>
                <text v-if="status.bound" class="note"
                    >在微信搜索并重新关注上述服务号。原账号绑定已保留，无需重复绑定，也不会补发历史提醒。</text
                >
                <view v-else class="steps">
                    <view v-for="(step, index) in STEPS" :key="index" class="steps__item">
                        <text class="steps__num">{{ index + 1 }}</text>
                        <view class="steps__body">
                            <text class="steps__title">{{ step.title }}</text>
                            <text class="steps__desc">{{ step.desc }}</text>
                        </view>
                    </view>
                </view>
                <text v-if="!status.bound" class="note note--warn"
                    >找不到菜单或邀请过期？在服务号发送“绑定账号”，重新获取专属入口。</text
                >
            </base-card>

            <text v-if="error" class="error-text">{{ error }}</text>

            <!-- 5. 底部操作 -->
            <view class="actions">
                <base-button
                    :text="status.can_receive ? '完成，返回' : '暂不设置，返回'"
                    variant="ink"
                    size="lg"
                    @click="goBack"
                />
                <view class="actions__secondary">
                    <text class="actions__text" @tap="refresh">{{
                        busy.status ? '正在刷新…' : '刷新状态'
                    }}</text>
                    <text
                        v-if="status.bound"
                        class="actions__text actions__text--muted"
                        @tap="unbind"
                        >{{ busy.unbind ? '正在处理…' : '解除微信绑定' }}</text
                    >
                </view>
                <text class="actions__foot">关注由您选择，站内消息始终保留</text>
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

const STEPS = [
    { title: '在微信搜索并关注服务号', desc: '可复制上方微信号或名称进行搜索。' },
    { title: '从服务号打开绑定邀请', desc: '点击欢迎消息或菜单中的“绑定账号”。' },
    { title: '回到小程序，确认本人账号', desc: '登录后点击“确认绑定并开启通知”。' }
] as const

const userStore = useUserStore()

const status = reactive({
    bound: false,
    follow_status: 'unknown' as 'followed' | 'unfollowed' | 'unknown',
    can_receive: false,
    channel_available: true,
    official_name: '',
    official_account: ''
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

/** 并发防串台：离页 / onShow 重入会递增，旧请求返回后按版本号丢弃 */
let generation = 0
let visible = false
let expiryTimer: ReturnType<typeof setTimeout> | undefined

const heroTitle = computed(() =>
    status.can_receive ? '关注与绑定已完成' : invitation.value ? '确认账号，开启提醒' : '在微信里，接收重要提醒'
)
const followLabel = computed(
    () => ({ followed: '已关注', unfollowed: '未关注', unknown: '待确认' })[status.follow_status] || '待确认'
)
const nickname = computed(() => String(userStore.userInfo?.nickname || ''))
const maskedMobile = computed(() =>
    String(userStore.userInfo?.mobile || '').replace(/^(\d{3})\d{4}(\d{4})$/, '$1****$2')
)

function clearExpiryTimer() {
    if (expiryTimer) {
        clearTimeout(expiryTimer)
        expiryTimer = undefined
    }
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

function copyAccount() {
    uni.setClipboardData({
        data: status.official_account || status.official_name,
        success: () => toast.success('已复制'),
        fail: () => toast.error('复制失败')
    })
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
})

onUnload(() => {
    visible = false
    generation += 1
    clearExpiryTimer()
})
</script>

<style lang="scss" scoped>
.oa {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    padding: var(--sp-3) var(--sp-4) calc(var(--sp-5) + var(--safe-bottom));
}

/* ---- 主题头卡（深色，与我的页倒计时卡同基调） ---- */
.hero {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
    padding: var(--sp-4);
    border-radius: var(--r-md);
    background: var(--c-ink);
    border: 1rpx solid var(--c-line-strong);

    &__eyebrow {
        display: flex;
        align-items: center;
        gap: var(--sp-1);
        font-size: var(--fs-xs);
        letter-spacing: 0.16em;
        color: var(--c-brand-soft);
    }

    &__title {
        font-size: var(--fs-xl);
        font-weight: var(--fw-semibold);
        color: var(--c-on-dark);
        line-height: var(--lh-tight);
    }

    &__desc {
        font-size: var(--fs-xs);
        line-height: var(--lh-loose);
        color: var(--c-on-dark-2);
    }

    &__tags {
        display: flex;
        flex-wrap: wrap;
        gap: var(--sp-1);
        margin-top: var(--sp-1);
    }

    &__tag {
        padding: 6rpx 18rpx;
        border-radius: var(--r-sm);
        border: 1rpx solid var(--c-line-strong);
        color: var(--c-brand-soft);
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
    }
}

/* ---- 区块标题 ---- */
.section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: var(--sp-1);
    margin-bottom: var(--sp-2);

    &__title {
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__hint {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

/* ---- 状态卡 ---- */
.status-grid {
    display: flex;
    border-radius: var(--r-sm);
    background: var(--c-surface-2);
    padding: var(--sp-3) 0;

    &__item {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
        padding: 0 var(--sp-3);
        min-width: 0;
    }

    &__item + &__item {
        border-left: 1rpx solid var(--c-line);
    }

    &__label {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__value {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink-2);
    }

    .is-done {
        color: var(--c-success);
    }
}

.note {
    font-size: var(--fs-xs);
    line-height: var(--lh-body);
    color: var(--c-ink-3);

    &--warn {
        padding: var(--sp-2) var(--sp-3);
        border-radius: var(--r-xs);
        background: var(--c-warning-bg);
        color: var(--c-warning);
    }
}

/* ---- 邀请卡 ---- */
.invite-badge {
    padding: 6rpx 14rpx;
    border-radius: var(--r-xs);
    background: var(--c-brand-bg);
    color: var(--c-brand-text);
    font-size: var(--fs-xs);
    line-height: var(--lh-body);
}

.account-panel {
    display: flex;
    flex-direction: column;
    gap: var(--sp-1);
    padding: var(--sp-3);
    border-radius: var(--r-sm);
    background: var(--c-surface-2);
    border: 1rpx solid var(--c-primary-border);

    &__name {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__sub {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        overflow-wrap: anywhere;
    }
}

.privacy-note {
    font-size: var(--fs-xs);
    line-height: var(--lh-loose);
    color: var(--c-ink-2);
}

/* ---- 引导卡 ---- */
.official {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    padding: var(--sp-3);
    border-radius: var(--r-sm);
    background: var(--c-surface-2);

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
        overflow-wrap: anywhere;
    }

    &__account {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        overflow-wrap: anywhere;
    }
}

.steps {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);

    &__item {
        display: flex;
        gap: var(--sp-2);
    }

    &__num {
        width: 44rpx;
        height: 44rpx;
        flex: none;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--r-pill);
        background: var(--c-brand-bg);
        color: var(--c-brand-text);
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
    }

    &__body {
        display: flex;
        flex-direction: column;
        gap: 4rpx;
        min-width: 0;
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &__desc {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        line-height: var(--lh-body);
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

/* ---- 底部操作 ---- */
.actions {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);

    &__secondary {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--sp-4);
        flex-wrap: wrap;
    }

    &__text {
        padding: var(--sp-2) var(--sp-2);
        font-size: var(--fs-xs);
        color: var(--c-brand-text);

        &--muted {
            color: var(--c-ink-3);
        }
    }

    &__foot {
        text-align: center;
        color: var(--c-ink-3);
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
    }
}
</style>
