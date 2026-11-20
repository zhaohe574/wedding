<template>
    <!-- error 态是整页空态、没有轮播承接；scroll-solid 在 scrollTop=0 时透明且无占位块，
         内容会顶到状态栏。故 error 直接走 solid（loading 有 .ph__hero，保持 scroll-solid）。 -->
    <page-shell
        bg="page"
        :bottom-bar="true"
        :nav-variant="error ? 'solid' : 'scroll-solid'"
        :scroll-top="scrollTop"
        :nav-solid-threshold="navSolidThreshold"
        :nav-tone="navTone"
        nav-background="page"
    >
        <!-- 导航栏不再放动作：姓名、收藏、分享都在身份卡里，避免与右上角胶囊争位 -->

        <!-- 加载态：舞台高度 + 身份卡骨架，页签立即可见 -->
        <view v-if="loading" class="ph">
            <view class="ph__hero" :style="{ height: skeletonHeight + 'px' }">
                <base-skeleton :rows="2" />
            </view>
            <view class="ph__card">
                <base-skeleton :rows="3" />
            </view>
        </view>

        <!-- 错误态：整页重试 + 返回列表 -->
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

        <template v-else>
            <!-- 轮播舞台：高度/指示器/自动轮播全部来自后台配置 -->
            <staff-banner
                :items="bannerItems"
                :config="bannerConfig"
                :fallback="fallbackImage"
                :fallback-kind="fallbackKind"
                :name="staff.name"
                @expand="onBannerExpand"
                @visual="bannerHasWideVisual = $event"
            />

            <!-- 身份卡：从图片上移到暖白卡，400rpx 小图下也能读清 -->
            <view class="idcard">
                <view class="idcard__top">
                    <text class="idcard__eyebrow">{{ eyebrow }}</text>
                    <!-- 收藏 + 分享：一起放身份卡首行，导航栏不再承担动作 -->
                    <view v-if="!pickMode" class="idcard__actions">
                        <view
                            hover-class="is-pressed"
                            class="idcard__act"
                            :class="{ 'is-on': isFavorite }"
                            aria-role="button"
                            :aria-label="isFavorite ? '取消收藏' : '收藏这位主持人'"
                            @tap="toggleFavorite"
                        >
                            <tn-icon
                                :name="isFavorite ? 'like-fill' : 'like'"
                                :size="26"
                                class="idcard__act-ic"
                            />
                            <text class="idcard__act-text">{{ isFavorite ? '已收藏' : '收藏' }}</text>
                        </view>
                        <!-- open-type=share 的 button 必须自己抹掉默认样式 -->
                        <button class="idcard__act" open-type="share" aria-label="分享这位主持人">
                            <tn-icon name="share" :size="26" class="idcard__act-ic" />
                            <text class="idcard__act-text">分享</text>
                        </button>
                    </view>
                </view>
                <view class="idcard__name-row">
                    <text class="idcard__name ellipsis">{{ staff.name || '主持人' }}</text>
                    <!-- 推荐标：与列表卡同款（品牌金底 + 星形 + 推荐） -->
                    <view v-if="isRecommend" class="idcard__rec">
                        <tn-icon name="star-fill" :size="16" class="idcard__rec-ic" />
                        <text class="idcard__rec-text">推荐</text>
                    </view>
                    <!-- 无 rank 不显示职级徽章（03 §A 组降级规则） -->
                    <text v-if="rankName" class="idcard__badge">{{ rankName }}</text>
                </view>
                <view v-if="heroMeta.length" class="idcard__meta">
                    <template v-for="(m, i) in heroMeta" :key="i">
                        <text v-if="i > 0" class="idcard__meta-sep" aria-hidden="true" />
                        <text class="idcard__meta-item">
                            <tn-icon
                                v-if="m.icon"
                                :name="m.icon"
                                :size="20"
                                class="idcard__meta-icon"
                            />
                            {{ m.text }}
                        </text>
                    </template>
                </view>
                <view
                    v-if="dateLabel"
                    class="idcard__avail"
                    :class="{ 'is-link': !pickMode }"
                    :hover-class="pickMode ? '' : 'is-pressed'"
                    :aria-role="pickMode ? undefined : 'button'"
                    :aria-label="pickMode ? undefined : '修改档期与地区'"
                    @tap="openScheduleSheet"
                >
                    <view class="idcard__avail-top">
                        <view class="idcard__dot" :class="{ 'is-full': !scheduleAvailable && !hasActiveOrder }" />
                        <text class="idcard__avail-date">{{ dateLabel }}</text>
                        <text class="idcard__avail-sep" aria-hidden="true">·</text>
                        <text class="idcard__avail-region ellipsis">{{
                            regionReady ? regionCity : '选择地区'
                        }}</text>
                        <text v-if="!pickMode" class="idcard__avail-edit">更换</text>
                        <tn-icon
                            v-if="!pickMode"
                            name="right"
                            :size="20"
                            class="idcard__avail-arrow"
                        />
                    </view>
                    <text class="idcard__avail-status">{{ availabilityText }}</text>
                </view>
            </view>

            <!-- 三页签吸顶：sticky-holder 用页面级滚动 + onPageScroll 替代 position:sticky -->
            <sticky-holder ref="stickyRef" :scroll-top="scrollTop" :top="navTotalPx">
                <view class="dtabs">
                    <view
                        v-for="t in TABS"
                        :key="t.key"
                        class="dtab"
                        :class="{ 'is-on': tab === t.key }"
                        hover-class="is-pressed"
                        aria-role="tab"
                        :aria-selected="tab === t.key"
                        @tap="switchTab(t.key)"
                    >
                        <text class="dtab__text">{{ t.label }}</text>
                        <text v-if="tabCount(t.key)" class="dtab__count">{{
                            tabCount(t.key)
                        }}</text>
                    </view>
                </view>
            </sticky-holder>

            <!-- 页签一：详情 -->
            <view v-if="tab === 'about'" class="panel panel--about">
                <view class="panel__inner">
                    <template v-if="introParagraphs.length">
                        <text v-for="(p, i) in introParagraphs" :key="i" class="intro">{{ p }}</text>
                    </template>
                    <base-empty
                        v-else
                        status="empty"
                        empty-text="还没有填写介绍"
                        desc="可以先看作品与真实评价"
                    />

                    <!-- 资历一行：只渲染服务端真实返回的字段，缺了就少一格 -->
                    <view v-if="facts.length" class="facts">
                        <template v-for="(f, i) in facts" :key="f.key">
                            <text v-if="i > 0" class="facts__sep" aria-hidden="true" />
                            <view class="facts__item">
                                <text class="facts__num">{{ f.value }}</text>
                                <text class="facts__unit">{{ f.unit }}</text>
                            </view>
                        </template>
                    </view>

                    <!-- 规格表 2×2 -->
                    <view v-if="specs.length" class="specs">
                        <view v-for="s in specs" :key="s.key" class="spec">
                            <text class="spec__k">{{ s.label }}</text>
                            <text class="spec__v">{{ s.value }}</text>
                        </view>
                    </view>

                    <!-- 服务说明：取后台人员资料里的 service_desc（不再从套餐 desc 拼条目） -->
                    <view v-if="serviceParagraphs.length" class="inc">
                        <text class="inc__title">服务说明</text>
                        <text v-for="(p, i) in serviceParagraphs" :key="i" class="inc__para">{{
                            p
                        }}</text>
                    </view>
                </view>

                <!-- 长图详情：后台装修内容，块顺序与图片无缝规则由组件负责 -->
                <long-detail
                    v-if="longDetail"
                    :content="longDetail"
                    @works="switchTab('works')"
                />
            </view>

            <!-- 页签二：作品 -->
            <view v-else-if="tab === 'works'" class="panel">
                <view v-if="workCategories.length" class="wfilter">
                    <chip-group
                        v-model="workCategory"
                        :items="workCategories"
                        size="md"
                        @change="onWorkFilter"
                    />
                </view>

                <view v-if="filteredWorks.length" class="wgrid">
                    <view v-for="w in filteredWorks" :key="w.id" class="wtile">
                        <image
                            v-if="w.cover"
                            class="wtile__img"
                            :src="imageUrl(w.cover)"
                            mode="aspectFill"
                        />
                        <view v-else class="wtile__blank" />
                        <view class="wtile__scrim" aria-hidden="true" />
                        <text v-if="w.caption" class="wtile__cap ellipsis">{{ w.caption }}</text>
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
                    :status="worksLoadStatus"
                    @retry="loadWorks(false)"
                />
            </view>

            <!-- 页签三：评价 -->
            <view v-else class="panel">
                <view v-if="reviewCount" class="rsum">
                    <text class="rsum__num">{{ scoreText }}</text>
                    <view class="rsum__mid">
                        <view class="stars" :aria-label="`评分 ${scoreText} 分，满分 5 分`">
                            <tn-icon
                                v-for="n in 5"
                                :key="n"
                                name="star-fill"
                                :size="22"
                                :class="['stars__i', { 'is-off': n > starFilled }]"
                            />
                        </view>
                        <text class="rsum__sub">{{ reviewSubText }}</text>
                    </view>
                    <view v-if="reviewTags.length" class="rtags">
                        <text v-for="t in reviewTags" :key="t" class="rtags__i">{{ t }}</text>
                    </view>
                </view>

                <view v-if="reviews.length" class="reviews">
                    <view v-for="r in reviews" :key="r.id" class="review">
                        <view class="review__head">
                            <text class="review__avatar">{{ firstChar(r.user?.nickname) }}</text>
                            <text class="review__name ellipsis">{{
                                r.user?.nickname || '匿名用户'
                            }}</text>
                            <text v-if="r.create_time" class="review__date">{{
                                formatDay(r.create_time)
                            }}</text>
                            <view class="stars stars--sm" :aria-label="`${r.score} 分`">
                                <tn-icon
                                    v-for="n in 5"
                                    :key="n"
                                    name="star-fill"
                                    :size="18"
                                    :class="['stars__i', { 'is-off': n > Number(r.score || 0) }]"
                                />
                            </view>
                        </view>
                        <text v-if="r.content" class="review__text">{{ r.content }}</text>
                    </view>
                    <load-more :status="reviewLoadStatus" @retry="loadReviews(false)" />
                </view>
                <base-empty
                    v-else
                    status="empty"
                    empty-text="还没有评价"
                    desc="这位主持人刚加入，先看看作品"
                />
            </view>
        </template>

        <!-- 吸底操作栏：「选人模式」只做对比与选择，其余场景是服务起价 + 咨询 / 预约 -->
        <template #bottom>
            <bottom-bar v-if="!loading && !error">
                <view class="abp">
                    <template v-if="pickMode">
                        <text class="abp__label">{{ pickLabel }}服务价</text>
                        <price v-if="pickPrice != null" size="md" :content="pickPrice" />
                        <text v-else class="abp__na">面议</text>
                        <text class="abp__note">{{ pickHint }}</text>
                    </template>
                    <template v-else>
                        <text class="abp__label">服务起价</text>
                        <price v-if="startPrice != null" size="md" :content="startPrice" />
                        <text v-else class="abp__na">面议</text>
                        <text v-if="hasActiveOrder" class="abp__note">该日已有你的预约</text>
                        <text v-else-if="!scheduleAvailable" class="abp__note">{{ fullNote }}</text>
                    </template>
                </view>
                <template #actions>
                    <view class="abtns">
                        <template v-if="pickMode">
                            <base-button
                                text="返回对比"
                                variant="ghost"
                                size="md"
                                :block="false"
                                @click="backFromPick"
                            />
                            <base-button
                                text="选择 TA"
                                variant="ink"
                                size="md"
                                :block="false"
                                :disabled="pickPrice == null"
                                @click="confirmPick"
                            />
                        </template>
                        <template v-else>
                            <base-button
                                v-if="hasActiveOrder || scheduleAvailable"
                                text="先咨询"
                                variant="ghost"
                                size="md"
                                :block="false"
                                @click="goConsult"
                            />
                            <base-button
                                v-else
                                text="换档期"
                                variant="ghost"
                                size="md"
                                :block="false"
                                @click="openScheduleSheet"
                            />
                            <base-button
                                :text="hasActiveOrder ? '查看订单' : scheduleAvailable ? '预约档期' : '去候补'"
                                variant="ink"
                                size="md"
                                :block="false"
                                @click="hasActiveOrder ? viewMyOrder() : handlePrimaryAction()"
                            />
                        </template>
                    </view>
                </template>
            </bottom-bar>
        </template>
    </page-shell>

    <!--
        档期与地区切换：直接进入详情页时，用户必须能改这两个条件。
        日期只决定「哪天可约」，地区决定「按哪算价、有哪些可约人员」，两者一起提交才刷新。
    -->
    <tn-popup
        v-model="schedVisible"
        open-direction="bottom"
        :safe-area-inset-bottom="true"
        :radius="28"
    >
        <view class="spop">
            <view class="spop__head">
                <text class="spop__title">档期与地区</text>
                <view
                    class="spop__close"
                    hover-class="is-pressed"
                    aria-label="关闭"
                    @tap="schedVisible = false"
                >
                    <tn-icon name="close" :size="32" />
                </view>
            </view>

            <scroll-view scroll-y class="spop__scroll">
                <view class="scal">
                    <view class="scal__nav">
                        <view
                            class="scal__btn"
                            :class="{ 'is-disabled': !canShiftMonth(-1) }"
                            hover-class="is-pressed"
                            aria-role="button"
                            aria-label="上个月"
                            @tap="shiftMonth(-1)"
                        >
                            <tn-icon name="left" :size="28" />
                        </view>
                        <text class="scal__title">{{ pickerMonthLabel }}</text>
                        <view
                            class="scal__btn"
                            :class="{ 'is-disabled': !canShiftMonth(1) }"
                            hover-class="is-pressed"
                            aria-role="button"
                            aria-label="下个月"
                            @tap="shiftMonth(1)"
                        >
                            <tn-icon name="right" :size="28" />
                        </view>
                    </view>

                    <view class="scal__week">
                        <text v-for="w in WEEK_LABELS" :key="w" class="scal__week-i">{{
                            w
                        }}</text>
                    </view>

                    <view class="scal__grid">
                        <view
                            v-for="cell in calendarCells"
                            :key="cell.key"
                            class="scal__cell"
                            :class="{
                                'is-blank': !cell.day,
                                'is-disabled': cell.disabled,
                                'is-on': cell.selected,
                                'is-today': cell.today
                            }"
                            @tap="pickDay(cell)"
                        >
                            <text v-if="cell.day" class="scal__day">{{ cell.day }}</text>
                        </view>
                    </view>
                </view>

                <view class="spop__region">
                    <region-columns
                        :key="pickerKey"
                        v-model="pickerRegion"
                        height="288rpx"
                        title="服务地区"
                    />
                </view>
            </scroll-view>

            <view class="spop__foot">
                <text v-if="schedTip" class="spop__tip">{{ schedTip }}</text>
                <base-button
                    :text="schedConfirmText"
                    variant="ink"
                    size="md"
                    :disabled="!schedConfirmable"
                    @click="confirmSchedule"
                />
            </view>
        </view>
    </tn-popup>

    <app-toast />
