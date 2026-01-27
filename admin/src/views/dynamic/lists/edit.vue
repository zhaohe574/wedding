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
                        <el-radio :label="3">案例</el-radio>
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

const emit = defineEmits(['success', 'close'])

const formRef = shallowRef<FormInstance>()
const popupRef = shallowRef<InstanceType<typeof Popup>>()
const mode = ref('add')
const popupTitle = computed(() => (mode.value === 'edit' ? '编辑动态' : '发布动态'))

const tagInputVisible = ref(false)
const tagInputValue = ref('')
const tagInputRef = ref()

const formData = reactive({
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
    status: 1
})

const formRules = {
    dynamic_type: [{ required: true, message: '请选择动态类型', trigger: 'change' }],
    content: [{ required: true, message: '请输入动态内容', trigger: 'blur' }]
}

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

const handleSubmit = async () => {
    await formRef.value?.validate()
    
    const params = {
        ...formData,
        images: formData.dynamic_type === 2 ? [] : formData.images,
        video: formData.dynamic_type === 2 ? formData.video : '',
        video_cover: formData.dynamic_type === 2 ? formData.video_cover : ''
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

const open = (type = 'add') => {
    mode.value = type
    
    // 重置表单数据
    if (type === 'add') {
        Object.assign(formData, {
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
            status: 1
        })
    }
    
    popupRef.value?.open()
}

const setFormData = async (data: any) => {
    for (const key in formData) {
        if (data[key] != null && data[key] != undefined) {
            //@ts-ignore
            formData[key] = data[key]
        }
    }
    
    // 处理标签
    if (data.tags && typeof data.tags === 'string') {
        formData.tags = data.tags.split(',').filter((t: string) => t.trim())
    }
    
    // 确保 allow_comment 有默认值
    if (data.allow_comment === undefined || data.allow_comment === null) {
        formData.allow_comment = 1
    }
}

const getDetail = async (row: Record<string, any>) => {
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
</style>
