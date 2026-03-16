<template>
    <div class="staff-edit admin-edit-page">
        <el-card class="admin-edit-head !border-none" shadow="never">
            <div class="admin-edit-head__top">
                <div>
                    <h1 class="admin-edit-title">{{ route.query.id ? '编辑人员' : '新增人员' }}</h1>
                    <p class="admin-edit-subtitle">
                        {{ route.query.id ? '维护人员资料、标签、套餐和轮播展示内容。' : '创建人员并完成标签、套餐和轮播展示配置。' }}
                    </p>
                </div>
                <div class="admin-edit-head__meta">
                    <div class="admin-edit-head__meta-label">后台账号状态</div>
                    <div class="admin-edit-head__meta-main">
                        <span>{{ adminInfo.account || '保存后自动生成账号' }}</span>
                        <el-tag v-if="adminInfo.account" :type="adminInfo.disable ? 'danger' : 'success'">
                            {{ adminInfo.disable ? '禁用' : '启用' }}
                        </el-tag>
                        <el-tag v-else type="info">未生成</el-tag>
                        <el-button v-if="adminInfo.account" type="primary" link @click="handleResetAdminPassword">
                            重置密码
                        </el-button>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <el-button type="primary" link @click="router.back()">返回上一页</el-button>
            </div>
        </el-card>
        <el-card class="mt-4 !border-none admin-edit-main" shadow="never">
            <el-form
                ref="formRef"
                class="ls-form"
                :model="formData"
                label-width="100px"
                :rules="rules"
            >
                <el-tabs v-model="activeTab" class="staff-edit-tabs">
                    <el-tab-pane label="基本信息" name="basic">
                        <div class="admin-edit-section">
                            <div class="admin-edit-section__header">
                                <div class="admin-edit-section__title">身份信息</div>
                                <div class="admin-edit-section__desc">用于建立人员基础档案与后台账号关联。</div>
                            </div>
                            <div class="grid staff-edit-grid gap-x-8">
                                <el-form-item label="姓名" prop="name">
                                    <el-input v-model="formData.name" placeholder="请输入姓名" maxlength="50" />
                                </el-form-item>
                                <el-form-item label="绑定用户" prop="user_id">
                                    <el-select
                                        v-model="formData.user_id"
                                        filterable
                                        remote
                                        reserve-keyword
                                        :remote-method="remoteUserSearch"
                                        :loading="userLoading"
                                        placeholder="输入昵称/账号/手机号搜索"
                                        class="w-full"
                                    >
                                        <el-option
                                            v-for="item in userOptions"
                                            :key="item.id"
                                            :label="item.label"
                                            :value="item.id"
                                        />
                                    </el-select>
                                </el-form-item>
                                <el-form-item label="手机号" prop="mobile">
                                    <el-input v-model="formData.mobile" placeholder="请输入手机号" maxlength="11" />
                                </el-form-item>
                                <el-form-item label="企微成员ID" prop="wecom_userid">
                                    <el-input
                                        v-model="formData.wecom_userid"
                                        placeholder="请输入企业微信成员ID"
                                        maxlength="64"
                                    />
                                </el-form-item>
                                <el-form-item label="头像" prop="avatar">
                                    <material-picker v-model="formData.avatar" :limit="1" />
                                </el-form-item>
                                <el-form-item label="后台账号">
                                    <div class="flex items-center gap-2">
                                        <span v-if="adminInfo.account">{{ adminInfo.account }}</span>
                                        <span v-else class="admin-edit-muted">保存后自动生成</span>
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
                                <div class="admin-edit-section__desc">配置分类、价格、展示排序与推荐状态。</div>
                            </div>
                            <div class="grid staff-edit-grid gap-x-8">
                                <el-form-item label="服务分类" prop="category_id">
                                    <el-cascader
                                        v-model="formData.category_id"
                                        :options="optionsData.categories"
                                        :props="{ value: 'id', label: 'name', checkStrictly: true, emitPath: false }"
                                        placeholder="选择服务分类"
                                        class="w-full"
                                    />
                                </el-form-item>
                                <el-form-item label="服务价格" prop="price">
                                    <el-input-number v-model="formData.price" :min="0" :precision="2" class="w-full" />
                                </el-form-item>
                                <el-form-item label="从业年限" prop="experience_years">
                                    <el-input-number v-model="formData.experience_years" :min="0" :max="50" class="w-full" />
                                </el-form-item>
                                <el-form-item label="排序" prop="sort">
                                    <el-input-number v-model="formData.sort" :min="0" :max="9999" class="w-full" />
                                </el-form-item>
                                <el-form-item label="是否推荐" prop="is_recommend">
                                    <el-radio-group v-model="formData.is_recommend">
                                        <el-radio :value="1">是</el-radio>
                                        <el-radio :value="0">否</el-radio>
                                    </el-radio-group>
                                </el-form-item>
                                <el-form-item label="状态" prop="status">
                                    <el-radio-group v-model="formData.status">
                                        <el-radio :value="1">启用</el-radio>
                                        <el-radio :value="0">禁用</el-radio>
                                    </el-radio-group>
                                </el-form-item>
                            </div>
                        </div>

                        <div class="admin-edit-section mt-4">
                            <div class="admin-edit-section__header">
                                <div class="admin-edit-section__title">补充说明</div>
                                <div class="admin-edit-section__desc">用于展示人员介绍与服务补充说明。</div>
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
                                <div class="admin-edit-section__desc">按标签类型批量设置人员风格与特长。</div>
                            </div>
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
                                    <div class="admin-edit-section__title">关联全局套餐</div>
                                    <div class="admin-edit-section__desc">支持设置默认价格、场次和单个套餐状态。</div>
                                </div>
                                <el-button @click="showPackageDialog = true">添加套餐</el-button>
                            </div>
                            <div class="staff-table-card mt-4">
                                <el-table :data="formData.packages" border>
                                    <el-table-column label="套餐名称" prop="package_name" min-width="150" />
                                    <el-table-column label="默认价格" width="150">
                                        <template #default="{ row }">
                                            <el-input-number
                                                v-model="row.price"
                                                :min="0"
                                                :precision="2"
                                                size="small"
                                                controls-position="right"
                                                placeholder="默认价格"
                                            />
                                        </template>
                                    </el-table-column>
                                    <el-table-column label="原价" width="150">
                                        <template #default="{ row }">
                                            <el-input-number
                                                v-model="row.original_price"
                                                :min="0"
                                                :precision="2"
                                                size="small"
                                                controls-position="right"
                                                placeholder="选填"
                                            />
                                        </template>
                                    </el-table-column>
                                    <el-table-column label="个人价格" width="150">
                                        <template #default="{ row }">
                                            <el-input-number
                                                v-model="row.custom_price"
                                                :min="0"
                                                :precision="2"
                                                size="small"
                                                controls-position="right"
                                                placeholder="不填则使用默认"
                                            />
                                        </template>
                                    </el-table-column>
                                    <el-table-column label="场次价格" width="100">
                                        <template #default="{ row, $index }">
                                            <el-button
                                                v-if="row.booking_type === 1"
                                                type="primary"
                                                link
                                                @click="openSlotPriceDialog($index)"
                                            >
                                                配置{{ row.custom_slot_prices?.length ? `(${row.custom_slot_prices.length})` : '' }}
                                            </el-button>
                                            <span v-else class="admin-edit-muted">全天</span>
                                        </template>
                                    </el-table-column>
                                    <el-table-column label="预约类型" width="140">
                                        <template #default="{ row }">
                                            <el-select
                                                v-model="row.booking_type"
                                                size="small"
                                                class="w-full"
                                                @change="handlePackageBookingTypeChange(row)"
                                            >
                                                <el-option label="全天套餐" :value="0" />
                                                <el-option label="分场次套餐" :value="1" />
                                            </el-select>
                                        </template>
                                    </el-table-column>
                                    <el-table-column label="允许场次" min-width="180">
                                        <template #default="{ row }">
                                            <el-checkbox-group
                                                v-if="row.booking_type === 1"
                                                v-model="row.allowed_time_slots"
                                                size="small"
                                            >
                                                <el-checkbox
                                                    v-for="slot in timeSlotOptions"
                                                    :key="slot.value"
                                                    :value="slot.value"
                                                    :label="slot.label"
                                                />
                                            </el-checkbox-group>
                                            <span v-else class="admin-edit-muted">全天</span>
                                        </template>
                                    </el-table-column>
                                    <el-table-column label="状态" width="100">
                                        <template #default="{ row }">
                                            <el-switch v-model="row.status" :active-value="1" :inactive-value="0" />
                                        </template>
                                    </el-table-column>
                                    <el-table-column label="操作" width="140">
                                        <template #default="{ row, $index }">
                                            <el-button type="primary" link @click="savePackageConfig(row)">保存</el-button>
                                            <el-button class="staff-secondary-action" type="danger" link @click="removePackage($index)">
                                                移除
                                            </el-button>
                                        </template>
                                    </el-table-column>
                                </el-table>
                            </div>
                        </div>

                        <div class="admin-edit-section mt-4">
                            <div class="admin-edit-toolbar">
                                <div>
                                    <div class="admin-edit-section__title">专属套餐</div>
                                    <div class="admin-edit-section__desc">维护人员独享套餐，支持独立编辑与上下架。</div>
                                </div>
                                <el-button type="primary" @click="openCreateStaffPackage">创建专属套餐</el-button>
                            </div>
                            <div class="staff-table-card mt-4">
                                <el-table :data="staffPackages" border>
                                    <el-table-column label="套餐名称" prop="name" min-width="150" />
                                    <el-table-column label="价格" prop="price" width="100">
                                        <template #default="{ row }">¥{{ row.price }}</template>
                                    </el-table-column>
                                    <el-table-column label="场次价格" width="100">
                                        <template #default="{ row }">
                                            <span v-if="row.slot_prices?.length">{{ row.slot_prices.length }}个场次</span>
                                            <span v-else class="admin-edit-muted">未配置</span>
                                        </template>
                                    </el-table-column>
                                    <el-table-column label="预约类型" width="120">
                                        <template #default="{ row }">
                                            <el-tag v-if="row.booking_type === 0" type="info">全天套餐</el-tag>
                                            <el-tag v-else type="warning">分场次套餐</el-tag>
                                        </template>
                                    </el-table-column>
                                    <el-table-column label="允许场次" min-width="160">
                                        <template #default="{ row }">
                                            <span v-if="row.booking_type !== 1" class="admin-edit-muted">全天</span>
                                            <span v-else>
                                                <el-tag
                                                    v-for="slot in normalizeAllowedSlots(row.allowed_time_slots)"
                                                    :key="slot"
                                                    class="mr-1"
                                                >
                                                    {{ timeSlotLabelMap[slot] || slot }}
                                                </el-tag>
                                            </span>
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
                    </el-tab-pane>

                    <el-tab-pane label="轮播图配置" name="banner">
                        <div class="admin-edit-section">
                            <div class="admin-edit-toolbar">
                                <div>
                                    <div class="admin-edit-section__title">轮播基础配置</div>
                                    <div class="admin-edit-section__desc">设置展示模式、高度、指示器与自动播放规则。</div>
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
                                    <div class="admin-edit-section__desc">维护图片或视频轮播素材并支持排序。</div>
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
                                                v-if="row.type === 1"
                                                :src="row.file_url"
                                                fit="cover"
                                                style="width: 80px; height: 60px"
                                                :preview-src-list="[row.file_url]"
                                            />
                                            <div v-else class="relative">
                                                <el-image
                                                    :src="row.cover_url"
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
                                            <el-tag :type="row.type === 1 ? 'success' : 'warning'">
                                                {{ row.type === 1 ? '图片' : '视频' }}
                                            </el-tag>
                                        </template>
                                    </el-table-column>
                                    <el-table-column label="视频自动播放" width="120">
                                        <template #default="{ row }">
                                            <span v-if="row.type === 2">
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
                        </div>
                    </el-tab-pane>
                </el-tabs>
            </el-form>
        </el-card>
        <footer-btns class="staff-edit-footer">
            <el-button type="primary" @click="handleSave">保存</el-button>
        </footer-btns>

        <!-- 套餐选择弹窗 -->
        <el-dialog v-model="showPackageDialog" title="选择套餐" width="600px" class="staff-edit-dialog">
            <el-table
                :data="availablePackages"
                @selection-change="handlePackageSelect"
                ref="packageTableRef"
            >
                <el-table-column type="selection" width="55" />
                <el-table-column label="套餐名称" prop="name" />
                <el-table-column label="分类" prop="category_name" />
                <el-table-column label="价格" prop="price" width="100">
                    <template #default="{ row }">¥{{ row.price }}</template>
                </el-table-column>
            </el-table>
            <template #footer>
                <el-button @click="showPackageDialog = false">取消</el-button>
                <el-button type="primary" @click="confirmPackageSelect">确定</el-button>
            </template>
        </el-dialog>

        <!-- 场次价格配置弹窗 -->
        <el-dialog v-model="showSlotPriceDialog" title="场次价格配置" width="600px" class="staff-edit-dialog">
            <div class="admin-edit-tip-block">
                <div class="admin-edit-tip-block__title">配置说明</div>
                <div class="admin-edit-muted">未填写的场次将使用默认价格</div>
            </div>
            <div class="slot-price-list mt-4">
                <div
                    v-for="(slot, index) in currentSlotPrices"
                    :key="index"
                    class="flex items-center gap-4 mb-4"
                >
                    <span class="w-16">{{ timeSlotLabelMap[slot.time_slot] || slot.time_slot }}</span>
                    <el-input-number
                        v-model="slot.price"
                        :min="0"
                        :precision="2"
                        placeholder="价格"
                        style="width: 150px"
                    />
                </div>
            </div>
            <template #footer>
                <el-button @click="showSlotPriceDialog = false">取消</el-button>
                <el-button type="primary" @click="confirmSlotPrice">确定</el-button>
            </template>
        </el-dialog>

        <!-- 创建专属套餐弹窗 -->
        <el-dialog
            v-model="showStaffPackageDialog"
            :title="isEditingStaffPackage ? '编辑专属套餐' : '创建专属套餐'"
            width="700px"
            class="staff-edit-dialog"
            @closed="resetStaffPackageForm"
        >
            <el-form
                ref="staffPackageFormRef"
                :model="staffPackageForm"
                :rules="staffPackageRules"
                label-width="100px"
            >
                <el-form-item label="套餐名称" prop="name">
                    <el-input v-model="staffPackageForm.name" placeholder="请输入套餐名称" />
                </el-form-item>
                <el-form-item label="所属分类" prop="category_id">
                    <el-cascader
                        v-model="staffPackageForm.category_id"
                        :options="optionsData.categories"
                        :props="{ value: 'id', label: 'name', checkStrictly: true, emitPath: false }"
                        placeholder="选择服务分类"
                        class="w-full"
                    />
                </el-form-item>
                <el-form-item label="默认价格" prop="price">
                    <el-input-number v-model="staffPackageForm.price" :min="0" :precision="2" class="w-full" />
                </el-form-item>
                <el-form-item label="原价" prop="original_price">
                    <el-input-number v-model="staffPackageForm.original_price" :min="0" :precision="2" class="w-full" />
                </el-form-item>
                <el-form-item label="预约类型" prop="booking_type">
                    <el-radio-group v-model="staffPackageForm.booking_type">
                        <el-radio :value="0">全天套餐</el-radio>
                        <el-radio :value="1">分场次套餐</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item v-if="staffPackageForm.booking_type === 1" label="允许场次" prop="allowed_time_slots">
                    <el-checkbox-group v-model="staffPackageForm.allowed_time_slots">
                        <el-checkbox
                            v-for="slot in timeSlotOptions"
                            :key="slot.value"
                            :value="slot.value"
                            :label="slot.label"
                        />
                    </el-checkbox-group>
                </el-form-item>
                <el-form-item v-if="staffPackageForm.booking_type === 1" label="场次价格">
                    <div class="w-full">
                        <div
                            v-for="(slot, index) in staffPackageForm.slot_prices"
                            :key="index"
                            class="flex items-center gap-4 mb-2"
                        >
                            <span class="w-16">{{ timeSlotLabelMap[slot.time_slot] || slot.time_slot }}</span>
                            <el-input-number
                                v-model="slot.price"
                                :min="0"
                                :precision="2"
                                placeholder="价格"
                                style="width: 120px"
                            />
                        </div>
                    </div>
                </el-form-item>
                <el-form-item label="套餐说明" prop="description">
                    <el-input
                        v-model="staffPackageForm.description"
                        type="textarea"
                        :rows="3"
                        placeholder="请输入套餐说明"
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="showStaffPackageDialog = false">取消</el-button>
                <el-button type="primary" @click="submitStaffPackage">
                    {{ isEditingStaffPackage ? '保存' : '创建' }}
                </el-button>
            </template>
        </el-dialog>

        <!-- 轮播图添加/编辑弹窗 -->
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
                :rules="bannerRules"
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
                <el-button type="primary" @click="submitBanner">确定</el-button>
            </template>
        </el-dialog>

        <!-- 后台账号凭证弹窗 -->
        <el-dialog
            v-model="credentialDialogVisible"
            title="后台账号信息"
            width="420px"
            class="staff-edit-dialog"
            @close="handleCredentialClose"
        >
            <div class="admin-edit-tip-block">
                <div class="admin-edit-tip-block__title">安全提示</div>
                <div class="admin-edit-muted">请及时保存账号与密码。</div>
            </div>
            <div class="space-y-3 mt-4">
                <el-alert type="warning" show-icon :closable="false">
                    <template #title>请及时保存账号与密码</template>
                    <template #default>关闭后密码不会再次展示，可通过“重置密码”重新生成。</template>
                </el-alert>
                <el-descriptions :column="1" border>
                    <el-descriptions-item label="账号">{{ credentialData.account }}</el-descriptions-item>
                    <el-descriptions-item label="密码">{{ credentialData.password }}</el-descriptions-item>
                </el-descriptions>
            </div>
            <template #footer>
                <el-button type="primary" @click="handleCredentialClose">我已记录</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup name="staffListsEdit">
