<template>
    <div>
        <admin-list-page
            title="附加项"
            description="附加项是套餐之外可单独加购的服务，按人员归属；此处是全量清单，与人员编辑页的「服务套餐」tab 数据一致。"
            toolbar-hint="上下架与价格改动会直接影响客户下单页，改完建议到小程序侧确认一次。"
            :pager="pager"
            @change="getLists"
        >
            <template #search>
            <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item class="w-[220px]" label="附加项名称">
                    <el-input
                        v-model="queryParams.name"
                        placeholder="输入附加项名称"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[200px]" label="所属人员">
                    <el-select
                        v-model="queryParams.staff_id"
                        placeholder="选择人员"
                        clearable
                        filterable
                    >
                        <el-option
                            v-for="s in staffOptions"
                            :key="s.id"
                            :label="s.name"
                            :value="s.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[160px]" label="状态">
                    <el-select v-model="queryParams.is_show" placeholder="全部" clearable>
                        <el-option label="上架" :value="1" />
                        <el-option label="下架" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
            </template>

            <template #actions>
                <el-button v-perms="['service.addon/add']" type="primary" @click="handleAdd">
                    新增附加项
                </el-button>
            </template>

            <el-table v-loading="pager.loading" :data="pager.lists" size="large">
                <el-table-column label="ID" prop="id" width="80" />
                <el-table-column label="所属人员" min-width="120">
                    <template #default="{ row }">
                        {{ staffName(row.staff_id) }}
                    </template>
                </el-table-column>
                <el-table-column label="附加项名称" prop="name" min-width="180" />
                <el-table-column label="分类" prop="category_name" min-width="120" />
                <el-table-column label="价格" width="140">
                    <template #default="{ row }">
                        <span class="text-danger">¥{{ formatMoney(row.price) }}</span>
                        <span v-if="row.original_price" class="ml-2 text-tx-secondary line-through">
                            ¥{{ formatMoney(row.original_price) }}
                        </span>
                    </template>
                </el-table-column>
                <el-table-column label="状态" width="110">
                    <template #default="{ row }">
                        <el-switch
                            v-perms="['service.addon/changeStatus']"
                            :model-value="Number(row.is_show) === 1"
                            @change="handleStatus(row)"
                        />
                    </template>
                </el-table-column>
                <el-table-column label="排序" prop="sort" width="80" />
                <el-table-column label="创建时间" prop="create_time" width="180" />
                <el-table-column label="操作" width="150" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-perms="['service.addon/edit']"
                            type="primary"
                            link
                            @click="handleEdit(row)"
                        >
                            编辑
                        </el-button>
                        <el-button
                            v-perms="['service.addon/delete']"
                            type="danger"
                            link
                            @click="handleDelete(row.id)"
                        >
                            删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
        </admin-list-page>

        <el-dialog
            v-model="showEdit"
            :title="form.id ? '编辑附加项' : '新增附加项'"
            width="620px"
        >
            <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
                <el-form-item label="所属人员" prop="staff_id">
                    <el-select v-model="form.staff_id" placeholder="请选择人员" filterable class="w-full">
                        <el-option
                            v-for="s in staffOptions"
                            :key="s.id"
                            :label="s.name"
                            :value="s.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="名称" prop="name">
                    <el-input v-model="form.name" placeholder="如：加急布置、额外跟妆" />
                </el-form-item>
                <el-form-item label="价格" prop="price">
                    <el-input-number v-model="form.price" :min="0" :precision="2" class="!w-[200px]" />
                </el-form-item>
                <el-form-item label="原价">
                    <el-input-number
                        v-model="form.original_price"
                        :min="0"
                        :precision="2"
                        class="!w-[200px]"
                    />
                </el-form-item>
                <el-form-item label="说明">
                    <el-input
                        v-model="form.description"
                        type="textarea"
                        :rows="3"
                        placeholder="展示给客户的补充说明"
                    />
                </el-form-item>
                <el-form-item label="排序">
                    <el-input-number v-model="form.sort" :min="0" class="!w-[200px]" />
                </el-form-item>
                <el-form-item label="上架">
                    <el-switch v-model="form.is_show" :active-value="1" :inactive-value="0" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="showEdit = false">取消</el-button>
                <el-button type="primary" :loading="saving" @click="handleSave">保存</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup name="serviceAddon">
/**
 * 附加项全局清单。
 *
 * 之前只能从「服务人员 → 编辑 → 服务套餐」里按人维护，运营想统一上下架必须逐个人翻。
 * 图片不在此处维护（沿用人员编辑页上传的图），编辑时不传 image 后端会保留原值。
 */
import type { FormInstance } from 'element-plus'
import {
    addonAdd,
    addonChangeStatus,
    addonDelete,
    addonDetail,
    addonEdit,
    addonLists
} from '@/api/serviceAddon'
import { staffAll } from '@/api/staff'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'
import { formatMoney } from '@shared/utils/format'

const queryParams = reactive({
    name: '',
    staff_id: '',
    is_show: ''
})

const staffOptions = ref<any[]>([])
const showEdit = ref(false)
const saving = ref(false)
const formRef = shallowRef<FormInstance>()

const createDefault = () => ({
    id: 0,
    staff_id: undefined as number | undefined,
    name: '',
    price: 0,
    original_price: 0,
    description: '',
    sort: 0,
    is_show: 1
})

const form = reactive(createDefault())

const rules = {
    staff_id: [{ required: true, message: '请选择所属人员', trigger: 'change' }],
    name: [{ required: true, message: '请输入附加项名称', trigger: 'blur' }],
    price: [{ required: true, message: '请输入价格', trigger: 'blur' }]
}

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: addonLists,
    params: queryParams
})

function staffName(id: number) {
    return staffOptions.value.find((s) => Number(s.id) === Number(id))?.name || '—'
}

async function loadStaff() {
    const data = await staffAll({})
    staffOptions.value = Array.isArray(data) ? data : (data?.lists ?? [])
}

function handleAdd() {
    Object.assign(form, createDefault())
    showEdit.value = true
}

async function handleEdit(row: any) {
    const detail = await addonDetail({ id: row.id })
    Object.assign(form, createDefault(), detail)
    showEdit.value = true
}

async function handleSave() {
    await formRef.value?.validate()
    saving.value = true
    try {
        const payload = { ...form }
        if (payload.id) await addonEdit(payload)
        else await addonAdd(payload)
        feedback.msgSuccess('保存成功')
        showEdit.value = false
        getLists()
    } finally {
        saving.value = false
    }
}

async function handleStatus(row: any) {
    const next = Number(row.is_show) === 1 ? 0 : 1
    await addonChangeStatus({ id: row.id, is_show: next })
    row.is_show = next
    feedback.msgSuccess('操作成功')
}

async function handleDelete(id: number) {
    // feedback.confirm 在「取消」时会 reject，不接住会冒成 unhandled rejection
    try {
        await feedback.confirm('确定删除该附加项？')
    } catch {
        return
    }
    await addonDelete({ id })
    feedback.msgSuccess('删除成功')
    getLists()
}

onMounted(() => {
    loadStaff()
    getLists()
})
</script>
