<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar
            title="个人资料"
            variant="solid"
            bg-color="#191713"
            text-color="#FFFDF8"
            title-align="center"
            @back="handleBack"
        />

        <view class="user-data-page">
            <view class="page-content wm-page-content">
                <BaseCard
                    class="profile-card"
                    variant="hero"
                    padding="32rpx"
                    border-radius="36rpx"
                >
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
                        <view class="profile-card__badges">
                            <StatusBadge label="个人资料" tone="primary" size="sm" />
                            <StatusBadge
                                :label="mobileStatusText"
                                :tone="mobileBadgeTone"
                                size="sm"
                            />
                        </view>
                        <text class="profile-card__name">{{ displayName }}</text>
                        <text class="profile-card__meta">{{ profileMetaText }}</text>
                    </view>
                </BaseCard>

                <BaseCard
                    class="section-card"
                    variant="list"
                    padding="26rpx 28rpx"
                    border-radius="32rpx"
                >
                    <view class="section-head">
                        <text class="section-title">账号与安全</text>
                    </view>

                    <view class="section-list">
                        <view class="section-list__item section-list__item--action">
                            <BaseInfoRow label="账号" :value="accountText" />
                            <view class="inline-action" @click="handleAccountClick">修改</view>
                        </view>

                        <view class="section-list__item">
                            <BaseInfoRow label="用户编号" :value="userSnText" />
                        </view>

                        <view class="section-list__item section-list__item--action">
                            <BaseInfoRow label="手机号" :value="mobileText" />
                            <!-- #ifdef MP-WEIXIN -->
                            <button
                                class="inline-action inline-action--button"
                                open-type="getPhoneNumber"
                                hover-class="none"
                                :disabled="mobileAuthDisabled"
                                @getphonenumber="getPhoneNumber"
                            >
                                {{ mobileActionText }}
                            </button>
                            <!-- #endif -->
                            <!-- #ifndef MP-WEIXIN -->
                            <view class="inline-action" @click="handleMobileClick">
                                {{ mobileActionText }}
                            </view>
                            <!-- #endif -->
                        </view>

                        <view class="section-list__item">
                            <BaseInfoRow label="注册时间" :value="createTimeText" />
                        </view>
                    </view>
                </BaseCard>

                <BaseCard
                    class="section-card"
                    variant="list"
                    padding="26rpx 28rpx"
                    border-radius="32rpx"
                >
                    <view class="section-head">
                        <text class="section-title">基础资料</text>
                    </view>

                    <view class="profile-form">
                        <BaseInput
                            v-model="form.nickname"
                            label="展示称呼"
                            placeholder="请输入展示称呼"
                            maxlength="32"
                            clearable
                        >
                            <template #prefix>
                                <BaseIcon name="user" size="30" color="#9A9388" />
                            </template>
                        </BaseInput>

                        <view class="picker-row" @click="handleSexClick">
                            <BaseInfoRow label="性别" :value="getSexText(form.sex)" />
                            <BaseIcon name="right" size="26" color="#9A9388" />
                        </view>
                    </view>
                </BaseCard>
            </view>

            <ActionArea class="user-data-page__actions" sticky safeBottom>
                <BaseButton
                    block
                    variant="dark"
                    size="lg"
                    :loading="saving"
                    loading-text="保存中"
                    label="保存资料"
                    @click="handleSaveProfile"
                />
            </ActionArea>

            <BaseOverlayMask
                :show="showUserName"
                background="rgba(25, 23, 19, 0.42)"
                @close="showUserName = false"
            />
            <tn-popup
                v-model="showUserName"
                :close-btn="false"
                open-direction="bottom"
                :radius="0"
                :overlay="false"
                :overlay-closeable="true"
            >
                <view class="account-sheet">
                    <view class="account-sheet__grabber"></view>
                    <view class="account-sheet__head">
                        <view class="account-sheet__title-group">
                            <text class="account-sheet__title">修改账号</text>
                            <text class="account-sheet__current">{{ currentAccountHint }}</text>
                        </view>
                        <view class="account-sheet__close" @click="showUserName = false">
                            <BaseIcon name="close" size="28" color="#665E52" />
                        </view>
                    </view>

                    <view class="account-sheet__form">
                        <text class="account-sheet__label">新账号</text>
                        <BaseInput
                            class="account-sheet__input"
                            v-model="newUsername"
                            placeholder="请输入新的账号"
                            maxlength="30"
                            clearable
                        />
                    </view>

                    <view class="account-sheet__actions">
                        <BaseButton
                            class="account-sheet__button"
                            block
                            variant="light"
                            size="md"
                            height="88rpx"
                            label="取消"
                            @click="showUserName = false"
                        />
                        <BaseButton
                            class="account-sheet__button"
                            block
                            variant="dark"
                            size="md"
                            height="88rpx"
                            :loading="accountSaving"
                            loading-text="保存中"
                            label="保存账号"
                            @click="changeUserNameConfirm"
                        />
                    </view>
                </view>
            </tn-popup>

            <BaseOverlayMask
                :show="showMobilePop"
                background="rgba(25, 23, 19, 0.52)"
                @close="showMobilePop = false"
            />
            <tn-popup
                v-model="showMobilePop"
                :close-btn="true"
                open-direction="center"
                :radius="36"
                :overlay="false"
                :overlay-closeable="true"
            >
                <BaseCard
                    class="edit-popup"
                    variant="panel"
                    padding="40rpx 36rpx 34rpx"
                    border-radius="38rpx"
                    background="#FFFDF8"
                    border="1rpx solid #D8C9AD"
                    box-shadow="0 22rpx 52rpx rgba(74, 43, 24, 0.18)"
                >
                    <view class="popup-head">
                        <text class="popup-title">
                            {{ userInfo.mobile ? '更换手机号' : '绑定手机号' }}
                        </text>
                    </view>
                    <view class="popup-form">
                        <BaseInput
                            v-model="newMobile"
                            type="tel"
                            placeholder="请输入新的手机号码"
                            maxlength="11"
                            clearable
                        />
                        <BaseInput
                            v-model="mobileCode"
                            type="number"
                            placeholder="请输入验证码"
                            clearable
                        >
                            <template #suffix>
                                <text
                                    class="code-btn"
                                    :class="{ 'code-btn--disabled': !canGetCode || smsSending }"
                                    @click="sendSms"
                                >
                                    {{ codeTips }}
                                </text>
                            </template>
                        </BaseInput>
                    </view>
                    <BaseButton
                        class="popup-submit"
                        block
                        variant="dark"
                        size="md"
                        :loading="mobileSaving"
                        loading-text="保存中"
                        label="确定"
                        @click="changeCodeMobile"
                    />
                </BaseCard>
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
import BaseCard from '@/components/base/BaseCard.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseInfoRow from '@/components/base/BaseInfoRow.vue'
import BaseInput from '@/components/base/BaseInput.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import BaseOverlayMask from '@/components/base/BaseOverlayMask.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
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
    const nickname = String(form.nickname || '').trim()
    const realName = String(form.real_name || '').trim()
    const account = String(userInfo.account || '').trim()
    return nickname || realName || account || '未填写资料'
})

