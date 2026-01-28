<template>
    <div class="staff-edit">
        <el-card class="!border-none" shadow="never">
            <el-page-header :content="$route.query.id ? '编辑人员' : '新增人员'" @back="$router.back()" />
        </el-card>
        <el-card class="mt-4 !border-none" shadow="never">
            <el-form
                ref="formRef"
                class="ls-form"
                :model="formData"
                label-width="100px"
                :rules="rules"
            >
                <el-tabs v-model="activeTab">
                    <el-tab-pane label="基本信息" name="basic">
                        <div class="grid grid-cols-2 gap-x-10">
                            <el-form-item label="姓名" prop="name">
                                <el-input v-model="formData.name" placeholder="请输入姓名" maxlength="50" />
                            </el-form-item>
                            <el-form-item label="手机号" prop="mobile">
                                <el-input v-model="formData.mobile" placeholder="请输入手机号" maxlength="11" />
                            </el-form-item>
                            <el-form-item label="头像" prop="avatar">
                                <material-picker v-model="formData.avatar" :limit="1" />
                            </el-form-item>
                            <el-form-item label="服务分类" prop="category_id">
                                <el-cascader
                                    v-model="formData.category_id"
                                    :options="optionsData.categories"
                                    :props="{ value: 'id', label: 'name', checkStrictly: true, emitPath: false }"
                                    placeholder="选择服务分类"
                                    class="w-full"
                                />
                            </el-form-item>
                            <el-form-item label="服务价格" prop="price">
                                <el-input-number v-model="formData.price" :min="0" :precision="2" class="w-full" />
                            </el-form-item>
                            <el-form-item label="从业年限" prop="experience_years">
                                <el-input-number v-model="formData.experience_years" :min="0" :max="50" class="w-full" />
                            </el-form-item>
                            <el-form-item label="排序" prop="sort">
                                <el-input-number v-model="formData.sort" :min="0" :max="9999" class="w-full" />
                            </el-form-item>
                            <el-form-item label="是否推荐" prop="is_recommend">
                                <el-radio-group v-model="formData.is_recommend">
                                    <el-radio :value="1">是</el-radio>
                                    <el-radio :value="0">否</el-radio>
                                </el-radio-group>
                            </el-form-item>
                            <el-form-item label="状态" prop="status">
                                <el-radio-group v-model="formData.status">
                                    <el-radio :value="1">启用</el-radio>
                                    <el-radio :value="0">禁用</el-radio>
                                </el-radio-group>
                            </el-form-item>
                        </div>
                        <el-form-item label="个人简介" prop="profile">
                            <el-input
                                v-model="formData.profile"
                                type="textarea"
                                :rows="3"
                                placeholder="请输入个人简介"
                                maxlength="500"
                                show-word-limit
                            />
                        </el-form-item>
                        <el-form-item label="服务说明" prop="service_desc">
                            <el-input
                                v-model="formData.service_desc"
                                type="textarea"
                                :rows="3"
                                placeholder="请输入服务说明"
                                maxlength="1000"
                                show-word-limit
                            />
                        </el-form-item>
                    </el-tab-pane>

                    <el-tab-pane label="标签设置" name="tags">
                        <el-form-item label="风格标签">
                            <el-checkbox-group v-model="formData.tag_ids">
                                <template v-for="(tags, type) in groupedTags" :key="type">
                                    <div class="mb-4">
                                        <div class="text-gray-500 mb-2">{{ type }}</div>
                                        <el-checkbox
                                            v-for="tag in tags"
                                            :key="tag.id"
                                            :value="tag.id"
                                            :label="tag.name"
                                        />
                                    </div>
                                </template>
                            </el-checkbox-group>
                        </el-form-item>
                    </el-tab-pane>

                    <el-tab-pane label="服务套餐" name="packages">
                        <el-form-item label="关联全局套餐">
                            <el-table :data="formData.packages" border>
                                <el-table-column label="套餐名称" prop="package_name" min-width="150" />
                                <el-table-column label="默认价格" prop="default_price" width="100">
                                    <template #default="{ row }">¥{{ row.default_price }}</template>
                                </el-table-column>
                                <el-table-column label="个人价格" width="150">
                                    <template #default="{ row }">
                                        <el-input-number
                                            v-model="row.custom_price"
                                            :min="0"
                                            :precision="2"
                                            size="small"
                                            controls-position="right"
                                            placeholder="不填则使用默认"
                                        />
                                    </template>
                                </el-table-column>
                                <el-table-column label="场次价格" width="100">
                                    <template #default="{ row, $index }">
                                        <el-button v-if="row.booking_type === 1" type="primary" link @click="openSlotPriceDialog($index)">
                                            配置{{ row.custom_slot_prices?.length ? `(${row.custom_slot_prices.length})` : '' }}
                                        </el-button>
                                        <span v-else class="text-gray-500">全天</span>
                                    </template>
                                </el-table-column>
                                <el-table-column label="预约类型" width="140">
                                    <template #default="{ row }">
                                        <el-select v-model="row.booking_type" size="small" class="w-full" @change="handlePackageBookingTypeChange(row)">
                                            <el-option label="全天套餐" :value="0" />
                                            <el-option label="分场次套餐" :value="1" />
                                        </el-select>
                                    </template>
                                </el-table-column>
                                <el-table-column label="允许场次" min-width="180">
                                    <template #default="{ row }">
                                        <el-checkbox-group
                                            v-if="row.booking_type === 1"
                                            v-model="row.allowed_time_slots"
                                            size="small"
                                        >
                                            <el-checkbox
                                                v-for="slot in timeSlotOptions"
                                                :key="slot.value"
                                                :value="slot.value"
                                                :label="slot.label"
                                            />
                                        </el-checkbox-group>
                                        <span v-else class="text-gray-500">全天</span>
                                    </template>
                                </el-table-column>
                                <el-table-column label="状态" width="100">
                                    <template #default="{ row }">
                                        <el-switch v-model="row.status" :active-value="1" :inactive-value="0" />
                                    </template>
                                </el-table-column>
                                <el-table-column label="操作" width="80">
                                    <template #default="{ $index }">
                                        <el-button type="danger" link @click="removePackage($index)">移除</el-button>
                                    </template>
                                </el-table-column>
                            </el-table>
                            <el-button class="mt-4" @click="showPackageDialog = true">添加套餐</el-button>
                        </el-form-item>

                        <!-- 专属套餐 -->
                        <el-divider content-position="left">专属套餐</el-divider>
                        <el-form-item label="专属套餐">
                            <el-table :data="staffPackages" border>
                                <el-table-column label="套餐名称" prop="name" min-width="150" />
                                <el-table-column label="价格" prop="price" width="100">
                                    <template #default="{ row }">¥{{ row.price }}</template>
                                </el-table-column>
                                <el-table-column label="场次价格" width="100">
                                    <template #default="{ row }">
                                        <span v-if="row.slot_prices?.length">{{ row.slot_prices.length }}个场次</span>
                                        <span v-else class="text-gray-400">未配置</span>
                                    </template>
                                </el-table-column>
                                <el-table-column label="预约类型" width="120">
                                    <template #default="{ row }">
                                        <el-tag v-if="row.booking_type === 0" type="info">全天套餐</el-tag>
                                        <el-tag v-else type="warning">分场次套餐</el-tag>
                                    </template>
                                </el-table-column>
                                <el-table-column label="允许场次" min-width="160">
                                    <template #default="{ row }">
                                        <span v-if="row.booking_type !== 1" class="text-gray-500">全天</span>
                                        <span v-else>
                                            <el-tag
                                                v-for="slot in normalizeAllowedSlots(row.allowed_time_slots)"
                                                :key="slot"
                                                class="mr-1"
                                            >
                                                {{ timeSlotLabelMap[slot] || slot }}
                                            </el-tag>
                                        </span>
                                    </template>
                                </el-table-column>
                                <el-table-column label="状态" prop="is_show" width="100">
                                    <template #default="{ row }">
                                        <el-tag :type="row.is_show ? 'success' : 'info'">
                                            {{ row.is_show ? '上架' : '下架' }}
                                        </el-tag>
                                    </template>
                                </el-table-column>
                            </el-table>
                            <el-button class="mt-4" type="primary" @click="showStaffPackageDialog = true">
                                创建专属套餐
                            </el-button>
                        </el-form-item>
                    </el-tab-pane>
                </el-tabs>
            </el-form>
        </el-card>
        <footer-btns>
            <el-button type="primary" @click="handleSave">保存</el-button>
        </footer-btns>

        <!-- 套餐选择弹窗 -->
        <el-dialog v-model="showPackageDialog" title="选择套餐" width="600px">
            <el-table
                :data="availablePackages"
                @selection-change="handlePackageSelect"
                ref="packageTableRef"
            >
                <el-table-column type="selection" width="55" />
                <el-table-column label="套餐名称" prop="name" />
                <el-table-column label="分类" prop="category_name" />
                <el-table-column label="价格" prop="price" width="100">
                    <template #default="{ row }">¥{{ row.price }}</template>
                </el-table-column>
            </el-table>
            <template #footer>
                <el-button @click="showPackageDialog = false">取消</el-button>
                <el-button type="primary" @click="confirmPackageSelect">确定</el-button>
            </template>
        </el-dialog>

        <!-- 场次价格配置弹窗 -->
        <el-dialog v-model="showSlotPriceDialog" title="场次价格配置" width="600px">
            <div class="text-xs text-gray-500 mb-2">未填写的场次将使用默认价格</div>
            <div class="slot-price-list">
                <div
                    v-for="(slot, index) in currentSlotPrices"
                    :key="index"
                    class="flex items-center gap-4 mb-4"
                >
                    <span class="w-16">{{ timeSlotLabelMap[slot.time_slot] || slot.time_slot }}</span>
                    <el-input-number
                        v-model="slot.price"
                        :min="0"
                        :precision="2"
                        placeholder="价格"
                        style="width: 150px"
                    />
                </div>
            </div>
            <template #footer>
                <el-button @click="showSlotPriceDialog = false">取消</el-button>
                <el-button type="primary" @click="confirmSlotPrice">确定</el-button>
            </template>
        </el-dialog>

        <!-- 创建专属套餐弹窗 -->
        <el-dialog v-model="showStaffPackageDialog" title="创建专属套餐" width="700px">
            <el-form
                ref="staffPackageFormRef"
                :model="staffPackageForm"
                :rules="staffPackageRules"
                label-width="100px"
            >
                <el-form-item label="套餐名称" prop="name">
                    <el-input v-model="staffPackageForm.name" placeholder="请输入套餐名称" />
                </el-form-item>
                <el-form-item label="所属分类" prop="category_id">
                    <el-cascader
                        v-model="staffPackageForm.category_id"
                        :options="optionsData.categories"
                        :props="{ value: 'id', label: 'name', checkStrictly: true, emitPath: false }"
                        placeholder="选择服务分类"
                        class="w-full"
                    />
                </el-form-item>
                <el-form-item label="默认价格" prop="price">
                    <el-input-number v-model="staffPackageForm.price" :min="0" :precision="2" class="w-full" />
                </el-form-item>
                <el-form-item label="原价" prop="original_price">
                    <el-input-number v-model="staffPackageForm.original_price" :min="0" :precision="2" class="w-full" />
                </el-form-item>
                <el-form-item label="预约类型" prop="booking_type">
                    <el-radio-group v-model="staffPackageForm.booking_type">
                        <el-radio :value="0">全天套餐</el-radio>
                        <el-radio :value="1">分场次套餐</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item v-if="staffPackageForm.booking_type === 1" label="允许场次" prop="allowed_time_slots">
                    <el-checkbox-group v-model="staffPackageForm.allowed_time_slots">
                        <el-checkbox
                            v-for="slot in timeSlotOptions"
                            :key="slot.value"
                            :value="slot.value"
                            :label="slot.label"
                        />
                    </el-checkbox-group>
                </el-form-item>
                <el-form-item v-if="staffPackageForm.booking_type === 1" label="场次价格">
                    <div class="w-full">
                        <div
                            v-for="(slot, index) in staffPackageForm.slot_prices"
                            :key="index"
                            class="flex items-center gap-4 mb-2"
                        >
                            <span class="w-16">{{ timeSlotLabelMap[slot.time_slot] || slot.time_slot }}</span>
                            <el-input-number
                                v-model="slot.price"
                                :min="0"
                                :precision="2"
                                placeholder="价格"
                                style="width: 120px"
                            />
                        </div>
                    </div>
                </el-form-item>
                <el-form-item label="套餐说明" prop="description">
                    <el-input
                        v-model="staffPackageForm.description"
                        type="textarea"
                        :rows="3"
                        placeholder="请输入套餐说明"
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="showStaffPackageDialog = false">取消</el-button>
                <el-button type="primary" @click="createStaffPackage">创建</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup name="staffListsEdit">
