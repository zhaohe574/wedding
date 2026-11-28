<template>
    <page-shell
        nav
        :title="navTitle"
        nav-variant="scroll-solid"
        nav-tone="light"
        nav-background="page"
        :scroll-top="navScrollTop"
        :nav-solid-threshold="navSolidThreshold"
        :nav-capsule-avoid="true"
        bg="page"
        :show-back="false"
        tabbar
        current="/pages/index/index"
        :pull-refresh="true"
        @scroll="onPageScrollTop"
        @refresh="onRefresh"
    >
        <!-- ============ 沉浸式 Hero ============ -->
        <!-- 底色分工：顶部由 .hero 与 pull-refresh 铺 --c-dark-2 常暗底；
             .shell--page 承载下方数据条 / 四宫格 / 推荐位 / 服务承诺 / 页脚与
             底部 tabbar 留白的 --c-bg，保证手动切换深浅色时全屏底色随 .scheme-* 翻转 -->
        <view class="hero" :style="{ height: `${heroHeight}rpx` }">
            <!-- 首屏主图来自装修 banner；未配置时回落到内置主图，保证不白屏。
                 视差只改 transform，位移上限锁在放大后的裁剪余量内，不会露出底下的常暗底。 -->
            <image
                class="hero__media"
                :src="heroImage"
                mode="aspectFill"
                :style="heroMediaStyle"
            />
            <!-- 渐变遮罩承载文字层级：上深（压状态栏文字）、下更深（压 CTA） -->
            <view class="hero__scrim" />
            <!-- 顶部暖墨渐变：压在遮罩之上，让状态栏与页面底色自然融合，不再是一刀切的硬边 -->
            <view class="hero__fade" aria-hidden="true" />

            <!-- 左上角不设品牌位：主图铺满到状态栏下，避免与系统胶囊争抢视觉重心 -->
            <view class="hero__copy" :style="heroCopyStyle">
                <!-- 宣传语回退链：装修 → 站点 Slogan → 不显示；开关关掉同样不显示 -->
                <text
                    v-if="heroText.showSlogan"
                    class="hero__eyebrow"
                    :style="heroSloganColor ? { color: heroSloganColor } : undefined"
                    >{{ heroText.slogan }}</text
                >
                <view v-if="heroText.showTitle" class="hero__title">
                    <text
                        v-for="(line, i) in heroText.titleLines"
                        :key="i"
                        class="hero__title-line"
                        :style="heroTitleColor ? { color: heroTitleColor } : undefined"
                        >{{ line }}</text
                    >
                </view>
                <text
                    v-if="heroText.showSubtitle"
                    class="hero__scope"
                    :style="heroSubtitleColor ? { color: heroSubtitleColor } : undefined"
                    >{{ heroText.subtitle }}</text
                >

                <view
                    v-if="heroText.showPrimary || heroText.showSecondary"
                    class="hero__cta"
                >
                    <!-- 主行动：文案与跳转目标均可装修，未配置跳转时默认进档期查询 -->
                    <template v-if="heroText.showPrimary">
                        <!-- #ifdef MP-WEIXIN -->
                        <button
                            v-if="isWechatContactLink(heroPrimaryLink)"
                            open-type="contact"
                            class="hero__primary hero__primary--btn press-spring btn--icon-r"
                            :class="{ 'is-pressing': heroPressing, 'is-rebound': heroRebound }"
                            @touchstart="onHeroPressStart"
                            @touchend="onHeroPressEnd"
                            @touchcancel="onHeroPressCancel"
                        >
                            <text class="hero__primary-text">{{ heroText.primaryText }}</text>
                            <tn-icon name="right" :size="28" />
                        </button>
                        <view
                            v-else
                            class="hero__primary press-spring btn--icon-r"
                            :class="{ 'is-pressing': heroPressing, 'is-rebound': heroRebound }"
                            @touchstart="onHeroPressStart"
                            @touchend="onHeroPressEnd"
                            @touchcancel="onHeroPressCancel"
                            @tap="onHeroPrimary"
                        >
                            <text class="hero__primary-text">{{ heroText.primaryText }}</text>
                            <tn-icon name="right" :size="28" />
                        </view>
                        <!-- #endif -->
                        <!-- #ifndef MP-WEIXIN -->
                        <view
                            class="hero__primary press-spring btn--icon-r"
                            :class="{ 'is-pressing': heroPressing, 'is-rebound': heroRebound }"
                            @touchstart="onHeroPressStart"
                            @touchend="onHeroPressEnd"
                            @touchcancel="onHeroPressCancel"
                            @tap="onHeroPrimary"
                        >
                            <text class="hero__primary-text">{{ heroText.primaryText }}</text>
                            <tn-icon name="right" :size="28" />
                        </view>
                        <!-- #endif -->
                    </template>
                    <template v-if="heroText.showSecondary">
                        <!-- #ifdef MP-WEIXIN -->
                        <button
                            v-if="isWechatContactLink(heroSecondaryLink)"
                            open-type="contact"
                            hover-class="is-pressed"
                            class="hero__ghost hero__ghost--btn"
                        >
                            {{ heroText.secondaryText }}
                        </button>
                        <text
                            v-else
                            hover-class="is-pressed"
                            class="hero__ghost"
                            @tap="goAbout"
                            >{{ heroText.secondaryText }}</text
                        >
                        <!-- #endif -->
                        <!-- #ifndef MP-WEIXIN -->
                        <text
                            hover-class="is-pressed"
                            class="hero__ghost"
                            @tap="goAbout"
                            >{{ heroText.secondaryText }}</text
                        >
                        <!-- #endif -->
                    </template>
                </view>
            </view>
        </view>

        <!-- ============ 数据条（叠压 Hero 底部 -65rpx） ============ -->
        <!-- 口径：stats 只认接口 home-brand.widget.stats；接口没有就整块不渲染，不写死数字 -->
        <view
            v-if="stats.length || loadingHome"
            class="stats"
            :class="brandContent.style === 'glass' ? 'stats--glass' : 'stats--solid'"
        >
            <view v-for="(s, i) in statsToShow" :key="i" class="stats__item">
                <view class="stats__value">
                    <rolling-number
                        v-if="s.stat"
                        :value="s.stat.number"
                        :prefix="s.stat.prefix"
                        :suffix="s.stat.suffix"
                        :decimals="s.stat.decimals"
                        :thousands="s.stat.thousands"
                        :min-chars="4"
                    />
                    <text v-else>{{ s.value }}</text>
                </view>
                <text class="stats__label">{{ s.label }}</text>
            </view>
        </view>

        <!-- ============ 四宫格 / 编辑部拼版（装修 home-service-categories 驱动；组件缺失才用内置四条） ============ -->
        <view
            v-if="quickEntries.length"
            class="tile-grid"
            :class="[`tile-grid--${quickLayout}`, { stagger: quickStaggerOn, 'is-in': quickStaggerIn }]"
        >
            <template v-for="(entry, index) in quickEntries" :key="entry.key">
                <!-- #ifdef MP-WEIXIN -->
                <button
                    v-if="isWechatContactLink(entry.link)"
                    open-type="contact"
                    class="tile tile--btn press-spring"
                    :class="tileClassOf(entry)"
                    :style="tileStyleOf(entry, index)"
                    @touchstart="onPressStart(entry.key)"
                    @touchend="onPressEnd"
                    @touchcancel="onPressCancel"
                >
                    <image
                        v-if="entry.image"
                        class="tile__image"
                        :src="entry.image"
                        mode="aspectFill"
                        lazy-load
                    />
                    <view class="tile__scrim" />
                    <view
                        class="tile__copy"
                        :class="[
                            `tile__copy--${entry.textPosition}`,
                            `tile__copy--align-${entry.textAlign}`
                        ]"
                    >
                        <text class="tile__title">{{ entry.title }}</text>
                        <text v-if="entry.subtitle" class="tile__subtitle">{{ entry.subtitle }}</text>
                        <view class="tile__line" />
                    </view>
                </button>
                <view
                    v-else
                    class="tile press-spring"
                    :class="tileClassOf(entry)"
                    :style="tileStyleOf(entry, index)"
                    @touchstart="onPressStart(entry.key)"
                    @touchend="onPressEnd"
                    @touchcancel="onPressCancel"
                    @tap="onQuick(entry)"
                >
                    <image
                        v-if="entry.image"
                        class="tile__image"
                        :src="entry.image"
                        mode="aspectFill"
                        lazy-load
                    />
                    <view class="tile__scrim" />
                    <view
                        class="tile__copy"
                        :class="[
                            `tile__copy--${entry.textPosition}`,
                            `tile__copy--align-${entry.textAlign}`
                        ]"
                    >
                        <text class="tile__title">{{ entry.title }}</text>
                        <text v-if="entry.subtitle" class="tile__subtitle">{{ entry.subtitle }}</text>
                        <view class="tile__line" />
                    </view>
                </view>
                <!-- #endif -->
                <!-- #ifndef MP-WEIXIN -->
                <view
                    class="tile press-spring"
                    :class="tileClassOf(entry)"
                    :style="tileStyleOf(entry, index)"
                    @touchstart="onPressStart(entry.key)"
                    @touchend="onPressEnd"
                    @touchcancel="onPressCancel"
                    @tap="onQuick(entry)"
                >
                    <image
                        v-if="entry.image"
                        class="tile__image"
                        :src="entry.image"
                        mode="aspectFill"
                        lazy-load
                    />
                    <view class="tile__scrim" />
                    <view
                        class="tile__copy"
                        :class="[
                            `tile__copy--${entry.textPosition}`,
                            `tile__copy--align-${entry.textAlign}`
                        ]"
                    >
                        <text class="tile__title">{{ entry.title }}</text>
                        <text v-if="entry.subtitle" class="tile__subtitle">{{ entry.subtitle }}</text>
                        <view class="tile__line" />
                    </view>
                </view>
                <!-- #endif -->
            </template>
        </view>

        <!-- ============ 本期推荐 ============ -->
        <!-- 无推荐人（含请求失败）时整块不渲染：不留标题、不留占位，避免首页出现空区块 -->
        <view v-if="recommendEnabled && (loadingHome || staff.length)" class="sec">
            <!-- 推荐位改为横向平铺，全部推荐人员都在这一条里，不再有「查看全部」二级入口 -->
            <section-header title="本期推荐" align="end" flush />
        </view>

        <!-- 推荐位骨架：只占推荐位高度，Hero 与数据条不闪白 -->
        <scroll-view
            v-if="recommendEnabled && loadingHome"
            scroll-x
            class="hosts"
            :show-scrollbar="false"
        >
            <view class="hosts__track">
                <view v-for="n in 2" :key="n" class="host-sk">
                    <view class="host-sk__ph" />
                    <view class="host-sk__line" />
                    <view class="host-sk__line host-sk__line--short" />
                </view>
            </view>
        </scroll-view>

        <!-- 竖版人像卡横向滑动：3:4 画框与竖版素材同比例，不再把脸裁掉。
             snap 吸附 + 中心卡聚焦：两侧轻缩，视线自动落在屏幕中线那一张。 -->
        <scroll-view
            v-else-if="recommendEnabled && staff.length"
            scroll-x
            class="hosts"
            :show-scrollbar="false"
            @scroll="onHostsScroll"
        >
            <view class="hosts__track">
                <view
                    v-for="(item, index) in staff"
                    :key="item.id"
                    class="host"
                    :class="{
                        'is-focus': hostFocus === index,
                        'is-side': hostFocus !== index,
                        'is-far': Math.abs(hostFocus - index) > 1,
                        'is-pressing': pressKey === `host-${item.id}`,
                        'is-rebound': reboundKey === `host-${item.id}`
                    }"
                    @touchstart="onPressStart(`host-${item.id}`)"
                    @touchend="onPressEnd"
                    @touchcancel="onPressCancel"
                    @tap="goStaffDetail(item.id)"
                >
                    <view class="host__ph">
                        <image
                            class="host__img"
                            :src="imageUrl(item.cover || item.avatar)"
                            mode="aspectFill"
                        />
                        <!-- 推荐标识：本期推荐位里所有卡片都带，和普通人员一眼区分 -->
                        <view v-if="item.is_recommend" class="host__rec">
                            <tn-icon name="star-fill" :size="18" class="host__rec-ic" />
                            <text class="host__rec-text">推荐</text>
                        </view>
                        <!-- 职级徽章：接口无 rank 字段，不渲染，不猜 -->
                        <view v-if="item.rank" class="host__rank">
                            <text class="host__rank-text">{{ item.rank }}</text>
                        </view>
                    </view>
                    <view class="host__body">
                        <view class="host__name-row">
                            <text class="host__name">{{ item.name }}</text>
                            <view v-if="styleTag(item)" class="host__divider" />
                            <text v-if="styleTag(item)" class="host__style">{{ styleTag(item) }}</text>
                        </view>
                        <text v-if="metaText(item)" class="host__meta">{{ metaText(item) }}</text>
                        <view class="host__price">
                            <text class="host__price-value t-num">{{ priceText(item.price) }}</text>
                            <text
                                v-if="
                                    item.price !== undefined &&
                                    item.price !== null &&
                                    item.price !== ''
                                "
                                class="host__price-unit"
                            >
                                / 起
                            </text>
                        </view>
                    </view>
                </view>
            </view>
        </scroll-view>

        <!-- ============ 近期吉日 · 可约情况（深色卡） ============ -->
        <!-- 无吉日（含请求失败）时整块不渲染：只留一个标题比不显示更糟 -->
        <view v-if="luckyVisible" class="sec sec--gap">
            <section-header title="近期吉日 · 可约情况" align="end" flush />
        </view>

        <view v-if="luckyVisible && luckyState === 'ready'" class="darkcard">
            <!-- 年份进度细线：按真实系统日期算「今年已过 / 当年总天数」，闰年自然多一天 -->
            <view class="yearbar">
                <view class="yearbar__meta">
                    <text class="yearbar__label">YEAR · {{ currentYear }}</text>
                    <text class="yearbar__range t-num">01.01 — 12.31</text>
                </view>
                <view class="yearbar__track">
                    <view class="yearbar__fill" :style="{ width: `${yearProgress}%` }" />
                </view>
            </view>
            <view class="darkcard__top">
                <view class="darkcard__title-wrap">
                    <text v-if="luckyRangeText" class="darkcard__sub">{{ luckyRangeText }}</text>
                </view>
                <view hover-class="is-pressed" class="darkcard__link hit-88" @tap="goQuery">
                    <text class="darkcard__link-text">全部档期</text>
                    <tn-icon name="right" :size="22" />
                </view>
            </view>
            <view class="slot-row">
                <view
                    v-for="slot in luckySlots"
                    :key="slot.date"
                    hover-class="is-pressed"
                    class="slot"
                    @tap="goQueryWithDate(slot.date)"
                >
                    <text class="slot__date">{{ slot.date_text }}</text>
                    <text class="slot__week">{{ slot.weekday_text }}</text>
                    <!-- 剩余名额后端无字段（G-02），不写「剩 N 位」，只给可约/已满 -->
                    <text class="slot__state">{{ slot.remaining_text }}</text>
                </view>
            </view>
        </view>

        <view v-else-if="luckyVisible" class="darkcard darkcard--sk">
            <view class="darkcard__title-wrap">
                <view class="sk sk--sub" />
            </view>
            <view class="slot-row">
                <view v-for="n in 3" :key="n" class="slot slot--sk" />
            </view>
        </view>

        <!-- ============ 一次交付，全程在场（装修 home-flow 驱动） ============ -->
        <template v-if="flowItems.length">
            <view class="sec sec--gap">
                <section-header :title="flowTitle" align="end" flush />
            </view>
            <view class="promise">
                <fulfill-timeline :items="flowItems" />
            </view>
        </template>

        <!-- ============ 页脚 ============ -->
        <view class="foot">
            <view class="foot__rule" />
            <!-- 品牌锁定行：优先用后台 Slogan，没配就退回品牌名，永远不留空白 -->
            <text class="foot__text">{{ brand.slogan || brand.name }}</text>
        </view>

        <app-toast />

        <!-- 首页弹窗广告：数据来自装修组件 home-popup-ad，频次/时机由组件内部按契约判定 -->
        <home-popup-ad v-if="popupAd" :content="popupAd" :show-key="homeShowKey" />
    </page-shell>

    <!-- 冷启动开屏层：首页作为 pages[0] 立即开始加载，广告层只盖在最上面，
         不再从独立开屏页 switchTab 过来（那条链路实测多等约 10 秒）。 -->
    <splash-screen v-if="splashVisible" @done="splashVisible = false" />
