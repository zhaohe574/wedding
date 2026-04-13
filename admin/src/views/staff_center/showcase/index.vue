<template>
    <div class="staff-center-showcase admin-edit-page">
        <el-card class="admin-edit-head !border-none" shadow="never">
            <div class="admin-edit-head__top">
                <div>
                    <h1 class="admin-edit-title">服务展示</h1>
                    <div class="admin-edit-head__desc">这里维护对外展示内容，包括服务说明、标签和轮播素材。</div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <el-button @click="router.push('/staff-center/profile')">基本资料</el-button>
                    <el-button type="primary" plain @click="router.push('/staff-center/package')">我的套餐</el-button>
                </div>
            </div>
        </el-card>

        <el-card class="mt-4 !border-none admin-edit-main" shadow="never">
            <el-form ref="formRef" class="ls-form" :model="formData" label-width="100px">
                <div class="staff-center-showcase__quick-links">
                    <el-alert
                        title="作品和证书仍沿用原后台页管理，这里提供快捷入口，避免把自助展示和平台审核混在同一个页面里。"
                        type="info"
                        :closable="false"
                        show-icon
                    />
                    <div class="flex flex-wrap gap-2 mt-3">
                        <el-button @click="router.push('/staff_work/lists')">作品管理</el-button>
                        <el-button @click="router.push('/service/certificate')">证书管理</el-button>
                    </div>
                </div>

                <div class="admin-edit-section mt-4">
                    <div class="admin-edit-section__header">
                        <div class="admin-edit-section__title">展示基础</div>
                    </div>
                    <div class="grid staff-edit-grid gap-x-8">
                        <el-form-item label="服务分类">
                            <el-input :model-value="formData.category_name || '未设置'" disabled class="w-full" />
                        </el-form-item>
                        <el-form-item label="服务价格">
                            <el-input :model-value="formData.price_text || '未设置'" disabled class="w-full" />
                        </el-form-item>
                        <el-form-item label="状态">
                            <el-radio-group v-model="formData.status" disabled>
                                <el-radio :value="1">启用</el-radio>
                                <el-radio :value="0">禁用</el-radio>
                            </el-radio-group>
                        </el-form-item>
                        <el-form-item label="是否推荐">
                            <el-radio-group v-model="formData.is_recommend" disabled>
                                <el-radio :value="1">是</el-radio>
                                <el-radio :value="0">否</el-radio>
                            </el-radio-group>
                        </el-form-item>
                    </div>
                </div>

                <div class="admin-edit-section mt-4">
                    <div class="admin-edit-section__header">
                        <div class="admin-edit-section__title">展示文案</div>
                    </div>
                    <el-form-item label="个人简介">
                        <el-input v-model="formData.profile" type="textarea" :rows="3" maxlength="500" show-word-limit />
                    </el-form-item>
                    <el-form-item label="服务说明" class="!mb-0">
                        <el-input v-model="formData.service_desc" type="textarea" :rows="3" maxlength="1000" show-word-limit />
                    </el-form-item>
                    <el-form-item label="长图详情" class="mt-4 !mb-0">
                        <long-detail-editor v-model="formData.long_detail" />
                    </el-form-item>
                </div>

                <div class="admin-edit-section mt-4">
                    <div class="admin-edit-section__header">
                        <div class="admin-edit-section__title">标签设置</div>
                    </div>
                    <el-alert class="mb-4" :title="tagReviewAlert.title" :type="tagReviewAlert.type" :closable="false" show-icon />
                    <el-form-item class="staff-tag-form-item !mb-0" label="风格标签">
                        <el-checkbox-group v-model="formData.tag_ids" class="staff-tag-groups">
                            <template v-if="Object.keys(groupedTags).length">
                                <div v-for="(tags, type) in groupedTags" :key="type" class="staff-tag-block">
                                    <div class="staff-tag-block__title">{{ tagTypeLabelMap[Number(type)] || type }}</div>
                                    <div class="staff-tag-block__items">
                                        <el-checkbox v-for="tag in tags" :key="tag.id" :value="tag.id" :label="tag.name" />
                                    </div>
                                </div>
                            </template>
                            <div v-else class="admin-edit-muted">请选择服务分类后再配置标签。</div>
                        </el-checkbox-group>
                    </el-form-item>
                </div>

                <div class="admin-edit-section mt-4">
                    <div class="admin-edit-toolbar">
                        <div>
                            <div class="admin-edit-section__title">轮播基础配置</div>
                        </div>
                        <el-button type="primary" @click="saveBannerConfig">保存配置</el-button>
                    </div>
                    <div class="grid staff-edit-grid gap-x-8 mt-4">
                        <el-form-item label="展示模式">
                            <el-radio-group v-model="bannerConfig.banner_mode">
                                <el-radio :value="1">小图模式</el-radio>
                                <el-radio :value="2">大图模式</el-radio>
                            </el-radio-group>
                        </el-form-item>
                        <el-form-item label="指示器样式">
                            <el-select v-model="bannerConfig.banner_indicator_style" class="w-full">
                                <el-option label="圆点" :value="1" />
                                <el-option label="数字" :value="2" />
                                <el-option label="进度条" :value="3" />
                                <el-option label="长条形" :value="4" />
                                <el-option label="无" :value="0" />
                            </el-select>
                        </el-form-item>
                        <el-form-item v-if="bannerConfig.banner_mode === 1" label="小图初始高度">
                            <el-input-number v-model="bannerConfig.banner_small_height" :min="200" :max="800" class="w-full" />
                        </el-form-item>
                        <el-form-item :label="bannerConfig.banner_mode === 1 ? '展开后高度' : '固定高度'">
                            <el-input-number v-model="bannerConfig.banner_large_height" :min="300" class="w-full" />
                        </el-form-item>
                        <el-form-item label="自动轮播">
                            <el-switch v-model="bannerConfig.banner_autoplay" :active-value="1" :inactive-value="0" />
                        </el-form-item>
                        <el-form-item v-if="bannerConfig.banner_autoplay === 1" label="轮播间隔">
                            <el-input-number v-model="bannerConfig.banner_interval" :min="1000" :max="10000" :step="500" class="w-full" />
                        </el-form-item>
                    </div>
                </div>

                <div class="admin-edit-section mt-4">
                    <div class="admin-edit-toolbar">
                        <div>
                            <div class="admin-edit-section__title">轮播图列表</div>
                        </div>
                        <el-button type="primary" @click="openAddBanner">添加轮播图</el-button>
                    </div>
                    <div class="staff-table-card mt-4">
                        <el-table :data="bannerList" border row-key="id">
                            <el-table-column label="排序" width="60">
                                <template #default="{ $index }">{{ $index + 1 }}</template>
                            </el-table-column>
                            <el-table-column label="预览" width="120">
                                <template #default="{ row }">
                                    <el-image v-if="Number(row.type) === 1" :src="row.file_url" fit="cover" style="width: 80px; height: 60px" :preview-src-list="[row.file_url]" />
                                    <div v-else class="relative">
                                        <el-image :src="row.cover_url || row.file_url" fit="cover" style="width: 80px; height: 60px" />
                                        <el-icon class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-white text-2xl"><VideoPlay /></el-icon>
                                    </div>
                                </template>
                            </el-table-column>
                            <el-table-column label="类型" width="100">
                                <template #default="{ row }">
                                    <el-tag :type="Number(row.type) === 1 ? 'success' : 'warning'">{{ Number(row.type) === 1 ? '图片' : '视频' }}</el-tag>
                                </template>
                            </el-table-column>
                            <el-table-column label="视频自动播放" width="120">
                                <template #default="{ row }">
                                    <span v-if="Number(row.type) === 2">
                                        <el-tag :type="row.is_autoplay ? 'success' : 'info'">{{ row.is_autoplay ? '是' : '否' }}</el-tag>
                                    </span>
                                    <span v-else class="admin-edit-muted">-</span>
                                </template>
                            </el-table-column>
                            <el-table-column label="操作" width="200">
                                <template #default="{ row, $index }">
                                    <el-button type="primary" link @click="openEditBanner(row)">编辑</el-button>
                                    <el-button type="danger" link @click="deleteBanner(row)">删除</el-button>
                                    <el-button v-if="$index > 0" type="primary" link @click="moveBanner($index, 'up')">上移</el-button>
                                    <el-button v-if="$index < bannerList.length - 1" type="primary" link @click="moveBanner($index, 'down')">下移</el-button>
                                </template>
                            </el-table-column>
                        </el-table>
                    </div>
                </div>
            </el-form>
        </el-card>

        <footer-btns>
            <el-button type="primary" :loading="saveLoading" @click="handleSave">保存展示内容</el-button>
        </footer-btns>

        <el-dialog v-model="showBannerDialog" :title="isEditingBanner ? '编辑轮播图' : '添加轮播图'" width="600px" class="staff-edit-dialog" @closed="resetBannerForm">
            <el-form ref="bannerFormRef" :model="bannerForm" :rules="bannerFormRules" label-width="100px">
                <el-form-item label="类型" prop="type">
                    <el-radio-group v-model="bannerForm.type">
                        <el-radio :value="1">图片</el-radio>
                        <el-radio :value="2">视频</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item label="文件" prop="file_url">
                    <material-picker v-model="bannerForm.file_url" :limit="1" :type="bannerForm.type === 1 ? 'image' : 'video'" />
                </el-form-item>
                <el-form-item v-if="bannerForm.type === 2" label="封面图" prop="cover_url">
                    <material-picker v-model="bannerForm.cover_url" :limit="1" type="image" />
                </el-form-item>
                <el-form-item v-if="bannerForm.type === 2" label="自动播放">
                    <el-switch v-model="bannerForm.is_autoplay" :active-value="1" :inactive-value="0" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="showBannerDialog = false">取消</el-button>
                <el-button type="primary" @click="submitBanner">确定</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup lang="ts" name="staffCenterShowcase">
