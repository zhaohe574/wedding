<template>
    <!--
        作品详情（消费端）。
        结构：多媒体展区（视频 / 图集切换 + 缩略图条）+ 作品信息卡 + 创作者名片 + 吸底操作栏。
        数据：staff.workDetail 单接口；创作者信息取作品数据里的 staff / staff_info。
        入口：人员详情作品页签（可返回）、分享直达（此时看主页走 navigateTo）。
    -->
    <page-shell
        bg="page"
        :bottom-bar="true"
        nav
        nav-variant="solid"
        nav-background="page"
        title="作品详情"
    >
        <!-- 加载态 -->
        <view v-if="loading" class="ph">
            <base-skeleton :rows="4" />
        </view>

        <!-- 错误态：整页重试 -->
        <base-empty
            v-else-if="error"
            status="error"
            :error-kind="view?.errorKind || 'unknown'"
            :error-text="view?.title"
            :desc="view?.desc"
            :primary-action="view?.primaryAction"
            fixed
            @primary="onPrimary"
            @retry="load"
        />

        <!-- 空态：作品不存在或已下架 -->
        <base-empty
            v-else-if="!work?.id"
            status="empty"
            empty-text="作品不存在或已下架"
            desc="可以去看看 TA 的其他作品"
            show-action
            action-text="返回上一页"
            fixed
            @action="goBack"
        />

        <template v-else>
            <!-- ========== 多媒体展区 ========== -->
            <view class="hero">
                <!-- 切换胶囊：视频与图集都有时才出现；等宽两段 + 滑动底块给出「滑块感」 -->
                <view v-if="videoUrl && galleryImages.length" class="hero__tabs" aria-role="tablist">
                    <view
                        class="hero__seg"
                        :class="{ 'is-right': mediaMode !== 'video' }"
                        aria-hidden="true"
                    />
                    <view
                        class="hero__tab"
                        :class="{ 'is-on': mediaMode === 'video' }"
                        hover-class="is-pressed"
                        aria-role="tab"
                        :aria-selected="mediaMode === 'video'"
                        @tap="switchMedia('video')"
                    >
                        <tn-icon name="play-fill" :size="20" class="hero__tab-ic" />
                        <text>视频</text>
                    </view>
                    <view
                        class="hero__tab"
                        :class="{ 'is-on': mediaMode === 'gallery' }"
                        hover-class="is-pressed"
                        aria-role="tab"
                        :aria-selected="mediaMode === 'gallery'"
                        @tap="switchMedia('gallery')"
                    >
                        <tn-icon name="image" :size="20" class="hero__tab-ic" />
                        <text>图集({{ galleryImages.length }})</text>
                    </view>
                </view>

                <!-- 视频模式 -->
                <view v-if="!galleryActive" class="hero__stage">
                    <video
                        class="hero__video"
                        :src="videoUrl"
                        :poster="coverUrl || undefined"
                        object-fit="contain"
                        controls
                        show-center-play-btn
                        enable-play-gesture
                    />
                </view>

                <!-- 图集模式：轮播 + 大图预览 + 页码，多图时带缩略图条 -->
                <template v-else-if="galleryImages.length">
                    <view class="hero__stage">
                        <swiper
                            class="hero__swiper"
                            :circular="galleryImages.length > 1"
                            :current="currentIndex"
                            @change="onSwiperChange"
                        >
                            <swiper-item v-for="(img, idx) in galleryImages" :key="img + idx">
                                <image
                                    class="hero__img"
                                    :src="img"
                                    mode="aspectFill"
                                    @tap="previewImages(idx)"
                                />
                            </swiper-item>
                        </swiper>
                        <text class="hero__hint">点击查看大图</text>
                        <text v-if="galleryImages.length > 1" class="hero__page">
                            {{ currentIndex + 1 }}/{{ galleryImages.length }}
                        </text>
                    </view>
                    <scroll-view
                        v-if="galleryImages.length > 1"
                        class="hero__thumbs"
                        scroll-x
                        :show-scrollbar="false"
                    >
                        <view class="hero__thumbs-row">
                            <image
                                v-for="(img, idx) in galleryImages"
                                :key="'thumb-' + idx"
                                class="hero__thumb"
                                :class="{ 'is-on': idx === currentIndex }"
                                :src="img"
                                mode="aspectFill"
                                @tap="currentIndex = idx"
                            />
                        </view>
                    </scroll-view>
                </template>

                <!-- 均无媒体：居中占位 -->
                <view v-else class="hero__blank">
                    <tn-icon name="image" :size="56" />
                    <text class="hero__blank-text">暂无媒体预览</text>
                </view>
            </view>

            <!-- ========== 作品信息卡 ========== -->
            <view class="card">
                <view class="card__meta">
                    <text class="card__type">{{ typeLabel }}</text>
                    <text v-if="shootDate" class="card__meta-item">
                        <tn-icon name="calendar" :size="20" class="card__meta-ic" />
                        {{ shootDate }}
                    </text>
                    <text v-if="workLocation" class="card__meta-item">
                        <tn-icon name="location" :size="20" class="card__meta-ic" />
                        {{ workLocation }}
                    </text>
                    <text class="card__meta-item">
                        <tn-icon name="eye" :size="20" class="card__meta-ic" />
                        {{ viewCount }} 浏览
                    </text>
                </view>
                <text class="card__title">{{ work.title || '精选作品' }}</text>
                <text v-if="work.description" class="card__desc">{{ work.description }}</text>
            </view>

            <!-- ========== 创作者名片 ========== -->
            <view
                v-if="staffInfo?.id"
                class="creator"
                hover-class="is-pressed"
                aria-role="button"
                :aria-label="`查看${staffInfo.name || 'TA'}的主页`"
                @tap="goStaff"
            >
                <image
                    v-if="staffAvatar"
                    class="creator__av"
                    :src="staffAvatar"
                    mode="aspectFill"
                />
                <view v-else class="creator__av creator__av--txt">
                    <text class="creator__av-text">{{ avatarInitial }}</text>
                </view>
                <view class="creator__who">
                    <text class="creator__name ellipsis">{{ staffInfo.name || '主持人' }}</text>
                    <view v-if="staffRating > 0 || staffOrderCount > 0" class="creator__stats">
                        <text v-if="staffRating > 0" class="creator__stat">
                            <tn-icon name="star-fill" :size="18" class="creator__stat-ic" />
                            {{ staffRating.toFixed(1) }}
                        </text>
                        <text v-if="staffOrderCount > 0" class="creator__stat">
                            {{ staffOrderCount }} 场
                        </text>
                    </view>
                </view>
                <view class="creator__aside">
                    <text class="creator__aside-label">服务起价</text>
                    <price v-if="staffPriceIsNumeric" size="sm" :content="staffPriceRaw || 0" />
                    <text v-else class="creator__aside-na">{{ staffPriceText || '面议' }}</text>
                </view>
                <tn-icon name="right" :size="20" class="creator__arrow" />
            </view>
        </template>

        <!-- ========== 吸底操作栏 ========== -->
        <template #bottom>
            <bottom-bar v-if="!loading && !error && staffInfo?.id">
                <view class="abp">
                    <text class="abp__label">服务起价</text>
                    <price v-if="staffPriceIsNumeric" size="md" :content="staffPriceRaw || 0" />
                    <text v-else class="abp__na">{{ staffPriceText || '面议' }}</text>
                </view>
                <template #actions>
                    <view class="abtns">
                        <base-button
                            text="查看主页"
                            variant="ghost"
                            size="md"
                            :block="false"
                            @click="goStaff"
                        />
                        <base-button
                            text="预约档期"
                            variant="ink"
                            size="md"
                            :block="false"
                            @click="goBooking"
                        />
                    </view>
                </template>
            </bottom-bar>
        </template>
    </page-shell>

    <app-toast />
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onLoad, onShareAppMessage } from '@dcloudio/uni-app'
import { getWorkDetail } from '@/api/catalog'
import { report } from '@/platform/report'
import { navigate } from '@/utils/link'
import { haptic } from '@/utils/haptics'
import { buildShare } from '@/utils/share'
import { useAppStore } from '@/stores/app'
import { resolveErrorView } from '@/utils/resolveErrorView'

