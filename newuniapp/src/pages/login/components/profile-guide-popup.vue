<template>
    <!--
        登录后引导弹框：步骤 1 补头像/昵称（新用户）→ 步骤 2 绑定手机号（coerce_mobile 开启时）。
        tn-popup 的 prop 名必须按组件真实定义写（popup.ts），写错只会静默失效 ——
        与 login.vue 的 phonePopup 弹层同一口径（v-model / open-direction / :radius / :safe-area-inset-bottom）。
    -->
    <tn-popup
        v-model="visible"
        open-direction="bottom"
        :radius="24"
        :safe-area-inset-bottom="true"
        :overlay-closeable="false"
    >
        <view class="pg">
            <view class="pg__head">
                <view class="pg__head-text">
                    <text v-if="showStepBadge" class="pg__badge">{{ stepText }}</text>
                    <text class="pg__title">{{ step === 'profile' ? '完善个人资料' : '绑定手机号' }}</text>
                    <text class="pg__sub">{{ step === 'profile' ? '头像与昵称会展示给你的婚礼服务团队' : forceMobile ? '根据平台要求，需绑定手机号后才可完成登录' : '用于订单进度通知与售后联系' }}</text>
                </view>
                <!-- 显式关闭入口：profile 跳过当前步骤；强制绑定步骤 2 取消本次登录 -->
                <view
                    class="pg__close hit-88"
                    hover-class="is-pressed"
                    aria-role="button"
                    aria-label="关闭"
                    @tap="onClose"
                >
                    <tn-icon name="close" :size="28" />
                </view>
            </view>

            <!-- 步骤 1：补头像 + 昵称 -->
            <form v-if="step === 'profile'" @submit="onSubmitProfile">
                <button
                    class="pg__avatar-btn"
                    open-type="chooseAvatar"
                    :disabled="uploadingAvatar"
                    @chooseavatar="onChooseAvatar"
                >
                    <view class="pg__avatar-ring">
                        <image class="pg__avatar" :src="previewAvatar" mode="aspectFill" />
                    </view>
                    <text class="pg__avatar-tip">{{ uploadingAvatar ? '上传中…' : '点击选择头像' }}</text>
                </button>
                <text v-if="avatarError" class="pg__err pg__err--center">{{ avatarError }}</text>

                <view class="pg__field" :class="{ 'is-err': nicknameError }">
                    <text class="pg__label">昵称</text>
                    <input
                        class="pg__input"
                        name="nickname"
                        type="nickname"
                        v-model="nickname"
                        maxlength="20"
                        placeholder="点击使用微信昵称或输入"
                        placeholder-class="pg__ph"
                        @input="onNicknameChange"
                        @blur="onNicknameChange"
                        @change="onNicknameChange"
                    />
                </view>
                <text v-if="nicknameError" class="pg__err">{{ nicknameError }}</text>
                <text v-if="submitError" class="pg__err">{{ submitError }}</text>

                <button
                    class="pg__submit press-spring"
                    hover-class="is-pressing"
                    :hover-stay-time="90"
                    form-type="submit"
                    :disabled="submitting || uploadingAvatar"
                >
                    <text class="pg__submit-text">{{ submitText }}</text>
                </button>
                <text class="pg__skip" @tap="onProfileSkip">暂不设置，先逛逛</text>
            </form>

            <!-- 步骤 2：绑定手机号（微信原生 getPhoneNumber） -->
            <view v-else>
                <button
                    class="pg__submit press-spring"
                    hover-class="is-pressing"
                    :hover-stay-time="90"
                    open-type="getPhoneNumber"
                    :disabled="bindingPhone"
                    @getphonenumber="onGetPhoneNumber"
                >
                    <tn-icon name="iphone" :size="28" />
                    <text class="pg__submit-text">{{ bindingPhone ? '绑定中…' : '微信手机号一键绑定' }}</text>
                </button>
                <text v-if="mobileError" class="pg__err pg__err--center">{{ mobileError }}</text>
                <text v-if="forceMobile" class="pg__skip" @tap="onMobileCancel">暂不绑定，取消登录</text>
                <text v-else class="pg__skip" @tap="onMobileSkip">稍后再绑，先逛逛</text>
            </view>
        </view>
    </tn-popup>
</template>

