<template>
    <!--
        19 候补申请（设计稿 3858–3957）
        ⚠ 三条硬口径：
        1. joinWaitlist 的 package_id 是**后端必填**（C-10/G14），本页必须有套餐选择，缺参在提交前就拦；
        2. 后端没有排队位次模型（G-13/G19），底部左位固定写「排队位次以提交后为准」，不编「第 3 位」；
        3. 后端 joinWaitlist 只接单个 date、没有 alt_dates（G-21），所以设计稿的「备选日期」整块不出现 ——
           不做一个提交后会被静默丢弃的字段。
    -->
    <page-shell bg="page" :bottom-bar="true" title="候补申请">
        <view v-if="loading" class="pad sk">
            <base-skeleton :rows="4" />
        </view>

        <base-empty
            v-else-if="error"
            status="error"
            :error-kind="view?.errorKind || 'unknown'"
            :error-text="view?.title"
            :desc="view?.desc"
            :primary-action="view?.primaryAction"
            fixed
            @primary="onPrimary"
            @retry="load"
        />

        <view v-else class="wl">
            <!-- WL 头（深色）：先承认事实，再给方案 -->
            <view class="head">
                <view class="head__flag">
                    <view class="head__flag-dot" />
                    <text class="head__flag-text">{{ fullFlagText }}</text>
                </view>
                <view class="head__main">
                    <text class="head__date">{{ dateText }}</text>
                    <text class="head__sub">{{ subText }}</text>
                </view>
                <view v-if="staffName" class="head__host">
                    <image
                        v-if="staffAvatar"
                        class="head__avatar"
                        :src="staffAvatar"
                        mode="aspectFill"
                    />
                    <view v-else class="head__avatar head__avatar--text">{{
                        staffName.slice(0, 1)
                    }}</view>
                    <text class="head__host-name">{{ staffName }}</text>
                    <text v-if="packageName" class="head__host-pkg">{{ packageName }}</text>
                </view>
            </view>

            <!-- 套餐（设计稿漏了，接口必填）：唯一会让整单提交失败的硬条件，排在校验第一位 -->
            <view
                hover-class="is-pressed"
                class="pkg"
                :class="{ 'pkg--err': !!errors.package }"
                aria-role="button"
                aria-label="选择候补套餐"
                @tap="openPackage"
            >
                <view class="pkg__l">
                    <text class="pkg__label">套餐</text>
                    <text class="pkg__req">必选</text>
                </view>
                <view class="pkg__r">
                    <text v-if="packageName" class="pkg__value">{{ packageName }}</text>
                    <text v-else class="pkg__ph">请选择套餐</text>
                    <text v-if="packagePrice" class="pkg__price">¥{{ packagePrice }}</text>
                    <tn-icon name="right" :size="20" color="var(--c-ink-3)" />
                </view>
            </view>
            <text v-if="errors.package" class="err">{{ errors.package }}</text>

            <!-- 三步规则 -->
            <view id="rules" class="rules">
                <section-header title="候补怎么运作" flush />
                <view class="steps3">
                    <view class="steps3__i"
                        ><text class="steps3__n">1</text
                        ><text class="steps3__t">按提交顺序排队</text></view
                    >
                    <view class="steps3__i"
                        ><text class="steps3__n">2</text
                        ><text class="steps3__t">释放即刻通知</text></view
                    >
                    <view class="steps3__i"
                        ><text class="steps3__n">3</text
                        ><text class="steps3__t">30 分钟内锁定</text></view
                    >
                </view>
                <text class="rules__note">
                    释放后服务号与短信同时通知，先到先得；超时未支付自动顺延下一位，不占用名额。
                </text>
            </view>

            <!-- 联系与偏好 -->
            <base-card class="blk">
                <section-header title="联系与偏好" flush />
                <view class="field">
                    <text class="field__k">手机号码</text>
                    <view class="field__v">
                        <text class="field__val">{{ maskedMobile || '未获取' }}</text>
                        <text class="field__link" @tap="openMobile">更换</text>
                    </view>
                </view>
                <text v-if="errors.mobile" class="err">{{ errors.mobile }}</text>

                <view class="field field--stack">
                    <view class="field__row">
                        <text class="field__k">备注</text>
                        <text class="field__hint">{{ remark.length }}/50</text>
                    </view>
                    <input
                        v-model="remark"
                        class="field__input"
                        placeholder="例如：户外仪式，希望普通话 + 英文"
                        placeholder-class="field__ph"
                        :maxlength="50"
                    />
                </view>

                <!-- 替代出口：不抢主行动，也不编「同档期还有 2 位」 -->
                <view
                    hover-class="is-pressed"
                    class="alt"
                    aria-role="button"
                    aria-label="去看同一天其他可约的主持人"
                    @tap="goQuery"
                >
                    <text class="alt__text">不想等？去看看同一天其他可约的主持人</text>
                    <tn-icon name="right" :size="18" color="var(--c-brand-text)" />
                </view>
            </base-card>

            <!-- 协议：默认未勾，点提交时才报红（按钮不置灰） -->
            <view class="agree">
                <view
                    hover-class="is-pressed"
                    class="agree__cb"
                    :class="{ 'agree__cb--on': agreed }"
                    aria-role="checkbox"
                    :aria-checked="agreed"
                    aria-label="我已阅读并同意候补规则"
                    @tap="agreed = !agreed"
                >
                    <tn-icon v-if="agreed" name="check" :size="20" color="var(--c-brand-soft)" />
                </view>
                <text class="agree__text">
                    我已阅读并同意
                    <text class="agree__name" @tap.stop="scrollRules">《候补规则》</text>
                    ，知悉候补不锁定档期、不收取费用。
                </text>
            </view>
            <text v-if="errors.agree" class="err">{{ errors.agree }}</text>
        </view>

        <template #bottom>
            <bottom-bar>
                <text class="bar__note">排队位次以提交后为准</text>
                <template #actions>
                    <base-button
                        :text="submitting ? '提交中…' : '提交候补申请'"
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

    <!-- 套餐选择（接口必填，不能没有） -->
    <tn-popup
        v-model="packagePopup"
        open-direction="bottom"
        bg-color="transparent"
        radius="0"
        :safe-area-inset-bottom="true"
    >
        <view class="sheet">
            <view class="sheet__head">
                <text class="sheet__title">选择套餐</text>
                <view class="sheet__close" @tap="packagePopup = false">
                    <tn-icon name="close" :size="28" color="var(--c-ink-3)" />
                </view>
            </view>
            <text class="sheet__desc"
                >候补需要指定套餐才能排队，顾问会按这个套餐为你协调档期。</text
            >
            <scroll-view class="sheet__body" scroll-y>
                <view
                    v-for="p in packages"
                    :key="p.id"
                    hover-class="is-pressed"
                    class="opt"
                    :class="{ 'opt--on': packageId === p.id }"
                    @tap="pickPackage(p)"
                >
                    <view class="opt__l">
                        <text class="opt__name">{{ p.name }}</text>
                        <text v-if="p.duration_desc" class="opt__desc">{{ p.duration_desc }}</text>
                    </view>
                    <text class="opt__price">¥{{ p.price }}</text>
                    <tn-icon
                        v-if="packageId === p.id"
                        name="check"
                        :size="24"
                        color="var(--c-brand-text)"
                    />
                </view>
                <view v-if="!packages.length" class="sheet__empty">
                    <text class="sheet__empty-text">暂时没有可选套餐，请回到主持人页重新选择</text>
                </view>
            </scroll-view>
        </view>
    </tn-popup>

    <!-- 更换手机号（joinWaitlist 支持 mobile，改这里就是真的改接收通知的号码） -->
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
                    <tn-icon name="close" :size="28" color="var(--c-ink-3)" />
                </view>
            </view>
            <text class="sheet__desc">档期释放时会同时发服务号与短信到这个号码。</text>
            <input
                v-model="mobileDraft"
                class="sheet__input"
                type="number"
                :maxlength="11"
                placeholder="请输入 11 位手机号"
                placeholder-class="field__ph"
            />
            <text v-if="mobileErr" class="err err--pad">{{ mobileErr }}</text>
            <view class="sheet__foot">
                <base-button text="保存" variant="ink" size="lg" @click="saveMobile" />
            </view>
        </view>
    </tn-popup>

    <app-toast />
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { getStaffDetailV2, getStaffDetail, getStaffPackages } from '@/api/catalog'
import { joinWaitlist } from '@/api/catalog'
import { getMe } from '@/api/user'
import { navigate } from '@/utils/link'
import { useLockFn } from '@/hooks/useLockFn'
import { toast } from '@/composables/useToast'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { report } from '@/platform/report'

