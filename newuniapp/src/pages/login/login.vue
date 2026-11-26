<template>
    <!--
        21 登录（设计稿 4062–4115）
        设计意图：品牌在上、动作在下；两个入口（微信为主 / 手机号为备）；协议单独占一行；
        底部留一条人工通道。手机号入口的显隐由后台开关 login.login_way 决定，
        开关没开就不渲染——否则就是一个点了会报「不支持的登录方式」的死按钮。
        ⚠ 有意偏离设计稿：协议勾选默认未勾（设计稿画的是已勾），默认同意在微信审核口径下会被打回。
    -->
    <page-shell
        nav
        nav-variant="scroll-solid"
        nav-tone="light"
        nav-background="page"
        :scroll-top="navScrollTop"
        :nav-solid-threshold="navSolidThreshold"
        bg="transparent"
        :show-back="true"
    >
        <view class="lg">
            <view class="lg__hero">
                <image class="lg__img" :src="heroImage" mode="aspectFill" @error="onHeroError" />
                <view class="lg__mask" />
                <view class="lg__brand">
                    <text class="lg__mark">{{ brand.name }}</text>
                    <text v-if="brand.slogan" class="lg__en">{{ brand.slogan }}</text>
                </view>
            </view>

            <view class="lg__sheet">
                <text class="lg__h2">先认识你们，再谈仪式。</text>
                <text class="lg__sub">登录后可查档期、收藏人员与订单进度</text>

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

        <template #bottom>
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
                </view>
            </tn-popup>

            <profile-guide-popup
                v-model="guidePopup"
                :step="guideStep"
                :initial-avatar="guideInitialAvatar"
                :need-mobile="guideNeedMobile"
                :show-step-badge="guideShowSteps"
                :force-mobile="guideForceMobile"
                :auth-token="pendingToken"
                @profile-saved="onProfileSaved"
                @profile-skip="onProfileSkip"
                @mobile-bound="onMobileBound"
                @mobile-skip="onMobileSkip"
                @mobile-cancel="onMobileCancel"
            />

            <app-toast />
        </template>
    </page-shell>
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
import { onLoad, onPageScroll, onShow, onUnload } from '@dcloudio/uni-app'
import { mnpLogin, accountLogin } from '@/api/auth'
import { smsSend } from '@/api/app'
import { wxLogin } from '@/platform/login'
import { getNavBarHeight } from '@/platform/device'
import { useUserStore } from '@/stores/user'
import { useAppStore } from '@/stores/app'
import ProfileGuidePopup from './components/profile-guide-popup.vue'
import cache from '@/utils/cache'
import { BACK_URL, BACK_URL_AT } from '@/enums/constantEnums'
import { navigate, TAB_PAGES } from '@/utils/link'
import { toast } from '@/composables/useToast'
import { FB } from '@/constants/feedback'
import { report } from '@/platform/report'
import { useBrand } from '@/composables/useBrand'
import { getDecorate } from '@/api/home'
import { findWidget, normalizeLoginHeroContent, parseDecoratePage } from '@shared/contracts'
import { shouldRemindAfterLogin, remindBeforeOaAction } from '@/utils/oa-reminder'

const DEFAULT_HERO_IMAGE = '/static/images/hero-ceremony.jpg'
const heroImage = ref(DEFAULT_HERO_IMAGE)

/**
 * 头图（.lg__hero 746rpx）一过顶就切实底导航：
 * 阈值 = 主图像素高 - 导航总高（状态栏 + 胶囊行），下限 40px 兜底。
 * scrollTop 只写 0 / 阈值+1 两档，避免滚动过程中高频 setData。
 */
const navSolidThreshold = Math.max(40, Math.round(uni.upx2px(746) - getNavBarHeight()))
const navScrollTop = ref(0)
onPageScroll((e: any) => {
    const top = Number(e?.scrollTop || 0)
    const next = top > navSolidThreshold ? navSolidThreshold + 1 : 0
    if (navScrollTop.value !== next) navScrollTop.value = next
})

/** 引导弹框：步骤 1 补头像昵称（新用户）→ 步骤 2 绑定手机号（coerce_mobile 开启且未绑时） */
const guidePopup = ref(false)
const guideStep = ref<'profile' | 'mobile'>('profile')
const guideInitialAvatar = ref('')
const guideNeedMobile = ref(false)
const guideShowSteps = ref(false)
/** 强制绑定模式（need_bind_mobile 拦截）：步骤 2 无跳过出口，取消 = 取消本次登录 */
const guideForceMobile = ref(false)
/**
 * 强制绑定流程的内存临时凭证：登录态尚未落库（不调用 userStore.login），
 * 只用来驱动引导弹框内的接口调用；绑定成功后才由 onMobileBound 正式持久化 app_token。
 */
