<template>
    <!-- 头部统一：走 page-shell 的 nav-bar，不再依赖系统导航 -->
    <page-shell title="设置" bg="page" :show-back="true">
        <view class="page-wrap settings">
            <base-card :flush="true">
                <view hover-class="is-pressed" class="menu" @tap="openPassword">
                    <text class="menu__label">修改密码</text>
                    <!-- v1 u-icon → v2 tn-icon：arrow-right → right（本文件 4 处同改） -->
                    <tn-icon name="right" :size="24" />
                </view>
                <view hover-class="is-pressed" class="menu" @tap="openOa">
                    <text class="menu__label">服务号消息</text>
                    <!-- P0 §2-7④：服务号状态异步取，首屏先骨架，避免闪「未绑定」 -->
                    <view v-if="metaLoading" class="menu__skeleton">
                        <base-skeleton :rows="1" variant="text" />
                    </view>
                    <text v-else class="menu__value">{{ oaText }}</text>
                    <!-- v1 u-icon → v2 tn-icon：arrow-right → right（本文件 4 处同改） -->
                    <tn-icon name="right" :size="24" />
                </view>
                <view hover-class="is-pressed" class="menu" @tap="openAgreement('service')">
                    <text class="menu__label">服务协议</text>
                    <!-- v1 u-icon → v2 tn-icon：arrow-right → right（本文件 4 处同改） -->
                    <tn-icon name="right" :size="24" />
                </view>
                <view hover-class="is-pressed" class="menu" @tap="openAgreement('privacy')">
                    <text class="menu__label">隐私政策</text>
                    <!-- v1 u-icon → v2 tn-icon：arrow-right → right（本文件 4 处同改） -->
                    <tn-icon name="right" :size="24" />
                </view>
                <!-- P0 §2-7③：版本行并入首张卡（原来单独占一张卡，信息密度过低） -->
                <view class="kv">
                    <text class="kv__k">当前版本</text>
                    <view v-if="metaLoading" class="kv__skeleton">
                        <base-skeleton :rows="1" variant="text" />
                    </view>
                    <text v-else class="kv__v">{{ version }}</text>
                </view>
                <!-- P0 §2-7⑥：退出登录降级为菜单末行（原实心 danger 按钮 + 提示语一并删除）。
                     与其余行同构：label + right 图标；点击走 uni.showModal 二次确认。 -->
                <view hover-class="is-pressed" class="menu menu--danger" @tap="handleLogout">
                    <text class="menu__label">退出登录</text>
                    <tn-icon name="right" :size="24" />
                </view>
            </base-card>

            <!-- 修改密码弹层 -->
            <view v-if="pwdVisible" class="mask" @tap="pwdVisible = false">
                <view class="sheet" @tap.stop>
                    <text class="sheet__title">修改密码</text>
                    <!-- P0 §2-7⑤：三字段失焦校验 + 错误落在字段正下方（01 §6.0：错误反馈不经 toast）
                         文案用肯定句：请输入当前密码 / 请输入 6-20 位新密码 / 两次输入的密码不一致 -->
                    <view class="sheet__field">
                        <input
                            v-model="pwd.old_password"
                            class="sheet__input"
                            password
                            placeholder="当前密码"
                            @blur="validatePwd('old_password')"
                        />
                        <text v-if="pwdError.old_password" class="sheet__err">{{
                            pwdError.old_password
                        }}</text>
                    </view>
                    <view class="sheet__field">
                        <input
                            v-model="pwd.password"
                            class="sheet__input"
                            password
                            placeholder="新密码（6-20 位）"
                            @blur="validatePwd('password')"
                        />
                        <text v-if="pwdError.password" class="sheet__err">{{
                            pwdError.password
                        }}</text>
                    </view>
                    <view class="sheet__field">
                        <input
                            v-model="pwd.password_confirm"
                            class="sheet__input"
                            password
                            placeholder="确认新密码"
                            @blur="validatePwd('password_confirm')"
                        />
                        <text v-if="pwdError.password_confirm" class="sheet__err">{{
                            pwdError.password_confirm
                        }}</text>
                    </view>
                    <view class="sheet__actions">
                        <base-button
                            text="取消"
                            variant="ghost"
                            size="md"
                            @click="pwdVisible = false"
                        />
                        <base-button
                            text="确认修改"
                            variant="ink"
                            size="md"
                            :loading="pwdSaving"
                            @click="submitPassword"
                        />
                    </view>
                </view>
            </view>
        </view>
        <app-toast />
    </page-shell>
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
/** 版本号与服务号状态是异步/首帧后才有值，先给骨架避免闪默认值 */
const metaLoading = ref(true)
const pwdVisible = ref(false)
const pwdSaving = ref(false)
const pwd = reactive({ old_password: '', password: '', password_confirm: '' })
/** 字段级错误：落在各字段正下方，不走 toast */
const pwdError = reactive({ old_password: '', password: '', password_confirm: '' })