interface PkgOption {
    id: number
    name: string
    price: string
    duration_desc?: string
}

const loading = ref(true)
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: true }))
const error = computed(() => !!rawError.value)

const staffId = ref(0)
const serviceDate = ref('')
const staffName = ref('')
const staffAvatar = ref('')
const packages = ref<PkgOption[]>([])
const packageId = ref(0)
/** 档期是否已满：只用接口的 is_available 判定，缺字段时不宣称「已满」 */
const isFull = ref<boolean | null>(null)
const fullMessage = ref('')

const remark = ref('')
const agreed = ref(false)
const mobile = ref('')
const mobilePopup = ref(false)
const mobileDraft = ref('')
const mobileErr = ref('')
const packagePopup = ref(false)

const errors = reactive<{ package: string; mobile: string; agree: string }>({
    package: '',
    mobile: '',
    agree: ''
})

const submitting = ref(false)
const { isLock, lockFn } = useLockFn(async () => doSubmit())

const maskedMobile = computed(() => {
    const m = mobile.value
    if (!m || m.length !== 11) return ''
    return `${m.slice(0, 3)}****${m.slice(7)}`
})

const dateText = computed(() => {
    const d = serviceDate.value
    return d.length >= 10 ? d.slice(5).replace('-', '.') : '—'
})

