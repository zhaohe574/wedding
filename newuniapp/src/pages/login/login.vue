<template>
    <!--
        21 登录（设计稿 4062–4115）
        设计意图：品牌在上、动作在下；两个入口（微信为主 / 手机号为备）；协议单独占一行；
        底部留一条人工通道。手机号入口的显隐由后台开关 login.login_way 决定，
        开关没开就不渲染——否则就是一个点了会报「不支持的登录方式」的死按钮。
        ⚠ 有意偏离设计稿：协议勾选默认未勾（设计稿画的是已勾），默认同意在微信审核口径下会被打回。
    -->
    <page-shell nav nav-variant="transparent" bg="transparent" :show-back="true" nav-tone="light">
        <view class="lg">
            <view class="lg__hero">
                <image class="lg__img" src="/static/images/hero-ceremony.jpg" mode="aspectFill" />
                <view class="lg__mask" />
                <view class="lg__brand">
                    <text class="lg__mark">{{ BRAND_NAME }}</text>
                    <text class="lg__en">{{ BRAND_NAME_EN }}</text>
                    <text class="lg__tag">婚礼纪事 · 尊享全流程管家服务</text>
                </view>
            </view>

            <view class="lg__sheet">
                <text class="lg__h2">先认识你们，再谈仪式。</text>
                <text class="lg__sub">登录后可查档期、收藏主持人，随时看订单进度与尾款。</text>

                <button class="lg__btn lg__btn--wechat" :disabled="loading" @tap="onWechat">
                    <tn-icon name="wechat" :size="34" />
                    <text class="lg__btn-text">{{ loading ? '登录中…' : '微信一键登录' }}</text>
                </button>

                <template v-if="phoneEntryVisible">
                    <view class="lg__or"><text class="lg__or-text">或</text></view>
                    <button class="lg__btn lg__btn--phone" @tap="openPhone">
                        <tn-icon name="iphone" :size="28" />
                        <text class="lg__btn-text lg__btn-text--ink">手机号快捷登录</text>
                    </button>
                </template>

                <view class="agree">
                    <view
                        class="agree__row"
                        hover-class="is-pressed"
                        aria-role="checkbox"
                        :aria-checked="agreed"
                        aria-label="我已阅读并同意用户协议与隐私政策"
                        @tap="agreed = !agreed"
                    >
                        <view class="agree__box" :class="{ 'is-checked': agreed }">
                            <tn-icon v-if="agreed" name="check" :size="20" />
                        </view>
                        <view class="agree__text">
                            <text>我已阅读并同意</text>
                            <text class="agree__link" @tap.stop="openAgreement('service')"
                                >《用户协议》</text
                            >
                            <text>与</text>
                            <text class="agree__link" @tap.stop="openAgreement('privacy')"
                                >《隐私政策》</text
                            >
                            <text>。手机号登录仅限已注册账号。</text>
                        </view>
                    </view>
                    <!-- 未勾选时按钮不置灰，点按在这里出红字（与设计稿的校验口径一致） -->
                    <text v-if="agreeError" class="agree__err">请先阅读并勾选协议</text>
                </view>

                <view class="lg__foot">
                    <text class="lg__foot-text">遇到登录问题？</text>
                    <text class="lg__foot-link" @tap="goConcierge">联系专属顾问</text>
                </view>
            </view>
        </view>
    </page-shell>

    <!-- 手机验证码登录：设计稿没有这一步，按同构样式内联成弹层，不新开页面 -->
    <!--
        ⚠️ 这三个 prop 名必须照 tn-popup 的真实定义写（node_modules/@tuniao/tnui-vue3-uniapp/
        components/popup/src/popup.ts），写错不会报错、不会告警，只会静默失效：
          v-model:show → v-model（组件收的是 modelValue，不是 show）
          mode         → open-direction（组件只有 openDirection，默认 'center'）
          :round       → :radius
        此前三处全写错，导致「手机号快捷登录」点了毫无反应 —— phonePopup 置了 true，
        但组件收不到，弹层永远不出现。
    -->
    <tn-popup
        v-model="phonePopup"
        open-direction="bottom"
        :radius="24"
        :safe-area-inset-bottom="true"
    >
        <view class="pp">
            <view class="pp__head">
                <view class="pp__head-text">
                    <text class="pp__title">手机号登录</text>
                    <text class="pp__sub">验证码 5 分钟内有效，同一号码 1 分钟可发 1 条</text>
                </view>
                <!-- 显式关闭入口：此前只能点遮罩或下滑，弹层本身没有可点的关闭按钮 -->
                <view
                    class="pp__close"
                    hover-class="is-pressed"
                    aria-role="button"
                    aria-label="关闭"
                    @tap="phonePopup = false"
                >
                    <tn-icon name="close" :size="28" />
                </view>
            </view>

            <view class="pp__field" :class="{ 'is-err': mobileError }">
                <text class="pp__label">手机号</text>
                <input
                    v-model="mobile"
                    class="pp__input"
                    type="number"
                    maxlength="11"
                    placeholder="请输入 11 位手机号"
                    placeholder-class="pp__ph"
                    @input="mobileError = ''"
                />
            </view>
            <text v-if="mobileError" class="pp__err">{{ mobileError }}</text>

            <view class="pp__field" :class="{ 'is-err': codeError }">
                <text class="pp__label">验证码</text>
                <input
                    v-model="code"
                    class="pp__input"
                    type="number"
                    maxlength="6"
                    placeholder="6 位数字验证码"
                    placeholder-class="pp__ph"
                    @input="codeError = ''"
                />
                <text class="pp__send" :class="{ 'is-off': counting || sending }" @tap="sendCode">
                    {{ counting ? `${countdown}s 后重发` : sending ? '发送中…' : '获取验证码' }}
                </text>
            </view>
            <text v-if="codeError" class="pp__err">{{ codeError }}</text>

            <base-button
                class="pp__submit"
                text="登录"
                variant="ink"
                size="lg"
                :loading="phoneLoading"
                @click="submitPhone"
            />
            <text class="pp__tip">未注册的手机号无法登录，请改用微信一键登录</text>
        </view>
    </tn-popup>

    <app-toast />
