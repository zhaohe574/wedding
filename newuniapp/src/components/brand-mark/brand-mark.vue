<template>
    <!--
        品牌标识位：有后台图标就渲染图标，没有 / 加载失败就回退到印章字。
        全站品牌图形只走这一个组件（我的未登录、关于我们、开屏层），
        不要在页面里自己写 <image :src="logo"> + 自己判空，那样每处兜底逻辑都会漂移。
    -->
    <image
        v-if="showLogo"
        class="bm bm--image"
        :class="{ 'bm--round': round }"
        :style="boxStyle"
        :src="brand.logo"
        :mode="fit"
        :aria-label="brand.name"
        @error="onLogoError"
    />
    <view
        v-else
        class="bm bm--seal"
        :class="[`bm--${variant}`, { 'bm--round': round }]"
        :style="boxStyle"
        :aria-label="brand.name"
    >
        <text class="bm__label" :style="labelStyle">{{ brand.seal }}</text>
    </view>
</template>

<script setup lang="ts">
/**
 * 品牌标识（小程序图标 / 印章兜底）。
 *
 * 契约：
 *  - 图标来源只有 `useBrand().brand.logo`（后台「前台LOGO」），组件不接受 src 参数 ——
 *    否则又会出现「某处传了本地图片」的硬编码。
 *  - 图标缺失或加载失败 → 自动回退到「名称首字」印章，不需要调用方写 v-if。
 *  - 纯展示，不含业务状态。
 */
import { computed } from 'vue'
import { useBrand } from '@/composables/useBrand'

const props = withDefaults(
    defineProps<{
        /** 边长（rpx）。我的未登录 62px≈120rpx，关于页 144rpx，开屏层 120rpx */
        size?: number
        /** 印章兜底时的底色语义：dark = 压在深色图/墨黑上；light = 米白/白底上 */
        variant?: 'dark' | 'light'
        /** 圆形（我的未登录 / 开屏层）还是圆角方印（关于页） */
        round?: boolean
        /** 图标填充方式 */
        fit?: 'aspectFit' | 'aspectFill'
    }>(),
    { size: 50, variant: 'dark', round: false, fit: 'aspectFit' }
)

const { brand, showLogo, onLogoError } = useBrand()

const boxStyle = computed(() => ({
    width: `${props.size}rpx`,
    height: `${props.size}rpx`
}))

/** 印章字大小跟随边长，和原 brand-seal 的比例保持一致（0.5） */
const labelStyle = computed(() => ({
    fontSize: `${Math.round(props.size * 0.5)}rpx`
}))
</script>

<style lang="scss" scoped>
.bm {
    display: block;
    flex: none;
    box-sizing: border-box;

    &--round {
        border-radius: var(--r-pill);
        overflow: hidden;
    }

    &--image {
        border-radius: var(--r-sm);
        overflow: hidden;
    }

    &--seal {
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--r-sm);
        border: 1rpx solid;
    }

    &__label {
        font-family: var(--font-serif);
        line-height: var(--lh-none);
        letter-spacing: 0;
    }

    /* 深色底（主图 / 墨黑块）：香槟金描边 + 香槟金字 */
    &--dark {
        border-color: var(--c-brand-soft);
        background: rgba(255, 255, 255, 0.06);

        .bm__label {
            color: var(--c-brand-soft);
        }
    }

    /* 浅色底：金色降到可读档，描边仍用香槟（装饰线不受对比度约束） */
    &--light {
        border-color: var(--c-brand);
        background: var(--c-brand-bg);

        .bm__label {
            color: var(--c-brand-text);
        }
    }
}
</style>
