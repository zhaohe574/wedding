<template>
    <el-dialog v-model="dialogVisible" title="取消订单" width="500px" destroy-on-close>
        <el-form :model="formData" label-width="100px">
            <el-form-item label="订单编号">
                <span>{{ orderSn || '-' }}</span>
            </el-form-item>
            <el-form-item label="取消原因" required>
                <el-input
                    v-model="formData.reason"
                    type="textarea"
                    :rows="3"
                    placeholder="请输入取消原因"
                />
            </el-form-item>
        </el-form>
        <template #footer>
            <el-button @click="dialogVisible = false">取消</el-button>
            <el-button type="danger" :loading="submitting" @click="handleSubmit">
                确认取消
            </el-button>
        </template>
    </el-dialog>
</template>

<script lang="ts" setup>
import { computed, ref, watch } from 'vue'
import { orderCancel } from '@/api/order'
import feedback from '@/utils/feedback'

const props = defineProps<{
    modelValue: boolean
    orderId: number
    orderSn?: string
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
    reason: ''
})

const submitting = ref(false)

watch(
    () => props.orderId,
    (val) => {
        formData.value.id = val || 0
        formData.value.reason = ''
    },
    { immediate: true }
)

const handleSubmit = async () => {
    if (!formData.value.id) return
    if (!formData.value.reason.trim()) {
        feedback.msgError('请输入取消原因')
        return
    }

    submitting.value = true
    try {
        await orderCancel(formData.value)
        feedback.msgSuccess('订单已取消')
        dialogVisible.value = false
        emit('success')
    } catch (error) {
        console.error(error)
    } finally {
        submitting.value = false
    }
}
</script>
