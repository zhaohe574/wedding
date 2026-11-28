<template>
    <!-- error 态是整页空态、没有轮播承接；scroll-solid 在 scrollTop=0 时透明且无占位块，
         内容会顶到状态栏。故 error 直接走 solid（loading 有 .ph__hero，保持 scroll-solid）。 -->
    <page-shell
        bg="page"
        :bottom-bar="true"
        :title="navTitle"
        :nav-variant="error ? 'solid' : 'scroll-solid'"
        :scroll-top="scrollTop"
        :nav-solid-threshold="navSolidThreshold"
        :nav-tone="navTone"
        :nav-capsule-avoid="true"
        nav-background="page"
    >
        <!-- 导航栏不放右侧动作：收藏、分享在身份卡里；滚动切实底后居中展示人员姓名并避让胶囊 -->

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
                :scroll-top="bannerScrollTop"
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
                            class="idcard__act hit-88"
                            :class="{ 'is-on': isFavorite }"
                            aria-role="button"
                            :aria-label="isFavorite ? '取消收藏' : '收藏这位主持人'"
                            @tap="onFavoriteTap"
                        >
                            <tn-icon
                                :name="isFavorite ? 'like-fill' : 'like'"
                                :size="26"
                                class="idcard__act-ic"
                            />
                            <text class="idcard__act-text">{{
                                isFavorite ? '已收藏' : '收藏'
                            }}</text>
                        </view>
                        <!-- open-type=share 的 button 必须自己抹掉默认样式 -->
                        <button
                            class="idcard__act hit-88"
                            open-type="share"
                            aria-label="分享这位主持人"
                        >
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
                        <view
                            class="idcard__dot"
                            :class="{ 'is-full': !scheduleAvailable && !hasActiveOrder }"
                        />
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
                        <text v-for="(p, i) in introParagraphs" :key="i" class="intro">{{
                            p
                        }}</text>
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
                                <text class="facts__num t-num">{{ f.value }}</text>
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

                    <!-- 服务套餐：详情页直接选购，选中项与吸底栏、预约页同源 -->
                    <view v-if="packages.length" class="pkgpick">
                        <view class="pkgpick__head">
                            <text class="pkgpick__title">服务套餐</text>
                            <text class="pkgpick__count t-num">{{ packages.length }} 档</text>
                        </view>
                        <view class="pkglist">
                            <view
                                v-for="c in packageCards"
                                :key="c.id"
                                class="pkgcard"
                                :class="{ 'is-on': c.id === selectedPackageId }"
                                hover-class="is-pressed"
                                aria-role="radio"
                                :aria-checked="c.id === selectedPackageId"
                                @tap="onSelectPackage(c.id)"
                            >
                                <view class="pkgcard__top">
                                    <view class="pkgcard__namewrap">
                                        <text class="pkgcard__name ellipsis">{{ c.name }}</text>
                                        <text v-if="c.recommended" class="pkgcard__rec">推荐</text>
                                    </view>
                                    <view class="pkgcard__tick" aria-hidden="true">
                                        <tn-icon name="check" :size="16" />
                                    </view>
                                </view>
                                <view v-if="c.duration || c.addonCount" class="pkgcard__tags">
                                    <text v-if="c.duration" class="pkgcard__tag">{{
                                        c.duration
                                    }}</text>
                                    <text v-if="c.addonCount" class="pkgcard__tag pkgcard__tag--addon"
                                        >{{ c.addonCount }} 项加购</text
                                    >
                                </view>
                                <text v-if="c.description" class="pkgcard__desc ellipsis">{{
                                    c.description
                                }}</text>
                                <view class="pkgcard__price">
                                    <price size="sm" :content="c.price" />
                                    <text class="pkgcard__unit">/ 场</text>
                                    <price
                                        v-if="c.originalPrice > Number(c.price)"
                                        size="sm"
                                        :content="c.originalPrice"
                                        line-through
                                    />
                                </view>
                            </view>
                        </view>
                    </view>
                    <view v-else-if="regionReady" class="pkgempty">
                        <text class="pkgempty__text">当前地区暂无可约套餐</text>
                        <view
                            v-if="!pickMode"
                            class="pkgempty__btn"
                            hover-class="is-pressed"
                            aria-role="button"
                            @tap="openScheduleSheet"
                        >
                            <text class="pkgempty__btn-text">切换服务地区</text>
                            <tn-icon name="right" :size="18" class="pkgempty__btn-ic" />
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
            <staff-works-panel
                v-else-if="tab === 'works'"
                :works="works"
                :has-more="worksHasMore"
                :load-status="worksLoadStatus"
                @load-more="loadWorks(false)"
                @select="openWorkDetail"
            />

            <!-- 页签三：评价 -->
            <staff-reviews-panel
                v-else
                :reviews="reviews"
                :review-count="reviewCount"
                :score-text="scoreText"
                :star-filled="starFilled"
                :review-sub-text="reviewSubText"
                :review-tags="reviewTags"
                :load-status="reviewLoadStatus"
                @load-more="loadReviews(false)"
            />
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
                        <text class="abp__label">{{ selectedPackage ? '当前套餐' : '服务起价' }}</text>
                        <price v-if="startPrice != null" size="md" :content="startPrice" />
                        <text v-else class="abp__na">面议</text>
                        <text v-if="barNote" class="abp__note">{{ barNote }}</text>
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
                                :text="
                                    hasActiveOrder
                                        ? '查看订单'
                                        : scheduleAvailable
                                          ? '预约档期'
                                          : '去候补'
                                "
                                variant="ink"
                                size="md"
                                :block="false"
                                @click="hasActiveOrder ? viewMyOrder() : handlePrimaryAction()"
                            />
                        </template>
                    </view>
                </template>
            </bottom-bar>

            <staff-schedule-sheet
                v-model="schedVisible"
                :date="date"
                :region="region"
                :min-date="minSelectableDate"
                :max-date="maxSelectableDate"
                @confirm="onScheduleConfirm"
            />

            <app-toast />
        </template>
    </page-shell>