</template>

<script setup lang="ts">
/**
 * 人员详情（05 的迭代版）。
 *
 * 结构：可配置轮播 + 上浮身份卡 + 三页签（详情 / 作品 / 评价）+ 吸底栏；
 * 详情页签末尾接后台 long_detail 装修内容。
 * 数据：优先 v2 聚合 getStaffDetailV2（1 次请求），失败降级 v1（detail + works + reviews 并发）。
 *
 * 降级口径（03 §A 组）：
 *  - 无 rank → 不渲染职级徽章；
 *  - 无 schedule_remaining → 身份卡只显示「可约 / 已满 / 待确认」，不硬造「仅 1 档」；
 *  - 作品条目只有 title/cover（无年份/场景/人数）→ 图墙只标标题，筛选条在有分类字段时才出现；
 *  - 评价推荐率、好评率只用 stats.good / stats.total 真实比值，算不出就不显示。
 *
 * 吸顶实现：小程序里 scroll-view 内 position:sticky 不生效，本页用「页面级滚动 + onPageScroll
 * 把 scrollTop 传给 sticky-holder」，由它渲染等高空位（不跳动）。详见 sticky-holder 注释。
 */
import { computed, nextTick, reactive, ref } from 'vue'
import { onLoad, onPageScroll, onShareAppMessage, onShow } from '@dcloudio/uni-app'
import {
    getStaffDetail,
    getStaffDetailV2,
    getStaffActiveOrder,
    getStaffWorks,
    getStaffPackages,
    getScheduleRule,
    toggleStaffFavorite
} from '@/api/catalog'
import { getStaffReviews, getReviewTags } from '@/api/content'
import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'
import { getNavBarHeight } from '@/platform/device'
import { navigate, resolveLink } from '@/utils/link'
import cache from '@/utils/cache'
import { toast } from '@/composables/useToast'
import { resolveBookingContext, saveBookingContext } from '@/utils/booking-context'
import {
    EMPTY_REGION,
    hasRegionContext,
    regionCodeParams,
    regionParams,
    type RegionContext
} from '@/utils/region'
import { report } from '@/platform/report'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { formatDate, parseDateValue } from '@shared/utils/format'
import { buildShare } from '@/utils/share'
import StaffBanner from './components/staff-banner.vue'
import LongDetail from './components/long-detail.vue'
import {
    DEFAULT_BANNER_CONFIG,
    bannerStageHeight,
    normalizeBannerConfig,
    type BannerConfig,
    type BannerSlide
} from './banner'

