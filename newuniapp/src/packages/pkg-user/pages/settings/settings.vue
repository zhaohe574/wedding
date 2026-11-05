<template>
    <view class="page-wrap settings">
        <base-card :flush="true">
            <view hover-class="is-pressed" class="menu" @tap="openPassword">
                <text class="menu__label">修改密码</text>
                <u-icon name="arrow-right" :size="24" color="var(--c-ink-3)" />
            </view>
            <view hover-class="is-pressed" class="menu" @tap="openOa">
                <text class="menu__label">服务号消息</text>
                <text class="menu__value">{{ oaText }}</text>
                <u-icon name="arrow-right" :size="24" color="var(--c-ink-3)" />
            </view>
            <view hover-class="is-pressed" class="menu" @tap="openAgreement('service')">
                <text class="menu__label">服务协议</text>
                <u-icon name="arrow-right" :size="24" color="var(--c-ink-3)" />
            </view>
            <view hover-class="is-pressed" class="menu menu--last" @tap="openAgreement('privacy')">
                <text class="menu__label">隐私政策</text>
                <u-icon name="arrow-right" :size="24" color="var(--c-ink-3)" />
            </view>
        </base-card>

        <base-card>
            <view class="kv">
                <text class="kv__k">当前版本</text>
                <text class="kv__v">{{ version }}</text>
            </view>
        </base-card>

        <view class="logout">
            <text class="logout__hint">退出后仍可随时重新登录当前账号</text>
            <base-button text="退出登录" variant="danger" size="md" @click="handleLogout" />
        </view>

        <!-- 修改密码弹层 -->
        <view v-if="pwdVisible" class="mask" @tap="pwdVisible = false">
            <view class="sheet" @tap.stop>
                <text class="sheet__title">修改密码</text>
                <input v-model="pwd.old_password" class="sheet__input" password placeholder="当前密码" />
                <input v-model="pwd.password" class="sheet__input" password placeholder="新密码（6-20 位）" />
                <input v-model="pwd.password_confirm" class="sheet__input" password placeholder="确认新密码" />
                <view class="sheet__actions">
                    <base-button text="取消" variant="ghost" size="md" @click="pwdVisible = false" />
                    <base-button text="确认修改" variant="ink" size="md" :loading="pwdSaving" @click="submitPassword" />
                </view>
            </view>
        </view>
    </view>
    <app-toast />
</template>

<script setup lang="ts">
import { toast } from '@/composables/useToast'
/** 设置：改密 / 服务号 / 协议 / 版本 / 退出 */
import { reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { changePassword } from '@/api/auth'
import { getOaSubscribeStatus } from '@/api/user'
import { logout as logoutApi } from '@/api/auth'
import { useUserStore } from '@/stores/user'
import { navigate } from '@/utils/link'

const userStore = useUserStore()
const version = ref('1.0.0')
const oaText = ref('')
const pwdVisible = ref(false)
const pwdSaving = ref(false)
const pwd = reactive({ old_password: '', password: '', password_confirm: '' })

async function loadOa() {
    try {
        const res = await getOaSubscribeStatus()
        const bound = Number((res as any)?.bound) === 1
        oaText.value = bound ? '已绑定' : '未绑定'
    } catch {
        oaText.value = ''
    }
}

function openPassword() {
    pwdVisible.value = true
}

function openOa() {
    toast.info('请在公众号内完成绑定')
}

function openAgreement(type: string) {
    navigate({ path: '/pages/agreement/agreement', query: { type } })
}

async function submitPassword() {
    if (pwdSaving.value) return
    if (!pwd.old_password || !pwd.password) {
        toast.info('请填写完整')
        return
    }
    if (pwd.password.length < 6) {
        toast.info('新密码至少 6 位')
        return
    }
    if (pwd.password !== pwd.password_confirm) {
        toast.info('两次输入不一致')
        return
    }
    pwdSaving.value = true
    try {
        await changePassword({ ...pwd })
        toast.success('已修改')
        pwdVisible.value = false
        pwd.old_password = pwd.password = pwd.password_confirm = ''
    } catch {
        // 请求层已提示
    } finally {
        pwdSaving.value = false
    }
}

async function handleLogout() {
    const confirmed = await new Promise<boolean>((resolve) => {
        uni.showModal({
            title: '退出登录',
            content: '确定退出当前账号吗？',
            success: (r) => resolve(!!r.confirm),
            fail: () => resolve(false)
        })
    })
    if (!confirmed) return
    try {
        await logoutApi()
    } catch {
        // 忽略
    }
    userStore.logout()
    uni.reLaunch({ url: '/pages/index/index' })
}

onLoad(() => {
    try {
        const info = uni.getAccountInfoSync()
        version.value = (info as any)?.miniProgram?.version || '1.0.0'
    } catch {
        version.value = '1.0.0'
    }
    loadOa()
})
</script>

<style lang="scss" scoped>
.settings {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    padding: var(--sp-3) var(--sp-4) calc(var(--sp-4) + var(--safe-bottom));
}

.menu {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    min-height: 104rpx;
    padding: var(--sp-3) var(--sp-4);
    border-bottom: 1rpx solid var(--c-line);
    box-sizing: border-box;

    &--last {
        border-bottom: none;
    }

    &:active {
        background: var(--c-surface-2);
    }

    &__label {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-md);
        color: var(--c-ink);
    }

    &__value {
        max-width: 42%;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }
}

.kv {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-3);
    min-height: 64rpx;

    &__k {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &__v {
        min-width: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        overflow-wrap: anywhere;
    }
}

.logout {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--sp-3);
    margin-top: var(--sp-4);

    &__hint {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        text-align: center;
    }
}

.mask {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: var(--c-mask);
    z-index: 200;
    display: flex;
    align-items: flex-end;
}

.sheet {
    width: 100%;
    max-height: 80vh;
    background: var(--c-surface);
    border-radius: var(--r-lg) var(--r-lg) 0 0;
    padding: var(--sp-4);
    padding-bottom: calc(var(--sp-4) + var(--safe-bottom));
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    box-sizing: border-box;

    &__title {
        font-size: var(--fs-lg);
        font-weight: 500;
        color: var(--c-ink);
    }

    &__input {
        width: 100%;
        height: 88rpx;
        background: var(--c-surface-2);
        border: 1rpx solid var(--c-line);
        border-radius: var(--r-md);
        padding: 0 var(--sp-3);
        font-size: var(--fs-md);
        color: var(--c-ink);
        box-sizing: border-box;
    }

    &__actions {
        display: flex;
        gap: var(--sp-2);
        margin-top: var(--sp-2);
    }
}
</style>
