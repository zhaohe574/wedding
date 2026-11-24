<template>
    <div class="pv-pc" :style="themeVars">
        <!-- ============ 桌面浏览器窗口壳 ============ -->
        <div class="pv-browser">
            <div class="pv-browser__bar">
                <span class="pv-browser__dot" />
                <span class="pv-browser__dot" />
                <span class="pv-browser__dot" />
                <div class="pv-browser__address">
                    <el-icon :size="12"><Lock /></el-icon>
                    <span>{{ addressText }}</span>
                </div>
            </div>

            <div class="pv-browser__viewport">
                <div class="pv-page">
                    <!-- ============ 顶部双语极简导航 ============ -->
                    <header class="pv-nav">
                        <div class="pv-nav__inner">
                            <div class="pv-nav__brand">
                                <img
                                    v-if="brandLogoUrl"
                                    class="pv-nav__logo"
                                    :src="brandLogoUrl"
                                    alt=""
                                />
                                <span v-else class="pv-seal" aria-hidden="true">{{ brandSealChar }}</span>
                                <span class="pv-nav__brand-text">
                                    <strong>{{ hero.brand_en }}</strong>
                                    <em>{{ navBrandName }}</em>
                                </span>
                            </div>
                            <nav class="pv-nav__links">
                                <span v-for="item in navItems" :key="item.en">
                                    <span class="pv-nav__link-en">{{ item.en }}</span>
                                    <span class="pv-nav__link-zh">{{ item.zh }}</span>
                                </span>
                            </nav>
                            <span class="pv-nav__cta">MINI PROGRAM</span>
                        </div>
                    </header>

                    <!-- ============ 模块 1：电影感首屏（pc-hero） ============ -->
                    <div
                        class="pv-block pv-block--hero"
                        :class="{ 'is-active': selected === 0 }"
                        @click="select(0)"
                    >
                        <span class="pv-block__tag">{{ blockTag('pc-hero') }}</span>
                        <span
                            class="pv-block__toggle"
                            :class="{ 'is-hidden': hero.enabled === 0 }"
                            @click.stop="toggleVisible(0)"
                        >
                            {{ hero.enabled === 0 ? '显示' : '隐藏' }}
                        </span>
                        <section class="m-hero">
                            <img v-if="heroImageUrl" class="m-hero__image" :src="heroImageUrl" alt="" />
                            <div v-else class="m-hero__placeholder">品牌现场</div>
                            <div class="m-hero__shade" aria-hidden="true"></div>
                            <div class="pv-shell m-hero__inner">
                                <div class="m-hero__copy">
                                    <h1 class="m-hero__title">
                                        <span
                                            v-for="(line, index) in heroTitleLines"
                                            :key="`${line}-${index}`"
                                        >{{ line }}</span>
                                    </h1>
                                    <p class="m-hero__eyebrow">{{ hero.eyebrow }}</p>
                                </div>
                                <div class="m-hero__qr-card">
                                    <span class="m-hero__qr-frame">
                                        <img v-if="qrImageUrl" :src="qrImageUrl" alt="" />
                                        <span v-else class="m-hero__qr-empty">待配置小程序码</span>
                                    </span>
                                    <span class="m-hero__qr-caption">{{ hero.qr_caption }}</span>
                                </div>
                            </div>
                        </section>
                        <div v-if="hero.enabled === 0" class="pv-hidden-mask">该模块已隐藏，前台不渲染</div>
                    </div>

                    <!-- ============ 模块 2：精选现场（pc-gallery） ============ -->
                    <div
                        class="pv-block pv-block--line"
                        :class="{ 'is-active': selected === 1 }"
                        @click="select(1)"
                    >
                        <span class="pv-block__tag">{{ blockTag('pc-gallery') }}</span>
                        <span
                            class="pv-block__toggle"
                            :class="{ 'is-hidden': gallery.enabled === 0 }"
                            @click.stop="toggleVisible(1)"
                        >
                            {{ gallery.enabled === 0 ? '显示' : '隐藏' }}
                        </span>
                        <section class="m-gallery">
                            <div class="pv-shell">
                                <div class="m-gallery__head">
                                    <p class="m-gallery__eyebrow">{{ gallery.eyebrow }}</p>
                                    <h2 class="m-gallery__title">{{ gallery.title }}</h2>
                                </div>
                                <div class="m-gallery__grid">
                                    <article v-if="galleryItems[0]" class="m-gallery__feature">
                                        <div class="m-gallery__media m-gallery__media--tall">
                                            <img
                                                v-if="imgUrl(galleryItems[0].image)"
                                                :src="imgUrl(galleryItems[0].image)"
                                                alt=""
                                            />
                                            <span v-else class="m-gallery__placeholder" aria-hidden="true">现场图</span>
                                        </div>
                                        <div class="m-gallery__caption">
                                            <span class="m-gallery__scene">{{ galleryItems[0].scene }}</span>
                                            <h3>{{ galleryItems[0].title }}</h3>
                                        </div>
                                    </article>
                                    <div class="m-gallery__stack">
                                        <article
                                            v-for="(item, index) in galleryItems.slice(1)"
                                            :key="`${item.title}-${index}`"
                                            class="m-gallery__cell"
                                        >
                                            <div class="m-gallery__media m-gallery__media--wide">
                                                <img v-if="imgUrl(item.image)" :src="imgUrl(item.image)" alt="" />
                                                <span v-else class="m-gallery__placeholder" aria-hidden="true">现场图</span>
                                            </div>
                                            <div class="m-gallery__caption">
                                                <span class="m-gallery__scene">{{ item.scene }}</span>
                                                <h3>{{ item.title }}</h3>
                                            </div>
                                        </article>
                                    </div>
                                </div>
                            </div>
                        </section>
                        <div v-if="gallery.enabled === 0" class="pv-hidden-mask">该模块已隐藏，前台不渲染</div>
                    </div>

                    <!-- ============ 模块 3：小程序引导（pc-miniprogram） ============ -->
                    <div
                        class="pv-block pv-block--line"
                        :class="{ 'is-active': selected === 2 }"
                        @click="select(2)"
                    >
                        <span class="pv-block__tag">{{ blockTag('pc-miniprogram') }}</span>
                        <span
                            class="pv-block__toggle"
                            :class="{ 'is-hidden': mini.enabled === 0 }"
                            @click.stop="toggleVisible(2)"
                        >
                            {{ mini.enabled === 0 ? '显示' : '隐藏' }}
                        </span>
                        <section class="m-mini">
                            <div class="pv-shell m-mini__inner">
                                <div class="m-mini__phones">
                                    <div
                                        v-for="phone in MP_PHONES"
                                        :key="phone.variant"
                                        class="mp-phone"
                                        :class="`mp-phone--${phone.variant}`"
                                    >
                                        <div class="mp-phone__screen">
                                            <img
                                                v-if="getPhonePreviewImage(phone.variant)"
                                                :src="getPhonePreviewImage(phone.variant)"
                                                :alt="mini.miniapp_name"
                                            />
                                            <div v-else class="mp-screen">
                                                <div class="mp-screen__bar">
                                                    <span class="mp-screen__seal" aria-hidden="true">{{ brandSealChar }}</span>
                                                    <strong>{{ navBrandName }}</strong>
                                                    <span class="mp-screen__bar-en">{{ hero.brand_en }}</span>
                                                </div>
                                                <div class="mp-screen__hero">
                                                    <img v-if="mpHeroImage" :src="mpHeroImage" alt="" />
                                                    <span v-else class="mp-screen__hero-empty" aria-hidden="true">仪式现场</span>
                                                </div>
                                                <div class="mp-screen__section">
                                                    <span class="mp-screen__section-title">推荐主持</span>
                                                    <span class="mp-screen__section-en">HOSTS</span>
                                                </div>
                                                <div class="mp-screen__hosts">
                                                    <div
                                                        v-for="(host, index) in mpAvatars"
                                                        :key="`host-${index}`"
                                                        class="mp-screen__host"
                                                    >
                                                        <span class="mp-screen__avatar">
                                                            <img v-if="host.image" :src="host.image" alt="" />
                                                        </span>
                                                        <span class="mp-screen__tag">{{ host.tag }}</span>
                                                    </div>
                                                </div>
                                                <div class="mp-screen__section">
                                                    <span class="mp-screen__section-title">近期吉日 · 可约情况</span>
                                                </div>
                                                <div class="mp-screen__dates">
                                                    <div
                                                        v-for="row in MP_DATE_ROWS"
                                                        :key="row.day"
                                                        class="mp-screen__date"
                                                    >
                                                        <strong>{{ row.day }}</strong>
                                                        <span
                                                            class="mp-screen__status"
                                                            :class="`mp-screen__status--${row.tone}`"
                                                        >{{ row.status }}</span>
                                                    </div>
                                                </div>
                                                <span class="mp-screen__cta">立即查档</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="m-mini__copy">
                                    <h2 class="m-mini__title">
                                        <span
                                            v-for="(line, index) in mpTitleLines"
                                            :key="`${line}-${index}`"
                                        >{{ line }}</span>
                                    </h2>
                                    <ul class="m-mini__highlights">
                                        <li v-for="item in mini.highlights" :key="item.title">
                                            <i class="m-mini__dot" aria-hidden="true"></i>
                                            <div>
                                                <strong>{{ item.title }}</strong>
                                                <span>{{ item.en_label }}</span>
                                            </div>
                                        </li>
                                    </ul>
                                    <div class="m-mini__pedestal">
                                        <div class="m-mini__qr-box">
                                            <img v-if="qrImageUrl" :src="qrImageUrl" alt="" />
                                            <span v-else class="m-mini__qr-empty">待配置小程序码</span>
                                        </div>
                                        <p class="m-mini__qr-caption">{{ mini.qrcode_caption }}</p>
                                        <p class="m-mini__qr-name">微信搜索：{{ mini.miniapp_name }}</p>
                                    </div>
                                </div>
                            </div>
                        </section>
                        <div v-if="mini.enabled === 0" class="pv-hidden-mask">该模块已隐藏，前台不渲染</div>
                    </div>

                    <!-- ============ 三栏建筑感深墨页脚 ============ -->
                    <footer class="pv-foot">
                        <div class="pv-shell pv-foot__grid">
                            <div class="pv-foot__contact">
                                <template v-if="footerContacts.length">
                                    <p v-for="item in footerContacts" :key="item.label">
                                        <span>{{ item.label }}</span>{{ item.value }}
                                    </p>
                                </template>
                                <p v-else>{{ hero.eyebrow }}</p>
                            </div>
                            <div class="pv-foot__brand">
                                <img
                                    v-if="brandLogoUrl"
                                    class="pv-foot__logo"
                                    :src="brandLogoUrl"
                                    alt=""
                                />
                                <span v-else class="pv-seal pv-seal--large" aria-hidden="true">{{ brandSealChar }}</span>
                                <strong>{{ navBrandName }}</strong>
                            </div>
                            <div class="pv-foot__aside">
                                <span class="pv-foot__slogan">{{ mini.footer_slogan }}</span>
                                <nav class="pv-foot__links" aria-label="协议与政策">
                                    <span>用户协议</span>
                                    <span>隐私政策</span>
                                </nav>
                            </div>
                        </div>
                    </footer>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
