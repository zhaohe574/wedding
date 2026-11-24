<template>
    <!-- 头部统一：走 page-shell 的 nav-bar，不再依赖系统导航 -->
    <page-shell title="案例" bg="page" :show-back="true">
        <view class="page-wrap case-page">
            <view class="tabs">
                <scroll-view scroll-x :show-scrollbar="false">
                    <view class="tabs__row">
                        <view
                            hover-class="is-pressed"
                            class="tabs__item"
                            :class="{ 'is-active': !tag }"
                            @tap="switchTag('')"
                        >
                            <text class="tabs__text">全部</text>
                        </view>
                        <view
                            hover-class="is-pressed"
                            v-for="t in tags"
                            :key="t"
                            class="tabs__item"
                            :class="{ 'is-active': tag === t }"
                            @tap="switchTag(t)"
                        >
                            <text class="tabs__text">{{ t }}</text>
                        </view>
                    </view>
                </scroll-view>
            </view>

            <view v-if="firstLoading" class="page-pad">
                <base-skeleton :rows="3" />
            </view>

            <base-empty
                v-else-if="error"
                status="error"
                :error-kind="view?.errorKind || 'unknown'"
                :error-text="view?.title"
                :desc="view?.desc"
                :primary-action="view?.primaryAction"
                fixed
                @primary="onPrimary"
                @retry="reload"
            />

            <!-- v1 u-icon → v2 tn-icon：empty-news 在 tn-icon 字体中不存在，改 empty-message -->
            <!-- 空态：desc 只在选了分类时传（v2 补丁⑤）——未选分类时「换个分类看看」是假建议，
                 换哪个都一样空；此时 desc 不传，只留标题和「去看看主持人」 -->
            <base-empty
                v-else-if="!list.length"
                status="empty"
                empty-icon="empty-message"
                :empty-text="FB.EMPTY_CASE"
                :desc="tag ? FB.EMPTY_HINT_CASE : ''"
                action-text="去看看主持人"
                :show-action="true"
                fixed
                @action="goQuery"
            />

            <view v-else class="grid">
                <view v-for="item in list" :key="item.id" class="cell">
                    <image
                        class="cell__img"
                        :src="imageUrl(item.cover || item.image)"
                        mode="aspectFill"
                    />
                    <view class="cell__mask" />
                    <text class="cell__title ellipsis-2">{{ item.title || item.content }}</text>
                </view>
            </view>

            <load-more :status="loadMoreStatus" @retry="fetchPage(false)" />
        </view>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
/**
 * 案例流（只读）。
 * 替代老端的「动态」：后端 dynamic/publish 已硬关闭，评论区在送审模式下也被禁用。
 */
import { computed, ref } from 'vue'
import { onLoad, onPullDownRefresh, onReachBottom } from '@dcloudio/uni-app'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { navigate, safeDecodeParam } from '@/utils/link'
import { FB } from '@/constants/feedback'
import { getCaseList, getCaseHotTags } from '@/api/content'
import { normalizePage } from '@/request'
import { useAppStore } from '@/stores/app'

const appStore = useAppStore()

const tag = ref('')
const tags = ref<string[]>([])
const list = ref<any[]>([])
const page = ref(1)
const pageSize = 10
const hasMore = ref(true)
const loadingMore = ref(false)
const firstLoading = ref(true)
const error = ref(false)

/** O5：真实错误驱动 errorKind / 文案 / 恢复动作（状态机骨架保持不变） */
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))
function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        reload()
        return
    }
    if (kind === 'reselect' || kind === 'back') {
        uni.navigateBack()
        return
    }
    if (kind === 'login') {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    // contact / 其余：保持现有客服入口，不新增跳转
}
/** LoadMore 四态（受控）：idle 静默 / loading / nomore / error 可点重试 */
const loadMoreFailed = ref(false)
const loadMoreStatus = computed<'idle' | 'loading' | 'nomore' | 'error'>(() => {
    if (loadMoreFailed.value) return 'error'
    if (loadingMore.value && list.value.length) return 'loading'
    if (!hasMore.value) return 'nomore'
    return 'idle'
})

function imageUrl(url?: string) {
    if (!url) return ''
    if (/^https?:/.test(url)) return url
    const domain = (appStore.config && appStore.config.domain) || ''
    return domain ? `${domain}${url}` : url
}

