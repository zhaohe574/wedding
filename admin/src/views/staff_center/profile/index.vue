<template>
    <div class="staff-center-profile admin-edit-page">
        <el-card class="admin-edit-head !border-none" shadow="never">
            <div class="admin-edit-head__top">
                <div>
                    <h1 class="admin-edit-title">我的资料</h1>
                </div>
                <div class="admin-edit-head__meta">
                    <div class="admin-edit-head__meta-label">后台账号状态</div>
                    <div class="admin-edit-head__meta-main">
                        <span>{{ adminInfo.account || '未生成' }}</span>
                        <el-tag v-if="adminInfo.account" :type="adminInfo.disable ? 'danger' : 'success'">
                            {{ adminInfo.disable ? '禁用' : '启用' }}
                        </el-tag>
                        <el-tag v-else type="info">未生成</el-tag>
                    </div>
                </div>
            </div>
        </el-card>

        <el-card class="mt-4 !border-none admin-edit-main" shadow="never">
            <el-form ref="formRef" class="ls-form" :model="formData" :rules="formRules" label-width="100px">
                <el-tabs v-model="activeTab" class="staff-edit-tabs">
                    <el-tab-pane label="基本信息" name="basic">
                        <div class="admin-edit-section">
                            <div class="admin-edit-section__header">
                                <div class="admin-edit-section__title">身份信息</div>
                            </div>
                            <div class="grid staff-edit-grid gap-x-8">
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
                            <div class="grid staff-edit-grid gap-x-8">
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

                        <div class="admin-edit-section mt-4">
                            <div class="admin-edit-section__header">
                                <div class="admin-edit-section__title">补充说明</div>
                            </div>
                            <el-form-item label="个人简介" prop="profile">
                                <el-input
                                    v-model="formData.profile"
                                    type="textarea"
                                    :rows="3"
                                    placeholder="请输入个人简介"
                                    maxlength="500"
                                    show-word-limit
                                />
                            </el-form-item>
                            <el-form-item class="!mb-0" label="服务说明" prop="service_desc">
                                <el-input
                                    v-model="formData.service_desc"
                                    type="textarea"
                                    :rows="3"
                                    placeholder="请输入服务说明"
                                    maxlength="1000"
                                    show-word-limit
                                />
                            </el-form-item>
                        </div>

                    </el-tab-pane>

                    <el-tab-pane label="标签设置" name="tags">
                        <div class="admin-edit-section">
                            <div class="admin-edit-section__header">
                                <div class="admin-edit-section__title">标签分组</div>
                            </div>
                            <el-alert
                                class="mb-4"
                                :title="tagReviewAlert.title"
                                :type="tagReviewAlert.type"
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
                                                    :value="tag.id"
                                                    :label="tag.name"
                                                />
                                            </div>
                                        </div>
                                    </template>
                                    <div v-else class="admin-edit-muted">请选择服务分类后再配置标签。</div>
                                </el-checkbox-group>
                            </el-form-item>
                        </div>
                    </el-tab-pane>

                    <el-tab-pane label="服务套餐" name="packages">
                        <div class="admin-edit-section">
                            <div class="admin-edit-toolbar">
                                <div>
                                    <div class="admin-edit-section__title">我的套餐</div>
                                </div>
                                <el-button type="primary" @click="openCreateStaffPackage">创建专属套餐</el-button>
                            </div>
                            <div class="staff-table-card mt-4">
                                <el-table :data="staffPackages" border>
                                    <el-table-column label="套餐名称" prop="name" min-width="150" />
                                    <el-table-column label="地区价" min-width="150">
                                        <template #default="{ row }">
                                            <package-region-price-summary
                                                :region-prices="row.region_prices"
                                                empty-class="admin-edit-muted"
                                            />
                                        </template>
                                    </el-table-column>
                                    <el-table-column label="价格" prop="price" width="140">
                                        <template #default="{ row }">¥{{ row.price }}</template>
                                    </el-table-column>
                                    <el-table-column label="原价" width="140">
                                        <template #default="{ row }">
                                            <span v-if="Number(row.original_price || 0) > 0">¥{{ row.original_price }}</span>
                                            <span v-else class="admin-edit-muted">-</span>
                                        </template>
                                    </el-table-column>
                                    <el-table-column label="推荐" width="100">
                                        <template #default="{ row }">
                                            <el-tag :type="row.is_recommend ? 'warning' : 'info'">
                                                {{ row.is_recommend ? '推荐' : '普通' }}
                                            </el-tag>
                                        </template>
                                    </el-table-column>
                                    <el-table-column label="状态" prop="is_show" width="100">
                                        <template #default="{ row }">
                                            <el-tag :type="row.is_show ? 'success' : 'info'">
                                                {{ row.is_show ? '上架' : '下架' }}
                                            </el-tag>
                                        </template>
                                    </el-table-column>
                                    <el-table-column label="操作" width="140">
                                        <template #default="{ row }">
                                            <el-button type="primary" link @click="openEditStaffPackage(row)">编辑</el-button>
                                            <el-button class="staff-secondary-action" type="danger" link @click="deleteStaffPackage(row)">
                                                删除
                                            </el-button>
                                        </template>
                                    </el-table-column>
                                </el-table>
                            </div>
                        </div>

                        <div class="admin-edit-section mt-6">
                            <div class="admin-edit-toolbar">
                                <div>
                                    <div class="admin-edit-section__title">预约附加项</div>
                                    <div class="admin-edit-muted">客户预约时会逐项确认，可自由增加或减少条目。</div>
                                </div>
                                <el-button type="primary" @click="openCreateStaffAddon">新增附加项</el-button>
                            </div>
                            <div class="staff-table-card mt-4">
                                <el-table :data="staffAddons" border>
                                    <el-table-column label="展示图" width="120">
                                        <template #default="{ row }">
                                            <el-image
                                                v-if="row.image"
                                                :src="row.image"
                                                fit="cover"
                                                style="width: 72px; height: 72px; border-radius: 12px"
                                                :preview-src-list="[row.image]"
                                            />
                                            <span v-else class="admin-edit-muted">未设置</span>
                                        </template>
                                    </el-table-column>
                                    <el-table-column label="附加项名称" prop="name" min-width="160" />
                                    <el-table-column label="价格" width="140">
                                        <template #default="{ row }">¥{{ row.price }}</template>
                                    </el-table-column>
                                    <el-table-column label="介绍" min-width="220">
                                        <template #default="{ row }">
                                            <span v-if="row.description">{{ row.description }}</span>
                                            <span v-else class="admin-edit-muted">未填写</span>
                                        </template>
                                    </el-table-column>
                                    <el-table-column label="排序" prop="sort" width="100" />
                                    <el-table-column label="状态" width="100">
                                        <template #default="{ row }">
                                            <el-tag :type="row.is_show ? 'success' : 'info'">
                                                {{ row.is_show ? '上架' : '下架' }}
                                            </el-tag>
                                        </template>
                                    </el-table-column>
                                    <el-table-column label="操作" width="140">
                                        <template #default="{ row }">
                                            <el-button type="primary" link @click="openEditStaffAddon(row)">编辑</el-button>
                                            <el-button class="staff-secondary-action" type="danger" link @click="deleteStaffAddon(row)">
                                                删除
                                            </el-button>
                                        </template>
                                    </el-table-column>
                                </el-table>
                            </div>
                        </div>
                    </el-tab-pane>

                    <el-tab-pane label="轮播图配置" name="banner">
                        <div class="admin-edit-section">
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
                                    <el-input-number
                                        v-model="bannerConfig.banner_small_height"
                                        :min="200"
                                        :max="800"
                                        class="w-full"
                                    >
                                        <template #append>rpx</template>
                                    </el-input-number>
                                </el-form-item>
                                <el-form-item :label="bannerConfig.banner_mode === 1 ? '展开后高度' : '固定高度'">
                                    <el-input-number v-model="bannerConfig.banner_large_height" :min="300" class="w-full">
                                        <template #append>rpx</template>
                                    </el-input-number>
                                </el-form-item>
                                <el-form-item label="自动轮播">
                                    <el-switch v-model="bannerConfig.banner_autoplay" :active-value="1" :inactive-value="0" />
                                </el-form-item>
                                <el-form-item v-if="bannerConfig.banner_autoplay === 1" label="轮播间隔">
                                    <el-input-number
                                        v-model="bannerConfig.banner_interval"
                                        :min="1000"
                                        :max="10000"
                                        :step="500"
                                        class="w-full"
                                    >
                                        <template #append>毫秒</template>
                                    </el-input-number>
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
                                                <el-icon class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-white text-2xl">
                                                    <VideoPlay />
                                                </el-icon>
                                            </div>
                                        </template>
                                    </el-table-column>
                                    <el-table-column label="类型" width="100">
                                        <template #default="{ row }">
                                            <el-tag :type="Number(row.type) === 1 ? 'success' : 'warning'">
                                                {{ Number(row.type) === 1 ? '图片' : '视频' }}
                                            </el-tag>
                                        </template>
                                    </el-table-column>
                                    <el-table-column label="视频自动播放" width="120">
                                        <template #default="{ row }">
                                            <span v-if="Number(row.type) === 2">
                                                <el-tag :type="row.is_autoplay ? 'success' : 'info'">
                                                    {{ row.is_autoplay ? '是' : '否' }}
                                                </el-tag>
                                            </span>
                                            <span v-else class="admin-edit-muted">-</span>
                                        </template>
                                    </el-table-column>
                                    <el-table-column label="操作" width="200">
                                        <template #default="{ row, $index }">
                                            <el-button type="primary" link @click="openEditBanner(row)">编辑</el-button>
                                            <el-button class="staff-secondary-action" type="danger" link @click="deleteBanner(row)">
                                                删除
                                            </el-button>
                                            <el-button v-if="$index > 0" type="primary" link @click="moveBanner($index, 'up')">
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
                        </div>
                    </el-tab-pane>
                </el-tabs>
            </el-form>
        </el-card>

        <footer-btns class="staff-center-profile-footer">
            <el-button type="primary" :loading="saveLoading" @click="handleSaveProfile">保存资料</el-button>
        </footer-btns>

        <el-dialog
            v-model="showStaffPackageDialog"
            :title="isEditingStaffPackage ? '编辑专属套餐' : '创建专属套餐'"
            width="960px"
            class="staff-edit-dialog"
            @closed="resetStaffPackageForm"
        >
            <el-form ref="staffPackageFormRef" :model="staffPackageForm" :rules="staffPackageRules" label-width="100px">
                <el-form-item label="套餐名称" prop="name">
                    <el-input v-model="staffPackageForm.name" placeholder="请输入套餐名称" />
                </el-form-item>
                <el-form-item label="默认价格" prop="price">
                    <el-input-number v-model="staffPackageForm.price" :min="0" :precision="2" class="w-full" />
                </el-form-item>
                <el-form-item label="原价" prop="original_price">
                    <el-input-number v-model="staffPackageForm.original_price" :min="0" :precision="2" class="w-full" />
                </el-form-item>
                <el-form-item label="封面图" prop="image">
                    <material-picker v-model="staffPackageForm.image" :limit="1" />
                </el-form-item>
                <el-form-item label="套餐说明" prop="description">
                    <el-input
                        v-model="staffPackageForm.description"
                        type="textarea"
                        :rows="3"
                        placeholder="请输入套餐说明"
                    />
                </el-form-item>
                <el-form-item label="地区价格">
                    <package-region-price-editor
                        v-model="staffPackageForm.region_prices"
                        options-api-scene="staffCenter"
                    />
                </el-form-item>
                <div class="grid staff-edit-grid gap-x-8">
                    <el-form-item label="排序" prop="sort">
                        <el-input-number v-model="staffPackageForm.sort" :min="0" :max="9999" class="w-full" />
                    </el-form-item>
                    <el-form-item label="推荐" prop="is_recommend">
                        <el-radio-group v-model="staffPackageForm.is_recommend">
                            <el-radio :value="1">是</el-radio>
                            <el-radio :value="0">否</el-radio>
                        </el-radio-group>
                    </el-form-item>
                </div>
                <el-form-item label="状态" prop="is_show">
                    <el-radio-group v-model="staffPackageForm.is_show">
                        <el-radio :value="1">上架</el-radio>
                        <el-radio :value="0">下架</el-radio>
                    </el-radio-group>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="showStaffPackageDialog = false">取消</el-button>
                <el-button type="primary" @click="submitStaffPackage">
                    {{ isEditingStaffPackage ? '保存' : '创建' }}
                </el-button>
            </template>
        </el-dialog>

        <el-dialog
            v-model="showStaffAddonDialog"
            :title="isEditingStaffAddon ? '编辑附加项' : '新增附加项'"
            width="680px"
            class="staff-edit-dialog"
            @closed="resetStaffAddonForm"
        >
            <el-form ref="staffAddonFormRef" :model="staffAddonForm" :rules="staffAddonRules" label-width="100px">
                <el-form-item label="附加项名称" prop="name">
                    <el-input v-model="staffAddonForm.name" maxlength="100" placeholder="例如：晨袍跟拍" />
                </el-form-item>
                <el-form-item label="附加项价格" prop="price">
                    <el-input-number v-model="staffAddonForm.price" :min="0" :precision="2" class="w-full" />
                </el-form-item>
                <el-form-item label="展示图" prop="image">
                    <material-picker v-model="staffAddonForm.image" :limit="1" />
                </el-form-item>
                <el-form-item label="附加项介绍" prop="description">
                    <el-input
                        v-model="staffAddonForm.description"
                        type="textarea"
                        :rows="4"
                        maxlength="500"
                        show-word-limit
                        placeholder="请输入附加项介绍，便于客户理解服务场景和内容"
                    />
                </el-form-item>
                <div class="grid staff-edit-grid gap-x-8">
                    <el-form-item label="排序" prop="sort">
                        <el-input-number v-model="staffAddonForm.sort" :min="0" :max="9999" class="w-full" />
                    </el-form-item>
                    <el-form-item label="状态" prop="is_show">
                        <el-radio-group v-model="staffAddonForm.is_show">
                            <el-radio :value="1">上架</el-radio>
                            <el-radio :value="0">下架</el-radio>
                        </el-radio-group>
                    </el-form-item>
                </div>
            </el-form>
            <template #footer>
                <el-button @click="showStaffAddonDialog = false">取消</el-button>
                <el-button type="primary" @click="submitStaffAddon">
                    {{ isEditingStaffAddon ? '保存' : '创建' }}
                </el-button>
            </template>
        </el-dialog>

        <el-dialog
            v-model="showBannerDialog"
            :title="isEditingBanner ? '编辑轮播图' : '添加轮播图'"
            width="600px"
            class="staff-edit-dialog"
            @closed="resetBannerForm"
        >
            <el-form ref="bannerFormRef" :model="bannerForm" :rules="bannerFormRules" label-width="100px">
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
                <el-button type="primary" @click="submitBanner">确定</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup lang="ts" name="staffCenterProfile">
