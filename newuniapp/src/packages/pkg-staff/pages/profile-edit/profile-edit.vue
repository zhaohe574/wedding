<template>
    <page-shell title="编辑资料" bg="page" :show-back="true" :bottom-bar="true">
        <view v-if="loading && !hasLoaded" class="pad">
            <base-skeleton :rows="5" />
        </view>

        <base-empty
            v-else-if="error && !hasLoaded"
            status="error"
            :error-kind="view?.errorKind || 'unknown'"
            :error-text="view?.title"
            :desc="view?.desc"
            :primary-action="view?.primaryAction"
            fixed
            @primary="onPrimary"
            @retry="load"
        />

        <view v-else class="pe">
            <!-- 灰度关闭：表单可浏览，但保存不可用（入口已隐藏，这是直达路径的兜底） -->
            <view v-if="!canWrite" class="gate-note">
                <tn-icon name="tips" :size="24" class="gate-note__icon" />
                <text class="gate-note__text">{{ STAFF_WRITE_GATE_TIP }}</text>
            </view>

            <!-- 身份信息 -->
            <base-card>
                <section-header title="身份信息" flush />
                <!-- 头像：单图上传走 chooseImages + uploadImage（同 pkg-user/profile 口径） -->
                <view class="avatar-row" hover-class="is-pressed" @tap="chooseAvatar">
                    <text class="avatar-row__label">头像</text>
                    <view class="avatar-row__right">
                        <image
                            v-if="form.avatar"
                            class="avatar-row__img"
                            :src="form.avatar"
                            mode="aspectFill"
                            aria-label="服务人员头像"
                        />
                        <view v-else class="avatar-row__img avatar-row__img--empty">
                            <tn-icon name="add" :size="32" />
                        </view>
                        <text class="avatar-row__link">{{ form.avatar ? '更换' : '上传' }}</text>
                    </view>
                </view>

                <form-field
                    label="姓名"
                    type="input"
                    :required="true"
                    :maxlength="50"
                    placeholder="请输入姓名"
                    :error="errors.name"
                    :model-value="form.name"
                    @update:model-value="(v: string) => (form.name = v)"
                />

                <!-- 手机号：展示行 + 更换入口（不是输入控件，自绘 row）；
                     原值展示后端给的掩码，更换走验证码弹层 -->
                <view class="mobile-row">
                    <view class="mobile-row__line">
                        <text class="mobile-row__label">手机号</text>
                        <view class="mobile-row__right">
                            <text class="mobile-row__value">{{
                                form.mobile || '未绑定'
                            }}</text>
                            <text class="mobile-row__link" @tap="openMobilePopup">更换</text>
                        </view>
                    </view>
                    <text v-if="errors.mobile" class="field-err">{{ errors.mobile }}</text>
                </view>
            </base-card>

            <!-- 服务信息 -->
            <base-card>
                <section-header title="服务信息" flush />
                <form-field
                    label="服务分类"
                    type="picker"
                    :required="true"
                    placeholder="请选择服务分类"
                    :error="errors.category"
                    :model-value="categoryName"
                    @tap="openCategoryPopup"
                />
                <form-field
                    label="从业年限"
                    type="input"
                    :maxlength="3"
                    placeholder="如 8"
                    hint="填数字，单位：年"
                    :error="errors.experience"
                    :model-value="form.experience_years"
                    @update:model-value="(v: string) => (form.experience_years = v)"
                />
            </base-card>

            <!-- 个人介绍 -->
            <base-card>
                <section-header title="个人介绍" flush />
                <form-field
                    label="个人简介"
                    type="textarea"
                    layout="stack"
                    :maxlength="500"
                    count
                    placeholder="向客户介绍你自己，最多 500 字"
                    :model-value="form.profile"
                    @update:model-value="(v: string) => (form.profile = v)"
                />
                <form-field
                    label="服务说明"
                    type="textarea"
                    layout="stack"
                    :maxlength="1000"
                    count
                    placeholder="说明你的服务内容与流程，最多 1000 字"
                    :model-value="form.service_desc"
                    @update:model-value="(v: string) => (form.service_desc = v)"
                />
                <form-field
                    label="详细介绍"
                    type="textarea"
                    layout="stack"
                    :maxlength="2000"
                    count
                    placeholder="选填，案例、擅长风格等补充信息"
                    :model-value="form.long_detail"
                    @update:model-value="(v: string) => (form.long_detail = v)"
                />
            </base-card>

            <!-- 专业标签：多选网格；改动走审核（tag_action=pending） -->
            <base-card>
                <section-header title="专业标签" subtitle="标签变更提交后需重新审核" flush />
                <view v-if="tagOptions.length" class="tags">
                    <view
                        v-for="t in tagOptions"
                        :key="t.id"
                        hover-class="is-pressed"
                        class="tag"
                        :class="{ 'is-active': selectedTagIds.includes(t.id) }"
                        :aria-role="'checkbox'"
                        :aria-checked="selectedTagIds.includes(t.id)"
                        :aria-label="t.name"
                        @tap="toggleTag(t.id)"
                    >
                        <text class="tag__text">{{ t.name }}</text>
                    </view>
                </view>
                <view v-else class="tags-empty">
                    <text class="tags-empty__text">标签列表加载失败或暂无可选标签</text>
                    <text class="tags-empty__retry" @tap="loadTags">重新加载</text>
                </view>
            </base-card>
        </view>

        <template #bottom>
            <bottom-bar>
                <text class="bar__note">{{
                    canWrite ? '标签与资料变更将进入审核' : STAFF_WRITE_GATE_TIP
                }}</text>
                <template #actions>
                    <base-button
                        v-if="canWrite"
                        :text="submitting ? '保存中…' : '保存'"
                        variant="ink"
                        size="lg"
                        :block="false"
                        :loading="submitting"
                        :disabled="submitting"
                        @click="submit"
                    />
                </template>
            </bottom-bar>
        </template>
    </page-shell>

    <!-- 服务分类选择（单层：后台分类选择器 checkStrictly 允许绑父级，取一级分类即可） -->
    <tn-popup
        v-model="categoryPopup"
        open-direction="bottom"
        bg-color="transparent"
        radius="0"
        :safe-area-inset-bottom="true"
    >
        <view class="sheet">
            <view class="sheet__head">
                <text class="sheet__title">选择服务分类</text>
                <view class="sheet__close" @tap="categoryPopup = false">
                    <tn-icon name="close" :size="28" />
                </view>
            </view>
            <scroll-view class="sheet__body" scroll-y>
                <view
                    v-for="c in categories"
                    :key="c.id"
                    hover-class="is-pressed"
                    class="opt"
                    :class="{ 'opt--on': form.category_id === c.id }"
                    @tap="pickCategory(c)"
                >
                    <text class="opt__name">{{ c.name }}</text>
                    <tn-icon v-if="form.category_id === c.id" name="check" :size="24" />
                </view>
                <view v-if="!categories.length" class="sheet__empty">
                    <text class="sheet__empty-text">分类没加载出来</text>
                    <text class="sheet__empty-retry" @tap="loadCategories">重试</text>
                </view>
            </scroll-view>
        </view>
    </tn-popup>

    <!-- 更换手机号：新号码 + 短信验证码，确定后随表单一起提交（不在弹层里单独提交） -->
    <tn-popup
        v-model="mobilePopup"
        open-direction="bottom"
        bg-color="transparent"
        radius="0"
        :safe-area-inset-bottom="true"
    >
        <view class="sheet">
            <view class="sheet__head">
                <text class="sheet__title">更换手机号</text>
                <view class="sheet__close" @tap="mobilePopup = false">
                    <tn-icon name="close" :size="28" />
                </view>
            </view>
            <text class="sheet__desc">验证码将发送到新手机号，点「保存」后生效。</text>
            <input
                v-model="mobileDraft"
                class="sheet__input"
                type="number"
                :maxlength="11"
                placeholder="请输入 11 位新手机号"
                placeholder-class="sheet__ph"
            />
            <view class="sms-row">
                <input
                    v-model="smsCode"
                    class="sms-row__input"
                    type="number"
                    :maxlength="8"
                    placeholder="请输入短信验证码"
                    placeholder-class="sheet__ph"
                />
                <view
                    class="sms-row__btn"
                    :class="{ 'is-off': smsCountdown > 0 }"
                    hover-class="is-pressed"
                    :aria-label="smsCountdown > 0 ? `${smsCountdown} 秒后可重新发送` : '发送验证码'"
                    @tap="sendCode"
                >
                    <text class="sms-row__btn-text">{{
                        smsCountdown > 0 ? `${smsCountdown}s 后重发` : '发送验证码'
                    }}</text>
                </view>
            </view>
            <text v-if="mobilePopupErr" class="field-err field-err--pad">{{
                mobilePopupErr
            }}</text>
            <view class="sheet__foot">
                <base-button text="确定" variant="ink" size="lg" @click="confirmMobile" />
            </view>
        </view>
    </tn-popup>

    <!-- 分类变更确认：联动套餐/附加项分类，提交前最后确认一次（tn-popup，与其他弹层互斥） -->
    <tn-popup
        v-model="categoryConfirm"
        open-direction="center"
        radius="24"
        :safe-area-inset-bottom="true"
    >
        <view class="confirm">
            <text class="confirm__title">确认修改服务分类</text>
            <text class="confirm__text">
                修改服务分类将联动已发布套餐与附加项的分类，确认提交？
            </text>
            <view class="confirm__btns">
                <view class="confirm__btn">
                    <base-button
                        text="取消"
                        variant="ghost"
                        size="md"
                        block
                        @click="cancelCategoryChange"
                    />
                </view>
                <view class="confirm__btn">
                    <base-button
                        text="确认提交"
                        variant="ink"
                        size="md"
                        block
                        :loading="submitting"
                        @click="doSubmit"
                    />
                </view>
            </view>
        </view>
    </tn-popup>

    <app-toast />
