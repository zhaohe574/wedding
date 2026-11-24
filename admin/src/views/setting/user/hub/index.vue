<template>
    <admin-hub-tabs
        title="用户与登录"
        description="配置用户登录注册策略、微信授权及新用户默认头像"
        :tabs="tabs"
        default-tab="login"
    />
</template>

<script lang="ts" setup name="settingUserHub">
/**
 * 用户与登录 Hub（批次 3a 聚合用户设置域页面）。
 * Tab 按 perms 过滤；子页面通过 tabs 数组挂载，原路由保持不变。
 */
import LoginRegisterPage from '@/views/setting/user/login_register.vue'
import UserSetupPage from '@/views/setting/user/setup.vue'

const tabs = [
    {
        name: 'login',
        label: '登录与注册',
        perms: ['setting.user.user/getRegisterConfig'],
        component: LoginRegisterPage
    },
    {
        name: 'setup',
        label: '默认头像与基础',
        perms: ['setting.user.user/getConfig'],
        component: UserSetupPage
    }
]
</script>