import { computed, onMounted, reactive, ref, shallowRef, watch } from 'vue'
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage } from 'element-plus'
import { VideoPlay } from '@element-plus/icons-vue'
import feedback from '@/utils/feedback'
import { categoryTree, styleTagAll } from '@/api/service'
import PackageRegionPriceEditor from '@/components/service/package-region-price-editor.vue'
import PackageRegionPriceSummary from '@/components/service/package-region-price-summary.vue'
import {
    myProfile,
    myProfileAddonAdd,
    myProfileAddonDelete,
    myProfileAddonList,
    myProfileAddonUpdate,
    myProfileBannerAdd,
    myProfileBannerConfig,
    myProfileBannerDelete,
    myProfileBannerEdit,
    myProfileBannerList,
    myProfileBannerSort,
    myProfileCreatePackage,
    myProfileDeletePackage,
    myProfilePackageConfig,
    myProfileUpdate,
    myProfileUpdateStaffPackage
} from '@/api/staff-center'

const formRef = shallowRef<FormInstance>()
const staffPackageFormRef = shallowRef<FormInstance>()
const staffAddonFormRef = shallowRef<FormInstance>()
const bannerFormRef = shallowRef<FormInstance>()

const activeTab = ref('basic')
const saveLoading = ref(false)
const showStaffPackageDialog = ref(false)
const showStaffAddonDialog = ref(false)
const showBannerDialog = ref(false)
const isEditingStaffPackage = ref(false)
const isEditingStaffAddon = ref(false)
const isEditingBanner = ref(false)

