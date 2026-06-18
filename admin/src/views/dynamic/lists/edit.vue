<template>
    <div class="edit-popup">
        <popup
            ref="popupRef"
            :title="popupTitle"
            :async="true"
            width="800px"
            @confirm="handleSubmit"
            @close="handleClose"
        >
            <el-form ref="formRef" :model="formData" :rules="formRules" label-width="100px">
                <el-form-item label="动态类型" prop="dynamic_type">
                    <el-radio-group v-model="formData.dynamic_type">
                        <el-radio :label="1">图文</el-radio>
                        <el-radio :label="2">视频</el-radio>
                        <el-radio :label="4">活动</el-radio>
                    </el-radio-group>
                </el-form-item>

                <el-form-item label="标题" prop="title">
                    <el-input
                        v-model="formData.title"
                        placeholder="请输入标题（可选）"
                        maxlength="100"
                        show-word-limit
                    />
                </el-form-item>

                <el-form-item label="内容" prop="content">
                    <el-input
                        v-model="formData.content"
                        type="textarea"
                        :rows="6"
                        placeholder="请输入动态内容"
                        maxlength="2000"
                        show-word-limit
                    />
                </el-form-item>

                <el-form-item label="图片" prop="images" v-if="formData.dynamic_type !== 2">
                    <material-picker
                        v-model="formData.images"
                        :limit="9"
                        type="image"
                    />
                    <div class="form-tips">最多上传9张图片</div>
                </el-form-item>

                <el-form-item label="视频" prop="video" v-if="formData.dynamic_type === 2">
                    <material-picker
                        v-model="formData.video"
                        :limit="1"
                        type="video"
                    />
                </el-form-item>

                <el-form-item label="视频封面" prop="video_cover" v-if="formData.dynamic_type === 2 && formData.video">
                    <material-picker
                        v-model="formData.video_cover"
                        :limit="1"
                        type="image"
                    />
                </el-form-item>

                <el-form-item label="位置" prop="location">
                    <el-input
                        v-model="formData.location"
                        placeholder="请输入位置（可选）"
                        maxlength="100"
                    />
                </el-form-item>

                <el-form-item label="标签" prop="tags">
                    <div class="flex flex-wrap gap-2 mb-2">
                        <el-tag
                            v-for="(tag, index) in formData.tags"
                            :key="index"
                            closable
                            @close="handleRemoveTag(index)"
                        >
                            {{ tag }}
                        </el-tag>
                        <el-input
                            v-if="tagInputVisible"
                            ref="tagInputRef"
                            v-model="tagInputValue"
                            size="small"
                            style="width: 100px"
                            @keyup.enter="handleAddTag"
                            @blur="handleAddTag"
                        />
                        <el-button
                            v-else
                            size="small"
                            @click="showTagInput"
                        >
                            + 添加标签
                        </el-button>
                    </div>
                    <div class="form-tips">最多添加5个标签</div>
                </el-form-item>

                <el-form-item label="允许评论" prop="allow_comment">
                    <el-switch
                        v-model="formData.allow_comment"
                        :active-value="1"
                        :inactive-value="0"
                        active-text="允许"
                        inactive-text="禁止"
                    />
                    <div class="form-tips">关闭后用户将无法对该动态发表新评论</div>
                </el-form-item>

                <template v-if="formData.dynamic_type === 4">
                    <el-divider content-position="left">活动报名配置</el-divider>
                    <el-form-item label="活动开始" prop="activity_start_time">
                        <el-date-picker
                            v-model="activityStartDate"
                            type="datetime"
                            value-format="YYYY-MM-DD HH:mm:ss"
                            placeholder="请选择活动开始时间"
                        />
                    </el-form-item>
                    <el-form-item label="报名截止" prop="activity_signup_deadline">
                        <el-date-picker
                            v-model="activityDeadlineDate"
                            type="datetime"
                            value-format="YYYY-MM-DD HH:mm:ss"
                            placeholder="请选择报名截止时间"
                        />
                    </el-form-item>
                    <el-form-item label="开启报名" prop="activity_signup_enabled">
                        <el-switch
                            v-model="formData.activity_signup_enabled"
                            :active-value="1"
                            :inactive-value="0"
                        />
                    </el-form-item>
                    <el-form-item label="总名额" prop="activity_total_quota">
                        <el-input-number
                            v-model="formData.activity_total_quota"
                            :min="0"
                            :precision="0"
                        />
                        <div class="form-tips">0 表示不限总名额，报名仍会校验票种库存。</div>
                    </el-form-item>
                    <el-form-item label="票种" prop="activity_tickets">
                        <div class="ticket-editor">
                            <div
                                v-for="(ticket, index) in formData.activity_tickets"
                                :key="index"
                                class="ticket-editor__row"
                            >
                                <div class="ticket-editor__main">
                                    <el-input v-model="ticket.name" placeholder="票种名称" />
                                    <el-input-number
                                        v-model="ticket.price"
                                        :min="0"
                                        :precision="2"
                                        placeholder="价格"
                                    />
                                    <el-input-number
                                        v-model="ticket.stock"
                                        :min="0"
                                        :precision="0"
                                        placeholder="库存"
                                    />
                                    <el-switch
                                        v-model="ticket.status"
                                        :active-value="1"
                                        :inactive-value="0"
                                    />
                                    <el-button type="danger" link @click="removeTicket(index)">
                                        删除
                                    </el-button>
                                </div>
                                <div class="ticket-editor__sale">
                                    <span class="ticket-editor__label">可购买时限</span>
                                    <el-date-picker
                                        :model-value="timestampToDateTime(ticket.sale_start_time)"
                                        type="datetime"
                                        value-format="YYYY-MM-DD HH:mm:ss"
                                        placeholder="开始时间，不填则不限"
                                        @update:model-value="
                                            (value) =>
                                                (ticket.sale_start_time = dateTimeToTimestamp(String(value || '')))
                                        "
                                    />
                                    <span class="ticket-editor__separator">至</span>
                                    <el-date-picker
                                        :model-value="timestampToDateTime(ticket.sale_end_time)"
                                        type="datetime"
                                        value-format="YYYY-MM-DD HH:mm:ss"
                                        placeholder="结束时间，不填则跟随活动截止"
                                        @update:model-value="
                                            (value) =>
                                                (ticket.sale_end_time = dateTimeToTimestamp(String(value || '')))
                                        "
                                    />
                                </div>
                            </div>
                            <el-button type="primary" plain @click="addTicket">添加票种</el-button>
                        </div>
                    </el-form-item>
                </template>

                <el-form-item label="状态" prop="status" v-if="mode === 'edit'">
                    <el-radio-group v-model="formData.status">
                        <el-radio :label="0">待审核</el-radio>
                        <el-radio :label="1">已发布</el-radio>
                        <el-radio :label="2">已下架</el-radio>
                    </el-radio-group>
                </el-form-item>
            </el-form>
        </popup>
    </div>
