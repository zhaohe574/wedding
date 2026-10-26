<template>
    <footer class="layout-footer text-center bg-[#222222] py-[30px]">
        <div class="text-[#bebebe]">
            <!-- <NuxtLink> 关于我们 </NuxtLink>
            ｜ -->
            <NuxtLink :to="`/policy/${PolicyAgreementEnum.SERVICE}`">
                用户协议
            </NuxtLink>
            ｜
            <NuxtLink :to="`/policy/${PolicyAgreementEnum.PRIVACY}`">
                隐私政策
            </NuxtLink>
            <!--
                原「会员中心」链接指向 /user/info，但 PC 官网定位为展示型站点
                （无客户登录、个人中心、预约与付款），该路由并不存在，
                会被预渲染爬虫判定为死链，故移除。
            -->
        </div>
        <div class="mt-4 text-tx-secondary">
            <a
                class="mx-1 hover:underline"
                :href="item.value"
                target="_blank"
                v-for="item in appStore.getCopyrightConfig"
                :key="item.key"
            >
                {{ item.key }}
            </a>
        </div>
    </footer>
</template>
<script lang="ts" setup>
import { useAppStore } from '@/stores/app'
import { PolicyAgreementEnum } from '@/enums/appEnums'
const appStore = useAppStore()
</script>

<style lang="scss" scoped></style>