const pendingToken = ref('')
const pendingUserInfo = ref<Record<string, any> | null>(null)

const loading = ref(false)
const agreed = ref(false)
const agreeError = ref(false)
const redirect = ref('')
/**
 * 登录页只执行一次「登录后去哪」。
 * 微信按钮虽然 disabled，但手机号弹层的登录按钮是异步置 loading，
 * 连点两下会各走一次 finish()，第二次的 navigateBack 会把原页面也弹掉。
 */
let finished = false

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
/** 品牌名称 / Slogan 取自后台配置（useBrand），登录页不再写死品牌字样 */
const { brand } = useBrand()

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

/** 后台登录配置是否开启「强制绑定手机号」 */
const coerceMobile = computed(() => Number((appStore.getLoginConfig as any)?.coerce_mobile) === 1)

onLoad((options: any) => {
    /*
     * 登录后的去向只有两种来源：
     *   A. 路由守卫拦下了 meta.auth 页面 —— BACK_URL 记着「本想去的那一页」，那页还没进栈；
     *   B. 页面里点了收藏 / 预约 / 提交才触发的登录 —— 没有 BACK_URL，登录后直接退回原页面，
     *      原页面实例还在栈里，已选内容天然保留。
     *
     * BACK_URL 必须带时间戳认：用户上次被拦下、又在登录页点了返回、过一阵从别处进来时，
     * 那条旧记录还在 storage 里，照它跳会把用户甩到别的页面。
     */
    const at = Number(cache.get(BACK_URL_AT) || 0)
    const fromGuard = at > 0 && Date.now() - at < 5000
    const explicit = options?.redirect ? decodeRedirect(options.redirect) : ''
    redirect.value = explicit || (fromGuard ? String(cache.get(BACK_URL) || '') : '')
    // 一次性：读出来就清，避免同一份目标被下一次登录复用
    cache.remove(BACK_URL)
    cache.remove(BACK_URL_AT)

    // 后端 need_bind_mobile 拦截回跳：拦截方已把临时凭证暂存进 temToken，
    // 这里直接打开强制绑手机弹窗（登录态仍未落库），绑定成功后在 onMobileBound 正式恢复
    if (String(options?.bind_mobile || '') === '1' && userStore.temToken) {
        pendingToken.value = userStore.temToken
        pendingUserInfo.value = null
        guideInitialAvatar.value = ''
        guideNeedMobile.value = true
        guideForceMobile.value = true
        guideShowSteps.value = false
        guideStep.value = 'mobile'
        guidePopup.value = true
    }

    void loadHeroDecorate()
})

onShow(() => {
    void appStore.getConfig(true).catch(() => undefined)
    void loadHeroDecorate()
})

/** 离开登录页即作废未完成的内存临时态：token 未落库，temToken 一并清掉 */
onUnload(() => {
    pendingToken.value = ''
    pendingUserInfo.value = null
    userStore.setTemToken(null)
})

function onHeroError() {
    if (heroImage.value !== DEFAULT_HERO_IMAGE) {
        heroImage.value = DEFAULT_HERO_IMAGE
    }
}

async function loadHeroDecorate() {
    try {
        const res = await getDecorate(7)
        if (!res) return
        const parsed = parseDecoratePage(res.data, res.meta)
        const widget = findWidget(parsed, 'login-hero')
        const content = normalizeLoginHeroContent(widget)
        heroImage.value = content.background_image || DEFAULT_HERO_IMAGE
    } catch {
        // 装修拉取失败时静默维持默认头图，不阻塞登录流程
    }
}

/** redirect 参数可能被编码过也可能没有，decode 失败就用原值，不能因为一个 % 崩掉整页 */
function decodeRedirect(raw: string) {
    try {
        return decodeURIComponent(raw)
    } catch {
        return String(raw)
    }
}

function openAgreement(type: string) {
    navigate({ path: '/pages/agreement/agreement', query: { type } })
}