</template>

<script setup lang="ts">
/**
 * 01 首页。
 *
 * 数据口径（本屏最容易犯错的地方）：
 *  - 数据条（场次 / 好评 / 覆盖城市）**只能来自接口** `home-brand` widget 的 stats；
 *    接口没下发就整块不渲染 —— 设计稿里的「1,280 场 / 98% / 32 城」是设计样例数字，不是可写死的常量。
 *  - 首屏图上文字（宣传语 / 主标题 / 服务范围 / 主次按钮）全部来自装修 `banner.data[0]`，
 *    留空回落到 `HOME_HERO_FALLBACK`（与后台装修预览同源，避免"预览一套、真机一套"）。
 *  - 服务承诺走装修 `home-flow`；widget 缺失时用 `HOME_FLOW_DEFAULTS`，视觉与改造前一致。
 *  - 吉日卡走 `schedule.luckyDays`（独立请求）；
 *    后端没有「剩余名额」字段（G-02），所以不显示「剩 N 位」，只给「可约 / 已满」。
 *  - 本期推荐与吉日卡：**无数据（含请求失败）时整块不渲染**，连 section-header 一起隐藏。
 */
import { computed, onUnmounted, reactive, ref, watch } from 'vue'
import { onLoad, onReady, onShareAppMessage, onShow } from '@dcloudio/uni-app'
import {
    HOME_FLOW_DEFAULTS,
    HOME_SERVICE_CATEGORIES_DEFAULTS,
    findWidget,
    normalizeHomeBrandContent,
    normalizeHomeFlowContent,
    normalizeHomeLuckyDaysContent,
    normalizeHomeRecommendContent,
    normalizeHomeServiceCategoriesContent,
    parseDecoratePage,
    resolveHomeHeroHeight,
    resolveHomeHeroItem,
    resolveHomeHeroText,
    type DecorateLink,
    type HomeServiceCategorySize,
    type HomeServiceCategoryTextAlign,
    type HomeServiceCategoryTextPosition
} from '@shared/contracts'
import { formatMoney } from '@shared/utils/format'
import { getHome, getIndex, getConfig } from '@/api/home'
import { getLuckyDays } from '@/api/catalog'
import { useAppStore } from '@/stores/app'
import { useBrand } from '@/composables/useBrand'
import { useTabQueryStore } from '@/stores/tabQuery'
import { useBadgeStore } from '@/stores/badge'
import { isDecorateLinkConfigured, isWechatContactLink, navigate } from '@/utils/link'
import { buildShare } from '@/utils/share'
import { report } from '@/platform/report'
import { getDeviceInfo, getNavBarHeight } from '@/platform/device'
import { bootTrace } from '@/utils/boot-trace'
import { claimSplashEntry } from '@/utils/splash'