</template>

<script setup lang="ts">
/**
 * 服务人员：编辑资料（P0 写能力）。
 *
 * 三条硬口径：
 * 1. 提交走 /staff_center/updateProfile，按 tag_action 区分成功语义：
 *    applied → 「资料已保存」；pending → 「已提交审核」（标签变更审核通过前前台仍显示旧标签）；
 * 2. 手机号变更不在弹层里单独提交：弹层只收集「新号码 + 验证码」，
 *    保存时随表单一起带 code 提交 —— 后端一次校验，避免「弹层里改了、表单没保存」的半截状态；
 * 3. 分类变更联动已发布套餐与附加项的分类，提交前必须弹确认；
 *    弹层用的是 tn-popup（v-model 绑 modelValue，不要写成 v-model:show）。
 */
import { computed, reactive, ref } from 'vue'
import { onLoad, onUnload } from '@dcloudio/uni-app'
import {
    getStaffCenterProfile,
    updateStaffProfile,
    type StaffProfileUpdateParams
} from '@/api/staff-center'
import { sendSmsCode } from '@/api/auth'
import { getCategoryTree, getServiceTags, type ServiceCategory } from '@/api/catalog'
import type { ServiceTagVO } from '@/api/types/catalog'
import { uploadImage } from '@/api/app'
import { chooseImages } from '@/composables/useChooseImage'
import { useStaffWriteGate, STAFF_WRITE_GATE_TIP } from '@/composables/useStaffWriteGate'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { toast } from '@/composables/useToast'