const categoryOptions = ref<any[]>([])
const groupedTags = ref<Record<string, any[]>>({})
const staffPackages = ref<any[]>([])
const staffAddons = ref<any[]>([])
const bannerList = ref<any[]>([])

const adminInfo = reactive({
    account: '',
    disable: 0
})

const tagAuditInfo = reactive({
    pending_tag_ids: [] as number[],
    pending_tag_names: [] as string[],
    tag_apply_status: null as number | null,
    tag_apply_status_desc: '',
    tag_apply_reject_reason: '',
    staff_tag_review_enabled: 0,
})

const tagTypeLabelMap: Record<number, string> = {
    1: '风格',
    2: '特长',
    3: '其他'
}

const tagReviewAlert = computed(() => {
    if (tagAuditInfo.staff_tag_review_enabled !== 1) {
        return {
            type: 'info' as const,
            title: '当前标签保存后立即生效。',
        }
    }
    if (tagAuditInfo.tag_apply_status === 0) {
        return {
            type: 'warning' as const,
            title: '当前存在待审核标签申请，新的保存会覆盖原待审内容。',
        }
    }
    if (tagAuditInfo.tag_apply_status === 2) {
        const reason = tagAuditInfo.tag_apply_reject_reason
            ? `拒绝原因：${tagAuditInfo.tag_apply_reject_reason}`
            : '请调整后重新提交。'
        return {
            type: 'error' as const,
            title: `上次标签申请未通过审核。${reason}`,
        }
    }

    return {
        type: 'info' as const,
        title: '当前标签保存后需管理员审核通过才会生效。',
    }
})