/** 只拼真数据：年份与星期由日期算出，城市/分类没有字段就不写 */
const subText = computed(() => {
    const d = serviceDate.value
    if (d.length < 10) return ''
    const year = d.slice(0, 4)
    const week = '日一二三四五六'[new Date(`${d}T00:00:00`).getDay()]
    return `${year} 周${week}`
})

const fullFlagText = computed(() => {
    if (isFull.value === null) return '可加入候补'
    return isFull.value ? '档期已满 · 可加入候补' : '该日期仍有档位'
})

const packageName = computed(() => packages.value.find((p) => p.id === packageId.value)?.name || '')
const packagePrice = computed(
    () => packages.value.find((p) => p.id === packageId.value)?.price || ''
)

async function load() {
    loading.value = true
    rawError.value = null
    if (!staffId.value) {
        rawError.value = { code: -1, message: '缺少主持人信息' }
        loading.value = false
        return
    }
    try {
        // v2 一次拿齐 staff + packages + availability；失败降级两次 v1，不让整页挂掉
        try {
            const v2: any = await getStaffDetailV2(staffId.value, {
                date: serviceDate.value || undefined
            })
            staffName.value = v2?.staff?.name || ''
            staffAvatar.value = v2?.staff?.avatar || ''
            packages.value = (v2?.packages || []).map(normalizePkg)
            if (typeof v2?.availability?.is_available === 'boolean') {
                isFull.value = !v2.availability.is_available
                fullMessage.value = v2.availability.message || ''
            }
        } catch (e) {
            report('api_error', { endpoint: 'v2.staff', message: '候补页 v2 详情失败，降级 v1' })
            const [detail, pkgs] = await Promise.all([
                getStaffDetail({ id: staffId.value }).catch(() => null),
                getStaffPackages({ staff_id: staffId.value }).catch(() => [])
            ])
            const d: any = detail || {}
            staffName.value = d?.name || d?.staff?.name || ''
            staffAvatar.value = d?.avatar || d?.staff?.avatar || ''
            packages.value = (Array.isArray(pkgs) ? pkgs : []).map(normalizePkg)
        }

        // 预置套餐若在上游传了 packageId 且确实存在，就直接选中
        if (packageId.value && packages.value.some((p) => p.id === packageId.value)) {
            // 保留
        } else if (packages.value.length === 1) {
            packageId.value = packages.value[0].id
        } else {
            packageId.value = 0
        }
    } catch (e) {
        rawError.value = e
    } finally {
        loading.value = false
    }
}

function normalizePkg(p: any): PkgOption {
    return {
        id: Number(p?.id ?? p?.package_id ?? 0),
        name: String(p?.name ?? p?.package?.name ?? '套餐'),
        price: String(p?.price ?? p?.package?.price ?? ''),
        duration_desc: p?.duration_desc ?? p?.desc ?? ''
    }
}

async function loadMobile() {
    try {
        const me: any = await getMe()
        mobile.value = me?.user?.mobile || ''
    } catch {
        // 拿不到手机号不算错误：可手动填写，但提交时会拦
    }
}

function openPackage() {
    errors.package = ''
    packagePopup.value = true
}

function pickPackage(p: PkgOption) {
    packageId.value = p.id
    errors.package = ''
    packagePopup.value = false
}

function openMobile() {
    mobileDraft.value = mobile.value
    mobileErr.value = ''
    mobilePopup.value = true
}

function saveMobile() {
    const v = mobileDraft.value.trim()
    if (!/^1\d{10}$/.test(v)) {
        mobileErr.value = '请输入 11 位手机号'
        return
    }
    mobile.value = v
    mobilePopup.value = false
}

function scrollRules() {
    uni.pageScrollTo({ selector: '#rules', duration: 240 })
}

