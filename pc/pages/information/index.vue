<template>
    <div class="info-center">
        <h1 class="info-center__heading">资讯中心</h1>
        <div class="info-center__grid">
            <InformationCard
                v-for="item in newsLists"
                :key="item.id"
                :header="item.name"
                :data="item.article"
                :link="`/information/default?cid=${item.id}&name=${item.name}`"
            >
                <template #content="{ data }">
                    <div class="info-center__body">
                        <div class="info-center__top">
                            <div
                                class="info-center__cell"
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
                                        <span
                                            class="info-center__cell-title line-clamp-2"
                                            >{{ title }}</span
                                        >
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
<style lang="scss" scoped>
.info-center {
    &__heading {
        margin: 0 0 24px;
        color: var(--pc-ink);
        font-family: var(--font-display);
        font-size: clamp(26px, 3vw, 32px);
        font-weight: 400;
        letter-spacing: 0.04em;
        line-height: 1.3;
    }

    &__grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
        align-items: start;
    }

    &__body {
        padding: 8px 20px 20px;
    }

    &__top {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        padding-top: 16px;
    }

    &__cell {
        overflow: hidden;
        border: 1px solid var(--pc-line);
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.02);

        :deep(.info-item__full) {
            padding-top: 0;
        }

        :deep(.info-item__media) {
            border-radius: 0;
        }

        :deep(.info-item__media .el-image) {
            width: 100% !important;
            height: auto !important;
            aspect-ratio: 4 / 3;
            border-radius: 0;
        }

        :deep(.info-item__body) {
            padding: 10px 12px 12px;
        }
    }

    &__cell-title {
        display: -webkit-box;
        overflow: hidden;
        color: var(--pc-ink);
        font-size: 13px;
        line-height: 1.55;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
        transition: color var(--dur-fast) var(--ease-out);
    }

    &__cell:hover &__cell-title {
        color: var(--pc-gold);
    }
}

@media (max-width: 860px) {
    .info-center__grid {
        grid-template-columns: minmax(0, 1fr);
    }
}

@media (max-width: 560px) {
    .info-center__top {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