/* ---------------- 加载 ---------------- */

const loading = ref(false)
const hasLoaded = ref(false)
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: true }))
const error = computed(() => !!rawError.value)

const { canWrite } = useStaffWriteGate()

/* ---------------- 表单 ---------------- */

const form = reactive({
    name: '',
    avatar: '',
    /** 展示用：原手机号（后端给的掩码）或更换后的新号码 */
    mobile: '',
    category_id: 0,
    experience_years: '',
    profile: '',
    service_desc: '',
    long_detail: ''
})

const errors = reactive({
    name: '',
    mobile: '',
    category: '',
    experience: ''
})

/** 提交基准：分类/手机号是否变更的比对原值 */
const originalCategoryId = ref(0)
const originalMobile = ref('')

const selectedTagIds = ref<number[]>([])
const submitting = ref(false)

/* ---------------- 分类 ---------------- */

const categories = ref<ServiceCategory[]>([])
const categoryPopup = ref(false)
const categoryConfirm = ref(false)
const originalCategoryName = ref('')

/**
 * 分类展示名：优先从已加载的分类树里按 id 找（保持与选择器一致），
 * 找不到（分类树加载失败/分类被下架）回退到 profile 返回的 category_name。
 */
const categoryName = computed(() => {
    const hit = categories.value.find((c) => c.id === form.category_id)
    if (hit) return hit.name
    if (form.category_id === originalCategoryId.value && originalCategoryName.value) {
        return originalCategoryName.value
    }
    return ''
})