/** 页面 setup 已执行（用于区分「JS 晚到」还是「框架延迟调 onLoad」） */
bootTrace('index:setup')

interface HomeQuick {
    /** 后台下发的入口没有固定枚举，这里放宽成字符串；内置兜底仍与契约默认值同源 */
    key: string
    title: string
    subtitle: string
    image: string
    /** 版式：大图占两行 / 小图标准格 / 通栏占两列 */
    size: HomeServiceCategorySize
    /** 卡片文字的纵向位置 */
    textPosition: HomeServiceCategoryTextPosition
    /** 卡片文字的水平对齐 */
    textAlign: HomeServiceCategoryTextAlign
    /** 单卡自定义高度（rpx）；0 = 跟随版式默认 */
    height: number
    link: DecorateLink
}

interface LuckySlot {
    date: string
    date_text: string
    weekday_text: string
    remaining_text: string
}

const appStore = useAppStore()
const tabQuery = useTabQueryStore()
const badgeStore = useBadgeStore()
/** 仅本进程首次冷启动展示开屏；登录回跳 / reLaunch 不再重复盖层 */
const splashVisible = ref(claimSplashEntry())
/** 品牌（名称 / 图标 / Slogan）唯一来源：后台配置 → 缓存 → 兜底，页面不写死任何一个 */
const { brand, refresh: refreshBrand } = useBrand()

/**
 * 四宫格兜底：装修 home-service-categories 没配（或全被隐藏）时用这四条。
 * 与契约 HOME_SERVICE_CATEGORIES_DEFAULTS 同源（同文案、同路由、同版式），
 * 保证「装修没配过」和「配了默认值」看到的是同一屏。配置存在时以配置为准，见 quickEntries。
 */
const QUICK_FALLBACK: HomeQuick[] = HOME_SERVICE_CATEGORIES_DEFAULTS.data.map(
    (item, index) => ({
        key: `fallback-${index}`,
        title: item.title,
        subtitle: item.subtitle,
        image: item.image,
        size: item.size,
        textPosition: item.text_position,
        textAlign: item.text_align,
        height: item.height,
        link: item.link
    })
)

const loadingHome = ref(true)

const page = reactive({ widgets: [] as any[], meta: {} as any, skipped: [] as string[] })
const staff = ref<any[]>([])

/**
 * home-brand widget：数据条唯一来源。
 * 注意变量名不要叫 `brand` —— 品牌配置（useBrand）已经占用了这个名字，
 * 两者语义不同：这里指「首页装修里的数据条组件」。
 * 归一化后固定 3 格；关闭或三格全空时整块不渲染（见模板 v-if）。
 */
const brandContent = computed(() =>
    normalizeHomeBrandContent(findWidget<any>(page as any, 'home-brand'))
)

const stats = computed<Array<{ value: string; label: string }>>(() => {
    if (brandContent.value.enabled !== 1) return []
    return brandContent.value.stats
        .filter((s) => !!s.value.trim())
        .slice(0, 3)
        .map((s) => ({ value: s.value, label: s.label }))
})

/** 首页弹窗广告：装修组件 home-popup-ad 的 content，未配置时为 null（不挂载组件） */
const popupAd = computed<any>(() => findWidget<any>(page as any, 'home-popup-ad'))

/**
 * 首屏主图 / 文案：装修 banner 的 data[0]（服务端保证只有 1 项）。
 * `resolveHomeHeroItem` 统一处理「组件关闭 / 老数据 is_show='0'」→ null，
 * 页面回落到内置主图与 HOME_HERO_FALLBACK 文案。
 */
const bannerItem = computed(() => resolveHomeHeroItem(findWidget<any>(page as any, 'banner')))

/**
 * 首屏主图高度（rpx）：装修可配置（600–1400），缺省 1054 与设计稿一致。
 * 与后台整页预览共用 resolveHomeHeroHeight，组件缺失或关闭时回落默认值。
 */
const heroHeight = computed(() => resolveHomeHeroHeight(findWidget<any>(page as any, 'banner')))

/**
 * 首页导航「滚过 Hero 即实底」的阈值（px）。
 *
 * Hero 高度装修可配（rpx）。数据条 .stats 用 margin-top: -65rpx 叠压 Hero 底部，
 * 所以主图实际露出的高度 = heroHeight - 65rpx。换算成 px 再扣掉导航总高
 * （状态栏 + 胶囊行），才是「导航下边缘刚好离开主图」的滚动距离。
 * 下限 40px 兜底：装修把 Hero 配到极矮时不至于阈值贴 0、一碰就闪实底。
 */
const navTotalPx = getNavBarHeight()
const navSolidThreshold = computed(() =>
    Math.max(40, Math.round(uni.upx2px(Math.max(0, heroHeight.value - 65)) - navTotalPx))
)

