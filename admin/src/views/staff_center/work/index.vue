<template>
    <admin-page-shell class="staff-center-work" title="我的作品">
        <template #search>
            <search-panel>
                <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                    <el-form-item class="w-[220px]" label="作品标题">
                        <el-input v-model="queryParams.title" placeholder="输入作品标题" clearable @keyup.enter="resetPage" />
                    </el-form-item>
                    <el-form-item class="w-[160px]" label="作品类型">
                        <el-select v-model="queryParams.type" placeholder="选择类型" clearable>
                            <el-option label="图片" :value="1" />
                            <el-option label="视频" :value="2" />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[160px]" label="审核状态">
                        <el-select v-model="queryParams.audit_status" placeholder="选择状态" clearable>
                            <el-option label="待审核" :value="0" />
                            <el-option label="已通过" :value="1" />
                            <el-option label="已拒绝" :value="2" />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[160px]" label="显示状态">
                        <el-select v-model="queryParams.is_show" placeholder="选择状态" clearable>
                            <el-option label="显示" :value="1" />
                            <el-option label="隐藏" :value="0" />
                        </el-select>
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" @click="resetPage">查询</el-button>
                        <el-button @click="resetParams">重置</el-button>
                        <el-button type="primary" @click="openForm()">
                            <template #icon>
                                <icon name="el-icon-Plus" />
                            </template>
                            新增作品
                        </el-button>
                    </el-form-item>
                </el-form>
            </search-panel>
        </template>

        <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-4">
            <el-card v-for="card in statusCards" :key="card.key" class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">{{ card.label }}</div>
                    <div class="text-2xl font-bold mt-2" :class="card.className">{{ card.count }}</div>
                </div>
            </el-card>
        </div>

        <el-card class="!border-none mt-4" shadow="never">
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="封面" width="100">
                    <template #default="{ row }">
                        <el-image
                            v-if="getWorkCover(row)"
                            :src="getWorkCover(row)"
                            fit="cover"
                            class="w-[64px] h-[64px] rounded"
                            :preview-src-list="getPreviewImages(row)"
                        />
                        <span v-else class="text-gray-400">未上传</span>
                    </template>
                </el-table-column>
                <el-table-column label="标题" prop="title" min-width="180" show-overflow-tooltip />
                <el-table-column label="类型" width="90">
                    <template #default="{ row }">
                        <el-tag>{{ row.type_desc || getWorkTypeText(row.type) }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="审核状态" width="110">
                    <template #default="{ row }">
                        <el-tag :type="getAuditTagType(Number(row.audit_status))">
                            {{ row.audit_status_desc || getAuditStatusText(row.audit_status) }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="显示" width="90">
                    <template #default="{ row }">
                        <el-switch
                            v-model="row.is_show"
                            :active-value="1"
                            :inactive-value="0"
                            @change="handleChangeStatus($event, row)"
                        />
                    </template>
                </el-table-column>
                <el-table-column label="封面作品" width="100">
                    <template #default="{ row }">
                        <el-tag v-if="Number(row.is_cover) === 1" type="success">是</el-tag>
                        <el-tag v-else type="info">否</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="排序" prop="sort" width="80" />
                <el-table-column label="互动" width="150">
                    <template #default="{ row }">
                        <div class="text-xs text-gray-500">浏览 {{ row.view_count || 0 }}</div>
                        <div class="text-xs text-gray-500">点赞 {{ row.like_count || 0 }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="创建时间" prop="create_time" width="170" />
                <el-table-column label="操作" width="230" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="openDetail(row)">详情</el-button>
                        <el-button type="primary" link @click="openForm(row.id)">编辑</el-button>
                        <el-button v-if="Number(row.is_cover) !== 1" type="warning" link @click="handleSetCover(row)">设为封面</el-button>
                        <el-button type="danger" link @click="handleDelete(row)">删除</el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <el-dialog v-model="detailVisible" title="作品详情" width="780px">
            <el-descriptions v-if="detailData" :column="2" border>
                <el-descriptions-item label="作品标题">{{ detailData.title || '-' }}</el-descriptions-item>
                <el-descriptions-item label="作品类型">{{ getWorkTypeText(detailData.type) }}</el-descriptions-item>
                <el-descriptions-item label="审核状态">
                    <el-tag :type="getAuditTagType(Number(detailData.audit_status))">
                        {{ detailData.audit_status_desc || getAuditStatusText(detailData.audit_status) }}
                    </el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="显示状态">{{ Number(detailData.is_show) === 1 ? '显示' : '隐藏' }}</el-descriptions-item>
                <el-descriptions-item label="拍摄日期">{{ detailData.shoot_date || '-' }}</el-descriptions-item>
                <el-descriptions-item label="拍摄地点">{{ detailData.location || '-' }}</el-descriptions-item>
                <el-descriptions-item label="排序">{{ detailData.sort || 0 }}</el-descriptions-item>
                <el-descriptions-item label="封面作品">{{ Number(detailData.is_cover) === 1 ? '是' : '否' }}</el-descriptions-item>
                <el-descriptions-item label="作品描述" :span="2">
                    <div class="whitespace-pre-wrap">{{ detailData.description || '-' }}</div>
                </el-descriptions-item>
                <el-descriptions-item label="作品图片" :span="2">
                    <div v-if="getPreviewImages(detailData).length" class="flex flex-wrap gap-2">
                        <el-image
                            v-for="(image, index) in getPreviewImages(detailData)"
                            :key="`${image}-${index}`"
                            :src="image"
                            fit="cover"
                            class="w-[112px] h-[84px] rounded"
                            :preview-src-list="getPreviewImages(detailData)"
                            :initial-index="index"
                        />
                    </div>
                    <span v-else class="text-gray-400">未上传</span>
                </el-descriptions-item>
                <el-descriptions-item v-if="detailData.video" label="作品视频" :span="2">
                    <el-link :href="detailData.video" target="_blank" type="primary">查看视频</el-link>
                </el-descriptions-item>
            </el-descriptions>
        </el-dialog>

        <el-dialog v-model="formVisible" :title="formMode === 'add' ? '新增作品' : '编辑作品'" width="760px" @closed="clearFormValidate">
            <el-form ref="formRef" :model="formData" :rules="formRules" label-width="100px">
                <el-form-item label="作品标题" prop="title">
                    <el-input v-model="formData.title" maxlength="100" placeholder="输入作品标题" />
                </el-form-item>
                <el-form-item label="作品类型" prop="type">
                    <el-radio-group v-model="formData.type">
                        <el-radio :value="1">图片</el-radio>
                        <el-radio :value="2">视频</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item label="封面图片" prop="cover">
                    <material-picker v-model="formData.cover" type="image" :limit="1" />
                </el-form-item>
                <el-form-item label="作品图片" prop="images">
                    <material-picker v-model="formData.images" type="image" :limit="9" />
                </el-form-item>
                <el-form-item v-if="formData.type === 2" label="作品视频" prop="video">
                    <material-picker v-model="formData.video" type="video" :limit="1" />
                </el-form-item>
                <el-form-item label="拍摄日期" prop="shoot_date">
                    <el-date-picker v-model="formData.shoot_date" type="date" value-format="YYYY-MM-DD" placeholder="选择拍摄日期" />
                </el-form-item>
                <el-form-item label="拍摄地点" prop="location">
                    <el-input v-model="formData.location" maxlength="100" placeholder="输入拍摄地点" />
                </el-form-item>
                <el-form-item label="排序" prop="sort">
                    <el-input-number v-model="formData.sort" :min="0" class="w-full" />
                </el-form-item>
                <el-form-item label="显示状态" prop="is_show">
                    <el-radio-group v-model="formData.is_show">
                        <el-radio :value="1">显示</el-radio>
                        <el-radio :value="0">隐藏</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item label="作品描述" prop="description">
                    <el-input v-model="formData.description" type="textarea" :rows="4" maxlength="500" show-word-limit placeholder="输入作品描述" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="formVisible = false">取消</el-button>
                <el-button type="primary" :loading="formLoading" @click="handleSubmitForm">保存</el-button>
            </template>
        </el-dialog>
    </admin-page-shell>
</template>

<script setup lang="ts" name="staffCenterWork">
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage } from 'element-plus'
import { computed, nextTick, onActivated, reactive, ref } from 'vue'
import {
    myProfile,
} from '@/api/staff-center'
import {
    staffWorkAdd,
    staffWorkChangeStatus,
    staffWorkDelete,
    staffWorkDetail,
    staffWorkEdit,
    staffWorkLists,
    staffWorkSetCover,
} from '@/api/staff'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'
import MaterialPicker from '@/components/material/picker.vue'

const formRef = ref<FormInstance>()
const currentStaffId = ref(0)
const detailVisible = ref(false)
const detailData = ref<any>(null)
const formVisible = ref(false)
const formLoading = ref(false)
const formMode = ref<'add' | 'edit'>('add')

const queryParams = reactive({
    staff_id: 0,
    title: '',
    type: '',
    audit_status: '',
    is_show: '',
})

const formData = reactive({
    id: 0,
    title: '',
    type: 1,
    cover: '',
    images: [] as string[],
    video: '',
    description: '',
    shoot_date: '',
    location: '',
    sort: 0,
    is_show: 1,
})

const formRules: FormRules = {
    title: [{ required: true, message: '请输入作品标题', trigger: 'blur' }],
}

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: staffWorkLists,
    params: queryParams,
})

