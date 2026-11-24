<template>
    <div class="shop-pages">
        <div class="link-overview">
            <div>
                <div class="link-overview__title">
                    {{ props.isTab ? '底部导航可选页面' : '移动端页面链接' }}
                </div>
                <div class="link-overview__desc">
                    {{
                        props.isTab
                            ? '仅展示小程序 tabBar 中可 switchTab 的四段主页'
                            : '仅展示 newuniapp 已注册真实页面；选中支持传参的页面后可在下方配置结构化参数'
                    }}
                </div>
            </div>
            <div class="flex items-center gap-2">
                <el-input
                    v-if="!props.isTab"
                    v-model="searchText"
                    placeholder="搜索页面名称或路径"
                    clearable
                    class="!w-[200px]"
                    size="small"
                />
                <el-tag effect="plain" round>{{ visibleLinkCount }} 个页面</el-tag>
            </div>
        </div>

        <el-empty v-if="visibleLinkCount === 0" description="没有匹配的页面" :image-size="90" />

        <div v-else class="link-group-list">
            <section v-for="group in normalGroups" :key="group.key" class="link-group">
                <div class="link-group__head">
                    <div>
                        <div class="link-group__title">{{ group.label }}</div>
                        <div class="link-group__desc">{{ group.description }}</div>
                    </div>
                    <el-tag size="small" effect="plain">{{ group.items.length }}</el-tag>
                </div>

                <div class="link-grid">
                    <button
                        v-for="item in group.items"
                        :key="item.path"
                        class="link-card"
                        :class="{ 'is-active': isSelected(item) }"
                        type="button"
                        @click="handleSelect(item)"
                    >
                        <div class="link-card__top">
                            <span class="link-card__name">{{ item.name }}</span>
                            <el-tag
                                v-if="!props.isTab && item.params?.length"
                                size="small"
                                type="info"
                                effect="plain"
                            >
                                可配参数
                            </el-tag>
                        </div>
                        <span class="link-card__path">{{ item.path }}</span>
                    </button>
                </div>
            </section>

            <!-- 工作人员/管理端专属入口默认折叠，防止运营误配到 C 端公开位 -->
            <el-collapse v-if="!props.isTab && internalGroups.length" v-model="expandedInternal">
                <el-collapse-item
                    v-for="group in internalGroups"
                    :key="group.key"
                    :name="group.key"
                    class="link-collapse-group"
                >
                    <template #title>
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-[14px]">{{ group.label }}</span>
                            <el-tag size="small" type="warning" effect="light">仅内部角色可进</el-tag>
                            <el-tag size="small" effect="plain">{{ group.items.length }}</el-tag>
                        </div>
                    </template>

                    <el-alert
                        type="warning"
                        show-icon
                        :closable="false"
                        class="mb-3"
                        title="普通访客点击以上入口会被权限拦截"
                        :description="group.description"
                    />

                    <div class="link-grid">
                        <button
                            v-for="item in group.items"
                            :key="item.path"
                            class="link-card"
                            :class="{ 'is-active': isSelected(item) }"
                            type="button"
                            @click="handleSelect(item)"
                        >
                            <div class="link-card__top">
                                <span class="link-card__name">{{ item.name }}</span>
                            </div>
                            <span class="link-card__path">{{ item.path }}</span>
                        </button>
                    </div>
                </el-collapse-item>
            </el-collapse>
        </div>

        <!-- 结构化参数配置面板（仅非 Tab 模式展示） -->
        <div v-if="!props.isTab && selectedPageDef" class="mt-4">
            <link-params-form
                v-if="selectedPageDef.params?.length"
                :title="`「${selectedPageDef.name}」跳转参数`"
                :params="selectedPageDef.params"
                :model-value="modelValue?.query || {}"
                @update:model-value="handleParamsUpdate"
            />
            <div v-else class="link-no-params">
                已选择「{{ selectedPageDef.name }}」，该页面无需配置额外参数。
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import {
    DECORATE_LINK_GROUPS,
    type DecorateLinkPageDef,
    findDecorateLinkPage
} from '@shared/contracts/decorate-links'
import { computed, type PropType, ref, watch } from 'vue'

import { type Link, LinkTypeEnum } from '.'
import LinkParamsForm from './link-params-form.vue'

const props = defineProps({
    modelValue: {
        type: Object as PropType<Link>,
        default: () => ({ path: '' })
    },
    isTab: {
        type: Boolean,
        default: false
    }
})
const emit = defineEmits<{
    (event: 'update:modelValue', value: Link): void
}>()

const searchText = ref('')
const expandedInternal = ref<string[]>([])

const filteredGroups = computed(() => {
    const kw = searchText.value.trim().toLowerCase()
    return DECORATE_LINK_GROUPS.map((group) => {
        const items = group.items.filter((item) => {
            if (props.isTab && item.canTab !== true) return false
            if (!kw) return true
            return (
                item.name.toLowerCase().includes(kw) ||
                item.path.toLowerCase().includes(kw)
            )
        })
        return { ...group, items }
    }).filter((group) => group.items.length > 0)
})

