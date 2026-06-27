<template>
    <main class="pc-enterprise-home">
        <section v-if="isEnabled(widgetMap['pc-hero'])" id="hero" class="enterprise-hero">
            <div class="enterprise-hero__image">
                <img v-if="heroImage" :src="heroImage" :alt="heroContent.image_alt || heroContent.title" />
                <div v-else class="enterprise-image-placeholder">首屏主图</div>
            </div>
            <div class="enterprise-hero__veil"></div>
            <header class="enterprise-header">
                <div class="enterprise-shell enterprise-header__inner">
                    <a class="enterprise-header__brand" href="#hero" aria-label="回到首页">
                        <span>{{ heroContent.brand_name }}</span>
                        <em>{{ heroContent.brand_tagline }}</em>
                    </a>
                    <nav class="enterprise-header__nav" aria-label="企业展示导航">
                        <a v-for="item in navItems" :key="item.href" :href="item.href">{{ item.label }}</a>
                    </nav>
                    <a class="enterprise-header__action" href="#contact">预约沟通</a>
                </div>
            </header>

            <div class="enterprise-shell enterprise-hero__inner">
                <div class="enterprise-hero__copy">
                    <div class="enterprise-eyebrow enterprise-eyebrow--light">{{ heroContent.eyebrow }}</div>
                    <h1>{{ heroContent.title }}</h1>
                    <p class="enterprise-hero__subtitle">{{ heroContent.subtitle }}</p>
                    <p class="enterprise-hero__description">{{ heroContent.description }}</p>
                    <div class="enterprise-hero__actions">
                        <a class="enterprise-hero__primary-action" href="#contact">{{ heroContent.primary_action }}</a>
                        <a class="enterprise-hero__secondary-action" href="#gallery">{{ heroContent.secondary_action }}</a>
                    </div>
                    <div class="enterprise-hero__badges" aria-label="服务标签">
                        <span v-for="item in heroBadges" :key="item">{{ item }}</span>
                    </div>
                </div>

                <aside class="enterprise-hero__panel">
                    <span>{{ heroContent.panel_eyebrow }}</span>
                    <strong>{{ heroContent.image_caption }}</strong>
                    <p>{{ heroContent.panel_description }}</p>
                </aside>

                <div class="enterprise-hero__stats" aria-label="服务数据">
                    <article v-for="(item, index) in heroStats" :key="`${item.label}-${index}`">
                        <strong>{{ item.value }}</strong>
                        <span>{{ item.label }}</span>
                    </article>
                </div>

                <a class="enterprise-hero__cue" href="#about" aria-label="查看品牌介绍">
                    <span></span>
                    向下了解
                </a>
            </div>
        </section>

        <section v-if="isEnabled(widgetMap['pc-about'])" id="about" class="enterprise-section enterprise-about">
            <div class="enterprise-shell enterprise-about__inner">
                <div class="enterprise-about__copy">
                    <div class="enterprise-eyebrow">{{ aboutContent.eyebrow }}</div>
                    <h2>{{ aboutContent.title }}</h2>
                    <p class="enterprise-lead">{{ aboutContent.subtitle }}</p>
                    <p class="enterprise-text">{{ aboutContent.description }}</p>
                    <div class="enterprise-about__points">
                        <article v-for="(item, index) in aboutPoints" :key="item">
                            <span>{{ String(index + 1).padStart(2, '0') }}</span>
                            <strong>{{ item }}</strong>
                        </article>
                    </div>
                </div>

                <div class="enterprise-about__media">
                    <div class="enterprise-about__image">
                        <img v-if="aboutImage" :src="aboutImage" :alt="aboutContent.image_alt || aboutContent.title" />
                        <div v-else class="enterprise-image-placeholder">品牌介绍图</div>
                    </div>
                    <div class="enterprise-about__caption">
                        <strong>{{ aboutContent.caption_title }}</strong>
                        <span>{{ aboutContent.caption_text }}</span>
                    </div>
                </div>
            </div>
        </section>

        <section v-if="isEnabled(widgetMap['pc-advantages'])" id="advantages" class="enterprise-section enterprise-advantages">
            <div class="enterprise-shell">
                <div class="enterprise-section-head enterprise-section-head--dark">
                    <div>
                        <div class="enterprise-eyebrow enterprise-eyebrow--light">{{ advantagesContent.eyebrow }}</div>
                        <h2>{{ advantagesContent.title }}</h2>
                    </div>
                    <p>{{ advantagesContent.subtitle }}</p>
                </div>
                <div class="enterprise-advantages__grid">
                    <article v-for="(item, index) in advantageItems" :key="`${item.title}-${index}`">
                        <span>{{ item.kicker || String(index + 1).padStart(2, '0') }}</span>
                        <h3>{{ item.title }}</h3>
                        <p>{{ item.description }}</p>
                    </article>
                </div>
            </div>
        </section>

        <section v-if="isEnabled(widgetMap['pc-gallery'])" id="gallery" class="enterprise-section enterprise-gallery">
            <div class="enterprise-shell">
                <div class="enterprise-section-head">
                    <div>
                        <div class="enterprise-eyebrow">{{ galleryContent.eyebrow }}</div>
                        <h2>{{ galleryContent.title }}</h2>
                    </div>
                    <p>{{ galleryContent.subtitle }}</p>
                </div>
                <div class="enterprise-gallery__grid">
                    <article
                        v-for="(item, index) in galleryItems"
                        :key="`${item.title}-${index}`"
                        :class="{ 'is-featured': index === 0 }"
                    >
                        <div class="enterprise-gallery__image">
                            <img
                                v-if="getImageUrl(item.image)"
                                :src="getImageUrl(item.image)"
                                :alt="item.alt || item.title"
                                loading="lazy"
                            />
                            <div v-else class="enterprise-image-placeholder">展示图</div>
                        </div>
                        <div class="enterprise-gallery__content">
                            <span>{{ item.scene || String(index + 1).padStart(2, '0') }}</span>
                            <h3>{{ item.title }}</h3>
                            <p>{{ item.description }}</p>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section v-if="isEnabled(widgetMap['pc-stats'])" id="track-record" class="enterprise-section enterprise-stats">
            <div class="enterprise-shell enterprise-stats__inner">
                <div class="enterprise-stats__copy">
                    <div class="enterprise-eyebrow">{{ statsContent.eyebrow }}</div>
                    <h2>{{ statsContent.title }}</h2>
                    <p>{{ statsContent.subtitle }}</p>
                </div>
                <div class="enterprise-stats__list">
                    <article v-for="(item, index) in statsItems" :key="`${item.label}-${index}`">
                        <strong>{{ item.value }}</strong>
                        <span>{{ item.label }}</span>
                        <p>{{ item.description }}</p>
                    </article>
                </div>
            </div>
        </section>

        <section v-if="isEnabled(widgetMap['pc-contact'])" id="contact" class="enterprise-section enterprise-contact">
            <div class="enterprise-shell enterprise-contact__inner">
                <div class="enterprise-contact__copy">
                    <div class="enterprise-eyebrow enterprise-eyebrow--light">{{ contactContent.eyebrow }}</div>
                    <h2>{{ contactContent.title }}</h2>
                    <p>{{ contactContent.subtitle }}</p>
                    <a class="enterprise-contact__tel" :href="`tel:${contactContent.phone}`">
                        {{ contactContent.action_text }}
                    </a>
                </div>
                <div class="enterprise-contact__info">
                    <div v-for="item in contactRows" :key="item.label">
                        <span>{{ item.label }}</span>
                        <strong>{{ item.value || '待后台完善' }}</strong>
                    </div>
                    <p>{{ contactContent.remark }}</p>
                </div>
                <div class="enterprise-contact__qr">
                    <img v-if="contactQrcode" :src="contactQrcode" :alt="contactContent.qrcode_alt || '联系二维码'" loading="lazy" />
                    <span v-else>二维码</span>
                </div>
            </div>
        </section>

        <footer class="enterprise-footer" aria-label="备案信息">
            <div class="enterprise-shell enterprise-footer__inner">
                <div class="enterprise-footer__mark">
                    <strong>{{ heroContent.brand_name }}</strong>
                    <span>{{ contactContent.footer_slogan }}</span>
                </div>
                <div v-if="copyrightItems.length" class="enterprise-footer__links">
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
                </div>
            </div>
        </footer>
    </main>
