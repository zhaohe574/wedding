<template>
    <!-- 头部统一：走 page-shell 的 nav-bar，不再依赖系统导航 -->
    <page-shell title="资讯详情" bg="page" :show-back="true">
        <view class="page-wrap article">
            <view v-if="loading" class="page-pad">
                <base-skeleton :rows="5" variant="text" />
            </view>

            <!-- 业务态（文章已下架）：不可重试、不给按钮（P0 §2-5） -->
            <base-empty
                v-else-if="error"
                status="error"
                :error-kind="notFound ? 'business' : view?.errorKind || 'unknown'"
                :error-text="notFound ? FB.EMPTY_REMOVED : view?.title"
                :desc="notFound ? FB.ERR_PERMISSION_HINT : view?.desc"
                :primary-action="isTerminal ? undefined : view?.primaryAction"
                fixed
                @primary="onPrimary"
                @retry="load"
            />

            <view v-else class="page-pad">
                <text class="article__title t-balance">{{ data.title }}</text>
                <!-- 样板页：作者 · 时间 · 阅读量，用「 · 」分隔并降到 --fs-20（原三 span + gap） -->
                <text v-if="metaText" class="article__meta t-num">{{ metaText }}</text>

                <image
                    v-if="data.image"
                    class="article__cover"
                    :src="imageUrl(data.image)"
                    mode="widthFix"
                />

                <view class="article__content">
                    <u-parse :html="data.content || ''" />
                </view>

                <view class="article__actions">
                    <view class="article__collect">
                        <base-button
                            :text="collected ? '已收藏' : '收藏这篇文章'"
                            :variant="collected ? 'ghost' : 'ink'"
                            size="md"
                            @click="toggleCollect"
                        />
                    </view>
                    <button
                        class="article__share"
                        open-type="share"
                        hover-class="is-pressed"
                        aria-label="分享这篇文章"
                    >
                        <tn-icon name="share" :size="26" class="article__share-ic" />
                        <text class="article__share-text">分享</text>
                    </button>
                </view>
            </view>
        </view>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
/** 资讯详情 */
import { computed, ref } from 'vue'
import { onLoad, onShareAppMessage, onShareTimeline } from '@dcloudio/uni-app'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { getArticleDetail, addArticleCollect, cancelArticleCollect } from '@/api/content'
import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'
import { back, navigate } from '@/utils/link'
import {
    buildShare,
    buildTimelineShare,
    enableShareMenu,
    extractFirstHtmlImage
} from '@/utils/share'
import { FB } from '@/constants/feedback'

const appStore = useAppStore()
const userStore = useUserStore()

const id = ref(0)
const data = ref<any>({})
const loading = ref(true)
const error = ref(false)
/** 接口返回空数据 = 文章已下架/删除：业务态，不是可重试的异常 */
const notFound = ref(false)

/** O5：真实错误驱动 errorKind / 文案 / 恢复动作（状态机骨架保持不变） */
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))
/**
 * 业务/权限类不可恢复：不渲染任何动作按钮（P0 §2-5）。
 * ⚠️ 坑：`resolveErrorView` 对 business **总是**附带 primaryAction（「返回上一页」），
 * 所以页面必须自己把 primary-action 置空，否则会多出一个按钮。
 * 目前 2 页（本页 + message/detail）各自置空；**出现第 3 页时收编进 resolveErrorView**。
 */
const isTerminal = computed(
    () => notFound.value || ['business', 'permission'].includes(view.value?.errorKind ?? '')
)
function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        load()
        return
    }
    if (kind === 'reselect' || kind === 'back') {
        back()
        return
    }
    if (kind === 'login') {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    // contact / 其余：保持现有客服入口，不新增跳转
}
const collected = ref(false)

/** 样板页：meta 三项合并为一段文本，缺项自动跳过分隔符 */
const metaText = computed(() =>
    [
        data.value.author,
        data.value.create_time,
        data.value.click ? `${data.value.click} 阅读` : ''
    ]
        .filter(Boolean)
        .join(' · ')
)

