<template>
    <div class="staff-center-dynamic-edit">
        <popup
            ref="popupRef"
            :title="popupTitle"
            :async="true"
            width="800px"
            @confirm="handleSubmit"
            @close="emit('close')"
        >
            <el-form ref="formRef" :model="formData" :rules="formRules" label-width="100px">
                <el-form-item label="动态类型" prop="dynamic_type">
                    <el-radio-group v-model="formData.dynamic_type">
                        <el-radio :label="1">图文</el-radio>
                        <el-radio :label="2">视频</el-radio>
                        <el-radio :label="4">活动</el-radio>
                    </el-radio-group>
                </el-form-item>

                <el-form-item label="标题">
                    <el-input v-model="formData.title" placeholder="请输入标题（可选）" maxlength="100" show-word-limit />
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

                <el-form-item v-if="formData.dynamic_type !== 2" label="图片">
                    <material-picker v-model="formData.images" :limit="9" type="image" />
                    <div class="form-tips">最多上传9张图片</div>
                </el-form-item>

                <el-form-item v-if="formData.dynamic_type === 2" label="视频">
                    <material-picker v-model="formData.video" :limit="1" type="video" />
                </el-form-item>

                <el-form-item v-if="formData.dynamic_type === 2 && formData.video" label="视频封面">
                    <material-picker v-model="formData.video_cover" :limit="1" type="image" />
                </el-form-item>

                <el-form-item label="位置">
                    <el-input v-model="formData.location" placeholder="请输入位置（可选）" maxlength="100" />
                </el-form-item>

                <el-form-item label="标签">
                    <div class="flex flex-wrap gap-2 mb-2">
                        <el-tag
                            v-for="(tag, index) in formData.tags"
                            :key="index"
                            closable
                            @close="formData.tags.splice(index, 1)"
                        >
                            {{ tag }}
                        </el-tag>
                        <el-input
                            v-if="tagInputVisible"
                            ref="tagInputRef"
                            v-model="tagInputValue"
                            size="small"
                            style="width: 120px"
                            @keyup.enter="handleAddTag"
                            @blur="handleAddTag"
                        />
                        <el-button v-else size="small" @click="showTagInput">+ 添加标签</el-button>
                    </div>
                    <div class="form-tips">最多添加5个标签</div>
                </el-form-item>

                <el-form-item label="允许评论">
                    <el-switch
                        v-model="formData.allow_comment"
                        :active-value="1"
                        :inactive-value="0"
                        active-text="允许"
                        inactive-text="禁止"
                    />
                </el-form-item>
            </el-form>
        </popup>
    </div>
</template>

<script setup lang="ts" name="staffCenterDynamicEdit">
import { computed, nextTick, reactive, ref, shallowRef } from 'vue'
import type { FormInstance } from 'element-plus'
import Popup from '@/components/popup/index.vue'
import feedback from '@/utils/feedback'
import { myDynamicAdd, myDynamicDetail, myDynamicEdit } from '@/api/staff-center'

const emit = defineEmits(['success', 'close'])

const formRef = shallowRef<FormInstance>()
const popupRef = shallowRef<InstanceType<typeof Popup>>()
const mode = ref<'add' | 'edit'>('add')
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
    allow_comment: 1
})

const formRules = {
    dynamic_type: [{ required: true, message: '请选择动态类型', trigger: 'change' }],
    content: [{ required: true, message: '请输入动态内容', trigger: 'blur' }]
}

const resetForm = () => {
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
        allow_comment: 1
    })
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
        } else {
            formData.tags.push(tag)
        }
    }
    tagInputValue.value = ''
    tagInputVisible.value = false
}

const handleSubmit = async () => {
    await formRef.value?.validate()
    const payload = {
        ...formData,
        images: formData.dynamic_type === 2 ? [] : formData.images,
        video: formData.dynamic_type === 2 ? formData.video : '',
        video_cover: formData.dynamic_type === 2 ? formData.video_cover : ''
    }

    if (mode.value === 'edit') {
        await myDynamicEdit(payload)
        feedback.msgSuccess('编辑成功')
    } else {
        await myDynamicAdd(payload)
        feedback.msgSuccess('发布成功')
    }
    popupRef.value?.close()
    emit('success')
}

const open = (type: 'add' | 'edit' = 'add') => {
    mode.value = type
    if (type === 'add') {
        resetForm()
    }
    popupRef.value?.open()
}

const getDetail = async (row: Record<string, any>) => {
    const data = await myDynamicDetail({ id: row.id })
    resetForm()
    Object.keys(formData).forEach((key) => {
        if (data[key] !== undefined && data[key] !== null) {
            ;(formData as any)[key] = data[key]
        }
    })
    if (typeof data.tags === 'string') {
        formData.tags = data.tags.split(',').filter((item: string) => item.trim())
    }
    if (data.allow_comment === undefined || data.allow_comment === null) {
        formData.allow_comment = 1
    }
}

defineExpose({
    open,
    getDetail
})
</script>

<style scoped>
.form-tips {
    font-size: 12px;
    color: var(--el-text-color-secondary);
    line-height: 1.5;
}
</style>
