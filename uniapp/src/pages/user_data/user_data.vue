<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar 
            title="个人资料"
            :front-color="$theme.navColor" 
            :background-color="$theme.navBgColor" 
        />
        <!-- #endif -->
    </page-meta>
    
    <view class="user-data-page">
        <!-- 头像区域 -->
        <view class="avatar-section">
            <view class="avatar-card">
                <avatar-upload
                    :modelValue="userInfo?.avatar"
                    file-key="url"
                    :round="true"
                    @update:modelValue="handleAvatarChange"
                />
                <text class="avatar-tip">点击修改头像</text>
            </view>
        </view>

        <!-- 信息列表 -->
        <view class="info-list">
            <!-- 账号 -->
            <view class="info-item" @click="handleAccountClick">
                <view class="info-label">账号</view>
                <view class="info-content">
                    <text class="info-value">{{ userInfo?.account }}</text>
                    <tn-icon name="right" size="32" color="#CBD5E1" />
                </view>
            </view>

            <!-- 昵称 -->
            <view class="info-item" @click="handleNicknameClick">
                <view class="info-label">昵称</view>
                <view class="info-content">
                    <text class="info-value">{{ userInfo?.nickname }}</text>
                    <tn-icon name="right" size="32" color="#CBD5E1" />
                </view>
            </view>

            <!-- 性别 -->
            <view class="info-item" @click="handleSexClick">
                <view class="info-label">性别</view>
                <view class="info-content">
                    <text class="info-value">{{ getSexText(userInfo?.sex) }}</text>
                    <tn-icon name="right" size="32" color="#CBD5E1" />
                </view>
            </view>

            <!-- 手机号 -->
            <view class="info-item phone-item">
                <view class="info-label">手机号</view>
                <view class="info-content">
                    <text class="info-value" :class="{ 'text-muted': !userInfo?.mobile }">
                        {{ userInfo?.mobile || '未绑定手机号' }}
                    </text>
                    <!-- #ifdef MP-WEIXIN -->
                    <view 
                        class="bind-btn"
                        :style="{ 
                            backgroundColor: $theme.primaryColor + '15',
                            color: $theme.primaryColor 
                        }"
                    >
                        <button 
                            class="bind-btn-inner"
                            open-type="getPhoneNumber"
                            @getphonenumber="getPhoneNumber"
                        >
                            {{ userInfo?.mobile ? '更换' : '绑定' }}
                        </button>
                    </view>
                    <!-- #endif -->
                    <!-- #ifndef MP-WEIXIN -->
                    <view 
                        class="bind-btn"
                        :style="{ 
                            backgroundColor: $theme.primaryColor + '15',
                            color: $theme.primaryColor 
                        }"
                        @click="showMobilePop = true"
                    >
                        {{ userInfo?.mobile ? '更换' : '绑定' }}
                    </view>
                    <!-- #endif -->
                </view>
            </view>

            <!-- 注册时间 -->
            <view class="info-item">
                <view class="info-label">注册时间</view>
                <view class="info-content">
                    <text class="info-value text-muted">{{ userInfo?.create_time }}</text>
                </view>
            </view>
        </view>

        <!-- 昵称修改弹窗 -->
        <tn-popup
            v-model="showNickName"
            :close-btn="true"
            mode="center"
            :mask-close-able="false"
            border-radius="24"
        >
            <view class="edit-popup">
                <form @submit="changeNameConfirm">
                    <view class="popup-title">修改昵称</view>
                    <view class="popup-input-wrapper">
                        <input
                            class="popup-input"
                            :value="userInfo.nickname"
                            name="nickname"
                            type="nickname"
                            placeholder="请输入昵称"
                            placeholder-class="input-placeholder"
                        />
                    </view>
                    <view class="popup-actions">
                        <button
                            class="popup-btn popup-btn-primary"
                            :style="{ 
                                background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
                                color: $theme.btnColor
                            }"
                            form-type="submit"
                            hover-class="none"
                        >
                            确定
                        </button>
                    </view>
                </form>
            </view>
        </tn-popup>

        <!-- 账号修改弹窗 -->
        <tn-popup 
            v-model="showUserName" 
            :close-btn="true" 
            mode="center" 
            border-radius="24"
            :mask-close-able="false"
        >
            <view class="edit-popup">
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
                        :style="{ 
                            background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
                            color: $theme.btnColor
                        }"
                        @click="changeUserNameConfirm"
                        hover-class="none"
                    >
                        确定
                    </button>
                </view>
            </view>
        </tn-popup>

        <!-- 手机号修改弹窗 -->
        <tn-popup 
            v-model="showMobilePop" 
            :close-btn="true" 
            mode="center" 
            border-radius="24"
            :mask-close-able="false"
        >
            <view class="edit-popup">
                <view class="popup-title">
                    {{ userInfo?.mobile ? '更换手机号' : '绑定手机号' }}
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
                        :class="{ 'code-btn-disabled': !canGetCode }"
                        :style="canGetCode ? { color: $theme.primaryColor } : {}"
                        @click="sendSms"
                    >
                        {{ codeTips }}
                    </view>
                </view>
                <view class="popup-actions">
                    <button
                        class="popup-btn popup-btn-primary"
                        :style="{ 
                            background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
                            color: $theme.btnColor
                        }"
                        @click="changeCodeMobile"
                        hover-class="none"
                    >
                        确定
                    </button>
                </view>
            </view>
        </tn-popup>

        <!-- 性别选择器 -->
        <tn-picker
            v-model="selectedSex"
            v-model:open="showSexPicker"
            :data="sexPickerData"
            @confirm="handleSexConfirm"
        />
    </view>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { onShow, onUnload } from '@dcloudio/uni-app'
