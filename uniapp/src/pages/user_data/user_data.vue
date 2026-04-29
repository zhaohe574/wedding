<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer">
        <BaseNavbar title="资料编辑" @back="handleBack" />

        <view class="user-data-page">
            <view class="page-content wm-page-content">
                <view class="sync-card wm-panel-card">
                    <view class="sync-tag">资料同步</view>
                    <view class="sync-profile">
                        <view class="sync-avatar">
                            <avatar-upload
                                :modelValue="form.avatar"
                                file-key="url"
                                :round="true"
                                @update:modelValue="handleAvatarChange"
                            />
                        </view>
                        <view class="sync-profile__main">
                            <text class="sync-name">{{ displayName }}</text>
                            <text class="sync-subtitle">{{ weddingMainDateText }}</text>
                        </view>
                    </view>
                    <text class="sync-tip">保存后会同步资料。</text>
                </view>

                <view class="section-card wm-form-block">
                    <text class="section-title">基本信息</text>
                    <view class="field-card wm-soft-card">
                        <text class="field-label">新人称呼</text>
                        <input
                            v-model="form.real_name"
                            class="field-input"
                            placeholder="请输入新人称呼"
                            placeholder-class="field-placeholder"
                        />
                    </view>

                    <view class="field-card wm-soft-card">
                        <text class="field-label">联系方式</text>
                        <view class="contact-value">{{ contactText }}</view>
                        <view class="contact-actions">
                            <view class="contact-action" @click="handleAccountClick">修改账号</view>
                            <!-- #ifdef MP-WEIXIN -->
                            <button
                                class="contact-action contact-action--button"
                                open-type="getPhoneNumber"
                                @getphonenumber="getPhoneNumber"
                            >
                                {{ userInfo.mobile ? '更换手机号' : '绑定手机号' }}
                            </button>
                            <!-- #endif -->
                            <!-- #ifndef MP-WEIXIN -->
                            <view class="contact-action" @click="handleMobileClick">
                                {{ userInfo.mobile ? '更换手机号' : '绑定手机号' }}
                            </view>
                            <!-- #endif -->
                        </view>
                    </view>

                    <view class="field-card field-card--click wm-soft-card" @click="handleSexClick">
                        <text class="field-label">性别</text>
                        <view class="field-click-value">
                            <text>{{ getSexText(form.sex) }}</text>
                            <tn-icon name="right" size="26" color="#9A9388" />
                        </view>
                    </view>
                </view>

                <view class="section-card wm-form-block">
                    <text class="section-title">婚礼信息</text>
                    <view class="field-card wm-soft-card">
                        <text class="field-label">婚礼日期</text>
                        <text class="field-readonly">{{ weddingDateText }}</text>
                    </view>
                    <view class="field-card wm-soft-card">
                        <text class="field-label">婚礼城市 / 场地</text>
                        <text class="field-readonly field-readonly--wrap">{{
                            weddingVenueText
                        }}</text>
                    </view>
                </view>
            </view>

            <ActionArea class="user-data-page__actions" sticky safeBottom layout="split">
                <view class="user-data-page__action">
                    <BaseButton block size="lg" variant="secondary" @click="handleCancelEdit">
                        取消编辑
                    </BaseButton>
                </view>
                <view class="user-data-page__action">
                    <BaseButton block size="lg" :loading="saving" @click="handleSaveProfile">
                        保存资料
                    </BaseButton>
                </view>
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
                            class="popup-input"
                            v-model="newUsername"
                            placeholder="请输入账号"
                            placeholder-class="input-placeholder"
                        />
                    </view>
                    <view class="popup-actions">
                        <button
                            class="popup-btn popup-btn-primary"
                            :disabled="accountSaving"
                            @click="changeUserNameConfirm"
                            hover-class="none"
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
                            class="popup-input"
                            v-model="newMobile"
                            type="number"
                            placeholder="请输入新的手机号码"
                            placeholder-class="input-placeholder"
                        />
                    </view>
                    <view class="popup-input-wrapper code-input-wrapper">
                        <input
                            class="flex-1 popup-input"
                            v-model="mobileCode"
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
                            @click="changeCodeMobile"
                            hover-class="none"
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
import {
    getUserWeddingDate,
    getUserInfo,
    userBindMobile,
    userEdit,
    userMnpMobile
} from '@/api/user'
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
const mobileReg = /^1\d{10}$/

