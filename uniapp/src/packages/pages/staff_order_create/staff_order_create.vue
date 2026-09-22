<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="staff" tone="workspace" hasSafeBottom>
        <BaseNavbar title="手动录单" variant="solid" title-align="center" bg-color="#181614" text-color="#FFFDF8" @back="leave" />
        
        <view class="manual-page">
            <!-- 顶部引导区 -->
            <view class="manual-hero">
                <view class="manual-hero__copy">
                    <text class="manual-hero__title">为客户录入服务订单</text>
                    <text class="manual-hero__sub">主服务人员固定为本人，费用按套餐与服务地区自动核算</text>
                </view>
            </view>

            <!-- 现代三步指示器 -->
            <view class="stepper">
                <view
                    v-for="(label, index) in ['客户与服务', '套餐与费用', '付款与确认']"
                    :key="label"
                    class="step-item"
                    :class="{ 'step-item--active': step === index, 'step-item--done': step > index }"
                    @click="step > index ? step = index : null"
                >
                    <view class="step-item__node">
                        <text v-if="step > index" class="step-item__check">✓</text>
                        <text v-else class="step-item__num">{{ index + 1 }}</text>
                    </view>
                    <text class="step-item__label">{{ label }}</text>
                    <view v-if="index < 2" class="step-item__line" />
                </view>
            </view>

            <!-- STEP 0: 客户与服务 -->
            <view v-if="step === 0" class="step-content">
                <BaseCard variant="panel" scene="staff" padding="28rpx" class="form-card">
                    <view class="card-head">
                        <text class="card-head__title">客户账号</text>
                        <text class="card-head__desc">支持绑定现有客户或使用临时联系人</text>
                    </view>

                    <view class="customer-search-row">
                        <view class="customer-search-input">
                            <BaseInput
                                v-model="mobile"
                                type="number"
                                :maxlength="11"
                                placeholder="输入客户11位手机号"
                                clearable
                            />
                        </view>
                        <BaseButton
                            size="sm"
                            variant="primary"
                            height="72rpx"
                            :loading="searching"
                            :disabled="searching || busy"
                            @click="lookup"
                        >
                            查询账号
                        </BaseButton>
                    </view>

                    <view v-if="customers.length" class="customer-results">
                        <view
                            v-for="item in customers"
                            :key="item.selection_token"
                            class="choice-card"
                            :class="{ 'choice-card--selected': selection?.selection_token === item.selection_token }"
                            @click="selectCustomer(item)"
                        >
                            <view class="choice-card__info">
                                <view class="choice-card__icon-box">
                                    <BaseIcon name="order" size="22" color="#B8954A" />
                                </view>
                                <view class="choice-card__copy">
                                    <text class="choice-card__name">{{ item.nickname }}</text>
                                    <text class="choice-card__meta">手机号：{{ item.mobile }}</text>
                                </view>
                            </view>
                            <view class="choice-card__badge">
                                {{ selection?.selection_token === item.selection_token ? '已选中' : '选择' }}
                            </view>
                        </view>
                    </view>

                    <view v-if="searched && !customers.length" class="tip-box">
                        <text>未查询到注册客户账号，您可选择「临时联系人」继续录单。</text>
                    </view>

                    <view
                        class="choice-card choice-card--guest"
                        :class="{ 'choice-card--selected': !selection }"
                        @click="selectCustomer(null)"
                    >
                        <view class="choice-card__info">
                            <view class="choice-card__icon-box choice-card__icon-box--guest">
                                <BaseIcon name="edit" size="22" color="#8C857B" />
                            </view>
                            <view class="choice-card__copy">
                                <text class="choice-card__name">临时联系人模式</text>
                                <text class="choice-card__meta">适用于新客建单，后续由后台关联账号</text>
                            </view>
                        </view>
                        <view class="choice-card__badge">
                            {{ !selection ? '已选中' : '选择' }}
                        </view>
                    </view>
                </BaseCard>

                <BaseCard variant="panel" scene="staff" padding="28rpx" class="form-card">
                    <view class="card-head">
                        <text class="card-head__title">履约服务信息</text>
                        <text class="card-head__desc">服务联系人、日期与地址</text>
                    </view>

                    <view class="form-fields">
                        <BaseInput
                            v-model="form.contact_name"
                            label="联系人姓名"
                            :maxlength="50"
                            placeholder="请填写联系人姓名"
                            clearable
                        />
                        <BaseInput
                            v-model="form.contact_mobile"
                            label="联系电话"
                            type="number"
                            :maxlength="11"
                            placeholder="请填写联系手机号"
                            clearable
                        />
                        <BaseDatePicker
                            :model-value="form.service_date"
                            label="服务日期"
                            :min-date="today"
                            :disabled="busy"
                            @confirm="changeDate"
                        />
                        <BasePickerField
                            label="服务地区"
                            :model-value="regionText"
                            placeholder="请选择已开通服务的地区"
                            :status-text="regionLoading ? '加载中...' : ''"
                            :hint="regionError || '仅展示支持服务的城市与区县'"
                            :disabled="busy || regionLoading"
                            @click="openRegion"
                        />
                        <view class="textarea-field">
                            <text class="textarea-field__label">详细地址</text>
                            <textarea
                                v-model="form.service_address"
                                maxlength="255"
                                placeholder="请输入详细地址、婚礼酒店名称及宴会厅"
                                class="manual-textarea"
                            />
                        </view>
                    </view>
                </BaseCard>
            </view>

            <!-- STEP 1: 套餐与费用 -->
            <view v-if="step === 1" class="step-content">
                <BaseCard variant="panel" scene="staff" padding="28rpx" class="form-card">
                    <view class="staff-tag-row">
                        <text class="staff-tag-row__label">主服务人员</text>
                        <view class="staff-tag-row__val">
                            <text class="staff-tag-row__name">{{ staffName || '本人' }}</text>
                            <text class="staff-tag-row__badge">主理人</text>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard variant="panel" scene="staff" padding="28rpx" class="form-card">
                    <view class="card-head">
                        <text class="card-head__title">选择服务套餐</text>
                        <text class="card-head__desc">已根据服务地区筛选可用套餐</text>
                    </view>

                    <text v-if="!packages.length" class="empty-tip">
                        当前地区和日期暂无可用套餐，请返回修改地区，或在套餐管理中确认上架状态。
                    </text>

                    <view v-else class="package-choices">
                        <view
                            v-for="item in packages"
                            :key="item.id"
                            class="choice-card"
                            :class="{ 'choice-card--selected': form.main_package_id === item.id }"
                            @click="selectPackage(item.id)"
                        >
                            <view class="choice-card__info">
                                <text class="choice-card__title">{{ item.name }}</text>
                            </view>
                            <view class="choice-card__price-col">
                                <text class="choice-card__price">¥{{ item.price }}</text>
                                <view class="choice-card__check-circle">
                                    <text v-if="form.main_package_id === item.id">✓</text>
                                </view>
                            </view>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard v-if="addons.length" variant="panel" scene="staff" padding="28rpx" class="form-card">
                    <view class="card-head">
                        <text class="card-head__title">附加服务项 (选填)</text>
                    </view>

                    <view class="addon-choices">
                        <view
                            v-for="item in addons"
                            :key="item.id"
                            class="addon-chip"
                            :class="{ 'addon-chip--selected': form.addon_ids.includes(item.id) }"
                            @click="toggleAddon(item.id)"
                        >
                            <text class="addon-chip__mark">{{ form.addon_ids.includes(item.id) ? '✓' : '+' }}</text>
                            <text class="addon-chip__name">{{ item.name }}</text>
                            <text class="addon-chip__price">+¥{{ item.price }}</text>
                        </view>
                    </view>
                </BaseCard>

                <view v-if="quote" class="quote-receipt">
                    <view class="quote-receipt__top">
                        <text class="quote-receipt__label">订单应付总额</text>
                        <text class="quote-receipt__total">¥{{ quote.pay_amount }}</text>
                    </view>
                    <view class="quote-receipt__divider" />
                    <view class="quote-receipt__breakdown">
                        <text>定金款项：¥{{ quote.deposit_amount }}</text>
                        <text>履约尾款：¥{{ quote.balance_amount }}</text>
                    </view>
                </view>
            </view>

            <!-- STEP 2: 付款与确认 -->
            <view v-if="step === 2" class="step-content">
                <BaseCard variant="panel" scene="staff" padding="28rpx" class="form-card">
                    <view class="card-head">
                        <text class="card-head__title">核对录单明细</text>
                    </view>

                    <view class="summary-list">
                        <view class="summary-row">
                            <text class="summary-row__label">联系人</text>
                            <text class="summary-row__val">{{ form.contact_name }} ({{ form.contact_mobile }})</text>
                        </view>
                        <view class="summary-row">
                            <text class="summary-row__label">服务日期</text>
                            <text class="summary-row__val">{{ form.service_date }}</text>
                        </view>
                        <view class="summary-row">
                            <text class="summary-row__label">服务地区</text>
                            <text class="summary-row__val">{{ regionText }}</text>
                        </view>
                        <view class="summary-row">
                            <text class="summary-row__label">详细地址</text>
                            <text class="summary-row__val">{{ form.service_address }}</text>
                        </view>
                        <view class="summary-row">
                            <text class="summary-row__label">服务套餐</text>
                            <text class="summary-row__val">{{ selectedPackageName }}</text>
                        </view>
                    </view>

                    <view v-if="quote" class="summary-quote">
                        <text class="summary-quote__label">核算总金额</text>
                        <view class="summary-quote__right">
                            <text class="summary-quote__val">¥{{ quote.pay_amount }}</text>
                            <text class="summary-quote__sub">定金 ¥{{ quote.deposit_amount }} · 尾款 ¥{{ quote.balance_amount }}</text>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard variant="panel" scene="staff" padding="28rpx" class="form-card">
                    <view class="card-head">
                        <text class="card-head__title">付款模式安排</text>
                    </view>

                    <view class="payment-mode-choices">
                        <view
                            v-if="selection"
                            class="choice-card"
                            :class="{ 'choice-card--selected': form.payment_entry_mode === 'online_pending' }"
                            @click="changePayment('online_pending')"
                        >
                            <view class="choice-card__info">
                                <text class="choice-card__title">客户在线支付</text>
                                <text class="choice-card__meta">生成待支付订单，由客户在小程序端完成支付</text>
                            </view>
                            <view class="choice-card__check-circle">
                                <text v-if="form.payment_entry_mode === 'online_pending'">✓</text>
                            </view>
                        </view>

                        <view
                            class="choice-card"
                            :class="{ 'choice-card--selected': form.payment_entry_mode === 'offline_voucher' }"
                            @click="changePayment('offline_voucher')"
                        >
                            <view class="choice-card__info">
                                <text class="choice-card__title">线下收款凭证审核</text>
                                <text class="choice-card__meta">服务人员代收或线下付款，提交转账凭证供后台审核</text>
                            </view>
                            <view class="choice-card__check-circle">
                                <text v-if="form.payment_entry_mode === 'offline_voucher'">✓</text>
                            </view>
                        </view>
                    </view>

                    <view v-if="form.payment_entry_mode === 'offline_voucher'" class="receipt-sub-section">
                        <view class="switch-row">
                            <text class="switch-row__label">同时提交已收款凭证截图</text>
                            <switch :checked="withReceipt" color="#C8A45D" :disabled="busy" @change="changeReceipt" />
                        </view>
                        <ReceiptEditor
                            v-if="withReceipt"
                            v-model="receipt"
                            :phases="receiptPhases"
                            :disabled="busy"
                            @uploading="uploading = $event"
                        />
                    </view>
                </BaseCard>

                <BaseCard variant="panel" scene="staff" padding="28rpx" class="form-card">
                    <view class="card-head">
                        <text class="card-head__title">订单备注 (选填)</text>
                    </view>
                    <textarea
                        v-model="form.admin_remark"
                        maxlength="500"
                        placeholder="请输入录单补充要求、新娘喜好或沟通重点..."
                        class="manual-textarea"
                    />
                    <text class="tip-text">提示：录单提交后不会立即直接确认到账或锁档；线下收款需等待管理员后台审核凭证。</text>
                </BaseCard>
            </view>

            <!-- 底部浮动操作条 -->
            <view class="bottom-actions">
                <view v-if="step > 0" class="bottom-actions__btn-wrap">
                    <BaseButton block variant="secondary" height="84rpx" :disabled="busy || uploading" @click="step--">
                        上一步
                    </BaseButton>
                </view>
                <view class="bottom-actions__btn-wrap bottom-actions__btn-wrap--primary">
                    <BaseButton block variant="dark" height="84rpx" :loading="busy" :disabled="busy || uploading" @click="next">
                        {{ step === 2 ? '确认提交订单' : '下一步' }}
                    </BaseButton>
                </view>
            </view>
        </view>

        <BaseServiceRegionPicker
            :model-value="form"
            v-model:open="regionOpen"
            :data="regionTree"
            @confirm="changeRegion"
        />
    </PageShell>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { onHide, onLoad, onShow, onUnload } from '@dcloudio/uni-app'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseInput from '@/components/base/BaseInput.vue'
