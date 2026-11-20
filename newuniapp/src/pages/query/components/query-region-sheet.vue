<template>
    <tn-popup
        :model-value="open"
        open-direction="bottom"
        :radius="24"
        :safe-area-inset-bottom="true"
        @update:model-value="onVisible"
        @close="close"
    >
        <view class="sheet">
            <view class="sheet__head">
                <text class="sheet__title">服务地区</text>
                <view class="sheet__close" hover-class="is-pressed" aria-label="关闭" @tap="close">
                    <tn-icon name="close" :size="34" />
                </view>
            </view>

            <region-columns
                :key="String(open)"
                :model-value="contextValue"
                :tree="tree"
                :show-header="false"
                @change="onChange"
            />

            <text class="sheet__tip">选到区县后自动生效，价格与服务范围按区县计算</text>
        </view>
    </tn-popup>
</template>

<script setup lang="ts">
/**
 * 查档期 · 地区弹窗。
 *
 * 2026-11-10：由「省 → 市 → 区 逐级下钻」改为与预约页完全一致的**三栏联动**，
 * 控件统一收在 components/region-columns，两处样式不再各写一套。
 *
 * 四条约束不变：
 *  1. 后端 `hasRegionContext()` 要求 city_code 与 district_code 同时非空
 *     （PackageRegionPriceService），缺区县时价格会静默退回全国价 —— 必须选到区县；
 *  2. 地区是查档期的硬必选，不提供「全国（不限地区）」；
 *  3. 换上级自动清空下级（由组件内部保证，只在最后一级回调）；
 *  4. 对外仍是 PickedRegion（camelCase），与页面既有结构对齐，只在边界做一次转换。
 *  5. 组件按 open 值做 key：每次打开重新挂载，避免上次「只选了省就关掉」的半选状态残留。
 */
import { computed } from 'vue'
import type { SlimProvince, PickedRegion } from '@/composables/useCatalogCache'
import type { RegionContext } from '@/utils/region'

const props = withDefaults(
    defineProps<{
        open: boolean
        modelValue: PickedRegion | null
        tree: SlimProvince[]
    }>(),
    {
        modelValue: null,
        tree: () => []
    }
)

const emit = defineEmits<{
    (e: 'update:open', v: boolean): void
    (e: 'pick', v: PickedRegion): void
}>()

/** 页面用 camelCase，组件用六字段 RegionContext：只在边界转换一次 */
const contextValue = computed<RegionContext | null>(() => {
    const v = props.modelValue
    if (!v?.districtCode) return null
    return {
        province_code: v.provinceCode || '',
        province_name: v.provinceName || '',
        city_code: v.cityCode || '',
        city_name: v.cityName || '',
        district_code: v.districtCode || '',
        district_name: v.districtName || ''
    }
})

function onChange(value: RegionContext | null) {
    if (!value?.district_code) return
    emit('pick', {
        provinceCode: value.province_code,
        provinceName: value.province_name,
        cityCode: value.city_code,
        cityName: value.city_name,
        districtCode: value.district_code,
        districtName: value.district_name
    })
    close()
}

function close() {
    emit('update:open', false)
}

function onVisible(v: boolean) {
    if (!v) close()
}
</script>

<style lang="scss" scoped>
.sheet {
    padding: 30rpx 42rpx calc(24rpx + var(--safe-bottom));

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: var(--sp-3);
        color: var(--c-ink-3);
    }

    &__title {
        font-size: var(--fs-30);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__close {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 72rpx;
        height: 72rpx;
        margin: -12rpx -12rpx -12rpx 0;
    }

    &__tip {
        display: block;
        margin-top: var(--sp-2);
        font-size: var(--fs-18);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }
}
</style>