const profileMetaText = computed(() => {
    const sex = getSexText(form.sex)
    return `${sex} · ${userSnText.value}`
})

const mobileStatusText = computed(() => (String(userInfo.mobile || '').trim() ? '已绑定' : '未绑定'))

const mobileBadgeTone = computed<'success' | 'warning'>(() =>
    String(userInfo.mobile || '').trim() ? 'success' : 'warning'
)

const accountText = computed(() => String(userInfo.account || '').trim() || '未设置账号')

const currentAccountHint = computed(() => {
    const account = String(userInfo.account || '').trim()
    return account ? `当前账号：${account}` : '当前未设置账号'
})

const userSnText = computed(() => String(userInfo.sn || userInfo.id || '').trim() || '暂无编号')

const mobileText = computed(() => String(userInfo.mobile || '').trim() || '未绑定手机号')

const mobileAuthDisabled = computed(() => mobileSaving.value || phoneAuthCooling.value)

const mobileActionText = computed(() => {
    if (mobileSaving.value) return '处理中'
    return String(userInfo.mobile || '').trim() ? '更换' : '绑定'
})

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
    min-height: 100%;
    padding-bottom: var(--wm-safe-bottom-action, calc(166rpx + env(safe-area-inset-bottom)));
    background:
        radial-gradient(circle at 84% 0%, rgba(217, 190, 130, 0.16) 0, transparent 320rpx),
        linear-gradient(180deg, rgba(25, 23, 19, 0.05) 0, transparent 160rpx),
        var(--wm-color-bg-page, #fbfaf7);
}

