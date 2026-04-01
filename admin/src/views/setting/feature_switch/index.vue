<template>
    <div class="feature-switch">
        <el-card shadow="never" class="!border-none">
            <div class="font-medium mb-7">功能开关</div>
            <el-form ref="formRef" :model="formData" label-width="140px">
                <el-form-item label="服务人员中心">
                    <div class="flex items-center gap-3">
                        <el-switch v-model="formData.staff_center" :active-value="1" :inactive-value="0" />
                        <span class="text-gray-500 text-xs">移动端服务人员入口与接口</span>
                    </div>
                </el-form-item>
                <el-form-item label="服务人员后台账号">
                    <div class="flex items-center gap-3">
                        <el-switch v-model="formData.staff_admin" :active-value="1" :inactive-value="0" />
                        <span class="text-gray-500 text-xs">创建服务人员时同步生成后台账号</span>
                    </div>
                </el-form-item>
                <el-form-item label="服务人员标签审核">
                    <div class="flex items-center gap-3">
                        <el-switch v-model="formData.staff_tag_review_enabled" :active-value="1" :inactive-value="0" />
                        <span class="text-gray-500 text-xs">开启后，服务人员自助修改标签需要管理员审核</span>
                    </div>
                </el-form-item>
                <el-form-item label="管理员看板入口">
                    <div class="flex items-center gap-3">
                        <el-switch v-model="formData.admin_dashboard" :active-value="1" :inactive-value="0" />
                        <span class="text-gray-500 text-xs">移动端管理员看板入口</span>
                    </div>
                </el-form-item>
                <el-form-item label="看板可访问用户ID">
                    <div class="w-[420px] flex flex-col gap-2">
                        <el-input
                            v-model="formData.admin_dashboard_user_ids"
                            type="textarea"
                            :rows="3"
                            placeholder="请输入用户ID，多个用逗号分隔，例如：1,2,3"
                        />
                        <span class="text-gray-500 text-xs"
                            >仅白名单用户可直接访问管理员看板，未配置则默认无人可访问。</span
                        >
                    </div>
                </el-form-item>
                <el-form-item label="人员详情页风格">
                    <div class="flex flex-col gap-2">
                        <el-radio-group v-model="formData.staff_detail_style">
                            <el-radio label="classic">经典信息型</el-radio>
                            <el-radio label="immersive">沉浸视觉型</el-radio>
                            <el-radio label="conversion">高转化营销型</el-radio>
                        </el-radio-group>
                        <span class="text-gray-500 text-xs">控制移动端服务人员详情页展示风格</span>
                    </div>
                </el-form-item>
            </el-form>
        </el-card>

        <footer-btns v-perms="['setting.feature_switch/setConfig']">
            <el-button type="primary" @click="handleSubmit">保存</el-button>
        </footer-btns>
    </div>
</template>

<script lang="ts" setup name="featureSwitch">
import { getFeatureSwitchConfig, setFeatureSwitchConfig } from '@/api/setting/featureSwitch'

const formData = reactive({
    staff_center: 1,
    staff_admin: 1,
    staff_tag_review_enabled: 0,
    admin_dashboard: 1,
    admin_dashboard_user_ids: '',
    staff_detail_style: 'classic'
})

const getData = async () => {
    const data = await getFeatureSwitchConfig()
    if (data) {
        Object.assign(formData, data)
    }
}

const handleSubmit = async () => {
    await setFeatureSwitchConfig(formData)
    getData()
}

getData()
</script>

<style lang="scss" scoped></style>
