<template>
    <span v-if="summary.total" class="region-price-summary">
        <el-tooltip v-if="summary.tooltip" placement="top-start" effect="dark">
            <template #content>
                <div class="summary-tooltip">{{ summary.tooltip }}</div>
            </template>
            <span class="region-price-summary__label">{{ summary.label }}</span>
        </el-tooltip>
        <span v-else class="region-price-summary__label">{{ summary.label }}</span>
    </span>
    <span v-else :class="emptyClass">{{ emptyText }}</span>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { summarizeRegionPrices } from '@/utils/package-region-price'

const props = withDefaults(
    defineProps<{
        regionPrices?: Record<string, any>[]
        emptyText?: string
        emptyClass?: string
    }>(),
    {
        regionPrices: () => [],
        emptyText: '未配置',
        emptyClass: 'text-gray-400'
    }
)

const summary = computed(() => summarizeRegionPrices(props.regionPrices))
</script>

<style scoped lang="scss">
.region-price-summary {
    display: inline-flex;
    align-items: center;
    min-width: 0;
}

.region-price-summary__label {
    color: #374151;
}

.summary-tooltip {
    max-width: 320px;
    line-height: 1.6;
    white-space: pre-line;
}
</style>