async function loadCategories() {
    try {
        const tree = (await getCategoryTree()) || []
        // 只取一级：后台编辑允许把人员绑在父级分类上（checkStrictly），
        // 只允许选叶子会让绑父级的人选不回自己当前的分类
        categories.value = (Array.isArray(tree) ? tree : []).map((c) => ({
            id: Number(c.id),
            name: String(c.name || '')
        }))
    } catch {
        // 静默：弹层里有重试入口
    }
}

function openCategoryPopup() {
    // tn-popup 互斥：打开前先关掉其他弹层
    mobilePopup.value = false
    categoryConfirm.value = false
    errors.category = ''
    categoryPopup.value = true
}

function pickCategory(c: { id: number; name: string }) {
    form.category_id = c.id
    errors.category = ''
    categoryPopup.value = false
}

/* ---------------- 标签 ---------------- */

const tagOptions = ref<ServiceTagVO[]>([])

async function loadTags() {
    try {
        const list = (await getServiceTags()) || []
        tagOptions.value = Array.isArray(list) ? list : []
    } catch {
        // 静默：卡片里有重新加载入口
    }
}

function toggleTag(id: number) {
    const i = selectedTagIds.value.indexOf(id)
    if (i >= 0) selectedTagIds.value.splice(i, 1)
    else selectedTagIds.value.push(id)
}

/* ---------------- 头像 ---------------- */

async function chooseAvatar() {
    const paths = await chooseImages({ count: 1 })
    const path = paths[0]
    if (!path) return
    try {
        const up = await uploadImage(path)
        form.avatar = (up as any)?.uri || (up as any)?.url || path
    } catch {
        // 上传失败保留原头像（请求层已提示）
    }
}

/* ---------------- 更换手机号 ---------------- */

const mobilePopup = ref(false)
const mobileDraft = ref('')
const smsCode = ref('')
const mobilePopupErr = ref('')
const smsCountdown = ref(0)
let smsTimer: ReturnType<typeof setInterval> | null = null

/** 是否已收集到新号码（点过弹层的「确定」） */
const mobilePending = ref(false)

function openMobilePopup() {
    categoryPopup.value = false
    categoryConfirm.value = false
    mobilePopupErr.value = ''
    // 已收集过新号码时回显，便于改错重填；验证码不清空（重开弹层不代表放弃已收集的验证码）
    mobileDraft.value = mobilePending.value ? form.mobile : ''
    mobilePopup.value = true
}

function startCountdown() {
    smsCountdown.value = 60
    if (smsTimer) clearInterval(smsTimer)
    smsTimer = setInterval(() => {
        smsCountdown.value -= 1
        if (smsCountdown.value <= 0) {
            if (smsTimer) clearInterval(smsTimer)
            smsTimer = null
        }
    }, 1000)
}

async function sendCode() {
    if (smsCountdown.value > 0) return
    const mobile = mobileDraft.value.trim()
    if (!/^1\d{10}$/.test(mobile)) {
        mobilePopupErr.value = '请输入 11 位手机号'
        return
    }
    mobilePopupErr.value = ''
    try {
        await sendSmsCode({ mobile })
        toast.info('验证码已发送，5 分钟内有效')
        startCountdown()
    } catch {
        // silent: true，失败提示这里自己发，避免与倒计时状态脱节
        mobilePopupErr.value = '验证码发送失败，请稍后重试'
    }
}