const normalGroups = computed(() => filteredGroups.value.filter((g) => !g.internalOnly))
const internalGroups = computed(() =>
    props.isTab ? [] : filteredGroups.value.filter((g) => g.internalOnly)
)

const visibleLinkCount = computed(() =>
    filteredGroups.value.reduce((sum, g) => sum + g.items.length, 0)
)

const selectedPageDef = computed<DecorateLinkPageDef | null>(() => {
    if (props.modelValue?.type && props.modelValue.type !== LinkTypeEnum.SHOP_PAGES) {
        return null
    }
    return findDecorateLinkPage(props.modelValue?.path)
})

const isSelected = (item: DecorateLinkPageDef) => {
    return (
        props.modelValue?.path === item.path &&
        (!props.modelValue?.type || props.modelValue.type === LinkTypeEnum.SHOP_PAGES)
    )
}

const buildDefaultQuery = (item: DecorateLinkPageDef): Record<string, string> | undefined => {
    if (item.path === '/pages/agreement/agreement') {
        return { type: 'privacy' }
    }
    return undefined
}

const handleSelect = (item: DecorateLinkPageDef) => {
    const samePage = isSelected(item)
    const nextQuery = samePage ? props.modelValue?.query : buildDefaultQuery(item)
    const next: Link = {
        path: item.path,
        name: item.name,
        type: LinkTypeEnum.SHOP_PAGES,
        ...(item.canTab ? { canTab: true } : {}),
        ...(nextQuery && Object.keys(nextQuery).length ? { query: { ...nextQuery } } : {})
    }
    emit('update:modelValue', next)
}

const handleParamsUpdate = (query: Record<string, string>) => {
    if (!selectedPageDef.value) return
    const next: Link = {
        path: selectedPageDef.value.path,
        name: selectedPageDef.value.name,
        type: LinkTypeEnum.SHOP_PAGES,
        ...(selectedPageDef.value.canTab ? { canTab: true } : {}),
        ...(Object.keys(query).length ? { query } : {})
    }
    emit('update:modelValue', next)
}

watch(
    () => props.modelValue?.path,
    (path) => {
        const hit = findDecorateLinkPage(path)
        if (hit?.internalOnly && !expandedInternal.value.includes('staff_internal')) {
            expandedInternal.value = ['staff_internal']
        }
    },
    { immediate: true }
)

watch(searchText, (kw) => {
    if (kw.trim() && internalGroups.value.length && !expandedInternal.value.includes('staff_internal')) {
        expandedInternal.value = ['staff_internal']
    }
})
</script>

<style lang="scss" scoped>
.shop-pages {
    height: 540px;
    overflow-y: auto;
    padding-right: 4px;
}

.link-overview {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 14px;
    padding: 12px 16px;
    border: 1px solid var(--el-border-color-lighter);
    border-radius: 10px;
    background: var(--el-fill-color-extra-light);
}

.link-overview__title {
    font-size: 15px;
    font-weight: 600;
    color: var(--el-text-color-primary);
}

.link-overview__desc {
    margin-top: 4px;
    font-size: 12px;
    line-height: 1.5;
    color: var(--el-text-color-secondary);
}

.link-group-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.link-group {
    padding: 14px 16px;
    border: 1px solid var(--el-border-color-lighter);
    border-radius: 10px;
    background: var(--el-bg-color);
}

.link-group__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 10px;
}

.link-group__title {
    padding-left: 8px;
    border-left: 3px solid var(--el-color-primary);
    font-weight: 600;
    line-height: 1.2;
    color: var(--el-text-color-primary);
}

.link-group__desc {
    margin-top: 4px;
    font-size: 12px;
    color: var(--el-text-color-secondary);
}

.link-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
}

.link-card {
    min-width: 0;
    padding: 10px 12px;
    border: 1px solid var(--el-border-color);
    border-radius: 8px;
    background: var(--el-fill-color-blank);
    text-align: left;
    cursor: pointer;
    transition: all 0.16s ease;

    &:hover {
        border-color: var(--el-color-primary-light-5);
        background: var(--el-color-primary-light-9);
    }

    &.is-active {
        border-color: var(--el-color-primary);
        background: var(--el-color-primary-light-9);
        color: var(--el-color-primary);
        box-shadow: 0 0 0 1px var(--el-color-primary-light-7) inset;
    }
}

.link-card__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
}

.link-card__name,
.link-card__path {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.link-card__name {
    font-size: 13px;
    font-weight: 600;
}

.link-card__path {
    margin-top: 4px;
    font-size: 11px;
    line-height: 1.35;
    color: var(--el-text-color-secondary);
}

.link-card.is-active .link-card__path {
    color: var(--el-color-primary);
}

.link-collapse-group {
    padding: 0 14px;
    border: 1px solid var(--el-border-color-lighter);
    border-radius: 10px;
    background: var(--el-bg-color);

    :deep(.el-collapse-item__header) {
        border-bottom: 0;
    }

    :deep(.el-collapse-item__wrap) {
        border-bottom: 0;
    }
}

.link-no-params {
    padding: 10px 14px;
    border: 1px dashed var(--el-border-color);
    border-radius: 8px;
    font-size: 12px;
    color: var(--el-text-color-secondary);
    background: var(--el-fill-color-extra-light);
}
</style>