/**
 * PC 官网装修预览（后台自绘流式版，v8.1 高定暖墨黑三屏）。
 *
 * 与前台 `pc/pages/index.vue` 的关系：
 *   - **结构同源**：顶部双语极简导航 + 3 个固定模块（pc-hero / pc-gallery /
 *     pc-miniprogram）+ 三栏建筑感页脚，与前台一比一镜像；
 *   - **视觉令牌同源**：`--pv-*` 色值来自 `widgets/pc-shared.ts` 的 `pcPreviewTheme`，
 *     手机样机内屏使用小程序同源浅色令牌（--mnp-* 前缀）；
 *   - **数据同源**：消费 `parsePcDecorateWidgets` 输出的 3 模块标准化列表，
 *     预览不做任何兜底拼装，所见即保存后前台所得。
 *
 * 已知差异（可接受）：
 *   1. 字体回落到后台所在系统的字体栈，不做 webfont；
 *   2. 导航不做 sticky 粘性与 backdrop-filter，画册悬停微缩放不做；
 *      首屏高度取前台 clamp 下限 580px（预览容器内 vh 语义不同）；
 *   3. 锚点不跳转、扫码不弹窗、协议链接不可点（预览不产生业务行为）。
 */
import { Lock } from '@element-plus/icons-vue'
import { computed, type CSSProperties } from 'vue'