const formData = reactive({
    id: 0,
    avatar: '',
    name: '',
    mobile: '',
    category_id: '',
    category_name: '',
    price_text: '',
    experience_years: 0,
    profile: '',
    service_desc: '',
    status: 1,
    is_recommend: 0,
    tag_ids: [] as number[]
})

const formRules: FormRules = {
    name: [{ required: true, message: '请输入姓名', trigger: 'blur' }]
}

const staffPackageForm = reactive({
    id: 0,
    name: '',
    price: 0,
    original_price: 0,
    image: '',
    description: '',
    region_prices: [] as Record<string, any>[],
    sort: 0,
    is_show: 1,
    is_recommend: 0
})

const staffAddonForm = reactive({
    addon_id: 0,
    name: '',
    price: 0,
    image: '',
    description: '',
    sort: 0,
    is_show: 1
})

const staffPackageRules: FormRules = {
    name: [{ required: true, message: '请输入套餐名称', trigger: 'blur' }],
    price: [{ required: true, message: '请输入套餐价格', trigger: 'blur' }]
}

const staffAddonRules: FormRules = {
    name: [{ required: true, message: '请输入附加项名称', trigger: 'blur' }],
    price: [{ required: true, message: '请输入附加项价格', trigger: 'blur' }]
}

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
    cover_url: [
        {
            validator: (_rule: any, value: any, callback: (error?: Error) => void) => {
                if (bannerForm.type === 2 && !value) {
                    callback(new Error('视频需要上传封面图'))
                    return
                }
                callback()
            },
            trigger: 'change'
        }
    ]
}

