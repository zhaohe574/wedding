<template>
    <div class="info-list">
        <div class="info-list__head">
            <h1 class="info-list__title">
                <span v-if="route.query.keywords">
                    查找"{{ route.query.keywords }}"
                </span>
                <span v-else>{{ route.query.name || getSourceText }}</span>
            </h1>
            <span v-if="data?.count" class="info-list__count">
                {{ data.count }} 篇
            </span>
        </div>
        <div
            v-loading="pending"
            element-loading-background="rgba(21, 18, 16, 0.65)"
        >
            <div v-if="error" class="info-list__state" role="status">
                <p>资讯暂时无法加载</p>
                <button
                    type="button"
                    class="info-list__action"
                    @click="refresh()"
                >
                    重新加载
                </button>
            </div>
            <div
                class="info-list__panel"
                v-else-if="data?.lists?.length"
            >
                <InformationItems
                    v-for="item in data.lists"
                    :key="item.id"
                    :id="item.id"
                    :title="item.title"
                    :desc="item.desc"
                    :click="item.click"
                    :author="item.author"
                    :create-time="item.create_time"
                    :image="item.image"
                    :only-title="false"
                />
                <div class="info-list__pager">
                    <el-pagination
                        v-model:current-page="params.page_no"
                        :total="data.count"
                        :page-size="params.page_size"
                        hide-on-single-page
                        @current-change="refresh()"
                    />
                </div>
            </div>
            <div v-else class="info-list__state">
                <p>暂无资讯</p>
                <NuxtLink class="info-list__link" to="/information">
                    返回资讯中心
                </NuxtLink>
            </div>
        </div>
    </div>
</template>
<script lang="ts" setup>
import { ElPagination } from 'element-plus'
import { getArticleList } from '~~/api/news'
const route = useRoute()
const sort = computed(() => route.params.source)
const keyword = computed(() => route.query.keywords || '')
const cid = computed(() => route.query.cid || '')
const params = reactive({
    page_no: 1,
    page_size: 15,
    keyword,
    cid,
    sort
})
const { data, refresh, pending, error } = await useAsyncData(
    () => getArticleList(params),
    {
        default: () => ({ lists: [], count: 0 })
    }
)

const getSourceText = computed(() => {
    switch (route.params.source) {
        case 'hot':
            return '热门资讯'
        case 'new':
            return ' 最新资讯'
        default:
            return '全部资讯'
    }
})

watch(
    [
        () => route.query.keywords,
        () => route.query.cid,
        () => route.params.source
    ],
    () => {
        params.page_no = 1
        refresh()
    }
)

// ===== SEO =====
const listTitle = computed(
    () => `${String(getSourceText.value).trim() || '资讯列表'}｜格林社婚礼服务`
)
const listDescription = computed(
    () =>
        `格林社婚礼服务${String(getSourceText.value).trim() || '资讯'}列表，包含婚礼仪式灵感、现场统筹方法与案例解读。`
)
const listUrl = computed(
    () => `https://www.gelinshe.com/pc/information/${route.params.source}`
)

useHead(() => ({
    title: listTitle.value,
    meta: [
        { name: 'description', content: listDescription.value },
        { property: 'og:type', content: 'website' },
        { property: 'og:title', content: listTitle.value },
        { property: 'og:description', content: listDescription.value },
        { property: 'og:url', content: listUrl.value }
    ],
    link: [{ rel: 'canonical', href: listUrl.value }]
}))
</script>
<style lang="scss" scoped>
.info-list {
    &__head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 24px;
    }

    &__title {
        margin: 0;
        color: var(--pc-ink);
        font-family: var(--font-display);
        font-size: clamp(26px, 3vw, 32px);
        font-weight: 400;
        letter-spacing: 0.04em;
        line-height: 1.3;
    }

    &__count {
        flex: none;
        padding: 2px 10px;
        border: 1px solid var(--pc-line);
        border-radius: 999px;
        color: var(--pc-ink-3);
        font-size: 12px;
        line-height: 20px;
    }

    &__panel {
        padding: 8px 24px;
        border: 1px solid var(--pc-line);
        border-radius: 14px;
        background: var(--pc-surface);
    }

    &__pager {
        display: flex;
        justify-content: flex-end;
        padding: 4px 0 12px;

        :deep(.el-pagination) {
            --el-pagination-bg-color: transparent;
            --el-pagination-text-color: var(--pc-ink-2);
            --el-pagination-button-color: var(--pc-ink-2);
            --el-pagination-button-bg-color: transparent;
            --el-pagination-button-disabled-color: var(--pc-ink-3);
            --el-pagination-button-disabled-bg-color: transparent;
            --el-pagination-hover-color: var(--pc-gold);
            gap: 2px;
        }

        :deep(.el-pager li),
        :deep(.btn-prev),
        :deep(.btn-next) {
            border-radius: 6px;
            background: transparent;
            color: var(--pc-ink-2);
        }

        :deep(.el-pager li.is-active) {
            background: #c9a96e;
            color: #181614;
        }
    }

    &__state {
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

        p {
            margin: 0;
        }
    }

    &__action,
    &__link {
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
</style>
