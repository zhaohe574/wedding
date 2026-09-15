<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="staff" tone="workspace" hasSafeBottom>
        <BaseNavbar title="手动录单" variant="solid" title-align="center" @back="leave" />
        <view class="manual-page">
            <view class="manual-intro"><text class="manual-title">为客户录入服务订单</text><text>主服务人员固定为本人，费用按套餐与服务地区计算。</text></view>
            <view class="manual-steps"><text v-for="(label, index) in ['客户与服务', '套餐与费用', '付款与确认']" :key="label" :class="{ active: step === index }">{{ index + 1 }} {{ label }}</text></view>
            <BaseCard v-if="step === 0" variant="panel" scene="staff" padding="28rpx">
                <view class="manual-card">
                <text class="manual-section">客户账号</text>
                <view class="manual-search"><view class="manual-search__input"><BaseInput v-model="mobile" type="number" :maxlength="11" placeholder="输入客户完整手机号" /></view><BaseButton size="sm" variant="secondary" :loading="searching" :disabled="searching || busy" @click="lookup">查询</BaseButton></view>
                <view v-for="item in customers" :key="item.selection_token" class="manual-choice" :class="{ selected: selection?.selection_token === item.selection_token }" @click="selectCustomer(item)">{{ item.nickname }} · {{ item.mobile }} <text>{{ selection?.selection_token === item.selection_token ? '已选择' : '选择' }}</text></view>
                <text v-if="searched && !customers.length" class="manual-help">未查到可用账号，可填写临时联系人。不会自动创建或关联客户账号。</text>
                <view class="manual-choice" :class="{ selected: !selection }" @click="selectCustomer(null)">临时联系人 <text>{{ !selection ? '已选择' : '选择' }}</text></view>
                <text class="manual-section">服务信息</text>
                <BaseInput v-model="form.contact_name" label="联系人" :maxlength="50" placeholder="请填写姓名" />
                <BaseInput v-model="form.contact_mobile" label="联系电话" type="number" :maxlength="11" placeholder="请填写联系电话" />
                <BaseDatePicker :model-value="form.service_date" label="服务日期" :min-date="today" :disabled="busy" @confirm="changeDate" />
                <BasePickerField label="服务地区" :model-value="regionText" placeholder="请选择服务地区" :status-text="regionLoading ? '加载中' : ''" :hint="regionError || '仅展示已开通服务的地区'" :disabled="busy || regionLoading" @click="openRegion" />
                <text class="manual-label">详细地址</text>
                <textarea v-model="form.service_address" maxlength="255" placeholder="详细地址、酒店及宴会厅" class="manual-textarea" />
                </view>
            </BaseCard>
            <BaseCard v-if="step === 1" variant="panel" scene="staff" padding="28rpx">
                <view class="manual-card">
                <view class="manual-field"><text>主服务人员</text><text>{{ staffName }}（本人）</text></view>
                <text class="manual-section">选择服务套餐</text>
                <text v-if="!packages.length" class="manual-help">当前地区和日期暂无可用套餐，请返回修改，或在套餐管理中检查。</text>
                <view v-for="item in packages" :key="item.id" class="manual-choice" :class="{ selected: form.main_package_id === item.id }" @click="selectPackage(item.id)"><text>{{ item.name }}</text><text>¥{{ item.price }}</text></view>
                <text v-if="addons.length" class="manual-section">附加服务</text>
                <view v-for="item in addons" :key="item.id" class="manual-choice" :class="{ selected: form.addon_ids.includes(item.id) }" @click="toggleAddon(item.id)"><text>{{ form.addon_ids.includes(item.id) ? '✓ ' : '＋ ' }}{{ item.name }}</text><text>¥{{ item.price }}</text></view>
                <view v-if="quote" class="manual-total"><text>订单总额</text><text>¥{{ quote.pay_amount }}</text><text class="manual-help">定金 ¥{{ quote.deposit_amount }} · 尾款 ¥{{ quote.balance_amount }}</text></view>
                </view>
            </BaseCard>
            <BaseCard v-if="step === 2" variant="panel" scene="staff" padding="28rpx">
                <view class="manual-card">
                <text class="manual-section">核对订单</text>
                <text>{{ form.contact_name }} · {{ form.contact_mobile }}</text>
                <text>{{ form.service_date }} · {{ regionText }}</text>
                <text>{{ form.service_address }}</text>
                <text>{{ staffName }} · {{ selectedPackageName }}</text>
                <view class="manual-total"><text>订单总额</text><text>¥{{ quote?.pay_amount }}</text><text class="manual-help">定金 ¥{{ quote?.deposit_amount }} · 尾款 ¥{{ quote?.balance_amount }}</text></view>
                <text class="manual-section">付款安排</text>
                <view v-if="selection" class="manual-choice" :class="{ selected: form.payment_entry_mode === 'online_pending' }" @click="changePayment('online_pending')">客户在线支付</view>
                <view class="manual-choice" :class="{ selected: form.payment_entry_mode === 'offline_voucher' }" @click="changePayment('offline_voucher')">线下收款</view>
                <text v-if="!selection" class="manual-help">临时联系人仅支持线下待收款，后续由后台关联客户账号。</text>
                <view v-if="form.payment_entry_mode === 'offline_voucher'" class="manual-field"><text>同时提交已收款凭证</text><switch :checked="withReceipt" color="#ad8c53" :disabled="busy" @change="changeReceipt" /></view>
                <ReceiptEditor v-if="withReceipt && form.payment_entry_mode === 'offline_voucher'" v-model="receipt" :phases="receiptPhases" :disabled="busy" @uploading="uploading = $event" />
                <text class="manual-label">订单备注（选填）</text>
                <textarea v-model="form.admin_remark" maxlength="500" placeholder="订单备注（选填）" class="manual-textarea" />
                <text class="manual-help">建单后不会直接确认到账或锁档；线下收款需等待后台审核。</text>
                </view>
            </BaseCard>
            <view class="manual-buttons">
                <view v-if="step > 0" class="manual-buttons__back"><BaseButton block variant="secondary" :disabled="busy || uploading" @click="step--">上一步</BaseButton></view>
                <view class="manual-buttons__next"><BaseButton block :loading="busy" :disabled="busy || uploading" @click="next">{{ step === 2 ? '确认提交订单' : '下一步' }}</BaseButton></view>
            </view>
        </view>
        <BaseServiceRegionPicker :model-value="form" v-model:open="regionOpen" :data="regionTree" @confirm="changeRegion" />
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
<style scoped>
.manual-page { padding: 28rpx 28rpx calc(48rpx + env(safe-area-inset-bottom)); display: flex; flex-direction: column; gap: 28rpx; color: var(--wm-text-primary); }
.manual-intro { display: flex; flex-direction: column; gap: 12rpx; font-size: 24rpx; color: var(--wm-text-secondary); line-height: 1.7; }
.manual-title { font-size: 36rpx; font-weight: 600; color: var(--wm-text-primary); }
.manual-steps { display: flex; justify-content: space-between; gap: 12rpx; font-size: 24rpx; color: var(--wm-text-tertiary); }
.manual-steps text { padding-bottom: 16rpx; border-bottom: 4rpx solid transparent; }
.manual-steps .active { color: var(--wm-color-gold); font-weight: 600; border-color: var(--wm-color-champagne); }
.manual-card { display: flex; flex-direction: column; gap: 28rpx; font-size: 27rpx; line-height: 1.6; }
.manual-section { font-size: 30rpx; font-weight: 600; color: var(--wm-text-primary); }
.manual-label { font-size: 24rpx; font-weight: 600; color: var(--wm-text-secondary); margin-bottom: -16rpx; }
.manual-field { display: flex; align-items: center; justify-content: space-between; gap: 20rpx; padding: 18rpx 0; border-bottom: 1rpx solid var(--wm-color-border); }
.manual-field > text:last-child { max-width: 75%; text-align: right; }
.manual-search { display: flex; align-items: center; gap: 16rpx; }
.manual-search__input { flex: 1; min-width: 0; }
.manual-choice { padding: 24rpx; border: 1rpx solid var(--wm-color-border); border-radius: var(--wm-radius-control, 44rpx); display: flex; justify-content: space-between; align-items: center; gap: 16rpx; }
.manual-choice.selected { border-color: var(--wm-color-champagne); background: var(--wm-color-bg-soft, #F8F3EA); color: var(--wm-color-gold); }
.manual-help { font-size: 24rpx; line-height: 1.7; color: var(--wm-text-tertiary); }
.manual-textarea { box-sizing: border-box; width: 100%; height: 180rpx; padding: 24rpx 30rpx; border: 1rpx solid var(--wm-color-border); border-radius: var(--wm-radius-control, 44rpx); background: var(--wm-color-bg-card); color: var(--wm-text-primary); font-size: 27rpx; }
.manual-total { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 16rpx; padding: 28rpx; background: var(--wm-color-bg-soft, #F8F3EA); border-radius: var(--wm-radius-control, 44rpx); font-size: 30rpx; color: var(--wm-color-gold); }
.manual-total > text:nth-child(2) { font-size: 38rpx; font-weight: 600; }
.manual-total .manual-help { width: 100%; }
.manual-buttons { display: flex; gap: 20rpx; }
.manual-buttons__back { flex: 1; min-width: 0; }
.manual-buttons__next { flex: 2; min-width: 0; }
</style>
