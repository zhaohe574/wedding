<template>
    <el-drawer v-model="drawerVisible" title="后台建单" size="780px" destroy-on-close class="offline-order-drawer">
        <template #header>
            <div class="offline-order-drawer__header">
                <div>
                    <div class="text-lg font-semibold">{{ drawerTitle }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ drawerDescription }}</div>
                </div>
                <el-tag :type="entryMeta.tagType" size="small">{{ entryMeta.tagText }}</el-tag>
            </div>
        </template>
        <div class="offline-order-drawer__body">
            <el-steps :active="offlineStep" simple finish-status="success" class="mb-5">
                <el-step title="客户信息" />
                <el-step title="档期与地区" />
                <el-step title="主服务" />
                <el-step title="附加与结算" />
            </el-steps>
            <el-form ref="formRef" :model="offlineForm" :rules="offlineRules" label-width="110px" class="offline-order-form">
                <div class="offline-section-card offline-section-card--entry">
                    <div class="offline-section-card__title">付款录入方式</div>
                    <el-form-item label="付款方式" prop="payment_entry_mode">
                        <el-radio-group v-model="offlineForm.payment_entry_mode" class="offline-entry-radio">
                            <el-radio-button
                                v-for="item in paymentEntryModeOptions"
                                :key="item.value"
                                :value="item.value"
                            >
                                {{ item.label }}
                            </el-radio-button>
                        </el-radio-group>
                    </el-form-item>
                    <div class="offline-entry-hint">
                        <div class="offline-entry-hint__title">{{ entryMeta.title }}</div>
                        <div class="offline-entry-hint__desc">{{ entryMeta.description }}</div>
                    </div>
                </div>

                <div class="offline-section-card">
                    <div class="offline-section-card__title">客户信息</div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <el-form-item label="客户类型" prop="bind_mode">
                            <el-radio-group v-model="offlineForm.bind_mode">
                                <el-radio-button label="user">已有用户</el-radio-button>
                                <el-radio-button label="temp">临时客户</el-radio-button>
                            </el-radio-group>
                        </el-form-item>
                        <el-form-item v-if="offlineForm.bind_mode === 'user'" label="平台用户" prop="user_id">
                            <el-select
                                v-model="offlineForm.user_id"
                                filterable
                                remote
                                reserve-keyword
                                :remote-method="remoteUserSearch"
                                :loading="userLoading"
                                placeholder="输入昵称/手机号搜索用户"
                                class="w-full"
                                clearable
                                @change="handleUserChange"
                            >
                                <el-option v-for="item in userOptions" :key="item.id" :label="item.label" :value="item.id" />
                            </el-select>
                        </el-form-item>
                        <el-form-item label="联系人" prop="contact_name">
                            <el-input v-model="offlineForm.contact_name" placeholder="请输入联系人" />
                        </el-form-item>
                        <el-form-item label="联系电话" prop="contact_mobile">
                            <el-input v-model="offlineForm.contact_mobile" placeholder="请输入联系电话" />
                        </el-form-item>
                    </div>
                </div>

                <div class="offline-section-card">
                    <div class="offline-section-card__title">档期与地区</div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <el-form-item label="服务日期" prop="service_date">
                            <el-date-picker
                                v-model="offlineForm.service_date"
                                type="date"
                                value-format="YYYY-MM-DD"
                                placeholder="选择服务日期"
                                class="w-full"
                            />
                        </el-form-item>
                        <el-form-item label="服务城市" prop="city_code">
                            <el-select
                                v-model="offlineForm.city_code"
                                filterable
                                placeholder="请选择服务城市"
                                class="w-full"
                                @change="handleCityChange"
                            >
                                <el-option
                                    v-for="item in cityOptions"
                                    :key="item.city_code"
                                    :label="item.city_name"
                                    :value="item.city_code"
                                />
                            </el-select>
                        </el-form-item>
                        <el-form-item label="服务区县" prop="district_code">
                            <el-select
                                v-model="offlineForm.district_code"
                                filterable
                                placeholder="请选择服务区县"
                                class="w-full"
                                :disabled="!offlineForm.city_code"
                                @change="handleDistrictChange"
                            >
                                <el-option
                                    v-for="item in districtOptions"
                                    :key="item.district_code"
                                    :label="item.district_name"
                                    :value="item.district_code"
                                />
                            </el-select>
                        </el-form-item>
                        <el-form-item label="服务地址">
                            <el-input v-model="offlineForm.service_address" placeholder="请输入服务地址" />
                        </el-form-item>
                    </div>
                </div>

                <div class="offline-section-card">
                    <div class="offline-section-card__title">主服务</div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <el-form-item label="主服务人员" prop="main_staff_id">
                            <el-input v-if="isFixedMainStaff" :model-value="fixedMainStaffName" disabled />
                            <el-select
                                v-else
                                v-model="offlineForm.main_staff_id"
                                filterable
                                placeholder="请选择主服务人员"
                                class="w-full"
                                @change="handleMainStaffChange"
                            >
                                <el-option v-for="item in staffOptions" :key="item.id" :label="item.name" :value="item.id" />
                            </el-select>
                        </el-form-item>
                        <el-form-item label="主套餐" prop="main_package_id">
                            <el-select
                                v-model="offlineForm.main_package_id"
                                filterable
                                placeholder="请选择主套餐"
                                class="w-full"
                                :disabled="!packageOptions.length"
                            >
                                <el-option
                                    v-for="item in packageOptions"
                                    :key="item.id"
                                    :label="`${item.name}｜¥${formatAmount(item.price)}`"
                                    :value="item.id"
                                />
                            </el-select>
                        </el-form-item>
                    </div>
                    <div v-if="selectedMainPackage" class="offline-entry-summary">
                        <span>主套餐：{{ selectedMainPackage.name }}</span>
                        <span>价格：¥{{ formatAmount(selectedMainPackage.price) }}</span>
                    </div>
                </div>

                <div class="offline-section-card">
                    <div class="offline-section-card__title">附加与协作</div>
                    <el-form-item label="附加项">
                        <el-checkbox-group v-model="offlineForm.addon_ids" class="offline-addon-grid">
                            <el-checkbox
                                v-for="item in addonOptions"
                                :key="item.id"
                                :label="Number(item.id)"
                                border
                            >
                                {{ item.name }}｜¥{{ formatAmount(item.price) }}
                            </el-checkbox>
                        </el-checkbox-group>
                        <span v-if="!addonOptions.length" class="text-gray-400">选择主套餐后展示可用附加项</span>
                    </el-form-item>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="offline-role-card">
                            <div class="offline-role-card__title">管家协作</div>
                            <el-select
                                v-model="offlineForm.butler_staff_id"
                                filterable
                                clearable
                                placeholder="不需要可留空"
                                class="w-full"
                                @change="(value: number | undefined) => handleRoleCandidateChange('butler', value)"
                            >
                                <el-option
                                    v-for="candidate in roleCandidateMap.butler"
                                    :key="candidate.staff_id"
                                    :label="`${candidate.name}｜${candidate.package_name}｜¥${formatAmount(candidate.price)}`"
                                    :value="candidate.staff_id"
                                />
                            </el-select>
                            <div class="offline-role-card__desc text-gray-400">
                                {{ selectedRoleCandidateMap.butler?.package_description || '系统会按地区、档期和角色关系筛选可协作人员。' }}
                            </div>
                        </div>
                        <div class="offline-role-card">
                            <div class="offline-role-card__title">督导协作</div>
                            <el-select
                                v-model="offlineForm.director_staff_id"
                                filterable
                                clearable
                                placeholder="不需要可留空"
                                class="w-full"
                                @change="(value: number | undefined) => handleRoleCandidateChange('director', value)"
                            >
                                <el-option
                                    v-for="candidate in roleCandidateMap.director"
                                    :key="candidate.staff_id"
                                    :label="`${candidate.name}｜${candidate.package_name}｜¥${formatAmount(candidate.price)}`"
                                    :value="candidate.staff_id"
                                />
                            </el-select>
                            <div class="offline-role-card__desc text-gray-400">
                                {{ selectedRoleCandidateMap.director?.package_description || '系统会按地区、档期和角色关系筛选可协作人员。' }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="offline-section-card">
                    <div class="offline-section-card__title">结算预估</div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <el-form-item label="优惠金额">
                            <el-input-number v-model="offlineForm.discount_amount" :min="0" :precision="2" class="w-full" />
                        </el-form-item>
                        <el-form-item label="管理备注">
                            <el-input v-model="offlineForm.admin_remark" type="textarea" :rows="3" placeholder="请输入备注" />
                        </el-form-item>
                    </div>
                    <div class="offline-summary-panel">
                        <div class="offline-amount-grid">
                            <div class="offline-amount-card">
                                <span>主服务</span>
                                <strong>¥{{ formatAmount(estimate.main_amount) }}</strong>
                            </div>
                            <div class="offline-amount-card">
                                <span>协作服务</span>
                                <strong>¥{{ formatAmount(estimate.related_amount) }}</strong>
                            </div>
                            <div class="offline-amount-card">
                                <span>附加项</span>
                                <strong>¥{{ formatAmount(estimate.addon_amount) }}</strong>
                            </div>
                            <div class="offline-amount-card offline-amount-card--primary">
                                <span>{{ primaryAmountLabel }}</span>
                                <strong>¥{{ formatAmount(estimate.pay_amount) }}</strong>
                            </div>
                        </div>
                        <div class="offline-entry-summary">
                            <span>合计：¥{{ formatAmount(estimate.total_amount) }}</span>
                            <span>优惠：¥{{ formatAmount(estimate.discount_amount) }}</span>
                            <span>支付模式：{{ estimate.payment_mode_desc || entryMeta.tagText }}</span>
                            <span v-if="estimate.deposit_remark">{{ estimate.deposit_remark }}</span>
                        </div>
                    </div>
                </div>
            </el-form>
        </div>
        <template #footer>
            <div class="offline-order-drawer__footer">
                <el-button @click="drawerVisible = false">取消</el-button>
                <el-button type="primary" :loading="submitting" @click="submitOfflineOrder">{{ submitText }}</el-button>
            </div>
        </template>
    </el-drawer>
</template>

<script lang="ts" setup>
import { computed, reactive, ref, watch } from 'vue'
import type { FormInstance, FormRules } from 'element-plus'
import { getUserList } from '@/api/consumer'
import { regionDistrictOptions, regionEnabledCityOptions } from '@/api/service'
import feedback from '@/utils/feedback'

type RoleKey = 'butler' | 'director'
type PaymentEntryMode = 'online_pending' | 'offline_voucher' | 'offline_paid'
type ApiFn = (params?: any) => Promise<any>

const props = withDefaults(defineProps<{
    modelValue: boolean
    addOffline: ApiFn
    estimateOffline: ApiFn
    offlineMainPackages: ApiFn
    offlineRoleCandidates: ApiFn
    getAddonConfig: ApiFn
    loadStaffOptions?: ApiFn
    fixedMainStaff?: { id: number; name: string } | null
}>(), {
    fixedMainStaff: null
})

const emit = defineEmits<{
    (event: 'update:modelValue', value: boolean): void
    (event: 'created'): void
}>()

const paymentEntryModeOptions: Array<{ value: PaymentEntryMode; label: string }> = [
    { value: 'online_pending', label: '线上支付' },
    { value: 'offline_voucher', label: '线下凭证' },
    { value: 'offline_paid', label: '线下已收款' }
]
const paymentEntryMetaMap: Record<
    PaymentEntryMode,
    {
        title: string
        description: string
        tagText: string
        tagType: 'primary' | 'warning' | 'success'
        submitText: string
        amountLabel: string
        successText: string
        channelText: string
    }
> = {
    online_pending: {
        title: '创建后进入待支付，用户仅可在线完成支付。',
        description: '适用于后台代客户下单，但仍需走线上支付收款的场景。',
        tagText: '待线上支付',
        tagType: 'primary',
        submitText: '创建待支付订单',
        amountLabel: '待线上支付',
        successText: '后台订单已创建，待用户线上支付',
        channelText: '线上支付'
    },
    offline_voucher: {
        title: '创建后进入待支付，用户需线下付款并上传支付凭证。',
        description: '适用于客户已在线下付款流程中，需要补传凭证并由后台审核的场景。',
        tagText: '待上传线下凭证',
        tagType: 'warning',
        submitText: '创建待凭证订单',
        amountLabel: '待线下支付',
        successText: '后台订单已创建，待上传线下凭证',
        channelText: '线下支付'
    },
    offline_paid: {
        title: '创建后直接登记为已线下收款，不再经过待支付与凭证审核。',
        description: '适用于后台已确认现金、转账等线下收款完成的场景。',
        tagText: '线下已支付',
        tagType: 'success',
        submitText: '创建已收款订单',
        amountLabel: '线下实付',
        successText: '线下已收款订单已创建',
        channelText: '线下支付'
    }
}

const drawerVisible = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value)
})
const isFixedMainStaff = computed(() => Number(props.fixedMainStaff?.id || 0) > 0)
const fixedMainStaffName = computed(() => props.fixedMainStaff?.name || '当前服务人员')
const entryMeta = computed(
    () => paymentEntryMetaMap[offlineForm.payment_entry_mode as PaymentEntryMode] || paymentEntryMetaMap.offline_paid
)
const drawerTitle = computed(() => `${isFixedMainStaff.value ? '线下建单' : '后台建单'} · ${entryMeta.value.tagText}`)
const drawerDescription = computed(() => entryMeta.value.description)
const submitText = computed(() => entryMeta.value.submitText)
const primaryAmountLabel = computed(() => entryMeta.value.amountLabel)

