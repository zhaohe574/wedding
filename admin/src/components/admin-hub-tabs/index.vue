<template>
    <admin-page-shell :title="title" :description="description">
        <template v-if="$slots.extra" #extra>
            <slot name="extra" />
        </template>
        <template v-if="$slots.header" #header>
            <slot name="header" />
        </template>

        <el-empty v-if="visibleTabs.length === 0" description="当前账号暂无可访问的子页面权限" />
        <el-card v-else shadow="never" class="!border-none admin-hub-tabs__card">
            <el-tabs v-model="activeTab" class="admin-hub-tabs__tabs">
                <el-tab-pane v-for="tab in visibleTabs" :key="tab.name" :label="tab.label" :name="tab.name" lazy>
                    <div class="admin-hub-tabs__pane">
                        <component :is="tab.component" v-if="tab.component" />
                        <slot v-else :name="tab.name" />
                    </div>
                </el-tab-pane>
            </el-tabs>
        </el-card>
    </admin-page-shell>
</template>

<script lang="ts">
import type { Component } from 'vue'

export interface HubTabItem {
    name: string
    label: string
    perms?: string[]
    component?: Component
}
</script>

<script lang="ts" setup name="AdminHubTabs">
import { hasPermission } from '@/utils/perm'

/**
 * 业务域 Hub 壳：按 Tab 聚合同类功能页或设置面板。
 *
 * - Tab 按 perms 过滤（hasPermission 语义与 v-perms 一致，空数组默认可见）；
 * - ?tab= 与路由双向同步，切换用 replace 不污染历史；
 * - 内嵌子页面里的 <footer-btns> 默认是 position: fixed 的悬浮条，
 *   多个 Tab 会互相遮挡，这里统一覆写为文档流内的普通按钮行。
 */
const props = defineProps<{
    title?: string
    description?: string
    tabs: HubTabItem[]
    defaultTab?: string
}>()

const route = useRoute()
const router = useRouter()

const visibleTabs = computed(() => props.tabs.filter((tab) => hasPermission(tab.perms || [])))

const resolveTab = (value: unknown) => {
    const target = String(value ?? '')
    if (target && visibleTabs.value.some((tab) => tab.name === target)) {
        return target
    }
    const fallback = props.defaultTab
    if (fallback && visibleTabs.value.some((tab) => tab.name === fallback)) {
        return fallback
    }
    return visibleTabs.value[0]?.name ?? ''
}

const activeTab = ref(resolveTab(route.query.tab))

watch(
    () => route.query.tab,
    (next) => {
        const nextTab = resolveTab(next)
        if (nextTab && nextTab !== activeTab.value) {
            activeTab.value = nextTab
        }
    }
)

// 权限异步就绪导致可见 Tab 集合变化时，保证当前激活值仍然有效。
watch(visibleTabs, () => {
    const nextTab = resolveTab(activeTab.value)
    if (nextTab && nextTab !== activeTab.value) {
        activeTab.value = nextTab
    }
})

watch(activeTab, (next) => {
    if (String(route.query.tab ?? '') !== next) {
        router.replace({ query: { ...route.query, tab: next } })
    }
})
</script>

<style lang="scss" scoped>
.admin-hub-tabs {
    &__tabs {
        :deep(.el-tabs__content) {
            padding-top: 8px;
        }
    }

    &__pane {
        min-width: 0;

        // 内嵌子页可能使用 <footer-btns>（内层为 position: fixed 悬浮条），
        // 在 Tab 内改为文档流按钮行，避免多个 Tab 或页内按钮互相遮挡。
        :deep(.footer-btns) {
            height: auto;
            margin-top: 16px;
        }

        :deep(.footer-btns__content) {
            position: static !important;
            justify-content: flex-start;
            height: auto;
            background: transparent;
            box-shadow: none;
        }
    }
}
</style>
