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
    min-height: 560px;
    box-sizing: border-box;
    display: grid;
    grid-template-columns: 430px minmax(0, 1fr) 176px;
    gap: 52px;
    align-items: center;
    padding: 86px 68px;
    color: #fffaf1;
    background:
        linear-gradient(135deg, rgba(216, 177, 106, 0.2), rgba(216, 177, 106, 0) 42%),
        repeating-linear-gradient(90deg, rgba(255, 250, 241, 0.055) 0, rgba(255, 250, 241, 0.055) 1px, transparent 1px, transparent 170px),
        #17130f;

    &__eyebrow {
        color: #d8b16a;
        font-size: 13px;
        font-weight: 900;
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

    &__info {
        display: grid;
        gap: 18px;
    }

    &__info div {
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
        color: #fffaf1;
        font-size: 18px;
        line-height: 1.55;
    }

    &__info p {
        margin: 6px 0 0;
        color: rgba(255, 250, 241, 0.62);
        font-size: 13px;
        line-height: 1.75;
    }

    &__qr {
        width: 156px;
        height: 156px;
        padding: 12px;
        border-radius: 8px;
        background: #fffaf1;
        color: #17130f;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 18px 18px 0 rgba(216, 177, 106, 0.18);

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    }
}
</style>