</template>

<script setup lang="ts">
/**
 * 人员详情（05 的迭代版）。
 *
 * 结构：可配置轮播 + 上浮身份卡 + 三页签（详情 / 作品 / 评价）+ 吸底栏；
 * 详情页签末尾接后台 long_detail 装修内容。
 * 数据：优先 v2 聚合 getStaffDetailV2（1 次请求），失败降级 v1（detail + works + reviews 并发）。
 */
import { computed, nextTick, ref } from 'vue'
import { onLoad, onPageScroll, onShareAppMessage, onShow } from '@dcloudio/uni-app'
import { toast } from '@/composables/useToast'
import { getNavBarHeight } from '@/platform/device'
import { resolveBookingContext, saveBookingContext } from '@/utils/booking-context'
import cache from '@/utils/cache'
import { navigate, resolveLink } from '@/utils/link'
import { EMPTY_REGION, regionParams, type RegionContext } from '@/utils/region'
import { buildShare } from '@/utils/share'
import { DEFAULT_BANNER_CONFIG, bannerStageHeight } from './banner'
import LongDetail from './components/long-detail.vue'
import StaffBanner from './components/staff-banner.vue'
import StaffReviewsPanel from './components/staff-reviews-panel.vue'
import StaffScheduleSheet from './components/staff-schedule-sheet.vue'
import StaffWorksPanel from './components/staff-works-panel.vue'
import {
    staffPackageId,
    tomorrowKey,
    toStaffPackageCard,
    useStaffDetail,
    type StaffDetailTabKey
} from './useStaffDetail'

const navTotalPx = getNavBarHeight()

const TABS: Array<{ key: StaffDetailTabKey; label: string }> = [
    { key: 'about', label: '详情' },
    { key: 'works', label: '作品' },
    { key: 'reviews', label: '评价' }
]

