<template>
    <admin-hub-tabs
        title="文章资讯"
        description="维护品牌专栏文章与文章栏目分类"
        :tabs="tabs"
        default-tab="lists"
    />
</template>

<script lang="ts" setup name="articleHub">
/**
 * 文章资讯 Hub（批次 3a 聚合文章域页面）。
 * Tab 按 perms 过滤；子页面通过 tabs 数组挂载，原路由保持不变。
 */
import ArticleColumnPage from '@/views/article/column/index.vue'
import ArticleListsPage from '@/views/article/lists/index.vue'

const tabs = [
    {
        name: 'lists',
        label: '文章管理',
        perms: ['article.article/lists'],
        component: ArticleListsPage
    },
    {
        name: 'column',
        label: '文章栏目',
        perms: ['article.articleCate/lists'],
        component: ArticleColumnPage
    }
]
</script>
