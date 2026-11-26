<template>
    <view class="banner-wrap">
        <!-- 舞台：高度由后台配置推导；小图模式可展开，大图模式固定 -->
        <view
            class="banner"
            :style="{ height: stageHeight + 'px' }"
            aria-role="img"
            :aria-label="ariaText"
        >
            <swiper
                v-if="visibleItems.length"
                class="banner__swiper"
                :current="current"
                :autoplay="autoPlay"
                :interval="config.interval"
                :duration="420"
                :circular="visibleItems.length > 1"
                @change="onChange"
            >
                <swiper-item v-for="(item, i) in visibleItems" :key="item.key">
                    <view class="banner__slide" @tap="onSlideTap(i)">
                        <image
                            v-if="item.type === 'image'"
                            class="banner__media"
                            :src="item.src"
                            mode="aspectFill"
                            @error="onImageError(item.key)"
                        />
                        <template v-else>
                            <!-- 点播放后在轮播位内直接播：不再弹全屏遮罩。
                                 不要给 video 挂 catchtap —— 会截走原生控制条的点击 -->
                            <video
                                v-if="playingKey === item.key"
                                class="banner__video"
                                :src="item.src"
                                :poster="item.poster"
                                :autoplay="true"
                                :controls="true"
                                :show-fullscreen-btn="false"
                                :show-center-play-btn="false"
                                object-fit="contain"
                                @ended="stopVideo"
                                @error="onVideoError"
                            />
                            <template v-else>
                                <image
                                    v-if="item.poster"
                                    class="banner__media"
                                    :src="item.poster"
                                    mode="aspectFill"
                                    @error="onPosterError(item.key)"
                                />
                                <view v-else class="banner__media banner__media--blank" />
                                <view class="banner__play" aria-hidden="true">
                                    <view class="banner__play-tri" />
                                </view>
                            </template>
                        </template>
                    </view>
                </swiper-item>
            </swiper>

            <!-- 无轮播时：cover 全宽铺；只有 avatar 时居中放，避免方图被裁成胸口 -->
            <view
                v-else-if="fallback && !fallbackFailed"
                class="banner__fallback"
                :class="{ 'banner__fallback--avatar': fallbackKind === 'avatar' }"
            >
                <image
                    v-if="fallbackKind === 'avatar'"
                    class="banner__avatar"
                    :src="fallback"
                    mode="aspectFill"
                    @error="fallbackFailed = true"
                />
                <image
                    v-else
                    class="banner__media"
                    :src="fallback"
                    mode="aspectFill"
                    @error="fallbackFailed = true"
                />
            </view>
            <view v-else class="banner__blank">
                <text class="banner__initial">{{ initial }}</text>
                <text class="banner__blank-note">暂未上传照片</text>
            </view>

            <template v-if="!isAvatarFallback">
                <view class="banner__shade banner__shade--top" aria-hidden="true" />
                <view class="banner__shade banner__shade--bottom" aria-hidden="true" />
            </template>

            <!-- 指示器：5 种样式共用左下同一基线，右侧固定给展开按钮 -->
            <view
                v-if="showIndicator && !playingKey"
                class="banner__indicator"
                :class="`banner__indicator--${config.indicatorStyle}`"
                aria-hidden="true"
            >
                <template v-if="config.indicatorStyle === 1">
                    <view
                        v-for="(item, i) in visibleItems"
                        :key="item.key"
                        class="banner__dot"
                        :class="{ 'is-on': i === current }"
                    />
                </template>

                <text v-else-if="config.indicatorStyle === 2" class="banner__number">
                    {{ pad(current + 1) }}
                    <text class="banner__number-dim">/ {{ pad(visibleItems.length) }}</text>
                </text>

                <template v-else-if="config.indicatorStyle === 3">
                    <view
                        v-for="(item, i) in visibleItems"
                        :key="item.key"
                        class="banner__seg"
                        :class="{ 'is-tight': visibleItems.length > 8 }"
                    >
                        <view class="banner__seg-track">
                            <view
                                v-if="i === current"
                                class="banner__seg-fill"
                                :class="{ 'is-static': !autoPlay }"
                                :style="progressStyle"
                            />
                            <view v-else-if="i < current" class="banner__seg-fill is-done" />
                        </view>
                    </view>
                </template>

                <!-- 长条形：占满整宽，按条目数均分整条进度（已看=满、当前=动画、未看=空） -->
                <view v-else-if="config.indicatorStyle === 4" class="banner__longbar">
                    <view
                        v-for="(item, i) in visibleItems"
                        :key="item.key"
                        class="banner__longbar-seg"
                    >
                        <view
                            v-if="i === current"
                            class="banner__longbar-fill"
                            :class="{ 'is-static': !autoPlay }"
                            :style="progressStyle"
                        />
                        <view v-else-if="i < current" class="banner__longbar-fill is-done" />
                    </view>
                </view>
            </view>

            <view
                v-if="canExpand && !playingKey"
                class="banner__expand"
                :class="{ 'is-raised': config.indicatorStyle === 4 }"
                hover-class="is-pressed"
                aria-role="button"
                :aria-label="expanded ? '收起轮播图' : '展开轮播图'"
                @tap.stop="toggleExpand"
            >
                <text class="banner__expand-text">{{ expanded ? '收起' : '展开' }}</text>
                <view class="banner__caret" :class="{ 'is-up': expanded }" />
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
/**
 * 人员详情轮播。
 *
 * 高度、指示器、自动轮播全部来自后台配置（见 ../banner.ts）：
 *  - mode=1：smallHeight 起步，可展开到 largeHeight；
 *  - mode=2：固定 largeHeight，无展开入口；
 *  - indicatorStyle 0–4；单张页不自动翻页；
 *  - 图片失败从序列移除，全部失败回落 cover/avatar，再回落姓氏首字。
 *
 * 视频：轮播照常按后台顺序自动翻页（视频页停在封面 + 播放键，不会卡住轮播）；
 * 点播放键后在轮播位内直接播（不弹全屏），播完自动回到轮播、恢复自动翻页；
 * 播放中手动滑走也会结束内嵌播放。
 */
