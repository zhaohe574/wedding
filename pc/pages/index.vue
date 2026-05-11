<template>
    <main class="pc-enterprise-home">
        <section v-if="isEnabled(widgetMap['pc-hero'])" class="enterprise-hero">
            <div class="enterprise-shell enterprise-hero__inner">
                <div class="enterprise-hero__copy">
                    <div class="enterprise-eyebrow">{{ heroContent.eyebrow }}</div>
                    <h1>{{ heroContent.title }}</h1>
                    <p class="enterprise-hero__subtitle">{{ heroContent.subtitle }}</p>
                    <p class="enterprise-hero__description">{{ heroContent.description }}</p>
                    <div class="enterprise-hero__badges">
                        <span v-for="item in normalizeList<string>(heroContent.badges)" :key="item">{{ item }}</span>
                    </div>
                </div>
                <div class="enterprise-hero__media">
                    <img v-if="heroImage" :src="heroImage" alt="" />
                    <div v-else class="enterprise-image-placeholder">企业首屏图</div>
                    <div v-if="heroContent.image_caption" class="enterprise-hero__caption">
                        {{ heroContent.image_caption }}
                    </div>
                </div>
            </div>
        </section>

        <section v-if="isEnabled(widgetMap['pc-about'])" class="enterprise-section enterprise-about">
            <div class="enterprise-shell enterprise-about__inner">
                <div class="enterprise-about__image">
                    <img v-if="aboutImage" :src="aboutImage" alt="" />
                    <div v-else class="enterprise-image-placeholder">品牌介绍图</div>
                </div>
                <div>
                    <div class="enterprise-eyebrow">{{ aboutContent.eyebrow }}</div>
                    <h2>{{ aboutContent.title }}</h2>
                    <p class="enterprise-lead">{{ aboutContent.subtitle }}</p>
                    <p class="enterprise-text">{{ aboutContent.description }}</p>
                    <div class="enterprise-about__points">
                        <span v-for="item in normalizeList<string>(aboutContent.points)" :key="item">{{ item }}</span>
                    </div>
                </div>
            </div>
        </section>

        <section v-if="isEnabled(widgetMap['pc-advantages'])" class="enterprise-section enterprise-advantages">
            <div class="enterprise-shell">
                <div class="enterprise-section-head enterprise-section-head--dark">
                    <div>
                        <div class="enterprise-eyebrow">{{ advantagesContent.eyebrow }}</div>
                        <h2>{{ advantagesContent.title }}</h2>
                    </div>
                    <p>{{ advantagesContent.subtitle }}</p>
                </div>
                <div class="enterprise-advantages__grid">
                    <article v-for="(item, index) in normalizeList<any>(advantagesContent.data)" :key="`${item.title}-${index}`">
                        <span>{{ String(index + 1).padStart(2, '0') }}</span>
                        <h3>{{ item.title }}</h3>
                        <p>{{ item.description }}</p>
                    </article>
                </div>
            </div>
        </section>

        <section v-if="isEnabled(widgetMap['pc-gallery'])" class="enterprise-section enterprise-gallery">
            <div class="enterprise-shell">
                <div class="enterprise-section-head">
                    <div>
                        <div class="enterprise-eyebrow">{{ galleryContent.eyebrow }}</div>
                        <h2>{{ galleryContent.title }}</h2>
                    </div>
                    <p>{{ galleryContent.subtitle }}</p>
                </div>
                <div class="enterprise-gallery__grid">
                    <article v-for="(item, index) in normalizeList<any>(galleryContent.data)" :key="`${item.title}-${index}`">
                        <div class="enterprise-gallery__image">
                            <img v-if="getImageUrl(item.image)" :src="getImageUrl(item.image)" alt="" />
                            <div v-else class="enterprise-image-placeholder">展示图</div>
                        </div>
                        <h3>{{ item.title }}</h3>
                        <p>{{ item.description }}</p>
                    </article>
                </div>
            </div>
        </section>

        <section v-if="isEnabled(widgetMap['pc-stats'])" class="enterprise-section enterprise-stats">
            <div class="enterprise-shell enterprise-stats__inner">
                <div>
                    <div class="enterprise-eyebrow">{{ statsContent.eyebrow }}</div>
                    <h2>{{ statsContent.title }}</h2>
                    <p>{{ statsContent.subtitle }}</p>
                </div>
                <div class="enterprise-stats__list">
                    <article v-for="(item, index) in normalizeList<any>(statsContent.data)" :key="`${item.label}-${index}`">
                        <strong>{{ item.value }}</strong>
                        <span>{{ item.label }}</span>
                        <p>{{ item.description }}</p>
                    </article>
                </div>
            </div>
        </section>

        <section v-if="isEnabled(widgetMap['pc-contact'])" class="enterprise-section enterprise-contact">
            <div class="enterprise-shell enterprise-contact__inner">
                <div>
                    <div class="enterprise-eyebrow">{{ contactContent.eyebrow }}</div>
                    <h2>{{ contactContent.title }}</h2>
                    <p>{{ contactContent.subtitle }}</p>
                </div>
                <div class="enterprise-contact__info">
                    <div>
                        <span>联系电话</span>
                        <strong>{{ contactContent.phone }}</strong>
                    </div>
                    <div>
                        <span>服务时间</span>
                        <strong>{{ contactContent.service_time }}</strong>
                    </div>
                    <div>
                        <span>企业地址</span>
                        <strong>{{ contactContent.address }}</strong>
                    </div>
                    <p>{{ contactContent.remark }}</p>
                </div>
                <div class="enterprise-contact__qr">
                    <img v-if="contactQrcode" :src="contactQrcode" alt="" />
                    <span v-else>二维码</span>
                </div>
            </div>
        </section>
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
        eyebrow: 'PROFESSIONAL EVENT HOSTING',
        title: '专业主持与企业活动表达服务',
        subtitle: '以稳健控场、清晰表达和高级审美，服务每一次重要亮相。',
        description: 'PC 首页定位为企业展示窗口，集中呈现团队能力、服务场景与联系方式。',
        image: '/resource/image/adminapi/default/banner003.png',
        image_caption: '企业活动 · 仪式表达 · 现场统筹',
        badges: ['企业活动', '品牌发布', '礼仪庆典']
    },
    about: {
        enabled: 1,
        eyebrow: 'ABOUT US',
        title: '以专业流程完成每一次重要表达',
        subtitle: '我们为企业发布、品牌活动、礼仪庆典与高端仪式提供主持与现场统筹支持。',
        description: '从前期沟通、流程梳理、主持文本到现场控场，团队用成熟方法帮助客户把重要场合表达得更清晰、更稳妥。',
        image: '/resource/image/adminapi/default/banner002.png',
        points: ['流程策划', '主持执行', '现场统筹']
    },
    advantages: {
        enabled: 1,
        eyebrow: 'CAPABILITIES',
        title: '把控节奏、表达与现场秩序',
        subtitle: '适配企业展示、发布会、庆典仪式、商务活动等不同场景。',
        data: [
            { title: '表达策略', description: '先明确活动目标，再拆解台词、流程与现场节奏。' },
            { title: '流程统筹', description: '对接人员、环节、物料与时间点，减少现场不确定性。' },
            { title: '审美统一', description: '文案、画面、音乐与仪式感保持同一品牌语气。' }
        ]
    },
    gallery: {
        enabled: 1,
        eyebrow: 'SHOWCASE',
        title: '真实场景中的专业呈现',
        subtitle: '用于展示企业活动、仪式现场、团队环境与服务质感。',
        data: [
            { image: '/resource/image/adminapi/default/banner003.png', title: '企业发布现场', description: '稳定推进流程，强化品牌表达。' },
            { image: '/resource/image/adminapi/default/banner001.png', title: '庆典仪式现场', description: '兼顾秩序、情绪与仪式感。' },
            { image: '/resource/image/adminapi/default/banner002.png', title: '团队服务场景', description: '让细节在现场自然发生。' }
        ]
    },
    stats: {
        enabled: 1,
        eyebrow: 'TRACK RECORD',
        title: '长期服务沉淀',
        subtitle: '用持续稳定的交付能力支撑每一次公开亮相。',
        data: [
            { value: '1000+', label: '活动服务经验', description: '覆盖仪式、发布与商务场景' },
            { value: '98%', label: '客户好评率', description: '来自长期合作与现场反馈' },
            { value: '30+', label: '覆盖城市', description: '支持跨区域活动执行' }
        ]
    },
    contact: {
        enabled: 1,
        eyebrow: 'CONTACT',
        title: '让重要场合被清晰表达',
        subtitle: '欢迎通过电话、二维码或地址信息进一步了解团队。',
        phone: '1888888888',
        service_time: '周一至周日 09:30 - 19:00',
        address: '请在后台装修中填写企业地址',
        qrcode: '/resource/image/adminapi/default/kefu01.png',
        remark: '欢迎通过上述方式进一步了解团队服务与合作信息。'
    }
}

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
</script>

