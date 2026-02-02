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
                <el-form-item label="管理员看板入口">
                    <div class="flex items-center gap-3">
                        <el-switch v-model="formData.admin_dashboard" :active-value="1" :inactive-value="0" />
                        <span class="text-gray-500 text-xs">移动端管理员看板入口</span>
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
    admin_dashboard: 1
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
