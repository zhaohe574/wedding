<template>
    <el-dialog v-model="dialogVisible" title="订单退款" width="560px" destroy-on-close>
        <el-form :model="formData" label-width="110px">
            <el-form-item label="订单编号">
                <span>{{ formData.order_sn || '-' }}</span>
            </el-form-item>
            <el-form-item label="退款模式">
                <el-radio-group v-model="formData.mode">
                    <el-radio-button label="full">全部退款</el-radio-button>
                    <el-radio-button label="partial">部分退款</el-radio-button>
                </el-radio-group>
            </el-form-item>
            <el-form-item label="最大可退">
                <span class="font-medium text-red-500">¥{{ formatAmount(formData.refundable_amount) }}</span>
            </el-form-item>
            <el-form-item label="退款金额">
                <el-input-number
                    v-model="formData.refund_amount"
                    :min="0.01"
                    :max="refundAmountInputMax"
                    :precision="2"
                    :disabled="formData.mode === 'full'"
                    class="w-full"
                />
            </el-form-item>
            <el-form-item label="退款说明">
                <el-input
                    v-model="formData.reason"
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
            <el-button @click="dialogVisible = false">取消</el-button>
            <el-button type="danger" :loading="submitting" @click="handleSubmit">
                确认退款
            </el-button>
        </template>
    </el-dialog>
</template>

<script lang="ts" setup>
import { computed, ref, watch } from 'vue'
import { refundApply } from '@/api/order'
import feedback from '@/utils/feedback'

const props = defineProps<{
    modelValue: boolean
    refundData: {
        order_id: number
        order_sn: string
        order_status: number
        refundable_amount: number | string
    }
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', val: boolean): void
    (e: 'success', orderId: number): void
}>()

const dialogVisible = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val)
})

const formData = ref({
    order_id: 0,
    order_sn: '',
    order_status: 0,
    mode: 'full' as 'full' | 'partial',
    refundable_amount: 0,
    refund_amount: 0,
    reason: ''
})

const submitting = ref(false)

watch(
    () => props.refundData,
    (val) => {
        if (val) {
            const refundable = Number(val.refundable_amount || 0)
            formData.value = {
                order_id: Number(val.order_id || 0),
                order_sn: val.order_sn || '',
                order_status: Number(val.order_status || 0),
                mode: 'full',
                refundable_amount: refundable,
                refund_amount: refundable,
                reason: ''
            }
        }
    },
    { immediate: true, deep: true }
)

const formatAmount = (val: any) => Number(val || 0).toFixed(2)

const refundAmountInputMax = computed(() => {
    if (formData.value.mode === 'full') {
        return Number(formData.value.refundable_amount || 0)
    }
    return Number(Math.max(Number(formData.value.refundable_amount || 0) - 0.01, 0.01).toFixed(2))
})

const refundHintText = computed(() => {
    const isFinished = [4, 5, 6, 8, 9].includes(Number(formData.value.order_status || 0))
    if (formData.value.mode === 'partial') {
        return '部分退款仅更新订单与支付信息，不释放服务人员档期。'
    }
    return isFinished
        ? '全部退款将更新订单为已退款，不再释放已结束订单的档期。'
        : '全部退款成功后会释放该订单占用的服务人员档期。'
})

const handleSubmit = async () => {
    const maxAmount = Number(formData.value.refundable_amount || 0)
    const refundAmount = Number(
        (formData.value.mode === 'full' ? formData.value.refundable_amount : formData.value.refund_amount) || 0
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
    if (formData.value.mode === 'partial' && refundAmount >= maxAmount) {
        feedback.msgError('部分退款金额必须小于最大可退金额')
        return
    }

    submitting.value = true
    try {
        await refundApply({
            order_id: formData.value.order_id,
            refund_amount: refundAmount,
            reason: formData.value.reason.trim()
        })
        feedback.msgSuccess('退款申请成功')
        dialogVisible.value = false
        emit('success', formData.value.order_id)
    } catch (error) {
        console.error(error)
    } finally {
        submitting.value = false
    }
}
</script>