const appStore = useAppStore()

const workId = ref(0)
const work = ref<any>(null)
const loading = ref(true)
const rawError = ref<unknown>(null)
const error = computed(() => !!rawError.value)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))

const mediaMode = ref<'video' | 'gallery'>('gallery')
const currentIndex = ref(0)

/** 相对路径补全域名（与 staff-works-panel 同规则） */
function resolveUrl(url?: string): string {
    if (!url) return ''
    if (/^https?:/.test(url)) return url
    const domain = (appStore.config && appStore.config.domain) || ''
    return domain ? `${domain}${url}` : url
}

const videoUrl = computed(() => resolveUrl(work.value?.video))
const coverUrl = computed(() => resolveUrl(work.value?.cover))

/** 图集：数组 / 对象 / 逗号分隔字符串都归一化，按 URL 去重保序；为空回退封面 */
const galleryImages = computed<string[]>(() => {
    const raw = work.value?.images
    const rawList: any[] = Array.isArray(raw)
        ? raw
        : typeof raw === 'string' && raw.trim()
          ? raw.split(/[,，\s]+/)
          : []
    const seen = new Set<string>()
    const urls: string[] = []
    for (const item of rawList) {
        const url = resolveUrl(
            typeof item === 'string'
                ? item
                : item?.url || item?.image || item?.path || item?.src || ''
        )
        if (!url || seen.has(url)) continue
        seen.add(url)
        urls.push(url)
    }
    if (!urls.length && coverUrl.value) return [coverUrl.value]
    return urls
})