type TabKey = 'about' | 'works' | 'reviews'

const appStore = useAppStore()
const userStore = useUserStore()

/** 导航总高 = 状态栏 + 44px 内容行（吸顶停留位置）；口径与 nav-bar 占位块完全一致 */
const navTotalPx = getNavBarHeight()

const TABS: Array<{ key: TabKey; label: string }> = [
    { key: 'about', label: '详情' },
    { key: 'works', label: '作品' },
    { key: 'reviews', label: '评价' }
]

const staffId = ref(0)
const date = ref('')
/**
 * 选人模式（预约页「婚礼管家 / 婚礼督导」步骤点「详情」进来）：
 * 只做资料与价格对比，底栏 CTA 换成「选择 TA」，不发起预约。
 */
const pickRole = ref('')
const pickPackageId = ref(0)
/** 列表页带来的服务地区：详情价格、预约报价、下单结算共用这一份上下文 */
const region = ref<RegionContext>({ ...EMPTY_REGION })
/** 档期与地区切换弹层（直接进入详情页时，用户需要能改日期和地区） */
const schedVisible = ref(false)
const pickerDate = ref('')
const pickerRegion = ref<RegionContext | null>(null)
const pickerKey = ref(0)
const pickerMonth = reactive({ year: 0, month: 0 })
const minSelectableDate = ref('')
const maxSelectableDate = ref('')
const loading = ref(true)
const error = ref(false)
const tab = ref<TabKey>('about')
const scrollTop = ref(0)

/** 首次详情加载完成标记：onShow 只在返回页面时做轻量刷新 */
let firstLoadDone = false
let refreshingOrder = false

const rawError = ref<unknown>(null)
const stickyRef = ref<any>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))

const staff = ref<any>({})
const banners = ref<any[]>([])
const bannerConfig = ref<BannerConfig>({ ...DEFAULT_BANNER_CONFIG })
const bannerExpanded = ref(false)
const bannerHasWideVisual = ref(true)
const longDetail = ref('')
const packages = ref<any[]>([])
const works = ref<any[]>([])
const reviews = ref<any[]>([])
const reviewStats = ref<any>({})
const reviewTags = ref<string[]>([])

const worksPage = ref(1)
const worksHasMore = ref(false)
const worksLoading = ref(false)
const reviewsPage = ref(1)
const reviewsHasMore = ref(false)
const reviewsLoading = ref(false)
const workCategory = ref<string | number>('all')

const scheduleAvailable = computed(() => staff.value.schedule_available !== false)

/**
 * 当前用户在该人员「当前日期」的有效订单。
 *
 * 后端已按「本人 + 该人员 + 当前服务日期 + 有效状态」过滤：
 * 待确认/待支付/待服务/服务中/已暂停/退款中；已完成、已评价、已取消、
 * 已退款、用户已删除不返回。
 */
const myOrder = computed(() => staff.value.my_order || null)
const hasActiveOrder = computed(() => Number(myOrder.value?.order_id || 0) > 0)

/** 收藏态：v2 下发布尔 0/1，v1 下发 true/false，两种都收 */
const isFavorite = computed(
    () => Number(staff.value.is_favorite) === 1 || staff.value.is_favorite === true
)

/** 推荐态：与首页/列表推荐标同一数据源，v2 已补该字段 */
const isRecommend = computed(() => Number(staff.value.is_recommend) === 1)

/* ---------------- 派生展示（全部来自真实字段，缺则隐藏） ---------------- */

const fallbackImage = computed(() => imageUrl(staff.value.cover || staff.value.avatar || ''))

/** cover 是横图可全宽铺；只有 avatar 时用居中方图，避免被 aspectFill 裁成胸口 */
const fallbackKind = computed<'cover' | 'avatar'>(() =>
    staff.value.cover ? 'cover' : staff.value.avatar ? 'avatar' : 'cover'
)

/** 轮播条目：只做字段归一化 + URL 补全，不在这里过滤类型（组件负责失败剔除） */
const bannerItems = computed<BannerSlide[]>(() => {
    const list = Array.isArray(banners.value) ? banners.value : []
    return list
        .map((item: any, index: number) => {
            const type = Number(item?.type) === 2 ? 'video' : 'image'
            return {
                key: String(item?.id ?? index),
                type,
                src: imageUrl(item?.file_url || item?.fileUrl || ''),
                poster: imageUrl(item?.cover_url || item?.coverUrl || ''),
                autoplay: Number(item?.is_autoplay) === 1
            } as BannerSlide
        })
        .filter((item) =>
            item.type === 'video' ? Boolean(item.src || item.poster) : Boolean(item.src)
        )
})

const navTone = computed<'dark' | 'light'>(() =>
    loading.value || error.value || !bannerHasWideVisual.value ? 'dark' : 'light'
)

const bannerStage = computed(() => bannerStageHeight(bannerConfig.value, bannerExpanded.value))

/** 滚过舞台后再切实底；40px 是原来的最小阈值，避免刚滑就变白 */
const navSolidThreshold = computed(() => Math.max(40, bannerStage.value - navTotalPx))

/** 首屏骨架：按默认小图高度画，数据回来后不会大幅跳动 */
const skeletonHeight = bannerStageHeight(DEFAULT_BANNER_CONFIG)

const rankName = computed(() => staff.value.rank_name || staff.value.rank || '')

/**
 * eyebrow：角色 · 城市。
 * role_name 是职级（主持人可能有「首席」等），没有就退回服务分类名 ——
 * 管家/督导详情页从预约流程进来时，这里要显示「管家」「督导」而不是「主持人」。
 */
const eyebrow = computed(() => {
    const role = String(staff.value.role_name || staff.value.category_name || '主持人')
    const city = staff.value.city || staff.value.city_name || ''
    return city ? `${role} · ${city}` : role
})