<script setup lang="ts">
/**
 * 登录后引导弹框（新用户补资料 / 强制绑定手机号）。
 *
 * 职责边界：本组件只负责采集与调用接口，登录态与去向由 login.vue 决定 ——
 *   · profile-saved / profile-skip / mobile-bound / mobile-skip / mobile-cancel 五个事件全部上抛；
 *   · 头像预填 props.initialAvatar（登录接口返回的 avatar），兜底静态默认头像；
 *   · 昵称走 <form @submit> + type="nickname"（微信快捷填充），提交时兜底读 e.detail.value.nickname；
 *   · 手机号走 open-type="getPhoneNumber"，code 换手机号（getMobileByMnp），换不到再拉 user/info 兜底；
 *   · forceMobile（need_bind_mobile 拦截）：步骤 2 没有「稍后再绑」出口，取消 = 取消本次登录，
 *     接口靠 props.authToken（内存临时凭证）携带 token 头，登录态此时还未落库。
 */
import { computed, ref, watch } from 'vue'
import { uploadImage } from '@/api/app'
import { updateUser, getMobileByMnp } from '@/api/auth'
import { getUserInfo } from '@/api/user'
import { isPhoneAuthDenied, extractPhoneCode } from '@/platform/login'
import { sanitizeServerMessage } from '@/utils/resolveErrorView'
import { haptic } from '@/utils/haptics'

const props = withDefaults(
    defineProps<{
        modelValue: boolean
        step: 'profile' | 'mobile'
        /** 新用户登录接口带回的头像（预填，可被 chooseAvatar 覆盖） */
        initialAvatar?: string
        /** 保存资料后是否还要进入绑手机步骤（决定提交按钮文案） */
        needMobile?: boolean
        /** 新用户且同时需绑手机时展示「第 x 步 / 共 2 步」 */
        showStepBadge?: boolean
        /** 强制绑定模式（need_bind_mobile 拦截）：步骤 2 无跳过出口，取消 = 取消本次登录 */
        forceMobile?: boolean
        /** 登录态尚未落库时的内存临时凭证，用于给接口附带 token 头 */
        authToken?: string
    }>(),
    {
        initialAvatar: '',
        needMobile: false,
        showStepBadge: false,
        forceMobile: false,
        authToken: ''
    }
)

const emit = defineEmits<{
    (e: 'update:modelValue', v: boolean): void
    (e: 'profile-saved', payload: { avatar: string; nickname: string }): void
    (e: 'profile-skip'): void
    (e: 'mobile-bound', payload: { mobile: string; userInfo?: Record<string, any> | null }): void
    (e: 'mobile-skip'): void
    (e: 'mobile-cancel'): void
}>()

const visible = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit('update:modelValue', v)
})

/* ---------- 局部状态 ---------- */
const avatar = ref('')
const nickname = ref('')
const submitting = ref(false)
const uploadingAvatar = ref(false)
const bindingPhone = ref(false)
const avatarError = ref('')
const nicknameError = ref('')
const submitError = ref('')
const mobileError = ref('')

const previewAvatar = computed(
    () => avatar.value || String(props.initialAvatar || '').trim() || '/static/images/user/default_avatar.png'
)
const stepText = computed(() => (props.step === 'profile' ? '第 1 步 / 共 2 步' : '第 2 步 / 共 2 步'))
const submitText = computed(() => {
    if (submitting.value) return '保存中…'
    return props.needMobile ? '下一步' : '保存并进入'
})

/** 强制绑定流程中登录态未落库，接口必须带内存临时凭证；正常流程返回 undefined 走请求层默认 token */
function getAuthHeader(): Record<string, string> | undefined {
    return props.authToken ? { token: props.authToken } : undefined
}

/**
 * 打开（modelValue → true）或步骤切换时重置局部态：
 *   · profile 步预填 initialAvatar、清空昵称 —— 每次打开都是干净的一份；
 *   · mobile 步只清错误与提交态。
 */
watch(
    () => props.modelValue,
    (open) => {
        if (open) resetForStep()
    }
)
watch(
    () => props.step,
    () => {
        if (props.modelValue) resetForStep()
    }
)

function resetForStep() {
    avatarError.value = ''
    nicknameError.value = ''
    submitError.value = ''
    mobileError.value = ''
    submitting.value = false
    uploadingAvatar.value = false
    bindingPhone.value = false
    if (props.step === 'profile') {
        avatar.value = String(props.initialAvatar || '').trim()
        nickname.value = ''
    }
}

/* ---------- 步骤 1：头像 / 昵称 ---------- */

