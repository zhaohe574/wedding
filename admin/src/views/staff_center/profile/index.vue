<template>
    <div class="staff-center-profile">
        <el-card class="!border-none mb-4" shadow="never">
            <template #header>
                <div class="flex items-center justify-between">
                    <span class="text-lg font-bold">我的资料中心</span>
                    <el-button type="primary" :loading="saveLoading" @click="handleSaveProfile">保存资料</el-button>
                </div>
            </template>

            <el-form ref="formRef" :model="formData" :rules="formRules" label-width="100px">
                <div class="grid grid-cols-2 gap-x-8">
                    <el-form-item label="头像" prop="avatar">
                        <material-picker v-model="formData.avatar" :limit="1" />
                    </el-form-item>
                    <el-form-item label="姓名" prop="name">
                        <el-input v-model="formData.name" maxlength="50" placeholder="请输入姓名" />
                    </el-form-item>
                    <el-form-item label="手机号" prop="mobile">
                        <el-input v-model="formData.mobile" maxlength="11" placeholder="请输入手机号" />
                    </el-form-item>
                    <el-form-item label="服务分类" prop="category_id">
                        <el-cascader
                            v-model="formData.category_id"
                            :options="categoryOptions"
                            :props="{ value: 'id', label: 'name', checkStrictly: true, emitPath: false }"
                            placeholder="请选择服务分类"
                            class="w-full"
                        />
                    </el-form-item>
                    <el-form-item label="服务价格" prop="price">
                        <el-input-number v-model="formData.price" :min="0" :precision="2" class="w-full" />
                    </el-form-item>
                    <el-form-item label="从业年限" prop="experience_years">
                        <el-input-number v-model="formData.experience_years" :min="0" :max="50" class="w-full" />
                    </el-form-item>
                </div>

                <el-form-item label="个人简介" prop="profile">
                    <el-input v-model="formData.profile" type="textarea" :rows="3" maxlength="500" show-word-limit />
                </el-form-item>

                <el-form-item label="服务说明" prop="service_desc">
                    <el-input v-model="formData.service_desc" type="textarea" :rows="3" maxlength="1000" show-word-limit />
                </el-form-item>

                <el-form-item label="风格标签">
                    <el-checkbox-group v-model="formData.tag_ids">
                        <template v-for="(tags, type) in groupedTags" :key="type">
                            <div class="mb-3 w-full">
                                <div class="text-gray-500 mb-1">{{ tagTypeLabelMap[type] || type }}</div>
                                <el-checkbox v-for="tag in tags" :key="tag.id" :label="tag.id">{{ tag.name }}</el-checkbox>
                            </div>
                        </template>
                    </el-checkbox-group>
                </el-form-item>
            </el-form>
        </el-card>

        <el-card class="!border-none mb-4" shadow="never">
            <template #header>
                <div class="flex items-center justify-between">
                    <span class="text-lg font-bold">套餐配置</span>
                    <el-button @click="loadPackageConfig">刷新</el-button>
                </div>
            </template>

            <el-table :data="configuredPackages" size="large">
                <el-table-column label="套餐名称" prop="package_name" min-width="160" />
                <el-table-column label="默认价格" width="140">
                    <template #default="{ row }">
                        <el-input-number v-model="row.price" :min="0" :precision="2" size="small" />
                    </template>
                </el-table-column>
                <el-table-column label="原价" width="140">
                    <template #default="{ row }">
                        <el-input-number v-model="row.original_price" :min="0" :precision="2" size="small" />
                    </template>
                </el-table-column>
                <el-table-column label="个人价格" width="140">
                    <template #default="{ row }">
                        <el-input-number v-model="row.custom_price" :min="0" :precision="2" size="small" />
                    </template>
                </el-table-column>
                <el-table-column label="预约类型" width="140">
                    <template #default="{ row }">
                        <el-select v-model="row.booking_type" size="small">
                            <el-option label="全天套餐" :value="0" />
                            <el-option label="分场次套餐" :value="1" />
                        </el-select>
                    </template>
                </el-table-column>
                <el-table-column label="允许场次" min-width="200">
                    <template #default="{ row }">
                        <el-checkbox-group v-if="row.booking_type === 1" v-model="row.allowed_time_slots">
                            <el-checkbox v-for="slot in timeSlotOptions" :key="slot.value" :label="slot.value">{{ slot.label }}</el-checkbox>
                        </el-checkbox-group>
                        <span v-else class="text-gray-500">全天</span>
                    </template>
                </el-table-column>
                <el-table-column label="状态" width="90">
                    <template #default="{ row }">
                        <el-switch v-model="row.status" :active-value="1" :inactive-value="0" />
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="100" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="savePackageConfig(row)">保存</el-button>
                    </template>
                </el-table-column>
            </el-table>

            <el-divider content-position="left">专属套餐</el-divider>

            <div class="mb-3">
                <el-button type="primary" @click="openCreateStaffPackage">创建专属套餐</el-button>
            </div>

            <el-table :data="staffPackages" size="large">
                <el-table-column label="套餐名称" prop="name" min-width="160" />
                <el-table-column label="分类" prop="category_name" min-width="140" />
                <el-table-column label="价格" width="120">
                    <template #default="{ row }">¥{{ row.price }}</template>
                </el-table-column>
                <el-table-column label="预约类型" width="120">
                    <template #default="{ row }">
                        <el-tag :type="row.booking_type === 1 ? 'warning' : 'info'">
                            {{ row.booking_type === 1 ? '分场次' : '全天' }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="row.is_show ? 'success' : 'info'">
                            {{ row.is_show ? '上架' : '下架' }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="160" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="openEditStaffPackage(row)">编辑</el-button>
                        <el-button type="danger" link @click="handleDeleteStaffPackage(row)">删除</el-button>
                    </template>
                </el-table-column>
            </el-table>
        </el-card>

        <el-card class="!border-none" shadow="never">
            <template #header>
                <div class="flex items-center justify-between">
                    <span class="text-lg font-bold">轮播图设置</span>
                    <div class="flex gap-2">
                        <el-button type="primary" @click="openBannerDialog()">新增轮播图</el-button>
                        <el-button @click="saveBannerConfig">保存轮播配置</el-button>
                    </div>
                </div>
            </template>

            <el-form :model="bannerConfig" label-width="110px">
                <div class="grid grid-cols-2 gap-x-8">
                    <el-form-item label="展示模式">
                        <el-radio-group v-model="bannerConfig.banner_mode">
                            <el-radio :label="1">小图模式</el-radio>
                            <el-radio :label="2">大图模式</el-radio>
                        </el-radio-group>
                    </el-form-item>
                    <el-form-item label="指示器样式">
                        <el-select v-model="bannerConfig.banner_indicator_style" class="w-full">
                            <el-option label="圆点" :value="1" />
                            <el-option label="数字" :value="2" />
                            <el-option label="进度条" :value="3" />
                            <el-option label="无" :value="0" />
                        </el-select>
                    </el-form-item>
                    <el-form-item label="小图高度">
                        <el-input-number v-model="bannerConfig.banner_small_height" :min="200" :max="1200" class="w-full" />
                    </el-form-item>
                    <el-form-item label="大图高度">
                        <el-input-number v-model="bannerConfig.banner_large_height" :min="200" :max="1200" class="w-full" />
                    </el-form-item>
                    <el-form-item label="自动轮播">
                        <el-switch v-model="bannerConfig.banner_autoplay" :active-value="1" :inactive-value="0" />
                    </el-form-item>
                    <el-form-item label="轮播间隔(ms)">
                        <el-input-number v-model="bannerConfig.banner_interval" :min="1000" :step="500" class="w-full" />
                    </el-form-item>
                </div>
            </el-form>

            <el-table :data="bannerList" size="large">
                <el-table-column label="预览" width="100">
                    <template #default="{ row }">
                        <el-image :src="row.cover_url || row.file_url" fit="cover" style="width: 48px; height: 48px" />
                    </template>
                </el-table-column>
                <el-table-column label="类型" width="90">
                    <template #default="{ row }">
                        <el-tag :type="row.type === 2 ? 'warning' : 'success'">
                            {{ row.type === 2 ? '视频' : '图片' }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="资源地址" prop="file_url" min-width="260" show-overflow-tooltip />
                <el-table-column label="自动播放" width="100">
                    <template #default="{ row }">
                        {{ row.is_autoplay ? '是' : '否' }}
                    </template>
                </el-table-column>
                <el-table-column label="排序" prop="sort" width="80" />
                <el-table-column label="操作" width="220" fixed="right">
                    <template #default="{ row, $index }">
                        <el-button type="primary" link @click="openBannerDialog(row)">编辑</el-button>
                        <el-button type="danger" link @click="handleDeleteBanner(row)">删除</el-button>
                        <el-button :disabled="$index === 0" type="primary" link @click="moveBanner($index, 'up')">上移</el-button>
                        <el-button
                            :disabled="$index === bannerList.length - 1"
                            type="primary"
                            link
                            @click="moveBanner($index, 'down')"
                        >
                            下移
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
        </el-card>

        <el-dialog v-model="staffPackageDialogVisible" :title="staffPackageForm.id ? '编辑专属套餐' : '创建专属套餐'" width="640px">
            <el-form ref="staffPackageFormRef" :model="staffPackageForm" :rules="staffPackageRules" label-width="110px">
                <el-form-item label="套餐名称" prop="name">
                    <el-input v-model="staffPackageForm.name" maxlength="100" />
                </el-form-item>
                <el-form-item label="服务分类" prop="category_id">
                    <el-cascader
                        v-model="staffPackageForm.category_id"
                        :options="categoryOptions"
                        :props="{ value: 'id', label: 'name', checkStrictly: true, emitPath: false }"
                        class="w-full"
                    />
                </el-form-item>
                <el-form-item label="价格" prop="price">
                    <el-input-number v-model="staffPackageForm.price" :min="0" :precision="2" class="w-full" />
                </el-form-item>
                <el-form-item label="原价">
                    <el-input-number v-model="staffPackageForm.original_price" :min="0" :precision="2" class="w-full" />
                </el-form-item>
                <el-form-item label="预约类型">
                    <el-select v-model="staffPackageForm.booking_type" class="w-full">
                        <el-option label="全天套餐" :value="0" />
                        <el-option label="分场次套餐" :value="1" />
                    </el-select>
                </el-form-item>
                <el-form-item v-if="staffPackageForm.booking_type === 1" label="允许场次">
                    <el-checkbox-group v-model="staffPackageForm.allowed_time_slots">
                        <el-checkbox v-for="slot in timeSlotOptions" :key="slot.value" :label="slot.value">{{ slot.label }}</el-checkbox>
                    </el-checkbox-group>
                </el-form-item>
                <el-form-item label="上架状态">
                    <el-switch v-model="staffPackageForm.is_show" :active-value="1" :inactive-value="0" />
                </el-form-item>
                <el-form-item label="描述">
                    <el-input v-model="staffPackageForm.description" type="textarea" :rows="3" maxlength="1000" show-word-limit />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="staffPackageDialogVisible = false">取消</el-button>
                <el-button type="primary" @click="submitStaffPackage">保存</el-button>
            </template>
        </el-dialog>

        <el-dialog v-model="bannerDialogVisible" :title="bannerForm.id ? '编辑轮播图' : '新增轮播图'" width="560px">
            <el-form ref="bannerFormRef" :model="bannerForm" :rules="bannerFormRules" label-width="100px">
                <el-form-item label="类型" prop="type">
                    <el-radio-group v-model="bannerForm.type">
                        <el-radio :label="1">图片</el-radio>
                        <el-radio :label="2">视频</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item :label="bannerForm.type === 2 ? '视频' : '图片'" prop="file_url">
                    <material-picker v-model="bannerForm.file_url" :limit="1" :type="bannerForm.type === 2 ? 'video' : 'image'" />
                </el-form-item>
                <el-form-item v-if="bannerForm.type === 2" label="封面图" prop="cover_url">
                    <material-picker v-model="bannerForm.cover_url" :limit="1" type="image" />
                </el-form-item>
                <el-form-item v-if="bannerForm.type === 2" label="自动播放">
                    <el-switch v-model="bannerForm.is_autoplay" :active-value="1" :inactive-value="0" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="bannerDialogVisible = false">取消</el-button>
                <el-button type="primary" @click="submitBanner">保存</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup lang="ts" name="staffCenterProfile">
import { onMounted, reactive, ref, shallowRef, watch } from 'vue'
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage } from 'element-plus'
import feedback from '@/utils/feedback'
import { categoryTree, styleTagAll } from '@/api/service'
import {
    myProfile,
    myProfileBannerAdd,
    myProfileBannerConfig,
    myProfileBannerDelete,
    myProfileBannerEdit,
    myProfileBannerList,
    myProfileBannerSort,
    myProfileCreatePackage,
    myProfileDeletePackage,
    myProfilePackageConfig,
    myProfileUpdate,
    myProfileUpdatePackageConfig,
    myProfileUpdateStaffPackage
} from '@/api/staff-center'

const formRef = shallowRef<FormInstance>()
const staffPackageFormRef = shallowRef<FormInstance>()
const bannerFormRef = shallowRef<FormInstance>()

const saveLoading = ref(false)

const categoryOptions = ref<any[]>([])
const groupedTags = ref<Record<string, any[]>>({})
const tagTypeLabelMap: Record<string, string> = {
    '1': '风格标签',
    '2': '特长标签',
    '3': '其他标签'
}

const timeSlotOptions = [
    { value: 0, label: '全天' },
    { value: 1, label: '早礼' },
    { value: 2, label: '午宴' },
    { value: 3, label: '晚宴' }
]

const formData = reactive({
    id: 0,
    avatar: '',
    name: '',
    mobile: '',
    category_id: '',
    price: 0,
    experience_years: 0,
    profile: '',
    service_desc: '',
    tag_ids: [] as number[]
})

const formRules: FormRules = {
    name: [{ required: true, message: '请输入姓名', trigger: 'blur' }],
    category_id: [{ required: true, message: '请选择服务分类', trigger: 'change' }]
}

const configuredPackages = ref<any[]>([])
const staffPackages = ref<any[]>([])

const staffPackageDialogVisible = ref(false)
const staffPackageForm = reactive({
    id: 0,
    name: '',
    category_id: '',
    price: 0,
    original_price: 0,
    booking_type: 0,
    allowed_time_slots: [] as number[],
    is_show: 1,
    description: ''
})
const staffPackageRules: FormRules = {
    name: [{ required: true, message: '请输入套餐名称', trigger: 'blur' }],
    category_id: [{ required: true, message: '请选择服务分类', trigger: 'change' }]
}

const bannerConfig = reactive({
    banner_mode: 1,
    banner_small_height: 400,
    banner_large_height: 600,
    banner_indicator_style: 1,
    banner_autoplay: 1,
    banner_interval: 3000
})
const bannerList = ref<any[]>([])

const bannerDialogVisible = ref(false)
const bannerForm = reactive({
    id: 0,
    type: 1,
    file_url: '',
    cover_url: '',
    is_autoplay: 0
})
const bannerFormRules: FormRules = {
    type: [{ required: true, message: '请选择类型', trigger: 'change' }],
    file_url: [{ required: true, message: '请上传文件', trigger: 'change' }],
    cover_url: [
        {
            validator: (_rule: any, value: any, callback: any) => {
                if (bannerForm.type === 2 && !value) {
                    callback(new Error('视频类型必须上传封面图'))
                    return
                }
                callback()
            },
            trigger: 'change'
        }
    ]
}

const normalizeJsonArray = (value: any): any[] => {
    if (Array.isArray(value)) return value
    if (typeof value === 'string' && value) {
        try {
            const parsed = JSON.parse(value)
            return Array.isArray(parsed) ? parsed : []
        } catch (error) {
            return []
        }
    }
    return []
}

const normalizeAllowedSlots = (value: any): number[] => {
    return normalizeJsonArray(value).map((item) => Number(item)).filter((item) => !Number.isNaN(item))
}

const loadCategoryOptions = async () => {
    categoryOptions.value = (await categoryTree()) || []
}

const loadTags = async () => {
    const categoryId = Number(formData.category_id)
    if (!categoryId) {
        groupedTags.value = {}
        formData.tag_ids = []
        return
    }
    groupedTags.value = (await styleTagAll({ group_by_type: 1, category_id: categoryId })) || {}
}

const loadProfile = async () => {
    const data = await myProfile()
    Object.keys(formData).forEach((key) => {
        if ((data as any)[key] !== undefined) {
            ;(formData as any)[key] = (data as any)[key]
        }
    })
    formData.tag_ids = Array.isArray(data.tag_ids) ? data.tag_ids.map((item: any) => Number(item)) : []

    bannerConfig.banner_mode = data.banner_mode ?? 1
    bannerConfig.banner_small_height = data.banner_small_height ?? 400
    bannerConfig.banner_large_height = data.banner_large_height ?? 600
    bannerConfig.banner_indicator_style = data.banner_indicator_style ?? 1
    bannerConfig.banner_autoplay = data.banner_autoplay ?? 1
    bannerConfig.banner_interval = data.banner_interval ?? 3000
}

const loadPackageConfig = async () => {
    const res = await myProfilePackageConfig({ include_global: 1 })
    configuredPackages.value = (res.configured_packages || []).map((item: any) => {
        const bookingType = item.booking_type ?? item.package?.booking_type ?? 0
        let allowedTimeSlots = normalizeAllowedSlots(item.allowed_time_slots ?? item.package?.allowed_time_slots)
        if (bookingType === 1 && allowedTimeSlots.length === 0) {
            allowedTimeSlots = timeSlotOptions.map((slot) => slot.value)
        }
        return {
            package_id: item.package_id,
            package_name: item.package?.name || item.package_name || `套餐#${item.package_id}`,
            price: item.price ?? item.package?.price ?? 0,
            original_price: item.original_price ?? item.package?.original_price ?? 0,
            custom_price: item.custom_price ?? null,
            custom_slot_prices: normalizeJsonArray(item.custom_slot_prices),
            booking_type: bookingType,
            allowed_time_slots: allowedTimeSlots,
            status: item.status ?? 1
        }
    })
    staffPackages.value = res.staff_packages || []
}

const loadBannerList = async () => {
    bannerList.value = (await myProfileBannerList()) || []
}

const handleSaveProfile = async () => {
    await formRef.value?.validate()
    saveLoading.value = true
    try {
        await myProfileUpdate({
            avatar: formData.avatar,
            name: formData.name,
            mobile: formData.mobile,
            category_id: formData.category_id,
            price: formData.price,
            experience_years: formData.experience_years,
            profile: formData.profile,
            service_desc: formData.service_desc,
            tag_ids: formData.tag_ids
        })
        ElMessage.success('保存成功')
    } finally {
        saveLoading.value = false
    }
}

const savePackageConfig = async (row: any) => {
    if (row.booking_type === 1 && (!row.allowed_time_slots || row.allowed_time_slots.length === 0)) {
        ElMessage.warning('分场次套餐必须选择允许场次')
        return
    }
    await myProfileUpdatePackageConfig({
        package_id: row.package_id,
        price: row.price,
        original_price: row.original_price,
        custom_price: row.custom_price,
        custom_slot_prices: row.booking_type === 1 ? row.custom_slot_prices || [] : [],
        booking_type: row.booking_type,
        allowed_time_slots: row.booking_type === 1 ? row.allowed_time_slots : [],
        status: row.status
    })
    ElMessage.success('保存成功')
    loadPackageConfig()
}

const resetStaffPackageForm = () => {
    Object.assign(staffPackageForm, {
        id: 0,
        name: '',
        category_id: '',
        price: 0,
        original_price: 0,
        booking_type: 0,
        allowed_time_slots: [],
        is_show: 1,
        description: ''
    })
}

const openCreateStaffPackage = () => {
    resetStaffPackageForm()
    staffPackageDialogVisible.value = true
}

const openEditStaffPackage = (row: any) => {
    resetStaffPackageForm()
    Object.assign(staffPackageForm, {
        id: row.id,
        name: row.name || '',
        category_id: row.category_id || '',
        price: Number(row.price || 0),
        original_price: Number(row.original_price || 0),
        booking_type: row.booking_type ?? 0,
        allowed_time_slots: normalizeAllowedSlots(row.allowed_time_slots),
        is_show: row.is_show ?? 1,
        description: row.description || ''
    })
    if (staffPackageForm.booking_type === 1 && staffPackageForm.allowed_time_slots.length === 0) {
        staffPackageForm.allowed_time_slots = timeSlotOptions.map((item) => item.value)
    }
    staffPackageDialogVisible.value = true
}

const submitStaffPackage = async () => {
    await staffPackageFormRef.value?.validate()
    if (staffPackageForm.booking_type === 1 && staffPackageForm.allowed_time_slots.length === 0) {
        ElMessage.warning('请选择允许场次')
        return
    }
    const payload = {
        name: staffPackageForm.name,
        category_id: staffPackageForm.category_id,
        price: staffPackageForm.price,
        original_price: staffPackageForm.original_price,
        booking_type: staffPackageForm.booking_type,
        allowed_time_slots: staffPackageForm.booking_type === 1 ? staffPackageForm.allowed_time_slots : [],
        is_show: staffPackageForm.is_show,
        description: staffPackageForm.description
    }
    if (staffPackageForm.id) {
        await myProfileUpdateStaffPackage({
            package_id: staffPackageForm.id,
            ...payload
        })
        ElMessage.success('更新成功')
    } else {
        await myProfileCreatePackage(payload)
        ElMessage.success('创建成功')
    }
    staffPackageDialogVisible.value = false
    loadPackageConfig()
}

const handleDeleteStaffPackage = async (row: any) => {
    await feedback.confirm(`确认删除专属套餐「${row.name}」吗？`)
    await myProfileDeletePackage({ package_id: row.id })
    ElMessage.success('删除成功')
    loadPackageConfig()
}

const openBannerDialog = (row?: any) => {
    Object.assign(bannerForm, {
        id: row?.id || 0,
        type: row?.type || 1,
        file_url: row?.file_url || '',
        cover_url: row?.cover_url || '',
        is_autoplay: row?.is_autoplay || 0
    })
    bannerDialogVisible.value = true
}

const submitBanner = async () => {
    await bannerFormRef.value?.validate()
    if (bannerForm.id) {
        await myProfileBannerEdit({ ...bannerForm })
        ElMessage.success('编辑成功')
    } else {
        await myProfileBannerAdd({ ...bannerForm })
        ElMessage.success('添加成功')
    }
    bannerDialogVisible.value = false
    loadBannerList()
}

const handleDeleteBanner = async (row: any) => {
    await feedback.confirm('确认删除该轮播图吗？')
    await myProfileBannerDelete({ id: row.id })
    ElMessage.success('删除成功')
    loadBannerList()
}

const moveBanner = async (index: number, direction: 'up' | 'down') => {
    const list = [...bannerList.value]
    const target = direction === 'up' ? index - 1 : index + 1
    if (target < 0 || target >= list.length) return
    ;[list[index], list[target]] = [list[target], list[index]]
    const sortData = list.map((item, idx) => ({ id: item.id, sort: idx }))
    await myProfileBannerSort({ sort_data: sortData })
    ElMessage.success('排序成功')
    loadBannerList()
}

const saveBannerConfig = async () => {
    await myProfileBannerConfig({ ...bannerConfig })
    ElMessage.success('轮播配置已保存')
}

watch(
    () => formData.category_id,
    () => {
        loadTags()
    }
)

onMounted(async () => {
    await loadCategoryOptions()
    await loadProfile()
    await loadTags()
    await loadPackageConfig()
    await loadBannerList()
})
</script>

<style scoped>
.staff-center-profile {
    padding: 16px;
}
</style>