const userLoading = ref(false)
const userOptions = ref<any[]>([])
const staffOptions = ref<any[]>([])
const cityOptions = ref<any[]>([])
const districtOptions = ref<any[]>([])
const packageOptions = ref<any[]>([])
const addonOptions = ref<any[]>([])
const roleCandidateMap = reactive<Record<RoleKey, any[]>>({
    butler: [],
    director: []
})
const submitting = ref(false)
const formRef = ref<FormInstance>()
const estimate = reactive({
    main_amount: 0,
    related_amount: 0,
    addon_amount: 0,
    total_amount: 0,
    discount_amount: 0,
    pay_amount: 0,
    payment_mode_desc: '线下已支付',
    payment_entry_mode: 'offline_paid',
    payment_entry_mode_desc: '线下已支付',
    payment_channel: 2,
    payment_channel_desc: '线下支付',
    deposit_amount: 0,
    balance_amount: 0,
    deposit_remark: ''
})
const offlineForm = reactive({
    payment_entry_mode: 'offline_paid' as PaymentEntryMode,
    bind_mode: 'user',
    user_id: undefined as number | undefined,
    contact_name: '',
    contact_mobile: '',
    service_date: '',
    province_code: '',
    province_name: '',
    city_code: '',
    city_name: '',
    district_code: '',
    district_name: '',
    service_address: '',
    main_staff_id: undefined as number | undefined,
    main_package_id: undefined as number | undefined,
    addon_ids: [] as number[],
    butler_staff_id: undefined as number | undefined,
    butler_package_id: undefined as number | undefined,
    director_staff_id: undefined as number | undefined,
    director_package_id: undefined as number | undefined,
    discount_amount: 0,
    admin_remark: ''
})

