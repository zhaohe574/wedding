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
                            <el-option label="已支付" :value="2" />
                            <el-option label="服务中" :value="3" />
                            <el-option label="已完成" :value="4" />
                            <el-option label="已评价" :value="5" />
                            <el-option label="已取消" :value="6" />
                            <el-option label="已暂停" :value="7" />
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
                        <el-button type="success" @click="handleAdd">新增订单</el-button>
                    </el-form-item>
                </el-form>
            </search-panel>
        </template>

        <!-- 统计卡片 -->
        <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">待确认</div>
                    <div class="text-2xl font-bold mt-2 text-yellow-500">{{ getStatusCount(0) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">待支付</div>
                    <div class="text-2xl font-bold mt-2 text-orange-500">{{ getStatusCount(1) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已支付</div>
                    <div class="text-2xl font-bold mt-2 text-blue-500">{{ getStatusCount(2) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">服务中</div>
                    <div class="text-2xl font-bold mt-2 text-purple-500">{{ getStatusCount(3) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已完成</div>
                    <div class="text-2xl font-bold mt-2 text-green-500">{{ getStatusCount(4) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已评价</div>
                    <div class="text-2xl font-bold mt-2 text-emerald-500">{{ getStatusCount(5) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已取消</div>
                    <div class="text-2xl font-bold mt-2 text-gray-500">{{ getStatusCount(6) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已暂停</div>
                    <div class="text-2xl font-bold mt-2 text-amber-500">{{ getStatusCount(7) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已退款</div>
                    <div class="text-2xl font-bold mt-2 text-red-500">{{ getStatusCount(8) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">用户已删除</div>
                    <div class="text-2xl font-bold mt-2 text-rose-500">{{ getStatusCount(9) }}</div>
                </div>
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
                        <span v-else>-</span>
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
                <el-table-column label="订单状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getStatusType(row.order_status)">
                            {{ row.order_status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="支付状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="row.pay_status === 1 ? 'success' : 'info'" size="small">
                            {{ row.pay_status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="服务日期" prop="service_date" width="110" />
                <el-table-column label="来源" width="80">
                    <template #default="{ row }">
                        <span>{{ row.source_desc }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="创建时间" prop="create_time" width="170" />
                <el-table-column label="操作" width="240" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handleDetail(row)">详情</el-button>
                        <el-button 
                            v-if="row.order_status === 1 && row.pay_type === 4 && row.pay_voucher && row.pay_voucher_status === 0" 
                            type="warning" 
                            link 
                            @click="handleAuditVoucher(row)"
                        >审核凭证</el-button>
                        <el-button 
                            v-if="row.order_status === 2" 
                            type="warning" 
                            link 
                            @click="handleStartService(row)"
                        >开始服务</el-button>
                        <el-button 
                            v-if="row.order_status === 3" 
                            type="success" 
                            link 
                            @click="handleComplete(row)"
                        >完成</el-button>
                        <el-button 
                            v-if="row.order_status <= 1" 
                            type="danger" 
                            link 
                            @click="handleCancel(row)"
                        >取消</el-button>
                        <el-button
                            v-if="row.order_status === 9"
                            type="danger"
                            link
                            @click="handleDelete(row)"
                        >删除</el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </div>

        <!-- 新增订单弹窗 -->
        <el-dialog v-model="addVisible" title="新增订单" width="860px">
            <el-form :model="addForm" label-width="110px">
                <el-form-item label="用户ID">
                    <el-select
                        v-model="addForm.user_id"
                        filterable
                        remote
                        reserve-keyword
                        :remote-method="remoteUserSearch"
                        :loading="userLoading"
                        placeholder="输入昵称/账号/手机号搜索用户"
                        class="w-full"
                    >
                        <el-option
                            v-for="item in userOptions"
                            :key="item.id"
                            :label="item.label"
                            :value="item.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="联系人">
                    <el-input v-model="addForm.contact_name" placeholder="请输入联系人" />
                </el-form-item>
                <el-form-item label="联系电话">
                    <el-input v-model="addForm.contact_mobile" placeholder="请输入联系电话" />
                </el-form-item>
                <el-form-item label="服务日期">
                    <el-date-picker v-model="addForm.service_date" type="date" value-format="YYYY-MM-DD" placeholder="选择服务日期" />
                </el-form-item>
                <el-form-item label="婚礼日期">
                    <el-date-picker v-model="addForm.wedding_date" type="date" value-format="YYYY-MM-DD" placeholder="选择婚礼日期" />
                </el-form-item>
                <el-form-item label="婚礼地点">
                    <el-input v-model="addForm.wedding_venue" placeholder="请输入婚礼地点" />
                </el-form-item>
                <el-form-item label="服务地址">
                    <el-input v-model="addForm.service_address" placeholder="请输入服务地址" />
                </el-form-item>
                <el-form-item label="优惠金额">
                    <el-input-number v-model="addForm.discount_amount" :min="0" :precision="2" />
                </el-form-item>
                <el-form-item label="订单项">
                    <div class="w-full flex flex-col gap-3">
                        <div v-for="(item, index) in addForm.items" :key="index" class="grid grid-cols-5 gap-3">
                            <el-select
                                v-model="item.staff_id"
                                filterable
                                placeholder="选择服务人员"
                                @change="handleStaffChange(index)"
                            >
                                <el-option
                                    v-for="staff in staffOptions"
                                    :key="staff.id"
                                    :label="staff.name"
                                    :value="staff.id"
                                />
                            </el-select>
                            <el-input v-model="item.staff_name" placeholder="服务人员名称" disabled />
                            <el-select
                                v-model="item.package_id"
                                filterable
                                placeholder="选择套餐"
                                @change="handlePackageChange(index)"
                            >
                                <el-option
                                    v-for="pkg in packageOptionsMap[index] || []"
                                    :key="pkg.id"
                                    :label="pkg.name"
                                    :value="pkg.id"
                                />
                            </el-select>
                            <el-input v-model="item.package_name" placeholder="套餐名称" disabled />
                            <el-input-number v-model="item.price" :min="0" :precision="2" placeholder="价格" />
                        </div>
                        <div class="grid grid-cols-4 gap-3">
                            <el-input v-model.number="addForm.items[0].quantity" placeholder="数量" />
                            <div class="col-span-3">
                                <el-button @click="addOrderItem">添加订单项</el-button>
                            </div>
                        </div>
                    </div>
                </el-form-item>
                <el-form-item label="支付预估">
                    <div class="w-full rounded border border-[var(--el-border-color)] p-4 bg-[var(--el-fill-color-lighter)] text-sm leading-7">
                        <div>支付模式：{{ addEstimate.payment_mode_desc || '全款支付' }}</div>
                        <div>订单总额：¥{{ Number(addEstimate.total_amount || 0).toFixed(2) }}</div>
                        <div>优惠金额：¥{{ Number(addEstimate.discount_amount || 0).toFixed(2) }}</div>
                        <div>应付金额：¥{{ Number(addEstimate.pay_amount || 0).toFixed(2) }}</div>
                        <div>定金金额：¥{{ Number(addEstimate.deposit_amount || 0).toFixed(2) }}</div>
                        <div>尾款金额：¥{{ Number(addEstimate.balance_amount || 0).toFixed(2) }}</div>
                        <div v-if="addEstimate.deposit_remark">支付说明：{{ addEstimate.deposit_remark }}</div>
                    </div>
                </el-form-item>
                <el-form-item label="管理备注">
                    <el-input v-model="addForm.admin_remark" type="textarea" :rows="3" placeholder="请输入备注" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="addVisible = false">取消</el-button>
                <el-button type="primary" @click="submitAdd">创建订单</el-button>
            </template>
        </el-dialog>

        <!-- 订单详情弹窗 -->
        <el-dialog v-model="detailVisible" title="订单详情" width="800px">
            <div v-if="currentOrder" class="order-detail">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="订单编号">{{ currentOrder.order_sn }}</el-descriptions-item>
                    <el-descriptions-item label="订单状态">
                        <el-tag :type="getStatusType(currentOrder.order_status)">
                            {{ currentOrder.order_status_desc }}
                        </el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="联系人">{{ getDisplayContactName(currentOrder) }}</el-descriptions-item>
                    <el-descriptions-item label="联系电话">{{ getDisplayContactMobile(currentOrder) }}</el-descriptions-item>
                    <el-descriptions-item label="服务日期">{{ getDisplayServiceDate(currentOrder) }}</el-descriptions-item>
                    <el-descriptions-item label="婚礼日期">{{ currentOrder.wedding_date || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="服务地址" :span="2">{{ currentOrder.service_address || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="订单总额">¥{{ currentOrder.total_amount }}</el-descriptions-item>
                    <el-descriptions-item
                        v-if="Number(currentOrder.addon_amount || 0) > 0"
                        label="附加服务金额"
                    >
                        ¥{{ currentOrder.addon_amount }}
                    </el-descriptions-item>
                    <el-descriptions-item label="优惠金额">¥{{ currentOrder.discount_amount }}</el-descriptions-item>
                    <el-descriptions-item label="应付金额">¥{{ currentOrder.pay_amount }}</el-descriptions-item>
                    <el-descriptions-item label="已付金额">
                        <span class="text-red-500 font-bold">¥{{ getDisplayPaidAmount(currentOrder) }}</span>
                    </el-descriptions-item>
                    <el-descriptions-item label="支付模式">{{ currentOrder.payment_mode_desc || '全款支付' }}</el-descriptions-item>
                    <el-descriptions-item label="当前待支付">{{ currentOrder.need_pay_label || '无需支付' }}</el-descriptions-item>
                    <el-descriptions-item v-if="Number(currentOrder.deposit_amount || 0) > 0" label="定金金额">¥{{ currentOrder.deposit_amount }}</el-descriptions-item>
                    <el-descriptions-item v-if="Number(currentOrder.balance_amount || 0) > 0" label="尾款金额">¥{{ currentOrder.balance_amount }}</el-descriptions-item>
                    <el-descriptions-item v-if="Number(currentOrder.unpaid_amount || 0) >= 0" label="待付金额">¥{{ currentOrder.unpaid_amount }}</el-descriptions-item>
                    <el-descriptions-item v-if="currentOrder.deposit_remark" label="支付说明" :span="2">{{ currentOrder.deposit_remark }}</el-descriptions-item>
                    <el-descriptions-item label="支付方式">{{ currentOrder.pay_type_desc || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="支付状态">{{ currentOrder.pay_status_desc || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="线下凭证" :span="2">
                        <el-image
                            v-if="currentOrder.pay_voucher"
                            :src="currentOrder.pay_voucher"
                            fit="contain"
                            style="width: 100%; max-height: 260px"
                        />
                        <span v-else>-</span>
                    </el-descriptions-item>
                    <el-descriptions-item label="凭证状态">
                        {{ currentOrder.pay_voucher_status_desc || '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="审核备注">
                        {{ currentOrder.pay_voucher_audit_remark || '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="用户备注" :span="2">{{ currentOrder.user_remark || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="管理备注" :span="2">{{ currentOrder.admin_remark || '-' }}</el-descriptions-item>
                </el-descriptions>

                <div class="mt-4" v-if="currentOrder.items && currentOrder.items.length > 0">
                    <h4 class="font-bold mb-2">服务项目</h4>
                    <el-table :data="currentOrder.items" border size="small">
                        <el-table-column label="工作人员" prop="staff_name" />
                        <el-table-column label="套餐" prop="package_name" />
                        <el-table-column label="服务日期" prop="service_date" />
                        <el-table-column label="单价" prop="price">
                            <template #default="{ row }">¥{{ row.price }}</template>
                        </el-table-column>
                        <el-table-column label="数量" prop="quantity" />
                        <el-table-column label="小计" prop="subtotal">
                            <template #default="{ row }">¥{{ row.subtotal }}</template>
                        </el-table-column>
                        <el-table-column label="附加服务" min-width="220">
                            <template #default="{ row }">
                                <div v-if="row.addons && row.addons.length" class="flex flex-wrap gap-2">
                                    <el-tag
                                        v-for="addon in row.addons"
                                        :key="addon.id"
                                        size="small"
                                        type="warning"
                                    >
                                        {{ addon.addon_name || addon.name }} +¥{{ addon.subtotal || addon.price }}
                                    </el-tag>
                                </div>
                                <span v-else>-</span>
                            </template>
                        </el-table-column>
                    </el-table>
                </div>

                <div class="mt-4" v-if="currentOrder.payments && currentOrder.payments.length > 0">
                    <h4 class="font-bold mb-2">支付记录</h4>
                    <el-table :data="currentOrder.payments" border size="small">
                        <el-table-column label="流水号" prop="payment_sn" min-width="180" />
                        <el-table-column label="支付阶段" min-width="90">
                            <template #default="{ row }">{{ row.pay_type_desc || '-' }}</template>
                        </el-table-column>
                        <el-table-column label="支付方式" min-width="100">
                            <template #default="{ row }">{{ row.pay_way_desc || '-' }}</template>
                        </el-table-column>
                        <el-table-column label="支付金额" min-width="100">
                            <template #default="{ row }">¥{{ row.pay_amount }}</template>
                        </el-table-column>
                        <el-table-column label="支付状态" min-width="100">
                            <template #default="{ row }">{{ row.pay_status_desc || '-' }}</template>
                        </el-table-column>
                        <el-table-column label="支付时间" prop="pay_time" min-width="160" />
                    </el-table>
                </div>

                <div class="mt-4" v-if="currentOrder.logs && currentOrder.logs.length > 0">
                    <h4 class="font-bold mb-2">操作日志</h4>
                    <el-timeline>
                        <el-timeline-item 
                            v-for="log in currentOrder.logs" 
                            :key="log.id"
                            :timestamp="log.create_time"
                            placement="top"
                        >
                            <span class="text-gray-500">[{{ log.operator_type_desc }}]</span>
                            {{ log.content }}
                        </el-timeline-item>
                    </el-timeline>
                </div>
            </div>
        </el-dialog>

        <!-- 线下凭证审核弹窗 -->
        <el-dialog v-model="auditVisible" title="线下凭证审核" width="520px">
            <el-form :model="auditForm" label-width="100px">
                <el-form-item label="订单编号">
                    <span>{{ auditForm.order_sn || '-' }}</span>
                </el-form-item>
                <el-form-item label="支付金额">
                    <span>¥{{ auditForm.pay_amount }}</span>
                </el-form-item>
                <el-form-item label="支付凭证">
                    <el-image
                        v-if="auditForm.voucher"
                        :src="auditForm.voucher"
                        fit="contain"
                        style="width: 100%; max-height: 260px"
                    />
                    <span v-else>未上传</span>
                </el-form-item>
                <el-form-item label="审核备注">
                    <el-input
                        v-model="auditForm.remark"
                        type="textarea"
                        :rows="3"
                        placeholder="可填写拒绝原因或备注"
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="auditVisible = false">取消</el-button>
                <el-button type="danger" @click="submitAudit(0)">拒绝</el-button>
                <el-button type="primary" @click="submitAudit(1)">通过</el-button>
            </template>
        </el-dialog>

        <!-- 取消订单弹窗 -->
        <el-dialog v-model="cancelVisible" title="取消订单" width="500px">
            <el-form :model="cancelForm" label-width="100px">
                <el-form-item label="取消原因">
                    <el-input 
                        v-model="cancelForm.reason" 
                        type="textarea" 
                        :rows="3" 
                        placeholder="请输入取消原因"
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="cancelVisible = false">取消</el-button>
                <el-button type="danger" @click="submitCancel">确认取消</el-button>
            </template>
        </el-dialog>
    </admin-page-shell>
</template>

<script lang="ts" setup name="orderLists">
import { useRoute, useRouter } from 'vue-router'
import {
    orderLists,
    orderDetail,
    orderStatistics,
    orderCancel,
    orderStartService,
    orderComplete,
    orderAuditVoucher,
    orderDelete,
    orderAdd,
    orderEstimatePayment
} from '@/api/order'
import { getUserList } from '@/api/consumer'
import { staffAll } from '@/api/staff'
import { packageAll } from '@/api/service'
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
    create_time: []
})

const statistics = ref<any>({})
const detailVisible = ref(false)
const currentOrder = ref<any>(null)
const addVisible = ref(false)
const userLoading = ref(false)
const userOptions = ref<any[]>([])
const staffOptions = ref<any[]>([])
const packageOptionsMap = reactive<Record<number, any[]>>({})
const addForm = reactive<any>({
    user_id: undefined,
    contact_name: '',
    contact_mobile: '',
    service_date: '',
    wedding_date: '',
    wedding_venue: '',
    service_address: '',
    discount_amount: 0,
    admin_remark: '',
    items: [
        {
            staff_id: undefined,
            staff_name: '',
            package_name: '',
            price: 0,
            quantity: 1
        }
    ]
})
const addEstimate = reactive<any>({
    payment_mode_desc: '全款支付',
    total_amount: 0,
    discount_amount: 0,
    pay_amount: 0,
    deposit_amount: 0,
    balance_amount: 0,
    deposit_remark: ''
})
const auditVisible = ref(false)
const auditForm = reactive({
    id: 0,
    order_sn: '',
    pay_amount: 0,
    voucher: '',
    remark: ''
})
const cancelVisible = ref(false)
const cancelForm = reactive({
    id: 0,
    reason: ''
})

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: orderLists,
    params: queryParams
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

const getStatusType = (
    status: number
): 'warning' | 'primary' | 'info' | 'success' | 'danger' => {
    const types: Record<number, 'warning' | 'primary' | 'info' | 'success' | 'danger'> = {
        0: 'warning',
        1: 'warning',
        2: 'primary',
        3: 'info',
        4: 'success',
        5: 'success',
        6: 'info',
        7: 'warning',
        8: 'danger',
        9: 'danger'
    }
    return types[status] || 'info'
}

const getDisplayContactName = (order: any) => {
    return order?.contact_name || order?.user?.nickname || '-'
}

const getDisplayContactMobile = (order: any) => {
    return order?.contact_mobile || order?.user?.mobile || '-'
}

const getDisplayServiceDate = (order: any) => {
    if (order?.service_date) return order.service_date
    const dates = (order?.items || [])
        .map((item: any) => item.service_date || item.schedule_date)
        .filter(Boolean)
    if (!dates.length) return '-'
    return Array.from(new Set(dates)).join('、')
}

const getDisplayPaidAmount = (order: any) => {
    return Number(order?.paid_amount ?? 0).toFixed(2)
}

const resetAddForm = () => {
    addForm.user_id = undefined
    addForm.contact_name = ''
    addForm.contact_mobile = ''
    addForm.service_date = ''
    addForm.wedding_date = ''
    addForm.wedding_venue = ''
    addForm.service_address = ''
    addForm.discount_amount = 0
    addForm.admin_remark = ''
    addForm.items = [
        {
            staff_id: undefined,
            staff_name: '',
            package_id: undefined,
            package_name: '',
            price: 0,
            quantity: 1
        }
    ]
    Object.assign(addEstimate, {
        payment_mode_desc: '全款支付',
        total_amount: 0,
        discount_amount: 0,
        pay_amount: 0,
        deposit_amount: 0,
        balance_amount: 0,
        deposit_remark: ''
    })
}

const addOrderItem = () => {
    addForm.items.push({
        staff_id: undefined,
        staff_name: '',
        package_id: undefined,
        package_name: '',
        price: 0,
        quantity: 1
    })
}

const remoteUserSearch = async (keyword: string) => {
    userLoading.value = true
    try {
        const { data = [] } = await getUserList({
            keyword,
            page_no: 1,
            page_size: 20
        })
        userOptions.value = (Array.isArray(data) ? data : []).map((item: any) => ({
            id: item.id,
            label: `${item.nickname || '未命名'}（${item.mobile || item.sn || item.id}）`
        }))
    } finally {
        userLoading.value = false
    }
}

const loadStaffOptions = async () => {
    const list = await staffAll({})
    staffOptions.value = Array.isArray(list) ? list : []
}

const loadPackageOptions = async (index: number, staffId: number) => {
    if (!staffId) {
        packageOptionsMap[index] = []
        return
    }
    const list = await packageAll({ staff_id: staffId })
    packageOptionsMap[index] = Array.isArray(list) ? list : []
}

const handleStaffChange = async (index: number) => {
    const item = addForm.items[index]
    const staff = staffOptions.value.find((staffItem: any) => Number(staffItem.id) === Number(item.staff_id))
    item.staff_name = staff?.name || ''
    item.package_id = undefined
    item.package_name = ''
    item.price = 0
    await loadPackageOptions(index, Number(item.staff_id || 0))
}

const handlePackageChange = (index: number) => {
    const item = addForm.items[index]
    const options = packageOptionsMap[index] || []
    const pkg = options.find((pkgItem: any) => Number(pkgItem.id) === Number(item.package_id))
    item.package_name = pkg?.name || ''
    item.price = Number(pkg?.price || 0)
}

const refreshAddEstimate = async () => {
    const validItems = addForm.items.filter((item: any) => Number(item.price || 0) > 0)
    const data = await orderEstimatePayment({
        items: validItems,
        discount_amount: addForm.discount_amount || 0
    })
    Object.assign(addEstimate, data || {})
}

const handleAdd = async () => {
    resetAddForm()
    addVisible.value = true
    await Promise.all([loadStaffOptions(), refreshAddEstimate()])
}

const submitAdd = async () => {
    const items = addForm.items
        .filter((item: any) => Number(item.price || 0) > 0)
        .map((item: any) => ({
            staff_id: Number(item.staff_id || 0),
            staff_name: item.staff_name,
            package_id: Number(item.package_id || 0),
            package_name: item.package_name,
            price: Number(item.price || 0),
            quantity: Number(item.quantity || 1),
            service_date: addForm.service_date
        }))

    await orderAdd({
        user_id: Number(addForm.user_id || 0),
        contact_name: addForm.contact_name,
        contact_mobile: addForm.contact_mobile,
        service_date: addForm.service_date,
        wedding_date: addForm.wedding_date,
        wedding_venue: addForm.wedding_venue,
        service_address: addForm.service_address,
        discount_amount: Number(addForm.discount_amount || 0),
        admin_remark: addForm.admin_remark,
        items
    })
    feedback.msgSuccess('创建成功')
    addVisible.value = false
    getLists()
    getStatistics()
}

const clearDetailQuery = () => {
    if (!route.query.detail_id) {
        return
    }

    const nextQuery = { ...route.query }
    delete nextQuery.detail_id

    router.replace({
        path: route.path,
        query: nextQuery
    })
}

const openOrderDetail = async (id: number, clearQuery = false) => {
    if (!id) {
        return
    }

    const res = await orderDetail({ id })
    currentOrder.value = res
    detailVisible.value = true

    if (clearQuery) {
        clearDetailQuery()
    }
}

const handleDetail = async (row: any) => {
    await openOrderDetail(Number(row.id))
}

const handleAuditVoucher = (row: any) => {
    auditForm.id = row.id
    auditForm.order_sn = row.order_sn || ''
    auditForm.pay_amount = row.pay_amount || 0
    auditForm.voucher = row.pay_voucher || ''
    auditForm.remark = ''
    auditVisible.value = true
}

const submitAudit = async (approved: number) => {
    await orderAuditVoucher({
        id: auditForm.id,
        approved,
        remark: auditForm.remark
    })
    feedback.msgSuccess('操作成功')
    auditVisible.value = false
    getLists()
    getStatistics()
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
})

watch(
    () => JSON.stringify({ items: addForm.items, discount_amount: addForm.discount_amount }),
    async () => {
        if (!addVisible.value) return
        await refreshAddEstimate()
    },
    { deep: true }
)

watch(
    () => route.query.detail_id,
    async (detailId) => {
        const id = Number(detailId || 0)
        if (!id) {
            return
        }
        await openOrderDetail(id, true)
    },
    { immediate: true }
)

getLists()
getStatistics()
</script>

<style scoped>
.order-detail :deep(.el-descriptions__label) {
    width: 100px;
}
</style>