</template>

<script setup lang="ts">
/**
 * 登录。
 *
 * 两个入口：
 *  - 微信一键登录：wx.login 拿 code → /login/mnpLogin → token
 *  - 手机号验证码：/sms/sendCode(scene=YZMDL) → /login/account{terminal:1,scene:2}
 *
 * 三个容易踩的点（都已处理）：
 *  1. 手机号入口必须按 config.login.login_way 显隐，否则是死按钮
 *  2. 验证码登录查不到用户时后端直接返回「用户不存在」，不会自动注册
 *     —— 设计稿那句「未注册手机号将自动创建账号」是错的，已改写文案
 *  3. 倒计时 60s 与后端「同号 1 分钟 1 条」对齐
 */
import { computed, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { mnpLogin, accountLogin } from '@/api/auth'
import { smsSend } from '@/api/app'
import { wxLogin } from '@/platform/login'
import { useUserStore } from '@/stores/user'
import { useAppStore } from '@/stores/app'
import cache from '@/utils/cache'
import { BACK_URL } from '@/enums/constantEnums'
import { navigate } from '@/utils/link'
import { toast } from '@/composables/useToast'
import { FB } from '@/constants/feedback'
import { report } from '@/platform/report'
import { BRAND_NAME, BRAND_NAME_EN } from '@/constants/brand'

const step = ref<'auth' | 'profile'>('auth')
const loading = ref(false)
const agreed = ref(false)
const agreeError = ref(false)
const redirect = ref('')

const phonePopup = ref(false)
const mobile = ref('')
const code = ref('')
const mobileError = ref('')
const codeError = ref('')
const sending = ref(false)
const phoneLoading = ref(false)
const counting = ref(false)
const countdown = ref(60)
let timer: ReturnType<typeof setInterval> | null = null

const userStore = useUserStore()
const appStore = useAppStore()

/**
 * 手机号入口是否显示。
 * login_way 后端是数组（1 账号密码 / 2 手机验证码），也可能是逗号串，两种都兜。
 * 拿不到配置时按「不显示」处理 —— 宁可少一个入口，也不要给一个点了报错的按钮。
 */
const phoneEntryVisible = computed(() => {
    const raw = (appStore.getLoginConfig as any)?.login_way
    if (raw === undefined || raw === null || raw === '') return false
    const list = Array.isArray(raw) ? raw : String(raw).split(',')
    return list.map((v: any) => String(v).trim()).includes('2')
})

onLoad((options: any) => {
    redirect.value = options?.redirect
        ? decodeURIComponent(options.redirect)
        : cache.get(BACK_URL) || ''
    // 登录页也需要开关，没拉过配置就补一次（失败静默，不阻塞登录）
    if (!Object.keys(appStore.getLoginConfig || {}).length) {
        appStore.getConfig().catch(() => undefined)
    }
})

function openAgreement(type: string) {
    navigate({ path: '/pages/agreement/agreement', query: { type } })
}

function goConcierge() {
    navigate({ path: '/packages/pkg-service/pages/concierge/concierge' })
}

/** 微信登录 */
async function onWechat() {
    if (!agreed.value) {
        agreeError.value = true
        return
    }
    agreeError.value = false
    loading.value = true
    try {
        const { code: wxCode } = await wxLogin()
        const res = await mnpLogin({ code: wxCode })
        if (!res || !res.token) throw new Error('登录失败')
        userStore.login(res.token)
        if (Number(res.is_new_user) === 1) {
            step.value = 'profile'
            // 新用户补资料不是本屏重点，直接放过，资料可在「我的」里改
            finish()
        } else {
            finish()
        }
    } catch (e: any) {
        toast.error(e?.message || FB.ERR_RELOGIN)
        report('api_error', { endpoint: 'login.mnpLogin', message: '微信登录失败' })
    } finally {
        loading.value = false
    }
}

function openPhone() {
    phonePopup.value = true
}

function startCountdown() {
    counting.value = true
    countdown.value = 60
    if (timer) clearInterval(timer)
    timer = setInterval(() => {
        countdown.value -= 1
        if (countdown.value <= 0) {
            counting.value = false
            if (timer) clearInterval(timer)
            timer = null
        }
    }, 1000)
}

async function sendCode() {
    if (counting.value || sending.value) return
    mobileError.value = ''
    if (!/^1\d{10}$/.test(mobile.value)) {
        mobileError.value = '请输入正确的 11 位手机号'
        return
    }
    sending.value = true
    try {
        await smsSend({ mobile: mobile.value, scene: 'YZMDL' })
        startCountdown()
        toast.success('验证码已发送')
    } catch (e: any) {
        mobileError.value = e?.message || '验证码发送失败，请稍后重试'
    } finally {
        sending.value = false
    }
}

async function submitPhone() {
    mobileError.value = ''
    codeError.value = ''
    if (!/^1\d{10}$/.test(mobile.value)) {
        mobileError.value = '请输入正确的 11 位手机号'
        return
    }
    if (!/^\d{6}$/.test(code.value)) {
        codeError.value = '请输入 6 位数字验证码'
        return
    }
    if (!agreed.value) {
        agreeError.value = true
        phonePopup.value = false
        return
    }
    phoneLoading.value = true
    try {
        const res: any = await accountLogin({ account: mobile.value, code: code.value })
        if (!res || !res.token) throw new Error('登录失败')
        userStore.login(res.token)
        phonePopup.value = false
        finish()
    } catch (e: any) {
        const msg = String(e?.message || '')
        if (msg.indexOf('不存在') > -1 || msg.indexOf('未注册') > -1) {
            codeError.value = '该手机号未注册，请改用微信一键登录'
        } else {
            codeError.value = msg || '登录失败，请重试'
        }
        report('api_error', { endpoint: 'login.account', message: '验证码登录失败' })
    } finally {
        phoneLoading.value = false
    }
}

function finish() {
    if (timer) clearInterval(timer)
    timer = null
    cache.remove(BACK_URL)
    const target = redirect.value
    if (target && target.indexOf('/pages/login/login') === -1) {
        uni.reLaunch({ url: target })
        return
    }
    uni.switchTab({ url: '/pages/index/index' })
}
</script>

<style lang="scss" scoped>
.lg {
    min-height: 100vh;
    background: var(--c-bg);
    display: flex;
    flex-direction: column;

    &__hero {
        position: relative;
        height: 746rpx; /* 388px */
        flex: none;
        overflow: hidden;
        background: var(--c-ink);
    }

    &__img {
        width: 100%;
        height: 100%;
        display: block;
    }

    &__mask {
        position: absolute;
        left: 0;
        right: 0;
        top: 0;
        bottom: 0;
        background: linear-gradient(
            180deg,
            rgba(20, 17, 14, 0.55) 0%,
            rgba(20, 17, 14, 0.05) 34%,
            rgba(20, 17, 14, 0.42) 68%,
            rgba(20, 17, 14, 0.92) 100%
        );
    }

    &__brand {
        position: absolute;
        left: 50rpx;
        right: 50rpx;
        bottom: 54rpx;
    }

    &__mark {
        display: block;
        font-family: var(--font-serif);
        font-size: var(--fs-63); /* 33px */
        letter-spacing: 0.3em;
        color: var(--c-on-dark);
        line-height: 1;
    }

    &__en {
        display: block;
        margin-top: 14rpx;
        font-family: var(--font-serif);
        font-size: var(--fs-15);
        letter-spacing: 0.44em;
        color: var(--c-brand-soft);
    }

    &__tag {
        display: block;
        margin-top: 28rpx;
        font-size: var(--fs-19);
        letter-spacing: 0.1em;
        color: rgba(255, 253, 248, 0.62);
    }

    &__sheet {
        flex: 1;
        /* 底部留白必须带上安全区：原来 padding-bottom 是 0、只靠 __foot 自己的 40rpx，
           在带 Home 指示条的机型上「联系专属顾问」会压在指示条区域里 */
        padding: 46rpx 50rpx calc(40rpx + var(--safe-bottom));
        display: flex;
        flex-direction: column;
        background: var(--c-bg);
    }

    &__h2 {
        font-size: var(--fs-37); /* 19px */
        font-weight: var(--fw-semibold);
        line-height: 1.52;
        letter-spacing: 0.02em;
        color: var(--c-ink);
        margin-top: 14rpx;
    }

    &__sub {
        margin-top: 18rpx;
        font-size: var(--fs-19);
        line-height: 1.8;
        letter-spacing: 0.02em;
        color: var(--c-ink-3);
    }

    &__btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 14rpx;
        /* 原生 button 的默认值必须清掉，否则内容区被挤、行高异常：
           padding 默认 0 14px、line-height 默认 2.55555556、box-sizing 非 border-box。
           同站 concierge 的 .cta 用同一套重置，是已验证可用的写法。 */
        width: 100%;
        padding: 0;
        line-height: normal;
        box-sizing: border-box;
        border: 0;
        font-family: inherit;

        &::after {
            border: 0;
        }

        &--wechat {
            margin-top: 40rpx;
            height: 87rpx; /* 45px */
            border-radius: var(--r-24);
            background: var(--c-ink);
            /* ⚠️ 必须在这里给 color：tn-icon 的 useComponentColor 只认 tn-* 类名和
               #hex / rgb() / rgba() / transparent，**var() 会被静默丢弃**（正则不匹配），
               图标于是继承父级颜色 = 原生 button 的默认黑 → 黑底黑图标看不见。
               颜色写在按钮上，图标靠继承拿到；文字由 .lg__btn-text 自己覆盖成白色。 */
            color: var(--c-brand-soft);
        }

        &--phone {
            height: 83rpx; /* 43px */
            border-radius: var(--r-24);
            background: transparent;
            border: 1rpx solid var(--c-control-border);
            /* 同上：图标颜色靠继承，不能靠 color prop 传 var() */
            color: var(--c-ink-2);
        }
    }

    &__btn-text {
        font-size: var(--fs-25);
        font-weight: var(--fw-medium);
        letter-spacing: 0.04em;
        color: var(--c-on-dark);

        &--ink {
            color: var(--c-ink);
        }
    }

    &__or {
        display: flex;
        align-items: center;
        /* 只有一个「或」文本节点，flex 默认 flex-start 会把它顶到左边；
           这一行是分隔符，必须居中 */
        justify-content: center;
        gap: 21rpx;
        margin: 29rpx 0;
    }

    &__or-text {
        font-size: var(--fs-17);
        letter-spacing: 0.12em;
        color: var(--c-ink-3);
    }

    &__foot {
        margin-top: auto;
        /* 底部留白交给 __sheet 的 padding-bottom（含安全区），这里不再重复加 40rpx */
        padding: 36rpx 0 0;
        text-align: center;
    }

    &__foot-text {
        font-size: var(--fs-19);
        color: var(--c-ink-3);
    }

    &__foot-link {
        font-size: var(--fs-19);
        color: var(--c-brand-text);
        margin-left: 6rpx;
    }
}