const staffId = ref(0)
const date = ref('')
const pickRole = ref('')
const pickPackageId = ref(0)
const region = ref<RegionContext>({ ...EMPTY_REGION })
const schedVisible = ref(false)
const tab = ref<StaffDetailTabKey>('about')
const scrollTop = ref(0)
/** 视差专用：节流后的滚动值，避免每个 scroll 事件都穿透到 banner 触发重排 */
const bannerScrollTop = ref(0)
let parallaxTimer: ReturnType<typeof setTimeout> | null = null
const stickyRef = ref<any>(null)

/** 40ms 节流：首个事件立即记下，尾部再补一次最新值（不掉最后一帧） */
function syncBannerScroll() {
    if (parallaxTimer) return
    parallaxTimer = setTimeout(() => {
        parallaxTimer = null
        bannerScrollTop.value = scrollTop.value
    }, 40)
}

const {
    loading,
    error,
    rawError,
    view,
    staff,
    bannerItems,
    bannerConfig,
    bannerExpanded,
    bannerHasWideVisual,
    longDetail,
    packages,
    works,
    worksHasMore,
    reviews,
    reviewTags,
    minSelectableDate,
    maxSelectableDate,
    scheduleAvailable,
    selectedPackageId,
    selectedPackage,
    selectedPackageName,
    selectPackage,
    myOrder,
    hasActiveOrder,
    isFavorite,
    isRecommend,
    fallbackImage,
    fallbackKind,
    rankName,
    eyebrow,
    dateLabel,
    availabilityText,
    regionCity,
    regionReady,
    heroMeta,
    introParagraphs,
    facts,
    specs,
    serviceParagraphs,
    startPrice,
    reviewCount,
    scoreText,
    starFilled,
    reviewSubText,
    worksLoadStatus,
    reviewLoadStatus,
    tabCount,
    imageUrl,
    loadScheduleRule,
    load,
    refreshActiveOrder,
    toggleFavorite,
    loadWorks,
    loadReviews
} = useStaffDetail({ staffId, date, region })

const navTone = computed<'dark' | 'light'>(() =>
    loading.value || error.value || !bannerHasWideVisual.value ? 'dark' : 'light'
)
const bannerStage = computed(() => bannerStageHeight(bannerConfig.value, bannerExpanded.value))
const navSolidThreshold = computed(() => Math.max(40, bannerStage.value - navTotalPx))
/** 仅在滚动切为实底导航且人员数据已就绪时展示人员姓名（头图透明态不展示） */
const navTitle = computed(() =>
    !loading.value && !error.value && scrollTop.value > navSolidThreshold.value
        ? String(staff.value?.name || '').trim()
        : ''
)
const skeletonHeight = bannerStageHeight(DEFAULT_BANNER_CONFIG)