/** 视频优先：选了视频但地址缺失时降级看图集，避免落进「暂无媒体」死角 */
const galleryActive = computed(() => !(mediaMode.value === 'video' && videoUrl.value))

const staffInfo = computed<any>(() => work.value?.staff || work.value?.staff_info || null)
const staffAvatar = computed(() => resolveUrl(staffInfo.value?.avatar || ''))
const avatarInitial = computed(() => String(staffInfo.value?.name || '·').slice(0, 1))
const staffRating = computed(() => Number(staffInfo.value?.rating || 0))
const staffOrderCount = computed(() => Number(staffInfo.value?.order_count || 0))

/** 起价：price_text 优先；纯数字走 <price>，带文案的（如「¥3000 起」）原样展示 */
const staffPriceRaw = computed<string | number | null>(() => {
    const s = staffInfo.value || {}
    const raw = s.price_text ?? s.price
    return raw === undefined || raw === null || raw === '' ? null : (raw as number | string)
})
const staffPriceIsNumeric = computed(() => {
    const p = staffPriceRaw.value
    if (typeof p === 'number') return Number.isFinite(p)
    return p !== null && /^[\d.,\s]+$/.test(String(p))
})
const staffPriceText = computed(() =>
    staffPriceRaw.value === null ? '' : String(staffPriceRaw.value)
)

const typeLabel = computed(
    () => String(work.value?.type_desc || '').trim() || (videoUrl.value ? '视频作品' : '图片作品')
)
const shootDate = computed(() => String(work.value?.shoot_date || '').trim())
const workLocation = computed(() => String(work.value?.location || '').trim())
const viewCount = computed(() => Number(work.value?.view_count || 0))

function onSwiperChange(e: any) {
    currentIndex.value = Number(e?.detail?.current || 0)
}

/** 媒体切换：同一模式不重复触发，切换时给一次 light 触觉 */
function switchMedia(mode: 'video' | 'gallery') {
    if (mediaMode.value === mode) return
    haptic('light', 'work-media')
    mediaMode.value = mode
}

function previewImages(idx: number) {
    if (!galleryImages.value.length) return
    uni.previewImage({
        urls: galleryImages.value,
        current: galleryImages.value[idx]
    })
}

function goBack() {
    const pages = getCurrentPages()
    if (pages.length > 1) uni.navigateBack()
    else uni.switchTab({ url: '/pages/index/index' })
}

/** 上一页就是 TA 的主页时直接返回，避免页面栈里叠出重复的主页 */
function goStaff() {
    const sid = Number(staffInfo.value?.id || 0)
    if (!sid) return
    const pages = getCurrentPages()
    const prev: any = pages.length > 1 ? pages[pages.length - 2] : null
    if (String(prev?.route || '').includes('pkg-catalog/pages/staff-detail/staff-detail')) {
        uni.navigateBack()
        return
    }
    navigate({
        path: '/packages/pkg-catalog/pages/staff-detail/staff-detail',
        query: { id: sid }
    })
}