const syncSelectedTags = (tagsByType: Record<string, any[]>) => {
    const availableIds = new Set<number>()
    Object.values(tagsByType).forEach((list) => {
        list.forEach((tag) => {
            if (tag?.id) {
                availableIds.add(Number(tag.id))
            }
        })
    })
    if (formData.tag_ids.length) {
        formData.tag_ids = formData.tag_ids.filter((id) => availableIds.has(Number(id)))
    }
}

const findCategoryName = (options: any[], targetId: number): string => {
    for (const option of options) {
        if (Number(option.id) === targetId) {
            return option.name || ''
        }
        if (Array.isArray(option.children) && option.children.length) {
            const childName = findCategoryName(option.children, targetId)
            if (childName) {
                return childName
            }
        }
    }
    return ''
}

const resolveCategoryName = (categoryId: string | number) => {
    const currentId = Number(categoryId)
    if (!currentId) {
        return ''
    }
    return findCategoryName(categoryOptions.value, currentId)
}

const resetStaffPackageForm = () => {
    Object.assign(staffPackageForm, {
        id: 0,
        name: '',
        price: 0,
        original_price: 0,
        image: '',
        description: '',
        region_prices: [],
        sort: 0,
        is_show: 1,
        is_recommend: 0
    })
    isEditingStaffPackage.value = false
}