function goQuery() {
    navigate({ path: '/pages/query/query' })
}

function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'login') {
        navigate({ path: '/pages/login/login' })
        return
    }
    if (!staffId.value) {
        uni.navigateBack()
        return
    }
    load()
}

/** 校验顺序固定：①套餐 → ②手机号 → ③协议（备选日期已不做，无 ④） */
function validate(): boolean {
    errors.package = packageId.value ? '' : '请先选一个套餐'
    errors.mobile = /^1\d{10}$/.test(mobile.value) ? '' : '请输入 11 位手机号'
    errors.agree = agreed.value ? '' : '请先勾选《候补规则》再提交'
    if (errors.package) {
        packagePopup.value = true
        return false
    }
    return !errors.mobile && !errors.agree
}

async function submit() {
    if (isLock.value) return
    if (!validate()) {
        if (errors.mobile && !errors.package) mobilePopup.value = true
        return
    }
    submitting.value = true
    try {
        await lockFn()
    } finally {
        submitting.value = false
    }
}

async function doSubmit() {
    try {
        const res: any = await joinWaitlist({
            staff_id: staffId.value,
            date: serviceDate.value,
            package_id: packageId.value,
            remark: remark.value.trim() || undefined,
            mobile: mobile.value || undefined
        })
        // 后端返回 { success, message, waitlist_id }，不是 null
        if (res && res.success === false) {
            toast.error(res.message || '申请没提交上去，候补名额还留着，再试一次')
            return
        }
        toast.success('提交成功，有档期会通知你')
        uni.redirectTo({
            url: '/packages/pkg-order/pages/waitlist/waitlist',
            fail: () => uni.navigateBack()
        })
    } catch {
        // 请求层已提示；这里给可重试的明确文案，避免用户以为名额丢了
        toast.error('申请没提交上去，候补名额还留着，再试一次')
    }
}

onLoad((options: any) => {
    staffId.value =
        Number(options?.hostId || options?.staffId || options?.staff_id || options?.id || 0) || 0
    serviceDate.value = String(options?.date || '')
    packageId.value = Number(options?.packageId || options?.package_id || 0) || 0
    load()
    loadMobile()
})
</script>

<style lang="scss" scoped>
.pad {
    padding: 0 42rpx;
}
.sk {
    padding-top: var(--sp-3);
}
.wl {
    padding: 0 42rpx calc(var(--sp-4) + var(--safe-bottom));
}

/* ---------- WL 头（深色） ---------- */
.head {
    margin-top: var(--sp-2);
    border-radius: var(--r-md);
    padding: var(--sp-4);
    background: var(--c-ink);
    border: 1rpx solid var(--c-ink-2);

    &__flag {
        display: inline-flex;
        align-items: center;
        gap: var(--sp-1);
        height: 40rpx;
        padding: 0 var(--sp-2);
        border-radius: var(--r-xs);
        background: var(--c-ink-2);
        border: 1rpx solid var(--c-ink-3);
    }

    &__flag-dot {
        width: 8rpx;
        height: 8rpx;
        border-radius: var(--r-pill);
        background: var(--c-brand-soft);
        flex: none;
    }

    &__flag-text {
        font-size: var(--fs-xs);
        letter-spacing: 0.06em;
        color: var(--c-brand-soft);
    }

    &__main {
        margin-top: var(--sp-3);
    }

    &__date {
        display: block;
        font-family: var(--font-serif);
        font-size: var(--fs-52);
        line-height: var(--lh-none);
        color: var(--c-on-dark);
    }

    &__sub {
        display: block;
        margin-top: var(--sp-2);
        font-size: var(--fs-xs);
        letter-spacing: 0.04em;
        color: var(--c-on-dark-2);
    }

    &__host {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
        margin-top: var(--sp-3);
        padding-top: var(--sp-3);
        border-top: 1rpx solid var(--c-ink-2);
    }

    &__avatar {
        width: 56rpx;
        height: 56rpx;
        border-radius: var(--r-xs);
        flex: none;
        background: var(--c-ink-2);

        &--text {
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--font-serif);
            font-size: 24rpx;
            color: var(--c-brand-soft);
        }
    }

    &__host-name {
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        color: var(--c-on-dark);
    }

    &__host-pkg {
        margin-left: auto;
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }
}