/**
 * 传给 nav-bar 的 scrollTop 只有两档：0 / 阈值+1。
 * nav-bar 判实底只看「> solidThreshold」，连续值没有意义 ——
 * 每帧写响应式变量会触发 mp-weixin 的 setData，滚动会被拖慢。
 * 只在跨越阈值时写一次（回顶时写回 0）。
 */
const navScrollTop = ref(0)
let rawScrollTop = 0

function syncNavScroll(top: number) {
    rawScrollTop = top
    const next = top > navSolidThreshold.value ? navSolidThreshold.value + 1 : 0
    if (navScrollTop.value !== next) navScrollTop.value = next
}

/* ---- 轻量视差（§3.8）：不挂 WXS（page-shell 的 scroll-view 在组件内部，页面拿不到节点），
        改在 @scroll 里节流写入：钳制在阈值内、步进 ≥ 4px 才更新，减少 mp-weixin setData ---- */
const prefersReducedMotion = (() => {
    try {
        return !!(globalThis as any).matchMedia?.('(prefers-reduced-motion: reduce)')?.matches
    } catch {
        return false
    }
})()

/** 低端机（benchmarkLevel 1–14）关闭视差：滚动中反复写样式不值得 */
const lowEndDevice = (() => {
    try {
        const level = Number((uni as any).getDeviceInfo?.()?.benchmarkLevel)
        return Number.isFinite(level) && level > 0 && level < 15
    } catch {
        return false
    }
})()

const parallaxEnabled = !prefersReducedMotion && !lowEndDevice
const heroMediaY = ref(0)
const heroCopyY = ref(0)
const heroCopyOpacity = ref(1)
let lastParallaxStep = -1

const heroMediaStyle = computed(() => ({
    transform: `translate3d(0, ${heroMediaY.value}px, 0) scale(${parallaxEnabled ? 1.12 : 1})`
}))

const heroCopyStyle = computed(() => ({
    transform: `translate3d(0, ${heroCopyY.value}px, 0)`,
    opacity: String(heroCopyOpacity.value)
}))

function applyParallax(top: number) {
    if (!parallaxEnabled) return
    const clamped = Math.min(Math.max(top, 0), navSolidThreshold.value)
    const step = Math.round(clamped / 4)
    if (step === lastParallaxStep) return
    lastParallaxStep = step
    const y = step * 4
    // 图片位移锁在 scale(1.12) 的裁剪余量内，绝不露出下面的常暗底
    const crop = uni.upx2px(heroHeight.value) * 0.055
    heroMediaY.value = Math.min(Math.round(y * 0.35), Math.round(crop))
    heroCopyY.value = Math.round(y * 0.12)
    heroCopyOpacity.value = Math.max(0.3, 1 - y / 320)
}

function onPageScrollTop(top: number) {
    syncNavScroll(top)
    applyParallax(top)
}

// 装修数据（banner 高度）晚到时阈值会变，用最后一次滚动位置重算一次，
// 否则导航会停在按旧阈值判定的状态（该实底没实底 / 该透明却实底）
watch(navSolidThreshold, () => syncNavScroll(rawScrollTop))

/** 仅在滚动切为实底导航时展示站点名称（取自 useBrand 动态配置，首屏 Hero 透明态不展示） */
const navTitle = computed(() =>
    navScrollTop.value > navSolidThreshold.value ? brand.value.name : ''
)

/** 内置首屏主图：仅在装修未配置时使用 */
const HERO_FALLBACK_IMAGE = '/static/images/hero-ceremony.jpg'

const heroImage = computed(() => imageUrl(bannerItem.value?.image) || HERO_FALLBACK_IMAGE)
const heroSloganColor = computed(() => String(bannerItem.value?.slogan_color || ''))

/**
 * 图片上浮文字：开关 + 兜底文案 + 样式一次性解析。
 * 与后台装修预览调用同一个纯函数（resolveHomeHeroText），杜绝"预览一套、真机一套"。
 */
const heroText = computed(() =>
    resolveHomeHeroText(bannerItem.value, {
        brandName: brand.value.name,
        brandSlogan: brand.value.slogan
    })
)
const heroTitleColor = computed(() => heroText.value.style.title_color)
const heroSubtitleColor = computed(() => heroText.value.style.subtitle_color)

const DEFAULT_HERO_PRIMARY_LINK: DecorateLink = {
    path: '/pages/query/query',
    type: 'shop',
    name: '档期查询'
}
const DEFAULT_HERO_SECONDARY_LINK: DecorateLink = {
    path: '/pages/about/about',
    type: 'shop',
    name: '关于我们'
}

const heroPrimaryLink = computed<DecorateLink>(() =>
    isDecorateLinkConfigured(bannerItem.value?.primary_link)
        ? bannerItem.value!.primary_link!
        : DEFAULT_HERO_PRIMARY_LINK
)
const heroSecondaryLink = computed<DecorateLink>(() =>
    isDecorateLinkConfigured(bannerItem.value?.secondary_link)
        ? bannerItem.value!.secondary_link!
        : DEFAULT_HERO_SECONDARY_LINK
)

/**
 * 四宫格图文入口：装修 home-service-categories 的 data。
 * 组件缺失（装修没下发）才回落 QUICK_FALLBACK；显式关闭或删空时整排隐藏。
 */
const quickEntries = computed<HomeQuick[]>(() => {
    const raw = findWidget<any>(page as any, 'home-service-categories')
    // 组件缺失（装修没下发）→ 用内置四条，保证首屏结构不塌
    if (!raw) return QUICK_FALLBACK
    // 显式关闭 → 整块隐藏，不能回落成内置四条（否则「显示本区块」开关无效）
    const content = normalizeHomeServiceCategoriesContent(raw)
    if (content.enabled !== 1) return []

    return content.data
        .filter((item) => item.is_show === '1')
        .map((item, index) => {
            const title = item.title.trim()
            if (!title || !isDecorateLinkConfigured(item.link)) return null
            return {
                key: `cat-${index}`,
                title,
                subtitle: item.subtitle,
                image: imageUrl(item.image),
                size: item.size,
                textPosition: item.text_position,
                textAlign: item.text_align,
                height: item.height,
                link: item.link
            } as HomeQuick
        })
        .filter((item: HomeQuick | null): item is HomeQuick => !!item)
})

/** 本期推荐显隐开关：装修 home-recommend.enabled，缺省显示 */
const recommendEnabled = computed(
    () => normalizeHomeRecommendContent(findWidget<any>(page as any, 'home-recommend')).enabled === 1
)

/**
 * 服务承诺：装修 home-flow 驱动。
 * widget 缺失时 normalizeHomeFlowContent 返回契约默认值（克隆），
 * 所以升级后即使后台还没保存过，视觉也与改造前完全一致。
 */
const flowContent = computed(() => normalizeHomeFlowContent(findWidget<any>(page as any, 'home-flow')))
const flowTitle = computed(() => flowContent.value.title || HOME_FLOW_DEFAULTS.title)
const flowItems = computed(() => {
    if (flowContent.value.enabled !== 1) return []
    return flowContent.value.data
        .filter((item) => item.is_show === '1' && String(item.title || '').trim())
        .map((item) => ({ label: item.tag, title: item.title, desc: item.desc }))
})

/**
 * 数据条文案拆成「静态前后缀 + 数值」：只有能拆出真实数字时才交给滚动数字，
 * 拆不出来（例如「—」占位、纯文字）就原样显示，绝不补 0。
 */
interface StatDisplay {
    prefix: string
    number: number
    suffix: string
    decimals: number
    thousands: boolean
}

function parseStat(raw: string): StatDisplay | null {
    const text = String(raw || '').trim()
    if (!text) return null
    const hit = /^([^\d-]*?)(-?\d[\d,]*(?:\.\d+)?)(.*)$/.exec(text)
    if (!hit) return null
    const number = Number(hit[2].replace(/,/g, ''))
    if (!Number.isFinite(number)) return null
    return {
        prefix: hit[1] || '',
        number,
        suffix: hit[3] || '',
        decimals: hit[2].includes('.') ? hit[2].split('.')[1].length : 0,
        thousands: hit[2].includes(',')
    }
}

