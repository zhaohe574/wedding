<template>
    <view class="page-wrap booking">
        <!-- 步骤条 -->
        <view class="steps">
            <view
                v-for="(s, i) in STEPS"
                :key="i"
                class="steps__item"
                :class="{ 'is-active': step >= i + 1, 'is-current': step === i + 1, 'is-complete': step > i + 1 }"
            >
                <view class="steps__dot">
                    <text class="steps__num">{{ i + 1 }}</text>
                </view>
                <text class="steps__label">{{ s }}</text>
            </view>
        </view>

        <view v-if="loading" class="page-pad">
            <base-skeleton :rows="3" />
        </view>

        <!-- 缺参数属于「不可重试」错误：重试只会用 staff_id=0 再失败一次 -->
        <view v-else-if="error && !staffId" class="page-pad">
            <view class="fallback">
                <text class="fallback__title">预约信息不完整</text>
                <text class="fallback__desc">缺少服务人员参数，请返回重新选择</text>
                <base-button text="返回上一页" variant="ghost" size="md" :block="false" @click="goBack" />
            </view>
        </view>

        <base-empty v-else-if="error" status="error" error-kind="network" fixed @retry="load" />

        <template v-else>
            <!-- 上下文摘要：任何时候都能看到约的是哪天、什么套餐、谁服务 -->
            <view class="brief page-pad">
                <view class="brief__item">
                    <text class="brief__label">日期</text>
                    <text class="brief__value">{{ date || '未选择' }}</text>
                </view>
                <view class="brief__item">
                    <text class="brief__label">套餐</text>
                    <text class="brief__value ellipsis">{{ currentPackageName || '未选择' }}</text>
                </view>
                <view class="brief__item">
                    <text class="brief__label">人员</text>
                    <text class="brief__value ellipsis">{{ staffName || '平台安排' }}</text>
                </view>
            </view>
            <!-- 第一步：套餐 + 附加项 -->
            <view v-if="step === 1" class="page-pad panel">
                <section-header title="选择套餐" :subtitle="`${packages.length} 个可选`" />
                <view class="pkg-list">
                    <base-card
                        v-for="p in packages"
                        :key="p.package_id || p.id"
                        clickable
                        compact
                        :selected="packageId === (p.package_id || p.id)"
                        @tap="selectPackage(p)"
                    >
                        <view class="pkg">
                            <view class="pkg__body">
                                <text class="pkg__name ellipsis">{{ p.package?.name || p.name }}</text>
                                <text v-if="p.duration_desc" class="pkg__meta">{{ p.duration_desc }}</text>
                            </view>
                            <price size="md" :content="p.price ?? p.package?.price ?? 0" />
                        </view>
                    </base-card>
                </view>

                <template v-if="addons.length">
                    <section-header title="附加服务" subtitle="可多选，也可不选" />
                    <base-card :flush="true">
                        <view hover-class="is-pressed"
                            v-for="(a, i) in addons"
                            :key="a.id"
                            class="addon"
                            :class="{ 'addon--last': i === addons.length - 1 }"
                            @tap="toggleAddon(a.id)"
                        >
                            <view class="addon__body">
                                <text class="addon__name">{{ a.name }}</text>
                                <text v-if="a.description" class="addon__desc ellipsis">{{ a.description }}</text>
                            </view>
                            <price size="sm" :content="a.price ?? 0" />
                            <view class="addon__check" :class="{ 'is-on': selectedAddons.includes(a.id) }">
                                <u-icon
                                    v-if="selectedAddons.includes(a.id)"
                                    name="checkmark"
                                    :size="24"
                                    color="var(--c-on-brand)"
                                />
                            </view>
                        </view>
                    </base-card>
                </template>
            </view>

            <!-- 第二步：角色选人 -->
            <view v-else-if="step === 2" class="page-pad panel">
                <section-header title="关联服务人员" subtitle="不选则由平台安排" />
                <view v-if="!roleConfigs.length">
                    <base-empty status="empty" empty-text="该套餐无需额外人员" />
                </view>
                <view v-else class="roles">
                    <base-card v-for="r in roleConfigs" :key="r.role_key">
                        <view class="role">
                            <view class="role__body">
                                <text class="role__name">{{ r.role_label }}</text>
                                <text class="role__hint">{{ roleSummary(r.role_key) }}</text>
                            </view>
                            <base-button
                                :text="roleSelected[r.role_key] ? '更换' : '去选择'"
                                variant="ghost"
                                size="sm"
                                :block="false"
                                @click="openRolePicker(r)"
                            />
                        </view>
                    </base-card>
                </view>
            </view>

            <!-- 第三步：确认 -->
            <view v-else class="page-pad panel">
                <section-header title="联系人信息" />
                <base-card>
                    <view class="form">
                        <base-input
                            v-model="form.contact_name"
                            label="联系人"
                            placeholder="请输入姓名"
                            maxlength="20"
                            required
                            :error="errors.contact_name"
                            @blur="validateField('contact_name')"
                        />
                        <base-input
                            v-model="form.contact_mobile"
                            label="手机号"
                            type="number"
                            placeholder="请输入手机号"
                            maxlength="11"
                            required
                            :error="errors.contact_mobile"
                            @blur="validateField('contact_mobile')"
                        />
                        <base-input
                            v-model="form.service_address"
                            label="服务地址"
                            placeholder="请输入详细地址"
                            required
                            :error="errors.service_address"
                            @blur="validateField('service_address')"
                        />
                        <base-input v-model="form.remark" label="备注" placeholder="选填" />
                    </view>
                </base-card>

                <section-header title="费用明细" />
                <base-card>
                    <view class="kv">
                        <text class="kv__k">服务套餐</text>
                        <text class="kv__v">¥{{ money(quote.service_amount) }}</text>
                    </view>
                    <view v-if="Number(quote.addon_amount) > 0" class="kv">
                        <text class="kv__k">附加服务</text>
                        <text class="kv__v">¥{{ money(quote.addon_amount) }}</text>
                    </view>
                    <view class="kv kv--strong">
                        <text class="kv__k">合计</text>
                        <text class="kv__v kv__v--brand">¥{{ money(quote.total_amount) }}</text>
                    </view>
                    <view v-if="quote.payment_mode_desc" class="kv">
                        <text class="kv__k">支付方式</text>
                        <text class="kv__v">{{ quote.payment_mode_desc }}</text>
                    </view>
                    <view v-if="Number(quote.deposit_amount) > 0" class="kv">
                        <text class="kv__k">定金</text>
                        <text class="kv__v">¥{{ money(quote.deposit_amount) }}</text>
                    </view>
                </base-card>

                <view v-if="quoteState === 'loading'" class="quote-state">
                    <text class="quote-state__text">正在获取报价…</text>
                </view>
                <view v-else-if="quoteState === 'failed' || errors.quote" class="quote-state quote-state--fail">
                    <text class="quote-state__text">报价未生成，无法提交</text>
                    <base-button text="重新获取报价" variant="ghost" size="sm" :block="false" @click="runPreview" />
                </view>

                <view v-if="validations.length" class="warnings">
                    <view v-for="(v, i) in validations" :key="i" class="warnings__item">
                        <u-icon name="error-circle" :size="24" color="var(--c-danger)" />
                        <text class="warnings__text">{{ v.message }}</text>
                    </view>
                </view>
            </view>

            <view class="panel__space" />
        </template>

        <!-- 底部操作 -->
        <view v-if="!loading && !error" class="action-bar">
            <view v-if="step > 1" class="action-bar__back" @tap="prevStep">
                <text class="action-bar__back-text">上一步</text>
            </view>
            <view class="action-bar__main">
                <text v-if="step === 3 && submitHint" class="action-bar__hint">{{ submitHint }}</text>
                <base-button
                    v-if="step < 3"
                    text="下一步"
                    variant="brand"
                    size="md"
                    @click="nextStep"
                />
                <base-button
                    v-else
                    text="提交订单"
                    variant="brand"
                    size="md"
                    :loading="submitting"
                    @click="handleSubmit"
                />
            </view>
        </view>

        <!-- 角色候选人弹层 -->
        <view v-if="pickerVisible" class="picker-mask" @tap="closePicker">
            <view class="picker" @tap.stop>
                <view class="picker__head">
                    <text class="picker__title">{{ pickerTitle }}</text>
                    <view hover-class="is-pressed" class="picker__close" aria-label="关闭" aria-role="button" @tap="closePicker">
                        <u-icon name="close" :size="32" color="var(--c-ink-3)" />
                    </view>
                </view>

                <scroll-view class="picker__body" scroll-y>
                    <!-- 候选人 -->
                    <template v-if="!pickerStage.staffId">
                        <view v-if="!pickerCandidates.length" class="picker__empty">
                            <text class="picker__empty-text">暂无可选人员</text>
                        </view>
                        <view hover-class="is-pressed"
                            v-for="c in pickerCandidates"
                            :key="c.id"
                            class="cand"
                            @tap="chooseCandidate(c)"
                        >
                            <image class="cand__avatar" :src="imageUrl(c.avatar)" mode="aspectFill" />
                            <view class="cand__body">
                                <text class="cand__name">{{ c.name }}</text>
                                <text class="cand__meta">★ {{ c.rating || '-' }} · {{ c.order_count || 0 }} 单</text>
                            </view>
                            <u-icon name="arrow-right" :size="24" color="var(--c-ink-3)" />
                        </view>
                    </template>

                    <!-- 候选人的套餐 -->
                    <template v-else>
                        <view hover-class="is-pressed" class="picker__back" @tap="pickerStage.staffId = 0">
                            <u-icon name="arrow-left" :size="24" color="var(--c-ink-3)" />
                            <text class="picker__back-text">返回人员列表</text>
                        </view>
                        <view hover-class="is-pressed"
                            v-for="p in pickerPackages"
                            :key="p.package_id || p.id"
                            class="cand"
                            @tap="confirmRole(p)"
                        >
                            <view class="cand__body">
                                <text class="cand__name">{{ p.package?.name || p.name }}</text>
                                <text v-if="p.duration_desc" class="cand__meta">{{ p.duration_desc }}</text>
                            </view>
                            <price size="sm" :content="p.price ?? p.package?.price ?? 0" />
                        </view>
                    </template>
                </scroll-view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