const offlineRules = reactive<FormRules>({
    payment_entry_mode: [{ required: true, message: '请选择付款录入方式', trigger: 'change' }],
    bind_mode: [{ required: true, message: '请选择客户类型', trigger: 'change' }],
    user_id: [{
        validator: (_rule, value, callback) => {
            if (offlineForm.bind_mode === 'user' && !value) {
                callback(new Error('请选择平台用户'))
                return
            }
            callback()
        },
        trigger: 'change'
    }],
    contact_name: [{ required: true, message: '请输入联系人', trigger: 'blur' }],
    contact_mobile: [{ required: true, message: '请输入联系电话', trigger: 'blur' }],
    service_date: [{ required: true, message: '请选择服务日期', trigger: 'change' }],
    city_code: [{ required: true, message: '请选择服务城市', trigger: 'change' }],
    district_code: [{ required: true, message: '请选择服务区县', trigger: 'change' }],
    main_staff_id: [{ required: true, message: '请选择主服务人员', trigger: 'change' }],
    main_package_id: [{ required: true, message: '请选择主套餐', trigger: 'change' }]
})

const offlineStep = computed(() => {
    if (!offlineForm.contact_name || !offlineForm.contact_mobile || (offlineForm.bind_mode === 'user' && !offlineForm.user_id)) {
        return 0
    }
    if (!offlineForm.service_date || !offlineForm.city_code || !offlineForm.district_code) {
        return 1
    }
    if (!offlineForm.main_staff_id || !offlineForm.main_package_id) {
        return 2
    }
    return 3
})
const selectedMainPackage = computed(() =>
    packageOptions.value.find((item) => Number(item.id) === Number(offlineForm.main_package_id || 0)) || null
)
const selectedRoleCandidateMap = computed<Record<RoleKey, any | null>>(() => ({
    butler: roleCandidateMap.butler.find((item) => Number(item.staff_id) === Number(offlineForm.butler_staff_id || 0)) || null,
    director: roleCandidateMap.director.find((item) => Number(item.staff_id) === Number(offlineForm.director_staff_id || 0)) || null
}))