<style lang="scss" scoped>
.pc-enterprise-home {
    min-width: 1200px;
    min-height: 100vh;
    color: #111111;
    background: #ffffff;
    font-family: ui-serif, Georgia, 'Times New Roman', 'Microsoft YaHei', serif;
}

.enterprise-shell {
    width: 1200px;
    margin: 0 auto;
    box-sizing: border-box;
}

.enterprise-eyebrow {
    color: #9a7336;
    font-size: 13px;
    font-weight: 800;
    letter-spacing: 0;
}

.enterprise-section {
    padding: 0;
}

.enterprise-section-head {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 420px;
    gap: 60px;
    align-items: end;

    h2 {
        margin: 14px 0 0;
        color: #111111;
        font-size: 38px;
        line-height: 1.2;
        font-weight: 800;
        letter-spacing: 0;
    }

    p {
        margin: 0;
        color: #6f6a61;
        font-size: 16px;
        line-height: 1.75;
    }

    &--dark {
        h2 {
            color: #ffffff;
        }

        p {
            color: rgba(255, 255, 255, 0.68);
        }
    }
}

.enterprise-image-placeholder {
    width: 100%;
    height: 100%;
    background: #151515;
    color: rgba(255, 255, 255, 0.72);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.enterprise-hero {
    min-height: 620px;
    background:
        linear-gradient(120deg, rgba(17, 17, 17, 0.05), rgba(200, 164, 93, 0.12)),
        #f7f3ec;

    &__inner {
        min-height: 620px;
        display: grid;
        grid-template-columns: minmax(0, 0.95fr) minmax(0, 1.05fr);
        gap: 72px;
        align-items: center;
        padding: 86px 68px;
    }

    h1 {
        margin: 18px 0 0;
        color: #111111;
        font-size: 58px;
        line-height: 1.05;
        font-weight: 800;
        letter-spacing: 0;
    }

    &__subtitle {
        margin: 24px 0 0;
        color: #2a2722;
        font-size: 22px;
        line-height: 1.55;
        font-weight: 600;
    }

    &__description {
        margin: 16px 0 0;
        color: #6f6a61;
        font-size: 16px;
        line-height: 1.8;
    }

    &__badges {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 34px;

        span {
            border: 1px solid rgba(17, 17, 17, 0.14);
            padding: 9px 14px;
            color: #111111;
            background: rgba(255, 255, 255, 0.48);
            font-size: 13px;
            font-weight: 700;
        }
    }

    &__media {
        position: relative;
        height: 430px;
        background: #111111;
        overflow: hidden;
        box-shadow: 0 28px 70px rgba(17, 17, 17, 0.24);

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
    }

    &__caption {
        position: absolute;
        left: 22px;
        bottom: 22px;
        max-width: calc(100% - 44px);
        padding: 10px 14px;
        color: #ffffff;
        background: rgba(17, 17, 17, 0.68);
        font-size: 14px;
    }
}

.enterprise-about {
    background: #ffffff;

    &__inner {
        min-height: 560px;
        display: grid;
        grid-template-columns: 480px minmax(0, 1fr);
        gap: 72px;
        align-items: center;
        padding: 70px 68px;
    }

    &__image {
        height: 390px;
        background: #171717;
        overflow: hidden;

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
    }

    h2 {
        margin: 16px 0 0;
        color: #111111;
        font-size: 38px;
        line-height: 1.2;
        font-weight: 800;
        letter-spacing: 0;
    }

    &__points {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 30px;

        span {
            padding: 9px 13px;
            background: #f7f3ec;
            color: #111111;
            font-size: 13px;
            font-weight: 700;
        }
    }
}

.enterprise-lead {
    margin: 20px 0 0;
    color: #2e2b26;
    font-size: 19px;
    line-height: 1.7;
    font-weight: 600;
}

.enterprise-text {
    margin: 18px 0 0;
    color: #6f6a61;
    font-size: 15px;
    line-height: 1.9;
}

.enterprise-advantages {
    background: #111111;
    color: #ffffff;

    .enterprise-shell {
        min-height: 430px;
        padding: 66px 68px;
    }

    &__grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
        margin-top: 42px;

        article {
            min-height: 138px;
            padding: 26px;
            border: 1px solid rgba(255, 255, 255, 0.14);
            background: rgba(255, 255, 255, 0.04);
        }

        span {
            color: #c8a45d;
            font-size: 13px;
            font-weight: 800;
        }

        h3 {
            margin: 18px 0 0;
            color: #ffffff;
            font-size: 20px;
            font-weight: 800;
        }

        p {
            margin: 12px 0 0;
            color: rgba(255, 255, 255, 0.68);
            font-size: 14px;
            line-height: 1.7;
        }
    }
}

