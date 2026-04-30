<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="个人资料" @back="handleBack" />

        <view class="user-data-page">
            <view class="page-content wm-page-content">
                <view class="profile-card wm-panel-card">
                    <view class="profile-card__avatar">
                        <avatar-upload
                            :modelValue="form.avatar"
                            file-key="url"
                            :round="true"
                            :size="116"
                            @update:modelValue="handleAvatarChange"
                        />
                    </view>
                    <view class="profile-card__body">
                        <view class="profile-card__tag">个人信息</view>
                        <text class="profile-card__name">{{ displayName }}</text>
                        <text class="profile-card__meta">{{ profileMetaText }}</text>
                    </view>
                </view>

                <view class="section-card wm-form-block">
                    <view class="section-head">
                        <text class="section-title">账号信息</text>
                        <text class="section-desc">账号和手机号用于登录与安全验证</text>
                    </view>

                    <view class="info-row wm-soft-card">
                        <view class="info-row__main">
                            <text class="field-label">账号</text>
                            <text class="field-value">{{ accountText }}</text>
                        </view>
                        <view class="inline-action" @click="handleAccountClick">修改</view>
                    </view>

                    <view class="info-row wm-soft-card">
                        <view class="info-row__main">
                            <text class="field-label">用户编号</text>
                            <text class="field-value">{{ userSnText }}</text>
                        </view>
                    </view>

                    <view class="info-row wm-soft-card">
                        <view class="info-row__main">
                            <text class="field-label">手机号</text>
                            <text class="field-value">{{ mobileText }}</text>
                        </view>
                        <!-- #ifdef MP-WEIXIN -->
                        <button
                            class="inline-action inline-action--button"
                            open-type="getPhoneNumber"
                            hover-class="none"
                            :disabled="mobileAuthDisabled"
                            @getphonenumber="getPhoneNumber"
                        >
                            {{ mobileSaving ? '处理中' : userInfo.mobile ? '更换' : '绑定' }}
                        </button>
                        <!-- #endif -->
                        <!-- #ifndef MP-WEIXIN -->
                        <view class="inline-action" @click="handleMobileClick">
                            {{ userInfo.mobile ? '更换' : '绑定' }}
                        </view>
                        <!-- #endif -->
                    </view>

                    <view class="info-row wm-soft-card">
                        <view class="info-row__main">
                            <text class="field-label">注册时间</text>
                            <text class="field-value">{{ createTimeText }}</text>
                        </view>
                    </view>
                </view>

                <view class="section-card wm-form-block">
                    <view class="section-head">
                        <text class="section-title">人员信息</text>
                        <text class="section-desc">完善昵称、称呼和性别，便于服务沟通</text>
                    </view>

                    <view class="field-card wm-soft-card">
                        <text class="field-label">昵称</text>
                        <input
                            v-model="form.nickname"
                            class="field-input"
                            placeholder="请输入昵称"
                            placeholder-class="field-placeholder"
                            maxlength="32"
                        />
                    </view>

                    <view class="field-card wm-soft-card">
                        <text class="field-label">姓名 / 称呼</text>
                        <input
                            v-model="form.real_name"
                            class="field-input"
                            placeholder="请输入姓名或称呼"
                            placeholder-class="field-placeholder"
                            maxlength="32"
                        />
                    </view>

                    <view class="field-card field-card--click wm-soft-card" @click="handleSexClick">
                        <view class="field-card__main">
                            <text class="field-label">性别</text>
                            <text class="field-value">{{ getSexText(form.sex) }}</text>
                        </view>
                        <tn-icon name="right" size="26" color="#9a9388" />
                    </view>
                </view>
            </view>

            <ActionArea class="user-data-page__actions" sticky safeBottom>
                <BaseButton block size="lg" :loading="saving" @click="handleSaveProfile">
                    保存资料
                </BaseButton>
            </ActionArea>

            <BaseOverlayMask :show="showUserName" @close="showUserName = false" />
            <tn-popup
                v-model="showUserName"
                :close-btn="true"
                open-direction="center"
                :radius="24"
                :overlay="false"
                :overlay-closeable="true"
            >
                <view class="edit-popup wm-form-block">
                    <view class="popup-title">修改账号</view>
                    <view class="popup-input-wrapper">
                        <input
                            v-model="newUsername"
                            class="popup-input"
                            placeholder="请输入账号"
                            placeholder-class="input-placeholder"
                            maxlength="30"
                        />
                    </view>
                    <view class="popup-actions">
                        <button
                            class="popup-btn popup-btn-primary"
                            :disabled="accountSaving"
                            hover-class="none"
                            @click="changeUserNameConfirm"
                        >
                            {{ accountSaving ? '保存中...' : '确定' }}
                        </button>
                    </view>
                </view>
            </tn-popup>

            <BaseOverlayMask :show="showMobilePop" @close="showMobilePop = false" />
            <tn-popup
                v-model="showMobilePop"
                :close-btn="true"
                open-direction="center"
                :radius="24"
                :overlay="false"
                :overlay-closeable="true"
            >
                <view class="edit-popup wm-form-block">
                    <view class="popup-title">
                        {{ userInfo.mobile ? '更换手机号' : '绑定手机号' }}
                    </view>
                    <view class="popup-input-wrapper">
                        <input
                            v-model="newMobile"
                            class="popup-input"
                            type="number"
                            placeholder="请输入新的手机号码"
                            placeholder-class="input-placeholder"
                            maxlength="11"
                        />
                    </view>
                    <view class="popup-input-wrapper code-input-wrapper">
                        <input
                            v-model="mobileCode"
                            class="flex-1 popup-input"
                            type="number"
                            placeholder="请输入验证码"
                            placeholder-class="input-placeholder"
                        />
                        <view
                            class="code-btn"
                            :class="{ 'code-btn-disabled': !canGetCode || smsSending }"
                            @click="sendSms"
                        >
                            {{ codeTips }}
                        </view>
                    </view>
                    <view class="popup-actions">
                        <button
                            class="popup-btn popup-btn-primary"
                            :disabled="mobileSaving"
                            hover-class="none"
                            @click="changeCodeMobile"
                        >
                            {{ mobileSaving ? '保存中...' : '确定' }}
                        </button>
                    </view>
                </view>
            </tn-popup>

            <tn-picker
                v-model="selectedSex"
                v-model:open="showSexPicker"
                :data="sexPickerData"
                @confirm="handleSexConfirm"
            />
        </view>
    </PageShell>
