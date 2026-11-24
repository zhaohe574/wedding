<template>
    <el-dialog
        :model-value="modelValue"
        title="新增跟进记录"
        width="760px"
        destroy-on-close
        @update:model-value="emit('update:modelValue', $event)"
        @closed="handleClosed"
    >
        <el-form ref="formRef" :model="form" :rules="rules" label-width="110px">
            <div class="quick-follow-dialog__grid">
                <el-form-item label="客户" prop="customer_id">
                    <el-input v-if="lockedCustomer" :model-value="lockedCustomerLabel" disabled />
                    <el-select
                        v-else
                        v-model="form.customer_id"
                        placeholder="输入姓名或手机号搜索客户"
                        filterable
                        remote
                        reserve-keyword
                        :remote-method="searchCustomers"
                        :loading="customerLoading"
                        class="w-full"
                        @change="handleCustomerSelect"
                    >
                        <el-option
                            v-for="item in customerOptions"
                            :key="item.id"
                            :label="formatCustomerOption(item)"
                            :value="item.id"
                        >
                            <div class="quick-follow-dialog__option">
                                <span>{{ item.customer_name || '未命名客户' }}</span>
                                <span class="text-xs text-tx-secondary">
                                    {{ item.customer_mobile || '-' }} · {{ item.intention_level || 'D' }}级
                                    <template v-if="item.advisor_name"> · {{ item.advisor_name }}</template>
                                </span>
                            </div>
                        </el-option>
                    </el-select>
                </el-form-item>

                <el-form-item label="跟进方式" prop="follow_type">
                    <el-select v-model="form.follow_type" placeholder="请选择跟进方式" class="w-full">
                        <el-option
                            v-for="item in options.follow_type_options"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value"
                        />
                    </el-select>
                </el-form-item>

                <el-form-item label="跟进结果" prop="follow_result">
                    <el-select v-model="form.follow_result" placeholder="请选择跟进结果" class="w-full">
                        <el-option
                            v-for="item in options.follow_result_options"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value"
                        />
                    </el-select>
                </el-form-item>

                <el-form-item label="跟进后意向" prop="intention_after">
                    <el-select v-model="form.intention_after" placeholder="请选择意向" class="w-full">
                        <el-option
                            v-for="item in options.intention_options"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value"
                        />
                    </el-select>
                </el-form-item>

                <el-form-item label="沟通时长(分)" prop="duration">
                    <el-input-number v-model="form.duration" :min="0" :max="10000" class="w-full" />
                </el-form-item>

                <el-form-item label="是否重要" prop="is_important">
                    <el-switch v-model="form.is_important" :active-value="1" :inactive-value="0" />
                </el-form-item>

                <el-form-item
                    v-if="Number(form.follow_result) === 5"
                    label="流失原因"
                    prop="loss_reason"
                    class="quick-follow-dialog__full"
                >
                    <el-input
                        v-model="form.loss_reason"
                        maxlength="200"
                        placeholder="如：预算超出 / 已选其他机构 / 婚期取消"
                    />
                </el-form-item>

                <el-form-item label="下次跟进" prop="next_follow_time" class="quick-follow-dialog__full">
                    <div class="quick-follow-dialog__next-wrap">
                        <el-date-picker
                            v-model="form.next_follow_time"
                            type="datetime"
                            value-format="YYYY-MM-DD HH:mm:ss"
                            placeholder="请选择下次跟进时间"
                            class="flex-1"
                        />
                        <div class="quick-follow-dialog__chips">
                            <el-button size="small" plain @click="applyNextOffset(1)">明天</el-button>
                            <el-button size="small" plain @click="applyNextOffset(3)">3天后</el-button>
                            <el-button size="small" plain @click="applyNextOffset(7)">7天后</el-button>
                            <el-button size="small" plain @click="applyNextOffset(14)">14天后</el-button>
                        </div>
                    </div>
                </el-form-item>

                <el-form-item label="跟进内容" prop="follow_content" class="quick-follow-dialog__full">
                    <el-input
                        v-model="form.follow_content"
                        type="textarea"
                        :rows="4"
                        maxlength="2000"
                        show-word-limit
                        placeholder="记录本次沟通核心内容、客户诉求与报价反馈"
                    />
                </el-form-item>

                <el-form-item label="下次计划" prop="next_follow_content" class="quick-follow-dialog__full">
                    <el-input
                        v-model="form.next_follow_content"
                        maxlength="255"
                        placeholder="如：发送草坪婚礼报价方案并约到店看样"
                    />
                </el-form-item>

                <el-form-item label="沟通附件" prop="attachments" class="quick-follow-dialog__full">
                    <material-picker v-model="form.attachments" :limit="6" />
                </el-form-item>
            </div>
        </el-form>
        <template #footer>
            <el-button @click="emit('update:modelValue', false)">取消</el-button>
            <el-button type="primary" :loading="submitting" @click="handleSubmit">保存</el-button>
        </template>
    </el-dialog>
</template>

<script lang="ts" setup>
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage } from 'element-plus'

