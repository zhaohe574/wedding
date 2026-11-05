<template>
    <view class="page-wrap article">
        <view v-if="loading" class="page-pad">
            <base-skeleton :rows="5" variant="text" />
        </view>

        <base-empty v-else-if="error" status="error" error-kind="network" fixed @retry="load" />

        <view v-else class="page-pad">
            <text class="article__title">{{ data.title }}</text>
            <view class="article__meta">
                <text v-if="data.author" class="article__author">{{ data.author }}</text>
                <text class="article__time">{{ data.create_time }}</text>
                <text v-if="data.click" class="article__click">{{ data.click }} 阅读</text>
            </view>

            <image v-if="data.image" class="article__cover" :src="imageUrl(data.image)" mode="widthFix" />

            <view class="article__content">
                <u-parse :html="data.content || ''" />
            </view>

            <view class="article__actions">
                <base-button
                    :text="collected ? '已收藏' : '收藏这篇文章'"
                    :variant="collected ? 'ghost' : 'brand'"
                    size="md"
                    @click="toggleCollect"
                />
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
/** 资讯详情 */
import { ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { getArticleDetail, addArticleCollect, cancelArticleCollect } from '@/api/content'
import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'
import { navigate } from '@/utils/link'

const appStore = useAppStore()
const userStore = useUserStore()

const id = ref(0)
const data = ref<any>({})
const loading = ref(true)
const error = ref(false)
const collected = ref(false)

function imageUrl(url?: string) {
    if (!url) return ''
    if (/^https?:/.test(url)) return url
    const domain = (appStore.config && appStore.config.domain) || ''
    return domain ? `${domain}${url}` : url
}

async function load() {
    loading.value = true
    error.value = false
    try {
        const res = await getArticleDetail({ id: id.value })
        if (!res) throw new Error('empty')
        data.value = res
        collected.value = Number((res as any).is_collect) === 1
    } catch {
        error.value = true
    } finally {
        loading.value = false
    }
}

async function toggleCollect() {
    if (!userStore.isLogin) {
        navigate({ path: '/pages/login/login' })
        return
    }
    try {
        if (collected.value) {
            await cancelArticleCollect({ article_id: id.value })
            collected.value = false
        } else {
            await addArticleCollect({ article_id: id.value })
            collected.value = true
        }
    } catch {
        // 请求层已提示
    }
}

onLoad((options: any) => {
    id.value = Number(options?.id || 0)
    if (!id.value) {
        error.value = true
        loading.value = false
        return
    }
    load()
})
</script>

<style lang="scss" scoped>
.article {
    background: var(--c-bg);

    &__title {
        display: block;
        margin: var(--sp-3) 0 var(--sp-2);
        font-size: var(--fs-2xl);
        font-weight: 500;
        color: var(--c-ink);
        line-height: 1.4;
        overflow-wrap: anywhere;
    }

    &__meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: var(--sp-1) var(--sp-3);
        margin-bottom: var(--sp-4);
        line-height: 1.5;
    }

    &__author,
    &__time,
    &__click {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        overflow-wrap: anywhere;
    }

    &__cover {
        display: block;
        width: 100%;
        margin-bottom: var(--sp-4);
        border-radius: var(--r-md);
        background: var(--c-neutral-bg);
    }

    &__content {
        max-width: 100%;
        box-sizing: border-box;
        font-family: var(--font-sans);
        font-size: var(--fs-md);
        color: var(--c-ink-2);
        line-height: 1.9;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    &__actions {
        margin: var(--sp-5) 0 var(--sp-6);
    }
}
</style>
