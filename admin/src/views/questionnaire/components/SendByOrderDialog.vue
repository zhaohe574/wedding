<template>
    <el-dialog
        v-model="visible"
        title="按订单补发新人问卷"
        width="600px"
        destroy-on-close
        append-to-body
        class="send-by-order-dialog"
    >
        <el-alert
            type="info"
            :closable="false"
            show-icon
            class="send-by-order__alert"
            :title="alertTitle"
        />

        <section class="send-by-order__section">
            <div class="send-by-order__section-title">方式一：搜索选择订单</div>
            <div class="order-search">
                <el-select v-model="form.searchField" class="order-search__field">
                    <el-option
                        v-for="item in SEARCH_FIELD_OPTIONS"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                    />
                </el-select>
                <el-input
                    v-model="form.keyword"
                    class="order-search__input"
                    clearable
                    :placeholder="searchPlaceholder"
                    @keyup.enter="searchOrders"
                />
                <el-button :loading="searching" @click="searchOrders">搜索</el-button>
            </div>

            <el-select
                v-model="form.order_id"
                class="order-select"
                filterable
                clearable
                :loading="searching"
                placeholder="选择或搜索订单"
            >
                <el-option
                    v-for="order in orderOptions"
                    :key="order.id"
                    :label="orderOptionLabel(order)"
                    :value="Number(order.id)"
                    :disabled="!isSendableOrder(order)"
                >
                    <div class="order-option">
                        <span class="order-option__main">{{ orderOptionLabel(order) }}</span>
                        <el-tag
                            size="small"
                            effect="plain"
                            :type="getOrderStatusTagType(order.order_status)"
                        >
                            {{ order.order_status_desc || getOrderStatusText(order.order_status) }}
                        </el-tag>
                    </div>
                </el-option>
            </el-select>

            <p v-if="selectedOrder && !isSendableOrder(selectedOrder)" class="order-hint order-hint--danger">
                该订单当前状态不支持发送新人问卷，请选择待服务 / 服务中 / 已完成 / 已评价的订单。
            </p>
        </section>

        <el-divider>或</el-divider>

        <section class="send-by-order__section">
            <div class="send-by-order__section-title">方式二：直接输入订单 ID</div>
            <el-input-number
                v-model="form.order_id"
                class="w-full"
                :min="1"
                :controls="false"
                placeholder="请输入订单 ID"
            />
        </section>

        <template #footer>
            <div class="dialog-footer">
                <el-button @click="visible = false">取消</el-button>
                <el-button type="primary" :loading="submitting" @click="submit">
                    确认补发
                </el-button>
            </div>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'

import { orderLists } from '@/api/order'
import { coupleQuestionnaireSend } from '@/api/questionnaire'
import {
    getOrderStatusTagType,
    getOrderStatusText,
    OrderStatus
} from '@/enums/orderEnums'

interface OrderOption {
    id?: number
    order_sn?: string
    contact_name?: string
    contact_mobile?: string
    order_status?: number
    order_status_desc?: string
    service_date?: string
    wedding_date?: string
    [key: string]: any
}

type OrderSearchField = 'order_sn' | 'contact_mobile' | 'contact_name'

const props = withDefaults(
    defineProps<{
        modelValue: boolean
        fetchOrders?: (params: Record<string, any>) => Promise<any>
        sendByOrder?: (params: { order_id: number }) => Promise<any>
        alertTitle?: string
    }>(),
    {
        alertTitle:
            '仅支持待服务 / 服务中 / 已完成 / 已评价订单，且订单主服务人员已发布问卷版本。'
    }
)

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'success', detail: any): void
}>()

const SENDABLE_ORDER_STATUS: number[] = [
    OrderStatus.PENDING_SERVICE,
    OrderStatus.IN_SERVICE,
    OrderStatus.COMPLETED,
    OrderStatus.REVIEWED
]

const IGNORED_ORDER_STATUS: number[] = [
    OrderStatus.PENDING_PAY,
    OrderStatus.CANCELLED,
    OrderStatus.REFUNDED,
    OrderStatus.USER_DELETED,
    OrderStatus.REFUNDING
]

