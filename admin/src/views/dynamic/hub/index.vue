<template>
    <admin-hub-tabs
        title="动态与评论"
        description="管理品牌动态内容发布与用户评论审核"
        :tabs="tabs"
        default-tab="lists"
    />
</template>

<script lang="ts" setup name="dynamicHub">
/**
 * 动态与评论 Hub（批次 3a 聚合动态域页面）。
 * Tab 按 perms 过滤；子页面通过 tabs 数组挂载，原路由保持不变。
 */
import CommentReviewPage from '@/views/dynamic/comment/review.vue'
import DynamicListsPage from '@/views/dynamic/lists/index.vue'

const tabs = [
    {
        name: 'lists',
        label: '动态列表',
        perms: ['growth.dynamic/lists'],
        component: DynamicListsPage
    },
    {
        name: 'comment',
        label: '评论审核',
        perms: ['growth.dynamicComment/reviewList'],
        component: CommentReviewPage
    }
]
</script>
