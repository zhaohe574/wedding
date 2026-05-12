<template>
    <section class="pc-hero-preview">
        <div class="pc-hero-preview__image">
            <img v-if="content.image" :src="getImageUrl(content.image)" alt="" />
        </div>
        <div class="pc-hero-preview__veil"></div>
        <header class="pc-hero-preview__header">
            <div class="pc-hero-preview__brand">格林社婚礼服务</div>
            <div class="pc-hero-preview__nav">
                <span>品牌介绍</span>
                <span>服务能力</span>
                <span>案例现场</span>
                <span>联系信息</span>
            </div>
        </header>
        <div class="pc-hero-preview__inner">
            <div class="pc-hero-preview__copy">
                <div class="pc-hero-preview__eyebrow">{{ content.eyebrow }}</div>
                <h1>{{ content.title }}</h1>
                <p class="pc-hero-preview__subtitle">{{ content.subtitle }}</p>
                <p class="pc-hero-preview__description">{{ content.description }}</p>
                <div class="pc-hero-preview__badges">
                    <span v-for="item in badges" :key="item">{{ item }}</span>
                </div>
            </div>
            <aside class="pc-hero-preview__panel">
                <span>Scene Direction</span>
                <strong>{{ content.image_caption }}</strong>
                <p>从沟通、脚本、音乐节点到现场控场，保持审美和情绪在同一个节奏里。</p>
            </aside>
            <div class="pc-hero-preview__stats">
                <article>
                    <strong>1000+</strong>
                    <span>活动服务经验</span>
                </article>
                <article>
                    <strong>98%</strong>
                    <span>客户好评率</span>
                </article>
                <article>
                    <strong>30+</strong>
                    <span>覆盖城市</span>
                </article>
            </div>
        </div>
    </section>
</template>

<script lang="ts" setup>
import type { PropType } from 'vue'

import { getImageUrl, normalizeList } from '../pc-shared'
import type options from './options'

type OptionsType = ReturnType<typeof options>

const props = defineProps({
    content: {
        type: Object as PropType<OptionsType['content']>,
        default: () => ({})
    },
    styles: {
        type: Object as PropType<OptionsType['styles']>,
        default: () => ({})
    }
})

const badges = computed(() => normalizeList<string>(props.content.badges).filter(Boolean).slice(0, 4))
</script>

<style lang="scss" scoped>
.pc-hero-preview {
    position: relative;
    width: 1200px;
    min-height: 820px;
    box-sizing: border-box;
    color: #fffaf1;
    background: #15100d;
    overflow: hidden;

    &__image,
    &__veil {
        position: absolute;
        inset: 0;
    }

    &__image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        filter: saturate(0.88) contrast(1.08);
    }

    &__veil {
        background:
            linear-gradient(90deg, rgba(10, 8, 6, 0.92) 0%, rgba(10, 8, 6, 0.74) 34%, rgba(10, 8, 6, 0.18) 100%),
            linear-gradient(180deg, rgba(10, 8, 6, 0.54) 0%, rgba(10, 8, 6, 0.12) 45%, rgba(10, 8, 6, 0.88) 100%),
            repeating-linear-gradient(90deg, rgba(255, 250, 241, 0.08) 0, rgba(255, 250, 241, 0.08) 1px, transparent 1px, transparent 160px);
    }

    &__header {
        position: relative;
        z-index: 2;
        height: 82px;
        padding: 0 68px;
        border-bottom: 1px solid rgba(255, 250, 241, 0.16);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    &__brand {
        color: #fffaf1;
        font-size: 20px;
        font-weight: 900;
    }

    &__nav {
        display: flex;
        gap: 34px;
        color: rgba(255, 250, 241, 0.82);
        font-size: 14px;
    }

    &__inner {
        position: relative;
        z-index: 2;
        min-height: 738px;
        display: grid;
        grid-template-columns: minmax(0, 690px) 320px;
        grid-template-rows: minmax(0, 1fr) auto;
        gap: 28px 78px;
        align-items: end;
        padding: 84px 68px 54px;
        box-sizing: border-box;
    }

    &__copy {
        padding-bottom: 58px;
    }

    &__eyebrow {
        color: #d8b16a;
        font-size: 13px;
        font-weight: 900;
    }

    h1 {
        max-width: 620px;
        margin: 22px 0 0;
        color: #fffaf1;
        font-size: 76px;
        line-height: 1.02;
        font-weight: 900;
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

    &__badges {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 38px;

        span {
            border: 1px solid rgba(255, 250, 241, 0.22);
            border-radius: 6px;
            padding: 10px 16px;
            color: #fffaf1;
            background: rgba(255, 250, 241, 0.08);
            font-size: 13px;
            font-weight: 800;
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

        &::before {
            content: '';
            position: absolute;
            top: -28px;
            right: 28px;
            width: 1px;
            height: 88px;
            background: #d8b16a;
        }

        span,
        strong,
        p {
            display: block;
        }

        span {
            color: #d8b16a;
            font-size: 12px;
            font-weight: 900;
        }

        strong {
            margin-top: 42px;
            color: #fffaf1;
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
            color: #d8b16a;
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
}
</style>
