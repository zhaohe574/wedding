<template>
    <view v-if="open" class="base-service-region-picker" :style="{ zIndex }" @click="handleCancel" @touchmove.stop.prevent="stopPageTouchMove">
        <BaseOverlayMask :show="open" :z-index="maskZIndex" @close="handleCancel" />
        <view
            class="base-service-region-picker__panel"
            :style="{ zIndex: zIndex + 1 }"
            @click.stop
            @touchmove.stop="stopPanelTouchMove"
        >
            <view class="base-service-region-picker__toolbar">
                <text class="base-service-region-picker__action" @click="handleCancel">取消</text>
                <text
                    class="base-service-region-picker__action base-service-region-picker__action--confirm"
                    @click="handleConfirm"
                >
                    {{ confirmText }}
                </text>
            </view>

            <view v-if="safeTree.length" class="base-service-region-picker__wheel-wrap">
                <view class="base-service-region-picker__indicator"></view>
                <picker-view
                    class="base-service-region-picker__wheel"
                    :value="pickerValue"
                    indicator-style="height: 68rpx;"
                    mask-style="background: transparent;"
                    @change="handlePickerChange"
                >
                    <picker-view-column>
                        <view
                            v-for="(province, index) in safeTree"
                            :key="province.province_code"
                            class="base-service-region-picker__option"
                            :class="{
                                'base-service-region-picker__option--active':
                                    index === pickerValue[0]
                            }"
                        >
                            <text class="base-service-region-picker__option-text">{{
                                province.province_name
                            }}</text>
                        </view>
                    </picker-view-column>
                    <picker-view-column>
                        <view
                            v-for="(city, index) in currentCities"
                            :key="city.city_code"
                            class="base-service-region-picker__option"
                            :class="{
                                'base-service-region-picker__option--active':
                                    index === pickerValue[1]
                            }"
                        >
                            <text class="base-service-region-picker__option-text">{{
                                city.city_name
                            }}</text>
                        </view>
                    </picker-view-column>
                    <picker-view-column>
                        <view
                            v-for="(district, index) in currentDistricts"
                            :key="district.district_code"
                            class="base-service-region-picker__option"
                            :class="{
                                'base-service-region-picker__option--active':
                                    index === pickerValue[2]
                            }"
                        >
                            <text class="base-service-region-picker__option-text">{{
                                district.district_name
                            }}</text>
                        </view>
                    </picker-view-column>
                </picker-view>
            </view>

            <view v-else class="base-service-region-picker__empty-state">
                <text class="base-service-region-picker__empty-title">暂无可选服务地区</text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import BaseOverlayMask from './BaseOverlayMask.vue'

export interface ServiceRegionValue {
    province_code: string
    province_name: string
    city_code: string
    city_name: string
    district_code: string
    district_name: string
}

interface NormalizedDistrict {
    province_code: string
    province_name: string
    city_code: string
    city_name: string
    district_code: string
    district_name: string
}

interface NormalizedCity {
    province_code: string
    province_name: string
    city_code: string
    city_name: string
    districts: NormalizedDistrict[]
}

interface NormalizedProvince {
    province_code: string
    province_name: string
    cities: NormalizedCity[]
}

interface Props {
    modelValue?: Partial<ServiceRegionValue>
    open?: boolean
    data?: any[]
    title?: string
    description?: string
    confirmText?: string
    zIndex?: number
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: () => ({}),
    open: false,
    data: () => [],
    title: '选择服务地区',
    description: '仅展示后台已开通服务的地区。',
    confirmText: '确认',
    zIndex: 20080
})

const emit = defineEmits<{
    (event: 'update:modelValue', value: ServiceRegionValue): void
    (event: 'update:open', value: boolean): void
    (event: 'confirm', value: ServiceRegionValue): void
    (event: 'cancel'): void
}>()

const emptyRegion = (): ServiceRegionValue => ({
    province_code: '',
    province_name: '',
    city_code: '',
    city_name: '',
    district_code: '',
    district_name: ''
})

const normalizeString = (value: unknown) => String(value || '').trim()
const normalizeRegion = (value?: Partial<ServiceRegionValue> | null): ServiceRegionValue => ({
    province_code: normalizeString(value?.province_code),
    province_name: normalizeString(value?.province_name),
    city_code: normalizeString(value?.city_code),
    city_name: normalizeString(value?.city_name),
    district_code: normalizeString(value?.district_code),
    district_name: normalizeString(value?.district_name)
})
const getValue = (item: Record<string, any>, keys: string[]) => {
    for (const key of keys) {
        const value = normalizeString(item?.[key])
        if (value) return value
    }
    return ''
}

