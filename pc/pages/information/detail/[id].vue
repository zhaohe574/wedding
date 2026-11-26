<template>
    <div
        v-if="pending || error || !newsDetail"
        class="detail-state"
        role="status"
    >
        <p>
            {{
                pending
                    ? '正在加载资讯…'
                    : '资讯暂时无法加载，可能已下架或网络异常。'
            }}
        </p>
        <div class="detail-state__actions">
            <button
                v-if="!pending"
                type="button"
                class="detail-state__retry"
                @click="refresh()"
            >
                重新加载
            </button>
            <NuxtLink class="detail-state__back" to="/information">
                返回资讯中心
            </NuxtLink>
        </div>
    </div>
    <div v-else class="detail">
        <el-breadcrumb class="detail__crumb" separator="/">
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
        <div class="detail__layout">
            <article class="detail__main">
                <header class="detail__head">
                    <h1 class="detail__title">{{ newsDetail.title }}</h1>
                    <div class="detail__meta">
                        <span v-if="newsDetail.author">
                            {{ newsDetail.author }}
                        </span>
                        <span
                            v-if="newsDetail.author"
                            class="detail__divider"
                            aria-hidden="true"
                            >|</span
                        >
                        <span>{{ newsDetail.create_time }}</span>
                        <span class="detail__click">
                            <Icon name="el-icon-View" />
                            <span>{{ newsDetail.click }}</span>
                        </span>
                    </div>
                </header>
                <blockquote v-if="newsDetail.abstract" class="detail__abstract">
                    {{ newsDetail.abstract }}
                </blockquote>
                <div
                    class="detail__content render-html"
                    v-html="newsDetail.content"
                ></div>
                <footer
                    v-if="newsDetail.last?.id || newsDetail.next?.id"
                    class="detail__nav"
                >
                    <NuxtLink
                        v-if="newsDetail.last?.id"
                        class="detail__nav-item"
                        :to="`/information/detail/${newsDetail.last.id}`"
                    >
                        <span class="detail__nav-label">上一篇</span>
                        <span class="detail__nav-title">{{
                            newsDetail.last?.title
                        }}</span>
                    </NuxtLink>
                    <NuxtLink
                        v-if="newsDetail.next?.id"
                        class="detail__nav-item"
                        :to="`/information/detail/${newsDetail.next.id}`"
                    >
                        <span class="detail__nav-label">下一篇</span>
                        <span class="detail__nav-title">{{
                            newsDetail.next?.title
                        }}</span>
                    </NuxtLink>
                </footer>
            </article>
            <aside class="detail__aside">
                <InformationCard
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
            </aside>
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
        // default 兜底：接口不可达时返回 null 走页面级空态，不抛错中断渲染
        default: () => null,
        watch: [() => route.params.id]
    }
)

// ===== SEO：文章详情动态 head + Article JSON-LD =====
const detailTitle = computed(() =>
    newsDetail.value?.title
        ? `${newsDetail.value.title}｜格林社婚礼服务`
        : '资讯详情｜格林社婚礼服务'
)
const detailDescription = computed(
    () =>
        newsDetail.value?.abstract ||
        newsDetail.value?.desc ||
        '格林社婚礼服务资讯详情。'
)
const detailUrl = computed(
    () => `https://www.gelinshe.com/pc/information/detail/${route.params.id}`
)

useHead(() => ({
    title: detailTitle.value,
    meta: [
        { name: 'description', content: detailDescription.value },
        { property: 'og:type', content: 'article' },
        { property: 'og:title', content: detailTitle.value },
        { property: 'og:description', content: detailDescription.value },
        { property: 'og:url', content: detailUrl.value }
    ],
    link: [{ rel: 'canonical', href: detailUrl.value }]
}))