</template>

<script lang="ts" setup name="dynamicEdit">
import type { FormInstance } from 'element-plus'
import Popup from '@/components/popup/index.vue'
import { dynamicAdd, dynamicEdit, dynamicDetail } from '@/api/dynamic'
import feedback from '@/utils/feedback'
import type {
    ActivityTicketForm,
    DynamicFormData,
    DynamicItem,
    NumericValue
} from '@/types/dynamic'

const emit = defineEmits(['success', 'close'])

const formRef = shallowRef<FormInstance>()
const popupRef = shallowRef<InstanceType<typeof Popup>>()
type EditMode = 'add' | 'edit'

const mode = ref<EditMode>('add')
const popupTitle = computed(() => (mode.value === 'edit' ? '编辑动态' : '发布动态'))

const tagInputVisible = ref(false)
const tagInputValue = ref('')
const tagInputRef = ref()

const formData = reactive<DynamicFormData>({
    id: 0,
    dynamic_type: 1,
    title: '',
    content: '',
    images: [] as string[],
    video: '',
    video_cover: '',
    location: '',
    tags: [] as string[],
    allow_comment: 1,
    status: 1,
    activity_start_time: 0,
    activity_signup_deadline: 0,
    activity_signup_enabled: 0,
    activity_total_quota: 0,
    activity_tickets: []
})