const userInfo = reactive<any>({})
const weddingInfo = reactive<any>({})
const form = reactive({
    avatar: '',
    real_name: '',
    sex: 0
})
const originalForm = reactive({
    avatar: '',
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
    return realName || '未填写称呼'
})

const weddingMainDateText = computed(() => {
    const date = String(weddingInfo.wedding_date || '').trim()
    return date ? `婚期：${date}` : '婚期待补充'
})

const weddingDateText = computed(() => {
    const date = String(weddingInfo.wedding_date || '').trim()
    return date || '未填写婚期'
})

const weddingVenueText = computed(() => {
    const venue = String(weddingInfo.wedding_venue || '').trim()
    return venue || '待补充场地'
})

const contactText = computed(() => {
    const mobile = String(userInfo.mobile || '').trim() || '未绑定手机号'
    const account = String(userInfo.account || '').trim() || '未设置账号'
    return `${mobile} / ${account}`
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
    form.real_name = String(info?.real_name || '')
    form.sex = normalizeSex(info?.sex)

    originalForm.avatar = form.avatar
    originalForm.real_name = form.real_name
    originalForm.sex = form.sex
}

const loadPageData = async () => {
    const [info, wedding] = await Promise.all([
        getUserInfo(),
        getUserWeddingDate().catch(() => ({}))
    ])

    Object.keys(userInfo).forEach((key) => delete userInfo[key])
    Object.assign(userInfo, info || {})
    Object.keys(weddingInfo).forEach((key) => delete weddingInfo[key])
    Object.assign(weddingInfo, wedding || {})

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
    if (value.length > 10) {
        uni.$u.toast('账号长度不得超过十位数')
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

const getPhoneNumber = async (e: any): Promise<void> => {
    const { encryptedData, iv, code } = e.detail || {}
    if (!encryptedData) return

    try {
        mobileSaving.value = true
        await userMnpMobile({
            code,
            encrypted_data: encryptedData,
            iv
        })
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
    if (form.real_name !== originalForm.real_name) {
        payloads.push({ field: FieldType.REAL_NAME, value: form.real_name.trim() })
    }
    if (form.sex !== originalForm.sex) {
        payloads.push({ field: FieldType.SEX, value: String(form.sex) })
    }

    return payloads
}

const validateProfileForm = () => {
    if (!form.real_name.trim()) {
        uni.$u.toast('请填写新人称呼')
        return false
    }
    if (form.real_name.trim().length > 32) {
        uni.$u.toast('新人称呼长度不能超过32位')
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

const handleCancelEdit = () => {
    if (saving.value) return
    form.avatar = originalForm.avatar
    form.real_name = originalForm.real_name
    form.sex = originalForm.sex
    selectedSex.value = form.sex
    uni.$u.toast('已取消编辑')
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
})
</script>

<style lang="scss" scoped>
.user-data-page {
    position: relative;
    z-index: 1;
    padding-bottom: var(--wm-safe-bottom-action, calc(150rpx + env(safe-area-inset-bottom)));
}

.page-content {
    padding: 15rpx 37rpx 0;
}

.sync-card {
    border-radius: var(--wm-radius-card, 45rpx);
    border: 1rpx solid var(--wm-color-border-strong, #d8c28a);
    background: linear-gradient(135deg, #ffffff 0%, #f7f0df 100%);
    padding: var(--wm-space-card-padding, 30rpx);
    box-shadow: var(--wm-shadow-soft, 0 16rpx 34rpx rgba(17, 17, 17, 0.14));
}

.sync-tag {
    align-self: flex-start;
    display: inline-flex;
    border-radius: 999rpx;
    padding: 8rpx 14rpx;
    background: var(--wm-color-bg-soft, #f3f2ee);
    color: var(--wm-color-primary, #0b0b0b);
    font-size: 20rpx;
    font-weight: 700;
}

.sync-profile {
    margin-top: 12rpx;
    display: flex;
    align-items: center;
    gap: 16rpx;
}

.sync-avatar {
    width: 88rpx;
    height: 88rpx;
    border-radius: 999rpx;
    overflow: hidden;
    flex-shrink: 0;
    background: var(--wm-color-primary-soft, #f3f2ee);
}

.sync-profile__main {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 6rpx;
}

.sync-name {
    font-size: 36rpx;
    font-weight: 700;
    line-height: 1.2;
    color: var(--wm-text-primary, #111111);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sync-subtitle {
    font-size: 22rpx;
    line-height: 1.5;
    color: var(--wm-text-secondary, #5f5a50);
}

.sync-tip {
    display: block;
    margin-top: 10rpx;
    font-size: 22rpx;
    line-height: 1.55;
    color: var(--wm-text-secondary, #5f5a50);
}

.section-card {
    margin-top: 12rpx;
    border-radius: var(--wm-radius-card-soft, 37rpx);
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    background: var(--wm-color-bg-card, rgba(255, 255, 255, 0.84));
    padding: 20rpx;
    box-shadow: var(--wm-shadow-soft, 0 14rpx 30rpx rgba(17, 17, 17, 0.1));
}

.section-title {
    display: block;
    font-size: 24rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #111111);
    margin-bottom: 12rpx;
}

.field-card {
    border-radius: var(--wm-radius-control, 34rpx);
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    background: rgba(248, 247, 242, 0.92);
    padding: 16rpx;
}

.field-card + .field-card {
    margin-top: 12rpx;
}

.field-card--click {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.field-label {
    display: block;
    font-size: 20rpx;
    font-weight: 600;
    color: var(--wm-text-secondary, #5f5a50);
    margin-bottom: 8rpx;
}

.field-input {
    width: 100%;
    min-height: 48rpx;
    font-size: 26rpx;
    color: var(--wm-text-primary, #111111);
    line-height: 1.4;
}

.field-placeholder {
    color: var(--wm-text-tertiary, #9A9388);
}

.field-readonly {
    display: block;
    font-size: 26rpx;
    font-weight: 600;
    line-height: 1.4;
    color: var(--wm-text-primary, #111111);
}

.field-readonly--wrap {
    white-space: normal;
    word-break: break-word;
}

.field-click-value {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    font-size: 26rpx;
    font-weight: 600;
    color: var(--wm-text-primary, #111111);
}

.contact-value {
    font-size: 24rpx;
    line-height: 1.45;
    color: var(--wm-text-primary, #111111);
}

.contact-actions {
    margin-top: 10rpx;
    display: flex;
    gap: 12rpx;
    flex-wrap: wrap;
}

.contact-action {
    padding: 8rpx 16rpx;
    border-radius: 999rpx;
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    background: #fff;
    font-size: 20rpx;
    color: var(--wm-text-primary, #111111);
}

.contact-action--button {
    line-height: 1.2;
    margin: 0;
}

.contact-action--button::after {
    border: none;
}

.user-data-page__actions {
    z-index: 30;
}

.user-data-page__action {
    flex: 1;
    min-width: 0;
}

.edit-popup {
    width: 85vw;
    padding: 36rpx 24rpx 24rpx;
    background: #ffffff;
    border-radius: 28rpx;
}

.popup-title {
    font-size: 36rpx;
    font-weight: 600;
    color: #111111;
    text-align: center;
    margin-bottom: 32rpx;
}

.popup-input-wrapper {
    padding-bottom: 16rpx;
    border-bottom: 2rpx solid #E7E2D6;
    margin-bottom: 24rpx;
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
    padding: 10rpx 20rpx;
    font-size: 26rpx;
    font-weight: 500;
    white-space: nowrap;
    border-radius: 18rpx;
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
    border-radius: 20rpx;
    font-size: 30rpx;
    font-weight: 600;
    border: none;
}

.popup-btn::after {
    border: none;
}

.popup-btn-primary {
    background: #0b0b0b;
    color: #ffffff;
}
</style>
