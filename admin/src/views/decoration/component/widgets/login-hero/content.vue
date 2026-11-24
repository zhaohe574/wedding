<template>
    <div class="login-hero-card">
        <div v-if="normalized.background_image" class="login-hero-card__img">
            <decoration-img
                width="100%"
                height="160px"
                :src="normalized.background_image"
                fit="cover"
            />
        </div>
        <div v-else class="login-hero-card__empty">
            <el-icon :size="28"><Picture /></el-icon>
            <span>未自定义背景图（将使用小程序内置默认图）</span>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { Picture } from '@element-plus/icons-vue'
import { normalizeLoginHeroContent } from '@shared/contracts/decorate'
import { computed, type PropType } from 'vue'

import DecorationImg from '../../decoration-img.vue'
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

const normalized = computed(() => normalizeLoginHeroContent(props.content))
</script>

<style lang="scss" scoped>
.login-hero-card {
    border-radius: 10px;
    overflow: hidden;
    background: #181614;

    &__empty {
        height: 160px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        color: rgba(255, 255, 255, 0.72);
        font-size: 12px;
        background: linear-gradient(160deg, #2b241d 0%, #181614 100%);
    }
}
</style>
