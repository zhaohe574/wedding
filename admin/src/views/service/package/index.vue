<template>
    <div class="package-lists">
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item class="w-[200px]" label="套餐名称">
                    <el-input
                        v-model="queryParams.name"
                        placeholder="输入套餐名称"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[200px]" label="服务分类">
                    <el-cascader
                        v-model="queryParams.category_id"
                        :options="optionsData.categories"
                        :props="{ value: 'id', label: 'name', checkStrictly: true, emitPath: false }"
                        placeholder="选择服务分类"
                        clearable
                    />
                </el-form-item>
                <el-form-item class="w-[200px]" label="套餐类型">
                    <el-select v-model="queryParams.package_type" placeholder="选择类型" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="全局套餐" :value="1" />
                        <el-option label="员工专属" :value="2" />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[200px]" label="状态">
                    <el-select v-model="queryParams.is_show" placeholder="选择状态" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="上架" :value="1" />
                        <el-option label="下架" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <div class="mb-4">
                <el-button
                    v-perms="['service.package/add']"
                    type="primary"
                    @click="handleAdd()"
                >
                    <template #icon>
                        <icon name="el-icon-Plus" />
                    </template>
                    新增套餐
                </el-button>
            </div>
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="ID" prop="id" width="80" />
                <el-table-column label="套餐名称" prop="name" min-width="150" />
                <el-table-column label="服务分类" prop="category_name" width="120" />
                <el-table-column label="套餐类型" width="140">
                    <template #default="{ row }">
                        <el-tag v-if="row.package_type === 1" type="primary">全局套餐</el-tag>
                        <el-tag v-else type="success">
                            员工专属
                            <span v-if="row.staff_name" class="ml-1">({{ row.staff_name }})</span>
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="价格" width="150">
                    <template #default="{ row }">
                        <div class="flex items-center gap-2">
                            <div>
                                <span class="text-red-500 font-bold">¥{{ row.price }}</span>
                                <span v-if="row.original_price > row.price" class="text-gray-400 line-through ml-2 text-xs">
                                    ¥{{ row.original_price }}
                                </span>
                            </div>
                            <el-tooltip content="配置时段价格" placement="top">
                                <el-button
                                    type="primary"
                                    link
                                    size="small"
                                    @click="openSlotPriceDialog(row)"
                                >
                                    <icon name="el-icon-Clock" />
                                </el-button>
                            </el-tooltip>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="服务时长" prop="duration_desc" width="100" />
                <el-table-column label="推荐" width="80">
                    <template #default="{ row }">
                        <el-tag v-if="row.is_recommend" type="warning">推荐</el-tag>
                        <el-tag v-else type="info">普通</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="状态" width="80">
                    <template #default="{ row }">
                        <el-switch
                            v-perms="['service.package/changeStatus']"
                            v-model="row.is_show"
                            :active-value="1"
                            :inactive-value="0"
                            @change="handleChangeStatus($event, row.id)"
                        />
                    </template>
                </el-table-column>
                <el-table-column label="排序" prop="sort" width="80" />
                <el-table-column label="创建时间" prop="create_time" width="170" />
                <el-table-column label="操作" width="160" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-perms="['service.package/edit']"
                            type="primary"
                            link
                            @click="handleEdit(row)"
                        >
                            编辑
                        </el-button>
                        <el-button
                            v-if="row.package_type === 1"
                            type="warning"
                            link
                            @click="openSlotPriceDialog(row)"
                        >
                            时段价格
                        </el-button>
                        <el-button
                            v-perms="['service.package/delete']"
                            type="danger"
                            link
                            @click="handleDelete(row.id)"
                        >
                            删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <!-- 编辑弹窗 -->
        <el-dialog
            v-model="showEditDialog"
            :title="editForm.id ? '编辑套餐' : '新增套餐'"
            width="600px"
        >
            <el-form
                ref="editFormRef"
                :model="editForm"
                :rules="editRules"
                label-width="100px"
            >
                <el-form-item label="套餐名称" prop="name">
                    <el-input v-model="editForm.name" placeholder="请输入套餐名称" maxlength="100" />
                </el-form-item>
                <el-form-item label="服务分类" prop="category_id">
                    <el-cascader
                        v-model="editForm.category_id"
                        :options="optionsData.categories"
                        :props="{ value: 'id', label: 'name', checkStrictly: true, emitPath: false }"
                        placeholder="选择服务分类"
                        class="w-full"
                    />
                </el-form-item>
                <el-form-item label="套餐类型" prop="package_type">
                    <el-radio-group v-model="editForm.package_type" :disabled="!!editForm.id">
                        <el-radio :value="1">全局套餐</el-radio>
                        <el-radio :value="2">员工专属</el-radio>
                    </el-radio-group>
                    <div v-if="editForm.package_type === 2" class="mt-2 w-full">
                        <el-select
                            v-model="editForm.staff_id"
                            placeholder="选择所属员工"
                            class="w-full"
                            :disabled="!!editForm.id"
                        >
                            <el-option
                                v-for="staff in optionsData.staffList"
                                :key="staff.id"
                                :label="staff.name"
                                :value="staff.id"
                            />
                        </el-select>
                    </div>
                </el-form-item>
                <div class="grid grid-cols-2 gap-4">
                    <el-form-item label="套餐价格" prop="price">
                        <el-input-number v-model="editForm.price" :min="0" :precision="2" class="w-full" />
                    </el-form-item>
                    <el-form-item label="原价" prop="original_price">
                        <el-input-number v-model="editForm.original_price" :min="0" :precision="2" class="w-full" />
                    </el-form-item>
                </div>
                <el-form-item label="服务时长" prop="duration">
                    <el-input-number v-model="editForm.duration" :min="1" class="w-full">
                        <template #append>小时</template>
                    </el-input-number>
                </el-form-item>
                <el-form-item label="套餐内容" prop="content">
                    <div class="w-full">
                        <el-tag
                            v-for="(item, index) in editForm.content"
                            :key="index"
                            closable
                            class="mr-2 mb-2"
                            @close="editForm.content.splice(index, 1)"
                        >
                            {{ item }}
                        </el-tag>
                        <el-input
                            v-if="showContentInput"
                            ref="contentInputRef"
                            v-model="contentInputValue"
                            size="small"
                            class="w-[200px]"
                            @keyup.enter="handleAddContent"
                            @blur="handleAddContent"
                        />
                        <el-button v-else size="small" @click="showContentInput = true">+ 添加内容</el-button>
                    </div>
                </el-form-item>
                <el-form-item label="描述" prop="description">
                    <el-input
                        v-model="editForm.description"
                        type="textarea"
                        :rows="3"
                        placeholder="请输入套餐描述"
                        maxlength="500"
                        show-word-limit
                    />
                </el-form-item>
                <div class="grid grid-cols-2 gap-4">
                    <el-form-item label="排序" prop="sort">
                        <el-input-number v-model="editForm.sort" :min="0" :max="9999" class="w-full" />
                    </el-form-item>
                    <el-form-item label="是否推荐" prop="is_recommend">
                        <el-radio-group v-model="editForm.is_recommend">
                            <el-radio :value="1">是</el-radio>
                            <el-radio :value="0">否</el-radio>
                        </el-radio-group>
                    </el-form-item>
                </div>
                <el-form-item label="状态" prop="is_show">
                    <el-radio-group v-model="editForm.is_show">
                        <el-radio :value="1">上架</el-radio>
                        <el-radio :value="0">下架</el-radio>
                    </el-radio-group>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="showEditDialog = false">取消</el-button>
                <el-button type="primary" @click="handleSave">保存</el-button>
            </template>
        </el-dialog>

        <!-- 时段价格配置弹窗 -->
        <el-dialog
            v-model="showSlotPriceDialog"
            title="时段价格配置"
            width="650px"
        >
            <div class="mb-4">
                <el-alert type="info" :closable="false">
                    <template #title>
                        套餐：<span class="font-bold">{{ currentSlotPackage?.name }}</span>
                        <span class="ml-4">默认价格：<span class="text-red-500 font-bold">¥{{ currentSlotPackage?.price }}</span></span>
                    </template>
                </el-alert>
            </div>
            <div class="mb-4">
                <el-button type="primary" size="small" @click="addSlotPrice">
                    <icon name="el-icon-Plus" class="mr-1" />
                    添加时段
                </el-button>
            </div>
            <el-table :data="currentSlotPrices" border>
                <el-table-column label="开始时间" width="150">
                    <template #default="{ row, $index }">
                        <el-time-picker
                            v-model="row.start_time"
                            format="HH:mm"
                            value-format="HH:mm"
                            placeholder="开始时间"
                            class="w-full"
                        />
                    </template>
                </el-table-column>
                <el-table-column label="结束时间" width="150">
                    <template #default="{ row, $index }">
                        <el-time-picker
                            v-model="row.end_time"
                            format="HH:mm"
                            value-format="HH:mm"
                            placeholder="结束时间"
                            class="w-full"
                        />
                    </template>
                </el-table-column>
                <el-table-column label="时段价格" width="150">
                    <template #default="{ row }">
                        <el-input-number
                            v-model="row.price"
                            :min="0"
                            :precision="2"
                            size="small"
                            class="w-full"
                        />
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="80">
                    <template #default="{ $index }">
                        <el-button type="danger" link @click="removeSlotPrice($index)">删除</el-button>
                    </template>
                </el-table-column>
            </el-table>
            <template #footer>
                <el-button @click="showSlotPriceDialog = false">取消</el-button>
                <el-button type="primary" @click="saveSlotPrice">保存</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup name="packageLists">