import {
    followRecordAdd,
    followRecordCustomerOptions,
    followRecordOptions
} from '@/api/crm/followRecord'

interface OptionItem {
    value: number | string
    label: string
}

interface CustomerOption {
    id: number
    customer_name: string
    customer_mobile: string
    intention_level: string
    advisor_id: number
    advisor_name?: string
}

const props = withDefaults(
    defineProps<{
        modelValue: boolean
        customer?: {
            id: number
            customer_name?: string
            customer_mobile?: string
            intention_level?: string
        } | null
    }>(),
    {
        customer: null
    }
)

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'submitted'): void
}>()

const formRef = shallowRef<FormInstance>()
const submitting = ref(false)
const customerLoading = ref(false)
const customerOptions = ref<CustomerOption[]>([])
const options = reactive({
    follow_type_options: [] as OptionItem[],
    follow_result_options: [] as OptionItem[],
    intention_options: [] as OptionItem[]
})

const createDefaultForm = () => ({
    customer_id: undefined as number | undefined,
    follow_type: 1,
    follow_content: '',
    follow_result: 1,
    intention_after: 'D',
    duration: 0,
    next_follow_time: '',
    next_follow_content: '',
    loss_reason: '',
    attachments: [] as string[],
    is_important: 0
})

const form = reactive(createDefaultForm())

const rules = reactive<FormRules>({
    customer_id: [{ required: true, message: '请选择客户', trigger: 'change' }],
    follow_type: [{ required: true, message: '请选择跟进方式', trigger: 'change' }],
    follow_result: [{ required: true, message: '请选择跟进结果', trigger: 'change' }],
    intention_after: [{ required: true, message: '请选择跟进后意向', trigger: 'change' }],
    follow_content: [{ required: true, message: '请输入跟进内容', trigger: 'blur' }]
})

const lockedCustomer = computed(() => Boolean(props.customer?.id))
const lockedCustomerLabel = computed(() => {
    const name = props.customer?.customer_name || '未命名客户'
    const mobile = props.customer?.customer_mobile ? ` / ${props.customer.customer_mobile}` : ''
    return `${name}${mobile}`
})

const formatCustomerOption = (item: CustomerOption) => {
    const mobile = item.customer_mobile ? ` / ${item.customer_mobile}` : ''
    return `${item.customer_name || '未命名客户'}${mobile}`
}

const applyNextOffset = (days: number) => {
    const target = new Date()
    target.setDate(target.getDate() + days)
    const y = target.getFullYear()
    const m = String(target.getMonth() + 1).padStart(2, '0')
    const d = String(target.getDate()).padStart(2, '0')
    form.next_follow_time = `${y}-${m}-${d} 10:00:00`
}

const loadOptions = async () => {
    if (options.follow_type_options.length) return
    const data = await followRecordOptions()
    options.follow_type_options = data?.follow_type_options || []
    options.follow_result_options = data?.follow_result_options || []
    options.intention_options = data?.intention_options || []
}

const searchCustomers = async (keyword = '') => {
    customerLoading.value = true
    try {
        const data = await followRecordCustomerOptions({ keyword })
        customerOptions.value = Array.isArray(data) ? data : []
    } finally {
        customerLoading.value = false
    }
}

const handleCustomerSelect = (customerId: number) => {
    const hit = customerOptions.value.find((item) => Number(item.id) === Number(customerId))
    if (hit?.intention_level) {
        form.intention_after = String(hit.intention_level)
    }
}

watch(
    () => props.modelValue,
    async (open) => {
        if (!open) return
        Object.assign(form, createDefaultForm())
        await loadOptions()
        if (props.customer?.id) {
            form.customer_id = Number(props.customer.id)
            form.intention_after = String(props.customer.intention_level || 'D')
        } else {
            await searchCustomers('')
        }
    }
)

const handleSubmit = async () => {
    await formRef.value?.validate()
    submitting.value = true
    try {
        await followRecordAdd({
            customer_id: Number(form.customer_id || 0),
            follow_type: Number(form.follow_type || 1),
            follow_content: form.follow_content.trim(),
            follow_result: Number(form.follow_result || 1),
            intention_after: form.intention_after,
            duration: Number(form.duration || 0),
            next_follow_time: form.next_follow_time || '',
            next_follow_content: form.next_follow_content.trim(),
            loss_reason: Number(form.follow_result) === 5 ? form.loss_reason.trim() : '',
            attachments: Array.isArray(form.attachments) ? form.attachments : [],
            is_important: Number(form.is_important || 0)
        })
        ElMessage.success('跟进记录已新增')
        emit('update:modelValue', false)
        emit('submitted')
    } finally {
        submitting.value = false
    }
}

const handleClosed = () => {
    formRef.value?.clearValidate()
}
</script>

<style lang="scss" scoped>
.quick-follow-dialog {
    &__grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0 20px;
    }

    &__full {
        grid-column: 1 / -1;
    }

    &__option {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    &__next-wrap {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
        width: 100%;
    }

    &__chips {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
}
</style>
