<template>
    <div class="staff-lists">
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item class="w-[200px]" label="人员名称">
                    <el-input
                        v-model="queryParams.name"
                        placeholder="输入人员名称"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[200px]" label="工号">
                    <el-input
                        v-model="queryParams.sn"
                        placeholder="输入工号"
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
                <el-form-item class="w-[200px]" label="状态">
                    <el-select v-model="queryParams.status" placeholder="选择状态" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="启用" :value="1" />
                        <el-option label="禁用" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <!-- 统计卡片 -->
        <div class="mt-4 grid grid-cols-4 gap-4">
            <el-card class="!border-none" shadow="never">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-gray-500 text-sm">全部人员</div>
                        <div class="text-2xl font-bold mt-2">{{ statistics.total || 0 }}</div>
                    </div>
                    <el-icon class="text-4xl text-blue-500"><User /></el-icon>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-gray-500 text-sm">启用人员</div>
                        <div class="text-2xl font-bold mt-2 text-green-500">{{ statistics.enable || 0 }}</div>
                    </div>
                    <el-icon class="text-4xl text-green-500"><CircleCheck /></el-icon>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-gray-500 text-sm">禁用人员</div>
                        <div class="text-2xl font-bold mt-2 text-red-500">{{ statistics.disable || 0 }}</div>
                    </div>
                    <el-icon class="text-4xl text-red-500"><CircleClose /></el-icon>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-gray-500 text-sm">推荐人员</div>
                        <div class="text-2xl font-bold mt-2 text-orange-500">{{ statistics.recommend || 0 }}</div>
                    </div>
                    <el-icon class="text-4xl text-orange-500"><Star /></el-icon>
                </div>
            </el-card>
        </div>

        <el-card class="!border-none mt-4" shadow="never">
            <div>
                <router-link
                    v-perms="['staff.staff/add']"
                    :to="{ path: staffEditPath }"
                >
                    <el-button type="primary" class="mb-4">
                        <template #icon>
                            <icon name="el-icon-Plus" />
                        </template>
                        新增人员
                    </el-button>
                </router-link>
            </div>
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="ID" prop="id" width="80" />
                <el-table-column label="头像" width="80">
                    <template #default="{ row }">
                        <el-avatar :src="row.avatar" :size="40">
                            <el-icon><User /></el-icon>
                        </el-avatar>
                    </template>
                </el-table-column>
                <el-table-column label="工号" prop="sn" width="120" />
                <el-table-column label="姓名" prop="name" min-width="100" />
                <el-table-column label="服务分类" prop="category_name" min-width="100" />
                <el-table-column label="服务价格" prop="price" width="100">
                    <template #default="{ row }">
                        <span class="text-red-500">¥{{ row.price }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="评分" prop="rating" width="80">
                    <template #default="{ row }">
                        <span class="text-orange-500">{{ row.rating }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="接单量" prop="order_count" width="80" />
                <el-table-column label="推荐" width="80">
                    <template #default="{ row }">
                        <el-tag v-if="row.is_recommend" type="warning">推荐</el-tag>
                        <el-tag v-else type="info">普通</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="状态" width="80">
                    <template #default="{ row }">
                        <el-switch
                            v-perms="['staff.staff/changeStatus']"
                            v-model="row.status"
                            :active-value="1"
                            :inactive-value="0"
                            @change="handleChangeStatus($event, row.id)"
                        />
                    </template>
                </el-table-column>
                <el-table-column label="排序" prop="sort" width="80" />
                <el-table-column label="创建时间" prop="create_time" width="170" />
                <el-table-column label="操作" width="150" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-perms="['staff.staff/edit']"
                            type="primary"
                            link
                        >
                            <router-link
                                :to="{
                                    path: staffEditPath,
                                    query: { id: row.id }
                                }"
                            >
                                编辑
                            </router-link>
                        </el-button>
                        <el-button
                            v-perms="['staff.staff/delete']"
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
    </div>
</template>

<script lang="ts" setup name="staffLists">
import { User, CircleCheck, CircleClose, Star } from '@element-plus/icons-vue'
import { staffLists, staffDelete, staffChangeStatus, staffStatistics } from '@/api/staff'
import { categoryTree } from '@/api/service'
import { useDictOptions } from '@/hooks/useDictOptions'
import { usePaging } from '@/hooks/usePaging'
import { getRoutePath } from '@/router'
import feedback from '@/utils/feedback'

// 新增/编辑页路径：优先从权限路由获取，若无对应菜单则兜底（需执行 015_add_staff_add_edit_menu.sql）
const staffEditPath = computed(() => getRoutePath('staff.staff/add:edit') || '/service/staff/edit')

const queryParams = reactive({
    name: '',
    sn: '',
    category_id: '',
    status: ''
})

const statistics = ref<any>({})

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: staffLists,
    params: queryParams
})

const { optionsData } = useDictOptions<{
    categories: any[]
}>({
    categories: {
        api: categoryTree
    }
})

const getStatistics = async () => {
    statistics.value = await staffStatistics()
}

const handleChangeStatus = async (status: any, id: number) => {
    try {
        await staffChangeStatus({ id, status })
        getLists()
        getStatistics()
    } catch (error) {
        getLists()
    }
}

const handleDelete = async (id: number) => {
    await feedback.confirm('确定要删除该工作人员？')
    await staffDelete({ id })
    getLists()
    getStatistics()
}

onActivated(() => {
    getLists()
    getStatistics()
})

getLists()
getStatistics()
</script>
