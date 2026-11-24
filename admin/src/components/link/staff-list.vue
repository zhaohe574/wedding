<template>
    <div class="staff-list">
        <el-form :model="queryParams" :inline="true" class="mb-2">
            <el-form-item label="服务分类" class="!w-[200px]">
                <el-select
                    v-model="queryParams.category_id"
                    placeholder="全部分类"
                    clearable
                    @change="resetPage"
                >
                    <el-option label="全部分类" value="" />
                    <el-option
                        v-for="cat in categories"
                        :key="cat.id"
                        :label="cat.name"
                        :value="cat.id"
                    />
                </el-select>
            </el-form-item>
            <el-form-item label="姓名 / 工号">
                <div class="flex items-center gap-2 whitespace-nowrap">
                    <el-input
                        v-model="queryParams.name"
                        class="w-[220px]"
                        placeholder="输入服务人员姓名"
                        clearable
                        @keyup.enter="resetPage"
                    />
                    <el-button type="primary" :icon="Search" @click="resetPage">查询</el-button>
                </div>
            </el-form-item>
        </el-form>

        <el-table
            v-loading="pager.loading"
            :data="pager.lists"
            height="376px"
            size="large"
            @row-click="handleSelectItem"
        >
            <el-table-column label="选择" width="64">
                <template #default="{ row }">
                    <el-checkbox
                        :model-value="isRowSelected(row.id)"
                        size="large"
                        @change="handleSelectItem(row)"
                    />
                </template>
            </el-table-column>
            <el-table-column label="服务人员" min-width="200">
                <template #default="{ row }">
                    <div class="flex items-center gap-3">
                        <el-avatar :src="row.avatar" :size="44">
                            {{ String(row.name || '').slice(0, 1) }}
                        </el-avatar>
                        <div class="min-w-0">
                            <div class="text-sm font-medium truncate">{{ row.name }}</div>
                            <div class="text-xs text-tx-secondary mt-0.5">
                                {{ row.category_name || '未分类' }} · 工号 {{ row.sn || '-' }}
                            </div>
                        </div>
                    </div>
                </template>
            </el-table-column>
            <el-table-column label="评分 / 接单" width="130">
                <template #default="{ row }">
                    <div class="text-xs leading-5">
                        <div>评分：{{ row.rating || '5.0' }}</div>
                        <div class="text-tx-secondary">接单：{{ row.order_count || 0 }}</div>
                    </div>
                </template>
            </el-table-column>
            <el-table-column label="展示价" width="110">
                <template #default="{ row }">
                    <span v-if="row.has_price" class="text-primary font-medium">
                        ¥{{ row.price_text || row.price }}
                    </span>
                    <span v-else class="text-xs text-tx-secondary">待定</span>
                </template>
            </el-table-column>
        </el-table>

        <div class="flex items-center justify-between gap-4 mt-3 pt-3 border-t border-br-light">
            <div class="flex items-center gap-2">
                <span class="text-xs text-tx-secondary whitespace-nowrap">预设服务日期（选填）：</span>
                <el-date-picker
                    v-model="presetDate"
                    type="date"
                    value-format="YYYY-MM-DD"
                    placeholder="不限定日期"
                    clearable
                    size="small"
                    class="!w-[150px]"
                    @change="handleDateChange"
                />
            </div>
            <pagination v-model="pager" @change="getLists" />
        </div>
    </div>
</template>

<script lang="ts" setup>
import { Search } from '@element-plus/icons-vue'
import { onMounted, type PropType, reactive, ref, watch } from 'vue'

import { categoryAll } from '@/api/service'
import { staffLists } from '@/api/staff'
import { usePaging } from '@/hooks/usePaging'

import { type Link, LinkTypeEnum } from '.'

const STAFF_DETAIL_PATH = '/packages/pkg-catalog/pages/staff-detail/staff-detail'

const props = defineProps({
    modelValue: {
        type: Object as PropType<Link>,
        default: () => ({ path: '' })
    }
})
const emit = defineEmits<{
    (event: 'update:modelValue', value: Link): void
}>()

const categories = ref<Array<{ id: number; name: string }>>([])
const presetDate = ref('')
const selectedId = ref('')
const selectedName = ref('')

const queryParams = reactive<Record<string, any>>({
    name: '',
    category_id: '',
    status: 1
})

const { pager, getLists, resetPage } = usePaging({
    fetchFun: staffLists,
    params: queryParams
})

const isRowSelected = (id: number | string) => {
    return selectedId.value !== '' && String(id) === String(selectedId.value)
}

const buildPayload = (id: string, name: string): Link => {
    const query: Record<string, string> = { id }
    if (presetDate.value) {
        query.date = presetDate.value
    }
    return {
        id,
        name: name || '服务人员详情',
        path: STAFF_DETAIL_PATH,
        query,
        type: LinkTypeEnum.STAFF_DETAIL
    }
}

const handleSelectItem = (row: any) => {
    if (!row?.id) return
    selectedId.value = String(row.id)
    selectedName.value = String(row.name || '服务人员详情')
    emit('update:modelValue', buildPayload(selectedId.value, selectedName.value))
}

const handleDateChange = (val: string | null) => {
    presetDate.value = val || ''
    if (selectedId.value) {
        emit('update:modelValue', buildPayload(selectedId.value, selectedName.value))
    }
}

watch(
    () => props.modelValue,
    (value) => {
        if (value?.type !== LinkTypeEnum.STAFF_DETAIL) {
            selectedId.value = ''
            selectedName.value = ''
            presetDate.value = ''
            return
        }
        selectedId.value = String(value.query?.id || value.id || '')
        selectedName.value = String(value.name || '')
        presetDate.value = String(value.query?.date || '')
    },
    { immediate: true }
)

onMounted(async () => {
    try {
        const res = await categoryAll()
        categories.value = Array.isArray(res) ? res : []
    } catch {
        categories.value = []
    }
})

getLists()
</script>