useHead(() => ({
    script: newsDetail.value?.title
        ? [
              {
                  type: 'application/ld+json',
                  innerHTML: JSON.stringify({
                      '@context': 'https://schema.org',
                      '@type': 'Article',
                      headline: newsDetail.value.title,
                      description: detailDescription.value,
                      url: detailUrl.value,
                      author: newsDetail.value.author
                          ? { '@type': 'Person', name: newsDetail.value.author }
                          : undefined,
                      datePublished: newsDetail.value.create_time || undefined
                  })
              }
          ]
        : []
}))
</script>
<style lang="scss" scoped>
.detail-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 14px;
    padding: 56px 24px;
    border: 1px solid var(--pc-line);
    border-radius: 14px;
    background: var(--pc-surface);
    color: var(--pc-ink-3);
    font-size: 14px;
    text-align: center;

    p {
        margin: 0;
    }

    &__actions {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    &__retry,
    &__back {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 36px;
        padding: 0 22px;
        border: 1px solid var(--pc-line-strong);
        border-radius: 999px;
        background: transparent;
        color: var(--pc-ink-2);
        font-family: inherit;
        font-size: 13px;
        text-decoration: none;
        cursor: pointer;
        transition:
            border-color var(--dur-fast) var(--ease-out),
            color var(--dur-fast) var(--ease-out);

        &:hover {
            border-color: var(--pc-gold);
            color: var(--pc-gold);
        }
    }
}

.detail {
    &__crumb {
        margin-bottom: 20px;
        font-size: 13px;

        :deep(.el-breadcrumb__inner),
        :deep(.el-breadcrumb__inner a) {
            color: var(--pc-ink-3);
            font-weight: 400;
            transition: color var(--dur-fast) var(--ease-out);
        }

        :deep(.el-breadcrumb__inner.is-link:hover) {
            color: var(--pc-gold);
        }

        :deep(.el-breadcrumb__separator) {
            color: var(--pc-ink-3);
            font-weight: 400;
        }

        :deep(.el-breadcrumb__item:last-child .el-breadcrumb__inner) {
            color: var(--pc-ink);
        }
    }

    &__layout {
        display: flex;
        align-items: flex-start;
        gap: 20px;
    }

    &__main {
        flex: 1;
        min-width: 0;
        padding: 32px;
        border: 1px solid var(--pc-line);
        border-radius: 14px;
        background: var(--pc-surface);
    }

    &__head {
        padding-bottom: 20px;
        border-bottom: 1px solid var(--pc-line);
    }

    &__title {
        margin: 0;
        color: var(--pc-ink);
        font-family: var(--font-display);
        font-size: clamp(22px, 2.4vw, 28px);
        font-weight: 400;
        line-height: 1.4;
    }

    &__meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
        margin-top: 14px;
        color: var(--pc-ink-3);
        font-size: 12px;
    }

    &__divider {
        color: var(--pc-line-strong);
    }

    &__click {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    &__abstract {
        margin: 24px 0 0;
        padding: 14px 18px;
        border-left: 2px solid var(--pc-gold);
        border-radius: 0 8px 8px 0;
        background: rgba(201, 169, 110, 0.06);
        color: var(--pc-ink-2);
        font-size: 14px;
        line-height: 1.8;
    }

    &__content {
        padding-top: 20px;
        color: var(--pc-ink-2);
        font-size: 15px;
        line-height: 1.9;

        :deep(h1),
        :deep(h2),
        :deep(h3),
        :deep(h4),
        :deep(h5) {
            color: var(--pc-ink);
        }

        :deep(a) {
            color: var(--pc-gold);
            text-decoration: none;
        }

        :deep(img) {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }
    }

    &__nav {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 32px;
        padding-top: 20px;
        border-top: 1px solid var(--pc-line);
    }

    &__nav-item {
        display: flex;
        align-items: baseline;
        gap: 10px;
        min-width: 0;
        color: var(--pc-ink-2);
        font-size: 14px;
        text-decoration: none;
        transition: color var(--dur-fast) var(--ease-out);

        &:hover {
            color: var(--pc-gold);
        }
    }

    &__nav-label {
        flex: none;
        color: var(--pc-ink-3);
        font-size: 13px;
    }

    &__nav-title {
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    &__aside {
        flex: none;
        width: 320px;
    }
}

@media (max-width: 960px) {
    .detail__layout {
        flex-direction: column;
    }

    .detail__aside {
        width: 100%;
    }
}

@media (max-width: 640px) {
    .detail__main {
        padding: 24px 20px;
    }
}
</style>
