<template>
    <admin-page-shell class="aftersale-container" title="售后工单">
        <!-- 统计卡片 -->
        <el-row :gutter="16" class="mb-4">
            <el-col :span="8">
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
                        未完成: <span class="text-warning">{{ statistics.ticket?.unfinished || statistics.ticket?.pending || 0 }}</span>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="8">
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
            <el-col :span="8">
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
            <div class="aftersale-toolbar">
                <el-button type="primary" @click="showSettingDialog">售后设置</el-button>
            </div>
            <el-tabs v-model="activeTab" @tab-change="handleTabChange">
                <!-- 工单管理 -->
                <el-tab-pane label="工单管理" name="ticket">
                    <div class="search-bar mb-4">
                        <el-form :inline="true" :model="ticketSearch">
                            <el-form-item class="w-[200px]" label="工单编号">
                                <el-input v-model="ticketSearch.ticket_sn" placeholder="请输入工单编号" clearable />
                            </el-form-item>
                            <el-form-item class="w-[180px]" label="工单类型">
                                <el-select v-model="ticketSearch.type" placeholder="全部类型" clearable>
                                    <el-option label="投诉" :value="1" />
                                    <el-option label="咨询" :value="2" />
                                    <el-option label="售后" :value="3" />
                                    <el-option label="建议" :value="4" />
                                    <el-option label="其他" :value="5" />
                                </el-select>
                            </el-form-item>
                            <el-form-item class="w-[180px]" label="优先级">
                                <el-select v-model="ticketSearch.priority" placeholder="全部优先级" clearable>
                                    <el-option label="低" :value="1" />
                                    <el-option label="中" :value="2" />
                                    <el-option label="高" :value="3" />
                                    <el-option label="紧急" :value="4" />
                                </el-select>
                            </el-form-item>
                            <el-form-item class="w-[180px]" label="状态">
                                <el-select v-model="ticketSearch.status" placeholder="全部状态" clearable>
                                    <el-option label="未完成" value="unfinished" />
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
                        <el-table-column label="关联订单" min-width="190">
                            <template #default="{ row }">
                                <div class="aftersale-order-cell">
                                    <span>{{ row.order_info?.order_sn || '未关联' }}</span>
                                    <small v-if="row.order_info?.staff_name">{{ row.order_info.staff_name }}</small>
                                </div>
                            </template>
                        </el-table-column>
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
                            <el-form-item class="w-[200px]" label="投诉编号">
                                <el-input v-model="complaintSearch.complaint_sn" placeholder="请输入投诉编号" clearable />
                            </el-form-item>
                            <el-form-item class="w-[180px]" label="投诉类型">
                                <el-select v-model="complaintSearch.type" placeholder="全部类型" clearable>
                                    <el-option label="服务态度" :value="1" />
                                    <el-option label="履约偏差" :value="2" />
                                    <el-option label="沟通问题" :value="3" />
                                    <el-option label="执行落差" :value="4" />
                                    <el-option label="其他" :value="5" />
                                </el-select>
                            </el-form-item>
                            <el-form-item class="w-[180px]" label="投诉等级">
                                <el-select v-model="complaintSearch.level" placeholder="全部等级" clearable>
                                    <el-option label="一般" :value="1" />
                                    <el-option label="严重" :value="2" />
                                    <el-option label="紧急" :value="3" />
                                </el-select>
                            </el-form-item>
                            <el-form-item class="w-[180px]" label="状态">
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

                <!-- 回访管理 -->
                <el-tab-pane label="回访管理" name="callback">
                    <div class="search-bar mb-4">
                        <el-form :inline="true" :model="callbackSearch">
                            <el-form-item class="w-[200px]" label="回访编号">
                                <el-input v-model="callbackSearch.callback_sn" placeholder="请输入回访编号" clearable />
                            </el-form-item>
                            <el-form-item class="w-[180px]" label="回访类型">
                                <el-select v-model="callbackSearch.type" placeholder="全部类型" clearable>
                                    <el-option label="服务前" :value="1" />
                                    <el-option label="服务中" :value="2" />
                                    <el-option label="服务后" :value="3" />
                                </el-select>
                            </el-form-item>
                            <el-form-item class="w-[180px]" label="状态">
                                <el-select v-model="callbackSearch.status" placeholder="全部状态" clearable>
                                    <el-option label="待回访" :value="0" />
                                    <el-option label="已回访" :value="1" />
                                    <el-option label="无法联系" :value="2" />
                                </el-select>
                            </el-form-item>
                            <el-form-item class="w-[180px]" label="是否有问题">
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

                    <el-table :data="callbackList" v-loading="callbackLoading" stripe class="aftersale-callback-table">
                        <el-table-column prop="callback_sn" label="回访编号" min-width="190" />
                        <el-table-column prop="user.nickname" label="客户" min-width="130" />
                        <el-table-column prop="staff.name" label="服务人员" min-width="140" />
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
        <el-dialog v-model="settingDialogVisible" title="售后设置" width="520px">
            <el-form :model="settingForm" label-width="150px">
                <el-form-item label="自动回访时间">
                    <div class="aftersale-setting-field">
                        <span>订单完成后</span>
                        <el-input-number
                            v-model="settingForm.auto_callback_plan_days"
                            :min="0"
                            :max="365"
                            controls-position="right"
                        />
                        <span>天计划回访</span>
                    </div>
                    <div class="form-tip">填 0 表示订单完成当天计划回访；系统会自动创建服务后问卷回访任务。</div>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="settingDialogVisible = false">取消</el-button>
                <el-button type="primary" :loading="settingSubmitting" @click="submitSetting">保存</el-button>
            </template>
        </el-dialog>

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
                        <el-option label="记录扣款" :value="2" />
                        <el-option label="禁用" :value="3" />
                        <el-option label="其他" :value="4" />
                    </el-select>
                </el-form-item>
                <el-form-item v-if="complaintHandleForm.action === 2" label="记录金额">
                    <el-input-number v-model="complaintHandleForm.amount" :min="0" :precision="2" />
                    <div class="form-tip">仅记录服务处理金额，不会自动发起客户退款。</div>
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
        <el-drawer
            v-model="detailDrawerVisible"
            :title="detailDrawerTitle"
            :size="detailMode ? '760px' : '600px'"
            :class="{ 'ticket-detail-drawer': Boolean(detailMode) }"
        >
            <template v-if="currentDetail">
                <template v-if="detailMode === 'ticket'">
                    <div class="ticket-detail">
                        <section class="ticket-detail-hero">
                            <div class="ticket-detail-hero__content">
                                <div class="ticket-detail-hero__meta">
                                    <span>{{ ticketDetailSummary.ticketSn }}</span>
                                    <el-tag :type="getStatusType(Number(currentDetail.status || 0))" effect="dark" size="small">
                                        {{ ticketDetailSummary.status }}
                                    </el-tag>
                                </div>
                                <h3>{{ ticketDetailSummary.title }}</h3>
                                <div class="ticket-detail-hero__tags">
                                    <el-tag :type="getPriorityType(Number(currentDetail.priority || 0))" size="small">
                                        {{ ticketDetailSummary.priority }}
                                    </el-tag>
                                    <el-tag size="small">{{ ticketDetailSummary.type }}</el-tag>
                                </div>
                            </div>
                            <div class="ticket-detail-hero__time">
                                <span>创建：{{ ticketDetailSummary.createdAt }}</span>
                                <span>更新：{{ ticketDetailSummary.updatedAt }}</span>
                            </div>
                        </section>

                        <section class="ticket-detail-section">
                            <div class="ticket-detail-section__head">
                                <h4>关键概况</h4>
                            </div>
                            <div class="ticket-detail-grid">
                                <div
                                    v-for="item in ticketOverviewItems"
                                    :key="item.label"
                                    class="ticket-detail-grid__item"
                                    :class="{ 'is-wide': item.wide }"
                                >
                                    <span>{{ item.label }}</span>
                                    <strong>{{ item.value }}</strong>
                                </div>
                            </div>
                        </section>

                        <section class="ticket-detail-section">
                            <div class="ticket-detail-section__head">
                                <h4>问题内容</h4>
                            </div>
                            <div class="ticket-detail-content">
                                <h5>{{ ticketDetailSummary.title }}</h5>
                                <p>{{ ticketDetailSummary.content }}</p>
                                <div v-if="ticketImageList.length" class="ticket-detail-images">
                                    <el-image
                                        v-for="(image, index) in ticketImageList"
                                        :key="`${image}-${index}`"
                                        :src="image"
                                        :preview-src-list="ticketImageList"
                                        :initial-index="index"
                                        fit="cover"
                                        preview-teleported
                                    />
                                </div>
                            </div>
                        </section>

                        <section class="ticket-detail-section">
                            <div class="ticket-detail-section__head">
                                <h4>处理结果</h4>
                            </div>
                            <div class="ticket-detail-result">
                                {{ ticketDetailSummary.handleResult }}
                            </div>
                        </section>

                        <section class="ticket-detail-section">
                            <div class="ticket-detail-section__head">
                                <h4>进度日志</h4>
                                <span>{{ ticketLogItems.length }} 条</span>
                            </div>
                            <el-timeline v-if="ticketLogItems.length" class="ticket-detail-timeline">
                                <el-timeline-item
                                    v-for="log in ticketLogItems"
                                    :key="log.id"
                                    :timestamp="log.time"
                                    :type="log.timelineType"
                                    placement="top"
                                >
                                    <div class="ticket-log-card">
                                        <div class="ticket-log-card__main">
                                            <strong>{{ log.content }}</strong>
                                            <el-tag v-if="log.statusChange" size="small" effect="plain">
                                                {{ log.statusChange }}
                                            </el-tag>
                                        </div>
                                        <div class="ticket-log-card__meta">
                                            <span>{{ log.operator }}</span>
                                        </div>
                                        <div v-if="log.images.length" class="ticket-detail-images is-log">
                                            <el-image
                                                v-for="(image, index) in log.images"
                                                :key="`${log.id}-${image}-${index}`"
                                                :src="image"
                                                :preview-src-list="log.images"
                                                :initial-index="index"
                                                fit="cover"
                                                preview-teleported
                                            />
                                        </div>
                                    </div>
                                </el-timeline-item>
                            </el-timeline>
                            <el-empty v-else description="暂无进度日志" :image-size="80" />
                        </section>
                    </div>
                </template>
                <template v-else-if="detailMode === 'complaint'">
                    <div class="ticket-detail">
                        <section class="ticket-detail-hero complaint-detail-hero">
                            <div class="ticket-detail-hero__content">
                                <div class="ticket-detail-hero__meta">
                                    <span>{{ complaintDetailSummary.complaintSn }}</span>
                                    <el-tag :type="getComplaintStatusType(Number(currentDetail.status || 0))" effect="dark" size="small">
                                        {{ complaintDetailSummary.status }}
                                    </el-tag>
                                </div>
                                <h3>{{ complaintDetailSummary.title }}</h3>
                                <div class="ticket-detail-hero__tags">
                                    <el-tag size="small">{{ complaintDetailSummary.type }}</el-tag>
                                    <el-tag :type="getLevelType(Number(currentDetail.level || 0))" size="small">
                                        {{ complaintDetailSummary.level }}
                                    </el-tag>
                                </div>
                            </div>
                            <div class="ticket-detail-hero__time">
                                <span>提交：{{ complaintDetailSummary.createdAt }}</span>
                                <span>处理：{{ complaintDetailSummary.handledAt }}</span>
                            </div>
                        </section>

                        <section class="ticket-detail-section">
                            <div class="ticket-detail-section__head">
                                <h4>关键概况</h4>
                            </div>
                            <div class="ticket-detail-grid">
                                <div
                                    v-for="item in complaintOverviewItems"
                                    :key="item.label"
                                    class="ticket-detail-grid__item"
                                    :class="{ 'is-wide': item.wide }"
                                >
                                    <span>{{ item.label }}</span>
                                    <strong>{{ item.value }}</strong>
                                </div>
                            </div>
                        </section>

                        <section class="ticket-detail-section">
                            <div class="ticket-detail-section__head">
                                <h4>投诉内容</h4>
                            </div>
                            <div class="ticket-detail-content">
                                <h5>{{ complaintDetailSummary.title }}</h5>
                                <p>{{ complaintDetailSummary.content }}</p>
                                <div v-if="complaintImageList.length" class="ticket-detail-images">
                                    <el-image
                                        v-for="(image, index) in complaintImageList"
                                        :key="`${image}-${index}`"
                                        :src="image"
                                        :preview-src-list="complaintImageList"
                                        :initial-index="index"
                                        fit="cover"
                                        preview-teleported
                                    />
                                </div>
                            </div>
                        </section>

                        <section class="ticket-detail-section">
                            <div class="ticket-detail-section__head">
                                <h4>期望与处理结果</h4>
                            </div>
                            <div class="ticket-detail-result-list">
                                <div>
                                    <span>期望结果</span>
                                    <p>{{ complaintDetailSummary.expectResult }}</p>
                                </div>
                                <div>
                                    <span>平台处理</span>
                                    <p>{{ complaintDetailSummary.handleResult }}</p>
                                </div>
                            </div>
                        </section>
                    </div>
                </template>
                <template v-else-if="detailMode === 'callback'">
                    <div class="ticket-detail">
                        <section class="ticket-detail-hero callback-detail-hero">
                            <div class="ticket-detail-hero__content">
                                <div class="ticket-detail-hero__meta">
                                    <span>{{ callbackDetailSummary.callbackSn }}</span>
                                    <el-tag :type="getCallbackStatusType(Number(currentDetail.status || 0))" effect="dark" size="small">
                                        {{ callbackDetailSummary.status }}
                                    </el-tag>
                                </div>
                                <h3>{{ callbackDetailSummary.title }}</h3>
                                <div class="ticket-detail-hero__tags">
                                    <el-tag size="small">{{ callbackDetailSummary.type }}</el-tag>
                                    <el-tag :type="Number(currentDetail.has_problem || 0) === 1 ? 'danger' : 'success'" size="small">
                                        {{ callbackDetailSummary.problemState }}
                                    </el-tag>
                                </div>
                            </div>
                            <div class="ticket-detail-hero__time">
                                <span>计划：{{ callbackDetailSummary.planTime }}</span>
                                <span>完成：{{ callbackDetailSummary.actualTime }}</span>
                            </div>
                        </section>

                        <section class="ticket-detail-section">
                            <div class="ticket-detail-section__head">
                                <h4>关键概况</h4>
                            </div>
                            <div class="ticket-detail-grid">
                                <div
                                    v-for="item in callbackOverviewItems"
                                    :key="item.label"
                                    class="ticket-detail-grid__item"
                                    :class="{ 'is-wide': item.wide }"
                                >
                                    <span>{{ item.label }}</span>
                                    <strong>{{ item.value }}</strong>
                                </div>
                            </div>
                        </section>

                        <section class="ticket-detail-section">
                            <div class="ticket-detail-section__head">
                                <h4>回访评分</h4>
                            </div>
                            <div class="callback-score-grid">
                                <div v-for="item in callbackScoreItems" :key="item.label">
                                    <span>{{ item.label }}</span>
                                    <strong>{{ item.value }}</strong>
                                </div>
                            </div>
                        </section>

                        <section class="ticket-detail-section">
                            <div class="ticket-detail-section__head">
                                <h4>回访内容</h4>
                            </div>
                            <div class="ticket-detail-result-list">
                                <div>
                                    <span>回访内容</span>
                                    <p>{{ callbackDetailSummary.content }}</p>
                                </div>
                                <div>
                                    <span>回访摘要</span>
                                    <p>{{ callbackDetailSummary.summary }}</p>
                                </div>
                                <div v-if="Number(currentDetail.has_problem || 0) === 1">
                                    <span>问题记录</span>
                                    <p>{{ callbackDetailSummary.problemText }}</p>
                                </div>
                            </div>
                        </section>
                    </div>
                </template>
                <template v-else>
                    <el-descriptions :column="2" border>
                        <el-descriptions-item v-for="(value, key) in detailFields" :key="key" :label="value.label">
                            {{ formatDetailValue(currentDetail, String(key), value) }}
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
            </template>
        </el-drawer>
    </admin-page-shell>
