<template>
    <div class="min-h-full flex flex-col">
        <div class="text-4xl mb-5">
            <span v-if="route.query.keywords">
                查找"{{ route.query.keywords }}"
            </span>
            <span v-else>{{ route.query.name || getSourceText }}</span>
        </div>
        <div v-loading="pending">
            <div v-if="error" class="py-8 text-center" role="status">
                <p>资讯暂时无法加载，请稍后重试。</p>
                <el-button @click="refresh()">重新加载</el-button>
            </div>
            <div
                class="bg-white px-5 rounded overflow-hidden"
                v-else-if="data?.lists?.length"
            >
                <div class="pt-5 text-tx-secondary" v-if="route.query.keywords">
                    为您找到相关结果 {{ data.count }}个
                </div>
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
                <div class="py-4 flex justify-end">
                    <el-pagination
                        v-model:current-page="params.page_no"
                        :total="data.count"
                        :page-size="params.page_size"
                        hide-on-single-page
                        @current-change="refresh()"
                    />
                </div>
            </div>
            <div v-else class="flex-1 flex justify-center items-center">
                <el-empty
                    :image="empty_news"
                    description="暂无资讯"
                    :image-size="250"
                />
            </div>
        </div>
    </div>
</template>
<script lang="ts" setup>
import { ElPagination, ElEmpty } from 'element-plus'
import empty_news from '@/assets/images/empty_news.png'
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
<style lang="scss" scoped></style>