const dateLabel = computed(() => {
    const hit = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(date.value || ''))
    if (!hit) return ''
    const year = Number(hit[1])
    const month = Number(hit[2])
    const day = Number(hit[3])
    const dt = new Date(year, month - 1, day)
    if (Number.isNaN(dt.getTime())) return ''
    return `${month}月${day}日 周${'日一二三四五六'[dt.getDay()]}`
})

const availabilityText = computed(() => {
    if (hasActiveOrder.value) return '已为你预约'
    if (scheduleAvailable.value) return '可约'
    const message = String(staff.value.schedule_message || '').trim()
    return message || '当日已满'
})

/** 身份卡可约行：日期 + 城市 + 状态，三者一起给，用户才知道按哪个条件在查 */
const regionCity = computed(
    () => region.value.city_name || staff.value.city || staff.value.city_name || ''
)
const regionReady = computed(() => hasRegionContext(region.value))

const heroMeta = computed(() => {
    const list: Array<{ text: string; icon?: string }> = []
    const rating = Number(staff.value.rating || 0)
    if (rating > 0) list.push({ text: `${Number(rating.toFixed(1))}`, icon: 'star-fill' })
    const count = Number(staff.value.order_count || 0)
    if (count > 0) list.push({ text: `${count} 场` })
    const years = Number(staff.value.experience_years || 0)
    if (years > 0) list.push({ text: `${years} 年经验` })
    return list
})

const introParagraphs = computed(() => {
    const raw = String(staff.value.intro || staff.value.profile || '').trim()
    if (!raw) return []
    return raw
        .split(/\n+/)
        .map((s) => s.trim())
        .filter(Boolean)
})

const facts = computed(() => {
    const list: Array<{ key: string; value: string; unit: string }> = []
    const count = Number(staff.value.order_count || 0)
    if (count > 0) list.push({ key: 'count', value: String(count), unit: '场婚礼' })
    const rating = Number(staff.value.rating || 0)
    if (rating > 0) list.push({ key: 'rating', value: rating.toFixed(1), unit: '分' })
    const good = Number(reviewStats.value.good || 0)
    const total = Number(reviewStats.value.total || 0)
    if (good > 0 && total > 0 && good <= total) {
        list.push({ key: 'good', value: `${Math.round((good / total) * 100)}%`, unit: '好评' })
    }
    // 从业年限只在姓名下方（heroMeta）出现一次，这里不再重复
    return list
})

const specs = computed(() => {
    const list: Array<{ key: string; label: string; value: string }> = []
    const tags = Array.isArray(staff.value.tags) ? staff.value.tags.filter(Boolean) : []
    if (tags.length) list.push({ key: 'style', label: '擅长风格', value: tags.join(' / ') })
    const lang = staff.value.languages || staff.value.language || ''
    if (lang)
        list.push({
            key: 'lang',
            label: '语言',
            value: Array.isArray(lang) ? lang.join(' · ') : String(lang)
        })
    const city = staff.value.service_cities || staff.value.city || ''
    if (city) {
        list.push({
            key: 'city',
            label: '服务城市',
            value: Array.isArray(city) ? city.join(' · ') : String(city)
        })
    }
    // 从业年限同 heroMeta，规格表不再重复
    return list
})

/** 服务说明 = 后台人员资料 service_desc，按换行分段（未配置则整块隐藏） */
const serviceParagraphs = computed(() => {
    const raw = String(staff.value.service_desc || '').trim()
    if (!raw) return []
    return raw
        .split(/\n+/)
        .map((s) => s.trim())
        .filter(Boolean)
})

const startPrice = computed(() => {
    if (staff.value.price !== undefined && staff.value.price !== null && staff.value.price !== '') {
        return staff.value.price as number | string
    }
    const p = packages.value[0]
    const v = p?.price ?? p?.package?.price
    return v === undefined || v === null || v === '' ? null : (v as number | string)
})

const pickMode = computed(() => !!pickRole.value)

const pickLabel = computed(() => {
    if (pickRole.value === 'butler') return '婚礼管家'
    if (pickRole.value === 'director') return '婚礼督导'
    return ''
})

/** 预约页传下来的角色套餐（不同人员价格不同，以该套餐价为准） */
const pickPackage = computed(
    () =>
        packages.value.find(
            (p) => Number(p?.package_id || p?.id) === Number(pickPackageId.value)
        ) || null
)

const pickPrice = computed(() => {
    const raw = pickPackage.value
        ? (pickPackage.value.price ?? pickPackage.value?.package?.price)
        : startPrice.value
    return raw === undefined || raw === null || raw === '' ? null : (raw as number | string)
})

const pickHint = computed(() => {
    if (pickPrice.value == null) return '该人员暂未配置可售套餐'
    const name = pickPackage.value?.package?.name || pickPackage.value?.name
    return name ? String(name) : '按所选人员服务计价'
})

/** 返回预约页继续对比其他人员（不写选择结果） */
function backFromPick() {
    const pages = getCurrentPages()
    if (pages.length > 1) uni.navigateBack()
    else uni.switchTab({ url: '/pages/index/index' })
}

/** 选定该人员：结果写入缓存，由预约页 onShow 读取（跨端一致，不依赖 eventChannel） */
function confirmPick() {
    const pkg = pickPackage.value
    if (!pkg) {
        toast.info('该人员暂未配置可预约套餐')
        return
    }
    cache.set('booking_role_pick', {
        role_key: pickRole.value,
        staff_id: staffId.value,
        package_id: Number(pkg.package_id || pkg.id || 0),
        name: String(staff.value.name || ''),
        avatar: String(staff.value.avatar || ''),
        price: Number(pkg.price ?? pkg?.package?.price ?? 0),
        ts: Date.now()
    })
    backFromPick()
}

const fullNote = computed(() => {
    // 底栏只放一句短状态；后端的长原因（单日接单上限等）留给身份卡，避免挤掉按钮
    return '该日不可约'
})

const reviewCount = computed(() => Number(reviewStats.value.total || reviews.value.length || 0))
const scoreText = computed(() => {
    const s = Number(reviewStats.value.score || staff.value.rating || 0)
    return s > 0 ? s.toFixed(1) : '—'
})
const starFilled = computed(() => {
    const s = Number(reviewStats.value.score || staff.value.rating || 0)
    return s > 0 ? Math.round(s) : 0
})
const reviewSubText = computed(() => {
    const total = reviewCount.value
    const good = Number(reviewStats.value.good || 0)
    if (total > 0 && good > 0 && good <= total) {
        return `${total} 条 · ${Math.round((good / total) * 100)}% 推荐`
    }
    return total > 0 ? `${total} 条评价` : ''
})

function tabCount(key: TabKey) {
    if (key === 'works') {
        const n = Number(staff.value.works_count || works.value.length || 0)
        return n > 0 ? n : 0
    }
    if (key === 'reviews') return reviewCount.value
    return 0
}

/* ---------------- 作品 ---------------- */

const workCategories = computed(() => {
    const set = new Set<string>()
    for (const w of works.value) {
        const c = w.category_name || w.tag || w.style
        if (c) set.add(String(c))
    }
    if (!set.size) return []
    return [
        { label: '全部', value: 'all' },
        ...Array.from(set).map((v) => ({ label: v, value: v }))
    ]
})

const filteredWorks = computed(() => {
    if (workCategory.value === 'all') return works.value
    return works.value.filter(
        (w) => String(w.category_name || w.tag || w.style) === String(workCategory.value)
    )
})

const worksLoadStatus = computed<'idle' | 'loading' | 'nomore' | 'error'>(() => {
    if (worksLoading.value) return 'loading'
    return worksHasMore.value ? 'idle' : 'nomore'
})

const reviewLoadStatus = computed<'idle' | 'loading' | 'nomore' | 'error'>(() => {
    if (reviewsLoading.value) return 'loading'
    return reviewsHasMore.value ? 'idle' : 'nomore'
})