import { computed, onMounted, reactive, ref, shallowRef } from 'vue'
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage } from 'element-plus'
import { VideoPlay } from '@element-plus/icons-vue'
import { useRouter } from 'vue-router'
import feedback from '@/utils/feedback'
import { categoryTree, styleTagAll } from '@/api/service'
import LongDetailEditor from '@/components/staff/long-detail-editor.vue'
import {
    myProfile,
    myProfileBannerAdd,
    myProfileBannerConfig,
    myProfileBannerDelete,
    myProfileBannerEdit,
    myProfileBannerList,
    myProfileBannerSort,
    myProfileUpdate
} from '@/api/staff-center'

const router = useRouter()
const formRef = shallowRef<FormInstance>()
const bannerFormRef = shallowRef<FormInstance>()
const saveLoading = ref(false)
const showBannerDialog = ref(false)
const isEditingBanner = ref(false)

const categoryOptions = ref<Record<string, any>[]>([])
const groupedTags = ref<Record<string, Array<Record<string, any>>>>({})
const bannerList = ref<Array<Record<string, any>>>([])

const tagAuditInfo = reactive({
    pending_tag_ids: [] as number[],
    tag_apply_status: null as number | null,
    tag_apply_reject_reason: '',
    staff_tag_review_enabled: 0
})