import { computed, ref, watch } from 'vue'
import { toast } from '@/composables/useToast'
import {
    bannerStageHeight,
    canExpandBanner,
    type BannerConfig,
    type BannerSlide
} from '../banner'

const props = withDefaults(
    defineProps<{
        items?: BannerSlide[]
        config: BannerConfig
        fallback?: string
        /** cover=全宽铺；avatar=居中方图（方头像直接 aspectFill 会只剩胸口） */
        fallbackKind?: 'cover' | 'avatar'
        name?: string
    }>(),
    {
        items: () => [],
        fallback: '',
        fallbackKind: 'cover',
        name: ''
    }
)

const emit = defineEmits<{
    (e: 'expand', v: boolean): void
    (e: 'visual', v: boolean): void
}>()

const current = ref(0)
const expanded = ref(false)
const failedKeys = ref<string[]>([])
const fallbackFailed = ref(false)
/** 正在轮播位内播放的视频 key；空串=没有视频在播 */
const playingKey = ref('')

const visibleItems = computed(() => props.items.filter((item) => !failedKeys.value.includes(item.key)))

const stageHeight = computed(() => bannerStageHeight(props.config, expanded.value))
// 无轮播（仅 cover/avatar 兜底）时不出现展开按钮；只有真实轮播条目才允许展开
const canExpand = computed(() => visibleItems.value.length > 0 && canExpandBanner(props.config))
const showIndicator = computed(
    () => visibleItems.value.length > 1 && props.config.indicatorStyle !== 0
)
const isAvatarFallback = computed(
    () => !visibleItems.value.length && props.fallbackKind === 'avatar' && !fallbackFailed.value
)
/**
 * 自动翻页：只在「有视频正在内嵌播放」时暂停。
 * 视频页本身不能停轮播 —— 之前把 type === 'video' 也算进去，
 * 轮播一到视频就停住，后面的条目永远看不到。
 */
const autoPlay = computed(
    () => props.config.autoplay && visibleItems.value.length > 1 && !playingKey.value
)

const progressStyle = computed(() => ({ animationDuration: `${props.config.interval}ms` }))

/** 是否有一张可用的宽幅画面：失败剔除后仍有轮播，或 cover 兜底没失败 */
const hasWideVisual = computed(
    () =>
        visibleItems.value.length > 0 ||
        (props.fallbackKind === 'cover' && !!props.fallback && !fallbackFailed.value)
)

const initial = computed(() => {
    const name = String(props.name || '').trim()
    return name ? Array.from(name)[0] : '主'
})

const ariaText = computed(() => {
    if (!visibleItems.value.length) return `${props.name || '人员'}的照片`
    return `第 ${current.value + 1} 张，共 ${visibleItems.value.length} 张`
})