const normalizeTree = (tree: any[]): NormalizedProvince[] =>
    (Array.isArray(tree) ? tree : [])
        .map((province) => {
            const provinceCode = getValue(province, ['province_code', 'code', 'value', 'id'])
            const provinceName = getValue(province, ['province_name', 'name', 'label'])
            const cities = (
                Array.isArray(province?.cities) ? province.cities : province?.children || []
            )
                .map((city: any) => {
                    const cityCode = getValue(city, ['city_code', 'code', 'value', 'id'])
                    const cityName = getValue(city, ['city_name', 'name', 'label'])
                    const districts = (
                        Array.isArray(city?.districts) ? city.districts : city?.children || []
                    )
                        .map((district: any) => ({
                            province_code: getValue(district, ['province_code']) || provinceCode,
                            province_name: getValue(district, ['province_name']) || provinceName,
                            city_code: getValue(district, ['city_code']) || cityCode,
                            city_name: getValue(district, ['city_name']) || cityName,
                            district_code: getValue(district, [
                                'district_code',
                                'code',
                                'value',
                                'id'
                            ]),
                            district_name: getValue(district, ['district_name', 'name', 'label'])
                        }))
                        .filter(
                            (district: NormalizedDistrict) =>
                                district.district_code && district.district_name
                        )
                    return {
                        province_code: getValue(city, ['province_code']) || provinceCode,
                        province_name: getValue(city, ['province_name']) || provinceName,
                        city_code: cityCode,
                        city_name: cityName,
                        districts
                    }
                })
                .filter(
                    (city: NormalizedCity) =>
                        city.city_code && city.city_name && city.districts.length
                )
            return {
                province_code: provinceCode,
                province_name: provinceName,
                cities
            }
        })
        .filter(
            (province: NormalizedProvince) =>
                province.province_code && province.province_name && province.cities.length
        )

const draftValue = ref<ServiceRegionValue>(emptyRegion())
const pickerValue = ref([0, 0, 0])

const safeTree = computed(() => normalizeTree(props.data))
const maskZIndex = computed(() => Math.max(0, props.zIndex - 1))
const currentProvince = computed(() => safeTree.value[pickerValue.value[0]])
const currentCities = computed(() => currentProvince.value?.cities || [])
const currentCity = computed(() => currentCities.value[pickerValue.value[1]])
const currentDistricts = computed(() => currentCity.value?.districts || [])

const clampIndex = (index: number, length: number) => {
    if (length <= 0) return 0
    return Math.min(Math.max(index, 0), length - 1)
}

const buildRegionByIndexes = (indexes = pickerValue.value) => {
    const province = safeTree.value[clampIndex(indexes[0] || 0, safeTree.value.length)]
    const city = province?.cities[clampIndex(indexes[1] || 0, province?.cities.length || 0)]
    const district = city?.districts[clampIndex(indexes[2] || 0, city?.districts.length || 0)]
    if (!province || !city || !district) return emptyRegion()
    return normalizeRegion(district)
}

const syncDraftByIndexes = (indexes: number[]) => {
    pickerValue.value = indexes
    draftValue.value = buildRegionByIndexes(indexes)
}

const findIndexesByRegion = (value?: Partial<ServiceRegionValue> | null) => {
    const region = normalizeRegion(value)
    const provinceIndex = Math.max(
        safeTree.value.findIndex((province) => province.province_code === region.province_code),
        0
    )
    const province = safeTree.value[provinceIndex]
    const cityIndex = Math.max(
        province?.cities.findIndex((city) => city.city_code === region.city_code) ?? -1,
        0
    )
    const city = province?.cities[cityIndex]
    const districtIndex = Math.max(
        city?.districts.findIndex(
            (district) => district.district_code === region.district_code
        ) ?? -1,
        0
    )
    return [
        clampIndex(provinceIndex, safeTree.value.length),
        clampIndex(cityIndex, province?.cities.length || 0),
        clampIndex(districtIndex, city?.districts.length || 0)
    ]
}

const syncDraft = () => {
    if (!safeTree.value.length) {
        pickerValue.value = [0, 0, 0]
        draftValue.value = emptyRegion()
        return
    }
    syncDraftByIndexes(findIndexesByRegion(props.modelValue))
}

watch(
    () => props.open,
    (open) => {
        if (open) syncDraft()
    },
    { immediate: true }
)

