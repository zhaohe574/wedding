<template>
    <admin-page-shell class="staff-lists" title="服务人员">
        <template #search>
            <search-panel>
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
                            :props="{
                                value: 'id',
                                label: 'name',
                                checkStrictly: true,
                                emitPath: false
                            }"
                            placeholder="选择服务分类"
                            clearable
                        />
                    </el-form-item>
                    <el-form-item class="w-[160px]" label="状态">
                        <el-select v-model="queryParams.status" placeholder="选择状态" clearable>
                            <el-option label="全部" value="" />
                            <el-option label="启用" :value="1" />
                            <el-option label="禁用" :value="0" />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[160px]" label="推荐">
                        <el-select v-model="queryParams.is_recommend" placeholder="全部" clearable>
                            <el-option label="全部" value="" />
                            <el-option label="推荐" :value="1" />
                            <el-option label="普通" :value="0" />
                        </el-select>
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" @click="resetPage">查询</el-button>
                        <el-button @click="handleReset">重置</el-button>
                    </el-form-item>
                </el-form>
            </search-panel>
        </template>

        <template #stats>
            <div class="staff-lists__stats">
                <div
                    v-for="stat in statCards"
                    :key="stat.key"
                    class="staff-lists__stat"
                    :class="{ 'is-active': activeStatKey === stat.key }"
                    @click="applyStat(stat.key)"
                >
                    <span class="staff-lists__stat-label">{{ stat.label }}</span>
                    <strong class="staff-lists__stat-value">{{ stat.value }}</strong>
                    <span class="staff-lists__stat-hint">{{ stat.hint }}</span>
                </div>
            </div>
        </template>

        <div class="admin-page-section">
            <admin-table-toolbar :hint="`共 ${pager.count || 0} 位服务人员`">
                <template #right>
                    <router-link v-perms="['ops.staff/add']" :to="{ path: staffEditPath }">
                        <el-button type="primary">
                            <template #icon>
                                <icon name="el-icon-Plus" />
                            </template>
                            新增人员
                        </el-button>
                    </router-link>
                </template>
            </admin-table-toolbar>

            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="服务人员" min-width="280">
                    <template #default="{ row }">
                        <div class="staff-cell">
                            <el-avatar :src="row.avatar" :size="42">
                                <el-icon><User /></el-icon>
                            </el-avatar>
                            <div class="staff-cell__main">
                                <div class="staff-cell__title">
                                    <span class="staff-cell__name">{{ row.name || '-' }}</span>
                                    <el-tag
                                        v-if="Number(row.is_recommend) === 1"
                                        size="small"
                                        type="warning"
                                        effect="plain"
                                    >
                                        推荐
                                    </el-tag>
                                    <span class="staff-cell__sn">工号 {{ row.sn || '-' }}</span>
                                </div>
                                <div
                                    class="staff-cell__intro"
                                    :class="{ 'is-empty': !row.list_intro }"
                                >
                                    {{ row.list_intro || '未填写列表页简介' }}
                                </div>
                            </div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="服务分类" prop="category_name" min-width="120">
                    <template #default="{ row }">{{ row.category_name || '-' }}</template>
                </el-table-column>
                <el-table-column label="服务价格" width="130">
                    <template #default="{ row }">
                        <span v-if="row.has_price" class="staff-price">¥{{ row.price_text }}</span>
                        <span v-else class="admin-edit-muted">面议</span>
                    </template>
                </el-table-column>
                <el-table-column label="评分" width="80">
                    <template #default="{ row }">
                        <span class="staff-rating">{{ row.rating }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="接单量" prop="order_count" width="90" />
                <el-table-column label="状态" width="80">
                    <template #default="{ row }">
                        <el-switch
                            v-perms="['ops.staff/changeStatus']"
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
                        <el-button v-perms="['ops.staff/edit']" type="primary" link>
                            <router-link :to="{ path: staffEditPath, query: { id: row.id } }">
                                编辑
                            </router-link>
                        </el-button>
                        <el-button
                            v-perms="['ops.staffCertificate/lists']"
                            type="primary"
                            link
                            @click="goCertificateList(row.id)"
                        >
                            证书
                        </el-button>
                        <el-button
                            v-perms="['ops.staff/delete']"
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
        </div>
    </admin-page-shell>
</template>

<script lang="ts" setup name="staffLists">
import { User } from '@element-plus/icons-vue'
import { useRouter } from 'vue-router'

import { categoryTree } from '@/api/service'
import { staffChangeStatus, staffDelete, staffLists, staffStatistics } from '@/api/staff'
import { useDictOptions } from '@/hooks/useDictOptions'
import { usePaging } from '@/hooks/usePaging'
import { getRoutePath } from '@/router'
import feedback from '@/utils/feedback'

// 新增/编辑页路径：优先从权限路由获取，若无对应菜单则兜底（需执行 015_add_staff_add_edit_menu.sql）
const staffEditPath = computed(() => getRoutePath('ops.staff/add:edit') || '/service/staff/edit')
// 证书页路径：优先从权限路由获取，若菜单缓存未刷新则回退到约定路径
const certificateListPath = computed(() => getRoutePath('ops.staffCertificate/lists') || '/service/certificate')
const router = useRouter()

const queryParams = reactive({
    name: '',
    sn: '',
    category_id: '' as string | number,
    status: '' as string | number,
    is_recommend: '' as string | number
})

const statistics = ref<any>({})

const statCards = computed(() => [
    {
        key: 'all',
        label: '全部人员',
        value: Number(statistics.value.total || 0),
        hint: '含启用、禁用与推荐人员'
    },
    {
        key: 'enable',
        label: '启用人员',
        value: Number(statistics.value.enable || 0),
        hint: '当前可正常接单'
    },
    {
        key: 'disable',
        label: '禁用人员',
        value: Number(statistics.value.disable || 0),
        hint: '已暂停接单'
    },
    {
        key: 'recommend',
        label: '推荐人员',
        value: Number(statistics.value.recommend || 0),
        hint: '小程序列表优先展示'
    }
])

const isEmptyFilter = (value: unknown) => value === '' || value === undefined || value === null

const activeStatKey = computed(() => {
    if (!isEmptyFilter(queryParams.is_recommend) && Number(queryParams.is_recommend) === 1) {
        return 'recommend'
    }
    if (!isEmptyFilter(queryParams.status) && Number(queryParams.status) === 1) {
        return 'enable'
    }
    if (!isEmptyFilter(queryParams.status) && Number(queryParams.status) === 0) {
        return 'disable'
    }
    if (
        isEmptyFilter(queryParams.status) &&
        isEmptyFilter(queryParams.is_recommend) &&
        !queryParams.name &&
        !queryParams.sn &&
        isEmptyFilter(queryParams.category_id)
    ) {
        return 'all'
    }
    return ''
})

const { pager, getLists, resetPage } = usePaging({
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

const clearFilters = () => {
    queryParams.name = ''
    queryParams.sn = ''
    queryParams.category_id = ''
    queryParams.status = ''
    queryParams.is_recommend = ''
}

const applyStat = (key: string) => {
    clearFilters()
    if (key === 'enable') {
        queryParams.status = 1
    } else if (key === 'disable') {
        queryParams.status = 0
    } else if (key === 'recommend') {
        queryParams.is_recommend = 1
    }
    resetPage()
}

const handleReset = () => {
    clearFilters()
    resetPage()
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

const goCertificateList = (staffId: number) => {
    router.push({
        path: certificateListPath.value,
        query: { staff_id: staffId },
    })
}

onActivated(() => {
    getLists()
    getStatistics()
})

getLists()
getStatistics()
</script>

<style lang="scss" scoped>
.staff-lists {
    &__stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    &__stat {
        display: flex;
        flex-direction: column;
        gap: 6px;
        padding: 12px 14px;
        border: 1px solid var(--admin-color-border);
        border-radius: var(--admin-radius-md);
        background: var(--admin-surface-card);
        cursor: pointer;
        transition:
            border-color 0.2s ease,
            background-color 0.2s ease;

        &:hover,
        &.is-active {
            border-color: var(--el-color-primary-light-5);
            background: var(--el-color-primary-light-9);
        }
    }

    &__stat-label {
        font-size: 12px;
        line-height: 1.4;
        color: var(--admin-color-muted);
    }

    &__stat-value {
        font-size: 20px;
        font-weight: 600;
        line-height: 1.1;
        color: var(--admin-color-title);
        font-variant-numeric: tabular-nums;
    }

    &__stat-hint {
        font-size: 12px;
        line-height: 1.4;
        color: var(--admin-color-muted);
    }
}

.staff-cell {
    display: flex;
    align-items: center;
    gap: 12px;

    &__main {
        min-width: 0;
    }

    &__title {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }

    &__name {
        font-size: 14px;
        font-weight: 600;
        line-height: 1.5;
        color: var(--admin-color-title);
    }

    &__sn {
        font-size: 12px;
        line-height: 1.5;
        color: var(--admin-color-muted);
    }

    &__intro {
        margin-top: 2px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 12px;
        line-height: 1.5;
        color: var(--admin-color-muted);

        &.is-empty {
            color: var(--el-text-color-placeholder);
        }
    }
}

.staff-price {
    font-weight: 600;
    color: var(--el-color-danger);
}

.staff-rating {
    color: var(--el-color-warning);
}

@media (max-width: 1200px) {
    .staff-lists__stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 768px) {
    .staff-lists__stats {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