function pad(n: number) {
    return String(n).padStart(2, '0')
}

function clampCurrent() {
    const max = visibleItems.value.length - 1
    if (max < 0) current.value = 0
    else if (current.value > max) current.value = max
}

watch(
    () => props.items.map((item) => item.key).join('|'),
    () => {
        failedKeys.value = []
        current.value = 0
        expanded.value = false
        playingKey.value = ''
        fallbackFailed.value = false
    }
)

watch(visibleItems, clampCurrent)
watch(
    () => props.fallback,
    () => {
        fallbackFailed.value = false
    }
)
watch(hasWideVisual, (value) => emit('visual', value), { immediate: true })

watch(canExpand, (value) => {
    if (!value && expanded.value) {
        expanded.value = false
        emit('expand', false)
    }
})

function onChange(e: any) {
    const index = Number(e?.detail?.current ?? 0)
    // 播放中滑到别页：结束内嵌播放，让自动轮播接着走
    if (playingKey.value) {
        const playingIndex = visibleItems.value.findIndex((it) => it.key === playingKey.value)
        if (playingIndex !== -1 && playingIndex !== index) stopVideo()
    }
    if (index !== current.value) current.value = index
}

function toggleExpand() {
    if (!canExpand.value) return
    expanded.value = !expanded.value
    emit('expand', expanded.value)
}

function onImageError(key: string) {
    if (!failedKeys.value.includes(key)) failedKeys.value = [...failedKeys.value, key]
}

function onPosterError(key: string) {
    // 封面失败不剔除视频本身：黑底 + 播放键仍然可点
    const item = props.items.find((it) => it.key === key)
    if (item && !item.src) onImageError(key)
}

function onSlideTap(index: number) {
    const item = visibleItems.value[index]
    if (!item) return

    if (item.type === 'video') {
        // 视频在轮播位内播放，不再打开全屏遮罩
        if (item.src) playingKey.value = item.key
        else toast.info('视频暂时无法播放')
        return
    }

    const urls = visibleItems.value
        .filter((it) => it.type === 'image' && it.src)
        .map((it) => it.src)
    if (!item.src || !urls.length) return
    uni.previewImage({ urls, current: item.src })
}

/** 结束内嵌播放：恢复封面 + 自动翻页 */
function stopVideo() {
    playingKey.value = ''
}

function onVideoError() {
    stopVideo()
    toast.info('视频暂时无法播放')
}
</script>

<style lang="scss" scoped>
.banner-wrap {
    position: relative;
}

