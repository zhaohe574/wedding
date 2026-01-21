<template>
    <div class="aftersale-container">
        <!-- 统计卡片 -->
        <el-row :gutter="16" class="mb-4">
            <el-col :span="6">
                <el-card shadow="hover" class="stat-card">
                    <div class="stat-content">
                        <div class="stat-icon ticket-icon">
                            <el-icon :size="32"><Tickets /></el-icon>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value">{{ statistics.ticket?.total || 0 }}</div>
                            <div class="stat-label">工单总数</div>
                        </div>
                    </div>
                    <div class="stat-footer">
                        待处理: <span class="text-warning">{{ statistics.ticket?.pending || 0 }}</span>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card shadow="hover" class="stat-card">
                    <div class="stat-content">
                        <div class="stat-icon complaint-icon">
                            <el-icon :size="32"><Warning /></el-icon>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value">{{ statistics.complaint?.total || 0 }}</div>
                            <div class="stat-label">投诉总数</div>
                        </div>
                    </div>
                    <div class="stat-footer">
                        待处理: <span class="text-danger">{{ statistics.complaint?.pending || 0 }}</span>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card shadow="hover" class="stat-card">
                    <div class="stat-content">
                        <div class="stat-icon reshoot-icon">
                            <el-icon :size="32"><Camera /></el-icon>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value">{{ statistics.reshoot?.total || 0 }}</div>
                            <div class="stat-label">补拍申请</div>
                        </div>
                    </div>
                    <div class="stat-footer">
                        待审核: <span class="text-warning">{{ statistics.reshoot?.pending || 0 }}</span>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card shadow="hover" class="stat-card">
                    <div class="stat-content">
                        <div class="stat-icon callback-icon">
                            <el-icon :size="32"><Phone /></el-icon>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value">{{ statistics.callback?.total || 0 }}</div>
                            <div class="stat-label">回访任务</div>
                        </div>
                    </div>
                    <div class="stat-footer">
                        今日待回访: <span class="text-primary">{{ statistics.callback?.today_plan || 0 }}</span>
                    </div>
                </el-card>
            </el-col>
        </el-row>

        <!-- 标签页 -->
        <el-card shadow="never">
            <el-tabs v-model="activeTab" @tab-change="handleTabChange">
                <!-- 工单管理 -->
                <el-tab-pane label="工单管理" name="ticket">
                    <div class="search-bar mb-4">
                        <el-form :inline="true" :model="ticketSearch">
                            <el-form-item label="工单编号">
                                <el-input v-model="ticketSearch.ticket_sn" placeholder="请输入工单编号" clearable />
                            </el-form-item>
                            <el-form-item label="工单类型">
                                <el-select v-model="ticketSearch.type" placeholder="全部类型" clearable>
                                    <el-option label="投诉" :value="1" />
                                    <el-option label="咨询" :value="2" />
                                    <el-option label="售后" :value="3" />
                                    <el-option label="建议" :value="4" />
                                    <el-option label="其他" :value="5" />
                                </el-select>
                            </el-form-item>
                            <el-form-item label="优先级">
                                <el-select v-model="ticketSearch.priority" placeholder="全部优先级" clearable>
                                    <el-option label="低" :value="1" />
                                    <el-option label="中" :value="2" />
                                    <el-option label="高" :value="3" />
                                    <el-option label="紧急" :value="4" />
                                </el-select>
                            </el-form-item>
                            <el-form-item label="状态">
                                <el-select v-model="ticketSearch.status" placeholder="全部状态" clearable>
                                    <el-option label="待分配" :value="0" />
                                    <el-option label="处理中" :value="1" />
                                    <el-option label="待确认" :value="2" />
                                    <el-option label="已完成" :value="3" />
                                    <el-option label="已关闭" :value="4" />
                                </el-select>
                            </el-form-item>
                            <el-form-item>
                                <el-button type="primary" @click="getTicketList">搜索</el-button>
                                <el-button @click="resetTicketSearch">重置</el-button>
                            </el-form-item>
                        </el-form>
                    </div>

                    <el-table :data="ticketList" v-loading="ticketLoading" stripe>
                        <el-table-column prop="ticket_sn" label="工单编号" width="180" />
                        <el-table-column prop="title" label="标题" min-width="200" show-overflow-tooltip />
                        <el-table-column prop="user.nickname" label="用户" width="120" />
                        <el-table-column prop="type_desc" label="类型" width="80">
                            <template #default="{ row }">
                                <el-tag size="small">{{ row.type_desc }}</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column prop="priority_desc" label="优先级" width="80">
                            <template #default="{ row }">
                                <el-tag :type="getPriorityType(row.priority)" size="small">{{ row.priority_desc }}</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column prop="status_desc" label="状态" width="90">
                            <template #default="{ row }">
                                <el-tag :type="getStatusType(row.status)" size="small">{{ row.status_desc }}</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column prop="assign_admin.name" label="处理人" width="100" />
                        <el-table-column prop="create_time" label="创建时间" width="160" />
                        <el-table-column label="操作" width="200" fixed="right">
                            <template #default="{ row }">
                                <el-button type="primary" link size="small" @click="viewTicket(row)">详情</el-button>
                                <el-button v-if="row.status === 0" type="warning" link size="small" @click="showAssignDialog(row)">分配</el-button>
                                <el-button v-if="row.status === 1" type="success" link size="small" @click="showHandleDialog(row)">处理</el-button>
                                <el-button v-if="row.status < 3" type="danger" link size="small" @click="showCloseDialog(row)">关闭</el-button>
                            </template>
                        </el-table-column>
                    </el-table>

                    <div class="pagination-container">
                        <el-pagination
                            v-model:current-page="ticketPager.page"
                            v-model:page-size="ticketPager.limit"
                            :total="ticketPager.total"
                            :page-sizes="[10, 20, 50, 100]"
                            layout="total, sizes, prev, pager, next, jumper"
                            @size-change="getTicketList"
                            @current-change="getTicketList"
                        />
                    </div>
                </el-tab-pane>

                <!-- 投诉管理 -->
                <el-tab-pane label="投诉管理" name="complaint">
                    <div class="search-bar mb-4">
                        <el-form :inline="true" :model="complaintSearch">
                            <el-form-item label="投诉编号">
                                <el-input v-model="complaintSearch.complaint_sn" placeholder="请输入投诉编号" clearable />
                            </el-form-item>
                            <el-form-item label="投诉类型">
                                <el-select v-model="complaintSearch.type" placeholder="全部类型" clearable>
                                    <el-option label="服务态度" :value="1" />
                                    <el-option label="专业能力" :value="2" />
                                    <el-option label="迟到早退" :value="3" />
                                    <el-option label="违规行为" :value="4" />
                                    <el-option label="其他" :value="5" />
                                </el-select>
                            </el-form-item>
                            <el-form-item label="投诉等级">
                                <el-select v-model="complaintSearch.level" placeholder="全部等级" clearable>
                                    <el-option label="一般" :value="1" />
                                    <el-option label="严重" :value="2" />
                                    <el-option label="紧急" :value="3" />
                                </el-select>
                            </el-form-item>
                            <el-form-item label="状态">
                                <el-select v-model="complaintSearch.status" placeholder="全部状态" clearable>
                                    <el-option label="待处理" :value="0" />
                                    <el-option label="处理中" :value="1" />
                                    <el-option label="已处理" :value="2" />
                                    <el-option label="已关闭" :value="4" />
                                </el-select>
                            </el-form-item>
                            <el-form-item>
                                <el-button type="primary" @click="getComplaintList">搜索</el-button>
                                <el-button @click="resetComplaintSearch">重置</el-button>
                            </el-form-item>
                        </el-form>
                    </div>

                    <el-table :data="complaintList" v-loading="complaintLoading" stripe>
                        <el-table-column prop="complaint_sn" label="投诉编号" width="180" />
                        <el-table-column prop="title" label="标题" min-width="200" show-overflow-tooltip />
                        <el-table-column prop="user.nickname" label="投诉人" width="120" />
                        <el-table-column prop="staff.name" label="被投诉人员" width="120" />
                        <el-table-column prop="type_desc" label="类型" width="90">
                            <template #default="{ row }">
                                <el-tag size="small">{{ row.type_desc }}</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column prop="level_desc" label="等级" width="80">
                            <template #default="{ row }">
                                <el-tag :type="getLevelType(row.level)" size="small">{{ row.level_desc }}</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column prop="status_desc" label="状态" width="90">
                            <template #default="{ row }">
                                <el-tag :type="getComplaintStatusType(row.status)" size="small">{{ row.status_desc }}</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column prop="create_time" label="创建时间" width="160" />
                        <el-table-column label="操作" width="150" fixed="right">
                            <template #default="{ row }">
                                <el-button type="primary" link size="small" @click="viewComplaint(row)">详情</el-button>
                                <el-button v-if="row.status < 2" type="success" link size="small" @click="showComplaintHandleDialog(row)">处理</el-button>
                            </template>
                        </el-table-column>
                    </el-table>

                    <div class="pagination-container">
                        <el-pagination
                            v-model:current-page="complaintPager.page"
                            v-model:page-size="complaintPager.limit"
                            :total="complaintPager.total"
                            :page-sizes="[10, 20, 50, 100]"
                            layout="total, sizes, prev, pager, next, jumper"
                            @size-change="getComplaintList"
                            @current-change="getComplaintList"
                        />
                    </div>
                </el-tab-pane>

                <!-- 补拍申请 -->
                <el-tab-pane label="补拍申请" name="reshoot">
                    <div class="search-bar mb-4">
                        <el-form :inline="true" :model="reshootSearch">
                            <el-form-item label="申请编号">
                                <el-input v-model="reshootSearch.reshoot_sn" placeholder="请输入申请编号" clearable />
                            </el-form-item>
                            <el-form-item label="申请类型">
                                <el-select v-model="reshootSearch.type" placeholder="全部类型" clearable>
                                    <el-option label="补拍" :value="1" />
                                    <el-option label="重拍" :value="2" />
                                </el-select>
                            </el-form-item>
                            <el-form-item label="状态">
                                <el-select v-model="reshootSearch.status" placeholder="全部状态" clearable>
                                    <el-option label="待审核" :value="0" />
                                    <el-option label="审核通过" :value="1" />
                                    <el-option label="审核拒绝" :value="2" />
                                    <el-option label="已安排" :value="3" />
                                    <el-option label="已完成" :value="4" />
                                </el-select>
                            </el-form-item>
                            <el-form-item>
                                <el-button type="primary" @click="getReshootList">搜索</el-button>
                                <el-button @click="resetReshootSearch">重置</el-button>
                            </el-form-item>
                        </el-form>
                    </div>

                    <el-table :data="reshootList" v-loading="reshootLoading" stripe>
                        <el-table-column prop="reshoot_sn" label="申请编号" width="180" />
                        <el-table-column prop="user.nickname" label="申请人" width="120" />
                        <el-table-column prop="staff.name" label="原服务人员" width="120" />
                        <el-table-column prop="type_desc" label="类型" width="80">
                            <template #default="{ row }">
                                <el-tag :type="row.type === 1 ? 'primary' : 'warning'" size="small">{{ row.type_desc }}</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column prop="reason_type_desc" label="原因" width="100" />
                        <el-table-column prop="status_desc" label="状态" width="90">
                            <template #default="{ row }">
                                <el-tag :type="getReshootStatusType(row.status)" size="small">{{ row.status_desc }}</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column prop="expect_date" label="期望日期" width="120" />
                        <el-table-column prop="schedule_date" label="安排日期" width="120" />
                        <el-table-column prop="create_time" label="创建时间" width="160" />
                        <el-table-column label="操作" width="200" fixed="right">
                            <template #default="{ row }">
                                <el-button type="primary" link size="small" @click="viewReshoot(row)">详情</el-button>
                                <el-button v-if="row.status === 0" type="success" link size="small" @click="showAuditDialog(row)">审核</el-button>
                                <el-button v-if="row.status === 1" type="warning" link size="small" @click="showScheduleDialog(row)">安排</el-button>
                                <el-button v-if="row.status === 3" type="primary" link size="small" @click="completeReshoot(row)">完成</el-button>
                            </template>
                        </el-table-column>
                    </el-table>

                    <div class="pagination-container">
                        <el-pagination
                            v-model:current-page="reshootPager.page"
                            v-model:page-size="reshootPager.limit"
                            :total="reshootPager.total"
                            :page-sizes="[10, 20, 50, 100]"
                            layout="total, sizes, prev, pager, next, jumper"
                            @size-change="getReshootList"
                            @current-change="getReshootList"
                        />
                    </div>
                </el-tab-pane>

                <!-- 回访管理 -->
                <el-tab-pane label="回访管理" name="callback">
                    <div class="search-bar mb-4">
                        <el-form :inline="true" :model="callbackSearch">
                            <el-form-item label="回访编号">
                                <el-input v-model="callbackSearch.callback_sn" placeholder="请输入回访编号" clearable />
                            </el-form-item>
                            <el-form-item label="回访类型">
                                <el-select v-model="callbackSearch.type" placeholder="全部类型" clearable>
                                    <el-option label="服务前" :value="1" />
                                    <el-option label="服务中" :value="2" />
                                    <el-option label="服务后" :value="3" />
                                </el-select>
                            </el-form-item>
                            <el-form-item label="状态">
                                <el-select v-model="callbackSearch.status" placeholder="全部状态" clearable>
                                    <el-option label="待回访" :value="0" />
                                    <el-option label="已回访" :value="1" />
                                    <el-option label="无法联系" :value="2" />
                                </el-select>
                            </el-form-item>
                            <el-form-item label="是否有问题">
                                <el-select v-model="callbackSearch.has_problem" placeholder="全部" clearable>
                                    <el-option label="有问题" :value="1" />
                                    <el-option label="无问题" :value="0" />
                                </el-select>
                            </el-form-item>
                            <el-form-item>
                                <el-button type="primary" @click="getCallbackList">搜索</el-button>
                                <el-button @click="resetCallbackSearch">重置</el-button>
                            </el-form-item>
                        </el-form>
                    </div>

                    <el-table :data="callbackList" v-loading="callbackLoading" stripe>
                        <el-table-column prop="callback_sn" label="回访编号" width="180" />
                        <el-table-column prop="user.nickname" label="客户" width="120" />
                        <el-table-column prop="staff.name" label="服务人员" width="120" />
                        <el-table-column prop="type_desc" label="类型" width="80" />
                        <el-table-column prop="method_desc" label="方式" width="80" />
                        <el-table-column prop="status_desc" label="状态" width="90">
                            <template #default="{ row }">
                                <el-tag :type="getCallbackStatusType(row.status)" size="small">{{ row.status_desc }}</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column prop="score" label="满意度" width="100">
                            <template #default="{ row }">
                                <el-rate v-if="row.score > 0" v-model="row.score" disabled size="small" />
                                <span v-else class="text-muted">未评价</span>
                            </template>
                        </el-table-column>
                        <el-table-column prop="has_problem" label="问题" width="80">
                            <template #default="{ row }">
                                <el-tag v-if="row.has_problem" type="danger" size="small">有问题</el-tag>
                                <el-tag v-else type="success" size="small">无</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column prop="plan_time" label="计划时间" width="160" />
                        <el-table-column label="操作" width="200" fixed="right">
                            <template #default="{ row }">
                                <el-button type="primary" link size="small" @click="viewCallback(row)">详情</el-button>
                                <el-button v-if="row.status === 0" type="success" link size="small" @click="showCallbackDialog(row)">回访</el-button>
                                <el-button v-if="row.status === 0" type="warning" link size="small" @click="markUnreachable(row)">无法联系</el-button>
                                <el-button v-if="row.has_problem && row.problem_status === 0" type="danger" link size="small" @click="escalateProblem(row)">升级</el-button>
                            </template>
                        </el-table-column>
                    </el-table>

                    <div class="pagination-container">
                        <el-pagination
                            v-model:current-page="callbackPager.page"
                            v-model:page-size="callbackPager.limit"
                            :total="callbackPager.total"
                            :page-sizes="[10, 20, 50, 100]"
                            layout="total, sizes, prev, pager, next, jumper"
                            @size-change="getCallbackList"
                            @current-change="getCallbackList"
                        />
                    </div>
                </el-tab-pane>
            </el-tabs>
        </el-card>

        <!-- 分配工单弹窗 -->
        <el-dialog v-model="assignDialogVisible" title="分配工单" width="500px">
            <el-form :model="assignForm" label-width="80px">
                <el-form-item label="处理人">
                    <el-select v-model="assignForm.admin_id" placeholder="请选择处理人" filterable>
                        <el-option v-for="item in adminList" :key="item.id" :label="item.name" :value="item.id" />
                    </el-select>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="assignDialogVisible = false">取消</el-button>
                <el-button type="primary" @click="submitAssign" :loading="submitLoading">确定</el-button>
            </template>
        </el-dialog>

        <!-- 处理工单弹窗 -->
        <el-dialog v-model="handleDialogVisible" title="处理工单" width="600px">
            <el-form :model="handleForm" label-width="80px">
                <el-form-item label="处理结果" required>
                    <el-input v-model="handleForm.result" type="textarea" :rows="4" placeholder="请输入处理结果" />
                </el-form-item>
                <el-form-item label="附件图片">
                    <el-upload
                        v-model:file-list="handleForm.imageList"
                        action="/api/upload/image"
                        list-type="picture-card"
                        :limit="5"
                    >
                        <el-icon><Plus /></el-icon>
                    </el-upload>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="handleDialogVisible = false">取消</el-button>
                <el-button type="primary" @click="submitHandle" :loading="submitLoading">确定</el-button>
            </template>
        </el-dialog>

        <!-- 关闭工单弹窗 -->
        <el-dialog v-model="closeDialogVisible" title="关闭工单" width="500px">
            <el-form :model="closeForm" label-width="80px">
                <el-form-item label="关闭原因" required>
                    <el-input v-model="closeForm.reason" type="textarea" :rows="3" placeholder="请输入关闭原因" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="closeDialogVisible = false">取消</el-button>
                <el-button type="primary" @click="submitClose" :loading="submitLoading">确定</el-button>
            </template>
        </el-dialog>

        <!-- 处理投诉弹窗 -->
        <el-dialog v-model="complaintHandleDialogVisible" title="处理投诉" width="600px">
            <el-form :model="complaintHandleForm" label-width="80px">
                <el-form-item label="处理动作">
                    <el-select v-model="complaintHandleForm.action" placeholder="请选择处理动作">
                        <el-option label="无" :value="0" />
                        <el-option label="警告" :value="1" />
                        <el-option label="扣款" :value="2" />
                        <el-option label="禁用" :value="3" />
                        <el-option label="其他" :value="4" />
                    </el-select>
                </el-form-item>
                <el-form-item v-if="complaintHandleForm.action === 2" label="扣款金额">
                    <el-input-number v-model="complaintHandleForm.amount" :min="0" :precision="2" />
                </el-form-item>
                <el-form-item label="处理结果" required>
                    <el-input v-model="complaintHandleForm.result" type="textarea" :rows="4" placeholder="请输入处理结果" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="complaintHandleDialogVisible = false">取消</el-button>
                <el-button type="primary" @click="submitComplaintHandle" :loading="submitLoading">确定</el-button>
            </template>
        </el-dialog>

        <!-- 审核补拍弹窗 -->
        <el-dialog v-model="auditDialogVisible" title="审核补拍申请" width="500px">
            <el-form :model="auditForm" label-width="80px">
                <el-form-item label="审核结果">
                    <el-radio-group v-model="auditForm.approved">
                        <el-radio :label="1">通过</el-radio>
                        <el-radio :label="0">拒绝</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item v-if="auditForm.approved === 1" label="是否免费">
                    <el-radio-group v-model="auditForm.is_free">
                        <el-radio :label="1">免费</el-radio>
                        <el-radio :label="0">收费</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item v-if="auditForm.approved === 1 && auditForm.is_free === 0" label="费用">
                    <el-input-number v-model="auditForm.fee" :min="0" :precision="2" />
                </el-form-item>
                <el-form-item label="备注">
                    <el-input v-model="auditForm.remark" type="textarea" :rows="3" placeholder="请输入备注" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="auditDialogVisible = false">取消</el-button>
                <el-button type="primary" @click="submitAudit" :loading="submitLoading">确定</el-button>
            </template>
        </el-dialog>

        <!-- 安排补拍弹窗 -->
        <el-dialog v-model="scheduleDialogVisible" title="安排补拍" width="500px">
            <el-form :model="scheduleForm" label-width="80px">
                <el-form-item label="拍摄日期" required>
                    <el-date-picker v-model="scheduleForm.schedule_date" type="date" placeholder="选择日期" value-format="YYYY-MM-DD" />
                </el-form-item>
                <el-form-item label="服务人员">
                    <el-select v-model="scheduleForm.new_staff_id" placeholder="请选择服务人员" filterable clearable>
                        <el-option v-for="item in staffList" :key="item.id" :label="item.name" :value="item.id" />
                    </el-select>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="scheduleDialogVisible = false">取消</el-button>
                <el-button type="primary" @click="submitSchedule" :loading="submitLoading">确定</el-button>
            </template>
        </el-dialog>

        <!-- 回访弹窗 -->
        <el-dialog v-model="callbackDialogVisible" title="完成回访" width="600px">
            <el-form :model="callbackForm" label-width="100px">
                <el-form-item label="满意度评分">
                    <el-rate v-model="callbackForm.score" />
                </el-form-item>
                <el-form-item label="服务态度">
                    <el-rate v-model="callbackForm.score_service" />
                </el-form-item>
                <el-form-item label="专业水平">
                    <el-rate v-model="callbackForm.score_professional" />
                </el-form-item>
                <el-form-item label="时间守约">
                    <el-rate v-model="callbackForm.score_punctual" />
                </el-form-item>
                <el-form-item label="回访时长(秒)">
                    <el-input-number v-model="callbackForm.duration" :min="0" />
                </el-form-item>
                <el-form-item label="回访内容">
                    <el-input v-model="callbackForm.content" type="textarea" :rows="3" placeholder="请输入回访内容" />
                </el-form-item>
                <el-form-item label="回访摘要">
                    <el-input v-model="callbackForm.summary" type="textarea" :rows="2" placeholder="请输入回访摘要" />
                </el-form-item>
                <el-form-item label="是否有问题">
                    <el-switch v-model="callbackForm.has_problem" :active-value="1" :inactive-value="0" />
                </el-form-item>
                <template v-if="callbackForm.has_problem === 1">
                    <el-form-item label="问题类型">
                        <el-input v-model="callbackForm.problem_type" placeholder="请输入问题类型" />
                    </el-form-item>
                    <el-form-item label="问题描述">
                        <el-input v-model="callbackForm.problem_desc" type="textarea" :rows="2" placeholder="请输入问题描述" />
                    </el-form-item>
                </template>
            </el-form>
            <template #footer>
                <el-button @click="callbackDialogVisible = false">取消</el-button>
                <el-button type="primary" @click="submitCallback" :loading="submitLoading">确定</el-button>
            </template>
        </el-dialog>

        <!-- 详情抽屉 -->
        <el-drawer v-model="detailDrawerVisible" :title="detailDrawerTitle" size="600px">
            <template v-if="currentDetail">
                <el-descriptions :column="2" border>
                    <el-descriptions-item v-for="(value, key) in detailFields" :key="key" :label="value.label">
                        {{ formatDetailValue(currentDetail, key, value) }}
                    </el-descriptions-item>
                </el-descriptions>
                <template v-if="currentDetail.logs && currentDetail.logs.length > 0">
                    <el-divider>操作日志</el-divider>
                    <el-timeline>
                        <el-timeline-item
                            v-for="log in currentDetail.logs"
                            :key="log.id"
                            :timestamp="formatTime(log.create_time)"
                            placement="top"
                        >
                            <el-card shadow="never">
                                <p>{{ log.content }}</p>
                            </el-card>
                        </el-timeline-item>
                    </el-timeline>
                </template>
            </template>
        </el-drawer>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Tickets, Warning, Camera, Phone, Plus } from '@element-plus/icons-vue'
