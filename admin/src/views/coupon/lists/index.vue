<template>
    <div class="coupon-container">
        <!-- 统计卡片 -->
        <el-row :gutter="16" class="mb-4">
            <el-col :span="6">
                <el-card shadow="hover" class="stat-card">
                    <div class="stat-content">
                        <div class="stat-label">优惠券总数</div>
                        <div class="stat-value">{{ statistics.total_count || 0 }}</div>
                    </div>
                    <el-icon class="stat-icon" :size="40"><Ticket /></el-icon>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card shadow="hover" class="stat-card success">
                    <div class="stat-content">
                        <div class="stat-label">已发放</div>
                        <div class="stat-value">{{ statistics.total_receive || 0 }}</div>
                    </div>
                    <el-icon class="stat-icon" :size="40"><Present /></el-icon>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card shadow="hover" class="stat-card warning">
                    <div class="stat-content">
                        <div class="stat-label">已使用</div>
                        <div class="stat-value">{{ statistics.total_used || 0 }}</div>
                    </div>
                    <el-icon class="stat-icon" :size="40"><Check /></el-icon>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card shadow="hover" class="stat-card info">
                    <div class="stat-content">
                        <div class="stat-label">使用率</div>
                        <div class="stat-value">{{ statistics.use_rate || '0%' }}</div>
                    </div>
                    <el-icon class="stat-icon" :size="40"><TrendCharts /></el-icon>
                </el-card>
            </el-col>
        </el-row>

        <!-- 搜索栏 -->
        <el-card shadow="never" class="mb-4">
            <el-form :model="queryParams" inline>
                <el-form-item label="优惠券名称">
                    <el-input v-model="queryParams.name" placeholder="请输入" clearable style="width: 180px" />
                </el-form-item>
                <el-form-item label="类型">
                    <el-select v-model="queryParams.coupon_type" placeholder="请选择" clearable style="width: 120px">
                        <el-option label="满减券" :value="1" />
                        <el-option label="折扣券" :value="2" />
                        <el-option label="立减券" :value="3" />
                    </el-select>
                </el-form-item>
                <el-form-item label="状态">
                    <el-select v-model="queryParams.status" placeholder="请选择" clearable style="width: 120px">
                        <el-option label="启用" :value="1" />
                        <el-option label="禁用" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item label="有效期类型">
                    <el-select v-model="queryParams.valid_type" placeholder="请选择" clearable style="width: 140px">
                        <el-option label="固定日期" :value="1" />
                        <el-option label="领取后N天" :value="2" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="handleSearch">搜索</el-button>
                    <el-button @click="handleReset">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <!-- 操作栏 -->
        <el-card shadow="never" class="mb-4">
            <el-button type="primary" @click="handleAdd">
                <el-icon><Plus /></el-icon>
                新建优惠券
            </el-button>
            <el-button @click="showUserCouponDialog = true">
                <el-icon><User /></el-icon>
                用户优惠券
            </el-button>
        </el-card>

        <!-- 列表 -->
        <el-card shadow="never">
            <el-table v-loading="loading" :data="tableData" row-key="id">
                <el-table-column label="优惠券信息" min-width="280">
                    <template #default="{ row }">
                        <div class="coupon-info">
                            <div class="coupon-name">
                                <span class="name">{{ row.name }}</span>
                                <el-tag :type="getCouponTypeTag(row.coupon_type)" size="small">
                                    {{ row.coupon_type_text }}
                                </el-tag>
                            </div>
                            <div class="discount-desc">{{ row.discount_desc }}</div>
                            <div class="valid-period">
                                <el-icon><Clock /></el-icon>
                                {{ row.valid_period }}
                            </div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="使用范围" width="120">
                    <template #default="{ row }">
                        <el-tag :type="getScopeTag(row.use_scope)" size="small">
                            {{ row.use_scope_text }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="发放/领取" width="120" align="center">
                    <template #default="{ row }">
                        <div>{{ row.receive_count }} / {{ row.total_count || '不限' }}</div>
                        <div class="text-gray text-xs">剩余: {{ row.remain_count }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="已使用" width="80" align="center" prop="used_count" />
                <el-table-column label="使用率" width="100" align="center">
                    <template #default="{ row }">
                        <el-progress 
                            :percentage="parseFloat(row.use_rate) || 0" 
                            :stroke-width="6"
                            :show-text="false"
                        />
                        <span class="text-xs">{{ row.use_rate }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="状态" width="100" align="center">
                    <template #default="{ row }">
                        <el-switch
                            v-model="row.status"
                            :active-value="1"
                            :inactive-value="0"
                            @change="handleToggleStatus(row)"
                        />
                    </template>
                </el-table-column>
                <el-table-column label="创建时间" width="160" prop="create_time_text" />
                <el-table-column label="操作" width="180" fixed="right">
                    <template #default="{ row }">
                        <el-button link type="primary" @click="handleEdit(row)">编辑</el-button>
                        <el-button link type="success" @click="handleSend(row)">发放</el-button>
                        <el-button link type="danger" @click="handleDelete(row)">删除</el-button>
                    </template>
                </el-table-column>
            </el-table>

            <div class="pagination-container">
                <el-pagination
                    v-model:current-page="queryParams.page_no"
                    v-model:page-size="queryParams.page_size"
                    :page-sizes="[10, 20, 50, 100]"
                    :total="total"
                    layout="total, sizes, prev, pager, next, jumper"
                    @size-change="getList"
                    @current-change="getList"
                />
            </div>
        </el-card>

        <!-- 编辑弹窗 -->
        <el-dialog 
            v-model="showEditDialog" 
            :title="editForm.id ? '编辑优惠券' : '新建优惠券'"
            width="650px"
            destroy-on-close
        >
            <el-form ref="editFormRef" :model="editForm" :rules="editRules" label-width="100px">
                <el-form-item label="优惠券名称" prop="name">
                    <el-input v-model="editForm.name" placeholder="请输入优惠券名称" maxlength="100" />
                </el-form-item>
                <el-form-item label="优惠券类型" prop="coupon_type">
                    <el-radio-group v-model="editForm.coupon_type" :disabled="!!editForm.id && editForm.receive_count > 0">
                        <el-radio :label="1">满减券</el-radio>
                        <el-radio :label="2">折扣券</el-radio>
                        <el-radio :label="3">立减券</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item label="使用门槛" prop="threshold_amount">
                    <el-input-number 
                        v-model="editForm.threshold_amount" 
                        :min="0" 
                        :precision="2"
                        :disabled="!!editForm.id && editForm.receive_count > 0"
                    />
                    <span class="ml-2 text-gray">元（0表示无门槛）</span>
                </el-form-item>
                <el-form-item label="优惠内容" prop="discount_amount">
                    <el-input-number 
                        v-model="editForm.discount_amount" 
                        :min="0.01" 
                        :precision="2"
                        :disabled="!!editForm.id && editForm.receive_count > 0"
                    />
                    <span class="ml-2 text-gray">
                        {{ editForm.coupon_type === 2 ? '折（如90表示9折）' : '元' }}
                    </span>
                </el-form-item>
                <el-form-item v-if="editForm.coupon_type === 2" label="最大优惠" prop="max_discount">
                    <el-input-number 
                        v-model="editForm.max_discount" 
                        :min="0" 
                        :precision="2"
                        :disabled="!!editForm.id && editForm.receive_count > 0"
                    />
                    <span class="ml-2 text-gray">元（0表示不限）</span>
                </el-form-item>
                <el-form-item label="发放总量" prop="total_count">
                    <el-input-number v-model="editForm.total_count" :min="0" />
                    <span class="ml-2 text-gray">张（0表示不限）</span>
                </el-form-item>
                <el-form-item label="每人限领" prop="per_limit">
                    <el-input-number v-model="editForm.per_limit" :min="0" />
                    <span class="ml-2 text-gray">张（0表示不限）</span>
                </el-form-item>
                <el-form-item label="有效期类型" prop="valid_type">
                    <el-radio-group v-model="editForm.valid_type" :disabled="!!editForm.id && editForm.receive_count > 0">
                        <el-radio :label="1">固定日期</el-radio>
                        <el-radio :label="2">领取后N天</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item v-if="editForm.valid_type === 1" label="有效期" prop="valid_time">
                    <el-date-picker
                        v-model="editForm.valid_time"
                        type="daterange"
                        value-format="YYYY-MM-DD"
                        start-placeholder="开始日期"
                        end-placeholder="结束日期"
                        :disabled="!!editForm.id && editForm.receive_count > 0"
                    />
                </el-form-item>
                <el-form-item v-if="editForm.valid_type === 2" label="有效天数" prop="valid_days">
                    <el-input-number 
                        v-model="editForm.valid_days" 
                        :min="1" 
                        :disabled="!!editForm.id && editForm.receive_count > 0"
                    />
                    <span class="ml-2 text-gray">天</span>
                </el-form-item>
                <el-form-item label="使用范围" prop="use_scope">
                    <el-radio-group v-model="editForm.use_scope" :disabled="!!editForm.id && editForm.receive_count > 0">
                        <el-radio :label="1">全部可用</el-radio>
                        <el-radio :label="2">指定分类</el-radio>
                        <el-radio :label="3">指定人员</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item label="状态" prop="status">
                    <el-switch v-model="editForm.status" :active-value="1" :inactive-value="0" />
                </el-form-item>
                <el-form-item label="备注" prop="remark">
                    <el-input 
                        v-model="editForm.remark" 
                        type="textarea" 
                        :rows="2" 
                        placeholder="请输入备注" 
                        maxlength="255"
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="showEditDialog = false">取消</el-button>
                <el-button type="primary" :loading="submitLoading" @click="handleSubmit">确定</el-button>
            </template>
        </el-dialog>

        <!-- 发放弹窗 -->
        <el-dialog v-model="showSendDialog" title="发放优惠券" width="500px" destroy-on-close>
            <el-form ref="sendFormRef" :model="sendForm" :rules="sendRules" label-width="100px">
                <el-form-item label="优惠券">
                    <span>{{ currentCoupon?.name }}</span>
                </el-form-item>
                <el-form-item label="发放方式">
                    <el-radio-group v-model="sendForm.send_type">
                        <el-radio :label="1">指定用户</el-radio>
                        <el-radio :label="2">批量发放</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item v-if="sendForm.send_type === 1" label="用户ID" prop="user_id">
                    <el-input v-model.number="sendForm.user_id" placeholder="请输入用户ID" />
                </el-form-item>
                <el-form-item v-if="sendForm.send_type === 2" label="用户ID列表" prop="user_ids">
                    <el-input 
                        v-model="sendForm.user_ids_text" 
                        type="textarea" 
                        :rows="4"
                        placeholder="请输入用户ID，多个用英文逗号分隔"
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="showSendDialog = false">取消</el-button>
                <el-button type="primary" :loading="sendLoading" @click="handleSendSubmit">发放</el-button>
            </template>
        </el-dialog>

        <!-- 用户优惠券弹窗 -->
        <el-dialog v-model="showUserCouponDialog" title="用户优惠券" width="900px" destroy-on-close>
            <el-form :model="userCouponQuery" inline class="mb-4">
                <el-form-item label="用户">
                    <el-input v-model="userCouponQuery.keyword" placeholder="昵称/手机号" clearable style="width: 150px" />
                </el-form-item>
                <el-form-item label="优惠券">
                    <el-input v-model="userCouponQuery.coupon_name" placeholder="优惠券名称" clearable style="width: 150px" />
                </el-form-item>
                <el-form-item label="状态">
                    <el-select v-model="userCouponQuery.status" placeholder="请选择" clearable style="width: 100px">
                        <el-option label="未使用" :value="0" />
                        <el-option label="已使用" :value="1" />
                        <el-option label="已过期" :value="2" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="getUserCouponList">搜索</el-button>
                </el-form-item>
            </el-form>

            <el-table v-loading="userCouponLoading" :data="userCouponList" max-height="400">
                <el-table-column label="用户" width="150">
                    <template #default="{ row }">
                        <div class="user-info">
                            <el-avatar :size="28" :src="row.user_avatar">{{ row.user_nickname?.charAt(0) }}</el-avatar>
                            <span class="ml-2">{{ row.user_nickname || '-' }}</span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="优惠券" min-width="150" prop="coupon_name" />
                <el-table-column label="优惠券码" width="180" prop="coupon_sn" />
                <el-table-column label="状态" width="80">
                    <template #default="{ row }">
                        <el-tag :type="getUserCouponStatusType(row.status)" size="small">
                            {{ row.status_text }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="有效期" width="180" prop="valid_period" />
                <el-table-column label="操作" width="80">
                    <template #default="{ row }">
                        <el-button 
                            v-if="row.status === 0" 
                            link 
                            type="danger" 
                            @click="handleRevoke(row)"
                        >
                            撤回
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>

            <div class="pagination-container mt-4">
                <el-pagination
                    v-model:current-page="userCouponQuery.page_no"
                    v-model:page-size="userCouponQuery.page_size"
                    :page-sizes="[10, 20, 50]"
                    :total="userCouponTotal"
                    layout="total, sizes, prev, pager, next"
                    @size-change="getUserCouponList"
                    @current-change="getUserCouponList"
                />
            </div>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { 
    Ticket, Present, Check, TrendCharts, Plus, User, Clock 
} from '@element-plus/icons-vue'
import {
    getCouponList,
    getCouponDetail,
    addCoupon,
    editCoupon,
    deleteCoupon,
    toggleCouponStatus,
    sendCoupon,
    batchSendCoupon,
    getUserCouponList as getUserCouponListApi,
    revokeCoupon,
    getCouponStatistics
} from '@/api/coupon'

// 列表数据
const loading = ref(false)
const tableData = ref<any[]>([])
const total = ref(0)
const statistics = ref<any>({})

// 查询参数
const queryParams = reactive({
    page_no: 1,
    page_size: 10,
    name: '',
    coupon_type: '',
    status: '',
    valid_type: ''
})

// 编辑弹窗
const showEditDialog = ref(false)
const submitLoading = ref(false)
const editFormRef = ref()
const editForm = reactive<any>({
    id: '',
    name: '',
    coupon_type: 1,
    threshold_amount: 0,
    discount_amount: 10,
    max_discount: 0,
    total_count: 0,
    per_limit: 1,
    valid_type: 1,
    valid_time: [],
    valid_days: 7,
    use_scope: 1,
    scope_ids: [],
    status: 1,
    remark: '',
    receive_count: 0
})

const editRules = {
    name: [{ required: true, message: '请输入优惠券名称', trigger: 'blur' }],
    coupon_type: [{ required: true, message: '请选择优惠券类型', trigger: 'change' }],
    discount_amount: [{ required: true, message: '请输入优惠内容', trigger: 'blur' }],
    valid_type: [{ required: true, message: '请选择有效期类型', trigger: 'change' }]
}

// 发放弹窗
const showSendDialog = ref(false)
const sendLoading = ref(false)
const sendFormRef = ref()
const currentCoupon = ref<any>(null)
const sendForm = reactive({
    send_type: 1,
    user_id: '',
    user_ids_text: ''
})

const sendRules = {
    user_id: [{ required: true, message: '请输入用户ID', trigger: 'blur' }]
}

// 用户优惠券弹窗
const showUserCouponDialog = ref(false)
const userCouponLoading = ref(false)
const userCouponList = ref<any[]>([])
const userCouponTotal = ref(0)
const userCouponQuery = reactive({
    page_no: 1,
    page_size: 10,
    keyword: '',
    coupon_name: '',
    status: ''
})

// 获取列表
const getList = async () => {
    loading.value = true
    try {
        const res = await getCouponList(queryParams)
        tableData.value = res.lists || []
        total.value = res.count || 0
    } catch (error) {
        console.error(error)
    } finally {
        loading.value = false
    }
}

// 获取统计
const getStatistics = async () => {
    try {
        const res = await getCouponStatistics()
        statistics.value = res || {}
    } catch (error) {
        console.error(error)
    }
}

// 搜索
const handleSearch = () => {
    queryParams.page_no = 1
    getList()
}

// 重置
const handleReset = () => {
    queryParams.name = ''
    queryParams.coupon_type = ''
    queryParams.status = ''
    queryParams.valid_type = ''
    handleSearch()
}

// 新建
const handleAdd = () => {
    Object.assign(editForm, {
        id: '',
        name: '',
        coupon_type: 1,
        threshold_amount: 0,
        discount_amount: 10,
        max_discount: 0,
        total_count: 0,
        per_limit: 1,
        valid_type: 1,
        valid_time: [],
        valid_days: 7,
        use_scope: 1,
        scope_ids: [],
        status: 1,
        remark: '',
        receive_count: 0
    })
    showEditDialog.value = true
}

// 编辑
const handleEdit = async (row: any) => {
    try {
        const res = await getCouponDetail({ id: row.id })
        Object.assign(editForm, res)
        if (res.valid_type === 1 && res.valid_start_time && res.valid_end_time) {
            editForm.valid_time = [
                new Date(res.valid_start_time * 1000).toISOString().split('T')[0],
                new Date(res.valid_end_time * 1000).toISOString().split('T')[0]
            ]
        }
        showEditDialog.value = true
    } catch (error) {
        console.error(error)
    }
}

// 提交
const handleSubmit = async () => {
    if (!editFormRef.value) return
    await editFormRef.value.validate()

    const params: any = { ...editForm }
    if (params.valid_type === 1 && params.valid_time?.length === 2) {
        params.valid_start_time = params.valid_time[0]
        params.valid_end_time = params.valid_time[1]
    }
    delete params.valid_time

    submitLoading.value = true
    try {
        if (params.id) {
            await editCoupon(params)
            ElMessage.success('编辑成功')
        } else {
            await addCoupon(params)
            ElMessage.success('添加成功')
        }
        showEditDialog.value = false
        getList()
        getStatistics()
    } catch (error) {
        console.error(error)
    } finally {
        submitLoading.value = false
    }
}

// 删除
const handleDelete = (row: any) => {
    ElMessageBox.confirm('确定要删除该优惠券吗？', '提示', {
        type: 'warning'
    }).then(async () => {
        await deleteCoupon({ id: row.id })
        ElMessage.success('删除成功')
        getList()
        getStatistics()
    }).catch(() => {})
}

// 启用/禁用
const handleToggleStatus = async (row: any) => {
    try {
        await toggleCouponStatus({ id: row.id })
        ElMessage.success('操作成功')
    } catch (error) {
        row.status = row.status === 1 ? 0 : 1
    }
}

// 发放
const handleSend = (row: any) => {
    currentCoupon.value = row
    sendForm.send_type = 1
    sendForm.user_id = ''
    sendForm.user_ids_text = ''
    showSendDialog.value = true
}

// 发放提交
const handleSendSubmit = async () => {
    if (!currentCoupon.value) return

    sendLoading.value = true
    try {
        if (sendForm.send_type === 1) {
            if (!sendForm.user_id) {
                ElMessage.warning('请输入用户ID')
                return
            }
            await sendCoupon({
                coupon_id: currentCoupon.value.id,
                user_id: sendForm.user_id
            })
        } else {
            if (!sendForm.user_ids_text) {
                ElMessage.warning('请输入用户ID列表')
                return
            }
            const userIds = sendForm.user_ids_text.split(',').map(id => parseInt(id.trim())).filter(id => !isNaN(id))
            if (!userIds.length) {
                ElMessage.warning('请输入有效的用户ID')
                return
            }
            await batchSendCoupon({
                coupon_id: currentCoupon.value.id,
                user_ids: userIds
            })
        }
        ElMessage.success('发放成功')
        showSendDialog.value = false
        getList()
    } catch (error) {
        console.error(error)
    } finally {
        sendLoading.value = false
    }
}

// 获取用户优惠券列表
const getUserCouponList = async () => {
    userCouponLoading.value = true
    try {
        const res = await getUserCouponListApi(userCouponQuery)
        userCouponList.value = res.lists || []
        userCouponTotal.value = res.count || 0
    } catch (error) {
        console.error(error)
    } finally {
        userCouponLoading.value = false
    }
}

// 撤回
const handleRevoke = (row: any) => {
    ElMessageBox.confirm('确定要撤回该优惠券吗？', '提示', {
        type: 'warning'
    }).then(async () => {
        await revokeCoupon({ user_coupon_id: row.id })
        ElMessage.success('撤回成功')
        getUserCouponList()
    }).catch(() => {})
}

// 获取类型标签
const getCouponTypeTag = (type: number) => {
    const tags: Record<number, string> = { 1: 'danger', 2: 'warning', 3: 'success' }
    return tags[type] || 'info'
}

// 获取范围标签
const getScopeTag = (scope: number) => {
    const tags: Record<number, string> = { 1: '', 2: 'warning', 3: 'info' }
    return tags[scope] || ''
}

// 获取用户优惠券状态标签
const getUserCouponStatusType = (status: number) => {
    const types: Record<number, string> = { 0: 'success', 1: 'info', 2: 'danger' }
    return types[status] || ''
}

onMounted(() => {
    getList()
    getStatistics()
})
</script>

<style scoped lang="scss">
.coupon-container {
    padding: 16px;
}

.stat-card {
    position: relative;
    overflow: hidden;

    .stat-content {
        .stat-label {
            font-size: 14px;
            color: #909399;
        }
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #303133;
            margin-top: 8px;
        }
    }

    .stat-icon {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: #e6e6e6;
    }

    &.success .stat-value { color: #67c23a; }
    &.warning .stat-value { color: #e6a23c; }
    &.info .stat-value { color: #409eff; }
}

.coupon-info {
    .coupon-name {
        display: flex;
        align-items: center;
        gap: 8px;
        .name {
            font-weight: 500;
            font-size: 14px;
        }
    }
    .discount-desc {
        color: #e6a23c;
        font-size: 13px;
        margin-top: 4px;
    }
    .valid-period {
        display: flex;
        align-items: center;
        gap: 4px;
        color: #909399;
        font-size: 12px;
        margin-top: 4px;
    }
}

.user-info {
    display: flex;
    align-items: center;
}

.pagination-container {
    display: flex;
    justify-content: flex-end;
    margin-top: 16px;
}

.mb-4 {
    margin-bottom: 16px;
}

.ml-2 {
    margin-left: 8px;
}

.mt-4 {
    margin-top: 16px;
}

.text-gray {
    color: #909399;
}

.text-xs {
    font-size: 12px;
}
</style>
