<template>
    <NuxtLink class="header-brand" to="/" aria-label="返回首页">
        <img
            v-if="brandLogo && !logoFailed"
            class="header-brand__logo"
            :src="brandLogo"
            alt="品牌标识"
            @error="logoFailed = true"
        />
        <template v-else>
            <span class="header-brand__seal" aria-hidden="true">{{
                sealChar
            }}</span>
            <span class="header-brand__name">{{ siteName }}</span>
        </template>
    </NuxtLink>
</template>
<script lang="ts" setup>
import { useAppStore } from '~~/stores/app'
const appStore = useAppStore()

const brandLogo = computed(
    () =>
        appStore.getWebsiteConfig.pc_logo ||
        appStore.getWebsiteConfig.shop_logo ||
        ''
)
const logoFailed = ref(false)
watch(brandLogo, () => {
    logoFailed.value = false
})

const siteName = computed(
    () => appStore.getWebsiteConfig.shop_name || '格林社婚礼服务'
)
const sealChar = computed(() => siteName.value.slice(0, 1) || '格')
</script>

<style lang="scss" scoped>
.header-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
    color: var(--pc-ink);
    text-decoration: none;

    &__logo {
        display: block;
        height: 28px;
        width: auto;
    }

    &__seal {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
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

    &__name {
        overflow: hidden;
        color: var(--pc-ink);
        font-family: var(--font-display);
        font-size: 15px;
        font-weight: 400;
        letter-spacing: 0.08em;
        white-space: nowrap;
        text-overflow: ellipsis;
    }
}
</style>