const formatAmount = (value: number | string | undefined) => Number(value || 0).toFixed(2)

const resetEstimate = () => {
    Object.assign(estimate, {
        main_amount: 0,
        related_amount: 0,
        addon_amount: 0,
        total_amount: 0,
        discount_amount: Number(offlineForm.discount_amount || 0),
        pay_amount: 0,
        payment_mode_desc: entryMeta.value.tagText,
        payment_entry_mode: offlineForm.payment_entry_mode,
        payment_entry_mode_desc: entryMeta.value.tagText,
        payment_channel: offlineForm.payment_entry_mode === 'online_pending' ? 1 : 2,
        payment_channel_desc: entryMeta.value.channelText,
        deposit_amount: 0,
        balance_amount: 0,
        deposit_remark: ''
    })
}

const resetForm = () => {
    offlineForm.payment_entry_mode = 'offline_paid'
    offlineForm.bind_mode = 'user'
    offlineForm.user_id = undefined
    offlineForm.contact_name = ''
    offlineForm.contact_mobile = ''
    offlineForm.service_date = ''
    offlineForm.province_code = ''
    offlineForm.province_name = ''
    offlineForm.city_code = ''
    offlineForm.city_name = ''
    offlineForm.district_code = ''
    offlineForm.district_name = ''
    offlineForm.service_address = ''
    offlineForm.main_staff_id = isFixedMainStaff.value ? Number(props.fixedMainStaff?.id || 0) : undefined
    offlineForm.main_package_id = undefined
    offlineForm.addon_ids = []
    offlineForm.butler_staff_id = undefined
    offlineForm.butler_package_id = undefined
    offlineForm.director_staff_id = undefined
    offlineForm.director_package_id = undefined
    offlineForm.discount_amount = 0
    offlineForm.admin_remark = ''
    userOptions.value = []
    packageOptions.value = []
    addonOptions.value = []
    districtOptions.value = []
    roleCandidateMap.butler = []
    roleCandidateMap.director = []
    resetEstimate()
    formRef.value?.clearValidate()
}

