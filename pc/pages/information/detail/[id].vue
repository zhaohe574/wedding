<template>
    <div
        v-if="pending || error || !newsDetail"
        class="py-10 text-center"
        role="status"
    >
        <p>
            {{
                pending
                    ? '正在加载资讯…'
                    : '资讯暂时无法加载，可能已下架或网络异常。'
            }}
        </p>
        <el-button v-if="!pending" class="mt-4" @click="refresh()"
            >重新加载</el-button
        >
        <NuxtLink class="ml-4" to="/information">返回资讯中心</NuxtLink>
    </div>
    <div v-else>
        <div class="flex items-center">
            当前位置：
            <el-breadcrumb separator="/">
                <el-breadcrumb-item :to="{ path: '/information' }">
                    资讯中心
                </el-breadcrumb-item>
                <el-breadcrumb-item
                    :to="{
                        path: `/information/default`,
                        query: {
                            cid: newsDetail.cid,
                            name: newsDetail.cate_name
                        }
                    }"
                >
                    {{ newsDetail.cate_name }}
                </el-breadcrumb-item>
                <el-breadcrumb-item>文章详情</el-breadcrumb-item>
            </el-breadcrumb>
        </div>
        <div class="flex gap-4 mt-5">
            <div class="w-[750px] bg-body rounded-[8px] flex-none p-5">
                <div class="border-b border-br pb-4">
                    <span class="font-medium text-[22px]">
                        {{ newsDetail.title }}
                    </span>
                    <div
                        class="mt-3 text-tx-secondary flex items-center flex-wrap"
                    >
                        <span v-if="newsDetail.author">
                            {{ newsDetail.author }}&nbsp;|&nbsp;
                        </span>
                        <span class="mr-5">{{ newsDetail.create_time }}</span>
                        <div class="flex items-center">
                            <Icon name="el-icon-View" />
                            <span>&nbsp;{{ newsDetail.click }}人浏览</span>
                        </div>
                    </div>
                </div>
                <div
                    v-if="newsDetail.abstract"
                    class="bg-page mt-4 p-3 rounded-lg"
                >
                    摘要：{{ newsDetail.abstract }}
                </div>
                <div class="py-4" v-html="newsDetail.content"></div>
                <div class="border-t border-br mt-[30px]">
                    <div class="mt-5 flex">
                        <span class="text-tx-regular">上一篇：</span>
                        <NuxtLink
                            v-if="newsDetail.last?.id"
                            class="flex-1 hover:underline"
                            :to="`/information/detail/${newsDetail.last?.id}`"
                        >
                            {{ newsDetail.last?.title }}
                        </NuxtLink>
                        <span v-else> 暂无相关文章 </span>
                    </div>
                    <div class="mt-5 flex">
                        <span class="text-tx-regular">下一篇：</span>
                        <NuxtLink
                            v-if="newsDetail.next?.id"
                            class="flex-1 hover:underline"
                            :to="`/information/detail/${newsDetail.next?.id}`"
                        >
                            {{ newsDetail.next?.title }}
                        </NuxtLink>
                        <span v-else> 暂无相关文章 </span>
                    </div>
                </div>
            </div>
            <InformationCard
                class="flex-1"
                header="相关资讯"
                :data="newsDetail.new"
                :only-title="false"
                image-size="mini"
                :show-author="false"
                :show-desc="false"
                :show-click="false"
                :border="false"
                :title-line="2"
                source="new"
            />
        </div>
    </div>
</template>
<script lang="ts" setup>
import { ElBreadcrumb, ElBreadcrumbItem } from 'element-plus'
import { getArticleDetail } from '~~/api/news'
const route = useRoute()
const {
    data: newsDetail,
    pending,
    error,
    refresh
} = await useAsyncData(
    () =>
        getArticleDetail({
            id: route.params.id,
            source: route.params.source
        }),
    {
        watch: [() => route.params.id]
    }
)
</script>
<style lang="scss" scoped></style>