.enterprise-gallery {
    background: #f7f3ec;

    .enterprise-shell {
        min-height: 600px;
        padding: 70px 68px;
    }

    &__grid {
        display: grid;
        grid-template-columns: 1.2fr 0.9fr 0.9fr;
        gap: 18px;
        margin-top: 42px;
    }

    article {
        background: #ffffff;
        overflow: hidden;
        box-shadow: 0 18px 50px rgba(17, 17, 17, 0.08);

        h3 {
            margin: 20px 22px 0;
            color: #111111;
            font-size: 18px;
            font-weight: 800;
        }

        p {
            margin: 10px 22px 24px;
            color: #6f6a61;
            font-size: 14px;
            line-height: 1.7;
        }
    }

    &__image {
        height: 230px;
        background: #151515;

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
    }
}

.enterprise-stats {
    background: #ffffff;

    &__inner {
        min-height: 320px;
        display: grid;
        grid-template-columns: 330px minmax(0, 1fr);
        gap: 48px;
        padding: 58px 68px;

        h2 {
            margin: 14px 0 0;
            color: #111111;
            font-size: 34px;
            line-height: 1.2;
            font-weight: 800;
        }

        > div > p {
            margin: 16px 0 0;
            color: #6f6a61;
            font-size: 15px;
            line-height: 1.8;
        }
    }

    &__list {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
    }

    article {
        padding: 28px;
        border-left: 3px solid #c8a45d;
        background: #f7f3ec;
    }

    strong {
        display: block;
        color: #111111;
        font-size: 44px;
        line-height: 1;
        font-weight: 900;
    }

    span {
        display: block;
        margin-top: 14px;
        color: #111111;
        font-size: 16px;
        font-weight: 800;
    }

    article p {
        margin: 10px 0 0;
        color: #6f6a61;
        font-size: 13px;
        line-height: 1.6;
    }
}