function goBooking() {
    const sid = Number(staffInfo.value?.id || 0)
    if (!sid) return
    navigate({
        path: '/packages/pkg-catalog/pages/booking/booking',
        query: { staff_id: sid }
    })
}

function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        load()
        return
    }
    if (kind === 'reselect' || kind === 'back') goBack()
}

async function load() {
    if (!workId.value) {
        loading.value = false
        return
    }
    loading.value = true
    rawError.value = null
    try {
        const res: any = await getWorkDetail({ id: workId.value })
        work.value = res?.detail || res?.data || res || null
        // 有视频默认播视频；纯图集默认看图
        mediaMode.value =
            Number(work.value?.type) === 2 || Boolean(videoUrl.value) ? 'video' : 'gallery'
        currentIndex.value = 0
    } catch (e) {
        rawError.value = e
        report('api_error', { endpoint: 'staff.workDetail', message: '作品详情加载失败' })
    } finally {
        loading.value = false
    }
}

onLoad((options: any) => {
    workId.value = Number(options?.id || 0)
    if (!workId.value) {
        loading.value = false
        return
    }
    load()
})

onShareAppMessage(() =>
    buildShare({
        title: String(work.value?.title || ''),
        path: '/packages/pkg-catalog/pages/work-detail/work-detail?id=' + workId.value,
        imageUrl: coverUrl.value || galleryImages.value[0] || undefined
    })
)
</script>

<style lang="scss" scoped>
/* ========== 骨架 ========== */
.ph {
    padding: var(--sp-4) var(--page-gutter) 0;
}

/* ========== 多媒体展区 ========== */
.hero {
    /* 等宽两段式切换：底槽 + 滑动底块（v10 §3.5 滑块感），不用下划线 */
    &__tabs {
        position: relative;
        display: flex;
        width: 420rpx;
        margin: var(--sp-3) auto 0;
        padding: 4rpx;
        box-sizing: border-box;
        border-radius: var(--r-pill);
        background: var(--c-surface-2);
        border: 1rpx solid var(--c-control-border);
    }

    /* 底块：始终占一半宽度，靠 translateX 滑动，过渡走 spring-soft */
    &__seg {
        position: absolute;
        top: 4rpx;
        left: 4rpx;
        width: calc(50% - 4rpx);
        height: calc(100% - 8rpx);
        border-radius: var(--r-pill);
        background: var(--c-active-bg);
        box-shadow: inset 0 0 0 1rpx var(--c-active-border);
        transition: transform var(--dur-base) var(--ease-spring-soft);

        &.is-right {
            transform: translate3d(100%, 0, 0);
        }
    }

    &__tab {
        position: relative;
        z-index: 1;
        flex: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6rpx;
        /* 视觉 60rpx，热区 88rpx：上下各扩 14rpx，与底槽留一点呼吸 */
        height: 60rpx;
        border-radius: var(--r-pill);
        color: var(--c-ink-3);
        font-size: var(--fs-22);
        line-height: var(--lh-none);
        transition: color var(--dur-fast) var(--ease-out);

        &::before {
            content: "";
            position: absolute;
            top: -14rpx;
            bottom: -14rpx;
            left: 0;
            right: 0;
        }

        &.is-on {
            color: var(--c-active-fg);
        }
    }

    &__tab-ic {
        color: inherit;
    }

    &__stage {
        position: relative;
        margin-top: var(--sp-3);
        background: var(--c-dark-2);
    }

    &__video {
        display: block;
        width: 100%;
        height: 460rpx;
    }

    &__swiper {
        width: 100%;
        height: 640rpx;
    }

    &__img {
        display: block;
        width: 100%;
        height: 100%;
    }

    &__hint,
    &__page {
        position: absolute;
        bottom: 20rpx;
        z-index: 2;
        padding: 6rpx 18rpx;
        border-radius: var(--r-pill);
        background: var(--c-mask);
        font-size: var(--fs-18);
        line-height: var(--lh-none);
        color: var(--c-on-dark);
    }

    &__hint {
        left: 24rpx;
    }

    &__page {
        right: 24rpx;
        font-variant-numeric: tabular-nums;
    }

    &__thumbs {
        margin-top: var(--sp-3);
        padding: 0 var(--page-gutter);
        box-sizing: border-box;
        white-space: nowrap;
    }

    &__thumbs-row {
        display: inline-flex;
        gap: var(--sp-2);
    }

    &__thumb {
        flex: none;
        width: 96rpx;
        height: 96rpx;
        border-radius: var(--r-sm);
        border: 2rpx solid transparent;
        box-sizing: border-box;

        &.is-on {
            border-color: var(--c-brand);
        }
    }

    &__blank {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: var(--sp-2);
        height: 420rpx;
        margin-top: var(--sp-3);
        background: var(--c-hero-bg);
        color: var(--c-ink-3);
    }

    &__blank-text {
        font-size: var(--fs-22);
        color: var(--c-ink-3);
    }
}

