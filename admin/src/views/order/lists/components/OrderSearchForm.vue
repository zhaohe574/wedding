<template>
    <search-panel>
        <el-form ref="formRef" class="mb-[-16px]" :model="params" :inline="true">
            <el-form-item class="w-[180px]" label="订单编号">
                <el-input
                    v-model="params.order_sn"
                    placeholder="输入订单编号"
                    clearable
                    @keyup.enter="$emit('search')"
                />
            </el-form-item>
            <el-form-item class="w-[150px]" label="联系人">
                <el-input
                    v-model="params.contact_name"
                    placeholder="输入联系人"
                    clearable
                    @keyup.enter="$emit('search')"
                />
            </el-form-item>
            <el-form-item class="w-[150px]" label="联系电话">
                <el-input
                    v-model="params.contact_mobile"
                    placeholder="输入联系电话"
                    clearable
                    @keyup.enter="$emit('search')"
                />
            </el-form-item>
            <el-form-item class="w-[150px]" label="订单状态">
                <el-select v-model="params.order_status" placeholder="选择状态" clearable>
                    <el-option
                        v-for="item in ORDER_STATUS_OPTIONS"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                    />
                </el-select>
            </el-form-item>
            <el-form-item class="w-[150px]" label="支付模式">
                <el-select v-model="params.payment_mode" placeholder="选择模式" clearable>
                    <el-option
                        v-for="item in PAYMENT_MODE_OPTIONS"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                    />
                </el-select>
            </el-form-item>
            <el-form-item class="w-[150px]" label="定金状态">
                <el-select v-model="params.deposit_paid" placeholder="选择状态" clearable>
                    <el-option label="全部" value="" />
                    <el-option label="未支付" :value="0" />
                    <el-option label="已支付" :value="1" />
                </el-select>
            </el-form-item>
            <el-form-item class="w-[150px]" label="尾款状态">
                <el-select v-model="params.balance_paid" placeholder="选择状态" clearable>
                    <el-option label="全部" value="" />
                    <el-option label="未支付" :value="0" />
                    <el-option label="已支付" :value="1" />
                </el-select>
            </el-form-item>
            <el-form-item class="w-[160px]" label="凭证审核">
                <el-select v-model="params.has_voucher_pending" placeholder="全部" clearable>
                    <el-option label="全部" value="" />
                    <el-option label="仅看待审核凭证" :value="1" />
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
                <el-button type="primary" @click="$emit('search')">查询</el-button>
                <el-button @click="$emit('reset')">重置</el-button>
                <el-button type="success" @click="$emit('open-offline-drawer')">后台建单</el-button>
            </el-form-item>
        </el-form>
    </search-panel>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { ORDER_STATUS_OPTIONS, PAYMENT_MODE_OPTIONS } from '@/enums/orderEnums'

const props = defineProps<{
    params: {
        order_sn: string
        contact_name: string
        contact_mobile: string
        order_status: string | number
        payment_mode: string
        deposit_paid: string | number
        balance_paid: string | number
        has_voucher_pending?: string | number
        start_time: string
        end_time: string
    }
}>()

defineEmits<{
    (e: 'search'): void
    (e: 'reset'): void
    (e: 'open-offline-drawer'): void
}>()

const createTimeRange = computed<string[]>({
    get: () => {
        if (!props.params.start_time || !props.params.end_time) {
            return []
        }
        return [props.params.start_time, props.params.end_time]
    },
    set: (value) => {
        props.params.start_time = value?.[0] || ''
        props.params.end_time = value?.[1] || ''
    }
})
</script>