const resetStaffAddonForm = () => {
    Object.assign(staffAddonForm, {
        addon_id: 0,
        name: '',
        price: 0,
        image: '',
        description: '',
        sort: 0,
        is_show: 1
    })
    isEditingStaffAddon.value = false
}

const resetBannerForm = () => {
    Object.assign(bannerForm, {
        id: 0,
        type: 1,
        file_url: '',
        cover_url: '',
        is_autoplay: 0
    })
    isEditingBanner.value = false
}

const loadCategoryOptions = async () => {
    categoryOptions.value = (await categoryTree()) || []
    if (!formData.category_name && formData.category_id) {
        formData.category_name = resolveCategoryName(formData.category_id)
    }
}

const loadTags = async () => {
    const categoryId = Number(formData.category_id)
    if (!categoryId) {
        groupedTags.value = {}
        formData.tag_ids = []
        return
    }
    const res = await styleTagAll({ group_by_type: 1, category_id: categoryId })
    groupedTags.value = res || {}
    syncSelectedTags(groupedTags.value)
}

const loadProfile = async () => {
    const data = await myProfile()
    Object.keys(formData).forEach((key) => {
        if ((data as any)[key] !== undefined) {
            ;(formData as any)[key] = (data as any)[key]
        }
    })
    formData.id = Number((data as any).id || 0)
    formData.category_id = (data as any).category_id ? String((data as any).category_id) : ''
    formData.category_name = (data as any).category_name || resolveCategoryName(formData.category_id)
    formData.price_text = (data as any).price_text || ''
    formData.status = Number((data as any).status ?? 1)
    formData.is_recommend = Number((data as any).is_recommend ?? 0)
    tagAuditInfo.pending_tag_ids = Array.isArray((data as any).pending_tag_ids)
        ? (data as any).pending_tag_ids.map((item: any) => Number(item))
        : []
    tagAuditInfo.pending_tag_names = Array.isArray((data as any).pending_tag_names)
        ? (data as any).pending_tag_names
        : []
    tagAuditInfo.tag_apply_status =
        (data as any).tag_apply_status === null || (data as any).tag_apply_status === undefined
            ? null
            : Number((data as any).tag_apply_status)
    tagAuditInfo.tag_apply_status_desc = (data as any).tag_apply_status_desc || ''
    tagAuditInfo.tag_apply_reject_reason = (data as any).tag_apply_reject_reason || ''
    tagAuditInfo.staff_tag_review_enabled = Number((data as any).staff_tag_review_enabled ?? 0)
    const effectiveTagIds = Array.isArray((data as any).tag_ids)
        ? (data as any).tag_ids.map((item: any) => Number(item))
        : []
    formData.tag_ids = tagAuditInfo.pending_tag_ids.length ? [...tagAuditInfo.pending_tag_ids] : effectiveTagIds

    adminInfo.account = (data as any).admin_account || ''
    adminInfo.disable = Number((data as any).admin_disable || 0)

    bannerConfig.banner_mode = Number((data as any).banner_mode ?? 1)
    bannerConfig.banner_small_height = Number((data as any).banner_small_height ?? 400)
    bannerConfig.banner_large_height = Number((data as any).banner_large_height ?? 600)
    bannerConfig.banner_indicator_style = Number((data as any).banner_indicator_style ?? 1)
    bannerConfig.banner_autoplay = Number((data as any).banner_autoplay ?? 1)
    bannerConfig.banner_interval = Number((data as any).banner_interval ?? 3000)
}