/* ========== 作品信息卡 ========== */
.card {
    margin: var(--sp-4) var(--page-gutter) 0;
    padding: 28rpx;
    border-radius: var(--r-lg);
    background: var(--c-surface);
    border: 1rpx solid var(--c-card-border);
    box-shadow: var(--shadow-card);

    &__meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: var(--sp-2);
        font-size: var(--fs-20);
        color: var(--c-ink-3);
    }

    &__type {
        padding: 5rpx 14rpx;
        border-radius: var(--r-pill);
        background: var(--c-brand-bg);
        color: var(--c-brand-text);
        font-size: var(--fs-18);
        line-height: var(--lh-none);
    }

    &__meta-item {
        display: inline-flex;
        align-items: center;
        gap: 4rpx;
    }

    &__meta-ic {
        color: var(--c-warning);
    }

    &__title {
        display: block;
        margin-top: 16rpx;
        font-size: var(--fs-40);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        line-height: var(--lh-tight);
    }

    &__desc {
        display: block;
        margin-top: 14rpx;
        font-size: var(--fs-25);
        line-height: var(--lh-loose);
        color: var(--c-ink-2);
        white-space: pre-wrap;
    }
}

/* ========== 创作者名片 ========== */
.creator {
    display: flex;
    align-items: center;
    gap: 18rpx;
    margin: var(--sp-4) var(--page-gutter) 0;
    padding: 24rpx;
    border-radius: var(--r-md);
    background: var(--c-surface);
    border: 1rpx solid var(--c-card-border);
    box-shadow: var(--shadow-card);

    &__av {
        flex: none;
        width: 88rpx;
        height: 88rpx;
        border-radius: var(--r-pill);
        background: var(--c-neutral-bg);

        &--txt {
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--c-brand-bg);
        }
    }

    &__av-text {
        font-family: var(--font-serif);
        font-size: var(--fs-30);
        color: var(--c-brand-text);
    }

    &__who {
        flex: 1;
        min-width: 0;
    }

    &__name {
        display: block;
        max-width: 240rpx;
        font-size: var(--fs-29);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        line-height: var(--lh-tight);
    }

    &__stats {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
        margin-top: 6rpx;
        font-size: var(--fs-20);
        color: var(--c-ink-3);
    }

    &__stat {
        display: inline-flex;
        align-items: center;
        gap: 4rpx;
    }

    &__stat-ic {
        color: var(--c-warning);
    }

    &__aside {
        flex: none;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 4rpx;
    }

    &__aside-label {
        font-size: var(--fs-18);
        color: var(--c-ink-3);
        letter-spacing: 1rpx;
    }

    &__aside-na {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &__arrow {
        flex: none;
        color: var(--c-ink-3);
    }
}

/* ========== 吸底操作栏 ========== */
.abp {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: 4rpx;
    min-width: 0;

    &__label {
        font-size: var(--fs-18);
        color: var(--c-ink-3);
        letter-spacing: 1rpx;
    }

    &__na {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }
}

.abtns {
    display: flex;
    flex: none;
    align-items: center;
    gap: var(--sp-2);
}

@media screen and (max-width: 359px) {
    .ph {
        padding-left: var(--sp-3);
        padding-right: var(--sp-3);
    }

    .card,
    .creator {
        margin-left: var(--sp-3);
        margin-right: var(--sp-3);
    }
}

@media (prefers-reduced-motion: reduce) {
    .hero__seg {
        transition: none;
    }
}
</style>