/** 加载中显示「—」占位，避免先闪 0 再跳数字 */
const statsToShow = computed(() => {
    const rows = stats.value.length
        ? stats.value
        : [
              { value: '—', label: '' },
              { value: '—', label: '' },
              { value: '—', label: '' }
          ]
    return rows.map((row) => ({ ...row, stat: parseStat(row.value) }))
})

/**
 * 四宫格版式：装修明确 grid 才均分双列；缺省（含组件缺失）按契约默认 bento 拼版。
 * 契约字段是 `layout: 'bento' | 'grid'`，这里不另造第二套枚举。
 */
const quickLayout = computed<'bento' | 'grid'>(() => {
    const raw = findWidget<any>(page as any, 'home-service-categories')
    if (!raw) return 'bento'
    const content = normalizeHomeServiceCategoriesContent(raw)
    return content.enabled === 1 ? content.layout : 'bento'
})

const isBento = computed(() => quickLayout.value === 'bento')

function tileClassOf(entry: HomeQuick) {
    return [
        isBento.value ? `tile--${entry.size}` : '',
        {
            'tile--no-image': !entry.image,
            'is-pressing': pressKey.value === entry.key,
            'is-rebound': reboundKey.value === entry.key
        }
    ]
}

/** 单卡高度（rpx）：0 = 跟随版式默认；正数覆盖并让相邻行自动撑开 */
function tileStyleOf(entry: HomeQuick, index: number) {
    const style: Record<string, string | number> = { '--i': index }
    if (entry.height > 0) {
        style.height = `${entry.height}rpx`
        style.minHeight = '0'
        style.alignSelf = 'start'
    }
    return style
}

/* ---- 两段式按压（§3.1）：宫格 / 推荐卡 / Hero 主按钮共用同一套 key 状态 ---- */
const pressKey = ref('')
const reboundKey = ref('')
const PRESS_MIN_MS = 75
const RELEASE_MS = 440
let pressStartAt = 0
let pressTimer: ReturnType<typeof setTimeout> | null = null
let reboundTimer: ReturnType<typeof setTimeout> | null = null

function clearPressTimers() {
    if (pressTimer) {
        clearTimeout(pressTimer)
        pressTimer = null
    }
    if (reboundTimer) {
        clearTimeout(reboundTimer)
        reboundTimer = null
    }
}

function onPressStart(key: string) {
    clearPressTimers()
    reboundKey.value = ''
    pressStartAt = Date.now()
    pressKey.value = key
}

function onPressEnd() {
    if (!pressKey.value) return
    const key = pressKey.value
    const rest = Math.max(0, PRESS_MIN_MS - (Date.now() - pressStartAt))
    if (pressTimer) clearTimeout(pressTimer)
    pressTimer = setTimeout(() => {
        pressTimer = null
        pressKey.value = ''
        reboundKey.value = key
        reboundTimer = setTimeout(() => {
            reboundKey.value = ''
            reboundTimer = null
        }, RELEASE_MS)
    }, rest)
}

function onPressCancel() {
    clearPressTimers()
    pressKey.value = ''
    reboundKey.value = ''
}

const heroPressing = computed(() => pressKey.value === 'hero-cta')
const heroRebound = computed(() => reboundKey.value === 'hero-cta')

function onHeroPressStart() {
    onPressStart('hero-cta')
}

function onHeroPressEnd() {
    onPressEnd()
}

function onHeroPressCancel() {
    onPressCancel()
}

function onHeroPrimary() {
    goQuery()
}

/* ---- 宫格错峰入场（§3.3）：首帧先挂 .stagger，下一拍再放 .is-in，动画才有起点 ---- */
const quickStaggerOn = ref(false)
const quickStaggerIn = ref(false)
let staggerTimer: ReturnType<typeof setTimeout> | null = null

function playQuickStagger() {
    if (!quickEntries.value.length || quickStaggerIn.value) return
    quickStaggerOn.value = true
    if (staggerTimer) return
    staggerTimer = setTimeout(() => {
        staggerTimer = null
        quickStaggerIn.value = true
    }, 40)
}

watch(
    () => quickEntries.value.length,
    (len) => {
        if (len) playQuickStagger()
    }
)

/* ---- 推荐横滑中心聚焦（§3.9）：@scroll 90ms 节流，按卡宽 + gap 反推中线序号 ---- */
const hostFocus = ref(0)
let hostScrollTimer: ReturnType<typeof setTimeout> | null = null

function onHostsScroll(e: any) {
    if (hostScrollTimer) return
    const left = Number(e?.detail?.scrollLeft) || 0
    hostScrollTimer = setTimeout(() => {
        hostScrollTimer = null
        const cardW = uni.upx2px(300)
        const gap = uni.upx2px(24)
        const pad = uni.upx2px(42)
        const viewW = Number(getDeviceInfo().windowWidth) || 375
        const index = Math.round((left + viewW / 2 - pad - cardW / 2) / (cardW + gap))
        const max = Math.max(0, staff.value.length - 1)
        hostFocus.value = Math.min(Math.max(index, 0), max)
    }, 90)
}

/* ---- 年份进度（真实系统日期，闰年按当年真实天数） ---- */
const today = ref(new Date())
const currentYear = computed(() => today.value.getFullYear())
const yearProgress = computed(() => {
    const now = today.value
    const start = new Date(now.getFullYear(), 0, 1).getTime()
    const end = new Date(now.getFullYear() + 1, 0, 1).getTime()
    const total = end - start
    if (total <= 0) return 0
    const percent = ((now.getTime() - start) / total) * 100
    return Math.min(100, Math.max(0, Math.round(percent * 10) / 10))
})

onUnmounted(() => {
    clearPressTimers()
    if (staggerTimer) clearTimeout(staggerTimer)
    if (hostScrollTimer) clearTimeout(hostScrollTimer)
})

const luckyState = ref<'loading' | 'ready' | 'error'>('loading')
const luckySlots = ref<LuckySlot[]>([])
const luckyRangeText = ref('')

/** 近期吉日显隐开关：装修 home-lucky-days.enabled，缺省显示 */
const luckyDaysEnabled = computed(
    () => normalizeHomeLuckyDaysContent(findWidget<any>(page as any, 'home-lucky-days')).enabled === 1
)

/** 吉日整块可见：加载中出骨架，成功且有数据出卡；空数据与请求失败都不渲染 */
const luckyVisible = computed(
    () =>
        luckyDaysEnabled.value &&
        !loadingHome.value &&
        (luckyState.value === 'loading' ||
            (luckyState.value === 'ready' && luckySlots.value.length > 0))
)

function imageUrl(url?: string) {
    if (!url) return ''
    if (/^https?:/.test(url)) return url
    const domain = (appStore.config && (appStore.config as any).domain) || ''
    return domain ? `${domain}${url}` : url
}

/** 风格标签：接口 tags 是字符串数组，取第一个；没有就不显示分隔线 */
function styleTag(item: any) {
    return Array.isArray(item.tags) && item.tags.length ? String(item.tags[0]) : ''
}

/** 卡片副信息：评分 / 评价数按接口字段拼，缺哪个少哪个，不补假数 */
function metaText(item: any) {
    const parts: string[] = []
    if (item.rating !== undefined && item.rating !== null && item.rating !== '') {
        parts.push(`评分 ${item.rating}`)
    }
    if (Number(item.order_count) > 0) parts.push(`${item.order_count} 场服务`)
    return parts.join(' · ')
}

function priceText(price: any) {
    if (price === undefined || price === null || price === '') return '价格待定'
    const num = Number(price)
    if (!Number.isFinite(num)) return '价格待定'
    return `¥${formatMoney(num, { thousand: true, trimZeros: true, decimals: 0 })}`
}

// ---------------------------------------------------------------- 数据加载