function goConcierge() {
    navigate({ path: '/pages/customer-service/customer-service' })
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
        const [{ code: wxCode }] = await Promise.all([
            wxLogin(),
            appStore.getConfig(true).catch(() => undefined)
        ])
        const res = await mnpLogin({ code: wxCode })
        if (!res || !res.token) throw new Error('登录失败')

        const loginInfo: Record<string, any> = {
            id: res.id ?? res.user_id,
            sn: res.sn,
            nickname: res.nickname,
            avatar: res.avatar,
            mobile: res.mobile,
            is_new_user: res.is_new_user
        }
        const isNewUser = Number(res.is_new_user) === 1
        const needBindMobile = coerceMobile.value && !String(res.mobile || '').trim()

        if (needBindMobile) {
            // 强制绑定：严禁提前 userStore.login（会持久化 app_token）——
            // 凭证与资料只放内存 + temToken，绑定成功后才在 onMobileBound 正式落库
            if (userStore.isLogin) userStore.logout()
            pendingToken.value = res.token
            pendingUserInfo.value = loginInfo
            userStore.setTemToken(res.token)
            guideInitialAvatar.value = String(res.avatar || '').trim()
            guideNeedMobile.value = true
            guideForceMobile.value = true
            guideShowSteps.value = isNewUser
            guideStep.value = isNewUser ? 'profile' : 'mobile'
            guidePopup.value = true
            return
        }

        pendingToken.value = ''
        pendingUserInfo.value = null
        guideForceMobile.value = false
        // 正常登录：先落库登录态，接口返回的资料全量并入（新用户多为空，由引导弹框补齐）
        userStore.login(res.token)
        userStore.setUserInfo({ ...userStore.userInfo, ...loginInfo })

        if (isNewUser) {
            // 新用户：先进步骤 1（补头像昵称），无需绑手机
            guideInitialAvatar.value = String(res.avatar || '').trim()
            guideNeedMobile.value = false
            guideShowSteps.value = false
            guideStep.value = 'profile'
            guidePopup.value = true
            return
        }
        void finish()
    } catch (e: any) {
        toast.error(e?.message || FB.ERR_RELOGIN)
        report('api_error', { endpoint: 'login.mnpLogin', message: '微信登录失败' })
    } finally {
        loading.value = false
    }
}

/** 步骤 1 提交成功：资料并入 store（is_new_user 置 0），还需绑手机则平滑切步骤 2 */
function onProfileSaved({ avatar, nickname }: { avatar: string; nickname: string }) {
    if (guideNeedMobile.value) {
        // 强制绑定流程：登录态未落库，资料只进内存，绑定成功后随 onMobileBound 一并落库
        pendingUserInfo.value = { ...(pendingUserInfo.value || {}), avatar, nickname, is_new_user: 0 }
        toast.success('资料已保存')
        guideStep.value = 'mobile'
        return
    }
    userStore.setUserInfo({ ...userStore.userInfo, avatar, nickname, is_new_user: 0 })
    guidePopup.value = false
    void finish()
}

/** 步骤 1 跳过：保留登录态（默认头像/昵称），按需进步骤 2 或直接完成 */
function onProfileSkip() {
    if (guideNeedMobile.value) {
        // 强制模式下步骤 2 仍无出口，跳过步骤 1 只是进步骤 2，绕不开绑定
        guideStep.value = 'mobile'
        return
    }
    guidePopup.value = false
    void finish()
}

/** 步骤 2 绑定成功：此刻才正式落库登录态，合并临时/最新资料后完成登录 */
function onMobileBound({ mobile: boundMobile, userInfo: latestInfo }: { mobile: string; userInfo?: Record<string, any> | null }) {
    const finalToken = pendingToken.value || userStore.temToken || userStore.token || ''
    if (!finalToken) {
        // 无可用凭证（如页面被重载导致内存态丢失）：取消本次登录，提示重登
        guidePopup.value = false
        pendingToken.value = ''
        pendingUserInfo.value = null
        userStore.setTemToken(null)
        toast.error('登录状态已失效，请重新登录')
        return
    }
    // 绑定成功才允许持久化 app_token（login 内部会顺带作废 temToken）
    userStore.login(finalToken)
    userStore.setUserInfo({
        ...userStore.userInfo,
        ...(pendingUserInfo.value || {}),
        ...(latestInfo || {}),
        ...(boundMobile ? { mobile: boundMobile } : {})
    })
    pendingToken.value = ''
    pendingUserInfo.value = null
    guideForceMobile.value = false
    userStore.setTemToken(null)
    guidePopup.value = false
    void finish()
}

/** 步骤 2 跳过（仅非强制模式，含右上角关闭）：清理临时态后完成登录 */
function onMobileSkip() {
    pendingToken.value = ''
    pendingUserInfo.value = null
    guideForceMobile.value = false
    guidePopup.value = false
    void finish()
}