const loadStaffList = async () => {
    if (isFixedMainStaff.value) {
        staffOptions.value = props.fixedMainStaff ? [{ id: props.fixedMainStaff.id, name: props.fixedMainStaff.name }] : []
        offlineForm.main_staff_id = Number(props.fixedMainStaff?.id || 0) || undefined
        return
    }
    if (!props.loadStaffOptions) {
        staffOptions.value = []
        return
    }
    const list = await props.loadStaffOptions({})
    staffOptions.value = Array.isArray(list) ? list : []
}

const loadCityOptions = async () => {
    const list = await regionEnabledCityOptions()
    cityOptions.value = Array.isArray(list) ? list : []
}

const loadDistrictOptions = async (cityCode: string) => {
    if (!cityCode) {
        districtOptions.value = []
        return
    }
    const list = await regionDistrictOptions({ city_code: cityCode })
    districtOptions.value = Array.isArray(list) ? list : []
}

const buildUserOptionLabel = (user: any) => {
    const name = user.nickname || user.real_name || user.account || `用户${user.id}`
    const mobile = user.mobile || user.account || '-'
    return `${name}（${mobile} / ID:${user.id}）`
}

const toUserOptions = (list: any[]) =>
    list
        .filter((item) => item && item.id)
        .map((item) => ({
            id: Number(item.id),
            nickname: item.nickname || item.real_name || item.account || '',
            mobile: item.mobile || '',
            label: buildUserOptionLabel(item),
            raw: item
        }))