const activityStartDate = computed({
    get: () => timestampToDateTime(formData.activity_start_time),
    set: (value: string) => {
        formData.activity_start_time = dateTimeToTimestamp(value)
    }
})

const activityDeadlineDate = computed({
    get: () => timestampToDateTime(formData.activity_signup_deadline),
    set: (value: string) => {
        formData.activity_signup_deadline = dateTimeToTimestamp(value)
    }
})

const formRules = {
    dynamic_type: [{ required: true, message: '请选择动态类型', trigger: 'change' }],
    content: [{ required: true, message: '请输入动态内容', trigger: 'blur' }]
}

const getDefaultFormData = (): DynamicFormData => ({
    id: 0,
    dynamic_type: 1,
    title: '',
    content: '',
    images: [],
    video: '',
    video_cover: '',
    location: '',
    tags: [],
    allow_comment: 1,
    status: 1,
    activity_start_time: 0,
    activity_signup_deadline: 0,
    activity_signup_enabled: 0,
    activity_total_quota: 0,
    activity_tickets: []
})

const normalizeStringList = (value: unknown) => {
    if (Array.isArray(value)) {
        return value.filter((item) => String(item || '').trim())
    }
    if (typeof value === 'string') {
        return value
            .split(',')
            .map((item) => item.trim())
            .filter(Boolean)
    }
    return []
}

const normalizeTicket = (
    ticket: Partial<ActivityTicketForm>,
    index: number
): ActivityTicketForm => ({
    id: ticket.id,
    name: String(ticket.name || ''),
    price: Number(ticket.price || 0),
    stock: Number(ticket.stock || 0),
    sale_start_time: Number(ticket.sale_start_time || 0),
    sale_end_time: Number(ticket.sale_end_time || 0),
    status: Number(ticket.status ?? 1),
    sort: Number(ticket.sort || index + 1)
})

const handleRemoveTag = (index: number) => {
    formData.tags.splice(index, 1)
}

const showTagInput = () => {
    tagInputVisible.value = true
    nextTick(() => {
        tagInputRef.value?.focus()
    })
}

const handleAddTag = () => {
    const tag = tagInputValue.value.trim()
    if (tag && !formData.tags.includes(tag)) {
        if (formData.tags.length >= 5) {
            feedback.msgWarning('最多添加5个标签')
            tagInputValue.value = ''
            tagInputVisible.value = false
            return
        }
        formData.tags.push(tag)
    }
    tagInputValue.value = ''
    tagInputVisible.value = false
}

const addTicket = () => {
    formData.activity_tickets.push({
        name: '',
        price: 0,
        stock: 0,
        sale_start_time: 0,
        sale_end_time: 0,
        status: 1,
        sort: formData.activity_tickets.length + 1
    })
}

const removeTicket = (index: number) => {
    formData.activity_tickets.splice(index, 1)
}