function confirmMobile() {
    const mobile = mobileDraft.value.trim()
    const code = smsCode.value.trim()
    if (!/^1\d{10}$/.test(mobile)) {
        mobilePopupErr.value = '请输入 11 位手机号'
        return
    }
    if (!/^\d{4,8}$/.test(code)) {
        mobilePopupErr.value = '请输入短信验证码（4-8 位数字）'
        return
    }
    // 弹层只收集，不提交：mobile/code 随表单一起走 updateProfile
    form.mobile = mobile
    mobilePending.value = true
    mobilePopupErr.value = ''
    mobilePopup.value = false
}

onUnload(() => {
    if (smsTimer) clearInterval(smsTimer)
    smsTimer = null
})

/* ---------------- 校验与提交 ---------------- */

function validate(): boolean {
    const name = form.name.trim()
    if (!name) {
        errors.name = '请输入姓名'
    } else if (name.length > 50) {
        errors.name = '姓名不能超过 50 字'
    } else {
        errors.name = ''
    }
    errors.category = form.category_id ? '' : '请选择服务分类'
    const years = form.experience_years.trim()
    errors.experience = years && !/^\d{1,3}$/.test(years) ? '从业年限请填数字' : ''
    errors.mobile = mobilePending.value && !/^\d{4,8}$/.test(smsCode.value.trim())
        ? '验证码已失效，请重新更换手机号'
        : ''

    const first = errors.name || errors.category || errors.experience || errors.mobile
    if (first) {
        toast.warning(first)
        return false
    }
    return true
}

/** 分类是否发生了变更（联动套餐/附加项，必须确认） */
const categoryChanged = computed(
    () => originalCategoryId.value > 0 && form.category_id !== originalCategoryId.value
)

/** 取消分类变更确认：表单保留（用户可改回原分类再保存） */
function cancelCategoryChange() {
    categoryConfirm.value = false
}

function submit() {
    if (submitting.value) return
    // 灰度兜底：入口已隐藏，这里是直达路径的最后闸门（后端也会拒绝）
    if (!canWrite.value) {
        toast.warning(STAFF_WRITE_GATE_TIP)
        return
    }
    if (!validate()) return

    // 分类变更：先弹确认（tn-popup），确认后才真正提交
    if (categoryChanged.value) {
        mobilePopup.value = false
        categoryConfirm.value = true
        return
    }
    doSubmit()
}

async function doSubmit() {
    if (submitting.value) return
    submitting.value = true
    try {
        const years = Number(form.experience_years.trim())
        const payload: StaffProfileUpdateParams = {
            name: form.name.trim(),
            avatar: form.avatar || undefined,
            category_id: form.category_id,
            experience_years: Number.isFinite(years) ? years : undefined,
            profile: form.profile.trim(),
            service_desc: form.service_desc.trim(),
            long_detail: form.long_detail.trim(),
            tag_ids: selectedTagIds.value.length ? selectedTagIds.value : []
        }
        if (mobilePending.value) {
            payload.mobile = form.mobile.trim()
            payload.code = smsCode.value.trim()
        }

        const res = await updateStaffProfile(payload)
        // tag_action=pending 时不能说「保存成功」：标签要等审核通过才生效
        if (res && res.tag_action === 'pending') {
            toast.success('已提交审核，标签变更审核通过后生效')
        } else {
            toast.success('资料已保存')
        }
        // profile 页 onShow 会重新拉取，这里直接返回即可
        setTimeout(() => uni.navigateBack(), 600)
    } catch (e: any) {
        // 写封装是 silent:true，错误反馈由页面承担（API 契约的错误文案映射）：
        //   · 验证码相关失败 → 提示后重新打开收集弹层，让用户补验证码或重输；
        //   · 灰度关闭 / 内容安全命中 / 其他业务拒绝 → 后端 msg 直接展示
        const msg = String(e?.message || '')
        if (msg.includes('验证码')) {
            toast.warning(msg || '请先获取短信验证码')
            openMobilePopup()
        } else {
            toast.error(msg || '保存失败，请稍后重试')
        }
    } finally {
        categoryConfirm.value = false
        submitting.value = false
    }
}

