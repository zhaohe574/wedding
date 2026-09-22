<template>
    <el-dialog v-model="dialogVisible" title="线下凭证审核" width="520px" destroy-on-close>
        <el-form :model="formData" label-width="100px">
            <el-form-item label="订单编号">
                <span>{{ formData.order_sn || '-' }}</span>
            </el-form-item>
            <el-form-item v-if="formData.phase_desc" label="付款阶段">
                <el-tag size="small" type="info">{{ formData.phase_desc }}</el-tag>
            </el-form-item>
            <el-form-item label="支付金额">
                <span class="text-red-500 font-bold">¥{{ formData.pay_amount }}</span>
            </el-form-item>
            <el-form-item label="支付凭证">
                <el-image
                    v-if="formData.voucher"
                    :src="formData.voucher"
                    :preview-src-list="[formData.voucher]"
                    preview-teleported
                    fit="contain"
                    style="width: 100%; max-height: 260px"
                />
                <span v-else class="text-gray-400">未上传</span>
            </el-form-item>
            <el-form-item label="收款归属" required>
                <el-radio-group v-model="formData.collection_owner">
                    <el-radio :value="1">平台收款</el-radio>
                    <el-radio :value="2">服务人员代收</el-radio>
                </el-radio-group>
            </el-form-item>
            <el-form-item label="审核备注">
                <el-input
                    v-model="formData.remark"
                    type="textarea"
                    :rows="3"
                    placeholder="若驳回请务必填写原因；通过时可填写核对说明"
                />
            </el-form-item>
        </el-form>
        <template #footer>
            <el-button @click="dialogVisible = false">取消</el-button>
            <el-button type="danger" :loading="submitting" @click="handleSubmit(0)">拒绝/驳回</el-button>
            <el-button type="primary" :loading="submitting" @click="handleSubmit(1)">审核通过</el-button>
        </template>
    </el-dialog>
</template>

<script lang="ts" setup>
import { computed, ref, watch } from 'vue'
import { orderAuditVoucher } from '@/api/order'
import feedback from '@/utils/feedback'

const props = defineProps<{
    modelValue: boolean
    orderData: {
        id: number
        order_sn: string
        pay_amount: number | string
        voucher: string
        collection_owner?: number
        remark?: string
        receipt_id?: number
        phase_desc?: string
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
    receipt_id: 0,
    order_sn: '',
    pay_amount: 0 as number | string,
    voucher: '',
    phase_desc: '',
    collection_owner: 1 as number | undefined,
    remark: ''
})

const submitting = ref(false)

watch(
    () => props.orderData,
    (val) => {
        if (val) {
            formData.value = {
                id: val.id || 0,
                receipt_id: val.receipt_id || 0,
                order_sn: val.order_sn || '',
                pay_amount: val.pay_amount || 0,
                voucher: val.voucher || '',
                phase_desc: val.phase_desc || '',
                collection_owner: val.collection_owner || 1,
                remark: val.remark || ''
            }
        }
    },
    { immediate: true, deep: true }
)

const handleSubmit = async (status: number) => {
    if (!formData.value.id) return
    if (status === 0 && !formData.value.remark?.trim()) {
        feedback.msgError('驳回时请在审核备注中填写原因')
        return
    }
    if (!formData.value.collection_owner) {
        feedback.msgError('请选择收款归属')
        return
    }
    submitting.value = true
    try {
        const payload: Record<string, any> = {
            id: formData.value.id,
            approved: status,
            status,
            remark: formData.value.remark?.trim() || '',
            collection_owner: formData.value.collection_owner
        }
        if (formData.value.receipt_id) {
            payload.receipt_id = formData.value.receipt_id
        }
        await orderAuditVoucher(payload)
        feedback.msgSuccess(status === 1 ? '审核通过成功' : '已驳回该收款凭证')
        dialogVisible.value = false
        emit('success')
    } catch (error) {
        console.error(error)
    } finally {
        submitting.value = false
    }
}
</script>
