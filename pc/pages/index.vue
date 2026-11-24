<template>
    <main class="pc-home">
        <!-- 顶部超薄粘性导航 -->
        <header class="pc-nav">
            <div class="pc-nav__inner">
                <a class="pc-nav__brand" href="#" aria-label="回到顶部">
                    <img
                        v-if="!showBrandSeal"
                        class="pc-nav__logo"
                        :src="brandLogo"
                        alt="品牌标识"
                        @error="logoFailed = true"
                    />
                    <span v-else class="pc-brand-seal" aria-hidden="true">{{
                        brandSealChar
                    }}</span>
                    <span class="pc-nav__brand-text">
                        <strong>{{ heroContent.brand_en }}</strong>
                        <em>{{ heroContent.brand_name }}</em>
                    </span>
                </a>
                <nav class="pc-nav__links" aria-label="页面导航">
                    <a
                        v-for="item in navItems"
                        :key="item.href"
                        :href="item.href"
                    >
                        <span class="pc-nav__link-en">{{ item.en }}</span>
                        <span class="pc-nav__link-zh">{{ item.zh }}</span>
                    </a>
                </nav>
                <button type="button" class="pc-nav__cta" @click="openQrDialog">
                    MINI PROGRAM
                </button>
            </div>
        </header>

        <!-- 模块 1：品牌首屏（全幅沉浸式主视觉 + 右下扫码浮卡） -->
        <section v-if="isModuleEnabled(heroWidget)" id="hero" class="pc-hero">
            <img
                v-if="heroImage"
                class="pc-hero__image"
                :src="heroImage"
                :alt="heroContent.image_alt || heroContent.title"
                width="1440"
                height="820"
                loading="eager"
                v-bind="{ fetchpriority: 'high' } as Record<string, any>"
            />
            <div class="pc-hero__shade" aria-hidden="true"></div>
            <div class="pc-shell pc-hero__inner">
                <div class="pc-hero__copy">
                    <h1 class="pc-hero__title">
                        <span
                            v-for="(line, index) in heroTitleLines"
                            :key="`${line}-${index}`"
                            >{{ line }}</span
                        >
                    </h1>
                    <p class="pc-hero__eyebrow">{{ heroContent.eyebrow }}</p>
                </div>
                <button
                    type="button"
                    class="pc-hero__qr-card"
                    @click="openQrDialog"
                >
                    <span class="pc-hero__qr-frame">
                        <img
                            v-if="qrImage"
                            :src="qrImage"
                            alt="微信小程序二维码"
                        />
                        <span v-else class="pc-hero__qr-empty"
                            >待配置小程序码</span
                        >
                    </span>
                    <span class="pc-hero__qr-caption">{{
                        heroContent.qr_caption
                    }}</span>
                </button>
            </div>
        </section>

        <!-- 全宽 1px 香槟金细线分隔 -->
        <div
            v-if="isModuleEnabled(galleryWidget)"
            class="pc-rule"
            aria-hidden="true"
        ></div>

        <!-- 模块 2：精选现场（非对称 1+2 高定画册） -->
        <section
            v-if="isModuleEnabled(galleryWidget)"
            id="gallery"
            class="pc-gallery"
        >
            <div class="pc-shell">
                <div class="pc-gallery__head">
                    <p class="pc-gallery__eyebrow">
                        {{ galleryContent.eyebrow }}
                    </p>
                    <h2 class="pc-gallery__title">
                        {{ galleryContent.title }}
                    </h2>
                </div>
                <div class="pc-gallery__grid">
                    <article v-if="galleryItems[0]" class="pc-gallery__feature">
                        <div class="pc-gallery__media pc-gallery__media--tall">
                            <img
                                v-if="getImageUrl(galleryItems[0].image)"
                                :src="getImageUrl(galleryItems[0].image)"
                                :alt="
                                    galleryItems[0].alt || galleryItems[0].title
                                "
                                loading="lazy"
                            />
                            <span
                                v-else
                                class="pc-gallery__placeholder"
                                aria-hidden="true"
                                >现场图</span
                            >
                        </div>
                        <div class="pc-gallery__caption">
                            <span class="pc-gallery__scene">{{
                                galleryItems[0].scene
                            }}</span>
                            <h3>{{ galleryItems[0].title }}</h3>
                        </div>
                    </article>
                    <div class="pc-gallery__stack">
                        <article
                            v-for="(item, index) in galleryItems.slice(1)"
                            :key="`${item.title}-${index}`"
                            class="pc-gallery__cell"
                        >
                            <div
                                class="pc-gallery__media pc-gallery__media--wide"
                            >
                                <img
                                    v-if="getImageUrl(item.image)"
                                    :src="getImageUrl(item.image)"
                                    :alt="item.alt || item.title"
                                    loading="lazy"
                                />
                                <span
                                    v-else
                                    class="pc-gallery__placeholder"
                                    aria-hidden="true"
                                    >现场图</span
                                >
                            </div>
                            <div class="pc-gallery__caption">
                                <span class="pc-gallery__scene">{{
                                    item.scene
                                }}</span>
                                <h3>{{ item.title }}</h3>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <!-- 全宽 1px 香槟金细线分隔 -->
        <div
            v-if="isModuleEnabled(miniProgramWidget)"
            class="pc-rule"
            aria-hidden="true"
        ></div>

        <!-- 模块 3：小程序引导（双手机象牙白实景 + 底座扫码收口） -->
        <section
            v-if="isModuleEnabled(miniProgramWidget)"
            id="miniprogram"
            class="pc-miniprogram"
        >
            <div class="pc-shell pc-miniprogram__inner">
                <div class="pc-miniprogram__phones">
                    <div
                        v-for="phone in MP_PHONES"
                        :key="phone.variant"
                        class="mp-phone"
                        :class="`mp-phone--${phone.variant}`"
                        :aria-hidden="
                            phone.variant === 'back' &&
                            !miniprogramContent.preview_image_back
                                ? 'true'
                                : undefined
                        "
                    >
                        <div class="mp-phone__screen">
                            <img
                                v-if="getPhonePreviewImage(phone.variant)"
                                :src="getPhonePreviewImage(phone.variant)"
                                :alt="
                                    phone.variant === 'front'
                                        ? `${miniprogramContent.miniapp_name}主屏预览`
                                        : `${miniprogramContent.miniapp_name}副屏预览`
                                "
                                loading="lazy"
                            />
                            <div v-else class="mp-screen">
                                <div class="mp-screen__bar">
                                    <span
                                        class="mp-screen__seal"
                                        aria-hidden="true"
                                        >{{ brandSealChar }}</span
                                    >
                                    <strong>{{
                                        heroContent.brand_name
                                    }}</strong>
                                    <span class="mp-screen__bar-en">{{
                                        heroContent.brand_en
                                    }}</span>
                                </div>
                                <div class="mp-screen__hero">
                                    <img
                                        v-if="mpHeroImage"
                                        :src="mpHeroImage"
                                        alt=""
                                        loading="lazy"
                                    />
                                    <span
                                        v-else
                                        class="mp-screen__hero-empty"
                                        aria-hidden="true"
                                        >仪式现场</span
                                    >
                                </div>
                                <div class="mp-screen__section">
                                    <span class="mp-screen__section-title"
                                        >推荐主持</span
                                    >
                                    <span class="mp-screen__section-en"
                                        >HOSTS</span
                                    >
                                </div>
                                <div class="mp-screen__hosts">
                                    <div
                                        v-for="(avatar, index) in mpAvatars"
                                        :key="`avatar-${index}`"
                                        class="mp-screen__host"
                                    >
                                        <span class="mp-screen__avatar">
                                            <img
                                                v-if="avatar"
                                                :src="avatar"
                                                alt=""
                                                loading="lazy"
                                            />
                                        </span>
                                        <span class="mp-screen__tag">{{
                                            MP_HOST_TAGS[index]
                                        }}</span>
                                    </div>
                                </div>
                                <div class="mp-screen__section">
                                    <span class="mp-screen__section-title"
                                        >近期吉日 · 可约情况</span
                                    >
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
                                            >{{ row.status }}</span
                                        >
                                    </div>
                                </div>
                                <span class="mp-screen__cta">立即查档</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pc-miniprogram__copy">
                    <h2 class="pc-miniprogram__title">
                        <span
                            v-for="(line, index) in mpTitleLines"
                            :key="`${line}-${index}`"
                            >{{ line }}</span
                        >
                    </h2>
                    <ul class="pc-miniprogram__highlights">
                        <li
                            v-for="item in miniprogramContent.highlights"
                            :key="item.title"
                        >
                            <i
                                class="pc-miniprogram__dot"
                                aria-hidden="true"
                            ></i>
                            <div>
                                <strong>{{ item.title }}</strong>
                                <span>{{ item.en_label }}</span>
                            </div>
                        </li>
                    </ul>
                    <div class="pc-miniprogram__pedestal">
                        <div class="pc-miniprogram__qr-box">
                            <img
                                v-if="qrImage"
                                :src="qrImage"
                                alt="微信小程序二维码"
                                loading="lazy"
                            />
                            <span v-else class="pc-miniprogram__qr-empty"
                                >待配置小程序码</span
                            >
                        </div>
                        <p class="pc-miniprogram__qr-caption">
                            {{ miniprogramContent.qrcode_caption }}
                        </p>
                        <p class="pc-miniprogram__qr-name">
                            微信搜索：{{ miniprogramContent.miniapp_name }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 三栏建筑感深墨页脚 -->
        <footer class="pc-footer">
            <div class="pc-shell pc-footer__grid">
                <div class="pc-footer__contact">
                    <template v-if="footerContacts.length">
                        <p v-for="item in footerContacts" :key="item.label">
                            <span>{{ item.label }}</span
                            >{{ item.value }}
                        </p>
                    </template>
                    <p v-else class="pc-footer__contact-fallback">
                        {{ heroContent.eyebrow }}
                    </p>
                </div>
                <div class="pc-footer__brand">
                    <img
                        v-if="!showBrandSeal"
                        class="pc-footer__logo"
                        :src="brandLogo"
                        :alt="heroContent.brand_name"
                        loading="lazy"
                        @error="logoFailed = true"
                    />
                    <span v-else class="pc-brand-seal" aria-hidden="true">{{
                        brandSealChar
                    }}</span>
                    <strong>{{ heroContent.brand_name }}</strong>
                </div>
                <div class="pc-footer__aside">
                    <span class="pc-footer__slogan">{{
                        miniprogramContent.footer_slogan
                    }}</span>
                    <nav class="pc-footer__links" aria-label="协议与政策">
                        <NuxtLink to="/policy/service">用户协议</NuxtLink>
                        <NuxtLink to="/policy/privacy">隐私政策</NuxtLink>
                    </nav>
                </div>
            </div>
            <div class="pc-footer__copyright">
                <template v-if="copyrightItems.length">
                    <template v-for="item in copyrightItems" :key="item.key">
                        <a
                            v-if="item.value"
                            :href="item.value"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            {{ item.key }}
                        </a>
                        <span v-else>{{ item.key }}</span>
                    </template>
                </template>
                <span v-else>© 2026 GLINSHE CEREMONY HOUSE</span>
            </div>
        </footer>

        <!-- 原生扫码弹窗 -->
        <dialog
            ref="qrDialogRef"
            class="mnp-dialog"
            aria-labelledby="mnp-dialog-title"
            v-bind="{ closedby: 'any' } as Record<string, any>"
        >
            <div class="mnp-dialog__body">
                <form method="dialog" class="mnp-dialog__close-form">
                    <button
                        type="submit"
                        class="mnp-dialog__close"
                        autofocus
                        aria-label="关闭弹窗"
                    >
                        ×
                    </button>
                </form>
                <h3 id="mnp-dialog-title" class="mnp-dialog__title">
                    打开微信小程序
                </h3>
                <div class="mnp-dialog__qr">
                    <img
                        v-if="qrImage"
                        :src="qrImage"
                        alt="微信小程序二维码"
                        v-bind="{ fetchpriority: 'low' } as Record<string, any>"
                    />
                    <span v-else class="mnp-dialog__qr-empty"
                        >待配置小程序码</span
                    >
                </div>
                <p class="mnp-dialog__caption">
                    {{ miniprogramContent.qrcode_caption }}
                </p>
                <p class="mnp-dialog__name">
                    微信搜索：{{ miniprogramContent.miniapp_name }}
                </p>
                <form method="dialog" class="mnp-dialog__done-form">
                    <button type="submit" class="mnp-dialog__done">关闭</button>
                </form>
            </div>
        </dialog>
    </main>
</template>

<script lang="ts" setup>
import { getIndex } from '@/api/shop'
import {
    findPcWidget,
    parsePcDecorateWidgets,
    type PcGalleryContent,
    type PcHeroContent,
    type PcMiniProgramContent
} from '@shared/contracts'
import { useAppStore } from '~~/stores/app'

definePageMeta({
    layout: 'blank'
})

const appStore = useAppStore()

const { data: pageData } = await useAsyncData(() => getIndex(), {
    default: () => ({
        page: {
            data: '[]'
        }
    })
})

// ===== 装修数据解析：契约负责归一化与旧数据迁移，页面只消费 3 个固定模块 =====
const widgets = computed(() =>
    parsePcDecorateWidgets(pageData.value?.page?.data)
)
const heroWidget = computed(() => findPcWidget(widgets.value, 'pc-hero'))
const galleryWidget = computed(() => findPcWidget(widgets.value, 'pc-gallery'))
const miniProgramWidget = computed(() =>
    findPcWidget(widgets.value, 'pc-miniprogram')
)

const heroContent = computed<PcHeroContent>(() => heroWidget.value.content)
const galleryContent = computed<PcGalleryContent>(
    () => galleryWidget.value.content
)
const miniprogramContent = computed<PcMiniProgramContent>(
    () => miniProgramWidget.value.content
)

const isModuleEnabled = (widget: { content: { enabled: 0 | 1 } }) =>
    widget.content.enabled === 1

const getImageUrl = (url?: string) => {
    if (!url) return ''
    if (/^https?:\/\//.test(url)) return url
    return appStore.getImageUrl(url)
}

// ===== 标题折行：装修配置中的 \n 渲染为多行衬线排印 =====
const splitLines = (text: string) =>
    String(text || '')
        .split(/\r?\n/)
        .map((s) => s.trim())
        .filter(Boolean)

const heroTitleLines = computed(() => splitLines(heroContent.value.title))
const mpTitleLines = computed(() => splitLines(miniprogramContent.value.title))

// ===== 品牌标识回退：后台 Logo 缺失或加载失败时展示哑光香槟金方印 =====
const websiteConfig = computed(() => appStore.getWebsiteConfig || {})
const brandLogo = computed(
    () => websiteConfig.value.pc_logo || websiteConfig.value.shop_logo || ''
)
const logoFailed = ref(false)
watch(brandLogo, () => {
    logoFailed.value = false
})
const showBrandSeal = computed(() => !brandLogo.value || logoFailed.value)
const brandSealChar = computed(
    () => (heroContent.value.brand_name || '格').slice(0, 1) || '格'
)

// ===== 小程序二维码：装修配置优先，回退全局小程序码 =====
// 首屏右下浮卡、第三屏底座、原生弹窗三处共用同一 qrImage
const qrImage = computed(
    () =>
        getImageUrl(miniprogramContent.value.qrcode) ||
        appStore.getQrcodeConfig?.mnp ||
        ''
)

const heroImage = computed(() => getImageUrl(heroContent.value.image))

// ===== 手机样机：与 newuniapp 同源的温润象牙白微缩小程序界面 =====
const MP_PHONES = [{ variant: 'back' }, { variant: 'front' }] as const
const MP_HOST_TAGS = ['温润叙事', '沉稳大气', '灵动细腻'] as const
const MP_DATE_ROWS = [
    { day: '宜嫁娶', status: '可约', tone: 'success' },
    { day: '宜宴请', status: '仅 1 位', tone: 'warning' },
    { day: '宜仪式', status: '已约满', tone: 'neutral' }
] as const

const mpPreviewImage = computed(() =>
    getImageUrl(miniprogramContent.value.preview_image)
)
const mpBackPreviewImage = computed(
    () =>
        getImageUrl(miniprogramContent.value.preview_image_back) ||
        mpPreviewImage.value
)
const getPhonePreviewImage = (variant: 'front' | 'back') =>
    variant === 'front' ? mpPreviewImage.value : mpBackPreviewImage.value
const mpHeroImage = computed(
    () => heroImage.value || getImageUrl(galleryContent.value.data[0]?.image)
)
const mpAvatars = computed(() =>
    galleryContent.value.data.map((item) => getImageUrl(item.image))
)

const galleryItems = computed(() => galleryContent.value.data)

// ===== 导航锚点：双语两行，仅展示已启用模块 =====
const navItems = computed(() => {
    const items: Array<{ en: string; zh: string; href: string }> = []
    if (isModuleEnabled(heroWidget.value)) {
        items.push({ en: 'CEREMONY HOUSE', zh: '婚礼服务', href: '#hero' })
    }
    if (isModuleEnabled(galleryWidget.value)) {
        items.push({ en: 'PORTFOLIO', zh: '精选现场', href: '#gallery' })
    }
    if (isModuleEnabled(miniProgramWidget.value)) {
        items.push({ en: 'MINI PROGRAM', zh: '小程序', href: '#miniprogram' })
    }
    return items
})

// ===== 页脚联络信息：空项不输出，全空时回显品牌眉题 =====
const footerContacts = computed(() =>
    [
        { label: '地址', value: miniprogramContent.value.address },
        { label: '电话', value: miniprogramContent.value.phone },
        { label: '服务时间', value: miniprogramContent.value.service_time }
    ].filter((item) => item.value)
)

const copyrightItems = computed(() =>
    ((appStore.getCopyrightConfig || []) as Record<string, any>[])
        .map((item) => ({
            key: String(item?.key || '').trim(),
            value: String(item?.value || '').trim()
        }))
        .filter((item) => item.key)
)

// ===== 扫码弹窗（原生 dialog）=====
const qrDialogRef = ref<HTMLDialogElement | null>(null)
const openQrDialog = () => {
    qrDialogRef.value?.showModal()
}

// 浏览器回退：不支持 closedby 的浏览器（如旧版 Safari）手动实现
// 「点击弹窗外侧关闭」——按下与抬起都在弹窗矩形之外时调用 close()。
let dialogFallbackCleanup: (() => void) | null = null

onMounted(() => {
    const dialog = qrDialogRef.value
    if (!dialog || 'closedBy' in HTMLDialogElement.prototype) return
    let pointerDownOutside = false
    const isOutsideDialog = (event: PointerEvent) => {
        const rect = dialog.getBoundingClientRect()
        return (
            event.clientX < rect.left ||
            event.clientX > rect.right ||
            event.clientY < rect.top ||
            event.clientY > rect.bottom
        )
    }
    const onPointerDown = (event: PointerEvent) => {
        pointerDownOutside = isOutsideDialog(event)
    }
    const onPointerUp = (event: PointerEvent) => {
        if (pointerDownOutside && isOutsideDialog(event) && dialog.open) {
            dialog.close()
        }
    }
    document.addEventListener('pointerdown', onPointerDown)
    document.addEventListener('pointerup', onPointerUp)
    dialogFallbackCleanup = () => {
        document.removeEventListener('pointerdown', onPointerDown)
        document.removeEventListener('pointerup', onPointerUp)
    }
})

onBeforeUnmount(() => {
    dialogFallbackCleanup?.()
    dialogFallbackCleanup = null
})

// ===== SEO：页面级 title / description / og / canonical / Organization JSON-LD =====
const heroTitleFlat = computed(() => heroContent.value.title.replace(/\n/g, ''))
const seoTitle = computed(
    () => `${heroContent.value.brand_name}｜${heroTitleFlat.value}`
)
const seoDescription = computed(() => heroContent.value.eyebrow || '')
const seoImage = computed(
    () => heroImage.value || heroContent.value.image || ''
)
const siteUrl = 'https://www.gelinshe.com/pc/'

useHead(() => ({
    title: seoTitle.value,
    meta: [
        { name: 'description', content: seoDescription.value },
        { property: 'og:type', content: 'website' },
        { property: 'og:title', content: seoTitle.value },
        { property: 'og:description', content: seoDescription.value },
        { property: 'og:url', content: siteUrl },
        { property: 'og:image', content: seoImage.value },
        { name: 'twitter:card', content: 'summary_large_image' }
    ],
    link: [{ rel: 'canonical', href: siteUrl }]
}))

useHead({
    script: [
        {
            type: 'application/ld+json',
            innerHTML: JSON.stringify({
                '@context': 'https://schema.org',
                '@type': 'Organization',
                name: heroContent.value.brand_name,
                alternateName: heroContent.value.brand_en,
                url: siteUrl,
                logo: seoImage.value,
                description: seoDescription.value,
                slogan: heroTitleFlat.value
            })
        }
    ]
})
</script>

<style lang="scss" scoped>
/* ===== 基础 ===== */
.pc-home {
    /* 就地声明高定暖墨黑及内屏浅色令牌，避免全局 var.css 缓存未注入导致变量回落透明/黑字 */
    --pc-bg: #151210;
    --pc-bg-deep: #110e0c;
    --pc-surface: #1b1714;
    --pc-ink: #efe5d3;
    --pc-ink-2: #b9aea0;
    --pc-ink-3: #8b8073;
    --pc-gold: #c9a96e;
    --pc-gold-soft: #dfd0b0;
    --pc-line: rgba(201, 169, 110, 0.22);
    --pc-line-strong: rgba(201, 169, 110, 0.42);
    --c-bg: #faf6ee;
    --c-surface: #fffdf8;
    --c-ink: #181614;
    --c-ink-3: #6b655c;
    --c-line: #e7e0d3;
    --c-brand: #c6a15b;
    --c-brand-text: #8a6424;
    --font-sans: -apple-system, BlinkMacSystemFont, 'Helvetica Neue',
        'PingFang SC', 'Microsoft YaHei', Arial, sans-serif;
    --font-display: Georgia, 'Times New Roman', 'Songti SC', 'Noto Serif SC',
        STSong, serif;
    --dur-fast: 150ms;
    --dur-base: 240ms;
    --ease-out: cubic-bezier(0.2, 0, 0, 1);

    min-height: 100vh;
    color: var(--pc-ink);
    background: var(--pc-bg);
    font-family: var(--font-sans);
}

.pc-shell {
    max-width: 1120px;
    margin: 0 auto;
    padding: 0 24px;
    box-sizing: border-box;
}

#hero,
#gallery,
#miniprogram {
    scroll-margin-top: 84px;
}

.pc-rule {
    border-top: 1px solid var(--pc-line);
}

/* 哑光香槟金渐变方印：Logo 缺失时的品牌回退，也用于页脚中栏 */
.pc-brand-seal {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 28px;
    height: 28px;
    border-radius: 4px;
    background: linear-gradient(145deg, #dfc48e 0%, #9b7740 100%);
    color: #181614;
    font-family: var(--font-display);
    font-size: 14px;
    font-weight: 500;
    line-height: 1;
}

/* ===== 顶部超薄粘性导航 ===== */
.pc-nav {
    position: sticky;
    top: 0;
    z-index: 20;
    height: 72px;
    background: rgba(17, 14, 12, 0.94);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(201, 169, 110, 0.28);
}

.pc-nav__inner {
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

.pc-nav__brand,
.pc-nav__brand:visited {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
    color: var(--pc-ink);
    text-decoration: none;
}

.pc-nav__logo {
    display: block;
    height: 28px;
    width: auto;
}

.pc-nav__brand-text {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;

    strong {
        color: #f5ecdc;
        font-family: var(--font-display);
        font-size: 12px;
        font-weight: 400;
        letter-spacing: 0.18em;
        line-height: 1;
        text-transform: uppercase;
        white-space: nowrap;
    }

    em {
        color: #d6cbbd;
        font-size: 12px;
        font-style: normal;
        font-weight: 400;
        letter-spacing: 0.08em;
        line-height: 1;
        white-space: nowrap;
    }
}

.pc-nav__links {
    display: flex;
    align-items: center;
    gap: 36px;

    a,
    a:visited {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        color: var(--pc-ink);
        text-align: center;
        text-decoration: none;
    }
}

.pc-nav__link-en {
    color: #dfc48e;
    font-family: var(--font-display);
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 0.16em;
    line-height: 1;
    text-transform: uppercase;
}

.pc-nav__link-zh {
    color: rgba(239, 229, 211, 0.88);
    font-size: 12px;
    line-height: 1;
    transition: color var(--dur-fast) var(--ease-out);

    a:hover & {
        color: #f5ecdc;
    }
}

.pc-nav__cta {
    height: 34px;
    padding: 0 18px;
    border: 1px solid rgba(201, 169, 110, 0.56);
    border-radius: 999px;
    background: rgba(201, 169, 110, 0.14);
    color: #efe5d3;
    font-family: var(--font-display);
    font-size: 11px;
    letter-spacing: 0.18em;
    white-space: nowrap;
    cursor: pointer;
    transition:
        background-color var(--dur-fast) var(--ease-out),
        border-color var(--dur-fast) var(--ease-out),
        color var(--dur-fast) var(--ease-out);

    &:hover {
        background: rgba(201, 169, 110, 0.24);
        border-color: #dfc48e;
        color: #f5ecdc;
    }
}

/* ===== 模块 1：品牌首屏 ===== */
.pc-hero {
    position: relative;
    display: flex;
    align-items: flex-end;
    min-height: clamp(580px, 82vh, 820px);
    overflow: hidden;
}

.pc-hero__image {
    position: absolute;
    inset: 0;
    z-index: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* 柔和暖墨暗角：顶部保证导航可读，底部无缝融入 #151210 */
.pc-hero__shade {
    position: absolute;
    inset: 0;
    z-index: 1;
    background: linear-gradient(
            180deg,
            rgba(17, 14, 12, 0.6) 0%,
            rgba(17, 14, 12, 0) 34%
        ),
        linear-gradient(
            180deg,
            rgba(17, 14, 12, 0) 46%,
            rgba(21, 18, 16, 0.86) 84%,
            #151210 100%
        );
}

.pc-hero__inner {
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

.pc-hero__title {
    margin: 0;
    color: var(--pc-ink);
    font-family: var(--font-display);
    font-size: clamp(34px, 4.2vw, 52px);
    font-weight: 400;
    line-height: 1.35;
    text-shadow: 0 2px 18px rgba(0, 0, 0, 0.45);

    span {
        display: block;
    }
}

.pc-hero__eyebrow {
    margin: 18px 0 0;
    color: var(--pc-gold);
    font-size: 12px;
    letter-spacing: 0.22em;
    text-transform: uppercase;
}

/* 磨砂深墨细金边扫码浮卡 */
.pc-hero__qr-card {
    flex-shrink: 0;
    width: 172px;
    padding: 14px;
    box-sizing: border-box;
    border: 1px solid rgba(201, 169, 110, 0.34);
    border-radius: 12px;
    background: rgba(24, 20, 17, 0.78);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    color: var(--pc-ink-2);
    font-family: inherit;
    text-align: center;
    cursor: pointer;
    transition: border-color var(--dur-fast) var(--ease-out);

    &:hover {
        border-color: var(--pc-gold);
    }
}

.pc-hero__qr-frame {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 136px;
    height: 136px;
    overflow: hidden;
    border-radius: 6px;
    background: #faf6ee;

    img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
}

.pc-hero__qr-empty {
    color: var(--c-ink-3);
    font-size: 11px;
}

.pc-hero__qr-caption {
    display: block;
    margin-top: 10px;
    color: var(--pc-ink-2);
    font-size: 11px;
    letter-spacing: 0.04em;
}

/* ===== 模块 2：精选现场（非对称 1+2 画册） ===== */
.pc-gallery {
    padding: 72px 0 84px;
}

.pc-gallery__head {
    margin-bottom: 40px;
}

.pc-gallery__eyebrow {
    margin: 0;
    color: var(--pc-gold);
    font-size: 12px;
    letter-spacing: 0.2em;
    text-transform: uppercase;
}

.pc-gallery__title {
    margin: 12px 0 0;
    color: var(--pc-ink);
    font-family: var(--font-display);
    font-size: 28px;
    font-weight: 400;
    line-height: 1.3;
}

.pc-gallery__grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 22px;
    align-items: stretch;
}

.pc-gallery__feature {
    display: flex;
    flex-direction: column;
}

.pc-gallery__stack {
    display: flex;
    flex-direction: column;
    gap: 22px;
}

.pc-gallery__cell {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    min-height: 0;
}

.pc-gallery__media {
    overflow: hidden;
    border-radius: 2px;
    background: var(--pc-surface);

    img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
}

.pc-gallery__media--tall {
    aspect-ratio: 4 / 4.35;
}

.pc-gallery__media--wide {
    flex: 1 1 auto;
    min-height: 0;
    aspect-ratio: 16 / 9;
}

.pc-gallery__placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    color: var(--pc-ink-3);
    font-size: 13px;
    letter-spacing: 0.12em;
}

/* 悬停微缩放仅对接受动效的用户启用 */
@media (prefers-reduced-motion: no-preference) {
    .pc-gallery__media img {
        transition: transform var(--dur-base) var(--ease-out);
    }

    .pc-gallery__media:hover img {
        transform: scale(1.03);
    }
}

.pc-gallery__scene {
    color: var(--pc-gold);
    font-size: 11px;
    letter-spacing: 0.18em;
    text-transform: uppercase;
}

.pc-gallery__caption {
    padding-top: 12px;

    h3 {
        margin: 6px 0 0;
        color: var(--pc-ink);
        font-family: var(--font-display);
        font-size: 20px;
        font-weight: 400;
        line-height: 1.3;
    }
}

.pc-gallery__cell .pc-gallery__caption h3 {
    font-size: 17px;
}

/* ===== 模块 3：小程序引导 ===== */
.pc-miniprogram {
    padding: 84px 0 96px;
}

.pc-miniprogram__inner {
    display: grid;
    grid-template-columns: minmax(0, 1.12fr) minmax(0, 0.88fr);
    gap: 52px;
    align-items: center;
}

/* 错落双联深钛框手机样机 */
.pc-miniprogram__phones {
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

.mp-phone {
    box-sizing: border-box;
    padding: 8px;
    border: 1px solid rgba(201, 169, 110, 0.26);
    border-radius: 28px;
    background: linear-gradient(160deg, #2c2723 0%, #1a1613 100%);
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.45);
}

.mp-phone__screen {
    overflow: hidden;
    border-radius: 21px;
    background: var(--c-bg);

    > img {
        display: block;
        width: 100%;
        height: auto;
        aspect-ratio: 9 / 19.2;
        object-fit: cover;
        object-position: top center;
    }
}

.mp-phone--back {
    position: relative;
    z-index: 1;
    flex: 0 0 auto;
    width: 214px;
    margin-top: 38px;
    border-color: rgba(201, 169, 110, 0.22);
    opacity: 0.94;
}

.mp-phone--front {
    position: relative;
    z-index: 2;
    flex: 0 0 auto;
    width: 240px;
    margin: 0 0 0 -16px;
    border-color: rgba(201, 169, 110, 0.42);
    box-shadow: -16px 24px 52px rgba(0, 0, 0, 0.58);
}

@media (prefers-reduced-motion: no-preference) {
    .mp-phone--back {
        transition:
            transform var(--dur-base) var(--ease-out),
            opacity var(--dur-base) var(--ease-out),
            border-color var(--dur-base) var(--ease-out);

        &:hover {
            transform: translateY(-4px);
            opacity: 1;
            border-color: rgba(201, 169, 110, 0.38);
        }
    }
}

/* 象牙白微缩小程序界面（与 newuniapp 同源观感） */
.mp-screen {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 12px 12px 14px;
    color: var(--c-ink);
    font-family: var(--font-sans);
}

.mp-screen__bar {
    display: flex;
    align-items: center;
    gap: 6px;

    strong {
        color: var(--c-ink);
        font-family: var(--font-display);
        font-size: 11px;
        font-weight: 500;
        line-height: 1;
    }
}

.mp-screen__seal {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 16px;
    height: 16px;
    border-radius: 3px;
    background: linear-gradient(145deg, #dfc48e 0%, #9b7740 100%);
    color: #181614;
    font-family: var(--font-display);
    font-size: 9px;
    line-height: 1;
}

.mp-screen__bar-en {
    margin-left: auto;
    color: var(--c-ink-3);
    font-family: var(--font-display);
    font-size: 8px;
    letter-spacing: 0.14em;
    line-height: 1;
    text-transform: uppercase;
}

.mp-screen__hero {
    overflow: hidden;
    aspect-ratio: 16 / 10;
    border-radius: 8px;
    background: var(--c-surface-2);

    img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
}

.mp-screen__hero-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: var(--c-ink-3);
    font-size: 10px;
}

.mp-screen__section {
    display: flex;
    align-items: baseline;
    gap: 6px;
}

.mp-screen__section-title {
    color: var(--c-ink);
    font-size: 11px;
    font-weight: 500;
    line-height: 1;
}

.mp-screen__section-en {
    color: var(--c-ink-3);
    font-family: var(--font-display);
    font-size: 8px;
    letter-spacing: 0.14em;
    line-height: 1;
    text-transform: uppercase;
}

.mp-screen__hosts {
    display: flex;
    gap: 8px;
}

.mp-screen__host {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
}

.mp-screen__avatar {
    display: block;
    width: 34px;
    height: 34px;
    overflow: hidden;
    border-radius: 50%;
    background: var(--c-surface-2);

    img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
}

.mp-screen__tag {
    padding: 2px 6px;
    border-radius: 999px;
    background: var(--c-brand-bg);
    color: var(--c-brand-text);
    font-size: 8px;
    line-height: 1.4;
    white-space: nowrap;
}

.mp-screen__dates {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.mp-screen__date {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 8px;
    border-radius: 6px;
    background: var(--c-surface);

    strong {
        color: var(--c-ink);
        font-size: 10px;
        font-weight: 500;
        line-height: 1.2;
    }
}

.mp-screen__status {
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

.mp-screen__cta {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 26px;
    border-radius: 999px;
    background: var(--c-brand);
    color: #181614;
    font-size: 10px;
    font-weight: 500;
    letter-spacing: 0.08em;
}

/* 右列文案与亮点 */
.pc-miniprogram__title {
    margin: 0;
    color: var(--pc-ink);
    font-family: var(--font-display);
    font-size: clamp(32px, 3.6vw, 44px);
    font-weight: 400;
    line-height: 1.3;

    span {
        display: block;
    }
}

.pc-miniprogram__highlights {
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
        color: var(--pc-ink);
        font-size: 15px;
        font-weight: 500;
        line-height: 1.3;
    }

    span {
        display: block;
        margin-top: 5px;
        color: var(--pc-ink-3);
        font-family: var(--font-display);
        font-size: 10px;
        letter-spacing: 0.14em;
        line-height: 1;
        text-transform: uppercase;
    }
}

.pc-miniprogram__dot {
    flex-shrink: 0;
    width: 6px;
    height: 6px;
    margin-top: 7px;
    border-radius: 50%;
    background: var(--pc-gold);
}

/* 高定二维码底座：暗金微衬方框 + 底座微光椭圆投影 */
.pc-miniprogram__pedestal {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-top: 44px;
    text-align: center;
}

.pc-miniprogram__qr-box {
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
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 4px;
        background: #faf6ee;
    }

    /* 底座微光椭圆投影层 */
    &::after {
        content: '';
        position: absolute;
        left: 50%;
        bottom: -22px;
        z-index: -1;
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

.pc-miniprogram__qr-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    border-radius: 4px;
    background: #faf6ee;
    color: var(--c-ink-3);
    font-size: 12px;
}

.pc-miniprogram__qr-caption {
    margin: 26px 0 0;
    color: var(--pc-ink-2);
    font-size: 13px;
}

.pc-miniprogram__qr-name {
    margin: 8px 0 0;
    color: var(--pc-ink-3);
    font-size: 12px;
}

/* ===== 三栏建筑感深墨页脚 ===== */
.pc-footer {
    padding: 44px 0 28px;
    border-top: 1px solid var(--pc-line);
    background: var(--pc-bg-deep);
}

.pc-footer__grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
    gap: 24px 32px;
    align-items: start;
}

.pc-footer__contact {
    p {
        margin: 0 0 8px;
        color: var(--pc-ink-3);
        font-size: 13px;
        line-height: 1.7;

        span {
            margin-right: 10px;
            color: var(--pc-ink-2);
        }
    }
}

.pc-footer__brand {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;

    .pc-footer__logo {
        display: block;
        height: 36px;
        width: auto;
        max-width: 140px;
        object-fit: contain;
    }

    .pc-brand-seal {
        width: 36px;
        height: 36px;
        font-size: 17px;
    }

    strong {
        color: var(--pc-ink);
        font-family: var(--font-display);
        font-size: 16px;
        font-weight: 400;
        letter-spacing: 0.12em;
    }
}

.pc-footer__aside {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 10px;
}

.pc-footer__slogan {
    color: var(--pc-ink-2);
    font-family: var(--font-display);
    font-size: 12px;
    letter-spacing: 0.16em;
    text-transform: uppercase;
}

.pc-footer__links {
    display: flex;
    gap: 16px;

    a {
        color: var(--pc-ink-3);
        font-size: 13px;
        text-decoration: none;
        transition: color var(--dur-fast) var(--ease-out);

        &:hover {
            color: var(--pc-gold);
        }
    }
}

.pc-footer__copyright {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 6px 18px;
    margin-top: 36px;
    color: var(--pc-ink-3);
    font-size: 12px;

    a {
        color: var(--pc-ink-3);
        text-decoration: none;
        transition: color var(--dur-fast) var(--ease-out);

        &:hover {
            color: var(--pc-gold);
        }
    }
}

/* ===== 原生扫码弹窗（暖墨暗金质感） =====
   注意：绝不无条件写 display，弹窗关闭态必须保持 UA 的 display: none */
.mnp-dialog {
    width: 380px;
    max-width: calc(100vw - 32px);
    padding: 0;
    border: 1px solid rgba(201, 169, 110, 0.48);
    border-radius: 16px;
    background: var(--pc-surface, #1b1714);
    color: var(--pc-ink, #efe5d3);
    box-shadow: 0 24px 64px rgba(0, 0, 0, 0.72);
}

.mnp-dialog[open] {
    display: block;
    max-height: calc(100vh - 48px);
    overflow-y: auto;
}

.mnp-dialog:modal {
    overscroll-behavior: contain;
}

.mnp-dialog::backdrop {
    overflow: hidden;
    overscroll-behavior: contain;
    background: rgba(8, 7, 6, 0.84);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.mnp-dialog__body {
    position: relative;
    padding: 30px 28px 28px;
    text-align: center;
}

.mnp-dialog__close-form {
    margin: 0;
}

.mnp-dialog__close {
    position: absolute;
    top: 14px;
    right: 14px;
    width: 30px;
    height: 30px;
    padding: 0;
    border: 1px solid rgba(201, 169, 110, 0.36);
    border-radius: 50%;
    background: rgba(239, 229, 211, 0.1);
    color: #efe5d3;
    font-family: inherit;
    font-size: 16px;
    line-height: 1;
    cursor: pointer;
    transition:
        border-color var(--dur-fast) var(--ease-out),
        color var(--dur-fast) var(--ease-out);

    &:hover {
        border-color: var(--pc-gold);
        color: var(--pc-gold);
    }
}

.mnp-dialog__title {
    margin: 0;
    color: #f5ecdc;
    font-family: var(--font-display);
    font-size: 20px;
    font-weight: 500;
    line-height: 1.3;
}

.mnp-dialog__qr {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 240px;
    max-width: 100%;
    height: 240px;
    margin: 20px auto 0;
    box-sizing: border-box;
    padding: 20px;
    border: 1px solid var(--pc-line);
    border-radius: 14px;
    background: rgba(201, 169, 110, 0.1);

    img {
        display: block;
        width: 200px;
        height: 200px;
        object-fit: cover;
        border-radius: 6px;
        background: #faf6ee;
    }
}

.mnp-dialog__qr-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 200px;
    height: 200px;
    border-radius: 6px;
    background: #faf6ee;
    color: var(--c-ink-3);
    font-size: 13px;
}

.mnp-dialog__caption {
    margin: 16px 0 0;
    color: #d6cbbd;
    font-size: 13px;
}

.mnp-dialog__name {
    margin: 6px 0 0;
    color: #e4cc9a;
    font-size: 14px;
    font-weight: 500;
}

.mnp-dialog__done-form {
    margin: 22px 0 0;
}

.mnp-dialog__done {
    width: 100%;
    height: 42px;
    padding: 0;
    border: 1px solid rgba(201, 169, 110, 0.58);
    border-radius: 999px;
    background: rgba(201, 169, 110, 0.2);
    color: #f5ecdc;
    font-family: inherit;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition:
        background-color var(--dur-fast) var(--ease-out),
        border-color var(--dur-fast) var(--ease-out),
        color var(--dur-fast) var(--ease-out);

    &:hover {
        background: #c9a96e;
        border-color: #c9a96e;
        color: #181614;
    }
}

/* ===== 响应式（桌面/笔记本降级） ===== */
@media (max-width: 959px) {
    .pc-nav__links {
        display: none;
    }

    .pc-hero__inner {
        flex-direction: column;
        align-items: flex-start;
        padding-top: 96px;
    }

    .pc-gallery__grid {
        grid-template-columns: minmax(0, 1fr);
    }

    .pc-miniprogram__inner {
        grid-template-columns: minmax(0, 1fr);
        gap: 44px;
    }

    .pc-miniprogram__highlights {
        flex-wrap: wrap;
        gap: 18px 24px;
    }

    .pc-footer__grid {
        grid-template-columns: minmax(0, 1fr);
        justify-items: center;
        text-align: center;
    }

    .pc-footer__aside {
        align-items: center;
    }
}

@media (max-width: 560px) {
    .mp-phone {
        padding: 6px;
        border-radius: 22px;
    }

    .mp-phone__screen {
        border-radius: 17px;
    }

    .mp-phone--back {
        width: 158px;
        margin-top: 24px;
    }

    .mp-phone--front {
        width: 178px;
        margin-left: -12px;
    }
}
</style>