import {
    getAfterSaleStatistics,
    getTicketLists,
    getTicketDetail,
    assignTicket,
    handleTicket,
    closeTicket,
    getComplaintLists,
    getComplaintDetail,
    handleComplaint,
    getReshootLists,
    getReshootDetail,
    auditReshoot,
    scheduleReshoot,
    completeReshoot as completeReshootApi,
    getCallbackLists,
    getCallbackDetail,
    completeCallback,
    markUnreachable as markUnreachableApi,
    escalateProblem as escalateProblemApi
} from '@/api/aftersale'

// 统计数据
const statistics = ref<any>({})

// 标签页
const activeTab = ref('ticket')

// 工单相关
const ticketList = ref<any[]>([])
const ticketLoading = ref(false)
const ticketSearch = reactive({
    ticket_sn: '',
    type: '',
    priority: '',
    status: ''
})
const ticketPager = reactive({
    page: 1,
    limit: 10,
    total: 0
})

// 投诉相关
const complaintList = ref<any[]>([])
const complaintLoading = ref(false)
const complaintSearch = reactive({
    complaint_sn: '',
    type: '',
    level: '',
    status: ''
})
const complaintPager = reactive({
    page: 1,
    limit: 10,
    total: 0
})

// 补拍相关
const reshootList = ref<any[]>([])
const reshootLoading = ref(false)
const reshootSearch = reactive({
    reshoot_sn: '',
    type: '',
    status: ''
})
const reshootPager = reactive({
    page: 1,
    limit: 10,
    total: 0
})