import type { FormInstance } from 'element-plus'
import { staffAdd, staffEdit, staffDetail, staffCreatePackage, staffGetPackageConfig } from '@/api/staff'
import { categoryTree, styleTagAll, packageAll } from '@/api/service'
import { useDictOptions } from '@/hooks/useDictOptions'
import useMultipleTabs from '@/hooks/useMultipleTabs'
import { ElMessage } from 'element-plus'

const route = useRoute()
const router = useRouter()
const { removeTab } = useMultipleTabs()
const formRef = shallowRef<FormInstance>()
const staffPackageFormRef = shallowRef<FormInstance>()
const activeTab = ref('basic')
const showPackageDialog = ref(false)
const showSlotPriceDialog = ref(false)
const showStaffPackageDialog = ref(false)
const selectedPackages = ref<any[]>([])
const groupedTags = ref<Record<string, any[]>>({})
const staffPackages = ref<any[]>([])  // 专属套餐列表
const currentSlotPriceIndex = ref(-1)
const currentSlotPrices = ref<{ time_slot: number; price: number | null }[]>([])

const formData = reactive({
    id: '',
    name: '',
    avatar: '',
    mobile: '',
    category_id: '',
    price: 0,
    experience_years: 0,
    profile: '',
    service_desc: '',
    sort: 0,
    is_recommend: 0,
    status: 1,
    tag_ids: [] as number[],
    packages: [] as any[]
})