function onWorkFilter() {
    // 筛选不重新请求：本地按分类字段过滤，避免空分类反复打接口
    if (workCategory.value !== 'all' && worksHasMore.value) loadWorks(false)
}

/* ---------------- 工具 ---------------- */

function imageUrl(url?: string) {
    if (!url) return ''
    if (/^https?:/.test(url)) return url
    const domain = (appStore.config && appStore.config.domain) || ''
    return domain ? `${domain}${url}` : url
}

function firstChar(name?: string) {
    const s = String(name || '').trim()
    return s ? Array.from(s)[0] : '匿'
}

function formatDay(v: any) {
    return formatDate(v, 'YYYY.MM.DD', '')
}

function goBack() {
    const pages = getCurrentPages()
    if (pages.length > 1) uni.navigateBack()
    else uni.switchTab({ url: '/pages/index/index' })
}

function switchTab(key: TabKey) {
    if (tab.value === key) return
    tab.value = key
    // 等新面板渲染完再滚：从短面板切到长面板时，旧 DOM 高度不够，会被 pageScrollTo 判为不可滚
    nextTick(() => scrollTabsIntoView())
    if (key === 'works' && !works.value.length) loadWorks(true)
    if (key === 'reviews' && !reviews.value.length) loadReviews(true)
}

/** 页签切换后停在页签位置：由 sticky-holder 提供它自己的实际触发点 */
function scrollTabsIntoView() {
    stickyRef.value?.scrollIntoView?.()
}

function onBannerExpand(expanded: boolean) {
    bannerExpanded.value = expanded
    // 展开动画结束后重新量一次页签位置，后续切页签才能停在正确位置
    setTimeout(() => stickyRef.value?.remeasure?.(), 300)
}

function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        load()
        return
    }
    if (kind === 'reselect' || kind === 'back') {
        goBack()
        return
    }
    if (kind === 'login') {
        navigate({ path: '/pages/login/login' })
    }
}

/** 收藏/取消收藏：与列表页同一接口（后端读 id，不是 staff_id） */
async function toggleFavorite() {
    if (!staffId.value) return
    if (!userStore.isLogin) {
        navigate({ path: '/pages/login/login' })
        return
    }
    try {
        const res: any = await toggleStaffFavorite({ id: staffId.value })
        const next =
            res && typeof res.is_favorite !== 'undefined'
                ? Number(res.is_favorite) === 1
                : !isFavorite.value
        staff.value = { ...staff.value, is_favorite: next ? 1 : 0 }
    } catch {
        // 请求层已提示，状态保持原样
    }
}

function goConsult() {
    navigate({
        path: '/pages/customer-service/customer-service',
        query: { scene: 'staff', staff_id: staffId.value }
    })
}

/* ---------------- 档期与地区切换 ---------------- */

const WEEK_LABELS = ['日', '一', '二', '三', '四', '五', '六']

function pad2(n: number) {
    return String(n).padStart(2, '0')
}

function dateKeyOf(year: number, month: number, day: number) {
    return `${year}-${pad2(month)}-${pad2(day)}`
}

/** 直接进入详情页（无 date 参数）时的默认档期：第二天 */
function tomorrowKey() {
    const d = new Date()
    d.setDate(d.getDate() + 1)
    return dateKeyOf(d.getFullYear(), d.getMonth() + 1, d.getDate())
}

const todayKey = computed(() => {
    const d = new Date()
    return dateKeyOf(d.getFullYear(), d.getMonth() + 1, d.getDate())
})

const pickerMonthLabel = computed(() => `${pickerMonth.year} 年 ${pickerMonth.month} 月`)

/**
 * 自绘月历：与 booking 的日期弹层同一口径（6 行固定高度，切月不跳）。
 * 早于规则最早可约日 / 晚于最晚可约日一律禁用。
 */
const calendarCells = computed(() => {
    const { year, month } = pickerMonth
    if (!year || !month) return [] as any[]
    const startWeekday = new Date(year, month - 1, 1).getDay()
    const daysInMonth = new Date(year, month, 0).getDate()
    const min = minSelectableDate.value || todayKey.value
    const max = maxSelectableDate.value

    const cells: any[] = []
    for (let i = 0; i < startWeekday; i++) cells.push({ key: `blank-${i}`, day: 0 })
    for (let d = 1; d <= daysInMonth; d++) {
        const key = dateKeyOf(year, month, d)
        cells.push({
            key,
            day: d,
            date: key,
            disabled: (!!min && key < min) || (!!max && key > max),
            selected: key === pickerDate.value,
            today: key === todayKey.value
        })
    }
    while (cells.length % 7 !== 0) cells.push({ key: `tail-${cells.length}`, day: 0 })
    return cells
})

function canShiftMonth(delta: number) {
    if (!pickerMonth.year) return false
    const target = new Date(pickerMonth.year, pickerMonth.month - 1 + delta, 1)
    const key = dateKeyOf(target.getFullYear(), target.getMonth() + 1, 1).slice(0, 7)
    const min = minSelectableDate.value
    const max = maxSelectableDate.value
    if (delta < 0 && min && key < min.slice(0, 7)) return false
    if (delta > 0 && max && key > max.slice(0, 7)) return false
    return true
}

function shiftMonth(delta: number) {
    if (!canShiftMonth(delta)) return
    const target = new Date(pickerMonth.year, pickerMonth.month - 1 + delta, 1)
    pickerMonth.year = target.getFullYear()
    pickerMonth.month = target.getMonth() + 1
}

function pickDay(cell: any) {
    if (!cell?.day || cell.disabled) return
    pickerDate.value = String(cell.date || '')
}

const schedConfirmable = computed(() => !!pickerDate.value && !!pickerRegion.value?.district_code)

const schedConfirmText = computed(() => {
    if (!pickerDate.value) return '请选择日期'
    if (!pickerRegion.value) return '请选择地区'
    return '查看该档期'
})

const schedTip = computed(() => {
    if (!pickerDate.value) return '请选择服务日期'
    if (!pickerRegion.value) return '请选择省 / 市 / 区，价格与可约人员按区县计算'
    return ''
})

async function loadScheduleRule() {
    try {
        const rule: any = await getScheduleRule()
        minSelectableDate.value = String(rule?.min_selectable_date || '')
        maxSelectableDate.value = String(rule?.max_selectable_date || '')
    } catch {
        // 规则失败不阻断：日历按「今天起」兜底
    }
}

function openScheduleSheet() {
    if (pickMode.value) return
    pickerDate.value = date.value || tomorrowKey()
    if (minSelectableDate.value && pickerDate.value < minSelectableDate.value) {
        pickerDate.value = minSelectableDate.value
    }
    if (maxSelectableDate.value && pickerDate.value > maxSelectableDate.value) {
        pickerDate.value = maxSelectableDate.value
    }
    pickerRegion.value = regionReady.value ? { ...region.value } : null
    const base = parseDateValue(pickerDate.value) || new Date()
    pickerMonth.year = base.getFullYear()
    pickerMonth.month = base.getMonth() + 1
    schedVisible.value = true
    pickerKey.value += 1
    if (!minSelectableDate.value) loadScheduleRule()
}

function confirmSchedule() {
    if (!schedConfirmable.value) return
    const nextRegion = pickerRegion.value as RegionContext
    date.value = pickerDate.value
    region.value = { ...nextRegion }
    saveBookingContext({ date: date.value, region: region.value })
    schedVisible.value = false
    // 换档期/地区后必须按新条件重新拉价格与可约状态，不能沿用旧结果
    load()
}