// 回访相关
const callbackList = ref<any[]>([])
const callbackLoading = ref(false)
const callbackSearch = reactive({
    callback_sn: '',
    type: '',
    status: '',
    has_problem: ''
})
const callbackPager = reactive({
    page: 1,
    limit: 10,
    total: 0
})

// 弹窗相关
const submitLoading = ref(false)
const adminList = ref<any[]>([])
const staffList = ref<any[]>([])

// 分配工单
const assignDialogVisible = ref(false)
const assignForm = reactive({
    id: 0,
    admin_id: ''
})

// 处理工单
const handleDialogVisible = ref(false)
const handleForm = reactive({
    id: 0,
    result: '',
    imageList: [] as any[]
})

// 关闭工单
const closeDialogVisible = ref(false)
const closeForm = reactive({
    id: 0,
    reason: ''
})

// 处理投诉
const complaintHandleDialogVisible = ref(false)
const complaintHandleForm = reactive({
    id: 0,
    action: 0,
    amount: 0,
    result: ''
})

// 审核补拍
const auditDialogVisible = ref(false)
const auditForm = reactive({
    id: 0,
    approved: 1,
    is_free: 1,
    fee: 0,
    remark: ''
})

// 安排补拍
const scheduleDialogVisible = ref(false)
const scheduleForm = reactive({
    id: 0,
    schedule_date: '',
    new_staff_id: ''
})