// 专属套餐表单
const staffPackageForm = reactive({
    name: '',
    category_id: '',
    price: 0,
    original_price: 0,
    description: '',
    slot_prices: [] as any[],
    booking_type: 0,
    allowed_time_slots: [] as number[]
})

const rules = reactive({
    name: [{ required: true, message: '请输入姓名', trigger: 'blur' }],
    category_id: [{ required: true, message: '请选择服务分类', trigger: 'change' }],
    price: [{ required: true, message: '请输入服务价格', trigger: 'blur' }]
})

const staffPackageRules = reactive({
    name: [{ required: true, message: '请输入套餐名称', trigger: 'blur' }],
    category_id: [{ required: true, message: '请选择所属分类', trigger: 'change' }],
    price: [{ required: true, message: '请输入套餐价格', trigger: 'blur' }],
    booking_type: [{ required: true, message: '请选择预约类型', trigger: 'change' }]
})

const timeSlotOptions = [
    { value: 1, label: '早礼' },
    { value: 2, label: '午宴' },
    { value: 3, label: '晚宴' }
]

const timeSlotLabelMap: Record<number, string> = {
    1: '早礼',
    2: '午宴',
    3: '晚宴'
}

const normalizeAllowedSlots = (value: any) => {
    if (Array.isArray(value)) {
        return value.map((item) => Number(item)).filter((item) => !Number.isNaN(item))
    }
    if (typeof value === 'string' && value) {
        try {
            const parsed = JSON.parse(value)
            if (Array.isArray(parsed)) {
                return parsed.map((item) => Number(item)).filter((item) => !Number.isNaN(item))
            }
        } catch (e) {
            return []
        }
    }
    return []
}

