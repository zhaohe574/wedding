<template>
    <!-- 小程序引导精简预览：整页预览由 pc-preview.vue 统一镜像真机几何，这里只做兜底渲染 -->
    <section class="pc-miniprogram-preview">
        <h2>{{ content.title }}</h2>
        <img
            v-if="content.preview_image"
            class="pc-miniprogram-preview__shot"
            :src="content.preview_image"
            :alt="content.miniapp_name"
        />
        <div class="pc-miniprogram-preview__highlights">
            <article v-for="(item, index) in highlights" :key="index">
                <span>{{ item.en_label }}</span>
                <strong>{{ item.title }}</strong>
            </article>
        </div>
        <div class="pc-miniprogram-preview__qr">
            <div class="pc-miniprogram-preview__qrcode">
                <img v-if="content.qrcode" :src="content.qrcode" :alt="content.qrcode_caption" />
                <span v-else>小程序码</span>
            </div>
            <div class="pc-miniprogram-preview__qr-text">
                <strong>{{ content.miniapp_name }}</strong>
                <span>{{ content.qrcode_caption }}</span>
            </div>
        </div>
        <div class="pc-miniprogram-preview__contact">
            <span v-if="content.phone">{{ content.phone }}</span>
            <span v-if="content.service_time">{{ content.service_time }}</span>
            <span v-if="content.address">{{ content.address }}</span>
        </div>
        <div v-if="content.footer_slogan" class="pc-miniprogram-preview__footer">{{ content.footer_slogan }}</div>
    </section>
</template>

<script lang="ts" setup>
import type { PropType } from 'vue'

import { normalizeList } from '../pc-shared'
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

const highlights = computed(() =>
    normalizeList<OptionsType['content']['highlights'][number]>(props.content.highlights).filter(Boolean).slice(0, 3)
)
</script>

<style lang="scss" scoped>
.pc-miniprogram-preview {
    width: 1200px;
    box-sizing: border-box;
    padding: 88px 68px;
    background: linear-gradient(180deg, #1b1714 0%, #110e0c 100%);
    color: #efe5d3;

    h2 {
        max-width: 720px;
        margin: 0;
        font-size: 42px;
        line-height: 1.18;
        font-weight: 900;
        white-space: pre-line;
    }

    &__shot {
        display: block;
        max-width: 460px;
        margin-top: 40px;
        border: 1px solid rgba(201, 169, 110, 0.42);
        border-radius: 16px;
    }

    &__highlights {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
        margin-top: 44px;

        article {
            padding: 24px 22px;
            border: 1px solid rgba(201, 169, 110, 0.22);
            border-radius: 8px;
            background: rgba(201, 169, 110, 0.06);
        }

        span {
            display: block;
            color: #c9a96e;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0.08em;
        }

        strong {
            display: block;
            margin-top: 8px;
            color: #efe5d3;
            font-size: 17px;
            font-weight: 900;
        }
    }

    &__qr {
        display: flex;
        align-items: center;
        gap: 22px;
        margin-top: 44px;
    }

    &__qrcode {
        width: 120px;
        height: 120px;
        padding: 8px;
        box-sizing: border-box;
        border: 1px solid rgba(201, 169, 110, 0.42);
        border-radius: 8px;
        background: #fffdf8;
        display: flex;
        align-items: center;
        justify-content: center;

        img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        span {
            color: #8b8073;
            font-size: 12px;
            text-align: center;
        }
    }

    &__qr-text {
        display: grid;
        gap: 8px;

        strong {
            font-size: 18px;
            font-weight: 900;
        }

        span {
            color: rgba(239, 229, 211, 0.72);
            font-size: 13px;
        }
    }

    &__contact {
        display: flex;
        flex-wrap: wrap;
        gap: 12px 28px;
        margin-top: 34px;
        color: rgba(239, 229, 211, 0.72);
        font-size: 14px;
    }

    &__footer {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid rgba(201, 169, 110, 0.22);
        color: rgba(239, 229, 211, 0.56);
        font-size: 13px;
        text-align: center;
    }
}
</style>