const loadPackageConfig = async () => {
    const res = await myProfilePackageConfig()
    const packageList = Array.isArray(res) ? res : []
    staffPackages.value = packageList.map((item: any) => ({
        ...item,
        price: Number(item.price ?? 0),
        original_price: Number(item.original_price ?? 0),
        sort: Number(item.sort ?? 0),
        is_show: Number(item.is_show ?? 1)
    }))
}

const loadAddonConfig = async () => {
    const res = await myProfileAddonList()
    staffAddons.value = Array.isArray(res) ? res : []
}

const loadBannerList = async () => {
    bannerList.value = (await myProfileBannerList()) || []
}

const handleSaveProfile = async () => {
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
            tag_ids: formData.tag_ids
        })
        ElMessage.success(res?.tag_action === 'pending' ? '资料已保存，标签修改已提交审核' : '保存成功')
        await Promise.all([loadProfile(), loadTags()])
    } finally {
        saveLoading.value = false
    }
}

const openCreateStaffPackage = () => {
    resetStaffPackageForm()
    showStaffPackageDialog.value = true
}

const openCreateStaffAddon = () => {
    resetStaffAddonForm()
    showStaffAddonDialog.value = true
}

const openEditStaffPackage = (row: any) => {
    resetStaffPackageForm()
    isEditingStaffPackage.value = true
    Object.assign(staffPackageForm, {
        id: Number(row.id || 0),
        name: row.name || '',
        price: Number(row.price || 0),
        original_price: Number(row.original_price || 0),
        image: row.image || '',
        description: row.description || '',
        region_prices: Array.isArray(row.region_prices) ? row.region_prices : [],
        sort: Number(row.sort || 0),
        is_show: Number(row.is_show ?? 1),
        is_recommend: Number(row.is_recommend ?? 0)
    })
    showStaffPackageDialog.value = true
}

const openEditStaffAddon = (row: any) => {
    resetStaffAddonForm()
    isEditingStaffAddon.value = true
    Object.assign(staffAddonForm, {
        addon_id: Number(row.id || 0),
        name: row.name || '',
        price: Number(row.price || 0),
        image: row.image || '',
        description: row.description || '',
        sort: Number(row.sort || 0),
        is_show: Number(row.is_show ?? 1)
    })
    showStaffAddonDialog.value = true
}

const submitStaffPackage = async () => {
    await staffPackageFormRef.value?.validate()
    const payload = {
        name: staffPackageForm.name,
        price: Number(staffPackageForm.price ?? 0),
        original_price: staffPackageForm.original_price ?? null,
        image: staffPackageForm.image,
        description: staffPackageForm.description,
        region_prices: [...staffPackageForm.region_prices],
        sort: Number(staffPackageForm.sort ?? 0),
        is_show: Number(staffPackageForm.is_show ?? 1),
        is_recommend: Number(staffPackageForm.is_recommend ?? 0)
    }

    if (isEditingStaffPackage.value) {
        await myProfileUpdateStaffPackage({
            package_id: staffPackageForm.id,
            ...payload
        })
        ElMessage.success('更新成功')
    } else {
        await myProfileCreatePackage(payload)
        ElMessage.success('创建成功')
    }

    showStaffPackageDialog.value = false
    resetStaffPackageForm()
    await loadPackageConfig()
}

const submitStaffAddon = async () => {
    await staffAddonFormRef.value?.validate()
    const payload = {
        name: staffAddonForm.name,
        price: Number(staffAddonForm.price ?? 0),
        image: staffAddonForm.image,
        description: staffAddonForm.description,
        sort: Number(staffAddonForm.sort ?? 0),
        is_show: Number(staffAddonForm.is_show ?? 1)
    }

    if (isEditingStaffAddon.value) {
        await myProfileAddonUpdate({
            addon_id: staffAddonForm.addon_id,
            ...payload
        })
        ElMessage.success('更新成功')
    } else {
        await myProfileAddonAdd(payload)
        ElMessage.success('创建成功')
    }

    showStaffAddonDialog.value = false
    resetStaffAddonForm()
    await loadAddonConfig()
}