watch(
    () => props.modelValue,
    () => {
        if (!props.open) {
            draftValue.value = normalizeRegion(props.modelValue)
        }
    },
    { deep: true }
)

watch(
    safeTree,
    () => {
        if (props.open) syncDraft()
    }
)

const handlePickerChange = (event: any) => {
    const rawValue = Array.isArray(event?.detail?.value) ? event.detail.value : [0, 0, 0]
    const previousValue = pickerValue.value
    const nextProvinceIndex = clampIndex(Number(rawValue[0]) || 0, safeTree.value.length)
    const province = safeTree.value[nextProvinceIndex]
    const nextCityIndex =
        nextProvinceIndex !== previousValue[0]
            ? 0
            : clampIndex(Number(rawValue[1]) || 0, province?.cities.length || 0)
    const city = province?.cities[nextCityIndex]
    const nextDistrictIndex =
        nextProvinceIndex !== previousValue[0] || nextCityIndex !== previousValue[1]
            ? 0
            : clampIndex(Number(rawValue[2]) || 0, city?.districts.length || 0)
    syncDraftByIndexes([nextProvinceIndex, nextCityIndex, nextDistrictIndex])
}

const closePicker = () => {
    emit('update:open', false)
}

const handleCancel = () => {
    draftValue.value = normalizeRegion(props.modelValue)
    closePicker()
    emit('cancel')
}

const handleConfirm = () => {
    if (!safeTree.value.length) {
        uni.showToast({
            title: '暂无可选服务地区',
            icon: 'none'
        })
        return
    }
    const nextValue = normalizeRegion(draftValue.value)
    if (!nextValue.city_code || !nextValue.district_code) {
        uni.showToast({
            title: '请选择到区县',
            icon: 'none'
        })
        return
    }
    emit('update:modelValue', nextValue)
    emit('confirm', nextValue)
    closePicker()
}

const stopPanelTouchMove = () => {
    return undefined
}

const stopPageTouchMove = () => {
    return undefined
}
</script>

<script lang="ts">
export default {
    name: 'BaseServiceRegionPicker',
    options: {
        virtualHost: true
    }
}
</script>

<style lang="scss" scoped>
.base-service-region-picker {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    pointer-events: auto;
    overflow: hidden;
}

.base-service-region-picker__panel {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: auto;
    box-sizing: border-box;
    padding: 28rpx 0 calc(18rpx + env(safe-area-inset-bottom));
    border-radius: var(--wm-radius-popup, 44rpx) var(--wm-radius-popup, 44rpx) 0 0;
    background: var(--wm-color-bg-card, #FFFDF8);
    box-shadow: 0 -18rpx 48rpx rgba(25, 23, 19, 0.14);
    overflow: hidden;
}

.base-service-region-picker__toolbar {
    height: 52rpx;
    padding: 0 32rpx;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-sizing: border-box;
}

.base-service-region-picker__action {
    font-size: 28rpx;
    font-weight: 700;
    line-height: 1;
    color: var(--wm-text-secondary, #665E52);
}

.base-service-region-picker__action--confirm {
    color: var(--wm-color-gold, #B8954A);
}

.base-service-region-picker__wheel-wrap {
    position: relative;
    margin-top: 28rpx;
    height: 320rpx;
}

.base-service-region-picker__indicator {
    position: absolute;
    left: 32rpx;
    right: 32rpx;
    top: 126rpx;
    height: 68rpx;
    border-top: 1rpx solid rgba(216, 201, 173, 0.42);
    border-bottom: 1rpx solid rgba(216, 201, 173, 0.42);
    pointer-events: none;
    z-index: 1;
}

.base-service-region-picker__wheel {
    position: relative;
    z-index: 2;
    width: 100%;
    height: 320rpx;
}

.base-service-region-picker__option {
    height: 68rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 10rpx;
    box-sizing: border-box;
    color: rgba(102, 94, 82, 0.32);
}

.base-service-region-picker__option--active {
    color: var(--wm-text-primary, #191713);
    font-weight: 900;
}

.base-service-region-picker__option-text {
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 30rpx;
    line-height: 1;
}

.base-service-region-picker__empty-state {
    height: 320rpx;
    margin-top: 28rpx;
    display: flex;
    align-items: center;
    justify-content: center;
}

.base-service-region-picker__empty-title {
    font-size: 28rpx;
    font-weight: 800;
    color: var(--wm-text-tertiary, #8A806F);
}
</style>