import type { FormInstance } from 'element-plus'
import {
    packageLists,
    packageAdd,
    packageEdit,
    packageDelete,
    packageChangeStatus,
    packageUpdateSlotPrices,
    categoryTree
} from '@/api/service'
import { staffAll } from '@/api/staff'
import { useDictOptions } from '@/hooks/useDictOptions'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

const queryParams = reactive({
    name: '',
    category_id: '',
    package_type: '',
    is_show: ''
})

const showEditDialog = ref(false)
const editFormRef = shallowRef<FormInstance>()
const showContentInput = ref(false)
const contentInputValue = ref('')

const editForm = reactive({
    id: '',
    name: '',
    category_id: '',
    package_type: 1,
    staff_id: 0,
    price: 0,
    original_price: 0,
    duration: 1,
    content: [] as string[],
    description: '',
    sort: 0,
    is_recommend: 0,
    is_show: 1
})

const editRules = reactive({
    name: [{ required: true, message: '请输入套餐名称', trigger: 'blur' }],
    category_id: [{ required: true, message: '请选择服务分类', trigger: 'change' }],
    price: [{ required: true, message: '请输入套餐价格', trigger: 'blur' }],
    duration: [{ required: true, message: '请输入服务时长', trigger: 'blur' }]
})

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: packageLists,
    params: queryParams
})

