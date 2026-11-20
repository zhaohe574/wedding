<template>
    <view v-if="blocks.length" class="ld">
        <!-- 章节标记：细线 + 小字，不和作者自己的标题抢层级 -->
        <view class="ld__marker" aria-hidden="true">
            <view class="ld__marker-line" />
            <text class="ld__marker-text">更多介绍</text>
            <view class="ld__marker-line" />
        </view>

        <template v-for="(block, index) in blocks" :key="index">
            <view
                v-if="block.type === 'text'"
                class="ld__text"
                :class="{ 'is-after-image': index > 0 && blocks[index - 1].type === 'image' }"
            >
                <text class="ld__text-inner" :style="textStyle(block.style)">{{ block.content }}</text>
            </view>

            <view
                v-else
                class="ld__images"
                :class="{
                    'is-first': index === 0,
                    'is-flush': index > 0 && blocks[index - 1].type === 'image'
                }"
            >
                <template v-for="(image, imageIndex) in block.images" :key="`${index}-${imageIndex}`">
                    <view v-if="failedImages.includes(image)" class="ld__ph">
                        <text class="ld__ph-text">图片加载失败</text>
                    </view>
                    <image
                        v-else
                        class="ld__img"
                        :class="{ 'is-loaded': loadedImages.includes(image) }"
                        :src="image"
                        mode="widthFix"
                        lazy-load
                        :aria-label="`长图详情第 ${imageIndex + 1} 张`"
                        @load="onImageLoad(image)"
                        @error="onImageError(image)"
                        @tap="preview(block.images, imageIndex)"
                    />
                </template>
            </view>
        </template>

        <view class="ld__end">
            <text class="ld__source">以上图文由老师提供，最终以服务确认单为准</text>
            <view class="ld__more" hover-class="is-pressed" aria-role="button" @tap="emit('works')">
                <text class="ld__more-text">继续看作品</text>
                <tn-icon name="right" :size="20" class="ld__more-icon" />
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
/**
 * 长图详情（后台 long_detail 装修内容）。
 *
 * 后台是块结构：image.images[] 按顺序首尾无间距；text.content + style。
 * 前台不做二次排版：
 *  - 图片全宽、零间距、零圆角，连续图片块之间也合并；
 *  - 文本回到 42rpx 栏宽，字号 sm/md/lg → 26/30/34rpx；
 *  - 非 JSON 旧数据按纯文本分段兜底；空内容整段隐藏。
 */
import { computed, ref, watch } from 'vue'
import { useAppStore } from '@/stores/app'

type Align = 'left' | 'center' | 'right'
type FontSize = 'sm' | 'md' | 'lg'

interface LongTextStyle {
    align: Align
    fontSize: FontSize
    color: string
    bold: boolean
}

type LongBlock =
    | { type: 'text'; content: string; style: LongTextStyle }
    | { type: 'image'; images: string[] }

const props = withDefaults(
    defineProps<{
        content?: string
    }>(),
    { content: '' }
)

const emit = defineEmits<{ (e: 'works'): void }>()

const appStore = useAppStore()
const failedImages = ref<string[]>([])
const loadedImages = ref<string[]>([])

const DEFAULT_STYLE: LongTextStyle = {
    align: 'left',
    fontSize: 'md',
    color: 'var(--c-ink)',
    bold: false
}

const SIZE_MAP: Record<FontSize, string> = {
    sm: 'var(--fs-26)',
    md: 'var(--fs-30)',
    lg: 'var(--fs-34)'
}