import type { FormInstance } from 'element-plus'
import {
    staffAdd,
    staffEdit,
    staffDetail,
    staffResetAdminPassword,
    staffCreatePackage,
    staffUpdateStaffPackage,
    staffUpdatePackageConfig,
    staffDeletePackage,
    staffGetPackageConfig,
    staffBannerList,
    staffBannerAdd,
    staffBannerEdit,
    staffBannerDelete,
    staffBannerSort,
    staffBannerUpdateConfig
} from '@/api/staff'
import { getUserList, getUserDetail } from '@/api/consumer'
import { categoryTree, styleTagAll, packageAll } from '@/api/service'
import { useDictOptions } from '@/hooks/useDictOptions'
import useMultipleTabs from '@/hooks/useMultipleTabs'
import { ElMessage } from 'element-plus'
import feedback from '@/utils/feedback'
import { VideoPlay } from '@element-plus/icons-vue'

const route = useRoute()
const router = useRouter()
const { removeTab } = useMultipleTabs()
const formRef = shallowRef<FormInstance>()
const staffPackageFormRef = shallowRef<FormInstance>()
const bannerFormRef = shallowRef<FormInstance>()
const activeTab = ref('basic')
const showPackageDialog = ref(false)
const showSlotPriceDialog = ref(false)
const showStaffPackageDialog = ref(false)
const showBannerDialog = ref(false)
const credentialDialogVisible = ref(false)
const pendingBack = ref(false)
const isEditingStaffPackage = ref(false)
const isEditingBanner = ref(false)
const selectedPackages = ref<any[]>([])
const groupedTags = ref<Record<string, any[]>>({})
const staffPackages = ref<any[]>([])  // 专属套餐列表
const bannerList = ref<any[]>([])  // 轮播图列表
const currentSlotPriceIndex = ref(-1)
const currentSlotPrices = ref<{ time_slot: number; price: number | undefined }[]>([])
const userOptions = ref<{ id: number; label: string; raw: any }[]>([])
const userLoading = ref(false)
let userSearchTimer: number | undefined
const adminInfo = reactive({
    account: '',
    disable: 0
})
const credentialData = reactive({
    account: '',
    password: ''
})