import { useThemeStore } from '@/stores/theme'
import { getUserInfo, userEdit, userBindMobile, userMnpMobile } from '@/api/user'
import { smsSend } from '@/api/app'
import { FieldType, SMSEnum } from '@/enums/appEnums'

const $theme = useThemeStore()

// 用户信息
const userInfo = ref<any>({})
// 用户信息的枚举
const fieldType = ref(FieldType.NONE)

// 显示昵称弹窗
const showNickName = ref<boolean>(false)
// 显示账户弹窗
const showUserName = ref<boolean>(false)
// 显示手机号验证码调整弹窗
const showMobilePop = ref<boolean>(false)

// 性别选择器相关
const showSexPicker = ref<boolean>(false)
const selectedSex = ref<number | undefined>(undefined)
const sexPickerData = [
    { label: '未知', value: 0 },
    { label: '男', value: 1 },
    { label: '女', value: 2 }
]

// 新昵称
const newNickname = ref<string>('')
// 新账号
const newUsername = ref<string>('')
// 新的手机号码
const newMobile = ref<string>('')

// 修改手机验证码
const mobileCode = ref<string>('')
const codeTips = ref('获取验证码')
const canGetCode = ref(true)
let codeTimer: any = null

// 验证码倒计时
const startCodeCountdown = () => {
    let seconds = 60
    canGetCode.value = false
    codeTips.value = `${seconds}秒`
    
    codeTimer = setInterval(() => {
        seconds--
        if (seconds > 0) {
            codeTips.value = `${seconds}秒`
        } else {
            clearInterval(codeTimer)
            codeTips.value = '获取验证码'
            canGetCode.value = true
        }
    }, 1000)
}

const getSexText = (sex: any): string => {
    if (sex === 2 || sex === '2' || sex === '女') return '女'
    if (sex === 1 || sex === '1' || sex === '男') return '男'
    return '未知'
}

// 获取用户信息
const getUser = async (): Promise<void> => {
    userInfo.value = await getUserInfo()
}

// 发送验证码
const sendSms = async () => {
    if (!newMobile.value) return uni.$u.toast('请输入新的手机号码')
    if (!canGetCode.value) return
    
    await smsSend({
        scene: userInfo.value.mobile ? SMSEnum.CHANGE_MOBILE : SMSEnum.BIND_MOBILE,
        mobile: newMobile.value
    })
    uni.$u.toast('发送成功')
    startCodeCountdown()
}

const handleAvatarChange = (value: string) => {
    fieldType.value = FieldType.AVATAR
    setUserInfoFun(value)
}

// 验证码修改手机号
const changeCodeMobile = async () => {
    await userBindMobile({
        type: userInfo.value.mobile ? 'change' : 'bind',
        mobile: newMobile.value,
        code: mobileCode.value
    })
    uni.$u.toast('操作成功')
    showMobilePop.value = false
    getUser()
}

// 修改用户信息
const setUserInfoFun = async (value: string): Promise<void> => {
    await userEdit({
        field: fieldType.value,
        value: value
    })
    uni.$u.toast('操作成功')
    getUser()
}

// 点击账号
const handleAccountClick = () => {
    showUserName.value = true
    newUsername.value = userInfo.value?.account || ''
}

// 点击昵称
const handleNicknameClick = () => {
    showNickName.value = true
    newNickname.value = userInfo.value?.nickname || ''
}

// 点击性别
const handleSexClick = () => {
    fieldType.value = FieldType.SEX
    // 设置当前性别值
    const currentSex = userInfo.value?.sex
    if (currentSex === 2 || currentSex === '2' || currentSex === '女') {
        selectedSex.value = 2
    } else if (currentSex === 1 || currentSex === '1' || currentSex === '男') {
        selectedSex.value = 1
    } else {
        selectedSex.value = 0
    }
    // 显示选择器
    showSexPicker.value = true
}

// 性别选择确认
const handleSexConfirm = (value: number) => {
    if (value === undefined || ![0, 1, 2].includes(value)) return
    setUserInfoFun(String(value))
}

