<template>
    <view class="page-wrap agree-page">
        <view v-if="loading" class="page-pad agree-page__loading">
            <base-skeleton :rows="5" variant="text" />
        </view>

        <base-empty v-else-if="error" status="error" error-kind="network" fixed @retry="load" />

        <view v-else class="page-pad agree-page__body">
            <view class="agree-page__header">
                <text class="agree-page__eyebrow">协议与说明</text>
                <text class="agree-page__title">{{ title }}</text>
                <text class="agree-page__meta">请仔细阅读以下内容，了解服务约定与相关说明</text>
            </view>
            <base-card class="agree-page__card">
                <u-parse class="agree-page__content" :html="content" />
            </base-card>
        </view>
    </view>
</template>

<script setup lang="ts">
/** 协议页：type = service | privacy，内容来自后台配置 */
import { ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { getPolicy } from '@/api/app'

const title = ref('')
const content = ref('')
const loading = ref(true)
const error = ref(false)
let type = 'service'

async function load() {
    loading.value = true
    error.value = false
    try {
        const res = await getPolicy({ type })
        title.value = res?.title || (type === 'privacy' ? '隐私政策' : '服务协议')
        content.value = res?.content || '<p>协议内容暂未配置</p>'
        uni.setNavigationBarTitle({ title: title.value })
    } catch {
        error.value = true
    } finally {
        loading.value = false
    }
}

onLoad((options: any) => {
    if (options?.type) type = String(options.type)
    load()
})
</script>

<style lang="scss" scoped>
.agree-page {
    &__loading {
        padding-top: var(--sp-3);
    }

    &__body {
        padding-top: var(--sp-5);
        padding-bottom: calc(var(--sp-4) + var(--safe-bottom));
    }

    &__header {
        margin-bottom: var(--sp-4);
    }

    &__eyebrow {
        display: block;
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
        letter-spacing: 4rpx;
    }

    &__title {
        display: block;
        margin: var(--sp-2) 0;
        font-size: var(--fs-2xl);
        font-weight: 500;
        line-height: 1.35;
        color: var(--c-ink);
        overflow-wrap: anywhere;
    }

    &__meta {
        display: block;
        font-size: var(--fs-xs);
        line-height: 1.7;
        color: var(--c-ink-3);
    }

    &__card {
        display: block;
    }

    &__content {
        font-size: var(--fs-md);
        line-height: 1.9;
        color: var(--c-ink-2);
        overflow-wrap: anywhere;
        word-break: break-word;

        ::v-deep p {
            margin: 0 0 var(--sp-3);
        }

        ::v-deep h1,
        ::v-deep h2,
        ::v-deep h3,
        ::v-deep h4 {
            margin: var(--sp-4) 0 var(--sp-2);
            font-weight: 500;
            line-height: 1.5;
            color: var(--c-ink);
        }

        ::v-deep img,
        ::v-deep video,
        ::v-deep table {
            max-width: 100% !important;
            box-sizing: border-box;
        }

        ::v-deep a {
            color: var(--c-brand-text);
            word-break: break-all;
        }
    }
}
</style>
