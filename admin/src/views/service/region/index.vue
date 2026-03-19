<template>
    <div class="service-region-page">
        <el-card class="!border-none" shadow="never">
            <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item class="w-[240px]" label="地区关键词">
                    <el-input
                        v-model="queryParams.keyword"
                        placeholder="输入省份或城市"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[180px]" label="状态">
                    <el-select v-model="queryParams.status" placeholder="全部状态" clearable>
                        <el-option label="启用" :value="1" />
                        <el-option label="停用" :value="0" />
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
                <el-button v-perms="['ops.region/add']" type="primary" @click="handleAdd">
                    <template #icon>
                        <icon name="el-icon-Plus" />
                    </template>
                    新增可接单城市
                </el-button>
            </div>

            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="ID" prop="id" width="80" />
                <el-table-column label="省份" prop="province_name" min-width="160" />
                <el-table-column label="城市" prop="city_name" min-width="160" />
                <el-table-column label="状态" width="120">
                    <template #default="{ row }">
                        <el-switch
                            v-model="row.status"
                            :active-value="1"
                            :inactive-value="0"
                            @change="handleChangeStatus($event, row.id)"
                        />
                    </template>
                </el-table-column>
                <el-table-column label="排序" prop="sort" width="100" />
                <el-table-column label="创建时间" prop="create_time" width="180" />
                <el-table-column label="操作" width="180" fixed="right">
                    <template #default="{ row }">
                        <el-button v-perms="['ops.region/edit']" type="primary" link @click="handleEdit(row)">
                            编辑
                        </el-button>
                        <el-button v-perms="['ops.region/delete']" type="danger" link @click="handleDelete(row.id)">
                            删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>

            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <el-dialog
            v-model="showDialog"
            :title="editForm.id ? '编辑服务地区' : '新增服务地区'"
            width="560px"
            @closed="resetEditForm"
        >
            <el-form ref="editFormRef" :model="editForm" :rules="editRules" label-width="100px">
                <el-form-item label="可接单城市" prop="city_code">
                    <el-select
                        v-model="editForm.city_code"
                        class="w-full"
                        filterable
                        clearable
                        placeholder="请选择城市"
                    >
                        <el-option
                            v-for="city in cityOptions"
                            :key="city.city_code"
                            :label="city.label"
                            :value="city.city_code"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="排序" prop="sort">
                    <el-input-number v-model="editForm.sort" class="w-full" :min="0" :max="9999" />
                </el-form-item>
                <el-form-item label="状态" prop="status">
                    <el-radio-group v-model="editForm.status">
                        <el-radio :value="1">启用</el-radio>
                        <el-radio :value="0">停用</el-radio>
                    </el-radio-group>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="showDialog = false">取消</el-button>
                <el-button type="primary" @click="handleSave">保存</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup lang="ts" name="serviceRegion">
import type { FormInstance } from 'element-plus'
import {
    regionAdd,
    regionChangeStatus,
    regionCityOptions,
    regionDelete,
    regionEdit,
    regionLists
} from '@/api/service'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

const queryParams = reactive({
    keyword: '',
    status: ''
})

const showDialog = ref(false)
const editFormRef = shallowRef<FormInstance>()
const cityOptions = ref<any[]>([])

const createDefaultForm = () => ({
    id: 0,
    city_code: '',
    sort: 0,
    status: 1
})

const editForm = reactive(createDefaultForm())

const editRules = reactive({
    city_code: [{ required: true, message: '请选择可接单城市', trigger: 'change' }]
})

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: regionLists,
    params: queryParams
})

const loadCityOptions = async () => {
    const res = await regionCityOptions()
    cityOptions.value = Array.isArray(res) ? res : []
}

const resetEditForm = () => {
    Object.assign(editForm, createDefaultForm())
}

const handleAdd = () => {
    resetEditForm()
    showDialog.value = true
}

const handleEdit = (row: any) => {
    Object.assign(editForm, {
        id: Number(row.id || 0),
        city_code: row.city_code || '',
        sort: Number(row.sort || 0),
        status: Number(row.status ?? 1)
    })
    showDialog.value = true
}

const handleSave = async () => {
    await editFormRef.value?.validate()
    const payload = { ...editForm }
    if (editForm.id) {
        await regionEdit(payload)
    } else {
        await regionAdd(payload)
    }
    showDialog.value = false
    getLists()
}

const handleDelete = async (id: number) => {
    await feedback.confirm('确定删除该服务地区吗？')
    await regionDelete({ id })
    getLists()
}

const handleChangeStatus = async (status: string | number | boolean, id: number) => {
    try {
        await regionChangeStatus({ id, status: Number(status) })
    } finally {
        getLists()
    }
}

onActivated(() => {
    getLists()
})

loadCityOptions()
getLists()
</script>