// 回访
const callbackDialogVisible = ref(false)
const callbackForm = reactive({
    id: 0,
    score: 5,
    score_service: 5,
    score_professional: 5,
    score_punctual: 5,
    duration: 0,
    content: '',
    summary: '',
    has_problem: 0,
    problem_type: '',
    problem_desc: ''
})

// 详情抽屉
const detailDrawerVisible = ref(false)
const detailDrawerTitle = ref('')
const currentDetail = ref<any>(null)
const detailFields = ref<any>({})

// 获取统计数据
const getStatistics = async () => {
    try {
        const res = await getAfterSaleStatistics()
        statistics.value = res
    } catch (error) {
        console.error(error)
    }
}

// 获取工单列表
const getTicketList = async () => {
    ticketLoading.value = true
    try {
        const res = await getTicketLists({
            ...ticketSearch,
            page: ticketPager.page,
            limit: ticketPager.limit
        })
        ticketList.value = res.lists || []
        ticketPager.total = res.count || 0
    } catch (error) {
        console.error(error)
    } finally {
        ticketLoading.value = false
    }
}

// 获取投诉列表
const getComplaintList = async () => {
    complaintLoading.value = true
    try {
        const res = await getComplaintLists({
            ...complaintSearch,
            page: complaintPager.page,
            limit: complaintPager.limit
        })
        complaintList.value = res.lists || []
        complaintPager.total = res.count || 0
    } catch (error) {
        console.error(error)
    } finally {
        complaintLoading.value = false
    }
}

