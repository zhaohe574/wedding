<template>
    <view class="login">
        <nav-bar title="" transparent :show-back="true" />

        <view class="login__brand">
            <text class="login__title">婚礼服务</text>
            <text class="login__sub">选日期 · 看档期 · 在线预约</text>
        </view>

        <!-- 第一步：授权登录 -->
        <view v-if="step === 'auth'" class="login__panel">
            <view class="login__actions">
                <base-button text="微信一键登录" variant="brand" size="lg" :loading="loading" @click="handleLogin" />
                <text class="login__tip">未注册的微信号将自动创建账号</text>
            </view>

            <view hover-class="is-pressed" class="agree" @tap="agreed = !agreed">
                <view class="agree__box" :class="{ 'is-checked': agreed }">
                    <u-icon v-if="agreed" name="checkmark" :size="24" color="var(--c-on-brand)" />
                </view>
                <view class="agree__text">
                    <text>我已阅读并同意</text>
                    <text class="agree__link" @tap.stop="openAgreement('service')">《服务协议》</text>
                    <text>与</text>
                    <text class="agree__link" @tap.stop="openAgreement('privacy')">《隐私政策》</text>
                </view>
            </view>
        </view>

        <!-- 第二步：新用户补资料（可跳过） -->
        <view v-else class="login__panel">
            <text class="profile__title">完善资料</text>
            <text class="profile__sub">让顾问更容易联系到你</text>

            <view hover-class="is-pressed" class="profile__avatar" @tap="chooseAvatar">
                <image class="profile__avatar-img" :src="avatar" mode="aspectFill" />
                <text class="profile__avatar-tip">点击选择头像</text>
            </view>

            <view class="profile__field">
                <text class="profile__label">昵称</text>
                <input
                    v-model="nickname"
                    class="profile__input"
                    type="nickname"
                    placeholder="请输入昵称"
                    placeholder-class="profile__ph"
                    maxlength="20"
                />
            </view>

            <view class="profile__actions">
                <base-button text="完成" variant="brand" size="lg" :loading="loading" @click="handleSaveProfile" />
                <base-button text="跳过" variant="text" size="md" @click="finish" />
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
/**
 * 登录。
 *
 * 流程：勾选协议 → wx.login 拿 code → /login/mnpLogin 换 token
 *      → 新用户补头像昵称（可跳过）→ 回跳 BACK_URL。
 * 不强制绑手机号：手机号快速验证是独立授权，放在下单前再要，转化更好。
 */
import { ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { mnpLogin, updateUser } from '@/api/auth'
import { wxLogin } from '@/platform/login'
import { useUserStore } from '@/stores/user'
import cache from '@/utils/cache'
import { BACK_URL } from '@/enums/constantEnums'
import { navigate } from '@/utils/link'

const step = ref<'auth' | 'profile'>('auth')
const loading = ref(false)
const agreed = ref(false)
const nickname = ref('')
const avatar = ref('/static/images/user/default_avatar.png')
const redirect = ref('')

const userStore = useUserStore()

onLoad((options: any) => {
    redirect.value = options?.redirect ? decodeURIComponent(options.redirect) : cache.get(BACK_URL) || ''
})

function openAgreement(type: string) {
    navigate({ path: '/pages/agreement/agreement', query: { type } })
}

async function handleLogin() {
    if (!agreed.value) {
        uni.showToast({ title: '请先阅读并同意协议', icon: 'none' })
        return
    }
    loading.value = true
    try {
        const { code } = await wxLogin()
        const res = await mnpLogin({ code })
        if (!res || !res.token) throw new Error('登录失败')

        userStore.login(res.token)

        if (Number(res.is_new_user) === 1) {
            step.value = 'profile'
        } else {
            finish()
        }
    } catch (e: any) {
        uni.showToast({ title: e?.message || '登录失败，请重试', icon: 'none' })
    } finally {
        loading.value = false
    }
}

function chooseAvatar() {
    // #ifdef MP-WEIXIN
    uni.chooseImage({
        count: 1,
        sizeType: ['compressed'],
        success: (res) => {
            const path = res.tempFilePaths && res.tempFilePaths[0]
            if (path) avatar.value = path
        }
    })
    // #endif
}

async function handleSaveProfile() {
    loading.value = true
    try {
        await updateUser({
            nickname: nickname.value || undefined,
            headimgurl: avatar.value.startsWith('http') ? avatar.value : undefined
        })
    } catch {
        // 补资料失败不阻断登录，用户可以稍后在「我的-资料」里改
    } finally {
        loading.value = false
        finish()
    }
}

function finish() {
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
.login {
    min-height: 100vh;
    background: var(--c-bg);
    display: flex;
    flex-direction: column;

    &__brand {
        padding: 120rpx var(--sp-5) var(--sp-6);
        display: flex;
        flex-direction: column;
        gap: var(--sp-2);
    }

    &__title {
        font-size: var(--fs-2xl);
        font-weight: 500;
        color: var(--c-ink);
        letter-spacing: 2rpx;
    }

    &__sub {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__panel {
        flex: 1;
        padding: 0 var(--sp-5);
        display: flex;
        flex-direction: column;
    }

    &__actions {
        display: flex;
        flex-direction: column;
        gap: var(--sp-3);
    }

    &__tip {
        text-align: center;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.agree {
    display: flex;
    align-items: flex-start;
    gap: var(--sp-2);
    margin-top: var(--sp-6);
    padding-bottom: var(--sp-6);

    &__box {
        width: 32rpx;
        height: 32rpx;
        border-radius: 50%;
        border: 1rpx solid var(--c-line-strong);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-top: 4rpx;

        &.is-checked {
            background: var(--c-brand);
            border-color: var(--c-brand);
        }
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        line-height: 1.6;
    }

    &__link {
        color: var(--c-brand-text);
    }
}

.profile {
    &__title {
        font-size: var(--fs-xl);
        font-weight: 500;
        color: var(--c-ink);
    }

    &__sub {
        margin-top: 8rpx;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__avatar {
        margin: var(--sp-6) 0 var(--sp-5);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: var(--sp-2);
    }

    &__avatar-img {
        width: 160rpx;
        height: 160rpx;
        border-radius: 50%;
        background: var(--c-neutral-bg);
    }

    &__avatar-tip {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__field {
        display: flex;
        align-items: center;
        gap: var(--sp-3);
        background: var(--c-surface);
        border-radius: var(--r-md);
        padding: var(--sp-3) var(--sp-4);
    }

    &__label {
        font-size: var(--fs-md);
        color: var(--c-ink-2);
        width: 80rpx;
    }

    &__input {
        flex: 1;
        font-size: var(--fs-md);
        color: var(--c-ink);
    }

    &__ph {
        color: var(--c-ink-3);
    }

    &__actions {
        margin-top: var(--sp-6);
        display: flex;
        flex-direction: column;
        gap: var(--sp-2);
    }
}
</style>