import BaseDatePicker from '@/components/base/BaseDatePicker.vue'
import BasePickerField from '@/components/base/BasePickerField.vue'
import BaseServiceRegionPicker from '@/components/base/BaseServiceRegionPicker.vue'
import type { ServiceRegionValue } from '@/components/base/BaseServiceRegionPicker.vue'
import { getServiceRegionTree } from '@/api/service'
import PageShell from '@/components/base/PageShell.vue'
import ReceiptEditor from '@/packages/components/ReceiptEditor.vue'
import { staffOrderCustomers, staffOrderOptions, staffOrderPreview, staffOrderCreate, newStaffSubmitKey } from '@/api/staffManualOrder'
import type { ManualQuote, ReceiptDraft } from '@/api/staffManualOrder'
import { loadServiceRegionSelection, formatServiceRegionText, hasServiceRegion } from '@/utils/service-region'
import { ensureStaffCenterAccess } from '@/packages/common/utils/staff-center'
import { remindBeforeOaAction } from '@/utils/oa-reminder'
import { useUserStore } from '@/stores/user'
import { showError } from '@/utils/feedback'
declare const wx: any

const exitAlert = (enabled: boolean) => {
    // 微信原生离页提醒覆盖系统返回，前往服务号设置时暂时关闭。
    // #ifdef MP-WEIXIN
    if (enabled) wx.enableAlertBeforeUnload?.({ message: '订单尚未提交，离开后需要重新填写。' })
    else wx.disableAlertBeforeUnload?.({})
    // #endif
}

