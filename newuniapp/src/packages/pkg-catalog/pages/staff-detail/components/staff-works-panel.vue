<template>
    <view class="panel">
        <view v-if="workCategories.length" class="wfilter">
            <chip-group
                v-model="workCategory"
                :items="workCategories"
                size="md"
                @change="onWorkFilter"
            />
        </view>

        <view v-if="filteredWorks.length" class="wgrid">
            <view
                v-for="w in filteredWorks"
                :key="w.id"
                class="wtile"
                hover-class="is-pressed"
                aria-role="button"
                @tap="onSelect(w)"
            >
                <image
                    v-if="workCover(w)"
                    class="wtile__img"
                    :src="workCover(w)"
                    mode="aspectFill"
                />
                <view v-else class="wtile__blank">
                    <tn-icon :name="isVideoWork(w) ? 'video' : 'image'" :size="48" />
                </view>
                <view class="wtile__scrim" aria-hidden="true" />
                <view v-if="isVideoWork(w)" class="wtile__badge">
                    <tn-icon name="play-fill" :size="18" />
                    <text>视频</text>
                </view>
                <view v-else-if="imageCount(w) > 1" class="wtile__badge">
                    <text>{{ imageCount(w) }} 图</text>
                </view>
                <text v-if="workTitle(w)" class="wtile__cap ellipsis">{{ workTitle(w) }}</text>
            </view>
        </view>
        <base-empty
            v-else
            status="empty"
            empty-text="作品整理中"
            desc="可以先看详情与真实评价"
        />

        <load-more
            v-if="filteredWorks.length"
            :status="loadStatus"
            @retry="emit('loadMore')"
        />
    </view>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useAppStore } from '@/stores/app'
import { haptic } from '@/utils/haptics'

const props = defineProps<{
    works: any[]
    hasMore: boolean
    loadStatus: 'idle' | 'loading' | 'nomore' | 'error'
}>()

const emit = defineEmits<{
    (e: 'loadMore'): void
    (e: 'select', work: any): void
}>()

const appStore = useAppStore()

function resolveUrl(url?: string): string {
    if (!url) return ''
    if (/^https?:/.test(url)) return url
    const domain = (appStore.config && appStore.config.domain) || ''
    return domain ? `${domain}${url}` : url
}

/** 封面：cover 优先，兜底取 images 第一张，最后补全域名 */
function workCover(w: any): string {
    const raw = w?.cover || (Array.isArray(w?.images) ? w.images.find(Boolean) : '')
    return resolveUrl(raw)
}

function workTitle(w: any): string {
    return String(w?.title || w?.caption || '').trim()
}

function isVideoWork(w: any): boolean {
    return Number(w?.type) === 2 || Boolean(w?.video)
}

function imageCount(w: any): number {
    return Array.isArray(w?.images) ? w.images.filter(Boolean).length : 0
}

function categoryOf(w: any): string {
    return String(w?.category_name || w?.tag || w?.style || w?.type_desc || '')
}

const workCategory = ref<string | number>('all')

const workCategories = computed(() => {
    const set = new Set<string>()
    for (const w of props.works) {
        const c = categoryOf(w)
        if (c) set.add(c)
    }
    // 只有 1 类时筛选条没有意义，直接不展示
    if (set.size <= 1) return []
    return [{ label: '全部', value: 'all' }, ...Array.from(set).map((v) => ({ label: v, value: v }))]
})

const filteredWorks = computed(() => {
    if (workCategory.value === 'all') return props.works
    return props.works.filter((w) => categoryOf(w) === String(workCategory.value))
})

function onWorkFilter() {
    haptic('light', 'works-filter')
    // 筛选不重新请求：本地按分类字段过滤，避免空分类反复打接口
    if (workCategory.value !== 'all' && props.hasMore) emit('loadMore')
}

function onSelect(w: any) {
    haptic('light', 'works-open')
    emit('select', w)
}
</script>

<style lang="scss" scoped>
.panel {
    padding: 34rpx 42rpx 0;
}

.wfilter {
    display: flex;
    gap: var(--sp-2);
    margin-bottom: 25rpx;
}

.wgrid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18rpx;
}

.wtile {
    position: relative;
    height: 242rpx;
    border-radius: var(--r-md);
    border: 1rpx solid var(--c-card-border);
    overflow: hidden;
    background: var(--c-hero-bg);
    /* v10 §2.1：作品缩略块与卡片同一套落地规则，暗色由 1rpx 描边分层 */
    box-shadow: var(--shadow-card);

    &__img {
        display: block;
        width: 100%;
        height: 100%;
    }

    &__blank {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--c-hero-bg);
        color: var(--c-ink-3);
    }

    &__scrim {
        position: absolute;
        left: 0;
        right: 0;
        top: 0;
        bottom: 0;
        background: linear-gradient(
            180deg,
            rgba(16, 14, 12, 0) 40%,
            rgba(16, 14, 12, 0.55) 74%,
            rgba(16, 14, 12, 0.9) 100%
        );
    }

    &__badge {
        position: absolute;
        top: 14rpx;
        right: 14rpx;
        z-index: 2;
        display: inline-flex;
        align-items: center;
        gap: 4rpx;
        padding: 6rpx 14rpx;
        border-radius: var(--r-pill);
        background: rgba(16, 14, 12, 0.55);
        font-size: var(--fs-18);
        line-height: var(--lh-none);
        color: var(--c-on-dark);
    }

    &__cap {
        position: absolute;
        left: 18rpx;
        right: 12rpx;
        bottom: 16rpx;
        z-index: 2;
        font-size: var(--fs-18);
        color: var(--c-on-dark);
    }
}

@media screen and (max-width: 359px) {
    .panel {
        padding-left: var(--sp-3);
        padding-right: var(--sp-3);
    }
}
</style>