/**
 * 预约配置：固定 3 步。
 *
 * 老端步骤数是动态的（1 + 附加项数 + 角色数 + 1），典型配置下要走 7 步。
 * 这里把「套餐 + 附加项」合成一页、「角色选人」一页、「确认」一页，恒定 3 步。
 */
import { computed, reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import {
    getStaffDetail,
    getStaffPackages,
    getStaffAddons,
    getBookingRoleCandidates
} from '@/api/catalog'
import { previewOrder, previewBooking, createOrder } from '@/api/order'
import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'
import { navigate } from '@/utils/link'
import { report } from '@/platform/report'
import { formatMoney } from '@shared/utils/format'

const STEPS = ['套餐', '人员', '确认']

const appStore = useAppStore()
const userStore = useUserStore()

const step = ref(1)
const loading = ref(true)
const error = ref(false)
const submitting = ref(false)

const staffId = ref(0)
const date = ref('')
const packages = ref<any[]>([])
const addons = ref<any[]>([])
const roleConfigs = ref<any[]>([])

const packageId = ref(0)
const selectedAddons = ref<number[]>([])
const roleSelected = reactive<Record<string, { staff_id: number; package_id: number; name: string; price: number }>>({})

const quote = ref<any>({})
const validations = ref<Array<{ message: string; level: string }>>([])
const quoteHash = ref('')

const form = reactive({
    contact_name: '',
    contact_mobile: '',
    service_address: '',
    remark: ''
})

// 角色选择弹层
const pickerVisible = ref(false)
const pickerRole = ref<any>(null)
const pickerCandidates = ref<any[]>([])
const pickerPackages = ref<any[]>([])
const pickerStage = reactive({ staffId: 0 })

const pickerTitle = computed(() => pickerRole.value?.role_label || '选择人员')

const staffName = ref('')
const quoteState = ref<'idle' | 'loading' | 'ready' | 'failed'>('idle')
const errors = reactive<Record<string, string>>({})

const currentPackageName = computed(() => {
    const p = packages.value.find((x: any) => Number(x.package_id || x.id) === Number(packageId.value))
    return p ? p.package?.name || p.name || '' : ''
})

/** 不能提交时，把原因说在按钮上方，而不是让按钮灰着不说话 */
const submitHint = computed(() => {
    if (!form.contact_name.trim()) return '还需填写联系人姓名'
    if (!/^1\d{10}$/.test(form.contact_mobile.trim())) return '还需填写正确的手机号'
    if (!form.service_address.trim()) return '还需填写服务地址'
    if (quoteState.value === 'loading') return '正在获取报价…'
    if (!quoteHash.value) return '报价未生成，暂不能提交'
    return ''
})

function imageUrl(url?: string) {
    if (!url) return ''
    if (/^https?:/.test(url)) return url
    const domain = (appStore.config && appStore.config.domain) || ''
    return domain ? `${domain}${url}` : url
}

function money(v: any) {
    return formatMoney(v as any)
}

/** 报价已失效：套餐/附加项/人员任一变更后，旧 quote_hash 不能再用于下单 */
function invalidateQuote() {
    quoteHash.value = ''
    quote.value = {}
    validations.value = []
    quoteState.value = 'idle'
}

function validateField(key: 'contact_name' | 'contact_mobile' | 'service_address') {
    if (key === 'contact_name') {
        const v = form.contact_name.trim()
        if (!v) errors.contact_name = '请填写联系人姓名'
        else if (v.length < 2) errors.contact_name = '姓名至少 2 个字'
        else delete errors.contact_name
    }
    if (key === 'contact_mobile') {
        const v = form.contact_mobile.trim()
        if (!v) errors.contact_mobile = '请填写手机号'
        else if (!/^1\d{10}$/.test(v)) errors.contact_mobile = '手机号格式不正确'
        else delete errors.contact_mobile
    }
    if (key === 'service_address') {
        const v = form.service_address.trim()
        if (!v) errors.service_address = '请填写服务地址'
        else delete errors.service_address
    }
}

/** 提交前全量校验：错误贴在字段下方，与按钮同屏可见 */
function validate() {
    validateField('contact_name')
    validateField('contact_mobile')
    validateField('service_address')
    if (!quoteHash.value) errors.quote = '报价未生成，请点击「重新获取报价」'
    else delete errors.quote
    return Object.keys(errors).length === 0
}

function goBack() {
    if (getCurrentPages().length > 1) uni.navigateBack()
    else uni.switchTab({ url: '/pages/index/index' })
}

function selectPackage(p: any) {
    const next = p.package_id || p.id
    if (Number(next) === Number(packageId.value)) return
    packageId.value = next
    invalidateQuote()
}

function toggleAddon(id: number) {
    const i = selectedAddons.value.indexOf(id)
    if (i >= 0) selectedAddons.value.splice(i, 1)
    else selectedAddons.value.push(id)
    invalidateQuote()
}

function roleSummary(roleKey: string) {
    const sel = roleSelected[roleKey]
    if (!sel) return '未选择，由平台安排'
    return `${sel.name} · ¥${money(sel.price)}`
}

/** 组装后端要求的参数（字段名来自 BookingFlowService 与老端 toBookingOrderParams） */
function buildParams() {
    const params: Record<string, any> = {
        staff_id: staffId.value,
        package_id: packageId.value,
        date: date.value
    }
    if (selectedAddons.value.length) params.addon_ids = [...selectedAddons.value]

    for (const key of Object.keys(roleSelected)) {
        const sel = roleSelected[key]
        if (!sel) continue
        params[`${key}_staff_id`] = sel.staff_id
        params[`${key}_package_id`] = sel.package_id
    }
    return params
}

async function load() {
    if (!staffId.value) {
        error.value = true
        loading.value = false
        return
    }
    loading.value = true
    error.value = false
    try {
        const detail = await getStaffDetail({ id: staffId.value, date: date.value || undefined })
        if (!detail || !detail.id) throw new Error('empty')

        staffName.value = detail.name || ''
        packages.value = Array.isArray(detail.packages) ? detail.packages : []
        addons.value = Array.isArray(detail.addons) ? detail.addons : []
        roleConfigs.value = Array.isArray(detail.related_role_configs) ? detail.related_role_configs : []

        if (packages.value.length) {
            const preferred = packages.value.find((p: any) => Number(p.package_id || p.id) === packageId.value)
            packageId.value = preferred ? packageId.value : packages.value[0].package_id || packages.value[0].id
        }

        // 附加项默认全不选
        selectedAddons.value = []
    } catch {
        error.value = true
        report('api_error', { endpoint: 'staff.detail', message: '预约配置加载失败' })
    } finally {
        loading.value = false
    }
}

async function openRolePicker(role: any) {
    if (!userStore.isLogin) {
        navigate({ path: '/pages/login/login' })
        return
    }
    pickerRole.value = role
    pickerStage.staffId = 0
    pickerPackages.value = []
    pickerCandidates.value = []
    pickerVisible.value = true
    try {
        const list = await getBookingRoleCandidates({
            staff_id: staffId.value,
            role_key: role.role_key,
            date: date.value || undefined
        })
        pickerCandidates.value = Array.isArray(list) ? list : []
    } catch {
        pickerCandidates.value = []
    }
}

async function chooseCandidate(c: any) {
    pickerStage.staffId = c.id
    pickerPackages.value = []
    try {
        const list = await getStaffPackages({ staff_id: c.id })
        pickerPackages.value = Array.isArray(list) ? list : []
      } catch {
        pickerPackages.value = []
    }
}

function confirmRole(p: any) {
    const role = pickerRole.value
    if (!role) return
    roleSelected[role.role_key] = {
        staff_id: pickerStage.staffId,
        package_id: p.package_id || p.id,
        name: (pickerCandidates.value.find((c) => c.id === pickerStage.staffId) || {}).name || '',
        price: Number(p.price ?? p.package?.price ?? 0)
    }
    invalidateQuote()
    closePicker()
}

function closePicker() {
    pickerVisible.value = false
}

function nextStep() {
    if (step.value === 1 && !packageId.value) {
        uni.showToast({ title: '请选择套餐', icon: 'none' })
        return
    }
    step.value += 1
    if (step.value === 3) runPreview()
}

function prevStep() {
    if (step.value > 1) step.value -= 1
}

async function runPreview() {
    quoteState.value = 'loading'
    try {
        // 优先 v2：一次拿到报价 + 结构化校验 + 报价指纹
        try {
            const v2 = await previewBooking(buildParams())
            quote.value = v2?.quote || {}
            quoteHash.value = String(v2?.quote_hash || '')
            validations.value = Array.isArray(v2?.validations) ? v2.validations : []
            if (v2?.contact_default) {
                if (!form.contact_name) form.contact_name = v2.contact_default.name || ''
                if (!form.contact_mobile) form.contact_mobile = v2.contact_default.mobile || ''
            }
            // 没有指纹等于报价没算出来，不能当成功
            quoteState.value = quoteHash.value ? 'ready' : 'failed'
            return
        } catch {
            // v2 未上线：降级 v1
        }

        const legacy: any = await previewOrder(buildParams())
        quote.value = legacy || {}
        quoteHash.value = String(legacy?.quote_hash || '')
        validations.value = Array.isArray(legacy?.validations) ? legacy.validations : []
        if (legacy?.contact_default) {
            if (!form.contact_name) form.contact_name = legacy.contact_default.name || ''
            if (!form.contact_mobile) form.contact_mobile = legacy.contact_default.mobile || ''
        }
        quoteState.value = quoteHash.value ? 'ready' : 'failed'
    } catch (e: any) {
        quoteState.value = 'failed'
        validations.value = [{ message: e?.message || '价格获取失败，请稍后重试', level: 'error' }]
    }
}

async function handleSubmit() {
    if (submitting.value) return
    if (!validate()) {
        const title = errors.contact_name
            ? '请完善联系人信息'
            : errors.quote
              ? '报价未生成，暂不能提交'
              : '请检查填写内容'
        uni.showToast({ title, icon: 'none' })
        return
    }
    submitting.value = true
    try {
        const params: Record<string, any> = {
            ...buildParams(),
            contact_name: form.contact_name.trim(),
            contact_mobile: form.contact_mobile.trim(),
            service_address: form.service_address.trim(),
            quote_hash: quoteHash.value
        }
        if (form.remark.trim()) params.remark = form.remark.trim()

        const res = await createOrder(params as any)
        const orderId = Number((res as any)?.order_id || (res as any)?.id || 0)
        uni.showToast({ title: '订单已提交', icon: 'success' })
        setTimeout(() => {
            if (orderId) {
                uni.redirectTo({ url: `/packages/pkg-order/pages/detail/detail?id=${orderId}` })
            } else {
                uni.switchTab({ url: '/pages/order/order' })
            }
        }, 600)
    } catch (e: any) {
        // 请求层已 toast；档期冲突类错误返回上一步让用户改日期
        if (e?.needReselect) step.value = 1
    } finally {
        submitting.value = false
    }
}

onLoad((options: any) => {
    staffId.value = Number(options?.staff_id || 0)
    packageId.value = Number(options?.package_id || 0)
    date.value = options?.date ? String(options.date) : ''
    if (!staffId.value) {
        error.value = true
        loading.value = false
        return
    }
    load()
})
</script>

<style lang="scss" scoped>
.booking {
    min-height: 100vh;
    padding-bottom: calc(180rpx + var(--safe-bottom));
    background: var(--c-bg);
}

.brief {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    padding-top: var(--sp-3);
    padding-bottom: var(--sp-3);

    &__item {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__label {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__value {
        font-size: var(--fs-xs);
        color: var(--c-ink);
        max-width: 100%;
    }
}

.fallback {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--sp-2);
    padding: var(--sp-6) var(--sp-4);
    text-align: center;

    &__title {
        font-size: var(--fs-lg);
        color: var(--c-ink);
    }

    &__desc {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        line-height: 1.6;
    }
}

.steps {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);
    padding: var(--sp-3) var(--sp-4);
    background: var(--c-surface);
    border-bottom: 1rpx solid var(--c-line);

    &__item {
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 1;
        gap: var(--sp-1);
        min-width: 0;
    }

    &__dot {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        width: 40rpx;
        height: 40rpx;
        box-sizing: border-box;
        border: 1rpx solid var(--c-line-strong);
        border-radius: 50%;
        background: var(--c-neutral-bg);
    }

    &__num {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        line-height: 1;
    }

    &__item.is-current &__dot {
        border-color: var(--c-ink);
        background: var(--c-ink);
    }

    &__item.is-current &__num {
        color: var(--c-on-dark);
    }

    &__item.is-complete &__dot {
        border-color: var(--c-brand);
        background: var(--c-brand);
    }

    &__item.is-complete &__num {
        color: var(--c-on-brand);
    }

    &__label {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        white-space: nowrap;
    }

    &__item.is-active &__label {
        color: var(--c-ink);
        font-weight: 500;
    }
}

.panel {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
    padding-top: var(--sp-3);

    &__space {
        height: var(--sp-5);
    }
}

.pkg-list {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
}

.pkg {
    display: flex;
    align-items: center;
    gap: var(--sp-3);

    &__body {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
        min-width: 0;
    }

    &__name {
        font-size: var(--fs-md);
        font-weight: 500;
        color: var(--c-ink);
        line-height: 1.5;
    }

    &__meta {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        line-height: 1.5;
    }
}

.addon {
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    min-height: 104rpx;
    box-sizing: border-box;
    padding: var(--sp-3) var(--sp-4);
    border-bottom: 1rpx solid var(--c-line);

    &--last {
        border-bottom: none;
    }

    &__body {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
        min-width: 0;
    }

    &__name {
        font-size: var(--fs-md);
        color: var(--c-ink);
        line-height: 1.5;
    }

    &__desc {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        line-height: 1.5;
    }

    &__check {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        width: 36rpx;
        height: 36rpx;
        box-sizing: border-box;
        border: 1rpx solid var(--c-line-strong);
        border-radius: var(--r-xs);

        &.is-on {
            background: var(--c-brand);
            border-color: var(--c-brand);
        }
    }
}

.roles {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
}

.role {
    display: flex;
    align-items: center;
    gap: var(--sp-3);

    &__body {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
        min-width: 0;
    }

    &__name {
        font-size: var(--fs-md);
        font-weight: 500;
        color: var(--c-ink);
        line-height: 1.5;
    }

    &__hint {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        line-height: 1.5;
        overflow-wrap: anywhere;
    }
}

.form {
    /* 表单基座由 base-input 承担，这里只处理容器内的最后一条分隔线 */
    :deep(.field:last-child) {
        border-bottom: none;
    }
}

.kv {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--sp-3);
    padding: var(--sp-2) 0;

    &__k {
        flex-shrink: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        line-height: 1.6;
    }

    &__v {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink);
        line-height: 1.6;
        text-align: right;
        overflow-wrap: anywhere;

        &--brand {
            font-size: var(--fs-lg);
            font-weight: 500;
            color: var(--c-brand-text);
        }
    }

    &--strong {
        margin-top: var(--sp-2);
        padding-top: var(--sp-3);
        border-top: 1rpx solid var(--c-line);
    }
}