.banner {
    position: relative;
    overflow: hidden;
    background: var(--c-dark-2);
    transition: height 260ms cubic-bezier(0.22, 0.61, 0.36, 1);

    &__swiper {
        width: 100%;
        height: 100%;
    }

    &__slide {
        width: 100%;
        height: 100%;
    }

    &__media {
        display: block;
        width: 100%;
        height: 100%;
        background: var(--c-surface-2);

        &--blank {
            background: var(--c-dark-2);
        }
    }

    /* 内嵌视频：铺满整个轮播位，控件由原生 video 提供 */
    &__video {
        display: block;
        width: 100%;
        height: 100%;
        background: var(--c-dark-2);
    }

    &__fallback {
        width: 100%;
        height: 100%;

        &--avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--c-hero-bg);
        }
    }

    &__avatar {
        width: 208rpx;
        height: 208rpx;
        border-radius: var(--r-lg);
        border: 1rpx solid var(--c-line-strong);
        background: var(--c-surface-2);
    }

    &__blank {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        background: var(--c-hero-bg);
    }

    &__initial {
        font-family: var(--font-serif);
        font-size: var(--fs-69);
        color: var(--c-brand-text);
        line-height: var(--lh-none);
    }

    &__blank-note {
        margin-top: var(--sp-2);
        font-size: var(--fs-20);
        color: var(--c-ink-3);
    }

    &__shade {
        position: absolute;
        left: 0;
        right: 0;
        pointer-events: none;

        &--top {
            top: 0;
            height: 176rpx;
            background: linear-gradient(180deg, rgba(14, 12, 10, 0.46), rgba(14, 12, 10, 0));
        }

        &--bottom {
            bottom: 0;
            height: 152rpx;
            background: linear-gradient(0deg, rgba(14, 12, 10, 0.52), rgba(14, 12, 10, 0));
        }
    }

    &__play {
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        display: flex;
        align-items: center;
        justify-content: center;
        width: 96rpx;
        height: 96rpx;
        border-radius: var(--r-pill);
        border: 1rpx solid rgba(255, 255, 255, 0.46);
        background: rgba(20, 17, 15, 0.42);
    }

    &__play-tri {
        width: 0;
        height: 0;
        margin-left: 6rpx;
        border-left: 24rpx solid var(--c-on-dark);
        border-top: 14rpx solid transparent;
        border-bottom: 14rpx solid transparent;
    }

    &__indicator {
        position: absolute;
        left: var(--sp-4);
        bottom: 56rpx;
        z-index: 3;
        display: flex;
        align-items: center;

        &--1 {
            gap: 10rpx;
        }

        &--3 {
            gap: 8rpx;
        }

        /* 长条形独占整宽：不再和左内边距/展开按钮抢位置 */
        &--4 {
            left: 0;
            right: 0;
            bottom: 56rpx;
            display: block;
        }
    }

    &__dot {
        width: 8rpx;
        height: 8rpx;
        border-radius: var(--r-pill);
        background: rgba(255, 255, 255, 0.46);
        transition: width 180ms ease, background 180ms ease;

        &.is-on {
            width: 22rpx;
            background: rgba(255, 255, 255, 0.96);
        }
    }

    &__number {
        padding: 6rpx 14rpx;
        border-radius: var(--r-pill);
        border: 1rpx solid rgba(255, 255, 255, 0.14);
        background: rgba(20, 17, 15, 0.24);
        font-family: var(--font-serif);
        font-size: var(--fs-22);
        letter-spacing: 2rpx;
        color: rgba(255, 255, 255, 0.96);
        line-height: var(--lh-none);
    }

    &__number-dim {
        color: rgba(255, 255, 255, 0.45);
    }

    &__seg {
        width: 36rpx;

        &.is-tight {
            width: 24rpx;
        }
    }

    &__seg-track {
        width: 100%;
        height: 4rpx;
        overflow: hidden;
        border-radius: var(--r-pill);
        background: rgba(255, 255, 255, 0.32);
    }

    &__seg-fill {
        height: 100%;
        width: 0;
        border-radius: var(--r-pill);
        background: rgba(255, 255, 255, 0.95);
        animation: bannerProgress linear forwards;

        &.is-done {
            width: 100%;
            animation: none;
        }

        &.is-static {
            width: 100%;
            animation: none;
        }
    }

    /* 整宽长条：每段等分，段间留 2rpx 细分隔线（露出图片=分隔） */
    &__longbar {
        display: flex;
        width: 100%;
        height: 4rpx;
        gap: 2rpx;
    }

    &__longbar-seg {
        flex: 1;
        min-width: 0;
        height: 100%;
        overflow: hidden;
        background: rgba(255, 255, 255, 0.32);
    }

    &__longbar-fill {
        height: 100%;
        width: 0;
        background: rgba(255, 255, 255, 0.95);
        animation: bannerProgress linear forwards;

        &.is-done {
            width: 100%;
            animation: none;
        }

        &.is-static {
            width: 100%;
            animation: none;
        }
    }

    &__expand {
        position: absolute;
        right: 24rpx;
        bottom: 52rpx;
        z-index: 4;
        display: inline-flex;
        align-items: center;
        gap: 8rpx;
        height: 52rpx;
        padding: 0 20rpx;
        border-radius: var(--r-pill);
        border: 1rpx solid rgba(255, 255, 255, 0.18);
        background: rgba(20, 17, 15, 0.46);

        /* 长条形整宽压在 56rpx 基线上，展开键抬高一层，避免压住进度 */
        &.is-raised {
            bottom: 116rpx;
        }
    }

    &__expand-text {
        font-size: var(--fs-20);
        color: var(--c-on-dark);
        line-height: var(--lh-none);
    }

    &__caret {
        width: 8rpx;
        height: 8rpx;
        border-right: 1rpx solid var(--c-on-dark);
        border-bottom: 1rpx solid var(--c-on-dark);
        transform: rotate(45deg) translateY(-2rpx);

        &.is-up {
            transform: rotate(-135deg) translateY(-2rpx);
        }
    }
}

@keyframes bannerProgress {
    from {
        width: 0;
    }
    to {
        width: 100%;
    }
}
</style>