/** 单字段校验（失焦触发）；返回是否通过 */
function validatePwd(key: keyof typeof pwdError) {
    if (key === 'old_password') {
        pwdError.old_password = pwd.old_password ? '' : '请输入当前密码'
    } else if (key === 'password') {
        const len = pwd.password.length
        pwdError.password = len >= 6 && len <= 20 ? '' : '请输入 6-20 位新密码'
    } else {
        pwdError.password_confirm =
            pwd.password_confirm && pwd.password_confirm === pwd.password
                ? ''
                : '两次输入的密码不一致'
    }
    return !pwdError[key]
}

/** 提交前整体校验：失焦只在 blur 触发，用户直接点「确认修改」时可能还没校验过 */
function validateAllPwd() {
    // 三个都要跑，不要用 && 短路 —— 否则只有第一个错误会显示
    const a = validatePwd('old_password')
    const b = validatePwd('password')
    const c = validatePwd('password_confirm')
    return a && b && c
}

async function loadOa() {
    try {
        const res = await getOaSubscribeStatus()
        const bound = Number((res as any)?.bound) === 1
        oaText.value = bound ? '已绑定' : '未绑定'
    } catch {
        oaText.value = ''
    } finally {
        metaLoading.value = false
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
    // 校验从 toast 改为字段下方红字（01 §6.0）；三个字段一起校验，不短路
    if (!validateAllPwd()) return
    pwdSaving.value = true
    try {
        await changePassword({ ...pwd })
        toast.success('已修改')
        pwdVisible.value = false
        pwd.old_password = pwd.password = pwd.password_confirm = ''
        pwdError.old_password = pwdError.password = pwdError.password_confirm = ''
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
            // P0 §2-7⑥：内容讲代价（原来讲"仍可重新登录"，与确认弹窗语气矛盾）
            content: '退出后需要重新登录才能查看订单',
            confirmText: '退出登录',
            cancelText: '取消',
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
    /* 行尾箭头继承父级；.menu__label（含 --danger 变体）/ .menu__value 各自声明了颜色 */
    color: var(--c-ink-3);
    min-height: 104rpx;
    padding: var(--sp-3) var(--sp-4);
    box-sizing: border-box;

    /* P0 §2-7①：分割线用 :not(:last-child)，不再手写 .menu--last ——
       末行是靠位置决定的，新增行会自动接上分割线，不会漏改 */
    &:not(:last-child) {
        border-bottom: 1rpx solid var(--c-line);
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

    /* 退出登录：与其余行同构，只把 label 染成危险色 */
    &--danger &__label {
        color: var(--c-danger);
    }

    &__value {
        max-width: 42%;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    &__skeleton {
        width: 120rpx;
    }
}

.kv {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-3);
    min-height: 64rpx;
    /* 并入首张 flush 卡后需自带左右内边距，与 .menu 对齐 */
    padding: var(--sp-2) var(--sp-4);

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

    &__skeleton {
        width: 120rpx;
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

    /* 字段容器：错误红字紧贴输入框下方 */
    &__field {
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
    }

    &__err {
        display: block;
        font-size: var(--fs-md);
        color: var(--c-danger);
        line-height: var(--lh-body);
    }

    &__actions {
        display: flex;
        gap: var(--sp-2);
        margin-top: var(--sp-2);
    }
}
</style>
