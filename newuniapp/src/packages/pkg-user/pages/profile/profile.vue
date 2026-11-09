<template>
    <!-- 头部统一：走 page-shell 的 nav-bar，不再依赖系统导航 -->
    <page-shell title="个人资料" bg="page" :show-back="true">
        <view class="page-wrap profile">
            <view v-if="loading" class="page-pad">
                <base-skeleton :rows="4" />
            </view>

            <template v-else>
                <base-card :flush="true">
                    <view hover-class="is-pressed" class="row" @tap="chooseAvatar">
                        <text class="row__label">头像</text>
                        <view class="row__value">
                            <image
                                class="row__avatar"
                                :src="form.avatar || defaultAvatar"
                                mode="aspectFill"
                            />
                            <!-- v1 u-icon → v2 tn-icon：arrow-right → right -->
                            <tn-icon name="right" :size="24" class="ic-ink-3" />
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
                            @blur="validateNickname"
                        />
                    </view>
                    <!-- 字段级错误：红字落在字段正下方，不弹 toast（01 §6.0 硬要求） -->
                    <text v-if="nicknameError" class="field-error">{{ nicknameError }}</text>

                    <view class="row row--last">
                        <text class="row__label">性别</text>
                        <view class="row__value">
                            <!-- P0 §2-6：手写 .seg 三选一 → chip-group（它的第二个接入点；
                                 一个用例是巧合，两个用例才是模式） -->
                            <chip-group v-model="form.sex" :items="GENDERS" size="md" />
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
                    <base-button
                        text="保存"
                        variant="ink"
                        size="lg"
                        :loading="saving"
                        @click="handleSave"
                    />
                    <!-- 保存失败落点：按钮恢复 + 按钮下方一行红字（P0 §2-6）。
                         原来失败只靠请求层 toast，页面本身没有任何落点。 -->
                    <text v-if="saveError" class="field-error field-error--submit">{{
                        saveError
                    }}</text>
                </view>
            </template>
        </view>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
import { toast } from '@/composables/useToast'
import { chooseImages } from '@/composables/useChooseImage'
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
/** 字段级错误（红字落在字段下方，不走 toast） */
const nicknameError = ref('')
/** 保存失败落点 */
const saveError = ref('')

/** 昵称失焦校验：空值不给保存（原来只在 placeholder 里提示，失焦无任何反馈） */
function validateNickname() {
    nicknameError.value = form.nickname.trim() ? '' : '请输入昵称'
    return !nicknameError.value
}

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

async function chooseAvatar() {
    // 合规 S1：统一封装，含取消 / 权限被拒 / 异常三种 fail 处理
    const paths = await chooseImages({ count: 1 })
    const path = paths[0]
    if (!path) return
    try {
        const up = await uploadImage(path)
        form.avatar = (up as any)?.uri || (up as any)?.url || path
    } catch {
        // 上传失败保留原头像
    }
}

async function handlePhone(e: any) {
    if (isPhoneAuthDenied(e)) {
        toast.info('已取消授权')
        return
    }
    const code = extractPhoneCode(e)
    if (!code) {
        toast.info('未获取到手机号')
        return
    }
    try {
        const res = await getMobileByMnp({ code })
        mobile.value = String((res as any)?.mobile || '')
        toast.success('绑定成功')
    } catch {
        // 请求层已提示
    }
}

async function handleSave() {
    if (saving.value) return
    // 保存前先跑一次校验：失焦校验只在 blur 触发，用户直接点保存时可能还没校验过
    if (!validateNickname()) return
    saving.value = true
    saveError.value = ''
    try {
        await setProfile({ nickname: form.nickname, avatar: form.avatar, sex: form.sex })
        userStore.setUserInfo({ ...userStore.userInfo, ...form })
        toast.success('已保存')
        setTimeout(() => uni.navigateBack(), 600)
    } catch {
        // 请求层已 toast；这里补页面级落点，让用户知道「没保存成功」而不是只看到一闪而过的提示
        saveError.value = '没保存成功，再试一次'
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
        border-radius: var(--r-pill);
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

/* 字段级错误：红字落在字段正下方（01 §6.0：错误反馈不经 toast）
   左侧缩进对齐输入区（标签 140rpx + 行内 gap --sp-3） */
.field-error {
    display: block;
    padding: 0 var(--sp-4) var(--sp-2) calc(140rpx + var(--sp-3) + var(--sp-4));
    font-size: var(--fs-md);
    color: var(--c-danger);
    line-height: var(--lh-body);

    &--submit {
        padding: var(--sp-2) 0 0;
        text-align: center;
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
    line-height: var(--lh-tight);
    flex-shrink: 0;
}

.submit {
    margin: var(--sp-4) 0 calc(var(--sp-4) + var(--safe-bottom));
}

/* tn-icon 颜色工具类（本地 scoped 版）：tn-icon 的 color prop 不认 var()，
   颜色必须靠 CSS 落在图标根节点上再由内部 <text> 继承。 */
.ic-ink-3 {
    color: var(--c-ink-3);
}
</style>
