<template>
    <el-dialog v-model="dialogVisible" title="确认线下收款" width="560px" destroy-on-close>
        <el-form :model="formData" label-width="100px">
            <el-form-item label="订单编号">
                <span>{{ formData.order_sn || '-' }}</span>
            </el-form-item>
            <el-form-item label="支付阶段">
                <el-tag type="info">{{ formData.pay_label || '-' }}</el-tag>
            </el-form-item>
            <el-form-item label="收款金额">
                <span class="text-red-500 font-bold">¥{{ formatAmount(formData.pay_amount) }}</span>
            </el-form-item>
            <el-form-item label="收款归属" required>
                <el-radio-group v-model="formData.collection_owner">
                    <el-radio :value="1">平台收款</el-radio>
                    <el-radio :value="2">服务人员代收</el-radio>
                </el-radio-group>
            </el-form-item>
            <el-form-item label="收款凭证" required>
                <material-picker v-model="formData.voucher" :limit="1" />
            </el-form-item>
        </el-form>
        <template #footer>
            <el-button @click="dialogVisible = false">取消</el-button>
            <el-button type="primary" :loading="submitting" @click="handleSubmit">
                确认收款
            </el-button>
        </template>
    </el-dialog>
</template>

<script lang="ts" setup>
import { computed, ref, watch } from 'vue'
import { orderConfirmOfflinePay } from '@/api/order'
import feedback from '@/utils/feedback'

const props = defineProps<{
    modelValue: boolean
    payData: {
        id: number
        order_sn: string
        pay_type: 2 | 3
        pay_amount: number | string
        pay_label: string
        collection_owner?: 1 | 2
        voucher: string
    }
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', val: boolean): void
    (e: 'success'): void
}>()

const dialogVisible = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val)
})

const formData = ref({
    id: 0,
    order_sn: '',
    pay_type: 3 as 2 | 3,
    pay_amount: 0 as number | string,
    pay_label: '全款',
    collection_owner: undefined as 1 | 2 | undefined,
    voucher: ''
})

const submitting = ref(false)

watch(
    () => props.payData,
    (val) => {
        if (val) {
            formData.value = {
                id: val.id || 0,
                order_sn: val.order_sn || '',
                pay_type: val.pay_type || 3,
                pay_amount: val.pay_amount || 0,
                pay_label: val.pay_label || '全款',
                collection_owner: val.collection_owner,
                voucher: val.voucher || ''
            }
        }
    },
    { immediate: true, deep: true }
)

const formatAmount = (val: any) => Number(val || 0).toFixed(2)

const handleSubmit = async () => {
    const payAmount = Number(formData.value.pay_amount || 0)
    if (payAmount <= 0) {
        feedback.msgError('收款金额必须大于0')
        return
    }
    if (!formData.value.voucher || !formData.value.collection_owner) {
        feedback.msgError('请选择收款归属并上传收款凭证')
        return
    }

    submitting.value = true
    try {
        await orderConfirmOfflinePay({
            id: formData.value.id,
            pay_type: formData.value.pay_type,
            pay_amount: payAmount,
            collection_owner: formData.value.collection_owner,
            voucher: formData.value.voucher
        })
        feedback.msgSuccess('线下收款已确认')
        dialogVisible.value = false
        emit('success')
    } catch (error) {
        console.error(error)
    } finally {
        submitting.value = false
    }
}
</script>