const formData = reactive({
    id: '',
    user_id: 0,
    name: '',
    avatar: '',
    mobile: '',
    wecom_userid: '',
    category_id: '',
    price: 0,
    experience_years: 0,
    profile: '',
    service_desc: '',
    sort: 0,
    is_recommend: 0,
    status: 1,
    tag_ids: [] as number[],
    packages: [] as any[]
})

const showCredentials = (payload: any, backAfter = false) => {
    if (!payload?.admin_account || !payload?.admin_password) {
        return
    }
    credentialData.account = payload.admin_account
    credentialData.password = payload.admin_password
    credentialDialogVisible.value = true
    pendingBack.value = backAfter
}

const handleCredentialClose = () => {
    credentialDialogVisible.value = false
    if (pendingBack.value) {
        pendingBack.value = false
        removeTab()
        router.back()
    }
}

const validateUserId = (_rule: any, value: any, callback: (error?: Error) => void) => {
    if (route.query.id) {
        callback()
        return
    }
    const userId = Number(value)
    if (!userId || userId <= 0) {
        callback(new Error('请选择系统用户'))
        return
    }
    callback()
}

const buildUserLabel = (user: any) => {
    const name = user.nickname || user.account || `用户${user.id}`
    const mobile = user.mobile || user.account || '-'
    return `${name}（${mobile} / ID:${user.id}）`
}

