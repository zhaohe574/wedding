<template>
    <admin-page-shell class="staff-center-profile-basic" title="基本资料">
        <el-card class="!border-none" shadow="never">
            <div class="page-head">
                <div>
                    <div class="page-head__title">基本资料</div>
                    <div class="page-head__desc">仅维护本人身份信息与基础业务资料，服务展示和套餐已拆分到独立页面。</div>
                </div>
                <el-button-group>
                    <el-button :type="route.path === profilePath ? 'primary' : 'default'" @click="router.push(profilePath)">
                        基本资料
                    </el-button>
                    <el-button :type="route.path === showcasePath ? 'primary' : 'default'" @click="router.push(showcasePath)">
                        服务展示
                    </el-button>
                    <el-button :type="route.path === packagePath ? 'primary' : 'default'" @click="router.push(packagePath)">
                        专属套餐
                    </el-button>
                </el-button-group>
            </div>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <el-form ref="formRef" class="ls-form" :model="formData" :rules="staffProfileFormRules" label-width="100px">
                <div class="admin-edit-section">
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
                                <span v-else class="text-gray-400">未生成</span>
                                <el-tag v-if="adminInfo.account" :type="adminInfo.disable ? 'danger' : 'success'">
                                    {{ adminInfo.disable ? '禁用' : '启用' }}
                                </el-tag>
                            </div>
                        </el-form-item>
                    </div>
                </div>

                <div class="admin-edit-section mt-4">
                    <div class="admin-edit-section__header">
                        <div class="admin-edit-section__title">业务信息</div>
                    </div>
                    <div class="grid profile-grid gap-x-8">
                        <el-form-item label="服务分类">
                            <el-input :model-value="formData.category_name || '未设置'" disabled class="w-full" />
                        </el-form-item>
                        <el-form-item label="服务价格">
                            <el-input :model-value="formData.price_text || '未设置'" disabled class="w-full" />
                        </el-form-item>
                        <el-form-item label="从业年限" prop="experience_years">
                            <el-input-number v-model="formData.experience_years" :min="0" :max="50" class="w-full" />
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

                <div class="flex justify-end mt-6">
                    <el-button type="primary" :loading="saveLoading" @click="handleSave">保存基本资料</el-button>
                </div>
            </el-form>
        </el-card>
    </admin-page-shell>
</template>

<script setup lang="ts" name="staffCenterProfile">
import { computed, onMounted, shallowRef } from 'vue'
import type { FormInstance } from 'element-plus'
import { useRoute, useRouter } from 'vue-router'
import {
    staffProfileFormRules,
    useStaffCenterProfile,
} from './use-staff-center-profile'

const route = useRoute()
const router = useRouter()
const formRef = shallowRef<FormInstance>()

const {
    adminInfo,
    formData,
    initializeBasicPage,
    replaceLastPathSegment,
    saveLoading,
    saveProfile,
} = useStaffCenterProfile()

const profilePath = computed(() => replaceLastPathSegment(route.path, 'profile'))
const showcasePath = computed(() => replaceLastPathSegment(route.path, 'showcase'))
const packagePath = computed(() => replaceLastPathSegment(route.path, 'package'))

const handleSave = async () => {
    await formRef.value?.validate()
    await saveProfile(['avatar', 'name', 'mobile', 'experience_years'])
    await initializeBasicPage()
}

onMounted(async () => {
    await initializeBasicPage()
})
</script>

<style lang="scss" scoped>
.staff-center-profile-basic {
    .page-head {
        @apply flex items-center justify-between gap-4;
    }

    .page-head__title {
        @apply text-lg font-semibold text-tx-primary;
    }

    .page-head__desc {
        @apply text-sm text-tx-secondary mt-2;
    }

    .profile-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        row-gap: 18px;
    }
}

@media (max-width: 1200px) {
    .staff-center-profile-basic {
        .page-head {
            @apply flex-col items-start;
        }

        .profile-grid {
            grid-template-columns: minmax(0, 1fr);
        }
    }
}
</style>