</template>

<script lang="ts" setup>
import { getIndex } from '@/api/shop'
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

const defaultContent = {
    hero: {
        enabled: 1,
        eyebrow: 'GLINSHE CEREMONY HOUSE',
        brand_name: '格林社婚礼服务',
        brand_tagline: 'Ceremony House',
        title: '让婚礼现场成为值得回看的仪式',
        subtitle: '以高级审美、稳健控场和细致统筹，呈现婚礼仪式与重要活动现场。',
        description: 'PC 首页定位为企业展示窗口，集中呈现品牌气质、主持能力、仪式统筹、案例现场与联系信息。',
        image: '/resource/image/adminapi/default/banner003.png',
        image_alt: '格林社婚礼仪式现场',
        image_caption: '婚礼主持 · 仪式统筹 · 活动呈现',
        panel_eyebrow: 'Scene Direction',
        panel_description: '从沟通、脚本、音乐节点到现场控场，保持审美和情绪在同一个节奏里。',
        primary_action: '联系顾问',
        secondary_action: '查看案例',
        badges: ['婚礼主持', '仪式统筹', '高端庆典']
    },
    about: {
        enabled: 1,
        eyebrow: 'ABOUT US',
        title: '不是把流程走完，而是让每一段关系被看见',
        subtitle: '我们为婚礼仪式、品牌庆典、企业活动与私享宴会提供主持表达和现场流程统筹。',
        description: '从前期沟通、仪式脚本、音乐节点到现场控场，团队以成熟流程协调新人、家庭、场地方和执行团队，让现场节奏自然、情绪饱满、表达得体。',
        image: '/resource/image/adminapi/default/banner002.png',
        image_alt: '格林社品牌服务现场',
        caption_title: '仪式不是流程清单',
        caption_text: '而是人物关系、现场秩序与情绪峰值的共同呈现。',
        points: ['需求沟通', '仪式脚本', '现场控场']
    },
    advantages: {
        enabled: 1,
        eyebrow: 'CAPABILITIES',
        title: '从表达、节奏、秩序到画面统一落地',
        subtitle: '适配婚礼仪式、答谢晚宴、企业庆典、品牌发布等不同场景。',
        data: [
            { kicker: 'Script', title: '仪式文本定制', description: '围绕人物关系与活动目标，打磨有分寸感的主持文本。' },
            { kicker: 'Rhythm', title: '全流程节奏管理', description: '梳理环节、人员、物料与时间点，降低现场不确定性。' },
            { kicker: 'Aesthetic', title: '现场审美协同', description: '让文案、音乐、影像与仪式氛围保持统一的品牌语气。' }
        ]
    },
    gallery: {
        enabled: 1,
        eyebrow: 'SHOWCASE',
        title: '真实现场中的仪式质感',
        subtitle: '用于展示婚礼仪式、庆典活动、团队服务和现场统筹的专业质感。',
        data: [
            { image: '/resource/image/adminapi/default/banner003.png', alt: '婚礼仪式现场', scene: 'Wedding', title: '婚礼仪式现场', description: '以稳定表达承接情绪，让重要瞬间自然发生。' },
            { image: '/resource/image/adminapi/default/banner001.png', alt: '高端庆典现场', scene: 'Event', title: '高端庆典现场', description: '兼顾秩序、节奏与仪式感，强化现场记忆点。' },
            { image: '/resource/image/adminapi/default/banner002.png', alt: '团队统筹服务', scene: 'Team', title: '团队统筹服务', description: '提前拆解每个细节，让执行在现场更从容。' }
        ]
    },
    stats: {
        enabled: 1,
        eyebrow: 'TRACK RECORD',
        title: '长期服务沉淀',
        subtitle: '用持续稳定的交付能力，支撑每一次重要亮相。',
        data: [
            { value: '1000+', label: '活动服务经验', description: '覆盖婚礼、庆典与商务场景' },
            { value: '98%', label: '客户好评率', description: '来自长期合作与现场反馈' },
            { value: '30+', label: '覆盖城市', description: '支持跨区域活动执行' }
        ]
    },
    contact: {
        enabled: 1,
        eyebrow: 'CONTACT',
        title: '把重要时刻交给更稳的现场团队',
        subtitle: '欢迎通过电话、二维码或地址信息进一步了解团队。',
        phone: '1888888888',
        service_time: '周一至周日 09:30 - 19:00',
        address: '请在后台装修中填写企业地址',
        qrcode: '/resource/image/adminapi/default/kefu01.png',
        qrcode_alt: '格林社联系二维码',
        remark: '欢迎通过上述方式进一步了解团队服务与合作信息。',
        action_text: '拨打电话预约',
        footer_slogan: '婚礼主持 · 仪式统筹 · 活动呈现'
    }
}