const normalizeSlotPrices = (value: any) => {
    if (Array.isArray(value)) {
        return value
    }
    if (typeof value === 'string' && value) {
        try {
            const parsed = JSON.parse(value)
            if (Array.isArray(parsed)) {
                return parsed
            }
        } catch (e) {
            return []
        }
    }
    return []
}

const resolveAllowedSlots = (row: any) => {
    if (row.booking_type !== 1) {
        return []
    }
    const allowed = normalizeAllowedSlots(row.allowed_time_slots)
    return allowed.length ? allowed : timeSlotOptions.map((item) => item.value)
}

const buildSlotPriceRows = (allowedSlots: number[], slotPrices: any[]) => {
    return allowedSlots.map((slot) => {
        const matched = slotPrices.find((item: any) => Number(item.time_slot) === slot)
        return {
            time_slot: slot,
            price: matched?.price ?? null
        }
    })
}

const normalizeSlotPricePayload = (slots: { time_slot: number; price: number | null }[]) => {
    return slots
        .filter((slot) => slot.price !== null && slot.price !== undefined && slot.price !== '')
        .map((slot) => ({
            time_slot: Number(slot.time_slot),
            price: Number(slot.price)
        }))
}

const syncStaffPackageSlotPrices = () => {
    if (staffPackageForm.booking_type !== 1) {
        staffPackageForm.slot_prices = []
        return
    }
    const allowedSlots = staffPackageForm.allowed_time_slots.length
        ? staffPackageForm.allowed_time_slots
        : timeSlotOptions.map((item) => item.value)
    const slotPrices = normalizeSlotPrices(staffPackageForm.slot_prices)
    staffPackageForm.slot_prices = buildSlotPriceRows(allowedSlots, slotPrices)
}
const { optionsData } = useDictOptions<{
    categories: any[]
    packages: any[]
}>({
    categories: {
        api: categoryTree
    },
    packages: {
        api: () => packageAll({ global_only: 1 })  // 只获取全局套餐
    }
})