/* ---------------- 加载回填 ---------------- */

function maskMobile(value: string) {
    return String(value || '').replace(/^(\d{3})\d{4}(\d{4})$/, '$1****$2')
}

async function load() {
    if (loading.value) return
    loading.value = true
    if (!hasLoaded.value) rawError.value = null
    try {
        const res: any = (await getStaffCenterProfile()) || {}
        form.name = String(res.name || '')
        form.avatar = String(res.avatar || '')
        // mobile_full 是完整号（不可直接回填输入框，仅用于比对），mobile 是掩码
        originalMobile.value = String(res.mobile_full || res.mobile || '')
        form.mobile = maskMobile(originalMobile.value)
        originalCategoryId.value = Number(res.category_id || 0)
        form.category_id = originalCategoryId.value
        originalCategoryName.value = String(res.category_name || '')
        form.experience_years = Number(res.experience_years) > 0 ? String(Number(res.experience_years)) : ''
        form.profile = String(res.profile || '')
        form.service_desc = String(res.service_desc || '')
        form.long_detail = String(res.long_detail || '')

        // 标签回填：tag_ids 优先；没有就按 tag_names 在标签库里匹配
        selectedTagIds.value = Array.isArray(res.tag_ids) ? res.tag_ids.map(Number) : []
        hasLoaded.value = true

        // 分类树与标签库并行拉，失败不阻塞表单（各自有重试入口）
        void loadCategories()
        void loadTags().then(() => {
            if (!selectedTagIds.value.length) {
                const names: string[] = Array.isArray(res.tag_names) ? res.tag_names : []
                if (names.length) {
                    selectedTagIds.value = tagOptions.value
                        .filter((t) => names.includes(t.name))
                        .map((t) => t.id)
                }
            }
        })
    } catch (e) {
        if (hasLoaded.value) {
            toast.error('刷新失败，请稍后重试')
        } else {
            rawError.value = e
        }
    } finally {
        loading.value = false
    }
}

function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        load()
        return
    }
    if (kind === 'reselect' || kind === 'back') {
        uni.navigateBack()
        return
    }
    if (kind === 'login') {
        uni.navigateTo({ url: '/pages/login/login' })
    }
}

onLoad(() => load())
</script>

<style lang="scss" scoped>
.pad {
    padding: var(--sp-3) var(--sp-4);
}

.pe {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    padding: var(--sp-3) var(--sp-4) var(--sp-4);
}

/* 灰度关闭提示条 */
.gate-note {
    display: flex;
    align-items: flex-start;
    gap: var(--sp-2);
    padding: var(--sp-3);
    border-radius: var(--r-sm);
    background: var(--c-neutral-bg);

    &__icon {
        flex-shrink: 0;
        margin-top: 4rpx;
        color: var(--c-ink-3);
    }

    &__text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
    }
}