.enterprise-contact {
    color: #ffffff;
    background:
        linear-gradient(135deg, rgba(200, 164, 93, 0.18), rgba(17, 17, 17, 0)),
        #111111;

    &__inner {
        min-height: 480px;
        display: grid;
        grid-template-columns: 410px minmax(0, 1fr) 170px;
        gap: 48px;
        align-items: center;
        padding: 66px 68px;

        h2 {
            margin: 14px 0 0;
            font-size: 38px;
            line-height: 1.2;
            font-weight: 800;
        }

        > div > p {
            margin: 18px 0 0;
            color: rgba(255, 255, 255, 0.7);
            font-size: 16px;
            line-height: 1.75;
        }
    }

    &__info {
        display: grid;
        gap: 16px;

        div {
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        }

        span {
            display: block;
            color: rgba(255, 255, 255, 0.52);
            font-size: 13px;
        }

        strong {
            display: block;
            margin-top: 8px;
            color: #ffffff;
            font-size: 17px;
            line-height: 1.5;
        }

        p {
            margin: 4px 0 0;
            color: rgba(255, 255, 255, 0.62);
            font-size: 13px;
            line-height: 1.7;
        }
    }

    &__qr {
        width: 150px;
        height: 150px;
        padding: 10px;
        background: #ffffff;
        color: #111111;
        display: flex;
        align-items: center;
        justify-content: center;

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
    }
}
</style>