.warnings {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
    margin-top: var(--sp-2);

    &__item {
        display: flex;
        align-items: flex-start;
        gap: var(--sp-1);
        padding: var(--sp-2) var(--sp-3);
        border-radius: var(--r-sm);
        background: var(--c-danger-bg);
    }

    &__text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        color: var(--c-danger);
        line-height: 1.6;
        overflow-wrap: anywhere;
    }
}

.quote-state {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-3);
    margin-top: var(--sp-2);
    padding: var(--sp-2) var(--sp-3);
    border-radius: var(--r-sm);
    background: var(--c-surface-2);

    &__text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
        line-height: 1.5;
    }

    &--fail {
        background: var(--c-danger-bg);

        .quote-state__text {
            color: var(--c-danger);
        }
    }
}

.action-bar {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    padding: var(--sp-3) var(--sp-4);
    padding-bottom: calc(var(--sp-3) + var(--safe-bottom));
    background: var(--c-surface);
    border-top: 1rpx solid var(--c-line);

    &__back {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        min-width: 88rpx;
        min-height: 88rpx;
        margin: -4rpx 0;
    }

    &__back-text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &__main {
        flex: 1;
        min-width: 0;
    }

    &__hint {
        display: block;
        margin-bottom: var(--sp-1);
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        text-align: center;
    }
}