// 获取补拍列表
const getReshootList = async () => {
    reshootLoading.value = true
    try {
        const res = await getReshootLists({
            ...reshootSearch,
            page: reshootPager.page,
            limit: reshootPager.limit
        })
        reshootList.value = res.lists || []
        reshootPager.total = res.count || 0
    } catch (error) {
        console.error(error)
    } finally {
        reshootLoading.value = false
    }
}

// 获取回访列表
const getCallbackList = async () => {
    callbackLoading.value = true
    try {
        const res = await getCallbackLists({
            ...callbackSearch,
            page: callbackPager.page,
            limit: callbackPager.limit
        })
        callbackList.value = res.lists || []
        callbackPager.total = res.count || 0
    } catch (error) {
        console.error(error)
    } finally {
        callbackLoading.value = false
    }
}

// 重置搜索
const resetTicketSearch = () => {
    Object.assign(ticketSearch, { ticket_sn: '', type: '', priority: '', status: '' })
    ticketPager.page = 1
    getTicketList()
}

const resetComplaintSearch = () => {
    Object.assign(complaintSearch, { complaint_sn: '', type: '', level: '', status: '' })
    complaintPager.page = 1
    getComplaintList()
}

const resetReshootSearch = () => {
    Object.assign(reshootSearch, { reshoot_sn: '', type: '', status: '' })
    reshootPager.page = 1
    getReshootList()
}