const navItems = [
    { label: '品牌介绍', href: '#about' },
    { label: '服务能力', href: '#advantages' },
    { label: '案例现场', href: '#gallery' },
    { label: '联系信息', href: '#contact' }
]

const normalizeList = <T = any>(value: any): T[] => {
    if (Array.isArray(value)) return value
    if (value && typeof value === 'object') return Object.values(value)
    return []
}

const getImageUrl = (url?: string) => {
    if (!url) return ''
    if (/^https?:\/\//.test(url)) return url
    return appStore.getImageUrl(url)
}

const pageWidgets = computed(() => {
    const data = pageData.value?.page?.data
    if (Array.isArray(data)) {
        return data
    }
    if (typeof data !== 'string') {
        return []
    }
    try {
        const parsedData = JSON.parse(data || '[]')
        return Array.isArray(parsedData) ? parsedData : []
    } catch (error) {
        return []
    }
})

const widgetMap = computed<Record<string, any>>(() => {
    return pageWidgets.value.reduce((map: Record<string, any>, widget: any) => {
        if (widget?.name && !map[widget.name]) {
            map[widget.name] = widget
        }
        return map
    }, {})
})

const isEnabled = (widget?: any) => Number(widget?.content?.enabled ?? 1) !== 0
const getContent = <T extends Record<string, any>>(name: string, fallback: T): T => ({
    ...fallback,
    ...(widgetMap.value[name]?.content || {})
})

const heroContent = computed(() => getContent('pc-hero', defaultContent.hero))
const aboutContent = computed(() => getContent('pc-about', defaultContent.about))
const advantagesContent = computed(() => getContent('pc-advantages', defaultContent.advantages))
const galleryContent = computed(() => getContent('pc-gallery', defaultContent.gallery))
const statsContent = computed(() => getContent('pc-stats', defaultContent.stats))
const contactContent = computed(() => getContent('pc-contact', defaultContent.contact))
const heroImage = computed(() => getImageUrl(heroContent.value.image))
const aboutImage = computed(() => getImageUrl(aboutContent.value.image))
const contactQrcode = computed(() => getImageUrl(contactContent.value.qrcode))
const heroBadges = computed(() => normalizeList<string>(heroContent.value.badges).filter(Boolean).slice(0, 4))
const aboutPoints = computed(() => normalizeList<string>(aboutContent.value.points).filter(Boolean).slice(0, 4))
const advantageItems = computed(() => normalizeList<any>(advantagesContent.value.data).filter(Boolean).slice(0, 4))
const galleryItems = computed(() => normalizeList<any>(galleryContent.value.data).filter(Boolean).slice(0, 3))
const statsItems = computed(() => normalizeList<any>(statsContent.value.data).filter(Boolean).slice(0, 3))
const heroStats = computed(() => statsItems.value.slice(0, 3))
const contactRows = computed(() => [
    { label: '联系电话', value: contactContent.value.phone },
    { label: '服务时间', value: contactContent.value.service_time },
    { label: '企业地址', value: contactContent.value.address }
])
const copyrightItems = computed(() =>
    normalizeList<{ key?: string; value?: string }>(appStore.getCopyrightConfig)
        .map((item) => ({
            key: String(item?.key || '').trim(),
            value: String(item?.value || '').trim()
        }))
        .filter((item) => item.key)
)
</script>

<style lang="scss" scoped>
.pc-enterprise-home {
    --pc-ink: #17130f;
    --pc-ink-soft: #2c241d;
    --pc-charcoal: #0f0c09;
    --pc-gold: #d8b16a;
    --pc-gold-deep: #a77a34;
    --pc-paper: #fffaf1;
    --pc-sand: #f8f2e8;
    --pc-muted: #71685c;
    --pc-line: rgba(23, 19, 15, 0.14);
    --pc-light-line: rgba(255, 250, 241, 0.16);
    min-width: 1200px;
    min-height: 100vh;
    color: var(--pc-ink);
    background:
        linear-gradient(90deg, rgba(23, 19, 15, 0.04) 1px, transparent 1px),
        var(--pc-sand);
    background-size: 96px 96px;
    font-family: 'PingFang SC', 'Microsoft YaHei', 'Hiragino Sans GB', Arial, sans-serif;
    scroll-behavior: smooth;
}

.enterprise-shell {
    width: 1200px;
    margin: 0 auto;
    box-sizing: border-box;
}

.enterprise-eyebrow {
    color: var(--pc-gold-deep);
    font-size: 13px;
    font-weight: 800;
    letter-spacing: 0;

    &--light {
        color: var(--pc-gold);
    }
}

.enterprise-section {
    padding: 0;
}

.enterprise-section-head {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 410px;
    gap: 78px;
    align-items: end;

    h2 {
        margin: 15px 0 0;
        color: var(--pc-ink);
        font-size: 44px;
        line-height: 1.14;
        font-weight: 900;
        letter-spacing: 0;
    }

    p {
        margin: 0;
        color: #6f665a;
        font-size: 16px;
        line-height: 1.85;
    }

    &--dark {
        h2 {
            color: var(--pc-paper);
        }

        p {
            color: rgba(255, 250, 241, 0.7);
        }
    }
}

.enterprise-image-placeholder {
    width: 100%;
    height: 100%;
    background: #16120f;
    color: rgba(255, 255, 255, 0.72);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.enterprise-header {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    z-index: 4;
    border-bottom: 1px solid rgba(255, 250, 241, 0.16);
    background: linear-gradient(180deg, rgba(12, 9, 7, 0.58), rgba(12, 9, 7, 0));

    &__inner {
        height: 86px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 68px;
    }

    &__brand {
        color: var(--pc-paper);
        text-decoration: none;
        display: grid;
        gap: 4px;

        span {
            font-size: 20px;
            font-weight: 900;
            letter-spacing: 0;
        }

        em {
            color: rgba(216, 177, 106, 0.78);
            font-size: 12px;
            font-style: normal;
            font-weight: 800;
        }
    }

    &__nav {
        display: flex;
        align-items: center;
        gap: 30px;

        a {
            position: relative;
            color: rgba(255, 250, 241, 0.82);
            font-size: 14px;
            text-decoration: none;
            transition: color 0.22s ease;

            &::after {
                content: '';
                position: absolute;
                left: 0;
                right: 0;
                bottom: -10px;
                height: 1px;
                background: var(--pc-gold);
                transform: scaleX(0);
                transform-origin: left center;
                transition: transform 0.24s ease;
            }

            &:hover {
                color: var(--pc-paper);
            }

            &:hover::after {
                transform: scaleX(1);
            }
        }
    }

    &__action {
        height: 38px;
        padding: 0 18px;
        border: 1px solid rgba(216, 177, 106, 0.72);
        border-radius: 6px;
        color: var(--pc-gold);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 900;
        text-decoration: none;
        transition:
            background 0.22s ease,
            color 0.22s ease,
            transform 0.22s ease;

        &:hover {
            color: var(--pc-ink);
            background: var(--pc-gold);
            transform: translateY(-1px);
        }
    }
}

.enterprise-hero {
    position: relative;
    min-height: 820px;
    color: var(--pc-paper);
    background: #15100d;
    overflow: hidden;

    &__image,
    &__veil {
        position: absolute;
        inset: 0;
    }

    &__image {
        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            filter: saturate(0.88) contrast(1.08);
            transform: scale(1.02);
        }
    }

    &__veil {
        background:
            radial-gradient(circle at 76% 28%, rgba(216, 177, 106, 0.22), transparent 28%),
            linear-gradient(90deg, rgba(10, 8, 6, 0.94) 0%, rgba(10, 8, 6, 0.76) 36%, rgba(10, 8, 6, 0.18) 100%),
            linear-gradient(180deg, rgba(10, 8, 6, 0.52) 0%, rgba(10, 8, 6, 0.1) 45%, rgba(10, 8, 6, 0.9) 100%),
            repeating-linear-gradient(90deg, rgba(255, 250, 241, 0.08) 0, rgba(255, 250, 241, 0.08) 1px, transparent 1px, transparent 160px);
    }

    &__inner {
        position: relative;
        z-index: 2;
        min-height: 820px;
        display: grid;
        grid-template-columns: minmax(0, 690px) 320px;
        grid-template-rows: minmax(0, 1fr) auto;
        gap: 28px 78px;
        align-items: end;
        padding: 168px 68px 54px;
        box-sizing: border-box;
    }

    &__copy {
        padding-bottom: 58px;
    }

    h1 {
        max-width: 650px;
        margin: 22px 0 0;
        color: var(--pc-paper);
        font-size: 76px;
        line-height: 1.02;
        font-weight: 900;
        letter-spacing: 0;
    }

    &__subtitle {
        max-width: 620px;
        margin: 30px 0 0;
        color: rgba(255, 250, 241, 0.94);
        font-size: 24px;
        line-height: 1.58;
        font-weight: 700;
    }

    &__description {
        max-width: 590px;
        margin: 18px 0 0;
        color: rgba(255, 250, 241, 0.68);
        font-size: 16px;
        line-height: 1.92;
    }

    &__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        margin-top: 34px;
    }

    &__primary-action,
    &__secondary-action {
        min-width: 128px;
        height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        padding: 0 20px;
        font-size: 14px;
        font-weight: 900;
        text-decoration: none;
        transition:
            transform 0.22s ease,
            border-color 0.22s ease,
            background 0.22s ease;

        &:hover {
            transform: translateY(-2px);
        }
    }

    &__primary-action {
        color: var(--pc-ink);
        background: var(--pc-gold);
        border: 1px solid var(--pc-gold);
    }

    &__secondary-action {
        color: var(--pc-paper);
        background: rgba(255, 250, 241, 0.08);
        border: 1px solid rgba(255, 250, 241, 0.28);

        &:hover {
            border-color: var(--pc-gold);
            background: rgba(216, 177, 106, 0.16);
        }
    }

    &__badges {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 24px;

        span {
            border: 1px solid rgba(255, 250, 241, 0.22);
            border-radius: 6px;
            padding: 10px 16px;
            color: var(--pc-paper);
            background: rgba(255, 250, 241, 0.08);
            font-size: 13px;
            font-weight: 800;
            backdrop-filter: blur(10px);
        }
    }

    &__panel {
        position: relative;
        align-self: center;
        min-height: 300px;
        padding: 30px 28px;
        border: 1px solid rgba(255, 250, 241, 0.2);
        border-radius: 8px;
        background: rgba(18, 14, 11, 0.58);
        box-shadow: 0 30px 90px rgba(0, 0, 0, 0.28);
        backdrop-filter: blur(16px);

        &::before {
            content: '';
            position: absolute;
            top: -28px;
            right: 28px;
            width: 1px;
            height: 88px;
            background: var(--pc-gold);
        }

        span,
        strong,
        p {
            display: block;
        }

        span {
            color: var(--pc-gold);
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0;
        }

        strong {
            margin-top: 42px;
            color: var(--pc-paper);
            font-size: 25px;
            line-height: 1.34;
            font-weight: 900;
        }

        p {
            margin: 22px 0 0;
            color: rgba(255, 250, 241, 0.68);
            font-size: 14px;
            line-height: 1.85;
        }
    }

    &__stats {
        grid-column: 1 / 3;
        width: 760px;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        border-top: 1px solid rgba(255, 250, 241, 0.16);
        border-radius: 8px;
        border-bottom: 1px solid rgba(255, 250, 241, 0.16);
        overflow: hidden;

        article {
            min-height: 98px;
            padding: 22px 26px;
            border-right: 1px solid rgba(255, 250, 241, 0.16);
            background: rgba(255, 250, 241, 0.055);

            &:last-child {
                border-right: 0;
            }
        }

        strong {
            display: block;
            color: var(--pc-gold);
            font-size: 32px;
            line-height: 1;
            font-weight: 900;
        }

        span {
            display: block;
            margin-top: 12px;
            color: rgba(255, 250, 241, 0.78);
            font-size: 13px;
            font-weight: 800;
        }
    }

    &__cue {
        position: absolute;
        right: 68px;
        bottom: 56px;
        display: flex;
        align-items: center;
        gap: 12px;
        color: rgba(255, 250, 241, 0.72);
        font-size: 13px;
        text-decoration: none;

        span {
            width: 52px;
            height: 1px;
            background: var(--pc-gold);
        }
    }
}