const tagTypeLabelMap: Record<number, string> = {
    1: '风格',
    2: '特长',
    3: '其他'
}

const tagReviewAlert = computed(() => {
    if (tagAuditInfo.staff_tag_review_enabled !== 1) {
        return { type: 'info' as const, title: '当前标签保存后立即生效。' }
    }
    if (tagAuditInfo.tag_apply_status === 0) {
        return { type: 'warning' as const, title: '当前存在待审核标签申请，新的保存会覆盖原待审内容。' }
    }
    if (tagAuditInfo.tag_apply_status === 2) {
        return {
            type: 'error' as const,
            title: tagAuditInfo.tag_apply_reject_reason
                ? `上次标签申请未通过审核。拒绝原因：${tagAuditInfo.tag_apply_reject_reason}`
                : '上次标签申请未通过审核，请调整后重新提交。'
        }
    }
    return { type: 'info' as const, title: '当前标签保存后需管理员审核通过才会生效。' }
})

const formData = reactive({
    avatar: '',
    name: '',
    mobile: '',
    category_id: '',
    category_name: '',
    price_text: '',
    experience_years: 0,
    profile: '',
    service_desc: '',
    long_detail: '',
    status: 1,
    is_recommend: 0,
    tag_ids: [] as number[]
})

const bannerConfig = reactive({
    banner_mode: 1,
    banner_small_height: 400,
    banner_large_height: 600,
    banner_indicator_style: 1,
    banner_autoplay: 1,
    banner_interval: 3000
})