import useAppStore from '@/stores/modules/app'
import type {
    PcGalleryContent,
    PcHeroContent,
    PcMiniProgramContent,
    PcWidgetName
} from '@shared/contracts/pc-decorate'

import { PC_WIDGET_META, pcPreviewTheme } from '../widgets/pc-shared'

const props = defineProps<{
    /** 当前页的 3 模块列表（编辑态草稿，来自 parsePcDecorateWidgets） */
    pageData: any[]
    /** 当前选中的模块下标 */
    modelValue: number
    /** PC 端前台地址（来自 getDecoratePc().pc_url） */
    pcUrl?: string
    /** 全局小程序码（来自 getDecoratePc().mnp_qr_code），模块未配置二维码时回退 */
    fallbackMnpQrCode?: string
    /** 站点 PC Logo（来自 getDecoratePc().pc_logo），缺失时显示品牌方印 */
    siteLogo?: string
    /** 站点品牌名（来自 getDecoratePc().brand_name），模块未填品牌名时回退 */
    brandName?: string
}>()

const emit = defineEmits<{
    (event: 'update:modelValue', index: number): void
    (event: 'toggle-visible', index: number): void
}>()

const appStore = useAppStore()

/** 主题令牌注入 CSS 变量：--pv-* 为高定暖墨黑，--mnp-* 为手机样机内屏浅色（色值唯一来源 pc-shared.ts） */
const themeVars = {
    '--pv-bg': pcPreviewTheme.bg,
    '--pv-bg-deep': pcPreviewTheme.bgDeep,
    '--pv-surface': pcPreviewTheme.surface,
    '--pv-ink': pcPreviewTheme.ink,
    '--pv-ink2': pcPreviewTheme.ink2,
    '--pv-ink3': pcPreviewTheme.ink3,
    '--pv-gold': pcPreviewTheme.gold,
    '--pv-gold-soft': pcPreviewTheme.goldSoft,
    '--pv-line': pcPreviewTheme.line,
    '--pv-line-strong': pcPreviewTheme.lineStrong,
    '--mnp-bg': pcPreviewTheme.mnpBg,
    '--mnp-surface': pcPreviewTheme.mnpSurface,
    '--mnp-ink': pcPreviewTheme.mnpInk,
    '--mnp-brand': pcPreviewTheme.mnpBrand,
    '--mnp-brand-text': pcPreviewTheme.mnpBrandText,
    '--mnp-line': pcPreviewTheme.mnpLine
} as CSSProperties

