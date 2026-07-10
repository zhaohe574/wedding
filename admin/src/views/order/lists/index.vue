<template>
    <admin-page-shell class="order-lists" title="订单管理">
        <template #search>
            <search-panel>
                <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                    <el-form-item class="w-[180px]" label="订单编号">
                        <el-input
                            v-model="queryParams.order_sn"
                            placeholder="输入订单编号"
                            clearable
                            @keyup.enter="resetPage"
                        />
                    </el-form-item>
                    <el-form-item class="w-[150px]" label="联系人">
                        <el-input
                            v-model="queryParams.contact_name"
                            placeholder="输入联系人"
                            clearable
                            @keyup.enter="resetPage"
                        />
                    </el-form-item>
                    <el-form-item class="w-[150px]" label="联系电话">
                        <el-input
                            v-model="queryParams.contact_mobile"
                            placeholder="输入联系电话"
                            clearable
                            @keyup.enter="resetPage"
                        />
                    </el-form-item>
                    <el-form-item class="w-[150px]" label="订单状态">
                        <el-select v-model="queryParams.order_status" placeholder="选择状态" clearable>
                            <el-option label="全部" value="" />
                            <el-option label="待确认" :value="0" />
                            <el-option label="待支付" :value="1" />
                            <el-option label="待服务" :value="2" />
                            <el-option label="服务中" :value="3" />
                            <el-option label="已完成" :value="4" />
                            <el-option label="已评价" :value="5" />
                            <el-option label="已取消" :value="6" />
                            <el-option label="已暂停" :value="7" />
                            <el-option label="退款中" :value="10" />
                            <el-option label="已退款" :value="8" />
                            <el-option label="用户已删除" :value="9" />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[150px]" label="支付模式">
                        <el-select v-model="queryParams.payment_mode" placeholder="选择模式" clearable>
                            <el-option label="全款支付" value="full" />
                            <el-option label="定金支付" value="deposit" />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[150px]" label="定金状态">
                        <el-select v-model="queryParams.deposit_paid" placeholder="选择状态" clearable>
                            <el-option label="未支付" :value="0" />
                            <el-option label="已支付" :value="1" />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[150px]" label="尾款状态">
                        <el-select v-model="queryParams.balance_paid" placeholder="选择状态" clearable>
                            <el-option label="未支付" :value="0" />
                            <el-option label="已支付" :value="1" />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[320px]" label="创建时间">
                        <el-date-picker
                            v-model="createTimeRange"
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
                        <el-button type="success" @click="handleOpenOfflineDrawer">后台建单</el-button>
                    </el-form-item>
                </el-form>
            </search-panel>
        </template>

        <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
            <el-card class="!border-none" shadow="never">
                <div class="text-center"><div class="text-gray-500 text-sm">待确认</div><div class="text-2xl font-bold mt-2 text-yellow-500">{{ getStatusCount(0) }}</div></div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center"><div class="text-gray-500 text-sm">待支付</div><div class="text-2xl font-bold mt-2 text-orange-500">{{ getStatusCount(1) }}</div></div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center"><div class="text-gray-500 text-sm">待服务</div><div class="text-2xl font-bold mt-2 text-blue-500">{{ getStatusCount(2) }}</div></div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center"><div class="text-gray-500 text-sm">服务中</div><div class="text-2xl font-bold mt-2 text-purple-500">{{ getStatusCount(3) }}</div></div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center"><div class="text-gray-500 text-sm">已完成</div><div class="text-2xl font-bold mt-2 text-green-500">{{ getStatusCount(4) }}</div></div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center"><div class="text-gray-500 text-sm">已评价</div><div class="text-2xl font-bold mt-2 text-emerald-500">{{ getStatusCount(5) }}</div></div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center"><div class="text-gray-500 text-sm">已取消</div><div class="text-2xl font-bold mt-2 text-gray-500">{{ getStatusCount(6) }}</div></div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center"><div class="text-gray-500 text-sm">已暂停</div><div class="text-2xl font-bold mt-2 text-amber-500">{{ getStatusCount(7) }}</div></div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center"><div class="text-gray-500 text-sm">退款中</div><div class="text-2xl font-bold mt-2 text-cyan-500">{{ getStatusCount(10) }}</div></div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center"><div class="text-gray-500 text-sm">已退款</div><div class="text-2xl font-bold mt-2 text-red-500">{{ getStatusCount(8) }}</div></div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center"><div class="text-gray-500 text-sm">用户已删除</div><div class="text-2xl font-bold mt-2 text-rose-500">{{ getStatusCount(9) }}</div></div>
            </el-card>
        </div>

        <div class="admin-page-section mt-4">
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="订单编号" prop="order_sn" min-width="180" />
                <el-table-column label="用户信息" min-width="150">
                    <template #default="{ row }">
                        <div class="flex items-center" v-if="row.user">
                            <el-avatar :src="row.user.avatar" :size="32" />
                            <span class="ml-2">{{ row.user.nickname }}</span>
                        </div>
                        <span v-else>{{ isOfflineOrder(row) ? '临时客户' : '-' }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="联系信息" min-width="140">
                    <template #default="{ row }">
                        <div>{{ row.contact_name }}</div>
                        <div class="text-gray-400 text-xs">{{ row.contact_mobile }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="订单金额" width="140">
                    <template #default="{ row }">
                        <div class="text-red-500 font-bold">¥{{ row.need_pay_amount || row.pay_amount }}</div>
                        <div class="text-gray-400 text-xs">{{ row.payment_mode_desc || '全款支付' }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="支付进度" width="160">
                    <template #default="{ row }">
                        <div>已付：¥{{ row.paid_amount || '0.00' }}</div>
                        <div class="text-gray-400 text-xs">待付：¥{{ row.unpaid_amount || '0.00' }}</div>
                        <div class="mt-1 flex gap-1 flex-wrap">
                            <el-tag size="small" :type="row.payment_mode === 'deposit' ? 'warning' : 'info'">
                                {{ row.payment_mode_desc || '全款支付' }}
                            </el-tag>
                            <el-tag v-if="row.payment_mode === 'deposit'" size="small" :type="row.deposit_paid ? 'success' : 'info'">
                                {{ row.deposit_paid ? '定金已付' : '定金未付' }}
                            </el-tag>
                            <el-tag v-if="row.payment_mode === 'deposit'" size="small" :type="row.balance_paid ? 'success' : 'info'">
                                {{ row.balance_paid ? '尾款已付' : '尾款未付' }}
                            </el-tag>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="付款渠道" width="110">
                    <template #default="{ row }">
                        <el-tag :type="Number(row.payment_channel || 1) === 2 ? 'success' : 'primary'" size="small">
                            {{ row.payment_channel_desc || '线上支付' }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="订单状态" width="100">
                    <template #default="{ row }"><el-tag :type="getStatusType(row.order_status)">{{ row.order_status_desc }}</el-tag></template>
                </el-table-column>
                <el-table-column label="剩余确认时间" width="160"><template #default="{ row }"><span>{{ getConfirmRemainText(row) }}</span></template></el-table-column>
                <el-table-column label="超时处理" width="120"><template #default="{ row }"><span>{{ row.confirm_timeout_action_desc || '-' }}</span></template></el-table-column>
                <el-table-column label="剩余支付时间" width="160"><template #default="{ row }"><span>{{ getPayRemainText(row) }}</span></template></el-table-column>
                <el-table-column label="支付超时处理" width="120"><template #default="{ row }"><span>{{ row.pay_timeout_action_desc || '-' }}</span></template></el-table-column>
                <el-table-column label="支付状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getPayStatusType(row.pay_status_display_key)" size="small">
                            {{ row.pay_status_display_desc || row.pay_status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="服务日期" prop="service_date" width="110" />
                <el-table-column label="来源" width="110">
                    <template #default="{ row }">
                        <div class="flex flex-wrap gap-1">
                            <el-tag v-if="Number(row.payment_channel || 1) === 2" size="small" type="success">线下付款</el-tag>
                            <span>{{ row.source_desc }}</span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="创建时间" prop="create_time" width="170" />
                <el-table-column label="操作" width="680" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handleDetail(row)">详情</el-button>
                        <el-button type="primary" link @click="handleQuestionnaireTasks(row)">问卷任务</el-button>
                        <el-button
                            type="warning"
                            link
                            :loading="confirmLetterOpeningId === Number(row.id || 0)"
                            @click="handleConfirmLetter(row)"
                        >
                            档期海报
                        </el-button>
                        <el-button
                            v-if="row.order_status === 0 && row.pending_confirm_count > 0"
                            type="success"
                            link
                            @click="handleConfirm(row)"
                        >
                            确认
                        </el-button>
                        <el-button v-if="canAuditVoucher(row)" type="warning" link @click="handleAuditVoucher(row)">审核凭证</el-button>
                        <el-button v-if="canConfirmOfflinePay(row)" type="success" link @click="handleConfirmOfflinePay(row)">确认线下收款</el-button>
                        <el-button v-if="Number(row.can_direct_reschedule || 0) === 1" type="primary" link @click="handleDirectReschedule(row)">改期</el-button>
                        <el-button v-if="row.order_status === 2" type="warning" link @click="handleStartService(row)">开始服务</el-button>
                        <el-button v-if="row.order_status === 3" type="success" link @click="handleComplete(row)">完成</el-button>
                        <el-button v-if="row.can_admin_refund" type="danger" link @click="handleRefund(row)">退款</el-button>
                        <el-button v-if="row.order_status <= 1" type="danger" link @click="handleCancel(row)">取消</el-button>
                        <el-button v-if="row.order_status === 9" type="danger" link @click="handleDelete(row)">删除</el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4"><pagination v-model="pager" @change="getLists" /></div>
        </div>

        <offline-order-drawer
            v-model="offlineDrawerVisible"
            :add-offline="orderAddOffline"
            :estimate-offline="orderEstimateOffline"
            :offline-main-packages="orderOfflineMainPackages"
            :offline-role-candidates="orderOfflineRoleCandidates"
            :get-addon-config="staffGetAddonConfig"
            :load-staff-options="staffAll"
            @created="handleOfflineCreated"
        />

        <el-dialog v-model="detailVisible" title="订单详情" width="800px">
            <div v-if="currentOrder" class="order-detail">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="订单编号">{{ currentOrder.order_sn }}</el-descriptions-item>
                    <el-descriptions-item label="订单状态"><el-tag :type="getStatusType(currentOrder.order_status)">{{ currentOrder.order_status_desc }}</el-tag></el-descriptions-item>
                    <el-descriptions-item label="剩余确认时间">{{ getConfirmRemainText(currentOrder) }}</el-descriptions-item>
                    <el-descriptions-item label="超时处理">{{ currentOrder.confirm_timeout_action_desc || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="联系人">{{ getDisplayContactName(currentOrder) }}</el-descriptions-item>
                    <el-descriptions-item label="联系电话">{{ getDisplayContactMobile(currentOrder) }}</el-descriptions-item>
                    <el-descriptions-item label="服务日期">{{ getDisplayServiceDate(currentOrder) }}</el-descriptions-item>
                    <el-descriptions-item label="服务地区">{{ currentOrder.service_region_text || currentOrder.service_address || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="服务地址" :span="2">{{ currentOrder.service_address || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="订单总额">¥{{ currentOrder.total_amount }}</el-descriptions-item>
                    <el-descriptions-item v-if="Number(currentOrder.addon_amount || 0) > 0" label="附加服务金额">¥{{ currentOrder.addon_amount }}</el-descriptions-item>
                    <el-descriptions-item label="优惠金额">¥{{ currentOrder.discount_amount }}</el-descriptions-item>
                    <el-descriptions-item label="应付金额">¥{{ currentOrder.pay_amount }}</el-descriptions-item>
                    <el-descriptions-item label="已付金额"><span class="text-red-500 font-bold">¥{{ getDisplayPaidAmount(currentOrder) }}</span></el-descriptions-item>
                    <el-descriptions-item label="支付模式">{{ currentOrder.payment_mode_desc || '全款支付' }}</el-descriptions-item>
                    <el-descriptions-item label="付款渠道">{{ currentOrder.payment_channel_desc || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="当前待支付">{{ getNeedPayStageText(currentOrder) }}</el-descriptions-item>
                    <el-descriptions-item label="剩余支付时间">{{ getPayRemainText(currentOrder) }}</el-descriptions-item>
                    <el-descriptions-item label="支付超时处理">{{ currentOrder.pay_timeout_action_desc || '-' }}</el-descriptions-item>
                    <el-descriptions-item v-if="Number(currentOrder.deposit_amount || 0) > 0" label="定金金额">¥{{ currentOrder.deposit_amount }}</el-descriptions-item>
                    <el-descriptions-item v-if="Number(currentOrder.balance_amount || 0) > 0" label="尾款金额">¥{{ currentOrder.balance_amount }}</el-descriptions-item>
                    <el-descriptions-item v-if="Number(currentOrder.unpaid_amount || 0) >= 0" label="待付金额">¥{{ currentOrder.unpaid_amount }}</el-descriptions-item>
                    <el-descriptions-item v-if="currentOrder.deposit_remark" label="支付说明" :span="2">{{ currentOrder.deposit_remark }}</el-descriptions-item>
                    <el-descriptions-item label="支付方式">{{ currentOrder.pay_type_desc || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="支付状态">
                        {{ currentOrder.pay_status_display_desc || currentOrder.pay_status_desc || '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="线下凭证" :span="2"><el-image v-if="currentOrder.pay_voucher" :src="currentOrder.pay_voucher" fit="contain" style="width: 100%; max-height: 260px" /><span v-else>-</span></el-descriptions-item>
                    <el-descriptions-item label="凭证状态">{{ currentOrder.pay_voucher_status_desc || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="审核备注">{{ currentOrder.pay_voucher_audit_remark || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="用户备注" :span="2">{{ currentOrder.user_remark || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="管理备注" :span="2">{{ currentOrder.admin_remark || '-' }}</el-descriptions-item>
                </el-descriptions>
                <div v-if="currentOrder.can_admin_refund" class="mt-4 flex justify-end">
                    <el-button type="danger" plain @click="handleRefund(currentOrder)">
                        发起退款
                    </el-button>
                </div>
                <div class="service-project-panel mt-4">
                    <div class="service-project-panel__header">
                        <div>
                            <h4 class="service-project-panel__title">服务项目</h4>
                            <div class="service-project-panel__summary">{{ currentServiceSummaryText }}</div>
                        </div>
                    </div>

                    <div v-if="currentPrimaryItem" class="service-project-main">
                        <div class="service-project-main__header">
                            <div class="service-project-main__copy">
                                <div class="service-project-main__label">主套餐</div>
                                <div class="service-project-main__title">{{ currentPrimaryTitle }}</div>
                            </div>
                            <div class="service-project-main__aside">
                                <div class="service-project-main__price">¥{{ formatAmount(currentPrimaryAmount) }}</div>
                                <el-tag
                                    size="small"
                                    :type="getOrderItemStatusType(Number(currentPrimaryItem?.item_status || 0))"
                                >
                                    {{ getOrderItemStatusText(Number(currentPrimaryItem?.item_status || 0)) }}
                                </el-tag>
                            </div>
                        </div>

                        <div class="service-project-main__meta-grid">
                            <div
                                v-for="meta in currentPrimaryMetaList"
                                :key="meta.label"
                                class="service-project-main__meta-card"
                            >
                                <span class="service-project-main__meta-label">{{ meta.label }}</span>
                                <strong class="service-project-main__meta-value">{{ meta.value }}</strong>
                            </div>
                        </div>

                        <div v-if="currentPrimaryDescription" class="service-project-main__desc">
                            {{ currentPrimaryDescription }}
                        </div>

                        <div class="service-project-main__address">
                            <span>服务地址</span>
                            <strong>{{ currentPrimaryAddress }}</strong>
                        </div>
                    </div>
                    <div v-else class="service-project-empty">当前订单暂无主套餐信息</div>

                    <div class="service-project-group">
                        <div class="service-project-group__header">
                            <span class="service-project-group__title">附加套餐</span>
                            <span class="service-project-group__count">{{ currentAddonRows.length }} 项</span>
                        </div>
                        <div v-if="currentAddonRows.length" class="service-project-grid">
                            <div
                                v-for="row in currentAddonRows"
                                :key="row.key"
                                class="service-sub-card"
                            >
                                <div class="service-sub-card__header">
                                    <div class="service-sub-card__title-row">
                                        <span class="service-sub-card__title">{{ row.title }}</span>
                                        <el-tag size="small" :type="row.typeTagType">{{ row.typeText }}</el-tag>
                                    </div>
                                    <span class="service-sub-card__price">{{ row.priceText }}</span>
                                </div>
                                <div v-if="row.metaText" class="service-sub-card__meta">{{ row.metaText }}</div>
                                <div v-if="row.description" class="service-sub-card__desc">{{ row.description }}</div>
                            </div>
                        </div>
                        <div v-else class="service-project-empty service-project-empty--sub">
                            当前订单未配置附加套餐
                        </div>
                    </div>

                    <div v-if="currentRelatedRows.length" class="service-project-group">
                        <div class="service-project-group__header">
                            <span class="service-project-group__title">协作服务</span>
                            <span class="service-project-group__count">{{ currentRelatedRows.length }} 项</span>
                        </div>
                        <div class="service-project-grid">
                            <div
                                v-for="row in currentRelatedRows"
                                :key="row.key"
                                class="service-sub-card service-sub-card--related"
                            >
                                <div class="service-sub-card__header">
                                    <div class="service-sub-card__title-row">
                                        <span class="service-sub-card__title">{{ row.title }}</span>
                                        <el-tag size="small" :type="row.typeTagType">{{ row.typeText }}</el-tag>
                                        <el-tag
                                            v-if="row.statusText"
                                            size="small"
                                            :type="row.statusType || 'info'"
                                        >
                                            {{ row.statusText }}
                                        </el-tag>
                                    </div>
                                    <span class="service-sub-card__price">{{ row.priceText }}</span>
                                </div>
                                <div v-if="row.metaText" class="service-sub-card__meta">{{ row.metaText }}</div>
                                <div v-if="row.description" class="service-sub-card__desc">{{ row.description }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-4" v-if="currentOrder.payments && currentOrder.payments.length > 0">
                    <h4 class="font-bold mb-2">支付记录</h4>
                    <el-table :data="currentOrder.payments" border size="small">
                        <el-table-column label="流水号" prop="payment_sn" min-width="180" />
                        <el-table-column label="支付阶段" min-width="90"><template #default="{ row }">{{ row.pay_type_desc || '-' }}</template></el-table-column>
                        <el-table-column label="支付方式" min-width="100"><template #default="{ row }">{{ row.pay_way_desc || '-' }}</template></el-table-column>
                        <el-table-column label="支付金额" min-width="100"><template #default="{ row }">¥{{ row.pay_amount }}</template></el-table-column>
                        <el-table-column label="支付状态" min-width="100"><template #default="{ row }">{{ row.pay_status_desc || '-' }}</template></el-table-column>
                        <el-table-column label="支付时间" prop="pay_time" min-width="160" />
                    </el-table>
                </div>
                <div class="mt-4" v-if="currentOrder.logs && currentOrder.logs.length > 0">
                    <h4 class="font-bold mb-2">操作日志</h4>
                    <el-timeline>
                        <el-timeline-item v-for="log in currentOrder.logs" :key="log.id" :timestamp="log.create_time" placement="top">
                            <span class="text-gray-500">[{{ log.operator_type_desc }}]</span>
                            {{ log.content }}
                        </el-timeline-item>
                    </el-timeline>
                </div>
            </div>
        </el-dialog>

        <el-dialog v-model="confirmLetterVisible" title="档期确认海报" width="980px" destroy-on-close>
            <div class="confirm-letter-panel">
                <div class="confirm-letter-panel__toolbar">
                    <div>
                        <div class="confirm-letter-panel__title">
                            {{ currentOrder?.order_sn ? `订单：${currentOrder.order_sn}` : '选择订单后生成海报' }}
                        </div>
                        <div class="confirm-letter-panel__desc">
                            后台可代服务人员生成朋友圈档期确认海报，不会推送给客户。
                        </div>
                    </div>
                    <div class="confirm-letter-panel__actions">
                        <el-select
                            v-model="confirmLetterForm.staff_id"
                            class="confirm-letter-panel__select"
                            placeholder="选择服务人员"
                            :disabled="!confirmLetterStaffOptions.length"
                            @change="handleConfirmLetterStaffChange"
                        >
                            <el-option
                                v-for="staff in confirmLetterStaffOptions"
                                :key="staff.staff_id"
                                :label="`${staff.staff_name}｜${staff.service_name || staff.item_type_desc || '服务项'}`"
                                :value="staff.staff_id"
                            />
                        </el-select>
                        <el-select
                            v-model="confirmLetterForm.config_id"
                            class="confirm-letter-panel__select"
                            placeholder="选择海报模板"
                            :disabled="!confirmLetterTemplateOptions.length"
                        >
                            <el-option
                                v-for="config in confirmLetterTemplateOptions"
                                :key="config.config_id"
                                :label="`${config.template_name || '未命名模板'}${Number(config.is_default || 0) === 1 ? '（默认）' : ''}`"
                                :value="config.config_id"
                            />
                        </el-select>
                        <el-button type="primary" :loading="confirmLetterGenerating" :disabled="!canGenerateConfirmLetter" @click="submitGenerateConfirmLetter">
                            生成海报
                        </el-button>
                    </div>
                </div>
                <el-empty v-if="!confirmLetterStaffOptions.length" description="当前订单暂无可生成海报的服务人员" :image-size="80" />
                <div v-else class="confirm-letter-panel__content">
                    <div class="confirm-letter-panel__preview">
                        <div class="confirm-letter-panel__section-title">当前海报</div>
                        <div v-if="confirmLetterCurrent" class="confirm-letter-preview">
                            <div class="confirm-letter-preview__meta">
                                <el-tag type="success">第 {{ confirmLetterCurrent.version }} 版</el-tag>
                                <el-tag type="info">{{ confirmLetterCurrent.config_name || '历史配置' }}</el-tag>
                                <span>{{ confirmLetterCurrent.confirm_date || '-' }}</span>
                            </div>
                            <el-image
                                v-if="confirmLetterCurrent.full_image_url"
                                :src="confirmLetterCurrent.full_image_url"
                                fit="contain"
                                class="confirm-letter-preview__image"
                                :preview-src-list="[confirmLetterCurrent.full_image_url]"
                            />
                            <div v-else class="service-project-empty">已生成记录，但图片暂未落盘，请重新生成图片。</div>
                            <div class="confirm-letter-preview__buttons">
                                <el-button
                                    v-if="confirmLetterCurrent.full_image_url"
                                    type="primary"
                                    link
                                    @click="openConfirmLetterImage(confirmLetterCurrent.full_image_url)"
                                >
                                    打开图片
                                </el-button>
                                <el-button type="primary" link :loading="confirmLetterAssetSaving" @click="regenerateConfirmLetterAssets(confirmLetterCurrent)">
                                    重新生成图片
                                </el-button>
                            </div>
                        </div>
                        <div v-else class="service-project-empty">选择服务人员和模板后，点击生成海报。</div>
                    </div>
                    <div class="confirm-letter-panel__history">
                        <div class="confirm-letter-panel__section-title">历史海报</div>
                        <el-table :data="confirmLetterHistoryRows" size="small" border>
                            <el-table-column label="版本" width="72">
                                <template #default="{ row }">第 {{ row.version }} 版</template>
                            </el-table-column>
                            <el-table-column label="模板" min-width="120">
                                <template #default="{ row }">{{ row.config_name || '历史配置' }}</template>
                            </el-table-column>
                            <el-table-column label="状态" width="80">
                                <template #default="{ row }">
                                    <el-tag size="small" :type="Number(row.is_current || 0) === 1 ? 'success' : 'info'">
                                        {{ Number(row.is_current || 0) === 1 ? '当前' : '历史' }}
                                    </el-tag>
                                </template>
                            </el-table-column>
                            <el-table-column label="操作" width="86">
                                <template #default="{ row }">
                                    <el-button type="primary" link @click="loadConfirmLetterDetail(row)">查看</el-button>
                                </template>
                            </el-table-column>
                        </el-table>
                    </div>
                </div>
            </div>
        </el-dialog>

        <el-dialog v-model="auditVisible" title="线下凭证审核" width="520px">
            <el-form :model="auditForm" label-width="100px">
                <el-form-item label="订单编号"><span>{{ auditForm.order_sn || '-' }}</span></el-form-item>
                <el-form-item label="支付金额"><span>¥{{ auditForm.pay_amount }}</span></el-form-item>
                <el-form-item label="支付凭证"><el-image v-if="auditForm.voucher" :src="auditForm.voucher" fit="contain" style="width: 100%; max-height: 260px" /><span v-else>未上传</span></el-form-item>
                <el-form-item label="审核备注"><el-input v-model="auditForm.remark" type="textarea" :rows="3" placeholder="可填写拒绝原因或备注" /></el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="auditVisible = false">取消</el-button>
                <el-button type="danger" @click="submitAudit(0)">拒绝</el-button>
                <el-button type="primary" @click="submitAudit(1)">通过</el-button>
            </template>
        </el-dialog>

        <el-dialog v-model="confirmPayVisible" title="确认线下收款" width="560px">
            <el-form :model="confirmPayForm" label-width="100px">
                <el-form-item label="订单编号"><span>{{ confirmPayForm.order_sn || '-' }}</span></el-form-item>
                <el-form-item label="支付阶段"><span>{{ confirmPayForm.pay_label || '-' }}</span></el-form-item>
                <el-form-item label="收款金额">
                    <div class="confirm-pay-field">
                        <el-input-number
                            v-model="confirmPayForm.pay_amount"
                            :min="0.01"
                            :max="confirmPayAmountInputMax"
                            :precision="2"
                            class="w-full"
                        />
                        <div class="form-tips">
                            系统建议金额：¥{{ formatAmount(confirmPayForm.suggested_pay_amount) }}；本次收款最高可录入
                            ¥{{ formatAmount(confirmPayForm.pay_amount_max) }}。
                        </div>
                    </div>
                </el-form-item>
                <el-form-item label="收款凭证">
                    <div class="confirm-pay-field">
                        <material-picker v-model="confirmPayForm.voucher" :limit="1" />
                        <div class="form-tips">支持上传转账截图、收据照片等，上传后该凭证将随本次确认记为已通过。</div>
                    </div>
                </el-form-item>
                <el-form-item label="说明">
                    <span class="text-gray-500">支付阶段由系统按订单当前状态自动计算，金额可按实际线下收款修正。</span>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="confirmPayVisible = false">取消</el-button>
                <el-button type="primary" :loading="confirmPaySubmitting" @click="submitConfirmOfflinePay">
                    确认收款
                </el-button>
            </template>
        </el-dialog>

        <el-dialog v-model="cancelVisible" title="取消订单" width="500px">
            <el-form :model="cancelForm" label-width="100px">
                <el-form-item label="取消原因"><el-input v-model="cancelForm.reason" type="textarea" :rows="3" placeholder="请输入取消原因" /></el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="cancelVisible = false">取消</el-button>
                <el-button type="danger" @click="submitCancel">确认取消</el-button>
            </template>
        </el-dialog>

        <el-dialog v-model="refundVisible" title="订单退款" width="560px">
            <el-form :model="refundForm" label-width="110px">
                <el-form-item label="订单编号">
                    <span>{{ refundForm.order_sn || '-' }}</span>
                </el-form-item>
                <el-form-item label="退款模式">
                    <el-radio-group v-model="refundForm.mode">
                        <el-radio-button label="full">全部退款</el-radio-button>
                        <el-radio-button label="partial">部分退款</el-radio-button>
                    </el-radio-group>
                </el-form-item>
                <el-form-item label="最大可退">
                    <span class="font-medium text-red-500">¥{{ formatAmount(refundForm.refundable_amount) }}</span>
                </el-form-item>
                <el-form-item label="退款金额">
                    <el-input-number
                        v-model="refundForm.refund_amount"
                        :min="0.01"
                        :max="refundAmountInputMax"
                        :precision="2"
                        :disabled="refundForm.mode === 'full'"
                        class="w-full"
                    />
                </el-form-item>
                <el-form-item label="退款说明">
                    <el-input
                        v-model="refundForm.reason"
                        type="textarea"
                        :rows="3"
                        maxlength="255"
                        show-word-limit
                        placeholder="请输入退款原因，可选"
                    />
                </el-form-item>
                <el-form-item label="处理提示">
                    <div class="text-sm leading-6 text-gray-500">
                        {{ refundHintText }}
                    </div>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="refundVisible = false">取消</el-button>
                <el-button type="danger" :loading="refundSubmitting" @click="submitRefundApply">
                    确认退款
                </el-button>
            </template>
        </el-dialog>

        <el-dialog v-model="directRescheduleVisible" title="订单改期" width="520px">
            <el-form
                ref="directRescheduleFormRef"
                :model="directRescheduleForm"
                :rules="directRescheduleRules"
                label-width="96px"
            >
                <el-form-item label="订单编号">
                    <span>{{ directRescheduleForm.order_sn || '-' }}</span>
                </el-form-item>
                <el-form-item label="当前日期">
                    <span>{{ directRescheduleForm.current_service_date || '-' }}</span>
                </el-form-item>
                <el-form-item label="新服务日期" prop="service_date">
                    <el-date-picker
                        v-model="directRescheduleForm.service_date"
                        type="date"
                        value-format="YYYY-MM-DD"
                        placeholder="请选择新服务日期"
                        :disabled-date="disableTodayAndPastDate"
                        class="w-full"
                    />
                </el-form-item>
                <el-form-item label="改期原因" prop="reason">
                    <el-input
                        v-model="directRescheduleForm.reason"
                        type="textarea"
                        :rows="3"
                        maxlength="255"
                        show-word-limit
                        placeholder="请输入改期原因"
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="directRescheduleVisible = false">取消</el-button>
                <el-button type="primary" :loading="directRescheduleSubmitting" @click="submitDirectReschedule">
                    确认改期
                </el-button>
            </template>
        </el-dialog>
    </admin-page-shell>
</template>

<script lang="ts" setup name="orderLists">
import { computed, onActivated, onDeactivated, onUnmounted, reactive, ref, watch } from 'vue'
import type { FormInstance, FormRules } from 'element-plus'
import { useRoute, useRouter } from 'vue-router'
import OfflineOrderDrawer from '@/components/order/offline-order-drawer.vue'
import {
    orderAddOffline,
    orderAuditVoucher,
    orderCancel,
    orderComplete,
    orderConfirm,
    orderConfirmLetterAssets,
    orderConfirmLetterDetail,
    orderConfirmLetterGenerate,
    orderConfirmLetterHistory,
    orderConfirmOfflinePay,
    orderDelete,
    orderDetail,
    orderDirectReschedule,
    orderEstimateOffline,
    orderLists,
    orderOfflineMainPackages,
    orderOfflineRoleCandidates,
    refundApply,
    orderStartService,
    orderStatistics
} from '@/api/order'
import { staffAll, staffGetAddonConfig } from '@/api/staff'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

const route = useRoute()
const router = useRouter()
const queryParams = reactive({
    order_sn: '',
    contact_name: '',
    contact_mobile: '',
    order_status: '',
    payment_mode: '',
    deposit_paid: '',
    balance_paid: '',
    start_time: '',
    end_time: ''
})

const createTimeRange = computed<string[]>({
    get: () => {
        if (!queryParams.start_time || !queryParams.end_time) {
            return []
        }
        return [queryParams.start_time, queryParams.end_time]
    },
    set: (value) => {
        queryParams.start_time = value?.[0] || ''
        queryParams.end_time = value?.[1] || ''
    }
})

const statistics = ref<any>({})
const detailVisible = ref(false)
const currentOrder = ref<any>(null)
const confirmLetterVisible = ref(false)
const countdownNowTs = ref(Date.now())
const offlineDrawerVisible = ref(false)
const auditVisible = ref(false)
const auditForm = reactive({
    id: 0,
    order_sn: '',
    pay_amount: 0,
    voucher: '',
    remark: ''
})
const confirmPayVisible = ref(false)
const confirmPayForm = reactive({
    id: 0,
    order_sn: '',
    pay_type: 3,
    pay_amount: 0,
    suggested_pay_amount: 0,
    pay_amount_max: 0,
    pay_label: '全款',
    voucher: ''
})
const confirmPaySubmitting = ref(false)
const confirmLetterOpeningId = ref(0)
const confirmLetterGenerating = ref(false)
const confirmLetterAssetSaving = ref(false)
const confirmLetterCurrent = ref<any>(null)
const confirmLetterHistoryRows = ref<any[]>([])
const confirmLetterForm = reactive({
    staff_id: undefined as number | undefined,
    config_id: undefined as number | undefined
})
const cancelVisible = ref(false)
const cancelForm = reactive({
    id: 0,
    reason: ''
})
const refundVisible = ref(false)
const refundSubmitting = ref(false)
const refundForm = reactive({
    order_id: 0,
    order_sn: '',
    order_status: 0,
    mode: 'full' as 'full' | 'partial',
    refundable_amount: 0,
    refund_amount: 0,
    reason: ''
})
const directRescheduleVisible = ref(false)
const directRescheduleSubmitting = ref(false)
const directRescheduleFormRef = ref<FormInstance>()
const directRescheduleForm = reactive({
    id: 0,
    order_sn: '',
    current_service_date: '',
    service_date: '',
    reason: ''
})
const directRescheduleRules = reactive<FormRules>({
    service_date: [{ required: true, message: '请选择新服务日期', trigger: 'change' }],
    reason: [{ max: 255, message: '改期原因最多255个字符', trigger: 'blur' }]
})
let countdownTimer: ReturnType<typeof setInterval> | null = null
let countdownRefreshing = false

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: orderLists,
    params: queryParams
})

const refundAmountInputMax = computed(() => {
    if (refundForm.mode === 'full') {
        return Number(refundForm.refundable_amount || 0)
    }
    return Number(Math.max(Number(refundForm.refundable_amount || 0) - 0.01, 0.01).toFixed(2))
})
const confirmPayAmountInputMax = computed(() => {
    const maxAmount = Number(confirmPayForm.pay_amount_max || 0)
    return maxAmount > 0 ? maxAmount : Number.MAX_SAFE_INTEGER
})
const confirmLetterStaffOptions = computed<any[]>(() => {
    const candidates = currentOrder.value?.schedule_confirm_letter?.candidates
    return Array.isArray(candidates) ? candidates : []
})
const selectedConfirmLetterStaff = computed<any>(() =>
    confirmLetterStaffOptions.value.find((item: any) => Number(item.staff_id || 0) === Number(confirmLetterForm.staff_id || 0)) || null
)
const confirmLetterTemplateOptions = computed<any[]>(() => {
    const versions = selectedConfirmLetterStaff.value?.versions
    return Array.isArray(versions) ? versions : []
})
const canGenerateConfirmLetter = computed(() =>
    !!currentOrder.value?.id &&
    Number(confirmLetterForm.staff_id || 0) > 0 &&
    Number(confirmLetterForm.config_id || 0) > 0
)
const refundHintText = computed(() => {
    const isFinished = [4, 5, 6, 8, 9].includes(Number(refundForm.order_status || 0))
    if (refundForm.mode === 'partial') {
        return '部分退款仅更新订单与支付信息，不释放服务人员档期。'
    }
    return isFinished
        ? '全部退款将更新订单为已退款，不再释放已结束订单的档期。'
        : '全部退款成功后会释放该订单占用的服务人员档期。'
})

const getStatistics = async () => {
    const res = await orderStatistics()
    statistics.value = res
}

const getStatusCount = (status: number) => {
    if (!statistics.value.status_counts) return 0
    const item = statistics.value.status_counts.find((s: any) => s.status === status)
    return item ? item.count : 0
}

const getStatusType = (status: number): 'warning' | 'primary' | 'info' | 'success' | 'danger' => {
    const types: Record<number, 'warning' | 'primary' | 'info' | 'success' | 'danger'> = {
        0: 'warning',
        1: 'warning',
        2: 'primary',
        3: 'info',
        4: 'success',
        5: 'success',
        6: 'info',
        7: 'warning',
        10: 'info',
        8: 'danger',
        9: 'danger'
    }
    return types[status] || 'info'
}

const getPayStatusType = (
    statusKey: string
): 'warning' | 'primary' | 'info' | 'success' | 'danger' => {
    const types: Record<string, 'warning' | 'primary' | 'info' | 'success' | 'danger'> = {
        unpaid: 'info',
        deposit_paid: 'warning',
        paid: 'success',
        partial_refund: 'warning',
        full_refund: 'danger'
    }
    return types[String(statusKey || '').trim()] || 'info'
}

const isOfflineOrder = (row: any) => Number(row?.source || 0) === 3 && !row?.user
const getDisplayContactName = (order: any) => order?.contact_name || order?.user?.nickname || '-'
const getDisplayContactMobile = (order: any) => order?.contact_mobile || order?.user?.mobile || '-'
const getDisplayPaidAmount = (order: any) => Number(order?.paid_amount ?? 0).toFixed(2)
const getNeedPayStageText = (order: any) => {
    if (!order) return '无需支付'
    const needPay = String(order?.need_pay || 'none')
    if (Number(order?.payment_channel || 1) === 2) {
        if (needPay === 'deposit') return '待上传首笔凭证'
        if (needPay === 'balance') return '待上传尾款凭证'
        if (needPay === 'full') return '待上传线下凭证'
        return '无需支付'
    }
    if (needPay === 'deposit') return '支付定金'
    if (needPay === 'balance') return '支付尾款'
    if (needPay === 'full') return '立即支付'
    return '无需支付'
}
const formatAmount = (value: number | string | undefined) => Number(value || 0).toFixed(2)
const getItemQuantity = (item: any) => Math.max(Number(item?.quantity || 1), 1)
const getItemDisplayAmount = (item: any) => {
    const subtotal = Number(item?.subtotal)
    if (Number.isFinite(subtotal) && subtotal >= 0) {
        return subtotal
    }

    return Math.max(Number(item?.price || 0) * getItemQuantity(item), 0)
}
const getAddonDisplayAmount = (addon: any) => {
    const subtotal = Number(addon?.subtotal)
    if (Number.isFinite(subtotal) && subtotal >= 0) {
        return subtotal
    }

    return Math.max(Number(addon?.price || 0) * Math.max(Number(addon?.quantity || 1), 1), 0)
}
const getItemTypeLabel = (item: any) => {
    const roleLabel = String(item?.item_meta?.role_label || '').trim()
    if (roleLabel) {
        return roleLabel
    }

    const itemType = Number(item?.item_type || 0)
    if (itemType === 1) return '主服务'
    if (itemType === 2) return '附加项'
    if (itemType === 3) return item?.item_type_desc || '协作服务'
    return item?.item_type_desc || '服务项'
}
const getItemTypeTagType = (item: any): 'primary' | 'success' | 'warning' | 'info' => {
    const itemType = Number(item?.item_type || 0)
    if (itemType === 1) return 'primary'
    if (itemType === 2) return 'warning'
    if (itemType === 3) return 'success'
    return 'info'
}
const getOrderItemStatusText = (status: number) => {
    const map: Record<number, string> = {
        0: '待服务',
        1: '服务中',
        2: '已完成',
        3: '已取消'
    }
    return map[status] || '-'
}
const getOrderItemStatusType = (status: number): 'warning' | 'primary' | 'success' | 'info' => {
    const map: Record<number, 'warning' | 'primary' | 'success' | 'info'> = {
        0: 'warning',
        1: 'primary',
        2: 'success',
        3: 'info'
    }
    return map[status] || 'info'
}
const getDisplayServiceDate = (order: any) => {
    if (order?.service_date) return order.service_date
    const dates = (order?.items || []).map((item: any) => item.service_date || item.schedule_date).filter(Boolean)
    return dates.length ? Array.from(new Set(dates)).join('、') : '-'
}

type ServiceDetailRow = {
    key: string
    title: string
    typeText: string
    typeTagType: 'warning' | 'success' | 'primary' | 'info'
    description: string
    metaText: string
    priceText: string
    statusText?: string
    statusType?: 'warning' | 'primary' | 'success' | 'info'
}

const currentOrderItems = computed(() => {
    const items = currentOrder.value?.items
    return Array.isArray(items) ? items : []
})

const currentPrimaryItem = computed(() =>
    currentOrderItems.value.find((item: any) => Number(item?.item_type || 1) === 1) ||
    currentOrderItems.value[0] ||
    null
)

const currentPrimaryTitle = computed(
    () =>
        String(
            currentPrimaryItem.value?.package_name || currentPrimaryItem.value?.package?.name || '待确认主套餐'
        ).trim() || '待确认主套餐'
)

const currentPrimaryStaffName = computed(
    () =>
        String(
            currentPrimaryItem.value?.staff_name || currentPrimaryItem.value?.staff?.name || '待分配服务人员'
        ).trim() || '待分配服务人员'
)

const currentPrimaryAmount = computed(() =>
    currentPrimaryItem.value ? getItemDisplayAmount(currentPrimaryItem.value) : 0
)

const currentPrimaryDescription = computed(
    () =>
        String(
            currentPrimaryItem.value?.package_description || currentPrimaryItem.value?.package?.description || ''
        ).trim()
)

const currentPrimaryAddress = computed(
    () => currentOrder.value?.service_address || currentOrder.value?.service_region_text || '-'
)

const currentPrimaryMetaList = computed(() =>
    [
        { label: '服务人员', value: currentPrimaryStaffName.value },
        {
            label: '服务日期',
            value: currentPrimaryItem.value?.service_date || currentOrder.value?.service_date || '-'
        },
        {
            label: '服务地区',
            value: currentOrder.value?.service_region_text || currentOrder.value?.service_address || '-'
        },
        { label: '数量', value: `x${getItemQuantity(currentPrimaryItem.value)}` }
    ].filter((item) => String(item.value || '').trim() !== '')
)

const getDetailItemDescription = (item: any) => {
    const parts: string[] = []
    const description = String(item?.package_description || item?.package?.description || '').trim()
    if (description) {
        parts.push(description)
    }

    const quantity = getItemQuantity(item)
    if (quantity > 1) {
        parts.push(`数量 x${quantity}`)
    }

    return parts.join(' · ')
}

const buildServiceRowKey = (
    kind: string,
    title: string,
    amount: number,
    quantity: number,
    extra = ''
) => `${kind}:${String(title || '').trim()}:${formatAmount(amount)}:${quantity}:${extra}`

const currentAddonRows = computed<ServiceDetailRow[]>(() => {
    const rows: ServiceDetailRow[] = []
    const seen = new Set<string>()

    const pushRow = (row: ServiceDetailRow, quantity: number, extra = '') => {
        const key = buildServiceRowKey(row.typeText, row.title, Number(row.priceText.replace('¥', '')), quantity, extra)
        if (seen.has(key)) return
        seen.add(key)
        rows.push({ ...row, key })
    }

    currentOrderItems.value.forEach((item: any) => {
        ;(item?.addons || []).forEach((addon: any) => {
            const quantity = Math.max(Number(addon?.quantity || 1), 1)
            const amount = getAddonDisplayAmount(addon)
            pushRow(
                {
                    key: '',
                    title: addon?.addon_name || addon?.name || '附加套餐',
                    typeText: '附加套餐',
                    typeTagType: 'warning',
                    description: '',
                    metaText: `数量 x${quantity}`,
                    priceText: `¥${formatAmount(amount)}`
                },
                quantity
            )
        })
    })

    currentOrderItems.value
        .filter((item: any) => Number(item?.item_type || 1) === 2)
        .forEach((item: any) => {
            const quantity = getItemQuantity(item)
            const amount = getItemDisplayAmount(item)
            pushRow(
                {
                    key: '',
                    title: item?.item_meta?.label || item?.package_name || '附加套餐',
                    typeText: '附加套餐',
                    typeTagType: 'warning',
                    description: getDetailItemDescription(item),
                    metaText: [item?.service_date, `数量 x${quantity}`].filter(Boolean).join(' · '),
                    priceText: `¥${formatAmount(amount)}`
                },
                quantity,
                item?.service_date || ''
            )
        })

    return rows
})

const currentRelatedRows = computed<ServiceDetailRow[]>(() =>
    currentOrderItems.value
        .filter((item: any) => Number(item?.item_type || 1) === 3)
        .map((item: any) => {
            const staffName = String(item?.staff_name || item?.staff?.name || '').trim()
            const roleLabel = String(item?.item_meta?.role_label || '').trim() || '协作服务'
            const amount = getItemDisplayAmount(item)
            return {
                key: buildServiceRowKey('related', `${roleLabel}:${staffName}`, amount, getItemQuantity(item), item?.service_date || ''),
                title: staffName ? `${roleLabel} · ${staffName}` : roleLabel,
                typeText: '协作服务',
                typeTagType: 'success',
                description: getDetailItemDescription(item),
                metaText: [item?.service_date, `数量 x${getItemQuantity(item)}`].filter(Boolean).join(' · '),
                priceText: `¥${formatAmount(amount)}`,
                statusText: getOrderItemStatusText(Number(item?.item_status || 0)),
                statusType: getOrderItemStatusType(Number(item?.item_status || 0))
            }
        })
)

const currentServiceSummaryText = computed(() => {
    const parts = [
        currentPrimaryItem.value ? '1 个主套餐' : '0 个主套餐',
        `${currentAddonRows.value.length} 个附加套餐`
    ]

    if (currentRelatedRows.value.length) {
        parts.push(`${currentRelatedRows.value.length} 个协作服务`)
    }

    return parts.join(' · ')
})

const formatCountdown = (seconds: number | string | undefined) => {
    const total = Math.max(Number(seconds || 0), 0)
    if (total <= 0) return '已超时，等待系统处理'
    const hours = Math.floor(total / 3600)
    const minutes = Math.floor((total % 3600) / 60)
    const remainSeconds = total % 60
    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(remainSeconds).padStart(2, '0')}`
}

const buildExpireAt = (deadlineTime: number | string | undefined, remainSeconds: number | string | undefined) => {
    if (Number(deadlineTime || 0) <= 0) return 0
    return Date.now() + Math.max(Number(remainSeconds || 0), 0) * 1000
}

const syncRowCountdownTargets = (row: any) => {
    if (!row) return
    row.__confirmExpireAt = buildExpireAt(row.confirm_deadline_time, row.confirm_remain_seconds)
    row.__payExpireAt = buildExpireAt(row.pay_deadline_time, row.pay_remain_seconds)
}

const ensureRowCountdownTargets = (row: any) => {
    if (!row) return
    if (Number(row?.confirm_deadline_time || 0) > 0 && typeof row.__confirmExpireAt !== 'number') {
        row.__confirmExpireAt = buildExpireAt(row.confirm_deadline_time, row.confirm_remain_seconds)
    }
    if (Number(row?.pay_deadline_time || 0) > 0 && typeof row.__payExpireAt !== 'number') {
        row.__payExpireAt = buildExpireAt(row.pay_deadline_time, row.pay_remain_seconds)
    }
}

const getLiveRemainSeconds = (
    row: any,
    deadlineField: 'confirm_deadline_time' | 'pay_deadline_time',
    expireField: '__confirmExpireAt' | '__payExpireAt'
) => {
    countdownNowTs.value
    ensureRowCountdownTargets(row)
    if (Number(row?.[deadlineField] || 0) <= 0) return 0
    const expireAt = Number(row?.[expireField] || 0)
    if (expireAt <= 0) return 0
    return Math.max(Math.ceil((expireAt - countdownNowTs.value) / 1000), 0)
}

const hasActiveCountdown = (row: any) =>
    getLiveRemainSeconds(row, 'confirm_deadline_time', '__confirmExpireAt') > 0 ||
    getLiveRemainSeconds(row, 'pay_deadline_time', '__payExpireAt') > 0

const hasExpiredCountdown = (row: any) =>
    (Number(row?.confirm_deadline_time || 0) > 0 && getLiveRemainSeconds(row, 'confirm_deadline_time', '__confirmExpireAt') <= 0) ||
    (Number(row?.pay_deadline_time || 0) > 0 && getLiveRemainSeconds(row, 'pay_deadline_time', '__payExpireAt') <= 0)

const clearCountdownTimer = () => {
    if (countdownTimer) {
        clearInterval(countdownTimer)
        countdownTimer = null
    }
}

const refreshCountdownDrivenData = async () => {
    if (countdownRefreshing) return
    countdownRefreshing = true
    try {
        await Promise.all([getLists(), getStatistics()])
        if (detailVisible.value && Number(currentOrder.value?.id || 0) > 0) {
            await openOrderDetail(Number(currentOrder.value.id))
        }
    } finally {
        countdownRefreshing = false
    }
}

const hasAnyActiveCountdown = () =>
    (pager.lists || []).some((row: any) => hasActiveCountdown(row)) || hasActiveCountdown(currentOrder.value)

const hasAnyExpiredCountdown = () =>
    (pager.lists || []).some((row: any) => hasExpiredCountdown(row)) || hasExpiredCountdown(currentOrder.value)

const startCountdownTimer = () => {
    clearCountdownTimer()
    countdownNowTs.value = Date.now()
    if (hasAnyExpiredCountdown()) {
        refreshCountdownDrivenData()
        return
    }
    if (!hasAnyActiveCountdown()) {
        return
    }
    countdownTimer = setInterval(() => {
        countdownNowTs.value = Date.now()
        if (hasAnyExpiredCountdown()) {
            clearCountdownTimer()
            refreshCountdownDrivenData()
            return
        }
        if (!hasAnyActiveCountdown()) {
            clearCountdownTimer()
        }
    }, 1000)
}

const getConfirmRemainText = (row: any) => {
    if (Number(row?.confirm_deadline_time || 0) <= 0) return '-'
    return formatCountdown(getLiveRemainSeconds(row, 'confirm_deadline_time', '__confirmExpireAt'))
}

const getPayRemainText = (row: any) => {
    if (Number(row?.pay_deadline_time || 0) <= 0) return '-'
    return formatCountdown(getLiveRemainSeconds(row, 'pay_deadline_time', '__payExpireAt'))
}

const canAuditVoucher = (row: any) =>
    Number(row?.order_status || 0) === 1 &&
    Number(row?.payment_channel || 1) === 2 &&
    !!row?.pay_voucher &&
    Number(row?.pay_voucher_status) === 0

const canConfirmOfflinePay = (row: any) =>
    Number(row?.order_status || 0) === 1 &&
    Number(row?.payment_channel || 1) === 2 &&
    !(row?.pay_voucher && Number(row?.pay_voucher_status) === 0)

const handleOpenOfflineDrawer = async () => {
    offlineDrawerVisible.value = true
}

const handleOfflineCreated = () => {
    getLists()
    getStatistics()
}

const clearDetailQuery = () => {
    if (!route.query.detail_id) return
    const nextQuery = { ...route.query }
    delete nextQuery.detail_id
    router.replace({ path: route.path, query: nextQuery })
}

const resetConfirmLetterState = () => {
    confirmLetterForm.staff_id = undefined
    confirmLetterForm.config_id = undefined
    confirmLetterCurrent.value = null
    confirmLetterHistoryRows.value = []
}

const resolveDefaultConfirmLetterConfigId = (staff: any) => {
    const versions = Array.isArray(staff?.versions) ? staff.versions : []
    const defaultConfig = versions.find((item: any) => Number(item.is_default || 0) === 1)
    return Number(defaultConfig?.config_id || versions[0]?.config_id || 0)
}

const loadConfirmLetterHistory = async () => {
    const orderId = Number(currentOrder.value?.id || 0)
    const staffId = Number(confirmLetterForm.staff_id || 0)
    if (!orderId || !staffId) {
        confirmLetterHistoryRows.value = []
        confirmLetterCurrent.value = null
        return
    }
    const rows = await orderConfirmLetterHistory({ id: orderId, staff_id: staffId })
    confirmLetterHistoryRows.value = Array.isArray(rows) ? rows : []
    const current = confirmLetterHistoryRows.value.find((row: any) => Number(row.is_current || 0) === 1) || confirmLetterHistoryRows.value[0] || null
    if (current?.letter_id) {
        await loadConfirmLetterDetail(current)
    } else {
        confirmLetterCurrent.value = null
    }
}

const initConfirmLetterState = async () => {
    resetConfirmLetterState()
    const candidates = confirmLetterStaffOptions.value
    if (!candidates.length) return
    const defaultStaffId = Number(currentOrder.value?.schedule_confirm_letter?.default_staff_id || candidates[0]?.staff_id || 0)
    const staff = candidates.find((item: any) => Number(item.staff_id || 0) === defaultStaffId) || candidates[0]
    confirmLetterForm.staff_id = Number(staff?.staff_id || 0) || undefined
    confirmLetterForm.config_id = resolveDefaultConfirmLetterConfigId(staff) || undefined
    await loadConfirmLetterHistory()
}

const openOrderDetail = async (id: number, clearQuery = false) => {
    if (!id) return
    const res = await orderDetail({ id })
    currentOrder.value = res
    detailVisible.value = true
    if (clearQuery) clearDetailQuery()
}

const handleDetail = async (row: any) => {
    await openOrderDetail(Number(row.id))
}

const handleConfirmLetter = async (row: any) => {
    const orderId = Number(row?.id || 0)
    if (!orderId) return
    confirmLetterOpeningId.value = orderId
    try {
        const res = await orderDetail({ id: orderId })
        currentOrder.value = res
        confirmLetterVisible.value = true
        await initConfirmLetterState()
    } finally {
        confirmLetterOpeningId.value = 0
    }
}

const handleConfirmLetterStaffChange = async () => {
    confirmLetterCurrent.value = null
    confirmLetterHistoryRows.value = []
    confirmLetterForm.config_id = resolveDefaultConfirmLetterConfigId(selectedConfirmLetterStaff.value) || undefined
    await loadConfirmLetterHistory()
}

const submitGenerateConfirmLetter = async () => {
    if (!canGenerateConfirmLetter.value) {
        feedback.msgError('请选择服务人员和海报模板')
        return
    }
    confirmLetterGenerating.value = true
    try {
        const data = await orderConfirmLetterGenerate({
            id: Number(currentOrder.value?.id || 0),
            staff_id: Number(confirmLetterForm.staff_id || 0),
            config_id: Number(confirmLetterForm.config_id || 0)
        })
        confirmLetterCurrent.value = data
        await loadConfirmLetterHistory()
        feedback.msgSuccess('档期确认海报已生成')
    } finally {
        confirmLetterGenerating.value = false
    }
}

const loadConfirmLetterDetail = async (row: any) => {
    const letterId = Number(row?.letter_id || 0)
    const staffId = Number(row?.staff_id || confirmLetterForm.staff_id || 0)
    if (!letterId || !staffId) return
    confirmLetterCurrent.value = await orderConfirmLetterDetail({
        letter_id: letterId,
        staff_id: staffId
    })
}

const regenerateConfirmLetterAssets = async (row: any) => {
    const letterId = Number(row?.letter_id || 0)
    const staffId = Number(row?.staff_id || confirmLetterForm.staff_id || 0)
    if (!letterId || !staffId) return
    confirmLetterAssetSaving.value = true
    try {
        await orderConfirmLetterAssets({
            letter_id: letterId,
            staff_id: staffId,
            snapshot_hash: String(row?.snapshot_hash || '')
        })
        await loadConfirmLetterDetail(row)
        await loadConfirmLetterHistory()
        feedback.msgSuccess('海报图片已重新生成')
    } finally {
        confirmLetterAssetSaving.value = false
    }
}

const openConfirmLetterImage = (url: string) => {
    if (!url) return
    window.open(url, '_blank')
}

const handleQuestionnaireTasks = (row: any) => {
    router.push({
        path: '/couple-questionnaire/tasks',
        query: {
            keyword: String(row.id || row.order_sn || '')
        }
    })
}

const refreshCurrentOrderDetail = async (orderId: number) => {
    if (!detailVisible.value || Number(currentOrder.value?.id || 0) !== Number(orderId || 0)) {
        return
    }
    currentOrder.value = await orderDetail({ id: orderId })
}

const handleConfirm = async (row: any) => {
    await feedback.confirm('确认后将处理当前账号可确认的待确认服务项，是否继续？')
    await orderConfirm({ id: row.id })
    feedback.msgSuccess('确认成功')
    await Promise.all([getLists(), getStatistics(), refreshCurrentOrderDetail(Number(row.id || 0))])
}

const handleAuditVoucher = (row: any) => {
    auditForm.id = row.id
    auditForm.order_sn = row.order_sn || ''
    auditForm.pay_amount = Number(row.need_pay_amount || row.pay_amount || 0)
    auditForm.voucher = row.pay_voucher || ''
    auditForm.remark = ''
    auditVisible.value = true
}

const handleConfirmOfflinePay = (row: any) => {
    const suggestedAmount = Number(row.need_pay_amount || row.pay_amount || 0)
    const unpaidAmount = Number(row.unpaid_amount || 0)
    const remainAmount = Number(row.pay_amount || 0) - Number(row.paid_amount || 0)
    const maxAmount = Number(Math.max(unpaidAmount, remainAmount, suggestedAmount, 0).toFixed(2))
    confirmPayForm.id = Number(row.id || 0)
    confirmPayForm.order_sn = row.order_sn || ''
    confirmPayForm.pay_type = row.need_pay === 'deposit' ? 1 : row.need_pay === 'balance' ? 2 : 3
    confirmPayForm.pay_amount = suggestedAmount
    confirmPayForm.suggested_pay_amount = suggestedAmount
    confirmPayForm.pay_amount_max = maxAmount
    confirmPayForm.pay_label = row.need_pay === 'deposit' ? '定金' : row.need_pay === 'balance' ? '尾款' : '全款'
    confirmPayForm.voucher = ''
    confirmPayVisible.value = true
}

const submitAudit = async (approved: number) => {
    await orderAuditVoucher({ id: auditForm.id, approved, remark: auditForm.remark })
    feedback.msgSuccess('操作成功')
    auditVisible.value = false
    getLists()
    getStatistics()
}

const submitConfirmOfflinePay = async () => {
    const payAmount = Number(confirmPayForm.pay_amount || 0)
    const maxAmount = Number(confirmPayForm.pay_amount_max || 0)
    if (payAmount <= 0) {
        feedback.msgError('收款金额必须大于0')
        return
    }
    if (maxAmount > 0 && payAmount > maxAmount) {
        feedback.msgError('收款金额不能超过当前剩余待收金额')
        return
    }

    confirmPaySubmitting.value = true
    try {
        await orderConfirmOfflinePay({
            id: confirmPayForm.id,
            pay_type: confirmPayForm.pay_type,
            pay_amount: payAmount,
            voucher: confirmPayForm.voucher
        })
        feedback.msgSuccess('线下收款已确认')
        confirmPayVisible.value = false
        await Promise.all([
            getLists(),
            getStatistics(),
            refreshCurrentOrderDetail(Number(confirmPayForm.id || 0))
        ])
    } finally {
        confirmPaySubmitting.value = false
    }
}

const handleStartService = async (row: any) => {
    await feedback.confirm('确定要开始服务吗？')
    await orderStartService({ id: row.id })
    feedback.msgSuccess('操作成功')
    getLists()
    getStatistics()
}

const handleComplete = async (row: any) => {
    await feedback.confirm('确定要完成订单吗？')
    await orderComplete({ id: row.id })
    feedback.msgSuccess('操作成功')
    getLists()
    getStatistics()
}

const disableTodayAndPastDate = (date: Date) => {
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    return date.getTime() <= today.getTime()
}

const handleDirectReschedule = (row: any) => {
    directRescheduleForm.id = Number(row.id || 0)
    directRescheduleForm.order_sn = row.order_sn || ''
    directRescheduleForm.current_service_date = row.service_date || ''
    directRescheduleForm.service_date = ''
    directRescheduleForm.reason = ''
    directRescheduleVisible.value = true
    directRescheduleFormRef.value?.clearValidate()
}

const submitDirectReschedule = async () => {
    await directRescheduleFormRef.value?.validate()
    if (directRescheduleForm.service_date === directRescheduleForm.current_service_date) {
        feedback.msgError('新服务日期不能与当前服务日期相同')
        return
    }

    directRescheduleSubmitting.value = true
    try {
        await orderDirectReschedule({
            id: directRescheduleForm.id,
            service_date: directRescheduleForm.service_date,
            reason: directRescheduleForm.reason
        })
        feedback.msgSuccess('改期成功')
        directRescheduleVisible.value = false
        await Promise.all([
            getLists(),
            getStatistics(),
            refreshCurrentOrderDetail(Number(directRescheduleForm.id || 0))
        ])
    } finally {
        directRescheduleSubmitting.value = false
    }
}

const handleRefund = (row: any) => {
    const refundableAmount = Number(row.refundable_amount || 0)
    refundForm.order_id = Number(row.id || row.order_id || 0)
    refundForm.order_sn = row.order_sn || ''
    refundForm.order_status = Number(row.order_status || 0)
    refundForm.mode = 'full'
    refundForm.refundable_amount = refundableAmount
    refundForm.refund_amount = refundableAmount
    refundForm.reason = ''
    refundVisible.value = true
}

const submitRefundApply = async () => {
    const maxAmount = Number(refundForm.refundable_amount || 0)
    const refundAmount = Number(
        (refundForm.mode === 'full' ? refundForm.refundable_amount : refundForm.refund_amount) || 0
    )

    if (maxAmount <= 0) {
        feedback.msgError('当前订单暂无可退金额')
        return
    }
    if (refundAmount <= 0) {
        feedback.msgError('退款金额必须大于0')
        return
    }
    if (refundAmount > maxAmount) {
        feedback.msgError('退款金额不能超过最大可退金额')
        return
    }
    if (refundForm.mode === 'partial' && refundAmount >= maxAmount) {
        feedback.msgError('部分退款金额必须小于最大可退金额')
        return
    }

    refundSubmitting.value = true
    try {
        await refundApply({
            order_id: refundForm.order_id,
            refund_amount: refundAmount,
            reason: refundForm.reason.trim()
        })
        feedback.msgSuccess('退款申请成功')
        refundVisible.value = false
        await Promise.all([
            getLists(),
            getStatistics(),
            refreshCurrentOrderDetail(Number(refundForm.order_id || 0))
        ])
    } finally {
        refundSubmitting.value = false
    }
}

const handleCancel = (row: any) => {
    cancelForm.id = row.id
    cancelForm.reason = ''
    cancelVisible.value = true
}

const submitCancel = async () => {
    await orderCancel(cancelForm)
    feedback.msgSuccess('订单已取消')
    cancelVisible.value = false
    getLists()
    getStatistics()
}

const handleDelete = async (row: any) => {
    await feedback.confirm('确认彻底删除该订单吗？此操作仅用于用户已删除订单。')
    await orderDelete({ id: row.id })
    feedback.msgSuccess('订单已删除')
    getLists()
    getStatistics()
}

onActivated(() => {
    getLists()
    getStatistics()
    startCountdownTimer()
})

onDeactivated(() => {
    clearCountdownTimer()
})

onUnmounted(() => {
    clearCountdownTimer()
})

watch(() => pager.lists, (lists) => {
    ;(lists || []).forEach((row: any) => syncRowCountdownTargets(row))
    startCountdownTimer()
}, { deep: false })

watch(currentOrder, (value) => {
    syncRowCountdownTargets(value)
    startCountdownTimer()
}, { deep: false })

watch(() => refundForm.mode, (mode) => {
    if (mode === 'full') {
        refundForm.refund_amount = Number(refundForm.refundable_amount || 0)
        return
    }

    if (Number(refundForm.refund_amount || 0) >= Number(refundForm.refundable_amount || 0)) {
        refundForm.refund_amount = Number(
            Math.max(Number(refundForm.refundable_amount || 0) - 0.01, 0.01).toFixed(2)
        )
    }
})

watch(() => route.query.detail_id, async (detailId) => {
    const id = Number(detailId || 0)
    if (!id) return
    await openOrderDetail(id, true)
}, { immediate: true })

getLists()
getStatistics()
</script>

<style scoped>
.order-detail :deep(.el-descriptions__label) {
    width: 100px;
}

.service-project-panel {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.confirm-pay-field {
    width: 100%;
}

.service-project-panel__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.service-project-panel__title {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
}

.service-project-panel__summary {
    margin-top: 4px;
    font-size: 12px;
    color: #6b7280;
}

.service-project-main {
    border: 1px solid #f2dce6;
    border-radius: 18px;
    padding: 18px;
    background: linear-gradient(180deg, #fff8fb 0%, #ffffff 100%);
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.service-project-main__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
}

.service-project-main__copy {
    min-width: 0;
    flex: 1;
}

.service-project-main__label {
    display: inline-flex;
    align-items: center;
    min-height: 26px;
    padding: 0 10px;
    border-radius: 999px;
    background: #ffe9f2;
    color: #be185d;
    font-size: 12px;
    font-weight: 600;
}

.service-project-main__title {
    margin-top: 10px;
    font-size: 20px;
    line-height: 1.5;
    font-weight: 700;
    color: #111827;
}

.service-project-main__aside {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 8px;
}

.service-project-main__price {
    font-size: 24px;
    line-height: 1.2;
    font-weight: 700;
    color: #be185d;
}

.service-project-main__meta-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 12px;
}

.service-project-main__meta-card {
    padding: 12px 14px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid #f5e5eb;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.service-project-main__meta-label {
    font-size: 12px;
    color: #9ca3af;
}

.service-project-main__meta-value {
    font-size: 14px;
    line-height: 1.6;
    color: #1f2937;
    word-break: break-word;
}

.service-project-main__desc {
    padding: 14px 16px;
    border-radius: 14px;
    background: #ffffff;
    border: 1px dashed #f2dce6;
    font-size: 13px;
    line-height: 1.8;
    color: #6b7280;
    white-space: pre-wrap;
}

.service-project-main__address {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    font-size: 13px;
    line-height: 1.8;
    color: #6b7280;
}

.service-project-main__address strong {
    color: #374151;
    font-weight: 600;
}

.service-project-group {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.service-project-group__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.service-project-group__title {
    font-size: 14px;
    font-weight: 700;
    color: #1f2937;
}

.service-project-group__count {
    font-size: 12px;
    color: #9ca3af;
}

.service-project-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 12px;
}

.service-sub-card {
    border: 1px solid #eee7e3;
    border-radius: 16px;
    padding: 16px;
    background: #fff;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.service-sub-card--related {
    border-color: #d7f0e2;
    background: linear-gradient(180deg, #f6fffa 0%, #ffffff 100%);
}

.service-sub-card__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
}

.service-sub-card__title-row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    min-width: 0;
    flex: 1;
}

.service-sub-card__title {
    font-size: 15px;
    line-height: 1.5;
    font-weight: 600;
    color: #111827;
    word-break: break-word;
}

.service-sub-card__price {
    flex-shrink: 0;
    font-size: 18px;
    line-height: 1.4;
    font-weight: 700;
    color: #be185d;
}

.service-sub-card__meta {
    font-size: 12px;
    line-height: 1.7;
    color: #6b7280;
}

.service-sub-card__desc {
    font-size: 13px;
    line-height: 1.8;
    color: #4b5563;
    white-space: pre-wrap;
}

.service-project-empty {
    border-radius: 14px;
    padding: 18px 20px;
    background: #f9fafb;
    border: 1px dashed #e5e7eb;
    font-size: 13px;
    color: #9ca3af;
}

.service-project-empty--sub {
    padding: 14px 16px;
}

.confirm-letter-panel {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.confirm-letter-panel__toolbar {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
}

.confirm-letter-panel__title {
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
}

.confirm-letter-panel__desc {
    margin-top: 6px;
    font-size: 12px;
    line-height: 1.7;
    color: #6b7280;
}

.confirm-letter-panel__actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    flex-shrink: 0;
}

.confirm-letter-panel__select {
    width: 210px;
}

.confirm-letter-panel__content {
    display: grid;
    grid-template-columns: minmax(0, 1.1fr) minmax(320px, 0.9fr);
    gap: 16px;
}

.confirm-letter-panel__preview,
.confirm-letter-panel__history {
    border: 1px solid #ebe5df;
    border-radius: 16px;
    padding: 16px;
    background: #fff;
}

.confirm-letter-panel__section-title {
    margin-bottom: 12px;
    font-size: 14px;
    font-weight: 700;
    color: #1f2937;
}

.confirm-letter-preview {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.confirm-letter-preview__meta {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.confirm-letter-preview__buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.confirm-letter-preview__image {
    width: 100%;
    min-height: 520px;
    border-radius: 12px;
    border: 1px solid #f0e7e2;
    background: #faf8f6;
}

@media (max-width: 1280px) {
    .confirm-letter-panel__toolbar {
        flex-direction: column;
    }

    .confirm-letter-panel__actions {
        width: 100%;
    }

    .confirm-letter-panel__content {
        grid-template-columns: minmax(0, 1fr);
    }
}

</style>