const bannerForm = reactive({
    id: 0,
    type: 1,
    file_url: '',
    cover_url: '',
    is_autoplay: 0
})

const bannerFormRules: FormRules = {
    type: [{ required: true, message: '请选择类型', trigger: 'change' }],
    file_url: [{ required: true, message: '请上传文件', trigger: 'change' }],
    cover_url: [{
        validator: (_rule, value, callback) => {
            if (bannerForm.type === 2 && !value) {
                callback(new Error('视频需要上传封面图'))
                return
            }
            callback()
        },
        trigger: 'change'
    }]
}

const findCategoryName = (options: Record<string, any>[], targetId: number): string => {
    for (const option of options) {
        if (Number(option.id) === targetId) {
            return String(option.name || '')
        }
        const children = Array.isArray(option.children) ? (option.children as Record<string, any>[]) : []
        if (children.length) {
            const childName = findCategoryName(children, targetId)
            if (childName) return childName
        }
    }
    return ''
}

const syncSelectedTags = (tagsByType: Record<string, Array<Record<string, any>>>) => {
    const availableIds = new Set<number>()
    Object.values(tagsByType).forEach((list) => {
        list.forEach((tag) => {
            if (tag?.id) availableIds.add(Number(tag.id))
        })
    })
    formData.tag_ids = formData.tag_ids.filter((id) => availableIds.has(Number(id)))
}

const loadCategoryOptions = async () => {
    categoryOptions.value = ((await categoryTree()) || []) as Record<string, any>[]
}

const loadTags = async () => {
    const categoryId = Number(formData.category_id)
    if (!categoryId) {
        groupedTags.value = {}
        return
    }
    const res = await styleTagAll({ group_by_type: 1, category_id: categoryId })
    groupedTags.value = (res || {}) as Record<string, Array<Record<string, any>>>
    syncSelectedTags(groupedTags.value)
}

const loadProfile = async () => {
    const data = await myProfile()
    formData.avatar = data?.avatar || ''
    formData.name = data?.name || ''
    formData.mobile = data?.mobile || ''
    formData.category_id = data?.category_id ? String(data.category_id) : ''
    formData.category_name = data?.category_name || findCategoryName(categoryOptions.value, Number(data?.category_id || 0))
    formData.price_text = data?.price_text || ''
    formData.experience_years = Number(data?.experience_years ?? 0)
    formData.profile = data?.profile || ''
    formData.service_desc = data?.service_desc || ''
    formData.long_detail = data?.long_detail || ''
    formData.status = Number(data?.status ?? 1)
    formData.is_recommend = Number(data?.is_recommend ?? 0)
    tagAuditInfo.pending_tag_ids = Array.isArray(data?.pending_tag_ids) ? data.pending_tag_ids.map((item: unknown) => Number(item)) : []
    tagAuditInfo.tag_apply_status = data?.tag_apply_status === null || data?.tag_apply_status === undefined ? null : Number(data.tag_apply_status)
    tagAuditInfo.tag_apply_reject_reason = data?.tag_apply_reject_reason || ''
    tagAuditInfo.staff_tag_review_enabled = Number(data?.staff_tag_review_enabled ?? 0)
    const effectiveTagIds = Array.isArray(data?.tag_ids) ? data.tag_ids.map((item: unknown) => Number(item)) : []
    formData.tag_ids = tagAuditInfo.pending_tag_ids.length ? [...tagAuditInfo.pending_tag_ids] : effectiveTagIds

    bannerConfig.banner_mode = Number(data?.banner_mode ?? 1)
    bannerConfig.banner_small_height = Number(data?.banner_small_height ?? 400)
    bannerConfig.banner_large_height = Number(data?.banner_large_height ?? 600)
    bannerConfig.banner_indicator_style = Number(data?.banner_indicator_style ?? 1)
    bannerConfig.banner_autoplay = Number(data?.banner_autoplay ?? 1)
    bannerConfig.banner_interval = Number(data?.banner_interval ?? 3000)
}