const selected = computed({
    get: () => props.modelValue,
    set: (value: number) => emit('update:modelValue', value)
})

const select = (index: number) => {
    selected.value = index
}

const toggleVisible = (index: number) => {
    emit('toggle-visible', index)
}

/** 左上模块中文名标签：与装修面板 PC_WIDGET_META 同源 */
const blockTag = (name: PcWidgetName) => PC_WIDGET_META[name].title

function contentOf<T>(name: string): T {
    const widget = props.pageData?.find((item) => item?.name === name)
    return (widget?.content ?? {}) as T
}

const hero = computed<PcHeroContent>(() => contentOf('pc-hero'))
const gallery = computed<PcGalleryContent>(() => contentOf('pc-gallery'))
const mini = computed<PcMiniProgramContent>(() => contentOf('pc-miniprogram'))

/** 相对路径补域名；绝对地址原样返回（store 内已含 https?:// 判定） */
function imgUrl(url?: string): string {
    const value = String(url || '').trim()
    if (!value) return ''
    if (/^https?:\/\//i.test(value)) return value
    return appStore.getImageUrl(value)
}

const heroImageUrl = computed(() => imgUrl(hero.value.image))
const brandLogoUrl = computed(() => imgUrl(props.siteLogo))

/** 扫码图：模块二维码优先，回退渠道设置中的全局小程序码（首屏浮卡 / 第三屏底座共用） */
const qrImageUrl = computed(() => imgUrl(mini.value.qrcode) || imgUrl(props.fallbackMnpQrCode))

const navBrandName = computed(() => hero.value.brand_name || props.brandName || '')
const brandSealChar = computed(() => navBrandName.value.slice(0, 1) || '格')

const addressText = computed(() => props.pcUrl || '/pc')

/** 标题折行：装修配置中的 \n 渲染为多行衬线排印 */
const splitLines = (text: string) =>
    String(text || '')
        .split(/\r?\n/)
        .map((line) => line.trim())
        .filter(Boolean)

const heroTitleLines = computed(() => splitLines(hero.value.title))
const mpTitleLines = computed(() => splitLines(mini.value.title))

const galleryItems = computed(() => gallery.value.data || [])

/** 导航锚点：双语两行，仅展示已启用模块（与前台 navItems 同逻辑） */
const navItems = computed(() => {
    const items: Array<{ en: string; zh: string }> = []
    if (hero.value.enabled === 1) items.push({ en: 'CEREMONY HOUSE', zh: '婚礼服务' })
    if (gallery.value.enabled === 1) items.push({ en: 'PORTFOLIO', zh: '精选现场' })
    if (mini.value.enabled === 1) items.push({ en: 'MINI PROGRAM', zh: '小程序' })
    return items
})

// ===== 手机样机：与 newuniapp 同源的温润象牙白微缩小程序界面 =====
const MP_PHONES = [{ variant: 'back' }, { variant: 'front' }] as const
const MP_HOST_TAGS = ['温润叙事', '沉稳大气', '灵动细腻'] as const
const MP_DATE_ROWS = [
    { day: '宜嫁娶', status: '可约', tone: 'success' },
    { day: '宜宴请', status: '仅 1 位', tone: 'warning' },
    { day: '宜仪式', status: '已约满', tone: 'neutral' }
] as const

const mpPreviewImage = computed(() => imgUrl(mini.value.preview_image))
const mpBackPreviewImage = computed(
    () => imgUrl(mini.value.preview_image_back) || mpPreviewImage.value
)
const getPhonePreviewImage = (variant: 'front' | 'back') =>
    variant === 'front' ? mpPreviewImage.value : mpBackPreviewImage.value
const mpHeroImage = computed(() => heroImageUrl.value || imgUrl(galleryItems.value[0]?.image))
const mpAvatars = computed(() =>
    galleryItems.value.map((item, index) => ({
        image: imgUrl(item.image),
        tag: MP_HOST_TAGS[index] ?? ''
    }))
)

/** 页脚联络信息：空项不输出，全空时回显品牌眉题（与前台 footerContacts 同逻辑） */
const footerContacts = computed(() =>
    [
        { label: '地址', value: mini.value.address },
        { label: '电话', value: mini.value.phone },
        { label: '服务时间', value: mini.value.service_time }
    ].filter((item) => item.value)
)
</script>

<style lang="scss" scoped>
$pv-font-display: Georgia, 'Times New Roman', 'Songti SC', STSong, 'Noto Serif CJK SC', serif;
$pv-font-sans: -apple-system, BlinkMacSystemFont, 'Helvetica Neue', 'PingFang SC', 'Microsoft YaHei',
    Arial, sans-serif;

/* ---------------- 页面基础（色值来自 --pv-* / --mnp-* 变量） ---------------- */
.pv-pc {
    position: relative;
    width: 100%;
    height: 100%;
}

.pv-browser {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid var(--pv-line);
    border-radius: 10px;
    background: var(--pv-bg-deep);

    &__bar {
        flex: none;
        display: flex;
        align-items: center;
        gap: 6px;
        height: 34px;
        padding: 0 12px;
        background: var(--pv-surface);
        border-bottom: 1px solid var(--pv-line);
    }

    &__dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        background: var(--pv-gold-soft);

        &:nth-child(2) {
            opacity: 0.7;
        }

        &:nth-child(3) {
            opacity: 0.45;
        }
    }

    &__address {
        flex: 1;
        min-width: 0;
        height: 22px;
        margin-left: 6px;
        padding: 0 12px;
        display: flex;
        align-items: center;
        gap: 6px;
        border-radius: 11px;
        background: var(--pv-bg-deep);
        border: 1px solid var(--pv-line);
        color: var(--pv-ink3);
        font-size: 12px;

        span {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    }

    &__viewport {
        position: relative;
        flex: 1;
        min-height: 0;
        overflow: auto;
        background: var(--pv-bg);
    }
}

/* ---------------- 站点画布（前台 1120px 栅格的流式镜像） ---------------- */
.pv-page {
    min-width: 920px;
    min-height: 100%;
    background: var(--pv-bg);
    color: var(--pv-ink);
    font-family: $pv-font-sans;
    -webkit-font-smoothing: antialiased;
}

.pv-shell {
    max-width: 1120px;
    margin: 0 auto;
    padding: 0 24px;
    box-sizing: border-box;
}

/* 哑光香槟金渐变方印：Logo 缺失时的品牌回退，也用于页脚中栏 */
.pv-seal {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 28px;
    height: 28px;
    border-radius: 4px;
    background: linear-gradient(145deg, #dfc48e 0%, #9b7740 100%);
    color: var(--mnp-ink);
    font-family: $pv-font-display;
    font-size: 14px;
    font-weight: 500;
    line-height: 1;

    &--large {
        width: 36px;
        height: 36px;
        font-size: 17px;
    }
}

/* ---------------- 顶部双语极简导航 ---------------- */
.pv-nav {
    height: 72px;
    background: var(--pv-bg);
    border-bottom: 1px solid var(--pv-line);

    &__inner {
        max-width: 1120px;
        height: 100%;
        margin: 0 auto;
        padding: 0 24px;
        box-sizing: border-box;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
    }

    &__brand {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    &__logo {
        display: block;
        height: 28px;
        width: auto;
    }

    &__brand-text {
        display: flex;
        flex-direction: column;
        gap: 4px;
        min-width: 0;

        strong {
            color: var(--pv-ink);
            font-family: $pv-font-display;
            font-size: 12px;
            font-weight: 400;
            letter-spacing: 0.18em;
            line-height: 1;
            text-transform: uppercase;
            white-space: nowrap;
        }

        em {
            color: var(--pv-ink2);
            font-size: 12px;
            font-style: normal;
            font-weight: 400;
            letter-spacing: 0.08em;
            line-height: 1;
            white-space: nowrap;
        }
    }

    &__links {
        display: flex;
        align-items: center;
        gap: 36px;

        > span {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            text-align: center;
        }
    }

    &__link-en {
        color: var(--pv-gold);
        font-family: $pv-font-display;
        font-size: 11px;
        letter-spacing: 0.16em;
        line-height: 1;
        text-transform: uppercase;
    }

    &__link-zh {
        color: var(--pv-ink2);
        font-size: 12px;
        line-height: 1;
    }

    &__cta {
        flex: none;
        display: inline-flex;
        align-items: center;
        height: 34px;
        padding: 0 18px;
        border: 1px solid var(--pv-line-strong);
        border-radius: 999px;
        background: rgba(239, 229, 211, 0.04);
        color: var(--pv-gold-soft);
        font-family: $pv-font-display;
        font-size: 11px;
        letter-spacing: 0.18em;
        white-space: nowrap;
    }
}

/* ---------------- 区块容器与选中态 ---------------- */
.pv-block {
    position: relative;
    cursor: pointer;

    &::after {
        content: '';
        position: absolute;
        inset: 0;
        border: 1px dashed transparent;
        pointer-events: none;
        z-index: 8;
    }

    &:hover::after {
        border-color: rgba(201, 169, 110, 0.65);
    }

    &.is-active::after {
        border: 1px solid var(--pv-gold);
        box-shadow: 0 0 0 1px rgba(201, 169, 110, 0.28);
    }

    &__tag {
        position: absolute;
        top: 0;
        left: 0;
        z-index: 9;
        padding: 1px 8px;
        font-size: 11px;
        line-height: 20px;
        color: #ffffff;
        background: rgba(17, 14, 12, 0.82);
        border-bottom-right-radius: 6px;
        opacity: 0;
        transition: opacity 0.15s;
        pointer-events: none;
    }

    &:hover &__tag,
    &.is-active &__tag {
        opacity: 1;
    }

    &__toggle {
        position: absolute;
        top: 8px;
        right: 12px;
        z-index: 9;
        height: 22px;
        padding: 0 10px;
        display: inline-flex;
        align-items: center;
        border: 1px solid var(--pv-line-strong);
        border-radius: 999px;
        background: rgba(17, 14, 12, 0.82);
        color: var(--pv-ink2);
        font-size: 11px;
        opacity: 0;
        transition: opacity 0.15s;
        cursor: pointer;

        &:hover {
            border-color: var(--pv-gold);
            color: var(--pv-gold);
        }

        &.is-hidden {
            border-color: var(--pv-gold);
            color: var(--pv-gold);
        }
    }

    &:hover &__toggle,
    &.is-active &__toggle {
        opacity: 1;
    }

    &--line {
        border-top: 1px solid var(--pv-line);
    }
}

.pv-hidden-mask {
    position: absolute;
    inset: 0;
    z-index: 7;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(17, 14, 12, 0.55);
    color: #ffffff;
    font-size: 13px;
    letter-spacing: 0.06em;
    pointer-events: none;
}

/* ---------------- 模块 1：电影感首屏（全幅主视觉 + 暖墨暗角 + 扫码浮卡） ---------------- */
.m-hero {
    position: relative;
    display: flex;
    align-items: flex-end;
    min-height: 580px;
    overflow: hidden;

    &__image {
        position: absolute;
        inset: 0;
        z-index: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    &__placeholder {
        position: absolute;
        inset: 0;
        z-index: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--pv-surface);
        color: var(--pv-ink3);
        font-size: 14px;
        letter-spacing: 0.12em;
    }

    /* 暖墨暗角：顶部保证导航可读，底部无缝融入 --pv-bg */
    &__shade {
        position: absolute;
        inset: 0;
        z-index: 1;
        background: linear-gradient(180deg, rgba(17, 14, 12, 0.6) 0%, rgba(17, 14, 12, 0) 34%),
            linear-gradient(
                180deg,
                rgba(17, 14, 12, 0) 46%,
                rgba(21, 18, 16, 0.86) 84%,
                var(--pv-bg) 100%
            );
    }

    &__inner {
        position: relative;
        z-index: 2;
        width: 100%;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 32px;
        padding-top: 120px;
        padding-bottom: 44px;
    }

    &__title {
        margin: 0;
        color: var(--pv-ink);
        font-family: $pv-font-display;
        font-size: 44px;
        font-weight: 400;
        line-height: 1.35;

        span {
            display: block;
        }
    }

    &__eyebrow {
        margin: 18px 0 0;
        color: var(--pv-gold);
        font-size: 12px;
        letter-spacing: 0.22em;
        text-transform: uppercase;
    }

    /* 磨砂深墨细金边扫码浮卡 */
    &__qr-card {
        flex-shrink: 0;
        width: 172px;
        padding: 14px;
        box-sizing: border-box;
        border: 1px solid rgba(201, 169, 110, 0.34);
        border-radius: 12px;
        background: rgba(24, 20, 17, 0.78);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        color: var(--pv-ink2);
        text-align: center;
    }

    &__qr-frame {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 136px;
        height: 136px;
        overflow: hidden;
        border-radius: 6px;
        background: var(--mnp-bg);

        img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    }

    &__qr-empty {
        color: var(--pv-ink3);
        font-size: 11px;
    }

    &__qr-caption {
        display: block;
        margin-top: 10px;
        color: var(--pv-ink2);
        font-size: 11px;
        letter-spacing: 0.04em;
    }
}

/* ---------------- 模块 2：精选现场（非对称 1+2 画册） ---------------- */
.m-gallery {
    padding: 72px 0 84px;

    &__head {
        margin-bottom: 40px;
    }

    &__eyebrow {
        margin: 0;
        color: var(--pv-gold);
        font-size: 12px;
        letter-spacing: 0.2em;
        text-transform: uppercase;
    }

    &__title {
        margin: 12px 0 0;
        color: var(--pv-ink);
        font-family: $pv-font-display;
        font-size: 28px;
        font-weight: 400;
        line-height: 1.3;
    }

    &__grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: 22px;
        align-items: stretch;
    }

    &__feature {
        display: flex;
        flex-direction: column;
    }

    &__stack {
        display: flex;
        flex-direction: column;
        gap: 22px;
    }

    &__cell {
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
        min-height: 0;

        .m-gallery__caption h3 {
            font-size: 17px;
        }
    }

    &__media {
        overflow: hidden;
        border-radius: 2px;
        background: var(--pv-surface);

        img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    }

    &__media--tall {
        aspect-ratio: 4 / 4.35;
    }

    &__media--wide {
        flex: 1 1 auto;
        min-height: 0;
        aspect-ratio: 16 / 9;
    }

    &__placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        color: var(--pv-ink3);
        font-size: 13px;
        letter-spacing: 0.12em;
    }

    &__scene {
        color: var(--pv-gold);
        font-size: 11px;
        letter-spacing: 0.18em;
        text-transform: uppercase;
    }

    &__caption {
        padding-top: 12px;

        h3 {
            margin: 6px 0 0;
            color: var(--pv-ink);
            font-family: $pv-font-display;
            font-size: 20px;
            font-weight: 400;
            line-height: 1.3;
        }
    }
}

/* ---------------- 模块 3：小程序引导（双手机实景 + 底座扫码收口） ---------------- */
.m-mini {
    padding: 84px 0 96px;

    &__inner {
        display: grid;
        grid-template-columns: minmax(0, 1.12fr) minmax(0, 0.88fr);
        gap: 52px;
        align-items: center;
    }

    &__phones {
        position: relative;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        width: min(460px, 100%);
        margin: 0 auto;
        padding: 12px 0 24px;

        &::before {
            content: '';
            position: absolute;
            inset: 10% 4% 4%;
            z-index: 0;
            border-radius: 50%;
            background: radial-gradient(
                ellipse at center,
                rgba(201, 169, 110, 0.14) 0%,
                rgba(201, 169, 110, 0) 68%
            );
            pointer-events: none;
        }
    }

    &__title {
        margin: 0;
        color: var(--pv-ink);
        font-family: $pv-font-display;
        font-size: 40px;
        font-weight: 400;
        line-height: 1.3;

        span {
            display: block;
        }
    }

    &__highlights {
        display: flex;
        gap: 28px;
        margin: 30px 0 0;
        padding: 0;
        list-style: none;

        li {
            display: flex;
            gap: 10px;
        }

        strong {
            display: block;
            color: var(--pv-ink);
            font-size: 15px;
            font-weight: 500;
            line-height: 1.3;
        }

        span {
            display: block;
            margin-top: 5px;
            color: var(--pv-ink3);
            font-family: $pv-font-display;
            font-size: 10px;
            letter-spacing: 0.14em;
            line-height: 1;
            text-transform: uppercase;
        }
    }

    &__dot {
        flex-shrink: 0;
        width: 6px;
        height: 6px;
        margin-top: 7px;
        border-radius: 50%;
        background: var(--pv-gold);
    }

    /* 高定扫码底座：暗金微衬方框 + 底座微光椭圆投影 */
    &__pedestal {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-top: 44px;
        text-align: center;
    }

    &__qr-box {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 156px;
        height: 156px;
        box-sizing: border-box;
        padding: 12px;
        border: 1px solid rgba(201, 169, 110, 0.38);
        border-radius: 14px;
        background: linear-gradient(
            160deg,
            rgba(201, 169, 110, 0.16) 0%,
            rgba(201, 169, 110, 0.05) 100%
        );

        img {
            position: relative;
            z-index: 1;
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 4px;
            background: var(--mnp-bg);
        }

        /* 底座微光椭圆投影层（置于方框底色之上、二维码内容之下） */
        &::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -22px;
            z-index: 0;
            width: 220px;
            height: 26px;
            border-radius: 50%;
            background: radial-gradient(
                ellipse at center,
                rgba(201, 169, 110, 0.32) 0%,
                rgba(201, 169, 110, 0) 70%
            );
            filter: blur(6px);
            transform: translateX(-50%);
        }
    }

    &__qr-empty {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        border-radius: 4px;
        background: var(--mnp-bg);
        color: var(--pv-ink3);
        font-size: 12px;
    }

    &__qr-caption {
        margin: 26px 0 0;
        color: var(--pv-ink2);
        font-size: 13px;
    }

    &__qr-name {
        margin: 8px 0 0;
        color: var(--pv-ink3);
        font-size: 12px;
    }
}