const toUserOptions = (list: any[]) => {
    return list
        .filter((item) => item && item.id)
        .map((item) => ({
            id: item.id,
            label: buildUserLabel(item),
            raw: item
        }))
}

const ensureUserOption = (user: any) => {
    if (!user?.id) {
        return
    }
    if (!userOptions.value.some((item) => item.id === user.id)) {
        userOptions.value.unshift({
            id: user.id,
            label: buildUserLabel(user),
            raw: user
        })
    }
}

const fetchUserOptions = async (query: string) => {
    const keyword = query.trim()
    if (!keyword) {
        userOptions.value = []
        return
    }
    userLoading.value = true
    try {
        const res = await getUserList({ keyword, page_no: 1, page_size: 20 })
        const lists = res?.lists ?? res?.data?.lists ?? res?.data ?? res ?? []
        userOptions.value = Array.isArray(lists) ? toUserOptions(lists) : []
    } catch (e) {
        userOptions.value = []
    } finally {
        userLoading.value = false
    }
}

const remoteUserSearch = (query: string) => {
    if (userSearchTimer) {
        window.clearTimeout(userSearchTimer)
    }
    const keyword = query.trim()
    if (!keyword) {
        userOptions.value = []
        return
    }
    userSearchTimer = window.setTimeout(() => {
        fetchUserOptions(keyword)
    }, 300)
}