const deleteStaffPackage = async (row: any) => {
    await feedback.confirm(`确定要删除专属套餐「${row.name || ''}」吗？`)
    await myProfileDeletePackage({ package_id: row.id })
    ElMessage.success('删除成功')
    await loadPackageConfig()
}

const deleteStaffAddon = async (row: any) => {
    await feedback.confirm(`确定要删除附加项「${row.name || ''}」吗？`)
    await myProfileAddonDelete({ addon_id: row.id })
    ElMessage.success('删除成功')
    await loadAddonConfig()
}

const openAddBanner = () => {
    resetBannerForm()
    showBannerDialog.value = true
}

const openEditBanner = (row: any) => {
    resetBannerForm()
    isEditingBanner.value = true
    Object.assign(bannerForm, {
        id: Number(row.id || 0),
        type: Number(row.type || 1),
        file_url: row.file_url || '',
        cover_url: row.cover_url || '',
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

const deleteBanner = async (row: any) => {
    await feedback.confirm('确定要删除该轮播图吗？')
    await myProfileBannerDelete({ id: row.id })
    ElMessage.success('删除成功')
    await loadBannerList()
}

const moveBanner = async (index: number, direction: 'up' | 'down') => {
    const list = [...bannerList.value]
    const targetIndex = direction === 'up' ? index - 1 : index + 1
    if (targetIndex < 0 || targetIndex >= list.length) {
        return
    }

    ;[list[index], list[targetIndex]] = [list[targetIndex], list[index]]
    const sortData = list.map((item, idx) => ({
        id: item.id,
        sort: idx
    }))

    await myProfileBannerSort({ sort_data: sortData })
    ElMessage.success('排序成功')
    await loadBannerList()
}

const saveBannerConfig = async () => {
    await myProfileBannerConfig({
        ...bannerConfig
    })
    ElMessage.success('配置保存成功')
}

watch(
    () => formData.category_id,
    () => {
        loadTags()
    }
)

onMounted(async () => {
    await loadCategoryOptions()
    await loadProfile()
    await loadTags()
    await loadPackageConfig()
    await loadAddonConfig()
    await loadBannerList()
})
</script>

<style lang="scss" scoped>
.staff-center-profile {
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

    .slot-price-list {
        max-height: 360px;
        overflow-y: auto;
        padding-right: 4px;
    }

    :deep(.staff-edit-tabs .el-tabs__header) {
        margin-bottom: 20px;
    }

    :deep(.staff-edit-tabs .el-tabs__nav-wrap::after) {
        background-color: #e8ecf3;
    }

    :deep(.staff-edit-tabs .el-tabs__item) {
        padding: 0 18px;
        font-size: 14px;
        color: #5f6472;
    }

    :deep(.staff-edit-tabs .el-tabs__item.is-active) {
        color: var(--el-color-primary);
        font-weight: 600;
    }

    :deep(.staff-edit-tabs .el-form-item) {
        margin-bottom: 16px;
    }

    :deep(.staff-table-card .el-table th.el-table__cell) {
        background: #f4f7fc;
        color: #3f4654;
    }

    :deep(.staff-table-card .el-table td.el-table__cell) {
        vertical-align: top;
    }

    :deep(.staff-secondary-action) {
        opacity: 0.8;
    }

    :deep(.staff-edit-dialog .el-dialog__header) {
        border-bottom: 1px solid #eceff5;
        margin-right: 0;
        padding: 16px 20px 14px;
    }

    :deep(.staff-edit-dialog .el-dialog__body) {
        padding: 18px 20px;
    }

    :deep(.staff-edit-dialog .el-dialog__footer) {
        border-top: 1px solid #eceff5;
        padding: 12px 20px 16px;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    :deep(.staff-center-profile-footer .footer-btns__content) {
        border-top: 1px solid #e8ecf3;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(8px);
    }
}

@media (max-width: 1200px) {
    .staff-center-profile {
        .staff-edit-grid {
            grid-template-columns: minmax(0, 1fr);
        }
    }
}
</style>