.enterprise-about {
    position: relative;
    background:
        linear-gradient(180deg, var(--pc-sand) 0%, var(--pc-paper) 100%);

    &__inner {
        min-height: 700px;
        display: grid;
        grid-template-columns: minmax(0, 520px) 1fr;
        gap: 88px;
        align-items: center;
        padding: 92px 68px 86px;
        box-sizing: border-box;
    }

    h2 {
        margin: 16px 0 0;
        color: var(--pc-ink);
        font-size: 48px;
        line-height: 1.14;
        font-weight: 900;
        letter-spacing: 0;
    }

    &__points {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1px;
        margin-top: 38px;
        border-radius: 8px;
        background: rgba(167, 122, 52, 0.24);
        overflow: hidden;

        article {
            min-height: 112px;
            padding: 20px 18px;
            background: var(--pc-paper);
        }

        span {
            display: block;
            color: var(--pc-gold-deep);
            font-size: 12px;
            font-weight: 900;
        }

        strong {
            display: block;
            margin-top: 28px;
            color: var(--pc-ink);
            font-size: 18px;
            line-height: 1.35;
            font-weight: 900;
        }
    }

    &__media {
        position: relative;
        min-height: 520px;
    }

    &__image {
        position: absolute;
        top: 0;
        right: 0;
        width: 500px;
        height: 470px;
        border-radius: 8px;
        background: var(--pc-ink);
        overflow: hidden;
        box-shadow: 0 34px 90px rgba(71, 48, 24, 0.18);

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
    }

    &__caption {
        position: absolute;
        left: 0;
        bottom: 0;
        width: 360px;
        padding: 28px 30px;
        border-left: 4px solid var(--pc-gold);
        border-radius: 8px;
        background: var(--pc-ink);
        color: var(--pc-paper);

        strong,
        span {
            display: block;
        }

        strong {
            font-size: 22px;
            line-height: 1.36;
            font-weight: 900;
        }

        span {
            margin-top: 12px;
            color: rgba(255, 250, 241, 0.68);
            font-size: 14px;
            line-height: 1.8;
        }
    }
}