const statusCards = computed(() => {
    const lists = pager.lists || []
    return [
        { key: 'total', label: '当前页作品', count: lists.length, className: 'text-gray-900' },
        { key: 'pending', label: '待审核', count: lists.filter((item) => Number(item.audit_status) === 0).length, className: 'text-orange-500' },
        { key: 'passed', label: '已通过', count: lists.filter((item) => Number(item.audit_status) === 1).length, className: 'text-green-500' },
        { key: 'rejected', label: '已拒绝', count: lists.filter((item) => Number(item.audit_status) === 2).length, className: 'text-red-500' },
    ]
})

const getAuditTagType = (status: number) => {
    const map = {
        0: 'warning',
        1: 'success',
        2: 'danger',
    } as const
    return map[status as keyof typeof map] ?? 'info'
}

const getAuditStatusText = (status: unknown) => {
    const map: Record<number, string> = {
        0: '待审核',
        1: '已通过',
        2: '已拒绝',
    }
    return map[Number(status)] || '-'
}

const getWorkTypeText = (type: unknown) => {
    return Number(type) === 2 ? '视频' : '图片'
}

const getPreviewImages = (row: Record<string, any>) => {
    const images = Array.isArray(row?.images) ? row.images.filter(Boolean) : []
    const cover = String(row?.cover || '')
    return cover ? [cover, ...images.filter((item) => item !== cover)] : images
}