/** 强制绑定取消（底部「暂不绑定，取消登录」或右上角关闭）：取消本次登录，绝不 finish() */
function onMobileCancel() {
    guidePopup.value = false
    guideForceMobile.value = false
    pendingToken.value = ''
    pendingUserInfo.value = null
    userStore.setTemToken(null)
    userStore.logout()
    toast.info('需绑定手机号后才可完成登录')
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
        // 手机号登录若带回 id / mobile 就落库：remindBeforeOaAction 用 id 兜底本地跳过键
        const uid = res.id ?? res.user_id
        if (uid) {
            userStore.setUserInfo({
                ...userStore.userInfo,
                id: uid,
                mobile: res.mobile ?? mobile.value
            })
        }
        phonePopup.value = false
        void finish()
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

/**
 * 登录成功后的去向。
 *
 * 交互规范 §6.3：**登录成功回跳 navigateBack 到拦截前页面**，无来源才 switchTab 到「我的」。
 * 之前这里只有 `reLaunch(BACK_URL) / switchTab(首页)` 两条路，于是：
 *   - 内页点动作触发的登录（收藏 / 预约 / 提交）没有 BACK_URL → 一律回首页，
 *     而且 reLaunch 会把整摞页面销毁，用户选好的套餐、填了一半的联系人全丢；
 *   - 被守卫拦下的页面虽然能回去，但 reLaunch 重建了页面，同样丢状态。
 * 现在按来源分开处理，两条路都不再销毁原页面。
 *
 * 独立登录还会先过一次服务号提醒（remindBeforeOaAction）：用户点「查看绑定方法」
 * 时返回 false 暂停跳转，待其返回登录页后重新完成登录。
 */
async function finish() {
    if (finished) return
    finished = true

    if (timer) clearInterval(timer)
    timer = null

    if (shouldRemindAfterLogin()) {
        const proceed = await remindBeforeOaAction()
        if (!proceed) {
            // 用户选择去看绑定方法（或被离页/换号打断）：暂停本次跳转，
            // 放开 finished 让返回后能重新完成登录，同时复位验证码倒计时避免卡死
            finished = false
            counting.value = false
            return
        }
    }

    const target = redirect.value
    redirect.value = ''

    // ① 守卫拦下的目标页：那一页压根没进过栈，只能跳过去（不是「回去」）
    if (target && target.indexOf('/pages/login/login') === -1) {
        const path = target.split('?')[0]
        if (TAB_PAGES.has(path)) {
            uni.switchTab({ url: path })
            return
        }
        // redirectTo 而不是 reLaunch：登录页被替换成目标页，下面那摞（首页→列表→详情）还在，
        // 用户从目标页返回时能逐级退回，而不是一步掉回首页
        uni.redirectTo({ url: target, fail: () => uni.reLaunch({ url: target }) })
        return
    }

    // ② 内页触发的登录：退回原页面。
    //    原页面实例仍在栈里，已选套装/附加项/联系人输入都是内存里的原值，无需序列化重放。
    const pages = getCurrentPages()
    const prev: any = pages.length > 1 ? pages[pages.length - 2] : null
    if (prev && `/${prev.route}` !== '/pages/login/login') {
        uni.navigateBack({ delta: 1 })
        return
    }

    // ③ 无来源（深链直接落到登录页）：回「我的」
    uni.switchTab({ url: '/pages/user/user' })
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
        background: var(--c-dark-2);
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
            background: var(--c-action-bg);
            /* ⚠️ 必须在这里给 color：tn-icon 的 useComponentColor 只认 tn-* 类名和
               #hex / rgb() / rgba() / transparent，**var() 会被静默丢弃**（正则不匹配），
               图标于是继承父级颜色 = 原生 button 的默认黑 → 黑底黑图标看不见。
               颜色写在按钮上，图标靠继承拿到；文字由 .lg__btn-text 自己覆盖成反相色。 */
            color: var(--c-action-fg);
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
        color: var(--c-action-fg);

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
            background: var(--c-active-bg);
            border-color: var(--c-active-border);
            /* 勾选后的对勾图标靠继承拿色：tn-icon 不认 var()，见 .lg__btn--wechat 的注释。
               不写这一行，对勾会是默认黑 —— 黑勾压在墨黑圆点上，等于勾了看不出来。 */
            color: var(--c-active-fg);
        }
    }

    &__text {
        flex: 1;
        font-size: var(--fs-20);
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
        font-size: var(--fs-20);
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
        font-size: var(--fs-md);
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
}
</style>