const pickMode = computed(() => !!pickRole.value)
const pickLabel = computed(() => {
    if (pickRole.value === 'butler') return '婚礼管家'
    if (pickRole.value === 'director') return '婚礼督导'
    return ''
})
const pickPackage = computed(
    () =>
        packages.value.find((p) => staffPackageId(p) === Number(pickPackageId.value)) || null
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
const fullNote = computed(() => '该日不可约')
const packageCards = computed(() => packages.value.map(toStaffPackageCard))
/** 吸底栏副文案：订单/可约状态优先，其次是当前选中套餐名 */
const barNote = computed(() => {
    if (hasActiveOrder.value) return '该日已有你的预约'
    if (!scheduleAvailable.value) return fullNote.value
    return selectedPackageName.value
})

function goBack() {
    const pages = getCurrentPages()
    if (pages.length > 1) uni.navigateBack()
    else uni.switchTab({ url: '/pages/index/index' })
}

function backFromPick() {
    goBack()
}

function confirmPick() {
    const pkg = pickPackage.value
    if (!pkg) {
        toast.info('该人员暂未配置可预约套餐')
        return
    }
    cache.set('booking_role_pick', {
        role_key: pickRole.value,
        staff_id: staffId.value,
        package_id: staffPackageId(pkg),
        name: String(staff.value.name || ''),
        avatar: String(staff.value.avatar || ''),
        price: Number(pkg.price ?? pkg?.package?.price ?? 0),
        ts: Date.now()
    })
    backFromPick()
}

/** 套餐卡点击：选人模式同步 pick_package_id，避免下方「选择 TA」沿用旧套餐价 */
function onSelectPackage(id: number) {
    selectPackage(id)
    if (pickMode.value) pickPackageId.value = Number(id || 0)
}

function switchTab(key: StaffDetailTabKey) {
    if (tab.value === key) return
    tab.value = key
    nextTick(() => stickyRef.value?.scrollIntoView?.())
    if (key === 'works' && !works.value.length) loadWorks(true)
    if (key === 'reviews' && !reviews.value.length) loadReviews(true)
}

function onBannerExpand(expanded: boolean) {
    bannerExpanded.value = expanded
    setTimeout(() => stickyRef.value?.remeasure?.(), 300)
}

/** 收藏/分享：动作在身份卡内，按压态由各自节点承担，不带动整块卡片 */
function onFavoriteTap() {
    toggleFavorite()
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

function goConsult() {
    navigate({
        path: '/pages/customer-service/customer-service',
        query: { scene: 'staff', staff_id: staffId.value }
    })
}

/** 作品网格点击 → 作品详情（消费端独立页，支持分享直达） */
function openWorkDetail(work: any) {
    const workId = Number(work?.id || 0)
    if (!workId) return
    navigate({
        path: '/packages/pkg-catalog/pages/work-detail/work-detail',
        query: { id: workId }
    })
}

function openScheduleSheet() {
    if (pickMode.value) return
    schedVisible.value = true
    if (!minSelectableDate.value) loadScheduleRule()
}

function onScheduleConfirm(payload: { date: string; region: RegionContext }) {
    date.value = payload.date
    region.value = { ...payload.region }
    saveBookingContext({ date: date.value, region: region.value })
    // 换档期/地区后必须按新条件重新拉价格与可约状态，不能沿用旧结果
    load()
}

function handlePrimaryAction() {
    if (!regionReady.value) {
        openScheduleSheet()
        toast.info('请选择服务地区后继续')
        return
    }
    // 有地区但没有可售套餐：跳空预约页只会让用户卡在「暂无套餐」，就地引导换地区
    if (!packages.value.length) {
        toast.warning('当前地区暂无可约套餐，请切换地区')
        openScheduleSheet()
        return
    }
    const shared = {
        staff_id: staffId.value,
        package_id: selectedPackageId.value > 0 ? selectedPackageId.value : '',
        date: date.value,
        ...regionParams(region.value)
    }
    if (scheduleAvailable.value) {
        navigate({ path: '/packages/pkg-catalog/pages/booking/booking', query: shared })
        return
    }
    navigate({ path: '/packages/pkg-order/pages/waitlist/apply', query: shared })
}

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

onShow(() => {
    if (pickMode.value) return
    refreshActiveOrder()
})

/**
 * page-shell 默认不接管滚动（只有 pullRefresh / lockViewport 才接管并 emit('scroll')），
 * 本页是页面级滚动，所以视差数据源用 onPageScroll + 40ms 节流，避免每帧 setData。
 */
onPageScroll((e: any) => {
    scrollTop.value = Number(e?.scrollTop || 0)
    syncBannerScroll()
})

onShareAppMessage(() =>
    buildShare({
        title: staff.value.name ? `${staff.value.name} · 婚礼主持` : '',
        suffix: '婚礼主持',
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
    // 入口带了套餐就先生效，列表回来后再按「是否仍可售」校准
    selectedPackageId.value = Number(options?.package_id || options?.pick_package_id || 0)
    if (!staffId.value) {
        rawError.value = new Error('missing-staff-id')
        error.value = true
        loading.value = false
        return
    }
    const entryDate = String(options?.date || '')
    resolveBookingContext(options || {}).then(async (ctx) => {
        date.value = /^\d{4}-\d{2}-\d{2}$/.test(entryDate) ? ctx.date : tomorrowKey()
        region.value = ctx.region
        await loadScheduleRule()
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
    /* 身份卡压在头图上：暖影负责与照片分层，深浅两套同一条变量 */
    box-shadow: var(--shadow-card);

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

    &__rec-ic,
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
    font-size: var(--fs-xs);
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
    font-size: var(--fs-20);
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
        font-size: var(--fs-xs);
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

/* ========== 服务套餐：详情页内选购预览 ========== */
.pkgpick {
    margin-top: 31rpx;
    padding-top: 27rpx;
    border-top: 1rpx solid var(--c-line);

    &__head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__title {
        font-size: var(--fs-22);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__count {
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }
}

.pkglist {
    display: flex;
    flex-direction: column;
    gap: 14rpx;
    margin-top: 18rpx;
}

.pkgcard {
    padding: 20rpx;
    /* 同心圆角：外 32rpx − 内边距 20rpx ≈ 12rpx（--r-sm）→ 外卡统一 --r-md */
    border-radius: var(--r-md);
    border: 1rpx solid var(--c-card-border);
    background: var(--c-surface);
    box-shadow: var(--shadow-card);
    transition:
        border-color var(--dur-fast) var(--ease-out),
        box-shadow var(--dur-base) var(--ease-out);

    &.is-on {
        border-color: var(--c-active-border);
        box-shadow: var(--shadow-card-hover);

        .pkgcard__tick {
            opacity: 1;
            transform: scale(1);
        }
    }

    &__top {
        display: flex;
        align-items: flex-start;
        gap: var(--sp-2);
    }

    &__namewrap {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 8rpx;
    }

    &__name {
        min-width: 0;
        font-size: var(--fs-25);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        line-height: var(--lh-tight);
    }

    &__rec {
        flex: none;
        display: inline-flex;
        align-items: center;
        height: 30rpx;
        padding: 0 10rpx;
        border-radius: var(--r-xs);
        background: var(--c-brand);
        color: var(--c-on-brand);
        font-size: var(--fs-18);
        line-height: var(--lh-none);
    }

    &__tick {
        flex: none;
        width: 30rpx;
        height: 30rpx;
        border-radius: var(--r-pill);
        background: var(--c-active-bg);
        color: var(--c-active-fg);
        border: 1rpx solid var(--c-active-border);
        box-sizing: border-box;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transform: scale(0.7);
        transition: opacity var(--dur-fast) var(--ease-out),
            transform var(--dur-fast) var(--ease-out);
    }

    &__tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8rpx;
        margin-top: 10rpx;
    }

    &__tag {
        display: inline-flex;
        align-items: center;
        height: 34rpx;
        padding: 0 10rpx;
        border-radius: var(--r-xs);
        background: var(--c-surface-2);
        font-size: var(--fs-18);
        line-height: var(--lh-none);
        color: var(--c-ink-2);
    }

    &__tag--addon {
        background: var(--c-brand-bg);
        color: var(--c-brand-text);
    }

    &__desc {
        display: block;
        margin-top: 8rpx;
        font-size: var(--fs-20);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }

    &__price {
        display: flex;
        align-items: baseline;
        gap: 8rpx;
        margin-top: 10rpx;
    }

    &__unit {
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }
}

.pkgempty {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);
    margin-top: 27rpx;
    padding: 22rpx 24rpx;
    border: 1rpx dashed var(--c-line-strong);
    border-radius: var(--r-sm);

    &__text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-20);
        color: var(--c-ink-2);
    }

    &__btn {
        flex: none;
        display: inline-flex;
        align-items: center;
        gap: 2rpx;
        padding: 12rpx 0;
        margin: -12rpx 0;
    }

    &__btn-text {
        font-size: var(--fs-20);
        color: var(--c-brand-text);
    }

    &__btn-ic {
        color: var(--c-brand-text);
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
        font-size: var(--fs-xs);
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
    .idcard,
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