/** chooseAvatar 回调 → 上传 → 用返回的 uri/url 替换预览；失败保留原头像、在头像下方展示红字 */
async function onChooseAvatar(e: any) {
    const tempPath = String(e?.detail?.avatarUrl || '').trim()
    if (!tempPath) return
    avatarError.value = ''
    uploadingAvatar.value = true
    try {
        const up: any = await uploadImage(tempPath, undefined, getAuthHeader())
        const uri = String(up?.uri || up?.url || '').trim()
        if (!uri) throw new Error('上传失败，请重试')
        avatar.value = uri
    } catch (err: any) {
        avatarError.value = sanitizeServerMessage(err?.message, '头像上传失败，请重试')
    } finally {
        uploadingAvatar.value = false
    }
}

/** 微信快捷昵称填充可能只触发 input/blur/change 之一，三个都监听并统一取 detail.value */
function onNicknameChange(e: any) {
    const v = String(e?.detail?.value ?? nickname.value ?? '')
    nickname.value = v
    nicknameError.value = ''
}

async function onSubmitProfile(e: any) {
    if (submitting.value || uploadingAvatar.value) return
    haptic('medium', 'pg-submit')
    submitError.value = ''
    nicknameError.value = ''

    // 提交时以 form 带回的值为准（type="nickname" 的快捷填充不一定同步进 v-model）
    const rawNick = String(e?.detail?.value?.nickname ?? nickname.value ?? '').trim()
    nickname.value = rawNick
    if (!rawNick) {
        nicknameError.value = '请输入昵称'
        return
    }
    const finalAvatar = String(avatar.value || props.initialAvatar || '').trim()
    if (!finalAvatar) {
        // 后端未配置默认头像时的极端兜底：没有头像没法提交
        avatarError.value = '请先点击上方选择头像'
        return
    }

    submitting.value = true
    try {
        await updateUser({ avatar: finalAvatar, nickname: rawNick }, getAuthHeader())
        emit('profile-saved', { avatar: finalAvatar, nickname: rawNick })
    } catch (err: any) {
        const msg = sanitizeServerMessage(err?.message, '保存失败，请稍后重试')
        // 昵称相关的业务拒绝（重名/敏感词）写在字段下方，其余落在提交按钮上方
        if (msg.indexOf('昵称') > -1) nicknameError.value = msg
        else submitError.value = msg
    } finally {
        submitting.value = false
    }
}

function onProfileSkip() {
    emit('profile-skip')
}

/* ---------- 步骤 2：绑定手机号 ---------- */

async function onGetPhoneNumber(e: any) {
    if (bindingPhone.value) return
    haptic('medium', 'pg-submit')
    mobileError.value = ''
    if (isPhoneAuthDenied(e)) {
        mobileError.value = '已取消授权，可重试或稍后在个人资料页绑定'
        return
    }
    const code = extractPhoneCode(e)
    if (!code) {
        mobileError.value = '未获取到手机号，请重试'
        return
    }
    bindingPhone.value = true
    try {
        const res: any = await getMobileByMnp({ code }, getAuthHeader())
        let nextMobile = String((res as any)?.mobile || '').trim()
        // 接口成功但 data 为空时，拉一次最新资料兜底拿手机号；拿不到也不阻塞流程
        let latestInfo: Record<string, any> | null = null
        if (!nextMobile) {
            try {
                latestInfo = (await getUserInfo(getAuthHeader())) as any
                nextMobile = String((latestInfo as any)?.mobile || '').trim()
            } catch {
                // 兜底失败就带空手机号上抛，login.vue 侧自行合并
            }
        }
        if (props.forceMobile && !nextMobile) {
            // 强制绑定下拿不到手机号不算成功：不外抛 mobile-bound，留在弹框内提示重试
            mobileError.value = '未获取到已绑定手机号，请重试'
            return
        }
        emit('mobile-bound', { mobile: nextMobile, userInfo: latestInfo })
    } catch (err: any) {
        mobileError.value = sanitizeServerMessage(err?.message, '绑定失败，请稍后重试')
    } finally {
        bindingPhone.value = false
    }
}

function onMobileSkip() {
    emit('mobile-skip')
}

/** 强制绑定模式取消（底部「暂不绑定」或右上角关闭）：login.vue 负责清理临时态与取消登录 */
function onMobileCancel() {
    emit('mobile-cancel')
}

/** 右上角关闭：profile 跳过当前步骤；强制绑定步骤 2 取消登录；非强制与「先逛逛」同语义 */
function onClose() {
    if (props.step === 'profile') onProfileSkip()
    else if (props.forceMobile) onMobileCancel()
    else onMobileSkip()
}
</script>