</template>

<script lang="ts" setup>
import { smsSend } from '@/api/app'
import { getUserInfo, userBindMobile, userEdit, userMnpMobile } from '@/api/user'
import ActionArea from '@/components/base/ActionArea.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import { FieldType, SMSEnum } from '@/enums/appEnums'
import PageShell from '@/components/base/PageShell.vue'
import { useThemeStore } from '@/stores/theme'
import { useUserStore } from '@/stores/user'
import { onShow, onUnload } from '@dcloudio/uni-app'
import { computed, reactive, ref } from 'vue'

const $theme = useThemeStore()
const userStore = useUserStore()

const saving = ref(false)
const accountSaving = ref(false)
const mobileSaving = ref(false)
const smsSending = ref(false)
const phoneAuthCooling = ref(false)
const mobileReg = /^1\d{10}$/
let phoneAuthCooldownTimer: ReturnType<typeof setTimeout> | null = null

const userInfo = reactive<any>({})
const form = reactive({
    avatar: '',
    nickname: '',
    real_name: '',
    sex: 0
})
const originalForm = reactive({
    avatar: '',
    nickname: '',
    real_name: '',
    sex: 0
})

const showUserName = ref(false)
const showMobilePop = ref(false)
const showSexPicker = ref(false)
const selectedSex = ref<number | undefined>(0)
const newUsername = ref('')
const newMobile = ref('')
const mobileCode = ref('')
const codeTips = ref('获取验证码')
const canGetCode = ref(true)
let codeTimer: ReturnType<typeof setInterval> | null = null

const sexPickerData = [
    { label: '未知', value: 0 },
    { label: '男', value: 1 },
    { label: '女', value: 2 }
]

const displayName = computed(() => {
    const realName = String(form.real_name || '').trim()
    const nickname = String(form.nickname || '').trim()
    const account = String(userInfo.account || '').trim()
    return realName || nickname || account || '未填写资料'
})

const profileMetaText = computed(() => {
    const sex = getSexText(form.sex)
    const mobile = String(userInfo.mobile || '').trim()
    return `${sex} · ${mobile ? '已绑定手机号' : '未绑定手机号'}`
})

const accountText = computed(() => String(userInfo.account || '').trim() || '未设置账号')

const userSnText = computed(() => String(userInfo.sn || userInfo.id || '').trim() || '暂无编号')

const mobileText = computed(() => String(userInfo.mobile || '').trim() || '未绑定手机号')

const mobileAuthDisabled = computed(() => mobileSaving.value || phoneAuthCooling.value)