// 可用套餐（未添加的全局套餐）
const availablePackages = computed(() => {
    const addedIds = formData.packages.map(p => p.package_id)
    return optionsData.packages?.filter(p => !addedIds.includes(p.id)) || []
})

// 获取标签列表（分组）
const getTags = async () => {
    const res = await styleTagAll({ group_by_type: 1 })
    groupedTags.value = res
}

// 获取详情
const getDetails = async () => {
    const data = await staffDetail({ id: route.query.id })
    Object.keys(formData).forEach((key) => {
        if (data[key] !== undefined) {
            (formData as any)[key] = data[key]
        }
    })
    // 处理套餐数据格式
    if (data.packages) {
        formData.packages = data.packages.map((p: any) => {
            const bookingType = p.booking_type ?? p.package?.booking_type ?? 0
            let allowedSlots = normalizeAllowedSlots(p.allowed_time_slots ?? p.package?.allowed_time_slots)
            if (bookingType === 1 && allowedSlots.length === 0) {
                allowedSlots = timeSlotOptions.map((item) => item.value)
            }
            return {
                package_id: p.package_id,
                package_name: p.package?.name || '',
                default_price: p.package?.price || 0,
                custom_price: p.custom_price,
                custom_slot_prices: p.custom_slot_prices || [],
                booking_type: bookingType,
                allowed_time_slots: allowedSlots,
                status: p.status ?? 1
            }
        })
    }
    // 获取专属套餐
    await getStaffPackages()
}

// 获取专属套餐列表
const getStaffPackages = async () => {
    if (!route.query.id) return
    try {
        const res = await staffGetPackageConfig({ 
            staff_id: route.query.id, 
            include_global: false 
        })
        staffPackages.value = res.staff_packages || []
    } catch (e) {
        console.error('获取专属套餐失败', e)
    }
}

const handlePackageSelect = (selection: any[]) => {
    selectedPackages.value = selection
}

const confirmPackageSelect = () => {
    selectedPackages.value.forEach(pkg => {
        const bookingType = pkg.booking_type ?? 0
        let allowedSlots = normalizeAllowedSlots(pkg.allowed_time_slots)
        if (bookingType === 1 && allowedSlots.length === 0) {
            allowedSlots = timeSlotOptions.map((item) => item.value)
        }
        formData.packages.push({
            package_id: pkg.id,
            package_name: pkg.name,
            default_price: pkg.price,
            custom_price: null,
            custom_slot_prices: [],
            booking_type: bookingType,
            allowed_time_slots: allowedSlots,
            status: 1
        })
    })
    showPackageDialog.value = false
    selectedPackages.value = []
}

const removePackage = (index: number) => {
    formData.packages.splice(index, 1)
}