/** 主操作：可约 → 06 选套餐；不可约 → 19 候补申请。地区必须一起带下去，否则报价退回全国价 */
function handlePrimaryAction() {
    // 没有区县就无法算价：先补地区，不带着空地区跳走
    if (!regionReady.value) {
        openScheduleSheet()
        toast.info('请选择服务地区后继续')
        return
    }
    const pkg = packages.value[0]
    const shared = {
        staff_id: staffId.value,
        package_id: pkg ? pkg.package_id || pkg.id : '',
        date: date.value,
        ...regionParams(region.value)
    }
    if (scheduleAvailable.value) {
        navigate({ path: '/packages/pkg-catalog/pages/booking/booking', query: shared })
        return
    }
    navigate({ path: '/packages/pkg-order/pages/waitlist/apply', query: shared })
}

/** 已有有效订单：优先直达订单详情，缺少订单号时回订单列表 */
function viewMyOrder() {
    const orderId = Number(myOrder.value?.order_id || 0)
    if (orderId > 0) {
        navigate({
            path: '/packages/pkg-order/pages/detail/detail',
            query: { id: orderId }
        })
        return
    }
    navigate({ path: '/pages/order/order' })
}

/* ---------------- 数据 ---------------- */

async function load() {
    if (!staffId.value) {
        rawError.value = new Error('missing-staff-id')
        error.value = true
        loading.value = false
        return
    }
    loading.value = true
    error.value = false
    try {
        // 优先 v2 聚合（1 次请求）；地区编码必须带上，否则价格退回全国价
        try {
            const v2: any = await getStaffDetailV2(staffId.value, {
                date: date.value || undefined,
                ...regionCodeParams(region.value)
            })
            applyV2(v2)
            loading.value = false
            return
        } catch (e) {
            // v2 未上线/异常：降级 v1（3 个接口并发），不在此处报错
        }

        const detail: any = await getStaffDetail({
            id: staffId.value,
            date: date.value || undefined,
            ...regionParams(region.value)
        })
        if (!detail || !detail.id) throw new Error('empty')
        staff.value = detail
        banners.value = Array.isArray(detail.banners) ? detail.banners : []
        longDetail.value = String(detail.long_detail || '')
        applyBanner(detail)
        packages.value = Array.isArray(detail.packages) ? detail.packages : []

        const [worksRes, reviewRes, pkgRes] = await Promise.allSettled([
            getStaffWorks({ staff_id: staffId.value, page: 1, page_size: 8 }),
            getStaffReviews({ staff_id: staffId.value, page: 1, page_size: 5 }),
            getStaffPackages({ staff_id: staffId.value, ...regionParams(region.value) })
        ])
        if (worksRes.status === 'fulfilled') applyWorks(worksRes.value as any, true)
        if (reviewRes.status === 'fulfilled') applyReviews(reviewRes.value as any, true)
        if (pkgRes.status === 'fulfilled' && !packages.value.length) {
            const raw: any = pkgRes.value
            packages.value = Array.isArray(raw) ? raw : raw?.lists || raw?.data || []
        }
        loadReviewTags()
    } catch (e) {
        rawError.value = e
        error.value = true
        report('api_error', { endpoint: 'staff.detail', message: '主持人详情加载失败' })
    } finally {
        loading.value = false
        firstLoadDone = true
    }
}

/**
 * 轻量刷新「当前日期有效订单」：只打一个接口，不重载整页。
 *
 * 刷新时机（约定）：
 *  - 首次进入由 load() 随详情一起返回；
 *  - 从订单详情/支付结果/预约页返回时 onShow 触发；
 *  - 切换日期/地区后由 load() 重新带回；
 *  - 不做轮询，避免详情页常驻请求。
 */
async function refreshActiveOrder() {
    if (!userStore.isLogin || !staffId.value || refreshingOrder) return
    refreshingOrder = true
    try {
        const res = await getStaffActiveOrder({
            staff_id: staffId.value,
            date: date.value || undefined
        })
        staff.value = { ...staff.value, my_order: res?.order || null }
    } catch {
        // 刷新失败保留旧状态，下一次 onShow 再试
    } finally {
        refreshingOrder = false
    }
}

onShow(() => {
    if (!firstLoadDone || pickMode.value) return
    refreshActiveOrder()
})

/** v1/v2 字段名不同，统一在此归一化 */
function applyBanner(source: any) {
    bannerConfig.value = normalizeBannerConfig({
        mode: source?.banner_mode ?? source?.mode,
        smallHeight: source?.banner_small_height ?? source?.small_height,
        largeHeight: source?.banner_large_height ?? source?.large_height,
        indicatorStyle: source?.banner_indicator_style ?? source?.indicator_style,
        autoplay: source?.banner_autoplay ?? source?.autoplay,
        interval: source?.banner_interval ?? source?.interval
    })
    bannerExpanded.value = false
}

function applyV2(v2: any) {
    const s = v2?.staff || {}
    staff.value = {
        ...s,
        schedule_available: v2?.availability?.is_available !== false,
        schedule_message: v2?.availability?.message || '',
        my_order: v2?.my_order || null
    }
    banners.value = Array.isArray(v2?.banner?.list) ? v2.banner.list : []
    longDetail.value = String(v2?.long_detail || '')
    applyBanner(v2?.banner || {})
    packages.value = Array.isArray(v2?.packages) ? v2.packages : []
    reviewStats.value = v2?.review?.stats || {}
    applyWorks(v2?.works, true)
    applyReviews(v2?.review, true)
    loadReviewTags()
}

function applyWorks(raw: any, reset: boolean) {
    const list = Array.isArray(raw) ? raw : raw?.list || raw?.data || raw?.lists || []
    works.value = reset ? list : works.value.concat(list)
    worksHasMore.value = !!(
        raw?.has_more ??
        (typeof raw?.total === 'number' && works.value.length < raw.total)
    )
    worksLoading.value = false
}

function applyReviews(raw: any, reset: boolean) {
    const list = Array.isArray(raw) ? raw : raw?.list || raw?.data || raw?.lists || []
    reviews.value = reset ? list : reviews.value.concat(list)
    if (raw?.stats) reviewStats.value = raw.stats
    reviewsHasMore.value = !!(
        raw?.has_more ??
        (typeof raw?.total === 'number' && reviews.value.length < raw.total)
    )
    reviewsLoading.value = false
}

async function loadReviewTags() {
    try {
        const res: any = await getReviewTags({ staff_id: staffId.value })
        const raw = Array.isArray(res) ? res : res?.lists || res?.data || []
        reviewTags.value = raw
            .map((t: any) => (typeof t === 'string' ? t : t?.name || t?.tag_name || ''))
            .filter(Boolean)
            .slice(0, 3)
    } catch {
        reviewTags.value = []
    }
}

async function loadWorks(reset: boolean) {
    if (worksLoading.value) return
    if (reset) {
        worksPage.value = 1
        worksHasMore.value = true
    }
    if (!worksHasMore.value) return
    worksLoading.value = true
    try {
        const raw: any = await getStaffWorks({
            staff_id: staffId.value,
            page: worksPage.value,
            page_size: 8
        })
        applyWorks(raw, reset)
        worksPage.value += 1
    } catch {
        worksLoading.value = false
        worksHasMore.value = false
    }
}

async function loadReviews(reset: boolean) {
    if (reviewsLoading.value) return
    if (reset) {
        reviewsPage.value = 1
        reviewsHasMore.value = true
    }
    if (!reviewsHasMore.value) return
    reviewsLoading.value = true
    try {
        const raw: any = await getStaffReviews({
            staff_id: staffId.value,
            page: reviewsPage.value,
            page_size: 5
        })
        applyReviews(raw, reset)
        reviewsPage.value += 1
    } catch {
        reviewsLoading.value = false
        reviewsHasMore.value = false
    }
}

/**
 * 滚动只做一件事：把 scrollTop 交给 sticky-holder（受控吸顶）与 nav-bar（scroll-solid 切实底）。
 * 实底阈值由 nav-bar 的 solidThreshold 自己判，页面不再维护一份 navSolid 状态。
 * 不在 onPageScroll 里做任何数据计算 —— 每帧 setData 是详情页卡顿的头号原因。
 */