.enterprise-lead {
    margin: 24px 0 0;
    color: #2d251f;
    font-size: 20px;
    line-height: 1.76;
    font-weight: 800;
}

.enterprise-text {
    margin: 18px 0 0;
    color: var(--pc-muted);
    font-size: 15px;
    line-height: 1.98;
}

.enterprise-advantages {
    position: relative;
    color: var(--pc-paper);
    background:
        linear-gradient(135deg, rgba(216, 177, 106, 0.18), rgba(216, 177, 106, 0) 38%),
        repeating-linear-gradient(90deg, rgba(255, 250, 241, 0.055) 0, rgba(255, 250, 241, 0.055) 1px, transparent 1px, transparent 160px),
        var(--pc-ink);

    .enterprise-shell {
        min-height: 640px;
        padding: 88px 68px 92px;
        box-sizing: border-box;
    }

    &__grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
        margin-top: 58px;
        align-items: stretch;

        article {
            position: relative;
            min-height: 260px;
            padding: 34px 32px;
            border: 1px solid rgba(255, 250, 241, 0.16);
            border-radius: 8px;
            background: rgba(255, 250, 241, 0.055);
            overflow: hidden;
            transition:
                transform 0.24s ease,
                background 0.24s ease,
                border-color 0.24s ease;

            &:nth-child(2) {
                transform: translateY(42px);
                background: rgba(216, 177, 106, 0.12);
            }

            &:hover {
                border-color: rgba(216, 177, 106, 0.62);
                background: rgba(216, 177, 106, 0.13);
                transform: translateY(-4px);
            }

            &:nth-child(2):hover {
                transform: translateY(34px);
            }

            &::after {
                content: '';
                position: absolute;
                left: 32px;
                right: 32px;
                bottom: 28px;
                height: 1px;
                background: rgba(216, 177, 106, 0.6);
            }
        }

        span {
            color: var(--pc-gold);
            font-size: 13px;
            font-weight: 900;
        }

        h3 {
            margin: 54px 0 0;
            color: var(--pc-paper);
            font-size: 25px;
            line-height: 1.28;
            font-weight: 900;
        }

        p {
            margin: 18px 0 0;
            color: rgba(255, 250, 241, 0.68);
            font-size: 14px;
            line-height: 1.86;
        }
    }
}