const getWorkCover = (row: Record<string, any>) => {
    return String(row?.cover || row?.images?.[0] || '')
}

const resetFormData = () => {
    formData.id = 0
    formData.title = ''
    formData.type = 1
    formData.cover = ''
    formData.images = []
    formData.video = ''
    formData.description = ''
    formData.shoot_date = ''
    formData.location = ''
    formData.sort = 0
    formData.is_show = 1
}

const loadCurrentStaff = async () => {
    const profile = await myProfile()
    currentStaffId.value = Number(profile?.id || profile?.staff_id || 0)
    queryParams.staff_id = currentStaffId.value
}

const openDetail = async (row: Record<string, any>) => {
    detailData.value = await staffWorkDetail({ id: row.id })
    detailVisible.value = true
}

const openForm = async (id = 0) => {
    resetFormData()
    formMode.value = id > 0 ? 'edit' : 'add'

    if (id > 0) {
        const detail = await staffWorkDetail({ id })
        formData.id = Number(detail.id || 0)
        formData.title = detail.title || ''
        formData.type = Number(detail.type || 1)
        formData.cover = detail.cover || ''
        formData.images = Array.isArray(detail.images) ? detail.images : []
        formData.video = detail.video || ''
        formData.description = detail.description || ''
        formData.shoot_date = detail.shoot_date || ''
        formData.location = detail.location || ''
        formData.sort = Number(detail.sort || 0)
        formData.is_show = Number(detail.is_show ?? 1)
    }

    formVisible.value = true
}

const handleSubmitForm = async () => {
    await formRef.value?.validate()

    if (!currentStaffId.value) {
        ElMessage.error('当前后台账号未关联服务人员档案')
        return
    }

    formLoading.value = true
    try {
        const payload = {
            id: formData.id,
            staff_id: currentStaffId.value,
            title: formData.title.trim(),
            type: formData.type,
            cover: formData.cover,
            images: formData.images,
            video: formData.type === 2 ? formData.video : '',
            video_url: formData.type === 2 ? formData.video : '',
            description: formData.description.trim(),
            shoot_date: formData.shoot_date,
            location: formData.location.trim(),
            sort: Number(formData.sort || 0),
            is_show: Number(formData.is_show),
            is_cover: 0,
        }

        if (formMode.value === 'add') {
            await staffWorkAdd(payload)
        } else {
            await staffWorkEdit(payload)
        }

        ElMessage.success(formMode.value === 'add' ? '新增成功，作品已提交审核' : '编辑成功，作品已重新提交审核')
        formVisible.value = false
        getLists()
    } finally {
        formLoading.value = false
    }
}

const handleChangeStatus = async (status: string | number | boolean, row: Record<string, any>) => {
    try {
        await staffWorkChangeStatus({ id: row.id, is_show: Number(status) })
        ElMessage.success('显示状态已更新')
        getLists()
    } catch (error) {
        getLists()
    }
}

const handleSetCover = async (row: Record<string, any>) => {
    await feedback.confirm('确认将该作品设为封面？')
    await staffWorkSetCover({ id: row.id })
    ElMessage.success('封面作品已更新')
    getLists()
}

const handleDelete = async (row: Record<string, any>) => {
    await feedback.confirm('确定要删除该作品吗？')
    await staffWorkDelete({ id: row.id })
    ElMessage.success('删除成功')
    getLists()
}

const clearFormValidate = () => {
    nextTick(() => {
        formRef.value?.clearValidate()
    })
}

const bootstrap = async () => {
    await loadCurrentStaff()
    getLists()
}

onActivated(() => {
    if (currentStaffId.value) {
        getLists()
    }
})

bootstrap()
</script>