/* 错落双联深钛框手机样机 */
.mp-phone {
    box-sizing: border-box;
    padding: 8px;
    border: 1px solid rgba(201, 169, 110, 0.26);
    border-radius: 28px;
    background: linear-gradient(160deg, #2c2723 0%, #1a1613 100%);
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.45);

    &--back {
        position: relative;
        z-index: 1;
        flex: 0 0 auto;
        width: 214px;
        margin-top: 38px;
        border-color: rgba(201, 169, 110, 0.22);
        opacity: 0.94;
    }

    &--front {
        position: relative;
        z-index: 2;
        flex: 0 0 auto;
        width: 240px;
        margin: 0 0 0 -16px;
        border-color: rgba(201, 169, 110, 0.42);
        box-shadow: -16px 24px 52px rgba(0, 0, 0, 0.58);
    }

    &__screen {
        overflow: hidden;
        border-radius: 21px;
        background: var(--mnp-bg);

        > img {
            display: block;
            width: 100%;
            height: auto;
            aspect-ratio: 9 / 19.2;
            object-fit: cover;
            object-position: top center;
        }
    }
}

/* 象牙白微缩小程序界面（--mnp-* 同源令牌；#f3ede1 / #6b655c / #f5eddd
   为前台 var.css 的 --c-surface-2 / --c-ink-3 / --c-brand-bg，未纳入 mnp 令牌） */