.enterprise-gallery {
    background: var(--pc-paper);

    .enterprise-shell {
        min-height: 760px;
        padding: 94px 68px 100px;
        box-sizing: border-box;
    }

    &__grid {
        display: grid;
        grid-template-columns: 1.2fr 0.9fr;
        grid-template-rows: 255px 255px;
        gap: 18px;
        margin-top: 54px;
    }

    article {
        position: relative;
        min-height: 0;
        border-radius: 8px;
        background: var(--pc-sand);
        overflow: hidden;

        &.is-featured {
            grid-row: 1 / 3;

            .enterprise-gallery__image {
                height: 100%;
            }

            .enterprise-gallery__content {
                left: 30px;
                right: 30px;
                bottom: 30px;
                color: var(--pc-paper);
                background: rgba(23, 19, 15, 0.68);
                backdrop-filter: blur(14px);

                h3 {
                    color: var(--pc-paper);
                    font-size: 28px;
                }

                p {
                    color: rgba(255, 250, 241, 0.74);
                }
            }
        }
    }

    &__image {
        height: 100%;
        background: var(--pc-ink);

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.4s ease;
        }
    }

    article:hover img {
        transform: scale(1.04);
    }

    &__content {
        position: absolute;
        left: 22px;
        right: 22px;
        bottom: 22px;
        padding: 20px 22px;
        border-radius: 8px;
        background: rgba(255, 250, 241, 0.94);

        span {
            color: var(--pc-gold-deep);
            font-size: 13px;
            font-weight: 900;
        }

        h3 {
            margin: 12px 0 0;
            color: var(--pc-ink);
            font-size: 21px;
            line-height: 1.28;
            font-weight: 900;
        }

        p {
            margin: 10px 0 0;
            color: #6f665a;
            font-size: 14px;
            line-height: 1.7;
        }
    }
}

