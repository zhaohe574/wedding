<template>
    <div class="pages-menu">
        <el-menu
            :default-active="modelValue"
            class="w-[160px] min-h-[668px]"
            @select="handleSelect"
        >
            <el-menu-item v-for="(item, key) in menus" :index="key" :key="item.id">
                <div class="pages-menu__item">
                    <span>{{ item.name }}</span>
                    <!-- 组件数量来自 shared 契约，运营一眼能看出「这页由几个组件构成」 -->
                    <span v-if="item.widgets?.length" class="pages-menu__count">
                        {{ item.widgets.length }} 个组件
                    </span>
                </div>
            </el-menu-item>
        </el-menu>
    </div>
</template>
<script lang="ts" setup>
import type { PropType } from 'vue'

defineProps({
    menus: {
        type: Object as PropType<Record<string, any>>,
        default: () => ({})
    },
    modelValue: {
        type: String,
        default: '1'
    }
})
const emit = defineEmits<{
    (event: 'update:modelValue', value: string): void
}>()
const handleSelect = (index: string) => {
    emit('update:modelValue', index)
}
</script>

<style lang="scss" scoped>
.pages-menu {
    :deep(.el-menu) {
        border-right: none;
    }
    :deep(.el-menu-item) {
        border-color: transparent;
        &.is-active {
            border-right-width: 2px;
            border-color: var(--el-color-primary);
            background-color: var(--el-color-primary-light-9);
        }
    }

    &__item {
        display: flex;
        flex-direction: column;
        line-height: 1.35;
    }

    &__count {
        font-size: 11px;
        color: var(--el-text-color-secondary);
    }
}
</style>