const resetCallbackSearch = () => {
    Object.assign(callbackSearch, { callback_sn: '', type: '', status: '', has_problem: '' })
    callbackPager.page = 1
    getCallbackList()
}

// 标签页切换
const handleTabChange = (name: string) => {
    if (name === 'ticket') getTicketList()
    else if (name === 'complaint') getComplaintList()
    else if (name === 'reshoot') getReshootList()
    else if (name === 'callback') getCallbackList()
}

// 状态样式
const getPriorityType = (priority: number) => {
    const map: any = { 1: 'info', 2: '', 3: 'warning', 4: 'danger' }
    return map[priority] || ''
}

const getStatusType = (status: number) => {
    const map: any = { 0: 'info', 1: 'warning', 2: 'primary', 3: 'success', 4: '', 5: 'info' }
    return map[status] || ''
}

const getLevelType = (level: number) => {
    const map: any = { 1: 'info', 2: 'warning', 3: 'danger' }
    return map[level] || ''
}

const getComplaintStatusType = (status: number) => {
    const map: any = { 0: 'info', 1: 'warning', 2: 'success', 3: 'primary', 4: '' }
    return map[status] || ''
}

const getReshootStatusType = (status: number) => {
    const map: any = { 0: 'info', 1: 'success', 2: 'danger', 3: 'warning', 4: 'success', 5: '' }
    return map[status] || ''
}