const createTimeText = computed(() => {
    return String(userInfo.create_time || userInfo.createTime || '').trim() || '暂无记录'
})

const normalizeSex = (sex: any): number => {
    if (sex === 2 || sex === '2' || sex === '女') return 2
    if (sex === 1 || sex === '1' || sex === '男') return 1
    return 0
}

const getSexText = (sex: any): string => {
    if (normalizeSex(sex) === 2) return '女'
    if (normalizeSex(sex) === 1) return '男'
    return '未知'
}

const resetFormByUserInfo = (info: any) => {
    form.avatar = String(info?.avatar || '')
    form.nickname = String(info?.nickname || '')
    form.real_name = String(info?.real_name || '')
    form.sex = normalizeSex(info?.sex)

    originalForm.avatar = form.avatar
    originalForm.nickname = form.nickname
    originalForm.real_name = form.real_name
    originalForm.sex = form.sex
}

const loadPageData = async () => {
    const info = await getUserInfo()

    Object.keys(userInfo).forEach((key) => delete userInfo[key])
    Object.assign(userInfo, info || {})

    resetFormByUserInfo(info)
    selectedSex.value = form.sex
}

const handleBack = () => {
    const pages = getCurrentPages()
    if (pages.length > 1) {
        uni.navigateBack()
        return
    }
    uni.switchTab({ url: '/pages/user/user' })
}

const handleAvatarChange = (value: string) => {
    form.avatar = value
}

const handleAccountClick = () => {
    if (accountSaving.value) return
    showUserName.value = true
    newUsername.value = String(userInfo.account || '')
}

const handleMobileClick = () => {
    if (mobileSaving.value || smsSending.value) return
    showMobilePop.value = true
    newMobile.value = String(userInfo.mobile || '')
    mobileCode.value = ''
}

const startPhoneAuthCooldown = () => {
    phoneAuthCooling.value = true
    if (phoneAuthCooldownTimer) clearTimeout(phoneAuthCooldownTimer)
    phoneAuthCooldownTimer = setTimeout(() => {
        phoneAuthCooling.value = false
        phoneAuthCooldownTimer = null
    }, 1200)
}

const handleSexClick = () => {
    selectedSex.value = form.sex
    showSexPicker.value = true
}

const handleSexConfirm = (value: number) => {
    if (value === undefined || ![0, 1, 2].includes(value)) return
    form.sex = Number(value)
}

const startCodeCountdown = () => {
    let seconds = 60
    canGetCode.value = false
    codeTips.value = `${seconds}秒`

    if (codeTimer) clearInterval(codeTimer)
    codeTimer = setInterval(() => {
        seconds -= 1
        if (seconds > 0) {
            codeTips.value = `${seconds}秒`
            return
        }
        if (codeTimer) {
            clearInterval(codeTimer)
            codeTimer = null
        }
        codeTips.value = '获取验证码'
        canGetCode.value = true
    }, 1000)
}

const getErrorMessage = (error: any, fallback: string) => {
    return typeof error === 'string' ? error : error?.msg || error?.message || fallback
}

const sendSms = async () => {
    if (!newMobile.value) {
        uni.$u.toast('请输入新的手机号码')
        return
    }
    if (!mobileReg.test(String(newMobile.value).trim())) {
        uni.$u.toast('请输入正确的手机号')
        return
    }
    if (!canGetCode.value || smsSending.value) return

    try {
        smsSending.value = true
        await smsSend({
            scene: userInfo.mobile ? SMSEnum.CHANGE_MOBILE : SMSEnum.BIND_MOBILE,
            mobile: String(newMobile.value).trim()
        })
        uni.$u.toast('发送成功')
        startCodeCountdown()
    } catch (error) {
        uni.$u.toast(getErrorMessage(error, '发送失败'))
    } finally {
        smsSending.value = false
    }
}

const changeCodeMobile = async () => {
    if (mobileSaving.value) return
    const mobile = String(newMobile.value || '').trim()
    if (!mobile) {
        uni.$u.toast('请输入新的手机号码')
        return
    }
    if (!mobileReg.test(mobile)) {
        uni.$u.toast('请输入正确的手机号')
        return
    }
    if (!mobileCode.value) {
        uni.$u.toast('请输入验证码')
        return
    }

    try {
        mobileSaving.value = true
        await userBindMobile({
            type: userInfo.mobile ? 'change' : 'bind',
            mobile,
            code: String(mobileCode.value).trim()
        })
        uni.$u.toast('操作成功')
        showMobilePop.value = false
        newMobile.value = ''
        mobileCode.value = ''
        await loadPageData()
        await userStore.getUser()
    } catch (error) {
        uni.$u.toast(getErrorMessage(error, '操作失败'))
    } finally {
        mobileSaving.value = false
    }
}