<style lang="scss" scoped>
.pg {
    padding: 40rpx 42rpx calc(32rpx + var(--safe-bottom));
    /* 与 snap-sheet 同一套浮层语义：同心圆角 + 顶部发散阴影 + 暗色 1rpx 描边 */
    border-radius: var(--r-24) var(--r-24) 0 0;
    box-shadow: var(--shadow-sheet);
    border: 1rpx solid var(--c-card-border);

    &__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: var(--sp-2);
        margin-bottom: var(--sp-4);
    }

    &__head-text {
        flex: 1;
        min-width: 0;
    }

    &__badge {
        display: inline-block;
        padding: 4rpx var(--sp-2);
        border-radius: var(--r-pill);
        background: var(--c-brand-bg);
        font-size: var(--fs-20);
        color: var(--c-brand-text);
        margin-bottom: var(--sp-2);
    }

    &__close {
        flex: none;
        width: 72rpx;
        height: 72rpx;
        margin: -12rpx -12rpx 0 0; /* 热区外扩，视觉位置贴右上角 */
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--c-ink-3);
    }

    &__title {
        display: block;
        font-size: var(--fs-34);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__sub {
        display: block;
        margin-top: var(--sp-1);
        font-size: var(--fs-21);
        color: var(--c-ink-3);
        line-height: var(--lh-body);
    }

    &__avatar-btn {
        /* 原生 button 默认样式必须清掉（padding/line-height/边框），否则头像区被挤偏 */
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: var(--sp-2);
        margin: 0 auto var(--sp-4);
        padding: 0;
        line-height: normal;
        background: transparent;
        border: 0;
        font-family: inherit;

        &::after {
            border: none;
        }
    }

    &__avatar-ring {
        width: 144rpx;
        height: 144rpx;
        border-radius: var(--r-pill);
        overflow: hidden;
        border: 1rpx solid var(--c-line);
        background: var(--c-surface-2);
    }

    &__avatar {
        width: 100%;
        height: 100%;
        display: block;
    }

    &__avatar-tip {
        font-size: var(--fs-20);
        color: var(--c-brand-text);
    }

    &__field {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
        height: 96rpx;
        padding: 0 var(--sp-3);
        border-radius: var(--r-md);
        background: var(--c-surface-2);
        border: 1rpx solid transparent;

        &.is-err {
            border-color: var(--c-danger-border);
        }
    }

    &__label {
        width: 104rpx;
        flex: none;
        font-size: var(--fs-25);
        color: var(--c-ink-2);
    }

    &__input {
        flex: 1;
        font-size: var(--fs-md);
        color: var(--c-ink);
    }

    &__ph {
        color: var(--c-ink-3);
    }

    &__err {
        display: block;
        margin: var(--sp-1) 0 0 var(--sp-3);
        font-size: var(--fs-21);
        color: var(--c-danger);

        &--center {
            margin-left: 0;
            text-align: center;
        }
    }

    &__submit {
        /* 原生 button 默认值清掉（与 login.vue 的 .lg__btn 同一套重置，已验证可用） */
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--sp-1);
        width: 100%;
        margin-top: var(--sp-5);
        height: 88rpx;
        padding: 0;
        line-height: normal;
        box-sizing: border-box;
        border: 0;
        font-family: inherit;
        border-radius: var(--r-24);
        background: var(--c-action-bg);
        /* ⚠️ 颜色必须写在按钮上：tn-icon 的 useComponentColor 不认 var()，图标靠继承拿色；
           文字由 .pg__submit-text 自己覆盖成反相色 */
        color: var(--c-action-fg);
        /* 两段式弹簧：按下 90ms 压实，松手 440ms 软回弹 */
        transition: transform var(--dur-release) var(--ease-spring-soft);

        &.is-pressing {
            transform: scale(0.96);
            transition-duration: var(--dur-press);
            transition-timing-function: var(--ease-out);
        }

        &::after {
            border: none;
        }

        &[disabled] {
            opacity: 0.6;
        }
    }

    &__submit-text {
        font-size: var(--fs-25);
        font-weight: var(--fw-medium);
        letter-spacing: 0.04em;
        color: var(--c-action-fg);
    }

    &__skip {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 88rpx;
        margin-top: var(--sp-1);
        padding: var(--sp-1) 0;
        box-sizing: border-box;
        text-align: center;
        font-size: var(--fs-20);
        color: var(--c-ink-3);
    }
}

/* v10 §3.13：减弱动效下按钮不缩放 */
@media (prefers-reduced-motion: reduce) {
    .pg__submit {
        transition: none;
    }
}
</style>