const today = new Date(Date.now() + 8 * 3600000).toISOString().slice(0, 10)
const form = reactive({ ...loadServiceRegionSelection(), service_date: '', service_address: '', contact_name: '', contact_mobile: '', admin_remark: '', main_package_id: 0, addon_ids: [] as number[], payment_entry_mode: 'offline_voucher' })
const user = useUserStore()
const step = ref(0), busy = ref(false), searching = ref(false), uploading = ref(false), searched = ref(false)
const mobile = ref(''), customers = ref<any[]>([]), selection = ref<any>(null), packages = ref<any[]>([]), addons = ref<any[]>([]), staffName = ref('')
const quote = ref<ManualQuote | null>(null), withReceipt = ref(false)
const receipt = ref<ReceiptDraft>({ pay_type: 3, voucher: '', collection_owner: 2 })
const receiptPhases = computed(() => [ ...(Number(quote.value?.deposit_amount) > 0 ? [{ value: 1, label: '定金', amount: Number(quote.value?.deposit_amount) }] : []), { value: 3, label: '全款', amount: Number(quote.value?.pay_amount || 0) } ])
const regionText = computed(() => formatServiceRegionText(form))
const changeReceipt = (event: any) => { withReceipt.value = !!event.detail.value }
const selectedPackageName = computed(() => packages.value.find(item => item.id === form.main_package_id)?.name || '')
let submitKey = newStaffSubmitKey(), generation = 0, active = true, submitted = false, ownerToken = ''
const valid = (version: number) => active && generation === version && user.token === ownerToken
const regionOpen = ref(false), regionLoading = ref(false), regionError = ref('')
const regionTree = ref<any[]>([])
const openRegion = async () => {
    if (busy.value || regionLoading.value) return
    if (regionTree.value.length) { regionOpen.value = true; return }
    const version = generation
    regionLoading.value = true; regionError.value = ''
    try {
        const data = await getServiceRegionTree()
        if (!valid(version)) return
        regionTree.value = Array.isArray(data) ? data : []
        if (!regionTree.value.length) { regionError.value = '暂无可选服务地区，点击重试'; return }
        regionOpen.value = true
    } catch (error) {
        if (valid(version)) { regionError.value = '地区加载失败，点击重试'; showError(error, '地区加载失败') }
    } finally { regionLoading.value = false }
}
const resetQuote = () => { quote.value = null; form.main_package_id = 0; form.addon_ids = []; addons.value = [] }
const changeRegion = (value: ServiceRegionValue) => {
    Object.assign(form, value)
    regionOpen.value = false
    resetQuote()
}
const changeDate = (value: string | number) => { form.service_date = String(value); resetQuote() }
const lookup = async () => {
    if (searching.value || busy.value) return
    if (!/^1[3-9]\d{9}$/.test(mobile.value)) return showError('请输入完整手机号')
    searching.value = true
    const version = generation
    try { const result = await staffOrderCustomers(mobile.value); if (valid(version)) { customers.value = result; searched.value = true } }
    catch (error) { showError(error, '查询失败') }
    finally { searching.value = false }
}
const selectCustomer = (value: any) => { selection.value = value; if (!value) form.payment_entry_mode = 'offline_voucher'; else if (!form.contact_mobile) form.contact_mobile = mobile.value }
const selectPackage = async (id: number) => {
    if (busy.value) return
    busy.value = true; form.main_package_id = id; form.addon_ids = []; quote.value = null
    const version = generation
    try { const options = await staffOrderOptions(form); if (!valid(version)) return; addons.value = options.addons; const result = await staffOrderPreview(form); if (valid(version)) quote.value = result }
    catch (error) { showError(error, '套餐加载失败') }
    finally { busy.value = false }
}
const toggleAddon = (id: number) => { if (busy.value) return; form.addon_ids = form.addon_ids.includes(id) ? form.addon_ids.filter(value => value !== id) : [...form.addon_ids, id]; quote.value = null }
const changePayment = (mode: string) => { if (busy.value) return; form.payment_entry_mode = mode; if (mode === 'online_pending') withReceipt.value = false }
const validateContact = () => {
    if (!form.contact_name.trim() || !/^1[3-9]\d{9}$/.test(form.contact_mobile)) throw new Error('请填写联系人及有效联系电话')
    if (!form.service_date || !hasServiceRegion(form) || !form.service_address.trim()) throw new Error('请填写服务日期、地区和详细地址')
}
const next = async () => {
    if (busy.value || uploading.value) return
    busy.value = true
    const version = generation
    try {
        validateContact()
        if (step.value === 0) {
            const options = await staffOrderOptions(form)
            if (!valid(version)) return
            packages.value = options.packages; staffName.value = options.staff_name; addons.value = []
            form.main_package_id = 0; form.addon_ids = []; quote.value = null; step.value = 1
        } else if (step.value === 1) {
            if (!form.main_package_id) throw new Error('请选择本人服务套餐')
            const result = await staffOrderPreview(form)
            if (!valid(version)) return
            quote.value = result; receipt.value.pay_type = result.deposit_amount > 0 ? 1 : 3; step.value = 2
        } else {
            if (withReceipt.value && !receipt.value.voucher) throw new Error('请上传收款凭证')
            const latest = await staffOrderPreview(form)
            if (!valid(version)) return
            if (latest.quote_hash !== quote.value?.quote_hash) { quote.value = latest; showError('费用摘要已更新，请核对后再次提交'); return }
            exitAlert(false)
            const proceed = await remindBeforeOaAction()
            if (active) exitAlert(true)
            if (!proceed || !valid(version)) return
            const result = await staffOrderCreate({ ...form, submit_key: submitKey, quote_hash: quote.value?.quote_hash, selection_token: selection.value?.selection_token || '', receipt: withReceipt.value ? receipt.value : undefined })
            if (!valid(version)) return
            if (result.quote_changed) { quote.value = result.quote; showError('报价已变化，请重新核对并提交'); return }
            submitted = true
            exitAlert(false)
            uni.redirectTo({ url: `/packages/pages/staff_order_detail/staff_order_detail?id=${result.order_id}` })
        }
    } catch (error) { showError(error, '操作失败，请重试') }
    finally { busy.value = false }
}
const leave = () => {
    if (busy.value) return
    uni.showModal({ title: '离开录单页面？', content: '尚未提交的内容将不再保留。', confirmText: '确认离开', success: result => { if (result.confirm) { submitted = true; exitAlert(false); const pages = getCurrentPages(); if (pages.length > 1) uni.navigateBack({ delta: 1 }); else uni.redirectTo({ url: '/packages/pages/staff_center/staff_center' }) } } })
}
onLoad(async () => { ownerToken = user.token || ''; if (!(await ensureStaffCenterAccess())) return })
onShow(() => { active = true; if (!submitted) exitAlert(true); if (ownerToken && ownerToken !== user.token) { submitted = true; exitAlert(false); uni.redirectTo({ url: '/packages/pages/staff_center/staff_center' }) } })
onHide(() => { active = false; generation++; regionOpen.value = false })
onUnload(() => { active = false; generation++ })
</script>