const remoteUserSearch = async (keyword: string) => {
    const value = keyword.trim()
    if (!value) {
        userOptions.value = []
        return
    }
    userLoading.value = true
    try {
        const res = await getUserList({ keyword: value, page_no: 1, page_size: 20 })
        const lists = res?.lists ?? res?.data?.lists ?? res?.data ?? res ?? []
        userOptions.value = Array.isArray(lists) ? toUserOptions(lists) : []
    } catch (error) {
        userOptions.value = []
    } finally {
        userLoading.value = false
    }
}

const handleUserChange = (value: number) => {
    const selected = userOptions.value.find((item) => Number(item.id) === Number(value || 0))
    if (!selected) return
    const selectedUser = selected.raw || selected
    offlineForm.contact_name = selectedUser.nickname || selectedUser.real_name || selectedUser.account || offlineForm.contact_name
    offlineForm.contact_mobile = selectedUser.mobile || offlineForm.contact_mobile
}

const clearRoleSelection = (roleKey: RoleKey) => {
    if (roleKey === 'butler') {
        offlineForm.butler_staff_id = undefined
        offlineForm.butler_package_id = undefined
        return
    }
    offlineForm.director_staff_id = undefined
    offlineForm.director_package_id = undefined
}

const buildPayload = () => ({
    payment_entry_mode: offlineForm.payment_entry_mode,
    bind_mode: offlineForm.bind_mode,
    user_id: Number(offlineForm.user_id || 0),
    contact_name: offlineForm.contact_name,
    contact_mobile: offlineForm.contact_mobile,
    service_date: offlineForm.service_date,
    province_code: offlineForm.province_code,
    province_name: offlineForm.province_name,
    city_code: offlineForm.city_code,
    city_name: offlineForm.city_name,
    district_code: offlineForm.district_code,
    district_name: offlineForm.district_name,
    service_address: offlineForm.service_address,
    main_staff_id: Number(offlineForm.main_staff_id || 0),
    main_package_id: Number(offlineForm.main_package_id || 0),
    addon_ids: offlineForm.addon_ids.map((id) => Number(id)),
    butler_staff_id: Number(offlineForm.butler_staff_id || 0),
    butler_package_id: Number(offlineForm.butler_package_id || 0),
    director_staff_id: Number(offlineForm.director_staff_id || 0),
    director_package_id: Number(offlineForm.director_package_id || 0),
    discount_amount: Number(offlineForm.discount_amount || 0),
    admin_remark: offlineForm.admin_remark
})

const canLoadPackages = () =>
    !!offlineForm.main_staff_id && !!offlineForm.service_date && !!offlineForm.city_code && !!offlineForm.district_code

