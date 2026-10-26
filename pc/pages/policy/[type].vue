<template>
    <div class="bg-white render-html p-[30px] w-[1200px] mx-auto min-h-screen">
        <div v-if="error || !data" class="text-center" role="status">
            <p>内容暂时无法加载。</p>
            <button @click="refresh()">重新加载</button>
        </div>
        <template v-else>
            <h1 class="text-center">{{ data.title }}</h1>
            <div class="mx-auto" v-html="data.content"></div>
        </template>
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
const policyDescription = computed(
    () => '格林社婚礼服务协议条款与政策说明。'
)
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
<style lang="scss" scoped></style>