.mp-screen {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 12px 12px 14px;
    color: var(--mnp-ink);
    font-family: $pv-font-sans;

    &__bar {
        display: flex;
        align-items: center;
        gap: 6px;

        strong {
            color: var(--mnp-ink);
            font-family: $pv-font-display;
            font-size: 11px;
            font-weight: 500;
            line-height: 1;
        }
    }

    &__seal {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 16px;
        height: 16px;
        border-radius: 3px;
        background: linear-gradient(145deg, #dfc48e 0%, #9b7740 100%);
        color: var(--mnp-ink);
        font-family: $pv-font-display;
        font-size: 9px;
        line-height: 1;
    }

    &__bar-en {
        margin-left: auto;
        color: #6b655c;
        font-family: $pv-font-display;
        font-size: 8px;
        letter-spacing: 0.14em;
        line-height: 1;
        text-transform: uppercase;
    }

    &__hero {
        overflow: hidden;
        aspect-ratio: 16 / 10;
        border-radius: 8px;
        background: #f3ede1;

        img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    }

    &__hero-empty {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: #6b655c;
        font-size: 10px;
    }

    &__section {
        display: flex;
        align-items: baseline;
        gap: 6px;
    }

    &__section-title {
        color: var(--mnp-ink);
        font-size: 11px;
        font-weight: 500;
        line-height: 1;
    }

    &__section-en {
        color: #6b655c;
        font-family: $pv-font-display;
        font-size: 8px;
        letter-spacing: 0.14em;
        line-height: 1;
        text-transform: uppercase;
    }

    &__hosts {
        display: flex;
        gap: 8px;
    }

    &__host {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
    }

    &__avatar {
        display: block;
        width: 34px;
        height: 34px;
        overflow: hidden;
        border-radius: 50%;
        background: #f3ede1;

        img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    }

    &__tag {
        padding: 2px 6px;
        border-radius: 999px;
        background: #f5eddd;
        color: var(--mnp-brand-text);
        font-size: 8px;
        line-height: 1.4;
        white-space: nowrap;
    }

    &__dates {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    &__date {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 8px;
        border-radius: 6px;
        background: var(--mnp-surface);

        strong {
            color: var(--mnp-ink);
            font-size: 10px;
            font-weight: 500;
            line-height: 1.2;
        }
    }

    &__status {
        margin-left: auto;
        padding: 2px 6px;
        border-radius: 999px;
        font-size: 8px;
        line-height: 1.4;

        &--success {
            background: #eaf0ea;
            color: #48644b;
        }

        &--warning {
            background: #faf0dc;
            color: #8a5a16;
        }

        &--neutral {
            background: #f0ece4;
            color: #605a51;
        }
    }

    &__cta {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 26px;
        border-radius: 999px;
        background: var(--mnp-brand);
        color: var(--mnp-ink);
        font-size: 10px;
        font-weight: 500;
        letter-spacing: 0.08em;
    }
}

/* ---------------- 三栏建筑感深墨页脚 ---------------- */
.pv-foot {
    padding: 44px 0 28px;
    border-top: 1px solid var(--pv-line);
    background: var(--pv-bg-deep);

    &__grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
        gap: 24px 32px;
        align-items: start;
    }

    &__contact {
        p {
            margin: 0 0 8px;
            color: var(--pv-ink3);
            font-size: 13px;
            line-height: 1.7;

            span {
                margin-right: 10px;
                color: var(--pv-ink2);
            }
        }
    }

    &__brand {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;

        strong {
            color: var(--pv-ink);
            font-family: $pv-font-display;
            font-size: 16px;
            font-weight: 400;
            letter-spacing: 0.12em;
        }
    }

    &__logo {
        display: block;
        height: 36px;
        width: auto;
        max-width: 140px;
        object-fit: contain;
    }

    &__aside {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 10px;
    }

    &__slogan {
        color: var(--pv-ink2);
        font-family: $pv-font-display;
        font-size: 12px;
        letter-spacing: 0.16em;
        text-transform: uppercase;
    }

    &__links {
        display: flex;
        gap: 16px;

        span {
            color: var(--pv-ink3);
            font-size: 13px;
        }
    }
}
</style>
