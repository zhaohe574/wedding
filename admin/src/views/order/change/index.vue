<template>
    <div class="order-change-lists">
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item class="w-[180px]" label="变更单号">
                    <el-input
                        v-model="queryParams.change_sn"
                        placeholder="输入变更单号"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[180px]" label="订单编号">
                    <el-input
                        v-model="queryParams.order_sn"
                        placeholder="输入订单编号"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[120px]" label="变更类型">
                    <el-select v-model="queryParams.change_type" placeholder="选择类型" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="改期" :value="1" />
                        <el-option label="换人" :value="2" />
                        <el-option label="加项" :value="3" />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[120px]" label="变更状态">
                    <el-select v-model="queryParams.change_status" placeholder="选择状态" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="待审核" :value="0" />
                        <el-option label="审核通过" :value="1" />
                        <el-option label="审核拒绝" :value="2" />
                        <el-option label="已执行" :value="3" />
                        <el-option label="已取消" :value="4" />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[320px]" label="申请时间">
                    <el-date-picker
                        v-model="queryParams.create_time"
                        type="daterange"
                        start-placeholder="开始日期"
                        end-placeholder="结束日期"
                        value-format="YYYY-MM-DD"
                        clearable
                    />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <!-- 统计卡片 -->
        <div class="mt-4 grid grid-cols-5 gap-4">
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">待审核</div>
                    <div class="text-2xl font-bold mt-2 text-orange-500">{{ getStatusCount(0) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">待执行</div>
                    <div class="text-2xl font-bold mt-2 text-blue-500">{{ getStatusCount(1) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已执行</div>
                    <div class="text-2xl font-bold mt-2 text-green-500">{{ getStatusCount(3) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已拒绝</div>
                    <div class="text-2xl font-bold mt-2 text-red-500">{{ getStatusCount(2) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已取消</div>
                    <div class="text-2xl font-bold mt-2 text-gray-500">{{ getStatusCount(4) }}</div>
                </div>
            </el-card>
        </div>

        <el-card class="!border-none mt-4" shadow="never">
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="变更单号" prop="change_sn" min-width="160" />
                <el-table-column label="订单编号" prop="order_sn" min-width="160">
                    <template #default="{ row }">
                        <span class="text-primary cursor-pointer" @click="viewOrder(row.order_id)">
                            {{ row.order_sn }}
                        </span>
                    </template>
                </el-table-column>
                <el-table-column label="用户信息" min-width="140">
                    <template #default="{ row }">
                        <div class="flex items-center" v-if="row.user">
                            <el-avatar :src="row.user.avatar" :size="28" />
                            <span class="ml-2">{{ row.user.nickname }}</span>
                        </div>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column label="变更类型" width="90">
                    <template #default="{ row }">
                        <el-tag :type="getTypeTagType(row.change_type)" size="small">
                            {{ row.change_type_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="变更内容" min-width="200">
                    <template #default="{ row }">
                        <!-- 改期 -->
                        <div v-if="row.change_type === 1">
                            <span class="text-gray-400">{{ row.old_service_date }}</span>
                            <el-icon class="mx-1"><Right /></el-icon>
                            <span class="text-primary">{{ row.new_service_date }}</span>
                        </div>
                        <!-- 换人 -->
                        <div v-else-if="row.change_type === 2">
                            <span class="text-gray-400">{{ row.old_staff_name }}</span>
                            <el-icon class="mx-1"><Right /></el-icon>
                            <span class="text-primary">{{ row.new_staff_name }}</span>
                            <span v-if="row.price_diff !== 0" 
                                  :class="row.price_diff > 0 ? 'text-red-500' : 'text-green-500'"
                                  class="ml-2">
                                {{ row.price_diff > 0 ? '+' : '' }}{{ row.price_diff }}元
                            </span>
                        </div>
                        <!-- 加项 -->
                        <div v-else-if="row.change_type === 3">
                            <span class="text-primary">{{ row.add_staff_name }}</span>
                            <span class="text-gray-400 ml-1">({{ row.add_package_name }})</span>
                            <span class="text-red-500 ml-2">+{{ row.add_price }}元</span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getStatusTagType(row.change_status)" size="small">
                            {{ row.change_status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="申请原因" prop="apply_reason" min-width="120" show-overflow-tooltip />
                <el-table-column label="申请时间" prop="create_time" width="160" />
                <el-table-column label="操作" width="180" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handleDetail(row)">详情</el-button>
                        <el-button 
                            v-if="row.change_status === 0" 
                            type="success" 
                            link 
                            @click="handleAudit(row, true)"
                        >通过</el-button>
                        <el-button 
                            v-if="row.change_status === 0" 
                            type="danger" 
                            link 
                            @click="handleAudit(row, false)"
                        >拒绝</el-button>
                        <el-button 
                            v-if="row.change_status === 1" 
                            type="warning" 
                            link 
                            @click="handleExecute(row)"
                        >执行</el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <!-- 详情弹窗 -->
        <el-dialog v-model="detailVisible" title="变更详情" width="700px">
            <div v-if="currentChange" class="change-detail">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="变更单号">{{ currentChange.change_sn }}</el-descriptions-item>
                    <el-descriptions-item label="变更类型">
                        <el-tag :type="getTypeTagType(currentChange.change_type)" size="small">
                            {{ currentChange.change_type_desc }}
                        </el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="订单编号">{{ currentChange.order_sn }}</el-descriptions-item>
                    <el-descriptions-item label="变更状态">
                        <el-tag :type="getStatusTagType(currentChange.change_status)" size="small">
                            {{ currentChange.change_status_desc }}
                        </el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="申请用户" v-if="currentChange.user">
                        {{ currentChange.user.nickname }} ({{ currentChange.user.mobile }})
                    </el-descriptions-item>
                    <el-descriptions-item label="申请时间">{{ currentChange.create_time }}</el-descriptions-item>
                    
                    <!-- 改期信息 -->
                    <template v-if="currentChange.change_type === 1">
                        <el-descriptions-item label="原服务日期">{{ currentChange.old_service_date }}</el-descriptions-item>
                        <el-descriptions-item label="新服务日期">{{ currentChange.new_service_date }}</el-descriptions-item>
                    </template>
                    
                    <!-- 换人信息 -->
                    <template v-if="currentChange.change_type === 2">
                        <el-descriptions-item label="原工作人员">{{ currentChange.old_staff_name }}</el-descriptions-item>
                        <el-descriptions-item label="新工作人员">{{ currentChange.new_staff_name }}</el-descriptions-item>
                        <el-descriptions-item label="原价格">¥{{ currentChange.old_price }}</el-descriptions-item>
                        <el-descriptions-item label="新价格">¥{{ currentChange.new_price }}</el-descriptions-item>
                        <el-descriptions-item label="差价">
                            <span :class="currentChange.price_diff > 0 ? 'text-red-500' : 'text-green-500'">
                                {{ currentChange.price_diff > 0 ? '+' : '' }}{{ currentChange.price_diff }}元
                            </span>
                        </el-descriptions-item>
                    </template>
                    
                    <!-- 加项信息 -->
                    <template v-if="currentChange.change_type === 3">
                        <el-descriptions-item label="新增人员">{{ currentChange.add_staff_name }}</el-descriptions-item>
                        <el-descriptions-item label="套餐">{{ currentChange.add_package_name }}</el-descriptions-item>
                        <el-descriptions-item label="服务日期">{{ currentChange.add_service_date }}</el-descriptions-item>
                        <el-descriptions-item label="增加金额">
                            <span class="text-red-500">+¥{{ currentChange.add_price }}</span>
                        </el-descriptions-item>
                    </template>
                    
                    <el-descriptions-item label="申请原因" :span="2">
                        {{ currentChange.apply_reason || '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="审核备注" :span="2" v-if="currentChange.audit_remark">
                        {{ currentChange.audit_remark }}
                    </el-descriptions-item>
                    <el-descriptions-item label="拒绝原因" :span="2" v-if="currentChange.reject_reason">
                        <span class="text-red-500">{{ currentChange.reject_reason }}</span>
                    </el-descriptions-item>
                </el-descriptions>

                <!-- 附件图片 -->
                <div class="mt-4" v-if="currentChange.attach_images && currentChange.attach_images.length > 0">
                    <h4 class="font-bold mb-2">附件图片</h4>
                    <el-image
                        v-for="(img, index) in currentChange.attach_images"
                        :key="index"
                        :src="img"
                        :preview-src-list="currentChange.attach_images"
                        :initial-index="index"
                        fit="cover"
                        class="w-20 h-20 mr-2 rounded"
                    />
                </div>
            </div>
        </el-dialog>

        <!-- 审核弹窗 -->
        <el-dialog v-model="auditVisible" :title="auditForm.approved ? '审核通过' : '审核拒绝'" width="500px">
            <el-form :model="auditForm" label-width="100px">
                <el-form-item label="审核备注">
                    <el-input 
                        v-model="auditForm.remark" 
                        type="textarea" 
                        :rows="3" 
                        placeholder="请输入审核备注（选填）"
                    />
                </el-form-item>
                <el-form-item label="拒绝原因" v-if="!auditForm.approved" required>
                    <el-input 
                        v-model="auditForm.reject_reason" 
                        type="textarea" 
                        :rows="3" 
                        placeholder="请输入拒绝原因"
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="auditVisible = false">取消</el-button>
                <el-button 
                    :type="auditForm.approved ? 'success' : 'danger'" 
                    @click="submitAudit"
                >确认</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup name="orderChange">
import { Right } from '@element-plus/icons-vue'
import { 
    orderChangeLists, 
    orderChangeDetail, 
    orderChangeStatistics,
    orderChangeAudit,
    orderChangeExecute
} from '@/api/order/change'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

const queryParams = reactive({
    change_sn: '',
    order_sn: '',
    change_type: '',
    change_status: '',
    create_time: []
})

const statistics = ref<any>({})
const detailVisible = ref(false)
const currentChange = ref<any>(null)
const auditVisible = ref(false)
const auditForm = reactive({
    id: 0,
    approved: true,
    remark: '',
    reject_reason: ''
})

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: orderChangeLists,
    params: queryParams
})

const getStatistics = async () => {
    const res = await orderChangeStatistics({})
    statistics.value = res
}

const getStatusCount = (status: number) => {
    if (!statistics.value.status_counts) return 0
    const item = statistics.value.status_counts.find((s: any) => s.status === status)
    return item ? item.count : 0
}

const getTypeTagType = (type: number) => {
    const types: Record<number, string> = {
        1: 'primary',
        2: 'warning',
        3: 'success'
    }
    return types[type] || 'info'
}

const getStatusTagType = (status: number) => {
    const types: Record<number, string> = {
        0: 'warning',
        1: 'primary',
        2: 'danger',
        3: 'success',
        4: 'info'
    }
    return types[status] || 'info'
}

const viewOrder = (orderId: number) => {
    // TODO: 跳转到订单详情
    console.log('View order:', orderId)
}

const handleDetail = async (row: any) => {
    const res = await orderChangeDetail({ id: row.id })
    currentChange.value = res
    detailVisible.value = true
}

const handleAudit = (row: any, approved: boolean) => {
    auditForm.id = row.id
    auditForm.approved = approved
    auditForm.remark = ''
    auditForm.reject_reason = ''
    auditVisible.value = true
}

const submitAudit = async () => {
    if (!auditForm.approved && !auditForm.reject_reason) {
        feedback.msgError('请填写拒绝原因')
        return
    }
    await orderChangeAudit({
        id: auditForm.id,
        approved: auditForm.approved ? 1 : 0,
        remark: auditForm.remark,
        reject_reason: auditForm.reject_reason
    })
    feedback.msgSuccess('操作成功')
    auditVisible.value = false
    getLists()
    getStatistics()
}

const handleExecute = async (row: any) => {
    await feedback.confirm('确定要执行此变更吗？执行后订单信息将被更新。')
    await orderChangeExecute({ id: row.id })
    feedback.msgSuccess('执行成功')
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

<style scoped>
.change-detail :deep(.el-descriptions__label) {
    width: 100px;
}
</style>