.enterprise-stats {
    background: var(--pc-sand);

    &__inner {
        min-height: 430px;
        display: grid;
        grid-template-columns: 320px minmax(0, 1fr);
        gap: 58px;
        align-items: center;
        padding: 78px 68px;
        box-sizing: border-box;
    }

    h2 {
        margin: 14px 0 0;
        color: var(--pc-ink);
        font-size: 40px;
        line-height: 1.18;
        font-weight: 900;
    }

    &__copy p {
        margin: 18px 0 0;
        color: var(--pc-muted);
        font-size: 15px;
        line-height: 1.84;
    }

    &__list {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        border-top: 1px solid rgba(23, 19, 15, 0.16);
        border-radius: 8px;
        border-bottom: 1px solid rgba(23, 19, 15, 0.16);
        overflow: hidden;
    }

    article {
        min-height: 206px;
        padding: 34px 28px;
        border-right: 1px solid rgba(23, 19, 15, 0.16);
        background: rgba(255, 250, 241, 0.58);

        &:last-child {
            border-right: 0;
        }
    }

    strong {
        display: block;
        color: var(--pc-ink);
        font-size: 54px;
        line-height: 1;
        font-weight: 900;
    }

    span {
        display: block;
        margin-top: 20px;
        color: var(--pc-ink);
        font-size: 16px;
        font-weight: 900;
    }

    article p {
        margin: 12px 0 0;
        color: var(--pc-muted);
        font-size: 13px;
        line-height: 1.7;
    }
}

