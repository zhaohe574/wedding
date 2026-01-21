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
                                <el-table-column label="时段价格" width="100">
                                    <template #default="{ row, $index }">
                                        <el-button type="primary" link @click="openSlotPriceDialog($index)">
                                            配置{{ row.custom_slot_prices?.length ? `(${row.custom_slot_prices.length})` : '' }}
                                        </el-button>
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
                                <el-table-column label="时段价格" width="100">
                                    <template #default="{ row }">
                                        <span v-if="row.slot_prices?.length">{{ row.slot_prices.length }}个时段</span>
                                        <span v-else class="text-gray-400">未配置</span>
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

        <!-- 时段价格配置弹窗 -->
        <el-dialog v-model="showSlotPriceDialog" title="时段价格配置" width="600px">
            <div class="slot-price-list">
                <div
                    v-for="(slot, index) in currentSlotPrices"
                    :key="index"
                    class="flex items-center gap-4 mb-4"
                >
                    <el-time-picker
                        v-model="slot.start_time"
                        format="HH:mm"
                        value-format="HH:mm"
                        placeholder="开始时间"
                        style="width: 120px"
                    />
                    <span>至</span>
                    <el-time-picker
                        v-model="slot.end_time"
                        format="HH:mm"
                        value-format="HH:mm"
                        placeholder="结束时间"
                        style="width: 120px"
                    />
                    <el-input-number
                        v-model="slot.price"
                        :min="0"
                        :precision="2"
                        placeholder="价格"
                        style="width: 150px"
                    />
                    <el-button type="danger" :icon="Delete" circle @click="removeSlotPrice(index)" />
                </div>
            </div>
            <el-button type="primary" link @click="addSlotPrice">+ 添加时段</el-button>
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
                <el-form-item label="服务时长" prop="duration">
                    <el-input-number v-model="staffPackageForm.duration" :min="1" class="w-full" />
                    <span class="ml-2 text-gray-500">小时</span>
                </el-form-item>
                <el-form-item label="时段价格">
                    <div class="w-full">
                        <div
                            v-for="(slot, index) in staffPackageForm.slot_prices"
                            :key="index"
                            class="flex items-center gap-4 mb-2"
                        >
                            <el-time-picker
                                v-model="slot.start_time"
                                format="HH:mm"
                                value-format="HH:mm"
                                placeholder="开始时间"
                                style="width: 120px"
                            />
                            <span>至</span>
                            <el-time-picker
                                v-model="slot.end_time"
                                format="HH:mm"
                                value-format="HH:mm"
                                placeholder="结束时间"
                                style="width: 120px"
                            />
                            <el-input-number
                                v-model="slot.price"
                                :min="0"
                                :precision="2"
                                placeholder="价格"
                                style="width: 120px"
                            />
                            <el-button type="danger" :icon="Delete" circle @click="staffPackageForm.slot_prices.splice(index, 1)" />
                        </div>
                        <el-button type="primary" link @click="staffPackageForm.slot_prices.push({ start_time: '', end_time: '', price: 0 })">
                            + 添加时段
                        </el-button>
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
import { Delete } from '@element-plus/icons-vue'
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
const currentSlotPrices = ref<any[]>([])

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
    duration: 1,
    description: '',
    slot_prices: [] as any[]
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
    duration: [{ required: true, message: '请输入服务时长', trigger: 'blur' }]
})

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
        formData.packages = data.packages.map((p: any) => ({
            package_id: p.package_id,
            package_name: p.package?.name || '',
            default_price: p.package?.price || 0,
            custom_price: p.custom_price,
            custom_slot_prices: p.custom_slot_prices || [],
            status: p.status ?? 1
        }))
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
        formData.packages.push({
            package_id: pkg.id,
            package_name: pkg.name,
            default_price: pkg.price,
            custom_price: null,
            custom_slot_prices: [],
            status: 1
        })
    })
    showPackageDialog.value = false
    selectedPackages.value = []
}

const removePackage = (index: number) => {
    formData.packages.splice(index, 1)
}

// 时段价格相关
const openSlotPriceDialog = (index: number) => {
    currentSlotPriceIndex.value = index
    currentSlotPrices.value = JSON.parse(JSON.stringify(formData.packages[index].custom_slot_prices || []))
    showSlotPriceDialog.value = true
}

const addSlotPrice = () => {
    currentSlotPrices.value.push({ start_time: '', end_time: '', price: 0 })
}

const removeSlotPrice = (index: number) => {
    currentSlotPrices.value.splice(index, 1)
}

const confirmSlotPrice = () => {
    // 验证时段价格
    for (const slot of currentSlotPrices.value) {
        if (!slot.start_time || !slot.end_time) {
            ElMessage.error('请完整填写时段的开始和结束时间')
            return
        }
        if (slot.start_time >= slot.end_time) {
            ElMessage.error('结束时间必须大于开始时间')
            return
        }
    }
    formData.packages[currentSlotPriceIndex.value].custom_slot_prices = currentSlotPrices.value
    showSlotPriceDialog.value = false
}

// 创建专属套餐
const createStaffPackage = async () => {
    await staffPackageFormRef.value?.validate()
    if (!route.query.id) {
        ElMessage.error('请先保存人员基本信息')
        return
    }
    try {
        await staffCreatePackage({
            staff_id: route.query.id,
            ...staffPackageForm
        })
        ElMessage.success('创建成功')
        showStaffPackageDialog.value = false
        // 重置表单
        Object.assign(staffPackageForm, {
            name: '',
            category_id: '',
            price: 0,
            original_price: 0,
            duration: 1,
            description: '',
            slot_prices: []
        })
        // 刷新专属套餐列表
        await getStaffPackages()
    } catch (e: any) {
        ElMessage.error(e.message || '创建失败')
    }
}

const handleSave = async () => {
    await formRef.value?.validate()
    const params = {
        ...formData,
        packages: formData.packages.map(p => ({
            package_id: p.package_id,
            price: p.price || p.default_price,
            custom_price: p.custom_price,
            custom_slot_prices: p.custom_slot_prices || [],
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
</script>