.agree {
    margin-top: 30rpx;

    &__row {
        display: flex;
        align-items: flex-start;
        gap: 14rpx;
    }

    &__box {
        width: 32rpx;
        height: 32rpx;
        border-radius: var(--r-pill);
        border: 1rpx solid var(--c-control-border);
        display: flex;
        align-items: center;
        justify-content: center;
        flex: none;
        margin-top: 4rpx;

        &.is-checked {
            background: var(--c-ink);
            border-color: var(--c-ink);
            /* 勾选后的对勾图标靠继承拿色：tn-icon 不认 var()，见 .lg__btn--wechat 的注释。
               不写这一行，对勾会是默认黑 —— 黑勾压在墨黑圆点上，等于勾了看不出来。 */
            color: var(--c-on-dark);
        }
    }

    &__text {
        flex: 1;
        font-size: 20rpx;
        line-height: 1.7;
        color: var(--c-ink-3);
    }

    &__link {
        color: var(--c-brand-text);
    }

    &__err {
        display: block;
        margin-top: 10rpx;
        margin-left: 46rpx;
        font-size: 20rpx;
        color: var(--c-danger);
    }
}

.pp {
    padding: 40rpx 42rpx calc(32rpx + var(--safe-bottom));

    &__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: var(--sp-2);
        margin-bottom: 30rpx;
    }

    &__head-text {
        flex: 1;
        min-width: 0;
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
        margin-top: 10rpx;
        font-size: var(--fs-21);
        color: var(--c-ink-3);
        line-height: var(--lh-body);
    }

    &__field {
        display: flex;
        align-items: center;
        gap: 16rpx;
        height: 96rpx;
        padding: 0 24rpx;
        border-radius: var(--r-md);
        background: var(--c-surface-2);
        border: 1rpx solid transparent;

        & + & {
            margin-top: 20rpx;
        }

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
        font-size: 28rpx;
        color: var(--c-ink);
    }

    &__ph {
        color: var(--c-ink-3);
    }

    &__send {
        flex: none;
        font-size: var(--fs-23);
        color: var(--c-brand-text);

        &.is-off {
            color: var(--c-ink-3);
        }
    }

    &__err {
        display: block;
        margin: 10rpx 0 0 24rpx;
        font-size: var(--fs-21);
        color: var(--c-danger);
    }

    &__submit {
        /* 验证码框 96rpx 高，34rpx 间距视觉上几乎粘连；提到 --sp-5(48rpx) */
        margin-top: var(--sp-5);
    }

    &__tip {
        display: block;
        margin-top: 18rpx;
        text-align: center;
        font-size: 20rpx;
        color: var(--c-ink-3);
    }
}
</style>