function imageUrl(url?: string) {
    if (!url) return ''
    if (/^https?:/.test(url)) return url
    const domain = (appStore.config && appStore.config.domain) || ''
    return domain ? `${domain}${url}` : url
}

async function load() {
    loading.value = true
    error.value = false
    notFound.value = false
    try {
        const res = await getArticleDetail({ id: id.value })
        // 空数据不再 throw new Error('empty')：那会落到 unknown 分支给一个永远点不成功的
        // 「重新加载」。文章已下架是确定性业务态，按 business 处理（P0 §2-5）。
        if (!res) {
            notFound.value = true
            error.value = true
            return
        }
        data.value = res
        collected.value = Number((res as any).is_collect) === 1
    } catch (e) {
        rawError.value = e
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
    enableShareMenu()
    id.value = Number(options?.id || options?.article_id || 0)
    if (!id.value) {
        error.value = true
        loading.value = false
        return
    }
    // 分享直达时全局配置可能还没拉过：补一次，品牌名 / 域名才能用于分享与图片
    if (!appStore.configLoaded) appStore.getConfig().catch(() => undefined)
    load()
})

/** 分享卡片：标题取文章标题，封面先取文章封面，无封面时抓正文首张配图 */
function articleShareOptions() {
    return {
        title: String(data.value?.title || '').trim(),
        suffix: ' · 婚礼资讯',
        path: '/packages/pkg-catalog/pages/article-detail/article-detail',
        query: { id: id.value },
        imageUrls: [
            imageUrl(data.value?.image),
            imageUrl(extractFirstHtmlImage(data.value?.content))
        ]
    }
}

onShareAppMessage(() => buildShare(articleShareOptions()))

onShareTimeline(() => buildTimelineShare(articleShareOptions()))
</script>

<style lang="scss" scoped>
.article {
    background: var(--c-bg);

    &__title {
        display: block;
        margin: var(--sp-3) 0 var(--sp-2);
        /* 语义 SSOT（01 §3.1）H1 页面标题 = --fs-2xl 48rpx/500；与 message/detail 同档 */
        font-size: var(--fs-2xl);
        font-weight: 500;
        color: var(--c-ink);
        line-height: var(--lh-tight);
        overflow-wrap: anywhere;
    }

    &__meta {
        display: block;
        margin-bottom: var(--sp-4);
        /* 中文短标签走语义档 --fs-xs（max(24rpx,12px)），排印档 --fs-20 无 max() 下限保护 */
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        line-height: var(--lh-body);
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
        /* 批 1.5 ⑥：原裸值 1.9 无设计稿出处，收敛到 4 档行高的 --lh-loose(1.7) */
        line-height: var(--lh-loose);
        overflow-wrap: anywhere;
        word-break: break-word;

        /* u-parse 正文兼容（同 message/detail 的写法）：
           mp-weixin 无 <p> 标签，段落渲染成 <rich-text class="_p __p">，
           img 渲染成 <image class="_image">；两套选择器都要挂，缺一套就有一端失配。 */
        :deep(p),
        :deep(.__p) {
            line-height: var(--lh-loose);
        }

        :deep(img),
        :deep(._image) {
            display: block;
            max-width: 100%;
            height: auto;
            margin: var(--sp-3) 0;
            border-radius: var(--r-md);
            background: var(--c-neutral-bg);
        }
    }

    &__actions {
        display: flex;
        align-items: center;
        gap: var(--sp-3);
        margin: var(--sp-5) 0 var(--sp-6);
    }

    /* base-button 默认 block=true（width:100%），外面套一层吃掉剩余宽度，与右侧分享同高并排 */
    &__collect {
        flex: 1;
        min-width: 0;
    }

    &__share {
        flex: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6rpx;
        height: 88rpx;
        padding: 0 var(--sp-4);
        margin: 0;
        border: 1rpx solid var(--c-line-strong);
        border-radius: var(--r-pill);
        background: var(--c-surface);
        color: var(--c-ink);
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-none);

        &::after {
            border: 0;
        }
    }

    &__share-ic,
    &__share-text {
        color: inherit;
        line-height: var(--lh-none);
    }
}
</style>
