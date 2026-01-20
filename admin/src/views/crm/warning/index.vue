<template>
    <div class="warning-lists">
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item class="w-[120px]" label="预警类型">
                    <el-select v-model="queryParams.warning_type" placeholder="选择" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="长期未跟进" :value="1" />
                        <el-option label="意向下降" :value="2" />
                        <el-option label="竞品流失" :value="3" />
                        <el-option label="预算不足" :value="4" />
                        <el-option label="其他" :value="5" />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[120px]" label="预警等级">
                    <el-select v-model="queryParams.warning_level" placeholder="选择" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="高" :value="3" />
                        <el-option label="中" :value="2" />
                        <el-option label="低" :value="1" />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[120px]" label="处理状态">
                    <el-select v-model="queryParams.warning_status" placeholder="选择" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="待处理" :value="0" />
                        <el-option label="已处理" :value="1" />
                        <el-option label="已忽略" :value="2" />
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
                <div class="text-center">
                    <div class="text-gray-500 text-sm">待处理</div>
                    <div class="text-2xl font-bold mt-2 text-orange-500">{{ stats.total_pending || 0 }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">高级预警</div>
                    <div class="text-2xl font-bold mt-2 text-red-500">{{ stats.high_level || 0 }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">中级预警</div>
                    <div class="text-2xl font-bold mt-2 text-yellow-500">{{ stats.medium_level || 0 }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">今日处理</div>
                    <div class="text-2xl font-bold mt-2 text-green-500">{{ stats.today_handled || 0 }}</div>
                </div>
            </el-card>
        </div>

        <el-card class="!border-none mt-4" shadow="never">
            <div class="flex justify-between mb-4">
                <div>
                    <el-button type="warning" @click="handleGenerate">
                        <el-icon class="mr-1"><Refresh /></el-icon>生成预警
                    </el-button>
                    <el-button @click="handleBatchProcess('handle')" :disabled="selectedIds.length === 0">
                        批量处理
                    </el-button>
                    <el-button @click="handleBatchProcess('ignore')" :disabled="selectedIds.length === 0">
                        批量忽略
                    </el-button>
                </div>
            </div>

            <el-table 
                size="large" 
                v-loading="pager.loading" 
                :data="pager.lists"
                @selection-change="handleSelectionChange"
            >
                <el-table-column type="selection" width="50" />
                <el-table-column label="客户信息" min-width="180">
                    <template #default="{ row }">
                        <div v-if="row.customer">
                            <div class="font-medium">{{ row.customer.customer_name }}</div>
                            <div class="text-gray-400 text-xs">{{ row.customer.customer_mobile }}</div>
                        </div>
                        <span v-else class="text-gray-400">-</span>
                    </template>
                </el-table-column>
                <el-table-column label="预警类型" width="120">
                    <template #default="{ row }">
                        <span>{{ row.warning_type_desc }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="预警等级" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getLevelTagType(row.warning_level)" size="large">
                            {{ row.warning_level_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="预警原因" prop="warning_reason" min-width="200" />
                <el-table-column label="未跟进天数" width="100">
                    <template #default="{ row }">
                        <span :class="row.days_no_follow > 14 ? 'text-red-500 font-bold' : ''">
                            {{ row.days_no_follow }}天
                        </span>
                    </template>
                </el-table-column>
                <el-table-column label="销售顾问" width="100">
                    <template #default="{ row }">
                        <span v-if="row.advisor">{{ row.advisor.advisor_name }}</span>
                        <span v-else class="text-gray-400">-</span>
                    </template>
                </el-table-column>
                <el-table-column label="处理状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getStatusTagType(row.warning_status)">
                            {{ row.warning_status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="创建时间" prop="create_time" width="170" />
                <el-table-column label="操作" width="150" fixed="right">
                    <template #default="{ row }">
                        <template v-if="row.warning_status === 0">
                            <el-button type="success" link @click="handleProcess(row)">处理</el-button>
                            <el-button type="warning" link @click="handleIgnore(row)">忽略</el-button>
                        </template>
                        <span v-else class="text-gray-400 text-sm">
                            {{ row.handle_remark || '已处理' }}
                        </span>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <!-- 处理弹窗 -->
        <el-dialog v-model="processVisible" :title="processAction === 'handle' ? '处理预警' : '忽略预警'" width="500px">
            <el-form :model="processForm" label-width="100px">
                <el-form-item label="处理备注">
                    <el-input 
                        v-model="processForm.remark" 
                        type="textarea" 
                        :rows="3" 
                        placeholder="请输入处理备注"
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="processVisible = false">取消</el-button>
                <el-button :type="processAction === 'handle' ? 'primary' : 'warning'" @click="submitProcess">
                    {{ processAction === 'handle' ? '确认处理' : '确认忽略' }}
                </el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup name="warningLists">
import { Refresh } from '@element-plus/icons-vue'
import { 
    lossWarningLists, 
    lossWarningHandle,
    lossWarningIgnore,
    lossWarningBatchProcess,
    lossWarningGenerate,
    lossWarningStats
} from '@/api/crm'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

const queryParams = reactive({
    warning_type: '',
    warning_level: '',
    warning_status: ''
})

const stats = ref<any>({})
const selectedIds = ref<number[]>([])
const processVisible = ref(false)
const processAction = ref('handle')
const processForm = reactive({
    id: 0,
    warning_ids: [] as number[],
    remark: ''
})

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: lossWarningLists,
    params: queryParams
})

const getLevelTagType = (level: number) => {
    const types: Record<number, string> = {
        1: 'info',
        2: 'warning',
        3: 'danger'
    }
    return types[level] || 'info'
}

const getStatusTagType = (status: number) => {
    const types: Record<number, string> = {
        0: 'warning',
        1: 'success',
        2: 'info'
    }
    return types[status] || 'info'
}

const getStats = async () => {
    stats.value = await lossWarningStats()
}

const handleSelectionChange = (rows: any[]) => {
    selectedIds.value = rows.map(row => row.id)
}

const handleGenerate = async () => {
    await feedback.confirm('确定要生成流失预警吗？')
    const res = await lossWarningGenerate()
    feedback.msgSuccess(res.msg || '预警生成成功')
    getLists()
    getStats()
}

const handleProcess = (row: any) => {
    processAction.value = 'handle'
    processForm.id = row.id
    processForm.warning_ids = []
    processForm.remark = ''
    processVisible.value = true
}

const handleIgnore = (row: any) => {
    processAction.value = 'ignore'
    processForm.id = row.id
    processForm.warning_ids = []
    processForm.remark = ''
    processVisible.value = true
}

const handleBatchProcess = (action: string) => {
    processAction.value = action
    processForm.id = 0
    processForm.warning_ids = selectedIds.value
    processForm.remark = ''
    processVisible.value = true
}

const submitProcess = async () => {
    if (processForm.id) {
        // 单个处理
        if (processAction.value === 'handle') {
            await lossWarningHandle({ id: processForm.id, remark: processForm.remark })
        } else {
            await lossWarningIgnore({ id: processForm.id, remark: processForm.remark })
        }
        feedback.msgSuccess('操作成功')
    } else {
        // 批量处理
        const result = await lossWarningBatchProcess({
            warning_ids: processForm.warning_ids,
            action: processAction.value,
            remark: processForm.remark
        })
        feedback.msgSuccess(`成功处理 ${result.success} 条预警`)
    }
    processVisible.value = false
    getLists()
    getStats()
}

onMounted(() => {
    getStats()
})

getLists()
</script>

<style scoped>
</style>
