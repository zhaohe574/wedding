<!-- 网站信息 -->
<template>
    <div class="login-register">
        <el-form ref="formRef" :rules="rules" :model="formData" label-width="120px">
            <el-card shadow="never" class="!border-none">
                <div class="font-medium mb-7">通用设置</div>

                <el-form-item label="登录方式" prop="login_way">
                    <div>
                        <el-checkbox-group v-model="formData.login_way">
                            <el-checkbox value="1">账号密码登录</el-checkbox>
                            <el-checkbox value="2">手机验证码登录</el-checkbox>
                        </el-checkbox-group>
                        <div class="form-tips">
                            H5/PC 端本地登录方式，至少选择一项；小程序端可仅展示微信登录
                        </div>
                    </div>
                </el-form-item>

                <el-form-item label="强制绑定手机" prop="coerce_mobile">
                    <div>
                        <el-switch
                            v-model="formData.coerce_mobile"
                            :active-value="1"
                            :inactive-value="0"
                        />
                        <span class="mt-1 ml-2">{{
                            formData.coerce_mobile ? '开启' : '关闭'
                        }}</span>

                        <div class="form-tips">
                            1、如果开启，则新用户在注册完成之后要强制绑定手机号<br />
                            2、老用户登录时如果检测到没有绑定手机，则要重新绑定手机号
                        </div>
                    </div>
                </el-form-item>

                <el-form-item label="政策协议" prop="login_agreement">
                    <div>
                        <el-switch
                            v-model="formData.login_agreement"
                            :active-value="1"
                            :inactive-value="0"
                        />
                        <span class="mt-1 ml-2">
                            {{ formData.login_agreement ? '开启' : '关闭' }}
                        </span>

                        <div class="form-tips">登录/注册会员时，是否显示服务协议和隐私政策</div>
                    </div>
                </el-form-item>
            </el-card>

            <el-card shadow="never" class="!border-none mt-4">
                <div class="font-medium mb-7">第三方设置</div>

                <el-form-item label="第三方登录" prop="third_auth">
                    <div>
                        <el-switch
                            v-model="formData.third_auth"
                            :active-value="1"
                            :inactive-value="0"
                        />
                        <span class="mt-1 ml-2">
                            {{ formData.third_auth ? '开启' : '关闭' }}
                        </span>

                        <div class="form-tips">登录时支持第三方登录，新用户授权即自动注册账号</div>

                        <div>
                            <el-checkbox
                                v-model="formData.wechat_auth"
                                :true-value="1"
                                :false-value="0"
                            >
                                微信登录
                            </el-checkbox>
                        </div>
                    </div>
                </el-form-item>

                <el-form-item label="微信开放平台">
                    <div>
                        <a href="https://open.weixin.qq.com/" target="_blank">
                            <el-button type="primary" link class="underline">
                                前往微信开放平台
                            </el-button>
                        </a>

                        <div class="form-tips">
                            1、在各渠道使用微信授权登录时，强烈建议配置微信开放平台<br />
                            2、微信开放平台关联公众号、小程序和APP后，可实现各端用户账号统一，识别买家唯一微信身份<br />
                            3、没有配置微信开放平台，同一微信号会生成多个用户，配置微信开放平台后已生成的用户账号无法合并
                        </div>
                    </div>
                </el-form-item>
            </el-card>
        </el-form>

        <footer-btns v-perms="['setting.user.user/setRegisterConfig']">
            <el-button type="primary" @click="handleSubmit">保存</el-button>
        </footer-btns>
    </div>
</template>

<script lang="ts" setup name="loginRegister">
import type { FormInstance, FormRules } from 'element-plus'

import type { LoginSetup } from '@/api/setting/user'
import { getLogin, setLogin } from '@/api/setting/user'

const formRef = ref<FormInstance>()

const createDefaultLoginSetup = (): LoginSetup => ({
    login_way: [],
    coerce_mobile: 0,
    login_agreement: 0,
    third_auth: 0,
    wechat_auth: 0,
    qq_auth: 0
})

const normalizeLoginWay = (value: unknown): string[] => {
    if (Array.isArray(value)) {
        return value.map((item) => String(item)).filter((item) => item !== '')
    }

    if (typeof value === 'string') {
        if (!value.trim()) {
            return []
        }

        try {
            const parsedValue = JSON.parse(value)
            if (Array.isArray(parsedValue)) {
                return parsedValue.map((item) => String(item)).filter((item) => item !== '')
            }
        } catch (error) {
            return value
                .split(',')
                .map((item) => item.trim())
                .filter((item) => item !== '')
        }
    }

    if (value === null || value === undefined || value === '') {
        return []
    }

    return [String(value)]
}

const normalizeToggleValue = (value: unknown): number => (Number(value) === 1 ? 1 : 0)

const normalizeLoginSetup = (value?: Partial<LoginSetup>): LoginSetup => {
    const defaultValue = createDefaultLoginSetup()

    return {
        login_way: normalizeLoginWay(value?.login_way ?? defaultValue.login_way),
        coerce_mobile: normalizeToggleValue(value?.coerce_mobile ?? defaultValue.coerce_mobile),
        login_agreement: normalizeToggleValue(
            value?.login_agreement ?? defaultValue.login_agreement
        ),
        third_auth: normalizeToggleValue(value?.third_auth ?? defaultValue.third_auth),
        wechat_auth: normalizeToggleValue(value?.wechat_auth ?? defaultValue.wechat_auth),
        qq_auth: normalizeToggleValue(value?.qq_auth ?? defaultValue.qq_auth)
    }
}

// 表单数据
const formData = reactive<LoginSetup>(createDefaultLoginSetup())

// 表单验证
const rules = reactive<FormRules<LoginSetup>>({
    login_way: [
        {
            required: true,
            validator: (_rule, value, callback) => {
                if (!Array.isArray(value) || value.length === 0) {
                    callback(new Error('登录方式至少选择一项！'))
                    return
                }

                callback()
            },
            trigger: 'change'
        }
    ],
    coerce_mobile: [{ required: true, trigger: 'change' }],
    login_agreement: [{ required: true, trigger: 'change' }],
    third_auth: [{ required: true, trigger: 'change' }]
})

// 获取登录注册数据
const getData = async () => {
    try {
        const data = normalizeLoginSetup(await getLogin())
        Object.assign(formData, data)
    } catch (error) {
    }
}

// 保存登录注册数据
const handleSubmit = async () => {
    await formRef.value?.validate()
    try {
        await setLogin(normalizeLoginSetup(formData))
        await getData()
    } catch (error) {
    }
}

getData()
</script>

<style lang="scss" scoped></style>
