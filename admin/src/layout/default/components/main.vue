<template>
    <main class="main-wrap h-full">
        <el-scrollbar>
            <div class="main-wrap__inner">
                <router-view v-if="isRouteShow" v-slot="{ Component, route }">
                    <keep-alive :include="includeList" :max="20">
                        <component :is="Component" :key="route.fullPath" />
                    </keep-alive>
                </router-view>
            </div>
        </el-scrollbar>
    </main>
</template>

<script setup lang="ts">
import useAppStore from '@/stores/modules/app'
import useTabsStore from '@/stores/modules/multipleTabs'
import useSettingStore from '@/stores/modules/setting'

const appStore = useAppStore()
const tabsStore = useTabsStore()
const settingStore = useSettingStore()
const isRouteShow = computed(() => appStore.isRouteShow)
const includeList = computed(() => (settingStore.openMultipleTabs ? tabsStore.getCacheTabList : []))
</script>

<style lang="scss" scoped>
.main-wrap {
    border: 1px solid var(--admin-color-border);
    border-radius: var(--admin-radius-lg);
    background: color-mix(in srgb, var(--admin-surface-card) 92%, #ffffff 8%);
    box-shadow: var(--admin-shadow-medium);
}

.main-wrap__inner {
    padding: 14px;
}

@media (max-width: 768px) {
    .main-wrap__inner {
        padding: 10px;
    }
}
</style>