</template>

<script setup lang="ts">
import { computed, ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import type { TabPaneName } from 'element-plus'
import { Tickets, Warning, Phone, Plus } from '@element-plus/icons-vue'
import {
    getAfterSaleStatistics,
    getAfterSaleConfig,
    getTicketLists,
    getTicketDetail,
    setAfterSaleConfig,
    assignTicket,
    handleTicket,
    closeTicket,
    getComplaintLists,
    getComplaintDetail,
    handleComplaint,
    getCallbackLists,
    getCallbackDetail,
    completeCallback,
    markUnreachable as markUnreachableApi,
    escalateProblem as escalateProblemApi
} from '@/api/aftersale'
import { adminLists } from '@/api/perms/admin'

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
const settingDialogVisible = ref(false)
const settingSubmitting = ref(false)
const settingForm = reactive({
    auto_callback_plan_days: 7
})

// 分配工单
const assignDialogVisible = ref(false)
const assignForm = reactive({
    id: 0,
    admin_id: undefined as number | undefined
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
const detailMode = ref<'ticket' | 'complaint' | 'callback' | ''>('')

const safeText = (value: unknown, fallback = '-') => {
    if (value === undefined || value === null || value === '') {
        return fallback
    }

    const text = String(value).trim()
    return text || fallback
}

const normalizeImageList = (value: unknown): string[] => {
    if (Array.isArray(value)) {
        return value.map((item) => String(item || '').trim()).filter(Boolean)
    }

    if (typeof value === 'string') {
        const text = value.trim()
        if (!text) {
            return []
        }

        try {
            const parsed = JSON.parse(text)
            if (Array.isArray(parsed)) {
                return parsed.map((item) => String(item || '').trim()).filter(Boolean)
            }
        } catch (error) {
            return text
                .split(',')
                .map((item) => item.trim())
                .filter(Boolean)
        }
    }

    return []
}

const ticketImageList = computed(() => normalizeImageList(currentDetail.value?.images))
const ticketDetailSummary = computed(() => {
    const detail = currentDetail.value || {}
    return {
        ticketSn: safeText(detail.ticket_sn, '编号待补充'),
        title: safeText(detail.title, '未命名工单'),
        status: safeText(detail.status_desc, '未知状态'),
        priority: safeText(detail.priority_desc, '普通优先级'),
        type: safeText(detail.type_desc, '售后工单'),
        content: safeText(detail.content),
        handleResult: safeText(detail.handle_result, '暂无处理结果'),
        createdAt: formatDetailTime(detail.create_time),
        updatedAt: formatDetailTime(detail.update_time || detail.create_time)
    }
})
const ticketOverviewItems = computed(() => {
    const detail = currentDetail.value || {}
    const orderInfo = detail.order_info || {}
    return [
        { label: '用户', value: safeText(detail.user?.nickname || detail.user?.mobile) },
        { label: '处理人', value: safeText(detail.assign_admin?.name || detail.assign_admin?.account, '待分配') },
        { label: '工单类型', value: ticketDetailSummary.value.type },
        { label: '关联订单', value: safeText(orderInfo.order_sn || detail.order?.order_sn, '未关联') },
        { label: '服务日期', value: safeText(orderInfo.service_date) },
        { label: '服务人员', value: safeText(orderInfo.staff_name) },
        { label: '主套餐', value: safeText(orderInfo.package_name), wide: true },
        { label: '服务地址', value: safeText(orderInfo.service_address), wide: true }
    ]
})
const ticketLogItems = computed(() => {
    const logs = Array.isArray(currentDetail.value?.logs) ? currentDetail.value.logs : []
    return logs.map((log: any, index: number) => ({
        id: log?.id || `${log?.create_time || 'log'}-${index}`,
        content: getLogDisplayContent(log),
        operator: getLogOperatorText(log),
        statusChange: getLogStatusChangeText(log),
        time: formatDetailTime(log?.create_time),
        images: normalizeImageList(log?.images),
        timelineType: index === logs.length - 1 ? 'primary' : ''
    }))
})
const complaintImageList = computed(() => normalizeImageList(currentDetail.value?.images))
const complaintDetailSummary = computed(() => {
    const detail = currentDetail.value || {}
    return {
        complaintSn: safeText(detail.complaint_sn, '编号待补充'),
        title: safeText(detail.title, '未命名投诉'),
        status: safeText(detail.status_desc, '未知状态'),
        type: safeText(detail.type_desc, '服务投诉'),
        level: safeText(detail.level_desc, '一般'),
        content: safeText(detail.content),
        expectResult: safeText(detail.expect_result, '未填写'),
        handleResult: safeText(detail.handle_result, '暂无处理结果'),
        createdAt: formatDetailTime(detail.create_time),
        handledAt: formatDetailTime(detail.handle_time)
    }
})
const complaintOverviewItems = computed(() => {
    const detail = currentDetail.value || {}
    return [
        { label: '投诉人', value: safeText(detail.user?.nickname || detail.user?.mobile) },
        { label: '联系电话', value: safeText(detail.contact_mobile) },
        { label: '联系人', value: safeText(detail.contact_name) },
        { label: '被投诉人员', value: safeText(detail.staff?.name || detail.staff_name, '平台待核查') },
        { label: '关联订单', value: safeText(detail.order?.order_sn || detail.order_info?.order_sn, '未关联') },
        { label: '处理金额', value: formatMoneyText(detail.handle_amount) },
        { label: '投诉类型', value: complaintDetailSummary.value.type },
        { label: '投诉等级', value: complaintDetailSummary.value.level }
    ]
})
const callbackDetailSummary = computed(() => {
    const detail = currentDetail.value || {}
    return {
        callbackSn: safeText(detail.callback_sn, '编号待补充'),
        title: `${safeText(detail.type_desc, '服务')}回访`,
        status: safeText(detail.status_desc, '未知状态'),
        type: safeText(detail.method_desc || detail.type_desc, '回访'),
        problemState: Number(detail.has_problem || 0) === 1 ? '存在问题' : '无问题',
        planTime: formatDetailTime(detail.plan_time),
        actualTime: formatDetailTime(detail.actual_time),
        content: safeText(detail.content, '暂无回访内容'),
        summary: safeText(detail.summary, '暂无回访摘要'),
        problemText: [safeText(detail.problem_type, ''), safeText(detail.problem_desc, '')]
            .filter(Boolean)
            .join('：') || '未填写问题说明'
    }
})
const callbackOverviewItems = computed(() => {
    const detail = currentDetail.value || {}
    return [
        { label: '用户', value: safeText(detail.user?.nickname || detail.user?.mobile) },
        { label: '服务人员', value: safeText(detail.staff?.name || detail.staff_name) },
        { label: '关联订单', value: safeText(detail.order?.order_sn || detail.order_info?.order_sn, '未关联') },
        { label: '回访方式', value: safeText(detail.method_desc, '未记录') },
        { label: '回访时长', value: formatDurationText(detail.duration) },
        { label: '是否有问题', value: callbackDetailSummary.value.problemState }
    ]
})
const callbackScoreItems = computed(() => {
    const detail = currentDetail.value || {}
    return [
        { label: '总体满意度', value: formatScoreText(detail.score) },
        { label: '服务态度', value: formatScoreText(detail.score_service) },
        { label: '专业水平', value: formatScoreText(detail.score_professional) },
        { label: '时间守约', value: formatScoreText(detail.score_punctual) }
    ]
})

// 获取统计数据
const getStatistics = async () => {
    try {
        const res = await getAfterSaleStatistics()
        statistics.value = res
    } catch (error) {
        console.error(error)
    }
}

const getAdminList = async () => {
    try {
        const res = await adminLists({
            page_type: 0
        })
        adminList.value = res.lists || res || []
    } catch (error) {
        console.error(error)
    }
}

const fetchSetting = async () => {
    const res = await getAfterSaleConfig()
    settingForm.auto_callback_plan_days = Number(res?.auto_callback_plan_days ?? 7)
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

const resetCallbackSearch = () => {
    Object.assign(callbackSearch, { callback_sn: '', type: '', status: '', has_problem: '' })
    callbackPager.page = 1
    getCallbackList()
}

// 标签页切换
const handleTabChange = (name: TabPaneName) => {
    if (name === 'ticket') getTicketList()
    else if (name === 'complaint') getComplaintList()
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

const getCallbackStatusType = (status: number) => {
    const map: any = { 0: 'info', 1: 'success', 2: 'warning', 3: '' }
    return map[status] || ''
}

// 查看详情
const viewTicket = async (row: any) => {
    const res = await getTicketDetail(row.id)
    currentDetail.value = res
    detailDrawerTitle.value = '工单详情'
    detailMode.value = 'ticket'
    detailFields.value = {
        ticket_sn: { label: '工单编号' },
        title: { label: '标题' },
        type_desc: { label: '类型' },
        priority_desc: { label: '优先级' },
        status_desc: { label: '状态' },
        'order_info.order_sn': { label: '关联订单' },
        'order_info.order_status_desc': { label: '订单状态' },
        'order_info.service_date': { label: '服务日期' },
        'order_info.staff_name': { label: '主套餐服务人员' },
        'order_info.package_name': { label: '主套餐' },
        'order_info.service_address': { label: '服务地址', span: 2 },
        content: { label: '内容', span: 2 },
        handle_result: { label: '处理结果', span: 2 },
        create_time: { label: '创建时间', type: 'time' }
    }
    detailDrawerVisible.value = true
}

const showSettingDialog = async () => {
    await fetchSetting()
    settingDialogVisible.value = true
}

const viewComplaint = async (row: any) => {
    const res = await getComplaintDetail(row.id)
    currentDetail.value = res
    detailDrawerTitle.value = '投诉详情'
    detailMode.value = 'complaint'
    detailFields.value = {
        complaint_sn: { label: '投诉编号' },
        title: { label: '标题' },
        type_desc: { label: '类型' },
        level_desc: { label: '等级' },
        status_desc: { label: '状态' },
        contact_name: { label: '联系人' },
        contact_mobile: { label: '联系电话' },
        content: { label: '内容', span: 2 },
        expect_result: { label: '期望结果', span: 2 },
        handle_amount: { label: '记录扣款金额' },
        handle_result: { label: '处理结果', span: 2 },
        create_time: { label: '创建时间', type: 'time' }
    }
    detailDrawerVisible.value = true
}

const viewCallback = async (row: any) => {
    const res = await getCallbackDetail(row.id)
    currentDetail.value = res
    detailDrawerTitle.value = '回访详情'
    detailMode.value = 'callback'
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
    assignForm.admin_id = undefined
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
    const adminId = assignForm.admin_id

    if (!adminId) {
        ElMessage.warning('请选择处理人')
        return
    }
    submitLoading.value = true
    try {
        await assignTicket({
            id: assignForm.id,
            admin_id: adminId
        })
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

const submitSetting = async () => {
    settingSubmitting.value = true
    try {
        await setAfterSaleConfig({
            auto_callback_plan_days: Number(settingForm.auto_callback_plan_days || 0)
        })
        ElMessage.success('售后设置已保存')
        settingDialogVisible.value = false
    } catch (error) {
        console.error(error)
    } finally {
        settingSubmitting.value = false
    }
}

// 格式化
const formatTime = (time: number) => {
    if (!time) return ''
    const date = new Date(time * 1000)
    return date.toLocaleString()
}

const formatDetailTime = (time: unknown) => {
    if (!time) {
        return '-'
    }

    if (typeof time === 'number') {
        return formatTime(time) || '-'
    }

    const text = String(time).trim()
    if (!text) {
        return '-'
    }

    const numericTime = Number(text)
    if (!Number.isNaN(numericTime) && numericTime > 0) {
        return formatTime(numericTime) || text
    }

    return text
}

const formatMoneyText = (value: unknown) => {
    const amount = Number(value || 0)
    if (!amount) {
        return '-'
    }
    return `¥${amount.toFixed(2)}`
}

const formatScoreText = (value: unknown) => {
    const score = Number(value || 0)
    return score > 0 ? `${score} 分` : '未评分'
}

const formatDurationText = (value: unknown) => {
    const duration = Number(value || 0)
    if (!duration) {
        return '-'
    }
    if (duration < 60) {
        return `${duration} 秒`
    }
    return `${Math.floor(duration / 60)} 分 ${duration % 60} 秒`
}

const getLogDisplayContent = (log: any) =>
    safeText(log?.content_display || log?.content || log?.remark, '已更新进度')

const getLogOperatorText = (log: any) => {
    const typeMap: Record<number, string> = {
        1: '用户',
        2: '管理员',
        3: '系统'
    }
    const operatorType = typeMap[Number(log?.operator_type || 0)] || '操作人'
    const operatorName = safeText(log?.operator_name, '')
    return operatorName ? `${operatorType}：${operatorName}` : operatorType
}

const getLogStatusChangeText = (log: any) => {
    const oldStatus = Number(log?.old_status ?? -1)
    const newStatus = Number(log?.new_status ?? -1)
    if (oldStatus < 0 || newStatus < 0 || oldStatus === newStatus) {
        return ''
    }

    return `${getTicketStatusLabel(oldStatus)} → ${getTicketStatusLabel(newStatus)}`
}

const getTicketStatusLabel = (status: number) => {
    const map: Record<number, string> = {
        0: '待分配',
        1: '处理中',
        2: '待确认',
        3: '已完成',
        4: '已关闭',
        5: '已取消'
    }
    return map[status] || '未知'
}

const formatDetailValue = (detail: any, key: string, config: any) => {
    const value = getNestedValue(detail, key)
    if (config.type === 'time' && value) {
        return typeof value === 'number' ? formatTime(value) : value
    }
    return value || '-'
}

const getNestedValue = (data: any, key: string) => {
    return key.split('.').reduce((value: any, part: string) => {
        return value && typeof value === 'object' ? value[part] : undefined
    }, data)
}

onMounted(() => {
    getStatistics()
    getAdminList()
    getTicketList()
    fetchSetting()
})
</script>

<style scoped lang="scss">
.aftersale-container {
    padding: 20px;
}

.aftersale-toolbar {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 16px;
}

.aftersale-order-cell {
    display: flex;
    flex-direction: column;
    line-height: 1.5;

    small {
        color: #909399;
    }
}

.aftersale-setting-field {
    display: flex;
    align-items: center;
    gap: 10px;
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

.form-tip {
    margin-left: 12px;
    font-size: 12px;
    color: #909399;
}

.ticket-detail {
    display: flex;
    flex-direction: column;
    gap: 16px;
    padding: 2px 2px 18px;
    color: #303133;
}

.ticket-detail-hero,
.ticket-detail-section {
    border: 1px solid #ebeef5;
    border-radius: 10px;
    background: #fff;
}

.ticket-detail-hero {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 220px;
    gap: 20px;
    padding: 20px;
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 56%, #f5f7fb 100%);
}

.complaint-detail-hero {
    background: linear-gradient(135deg, #fff8f8 0%, #ffffff 58%, #fff5f6 100%);
}

.callback-detail-hero {
    background: linear-gradient(135deg, #f4fffb 0%, #ffffff 58%, #f0fbff 100%);
}

.ticket-detail-hero__content {
    min-width: 0;
}

.ticket-detail-hero__meta,
.ticket-detail-hero__tags,
.ticket-log-card__main,
.ticket-log-card__meta,
.ticket-detail-section__head {
    display: flex;
    align-items: center;
}

.ticket-detail-hero__meta {
    gap: 10px;
    color: #909399;
    font-size: 13px;
}

.ticket-detail-hero h3 {
    margin: 10px 0 12px;
    font-size: 20px;
    line-height: 1.35;
    font-weight: 700;
    color: #1f2d3d;
}

.ticket-detail-hero__tags {
    gap: 8px;
    flex-wrap: wrap;
}

.ticket-detail-hero__time {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 8px;
    padding-left: 20px;
    border-left: 1px solid #ebeef5;
    color: #606266;
    font-size: 13px;
}

.ticket-detail-section {
    padding: 18px;
}

.ticket-detail-section__head {
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
}

.ticket-detail-section__head h4 {
    margin: 0;
    font-size: 15px;
    font-weight: 700;
    color: #303133;
}

.ticket-detail-section__head span {
    font-size: 13px;
    color: #909399;
}

.ticket-detail-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.ticket-detail-grid__item {
    min-width: 0;
    padding: 12px 14px;
    border-radius: 8px;
    background: #f7f8fa;
}

.ticket-detail-grid__item.is-wide {
    grid-column: span 2;
}

.ticket-detail-grid__item span {
    display: block;
    margin-bottom: 6px;
    color: #909399;
    font-size: 12px;
}

.ticket-detail-grid__item strong {
    display: block;
    color: #303133;
    font-size: 14px;
    line-height: 1.5;
    word-break: break-word;
}

.ticket-detail-content h5 {
    margin: 0 0 10px;
    font-size: 15px;
    color: #303133;
}

.ticket-detail-content p,
.ticket-detail-result {
    margin: 0;
    padding: 14px;
    border-radius: 8px;
    background: #f7f8fa;
    color: #606266;
    line-height: 1.7;
    white-space: pre-wrap;
    word-break: break-word;
}

.ticket-detail-result-list {
    display: grid;
    gap: 12px;
}

.ticket-detail-result-list > div {
    padding: 14px;
    border-radius: 8px;
    background: #f7f8fa;
}

.ticket-detail-result-list span {
    display: block;
    margin-bottom: 8px;
    color: #909399;
    font-size: 12px;
}

.ticket-detail-result-list p {
    margin: 0;
    color: #606266;
    line-height: 1.7;
    white-space: pre-wrap;
    word-break: break-word;
}

.callback-score-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}

.callback-score-grid > div {
    padding: 14px;
    border-radius: 8px;
    background: #f7f8fa;
    text-align: center;
}

.callback-score-grid span {
    display: block;
    margin-bottom: 8px;
    color: #909399;
    font-size: 12px;
}

.callback-score-grid strong {
    color: #303133;
    font-size: 18px;
}

.ticket-detail-images {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 12px;
}

.ticket-detail-images :deep(.el-image) {
    width: 88px;
    height: 88px;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #ebeef5;
    background: #f7f8fa;
}

.ticket-detail-images.is-log :deep(.el-image) {
    width: 64px;
    height: 64px;
}

.ticket-detail-timeline {
    padding: 2px 0 0 4px;
}

.ticket-log-card {
    padding: 12px 14px;
    border: 1px solid #ebeef5;
    border-radius: 8px;
    background: #fbfcff;
}

.ticket-log-card__main {
    justify-content: space-between;
    gap: 12px;
}

.ticket-log-card__main strong {
    min-width: 0;
    flex: 1;
    color: #303133;
    line-height: 1.6;
    word-break: break-word;
}

.ticket-log-card__meta {
    margin-top: 8px;
    gap: 10px;
    color: #909399;
    font-size: 12px;
}

.mb-4 { margin-bottom: 16px; }
</style>
