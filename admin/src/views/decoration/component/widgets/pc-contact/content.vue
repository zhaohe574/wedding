<template>
    <section class="pc-contact-preview">
        <div class="pc-contact-preview__copy">
            <div class="pc-contact-preview__eyebrow">{{ content.eyebrow }}</div>
            <h2>{{ content.title }}</h2>
            <p>{{ content.subtitle }}</p>
        </div>
        <div class="pc-contact-preview__info">
            <div>
                <span>联系电话</span>
                <strong>{{ content.phone }}</strong>
            </div>
            <div>
                <span>服务时间</span>
                <strong>{{ content.service_time }}</strong>
            </div>
            <div>
                <span>企业地址</span>
                <strong>{{ content.address }}</strong>
            </div>
            <p>{{ content.remark }}</p>
        </div>
        <div class="pc-contact-preview__qr">
            <img v-if="content.qrcode" :src="getImageUrl(content.qrcode)" alt="" />
            <span v-else>二维码</span>
        </div>
    </section>
</template>

<script lang="ts" setup>
import type { PropType } from 'vue'

import { getImageUrl } from '../pc-shared'
import type options from './options'

type OptionsType = ReturnType<typeof options>
defineProps({
    content: {
        type: Object as PropType<OptionsType['content']>,
        default: () => ({})
    }
})
</script>

<style lang="scss" scoped>
.pc-contact-preview {
    width: 1200px;
    min-height: 480px;
    box-sizing: border-box;
    display: grid;
    grid-template-columns: 410px minmax(0, 1fr) 170px;
    gap: 48px;
    align-items: center;
    padding: 66px 68px;
    color: #ffffff;
    background:
        linear-gradient(135deg, rgba(200, 164, 93, 0.18), rgba(17, 17, 17, 0)),
        #111111;

    &__eyebrow {
        color: #c8a45d;
        font-size: 13px;
        font-weight: 800;
    }

    h2 {
        margin: 14px 0 0;
        font-size: 38px;
        line-height: 1.2;
        font-weight: 800;
    }

    &__copy p {
        margin: 18px 0 0;
        color: rgba(255, 255, 255, 0.7);
        font-size: 16px;
        line-height: 1.75;
    }

    &__info {
        display: grid;
        gap: 16px;
    }

    &__info div {
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

    &__info p {
        margin: 4px 0 0;
        color: rgba(255, 255, 255, 0.62);
        font-size: 13px;
        line-height: 1.7;
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
        }
    }
}
</style>