/* ---------- 套餐行（接口必填） ---------- */
.pkg {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-3);
    margin-top: var(--sp-3);
    padding: var(--sp-3) var(--sp-4);
    border-radius: var(--r-md);
    background: var(--c-surface);
    border: 1rpx solid var(--c-line);

    &--err {
        border-color: var(--c-danger);
    }

    &__l {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
        flex: none;
    }

    &__label {
        font-size: var(--fs-md);
        color: var(--c-ink);
    }

    &__req {
        font-size: var(--fs-xs);
        color: var(--c-danger);
        border: 1rpx solid var(--c-danger-border);
        background: var(--c-danger-bg);
        border-radius: var(--r-xs);
        padding: 0 var(--sp-1);
        line-height: var(--lh-tight);
    }

    &__r {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: var(--sp-1);
    }

    &__value {
        font-size: var(--fs-md);
        color: var(--c-ink);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    &__ph {
        font-size: var(--fs-md);
        color: var(--c-ink-3);
    }

    &__price {
        font-family: var(--font-serif);
        font-size: var(--fs-md);
        color: var(--c-ink-2);
    }
}

/* ---------- 三步规则 ---------- */
.rules {
    margin-top: var(--sp-3);
    padding: var(--sp-4);
    border-radius: var(--r-lg);
    background: var(--c-surface);
    border: 1rpx solid var(--c-line);

    &__note {
        display: block;
        margin-top: var(--sp-2);
        font-size: var(--fs-xs);
        line-height: var(--lh-loose);
        color: var(--c-ink-3);
    }
}

.steps3 {
    display: flex;
    gap: var(--sp-1);

    &__i {
        flex: 1;
        min-width: 0;
        height: 56rpx;
        border-radius: var(--r-xs);
        background: var(--c-brand-bg);
        border: 1rpx solid var(--c-brand-soft);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--sp-1);
        padding: 0 var(--sp-1);
    }

    &__n {
        font-family: var(--font-serif);
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
        flex: none;
    }

    &__t {
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
        overflow: hidden;
        white-space: nowrap;
    }
}

/* ---------- 字段 ---------- */
.blk {
    margin-top: var(--sp-3);
}

.field {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-3);
    min-height: 88rpx;
    border-bottom: 1rpx solid var(--c-line);

    &--stack {
        display: block;
        padding: var(--sp-2) 0 var(--sp-3);
        border-bottom: 0;
    }

    &__row {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
    }

    &__k {
        font-size: var(--fs-md);
        color: var(--c-ink-2);
        flex: none;
    }

    &__v {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
    }

    &__val {
        font-size: var(--fs-md);
        color: var(--c-ink);
    }

    &__link {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        text-decoration: underline;
    }

    &__hint {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__input {
        width: 100%;
        height: 72rpx;
        margin-top: var(--sp-1);
        font-size: var(--fs-md);
        color: var(--c-ink);
        text-align: left;
    }

    &__ph {
        color: var(--c-ink-3);
    }
}

/* ---------- 替代出口 ---------- */
.alt {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);
    margin-top: var(--sp-2);
    padding-top: var(--sp-3);
    border-top: 1rpx solid var(--c-line);

    &__text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-brand-text);
    }
}

/* ---------- 协议 ---------- */
.agree {
    display: flex;
    align-items: flex-start;
    gap: var(--sp-2);
    margin-top: var(--sp-3);

    &__cb {
        width: 32rpx;
        height: 32rpx;
        border-radius: var(--r-xs);
        border: 1rpx solid var(--c-control-border);
        background: var(--c-surface);
        flex: none;
        margin-top: 4rpx;
        display: flex;
        align-items: center;
        justify-content: center;

        &--on {
            background: var(--c-ink);
            border-color: var(--c-ink);
        }
    }

    &__text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        line-height: var(--lh-loose);
        color: var(--c-ink-3);
    }

    &__name {
        color: var(--c-ink-2);
        font-weight: var(--fw-medium);
    }
}

.err {
    display: block;
    margin-top: var(--sp-1);
    font-size: var(--fs-xs);
    line-height: var(--lh-body);
    color: var(--c-danger);

    &--pad {
        padding: 0 var(--sp-4);
    }
}

/* ---------- 底部栏 ---------- */
.bar__note {
    font-size: var(--fs-xs);
    color: var(--c-ink-3);
}

/* ---------- 弹层 ---------- */
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
        padding: var(--sp-5) 0;
        text-align: center;
    }

    &__empty-text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
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

    &__foot {
        margin-top: var(--sp-4);
    }
}

.opt {
    display: flex;
    align-items: center;
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

    &__l {
        flex: 1;
        min-width: 0;
    }

    &__name {
        display: block;
        font-size: var(--fs-md);
        color: var(--c-ink);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    &__desc {
        display: block;
        margin-top: 2rpx;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__price {
        font-family: var(--font-serif);
        font-size: var(--fs-md);
        color: var(--c-ink-2);
        flex: none;
    }
}
</style>