// 场次价格相关
const openSlotPriceDialog = (index: number) => {
    const row = formData.packages[index]
    if (row.booking_type !== 1) {
        ElMessage.warning('全天套餐无需配置场次价格')
        return
    }
    currentSlotPriceIndex.value = index
    const allowedSlots = resolveAllowedSlots(row)
    const slotPrices = normalizeSlotPrices(row.custom_slot_prices)
    currentSlotPrices.value = buildSlotPriceRows(allowedSlots, slotPrices)
    showSlotPriceDialog.value = true
}

const confirmSlotPrice = () => {
    const payload = normalizeSlotPricePayload(currentSlotPrices.value)
    for (const slot of payload) {
        if (Number.isNaN(slot.price) || slot.price < 0) {
            ElMessage.error('价格不能为负数')
            return
        }
    }
    formData.packages[currentSlotPriceIndex.value].custom_slot_prices = payload
    showSlotPriceDialog.value = false
}

const handlePackageBookingTypeChange = (row: any) => {
    if (row.booking_type === 0) {
        row.allowed_time_slots = []
        row.custom_slot_prices = []
        return
    }
    if (!row.allowed_time_slots || row.allowed_time_slots.length === 0) {
        row.allowed_time_slots = timeSlotOptions.map((item) => item.value)
    }
}

// 创建专属套餐
const createStaffPackage = async () => {
    await staffPackageFormRef.value?.validate()
    if (!route.query.id) {
        ElMessage.error('请先保存人员基本信息')
        return
    }
    if (staffPackageForm.booking_type === 1 && staffPackageForm.allowed_time_slots.length === 0) {
        ElMessage.error('请选择允许场次')
        return
    }
    if (staffPackageForm.booking_type === 0) {
        staffPackageForm.allowed_time_slots = []
    }
    const slotPricesPayload = staffPackageForm.booking_type === 1
        ? normalizeSlotPricePayload(staffPackageForm.slot_prices)
        : []
    for (const slot of slotPricesPayload) {
        if (Number.isNaN(slot.price) || slot.price < 0) {
            ElMessage.error('价格不能为负数')
            return
        }
    }
    try {
        await staffCreatePackage({
            staff_id: route.query.id,
            ...staffPackageForm,
            slot_prices: slotPricesPayload
        })
        ElMessage.success('创建成功')
        showStaffPackageDialog.value = false
        // 重置表单
        Object.assign(staffPackageForm, {
            name: '',
            category_id: '',
            price: 0,
            original_price: 0,
            description: '',
            slot_prices: [],
            booking_type: 0,
            allowed_time_slots: []
        })
        // 刷新专属套餐列表
        await getStaffPackages()
    } catch (e: any) {
        ElMessage.error(e.message || '创建失败')
    }
}

const handleSave = async () => {
    await formRef.value?.validate()
    const invalidPackage = formData.packages.find((pkg) => pkg.booking_type === 1 && (!pkg.allowed_time_slots || pkg.allowed_time_slots.length === 0))
    if (invalidPackage) {
        ElMessage.error('请选择允许场次')
        return
    }
    const params = {
        ...formData,
        packages: formData.packages.map(p => ({
            package_id: p.package_id,
            price: p.price || p.default_price,
            custom_price: p.custom_price,
            custom_slot_prices: p.custom_slot_prices || [],
            booking_type: p.booking_type ?? 0,
            allowed_time_slots: p.booking_type === 1 ? (p.allowed_time_slots || []) : [],
            status: p.status ?? 1
        }))
    }
    if (route.query.id) {
        await staffEdit(params)
    } else {
        await staffAdd(params)
    }
    removeTab()
    router.back()
}

getTags()
if (route.query.id) {
    getDetails()
}

watch(
    () => staffPackageForm.booking_type,
    (value) => {
        if (value === 0) {
            staffPackageForm.allowed_time_slots = []
            syncStaffPackageSlotPrices()
            return
        }
        if (!staffPackageForm.allowed_time_slots || staffPackageForm.allowed_time_slots.length === 0) {
            staffPackageForm.allowed_time_slots = timeSlotOptions.map((item) => item.value)
        }
        syncStaffPackageSlotPrices()
    }
)

watch(
    () => staffPackageForm.allowed_time_slots,
    () => {
        if (staffPackageForm.booking_type === 1) {
            syncStaffPackageSlotPrices()
        }
    },
    { deep: true }
)
</script>
