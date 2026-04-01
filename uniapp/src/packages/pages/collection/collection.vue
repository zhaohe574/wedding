<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer">
        <BaseNavbar title="我的收藏" />
        <z-paging
            ref="paging"
            v-model="collectData"
            @query="queryList"
            :fixed="false"
            height="100%"
            use-page-scroll
            :hide-empty-view="true"
        >
            <view v-if="collectData.length" class="collection-page">
                <u-swipe-action
                    v-for="(item, index) in collectData"
                    :key="item.id"
                    :show="item.show"
                    :index="index"
                    :options="options"
                    btn-width="132"
                    @click="handleCollect"
                >
                    <news-card :item="item" :newsId="item.article_id"></news-card>
                </u-swipe-action>
            </view>
            <view v-else class="wm-empty-shell">
                <EmptyState
                    title="还没有收藏内容"
                    description="把感兴趣的灵感资讯加入收藏，后续就能在这里快速回看。"
                />
            </view>
        </z-paging>
    </PageShell>
</template>

<script lang="ts" setup>
import EmptyState from '@/components/base/EmptyState.vue'
import PageShell from '@/components/base/PageShell.vue'
import { ref, reactive, shallowRef } from 'vue'
import { getCollect, cancelCollect } from '@/api/news'

const paging = shallowRef()
const options = reactive([
    {
        text: '取消收藏',
        style: {
            color: '#FFFFFF',
            backgroundColor: '#FF2C3C'
        }
    }
])
const collectData: any = ref([])

const queryList = async () => {
    const { lists } = await getCollect()
    lists.forEach((item: any) => {
        item.show = false
    })
    collectData.value = lists
    paging.value.complete(lists)
}

const handleCollect = async (index: number): Promise<void> => {
    try {
        const articleId: number = collectData.value[index].article_id
        await cancelCollect({ id: articleId })
        uni.$u.toast('已取消收藏')
        paging.value.reload()
    } catch (err) {
        console.log('取消收藏报错=>', err)
    }
}
</script>

<style scoped>
.collection-page {
    padding: 20rpx 0 24rpx;
}
</style>