onPageScroll((e: any) => {
    scrollTop.value = Number(e?.scrollTop || 0)
})

onShareAppMessage(() =>
    buildShare({
        title: staff.value.name ? `${staff.value.name} · 婚礼主持` : '',
        suffix: '婚礼主持',
        // 分享链接带日期 + 地区：收件人打开时看到的是同一档期、同一个区县价
        path:
            resolveLink({
                path: '/packages/pkg-catalog/pages/staff-detail/staff-detail',
                query: {
                    id: staffId.value,
                    ...(date.value ? { date: date.value } : {}),
                    ...regionParams(region.value)
                }
            }) || ''
    })
)

onLoad((options: any) => {
    staffId.value = Number(options?.id || 0)
    pickRole.value = String(options?.pick_role || '')
    pickPackageId.value = Number(options?.pick_package_id || 0)
    if (!staffId.value) {
        rawError.value = new Error('missing-staff-id')
        error.value = true
        loading.value = false
        return
    }
    /**
     * 日期/地区不再只信上一页 query：
     * 搜索、扫码、分享等入口通常只带 id，这里用「入口参数 > 最近选择 > 系统默认」
     * 解析出完整上下文，再按同一份条件加载详情价格，并原样传给预约页。
     *
     * 直接进入（没有 date 参数）默认第二天：用户先看到「明天能不能约」，
     * 而不是空白档期；地区仍只认入口/缓存，没有就让用户在弹层里明确选择。
     */
    const entryDate = String(options?.date || '')
    resolveBookingContext(options || {}).then(async (ctx) => {
        date.value = /^\d{4}-\d{2}-\d{2}$/.test(entryDate) ? ctx.date : tomorrowKey()
        region.value = ctx.region
        await loadScheduleRule()
        // 默认「第二天」可能早于规则最早可约日：按规则夹紧，避免一进页就是禁用日期
        if (minSelectableDate.value && date.value < minSelectableDate.value) {
            date.value = minSelectableDate.value
        }
        if (maxSelectableDate.value && date.value > maxSelectableDate.value) {
            date.value = maxSelectableDate.value
        }
        saveBookingContext({ date: date.value, region: ctx.region })
        load()
    })
})
</script>

<style lang="scss" scoped>
/* ========== 骨架 ========== */
.ph {
    &__hero {
        padding: var(--sp-4);
        background: var(--c-hero-bg);
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
    }

    &__card {
        position: relative;
        z-index: 2;
        margin: -40rpx 42rpx 0;
        padding: 36rpx 42rpx 28rpx;
        background: var(--c-surface);
        border-radius: var(--r-lg) var(--r-lg) 0 0;
    }
}

/* ========== 身份卡：从 hero 上浮，承接姓名/评分/可约状态 ========== */
.idcard {
    position: relative;
    z-index: 3;
    margin-top: -40rpx;
    padding: 36rpx 42rpx 28rpx;
    background: var(--c-surface);
    border-radius: var(--r-lg) var(--r-lg) 0 0;

    &__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
        min-height: 52rpx;
    }

    &__eyebrow {
        flex: 1;
        min-width: 0;
        display: block;
        font-family: var(--font-serif);
        font-size: var(--fs-18);
        letter-spacing: 2rpx;
        text-transform: uppercase;
        color: var(--c-brand-text);
        line-height: var(--lh-none);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    /* 收藏 + 分享：两个动作并排，样式一致，不再占用导航右区 */
    &__actions {
        flex: none;
        display: flex;
        align-items: center;
        gap: var(--sp-3);
    }

    &__act {
        display: inline-flex;
        align-items: center;
        gap: 6rpx;
        height: 52rpx;
        padding: 0;
        margin: 0;
        border: 0;
        background: transparent;
        line-height: var(--lh-none);
        color: var(--c-ink-3);

        /* open-type=share 的 button 默认边框 */
        &::after {
            border: 0;
        }

        &.is-on {
            color: var(--c-danger);
        }
    }

    &__act-ic {
        color: inherit;
    }

    &__act-text {
        font-size: var(--fs-20);
        color: inherit;
        line-height: var(--lh-none);
    }

    &__name-row {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
        margin-top: 14rpx;
        min-width: 0;
    }

    &__name {
        min-width: 0;
        max-width: 460rpx;
        font-size: var(--fs-46);
        font-weight: var(--fw-semibold);
        letter-spacing: 1rpx;
        line-height: var(--lh-tight);
    }

    /* 推荐标：与列表卡完全同款（品牌金底 + 星形 + 推荐） */
    &__rec {
        flex: none;
        display: inline-flex;
        align-items: center;
        gap: 4rpx;
        height: 34rpx;
        padding: 0 10rpx;
        border-radius: var(--r-xs);
        background: var(--c-brand);
        font-size: var(--fs-18);
        line-height: var(--lh-none);
        color: var(--c-ink);
    }

    &__rec-ic {
        color: var(--c-ink);
    }

    &__rec-text {
        color: var(--c-ink);
    }

    &__badge {
        flex: none;
        height: 34rpx;
        padding: 0 10rpx;
        border-radius: var(--r-xs);
        border: 1rpx solid var(--c-brand);
        background: var(--c-brand-bg);
        color: var(--c-brand-text);
        font-size: var(--fs-18);
        line-height: 32rpx;
    }

    &__meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: var(--sp-2);
        margin-top: 16rpx;
        font-size: var(--fs-22);
        color: var(--c-ink-3);
    }

    &__meta-item {
        display: inline-flex;
        align-items: center;
        gap: 6rpx;
    }

    &__meta-icon {
        color: var(--c-warning);
    }

    &__meta-sep {
        width: 1rpx;
        height: 18rpx;
        background: var(--c-line-strong);
    }

    /*
        可约行两行结构：
        第一行「日期 · 地区 + 更换」，第二行状态原因。
        原来挤成一行，后端的长原因（单日接单上限）会把「更换」顶出屏幕。
    */
    &__avail {
        display: block;
        margin-top: 18rpx;
        padding-top: 16rpx;
        border-top: 1rpx solid var(--c-line);
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &__avail-top {
        display: flex;
        align-items: center;
        gap: 10rpx;
        min-width: 0;
    }

    &__dot {
        flex: none;
        width: 12rpx;
        height: 12rpx;
        border-radius: var(--r-pill);
        background: var(--c-success);

        &.is-full {
            background: transparent;
            border: 1rpx solid var(--c-ink-3);
        }
    }

    &__avail-date {
        flex: none;
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__avail-sep {
        flex: none;
        color: var(--c-ink-3);
    }

    &__avail-region {
        flex: 1;
        min-width: 0;
        color: var(--c-ink-2);
    }

    &__avail-status {
        display: block;
        margin-top: 6rpx;
        padding-left: 22rpx;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }

    /* 可切换档期：右侧「更换」+ chevron，整行可点 */
    &__avail-edit {
        flex: none;
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
        text-decoration: underline;
        text-underline-offset: 3rpx;
    }

    &__avail-arrow {
        flex: none;
        color: var(--c-ink-3);
    }
}

/* ========== 档期与地区弹层 ========== */
.spop {
    display: flex;
    flex-direction: column;
    max-height: 86vh;
    background: var(--c-surface);
    border-radius: var(--r-md) var(--r-md) 0 0;
    overflow: hidden;

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex: none;
        height: 104rpx;
        padding: 0 var(--sp-2) 0 var(--sp-4);
        border-bottom: 1rpx solid var(--c-line);
    }

    &__title {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__close {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 88rpx;
        height: 88rpx;
        color: var(--c-ink-3);
    }

    &__scroll {
        flex: none;
        max-height: 58vh;
    }

    &__region {
        padding: var(--sp-2) var(--sp-4) var(--sp-4);
    }

    &__foot {
        display: flex;
        flex-direction: column;
        flex: none;
        gap: 12rpx;
        padding: var(--sp-2) var(--sp-4) var(--sp-4);
    }

    &__tip {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }
}

.scal {
    padding: var(--sp-3) var(--sp-4) 0;

    &__nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 72rpx;
        height: 72rpx;
        border-radius: var(--r-pill);
        color: var(--c-ink-2);

        &.is-disabled {
            color: var(--c-control-border);
        }
    }

    &__title {
        flex: 1;
        text-align: center;
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__week {
        display: flex;
        margin-top: var(--sp-2);
    }

    &__week-i {
        flex: 1;
        text-align: center;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__grid {
        display: flex;
        flex-wrap: wrap;
        margin-top: 8rpx;
    }

    &__cell {
        width: calc(100% / 7);
        height: 64rpx;
        display: flex;
        align-items: center;
        justify-content: center;

        &.is-blank {
            visibility: hidden;
        }

        &.is-disabled .scal__day {
            color: var(--c-control-border);
            text-decoration: line-through;
        }

        &.is-today .scal__day {
            color: var(--c-brand-text);
        }

        &.is-on .scal__day {
            width: 56rpx;
            height: 56rpx;
            line-height: 56rpx;
            border-radius: var(--r-pill);
            background: var(--c-ink);
            color: var(--c-on-dark);
        }
    }

    &__day {
        display: block;
        width: 56rpx;
        height: 56rpx;
        line-height: 56rpx;
        text-align: center;
        font-size: var(--fs-xs);
        color: var(--c-ink);
    }
}

/* ========== 三页签 ========== */
.dtabs {
    display: flex;
    align-items: stretch;
    height: 88rpx;
    background: var(--c-bg);
    border-bottom: 1rpx solid var(--c-line);
}

.dtab {
    flex: 1;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6rpx;
    font-size: 24rpx;
    color: var(--c-ink-3);

    &__text {
        line-height: var(--lh-none);
    }

    &__count {
        font-family: var(--font-serif);
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }

    &.is-on {
        color: var(--c-ink);
        font-weight: var(--fw-semibold);

        .dtab__count {
            color: var(--c-brand-text);
        }

        &::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 42rpx;
            height: 4rpx;
            border-radius: var(--r-2);
            background: var(--c-ink);
        }
    }
}

/* ========== 详情页签 ========== */
.panel {
    padding: 34rpx 42rpx 0;
}

/* 详情页签里长图要全宽，所以 padding 下放到 inner */
.panel--about {
    padding: 0;
}

.panel__inner {
    padding: 34rpx 42rpx 0;
}

.intro {
    display: block;
    font-size: var(--fs-25);
    line-height: var(--lh-loose);
    color: var(--c-ink-2);

    & + & {
        margin-top: 18rpx;
    }
}

.facts {
    display: flex;
    align-items: baseline;
    flex-wrap: wrap;
    gap: var(--sp-2);
    margin-top: 23rpx;
    font-size: 20rpx;
    color: var(--c-ink-3);

    &__sep {
        width: 1rpx;
        height: 16rpx;
        background: var(--c-line-strong);
    }

    &__item {
        display: inline-flex;
        align-items: baseline;
        gap: 4rpx;
    }

    &__num {
        font-family: var(--font-serif);
        font-size: 24rpx;
        color: var(--c-ink);
    }
}

.specs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18rpx 27rpx;
    margin-top: 23rpx;
    padding-top: 23rpx;
    border-top: 1rpx solid var(--c-line);
}