const loadBannerList = async () => {
    bannerList.value = ((await myProfileBannerList()) || []) as Array<Record<string, any>>
}

const handleSave = async () => {
    await formRef.value?.validate()
    saveLoading.value = true
    try {
        const res = await myProfileUpdate({
            avatar: formData.avatar,
            name: formData.name,
            mobile: formData.mobile,
            experience_years: formData.experience_years,
            profile: formData.profile,
            service_desc: formData.service_desc,
            long_detail: formData.long_detail,
            tag_ids: formData.tag_ids
        })
        ElMessage.success(res?.tag_action === 'pending' ? '展示内容已保存，标签修改已提交审核' : '保存成功')
        await Promise.all([loadProfile(), loadTags()])
    } finally {
        saveLoading.value = false
    }
}

const resetBannerForm = () => {
    Object.assign(bannerForm, { id: 0, type: 1, file_url: '', cover_url: '', is_autoplay: 0 })
    isEditingBanner.value = false
}

const openAddBanner = () => {
    resetBannerForm()
    showBannerDialog.value = true
}

const openEditBanner = (row: Record<string, any>) => {
    resetBannerForm()
    isEditingBanner.value = true
    Object.assign(bannerForm, {
        id: Number(row.id || 0),
        type: Number(row.type || 1),
        file_url: String(row.file_url || ''),
        cover_url: String(row.cover_url || ''),
        is_autoplay: Number(row.is_autoplay || 0)
    })
    showBannerDialog.value = true
}

const submitBanner = async () => {
    await bannerFormRef.value?.validate()
    if (isEditingBanner.value) {
        await myProfileBannerEdit({ ...bannerForm })
        ElMessage.success('编辑成功')
    } else {
        await myProfileBannerAdd({ ...bannerForm })
        ElMessage.success('添加成功')
    }
    showBannerDialog.value = false
    await loadBannerList()
}

const deleteBanner = async (row: Record<string, any>) => {
    await feedback.confirm('确定要删除该轮播图吗？')
    await myProfileBannerDelete({ id: row.id })
    ElMessage.success('删除成功')
    await loadBannerList()
}

const moveBanner = async (index: number, direction: 'up' | 'down') => {
    const list = [...bannerList.value]
    const targetIndex = direction === 'up' ? index - 1 : index + 1
    if (targetIndex < 0 || targetIndex >= list.length) return
    ;[list[index], list[targetIndex]] = [list[targetIndex], list[index]]
    await myProfileBannerSort({
        sort_data: list.map((item, currentIndex) => ({ id: item.id, sort: currentIndex }))
    })
    ElMessage.success('排序成功')
    await loadBannerList()
}

const saveBannerConfig = async () => {
    await myProfileBannerConfig({ ...bannerConfig })
    ElMessage.success('配置保存成功')
}

onMounted(async () => {
    await loadCategoryOptions()
    await loadProfile()
    await loadTags()
    await loadBannerList()
})
</script>

<style lang="scss" scoped>
.staff-center-showcase {
    .staff-edit-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        row-gap: 18px;
    }

    .staff-table-card {
        @apply rounded-lg border border-[#E8ECF3] overflow-hidden;
    }

    .staff-tag-groups {
        @apply w-full;
    }

    .staff-tag-block {
        @apply rounded-lg border border-[#E8ECF3] bg-white px-4 py-3 mb-4;
    }

    .staff-tag-block__title {
        @apply text-sm font-medium text-tx-primary mb-3;
    }

    .staff-tag-block__items {
        @apply flex flex-wrap gap-x-4 gap-y-2;
    }
}

@media (max-width: 1200px) {
    .staff-center-showcase {
        .staff-edit-grid {
            grid-template-columns: minmax(0, 1fr);
        }
    }
}
</style>
