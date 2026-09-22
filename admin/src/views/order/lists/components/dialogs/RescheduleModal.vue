<template>
    <el-dialog v-model="dialogVisible" title="订单改期" width="520px" destroy-on-close>
        <el-form
            ref="formRef"
            :model="formData"
            :rules="formRules"
            label-width="96px"
        >
            <el-form-item label="订单编号">
                <span>{{ formData.order_sn || '-' }}</span>
            </el-form-item>
            <el-form-item label="当前日期">
                <span>{{ formData.current_service_date || '-' }}</span>
            </el-form-item>
            <el-form-item label="新服务日期" prop="service_date">
                <el-date-picker
                    v-model="formData.service_date"
                    type="date"
                    value-format="YYYY-MM-DD"
                    placeholder="请选择新服务日期"
                    :disabled-date="disableTodayAndPastDate"
                    class="w-full"
                />
            </el-form-item>
            <el-form-item label="改期原因" prop="reason">
                <el-input
                    v-model="formData.reason"
                    type="textarea"
                    :rows="3"
                    maxlength="255"
                    show-word-limit
                    placeholder="请输入改期原因"
                />
            </el-form-item>
        </el-form>
        <template #footer>
            <el-button @click="dialogVisible = false">取消</el-button>
            <el-button type="primary" :loading="submitting" @click="handleSubmit">
                确认改期
            </el-button>
        </template>
    </el-dialog>
</template>

<script lang="ts" setup>
import { computed, reactive, ref, watch } from 'vue'
import type { FormInstance, FormRules } from 'element-plus'
import { orderDirectReschedule } from '@/api/order'
import feedback from '@/utils/feedback'

const props = defineProps<{
    modelValue: boolean
    orderData: {
        id: number
        order_sn: string
        service_date: string
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

const formRef = ref<FormInstance>()
const submitting = ref(false)

const formData = reactive({
    id: 0,
    order_sn: '',
    current_service_date: '',
    service_date: '',
    reason: ''
})

const formRules: FormRules = {
    service_date: [{ required: true, message: '请选择新服务日期', trigger: 'change' }],
    reason: [{ max: 255, message: '改期原因最多255个字符', trigger: 'blur' }]
}

const disableTodayAndPastDate = (date: Date) => {
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    return date.getTime() <= today.getTime()
}

watch(
    () => props.orderData,
    (val) => {
        if (val) {
            formData.id = Number(val.id || 0)
            formData.order_sn = val.order_sn || ''
            formData.current_service_date = val.service_date || ''
            formData.service_date = ''
            formData.reason = ''
            formRef.value?.clearValidate()
        }
    },
    { immediate: true, deep: true }
)

const handleSubmit = async () => {
    await formRef.value?.validate()
    if (formData.service_date === formData.current_service_date) {
        feedback.msgError('新服务日期不能与当前服务日期相同')
        return
    }

    submitting.value = true
    try {
        await orderDirectReschedule({
            id: formData.id,
            service_date: formData.service_date,
            reason: formData.reason
        })
        feedback.msgSuccess('改期成功')
        dialogVisible.value = false
        emit('success', formData.id)
    } catch (error) {
        console.error(error)
    } finally {
        submitting.value = false
    }
}
</script>