const timestampToDateTime = (timestamp: number) => {
    if (!timestamp) return ''
    const date = new Date(Number(timestamp) * 1000)
    const pad = (num: number) => String(num).padStart(2, '0')
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(
        date.getHours()
    )}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`
}

const dateTimeToTimestamp = (value: string) => {
    if (!value) return 0
    return Math.floor(new Date(value.replace(/-/g, '/')).getTime() / 1000)
}

const handleSubmit = async () => {
    await formRef.value?.validate()
    const activityError = validateActivityForm()
    if (activityError) {
        feedback.msgError(activityError)
        return
    }
    
    const params: DynamicFormData = {
        ...formData,
        images: formData.dynamic_type === 2 ? [] : [...formData.images],
        video: formData.dynamic_type === 2 ? formData.video : '',
        video_cover: formData.dynamic_type === 2 ? formData.video_cover : '',
        tags: [...formData.tags],
        activity_tickets: formData.activity_tickets.map((ticket, index) =>
            normalizeTicket(ticket, index)
        )
    }
    if (Number(formData.dynamic_type) !== 4) {
        Object.assign(params, {
            activity_start_time: 0,
            activity_signup_deadline: 0,
            activity_signup_enabled: 0,
            activity_total_quota: 0,
            activity_tickets: []
        })
    }

    if (mode.value === 'edit') {
        await dynamicEdit(params)
        feedback.msgSuccess('编辑成功')
    } else {
        await dynamicAdd(params)
        feedback.msgSuccess('发布成功')
    }
    popupRef.value?.close()
    emit('success')
}

const handleClose = () => {
    emit('close')
}

const validateActivityForm = () => {
    if (Number(formData.dynamic_type) !== 4) {
        return ''
    }
    if (!formData.activity_start_time) {
        return '请选择活动开始时间'
    }
    if (Number(formData.activity_signup_enabled) === 1 && formData.activity_tickets.length === 0) {
        return '请至少配置一个活动票种'
    }
    for (const [index, ticket] of formData.activity_tickets.entries()) {
        if (!String(ticket.name || '').trim()) {
            return `请填写第 ${index + 1} 个票种名称`
        }
        if (Number(ticket.price || 0) < 0) {
            return `第 ${index + 1} 个票种价格不能小于0`
        }
        if (Number(ticket.stock || 0) < 0) {
            return `第 ${index + 1} 个票种库存不能小于0`
        }
        const saleStartTime = Number(ticket.sale_start_time || 0)
        const saleEndTime = Number(ticket.sale_end_time || 0)
        if (saleStartTime < 0 || saleEndTime < 0) {
            return `第 ${index + 1} 个票种可购买时间格式错误`
        }
        if (saleStartTime > 0 && saleEndTime > 0 && saleEndTime <= saleStartTime) {
            return `第 ${index + 1} 个票种可购买结束时间必须晚于开始时间`
        }
    }
    return ''
}

const resetFormData = () => {
    Object.assign(formData, getDefaultFormData())
}

const open = (type: EditMode = 'add') => {
    mode.value = type
    resetFormData()
    
    popupRef.value?.open()
}

const setFormData = (data: DynamicItem) => {
    resetFormData()
    Object.assign(formData, {
        id: data.id ?? 0,
        dynamic_type: Number(data.dynamic_type ?? formData.dynamic_type),
        title: String(data.title || ''),
        content: String(data.content || ''),
        images: normalizeStringList(data.images),
        video: String(data.video || ''),
        video_cover: String(data.video_cover || ''),
        location: String(data.location || ''),
        tags: normalizeStringList(data.tags),
        allow_comment: Number(data.allow_comment ?? 1),
        status: Number(data.status ?? 1),
        activity_start_time: Number(data.activity_start_time || 0),
        activity_signup_deadline: Number(data.activity_signup_deadline || 0),
        activity_signup_enabled: Number(data.activity_signup_enabled || 0),
        activity_total_quota: Number(data.activity_total_quota || 0)
    })
    formData.activity_tickets = Array.isArray(data.activity_tickets)
        ? data.activity_tickets.map((ticket, index) => normalizeTicket(ticket, index))
        : []
}

const getDetail = async (row: { id: NumericValue }) => {
    const data = await dynamicDetail({ id: row.id })
    setFormData(data)
}

defineExpose({
    open,
    setFormData,
    getDetail
})
</script>

<style lang="scss" scoped>
.form-tips {
    font-size: 12px;
    color: var(--el-text-color-secondary);
    line-height: 1.5;
}

.ticket-editor {
    width: 100%;
}

.ticket-editor__row {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 12px;
    border: 1px solid var(--el-border-color-light);
    border-radius: 8px;
    margin-bottom: 10px;
}

.ticket-editor__main {
    display: grid;
    grid-template-columns: 1fr 160px 140px 90px 60px;
    gap: 10px;
    align-items: center;
}

.ticket-editor__sale {
    display: grid;
    grid-template-columns: 86px 220px 24px 220px;
    gap: 10px;
    align-items: center;
}

.ticket-editor__label,
.ticket-editor__separator {
    color: var(--el-text-color-secondary);
    font-size: 12px;
}

.ticket-editor__separator {
    text-align: center;
}
</style>