function toUrl(url?: string) {
    const value = String(url || '').trim()
    if (!value) return ''
    if (/^(https?:)?\/\//.test(value) || value.startsWith('data:')) return value
    const domain = (appStore.config && appStore.config.domain) || ''
    return domain ? `${domain}${value}` : value
}

function normalizeStyle(raw: any): LongTextStyle {
    const align = ['left', 'center', 'right'].includes(raw?.align) ? raw.align : 'left'
    const fontSize = ['sm', 'md', 'lg'].includes(raw?.fontSize) ? raw.fontSize : 'md'
    const color =
        typeof raw?.color === 'string' && /^#[0-9a-f]{3,8}$/i.test(raw.color.trim())
            ? raw.color.trim()
            : DEFAULT_STYLE.color
    return { align, fontSize, color, bold: Boolean(raw?.bold) }
}

function imageUrlOf(item: any) {
    if (typeof item === 'string') return item.trim()
    return String(item?.url || item?.path || item?.src || '').trim()
}

function stripHtml(input: string) {
    return input
        .replace(/<br\s*\/?>/gi, '\n')
        .replace(/<\/(p|div|li|h[1-6])>/gi, '\n')
        .replace(/<[^>]+>/g, '')
        .replace(/&nbsp;/gi, ' ')
        .replace(/&amp;/gi, '&')
        .replace(/&lt;/gi, '<')
        .replace(/&gt;/gi, '>')
        .replace(/&quot;/gi, '"')
        .replace(/&#39;/gi, "'")
        .replace(/\n{3,}/g, '\n\n')
        .trim()
}

function fallbackBlocks(raw: string): LongBlock[] {
    // 解析失败的 JSON（以 [ / { 开头）不把原文当正文展示，避免整页出现 JSON 字符串
    if (/^\s*[[{]/.test(raw)) return []
    const text = stripHtml(raw)
    if (!text) return []
    return text
        .split(/\n{2,}/)
        .map((content) => content.trim())
        .filter(Boolean)
        .map((content) => ({ type: 'text' as const, content, style: { ...DEFAULT_STYLE } }))
}

const blocks = computed<LongBlock[]>(() => {
    const raw = String(props.content || '').trim()
    if (!raw) return []

    let parsed: any
    try {
        parsed = JSON.parse(raw)
    } catch {
        return fallbackBlocks(raw)
    }
    if (!Array.isArray(parsed)) return fallbackBlocks(raw)

    const list: LongBlock[] = []
    for (const item of parsed) {
        if (!item || typeof item !== 'object') continue

        if (item.type === 'image') {
            const images = (Array.isArray(item.images) ? item.images : [])
                .map(imageUrlOf)
                .filter(Boolean)
                .map(toUrl)
            if (images.length) list.push({ type: 'image', images })
            continue
        }

        if (item.type === 'text') {
            const content = String(item.content || '').trim()
            if (content) list.push({ type: 'text', content, style: normalizeStyle(item.style) })
        }
    }
    return list
})

watch(
    () => props.content,
    () => {
        failedImages.value = []
        loadedImages.value = []
    }
)

function textStyle(style: LongTextStyle) {
    return {
        textAlign: style.align,
        fontSize: SIZE_MAP[style.fontSize] || SIZE_MAP.md,
        color: style.color || 'var(--c-ink-2)',
        fontWeight: style.bold ? 600 : 400
    }
}

function onImageLoad(image: string) {
    if (!loadedImages.value.includes(image)) loadedImages.value = [...loadedImages.value, image]
}

function onImageError(image: string) {
    if (!failedImages.value.includes(image)) failedImages.value = [...failedImages.value, image]
}

function preview(images: string[], index: number) {
    const urls = images.filter((image) => !failedImages.value.includes(image))
    const current = images[index]
    if (!current || !urls.length) return
    uni.previewImage({ urls, current })
}
</script>

<style lang="scss" scoped>
.ld {
    padding-bottom: var(--sp-3);

    &__marker {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
        margin: var(--sp-4) 42rpx var(--sp-3);
    }

    &__marker-line {
        flex: 1;
        height: 1rpx;
        background: var(--c-line);
    }

    &__marker-text {
        font-size: var(--fs-20);
        letter-spacing: 4rpx;
        color: var(--c-ink-3);
        line-height: var(--lh-none);
    }

    &__text {
        padding: 0 42rpx;

        &.is-after-image {
            margin-top: var(--sp-4);
        }
    }

    &__text + &__text {
        margin-top: var(--sp-3);
    }

    &__text-inner {
        display: block;
        max-width: 516px;
        margin: 0 auto;
        font-size: var(--fs-30);
        line-height: var(--lh-loose);
        color: var(--c-ink-2);
        white-space: pre-wrap;
        word-break: break-word;
    }

    /* 图片块：全宽铺满，块内/块间都不留缝 */
    &__images {
        width: 100%;
        margin-top: var(--sp-4);
        font-size: 0;
        line-height: 0;

        &.is-first,
        &.is-flush {
            margin-top: 0;
        }
    }

    &__img {
        display: block;
        width: 100%;
        background: var(--c-surface-2);
        opacity: 0;
        transition: opacity 200ms ease-out;

        &.is-loaded {
            opacity: 1;
        }
    }

    &__ph {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 360rpx;
        background: var(--c-surface-2);
    }

    &__ph-text {
        font-size: var(--fs-22);
        color: var(--c-ink-3);
    }

    &__end {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: var(--sp-2);
        padding: var(--sp-5) 42rpx 0;
    }

    &__source {
        font-size: var(--fs-18);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
        text-align: center;
    }

    &__more {
        display: inline-flex;
        align-items: center;
        gap: 6rpx;
        padding: 8rpx 0;
    }

    &__more-text {
        font-size: var(--fs-24);
        color: var(--c-brand-text);
    }

    &__more-icon {
        color: var(--c-brand-text);
    }
}

@media screen and (max-width: 359px) {
    .ld {
        &__marker {
            margin-left: var(--sp-3);
            margin-right: var(--sp-3);
        }

        &__text {
            padding-left: var(--sp-3);
            padding-right: var(--sp-3);
        }

        &__end {
            padding-left: var(--sp-3);
            padding-right: var(--sp-3);
        }
    }
}
</style>