const SEARCH_FIELD_OPTIONS: Array<{
    value: OrderSearchField
    label: string
    placeholder: string
}> = [
    { value: 'order_sn', label: '订单编号', placeholder: '输入订单编号' },
    { value: 'contact_mobile', label: '联系电话', placeholder: '输入客户联系电话' },
    { value: 'contact_name', label: '联系人', placeholder: '输入联系人姓名' }
]

const visible = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value)
})

const searching = ref(false)
const submitting = ref(false)
const orderOptions = ref<OrderOption[]>([])
const form = reactive<{
    order_id: number | undefined
    searchField: OrderSearchField
    keyword: string
}>({
    order_id: undefined,
    searchField: 'order_sn',
    keyword: ''
})

const searchPlaceholder = computed(
    () => SEARCH_FIELD_OPTIONS.find((item) => item.value === form.searchField)?.placeholder || '输入关键词'
)

const selectedOrder = computed(() =>
    orderOptions.value.find((order) => Number(order.id) === Number(form.order_id))
)

const isSendableOrder = (order?: OrderOption) =>
    SENDABLE_ORDER_STATUS.includes(Number(order?.order_status))

const orderOptionLabel = (order?: OrderOption) => {
    if (!order) {
        return '-'
    }
    return [
        order.order_sn || `订单 #${order.id || '-'}`,
        order.contact_name,
        order.contact_mobile,
        order.service_date || order.wedding_date
    ]
        .filter(Boolean)
        .join(' · ')
}

const resetForm = () => {
    form.order_id = undefined
    form.searchField = 'order_sn'
    form.keyword = ''
    orderOptions.value = []
}

const searchOrders = async () => {
    if (searching.value) {
        return
    }
    searching.value = true
    const keyword = form.keyword.trim()
    try {
        const params: Record<string, any> = { page_no: 1, page_size: 20 }
        if (keyword) {
            params[form.searchField] = keyword
        }
        const res: any = await (props.fetchOrders || orderLists)(params)
        const rows: any[] = Array.isArray(res) ? res : res?.lists || res?.data?.lists || []
        orderOptions.value = rows
            .filter((row) => !IGNORED_ORDER_STATUS.includes(Number(row.order_status)))
            .map((row) => ({ ...row }))
    } catch {
        orderOptions.value = []
    } finally {
        searching.value = false
    }
}

const submit = async () => {
    const orderId = Number(form.order_id || 0)
    if (orderId <= 0) {
        ElMessage.warning('请先选择或输入订单 ID')
        return
    }
    if (selectedOrder.value && !isSendableOrder(selectedOrder.value)) {
        ElMessage.warning('该订单当前状态不支持发送新人问卷')
        return
    }
    submitting.value = true
    try {
        const res = await (props.sendByOrder || coupleQuestionnaireSend)({ order_id: orderId })
        ElMessage.success('补发成功')
        visible.value = false
        emit('success', res)
    } catch {
        // 业务错误已由 request 层统一提示，这里不重复弹窗
    } finally {
        submitting.value = false
    }
}

watch(
    () => props.modelValue,
    (isOpen) => {
        if (isOpen) {
            resetForm()
            void searchOrders()
        }
    }
)
</script>

<style scoped lang="scss">
.send-by-order__alert {
    border-radius: var(--admin-radius-sm);
}

.send-by-order__section {
    margin-top: 16px;

    &-title {
        margin-bottom: 8px;
        font-size: 13px;
        font-weight: 600;
        color: var(--admin-color-title);
    }
}

.order-search {
    display: flex;
    gap: 8px;
    margin-bottom: 10px;

    &__field {
        width: 130px;
        flex: none;
    }

    &__input {
        flex: 1;
    }
}

.order-select {
    width: 100%;
}

.order-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;

    &__main {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
}

.order-hint {
    margin: 8px 0 0;
    font-size: 12px;
    line-height: 1.6;
    color: var(--admin-color-muted);

    &--danger {
        color: var(--el-color-danger);
    }
}

.dialog-footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}
</style>