const changeUserNameConfirm = async () => {
    if (accountSaving.value) return
    const value = String(newUsername.value || '').trim()
    if (!value) {
        uni.$u.toast('账号不能为空')
        return
    }
    if (value.length > 30) {
        uni.$u.toast('账号长度不得超过30位')
        return
    }

    try {
        accountSaving.value = true
        await userEdit({
            field: FieldType.USERNAME,
            value
        })
        uni.$u.toast('操作成功')
        showUserName.value = false
        await loadPageData()
        await userStore.getUser()
    } catch (error) {
        uni.$u.toast(getErrorMessage(error, '操作失败'))
    } finally {
        accountSaving.value = false
    }
}

const getPhoneNumber = async (event: any): Promise<void> => {
    if (mobileSaving.value || phoneAuthCooling.value) return
    startPhoneAuthCooldown()

    const detail = event?.detail || {}
    const code = String(detail.code || '').trim()
    if (!code) {
        const errMsg = String(detail.errMsg || '')
        if (errMsg && !errMsg.includes(':ok')) {
            uni.$u.toast('未授权获取手机号')
        }
        return
    }

    try {
        mobileSaving.value = true
        await userMnpMobile({ code })
        uni.$u.toast('操作成功')
        await loadPageData()
        await userStore.getUser()
    } catch (error) {
        uni.$u.toast(getErrorMessage(error, '操作失败'))
    } finally {
        mobileSaving.value = false
    }
}

const getDirtyFields = () => {
    const payloads: Array<{ field: string; value: string }> = []

    if (form.avatar !== originalForm.avatar) {
        payloads.push({ field: FieldType.AVATAR, value: form.avatar })
    }
    if (form.nickname !== originalForm.nickname) {
        payloads.push({ field: FieldType.NICKNAME, value: form.nickname.trim() })
    }
    if (form.real_name !== originalForm.real_name) {
        payloads.push({ field: FieldType.REAL_NAME, value: form.real_name.trim() })
    }
    if (form.sex !== originalForm.sex) {
        payloads.push({ field: FieldType.SEX, value: String(form.sex) })
    }

    return payloads
}

const validateProfileForm = () => {
    if (form.nickname.trim().length > 32) {
        uni.$u.toast('昵称长度不能超过32位')
        return false
    }
    if (form.real_name.trim().length > 32) {
        uni.$u.toast('姓名或称呼长度不能超过32位')
        return false
    }
    return true
}

const handleSaveProfile = async () => {
    if (saving.value) return
    if (!validateProfileForm()) return

    const payloads = getDirtyFields()
    if (!payloads.length) {
        uni.$u.toast('暂无可保存的修改')
        return
    }

    saving.value = true
    try {
        for (const item of payloads) {
            await userEdit({
                field: item.field,
                value: item.value
            })
        }
        uni.$u.toast('保存成功')
        await loadPageData()
        await userStore.getUser()
    } catch (error) {
        uni.$u.toast(getErrorMessage(error, '保存失败'))
    } finally {
        saving.value = false
    }
}

onShow(async () => {
    $theme.setScene('consumer')
    await loadPageData()
})

onUnload(() => {
    if (codeTimer) {
        clearInterval(codeTimer)
        codeTimer = null
    }
    if (phoneAuthCooldownTimer) {
        clearTimeout(phoneAuthCooldownTimer)
        phoneAuthCooldownTimer = null
    }
})
</script>

<style lang="scss" scoped>
.user-data-page {
    position: relative;
    z-index: 1;
    padding-bottom: var(--wm-safe-bottom-action, calc(150rpx + env(safe-area-inset-bottom)));
}

.page-content {
    padding: 15rpx var(--wm-space-page-x, 37rpx) 0;
}