/* 头像行 */
.avatar-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-3);
    min-height: 112rpx;
    border-bottom: 1rpx solid var(--c-control-border);

    &__label {
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &__right {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
    }

    &__img {
        width: 104rpx;
        height: 104rpx;
        border-radius: var(--r-pill);
        background: var(--c-neutral-bg);
        flex-shrink: 0;

        &--empty {
            display: flex;
            align-items: center;
            justify-content: center;
            /* tn-icon 不认 var() color prop（R13），颜色靠继承 */
            color: var(--c-ink-3);
            border: 1rpx dashed var(--c-control-border);
            box-sizing: border-box;
        }
    }

    &__link {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

/* 手机号行 */
.mobile-row {
    &__line {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-3);
        min-height: 88rpx;
        border-bottom: 1rpx solid var(--c-control-border);
    }

    &__label {
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &__right {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
        min-width: 0;
    }

    &__value {
        font-size: var(--fs-md);
        color: var(--c-ink);
        font-variant-numeric: tabular-nums;
    }

    &__link {
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
    }
}

/* 字段级错误：红字贴字段下方 */
.field-err {
    display: block;
    padding-top: var(--sp-1);
    font-size: var(--fs-xs);
    line-height: var(--lh-body);
    color: var(--c-danger);

    &--pad {
        margin-top: var(--sp-1);
    }
}

/* 标签网格（review/publish 的 .tag 口径） */
.tags {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-2);
    margin-top: var(--sp-3);
}

.tag {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 64rpx;
    padding: 0 var(--sp-3);
    border-radius: var(--r-pill);
    background: var(--c-surface-2);
    border: 1rpx solid var(--c-control-border);
    box-sizing: border-box;

    &.is-active {
        background: var(--c-ink);
        border-color: var(--c-ink);
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &.is-active &__text {
        color: var(--c-on-dark);
    }
}

.tags-empty {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    margin-top: var(--sp-3);

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__retry {
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
    }
}

.bar__note {
    font-size: var(--fs-xs);
    color: var(--c-ink-3);
}

/* ---------------- 弹层 ---------------- */
.sheet {
    background: var(--c-surface);
    padding: var(--sp-4) var(--sp-4) calc(var(--sp-4) + var(--safe-bottom));
    border-radius: var(--r-lg) var(--r-lg) 0 0;

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__title {
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__close {
        color: var(--c-ink-3);
        width: 56rpx;
        height: 56rpx;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__desc {
        display: block;
        margin-top: var(--sp-1);
        font-size: var(--fs-xs);
        line-height: var(--lh-loose);
        color: var(--c-ink-3);
    }

    &__body {
        max-height: 640rpx;
        margin-top: var(--sp-3);
    }

    &__empty {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--sp-2);
        padding: var(--sp-5) 0;
    }

    &__empty-text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__empty-retry {
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
    }

    &__input {
        width: 100%;
        height: 88rpx;
        margin-top: var(--sp-3);
        padding: 0 var(--sp-3);
        border-radius: var(--r-sm);
        border: 1rpx solid var(--c-control-border);
        background: var(--c-surface);
        font-size: var(--fs-md);
        color: var(--c-ink);
        box-sizing: border-box;
    }

    &__ph {
        color: var(--c-ink-3);
    }

    &__foot {
        margin-top: var(--sp-4);
    }
}

.opt {
    color: var(--c-brand-text);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);
    min-height: 96rpx;
    padding: var(--sp-2) var(--sp-3);
    border-radius: var(--r-sm);
    border: 1rpx solid var(--c-line);
    margin-bottom: var(--sp-2);

    &--on {
        border-color: var(--c-brand-text);
        background: var(--c-brand-bg);
    }

    &__name {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-md);
        color: var(--c-ink);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }
}

/* 验证码行 */
.sms-row {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    margin-top: var(--sp-3);

    &__input {
        flex: 1;
        min-width: 0;
        height: 88rpx;
        padding: 0 var(--sp-3);
        border-radius: var(--r-sm);
        border: 1rpx solid var(--c-control-border);
        background: var(--c-surface);
        font-size: var(--fs-md);
        color: var(--c-ink);
        box-sizing: border-box;
    }

    &__btn {
        flex-shrink: 0;
        height: 88rpx;
        padding: 0 var(--sp-3);
        border-radius: var(--r-sm);
        background: var(--c-brand-bg);
        display: flex;
        align-items: center;
        justify-content: center;

        &.is-off {
            background: var(--c-neutral-bg);
        }
    }

    &__btn-text {
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
    }

    &__btn.is-off &__btn-text {
        color: var(--c-ink-3);
    }
}

/* 分类变更确认弹层 */
.confirm {
    padding: var(--sp-5) var(--sp-5) calc(var(--sp-4) + var(--safe-bottom));
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);

    &__title {
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        text-align: center;
    }

    &__text {
        font-size: var(--fs-md);
        line-height: var(--lh-loose);
        color: var(--c-ink-2);
        text-align: center;
    }

    &__btns {
        display: flex;
        gap: var(--sp-3);
        margin-top: var(--sp-2);
    }

    &__btn {
        flex: 1;
        min-width: 0;
    }
}
</style>
