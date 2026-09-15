<!-- 个人资料 -->
<template>
    <div class="user-setting">
        <el-card class="!border-none" shadow="never">
            <el-form
                ref="formRef"
                class="ls-form"
                :model="formData"
                :rules="rules"
                label-width="100px"
            >
                <el-form-item label="头像：" prop="avatar">
                    <material-picker v-model="formData.avatar" :limit="1" />
                </el-form-item>

                <el-form-item label="账号：" prop="account">
                    <div class="w-80">
                        <el-input v-model="formData.account" disabled />
                    </div>
                </el-form-item>

                <el-form-item label="名称：" prop="name">
                    <div class="w-80">
                        <el-input v-model="formData.name" placeholder="请输入名称" />
                    </div>
                </el-form-item>

                <el-form-item label="当前密码：" prop="password_old">
                    <div class="w-80">
                        <el-input
                            v-model.trim="formData.password_old"
                            placeholder="修改密码时必填, 不修改密码时留空"
                            type="password"
                            show-password
                        />
                    </div>
                </el-form-item>

                <el-form-item label="新的密码：" prop="password">
                    <div class="w-80">
                        <el-input
                            v-model.trim="formData.password"
                            placeholder="修改密码时必填, 不修改密码时留空"
                            type="password"
                            show-password
                        />
                    </div>
                </el-form-item>

                <el-form-item label="确定密码：" prop="password_confirm">
                    <div class="w-80">
                        <el-input
                            v-model.trim="formData.password_confirm"
                            placeholder="修改密码时必填, 不修改密码时留空"
                            type="password"
                            show-password
                        />
                    </div>
                </el-form-item>
            </el-form>
        </el-card>
        <section class="p-6">
            <h3 class="mb-4 font-medium">小程序账号关联</h3>
            <div v-if="binding.user_id">已关联用户：{{ binding.user_id }}</div>
            <p class="my-3">绑定、换绑和解绑请联系授权管理员处理。</p>
            <el-button @click="loadBinding">刷新关联状态</el-button>
        </section>
        <footer-btns>
            <el-button type="primary" @click="handleSubmit">保存</el-button>
        </footer-btns>
    </div>
</template>

<script setup lang="ts" name="userSetting">
import request from '@/utils/request'
import type { FormInstance } from 'element-plus'

import { logout as logoutApi, setUserInfo } from '@/api/user'
import { PageEnum } from '@/enums/pageEnum'
import useUserStore from '@/stores/modules/user'
import { clearAuthInfo } from '@/utils/auth'
import feedback from '@/utils/feedback'

const binding = reactive({ user_id: 0, binding_code: '', candidate_user_id: 0 })
const loadBinding = async () => Object.assign(binding, await request.get({ url: '/auth.admin/bindingEntry' }))
const formRef = ref<FormInstance>()
const userStore = useUserStore()
const route = useRoute()
const router = useRouter()
const isForcePasswordReset = computed(() => userStore.forcePasswordReset || route.query.force === '1')
// 表单数据
const formData = reactive({
    avatar: '', // 头像
    account: '', // 账号
    name: '', // 名称
    password_old: '', // 当前密码
    password: '', // 新的密码
    password_confirm: '' // 确定密码
})

// 表单校验规则
const rules = reactive<object>({
    avatar: [
        {
            required: true,
            message: '头像不能为空',
            trigger: ['change']
        }
    ],
    name: [
        {
            required: true,
            message: '请输入名称',
            trigger: ['blur']
        }
    ]
})

// 获取个人设置
const getUser = async () => {
    const userInfo = userStore.userInfo
    for (const key in formData) {
        //@ts-ignore
        formData[key] = userInfo[key]
    }
}

// 设置个人设置
const setUser = async () => {
    if (formData.password_old || formData.password || formData.password_confirm) {
        if (!formData.password_old) {
            return feedback.msgError('请输入当前密码')
        }

        if (!formData.password) {
            return feedback.msgError('请输入新的密码')
        }

        if (!formData.password_confirm) {
            return feedback.msgError('请输入确定密码')
        }

        if (formData.password_confirm != formData.password) {
            return feedback.msgError('两次输入的密码不一样')
        }
    }

    if (isForcePasswordReset.value && (!formData.password_old || !formData.password || !formData.password_confirm)) {
        return feedback.msgError('当前账号必须先重置密码')
    }

    if (formData.password_old && formData.password && formData.password_confirm) {
        if (formData.password_old.length < 6 || formData.password_old.length > 32) {
            return feedback.msgError('密码长度在6到32之间')
        }
        if (formData.password.length < 6 || formData.password.length > 32) {
            return feedback.msgError('密码长度在6到32之间')
        }
        if (formData.password_confirm.length < 6 || formData.password_confirm.length > 32) {
            return feedback.msgError('密码长度在6到32之间')
        }
    }
    const hasChangedPassword = !!(
        formData.password_old &&
        formData.password &&
        formData.password_confirm
    )
    await setUserInfo(formData)
    if (hasChangedPassword) {
        feedback.msgSuccess('密码修改成功，请重新登录')
        try {
            await logoutApi()
        } catch (error) {
            // 密码修改后本地必须退出，不因服务端退出接口失败阻断重新登录。
        } finally {
            clearAuthInfo()
            await router.replace(PageEnum.LOGIN)
        }
        return
    }
    await userStore.getUserInfo()
    feedback.msgSuccess('保存成功')
}

// 提交数据
const handleSubmit = async () => {
    await formRef.value?.validate()
    await setUser()
}

getUser()
</script>

<style lang="scss" scoped></style>
