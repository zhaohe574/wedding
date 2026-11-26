<template>
    <el-dialog
        v-model="visible"
        title="按订单生成档期确认函"
        width="600px"
        destroy-on-close
        append-to-body
        class="generate-by-order-dialog"
    >
        <el-alert
            type="info"
            :closable="false"
            show-icon
            class="generate-by-order__alert"
            title="仅待服务 / 服务中 / 已完成 / 已评价订单支持生成档期确认函（已暂停/未确认/未支付订单不可生成）；生成后可在记录列表中预览、下载或重算。"
        />

        <section class="generate-by-order__section">
            <div class="generate-by-order__section-title">选择海报名片模板版本</div>
            <el-select
                v-model="form.config_id"
                class="w-full"
                :loading="configLoading"
                :disabled="!configOptions.length"
                placeholder="请选择启用中的模板版本"
            >
                <el-option
                    v-for="item in configOptions"
                    :key="item.config_id"
                    :label="configOptionLabel(item)"
                    :value="Number(item.config_id)"
                />
            </el-select>
            <p v-if="!configLoading && !configOptions.length" class="order-hint order-hint--danger">
                当前没有启用中的海报模板，请先在「我的模板」中启用一个版本后再生成。
            </p>
            <p v-else class="order-hint">
                生成时使用所选模板版本渲染，可随时在模板工作台切换默认版本。
            </p>
        </section>

        <el-divider>或</el-divider>

        <section class="generate-by-order__section">
            <div class="generate-by-order__section-title">方式一：搜索选择订单</div>
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
                placeholder="选择订单（未输入关键词时展示最近 20 条）"
            >
                <el-option
                    v-for="order in orderOptions"
                    :key="order.id"
                    :label="orderOptionLabel(order)"
                    :value="Number(order.id)"
                    :disabled="!isGenerableOrder(order)"
                >
                    <div class="order-option">
                        <span class="order-option__main">{{ orderOptionLabel(order) }}</span>
                        <span class="order-option__tags">
                            <el-tag
                                size="small"
                                effect="plain"
                                :type="getOrderStatusTagType(order.order_status)"
                            >
                                {{ order.order_status_desc || getOrderStatusText(order.order_status) }}
                            </el-tag>
                            <el-tag v-if="!isGenerableOrder(order)" size="small" effect="plain" type="info">
                                不可生成
                            </el-tag>
                        </span>
                    </div>
                </el-option>
            </el-select>

            <p
                v-if="selectedOrder && !isGenerableOrder(selectedOrder)"
                class="order-hint order-hint--danger"
            >
                该订单当前状态不支持生成档期确认函，请选择待服务 / 服务中 / 已完成 / 已评价的订单。
            </p>
            <p v-else class="order-hint">
                已自动过滤待支付、已取消、退款中/已退款与用户删除的订单。
            </p>
        </section>

        <el-divider>或</el-divider>

        <section class="generate-by-order__section">
            <div class="generate-by-order__section-title">方式二：直接输入订单 ID</div>
            <el-input-number
                v-model="form.order_id"
                class="w-full"
                :min="1"
                :controls="false"
                placeholder="请输入订单 ID"
            />
            <p class="order-hint">适合从订单管理页复制订单 ID 后快速生成。</p>
        </section>

        <template #footer>
            <div class="dialog-footer">
                <el-button @click="visible = false">取消</el-button>
                <el-button type="primary" :loading="submitting" @click="submit">
                    确认生成
                </el-button>
            </div>
        </template>
    </el-dialog>
</template>

<script setup lang="ts" name="GenerateByOrderDialog">
/**
 * 服务人员「按订单生成档期确认函」弹窗：先选模板版本，再选订单或直接填订单 ID。
 * 订单筛选口径与运营端一致，但生成走 `/ops.staff/myScheduleConfirmLetterGenerate`。
 */
import { ElMessage } from 'element-plus'

import {
    myOrders,
    myScheduleConfirmLetterConfig,
    myScheduleConfirmLetterGenerate
} from '@/api/staff-center'
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
        defaultConfigId?: number
    }>(),
    {
        defaultConfigId: 0
    }
)

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'success', detail: any): void
}>()

const GENERABLE_ORDER_STATUS: number[] = [
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

const configLoading = ref(false)
const configOptions = ref<any[]>([])
const searching = ref(false)
const submitting = ref(false)
const orderOptions = ref<OrderOption[]>([])
const form = reactive<{
    order_id: number | undefined
    config_id: number | undefined
    searchField: OrderSearchField
    keyword: string
}>({
    order_id: undefined,
    config_id: undefined,
    searchField: 'order_sn',
    keyword: ''
})

const searchPlaceholder = computed(
    () =>
        SEARCH_FIELD_OPTIONS.find((item) => item.value === form.searchField)?.placeholder ||
        '输入关键词'
)

const selectedOrder = computed(() =>
    orderOptions.value.find((order) => Number(order.id) === Number(form.order_id))
)

const isGenerableOrder = (order?: OrderOption) =>
    GENERABLE_ORDER_STATUS.includes(Number(order?.order_status))

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

const configOptionLabel = (config: any) => {
    const name = config?.template_name || '未命名模板'
    const version = Number(config?.template_version || 1)
    const isDefault = Number(config?.is_default || 0) === 1
    return `${name}${isDefault ? '（默认）' : ''} · v${version}`
}

const resolveDefaultConfigId = () => {
    const preferred = Number(props.defaultConfigId || 0)
    if (
        preferred > 0 &&
        configOptions.value.some((item) => Number(item?.config_id || 0) === preferred)
    ) {
        return preferred
    }
    const defaultItem = configOptions.value.find(
        (item) => Number(item?.is_default || 0) === 1
    )
    return Number(defaultItem?.config_id || configOptions.value[0]?.config_id || 0) || undefined
}

const loadConfigOptions = async () => {
    configLoading.value = true
    try {
        const data: any = await myScheduleConfirmLetterConfig({ include_disabled: 0 })
        const versions = Array.isArray(data?.versions) ? data.versions : []
        configOptions.value = versions.filter((item: any) => Number(item?.status ?? 1) === 1)
        form.config_id = resolveDefaultConfigId()
    } catch {
        configOptions.value = []
        form.config_id = undefined
    } finally {
        configLoading.value = false
    }
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
        const res: any = await myOrders(params)
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

const resetForm = () => {
    form.order_id = undefined
    form.searchField = 'order_sn'
    form.keyword = ''
    orderOptions.value = []
}

const submit = async () => {
    const orderId = Number(form.order_id || 0)
    if (orderId <= 0) {
        ElMessage.warning('请先选择或输入订单 ID')
        return
    }
    if (selectedOrder.value && !isGenerableOrder(selectedOrder.value)) {
        ElMessage.warning('该订单当前状态不支持生成档期确认函')
        return
    }
    const configId = Number(form.config_id || 0)
    if (configId <= 0) {
        ElMessage.warning('请选择海报名片模板版本')
        return
    }
    submitting.value = true
    try {
        const res = await myScheduleConfirmLetterGenerate({
            order_id: orderId,
            config_id: configId
        })
        ElMessage.success('档期确认函已生成')
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
        if (!isOpen) {
            return
        }
        resetForm()
        void loadConfigOptions()
        void searchOrders()
    },
    { immediate: true }
)
</script>

<style scoped lang="scss">
.generate-by-order__alert {
    border-radius: var(--admin-radius-sm);
}

.generate-by-order__section {
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

    &__tags {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        gap: 6px;
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
