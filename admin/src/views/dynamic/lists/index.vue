<template>
    <div class="dynamic-lists">
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item class="w-[150px]" label="动态类型">
                    <el-select v-model="queryParams.dynamic_type" placeholder="选择类型" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="图文" :value="1" />
                        <el-option label="视频" :value="2" />
                        <el-option label="活动" :value="4" />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[150px]" label="状态">
                    <el-select v-model="queryParams.status" placeholder="选择状态" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="待审核" :value="0" />
                        <el-option label="已发布" :value="1" />
                        <el-option label="已下架" :value="2" />
                        <el-option label="已拒绝" :value="3" />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[180px]" label="发布者类型">
                    <el-select v-model="queryParams.user_type" placeholder="选择类型" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="用户" :value="1" />
                        <el-option label="工作人员" :value="2" />
                        <el-option label="官方" :value="3" />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[150px]" label="置顶">
                    <el-select v-model="queryParams.is_top" placeholder="选择" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="是" :value="1" />
                        <el-option label="否" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[150px]" label="热门">
                    <el-select v-model="queryParams.is_hot" placeholder="选择" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="是" :value="1" />
                        <el-option label="否" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[200px]" label="内容">
                    <el-input
                        v-model="queryParams.content"
                        placeholder="搜索内容"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                    <el-button type="primary" @click="handleAdd">
                        <template #icon>
                            <icon name="el-icon-Plus" />
                        </template>
                        发布动态
                    </el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <!-- 统计卡片 -->
        <div class="mt-4 grid grid-cols-4 gap-4">
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">待审核</div>
                    <div class="text-2xl font-bold mt-2 text-orange-500">{{ getStatusCount(0) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已发布</div>
                    <div class="text-2xl font-bold mt-2 text-green-500">{{ getStatusCount(1) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已下架</div>
                    <div class="text-2xl font-bold mt-2 text-gray-500">{{ getStatusCount(2) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已拒绝</div>
                    <div class="text-2xl font-bold mt-2 text-red-500">{{ getStatusCount(3) }}</div>
                </div>
            </el-card>
        </div>

        <el-card class="!border-none mt-4" shadow="never">
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="ID" prop="id" width="80" />
                <el-table-column label="发布者" min-width="150">
                    <template #default="{ row }">
                        <div class="flex items-center" v-if="row.publisher">
                            <el-avatar :src="row.publisher.avatar" :size="32" />
                            <div class="ml-2">
                                <div>{{ row.publisher.nickname }}</div>
                                <div class="text-gray-400 text-xs">{{ row.user_type_desc }}</div>
                            </div>
                        </div>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column label="类型" width="80">
                    <template #default="{ row }">
                        <el-tag size="small">{{ row.type_desc }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="内容" min-width="200" show-overflow-tooltip>
                    <template #default="{ row }">
                        <div v-if="row.title" class="font-bold">{{ row.title }}</div>
                        <div class="text-gray-500">{{ row.content }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="图片" width="100">
                    <template #default="{ row }">
                        <div v-if="row.images && row.images.length > 0" class="flex gap-1">
                            <el-image 
                                :src="row.images[0]" 
                                style="width: 40px; height: 40px"
                                fit="cover"
                                :preview-src-list="row.images"
                            />
                            <span v-if="row.images.length > 1" class="text-gray-400">
                                +{{ row.images.length - 1 }}
                            </span>
                        </div>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column label="互动数据" width="150">
                    <template #default="{ row }">
                        <div class="text-xs">
                            <span class="mr-2">浏览: {{ row.view_count }}</span>
                            <span class="mr-2">点赞: {{ row.like_count }}</span>
                        </div>
                        <div class="text-xs">
                            <span class="mr-2">评论: {{ row.comment_count }}</span>
                            <span>收藏: {{ row.collect_count }}</span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getStatusType(row.status)">
                            {{ row.status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="评论状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="row.allow_comment === 1 ? 'success' : 'danger'" size="small">
                            {{ row.allow_comment === 1 ? '允许评论' : '禁止评论' }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="活动报名" width="190">
                    <template #default="{ row }">
                        <div v-if="row.dynamic_type === 4" class="text-xs leading-6">
                            <div>开始：{{ formatActivityTime(row.activity_start_time) }}</div>
                            <div>截止：{{ formatActivityTime(row.activity_signup_deadline) }}</div>
                            <div>
                                名额：{{ row.activity_registered_count || 0 }} /
                                {{ row.activity_total_quota || '不限' }}
                            </div>
                            <el-tag size="small" :type="getActivitySignupStatus(row).type">
                                {{ getActivitySignupStatus(row).text }}
                            </el-tag>
                        </div>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column label="置顶/热门" width="100">
                    <template #default="{ row }">
                        <el-tag v-if="row.is_top" type="warning" size="small">置顶</el-tag>
                        <el-tag v-if="row.is_hot" type="danger" size="small" class="ml-1">热门</el-tag>
                        <span v-if="!row.is_top && !row.is_hot">-</span>
                    </template>
                </el-table-column>
                <el-table-column label="发布时间" prop="create_time" width="170" />
                <el-table-column label="操作" width="220" fixed="right" align="left">
                    <template #default="{ row }">
                        <div class="flex flex-wrap items-center">
                        <el-button 
                            v-if="row.user_type === 3" 
                            type="primary" 
                            link 
                            @click="handleEdit(row)"
                        >编辑</el-button>
                        <el-button type="primary" link @click="handleDetail(row)">详情</el-button>
                        <el-button
                            v-if="row.dynamic_type === 4"
                            type="primary"
                            link
                            @click="handleRegistrations(row)"
                        >报名</el-button>
                        <el-button
                            v-if="row.dynamic_type === 4"
                            type="warning"
                            link
                            @click="handleRefunds(row)"
                        >退款</el-button>
                        <el-button 
                            v-if="row.status === 0" 
                            type="success" 
                            link 
                            @click="handleAudit(row, true)"
                        >通过</el-button>
                        <el-button 
                            v-if="row.status === 0" 
                            type="danger" 
                            link 
                            @click="handleAudit(row, false)"
                        >拒绝</el-button>
                        <el-button 
                            v-if="row.status === 1" 
                            type="warning" 
                            link 
                            @click="handleOffline(row)"
                        >下架</el-button>
                        <el-dropdown v-if="row.status === 1" trigger="click" class="ml-2">
                            <el-button type="primary" link>更多</el-button>
                            <template #dropdown>
                                <el-dropdown-menu>
                                    <el-dropdown-item @click="handleSetTop(row)">
                                        {{ row.is_top ? '取消置顶' : '设为置顶' }}
                                    </el-dropdown-item>
                                    <el-dropdown-item @click="handleSetHot(row)">
                                        {{ row.is_hot ? '取消热门' : '设为热门' }}
                                    </el-dropdown-item>
                                </el-dropdown-menu>
                            </template>
                        </el-dropdown>
                        <el-button type="danger" link @click="handleDelete(row)">删除</el-button>
                        </div>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <!-- 编辑弹窗 -->
        <edit-popup ref="editRef" @success="handleSuccess" />

        <!-- 详情弹窗 -->
        <el-dialog v-model="detailVisible" title="动态详情" width="700px">
            <div v-if="currentDynamic">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="发布者">
                        {{ currentDynamic.publisher?.nickname || '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="发布者类型">{{ currentDynamic.user_type_desc }}</el-descriptions-item>
                    <el-descriptions-item label="动态类型">{{ currentDynamic.type_desc }}</el-descriptions-item>
                    <el-descriptions-item label="状态">
                        <el-tag :type="getStatusType(Number(currentDynamic.status ?? 0))">
                            {{ currentDynamic.status_desc }}
                        </el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="内容" :span="2">{{ currentDynamic.content }}</el-descriptions-item>
                    <el-descriptions-item label="位置" :span="2">{{ currentDynamic.location || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="标签" :span="2">{{ currentDynamic.tags || '-' }}</el-descriptions-item>
                </el-descriptions>

                <div class="mt-4" v-if="currentDynamic.images && currentDynamic.images.length > 0">
                    <h4 class="font-bold mb-2">图片</h4>
                    <div class="flex flex-wrap gap-2">
                        <el-image
                            v-for="(img, idx) in currentDynamic.images"
                            :key="idx"
                            :src="img"
                            style="width: 100px; height: 100px"
                            fit="cover"
                            :preview-src-list="currentDynamic.images"
                            :initial-index="idx"
                        />
                    </div>
                </div>

                <div class="mt-4" v-if="currentDynamic.video_url">
                    <h4 class="font-bold mb-2">视频</h4>
                    <video :src="currentDynamic.video_url" controls style="max-width: 400px" />
                </div>

                <div class="mt-4">
                    <h4 class="font-bold mb-2">互动数据</h4>
                    <el-descriptions :column="5">
                        <el-descriptions-item label="浏览">{{ currentDynamic.view_count }}</el-descriptions-item>
                        <el-descriptions-item label="点赞">{{ currentDynamic.like_count }}</el-descriptions-item>
                        <el-descriptions-item label="评论">{{ currentDynamic.comment_count }}</el-descriptions-item>
                        <el-descriptions-item label="收藏">{{ currentDynamic.collect_count }}</el-descriptions-item>
                        <el-descriptions-item label="分享">{{ currentDynamic.share_count }}</el-descriptions-item>
                    </el-descriptions>
                </div>
            </div>
        </el-dialog>

        <el-dialog
            v-model="registrationVisible"
            width="1180px"
            class="activity-registration-dialog"
            destroy-on-close
        >
            <template #header>
                <div class="activity-registration-dialog__head">
                    <div>
                        <div class="activity-registration-dialog__title">活动报名名单</div>
                        <div class="activity-registration-dialog__subtitle">
                            {{ currentActivityTitle || `活动 #${currentActivityId}` }}
                        </div>
                    </div>
                    <div class="activity-registration-dialog__count">
                        当前 {{ registrationSummary.total }} 条
                    </div>
                </div>
            </template>

            <div class="activity-registration-panel">
                <div class="activity-registration-summary">
                    <div class="activity-registration-summary__item">
                        <span>当前结果</span>
                        <strong>{{ registrationSummary.total }}</strong>
                    </div>
                    <div class="activity-registration-summary__item is-success">
                        <span>已报名</span>
                        <strong>{{ registrationSummary.registered }}</strong>
                    </div>
                    <div class="activity-registration-summary__item is-warning">
                        <span>待支付</span>
                        <strong>{{ registrationSummary.pendingPay }}</strong>
                    </div>
                    <div class="activity-registration-summary__item is-danger">
                        <span>取消审核</span>
                        <strong>{{ registrationSummary.cancelPending }}</strong>
                    </div>
                    <div class="activity-registration-summary__item is-money">
                        <span>已支付金额</span>
                        <strong>{{ registrationSummary.paidAmountLabel }}</strong>
                    </div>
                </div>

                <div class="activity-registration-toolbar">
                    <div class="activity-registration-toolbar__filters">
                        <el-select
                            v-model="registrationFilters.registration_status"
                            placeholder="报名状态"
                            clearable
                            class="activity-registration-toolbar__control"
                            @change="loadRegistrations"
                        >
                            <el-option label="待支付" :value="0" />
                            <el-option label="已报名" :value="1" />
                            <el-option label="取消审核中" :value="2" />
                            <el-option label="已取消" :value="3" />
                            <el-option label="退款处理中" :value="4" />
                            <el-option label="退款失败" :value="5" />
                        </el-select>
                        <el-select
                            v-model="registrationFilters.pay_status"
                            placeholder="支付状态"
                            clearable
                            class="activity-registration-toolbar__control"
                            @change="loadRegistrations"
                        >
                            <el-option label="待支付" :value="0" />
                            <el-option label="已支付" :value="1" />
                            <el-option label="已退款" :value="2" />
                            <el-option label="支付失败" :value="3" />
                        </el-select>
                        <el-select
                            v-model="registrationFilters.cancel_status"
                            placeholder="取消状态"
                            clearable
                            class="activity-registration-toolbar__control"
                            @change="loadRegistrations"
                        >
                            <el-option label="未申请" :value="0" />
                            <el-option label="待审核" :value="1" />
                            <el-option label="已通过" :value="2" />
                            <el-option label="已拒绝" :value="3" />
                        </el-select>
                        <el-input
                            v-model="registrationFilters.keyword"
                            placeholder="联系人/手机号"
                            clearable
                            class="activity-registration-toolbar__search"
                            @keyup.enter="loadRegistrations"
                            @clear="loadRegistrations"
                        />
                    </div>
                    <div class="activity-registration-toolbar__actions">
                        <el-button type="primary" @click="loadRegistrations">筛选</el-button>
                        <el-button @click="resetRegistrationFilters">重置</el-button>
                        <el-button type="success" plain @click="exportRegistrations">导出</el-button>
                    </div>
                </div>

                <el-table
                    v-loading="registrationLoading"
                    :data="registrationList"
                    size="large"
                    stripe
                    border
                    class="activity-registration-table"
                    empty-text="暂无报名记录"
                    max-height="560"
                    :row-class-name="getRegistrationRowClass"
                >
                    <el-table-column label="报名人" min-width="190" fixed="left">
                        <template #default="{ row }">
                            <div class="activity-registration-user">
                                <div class="activity-registration-user__avatar">
                                    {{ getContactInitial(row.contact_name) }}
                                </div>
                                <div class="activity-registration-user__content">
                                    <div class="activity-registration-user__name">
                                        {{ row.contact_name || '-' }}
                                    </div>
                                    <div class="activity-registration-user__meta">
                                        {{ row.contact_mobile || '-' }}
                                    </div>
                                    <div class="activity-registration-user__id">ID：{{ row.id }}</div>
                                </div>
                            </div>
                        </template>
                    </el-table-column>
                    <el-table-column label="票种与金额" min-width="190">
                        <template #default="{ row }">
                            <div class="activity-registration-stack">
                                <span class="activity-registration-ticket">{{ row.ticket_name || '-' }}</span>
                                <span class="activity-registration-price">
                                    {{ row.ticket_price_label || formatRegistrationAmount(row.pay_amount) }}
                                </span>
                            </div>
                        </template>
                    </el-table-column>
                    <el-table-column label="报名 / 支付状态" min-width="230">
                        <template #default="{ row }">
                            <div class="activity-registration-tags">
                                <el-tag :type="getRegistrationStatusType(row.registration_status)" effect="light">
                                    {{ row.registration_status_desc || '-' }}
                                </el-tag>
                                <el-tag :type="getPayStatusType(row.pay_status)" effect="plain">
                                    {{ row.pay_status_desc || '-' }}
                                </el-tag>
                                <el-tag
                                    v-if="Number(row.cancel_status || 0) > 0"
                                    :type="getCancelStatusType(row.cancel_status)"
                                    effect="plain"
                                >
                                    {{ row.cancel_status_desc || '-' }}
                                </el-tag>
                            </div>
                        </template>
                    </el-table-column>
                    <el-table-column label="取消 / 退款" min-width="230">
                        <template #default="{ row }">
                            <div v-if="row.latest_refund || row.cancel_reason" class="activity-registration-refund">
                                <div v-if="row.latest_refund" class="activity-registration-refund__line">
                                    <el-tag
                                        :type="getRefundStatusType(row.latest_refund.refund_status)"
                                        size="small"
                                    >
                                        {{ row.latest_refund.refund_status_desc || '-' }}
                                    </el-tag>
                                    <span>{{ formatRegistrationAmount(row.latest_refund.refund_amount) }}</span>
                                </div>
                                <div class="activity-registration-refund__reason">
                                    {{ row.latest_refund?.refund_reason || row.cancel_reason || '-' }}
                                </div>
                            </div>
                            <span v-else class="activity-registration-muted">无取消申请</span>
                        </template>
                    </el-table-column>
                    <el-table-column label="备注" min-width="170" show-overflow-tooltip>
                        <template #default="{ row }">
                            <span class="activity-registration-remark">{{ row.remark || '-' }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column label="时间" width="190">
                        <template #default="{ row }">
                            <div class="activity-registration-time">
                                <span>报名：{{ formatRegistrationTime(row.create_time) }}</span>
                                <span v-if="row.pay_time">支付：{{ formatRegistrationTime(row.pay_time) }}</span>
                            </div>
                        </template>
                    </el-table-column>
                </el-table>
            </div>
        </el-dialog>

        <el-dialog v-model="refundVisible" title="取消/退款申请" width="980px">
            <div class="mb-3 flex flex-wrap items-center gap-3">
                <el-select
                    v-model="refundFilters.refund_status"
                    placeholder="退款状态"
                    clearable
                    class="w-[160px]"
                    @change="loadRefunds"
                >
                    <el-option label="待审核" :value="0" />
                    <el-option label="审核通过" :value="1" />
                    <el-option label="退款处理中" :value="2" />
                    <el-option label="已退款" :value="3" />
                    <el-option label="已拒绝" :value="4" />
                    <el-option label="退款失败" :value="5" />
                </el-select>
                <el-button type="primary" @click="loadRefunds">筛选</el-button>
                <el-button @click="resetRefundFilters">重置</el-button>
            </div>
            <el-table v-loading="refundLoading" :data="refundList" size="large">
                <el-table-column prop="id" label="ID" width="80" />
                <el-table-column label="报名人" width="180">
                    <template #default="{ row }">
                        {{ row.registration?.contact_name || '-' }}
                        <div class="text-xs text-gray-400">{{ row.registration?.contact_mobile || '' }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="票种" min-width="140">
                    <template #default="{ row }">{{ row.registration?.ticket_name || '-' }}</template>
                </el-table-column>
                <el-table-column prop="refund_amount" label="退款金额" width="110" />
                <el-table-column prop="refund_status_desc" label="状态" width="120" />
                <el-table-column prop="refund_reason" label="原因" min-width="180" show-overflow-tooltip />
                <el-table-column label="操作" width="150" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-if="row.refund_status === 0"
                            type="success"
                            link
                            @click="auditActivityRefund(row, true)"
                        >通过</el-button>
                        <el-button
                            v-if="row.refund_status === 0"
                            type="danger"
                            link
                            @click="auditActivityRefund(row, false)"
                        >拒绝</el-button>
                    </template>
                </el-table-column>
            </el-table>
        </el-dialog>

        <!-- 审核弹窗 -->
        <el-dialog v-model="auditVisible" :title="auditForm.approved ? '审核通过' : '审核拒绝'" width="500px">
            <el-form :model="auditForm" label-width="100px">
                <el-form-item label="审核备注">
                    <el-input 
                        v-model="auditForm.remark" 
                        type="textarea" 
                        :rows="3" 
                        placeholder="请输入审核备注（可选）"
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="auditVisible = false">取消</el-button>
                <el-button :type="auditForm.approved ? 'success' : 'danger'" @click="submitAudit">
                    {{ auditForm.approved ? '确认通过' : '确认拒绝' }}
                </el-button>
            </template>
        </el-dialog>

        <!-- 下架弹窗 -->
        <el-dialog v-model="offlineVisible" title="下架动态" width="500px">
            <el-form :model="offlineForm" label-width="100px">
                <el-form-item label="下架原因">
                    <el-input 
                        v-model="offlineForm.reason" 
                        type="textarea" 
                        :rows="3" 
                        placeholder="请输入下架原因"
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="offlineVisible = false">取消</el-button>
                <el-button type="warning" @click="submitOffline">确认下架</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup name="dynamicLists">
import { 
    dynamicLists, 
    dynamicDetail, 
    dynamicStatistics, 
    dynamicAudit, 
    dynamicOffline,
    dynamicSetTop,
    dynamicSetHot,
    dynamicDelete,
    activityRegistrationLists,
    activityRefundLists,
    activityRefundAudit,
    activityRegistrationExport
} from '@/api/dynamic'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'
import EditPopup from './edit.vue'
import type {
    ActivityRefundFilters,
    ActivityRefundListParams,
    ActivityRefundRow,
    ActivityRegistrationFilters,
    ActivityRegistrationListParams,
    ActivityRegistrationRow,
    ActivitySignupStatus,
    CsvCellValue,
    CsvRow,
    DynamicItem,
    DynamicListParams,
    DynamicStatistics
} from '@/types/dynamic'

const editRef = shallowRef<InstanceType<typeof EditPopup>>()

const queryParams = reactive<DynamicListParams>({
    dynamic_type: '',
    status: '',
    user_type: '',
    is_top: '',
    is_hot: '',
    content: ''
})

const statistics = ref<DynamicStatistics>({})
const detailVisible = ref(false)
const currentDynamic = ref<DynamicItem | null>(null)
const auditVisible = ref(false)
const auditForm = reactive<{
    id: DynamicItem['id']
    approved: number
    remark: string
}>({
    id: 0,
    approved: 1,
    remark: ''
})
const offlineVisible = ref(false)
const offlineForm = reactive<{
    id: DynamicItem['id']
    reason: string
}>({
    id: 0,
    reason: ''
})
const registrationVisible = ref(false)
const registrationLoading = ref(false)
const registrationList = ref<ActivityRegistrationRow[]>([])
const refundVisible = ref(false)
const refundLoading = ref(false)
const refundList = ref<ActivityRefundRow[]>([])
const currentActivityId = ref(0)
const currentActivityTitle = ref('')
const registrationFilters = reactive<ActivityRegistrationFilters>({
    registration_status: '',
    pay_status: '',
    cancel_status: '',
    keyword: ''
})
const refundFilters = reactive<ActivityRefundFilters>({
    refund_status: ''
})

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: dynamicLists,
    params: queryParams
})

const getStatistics = async () => {
    const res = await dynamicStatistics()
    statistics.value = res
}

const getStatusCount = (status: number) => {
    if (!statistics.value.status_counts) return 0
    const item = statistics.value.status_counts.find((item) => Number(item.status) === status)
    return item ? item.count : 0
}

const getStatusType = (status: number) => {
    const types = {
        0: 'warning',
        1: 'success',
        2: 'info',
        3: 'danger'
    } as const
    return types[status as keyof typeof types] ?? 'info'
}

const handleDetail = async (row: DynamicItem) => {
    const res = await dynamicDetail({ id: row.id })
    currentDynamic.value = res
    detailVisible.value = true
}

const handleAudit = (row: DynamicItem, approved: boolean) => {
    auditForm.id = row.id
    auditForm.approved = approved ? 1 : 0
    auditForm.remark = ''
    auditVisible.value = true
}

const submitAudit = async () => {
    await dynamicAudit(auditForm)
    feedback.msgSuccess('审核成功')
    auditVisible.value = false
    getLists()
    getStatistics()
}

const handleOffline = (row: DynamicItem) => {
    offlineForm.id = row.id
    offlineForm.reason = ''
    offlineVisible.value = true
}

const submitOffline = async () => {
    await dynamicOffline(offlineForm)
    feedback.msgSuccess('下架成功')
    offlineVisible.value = false
    getLists()
    getStatistics()
}

const handleSetTop = async (row: DynamicItem) => {
    await dynamicSetTop({ id: row.id, is_top: row.is_top ? 0 : 1 })
    feedback.msgSuccess('设置成功')
    getLists()
}

const handleSetHot = async (row: DynamicItem) => {
    await dynamicSetHot({ id: row.id, is_hot: row.is_hot ? 0 : 1 })
    feedback.msgSuccess('设置成功')
    getLists()
}

const handleDelete = async (row: DynamicItem) => {
    await feedback.confirm('确定要删除该动态吗？')
    await dynamicDelete({ id: row.id })
    feedback.msgSuccess('删除成功')
    getLists()
    getStatistics()
}

const formatActivityTime = (timestamp: number) => {
    if (!timestamp) return '-'
    const date = new Date(Number(timestamp) * 1000)
    const pad = (num: number) => String(num).padStart(2, '0')
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(
        date.getHours()
    )}:${pad(date.getMinutes())}`
}

const getActivitySignupStatus = (row: DynamicItem): ActivitySignupStatus => {
    if (!row.activity_signup_enabled) {
        return { text: '报名关闭', type: 'info' as const }
    }
    const now = Math.floor(Date.now() / 1000)
    if (Number(row.activity_signup_deadline || 0) > 0 && Number(row.activity_signup_deadline) <= now) {
        return { text: '已截止', type: 'warning' as const }
    }
    if (Number(row.activity_start_time || 0) > 0 && Number(row.activity_start_time) <= now) {
        return { text: '已开始', type: 'danger' as const }
    }
    const totalQuota = Number(row.activity_total_quota || 0)
    const registeredCount = Number(row.activity_registered_count || 0)
    if (totalQuota > 0 && registeredCount >= totalQuota) {
        return { text: '名额已满', type: 'danger' as const }
    }
    return { text: '报名开启', type: 'success' as const }
}

const handleRegistrations = async (row: DynamicItem) => {
    currentActivityId.value = Number(row.id || 0)
    currentActivityTitle.value = row.title || row.content || ''
    registrationVisible.value = true
    Object.assign(registrationFilters, {
        registration_status: '',
        pay_status: '',
        cancel_status: '',
        keyword: ''
    })
    await loadRegistrations()
}

const registrationSummary = computed(() => {
    const total = registrationList.value.length
    const registered = registrationList.value.filter(
        (item) => Number(item.registration_status) === 1
    ).length
    const pendingPay = registrationList.value.filter(
        (item) => Number(item.registration_status) === 0
    ).length
    const cancelPending = registrationList.value.filter(
        (item) => Number(item.cancel_status) === 1
    ).length
    const paidAmount = registrationList.value.reduce((sum, item) => {
        if (Number(item.pay_status) !== 1) {
            return sum
        }
        return sum + Number(item.pay_amount || item.ticket_price || 0)
    }, 0)

    return {
        total,
        registered,
        pendingPay,
        cancelPending,
        paidAmountLabel: formatRegistrationAmount(paidAmount)
    }
})

const buildRegistrationParams = (
    extra: Partial<ActivityRegistrationListParams> = {}
): ActivityRegistrationListParams => {
    const params: ActivityRegistrationListParams = {
        dynamic_id: currentActivityId.value,
        page_size: 100,
        ...extra
    }
    Object.entries(registrationFilters).forEach(([key, value]) => {
        if (value !== '') {
            params[key] = value
        }
    })
    return params
}

const loadRegistrations = async () => {
    if (!currentActivityId.value) return
    registrationLoading.value = true
    try {
        const res = await activityRegistrationLists(buildRegistrationParams())
        registrationList.value = res.data || []
    } finally {
        registrationLoading.value = false
    }
}

const resetRegistrationFilters = async () => {
    Object.assign(registrationFilters, {
        registration_status: '',
        pay_status: '',
        cancel_status: '',
        keyword: ''
    })
    await loadRegistrations()
}

const getRegistrationStatusType = (status: number | string) => {
    const types: Record<number, 'success' | 'warning' | 'info' | 'danger' | 'primary'> = {
        0: 'warning',
        1: 'success',
        2: 'warning',
        3: 'info',
        4: 'primary',
        5: 'danger'
    }
    return types[Number(status)] || 'info'
}

const getPayStatusType = (status: number | string) => {
    const types: Record<number, 'success' | 'warning' | 'info' | 'danger'> = {
        0: 'warning',
        1: 'success',
        2: 'info',
        3: 'danger'
    }
    return types[Number(status)] || 'info'
}

const getCancelStatusType = (status: number | string) => {
    const types: Record<number, 'success' | 'warning' | 'info' | 'danger'> = {
        0: 'info',
        1: 'warning',
        2: 'success',
        3: 'danger'
    }
    return types[Number(status)] || 'info'
}

const getRefundStatusType = (status: number | string) => {
    const types: Record<number, 'success' | 'warning' | 'info' | 'danger' | 'primary'> = {
        0: 'warning',
        1: 'primary',
        2: 'warning',
        3: 'success',
        4: 'danger',
        5: 'danger'
    }
    return types[Number(status)] || 'info'
}

const formatRegistrationAmount = (value: number | string) => {
    const amount = Number(value || 0)
    return amount <= 0 ? '免费' : `¥${amount.toFixed(2)}`
}

const formatRegistrationTime = (value: number | string) => {
    if (!value) return '-'
    if (typeof value === 'string' && value.includes('-')) {
        return value
    }
    return formatActivityTime(Number(value))
}

const getContactInitial = (name: string) => {
    const text = String(name || '').trim()
    return text ? text.slice(0, 1).toUpperCase() : '客'
}

const getRegistrationRowClass = ({ row }: { row: ActivityRegistrationRow }) => {
    if (Number(row.cancel_status) === 1) {
        return 'activity-registration-table__row--pending-cancel'
    }
    if (Number(row.registration_status) === 0) {
        return 'activity-registration-table__row--pending-pay'
    }
    return ''
}

const handleRefunds = async (row: DynamicItem) => {
    currentActivityId.value = Number(row.id || 0)
    refundVisible.value = true
    Object.assign(refundFilters, { refund_status: '' })
    await loadRefunds()
}

const buildRefundParams = (): ActivityRefundListParams => {
    const params: ActivityRefundListParams = {
        dynamic_id: currentActivityId.value,
        page_size: 100
    }
    if (refundFilters.refund_status !== '') {
        params.refund_status = refundFilters.refund_status
    }
    return params
}

const loadRefunds = async () => {
    if (!currentActivityId.value) return
    refundLoading.value = true
    try {
        const res = await activityRefundLists(buildRefundParams())
        refundList.value = res.data || []
    } finally {
        refundLoading.value = false
    }
}

const resetRefundFilters = async () => {
    Object.assign(refundFilters, { refund_status: '' })
    await loadRefunds()
}

const auditActivityRefund = async (row: ActivityRefundRow, approved: boolean) => {
    let remark = ''
    if (!approved) {
        const result = await feedback.prompt('请输入拒绝原因', '审核拒绝')
        remark = String(result.value || '')
    }
    await activityRefundAudit({
        id: Number(row.id || 0),
        approved: approved ? 1 : 0,
        remark
    })
    feedback.msgSuccess(approved ? '已通过' : '已拒绝')
    await loadRefunds()
    await getLists()
}

const exportRegistrations = async () => {
    const res = await activityRegistrationExport(buildRegistrationParams({ page_size: 1000 }))
    const rows = Array.isArray(res) ? res : res?.data || []
    if (!rows.length) {
        feedback.msgError('暂无可导出的报名数据')
        return
    }
    downloadCsv(
        rows.map((item) => ({
            ID: item.id,
            活动: item.dynamic?.title || '',
            联系人: item.contact_name || '',
            手机号: item.contact_mobile || '',
            票种: item.ticket_name || '',
            金额: item.ticket_price_label || '',
            报名状态: item.registration_status_desc || '',
            支付状态: item.pay_status_desc || '',
            取消状态: item.cancel_status_desc || '',
            报名时间: item.create_time || ''
        })),
        `activity-registration-${currentActivityId.value}.csv`
    )
}

const downloadCsv = (rows: CsvRow[], filename: string) => {
    const headers = Object.keys(rows[0] || {})
    const escapeCell = (value: CsvCellValue) => `"${String(value ?? '').replace(/"/g, '""')}"`
    const csv = [
        headers.map(escapeCell).join(','),
        ...rows.map((row) => headers.map((key) => escapeCell(row[key])).join(','))
    ].join('\n')
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    link.click()
    URL.revokeObjectURL(url)
}

const handleAdd = () => {
    editRef.value?.open('add')
}

const handleEdit = async (row: DynamicItem) => {
    editRef.value?.open('edit')
    await editRef.value?.getDetail(row)
}

const handleSuccess = () => {
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

<style lang="scss" scoped>
:deep(.activity-registration-dialog) {
    .el-dialog__header {
        padding: 22px 24px 16px;
        margin-right: 0;
        border-bottom: 1px solid var(--el-border-color-lighter);
    }

    .el-dialog__body {
        padding: 18px 24px 24px;
        background: #f7f5f1;
    }
}

.activity-registration-dialog__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.activity-registration-dialog__title {
    font-size: 18px;
    font-weight: 700;
    color: #1f1b16;
    line-height: 1.3;
}

.activity-registration-dialog__subtitle {
    margin-top: 4px;
    max-width: 760px;
    color: #7a7064;
    font-size: 13px;
    line-height: 1.5;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.activity-registration-dialog__count {
    flex-shrink: 0;
    padding: 7px 12px;
    border-radius: 999px;
    background: #1f1b16;
    color: #fffaf0;
    font-size: 13px;
    font-weight: 600;
}

.activity-registration-panel {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.activity-registration-summary {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 10px;
}

.activity-registration-summary__item {
    min-width: 0;
    padding: 14px 16px;
    border: 1px solid rgba(128, 113, 93, 0.14);
    border-radius: 10px;
    background: #fffdf8;
    box-shadow: 0 10px 24px rgba(31, 27, 22, 0.04);

    span {
        display: block;
        color: #8a8176;
        font-size: 12px;
        line-height: 1.2;
    }

    strong {
        display: block;
        margin-top: 8px;
        color: #1f1b16;
        font-size: 22px;
        line-height: 1;
        font-weight: 800;
    }

    &.is-success strong {
        color: #11845b;
    }

    &.is-warning strong {
        color: #b7791f;
    }

    &.is-danger strong {
        color: #c2410c;
    }

    &.is-money strong {
        color: #a17a2f;
    }
}

.activity-registration-toolbar {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 12px;
    border: 1px solid rgba(128, 113, 93, 0.16);
    border-radius: 10px;
    background: #fffdf8;
}

.activity-registration-toolbar__filters,
.activity-registration-toolbar__actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.activity-registration-toolbar__filters {
    flex: 1;
    min-width: 0;
    flex-wrap: wrap;
}

.activity-registration-toolbar__control {
    width: 142px;
}

.activity-registration-toolbar__search {
    width: 210px;
}

.activity-registration-toolbar__actions {
    flex-shrink: 0;
}

:deep(.activity-registration-table) {
    border-radius: 10px;
    overflow: hidden;
    background: #fffdf8;

    .el-table__header th {
        background: #f2ede4;
        color: #5e554b;
        font-size: 12px;
        font-weight: 700;
    }

    .el-table__row td {
        vertical-align: top;
        padding-top: 13px;
        padding-bottom: 13px;
    }

    .activity-registration-table__row--pending-pay td {
        background: #fff9ed;
    }

    .activity-registration-table__row--pending-cancel td {
        background: #fff4ed;
    }
}

.activity-registration-user {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    min-width: 0;
}

.activity-registration-user__avatar {
    flex-shrink: 0;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #211c16;
    color: #f8ead2;
    font-weight: 800;
    font-size: 14px;
}

.activity-registration-user__content,
.activity-registration-stack,
.activity-registration-refund,
.activity-registration-time {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.activity-registration-user__name,
.activity-registration-ticket {
    color: #1f1b16;
    font-weight: 700;
    line-height: 1.35;
}

.activity-registration-user__meta,
.activity-registration-user__id,
.activity-registration-muted,
.activity-registration-remark,
.activity-registration-time,
.activity-registration-refund__reason {
    color: #7a7064;
    font-size: 12px;
    line-height: 1.45;
}

.activity-registration-user__id {
    color: #a49a8c;
}

.activity-registration-price {
    width: fit-content;
    padding: 4px 8px;
    border-radius: 999px;
    background: #f5ead6;
    color: #9b6f24;
    font-size: 12px;
    font-weight: 700;
}

.activity-registration-tags {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
}

.activity-registration-refund__line {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #1f1b16;
    font-size: 12px;
    font-weight: 700;
}

@media (max-width: 1280px) {
    .activity-registration-summary {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .activity-registration-toolbar {
        flex-direction: column;
        align-items: stretch;
    }

    .activity-registration-toolbar__actions {
        justify-content: flex-end;
    }
}
</style>
