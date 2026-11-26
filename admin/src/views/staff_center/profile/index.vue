<template>
    <admin-page-shell class="staff-center-profile-basic" title="基本资料">
        <StaffProfileHeader active="profile" :profile="formData" :routes="routes" />

        <el-alert
            v-if="loadError"
            type="error"
            :title="loadError"
            :closable="false"
            show-icon
        >
            <template #default>
                <el-button type="primary" link @click="initializeBasicPage">重试</el-button>
            </template>
        </el-alert>

        <div v-loading="loading" class="staff-center-profile-basic__body">
            <el-form
                ref="formRef"
                class="ls-form"
                :model="formData"
                :rules="staffProfileFormRules"
                label-width="100px"
            >
                <div class="admin-page-section">
                    <div class="admin-edit-section__header">
                        <div class="admin-edit-section__title">身份信息</div>
                    </div>
                    <div class="grid profile-grid gap-x-8">
                        <el-form-item label="姓名" prop="name">
                            <el-input v-model="formData.name" maxlength="50" placeholder="请输入姓名" />
                        </el-form-item>
                        <el-form-item label="手机号" prop="mobile">
                            <el-input v-model="formData.mobile" maxlength="11" placeholder="请输入手机号" />
                        </el-form-item>
                        <el-form-item label="头像" prop="avatar">
                            <material-picker v-model="formData.avatar" :limit="1" />
                        </el-form-item>
                        <el-form-item label="后台账号">
                            <div class="flex items-center gap-2">
                                <span v-if="adminInfo.account">{{ adminInfo.account }}</span>
                                <span v-else class="admin-edit-muted">未生成</span>
                                <el-tag
                                    v-if="adminInfo.account"
                                    :type="adminInfo.disable ? 'danger' : 'success'"
                                    effect="plain"
                                >
                                    {{ adminInfo.disable ? '禁用' : '启用' }}
                                </el-tag>
                            </div>
                        </el-form-item>
                    </div>
                </div>

                <div class="admin-page-section">
                    <div class="admin-edit-section__header">
                        <div class="admin-edit-section__title">业务信息</div>
                    </div>
                    <div class="grid profile-grid gap-x-8">
                        <el-form-item label="服务分类">
                            <el-input :model-value="formData.category_name || '未设置'" disabled class="w-full" />
                        </el-form-item>
                        <el-form-item label="服务起价">
                            <el-input :model-value="formData.price_text || '未配置套餐'" disabled class="w-full" />
                        </el-form-item>
                        <el-form-item label="从业年限（年）" prop="experience_years">
                            <el-input-number
                                v-model="formData.experience_years"
                                :min="0"
                                :max="50"
                                class="w-full"
                            />
                        </el-form-item>
                        <el-form-item label="是否推荐">
                            <el-radio-group v-model="formData.is_recommend" disabled>
                                <el-radio :value="1">是</el-radio>
                                <el-radio :value="0">否</el-radio>
                            </el-radio-group>
                        </el-form-item>
                        <el-form-item label="状态">
                            <el-radio-group v-model="formData.status" disabled>
                                <el-radio :value="1">启用</el-radio>
                                <el-radio :value="0">禁用</el-radio>
                            </el-radio-group>
                        </el-form-item>
                    </div>
                </div>

                <div class="admin-page-section">
                    <div class="admin-edit-section__header">
                        <div class="admin-edit-section__title">月报素材</div>
                    </div>
                    <div class="grid profile-grid gap-x-8">
                        <el-form-item>
                            <template #label>
                                <span class="form-label">
                                    头像素材
                                    <el-tooltip
                                        content="用于单量月报执行榜展示。"
                                        placement="top"
                                        :popper-style="labelTipStyle"
                                    >
                                        <el-icon class="label-tip-icon"><QuestionFilled /></el-icon>
                                    </el-tooltip>
                                </span>
                            </template>
                            <material-picker v-model="formData.monthly_report_material.avatar_photo" :limit="1" />
                        </el-form-item>
                        <el-form-item>
                            <template #label>
                                <span class="form-label">
                                    半身素材
                                    <el-tooltip
                                        content="用于单量月报单王展示。"
                                        placement="top"
                                        :popper-style="labelTipStyle"
                                    >
                                        <el-icon class="label-tip-icon"><QuestionFilled /></el-icon>
                                    </el-tooltip>
                                </span>
                            </template>
                            <material-picker v-model="formData.monthly_report_material.half_body_photo" :limit="1" />
                        </el-form-item>
                        <el-form-item label="英文名/拼音">
                            <el-input
                                v-model="formData.monthly_report_material.english_name"
                                maxlength="80"
                                placeholder="用于月报英文名展示"
                            />
                        </el-form-item>
                    </div>
                </div>
            </el-form>

            <div class="staff-center-profile-basic__actions">
                <el-button type="primary" :loading="saveLoading" @click="handleSave">
                    保存基本资料
                </el-button>
            </div>
        </div>
    </admin-page-shell>
</template>

<script setup lang="ts" name="staffCenterProfile">
import { QuestionFilled } from '@element-plus/icons-vue'
import type { FormInstance } from 'element-plus'
import { onMounted, shallowRef } from 'vue'

import StaffProfileHeader from './components/StaffProfileHeader.vue'
import {
    staffProfileFormRules,
    useStaffCenterProfile,
} from './use-staff-center-profile'

const formRef = shallowRef<FormInstance>()

const labelTipStyle = { maxWidth: '320px', lineHeight: '1.6' }

const {
    adminInfo,
    formData,
    initializeBasicPage,
    loadError,
    loading,
    resolveStaffCenterPath,
    saveLoading,
    saveProfile,
} = useStaffCenterProfile()

const routes = {
    profile: resolveStaffCenterPath('page.staff_center.profile', 'profile'),
    showcase: resolveStaffCenterPath('page.staff_center.showcase', 'showcase'),
    package: resolveStaffCenterPath('page.staff_center.package', 'package'),
    work: resolveStaffCenterPath('page.staff_center.work', 'work'),
    certificate: resolveStaffCenterPath('page.staff_center.certificate', 'certificate'),
}

const handleSave = async () => {
    await formRef.value?.validate()
    await saveProfile(['avatar', 'name', 'mobile', 'experience_years', 'monthly_report_material'])
    await initializeBasicPage()
}

onMounted(async () => {
    await initializeBasicPage()
})
</script>

<style lang="scss" scoped>
.staff-center-profile-basic {
    &__body {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    &__actions {
        display: flex;
        justify-content: flex-end;
        padding: 14px 16px;
        border: 1px solid var(--admin-color-border);
        border-radius: var(--admin-radius-md);
        background: var(--admin-surface-card);
    }

    .profile-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        row-gap: 18px;
    }

    .form-label {
        display: inline-flex;
        align-items: center;
    }

    .label-tip-icon {
        margin-left: 4px;
        font-size: 13px;
        color: var(--el-text-color-placeholder);
        cursor: help;
    }
}

@media (max-width: 1200px) {
    .staff-center-profile-basic {
        .profile-grid {
            grid-template-columns: minmax(0, 1fr);
        }
    }
}
</style>
