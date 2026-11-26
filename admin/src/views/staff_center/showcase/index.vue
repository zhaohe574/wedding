<template>
    <admin-page-shell class="staff-center-showcase" title="服务展示">
        <StaffProfileHeader active="showcase" :profile="formData" :routes="routes" />

        <el-alert
            v-if="loadError"
            type="error"
            :title="loadError"
            :closable="false"
            show-icon
        >
            <template #default>
                <el-button type="primary" link @click="initializeShowcasePage">重试</el-button>
            </template>
        </el-alert>

        <div v-loading="loading" class="staff-center-showcase__body">
            <el-form ref="formRef" class="ls-form" :model="formData" label-width="100px">
                <section class="admin-page-section">
                    <div class="admin-edit-section__header">
                        <div class="admin-edit-section__title">展示文案</div>
                    </div>

                    <el-form-item label="列表页简介" prop="list_intro">
                        <div class="w-full">
                            <el-input
                                v-model="formData.list_intro"
                                type="textarea"
                                :rows="2"
                                maxlength="36"
                                show-word-limit
                                placeholder="15~36 字，展示在小程序列表卡片"
                            />
                            <div class="list-intro-preview">
                                <el-avatar :src="formData.avatar" :size="36">
                                    {{ String(formData.name || '人').slice(0, 1) }}
                                </el-avatar>
                                <div class="list-intro-preview__main">
                                    <div class="list-intro-preview__name">
                                        {{ formData.name || '服务人员姓名' }}
                                        <el-tag v-if="formData.is_recommend" size="small" type="warning" effect="plain">
                                            推荐
                                        </el-tag>
                                    </div>
                                    <div
                                        class="list-intro-preview__intro"
                                        :class="{ 'is-empty': !formData.list_intro }"
                                    >
                                        {{ formData.list_intro || '填写后将展示在小程序人员列表卡片中' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </el-form-item>

                    <el-form-item label="个人简介" prop="profile">
                        <el-input
                            v-model="formData.profile"
                            type="textarea"
                            :rows="3"
                            maxlength="500"
                            show-word-limit
                            placeholder="概括从业经验、擅长风格与服务理念"
                        />
                    </el-form-item>

                    <el-form-item label="服务说明" prop="service_desc">
                        <el-input
                            v-model="formData.service_desc"
                            type="textarea"
                            :rows="3"
                            maxlength="1000"
                            show-word-limit
                            placeholder="说明服务流程、交付内容与注意事项"
                        />
                    </el-form-item>

                    <el-form-item class="!mb-0" label="长图详情" prop="long_detail">
                        <long-detail-editor v-model="formData.long_detail" />
                    </el-form-item>
                </section>

                <section class="admin-page-section">
                    <div class="admin-edit-section__header">
                        <div class="section-title-row">
                            <span class="admin-edit-section__title">风格标签</span>
                            <el-tag v-if="tagReviewEnabled" size="small" type="info" effect="plain">
                                保存后需审核
                            </el-tag>
                        </div>
                    </div>

                    <el-alert
                        v-if="tagReviewNotice"
                        class="mb-4"
                        :title="tagReviewNotice.title"
                        :type="tagReviewNotice.type"
                        :closable="false"
                        show-icon
                    />
                    <el-alert
                        v-if="tagLoadFailed"
                        class="mb-4"
                        type="warning"
                        title="风格标签加载失败，请刷新页面重试"
                        :closable="false"
                        show-icon
                    />

                    <el-form-item class="staff-tag-form-item !mb-0" label="风格标签">
                        <el-checkbox-group v-model="formData.tag_ids" class="staff-tag-groups">
                            <template v-if="Object.keys(groupedTags).length">
                                <div v-for="(tags, type) in groupedTags" :key="type" class="staff-tag-block">
                                    <div class="staff-tag-block__title">{{ tagTypeLabelMap[Number(type)] || type }}</div>
                                    <div class="staff-tag-block__items">
                                        <el-checkbox
                                            v-for="tag in tags"
                                            :key="tag.id"
                                            :value="Number(tag.id)"
                                            :label="tag.name"
                                        />
                                    </div>
                                </div>
                            </template>
                            <div v-else class="admin-edit-muted">
                                {{ categoryLoadFailed ? '服务分类加载失败，请刷新页面后重试。' : '暂无可选标签，请确认服务分类后刷新页面。' }}
                            </div>
                        </el-checkbox-group>
                    </el-form-item>
                </section>

                <section class="admin-page-section">
                    <admin-table-toolbar>
                        <template #right>
                            <el-button type="primary" @click="saveBannerConfig">保存配置</el-button>
                        </template>
                    </admin-table-toolbar>

                    <div class="grid staff-edit-grid gap-x-8">
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
                            <el-input-number
                                v-model="bannerConfig.banner_small_height"
                                :min="200"
                                :max="800"
                                class="w-full"
                            />
                        </el-form-item>
                        <el-form-item :label="bannerConfig.banner_mode === 1 ? '展开后高度' : '固定高度'">
                            <el-input-number
                                v-model="bannerConfig.banner_large_height"
                                :min="300"
                                :max="1600"
                                class="w-full"
                            />
                        </el-form-item>
                        <el-form-item label="自动轮播">
                            <el-switch
                                v-model="bannerConfig.banner_autoplay"
                                :active-value="1"
                                :inactive-value="0"
                            />
                        </el-form-item>
                        <el-form-item v-if="bannerConfig.banner_autoplay === 1" label="轮播间隔">
                            <el-input-number
                                v-model="bannerConfig.banner_interval"
                                :min="1000"
                                :max="10000"
                                :step="500"
                                class="w-full"
                            />
                        </el-form-item>
                    </div>
                </section>

                <section class="admin-page-section">
                    <admin-table-toolbar>
                        <template #right>
                            <el-button type="primary" @click="openAddBanner">添加轮播图</el-button>
                        </template>
                    </admin-table-toolbar>

                    <div class="staff-table-card">
                        <el-table :data="bannerList" border row-key="id">
                            <el-table-column label="排序" width="60">
                                <template #default="{ $index }">{{ $index + 1 }}</template>
                            </el-table-column>
                            <el-table-column label="预览" width="120">
                                <template #default="{ row }">
                                    <el-image
                                        v-if="Number(row.type) === 1"
                                        :src="row.file_url"
                                        fit="cover"
                                        style="width: 80px; height: 60px"
                                        :preview-src-list="[row.file_url]"
                                    />
                                    <div v-else class="relative">
                                        <el-image
                                            :src="row.cover_url || row.file_url"
                                            fit="cover"
                                            style="width: 80px; height: 60px"
                                        />
                                        <el-icon
                                            class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-white text-2xl"
                                        >
                                            <VideoPlay />
                                        </el-icon>
                                    </div>
                                </template>
                            </el-table-column>
                            <el-table-column label="类型" width="100">
                                <template #default="{ row }">
                                    <el-tag :type="Number(row.type) === 1 ? 'success' : 'warning'" effect="plain">
                                        {{ Number(row.type) === 1 ? '图片' : '视频' }}
                                    </el-tag>
                                </template>
                            </el-table-column>
                            <el-table-column label="视频自动播放" width="120">
                                <template #default="{ row }">
                                    <span v-if="Number(row.type) === 2">
                                        <el-tag :type="row.is_autoplay ? 'success' : 'info'" effect="plain">
                                            {{ row.is_autoplay ? '是' : '否' }}
                                        </el-tag>
                                    </span>
                                    <span v-else class="admin-edit-muted">-</span>
                                </template>
                            </el-table-column>
                            <el-table-column label="操作" width="200">
                                <template #default="{ row, $index }">
                                    <el-button type="primary" link @click="openEditBanner(row)">编辑</el-button>
                                    <el-button type="danger" link @click="deleteBanner(row)">删除</el-button>
                                    <el-button
                                        v-if="$index > 0"
                                        type="primary"
                                        link
                                        @click="moveBanner($index, 'up')"
                                    >
                                        上移
                                    </el-button>
                                    <el-button
                                        v-if="$index < bannerList.length - 1"
                                        type="primary"
                                        link
                                        @click="moveBanner($index, 'down')"
                                    >
                                        下移
                                    </el-button>
                                </template>
                            </el-table-column>
                        </el-table>
                    </div>
                </section>
            </el-form>
        </div>

        <template #footer>
            <div class="flex justify-end">
                <el-button type="primary" :loading="saveLoading" @click="handleSave">
                    保存展示内容
                </el-button>
            </div>
        </template>

        <el-dialog
            v-model="showBannerDialog"
            :title="isEditingBanner ? '编辑轮播图' : '添加轮播图'"
            width="600px"
            class="staff-edit-dialog"
            @closed="resetBannerForm"
        >
            <el-form
                ref="bannerFormRef"
                :model="bannerForm"
                :rules="bannerFormRules"
                label-width="100px"
            >
                <el-form-item label="类型" prop="type">
                    <el-radio-group v-model="bannerForm.type">
                        <el-radio :value="1">图片</el-radio>
                        <el-radio :value="2">视频</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item label="文件" prop="file_url">
                    <material-picker
                        v-model="bannerForm.file_url"
                        :limit="1"
                        :type="bannerForm.type === 1 ? 'image' : 'video'"
                    />
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
                <el-button type="primary" @click="handleBannerSubmit">确定</el-button>
            </template>
        </el-dialog>
    </admin-page-shell>
</template>

<script setup lang="ts" name="staffCenterShowcase">
import { VideoPlay } from '@element-plus/icons-vue'
import type { FormInstance } from 'element-plus'
import { onMounted, shallowRef } from 'vue'

import LongDetailEditor from '@/components/staff/long-detail-editor.vue'

import StaffProfileHeader from '../profile/components/StaffProfileHeader.vue'
import { useStaffCenterProfile } from '../profile/use-staff-center-profile'

const formRef = shallowRef<FormInstance>()
const bannerFormRef = shallowRef<FormInstance>()

const {
    bannerConfig,
    bannerForm,
    bannerFormRules,
    bannerList,
    categoryLoadFailed,
    deleteBanner,
    formData,
    groupedTags,
    initializeShowcasePage,
    isEditingBanner,
    loadError,
    loadProfile,
    loadTags,
    loading,
    moveBanner,
    openAddBanner,
    openEditBanner,
    resetBannerForm,
    resolveStaffCenterPath,
    saveBannerConfig,
    saveLoading,
    saveProfile,
    showBannerDialog,
    submitBanner,
    tagAuditInfo,
    tagLoadFailed,
    tagReviewAlert,
    tagTypeLabelMap,
} = useStaffCenterProfile()

const tagReviewEnabled = computed(() => Number(tagAuditInfo.staff_tag_review_enabled) === 1)

const tagReviewNotice = computed(() =>
    tagReviewAlert.value.type === 'info' ? null : tagReviewAlert.value
)

const routes = {
    profile: resolveStaffCenterPath('page.staff_center.profile', 'profile'),
    showcase: resolveStaffCenterPath('page.staff_center.showcase', 'showcase'),
    package: resolveStaffCenterPath('page.staff_center.package', 'package'),
    work: resolveStaffCenterPath('page.staff_center.work', 'work'),
    certificate: resolveStaffCenterPath('page.staff_center.certificate', 'certificate'),
}

const handleSave = async () => {
    await formRef.value?.validate()
    await saveProfile(['list_intro', 'profile', 'service_desc', 'long_detail', 'tag_ids'])
    await Promise.allSettled([loadProfile(), loadTags()])
}

const handleBannerSubmit = async () => {
    await bannerFormRef.value?.validate()
    await submitBanner()
}

onMounted(async () => {
    await initializeShowcasePage()
})
</script>

<style lang="scss" scoped>
.staff-center-showcase {
    &__body {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .staff-edit-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        row-gap: 18px;
    }

    .staff-table-card {
        border: 1px solid var(--admin-color-border);
        border-radius: var(--admin-radius-sm);
        overflow: hidden;
    }

    .staff-tag-groups {
        width: 100%;
    }

    .section-title-row {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .staff-tag-block {
        margin-bottom: 16px;
        padding: 12px 16px;
        border: 1px solid var(--admin-color-border);
        border-radius: var(--admin-radius-sm);
        background: var(--admin-surface-soft);
    }

    .staff-tag-block__title {
        margin-bottom: 10px;
        font-size: 14px;
        font-weight: 500;
        color: var(--admin-color-title);
    }

    .staff-tag-block__items {
        display: flex;
        flex-wrap: wrap;
        gap: 8px 16px;
    }
}

.list-intro-preview {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 10px;
    padding: 12px 14px;
    border: 1px solid var(--admin-color-border);
    border-radius: var(--admin-radius-sm);
    background: var(--admin-surface-soft);

    &__main {
        min-width: 0;
    }

    &__name {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 600;
        line-height: 1.5;
        color: var(--admin-color-title);
    }

    &__intro {
        margin-top: 2px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 12px;
        line-height: 1.6;
        color: var(--admin-color-muted);

        &.is-empty {
            color: var(--el-text-color-placeholder);
        }
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
