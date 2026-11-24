<template>
    <div class="policy-page">
        <div class="policy-shell">
            <header class="policy-topbar">
                <NuxtLink class="policy-topbar__brand" to="/">
                    <span class="policy-topbar__seal" aria-hidden="true"
                        >格</span
                    >
                    <span class="policy-topbar__name">格林社婚礼服务</span>
                </NuxtLink>
                <NuxtLink class="policy-topbar__back" to="/">
                    ← 返回首页
                </NuxtLink>
            </header>
            <article class="policy-card">
                <div
                    v-if="error || !data"
                    class="policy-card__empty"
                    role="status"
                >
                    <p>内容暂时无法加载。</p>
                    <button type="button" @click="refresh()">重新加载</button>
                </div>
                <template v-else>
                    <h1>{{ data.title }}</h1>
                    <div class="render-html" v-html="data.content"></div>
                </template>
            </article>
        </div>
    </div>
</template>
<script lang="ts" setup>
import { getPolicy } from '~~/api/app'

const route = useRoute()
const { data, error, refresh } = await useAsyncData(
    () =>
        getPolicy({
            type: route.params.type
        }),
    {
        // default 兜底：接口不可达时返回 null 走页面级空态，不抛错中断渲染
        default: () => null,
        watch: [() => route.params.type]
    }
)

definePageMeta({
    layout: 'blank'
})

// ===== SEO =====
const policyTitles: Record<string, string> = {
    privacy: '隐私政策',
    service: '服务协议',
    agreement: '用户协议'
}
const policyTitle = computed(
    () =>
        `${data.value?.title || policyTitles[String(route.params.type)] || '协议条款'}｜格林社婚礼服务`
)
const policyDescription = computed(() => '格林社婚礼服务协议条款与政策说明。')
const policyUrl = computed(
    () => `https://www.gelinshe.com/pc/policy/${route.params.type}`
)

useHead(() => ({
    title: policyTitle.value,
    meta: [
        { name: 'description', content: policyDescription.value },
        { property: 'og:type', content: 'article' },
        { property: 'og:title', content: policyTitle.value },
        { property: 'og:description', content: policyDescription.value },
        { property: 'og:url', content: policyUrl.value }
    ],
    link: [{ rel: 'canonical', href: policyUrl.value }]
}))
</script>
<style lang="scss" scoped>
.policy-page {
    --pc-bg: #151210;
    --pc-surface: #1b1714;
    --pc-ink: #efe5d3;
    --pc-ink-2: #b9aea0;
    --pc-gold: #c9a96e;
    --pc-line: rgba(201, 169, 110, 0.22);
    --pc-line-strong: rgba(201, 169, 110, 0.42);
    --font-sans: -apple-system, BlinkMacSystemFont, 'Helvetica Neue',
        'PingFang SC', 'Microsoft YaHei', Arial, sans-serif;
    --font-display: Georgia, 'Times New Roman', 'Songti SC', 'Noto Serif SC',
        STSong, serif;
    --dur-fast: 150ms;
    --ease-out: cubic-bezier(0.2, 0, 0, 1);

    min-height: 100vh;
    padding: 32px 0 56px;
    background: var(--pc-bg);
    color: var(--pc-ink);
    font-family: var(--font-sans);
}

.policy-shell {
    max-width: 840px;
    margin: 0 auto;
    padding: 0 24px;
    box-sizing: border-box;
}

.policy-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 20px;
}

.policy-topbar__brand {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--pc-ink);
    text-decoration: none;
}

.policy-topbar__seal {
    display: inline-flex;
    align-items: center;
    justify-content: center;
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

.policy-topbar__name {
    font-size: 14px;
    font-weight: 500;
}

.policy-topbar__back {
    color: var(--pc-ink-2);
    font-size: 13px;
    text-decoration: none;
    transition: color var(--dur-fast) var(--ease-out);

    &:hover {
        color: var(--pc-gold);
    }
}

.policy-card {
    padding: 40px 44px 48px;
    border: 1px solid var(--pc-line);
    border-radius: 16px;
    background: var(--pc-surface);

    h1 {
        margin: 0 0 24px;
        color: var(--pc-ink);
        font-family: var(--font-display);
        font-size: 26px;
        font-weight: 400;
        line-height: 1.35;
        text-align: center;
    }

    &__empty {
        padding: 40px 0;
        color: var(--pc-ink-2);
        font-size: 14px;
        text-align: center;

        p {
            margin: 0 0 14px;
        }

        button {
            height: 36px;
            padding: 0 22px;
            border: 1px solid var(--pc-line-strong);
            border-radius: 999px;
            background: transparent;
            color: var(--pc-ink-2);
            font-family: inherit;
            font-size: 13px;
            cursor: pointer;
            transition:
                border-color var(--dur-fast) var(--ease-out),
                color var(--dur-fast) var(--ease-out);

            &:hover {
                border-color: var(--pc-gold);
                color: var(--pc-gold);
            }
        }
    }

    :deep(.render-html) {
        color: var(--pc-ink-2);
        font-size: 15px;
        line-height: 1.9;

        h1,
        h2,
        h3,
        h4,
        h5 {
            color: var(--pc-ink);
        }
    }
}

@media (max-width: 720px) {
    .policy-card {
        padding: 28px 22px 32px;
    }
}
</style>