.profile-card {
    display: flex;
    align-items: center;
    gap: 24rpx;
    border-radius: var(--wm-radius-card, 45rpx);
    border: 1rpx solid var(--wm-color-border-strong, #d8c28a);
    background: linear-gradient(135deg, #ffffff 0%, #f7f0df 100%);
    padding: 30rpx;
    box-shadow: var(--wm-shadow-soft, 0 16rpx 34rpx rgba(17, 17, 17, 0.14));
}

.profile-card__avatar {
    width: 116rpx;
    height: 116rpx;
    border-radius: 999rpx;
    overflow: hidden;
    flex-shrink: 0;
    background: var(--wm-color-primary-soft, #f3f2ee);
}

.profile-card__body {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.profile-card__tag {
    align-self: flex-start;
    display: inline-flex;
    border-radius: 999rpx;
    padding: 8rpx 14rpx;
    background: var(--wm-color-bg-soft, #f3f2ee);
    color: var(--wm-color-primary, #0b0b0b);
    font-size: 20rpx;
    font-weight: 700;
}

.profile-card__name {
    font-size: 38rpx;
    font-weight: 700;
    line-height: 1.25;
    color: var(--wm-text-primary, #111111);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.profile-card__meta {
    font-size: 22rpx;
    line-height: 1.5;
    color: var(--wm-text-secondary, #5f5a50);
}

.section-card {
    margin-top: 14rpx;
    border-radius: var(--wm-radius-card-soft, 37rpx);
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    background: var(--wm-color-bg-card, rgba(255, 255, 255, 0.84));
    padding: 22rpx;
    box-shadow: var(--wm-shadow-soft, 0 14rpx 30rpx rgba(17, 17, 17, 0.1));
}

.section-head {
    display: flex;
    flex-direction: column;
    gap: 6rpx;
    margin-bottom: 16rpx;
}

.section-title {
    display: block;
    font-size: 26rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #111111);
}

.section-desc {
    display: block;
    font-size: 22rpx;
    line-height: 1.45;
    color: var(--wm-text-secondary, #5f5a50);
}

.field-card,
.info-row {
    border-radius: var(--wm-radius-control, 30rpx);
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    background: rgba(248, 247, 242, 0.92);
    padding: 18rpx 20rpx;
    box-sizing: border-box;
}

.field-card + .field-card,
.info-row + .info-row {
    margin-top: 12rpx;
}

.field-card--click,
.info-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18rpx;
}

.field-card__main,
.info-row__main {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.field-label {
    display: block;
    font-size: 22rpx;
    font-weight: 600;
    color: var(--wm-text-secondary, #5f5a50);
}

.field-input {
    width: 100%;
    min-height: 54rpx;
    font-size: 28rpx;
    color: var(--wm-text-primary, #111111);
    line-height: 1.4;
}

.field-placeholder {
    color: var(--wm-text-tertiary, #9a9388);
}

.field-value {
    display: block;
    min-width: 0;
    font-size: 28rpx;
    font-weight: 600;
    line-height: 1.45;
    color: var(--wm-text-primary, #111111);
    word-break: break-word;
}

.inline-action {
    flex-shrink: 0;
    min-width: 96rpx;
    min-height: 56rpx;
    padding: 0 18rpx;
    border-radius: 999rpx;
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    background: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    font-size: 22rpx;
    font-weight: 600;
    line-height: 1;
    color: var(--wm-text-primary, #111111);
}

.inline-action--button {
    margin: 0;
}

.inline-action--button::after {
    border: none;
}

.user-data-page__actions {
    z-index: 30;
}

.edit-popup {
    width: 85vw;
    padding: 36rpx 24rpx 24rpx;
    background: #ffffff;
    border-radius: 28rpx;
}

.popup-title {
    margin-bottom: 32rpx;
    font-size: 36rpx;
    font-weight: 600;
    color: #111111;
    text-align: center;
}

.popup-input-wrapper {
    margin-bottom: 24rpx;
    padding-bottom: 16rpx;
    border-bottom: 2rpx solid #e7e2d6;
}

.code-input-wrapper {
    display: flex;
    align-items: center;
    gap: 16rpx;
}

.popup-input {
    width: 100%;
    height: 72rpx;
    font-size: 28rpx;
    color: #111111;
}

.input-placeholder {
    color: #d8d3c7;
}

.code-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    min-width: 132rpx;
    height: 56rpx;
    padding: 0 16rpx;
    box-sizing: border-box;
    border-radius: 18rpx;
    font-size: 26rpx;
    font-weight: 500;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    color: #0b0b0b;
}

.code-btn-disabled {
    color: #9a9388;
}

.popup-actions {
    margin-top: 32rpx;
}

.popup-btn {
    width: 100%;
    height: 72rpx;
    border: none;
    border-radius: 20rpx;
    font-size: 30rpx;
    font-weight: 600;
}

.popup-btn::after {
    border: none;
}

.popup-btn-primary {
    background: #0b0b0b;
    color: #ffffff;
}
</style>