const { optionsData } = useDictOptions<{
    categories: any[]
    staffList: any[]
}>({
    categories: {
        api: categoryTree
    },
    staffList: {
        api: staffAll
    }
})

// 时段价格配置相关
const showSlotPriceDialog = ref(false)
const currentSlotPackage = ref<any>(null)
const currentSlotPrices = ref<{ start_time: string; end_time: string; price: number }[]>([])

const openSlotPriceDialog = (row: any) => {
    currentSlotPackage.value = row
    // 解析已有的时段价格
    currentSlotPrices.value = row.slot_prices ? [...row.slot_prices] : []
    showSlotPriceDialog.value = true
}

const addSlotPrice = () => {
    currentSlotPrices.value.push({
        start_time: '08:00',
        end_time: '12:00',
        price: currentSlotPackage.value?.price || 0
    })
}

const removeSlotPrice = (index: number) => {
    currentSlotPrices.value.splice(index, 1)
}

const saveSlotPrice = async () => {
    // 验证时段
    for (const slot of currentSlotPrices.value) {
        if (!slot.start_time || !slot.end_time) {
            feedback.msgError('请填写完整的时段信息')
            return
        }
        if (slot.start_time >= slot.end_time) {
            feedback.msgError('结束时间必须大于开始时间')
            return
        }
        if (slot.price < 0) {
            feedback.msgError('价格不能为负数')
            return
        }
    }
    
    await packageUpdateSlotPrices({
        id: currentSlotPackage.value.id,
        slot_prices: currentSlotPrices.value
    })
    feedback.msgSuccess('保存成功')
    showSlotPriceDialog.value = false
    getLists()
}

const handleAddContent = () => {
    if (contentInputValue.value.trim()) {
        editForm.content.push(contentInputValue.value.trim())
        contentInputValue.value = ''
    }
    showContentInput.value = false
}

const handleAdd = () => {
    Object.assign(editForm, {
        id: '',
        name: '',
        category_id: '',
        package_type: 1,
        staff_id: 0,
        price: 0,
        original_price: 0,
        duration: 1,
        content: [],
        description: '',
        sort: 0,
        is_recommend: 0,
        is_show: 1
    })
    showEditDialog.value = true
}

const handleEdit = (row: any) => {
    Object.assign(editForm, {
        id: row.id,
        name: row.name,
        category_id: row.category_id,
        package_type: row.package_type || 1,
        staff_id: row.staff_id || 0,
        price: row.price,
        original_price: row.original_price,
        duration: row.duration,
        content: row.content || [],
        description: row.description,
        sort: row.sort,
        is_recommend: row.is_recommend,
        is_show: row.is_show
    })
    showEditDialog.value = true
}

const handleSave = async () => {
    await editFormRef.value?.validate()
    if (editForm.id) {
        await packageEdit(editForm)
    } else {
        await packageAdd(editForm)
    }
    showEditDialog.value = false
    getLists()
}

const handleChangeStatus = async (is_show: any, id: number) => {
    try {
        await packageChangeStatus({ id, is_show })
        getLists()
    } catch (error) {
        getLists()
    }
}

const handleDelete = async (id: number) => {
    await feedback.confirm('确定要删除该套餐？')
    await packageDelete({ id })
    getLists()
}

onActivated(() => {
    getLists()
})

getLists()
</script>