const loadPackageOptions = async () => {
    if (!canLoadPackages()) {
        packageOptions.value = []
        offlineForm.main_package_id = undefined
        return
    }
    const list = await props.offlineMainPackages(buildPayload())
    packageOptions.value = Array.isArray(list) ? list : []
    if (!packageOptions.value.some((item) => Number(item.id) === Number(offlineForm.main_package_id || 0))) {
        offlineForm.main_package_id = undefined
    }
}

const loadAddonOptions = async () => {
    if (!offlineForm.main_staff_id || !offlineForm.main_package_id) {
        addonOptions.value = []
        offlineForm.addon_ids = []
        return
    }
    const list = await props.getAddonConfig({
        staff_id: Number(offlineForm.main_staff_id || 0),
        package_id: Number(offlineForm.main_package_id || 0)
    })
    addonOptions.value = Array.isArray(list) ? list : []
    const validIds = new Set(addonOptions.value.map((item: any) => Number(item.id)))
    offlineForm.addon_ids = offlineForm.addon_ids.filter((id) => validIds.has(Number(id)))
}

const loadRoleCandidates = async (roleKey: RoleKey) => {
    if (!canLoadPackages()) {
        roleCandidateMap[roleKey] = []
        clearRoleSelection(roleKey)
        return
    }
    const list = await props.offlineRoleCandidates({ ...buildPayload(), role_key: roleKey })
    roleCandidateMap[roleKey] = Array.isArray(list) ? list : []
    const selectedStaffId = roleKey === 'butler' ? offlineForm.butler_staff_id : offlineForm.director_staff_id
    const selected = roleCandidateMap[roleKey].find((item) => Number(item.staff_id) === Number(selectedStaffId || 0))
    if (!selected) {
        clearRoleSelection(roleKey)
    }
}

const refreshEstimate = async () => {
    if (!drawerVisible.value) return
    if (!offlineForm.main_package_id || !canLoadPackages()) {
        resetEstimate()
        return
    }
    const data = await props.estimateOffline(buildPayload())
    Object.assign(estimate, data || {})
}

const refreshContextOptions = async () => {
    await Promise.all([loadPackageOptions(), loadRoleCandidates('butler'), loadRoleCandidates('director')])
}

const handleCityChange = async (cityCode: string) => {
    const selectedCity = cityOptions.value.find((item) => item.city_code === cityCode)
    offlineForm.city_name = selectedCity?.city_name || ''
    offlineForm.province_code = selectedCity?.province_code || ''
    offlineForm.province_name = selectedCity?.province_name || ''
    offlineForm.district_code = ''
    offlineForm.district_name = ''
    offlineForm.main_package_id = undefined
    clearRoleSelection('butler')
    clearRoleSelection('director')
    await loadDistrictOptions(cityCode)
}

const handleDistrictChange = () => {
    const selectedDistrict = districtOptions.value.find((item) => item.district_code === offlineForm.district_code)
    offlineForm.district_name = selectedDistrict?.district_name || ''
    offlineForm.main_package_id = undefined
    clearRoleSelection('butler')
    clearRoleSelection('director')
}

const handleMainStaffChange = async () => {
    if (isFixedMainStaff.value) {
        offlineForm.main_staff_id = Number(props.fixedMainStaff?.id || 0) || undefined
    }
    offlineForm.main_package_id = undefined
    offlineForm.addon_ids = []
    clearRoleSelection('butler')
    clearRoleSelection('director')
    await Promise.all([loadAddonOptions(), refreshContextOptions()])
}

const handleRoleCandidateChange = (roleKey: RoleKey, value: number | undefined) => {
    const selected = roleCandidateMap[roleKey].find((item) => Number(item.staff_id) === Number(value || 0))
    if (roleKey === 'butler') {
        offlineForm.butler_package_id = selected ? Number(selected.package_id || 0) : undefined
        return
    }
    offlineForm.director_package_id = selected ? Number(selected.package_id || 0) : undefined
}