const loadCurrentUserOption = async () => {
    const userId = Number(formData.user_id)
    if (!userId) {
        return
    }
    try {
        const res = await getUserDetail({ id: userId })
        const user = res?.data ?? res
        ensureUserOption(user)
    } catch (e) {
        // 忽略加载失败，避免阻断编辑
    }
}

// 专属套餐表单
const staffPackageForm = reactive({
    id: 0,
    name: '',
    category_id: '',
    price: 0,
    original_price: 0,
    description: '',
    slot_prices: [] as any[],
    booking_type: 0,
    allowed_time_slots: [] as number[]
})

// 轮播图配置
const bannerConfig = reactive({
    banner_mode: 1,
    banner_small_height: 400,
    banner_large_height: 600,
    banner_indicator_style: 1,
    banner_autoplay: 1,
    banner_interval: 3000
})

// 轮播图表单
const bannerForm = reactive({
    id: 0,
    type: 1,
    file_url: '',
    cover_url: '',
    is_autoplay: 0
})

const rules = reactive({
    user_id: [{ validator: validateUserId, trigger: 'blur' }],
    name: [{ required: true, message: '请输入姓名', trigger: 'blur' }],
    category_id: [{ required: true, message: '请选择服务分类', trigger: 'change' }],
    price: [{ required: true, message: '请输入服务价格', trigger: 'blur' }]
})

const staffPackageRules = reactive({
    name: [{ required: true, message: '请输入套餐名称', trigger: 'blur' }],
    category_id: [{ required: true, message: '请选择所属分类', trigger: 'change' }],
    price: [{ required: true, message: '请输入套餐价格', trigger: 'blur' }],
    booking_type: [{ required: true, message: '请选择预约类型', trigger: 'change' }]
})

const bannerRules = reactive({
    type: [{ required: true, message: '请选择类型', trigger: 'change' }],
    file_url: [{ required: true, message: '请上传文件', trigger: 'change' }],
    cover_url: [
        {
            validator: (_rule: any, value: any, callback: (error?: Error) => void) => {
                if (bannerForm.type === 2 && !value) {
                    callback(new Error('视频需要上传封面图'))
                } else {
                    callback()
                }
            },
            trigger: 'change'
        }
    ]
})

const timeSlotOptions = [
    { value: 1, label: '早礼' },
    { value: 2, label: '午宴' },
    { value: 3, label: '晚宴' }
]

const timeSlotLabelMap: Record<number, string> = {
    1: '早礼',
    2: '午宴',
    3: '晚宴'
}

const tagTypeLabelMap: Record<number, string> = {
    1: '风格',
    2: '特长',
    3: '其他'
}

const normalizeAllowedSlots = (value: any) => {
    if (Array.isArray(value)) {
        return value.map((item) => Number(item)).filter((item) => !Number.isNaN(item))
    }
    if (typeof value === 'string' && value) {
        try {
            const parsed = JSON.parse(value)
            if (Array.isArray(parsed)) {
                return parsed.map((item) => Number(item)).filter((item) => !Number.isNaN(item))
            }
        } catch (e) {
            return []
        }
    }
    return []
}

const normalizeSlotPrices = (value: any) => {
    if (Array.isArray(value)) {
        return value
    }
    if (typeof value === 'string' && value) {
        try {
            const parsed = JSON.parse(value)
            if (Array.isArray(parsed)) {
                return parsed
            }
        } catch (e) {
            return []
        }
    }
    return []
}

const resolveAllowedSlots = (row: any) => {
    if (row.booking_type !== 1) {
        return []
    }
    const allowed = normalizeAllowedSlots(row.allowed_time_slots)
    return allowed.length ? allowed : timeSlotOptions.map((item) => item.value)
}

const buildSlotPriceRows = (allowedSlots: number[], slotPrices: any[]) => {
    return allowedSlots.map((slot) => {
        const matched = slotPrices.find((item: any) => Number(item.time_slot) === slot)
        return {
            time_slot: slot,
            price: matched?.price ?? undefined
        }
    })
}

const normalizeSlotPricePayload = (slots: { time_slot: number; price: number | undefined }[]) => {
    return slots
        .filter((slot) => slot.price !== undefined)
        .map((slot) => ({
            time_slot: Number(slot.time_slot),
            price: Number(slot.price)
        }))
}