.spec {
    display: flex;
    flex-direction: column;
    gap: var(--sp-1);
    min-width: 0;

    &__k {
        font-size: var(--fs-18);
        color: var(--c-ink-3);
        letter-spacing: 1rpx;
    }

    &__v {
        font-size: var(--fs-22);
        color: var(--c-ink);
        line-height: var(--lh-body);
    }
}

.inc {
    margin-top: 31rpx;
    padding-top: 27rpx;
    border-top: 1rpx solid var(--c-line);

    &__title {
        display: block;
        font-size: var(--fs-22);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        margin-bottom: 19rpx;
    }

    &__para {
        display: block;
        font-size: var(--fs-22);
        color: var(--c-ink-2);
        line-height: var(--lh-loose);

        & + & {
            margin-top: 12rpx;
        }
    }
}

/* ========== 作品页签 ========== */
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
    overflow: hidden;
    background: var(--c-hero-bg);

    &__img {
        width: 100%;
        height: 100%;
    }

    &__blank {
        width: 100%;
        height: 100%;
        background: var(--c-hero-bg);
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

/* ========== 评价页签 ========== */
.rsum {
    display: flex;
    align-items: center;
    gap: var(--sp-4);
    padding-bottom: 29rpx;
    border-bottom: 1rpx solid var(--c-line);

    &__num {
        font-family: var(--font-serif);
        font-size: var(--fs-63);
        font-weight: var(--fw-medium);
        line-height: var(--lh-none);
        color: var(--c-ink);
    }

    &__mid {
        flex: 1;
        min-width: 0;
    }

    &__sub {
        display: block;
        margin-top: 12rpx;
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }
}

.stars {
    display: flex;
    align-items: center;
    gap: 4rpx;

    &__i {
        /* G1 ⑧：已选星原 --c-brand(#c6a15b) 对白底 2.39:1，与评分控件真源
           form-field.vue 的 --c-warning 对齐（一个语义一个值，不用两套） */
        color: var(--c-warning);

        &.is-off {
            /* G1 ⑨：未选星原 --c-line-strong 仅 1.60:1（看不出有几颗星）→ --c-ink-3 5.67:1 */
            color: var(--c-ink-3);
        }
    }

    &--sm {
        margin-left: auto;
    }
}

.rtags {
    display: flex;
    flex-wrap: wrap;
    gap: 10rpx;
    justify-content: flex-end;
    max-width: 280rpx;

    &__i {
        height: 38rpx;
        padding: 0 14rpx;
        border-radius: var(--r-xs);
        border: 1rpx solid var(--c-primary-border);
        background: var(--c-brand-bg);
        color: var(--c-brand-text);
        font-size: var(--fs-18);
        line-height: 36rpx;
        white-space: nowrap;
    }
}

.reviews {
    display: flex;
    flex-direction: column;
}

.review {
    padding: 25rpx 0;
    border-bottom: 1rpx solid var(--c-line);

    &:last-child {
        border-bottom: 0;
    }

    &__head {
        display: flex;
        align-items: center;
        gap: 14rpx;
    }

    &__avatar {
        flex: none;
        width: 46rpx;
        height: 46rpx;
        border-radius: var(--r-pill);
        border: 1rpx solid var(--c-primary-border);
        background: var(--c-brand-bg);
        color: var(--c-brand-text);
        font-family: var(--font-serif);
        font-size: 20rpx;
        text-align: center;
        line-height: 44rpx;
    }

    &__name {
        font-size: var(--fs-22);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        max-width: 220rpx;
    }

    &__date {
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }

    &__text {
        display: block;
        margin-top: 16rpx;
        font-size: var(--fs-22);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
    }
}

/* ========== 吸底栏 ========== */
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
        font-size: 24rpx;
        color: var(--c-ink-2);
    }

    &__note {
        overflow: hidden;
        font-size: var(--fs-18);
        color: var(--c-ink-3);
        white-space: nowrap;
        text-overflow: ellipsis;
    }
}

.abtns {
    display: flex;
    flex: none;
    align-items: center;
    gap: var(--sp-2);
}

@media screen and (max-width: 359px) {
    .idcard {
        padding-left: var(--sp-3);
        padding-right: var(--sp-3);
    }

    .panel {
        padding-left: var(--sp-3);
        padding-right: var(--sp-3);
    }

    .panel--about {
        padding-left: 0;
        padding-right: 0;
    }

    .panel__inner {
        padding-left: var(--sp-3);
        padding-right: var(--sp-3);
    }

    .ph__card {
        margin-left: var(--sp-3);
        margin-right: var(--sp-3);
    }
}
</style>