const submitOfflineOrder = async () => {
    await formRef.value?.validate()
    submitting.value = true
    try {
        await props.addOffline(buildPayload())
        feedback.msgSuccess(entryMeta.value.successText)
        drawerVisible.value = false
        emit('created')
    } finally {
        submitting.value = false
    }
}

watch(drawerVisible, async (visible) => {
    if (!visible) return
    resetForm()
    await Promise.all([loadStaffList(), loadCityOptions()])
})

watch(() => props.fixedMainStaff?.id, (id) => {
    if (drawerVisible.value && isFixedMainStaff.value) {
        offlineForm.main_staff_id = Number(id || 0) || undefined
    }
})

watch(() => offlineForm.bind_mode, (mode) => {
    if (mode === 'temp') {
        offlineForm.user_id = undefined
    }
})

watch(() => JSON.stringify({
    service_date: offlineForm.service_date,
    city_code: offlineForm.city_code,
    district_code: offlineForm.district_code
}), async () => {
    if (!drawerVisible.value || !offlineForm.main_staff_id) return
    offlineForm.main_package_id = undefined
    clearRoleSelection('butler')
    clearRoleSelection('director')
    await refreshContextOptions()
})

watch(() => offlineForm.main_package_id, async () => {
    await loadAddonOptions()
})

watch(() => JSON.stringify({
    main_package_id: offlineForm.main_package_id,
    addon_ids: offlineForm.addon_ids,
    butler_staff_id: offlineForm.butler_staff_id,
    butler_package_id: offlineForm.butler_package_id,
    director_staff_id: offlineForm.director_staff_id,
    director_package_id: offlineForm.director_package_id,
    discount_amount: offlineForm.discount_amount,
    payment_entry_mode: offlineForm.payment_entry_mode
}), async () => {
    await refreshEstimate()
})
</script>

<style scoped>
.offline-order-drawer__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
}

.offline-order-drawer__body {
    padding-right: 4px;
}

.offline-section-card {
    border: 1px solid var(--el-border-color-light);
    border-radius: 16px;
    padding: 18px 18px 8px;
    background: linear-gradient(180deg, #fff8fb 0%, #ffffff 100%);
}

.offline-section-card--entry {
    background: linear-gradient(135deg, #fff7ed 0%, #fff8fb 58%, #ffffff 100%);
}

.offline-section-card + .offline-section-card {
    margin-top: 16px;
}

.offline-section-card__title {
    margin-bottom: 16px;
    font-size: 15px;
    font-weight: 600;
    color: #7a284d;
}

.offline-summary-panel {
    border-radius: 12px;
    background: #fff4f8;
    padding: 12px 14px;
    line-height: 1.8;
}

.offline-entry-radio {
    width: 100%;
}

.offline-entry-hint {
    margin-bottom: 10px;
    padding: 14px 16px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.92);
    border: 1px dashed #f3c5d6;
}

.offline-entry-hint__title {
    font-size: 14px;
    font-weight: 600;
    color: #7a284d;
}

.offline-entry-hint__desc {
    margin-top: 8px;
    font-size: 12px;
    line-height: 1.7;
    color: #6b7280;
}

.offline-addon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 10px 16px;
    width: 100%;
}

.offline-role-card {
    border: 1px solid #f4d3df;
    border-radius: 14px;
    padding: 14px;
    background: #fff;
}

.offline-role-card__title {
    margin-bottom: 10px;
    font-size: 14px;
    font-weight: 600;
}

.offline-role-card__desc {
    margin-top: 8px;
    font-size: 12px;
    line-height: 1.6;
}

.offline-amount-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 12px;
}

.offline-amount-card {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 14px;
    border-radius: 14px;
    background: #fff7f9;
    border: 1px solid #f4d3df;
}

.offline-amount-card--primary {
    background: linear-gradient(135deg, #ca8a04 0%, #db2777 100%);
    color: #fff;
    border-color: transparent;
}

.offline-entry-summary {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 14px;
    font-size: 12px;
    line-height: 1.7;
    color: #6b7280;
}

.offline-order-drawer__footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}
</style>