<style lang="scss" scoped>
.manual-page {
    padding: 20rpx 28rpx calc(50rpx + env(safe-area-inset-bottom));
    display: flex;
    flex-direction: column;
    gap: 24rpx;
    box-sizing: border-box;
}

.manual-hero {
    display: flex;
    flex-direction: column;
    padding: 24rpx 28rpx;
    border-radius: 24rpx;
    background: linear-gradient(135deg, #1C1A17 0%, #11100E 100%);
    border: 1rpx solid rgba(217, 190, 130, 0.3);

    &__copy {
        display: flex;
        flex-direction: column;
        gap: 6rpx;
    }

    &__title {
        font-size: 34rpx;
        font-weight: 800;
        color: #FFFFFF;
        line-height: 1.3;
    }

    &__sub {
        font-size: 22rpx;
        color: rgba(255, 255, 255, 0.68);
        line-height: 1.4;
    }
}

/* 步骤指示器 */
.stepper {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20rpx 24rpx;
    border-radius: 24rpx;
    background: #FFFFFF;
    border: 1rpx solid #EAE5DB;
    box-shadow: 0 6rpx 18rpx rgba(24, 22, 20, 0.04);
}

.step-item {
    position: relative;
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8rpx;

    &__node {
        width: 44rpx;
        height: 44rpx;
        border-radius: 999rpx;
        background: #F0EDE6;
        color: #8C857B;
        font-size: 22rpx;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1;
        transition: all 0.2s ease;
    }

    &__label {
        font-size: 21rpx;
        font-weight: 600;
        color: #8C857B;
        line-height: 1.2;
        white-space: nowrap;
    }

    &__line {
        position: absolute;
        top: 22rpx;
        left: 60%;
        width: 80%;
        height: 2rpx;
        background: #EAE5DB;
        z-index: 0;
    }

    &--active {
        .step-item__node {
            background: linear-gradient(135deg, #1C1A17 0%, #11100E 100%);
            color: #D9BE82;
            box-shadow: 0 4rpx 12rpx rgba(24, 22, 20, 0.25);
        }

        .step-item__label {
            color: #181614;
            font-weight: 700;
        }
    }

    &--done {
        .step-item__node {
            background: #F3E5C8;
            color: #7D561F;
        }

        .step-item__line {
            background: #D9BE82;
        }
    }
}

/* 草稿提示 */
.draft-alert {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    padding: 16rpx 24rpx;
    border-radius: 18rpx;
    background: #FFF9ED;
    border: 1rpx solid rgba(200, 164, 93, 0.35);

    &__left {
        display: flex;
        align-items: center;
        gap: 12rpx;
    }

    &__dot {
        width: 14rpx;
        height: 14rpx;
        border-radius: 999rpx;
        background: #C8A45D;
    }

    &__text {
        font-size: 23rpx;
        color: #7A5820;
        font-weight: 600;
    }

    &__btn {
        font-size: 22rpx;
        color: #B84A39;
        font-weight: 600;
        padding: 4rpx 12rpx;
    }
}

.step-content {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
}

.form-card {
    border-radius: 28rpx;
}

.card-head {
    display: flex;
    flex-direction: column;
    gap: 4rpx;
    margin-bottom: 20rpx;

    &__title {
        font-size: 30rpx;
        font-weight: 800;
        color: #181614;
    }

    &__desc {
        font-size: 22rpx;
        color: #8C857B;
    }
}

.customer-search-row {
    display: flex;
    align-items: center;
    gap: 16rpx;
}

.customer-search-input {
    flex: 1;
    min-width: 0;
}

.customer-results {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
    margin-top: 14rpx;
}

.choice-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    padding: 22rpx 24rpx;
    border-radius: 20rpx;
    background: #FAF8F5;
    border: 1rpx solid #EAE5DB;
    transition: all 0.2s ease;

    &:active {
        transform: scale(0.98);
    }

    &--selected {
        background: #F9F3E8;
        border-color: #D9BE82;
        box-shadow: 0 4rpx 16rpx rgba(200, 164, 93, 0.14);
    }

    &--guest {
        margin-top: 14rpx;
    }

    &__info {
        display: flex;
        align-items: center;
        gap: 16rpx;
        flex: 1;
        min-width: 0;
    }

    &__icon-box {
        width: 52rpx;
        height: 52rpx;
        border-radius: 14rpx;
        background: #F4EEDF;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;

        &--guest {
            background: #EDEAE1;
        }
    }

    &__copy {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__name {
        font-size: 27rpx;
        font-weight: 700;
        color: #181614;
    }

    &__meta {
        font-size: 21rpx;
        color: #8C857B;
    }

    &__badge {
        font-size: 22rpx;
        font-weight: 700;
        color: #B8954A;
        padding: 6rpx 16rpx;
        border-radius: 999rpx;
        background: rgba(200, 164, 93, 0.14);
    }

    &__title {
        font-size: 28rpx;
        font-weight: 700;
        color: #181614;
    }

    &__price-col {
        display: flex;
        align-items: center;
        gap: 16rpx;
    }

    &__price {
        font-size: 32rpx;
        font-weight: 800;
        color: #B8954A;
    }

    &__check-circle {
        width: 40rpx;
        height: 40rpx;
        border-radius: 999rpx;
        border: 2rpx solid #D9BE82;
        background: #FFFFFF;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22rpx;
        font-weight: 800;
        color: #B8954A;
    }
}

.tip-box {
    padding: 16rpx 20rpx;
    border-radius: 16rpx;
    background: #FAF8F5;
    border: 1rpx dashed #D8D2C5;
    font-size: 22rpx;
    color: #8C857B;
    line-height: 1.4;
    margin-top: 14rpx;
}

.form-fields {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
}

.textarea-field {
    display: flex;
    flex-direction: column;
    gap: 10rpx;

    &__label {
        font-size: 24rpx;
        font-weight: 700;
        color: #4A453C;
    }
}

.manual-textarea {
    width: 100%;
    height: 160rpx;
    padding: 20rpx 24rpx;
    border-radius: 20rpx;
    background: #FAF8F5;
    border: 1rpx solid #EAE5DB;
    box-sizing: border-box;
    font-size: 26rpx;
    line-height: 1.5;
    color: #181614;
}

.staff-tag-row {
    display: flex;
    align-items: center;
    justify-content: space-between;

    &__label {
        font-size: 26rpx;
        color: #7A7267;
        font-weight: 600;
    }

    &__val {
        display: flex;
        align-items: center;
        gap: 10rpx;
    }

    &__name {
        font-size: 28rpx;
        font-weight: 800;
        color: #181614;
    }

    &__badge {
        font-size: 19rpx;
        font-weight: 700;
        color: #B8954A;
        padding: 4rpx 12rpx;
        border-radius: 999rpx;
        background: #F4EEDF;
    }
}

.package-choices {
    display: flex;
    flex-direction: column;
    gap: 14rpx;
}

.addon-choices {
    display: flex;
    flex-wrap: wrap;
    gap: 14rpx;
}

.addon-chip {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    padding: 14rpx 22rpx;
    border-radius: 999rpx;
    background: #FAF8F5;
    border: 1rpx solid #EAE5DB;
    font-size: 23rpx;
    transition: all 0.2s ease;

    &:active {
        transform: scale(0.96);
    }

    &--selected {
        background: #F8F3EA;
        border-color: #D9BE82;
        color: #8C6A37;
        font-weight: 700;
    }

    &__mark {
        font-weight: 800;
    }

    &__name {
        color: #181614;
    }

    &__price {
        font-weight: 700;
        color: #B8954A;
    }
}

.quote-receipt {
    display: flex;
    flex-direction: column;
    gap: 14rpx;
    padding: 26rpx 28rpx;
    border-radius: 24rpx;
    background: linear-gradient(135deg, #FAF3E5 0%, #EBD8B0 100%);
    border: 1rpx solid rgba(200, 164, 93, 0.45);
    box-shadow: 0 10rpx 24rpx rgba(200, 164, 93, 0.16);

    &__top {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
    }

    &__label {
        font-size: 26rpx;
        font-weight: 700;
        color: #4A3311;
    }

    &__total {
        font-size: 44rpx;
        font-weight: 900;
        color: #181614;
    }

    &__divider {
        height: 1rpx;
        background: rgba(200, 164, 93, 0.35);
    }

    &__breakdown {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 23rpx;
        color: #6E5328;
        font-weight: 600;
    }
}

.summary-list {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
    padding-bottom: 16rpx;
    border-bottom: 1rpx solid #EBE6DC;
}

.summary-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20rpx;

    &__label {
        font-size: 23rpx;
        color: #8C857B;
        flex-shrink: 0;
    }

    &__val {
        font-size: 24rpx;
        font-weight: 700;
        color: #181614;
        text-align: right;
    }
}

.summary-quote {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 16rpx;

    &__label {
        font-size: 26rpx;
        font-weight: 700;
        color: #181614;
    }

    &__right {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 4rpx;
    }

    &__val {
        font-size: 38rpx;
        font-weight: 800;
        color: #B8954A;
    }

    &__sub {
        font-size: 20rpx;
        color: #8C857B;
    }
}

.payment-mode-choices {
    display: flex;
    flex-direction: column;
    gap: 14rpx;
}

.receipt-sub-section {
    margin-top: 20rpx;
    padding-top: 18rpx;
    border-top: 1rpx solid #EBE6DC;
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.switch-row {
    display: flex;
    align-items: center;
    justify-content: space-between;

    &__label {
        font-size: 25rpx;
        font-weight: 700;
        color: #181614;
    }
}

.tip-text {
    display: block;
    font-size: 21rpx;
    color: #8C857B;
    line-height: 1.45;
    margin-top: 12rpx;
}

.bottom-actions {
    display: flex;
    align-items: center;
    gap: 16rpx;
    margin-top: 10rpx;

    &__btn-wrap {
        flex: 1;
        min-width: 0;

        &--primary {
            flex: 2;
        }
    }
}
</style>
