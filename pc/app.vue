<script lang="ts" setup>
import { ID_INJECTION_KEY, ElConfigProvider } from 'element-plus'
import zhCn from 'element-plus/es/locale/lang/zh-cn'
import { useAppStore } from './stores/app'

provide(ID_INJECTION_KEY, {
    prefix: 100,
    current: 0
})
const config = {
    locale: zhCn,
    size: 'small' as const
}
const appStore = useAppStore()
const websiteConfig = computed(() => appStore.getWebsiteConfig || {})
const statisticsConfig = computed(() => appStore.getSiteStatistics || {})

// 全局默认 head：仅在配置项存在时输出，避免 SSR/预渲染产出空的 title、description、
// keywords 与 icon 标签（页面级 useHead 会覆盖 title 与 description）。
const headOptions: any = {
    meta: [],
    link: [],
    script: []
}

if (websiteConfig.value.pc_desc) {
    headOptions.meta.push({
        name: 'description',
        content: websiteConfig.value.pc_desc
    })
}
if (websiteConfig.value.pc_keywords) {
    headOptions.meta.push({
        name: 'keywords',
        content: websiteConfig.value.pc_keywords
    })
}
if (websiteConfig.value.pc_ico) {
    headOptions.link.push({ rel: 'icon', href: websiteConfig.value.pc_ico })
}

const clarityCode = statisticsConfig.value.clarity_code

if (clarityCode) {
    headOptions.script.push({
        type: 'text/javascript',
        innerHTML: `
        (function(c,l,a,r,i,t,y){
          c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
          t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
          y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
        })(window, document, "clarity", "script", "${clarityCode}");
      `
    })
}
useHead(headOptions)
</script>
<template>
    <ElConfigProvider v-bind="config">
        <NuxtLayout>
            <NuxtLoadingIndicator color="#d8b16a" :height="2" />
            <NuxtPage />
        </NuxtLayout>
    </ElConfigProvider>
</template>
