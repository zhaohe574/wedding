<template>
    <admin-hub-tabs title="服务套餐" :tabs="tabs" default-tab="package" />
</template>

<script lang="ts" setup name="servicePackageHub">
import AddonPage from '@/views/service/addon/index.vue'
import PackagePage from '@/views/service/package/index.vue'

const tabs = [
    {
        name: 'package',
        label: '人员套餐',
        perms: ['ops.package/lists'],
        component: PackagePage
    },
    {
        name: 'addon',
        label: '预约附加项',
        perms: ['service.addon/lists'],
        component: AddonPage
    }
]
</script>