.page-content {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
    padding: 24rpx var(--wm-space-page-x, 32rpx) 0;
    box-sizing: border-box;
}

.profile-card {
    display: flex;
    align-items: center;
    gap: 26rpx;
    min-height: 168rpx;
}

.profile-card__avatar {
    position: relative;
    z-index: 1;
    width: 118rpx;
    height: 118rpx;
    border-radius: 999rpx;
    overflow: hidden;
    flex-shrink: 0;
    background: rgba(255, 253, 248, 0.96);
    border: 2rpx solid rgba(217, 190, 130, 0.82);
    box-shadow: 0 14rpx 30rpx rgba(0, 0, 0, 0.18);
}

.profile-card__body {
    position: relative;
    z-index: 1;
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 10rpx;
}

.profile-card__badges {
    align-self: flex-start;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10rpx;
}

.profile-card__name {
    display: block;
    font-size: 40rpx;
    font-weight: 900;
    line-height: 1.25;
    color: var(--wm-text-inverse, #fffdf8);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.profile-card__meta {
    display: block;
    font-size: 23rpx;
    font-weight: 700;
    line-height: 1.45;
    color: rgba(255, 253, 248, 0.7);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.section-card {
    overflow: visible;
}

.section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14rpx;
}

.section-title {
    display: block;
    font-size: 30rpx;
    font-weight: 900;
    line-height: 1.25;
    color: var(--wm-text-primary, #191713);
}

.section-list {
    display: flex;
    flex-direction: column;
}

.section-list__item {
    display: flex;
    align-items: center;
    gap: 16rpx;
    min-height: 88rpx;
    border-bottom: 1rpx solid rgba(216, 201, 173, 0.62);
}

.section-list__item:last-child {
    border-bottom: none;
}

.section-list__item :deep(.base-info-row) {
    width: 100%;
    min-width: 0;
}

.section-list__item--action :deep(.base-info-row) {
    flex: 1;
    min-width: 0;
}

.inline-action {
    flex-shrink: 0;
    min-width: 92rpx;
    min-height: 56rpx;
    padding: 0 20rpx;
    border-radius: 999rpx;
    border: 1rpx solid var(--wm-color-champagne, #d9be82);
    background: var(--wm-color-gold-soft, #f1e5c8);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    font-size: 23rpx;
    font-weight: 900;
    line-height: 1;
    color: var(--wm-color-primary, #191713);
}

.inline-action--button {
    margin: 0;
    height: 56rpx;
}

.inline-action--button::after {
    border: none;
}

.profile-form {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
}

.picker-row {
    min-height: 96rpx;
    display: flex;
    align-items: center;
    gap: 14rpx;
    padding: 0 26rpx;
    border: 1rpx solid var(--wm-color-border, #d8c9ad);
    border-radius: var(--wm-radius-input, 44rpx);
    background: var(--wm-color-bg-card, #fffdf8);
    box-shadow: var(--wm-shadow-soft, 0 16rpx 36rpx rgba(74, 43, 24, 0.07));
    box-sizing: border-box;
}

.picker-row :deep(.base-info-row) {
    flex: 1;
    min-width: 0;
}

.user-data-page__actions {
    z-index: 30;
    --wm-space-action-x: 32rpx;
}

.account-sheet {
    width: 100vw;
    padding: 20rpx 34rpx calc(34rpx + env(safe-area-inset-bottom));
    border-radius: 40rpx 40rpx 0 0;
    background:
        radial-gradient(circle at 88% 0%, rgba(217, 190, 130, 0.2) 0, transparent 260rpx),
        #fffdf8;
    border: 1rpx solid rgba(216, 201, 173, 0.92);
    border-bottom: none;
    box-shadow: 0 -24rpx 58rpx rgba(74, 43, 24, 0.18);
    box-sizing: border-box;
}

.account-sheet__grabber {
    width: 76rpx;
    height: 8rpx;
    margin: 0 auto 26rpx;
    border-radius: 999rpx;
    background: rgba(102, 94, 82, 0.24);
}

.account-sheet__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 24rpx;
}

.account-sheet__title-group {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 10rpx;
}

.account-sheet__title {
    display: block;
    font-size: 36rpx;
    font-weight: 900;
    line-height: 1.22;
    color: var(--wm-text-primary, #191713);
}

.account-sheet__current {
    display: block;
    max-width: 100%;
    font-size: 23rpx;
    font-weight: 800;
    line-height: 1.35;
    color: var(--wm-text-secondary, #665e52);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.account-sheet__close {
    flex-shrink: 0;
    width: 64rpx;
    height: 64rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999rpx;
    background: rgba(250, 246, 238, 0.92);
    border: 1rpx solid rgba(216, 201, 173, 0.82);
    box-shadow: 0 10rpx 24rpx rgba(74, 43, 24, 0.08);
}

.account-sheet__form {
    margin-top: 30rpx;
    padding: 24rpx;
    border-radius: 32rpx;
    background: linear-gradient(180deg, rgba(241, 229, 200, 0.58) 0%, #fffdf8 100%);
    border: 1rpx solid rgba(217, 190, 130, 0.76);
    box-shadow: 0 14rpx 32rpx rgba(74, 43, 24, 0.08);
}

.account-sheet__label {
    display: block;
    margin-bottom: 16rpx;
    font-size: 24rpx;
    font-weight: 900;
    color: var(--wm-color-gold, #b8954a);
}

.account-sheet__input :deep(.base-input__control) {
    min-height: 100rpx;
    border-radius: 30rpx;
    background: #fffdf8;
}

.account-sheet__input :deep(.base-input__native) {
    height: 92rpx;
    line-height: 92rpx;
    font-size: 30rpx;
}

.account-sheet__actions {
    display: grid;
    grid-template-columns: minmax(0, 0.82fr) minmax(0, 1.18fr);
    gap: 18rpx;
    margin-top: 28rpx;
}

.account-sheet__button {
    width: 100%;
}

.edit-popup {
    width: 660rpx;
    max-width: calc(100vw - 56rpx);
    box-sizing: border-box;
}

.popup-head {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24rpx;
    margin-bottom: 28rpx;
}

.popup-title {
    display: block;
    min-width: 0;
    flex: 1;
    font-size: 34rpx;
    font-weight: 900;
    line-height: 1.25;
    color: var(--wm-text-primary, #191713);
}

.popup-close {
    flex-shrink: 0;
    width: 56rpx;
    height: 56rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999rpx;
    background: rgba(250, 246, 238, 0.9);
    border: 1rpx solid rgba(216, 201, 173, 0.72);
}

.popup-form {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
}

.account-popup__body {
    position: relative;
    z-index: 1;
}

.account-popup__body :deep(.base-input__control) {
    min-height: 104rpx;
    border-radius: 34rpx;
    background: #fffdf8;
}

.account-popup__body :deep(.base-input__native) {
    height: 96rpx;
    line-height: 96rpx;
    font-size: 30rpx;
}

.code-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    min-width: 124rpx;
    height: 50rpx;
    padding: 0 16rpx;
    box-sizing: border-box;
    border-radius: 999rpx;
    border: 1rpx solid var(--wm-color-champagne, #d9be82);
    background: var(--wm-color-gold-soft, #f1e5c8);
    font-size: 23rpx;
    font-weight: 900;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    color: var(--wm-color-primary, #191713);
}

.code-btn--disabled {
    border-color: var(--wm-color-border, #d8c9ad);
    background: var(--wm-color-bg-soft, #faf6ee);
    color: var(--wm-text-tertiary, #8a806f);
}

.popup-submit {
    position: relative;
    z-index: 1;
    margin-top: 30rpx;
}

.edit-popup :deep(.base-button--md) {
    height: 84rpx !important;
    min-height: 84rpx !important;
}

@media (max-width: 360px) {
    .page-content {
        padding-left: 24rpx;
        padding-right: 24rpx;
        gap: 18rpx;
    }

    .profile-card {
        gap: 20rpx;
    }

    .profile-card__avatar {
        width: 104rpx;
        height: 104rpx;
    }

    .profile-card__name {
        font-size: 36rpx;
    }

    .inline-action {
        min-width: 84rpx;
        padding: 0 16rpx;
    }
}
</style>
