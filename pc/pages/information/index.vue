<template>
    <div>
        <div class="text-4xl mb-5">资讯中心</div>
        <div class="flex flex-wrap gap-4">
            <InformationCard
                v-for="item in newsLists"
                style="width: calc(50% - 8px)"
                :key="item.id"
                :header="item.name"
                :data="item.article"
                :link="`/information/default?cid=${item.id}&name=${item.name}`"
            >
                <template #content="{ data }">
                    <div class="px-4 py-5">
                        <div class="flex gap-2.5">
                            <div
                                class="w-[180px] bg-page rounded overflow-hidden"
                                v-for="(item, index) in splitData(data)
                                    .topThree"
                                :key="item.id"
                            >
                                <InformationItems
                                    :index="index"
                                    :id="item.id"
                                    :title="item.title"
                                    :desc="item.desc"
                                    :click="item.click"
                                    :author="item.author"
                                    :create-time="item.create_time"
                                    :image="item.image || placeholder"
                                    :only-title="false"
                                    :border="false"
                                    :show-author="false"
                                    :show-desc="false"
                                    :show-time="false"
                                    :show-click="false"
                                    :is-horizontal="true"
                                >
                                    <template #title="{ title }">
                                        <span class="line-clamp-2">{{
                                            title
                                        }}</span>
                                    </template>
                                </InformationItems>
                            </div>
                        </div>
                        <div
                            v-for="item in splitData(data).remain"
                            :key="item.id"
                        >
                            <InformationItems
                                :id="item.id"
                                :title="item.title"
                                :desc="item.desc"
                                :click="item.click"
                                :author="item.author"
                                :create-time="item.create_time"
                                :image="item.image"
                                :only-title="true"
                                :show-time="false"
                            >
                                <template #title="{ title }">
                                    <span class="line-clamp-1">{{
                                        title
                                    }}</span>
                                </template>
                            </InformationItems>
                        </div>
                    </div>
                </template>
            </InformationCard>
        </div>
    </div>
</template>
<script lang="ts" setup>
import { getArticleCenter } from '~~/api/news'
import placeholder from '@/assets/images/placeholder.png'
// default 兜底：后端接口不可达时仍产出含真实文案的静态页，而不是空白/报错
const { data: newsLists } = await useAsyncData(() => getArticleCenter(), {
    default: () => []
})
const splitData = <T,>(data: T[]) => {
    const size = 3
    return {
        topThree: data.slice(0, size),
        remain: data.slice(size)
    }
}

// ===== SEO =====
const pageTitle = '资讯中心｜格林社婚礼服务'
const pageDescription =
    '格林社婚礼服务资讯中心：婚礼仪式灵感、现场统筹方法与高端庆典案例解读。'
const pageUrl = 'https://www.gelinshe.com/pc/information'

useHead({
    title: pageTitle,
    meta: [
        { name: 'description', content: pageDescription },
        { property: 'og:type', content: 'website' },
        { property: 'og:title', content: pageTitle },
        { property: 'og:description', content: pageDescription },
        { property: 'og:url', content: pageUrl }
    ],
    link: [{ rel: 'canonical', href: pageUrl }]
})
</script>
<style lang="scss" scoped></style>