.enterprise-contact {
    color: var(--pc-paper);
    background:
        linear-gradient(135deg, rgba(216, 177, 106, 0.2), rgba(216, 177, 106, 0) 42%),
        repeating-linear-gradient(90deg, rgba(255, 250, 241, 0.055) 0, rgba(255, 250, 241, 0.055) 1px, transparent 1px, transparent 170px),
        var(--pc-ink);

    &__inner {
        min-height: 560px;
        display: grid;
        grid-template-columns: 430px minmax(0, 1fr) 176px;
        gap: 52px;
        align-items: center;
        padding: 86px 68px;
        box-sizing: border-box;
    }

    h2 {
        margin: 14px 0 0;
        font-size: 44px;
        line-height: 1.14;
        font-weight: 900;
    }

    &__copy p {
        margin: 22px 0 0;
        color: rgba(255, 250, 241, 0.72);
        font-size: 16px;
        line-height: 1.84;
    }

    &__tel {
        margin-top: 26px;
        min-width: 132px;
        height: 44px;
        padding: 0 20px;
        border-radius: 6px;
        color: var(--pc-ink);
        background: var(--pc-gold);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 900;
        text-decoration: none;
        transition:
            transform 0.22s ease,
            background 0.22s ease;

        &:hover {
            transform: translateY(-2px);
            background: #e3c27d;
        }
    }

    &__info {
        display: grid;
        gap: 18px;

        div {
            padding: 0 0 18px 22px;
            border-left: 2px solid rgba(216, 177, 106, 0.7);
            border-radius: 6px;
            border-bottom: 1px solid rgba(255, 250, 241, 0.12);
        }

        span {
            display: block;
            color: rgba(255, 250, 241, 0.54);
            font-size: 13px;
        }

        strong {
            display: block;
            margin-top: 8px;
            color: var(--pc-paper);
            font-size: 18px;
            line-height: 1.55;
            word-break: break-word;
        }

        p {
            margin: 6px 0 0;
            color: rgba(255, 250, 241, 0.62);
            font-size: 13px;
            line-height: 1.75;
        }
    }

    &__qr {
        width: 156px;
        height: 156px;
        padding: 12px;
        border-radius: 8px;
        background: var(--pc-paper);
        color: var(--pc-ink);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 18px 18px 0 rgba(216, 177, 106, 0.18);

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
    }
}

.enterprise-footer {
    color: rgba(255, 250, 241, 0.62);
    background: var(--pc-ink);

    &__inner {
        min-height: 98px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 36px;
        padding: 0 68px;
        border-top: 1px solid rgba(255, 250, 241, 0.12);
        box-sizing: border-box;
    }

    &__mark {
        display: grid;
        gap: 6px;

        strong {
            color: rgba(216, 177, 106, 0.86);
            font-size: 13px;
            font-weight: 900;
        }

        span {
            font-size: 12px;
            color: rgba(255, 250, 241, 0.52);
        }
    }

    &__links {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 10px 20px;
        max-width: 760px;
        font-size: 13px;
        line-height: 1.6;

        a,
        span {
            position: relative;
            color: rgba(255, 250, 241, 0.62);
            text-decoration: none;
        }

        a:hover {
            color: var(--pc-gold);
        }

        a:not(:last-child)::after,
        span:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 50%;
            right: -10px;
            width: 1px;
            height: 12px;
            background: rgba(255, 250, 241, 0.18);
            transform: translateY(-50%);
        }
    }
}
</style>