const syncStaffPackageSlotPrices = () => {
    if (staffPackageForm.booking_type !== 1) {
        staffPackageForm.slot_prices = []
        return
    }
    const allowedSlots = staffPackageForm.allowed_time_slots.length
        ? staffPackageForm.allowed_time_slots
        : timeSlotOptions.map((item) => item.value)
    const slotPrices = normalizeSlotPrices(staffPackageForm.slot_prices)
    staffPackageForm.slot_prices = buildSlotPriceRows(allowedSlots, slotPrices)
}

const resetStaffPackageForm = () => {
    Object.assign(staffPackageForm, {
        id: 0,
        name: '',
        category_id: '',
        price: 0,
        original_price: 0,
        description: '',
        slot_prices: [],
        booking_type: 0,
        allowed_time_slots: []
    })
    isEditingStaffPackage.value = false
}
const { optionsData } = useDictOptions<{
    categories: any[]
    packages: any[]
}>({
    categories: {
        api: categoryTree
    },
    packages: {
        api: () => packageAll({ global_only: 1 })  // 只获取全局套餐
    }
})

// 可用套餐（未添加的全局套餐）
const availablePackages = computed(() => {
    const addedIds = formData.packages.map(p => p.package_id)
    return optionsData.packages?.filter(p => !addedIds.includes(p.id)) || []
})

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