async function fetchPage(reset = false) {
    if (reset) {
        page.value = 1
        hasMore.value = true
        error.value = false
    }
    if (!hasMore.value && !reset) return

    loadingMore.value = true
    loadMoreFailed.value = false
    try {
        const res = await getCaseList({
            page: page.value,
            page_size: pageSize,
            tag: tag.value || undefined
        })
        const paged = normalizePage<any>(res, page.value, pageSize)
        list.value = reset ? paged.list : [...list.value, ...paged.list]
        hasMore.value = paged.hasMore
        if (hasMore.value) page.value += 1
    } catch (e) {
        rawError.value = e
        if (reset) error.value = true
        else loadMoreFailed.value = true
    } finally {
        loadingMore.value = false
        firstLoading.value = false
        uni.stopPullDownRefresh()
    }
}

function reload() {
    firstLoading.value = true
    fetchPage(true)
}

function switchTag(t: string) {
    if (t === tag.value) return
    tag.value = t
    list.value = []
    reload()
}

/** 空态动作：案例流没有内容时，引导去看能下单的人（文案「去看看主持人」与动作一致） */
function goQuery() {
    navigate({ path: '/pages/query/query' })
}

onLoad(async (options: any) => {
    const incomingTag = options?.tag ? safeDecodeParam(options.tag) : ''
    if (incomingTag) tag.value = incomingTag
    try {
        const t = await getCaseHotTags()
        const baseTags = Array.isArray(t) ? t.slice(0, 8) : []
        tags.value =
            incomingTag && !baseTags.includes(incomingTag) ? [incomingTag, ...baseTags] : baseTags
    } catch {
        tags.value = incomingTag ? [incomingTag] : []
    }
    fetchPage(true)
})

onPullDownRefresh(() => fetchPage(true))
onReachBottom(() => fetchPage(false))
</script>

<style lang="scss" scoped>
.case-page {
    background: var(--c-bg);
}

.tabs {
    position: sticky;
    top: 0;
    z-index: 10;
    padding: var(--sp-2) 0;
    background: var(--c-surface);
    border-bottom: 1rpx solid var(--c-line);
    white-space: nowrap;

    &__row {
        display: inline-flex;
        align-items: center;
        gap: var(--sp-2);
        padding: 0 var(--sp-4);
    }

    &__item {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        /* P0 §2-3：胶囊版式与 search 对齐（高 56rpx / --r-pill / --c-line-strong 边 /
           选中 --c-ink 底 + 反白字）。原本已是胶囊，只把边从 --c-line 换成 --c-line-strong。 */
        height: 56rpx;
        box-sizing: border-box;
        padding: 0 var(--sp-3);
        border: 1rpx solid var(--c-line-strong);
        border-radius: var(--r-pill);
        background: var(--c-surface-2);

        /* 视觉 56rpx，触控 88rpx（56 + 16×2）。只做纵向扩展，横向留 0 避免与相邻 tab 热区重叠 */
        &::before {
            content: '';
            position: absolute;
            top: -16rpx;
            bottom: -16rpx;
            left: 0;
            right: 0;
        }

        &.is-active {
            background: var(--c-ink);
            border-color: var(--c-ink);
        }
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
        line-height: var(--lh-tight);
    }

    &__item.is-active &__text {
        color: var(--c-on-dark);
    }
}

.grid {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-2);
    padding: var(--sp-3) var(--sp-4);
}

.cell {
    position: relative;
    width: calc(50% - var(--sp-1));
    height: 420rpx;
    overflow: hidden;
    border-radius: var(--r-md);
    background: var(--c-neutral-bg);
    box-shadow: var(--shadow-card);

    &__img {
        display: block;
        width: 100%;
        height: 100%;
    }

    &__mask {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        height: 58%;
        background: rgba(24, 22, 20, 0.4); /* O7: 纯色遮罩，渐变不承载文字层级 */
    }

    &__title {
        position: absolute;
        left: var(--sp-3);
        right: var(--sp-3);
        bottom: var(--sp-3);
        font-size: var(--fs-xs);
        color: var(--c-on-dark);
        line-height: var(--lh-body);
        overflow-wrap: anywhere;
    }
}
</style>