async function loadHome() {
    loadingHome.value = true
    try {
        let pageData: { data: any; meta: any } | null = null
        try {
            const home = await getHome()
            pageData = home.page
            staff.value = Array.isArray(home.staff_recommend) ? home.staff_recommend : []
            // 品牌等字段并进全局配置（不覆盖已有值，也不把「完整配置已加载」置真）
            appStore.applyConfig(home.config as any)
        } catch {
            // 降级：老接口（v2 未上线时）
            const [index, config] = await Promise.all([getIndex(), getConfig().catch(() => null)])
            pageData = (index && index.page) || null
            staff.value = []
            appStore.applyConfig(config)
        }

        if (!pageData) throw new Error('empty page')

        const parsed = parseDecoratePage(pageData.data, pageData.meta)
        page.widgets = parsed.widgets
        page.meta = parsed.meta
        page.skipped = parsed.skipped
    } catch {
        // 首屏静态结构（Hero / 快捷入口 / 服务承诺）不依赖接口，失败只清空接口内容
        staff.value = []
    } finally {
        loadingHome.value = false
        bootTrace('index:home settled', { staff: staff.value.length })
    }
}

function pad2(n: number) {
    return n < 10 ? `0${n}` : String(n)
}

function toDateString(d: Date) {
    return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`
}

/** 吉日卡：取「本月起两个月」区间；不传 year/month 会更短，这里明确给区间 */
async function loadLucky() {
    luckyState.value = 'loading'
    const now = new Date()
    const start = new Date(now.getFullYear(), now.getMonth(), now.getDate())
    const end = new Date(now.getFullYear(), now.getMonth() + 2, 0) // 下个月最后一天
    try {
        const rows = await getLuckyDays({
            start_date: toDateString(start),
            end_date: toDateString(end),
            marriage_only: 1
        })
        const list = Array.isArray(rows) ? rows : []
        luckySlots.value = list.slice(0, 3).map((row: any) => {
            const date = String(row.event_date || '')
            const d = new Date(`${date}T00:00:00`)
            const valid = !Number.isNaN(d.getTime())
            const week = valid
                ? ['周日', '周一', '周二', '周三', '周四', '周五', '周六'][d.getDay()]
                : ''
            return {
                date,
                date_text: valid ? `${pad2(d.getMonth() + 1)}.${pad2(d.getDate())}` : date,
                weekday_text: week,
                // 后端只有 is_lucky_day（本接口已过滤吉日），没有剩余名额 → 只写「可约」
                remaining_text: '可约'
            }
        })
        const months = [
            start.getMonth() + 1,
            start.getMonth() + 2 > 12 ? start.getMonth() + 2 - 12 : start.getMonth() + 2
        ]
        luckyRangeText.value = `${months[0]} 月 – ${months[1]} 月`
        luckyState.value = 'ready'
    } catch {
        luckyState.value = 'error'
    }
}

function loadAll() {
    loadHome()
    loadLucky()
}

// ---------------------------------------------------------------- 跳转

function onQuick(entry: HomeQuick) {
    navigate(entry.link)
}

function goQuery() {
    navigate(heroPrimaryLink.value)
}

/** 点吉日格带日期进 03。
 *  不能走 URL：navigate() 对 tab 页用 switchTab，query 会被丢弃（utils/link.ts）。
 *  所以用 tabQuery 中转，由 query.vue 的 onLoad/onShow 消费。
 */
function goQueryWithDate(date: string) {
    if (!date) {
        navigate({ path: '/pages/query/query' })
        return
    }
    tabQuery.setQuery('query', { date })
    navigate({ path: '/pages/query/query' })
}

function goStaffDetail(id: number) {
    navigate({ path: '/packages/pkg-catalog/pages/staff-detail/staff-detail', query: { id } })
}

function goAbout() {
    navigate(heroSecondaryLink.value)
}

/**
 * 小程序端「系统标题」= 品牌名。
 *
 * 自定义导航下它不显示，但微信「右上角 → 转发」的默认卡片标题取的就是页面标题；
 * 不同步的话，转发出去还是 pages.json 里构建期写死的值。
 * onShow 保证页面已就绪（onLaunch 阶段 setNavigationBarTitle 会 fail），
 * watch 保证配置晚到 / 下拉刷新换了名称时能再同步一次。
 */
function syncNavTitle() {
    const name = brand.value.name
    if (!name) return
    uni.setNavigationBarTitle({ title: name, fail: () => undefined })
}

/**
 * 每次进入首页自增：tab 页不会重新挂载，弹窗广告只靠 onMounted 判定的话，
 * 「每次进入首页」这个频次实际只会生效一次。用这个 key 驱动重新判定。
 */
const homeShowKey = ref(0)

onShow(() => {
    bootTrace('index:onShow')
    syncNavTitle()
    homeShowKey.value += 1
    // 年份进度线跟着真实日期走：跨天/跨年后回到首页要重算
    today.value = new Date()
    // tab 切换不重新挂载页面，角标只能靠 onShow 刷新（store 内 30s 节流，切 tab 不会打接口）
    badgeStore.refresh()
})
watch(() => brand.value.name, syncNavTitle)

onLoad(() => {
    bootTrace('index:onLoad')
    loadAll()
    // 装修解析异常只是降级，不阻断首屏；落一次上报便于排查
    if (page.skipped.length)
        report('page_error', { message: `首页跳过装修组件：${page.skipped.join(',')}` })
})

onReady(() => {
    bootTrace('index:onReady')
    // 装修数据可能晚于 onReady，watch 会补播；这里先跑一次保证首屏有数据时也有入场
    playQuickStagger()
})

function onRefresh(done: () => void) {
    // 顺带强制刷新品牌配置：运营在后台改完名称/图标，下拉一次就能验活，不用杀进程重进
    Promise.all([loadHome(), loadLucky(), refreshBrand().catch(() => undefined)]).finally(done)
}

/**
 * 首页转发。
 * 标题 / 封面缺省值统一由品牌配置补（buildShare），页面里不写死品牌名。
 */
onShareAppMessage(() =>
    buildShare({
        path: '/pages/index/index',
        suffix: '婚礼主持'
    })
)
</script>

<style lang="scss" scoped>
/* ---------------- Hero ---------------- */
.hero {
    position: relative;
    height: 1054rpx; /* 设计稿 548px */
    padding: 0 42rpx; /* 设计稿 22px */
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    overflow: hidden;
    /* 主图解码前先垫常暗底：否则这一帧会露出页面底色，看起来就是「启动白屏」 */
    background: var(--c-dark-2);

    &__media {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        width: 100%;
        height: 100%;
        transform-origin: center 35%;
        will-change: transform;
    }

    /* 顶部暖墨融合：从品牌暖底渐隐到透明，压住主图上沿，状态栏不再是硬切边 */
    &__fade {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 32%;
        background: linear-gradient(
            180deg,
            rgba(var(--c-bg-rgb), 0.55) 0%,
            rgba(var(--c-bg-rgb), 0) 100%
        );
        pointer-events: none;
    }

    /* 遮罩：--c-ink（#181614）的透明度阶梯。渐变无法用 hex 变量算 alpha，
       与 tokens.css 的 --c-mask 同一取值来源；此处是唯一允许出现 rgba 数值的场景（图片压字必须有遮罩） */
    &__scrim {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(
            180deg,
            rgba(24, 22, 20, 0.58) 0%,
            rgba(24, 22, 20, 0.16) 26%,
            rgba(24, 22, 20, 0.48) 58%,
            rgba(24, 22, 20, 0.94) 100%
        );
    }

    &__copy {
        position: relative;
        z-index: 2;
        padding-bottom: 100rpx; /* 设计稿 52px */
        will-change: transform, opacity;
    }

    &__eyebrow {
        display: block;
        font-family: var(--font-serif);
        font-size: var(--fs-xs);
        letter-spacing: 0.24em;
        color: var(--c-brand-soft);
    }

    &__title {
        margin-top: 27rpx; /* 设计稿 14px */
    }

    &__title-line {
        display: block;
        font-size: var(--fs-52); /* 设计稿 27px */
        line-height: var(--lh-body);
        font-weight: var(--fw-medium);
        color: var(--c-on-dark);
        letter-spacing: 0.01em;
    }

    &__scope {
        display: block;
        margin-top: 23rpx; /* 设计稿 12px */
        font-size: var(--fs-xs);
        letter-spacing: 0.08em;
        color: var(--c-on-dark-2);
    }

    &__cta {
        display: flex;
        align-items: center;
        gap: 34rpx; /* 设计稿 18px */
        margin-top: 50rpx; /* 设计稿 26px */
    }

    &__primary {
        height: 88rpx; /* 88rpx 最小可点热区（设计稿 44px） */
        /* 图标侧（右）比文字侧窄 12rpx：图标视觉重心才落在胶囊内 */
        padding: 0 34rpx 0 46rpx;
        border-radius: var(--r-pill);
        background: var(--c-hero-cta-bg);
        display: inline-flex;
        align-items: center;
        gap: 12rpx;
        /* 图标与按钮文字同色，由父级继承（tn-icon 不认 var()） */
        color: var(--c-on-brand);
        transform-origin: center center;

        &--btn {
            margin: 0;
            border: none;
            line-height: 1;
            font-size: inherit;
            &::after {
                border: none;
            }
        }
    }

    &__primary-text {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        letter-spacing: 0.04em;
        color: var(--c-on-brand);
    }

    /* 次行动降为文字链：首屏只有一个主行动 */
    &__ghost {
        height: 88rpx;
        display: inline-flex;
        align-items: center;
        padding: 0 8rpx;
        font-size: var(--fs-xs);
        letter-spacing: 0.06em;
        color: var(--c-on-dark);
        border-bottom: 1rpx solid var(--c-on-dark-2);

        &--btn {
            margin: 0;
            background: transparent;
            border-top: none;
            border-left: none;
            border-right: none;
            border-radius: 0;
            line-height: 1;
            &::after {
                border: none;
            }
        }
    }
}

/* ---------------- 数据条 ---------------- */
.stats {
    position: relative;
    z-index: 6;
    margin: -65rpx 38rpx 0; /* 设计稿 -34px 叠压 / 左右 20px */
    border-radius: var(--r-md);
    display: flex;
    align-items: center;
    padding: 35rpx 12rpx; /* 设计稿 18px 6px */

    /* 实底：契约 style='card' —— 暖白卡片 + 描边 + 暖影 */
    &--solid {
        background: var(--c-surface);
        border: 1rpx solid var(--c-card-border);
        box-shadow: var(--shadow-card);
    }

    /* 磨砂：契约 style='glass' —— 叠压首屏时默认材质 */
    &--glass {
        background: rgba(var(--c-surface-rgb), 0.72);
        border: 1rpx solid var(--c-card-border);
        -webkit-backdrop-filter: blur(18px) saturate(1.1);
        backdrop-filter: blur(18px) saturate(1.1);
        box-shadow: var(--shadow-glass);
    }

    &__item {
        flex: 1;
        text-align: center;
        position: relative;

        & + &::before {
            content: '';
            position: absolute;
            left: 0;
            top: 12rpx;
            bottom: 12rpx;
            width: 1rpx;
            background: var(--c-line);
        }
    }

    /* 数字用滚动组件时也要保住基线：4ch 锁宽，滚到 1 位数字时不左右跳 */
    &__value {
        display: flex;
        align-items: baseline;
        justify-content: center;
        min-width: 4ch;
        font-family: var(--font-serif);
        font-size: var(--fs-40); /* 设计稿 21px */
        font-weight: var(--fw-medium);
        color: var(--c-ink);
        letter-spacing: 0.01em;
        line-height: var(--lh-tight);
    }

    &__label {
        display: block;
        margin-top: 10rpx; /* 设计稿 5px */
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        letter-spacing: 0.06em;
    }
}

/* 不支持毛玻璃的基础库：提高不透明度保住文字对比度，仍保留浮层质感 */
@supports not ((backdrop-filter: blur(2px)) or (-webkit-backdrop-filter: blur(2px))) {
    .stats--glass {
        background: rgba(var(--c-surface-rgb), 0.96);
    }
}

/* ---------------- 四宫格 / 编辑部拼版 ---------------- */
.tile-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    align-items: start;
    gap: 18rpx;
    padding: 36rpx 42rpx 0;

    /* 均分双列（layout='grid'）：所有入口同尺寸，不跨行跨列；行内 entry.height 仍可覆写 */
    &--grid .tile {
        grid-row: span 1;
        grid-column: span 1;
        align-self: start;
        height: 168rpx;
        min-height: 0;
    }
}

.tile {
    position: relative;
    min-width: 0;
    height: 168rpx;
    overflow: hidden;
    border-radius: var(--r-md);
    background: var(--c-neutral-bg);
    box-shadow: var(--shadow-card);
    box-sizing: border-box;

    &--large {
        grid-row: span 2;
        align-self: stretch;
        height: 100%;
        min-height: 354rpx;
    }

    &--wide {
        grid-column: span 2;
    }

    &--no-image {
        background: linear-gradient(135deg, var(--c-dark-1) 0%, var(--c-dark-2) 100%);
    }

    &--btn {
        display: block;
        width: 100%;
        margin: 0;
        padding: 0;
        text-align: left;
        line-height: inherit;
        font-size: inherit;
        &::after {
            border: none;
        }
    }

    &__image {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: block;
    }

    &__scrim {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        background: linear-gradient(
            180deg,
            rgba(24, 22, 20, 0.2) 0%,
            rgba(24, 22, 20, 0.68) 100%
        );
        pointer-events: none;
    }

    &__copy {
        position: absolute;
        left: 24rpx;
        right: 24rpx;
        z-index: 2;
        display: flex;
        flex-direction: column;
        gap: 8rpx;

        &--top {
            top: 24rpx;
        }

        &--middle {
            top: 50%;
            transform: translateY(-50%);
        }

        &--bottom {
            bottom: 22rpx;
        }

        &--align-left {
            align-items: flex-start;
            text-align: left;
        }

        &--align-center {
            align-items: center;
            text-align: center;
        }

        &--align-right {
            align-items: flex-end;
            text-align: right;
        }
    }

    &__title {
        max-width: 100%;
        font-size: var(--fs-30);
        line-height: var(--lh-tight);
        font-weight: var(--fw-semibold);
        color: var(--c-on-dark);
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        word-break: break-word;
    }

    &__subtitle {
        max-width: 100%;
        font-size: var(--fs-18);
        line-height: var(--lh-tight);
        font-weight: var(--fw-medium);
        letter-spacing: 0.06em;
        color: var(--c-brand-soft);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__line {
        width: 42rpx;
        height: 2rpx;
        border-radius: var(--r-pill);
        background: var(--c-brand);
    }
}

/* ---------------- 区块间距 ---------------- */
.sec {
    padding: 0 42rpx;
    margin-top: 50rpx; /* 设计稿 26px */

    &--gap {
        margin-top: 54rpx; /* 设计稿 28px */
    }
}

/* ---------------- 推荐位（横滑竖版卡） ---------------- */
.hosts {
    width: 100%;
    white-space: nowrap;
    scroll-snap-type: x mandatory;
}

.hosts__track {
    display: inline-flex;
    align-items: stretch;
    gap: 24rpx;
    /* 上下留出缓冲：横滑时手指容易带出纵向位移，卡片不再贴着上下边界 */
    padding: 12rpx 42rpx 28rpx;
    vertical-align: top;
}

.host {
    flex: none;
    width: 300rpx;
    white-space: normal;
    scroll-snap-align: center;
    border-radius: var(--r-lg);
    overflow: hidden;
    background: var(--c-surface);
    border: 1rpx solid var(--c-card-border);
    box-shadow: var(--shadow-card);
    transform: scale(var(--host-scale, 1));
    transform-origin: center bottom;
    transition: transform 260ms var(--ease-out), box-shadow 260ms var(--ease-out);

    /* 中心聚焦：屏幕中线那张 1.0，相邻 0.94，更远 0.88，视线自然回到中间 */
    &.is-side {
        --host-scale: 0.94;
    }

    &.is-far {
        --host-scale: 0.88;
    }

    &.is-focus {
        box-shadow: var(--shadow-card-hover);
    }

    &.is-pressing {
        --host-scale: 0.9;
    }

    /* 松手回弹：animation 会接管 transform，所以按焦点态给两套终值，
       避免侧卡回弹后跳到 1.0 破坏中心聚焦（也避免依赖 calc(var * number) 的兼容性） */
    &.is-focus.is-rebound {
        animation: host-rebound 440ms cubic-bezier(0.22, 1, 0.36, 1) both;
    }

    &.is-side.is-rebound {
        animation: host-rebound-side 440ms cubic-bezier(0.22, 1, 0.36, 1) both;
    }

    &.is-far.is-rebound {
        animation: host-rebound-far 440ms cubic-bezier(0.22, 1, 0.36, 1) both;
    }

    /* 3:4 竖版画框：与竖版人像同比例，aspectFill 不再裁掉头肩 */
    &__ph {
        position: relative;
        height: 400rpx;
        overflow: hidden;
        background: var(--c-neutral-bg);
    }

    &__img {
        width: 100%;
        height: 100%;
    }

    &__rank {
        position: absolute;
        top: 19rpx;
        right: 19rpx;
        z-index: 2;
        height: 38rpx;
        padding: 0 15rpx;
        border-radius: var(--r-xs);
        background: var(--c-toast-bg);
        display: inline-flex;
        align-items: center;
    }

    &__rank-text {
        font-size: var(--fs-xs);
        letter-spacing: 0.12em;
        color: var(--c-brand-soft);
    }

    /* 推荐标识：品牌金底 + 星形，比纯文字角标更明确 */
    &__rec {
        position: absolute;
        top: 19rpx;
        left: 19rpx;
        z-index: 2;
        height: 38rpx;
        padding: 0 14rpx;
        border-radius: var(--r-xs);
        background: var(--c-brand);
        display: inline-flex;
        align-items: center;
        gap: 6rpx;
    }

    &__rec-ic {
        color: var(--c-ink);
    }

    &__rec-text {
        font-size: var(--fs-xs);
        letter-spacing: 0.08em;
        color: var(--c-ink);
        line-height: var(--lh-none);
    }

    &__body {
        padding: 20rpx 22rpx 24rpx;
    }

    &__name-row {
        display: flex;
        align-items: center;
        gap: 10rpx;
    }

    &__name {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        letter-spacing: 0.02em;
        max-width: 62%;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    &__divider {
        width: 1rpx;
        height: 18rpx;
        background: var(--c-line-strong);
        flex: none;
    }

    &__style {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        letter-spacing: 0.06em;
        flex: 1;
        min-width: 0;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    &__meta {
        display: block;
        margin-top: 12rpx;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        letter-spacing: 0.03em;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    &__price {
        margin-top: 17rpx;
        display: flex;
        align-items: baseline;
        gap: 6rpx;
    }

    &__price-value {
        font-family: var(--font-serif);
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        color: var(--c-brand-text);
    }

    &__price-unit {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

/* 推荐位骨架（不闪白，占位与真卡同高同宽） */
.host-sk {
    flex: none;
    width: 300rpx;
    border-radius: var(--r-lg);
    border: 1rpx solid var(--c-card-border);
    background: var(--c-surface);
    overflow: hidden;

    &__ph {
        height: 400rpx;
        background: var(--c-neutral-bg);
    }

    &__line {
        height: 22rpx;
        margin: 20rpx 22rpx;
        border-radius: var(--r-xs);
        background: var(--c-neutral-bg);

        &--short {
            width: 50%;
        }
    }
}

/* ---------------- 吉日深色卡 ---------------- */
.darkcard {
    margin: 0 42rpx;
    border-radius: var(--r-24); /* 同心圆角：卡 24 → 内格 --r-sm */
    background: linear-gradient(145deg, var(--c-dark-1), var(--c-dark-2));
    border: 1rpx solid var(--c-on-dark-fill-2);
    padding: 38rpx 38rpx 35rpx; /* 设计稿 20px 20px 18px */

    &__top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__title-wrap {
        flex: 1;
        min-width: 0;
    }

    &__sub {
        display: block;
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
        letter-spacing: 0.04em;
    }

    &__link {
        display: flex;
        align-items: center;
        gap: 4rpx;
        min-height: 60rpx;
        flex: none;
        /* 图标与「全部档期」文字同色；文字自己声明了 --c-brand-soft，不受影响 */
        color: var(--c-brand-soft);
    }

    &__link-text {
        font-size: var(--fs-xs);
        color: var(--c-brand-soft);
        letter-spacing: 0.04em;
    }

    &--sk {
        display: flex;
        flex-direction: column;
        gap: var(--sp-3);
    }
}

/* 年份进度细线：线宽 = 今年已过比例，起止日期只在两端做小注 */
.yearbar {
    margin-bottom: 28rpx;

    &__meta {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: var(--sp-2);
        padding-bottom: 12rpx;
    }

    &__label {
        font-family: var(--font-serif);
        font-size: var(--fs-xs);
        letter-spacing: 0.18em;
        color: var(--c-on-dark-2);
    }

    &__range {
        font-family: var(--font-serif);
        font-size: var(--fs-xs);
        letter-spacing: 0.08em;
        color: var(--c-on-dark-2);
    }

    &__track {
        height: 2rpx;
        border-radius: var(--r-pill);
        background: var(--c-on-dark-fill-2);
        overflow: hidden;
    }

    &__fill {
        height: 100%;
        border-radius: var(--r-pill);
        background: var(--c-brand);
    }
}

.slot-row {
    display: flex;
    gap: 15rpx; /* 设计稿 8px */
    margin-top: 31rpx; /* 设计稿 16px */
}

.slot {
    flex: 1;
    min-width: 0;
    padding: 23rpx 0;
    border-radius: var(--r-sm);
    border: 1rpx solid var(--c-on-dark-fill-2);
    background: var(--c-on-dark-fill-1);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6rpx;

    &--sk {
        height: 130rpx;
        background: var(--c-on-dark-fill-2);
        border-color: transparent;
    }

    &__date {
        font-family: var(--font-serif);
        font-size: var(--fs-29); /* 设计稿 15px */
        color: var(--c-on-dark);
        font-weight: var(--fw-medium);
    }

    &__week {
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
        letter-spacing: 0.04em;
    }

    &__state {
        margin-top: 8rpx;
        font-size: var(--fs-xs);
        color: var(--c-brand-soft);
        letter-spacing: 0.03em;
    }
}

.sk {
    height: 24rpx;
    border-radius: var(--r-xs);
    background: var(--c-on-dark-fill-3);

    &--sub {
        width: 32%;
    }
}

/* ---------------- 服务承诺 ---------------- */
.promise {
    padding: 0 42rpx;
}

/* ---------------- 页脚 ---------------- */
.foot {
    padding: 50rpx 42rpx 16rpx; /* 设计稿 26px 22px 8px */
    text-align: center;

    &__rule {
        width: 42rpx;
        height: 1rpx;
        background: var(--c-brand);
        margin: 0 auto 23rpx;
    }

    &__text {
        font-family: var(--font-serif);
        font-size: var(--fs-xs);
        letter-spacing: 0.28em;
        color: var(--c-ink-3);
    }
}

/* 推荐位松手回弹（§3.1）：焦点卡回 1.0，侧卡回 0.94，
   与 --host-scale 的按压值衔接，松手不会破坏中心聚焦 */
@keyframes host-rebound {
    0% {
        transform: scale(0.9);
    }
    42% {
        transform: scale(1.03);
    }
    72% {
        transform: scale(0.995);
    }
    100% {
        transform: scale(1);
    }
}

@keyframes host-rebound-side {
    0% {
        transform: scale(0.86);
    }
    42% {
        transform: scale(0.975);
    }
    72% {
        transform: scale(0.938);
    }
    100% {
        transform: scale(0.94);
    }
}

@keyframes host-rebound-far {
    0% {
        transform: scale(0.82);
    }
    42% {
        transform: scale(0.912);
    }
    72% {
        transform: scale(0.878);
    }
    100% {
        transform: scale(0.88);
    }
}

/* 减弱动效：不缩放、不回弹 */
@media (prefers-reduced-motion: reduce) {
    .host {
        transition: none;
    }
    .host.is-focus.is-rebound,
    .host.is-side.is-rebound,
    .host.is-far.is-rebound {
        animation: none;
    }
}
</style>