// 修改用户账号
const changeUserNameConfirm = () => {
    if (newUsername.value == '') return uni.$u.toast('账号不能为空')
    if (newUsername.value.length > 10) return uni.$u.toast('账号长度不得超过十位数')

    fieldType.value = FieldType.USERNAME
    setUserInfoFun(newUsername.value)
    showUserName.value = false
}

// 修改用户昵称
const changeNameConfirm = async (e: any) => {
    newNickname.value = e.detail.value.nickname
    if (newNickname.value == '') return uni.$u.toast('昵称不能为空')
    if (newNickname.value.length > 10) return uni.$u.toast('昵称长度不得超过十位数')
    fieldType.value = FieldType.NICKNAME
    await setUserInfoFun(newNickname.value)
    showNickName.value = false
}

// 微信小程序 绑定/修改用户手机号
const getPhoneNumber = async (e: any): Promise<void> => {
    const { encryptedData, iv, code } = e.detail
    const data = {
        code,
        encrypted_data: encryptedData,
        iv
    }
    if (encryptedData) {
        await userMnpMobile({ ...data })
        uni.$u.toast('操作成功')
        getUser()
    }
}

onShow(async () => {
    getUser()
})

onUnload(() => {
    if (codeTimer) {
        clearInterval(codeTimer)
    }
})
</script>

<style lang="scss" scoped>
.user-data-page {
    min-height: 100vh;
    background-color: #F5F5F5;
    padding-bottom: 48rpx;
}

// 头像区域
.avatar-section {
    padding: 48rpx 24rpx 32rpx;
    background: #FFFFFF;
    
    .avatar-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 16rpx;
        
        .avatar-tip {
            font-size: 24rpx;
            color: #94A3B8;
        }
    }
}

// 信息列表
.info-list {
    margin-top: 24rpx;
    background: #FFFFFF;
    border-radius: 16rpx;
    margin-left: 24rpx;
    margin-right: 24rpx;
    overflow: hidden;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.08);
}

.info-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 32rpx;
    border-bottom: 1rpx solid #F1F5F9;
    transition: all 0.2s ease;
    
    &:last-child {
        border-bottom: none;
    }
    
    &:active {
        background-color: #F8FAFC;
    }
    
    .info-label {
        font-size: 28rpx;
        color: #1E293B;
        font-weight: 500;
        width: 150rpx;
        flex-shrink: 0;
    }
    
    .info-content {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 16rpx;
        
        .info-value {
            font-size: 28rpx;
            color: #334155;
            max-width: 400rpx;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            
            &.text-muted {
                color: #94A3B8;
            }
        }
    }
}

.phone-item {
    .bind-btn {
        padding: 8rpx 24rpx;
        border-radius: 24rpx;
        font-size: 26rpx;
        font-weight: 500;
        transition: all 0.2s ease;
        position: relative;
        
        &:active {
            transform: scale(0.95);
            opacity: 0.8;
        }
        
        .bind-btn-inner {
            background: transparent;
            border: none;
            padding: 0;
            margin: 0;
            font-size: 26rpx;
            font-weight: 500;
            line-height: 1;
            color: inherit;
            
            &::after {
                border: none;
            }
        }
    }
}

// 弹窗样式
.edit-popup {
    width: 85vw;
    padding: 48rpx 40rpx 40rpx;
    background: #FFFFFF;
    border-radius: 24rpx;
    
    .popup-title {
        font-size: 36rpx;
        font-weight: 600;
        color: #1E293B;
        text-align: center;
        margin-bottom: 48rpx;
    }
    
    .popup-input-wrapper {
        padding-bottom: 16rpx;
        border-bottom: 2rpx solid #E2E8F0;
        margin-bottom: 24rpx;
        
        &.code-input-wrapper {
            display: flex;
            align-items: center;
            gap: 24rpx;
        }
        
        .popup-input {
            width: 100%;
            height: 72rpx;
            font-size: 28rpx;
            color: #1E293B;
            
            .input-placeholder {
                color: #CBD5E1;
            }
        }
        
        .code-btn {
            padding: 12rpx 24rpx;
            font-size: 26rpx;
            font-weight: 500;
            white-space: nowrap;
            border-radius: 8rpx;
            transition: all 0.2s ease;
            
            &:active:not(.code-btn-disabled) {
                transform: scale(0.95);
                opacity: 0.8;
            }
            
            &.code-btn-disabled {
                color: #94A3B8;
            }
        }
    }
    
    .popup-actions {
        margin-top: 48rpx;
        
        .popup-btn {
            width: 100%;
            height: 88rpx;
            border-radius: 48rpx;
            font-size: 32rpx;
            font-weight: 600;
            border: none;
            transition: all 0.2s ease;
            
            &::after {
                border: none;
            }
            
            &:active {
                transform: translateY(2rpx);
                opacity: 0.9;
            }
        }
    }
}
</style>
