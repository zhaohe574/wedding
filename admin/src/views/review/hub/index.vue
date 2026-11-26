<template>
    <admin-hub-tabs title="评价管理" :tabs="tabs" default-tab="lists" />
</template>

<script lang="ts" setup name="reviewHub">
/**
 * 评价管理 Hub（批次 3a 聚合评价域页面）。
 * Tab 按 perms 过滤；子页面通过 tabs 数组挂载，原路由保持不变。
 */
import ReviewListsPage from '@/views/review/lists/index.vue'
import ReviewTagPage from '@/views/review/tag/index.vue'

const tabs = [
    {
        name: 'lists',
        label: '评价列表',
        perms: ['growth.review/lists'],
        component: ReviewListsPage
    },
    {
        name: 'tag',
        label: '评价标签',
        perms: ['growth.reviewTag/lists'],
        component: ReviewTagPage
    }
]
</script>