.picker-mask {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 200;
    display: flex;
    align-items: flex-end;
    background: var(--c-mask);
}

.picker {
    display: flex;
    flex-direction: column;
    width: 100%;
    max-height: 70vh;
    padding-bottom: var(--safe-bottom);
    border-radius: var(--r-lg) var(--r-lg) 0 0;
    background: var(--c-surface);

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
        min-height: 104rpx;
        box-sizing: border-box;
        padding: var(--sp-1) var(--sp-2) var(--sp-1) var(--sp-4);
        border-bottom: 1rpx solid var(--c-line);
    }

    &__title {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-lg);
        font-weight: 500;
        color: var(--c-ink);
        line-height: 1.5;
        overflow-wrap: anywhere;
    }

    &__close {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        width: 88rpx;
        height: 88rpx;
    }

    &__body {
        max-height: 56vh;
    }

    &__empty {
        padding: var(--sp-6);
        text-align: center;
    }

    &__empty-text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__back {
        display: flex;
        align-items: center;
        gap: var(--sp-1);
        min-height: 88rpx;
        box-sizing: border-box;
        padding: var(--sp-2) var(--sp-4);
        background: var(--c-surface-2);
        border-bottom: 1rpx solid var(--c-line);
    }

    &__back-text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }
}

.cand {
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    min-height: 104rpx;
    box-sizing: border-box;
    padding: var(--sp-3) var(--sp-4);
    border-bottom: 1rpx solid var(--c-line);

    &__avatar {
        flex-shrink: 0;
        width: 80rpx;
        height: 80rpx;
        border-radius: 50%;
        background: var(--c-neutral-bg);
    }

    &__body {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
        min-width: 0;
    }

    &__name {
        font-size: var(--fs-md);
        color: var(--c-ink);
        line-height: 1.5;
        overflow-wrap: anywhere;
    }

    &__meta {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        line-height: 1.5;
        overflow-wrap: anywhere;
    }
}
</style>