const getCallbackStatusType = (status: number) => {
    const map: any = { 0: 'info', 1: 'success', 2: 'warning', 3: '' }
    return map[status] || ''
}

// 查看详情
const viewTicket = async (row: any) => {
    const res = await getTicketDetail(row.id)
    currentDetail.value = res
    detailDrawerTitle.value = '工单详情'
    detailFields.value = {
        ticket_sn: { label: '工单编号' },
        title: { label: '标题' },
        type_desc: { label: '类型' },
        priority_desc: { label: '优先级' },
        status_desc: { label: '状态' },
        content: { label: '内容', span: 2 },
        handle_result: { label: '处理结果', span: 2 },
        create_time: { label: '创建时间', type: 'time' }
    }
    detailDrawerVisible.value = true
}

const viewComplaint = async (row: any) => {
    const res = await getComplaintDetail(row.id)
    currentDetail.value = res
    detailDrawerTitle.value = '投诉详情'
    detailFields.value = {
        complaint_sn: { label: '投诉编号' },
        title: { label: '标题' },
        type_desc: { label: '类型' },
        level_desc: { label: '等级' },
        status_desc: { label: '状态' },
        content: { label: '内容', span: 2 },
        expect_result: { label: '期望结果', span: 2 },
        handle_result: { label: '处理结果', span: 2 },
        create_time: { label: '创建时间', type: 'time' }
    }
    detailDrawerVisible.value = true
}

const viewReshoot = async (row: any) => {
    const res = await getReshootDetail(row.id)
    currentDetail.value = res
    detailDrawerTitle.value = '补拍申请详情'
    detailFields.value = {
        reshoot_sn: { label: '申请编号' },
        type_desc: { label: '申请类型' },
        reason_type_desc: { label: '原因类型' },
        status_desc: { label: '状态' },
        reason: { label: '详细原因', span: 2 },
        expect_date: { label: '期望日期' },
        schedule_date: { label: '安排日期' },
        create_time: { label: '创建时间', type: 'time' }
    }
    detailDrawerVisible.value = true
}

const viewCallback = async (row: any) => {
    const res = await getCallbackDetail(row.id)
    currentDetail.value = res
    detailDrawerTitle.value = '回访详情'
    detailFields.value = {
        callback_sn: { label: '回访编号' },
        type_desc: { label: '类型' },
        method_desc: { label: '方式' },
        status_desc: { label: '状态' },
        score: { label: '满意度' },
        content: { label: '回访内容', span: 2 },
        summary: { label: '回访摘要', span: 2 },
        plan_time: { label: '计划时间', type: 'time' },
        actual_time: { label: '实际时间', type: 'time' }
    }
    detailDrawerVisible.value = true
}

// 弹窗操作
const showAssignDialog = (row: any) => {
    assignForm.id = row.id
    assignForm.admin_id = ''
    assignDialogVisible.value = true
}

const showHandleDialog = (row: any) => {
    handleForm.id = row.id
    handleForm.result = ''
    handleForm.imageList = []
    handleDialogVisible.value = true
}

const showCloseDialog = (row: any) => {
    closeForm.id = row.id
    closeForm.reason = ''
    closeDialogVisible.value = true
}

const showComplaintHandleDialog = (row: any) => {
    complaintHandleForm.id = row.id
    complaintHandleForm.action = 0
    complaintHandleForm.amount = 0
    complaintHandleForm.result = ''
    complaintHandleDialogVisible.value = true
}

const showAuditDialog = (row: any) => {
    auditForm.id = row.id
    auditForm.approved = 1
    auditForm.is_free = 1
    auditForm.fee = 0
    auditForm.remark = ''
    auditDialogVisible.value = true
}

const showScheduleDialog = (row: any) => {
    scheduleForm.id = row.id
    scheduleForm.schedule_date = ''
    scheduleForm.new_staff_id = ''
    scheduleDialogVisible.value = true
}

const showCallbackDialog = (row: any) => {
    callbackForm.id = row.id
    callbackForm.score = 5
    callbackForm.score_service = 5
    callbackForm.score_professional = 5
    callbackForm.score_punctual = 5
    callbackForm.duration = 0
    callbackForm.content = ''
    callbackForm.summary = ''
    callbackForm.has_problem = 0
    callbackForm.problem_type = ''
    callbackForm.problem_desc = ''
    callbackDialogVisible.value = true
}