// 获取标签列表（分组）
const getTags = async () => {
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

// 获取轮播图列表
const getBannerList = async () => {
    if (!route.query.id) return
    try {
        const res = await staffBannerList({ staff_id: route.query.id })
        bannerList.value = res || []
    } catch (e) {
        console.error('获取轮播图列表失败', e)
    }
}

// 加载轮播图配置
const loadBannerConfig = (data: any) => {
    if (data.banner_mode !== undefined) {
        bannerConfig.banner_mode = data.banner_mode
    }
    if (data.banner_small_height !== undefined) {
        bannerConfig.banner_small_height = data.banner_small_height
    }
    if (data.banner_large_height !== undefined) {
        bannerConfig.banner_large_height = data.banner_large_height
    }
    if (data.banner_indicator_style !== undefined) {
        bannerConfig.banner_indicator_style = data.banner_indicator_style
    }
    if (data.banner_autoplay !== undefined) {
        bannerConfig.banner_autoplay = data.banner_autoplay
    }
    if (data.banner_interval !== undefined) {
        bannerConfig.banner_interval = data.banner_interval
    }
}

// 获取详情
const getDetails = async () => {
    const data = await staffDetail({ id: route.query.id })
    Object.keys(formData).forEach((key) => {
        if (data[key] !== undefined) {
            (formData as any)[key] = data[key]
        }
    })
    adminInfo.account = data.admin_account || ''
    adminInfo.disable = Number(data.admin_disable || 0)
    await loadCurrentUserOption()
    await getTags()
    // 处理套餐数据格式
    if (data.packages) {
        formData.packages = data.packages.map((p: any) => {
            const bookingType = p.booking_type ?? p.package?.booking_type ?? 0
            let allowedSlots = normalizeAllowedSlots(p.allowed_time_slots ?? p.package?.allowed_time_slots)
            if (bookingType === 1 && allowedSlots.length === 0) {
                allowedSlots = timeSlotOptions.map((item) => item.value)
            }
            return {
                package_id: p.package_id,
                package_name: p.package?.name || '',
                price: p.price ?? p.package?.price ?? 0,
                original_price: p.original_price ?? p.package?.original_price ?? null,
                custom_price: p.custom_price,
                custom_slot_prices: p.custom_slot_prices || [],
                booking_type: bookingType,
                allowed_time_slots: allowedSlots,
                status: p.status ?? 1,
                is_new: false
            }
        })
    }
    // 获取专属套餐
    await getStaffPackages()
    // 加载轮播图配置
    loadBannerConfig(data)
    // 获取轮播图列表
    await getBannerList()
}

// 获取专属套餐列表
const getStaffPackages = async () => {
    if (!route.query.id) return
    try {
        const res = await staffGetPackageConfig({ 
            staff_id: route.query.id, 
            include_global: false 
        })
        staffPackages.value = res.staff_packages || []
    } catch (e) {
        console.error('获取专属套餐失败', e)
    }
}

const handlePackageSelect = (selection: any[]) => {
    selectedPackages.value = selection
}

const confirmPackageSelect = () => {
    selectedPackages.value.forEach(pkg => {
        const bookingType = pkg.booking_type ?? 0
        let allowedSlots = normalizeAllowedSlots(pkg.allowed_time_slots)
        if (bookingType === 1 && allowedSlots.length === 0) {
            allowedSlots = timeSlotOptions.map((item) => item.value)
        }
        formData.packages.push({
            package_id: pkg.id,
            package_name: pkg.name,
            price: pkg.price || 0,
            original_price: pkg.original_price || null,
            custom_price: null,
            custom_slot_prices: [],
            booking_type: bookingType,
            allowed_time_slots: allowedSlots,
            status: 1,
            is_new: true
        })
    })
    showPackageDialog.value = false
    selectedPackages.value = []
}

const removePackage = (index: number) => {
    formData.packages.splice(index, 1)
}

const savePackageConfig = async (row: any) => {
    if (!route.query.id) {
        ElMessage.error('请先保存人员基本信息')
        return
    }
    if (row.is_new) {
        ElMessage.warning('新关联套餐请先点击页面底部保存')
        return
    }
    const allowedSlots = row.booking_type === 1 ? normalizeAllowedSlots(row.allowed_time_slots) : []
    if (row.booking_type === 1 && allowedSlots.length === 0) {
        ElMessage.error('请选择允许场次')
        return
    }
    const slotPricesPayload = row.booking_type === 1
        ? normalizeSlotPricePayload(normalizeSlotPrices(row.custom_slot_prices))
        : []
    for (const slot of slotPricesPayload) {
        if (Number.isNaN(slot.price) || slot.price < 0) {
            ElMessage.error('价格不能为负数')
            return
        }
    }
    try {
        await staffUpdatePackageConfig({
            staff_id: route.query.id,
            package_id: row.package_id,
            price: row.price,
            original_price: row.original_price,
            custom_price: row.custom_price,
            custom_slot_prices: slotPricesPayload,
            booking_type: row.booking_type ?? 0,
            allowed_time_slots: allowedSlots,
            status: row.status ?? 1
        })
        row.is_new = false
        ElMessage.success('保存成功')
    } catch (e: any) {
        ElMessage.error(e.message || '保存失败')
    }
}

// 场次价格相关
const openSlotPriceDialog = (index: number) => {
    const row = formData.packages[index]
    if (row.booking_type !== 1) {
        ElMessage.warning('全天套餐无需配置场次价格')
        return
    }
    currentSlotPriceIndex.value = index
    const allowedSlots = resolveAllowedSlots(row)
    const slotPrices = normalizeSlotPrices(row.custom_slot_prices)
    currentSlotPrices.value = buildSlotPriceRows(allowedSlots, slotPrices)
    showSlotPriceDialog.value = true
}

const confirmSlotPrice = () => {
    const payload = normalizeSlotPricePayload(currentSlotPrices.value)
    for (const slot of payload) {
        if (Number.isNaN(slot.price) || slot.price < 0) {
            ElMessage.error('价格不能为负数')
            return
        }
    }
    formData.packages[currentSlotPriceIndex.value].custom_slot_prices = payload
    showSlotPriceDialog.value = false
}

const handlePackageBookingTypeChange = (row: any) => {
    if (row.booking_type === 0) {
        row.allowed_time_slots = []
        row.custom_slot_prices = []
        return
    }
    if (!row.allowed_time_slots || row.allowed_time_slots.length === 0) {
        row.allowed_time_slots = timeSlotOptions.map((item) => item.value)
    }
}

const openCreateStaffPackage = () => {
    resetStaffPackageForm()
    showStaffPackageDialog.value = true
}

const openEditStaffPackage = (row: any) => {
    resetStaffPackageForm()
    isEditingStaffPackage.value = true
    staffPackageForm.id = row.id
    staffPackageForm.name = row.name || ''
    staffPackageForm.category_id = row.category_id || ''
    staffPackageForm.price = row.price || 0
    staffPackageForm.original_price = row.original_price || 0
    staffPackageForm.description = row.description || ''
    staffPackageForm.booking_type = row.booking_type ?? 0
    staffPackageForm.allowed_time_slots = normalizeAllowedSlots(row.allowed_time_slots)
    staffPackageForm.slot_prices = normalizeSlotPrices(row.slot_prices)
    if (staffPackageForm.booking_type === 1 && staffPackageForm.allowed_time_slots.length === 0) {
        staffPackageForm.allowed_time_slots = timeSlotOptions.map((item) => item.value)
    }
    syncStaffPackageSlotPrices()
    showStaffPackageDialog.value = true
}

const deleteStaffPackage = async (row: any) => {
    if (!route.query.id) {
        ElMessage.error('请先保存人员基本信息')
        return
    }
    await feedback.confirm(`确定要删除专属套餐「${row.name || ''}」吗？`)
    try {
        await staffDeletePackage({ staff_id: route.query.id, package_id: row.id })
        ElMessage.success('删除成功')
        await getStaffPackages()
    } catch (e: any) {
        ElMessage.error(e.message || '删除失败')
    }
}

// 创建/编辑专属套餐
const submitStaffPackage = async () => {
    await staffPackageFormRef.value?.validate()
    if (!route.query.id) {
        ElMessage.error('请先保存人员基本信息')
        return
    }
    if (staffPackageForm.booking_type === 1 && staffPackageForm.allowed_time_slots.length === 0) {
        ElMessage.error('请选择允许场次')
        return
    }
    if (staffPackageForm.booking_type === 0) {
        staffPackageForm.allowed_time_slots = []
    }
    const slotPricesPayload = staffPackageForm.booking_type === 1
        ? normalizeSlotPricePayload(staffPackageForm.slot_prices)
        : []
    for (const slot of slotPricesPayload) {
        if (Number.isNaN(slot.price) || slot.price < 0) {
            ElMessage.error('价格不能为负数')
            return
        }
    }
    try {
        const payload = {
            staff_id: route.query.id,
            ...staffPackageForm,
            slot_prices: slotPricesPayload
        }
        if (isEditingStaffPackage.value) {
            await staffUpdateStaffPackage({
                ...payload,
                package_id: staffPackageForm.id
            })
            ElMessage.success('更新成功')
        } else {
            await staffCreatePackage(payload)
            ElMessage.success('创建成功')
        }
        showStaffPackageDialog.value = false
        resetStaffPackageForm()
        // 刷新专属套餐列表
        await getStaffPackages()
    } catch (e: any) {
        ElMessage.error(e.message || (isEditingStaffPackage.value ? '更新失败' : '创建失败'))
    }
}

const handleSave = async () => {
    await formRef.value?.validate()
    const invalidPackage = formData.packages.find((pkg) => pkg.booking_type === 1 && (!pkg.allowed_time_slots || pkg.allowed_time_slots.length === 0))
    if (invalidPackage) {
        ElMessage.error('请选择允许场次')
        return
    }
    const params = {
        ...formData,
        packages: formData.packages.map(p => ({
            package_id: p.package_id,
            price: p.price,
            original_price: p.original_price,
            custom_price: p.custom_price,
            custom_slot_prices: p.custom_slot_prices || [],
            booking_type: p.booking_type ?? 0,
            allowed_time_slots: p.booking_type === 1 ? (p.allowed_time_slots || []) : [],
            status: p.status ?? 1
        }))
    }
    if (route.query.id) {
        await staffEdit(params)
        removeTab()
        router.back()
        return
    }

    const res = await staffAdd(params)
    if (res?.admin_account && res?.admin_password) {
        adminInfo.account = res.admin_account
        adminInfo.disable = 0
        showCredentials(res, true)
        return
    }
    removeTab()
    router.back()
}

const handleResetAdminPassword = async () => {
    if (!route.query.id) {
        ElMessage.error('请先保存人员基本信息')
        return
    }
    await feedback.confirm('确定要重置后台账号密码？')
    const res = await staffResetAdminPassword({ id: Number(route.query.id) })
    if (res?.admin_account && res?.admin_password) {
        adminInfo.account = res.admin_account
        adminInfo.disable = 0
        showCredentials(res)
    }
}

if (route.query.id) {
    getDetails()
}

watch(
    () => staffPackageForm.booking_type,
    (value) => {
        if (value === 0) {
            staffPackageForm.allowed_time_slots = []
            syncStaffPackageSlotPrices()
            return
        }
        if (!staffPackageForm.allowed_time_slots || staffPackageForm.allowed_time_slots.length === 0) {
            staffPackageForm.allowed_time_slots = timeSlotOptions.map((item) => item.value)
        }
        syncStaffPackageSlotPrices()
    }
)

watch(
    () => staffPackageForm.allowed_time_slots,
    () => {
        if (staffPackageForm.booking_type === 1) {
            syncStaffPackageSlotPrices()
        }
    },
    { deep: true }
)

watch(
    () => formData.category_id,
    () => {
        getTags()
    }
)

// ==================== 轮播图相关方法 ====================

// 打开添加轮播图弹窗
const openAddBanner = () => {
    if (!route.query.id) {
        ElMessage.error('请先保存人员基本信息')
        return
    }
    resetBannerForm()
    showBannerDialog.value = true
}

// 打开编辑轮播图弹窗
const openEditBanner = (row: any) => {
    resetBannerForm()
    isEditingBanner.value = true
    bannerForm.id = row.id
    bannerForm.type = row.type
    bannerForm.file_url = row.file_url
    bannerForm.cover_url = row.cover_url || ''
    bannerForm.is_autoplay = row.is_autoplay || 0
    showBannerDialog.value = true
}

// 重置轮播图表单
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

// 提交轮播图
const submitBanner = async () => {
    await bannerFormRef.value?.validate()
    
    if (!route.query.id) {
        ElMessage.error('请先保存人员基本信息')
        return
    }

    try {
        const params = {
            ...bannerForm,
            staff_id: route.query.id
        }

        if (isEditingBanner.value) {
            await staffBannerEdit(params)
            ElMessage.success('编辑成功')
        } else {
            await staffBannerAdd(params)
            ElMessage.success('添加成功')
        }

        showBannerDialog.value = false
        await getBannerList()
    } catch (e: any) {
        ElMessage.error(e.message || (isEditingBanner.value ? '编辑失败' : '添加失败'))
    }
}

// 删除轮播图
const deleteBanner = async (row: any) => {
    await feedback.confirm(`确定要删除该轮播图吗？`)
    try {
        await staffBannerDelete({ id: row.id })
        ElMessage.success('删除成功')
        await getBannerList()
    } catch (e: any) {
        ElMessage.error(e.message || '删除失败')
    }
}

// 移动轮播图
const moveBanner = async (index: number, direction: 'up' | 'down') => {
    const list = [...bannerList.value]
    const targetIndex = direction === 'up' ? index - 1 : index + 1

    if (targetIndex < 0 || targetIndex >= list.length) {
        return
    }

    // 交换位置
    ;[list[index], list[targetIndex]] = [list[targetIndex], list[index]]

    // 更新排序
    const sortData = list.map((item, idx) => ({
        id: item.id,
        sort: idx
    }))

    try {
        await staffBannerSort({
            staff_id: route.query.id,
            sort_data: sortData
        })
        ElMessage.success('排序成功')
        await getBannerList()
    } catch (e: any) {
        ElMessage.error(e.message || '排序失败')
    }
}

// 保存轮播图配置
const saveBannerConfig = async () => {
    if (!route.query.id) {
        ElMessage.error('请先保存人员基本信息')
        return
    }

    try {
        await staffBannerUpdateConfig({
            staff_id: route.query.id,
            ...bannerConfig
        })
        ElMessage.success('配置保存成功')
    } catch (e: any) {
        ElMessage.error(e.message || '配置保存失败')
    }
}

</script>

<style lang="scss" scoped>
.staff-edit {
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

    :deep(.staff-edit-footer .footer-btns__content) {
        border-top: 1px solid #e8ecf3;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(8px);
    }
}

@media (max-width: 1200px) {
    .staff-edit {
        .staff-edit-grid {
            grid-template-columns: minmax(0, 1fr);
        }
    }
}
</style>
