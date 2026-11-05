<template>
    <view class="page-wrap profile">
        <view v-if="loading" class="page-pad">
            <base-skeleton :rows="4" />
        </view>

        <template v-else>
            <base-card :flush="true">
                <view hover-class="is-pressed" class="row" @tap="chooseAvatar">
                    <text class="row__label">头像</text>
                    <view class="row__value">
                        <image class="row__avatar" :src="form.avatar || defaultAvatar" mode="aspectFill" />
                        <u-icon name="arrow-right" :size="24" color="var(--c-ink-3)" />
                    </view>
                </view>

                <view class="row">
                    <text class="row__label">昵称</text>
                    <input
                        v-model="form.nickname"
                        class="row__input"
                        type="nickname"
                        placeholder="请输入昵称"
                        placeholder-class="row__ph"
                        maxlength="20"
                    />
                </view>

                <view class="row row--last">
                    <text class="row__label">性别</text>
                    <view class="row__value">
                        <view class="seg">
                            <view hover-class="is-pressed"
                                v-for="g in GENDERS"
                                :key="g.value"
                                class="seg__item"
                                :class="{ 'is-active': form.sex === g.value }"
                                @tap="form.sex = g.value"
                            >
                                <text class="seg__text">{{ g.label }}</text>
                            </view>
                        </view>
                    </view>
                </view>
            </base-card>

            <base-card>
                <view class="row row--last row--plain">
                    <text class="row__label">手机号</text>
                    <view class="row__value">
                        <text class="row__text">{{ mobile || '未绑定' }}</text>
                        <button
                            v-if="!mobile"
                            class="phone-btn"
                            open-type="getPhoneNumber"
                            @getphonenumber="handlePhone"
                        >
                            绑定
                        </button>
                    </view>
                </view>
            </base-card>

            <view class="submit">
                <base-button text="保存" variant="brand" size="lg" :loading="saving" @click="handleSave" />
            </view>
        </template>
    </view>
</template>

<script setup lang="ts">
/** 个人资料：头像 / 昵称 / 性别 / 手机号 */
import { reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { getUserInfo, setProfile } from '@/api/user'
import { getMobileByMnp } from '@/api/auth'
import { uploadImage } from '@/api/app'
import { extractPhoneCode, isPhoneAuthDenied } from '@/platform/login'
import { useUserStore } from '@/stores/user'

const GENDERS = [
    { label: '未设置', value: 0 },
    { label: '男', value: 1 },
    { label: '女', value: 2 }
]

const defaultAvatar = '/static/images/user/default_avatar.png'
const userStore = useUserStore()

const loading = ref(true)
const saving = ref(false)
const mobile = ref('')
const form = reactive({ nickname: '', avatar: '', sex: 0 })

async function load() {
    loading.value = true
    try {
        const info = await getUserInfo()
        form.nickname = info?.nickname || ''
        form.avatar = info?.avatar || ''
        form.sex = Number(info?.sex ?? 0)
        mobile.value = String(info?.mobile || '')
    } catch {
        // 请求层已提示
    } finally {
        loading.value = false
    }
}

function chooseAvatar() {
    uni.chooseImage({
        count: 1,
        sizeType: ['compressed'],
        success: async (res) => {
            const path = res.tempFilePaths && res.tempFilePaths[0]
            if (!path) return
            try {
                const up = await uploadImage(path)
                form.avatar = (up as any)?.uri || (up as any)?.url || path
            } catch {
                // 上传失败保留原头像
            }
        }
    })
}

async function handlePhone(e: any) {
    if (isPhoneAuthDenied(e)) {
        uni.showToast({ title: '已取消授权', icon: 'none' })
        return
    }
    const code = extractPhoneCode(e)
    if (!code) {
        uni.showToast({ title: '未获取到手机号', icon: 'none' })
        return
    }
    try {
        const res = await getMobileByMnp({ code })
        mobile.value = String((res as any)?.mobile || '')
        uni.showToast({ title: '绑定成功', icon: 'success' })
    } catch {
        // 请求层已提示
    }
}

async function handleSave() {
    if (saving.value) return
    saving.value = true
    try {
        await setProfile({ nickname: form.nickname, avatar: form.avatar, sex: form.sex })
        userStore.setUserInfo({ ...userStore.userInfo, ...form })
        uni.showToast({ title: '已保存', icon: 'success' })
        setTimeout(() => uni.navigateBack(), 600)
    } catch {
        // 请求层已提示
    } finally {
        saving.value = false
    }
}

onLoad(() => load())
</script>

<style lang="scss" scoped>
.profile {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    padding: var(--sp-3) var(--sp-4) 0;
}

.row {
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    min-height: 112rpx;
    padding: var(--sp-3) var(--sp-4);
    border-bottom: 1rpx solid var(--c-line);
    box-sizing: border-box;

    &--last {
        border-bottom: none;
    }

    &--plain {
        min-height: 96rpx;
        padding: var(--sp-2) 0;
    }

    &__label {
        width: 120rpx;
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
        flex-shrink: 0;
    }

    &__value {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: var(--sp-2);
    }

    &__avatar {
        width: 104rpx;
        height: 104rpx;
        border: 1rpx solid var(--c-line);
        border-radius: 50%;
        background: var(--c-neutral-bg);
        flex-shrink: 0;
    }

    &__input {
        flex: 1;
        min-width: 0;
        height: 72rpx;
        font-size: var(--fs-md);
        color: var(--c-ink);
        text-align: right;
    }

    &__ph {
        color: var(--c-ink-3);
    }

    &__text {
        min-width: 0;
        font-size: var(--fs-md);
        color: var(--c-ink);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }
}

.seg {
    display: flex;
    justify-content: flex-end;
    gap: var(--sp-1);
    max-width: 100%;

    &__item {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 64rpx;
        padding: 0 20rpx;
        border-radius: var(--r-pill);
        background: var(--c-surface-2);
        border: 1rpx solid var(--c-line);
        box-sizing: border-box;

        &.is-active {
            background: var(--c-ink);
            border-color: var(--c-ink);
        }
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
        white-space: nowrap;
    }

    &__item.is-active &__text {
        color: var(--c-on-dark);
    }
}

.phone-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 112rpx;
    min-height: 64rpx;
    margin: 0;
    padding: 0 var(--sp-3);
    border-radius: var(--r-pill);
    background: var(--c-brand-bg);
    color: var(--c-brand-text);
    font-size: var(--fs-xs);
    line-height: 1.4;
    flex-shrink: 0;
}

.submit {
    margin: var(--sp-4) 0 calc(var(--sp-4) + var(--safe-bottom));
}
</style>