// 提交操作
const submitAssign = async () => {
    if (!assignForm.admin_id) {
        ElMessage.warning('请选择处理人')
        return
    }
    submitLoading.value = true
    try {
        await assignTicket(assignForm)
        ElMessage.success('分配成功')
        assignDialogVisible.value = false
        getTicketList()
        getStatistics()
    } catch (error) {
        console.error(error)
    } finally {
        submitLoading.value = false
    }
}

const submitHandle = async () => {
    if (!handleForm.result) {
        ElMessage.warning('请输入处理结果')
        return
    }
    submitLoading.value = true
    try {
        await handleTicket({
            id: handleForm.id,
            result: handleForm.result,
            images: handleForm.imageList.map((item: any) => item.response?.data?.url || item.url)
        })
        ElMessage.success('处理成功')
        handleDialogVisible.value = false
        getTicketList()
        getStatistics()
    } catch (error) {
        console.error(error)
    } finally {
        submitLoading.value = false
    }
}

const submitClose = async () => {
    if (!closeForm.reason) {
        ElMessage.warning('请输入关闭原因')
        return
    }
    submitLoading.value = true
    try {
        await closeTicket(closeForm)
        ElMessage.success('关闭成功')
        closeDialogVisible.value = false
        getTicketList()
        getStatistics()
    } catch (error) {
        console.error(error)
    } finally {
        submitLoading.value = false
    }
}

const submitComplaintHandle = async () => {
    if (!complaintHandleForm.result) {
        ElMessage.warning('请输入处理结果')
        return
    }
    submitLoading.value = true
    try {
        await handleComplaint(complaintHandleForm)
        ElMessage.success('处理成功')
        complaintHandleDialogVisible.value = false
        getComplaintList()
        getStatistics()
    } catch (error) {
        console.error(error)
    } finally {
        submitLoading.value = false
    }
}

const submitAudit = async () => {
    submitLoading.value = true
    try {
        await auditReshoot(auditForm)
        ElMessage.success('审核成功')
        auditDialogVisible.value = false
        getReshootList()
        getStatistics()
    } catch (error) {
        console.error(error)
    } finally {
        submitLoading.value = false
    }
}

const submitSchedule = async () => {
    if (!scheduleForm.schedule_date) {
        ElMessage.warning('请选择拍摄日期')
        return
    }
    submitLoading.value = true
    try {
        await scheduleReshoot(scheduleForm)
        ElMessage.success('安排成功')
        scheduleDialogVisible.value = false
        getReshootList()
    } catch (error) {
        console.error(error)
    } finally {
        submitLoading.value = false
    }
}

const completeReshoot = async (row: any) => {
    await ElMessageBox.confirm('确认补拍已完成？', '提示', { type: 'warning' })
    try {
        await completeReshootApi({ id: row.id, remark: '' })
        ElMessage.success('操作成功')
        getReshootList()
        getStatistics()
    } catch (error) {
        console.error(error)
    }
}

const submitCallback = async () => {
    submitLoading.value = true
    try {
        await completeCallback(callbackForm)
        ElMessage.success('回访完成')
        callbackDialogVisible.value = false
        getCallbackList()
        getStatistics()
    } catch (error) {
        console.error(error)
    } finally {
        submitLoading.value = false
    }
}

const markUnreachable = async (row: any) => {
    await ElMessageBox.confirm('确认标记为无法联系？', '提示', { type: 'warning' })
    try {
        await markUnreachableApi({ id: row.id })
        ElMessage.success('标记成功')
        getCallbackList()
    } catch (error) {
        console.error(error)
    }
}

const escalateProblem = async (row: any) => {
    await ElMessageBox.confirm('确认将问题升级为工单？', '提示', { type: 'warning' })
    try {
        await escalateProblemApi({ id: row.id })
        ElMessage.success('升级成功')
        getCallbackList()
        getStatistics()
    } catch (error) {
        console.error(error)
    }
}

// 格式化
const formatTime = (time: number) => {
    if (!time) return ''
    const date = new Date(time * 1000)
    return date.toLocaleString()
}

const formatDetailValue = (detail: any, key: string, config: any) => {
    const value = detail[key]
    if (config.type === 'time' && value) {
        return typeof value === 'number' ? formatTime(value) : value
    }
    return value || '-'
}

onMounted(() => {
    getStatistics()
    getTicketList()
})
</script>

<style scoped lang="scss">
.aftersale-container {
    padding: 20px;
}

.stat-card {
    .stat-content {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .stat-icon {
        width: 64px;
        height: 64px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;

        &.ticket-icon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        &.complaint-icon { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        &.reshoot-icon { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        &.callback-icon { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
    }

    .stat-info {
        flex: 1;
    }

    .stat-value {
        font-size: 28px;
        font-weight: 600;
        color: #303133;
    }

    .stat-label {
        font-size: 14px;
        color: #909399;
    }

    .stat-footer {
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #ebeef5;
        font-size: 13px;
        color: #606266;
    }
}

.search-bar {
    background: #f5f7fa;
    padding: 16px;
    border-radius: 4px;
}

.pagination-container {
    margin-top: 20px;
    display: flex;
    justify-content: flex-end;
}

.text-warning { color: #e6a23c; }
.text-danger { color: #f56c6c; }
.text-primary { color: #409eff; }
.text-muted { color: #909399; }

.mb-4 { margin-bottom: 16px; }
</style>
